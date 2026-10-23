<?php
/**
 * Template Name: HTML Sitemap
 *
 * Pages grouped by purpose, products grouped like the Products menu (family > category > product),
 * then news and customer cases. Empty categories are left out.
 */
get_header();
$sitemap_ui = function_exists('boblift_sitemap_page_options') ? boblift_sitemap_page_options() : [];
$groups_ui = boblift_site_ui('sitemapGroups');
// Copy an editor saved on the old Sitemap settings stays on the page; values equal to the old built-in copy (English or
// localized) are not customisations. Fields the redesign dropped are mapped to the nearest new place.
$saved_sitemap = boblift_option('sitemap_page', []);
$saved_sitemap = is_array($saved_sitemap) ? $saved_sitemap : [];
$legacy_sitemap_defaults = [
    'products_title' => ['Products'],
    'product_range_title' => ['Products and categories'],
    'product_categories_title' => ['Product Categories'],
    'product_range_label' => ['Product Range'],
    'news_title' => ['News'],
    'news_categories_title' => ['News Categories'],
    'cases_title' => ['Cases'],
    'auto_label' => ['Updated'],
    'auto_value' => ['Auto'],
    'help_title' => ['How this updates'],
    'html_note_label' => ['HTML Sitemap:'],
    'html_note' => ['generated from WordPress queries, so published pages, products, news and cases appear automatically.'],
    'xml_note_label' => ['XML Sitemap:'],
    'xml_note' => ['use WordPress Core at /sitemap.xml, or a mature SEO plugin such as Yoast SEO or Rank Math on production.', 'uses WordPress Core, or a mature SEO plugin such as Yoast SEO or Rank Math on production.'],
];
$legacy = [];
foreach ($legacy_sitemap_defaults as $legacy_key => $legacy_defaults) {
    $value = trim((string) ($saved_sitemap[$legacy_key] ?? ''));
    $forms = array_merge($legacy_defaults, function_exists('boblift_localized_static_text') ? array_map('boblift_localized_static_text', $legacy_defaults) : []);
    $legacy[$legacy_key] = in_array($value, $forms, true) ? '' : $value;
}
// Section headings: the old product and news headings (section, range and category titles) feed the single new heading.
foreach ([
    'productsTitle' => ['products_title', 'product_range_title', 'product_categories_title'],
    'newsTitle' => ['news_title', 'news_categories_title'],
    'casesTitle' => ['cases_title'],
] as $ui_key => $legacy_keys) {
    foreach ($legacy_keys as $legacy_key) {
        if ($legacy[$legacy_key] !== '') {
            $groups_ui[$ui_key] = $legacy[$legacy_key];
            break;
        }
    }
}
$legacy_products_label = $legacy['product_range_label'];
// The old page ended with a "How this updates" note (and showed an "Updated: Auto" figure); customised text stays as notes.
$legacy_help = ['help_title' => $legacy['help_title']];
$legacy_help_notes = array_values(array_filter([
    ['label' => $legacy['auto_label'], 'text' => $legacy['auto_value']],
    ['label' => $legacy['html_note_label'], 'text' => $legacy['html_note']],
    ['label' => $legacy['xml_note_label'], 'text' => $legacy['xml_note']],
], static fn(array $note): bool => $note['text'] !== '' || $note['label'] !== ''));
$show_legacy_help = $legacy_help['help_title'] !== '' || $legacy_help_notes !== [];

$sitemap_label = static function (string $label): string {
    if (function_exists('boblift_localize_product_text_fragments')) {
        $label = (string) boblift_localize_product_text_fragments($label);
    }
    return function_exists('boblift_localized_static_text') ? boblift_localized_static_text($label) : $label;
};

