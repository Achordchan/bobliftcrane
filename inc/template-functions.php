<?php
if (!defined('ABSPATH')) {
    exit;
}

function boblift_asset(string $path): string
{
    return get_template_directory_uri() . '/assets/' . ltrim($path, '/');
}

function boblift_asset_path(string $path): string
{
    return get_template_directory() . '/assets/' . ltrim($path, '/');
}

function boblift_image_src(string $path): string
{
    if (str_starts_with($path, 'http')) {
        return $path;
    }
    return boblift_asset($path);
}

function boblift_url(string $path): string
{
    $path = trim($path);
    if ($path === '') {
        return function_exists('boblift_public_path_url') ? boblift_public_path_url('/') : home_url('/');
    }
    if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $path) || str_starts_with($path, '#')) {
        return $path;
    }

    $parts = wp_parse_url($path);
    $route = trim((string) ($parts['path'] ?? $path), '/');
    $url = function_exists('boblift_public_url_for_path') ? boblift_public_url_for_path($route) : home_url('/' . $route . '/');
    if (!empty($parts['query'])) {
        $url .= '?' . $parts['query'];
    }
    if (!empty($parts['fragment'])) {
        $url .= '#' . $parts['fragment'];
    }
    return $url;
}

function boblift_img(string $path, string $alt = '', string $class = '', array $attrs = []): string
{
    if (!array_key_exists('loading', $attrs)) {
        $attrs['loading'] = 'lazy';
    }
    if (!array_key_exists('decoding', $attrs)) {
        $attrs['decoding'] = 'async';
    }

    $extra = '';
    foreach ($attrs as $key => $value) {
        $extra .= ' ' . esc_attr($key) . '="' . esc_attr((string) $value) . '"';
    }
    return '<img src="' . esc_url(boblift_asset($path)) . '" alt="' . esc_attr($alt) . '" class="' . esc_attr($class) . '"' . $extra . '>';
}

function boblift_image_setting_url(mixed $image, string $fallback = ''): string
{
    $attachment_id = is_array($image) ? (int) ($image['id'] ?? 0) : 0;
    if ($attachment_id > 0) {
        $url = wp_get_attachment_image_url($attachment_id, 'full');
        if (is_string($url) && $url !== '') {
            return $url;
        }
    }

    $path = is_array($image) ? (string) ($image['path'] ?? '') : (string) $image;
    if ($path === '') {
        $path = $fallback;
    }
    return $path !== '' ? boblift_image_src($path) : '';
}

function boblift_language_options(): array
{
    if (!function_exists('pll_the_languages')) {
        return [];
    }

    $languages = pll_the_languages([
        'raw' => 1,
        'hide_if_empty' => 0,
    ]);
    if (!is_array($languages) || $languages === []) {
        return [];
    }

    $header_ui = function_exists('boblift_site_ui') ? boblift_site_ui('header') : [];
    $markets = is_array($header_ui['languageMarkets'] ?? null) ? $header_ui['languageMarkets'] : [];
    $regional_market = (string) ($header_ui['regionalMarketLabel'] ?? 'Regional Market');

    $languages = array_values(array_filter($languages, static function (array $language): bool {
        $code = (string) ($language['slug'] ?? 'en');
        if (!function_exists('boblift_mls_language_visible_in_switcher')) {
            return true;
        }
        return boblift_mls_language_visible_in_switcher($code);
    }));

    return array_values(array_map(static function (array $language) use ($markets, $regional_market): array {
        $code = (string) ($language['slug'] ?? 'en');
        return [
            'label' => (string) ($language['name'] ?? strtoupper($code)),
            'code' => $code,
            'flag' => boblift_language_flag_src((string) ($language['flag'] ?? ''), $code),
            'href' => (string) ($language['url'] ?? '/'),
            'market' => $markets[$code] ?? $regional_market,
            'current' => !empty($language['current_lang']),
        ];
    }, $languages));
}

function boblift_language_flag_src(string $flag, string $code): string
{
    $theme_flags = [
        'ja' => 'flags/jp.svg',
        'es' => 'flags/es.svg',
        'zh' => 'flags/cn.svg',
        'ru' => 'flags/ru.svg',
        'en' => 'flags/us.svg',
    ];

    if (isset($theme_flags[$code])) {
        return $theme_flags[$code];
    }

    $flag = trim($flag);
    if ($flag !== '') {
        return $flag;
    }

    return 'flags/us.svg';
}

function boblift_language_flag_url(string $flag): string
{
    $flag = trim($flag);
    if ($flag === '') {
        return '';
    }
    if (preg_match('#^(?:https?:)?//#i', $flag) || str_starts_with($flag, 'data:')) {
        return $flag;
    }
    return boblift_asset($flag);
}

function boblift_media_setting_url(mixed $media, string $fallback = ''): string
{
    $attachment_id = is_array($media) ? (int) ($media['id'] ?? 0) : 0;
    if ($attachment_id > 0) {
        $url = wp_get_attachment_url($attachment_id);
        if (is_string($url) && $url !== '') {
            return $url;
        }
    }

    $path = is_array($media) ? (string) ($media['path'] ?? '') : (string) $media;
    if ($path === '') {
        $path = $fallback;
    }
    return $path !== '' ? boblift_image_src($path) : '';
}

function boblift_youtube_embed_url(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }
    $parts = wp_parse_url($url);
    if (!is_array($parts) || empty($parts['host'])) {
        return $url;
    }
    $host = strtolower((string) $parts['host']);
    $host = preg_replace('/^(?:www|m|music)\./', '', $host) ?: $host;
    $path = trim((string) ($parts['path'] ?? ''), '/');
    $video_id = '';
    if ($host === 'youtu.be') {
        $video_id = strtok($path, '/') ?: '';
    } elseif (in_array($host, ['youtube.com', 'youtube-nocookie.com'], true)) {
        parse_str((string) ($parts['query'] ?? ''), $query);
        if (!empty($query['v'])) {
            $video_id = (string) $query['v'];
        } elseif (preg_match('~^(?:embed|shorts|live)/([A-Za-z0-9_-]{6,})~', $path, $match)) {
            $video_id = $match[1];
        }
    }
    if (preg_match('/^[A-Za-z0-9_-]{6,}$/', $video_id)) {
        return 'https://www.youtube.com/embed/' . rawurlencode($video_id) . '?rel=0&modestbranding=1&playsinline=1';
    }
    return $url;
}

function boblift_img_from_setting(mixed $image, string $fallback, string $alt = '', string $class = '', array $attrs = []): string
{
    $src = boblift_image_setting_url($image, $fallback);
    if (!array_key_exists('loading', $attrs)) {
        $attrs['loading'] = 'lazy';
    }
    if (!array_key_exists('decoding', $attrs)) {
        $attrs['decoding'] = 'async';
    }

    $extra = '';
    foreach ($attrs as $key => $value) {
        $extra .= ' ' . esc_attr($key) . '="' . esc_attr((string) $value) . '"';
    }
    return '<img src="' . esc_url($src) . '" alt="' . esc_attr($alt) . '" class="' . esc_attr($class) . '"' . $extra . '>';
}

