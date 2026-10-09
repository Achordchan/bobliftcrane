<?php
/**
 * Bring the forklift fly jib cranes in line with the technical department's load charts (flyjib-plan.json).
 *
 * Usage (from the WordPress root): wp eval-file <path>/apply-flyjib.php [dry-run]
 * Updates the English title (slug kept), capacity, spec table and short description, and adds the load chart
 * after the brochure drawing. Run brochure-specs/apply-translations.php and apply-body.php afterwards.
 */

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$dir = __DIR__;
$dry = in_array('dry-run', $args ?? [], true);
$json = static fn($value): string => wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
foreach (json_decode((string) file_get_contents($dir . '/flyjib-plan.json'), true) as $p) {
    $post = null;
    foreach (get_posts(['post_type' => 'product', 'post_name__in' => [$p['slug']], 'posts_per_page' => 5, 'lang' => '']) as $candidate) {
        if (!function_exists('pll_get_post_language') || pll_get_post_language($candidate->ID) === 'en') {
            $post = $candidate;
        }
    }
    if (!$post) {
        echo "missing {$p['slug']}\n";
        continue;
    }
    echo "{$p['model']}: {$post->post_title} -> {$p['title']}\n";
    if ($dry) {
        continue;
    }
    wp_update_post(['ID' => $post->ID, 'post_title' => $p['title'], 'post_excerpt' => $p['short_description']]);
    foreach (['_capacity' => $p['capacity'], '_capacity_value' => (string) $p['capacity_value'], '_specs' => $json($p['specs']),
        '_short_description' => $p['short_description'], '_seo_description' => $p['short_description'], '_seo_title' => "China {$p['title']} - BOB-LIFT"] as $key => $value) {
        update_post_meta($post->ID, $key, wp_slash($value));
    }
    $existing = get_posts(['post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_boblift_drawing_file', 'meta_value' => $p['chart'], 'posts_per_page' => 1, 'fields' => 'ids']);
    $chart = $existing ? (int) $existing[0] : 0;
    if (!$chart) {
        $tmp = wp_tempnam($p['chart']);
        copy($dir . '/media/' . $p['chart'], $tmp);
        $chart = media_handle_sideload(['name' => sanitize_file_name('boblift-drawing-' . $p['chart']), 'tmp_name' => $tmp], $post->ID, $p['model'] . ' load chart');
        if (is_wp_error($chart)) {
            echo "  {$p['chart']}: {$chart->get_error_message()}\n";
            continue;
        }
        update_post_meta($chart, '_boblift_drawing_file', $p['chart']);
        update_post_meta($chart, '_wp_attachment_image_alt', $p['model'] . ' load chart');
    }
    $drawings = json_decode((string) get_post_meta($post->ID, '_drawings', true), true) ?: [];
    if (!in_array($chart, array_map('intval', array_column($drawings, 'id')), true)) {
        $drawings[] = ['id' => $chart, 'label' => $p['model'] . ' load chart'];
        update_post_meta($post->ID, '_drawings', wp_slash($json($drawings)));
    }
}
echo $dry ? "DRY RUN\n" : "DONE\n";
