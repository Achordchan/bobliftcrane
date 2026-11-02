<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST: set up a new product and its category in every language through the standard wp/v2 routes, which is all the
 * WordPress.com connector reaches. Until now a product could only be created in the admin (its spec table, model, photos
 * and drawings are not REST meta, and boblift_translation files posts and cases only), so a new model meant hand work on
 * the server.
 *
 * 1. boblift_product_setup on wp/v2/product, sent when creating the product or on its own afterwards:
 *    {"lang": "en", "meta": {...}}                       files the product in English and writes the meta below;
 *    {"lang": "zh", "source": 123, "meta": {...}}        files it as the "zh" translation of English product 123. The
 *       translation takes the source's slug, featured image and categories (in its language) and, unless "meta" names
 *       them, the source's model, capacity value, boom type, photos, drawings and "show body/images" boxes; "meta"
 *       carries the translated texts. Title, body and excerpt are sent as usual with the item.
 *    Meta keys (any language):
 *      _model, _capacity, _status, _model_aliases, _short_description, _seo_title, _seo_description   plain text
 *      _capacity_value                    number of tonnes (filters, tonnage pages)
 *      _boom_type                         one of the product-list boom options, or "" for none
 *      _specs      [{"label", "value"}]   the spec table; the English labels "Max Lifting Capacity", "Max Lifting Moment"
 *                                         and "Self Weight" feed the list filters
 *      _features   [{"title", "text", "detail", "image"}]
 *      _gallery    [{"image": URL, "label"}]
 *      _drawings   [{"id": media ID, "label"}]   dimension drawings and load charts shown beside the spec table
 *      _applications, _tags               lists of plain-text items
 *      _detail_body_ready, _detail_images_ready   true/false: show the body / detail images on the product page
 *    English product only:
 *      _boblift_drawing_data  {"load_chart": [[radius m, load kg], ...], "max_radius_m", "max_height_m",
 *                              "max_height_approx", "max_boom_angle_deg", "outrigger_span_mm"}, the figures the product
 *                              page reads off the drawing (see boblift_product_drawing_data()).
 *
 * 2. boblift_term_translation on wp/v2/product_category, sent when creating the category or on its own:
 *    {"lang": "en", "sort": 40}                          files the category in English (sort: its place in the lists);
 *    {"lang": "zh", "source": 61}                        files it as the "zh" translation of English category 61, with
 *       the source's slug (when languages have their own URLs, Polylang force_lang 1-3; otherwise a unique slug) and,
 *       as its parent, the parent's "zh" translation. Name and description are sent as usual.
 *
 * Every value is checked before anything is written; a create request whose value is refused creates nothing.
 */

/** The meta keys boblift_product_setup writes, and how each is checked: text, number, boom, rows:<keys>, drawings, list, flag, drawing_data. */
function boblift_product_setup_meta_types(): array
{
    return [
        '_model' => 'text',
        '_capacity' => 'text',
        '_status' => 'text',
        '_model_aliases' => 'text',
        '_short_description' => 'textarea',
        '_seo_title' => 'text',
        '_seo_description' => 'textarea',
        '_capacity_value' => 'number',
        '_boom_type' => 'boom',
        '_specs' => 'rows:label,value',
        '_features' => 'rows:title,text,detail,image',
        '_gallery' => 'gallery',
        '_drawings' => 'drawings',
        '_applications' => 'list',
        '_tags' => 'list',
        '_detail_body_ready' => 'flag',
        '_detail_images_ready' => 'flag',
        '_boblift_drawing_data' => 'drawing_data',
    ];
}

/** Meta a translation takes from its English product when the request does not name it: figures, photos and switches. */
function boblift_product_setup_shared_keys(): array
{
    return ['_model', '_capacity_value', '_boom_type', '_gallery', '_drawings', '_detail_body_ready', '_detail_images_ready', '_boblift_lifecycle', '_catalog_pdf_url'];
}

