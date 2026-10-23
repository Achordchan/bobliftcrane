<?php
if (!defined('ABSPATH')) {
    exit;
}

function boblift_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('custom-logo');
    register_nav_menus([
        'primary' => __('Primary Navigation', 'boblift'),
        'footer' => __('Footer Navigation', 'boblift'),
    ]);
}
add_action('after_setup_theme', 'boblift_setup');


function boblift_sanitize_upload_filename(string $filename, string $filename_raw = ''): string
{
    $source = $filename_raw !== '' ? $filename_raw : $filename;
    if (!preg_match('/[^\x20-\x7E]/', $source) && !preg_match('/-{2,}/', $filename)) {
        return $filename;
    }

    $extension = strtolower((string) pathinfo($source, PATHINFO_EXTENSION));
    if ($extension === '') {
        $extension = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
    }
    $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?: 'file';

    $date = function_exists('current_time') ? current_time('Ymd-His') : gmdate('Ymd-His');
    $hash = substr(md5($source), 0, 10);

    return sprintf('boblift-%s-%s.%s', $date, $hash, $extension);
}
add_filter('sanitize_file_name', 'boblift_sanitize_upload_filename', 20, 2);

/** Whether $min was built from $source: scripts/build-js.mjs writes the source's SHA-1 on its first line. */
function boblift_minified_script_is_current(string $source, string $min): bool
{
    if (!is_readable($source) || !is_readable($min)) {
        return false;
    }
    $handle = fopen($min, 'rb');
    $first_line = $handle ? (string) fgets($handle, 200) : '';
    if ($handle) {
        fclose($handle);
    }
    return preg_match('/sha1:([0-9a-f]{40})/', $first_line, $match) === 1 && hash_equals($match[1], (string) sha1_file($source));
}