// Pages, sorted into four columns by their English slug; anything new lands under Company.
$page_columns = [
    'company' => ['title' => (string) ($groups_ui['companyTitle'] ?? 'Company'), 'slugs' => ['about', 'why-choose-us', 'factory-tour', 'global-customer-visits', 'social-media'], 'links' => []],
    'services' => ['title' => (string) ($groups_ui['servicesTitle'] ?? 'Service and support'), 'slugs' => ['quality-testing', 'mounting-shipping', 'service', 'videos', 'contact'], 'links' => []],
    'resources' => ['title' => (string) ($groups_ui['resourcesTitle'] ?? 'Products, cases and news'), 'slugs' => ['products', 'cases', 'news'], 'links' => []],
    'legal' => ['title' => (string) ($groups_ui['legalTitle'] ?? 'Legal'), 'slugs' => ['privacy-policy', 'terms-of-use', 'sitemap'], 'links' => []],
];
$front_id = (int) get_option('page_on_front');
if ($front_id > 0 && function_exists('pll_get_post')) {
    $front_id = (int) (pll_get_post($front_id) ?: $front_id);
}
$pages = get_pages([
    'sort_column' => 'menu_order,post_title',
    'post_status' => 'publish',
    'exclude' => function_exists('boblift_utility_page_ids') ? boblift_utility_page_ids() : [],
]);
$page_rows = [];
foreach ($pages as $page) {
    if (!$page instanceof WP_Post) {
        continue;
    }
    $english_id = function_exists('pll_get_post') ? (int) (pll_get_post($page->ID, 'en') ?: $page->ID) : $page->ID;
    $slug = (string) get_post_field('post_name', $english_id);
    $column = 'company';
    $order = 99;
    foreach ($page_columns as $key => $definition) {
        $position = array_search($slug, $definition['slugs'], true);
        if ($position !== false) {
            $column = $key;
            $order = (int) $position;
            break;
        }
    }
    if ($page->ID === $front_id) {
        $order = -1;
    }
    $page_rows[] = [$column, $order, $sitemap_label(get_the_title($page)), get_permalink($page)];
}
usort($page_rows, static fn(array $a, array $b): int => $a[1] <=> $b[1]);
foreach ($page_rows as [$column, , $label, $url]) {
    $page_columns[$column]['links'][] = [$label, $url];
}
$page_columns = array_filter($page_columns, static fn(array $column): bool => $column['links'] !== []);

