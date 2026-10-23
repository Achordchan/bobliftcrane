<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Tonnage pages (/5-ton-crane.html, /zh/5-ton-crane.html ...): the old site's "Crane by Ton" addresses, now pages of their
 * own listing the current models of one capacity bin (boblift_tonnage_bins()), every category included. archive-product.php
 * renders them through the boblift_tonnage query var; this file routes them and gives them their title, description,
 * canonical, hreflang, breadcrumb, intro and sitemap entries. A bin without models has no page: its address 301s to the
 * product list, which keeps the old address's links and avoids an empty, thin page.
 */

/** The size in tonnes the current request shows (5 for /5-ton-crane.html), or 0. */
function boblift_current_tonnage(): int
{
    $size = (int) get_query_var('boblift_tonnage');
    return isset(boblift_tonnage_bins()[$size]) ? $size : 0;
}

function boblift_tonnage_url(int $size, ?string $language = null): string
{
    return boblift_public_path_url('/' . $size . '-ton-crane.html', $language);
}

/**
 * The capacity the product list filters and sorts by: a product rated by working height (aerial platforms: a "Working
 * Height" row and no "Max Lifting Capacity" row) counts as 0, so it stays out of the capacity filter and the tonnage pages.
 */
function boblift_product_listing_capacity(array $product): float
{
    $capacity = (float) ($product['filter_values']['capacity'] ?? 0);
    $spec_rows = (int) ($product['id'] ?? 0) > 0 ? (array) boblift_decode_meta(get_post_meta(boblift_product_english_id((int) $product['id']), '_specs', true), []) : [];
    $spec_labels = array_map(static fn($row): string => is_array($row) ? trim((string) ($row['label'] ?? '')) : '', $spec_rows);
    // A capacity stated with its unit (a "5 ton" capacity field) is a rated capacity whatever the spec table holds.
    $rated = function_exists('boblift_home_product_rated_capacity') ? boblift_home_product_rated_capacity($product) : 0.0;
    if ($rated <= 0 && in_array('Working Height', $spec_labels, true) && !in_array('Max Lifting Capacity', $spec_labels, true)) {
        return 0.0;
    }
    return $capacity;
}

/** [min, max] tonnes of a bin key ("4-5"): min excluded, max included, as in the capacity filter. */
function boblift_tonnage_bin_range(string $bin): array
{
    [$min, $max] = array_map('floatval', explode('-', $bin) + [0, 0]);
    return [$min, $max];
}

/** The current models of a tonnage page in the current language, every category included, in the product list's order. */
function boblift_tonnage_products(int $size): array
{
    static $cache = [];
    $language = function_exists('boblift_current_language_slug') ? boblift_current_language_slug() : '';
    if (isset($cache[$language][$size])) {
        return $cache[$language][$size];
    }
    $bin = boblift_tonnage_bins()[$size] ?? '';
    if ($bin === '') {
        return [];
    }
    [$min, $max] = boblift_tonnage_bin_range($bin);
    $products = array_values(array_filter(boblift_all_products(), static function (array $product) use ($min, $max): bool {
        $capacity = boblift_product_listing_capacity($product);
        return $capacity > $min && $capacity <= $max;
    }));
    return $cache[$language][$size] = $products;
}

/** "4.6", "9,000": a figure without trailing zeros. */
function boblift_tonnage_number(float $value, bool $thousands = false): string
{
    $text = number_format($value, $thousands ? 0 : 2, '.', $thousands ? ',' : '');
    return $thousands ? $text : rtrim(rtrim($text, '0'), '.');
}

/** "8.4–12 t·m", or one figure when every model has the same; '' when no model gives one. */
function boblift_tonnage_figure_range(array $products, string $key, string $unit): string
{
    $values = array_filter(array_map(static fn(array $product): float => (float) ($product['filter_values'][$key] ?? 0), $products), static fn(float $value): bool => $value > 0);
    if (!$values) {
        return '';
    }
    $thousands = $key === 'weight';
    $min = boblift_tonnage_number(min($values), $thousands);
    $max = boblift_tonnage_number(max($values), $thousands);
    return ($min === $max ? $min : $min . '–' . $max) . ' ' . $unit;
}

