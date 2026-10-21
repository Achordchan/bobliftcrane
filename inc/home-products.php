<?php
if (!defined('ABSPATH')) {
    exit;
}

function boblift_home_product_section_config(): array
{
    $home = boblift_options()['home'] ?? [];
    $section = $home['product_section'] ?? [];
    $product_ui = function_exists('boblift_site_ui') ? boblift_site_ui('productDetail') : [];

    return [
        'label' => $section['label'] ?? 'Main Products',
        'title' => $section['title'] ?? 'BOB-LIFT Crane Solutions',
        'maxCategoryProducts' => (int) ($section['max_category_products'] ?? 8),
        'showCategoryScrollButtons' => (bool) ($section['show_category_scroll_buttons'] ?? true),
        // One wording for every count, so no language needs plural forms (strings in inc/translations/home-products.php).
        'viewAllLabel' => (string) (($section['view_all_label'] ?? '') ?: boblift_localized_static_text('All models · {count}')),
        'requestQuoteLabel' => (string) ($product_ui['requestQuoteLabel'] ?? 'Request Quote'),
        'detailsShortLabel' => $section['details_short_label'] ?? 'Details',
        'viewDetailsLabel' => $section['view_details_label'] ?? 'View Details',
        'previousCategoriesLabel' => $section['previous_categories_label'] ?? 'Previous product categories',
        'nextCategoriesLabel' => $section['next_categories_label'] ?? 'Next product categories',
        'showProductSpecs' => (bool) ($section['show_product_specs'] ?? true),
        'selectedProductSlugs' => array_values(array_filter(array_map('strval', $section['selected_product_slugs'] ?? []))),
    ];
}