function boblift_register_rest_product_setup_fields(): void
{
    register_rest_field('product', 'boblift_product_setup', [
        'get_callback' => static function (array $post): array {
            $id = (int) $post['id'];
            return [
                'lang' => function_exists('pll_get_post_language') ? (string) pll_get_post_language($id) : '',
                'translations' => function_exists('pll_get_post_translations') ? (array) pll_get_post_translations($id) : [],
            ];
        },
        'update_callback' => static function ($value, WP_Post $post, string $field, WP_REST_Request $request) {
            try {
                $creating = $request->get_method() === 'POST' && empty($request['id']);
                $saved = boblift_rest_save_product_setup($post, is_array($value) ? $value : [], $creating);
                if (is_wp_error($saved) && $creating) {
                    wp_delete_post($post->ID, true);
                }
                return $saved;
            } finally {
                boblift_rest_translation_lock(false);
            }
        },
        'schema' => [
            'description' => 'Language, translation link and product meta; write {"lang", "source", "meta"} (see inc/rest-product-setup.php).',
            'type' => 'object',
            'context' => ['edit'],
        ],
    ]);
    register_rest_field('product_category', 'boblift_term_translation', [
        'get_callback' => static function (array $term): array {
            $id = (int) $term['id'];
            return [
                'lang' => function_exists('pll_get_term_language') ? (string) pll_get_term_language($id) : '',
                'translations' => function_exists('pll_get_term_translations') ? (array) pll_get_term_translations($id) : [],
                'sort' => (string) get_term_meta($id, '_boblift_sort', true),
            ];
        },
        'update_callback' => static function ($value, WP_Term $term, string $field, WP_REST_Request $request) {
            try {
                $creating = $request->get_method() === 'POST' && empty($request['id']);
                $saved = boblift_rest_save_term_translation($term, is_array($value) ? $value : [], $creating);
                if (is_wp_error($saved) && $creating) {
                    wp_delete_term($term->term_id, $term->taxonomy);
                }
                return $saved;
            } finally {
                boblift_rest_translation_lock(false);
            }
        },
        'schema' => [
            'description' => 'Polylang language and translations of the category; write {"lang", "source", "sort"}.',
            'type' => 'object',
            'context' => ['view', 'edit'],
        ],
    ]);
}
add_action('rest_api_init', 'boblift_register_rest_product_setup_fields');

