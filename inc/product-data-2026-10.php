<?php
/**
 * One-off data changes that go with the product page rework (2026-10-22), applied once per site on init so the staging
 * site gets what was done by hand on the local copy:
 * - the figures read off the technical department's drawings (inc/drawing-data.json) go onto each English product as
 *   _boblift_drawing_data (see boblift_product_drawing_data());
 * - the marine cranes left out of the current brochure are published again as previous models in every language, and
 *   the three that are in the technical drawings stay current;
 * - the drawings the technical department corrected (SQZ800 on 2026-10-21, SQ10SA4(T) and SQ3.2SA4(T) on 2026-10-22)
 *   replace the old ones, and the SQ8ZA6 moment is set to the confirmed 16.7 t·m.
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

/**
 * Publishes the drafts among the product and its translations; true when each write succeeded. Republishing these
 * exact models was approved by the site owner on 2026-10-21 (thread "起重机站 Products 页优化"). It is limited further to
 * the products the brochure clean-up retired (boblift_retired_marine_crane_slugs(), English slug) and to drafts, the
 * status that clean-up set: private, pending or scheduled posts are an editor's choice and stay as they are.
 */
function boblift_product_data_publish_all(int $english_id): bool
{
    $retired = function_exists('boblift_retired_marine_crane_slugs') ? boblift_retired_marine_crane_slugs() : [];
    if (!in_array((string) get_post_field('post_name', $english_id), $retired, true)) {
        return true;
    }
    $ids = function_exists('pll_get_post_translations') ? array_values(pll_get_post_translations($english_id)) : [];
    $ok = true;
    foreach (array_unique(array_map('intval', array_merge([$english_id], $ids))) as $id) {
        if (get_post_status($id) === 'draft') {
            $result = wp_update_post(['ID' => $id, 'post_status' => 'publish'], true);
            $ok = $ok && !is_wp_error($result) && $result;
        }
    }
    return $ok;
}

/** Drawings replaced by corrected versions from the technical department (assets/images/drawings/boblift-drawings-<model>.webp). */
function boblift_product_data_corrected_drawings(): array
{
    return ['SQZ800', 'SQ10SA4', 'SQ10SA4T', 'SQ3.2SA4', 'SQ3.2SA4T'];
}

/**
 * Adds the corrected drawing as a new attachment and points the model's drawing at it, in every language. The old upload
 * is kept. Only a drawing that is still the imported one (file "boblift-drawings-<model>.webp", or its "-scaled"
 * copy) is swapped, so a drawing an editor has since replaced or reordered is left alone. True when done or not needed.
 */
function boblift_product_data_replace_drawing(string $model): bool
{
    $source = get_template_directory() . '/assets/images/drawings/boblift-drawings-' . $model . '.webp';
    $english = boblift_product_data_english_id($model);
    $drawings = $english > 0 ? boblift_decode_meta(get_post_meta($english, '_drawings', true), []) : [];
    $old = (int) ($drawings[0]['id'] ?? 0);
    if ($old <= 0) {
        return true;
    }
    $old_name = wp_basename((string) get_post_meta($old, '_wp_attached_file', true));
    $imported = ['boblift-drawings-' . $model . '.webp', 'boblift-drawings-' . $model . '-scaled.webp'];
    if (!in_array($old_name, $imported, true)) {
        return true;
    }
    if (!is_readable($source)) {
        return false;
    }
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $tmp = wp_tempnam($source);
    if (!$tmp || !copy($source, $tmp)) {
        return false;
    }
    $new = media_handle_sideload(['name' => 'boblift-drawings-' . $model . '-2026-10.webp', 'tmp_name' => $tmp], 0, (string) get_the_title($old));
    if (is_wp_error($new)) {
        @unlink($tmp);
        return false;
    }
    $ids = function_exists('pll_get_post_translations') ? array_values(pll_get_post_translations($english)) : [];
    $ok = true;
    foreach (array_unique(array_map('intval', array_merge([$english], $ids))) as $id) {
        $list = boblift_decode_meta(get_post_meta($id, '_drawings', true), []);
        if ((int) ($list[0]['id'] ?? 0) !== $old) {
            continue;
        }
        $list[0]['id'] = (int) $new;
        $json = (string) wp_json_encode($list, JSON_UNESCAPED_UNICODE);
        update_post_meta($id, '_drawings', wp_slash($json));
        $ok = (int) (boblift_decode_meta(get_post_meta($id, '_drawings', true), [])[0]['id'] ?? 0) === (int) $new && $ok;
    }
    return $ok;
}

/**
 * SQ8ZA6 and SQ8ZA6TJ share one load chart; the technical department confirmed 16.7 t·m for both (2026-10-22), so the
 * SQ8ZA6 moment row changes from 16 to 16.7 in every language. True when every row reads 16.7 afterwards.
 */
function boblift_product_data_fix_sq8za6_moment(): bool
{
    $english = boblift_product_data_english_id('SQ8ZA6');
    if ($english <= 0) {
        return true;
    }
    $english_rows = boblift_decode_meta(get_post_meta($english, '_specs', true), []);
    $row_index = null;
    foreach ((array) $english_rows as $index => $row) {
        if (is_array($row) && trim((string) ($row['label'] ?? '')) === 'Max Lifting Moment') {
            $row_index = $index;
        }
    }
    if ($row_index === null) {
        return true;
    }
    $ids = function_exists('pll_get_post_translations') ? array_values(pll_get_post_translations($english)) : [];
    $ok = true;
    foreach (array_unique(array_map('intval', array_merge([$english], $ids))) as $id) {
        $rows = boblift_decode_meta(get_post_meta($id, '_specs', true), []);
        if (!isset($rows[$row_index]['value']) || count($rows) !== count($english_rows)) {
            continue;
        }
        // The leading figure, with a comma or a point ("16", "16.0", "16,0"); only an old 16 is changed, any other figure
        // is an editor's and stays, so it cannot keep the migration retrying.
        $value = trim((string) $rows[$row_index]['value']);
        if (!preg_match('/^(\d+(?:[.,]\d+)?)/u', $value, $figure) || (float) str_replace(',', '.', $figure[1]) !== 16.0) {
            continue;
        }
        $rows[$row_index]['value'] = '16.7' . substr($value, strlen($figure[1]));
        update_post_meta($id, '_specs', wp_slash((string) wp_json_encode($rows, JSON_UNESCAPED_UNICODE)));
        $check = boblift_decode_meta(get_post_meta($id, '_specs', true), []);
        $ok = (string) ($check[$row_index]['value'] ?? '') === $rows[$row_index]['value'] && $ok;
    }
    return $ok;
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
    foreach (boblift_product_data_corrected_drawings() as $model) {
        $ok = boblift_product_data_replace_drawing($model) && $ok;
    }
    $ok = boblift_product_data_fix_sq8za6_moment() && $ok;
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
