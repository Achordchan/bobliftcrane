<?php
/**
 * Crane series: one page per use, boom type and rated capacity, with the models of that class as configurations.
 *
 * Marine, truck mounted and pickup cranes are grouped; every other product keeps its own page. The series page is the product whose slug
 * has no model suffix (12-ton-knuckle-boom-marine-crane). The other products of the group redirect to it with ?model=<model> and
 * leave the lists, the sitemap, the counts and the related models, but stay editable as products. Their posts remain the source
 * of each configuration's specifications, photos and drawings: single-product.php renders once per configuration and the blocks
 * of all of them are printed together, so search engines read every configuration and the theme script only shows the chosen one.
 *
 * The winch differs by kind (boblift_series_winch_mode()): optional on marine knuckle cranes, standard on marine telescopic ones,
 * and not asked on truck cranes, where only the models on the site are listed.
 *
 * Grouping can be changed with the boblift_series_groups filter, the series name with boblift_series_code.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * What a marine model name says (A = boom sections, BLZ uses K; T = marine; J = winch; +1 = manual extension).
 * Names without that pattern (SQZ330T) count as a choice of their own, under "named".
 */
function boblift_series_parse_model(string $model): array
{
    $model = trim($model);
    $manual = str_ends_with($model, '+1');
    $base = $manual ? substr($model, 0, -2) : $model;
    if (preg_match('/^SQ\d+(?:\.\d+)?[ZS](?<section>A\d)T?(?<winch>J?)$/', $base, $m) || preg_match('/^BLZ\d+(?<section>K\d)T?(?<winch>J?)$/', $base, $m)) {
        return ['named' => '', 'sections' => $m['section'], 'winch' => $m['winch'] !== '' ? '1' : '0', 'manual' => $manual ? '1' : '0'];
    }
    if (preg_match('/^(?<name>SQZ\d+T?)(?<winch>J?)$/', $base, $m)) {
        return ['named' => $m['name'], 'sections' => '', 'winch' => $m['winch'] !== '' ? '1' : '0', 'manual' => $manual ? '1' : '0'];
    }
    return ['named' => $model, 'sections' => '', 'winch' => '', 'manual' => ''];
}

/** The series name shown on cards and under the model: SQ12Z-T (marine), SQ12Z (truck), BLZ90-T. This is the one place that names a series. */
function boblift_series_code(string $model): string
{
    if (preg_match('/^SQ(\d+(?:\.\d+)?)([ZS])A\d(T?)/', $model, $m)) {
        $code = 'SQ' . $m[1] . $m[2] . ($m[3] !== '' ? '-T' : '');
    } elseif (preg_match('/^(BLZ\d+)K\d(T?)/', $model, $m)) {
        $code = $m[1] . ($m[2] !== '' ? '-T' : '');
    } else {
        // SQZ names carry a lifting moment class and differ per series, so the model names the series itself.
        $code = $model;
    }
    return (string) apply_filters('boblift_series_code', $code, $model);
}

/**
 * The same crane with the winch the other way round: J follows T and +1 stays last (SQ12ZA6T - SQ12ZA6TJ, SQ08ZA3T+1 - SQ08ZA3TJ+1,
 * SQZ330T - SQZ330TJ). A knuckle boom crane can have a winch or not; a telescopic one must have it (owner, 2026-11-05), so it has no
 * twin. '' when the name does not follow the pattern.
 */
function boblift_series_winch_twin(string $model): string
{
    if (!preg_match('/^(.*T)(J?)(\+1)?$/', $model, $m)) {
        return '';
    }
    return $m[1] . ($m[2] === '' ? 'J' : '') . ($m[3] ?? '');
}

/**
 * Every choice a series offers: its real configurations, plus the other winch version of each that has no product of its own.
 * Such a version takes the figures, photos and drawings of the configuration it comes from, except the self weight, which is
 * not known, so no figure is made up. Real members in, members with derived, source and figures out.
 */
function boblift_series_expand_variants(array $members, string $series_url, string $winch = 'optional'): array
{
    if ($winch !== 'optional') {
        return array_map(static fn(array $member): array => $member + ['derived' => false, 'source' => $member['model']], $members);
    }
    $real = array_column($members, 'model');
    $variants = [];
    foreach ($members as $member) {
        $variants[] = $member + ['derived' => false, 'source' => $member['model']];
        $twin = (string) boblift_series_winch_twin($member['model']);
        if ($twin === '' || in_array($twin, $real, true) || boblift_series_parse_model($twin)['winch'] === '') {
            continue;
        }
        $variants[] = array_merge($member, [
            'model' => $twin,
            'url' => boblift_series_model_url($series_url, $twin),
            'is_home' => false,
            // The source's choices as they stand (a series of two makes chooses by the model, "named"), with only the winch
            // turned over: a re-parsed name would lose them and leave a choice with nothing picked.
            'values' => array_merge((array) ($member['values'] ?? []), ['winch' => boblift_series_parse_model($twin)['winch']]),
            'derived' => true,
            'source' => $member['model'],
            'figures' => array_merge((array) ($member['figures'] ?? []), ['weight' => null]),
        ]);
    }
    usort($variants, static fn(array $a, array $b): int => strnatcmp($a['model'], $b['model']));
    return $variants;
}

/** The model an inquiry is really about: the other winch version when the customer's product text names it. */
function boblift_series_inquired_model(string $model, string $product_text): string
{
    $twin = boblift_series_winch_twin($model);
    return $twin !== '' && preg_match('/(?:^|[\s(;,])' . preg_quote($twin, '/') . '(?:$|[\s);,])/i', $product_text) ? $twin : $model;
}

/** ?model= link to one configuration; the plus of +1 must be encoded or it reads as a space. */
function boblift_series_model_url(string $series_url, string $model): string
{
    return $series_url . (str_contains($series_url, '?') ? '&' : '?') . 'model=' . rawurlencode($model);
}

/** The configuration a ?model= value asks for, or $default. A bare plus arrives as a space. */
function boblift_series_requested_model(array $models, string $requested, string $default): string
{
    $requested = str_replace(' ', '+', trim($requested));
    foreach ($models as $model) {
        if (strcasecmp($model, $requested) === 0) {
            return $model;
        }
    }
    return $default;
}

/** How a model names its configuration: SQ boom sections (A4), BLZ boom sections (K4), a named class (SQZ430) or none of these. */
function boblift_series_naming(string $model): string
{
    $values = boblift_series_parse_model($model);
    if ($values['sections'] !== '') {
        return str_starts_with($model, 'BLZ') ? 'K' : 'A';
    }
    return $values['winch'] !== '' ? 'named' : 'other';
}

/**
 * Members whose names do not compare (A4 beside K4, SQ20ZA6 beside SQZ430) are chosen by the model itself: "named" becomes
 * the model without its winch letter, so the winch stays a choice of its own, and the boom sections are no longer asked.
 */
function boblift_series_unify_naming(array $members): array
{
    if (count(array_unique(array_map(static fn(array $member): string => boblift_series_naming((string) $member['model']), $members))) < 2) {
        return $members;
    }
    foreach ($members as $position => $member) {
        $model = (string) $member['model'];
        $name = ($member['values']['winch'] ?? '') === '1' ? (string) preg_replace('/J(?=(?:\+1)?$)/', '', $model) : $model;
        $members[$position]['values'] = array_merge($member['values'], ['named' => $name, 'sections' => '']);
    }
    return $members;
}

/** Models in display order: natural order, with another make (BLZ) after the SQ models of its class. */
function boblift_series_model_order(string $a, string $b): int
{
    return (str_starts_with($a, 'BLZ') <=> str_starts_with($b, 'BLZ')) ?: strnatcmp($a, $b);
}

/**
 * Groups product rows by boom type and capacity. Rows: id, slug, model, capacity, boom ("knuckle" or "telescopic"), legacy.
 * The series page is the row whose slug has no model suffix, else the first current model, else the first model.
 */
function boblift_series_group_rows(array $rows): array
{
    $groups = [];
    foreach ($rows as $row) {
        $capacity = (float) $row['capacity'];
        if ($capacity <= 0 || trim((string) $row['model']) === '') {
            continue;
        }
        $family = (string) ($row['family'] ?? 'marine');
        // One class is one series, whichever make the model names (BLZ129K4 beside the 6.3 t SQ models).
        $key = ($family === 'marine' ? '' : $family . '-') . $row['boom'] . '-' . rtrim(rtrim(number_format($capacity, 2, '.', ''), '0'), '.');
        $groups[$key] ??= ['key' => $key, 'family' => $family, 'boom' => $row['boom'], 'capacity' => $capacity, 'members' => []];
        $groups[$key]['members'][] = $row;
    }
    foreach ($groups as $key => $group) {
        usort($group['members'], static fn(array $a, array $b): int => boblift_series_model_order($a['model'], $b['model']));
        // The series page is a current model: the capacity-named page first, then any current member. A previous-model
        // home would take the whole series out of the lists, so one is chosen only when every member is a previous model.
        $is_named = static fn(array $member): bool => (bool) preg_match('/^\d[\d-]*-ton-(?:knuckle|telescopic)-boom-(?:marine|truck-mounted)-crane$|^800-kg-pickup-crane-with-outriggers$/', (string) $member['slug']);
        $home = null;
        foreach ([
            static fn(array $m): bool => empty($m['legacy']) && $is_named($m),
            static fn(array $m): bool => empty($m['legacy']),
            $is_named,
        ] as $test) {
            foreach ($group['members'] as $member) {
                if ($test($member)) {
                    $home = $member;
                    break 2;
                }
            }
        }
        $home ??= $group['members'][0];
        $groups[$key]['members'] = $group['members'];
        $groups[$key]['home'] = (int) $home['id'];
        $groups[$key]['code'] = boblift_series_code((string) $home['model']);
    }
    uasort($groups, static fn(array $a, array $b): int => ($a['family'] <=> $b['family']) ?: ($a['boom'] <=> $b['boom']) ?: ($a['capacity'] <=> $b['capacity']));
    return $groups;
}

/** The choices a series offers, in display order: [dimension => values]. Only dimensions with two or more values are asked. */
function boblift_series_dimensions(array $members): array
{
    $dimensions = [];
    foreach (['named', 'sections', 'winch', 'manual'] as $dimension) {
        $values = array_values(array_unique(array_filter(array_map(static fn(array $member): string => (string) ($member['values'][$dimension] ?? ''), $members), static fn(string $value): bool => $value !== '')));
        usort($values, 'boblift_series_model_order');
        if (count($values) > 1) {
            $dimensions[$dimension] = $values;
        }
    }
    // Where the boom sections already settle the +1 (SQ1ZA3+1 beside SQ1ZA4) it is not a choice: the section's label says it.
    if (isset($dimensions['manual'], $dimensions['sections'])) {
        $settled = true;
        foreach ($dimensions['sections'] as $section) {
            $own = array_filter($members, static fn(array $member): bool => ($member['values']['sections'] ?? '') === $section);
            $settled = $settled && count(array_unique(array_map(static fn(array $member): string => (string) $member['values']['manual'], $own))) === 1;
        }
        if ($settled) {
            unset($dimensions['manual']);
        }
    }
    return $dimensions;
}

/** Whether every configuration has this value, so it is stated once rather than asked: only then is the note shown. */
function boblift_series_all_have(array $members, string $dimension, string $value): bool
{
    return $members !== [] && !array_filter($members, static fn(array $member): bool => (string) ($member['values'][$dimension] ?? '') !== $value);
}

/**
 * One row per product: one filed under several grouped categories joins one family only, pickup before marine before
 * truck mounted, so it is never a member of two series. Rows: id, slug, category.
 */
