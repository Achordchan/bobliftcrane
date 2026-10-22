<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST: the boblift_translation field on wp/v2 posts, cases, products and pages, so translations can be read and written
 * through the standard REST routes (the WordPress.com connector only reaches wp/v2, and Polylang's free version adds no
 * language to them).
 *
 * Read: {"lang": "ru", "translations": {"en": 123, "ru": 456}}.
 * Write (posts and cases): {"source": 123, "lang": "ru", "meta": {"_seo_title": "..."}} files the item as the "ru"
 * translation of post 123, gives it the source's slug (a case adds "-ru": case routes do not pick by language), its
 * featured image, its categories in that language and, for a case, its photos, product and kind; "meta" sets the text
 * fields listed in boblift_rest_translation_meta_keys(). The item's own title, content and excerpt are sent as usual.
 */
function boblift_rest_translation_meta_keys(string $post_type): array
{
    return [
        'case' => ['_type', '_summary', '_country', '_application', '_facts', '_sections', '_seo_title', '_seo_description', '_article_schema_jsonld'],
        'post' => ['_seo_title', '_seo_description', '_seo_keywords', '_article_schema_jsonld'],
    ][$post_type] ?? [];
}

function boblift_register_rest_translation_field(): void
{
    register_rest_field(['post', 'case', 'product', 'page'], 'boblift_translation', [
        'get_callback' => static function (array $post): array {
            if (!function_exists('pll_get_post_language') || !function_exists('pll_get_post_translations')) {
                return ['lang' => '', 'translations' => new stdClass()];
            }
            $translations = array_map('intval', (array) pll_get_post_translations((int) $post['id']));
            return ['lang' => (string) pll_get_post_language((int) $post['id']), 'translations' => $translations ?: new stdClass()];
        },
        'update_callback' => static function ($value, WP_Post $post) {
            return boblift_rest_save_translation($post, is_array($value) ? $value : []);
        },
        'schema' => [
            'description' => 'Polylang language and translations; on posts and cases, send source, lang and meta to file this item as a translation.',
            'type' => 'object',
            'context' => ['view', 'edit'],
            'properties' => [
                'lang' => ['type' => 'string'],
                'source' => ['type' => 'integer'],
                'translations' => ['type' => 'object'],
                'meta' => ['type' => 'object'],
            ],
        ],
    ]);
}
add_action('rest_api_init', 'boblift_register_rest_translation_field');

function boblift_rest_save_translation(WP_Post $post, array $value): true|WP_Error
{
    if (!current_user_can('edit_post', $post->ID)) {
        return new WP_Error('rest_cannot_edit', 'Sorry, you are not allowed to edit this item.', ['status' => rest_authorization_required_code()]);
    }
    if (!in_array($post->post_type, ['post', 'case'], true)) {
        return new WP_Error('boblift_translation_type', 'Translations can be written for posts and cases only.', ['status' => 400]);
    }
    if (!function_exists('pll_set_post_language') || !function_exists('pll_save_post_translations') || !function_exists('pll_languages_list')) {
        return new WP_Error('boblift_translation_polylang', 'Polylang is not active.', ['status' => 500]);
    }
    $lang = sanitize_key((string) ($value['lang'] ?? ''));
    $source = get_post((int) ($value['source'] ?? 0));
    $source_lang = $source instanceof WP_Post ? (string) pll_get_post_language($source->ID) : '';
    if (!in_array($lang, array_map('strval', (array) pll_languages_list(['fields' => 'slug'])), true)) {
        return new WP_Error('boblift_translation_lang', 'Unknown language.', ['status' => 400]);
    }
    if (!$source instanceof WP_Post || $source->post_type !== $post->post_type || $source->ID === $post->ID || $source_lang === '' || $source_lang === $lang) {
        return new WP_Error('boblift_translation_source', 'The source must be another ' . $post->post_type . ' in a different language.', ['status' => 400]);
    }
    $existing = (int) pll_get_post($source->ID, $lang);
    if ($existing > 0 && $existing !== $post->ID) {
        return new WP_Error('boblift_translation_exists', 'The source already has a ' . $lang . ' translation (ID ' . $existing . ').', ['status' => 409]);
    }
    $meta = is_array($value['meta'] ?? null) ? $value['meta'] : [];
    $allowed = boblift_rest_translation_meta_keys($post->post_type);
    $unknown = array_diff(array_keys($meta), $allowed);
    if ($unknown) {
        return new WP_Error('boblift_translation_meta', 'Unknown meta: ' . implode(', ', $unknown) . '. Allowed: ' . implode(', ', $allowed) . '.', ['status' => 400]);
    }

    pll_set_post_language($post->ID, $lang);
    $translations = (array) pll_get_post_translations($source->ID);
    $translations[$source_lang] = $source->ID;
    $translations[$lang] = $post->ID;
    pll_save_post_translations($translations);

    foreach ($meta as $key => $text) {
        update_post_meta($post->ID, $key, wp_slash(is_string($text) ? $text : (string) wp_json_encode($text, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
    }
    // Shared with the source: the cover, the post's categories in this language, and a case's photos, product and kind.
    $thumbnail = (int) get_post_thumbnail_id($source->ID);
    if ($thumbnail > 0 && !has_post_thumbnail($post->ID)) {
        set_post_thumbnail($post->ID, $thumbnail);
    }
    if ($post->post_type === 'post') {
        $categories = array_values(array_filter(array_map(static fn(int $id): int => (int) pll_get_term($id, $lang), wp_get_post_categories($source->ID))));
        if ($categories) {
            wp_set_post_categories($post->ID, $categories);
        }
    }
    if ($post->post_type === 'case') {
        foreach (['_case_kind', '_product_name', '_case_gallery', '_related_product'] as $key) {
            if ((string) get_post_meta($post->ID, $key, true) === '') {
                update_post_meta($post->ID, $key, wp_slash((string) get_post_meta($source->ID, $key, true)));
            }
        }
    }
    // A post keeps the English slug under its language prefix (inc/polylang-slugs.php); a case gets its own.
    $slug = $post->post_type === 'case' ? $source->post_name . '-' . $lang : $source->post_name;
    if ($post->post_name !== $slug) {
        wp_update_post(['ID' => $post->ID, 'post_name' => $slug]);
    }
    return true;
}