/**
 * Everything the page says about its bin, all from the products' own data: count, models, sub-categories, the root families
 * (truck-mounted-crane, marine-crane ... by their English slug), the rated range and the moment and weight ranges. Models
 * and categories follow the Products menu order, the larger models first within a category.
 */
function boblift_tonnage_page_data(int $size): array
{
    $t = static fn(string $text): string => boblift_localized_static_text($text);
    [$min, $max] = boblift_tonnage_bin_range(boblift_tonnage_bins()[$size] ?? '0-0');
    $parents = [];
    $positions = [];
    foreach (boblift_product_category_hierarchy() as $group) {
        $parent = (string) ($group['slug'] ?? '');
        $parents[$parent] = $parent;
        $positions[$parent] = count($positions);
        foreach ((array) ($group['children'] ?? []) as $child) {
            $parents[(string) ($child['slug'] ?? '')] = $parent;
            $positions[(string) ($child['slug'] ?? '')] = count($positions);
        }
    }
    $products = boblift_tonnage_products($size);
    usort($products, static fn(array $a, array $b): int => [$positions[(string) ($a['category'] ?? '')] ?? PHP_INT_MAX, -(float) ($a['filter_values']['capacity'] ?? 0), (string) ($a['model'] ?? '')]
        <=> [$positions[(string) ($b['category'] ?? '')] ?? PHP_INT_MAX, -(float) ($b['filter_values']['capacity'] ?? 0), (string) ($b['model'] ?? '')]);
    $families = [];
    $categories = [];
    foreach ($products as $product) {
        $slug = (string) ($product['category'] ?? '');
        $title = trim((string) ($product['category_title'] ?? ''));
        if ($title !== '') {
            $categories[$title] = $title;
        }
        $term = isset($parents[$slug]) ? get_term_by('slug', $parents[$slug], 'product_category') : null;
        if ($term instanceof WP_Term) {
            $families[boblift_source_term_slug($term)] = true;
        }
    }
    $range = $min > 0
        ? str_replace(['{min}', '{max}'], [boblift_tonnage_number($min), boblift_tonnage_number($max)], $t('over {min} t and up to {max} t'))
        : str_replace('{max}', boblift_tonnage_number($max), $t('up to {max} t'));
    return [
        'count' => count($products),
        'models' => array_values(array_unique(array_filter(array_map(static fn(array $product): string => trim((string) ($product['model'] ?? '')), $products)))),
        'categories' => array_values($categories),
        'families' => array_keys($families),
        'range' => $range,
        'moment' => boblift_tonnage_figure_range($products, 'moment', 't·m'),
        'weight' => boblift_tonnage_figure_range($products, 'weight', 'kg'),
    ];
}

/** The list separator of the current language: "、" in Chinese and Japanese, "، " in Arabic, else ", ". */
function boblift_tonnage_list(array $items): string
{
    $language = function_exists('boblift_current_language_slug') ? boblift_current_language_slug() : '';
    return implode(['zh' => '、', 'ja' => '、', 'ar' => '، '][$language] ?? ', ', $items);
}

function boblift_tonnage_heading(int $size): string
{
    return str_replace('{n}', (string) $size, boblift_localized_static_text('{n} Ton Cranes'));
}