function boblift_post_thumbnail_url(WP_Post $post, string $fallback): string
{
    $thumbnail_id = get_post_thumbnail_id($post);
    if ($thumbnail_id) {
        $original = function_exists('wp_get_original_image_url') ? wp_get_original_image_url($thumbnail_id) : false;
        if (is_string($original) && $original !== '') {
            return $original;
        }
    }
    $thumbnail = get_the_post_thumbnail_url($post, 'full');
    return is_string($thumbnail) && $thumbnail !== '' ? $thumbnail : $fallback;
}

function boblift_decode_meta(mixed $value, mixed $fallback = []): mixed
{
    if (is_array($value)) {
        return $value;
    }
    if (!is_string($value) || trim($value) === '') {
        return $fallback;
    }
    $decoded = json_decode($value, true);
    return is_array($decoded) ? $decoded : $fallback;
}

function boblift_csv_meta(mixed $value): array
{
    if (is_array($value)) {
        return array_values(array_filter(array_map('strval', $value)));
    }
    return array_values(array_filter(array_map('trim', explode(',', (string) $value))));
}

function boblift_category_map(): array
{
    $map = [];
    foreach (boblift_categories() as $category) {
        $map[$category['slug']] = $category;
    }
    return $map;
}

function boblift_product_seed_lookup(): array
{
    static $lookup = null;
    if ($lookup !== null) {
        return $lookup;
    }
    $lookup = [];
    foreach (boblift_products_seed() as $product) {
        $slug = (string) ($product['slug'] ?? '');
        if ($slug !== '') {
            $lookup[$slug] = $product;
        }
    }
    return $lookup;
}

function boblift_product_phrase_translation_map(): array
{
    static $cache = [];
    $language = function_exists('boblift_current_language_slug') ? boblift_current_language_slug() : (function_exists('pll_current_language') ? (string) pll_current_language('slug') : 'default');
    $language = $language !== '' ? $language : 'default';
    if (isset($cache[$language])) {
        return $cache[$language];
    }

    $phrases_by_slug = [
        'truck-mounted-crane' => ['Truck Mounted Crane', 'Truck Mounted Cranes', 'Cranes for Truck'],
        'knuckle-boom-truck-mounted-cranes' => ['Knuckle Boom Truck Mounted Crane', 'Knuckle Boom Truck Mounted Cranes'],
        'telescopic-boom-truck-mounted-cranes' => ['Telescopic Boom Truck Mounted Crane', 'Telescopic Boom Truck Mounted Cranes'],
        'marine-crane' => ['Marine Crane', 'Marine Cranes', 'Ship Crane', 'Ship Cranes'],
        'knuckle-boom-marine-cranes' => ['Knuckle Boom Marine Crane', 'Knuckle Boom Marine Cranes'],
        'telescopic-boom-marine-cranes' => ['Telescopic Boom Marine Crane', 'Telescopic Boom Marine Cranes'],
        'pickup-cranes' => ['Pickup Crane', 'Pickup Cranes'],
        'forklift-fly-jib-cranes' => ['Forklift Fly Jib Crane', 'Forklift Fly Jib Cranes'],
        'mini-dumpers' => ['Mini Dumper', 'Mini Dumpers'],
        'timber-cranes' => ['Timber Crane', 'Timber Cranes'],
        'spider-cranes' => ['Spider Crane', 'Spider Cranes'],
        'tail-lift' => ['Tail Lift'],
        'aerial-work-platform' => ['Aerial Work Platform'],
    ];

    $category_by_source = [];
    foreach (boblift_categories() as $category) {
        $source_slug = (string) ($category['source_slug'] ?? $category['slug'] ?? '');
        $title = trim((string) ($category['title'] ?? ''));
        if ($source_slug !== '' && $title !== '') {
            $category_by_source[$source_slug] = $title;
        }
    }

    $map = [];
    foreach ($phrases_by_slug as $source_slug => $phrases) {
        $replacement = $category_by_source[$source_slug] ?? '';
        if ($replacement === '') {
            continue;
        }
        foreach ($phrases as $phrase) {
            // Singular and plural of the same name are not a rename: "Pickup Crane" must not become "Pickup Cranes".
            if (strcasecmp(rtrim($phrase, 's'), rtrim($replacement, 's')) !== 0) {
                $map[$phrase] = $replacement;
            }
        }
    }

    uksort($map, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));
    $cache[$language] = $map;
    return $map;
}

function boblift_localize_product_text_fragments(mixed $value): mixed
{
    if (is_array($value)) {
        foreach ($value as $key => $child) {
            $value[$key] = boblift_localize_product_text_fragments($child);
        }
        return $value;
    }
    if (!is_string($value) || $value === '') {
        return $value;
    }

    foreach (boblift_product_phrase_translation_map() as $phrase => $replacement) {
        $value = preg_replace('/(?<![A-Za-z])' . preg_quote($phrase, '/') . '(?![A-Za-z])/i', $replacement, $value) ?? $value;
    }

    $static_phrases = [
        'Project-specific configuration',
        'Customer Case',
        'Marine Operations',
        'Complex Working Conditions',
        'Main product view',
        'Product photo',
        'Product Photos',
        'Product Details',
        'Product detail image',
        'Product stage',
        'Detail scroll',
        'Technical Specifications',
        'Quick Specs',
        'Key Features',
        'Specifications',
        'model no',
        'Electic starting Yes',
        'parameter chart',
        'Stable lifting structure',
        'Designed for practical jobsite operation with reliable boom movement and load control',
        'Export-ready configuration',
        'Suitable for international procurement with documentation, packing and configuration support',
        'Application matching',
        'Can be matched to working radius, yard or attachment requirements',
        'image',
        'Truck Mounted',
        'Meter Aerial Work Platform',
        'Knuckle Spider Crane',
        'Telescopic Spider Crane',
        'Pickup Crane With Outriggers',
        'Pickup Crane Without Outriggers',
        'With Outriggers',
        'Without Outriggers',
        'Arm Length',
        'Ton Mini Dumper',
    ];
    foreach ($static_phrases as $phrase) {
        $replacement = function_exists('boblift_localized_static_text') ? boblift_localized_static_text($phrase) : $phrase;
        if ($replacement !== $phrase) {
            $value = preg_replace('/(?<![A-Za-z])' . preg_quote($phrase, '/') . '(?![A-Za-z])/i', $replacement, $value) ?? $value;
        }
    }

    $language = function_exists('boblift_current_language_slug') ? boblift_current_language_slug() : (function_exists('pll_current_language') ? (string) pll_current_language('slug') : '');
    if ($language !== '' && (!function_exists('boblift_mls_source_language') || $language !== boblift_mls_source_language())) {
        $ton = function_exists('boblift_localized_static_text') ? boblift_localized_static_text('Ton', $language) : 'Ton';
        if ($ton !== 'Ton') {
            $value = preg_replace('/(\d+(?:\.\d+)?)\s+Tons?\b/i', '$1' . $ton, $value) ?? $value;
        }
        $timely_delivery = function_exists('boblift_localized_static_text') ? boblift_localized_static_text('timely delivery', $language) : 'timely delivery';
        if ($timely_delivery !== 'timely delivery') {
            $value = preg_replace('/(?<![A-Za-z])timely delivery(?![A-Za-z])/i', $timely_delivery, $value) ?? $value;
        }
    }

    return $value;
}