function boblift_assets(): void
{
    $css = get_template_directory() . '/assets/css/theme.css';
    $js = get_template_directory() . '/assets/js/theme.js';
    $deps = [];
    $gsap = get_template_directory() . '/assets/js/vendor/gsap.min.js';
    $gsap_uri = get_template_directory_uri() . '/assets/js/vendor/gsap.min.js';
    $gsap_version = is_readable($gsap) ? (string) filemtime($gsap) : '3.15.0';

    $queried = is_page() ? get_queried_object() : null;
    $is_about_page = $queried instanceof WP_Post && function_exists('boblift_page_is_translation_of_slug') && boblift_page_is_translation_of_slug($queried, 'about');
    if ($is_about_page || is_page_template('page-about.php')) {
        wp_enqueue_script('boblift-gsap', $gsap_uri, [], $gsap_version, true);
        $scroll_trigger = get_template_directory() . '/assets/js/vendor/ScrollTrigger.min.js';
        wp_enqueue_script('boblift-scrolltrigger', get_template_directory_uri() . '/assets/js/vendor/ScrollTrigger.min.js', ['boblift-gsap'], is_readable($scroll_trigger) ? (string) filemtime($scroll_trigger) : '3.15.0', true);
        $deps[] = 'boblift-scrolltrigger';
    }

    wp_enqueue_style('boblift-theme', get_template_directory_uri() . '/assets/css/theme.css', [], is_readable($css) ? (string) filemtime($css) : '1.0.0');
    // The minified build (npm run build:js) is served only while it was built from the current theme.js.
    $min = get_template_directory() . '/assets/js/theme.min.js';
    $js_file = boblift_minified_script_is_current($js, $min) ? 'theme.min.js' : 'theme.js';
    $js_path = get_template_directory() . '/assets/js/' . $js_file;
    wp_enqueue_script('boblift-theme', get_template_directory_uri() . '/assets/js/' . $js_file, $deps, is_readable($js_path) ? (string) filemtime($js_path) : '1.0.0', true);
    $quote_form_ui = function_exists('boblift_site_ui') ? boblift_site_ui('quoteForm') : [];
    $quote_list_ui = function_exists('boblift_site_ui') ? boblift_site_ui('quoteList') : [];
    $header_ui = function_exists('boblift_site_ui') ? boblift_site_ui('header') : [];
    $product_archive_ui = function_exists('boblift_site_ui') ? boblift_site_ui('productArchive') : [];
    wp_add_inline_script('boblift-theme', 'window.bobliftI18n = ' . wp_json_encode([
        'quoteForm' => $quote_form_ui,
        'quoteList' => $quote_list_ui,
        'header' => $header_ui,
        'productArchive' => $product_archive_ui,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';', 'before');
}
add_action('wp_enqueue_scripts', 'boblift_assets');

function boblift_font_preload(): void
{
    foreach (['barlow-400-latin.woff2', 'barlow-600-latin.woff2'] as $file) {
        echo '<link rel="preload" href="' . esc_url(get_template_directory_uri() . '/assets/fonts/barlow/' . $file) . '" as="font" type="font/woff2" crossorigin>' . "\n";
    }
}
add_action('wp_head', 'boblift_font_preload', 2);



function boblift_localize_seo_text(string $text): string
{
    // English search text is written as it should appear; the phrase map would turn "Ship Cranes" into "Marine Crane".
    if ($text === '' || (function_exists('boblift_current_language_slug') && boblift_current_language_slug() === 'en')) {
        return $text;
    }
    return function_exists('boblift_localize_product_text_fragments') ? (string) boblift_localize_product_text_fragments($text) : $text;
}

function boblift_seo_default_title_for_post(WP_Post $post): string
{
    if ($post->post_type === 'page') {
        if ((int) get_option('page_on_front') === (int) $post->ID || $post->post_name === 'home') {
            return boblift_localize_seo_text('BOB-LIFT | Truck Mounted Cranes and Lifting Solutions');
        }
        $title = match ($post->post_name) {
            'products' => 'Cranes & Lifting Equipment | BOB-LIFT',
            'cases' => 'Case Center | BOB-LIFT',
            'news' => 'News | BOB-LIFT',
            'about' => 'About BOB-LIFT | BOB-LIFT',
            'contact' => 'Contact Us | BOB-LIFT',
            'quote' => 'Request Quote | BOB-LIFT',
            'success' => 'Inquiry Submitted | BOB-LIFT',
            default => get_the_title($post) . ' | BOB-LIFT',
        };
        return boblift_localize_seo_text($title);
    }
    return boblift_localize_seo_text(get_the_title($post) . ' | BOB-LIFT');
}

function boblift_seo_default_description_for_post(WP_Post $post): string
{
    if ($post->post_type === 'page') {
        if ((int) get_option('page_on_front') === (int) $post->ID || $post->post_name === 'home') {
            return boblift_localize_seo_text('BOB-LIFT supplies truck mounted cranes, marine cranes, aerial work platforms and lifting attachments for global projects.');
        }
        if ($post->post_name === 'products') {
            $archive = function_exists('boblift_product_archive_options') ? boblift_product_archive_options() : [];
            return boblift_localize_seo_text((string) ($archive['text'] ?? 'Explore BOB-LIFT truck mounted cranes, marine cranes and lifting equipment for global projects.'));
        }
        if ($post->post_name === 'cases') {
            $ui = function_exists('boblift_site_ui') ? boblift_site_ui('casesPage') : [];
            return boblift_localize_seo_text((string) ($ui['heroDescription'] ?? 'Browse customer cases across marine operations, material handling and aerial work applications.'));
        }
        if ($post->post_name === 'news') {
            $ui = function_exists('boblift_site_ui') ? boblift_site_ui('newsPage') : [];
            return boblift_localize_seo_text((string) ($ui['heroDescription'] ?? 'Read BOB-LIFT product updates, factory stories and marine solution articles.'));
        }
        if ($post->post_name === 'about') {
            $about = function_exists('boblift_about_page_options') ? boblift_about_page_options() : [];
            // The profile can run to several paragraphs; the first one is the description.
            $profile = preg_split('/\R\s*\R/u', trim((string) ($about['strength']['text'] ?? ''))) ?: [];
            $first = trim((string) ($profile[0] ?? ''));
            return boblift_localize_seo_text($first !== '' ? $first : 'BOB-LIFT integrates crane engineering, manufacturing and export support for global lifting projects.');
        }
        if ($post->post_name === 'contact') {
            $ui = function_exists('boblift_site_ui') ? boblift_site_ui('contactPage') : [];
            return boblift_localize_seo_text((string) ($ui['heroDescription'] ?? 'Contact BOB-LIFT for crane model selection, truck chassis matching and export questions.'));
        }
        if ($post->post_name === 'quote') {
            $ui = function_exists('boblift_site_ui') ? boblift_site_ui('quotePage') : [];
            return boblift_localize_seo_text((string) ($ui['heroDescription'] ?? 'Request a tailored BOB-LIFT quotation for your lifting requirement.'));
        }
        foreach (['why-choose-us' => 'whyChoose', 'factory-tour' => 'factoryTour', 'quality-testing' => 'qualityTesting', 'mounting-shipping' => 'mountingShipping', 'service' => 'service', 'videos' => 'videoCenter'] as $slug => $section) {
            if (function_exists('boblift_page_is_translation_of_slug') && boblift_page_is_translation_of_slug($post, $slug)) {
                $ui = function_exists('boblift_site_ui') ? boblift_site_ui($section) : [];
                return (string) ($ui['heroText'] ?? '');
            }
        }
        if ($post->post_name === 'success') {
            return boblift_localize_seo_text('Your BOB-LIFT inquiry has been submitted successfully. Our team will contact you soon.');
        }
    }
    $description = boblift_clean_imported_product_text((string) get_post_meta($post->ID, '_short_description', true));
    if ($description === '') {
        $description = has_excerpt($post) ? boblift_clean_imported_product_text((string) get_the_excerpt($post)) : '';
    }
    return boblift_localize_seo_text($description !== '' ? $description : wp_trim_words(wp_strip_all_tags((string) $post->post_content), 28, ''));
}

function boblift_seo_default_title_for_term(WP_Term $term): string
{
    return boblift_localize_seo_text($term->name . ' | BOB-LIFT');
}

function boblift_seo_default_description_for_term(WP_Term $term): string
{
    if ((string) $term->description !== '') {
        return boblift_localize_seo_text((string) $term->description);
    }
    if ($term->taxonomy === 'product_category') {
        return boblift_localize_seo_text('Explore BOB-LIFT ' . $term->name . ' solutions for global lifting and construction projects.');
    }
    return boblift_localize_seo_text('Read BOB-LIFT ' . $term->name . ' updates, product news and lifting solution insights.');
}

function boblift_seo_post_title(WP_Post $post): string
{
    $value = trim((string) get_post_meta($post->ID, '_seo_title', true));
    // Titles scraped from the old marketplace template end in "… Buy … Product on BOB-LIFT".
    if (preg_match('/\bProduct on BOB-LIFT$/i', $value)) {
        $value = '';
    }
    return boblift_localize_seo_text($value !== '' ? $value : boblift_seo_default_title_for_post($post));
}

function boblift_seo_post_description(WP_Post $post): string
{
    $value = boblift_clean_imported_product_text((string) get_post_meta($post->ID, '_seo_description', true));
    return boblift_localize_seo_text($value !== '' ? $value : boblift_seo_default_description_for_post($post));
}

function boblift_seo_term_title(WP_Term $term): string
{
    $value = trim((string) get_term_meta($term->term_id, '_boblift_seo_title', true));
    return boblift_localize_seo_text($value !== '' ? $value : boblift_seo_default_title_for_term($term));
}

function boblift_seo_term_description(WP_Term $term): string
{
    $value = trim((string) get_term_meta($term->term_id, '_boblift_seo_description', true));
    return boblift_localize_seo_text($value !== '' ? $value : boblift_seo_default_description_for_term($term));
}

function boblift_seo_page_by_slug(string $slug, string $field): string
{
    $page = function_exists('boblift_find_page_by_slug') ? boblift_find_page_by_slug($slug) : get_page_by_path($slug, OBJECT, 'page');
    if ($page instanceof WP_Post && function_exists('pll_get_post') && function_exists('pll_current_language')) {
        $language = (string) pll_current_language('slug');
        $translated_id = $language !== '' ? (int) pll_get_post($page->ID, $language) : 0;
        $translated = $translated_id > 0 ? get_post($translated_id) : null;
        if ($translated instanceof WP_Post) {
            $page = $translated;
        }
    }
    if (!$page instanceof WP_Post) {
        return '';
    }
    return $field === 'description' ? boblift_seo_post_description($page) : boblift_seo_post_title($page);
}

function boblift_current_product_category_term(): ?WP_Term
{
    $slug = sanitize_title((string) get_query_var('boblift_product_category'));
    if ($slug !== '') {
        $term = get_term_by('slug', $slug, 'product_category');
        return $term instanceof WP_Term ? $term : null;
    }
    if (is_tax('product_category')) {
        $term = get_queried_object();
        return $term instanceof WP_Term ? $term : null;
    }
    return null;
}

function boblift_document_title_parts(array $title): array
{
    $seo_title = '';
    $product_category = boblift_current_product_category_term();
    if (boblift_current_tonnage() > 0) {
        $seo_title = boblift_tonnage_title(boblift_current_tonnage());
    } elseif ($product_category instanceof WP_Term) {
        $seo_title = boblift_seo_term_title($product_category);
    } elseif (is_category()) {
        $term = get_queried_object();
        $seo_title = $term instanceof WP_Term ? boblift_seo_term_title($term) : '';
    } elseif (is_post_type_archive('product')) {
        $seo_title = boblift_seo_page_by_slug('products', 'title');
    } elseif (is_post_type_archive('case')) {
        $seo_title = boblift_seo_page_by_slug('cases', 'title');
    } elseif (is_singular('feedback')) {
        // Feedback has no per-language title; build it from the model and the localized country.
        $post = get_queried_object();
        $item = $post instanceof WP_Post ? boblift_feedback_item($post) : null;
        $record_title = $post instanceof WP_Post ? boblift_feedback_seo_text($post->ID, 'title') : '';
        if ($record_title !== '') {
            $seo_title = $record_title;
        } elseif ($item) {
            $label = (string) (boblift_site_ui('casesPage')['customerFeedbackLabel'] ?? 'Customer Feedback');
            $seo_title = implode(' · ', array_filter([$item['model'], $item['country']])) . ' | ' . $label . ' | BOB-LIFT';
        }
    } elseif (is_singular()) {
        $post = get_queried_object();
        $seo_title = $post instanceof WP_Post ? boblift_seo_post_title($post) : '';
    } elseif (is_search()) {
        $search_ui = boblift_site_ui('search');
        $search_query = trim(get_search_query(false));
        $seo_title = $search_query !== ''
            ? str_replace('{query}', $search_query, (string) ($search_ui['seoTitleTemplate'] ?? 'Search: {query} | BOB-LIFT'))
            : (string) ($search_ui['seoTitleEmpty'] ?? 'Search | BOB-LIFT');
    }

    if ($seo_title !== '') {
        $title['title'] = $seo_title;
        unset($title['site'], $title['tagline']);
    }

    return $title;
}
add_filter('document_title_parts', 'boblift_document_title_parts', 20);

function boblift_meta_description(): void
{
    $description = '';
    $product_category = boblift_current_product_category_term();
    if (boblift_current_tonnage() > 0) {
        $description = boblift_tonnage_description(boblift_current_tonnage());
    } elseif ($product_category instanceof WP_Term) {
        $description = boblift_seo_term_description($product_category);
    } elseif (is_category()) {
        $term = get_queried_object();
        $description = $term instanceof WP_Term ? boblift_seo_term_description($term) : '';
    } elseif (is_post_type_archive('product')) {
        $description = boblift_seo_page_by_slug('products', 'description');
    } elseif (is_post_type_archive('case')) {
        $description = boblift_seo_page_by_slug('cases', 'description');
    } elseif (is_singular('feedback')) {
        // The record's own description when this language has one, else the description every post falls back to.
        $post = get_queried_object();
        $description = $post instanceof WP_Post ? boblift_feedback_seo_text($post->ID, 'description') : '';
        if ($description === '' && $post instanceof WP_Post) {
            $description = boblift_seo_post_description($post);
        }
    } elseif (is_singular()) {
        $post = get_queried_object();
        $description = $post instanceof WP_Post ? boblift_seo_post_description($post) : '';
    }
    if ($description !== '') {
        echo '<meta name="description" content="' . esc_attr(wp_strip_all_tags($description)) . '">' . "\n";
    }
}
add_action('wp_head', 'boblift_meta_description', 1);

/**
 * Jetpack SEO prints a second description on singular pages (the excerpt or its own field), so a page carried two
 * different ones and search engines could pick the wrong one. The theme's tag above is the only description; Jetpack
 * keeps its other tags, such as robots noindex.
 */
add_filter('jetpack_seo_meta_tags', static function ($meta) {
    if (is_array($meta)) {
        unset($meta['description']);
    }
    return $meta;
});

function boblift_remove_public_user_sitemap($provider, string $name)
{
    return $name === 'users' ? false : $provider;
}
add_filter('wp_sitemaps_add_provider', 'boblift_remove_public_user_sitemap', 10, 2);

// Pages that exist only for routing, in every language: the thank-you page after an inquiry,
// and the old quote page that now 301s to the contact form.
function boblift_utility_page_ids(): array
{
    $ids = [];
    foreach (array_merge(['success', 'quote'], function_exists('boblift_retired_page_slugs') ? boblift_retired_page_slugs() : []) as $slug) {
        $page = function_exists('boblift_find_page_by_slug') ? boblift_find_page_by_slug($slug) : get_page_by_path($slug, OBJECT, 'page');
        if (!$page instanceof WP_Post) {
            continue;
        }
        $page_ids = function_exists('pll_get_post_translations') ? array_map('intval', array_values(pll_get_post_translations($page->ID))) : [];
        $page_ids[] = (int) $page->ID;
        foreach (array_unique($page_ids) as $page_id) {
            // A customised quote page stays reachable (boblift_redirect_quote_page()).
            if ($slug === 'quote') {
                $page_language = function_exists('pll_get_post_language') ? (string) pll_get_post_language($page_id) : '';
                if (function_exists('boblift_quote_page_customized') && boblift_quote_page_customized($page_language)) {
                    continue;
                }
            }
            $ids[] = $page_id;
        }
    }

    return array_values(array_unique(array_filter($ids)));
}

function boblift_noindex_utility_pages(array $robots): array
{
    if (is_page() && in_array((int) get_queried_object_id(), boblift_utility_page_ids(), true)) {
        $robots['noindex'] = true;
        $robots['follow'] = true;
    }

    return $robots;
}
add_filter('wp_robots', 'boblift_noindex_utility_pages');

function boblift_redirect_retired_pages(): void
{
    if (is_admin() || !is_page() || !function_exists('boblift_page_is_translation_of_slug')) {
        return;
    }
    $page = get_queried_object();
    if (!$page instanceof WP_Post) {
        return;
    }
    foreach (boblift_retired_page_slugs() as $slug) {
        if (boblift_page_is_translation_of_slug($page, $slug)) {
            wp_safe_redirect(boblift_url('/about.html'), 301);
            exit;
        }
    }
}
add_action('template_redirect', 'boblift_redirect_retired_pages', 1);

function boblift_exclude_utility_pages_from_sitemap(array $args, string $post_type): array
{
    if ($post_type !== 'page') {
        return $args;
    }

    $excluded_ids = boblift_utility_page_ids();

    if ($excluded_ids) {
        $current = isset($args['post__not_in']) && is_array($args['post__not_in']) ? $args['post__not_in'] : [];
        $args['post__not_in'] = array_values(array_unique(array_merge(array_map('intval', $current), $excluded_ids)));
    }

    return $args;
}
add_filter('wp_sitemaps_posts_query_args', 'boblift_exclude_utility_pages_from_sitemap', 10, 2);

function boblift_standard_sitemap_robots_url(string $output, bool $is_public): string
{
    if (!$is_public) {
        return $output;
    }

    $legacy = home_url('/wp-sitemap.xml');
    $standard = home_url('/sitemap.xml');
    if (str_contains($output, $legacy)) {
        return str_replace($legacy, $standard, $output);
    }

    return rtrim($output) . "\n\nSitemap: " . esc_url($standard) . "\n";
}
add_filter('robots_txt', 'boblift_standard_sitemap_robots_url', 20, 2);

function boblift_standard_sitemap_stylesheet_url(string $url): string
{
    $path = (string) wp_parse_url($url, PHP_URL_PATH);
    if ($path === '/wp-sitemap.xsl') {
        return home_url('/sitemap.xsl');
    }
    if ($path === '/wp-sitemap-index.xsl') {
        return home_url('/sitemap-index.xsl');
    }

    return $url;
}
add_filter('wp_sitemaps_stylesheet_url', 'boblift_standard_sitemap_stylesheet_url');
add_filter('wp_sitemaps_stylesheet_index_url', 'boblift_standard_sitemap_stylesheet_url');

function boblift_standard_sitemap_child_url(string $url): string
{
    $path = (string) wp_parse_url($url, PHP_URL_PATH);
    if (!str_starts_with($path, '/wp-sitemap-') || !str_ends_with($path, '.xml')) {
        return $url;
    }

    return home_url(str_replace('/wp-sitemap-', '/sitemap-', $path));
}

function boblift_standard_sitemap_index_entry(array $entry): array
{
    if (isset($entry['loc']) && is_string($entry['loc'])) {
        $entry['loc'] = boblift_standard_sitemap_child_url($entry['loc']);
    }

    return $entry;
}
add_filter('wp_sitemaps_index_entry', 'boblift_standard_sitemap_index_entry');

function boblift_redirect_core_sitemap_index_to_standard_url(): void
{
    $path = wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    if (!is_string($path)) {
        return;
    }

    if ($path === '/wp-sitemap.xml') {
        wp_safe_redirect(home_url('/sitemap.xml'), 301);
        exit;
    }

    if ($path === '/wp-sitemap.xsl') {
        wp_safe_redirect(home_url('/sitemap.xsl'), 301);
        exit;
    }

    if ($path === '/wp-sitemap-index.xsl') {
        wp_safe_redirect(home_url('/sitemap-index.xsl'), 301);
        exit;
    }

    if (str_starts_with($path, '/wp-sitemap-') && str_ends_with($path, '.xml')) {
        wp_safe_redirect(home_url(str_replace('/wp-sitemap-', '/sitemap-', $path)), 301);
        exit;
    }
}
add_action('template_redirect', 'boblift_redirect_core_sitemap_index_to_standard_url', 0);

function boblift_activation(): void
{
    boblift_register_content_types();
    boblift_register_taxonomies();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'boblift_activation');

function boblift_deactivation(): void
{
    flush_rewrite_rules();
}
add_action('switch_theme', 'boblift_deactivation');

// search.php runs its own product, news and case queries; keep the main search query to those types.
function boblift_limit_main_search(WP_Query $query): void
{
    if (!is_admin() && $query->is_main_query() && $query->is_search()) {
        $query->set('post_type', ['product', 'post', 'case']);
        $query->set('posts_per_page', 1);
    }
}
add_action('pre_get_posts', 'boblift_limit_main_search');

// On the front end the case and product types carry their names in the page's language. Their registered labels are the
// Chinese admin names (案例, 产品), and markup built from them, such as the breadcrumb JSON-LD the platform prints in
// <head>, would otherwise show those on every language.
function boblift_localize_front_post_type_names(): void
{
    if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }
    $names = [
        'case' => (string) (boblift_site_ui('caseDetail')['cases'] ?? 'Cases'),
        'product' => (string) (boblift_site_ui('search')['tabProducts'] ?? 'Products'),
    ];
    foreach ($names as $type => $name) {
        $object = get_post_type_object($type);
        if ($object && $name !== '') {
            $object->label = $name;
            $object->labels->name = $name;
            $object->labels->menu_name = $name;
            $object->labels->archives = $name;
        }
    }
}
add_action('wp', 'boblift_localize_front_post_type_names');

// Article JSON-LD kept in _article_schema_jsonld; {{home}} stands for the site address so the markup follows the host.
// It is the language-neutral root: Polylang adds the language prefix to home_url() on translated pages, and translated
// schema already spells its own prefix ({{home}}/ru/cases/...) and points at shared uploads ({{home}}/wp-content/...).
function boblift_article_schema(): void
{
    if (!is_singular(['post', 'case']) || post_password_required(get_queried_object())) {
        return;
    }
    $schema = trim((string) get_post_meta(get_queried_object_id(), '_article_schema_jsonld', true));
    if ($schema === '') {
        return;
    }
    $schema = str_replace('{{home}}', untrailingslashit((string) get_option('home')), $schema);
    $data = json_decode($schema, true);
    if (!is_array($data)) {
        return;
    }
    echo '<script type="application/ld+json">' . wp_json_encode(boblift_demote_product_nodes($data), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . "</script>\n";
}
add_action('wp_head', 'boblift_article_schema', 5);

// JSON-LD written into the body (a Custom HTML block holding <script type="application/ld+json">, as for a VideoObject)
// is printed in the head instead: the templates pass the body through wp_kses_post(), which drops the <script> tags and
// would leave the JSON on the page as text.
function boblift_content_jsonld_pattern(): string
{
    return '#<script\b[^>]*\btype\s*=\s*(["\'])application/ld\+json\1[^>]*>(.*?)</script>#is';
}

function boblift_strip_content_jsonld(string $content): string
{
    return stripos($content, 'application/ld+json') === false ? $content : (string) preg_replace(boblift_content_jsonld_pattern(), '', $content);
}
add_filter('the_content', 'boblift_strip_content_jsonld', 1);

function boblift_content_jsonld(): void
{
    if (!is_singular() || post_password_required(get_queried_object())) {
        return;
    }
    $content = (string) get_post_field('post_content', get_queried_object_id(), 'raw');
    if (stripos($content, 'application/ld+json') === false || !preg_match_all(boblift_content_jsonld_pattern(), $content, $matches)) {
        return;
    }
    foreach ($matches[2] as $json) {
        $data = json_decode(str_replace('{{home}}', untrailingslashit((string) get_option('home')), trim($json)), true);
        if (is_array($data)) {
            $data = boblift_demote_product_nodes($data);
            echo '<script type="application/ld+json">' . wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . "</script>\n";
        }
    }
}
add_action('wp_head', 'boblift_content_jsonld', 6);

// A Product node (a case's "about": its crane model) makes Search Console report a Product snippet missing offers,
// review or aggregateRating. We publish no prices or ratings, so such nodes go out as a plain Thing that keeps the
// model name and its product page URL.
function boblift_demote_product_nodes(array $data): array
{
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $data[$key] = boblift_demote_product_nodes($value);
        }
    }
    if (isset($data['@type']) && (is_string($data['@type']) ? $data['@type'] === 'Product' : in_array('Product', (array) $data['@type'], true))) {
        $data = ['@type' => 'Thing'] + array_intersect_key($data, array_flip(['@id', 'name', 'url', 'description', 'image']));
    }
    return $data;
}