function boblift_home_product_catalog(array $section): array
{
    $categories = boblift_categories();
    usort($categories, static fn(array $a, array $b): int => (int) ($a['sort'] ?? 100) <=> (int) ($b['sort'] ?? 100));
    $category_map = [];
    foreach ($categories as $category) {
        $slug = (string) ($category['slug'] ?? '');
        if ($slug !== '') {
            $category_map[$slug] = $category;
        }
    }

    $products = boblift_all_products();
    $seed_by_slug = [];
    foreach (boblift_products_seed() as $seed_product) {
        $seed_by_slug[$seed_product['slug'] ?? ''] = $seed_product;
    }

    foreach ($products as &$product) {
        $seed = $seed_by_slug[$product['slug'] ?? ''] ?? [];
        $product['featured'] = (bool) ($seed['featured'] ?? false);
        $product['popularity'] = (int) ($seed['popularity'] ?? 50);
        $product['home_sort'] = (int) ($seed['sort_order'] ?? 100);
    }
    unset($product);

    $category_totals = [];
    $all_by_category = [];
    foreach ($products as $product) {
        $slug = (string) ($product['category'] ?? '');
        if ($slug !== '') {
            $category_totals[$slug] = ($category_totals[$slug] ?? 0) + 1;
            $all_by_category[$slug][] = $product;
        }
    }

    $selected_order = array_flip($section['selectedProductSlugs']);
    if ($selected_order !== []) {
        $products = array_values(array_filter($products, static fn(array $product): bool => isset($selected_order[(string) ($product['slug'] ?? '')])));
        usort($products, static fn(array $a, array $b): int => ($selected_order[(string) ($a['slug'] ?? '')] ?? 999) <=> ($selected_order[(string) ($b['slug'] ?? '')] ?? 999));
    }

    $products_by_category = [];
    foreach ($products as $product) {
        $slug = (string) ($product['category'] ?? '');
        if ($slug !== '') {
            $products_by_category[$slug][] = $product;
        }
    }

    $descendant_slugs = static function (string $slug): array {
        $term = get_term_by('slug', $slug, 'product_category');
        if (!$term instanceof WP_Term) {
            return [];
        }
        $descendants = get_terms(['taxonomy' => 'product_category', 'child_of' => $term->term_id, 'hide_empty' => false, 'fields' => 'id=>slug']);
        return is_wp_error($descendants) ? [] : array_values($descendants);
    };

    $catalog = [];
    foreach (boblift_product_category_hierarchy() as $group) {
        $parent_slug = (string) ($group['slug'] ?? '');
        if ($parent_slug === '') {
            continue;
        }
        $child_slugs = array_values(array_filter(array_map(static fn(array $child): string => (string) ($child['slug'] ?? ''), $group['children'] ?? [])));
        // Categories added below the listed children (grandchildren and deeper) belong to the family too.
        $child_slugs = array_values(array_unique(array_merge($child_slugs, $descendant_slugs($parent_slug))));
        $parent_products = $products_by_category[$parent_slug] ?? [];
        $total = $category_totals[$parent_slug] ?? 0;
        $family_products = $all_by_category[$parent_slug] ?? [];
        $buckets = [];
        foreach ($child_slugs as $slug) {
            $total += $category_totals[$slug] ?? 0;
            $family_products = array_merge($family_products, $all_by_category[$slug] ?? []);
            if (!empty($products_by_category[$slug])) {
                $buckets[] = $products_by_category[$slug];
            }
        }
        $group_products = array_merge($parent_products, ...$buckets);
        // A product listed in more than one bucket counts once.
        $seen_group_products = [];
        $group_products = array_values(array_filter($group_products, static function (array $product) use (&$seen_group_products): bool {
            $key = (string) ($product['id'] ?? $product['slug'] ?? '');
            if ($key !== '' && isset($seen_group_products[$key])) {
                return false;
            }
            $seen_group_products[$key] = true;
            return true;
        }));
        if ($group_products === []) {
            continue;
        }
        $limit = max(1, $section['maxCategoryProducts']);
        if ($selected_order !== []) {
            usort($group_products, static fn(array $a, array $b): int => ($selected_order[(string) ($a['slug'] ?? '')] ?? 999) <=> ($selected_order[(string) ($b['slug'] ?? '')] ?? 999));
            $picked = array_slice($group_products, 0, $limit);
        } else {
            $picked = boblift_home_product_pick($buckets, $parent_products, $limit);
        }
        boblift_home_product_sort_products($group_products);
        $parent_category = $category_map[$parent_slug] ?? (array) ($group['category'] ?? []);
        $title = (string) ($parent_category['title'] ?? ($group['title'] ?? $parent_slug));
        // The card shows a cut-out of the family's first model that has one, so every card reads the same way; a family
        // without any cut-out keeps a photo, shown filling its frame.
        // Candidates go heaviest first by the English spec table, so every language picks the same picture.
        $image_candidates = $group_products;
        $candidate_weight = static fn(array $product): float => boblift_home_product_rated_capacity($product) ?: boblift_home_product_capacity_value($product);
        usort($image_candidates, static fn(array $a, array $b): int => $candidate_weight($b) <=> $candidate_weight($a));
        $image = '';
        foreach ($image_candidates as $product) {
            $src = (string) boblift_product_image($product);
            if ($src !== '' && boblift_image_fit($src) === 'cutout') {
                $image = $src;
                break;
            }
        }
        // Next, a cut-out in a model's gallery, the site's own "-cutout" files before other transparent ones.
        foreach (['/-cutout\.png$/i', '/transparent\.png$/i'] as $pattern) {
            foreach ($image === '' ? $image_candidates : [] as $product) {
                foreach ((array) ($product['gallery'] ?? []) as $item) {
                    $src = is_array($item) ? (string) ($item['image'] ?? '') : (string) $item;
                    if ($src !== '' && preg_match($pattern, (string) strtok($src, '?'))) {
                        $image = $src;
                        break 3;
                    }
                }
            }
        }
        $image = $image !== '' ? $image : (string) (($group_products[0]['image'] ?? '') ?: ($parent_category['image'] ?? ''));

        // Sub-type links: the direct child categories that hold current models, each counted with its own descendants,
        // largest first.
        $children = [];
        foreach (($group['children'] ?? []) as $child) {
            $child_slug = (string) ($child['slug'] ?? '');
            $count = 0;
            foreach (array_merge([$child_slug], $descendant_slugs($child_slug)) as $slug) {
                $count += $category_totals[$slug] ?? 0;
            }
            if ($child_slug !== '' && $count > 0) {
                $child_title = (string) ($category_map[$child_slug]['title'] ?? ($child['title'] ?? $child_slug));
                $children[] = ['title' => $child_title, 'href' => boblift_product_nav_url('/' . $child_slug, $child_title), 'count' => $count];
            }
        }

        usort($children, static fn(array $a, array $b): int => $b['count'] <=> $a['count']);
        $intro = (array) ($parent_category['intro_paragraphs'] ?? []);
        $catalog[] = [
            'name' => $title,
            'image' => $image !== '' ? $image : 'images/products/crane-accessories.png',
            'image_fit' => $image !== '' ? boblift_image_fit(boblift_image_src($image)) : 'cutout',
            'icon' => (string) ($parent_category['icon'] ?? ''),
            'href' => boblift_product_nav_url('/' . $parent_slug, $title),
            'total' => $total,
            'intro' => trim(wp_strip_all_tags((string) ($intro[0] ?? ''))),
            'range' => boblift_home_product_capacity_range($family_products),
            'children' => $children,
            'products' => $picked,
        ];
    }

    return $catalog;
}

// Sub-types share the slots in turn, so the preview shows the range of a family instead of its heaviest few of one
// type; products filed directly under the parent only fill leftover slots. Within a sub-type the picks are spread
// evenly across its capacity range, drawn from models with a cut-out picture that no other pick shares when there are
// enough of them. The picks are then shown heaviest first.
function boblift_home_product_pick(array $buckets, array $parent_products, int $limit): array
{
    $image_key = static fn(array $product): string => (string) preg_replace('/^.*[\/=]/', '', (string) strtok(boblift_home_product_card_image($product), '&'));
    $is_cutout = static fn(array $product): bool => boblift_image_fit(boblift_home_product_card_image($product)) === 'cutout';
    $by_capacity = static fn(array $a, array $b): int => boblift_home_product_capacity_value($b) <=> boblift_home_product_capacity_value($a);
    foreach ($buckets as &$bucket) {
        boblift_home_product_sort_products($bucket);
    }
    unset($bucket);
    boblift_home_product_sort_products($parent_products);

    $counts = array_fill(0, count($buckets), 0);
    $taken = 0;
    for ($round = 0; $taken < $limit; $round++) {
        $added = false;
        foreach (array_keys($buckets) as $key) {
            if ($round < count($buckets[$key]) && $taken < $limit) {
                $counts[$key]++;
                $taken++;
                $added = true;
            }
        }
        if (!$added) {
            break;
        }
    }

    $picked = [];
    $used_images = [];
    foreach ($buckets as $key => $bucket) {
        $wanted = $counts[$key] ?? 0;
        if ($wanted === 0) {
            continue;
        }
        $preferred = [];
        $seen = [];
        foreach ($bucket as $product) {
            $image = $image_key($product);
            if ($is_cutout($product) && !isset($seen[$image]) && !isset($used_images[$image])) {
                $preferred[] = $product;
                $seen[$image] = true;
            }
        }
        $pool = count($preferred) >= $wanted ? $preferred : array_merge($preferred, array_values(array_filter($bucket, static fn(array $product): bool => !in_array($product, $preferred, true))));
        usort($pool, $by_capacity);
        $last = count($pool) - 1;
        $indexes = $wanted === 1 ? [0] : array_unique(array_map(static fn(int $step): int => (int) round($step * $last / ($wanted - 1)), range(0, $wanted - 1)));
        foreach ($indexes as $index) {
            $picked[] = $pool[$index];
            $used_images[$image_key($pool[$index])] = true;
        }
    }
    foreach ($parent_products as $product) {
        if (count($picked) >= $limit) {
            break;
        }
        $picked[] = $product;
    }
    usort($picked, $by_capacity);

    return $picked;
}