function boblift_text_has_untranslated_english(string $html): bool
{
    $text = html_entity_decode(wp_strip_all_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\b(?:BOB[- ]?LIFT|SQ\d+[A-Z0-9]*|KW|MPa|L\/?min|kg|mm|m3?|EAEU|ISO|CE)\b/i', ' ', $text) ?? $text;
    return (bool) preg_match('/\b[A-Za-z][a-z]{2,}(?:\s+[A-Za-z][a-z]{2,}){2,}\b/', $text);
}

function boblift_normalize_product_applications(array $applications): array
{
    $archive_ui = function_exists('boblift_site_ui') ? boblift_site_ui('productArchive') : [];
    $options = is_array($archive_ui['applicationOptions'] ?? null) ? $archive_ui['applicationOptions'] : [];
    $aliases = [
        'construction' => ['construction', '建設'],
        'logistics' => ['logistics', '物流'],
        'municipal-engineering' => ['municipal-engineering', 'municipal engineering', '市政工事', '自治体エンジニアリング', '都市土木', '都市土木工学', '土木工学', '市政工学'],
        'marine' => ['marine', '海事', '海洋', '海運'],
        'agriculture' => ['agriculture', '農業'],
        'mining' => ['mining', '鉱業'],
    ];
    foreach ($options as $key => $label) {
        $aliases[(string) $key][] = (string) $key;
        $aliases[(string) $key][] = (string) $label;
    }

    $lookup = [];
    foreach ($aliases as $key => $values) {
        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                $lookup[mb_strtolower($value)] = (string) $key;
                $lookup[sanitize_title($value)] = (string) $key;
            }
        }
    }

    $normalized = [];
    foreach ($applications as $application) {
        $application = trim((string) $application);
        if ($application === '') {
            continue;
        }
        $key = $lookup[mb_strtolower($application)] ?? $lookup[sanitize_title($application)] ?? $application;
        $normalized[] = $key;
    }
    return array_values(array_unique($normalized));
}

function boblift_apply_product_runtime_meta(array $product, ?WP_Post $post = null): array
{
    $seed = boblift_product_seed_lookup()[(string) ($product['slug'] ?? '')] ?? [];
    $product['boom_type'] = function_exists('boblift_normalize_product_boom_type')
        ? boblift_normalize_product_boom_type((string) ($product['boom_type'] ?? ''))
        : sanitize_title((string) ($product['boom_type'] ?? ''));
    $product['featured'] = (bool) ($seed['featured'] ?? $product['featured'] ?? false);
    $product['popularity'] = (int) ($seed['popularity'] ?? $product['popularity'] ?? 50);
    $product['created_at'] = (string) ($seed['created_at'] ?? ($post instanceof WP_Post ? get_post_time(DATE_ATOM, false, $post) : ($product['created_at'] ?? '')));
    return $product;
}

// The June import copied placeholders from the old site into the model field ("z1", "TRUCK-MOUNTED",
// "TRUCK-TAIL", or the product name repeated). They are not model numbers, so they are never shown as one.
function boblift_clean_product_model(string $model, string $title): string
{
    $model = trim($model);
    if ($model === '' || in_array(strtolower($model), ['z1', 'truck-mounted', 'truck-tail'], true) || strcasecmp($model, trim($title)) === 0) {
        return '';
    }
    return $model;
}

// Descriptions scraped from the old marketplace template ("…, find complete details about … - BOB-LIFT")
// are keyword lists, not copy. Drop them so the caller falls back to real text.
function boblift_clean_imported_product_text(string $text): string
{
    $text = trim($text);
    if ($text === '' || stripos($text, 'find complete details about') !== false) {
        return '';
    }
    return ltrim($text, " ,;-–—");
}

function boblift_product_from_post(WP_Post $post): array
{
    $terms = get_the_terms($post, 'product_category');
    $product_terms = (!is_wp_error($terms) && is_array($terms)) ? array_values(array_filter($terms, static fn($term): bool => $term instanceof WP_Term)) : [];
    usort($product_terms, static fn(WP_Term $a, WP_Term $b): int => ((int) $b->parent <=> (int) $a->parent) ?: strcasecmp($a->name, $b->name));
    $category_slugs = array_values(array_unique(array_map(static fn(WP_Term $term): string => $term->slug, $product_terms)));
    if (!$category_slugs) {
        $category_slugs = ['other-cranes'];
    }
    $category_slug = $category_slugs[0];
    $category = boblift_category_map()[$category_slug] ?? ['title' => $category_slug, 'slug' => $category_slug];
    $image = boblift_post_thumbnail_url($post, boblift_asset('images/products/crane-accessories.png'));
    $product_image_rows = static function (mixed $raw): array {
        $items = boblift_decode_meta($raw, []);
        $images = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $item_image = (string) ($item['url'] ?? $item['image'] ?? '');
                if ($item_image !== '') {
                    $images[] = [
                        'image' => $item_image,
                        'label' => boblift_localize_product_text_fragments((string) ($item['label'] ?? '')),
                    ];
                }
                continue;
            }
            $item_image = (string) $item;
            if ($item_image !== '') {
                $images[] = $item_image;
            }
        }
        return $images;
    };
    $gallery = $product_image_rows(get_post_meta($post->ID, '_gallery', true));
    $detail_images = $product_image_rows(get_post_meta($post->ID, '_detail_images', true));

    $raw_hotspots = boblift_decode_meta(get_post_meta($post->ID, '_hotspots', true), []);
    $hotspots = [];
    foreach ($raw_hotspots as $item) {
        if (!is_array($item)) {
            continue;
        }
        $label = trim((string) boblift_localize_product_text_fragments((string) ($item['label'] ?? '')));
        if ($label === '') {
            continue;
        }
        $hotspots[] = [
            'label' => $label,
            'x' => max(0, min(100, (float) ($item['x'] ?? 50))),
            'y' => max(0, min(100, (float) ($item['y'] ?? 50))),
        ];
    }

    $title = boblift_localize_product_text_fragments(get_the_title($post));
    $short_description = boblift_clean_imported_product_text((string) get_post_meta($post->ID, '_short_description', true));
    if ($short_description === '') {
        $short_description = boblift_clean_imported_product_text((string) get_the_excerpt($post));
    }
    if ($short_description === '') {
        $short_description = wp_trim_words(wp_strip_all_tags((string) $post->post_content), 40);
    }
    $short_description = boblift_localize_product_text_fragments($short_description);
    $features = boblift_localize_product_text_fragments(boblift_decode_meta(get_post_meta($post->ID, '_features', true), []));
    $content = boblift_localize_product_text_fragments(apply_filters('the_content', $post->post_content));
    if (is_string($content) && boblift_text_has_untranslated_english($content)) {
        $content = '';
    }

    return boblift_apply_product_runtime_meta([
        'id' => $post->ID,
        'slug' => $post->post_name,
        'title' => $title,
        'model' => boblift_clean_product_model((string) get_post_meta($post->ID, '_model', true), (string) get_the_title($post)),
        'category' => $category_slug,
        'categories' => $category_slugs,
        'category_title' => $category['title'] ?? $category_slug,
        'capacity' => boblift_localize_product_text_fragments((string) get_post_meta($post->ID, '_capacity', true)),
        'capacity_value' => (float) get_post_meta($post->ID, '_capacity_value', true),
        'boom_type' => (string) get_post_meta($post->ID, '_boom_type', true),
        'catalog_pdf_url' => esc_url_raw((string) get_post_meta($post->ID, '_catalog_pdf_url', true)),
        'applications' => boblift_normalize_product_applications(boblift_csv_meta(get_post_meta($post->ID, '_applications', true))),
        'status' => (string) get_post_meta($post->ID, '_status', true),
        'image' => $image,
        'image_is_url' => true,
        'short_description' => $short_description,
        'tags' => boblift_csv_meta(get_post_meta($post->ID, '_tags', true)),
        'specs' => boblift_localize_product_text_fragments(boblift_decode_meta(get_post_meta($post->ID, '_specs', true), [])),
        'features' => is_array($features) ? $features : [],
        'gallery' => $gallery,
        'detail_images' => $detail_images,
        'hotspots' => $hotspots,
        'detail_url' => get_permalink($post),
        'inquiry_url' => boblift_url('/contact.html?product=' . rawurlencode($title) . '#inquiry'),
        'content' => $content,
    ], $post);
}