/** One meta value checked and turned into what is stored, or a WP_Error naming the key. */
function boblift_product_setup_meta_value(string $key, mixed $value): string|WP_Error
{
    $type = boblift_product_setup_meta_types()[$key] ?? '';
    $bad = static fn(string $why): WP_Error => new WP_Error('boblift_product_setup_meta', sprintf('meta %s: %s', $key, $why), ['status' => 400]);
    $json = static fn(array $data): string => (string) wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    switch (true) {
        case $type === 'text' || $type === 'textarea':
            if (!is_string($value)) {
                return $bad('expected a string');
            }
            return $type === 'text' ? sanitize_text_field($value) : sanitize_textarea_field($value);
        case $type === 'number':
            if (!is_numeric($value) || (float) $value < 0) {
                return $bad('expected a number of tonnes');
            }
            return rtrim(rtrim(number_format((float) $value, 3, '.', ''), '0'), '.');
        case $type === 'boom':
            if (!is_string($value)) {
                return $bad('expected a string');
            }
            $boom = boblift_normalize_product_boom_type($value);
            if ($value !== '' && $boom === '') {
                return $bad('unknown boom type; use one of ' . implode(', ', array_keys(boblift_product_boom_options())) . ' or ""');
            }
            return $boom;
        case str_starts_with($type, 'rows:') || $type === 'gallery':
            $keys = $type === 'gallery' ? ['image', 'label'] : explode(',', substr($type, 5));
            if (!is_array($value) || !array_is_list($value)) {
                return $bad('expected a list of rows');
            }
            foreach ($value as $row) {
                if (!is_array($row) || array_diff(array_keys($row), $keys) !== [] || array_filter($row, static fn($cell): bool => !is_string($cell)) !== []) {
                    return $bad('each row is an object with string fields ' . implode(', ', $keys));
                }
                if ($type === 'gallery' && !wp_http_validate_url((string) ($row['image'] ?? ''))) {
                    return $bad('each photo needs an image URL');
                }
            }
            return boblift_rows_to_json($value, $keys);
        case $type === 'drawings':
            if (!is_array($value) || !array_is_list($value)) {
                return $bad('expected a list of {id, label}');
            }
            $rows = [];
            foreach ($value as $row) {
                $id = is_array($row) ? (int) ($row['id'] ?? 0) : 0;
                if ($id <= 0 || get_post_type($id) !== 'attachment' || !wp_attachment_is_image($id)) {
                    return $bad('each drawing needs the "id" of an image in the media library');
                }
                $rows[] = ['id' => $id, 'label' => sanitize_text_field((string) ($row['label'] ?? ''))];
            }
            return $json($rows);
        case $type === 'list':
            if (!is_array($value) || !array_is_list($value) || array_filter($value, static fn($item): bool => !is_string($item)) !== []) {
                return $bad('expected a list of strings');
            }
            return boblift_lines_to_csv(implode("\n", $value));
        case $type === 'flag':
            if (!is_bool($value)) {
                return $bad('expected true or false');
            }
            return $value ? '1' : '';
        case $type === 'drawing_data':
            if (!is_array($value) || array_is_list($value)) {
                return $bad('expected an object');
            }
            $data = [];
            $chart = $value['load_chart'] ?? [];
            if (!is_array($chart) || !array_is_list($chart)) {
                return $bad('load_chart is a list of [radius m, load kg] pairs');
            }
            foreach ($chart as $point) {
                if (!is_array($point) || count($point) !== 2 || !is_numeric($point[0] ?? null) || !is_numeric($point[1] ?? null) || (float) $point[0] <= 0 || (float) $point[1] <= 0) {
                    return $bad('load_chart is a list of [radius m, load kg] pairs');
                }
                $data['load_chart'][] = [(float) $point[0], (float) $point[1]];
            }
            foreach (['max_radius_m', 'max_height_m', 'max_boom_angle_deg'] as $number) {
                if (array_key_exists($number, $value) && $value[$number] !== null) {
                    if (!is_numeric($value[$number]) || (float) $value[$number] <= 0) {
                        return $bad($number . ' is a positive number or null');
                    }
                    $data[$number] = (float) $value[$number];
                }
            }
            if (array_key_exists('max_height_approx', $value)) {
                if (!is_bool($value['max_height_approx'])) {
                    return $bad('max_height_approx is true or false');
                }
                $data['max_height_approx'] = $value['max_height_approx'];
            }
            if (isset($value['outrigger_span_mm'])) {
                $span = trim((string) $value['outrigger_span_mm']);
                if (!preg_match('/^\d+(?:\s*[-~–xX×]\s*\d+)?$/u', $span)) {
                    return $bad('outrigger_span_mm is a number, a range ("2250-8600") or a footprint ("3686 x 3423")');
                }
                $data['outrigger_span_mm'] = $span;
            }
            $unknown = array_diff(array_keys($value), ['load_chart', 'max_radius_m', 'max_height_m', 'max_height_approx', 'max_boom_angle_deg', 'outrigger_span_mm']);
            if ($unknown) {
                return $bad('unknown fields ' . implode(', ', $unknown));
            }
            return $json($data);
    }
    return $bad('not a meta key this field writes; use one of ' . implode(', ', array_keys(boblift_product_setup_meta_types())));
}

/**
 * Checks a boblift_product_setup value against the stored data; create requests run it before the post is inserted.
 *
 * @return array{lang: string, source: ?WP_Post, meta: array<string, string>}|WP_Error
 */
