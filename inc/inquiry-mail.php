<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The email an inquiry sends to the sales team: the loader site's design C "nameplate" card, so the three BOB-LIFT sites
 * look the same in the inbox. It is an HTML table with inline styles, the only layout mail programs agree on.
 */

const BOBLIFT_INQUIRY_DEFAULT_RECIPIENT = 'info@boblift.com';

/** Where inquiry notifications go: the sales email in 联系方式 (English options), else info@boblift.com. Never admin_email. */
function boblift_inquiry_recipient(): string
{
    $contact = get_option('boblift_theme_options', []);
    $email = is_array($contact) ? sanitize_email((string) ($contact['contact']['email'] ?? '')) : '';
    return (string) apply_filters('boblift_inquiry_recipient', is_email($email) ? $email : BOBLIFT_INQUIRY_DEFAULT_RECIPIENT);
}

/** Who the email comes from: the site title, at the site's own domain so receiving servers accept it. */
function boblift_inquiry_mail_from_header(): string
{
    $host = preg_replace('/^www\./i', '', (string) wp_parse_url(home_url(), PHP_URL_HOST));
    $name = trim(wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES));
    $name = $name === '' || stripos($name, 'zealous') !== false ? 'BOB-LIFT' : $name;
    return 'From: ' . str_replace(['"', "\r", "\n"], '', $name) . ' <wordpress@' . $host . '>';
}

/** The logo as an absolute address: mail programs have no page to resolve a relative one against. */
function boblift_inquiry_mail_logo(): string
{
    $src = boblift_asset('images/bob-lift-logo.png');
    if (str_starts_with($src, '//')) {
        $src = 'https:' . $src;
    } elseif (str_starts_with($src, '/')) {
        $src = untrailingslashit(home_url()) . $src;
    }
    return '<img src="' . esc_url($src) . '" width="120" alt="BOB-LIFT" style="display:block;width:120px;height:auto;border:0;">';
}

/**
 * Which ad brought the inquiry (sent by assets/js/theme.js in hidden boblift_attr_* fields, only when the visitor agreed to
 * advertising cookies), with the longest value each may have. Saved with the inquiry as attr_<name> and shown in the mail.
 */
const BOBLIFT_INQUIRY_ATTRIBUTION = [
    'gclid' => 255, 'gbraid' => 255, 'wbraid' => 255, 'gad_source' => 120, 'gad_campaignid' => 120,
    'utm_source' => 120, 'utm_medium' => 120, 'utm_campaign' => 120, 'utm_term' => 120, 'utm_content' => 120,
    'landing' => 300, 'first_seen' => 40, 'referrer' => 120,
];

/** Chinese labels of the attribution fields, for the mail and the admin inquiry box. */
const BOBLIFT_INQUIRY_ATTRIBUTION_LABELS = [
    'gclid' => 'gclid', 'gbraid' => 'gbraid', 'wbraid' => 'wbraid', 'gad_source' => 'gad_source', 'gad_campaignid' => '广告系列编号',
    'utm_source' => 'UTM 来源', 'utm_medium' => 'UTM 媒介', 'utm_campaign' => 'UTM 系列', 'utm_term' => 'UTM 关键词', 'utm_content' => 'UTM 内容',
    'landing' => '落地页', 'first_seen' => '落地时间', 'referrer' => '进站前网站',
];

/** One posted value as stored: one printable line, no markup, within its length; a click ID that is cut matches nothing, so it is dropped. */
function boblift_inquiry_attribution_clean(string $name, $value): string
{
    if (!isset(BOBLIFT_INQUIRY_ATTRIBUTION[$name]) || !is_scalar($value)) {
        return '';
    }
    $text = trim((string) preg_replace('/\s+/u', ' ', (string) preg_replace('/[\x00-\x1f\x7f<>]/u', ' ', (string) $value)));
    if (in_array($name, ['gclid', 'gbraid', 'wbraid'], true)) {
        return strlen($text) <= BOBLIFT_INQUIRY_ATTRIBUTION[$name] && preg_match('/^[A-Za-z0-9_.~=-]+$/', $text) ? $text : '';
    }
    if ($name === 'referrer') {
        return boblift_inquiry_referrer_host($text);
    }
    return mb_substr($text, 0, BOBLIFT_INQUIRY_ATTRIBUTION[$name]);
}

/** A referring site as kept: its host name only (no path, no query), lower case, without www.; empty when it is not one. */
function boblift_inquiry_referrer_host(string $value): string
{
    $host = strtolower(trim($value));
    $host = (string) preg_replace('/^www\./', '', $host);
    return strlen($host) <= 120 && preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]*[a-z0-9])?)+$/', $host) ? $host : '';
}

