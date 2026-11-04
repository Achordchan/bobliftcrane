<?php
/**
 * Marine crane series: one page per boom type and rated capacity, with the models of that class as configurations.
 *
 * Only the two marine categories are grouped; every other product keeps its own page. The series page is the product whose
 * slug has no model suffix (12-ton-knuckle-boom-marine-crane). The other products of the group redirect to it with
 * ?model=<model> and leave the lists, the sitemap, the counts and the related models, but stay editable as products.
 * Their posts remain the source of each configuration's specifications, photos and drawings: single-product.php renders once
 * per configuration and the blocks of all of them are printed together, so search engines read every configuration and the
 * theme script only shows the chosen one.
 *
 * Grouping can be changed with the boblift_marine_series_groups filter, the series name with boblift_marine_series_code.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * What a marine model name says (A = boom sections, BLZ uses K; T = marine; J = winch; +1 = manual extension).
 * Names without that pattern (SQZ330T) count as a choice of their own, under "named".
 */
function boblift_marine_parse_model(string $model): array
{
    $model = trim($model);
    $manual = str_ends_with($model, '+1');
    $base = $manual ? substr($model, 0, -2) : $model;
    if (preg_match('/^SQ\d+(?:\.\d+)?[ZS](?<section>A\d)T(?<winch>J?)$/', $base, $m) || preg_match('/^BLZ\d+(?<section>K\d)T(?<winch>J?)$/', $base, $m)) {
        return ['named' => '', 'sections' => $m['section'], 'winch' => $m['winch'] !== '' ? '1' : '0', 'manual' => $manual ? '1' : '0'];
    }
    return ['named' => $model, 'sections' => '', 'winch' => '', 'manual' => ''];
}

/** The series name shown on cards and under the model: SQ12Z-T, BLZ90-T, SQZ-T. This is the one place that names a series. */
function boblift_marine_series_code(string $model): string
{
    if (preg_match('/^SQ(\d+(?:\.\d+)?)([ZS])A\d/', $model, $m)) {
        $code = 'SQ' . $m[1] . $m[2] . '-T';
    } elseif (preg_match('/^(BLZ\d+)K\d/', $model, $m)) {
        $code = $m[1] . '-T';
    } elseif (preg_match('/^SQZ\d/', $model)) {
        $code = 'SQZ-T';
    } else {
        $code = $model;
    }
    return (string) apply_filters('boblift_marine_series_code', $code, $model);
}

/** ?model= link to one configuration; the plus of +1 must be encoded or it reads as a space. */
function boblift_marine_model_url(string $series_url, string $model): string
{
    return $series_url . (str_contains($series_url, '?') ? '&' : '?') . 'model=' . rawurlencode($model);
}

/** The configuration a ?model= value asks for, or $default. A bare plus arrives as a space. */
function boblift_marine_requested_model(array $models, string $requested, string $default): string
{
    $requested = str_replace(' ', '+', trim($requested));
    foreach ($models as $model) {
        if (strcasecmp($model, $requested) === 0) {
            return $model;
        }
    }
    return $default;
}

/**
 * Groups product rows by boom type and capacity. Rows: id, slug, model, capacity, boom ("knuckle" or "telescopic"), legacy.
 * The series page is the row whose slug has no model suffix, else the first current model, else the first model.
 */
