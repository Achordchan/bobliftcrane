<?php
/**
 * One-off data changes that go with the product page rework (2026-10-22), applied once per site on init so the staging
 * site gets what was done by hand on the local copy:
 * - the figures read off the technical department's drawings (inc/drawing-data.json) go onto each English product as
 *   _boblift_drawing_data (see boblift_product_drawing_data());
 * - the marine cranes left out of the current brochure are published again as previous models in every language, and
 *   the three that are in the technical drawings stay current;
 * - the SQZ800 drawing is replaced by the corrected lifting chart the technical department sent on 2026-10-21.
 */
function boblift_product_data_marine_previous_models(): array
{
    return ['SQ12SA5T', 'SQ10SA5T', 'SQ8SA4T', 'SQ6.3SA3T', 'SQ5SA3T', 'SQ3.2SA3T', 'SQZ350T', 'SQ12ZA5TJ', 'SQ12ZA5T', 'SQ12ZA4TJ', 'SQ12ZA4T', 'SQ10ZA5TJ', 'SQ10ZA5T', 'SQ8ZA5TJ', 'SQ8ZA4TJ', 'SQ8ZA4T', 'SQ6.3ZA4T', 'SQ5ZA4T', 'SQ5ZA3T', 'SQ4ZA4T', 'SQ3.2ZA3T', 'SQ2ZA4T', 'SQ1ZA3T+1', 'SQ08ZA3T+1'];
}

function boblift_product_data_marine_current_models(): array
{
    return ['SQ6.3ZA5T', 'BLZ90K4TJ', 'SQ4ZA3T'];
}

/** The English product with this model code, in any status; 0 when there is none. */
function boblift_product_data_english_id(string $model): int
{
    $ids = get_posts([
        'post_type' => 'product',
        'post_status' => ['publish', 'draft', 'pending', 'private'],
        'meta_key' => '_model',
        'meta_value' => $model,
        'numberposts' => -1,
        'fields' => 'ids',
        'suppress_filters' => true,
        'lang' => '',
    ]);
    foreach ($ids as $id) {
        if (!function_exists('pll_get_post_language') || pll_get_post_language((int) $id) === 'en') {
            return (int) $id;
        }
    }
    return 0;
}

/** Publishes the product and all its translations; true when every one is published. */
function boblift_product_data_publish_all(int $english_id): bool
{
    $ids = function_exists('pll_get_post_translations') ? array_values(pll_get_post_translations($english_id)) : [];
    $ok = true;
    foreach (array_unique(array_map('intval', array_merge([$english_id], $ids))) as $id) {
        if (get_post_status($id) !== 'publish') {
            $result = wp_update_post(['ID' => $id, 'post_status' => 'publish'], true);
            $ok = $ok && !is_wp_error($result) && $result;
        }
    }
    return $ok;
}

/** Writes the new SQZ800 drawing over its attachment and rebuilds the sizes; true when done or nothing to do. */
function boblift_product_data_replace_sqz800_drawing(): bool
{
    $source = get_template_directory() . '/assets/images/drawings/boblift-drawings-SQZ800.webp';
    global $wpdb;
    $attachment = (int) $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s ORDER BY post_id LIMIT 1", '%boblift-drawings-SQZ800.webp'));
    $file = $attachment > 0 ? get_attached_file($attachment) : '';
    if (!$file || !is_readable($source)) {
        return true;
    }
    if (!copy($source, $file)) {
        return false;
    }
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $meta = wp_generate_attachment_metadata($attachment, $file);
    if (is_array($meta) && $meta) {
        wp_update_attachment_metadata($attachment, $meta);
    }
    return true;
}

function boblift_apply_product_data_2026_10(): void
{
    $flag = 'boblift_product_data_2026_10_v1';
    // After the marine brochure clean-up has finished, so that it cannot draft again what this republishes.
    if (get_option($flag) !== false || get_option('boblift_marine_brochure_models_v1') === false || !post_type_exists('product') || !function_exists('boblift_save_product_lifecycle')) {
        return;
    }
    // Same lock as the marine brochure clean-up: one request at a time, taken over after ten minutes.
    global $wpdb;
    $lock = $flag . '_lock';
    $token = time() . '|' . wp_generate_password(12, false);
    if (!add_option($lock, $token, '', false)) {
        $held = (string) $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $lock));
        if (time() - (int) $held < 600) {
            return;
        }
        $taken = $wpdb->update($wpdb->options, ['option_value' => $token], ['option_name' => $lock, 'option_value' => $held]);
        wp_cache_delete($lock, 'options');
        if ($taken !== 1) {
            return;
        }
    }

    $file = get_template_directory() . '/inc/drawing-data.json';
    $data = is_readable($file) ? json_decode((string) file_get_contents($file), true) : null;
    // A missing or broken data file is a failed run, not an empty one, so the next request tries again.
    $ok = is_array($data) && $data !== [];
    foreach ((array) $data as $model => $figures) {
        $id = boblift_product_data_english_id((string) $model);
        if ($id <= 0 || !is_array($figures)) {
            continue;
        }
        $json = (string) wp_json_encode($figures);
        update_post_meta($id, '_boblift_drawing_data', wp_slash($json));
        // update_post_meta() also returns false when the value is unchanged, so the stored value is checked instead.
        $ok = get_post_meta($id, '_boblift_drawing_data', true) === $json && $ok;
    }
    foreach (boblift_product_data_marine_previous_models() as $model) {
        $id = boblift_product_data_english_id($model);
        if ($id > 0) {
            boblift_save_product_lifecycle($id, 'legacy');
            $ok = boblift_product_data_publish_all($id) && $ok;
        }
    }
    foreach (boblift_product_data_marine_current_models() as $model) {
        $id = boblift_product_data_english_id($model);
        if ($id > 0) {
            boblift_save_product_lifecycle($id, '');
            $ok = boblift_product_data_publish_all($id) && $ok;
        }
    }
    $ok = boblift_product_data_replace_sqz800_drawing() && $ok;
    if (function_exists('boblift_legacy_product_ids')) {
        boblift_legacy_product_ids(true);
    }
    // Only a clean run is recorded; after a failure the next request tries again.
    if ($ok) {
        update_option($flag, time(), false);
    }
    $wpdb->delete($wpdb->options, ['option_name' => $lock, 'option_value' => $token]);
    wp_cache_delete($lock, 'options');
}
add_action('init', 'boblift_apply_product_data_2026_10', 41);
