<?php
/**
 * Generated copy for crane product pages: a range guide (where the model sits, how it differs from the models
 * around it, what it needs from the truck or vessel), spec groups, order terms and model FAQs.
 * Every sentence is built from the product data (model code, English spec table, capacity) so nothing is invented;
 * a sentence whose figures are missing is left out. Strings are English originals translated through
 * boblift_localized_static_text() (inc/translations/product-guide.php).
 */

/**
 * "SQ8ZA6T" -> boom (Z knuckle / S telescopic), boom section count and suffixes; null for other patterns.
 * "+1" models carry one more section than their count: "sections_label" reads "3+1".
 */
function boblift_crane_model_code(string $model): ?array
{
    if (!preg_match('/^SQ(?<capacity>\d+(?:\.\d+)?)(?<boom>Z|S)A(?<count>\d)(?<marine>T?)(?<winch>J?)(?<flip>F?)$/', str_replace('+1', '', $model), $m)) {
        return null;
    }
    return ['boom' => $m['boom'], 'sections' => (int) $m['count'], 'sections_label' => $m['count'] . (str_contains($model, '+1') ? '+1' : ''), 'marine' => $m['marine'] !== '', 'winch' => $m['winch'] !== '', 'flip' => $m['flip'] !== ''];
}

/** One English spec value by its English label ("16 t·m"), '' when the product has no such row. */
function boblift_product_english_spec(int $product_id, string $label): string
{
    static $cache = [];
    $english_id = boblift_product_english_id($product_id);
    if (!isset($cache[$english_id])) {
        $cache[$english_id] = [];
        foreach (boblift_decode_meta(get_post_meta($english_id, '_specs', true), []) as $row) {
            if (is_array($row) && trim((string) ($row['label'] ?? '')) !== '') {
                $cache[$english_id][trim((string) $row['label'])] = trim((string) ($row['value'] ?? ''));
            }
        }
    }
    return $cache[$english_id][$label] ?? '';
}

/** Crane family from the English product's categories: truck, marine or '' (spider, timber, attachments, access equipment). */
function boblift_product_crane_family(array $english_slugs): string
{
    if (array_intersect($english_slugs, ['marine-crane', 'knuckle-boom-marine-cranes', 'telescopic-boom-marine-cranes'])) {
        return 'marine';
    }
    if (array_intersect($english_slugs, ['truck-mounted-crane', 'knuckle-boom-truck-mounted-cranes', 'telescopic-boom-truck-mounted-cranes', 'pickup-cranes'])) {
        return 'truck';
    }
    return '';
}

/**
 * Slugs of the product's English categories together with all their ancestors, so a child category
 * still counts for its family (a model filed only under a sub-type of tail lifts is still a tail lift).
 */
function boblift_product_category_lineage(int $product_id): array
{
    $terms = wp_get_post_terms(boblift_product_english_id($product_id), 'product_category');
    if (!is_array($terms)) {
        return [];
    }
    $slugs = [];
    foreach ($terms as $term) {
        $slugs[] = $term->slug;
        foreach (get_ancestors($term->term_id, 'product_category', 'taxonomy') as $ancestor_id) {
            $ancestor = get_term($ancestor_id, 'product_category');
            if ($ancestor instanceof WP_Term) {
                $slugs[] = $ancestor->slug;
            }
        }
    }
    return array_values(array_unique($slugs));
}

/**
 * Whether the crane order terms (12-month warranty, from 1 unit, container loading) apply: the product
 * must sit in a crane family, and nowhere in the access equipment or tail lifts those terms exclude.
 * Forklift jib cranes carry the same whole-machine 12-month warranty (the forklift jib catalogue, approved
 * by the user on 2026-11-02, and the user on 2026-11-10).
 */
function boblift_product_crane_terms_apply(int $product_id): bool
{
    $lineage = boblift_product_category_lineage($product_id);
    // The families and their concrete crane categories, since a category may stand on its own without its parent.
    $cranes = [
        'truck-mounted-crane', 'knuckle-boom-truck-mounted-cranes', 'telescopic-boom-truck-mounted-cranes', 'pickup-cranes',
        'marine-crane', 'knuckle-boom-marine-cranes', 'telescopic-boom-marine-cranes',
        'other-cranes', 'spider-cranes', 'timber-cranes', 'electric-mini-cranes', 'forklift-fly-jib-cranes',
    ];
    return (bool) array_intersect($lineage, $cranes)
        && !array_intersect($lineage, ['aerial-work-platform', 'tail-lift']);
}

/**
 * Every published model in the term (this page's language), with the figures the guide compares.
 * Each: id, model, title, url, capacity (t), moment, weight, sections (0 when the model code is not decoded), legacy.
 * $lifecycle: "current" (the default) leaves out the previous models, "legacy" keeps only them, "all" keeps both.
 */