function boblift_marine_group_rows(array $rows): array
{
    $groups = [];
    foreach ($rows as $row) {
        $capacity = (float) $row['capacity'];
        if ($capacity <= 0 || trim((string) $row['model']) === '') {
            continue;
        }
        $key = $row['boom'] . '-' . rtrim(rtrim(number_format($capacity, 2, '.', ''), '0'), '.');
        $groups[$key] ??= ['key' => $key, 'boom' => $row['boom'], 'capacity' => $capacity, 'members' => []];
        $groups[$key]['members'][] = $row;
    }
    foreach ($groups as $key => $group) {
        usort($group['members'], static fn(array $a, array $b): int => strnatcmp($a['model'], $b['model']));
        $home = null;
        foreach ($group['members'] as $member) {
            if (preg_match('/^\d[\d-]*-ton-(?:knuckle|telescopic)-boom-marine-crane$/', (string) $member['slug'])) {
                $home = $member;
                break;
            }
        }
        if ($home === null) {
            foreach ($group['members'] as $member) {
                if (empty($member['legacy'])) {
                    $home = $member;
                    break;
                }
            }
        }
        $home ??= $group['members'][0];
        $groups[$key]['members'] = $group['members'];
        $groups[$key]['home'] = (int) $home['id'];
        $groups[$key]['code'] = boblift_marine_series_code((string) $home['model']);
    }
    uasort($groups, static fn(array $a, array $b): int => ($a['boom'] <=> $b['boom']) ?: ($a['capacity'] <=> $b['capacity']));
    return $groups;
}

/** The choices a series offers, in display order: [dimension => values]. Only dimensions with two or more values are asked. */
function boblift_marine_series_dimensions(array $members): array
{
    $dimensions = [];
    foreach (['named', 'sections', 'winch', 'manual'] as $dimension) {
        $values = array_values(array_unique(array_filter(array_map(static fn(array $member): string => (string) ($member['values'][$dimension] ?? ''), $members), static fn(string $value): bool => $value !== '')));
        sort($values, SORT_NATURAL);
        if (count($values) > 1) {
            $dimensions[$dimension] = $values;
        }
    }
    return $dimensions;
}

/** Whether every configuration has this value, so it is stated once rather than asked: only then is the note shown. */
function boblift_marine_series_all_have(array $members, string $dimension, string $value): bool
{
    return $members !== [] && !array_filter($members, static fn(array $member): bool => (string) ($member['values'][$dimension] ?? '') !== $value);
}

/** The English marine products with what grouping needs. */
function boblift_marine_series_rows(): array
{
    if (!function_exists('pll_default_language')) {
        return [];
    }
    global $wpdb;
    $language = (string) pll_default_language();
    $found = $wpdb->get_results($wpdb->prepare(
        "SELECT DISTINCT p.ID AS id, p.post_name AS slug, t.slug AS category
        FROM {$wpdb->posts} p
        JOIN {$wpdb->term_relationships} r ON r.object_id = p.ID
        JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = r.term_taxonomy_id AND tt.taxonomy = 'product_category'
        JOIN {$wpdb->terms} t ON t.term_id = tt.term_id AND t.slug IN ('knuckle-boom-marine-cranes', 'telescopic-boom-marine-cranes')
        JOIN {$wpdb->term_relationships} lr ON lr.object_id = p.ID
        JOIN {$wpdb->term_taxonomy} ltt ON ltt.term_taxonomy_id = lr.term_taxonomy_id AND ltt.taxonomy = 'language'
        JOIN {$wpdb->terms} lt ON lt.term_id = ltt.term_id AND lt.slug = %s
        WHERE p.post_type = 'product' AND p.post_status = 'publish' AND p.post_password = ''",
        $language
    ), ARRAY_A);
    $rows = [];
    foreach ((array) $found as $row) {
        $id = (int) $row['id'];
        $rows[] = [
            'id' => $id,
            'slug' => (string) $row['slug'],
            'model' => trim((string) get_post_meta($id, '_model', true)),
            'capacity' => (float) get_post_meta($id, '_capacity_value', true),
            'boom' => str_starts_with((string) $row['category'], 'knuckle') ? 'knuckle' : 'telescopic',
            'legacy' => boblift_product_is_legacy($id),
        ];
    }
    return $rows;
}

