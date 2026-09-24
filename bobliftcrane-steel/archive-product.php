<?php
/** Steel product archive (also used for product category pages). */
get_header();

$products = boblift_all_products();
$categories = boblift_categories();
$archive_ui = function_exists('boblift_site_ui') ? boblift_site_ui('productArchive') : [];
$product_archive = function_exists('boblift_product_archive_options') ? boblift_product_archive_options() : [];
$capacity_ranges = is_array($archive_ui['capacityRanges'] ?? null) ? $archive_ui['capacityRanges'] : [];
$boom_options = function_exists('boblift_product_boom_options') ? boblift_product_boom_options() : [];
$application_options = is_array($archive_ui['applicationOptions'] ?? null) ? $archive_ui['applicationOptions'] : [];
$status_options = is_array($archive_ui['statusOptions'] ?? null) ? $archive_ui['statusOptions'] : [];
$sort_options = is_array($archive_ui['sortOptions'] ?? null) ? $archive_ui['sortOptions'] : ['recommended' => 'Recommended'];

$initial_category = sanitize_title((string) get_query_var('boblift_product_category'));
if ($initial_category === '' && isset($_GET['category']) && is_string($_GET['category'])) {
    $initial_category = sanitize_title(wp_unslash($_GET['category']));
}

$category_map = [];
foreach ($categories as $category) {
    $slug = (string) ($category['slug'] ?? '');
    if ($slug !== '') {
        $category_map[$slug] = $category;
    }
}
if ($initial_category !== '' && !isset($category_map[$initial_category])) {
    $initial_category = '';
}

/* Counts per facet. */
$category_counts = [];
$capacity_counts = array_fill_keys(array_column($capacity_ranges, 'value'), 0);
$boom_counts = array_fill_keys(array_keys($boom_options), 0);
$application_counts = array_fill_keys(array_keys($application_options), 0);
$status_counts = array_fill_keys(array_keys($status_options), 0);
foreach ($products as $product) {
    foreach (boblift_steel_product_category_slugs($product) as $slug) {
        $category_counts[$slug] = ($category_counts[$slug] ?? 0) + 1;
    }
    $capacity = (float) ($product['capacity_value'] ?? 0);
    foreach ($capacity_ranges as $range) {
        $value = (string) ($range['value'] ?? '');
        preg_match_all('/\d+(?:\.\d+)?/', $value, $matches);
        $min = isset($matches[0][0]) ? (float) $matches[0][0] : 0.0;
        $max = isset($matches[0][1]) ? (float) $matches[0][1] : null;
        $in_range = $max === null ? $capacity > $min : (($min <= 0 && $capacity > 0 && $capacity <= $max) || ($min > 0 && $capacity > $min && $capacity <= $max));
        if ($in_range) {
            $capacity_counts[$value]++;
        }
    }
    $boom = (string) ($product['boom_type'] ?? '');
    if (isset($boom_counts[$boom])) {
        $boom_counts[$boom]++;
    }
    foreach (($product['applications'] ?? []) as $application) {
        if (isset($application_counts[$application])) {
            $application_counts[$application]++;
        }
    }
    $status = function_exists('boblift_normalize_product_status') ? boblift_normalize_product_status((string) ($product['status'] ?? 'customizable')) : 'customizable';
    if (isset($status_counts[$status])) {
        $status_counts[$status]++;
    }
}

/* Category tree for the filter. */
$tree = [];
foreach (boblift_product_category_hierarchy() as $group) {
    $category = is_array($group['category'] ?? null) ? $group['category'] : [];
    $slug = (string) ($group['slug'] ?? ($category['slug'] ?? ''));
    if ($slug === '') {
        continue;
    }
    $children = [];
    foreach (($group['children'] ?? []) as $child) {
        $child_slug = (string) ($child['slug'] ?? '');
        if ($child_slug !== '' && ($category_counts[$child_slug] ?? 0) > 0) {
            $children[] = ['slug' => $child_slug, 'title' => (string) ($child['title'] ?? $child_slug), 'count' => (int) $category_counts[$child_slug]];
        }
    }
    $count = (int) ($category_counts[$slug] ?? 0);
    if ($count <= 0 && $children === []) {
        continue;
    }
    $tree[] = ['slug' => $slug, 'title' => (string) ($group['title'] ?? ($category['title'] ?? $slug)), 'count' => $count, 'children' => $children];
}

