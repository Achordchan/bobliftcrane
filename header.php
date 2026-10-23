<?php
/** Theme header. */
$options = boblift_options();
$branding = $options['branding'] ?? [];
$top_bar = is_array($options['top_bar'] ?? null) ? $options['top_bar'] : [];
$show_top_bar = !array_key_exists('enabled', $top_bar) || (string) ($top_bar['enabled'] ?? '1') === '1';
$nav_items = $options['nav_items'] ?? [];
$product_menu_families = function_exists('boblift_product_menu_families') ? boblift_product_menu_families() : [];
// The family of the category or product being viewed is marked in the Products menu.
$product_menu_active = '';
$product_menu_term = null;
if (is_singular('product')) {
    $product_menu_terms = get_the_terms(get_queried_object_id(), 'product_category');
    $product_menu_term = is_array($product_menu_terms) ? ($product_menu_terms[0] ?? null) : null;
} elseif (function_exists('boblift_current_product_category_term')) {
    $product_menu_term = boblift_current_product_category_term();
}
if ($product_menu_term instanceof WP_Term) {
    $product_menu_ancestors = get_ancestors($product_menu_term->term_id, 'product_category', 'taxonomy');
    $product_menu_top = $product_menu_ancestors ? get_term((int) end($product_menu_ancestors), 'product_category') : $product_menu_term;
    if ($product_menu_top instanceof WP_Term && in_array($product_menu_top->slug, array_column($product_menu_families, 'slug'), true)) {
        $product_menu_active = $product_menu_top->slug;
    }
}
$contact = $options['contact'] ?? [];
$header_ui = function_exists('boblift_site_ui') ? boblift_site_ui('header') : [];
$quote_form_ui = function_exists('boblift_site_ui') ? boblift_site_ui('quoteForm') : [];
$search_ui = function_exists('boblift_site_ui') ? boblift_site_ui('search') : [];
// Sister sites sit next to the logo; this site is the crane one, so its entry always links home in the current language.
$site_switcher_ui = function_exists('boblift_site_ui') ? boblift_site_ui('siteSwitcher') : [];
$site_switcher_items = [];
// Lists saved before marks and descriptions existed take them from the built-in list, matched by domain.
$site_switcher_defaults = [];
foreach ((array) (boblift_site_ui_defaults()['siteSwitcher']['sites'] ?? []) as $default_site) {
    $site_switcher_defaults[(string) ($default_site['domain'] ?? '')] = $default_site;
}
foreach ((array) ($site_switcher_ui['sites'] ?? []) as $site) {
    if (!is_array($site) || trim((string) ($site['label'] ?? '')) === '') {
        continue;
    }
    $site_current = !empty($site['current']);
    $site_url = $site_current ? home_url('/') : trim((string) ($site['url'] ?? ''));
    if ($site_url === '') {
        continue;
    }
    $site_switcher_items[] = [
        'label' => (string) $site['label'],
        'short' => (string) ($site['short'] ?? $site['label']),
        'domain' => (string) ($site['domain'] ?? preg_replace('#^https?://(www\.)?|/$#', '', $site_url)),
        'url' => $site_url,
        'current' => $site_current,
        'mark' => (string) ($site['mark'] ?? $site_switcher_defaults[(string) ($site['domain'] ?? '')]['mark'] ?? mb_substr((string) $site['label'], 0, 1)),
        'description' => (string) ($site['description'] ?? boblift_localized_static_text((string) ($site_switcher_defaults[(string) ($site['domain'] ?? '')]['description'] ?? ''))),
    ];
}
// Links to a sister site open the leave dialog in footer.php first.
$site_leave_attrs = static fn(array $site): string => ' data-leave-site data-leave-name="' . esc_attr($site['label']) . '" data-leave-domain="' . esc_attr($site['domain']) . '"';
$site_switcher_current = array_values(array_filter($site_switcher_items, static fn(array $site): bool => $site['current']))[0] ?? [];
$search_query = is_search() ? get_search_query() : '';
$current_path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$active_language_code = function_exists('pll_current_language') ? (string) pll_current_language('slug') : 'en';
if ($active_language_code === '') {
    $active_language_code = 'en';
}
$news_ui = function_exists('boblift_site_ui') ? boblift_site_ui('newsPage') : [];
$news_category_slugs = function_exists('boblift_news_category_source_slugs') ? boblift_news_category_source_slugs() : ['company-news', 'industry-insight'];
$news_categories = array_values(array_filter(array_map(static function (string $slug): ?WP_Term {
    return function_exists('boblift_term_by_slug_for_current_language') ? boblift_term_by_slug_for_current_language($slug, 'category') : get_category_by_slug($slug);
}, $news_category_slugs)));
$news_page_url = boblift_url('/news.html');
$active_news_category = isset($_GET['news_category']) ? sanitize_title(wp_unslash((string) $_GET['news_category'])) : '';
$allowed_news_category_slugs = array_map(static fn(WP_Term $term): string => $term->slug, $news_categories);
if ($active_news_category !== '' && !in_array($active_news_category, $allowed_news_category_slugs, true)) {
    $active_news_category = '';
}
$news_menu_items = [[
    'label' => function_exists('boblift_localized_static_text') ? boblift_localized_static_text((string) ($news_ui['allLabel'] ?? 'All')) : (string) ($news_ui['allLabel'] ?? 'All'),
    'href' => $news_page_url,
    'active' => $active_news_category === '',
]];
foreach ($news_categories as $category) {
    $news_menu_items[] = [
        'label' => function_exists('boblift_localized_static_text') ? boblift_localized_static_text($category->name) : $category->name,
        'href' => add_query_arg('news_category', $category->slug, $news_page_url),
        'active' => $active_news_category === $category->slug,
    ];
}
$is_news_nav_item = static function (array $item, string $item_url = ''): bool {
    $label = strtolower(trim((string) ($item['label'] ?? '')));
    $href_path = trim((string) parse_url((string) ($item['href'] ?? ''), PHP_URL_PATH), '/');
    $url_path = trim((string) parse_url($item_url, PHP_URL_PATH), '/');
    return $label === 'news' || in_array($href_path, ['news', 'news.html'], true) || in_array($url_path, ['news', 'news.html'], true);
};
$about_page_options = function_exists('boblift_about_page_options') ? boblift_about_page_options() : [];
$about_nav_default_children = [
    ['label' => 'About Us', 'href' => '/about.html', 'slug' => 'about'],
    ['label' => 'Why Choose BOB-LIFT', 'href' => '/why-choose-us.html', 'slug' => 'why-choose-us'],
    ['label' => 'Factory Tour', 'href' => '/factory-tour.html', 'slug' => 'factory-tour'],
    ['label' => 'Quality & Testing', 'href' => '/quality-testing.html', 'slug' => 'quality-testing'],
    ['label' => 'Global Customer Visits', 'href' => '/global-customer-visits.html', 'slug' => 'global-customer-visits'],
];
$about_nav_source = is_array($about_page_options['secondary_nav'] ?? null) ? $about_page_options['secondary_nav'] : $about_nav_default_children;
$about_contact_nav_label = '';
foreach ($nav_items as $nav_item_for_label) {
    if (!is_array($nav_item_for_label)) {
        continue;
    }
    $nav_item_path = trim((string) wp_parse_url((string) ($nav_item_for_label['href'] ?? ''), PHP_URL_PATH), '/');
    if (in_array($nav_item_path, ['contact', 'contact.html'], true)) {
        $about_contact_nav_label = trim((string) ($nav_item_for_label['label'] ?? ''));
        break;
    }
}
$about_nav_default_labels = [];
foreach ($about_nav_default_children as $default_child) {
    $about_nav_default_labels[(string) $default_child['slug']] = (string) $default_child['label'];
}
$about_source_language = function_exists('boblift_mls_source_language') ? (string) boblift_mls_source_language() : 'en';
$about_localized_page_label = static function (string $slug, string $label) use ($active_language_code, $about_source_language, $about_nav_default_labels): string {
    if ($active_language_code === '' || $active_language_code === $about_source_language || ($about_nav_default_labels[$slug] ?? '') !== $label) {
        return $label;
    }
    $page = boblift_find_page_by_slug($slug);
    if (!$page instanceof WP_Post) {
        return $label;
    }
    $localized = function_exists('boblift_post_for_current_language') ? boblift_post_for_current_language($page) : $page;
    $title = trim((string) get_the_title($localized));
    return $title !== '' ? $title : $label;
};
$about_nav_children = [];
foreach ($about_nav_source as $child) {
    if (!is_array($child)) {
        continue;
    }
    $href = trim((string) ($child['href'] ?? ''));
    $slug = sanitize_title((string) ($child['slug'] ?? ''));
    if ($slug === '' && $href !== '') {
        $slug = sanitize_title((string) preg_replace('/\.html$/', '', trim((string) wp_parse_url($href, PHP_URL_PATH), '/')));
    }
    $label = trim((string) ($child['label'] ?? ''));
    if ($label === '') {
        $label = $about_nav_default_labels[$slug] ?? '';
    }
    if ($active_language_code !== $about_source_language && $slug === 'contact' && $label === 'Contact Us' && $about_contact_nav_label !== '') {
        $label = $about_contact_nav_label;
    }
    if ($label === '' || $href === '') {
        continue;
    }
    $about_nav_children[] = [
        'label' => $about_localized_page_label($slug, $label),
        'href' => $href,
    ];
}
if ($about_nav_children === []) {
    $about_nav_children = array_map(static fn(array $child): array => ['label' => (string) $child['label'], 'href' => (string) $child['href']], $about_nav_default_children);
}
$nav_path_key = static function (string $href): string {
    $parts = wp_parse_url($href);
    $path = trim((string) ($parts['path'] ?? $href), '/');
    if ($path === '') {
        return '/';
    }
    return (string) preg_replace('/\.html$/', '', $path);
};
$current_nav_path = $nav_path_key('/' . $current_path);
$nav_href_matches_current = static function (string $href, bool $ignore_fragment = false) use ($current_nav_path, $nav_path_key): bool {
    $parts = wp_parse_url($href);
    if (!$ignore_fragment && !empty($parts['fragment'])) {
        return false;
    }
    return $nav_path_key($href) === $current_nav_path;
};
$is_about_nav_item = static function (array $item) use ($nav_path_key): bool {
    $label = strtolower(trim((string) ($item['label'] ?? '')));
    return str_contains($label, 'about') || $nav_path_key((string) ($item['href'] ?? '')) === 'about';
};
$is_contact_nav_item = static function (array $item) use ($nav_path_key): bool {
    $label = strtolower(trim((string) ($item['label'] ?? '')));
    return str_contains($label, 'contact') || $nav_path_key((string) ($item['href'] ?? '')) === 'contact';
};
// Top level: Products · Cases · Services · About · News · Contact. Home is left out (the logo links home),
// Services gathers the mounting and after-sales pages, and News also carries the video and social pages.
$nav_page_child = static function (string $slug, string $label, string $href = '') use ($about_localized_page_label): array {
    $about_nav_label = $about_localized_page_label($slug, $label);
    if ($about_nav_label === $label && function_exists('boblift_localized_static_text')) {
        $about_nav_label = boblift_localized_static_text($label);
    }
    return ['label' => $about_nav_label, 'href' => $href !== '' ? $href : '/' . $slug . '.html'];
};
$nav_static_label = static fn(string $label): string => function_exists('boblift_localized_static_text') ? boblift_localized_static_text($label) : $label;
$nav_saved_contact = null;
$nav_by_key = [];
foreach ($nav_items as $item) {
    if (!is_array($item)) {
        continue;
    }
    $key = $nav_path_key((string) ($item['href'] ?? ''));
    if ($key === '/') {
        continue;
    }
    if ($is_contact_nav_item($item)) {
        $nav_saved_contact = $item;
        continue;
    }
    if ($is_about_nav_item($item)) {
        $item['children'] = $about_nav_children;
        $key = 'about';
    } elseif (!empty($item['has_menu']) && !$is_news_nav_item($item)) {
        $key = 'products';
    } elseif ($is_news_nav_item($item)) {
        $key = 'news';
    }
    // Keep every saved entry: a second entry that maps to the same key (two submenus, say) is listed after the ordered ones instead of replacing the first.
    if (isset($nav_by_key[$key])) {
        $base_key = $key;
        for ($n = 2; isset($nav_by_key[$key]); $n++) {
            $key = $base_key . '-' . $n;
        }
    }
    $nav_by_key[$key] = $item;
}
// A saved Services entry keeps its own label, link and children; the generated one is added only when there is none.
// A saved item for the same destination (/mounting-shipping.html) is the Services entry too.
if (!isset($nav_by_key['services']) && isset($nav_by_key['mounting-shipping'])) {
    $nav_by_key['services'] = $nav_by_key['mounting-shipping'];
    unset($nav_by_key['mounting-shipping']);
}
if (!isset($nav_by_key['services'])) {
    // Links go to the visitor's language version when one exists, as the footer's page links do.
    $services_localize = static function ($page) {
        if (!$page instanceof WP_Post || !function_exists('boblift_post_for_current_language')) {
            return $page;
        }
        $localized = boblift_post_for_current_language($page);
        return $localized->post_status === 'publish' ? $localized : $page;
    };
    $services_mounting_page = $services_localize(boblift_find_page_by_slug('mounting-shipping'));
    $services_operation_page = $services_localize(boblift_find_page_by_slug('service'));
}
if (!isset($nav_by_key['services']) && $services_mounting_page instanceof WP_Post) {
    $services_children = [$nav_page_child('mounting-shipping', 'Truck Mounting & Export', get_permalink($services_mounting_page))];
    if ($services_operation_page instanceof WP_Post) {
        $services_children[] = $nav_page_child('service', 'Service & Operation', get_permalink($services_operation_page));
    }
    if (boblift_find_page_by_slug('products') instanceof WP_Post) {
        $services_children[] = ['label' => $nav_static_label('Brochures'), 'href' => boblift_product_archive_path() . '#brochures'];
    }
    $generated_services = [
        'label' => $nav_static_label('Services'),
        'href' => get_permalink($services_mounting_page),
        'children' => $services_children,
    ];
    // Saved order is kept; the generated Services entry goes after Cases (or Products), else first.
    $after = isset($nav_by_key['cases']) ? 'cases' : (isset($nav_by_key['products']) ? 'products' : '');
    $keys = array_keys($nav_by_key);
    $at = $after !== '' ? array_search($after, $keys, true) + 1 : 0;
    $nav_by_key = array_slice($nav_by_key, 0, $at, true) + ['services' => $generated_services] + array_slice($nav_by_key, $at, null, true);
}
// Entries follow the order saved in the navigation editor. Only the untouched historical default order (About, Products,
// Cases, News) is rearranged into Products · Cases · Services · About · News; Contact stays last as the call-to-action.
if (array_values(array_diff(array_keys($nav_by_key), ['services'])) === ['about', 'products', 'cases', 'news']) {
    $nav_by_key = array_replace(array_intersect_key(array_flip(['products', 'cases', 'services', 'about', 'news']), $nav_by_key), $nav_by_key);
}
$nav_contact_item = [
    'label' => trim((string) ($nav_saved_contact['label'] ?? '')) !== '' ? (string) $nav_saved_contact['label'] : $nav_static_label('Contact Us'),
    'href' => '/contact.html',
];
$nav_items = array_merge(array_values($nav_by_key), [$nav_contact_item]);
// Video Center and Social Media follow the news categories, after a divider.
$news_menu_items[] = array_merge($nav_page_child('videos', 'Video Center'), ['divider' => true, 'active' => $nav_href_matches_current('/videos.html')]);
$news_menu_items[] = array_merge($nav_page_child('social-media', 'Social Media'), ['active' => $nav_href_matches_current('/social-media.html')]);
?><!doctype html>
<html lang="<?php echo esc_attr($active_language_code); ?>" dir="<?php echo esc_attr(function_exists('boblift_text_direction') ? boblift_text_direction() : 'ltr'); ?>" class="font-sans">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class('bg-white text-ink'); ?>>
<?php boblift_body_open(); ?>
<?php
$languages = function_exists('boblift_language_options') ? boblift_language_options() : [];
$active_language = $languages[0] ?? ['label' => 'English', 'flag' => 'flags/us.svg', 'code' => $active_language_code];
foreach ($languages as $language) {
    if (!empty($language['current'])) {
        $active_language = $language;
        break;
    }
}
?>
<?php if ($show_top_bar) : ?>
    <div class="bg-navy px-4 py-3 text-center text-[11px] font-normal leading-5 text-white/85 md:text-xs"><?php // Phones drop the highlight so the bar stays on one line. ?><span class="hidden text-gold sm:inline"><?php echo esc_html($top_bar['highlight'] ?? 'Global Lifting Solutions.'); ?> </span><?php echo esc_html($top_bar['text'] ?? 'Exported to 120+ Countries Worldwide.'); ?><a class="-my-3 ms-3 inline-block py-3 text-gold md:ms-5" href="<?php echo esc_url(boblift_url($top_bar['link_href'] ?? '/about')); ?>"><?php echo esc_html($top_bar['link_label'] ?? 'Learn More +'); ?></a></div>
