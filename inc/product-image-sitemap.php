<?php
if (!defined('ABSPATH')) {
    exit;
}

/*
 * Image sitemap for product pages, at /product-image-sitemap.xml and listed in robots.txt.
 *
 * Jetpack's sitemap (/sitemap.xml) and the core language sitemaps list product pages without their pictures, and most
 * product photos only reach the page as gallery thumbnails, so image search finds them late or not at all. This lists,
 * for every published product page in every language, its main picture and gallery photos at the same CDN address the
 * page itself uses (boblift_image_resized at 1400 px), so Google ties each picture to the page that shows it.
 *
 * Product pages the other sitemaps leave out (configurations that redirect to their series, and the like) are left out
 * here too, through the same jetpack_sitemap_skip_post filter. The XML is kept in a transient and rebuilt after a
 * product or its pictures change.
 */

const BOBLIFT_PRODUCT_IMAGE_SITEMAP_PATH = '/product-image-sitemap.xml';
const BOBLIFT_PRODUCT_IMAGE_SITEMAP_CACHE = 'boblift_product_image_sitemap';

/** The pictures one product page shows: main picture first, then its gallery, each once, as the page's CDN addresses. */
function boblift_product_image_sitemap_images(WP_Post $post): array
{
    $product = boblift_product_from_post($post);
    $sources = [boblift_product_image($product)];
    foreach ((array) ($product['gallery'] ?? []) as $item) {
        $src = is_string($item) ? $item : (string) ($item['image'] ?? $item['url'] ?? '');
        if ($src !== '') {
            $sources[] = str_starts_with($src, 'http') ? $src : boblift_asset($src);
        }
    }
    $images = [];
    foreach ($sources as $src) {
        $src = trim((string) $src);
        // The stand-in picture of a product without its own photo is not this product's picture.
        if ($src === '' || str_contains($src, 'images/products/crane-accessories.png')) {
            continue;
        }
        // Paths are case-sensitive, so two files differing only in case stay two pictures.
        $images[] = boblift_image_resized($src, 1400);
    }
    return array_values(array_unique($images));
}

function boblift_product_image_sitemap_xml(): string
{
    $cached = get_transient(BOBLIFT_PRODUCT_IMAGE_SITEMAP_CACHE);
    if (is_string($cached) && $cached !== '') {
        return $cached;
    }
    $posts = get_posts([
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'ID',
        'order' => 'ASC',
        'lang' => '',
        // A password-protected product keeps its pictures behind the password, so it stays out of a public sitemap.
        'has_password' => false,
        'no_found_rows' => true,
    ]);
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
        . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
    foreach ($posts as $post) {
        if ($post->post_password !== '' || apply_filters('jetpack_sitemap_skip_post', false, $post)) {
            continue;
        }
        $images = boblift_product_image_sitemap_images($post);
        $loc = get_permalink($post);
        if (!$images || !$loc) {
            continue;
        }
        $xml .= '<url><loc>' . esc_xml($loc) . '</loc>';
        foreach ($images as $image) {
            $xml .= '<image:image><image:loc>' . esc_xml($image) . '</image:loc></image:image>';
        }
        $xml .= "</url>\n";
    }
    $xml .= '</urlset>' . "\n";
    set_transient(BOBLIFT_PRODUCT_IMAGE_SITEMAP_CACHE, $xml, 12 * HOUR_IN_SECONDS);
    return $xml;
}

function boblift_product_image_sitemap_serve(): void
{
    // The same address robots.txt gives, so a site under a sub-directory serves it where it says it is.
    $path = wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    if (!is_string($path) || $path !== wp_parse_url(home_url(BOBLIFT_PRODUCT_IMAGE_SITEMAP_PATH), PHP_URL_PATH)) {
        return;
    }
    status_header(200);
    header('Content-Type: application/xml; charset=UTF-8');
    header('X-Robots-Tag: noindex, follow');
    echo boblift_product_image_sitemap_xml();
    exit;
}
add_action('parse_request', 'boblift_product_image_sitemap_serve', 0);

function boblift_product_image_sitemap_robots(string $output, bool $is_public): string
{
    if (!$is_public) {
        return $output;
    }
    return rtrim($output) . "\nSitemap: " . esc_url(home_url(BOBLIFT_PRODUCT_IMAGE_SITEMAP_PATH)) . "\n";
}
add_filter('robots_txt', 'boblift_product_image_sitemap_robots', 30, 2);

function boblift_product_image_sitemap_flush(): void
{
    delete_transient(BOBLIFT_PRODUCT_IMAGE_SITEMAP_CACHE);
}
add_action('save_post_product', 'boblift_product_image_sitemap_flush');
add_action('deleted_post', 'boblift_product_image_sitemap_flush');

function boblift_product_image_sitemap_flush_meta($meta_id, $object_id, $meta_key): void
{
    if (in_array($meta_key, ['_gallery', '_thumbnail_id'], true) && get_post_type((int) $object_id) === 'product') {
        boblift_product_image_sitemap_flush();
    }
}
add_action('added_post_meta', 'boblift_product_image_sitemap_flush_meta', 10, 3);
add_action('updated_post_meta', 'boblift_product_image_sitemap_flush_meta', 10, 3);
add_action('deleted_post_meta', 'boblift_product_image_sitemap_flush_meta', 10, 3);