/** "5 Ton Crane: Truck Mounted & Marine Models | BOB-LIFT", naming only the families the page really lists. */
function boblift_tonnage_title(int $size): string
{
    $data = boblift_tonnage_page_data($size);
    $truck = in_array('truck-mounted-crane', $data['families'], true);
    $marine = in_array('marine-crane', $data['families'], true);
    $template = $truck && $marine ? '{n} Ton Crane: Truck Mounted & Marine Models | BOB-LIFT'
        : ($truck ? '{n} Ton Crane: Truck Mounted Models | BOB-LIFT'
        : ($marine ? '{n} Ton Crane: Marine Models | BOB-LIFT' : '{n} Ton Crane: Models & Specifications | BOB-LIFT'));
    return str_replace('{n}', (string) $size, boblift_localized_static_text($template));
}

/** The meta description, its model list shortened until it fits 160 characters. */
function boblift_tonnage_description(int $size): string
{
    $data = boblift_tonnage_page_data($size);
    $template = boblift_localized_static_text($data['count'] === 1
        ? 'The BOB-LIFT {models} is rated {range}. Lifting moment, self weight and reach from the factory drawings. Request a quote.'
        : '{count} BOB-LIFT crane models rated {range}: {models}. Lifting moment, self weight and reach from the factory drawings. Request a quote.');
    $models = $data['models'];
    for ($shown = count($models); $shown >= 1; $shown--) {
        $list = boblift_tonnage_list(array_slice($models, 0, $shown)) . ($shown < count($models) ? '…' : '');
        $text = str_replace(['{count}', '{range}', '{models}', '….', '…。'], [(string) $data['count'], $data['range'], $list, '…', '…。'], $template);
        if (mb_strlen($text) <= 160) {
            return $text;
        }
    }
    return mb_strimwidth($text ?? '', 0, 160, '…');
}

/** The intro under the heading: how many models, in which categories, which ones, and their moment and weight ranges. */
function boblift_tonnage_intro(int $size): string
{
    $data = boblift_tonnage_page_data($size);
    $t = static fn(string $text): string => boblift_localized_static_text($text);
    $sentences = [str_replace(
        ['{count}', '{range}', '{categories}', '{models}'],
        [(string) $data['count'], $data['range'], boblift_tonnage_list($data['categories']), boblift_tonnage_list($data['models'])],
        $t($data['count'] === 1
            ? 'BOB-LIFT builds one current crane model rated {range}, in {categories}: {models}.'
            : 'BOB-LIFT builds {count} current crane models rated {range}, in {categories}: {models}.')
    )];
    if ($data['moment'] !== '') {
        $sentences[] = str_replace('{moment}', $data['moment'], $t('Lifting moment {moment}.'));
    }
    if ($data['weight'] !== '') {
        $sentences[] = str_replace('{weight}', $data['weight'], $t('Crane self weight {weight}.'));
    }
    $sentences[] = $t("Figures from each model's technical drawing.");
    $language = function_exists('boblift_current_language_slug') ? boblift_current_language_slug() : '';
    return implode(in_array($language, ['zh', 'ja'], true) ? '' : ' ', $sentences);
}

/** Home > Products > N Ton Cranes, as [label, url] pairs. */
function boblift_tonnage_breadcrumb(int $size): array
{
    $t = static fn(string $text): string => boblift_localized_static_text($text);
    return [
        [$t('Home'), boblift_public_path_url('/')],
        [$t('Products'), boblift_product_archive_url()],
        [boblift_tonnage_heading($size), boblift_tonnage_url($size)],
    ];
}

function boblift_tonnage_query_vars(array $vars): array
{
    $vars[] = 'boblift_tonnage';
    return $vars;
}
add_filter('query_vars', 'boblift_tonnage_query_vars');

// /5-ton-crane.html arrives as an .html slug (boblift_html_request_vars()); a tonnage size becomes the tonnage page, which
// keeps the language directory's `lang`. Runs first so no page, product or post of that slug is looked up.
function boblift_tonnage_request_vars(array $vars): array
{
    $size = boblift_tonnage_size_from_path((string) ($vars['boblift_html_slug'] ?? '') . '.html');
    if ($size <= 0) {
        return $vars;
    }
    return array_filter(['boblift_tonnage' => (string) $size, 'lang' => $vars['lang'] ?? null]);
}
add_filter('request', 'boblift_tonnage_request_vars', 9);

