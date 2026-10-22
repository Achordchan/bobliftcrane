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

function boblift_text_direction(): string
{
    if (function_exists('pll_current_language')) {
        $language = pll_current_language('OBJECT');
        if (is_object($language) && isset($language->is_rtl)) {
            return $language->is_rtl ? 'rtl' : 'ltr';
        }
    }

    return is_rtl() ? 'rtl' : 'ltr';
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
        'fr' => 'flags/fr.svg',
        'ar' => 'flags/sa.svg',
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

/**
 * Whether a product's body text ("_detail_body_ready") or detail images ("_detail_images_ready") may be shown.
 * An editor's choice wins: a saved "1" shows it, a saved empty value hides it. Without a saved choice:
 *   - a product that was not imported from the old site shows its content;
 *   - an imported product shows its body once the body differs from the raw old-site copy kept in _legacy_content (the
 *     brochure text and editor rewrites), so products published before these boxes existed keep their text;
 *   - imported detail images show once they are no longer the untouched old-site pictures (redesigned by the project's tools, or
 *     pictures an editor added: their attachments lack the _old_site_image_url marker); untouched old-site pictures stay hidden until the box is ticked.
 * Updating other fields of a post (an excerpt, a gallery) never makes the old-site body visible.
 */
function boblift_product_content_ready(int $post_id, string $flag_key): bool
{
    if (metadata_exists('post', $post_id, $flag_key)) {
        return get_post_meta($post_id, $flag_key, true) === '1';
    }
    // A translation without its own choice follows the source product: translating the body is not an editorial review, and
    // the translated body always differs from the untranslated _legacy_content backup.
    if (function_exists('pll_get_post') && function_exists('pll_get_post_language')) {
        $source_language = function_exists('boblift_mls_source_language') ? (string) boblift_mls_source_language() : 'en';
        $source_id = (int) pll_get_post($post_id, $source_language);
        if ($source_id > 0 && $source_id !== $post_id && (string) pll_get_post_language($post_id) !== $source_language) {
            return boblift_product_content_ready($source_id, $flag_key);
        }
    }
    if ((string) get_post_meta($post_id, '_old_site_imported_at', true) === '') {
        return true;
    }
    if ($flag_key !== '_detail_body_ready') {
        // Detail images are reviewed when the project redesigned them (marker meta of the detail-image tools) or when an editor
        // put in pictures of their own: an imported old-site picture carries _old_site_image_url, anything else does not.
        foreach (array_keys((array) get_post_meta($post_id)) as $meta_key) {
            if (str_starts_with((string) $meta_key, '_detail_images_before_') || str_starts_with((string) $meta_key, '_boblift_detail_')) {
                return true;
            }
        }
        // Stored either as a JSON string or as a native meta array; boblift_decode_meta() reads both.
        $rows = boblift_decode_meta(get_post_meta($post_id, '_detail_images', true), []);
        $uploads_base = trailingslashit(wp_get_upload_dir()['baseurl']);
        foreach (is_array($rows) ? $rows : [] as $row) {
            $src = html_entity_decode((string) (is_array($row) ? ($row['image'] ?? $row['url'] ?? '') : $row));
            // Same normalisation as the product page: unwrap the WebP converter, then match on the uploads path (the stored host
            // can be an old or language domain).
            if (preg_match('/webpc-passthru\.php\?(?:[^#]*&)?src=([^&#]+)/', $src, $found)) {
                $src = rawurldecode($found[1]);
            }
            $site_hosts = [strtolower((string) wp_parse_url(home_url(), PHP_URL_HOST)), strtolower((string) wp_parse_url(wp_get_upload_dir()['baseurl'], PHP_URL_HOST))];
            // Every language domain counts as this site, as in single-product.php.
            foreach (function_exists('pll_languages_list') ? pll_languages_list() : [] as $language_slug) {
                $site_hosts[] = strtolower((string) wp_parse_url((string) (function_exists('pll_home_url') ? pll_home_url($language_slug) : ''), PHP_URL_HOST));
            }
            $site_hosts = array_filter($site_hosts);
            $original_host = strtolower((string) wp_parse_url($src, PHP_URL_HOST));
            // Only this site's own addresses (an old or language domain) are moved onto the uploads host; another site's
            // /wp-content/uploads/ picture keeps its host, so the check below still sees it as an editor's choice.
            $own_host = $original_host === '' || in_array($original_host, $site_hosts, true) || str_contains($original_host, 'bobliftcrane.com');
            if ($own_host && preg_match('#/wp-content/uploads/(.+)$#', $src, $found)) {
                $src = $uploads_base . $found[1];
            }
            $attachment_id = $src !== '' ? attachment_url_to_postid($src) : 0;
            // Only a resolved attachment is evidence: one without the old-site import marker is an editor's own picture.
            // A lookup that finds nothing says nothing.
            if ($attachment_id > 0 && !metadata_exists('post', $attachment_id, '_old_site_image_url')) {
                return true;
            }
            if ($attachment_id === 0 && $src !== '') {
                // Pictures outside the media library that an editor can only have chosen themselves: a theme-relative path, or an
                // address on a host that is neither this site nor the old site's image CDN.
                $src_host = strtolower((string) wp_parse_url($src, PHP_URL_HOST));
                $is_theme_path = $src_host === '' && !str_starts_with($src, '/') && !str_starts_with($src, '//');
                if ($is_theme_path || ($src_host !== '' && !in_array($src_host, $site_hosts, true) && !str_contains($src_host, 'ldycdn.com') && !str_contains($src_host, 'bobliftcrane.com'))) {
                    return true;
                }
            }
        }
        return false;
    }
    // Text plus embedded media (images, galleries, video) make up the content; a body replaced with media only still counts.
    $signature = static function (string $html): string {
        preg_match_all('/<(?:img|video|audio|iframe|source|embed)\b[^>]*?\bsrc=["\']([^"\']+)/i', $html, $media);
        $text = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(wp_strip_all_tags($html), ENT_QUOTES, 'UTF-8')));
        return $text . '|' . implode(',', $media[1]);
    };
    $post = get_post($post_id);
    $body = $post instanceof WP_Post ? $signature($post->post_content) : '|';
    return $body !== '|' && $body !== $signature((string) get_post_meta($post_id, '_legacy_content', true));
}

/**
 * A page by its slug alone, so pages nested under About (about/<slug>) are found too. Prefers the source-language page
 * when translations share the slug; returns null when there is none.
 */