function boblift_product_range_models(WP_Term $term, string $lifecycle = 'current', bool $include_children = true): array
{
    $args = [
        'post_type' => 'product',
        'post_status' => 'publish',
        'numberposts' => -1,
        'tax_query' => [['taxonomy' => 'product_category', 'field' => 'term_id', 'terms' => $term->term_id, 'include_children' => $include_children]],
    ];
    if ($lifecycle === 'legacy') {
        $args['post__in'] = boblift_legacy_product_ids() ?: [0];
    } elseif ($lifecycle !== 'all') {
        $args = boblift_without_legacy($args);
    }
    // Full post objects, so titles, links and meta come from one query each rather than one per model.
    return boblift_product_model_rows(get_posts($args));
}

/** Range model rows (see boblift_product_range_models) for the given product posts, in capacity order. */
function boblift_product_model_rows(array $posts): array
{
    // The spec figures are read from the English products; load those posts and their meta together too.
    _prime_post_caches(array_values(array_unique(array_map(static fn(WP_Post $post): int => boblift_product_english_id($post->ID), $posts))), false, true);
    $models = [];
    foreach ($posts as $post) {
        $id = $post->ID;
        $model = boblift_clean_product_model((string) get_post_meta($id, '_model', true), (string) get_the_title($post));
        $values = boblift_product_filter_values($id);
        $code = boblift_crane_model_code($model);
        $models[] = [
            'id' => $id,
            'model' => $model,
            'title' => get_the_title($post),
            'url' => (string) get_permalink($post),
            'capacity' => (float) $values['capacity'],
            'moment' => boblift_product_english_spec($id, 'Max Lifting Moment'),
            'weight' => boblift_product_english_spec($id, 'Self Weight'),
            'sections' => $code ? $code['sections'] : 0,
            'sections_label' => $code ? $code['sections_label'] : '',
            'legacy' => boblift_product_is_legacy($id),
        ];
    }
    usort($models, static fn(array $a, array $b): int => ($a['capacity'] <=> $b['capacity']) ?: ($a['sections'] <=> $b['sections']) ?: strnatcasecmp($a['model'], $b['model']));
    return $models;
}

/** "8" for 8.0, "6.3" for 6.3. */
function boblift_guide_number(float $number): string
{
    return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
}

/** Linked model names joined with the language's list separator. */
function boblift_guide_model_links(array $models): string
{
    $links = array_map(static fn(array $item): string => '<a href="' . esc_url($item['url']) . '">' . esc_html($item['model']) . '</a>', $models);
    return implode(esc_html(boblift_localized_static_text(', ')), $links);
}

/** A translated template with {tokens}; values are HTML already (escape plain values before passing them). */
function boblift_guide_sentence(string $template, array $tokens): string
{
    return strtr(esc_html(boblift_localized_static_text($template)), $tokens);
}

/**
 * The range guide for one crane: paragraphs (HTML), highlight cards, the same-class comparison rows and the
 * next class up and down. Empty arrays when the model code is not decoded or the product is not a truck or marine crane.
 */
