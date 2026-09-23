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
    wp_enqueue_script('boblift-theme', get_template_directory_uri() . '/assets/js/theme.js', $deps, is_readable($js) ? (string) filemtime($js) : '1.0.0', true);
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



function boblift_localize_seo_text(string $text): string
{
    if ($text === '') {
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
            'products' => 'Construction Equipment | BOB-LIFT',
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
            return boblift_localize_seo_text((string) ($archive['text'] ?? 'Explore reliable BOB-LIFT lifting and construction machinery for global projects.'));
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
            return boblift_localize_seo_text((string) ($about['strength']['text'] ?? 'BOB-LIFT integrates crane engineering, manufacturing and export support for global lifting projects.'));
        }
        if ($post->post_name === 'contact') {
            $ui = function_exists('boblift_site_ui') ? boblift_site_ui('contactPage') : [];
            return boblift_localize_seo_text((string) ($ui['heroDescription'] ?? 'Contact BOB-LIFT for crane model selection, truck chassis matching and export questions.'));
        }
        if ($post->post_name === 'quote') {
            $ui = function_exists('boblift_site_ui') ? boblift_site_ui('quotePage') : [];
            return boblift_localize_seo_text((string) ($ui['heroDescription'] ?? 'Request a tailored BOB-LIFT quotation for your lifting requirement.'));
        }
        if ($post->post_name === 'success') {
            return boblift_localize_seo_text('Your BOB-LIFT inquiry has been submitted successfully. Our team will contact you soon.');
        }
    }
    $description = (string) get_post_meta($post->ID, '_short_description', true);
    if ($description === '') {
        $description = has_excerpt($post) ? get_the_excerpt($post) : '';
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
    return boblift_localize_seo_text($value !== '' ? $value : boblift_seo_default_title_for_post($post));
}

function boblift_seo_post_description(WP_Post $post): string
{
    $value = trim((string) get_post_meta($post->ID, '_seo_description', true));
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
    $page = get_page_by_path($slug, OBJECT, 'page');
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
    if ($product_category instanceof WP_Term) {
        $seo_title = boblift_seo_term_title($product_category);
    } elseif (is_category()) {
        $term = get_queried_object();
        $seo_title = $term instanceof WP_Term ? boblift_seo_term_title($term) : '';
    } elseif (is_post_type_archive('product')) {
        $seo_title = boblift_seo_page_by_slug('products', 'title');
    } elseif (is_post_type_archive('case')) {
        $seo_title = boblift_seo_page_by_slug('cases', 'title');
    } elseif (is_singular()) {
        $post = get_queried_object();
        $seo_title = $post instanceof WP_Post ? boblift_seo_post_title($post) : '';
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
    if ($product_category instanceof WP_Term) {
        $description = boblift_seo_term_description($product_category);
    } elseif (is_category()) {
        $term = get_queried_object();
        $description = $term instanceof WP_Term ? boblift_seo_term_description($term) : '';
    } elseif (is_post_type_archive('product')) {
        $description = boblift_seo_page_by_slug('products', 'description');
    } elseif (is_post_type_archive('case')) {
        $description = boblift_seo_page_by_slug('cases', 'description');
    } elseif (is_singular()) {
        $post = get_queried_object();
        $description = $post instanceof WP_Post ? boblift_seo_post_description($post) : '';
    }
    if ($description !== '') {
        echo '<meta name="description" content="' . esc_attr(wp_strip_all_tags($description)) . '">' . "\n";
    }
}
add_action('wp_head', 'boblift_meta_description', 1);

function boblift_remove_public_user_sitemap($provider, string $name)
{
    return $name === 'users' ? false : $provider;
}
add_filter('wp_sitemaps_add_provider', 'boblift_remove_public_user_sitemap', 10, 2);

function boblift_exclude_utility_pages_from_sitemap(array $args, string $post_type): array
{
    if ($post_type !== 'page') {
        return $args;
    }

    $excluded_ids = [];
    foreach (['success'] as $slug) {
        $page = get_page_by_path($slug, OBJECT, 'page');
        if ($page instanceof WP_Post) {
            $excluded_ids[] = (int) $page->ID;
        }
    }

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
