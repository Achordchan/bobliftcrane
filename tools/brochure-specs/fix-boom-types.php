<?php
/**
 * Give SQ-series products without a boom type the one their model name states (SQ…Z… knuckle, SQ…S… telescopic).
 *
 * Usage (from the WordPress root): wp eval-file <path>/fix-boom-types.php [dry-run]
 * Only products saved as "custom-solution" or empty are touched, in every language. Other models (timber
 * cranes, platforms) stay out of the boom filter because the brochure does not state their boom type.
 */

$dry = in_array('dry-run', $args ?? [], true);
$changed = [];
foreach (get_posts(['post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => -1, 'lang' => '']) as $post) {
    $model = (string) get_post_meta($post->ID, '_model', true);
    $boom = (string) get_post_meta($post->ID, '_boom_type', true);
    if (!in_array($boom, ['', 'custom-solution'], true) || !preg_match('/^SQ\d+(?:\.\d+)?([ZS])/', $model, $m)) {
        continue;
    }
    $next = $m[1] === 'Z' ? 'knuckle-boom' : 'telescopic-boom';
    $changed[] = "{$post->ID} {$model}: {$boom} -> {$next}";
    if (!$dry) {
        update_post_meta($post->ID, '_boom_type', $next);
    }
}
echo implode("\n", $changed), "\n";
printf("%s: %d products\n", $dry ? 'DRY RUN' : 'DONE', count($changed));