/** Every series with each member's translations, an index from any language's post, and the posts that leave the lists. */
function boblift_marine_series_map(): array
{
    static $map = null;
    if ($map !== null) {
        return $map;
    }
    $map = boblift_cached_data('marine-series', [], static function (): array {
        $rows = boblift_marine_series_rows();
        $groups = (array) apply_filters('boblift_marine_series_groups', boblift_marine_group_rows($rows), $rows);
        $index = [];
        $hidden = [];
        foreach ($groups as $key => $group) {
            foreach ($group['members'] as $position => $member) {
                $translations = array_map('intval', function_exists('pll_get_post_translations') ? pll_get_post_translations((int) $member['id']) : []);
                $translations = array_filter($translations, static fn(int $id): bool => get_post_status($id) === 'publish' && (string) get_post_field('post_password', $id) === '');
                $groups[$key]['members'][$position]['translations'] = $translations;
                foreach ($translations as $id) {
                    $index[$id] = $key;
                    if ((int) $member['id'] !== (int) $group['home']) {
                        $hidden[] = $id;
                    }
                }
            }
        }
        return ['groups' => $groups, 'index' => $index, 'hidden' => array_values(array_unique($hidden))];
    });
    return $map;
}

/**
 * The series a product belongs to, in that product's language, or null for every product outside the two marine
 * categories. Members: id, slug, model, url (?model=), is_home, values (named, sections, winch, manual).
 */
function boblift_marine_series_for_product(int $post_id): ?array
{
    static $memo = [];
    $map = boblift_marine_series_map();
    $key = $map['index'][$post_id] ?? null;
    $group = $key !== null ? ($map['groups'][$key] ?? null) : null;
    if ($group === null) {
        return null;
    }
    $language = '';
    foreach ($group['members'] as $member) {
        $found = array_search($post_id, $member['translations'], true);
        if ($found !== false) {
            $language = (string) $found;
            break;
        }
    }
    if (isset($memo[$key . '|' . $language])) {
        return $memo[$key . '|' . $language];
    }
    $home_id = 0;
    foreach ($group['members'] as $member) {
        if ((int) $member['id'] === (int) $group['home']) {
            $home_id = (int) ($member['translations'][$language] ?? 0);
        }
    }
    if ($home_id === 0) {
        return null;
    }
    $series_url = (string) get_permalink($home_id);
    $members = [];
    foreach ($group['members'] as $member) {
        $id = (int) ($member['translations'][$language] ?? 0);
        if ($id === 0) {
            continue;
        }
        $members[] = [
            'id' => $id,
            'slug' => (string) get_post_field('post_name', $id),
            'model' => (string) $member['model'],
            'url' => boblift_marine_model_url($series_url, (string) $member['model']),
            'is_home' => $id === $home_id,
            'values' => boblift_marine_parse_model((string) $member['model']),
        ];
    }
    $home_model = '';
    foreach ($members as $member) {
        if ($member['is_home']) {
            $home_model = $member['model'];
        }
    }
    return $memo[$key . '|' . $language] = ['key' => $key, 'code' => (string) $group['code'], 'boom' => $group['boom'], 'capacity' => $group['capacity'], 'language' => $language, 'home_id' => $home_id, 'home_model' => $home_model, 'url' => $series_url, 'members' => $members];
}

/** This product's own entry in its series. */
function boblift_marine_series_member(array $series, int $post_id): ?array
{
    foreach ($series['members'] as $member) {
        if ($member['id'] === $post_id) {
            return $member;
        }
    }
    return null;
}

/** "SQ12Z-T Series", in the page's language. */
function boblift_marine_series_label(string $code): string
{
    return str_replace('{code}', $code, boblift_localized_static_text('{code} Series'));
}

/**
 * Old configuration pages go to their series, in the same language. Runs before Polylang's and core's canonical checks.
 */
function boblift_marine_series_redirect(): void
{
    if (!is_singular('product') || is_preview()) {
        return;
    }
    $id = (int) get_queried_object_id();
    $series = boblift_marine_series_for_product($id);
    $member = $series ? boblift_marine_series_member($series, $id) : null;
    if ($member === null || $member['is_home']) {
        return;
    }
    wp_safe_redirect($member['url'], 301, 'BOB-LIFT marine series');
    exit;
}
add_action('template_redirect', 'boblift_marine_series_redirect', 2);

