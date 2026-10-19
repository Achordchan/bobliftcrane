<?php
/**
 * Marine cranes follow the current marine crane brochure (14 knuckle boom and 10 telescopic boom models). The older
 * models imported on 2026-10-09 were never on the old site (its sitemap has no model-code product URLs), so they go
 * back to draft in every language, once. Drafts can be published again from the product screen.
 */
function boblift_retired_marine_crane_slugs(): array
{
    return [
        '0-8-ton-knuckle-boom-marine-crane-sq08za3t1',
        '1-6-ton-knuckle-boom-marine-crane-sq1za3t1',
        '2-ton-knuckle-boom-marine-crane-sq2za4t',
        '3-2-ton-knuckle-boom-marine-crane-sq3-2za3t',
        '4-ton-knuckle-boom-marine-crane-sq4za3t',
        '4-ton-knuckle-boom-marine-crane-sq4za4t',
        '4-6-ton-knuckle-boom-marine-crane-blz90k4tj',
        '5-ton-knuckle-boom-marine-crane-sq5za3t',
        '5-ton-knuckle-boom-marine-crane-sq5za4t',
        '6-3-ton-knuckle-boom-marine-crane-sq6-3za4t',
        '6-3-ton-knuckle-boom-marine-crane-sq6-3za5t',
        '8-ton-knuckle-boom-marine-crane-sq8za4t',
        '8-ton-knuckle-boom-marine-crane-sq8za4tj',
        '8-ton-knuckle-boom-marine-crane-sq8za5tj',
        '10-ton-knuckle-boom-marine-crane-sq10za5t',
        '10-ton-knuckle-boom-marine-crane-sq10za5tj',
        '12-ton-knuckle-boom-marine-crane-sq12za4t',
        '12-ton-knuckle-boom-marine-crane-sq12za4tj',
        '12-ton-knuckle-boom-marine-crane-sq12za5t',
        '12-ton-knuckle-boom-marine-crane-sq12za5tj',
        '16-ton-knuckle-boom-marine-crane-sqz350t',
        '3-2-ton-telescopic-boom-marine-crane-sq3-2sa3t',
        '5-ton-telescopic-boom-marine-crane-sq5sa3t',
        '6-3-ton-telescopic-boom-marine-crane-sq6-3sa3t',
        '8-ton-telescopic-boom-marine-crane-sq8sa4t',
        '10-ton-telescopic-boom-marine-crane-sq10sa5t',
        '12-ton-telescopic-boom-marine-crane-sq12sa5t',
    ];
}

function boblift_retire_old_marine_crane_models(): void
{
    if (get_option('boblift_marine_brochure_models_v1') !== false || !post_type_exists('product')) {
        return;
    }
    // add_option() inserts a unique row, so only one request holds the lock. A lock left by a request that died is
    // released after ten minutes and the run starts again.
    $lock = 'boblift_marine_brochure_models_v1_lock';
    if (!add_option($lock, time(), '', false)) {
        if (time() - (int) get_option($lock) < 600) {
            return;
        }
        update_option($lock, time(), false);
    }

    // Any status, so a retry still finds the translations of a product that is already a draft.
    $ids = get_posts([
        'post_type' => 'product',
        'post_status' => ['publish', 'draft', 'pending', 'private', 'future'],
        'post_name__in' => boblift_retired_marine_crane_slugs(),
        'numberposts' => -1,
        'fields' => 'ids',
        'suppress_filters' => true,
        'lang' => '',
    ]);
    // Translations usually share the English slug, but some may keep a numbered or language-suffixed slug, so
    // linked translations are added through Polylang too.
    if (function_exists('pll_get_post_translations')) {
        foreach ($ids as $id) {
            $ids = array_merge($ids, array_values(pll_get_post_translations((int) $id)));
        }
    }
    $ids = array_unique(array_map('intval', $ids));

    $failed = 0;
    $drafted = 0;
    foreach ($ids as $id) {
        if (get_post_type($id) !== 'product' || get_post_status($id) !== 'publish') {
            continue;
        }
        $drafted++;
        $result = wp_update_post(['ID' => $id, 'post_status' => 'draft'], true);
        if (is_wp_error($result) || !$result) {
            $failed++;
        }
    }
    // Only a clean run is recorded; after a failure the next request tries again.
    if ($failed === 0) {
        update_option('boblift_marine_brochure_models_v1', $drafted, false);
    }
    delete_option($lock);
}
add_action('init', 'boblift_retire_old_marine_crane_models', 40);