function boblift_home_product_sort_products(array &$products): void
{
    usort($products, static function (array $a, array $b): int {
        return ((int) ($b['featured'] ?? false) <=> (int) ($a['featured'] ?? false))
            ?: ((int) ($a['home_sort'] ?? 100) <=> (int) ($b['home_sort'] ?? 100))
            ?: (boblift_home_product_capacity_value($b) <=> boblift_home_product_capacity_value($a))
            ?: ((int) ($b['popularity'] ?? 50) <=> (int) ($a['popularity'] ?? 50));
    });
}

function boblift_home_product_capacity_value(array $product): float
{
    if (isset($product['capacity_value']) && is_numeric($product['capacity_value'])) {
        return (float) $product['capacity_value'];
    }
    if (preg_match('/([0-9]+(?:[.-][0-9]+)?)/', (string) ($product['capacity'] ?? $product['title'] ?? ''), $match)) {
        return (float) str_replace('-', '.', $match[1]);
    }
    return 0.0;
}

/** The English spec rows: their labels are the same in every language, the translations carry translated ones. */
function boblift_home_product_english_specs(array $product): array
{
    static $cache = [];
    $id = (int) ($product['id'] ?? 0);
    if ($id <= 0 || !function_exists('boblift_product_english_id')) {
        return array_values(array_filter((array) ($product['specs'] ?? []), 'is_array'));
    }
    $english_id = boblift_product_english_id($id);
    return $cache[$english_id] ??= array_values(array_filter((array) boblift_decode_meta(get_post_meta($english_id, '_specs', true), []), 'is_array'));
}

// Rated capacity in tonnes from the "Max Lifting Capacity" row or a capacity text with its unit; 0 without either. The
// numeric capacity value is not used: aerial work platforms keep their working height in metres there.
function boblift_home_product_rated_capacity(array $product): float
{
    foreach (boblift_home_product_english_specs($product) as $row) {
        if (trim((string) ($row['label'] ?? '')) === 'Max Lifting Capacity' && preg_match('/^([\d.,]+)(?:\s*[-–]\s*([\d.,]+))?\s*(kg|t|tons?)(?![\p{L}·-])/u', trim((string) ($row['value'] ?? '')), $m)) {
            // A span such as "1-2 tons" is rated by its upper figure.
            $number = (float) str_replace(',', '', $m[2] !== '' ? $m[2] : $m[1]);
            return $m[3] === 'kg' ? $number / 1000 : $number;
        }
    }
    // A product without a spec table may still state its capacity with a unit ("2.5 ton"), read in English.
    $id = (int) ($product['id'] ?? 0);
    $capacity_text = $id > 0 && function_exists('boblift_product_english_id') ? (string) get_post_meta(boblift_product_english_id($id), '_capacity', true) : (string) ($product['capacity'] ?? '');
    if (preg_match('/^([\d.,]+)\s*(kg|t|tons?)$/iu', trim($capacity_text), $m)) {
        $number = (float) str_replace(',', '', $m[1]);
        return strtolower($m[2]) === 'kg' ? $number / 1000 : $number;
    }
    return 0.0;
}

/** "20 t", "800 kg" (below one tonne), joined by a no-break space. */
function boblift_home_product_tonnage_label(float $tonnes): string
{
    return $tonnes < 1 ? boblift_tonnes_label($tonnes * 1000) . "\u{00A0}kg" : boblift_tonnes_label($tonnes) . "\u{00A0}t";
}

/** "17.5 m" from a value starting with millimetres or metres ("17500 mm (3 m mast) / …"); '' otherwise. */
function boblift_home_product_length_label(string $value): string
{
    if (!preg_match('/^([\d.,]+)\s*(mm|m)(?!\p{L})/u', trim($value), $m)) {
        return '';
    }
    return boblift_tonnes_label((float) str_replace(',', '', $m[1]) / ($m[2] === 'mm' ? 1000 : 1)) . "\u{00A0}m";
}