function boblift_series_one_family(array $found): array
{
    $rank = static fn(string $category): int => $category === 'pickup-cranes' ? 0 : (str_contains($category, 'marine') ? 1 : 2);
    $rows = [];
    foreach ($found as $row) {
        $id = (int) ($row['id'] ?? 0);
        if (!isset($rows[$id]) || $rank((string) $row['category']) < $rank((string) $rows[$id]['category'])) {
            $rows[$id] = $row;
        }
    }
    return array_values($rows);
}

/** The English marine, truck mounted and pickup products with what grouping needs. */
function boblift_series_rows(): array
{
    if (!function_exists('pll_default_language')) {
        return [];
    }
    global $wpdb;
    // The English products, as boblift_product_english_id() reads them: figures and models come from the English pages,
    // whichever language the site is set to default to.
    $language = 'en';
    $found = $wpdb->get_results($wpdb->prepare(
        "SELECT DISTINCT p.ID AS id, p.post_name AS slug, t.slug AS category
        FROM {$wpdb->posts} p
        JOIN {$wpdb->term_relationships} r ON r.object_id = p.ID
        JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = r.term_taxonomy_id AND tt.taxonomy = 'product_category'
        JOIN {$wpdb->terms} t ON t.term_id = tt.term_id AND t.slug IN ('knuckle-boom-marine-cranes', 'telescopic-boom-marine-cranes', 'knuckle-boom-truck-mounted-cranes', 'telescopic-boom-truck-mounted-cranes', 'pickup-cranes')
        JOIN {$wpdb->term_relationships} lr ON lr.object_id = p.ID
        JOIN {$wpdb->term_taxonomy} ltt ON ltt.term_taxonomy_id = lr.term_taxonomy_id AND ltt.taxonomy = 'language'
        JOIN {$wpdb->terms} lt ON lt.term_id = ltt.term_id AND lt.slug = %s
        WHERE p.post_type = 'product' AND p.post_status = 'publish' AND p.post_password = ''",
        $language
    ), ARRAY_A);
    $ids = array_map('intval', array_column((array) $found, 'id'));
    update_postmeta_cache($ids);
    update_object_term_cache($ids, 'product');
    $rows = [];
    foreach (boblift_series_one_family((array) $found) as $row) {
        $id = (int) $row['id'];
        $rows[] = [
            'id' => $id,
            'slug' => (string) $row['slug'],
            'model' => trim((string) get_post_meta($id, '_model', true)),
            'capacity' => (float) get_post_meta($id, '_capacity_value', true),
            'family' => str_contains((string) $row['category'], 'marine') ? 'marine' : ($row['category'] === 'pickup-cranes' ? 'pickup' : 'truck'),
            // The pickup cranes are knuckle boom cranes (Z in the model name).
            'boom' => str_starts_with((string) $row['category'], 'telescopic') ? 'telescopic' : 'knuckle',
            'legacy' => boblift_product_is_legacy($id),
        ];
    }
    return $rows;
}

/** Every series with each member's translations, an index from any language's post, and the posts that leave the lists. */
function boblift_series_map(): array
{
    static $map = null;
    if ($map !== null) {
        return $map;
    }
    $map = boblift_cached_data('product-series', [], 'boblift_series_build_map');
    return $map;
}

/**
 * The translations ({lang: id}) by which a member is part of its series: published without a password, and only in
 * languages where the series page is too. In any other language the configuration keeps its own page.
 */
function boblift_series_joined_translations(array $home, array $member, callable $available): array
{
    $home_languages = array_keys(array_filter($home, static fn($id): bool => $available((int) $id)));
    return array_intersect_key(array_filter($member, static fn($id): bool => $available((int) $id)), array_flip($home_languages));
}

/** Every series with its members' translations. Loads posts, meta and terms in one go: this runs once per language after a save. */
function boblift_series_build_map(): array
{
    $rows = boblift_series_rows();
    $groups = (array) apply_filters('boblift_series_groups', boblift_series_group_rows($rows), $rows);
    $index = [];
    $hidden = [];
    $homes = [];
    $translated = [];
    foreach ($groups as $group) {
        foreach ($group['members'] as $member) {
            $translated[(int) $member['id']] = array_map('intval', function_exists('pll_get_post_translations') ? pll_get_post_translations((int) $member['id']) : []);
        }
    }
    _prime_post_caches(array_merge([], ...array_map('array_values', array_values($translated))), false, false);
    $available = static fn(int $id): bool => get_post_status($id) === 'publish' && (string) get_post_field('post_password', $id) === '';
    foreach ($groups as $key => $group) {
        // A configuration joins its series only in the languages where the series page itself is published without a
        // password; elsewhere it keeps its own page, in the lists and the sitemap, and is not redirected.
        foreach ($group['members'] as $position => $member) {
            $translations = boblift_series_joined_translations($translated[(int) $group['home']] ?? [], $translated[(int) $member['id']] ?? [], $available);
            $groups[$key]['members'][$position]['translations'] = $translations;
            foreach ($translations as $lang => $id) {
                $index[$id] = $key;
                if ((int) $member['id'] !== (int) $group['home']) {
                    $hidden[] = $id;
                    $homes[$id] = (int) ($translated[(int) $group['home']][$lang] ?? 0);
                }
            }
        }
    }
    return ['groups' => $groups, 'index' => $index, 'hidden' => array_values(array_unique($hidden)), 'homes' => array_filter($homes)];
}

/**
 * The series a product belongs to, in that product's language, or null for every product outside the two marine
 * categories. Members: id, slug, model, url (?model=), is_home, values (named, sections, winch, manual).
 */
function boblift_series_for_product(int $post_id): ?array
{
    static $memo = [];
    $map = boblift_series_map();
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
            'en_id' => (int) $member['id'],
            'slug' => (string) get_post_field('post_name', $id),
            'model' => (string) $member['model'],
            'url' => boblift_series_model_url($series_url, (string) $member['model']),
            'is_home' => $id === $home_id,
            'values' => boblift_series_winch_values(boblift_series_parse_model((string) $member['model']), boblift_series_winch_mode((string) ($group['family'] ?? 'marine'), (string) $group['boom'])),
        ];
    }
    $members = boblift_series_unify_naming($members);
    $home_model = '';
    foreach ($members as $member) {
        if ($member['is_home']) {
            $home_model = $member['model'];
        }
    }
    return $memo[$key . '|' . $language] = ['key' => $key, 'code' => (string) $group['code'], 'family' => (string) ($group['family'] ?? 'marine'), 'winch' => boblift_series_winch_mode((string) ($group['family'] ?? 'marine'), (string) $group['boom']), 'boom' => $group['boom'], 'capacity' => $group['capacity'], 'language' => $language, 'home_id' => $home_id, 'home_model' => $home_model, 'url' => $series_url, 'members' => $members];
}

/**
 * The categories a series page is listed under: its own first, then each one another configuration is filed under, so a
 * series keeps its place in every category a configuration had before the configurations left the lists.
 */
function boblift_series_union_categories(array $own, array $members): array
{
    return array_values(array_unique(array_merge($own, ...array_map(static fn($slugs): array => array_map('strval', (array) $slugs), array_values($members)))));
}

/** boblift_series_union_categories() for a product: a series page gets its configurations' categories, any other keeps its own. */
function boblift_series_listed_categories(int $post_id, array $own): array
{
    $series = boblift_series_for_product($post_id);
    if ($series === null || (int) $series['home_id'] !== $post_id) {
        return $own;
    }
    $members = [];
    foreach ($series['members'] as $member) {
        if (!$member['is_home']) {
            $members[] = wp_get_post_terms((int) $member['id'], 'product_category', ['fields' => 'slugs']);
        }
    }
    return boblift_series_union_categories($own, array_filter($members, 'is_array'));
}

/** This product's own entry in its series. */
function boblift_series_member(array $series, int $post_id): ?array
{
    foreach ($series['members'] as $member) {
        if ($member['id'] === $post_id) {
            return $member;
        }
    }
    return null;
}

/** "SQ12Z-T Series" in the page's language; an SQZ series is called by its models: "SQZ330T / SQZ350T". */
function boblift_series_label(array $series): string
{
    if (str_starts_with((string) $series['code'], 'SQZ')) {
        return implode(' / ', array_column($series['members'], 'model'));
    }
    return str_replace('{code}', (string) $series['code'], boblift_localized_static_text('{code} Series'));
}

/**
 * Old configuration pages go to their series, in the same language. Runs before Polylang's and core's canonical checks.
 */
function boblift_series_redirect(): void
{
    if (!is_singular('product') || is_preview()) {
        return;
    }
    // A language-prefixed address that resolved to another language's configuration (/ja/?p=<English id>) goes to the
    // series page of the language asked for, as inc/polylang-rewrites.php does for any other post.
    $id = (int) get_queried_object_id();
    $id = function_exists('boblift_post_in_request_language') ? boblift_post_in_request_language($id) : $id;
    $series = boblift_series_for_product($id);
    $member = $series ? boblift_series_member($series, $id) : null;
    if ($member === null || $member['is_home']) {
        return;
    }
    wp_safe_redirect(boblift_series_redirect_url($member['url'], (string) ($_SERVER['QUERY_STRING'] ?? '')), 301, 'BOB-LIFT product series');
    exit;
}
add_action('template_redirect', 'boblift_series_redirect', 2);

/**
 * The series address plus whatever the old address carried (utm_source, gclid), so ad and campaign tracking survives the move.
 * The parameters that picked the old page (?p=, ?post_type=, ?name=, ...) are dropped: carried to the series address they
 * would pick the configuration again and redirect for ever.
 */
function boblift_series_redirect_url(string $url, string $query): string
{
    $routing = ['model', 'p', 'page_id', 'name', 'pagename', 'product', 'post_type', 'lang', 'preview', 'preview_id', 'attachment_id'];
    $kept = array_filter(explode('&', $query), static fn(string $pair): bool => $pair !== '' && !in_array(strtolower(rawurldecode(strtok($pair, '='))), $routing, true));
    return $kept === [] ? $url : $url . (str_contains($url, '?') ? '&' : '?') . implode('&', $kept);
}

/** Visitors reach a configuration at its series page, so every link to one goes there directly. Editors still see the product's own address. */
function boblift_series_product_link(string $url, $post): string
{
    if (!$post instanceof WP_Post || $post->post_type !== 'product' || is_admin() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return $url;
    }
    $map = boblift_series_map();
    if (!in_array((int) $post->ID, $map['hidden'], true)) {
        return $url;
    }
    $series = boblift_series_for_product((int) $post->ID);
    $member = $series ? boblift_series_member($series, (int) $post->ID) : null;
    return $member ? (string) $member['url'] : $url;
}
add_filter('post_type_link', 'boblift_series_product_link', 20, 2);

/**
 * The key an address is looked up by: its host and path without ".html", lowercased, where this site's own hosts ($bases:
 * the home host and the live domain) read as "site", and a language subdomain of one as "ja.site". A relative address is on
 * $default_host. Null for an address that is not one of this site's .html pages.
 */
function boblift_series_url_key(string $url, array $bases, string $default_host): ?string
{
    $parts = parse_url(html_entity_decode($url, ENT_QUOTES));
    $path = (string) ($parts['path'] ?? '');
    if ($parts === false || !str_ends_with(strtolower($path), '.html') || (isset($parts['scheme']) && !in_array(strtolower($parts['scheme']), ['http', 'https'], true))) {
        return null;
    }
    $host = strtolower((string) ($parts['host'] ?? $default_host));
    $bases = array_map(static fn($base): string => strtolower((string) $base), $bases);
    if (in_array($host, $bases, true)) {
        $host = 'site';
    } else {
        // The longest base first, so a language subdomain of www.example.com is not read as one of example.com.
        usort($bases, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));
        foreach ($bases as $base) {
            if ($base !== '' && str_ends_with($host, '.' . $base) && !str_starts_with($base, 'www.') && !str_starts_with($host, 'www.')) {
                $host = substr($host, 0, -strlen($base)) . 'site';
                break;
            }
        }
    }
    if (!str_ends_with($host, 'site')) {
        return null;
    }
    return $host . strtolower(substr($path, 0, -5));
}

