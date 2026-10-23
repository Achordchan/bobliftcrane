<?php
if (!defined('ABSPATH')) {
    exit;
}

// Search signals for the archive views WordPress leaves bare. A product category page is routed through the
// boblift_product_category query var, so neither core nor Polylang sees a term archive there: it had no canonical,
// no hreflang links, and its language switcher sent every language to that language's home page.

// The term a product category or news category page shows, or null on any other view.
function boblift_seo_archive_term(): ?WP_Term
{
    $product_category = function_exists('boblift_current_product_category_term') ? boblift_current_product_category_term() : null;
    if ($product_category instanceof WP_Term) {
        return $product_category;
    }
    if (is_category()) {
        $term = get_queried_object();
        return $term instanceof WP_Term ? $term : null;
    }
    return null;
}

function boblift_seo_archive_canonical(): void
{
    $term = boblift_seo_archive_term();
    if (!$term instanceof WP_Term) {
        return;
    }
    // Filters (?capacity=, ?news_page=) show the same category, so the canonical is always the bare category URL.
    $url = get_term_link($term);
    if (is_string($url) && $url !== '') {
        echo '<link rel="canonical" href="' . esc_url($url) . '" />' . "\n";
    }
}
add_action('wp_head', 'boblift_seo_archive_canonical', 1);

// Polylang builds both the language switcher and the hreflang links from this URL.
function boblift_seo_product_category_translation_url($url, string $language)
{
    $term = function_exists('boblift_current_product_category_term') ? boblift_current_product_category_term() : null;
    if (!$term instanceof WP_Term || !function_exists('pll_get_term')) {
        return $url;
    }
    $translated_id = (int) pll_get_term($term->term_id, $language);
    $translated = $translated_id > 0 ? get_term($translated_id, 'product_category') : null;
    if (!$translated instanceof WP_Term || is_wp_error($translated)) {
        return $url;
    }
    $link = get_term_link($translated);
    return is_string($link) && $link !== '' ? $link : $url;
}
add_filter('pll_translation_url', 'boblift_seo_product_category_translation_url', 10, 2);

// Tag pages only repeat post cards under a tag name; keep them out of the index and the sitemap.
function boblift_seo_noindex_tags(array $robots): array
{
    if (is_tag()) {
        $robots['noindex'] = true;
        $robots['follow'] = true;
    }
    return $robots;
}
add_filter('wp_robots', 'boblift_seo_noindex_tags');

function boblift_seo_sitemap_taxonomies(array $taxonomies): array
{
    unset($taxonomies['post_tag']);
    return $taxonomies;
}
add_filter('wp_sitemaps_taxonomies', 'boblift_seo_sitemap_taxonomies');

// MailPoet's subscription and captcha screens are utility pages, not content.
function boblift_seo_sitemap_post_types(array $post_types): array
{
    unset($post_types['mailpoet_page']);
    return $post_types;
}
add_filter('wp_sitemaps_post_types', 'boblift_seo_sitemap_post_types');

// The newsroom folded Product News into Company News and Marine Solution into Industry Insight
// (boblift_news_filter_category()); their own archive pages now 301 to the group they joined, in every language.
function boblift_seo_retired_news_categories(): array
{
    return ['product-news' => 'company-news', 'marine-solution' => 'industry-insight'];
}

function boblift_seo_retired_news_category_target(WP_Term $term): ?WP_Term
{
    $target_slug = boblift_seo_retired_news_categories()[boblift_source_term_slug($term)] ?? '';
    if ($target_slug === '') {
        return null;
    }
    $target = get_terms(['taxonomy' => 'category', 'slug' => $target_slug, 'hide_empty' => false, 'number' => 1, 'lang' => 'en']);
    $target = !is_wp_error($target) && !empty($target) && $target[0] instanceof WP_Term ? $target[0] : null;
    if (!$target instanceof WP_Term) {
        return null;
    }
    $language = function_exists('pll_get_term_language') ? (string) pll_get_term_language($term->term_id, 'slug') : '';
    $translated_id = $language !== '' && function_exists('pll_get_term') ? (int) pll_get_term($target->term_id, $language) : 0;
    $translated = $translated_id > 0 ? get_term($translated_id, 'category') : null;
    return $translated instanceof WP_Term && !is_wp_error($translated) ? $translated : $target;
}

function boblift_seo_redirect_retired_news_categories(): void
{
    if (!is_category()) {
        return;
    }
    $term = get_queried_object();
    $target = $term instanceof WP_Term ? boblift_seo_retired_news_category_target($term) : null;
    $url = $target instanceof WP_Term ? get_term_link($target) : '';
    if (is_string($url) && $url !== '') {
        wp_safe_redirect($url, 301);
        exit;
    }
}
add_action('template_redirect', 'boblift_seo_redirect_retired_news_categories', 1);

function boblift_seo_sitemap_retired_news_categories(array $args, string $taxonomy): array
{
    if ($taxonomy !== 'category') {
        return $args;
    }
    $retired = get_terms(['taxonomy' => 'category', 'slug' => array_keys(boblift_seo_retired_news_categories()), 'hide_empty' => false, 'lang' => 'en']);
    $exclude = [];
    foreach (is_wp_error($retired) ? [] : $retired as $term) {
        $translations = function_exists('pll_get_term_translations') ? pll_get_term_translations($term->term_id) : [];
        $exclude = array_merge($exclude, [(int) $term->term_id], array_map('intval', array_values((array) $translations)));
    }
    if ($exclude) {
        $args['exclude'] = array_values(array_unique(array_merge((array) ($args['exclude'] ?? []), $exclude)));
    }
    return $args;
}
add_filter('wp_sitemaps_taxonomies_query_args', 'boblift_seo_sitemap_retired_news_categories', 10, 2);