function boblift_product_guide(array $product, string $family, ?WP_Term $term): array
{
    $guide = ['intro' => '', 'cards' => [], 'compare' => [], 'compare_title' => '', 'steps' => '', 'supply' => ''];
    $model = trim((string) ($product['model'] ?? ''));
    $code = boblift_crane_model_code($model);
    $product_id = (int) ($product['id'] ?? 0);
    if (!$code || $family === '' || $product_id <= 0) {
        return $guide;
    }
    $capacity = (float) boblift_product_filter_values($product_id)['capacity'];
    $moment = boblift_product_english_spec($product_id, 'Max Lifting Moment');
    $weight = boblift_product_english_spec($product_id, 'Self Weight');
    $power = boblift_product_english_spec($product_id, 'Recommended Power');
    $flow = boblift_product_english_spec($product_id, 'Hydraulic System Flow');
    $pressure = boblift_product_english_spec($product_id, 'Hydraulic System Pressure');
    $model_html = '<strong>' . esc_html($model) . '</strong>';

    $range = $term instanceof WP_Term ? boblift_product_range_models($term) : [];
    $capacities = array_values(array_unique(array_filter(array_map(static fn(array $item): float => $item['capacity'], $range))));
    sort($capacities);
    // A previous model is not one of the range it is counted against.
    if (count($range) > 1 && count($capacities) > 1 && !boblift_product_is_legacy($product_id)) {
        $guide['intro'] = boblift_guide_sentence('The {model} is one of {count} models in our {range} range, which runs from {min} t to {max} t.', [
            '{model}' => $model_html,
            '{count}' => esc_html((string) count($range)),
            '{range}' => esc_html($term->name),
            '{min}' => esc_html(boblift_guide_number($capacities[0])),
            '{max}' => esc_html(boblift_guide_number(end($capacities))),
        ]);
    }

    // Highlight cards: capacity class, boom sections, hydraulic supply.
    if ($capacity > 0) {
        $guide['cards'][] = [
            'value' => boblift_guide_number($capacity) . ' t',
            'label' => boblift_localized_static_text('Capacity class'),
            'text' => $moment !== '' ? str_replace('{moment}', $moment, boblift_localized_static_text('Max lifting moment {moment}')) : '',
        ];
    }
    $hydraulic = $code['sections'] - 1;
    // The "+1" section's type is not in the model code, so a "3+1" model shows its count without a breakdown.
    $extra_section = $code['sections_label'] !== (string) $code['sections'];
    $guide['cards'][] = [
        'value' => $code['sections_label'],
        'label' => boblift_localized_static_text($code['boom'] === 'Z' ? 'Knuckle boom sections' : 'Telescopic boom sections'),
        'text' => $extra_section ? '' : str_replace('{count}', (string) $hydraulic, boblift_localized_static_text($hydraulic === 1 ? '1 main boom + 1 hydraulic boom' : '1 main boom + {count} hydraulic booms')),
    ];
    if ($flow !== '') {
        $guide['cards'][] = [
            'value' => $flow,
            'label' => boblift_localized_static_text('Hydraulic flow'),
            'text' => trim(implode(' · ', array_filter([$pressure, $power !== '' ? str_replace('{power}', $power, boblift_localized_static_text('{power} recommended')) : '']))),
        ];
    }

    // Same capacity class: this model and the ones that differ from it in boom sections.
    $same_class = array_values(array_filter($range, static fn(array $item): bool => $capacity > 0 && abs($item['capacity'] - $capacity) < 0.001));
    if (count($same_class) > 1) {
        $guide['compare_title'] = str_replace('{capacity}', boblift_guide_number($capacity), boblift_localized_static_text('The {capacity} t models compared'));
        $guide['compare'] = array_map(static fn(array $item): array => $item + ['current' => (int) $item['id'] === $product_id], $same_class);
    }

    // The next class up and down: the model with the same boom section count, else one with a decoded SQ name, else any.
    $pick = static function (float $class) use ($range, $code): ?array {
        $candidates = array_values(array_filter($range, static fn(array $item): bool => abs($item['capacity'] - $class) < 0.001));
        usort($candidates, static fn(array $a, array $b): int => (int) ($b['sections'] === $code['sections']) <=> (int) ($a['sections'] === $code['sections']) ?: (int) ($b['sections'] > 0) <=> (int) ($a['sections'] > 0));
        return $candidates[0] ?? null;
    };
    $index = array_search($capacity, $capacities, false);
    $up = $index !== false && isset($capacities[$index + 1]) ? $pick($capacities[$index + 1]) : null;
    $down = $index !== false && $index > 0 ? $pick($capacities[$index - 1]) : null;
    $steps = [];
    if ($up) {
        $steps[] = boblift_guide_sentence('Need more than {capacity} t? The next class up is the {next} at {next_capacity} t.', [
            '{capacity}' => esc_html(boblift_guide_number($capacity)),
            '{next}' => boblift_guide_model_links([$up]),
            '{next_capacity}' => esc_html(boblift_guide_number($up['capacity'])),
        ]);
    }
    if ($down) {
        $steps[] = boblift_guide_sentence('For lighter loads, the {previous} at {previous_capacity} t is the class below.', [
            '{previous}' => boblift_guide_model_links([$down]),
            '{previous_capacity}' => esc_html(boblift_guide_number($down['capacity'])),
        ]);
    }
    $guide['steps'] = implode(' ', $steps);

    // What the crane needs from the truck or vessel.
    if ($flow !== '' && $pressure !== '') {
        $supply = boblift_guide_sentence($family === 'marine'
            ? 'Before ordering, check that the vessel\'s hydraulic supply can deliver {flow} at {pressure}.'
            : 'Before ordering, check that the truck\'s power take-off can deliver {flow} at {pressure}.', [
            '{flow}' => esc_html($flow),
            '{pressure}' => esc_html($pressure),
        ]);
        if ($power !== '') {
            $supply .= ' ' . boblift_guide_sentence('We recommend {power} of hydraulic power.', ['{power}' => esc_html($power)]);
        }
        if ($family === 'truck' && $weight !== '') {
            $supply .= ' ' . boblift_guide_sentence('Its self weight of {weight} adds to the load on the truck, so allow for it in the payload.', ['{weight}' => esc_html($weight)]);
        }
        $guide['supply'] = $supply;
    }
    return $guide;
}

/**
 * The spec rows in groups (lifting, hydraulics and power, machine), by the English labels of the same rows.
 * $rows are the displayed rows, with the Model row first when $has_model_row. Returns [] when the English table does not line up
 * with this language's rows, so the page falls back to one table.
 */
/**
 * Figures read off the product's drawing (tools/drawing-data): reach, boom angle, outrigger span and the load chart. Stored
 * once on the English product as _boblift_drawing_data, since figures do not change with the language.
 */