// A family's capacity span, e.g. "1.6–40 t". Only when at least 80% of its models state a rated capacity, so a family
// rated by another measure (working height, platform size) shows its model count alone.
function boblift_home_product_capacity_range(array $products): string
{
    if ($products === []) {
        return '';
    }
    $rated = array_values(array_filter(array_map('boblift_home_product_rated_capacity', $products), static fn(float $value): bool => $value > 0));
    if (count($rated) / count($products) < 0.8) {
        return '';
    }
    $min = min($rated);
    $max = max($rated);
    if ($min === $max) {
        return boblift_home_product_tonnage_label($max);
    }
    return $min >= 1
        ? boblift_tonnes_label($min) . '–' . boblift_tonnes_label($max) . "\u{00A0}t"
        : boblift_home_product_tonnage_label($min) . '–' . boblift_home_product_tonnage_label($max);
}

/** Width over height of an uploaded image (the WebP converter's passthru URL included); 0 when it cannot be read. */
function boblift_home_product_image_ratio(string $url): float
{
    static $cache = [];
    if (isset($cache[$url])) {
        return $cache[$url];
    }
    $source = html_entity_decode($url);
    if (preg_match('/[?&]src=([^&]+)/', $source, $m)) {
        $source = rawurldecode($m[1]);
    }
    $uploads = wp_get_upload_dir();
    $path = str_starts_with($source, (string) $uploads['baseurl']) ? $uploads['basedir'] . substr((string) strtok($source, '?'), strlen((string) $uploads['baseurl'])) : '';
    $size = $path !== '' && is_readable($path) ? @getimagesize($path) : false;
    return $cache[$url] = $size && $size[1] > 0 ? $size[0] / $size[1] : 0.0;
}

/** The card picture: the model's own photo when it is a cut-out, else a cut-out from its gallery, else the photo. */
function boblift_home_product_card_image(array $product): string
{
    $main = (string) boblift_product_image($product);
    if ($main === '' || boblift_image_fit($main) === 'cutout') {
        return $main;
    }
    foreach ((array) ($product['gallery'] ?? []) as $item) {
        $src = is_array($item) ? (string) ($item['image'] ?? '') : (string) $item;
        if ($src !== '' && preg_match('/-cutout\.png$/i', (string) strtok($src, '?'))) {
            // A theme-relative path ("images/products/x-cutout.png") resolves to the theme's assets; full and root-relative URLs stay.
            return str_starts_with($src, '/') ? $src : boblift_image_src($src);
        }
    }
    return $main;
}

/**
 * One-line card label as HTML: rated capacity and boom type ("20 t · Telescopic Boom"), else the product's category.
 * The tonnage keeps its lower-case unit and left-to-right order inside the upper-case, possibly right-to-left label.
 */
function boblift_home_product_card_kicker(array $product): string
{
    $parts = [];
    $capacity = boblift_home_product_rated_capacity($product);
    if ($capacity > 0) {
        $parts[] = '<bdi class="normal-case text-ink" dir="ltr">' . esc_html(boblift_home_product_tonnage_label($capacity)) . '</bdi>';
    }
    $boom = boblift_boom_label((string) ($product['boom_type'] ?? ''));
    if ($boom !== '') {
        $parts[] = esc_html($boom);
    }
    return $parts !== [] ? implode(' · ', $parts) : esc_html((string) ($product['category_title'] ?? 'BOB-LIFT'));
}

// Two figures for the card footer. The capacity is already in the card label, so they are the lifting moment and the
// working radius, then the lifting height. Product lines label these rows differently, so the English rows are matched by
// alias. Missing ones are filled from the self weight, then the first other numeric rows; a product with none shows
// no footer figures.
// Each entry: label, value.
function boblift_home_product_card_specs(array $product): array
{
    $english = boblift_home_product_english_specs($product);
    $local = array_values(array_filter((array) ($product['specs'] ?? []), 'is_array'));
    $same_rows = count($local) === count($english);
    $is_figure = static fn(string $value): bool => (bool) preg_match('/^[\d.,]+\s*[^\d\s]/u', $value);
    // A translated row stands for the English row at the same index only when it carries the same value or the translated
    // label: an editor may have reordered the rows. Otherwise the English value is shown under the translated English label.
    $row_item = static function (int $index, string $label = '') use ($english, $local, $same_rows): array {
        $source = $english[$index];
        $source_label = trim((string) ($source['label'] ?? ''));
        $source_value = trim((string) ($source['value'] ?? ''));
        $row = $same_rows ? $local[$index] : null;
        $matches = is_array($row) && (trim((string) ($row['value'] ?? '')) === $source_value
            || trim((string) ($row['label'] ?? '')) === trim(boblift_localized_static_text($source_label)));
        if (!$matches) {
            return ['label' => $label !== '' ? $label : boblift_localized_static_text($source_label), 'value' => $source_value];
        }
        return ['label' => $label !== '' ? $label : trim((string) ($row['label'] ?? '')), 'value' => trim((string) ($row['value'] ?? ''))];
    };
    $items = [];
    $used = [];
    foreach ($english as $index => $row) {
        if (trim((string) ($row['label'] ?? '')) === 'Max Lifting Capacity') {
            $used[$index] = true;
        }
    }
    foreach ($english as $index => $row) {
        if (trim((string) ($row['label'] ?? '')) === 'Max Lifting Moment' && $is_figure(trim((string) ($row['value'] ?? '')))) {
            $items[] = $row_item($index, (string) (boblift_site_ui('productArchive')['momentShortLabel'] ?? 'Moment'));
            $used[$index] = true;
            break;
        }
    }
    $reach_aliases = [
        '/^max\.?\s+(working\s+)?(radius|range|outreach|reach)\b/i' => 'Max. radius',
        '/^(max\.?\s+)?(lifting|working|hook)\s+height\b/i' => 'Max. height',
    ];
    foreach ($reach_aliases as $pattern => $label) {
        foreach ($english as $index => $row) {
            $value = boblift_home_product_length_label((string) ($row['value'] ?? ''));
            if ($value !== '' && preg_match($pattern, trim((string) ($row['label'] ?? '')))) {
                $items[] = ['label' => boblift_localized_static_text($label), 'value' => $value];
                $used[$index] = true;
                break;
            }
        }
    }
    // The self weight comes first among the rest: buyers match it to their chassis or forklift.
    foreach ($english as $index => $row) {
        if (count($items) < 2 && !isset($used[$index]) && trim((string) ($row['label'] ?? '')) === 'Self Weight' && $is_figure(trim((string) ($row['value'] ?? '')))) {
            $weight = $row_item($index, boblift_localized_static_text('Self weight'));
            if (preg_match('/^([\d.,]+)\s*kg$/u', trim((string) ($row['value'] ?? '')), $m)) {
                $weight['value'] = boblift_home_product_tonnage_label((float) str_replace(',', '', $m[1]) / 1000);
            }
            $items[] = $weight;
            $used[$index] = true;
        }
    }
    foreach ($english as $index => $row) {
        if (count($items) >= 2) {
            break;
        }
        if (!isset($used[$index]) && $is_figure(trim((string) ($row['value'] ?? '')))) {
            $items[] = $row_item($index);
        }
    }
    return array_slice($items, 0, 2);
}