/** This site's hosts for boblift_series_url_key(): the home host and the live domain. */
function boblift_series_site_hosts(): array
{
    $home = strtolower((string) parse_url(home_url('/'), PHP_URL_HOST));
    return array_values(array_unique(array_filter([$home, 'bobliftcrane.com', 'www.bobliftcrane.com'])));
}

/**
 * Old configuration addresses (boblift_series_url_key()) => their series address, in every language. Each language's
 * address starts from its own home as Polylang builds it (directory, subdomain or domain), never from a fixed /xx/ prefix.
 */
function boblift_series_old_paths(): array
{
    static $paths = null;
    if ($paths !== null) {
        return $paths;
    }
    $paths = [];
    $bases = boblift_series_site_hosts();
    $homes = [];
    foreach (boblift_series_map()['hidden'] as $id) {
        $series = boblift_series_for_product((int) $id);
        $member = $series ? boblift_series_member($series, (int) $id) : null;
        $slug = (string) get_post_field('post_name', (int) $id);
        if ($member === null || $slug === '') {
            continue;
        }
        $language = function_exists('pll_get_post_language') ? (string) pll_get_post_language((int) $id) : '';
        $homes[$language] ??= function_exists('pll_home_url') && $language !== '' ? (string) pll_home_url($language) : home_url('/');
        $key = boblift_series_url_key(trailingslashit($homes[$language]) . $slug . '.html', $bases, $bases[0] ?? '');
        if ($key !== null) {
            $paths[$key] = (string) $member['url'];
        }
    }
    return $paths;
}

/** One address: a configuration's old address becomes its series address (campaign parameters and anchor kept); any other comes back unchanged. */
function boblift_series_old_url(string $url): string
{
    $paths = str_contains($url, '.html') ? boblift_series_old_paths() : [];
    $bases = $paths !== [] ? boblift_series_site_hosts() : [];
    return boblift_series_map_old_url($url, $paths, $bases, $bases[0] ?? '');
}

/** boblift_series_old_url() on a given map: the language is told by the address's own host and path. */
function boblift_series_map_old_url(string $url, array $paths, array $bases, string $default_host): string
{
    $key = $paths !== [] ? boblift_series_url_key($url, $bases, $default_host) : null;
    $target = $key !== null ? ($paths[$key] ?? null) : null;
    if ($target === null) {
        return $url;
    }
    $parts = parse_url(html_entity_decode($url, ENT_QUOTES));
    $target = boblift_series_redirect_url($target, (string) ($parts['query'] ?? ''));
    return $target . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
}

/**
 * An old language host's configuration address (es.<old domain>/<slug>.html) on this site: the series page of that language
 * with ?model= when the configuration is translated there ($prefix: the language's directory, "es"), else the English one.
 * Null when the path is no configuration's. Tracking parameters are added by the caller.
 */
function boblift_series_old_path_target(string $path, string $prefix, array $paths, array $bases, string $host): ?string
{
    $path = '/' . ltrim($path, '/');
    foreach (array_filter([$prefix !== '' ? '/' . trim($prefix, '/') . $path : '', $path]) as $candidate) {
        $url = 'https://' . $host . $candidate;
        $target = boblift_series_map_old_url($url, $paths, $bases, $host);
        if ($target !== $url) {
            return $target;
        }
    }
    return null;
}

/**
 * Links written into posts, cases and product texts to a configuration's old address go straight to its series page,
 * instead of through the redirect. Only this site's addresses are touched; anchors and campaign parameters are kept.
 */
function boblift_series_content_links(string $content): string
{
    if ($content === '' || !str_contains($content, '.html') || is_admin()) {
        return $content;
    }
    $paths = boblift_series_old_paths();
    if ($paths === []) {
        return $content;
    }
    $bases = boblift_series_site_hosts();
    return (string) preg_replace_callback(
        '/(\bhref=(["\']))([^"\']+\.html(?:[?#][^"\']*)?)(?=\2)/i',
        static function (array $m) use ($paths, $bases): string {
            $url = boblift_series_map_old_url($m[3], $paths, $bases, $bases[0] ?? '');
            return $url === $m[3] ? $m[0] : $m[1] . esc_url($url);
        },
        $content
    );
}
// After the old-site link rewrite (inc/legacy-redirects.php, 30), which can turn an old address into a configuration's.
add_filter('the_content', 'boblift_series_content_links', 40);

/** The live site's Jetpack sitemap reads posts on its own, without the query filter below. */
function boblift_series_jetpack_sitemap_skip($skip, $post)
{
    return $skip || (is_object($post) && in_array((int) ($post->ID ?? 0), boblift_series_map()['hidden'], true));
}
add_filter('jetpack_sitemap_skip_post', 'boblift_series_jetpack_sitemap_skip', 10, 2);

/**
 * Configurations are not products of their own for visitors: product queries leave them out, so the sitemap, lists, counts,
 * related models and the previous-model library all show one entry per series. The editing screens, REST and WP-CLI still see them.
 */