<?php endif; ?>
<?php // The announcement bar scrolls away; the navigation below stays at the top of the window. ?>
<header id="top" class="sticky top-0 z-[200]">
    <div class="relative border-b border-line bg-white shadow-card">
        <div data-header-row class="page-shell flex h-[78px] items-center justify-between gap-3 lg:h-[92px] lg:gap-4 min-[1380px]:grid min-[1380px]:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] min-[1680px]:gap-8">
            <div class="flex min-w-0 items-center gap-3 lg:gap-2 xl:gap-4">
                <a class="flex shrink-0 items-center" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr($header_ui['homeAriaLabel'] ?? 'BOB-LIFT Home'); ?>">
                    <?php echo boblift_img_from_setting($branding['logo'] ?? 'images/bob-lift-logo.png', 'images/bob-lift-logo.png', $branding['logo_alt'] ?? 'BOB-LIFT Cranes', 'h-[52px] w-auto object-contain lg:h-[58px]', ['loading' => 'eager']); ?>
                </a>
                <?php if ($site_switcher_items !== []) : ?>
                    <span class="hidden h-8 w-px shrink-0 bg-line md:block" aria-hidden="true"></span>
                    <div class="relative hidden md:block" data-language-root>
                        <button class="group flex h-10 items-center gap-1.5 whitespace-nowrap rounded-sm px-2 text-sm font-black text-ink transition hover:bg-mist focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-gold/20 aria-expanded:bg-mist" type="button" aria-label="<?php echo esc_attr(($site_switcher_current['short'] ?? 'Cranes') . ' · ' . ($site_switcher_ui['openLabel'] ?? 'Switch BOB-LIFT site')); ?>" aria-expanded="false" aria-controls="site-switcher-menu" data-language-toggle>
                            <span><?php echo esc_html($site_switcher_current['short'] ?? 'Cranes'); ?></span>
                            <?php echo boblift_icon('chevron-down', 'h-4 w-4 shrink-0 text-slate-500 transition group-aria-expanded:rotate-180'); ?>
                        </button>
                        <div id="site-switcher-menu" class="absolute start-0 top-full z-[220] hidden pt-3" data-language-menu>
                            <div class="w-[340px] overflow-hidden rounded border border-line bg-white p-1.5 shadow-raised">
                                <p class="px-3 pb-1.5 pt-2 text-[11px] font-black uppercase tracking-[0.14em] text-slate-500"><?php echo esc_html($site_switcher_ui['title'] ?? 'BOB-LIFT sites'); ?></p>
                                <?php foreach ($site_switcher_items as $site) : ?>
                                    <a class="flex items-center gap-3 rounded-sm px-3 py-2.5 outline-none transition hover:bg-mist focus-visible:bg-mist <?php echo $site['current'] ? 'bg-mist' : ''; ?>" href="<?php echo esc_url($site['url']); ?>"<?php echo $site['current'] ? ' aria-current="true"' : $site_leave_attrs($site); ?>>
                                        <span class="grid size-9 shrink-0 place-items-center rounded-sm <?php echo $site['current'] ? 'bg-navy text-white' : 'border border-line bg-white text-navy'; ?> text-sm font-black" aria-hidden="true"><?php echo esc_html($site['mark']); ?></span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-[11px] font-normal text-slate-500"><?php echo esc_html($site['domain']); ?></span>
                                            <span class="block truncate text-sm font-black text-ink"><?php echo esc_html($site['label']); ?></span>
                                            <?php if ($site['description'] !== '') : ?><span class="block text-xs font-normal leading-4 text-slate-500"><?php echo esc_html($site['description']); ?></span><?php endif; ?>
                                        </span>
                                        <?php if ($site['current']) : ?>
                                            <span class="shrink-0 rounded-sm bg-navy px-1.5 py-0.5 text-[10px] font-black text-white"><?php echo esc_html($site_switcher_ui['currentLabel'] ?? 'You are here'); ?></span>
                                        <?php else : ?>
                                            <?php echo boblift_icon('arrow-up-right', 'h-4 w-4 shrink-0 text-slate-500'); ?>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <nav data-desktop-nav class="hidden min-w-0 items-center gap-1 lg:flex min-[1680px]:gap-2" aria-label="<?php echo esc_attr($header_ui['mainNavigationLabel'] ?? 'Main navigation'); ?>">
                <?php foreach ($nav_items as $nav_index => $item) :
                    $href = (string) ($item['href'] ?? '/');
                    $news_by_href = $is_news_nav_item($item);
                    $item_url = !empty($item['has_menu']) && !$news_by_href ? boblift_product_archive_url() : boblift_url($href);
                    $is_news_item = $news_by_href || $is_news_nav_item($item, $item_url);
                    $is_product_menu_item = !empty($item['has_menu']) && !$is_news_item;
                    $children = is_array($item['children'] ?? null) ? array_values(array_filter($item['children'], static fn($child): bool => is_array($child) && trim((string) ($child['label'] ?? '')) !== '')) : [];
                    $has_children = $children !== [];
                    $item_path = trim(parse_url($item_url, PHP_URL_PATH) ?: '/', '/');
                    $active = $href === '/' ? ($current_path === '' && !is_search()) : ($item_path !== '' && str_starts_with($current_path, $item_path));
                    if ($is_product_menu_item && (is_post_type_archive('product') || is_singular('product') || get_query_var('boblift_product_category') || get_query_var('boblift_tonnage'))) {
                        $active = true;
                    }
                    if ($is_news_item && ($current_path === 'news' || $current_path === 'news.html' || is_page_template('page-news.php') || is_singular('post') || $nav_href_matches_current('/videos.html') || $nav_href_matches_current('/social-media.html'))) {
                        $active = true;
                    }
                    if ($has_children) {
                        foreach ($children as $child) {
                            if ($nav_href_matches_current((string) ($child['href'] ?? ''), true)) {
                                $active = true;
                                break;
                            }
                        }
                    }
                    $pill = 'group relative flex h-10 shrink-0 items-center gap-1 whitespace-nowrap px-2 text-[14px] font-black leading-none transition duration-300 min-[1200px]:text-[16px] min-[1400px]:gap-1.5 min-[1400px]:px-3 min-[1680px]:px-4 after:absolute after:-bottom-1 after:start-3 after:end-3 after:h-0.5 after:origin-center after:rounded-full after:bg-gold after:transition after:duration-300 ' . ($active ? 'text-ink after:scale-x-100' : 'text-slate-600 after:scale-x-0 hover:text-ink hover:after:scale-x-100');
                    ?>
                    <?php if ($is_product_menu_item) : ?>
                        <div class="group static" data-secondary-nav-root>
                            <a class="<?php echo esc_attr($pill); ?>" href="<?php echo esc_url($item_url); ?>" aria-haspopup="true" aria-expanded="false" data-secondary-nav-trigger><?php echo esc_html(function_exists('boblift_localized_static_text') ? boblift_localized_static_text((string) $item['label']) : (string) $item['label']); ?><?php echo boblift_icon('chevron-down', 'h-3.5 w-3.5 text-gold transition group-hover:rotate-180 group-focus-within:rotate-180'); ?></a>
                            <div class="pointer-events-none invisible absolute start-0 end-0 top-full z-[214] opacity-0 transition duration-200 group-hover:pointer-events-auto group-hover:visible group-hover:opacity-100 group-focus-within:pointer-events-auto group-focus-within:visible group-focus-within:opacity-100" data-secondary-nav-bar data-secondary-nav-menu>
                                <div class="relative border-y border-blue-100 bg-[linear-gradient(180deg,#f8fbff_0%,#ffffff_100%)] shadow-raised">
                                    <div class="page-shell flex h-[68px] items-center overflow-x-auto">
                                        <?php // The families of the catalog in one row, a divider between the crane group and the related-equipment groups. ?>
                                        <nav class="mx-auto flex min-w-max items-center justify-center whitespace-nowrap" aria-label="<?php echo esc_attr(function_exists('boblift_localized_static_text') ? boblift_localized_static_text((string) ($item['label'] ?? 'Products')) : (string) ($item['label'] ?? 'Products')); ?> secondary navigation">
                                            <?php foreach ($product_menu_families as $family_index => $family) : ?>
                                                <?php if ($family_index > 0 && $family['group'] !== $product_menu_families[$family_index - 1]['group']) : ?><span class="mx-7 h-7 w-px bg-slate-300" aria-hidden="true"></span><?php elseif ($family_index > 0) : ?><span class="mx-5" aria-hidden="true"></span><?php endif; ?>
                                                <a class="text-[16px] font-medium leading-none transition hover:text-blue-700 <?php echo $family['slug'] === $product_menu_active ? 'font-black text-ink' : 'text-slate-600'; ?>" href="<?php echo esc_url($family['url']); ?>"><?php echo esc_html($family['title']); ?></a>
                                            <?php endforeach; ?>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($has_children) : ?>
                        <div class="group static" data-secondary-nav-root>
                            <a class="<?php echo esc_attr($pill); ?>" href="<?php echo esc_url($item_url); ?>" aria-haspopup="true" aria-expanded="false" data-secondary-nav-trigger><?php echo esc_html(function_exists('boblift_localized_static_text') ? boblift_localized_static_text((string) $item['label']) : (string) $item['label']); ?><?php echo boblift_icon('chevron-down', 'h-3.5 w-3.5 text-gold transition group-hover:rotate-180 group-focus-within:rotate-180'); ?></a>
                            <div class="pointer-events-none invisible absolute start-0 end-0 top-full z-[214] opacity-0 transition duration-200 group-hover:pointer-events-auto group-hover:visible group-hover:opacity-100 group-focus-within:pointer-events-auto group-focus-within:visible group-focus-within:opacity-100" data-secondary-nav-bar data-secondary-nav-menu>
                                <div class="relative border-y border-blue-100 bg-[linear-gradient(180deg,#f8fbff_0%,#ffffff_100%)] shadow-raised">
                                    <div class="page-shell flex h-[68px] items-center overflow-x-auto">
                                        <nav class="mx-auto flex min-w-max items-center justify-center whitespace-nowrap" aria-label="<?php echo esc_attr(function_exists('boblift_localized_static_text') ? boblift_localized_static_text((string) ($item['label'] ?? 'Secondary')) : (string) ($item['label'] ?? 'Secondary')); ?> secondary navigation">
                                            <?php foreach ($children as $child_index => $child) : $child_href = (string) ($child['href'] ?? '#'); $child_active = $nav_href_matches_current($child_href); ?>
                                                <?php if ($child_index === 1) : ?><span class="mx-7 h-7 w-px bg-slate-300" aria-hidden="true"></span><?php elseif ($child_index > 1) : ?><span class="mx-5" aria-hidden="true"></span><?php endif; ?>
                                                <a class="text-[16px] font-medium leading-none transition hover:text-blue-700 <?php echo $child_active ? 'font-black text-ink' : 'text-slate-600'; ?>" href="<?php echo esc_url(boblift_url($child_href)); ?>"><?php echo esc_html(function_exists('boblift_localized_static_text') ? boblift_localized_static_text((string) ($child['label'] ?? '')) : (string) ($child['label'] ?? '')); ?></a>
                                            <?php endforeach; ?>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($is_news_item && $news_menu_items !== []) : ?>
                        <div class="group static" data-secondary-nav-root>
                            <a class="<?php echo esc_attr($pill); ?>" href="<?php echo esc_url($item_url); ?>" aria-haspopup="true" aria-expanded="false" data-secondary-nav-trigger><?php echo esc_html(function_exists('boblift_localized_static_text') ? boblift_localized_static_text((string) $item['label']) : (string) $item['label']); ?><?php echo boblift_icon('chevron-down', 'h-3.5 w-3.5 text-gold transition group-hover:rotate-180 group-focus-within:rotate-180'); ?></a>
                            <div class="pointer-events-none invisible absolute start-0 end-0 top-full z-[214] opacity-0 transition duration-200 group-hover:pointer-events-auto group-hover:visible group-hover:opacity-100 group-focus-within:pointer-events-auto group-focus-within:visible group-focus-within:opacity-100" data-secondary-nav-bar data-secondary-nav-menu>
                                <div class="relative border-y border-blue-100 bg-[linear-gradient(180deg,#f8fbff_0%,#ffffff_100%)] shadow-raised">
                                    <div class="page-shell flex h-[68px] items-center overflow-x-auto">
                                        <nav class="mx-auto flex min-w-max items-center justify-center whitespace-nowrap" aria-label="News secondary navigation">
                                            <?php foreach ($news_menu_items as $news_index => $news_menu_item) : ?>
                                                <?php if ($news_index === 1 || !empty($news_menu_item['divider'])) : ?><span class="mx-7 h-7 w-px bg-slate-300" aria-hidden="true"></span><?php elseif ($news_index > 1) : ?><span class="mx-5" aria-hidden="true"></span><?php endif; ?>
                                                <a class="text-[16px] font-medium leading-none transition hover:text-blue-700 <?php echo !empty($news_menu_item['active']) ? 'font-black text-ink' : 'text-slate-600'; ?>" href="<?php echo esc_url(boblift_url((string) $news_menu_item['href'])); ?>"><?php echo esc_html((string) $news_menu_item['label']); ?></a>
                                            <?php endforeach; ?>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else : ?>
                        <a class="<?php echo esc_attr($pill); ?>" href="<?php echo esc_url($item_url); ?>"><?php echo esc_html(function_exists('boblift_localized_static_text') ? boblift_localized_static_text((string) $item['label']) : (string) $item['label']); ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
            <div class="flex items-center gap-2 md:gap-3 xl:gap-2 min-[1380px]:justify-self-end min-[1680px]:gap-3">
                <button class="group grid h-11 w-11 shrink-0 place-items-center rounded-sm border border-line bg-white text-ink transition hover:border-ink/30 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-gold/20 aria-expanded:border-ink/40 aria-expanded:bg-mist md:h-12 md:w-12" type="button" aria-label="<?php echo esc_attr($search_ui['openLabel'] ?? 'Search the site'); ?>" aria-expanded="false" aria-controls="site-search" data-search-toggle><?php echo boblift_icon('search', 'h-5 w-5 group-aria-expanded:hidden'); ?><?php echo boblift_icon('x', 'hidden h-5 w-5 group-aria-expanded:block'); ?></button>
                <div class="relative" data-language-root>
                    <button class="group flex h-11 items-center gap-1.5 rounded-sm border border-line bg-white px-3 text-start transition hover:border-ink/30 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-gold/20 aria-expanded:border-ink/40 md:h-12" type="button" aria-label="<?php echo esc_attr(strtoupper((string) ($active_language['code'] ?? 'en')) . ' · ' . ($header_ui['changeLanguageLabel'] ?? 'Change language') . ': ' . ($active_language['label'] ?? 'English')); ?>" aria-expanded="false" aria-controls="language-menu" data-language-toggle>
                        <?php echo boblift_icon('globe', 'h-4 w-4 shrink-0 text-slate-500'); ?>
                        <strong class="text-sm font-black uppercase tracking-[0.06em] text-ink"><?php echo esc_html(strtoupper((string) ($active_language['code'] ?? 'en'))); ?></strong>
                        <?php echo boblift_icon('chevron-down', 'h-4 w-4 shrink-0 text-slate-500 transition group-aria-expanded:rotate-180'); ?>
                    </button>
                    <div id="language-menu" class="absolute end-0 top-full z-[220] hidden pt-2" data-language-menu>
                        <div class="w-52 overflow-hidden rounded border border-line bg-white py-1.5 shadow-raised">
                            <?php foreach ($languages as $language) :
                                $language_label = (string) ($language['label'] ?? 'English');
                                $language_code = (string) ($language['code'] ?? 'en');
                                $language_market = (string) ($language['market'] ?? ($header_ui['regionalMarketLabel'] ?? 'Regional Market'));
                                $selected = !empty($language['current']);
                                ?>
                                <a class="relative flex h-11 items-center gap-3 px-4 text-sm outline-none transition hover:bg-mist hover:text-ink focus-visible:bg-mist <?php echo $selected ? 'bg-mist font-black text-ink' : 'font-medium text-slate-600'; ?>" href="<?php echo esc_attr((string) ($language['href'] ?? '/')); ?>" hreflang="<?php echo esc_attr($language_code); ?>" title="<?php echo esc_attr($language_market); ?>" <?php echo $selected ? 'aria-current="true"' : ''; ?>>
                                    <span class="min-w-0 flex-1 truncate"><?php echo esc_html($language_label); ?></span>
                                    <span class="text-[11px] font-bold uppercase tracking-[0.12em] <?php echo $selected ? 'text-ink' : 'text-slate-500'; ?>"><?php echo esc_html(strtoupper($language_code)); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <button class="grid h-11 w-11 place-items-center rounded-sm border border-navy/10 bg-navy text-white shadow-card transition duration-300 hover:-translate-y-0.5 lg:hidden" type="button" data-mobile-toggle aria-expanded="false" aria-controls="mobile-menu" aria-label="<?php echo esc_attr($header_ui['mobileOpenLabel'] ?? 'Open mobile navigation'); ?>"><?php echo boblift_icon('menu', 'h-5 w-5', '2.4'); ?></button>
                <button class="group hidden h-12 items-center justify-center whitespace-nowrap rounded-sm border border-transparent bg-navy bg-clip-padding px-4 text-sm font-black text-white shadow-card outline-none select-none transition-all hover:-translate-y-0.5 hover:bg-navy lg:inline-flex" type="button" data-inquiry-modal-open data-inquiry-source="顶部导航"><span class="2xl:hidden"><?php echo esc_html($header_ui['quoteShortLabel'] ?? 'Quote'); ?></span><span class="hidden 2xl:inline"><?php echo esc_html($header_ui['quoteLabel'] ?? ($quote_form_ui['label'] ?? 'Request Quote')); ?></span></button>
            </div>
        </div>
    </div>
    <div id="mobile-menu" class="mobile-menu absolute end-3 top-[calc(100%+10px)] z-50 hidden max-h-[min(calc(100dvh-164px),680px)] w-[min(350px,calc(100vw-24px))] overflow-y-auto overscroll-contain rounded border border-line bg-white p-1.5 shadow-raised sm:end-4 sm:w-[min(360px,calc(100vw-32px))] lg:hidden">
        <div class="rounded bg-[linear-gradient(135deg,#0a2f62_0%,#155aa2_100%)] px-4 py-2.5 text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.18)]">
            <p class="text-[9px] font-extrabold uppercase tracking-[0.2em] text-gold"><?php echo esc_html($header_ui['mobileLabel'] ?? 'Navigation'); ?></p>
            <p class="mt-0.5 text-base font-extrabold leading-6"><?php echo esc_html($header_ui['mobileTitle'] ?? 'BOB-LIFT Menu'); ?></p>
        </div>
        <div class="mt-2 grid gap-1">
            <?php foreach ($nav_items as $mobile_nav_index => $item) :
                $mobile_href = (string) ($item['href'] ?? '/');
                $mobile_news_by_href = $is_news_nav_item($item);
                $mobile_item_url = !empty($item['has_menu']) && !$mobile_news_by_href ? boblift_product_archive_url() : boblift_url($mobile_href);
                $mobile_is_news_item = $mobile_news_by_href || $is_news_nav_item($item, $mobile_item_url);
                $mobile_is_product_menu_item = !empty($item['has_menu']) && !$mobile_is_news_item;
                $mobile_children = is_array($item['children'] ?? null) ? array_values(array_filter($item['children'], static fn($child): bool => is_array($child) && trim((string) ($child['label'] ?? '')) !== '')) : [];
                $mobile_has_children = $mobile_children !== [];
                $mobile_news_open = $mobile_is_news_item && ($current_path === 'news' || $current_path === 'news.html' || is_page_template('page-news.php') || is_singular('post'));
                $mobile_children_open = false;
                foreach ($mobile_children as $mobile_child) {
                    if ($nav_href_matches_current((string) ($mobile_child['href'] ?? ''), true)) {
                        $mobile_children_open = true;
                        break;
                    }
                }
                ?>
                <div>
                    <?php if ($mobile_is_product_menu_item) : ?>
                        <div class="flex cursor-pointer items-center rounded px-4 py-3 transition duration-200 hover:bg-[#f7fbff] hover:text-blue-700" data-mobile-submenu-row>
                            <a class="min-w-0 shrink-0 text-[15px] font-black leading-5 text-ink outline-none hover:text-blue-700" href="<?php echo esc_url(boblift_product_archive_url()); ?>" data-mobile-submenu-link><?php echo esc_html(function_exists('boblift_localized_static_text') ? boblift_localized_static_text((string) ($item['label'] ?? '')) : (string) ($item['label'] ?? '')); ?></a>
                            <button class="group -my-3 -me-4 ms-auto grid h-11 w-12 shrink-0 place-items-center text-slate-300 transition hover:text-blue-600" type="button" aria-expanded="false" aria-controls="mobile-products-submenu" aria-label="<?php echo esc_attr($header_ui['expandProductCategoriesLabel'] ?? 'Expand product categories'); ?>" data-mobile-submenu-toggle>
                                <?php echo boblift_icon('chevron-down', 'h-4 w-4 transition group-aria-expanded:rotate-180'); ?>
                            </button>
                        </div>
                        <div id="mobile-products-submenu" class="mx-1.5 mb-2 hidden rounded border border-line bg-[#f8fbff] p-2.5" data-product-nav="mobile" data-mobile-submenu-panel>
                            <p class="px-2 pb-2 text-[10px] font-black uppercase tracking-[0.18em] text-gold"><?php echo esc_html($header_ui['productCategoriesLabel'] ?? 'Product Categories'); ?></p>
                            <div class="grid gap-3">
                                <?php $family_group_shown = 'crane'; foreach ($product_menu_families as $family) : ?>
                                    <?php if ($family['group'] !== $family_group_shown) : $family_group_shown = $family['group']; ?>
                                        <p class="px-2 pt-1 text-[10px] font-black uppercase tracking-[0.14em] text-slate-500"><?php echo esc_html(boblift_product_family_group_label($family['group'])); ?></p>
                                    <?php endif; ?>
                                    <div>
                                        <a class="flex items-center justify-between gap-3 rounded bg-white px-3 py-2 text-[13px] font-black leading-4 text-ink shadow-card" href="<?php echo esc_url($family['url']); ?>"><span class="min-w-0"><?php echo esc_html($family['title']); ?></span><span class="shrink-0 text-[11px] font-bold tabular-nums text-slate-500"><?php echo esc_html((string) $family['count']); ?></span></a>
                                        <?php if ($family['children']) : ?>
                                            <div class="mt-1 grid gap-1 ps-2">
                                                <?php foreach ($family['children'] as $family_child) : ?><a class="rounded px-3 py-1.5 text-xs font-bold leading-[18px] text-slate-500 hover:bg-white hover:text-blue-700" href="<?php echo esc_url($family_child['url']); ?>"><?php echo esc_html($family_child['title']); ?></a><?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php elseif ($mobile_has_children) : ?>
                        <details class="group rounded" <?php echo $mobile_children_open ? 'open' : ''; ?>>
                            <summary class="flex cursor-pointer list-none items-center justify-between rounded px-4 py-3 text-[15px] font-black leading-5 text-ink outline-none transition duration-200 hover:bg-[#f7fbff] hover:text-blue-700 [&::-webkit-details-marker]:hidden">
                                <span><?php echo esc_html(function_exists('boblift_localized_static_text') ? boblift_localized_static_text((string) ($item['label'] ?? '')) : (string) ($item['label'] ?? '')); ?></span>
                                <?php echo boblift_icon('chevron-down', 'h-4 w-4 text-slate-300 transition group-open:rotate-180 group-open:text-blue-600'); ?>
                            </summary>
                            <div class="mx-1.5 mb-2 rounded border border-line bg-[#f8fbff] p-2">
                                <?php foreach ($mobile_children as $mobile_child) : $mobile_child_href = (string) ($mobile_child['href'] ?? '#'); $mobile_child_active = $nav_href_matches_current($mobile_child_href); ?>
                                    <a class="flex items-center justify-between rounded px-3 py-2 text-xs font-bold leading-[18px] transition hover:bg-white hover:text-blue-700 <?php echo $mobile_child_active ? 'bg-white text-blue-700' : 'text-slate-500'; ?>" href="<?php echo esc_url(boblift_url($mobile_child_href)); ?>">
                                        <span><?php echo esc_html(function_exists('boblift_localized_static_text') ? boblift_localized_static_text((string) ($mobile_child['label'] ?? '')) : (string) ($mobile_child['label'] ?? '')); ?></span>
                                        <?php if ($mobile_child_active) : ?><?php echo boblift_icon('check', 'h-3.5 w-3.5 text-gold'); ?><?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    <?php elseif ($mobile_is_news_item && $news_menu_items !== []) : ?>
                        <details class="group rounded" <?php echo $mobile_news_open ? 'open' : ''; ?>>
                            <summary class="flex cursor-pointer list-none items-center justify-between rounded px-4 py-3 text-[15px] font-black leading-5 text-ink outline-none transition duration-200 hover:bg-[#f7fbff] hover:text-blue-700 [&::-webkit-details-marker]:hidden">
                                <span><?php echo esc_html(function_exists('boblift_localized_static_text') ? boblift_localized_static_text((string) ($item['label'] ?? '')) : (string) ($item['label'] ?? '')); ?></span>
                                <?php echo boblift_icon('chevron-down', 'h-4 w-4 text-slate-300 transition group-open:rotate-180 group-open:text-blue-600'); ?>
                            </summary>
                            <div class="mx-1.5 mb-2 rounded border border-line bg-[#f8fbff] p-2">
                                <?php foreach ($news_menu_items as $news_menu_item) : ?>
                                    <a class="flex items-center justify-between rounded px-3 py-2 text-xs font-bold leading-[18px] transition hover:bg-white hover:text-blue-700 <?php echo !empty($news_menu_item['active']) ? 'bg-white text-blue-700' : 'text-slate-500'; ?>" href="<?php echo esc_url(boblift_url((string) $news_menu_item['href'])); ?>">
                                        <span><?php echo esc_html((string) $news_menu_item['label']); ?></span>
                                        <?php if (!empty($news_menu_item['active'])) : ?><?php echo boblift_icon('check', 'h-3.5 w-3.5 text-gold'); ?><?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    <?php else : ?>
                        <a class="group flex cursor-pointer items-center justify-between rounded px-4 py-3 text-[15px] font-black leading-5 text-ink outline-none transition duration-200 hover:bg-[#f7fbff] hover:text-blue-700" href="<?php echo esc_url($mobile_item_url); ?>"><?php echo esc_html(function_exists('boblift_localized_static_text') ? boblift_localized_static_text((string) ($item['label'] ?? '')) : (string) ($item['label'] ?? '')); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-blue-600'); ?></a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php $site_switcher_others = array_values(array_filter($site_switcher_items, static fn(array $site): bool => !$site['current'])); ?>
        <?php if ($site_switcher_others !== []) : ?>
            <div class="mt-1.5 border-t border-line px-2 pb-1 pt-3">
                <p class="px-2 pb-1.5 text-[11px] font-black uppercase tracking-[0.14em] text-slate-500"><?php echo esc_html($site_switcher_ui['mobileTitle'] ?? 'Other BOB-LIFT sites'); ?></p>
                <?php foreach ($site_switcher_others as $site) : ?>
                    <a class="flex items-center justify-between gap-3 rounded px-2 py-2.5 transition hover:bg-[#f7fbff]" href="<?php echo esc_url($site['url']); ?>"<?php echo $site_leave_attrs($site); ?>>
                        <span class="grid size-9 shrink-0 place-items-center rounded-sm border border-line bg-white text-sm font-black text-navy" aria-hidden="true"><?php echo esc_html($site['mark']); ?></span>
                        <span class="min-w-0 flex-1"><span class="block text-sm font-black text-ink"><?php echo esc_html($site['label']); ?></span><span class="block text-xs font-normal text-slate-500"><?php echo esc_html($site['description'] !== '' ? $site['description'] : $site['domain']); ?></span></span>
                        <?php echo boblift_icon('arrow-up-right', 'h-4 w-4 shrink-0 text-slate-500'); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <div id="site-search" class="absolute inset-x-0 top-full z-[230] hidden border-b border-line bg-white shadow-raised" role="dialog" aria-label="<?php echo esc_attr($search_ui['panelLabel'] ?? 'Site search'); ?>" data-search-panel>
        <div class="page-shell py-5 md:py-8">
            <form class="flex gap-2 md:gap-3" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                <label class="relative min-w-0 flex-1">
                    <span class="sr-only"><?php echo esc_html($search_ui['inputLabel'] ?? 'Search products and articles'); ?></span>
                    <?php echo boblift_icon('search', 'pointer-events-none absolute start-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-500 md:start-5'); ?>
                    <input class="h-12 w-full rounded-sm border border-[#D8DEE8] bg-white ps-11 pe-4 text-base font-bold text-ink outline-none transition placeholder:font-normal placeholder:text-slate-400 focus:border-navy focus:ring-4 focus:ring-blue-100 md:h-14 md:ps-14 md:text-lg [&::-webkit-search-cancel-button]:hidden" type="search" name="s" value="<?php echo esc_attr($search_query); ?>" placeholder="<?php echo esc_attr($search_ui['placeholder'] ?? 'Search a model, crane type or topic'); ?>" autocomplete="off" enterkeyhint="search" data-search-input>
                </label>
                <button class="inline-flex h-12 shrink-0 items-center justify-center rounded-sm bg-navy px-5 text-sm font-black text-white transition hover:bg-[#0b2d5b] md:h-14 md:px-8 md:text-base" type="submit"><?php echo esc_html($search_ui['submitLabel'] ?? 'Search'); ?></button>
            </form>
            <?php if ($product_menu_families) : ?>
                <div class="mt-4 flex flex-wrap items-center gap-2 md:mt-5">
                    <span class="me-1 w-full text-xs font-black uppercase tracking-[0.14em] text-slate-500 sm:w-auto"><?php echo esc_html($search_ui['browseLabel'] ?? 'Browse by category'); ?></span>
                    <?php foreach ($product_menu_families as $family) : ?>
                        <a class="inline-flex h-9 items-center gap-2 rounded-sm border border-line bg-white px-3 text-sm font-bold text-navy transition hover:border-blue-300 hover:text-blue-700" href="<?php echo esc_url($family['url']); ?>"><?php echo esc_html($family['title']); ?><span class="text-xs font-bold tabular-nums text-slate-500"><?php echo esc_html((string) $family['count']); ?></span></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>
<main>
