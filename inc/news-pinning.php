<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pinned news: a post marked sticky (WordPress's own "Stick to the top of the blog" box, or "sticky": true on
 * wp/v2/posts) leads the newsroom and the home page's news cards, newest pinned first. Each language's translation is
 * its own post, so each one is pinned on its own.
 *
 * A pin can end by itself: _boblift_pinned_until (Y-m-d, on wp/v2/posts as meta) is the last day the post stays on
 * top, in the site's time zone; after it the post falls back to its date order while still marked sticky. Left empty,
 * the pin holds until it is taken off.
 */
function boblift_register_news_pinning_meta(): void
{
    register_post_meta('post', '_boblift_pinned_until', [
        'type' => 'string',
        'single' => true,
        'default' => '',
        'show_in_rest' => true,
        'sanitize_callback' => static function ($value): string {
            $value = trim((string) $value);
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
            return $date && $date->format('Y-m-d') === $value ? $value : '';
        },
        'auth_callback' => static fn($allowed, $meta_key, $post_id): bool => current_user_can('edit_post', (int) $post_id),
    ]);
}
add_action('init', 'boblift_register_news_pinning_meta');

/** Ids of the published posts pinned today in the current language, newest first. */
function boblift_pinned_post_ids(string $category_slug = ''): array
{
    $sticky = array_values(array_filter(array_map('intval', (array) get_option('sticky_posts', []))));
    if ($sticky === []) {
        return [];
    }
    $args = [
        'post_type' => 'post',
        'post_status' => 'publish',
        'post__in' => $sticky,
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
        'ignore_sticky_posts' => true,
        'fields' => 'ids',
        'no_found_rows' => true,
    ];
    if ($category_slug !== '') {
        $args['category_name'] = $category_slug;
    }
    $today = wp_date('Y-m-d');
    // Polylang may leave a post__in query unfiltered, so the language is checked here too.
    $language = function_exists('pll_current_language') ? (string) pll_current_language() : '';
    return array_values(array_filter(array_map('intval', (new WP_Query($args))->posts), static function (int $id) use ($today, $language): bool {
        if ($language !== '' && function_exists('pll_get_post_language') && (string) pll_get_post_language($id) !== $language) {
            return false;
        }
        $until = (string) get_post_meta($id, '_boblift_pinned_until', true);
        return $until === '' || $until >= $today;
    }));
}
