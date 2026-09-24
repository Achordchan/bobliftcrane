<?php
/**
 * Small presentation helpers shared by the Steel templates.
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Localise a static UI string through the parent's translation table when available. */
function boblift_steel_t(string $text): string
{
    if ($text === '') {
        return '';
    }
    return function_exists('boblift_localized_static_text') ? (string) boblift_localized_static_text($text) : $text;
}

/** Thin-stroke inline icon set. */
function boblift_steel_icon(string $name, string $class = ''): string
{
    $paths = [
        'arrow-right' => '<path d="M4 12h15"/><path d="m13 6 6 6-6 6"/>',
        'arrow-up-right' => '<path d="M7 17 17 7"/><path d="M8 7h9v9"/>',
        'arrow-left' => '<path d="M20 12H5"/><path d="m11 18-6-6 6-6"/>',
        'arrow-down' => '<path d="M12 4v15"/><path d="m6 13 6 6 6-6"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'chevron-right' => '<path d="m9 6 6 6-6 6"/>',
        'plus' => '<path d="M12 5v14"/><path d="M5 12h14"/>',
        'minus' => '<path d="M5 12h14"/>',
        'close' => '<path d="M6 6l12 12"/><path d="M18 6 6 18"/>',
        'menu' => '<path d="M3 7h18"/><path d="M3 12h18"/><path d="M3 17h18"/>',
        'check' => '<path d="m5 12 5 5L20 7"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="1"/><path d="m3 7 9 6 9-6"/>',
        'phone' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a14 14 0 0 1 0 18"/><path d="M12 3a14 14 0 0 0 0 18"/>',
        'download' => '<path d="M12 4v11"/><path d="m7 10 5 5 5-5"/><path d="M4 19h16"/>',
        'file' => '<path d="M14 3H7a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V8z"/><path d="M14 3v5h5"/>',
        'play' => '<path d="M8 5v14l11-7z"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'filter' => '<path d="M4 6h16"/><path d="M7 12h10"/><path d="M10 18h4"/>',
        'pin' => '<path d="M12 21s7-6.5 7-12a7 7 0 0 0-14 0c0 5.5 7 12 7 12z"/><circle cx="12" cy="9" r="2.5"/>',
        'external' => '<path d="M14 4h6v6"/><path d="M20 4 11 13"/><path d="M19 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h5"/>',
        'whatsapp' => '<path d="M4 20l1.3-3.8A8.5 8.5 0 1 1 8.3 19z"/><path d="M9.5 8.5c.2 2.5 3.5 5.8 6 6l1-1.5-2-1-1 .8a5.5 5.5 0 0 1-2.3-2.3l.8-1-1-2z"/>',
        'telegram' => '<path d="M21 4 3 11l5.5 2L19 6l-8 8v5l3-3 4 3z"/>',
        'qr' => '<rect x="4" y="4" width="6" height="6"/><rect x="14" y="4" width="6" height="6"/><rect x="4" y="14" width="6" height="6"/><path d="M14 14h2v2h-2z"/><path d="M18 14h2v2h-2z"/><path d="M14 18h2v2h-2z"/><path d="M18 18h2v2h-2z"/>',
        'send' => '<path d="M21 3 3 10.5l7 2.5 2 7z"/><path d="M21 3 10 13"/>',
        'quote' => '<path d="M5 4h14a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1h-8l-5 4v-4H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1z"/><path d="M8 9h8"/><path d="M8 12h5"/>',
    ];
    $path = $paths[$name] ?? $paths['arrow-right'];
    $fill = $name === 'play' ? 'currentColor' : 'none';
    return '<svg class="st-icon ' . esc_attr($class) . '" viewBox="0 0 24 24" fill="' . $fill . '" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $path . '</svg>';
}

/** Two-digit index used in eyebrows: 1 -> "01". */
function boblift_steel_index(int $number): string
{
    return str_pad((string) max(0, $number), 2, '0', STR_PAD_LEFT);
}

/** Resolve an image option (array with id/path or plain path) to a URL. */
function boblift_steel_image(mixed $image, string $fallback = ''): string
{
    if (function_exists('boblift_image_setting_url')) {
        return boblift_image_setting_url($image, $fallback);
    }
    $path = is_array($image) ? (string) ($image['path'] ?? '') : (string) $image;
    return $path !== '' ? boblift_asset($path) : ($fallback !== '' ? boblift_asset($fallback) : '');
}

/** Split "1989 Company Founded" into [year, label]. */
function boblift_steel_split_timeline(string $entry): array
{
    $parts = explode(' ', trim($entry), 2);
    return [(string) ($parts[0] ?? ''), (string) ($parts[1] ?? '')];
}

/** Split a stat value such as "35+" or "9999+" into [number, suffix]. */
function boblift_steel_split_stat(string $value): array
{
    if (preg_match('/^([0-9][0-9,.\s]*)(.*)$/u', trim($value), $m)) {
        return [trim($m[1]), trim($m[2])];
    }
    return [trim($value), ''];
}