function boblift_series_filter_query(WP_Query $query): void
{
    // A lookup that really wants a configuration (a feedback entry's exact model, a film of it) asks with boblift_series_members.
    if (is_admin() || (defined('REST_REQUEST') && REST_REQUEST) || (defined('WP_CLI') && WP_CLI) || $query->get('boblift_series_members')) {
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
    $hidden = boblift_series_map()['hidden'];
    if ($hidden === []) {
        return;
    }
    $listed = array_map('intval', (array) $query->get('post__in'));
    // A category query (range tables, compare blocks, related models) finds a series by any of its configurations: the
    // configurations it matches stand for their series pages, which may be filed elsewhere, and the category condition
    // gives way to that list. The category page itself lists from boblift_all_products() with the categories joined.
    if (!$query->is_main_query() && boblift_series_query_has_category($query->get('tax_query'))) {
        $matched = get_posts([
            'post_type' => 'product',
            'post_status' => $query->get('post_status') ?: 'publish',
            'tax_query' => $query->get('tax_query'),
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'suppress_filters' => false,
            'boblift_series_members' => true,
        ] + ($query->get('lang', null) !== null ? ['lang' => $query->get('lang')] : []));
        $ids = boblift_series_listed_ids(array_map('intval', $matched), (array) (boblift_series_map()['homes'] ?? []), $hidden);
        if ($listed !== []) {
            $ids = array_values(array_intersect($ids, boblift_series_listed_ids($listed, (array) (boblift_series_map()['homes'] ?? []), $hidden)));
        }
        // WordPress drops post__not_in once post__in is set: the excluded posts (the product itself, previous models) leave here.
        $query->set('tax_query', []);
        $query->set('post__in', boblift_series_without_ids($ids, (array) $query->get('post__not_in')));
        return;
    }
    if ($listed !== []) {
        // A saved pick of a configuration (featured, related) stands for its series page in the same language.
        $kept = boblift_series_listed_ids($listed, (array) (boblift_series_map()['homes'] ?? []), $hidden);
        $query->set('post__in', boblift_series_without_ids($kept, (array) $query->get('post__not_in')));
        return;
    }
    $query->set('post__not_in', array_values(array_unique(array_merge(array_map('intval', (array) $query->get('post__not_in')), $hidden))));
}
add_action('pre_get_posts', 'boblift_series_filter_query');

/** $ids without $excluded, in order; [0] (matches nothing) when none is left, since an empty post__in would match everything. */
function boblift_series_without_ids(array $ids, array $excluded): array
{
    $left = array_values(array_diff(array_map('intval', $ids), array_map('intval', $excluded)));
    return $left !== [] ? $left : [0];
}

/** Whether a tax_query narrows by product category. */
function boblift_series_query_has_category($tax_query): bool
{
    foreach ((array) $tax_query as $key => $clause) {
        if ($key === 'relation' || !is_array($clause)) {
            continue;
        }
        if (($clause['taxonomy'] ?? '') === 'product_category' || (!isset($clause['taxonomy']) && boblift_series_query_has_category($clause))) {
            return true;
        }
    }
    return false;
}

/**
 * Picked product ids with each configuration replaced by its series page ($homes: configuration id => series page id, both
 * in one language), in the order picked and once each. A configuration without a known series page is left out.
 */
function boblift_series_listed_ids(array $listed, array $homes, array $hidden): array
{
    $hidden = array_flip(array_map('intval', $hidden));
    $kept = [];
    foreach ($listed as $id) {
        $id = (int) ($homes[(int) $id] ?? $id);
        if ($id > 0 && !isset($hidden[$id])) {
            $kept[$id] = $id;
        }
    }
    return array_values($kept);
}

/** Cases or feedback of every configuration of a series, once each: they all belong on the series page. */
function boblift_series_related_items(array $series, callable $read, int $limit = 0): array
{
    $items = [];
    foreach ($series['members'] as $member) {
        foreach ((array) $read((int) $member['id']) as $item) {
            $items[(string) ($item['id'] ?? $item['url'] ?? md5(serialize($item)))] ??= $item;
        }
    }
    $items = array_values($items);
    return $limit > 0 ? array_slice($items, 0, $limit) : $items;
}

/**
 * Whether one configuration meets every chosen range at once (the same rule as theme.js): $ranges is {"capacity"|"moment"|
 * "weight": [bins]}, an empty list does not narrow; capacity is the entry's listing figure.
 */
function boblift_filter_configs_match(array $configs, float $capacity, array $ranges, callable $in_bin): bool
{
    foreach ($configs ?: [['m' => 0.0, 'w' => 0.0]] as $config) {
        $figures = ['capacity' => $capacity, 'moment' => (float) ($config['m'] ?? 0), 'weight' => (float) ($config['w'] ?? 0)];
        $meets = true;
        foreach ($ranges as $filter => $bins) {
            if ($bins && !array_filter((array) $bins, static fn($bin): bool => $in_bin($figures[$filter] ?? 0.0, (string) $bin))) {
                $meets = false;
                break;
            }
        }
        if ($meets) {
            return true;
        }
    }
    return false;
}

/** A series page answers searches for any of its models, since the configurations have no result of their own. */
function boblift_series_search_models(int $post_id): array
{
    return array_keys(boblift_series_search_targets($post_id));
}

/**
 * Every name a series page answers to, each with the address that opens it on the matching configuration: the models, the
 * other winch version of each, every configuration's former names (_model_aliases) and the series code.
 */
function boblift_series_search_targets(int $post_id): array
{
    $series = boblift_series_for_product($post_id);
    if (!$series || $series['home_id'] !== $post_id) {
        return [];
    }
    $home_url = (string) get_permalink($post_id);
    $members = [];
    foreach ($series['members'] as $member) {
        $members[] = [
            'model' => (string) $member['model'],
            'url' => (string) $member['url'],
            'aliases' => preg_split('/[,;\/，、\n]+/u', (string) get_post_meta((int) $member['en_id'], '_model_aliases', true)) ?: [],
        ];
    }
    return boblift_series_search_names($members, (string) $series['winch'], (string) $series['code'], $home_url);
}

/**
 * The names behind boblift_series_search_targets(): real names first (each model, its other winch version), then former
 * names, which never take over a real model's name, then the series code.
 */
function boblift_series_search_names(array $members, string $winch, string $code, string $home_url): array
{
    $targets = [];
    foreach ($members as $member) {
        $model = (string) $member['model'];
        $targets[$model] = (string) $member['url'];
    }
    foreach ($members as $member) {
        $model = (string) $member['model'];
        if ($winch === 'optional' && ($twin = (string) boblift_series_winch_twin($model)) !== '') {
            // The other winch version, which has no product of its own.
            $targets[$twin] ??= boblift_series_model_url($home_url, $twin);
        }
        if ($winch === 'standard') {
            $targets[(string) preg_replace('/T(\+1)?$/', 'TJ$1', $model)] ??= (string) $member['url'];
        }
    }
    foreach ($members as $member) {
        foreach ((array) $member['aliases'] as $alias) {
            $alias = trim((string) $alias);
            if ($alias !== '') {
                $targets[$alias] ??= (string) $member['url'];
            }
        }
    }
    if ($code !== '') {
        $targets[$code] ??= $home_url;
    }
    return $targets;
}

/** What a product card shows for a series page: its code and models. Null for any other product. */
/* -------------------------------------------------------------------------- *
 * Product cards: what a card says about a series or a single product.
 * -------------------------------------------------------------------------- */

/** "1–1.3 t·m", "280–330 kg" or one value, from the values that exist; '' when none does. */
function boblift_series_figure_range(array $values): string
{
    $numbers = [];
    $unit = '';
    $approx = false;
    foreach ($values as $value) {
        if (is_int($value) || is_float($value)) {
            $numbers[] = (float) $value;
            $unit = 'kg';
        } elseif (preg_match('/^(≈\s*)?([\d.,]+)\s*(t·m|kN·m|m|kg)$/u', trim((string) $value), $match)) {
            $numbers[] = (float) str_replace(',', '', $match[2]);
            $unit = $match[3];
            $approx = $approx || $match[1] !== '';
        }
    }
    if ($numbers === []) {
        return '';
    }
    $format = static fn(float $number): string => rtrim(rtrim(number_format($number, 2, '.', ','), '0'), '.');
    $low = min($numbers);
    $high = max($numbers);
    return ($approx ? '≈ ' : '') . $format($low) . ($high > $low ? '–' . $format($high) : '') . ' ' . $unit;
}

/**
 * Any figure across a series' configurations: a range when all are numbers in one unit ("12.9–13.2 t·m", "3–5", "360–400°"),
 * else the different values as written ("3+1 / 4"). Empty values are left out.
 */
function boblift_series_compare_range(array $values, bool $numbers_only = false, string $language = '', string $source_language = 'en'): string
{
    $values = array_values(array_unique(array_filter(array_map(static fn($value): string => trim((string) $value), $values), static fn(string $value): bool => $value !== '')));
    if ($values === []) {
        return '';
    }
    $numbers = [];
    $units = [];
    $approx = false;
    foreach ($values as $value) {
        if (!preg_match('/^(≈\s*)?(\d{1,3}(?:[ \x{00A0}\x{202F}]\d{3})+(?:[.,]\d+)?|\d[\d,.\x{00A0}\x{202F}]*)\s*([^\d\s+\/]*(?:\s[^\d\s+\/]+)?)$/u', $value, $match) || ($number = boblift_series_parse_number($match[2], $source_language)) === null) {
            return $numbers_only ? '' : implode(' / ', $values);
        }
        $numbers[] = $number;
        $units[$match[3]] = true;
        $approx = $approx || $match[1] !== '';
    }
    if (count($units) > 1) {
        return $numbers_only ? '' : implode(' / ', $values);
    }
    $unit = (string) array_key_first($units);
    $format = static fn(float $number): string => boblift_series_format_number($number, $language);
    $low = min($numbers);
    $high = max($numbers);
    return ($approx ? '≈ ' : '') . $format($low) . ($high > $low ? '–' . $format($high) : '') . ($unit === '' || $unit === '°' ? $unit : ' ' . $unit);
}

/**
 * A figure written in any of the site's languages as a number: "6,300" and "6.300" are 6300 when the separator is followed by
 * groups of three digits; "5,5" and "5.5" are 5.5; a space or narrow space between groups is a thousands separator. Null when
 * the text is not one number.
 */
function boblift_series_parse_number(string $text, string $language): ?float
{
    $text = trim((string) preg_replace('/[\x{00A0}\x{202F}\s]/u', '', $text));
    if ($text === '' || !preg_match('/^\d[\d.,]*$/', $text)) {
        return null;
    }
    // English and the other languages written with a decimal point (the English source data above all): the point is the
    // decimal point and a comma groups thousands, so 4.125 m stays 4.125 and 12,000 kg is 12000.
    if (!boblift_series_decimal_comma($language)) {
        return preg_match('/^(?:\d{1,3}(?:,\d{3})+|\d+)(?:\.\d+)?$/', $text) ? (float) str_replace(',', '', $text) : null;
    }
    if (preg_match('/^\d{1,3}(?:,\d{3})+(?:\.\d+)?$/', $text)) {
        return (float) str_replace(',', '', $text);
    }
    if (preg_match('/^\d{1,3}(?:\.\d{3})+(?:,\d+)?$/', $text)) {
        return (float) str_replace(',', '.', str_replace('.', '', $text));
    }
    if (preg_match('/^\d+(?:[.,]\d+)?$/', $text)) {
        return (float) str_replace(',', '.', $text);
    }
    return null;
}

/** Whether a language writes decimals with a comma (5,5): Spanish, French and Russian on this site. */
function boblift_series_decimal_comma(string $language): bool
{
    return in_array($language, ['es', 'fr', 'ru'], true);
}

/** A number for a figure in the page's language: 12,000 and 5.5 in English; 12,000 and 5,5 in Spanish, French and Russian. */
function boblift_series_format_number(float $number, string $language = ''): string
{
    // Up to three decimals, as the data has them (4.125 m), without trailing zeros (0.750 kW reads 0.75).
    $text = rtrim(rtrim(number_format($number, 3, '.', ','), '0'), '.');
    if (boblift_series_decimal_comma($language) && str_contains($text, '.')) {
        // A decimal comma; a figure with decimals keeps its thousands apart with a narrow space, never a second comma.
        $text = strtr($text, ['.' => ',', ',' => "\u{202F}"]);
    }
    return $text;
}

/** A lifting moment in t·m, as every crane shows it. The data has a few in kN·m; the spec table keeps those as entered. */
/** A lifting moment as the data writes it ("13 Ton.m", a bare "2.2", "90 kN·m") in t·m, the way cards and tables compare it. */
function boblift_series_moment_normalized(string $moment): string
{
    $moment = (string) preg_replace('/\s*ton\.?\s*m$/i', ' t·m', trim($moment));
    return boblift_series_moment_in_tm(preg_match('/^\d+(?:\.\d+)?$/', $moment) ? $moment . ' t·m' : $moment);
}

function boblift_series_moment_in_tm(string $moment): string
{
    return preg_match('/^([\d.]+)\s*kN·m$/u', trim($moment), $match) ? rtrim(rtrim(number_format((float) $match[1] / 9.80665, 1, '.', ''), '0'), '.') . ' t·m' : $moment;
}

/** A figure as the page's language writes it: 6,3 in Spanish, Russian and French. */
function boblift_series_local_number(float $value): string
{
    $text = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    return in_array(function_exists('boblift_current_language_slug') ? boblift_current_language_slug() : '', ['es', 'ru', 'fr'], true) ? str_replace('.', ',', $text) : $text;
}

/** A sub-category's button label: its family is already the tab above it. Other sub-categories keep their name. */
function boblift_series_subcategory_label(string $slug, string $title): string
{
    return match (true) {
        (bool) preg_match('/^knuckle-boom-(truck-mounted|marine)-cranes/', $slug) => boblift_series_text('Knuckle Boom'),
        (bool) preg_match('/^telescopic-boom-(truck-mounted|marine)-cranes/', $slug) => boblift_series_text('Telescopic Boom'),
        str_starts_with($slug, 'pickup-cranes') => boblift_series_text('Pickup'),
        default => $title,
    };
}

/** A timber crane's card title: its capacity, and its arm length where the English product title gives one ("Arm Length 7600MM"). */
function boblift_series_timber_title(float $capacity, string $english_title): string
{
    $title = str_replace('{value}', boblift_series_local_number($capacity), boblift_series_text('{value} Ton Timber Crane'));
    if (preg_match('/Arm Length\s*(\d+)\s*MM/i', $english_title, $match)) {
        $title .= ' · ' . boblift_series_local_number((int) $match[1] / 1000) . ' m';
    }
    return $title;
}

/** A truck mounted or marine crane's card title: capacity and boom. What it is mounted on goes in its own label beside the models. */
function boblift_series_short_title(float $capacity, string $boom): string
{
    return str_replace('{value}', boblift_series_local_number($capacity), boblift_series_text($boom === 'telescopic' ? '{value} Ton Telescopic Boom' : '{value} Ton Knuckle Boom'));
}

/**
 * A card is named by what the crane is (capacity and type); the model or series code comes second.
 * Returns title, full_title, eyebrow, code, configs (label, url), specs (the four figures as ranges) and figures (the three a card shows).
 */
function boblift_series_card_view(array $product): array
{
    $id = (int) ($product['id'] ?? 0);
    $model = trim((string) ($product['model'] ?? ''));
    $title = trim((string) ($product['title'] ?? ''));
    $name = $model !== '' ? (string) preg_replace('/^[\s\-_–—|\/:]+|[\s\-_–—|\/:]+$/u', '', str_ireplace($model, '', $title)) : '';
    $series = boblift_series_for_product($id);
    $is_series = $series && $series['home_id'] === $id;
    $english_id = function_exists('boblift_product_english_id') ? (int) boblift_product_english_id($id) : $id;
    $members = $is_series ? boblift_series_members_with_figures($series) : [['model' => $model, 'url' => '', 'values' => [], 'figures' => boblift_series_product_figures($english_id)]];
    $range = static fn(string $key): string => boblift_series_figure_range(array_map(static fn(array $member) => $member['figures'][$key] ?? null, $members));
    $specs = [];
    foreach (['capacity' => 'Max lifting capacity', 'moment' => 'Max lifting moment', 'radius' => 'Max reach', 'weight' => 'Self weight'] as $key => $label) {
        $value = $range($key);
        if ($value !== '') {
            $specs[$key] = ['label' => boblift_series_text($label), 'value' => $value];
        }
    }
    $pickup = $is_series && $series['family'] === 'pickup';
    $configs = [];
    if (count($members) > 1) {
        foreach ($members as $member) {
            $configs[] = ['model' => (string) $member['model'], 'label' => $pickup ? boblift_series_text($member['values']['manual'] === '1' ? 'With outriggers' : 'Without outriggers') : (string) $member['model'], 'url' => (string) $member['url']];
        }
    } elseif ((string) $members[0]['model'] !== '') {
        // A crane with no other configuration still names its model; its address needs no ?model=.
        $configs[] = ['model' => (string) $members[0]['model'], 'label' => (string) $members[0]['model'], 'url' => (string) get_permalink($id)];
    }
    // One card stands for both pickup configurations, so its title leaves out "with outriggers".
    if ($pickup && ($specs['capacity']['value'] ?? '') === '800 kg') {
        $name = boblift_series_text('800 kg Pickup Crane');
    }
    // Timber crane titles name only the type, so the capacity tells the models apart (the product data stays as it is).
    $capacity = (float) get_post_meta($english_id, '_capacity_value', true);
    if (!$is_series && $capacity > 0 && has_term('timber-cranes', 'product_category', $english_id)) {
        $name = boblift_series_timber_title($capacity, (string) get_post_field('post_title', $english_id));
    }
    $full_title = $name !== '' ? $name : $title;
    $type = '';
    if ($is_series && in_array($series['family'], ['truck', 'marine'], true) && $capacity > 0) {
        $name = boblift_series_short_title($capacity, (string) $series['boom']);
        $type = boblift_series_text($series['family'] === 'marine' ? 'Marine Crane' : 'Truck Mounted Crane');
    }
    // Three figures fit a line. A series title already names its capacity.
    $figures = $specs;
    if ($is_series && count($figures) > 3) {
        unset($figures['capacity']);
    }
    if (count($figures) > 3) {
        unset($figures['weight']);
    }
    if ($figures === [] && function_exists('boblift_home_product_card_specs')) {
        $figures = boblift_home_product_card_specs($product);
    }
    $code = $is_series ? boblift_series_label($series) : $model;
    $only = count($configs) === 1 ? $configs[0]['label'] : '';
    return [
        'title' => $name !== '' ? $name : $title,
        // The whole name, for link titles, screen readers and the quick view.
        'full_title' => $full_title,
        // The line above the title: what it is mounted on, or for other cranes their category.
        'eyebrow' => $type !== '' ? $type : trim((string) ($product['category_title'] ?? '')),
        'code' => $code,
        // Beside the configuration labels: the series, unless the only label says the same (SL70, SQZ1000).
        'code_line' => $only === $code ? '' : $code,
        'only_model' => $only,
        'is_series' => $is_series,
        'configs' => $configs,
        'specs' => $specs,
        'figures' => array_values($figures),
    ];
}

/**
 * Each configuration's text for the card's quick view. All show the same spec fields in one order, the home configuration's first
 * and up to six, with a dash for a value one does not have, so switching keeps the grid still. Empty for a single product.
 */
function boblift_series_quick_view_configs(array $product): array
{
    $id = (int) ($product['id'] ?? 0);
    $series = boblift_series_for_product($id);
    if (!$series || $series['home_id'] !== $id || count($series['members']) < 2) {
        return [];
    }
    $members = $series['members'];
    usort($members, static fn(array $a, array $b): int => (int) $b['is_home'] <=> (int) $a['is_home']);
    // Translated rows are matched through their English label: the same field is not always worded the same way in a language.
    $field = static function (array $data, int $en_id): array {
        $local = array_values(array_filter((array) ($data['specs'] ?? []), 'is_array'));
        $english = array_values(array_filter((array) boblift_decode_meta(get_post_meta($en_id, '_specs', true), []), 'is_array'));
        $aligned = function_exists('boblift_spec_rows_aligned') ? boblift_spec_rows_aligned($local, $english) : count($local) === count($english);
        $fields = [];
        foreach ($local as $index => $row) {
            $key = mb_strtolower(trim((string) (($aligned ? ($english[$index]['label'] ?? '') : '') ?: ($row['label'] ?? ''))));
            if ($key !== '') {
                $fields[$key] = ['label' => trim((string) ($row['label'] ?? '')), 'value' => trim((string) ($row['value'] ?? ''))];
            }
        }
        return $fields;
    };
    $rows = [];
    $fields = [];
    $labels = [];
    foreach ($members as $member) {
        $post = get_post((int) $member['id']);
        $rows[$member['model']] = $post ? boblift_product_from_post($post) : [];
        $fields[$member['model']] = $field($rows[$member['model']], (int) $member['en_id']);
        foreach ($fields[$member['model']] as $key => $row) {
            if ($row['value'] !== '') {
                $labels[$key] ??= $row['label'];
            }
        }
    }
    $labels = array_slice($labels, 0, 6, true);
    $configs = [];
    foreach ($series['members'] as $member) {
        $data = $rows[$member['model']];
        $specs = [];
        foreach ($labels as $key => $label) {
            $value = boblift_series_moment_in_tm((string) ($fields[$member['model']][$key]['value'] ?? ''));
            $specs[] = ['label' => $label, 'value' => $value !== '' ? $value : '—'];
        }
        $title = trim((string) ($data['title'] ?? ''));
        $configs[] = [
            // The configuration's own product: the quote list keeps the one chosen, not the series page.
            'id' => (string) $member['id'],
            'model' => (string) $member['model'],
            'label' => $series['family'] === 'pickup' ? boblift_series_text($member['values']['manual'] === '1' ? 'With outriggers' : 'Without outriggers') : (string) $member['model'],
            'url' => (string) $member['url'],
            'slug' => (string) $member['slug'],
            // The quote names the configuration, whichever way its product title is written.
            'title' => stripos($title, (string) $member['model']) !== false ? $title : trim($title . ' (' . $member['model'] . ')'),
            // The sentence states the moment too, and sits right above the grid.
            'summary' => (string) preg_replace_callback('/\d[\d.]*\s*kN·m/u', static fn(array $match): string => boblift_series_moment_in_tm($match[0]), trim((string) ($data['short_description'] ?? ''))),
            'image' => $data ? boblift_product_image($data) : '',
            'specs' => $specs,
            'home' => (bool) $member['is_home'],
        ];
    }
    return $configs;
}

/** The line beside a card's title: its series with the configuration count, or with the one model it has. */
function boblift_series_card_subtitle(array $view): string
{
    $parts = [];
    if ($view['code_line'] !== '') {
        $parts[] = '<bdi>' . esc_html($view['code_line']) . '</bdi>';
    }
    $count = count($view['configs']);
    if ($count > 1) {
        $parts[] = esc_html(str_replace('{count}', (string) $count, boblift_series_text('{count} configurations')));
    } elseif ($view['only_model'] !== '') {
        $parts[] = '<bdi>' . esc_html($view['only_model']) . '</bdi>';
    }
    return implode('<span class="mx-1">·</span>', $parts);
}

/** The configuration being collected while the template runs for it, or ''. */
function boblift_series_capturing(): bool
{
    return ($GLOBALS['boblift_series_render']['capturing'] ?? '') !== '';
}

/**
 * Starts a series page: runs the template for every other configuration first, keeping only their blocks.
 * The post and query are switched as well, since anything resetting post data mid-way would otherwise go back to the series page.
 */
function boblift_series_begin(array $series): void
{
    global $post, $wp_query;
    $variants = boblift_series_expand_variants(boblift_series_members_with_figures($series), $series['url'], (string) $series['winch']);
    $requested = isset($_GET['model']) && is_string($_GET['model']) ? wp_unslash($_GET['model']) : '';
    $active = boblift_series_requested_model(array_column($variants, 'model'), boblift_series_normalize_requested($requested, (string) $series['winch']), $series['home_model']);
    $source = $series['home_model'];
    foreach ($variants as $variant) {
        if ($variant['model'] === $active) {
            $source = $variant['source'];
        }
    }
    // A derived winch version is shown with the blocks of the configuration it comes from.
    $GLOBALS['boblift_series_render'] = ['series' => $series, 'variants' => $variants, 'active' => $active, 'active_source' => $source, 'capturing' => '', 'blocks' => [], 'meta' => [], 'open' => []];
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
        $GLOBALS['boblift_series_render']['capturing'] = $member['model'];
        ob_start();
        include get_template_directory() . '/single-product.php';
        ob_end_clean();
    }
    $GLOBALS['boblift_series_render']['capturing'] = '';
    [$post, $wp_query->post, $wp_query->queried_object, $wp_query->queried_object_id] = $saved;
    setup_postdata($post);
}