function boblift_render_home_products(): void
{
    $section = boblift_home_product_section_config();
    $catalog = boblift_home_product_catalog($section);
    if (!$catalog) {
        return;
    }
    ?>
    <section id="products" class="relative overflow-hidden bg-[linear-gradient(180deg,#ffffff_0%,#f8fbff_48%,#ffffff_100%)] py-10 md:py-14" data-home-products>
        <div class="page-shell">
            <div class="relative">
                <div class="mb-5 flex flex-col gap-3 md:mb-8 md:gap-4 lg:mb-8 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="section-label mb-3"><?php echo esc_html($section['label']); ?></p>
                        <h2 class="text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em] text-ink [text-wrap:balance] [word-break:auto-phrase]"><?php echo esc_html($section['title']); ?></h2>
                    </div>
                </div>

                <?php $category_count = count($catalog); $fits = $category_count <= 6; ?>
                <div class="boblift-cat-wrap relative z-10 <?php echo $fits ? 'is-fit' : ''; ?>" style="--cat-count: <?php echo esc_attr((string) $category_count); ?>">
                    <?php if ($section['showCategoryScrollButtons']) : ?><button class="boblift-cat-arrow group absolute -start-4 top-1/2 z-20 hidden h-10 w-10 -translate-y-1/2 place-items-center rounded-sm border border-blue-100 bg-white/95 text-[#08234a] shadow-card backdrop-blur transition duration-300 hover:scale-105 hover:border-blue-300 hover:text-blue-700 disabled:pointer-events-none disabled:opacity-30 lg:grid" type="button" data-product-scroll="prev" aria-label="<?php echo esc_attr($section['previousCategoriesLabel']); ?>" disabled><?php echo boblift_icon('chevron-left', 'h-5 w-5 drop-shadow-sm stroke-[3] rtl:-scale-x-100'); ?></button><?php endif; ?>
                    <div class="boblift-cat-scroller boblift-swipe-fade" data-product-category-scroller>
                        <div class="boblift-cat-track" role="tablist" aria-label="<?php echo esc_attr($section['label']); ?>">
                            <?php foreach ($catalog as $index => $category) : ?>
                                <?php $chip_icon = $category['icon'] !== '' ? 'images/mega-menu-icons/' . sanitize_key($category['icon']) . '.png' : ''; ?>
                                <button id="home-products-tab-<?php echo esc_attr((string) $index); ?>" class="boblift-cat-card group" type="button" role="tab" aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>" tabindex="<?php echo $index === 0 ? '0' : '-1'; ?>" aria-controls="home-products-panel-<?php echo esc_attr((string) $index); ?>" data-product-category-tab="<?php echo esc_attr((string) $index); ?>">
                                    <span class="boblift-cat-media<?php echo ($category['image_fit'] ?? '') === 'photo' ? ' is-photo' : ''; ?>"><img src="<?php echo esc_url(boblift_image_src($category['image'])); ?>" alt="" loading="lazy" decoding="async"></span>
                                    <?php if ($chip_icon !== '' && is_readable(boblift_asset_path($chip_icon))) : ?><img class="boblift-cat-chip-icon" src="<?php echo esc_url(boblift_asset($chip_icon)); ?>" alt="" width="26" height="26" loading="lazy" decoding="async"><?php endif; ?>
                                    <span class="boblift-cat-label"><span class="boblift-cat-name"><?php echo esc_html($category['name']); ?></span><?php echo boblift_icon('arrow-right', 'boblift-cat-arrow-icon h-[18px] w-[18px]'); ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php if ($section['showCategoryScrollButtons']) : ?><button class="boblift-cat-arrow group absolute -end-4 top-1/2 z-20 hidden h-10 w-10 -translate-y-1/2 place-items-center rounded-sm border border-blue-100 bg-white/95 text-[#08234a] shadow-card backdrop-blur transition duration-300 hover:scale-105 hover:border-blue-300 hover:text-blue-700 disabled:pointer-events-none disabled:opacity-30 lg:grid" type="button" data-product-scroll="next" aria-label="<?php echo esc_attr($section['nextCategoriesLabel']); ?>" disabled><?php echo boblift_icon('chevron-right', 'h-5 w-5 drop-shadow-sm stroke-[3] rtl:-scale-x-100'); ?></button><?php endif; ?>
                </div>

                <div class="relative z-10 mt-5 sm:mt-8" data-product-stage>
                    <?php foreach ($catalog as $index => $category) : boblift_render_home_product_grid($category, $index, $section); endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    <?php
}