/**
 * How the visitor reached the site, for the sales team: [channel, detail]. A Google Ads click ID (kept since landing, or on
 * the page the form was sent from) wins, then campaign tags, then the site the visitor came from (kept since landing when
 * advertising was agreed to, else only the referrer of the page the form was sent from, which needs no storage).
 */
function boblift_inquiry_channel(array $fields): array
{
    $source = boblift_inquiry_source_page($fields);
    $value = static fn(string $name): string => (string) ($fields['attr_' . $name] ?? $source['params'][$name] ?? '');
    if ($value('gclid') !== '' || $value('gbraid') !== '' || $value('wbraid') !== '' || $value('gad_source') !== '') {
        $detail = $value('utm_term') !== '' ? '关键词 ' . $value('utm_term') : ($value('gad_campaignid') !== '' ? '广告系列 ' . $value('gad_campaignid') : '');
        return ['Google Ads 广告', $detail];
    }
    if ($value('utm_source') !== '') {
        return ['营销链接', trim($value('utm_source') . ($value('utm_medium') !== '' ? ' / ' . $value('utm_medium') : ''))];
    }
    $host = (string) (($fields['attr_referrer'] ?? '') !== '' ? $fields['attr_referrer'] : ($fields['page_referrer'] ?? ''));
    if ($host !== '') {
        $named = static function (array $list) use ($host): string {
            foreach ($list as $pattern => $name) {
                if (preg_match('/(?:^|\.)' . $pattern . '$/', $host)) {
                    return $name;
                }
            }
            return '';
        };
        // AI assistants first: gemini.google.com is not Google Search.
        $ai = $named(['chatgpt\.com' => 'ChatGPT', 'openai\.com' => 'ChatGPT', 'perplexity\.ai' => 'Perplexity', 'gemini\.google\.com' => 'Gemini', 'copilot\.microsoft\.com' => 'Copilot', 'claude\.ai' => 'Claude', 'deepseek\.com' => 'DeepSeek']);
        if ($ai !== '') {
            return ['AI 助手推荐', $ai];
        }
        $ads = $named(['googleadservices\.com' => 'Google Ads', 'doubleclick\.net' => 'Google Ads', 'syndicatedsearch\.goog' => 'Google 搜索网络合作伙伴']);
        if ($ads !== '') {
            return ['Google Ads 广告', $ads . '（没有点击编号）'];
        }
        $search = $named(['google\.[a-z.]+' => 'Google', 'bing\.com' => 'Bing', 'yahoo\.[a-z.]+' => 'Yahoo', 'yandex\.[a-z.]+' => 'Yandex', 'ya\.ru' => 'Yandex', 'baidu\.com' => '百度', 'duckduckgo\.com' => 'DuckDuckGo', 'ecosia\.org' => 'Ecosia', 'naver\.com' => 'Naver', 'seznam\.cz' => 'Seznam', 'sogou\.com' => '搜狗', 'so\.com' => '360 搜索', 'brave\.com' => 'Brave']);
        if ($search !== '') {
            return ['自然搜索', $search];
        }
        $social = $named(['facebook\.com' => 'Facebook', 'fb\.com' => 'Facebook', 'instagram\.com' => 'Instagram', 'linkedin\.com' => 'LinkedIn', 'lnkd\.in' => 'LinkedIn', 'youtube\.com' => 'YouTube', 'youtu\.be' => 'YouTube', 'x\.com' => 'X', 't\.co' => 'X', 'twitter\.com' => 'X', 'whatsapp\.com' => 'WhatsApp', 'tiktok\.com' => 'TikTok', 'vk\.com' => 'VK', 'pinterest\.[a-z.]+' => 'Pinterest', 'reddit\.com' => 'Reddit', 't\.me' => 'Telegram']);
        if ($social !== '') {
            return ['社交媒体', $social];
        }
        return ['外部网站', $host];
    }
    if (($fields['ad_consent'] ?? '') === 'false') {
        return ['来源未知', '访客没同意广告 Cookie，进站来源没有记录'];
    }
    return ['直接访问', '直接输入网址或收藏夹，也可能是来源被浏览器隐藏'];
}

/**
 * The visitor's device from the browser's User-Agent, e.g. "手机 · iPhone · Safari": what kind, which system, which browser
 * or app. iPadOS Safari calls itself a Mac; the form says whether the screen takes touch, which tells the two apart.
 */