function boblift_product_drawing_data(int $product_id): array
{
    $raw = get_post_meta(function_exists('boblift_product_english_id') ? boblift_product_english_id($product_id) : $product_id, '_boblift_drawing_data', true);
    $data = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : null);
    if (!is_array($data)) {
        return [];
    }
    $number = static fn($value): string => is_numeric($value) && (float) $value > 0 ? rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.') : '';
    $rows = [];
    if (($radius = $number($data['max_radius_m'] ?? null)) !== '') {
        $rows[] = ['key' => 'radius', 'label' => boblift_localized_static_text('Max. radius'), 'value' => $radius . ' m'];
    }
    if (($height = $number($data['max_height_m'] ?? null)) !== '') {
        $rows[] = ['key' => 'height', 'label' => boblift_localized_static_text('Max. height'), 'value' => (!empty($data['max_height_approx']) ? '≈ ' : '') . $height . ' m'];
    }
    if (($angle = $number($data['max_boom_angle_deg'] ?? null)) !== '') {
        $rows[] = ['key' => 'angle', 'label' => boblift_localized_static_text('Max. boom angle'), 'value' => $angle . '°'];
    }
    $span = trim((string) ($data['outrigger_span_mm'] ?? ''));
    // A range ("2250-8600") for truck cranes, a footprint ("3686 x 3423") for spider cranes.
    if ($span !== '' && preg_match('/^\d+(?:\s*([-~–xX×])\s*\d+)?$/u', $span, $span_match)) {
        $separator = in_array($span_match[1] ?? '', ['x', 'X', '×'], true) ? ' × ' : '–';
        $rows[] = ['key' => 'span', 'label' => boblift_localized_static_text('Outrigger span'), 'value' => (string) preg_replace('/\s*[-~–xX×]\s*/u', $separator, $span) . ' mm'];
    }
    $chart = [];
    foreach ((array) ($data['load_chart'] ?? []) as $point) {
        if (is_array($point) && count($point) === 2 && (float) $point[0] > 0 && (float) $point[1] > 0) {
            $chart[] = ['radius' => (float) $point[0], 'load' => (float) $point[1]];
        }
    }
    // The chart reads outward from the crane; a sheet that prints its pairs out of order is sorted by radius.
    usort($chart, static fn(array $a, array $b): int => $a['radius'] <=> $b['radius']);
    return ['rows' => $rows, 'chart' => count($chart) >= 2 ? $chart : []];
}

/** The spec rows (English labels) that give a drawing figure: reach and lifting height, in order of preference. */
function boblift_spec_figure_labels(): array
{
    return [
        'radius' => ['Max Working Radius', 'Max Working Range', 'Max Working Distance'],
        'height' => ['Max Lifting Height', 'Max Lifting Height (from ground)', 'Working Height'],
    ];
}

/**
 * The drawing figures the spec table does not already give: a reach or height row in the table (Max Working Radius,
 * Max Lifting Height, ...) is the same figure, so the drawing's row is left out rather than shown twice.
 */
function boblift_product_drawing_rows_unique(int $product_id, array $rows): array
{
    // The English rows stand for this page's rows only while this language's table follows the English one row for row;
    // a translated table that does not keeps every drawing figure, since it may lack the row the English table has.
    $english = boblift_decode_meta(get_post_meta(boblift_product_english_id($product_id), '_specs', true), []);
    $local = boblift_decode_meta(get_post_meta($product_id, '_specs', true), []);
    if (!is_array($english) || !is_array($local) || !boblift_spec_rows_aligned($local, $english)) {
        return $rows;
    }
    $covered = boblift_spec_figure_labels();
    return array_values(array_filter($rows, static function (array $row) use ($covered, $product_id): bool {
        foreach ($covered[$row['key'] ?? ''] ?? [] as $label) {
            if (boblift_product_english_spec($product_id, $label) !== '') {
                return false;
            }
        }
        return true;
    }));
}

/** Display width of a text: East Asian wide characters count as two, as mb_strwidth() counts them (mbstring is optional). */
function boblift_text_width(string $text): int
{
    if (function_exists('mb_strwidth')) {
        return mb_strwidth($text);
    }
    $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $wide = preg_match_all('/[\x{1100}-\x{115F}\x{2E80}-\x{A4CF}\x{AC00}-\x{D7A3}\x{F900}-\x{FAFF}\x{FE30}-\x{FE4F}\x{FF00}-\x{FF60}\x{FFE0}-\x{FFE6}]/u', $text);
    return count($chars) + (int) $wide;
}

/**
 * Whether a translated spec table still follows the English one row for row: same length, and the first figure of
 * each row equal to the English row's (figures survive translation). Only then can a translated row stand for the
 * English row at its position.
 */
function boblift_spec_rows_aligned(array $local, array $english): bool
{
    if ($local === [] || count($local) !== count($english)) {
        return false;
    }
    $first_number = static function ($row): ?float {
        if (!is_array($row) || !preg_match('/\d[\d.,\x{00A0}\x{202F} ]*\d|\d/u', (string) ($row['value'] ?? ''), $m)) {
            return null;
        }
        // "6,300", "6 300", "6.300" (thousands) and "16,0" / "16.0" (decimals) all read as the same figure: the last
        // separator is a decimal point only when one or two digits follow it.
        $digits = (string) preg_replace('/[\x{00A0}\x{202F} ]/u', '', $m[0]);
        if (preg_match('/^(.*)[.,](\d{1,2})$/', $digits, $parts)) {
            return (float) (str_replace([',', '.'], '', $parts[1]) . '.' . $parts[2]);
        }
        return (float) str_replace([',', '.'], '', $digits);
    };
    foreach (array_values($english) as $index => $row) {
        if ($first_number($row) !== $first_number(array_values($local)[$index] ?? null)) {
            return false;
        }
    }
    return true;
}