/** Product name without the model code, used for the card eyebrow. */
function boblift_steel_product_name(array $product): string
{
    $title = trim((string) ($product['title'] ?? ''));
    $model = trim((string) ($product['model'] ?? ''));
    if ($model === '' || strcasecmp($model, $title) === 0) {
        return $title;
    }
    $stripped = trim(str_ireplace($model, '', $title), " \t\n\r\0\x0B-_–—|/,");
    return $stripped !== '' ? $stripped : $title;
}

/** Heading shown on cards and the product hero: the model code when it exists. */
function boblift_steel_product_heading(array $product): string
{
    $model = trim((string) ($product['model'] ?? ''));
    return $model !== '' ? $model : trim((string) ($product['title'] ?? ''));
}

/** Only real, admin-entered specifications. No inferred values. */
function boblift_steel_product_specs(array $product, int $limit = 0): array
{
    $specs = array_values(array_filter($product['specs'] ?? [], static fn($spec): bool => is_array($spec) && trim((string) ($spec['label'] ?? '')) !== '' && trim((string) ($spec['value'] ?? '')) !== ''));
    return $limit > 0 ? array_slice($specs, 0, $limit) : $specs;
}

/** Compact datasheet rows for a product card. */
function boblift_steel_card_rows(array $product): array
{
    $ui = function_exists('boblift_site_ui') ? boblift_site_ui('productDetail') : [];
    $labels = is_array($ui['specLabels'] ?? null) ? $ui['specLabels'] : [];
    $rows = [];
    $capacity = trim((string) ($product['capacity'] ?? ''));
    if ($capacity !== '') {
        $rows[] = [boblift_steel_t((string) ($labels['maxLift'] ?? 'Max lift')), $capacity];
    }
    $boom = function_exists('boblift_boom_label') ? boblift_boom_label((string) ($product['boom_type'] ?? '')) : '';
    if ($boom !== '') {
        $rows[] = [boblift_steel_t('Boom'), $boom];
    }
    foreach (boblift_steel_product_specs($product, 3) as $spec) {
        if (count($rows) >= 3) {
            break;
        }
        $label = (string) $spec['label'];
        if ($capacity !== '' && stripos($label, 'capacity') !== false) {
            continue;
        }
        $rows[] = [$label, (string) $spec['value']];
    }
    if ($rows === []) {
        $status = function_exists('boblift_product_status_label') ? boblift_product_status_label((string) ($product['status'] ?? '')) : '';
        if ($status !== '') {
            $rows[] = [boblift_steel_t('Availability'), $status];
        }
    }
    return array_slice($rows, 0, 3);
}

/** Category slugs a product belongs to, including parents (for filtering). */
function boblift_steel_product_category_slugs(array $product): array
{
    static $parent_map = null;
    if ($parent_map === null) {
        $parent_map = function_exists('boblift_product_parent_category_map') ? boblift_product_parent_category_map() : [];
    }
    $slugs = array_values(array_unique(array_filter(array_map('strval', $product['categories'] ?? [($product['category'] ?? '')]))));
    foreach ($slugs as $slug) {
        $parent = (string) ($parent_map[$slug] ?? '');
        if ($parent !== '' && !in_array($parent, $slugs, true)) {
            $slugs[] = $parent;
        }
    }
    return $slugs;
}

/** Public URL of a product category. */
function boblift_steel_category_url(string $slug): string
{
    $term = get_term_by('slug', $slug, 'product_category');
    if ($term instanceof WP_Term) {
        $link = get_term_link($term);
        if (is_string($link)) {
            return $link;
        }
    }
    return function_exists('boblift_public_path_url') ? boblift_public_path_url('/' . $slug) : boblift_url('/' . $slug);
}

/** Contact shortcuts used by header, dock and footer. */
function boblift_steel_contact(): array
{
    $contact = function_exists('boblift_contact_settings') ? boblift_contact_settings() : [];
    $options = boblift_options();
    $raw = is_array($options['contact'] ?? null) ? $options['contact'] : [];
    $telegram_digits = preg_replace('/\D+/', '', (string) ($contact['telegram'] ?? $raw['telegram'] ?? ''));
    return [
        'email' => (string) ($contact['email'] ?? 'info@bobliftcrane.com'),
        'phone' => (string) ($contact['phone'] ?? ''),
        'phone_href' => (string) ($contact['phoneHref'] ?? ''),
        'whatsapp_href' => (string) ($contact['whatsappHref'] ?? ''),
        'whatsapp_number' => (string) ($contact['whatsappNumber'] ?? ''),
        'telegram_href' => $telegram_digits !== '' ? 'https://t.me/+' . $telegram_digits : '',
        'wechat_id' => (string) ($contact['wechatId'] ?? ''),
        'wechat_qr' => boblift_steel_image($contact['wechatQr'] ?? ($raw['wechat_qr'] ?? ''), 'images/wechat-qr.png'),
        'address' => (string) ($contact['address'] ?? ''),
        'quote_url' => boblift_url((string) ($raw['quote_path'] ?? '/quote')),
        'contact_url' => boblift_url((string) ($raw['contact_path'] ?? '/contact')),
    ];
}
