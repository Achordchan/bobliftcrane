<?php
/**
 * Case details over REST: the fields of the case edit screen (案例详情), the gallery, the linked products and the
 * article JSON-LD are registered as post meta of wp/v2/case, so a case can be written in full through the REST API or the
 * WordPress.com connector (the "meta" object of a create or update) instead of on the server.
 *
 * Text fields are stored as on the edit screen. _facts and _sections take a JSON array of rows ({label, value} and
 * {heading, body}), _case_gallery a comma list of attachment IDs, _related_product one English product ID per value and
 * _article_schema_jsonld a JSON object or array ({{home}} for the site address). A request with a malformed value is
 * refused before anything is saved.
 */

/** Meta key => [schema type, sanitizer] for every case field that REST can read and write. */
function boblift_case_rest_meta_fields(): array
{
    $text = static fn($value): string => sanitize_text_field((string) $value);
    $textarea = static fn($value): string => sanitize_textarea_field((string) $value);
    return [
        '_case_kind' => ['string', static fn($value): string => in_array($value, ['scene_case', 'customer_feedback'], true) ? (string) $value : 'scene_case'],
        '_type' => ['string', $text],
        '_country' => ['string', $text],
        '_product_name' => ['string', $text],
        '_application' => ['string', $text],
        '_seo_title' => ['string', $text],
        '_summary' => ['string', $textarea],
        '_seo_description' => ['string', $textarea],
        '_feedback_quote' => ['string', $textarea],
        '_feedback_media_type' => ['string', static fn($value): string => $value === 'video' ? 'video' : 'image'],
        '_feedback_media_url' => ['string', static fn($value): string => esc_url_raw((string) $value)],
        '_facts' => ['string', static fn($value): string => boblift_rows_to_json(boblift_case_rest_rows($value) ?? [], ['label', 'value'])],
        '_sections' => ['string', static fn($value): string => boblift_rows_to_json(boblift_case_rest_rows($value) ?? [], ['heading', 'body'])],
        '_case_gallery' => ['string', static fn($value): string => implode(',', boblift_case_rest_ids($value))],
        '_related_product' => ['array', static fn($value): string => (string) absint($value)],
        '_article_schema_jsonld' => ['string', static function ($value): string {
            $decoded = json_decode((string) $value, true);
            return is_array($decoded) ? (string) wp_json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
        }],
    ];
}

/** Rows of a JSON array of objects, or null when the value is not one. */
function boblift_case_rest_rows(mixed $value): ?array
{
    $rows = is_string($value) ? json_decode($value, true) : null;
    if (!is_array($rows) || !array_is_list($rows)) {
        return null;
    }
    foreach ($rows as $row) {
        if (!is_array($row) || array_is_list($row) && $row !== []) {
            return null;
        }
    }
    return $rows;
}

/** Positive IDs of a comma list, in order and without repeats. */
function boblift_case_rest_ids(mixed $value): array
{
    return array_values(array_unique(array_filter(array_map('absint', explode(',', (string) $value)))));
}

function boblift_register_case_rest_meta(): void
{
    foreach (boblift_case_rest_meta_fields() as $key => [$type, $sanitize]) {
        $args = [
            'type' => 'string',
            'single' => $type !== 'array',
            'show_in_rest' => true,
            'sanitize_callback' => $sanitize,
            'auth_callback' => static fn($allowed, $meta_key, $post_id): bool => current_user_can('edit_post', (int) $post_id),
        ];
        if ($args['single']) {
            $args['default'] = '';
        }
        register_post_meta('case', $key, $args);
    }
    // The core REST API only exposes registered meta on post types that support custom fields.
    add_post_type_support('case', 'custom-fields');
}
add_action('init', 'boblift_register_case_rest_meta', 20);

/**
 * Refuses a case create or update whose meta would be stored wrong: rows that are not a JSON array of objects, schema that
 * is not JSON, gallery IDs that are not attachments, linked IDs that are not products. Runs before the post is saved.
 */
function boblift_validate_case_rest_meta(stdClass|WP_Error $prepared, WP_REST_Request $request): stdClass|WP_Error
{
    $meta = $request->get_param('meta');
    if (is_wp_error($prepared) || !is_array($meta)) {
        return $prepared;
    }
    $error = static fn(string $key, string $message): WP_Error => new WP_Error('rest_invalid_param', $key . ': ' . $message, ['status' => 400, 'meta_key' => $key]);
    foreach (['_facts' => ['label', 'value'], '_sections' => ['heading', 'body']] as $key => $columns) {
        if (!array_key_exists($key, $meta) || $meta[$key] === '' || $meta[$key] === null) {
            continue;
        }
        $rows = boblift_case_rest_rows($meta[$key]);
        if ($rows === null) {
            return $error($key, 'expected a JSON array of objects with ' . implode(' and ', $columns) . '.');
        }
        foreach ($rows as $row) {
            if (array_diff(array_keys($row), $columns) || array_filter($row, static fn($cell): bool => !is_scalar($cell))) {
                return $error($key, 'each row takes only ' . implode(' and ', $columns) . ', as text.');
            }
        }
    }
    if (isset($meta['_article_schema_jsonld']) && $meta['_article_schema_jsonld'] !== '' && !is_array(json_decode((string) $meta['_article_schema_jsonld'], true))) {
        return $error('_article_schema_jsonld', 'expected a JSON object or array.');
    }
    if (isset($meta['_case_gallery'])) {
        if (!is_string($meta['_case_gallery']) || !preg_match('/^\s*(\d+\s*(,\s*\d+\s*)*)?$/', $meta['_case_gallery'])) {
            return $error('_case_gallery', 'expected a comma list of media IDs.');
        }
        foreach (boblift_case_rest_ids($meta['_case_gallery']) as $id) {
            if (get_post_type($id) !== 'attachment') {
                return $error('_case_gallery', $id . ' is not a media library item.');
            }
        }
    }
    if (isset($meta['_related_product'])) {
        if (!is_array($meta['_related_product'])) {
            return $error('_related_product', 'expected an array of product IDs.');
        }
        foreach ($meta['_related_product'] as $id) {
            if (!is_numeric($id) || get_post_type((int) $id) !== 'product') {
                return $error('_related_product', $id . ' is not a product.');
            }
        }
    }
    return $prepared;
}
add_filter('rest_pre_insert_case', 'boblift_validate_case_rest_meta', 10, 2);

/**
 * A password-protected case shows its details only after the password is entered (single-case.php), but core REST returns
 * registered meta whatever the post password. Its meta is left out of REST responses for anyone who cannot edit the case.
 */
function boblift_hide_protected_case_rest_meta(WP_REST_Response $response, WP_Post $post): WP_REST_Response
{
    if ($post->post_password !== '' && !current_user_can('edit_post', $post->ID)) {
        $data = $response->get_data();
        unset($data['meta']);
        $response->set_data($data);
    }
    return $response;
}
add_filter('rest_prepare_case', 'boblift_hide_protected_case_rest_meta', 10, 2);