function boblift_product_spec_groups(int $product_id, array $rows, bool $has_model_row): array
{
    $english = array_values(array_filter(boblift_decode_meta(get_post_meta(boblift_product_english_id($product_id), '_specs', true), []), static fn($row): bool => is_array($row) && trim((string) ($row['label'] ?? '')) !== '' && trim((string) ($row['value'] ?? '')) !== ''));
    $spec_rows = $has_model_row ? array_slice($rows, 1) : $rows;
    if (count($english) !== count($spec_rows)) {
        return [];
    }
    // Equal counts are not enough: a translated table may be reordered. Rows line up only when the first number in each
    // row matches the English row at the same place (values are figures, so they survive translation); otherwise one table.
    $first_number = static fn(string $value): string => preg_match('/\d+(?:[.,]\d+)?/', $value, $m) ? str_replace(',', '.', $m[0]) : '';
    foreach ($spec_rows as $index => $row) {
        if ($first_number((string) ($english[$index]['value'] ?? '')) !== $first_number((string) ($row['value'] ?? ''))) {
            return [];
        }
    }
    // Performance (what it lifts, how far, how high) under Lifting; what drives it under Hydraulics and power; the rest is
    // the machine itself (weight, size, mounting, control).
    $lifting = ['Max Lifting Capacity', 'Max Lifting Moment', 'Load at Max Radius', 'Rotation Angle', 'Max Working Radius', 'Max Working Range', 'Min Working Range', 'Max Working Distance', 'Min Working Distance', 'Max Lifting Height', 'Max Lifting Height (from ground)', 'Working Height', 'Lifting Angle', 'Boom', 'Boom Type', 'Boom Sections', 'Winch', 'Matching Forklifts'];
    $hydraulics = ['Recommended Power', 'Hydraulic System Flow', 'Hydraulic System Pressure', 'Rated Working Pressure', 'Rated Flow', 'Oil Tank Capacity', 'Motor Power', 'Voltage', 'Battery', 'Drive', 'Cylinders'];
    $groups = [
        'lifting' => ['label' => boblift_localized_static_text('Lifting'), 'rows' => []],
        'hydraulics' => ['label' => boblift_localized_static_text('Hydraulics and power'), 'rows' => []],
        // The Model row is left out: the heading above the table already names the model.
        'machine' => ['label' => boblift_localized_static_text('Machine'), 'rows' => []],
    ];
    foreach ($spec_rows as $index => $row) {
        $label = trim((string) $english[$index]['label']);
        $key = in_array($label, $lifting, true) ? 'lifting' : (in_array($label, $hydraulics, true) ? 'hydraulics' : 'machine');
        $groups[$key]['rows'][] = $row;
    }
    foreach ($groups as $key => $group) {
        $groups[$key]['key'] = $key;
    }
    $groups = array_values(array_filter($groups, static fn(array $group): bool => $group['rows'] !== []));
    // A single group adds a heading and nothing else.
    return count($groups) > 1 ? $groups : [];
}

/**
 * Order terms on crane pages. Warranty and minimum order are confirmed (the user, 2026-10-19); the lead time,
 * container loading and service steps are placeholders pending the user's figures, editable under 产品详情页
 * (productDetail.buyingLeadTime / buyingContainers); while either is unchanged the product page leaves both off.
 */
function boblift_product_buying_terms(float $capacity, array $ui): array
{
    $placeholder_lead = '15–30';
    $placeholder_containers = [['max' => 3.2, 'gp20' => 4, 'hq40' => 8], ['max' => 8, 'gp20' => 2, 'hq40' => 4], ['max' => 16, 'gp20' => 1, 'hq40' => 2], ['max' => 0, 'gp20' => 0, 'hq40' => 1]];
    $lead = trim((string) ($ui['buyingLeadTime'] ?? $placeholder_lead));
    $containers = is_array($ui['buyingContainers'] ?? null) ? $ui['buyingContainers'] : $placeholder_containers;
    $loading = ['gp20' => 0, 'hq40' => 0];
    foreach ($containers as $tier) {
        $max = (float) ($tier['max'] ?? 0);
        if ($max <= 0 || $capacity <= $max) {
            $loading = ['gp20' => (int) ($tier['gp20'] ?? 0), 'hq40' => (int) ($tier['hq40'] ?? 0)];
            break;
        }
    }
    return [
        'lead' => $lead,
        'loading' => $loading,
        'pending' => $lead === $placeholder_lead || $containers === $placeholder_containers,
    ];
}

