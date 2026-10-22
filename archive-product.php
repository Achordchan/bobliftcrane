<?php
get_header();
$products = boblift_all_products();
// A product rated by working height (aerial platforms: a "Working Height" row and no "Max Lifting Capacity" row) keeps its
// height in metres as the numeric fallback, so it stays out of the capacity filter and capacity sorts. The raw figure
// still orders the family in the default sort.
foreach ($products as &$product) {
    $product['sort_size'] = (float) ($product['filter_values']['capacity'] ?? 0);
    $spec_rows = (int) ($product['id'] ?? 0) > 0 ? (array) boblift_decode_meta(get_post_meta(boblift_product_english_id((int) $product['id']), '_specs', true), []) : [];
    $spec_labels = array_map(static fn($row): string => is_array($row) ? trim((string) ($row['label'] ?? '')) : '', $spec_rows);
    // A capacity stated with its unit (a "5 ton" capacity field) is a rated capacity whatever the spec table holds.
    $rated = function_exists('boblift_home_product_rated_capacity') ? boblift_home_product_rated_capacity($product) : 0.0;
    if ($rated <= 0 && in_array('Working Height', $spec_labels, true) && !in_array('Max Lifting Capacity', $spec_labels, true)) {
        $product['filter_values']['capacity'] = 0.0;
    }
}
unset($product);
$categories = boblift_categories();
$initial_category = sanitize_title((string) get_query_var('boblift_product_category'));
if ($initial_category === '' && isset($_GET['category']) && is_string($_GET['category'])) {
    $initial_category = sanitize_title(wp_unslash($_GET['category']));
}
$category_counts = array_fill_keys(array_map(static fn($item) => $item['slug'], $categories), 0);
$category_product_map = array_fill_keys(array_map(static fn($item) => $item['slug'], $categories), []);
$archive_ui = function_exists('boblift_site_ui') ? boblift_site_ui('productArchive') : [];
// Spec filters, all from the brochure figures: bins are "min-max" or "min+" in tonnes, t·m and kg.
$spec_filters = [
    'capacity' => ['label' => (string) ($archive_ui['capacityFilterLabel'] ?? 'Lifting Capacity'), 'bins' => (array) ($archive_ui['capacityBins'] ?? [])],
    'moment' => ['label' => (string) ($archive_ui['momentFilterLabel'] ?? 'Lifting Moment'), 'bins' => (array) ($archive_ui['momentBins'] ?? [])],
    'weight' => ['label' => (string) ($archive_ui['weightFilterLabel'] ?? 'Crane Self Weight'), 'bins' => (array) ($archive_ui['weightBins'] ?? [])],
];
// Boom type only separates knuckle and telescopic cranes; fly jibs and platforms are their own categories.
$boom_options = array_intersect_key(function_exists('boblift_product_boom_options') ? boblift_product_boom_options() : [], ['knuckle-boom' => true, 'telescopic-boom' => true]);
$in_bin = static function (float $number, string $bin): bool {
    // Bin keys write decimals with "_" (no dots in settings paths).
    preg_match_all('/\d+(?:\.\d+)?/', str_replace('_', '.', $bin), $matches);
    $min = (float) ($matches[0][0] ?? 0);
    $max = isset($matches[0][1]) ? (float) $matches[0][1] : null;
    return $number > 0 && $number > $min && ($max === null || $number <= $max);
};
$sort_options = is_array($archive_ui['sortOptions'] ?? null) ? $archive_ui['sortOptions'] : [];
$product_archive = boblift_product_archive_options();
$product_ui = function_exists('boblift_site_ui') ? boblift_site_ui('productDetail') : [];
$quote_list_ui = function_exists('boblift_site_ui') ? boblift_site_ui('quoteList') : [];
// The default banner shows the built-in photo unless an image was uploaded in 产品页默认 Banner.
$archive_banner_image = is_array($product_archive['image'] ?? null) ? $product_archive['image'] : (is_string($product_archive['image'] ?? null) && trim($product_archive['image']) !== '' ? ['id' => 0, 'path' => trim($product_archive['image'])] : []);
$archive_banner_path = trim((string) ($archive_banner_image['path'] ?? ''));
// An upload, or a custom path/URL, wins; only the retired default image falls back to the built-in photo.
$archive_banner_custom = (int) ($archive_banner_image['id'] ?? 0) > 0 || ($archive_banner_path !== '' && $archive_banner_path !== 'images/products/truck-mounted-crane.png');
$banner_default = [
    'label' => (string) ($product_archive['label'] ?? 'BOB-LIFT Product Range'),
    'title' => (string) ($product_archive['title'] ?? 'Cranes & Lifting Equipment'),
    'text' => (string) ($product_archive['text'] ?? 'Explore BOB-LIFT truck mounted cranes, marine cranes and lifting equipment for global projects.'),
    'image' => $archive_banner_custom ? boblift_image_setting_url($archive_banner_image) : boblift_product_banner_photo_url(),
    'imageAlt' => $archive_banner_custom ? (string) ($product_archive['image_alt'] ?? '') : 'BOB-LIFT knuckle boom crane in the factory yard',
];
$banner_categories = [];
foreach ($categories as $category) {
    $slug = (string) ($category['slug'] ?? '');
    if ($slug === '') {
        continue;
    }
    $banner_categories[$slug] = [
        'label' => $category['banner_label'] ?? 'BOB-LIFT Product Range',
        'title' => $category['banner_title'] ?? ($category['title'] ?? $slug),
        'text' => $category['description'] ?? $banner_default['text'],
        'image' => boblift_product_banner_photo_url(get_term_by('slug', $slug, 'product_category') ?: null),
        'imageAlt' => $category['title'] ?? $banner_default['imageAlt'],
    ];
}
$valid_category_slugs = array_fill_keys(array_keys($banner_categories), true);
if ($initial_category !== '' && !isset($valid_category_slugs[$initial_category])) {
    $initial_category = '';
}
$banner_initial = $initial_category !== '' ? $banner_categories[$initial_category] : $banner_default;
$spec_counts = array_map(static fn(array $filter): array => array_fill_keys(array_keys($filter['bins']), 0), $spec_filters);
$boom_counts = array_fill_keys(array_keys($boom_options), 0);
foreach ($products as $product) {
    $product_key = (string) ($product['id'] ?? $product['slug'] ?? $product['title'] ?? wp_unique_id('product_'));
    $product_categories = array_values(array_unique(array_filter(array_map('strval', $product['categories'] ?? [($product['category'] ?? '')]))));
    foreach ($product_categories as $category) {
        if (isset($category_counts[$category])) {
            $category_counts[$category]++;
            $category_product_map[$category][$product_key] = true;
        }
    }
    foreach ($spec_filters as $filter => $config) {
        $number = (float) ($product['filter_values'][$filter] ?? 0);
        foreach (array_keys($config['bins']) as $bin) {
            if ($in_bin($number, (string) $bin)) {
                $spec_counts[$filter][$bin]++;
            }
        }
    }
    $boom = $product['boom_type'] ?? '';
    if (isset($boom_counts[$boom])) {
        $boom_counts[$boom]++;
    }
}
$legacy_groups = function_exists('boblift_legacy_model_library') ? boblift_legacy_model_library($categories) : [];
$legacy_total = array_sum(array_map(static fn(array $group): int => count($group['models']), $legacy_groups));
$category_hierarchy = boblift_product_category_hierarchy();
$category_url_map = [];
foreach ($categories as $category) {
    $slug = (string) ($category['slug'] ?? '');
    if ($slug === '') {
        continue;
    }
    $term = get_term_by('slug', $slug, 'product_category');
    $term_url = $term instanceof WP_Term ? get_term_link($term) : '';
    $category_url_map[$slug] = is_string($term_url) ? $term_url : boblift_public_path_url('/' . $slug);
}
$category_filter_tree = [];
foreach ($category_hierarchy as $group) {
    $children = $group['children'] ?? [];
    if ($children) {
        $parent_slug = (string) ($group['slug'] ?? '');
        $child_slugs = array_values(array_filter(array_map(static fn(array $child): string => (string) ($child['slug'] ?? ''), $children)));
        $filter_slugs = array_values(array_unique(array_filter(array_merge([$parent_slug], $child_slugs))));
        $product_ids = [];
        foreach ($filter_slugs as $filter_slug) {
            foreach (($category_product_map[$filter_slug] ?? []) as $product_id => $_selected) {
                $product_ids[$product_id] = true;
            }
        }
        $count = count($product_ids);
        if ($count <= 0) {
            continue;
        }
        $category_filter_tree[] = ['slug' => $parent_slug, 'title' => (string) $group['title'], 'children' => $children, 'child_slugs' => $filter_slugs, 'count' => $count];
        continue;
    }
    $category = $group['category'] ?? [];
    $slug = (string) ($category['slug'] ?? $group['slug'] ?? '');
    if ($slug !== '' && (($category_counts[$slug] ?? 0) > 0 || $slug === 'other-cranes')) {
        $category_filter_tree[] = ['slug' => $slug, 'title' => (string) ($category['title'] ?? $group['title'] ?? $slug), 'children' => [], 'child_slugs' => [$slug], 'count' => (int) ($category_counts[$slug] ?? 0)];
    }
}
// Same order and labels as the Products menu: cranes, then crane attachments, then aerial access equipment.
foreach ($category_filter_tree as $index => $item) {
    $term = get_term_by('slug', (string) $item['slug'], 'product_category');
    $category_filter_tree[$index]['group'] = boblift_product_family_group($term instanceof WP_Term ? boblift_source_term_slug($term) : (string) $item['slug']);
    $category_filter_tree[$index]['position'] = $index;
}
$group_order = ['crane' => 0, 'attachment' => 1, 'access' => 2];
usort($category_filter_tree, static fn(array $a, array $b): int => ($group_order[$a['group']] <=> $group_order[$b['group']]) ?: ($a['position'] <=> $b['position']));
// Default order ("Recommended" keeps it): featured models, then the families in tab order, largest model first.
$family_rank = [];
foreach ($category_filter_tree as $rank => $item) {
    foreach ($item['child_slugs'] ?? [$item['slug']] as $slug) {
        $family_rank[$slug] ??= $rank;
    }
}
$products = array_values($products);
$product_order = [];
foreach ($products as $index => $product) {
    $ranks = array_map(static fn($slug): int => $family_rank[(string) $slug] ?? PHP_INT_MAX, $product['categories'] ?? [($product['category'] ?? '')]);
    $product_order[$index] = [empty($product['featured']) ? 1 : 0, $ranks ? min($ranks) : PHP_INT_MAX, -(float) $product['sort_size'], $index];
}
uksort($products, static fn(int $a, int $b): int => $product_order[$a] <=> $product_order[$b]);
$products = array_values($products);
$filter_group = static function (string $title, string $filter, array $items, array $counts): void {
    $items = array_filter($items, static fn($label, $value): bool => $filter === 'capacity' || (int) ($counts[$value] ?? 0) > 0, ARRAY_FILTER_USE_BOTH);
    if (!$items) {
        return;
    }
    ?>
    <fieldset class="border-t border-[#E5E7EB] pt-6 first:border-t-0 first:pt-0" data-filter-group="<?php echo esc_attr($filter); ?>">
        <legend class="float-left mb-4 w-full text-base font-black text-[#0B1B33]"><?php echo esc_html($title); ?></legend>
        <div class="clear-left space-y-3">
            <?php foreach ($items as $value => $label) : ?>
                <label class="product-filter-option" data-filter-option><span class="relative flex h-5 w-5 shrink-0 items-center justify-center"><input class="peer h-5 w-5 appearance-none rounded-sm border border-slate-300 bg-white transition checked:border-[#0B3F7D] checked:bg-[#0B3F7D] focus:outline-none focus:ring-4 focus:ring-blue-100" type="checkbox" data-filter="<?php echo esc_attr($filter); ?>" value="<?php echo esc_attr($value); ?>"><span class="pointer-events-none absolute h-2.5 w-1.5 -translate-y-0.5 rotate-45 border-b-2 border-r-2 border-white opacity-0 peer-checked:opacity-100"></span></span><span class="min-w-0 flex-1 whitespace-nowrap" data-filter-label><?php echo esc_html($label); ?></span><span class="shrink-0 text-xs font-black text-slate-400" data-filter-count><?php echo esc_html((string) ($counts[$value] ?? 0)); ?></span></label>
            <?php endforeach; ?>
        </div>
    </fieldset>
<?php };
// Desktop bar: the same options as $filter_group, behind one dropdown button per filter.
$filter_dropdown = static function (string $title, string $filter, array $items, array $counts): void {
    $items = array_filter($items, static fn($label, $value): bool => $filter === 'capacity' || (int) ($counts[$value] ?? 0) > 0, ARRAY_FILTER_USE_BOTH);
    if (!$items) {
        return;
    }
    $panel_id = 'product-filter-' . $filter;
    ?>
    <div class="relative" data-filter-group="<?php echo esc_attr($filter); ?>" data-filter-dropdown data-filter-title="<?php echo esc_attr($title); ?>">
        <button class="boblift-filter-dropdown-trigger" type="button" aria-expanded="false" aria-controls="<?php echo esc_attr($panel_id); ?>" data-filter-dropdown-trigger><?php echo esc_html($title); ?><span class="hidden h-5 min-w-5 items-center justify-center rounded-sm bg-[#0B3F7D] px-1 text-[11px] font-black text-white" data-filter-dropdown-count></span><?php echo boblift_icon('chevron-down', 'h-4 w-4 text-slate-400 transition'); ?></button>
        <div id="<?php echo esc_attr($panel_id); ?>" class="absolute start-0 top-[calc(100%+8px)] z-40 hidden max-h-[60vh] overflow-y-auto rounded border border-[#DDE5EF] bg-white p-2 shadow-raised <?php echo count($items) > 6 ? 'w-[440px]' : 'w-[280px]'; ?>" data-filter-dropdown-panel>
            <div class="grid gap-0.5 <?php echo count($items) > 6 ? 'grid-cols-2' : ''; ?>">
                <?php foreach ($items as $value => $label) : ?>
                    <label class="product-filter-option min-h-10 rounded-sm px-2.5 hover:bg-[#F8FAFC]" data-filter-option><span class="relative flex h-5 w-5 shrink-0 items-center justify-center"><input class="peer h-5 w-5 appearance-none rounded-sm border border-slate-300 bg-white transition checked:border-[#0B3F7D] checked:bg-[#0B3F7D] focus:outline-none focus:ring-4 focus:ring-blue-100" type="checkbox" data-filter="<?php echo esc_attr($filter); ?>" value="<?php echo esc_attr($value); ?>"><span class="pointer-events-none absolute h-2.5 w-1.5 -translate-y-0.5 rotate-45 border-b-2 border-r-2 border-white opacity-0 peer-checked:opacity-100"></span></span><span class="min-w-0 flex-1 whitespace-nowrap" data-filter-label><?php echo esc_html($label); ?></span><span class="shrink-0 text-xs font-black text-slate-400" data-filter-count><?php echo esc_html((string) ($counts[$value] ?? 0)); ?></span></label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php };