/**
 * Configurations are not products of their own for visitors: product queries leave them out, so the sitemap, lists, counts,
 * related models and the previous-model library all show one entry per series. The editing screens, REST and WP-CLI still see them.
 */
function boblift_marine_series_filter_query(WP_Query $query): void
{
    if (is_admin() || (defined('REST_REQUEST') && REST_REQUEST) || (defined('WP_CLI') && WP_CLI)) {
        return;
    }
    $types = (array) $query->get('post_type');
    if (!in_array('product', $types, true) && !$query->is_tax('product_category')) {
        return;
    }
    // A request for one named post is how a redirect finds its page; a configuration read directly is no list.
    if ($query->get('name') !== '' || (int) $query->get('p') > 0 || (int) $query->get('page_id') > 0) {
        return;
    }
    $hidden = boblift_marine_series_map()['hidden'];
    if ($hidden === []) {
        return;
    }
    $listed = array_map('intval', (array) $query->get('post__in'));
    if ($listed !== []) {
        $kept = array_values(array_diff($listed, $hidden));
        $query->set('post__in', $kept !== [] ? $kept : [0]);
        return;
    }
    $query->set('post__not_in', array_values(array_unique(array_merge(array_map('intval', (array) $query->get('post__not_in')), $hidden))));
}
add_action('pre_get_posts', 'boblift_marine_series_filter_query');

/** A series page answers searches for any of its models, since the configurations have no result of their own. */
function boblift_marine_series_search_models(int $post_id): array
{
    $series = boblift_marine_series_for_product($post_id);
    return $series && $series['home_id'] === $post_id ? array_column($series['members'], 'model') : [];
}

/** What a product card shows for a series page: its code and models. Null for any other product. */
function boblift_marine_series_card(int $post_id): ?array
{
    $series = boblift_marine_series_for_product($post_id);
    return $series && $series['home_id'] === $post_id ? ['code' => $series['code'], 'models' => array_column($series['members'], 'model')] : null;
}

