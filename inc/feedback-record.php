<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Delivery records for customer feedback entries. An entry used to be a gallery with a model and a country; the page
 * around it is the same template on all 101 entries, so search engines saw near-duplicates. A delivery record is the
 * written part that is the entry's own: what the photos show, where the crane went and what the model is built for.
 *
 * The record is stored per language in _feedback_record ({"en": "<h2>…", "es": "…"}, limited HTML) with its search
 * title and description in _feedback_seo ({"en": {"title": "…", "description": "…"}}). Both are post meta of
 * wp/v2/feedback, so they are written through the REST API or the WordPress.com connector; the edit screen does not
 * touch them. A language without its own record keeps the gallery page and stays out of the index and the sitemap.
 */

/** Value of a per-language JSON meta for one language, without falling back to English. */
function boblift_feedback_record_value(int $post_id, string $key, string $language): mixed
{
    $values = json_decode((string) get_post_meta($post_id, $key, true), true);
    return is_array($values) ? ($values[$language] ?? null) : null;
}

/** The delivery record of an entry in a language ('' when that language has none). */
function boblift_feedback_record(int $post_id, string $language = ''): string
{
    $language = $language !== '' ? $language : boblift_current_language_slug();
    $language = $language !== '' ? $language : 'en';
    return trim((string) boblift_feedback_record_value($post_id, '_feedback_record', $language));
}

/** Search title or description of an entry in the current language ('' when not set). */
function boblift_feedback_seo_text(int $post_id, string $field): string
{
    $language = boblift_current_language_slug() ?: 'en';
    $seo = boblift_feedback_record_value($post_id, '_feedback_seo', $language);
    return is_array($seo) ? trim((string) ($seo[$field] ?? '')) : '';
}

function boblift_feedback_rest_language_map(mixed $value, callable $clean): string
{
    $decoded = is_string($value) ? json_decode($value, true) : $value;
    if (!is_array($decoded) || array_is_list($decoded) && $decoded !== []) {
        return '';
    }
    $out = [];
    foreach ($decoded as $language => $item) {
        $language = sanitize_key((string) $language);
        $item = $clean($item);
        if ($language !== '' && $item !== null && $item !== '' && $item !== []) {
            $out[$language] = $item;
        }
    }
    return $out ? (string) wp_json_encode($out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
}

function boblift_register_feedback_record_meta(): void
{
    $fields = [
        '_feedback_record' => static fn($value): string => boblift_feedback_rest_language_map($value, static fn($html): string => trim(wp_kses_post((string) $html))),
        '_feedback_seo' => static fn($value): string => boblift_feedback_rest_language_map($value, static function ($seo): ?array {
            if (!is_array($seo)) {
                return null;
            }
            return array_filter([
                'title' => sanitize_text_field((string) ($seo['title'] ?? '')),
                'description' => sanitize_text_field((string) ($seo['description'] ?? '')),
            ]);
        }),
    ];
    foreach ($fields as $key => $sanitize) {
        register_post_meta('feedback', $key, [
            'type' => 'string',
            'single' => true,
            'default' => '',
            'show_in_rest' => true,
            'sanitize_callback' => $sanitize,
            'auth_callback' => static fn($allowed, $meta_key, $post_id): bool => current_user_can('edit_post', (int) $post_id),
        ]);
    }
    // Model and country are read alongside the record, so a writer knows which entry it is working on.
    foreach (['_feedback_model', '_feedback_country'] as $key) {
        register_post_meta('feedback', $key, [
            'type' => 'string',
            'single' => true,
            'default' => '',
            'show_in_rest' => ['schema' => ['readonly' => true]],
            'auth_callback' => '__return_false',
        ]);
    }
    add_post_type_support('feedback', 'custom-fields');
}
add_action('init', 'boblift_register_feedback_record_meta', 20);

// Gallery-only entries share almost all of their text with the other 100; keep them out of the index until written up.
function boblift_feedback_record_robots(array $robots): array
{
    if (is_singular('feedback') && boblift_feedback_record(get_queried_object_id()) === '') {
        $robots['noindex'] = true;
        $robots['follow'] = true;
    }
    return $robots;
}
add_filter('wp_robots', 'boblift_feedback_record_robots', 20);

// The feedback sitemap lists the English URLs, so it keeps only the entries written up in English.
function boblift_feedback_record_sitemap_args(array $args, string $post_type): array
{
    if ($post_type !== 'feedback') {
        return $args;
    }
    $ids = get_posts(['post_type' => 'feedback', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_feedback_record', 'lang' => '']);
    $ids = array_values(array_filter(array_map('intval', $ids), static fn(int $id): bool => boblift_feedback_record($id, 'en') !== ''));
    $args['post__in'] = $ids ?: [0];
    return $args;
}
add_filter('wp_sitemaps_posts_query_args', 'boblift_feedback_record_sitemap_args', 10, 2);