$category_filter_group = static function (array $tree) use ($category_counts, $archive_ui): void { ?>
    <fieldset class="border-t border-[#E5E7EB] pt-6 first:border-t-0 first:pt-0">
        <legend class="float-left mb-4 w-full text-base font-black text-[#0B1B33]"><?php echo esc_html($archive_ui['categoryFilterLabel'] ?? 'Product Category'); ?></legend>
        <div class="clear-left space-y-3">
            <?php $group_shown = 'crane'; foreach ($tree as $item) : ?>
                <?php if (($item['group'] ?? 'crane') !== $group_shown) : $group_shown = $item['group']; ?>
                    <p class="border-t border-line pt-3 text-[11px] font-black uppercase tracking-[0.14em] text-slate-400"><?php echo esc_html(boblift_product_family_group_label($group_shown)); ?></p>
                <?php endif; ?>
                <label class="product-filter-option"><span class="relative flex h-5 w-5 shrink-0 items-center justify-center"><input class="peer h-5 w-5 appearance-none rounded-sm border border-slate-300 bg-white transition checked:border-[#0B3F7D] checked:bg-[#0B3F7D] focus:outline-none focus:ring-4 focus:ring-blue-100" type="checkbox" data-filter="category" data-category-role="<?php echo !empty($item['children']) ? 'parent' : 'single'; ?>" data-category-children="<?php echo esc_attr(implode(',', $item['child_slugs'] ?? [$item['slug']])); ?>" value="<?php echo esc_attr($item['slug']); ?>"><span class="pointer-events-none absolute h-2.5 w-1.5 -translate-y-0.5 rotate-45 border-b-2 border-r-2 border-white opacity-0 peer-checked:opacity-100"></span></span><span class="min-w-0 flex-1 font-black leading-snug"><?php echo esc_html($item['title']); ?></span><span class="shrink-0 text-xs font-black text-slate-400"><?php echo esc_html((string) ($item['count'] ?? 0)); ?></span></label>
                <?php if (!empty($item['children'])) : ?>
                    <div class="hidden space-y-3" data-category-children-panel="<?php echo esc_attr($item['slug']); ?>" data-category-children="<?php echo esc_attr(implode(',', $item['child_slugs'] ?? [])); ?>">
                        <?php foreach (($item['children'] ?? []) as $child) : $child_slug = (string) ($child['slug'] ?? ''); ?>
                            <label class="product-filter-option ps-8 text-[0.92em]"><span class="relative flex h-5 w-5 shrink-0 items-center justify-center"><input class="peer h-5 w-5 appearance-none rounded-sm border border-slate-300 bg-white transition checked:border-[#0B3F7D] checked:bg-[#0B3F7D] focus:outline-none focus:ring-4 focus:ring-blue-100" type="checkbox" data-filter="category" data-category-role="child" data-category-parent="<?php echo esc_attr($item['slug']); ?>" value="<?php echo esc_attr($child_slug); ?>"><span class="pointer-events-none absolute h-2.5 w-1.5 -translate-y-0.5 rotate-45 border-b-2 border-r-2 border-white opacity-0 peer-checked:opacity-100"></span></span><span class="min-w-0 flex-1 leading-snug"><?php echo esc_html($child['title'] ?? $child_slug); ?></span><span class="shrink-0 text-xs font-black text-slate-400"><?php echo esc_html((string) ($category_counts[$child_slug] ?? 0)); ?></span></label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </fieldset>
<?php };
// Phones and tablets: every filter in one drawer.
$filter_sidebar = static function () use ($filter_group, $category_filter_group, $category_filter_tree, $spec_filters, $spec_counts, $boom_options, $boom_counts, $archive_ui): void { ?>
    <aside class="boblift-product-filter-sidebar rounded border border-[#E5E7EB] bg-white p-6 shadow-card">
        <div class="boblift-product-filter-head mb-6 flex items-center justify-between gap-4">
            <h2 class="text-2xl font-black tracking-[-0.015em] text-[#0B1B33]"><?php echo esc_html($archive_ui['filtersTitle'] ?? 'Filters'); ?></h2>
            <div class="flex items-center gap-3">
                <button class="text-sm font-bold text-slate-500 transition hover:text-blue-700" type="button" data-filter-reset><?php echo esc_html($archive_ui['clearAllLabel'] ?? 'Clear All'); ?></button>
                <button class="inline-flex h-10 w-10 items-center justify-center rounded-sm border border-[#E5E7EB] text-[#0B1B33] transition hover:border-blue-300 hover:text-blue-700" type="button" data-filter-drawer-close aria-label="<?php echo esc_attr($archive_ui['closeFiltersLabel'] ?? 'Close filters'); ?>"><?php echo boblift_icon('x', 'h-5 w-5'); ?></button>
            </div>
        </div>
        <?php // An old tonnage link's capacity range (?capacity=4-5) has no tick box; it shows here so phones can see and clear it. ?>
        <div class="mb-6 hidden" data-filter-custom-capacity><button class="boblift-filter-chip" type="button" data-filter-custom-capacity-clear><span data-filter-custom-capacity-label></span><?php echo boblift_icon('x', 'h-3.5 w-3.5 text-slate-400'); ?></button></div>
        <div class="space-y-7"><?php
            $category_filter_group($category_filter_tree);
            $filter_group((string) ($archive_ui['boomFilterLabel'] ?? 'Boom Type'), 'boom', $boom_options, $boom_counts);
            foreach ($spec_filters as $filter => $config) {
                $filter_group($config['label'], $filter, $config['bins'], $spec_counts[$filter]);
            }
            ?></div>
        <div class="boblift-filter-drawer-actions mt-8 border-t border-[#E5E7EB] pt-6"><button class="min-h-12 w-full rounded-sm bg-[#0B1B33] px-5 text-sm font-black text-white shadow-card transition hover:-translate-y-0.5 hover:bg-[#0B3F7D]" type="button" data-filter-apply><?php echo esc_html($archive_ui['applyFiltersLabel'] ?? 'Apply'); ?></button></div>
    </aside>
<?php };
// Desktop: category tabs (one category at a time, like the Products menu), then a dropdown per spec filter,
// then the picked spec options as chips. The checkboxes mirror the drawer's, so both drive the same filtering.
$filter_bar = static function () use ($filter_dropdown, $category_filter_tree, $category_counts, $spec_filters, $spec_counts, $boom_options, $boom_counts, $archive_ui, $products, $legacy_total): void { ?>
    <div class="mb-6 hidden rounded border border-[#E5E7EB] bg-white shadow-card lg:mb-7 lg:block" data-filter-bar>
        <div class="flex flex-wrap px-3 shadow-[inset_0_-1px_0_#E5E7EB]" role="group" aria-label="<?php echo esc_attr($archive_ui['categoryFilterLabel'] ?? 'Product Category'); ?>">
            <button class="boblift-category-tab" type="button" aria-pressed="false" data-category-tab=""><?php echo esc_html($archive_ui['allCategoriesLabel'] ?? 'All Products'); ?><span data-tab-count><?php echo esc_html((string) count($products)); ?></span></button>
            <?php foreach ($category_filter_tree as $item) : ?>
                <button class="boblift-category-tab" type="button" aria-pressed="false" data-category-tab="<?php echo esc_attr($item['slug']); ?>"><?php echo esc_html($item['title']); ?><span data-tab-count><?php echo esc_html((string) ($item['count'] ?? 0)); ?></span></button>
            <?php endforeach; ?>
            <?php // Previous models look like a tab but toggle on their own: the category tab stays lit beside it, and the selected category's previous models replace the grid. ?>
            <?php if ($legacy_total > 0) : ?><button class="boblift-category-tab boblift-legacy-tab ms-auto" type="button" aria-pressed="false" aria-controls="previous-models" data-legacy-toggle><?php echo boblift_icon('clock-3', 'h-4 w-4'); ?><?php echo esc_html(boblift_localized_static_text('Previous models')); ?><span data-tab-count data-legacy-count><?php echo esc_html((string) $legacy_total); ?></span></button><?php endif; ?>
        </div>
        <?php foreach ($category_filter_tree as $item) : if (empty($item['children'])) { continue; } ?>
            <div class="hidden flex-wrap gap-2 px-6 pt-4" role="group" aria-label="<?php echo esc_attr($item['title']); ?>" data-category-subtabs="<?php echo esc_attr($item['slug']); ?>">
                <button class="boblift-category-subtab" type="button" aria-pressed="false" data-category-tab="<?php echo esc_attr($item['slug']); ?>"><?php echo esc_html($archive_ui['allInCategoryLabel'] ?? 'All'); ?><span data-tab-count><?php echo esc_html((string) ($item['count'] ?? 0)); ?></span></button>
                <?php foreach ($item['children'] as $child) : $child_slug = (string) ($child['slug'] ?? ''); if (($category_counts[$child_slug] ?? 0) <= 0) { continue; } ?>
                    <button class="boblift-category-subtab" type="button" aria-pressed="false" data-category-tab="<?php echo esc_attr($child_slug); ?>"><?php echo esc_html($child['title'] ?? $child_slug); ?><span data-tab-count><?php echo esc_html((string) $category_counts[$child_slug]); ?></span></button>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <div class="flex flex-wrap items-center gap-3 px-6 py-4" data-filter-dropdowns>
            <?php
            $filter_dropdown((string) ($archive_ui['boomFilterLabel'] ?? 'Boom Type'), 'boom', $boom_options, $boom_counts);
            foreach ($spec_filters as $filter => $config) {
                $filter_dropdown($config['label'], $filter, $config['bins'], $spec_counts[$filter]);
            }
            ?>
        </div>
        <div class="hidden flex-wrap items-center gap-2 border-t border-[#E5E7EB] px-6 py-3" data-filter-chips-row>
            <div class="flex flex-wrap gap-2" data-filter-chips></div>
            <button class="ms-2 inline-flex h-8 items-center text-sm font-black text-blue-700 transition hover:text-navy" type="button" data-filter-clear-specs><?php echo esc_html($archive_ui['clearFiltersLabel'] ?? 'Clear Filters'); ?></button>
            <template data-filter-chip-icon><?php echo boblift_icon('x', 'h-3.5 w-3.5 text-slate-400'); ?></template>
        </div>
    </div>
<?php };
?>
<section class="boblift-product-listing bg-[#F8FAFC] py-8 md:py-12 lg:py-14" data-product-listing data-view="grid" data-current-category="<?php echo esc_attr($initial_category); ?>" data-product-archive-url="<?php echo esc_url(boblift_product_archive_url()); ?>" data-category-urls="<?php echo esc_attr(wp_json_encode($category_url_map, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); ?>">
    <div class="page-shell">
        <?php
        boblift_page_hero([
            'label' => $banner_initial['label'],
            'title' => $banner_initial['title'],
            'text' => $banner_initial['text'],
            'image' => $banner_initial['image'],
            'image_alt' => $banner_initial['imageAlt'],
            'class' => 'mb-6 md:mb-7',
            'attrs' => 'data-product-banner data-banner-default="' . esc_attr(wp_json_encode($banner_default, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . '" data-banner-categories="' . esc_attr(wp_json_encode($banner_categories, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . '"',
        ]);
        $filter_bar();
        ?>

        <div class="mb-6 flex scroll-mt-6 flex-col gap-4 md:flex-row md:items-center md:justify-between lg:mb-7" data-products-toolbar>
            <div class="flex flex-wrap items-center gap-3">
                <p class="text-sm font-black text-slate-500 md:text-base"><?php echo wp_kses(str_replace(['{count}', '{total}'], ['<span data-product-count>' . esc_html((string) count($products)) . '</span>', '<span data-product-total>' . esc_html((string) count($products)) . '</span>'], (string) ($archive_ui['showingTemplate'] ?? 'Showing {count} of {total} products')), ['span' => ['data-product-count' => true, 'data-product-total' => true]]); ?></p>
                <p class="hidden items-center gap-3 text-sm font-black text-slate-500 md:text-base" data-product-page><span class="h-4 w-px bg-[#D8DEE8]" aria-hidden="true"></span><span data-product-page-text></span></p>
            </div>
            <div class="flex w-full flex-wrap items-center gap-3 md:w-auto">
                <div class="boblift-product-mobile-tools relative flex w-full items-center gap-2 md:w-auto md:gap-3" data-sort-dropdown>
                    <button class="boblift-product-filter-button inline-flex h-12 items-center gap-2 rounded-sm border border-[#D8DEE8] bg-white px-5 text-sm font-black text-[#0B1B33] shadow-card transition hover:border-blue-300 hover:text-blue-700 lg:hidden" type="button" data-filter-drawer-open><?php echo boblift_icon('sliders-horizontal', 'h-4 w-4'); ?><?php echo esc_html($archive_ui['filterButtonLabel'] ?? 'Filter'); ?></button>
                    <span class="boblift-product-sort-label text-sm font-black text-slate-500"><?php echo esc_html($archive_ui['sortByLabel'] ?? 'Sort by:'); ?></span>
                    <button class="boblift-product-sort-trigger inline-flex h-12 min-w-[240px] items-center justify-between gap-4 rounded-sm border border-[#D8DEE8] bg-white px-4 text-start text-sm font-black text-[#0B1B33] shadow-card transition hover:border-blue-300 focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-100" type="button" aria-haspopup="listbox" aria-expanded="false" data-sort-products data-sort-trigger data-sort-value="recommended"><span data-sort-label><?php echo esc_html($sort_options['recommended'] ?? 'Recommended'); ?></span><?php echo boblift_icon('chevron-down', 'h-4 w-4 text-slate-400 transition'); ?></button>
                    <div class="absolute end-0 top-[calc(100%+10px)] z-50 hidden w-[240px] overflow-hidden rounded border border-[#DDE5EF] bg-white p-2 shadow-raised" role="listbox" data-sort-menu>
                        <?php foreach ($sort_options as $value => $label) : ?>
                            <button class="flex min-h-10 w-full items-center justify-between gap-3 rounded-sm px-3 text-start text-sm font-black transition <?php echo $value === 'recommended' ? 'bg-blue-50 text-blue-700' : 'text-[#0B1B33] hover:bg-[#F8FAFC]'; ?>" type="button" role="option" aria-selected="<?php echo $value === 'recommended' ? 'true' : 'false'; ?>" data-sort-option="<?php echo esc_attr($value); ?>"><span><?php echo esc_html($label); ?></span><span class="<?php echo $value === 'recommended' ? 'inline-flex' : 'hidden'; ?>" data-sort-check><?php echo boblift_icon('check', 'h-4 w-4'); ?></span></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="hidden rounded border border-[#D8DEE8] bg-white p-1 shadow-card md:flex" data-products-view-modes aria-label="<?php echo esc_attr($archive_ui['viewModeLabel'] ?? 'Product view mode'); ?>"><button class="inline-flex h-10 w-10 items-center justify-center rounded-sm bg-[#0B1B33] text-sm font-black text-white transition" type="button" data-view-toggle="grid" aria-label="<?php echo esc_attr($archive_ui['gridViewLabel'] ?? 'Grid view'); ?>"><?php echo boblift_icon('grid-3x3', 'h-5 w-5'); ?></button><button class="inline-flex h-10 w-10 items-center justify-center rounded-sm text-sm font-black text-[#0B1B33] transition hover:bg-slate-100" type="button" data-view-toggle="list" aria-label="<?php echo esc_attr($archive_ui['listViewLabel'] ?? 'List view'); ?>"><?php echo boblift_icon('list', 'h-5 w-5'); ?></button></div>
            </div>
        </div>

        <div class="min-w-0 scroll-mt-6" data-products-results>
            <div class="products-grid grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3 xl:gap-6 min-[1700px]:grid-cols-4" data-products-grid>
                <?php foreach ($products as $index => $product) : boblift_product_card($product, '', $index === 0); endforeach; ?>
            </div>
            <div class="hidden min-h-[420px] flex-col items-center justify-center rounded border border-dashed border-[#CBD5E1] bg-white px-6 text-center shadow-card" data-product-empty><span class="inline-flex h-14 w-14 items-center justify-center rounded-sm bg-blue-50 text-blue-700"><?php echo boblift_icon('search-x', 'h-7 w-7'); ?></span><h2 class="mt-5 text-2xl font-black tracking-[-0.015em] text-[#0B1B33]"><?php echo esc_html($archive_ui['emptyTitle'] ?? 'No matching products found'); ?></h2><p class="mt-3 max-w-md text-sm font-normal leading-6 text-slate-600"><?php echo esc_html($archive_ui['emptyText'] ?? 'Try adjusting your filters or contact us for a custom lifting solution.'); ?></p><button class="mt-6 min-h-12 rounded-sm bg-[#0B1B33] px-6 text-sm font-black text-white transition hover:-translate-y-0.5 hover:bg-[#0B3F7D]" type="button" data-filter-reset><?php echo esc_html($archive_ui['clearFiltersLabel'] ?? 'Clear Filters'); ?></button><p class="mt-5 text-sm text-slate-500"><?php echo esc_html(boblift_localized_static_text('Looking for loaders or dumpers?')); ?> <a class="font-black text-blue-700 underline-offset-4 hover:underline" href="https://bobliftloader.com/" data-leave-site data-leave-name="Loader" data-leave-domain="bobliftloader.com" target="_blank" rel="noopener">bobliftloader.com</a></p></div>
            <nav class="mt-8 flex flex-wrap items-center justify-center gap-1.5 sm:gap-2" aria-label="<?php echo esc_attr($archive_ui['paginationLabel'] ?? 'Product pagination'); ?>" data-product-pagination></nav>
            <?php if ($legacy_total > 0) : ?>
                <p class="mt-6 flex flex-wrap items-center justify-center gap-x-2 gap-y-1 text-center text-sm text-slate-500" data-legacy-entry><span><?php echo esc_html(boblift_localized_static_text('Looking for an older model?')); ?></span><button class="inline-flex items-center gap-1.5 font-black text-blue-700 transition hover:text-[#071f42]" type="button" aria-controls="previous-models" data-legacy-open><?php echo esc_html(boblift_localized_static_text('Previous models')); ?> <span class="font-semibold text-slate-400 [font-variant-numeric:tabular-nums]" data-legacy-count><?php echo esc_html((string) $legacy_total); ?></span><?php echo boblift_icon('arrow-right', 'h-4 w-4 rtl:-scale-x-100'); ?></button></p>
            <?php endif; ?>
        </div>
        <?php if (function_exists('boblift_render_legacy_panel')) { boblift_render_legacy_panel($legacy_groups); } ?>

        <div class="boblift-archive-about">
            <?php
            // Category introductions (edited on the category screen or through REST term meta), shown below the listing for the selected category only; on desktop the brochure sits beside it (see .boblift-archive-about).
            foreach ($categories as $intro_category) :
                $intro_slug = (string) ($intro_category['slug'] ?? '');
                $intro_paragraphs = array_values(array_filter(array_map('strval', (array) ($intro_category['intro_paragraphs'] ?? [])), static fn(string $text): bool => trim($text) !== ''));
                if ($intro_slug === '' || $intro_paragraphs === []) {
                    continue;
                }
                $intro_title = trim((string) ($intro_category['intro_title'] ?? ''));
            ?>
                <section class="mt-12 max-w-[880px] lg:mt-16" data-category-intro="<?php echo esc_attr($intro_slug); ?>" aria-labelledby="category-intro-<?php echo esc_attr($intro_slug); ?>"<?php echo $intro_slug === $initial_category ? '' : ' hidden'; ?>>
                    <h2 id="category-intro-<?php echo esc_attr($intro_slug); ?>" class="text-[clamp(1.4rem,2vw,2rem)] font-black leading-tight tracking-[-0.01em] text-[#0B1B33]"><?php echo esc_html($intro_title !== '' ? $intro_title : (string) ($intro_category['banner_title'] ?? $intro_category['title'] ?? '')); ?></h2>
                    <?php foreach ($intro_paragraphs as $paragraph) : ?>
                        <p class="mt-4 text-sm font-normal leading-7 text-slate-600 md:text-base"><?php echo esc_html($paragraph); ?></p>
                    <?php endforeach; ?>
                </section>
            <?php endforeach; ?>
            <?php boblift_render_brochures(['context' => 'archive', 'initial_category' => $initial_category]); ?>
        </div>
        <?php boblift_render_archive_extras($categories, $initial_category); ?>
        <?php
        // Last on the page, after our own content: the sister site for compact site equipment. The link opens the leave dialog (footer.php) like the header switcher.
        $t = static fn(string $text): string => boblift_localized_static_text($text);
        $loader_leave = ' href="https://bobliftloader.com/" data-leave-site data-leave-name="Loader" data-leave-domain="bobliftloader.com"';
        ?>
        <section class="mt-12 grid overflow-hidden rounded border border-[#E5E7EB] bg-white shadow-card md:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)] lg:mt-16" aria-labelledby="group-loader-title">
            <div class="flex flex-col justify-center p-6 md:p-8 lg:p-10">
                <p class="section-label"><?php echo esc_html($t('Also from BOB-LIFT')); ?></p>
                <h2 id="group-loader-title" class="mt-3 text-2xl font-black leading-tight tracking-[-0.015em] text-ink md:text-3xl"><?php echo esc_html($t('Mini loaders and dumpers')); ?></h2>
                <p class="mt-3 max-w-[48ch] text-sm leading-7 text-slate-600 md:text-base"><?php echo esc_html($t('Mini skid steer loaders, mini dumpers and attachments, on the BOB-LIFT loader site.')); ?></p>
                <a class="mt-6 inline-flex h-12 w-fit items-center gap-2 rounded-sm bg-navy px-6 text-sm font-black text-white shadow-card transition hover:-translate-y-0.5 hover:bg-[#0b2d5b]"<?php echo $loader_leave; ?> target="_blank" rel="noopener"><?php echo esc_html(str_replace('{domain}', 'bobliftloader.com', $t('Visit {domain}'))); ?><?php echo boblift_icon('arrow-up-right', 'h-4 w-4 rtl:-scale-x-100'); ?></a>
            </div>
            <a class="group relative grid min-h-[200px] grid-cols-2 items-center gap-2 bg-[radial-gradient(circle_at_50%_40%,#ffffff_0%,#f8fafc_38%,#eef2f7_70%,#e2e8f0_100%)] px-4 py-6 sm:min-h-[260px] md:px-8"<?php echo $loader_leave; ?> target="_blank" rel="noopener" tabindex="-1" aria-hidden="true">
                <img class="mx-auto max-h-[150px] w-full object-contain drop-shadow-[0_6px_8px_rgba(11,27,51,0.12)] transition duration-500 group-hover:scale-[1.04] sm:max-h-[210px]" src="<?php echo esc_url(boblift_asset('images/group/loader-bl-1000.webp')); ?>" width="748" height="556" alt="" loading="lazy" decoding="async">
                <img class="mx-auto max-h-[150px] w-full object-contain drop-shadow-[0_6px_8px_rgba(11,27,51,0.12)] transition duration-500 group-hover:scale-[1.04] sm:max-h-[210px]" src="<?php echo esc_url(boblift_asset('images/group/loader-mini-dumper.webp')); ?>" width="900" height="709" alt="" loading="lazy" decoding="async">
            </a>
        </section>
    </div>
    <div class="fixed inset-0 z-[250] hidden items-center justify-center bg-[#0B1B33]/45 px-4 py-8 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="quick-view-title" data-quick-view-modal>
        <div class="max-h-[calc(100vh-4rem)] w-full max-w-5xl overflow-y-auto rounded bg-white shadow-card" data-quick-view-panel>
            <div class="grid gap-0 lg:grid-cols-[0.95fr_1.05fr]">
                <div class="relative min-h-[340px] bg-[radial-gradient(circle_at_48%_34%,#ffffff_0%,#f8fafc_34%,#eef2f7_64%,#e2e8f0_100%)] lg:min-h-[480px]">
                    <span class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_50%_82%,rgba(15,23,42,0.12),transparent_44%)]"></span>
                    <img class="absolute inset-0 z-10 h-full w-full object-contain p-10 drop-shadow-[0_6px_8px_rgba(11,27,51,0.10)]" src="" alt="" data-quick-image loading="lazy" decoding="async">
                </div>
                <div class="flex flex-col p-6 md:p-8">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="boblift-kicker" data-quick-category>BOB-LIFT</p>
                            <h2 id="quick-view-title" class="mt-3 text-3xl font-black leading-tight tracking-[-0.015em] text-[#0B1B33] md:text-4xl" data-quick-title></h2>
                        </div>
                        <button class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-sm border border-[#E5E7EB] text-[#0B1B33] transition hover:border-blue-300 hover:text-blue-700" type="button" aria-label="<?php echo esc_attr($product_ui['closeQuickViewLabel'] ?? 'Close quick view'); ?>" data-quick-close><?php echo boblift_icon('x', 'h-5 w-5'); ?></button>
                    </div>
                    <p class="mt-4 text-base font-normal leading-7 text-slate-600" data-quick-summary></p>
                    <dl class="mt-5 grid grid-cols-2 border-s border-t border-[#E5E7EB] sm:grid-cols-3" data-quick-specs></dl>
                    <div class="mt-auto grid gap-3 pt-8 sm:grid-cols-[1fr_1fr_auto]">
                        <button class="inline-flex min-h-12 items-center justify-center rounded-sm bg-[#0B1B33] px-6 text-sm font-black text-white transition hover:-translate-y-0.5 hover:bg-[#0B3F7D]" type="button" data-quick-inquiry data-inquiry-modal-open data-inquiry-source="产品快速预览"><?php echo esc_html($product_ui['requestQuoteLabel'] ?? 'Request Quote'); ?></button>
                        <a class="inline-flex min-h-12 items-center justify-center gap-2 rounded-sm border border-[#D8DEE8] bg-white px-6 text-sm font-black text-[#0B1B33] transition hover:-translate-y-0.5 hover:border-blue-300 hover:text-blue-700" href="<?php echo esc_url(boblift_product_archive_url()); ?>" data-quick-detail><?php echo esc_html($product_ui['viewDetailsLabel'] ?? 'View Details'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
                        <button class="inline-flex min-h-12 items-center justify-center rounded-sm border border-[#D8DEE8] bg-white px-5 text-[#0B1B33] transition hover:-translate-y-0.5 hover:border-blue-300 hover:text-blue-700" type="button" aria-label="<?php echo esc_attr($quote_list_ui['addLabel'] ?? 'Add to Quote List'); ?>" data-quick-favorite><?php echo boblift_icon('heart', 'h-5 w-5'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="boblift-filter-drawer fixed inset-0 z-[60] hidden bg-[#0B1B33]/40 backdrop-blur-sm lg:hidden" data-filter-drawer><div class="boblift-filter-drawer-panel ms-auto h-full w-full max-w-md overflow-y-auto bg-[#F8FAFC] p-4 shadow-raised"><?php $filter_sidebar(); ?></div></div>
</section>
<?php get_footer(); ?>
