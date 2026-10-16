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

// Preflight: every product image must be present before anything is published (a product created without its photo would be skipped on reruns).
$missing_images = [];
foreach ($plan['products'] as $p) {
    if (!empty($p['image']) && !is_file($dir . '/' . $p['image'])) {
        $missing_images[] = $p['image'];
    }
}
if ($missing_images) {
    fwrite(STDERR, "Missing product images, nothing was created:\n  " . implode("\n  ", $missing_images) . "\n");
    exit(1);
}

foreach ($plan['products'] as $p) {
    $slug = sanitize_title($p['title']);
    $existing = get_posts(['post_type' => 'product', 'post_name__in' => [$slug], 'post_status' => ['publish', 'draft', 'pending', 'private'], 'posts_per_page' => 1, 'lang' => '']);
    $term = get_term_by('slug', $p['category'], 'product_category');
    // A draft this script staged (marker meta) is an interrupted run and is initialised again from the start.
    $resuming = $existing && $existing[0]->post_status === 'draft' && get_post_meta($existing[0]->ID, '_boblift_staging', true) === '1';
    if ($existing && !$resuming && $term && !$dry && !empty($p['image']) && !has_post_thumbnail($existing[0]->ID)) {
        // A product created earlier without its photo (failed import): attach it now.
        $tmp = wp_tempnam(basename($p['image']));
        copy($dir . '/' . $p['image'], $tmp);
        $repair = media_handle_sideload(['name' => $slug . '.webp', 'tmp_name' => $tmp], $existing[0]->ID, $p['title']);
        if (!is_wp_error($repair)) {
            set_post_thumbnail($existing[0]->ID, $repair);
            update_post_meta($repair, '_wp_attachment_image_alt', $p['title']);
            $gallery_now = json_decode((string) get_post_meta($existing[0]->ID, '_gallery', true), true) ?: [];
            if (!$gallery_now) {
                update_post_meta($existing[0]->ID, '_gallery', wp_slash(wp_json_encode([['image' => wp_get_attachment_url($repair), 'label' => $p['title']]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
            }
            $skipped[] = $slug . ' (exists, photo attached)';
            continue;
        }
    }
    if (($existing && !$resuming) || !$term) {
        $skipped[] = $slug . ($term ? ' (exists)' : ' (no category ' . $p['category'] . ')');
        continue;
    }
    if ($dry) {
        $created[] = $slug;
        continue;
    }
    // Staged as a marked draft; it is published only after the image and all metadata are written.
    $postarr = [
        'post_type' => 'product',
        'post_status' => 'draft',
        'post_title' => $p['title'],
        'post_name' => $slug,
        'post_excerpt' => $p['short_description'],
        'post_content' => '<p>' . esc_html($p['short_description']) . '</p>',
    ];
    if ($resuming) {
        $postarr['ID'] = $existing[0]->ID;
        $id = wp_update_post(wp_slash($postarr), true);
    } else {
        $id = wp_insert_post(wp_slash($postarr), true);
    }
    if (is_wp_error($id)) {
        $skipped[] = $slug . ' (' . $id->get_error_message() . ')';
        continue;
    }
    update_post_meta($id, '_boblift_staging', '1');
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
    if (!empty($p['image']) && is_wp_error($attachment)) {
        // The planned image could not be imported: keep the product staged; fix the file and run again to finish it.
        $skipped[] = $slug . ' (staged as draft: the image could not be imported)';
        continue;
    }
    wp_update_post(['ID' => $id, 'post_status' => 'publish']);
    delete_post_meta($id, '_boblift_staging');
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
