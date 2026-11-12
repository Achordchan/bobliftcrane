<?php
if (!defined('ABSPATH')) {
    exit;
}

function boblift_home_product_section_config(): array
{
    $home = boblift_options()['home'] ?? [];
    $section = $home['product_section'] ?? [];
    $product_ui = function_exists('boblift_site_ui') ? boblift_site_ui('productDetail') : [];
    $saved_label = static function (string $label, string $english): string {
        $label = trim($label);
        return $label === '' || $label === $english ? boblift_series_text($english) : $label;
    };

    return [
        'label' => $section['label'] ?? 'Main Products',
        'title' => $section['title'] ?? 'BOB-LIFT Crane Solutions',
        'maxCategoryProducts' => (int) ($section['max_category_products'] ?? 8),
        // The product cards' own button wording (Site UI > productDetail), else the translated defaults, as on the product lists.
        'requestQuoteLabel' => $saved_label((string) ($product_ui['requestQuoteLabel'] ?? ''), 'Get a Quote'),
        'viewDetailsLabel' => $saved_label((string) (($section['view_details_label'] ?? '') ?: ($product_ui['viewDetailsLabel'] ?? '')), 'View Details'),
        'selectedProductSlugs' => array_values(array_filter(array_map('strval', $section['selected_product_slugs'] ?? []))),
        'allProductsLabel' => boblift_localized_static_text('All products'),
        'bestSellersLabel' => boblift_localized_static_text('Top-selling models'),
        'bestSellersTitle' => boblift_localized_static_text('The best-selling model in each range'),
        'bestSellerBadge' => boblift_localized_static_text('Best seller'),
        // Family slug => the product (by its English slug) shown as that family's best seller, and for a series page the
        // configuration it stands for. A family saved with a product replaces its default (theme settings, 3. 产品区).
        'bestSellers' => array_merge(boblift_home_best_seller_defaults(), array_filter((array) ($section['best_sellers'] ?? []), static fn($pick): bool => is_array($pick) && trim((string) ($pick['product'] ?? '')) !== '')),
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
            'slug' => $parent_slug,
            'name' => $title,
            'image' => $image !== '' ? $image : 'images/products/crane-accessories.png',
            'image_fit' => $image !== '' ? boblift_image_fit(boblift_home_image_url($image)) : 'cutout',
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

/**
 * A spec figure as a number. A comma followed by groups of three digits separates thousands ("6,300" is 6300); any other
 * comma is a decimal comma ("6,3" is 6.3).
 */
function boblift_home_spec_number(string $figure): float
{
    $figure = trim($figure);
    if (preg_match('/^\d{1,3}(?:,\d{3})+(?:\.\d+)?$/', $figure)) {
        return (float) str_replace(',', '', $figure);
    }
    return (float) str_replace(',', '.', $figure);
}

// Rated capacity in tonnes from the "Max Lifting Capacity" row or a capacity text with its unit; 0 without either. The
// numeric capacity value is not used: aerial work platforms keep their working height in metres there.
function boblift_home_product_rated_capacity(array $product): float
{
    foreach (boblift_home_product_english_specs($product) as $row) {
        if (trim((string) ($row['label'] ?? '')) === 'Max Lifting Capacity' && preg_match('/^([\d.,]+)(?:\s*[-–]\s*([\d.,]+))?\s*(kg|t|tons?)(?![\p{L}·-])/u', trim((string) ($row['value'] ?? '')), $m)) {
            // A span such as "1-2 tons" is rated by its upper figure.
            $number = boblift_home_spec_number($m[2] !== '' ? $m[2] : $m[1]);
            return $m[3] === 'kg' ? $number / 1000 : $number;
        }
    }
    // A product without a spec table may still state its capacity with a unit ("2.5 ton"), read in English.
    $id = (int) ($product['id'] ?? 0);
    $capacity_text = $id > 0 && function_exists('boblift_product_english_id') ? (string) get_post_meta(boblift_product_english_id($id), '_capacity', true) : (string) ($product['capacity'] ?? '');
    if (preg_match('/^([\d.,]+)\s*(kg|t|tons?)$/iu', trim($capacity_text), $m)) {
        $number = boblift_home_spec_number($m[1]);
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
    return boblift_tonnes_label(boblift_home_spec_number($m[1]) / ($m[2] === 'mm' ? 1000 : 1)) . "\u{00A0}m";
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

/** A saved picture as a URL: theme-relative paths ("images/products/x.png") resolve to the theme's assets; full and root-relative URLs stay. */
function boblift_home_image_url(string $src): string
{
    return $src === '' || str_starts_with($src, '/') ? $src : boblift_image_src($src);
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
            return boblift_home_image_url($src);
        }
    }
    return $main;
}

/**
 * One-line card label as HTML: rated capacity and boom type ("20 t · Telescopic Boom"), else the product's category.
 * The tonnage keeps its lower-case unit and left-to-right order inside the upper-case, possibly right-to-left label.
 */
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
                $weight['value'] = boblift_home_product_tonnage_label(boblift_home_spec_number($m[1]) / 1000);
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

/**
 * The best seller shown for each family, picked 2026-11-12 from Search Console (10-22 to 11-10, every language) and the
 * site's inquiries (10-19 to 11-12). 'model' names the configuration of a series page.
 */
function boblift_home_best_seller_defaults(): array
{
    return [
        'truck-mounted-crane' => ['product' => '10-ton-telescopic-boom-truck-mounted-crane', 'model' => 'SQ10SA4'],
        'marine-crane' => ['product' => '1-ton-knuckle-boom-marine-crane'],
        'forklift-fly-jib-cranes' => ['product' => '4-ton-forklift-fly-jib-crane'],
        'electric-mini-cranes' => ['product' => '1-ton-electric-mini-crane'],
        'other-cranes' => ['product' => 'truck-tail-lift'],
    ];
}

/**
 * Each family's tile photo: a real picture from a customer's site or the factory, kept in the theme so it never
 * depends on the media library. 'position' is the CSS object-position that keeps the crane in the frame.
 */
function boblift_home_family_photos(): array
{
    return [
        'truck-mounted-crane' => [
            'image' => 'images/home-families/truck-mounted-crane-10-ton-telescopic-boom-philippines.webp',
            'position' => '38% 50%',
            'alt' => "A BOB-LIFT 10 ton telescopic boom truck mounted crane installed behind the cab of a customer's truck in the Philippines",
        ],
        'marine-crane' => [
            'image' => 'images/home-families/marine-crane-sq5za3t-knuckle-boom-workboat-egypt.webp',
            'position' => '50% 62%',
            'alt' => "A BOB-LIFT SQ5ZA3T knuckle boom marine crane folded on the aft deck of a customer's harbour workboat in Egypt",
        ],
        'forklift-fly-jib-cranes' => [
            'image' => 'images/home-families/forklift-fly-jib-crane-cc85-boom-raised-factory-yard.webp',
            'position' => '62% 50%',
            'alt' => 'A BOB-LIFT CC85 fly jib crane with its boom raised, mounted on a forklift in the factory yard',
        ],
        'electric-mini-cranes' => [
            'image' => 'images/home-families/electric-mini-crane-sq1z-er-wireless-remote-workshop.webp',
            'position' => '35% 50%',
            'alt' => 'Worker operating a BOB-LIFT SQ1Z-ER electric mini crane with a wireless remote in the workshop',
        ],
        'other-cranes' => [
            'image' => 'images/home-families/truck-mounted-aerial-work-platform-basket-raised.webp',
            'position' => '50% 40%',
            'alt' => 'BOB-LIFT truck mounted aerial work platform lifting its basket high inside a factory hall',
        ],
    ];
}

/** "1-2 tons" as "1–2 t"; other values unchanged. */
function boblift_home_tonnage_text(string $value): string
{
    $value = trim($value);
    if (preg_match('/^([\d.]+)\s*[-–]\s*([\d.]+)\s*(?:t|tons?|tonnes?)$/iu', $value, $match)) {
        return $match[1] . '–' . $match[2] . ' t';
    }
    return $value;
}

/**
 * A best-seller card: the product in the current language, its name and model, and two figures, the maximum capacity
 * and the maximum reach. A series page shows the named configuration's own figures and opens on it. A figure the
 * product does not have is left out. Null when the product is not published.
 */
function boblift_home_best_seller_card(array $pick): ?array
{
    $product = boblift_find_product((string) ($pick['product'] ?? ''));
    if (!$product) {
        return null;
    }
    $view = boblift_series_card_view($product);
    $url = boblift_product_url($product);
    $code = (string) $view['code'];
    $capacity = (string) ($view['specs']['capacity']['value'] ?? '');
    $reach = (string) ($view['specs']['radius']['value'] ?? '');
    $model = trim((string) ($pick['model'] ?? ''));
    $series = $model !== '' ? boblift_series_for_product((int) ($product['id'] ?? 0)) : null;
    foreach ($series ? boblift_series_members_with_figures($series) : [] as $member) {
        if (strcasecmp((string) $member['model'], $model) === 0) {
            $figures = (array) $member['figures'];
            $capacity = ($figures['capacity'] ?? null) !== null ? boblift_series_format_kg((int) $figures['capacity']) : $capacity;
            $reach = (string) (($figures['radius'] ?? '') ?: $reach);
            $url = (string) (($member['url'] ?? '') ?: $url);
            $code = (string) $member['model'];
            break;
        }
    }
    // Figures the series data leaves out are read from the spec table as written: a reach in millimetres or under
    // another row name (a forklift jib's "Max Working Range"), a capacity outside the crane figures (a tail lift's "1-2 tons").
    foreach (boblift_home_product_english_specs($product) as $row) {
        $label = trim((string) ($row['label'] ?? ''));
        if ($capacity === '' && $label === 'Max Lifting Capacity') {
            $capacity = boblift_home_tonnage_text((string) ($row['value'] ?? ''));
        }
        if ($reach === '' && preg_match('/^max\.?\s+(working\s+)?(radius|range|outreach|reach)\b/i', $label)) {
            $reach = boblift_home_product_length_label((string) ($row['value'] ?? ''));
        }
    }
    $specs = [];
    if ($capacity !== '') {
        $specs[] = ['label' => boblift_series_text('Max lifting capacity'), 'value' => $capacity];
    }
    if ($reach !== '') {
        $specs[] = ['label' => boblift_series_text('Max reach'), 'value' => $reach];
    }
    $image = boblift_home_product_card_image($product);
    return [
        'title' => (string) $view['full_title'],
        'code' => $code,
        'url' => $url,
        'image' => $image,
        'image_fit' => boblift_image_fit($image) === 'photo' && boblift_home_product_image_ratio($image) >= 1.15 ? 'cover' : 'contain',
        'specs' => $specs,
        'inquiry_url' => (string) ($product['inquiry_url'] ?? boblift_url('/contact.html#inquiry')),
        'inquiry_product' => (string) ($product['title'] ?? ''),
        'inquiry_slug' => (string) ($product['slug'] ?? ''),
    ];
}

/** The families as the home page shows them: one photo tile each, and each family's best seller. */
function boblift_home_product_families(array $section): array
{
    $photos = boblift_home_family_photos();
    $families = [];
    foreach (boblift_home_product_catalog($section) as $category) {
        $slug = (string) ($category['slug'] ?? '');
        $photo = $photos[$slug] ?? null;
        $pick = $section['bestSellers'][$slug] ?? null;
        $total = (int) ($category['total'] ?? 0);
        $range = (string) ($category['range'] ?? '');
        $families[] = [
            'slug' => $slug,
            'name' => (string) $category['name'],
            'href' => (string) $category['href'],
            'meta' => str_replace('{count}', (string) $total, boblift_localized_static_text('Models: {count}')),
            'range' => $range,
            // A family added later without a photo here shows its own category picture until it gets one.
            'image' => $photo ? boblift_asset($photo['image']) : boblift_home_image_url((string) $category['image']),
            'position' => (string) ($photo['position'] ?? '50% 50%'),
            'alt' => $photo ? boblift_localized_static_text($photo['alt']) : '',
            'children' => (array) ($category['children'] ?? []),
            'best' => is_array($pick) ? boblift_home_best_seller_card($pick) : null,
        ];
    }
    return $families;
}

function boblift_render_home_products(): void
{
    $section = boblift_home_product_section_config();
    $families = boblift_home_product_families($section);
    if ($families) {
        boblift_render_home_products_view($section, $families, boblift_product_archive_url());
    }
}

/**
 * Two layers: a photo tile per product family that opens its category page, then the best-selling model of each family.
 * Five families sit in a mosaic (the first tall on the left, the second wide on the right, three below); any other
 * number fills an even grid.
 */
function boblift_render_home_products_view(array $section, array $families, string $all_products_url): void
{
    $mosaic = count($families) === 5;
    $cards = array_values(array_filter($families, static fn(array $family): bool => !empty($family['best'])));
    $arrow = boblift_icon('arrow-right', 'h-4 w-4 rtl:-scale-x-100');
    ?>
    <section id="products" class="home-families-section">
        <div class="page-shell">
            <div class="home-families-head">
                <div>
                    <p class="section-label mb-3"><?php echo esc_html($section['label']); ?></p>
                    <h2 class="text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em] text-ink [text-wrap:balance] [word-break:auto-phrase]"><?php echo esc_html($section['title']); ?></h2>
                </div>
                <a class="home-families-all" href="<?php echo esc_url($all_products_url); ?>"><?php echo esc_html($section['allProductsLabel']); ?> <?php echo $arrow; ?></a>
            </div>

            <div class="home-families-grid<?php echo $mosaic ? ' is-mosaic' : ''; ?>">
                <?php foreach ($families as $index => $family) : ?>
                    <?php $chips = $index < 2 && count($family['children']) >= 2 ? $family['children'] : []; ?>
                    <article class="home-family-tile">
                        <img <?php echo boblift_img_src($family['image'], $index === 0 ? '(min-width: 768px) 40vw, 100vw' : '(min-width: 768px) 60vw, 50vw', 1080); ?> alt="<?php echo esc_attr($family['alt']); ?>" style="object-position: <?php echo esc_attr($family['position']); ?>" loading="lazy" decoding="async">
                        <div class="home-family-body">
                            <p class="home-family-meta"><?php echo esc_html($family['meta']); ?><?php if ($family['range'] !== '') : ?> · <bdi dir="ltr"><?php echo esc_html($family['range']); ?></bdi><?php endif; ?></p>
                            <h3 class="home-family-title"><a class="home-family-link" href="<?php echo esc_url($family['href']); ?>"><?php echo esc_html($family['name']); ?></a></h3>
                            <?php if ($chips) : ?>
                                <ul class="home-family-types" aria-label="<?php echo esc_attr(boblift_localized_static_text('Product types') . ': ' . $family['name']); ?>">
                                    <?php foreach ($chips as $child) : ?><li><a href="<?php echo esc_url($child['href']); ?>"><?php echo esc_html($child['title']); ?></a></li><?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                        <span class="home-family-go" aria-hidden="true"><?php echo boblift_icon('arrow-right', 'h-[18px] w-[18px] rtl:-scale-x-100'); ?></span>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if ($cards) : ?>
                <div class="home-hot-head">
                    <p class="section-label"><?php echo esc_html($section['bestSellersLabel']); ?></p>
                    <p class="home-hot-title"><?php echo esc_html($section['bestSellersTitle']); ?></p>
                </div>
                <div class="home-hot-cards boblift-swipe-fade">
                    <?php foreach ($cards as $family) : $card = $family['best']; ?>
                        <article class="home-hot-card">
                            <a class="home-hot-media is-<?php echo esc_attr($card['image_fit']); ?>" href="<?php echo esc_url($card['url']); ?>" tabindex="-1" aria-hidden="true"><img <?php echo boblift_img_src($card['image'], '(min-width: 1280px) 18vw, (min-width: 768px) 30vw, 74vw', 480); ?> alt="" loading="lazy" decoding="async"></a>
                            <div class="home-hot-body">
                                <p class="home-hot-family"><span class="home-hot-badge"><?php echo esc_html($section['bestSellerBadge']); ?></span><span class="truncate"><?php echo esc_html($family['name']); ?></span></p>
                                <h3 class="home-hot-name"><a href="<?php echo esc_url($card['url']); ?>" title="<?php echo esc_attr($card['title']); ?>"><?php echo esc_html($card['title']); ?></a></h3>
                                <p class="home-hot-model"><?php if ($card['code'] !== '') : ?><bdi><?php echo esc_html($card['code']); ?></bdi><?php else : ?>&nbsp;<?php endif; ?></p>
                                <?php if ($card['specs']) : ?>
                                    <dl class="home-hot-specs">
                                        <?php foreach ($card['specs'] as $spec) : ?><div><dt><?php echo esc_html($spec['label']); ?></dt><dd><bdi dir="ltr"><?php echo esc_html($spec['value']); ?></bdi></dd></div><?php endforeach; ?>
                                    </dl>
                                <?php endif; ?>
                                <div class="home-hot-actions">
                                    <a class="home-hot-quote" href="<?php echo esc_url($card['inquiry_url']); ?>" data-inquiry-modal-open data-inquiry-source="首页产品卡片" data-inquiry-product="<?php echo esc_attr($card['inquiry_product']); ?>" data-inquiry-product-slug="<?php echo esc_attr($card['inquiry_slug']); ?>"><?php echo esc_html($section['requestQuoteLabel']); ?></a>
                                    <a class="home-hot-more" href="<?php echo esc_url($card['url']); ?>"><?php echo esc_html($section['viewDetailsLabel']); ?> <?php echo $arrow; ?></a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php
}