// Social preview tags for case pages: their SEO title and description, and the cover photo.
function boblift_case_social_tags(): void
{
    if (!is_singular('case') || post_password_required(get_queried_object())) {
        return;
    }
    $id = get_queried_object_id();
    $title = trim((string) get_post_meta($id, '_seo_title', true)) ?: get_the_title($id);
    $description = trim((string) get_post_meta($id, '_seo_description', true)) ?: trim((string) get_post_meta($id, '_summary', true));
    $image = (string) get_the_post_thumbnail_url($id, 'large');
    $tags = [
        'og:type' => 'article',
        'og:title' => $title,
        'og:description' => $description,
        'og:url' => get_permalink($id),
        'og:image' => $image,
        'twitter:card' => 'summary_large_image',
    ];
    foreach (array_filter($tags) as $property => $content) {
        $attribute = str_starts_with($property, 'twitter:') ? 'name' : 'property';
        printf("<meta %s=\"%s\" content=\"%s\">\n", $attribute, esc_attr($property), esc_attr($content));
    }
}
add_action('wp_head', 'boblift_case_social_tags', 5);

// Social preview tags for news posts: their SEO title and description (or the excerpt), and the cover image.
function boblift_news_social_tags(): void
{
    if (!is_singular('post') || post_password_required(get_queried_object())) {
        return;
    }
    $id = get_queried_object_id();
    $title = trim((string) get_post_meta($id, '_seo_title', true)) ?: get_the_title($id);
    $description = trim((string) get_post_meta($id, '_seo_description', true))
        ?: wp_trim_words(wp_strip_all_tags((string) (get_post_field('post_excerpt', $id) ?: get_post_field('post_content', $id))), 40, '…');
    $image = (string) get_the_post_thumbnail_url($id, 'full');
    $tags = [
        'og:type' => 'article',
        'og:site_name' => 'BOB-LIFT',
        'og:title' => $title,
        'og:description' => $description,
        'og:url' => get_permalink($id),
        'og:image' => $image,
        'article:published_time' => get_post_time('c', true, $id),
        'twitter:card' => 'summary_large_image',
        'twitter:title' => $title,
        'twitter:description' => $description,
        'twitter:image' => $image,
    ];
    foreach (array_filter($tags) as $property => $content) {
        $attribute = str_starts_with($property, 'twitter:') ? 'name' : 'property';
        printf("<meta %s=\"%s\" content=\"%s\">\n", $attribute, esc_attr($property), esc_attr($content));
    }
}
add_action('wp_head', 'boblift_news_social_tags', 5);