function boblift_product_url(array $product): string
{
    return $product['detail_url'] ?? boblift_html_url((string) ($product['slug'] ?? ''));
}

function boblift_product_image(array $product): string
{
    $image = $product['image'] ?? 'images/products/crane-accessories.png';
    return !empty($product['image_is_url']) ? $image : boblift_asset($image);
}

function boblift_product_catalog_url(array $product): string
{
    $url = trim((string) ($product['catalog_pdf_url'] ?? $product['catalog_url'] ?? $product['pdf_url'] ?? ''));
    return $url !== '' ? esc_url_raw($url) : '';
}

function boblift_all_products(): array
{
    $query = new WP_Query([
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'],
    ]);
    if ($query->have_posts()) {
        return array_map('boblift_product_from_post', $query->posts);
    }
    return array_map(static function (array $product): array {
        $product['detail_url'] = boblift_html_url((string) $product['slug']);
        $product['inquiry_url'] = boblift_url('/contact.html?product=' . rawurlencode($product['title']) . '#inquiry');
        return boblift_apply_product_runtime_meta($product);
    }, boblift_products_seed());
}

function boblift_find_product(string $slug): ?array
{
    $post = get_page_by_path($slug, OBJECT, 'product');
    if ($post instanceof WP_Post && $post->post_status === 'publish') {
        return boblift_product_from_post($post);
    }
    return boblift_find_seed_item('products', $slug);
}

function boblift_articles(string $category_slug = '', int $posts_per_page = 12): array
{
    $category_slug = sanitize_title($category_slug);
    $query_args = [
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => $posts_per_page,
        'orderby' => 'date',
        'order' => 'DESC',
        'ignore_sticky_posts' => true,
    ];
    if ($category_slug !== '') {
        $query_args['category_name'] = $category_slug;
    }
    $query = new WP_Query($query_args);
    if ($query->have_posts()) {
        return array_map(static function (WP_Post $post): array {
            $news_ui = function_exists('boblift_site_ui') ? boblift_site_ui('newsDetail') : [];
            $post_category = get_the_category($post->ID)[0] ?? null;
            $category = $post_category instanceof WP_Term ? $post_category->name : ($news_ui['news'] ?? 'News');
            $category = function_exists('boblift_localized_static_text') ? boblift_localized_static_text((string) $category) : (string) $category;
            $category_slug = $post_category instanceof WP_Term ? $post_category->slug : sanitize_title((string) $category);
            return [
                'id' => $post->ID,
                'slug' => $post->post_name,
                'title' => boblift_localize_product_text_fragments(get_the_title($post)),
                'date' => get_the_date('Y-m-d', $post),
                'category' => $category,
                'category_slug' => $category_slug,
                'image' => boblift_post_thumbnail_url($post, boblift_asset('images/factory-workshop.jpg')),
                'image_is_url' => true,
                'excerpt' => boblift_localize_product_text_fragments(get_the_excerpt($post)),
                'content' => boblift_localize_product_text_fragments(apply_filters('the_content', $post->post_content)),
                'url' => get_permalink($post),
            ];
        }, $query->posts);
    }
    if ($category_slug !== '') {
        return [];
    }
    return array_map(static function (array $article): array {
        $article['url'] = boblift_html_url((string) $article['slug']);
        $article['category_slug'] = sanitize_title((string) ($article['category_slug'] ?? $article['category'] ?? 'news'));
        return $article;
    }, boblift_articles_seed());
}

function boblift_normalize_case_kind(string $kind): string
{
    return in_array($kind, ['scene_case', 'customer_feedback'], true) ? $kind : 'scene_case';
}

function boblift_case_fact_value(array $facts, string $label): string
{
    foreach ($facts as $fact) {
        $fact_label = strtolower(trim((string) ($fact['label'] ?? '')));
        if ($fact_label === strtolower($label)) {
            return trim((string) ($fact['value'] ?? ''));
        }
    }
    return '';
}

function boblift_case_media_url(array $item): string
{
    $media_url = trim((string) ($item['feedback_media_url'] ?? ''));
    if ($media_url === '') {
        return boblift_item_image($item);
    }
    if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $media_url) || str_starts_with($media_url, '/')) {
        return $media_url;
    }
    return boblift_asset($media_url);
}

function boblift_normalize_case_item(array $item): array
{
    $facts = is_array($item['facts'] ?? null) ? $item['facts'] : [];
    $item['case_kind'] = boblift_normalize_case_kind((string) ($item['case_kind'] ?? 'scene_case'));
    $item['country'] = trim((string) ($item['country'] ?? ''));
    $item['product_name'] = trim((string) ($item['product_name'] ?? boblift_case_fact_value($facts, 'Product')));
    $item['application'] = trim((string) ($item['application'] ?? boblift_case_fact_value($facts, 'Scenario')));
    $item['feedback_quote'] = trim((string) ($item['feedback_quote'] ?? ''));
    $media_type = strtolower(trim((string) ($item['feedback_media_type'] ?? 'image')));
    $item['feedback_media_type'] = $media_type === 'video' ? 'video' : 'image';
    $item['feedback_media_url'] = trim((string) ($item['feedback_media_url'] ?? ''));
    return $item;
}

