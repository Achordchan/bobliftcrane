<?php
/**
 * Append the factory photos in plan.json to the product galleries, in every language.
 *
 * Usage (from the WordPress root): wp eval-file <path>/apply.php [dry-run]
 * Build first with build.py. Each photo is imported once (tracked by _boblift_product_photo_file), and a gallery
 * never gets the same image twice, so the script can run again safely. With own_photos_first, the borrowed
 * "Reference photo: similar model" row is dropped and the first own photo becomes the main image.
 */

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$dir = __DIR__;
$dry = in_array('dry-run', $args ?? [], true);
$plan = json_decode((string) file_get_contents($dir . '/plan.json'), true);

$find = static function (string $slug): ?WP_Post {
    foreach (get_posts(['post_type' => 'product', 'post_name__in' => [$slug], 'post_status' => 'publish', 'posts_per_page' => 5, 'lang' => '']) as $post) {
        if (!function_exists('pll_get_post_language') || pll_get_post_language($post->ID) === 'en') {
            return $post;
        }
    }
    return null;
};

$import = static function (string $file, int $parent, string $title) use ($dir): int {
    $existing = get_posts(['post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_boblift_product_photo_file', 'meta_value' => $file, 'posts_per_page' => 1, 'fields' => 'ids']);
    if ($existing) {
        return (int) $existing[0];
    }
    $tmp = wp_tempnam(basename($file));
    copy($dir . '/media/' . $file, $tmp);
    $id = media_handle_sideload(['name' => sanitize_file_name('boblift-factory-photos-' . $file), 'tmp_name' => $tmp], $parent, $title);
    if (is_wp_error($id)) {
        WP_CLI::warning($file . ': ' . $id->get_error_message());
        return 0;
    }
    update_post_meta($id, '_boblift_product_photo_file', $file);
    update_post_meta($id, '_wp_attachment_image_alt', $title);
    return (int) $id;
};

$added = 0;
$missing = [];
foreach ($plan['products'] as $p) {
    $post = $find($p['slug']);
    if (!$post) {
        $missing[] = $p['slug'];
        continue;
    }
    $model = $p['model'];
    $translations = function_exists('pll_get_post_translations') ? pll_get_post_translations($post->ID) : ['en' => $post->ID];
    if ($dry) {
        printf("%s: %d photos -> %s\n", $model, count($p['photos']), implode(',', array_keys($translations)));
        continue;
    }
    $urls = [];
    foreach ($p['photos'] as $photo) {
        $id = $import($photo['file'], $post->ID, $model . ' factory photo');
        if ($id) {
            $urls[$id] = (string) wp_get_attachment_url($id);
        }
    }
    if (!$urls) {
        continue;
    }

    // Images of the borrowed reference photo, found on the English gallery by its label.
    $english = json_decode((string) get_post_meta($post->ID, '_gallery', true), true) ?: [];
    $borrowed = [];
    foreach ($english as $row) {
        if (str_starts_with((string) ($row['label'] ?? ''), 'Reference photo')) {
            $borrowed[] = (string) $row['image'];
        }
    }

    foreach ($translations as $lang => $tid) {
        $gallery = json_decode((string) get_post_meta($tid, '_gallery', true), true) ?: [];
        if (!empty($p['own_photos_first'])) {
            $gallery = array_values(array_filter($gallery, static fn($row): bool => !in_array((string) ($row['image'] ?? ''), $borrowed, true)));
            set_post_thumbnail($tid, (int) array_key_first($urls));
        }
        $label = $lang === 'en' ? $model . ' (factory photo)' : get_the_title($tid);
        $have = array_map(static fn($row): string => (string) ($row['image'] ?? ''), $gallery);
        foreach ($urls as $url) {
            if (!in_array($url, $have, true)) {
                $gallery[] = ['image' => $url, 'label' => $label];
                $added++;
            }
        }
        update_post_meta($tid, '_gallery', wp_slash(wp_json_encode($gallery, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
    }
}
printf("%s: %d gallery rows added%s\n", $dry ? 'DRY RUN' : 'DONE', $added, $missing ? '; missing slugs: ' . implode(', ', $missing) : '');