/** Keeps what the configurator needs from the template's run for one configuration. */
function boblift_series_collect(string $model, array $meta): void
{
    if (isset($GLOBALS['boblift_series_render'])) {
        $GLOBALS['boblift_series_render']['meta'][$model] = $meta;
    }
}

/** Starts a block that differs per configuration. Outside a series page it does nothing, so other products print as before. */
function boblift_series_block_open(string $name): void
{
    if (isset($GLOBALS['boblift_series_render'])) {
        ob_start();
    }
}

/**
 * A configuration's block shown for its other winch version: the model named in the texts the theme script swaps
 * (data-cfg-model-text) becomes the version's, and the self weight, which is not known, a dash.
 */
function boblift_series_derived_block(string $html, string $source, string $model, string $winch_text = '', string $note = ''): string
{
    // Each changed element keeps its source content in data-cfg-orig, which the theme script restores from: a switch to
    // another configuration must start from the source's own model and weight, not from this page's derived ones.
    $keep = static fn(string $open, string $inner): string => substr($open, 0, -1) . ' data-cfg-orig="' . esc_attr($inner) . '">';
    $html = (string) preg_replace_callback('/(<([a-z0-9]+)\b[^>]*\bdata-cfg-model-text\b[^>]*>)(.*?)(<\/\2>)/s', static fn(array $m): string => $keep($m[1], $m[3]) . str_replace($source, $model, $m[3]) . $m[4], $html);
    $html = (string) preg_replace_callback('/(\b(?:data-cfg-cell="weight"|data-cfg-weight-row(?=[\s>]))[^>]*>.*?)(<dd\b[^>]*>)(.*?)(<\/dd>)/s', static fn(array $m): string => $m[1] . $keep($m[2], $m[3]) . '<span class="text-slate-400">—</span>' . $m[4], $html);
    // A table whose rows could not be placed says the self weight is confirmed with the quote, above it.
    if ($note !== '') {
        $html = (string) preg_replace('/<p\b([^>]*\bdata-cfg-spec-note)\s+hidden><\/p>/', '<p$1>' . esc_html($note) . '</p>', $html);
    }
    // The winch row says what this version has (with or without winch), in the page's language.
    if ($winch_text === '') {
        return $html;
    }
    return (string) preg_replace_callback('/(\bdata-cfg-winch-row(?=[\s>])[^>]*>.*?)(<dd\b[^>]*>)(.*?)(<\/dd>)/s', static fn(array $m): string => $m[1] . $keep($m[2], $m[3]) . '<span dir="auto">' . esc_html($winch_text) . '</span>' . $m[4], $html);
}

/** The winch row's words for a derived version ("With winch" / "No winch", translated), or '' when the page is not on one. */
function boblift_series_derived_winch_text(array $state): string
{
    foreach ((array) ($state['variants'] ?? []) as $variant) {
        if (($variant['model'] ?? '') === ($state['active'] ?? null) && !empty($variant['derived'])) {
            return boblift_series_winch_text((string) ($variant['values']['winch'] ?? ''));
        }
    }
    return '';
}

/** A derived version's note that its self weight is confirmed with the quote, in the page's language. */
function boblift_series_weight_note(string $winch): string
{
    return boblift_series_text($winch === '1' ? 'Winch version: self weight confirmed with your quote' : 'No-winch version: self weight confirmed with your quote');
}

/** boblift_series_weight_note() for the version the page opens on, or '' when that is a configuration of its own. */
function boblift_series_derived_weight_note(array $state): string
{
    foreach ((array) ($state['variants'] ?? []) as $variant) {
        if (($variant['model'] ?? '') === ($state['active'] ?? null) && !empty($variant['derived'])) {
            return boblift_series_weight_note((string) ($variant['values']['winch'] ?? ''));
        }
    }
    return '';
}

/**
 * Whether this language's spec table has an English self weight or winch row that could not be placed ($weight_label,
 * $winch_label empty): a derived version cannot change that row, so the table carries a note instead.
 */
function boblift_series_spec_rows_unplaced(int $english_id, array $specs, string $weight_label, string $winch_label): bool
{
    $source = boblift_decode_meta(get_post_meta($english_id, '_specs', true), []);
    $labels = array_map(static fn($row): string => is_array($row) ? (string) ($row['label'] ?? '') : '', is_array($source) ? $source : []);
    return boblift_series_rows_unplaced($labels, $weight_label, $winch_label);
}

/** boblift_series_spec_rows_unplaced() on the English labels. */
function boblift_series_rows_unplaced(array $english_labels, string $weight_label, string $winch_label): bool
{
    return (in_array('Self Weight', $english_labels, true) && $weight_label === '') || (in_array('Winch', $english_labels, true) && $winch_label === '');
}

/** "With winch" or "No winch" in the page's language; '' when the winch is not stated. */
function boblift_series_winch_text(string $winch): string
{
    return $winch === '1' ? boblift_series_text('With winch') : ($winch === '0' ? boblift_series_text('No winch') : '');
}