/**
 * The topics a product's own questions (boblift_product_copy) already answer, of those the generated questions cover,
 * judged on the list shown ($own, in the page's language) so a translation is never taken to say what it does not:
 * - "hydraulic": an answer gives the spec table's oil flow and pressure ($flow, $pressure, English values), every
 *   figure (both ends of a range like 40–60 L/min) with its unit; figures are the same in every language and the
 *   unit is matched in each language's form, and a question on oil type or upkeep does not give them. The answer has
 *   to open with one of them, so a passing mention under another question does not count.
 * - "warranty": a question names the warranty (in any of the site's languages) and its answer gives the 12 months
 *   in words for months.
 * Figures in a model code (SQ12ZA4, SQ1ZA3+1) are not read as figures.
 */
function boblift_product_faq_answered_topics(array $own, string $flow, string $pressure): array
{
    $numbers = static function (string $value): array {
        preg_match_all('/\d+(?:[.,]\d+)?/', $value, $matches);
        return $matches[0];
    };
    // A quantity is given when its figures are written together and followed by its unit, in any of the forms the
    // site's languages write it (15 L/min, 15 л/мин, 15 لتر/دقيقة; 18 MPa, 18 МПа, 18 ميجا باسكال). An 18 L tank
    // or a 15 kW motor does not give a flow or a pressure. A quantity without figures, or in a unit not listed, gives
    // nothing, so the generated question stays.
    $units = [
        'l/min' => ['L/min', 'l/min', 'ℓ/min', 'L/分', 'л/мин', '升/分', 'لتر/دقيقة', 'لتر في الدقيقة'],
        'mpa' => ['MPa', 'МПа', '兆帕', 'ميجا باسكال', 'ميغا باسكال', 'ميجاباسكال'],
        'bar' => ['bar', 'бар', 'بار', '巴'],
    ];
    $quantity = static function (string $spec) use ($numbers, $units): array {
        foreach ($units as $key => $forms) {
            if (preg_match('/(?<![\p{L}])' . preg_quote($key, '/') . '(?![\p{L}])/iu', $spec)) {
                return ['figures' => $numbers($spec), 'units' => $forms];
            }
        }
        return ['figures' => [], 'units' => []];
    };
    // The whole quantity in one place: its figures in order, joined as a range ("40–60", "40 to 60", "40 至 60"), then
    // its unit. A figure that turns up elsewhere (an 18 L tank, a 40 L oil tank) does not stand for an end of the range.
    $gives_quantity = static function (string $text, array $quantity): bool {
        if ($quantity['figures'] === []) {
            return false;
        }
        $figure = static fn(string $value): string => str_replace('\\.', '[.,]', preg_quote(str_replace(',', '.', $value), '/'));
        $range = implode('\s*(?:[-–—~～〜]|to|至|до|a|à|bis|إلى)\s*', array_map($figure, $quantity['figures']));
        $forms = implode('|', array_map(static fn(string $form): string => preg_quote($form, '/'), $quantity['units']));
        return (bool) preg_match('/(?<![A-Za-z\d.,])' . $range . '\s*(?:' . $forms . ')(?![\p{L}])/iu', $text);
    };
    $flow_quantity = $quantity($flow);
    $pressure_quantity = $quantity($pressure);
    // The answer is about the supply: it opens with the flow or the pressure ("15 L/min at 18 MPa…", "18 MPa 下 15 L/min…").
    // An answer on another question that mentions the supply in passing (the vessel size, with weight first) leaves the
    // generated question in place, so the supply keeps a question of its own.
    $leads_with_supply = static function (string $answer) use ($flow_quantity, $pressure_quantity): bool {
        // The first figure outside the model codes: a word that starts with a Latin letter and holds digits, with any
        // "+1" (SQ1ZA4, SQ1ZA3+1, SQ3.2SA4T), is taken out first, so "The SQ1ZA3+1 needs 15 L/min…" opens with 15.
        $text = (string) preg_replace('/(?<![A-Za-z\d])[A-Za-z][A-Za-z\d.]*\d[A-Za-z\d.]*(?:\+\d+)?/', ' ', $answer);
        if (!preg_match('/(?<![\d.,])\d+(?:[.,]\d+)?/', $text, $match)) {
            return false;
        }
        $first = str_replace(',', '.', $match[0]);
        $opening = array_map(static fn(array $quantity): string => str_replace(',', '.', (string) ($quantity['figures'][0] ?? '')), [$flow_quantity, $pressure_quantity]);
        return in_array($first, $opening, true);
    };
    // Twelve months as each language writes it (12 months, 12-month, 12 个月, 12か月, 12 месяцев, 12 meses, 12 mois,
    // 12 شهرًا); a 12 in a model code (SQ12ZA4) or another figure does not say how long the warranty runs.
    $twelve_months = '/(?<![A-Za-z\d.,])12\s*-?\s*(?:months?\b|个月|個月|か月|ヶ月|カ月|ケ月|месяц|meses\b|mois\b|شهر|أشهر)/iu';
    $topics = [];
    foreach ($own as $faq) {
        $question = (string) ($faq['question'] ?? '');
        $answer = (string) ($faq['answer'] ?? '');
        if ($leads_with_supply($answer) && $gives_quantity($answer, $flow_quantity) && $gives_quantity($answer, $pressure_quantity)) {
            $topics['hydraulic'] = true;
        }
        if (preg_match('/warrant|garant|保修|质保|保証|гарант|ضمان/iu', $question) && preg_match($twelve_months, $answer)) {
            $topics['warranty'] = true;
        }
    }
    return array_keys($topics);
}

