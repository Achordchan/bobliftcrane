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
    // Translations share the English slug, so one query by slug finds every language's copy.
    $ids = get_posts([
        'post_type' => 'product',
        'post_status' => 'publish',
        'post_name__in' => boblift_retired_marine_crane_slugs(),
        'numberposts' => -1,
        'fields' => 'ids',
        'suppress_filters' => true,
        'lang' => '',
    ]);
    // Saved before the updates, so a slow request cannot start the same run twice.
    update_option('boblift_marine_brochure_models_v1', count($ids), false);
    foreach ($ids as $id) {
        wp_update_post(['ID' => (int) $id, 'post_status' => 'draft']);
    }
}
add_action('init', 'boblift_retire_old_marine_crane_models', 40);
