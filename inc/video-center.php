<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Video Center, beyond the theme's own library (inc/video-library.php):
 *
 * 1. Films written into product bodies. Every film a published product of the page's language plays in its video column
 *    (boblift_split_body_videos(): a Video block from the media library) is listed too, so a film put on a product page
 *    shows up here without a code change. One card per film URL, titled with its caption (or the product name), filed
 *    under truck or marine cranes by the product's family and under "Other cranes" otherwise. Newest product first.
 *    The list is cached per language for an hour and dropped whenever a product's cache is cleaned (every save,
 *    boblift_content_insert and the other product REST writes).
 *
 * 2. Extra films for this language's Video Center page, for footage that sits on no product page (a news film, a
 *    shipping clip): the boblift_video_center_extra field on wp/v2/pages, stored on the page itself.
 *    Write {"items": [{"src": "https://...mp4", "poster": "https://...jpg", "title": "...", "group": "test"}, ...]}
 *    (at most 40; group is one of truck, marine, product, test, factory, service) to replace the list, {"items": []}
 *    to clear it. Read (context edit) returns {"items": [...]}.
 */

function boblift_video_center_groups(): array
{
    return ['truck', 'marine', 'product', 'test', 'factory', 'service'];
}

function boblift_video_center_cache_key(string $language): string
{
    return 'boblift_vc_products_' . (int) get_option('boblift_video_center_rev', 0) . '_' . sanitize_key($language);
}

function boblift_video_center_product_videos(): array
{
    $language = function_exists('boblift_current_language_slug') ? (string) boblift_current_language_slug() : 'en';
    $key = boblift_video_center_cache_key($language);
    $cached = get_transient($key);
    if (is_array($cached)) {
        return $cached;
    }
    $args = [
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => ['date' => 'DESC', 'ID' => 'DESC'],
        'no_found_rows' => true,
    ];
    if (function_exists('pll_current_language')) {
        $args['lang'] = $language;
    }
    $videos = [];
    $seen = [];
    foreach (get_posts($args) as $post) {
        if (stripos((string) $post->post_content, 'wp-block-video') === false || post_password_required($post)) {
            continue;
        }
        // The product page shows its body (and so its films) only once the body is switched on.
        if (function_exists('boblift_product_content_ready') && !boblift_product_content_ready((int) $post->ID, '_detail_body_ready')) {
            continue;
        }
        $english_id = function_exists('boblift_product_english_id') ? boblift_product_english_id((int) $post->ID) : (int) $post->ID;
        $use = boblift_product_video_use($english_id);
        [, $films] = boblift_split_body_videos((string) $post->post_content, get_the_title($post));
        foreach ($films as $film) {
            $src = (string) $film['src'];
            if ($src === '' || isset($seen[$src])) {
                continue;
            }
            $seen[$src] = true;
            $videos[] = array_replace($film, [
                'key' => 'product-' . substr(md5($src), 0, 10),
                'group' => $use !== '' ? $use : 'product',
                'width' => 1920,
                'height' => 1080,
            ]);
        }
    }
    set_transient($key, $videos, HOUR_IN_SECONDS);
    return $videos;
}

function boblift_video_center_flush_products(int $post_id): void
{
    if (get_post_type($post_id) === 'product') {
        update_option('boblift_video_center_rev', (int) get_option('boblift_video_center_rev', 0) + 1, false);
    }
}
add_action('clean_post_cache', 'boblift_video_center_flush_products');

function boblift_video_center_clean_extra(mixed $items): array|WP_Error
{
    $invalid = new WP_Error('boblift_video_center_invalid', 'Send {"items": [{"src": "https://...mp4", "poster": "https://...jpg", "title": "...", "group": "' . implode('|', boblift_video_center_groups()) . '"}, ...]} with at most 40 items.', ['status' => 400]);
    if (!is_array($items) || count($items) > 40 || !array_is_list($items)) {
        return $invalid;
    }
    $clean = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            return $invalid;
        }
        $src = is_string($item['src'] ?? null) ? trim($item['src']) : '';
        $poster = is_string($item['poster'] ?? null) ? trim($item['poster']) : '';
        $title = is_string($item['title'] ?? null) ? trim($item['title']) : '';
        $group = is_string($item['group'] ?? null) ? $item['group'] : '';
        if (!preg_match('#^https://\S+\.(mp4|webm)(\?\S*)?$#i', $src) || !preg_match('#^https://\S+$#', $poster) || esc_url_raw($src) !== $src || esc_url_raw($poster) !== $poster
            || $title === '' || mb_strlen($title) > 160 || sanitize_text_field($title) !== $title || !in_array($group, boblift_video_center_groups(), true)) {
            return $invalid;
        }
        $clean[] = ['src' => $src, 'poster' => $poster, 'title' => $title, 'group' => $group];
    }
    return $clean;
}

// The extra films saved on a Video Center page, as player entries.
function boblift_video_center_extra_videos(int $page_id): array
{
    $saved = get_post_meta($page_id, '_boblift_video_center_extra', true);
    $videos = [];
    foreach (is_array($saved) ? $saved : [] as $index => $item) {
        if (!is_array($item) || empty($item['src'])) {
            continue;
        }
        $videos[] = [
            'key' => 'extra-' . $index,
            'group' => (string) ($item['group'] ?? 'factory'),
            'title' => (string) ($item['title'] ?? ''),
            'label' => (string) ($item['title'] ?? ''),
            'player' => 'local',
            'youtube' => '',
            'src' => (string) $item['src'],
            'src_mobile' => '',
            'poster_src' => (string) ($item['poster'] ?? ''),
            'duration' => 0,
            'width' => 1920,
            'height' => 1080,
        ];
    }
    return $videos;
}

function boblift_video_center_register_page_field(): void
{
    register_rest_field('page', 'boblift_video_center_extra', [
        'get_callback' => static function (array $post) {
            if (!current_user_can('manage_options')) {
                return null;
            }
            $saved = get_post_meta((int) $post['id'], '_boblift_video_center_extra', true);
            return ['items' => is_array($saved) ? array_values($saved) : []];
        },
        'update_callback' => static function ($value, WP_Post $post) {
            if (!current_user_can('manage_options')) {
                return new WP_Error('boblift_video_center_forbidden', 'Changing the Video Center list needs manage_options.', ['status' => 403]);
            }
            if (!is_array($value) || !array_key_exists('items', $value)) {
                return new WP_Error('boblift_video_center_invalid', 'Send {"items": [...]}.', ['status' => 400]);
            }
            $items = boblift_video_center_clean_extra($value['items']);
            if (is_wp_error($items)) {
                return $items;
            }
            if ($items === []) {
                delete_post_meta($post->ID, '_boblift_video_center_extra');
            } else {
                update_post_meta($post->ID, '_boblift_video_center_extra', $items);
            }
            clean_post_cache($post->ID);
            return true;
        },
        'schema' => [
            'description' => 'Extra films on this Video Center page (footage that is on no product page). Write {"items": [{"src", "poster", "title", "group"}]}; group: ' . implode(', ', boblift_video_center_groups()) . '.',
            'type' => ['object', 'null'],
            'context' => ['edit'],
        ],
    ]);
}
add_action('rest_api_init', 'boblift_video_center_register_page_field');
