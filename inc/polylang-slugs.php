<?php
/**
 * Lets a translation keep the same slug as the page it translates (/ja/foo.html next to /foo.html).
 *
 * Free Polylang makes WordPress add -2, -3 to a slug that another language already uses. With languages in the URL
 * directory (/ja/, /zh/) the slug only has to be unique inside its own language, so when every post that holds the slug
 * is in a different language than the post being saved, the original slug is kept. Works on posts, pages and custom
 * post types.
 */
/**
 * The "lang" a REST request carries, wherever it was sent (query string, form or JSON body); null outside REST.
 * $_REQUEST misses a JSON body, so the parsed request is remembered before the post is saved.
 */
function boblift_rest_incoming_language(?WP_REST_Request $request = null, bool $store = false): ?string
{
    static $language = null;
    if ($store) {
        $value = $request ? $request->get_param('lang') : null;
        $language = is_string($value) ? $value : null;
    }
    return $language;
}

add_filter('rest_request_before_callbacks', static function ($response, $handler, WP_REST_Request $request) {
    boblift_rest_incoming_language($request, true);
    return $response;
}, 10, 3);

add_filter('wp_unique_post_slug', static function (string $slug, int $post_id, string $post_status, string $post_type, int $post_parent, string $original_slug): string {
    if ($slug === $original_slug || $post_type === 'attachment' || !function_exists('pll_get_post_language') || !function_exists('pll_languages_list')) {
        return $slug;
    }
    // Languages only have separate addresses in directory, subdomain or domain mode (force_lang 1-3); when the language comes
    // from the content (0), a shared slug would give two posts one URL.
    $force_lang = (int) (((array) get_option('polylang', []))['force_lang'] ?? 0);
    if ($force_lang < 1 || $force_lang > 3) {
        return $slug;
    }
    $language = pll_get_post_language($post_id);
    if (!$language) {
        return $slug;
    }
    // While a save changes the post's language (classic editor field or REST "lang"), the stored language is stale, so the
    // shared slug is only allowed when the incoming language is the stored one.
    foreach ([$_POST['post_lang_choice'] ?? null, $_REQUEST['lang'] ?? null, boblift_rest_incoming_language()] as $incoming) {
        if (is_string($incoming) && !in_array($incoming, ['', 'all'], true) && sanitize_key($incoming) !== $language) {
            return $slug;
        }
    }
    global $wpdb;
    // WordPress keeps hierarchical slugs unique per parent and other slugs unique across the whole post type.
    $holders = is_post_type_hierarchical($post_type)
        ? $wpdb->get_col($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = %s AND post_parent = %d AND ID <> %d AND post_status NOT IN ('trash', 'auto-draft')",
            $original_slug, $post_type, $post_parent, $post_id
        ))
        : $wpdb->get_col($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = %s AND ID <> %d AND post_status NOT IN ('trash', 'auto-draft')",
            $original_slug, $post_type, $post_id
        ));
    // An attachment holding the slug keeps WordPress's suffix, so attachment and post URLs never coincide.
    $attachment = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'attachment' AND ID <> %d LIMIT 1",
        $original_slug, $post_id
    ));
    if ($attachment) {
        return $slug;
    }
    // No same-type holder means WordPress changed the slug for another reason (reserved word, feed or date clash): keep its choice.
    if (!$holders) {
        return $slug;
    }
    foreach ($holders as $holder) {
        // A holder with no language (or the same one) shares this namespace, so WordPress's suffix stays.
        $holder_language = pll_get_post_language((int) $holder);
        if (!$holder_language || $holder_language === $language) {
            return $slug;
        }
    }
    return $original_slug;
}, 10, 6);
