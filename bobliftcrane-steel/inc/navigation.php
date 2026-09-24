<?php
/**
 * Navigation model for the Steel header and footer.
 * Reads the same options the parent header uses, but returns plain arrays so
 * templates stay readable.
 */

if (!defined('ABSPATH')) {
    exit;
}

function boblift_steel_current_language(): string
{
    $code = function_exists('pll_current_language') ? (string) pll_current_language('slug') : '';
    return $code !== '' ? $code : 'en';
}

function boblift_steel_source_language(): string
{
    return function_exists('boblift_mls_source_language') ? (string) boblift_mls_source_language() : 'en';
}

function boblift_steel_path_key(string $href): string
{
    $parts = wp_parse_url($href);
    $path = trim((string) ($parts['path'] ?? $href), '/');
    if ($path === '') {
        return '/';
    }
    return (string) preg_replace('/\.html$/', '', $path);
}

function boblift_steel_current_path_key(): string
{
    $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    return boblift_steel_path_key('/' . trim($path, '/'));
}

/** Secondary pages under "About", localised by page title when translated. */
function boblift_steel_about_children(): array
{
    $defaults = [
        ['label' => 'About Us', 'href' => '/about.html', 'slug' => 'about'],
        ['label' => 'Global Customer Visits', 'href' => '/global-customer-visits.html', 'slug' => 'global-customer-visits'],
        ['label' => 'Social Media', 'href' => '/social-media.html', 'slug' => 'social-media'],
        ['label' => 'Public Welfare', 'href' => '/public-welfare.html', 'slug' => 'public-welfare'],
        ['label' => 'Contact Us', 'href' => '/contact.html', 'slug' => 'contact'],
    ];
    $about = function_exists('boblift_about_page_options') ? boblift_about_page_options() : [];
    $source = is_array($about['secondary_nav'] ?? null) ? $about['secondary_nav'] : $defaults;
    $language = boblift_steel_current_language();
    $translate = $language !== boblift_steel_source_language();
    $children = [];
    foreach ($source as $child) {
        if (!is_array($child)) {
            continue;
        }
        $href = trim((string) ($child['href'] ?? ''));
        $label = trim((string) ($child['label'] ?? ''));
        $slug = sanitize_title((string) ($child['slug'] ?? ''));
        if ($slug === '' && $href !== '') {
            $slug = sanitize_title((string) preg_replace('/\.html$/', '', trim((string) wp_parse_url($href, PHP_URL_PATH), '/')));
        }
        if ($href === '' || $label === '') {
            continue;
        }
        if ($translate && $slug !== '') {
            $page = get_page_by_path($slug, OBJECT, 'page');
            if ($page instanceof WP_Post) {
                $localized = function_exists('boblift_post_for_current_language') ? boblift_post_for_current_language($page) : $page;
                $title = trim((string) get_the_title($localized));
                if ($title !== '') {
                    $label = $title;
                }
            }
        }
        $children[] = ['label' => $label, 'href' => boblift_url($href), 'key' => boblift_steel_path_key($href)];
    }
    return $children;
}

/** Product families with their child categories and product counts. */
function boblift_steel_product_menu(): array
{
    static $menu = null;
    if ($menu !== null) {
        return $menu;
    }
    $counts = [];
    foreach (boblift_all_products() as $product) {
        foreach (boblift_steel_product_category_slugs($product) as $slug) {
            $counts[$slug] = ($counts[$slug] ?? 0) + 1;
        }
    }
    $groups = [];
    foreach (boblift_product_category_hierarchy() as $group) {
        $slug = (string) ($group['slug'] ?? ($group['category']['slug'] ?? ''));
        if ($slug === '') {
            continue;
        }
        $count = (int) ($counts[$slug] ?? 0);
        $children = [];
        foreach (($group['children'] ?? []) as $child) {
            $child_slug = (string) ($child['slug'] ?? '');
            $child_count = (int) ($counts[$child_slug] ?? 0);
            if ($child_slug === '' || $child_count <= 0) {
                continue;
            }
            $children[] = [
                'slug' => $child_slug,
                'title' => (string) ($child['title'] ?? $child_slug),
                'href' => boblift_steel_category_url($child_slug),
                'count' => $child_count,
            ];
            $count = max($count, $child_count);
        }
        if ($count <= 0 && $children === []) {
            continue;
        }
        $category = is_array($group['category'] ?? null) ? $group['category'] : [];
        $groups[] = [
            'slug' => $slug,
            'title' => (string) ($group['title'] ?? ($category['title'] ?? $slug)),
            'href' => boblift_steel_category_url($slug),
            'image' => (string) ($category['image'] ?? ''),
            'count' => $count,
            'children' => $children,
        ];
    }
    $menu = $groups;
    return $menu;
}

