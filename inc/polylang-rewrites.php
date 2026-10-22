<?php
/**
 * Language-prefixed copies of the theme's own rewrite rules.
 *
 * With Polylang's "language set from the directory name" mode, translated pages live under /ja/, /zh/ and so on. Polylang
 * prefixes the rules of the post types and taxonomies it knows, but not the theme's custom .html, category-root and
 * custom-address rules (boblift_html_slug, boblift_product_category, boblift_custom_url). This adds a prefixed twin of
 * each of them for every language whose URLs carry a directory prefix (the default language too when Polylang is set not to
 * hide it), so /ja/contact.html and /ja/service.html resolve the same
 * way /contact.html and /service.html do.
 */
/** Language slugs that appear as a URL directory: all of them, minus the default language when Polylang hides it. */
function boblift_prefixed_languages(): array
{
    if (!function_exists('pll_languages_list') || !function_exists('pll_default_language')) {
        return [];
    }
    $options = (array) get_option('polylang', []);
    // Only directory mode (force_lang 1) puts the language in the path; subdomain, domain and query modes add no prefix.
    if ((int) ($options['force_lang'] ?? 0) !== 1) {
        return [];
    }
    $hide_default = !array_key_exists('hide_default', $options) || !empty($options['hide_default']);
    $default = (string) pll_default_language();
    return array_values(array_filter((array) pll_languages_list(['fields' => 'slug']), static fn($slug): bool => !$hide_default || $slug !== $default));
}

/** "language/" when Polylang keeps the /language/ base in pretty permalinks (its "rewrite" option off), otherwise "". */
function boblift_language_path_base(): string
{
    $options = (array) get_option('polylang', []);
    return array_key_exists('rewrite', $options) && empty($options['rewrite']) ? 'language/' : '';
}

add_filter('rewrite_rules_array', static function (array $rules): array {
    $slugs = boblift_prefixed_languages();
    if (!$slugs) {
        return $rules;
    }
    $prefix = preg_quote(boblift_language_path_base(), '#') . '(' . implode('|', array_map('preg_quote', $slugs)) . ')/';
    $twins = [];
    foreach ($rules as $regex => $query) {
        if ($regex === '' || $regex[0] !== '^' || !str_contains($query, 'boblift_') || str_starts_with($regex, '^' . $prefix)) {
            continue;
        }
        $shifted = preg_replace_callback('/\$matches\[(\d+)\]/', static fn(array $m): string => '$matches[' . ((int) $m[1] + 1) . ']', $query);
        $twins['^' . $prefix . substr($regex, 1)] = $shifted . '&lang=$matches[1]';
    }
    // Customer feedback is language-neutral (not translated), but translated pages link to it as /ja/feedback/<slug>/.
    // Its standard CPT rules get a prefixed twin too, without a `lang` query var so the untranslated post still matches.
    foreach ($rules as $regex => $query) {
        if (!str_starts_with($regex, 'feedback/') || !str_contains($query, 'feedback=')) {
            continue;
        }
        $shifted = preg_replace_callback('/\$matches\[(\d+)\]/', static fn(array $m): string => '$matches[' . ((int) $m[1] + 1) . ']', $query);
        $twins['^' . $prefix . $regex] = $shifted;
    }
    return $twins + $rules;
}, 20);

/** Removes a leading /ja/, /zh/ ... from a request path when languages live in the URL directory. */
function boblift_strip_language_prefix(string $path): string
{
    $base = boblift_language_path_base();
    foreach (boblift_prefixed_languages() as $slug) {
        if (preg_match('#^/' . preg_quote($base . $slug, '#') . '(/|$)#', $path)) {
            return substr($path, strlen($base . $slug) + 1) ?: '/';
        }
    }
    return $path;
}

/**
 * Keeps WordPress's 404 guess in the language of the request.
 *
 * For an address that matches nothing, such as /contact/ (the contact page is a child of About, so its page path is
 * about/contact), WordPress redirects to the first published post whose slug matches, whatever its language: /contact/
 * went to /ja/contact.html and /why-choose-us/ to /ru/why-choose-us.html. This makes the same guess (exact slug first,
 * then slug prefix, as WordPress does), then sends the visitor to the translation in the language the address asked for
 * (its /xx/ directory, else the default language), falling back to the matched post itself when it is not translated.
 */