// The old site's "Crane by Ton" addresses that the redirect rules do not catch (/zh/5-ton-crane, /products/5-ton-crane in
// another case ...) move to the tonnage page in the address's language (inc/tonnage-pages.php), keeping their parameters.
function boblift_redirect_old_tonnage_pages(): void
{
    if (is_admin() || !is_404()) {
        return;
    }
    $size = function_exists('boblift_tonnage_size_from_path') ? boblift_tonnage_size_from_path(boblift_strip_language_prefix((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH))) : 0;
    if ($size <= 0) {
        return;
    }
    $query = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_QUERY);
    wp_safe_redirect(boblift_tonnage_url($size) . ($query !== '' ? '?' . $query : ''), 301);
    exit;
}
add_action('template_redirect', 'boblift_redirect_old_tonnage_pages', 1);

// The product list used to pick categories from ?category= (/products.html?category=marine-crane); each category now has
// its own page, which the list only links to, so such links move there, keeping the other parameters (spec filters, utm_*).
// Several categories (?category=a&category=b, an old multiple choice) have no single page: they move to the whole range,
// which still lists all their products.
function boblift_redirect_category_query_to_category_page(): void
{
    if (is_admin() || (string) get_query_var('boblift_product_category') !== '') {
        return;
    }
    // The product list is served by the products page (/products.html, page "products"), not the post type archive.
    $path = boblift_normalize_public_path(boblift_strip_language_prefix((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH)));
    if (!is_post_type_archive('product') && $path !== boblift_product_archive_path()) {
        return;
    }
    // Read from the raw query string, not $_GET: the list writes a multiple choice as repeated parameters
    // (capacity=2-4&capacity=4-6), which $_GET collapses to the last one.
    $pairs = array_values(array_filter(explode('&', (string) ($_SERVER['QUERY_STRING'] ?? '')), static fn(string $pair): bool => $pair !== ''));
    $slugs = [];
    $kept = [];
    foreach ($pairs as $pair) {
        [$name, $value] = array_pad(explode('=', $pair, 2), 2, '');
        if (urldecode($name) === 'category') {
            $slugs[] = sanitize_title(urldecode($value));
        } else {
            $kept[] = $pair;
        }
    }
    $slugs = array_values(array_unique(array_filter($slugs)));
    if (!$slugs) {
        return;
    }
    $term = count($slugs) === 1 ? get_term_by('slug', $slugs[0], 'product_category') : null;
    $target = $term instanceof WP_Term ? get_term_link($term) : (count($slugs) > 1 ? boblift_product_archive_url() : null);
    if (!is_string($target) || $target === '') {
        return;
    }
    if ($kept) {
        $target .= (str_contains($target, '?') ? '&' : '?') . implode('&', $kept);
    }
    wp_safe_redirect($target, 301);
    exit;
}
add_action('template_redirect', 'boblift_redirect_category_query_to_category_page', 1);