/* Page heading. */
$heading = [
    'label' => boblift_steel_t((string) ($product_archive['label'] ?? 'BOB-LIFT product range')),
    'title' => boblift_steel_t((string) ($product_archive['title'] ?? 'Construction equipment')),
    'text' => boblift_steel_t((string) ($product_archive['text'] ?? '')),
];
$current_category = $initial_category !== '' ? $category_map[$initial_category] : null;
if ($current_category) {
    $heading = [
        'label' => boblift_steel_t((string) ($current_category['banner_label'] ?? ($product_archive['label'] ?? 'BOB-LIFT product range'))),
        'title' => (string) ($current_category['banner_title'] ?? ($current_category['title'] ?? $initial_category)),
        'text' => boblift_steel_t((string) ($current_category['description'] ?? '')),
    ];
}
$archive_url = function_exists('boblift_product_archive_url') ? boblift_product_archive_url() : boblift_url('/products');
$total = count($products);
$showing = (string) ($archive_ui['showingTemplate'] ?? 'Showing {count} of {total} products');
$showing_html = str_replace(
    ['{count}', '{total}'],
    ['<strong data-count-current>' . esc_html((string) $total) . '</strong>', '<strong>' . esc_html((string) $total) . '</strong>'],
    esc_html(boblift_steel_t($showing))
);

$render_option = static function (string $filter, string $value, string $label, int $count, string $extra_class = '', array $data = []): void {
    $attrs = '';
    foreach ($data as $key => $val) {
        $attrs .= ' data-' . esc_attr($key) . '="' . esc_attr((string) $val) . '"';
    }
    echo '<label class="st-check ' . esc_attr($extra_class) . '"><input type="checkbox" data-filter="' . esc_attr($filter) . '" value="' . esc_attr($value) . '"' . $attrs . '><span class="st-check__label">' . esc_html($label) . '</span><span class="st-check__count">' . esc_html((string) $count) . '</span></label>';
};
$render_group = static function (string $title, string $filter, array $items, array $counts) use ($render_option): void {
    $items = array_filter($items, static fn($label, $value): bool => (int) ($counts[$value] ?? 0) > 0, ARRAY_FILTER_USE_BOTH);
    if ($items === []) {
        return;
    }
    echo '<fieldset><legend>' . esc_html($title) . '</legend>';
    foreach ($items as $value => $label) {
        $render_option($filter, (string) $value, boblift_steel_t((string) $label), (int) ($counts[$value] ?? 0));
    }
    echo '</fieldset>';
};
?>