/** Primary navigation items. */
function boblift_steel_primary_nav(): array
{
    $options = boblift_options();
    $items = is_array($options['nav_items'] ?? null) ? $options['nav_items'] : [];
    $current = boblift_steel_current_path_key();
    $nav = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $label = trim((string) ($item['label'] ?? ''));
        $href = trim((string) ($item['href'] ?? '/'));
        if ($label === '') {
            continue;
        }
        $key = boblift_steel_path_key($href);
        $lower = strtolower($label);
        $type = 'link';
        $children = [];
        if ($key === 'contact' || str_contains($lower, 'contact')) {
            continue; // Contact lives in the header CTA and footer.
        }
        if ($key === 'products' || (string) ($item['has_menu'] ?? '0') === '1') {
            $type = 'mega';
        } elseif ($key === 'about' || str_contains($lower, 'about')) {
            $type = 'children';
            $children = boblift_steel_about_children();
        }
        $active = $key === $current;
        if (!$active && $type === 'children') {
            foreach ($children as $child) {
                if (($child['key'] ?? '') === $current) {
                    $active = true;
                    break;
                }
            }
        }
        if (!$active && $type === 'mega') {
            $active = is_singular('product') || is_post_type_archive('product') || is_tax('product_category') || (string) get_query_var('boblift_product_category') !== '';
        }
        if (!$active && $key === 'news') {
            $active = is_singular('post') || is_category();
        }
        if (!$active && $key === 'cases') {
            $active = is_singular('case') || is_post_type_archive('case');
        }
        $nav[] = [
            'label' => $label,
            'href' => $key === 'products' && function_exists('boblift_product_archive_url') ? boblift_product_archive_url() : boblift_url($href),
            'type' => $type,
            'active' => $active,
            'children' => $children,
        ];
    }
    return $nav;
}

/** Language switcher entries. */
function boblift_steel_languages(): array
{
    $languages = function_exists('boblift_language_options') ? boblift_language_options() : [];
    $result = [];
    foreach ($languages as $language) {
        if (!is_array($language)) {
            continue;
        }
        $result[] = [
            'label' => (string) ($language['label'] ?? ''),
            'code' => (string) ($language['code'] ?? ''),
            'href' => (string) ($language['href'] ?? '#'),
            'flag' => function_exists('boblift_language_flag_url') ? boblift_language_flag_url((string) ($language['flag'] ?? '')) : '',
            'current' => !empty($language['current']),
        ];
    }
    return $result;
}

/** Floating contact dock entries. */
function boblift_steel_dock(): array
{
    $contact = boblift_steel_contact();
    $buttons = function_exists('boblift_floating_contact_buttons') ? boblift_floating_contact_buttons() : [];
    $dock = [];
    foreach ($buttons as $button) {
        if (!is_array($button) || (string) ($button['enabled'] ?? '1') !== '1') {
            continue;
        }
        $type = (string) ($button['type'] ?? '');
        $label = boblift_steel_t((string) ($button['label'] ?? ucfirst($type)));
        $entry = ['type' => $type, 'label' => $label, 'icon' => 'mail', 'href' => '', 'target' => ''];
        switch ($type) {
            case 'quote':
                $entry['icon'] = 'quote';
                $entry['href'] = $contact['quote_url'];
                break;
            case 'email':
                $entry['icon'] = 'mail';
                $entry['href'] = 'mailto:' . $contact['email'];
                break;
            case 'whatsapp':
                $entry['icon'] = 'whatsapp';
                $entry['href'] = $contact['whatsapp_href'];
                $entry['target'] = '_blank';
                break;
            case 'telegram':
                $entry['icon'] = 'telegram';
                $entry['href'] = $contact['telegram_href'];
                $entry['target'] = '_blank';
                break;
            case 'wechat':
                $entry['icon'] = 'qr';
                $entry['href'] = '#wechat';
                break;
            default:
                $entry['href'] = str_replace(['{email}', '{whatsapp}'], [$contact['email'], $contact['whatsapp_number']], (string) ($button['href'] ?? ''));
        }
        if ($entry['href'] === '') {
            continue;
        }
        $dock[] = $entry;
    }
    return $dock;
}
