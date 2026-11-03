<?php
// Keeps /<category>/ addresses on their category page when the rewrite rules miss them (see the function below).

/**
 * /marine-crane/ and /zh/marine-crane/ reach their category even when the rewrite rules lack the category's rule.
 *
 * The root rules come from boblift_product_category_root_rewrite_slugs() and exist only after a flush that saw every
 * category. Without its rule such an address fell through to the page rule or to nothing and showed the news list,
 * which the edge cache then kept. A one-segment path (after the language directory) that is a product category's slug
 * is routed to that category here, exactly as its rule would; anything else is left as WordPress parsed it.
 */
function boblift_product_category_root_request_fallback(array $vars): array
{
    // boblift_custom_url is not skipped: a category whose address is also a custom route arrives with it, and when that
    // route cannot be resolved the request would otherwise end as the news list.
    foreach (['boblift_product_category', 'boblift_html_slug', 'boblift_tonnage', 'feed', 'sitemap'] as $key) {
        if (!empty($vars[$key])) {
            return $vars;
        }
    }
    $path = (string) wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    $home_path = rtrim((string) wp_parse_url((string) get_option('home'), PHP_URL_PATH), '/');
    if ($home_path !== '' && str_starts_with($path, $home_path . '/')) {
        $path = substr($path, strlen($home_path));
    }
    $language = '';
    if (function_exists('boblift_prefixed_languages') && function_exists('boblift_language_path_base')) {
        $base = boblift_language_path_base();
        foreach (boblift_prefixed_languages() as $slug) {
            if (preg_match('#^/' . preg_quote($base . $slug, '#') . '(/|$)#', $path)) {
                $language = $slug;
                $path = substr($path, strlen($base . $slug) + 1) ?: '/';
                break;
            }
        }
    }
    $segment = trim(rawurldecode($path), '/');
    if ($segment === '' || str_contains($segment, '/') || str_contains($segment, '.')) {
        return $vars;
    }
    $slug = sanitize_title($segment);
    $terms = get_terms(['taxonomy' => 'product_category', 'slug' => $slug, 'hide_empty' => false, 'number' => 1, 'lang' => '']);
    if (is_wp_error($terms) || empty($terms) || !$terms[0] instanceof WP_Term) {
        return $vars;
    }
    $routed = ['boblift_product_category' => $terms[0]->slug];
    if ($language !== '') {
        $routed['lang'] = $language;
    }
    return $routed;
}
add_filter('request', 'boblift_product_category_root_request_fallback', 8);