// Products: one block per category that holds products, labelled with its family.
$products_in = static function (WP_Term $term): array {
    $posts = get_posts([
        'post_type' => 'product',
        'post_status' => 'publish',
        'numberposts' => -1,
        'tax_query' => [['taxonomy' => 'product_category', 'field' => 'term_id', 'terms' => $term->term_id, 'include_children' => false]],
    ]);
    usort($posts, static fn(WP_Post $a, WP_Post $b): int => strnatcasecmp($a->post_title, $b->post_title));
    return $posts;
};
$product_blocks = [];
$seen_products = [];
$product_total = 0;
// The sitemap follows the real category tree, whatever labels or groups the navigation menu uses.
foreach (function_exists('boblift_build_product_menu_families') ? boblift_build_product_menu_families() : [] as $family) {
    $family_term = get_term_by('slug', (string) ($family['slug'] ?? ''), 'product_category');
    $family_term = $family_term instanceof WP_Term ? $family_term : null;
    // A synthetic family (a group with no term of its own) lists the child categories of its group definition.
    $root_terms = [];
    if ($family_term === null && function_exists('boblift_product_category_hierarchy')) {
        foreach (boblift_product_category_hierarchy() as $group) {
            if ((string) ($group['slug'] ?? '') !== (string) ($family['slug'] ?? '')) {
                continue;
            }
            foreach ((array) ($group['children'] ?? []) as $child) {
                $child_term = get_term_by('slug', (string) ($child['slug'] ?? ''), 'product_category');
                if ($child_term instanceof WP_Term) {
                    $root_terms[] = $child_term;
                }
            }
        }
        if ($root_terms === []) {
            continue;
        }
    }
    $child_order = array_flip(array_map(static fn(array $child): string => (string) ($child['title'] ?? ''), (array) ($family['children'] ?? [])));
    $children = $root_terms;
    foreach ($family_term ? [$family_term] : $root_terms as $root_term) {
        $descendants = get_terms(['taxonomy' => 'product_category', 'child_of' => $root_term->term_id, 'hide_empty' => false]); // every descendant, so products in deeper categories are listed too
        $children = array_merge($children, is_wp_error($descendants) ? [] : array_values(array_filter($descendants, static fn($term): bool => $term instanceof WP_Term)));
    }
    usort($children, static fn(WP_Term $a, WP_Term $b): int => ($child_order[$sitemap_label($a->name)] ?? 99) <=> ($child_order[$sitemap_label($b->name)] ?? 99));
    $family_title = (string) ($family['title'] ?? ($family_term ? $family_term->name : ''));
    $family_url = (string) ($family['url'] ?? ($family_term ? get_term_link($family_term) : boblift_product_archive_url()));
    foreach (array_merge($children, $family_term ? [$family_term] : []) as $term) {
        $posts = array_values(array_filter($products_in($term), static fn(WP_Post $post): bool => !isset($seen_products[$post->ID])));
        if ($posts === []) {
            continue;
        }
        foreach ($posts as $post) {
            $seen_products[$post->ID] = true;
        }
        $product_total += count($posts);
        $is_family = $family_term !== null && $term->term_id === $family_term->term_id;
        $term_url = get_term_link($term);
        $product_blocks[] = [
            'eyebrow' => $children !== [] || !$is_family ? $family_title : (function_exists('boblift_product_family_group_label') ? boblift_product_family_group_label((string) ($family['group'] ?? '')) : ''),
            'eyebrow_url' => $children !== [] || !$is_family ? $family_url : '',
            'title' => $is_family && $children !== [] ? $family_title : $sitemap_label($term->name),
            'url' => is_string($term_url) ? $term_url : $family_url,
            'links' => array_map(static fn(WP_Post $post): array => [$sitemap_label(get_the_title($post)), get_permalink($post)], $posts),
        ];
    }
}

// Published products outside every listed category (category assignment is optional) still get their sitemap links.
$unlisted_products = array_values(array_filter(get_posts(['post_type' => 'product', 'post_status' => 'publish', 'numberposts' => -1]), static fn(WP_Post $post): bool => !isset($seen_products[$post->ID])));
if ($unlisted_products !== []) {
    usort($unlisted_products, static fn(WP_Post $a, WP_Post $b): int => strnatcasecmp($a->post_title, $b->post_title));
    $product_total += count($unlisted_products);
    $product_blocks[] = [
        'eyebrow' => '',
        'eyebrow_url' => '',
        'title' => function_exists('boblift_localized_static_text') ? boblift_localized_static_text('Other products') : 'Other products',
        'url' => boblift_product_archive_url(),
        'links' => array_map(static fn(WP_Post $post): array => [$sitemap_label(get_the_title($post)), get_permalink($post)], $unlisted_products),
    ];
}

// The tonnage pages (inc/tonnage-pages.php), each listing one capacity bin across every category.
$sitemap_capacity_links = [];
foreach (function_exists('boblift_tonnage_links') ? boblift_tonnage_links() : [] as $size => [, $capacity_url]) {
    $sitemap_capacity_links[] = [boblift_tonnage_heading((int) $size), $capacity_url];
}
if ($sitemap_capacity_links !== []) {
    $product_blocks[] = [
        'eyebrow' => '',
        'eyebrow_url' => '',
        'title' => boblift_localized_static_text('Browse by capacity'),
        'url' => boblift_product_archive_url() . '#by-capacity',
        'links' => $sitemap_capacity_links,
    ];
}

