<?php
/**
 * Attach brochure drawings, brochure photos, series features and the source line to English products.
 *
 * Usage (from the WordPress root): wp eval-file <path>/apply-phase-c.php [dry-run]
 * Run after phase A and phase B. Each brochure file is imported once (tracked by the
 * _boblift_brochure_file attachment meta), so the script can run again safely.
 */

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$dir = __DIR__;
$dry = in_array('dry-run', $args ?? [], true);
$plan = json_decode((string) file_get_contents($dir . '/phase-c.json'), true);

$find = static function (string $slug): ?WP_Post {
    foreach (get_posts(['post_type' => 'product', 'post_name__in' => [$slug], 'post_status' => 'publish', 'posts_per_page' => 5, 'lang' => '']) as $post) {
        if (!function_exists('pll_get_post_language') || pll_get_post_language($post->ID) === 'en') {
            return $post;
        }
    }
    return null;
};

$import = static function (string $file, int $parent, string $title) use ($dir): int {
    $existing = get_posts(['post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_boblift_brochure_file', 'meta_value' => $file, 'posts_per_page' => 1, 'fields' => 'ids']);
    if ($existing) {
        return (int) $existing[0];
    }
    $tmp = wp_tempnam(basename($file));
    copy($dir . '/' . $file, $tmp);
    $name = sanitize_file_name('boblift-' . str_replace('/', '-', $file));
    $id = media_handle_sideload(['name' => $name, 'tmp_name' => $tmp], $parent, $title);
    if (is_wp_error($id)) {
        return 0;
    }
    update_post_meta($id, '_boblift_brochure_file', $file);
    update_post_meta($id, '_wp_attachment_image_alt', $title);
    return (int) $id;
};

$done = 0;
$missing = [];
foreach ($plan['products'] as $p) {
    $post = $find($p['slug']);
    if (!$post) {
        $missing[] = $p['slug'];
        continue;
    }
    $done++;
    if ($dry) {
        continue;
    }
    $id = $post->ID;
    $model = $p['model'];

    $drawing = $import($p['drawing'], $id, $model . ' dimension drawing and load chart');
    if ($drawing) {
        update_post_meta($id, '_drawings', wp_slash(wp_json_encode([['id' => $drawing, 'label' => $model . ' dimension drawing and load chart']], JSON_UNESCAPED_UNICODE)));
    }

    $gallery = json_decode((string) get_post_meta($id, '_gallery', true), true) ?: [];
    if (!empty($p['borrow_photo_from'])) {
        // A new model without its own photo shows the sibling's photo, labelled as a reference.
        $sibling = $find($p['borrow_photo_from']);
        $thumb = $sibling ? (int) get_post_thumbnail_id($sibling) : 0;
        if ($thumb) {
            set_post_thumbnail($id, $thumb);
            $sibling_model = (string) get_post_meta($sibling->ID, '_model', true);
            $gallery = [['image' => wp_get_attachment_url($thumb), 'label' => 'Reference photo: similar model ' . $sibling_model]];
        }
    }
    $urls = array_map(static fn($g) => is_array($g) ? (string) ($g['image'] ?? '') : (string) $g, $gallery);
    foreach ($p['photos'] as $index => $file) {
        $photo = $import($file, $id, $model . ' photo from the BOB-LIFT product manual');
        $url = $photo ? (string) wp_get_attachment_url($photo) : '';
        if ($url === '' || in_array($url, $urls, true)) {
            continue;
        }
        if (!has_post_thumbnail($id) || ($index === 0 && !$gallery)) {
            set_post_thumbnail($id, $photo);
        }
        $gallery[] = ['image' => $url, 'label' => $model . ' (BOB-LIFT product manual)'];
        $urls[] = $url;
    }
    update_post_meta($id, '_gallery', wp_slash(wp_json_encode($gallery, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));

    if (!empty($p['features'])) {
        update_post_meta($id, '_features', wp_slash(wp_json_encode($p['features'], JSON_UNESCAPED_UNICODE)));
        // The product page shows the features block only for brochure text, not old-site copy.
        update_post_meta($id, '_features_source', 'brochure');
    }
    update_post_meta($id, '_brochure_source', $p['source']);
}

printf("%s: %d products, missing %d\n", $dry ? 'DRY RUN' : 'DONE', $done, count($missing));
foreach ($missing as $m) echo "  missing $m\n";