/** The models and count under a card's title. */
function boblift_marine_render_series_card_models(array $card): void
{
    $count = count($card['models']);
    ?>
    <div class="mt-3">
        <?php if ($count > 1) : ?><p class="text-xs font-semibold text-slate-500"><?php echo esc_html(str_replace('{count}', (string) $count, boblift_localized_static_text('{count} configurations'))); ?></p><?php endif; ?>
        <div class="<?php echo $count > 1 ? 'mt-1.5 ' : ''; ?>flex flex-wrap gap-1.5" dir="ltr">
            <?php foreach ($card['models'] as $model) : ?>
                <span class="inline-flex h-6 items-center rounded-sm border border-[#D5DEE9] bg-[#F5F8FC] px-1.5 text-[12px] font-black text-[#071f42] [font-variant-numeric:tabular-nums]"><?php echo esc_html($model); ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

/* -------------------------------------------------------------------------- *
 * Rendering: single-product.php runs once per configuration.
 * -------------------------------------------------------------------------- */

/** The configuration being collected while the template runs for it, or ''. */
function boblift_marine_series_capturing(): bool
{
    return ($GLOBALS['boblift_marine_render']['capturing'] ?? '') !== '';
}

/**
 * Starts a series page: runs the template for every other configuration first, keeping only their blocks.
 * The post and query are switched as well, since anything resetting post data mid-way would otherwise go back to the series page.
 */
function boblift_marine_series_begin(array $series): void
{
    global $post, $wp_query;
    $models = array_column($series['members'], 'model');
    $requested = isset($_GET['model']) && is_string($_GET['model']) ? wp_unslash($_GET['model']) : '';
    $GLOBALS['boblift_marine_render'] = ['series' => $series, 'active' => boblift_marine_requested_model($models, $requested, $series['home_model']), 'capturing' => '', 'blocks' => [], 'meta' => [], 'open' => []];
    $saved = [$post, $wp_query->post, $wp_query->queried_object, $wp_query->queried_object_id];
    foreach ($series['members'] as $member) {
        if ($member['is_home']) {
            continue;
        }
        $config = get_post($member['id']);
        if (!$config instanceof WP_Post) {
            continue;
        }
        $post = $config;
        $wp_query->post = $config;
        $wp_query->queried_object = $config;
        $wp_query->queried_object_id = $config->ID;
        setup_postdata($config);
        $GLOBALS['boblift_marine_render']['capturing'] = $member['model'];
        ob_start();
        include get_template_directory() . '/single-product.php';
        ob_end_clean();
    }
    $GLOBALS['boblift_marine_render']['capturing'] = '';
    [$post, $wp_query->post, $wp_query->queried_object, $wp_query->queried_object_id] = $saved;
    setup_postdata($post);
}

/** Keeps what the configurator needs from the template's run for one configuration. */
function boblift_marine_series_collect(string $model, array $meta): void
{
    if (isset($GLOBALS['boblift_marine_render'])) {
        $GLOBALS['boblift_marine_render']['meta'][$model] = $meta;
    }
}

/** Starts a block that differs per configuration. Outside a series page it does nothing, so other products print as before. */
function boblift_marine_block_open(string $name): void
{
    if (isset($GLOBALS['boblift_marine_render'])) {
        ob_start();
    }
}

/** Ends a block: stores it, and on the series page's own run prints every configuration's version of it. */
function boblift_marine_block_close(string $name, string $class = ''): void
{
    if (!isset($GLOBALS['boblift_marine_render'])) {
        return;
    }
    $state = &$GLOBALS['boblift_marine_render'];
    $html = (string) ob_get_clean();
    if ($state['capturing'] !== '') {
        $state['blocks'][$state['capturing']][$name] = $html;
        return;
    }
    $state['blocks'][$state['series']['home_model']][$name] = $html;
    echo '<div data-cfg-block="' . esc_attr($name) . '"' . ($class !== '' ? ' class="' . esc_attr($class) . '"' : '') . '>';
    foreach ($state['series']['members'] as $member) {
        $block = (string) ($state['blocks'][$member['model']][$name] ?? '');
        $active = $member['model'] === $state['active'];
        echo '<div data-cfg-variant="' . esc_attr($member['model']) . '"' . ($active ? '' : ' hidden') . '>' . ($active ? $block : boblift_marine_hidden_block($block)) . '</div>';
    }
    echo '</div>';
}

/** A hidden configuration holds its ids back so they stay unique, and asks for no image until it is shown. */
function boblift_marine_hidden_block(string $html): string
{
    $html = (string) preg_replace('/(<[a-z][a-z0-9]*\b[^>]*?)\sid="([^"]+)"/i', '$1 data-vid="$2"', $html);
    return str_replace('loading="eager" fetchpriority="high"', 'loading="lazy"', $html);
}

/**
 * Words of the configuration box. Their translations are keyed apart ("cfg|Winch") because the same English words elsewhere on
 * the site already have other translations, which this box must not change or take over.
 */
function boblift_marine_text(string $text): string
{
    $key = 'cfg|' . $text;
    $translated = boblift_localized_static_text($key);
    return $translated === $key ? $text : $translated;
}

/** The label of one choice: [code, meaning]. */
function boblift_marine_choice_label(string $dimension, string $value): array
{
    $t = static fn(string $text): string => boblift_localized_static_text($text);
    if ($dimension === 'sections') {
        $hydraulic = (int) substr($value, 1) - 1;
        return [$value, str_replace('{count}', (string) $hydraulic, $t($hydraulic === 1 ? '1 main boom + 1 hydraulic boom' : '1 main boom + {count} hydraulic booms'))];
    }
    if ($dimension === 'winch') {
        return $value === '1' ? ['J', boblift_marine_text('With winch')] : ['—', boblift_marine_text('No winch')];
    }
    if ($dimension === 'manual') {
        return $value === '1' ? ['+1', ''] : ['—', ''];
    }
    return [$value, ''];
}

/** The configuration box under the model breakdown, with the data the theme script switches from. */
function boblift_marine_render_configurator(): void
{
    $state = $GLOBALS['boblift_marine_render'] ?? null;
    if (!$state || $state['capturing'] !== '') {
        return;
    }
    $t = static fn(string $text): string => boblift_marine_text($text);
    $members = $state['series']['members'];
    $count = count($members);
    $dimensions = boblift_marine_series_dimensions($members);
    $active_values = [];
    foreach ($members as $member) {
        if ($member['model'] === $state['active']) {
            $active_values = $member['values'];
        }
    }
    $headings = ['named' => $t('Model'), 'sections' => $t('Boom sections'), 'winch' => $t('Winch'), 'manual' => $t('Manual extension (+1)')];
    $unavailable = $t('Not offered with the current choice: selecting it changes the other options');
    $data = [
        'default' => $state['series']['home_model'],
        'active' => $state['active'],
        'dimensions' => array_keys($dimensions),
        'unavailable' => $unavailable,
        'variants' => array_map(static fn(array $member): array => [
            'model' => $member['model'],
            'slug' => $member['slug'],
            'values' => $member['values'],
        ] + (array) ($state['meta'][$member['model']] ?? []), $members),
    ];
    ?>
    <div class="boblift-series-config mt-6 rounded-sm border border-[#D5DEE9] bg-white/70 p-4 sm:p-5" data-series-config>
        <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
            <p class="text-sm font-black text-[#071f42]"><?php echo esc_html($t('Choose your configuration')); ?></p>
            <p class="text-xs text-slate-500"><?php echo esc_html($count === 1 ? $t('1 configuration in this series') : str_replace('{count}', (string) $count, $t('{count} configurations in this series'))); ?></p>
        </div>
        <?php foreach ($dimensions as $dimension => $values) : ?>
            <div class="mt-4">
                <p class="text-[11px] font-black uppercase leading-4 tracking-[0.12em] text-slate-500 rtl:tracking-normal" id="boblift-cfg-<?php echo esc_attr($dimension); ?>"><?php echo esc_html($headings[$dimension]); ?></p>
                <div class="mt-2 flex flex-wrap gap-2" role="radiogroup" aria-labelledby="boblift-cfg-<?php echo esc_attr($dimension); ?>">
                    <?php foreach ($values as $value) :
                        [$code, $meaning] = boblift_marine_choice_label($dimension, $value);
                        $checked = ($active_values[$dimension] ?? '') === $value;
                        $wanted = array_merge($active_values, [$dimension => $value]);
                        $exists = (bool) array_filter($members, static fn(array $member): bool => !array_diff_assoc($wanted, $member['values']));
                        ?>
                        <button class="boblift-model-variant inline-flex min-h-11 flex-col items-start justify-center gap-0.5 rounded-sm border px-3.5 py-2 text-start transition sm:flex-row sm:items-center sm:gap-2 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-blue-100 <?php echo $checked ? 'border-[#071f42] bg-[#071f42] text-white' : 'border-[#D5DEE9] bg-white text-[#071f42] hover:border-blue-300'; ?> <?php echo $exists ? '' : 'border-dashed'; ?>" type="button" role="radio" aria-checked="<?php echo $checked ? 'true' : 'false'; ?>" tabindex="<?php echo $checked ? '0' : '-1'; ?>" data-cfg-group="<?php echo esc_attr($dimension); ?>" data-cfg-value="<?php echo esc_attr($value); ?>"<?php echo $exists ? '' : ' title="' . esc_attr($unavailable) . '"'; ?>>
                            <span class="text-sm font-black [font-variant-numeric:tabular-nums]" dir="ltr"><?php echo esc_html($code); ?></span>
                            <?php if ($meaning !== '') : ?><span class="text-[13px] font-normal opacity-80"><?php echo esc_html($meaning); ?></span><?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (boblift_marine_series_all_have($members, 'winch', '1')) : ?>
            <p class="mt-3 text-[13px] text-slate-600"><?php echo esc_html($t('Winch (J) on every configuration')); ?></p>
        <?php endif; ?>
        <?php if (boblift_marine_series_all_have($members, 'manual', '1')) : ?>
            <p class="mt-3 text-[13px] text-slate-600"><?php echo esc_html($t('+1 manual extension on every configuration')); ?></p>
        <?php endif; ?>
        <p class="mt-4 border-t border-[#E6EDF5] pt-3 text-[13px] text-slate-600"><?php echo esc_html($t('Selected model')); ?>: <strong class="font-black text-[#071f42] [font-variant-numeric:tabular-nums]" dir="ltr" data-cfg-model><?php echo esc_html($state['active']); ?></strong></p>
        <script type="application/json" data-series-data><?php echo wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG); ?></script>
    </div>
    <?php
}