function boblift_product_setup_check(int $post_id, array $value, bool $creating = false): array|WP_Error
{
    if (!function_exists('pll_set_post_language') || !function_exists('pll_save_post_translations') || !function_exists('pll_languages_list')) {
        return new WP_Error('boblift_product_setup_polylang', 'Polylang is not active.', ['status' => 500]);
    }
    $unknown = array_diff(array_keys($value), ['lang', 'source', 'meta']);
    if ($unknown) {
        return new WP_Error('boblift_product_setup_keys', 'Unknown keys: ' . implode(', ', $unknown) . '. Send lang, source and meta.', ['status' => 400]);
    }
    $lang = sanitize_key((string) ($value['lang'] ?? ''));
    if (!in_array($lang, array_map('strval', (array) pll_languages_list(['fields' => 'slug'])), true)) {
        return new WP_Error('boblift_product_setup_lang', 'Send "lang", one of the site languages.', ['status' => 400]);
    }
    $source = null;
    if (isset($value['source'])) {
        $source = get_post((int) $value['source']);
        if ($lang === 'en' || !$source instanceof WP_Post || $source->post_type !== 'product' || $source->ID === $post_id || (string) pll_get_post_language($source->ID) !== 'en') {
            return new WP_Error('boblift_product_setup_source', '"source" must be an English product, and "lang" another language.', ['status' => 400]);
        }
        if (!current_user_can('edit_post', $source->ID)) {
            return new WP_Error('rest_cannot_edit', 'Sorry, you are not allowed to edit the source product.', ['status' => rest_authorization_required_code()]);
        }
        // A product this request just inserted may carry Polylang's default language; the requested one replaces it.
        $stored = $post_id > 0 && !$creating ? (string) pll_get_post_language($post_id) : '';
        if ($stored !== '' && $stored !== $lang) {
            return new WP_Error('boblift_product_setup_lang', sprintf('This product is in "%s"; its language cannot be changed here.', $stored), ['status' => 400]);
        }
        $existing = (int) pll_get_post($source->ID, $lang);
        if ($existing > 0 && $existing !== $post_id && get_post($existing) instanceof WP_Post) {
            return new WP_Error('boblift_product_setup_exists', 'The source already has a ' . $lang . ' translation (ID ' . $existing . ').', ['status' => 409]);
        }
        if (function_exists('pll_get_term')) {
            $terms = wp_get_post_terms($source->ID, 'product_category', ['fields' => 'ids']);
            $missing = array_filter(is_array($terms) ? $terms : [], static fn(int $id): bool => (int) pll_get_term($id, $lang) <= 0);
            if ($missing) {
                return new WP_Error('boblift_product_setup_category', 'These categories of the source have no ' . $lang . ' translation: ' . implode(', ', $missing) . '.', ['status' => 400]);
            }
        }
    } else {
        // Without "source" the request only writes meta: it may file a product that has no language yet in English, or
        // name the language the product already has. Moving a product to another language would leave its translation
        // group and its slug (shared with the English product) inconsistent.
        $stored = $post_id > 0 && !$creating ? (string) pll_get_post_language($post_id) : '';
        if ($stored !== '' && $stored !== $lang) {
            return new WP_Error('boblift_product_setup_lang', sprintf('This product is in "%s"; its language cannot be changed here.', $stored), ['status' => 400]);
        }
        if ($stored === '' && $lang !== 'en') {
            return new WP_Error('boblift_product_setup_source', 'A product in another language than English needs "source", its English product.', ['status' => 400]);
        }
    }
    $meta = $value['meta'] ?? [];
    if (!is_array($meta) || ($meta !== [] && array_is_list($meta))) {
        return new WP_Error('boblift_product_setup_meta', '"meta" is an object of meta keys.', ['status' => 400]);
    }
    $stored = [];
    foreach ($meta as $key => $raw) {
        if ($key === '_boblift_drawing_data' && $lang !== 'en') {
            return new WP_Error('boblift_product_setup_meta', 'meta _boblift_drawing_data is written on the English product only.', ['status' => 400]);
        }
        $checked = boblift_product_setup_meta_value((string) $key, $raw);
        if (is_wp_error($checked)) {
            return $checked;
        }
        $stored[(string) $key] = $checked;
    }
    return ['lang' => $lang, 'source' => $source, 'meta' => $stored];
}

function boblift_rest_pre_insert_product_setup(stdClass|WP_Error $prepared, WP_REST_Request $request): stdClass|WP_Error
{
    $value = $request['boblift_product_setup'];
    if (is_wp_error($prepared) || !is_array($value)) {
        return $prepared;
    }
    if (!boblift_rest_translation_lock(true)) {
        return new WP_Error('boblift_translation_busy', 'Another request is writing a translation; try again.', ['status' => 409]);
    }
    $checked = boblift_product_setup_check((int) ($prepared->ID ?? 0), $value);
    if (is_wp_error($checked)) {
        boblift_rest_translation_lock(false);
        return $checked;
    }
    return $prepared;
}
add_filter('rest_pre_insert_product', 'boblift_rest_pre_insert_product_setup', 5, 2);

