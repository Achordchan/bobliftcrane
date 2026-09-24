<?php
/** Theme header. */
$options = boblift_options();
$branding = $options['branding'] ?? [];
$top_bar = is_array($options['top_bar'] ?? null) ? $options['top_bar'] : [];
$show_top_bar = !array_key_exists('enabled', $top_bar) || (string) ($top_bar['enabled'] ?? '1') === '1';
$nav_items = $options['nav_items'] ?? [];
$product_groups = $options['product_nav_groups'] ?? [];
$contact = $options['contact'] ?? [];
$header_ui = function_exists('boblift_site_ui') ? boblift_site_ui('header') : [];
$quote_form_ui = function_exists('boblift_site_ui') ? boblift_site_ui('quoteForm') : [];
$flag_alt_template = (string) ($header_ui['languageFlagAltTemplate'] ?? '{language} flag');
$current_path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$active_language_code = function_exists('pll_current_language') ? (string) pll_current_language('slug') : 'en';
if ($active_language_code === '') {
    $active_language_code = 'en';
}
$news_ui = function_exists('boblift_site_ui') ? boblift_site_ui('newsPage') : [];
$news_category_slugs = function_exists('boblift_news_category_source_slugs') ? boblift_news_category_source_slugs() : ['company-news', 'product-news', 'industry-insight', 'marine-solution'];
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
    ['label' => 'Global Customer Visits', 'href' => '/global-customer-visits.html', 'slug' => 'global-customer-visits'],
    ['label' => 'Social Media', 'href' => '/social-media.html', 'slug' => 'social-media'],
    ['label' => 'Public Welfare', 'href' => '/public-welfare.html', 'slug' => 'public-welfare'],
    ['label' => 'Contact Us', 'href' => '/contact.html', 'slug' => 'contact'],
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
    $page = get_page_by_path($slug, OBJECT, 'page');
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
$nav_items = array_values(array_filter(array_map(static function (mixed $item) use ($about_nav_children, $is_about_nav_item, $is_contact_nav_item): ?array {
    if (!is_array($item)) {
        return null;
    }
    if ($is_contact_nav_item($item)) {
        return null;
    }
    if ($is_about_nav_item($item)) {
        $item['children'] = $about_nav_children;
    }
    return $item;
}, $nav_items)));
?><!doctype html>
<html lang="<?php echo esc_attr($active_language_code); ?>" dir="ltr" class="font-sans">
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
<header id="top" class="relative z-[200]">
    <?php if ($show_top_bar) : ?>
        <div class="bg-navy px-4 py-3 text-center text-[11px] font-semibold leading-5 text-white/85 md:text-xs"><span class="text-gold"><?php echo esc_html($top_bar['highlight'] ?? 'Global Lifting Solutions.'); ?></span> <?php echo esc_html($top_bar['text'] ?? 'Exported to 120+ Countries Worldwide.'); ?><a class="ml-3 text-gold md:ml-5" href="<?php echo esc_url(boblift_url($top_bar['link_href'] ?? '/about')); ?>"><?php echo esc_html($top_bar['link_label'] ?? 'Learn More +'); ?></a></div>
    <?php endif; ?>
    <div class="relative border-b border-line bg-white/96 shadow-card backdrop-blur">
        <div class="page-shell flex h-[78px] items-center justify-between gap-3 lg:h-[92px] lg:gap-4 2xl:gap-8">
            <a class="flex shrink-0 items-center" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr($header_ui['homeAriaLabel'] ?? 'BOB-LIFT Home'); ?>">
                <?php echo boblift_img_from_setting($branding['logo'] ?? 'images/bob-lift-logo.png', 'images/bob-lift-logo.png', $branding['logo_alt'] ?? 'BOB-LIFT Cranes', 'h-[52px] w-auto object-contain lg:h-[58px]'); ?>
            </a>
            <nav class="hidden min-w-0 items-center gap-1 lg:flex 2xl:gap-2" aria-label="<?php echo esc_attr($header_ui['mainNavigationLabel'] ?? 'Main navigation'); ?>">
                <?php foreach ($nav_items as $nav_index => $item) :
                    $href = (string) ($item['href'] ?? '/');
                    $news_by_href = $is_news_nav_item($item);
                    $item_url = !empty($item['has_menu']) && !$news_by_href ? boblift_product_archive_url() : boblift_url($href);
                    $is_news_item = $news_by_href || $is_news_nav_item($item, $item_url);
                    $is_product_menu_item = !empty($item['has_menu']) && !$is_news_item;
                    $children = is_array($item['children'] ?? null) ? array_values(array_filter($item['children'], static fn($child): bool => is_array($child) && trim((string) ($child['label'] ?? '')) !== '')) : [];
                    $has_children = $children !== [];
                    $item_path = trim(parse_url($item_url, PHP_URL_PATH) ?: '/', '/');
                    $active = $href === '/' ? $current_path === '' : ($item_path !== '' && str_starts_with($current_path, $item_path));
                    if ($is_product_menu_item && (is_post_type_archive('product') || is_singular('product') || get_query_var('boblift_product_category'))) {
                        $active = true;
                    }
                    if ($is_news_item && ($current_path === 'news' || $current_path === 'news.html' || is_page_template('page-news.php') || is_singular('post'))) {
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
                    $pill = 'group relative flex h-10 shrink-0 items-center gap-1 whitespace-nowrap px-2 text-[14px] font-black leading-none transition duration-300 min-[1200px]:text-[16px] min-[1400px]:gap-1.5 min-[1400px]:px-3 2xl:px-4 2xl:text-[16px] after:absolute after:-bottom-1 after:left-3 after:right-3 after:h-0.5 after:origin-center after:rounded-full after:bg-gold after:transition after:duration-300 ' . ($active ? 'text-ink after:scale-x-100' : 'text-slate-600 after:scale-x-0 hover:text-ink hover:after:scale-x-100');
                    ?>
                    <?php if ($is_product_menu_item) : ?>
                        <div class="group/products static" data-mega-root>
                            <a class="<?php echo esc_attr($pill); ?>" href="<?php echo esc_url($item_url); ?>" aria-haspopup="true"><?php echo esc_html(function_exists('boblift_localized_static_text') ? boblift_localized_static_text((string) $item['label']) : (string) $item['label']); ?><?php echo boblift_icon('chevron-down', 'h-3.5 w-3.5 text-gold transition group-hover/products:rotate-180'); ?></a>
                            <div class="mega-menu absolute left-1/2 top-full z-[210] w-[min(1560px,calc(100vw-80px))] -translate-x-1/2">
                                <div class="relative max-h-[calc(100vh-132px)] overflow-hidden rounded border border-[#d8e7f7] bg-white shadow-raised ring-1 ring-white/80" data-product-nav="desktop">
                                    <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(135deg,rgba(255,255,255,0.98)_0%,rgba(247,251,255,0.94)_100%)]"></div>
                                    <div class="relative grid max-h-[calc(100vh-280px)] grid-cols-3 items-stretch gap-4 overflow-y-auto p-4 pr-3 2xl:max-h-[calc(100vh-250px)] 2xl:gap-6 2xl:p-6 2xl:pr-5">
                                        <?php foreach ($product_groups as $group_index => $group) : $featured = $group_index < 2; ?>
                                            <div class="relative flex h-full min-h-[500px] flex-col overflow-hidden rounded border border-[#dbe8f5] bg-white/92 shadow-card 2xl:min-h-[590px] <?php echo $featured ? 'p-0' : 'p-5 2xl:p-6'; ?>">
                                                <?php if ($featured) : ?>
                                                    <a class="group/title relative block h-[clamp(260px,19vw,310px)] w-full shrink-0 overflow-hidden border-b border-[#dbe8f5] bg-[#f4f8fd] 2xl:h-[clamp(300px,17vw,340px)]" href="<?php echo esc_url(boblift_product_nav_url((string) ($group['href'] ?? '/products'), (string) ($group['title'] ?? ''))); ?>">
                                                        <?php echo boblift_img_from_setting($group['image'] ?? 'images/telescopic-banner.jpg', 'images/telescopic-banner.jpg', '', 'h-full w-full object-cover object-center'); ?>
                                                        <span class="absolute inset-0 bg-[linear-gradient(115deg,rgba(255,255,255,0.96)_0%,rgba(255,255,255,0.78)_42%,rgba(255,255,255,0.18)_100%)]"></span>
                                                        <span class="absolute left-4 top-4 flex items-start gap-3 2xl:left-6 2xl:top-6 2xl:gap-4"><span class="grid h-[52px] w-[52px] shrink-0 place-items-center rounded-sm bg-blue-700 shadow-card min-[1700px]:h-16 min-[1700px]:w-16 2xl:h-[68px] 2xl:w-[68px]"><?php echo boblift_img('images/mega-menu-icons/' . ($group['iconName'] ?? $group['icon'] ?? 'attachments') . '.png', '', 'h-8 w-8 object-contain brightness-0 invert min-[1700px]:h-11 min-[1700px]:w-11'); ?></span><span class="min-w-0 max-w-[300px] pt-1"><strong class="block text-[clamp(18px,1.12vw,26px)] font-black leading-tight tracking-[-0.015em] text-navy"><?php echo esc_html($group['title']); ?></strong><span class="mt-1.5 block text-[clamp(12px,0.72vw,15px)] font-semibold leading-5 text-slate-600"><?php echo esc_html($group['description']); ?></span><span class="mt-3 block h-1 w-12 rounded-full bg-blue-600"></span></span></span>
                                                    </a>
                                                <?php else : ?>
                                                    <a class="group/title flex items-center gap-4" href="<?php echo esc_url(boblift_product_nav_url((string) ($group['href'] ?? '/products'), (string) ($group['title'] ?? ''))); ?>"><span class="grid h-14 w-14 shrink-0 place-items-center rounded-sm bg-blue-700 shadow-card"><?php echo boblift_img('images/mega-menu-icons/' . ($group['iconName'] ?? $group['icon'] ?? 'attachments') . '.png', '', 'h-9 w-9 object-contain brightness-0 invert'); ?></span><span class="min-w-0"><strong class="block text-[clamp(18px,1vw,22px)] font-black leading-tight tracking-[-0.015em] text-navy"><?php echo esc_html($group['title']); ?></strong><span class="mt-1.5 block text-[clamp(13px,0.76vw,15px)] font-semibold leading-5 text-slate-500"><?php echo esc_html($group['description']); ?></span><span class="mt-3 block h-1 w-10 rounded-full bg-blue-600"></span></span></a>
                                                <?php endif; ?>
                                                <div class="grid flex-1 content-start gap-0 <?php echo $featured ? 'p-3.5 2xl:p-5' : 'mt-4 2xl:mt-5'; ?>">
                                                    <?php foreach (($group['links'] ?? []) as $link_index => $link) : ?>
                                                        <a class="group/item relative flex min-h-[50px] items-center gap-3 border-b border-[#e2ebf5] px-1 py-2 text-navy transition duration-200 ease-out last:border-b-0 hover:text-blue-700 2xl:min-h-[58px] 2xl:gap-4 2xl:py-2.5 <?php echo $group_index === 0 && $link_index === 0 ? 'text-blue-700' : ''; ?>" href="<?php echo esc_url(boblift_product_nav_url((string) ($link['href'] ?? '/products'), (string) ($link['label'] ?? ''))); ?>"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-sm bg-[#f3f7fc] text-navy/75 transition duration-200 group-hover/item:bg-blue-50 2xl:h-12 2xl:w-12 "><?php echo boblift_img('images/mega-menu-icons/' . ($link['iconName'] ?? $link['icon'] ?? 'truck-crane') . '.png', '', 'h-7 w-7 object-contain 2xl:h-8 2xl:w-8'); ?></span><span class="min-w-0 flex-1 text-[clamp(13px,0.8vw,16px)] font-black leading-[1.16] tracking-[-0.01em]"><?php echo esc_html($link['label']); ?></span><span class="shrink-0 -rotate-90 text-blue-600 transition duration-200 group-hover/item:translate-x-1"><?php echo boblift_icon('chevron-down', 'h-4 w-4'); ?></span></a>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="relative border-t border-[#dbe8f5] bg-[#f4f8fd]/94 px-5 py-3.5 2xl:px-7">
                                        <div class="flex flex-wrap items-center justify-between gap-4">
                                            <div class="flex min-w-[260px] items-center gap-4">
                                                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-sm bg-white text-navy"><?php echo boblift_img('images/mega-menu-icons/catalog.png', '', 'h-8 w-8 object-contain'); ?></span>
                                                <span><span class="block text-[17px] font-black leading-tight text-navy"><?php echo esc_html($header_ui['megaTitle'] ?? 'Explore All Products'); ?></span><span class="mt-1 block text-sm font-semibold text-slate-500"><?php echo esc_html($header_ui['megaDescription'] ?? 'Discover our full range of lifting solutions.'); ?></span></span>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-x-5 gap-y-3 text-sm font-black text-navy 2xl:text-base">
                                                <a class="inline-flex items-center gap-4 text-navy transition hover:text-blue-700" href="mailto:<?php echo esc_attr($contact['email'] ?? 'info@bobliftcrane.com'); ?>"><span class="grid h-10 w-10 place-items-center rounded-sm border border-[#d8e7f7] bg-white text-navy"><?php echo boblift_icon('mail', 'h-5 w-5'); ?></span><?php echo esc_html($contact['email'] ?? 'info@bobliftcrane.com'); ?></a>
                                                <a class="inline-flex items-center gap-4 text-navy transition hover:text-blue-700" href="https://wa.me/<?php echo esc_attr(preg_replace('/\D+/', '', $contact['whatsapp'] ?? $contact['phone'] ?? '8615852499415')); ?>"><span class="grid h-10 w-10 place-items-center rounded-sm border border-[#d8e7f7] bg-white text-navy"><?php echo boblift_icon('phone', 'h-5 w-5'); ?></span><?php echo esc_html($contact['phone'] ?? '+86 15852499415'); ?></a>
                                            </div>
                                            <a class="group/catalog inline-flex h-12 shrink-0 items-center gap-3 rounded-sm bg-blue-700 px-6 text-sm font-black text-white shadow-card transition hover:-translate-y-0.5 hover:bg-blue-800 2xl:text-base" href="<?php echo esc_url(boblift_product_archive_url()); ?>"><?php echo esc_html($header_ui['megaButtonLabel'] ?? 'View Product Catalog'); ?><span class="grid h-7 w-7 place-items-center rounded-sm border border-white/60 text-white transition group-hover/catalog:translate-x-0.5"><?php echo boblift_icon('arrow-right', 'h-4 w-4 -rotate-45'); ?></span></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($has_children) : ?>
                        <div class="group static" data-secondary-nav-root>
                            <a class="<?php echo esc_attr($pill); ?>" href="<?php echo esc_url($item_url); ?>" aria-haspopup="true" aria-expanded="false" data-secondary-nav-trigger><?php echo esc_html(function_exists('boblift_localized_static_text') ? boblift_localized_static_text((string) $item['label']) : (string) $item['label']); ?><?php echo boblift_icon('chevron-down', 'h-3.5 w-3.5 text-gold transition group-hover:rotate-180 group-focus-within:rotate-180'); ?></a>
                            <div class="pointer-events-none invisible absolute left-0 right-0 top-full z-[214] opacity-0 transition duration-200 group-hover:pointer-events-auto group-hover:visible group-hover:opacity-100 group-focus-within:pointer-events-auto group-focus-within:visible group-focus-within:opacity-100" data-secondary-nav-bar data-secondary-nav-menu>
                                <div class="relative border-y border-blue-100 bg-[linear-gradient(180deg,#f8fbff_0%,#ffffff_100%)] shadow-raised">
                                    <div class="page-shell flex h-[68px] items-center justify-center overflow-x-auto">
                                        <nav class="flex min-w-max items-center justify-center whitespace-nowrap" aria-label="<?php echo esc_attr(function_exists('boblift_localized_static_text') ? boblift_localized_static_text((string) ($item['label'] ?? 'Secondary')) : (string) ($item['label'] ?? 'Secondary')); ?> secondary navigation">
                                            <?php foreach ($children as $child_index => $child) : $child_href = (string) ($child['href'] ?? '#'); $child_active = $nav_href_matches_current($child_href); ?>
                                                <?php if ($child_index === 1) : ?><span class="mx-7 h-7 w-px bg-slate-300" aria-hidden="true"></span><?php elseif ($child_index > 1) : ?><span class="mx-5" aria-hidden="true"></span><?php endif; ?>
                                                <a class="text-[16px] font-semibold leading-none transition hover:text-blue-700 <?php echo $child_active ? 'font-black text-ink' : 'text-slate-600'; ?>" href="<?php echo esc_url(boblift_url($child_href)); ?>"><?php echo esc_html(function_exists('boblift_localized_static_text') ? boblift_localized_static_text((string) ($child['label'] ?? '')) : (string) ($child['label'] ?? '')); ?></a>
                                            <?php endforeach; ?>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($is_news_item && $news_menu_items !== []) : ?>
                        <div class="group static" data-secondary-nav-root>
                            <a class="<?php echo esc_attr($pill); ?>" href="<?php echo esc_url($item_url); ?>" aria-haspopup="true" aria-expanded="false" data-secondary-nav-trigger><?php echo esc_html(function_exists('boblift_localized_static_text') ? boblift_localized_static_text((string) $item['label']) : (string) $item['label']); ?><?php echo boblift_icon('chevron-down', 'h-3.5 w-3.5 text-gold transition group-hover:rotate-180 group-focus-within:rotate-180'); ?></a>
                            <div class="pointer-events-none invisible absolute left-0 right-0 top-full z-[214] opacity-0 transition duration-200 group-hover:pointer-events-auto group-hover:visible group-hover:opacity-100 group-focus-within:pointer-events-auto group-focus-within:visible group-focus-within:opacity-100" data-secondary-nav-bar data-secondary-nav-menu>
                                <div class="relative border-y border-blue-100 bg-[linear-gradient(180deg,#f8fbff_0%,#ffffff_100%)] shadow-raised">
                                    <div class="page-shell flex h-[68px] items-center justify-center overflow-x-auto">
                                        <nav class="flex min-w-max items-center justify-center whitespace-nowrap" aria-label="News secondary navigation">
                                            <?php foreach ($news_menu_items as $news_index => $news_menu_item) : ?>
                                                <?php if ($news_index === 1) : ?><span class="mx-7 h-7 w-px bg-slate-300" aria-hidden="true"></span><?php elseif ($news_index > 1) : ?><span class="mx-5" aria-hidden="true"></span><?php endif; ?>
                                                <a class="text-[16px] font-semibold leading-none transition hover:text-blue-700 <?php echo !empty($news_menu_item['active']) ? 'font-black text-ink' : 'text-slate-600'; ?>" href="<?php echo esc_url((string) $news_menu_item['href']); ?>"><?php echo esc_html((string) $news_menu_item['label']); ?></a>
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
            <div class="flex items-center gap-2 md:gap-3 xl:gap-2 2xl:gap-3">
                <div class="relative" data-language-root>
                    <button class="group flex h-12 items-center gap-2.5 rounded-sm border border-line bg-white px-3 text-left transition hover:border-ink/30 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-gold/20 aria-expanded:border-ink/40 max-[360px]:h-11 max-[360px]:w-11 max-[360px]:justify-center max-[360px]:p-0 md:min-w-[148px] md:px-4" type="button" aria-label="<?php echo esc_attr($header_ui['changeLanguageLabel'] ?? 'Change language'); ?>" aria-expanded="false" aria-controls="language-menu" data-language-toggle>
                        <img class="h-4 w-6 shrink-0 rounded-sm object-cover object-left ring-1 ring-ink/10" src="<?php echo esc_url(boblift_language_flag_url((string) ($active_language['flag'] ?? 'flags/us.svg'))); ?>" alt="<?php echo esc_attr(str_replace('{language}', (string) ($active_language['label'] ?? 'English'), $flag_alt_template)); ?>" loading="lazy">
                        <strong class="block min-w-0 flex-1 truncate whitespace-nowrap text-sm font-black text-ink max-[360px]:hidden"><?php echo esc_html($active_language['label'] ?? 'English'); ?></strong>
                        <?php echo boblift_icon('chevron-down', 'h-4 w-4 shrink-0 text-slate-400 transition group-aria-expanded:rotate-180 max-[360px]:hidden'); ?>
                    </button>
                    <div id="language-menu" class="absolute right-0 top-full z-[220] hidden pt-2" data-language-menu>
                        <div class="w-60 overflow-hidden rounded border border-line bg-white py-1.5 shadow-raised">
                            <?php foreach ($languages as $language) :
                                $language_label = (string) ($language['label'] ?? 'English');
                                $language_code = (string) ($language['code'] ?? 'en');
                                $language_market = (string) ($language['market'] ?? ($header_ui['regionalMarketLabel'] ?? 'Regional Market'));
                                $selected = !empty($language['current']);
                                ?>
                                <a class="relative flex h-11 items-center gap-3 px-4 text-sm outline-none transition hover:bg-mist hover:text-ink focus-visible:bg-mist <?php echo $selected ? 'bg-mist font-black text-ink before:absolute before:inset-y-2 before:left-0 before:w-0.5 before:bg-gold' : 'font-semibold text-slate-600'; ?>" href="<?php echo esc_attr((string) ($language['href'] ?? '/')); ?>" hreflang="<?php echo esc_attr($language_code); ?>" title="<?php echo esc_attr($language_market); ?>" <?php echo $selected ? 'aria-current="true"' : ''; ?>>
                                    <img class="h-4 w-6 shrink-0 rounded-sm object-cover object-left ring-1 ring-ink/10" src="<?php echo esc_url(boblift_language_flag_url((string) ($language['flag'] ?? 'flags/us.svg'))); ?>" alt="<?php echo esc_attr(str_replace('{language}', $language_label, $flag_alt_template)); ?>" loading="lazy">
                                    <span class="min-w-0 flex-1 truncate"><?php echo esc_html($language_label); ?></span>
                                    <span class="text-[11px] font-bold uppercase tracking-[0.12em] <?php echo $selected ? 'text-ink' : 'text-slate-400'; ?>"><?php echo esc_html(strtoupper($language_code)); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <button class="grid h-11 w-11 place-items-center rounded-sm border border-navy/10 bg-navy text-white shadow-card transition duration-300 hover:-translate-y-0.5 lg:hidden" type="button" data-mobile-toggle aria-expanded="false" aria-controls="mobile-menu" aria-label="<?php echo esc_attr($header_ui['mobileOpenLabel'] ?? 'Open mobile navigation'); ?>"><?php echo boblift_icon('menu', 'h-5 w-5', '2.4'); ?></button>
                <button class="group hidden h-12 min-w-[6.75rem] items-center justify-center gap-1.5 whitespace-nowrap rounded-sm border border-transparent bg-navy bg-clip-padding px-4 text-sm font-black text-white shadow-card outline-none select-none transition-all hover:-translate-y-0.5 hover:bg-navy lg:inline-flex 2xl:min-w-[11.75rem] 2xl:px-5" type="button" data-inquiry-modal-open data-inquiry-source="顶部导航"><span class="2xl:hidden"><?php echo esc_html($header_ui['quoteShortLabel'] ?? 'Quote'); ?></span><span class="hidden 2xl:inline"><?php echo esc_html($header_ui['quoteLabel'] ?? ($quote_form_ui['label'] ?? 'Request Quote')); ?></span><span class="grid h-6 w-6 place-items-center rounded-sm bg-white/12 text-white transition md:h-7 md:w-7"><?php echo boblift_icon('check', 'h-4 w-4'); ?></span></button>
            </div>
        </div>
    </div>
    <div id="mobile-menu" class="mobile-menu absolute right-3 top-[calc(100%+10px)] z-50 hidden max-h-[min(calc(100dvh-164px),680px)] w-[min(350px,calc(100vw-24px))] overflow-y-auto overscroll-contain rounded border border-line bg-white p-1.5 shadow-raised sm:right-4 sm:w-[min(360px,calc(100vw-32px))] lg:hidden">
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
                            <button class="group -my-3 -mr-4 ml-auto grid h-11 w-12 shrink-0 place-items-center text-slate-300 transition hover:text-blue-600" type="button" aria-expanded="false" aria-controls="mobile-products-submenu" aria-label="<?php echo esc_attr($header_ui['expandProductCategoriesLabel'] ?? 'Expand product categories'); ?>" data-mobile-submenu-toggle>
                                <?php echo boblift_icon('chevron-down', 'h-4 w-4 transition group-aria-expanded:rotate-180'); ?>
                            </button>
                        </div>
                        <div id="mobile-products-submenu" class="mx-1.5 mb-2 hidden rounded border border-line bg-[#f8fbff] p-2.5" data-product-nav="mobile" data-mobile-submenu-panel>
                            <p class="px-2 pb-2 text-[10px] font-black uppercase tracking-[0.18em] text-gold"><?php echo esc_html($header_ui['productCategoriesLabel'] ?? 'Product Categories'); ?></p>
                            <div class="grid gap-3">
                                <?php foreach ($product_groups as $group_index => $group) : ?>
                                    <div>
                                        <a class="block rounded bg-white px-3 py-2 text-[13px] font-black leading-4 text-ink shadow-card" href="<?php echo esc_url(boblift_product_nav_url((string) ($group['href'] ?? '/products'), (string) ($group['title'] ?? ''))); ?>"><?php echo esc_html($group['title'] ?? 'Products'); ?></a>
                                        <div class="mt-1 grid gap-1 pl-2">
                                            <?php foreach (($group['links'] ?? []) as $link) : ?><a class="rounded px-3 py-1.5 text-xs font-bold leading-[18px] text-slate-500 hover:bg-white hover:text-blue-700" href="<?php echo esc_url(boblift_product_nav_url((string) ($link['href'] ?? '/products'), (string) ($link['label'] ?? ''))); ?>"><?php echo esc_html($link['label'] ?? 'Product'); ?></a><?php endforeach; ?>
                                        </div>
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
                                    <a class="flex items-center justify-between rounded px-3 py-2 text-xs font-bold leading-[18px] transition hover:bg-white hover:text-blue-700 <?php echo !empty($news_menu_item['active']) ? 'bg-white text-blue-700' : 'text-slate-500'; ?>" href="<?php echo esc_url((string) $news_menu_item['href']); ?>">
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
    </div>
</header>
<main>