// WordPress.com adds Jetpack "Share this" and "Like this" blocks under every post's text. The theme's own article layout
// has no place for them (and the likes need a WordPress.com login), so they are switched off.
add_filter('sharing_show', '__return_false');
add_filter('wpl_is_likes_visible', '__return_false');

/**
 * Jetpack's Carousel (the full-screen lightbox for pictures in a post's text) loads whenever any text with a picture
 * goes through the_content, which the home page and the lists do for every card they build. Only the templates that print
 * a post's text keep it; elsewhere its stylesheet (render-blocking in <head>), its script, the Swiper prefetch and the
 * hidden lightbox markup in the footer are taken out.
 */
function boblift_carousel_wanted(): bool
{
    global $template;
    return is_singular() && !is_front_page() && in_array(basename((string) $template), ['single.php', 'single-case.php', 'single-feedback.php', 'single-product.php', 'page.php'], true);
}

function boblift_drop_jetpack_carousel(): void
{
    if (is_admin() || boblift_carousel_wanted()) {
        return;
    }
    foreach (['jetpack-carousel', 'jetpack-swiper-library'] as $handle) {
        wp_dequeue_style($handle);
        wp_dequeue_script($handle);
    }
    if (current_action() !== 'wp_footer') {
        return;
    }
    global $wp_filter;
    foreach (($wp_filter['wp_footer']->callbacks ?? []) as $priority => $callbacks) {
        foreach ($callbacks as $callback) {
            $function = $callback['function'];
            if (is_array($function) && is_object($function[0]) && $function[0] instanceof Jetpack_Carousel) {
                remove_action('wp_footer', $function, $priority);
            }
        }
    }
}
add_action('wp_print_styles', 'boblift_drop_jetpack_carousel', 100);
add_action('wp_print_scripts', 'boblift_drop_jetpack_carousel', 100);
add_action('wp_footer', 'boblift_drop_jetpack_carousel', 1);