function boblift_find_page_by_slug(string $slug): ?WP_Post
{
    $pages = get_posts(['post_type' => 'page', 'post_status' => 'publish', 'name' => $slug, 'posts_per_page' => -1, 'lang' => '', 'suppress_filters' => false]);
    $source = function_exists('boblift_mls_source_language') ? (string) boblift_mls_source_language() : 'en';
    foreach ($pages as $page) {
        $language = function_exists('pll_get_post_language') ? pll_get_post_language($page->ID) : $source;
        if (!$language || $language === $source) {
            return $page;
        }
    }
    return $pages[0] ?? null;
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
    $repeated_name = strcasecmp($model, trim($title)) === 0
        && !preg_match('/^(?=.*[a-z])(?=.*\d)[a-z0-9.+\/_-]+$/i', $model);
    if ($model === '' || in_array(strtolower($model), ['z1', 'truck-mounted', 'truck-tail'], true) || $repeated_name) {
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
    // Unicode-aware: a byte mask would also strip the leading bytes of other punctuation such as curly quotes.
    return (string) preg_replace('/^[\s,;\-–—]+/u', '', $text);
}

// How a product image sits on the hero stage: 'cutout' floats whole on the floor, 'photo' fills the frame.
// tools/image-fit records the fit from the pixels; images without it fall back to the file name.
function boblift_image_fit(string $url): string
{
    // attachment_url_to_postid() scans the postmeta table once per image, so the fits are kept in the listing cache
    // and the ones worked out on this request are saved when it ends.
    $state = &$GLOBALS['boblift_image_fits'];
    if (!is_array($state)) {
        $name = boblift_cache_name('image-fit');
        $cached = boblift_cache_get($name, $found);
        $state = ['name' => $name, 'fits' => $found && is_array($cached) ? $cached : [], 'dirty' => false];
    }
    $cache = &$state['fits'];
    if ($url === '') {
        return 'photo';
    }
    if (!isset($cache[$url])) {
        if (!$state['dirty']) {
            $state['dirty'] = true;
            add_action('shutdown', 'boblift_image_fit_save', 0);
        }
        // Theme assets are not media-library attachments, so only uploads are looked up.
        $theme_asset = str_starts_with($url, trailingslashit(get_template_directory_uri()))
            || str_starts_with($url, trailingslashit(get_stylesheet_directory_uri()));
        $id = $theme_asset ? 0 : attachment_url_to_postid($url);
        if (!$id && !$theme_asset) {
            // Resized copies ("-768x768.png") belong to the full-size attachment; retry only when that changes the URL.
            $full_size = (string) preg_replace('/-\d+x\d+(\.[a-z]+)$/i', '$1', (string) strtok($url, '?'));
            if ($full_size !== $url) {
                $id = attachment_url_to_postid($full_size);
            }
        }
        $fit = $id ? (string) get_post_meta($id, '_boblift_image_fit', true) : '';
        if ($fit !== 'cutout' && $fit !== 'photo') {
            $fit = preg_match('/transparent|\.png(?:$|[?&])/i', $url) ? 'cutout' : 'photo';
        }
        $cache[$url] = $fit;
    }
    return $cache[$url];
}

function boblift_image_fit_save(): void
{
    $state = $GLOBALS['boblift_image_fits'] ?? null;
    if (is_array($state) && $state['dirty']) {
        boblift_cache_set($state['name'], $state['fits']);
    }
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
    // Dimension drawings cropped from the product manual: [{id, image, label}], sized from the attachment.
    $drawings = [];
    foreach (boblift_decode_meta(get_post_meta($post->ID, '_drawings', true), []) as $item) {
        $source = is_array($item) ? wp_get_attachment_image_src((int) ($item['id'] ?? 0), 'full') : false;
        if ($source) {
            $drawings[] = ['image' => (string) $source[0], 'width' => (int) $source[1], 'height' => (int) $source[2], 'label' => boblift_localize_product_text_fragments((string) ($item['label'] ?? ''))];
        }
    }

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
    // The body is a fallback only when the product is not password protected.
    if ($short_description === '' && !post_password_required($post)) {
        $short_description = wp_trim_words(wp_strip_all_tags((string) $post->post_content), 40);
    }
    $short_description = boblift_localize_product_text_fragments($short_description);
    $features = boblift_localize_product_text_fragments(boblift_decode_meta(get_post_meta($post->ID, '_features', true), []));
    $content = post_password_required($post) ? '' : boblift_localize_product_text_fragments(apply_filters('the_content', $post->post_content));
    // Hide English copy left untranslated on the Japanese and Chinese sites; English pages keep it.
    $content_language = function_exists('boblift_current_language_slug') ? boblift_current_language_slug() : '';
    if (is_string($content) && !in_array($content_language, ['', 'en'], true) && boblift_text_has_untranslated_english($content)) {
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
        'boom_type' => (string) (get_post_meta($post->ID, '_boom_type', true) ?: get_post_meta(boblift_product_english_id($post->ID), '_boom_type', true)),
        'filter_values' => boblift_product_filter_values($post->ID),
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
        'drawings' => $drawings,
        'brochure_source' => (string) get_post_meta($post->ID, '_brochure_source', true),
        'model_aliases' => (string) get_post_meta($post->ID, '_model_aliases', true),
        'features_series' => (string) get_post_meta($post->ID, '_features_series', true),
        'features_image' => (string) wp_get_attachment_image_url((int) get_post_meta($post->ID, '_features_image', true), 'large'),
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

/**
 * Versioned cache for the slow builds of the product listing: the product list, the compare tables, the previous-model
 * library and the image fits. Entries are kept per request and, on public pages, in transients named after what they
 * vary by (language, host, arguments). Each stores a signature, a version option bumped by any product, attachment,
 * term or site-option change plus the newest modification time of the theme code and seed data, so edits show on the
 * next load. Values are compressed and split into rows of at most 250 KB, so each row fits a memcached item.
 */
function boblift_products_code_version(): int
{
    static $code_version = null;
    if ($code_version === null) {
        $files = array_merge(
            glob(get_template_directory() . '/inc/*.{php,json}', GLOB_BRACE) ?: [],
            glob(get_template_directory() . '/inc/*/*.php') ?: [],
            glob(WP_PLUGIN_DIR . '/boblift-multilingual-sync/includes/*.php') ?: []
        );
        $code_version = $files ? max(array_map('filemtime', $files)) : 0;
    }
    return $code_version;
}

function boblift_cache_name(string $group, array $vary = []): string
{
    $language = function_exists('boblift_current_language_slug') ? boblift_current_language_slug() : '';
    $context = implode('|', [$group, $language, home_url('/'), (string) ($_SERVER['HTTP_HOST'] ?? ''), is_ssl() ? 'https' : 'http']);
    return 'boblift_c_' . md5($context . '|' . serialize($vary));
}

function boblift_cache_signature(): string
{
    return get_option('boblift_products_cache_version', '0') . '|' . boblift_products_code_version();
}

function boblift_products_cache_persistent(): bool
{
    // Only anonymous visitors share the stored entries: a logged-in request can render personalised content (a login/out
    // block, nonces, user shortcodes), so it builds for itself and keeps the result for this request only.
    return !is_admin() && !wp_doing_ajax() && !wp_doing_cron() && !(defined('REST_REQUEST') && REST_REQUEST)
        && !is_preview() && !is_customize_preview() && !is_user_logged_in()
        // Stored entries are zlib-compressed; without the extension the cache stays per request.
        && function_exists('gzcompress') && function_exists('gzuncompress');
}

/** The cached value, with $found telling a cached null or empty value from a miss. */
function boblift_cache_get(string $name, ?bool &$found = null): mixed
{
    $found = false;
    $signature = boblift_cache_signature();
    $memo = $GLOBALS['boblift_cache_memo'][$name] ?? null;
    if (is_array($memo) && $memo['signature'] === $signature) {
        $found = true;
        return $memo['data'];
    }
    if (!boblift_products_cache_persistent()) {
        return null;
    }
    $head = get_transient($name);
    if (!is_array($head) || ($head['signature'] ?? null) !== $signature) {
        return null;
    }
    $encoded = (string) ($head['data'] ?? '');
    for ($index = 1; $index < (int) ($head['chunks'] ?? 1); $index++) {
        $part = get_transient($name . '_' . (string) ($head['generation'] ?? '') . '_' . $index);
        if (!is_string($part)) {
            return null;
        }
        $encoded .= $part;
    }
    $raw = $encoded !== '' ? gzuncompress((string) base64_decode($encoded, true)) : false;
    if (!is_string($raw)) {
        return null;
    }
    $data = unserialize($raw);
    $GLOBALS['boblift_cache_memo'][$name] = ['signature' => $signature, 'data' => $data];
    $found = true;
    return $data;
}

function boblift_cache_set(string $name, mixed $data): void
{
    // A build after a change re-arms the version bump for the next change.
    $GLOBALS['boblift_products_cache_bumped'] = false;
    $signature = boblift_cache_signature();
    $GLOBALS['boblift_cache_memo'][$name] = ['signature' => $signature, 'data' => $data];
    if (!boblift_products_cache_persistent()) {
        return;
    }
    $serialized = serialize($data);
    // Some blocks leave side effects outside WP_Styles/WP_Scripts that a cache hit cannot replay: interactive blocks
    // (Interactivity API script modules and state) and generated block-support CSS (layout containers, element colours,
    // duotone filters kept by the Style Engine). A build that rendered any of them is kept for this request only.
    foreach (['data-wp-interactive', 'wp-container-', 'wp-elements-', 'wp-duotone-'] as $marker) {
        if (str_contains($serialized, $marker)) {
            return;
        }
    }
    $parts = str_split(base64_encode(gzcompress($serialized, 6)), 250000);
    // Trailing chunks are written under this build's own generation and the head that names it goes last, so concurrent
    // builds never mix their chunks and a reader never sees a head before its chunks.
    $generation = substr(md5(wp_generate_uuid4()), 0, 12);
    foreach (array_slice($parts, 1, null, true) as $index => $part) {
        if (!set_transient($name . '_' . $generation . '_' . $index, $part, 12 * HOUR_IN_SECONDS)) {
            return;
        }
    }
    set_transient($name, ['signature' => $signature, 'generation' => $generation, 'chunks' => count($parts), 'data' => $parts[0]], 12 * HOUR_IN_SECONDS);
}

/**
 * Styles and scripts a build enqueues, registers or extends as a side effect (the_content enqueues block styles and
 * blocks may register handles or add inline CSS/JS), so a cache hit reproduces them. The queues are emptied for the build
 * and restored after it, so a handle already queued by an earlier build on the same request is still recorded; the
 * restored order is the one an uncached request ends with. Registrations made during the build are recorded whole, and
 * inline code and localized data added to handles registered before it are recorded as the added parts.
 */
function boblift_cache_assets_mark(): array
{
    $mark = [];
    foreach (['styles' => wp_styles(), 'scripts' => wp_scripts()] as $kind => $dependencies) {
        $extras = [];
        foreach ($dependencies->registered as $handle => $item) {
            $extras[$handle] = $item->extra;
        }
        $mark[$kind] = ['queue' => array_values($dependencies->queue), 'args' => $dependencies->args, 'extras' => $extras];
        $dependencies->dequeue($mark[$kind]['queue']);
    }
    return $mark;
}

function boblift_cache_assets_since(array $mark): array
{
    $recorded = [];
    foreach (['styles' => wp_styles(), 'scripts' => wp_scripts()] as $kind => $dependencies) {
        $recorded[$kind] = array_values($dependencies->queue);
        $before = $mark[$kind]['extras'];
        $registered = [];
        $added = [];
        foreach ($dependencies->registered as $handle => $item) {
            if (!array_key_exists($handle, $before)) {
                $registered[$handle] = ['src' => $item->src, 'deps' => $item->deps, 'ver' => $item->ver, 'args' => $item->args, 'extra' => $item->extra];
                continue;
            }
            $old = (array) $before[$handle];
            $parts = [];
            foreach (['before', 'after'] as $position) {
                $now = (array) ($item->extra[$position] ?? []);
                $was = (array) ($old[$position] ?? []);
                if (count($now) > count($was)) {
                    $parts[$position] = array_slice($now, count($was));
                }
            }
            if (($item->extra['data'] ?? null) !== ($old['data'] ?? null)) {
                $parts['data'] = $item->extra['data'] ?? null;
            }
            if ($parts) {
                $added[$handle] = $parts;
            }
        }
        $build_args = $dependencies->args;
        $dependencies->dequeue($recorded[$kind]);
        $dependencies->enqueue($mark[$kind]['queue']);
        $dependencies->enqueue($recorded[$kind]);
        $dependencies->args = $build_args + $mark[$kind]['args'];
        $recorded[$kind . '_registered'] = $registered;
        $recorded[$kind . '_added'] = $added;
    }
    return $recorded;
}

function boblift_cache_replay_assets(array $cached): void
{
    foreach (['styles' => wp_styles(), 'scripts' => wp_scripts()] as $kind => $dependencies) {
        foreach ((array) ($cached[$kind . '_registered'] ?? []) as $handle => $item) {
            if (!isset($dependencies->registered[$handle])) {
                $dependencies->add($handle, $item['src'], $item['deps'], $item['ver'], $item['args']);
                $dependencies->registered[$handle]->extra = (array) $item['extra'];
            }
        }
        foreach ((array) ($cached[$kind . '_added'] ?? []) as $handle => $parts) {
            if (!isset($dependencies->registered[$handle])) {
                continue;
            }
            $extra = &$dependencies->registered[$handle]->extra;
            foreach (['before', 'after'] as $position) {
                if (!empty($parts[$position])) {
                    $extra[$position] = array_merge((array) ($extra[$position] ?? []), (array) $parts[$position]);
                }
            }
            if (array_key_exists('data', $parts)) {
                $extra['data'] = $parts['data'];
            }
            unset($extra);
        }
        array_map($kind === 'styles' ? 'wp_enqueue_style' : 'wp_enqueue_script', (array) ($cached[$kind] ?? []));
    }
}

function boblift_cached_data(string $group, array $vary, callable $build): mixed
{
    $name = boblift_cache_name($group, $vary);
    $cached = boblift_cache_get($name, $found);
    if ($found && is_array($cached) && array_key_exists('data', $cached)) {
        boblift_cache_replay_assets($cached);
        return $cached['data'];
    }
    $mark = boblift_cache_assets_mark();
    $data = $build();
    boblift_cache_set($name, ['data' => $data] + boblift_cache_assets_since($mark));
    return $data;
}

function boblift_cached_html(string $group, array $vary, callable $render): string
{
    $name = boblift_cache_name($group, $vary);
    $cached = boblift_cache_get($name, $found);
    if ($found && is_array($cached) && isset($cached['html'])) {
        boblift_cache_replay_assets($cached);
        return (string) $cached['html'];
    }
    $mark = boblift_cache_assets_mark();
    ob_start();
    $render();
    $html = (string) ob_get_clean();
    boblift_cache_set($name, ['html' => $html] + boblift_cache_assets_since($mark));
    return $html;
}

function boblift_bump_products_cache(): void
{
    // A product save updates dozens of meta rows: the first change bumps the version at once, the rest of the burst is
    // skipped, and the version is bumped again when the request ends, so nothing built mid-burst (here or on a
    // concurrent request) outlives the save.
    if (empty($GLOBALS['boblift_products_cache_final_bump'])) {
        $GLOBALS['boblift_products_cache_final_bump'] = true;
        add_action('shutdown', 'boblift_bump_products_cache_now', 0);
    }
    if (!empty($GLOBALS['boblift_products_cache_bumped'])) {
        return;
    }
    $GLOBALS['boblift_products_cache_bumped'] = true;
    boblift_bump_products_cache_now();
}

function boblift_bump_products_cache_now(): void
{
    $GLOBALS['boblift_cache_memo'] = [];
    // A fresh token, not an increment: two concurrent saves could compute the same next number, or one could write back
    // an older one, and make entries built mid-save valid again.
    update_option('boblift_products_cache_version', wp_generate_uuid4(), true);
}

function boblift_bump_products_cache_for_post(int $post_id): void
{
    // Synced patterns (wp_block) can be embedded in product content, so editing one changes the rendered products.
    if (in_array(get_post_type($post_id), ['product', 'attachment', 'wp_block'], true)) {
        boblift_bump_products_cache();
    }
}

/**
 * Rendered product content can embed dynamic blocks (latest posts, queries, page lists, synced patterns) that read other
 * content, so saving, deleting or trashing content of any kind retires the cache. Post types written by WordPress itself
 * or by visitors (revisions, oEmbed caches, changesets, privacy requests, form entries) are left out, so browsing and
 * enquiries never invalidate it.
 */
function boblift_bump_products_cache_for_content(int $post_id): void
{
    $type = (string) get_post_type($post_id);
    if ($type === '' || in_array($type, ['revision', 'oembed_cache', 'customize_changeset', 'user_request'], true) || str_starts_with($type, 'flamingo_')) {
        return;
    }
    boblift_bump_products_cache();
}

add_action('save_post', static fn($post_id) => boblift_bump_products_cache_for_content((int) $post_id));
add_action('deleted_post', static fn($post_id) => boblift_bump_products_cache_for_content((int) $post_id));
add_action('trashed_post', static fn($post_id) => boblift_bump_products_cache_for_content((int) $post_id));
add_action('untrashed_post', static fn($post_id) => boblift_bump_products_cache_for_content((int) $post_id));
add_action('add_attachment', 'boblift_bump_products_cache');
add_action('edit_attachment', 'boblift_bump_products_cache');
add_action('delete_attachment', 'boblift_bump_products_cache');
add_action('transition_post_status', static function ($new_status, $old_status, $post): void {
    if ($post instanceof WP_Post && $new_status !== $old_status) {
        boblift_bump_products_cache_for_content((int) $post->ID);
    }
}, 10, 3);
// Categories, languages and Polylang translation groups are all terms on the product.
add_action('set_object_terms', static fn($object_id) => boblift_bump_products_cache_for_post((int) $object_id));
// wp_remove_object_terms() fires only this one.
add_action('deleted_term_relationships', static fn($object_id) => boblift_bump_products_cache_for_post((int) $object_id));
foreach (['added_post_meta', 'updated_post_meta', 'deleted_post_meta'] as $boblift_meta_hook) {
    add_action($boblift_meta_hook, static function ($meta_ids, $object_id): void {
        boblift_bump_products_cache_for_post((int) $object_id);
    }, 10, 2);
}
foreach (['created_term', 'edited_term', 'delete_term'] as $boblift_term_hook) {
    add_action($boblift_term_hook, static function ($term_id, $tt_id, $taxonomy): void {
        if (in_array($taxonomy, ['product_category', 'language', 'post_translations', 'term_translations'], true)) {
            boblift_bump_products_cache();
        }
    }, 10, 3);
}
// Category titles, static-text translations, language settings and the permalink settings behind product links live in options.
foreach (['added_option', 'updated_option', 'deleted_option'] as $boblift_option_hook) {
    add_action($boblift_option_hook, static function ($option): void {
        $option = (string) $option;
        if ($option === 'boblift_products_cache_version' || str_starts_with($option, '_transient') || str_starts_with($option, '_site_transient')) {
            return;
        }
        if (preg_match('/^(boblift|polylang|pll_|theme_mods_)/', $option)
            || in_array($option, ['permalink_structure', 'category_base', 'tag_base', 'home', 'siteurl', 'show_on_front', 'page_on_front'], true)) {
            boblift_bump_products_cache();
        }
    });
}

/** Published products; previous models (inc/product-lifecycle.php) only when $include_legacy, as search and inquiry lists need them. */
function boblift_all_products(bool $include_legacy = false): array
{
    $products = boblift_cached_data('products', [$include_legacy], static fn(): array => boblift_build_all_products($include_legacy));
    // The uncached query also primed the posts, terms and meta that the cards and compare tables read next.
    $ids = array_filter(array_map(static fn(array $product): int => (int) ($product['id'] ?? 0), $products));
    if ($ids) {
        _prime_post_caches($ids, true, true);
    }
    return $products;
}

function boblift_build_all_products(bool $include_legacy): array
{
    $args = [
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'],
    ];
    $query = new WP_Query($include_legacy ? $args : boblift_without_legacy($args));
    if ($query->have_posts()) {
        return array_map('boblift_product_from_post', $query->posts);
    }
    // Every published product is a previous model: the current range is empty, not the seed catalog.
    if (!$include_legacy && (new WP_Query(['post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids', 'no_found_rows' => true]))->have_posts()) {
        return [];
    }
    return array_map(static function (array $product): array {
        $product['detail_url'] = boblift_html_url((string) $product['slug']);
        $product['inquiry_url'] = boblift_url('/contact.html?product=' . rawurlencode($product['title']) . '#inquiry');
        $product['filter_values'] ??= ['capacity' => max(0.0, (float) ($product['capacity_value'] ?? 0)), 'moment' => 0.0, 'weight' => 0.0];
        return boblift_apply_product_runtime_meta($product);
    }, boblift_products_seed());
}

function boblift_find_product(string $slug): ?array
{
    $post = get_page_by_path($slug, OBJECT, 'product');
    // Translations share the English slug, so the slug alone may find another language's product.
    if ($post instanceof WP_Post && function_exists('boblift_post_translation_for_current_language')) {
        $translation = boblift_post_translation_for_current_language($post);
        $post = $translation->post_status === 'publish' ? $translation : $post;
    }
    if ($post instanceof WP_Post && $post->post_status === 'publish') {
        return boblift_product_from_post($post);
    }
    return boblift_find_seed_item('products', $slug);
}

// Imported posts often use their own title plus ", BOB-LIFT" as the excerpt; a card is better without it.
function boblift_article_card_excerpt(string $excerpt, string $title): string
{
    $normalize = static function (string $text): string {
        $text = trim((string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text));
        return function_exists('mb_strtolower') ? mb_strtolower($text) : strtolower($text);
    };
    $excerpt_key = $normalize($excerpt);
    $title_key = $normalize($title);
    $length = static function (string $text): int {
        return function_exists('mb_strlen') ? mb_strlen($text) : (int) preg_match_all('/./us', $text);
    };
    if ($excerpt_key === '' || ($title_key !== '' && str_starts_with($excerpt_key, $title_key) && $length($excerpt_key) - $length($title_key) < 20)) {
        return '';
    }
    return $excerpt;
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
                'image' => boblift_post_thumbnail_url($post, boblift_asset('images/page-heroes/factory-floor.webp')),
                'image_is_url' => true,
                'excerpt' => boblift_article_card_excerpt(boblift_localize_product_text_fragments(get_the_excerpt($post)), boblift_localize_product_text_fragments(get_the_title($post))),
                'content' => post_password_required($post) ? '' : boblift_localize_product_text_fragments(apply_filters('the_content', $post->post_content)),
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
    // A password-protected case shows only its title and photo in lists: the text, facts and sections wait for the password.
    if (post_password_required($post)) {
        foreach (['summary', 'content', 'feedback_quote', 'feedback_media_url', 'product_name', 'application', 'country'] as $private_key) {
            $item[$private_key] = '';
        }
        $item['facts'] = [];
        $item['sections'] = [];
    }
    return boblift_normalize_case_item($item);
}

// One customer feedback entry. Country, configuration note and body are stored per language;
// images use a web size plus a thumbnail, videos carry their poster (the video attachment's featured image).
function boblift_feedback_item(WP_Post $post): ?array
{
    // A password-protected entry exposes nothing (text, media, URLs in lists) until WordPress has accepted the password.
    if (post_password_required($post)) {
        return null;
    }
    $language = function_exists('boblift_current_language_slug') ? boblift_current_language_slug() : '';
    $pick = static function (string $key) use ($post, $language): string {
        $values = json_decode((string) get_post_meta($post->ID, $key, true), true) ?: [];
        // Use the localized text only when it has real content; an empty ja/zh slot falls back to English.
        $local = trim((string) ($values[$language] ?? ''));
        return $local !== '' ? $local : trim((string) ($values['en'] ?? ''));
    };
    $media = [];
    foreach (array_filter(array_map('intval', explode(',', (string) get_post_meta($post->ID, '_feedback_media', true)))) as $id) {
        if (wp_attachment_is('video', $id)) {
            $src = (string) wp_get_attachment_url($id);
            $poster = (string) get_the_post_thumbnail_url($id, 'large');
            if ($src !== '') {
                $media[] = ['type' => 'video', 'src' => $src, 'poster' => $poster, 'thumb' => (string) get_the_post_thumbnail_url($id, 'medium')];
            }
        } elseif ($src = (string) wp_get_attachment_image_url($id, 'large')) {
            $media[] = ['type' => 'image', 'src' => $src, 'full' => (string) wp_get_attachment_image_url($id, 'full'), 'thumb' => (string) wp_get_attachment_image_url($id, 'medium')];
        }
    }
    // An article with a featured image but no photos or videos shows the featured image as its only media.
    $thumbnail_id = (int) get_post_thumbnail_id($post);
    if (!$media && $thumbnail_id && ($src = (string) wp_get_attachment_image_url($thumbnail_id, 'large'))) {
        $media[] = ['type' => 'image', 'src' => $src, 'full' => (string) wp_get_attachment_image_url($thumbnail_id, 'full'), 'thumb' => (string) wp_get_attachment_image_url($thumbnail_id, 'medium')];
    }
    $content = (string) $post->post_content;
    $legacy_body = $pick('_feedback_body');
    // An article told only in the editor (no gallery, no featured image) still counts; an entry with nothing to show does not.
    if (!$media && trim($content) === '' && $legacy_body === '') {
        return null;
    }
    // The featured image is the cover when set; otherwise the first image (or the first video's poster),
    // and for an article without a gallery the first image in its body.
    $cover = $thumbnail_id ? (string) wp_get_attachment_image_url($thumbnail_id, 'large') : '';
    if ($cover === '' && $media) {
        $cover = $media[0]['type'] === 'image' ? $media[0]['src'] : ($media[0]['poster'] ?? '');
    }
    if ($cover === '' && !$media) {
        if (preg_match('/wp-image-(\d+)/', $content, $match)) {
            $cover = (string) wp_get_attachment_image_url((int) $match[1], 'large');
        }
        if ($cover === '' && preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $match)) {
            $cover = $match[1];
        }
    }
    foreach ($media as $m) {
        if ($cover === '' && $m['type'] === 'image') {
            $cover = $m['src'];
        }
    }
    return [
        'id' => $post->ID,
        'url' => (string) get_permalink($post),
        'country' => $pick('_feedback_country'),
        'model' => trim((string) get_post_meta($post->ID, '_feedback_model', true)),
        'note' => $pick('_feedback_note'),
        'excerpt' => trim($post->post_excerpt),
        // Plain-text body per language from before the editor body; shown only while the editor body is empty.
        'legacy_body' => $legacy_body,
        'media' => $media,
        'cover' => $cover,
        'photos' => count(array_filter($media, static fn(array $m): bool => $m['type'] === 'image')),
        'videos' => count(array_filter($media, static fn(array $m): bool => $m['type'] === 'video')),
    ];
}

// "12 photos · 1 video", singular when the count is one.
function boblift_feedback_counts_text(array $item): string
{
    $ui = boblift_site_ui('casesPage');
    $part = static fn(int $n, string $one, string $many): string => $n ? $n . ' ' . ($n === 1 ? $one : $many) : '';
    return implode(' · ', array_filter([
        $part((int) $item['photos'], (string) ($ui['feedbackPhotoLabel'] ?? 'photo'), (string) ($ui['feedbackPhotosLabel'] ?? 'photos')),
        $part((int) $item['videos'], (string) ($ui['feedbackVideoLabel'] ?? 'video'), (string) ($ui['feedbackVideosLabel'] ?? 'videos')),
    ]));
}

// Customer feedback entries for the Cases page.
function boblift_feedback_items(array $query = []): array
{
    $posts = get_posts($query + ['post_type' => 'feedback', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'], 'lang' => '']);
    return array_values(array_filter(array_map('boblift_feedback_item', $posts)));
}

// Cases and feedback linked to a product (stored against the English product, shown in every language).
function boblift_product_english_id(int $product_id): int
{
    $english = function_exists('pll_get_post') ? (int) pll_get_post($product_id, 'en') : 0;
    return $english ?: $product_id;
}

/**
 * Numbers the product filters use, read from the English spec table (translations carry translated labels):
 * max lifting capacity in tonnes, max lifting moment in t·m (kN·m converted) and self weight in kg. 0 = not given.
 */
function boblift_product_filter_values(int $product_id): array
{
    static $cache = [];
    $english_id = boblift_product_english_id($product_id);
    if (isset($cache[$english_id])) {
        return $cache[$english_id];
    }
    $values = ['capacity' => 0.0, 'moment' => 0.0, 'weight' => 0.0];
    foreach (boblift_decode_meta(get_post_meta($english_id, '_specs', true), []) as $row) {
        $label = is_array($row) ? (string) ($row['label'] ?? '') : '';
        if (!preg_match('/^([\d.,]+)\s*(t·m|kN·m|kg|t)(?![\p{L}·])/u', trim((string) ($row['value'] ?? '')), $m)) {
            continue;
        }
        $number = (float) str_replace(',', '', $m[1]);
        if ($label === 'Max Lifting Capacity') {
            $values['capacity'] = $m[2] === 'kg' ? $number / 1000 : ($m[2] === 't' ? $number : 0.0);
        } elseif ($label === 'Max Lifting Moment') {
            $values['moment'] = $m[2] === 'kN·m' ? round($number / 9.81, 1) : ($m[2] === 't·m' ? $number : 0.0);
        } elseif ($label === 'Self Weight') {
            $values['weight'] = $m[2] === 'kg' ? $number : ($m[2] === 't' ? $number * 1000 : 0.0);
        }
    }
    if ($values['capacity'] <= 0) {
        // Products without a parseable "Max Lifting Capacity" row keep their numeric capacity so they stay filterable.
        $values['capacity'] = max(0.0, (float) get_post_meta($english_id, '_capacity_value', true));
    }
    return $cache[$english_id] = $values;
}

/** Every displayable feedback entry linked to the product: protected entries and entries without media are left out first. */
function boblift_product_feedback_all(int $product_id): array
{
    static $cache = [];
    $english_id = boblift_product_english_id($product_id);
    return $cache[$english_id] ??= boblift_feedback_items(['meta_key' => '_related_product', 'meta_value' => (string) $english_id]);
}

function boblift_product_feedback(int $product_id, int $limit = 8): array
{
    return array_slice(boblift_product_feedback_all($product_id), 0, $limit);
}

function boblift_product_feedback_count(int $product_id): int
{
    return count(boblift_product_feedback_all($product_id));
}

/**
 * Products a case or feedback entry is linked to (_related_product, stored against English product ids), in the page's
 * language. Only the exact model on the entry or its stored link count; no family-level guesses.
 */
function boblift_item_related_products(int $post_id, int $limit = 3): array
{
    $language = function_exists('boblift_current_language_slug') ? boblift_current_language_slug() : '';
    $source_id = function_exists('pll_get_post') ? ((int) pll_get_post($post_id, 'en') ?: $post_id) : $post_id;
    $products = [];
    // The exact model named on the entry wins over a stored link to a neighbouring model (older feedback links often point
    // to the family page, e.g. SQ5ZA3TJ for an SQ5ZA3T delivery).
    $model = trim((string) (get_post_meta($source_id, '_feedback_model', true) ?: get_post_meta($source_id, '_product_name', true)));
    $exact = $model !== '' ? get_posts(['post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => 1, 'lang' => 'en', 'fields' => 'ids', 'meta_key' => '_model', 'meta_value' => $model]) : [];
    $linked = $exact ? array_map('intval', $exact) : array_map('intval', get_post_meta($source_id, '_related_product'));
    foreach ($linked as $product_id) {
        $translated = $language !== '' && function_exists('pll_get_post') ? ((int) pll_get_post($product_id, $language) ?: $product_id) : $product_id;
        $product = get_post($translated);
        if ($product instanceof WP_Post && $product->post_type === 'product' && $product->post_status === 'publish' && !post_password_required($product)) {
            $products[$product->ID] = $product;
        }
    }
    return array_slice(array_values($products), 0, $limit);
}

// "Products in this case" cards for case and feedback pages: photo, name, model, two spec rows and a link to the product.
function boblift_render_item_products(array $products, string $heading): void
{
    if (!$products) {
        return;
    }
    $view_label = (string) (boblift_site_ui('caseDetail')['viewProductLabel'] ?? 'View product');
    ?>
    <section class="boblift-item-products" aria-label="<?php echo esc_attr($heading); ?>">
        <h2 class="text-2xl font-black tracking-[-0.015em] text-ink md:text-3xl"><?php echo esc_html($heading); ?></h2>
        <div class="mt-6 grid gap-4 <?php echo count($products) > 1 ? 'md:grid-cols-2' : ''; ?> <?php echo count($products) > 2 ? 'xl:grid-cols-3' : ''; ?>">
            <?php foreach ($products as $product) :
                $title = get_the_title($product);
                $model = (string) get_post_meta(boblift_product_english_id($product->ID), '_model', true);
                $model = $model !== '' && stripos($title, $model) === false ? $model : '';
                $specs = array_slice(array_values(array_filter(boblift_decode_meta(get_post_meta($product->ID, '_specs', true), []), static fn($row): bool => is_array($row) && trim((string) ($row['label'] ?? '')) !== '' && trim((string) ($row['value'] ?? '')) !== '')), 0, 2);
                $url = get_permalink($product);
                ?>
                <a class="group grid grid-cols-[7.5rem_minmax(0,1fr)] items-center gap-4 rounded border border-line bg-white p-3 transition hover:border-blue-300 sm:grid-cols-[9rem_minmax(0,1fr)]" href="<?php echo esc_url($url); ?>">
                    <span class="block aspect-[4/3] overflow-hidden rounded-sm bg-mist"><img class="h-full w-full object-contain p-1.5" src="<?php echo esc_url(boblift_post_thumbnail_url($product, boblift_asset('images/products/crane-accessories.png'))); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy" decoding="async"></span>
                    <span class="min-w-0">
                        <span class="line-clamp-2 block text-base font-black leading-snug text-ink transition group-hover:text-blue-700"><?php echo esc_html($title); ?></span>
                        <?php if ($model !== '') : ?><span class="mt-0.5 block text-xs font-bold text-slate-400"><?php echo esc_html($model); ?></span><?php endif; ?>
                        <?php if ($specs) : ?>
                            <span class="mt-2 grid gap-0.5 text-xs text-slate-500">
                                <?php foreach ($specs as $spec) : ?><span><?php echo esc_html((string) $spec['label']); ?> <b class="font-black text-ink"><?php echo esc_html((string) $spec['value']); ?></b></span><?php endforeach; ?>
                            </span>
                        <?php endif; ?>
                        <span class="mt-2 inline-flex items-center gap-1.5 text-sm font-black text-blue-700"><?php echo esc_html($view_label); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4 transition group-hover:translate-x-0.5 rtl:-scale-x-100'); ?></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}

function boblift_product_cases(int $product_id, int $limit = 3): array
{
    $posts = get_posts(['post_type' => 'case', 'post_status' => 'publish', 'posts_per_page' => -1, 'lang' => '', 'meta_key' => '_related_product', 'meta_value' => (string) boblift_product_english_id($product_id)]);
    $language = function_exists('boblift_current_language_slug') ? boblift_current_language_slug() : '';
    $items = [];
    foreach ($posts as $post) {
        $translated = $language !== '' && function_exists('pll_get_post') ? (int) pll_get_post($post->ID, $language) : $post->ID;
        $post = $translated ? get_post($translated) : null;
        if ($post instanceof WP_Post && $post->post_status === 'publish' && !isset($items[$post->ID]) && boblift_normalize_case_kind((string) get_post_meta($post->ID, '_case_kind', true)) === 'scene_case') {
            $items[$post->ID] = boblift_case_item_from_post($post);
        }
    }
    return array_slice(array_values($items), 0, $limit);
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
 * The old hero, banner and home gallery pictures were a 300px thumbnail, had a headline baked in or
 * showed a generic crane. Stored options that still point at them get a clean photo instead.
 */
function boblift_page_hero_image(string $image): string
{
    $replacements = [
        'images/factory-workshop.jpg' => 'images/page-heroes/factory-floor.webp',
        'images/global-network-blue.jpg' => 'images/page-heroes/sales-team.webp',
        'images/marine-scene.jpg' => 'images/page-heroes/marine-deck.webp',
        'images/cta-lift-photo.png' => 'images/page-heroes/site-dusk.webp',
        'images/telescopic-banner.jpg' => 'images/product-banners/truck-mounted-crane.jpg',
        'images/knuckle-banner.jpg' => 'images/product-banners/default.jpg',
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
function boblift_page_hero(array|string $hero, string $title = '', string $description = '', string $image = '', string $buttons = ''): void
{
    // Compatibility with the original positional call boblift_page_hero($label, $title, $description, $image, $buttons).
    if (is_string($hero)) {
        $hero = ['label' => $hero, 'title' => $title, 'text' => $description, 'image' => $image, 'buttons_html' => $buttons];
    }
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
            <div class="boblift-page-hero-media relative aspect-[11/5] w-full overflow-hidden bg-mist sm:aspect-[3/1] lg:absolute lg:inset-y-0 lg:end-0 lg:aspect-auto lg:w-[72%] lg:bg-white xl:w-[66%] 2xl:w-[62%] min-[1920px]:w-[56%]">
                <img class="absolute inset-0 h-full w-full object-cover" style="object-position: <?php echo esc_attr((string) ($hero['image_position'] ?? 'right 30%')); ?>" src="<?php echo esc_url(boblift_page_hero_image($image)); ?>" alt="<?php echo esc_attr((string) ($hero['image_alt'] ?? '')); ?>" loading="eager" decoding="async" fetchpriority="high" data-banner-image>
            </div>
        <?php endif; ?>
        <div class="boblift-page-hero-inner relative z-10 <?php echo esc_attr($inner_class); ?>">
            <p class="boblift-kicker boblift-page-hero-label mb-3 empty:hidden" data-banner-label><?php echo esc_html(trim((string) ($hero['label'] ?? ''))); ?></p>
            <h1 class="boblift-page-hero-title <?php echo $has_image ? 'max-w-3xl' : 'max-w-5xl'; ?> text-[clamp(2rem,3vw,3.75rem)] font-black leading-[1.02] tracking-[-0.02em] text-[#0B1B33]" data-banner-title><?php echo $title_html; ?></h1>
            <p class="boblift-page-hero-text mt-4 <?php echo $has_image ? 'max-w-xl' : 'max-w-[64rem]'; ?> text-sm font-normal leading-7 text-slate-600 empty:hidden md:text-base md:leading-8" data-banner-text><?php echo esc_html(trim((string) ($hero['text'] ?? ''))); ?></p>
            <?php if ($meta !== '') : ?><p class="mt-4 text-xs font-black uppercase tracking-[0.14em] text-slate-400"><?php echo esc_html($meta); ?></p><?php endif; ?>
            <?php if (trim((string) ($hero['buttons_html'] ?? '')) !== '') : ?>
                <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:items-center"><?php echo $hero['buttons_html']; // Already-escaped markup from the caller, as before. ?></div>
            <?php endif; ?>
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
    return '<span class="inline-flex items-center gap-2 rounded-sm bg-white/92 py-2 ps-3 pe-5 text-sm font-black leading-none shadow-card ring-1 ring-[#E5E7EB] backdrop-blur ' . esc_attr($config['wrapper'] . ' ' . $class) . '" data-status-badge="' . esc_attr($normalized) . '">' . boblift_icon($config['icon'], 'h-5 w-5 ' . $config['icon_class']) . esc_html(boblift_product_status_label($normalized)) . '</span>';
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
        $title_without_model = (string) preg_replace('/^[\s\-_–—|\/:]+|[\s\-_–—|\/:]+$/u', '', str_ireplace($product_model, '', $product_title));
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
    // Quick view shows up to six rows of the real spec table; the card's three-spec summary is the fallback.
    $quick_rows = array_values(array_filter(array_map(static fn($row): array => is_array($row) ? ['label' => (string) ($row['label'] ?? ''), 'value' => (string) ($row['value'] ?? '')] : [], $product['specs'] ?? []), static fn(array $row): bool => $row['label'] !== '' && $row['value'] !== ''));
    $quick_specs = esc_attr(wp_json_encode($quick_rows ? array_slice($quick_rows, 0, 6) : $specs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $product_ui = function_exists('boblift_site_ui') ? boblift_site_ui('productDetail') : [];
    $quote_list_ui = function_exists('boblift_site_ui') ? boblift_site_ui('quoteList') : [];
    $add_quote_label = (string) ($quote_list_ui['addLabel'] ?? 'Add to Quote List');
    $quick_view_label = (string) ($product_ui['quickViewLabel'] ?? 'Quick view');
    $view_details_label = (string) ($product_ui['viewDetailsLabel'] ?? 'View Details');
    $request_quote_label = (string) ($product_ui['requestQuoteLabel'] ?? 'Request Quote');
    // Cut-outs float on the card's backdrop; site photos show whole on a plain mat, never cropped.
    $card_image = boblift_product_image($product);
    $card_image_class = boblift_image_fit($card_image) === 'cutout'
        ? 'scale-[1.08] object-contain p-4 drop-shadow-[0_6px_8px_rgba(11,27,51,0.10)] group-hover:scale-[1.12] sm:p-5'
        : 'object-contain bg-[#EDF1F5]';
    ?>
    <article class="product-card group flex min-h-full cursor-pointer flex-col overflow-hidden rounded border border-[#E5E7EB] bg-white shadow-card transition duration-300 hover:-translate-y-1 hover:border-blue-200 <?php echo esc_attr($extra_class); ?>" data-product-id="<?php echo esc_attr($product_id); ?>" data-product-slug="<?php echo esc_attr((string) ($product['slug'] ?? '')); ?>" data-category="<?php echo esc_attr($product['category'] ?? ''); ?>" data-categories="<?php echo esc_attr(implode(',', $product_categories)); ?>" data-category-title="<?php echo esc_attr($product['category_title'] ?? 'BOB-LIFT'); ?>" data-capacity="<?php echo esc_attr((string) ($product['filter_values']['capacity'] ?? 0)); ?>" data-moment="<?php echo esc_attr((string) ($product['filter_values']['moment'] ?? 0)); ?>" data-weight="<?php echo esc_attr((string) ($product['filter_values']['weight'] ?? 0)); ?>" data-boom="<?php echo esc_attr($product['boom_type'] ?? ''); ?>" data-featured="<?php echo !empty($product['featured']) ? '1' : '0'; ?>" data-created="<?php echo esc_attr((string) ($product['created_at'] ?? '')); ?>" data-popularity="<?php echo esc_attr((string) ($product['popularity'] ?? 0)); ?>" data-title="<?php echo esc_attr($product['title']); ?>" data-image="<?php echo esc_url(boblift_product_image($product)); ?>" data-summary="<?php echo esc_attr($product['short_description'] ?? ''); ?>" data-inquiry-url="<?php echo esc_url($inquiry_url); ?>" data-detail-url="<?php echo esc_url($detail_url); ?>" data-specs="<?php echo $quick_specs; ?>">
        <div class="product-card-inner flex min-h-full flex-col">
            <div class="product-card-media relative h-64 overflow-hidden bg-[radial-gradient(circle_at_48%_34%,#ffffff_0%,#f8fafc_34%,#eef2f7_64%,#e2e8f0_100%)] sm:h-72 lg:h-64 xl:h-72">
                <span class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_50%_82%,rgba(15,23,42,0.13),transparent_44%)]"></span>
                <img class="absolute inset-0 z-10 h-full w-full transition duration-500 <?php echo esc_attr($card_image_class); ?>" src="<?php echo esc_url($card_image); ?>" alt="<?php echo esc_attr($product['title']); ?>" <?php echo $eager_image ? 'loading="eager" fetchpriority="high"' : 'loading="lazy"'; ?> decoding="async">
                <a class="absolute inset-0 z-[15]" href="<?php echo esc_url($detail_url); ?>" aria-label="<?php echo esc_attr($view_details_label . ' ' . $product['title']); ?>"><span class="sr-only"><?php echo esc_html($view_details_label . ' ' . $product['title']); ?></span></a>
                <div class="product-card-inline-actions absolute bottom-3 end-6 z-20 flex gap-2"><button class="inline-flex h-9 w-9 items-center justify-center rounded-sm border border-[#E5E7EB] bg-white text-[#0B1B33] transition hover:border-blue-300 hover:text-blue-700" type="button" data-product-favorite aria-label="<?php echo esc_attr($add_quote_label); ?>"><?php echo boblift_icon('heart', 'h-[18px] w-[18px]'); ?></button><button class="inline-flex h-9 w-9 items-center justify-center rounded-sm border border-[#E5E7EB] bg-white text-[#0B1B33] transition hover:border-blue-300 hover:text-blue-700" type="button" data-product-quick-view aria-label="<?php echo esc_attr($quick_view_label . ' ' . $product['title']); ?>"><?php echo boblift_icon('eye', 'h-[18px] w-[18px]'); ?></button></div>
            </div>
            <div class="product-card-body flex flex-1 flex-col p-6">
                <div class="product-card-head flex items-start justify-between gap-3">
                <a class="block min-w-0 flex-1" href="<?php echo esc_url($detail_url); ?>" aria-label="<?php echo esc_attr($view_details_label . ' ' . $product['title']); ?>">
                    <div class="product-card-meta mb-1.5 flex min-w-0 items-center gap-4 font-normal normal-case tracking-normal text-slate-600"><span class="line-clamp-2 block min-w-0 max-w-full break-words text-[14px] leading-[1.35]"><?php echo esc_html($card_eyebrow); ?></span></div>
                    <h2 class="product-card-title font-black <?php echo $product_model !== '' ? 'product-card-model text-[clamp(28px,1.8vw,34px)] uppercase leading-none tracking-[0.02em] text-[#0B3F7D]' : 'text-xl leading-tight tracking-[-0.01em] text-[#0B1B33]'; ?>"><?php echo esc_html($card_heading); ?></h2>
                    <p class="product-card-summary mt-3 hidden max-w-3xl text-sm font-normal leading-6 text-slate-600 lg:text-base lg:leading-7"><?php echo esc_html($product['short_description'] ?? ''); ?></p>
                </a>
                </div>
                <div class="product-card-footer mt-auto grid grid-cols-2 gap-3 pt-5">
                    <div class="product-card-list-actions hidden gap-3"><button class="inline-flex h-11 w-11 items-center justify-center rounded-sm border border-[#E5E7EB] bg-white text-[#0B1B33] transition hover:-translate-y-0.5 hover:text-blue-700" type="button" data-product-favorite aria-label="<?php echo esc_attr($add_quote_label); ?>"><?php echo boblift_icon('heart', 'h-5 w-5'); ?></button><button class="inline-flex h-11 w-11 items-center justify-center rounded-sm border border-[#E5E7EB] bg-white text-[#0B1B33] transition hover:-translate-y-0.5 hover:text-blue-700" type="button" data-product-quick-view aria-label="<?php echo esc_attr($quick_view_label . ' ' . $product['title']); ?>"><?php echo boblift_icon('eye', 'h-5 w-5'); ?></button></div>
                    <div class="product-card-ctas contents"><button class="inline-flex min-h-12 items-center justify-center gap-2 text-center leading-tight rounded-sm bg-[#0B1B33] px-4 text-[13px] font-black text-white shadow-card transition hover:-translate-y-0.5 hover:bg-[#0B3F7D]" type="button" data-inquiry-modal-open data-inquiry-source="产品列表卡片" data-inquiry-product="<?php echo esc_attr($product['title']); ?>" data-inquiry-product-slug="<?php echo esc_attr((string) ($product['slug'] ?? '')); ?>"><?php echo boblift_icon('file-text', 'h-4 w-4'); ?><span class="hidden sm:inline"><?php echo esc_html($request_quote_label); ?></span><span class="sm:hidden"><?php echo esc_html($quote_list_ui['quoteShortLabel'] ?? 'Quote'); ?></span></button><a class="inline-flex min-h-12 items-center justify-center gap-2 text-center leading-tight rounded-sm border border-[#0B1B33]/40 bg-white px-4 text-[13px] font-black text-[#0B1B33] transition hover:-translate-y-0.5 hover:border-blue-500 hover:text-blue-700" href="<?php echo esc_url($detail_url); ?>"><span class="hidden sm:inline"><?php echo esc_html($view_details_label); ?></span><span class="sm:hidden"><?php echo esc_html($product_ui['viewShortLabel'] ?? 'View'); ?></span> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a></div>
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
                    <p class="mt-5 max-w-[680px] text-sm font-normal leading-7 text-slate-600 md:text-base md:leading-8"><?php echo esc_html((string) ($visits['text'] ?? '')); ?></p>
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

// Site search. Products are ranked in PHP so a model typed without its dash or spaces ("sq5za2")
// still finds SQ5ZA2; news and cases use the normal WordPress keyword search.
function boblift_search_key(string $text): string
{
    $text = (string) preg_replace('/[^\p{L}\p{N}]+/u', '', $text);
    return function_exists('mb_strtolower') ? mb_strtolower($text) : strtolower($text);
}

function boblift_search_products(string $query): array
{
    $query = trim($query);
    $key = boblift_search_key($query);
    if ($key === '') {
        return [];
    }
    $words = array_values(array_filter(array_map('boblift_search_key', preg_split('/\s+/u', $query) ?: [])));
    $scored = [];
    foreach (boblift_all_products(true) as $index => $product) {
        $models = array_filter(array_map('boblift_search_key', array_merge([(string) ($product['model'] ?? '')], preg_split('/[,，、\/]+/u', (string) ($product['model_aliases'] ?? '')) ?: [])));
        $score = 0;
        foreach ($models as $model) {
            if ($model === $key) {
                $score = max($score, 100);
            } elseif (str_starts_with($model, $key)) {
                $score = max($score, 80);
            } elseif (mb_strlen($key) >= 2 && str_contains($model, $key)) {
                $score = max($score, 60);
            }
        }
        if ($score === 0) {
            $title = boblift_search_key((string) ($product['title'] ?? ''));
            $haystack = boblift_search_key(implode(' ', [
                (string) ($product['title'] ?? ''),
                (string) ($product['category_title'] ?? ''),
                (string) ($product['short_description'] ?? ''),
                boblift_boom_label((string) ($product['boom_type'] ?? '')),
            ]));
            if (str_contains($title, $key)) {
                $score = 40;
            } elseif ($words && count(array_filter($words, static fn(string $word): bool => str_contains($haystack, $word))) === count($words)) {
                $score = 20;
            }
        }
        if ($score > 0) {
            $scored[] = ['score' => $score, 'index' => $index, 'product' => $product];
        }
    }
    usort($scored, static fn(array $a, array $b): int => ($b['score'] <=> $a['score']) ?: ($a['index'] <=> $b['index']));
    return array_column($scored, 'product');
}

function boblift_search_posts(string $query, string $post_type, int $per_page, int $page = 1): WP_Query
{
    return new WP_Query([
        's' => $query,
        'post_type' => $post_type,
        'post_status' => 'publish',
        'posts_per_page' => $per_page,
        'paged' => max(1, $page),
        'ignore_sticky_posts' => true,
    ]);
}

/**
 * Line icons for the product families in the Products menu: a few strokes each on a 24px grid, drawn
 * with the same 1.75 stroke as the other interface icons so they stay legible at 20–24px.
 */
function boblift_product_family_icon(string $icon, string $class = 'h-6 w-6'): string
{
    $wheels = '<circle cx="6.5" cy="18" r="1.75"/><circle cx="17" cy="18" r="1.75"/>';
    $shapes = [
        // Truck with the crane column behind the cab and the boom reaching back over the bed.
        'truck-crane' => '<path d="M2 16h11"/><path d="M13 16v-5h4l3 3v2h-1"/><path d="M11 16v-6L4 5"/><path d="M4 5v4"/>' . $wheels,
        // Vessel deck with a slewing crane.
        'marine-crane' => '<path d="M2 16h20l-2.5 4h-15z"/><path d="M8 16V8"/><path d="M8 8l9-4"/><path d="M17 4v6"/>',
        // Tracked carrier with a folded boom (spider and timber cranes).
        'other-crane' => '<rect x="3" y="15" width="16" height="4" rx="2"/><path d="M7 15v-3h5v3"/><path d="M10 12l3-6 6 2"/><path d="M19 8v4"/>',
        // Forklift with a fly jib on the mast.
        'forklift-jib' => '<path d="M9 3v17h6"/><path d="M9 6h11"/><path d="M19 6v5"/><path d="M9 12H4l-1 4v2h2"/><circle cx="6.5" cy="19" r="1.5"/>',
        // Truck with a boom and a work basket.
        'aerial-platform' => '<path d="M2 16h11"/><path d="M13 16v-5h4l3 3v2h-1"/><path d="M10 16v-4L5 6"/><path d="M2 3h6v3H2z"/>' . $wheels,
        // Box truck with the lift platform lowered at the rear.
        'tail-lift' => '<path d="M7 5h11v11H7z"/><path d="M18 9h2.5L22 12v4h-4"/><path d="M1 20h7"/><path d="M4 16v4"/><circle cx="11" cy="18" r="1.75"/><circle cx="19" cy="18" r="1.75"/>',
    ];
    $body = $shapes[$icon] ?? $shapes['truck-crane'];
    return '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $body . '</svg>';
}

/**
 * Columns the last item of a CSS grid should span so its row ends flush, given each item's span in auto-placement order.
 */
function boblift_grid_last_item_span(array $spans, int $columns): int
{
    $column = 0;
    $last_start = 0;
    $last_span = 1;
    foreach ($spans as $span) {
        $span = max(1, min($columns, (int) $span));
        if ($column + $span > $columns) {
            $column = 0;
        }
        $last_start = $column;
        $last_span = $span;
        $column = ($column + $span) % $columns;
    }
    return $columns - $last_start;
}
