<?php
/**
 * Switch on the designed detail images (详情图) for every product, in every language, that has them.
 *
 * Usage (from the WordPress root): wp eval-file <path>/enable.php [dry-run]
 * Sets _detail_images_ready = 1 where _detail_images holds at least one image; editors can switch a
 * product off again in its admin box.
 */

$dry = in_array('dry-run', $args ?? [], true);
$on = 0;
$skipped = [];
foreach (get_posts(['post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => -1, 'lang' => '']) as $post) {
    $images = json_decode((string) get_post_meta($post->ID, '_detail_images', true), true);
    $has = is_array($images) && array_filter($images, static fn($item): bool => (is_string($item) ? $item : ($item['image'] ?? '')) !== '');
    if (!$has) {
        $skipped[] = $post->ID;
        continue;
    }
    if (get_post_meta($post->ID, '_detail_images_ready', true) !== '1') {
        $on++;
        if (!$dry) {
            update_post_meta($post->ID, '_detail_images_ready', '1');
        }
    }
}
printf("%s: %d products switched on, %d without detail images\n", $dry ? 'DRY RUN' : 'DONE', $on, count($skipped));