/** ProductGroup with one Product per configuration. No offers or prices: the site publishes none. */
function boblift_marine_series_schema(): array
{
    $state = $GLOBALS['boblift_marine_render'] ?? null;
    if (!$state) {
        return [];
    }
    $series = $state['series'];
    $t = static fn(string $text): string => boblift_marine_text($text);
    // Spec labels are translated with the product texts.
    $spec = static fn(string $text): string => function_exists('boblift_localize_product_text_fragments') ? (string) boblift_localize_product_text_fragments($text) : $text;
    $brand = ['@type' => 'Brand', 'name' => 'BOB-LIFT'];
    $manufacturer = ['@type' => 'Organization', 'name' => 'Xuzhou BOB-LIFT Construction Machinery Co., Ltd.'];
    $variants = [];
    foreach ($series['members'] as $member) {
        $meta = (array) ($state['meta'][$member['model']] ?? []);
        $values = $member['values'];
        $properties = [];
        $add = static function (string $name, $value, string $unit = '') use (&$properties): void {
            if ($value === '' || $value === null || $value === 0.0) {
                return;
            }
            $properties[] = ['@type' => 'PropertyValue', 'name' => $name, 'value' => $value] + ($unit !== '' ? ['unitText' => $unit] : []);
        };
        $add($t('Boom sections'), (string) $values['sections']);
        if ($values['winch'] !== '') {
            $add($t('Winch'), $values['winch'] === '1' ? $t('With winch') : $t('No winch'));
        }
        if ($values['manual'] === '1') {
            $add($t('Manual extension (+1)'), '+1');
        }
        $figures = boblift_product_filter_values($member['id']);
        $add($spec('Max Lifting Capacity'), (float) ($figures['capacity'] ?? 0), 't');
        $add($spec('Max Lifting Moment'), (float) ($figures['moment'] ?? 0), 't·m');
        foreach ((array) (boblift_product_drawing_data($member['id'])['rows'] ?? []) as $row) {
            if (($row['key'] ?? '') === 'radius') {
                $add($spec('Max Working Radius'), trim((string) ($row['value'] ?? '')));
            }
        }
        $variants[] = array_filter([
            '@type' => 'Product',
            'name' => (string) ($meta['inquiry']['title'] ?? $member['model']),
            'model' => $member['model'],
            'sku' => $member['model'],
            'url' => $member['url'],
            'image' => (string) ($meta['image'] ?? ''),
            'brand' => $brand,
            'manufacturer' => $manufacturer,
            'additionalProperty' => $properties,
        ]);
    }
    $home_meta = (array) ($state['meta'][$series['home_model']] ?? []);
    return array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'ProductGroup',
        '@id' => $series['url'] . '#series',
        'name' => (string) ($home_meta['name'] ?? '') . ' (' . boblift_marine_series_label($series['code']) . ')',
        'productGroupID' => $series['code'],
        'url' => $series['url'],
        'image' => (string) ($home_meta['image'] ?? ''),
        'description' => (string) ($home_meta['description'] ?? ''),
        'brand' => $brand,
        'manufacturer' => $manufacturer,
        'hasVariant' => $variants,
    ]);
}