// WordPress's emoji script swaps emoji for pictures on browsers that cannot draw them, which every current browser can;
// on a phone it cost a 250 ms task on every page. The site's copy has no emoji to draw.
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('wp_enqueue_scripts', 'wp_enqueue_emoji_styles');

/**
 * Some translated articles reached the site with bare image file names (src="chart.png") instead of upload URLs, so the
 * browser looked for the picture next to the article address and showed a broken image. Each bare name is pointed at the
 * uploaded attachment with that file name; names with no matching upload are left as they are.
 */
function boblift_resolve_bare_image_sources(string $content): string {
    if (stripos($content, '<img') === false) {
        return $content;
    }
    return (string) preg_replace_callback('/(<img\b[^>]*?\bsrc=)(["\'])([^"\'\/:?#]+\.(?:jpe?g|png|gif|webp|avif|svg))\2/i', static function (array $m): string {
        static $found = [];
        $name = $m[3];
        if (!array_key_exists($name, $found)) {
            global $wpdb;
            $id = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND (meta_value = %s OR meta_value LIKE %s) ORDER BY post_id LIMIT 1",
                $name,
                '%/' . $wpdb->esc_like($name)
            ));
            $found[$name] = $id ? (string) wp_get_attachment_url($id) : '';
        }
        return $found[$name] !== '' ? $m[1] . $m[2] . esc_url($found[$name]) . $m[2] : $m[0];
    }, $content);
}
add_filter('the_content', 'boblift_resolve_bare_image_sources', 5);

