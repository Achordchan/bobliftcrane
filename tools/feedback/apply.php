<?php
/**
 * Import customer feedback (photos and videos by country and model) from plan.json and media/.
 *
 * Usage (from the WordPress root): wp eval-file <path>/apply.php [dry-run]
 * Build first with build.py. Each feedback group is keyed by its source folder and each file by its path,
 * so the script can run again safely and only adds what is missing: an existing entry keeps its slug, country, note,
 * model and every photo an editor added; only planned media that is not attached yet is appended.
 */

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$dir = __DIR__;
$dry = in_array('dry-run', $args ?? [], true);
$plan = json_decode((string) file_get_contents($dir . '/plan.json'), true);

$import = static function (string $file, string $title) use ($dir): int {
    $existing = get_posts(['post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_boblift_feedback_file', 'meta_value' => $file, 'posts_per_page' => 1, 'fields' => 'ids']);
    if ($existing) {
        return (int) $existing[0];
    }
    $path = $dir . '/media/' . $file;
    if (!is_file($path)) {
        return 0;
    }
    $tmp = wp_tempnam(basename($file));
    copy($path, $tmp);
    $id = media_handle_sideload(['name' => 'feedback-' . basename($file), 'tmp_name' => $tmp], 0, $title);
    if (is_wp_error($id)) {
        WP_CLI::warning($file . ': ' . $id->get_error_message());
        return 0;
    }
    update_post_meta($id, '_boblift_feedback_file', $file);
    update_post_meta($id, '_wp_attachment_image_alt', $title);
    return (int) $id;
};

$created = 0;
$media_count = 0;
$missing = [];
foreach ($plan['items'] as $order => $item) {
    $existing = get_posts(['post_type' => 'feedback', 'post_status' => 'any', 'meta_key' => '_boblift_feedback_source', 'meta_value' => $item['source'], 'posts_per_page' => 1, 'fields' => 'ids']);
    $title = $item['model'] . ' · ' . $item['country']['en'];
    if ($dry) {
        $created += $existing ? 0 : 1;
        $media_count += count($item['media']);
        continue;
    }
    $post_id = $existing ? (int) $existing[0] : wp_insert_post(['post_type' => 'feedback', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $item['slug'], 'menu_order' => $order], true);
    if (is_wp_error($post_id)) {
        $missing[] = $item['source'] . ' (post)';
        continue;
    }
    $created += $existing ? 0 : 1;
    // The title's "·" once leaked into the slug as %c2%b7; the detail page URL uses the plan's clean slug.
    if (!$existing && get_post_field('post_name', $post_id) !== $item['slug']) {
        wp_update_post(['ID' => $post_id, 'post_name' => $item['slug']]);
    }
    $ids = [];
    foreach ($item['media'] as $media) {
        $id = $import($media['file'], $title);
        if (!$id) {
            $missing[] = $media['file'];
            continue;
        }
        if ($media['type'] === 'video' && !empty($media['poster'])) {
            $poster = $import($media['poster'], $title . ' (video poster)');
            if ($poster) {
                update_post_meta($id, '_thumbnail_id', $poster);
            }
        }
        $ids[] = $id;
        $media_count++;
    }
    update_post_meta($post_id, '_boblift_feedback_source', $item['source']);
    if ($existing) {
        // Keep what the editor maintains; just attach planned media that is still missing.
        $current = array_values(array_filter(array_map('intval', explode(',', (string) get_post_meta($post_id, '_feedback_media', true)))));
        update_post_meta($post_id, '_feedback_media', implode(',', array_values(array_unique(array_merge($current, $ids)))));
        continue;
    }
    update_post_meta($post_id, '_feedback_country', wp_slash(wp_json_encode($item['country'], JSON_UNESCAPED_UNICODE)));
    update_post_meta($post_id, '_feedback_note', $item['note'] ? wp_slash(wp_json_encode($item['note'], JSON_UNESCAPED_UNICODE)) : '');
    update_post_meta($post_id, '_feedback_model', $item['model']);
    update_post_meta($post_id, '_feedback_media', implode(',', $ids));
}

printf("%s: %d groups (%d new), %d media, missing %d\n", $dry ? 'DRY RUN' : 'DONE', count($plan['items']), $created, $media_count, count($missing));
foreach ($missing as $m) echo "  missing $m\n";