function boblift_inquiry_device(string $agent, bool $touch = false): string
{
    if ($agent === '') {
        return '';
    }
    $has = static fn(string $pattern): bool => (bool) preg_match('/' . $pattern . '/i', $agent);
    $ipad = $has('iPad') || ($has('Macintosh') && $touch);
    $system = match (true) {
        $ipad => 'iPad',
        $has('iPhone|iPod') => 'iPhone',
        $has('HarmonyOS|OpenHarmony') => '鸿蒙',
        $has('Android') => 'Android',
        $has('Windows') => 'Windows',
        $has('Macintosh|Mac OS X') => 'Mac',
        $has('CrOS') => 'ChromeOS',
        $has('Linux') => 'Linux',
        default => '',
    };
    $kind = match (true) {
        $ipad, $has('Tablet') || ($has('Android') && !$has('Mobile')) => '平板',
        $has('Mobi|iPhone|iPod|Android|HarmonyOS') => '手机',
        $has('bot|crawl|spider') => '程序',
        default => '电脑',
    };
    $browser = match (true) {
        $has('MicroMessenger') => '微信',
        $has('WhatsApp') => 'WhatsApp',
        $has('FBAN|FBAV|FB_IAB') => 'Facebook 内置浏览器',
        $has('Instagram') => 'Instagram 内置浏览器',
        $has('LinkedInApp') => 'LinkedIn 内置浏览器',
        $has('Edg(?:e|A|iOS)?\/') => 'Edge',
        $has('SamsungBrowser') => '三星浏览器',
        $has('OPR\/|Opera') => 'Opera',
        $has('YaBrowser') => 'Yandex 浏览器',
        $has('Firefox|FxiOS') => 'Firefox',
        $has('Chrome|CriOS') => 'Chrome',
        $has('Safari') => 'Safari',
        default => '',
    };
    return implode(' · ', array_filter([$kind, $system, $browser], 'strlen'));
}

/** The attribution fields of the current POST, without the prefix. */
function boblift_inquiry_attribution_from_post(): array
{
    // Nothing is accepted unless a Google Ads ID puts Advertising in the cookie banner, even from a page cached earlier.
    if (!function_exists('boblift_consent_categories') || !in_array('advertising', boblift_consent_categories(), true)) {
        return [];
    }
    $found = [];
    foreach (array_keys(BOBLIFT_INQUIRY_ATTRIBUTION) as $name) {
        $value = boblift_inquiry_attribution_clean($name, wp_unslash($_POST['boblift_attr_' . $name] ?? ''));
        if ($value !== '') {
            $found[$name] = $value;
        }
    }
    return $found;
}

/** The grey block in the mail that says how the visitor arrived; empty when nothing was sent. */
function boblift_inquiry_attribution_block(array $fields): string
{
    $rows = '';
    foreach (BOBLIFT_INQUIRY_ATTRIBUTION_LABELS as $name => $label) {
        $value = (string) ($fields['attr_' . $name] ?? '');
        if ($value !== '') {
            $rows .= '<div style="padding:3px 0;font-size:13px;line-height:1.6;color:#334155;word-break:break-all;"><span style="color:#64748b;">' . esc_html($label) . '</span> ' . esc_html($value) . '</div>';
        }
    }
    if ($rows === '') {
        return '';
    }
    $ads = ($fields['attr_gclid'] ?? '') !== '' || ($fields['attr_gbraid'] ?? '') !== '' || ($fields['attr_wbraid'] ?? '') !== '' || ($fields['attr_gad_source'] ?? '') !== '';
    $tagged = $ads || array_filter(array_keys(BOBLIFT_INQUIRY_ATTRIBUTION), static fn(string $name): bool => str_starts_with($name, 'utm_') && ($fields['attr_' . $name] ?? '') !== '');
    // Only a site the visitor came from was kept: that is a visit record, not an ad.
    $kind = '<div style="padding:3px 0;font-size:13px;line-height:1.6;color:#334155;"><span style="color:#64748b;">' . ($tagged ? '广告来源' : '进站记录') . '</span> <b>' . ($ads ? 'Google Ads 点击' : ($tagged ? '营销标记' : '首次进站')) . '</b></div>';
    return '<tr><td style="padding:20px 36px 0;"><table width="100%" cellspacing="0" cellpadding="0" style="background:#f5f7fa;border-radius:4px;"><tr><td style="padding:14px 18px;">' . $kind . $rows . '</td></tr></table></td></tr>';
}

/**
 * Cloudflare's published proxy ranges (https://www.cloudflare.com/ips/). CF-Connecting-IP is believed only from these:
 * anyone else could send the header to pose as another visitor and get round the rate limit.
 */
const BOBLIFT_CLOUDFLARE_RANGES = [
    '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18', '108.162.192.0/18',
    '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
    '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
    '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32', '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
];

