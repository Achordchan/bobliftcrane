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
 * copy) is swapped, so a drawing an editor has since replaced or reordered is left alone. True when done or not needed,
 * null while the product does not exist yet.
 */
function boblift_product_data_replace_drawing(string $model): ?bool
{
    $english = boblift_product_data_english_id($model);
    // No product yet (not imported): null keeps the step open.
    if ($english <= 0) {
        return null;
    }
    // The old and new attachment are recorded as soon as the new one exists, so a run that stops half way resumes the
    // remaining translations from this record instead of from the English drawing, which by then is the new one.
    $map_option = 'boblift_product_data_2026_10_drawings';
    $map = (array) get_option($map_option, []);
    [$old, $new] = array_map('intval', (array) ($map[$model] ?? [0, 0])) + [0, 0];
    if ($new <= 0 || !get_post($new)) {
        $drawings = boblift_decode_meta(get_post_meta($english, '_drawings', true), []);
        $old = (int) ($drawings[0]['id'] ?? 0);
        $imported = ['boblift-drawings-' . $model . '.webp', 'boblift-drawings-' . $model . '-scaled.webp'];
        if ($old <= 0 || !in_array(wp_basename((string) get_post_meta($old, '_wp_attached_file', true)), $imported, true)) {
            return true;
        }
        $source = get_template_directory() . '/assets/images/drawings/boblift-drawings-' . $model . '.webp';
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
        $map[$model] = [$old, (int) $new];
        update_option($map_option, $map, false);
    }
    $ids = function_exists('pll_get_post_translations') ? array_values(pll_get_post_translations($english)) : [];
    $ok = true;
    foreach (array_unique(array_map('intval', array_merge([$english], $ids))) as $id) {
        $list = boblift_decode_meta(get_post_meta($id, '_drawings', true), []);
        // Only entries still on the imported drawing move; one an editor changed in the meantime stays.
        if ((int) ($list[0]['id'] ?? 0) !== $old) {
            continue;
        }
        $list[0]['id'] = (int) $new;
        update_post_meta($id, '_drawings', wp_slash((string) wp_json_encode($list, JSON_UNESCAPED_UNICODE)));
        $ok = (int) (boblift_decode_meta(get_post_meta($id, '_drawings', true), [])[0]['id'] ?? 0) === (int) $new && $ok;
    }
    return $ok;
}

/**
 * SQ8ZA6 and SQ8ZA6TJ share one load chart; the technical department confirmed 16.7 t·m for both (2026-10-22), so the
 * SQ8ZA6 moment row changes from 16 to 16.7 in every language. True when every row reads 16.7 afterwards.
 */
function boblift_product_data_fix_sq8za6_moment(): ?bool
{
    $english = boblift_product_data_english_id('SQ8ZA6');
    // No product yet (not imported): null keeps the step open.
    if ($english <= 0) {
        return null;
    }
    $english_rows = (array) boblift_decode_meta(get_post_meta($english, '_specs', true), []);
    $row_index = null;
    foreach ($english_rows as $index => $row) {
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
        $rows = (array) boblift_decode_meta(get_post_meta($id, '_specs', true), []);
        // Row identity: the table must follow the English one row for row (every other row's first figure equal to the
        // English row's, boblift_spec_rows_aligned()), so the row at the English moment's position is the moment. Its own
        // value must then be a moment of 16: "16", "16.0", "16,0" with no unit or a t·m unit ("t·m", "Ton.m", "ton.m",
        // "tm"). Anything else (a reordered table, "16 kW", an editor's other figure) is left alone.
        $others = static fn(array $table): array => array_values(array_diff_key($table, [$row_index => true]));
        if (!boblift_spec_rows_aligned($others($rows), $others($english_rows)) || !preg_match('/^16(?:[.,]0+)?(\s*(?:t(?:on)?\s*[·.]?\s*m)?)$/iu', trim((string) ($rows[$row_index]['value'] ?? '')), $unit)) {
            continue;
        }
        $rows[$row_index]['value'] = '16.7' . $unit[1];
        update_post_meta($id, '_specs', wp_slash((string) wp_json_encode($rows, JSON_UNESCAPED_UNICODE)));
        $check = (array) boblift_decode_meta(get_post_meta($id, '_specs', true), []);
        $ok = (string) ($check[$row_index]['value'] ?? '') === $rows[$row_index]['value'] && $ok;
    }
    return $ok;
}

function boblift_apply_product_data_2026_10(): void
{
    $flag = 'boblift_product_data_2026_10_v1';
    // After the marine brochure clean-up has finished, so that it cannot draft again what this republishes.
    if (get_option($flag) !== false || get_transient($flag . '_wait') || get_option('boblift_marine_brochure_models_v1') === false || !post_type_exists('product') || !function_exists('boblift_save_product_lifecycle')) {
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
    // Each step is checkpointed once it succeeds, so a retry (a product not imported yet, a failed write) only does
    // what is still open and never touches a product again after it was done; an editor's later change stays.
    $done_option = $flag . '_done';
    $done = (array) get_option($done_option, []);
    $missing = 0;
    $step = static function (string $key, callable $run) use (&$done, &$ok, &$missing): void {
        if (!empty($done[$key])) {
            return;
        }
        $result = $run();
        if ($result === null) {
            $missing++;
            return;
        }
        if ($result) {
            $done[$key] = 1;
        } else {
            $ok = false;
        }
    };
    foreach ((array) $data as $model => $figures) {
        $step('figures:' . $model, static function () use ($model, $figures): ?bool {
            $id = boblift_product_data_english_id((string) $model);
            if ($id <= 0) {
                return null;
            }
            if (!is_array($figures)) {
                return true;
            }
            $json = (string) wp_json_encode($figures);
            update_post_meta($id, '_boblift_drawing_data', wp_slash($json));
            // update_post_meta() also returns false when the value is unchanged, so the stored value is checked instead.
            return get_post_meta($id, '_boblift_drawing_data', true) === $json;
        });
    }
    foreach ([boblift_product_data_marine_previous_models(), boblift_product_data_marine_current_models()] as $group => $models) {
        foreach ($models as $model) {
            $step('lifecycle:' . $model, static function () use ($model, $group): ?bool {
                $id = boblift_product_data_english_id($model);
                if ($id <= 0) {
                    return null;
                }
                boblift_save_product_lifecycle($id, $group === 0 ? 'legacy' : '');
                return boblift_product_data_publish_all($id);
            });
        }
    }
    foreach (boblift_product_data_corrected_drawings() as $model) {
        $step('drawing:' . $model, static fn(): ?bool => boblift_product_data_replace_drawing($model));
    }
    $step('moment:SQ8ZA6', static fn(): ?bool => boblift_product_data_fix_sq8za6_moment());
    update_option($done_option, $done, false);
    if (function_exists('boblift_legacy_product_ids')) {
        boblift_legacy_product_ids(true);
    }
    // Done when every step is checkpointed. Products not imported yet (a fresh site, where init runs before the importer)
    // keep their steps open; such a run waits an hour before looking again, and only those open steps run then.
    if ($ok && $missing === 0) {
        update_option($flag, time(), false);
    } elseif ($missing > 0) {
        set_transient($flag . '_wait', 1, HOUR_IN_SECONDS);
    }
    $wpdb->delete($wpdb->options, ['option_name' => $lock, 'option_value' => $token]);
    wp_cache_delete($lock, 'options');
}
add_action('init', 'boblift_apply_product_data_2026_10', 41);
