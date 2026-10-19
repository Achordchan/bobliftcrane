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
 * must sit in a crane family, and nowhere in the access equipment, tail lifts or fly jibs those terms exclude.
 */
function boblift_product_crane_terms_apply(int $product_id): bool
{
    $lineage = boblift_product_category_lineage($product_id);
    // The families and their concrete crane categories, since a category may stand on its own without its parent.
    $cranes = [
        'truck-mounted-crane', 'knuckle-boom-truck-mounted-cranes', 'telescopic-boom-truck-mounted-cranes', 'pickup-cranes',
        'marine-crane', 'knuckle-boom-marine-cranes', 'telescopic-boom-marine-cranes',
        'other-cranes', 'spider-cranes', 'timber-cranes',
    ];
    return (bool) array_intersect($lineage, $cranes)
        && !array_intersect($lineage, ['aerial-work-platform', 'tail-lift', 'forklift-fly-jib-cranes']);
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
    $posts = get_posts($args);
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
    $lifting = ['Max Lifting Capacity', 'Max Lifting Moment', 'Rotation Angle', 'Max Working Radius', 'Max Working Range', 'Min Working Range', 'Max Lifting Height', 'Max Lifting Height (from ground)', 'Working Height', 'Lifting Angle', 'Boom Type', 'Boom Sections', 'Winch', 'Matching Forklifts'];
    $hydraulics = ['Recommended Power', 'Hydraulic System Flow', 'Hydraulic System Pressure', 'Rated Working Pressure', 'Rated Flow', 'Oil Tank Capacity'];
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
    $groups = array_values(array_filter($groups, static fn(array $group): bool => $group['rows'] !== []));
    // A single group adds a heading and nothing else.
    return count($groups) > 1 ? $groups : [];
}

/**
 * Order terms on crane pages. Warranty and minimum order are confirmed (the user, 2026-10-19); the lead time,
 * container loading and service steps are placeholders pending the user's figures, editable under 产品详情页
 * (productDetail.buyingLeadTime / buyingContainers) and flagged to signed-in editors until they change.
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

/** Model questions answered from the data, asked before the shared FAQ. */
function boblift_product_guide_faqs(array $product, array $guide, string $family, bool $terms_apply): array
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
    if ($flow !== '' && $pressure !== '') {
        $power = boblift_product_english_spec($product_id, 'Recommended Power');
        $faqs[] = [
            'question' => $t('What hydraulic supply does the {model} need?', ['{model}' => $model]),
            'answer' => trim($t('{flow} at {pressure}.', ['{flow}' => $flow, '{pressure}' => $pressure]) . ' ' . ($power !== '' ? $t('We recommend {power} of hydraulic power.', ['{power}' => $power]) : '') . ' ' . $t($family === 'marine' ? 'Send us the vessel\'s hydraulic data and we confirm the match when we quote.' : 'Send us the truck\'s power take-off data and we confirm the match when we quote.')),
        ];
    }
    if ($terms_apply) {
        $faqs[] = [
            'question' => $t('What warranty and minimum order apply to the {model}?', ['{model}' => $model]),
            'answer' => $t('The {model} carries a 12-month warranty, and you can order from 1 unit.', ['{model}' => $model]),
        ];
    }
    return $faqs;
}
