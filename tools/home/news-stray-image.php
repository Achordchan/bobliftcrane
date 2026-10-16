<?php
/**
 * The "SQ10ZA5 Limited Time Discount" article (en/ja/zh) embeds attachment 451 "ss.png", a screenshot of
 * an IP-lookup site full of third-party ads that has nothing to do with the crane. This removes that image
 * block from every post that embeds it. The attachment itself is left in the media library.
 *
 *   wp eval-file ../tools/home/news-stray-image.php dry-run
 *   wp eval-file ../tools/home/news-stray-image.php
 */
$dry_run = in_array('dry-run', $args ?? [], true);
global $wpdb;
$ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'post' AND post_content LIKE '%/uploads/2026/05/ss-%' OR (post_type = 'post' AND post_content LIKE '%/uploads/2026/05/ss.png%')");
$pattern = '#\s*<!-- wp:image \{[^}]*"id":\d+[^}]*\} -->\s*<figure[^>]*>\s*<img[^>]+/uploads/2026/05/ss(?:-\d+x\d+)?\.png[^>]*>\s*</figure>\s*<!-- /wp:image -->#s';
foreach ($ids as $id) {
    $post = get_post((int) $id);
    $content = (string) preg_replace($pattern, "\n", $post->post_content, -1, $count);
    if ($count === 0) {
        WP_CLI::warning("#$id {$post->post_title}: image found but block pattern did not match, check by hand");
        continue;
    }
    if (!$dry_run) {
        wp_update_post(['ID' => $post->ID, 'post_content' => $content]);
    }
    WP_CLI::log(($dry_run ? '[dry-run] ' : '') . "#$id {$post->post_title}: removed $count image block(s)");
}