<section class="st-archive" data-archive data-current-category="<?php echo esc_attr($initial_category); ?>" data-archive-url="<?php echo esc_url($archive_url); ?>">
    <div class="st-frame">
        <header class="st-archive__head">
            <div>
                <nav class="st-archive__crumbs" aria-label="Breadcrumb">
                    <a href="<?php echo esc_url(boblift_url('/')); ?>"><?php echo esc_html(boblift_steel_t('Home')); ?></a><span>/</span>
                    <?php if ($current_category) : ?>
                        <a href="<?php echo esc_url($archive_url); ?>"><?php echo esc_html(boblift_steel_t('Products')); ?></a><span>/</span><span><?php echo esc_html((string) ($current_category['title'] ?? '')); ?></span>
                    <?php else : ?>
                        <span><?php echo esc_html(boblift_steel_t('Products')); ?></span>
                    <?php endif; ?>
                </nav>
                <p class="st-eyebrow"><?php echo esc_html($heading['label']); ?></p>
                <h1 class="st-h1"><?php echo esc_html($heading['title']); ?></h1>
                <?php if ($heading['text'] !== '') : ?><p class="st-lead"><?php echo esc_html($heading['text']); ?></p><?php endif; ?>
            </div>
            <div>
                <p class="st-archive__figure"><span data-count-current><?php echo esc_html((string) $total); ?></span><small><?php echo esc_html(boblift_steel_t('models in this selection')); ?></small></p>
                <p class="st-archive__count"><?php echo $showing_html; ?></p>
            </div>
        </header>

        <div class="st-archive__layout">
            <aside class="st-filters" data-filters aria-label="<?php echo esc_attr(boblift_steel_t((string) ($archive_ui['filtersTitle'] ?? 'Filters'))); ?>">
                <div class="st-filters__head">
                    <h2><?php echo esc_html(boblift_steel_t((string) ($archive_ui['filtersTitle'] ?? 'Filters'))); ?></h2>
                    <div style="display:flex;gap:14px;align-items:center">
                        <button class="st-filters__reset" type="button" data-filter-reset><?php echo esc_html(boblift_steel_t((string) ($archive_ui['clearAllLabel'] ?? 'Clear all'))); ?></button>
                        <button class="st-filters__close" type="button" data-filters-close aria-label="<?php echo esc_attr(boblift_steel_t((string) ($archive_ui['closeFiltersLabel'] ?? 'Close filters'))); ?>"><?php echo boblift_steel_icon('close'); ?></button>
                    </div>
                </div>

                <?php if ($tree !== []) : ?>
                    <fieldset>
                        <legend><?php echo esc_html(boblift_steel_t((string) ($archive_ui['categoryFilterLabel'] ?? 'Product category'))); ?></legend>
                        <?php foreach ($tree as $node) : ?>
                            <?php $render_option('category', $node['slug'], $node['title'], max($node['count'], array_sum(array_column($node['children'], 'count'))), '', $node['children'] !== [] ? ['category-role' => 'parent'] : ['category-role' => 'single']); ?>
                            <?php if ($node['children'] !== []) : ?>
                                <div class="st-filters__children" data-children-of="<?php echo esc_attr($node['slug']); ?>" hidden>
                                    <?php foreach ($node['children'] as $child) : $render_option('category', $child['slug'], $child['title'], $child['count'], 'st-check--child', ['category-role' => 'child', 'category-parent' => $node['slug']]); endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </fieldset>
                <?php endif; ?>

                <?php
                $render_group(boblift_steel_t((string) ($archive_ui['capacityFilterLabel'] ?? 'Lifting capacity')), 'capacity', array_column($capacity_ranges, 'label', 'value'), $capacity_counts);
                $render_group(boblift_steel_t((string) ($archive_ui['boomFilterLabel'] ?? 'Boom type')), 'boom', $boom_options, $boom_counts);
                $render_group(boblift_steel_t((string) ($archive_ui['applicationFilterLabel'] ?? 'Application')), 'application', $application_options, $application_counts);
                $render_group(boblift_steel_t((string) ($archive_ui['availabilityFilterLabel'] ?? 'Availability')), 'status', $status_options, $status_counts);
                ?>
            </aside>
            <div class="st-filters__backdrop" data-filters-backdrop></div>

            <div class="st-results">
                <div class="st-toolbar">
                    <button class="st-toolbar__open" type="button" data-filters-open><?php echo boblift_steel_icon('filter'); ?><?php echo esc_html(boblift_steel_t((string) ($archive_ui['filterButtonLabel'] ?? 'Filter'))); ?></button>
                    <label class="st-select">
                        <span><?php echo esc_html(boblift_steel_t((string) ($archive_ui['sortByLabel'] ?? 'Sort by'))); ?></span>
                        <select data-sort aria-label="<?php echo esc_attr(boblift_steel_t((string) ($archive_ui['sortByLabel'] ?? 'Sort by'))); ?>">
                            <?php foreach ($sort_options as $value => $label) : ?>
                                <option value="<?php echo esc_attr((string) $value); ?>"><?php echo esc_html(boblift_steel_t((string) $label)); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php echo boblift_steel_icon('chevron-down'); ?>
                    </label>
                </div>

                <div class="st-grid st-grid--products" data-grid>
                    <?php foreach ($products as $index => $product) : boblift_steel_product_card($product, ['eager' => $index < 3, 'index' => $index + 1]); endforeach; ?>
                </div>

                <div class="st-empty" data-empty hidden>
                    <h2><?php echo esc_html(boblift_steel_t((string) ($archive_ui['emptyTitle'] ?? 'No matching products found'))); ?></h2>
                    <p><?php echo esc_html(boblift_steel_t((string) ($archive_ui['emptyText'] ?? 'Try adjusting your filters or contact us for a custom lifting solution.'))); ?></p>
                    <button class="st-btn st-btn--ghost" type="button" data-filter-reset><span class="st-btn__label"><?php echo esc_html(boblift_steel_t((string) ($archive_ui['clearFiltersLabel'] ?? 'Clear filters'))); ?></span><span class="st-btn__icon"><?php echo boblift_steel_icon('close'); ?></span></button>
                </div>

                <nav class="st-pagination" data-pagination aria-label="<?php echo esc_attr(boblift_steel_t((string) ($archive_ui['paginationLabel'] ?? 'Product pagination'))); ?>"></nav>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>