$news_categories = get_terms(['taxonomy' => 'category', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC']);
$news_categories = is_wp_error($news_categories) ? [] : array_values(array_filter($news_categories, static fn($term): bool => $term instanceof WP_Term && $term->slug !== 'uncategorized'));
$news = get_posts(['post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 80, 'orderby' => 'date', 'order' => 'DESC']);
$cases = get_posts(['post_type' => 'case', 'post_status' => 'publish', 'numberposts' => 80, 'orderby' => 'date', 'order' => 'DESC']);

$total_links = array_sum(array_map(static fn(array $column): int => count($column['links']), $page_columns))
    + $product_total + count($product_blocks) + count($news_categories) + count($news) + count($cases);

$section_heading = static function (string $title, int $count, string $link_label = '', string $link_url = ''): void { ?>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-x-6 gap-y-2 border-b border-line pb-4">
        <h2 class="text-[clamp(1.5rem,2.2vw,2.25rem)] font-black leading-tight tracking-[-0.015em] text-ink"><?php echo esc_html($title); ?> <span class="ml-1 align-middle text-sm font-bold tabular-nums text-slate-500"><?php echo esc_html((string) $count); ?></span></h2>
        <?php if ($link_label !== '') : ?>
            <a class="inline-flex items-center gap-2 text-sm font-bold text-navy transition hover:text-blue-700" href="<?php echo esc_url($link_url); ?>"><?php echo esc_html($link_label); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
        <?php endif; ?>
    </div>
<?php };
?>
<div class="bg-white text-ink">
    <div class="page-shell pt-5 md:pt-8">
        <?php
        boblift_page_hero([
            'label' => (string) ($sitemap_ui['label'] ?? 'HTML Sitemap'),
            'title' => (string) ($sitemap_ui['title'] ?? 'Find BOB-LIFT pages quickly.'),
            'text' => (string) ($sitemap_ui['description'] ?? ''),
            'meta' => $total_links . ' ' . (string) ($sitemap_ui['links_label'] ?? 'Links'),
            'image' => 'images/factory/aerial-site.webp',
            'image_alt' => (string) ($sitemap_ui['title'] ?? ''),
            'image_position' => 'center 50%',
        ]);
        ?>
    </div>

    <div class="page-shell grid gap-12 py-10 md:gap-16 md:py-14">
        <section aria-labelledby="sitemap-pages">
            <h2 id="sitemap-pages" class="sr-only"><?php echo esc_html((string) ($sitemap_ui['core_pages_title'] ?? 'Core Pages')); ?></h2>
            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4 lg:gap-10">
                <?php foreach ($page_columns as $column) : ?>
                    <div>
                        <h3 class="border-b border-line pb-3 text-xs font-black uppercase tracking-[0.14em] text-slate-500"><?php echo esc_html($column['title']); ?></h3>
                        <ul class="mt-3 grid gap-1">
                            <?php foreach ($column['links'] as [$label, $url]) : ?>
                                <li><a class="boblift-sitemap-link inline-block text-base font-bold text-ink" href="<?php echo esc_url((string) $url); ?>"><?php echo esc_html((string) $label); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <?php if ($product_blocks !== []) : ?>
            <section aria-labelledby="sitemap-products">
                <?php if ($legacy_products_label !== '') : ?><p class="mb-3 text-xs font-black uppercase tracking-[0.14em] text-slate-500"><?php echo esc_html($legacy_products_label); ?></p><?php endif; ?>
                <div id="sitemap-products"><?php $section_heading((string) ($groups_ui['productsTitle'] ?? 'Products'), $product_total, (string) ($sitemap_ui['view_products_label'] ?? 'View Products'), boblift_url('/products.html')); ?></div>
                <div class="gap-10 sm:columns-2 lg:columns-3 2xl:columns-4">
                    <?php foreach ($product_blocks as $block) : ?>
                        <div class="boblift-sitemap-group mb-8">
                            <?php if ($block['eyebrow'] !== '') : ?>
                                <p class="text-xs font-black uppercase tracking-[0.14em] text-slate-500"><?php if ($block['eyebrow_url'] !== '') : ?><a class="transition hover:text-blue-700" href="<?php echo esc_url($block['eyebrow_url']); ?>"><?php echo esc_html($block['eyebrow']); ?></a><?php else : echo esc_html($block['eyebrow']); endif; ?></p>
                            <?php endif; ?>
                            <h3 class="mt-1 text-lg font-black leading-snug"><a class="text-ink transition hover:text-blue-700" href="<?php echo esc_url($block['url']); ?>"><?php echo esc_html($block['title']); ?></a> <span class="text-sm font-bold tabular-nums text-slate-500"><?php echo esc_html((string) count($block['links'])); ?></span></h3>
                            <ul class="mt-2 grid gap-0.5">
                                <?php foreach ($block['links'] as [$label, $url]) : ?>
                                    <li><a class="boblift-sitemap-link inline-block text-sm font-medium text-slate-600" href="<?php echo esc_url((string) $url); ?>"><?php echo esc_html((string) $label); ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <div class="grid gap-12 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)] lg:gap-16">
            <?php if ($news !== []) : ?>
                <section aria-labelledby="sitemap-news">
                    <div id="sitemap-news"><?php $section_heading((string) ($groups_ui['newsTitle'] ?? 'News'), count($news), (string) ($groups_ui['allNewsLabel'] ?? 'All news'), boblift_url('/news')); ?></div>
                    <?php if ($news_categories !== []) : ?>
                        <p class="mb-5 flex flex-wrap gap-x-5 gap-y-2 text-sm font-bold">
                            <?php foreach ($news_categories as $term) : $url = get_term_link($term); if (!is_string($url)) continue; ?>
                                <a class="text-navy transition hover:text-blue-700" href="<?php echo esc_url($url); ?>"><?php echo esc_html($sitemap_label($term->name)); ?> <span class="font-normal tabular-nums text-slate-500"><?php echo esc_html((string) $term->count); ?></span></a>
                            <?php endforeach; ?>
                        </p>
                    <?php endif; ?>
                    <ul class="grid gap-x-10 md:grid-cols-2">
                        <?php foreach ($news as $post) : ?>
                            <li class="border-b border-line/70"><a class="boblift-sitemap-link flex items-baseline gap-3 py-2 text-sm font-medium text-slate-600" href="<?php echo esc_url(get_permalink($post)); ?>"><time class="shrink-0 text-xs font-bold tabular-nums text-slate-500" datetime="<?php echo esc_attr(get_the_date('Y-m-d', $post)); ?>"><?php echo esc_html(get_the_date('Y-m', $post)); ?></time><span class="min-w-0"><?php echo esc_html($sitemap_label(get_the_title($post))); ?></span></a></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>

            <?php if ($cases !== []) : ?>
                <section aria-labelledby="sitemap-cases">
                    <div id="sitemap-cases"><?php $section_heading((string) ($groups_ui['casesTitle'] ?? 'Customer cases'), count($cases), (string) ($groups_ui['allCasesLabel'] ?? 'All cases'), boblift_url('/cases.html')); ?></div>
                    <ul class="grid">
                        <?php foreach ($cases as $post) : ?>
                            <li class="border-b border-line/70"><a class="boblift-sitemap-link block py-2 text-sm font-medium text-slate-600" href="<?php echo esc_url(get_permalink($post)); ?>"><?php echo esc_html($sitemap_label(get_the_title($post))); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
            <?php if ($show_legacy_help) : ?>
                <section aria-label="<?php echo esc_attr($legacy_help['help_title'] !== '' ? $legacy_help['help_title'] : 'Sitemap notes'); ?>">
                    <?php if ($legacy_help['help_title'] !== '') : ?>
                        <h2 class="mb-4 border-b border-line pb-4 text-xl font-black leading-tight text-ink"><?php echo esc_html($legacy_help['help_title']); ?></h2>
                    <?php endif; ?>
                    <?php foreach ($legacy_help_notes as $note) : ?>
                        <p class="py-2 text-sm font-normal text-slate-600"><?php if ($note['label'] !== '') : ?><strong class="text-navy"><?php echo esc_html($note['label']); ?></strong> <?php endif; ?><?php echo esc_html($note['text']); ?></p>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php get_footer(); ?>