function boblift_render_home_product_grid(array $category, int $index, array $section): void
{
    $total = (int) ($category['total'] ?? count($category['products']));
    $view_all_label = $total > 1 ? str_replace('{count}', (string) $total, $section['viewAllLabel']) : '';
    $children = (array) ($category['children'] ?? []);
    $range = (string) ($category['range'] ?? '');
    ?>
    <div id="home-products-panel-<?php echo esc_attr((string) $index); ?>" class="product-flip-panel <?php echo $index === 0 ? '' : 'hidden'; ?>" role="tabpanel" aria-labelledby="home-products-tab-<?php echo esc_attr((string) $index); ?>" data-product-grid-panel="<?php echo esc_attr((string) $index); ?>">
        <div class="mb-4 border-b border-line pb-4 sm:mb-5 md:mb-7 md:pb-5">
            <div class="flex items-start justify-between gap-4 lg:gap-10">
                <div class="min-w-0 flex-1">
                    <h3 class="text-xl font-black leading-tight tracking-[-0.005em] text-ink sm:text-[26px] xl:text-[30px]"><?php echo esc_html($category['name']); ?></h3>
                    <?php if (($category['intro'] ?? '') !== '') : ?><?php // Phones go straight from the family name to its figures; the intro shows from 640px up. ?><p class="mt-2 line-clamp-2 max-w-[62rem] text-[15px] max-sm:hidden leading-6 text-slate-500 [text-wrap:pretty] [word-break:auto-phrase] sm:text-[15px]"><?php echo esc_html($category['intro']); ?></p><?php endif; ?>
                </div>
                <div class="hidden shrink-0 items-center gap-2 sm:flex">
                    <a class="inline-flex items-center justify-center whitespace-nowrap rounded-sm border border-line bg-white px-5 py-2.5 text-xs font-black text-ink transition hover:-translate-y-0.5 hover:border-blue-300 hover:text-blue-700 md:px-6 md:py-3 md:text-sm" href="<?php echo esc_url(boblift_url('/contact.html#inquiry')); ?>" data-inquiry-modal-open data-inquiry-source="首页产品分类" data-inquiry-product="<?php echo esc_attr($category['name']); ?>"><?php echo esc_html($section['requestQuoteLabel']); ?></a>
                    <?php if ($view_all_label !== '') : ?><a class="inline-flex shrink-0 items-center justify-center gap-1.5 whitespace-nowrap rounded-sm bg-navy px-5 py-2.5 text-xs font-black text-white shadow-card transition hover:-translate-y-0.5 hover:bg-[#0b2d5b] md:px-6 md:py-3 md:text-sm" href="<?php echo esc_url($category['href']); ?>"><?php echo esc_html($view_all_label); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4 rtl:-scale-x-100'); ?></a><?php endif; ?>
                </div>
            </div>
            <?php // The model count and range share a row with the sub-type links on wide screens. ?>
            <div class="mt-3 flex flex-col gap-3 sm:mt-4 lg:flex-row lg:items-center lg:gap-5">
                <p class="shrink-0 text-sm font-bold text-slate-600"><?php echo esc_html(str_replace('{count}', (string) $total, boblift_localized_static_text('Models: {count}'))); ?><?php if ($range !== '') : ?>&nbsp;· <bdi class="font-black text-ink" dir="ltr"><?php echo esc_html($range); ?></bdi><?php endif; ?></p>
            <?php if (count($children) >= 2) : ?>
                <nav class="boblift-swipe-fade -mx-4 flex gap-2 overflow-x-auto px-4 [scrollbar-width:none] sm:mx-0 sm:flex-wrap sm:overflow-visible sm:px-0 [&>a]:h-8 [&>a]:shrink-0 [&>a]:px-2.5 [&>a]:text-[13px] sm:[&>a]:h-9 sm:[&>a]:px-3 sm:[&>a]:text-sm [&::-webkit-scrollbar]:hidden" aria-label="<?php echo esc_attr(boblift_localized_static_text('Product types') . ': ' . $category['name']); ?>">
                    <?php foreach ($children as $child) : ?>
                        <a class="boblift-category-subtab" href="<?php echo esc_url($child['href']); ?>"><?php echo esc_html($child['title']); ?> <span data-tab-count><?php echo esc_html((string) $child['count']); ?></span></a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>
            </div>
        </div>
        <?php if (count($category['products']) === 1) : ?>
            <?php boblift_render_home_product_feature($category['products'][0], $section); ?>
        <?php else : ?>
        <?php
        // One row of models that scrolls sideways: two, three or four in view, the rest a swipe or an arrow away (still in the page).
        $scrolls = count($category['products']) > 4;
        ?>
        <div class="relative" data-product-carousel>
        <?php if ($scrolls) : ?><button class="group absolute top-[38%] z-20 hidden h-10 w-10 -translate-y-1/2 place-items-center rounded-sm border border-blue-100 bg-white/95 text-[#08234a] shadow-card backdrop-blur transition duration-300 hover:border-blue-300 hover:text-blue-700 disabled:pointer-events-none disabled:opacity-0 -start-5 lg:grid" type="button" data-product-row-scroll="prev" aria-label="<?php echo esc_attr(boblift_localized_static_text('Show previous models')); ?>" disabled><?php echo boblift_icon('chevron-left', 'h-5 w-5 stroke-[3] rtl:-scale-x-100'); ?></button><?php endif; ?>
        <div class="-mx-4 grid snap-x snap-mandatory auto-cols-[44%] grid-flow-col gap-3 overflow-x-auto scroll-px-4 px-4 pb-2 [scrollbar-width:none] sm:mx-0 sm:auto-cols-[calc((100%-1.5rem)/2)] sm:gap-6 sm:scroll-px-0 sm:px-0 md:auto-cols-[calc((100%-3rem)/3)] lg:auto-cols-[calc((100%-3.75rem)/4)] lg:gap-5 xl:auto-cols-[calc((100%-5.25rem)/4)] xl:gap-7 [&::-webkit-scrollbar]:hidden" data-product-row>
            <?php foreach ($category['products'] as $product) : ?>
                <?php
                $card_model = trim((string) ($product['model'] ?? ''));
                $card_specs = $section['showProductSpecs'] ? boblift_home_product_card_specs($product) : [];
                // A landscape photo fills its frame; a cut-out, or a square studio shot, stands in it with room around.
                $card_image = boblift_home_product_card_image($product);
                $card_photo = boblift_image_fit($card_image) === 'photo' && boblift_home_product_image_ratio($card_image) >= 1.15;
                ?>
                <a class="group flex snap-start flex-col overflow-hidden rounded border border-line bg-white text-start shadow-card transition duration-300 [@media(hover:hover)]:hover:border-blue-300" href="<?php echo esc_url(boblift_product_url($product)); ?>">
                    <span class="relative block h-[110px] w-full overflow-hidden bg-[radial-gradient(circle_at_48%_34%,#ffffff_0%,#f8fafc_34%,#eef2f7_64%,#e2e8f0_100%)] sm:h-auto sm:aspect-[1.3/1] md:max-h-[190px] lg:max-h-[220px] 2xl:max-h-[260px]"><span class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_50%_82%,rgba(15,23,42,0.12),transparent_44%)]"></span><img class="absolute inset-0 z-10 h-full w-full transition duration-300 group-hover:scale-[1.035] <?php echo $card_photo ? 'object-cover' : 'object-contain p-3 drop-shadow-[0_6px_8px_rgba(11,27,51,0.10)] sm:p-5'; ?>" src="<?php echo esc_url($card_image); ?>" alt="<?php echo esc_attr($product['title'] ?? ''); ?>" loading="lazy" decoding="async"></span>
                    <span class="mt-3 block truncate px-3 text-[11px] font-black uppercase leading-4 tracking-[0.08em] text-slate-400 rtl:tracking-normal sm:mt-5 sm:px-6 sm:text-xs sm:leading-5 sm:tracking-[0.1em]"><?php echo boblift_home_product_card_kicker($product); // Escaped part by part. ?></span>
                    <?php if ($card_model !== '') : ?>
                        <span class="boblift-home-product-card-title mt-1 block px-3 text-[clamp(22px,5.5vw,30px)] font-black uppercase leading-none tracking-[0.025em] text-ink sm:px-6 sm:text-[34px] sm:tracking-[0.035em]"><?php echo esc_html($card_model); ?></span>
                    <?php else : ?>
                        <span class="mt-1 line-clamp-2 px-3 text-base font-black leading-tight text-ink sm:px-6 sm:text-xl"><?php echo esc_html((string) ($product['title'] ?? 'BOB-LIFT')); ?></span>
                    <?php endif; ?>
                    <span class="mx-3 mb-3 mt-auto block pt-3 sm:mx-6 sm:mb-5 sm:pt-4">
                        <span class="flex items-end gap-3 border-t border-line pt-3 sm:pt-4">
                            <?php if ($card_specs) : ?>
                                <span class="grid min-w-0 flex-1 grid-cols-2 gap-2 sm:gap-4">
                                    <?php foreach ($card_specs as $spec) : ?>
                                        <span class="block min-w-0"><span class="block truncate text-[11px] font-bold leading-4 text-slate-400 sm:text-xs"><?php echo esc_html($spec['label']); ?></span><span class="mt-0.5 block truncate text-sm font-black text-ink sm:text-base" dir="ltr"><?php echo esc_html($spec['value']); ?></span></span>
                                    <?php endforeach; ?>
                                </span>
                            <?php endif; ?>
                            <?php // The whole card is the link; the arrow only marks it. ?>
                            <span class="ms-auto hidden size-8 shrink-0 place-items-center rounded-sm text-blue-600 transition group-hover:bg-blue-50 sm:grid" aria-hidden="true"><?php echo boblift_icon('arrow-right', 'h-4 w-4 transition duration-300 group-hover:translate-x-0.5 rtl:-scale-x-100 rtl:group-hover:-translate-x-0.5'); ?></span>
                        </span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php if ($scrolls) : ?><button class="group absolute top-[38%] z-20 hidden h-10 w-10 -translate-y-1/2 place-items-center rounded-sm border border-blue-100 bg-white/95 text-[#08234a] shadow-card backdrop-blur transition duration-300 hover:border-blue-300 hover:text-blue-700 disabled:pointer-events-none disabled:opacity-0 -end-5 lg:grid" type="button" data-product-row-scroll="next" aria-label="<?php echo esc_attr(boblift_localized_static_text('Show more models')); ?>"><?php echo boblift_icon('chevron-right', 'h-5 w-5 stroke-[3] rtl:-scale-x-100'); ?></button><?php endif; ?>
        </div>
        <?php endif; ?>
        <div class="mt-4 flex gap-2 sm:hidden">
            <?php if ($view_all_label !== '') : ?><a class="flex h-12 min-w-0 flex-1 items-center justify-center gap-1.5 rounded-sm bg-navy px-5 text-sm font-black text-white shadow-card transition hover:bg-[#0b2d5b]" href="<?php echo esc_url($category['href']); ?>"><?php echo esc_html($view_all_label); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4 rtl:-scale-x-100'); ?></a><?php endif; ?>
            <a class="flex h-12 flex-1 items-center justify-center whitespace-nowrap rounded-sm border border-line bg-white px-4 text-sm font-black text-ink transition hover:border-blue-300 hover:text-blue-700" href="<?php echo esc_url(boblift_url('/contact.html#inquiry')); ?>" data-inquiry-modal-open data-inquiry-source="首页产品分类" data-inquiry-product="<?php echo esc_attr($category['name']); ?>"><?php echo esc_html($section['requestQuoteLabel']); ?></a>
        </div>
    </div>
    <?php
}

