<?php
/**
 * Lets a translation keep the same slug as the page it translates (/ja/foo.html next to /foo.html).
 *
 * Free Polylang makes WordPress add -2, -3 to a slug that another language already uses. With languages in the URL
 * directory (/ja/, /zh/) the slug only has to be unique inside its own language, so when every post that holds the slug
 * is in a different language than the post being saved, the original slug is kept. Works on posts, pages and custom
 * post types.
 */
add_filter('wp_unique_post_slug', static function (string $slug, int $post_id, string $post_status, string $post_type, int $post_parent, string $original_slug): string {
    if ($slug === $original_slug || $post_type === 'attachment' || !function_exists('pll_get_post_language') || !function_exists('pll_languages_list')) {
        return $slug;
    }
    $language = pll_get_post_language($post_id);
    if (!$language) {
        return $slug;
    }
    global $wpdb;
    $holders = $wpdb->get_col($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = %s AND post_parent = %d AND ID <> %d AND post_status NOT IN ('trash', 'auto-draft')",
        $original_slug, $post_type, $post_parent, $post_id
    ));
    // No same-type holder means WordPress changed the slug for another reason (reserved word, feed or date clash): keep its choice.
    if (!$holders) {
        return $slug;
    }
    foreach ($holders as $holder) {
        if (pll_get_post_language((int) $holder) === $language) {
            return $slug;
        }
    }
    return $original_slug;
}, 10, 6);