// A bin without current models has no page; an address in another case (/5-Ton-Crane.html) moves to the lower-case one.
function boblift_tonnage_template_redirect(): void
{
    $size = boblift_current_tonnage();
    if ($size <= 0) {
        return;
    }
    $query = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_QUERY);
    if (!boblift_tonnage_products($size)) {
        wp_safe_redirect(boblift_product_archive_url() . ($query !== '' ? '?' . $query : ''), 301);
        exit;
    }
    $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $canonical = boblift_tonnage_url($size);
    if (rawurldecode($path) !== (string) parse_url($canonical, PHP_URL_PATH)) {
        wp_safe_redirect($canonical . ($query !== '' ? '?' . $query : ''), 301);
        exit;
    }
}
add_action('template_redirect', 'boblift_tonnage_template_redirect', 1);

// The language query var makes WordPress see a language term archive and print its feed link; the page has no feed.
function boblift_tonnage_remove_feed_links(): void
{
    if (boblift_current_tonnage() > 0) {
        remove_action('wp_head', 'feed_links_extra', 3);
    }
}
add_action('template_redirect', 'boblift_tonnage_remove_feed_links');

function boblift_tonnage_template(string $template): string
{
    if (boblift_current_tonnage() <= 0) {
        return $template;
    }
    $archive_template = locate_template('archive-product.php');
    return $archive_template !== '' ? $archive_template : $template;
}
add_filter('template_include', 'boblift_tonnage_template');

// The same page in another language: Polylang builds the language switcher and the hreflang links from it (it would
// otherwise send every language to its home page, the request being no post or term).
function boblift_tonnage_translation_url($url, string $language)
{
    $size = boblift_current_tonnage();
    return $size > 0 ? boblift_tonnage_url($size, $language) : $url;
}
add_filter('pll_translation_url', 'boblift_tonnage_translation_url', 10, 2);

function boblift_tonnage_canonical(): void
{
    $size = boblift_current_tonnage();
    if ($size > 0) {
        echo '<link rel="canonical" href="' . esc_url(boblift_tonnage_url($size)) . '" />' . "\n";
    }
}
add_action('wp_head', 'boblift_tonnage_canonical', 1);

function boblift_tonnage_breadcrumb_schema(): void
{
    $size = boblift_current_tonnage();
    if ($size <= 0) {
        return;
    }
    $items = [];
    foreach (boblift_tonnage_breadcrumb($size) as $position => [$label, $url]) {
        $items[] = ['@type' => 'ListItem', 'position' => $position + 1, 'name' => $label, 'item' => $url];
    }
    echo '<script type="application/ld+json">' . wp_json_encode(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . "</script>\n";
}
add_action('wp_head', 'boblift_tonnage_breadcrumb_schema', 5);

/** Sitemap of the tonnage pages that have models, in the sitemap's language (Polylang serves one per language). */
function boblift_tonnage_register_sitemap(): void
{
    if (!class_exists('WP_Sitemaps_Provider')) {
        return;
    }
    wp_register_sitemap_provider('tonnage', new class extends WP_Sitemaps_Provider {
        public function __construct()
        {
            $this->name = 'tonnage';
            $this->object_type = 'tonnage';
        }

        public function get_url_list($page_num, $object_subtype = '')
        {
            if ((int) $page_num !== 1) {
                return [];
            }
            $urls = [];
            foreach (array_keys(boblift_tonnage_bins()) as $size) {
                if (boblift_tonnage_products($size)) {
                    $urls[] = ['loc' => boblift_tonnage_url($size)];
                }
            }
            return $urls;
        }

        public function get_max_num_pages($object_subtype = '')
        {
            return 1;
        }
    });
}
add_action('init', 'boblift_tonnage_register_sitemap', 20);
