<?php
/**
 * BOB-LIFT Steel — child theme bootstrap.
 *
 * The parent theme (bobliftcrane) keeps every data concern: post types, options,
 * admin panels, importer and form handlers. This file only swaps the presentation
 * layer: fonts, stylesheet, script and a handful of template helpers.
 */

if (!defined('ABSPATH')) {
    exit;
}

const BOBLIFT_STEEL_VERSION = '0.1.0';

require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/navigation.php';
require_once __DIR__ . '/inc/components.php';

function boblift_steel_uri(string $path = ''): string
{
    return get_stylesheet_directory_uri() . '/' . ltrim($path, '/');
}

function boblift_steel_path(string $path = ''): string
{
    return get_stylesheet_directory() . '/' . ltrim($path, '/');
}

/**
 * Templates that have been rebuilt in this theme. Everything else still renders
 * the parent template body (with the new header, footer and typography) until
 * it is redesigned, so the parent stylesheet is kept on those pages.
 */
function boblift_steel_is_redesigned(): bool
{
    if (is_front_page() || is_singular('product')) {
        return true;
    }
    if (is_post_type_archive('product') || is_tax('product_category') || (string) get_query_var('boblift_product_category') !== '') {
        return true;
    }
    $queried = is_page() ? get_queried_object() : null;
    if ($queried instanceof WP_Post && function_exists('boblift_page_is_translation_of_slug') && boblift_page_is_translation_of_slug($queried, 'products')) {
        return true;
    }
    return false;
}

function boblift_steel_assets(): void
{
    $redesigned = boblift_steel_is_redesigned();
    $css = boblift_steel_path('assets/css/steel.css');
    $js = boblift_steel_path('assets/js/steel.js');

    if ($redesigned) {
        wp_dequeue_style('boblift-theme');
        wp_dequeue_script('boblift-theme');
        wp_dequeue_script('boblift-gsap');
        wp_dequeue_script('boblift-scrolltrigger');
    }

    wp_enqueue_style(
        'boblift-steel-fonts',
        'https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700;800&family=Barlow:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap',
        [],
        null
    );
    wp_enqueue_style('boblift-steel', boblift_steel_uri('assets/css/steel.css'), $redesigned ? ['boblift-steel-fonts'] : ['boblift-steel-fonts', 'boblift-theme'], is_readable($css) ? (string) filemtime($css) : BOBLIFT_STEEL_VERSION);
    wp_enqueue_script('boblift-steel', boblift_steel_uri('assets/js/steel.js'), [], is_readable($js) ? (string) filemtime($js) : BOBLIFT_STEEL_VERSION, true);

    $archive_ui = function_exists('boblift_site_ui') ? boblift_site_ui('productArchive') : [];
    wp_add_inline_script('boblift-steel', 'window.bobliftSteel = ' . wp_json_encode([
        'archive' => [
            'previous' => (string) ($archive_ui['previousLabel'] ?? 'Previous'),
            'next' => (string) ($archive_ui['nextLabel'] ?? 'Next'),
            'perPage' => 12,
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';', 'before');
}
add_action('wp_enqueue_scripts', 'boblift_steel_assets', 20);

function boblift_steel_resource_hints(array $urls, string $relation_type): array
{
    if ($relation_type === 'preconnect') {
        $urls[] = ['href' => 'https://fonts.googleapis.com'];
        $urls[] = ['href' => 'https://fonts.gstatic.com', 'crossorigin' => 'anonymous'];
    }
    return $urls;
}
add_filter('wp_resource_hints', 'boblift_steel_resource_hints', 10, 2);

function boblift_steel_body_class(array $classes): array
{
    $classes[] = 'steel';
    $classes[] = boblift_steel_is_redesigned() ? 'steel--native' : 'steel--legacy';
    return $classes;
}
add_filter('body_class', 'boblift_steel_body_class');

function boblift_steel_head_script(): void
{
    echo "<script>document.documentElement.classList.add('js');</script>\n";
}
add_action('wp_head', 'boblift_steel_head_script', 1);