/**
 * Two more ways article pictures broke after moving content between sites: translations copied from the local build still
 * point at http://localhost/wp-content/uploads/..., and a few point at the month folder the picture had locally while the
 * upload here landed in another month. Local addresses are pointed at this site's uploads; an upload address whose file is
 * missing is pointed at the one attachment with that file name. Anything ambiguous or not found is left as it is.
 */
/**
 * The URL of the one attachment whose file is called $name, or '' when none or several are. A generated size such as
 * guide-1024x683.jpg is found through its original (guide.jpg) and returns that size when it exists, else the original.
 */
function boblift_unique_upload_url_for_name(string $name): string {
    global $wpdb;
    $attachment_with_file = static function (string $file) use ($wpdb): int {
        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND (meta_value = %s OR meta_value LIKE %s) LIMIT 2",
            $file,
            '%/' . $wpdb->esc_like($file)
        ));
        // The attachment's id, 0 when no attachment has the file, -1 when several do.
        return count($ids) === 1 ? (int) $ids[0] : (count($ids) > 1 ? -1 : 0);
    };
    $id = $attachment_with_file($name);
    if ($id !== 0) {
        return $id > 0 ? (string) wp_get_attachment_url($id) : '';
    }
    if (!preg_match('/^(.+?)(?:-scaled)?-\d+x\d+(\.[a-z0-9]+)$/i', $name, $size)) {
        return '';
    }
    $id = $attachment_with_file($size[1] . $size[2]) ?: $attachment_with_file($size[1] . '-scaled' . $size[2]);
    if ($id <= 0) {
        return '';
    }
    $original = (string) wp_get_attachment_url($id);
    $original_file = (string) get_attached_file($id);
    $meta = wp_get_attachment_metadata($id);
    foreach ((array) ($meta['sizes'] ?? []) as $generated) {
        if (($generated['file'] ?? '') === $name && file_exists(trailingslashit(dirname($original_file)) . $name)) {
            return trailingslashit(dirname($original)) . rawurlencode($name);
        }
    }
    return $original;
}