function boblift_case_item_from_post(WP_Post $post): array
{
    $facts = boblift_decode_meta(get_post_meta($post->ID, '_facts', true), []);
    $item = [
        'id' => $post->ID,
        'slug' => $post->post_name,
        'title' => boblift_localize_product_text_fragments(get_the_title($post)),
        'type' => boblift_localize_product_text_fragments((string) get_post_meta($post->ID, '_type', true)),
        'summary' => boblift_localize_product_text_fragments((string) (get_post_meta($post->ID, '_summary', true) ?: get_the_excerpt($post))),
        'facts' => boblift_localize_product_text_fragments($facts),
        'sections' => boblift_localize_product_text_fragments(boblift_decode_meta(get_post_meta($post->ID, '_sections', true), [])),
        'image' => boblift_post_thumbnail_url($post, boblift_asset('images/marine-scene.jpg')),
        'image_is_url' => true,
        'content' => boblift_localize_product_text_fragments(apply_filters('the_content', $post->post_content)),
        'url' => get_permalink($post),
        'case_kind' => (string) get_post_meta($post->ID, '_case_kind', true),
        'country' => (string) get_post_meta($post->ID, '_country', true),
        'product_name' => boblift_localize_product_text_fragments((string) get_post_meta($post->ID, '_product_name', true)),
        'application' => boblift_localize_product_text_fragments((string) get_post_meta($post->ID, '_application', true)),
        'feedback_quote' => boblift_localize_product_text_fragments((string) get_post_meta($post->ID, '_feedback_quote', true)),
        'feedback_media_type' => (string) get_post_meta($post->ID, '_feedback_media_type', true),
        'feedback_media_url' => (string) get_post_meta($post->ID, '_feedback_media_url', true),
    ];
    return boblift_normalize_case_item($item);
}