/**
 * Whether a section (by its id) is in the blocks already captured for a configuration ($model) or for any configuration
 * (null). The series page's own configuration is captured last, so its sections are judged by the template itself.
 */
function boblift_series_section_in(string $id, ?string $model = null): bool
{
    $blocks = (array) ($GLOBALS['boblift_series_render']['blocks'] ?? []);
    foreach ($model === null ? $blocks : [$model => $blocks[$model] ?? []] as $html_by_name) {
        foreach ((array) $html_by_name as $html) {
            if (str_contains((string) $html, 'id="' . $id . '"')) {
                return true;
            }
        }
    }
    return false;
}

/** Whether the configuration the page opens on has the section: $own for the series page's own one, else its captured blocks. */
function boblift_series_section_shown(string $id, bool $own): bool
{
    $state = $GLOBALS['boblift_series_render'] ?? null;
    if (!$state || $state['active_source'] === $state['series']['home_model']) {
        return $own;
    }
    return boblift_series_section_in($id, (string) $state['active_source']);
}

/** Ends a block: stores it, and on the series page's own run prints every configuration's version of it. */
function boblift_series_block_close(string $name, string $class = ''): void
{
    if (!isset($GLOBALS['boblift_series_render'])) {
        return;
    }
    $state = &$GLOBALS['boblift_series_render'];
    $html = (string) ob_get_clean();
    if ($state['capturing'] !== '') {
        $state['blocks'][$state['capturing']][$name] = $html;
        return;
    }
    $state['blocks'][$state['series']['home_model']][$name] = $html;
    // The body text of a configuration speaks for that product: its weight, its winch. A derived version shows none of it.
    $hide_block = $name === 'overview' && $state['active'] !== $state['active_source'];
    echo '<div data-cfg-block="' . esc_attr($name) . '"' . ($class !== '' ? ' class="' . esc_attr($class) . '"' : '') . ($hide_block ? ' hidden' : '') . '>';
    foreach ($state['series']['members'] as $member) {
        $block = (string) ($state['blocks'][$member['model']][$name] ?? '');
        $active = $member['model'] === $state['active_source'];
        if ($active && $state['active'] !== $state['active_source']) {
            // A winch version without a product of its own: the page is printed with its own model and an open self weight,
            // as the theme script would set them, so the source already says the right thing.
            $block = boblift_series_derived_block($block, (string) $state['active_source'], (string) $state['active'], boblift_series_derived_winch_text($state), boblift_series_derived_weight_note($state));
        }
        echo '<div data-cfg-variant="' . esc_attr($member['model']) . '"' . ($active ? '' : ' hidden') . '>' . ($active ? $block : boblift_series_hidden_block($block)) . '</div>';
    }
    echo '</div>';
}

/** A hidden configuration holds its ids back so they stay unique, and asks for no image until it is shown. */
function boblift_series_hidden_block(string $html): string
{
    $html = (string) preg_replace('/(<[a-z][a-z0-9]*\b[^>]*?)\sid="([^"]+)"/i', '$1 data-vid="$2"', $html);
    return str_replace('loading="eager" fetchpriority="high"', 'loading="lazy"', $html);
}

/**
 * Words of the configuration box. Their translations are keyed apart ("cfg|Winch") because the same English words elsewhere on
 * the site already have other translations, which this box must not change or take over.
 */
function boblift_series_text(string $text): string
{
    $key = 'cfg|' . $text;
    $translated = boblift_localized_static_text($key);
    return $translated === $key ? $text : $translated;
}

/**
 * The figures that tell a series' configurations apart, read from the English product so every language shows the same numbers.
 * Weights and capacities are numbers in kg; moment, reach and height stay as the data writes them. Missing figures are null.
 */
function boblift_series_product_figures(int $english_id): array
{
    $rows = [];
    $specs = boblift_decode_meta(get_post_meta($english_id, '_specs', true), []);
    foreach (is_array($specs) ? $specs : [] as $spec) {
        if (is_array($spec)) {
            $rows[(string) ($spec['label'] ?? '')] = trim((string) ($spec['value'] ?? ''));
        }
    }
    $drawing = [];
    foreach ((array) (boblift_product_drawing_data($english_id)['rows'] ?? []) as $row) {
        $drawing[(string) ($row['key'] ?? '')] = trim((string) ($row['value'] ?? ''));
    }
    return [
        'weight' => boblift_series_kilograms($rows['Self Weight'] ?? ''),
        'capacity' => boblift_series_kilograms($rows['Max Lifting Capacity'] ?? ''),
        'moment' => boblift_series_moment_normalized((string) ($rows['Max Lifting Moment'] ?? '')) ?: null,
    ] + boblift_series_reach_figures($rows, $drawing);
}

/**
 * Reach and lifting height: the spec table's row first (any of its names, boblift_spec_figure_labels()), the drawing's
 * figure only where the table gives none. $rows: English label => value; $drawing: key => value.
 */
function boblift_series_reach_figures(array $rows, array $drawing): array
{
    $labels = function_exists('boblift_spec_figure_labels') ? boblift_spec_figure_labels() : ['radius' => ['Max Working Radius'], 'height' => ['Max Lifting Height']];
    $figures = [];
    foreach (['radius', 'height'] as $key) {
        $figures[$key] = null;
        foreach ($labels[$key] as $label) {
            if (trim((string) ($rows[$label] ?? '')) !== '') {
                $figures[$key] = trim((string) $rows[$label]);
                break;
            }
        }
        $figures[$key] ??= trim((string) ($drawing[$key] ?? '')) ?: null;
    }
    return $figures;
}

/** "3950 kg" as 3950; anything else (a range, tonnes) as null, so no figure is guessed. */
function boblift_series_kilograms(string $value): ?int
{
    return preg_match('/^(\d[\d,]*)\s*kg$/', trim($value), $match) ? (int) str_replace(',', '', $match[1]) : null;
}

function boblift_series_format_kg(int $kilograms): string
{
    return number_format($kilograms, 0, '.', ',') . ' kg';
}

/**
 * How a series treats the winch. Marine knuckle cranes can have one or not, marine telescopic cranes must have one (owner,
 * 2026-11-05). For truck cranes the rule is not settled, so nothing is asked or said about it.
 */
function boblift_series_winch_mode(string $family, string $boom): string
{
    if ($family !== 'marine') {
        return 'none';
    }
    return $boom === 'telescopic' ? 'standard' : 'optional';
}

/** A crane with a standard winch has one whatever its model name says, so there is nothing to choose. */
function boblift_series_winch_values(array $values, string $winch): array
{
    if ($winch === 'standard' && $values['winch'] !== '') {
        $values['winch'] = '1';
    }
    return $values;
}

/** The sentence about the winch for this kind of series, under the comparison and in the structured data; none for truck cranes. */
function boblift_series_winch_sentence(string $winch): string
{
    return match ($winch) {
        'standard' => boblift_series_text('Telescopic boom marine cranes come with a winch as standard.'),
        'optional' => boblift_series_text('Knuckle boom marine cranes can be built with or without a winch.'),
        default => '',
    };
}

/**
 * A marine telescopic model is named as on its drawing, without J, though the winch is standard, so a name with J means the same
 * crane. Other names stay as they are.
 */
function boblift_series_normalize_requested(string $requested, string $winch): string
{
    $requested = str_replace(' ', '+', trim($requested));
    return $winch === 'standard' ? (string) preg_replace('/TJ(\+1)?$/i', 'T$1', $requested) : $requested;
}

/** The series' members with their figures. */
function boblift_series_members_with_figures(array $series): array
{
    static $memo = [];
    $key = $series['key'] . '|' . $series['language'];
    if (!isset($memo[$key])) {
        $memo[$key] = array_map(static fn(array $member): array => $member + ['figures' => boblift_series_product_figures((int) $member['en_id'])], $series['members']);
    }
    return $memo[$key];
}

/**
 * The hero's figures for one configuration: the same five in the same order across a series, so choices compare at a glance. A
 * figure the data does not have shows a dash and no value is guessed.
 */
function boblift_series_hero_figures(array $figures): array
{
    $rows = [];
    foreach ([
        ['capacity', 'Max lifting capacity', $figures['capacity'] !== null ? boblift_series_format_kg($figures['capacity']) : null],
        ['moment', 'Max lifting moment', $figures['moment']],
        ['radius', 'Max reach', $figures['radius']],
        ['height', 'Lifting height', $figures['height']],
        ['weight', 'Self weight', $figures['weight'] !== null ? boblift_series_format_kg($figures['weight']) : null],
    ] as [$key, $label, $value]) {
        $rows[] = ['key' => $key, 'label' => boblift_series_text($label), 'value' => $value !== null && $value !== '' ? $value : '—'];
    }
    return $rows;
}

/** "≈ 16.5 m" and "12,000 kg" as number and unit, so the hero can set the unit small. */
function boblift_series_figure_parts(string $value): array
{
    return preg_match('/^(≈\s*)?([\d.,+]+)\s*(kg|t·m|kN·m|m)$/u', trim($value), $match) ? [$match[1] . $match[2], $match[3]] : [$value, ''];
}

/** Choices that have no version with the rest of this configuration: "dimension|value". */
function boblift_series_unavailable_choices(array $members, array $dimensions, array $values): array
{
    $out = [];
    foreach ($dimensions as $dimension => $options) {
        foreach ($options as $option) {
            // Only what is asked counts: a +1 the boom sections settle is not part of the choice.
            $wanted = array_merge(array_intersect_key($values, $dimensions), [$dimension => $option]);
            if (!array_filter($members, static fn(array $member): bool => !array_diff_assoc($wanted, $member['values']))) {
                $out[] = $dimension . '|' . $option;
            }
        }
    }
    return $out;
}

/** What a winch adds to the weight of this configuration's boom, "+50 kg", when both versions give a weight. */
function boblift_series_winch_difference(array $members, array $values): string
{
    $weights = [];
    foreach ($members as $member) {
        if ($member['values']['sections'] === $values['sections'] && $member['values']['named'] === $values['named'] && $member['values']['manual'] === $values['manual'] && isset($member['figures']['weight'])) {
            $weights[$member['values']['winch']] = (int) $member['figures']['weight'];
        }
    }
    if (!isset($weights['0'], $weights['1'])) {
        return '';
    }
    $difference = $weights['1'] - $weights['0'];
    return ($difference >= 0 ? '+' : '−') . number_format(abs($difference), 0, '.', ',') . ' kg';
}

/**
 * How a configuration differs from the series page's model, as far as the data knows: "Self weight −400 kg · With winch".
 * Empty for that model itself. Figures one of the two lacks are left out.
 */
function boblift_series_configuration_difference(array $member, array $base): string
{
    if ($member['model'] === $base['model']) {
        return '';
    }
    $parts = [];
    $signed = static function (float $difference, string $unit): string {
        // Isolated so a right-to-left page keeps the sign, number and unit in order.
        return "\u{2066}" . ($difference >= 0 ? '+' : '−') . rtrim(rtrim(number_format(abs($difference), 2, '.', ','), '0'), '.') . ' ' . $unit . "\u{2069}";
    };
    $measure = static function ($value): ?array {
        if (is_int($value) || is_float($value)) {
            return [(float) $value, 'kg'];
        }
        return preg_match('/([\d.,]+)\s*(kg|t·m|kN·m|m)$/u', (string) $value, $match) ? [(float) str_replace(',', '', $match[1]), $match[2]] : null;
    };
    // Every figure both give and that differs; one the other lacks cannot be compared and is left out.
    foreach (['capacity' => 'Max lifting capacity', 'moment' => 'Max lifting moment', 'radius' => 'Max reach', 'height' => 'Lifting height', 'weight' => 'Self weight'] as $key => $label) {
        $own = $measure($member['figures'][$key] ?? null);
        $other = $measure($base['figures'][$key] ?? null);
        if ($own !== null && $other !== null && $own[1] === $other[1] && abs($own[0] - $other[0]) > 0.001) {
            $parts[] = boblift_series_text($label) . ' ' . $signed($own[0] - $other[0], $own[1]);
        }
    }
    if ($member['values']['winch'] !== '' && $member['values']['winch'] !== $base['values']['winch']) {
        $parts[] = boblift_series_text($member['values']['winch'] === '1' ? (empty($member['derived']) ? 'With winch' : 'With winch (optional)') : 'No winch');
    }
    return implode(' · ', $parts);
}