function boblift_rest_save_product_setup(WP_Post $post, array $value, bool $creating = false): true|WP_Error
{
    if (!current_user_can('edit_post', $post->ID)) {
        return new WP_Error('rest_cannot_edit', 'Sorry, you are not allowed to edit this product.', ['status' => rest_authorization_required_code()]);
    }
    $checked = boblift_product_setup_check($post->ID, $value, $creating);
    if (is_wp_error($checked)) {
        return $checked;
    }
    ['lang' => $lang, 'source' => $source, 'meta' => $meta] = $checked;

    pll_set_post_language($post->ID, $lang);
    if ($source instanceof WP_Post) {
        $translations = array_filter((array) pll_get_post_translations($source->ID), static fn($id): bool => get_post((int) $id) instanceof WP_Post);
        $translations['en'] = $source->ID;
        $translations[$lang] = $post->ID;
        pll_save_post_translations($translations);
    }
    foreach ($meta as $key => $stored) {
        update_post_meta($post->ID, $key, wp_slash($stored));
    }
    if ($source instanceof WP_Post) {
        foreach (boblift_product_setup_shared_keys() as $key) {
            if (!array_key_exists($key, $meta) && metadata_exists('post', $source->ID, $key)) {
                update_post_meta($post->ID, $key, wp_slash((string) get_post_meta($source->ID, $key, true)));
            }
        }
        $thumbnail = (int) get_post_thumbnail_id($source->ID);
        if ($thumbnail > 0 && !has_post_thumbnail($post->ID)) {
            set_post_thumbnail($post->ID, $thumbnail);
        }
        $terms = wp_get_post_terms($source->ID, 'product_category', ['fields' => 'ids']);
        $local = array_values(array_filter(array_map(static fn(int $id): int => (int) pll_get_term($id, $lang), is_array($terms) ? $terms : [])));
        // Also when empty: the translation leaves the families its source no longer belongs to.
        wp_set_object_terms($post->ID, $local, 'product_category');
        // A product translation keeps the English slug under its language prefix (inc/polylang-slugs.php).
        if ($post->post_name !== $source->post_name) {
            wp_update_post(['ID' => $post->ID, 'post_name' => $source->post_name]);
        }
    }
    return true;
}

/**
 * Checks a boblift_term_translation value; create requests run it before the term is inserted.
 *
 * @return array{lang: string, source: ?WP_Term, sort: ?int}|WP_Error
 */