// A family with a single model gets one wide card instead of a lone tile in a four-column row.
function boblift_render_home_product_feature(array $product, array $section): void
{
    $model = trim((string) ($product['model'] ?? ''));
    $specs = array_values(array_filter((array) ($product['specs'] ?? []), static fn($spec): bool => is_array($spec) && trim((string) ($spec['label'] ?? '')) !== '' && trim((string) ($spec['value'] ?? '')) !== ''));
    $summary = trim(wp_strip_all_tags((string) ($product['short_description'] ?? '')));
    $detail_url = boblift_product_url($product);
    ?>
    <article class="grid overflow-hidden rounded border border-line bg-white shadow-card lg:min-h-[320px] lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]">
        <a class="group relative block aspect-[1.4/1] overflow-hidden bg-[radial-gradient(circle_at_48%_34%,#ffffff_0%,#f8fafc_34%,#eef2f7_64%,#e2e8f0_100%)] lg:aspect-auto" href="<?php echo esc_url($detail_url); ?>"><img class="absolute inset-0 z-10 h-full w-full object-contain p-6 drop-shadow-[0_6px_8px_rgba(11,27,51,0.10)] transition duration-300 group-hover:scale-[1.03] sm:p-10" src="<?php echo esc_url(boblift_product_image($product)); ?>" alt="<?php echo esc_attr($product['title'] ?? ''); ?>" loading="lazy" decoding="async"></a>
        <div class="flex min-w-0 flex-col justify-center p-5 sm:p-8 lg:p-10">
            <?php if ($model !== '') : ?><p class="text-[11px] font-black uppercase tracking-[0.1em] text-slate-400 rtl:tracking-normal sm:text-xs"><?php echo esc_html($model); ?></p><?php endif; ?>
            <h4 class="mt-2 text-[26px] font-black leading-[1.05] tracking-[-0.005em] text-ink sm:text-[32px] xl:text-[38px]"><?php echo esc_html($product['title'] ?? ''); ?></h4>
            <?php if ($section['showProductSpecs'] && $specs) : ?>
                <dl class="mt-5 grid gap-px overflow-hidden rounded border border-line bg-line sm:mt-6">
                    <?php foreach (array_slice($specs, 0, 3) as $spec) : ?>
                        <div class="flex items-center justify-between gap-4 bg-white px-4 py-3 text-sm"><dt class="font-bold text-slate-500"><?php echo esc_html((string) $spec['label']); ?></dt><dd class="text-end font-black text-ink"><?php echo esc_html((string) $spec['value']); ?></dd></div>
                    <?php endforeach; ?>
                </dl>
            <?php endif; ?>
            <?php if ($summary !== '') : ?><p class="mt-5 line-clamp-3 text-sm leading-6 text-slate-500 sm:text-base sm:leading-7"><?php echo esc_html($summary); ?></p><?php endif; ?>
            <div class="mt-6 flex flex-wrap gap-3 sm:mt-8">
                <a class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-sm bg-navy px-5 py-3 text-sm font-black text-white shadow-card transition hover:-translate-y-0.5 hover:bg-[#0b2d5b] md:px-6" href="<?php echo esc_url($detail_url); ?>"><?php echo esc_html($section['viewDetailsLabel']); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4 rtl:-scale-x-100'); ?></a>
                <a class="inline-flex items-center justify-center whitespace-nowrap rounded-sm border border-line bg-white px-5 py-3 text-sm font-black text-ink transition hover:-translate-y-0.5 hover:border-blue-300 hover:text-blue-700 md:px-6" href="<?php echo esc_url((string) ($product['inquiry_url'] ?? boblift_url('/contact.html#inquiry'))); ?>" data-inquiry-modal-open data-inquiry-source="首页产品卡片" data-inquiry-product="<?php echo esc_attr((string) ($product['title'] ?? '')); ?>" data-inquiry-product-slug="<?php echo esc_attr((string) ($product['slug'] ?? '')); ?>"><?php echo esc_html($section['requestQuoteLabel']); ?></a>
            </div>
        </div>
    </article>
    <?php
}