/** "3 hydraulic booms" for a boom-section code (A4, K4). */
function boblift_series_booms_label(string $section): string
{
    $hydraulic = (int) substr($section, 1) - 1;
    return $hydraulic === 1 ? boblift_series_text('1 hydraulic boom') : str_replace('{count}', (string) $hydraulic, boblift_series_text('{count} hydraulic booms'));
}

/**
 * A choice in words, [label, small line]: the model name's codes are explained further down, so buttons do not repeat them. A
 * section count's small line is its reach, when every configuration with that count gives the same one.
 */
function boblift_series_choice_label(string $dimension, string $value, array $members = [], string $family = ''): array
{
    // On a pickup crane the two models differ by their outriggers: that is what a buyer chooses.
    if ($dimension === 'manual' && $family === 'pickup') {
        return [boblift_series_text($value === '1' ? 'With outriggers' : 'Without outriggers'), ''];
    }
    if ($dimension === 'sections') {
        $own = array_filter($members, static fn(array $member): bool => ($member['values']['sections'] ?? '') === $value);
        $reaches = array_unique(array_map(static fn(array $member): string => (string) ($member['figures']['radius'] ?? ''), $own));
        $reach = count($reaches) === 1 ? (string) current($reaches) : '';
        // A +1 the section always has, in a series where others have none, is told here: it is not a choice of its own.
        $always_manual = !array_filter($own, static fn(array $member): bool => ($member['values']['manual'] ?? '') !== '1');
        $some_without = (bool) array_filter($members, static fn(array $member): bool => ($member['values']['manual'] ?? '') !== '1');
        return [boblift_series_booms_label($value) . ($own !== [] && $always_manual && $some_without ? ' +1' : ''), $reach !== '' ? str_replace('{value}', $reach, boblift_series_text('reach {value}')) : ''];
    }
    if ($dimension === 'winch') {
        return [boblift_series_text($value === '1' ? 'With winch' : 'No winch'), ''];
    }
    if ($dimension === 'manual') {
        return [$value === '1' ? '+1' : '—', ''];
    }
    // SQZ models differ by moment class: the name without its marine T, and the moment the data gives.
    foreach ($members as $member) {
        if (($member['values']['named'] ?? '') === $value) {
            $own = boblift_series_parse_model($value);
            // A model in a series of two makes: its boom count when the name says it (K is not confirmed yet), else its moment.
            if (($own['named'] ?? '') === '') {
                if (str_starts_with((string) $own['sections'], 'A')) {
                    return [$value, boblift_series_booms_label((string) $own['sections'])];
                }
                $moment = (string) ($member['figures']['moment'] ?? '');
                return [$value, $moment !== '' ? boblift_series_text('Max lifting moment') . " \u{2066}{$moment}\u{2069}" : ''];
            }
            return [(string) preg_replace('/T$/', '', $value), (string) ($member['figures']['moment'] ?? '')];
        }
    }
    return [$value, ''];
}

/**
 * The model name part by part under the title: three to a row, each with its meaning in plain sight. A name that decodes gets
 * the winch part either way, shown or hidden by the theme script, since the choice can change it.
 */
function boblift_series_decode_parts(string $model, string $winch = 'optional', int $english_id = 0): array
{
    $parts = boblift_model_parts($model);
    if (!$parts) {
        return ['parts' => [], 'former' => '', 'has_winch' => false];
    }
    $former = '';
    if (str_starts_with($model, 'SQZ') && $english_id > 0) {
        // The moment class is a class, not the figure, which the data gives; old customers know the crane by its former number.
        $moment = (string) (boblift_series_product_figures($english_id)['moment'] ?? '');
        $former = trim((string) get_post_meta($english_id, '_model_aliases', true));
        if ($moment !== '') {
            foreach ($parts as $index => [$code]) {
                if (ctype_digit((string) $code)) {
                    $parts[$index][1] = str_replace('{value}', $moment, boblift_series_text('Lifting moment class ({value})'));
                }
            }
        }
    }
    $has_winch = in_array('J', array_column($parts, 0), true);
    if (!$has_winch && $winch === 'optional') {
        $parts[] = ['J', boblift_localized_static_text('With winch')];
    }
    if ($winch === 'standard') {
        // A telescopic model's name has no J; the winch is standard all the same.
        $parts[] = ['✓', boblift_series_text('Winch: standard')];
    }
    return ['parts' => $parts, 'former' => $former, 'has_winch' => $has_winch];
}

/**
 * Models in the spec tables of the 2026 catalogues (assets/downloads, truck mounted pages 6-14, marine pages 4-12): the regular
 * production models. The exact name only: a catalogue with SQ4ZA3TJ says nothing about SQ4ZA3T. The CE annex pages do not count.
 */
function boblift_series_catalogue_models(): array
{
    return ['BLZ129K4', 'BLZ90K5', 'BLZ90K5TJ', 'SQ08ZA3+1', 'SQ08ZA3TJ+1', 'SQ10SA4', 'SQ10SA4T', 'SQ10ZA6', 'SQ10ZA6TJ', 'SQ12SA4', 'SQ12SA4T', 'SQ12ZA6', 'SQ12ZA6T', 'SQ14SA5', 'SQ16SA5', 'SQ16SA5T', 'SQ1ZA3+1', 'SQ1ZA3TJ+1', 'SQ20SA5', 'SQ20SA5T', 'SQ2SA3', 'SQ2SA3T', 'SQ2ZA2TJ', 'SQ2ZA4', 'SQ3.2SA4', 'SQ3.2SA4T', 'SQ3.2ZA3', 'SQ3.2ZA3TJ', 'SQ3.2ZA4', 'SQ4SA3T', 'SQ4SA4', 'SQ4ZA3', 'SQ4ZA3TJ', 'SQ4ZA4', 'SQ5SA3', 'SQ5SA4', 'SQ5SA4T', 'SQ5ZA3', 'SQ5ZA3TJ', 'SQ5ZA4', 'SQ6.3SA4', 'SQ6.3SA4T', 'SQ6.3ZA4TJ', 'SQ6.3ZA5', 'SQ8SA5', 'SQ8SA5T', 'SQ8ZA6', 'SQ8ZA6TJ', 'SQZ330', 'SQZ330T', 'SQZ430', 'SQZ430T', 'SQZ500', 'SQZ500T', 'SQZ560', 'SQZ600', 'SQZ700', 'SQZ800'];
}

/** The opening of a configuration's status line, '' for none: from the catalogue, else that this is what the series compares against. */
function boblift_series_status_text(string $model, bool $is_base): string
{
    if (in_array($model, boblift_series_catalogue_models(), true)) {
        return boblift_series_text('Standard production model');
    }
    return $is_base ? boblift_series_text('Base configuration for this series') : '';
}