function boblift_repair_upload_image_sources(string $content): string {
    if (stripos($content, '/wp-content/uploads/') === false) {
        return $content;
    }
    $uploads = wp_get_upload_dir();
    $base_url = trailingslashit((string) $uploads['baseurl']);
    $content = (string) preg_replace('#https?://(?:localhost|127\.0\.0\.1)(?::\d+)?/wp-content/uploads/#i', $base_url, $content);
    $home = wp_parse_url(home_url('/'));
    $site_authority = strtolower((string) ($home['host'] ?? '') . (isset($home['port']) ? ':' . $home['port'] : ''));
    // The uploads folder as a URL path (on a multisite network it ends in /sites/<id>), so file paths match basedir.
    $base_path = trailingslashit((string) wp_parse_url($base_url, PHP_URL_PATH));
    return (string) preg_replace_callback('/<img\b[^>]*>/i', static function (array $tag) use ($uploads, $site_authority, $base_path): string {
        static $found = [];
        if (!preg_match('/(\bsrc=)(["\'])((?:https?:\/\/([^\/"\']+))?\/[^"\'?#]+)\2/i', $tag[0], $m)) {
            return $tag[0];
        }
        // The host is compared with its port, so a local site such as localhost:8080 matches its own addresses.
        $host = strtolower($m[4] ?? '');
        if ($host !== '' && $host !== $site_authority) {
            return $tag[0];
        }
        // Only addresses inside this site's uploads URL (which carries any install subdirectory) are looked at.
        $path = (string) wp_parse_url($m[3], PHP_URL_PATH);
        if (strpos($path, $base_path) !== 0) {
            return $tag[0];
        }
        $relative = rawurldecode(substr($path, strlen($base_path)));
        if (file_exists(trailingslashit((string) $uploads['basedir']) . $relative)) {
            return $tag[0];
        }
        $name = wp_basename($relative);
        if (!array_key_exists($name, $found)) {
            $found[$name] = boblift_unique_upload_url_for_name($name);
        }
        // No match, or the match is this very file (uploads served from elsewhere, say): the tag stays as it is.
        if ($found[$name] === '' || (string) wp_parse_url($found[$name], PHP_URL_PATH) === $path) {
            return $tag[0];
        }
        // The responsive sources point at the same missing folder, so they go and the repaired src is the only one.
        $repaired = str_replace($m[0], $m[1] . $m[2] . esc_url($found[$name]) . $m[2], $tag[0]);
        return (string) preg_replace('/\s(?:srcset|sizes)=(["\'])[^"\']*\1/i', '', $repaired);
    }, $content);
}
add_filter('the_content', 'boblift_repair_upload_image_sources', 6);
