<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Photo lists over REST, so new factory and visit photos can be added through the REST API or the WordPress.com
 * connector (the "meta" object of a product or page update) instead of on the server:
 *
 *   POST /wp/v2/product/<id> {"meta": {"_gallery": "[{\"image\": \"https://…/photo.jpg\", \"label\": \"SQ4ZA3 (factory photo)\"}]"}}
 *     The product's photo gallery, a JSON array of {image, label} rows as the product edit screen stores it. Each
 *     Polylang language version holds its own copy, so write every translation.
 *   POST /wp/v2/pages/<id> {"meta": {"_boblift_visit_items": "[{\"id\": 123, \"country\": \"Portugal\", \"title\": \"…\"}]"}}
 *     Extra photos for the Global Customer Visits page, a JSON array of {id, country, title} rows (id = media library
 *     item). They are shown before the photos saved in the About page settings; the English page's list is used for
 *     every language unless a translation has its own.
 *
 * A request with a malformed value is refused before anything is saved.
 */
function boblift_register_gallery_rest_meta(): void
{
    $auth = static fn($allowed, $meta_key, $post_id): bool => current_user_can('edit_post', (int) $post_id);
    register_post_meta('product', '_gallery', [
        'type' => 'string',
        'single' => true,
        'default' => '',
        'show_in_rest' => true,
        'sanitize_callback' => static fn($value): string => boblift_rows_to_json(boblift_case_rest_rows($value) ?? [], ['image', 'label']),
        'auth_callback' => $auth,
    ]);
    register_post_meta('page', '_boblift_visit_items', [
        'type' => 'string',
        'single' => true,
        'default' => '',
        'show_in_rest' => true,
        'sanitize_callback' => static function ($value): string {
            $items = [];
            foreach (boblift_case_rest_rows($value) ?? [] as $row) {
                $id = absint($row['id'] ?? 0);
                if ($id > 0) {
                    $items[] = ['id' => $id, 'country' => sanitize_text_field((string) ($row['country'] ?? '')), 'title' => sanitize_text_field((string) ($row['title'] ?? ''))];
                }
            }
            return wp_json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
        },
        'auth_callback' => $auth,
    ]);
    // The core REST API only exposes registered meta on post types that support custom fields.
    add_post_type_support('product', 'custom-fields');
    add_post_type_support('page', 'custom-fields');
}
add_action('init', 'boblift_register_gallery_rest_meta', 20);

// Core REST returns registered meta whatever the post password, so a protected product or page keeps its photo lists
// (and other meta) out of responses for anyone who cannot edit it, as protected cases do (inc/case-rest.php).
add_filter('rest_prepare_product', 'boblift_hide_protected_case_rest_meta', 10, 2);
add_filter('rest_prepare_page', 'boblift_hide_protected_case_rest_meta', 10, 2);

/** Checks one photo-list meta value; returns an error message, or '' when it is fine (or not sent). */
function boblift_gallery_rest_error(array $meta, string $key, array $columns, string $id_column = ''): string
{
    if (!array_key_exists($key, $meta) || $meta[$key] === '' || $meta[$key] === null) {
        return '';
    }
    $rows = boblift_case_rest_rows($meta[$key]);
    if ($rows === null) {
        return 'expected a JSON array of objects with ' . implode(', ', $columns) . '.';
    }
    foreach ($rows as $row) {
        if (array_diff(array_keys($row), $columns) || array_filter($row, static fn($cell): bool => !is_scalar($cell))) {
            return 'each row takes only ' . implode(', ', $columns) . ', as plain values.';
        }
        if ($id_column !== '' && !wp_attachment_is_image(absint($row[$id_column] ?? 0))) {
            return ($row[$id_column] ?? '(none)') . ' is not an image in the media library.';
        }
        if ($id_column === '' && trim((string) ($row['image'] ?? '')) === '') {
            return 'every row needs an image.';
        }
    }
    return '';
}

/** Refuses a create or update whose photo list would be stored wrong. Runs before the post is saved. */
function boblift_validate_gallery_rest_meta(stdClass|WP_Error $prepared, WP_REST_Request $request, string $key, array $columns, string $id_column): stdClass|WP_Error
{
    $meta = $request->get_param('meta');
    if (is_wp_error($prepared) || !is_array($meta)) {
        return $prepared;
    }
    $message = boblift_gallery_rest_error($meta, $key, $columns, $id_column);
    return $message === '' ? $prepared : new WP_Error('rest_invalid_param', $key . ': ' . $message, ['status' => 400, 'meta_key' => $key]);
}
add_filter('rest_pre_insert_product', static fn($prepared, $request) => boblift_validate_gallery_rest_meta($prepared, $request, '_gallery', ['image', 'label'], ''), 10, 2);
add_filter('rest_pre_insert_page', static fn($prepared, $request) => boblift_validate_gallery_rest_meta($prepared, $request, '_boblift_visit_items', ['id', 'country', 'title'], 'id'), 10, 2);

/** Visit photos added over REST to this page (or to its English version), as Global Customer Visits items. */
function boblift_rest_visit_items(int $page_id): array
{
    $rows = boblift_case_rest_rows(get_post_meta($page_id, '_boblift_visit_items', true)) ?? [];
    if (!$rows && function_exists('pll_get_post') && ($english = (int) pll_get_post($page_id, 'en')) && $english !== $page_id) {
        $rows = boblift_case_rest_rows(get_post_meta($english, '_boblift_visit_items', true)) ?? [];
    }
    $items = [];
    foreach ($rows as $row) {
        $id = absint($row['id'] ?? 0);
        if ($id > 0 && wp_attachment_is_image($id)) {
            $items[] = ['image' => ['id' => $id], 'country' => (string) ($row['country'] ?? ''), 'title' => (string) ($row['title'] ?? '')];
        }
    }
    return $items;
}
