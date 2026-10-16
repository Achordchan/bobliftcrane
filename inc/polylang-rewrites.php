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
    $hide_default = !array_key_exists('hide_default', $options) || !empty($options['hide_default']);
    $default = (string) pll_default_language();
    return array_values(array_filter((array) pll_languages_list(['fields' => 'slug']), static fn($slug): bool => !$hide_default || $slug !== $default));
}

add_filter('rewrite_rules_array', static function (array $rules): array {
    $slugs = boblift_prefixed_languages();
    if (!$slugs) {
        return $rules;
    }
    $prefix = '(' . implode('|', array_map('preg_quote', $slugs)) . ')/';
    $twins = [];
    foreach ($rules as $regex => $query) {
        if ($regex === '' || $regex[0] !== '^' || !str_contains($query, 'boblift_') || str_starts_with($regex, '^' . $prefix)) {
            continue;
        }
        $shifted = preg_replace_callback('/\$matches\[(\d+)\]/', static fn(array $m): string => '$matches[' . ((int) $m[1] + 1) . ']', $query);
        $twins['^' . $prefix . substr($regex, 1)] = $shifted . '&lang=$matches[1]';
    }
    return $twins + $rules;
}, 20);

/** Removes a leading /ja/, /zh/ ... from a request path when languages live in the URL directory. */
function boblift_strip_language_prefix(string $path): string
{
    foreach (boblift_prefixed_languages() as $slug) {
        if (preg_match('#^/' . preg_quote($slug, '#') . '(/|$)#', $path)) {
            return substr($path, strlen($slug) + 1) ?: '/';
        }
    }
    return $path;
}
