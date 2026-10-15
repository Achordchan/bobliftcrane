<?php
/**
 * Product body text and detail images are shown only when the product's "正文" / "详情图" readiness boxes are ticked
 * (_detail_body_ready / _detail_images_ready). Products that had content before those boxes existed would otherwise
 * lose it on deployment, so this switches the boxes on for content that was not just imported from the old site:
 *
 *   - the product was created natively (no old-site import marker), or
 *   - an editor saved it after the old-site import (modified after _old_site_imported_at).
 *
 * Products still in their untouched old-site imported state stay hidden. A box an editor has already saved (ticked or
 * unticked) is never changed. Idempotent.
 *
 *   wp eval-file tools/home/product-ready-flags.php dry-run
 *   wp eval-file tools/home/product-ready-flags.php
 */
$dry_run = in_array('dry-run', $args ?? [], true);
$posts = get_posts(['post_type' => 'product', 'post_status' => 'any', 'posts_per_page' => -1, 'lang' => '']);
$switched = 0;
foreach ($posts as $post) {
    $imported_at = (string) get_post_meta($post->ID, '_old_site_imported_at', true);
    $reviewed = $imported_at === '' || strtotime($post->post_modified_gmt . ' UTC') > (strtotime($imported_at) ?: 0);
    if (!$reviewed) {
        continue;
    }
    $has_body = trim(wp_strip_all_tags((string) $post->post_content)) !== '' || (bool) preg_match('/<(img|figure|video|audio|iframe|picture|embed|object)\b|wp:(image|gallery|video|embed)/i', (string) $post->post_content);
    $has_images = trim((string) get_post_meta($post->ID, '_detail_images', true)) !== '' && trim((string) get_post_meta($post->ID, '_detail_images', true)) !== '[]';
    $changes = [];
    foreach (['_detail_body_ready' => $has_body, '_detail_images_ready' => $has_images] as $key => $has_content) {
        if ($has_content && !metadata_exists('post', $post->ID, $key)) {
            $changes[] = $key;
            if (!$dry_run) {
                update_post_meta($post->ID, $key, '1');
            }
        }
    }
    if ($changes) {
        $switched++;
        echo ($dry_run ? '[dry-run] ' : '') . '#' . $post->ID . ' ' . $post->post_name . ': ' . implode(', ', $changes) . "\n";
    }
}
echo ($dry_run ? 'DRY RUN' : 'DONE') . ": $switched products\n";