function boblift_ip_in_range(string $ip, string $range): bool
{
    [$subnet, $bits] = explode('/', $range);
    $ip_bin = @inet_pton($ip);
    $subnet_bin = @inet_pton($subnet);
    if ($ip_bin === false || $subnet_bin === false || strlen($ip_bin) !== strlen($subnet_bin)) {
        return false;
    }
    $bits = (int) $bits;
    $bytes = intdiv($bits, 8);
    if (strncmp($ip_bin, $subnet_bin, $bytes) !== 0) {
        return false;
    }
    $rest = $bits % 8;
    return $rest === 0 || ((ord($ip_bin[$bytes]) ^ ord($subnet_bin[$bytes])) & (0xFF << (8 - $rest)) & 0xFF) === 0;
}

/**
 * The visitor's IP address: CF-Connecting-IP when the request really came through Cloudflare, else REMOTE_ADDR (on
 * WordPress.com that is already the visitor's own address). A host behind another reverse proxy, which leaves its own
 * address in REMOTE_ADDR, resolves the visitor through the boblift_inquiry_client_ip filter, which receives the peer
 * address and should read a forwarded header only from proxies it trusts.
 */
function boblift_inquiry_client_ip(): string
{
    return (string) apply_filters('boblift_inquiry_client_ip', boblift_inquiry_resolve_client_ip(), sanitize_text_field((string) ($_SERVER['REMOTE_ADDR'] ?? '')));
}