/** The configuration box, with the data the theme script switches from. A series of one has nothing to choose, so no box. */
function boblift_series_render_configurator(): void
{
    $state = $GLOBALS['boblift_series_render'] ?? null;
    if (!$state || $state['capturing'] !== '') {
        return;
    }
    $t = static fn(string $text): string => boblift_series_text($text);
    $members = $state['variants'];
    $count = count($state['series']['members']);
    $dimensions = boblift_series_dimensions($members);
    $telescopic = $state['series']['winch'] === 'standard';
    if ($dimensions === []) {
        if ($telescopic) {
            echo '<p class="mt-5 text-sm text-slate-600">' . esc_html($t('Winch included on every telescopic marine crane')) . '</p>';
        }
        return;
    }
    $active = $members[0];
    foreach ($members as $member) {
        if ($member['model'] === $state['active']) {
            $active = $member;
        }
    }
    $heading_named = array_filter($members, static fn(array $member): bool => boblift_series_parse_model($member['model'])['named'] === '') ? 'Model' : 'Model (moment class)';
    $headings = ['named' => $t($heading_named), 'sections' => $t('Boom sections'), 'winch' => $t('Winch'), 'manual' => $t($state['series']['family'] === 'pickup' ? 'Outriggers' : 'Manual extension (+1)')];
    $unavailable_text = $t('Not offered with the current choice: selecting it changes the other options');
    $base = $active;
    foreach ($members as $member) {
        if ($member['model'] === $state['series']['home_model']) {
            $base = $member;
        }
    }
    $compared = str_replace('{model}', $base['model'], $t('Compared with {model}'));
    $ui = static fn(array $member): array => [
        'summary' => boblift_series_configuration_difference($member, $base),
        'status' => boblift_series_status_text($member['model'], $member['model'] === $base['model']),
        'unavailable' => boblift_series_unavailable_choices($members, $dimensions, $member['values']),
        'winch' => boblift_series_winch_difference($members, $member['values']),
    ];
    $data = [
        'default' => $state['series']['home_model'],
        'active' => $state['active'],
        'dimensions' => array_keys($dimensions),
        'unavailable' => $unavailable_text,
        'variants' => array_map(static function (array $member) use ($ui, $state): array {
            $meta = (array) ($state['meta'][$member['source']] ?? []);
            if ($member['derived']) {
                // Quote buttons, WhatsApp and mail name the version chosen, under its own address.
                $meta = boblift_series_meta_for_twin($meta, $member['source'], $member['model'], $member['url']);
            }
            return [
                'model' => $member['model'],
                'source' => $member['source'],
                'derived' => $member['derived'],
                'slug' => $member['slug'],
                'values' => $member['values'],
                'winchText' => $member['derived'] ? boblift_series_winch_text((string) $member['values']['winch']) : '',
                'weightNote' => $member['derived'] ? boblift_series_weight_note((string) $member['values']['winch']) : '',
            ] + $ui($member) + $meta;
        }, $members),
    ];
    $active_ui = $ui($active);
    ?>
    <div class="boblift-series-config mt-7 rounded-sm border border-[#D5DEE9] bg-white/70 px-4 py-5 sm:px-5 sm:py-6" data-series-config>
        <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
            <p class="text-sm font-black text-[#071f42]"><?php echo esc_html($t('Choose your configuration')); ?></p>
            <p class="text-xs text-slate-500"><?php echo esc_html($count === 1 ? '' : str_replace('{count}', (string) $count, $t('{count} configurations in this series'))); ?></p>
        </div>
        <?php foreach ($dimensions as $dimension => $values) : ?>
            <div class="mt-4">
                <p class="text-[13px] font-semibold leading-4 text-slate-500" id="boblift-cfg-<?php echo esc_attr($dimension); ?>"><?php echo esc_html($headings[$dimension]); ?></p>
                <?php $has_main_boom = $dimension === 'sections' && !array_filter($values, static fn(string $value): bool => !str_starts_with($value, 'A')); ?>
                <div class="mt-2 flex flex-wrap items-stretch <?php echo $has_main_boom ? 'boblift-boom-row gap-1.5' : 'gap-2'; ?>">
                <?php // Every SQ model has one main boom, so the hydraulic booms read as what is added to it. BLZ's K is not confirmed yet. ?>
                <?php if ($has_main_boom) : ?>
                    <span class="inline-flex min-h-[3.25rem] flex-col items-start justify-center rounded-sm border border-[#C9D6E6] bg-[#E8EDF3] px-2 py-1.5 text-start text-[#071f42]" data-boom-fixed><span class="text-[14px] font-bold leading-5"><?php echo esc_html($t('1 main boom')); ?></span><span class="text-[12px] font-normal leading-4 text-slate-600"><?php echo esc_html($t('Standard')); ?></span></span>
                    <span class="inline-flex items-center text-lg font-black text-slate-400" aria-hidden="true">+</span>
                <?php endif; ?>
                <div class="flex flex-wrap <?php echo $has_main_boom ? 'gap-1.5' : 'gap-2'; ?>" role="radiogroup" aria-labelledby="boblift-cfg-<?php echo esc_attr($dimension); ?>">
                    <?php foreach ($values as $value) :
                        [$label, $detail] = boblift_series_choice_label($dimension, $value, $members, (string) $state['series']['family']);
                        $checked = ($active['values'][$dimension] ?? '') === $value;
                        $exists = !in_array($dimension . '|' . $value, $active_ui['unavailable'], true);
                        $is_winch_on = $dimension === 'winch' && $value === '1';
                        ?>
                        <button class="boblift-model-variant relative inline-flex min-h-[3.25rem] flex-col items-start justify-center rounded-sm border <?php echo $has_main_boom ? 'px-2' : 'ps-3.5 pe-8'; ?> py-1.5 text-start transition focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-blue-100 <?php echo $checked ? 'border-[#071f42] bg-[#071f42] text-white' : 'border-[#D5DEE9] bg-white text-[#071f42] hover:border-blue-300'; ?> <?php echo $exists ? '' : 'border-dashed'; ?>" type="button" role="radio" aria-checked="<?php echo $checked ? 'true' : 'false'; ?>" tabindex="<?php echo $checked ? '0' : '-1'; ?>" data-cfg-group="<?php echo esc_attr($dimension); ?>" data-cfg-value="<?php echo esc_attr($value); ?>"<?php echo $exists ? '' : ' title="' . esc_attr($unavailable_text) . '"'; ?>>
                            <span class="<?php echo $has_main_boom ? 'text-[14px]' : 'text-sm'; ?> font-bold leading-5"><?php echo esc_html($label); ?></span>
                            <?php if ($detail !== '') : ?><span class="text-[12px] font-normal leading-4 opacity-80"><?php echo esc_html($detail); ?></span><?php endif; ?>
                            <?php if ($is_winch_on) : ?><span class="text-xs font-normal leading-4 opacity-80 [font-variant-numeric:tabular-nums]" dir="ltr" data-cfg-winch-difference<?php echo $active_ui['winch'] === '' ? ' hidden' : ''; ?>><?php echo esc_html($active_ui['winch']); ?></span><?php endif; ?>
                            <span class="boblift-variant-check" aria-hidden="true"></span>
                        </button>
                    <?php endforeach; ?>
                </div>
                </div>
                <?php // Says what the +1 on a button means. Always shown in such a series, so the box keeps its height. ?>
                <?php if ($dimension === 'sections' && array_filter($members, static fn(array $member): bool => ($member['values']['manual'] ?? '') === '1')) : ?>
                    <p class="mt-2 text-[13px] leading-4 text-slate-500"><?php echo esc_html($t('+1 = one added manual extension')); ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if ($telescopic) : ?>
            <p class="mt-3 text-[13px] text-slate-600"><?php echo esc_html($t('Winch included on every telescopic marine crane')); ?></p>
        <?php endif; ?>
        <?php if (boblift_series_all_have($members, 'manual', '1')) : ?>
            <p class="mt-3 text-[13px] text-slate-600"><?php echo esc_html($t('+1 manual extension on every configuration')); ?></p>
        <?php endif; ?>
        <?php // Always as tall as the longest of these lines, so the page below stays put when the choice changes (theme.js). ?>
        <div class="flow-root" data-cfg-summary-box>
        <p class="mt-3.5 border-t border-[#E6EDF5] pt-3 text-[13px] leading-5 text-slate-600" aria-live="polite" data-cfg-summary-line<?php echo $active_ui['summary'] === '' && $active_ui['status'] === '' ? ' hidden' : ''; ?>><span class="font-semibold text-[#071f42]" data-cfg-status><?php echo esc_html($active_ui['status']); ?></span><span class="mx-1"<?php echo $active_ui['summary'] !== '' && $active_ui['status'] !== '' ? '' : ' hidden'; ?> data-cfg-status-gap>·</span><span<?php echo $active_ui['summary'] === '' ? ' hidden' : ''; ?> data-cfg-compare-text><span class="font-black text-[#071f42]"><?php echo esc_html($compared); ?>:</span> <span data-cfg-summary><?php echo esc_html($active_ui['summary']); ?></span></span></p>
        <p class="mt-1.5 text-[13px] leading-5 text-slate-600" data-cfg-weight-note<?php echo empty($active['derived']) ? ' hidden' : ''; ?>><?php echo !empty($active['derived']) ? esc_html($t($active['values']['winch'] === '1' ? 'Winch version: self weight confirmed with your quote' : 'No-winch version: self weight confirmed with your quote')) : ''; ?></p>
        </div>
        <script type="application/json" data-series-data><?php echo wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG); ?></script>
    </div>
    <?php
}

/** What sets a configuration apart in the comparison: "A4 · With winch", with +1 only where the series differs on it. */
function boblift_series_compare_label(array $values, array $dimensions, string $family = ''): string
{
    if ($family === 'pickup' && isset($dimensions['manual'])) {
        return boblift_series_text($values['manual'] === '1' ? 'With outriggers' : 'Without outriggers');
    }
    return implode(' · ', array_filter([
        isset($dimensions['sections']) ? (string) $values['sections'] : '',
        isset($dimensions['winch']) ? boblift_series_text($values['winch'] === '1' ? 'With winch' : 'No winch') : '',
        isset($dimensions['manual']) && $values['manual'] === '1' ? '+1' : '',
    ]));
}

/** The figures worth comparing: those at least two configurations give. [[label, key], ...]. */
function boblift_series_compare_columns(array $members): array
{
    $columns = [];
    foreach (['moment' => 'Max lifting moment', 'radius' => 'Max reach', 'height' => 'Lifting height', 'weight' => 'Self weight'] as $key => $label) {
        $values = array_filter(array_map(static fn(array $member) => $member['figures'][$key] ?? null, $members), static fn($value): bool => $value !== null);
        // A moment all configurations share tells nothing here.
        if (count($values) >= 2 && ($key !== 'moment' || count(array_unique($values)) > 1)) {
            $columns[] = [$label, $key];
        }
    }
    return $columns;
}

/**
 * The configurations compared at the top of the specifications: each row picks that configuration. Printed once, in the shown
 * configuration's block; the theme script moves it along with the choice.
 */
function boblift_series_render_compare(string $model): void
{
    $state = $GLOBALS['boblift_series_render'] ?? null;
    if (!$state || $state['active_source'] !== $model || count((array) $state['variants']) < 2) {
        return;
    }
    boblift_specs_render_choices($state);
}

/**
 * The quote details (inquiry item, WhatsApp, mail) of the configuration a series page opens on, as the theme script would set
 * them, so the page is right before any script runs. Empty outside the series page's own run.
 */
function boblift_series_active_meta(): array
{
    $state = $GLOBALS['boblift_series_render'] ?? null;
    if (!$state || $state['capturing'] !== '') {
        return [];
    }
    $meta = (array) ($state['meta'][$state['active_source']] ?? []);
    if ($meta === [] || $state['active'] === $state['active_source']) {
        return $meta;
    }
    $url = '';
    foreach ((array) $state['variants'] as $variant) {
        if (($variant['model'] ?? '') === $state['active']) {
            $url = (string) ($variant['url'] ?? '');
        }
    }
    return boblift_series_meta_for_twin($meta, (string) $state['active_source'], (string) $state['active'], $url);
}

/** Quote details of a configuration, rewritten for its other winch version. */
function boblift_series_meta_for_twin(array $meta, string $source, string $twin, string $url): array
{
    if (isset($meta['inquiry'])) {
        $meta['inquiry']['title'] = str_replace($source, $twin, (string) $meta['inquiry']['title']);
        $meta['inquiry']['detailUrl'] = $url;
    }
    foreach (['whatsapp', 'mail'] as $key) {
        if (isset($meta[$key])) {
            // The model appears encoded once in the words and twice in the address inside them.
            // One pass: without special characters both forms are the same text, and replacing twice would double the J.
            $meta[$key] = strtr((string) $meta[$key], [rawurlencode($source) => rawurlencode($twin), rawurlencode(rawurlencode($source)) => rawurlencode(rawurlencode($twin))]);
        }
    }
    return $meta;
}

/** The label of the row in this language's spec table that the English table calls $english (boblift_series_spec_label_rows()). */
function boblift_series_spec_label(int $english_id, array $specs, string $english): string
{
    $source = boblift_decode_meta(get_post_meta($english_id, '_specs', true), []);
    return boblift_series_spec_label_rows(is_array($source) ? $source : [], $specs, $english, static fn(array $local, array $source): bool => function_exists('boblift_spec_rows_aligned') && boblift_spec_rows_aligned($local, $source));
}

/**
 * The row is found by its key where both tables carry keys, else at the English row's position when the tables line up
 * ($aligned: boblift_spec_rows_aligned()). A table that does not line up gives '', so no row is changed or hidden.
 */
function boblift_series_spec_label_rows(array $source, array $specs, string $english, callable $aligned): string
{
    $source = array_values(array_filter($source, 'is_array'));
    $specs = array_values(array_filter($specs, 'is_array'));
    foreach ($source as $index => $row) {
        if (($row['label'] ?? '') !== $english) {
            continue;
        }
        $key = (string) ($row['key'] ?? '');
        if ($key !== '') {
            foreach ($specs as $local) {
                if ((string) ($local['key'] ?? '') === $key) {
                    return (string) ($local['label'] ?? '');
                }
            }
        }
        return $aligned($specs, $source) ? (string) ($specs[$index]['label'] ?? '') : '';
    }
    return '';
}

/** ProductGroup with one Product per configuration. No offers or prices: the site publishes none. */
function boblift_series_schema(): array
{
    $state = $GLOBALS['boblift_series_render'] ?? null;
    if (!$state) {
        return [];
    }
    $series = $state['series'];
    $t = static fn(string $text): string => boblift_series_text($text);
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
        if ($values['winch'] !== '' && $series['winch'] !== 'none') {
            $add($t('Winch'), $values['winch'] === '1' ? $t('With winch') : $t('No winch'));
        }
        if ($series['family'] === 'pickup') {
            $add($t('Outriggers'), $t($values['manual'] === '1' ? 'With outriggers' : 'Without outriggers'));
        } elseif ($values['manual'] === '1') {
            $add($t('Manual extension (+1)'), '+1');
        }
        $figures = boblift_series_product_figures((int) $member['en_id']);
        $add($t('Max lifting capacity'), $figures['capacity'] !== null ? boblift_series_format_kg($figures['capacity']) : '');
        $add($t('Max lifting moment'), (string) $figures['moment']);
        $add($t('Max reach'), (string) $figures['radius']);
        $add($t('Self weight'), $figures['weight'] !== null ? boblift_series_format_kg($figures['weight']) : '');
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
        'name' => (string) ($home_meta['name'] ?? '') . ' (' . boblift_series_label($series) . ')',
        'productGroupID' => $series['code'],
        'url' => $series['url'],
        'image' => (string) ($home_meta['image'] ?? ''),
        'description' => trim((string) ($home_meta['description'] ?? '') . ' ' . boblift_series_winch_sentence((string) $series['winch'])),
        'additionalProperty' => $series['winch'] === 'none' ? [] : [['@type' => 'PropertyValue', 'name' => $t('Winch'), 'value' => $t($series['winch'] === 'standard' ? 'Standard' : 'Optional')]],
        'brand' => $brand,
        'manufacturer' => $manufacturer,
        'hasVariant' => $variants,
    ]);
}
