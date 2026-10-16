<?php
/**
 * Fix product main and gallery images from plan.json and record how each one should be framed.
 *
 * Usage (from the WordPress root): wp eval-file <path>/apply.php [dry-run]
 * Run after the brochure phases. Needs GD with PNG support. Trimmed cut-outs are imported once
 * (tracked by the _boblift_trimmed_from attachment meta), so the script can run again safely.
 *
 * - swap:   the "-transparent.png" was a photo with holes cut by background removal; use the original.
 * - trim:   a clean cut-out with wide transparent margins; crop it to its box so it fills the stage.
 * - cutout / photo: keep the file, only record the fit.
 */

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$dry = in_array('dry-run', $args ?? [], true);
$plan = json_decode((string) file_get_contents(__DIR__ . '/plan.json'), true);
$uploads = wp_get_upload_dir();

$attachment_by_file = static function (string $rel): int {
    $ids = get_posts(['post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_wp_attached_file', 'meta_value' => $rel, 'posts_per_page' => 1, 'fields' => 'ids']);
    return $ids ? (int) $ids[0] : 0;
};

$trim = static function (int $source, string $rel, array $box) use ($uploads): int {
    $existing = get_posts(['post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_boblift_trimmed_from', 'meta_value' => $rel, 'posts_per_page' => 1, 'fields' => 'ids']);
    if ($existing) {
        return (int) $existing[0];
    }
    $image = @imagecreatefrompng($uploads['basedir'] . '/' . $rel);
    if (!$image) {
        return 0;
    }
    [$x0, $y0, $x1, $y1] = array_map('intval', $box);
    $cropped = imagecrop($image, ['x' => $x0, 'y' => $y0, 'width' => $x1 - $x0, 'height' => $y1 - $y0]);
    if (!$cropped) {
        return 0;
    }
    imagealphablending($cropped, false);
    imagesavealpha($cropped, true);
    $tmp = wp_tempnam('boblift-cutout');
    imagepng($cropped, $tmp, 6);
    $name = preg_replace('/-transparent\.png$/', '', wp_basename($rel)) . '-cutout.png';
    $id = media_handle_sideload(['name' => $name, 'tmp_name' => $tmp], (int) get_post_field('post_parent', $source), get_the_title($source));
    if (is_wp_error($id)) {
        return 0;
    }
    update_post_meta($id, '_boblift_trimmed_from', $rel);
    update_post_meta($id, '_wp_attachment_image_alt', (string) get_post_meta($source, '_wp_attachment_image_alt', true));
    return (int) $id;
};

// Source file (upload-relative) => [target attachment id, fit].
$map = [];
$missing = [];
foreach ($plan['images'] as $item) {
    $rel = $item['file'];
    $source = $attachment_by_file($rel);
    if (!$source) {
        $missing[] = $rel;
        continue;
    }
    if ($dry) {
        // Resolve the replacement without writing: a swap uses the original attachment, a trim uses the existing cut-out or,
        // when it has not been made yet, a planned one (-1), so the preview counts the products that would change.
        $planned = $source;
        if ($item['action'] === 'swap') {
            $planned = $attachment_by_file($item['original']);
        } elseif ($item['action'] === 'trim') {
            $made = get_posts(['post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_boblift_trimmed_from', 'meta_value' => $rel, 'posts_per_page' => 1, 'fields' => 'ids']);
            $planned = $made ? (int) $made[0] : -1;
        }
        if (!$planned) {
            $missing[] = $rel . ' (' . $item['action'] . ')';
            continue;
        }
        $map[$rel] = [$planned, $item['action']];
        continue;
    }
    $target = $source;
    $fit = $item['action'] === 'photo' || $item['action'] === 'swap' ? 'photo' : 'cutout';
    if ($item['action'] === 'swap') {
        $target = $attachment_by_file($item['original']);
    } elseif ($item['action'] === 'trim') {
        $target = $trim($source, $rel, $item['box']);
    }
    if (!$target) {
        $missing[] = $rel . ' (' . $item['action'] . ')';
        continue;
    }
    update_post_meta($target, '_boblift_image_fit', $fit);
    if ($item['action'] !== 'swap') {
        update_post_meta($source, '_boblift_image_fit', $fit);
    }
    $map[$rel] = [$target, $fit];
}

$rel_of_url = static function (string $url) use ($uploads): string {
    return ltrim(str_replace($uploads['baseurl'], '', $url), '/');
};

$changed = 0;
foreach (get_posts(['post_type' => 'product', 'post_status' => ['publish', 'draft', 'pending', 'private'], 'posts_per_page' => -1, 'lang' => '']) as $post) {
    $dirty = false;
    $thumb = (int) get_post_thumbnail_id($post->ID);
    if ($thumb) {
        $rel = (string) get_post_meta($thumb, '_wp_attached_file', true);
        if (isset($map[$rel]) && $map[$rel][0] !== $thumb) {
            $dirty = true;
            if (!$dry) set_post_thumbnail($post->ID, $map[$rel][0]);
        }
    }
    $gallery = json_decode((string) get_post_meta($post->ID, '_gallery', true), true) ?: [];
    $next = [];
    $seen = [];
    foreach ($gallery as $entry) {
        $entry = is_array($entry) ? $entry : ['image' => (string) $entry, 'label' => ''];
        $entry['image'] = $entry['image'] ?? $entry['url'] ?? '';
        $rel = $rel_of_url((string) ($entry['image'] ?? ''));
        if (isset($map[$rel])) {
            // A planned (not yet created) cut-out has no URL; it still counts as a change.
            $url = $map[$rel][0] > 0 ? (string) wp_get_attachment_url($map[$rel][0]) : '(planned cut-out)';
            if ($url !== $entry['image']) {
                $entry['image'] = $url;
                $dirty = true;
            }
        }
        $key = strtolower((string) $entry['image']);
        if (isset($seen[$key])) {
            $dirty = true;
            continue;
        }
        $seen[$key] = true;
        $next[] = $entry;
    }
    if ($dirty) {
        $changed++;
        if (!$dry) {
            update_post_meta($post->ID, '_gallery', wp_slash(wp_json_encode($next, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
        }
    }
}

$count = array_count_values(array_column($plan['images'], 'action'));
printf("%s: %d images mapped (%s), %d products %s, missing %d\n", $dry ? 'DRY RUN' : 'DONE', count($map), http_build_query($count, '', ', '), $changed, $dry ? 'would change' : 'changed', count($missing));
foreach ($missing as $m) echo "  missing $m\n";