function boblift_term_translation_check(int $term_id, array $value, bool $creating = false): array|WP_Error
{
    if (!function_exists('pll_set_term_language') || !function_exists('pll_save_term_translations') || !function_exists('pll_languages_list')) {
        return new WP_Error('boblift_term_translation_polylang', 'Polylang is not active.', ['status' => 500]);
    }
    $unknown = array_diff(array_keys($value), ['lang', 'source', 'sort']);
    if ($unknown) {
        return new WP_Error('boblift_term_translation_keys', 'Unknown keys: ' . implode(', ', $unknown) . '. Send lang, source and sort.', ['status' => 400]);
    }
    $lang = sanitize_key((string) ($value['lang'] ?? ''));
    if (!in_array($lang, array_map('strval', (array) pll_languages_list(['fields' => 'slug'])), true)) {
        return new WP_Error('boblift_term_translation_lang', 'Send "lang", one of the site languages.', ['status' => 400]);
    }
    $sort = null;
    if (array_key_exists('sort', $value)) {
        if ($lang !== 'en' || !is_int($value['sort'])) {
            return new WP_Error('boblift_term_translation_sort', '"sort" is a whole number, set on the English category (translations follow it).', ['status' => 400]);
        }
        $sort = $value['sort'];
    }
    $source = null;
    if (isset($value['source'])) {
        $source = get_term((int) $value['source'], 'product_category');
        if ($lang === 'en' || !$source instanceof WP_Term || $source->term_id === $term_id || (string) pll_get_term_language($source->term_id) !== 'en') {
            return new WP_Error('boblift_term_translation_source', '"source" must be an English product category, and "lang" another language.', ['status' => 400]);
        }
        $existing = (int) pll_get_term($source->term_id, $lang);
        // A link to a term that no longer exists (deleted after a failed request) does not count.
        if ($existing > 0 && $existing !== $term_id && get_term($existing, 'product_category') instanceof WP_Term) {
            return new WP_Error('boblift_term_translation_exists', 'The source already has a ' . $lang . ' translation (ID ' . $existing . ').', ['status' => 409]);
        }
        if ($source->parent > 0 && (int) pll_get_term($source->parent, $lang) <= 0) {
            return new WP_Error('boblift_term_translation_parent', 'The parent of the source has no ' . $lang . ' translation.', ['status' => 400]);
        }
        // Checked before anything changes: an update that is refused afterwards would not be rolled back.
        if (boblift_languages_have_own_urls() && !boblift_term_slug_free_in_language($source->slug, 'product_category', $lang, $term_id)) {
            return new WP_Error('boblift_term_translation_slug', sprintf('Another %s category already uses the slug "%s".', $lang, $source->slug), ['status' => 409]);
        }
    } elseif ($lang !== 'en') {
        return new WP_Error('boblift_term_translation_source', 'A category in another language than English needs "source", its English category.', ['status' => 400]);
    }
    // A category this request just inserted may carry Polylang's default language; the requested one replaces it.
    $stored = $term_id > 0 && !$creating ? (string) pll_get_term_language($term_id) : '';
    if ($stored !== '' && $stored !== $lang) {
        return new WP_Error('boblift_term_translation_lang', sprintf('This category is in "%s"; its language cannot be changed here.', $stored), ['status' => 400]);
    }
    return ['lang' => $lang, 'source' => $source, 'sort' => $sort];
}

// Runs before the terms controller writes anything: on a create or an update of a category (name, parent, description
// are saved before additional fields), a refused boblift_term_translation leaves the category as it was.
add_filter('rest_request_before_callbacks', static function ($response, $handler, WP_REST_Request $request) {
    if (is_wp_error($response) || !in_array($request->get_method(), ['POST', 'PUT', 'PATCH'], true) || !is_array($request['boblift_term_translation'])
        || !preg_match('#^/wp/v2/product_category(?:/(\d+))?/?$#', $request->get_route(), $match)) {
        return $response;
    }
    $term_id = (int) ($match[1] ?? 0);
    if ($term_id > 0 && !get_term($term_id, 'product_category') instanceof WP_Term) {
        return $response;
    }
    // Held from here through the field callback (released there or after the callbacks), so two requests cannot both pass
    // this check and then have one refused after the controller has saved its name, description or parent.
    if (!boblift_rest_translation_lock(true)) {
        return new WP_Error('boblift_translation_busy', 'Another request is writing a translation; try again.', ['status' => 409]);
    }
    $checked = boblift_term_translation_check($term_id, $request['boblift_term_translation']);
    if (is_wp_error($checked)) {
        boblift_rest_translation_lock(false);
        return $checked;
    }
    return $response;
}, 20, 3);

// Releases the translation lock when the controller stopped before the field callback (which releases it otherwise).
add_filter('rest_request_after_callbacks', static function ($response) {
    if (function_exists('boblift_rest_translation_lock')) {
        boblift_rest_translation_lock(false);
    }
    return $response;
}, 20);

/** True when each language has its own addresses (directory, subdomain or domain: Polylang force_lang 1-3), so a translation may share the English slug. */
function boblift_languages_have_own_urls(): bool
{
    $force_lang = (int) (((array) get_option('polylang', []))['force_lang'] ?? 0);
    return $force_lang >= 1 && $force_lang <= 3;
}