/**
 * Model questions answered from the data, asked before the shared FAQ. A topic in $answered (see
 * boblift_product_faq_answered_topics()) is left out: the product's own question on it says the same and more.
 */
function boblift_product_guide_faqs(array $product, array $guide, string $family, bool $terms_apply, array $answered = []): array
{
    $model = trim((string) ($product['model'] ?? ''));
    $product_id = (int) ($product['id'] ?? 0);
    if ($model === '' || $family === '' || !boblift_crane_model_code($model)) {
        return [];
    }
    $t = static fn(string $text, array $tokens = []): string => strtr(boblift_localized_static_text($text), $tokens);
    $faqs = [];
    $others = array_values(array_filter($guide['compare'], static fn(array $item): bool => !$item['current']));
    if ($others) {
        $describe = static fn(array $item): string => $t('{model}: {sections} boom sections, {moment}, {weight}.', [
            '{model}' => $item['model'],
            '{sections}' => $item['sections_label'] !== '' ? $item['sections_label'] : '–',
            '{moment}' => $item['moment'] !== '' ? $item['moment'] : '–',
            '{weight}' => $item['weight'] !== '' ? $item['weight'] : '–',
        ]);
        $faqs[] = [
            'question' => count($others) === 1
                ? $t('How does the {model} differ from the {other}?', ['{model}' => $model, '{other}' => $others[0]['model']])
                : $t('How does the {model} differ from the other {capacity} t models?', ['{model}' => $model, '{capacity}' => boblift_guide_number((float) $others[0]['capacity'])]),
            // The closing line only when the boom sections really differ (SQ08ZA3 and SQ08ZA3+1 differ in more than the count).
            'answer' => trim(implode(' ', array_map($describe, $guide['compare'])) . (count(array_unique(array_column($guide['compare'], 'sections_label'))) === count($guide['compare']) ? ' ' . $t('They share a capacity class and differ in the number of boom sections.') : '')),
        ];
    }
    $flow = boblift_product_english_spec($product_id, 'Hydraulic System Flow');
    $pressure = boblift_product_english_spec($product_id, 'Hydraulic System Pressure');
    if ($flow !== '' && $pressure !== '' && !in_array('hydraulic', $answered, true)) {
        $power = boblift_product_english_spec($product_id, 'Recommended Power');
        $faqs[] = [
            'question' => $t('What hydraulic supply does the {model} need?', ['{model}' => $model]),
            'answer' => trim($t('{flow} at {pressure}.', ['{flow}' => $flow, '{pressure}' => $pressure]) . ' ' . ($power !== '' ? $t('We recommend {power} of hydraulic power.', ['{power}' => $power]) : '') . ' ' . $t($family === 'marine' ? 'Send us the vessel\'s hydraulic data and we confirm the match when we quote.' : 'Send us the truck\'s power take-off data and we confirm the match when we quote.')),
        ];
    }
    if ($terms_apply && !in_array('warranty', $answered, true)) {
        $faqs[] = [
            'question' => $t('What warranty and minimum order apply to the {model}?', ['{model}' => $model]),
            'answer' => $t('The {model} carries a 12-month warranty, and you can order from 1 unit.', ['{model}' => $model]),
        ];
    }
    return $faqs;
}

/**
 * Which "Ordering and service" questions a product page shows: the saved list (site_ui.productDetail.faqItems) is
 * written for truck mounted equipment and talks about chassis and truck mounting, so marine, spider, electric mini and
 * forklift jib cranes get their own list instead. '' means the saved list applies.
 */
/** The families with their own "Ordering and service" list (see boblift_product_ordering_faq_family()). */
function boblift_product_ordering_faq_families(): array
{
    return ['marine', 'spider', 'electric-mini', 'fly-jib'];
}

function boblift_product_ordering_faq_family(array $english_slugs): string
{
    if (boblift_product_crane_family($english_slugs) === 'marine') {
        return 'marine';
    }
    foreach (['spider-cranes' => 'spider', 'electric-mini-cranes' => 'electric-mini', 'forklift-fly-jib-cranes' => 'fly-jib'] as $slug => $family) {
        if (in_array($slug, $english_slugs, true)) {
            return $family;
        }
    }
    return '';
}

/**
 * The theme's "Ordering and service" questions for the family, in this page's language or $language
 * (inc/translations/product-ordering-faq.php). A list saved for the family (site_ui.productDetail.faqItemsByFamily,
 * written through the boblift_product_faq REST field) replaces it on the page.
 * Facts only: warranty, reply and parts times and training as confirmed by the factory on 2026-10-27, forklift jib
 * cranes included.
 */