function boblift_inquiry_resolve_client_ip(): string
{
    $peer = sanitize_text_field((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    $cloudflare = trim((string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''));
    if ($cloudflare !== '' && filter_var($cloudflare, FILTER_VALIDATE_IP)) {
        foreach (BOBLIFT_CLOUDFLARE_RANGES as $range) {
            if (boblift_ip_in_range($peer, $range)) {
                return $cloudflare;
            }
        }
    }
    return $peer;
}

/** The country the sender's IP address points to, in Chinese (e.g. 加拿大); empty when it cannot be told. */
function boblift_inquiry_ip_country(string $ip): string
{
    $code = function_exists('boblift_country_code_from_request') ? boblift_country_code_from_request() : '';
    if ($code === '' && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        $key = 'boblift_ip_country_' . md5($ip);
        $code = get_transient($key);
        if ($code === false) {
            $response = wp_remote_get('https://ipinfo.io/' . rawurlencode($ip) . '/country', ['timeout' => 3]);
            $text = strtoupper(trim((string) wp_remote_retrieve_body($response)));
            $code = !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200 && preg_match('/^[A-Z]{2}$/', $text) ? $text : '';
            set_transient($key, $code, $code !== '' ? WEEK_IN_SECONDS : HOUR_IN_SECONDS);
        }
    }
    if ($code === '') {
        return '';
    }
    static $names = null;
    $names ??= require __DIR__ . '/country-names-zh.php';
    return $names[$code] ?? $code;
}

/** The products named in the inquiry: model, type, photo and link of each chosen product, else the typed product name. */
function boblift_inquiry_mail_products(array $fields): array
{
    // Each entry is a product found on the site, or a name that matches none, kept as typed so nothing the customer asked
    // about is dropped from the mail.
    $entries = [];
    foreach (array_filter(array_map('trim', explode(',', (string) ($fields['product_slug'] ?? '')))) as $slug) {
        $post = get_page_by_path(sanitize_title($slug), OBJECT, 'product');
        if ($post instanceof WP_Post) {
            $entries[] = $post;
        }
    }
    // Some buttons send only the product's name (in the page's language). Each name the form joined with "; " is looked up
    // as a product title in any language, then by the model in it (SQ12ZA6, SQ12ZA6+1, SQZ800 …).
    if (!$entries) {
        foreach (array_filter(array_map('trim', explode(';', (string) ($fields['product'] ?? '')))) as $name) {
            $entries[] = boblift_inquiry_product_by_name($name) ?? $name;
        }
    }
    $products = [];
    $seen = [];
    foreach ($entries as $post) {
        if (!$post instanceof WP_Post) {
            $products[] = ['title' => (string) $post, 'model' => '', 'type' => '', 'url' => '', 'image' => ''];
            continue;
        }
        $english_id = function_exists('boblift_product_english_id') ? boblift_product_english_id($post->ID) : $post->ID;
        if (isset($seen[$english_id])) {
            continue;
        }
        $seen[$english_id] = true;
        $title = get_the_title($english_id);
        $model = function_exists('boblift_clean_product_model') ? boblift_clean_product_model((string) get_post_meta($english_id, '_model', true), $title) : (string) get_post_meta($english_id, '_model', true);
        // 'lang' => '' keeps Polylang from limiting the terms to the language of the page the inquiry came from.
        $terms = wp_get_object_terms($english_id, 'product_category', ['fields' => 'names', 'lang' => '']);
        $products[] = [
            'title' => $title,
            'model' => $model,
            'type' => is_array($terms) && $terms ? (string) $terms[0] : '',
            'url' => (string) get_permalink($english_id),
            'image' => boblift_inquiry_mail_product_image($english_id, $post->ID),
        ];
    }
    if (!$products && trim((string) ($fields['product'] ?? '')) !== '') {
        $products[] = ['title' => trim((string) $fields['product']), 'model' => '', 'type' => '', 'url' => '', 'image' => ''];
    }
    return $products;
}

/** A published product whose title (in any language) is the given name, else whose model the name contains. */
function boblift_inquiry_product_by_name(string $name): ?WP_Post
{
    if ($name === '') {
        return null;
    }
    $args = ['post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => 1, 'no_found_rows' => true, 'lang' => '', 'suppress_filters' => false];
    $found = get_posts($args + ['title' => $name]);
    if ($found) {
        return $found[0];
    }
    // The longest model-like word first: "SQ12ZA6" rather than "12"; suffixes (SQ12ZA6TJF) and an extension such as "+1"
    // are part of the model.
    preg_match_all('/\b[A-Z]{2,4}\d+(?:\.\d+)?[A-Z]*\d*[A-Z]*(?:\+\d+)?(?![\w+])/', strtoupper($name), $matches);
    $models = array_unique($matches[0]);
    usort($models, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));
    foreach ($models as $model) {
        $found = get_posts($args + ['meta_query' => [['key' => '_model', 'value' => $model]]]);
        if ($found) {
            return $found[0];
        }
    }
    return null;
}

/**
 * The product's photo for the mail, as an absolute address: the English product's featured image, else the translation's,
 * else the first gallery photo. Mail programs cannot resolve a relative address.
 */
function boblift_inquiry_mail_product_image(int $english_id, int $post_id): string
{
    $image = (string) (get_the_post_thumbnail_url($english_id, 'thumbnail') ?: get_the_post_thumbnail_url($post_id, 'thumbnail') ?: '');
    if ($image === '') {
        $gallery = function_exists('boblift_decode_meta') ? boblift_decode_meta(get_post_meta($english_id, '_gallery', true), []) : [];
        $first = is_array($gallery) ? reset($gallery) : null;
        $image = is_array($first) ? (string) ($first['url'] ?? $first['image'] ?? '') : (is_string($first) ? $first : '');
    }
    if (str_starts_with($image, '//')) {
        $image = 'https:' . $image;
    } elseif (str_starts_with($image, '/')) {
        // Against the site's origin only: a site in a subdirectory already has that directory in the path.
        $home = wp_parse_url(home_url());
        $image = is_array($home) && !empty($home['host']) ? ($home['scheme'] ?? 'https') . '://' . $home['host'] . (isset($home['port']) ? ':' . $home['port'] : '') . $image : '';
    }
    return preg_match('#^https?://#i', $image) ? $image : '';
}

function boblift_inquiry_mail_product_card(array $product): string
{
    $tag = static fn(string $label, string $text): string => '<span style="display:inline-block;margin:0 6px 6px 0;padding:4px 10px;border-radius:4px;background:#f1f4f9;font-size:13px;color:#334155;white-space:nowrap;"><span style="color:#64748b;">' . $label . '</span> ' . esc_html($text) . '</span>';
    $tags = ($product['model'] !== '' ? $tag('型号', $product['model']) : '') . $tag('类型', $product['type'] !== '' ? $product['type'] : '—');
    $name = $product['url'] !== ''
        ? '<a href="' . esc_url($product['url']) . '" target="_blank" style="display:block;padding-bottom:10px;font-size:17px;line-height:1.45;font-weight:bold;color:#0b1f33;text-decoration:none;">' . esc_html($product['title']) . '</a>'
        : '<div style="padding-bottom:10px;font-size:17px;line-height:1.45;font-weight:bold;color:#0b1f33;">' . esc_html($product['title']) . '</div>';
    $photo = $product['image'] !== '' ? '<td width="96" style="width:96px;padding:16px 0 16px 16px;vertical-align:top;"><img src="' . esc_url($product['image']) . '" width="96" height="96" alt="' . esc_attr($product['title']) . '" style="display:block;width:96px;height:96px;border-radius:4px;border:1px solid #edf0f4;object-fit:cover;"></td>' : '';
    return '<table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:10px;border:1px solid #e3e8ef;border-radius:4px;border-collapse:separate;"><tr>' . $photo . '<td style="padding:16px 18px 10px;vertical-align:top;">' . $name . $tags . '</td></tr></table>';
}

/**
 * The page an inquiry was sent from, split for reading: the address without its query string, the page's title (sent by
 * the form, without the site name) and the query parameters (ad tags such as gclid) on their own.
 */
function boblift_inquiry_source_page(array $fields): array
{
    $url = trim((string) ($fields['source_page'] ?? ''));
    $parts = $url !== '' ? wp_parse_url($url) : [];
    $parts = is_array($parts) ? $parts : [];
    // The form's own referer field is relative (/contact.html?gclid=…): it is read against this site's address.
    if ($url !== '' && empty($parts['host'])) {
        $home = wp_parse_url(home_url('/'));
        $parts = array_merge(is_array($home) ? array_intersect_key($home, array_flip(['scheme', 'host', 'port'])) : [], $parts);
    }
    $query = [];
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    // Ad and campaign tags are listed on their own; anything else in the query (?page_id=, ?s=, ?product= …) picks the
    // page, so it stays in the address.
    $tracking = [];
    foreach ($query as $name => $value) {
        if (is_scalar($value) && preg_match('/^(?:utm_\w+|gclid|gbraid|wbraid|gad_\w+|fbclid|msclkid|ttclid|li_fat_id|_gl|yclid|dclid)$/i', (string) $name)) {
            $tracking[(string) $name] = (string) $value;
            unset($query[$name]);
        }
    }
    $path = (string) ($parts['path'] ?? '/');
    $clean = '';
    if (!empty($parts['host'])) {
        $clean = ($parts['scheme'] ?? 'https') . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '') . $path . ($query ? '?' . http_build_query($query) : '');
    }
    // "BOB-LIFT" sits at the end of most titles and at the start of the home page's; either way it says nothing here.
    $title = trim((string) preg_replace(['/\s*[|｜–—-]\s*BOB-LIFT\s*$/u', '/^BOB-LIFT\s*[|｜–—-]\s*/u'], '', (string) ($fields['source_title'] ?? '')));
    // The home page in any language (/, /zh/ …), with nothing in the query that picks another page, is named as such.
    if ($clean !== '' && !$query && preg_match('#^/(?:[a-z]{2}/?)?$#', $path)) {
        $title = '首页' . ($title !== '' ? ' · ' . $title : '');
    }
    return ['url' => $clean, 'title' => $title, 'params' => array_filter($tracking, 'strlen')];
}

/** The notification the sales team receives, as [subject, html body]. */
function boblift_inquiry_admin_mail(array $fields, int $post_id): array
{
    $font = "font-family:-apple-system,BlinkMacSystemFont,'PingFang SC','Microsoft YaHei','Helvetica Neue',Arial,sans-serif;";
    $heading = static fn(string $text): string => '<tr><td style="padding:28px 36px 12px;font-size:13px;font-weight:bold;letter-spacing:1px;color:#64748b;">' . $text . '</td></tr>';
    $row = static fn(string $label, string $value): string => '<tr><td width="130" style="width:130px;padding:13px 12px 13px 0;border-bottom:1px solid #edf0f4;font-size:14px;color:#64748b;vertical-align:top;">' . $label . '</td><td style="padding:13px 0;border-bottom:1px solid #edf0f4;font-size:16px;color:#0b1f33;line-height:1.6;vertical-align:top;word-break:break-word;">' . $value . '</td></tr>';
    $meta = static fn(string $label, string $value): string => '<td width="33%" style="width:33%;padding:16px 18px;vertical-align:top;"><div style="font-size:12px;color:#64748b;padding-bottom:4px;">' . $label . '</div><div style="font-size:15px;font-weight:bold;color:#0b1f33;line-height:1.4;word-break:break-word;">' . $value . '</div></td>';

    $email = (string) $fields['email'];
    $mailto = '<a href="mailto:' . esc_attr($email) . '" style="color:#215bdb;text-decoration:none;">' . esc_html($email) . '</a>';
    $labels = [
        'name' => '姓名', 'email' => '邮箱', 'phone' => '电话 / WhatsApp', 'company' => '公司', 'website' => '网站', 'country' => '国家（客户填写）', 'quantity' => '数量',
        'truck_available' => '是否有卡车', 'truck_model' => '卡车型号 / 底盘品牌', 'truck_specs' => '底盘参数', 'imported_from_china' => '是否从中国进口过',
        'customs_clearance' => '清关', 'destination_port' => '目的港 / 城市', 'preferred_delivery' => '期望交期', 'contact_method' => '联系方式偏好',
        'preferred_contact_time' => '方便联系时间', 'other_requirements' => '其他要求',
    ];
    $rows = [];
    foreach ($labels as $key => $label) {
        if ($key === 'email') {
            $rows[] = $row($label, $mailto);
            continue;
        }
        $value = trim((string) ($fields[$key] ?? ''));
        if ($value !== '') {
            $rows[] = $row($label, $key === 'website' ? '<a href="' . esc_url(preg_match('#^https?://#i', $value) ? $value : 'https://' . $value) . '" style="color:#215bdb;text-decoration:none;">' . esc_html($value) . '</a>' : nl2br(esc_html($value), false));
        }
    }
    if (trim((string) ($fields['legacy_models'] ?? '')) !== '') {
        $rows[] = $row('老型号', esc_html($fields['legacy_models']) . '（需定制生产）');
    }

    $products = boblift_inquiry_mail_products($fields);
    $product_html = $products ? implode('', array_map('boblift_inquiry_mail_product_card', $products)) : '<div style="padding:14px 18px;border:1px solid #e3e8ef;border-radius:4px;font-size:15px;color:#64748b;">未选择产品</div>';
    $kind = $products ? '产品询盘' : '询盘表单';
    $entry = trim((string) ($fields['source_entry'] ?? ''));
    $source = boblift_inquiry_source_page($fields);
    $page_html = '—';
    if ($source['url'] !== '') {
        $page_html = ($source['title'] !== '' ? '<div style="font-size:16px;font-weight:bold;color:#0b1f33;padding-bottom:2px;">' . esc_html($source['title']) . '</div>' : '')
            . '<a href="' . esc_url($source['url']) . '" target="_blank" style="color:#215bdb;text-decoration:none;">' . esc_html($source['url']) . '</a>';
        if ($source['params']) {
            $pairs = [];
            foreach ($source['params'] as $name => $value) {
                $pairs[] = esc_html($name . '=' . $value);
            }
            $page_html .= '<div style="padding-top:6px;font-size:12px;line-height:1.6;color:#64748b;">页面参数：' . implode(' &nbsp;·&nbsp; ', $pairs) . '</div>';
        }
    }
    // The IP address is used only to tell the country; the privacy policy promises it is not kept with the inquiry.
    $ip = boblift_inquiry_client_ip();
    $language_names = ['en' => '英语', 'zh' => '中文', 'ja' => '日语', 'es' => '西班牙语', 'ru' => '俄语', 'fr' => '法语', 'ar' => '阿拉伯语'];
    $posted_language = sanitize_key((string) wp_unslash($_POST['boblift_lang'] ?? 'en'));
    $language = $language_names[$posted_language] ?? ($posted_language !== '' ? $posted_language : '英语');
    $country = $ip !== '' ? (boblift_inquiry_ip_country($ip) ?: '未能识别') : '未能识别';
    $time = wp_date('Y-m-d H:i');
    // How the visitor came and on what: the big line is the kind (Google Ads 广告, 手机), the small one the details.
    $small = static fn(string $text): string => $text !== '' ? '<div style="padding-top:2px;font-size:12px;font-weight:normal;color:#64748b;">' . esc_html($text) . '</div>' : '';
    [$channel, $channel_detail] = boblift_inquiry_channel($fields);
    $channel_html = esc_html($channel) . $small($channel_detail);
    $device_parts = explode(' · ', (string) ($fields['device'] ?? ''), 2);
    $device_html = $device_parts[0] !== '' ? esc_html($device_parts[0]) . $small((string) ($device_parts[1] ?? '')) : '未能识别';
    $message = trim((string) $fields['message']);
    $message_block = $message !== '' ? $heading('留言') . '<tr><td style="padding:0 36px;"><div style="padding:16px 20px;background:#f5f7fa;border-radius:4px;font-size:16px;line-height:1.75;color:#0b1f33;word-break:break-word;">' . nl2br(esc_html($message), false) . '</div></td></tr>' : '';
    $admin_link = $post_id > 0 ? '<a href="' . esc_url(admin_url('post.php?post=' . $post_id . '&action=edit')) . '" style="color:#94a3b8;">网站后台「询盘」</a>' : '网站后台「询盘」';
    $host = esc_html((string) wp_parse_url(home_url(), PHP_URL_HOST));

    $body = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head><body style="margin:0;padding:0;background:#eef1f5;">'
        . '<table width="100%" cellspacing="0" cellpadding="0" style="width:100%;background:#eef1f5;-webkit-text-size-adjust:100%;text-size-adjust:100%;' . $font . '"><tr><td align="center" style="padding:32px 0;">'
        . '<table width="660" cellspacing="0" cellpadding="0" style="width:660px;background:#ffffff;border:1px solid #e3e8ef;border-radius:4px;border-collapse:separate;overflow:hidden;text-align:left;">'
        . '<tr><td height="6" style="height:6px;background:#215bdb;font-size:0;line-height:0;">&nbsp;</td></tr>'
        . '<tr><td style="padding:22px 36px;border-bottom:1px solid #edf0f4;"><table width="100%" cellspacing="0" cellpadding="0"><tr><td style="vertical-align:middle;">' . boblift_inquiry_mail_logo() . '</td><td align="right" style="vertical-align:middle;font-size:13px;color:#64748b;">' . esc_html($time) . '</td></tr></table></td></tr>'
        . '<tr><td style="padding:30px 36px 0;"><div style="font-size:26px;line-height:1.3;font-weight:bold;color:#0b1f33;">新询盘通知</div><div style="padding-top:10px;font-size:15px;line-height:1.7;color:#475569;">起重机站收到一条新询盘，内容如下，请及时跟进。</div></td></tr>'
        . '<tr><td style="padding:20px 36px 0;"><table width="100%" cellspacing="0" cellpadding="0" style="background:#f5f7fa;border-radius:4px;"><tr><td style="padding:14px 18px;"><div style="font-size:12px;color:#64748b;padding-bottom:6px;">来源页面 &nbsp;<span style="display:inline-block;padding:2px 8px;border-radius:2px;background:#e3ebfc;color:#215bdb;font-size:12px;font-weight:bold;">' . $kind . '</span>' . ($entry !== '' ? ' &nbsp;<span style="font-size:12px;color:#475569;">入口：' . esc_html($entry) . '</span>' : '') . '</div><div style="font-size:14px;line-height:1.6;word-break:break-all;">' . $page_html . '</div></td></tr></table></td></tr>'
        . $heading('产品信息') . '<tr><td style="padding:0 36px;">' . $product_html . '</td></tr>'
        . $heading('客户信息') . '<tr><td style="padding:0 36px;"><table width="100%" cellspacing="0" cellpadding="0" style="border-top:1px solid #edf0f4;">' . implode('', $rows) . '</table></td></tr>'
        . $message_block
        . '<tr><td style="padding:24px 36px 0;"><table width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #edf0f4;border-radius:4px;border-collapse:separate;"><tr>' . $meta('来源渠道', $channel_html) . $meta('访问设备', $device_html) . $meta('来源国家', esc_html($country)) . '</tr><tr>' . $meta('页面语言', esc_html($language)) . $meta('提交时间', esc_html($time)) . $meta('&nbsp;', '&nbsp;') . '</tr></table></td></tr>'
        . boblift_inquiry_attribution_block($fields)
        . '<tr><td align="center" style="padding:32px 36px 12px;"><table cellspacing="0" cellpadding="0"><tr><td align="center" style="background:#215bdb;border-radius:2px;"><a href="mailto:' . esc_attr($email) . '" style="display:inline-block;width:260px;line-height:50px;font-size:17px;font-weight:bold;color:#ffffff;text-decoration:none;text-align:center;letter-spacing:1px;">立即回复客户</a></td></tr></table></td></tr>'
        . '<tr><td align="center" style="padding:0 36px 32px;font-size:13px;line-height:1.6;color:#64748b;">也可以直接回复这封邮件，回信会发到 ' . esc_html($email) . '</td></tr>'
        . '</table>'
        . '<table width="660" cellspacing="0" cellpadding="0" style="width:660px;"><tr><td align="center" style="padding:18px 36px 0;font-size:12px;line-height:1.7;color:#94a3b8;">此邮件由 ' . $host . ' 询盘表单自动发送 · 副本见' . $admin_link . '</td></tr></table>'
        . '</td></tr></table></body></html>';

    $subject = '【起重机站】询盘飞奔而来，请及时跟进：' . $email;
    // A subject an editor saved in 站点文案 (quoteForm → messages → mailSubject) still wins over the branded default. Only the
    // source-language (English) options count: the sales team reads one subject whatever the visitor's language, and the
    // translated copies hold machine translations of the old default (e.g. 新的 BOB-LIFT 询价), not a choice.
    $source_options = get_option('boblift_theme_options', []);
    $saved_subject = trim((string) (is_array($source_options) ? ($source_options['site_ui']['quoteForm']['messages']['mailSubject'] ?? '') : ''));
    if ($saved_subject !== '' && $saved_subject !== 'New BOB-LIFT inquiry') {
        $subject = $saved_subject;
    }
    return [$subject, $body];
}

/** Send the sales notification for a saved inquiry. */
function boblift_send_inquiry_mail(array $fields, int $post_id): void
{
    // The form is handled on init priority 0, before the theme registers products and their categories; the product
    // card needs both (its type and permalink).
    if (!taxonomy_exists('product_category') && function_exists('boblift_register_taxonomies')) {
        boblift_register_content_types();
        boblift_register_taxonomies();
    }
    $from = boblift_inquiry_mail_from_header();
    [$subject, $body] = boblift_inquiry_admin_mail($fields, $post_id);
    $reply_to = 'Reply-To: ' . str_replace(["\r", "\n"], '', (string) $fields['email']);
    $sent = wp_mail(boblift_inquiry_recipient(), $subject, $body, ['Content-Type: text/html; charset=UTF-8', $from, $reply_to]);
    if ($post_id > 0) {
        update_post_meta($post_id, '_mail_sent', $sent ? '1' : '0');
    }
}