function boblift_cases(): array
{
    $query = new WP_Query(['post_type' => 'case', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC']);
    if ($query->have_posts()) {
        $items = array_map(static fn (WP_Post $post): array => boblift_case_item_from_post($post), $query->posts);
        $seed_order = array_flip(array_column(boblift_cases_seed(), 'slug'));
        usort($items, static function (array $a, array $b) use ($seed_order): int {
            $a_order = $seed_order[$a['slug'] ?? ''] ?? PHP_INT_MAX;
            $b_order = $seed_order[$b['slug'] ?? ''] ?? PHP_INT_MAX;
            return $a_order <=> $b_order;
        });
        return $items;
    }
    return array_map(static function (array $case): array {
        $case['url'] = function_exists('boblift_localized_url') ? boblift_localized_url(home_url('/cases/' . $case['slug'])) : home_url('/cases/' . $case['slug']);
        return boblift_normalize_case_item($case);
    }, boblift_cases_seed());
}

function boblift_item_image(array $item): string
{
    $image = $item['image'] ?? '';
    return !empty($item['image_is_url']) ? $image : boblift_asset($image);
}

function boblift_body_open(): void
{
    if (function_exists('wp_body_open')) {
        wp_body_open();
    }
}

/**
 * The old hero and closing-banner pictures were a 300px thumbnail, had a headline baked in or showed
 * a generic crane. Stored options that still point at them get a clean photo instead.
 */
function boblift_page_hero_image(string $image): string
{
    $replacements = [
        'images/factory-workshop.jpg' => 'images/page-heroes/factory-floor.webp',
        'images/global-network-blue.jpg' => 'images/page-heroes/sales-team.webp',
        'images/marine-scene.jpg' => 'images/page-heroes/marine-deck.webp',
        'images/cta-lift-photo.png' => 'images/page-heroes/site-dusk.webp',
    ];
    $path = ltrim($image, '/');
    return boblift_image_src($replacements[$path] ?? $image);
}

// Image setting (uploaded attachment or theme path) for a hero or banner, with old theme paths replaced.
function boblift_page_hero_image_setting(mixed $image, string $fallback): string
{
    if (is_array($image) && (int) ($image['id'] ?? 0) > 0) {
        return boblift_image_setting_url($image, $fallback);
    }
    $path = trim(is_array($image) ? (string) ($image['path'] ?? '') : (string) $image);
    return boblift_page_hero_image($path !== '' ? $path : $fallback);
}

/**
 * The hero at the top of inner pages, the same card as the product list banner: heading on the left,
 * photo on the right fading into the card (mask rules in input.css). Without an image it is a plain
 * text card, used by the policy pages.
 *
 * Keys: label, title, title_highlight (phrase shown in blue), text, meta (small line under the text),
 * image (URL or theme path), image_alt, image_position (CSS object-position),
 * links (list of [label, href, icon, external]), class, attrs (already escaped, for scripts).
 */
function boblift_page_hero(array $hero): void
{
    $image = trim((string) ($hero['image'] ?? ''));
    $has_image = $image !== '';
    $title = (string) ($hero['title'] ?? '');
    $title_html = esc_html($title);
    $highlight = trim((string) ($hero['title_highlight'] ?? ''));
    if ($highlight !== '' && str_contains($title, $highlight)) {
        $title_html = preg_replace('/' . preg_quote(esc_html($highlight), '/') . '/', '<span class="text-blue-700">' . esc_html($highlight) . '</span>', $title_html, 1);
    }
    $meta = trim((string) ($hero['meta'] ?? ''));
    $links = array_values(array_filter(is_array($hero['links'] ?? null) ? $hero['links'] : [], static fn($link): bool => is_array($link) && trim((string) ($link['label'] ?? '')) !== ''));
    $inner_class = $has_image
        ? 'px-5 py-6 sm:px-8 lg:flex lg:min-h-[320px] lg:max-w-[46%] lg:flex-col lg:justify-center lg:py-10 xl:min-h-[340px] xl:px-12 2xl:min-h-[360px] 2xl:max-w-[44%] min-[1920px]:min-h-[380px]'
        : 'px-5 py-7 sm:px-8 md:py-10 xl:px-12 xl:py-12';
    ?>
    <section class="boblift-page-hero relative overflow-hidden rounded border border-line bg-white shadow-card <?php echo esc_attr((string) ($hero['class'] ?? '')); ?>" <?php echo (string) ($hero['attrs'] ?? ''); ?>>
        <?php if ($has_image) : ?>
            <div class="boblift-page-hero-media relative aspect-[11/5] w-full overflow-hidden bg-mist sm:aspect-[3/1] lg:absolute lg:inset-y-0 lg:right-0 lg:aspect-auto lg:w-[72%] lg:bg-white xl:w-[66%] 2xl:w-[62%] min-[1920px]:w-[56%]">
                <img class="absolute inset-0 h-full w-full object-cover" style="object-position: <?php echo esc_attr((string) ($hero['image_position'] ?? 'right 30%')); ?>" src="<?php echo esc_url(boblift_page_hero_image($image)); ?>" alt="<?php echo esc_attr((string) ($hero['image_alt'] ?? '')); ?>" loading="eager" decoding="async" fetchpriority="high" data-banner-image>
            </div>
        <?php endif; ?>
        <div class="boblift-page-hero-inner relative z-10 <?php echo esc_attr($inner_class); ?>">
            <p class="boblift-page-hero-label mb-3 text-xs font-black uppercase tracking-[0.18em] text-[#0B3F7D] empty:hidden" data-banner-label><?php echo esc_html(trim((string) ($hero['label'] ?? ''))); ?></p>
            <h1 class="boblift-page-hero-title <?php echo $has_image ? '' : 'max-w-4xl'; ?> text-[clamp(2rem,3vw,3.75rem)] font-black leading-[1.02] tracking-[-0.02em] text-[#0B1B33]" data-banner-title><?php echo $title_html; ?></h1>
            <p class="boblift-page-hero-text mt-4 <?php echo $has_image ? 'max-w-xl' : 'max-w-3xl'; ?> text-sm font-semibold leading-7 text-slate-600 empty:hidden md:text-base md:leading-8" data-banner-text><?php echo esc_html(trim((string) ($hero['text'] ?? ''))); ?></p>
            <?php if ($meta !== '') : ?><p class="mt-4 text-xs font-black uppercase tracking-[0.14em] text-slate-400"><?php echo esc_html($meta); ?></p><?php endif; ?>
            <?php if ($links !== []) : ?>
                <div class="mt-5 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm font-bold text-navy md:mt-6">
                    <?php foreach ($links as $link) : ?>
                        <a class="inline-flex items-center gap-2 transition hover:text-blue-700" href="<?php echo esc_url((string) ($link['href'] ?? '#')); ?>"<?php echo !empty($link['external']) ? ' target="_blank" rel="noreferrer"' : ''; ?>><?php echo !empty($link['icon']) ? boblift_icon((string) $link['icon'], 'h-4 w-4 text-slate-400') : ''; ?><?php echo esc_html((string) $link['label']); ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php
}

function boblift_button(string $label, string $href, string $variant = 'primary'): string
{
    $class = $variant === 'white' ? 'btn-white' : 'btn-primary';
    $icon = $variant === 'white' ? '' : boblift_icon('arrow-right', 'h-4 w-4');
    return '<a class="' . esc_attr($class) . '" href="' . esc_url(boblift_url($href)) . '">' . esc_html($label) . $icon . '</a>';
}

function boblift_normalize_product_status(string $status): string
{
    $status = strtolower(trim($status));
    return match ($status) {
        'in-stock', 'best-seller', 'pre-order' => $status,
        default => 'customizable',
    };
}

function boblift_product_status_label(string $status): string
{
    $normalized = boblift_normalize_product_status($status);
    $ui = function_exists('boblift_site_ui') ? boblift_site_ui('productDetail') : [];
    $labels = is_array($ui['productStatusLabels'] ?? null) ? $ui['productStatusLabels'] : [];
    return (string) ($labels[$normalized] ?? match ($normalized) {
        'in-stock' => 'In Stock',
        'best-seller' => 'Best Seller',
        'pre-order' => 'Pre-Order',
        default => 'Customizable',
    });
}

function boblift_product_status_badge(string $status, string $class = ''): string
{
    $normalized = boblift_normalize_product_status($status);
    $config = match ($normalized) {
        'in-stock' => ['icon' => 'check-circle', 'wrapper' => 'text-emerald-700', 'icon_class' => 'text-emerald-600'],
        'best-seller' => ['icon' => 'flame', 'wrapper' => 'text-blue-700', 'icon_class' => 'text-red-500'],
        'pre-order' => ['icon' => 'clock-3', 'wrapper' => 'text-orange-700', 'icon_class' => 'text-orange-600'],
        default => ['icon' => 'wrench', 'wrapper' => 'text-indigo-700', 'icon_class' => 'text-indigo-600'],
    };
    return '<span class="inline-flex items-center gap-2 rounded-sm bg-white/92 py-2 pl-3 pr-5 text-sm font-black leading-none shadow-card ring-1 ring-[#E5E7EB] backdrop-blur ' . esc_attr($config['wrapper'] . ' ' . $class) . '" data-status-badge="' . esc_attr($normalized) . '">' . boblift_icon($config['icon'], 'h-5 w-5 ' . $config['icon_class']) . esc_html(boblift_product_status_label($normalized)) . '</span>';
}

function boblift_boom_label(string $boom): string
{
    $boom = function_exists('boblift_normalize_product_boom_type') ? boblift_normalize_product_boom_type($boom) : sanitize_title($boom);
    $options = function_exists('boblift_product_boom_options') ? boblift_product_boom_options() : [];
    return $boom !== '' ? (string) ($options[$boom] ?? '') : '';
}

function boblift_product_card_specs(array $product): array
{
    $specs = array_values(array_filter($product['specs'] ?? [], static fn($spec): bool => is_array($spec) && ($spec['label'] ?? '') !== '' && ($spec['value'] ?? '') !== ''));
    if ($specs) {
        return array_slice($specs, 0, 3);
    }
    $product_ui = function_exists('boblift_site_ui') ? boblift_site_ui('productDetail') : [];
    $labels = is_array($product_ui['specLabels'] ?? null) ? $product_ui['specLabels'] : [];
    $custom_value = (string) ($labels['customValue'] ?? 'Custom');
    $capacity = (float) ($product['capacity_value'] ?? 0);
    $boom_length = $capacity > 0 ? ((string) max(6, (int) ceil($capacity * 2.2))) . ' m' : $custom_value;
    return [
        ['key' => 'maxLift', 'label' => (string) ($labels['maxLift'] ?? 'Max Lift'), 'value' => $product['capacity'] ?? $custom_value],
        ['key' => 'boomLength', 'label' => (string) ($labels['boomLength'] ?? 'Boom Length'), 'value' => $boom_length],
        ['key' => 'rotation', 'label' => (string) ($labels['rotation'] ?? 'Rotation'), 'value' => ($product['category'] ?? '') === 'crane-accessories' ? $custom_value : '360°'],
    ];
}

function boblift_product_spec_icon_name(string $label, string $key = ''): string
{
    return match ($key !== '' ? $key : $label) {
        'boomLength', 'Boom Length' => 'maximize-2',
        'rotation', 'Rotation' => 'rotate-cw',
        default => 'weight',
    };
}

function boblift_product_card(array $product, string $extra_class = '', bool $eager_image = false): void
{
    $product_title = trim((string) ($product['title'] ?? ''));
    $product_model = trim((string) ($product['model'] ?? ''));
    $product_name = $product_title;
    if ($product_model !== '') {
        $title_without_model = trim(str_ireplace($product_model, '', $product_title), " \t\n\r\0\x0B-_–—|/:");
        if ($title_without_model !== '') {
            $product_name = $title_without_model;
        }
    }
    $card_eyebrow = $product_model !== '' ? $product_name : (string) ($product['category_title'] ?? 'BOB-LIFT');
    $card_heading = $product_model !== '' ? $product_model : $product_title;
    $apps = implode(',', $product['applications'] ?? []);
    $product_categories = array_values(array_unique(array_filter(array_map('strval', $product['categories'] ?? [($product['category'] ?? '')]))));
    $status = boblift_normalize_product_status((string) ($product['status'] ?? 'customizable'));
    $specs = boblift_product_card_specs($product);
    $detail_url = boblift_product_url($product);
    $inquiry_url = $product['inquiry_url'] ?? boblift_url('/contact.html#inquiry');
    $product_id = (string) ($product['id'] ?? $product['slug'] ?? $product['title']);
    $quick_specs = esc_attr(wp_json_encode($specs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $product_ui = function_exists('boblift_site_ui') ? boblift_site_ui('productDetail') : [];
    $quote_list_ui = function_exists('boblift_site_ui') ? boblift_site_ui('quoteList') : [];
    $add_quote_label = (string) ($quote_list_ui['addLabel'] ?? 'Add to Quote List');
    $quick_view_label = (string) ($product_ui['quickViewLabel'] ?? 'Quick view');
    $view_details_label = (string) ($product_ui['viewDetailsLabel'] ?? 'View Details');
    $request_quote_label = (string) ($product_ui['requestQuoteLabel'] ?? 'Request Quote');
    ?>
    <article class="product-card group flex min-h-full cursor-pointer flex-col overflow-hidden rounded border border-[#E5E7EB] bg-white shadow-card transition duration-300 hover:-translate-y-1 hover:border-blue-200 <?php echo esc_attr($extra_class); ?>" data-product-id="<?php echo esc_attr($product_id); ?>" data-product-slug="<?php echo esc_attr((string) ($product['slug'] ?? '')); ?>" data-category="<?php echo esc_attr($product['category'] ?? ''); ?>" data-categories="<?php echo esc_attr(implode(',', $product_categories)); ?>" data-category-title="<?php echo esc_attr($product['category_title'] ?? 'BOB-LIFT'); ?>" data-capacity="<?php echo esc_attr((string) ($product['capacity_value'] ?? 0)); ?>" data-boom="<?php echo esc_attr($product['boom_type'] ?? ''); ?>" data-applications="<?php echo esc_attr($apps); ?>" data-status="<?php echo esc_attr($status); ?>" data-status-label="<?php echo esc_attr(boblift_product_status_label($status)); ?>" data-featured="<?php echo !empty($product['featured']) ? '1' : '0'; ?>" data-created="<?php echo esc_attr((string) ($product['created_at'] ?? '')); ?>" data-popularity="<?php echo esc_attr((string) ($product['popularity'] ?? 0)); ?>" data-title="<?php echo esc_attr($product['title']); ?>" data-image="<?php echo esc_url(boblift_product_image($product)); ?>" data-summary="<?php echo esc_attr($product['short_description'] ?? ''); ?>" data-inquiry-url="<?php echo esc_url($inquiry_url); ?>" data-detail-url="<?php echo esc_url($detail_url); ?>" data-specs="<?php echo $quick_specs; ?>">
        <div class="product-card-inner flex min-h-full flex-col">
            <div class="product-card-media relative h-64 overflow-hidden bg-[radial-gradient(circle_at_48%_34%,#ffffff_0%,#f8fafc_34%,#eef2f7_64%,#e2e8f0_100%)] sm:h-72 lg:h-64 xl:h-72">
                <span class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_50%_82%,rgba(15,23,42,0.13),transparent_44%)]"></span>
                <img class="absolute inset-0 z-10 h-full w-full scale-[1.08] object-contain p-4 drop-shadow-[0_6px_8px_rgba(11,27,51,0.10)] transition duration-500 group-hover:scale-[1.12] sm:p-5" src="<?php echo esc_url(boblift_product_image($product)); ?>" alt="<?php echo esc_attr($product['title']); ?>" <?php echo $eager_image ? 'loading="eager" fetchpriority="high"' : 'loading="lazy"'; ?> decoding="async">
                <a class="absolute inset-0 z-[15]" href="<?php echo esc_url($detail_url); ?>" aria-label="<?php echo esc_attr($view_details_label . ' ' . $product['title']); ?>"><span class="sr-only"><?php echo esc_html($view_details_label . ' ' . $product['title']); ?></span></a>
                <?php echo boblift_product_status_badge($status, 'absolute left-5 top-5 z-20'); ?>
                <div class="product-card-overlay-actions absolute right-5 top-5 z-20 flex flex-col gap-3"><button class="inline-flex h-11 w-11 items-center justify-center rounded-sm bg-white text-[#0B1B33] shadow-card transition hover:-translate-y-0.5 hover:text-blue-700" type="button" data-product-favorite aria-label="<?php echo esc_attr($add_quote_label); ?>"><?php echo boblift_icon('heart', 'h-5 w-5'); ?></button><button class="inline-flex h-11 w-11 items-center justify-center rounded-sm bg-white text-[#0B1B33] shadow-card transition hover:-translate-y-0.5 hover:text-blue-700" type="button" data-product-quick-view aria-label="<?php echo esc_attr($quick_view_label . ' ' . $product['title']); ?>"><?php echo boblift_icon('eye', 'h-5 w-5'); ?></button></div>
            </div>
            <div class="product-card-body flex flex-1 flex-col p-6">
                <a class="block" href="<?php echo esc_url($detail_url); ?>" aria-label="<?php echo esc_attr($view_details_label . ' ' . $product['title']); ?>">
                    <div class="product-card-meta mb-1.5 flex min-w-0 items-center gap-4 font-semibold normal-case tracking-normal text-slate-600"><span class="line-clamp-2 block min-w-0 max-w-full break-words text-[14px] leading-[1.35]"><?php echo esc_html($card_eyebrow); ?></span></div>
                    <h2 class="product-card-title font-black <?php echo $product_model !== '' ? 'product-card-model text-[clamp(28px,1.8vw,34px)] uppercase leading-none tracking-[0.02em] text-[#0B3F7D]' : 'text-xl leading-tight tracking-[-0.01em] text-[#0B1B33]'; ?>"><?php echo esc_html($card_heading); ?></h2>
                    <p class="product-card-summary mt-3 hidden max-w-3xl text-sm font-semibold leading-6 text-slate-600 lg:text-base lg:leading-7"><?php echo esc_html($product['short_description'] ?? ''); ?></p>
                </a>
                <div class="product-card-footer mt-auto grid grid-cols-2 gap-3 pt-5">
                    <div class="product-card-list-actions hidden gap-3"><button class="inline-flex h-11 w-11 items-center justify-center rounded-sm border border-[#E5E7EB] bg-white text-[#0B1B33] transition hover:-translate-y-0.5 hover:text-blue-700" type="button" data-product-favorite aria-label="<?php echo esc_attr($add_quote_label); ?>"><?php echo boblift_icon('heart', 'h-5 w-5'); ?></button><button class="inline-flex h-11 w-11 items-center justify-center rounded-sm border border-[#E5E7EB] bg-white text-[#0B1B33] transition hover:-translate-y-0.5 hover:text-blue-700" type="button" data-product-quick-view aria-label="<?php echo esc_attr($quick_view_label . ' ' . $product['title']); ?>"><?php echo boblift_icon('eye', 'h-5 w-5'); ?></button></div>
                    <div class="product-card-ctas contents"><button class="inline-flex min-h-12 items-center justify-center gap-2 whitespace-nowrap rounded-sm bg-[#0B1B33] px-4 text-[13px] font-black text-white shadow-card transition hover:-translate-y-0.5 hover:bg-[#0B3F7D]" type="button" data-inquiry-modal-open data-inquiry-source="产品列表卡片" data-inquiry-product="<?php echo esc_attr($product['title']); ?>" data-inquiry-product-slug="<?php echo esc_attr((string) ($product['slug'] ?? '')); ?>"><?php echo boblift_icon('file-text', 'h-4 w-4'); ?><span class="hidden sm:inline"><?php echo esc_html($request_quote_label); ?></span><span class="sm:hidden"><?php echo esc_html($quote_list_ui['quoteShortLabel'] ?? 'Quote'); ?></span></button><a class="inline-flex min-h-12 items-center justify-center gap-2 whitespace-nowrap rounded-sm border border-[#0B1B33]/40 bg-white px-4 text-[13px] font-black text-[#0B1B33] transition hover:-translate-y-0.5 hover:border-blue-500 hover:text-blue-700" href="<?php echo esc_url($detail_url); ?>"><span class="hidden sm:inline"><?php echo esc_html($view_details_label); ?></span><span class="sm:hidden"><?php echo esc_html($product_ui['viewShortLabel'] ?? 'View'); ?></span> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a></div>
                </div>
            </div>
        </div>
    </article>
    <?php
}

function boblift_render_customer_visits_section(array $visits, string $variant = 'default'): void
{
    $items = array_values(array_filter($visits['items'] ?? [], static function ($item): bool {
        if (!is_array($item)) {
            return false;
        }
        $image = $item['image'] ?? [];
        if (is_array($image)) {
            return (int) ($image['id'] ?? 0) > 0 || trim((string) ($image['path'] ?? '')) !== '';
        }
        return trim((string) $image) !== '';
    }));
    if ($items === []) {
        return;
    }
    $section_class = $variant === 'home'
        ? 'bg-[linear-gradient(180deg,#ffffff_0%,#f8fbff_100%)] py-7 md:py-9 lg:py-10'
        : 'bg-[#F8FAFC] py-10 md:py-14 lg:py-20';
    $visible_items = array_slice($items, 0, 6);
    if ($variant === 'home') {
        ?>
        <section id="global-customer-visits" class="<?php echo esc_attr($section_class); ?>">
            <div class="page-shell">
                <div class="mb-5 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                    <div class="min-w-0">
                        <p class="section-label mb-3"><?php echo esc_html((string) ($visits['label'] ?? 'Global Customer Visits')); ?></p>
                        <h2 class="text-[clamp(30px,4vw,58px)] font-black leading-[1.02] tracking-[-0.015em] text-ink"><?php echo esc_html((string) ($visits['home_title'] ?? 'Customer Visit Gallery')); ?></h2>
                    </div>
                    <a class="inline-flex h-11 w-fit shrink-0 items-center justify-center gap-2 rounded-sm bg-navy px-5 text-xs font-black text-white shadow-card transition hover:-translate-y-0.5 hover:bg-[#0b2d5b] md:h-12 md:px-6 md:text-sm" href="<?php echo esc_url(boblift_url('/global-customer-visits.html')); ?>"><?php echo esc_html((string) ($visits['home_cta_label'] ?? 'View More Photos')); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
                </div>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6">
                    <?php foreach ($visible_items as $index => $item) : ?>
                        <figure class="group relative overflow-hidden rounded border border-line bg-white shadow-card transition duration-300 hover:-translate-y-1 ">
                            <div class="relative aspect-[1.18/1] overflow-hidden bg-[#eef5ff]">
                                <?php echo boblift_img_from_setting($item['image'] ?? [], 'images/factory-workshop.jpg', (string) ($item['title'] ?? $item['label'] ?? 'BOB-LIFT customer visit'), 'absolute inset-0 h-full w-full object-cover object-center transition duration-500 group-hover:scale-[1.04]', ['loading' => $index === 0 ? 'eager' : 'lazy', 'decoding' => 'async']); ?>
                            </div>
                        </figure>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
        return;
    }
    ?>
    <section id="global-customer-visits" class="<?php echo esc_attr($section_class); ?>">
        <div class="page-shell">
            <div class="grid gap-7 lg:grid-cols-[minmax(0,0.72fr)_minmax(0,1.28fr)] lg:items-end lg:gap-10">
                <div class="min-w-0">
                    <p class="section-label mb-4"><?php echo esc_html((string) ($visits['label'] ?? 'Global Customer Visits')); ?></p>
                    <h2 class="max-w-[760px] text-[clamp(32px,5.4vw,68px)] font-black leading-[0.98] tracking-[-0.01em] text-ink"><?php echo esc_html((string) ($visits['title'] ?? 'Welcoming partners to see how BOB-LIFT builds.')); ?></h2>
                    <p class="mt-5 max-w-[680px] text-sm font-semibold leading-7 text-slate-600 md:text-base md:leading-8"><?php echo esc_html((string) ($visits['text'] ?? '')); ?></p>
                    <?php if (!empty($visits['cta_label'])) : ?>
                        <a class="mt-6 inline-flex h-12 items-center justify-center gap-2 rounded-sm bg-navy px-6 text-sm font-black text-white shadow-card transition hover:-translate-y-0.5 hover:bg-[#0b2d5b]" href="<?php echo esc_url(boblift_url((string) ($visits['cta_href'] ?? '/contact.html'))); ?>"><?php echo esc_html((string) $visits['cta_label']); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
                    <?php endif; ?>
                </div>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 lg:gap-4">
                    <?php foreach ($visible_items as $index => $item) : ?>
                        <figure class="group relative overflow-hidden rounded border border-line bg-white shadow-card transition duration-300 hover:-translate-y-1 ">
                            <div class="relative aspect-[1.18/1] overflow-hidden bg-[#eef5ff]">
                                <?php echo boblift_img_from_setting($item['image'] ?? [], 'images/factory-workshop.jpg', (string) ($item['title'] ?? $item['label'] ?? 'BOB-LIFT customer visit'), 'absolute inset-0 h-full w-full object-cover object-center transition duration-500 group-hover:scale-[1.04]', ['loading' => $variant === 'home' && $index === 0 ? 'eager' : 'lazy', 'decoding' => 'async']); ?>
                            </div>
                        </figure>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    <?php
}

function boblift_product_nav_url(string $href, string $label = ''): string
{
    $slug = '';
    $label = trim($label);
    if ($label !== '') {
        $term_by_name = get_term_by('name', $label, 'product_category');
        if ($term_by_name instanceof WP_Term) {
            $slug = $term_by_name->slug;
        }
    }

    if ($slug === '') {
        $parts = wp_parse_url($href);
        $path = trim((string) ($parts['path'] ?? ''), '/');
        if ($path === 'products' && !empty($parts['fragment'])) {
            $slug = sanitize_title((string) $parts['fragment']);
        }
        if ($slug === '' && $path === 'products' && !empty($parts['query'])) {
            parse_str((string) $parts['query'], $query);
            if (!empty($query['category']) && is_string($query['category'])) {
                $slug = sanitize_title($query['category']);
            }
        }
        if ($slug === '' && preg_match('#^products/category/([^/]+)$#', $path, $matches)) {
            $slug = sanitize_title($matches[1]);
        }
        if ($slug === '' && $path !== '' && !str_contains($path, '/')) {
            $path_slug = preg_replace('/\.html$/', '', $path);
            $term_by_slug = get_term_by('slug', sanitize_title((string) $path_slug), 'product_category');
            if ($term_by_slug instanceof WP_Term) {
                $slug = $term_by_slug->slug;
            }
        }
    }

    if ($slug !== '') {
        $term = get_term_by('slug', $slug, 'product_category');
        if ($term instanceof WP_Term) {
            $url = get_term_link($term);
            if (!is_wp_error($url)) {
                return $url;
            }
        }
    }

    return boblift_url($href);
}