function boblift_product_ordering_faqs(string $family, ?string $language = null): array
{
    $delivery = ['What is the delivery time, and which documents come with the order?', 'Lead time depends on the configuration, order quantity and destination, and we confirm it in writing with your quotation. Each order ships with a commercial invoice, packing list and bill of lading, plus the product documents required for the confirmed order.'];
    $training = ['Is operator training provided?', 'There is no operator training at delivery. Your technicians are welcome at our factory in Xuzhou to learn the crane, the manual comes in English with other languages on request before the order, and our Service & Operation page has short videos on checks before and during lifting.'];
    $service = ['What after-sales support is available?', 'Cranes carry a 12-month warranty. Send the model, serial number and a photo or video of the issue: we reply within 12 hours, guide the repair by video and ship common spare parts within 48 hours.'];
    $custom = ['Can the crane be customized for our working conditions?', 'Tell us the lifting work, the site and the requirements of the destination country, and we confirm what can be adjusted before quotation.'];
    $lists = [
        'marine' => [
            ['Which configuration should we choose for this model?', 'Send the load, the outreach you need over the ship\'s side, the vessel type and where the crane will stand on deck. Our engineers confirm a practical model and mounting before quotation.'],
            ['How is the crane mounted on the vessel?', 'The crane stands on its base plate, fixed to the deck or to a raised pedestal, and the base plate size is on the product drawing. Send us the deck structure at the crane position: we adapt the mounting to your vessel and confirm it with you.'],
            ['Does this crane suit our sea conditions?', 'Tell us where the vessel works, whether you lift in port or at sea, and the conditions you expect during lifting. Our engineers check them against the load chart and confirm whether this model suits the job before quotation.'],
            ['How is the crane protected against corrosion?', 'Tell us whether the vessel works in salt water or fresh water and where the crane sits on deck. We confirm the paint and surface protection for your conditions in writing with the quotation.'],
            $delivery,
            ['Do you help with installation and after-sales service?', 'Your shipyard fits the crane with our installation guidance and remote technical support. Cranes carry a 12-month warranty, we reply to service requests within 12 hours, and common spare parts ship within 48 hours.'],
        ],
        'spider' => [
            ['Which configuration should we choose for this model?', 'Send the heaviest load, the working radius and height, and the narrowest doorway, corridor or lift the crane must pass through. Our engineers confirm a practical configuration before quotation.'],
            ['What does a spider crane need on site?', 'A spider crane stands on its own outrigger legs instead of a truck. Check that the ground or floor under each leg can carry the load, and read the load chart at the radius you need. Send us the site details and we confirm the setup with you.'],
            $custom,
            $delivery,
            $training,
            $service,
        ],
        'electric-mini' => [
            ['Which configuration should we choose for this model?', 'Send the load, the working radius, where the crane will be fixed and the destination country. We confirm whether the 1 t or 2 t size, with lever or wireless remote control, suits the job before quotation.'],
            ['How is the crane mounted and powered?', 'The crane bolts to a vehicle bed or a fixed base, and a base cart with side outriggers is available as an option. A 12 V electric-hydraulic power unit drives it from a battery, so it needs no truck power take-off.'],
            $custom,
            $delivery,
            $training,
            $service,
        ],
        'fly-jib' => [
            ['Which configuration should we choose for this model?', 'Send your forklift model, its rated capacity and load center, the mast height, and the load and reach you need. Our engineers confirm the jib size before quotation.'],
            ['How does the jib crane fit our forklift?', 'The crane frame hangs on the fork carriage where the forks normally sit, and the boom runs from the forklift\'s auxiliary hydraulic circuit. Send a side photo of the forklift, its data plate and whether it has an auxiliary circuit, and our engineers confirm the fit.'],
            $delivery,
            $training,
            $service,
        ],
    ];
    return array_map(static fn(array $item): array => [
        'question' => boblift_localized_static_text($item[0], $language),
        'answer' => boblift_localized_static_text($item[1], $language),
    ], $lists[$family] ?? []);
}

/**
 * The family's quote banner title and text, in this page's language; null for the saved truck wording.
 */
function boblift_product_quote_copy(string $family): ?array
{
    $copy = [
        'marine' => ['Need a crane matched to your vessel and lifting task?', 'Send the load, the outreach over the ship\'s side, the vessel type, the crane position on deck and the destination port. BOB-LIFT will help match a practical configuration.'],
        'spider' => ['Need a spider crane matched to your site and lifting task?', 'Send the load, the working radius and height, the narrowest doorway or passage on site and the destination country. BOB-LIFT will help match a practical configuration.'],
        'electric-mini' => ['Need a mini crane matched to your vehicle or workshop?', 'Send the load, the working radius, where the crane will be fixed and the destination country. BOB-LIFT will help match a practical configuration.'],
        'fly-jib' => ['Need a jib crane matched to your forklift?', 'Send your forklift model and rated capacity, the mast height, the load and reach you need and the destination country. BOB-LIFT will help match a practical configuration.'],
    ];
    if (!isset($copy[$family])) {
        return null;
    }
    return ['title' => boblift_localized_static_text($copy[$family][0]), 'text' => boblift_localized_static_text($copy[$family][1])];
}