function boblift_guess_404_permalink_in_request_language(mixed $pre): mixed
{
    if ($pre !== null || !function_exists('pll_get_post') || !function_exists('pll_get_post_language') || !function_exists('pll_default_language')) {
        return $pre;
    }
    $name = (string) get_query_var('name');
    if ($name === '') {
        $name = wp_basename((string) get_query_var('pagename'));
    }
    $name = sanitize_title_for_query($name);
    if ($name === '') {
        return $pre;
    }

    global $wpdb;
    $types = array_values(array_filter(array_diff(get_post_types(['public' => true]), ['attachment']), 'is_post_type_viewable'));
    if (get_query_var('post_type')) {
        $types = array_values(array_intersect($types, (array) get_query_var('post_type')));
    }
    if (!$types) {
        return $pre;
    }
    // Same constraints as core: the post types asked for, and the year, month and day of a dated address.
    $where = $wpdb->prepare('post_type IN (' . implode(',', array_fill(0, count($types), '%s')) . ") AND post_status = 'publish'", ...$types);
    foreach (['year' => 'YEAR', 'monthnum' => 'MONTH', 'day' => 'DAYOFMONTH'] as $var => $sql) {
        if (get_query_var($var)) {
            $where .= $wpdb->prepare(" AND $sql(post_date) = %d", (int) get_query_var($var));
        }
    }
    $ids = $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND $where ORDER BY ID", $name));
    if (!$ids && !apply_filters('strict_redirect_guess_404_permalink', false)) {
        $ids = $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_name LIKE %s AND $where ORDER BY ID", $wpdb->esc_like($name) . '%'));
    }
    if (!$ids) {
        return $pre;
    }

    $language = boblift_request_url_language() ?: (string) pll_default_language();
    $default = (string) pll_default_language();
    $chosen = 0;
    foreach ($ids as $id) {
        if ((string) pll_get_post_language((int) $id, 'slug') === $language) {
            $chosen = (int) $id;
            break;
        }
    }
    if (!$chosen) {
        $first = (int) $ids[0];
        foreach ([(int) pll_get_post($first, $language), (int) pll_get_post($first, $default), $first] as $candidate) {
            if ($candidate > 0 && get_post_status($candidate) === 'publish') {
                $chosen = $candidate;
                break;
            }
        }
    }
    // Keep a comments feed or a later page of a multipage post, as core does.
    if (get_query_var('feed')) {
        $url = get_post_comments_feed_link($chosen, (string) get_query_var('feed'));
    } elseif ((int) get_query_var('page') > 1) {
        $url = trailingslashit((string) get_permalink($chosen)) . user_trailingslashit((string) (int) get_query_var('page'), 'single_paged');
    } else {
        $url = get_permalink($chosen);
    }
    return is_string($url) && $url !== '' ? $url : $pre;
}
add_filter('pre_redirect_guess_404_permalink', 'boblift_guess_404_permalink_in_request_language');

/**
 * Keeps a page found by its bare slug in the language of the request.
 *
 * Translated copies of the About child pages (contact, service, why-choose-us ...) have no parent, so /contact/ matches
 * whichever copy get_page_by_path() finds first and the canonical redirect then sent visitors to /ja/contact.html; the
 * English page, under About, made /ja/about/contact/ land on /contact.html. When the post found is in another language
 * than the address asks for and has a published translation in that language, redirect there instead.
 */
function boblift_canonical_in_request_language(string|false $redirect_url, string $requested_url): string|false
{
    if (!is_singular() || !function_exists('pll_get_post') || !function_exists('pll_get_post_language')) {
        return $redirect_url;
    }
    $post = get_queried_object();
    $language = boblift_request_url_language();
    if (!$post instanceof WP_Post || $language === '') {
        return $redirect_url;
    }
    $post_language = (string) pll_get_post_language($post->ID, 'slug');
    if ($post_language === '' || $post_language === $language) {
        return $redirect_url;
    }
    $translation = (int) pll_get_post($post->ID, $language);
    if ($translation <= 0 || get_post_status($translation) !== 'publish') {
        return $redirect_url;
    }
    $url = get_permalink($translation);
    if (!is_string($url) || $url === '') {
        return $redirect_url;
    }
    // Keep campaign parameters (gclid, utm_*), but not routing ones: ?page_id=569 or ?lang=ja would pick the other
    // language's page again on the corrected address (as in boblift_redirect_case_to_url_language()).
    $query = wp_unslash($_GET);
    $query = is_array($query) ? $query : [];
    foreach (['p', 'page_id', 'name', 'pagename', 'post_type', 'lang', 'preview', 'preview_id'] as $routing_key) {
        unset($query[$routing_key]);
    }
    if (untrailingslashit((string) parse_url($requested_url, PHP_URL_PATH)) === untrailingslashit((string) parse_url($url, PHP_URL_PATH)) && !array_diff_key(wp_unslash($_GET), $query)) {
        return $redirect_url;
    }
    $query_string = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    return $query_string !== '' ? $url . (str_contains($url, '?') ? '&' : '?') . $query_string : $url;
}
add_filter('redirect_canonical', 'boblift_canonical_in_request_language', 20, 2);
