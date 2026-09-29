<?php
/**
 * Create English products for brochure models the site does not have yet (phase-b.json).
 *
 * Usage (from the WordPress root): wp eval-file <path>/apply-phase-b.php [dry-run]
 * Skips any product whose slug already exists, so it is safe to run again.
 */

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$dir = __DIR__;
$dry = in_array('dry-run', $args ?? [], true);
$plan = json_decode((string) file_get_contents($dir . '/phase-b.json'), true);
$created = [];
$skipped = [];

foreach ($plan['products'] as $p) {
    $slug = sanitize_title($p['title']);
    $existing = get_posts(['post_type' => 'product', 'post_name__in' => [$slug], 'post_status' => ['publish', 'draft', 'pending', 'private'], 'posts_per_page' => 1, 'lang' => '']);
    $term = get_term_by('slug', $p['category'], 'product_category');
    if ($existing || !$term) {
        $skipped[] = $slug . ($term ? ' (exists)' : ' (no category ' . $p['category'] . ')');
        continue;
    }
    if ($dry) {
        $created[] = $slug;
        continue;
    }
    $id = wp_insert_post(wp_slash([
        'post_type' => 'product',
        'post_status' => 'publish',
        'post_title' => $p['title'],
        'post_name' => $slug,
        'post_excerpt' => $p['short_description'],
        'post_content' => '<p>' . esc_html($p['short_description']) . '</p>',
    ]), true);
    if (is_wp_error($id)) {
        $skipped[] = $slug . ' (' . $id->get_error_message() . ')';
        continue;
    }
    if (function_exists('pll_set_post_language')) {
        pll_set_post_language($id, 'en');
    }
    wp_set_object_terms($id, [(int) $term->term_id], 'product_category');

    // Import the image into the media library and use it as the featured image and gallery.
    $gallery = [];
    $attachment = new WP_Error('no_image');
    if (!empty($p['image'])) {
        $tmp = wp_tempnam(basename($p['image']));
        copy($dir . '/' . $p['image'], $tmp);
        $attachment = media_handle_sideload(['name' => $slug . '.webp', 'tmp_name' => $tmp], $id, $p['title']);
    }
    if (!is_wp_error($attachment)) {
        set_post_thumbnail($id, $attachment);
        update_post_meta($attachment, '_wp_attachment_image_alt', $p['title']);
        $gallery[] = ['image' => wp_get_attachment_url($attachment), 'label' => $p['title']];
    }

    $meta = [
        '_model' => $p['model'],
        '_capacity' => $p['capacity'],
        '_capacity_value' => (string) $p['capacity_value'],
        '_boom_type' => $p['boom_type'],
        '_status' => 'in-stock',
        '_applications' => $p['applications'],
        '_short_description' => $p['short_description'],
        '_seo_title' => $p['seo_title'],
        '_seo_description' => $p['short_description'],
        '_specs' => wp_json_encode($p['specs'], JSON_UNESCAPED_UNICODE),
        '_features' => wp_json_encode($p['features'], JSON_UNESCAPED_UNICODE),
        '_gallery' => wp_json_encode($gallery, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        '_brochure_source' => $p['source'],
    ];
    foreach ($meta as $key => $value) {
        update_post_meta($id, $key, wp_slash($value));
    }
    $created[] = $id . ' ' . $slug;
}

// The new models widen the English category capacity ranges (truck up to 40 t, marine from 800 kg).
$ranges = [
    'truck-mounted-crane' => ['from 800 kg to 25 t', 'from 800 kg to 40 t'],
    'marine-crane' => ['from 1 t to 25 t', 'from 800 kg to 25 t'],
];
foreach ($ranges as $slug => [$from, $to]) {
    $term = get_term_by('slug', $slug, 'product_category');
    if (!$term) {
        continue;
    }
    if (str_contains($term->description, $from)) {
        $dry ?: wp_update_term($term->term_id, 'product_category', ['description' => str_replace($from, $to, $term->description)]);
        echo "  category $slug: $from -> $to\n";
    }
    $seo = (string) get_term_meta($term->term_id, '_boblift_seo_description', true);
    if (str_contains($seo, $from)) {
        $dry ?: update_term_meta($term->term_id, '_boblift_seo_description', str_replace($from, $to, $seo));
        echo "  category SEO $slug: $from -> $to\n";
    }
}

printf("%s: created %d, skipped %d\n", $dry ? 'DRY RUN' : 'DONE', count($created), count($skipped));
foreach ($created as $c) echo "  + $c\n";
foreach ($skipped as $s) echo "  - $s\n";