function boblift_rest_save_term_translation(WP_Term $term, array $value, bool $creating = false): true|WP_Error
{
    if (!current_user_can('manage_categories')) {
        return new WP_Error('rest_cannot_edit', 'Sorry, you are not allowed to edit categories.', ['status' => rest_authorization_required_code()]);
    }
    if (!boblift_rest_translation_lock(true)) {
        return new WP_Error('boblift_translation_busy', 'Another request is writing a translation; try again.', ['status' => 409]);
    }
    $checked = boblift_term_translation_check($term->term_id, $value, $creating);
    if (is_wp_error($checked)) {
        return $checked;
    }
    ['lang' => $lang, 'source' => $source, 'sort' => $sort] = $checked;

    pll_set_term_language($term->term_id, $lang);
    if ($sort !== null) {
        update_term_meta($term->term_id, '_boblift_sort', (string) $sort);
    }
    if (!$source instanceof WP_Term) {
        return true;
    }
    $translations = array_filter((array) pll_get_term_translations($source->term_id), static fn($id): bool => get_term((int) $id, 'product_category') instanceof WP_Term);
    $translations['en'] = $source->term_id;
    $translations[$lang] = $term->term_id;
    pll_save_term_translations($translations);
    $parent = $source->parent > 0 ? (int) pll_get_term($source->parent, $lang) : 0;
    // Parent first: wp_update_term() gives a slug that changes parent in the same call a unique (suffixed) form instead.
    if ((int) $term->parent !== $parent) {
        $updated = wp_update_term($term->term_id, 'product_category', ['parent' => $parent]);
        if (is_wp_error($updated)) {
            return $updated;
        }
        // The parent change may have suffixed the slug; decide on the slug from the stored term.
        clean_term_cache($term->term_id, 'product_category');
        $reloaded = get_term($term->term_id, 'product_category');
        if ($reloaded instanceof WP_Term) {
            $term = $reloaded;
        }
    }
    // The English slug under the language prefix (/zh/<slug>), as every other category translation has. Core and free
    // Polylang would suffix it, since a term in another language holds it, so it is written directly once no term in this
    // language holds it (the check inc/polylang-slugs.php uses for the same rule). Only where languages have their own
    // addresses: when the language comes from the content, a shared slug would give both categories one archive URL, so
    // the unique slug stays.
    if (!boblift_languages_have_own_urls()) {
        return true;
    }
    if ($term->slug !== $source->slug) {
        global $wpdb;
        // An existing category keeps answering at its old address (boblift_redirect_replaced_term_slug()).
        if (!$creating && $term->slug !== '') {
            add_term_meta($term->term_id, '_boblift_old_slug', $term->slug);
        }
        $wpdb->update($wpdb->terms, ['slug' => $source->slug], ['term_id' => $term->term_id]);
        clean_term_cache($term->term_id, 'product_category');
        boblift_flush_product_category_rewrites();
    }
    $fresh = get_term($term->term_id, 'product_category');
    if (!$fresh instanceof WP_Term || $fresh->slug !== $source->slug) {
        return new WP_Error('boblift_term_translation_slug', sprintf('Linked, but the slug stayed "%s" instead of "%s".', $fresh instanceof WP_Term ? $fresh->slug : '', $source->slug), ['status' => 500]);
    }
    return true;
}

/**
 * A category address with a slug that boblift_term_translation replaced (/zh/dian-dong-mini after the category took
 * electric-mini-cranes) answers with a 301 to the category's current address in that language.
 */
function boblift_redirect_replaced_term_slug(): void
{
    if (!is_404() || !function_exists('boblift_request_url_language')) {
        return;
    }
    $language = boblift_request_url_language();
    $segment = rawurlencode(rawurldecode((string) preg_replace('/\.html$/', '', basename((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH)))));
    if ($segment === '') {
        return;
    }
    $terms = get_terms(['taxonomy' => 'product_category', 'hide_empty' => false, 'lang' => '',
        'meta_query' => [['key' => '_boblift_old_slug', 'value' => strtolower($segment)]]]);
    // Only the category that had this address in the requested language.
    $terms = array_values(array_filter(is_array($terms) ? $terms : [], static fn($term): bool => $term instanceof WP_Term
        && (!function_exists('pll_get_term_language') || (string) pll_get_term_language($term->term_id) === $language)));
    if ($terms) {
        $link = get_term_link($terms[0]);
        if (!is_wp_error($link)) {
            $query = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);
            wp_safe_redirect($link . ($query !== '' ? (str_contains($link, '?') ? '&' : '?') . $query : ''), 301);
            exit;
        }
    }
}
add_action('template_redirect', 'boblift_redirect_replaced_term_slug', 1);
