<?php
/**
 * Bring the truck-mounted cranes in line with the technical department's drawings (plan.json, from build.py).
 *
 * Usage (from the WordPress root): wp eval-file <path>/apply.php [marine-plan.json] [dry-run]
 * The marine cranes use marine-plan.json (build.py marine); the default is the truck cranes' plan.json.
 * - Existing English products: spec table from the drawing; aliases that are now products of their own are dropped.
 * - New English products: created with specs, the sibling's series features and photo (labelled as a reference
 *   photo), and the outline drawing and load chart. Skipped when the slug already exists, so it can run again.
 * - The truck crane category now reaches 50 t (SQZ1000).
 * Run tools/brochure-specs/apply-translations.php and apply-body.php afterwards for the ja/zh copies and body text.
 */

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$dir = __DIR__;
$dry = in_array('dry-run', $args ?? [], true);
$plan_file = current(array_filter($args ?? [], static fn(string $a): bool => str_ends_with($a, '.json'))) ?: 'plan.json';
$plan = json_decode((string) file_get_contents($dir . '/' . $plan_file), true);
$json = static fn($value): string => wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$find = static function (string $slug): ?WP_Post {
    foreach (get_posts(['post_type' => 'product', 'post_name__in' => [$slug], 'post_status' => ['publish', 'draft', 'pending', 'private'], 'posts_per_page' => 5, 'lang' => '']) as $post) {
        if (!function_exists('pll_get_post_language') || pll_get_post_language($post->ID) === 'en') {
            return $post;
        }
    }
    return null;
};

$import = static function (string $file, int $parent, string $title) use ($dir): int {
    $existing = get_posts(['post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_boblift_drawing_file', 'meta_value' => $file, 'posts_per_page' => 1, 'fields' => 'ids']);
    if ($existing) {
        return (int) $existing[0];
    }
    $tmp = wp_tempnam($file);
    copy($dir . '/media/' . $file, $tmp);
    $id = media_handle_sideload(['name' => sanitize_file_name('boblift-drawing-' . $file), 'tmp_name' => $tmp], $parent, $title);
    if (is_wp_error($id)) {
        WP_CLI::warning($file . ': ' . $id->get_error_message());
        return 0;
    }
    update_post_meta($id, '_boblift_drawing_file', $file);
    update_post_meta($id, '_wp_attachment_image_alt', $title);
    return (int) $id;
};

$updated = [];
$missing = [];
foreach ($plan['existing'] as $p) {
    $post = $find($p['slug']);
    if (!$post) {
        $missing[] = $p['slug'];
        continue;
    }
    $updated[] = $p['model'];
    if ($dry) {
        continue;
    }
    update_post_meta($post->ID, '_specs', wp_slash($json($p['specs'])));
    if ($p['drop_alias'] !== '') {
        $aliases = array_filter(array_map('trim', explode(',', (string) get_post_meta($post->ID, '_model_aliases', true))), static fn(string $a): bool => $a !== '' && $a !== $p['drop_alias']);
        update_post_meta($post->ID, '_model_aliases', implode(', ', $aliases));
    }
}

$created = [];
$skipped = [];
foreach ($plan['new'] as $p) {
    $slug = sanitize_title($p['title']);
    $term = get_term_by('slug', $p['category'], 'product_category');
    $sibling = $find($p['sibling']);
    // Already created: keep it, but bring its spec table in line with the plan.
    if (($existing = $find($slug)) && !$dry) {
        update_post_meta($existing->ID, '_specs', wp_slash($json($p['specs'])));
    }
    if ($existing || !$term || !$sibling) {
        $skipped[] = $slug . ($term ? ($sibling ? ' (exists)' : ' (no sibling ' . $p['sibling'] . ')') : ' (no category)');
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

    // The sibling's main photo stands in, labelled as a reference, until tools/product-photos adds own photos.
    $gallery = [];
    $thumb = (int) get_post_thumbnail_id($sibling->ID);
    if ($thumb) {
        set_post_thumbnail($id, $thumb);
        $gallery[] = ['image' => wp_get_attachment_url($thumb), 'label' => 'Reference photo: similar model ' . get_post_meta($sibling->ID, '_model', true)];
    }
    $drawings = [];
    foreach ($p['drawings'] as $drawing) {
        $label = $p['model'] . ($drawing['kind'] === 'chart' ? ' load chart' : ' dimension drawing and technical parameters');
        $attachment = $import($drawing['file'], $id, $label);
        if ($attachment) {
            $drawings[] = ['id' => $attachment, 'label' => $label];
        }
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
        '_specs' => $json($p['specs']),
        '_gallery' => $json($gallery),
        '_drawings' => $json($drawings),
        '_brochure_source' => $p['source'],
    ];
    // Series features (with their detail photos) as on the sibling.
    foreach (['_features', '_features_source', '_features_series'] as $key) {
        $meta[$key] = (string) get_post_meta($sibling->ID, $key, true);
    }
    if ($meta['_features'] === '') {
        $meta['_features'] = $json($p['features']);
    }
    foreach ($meta as $key => $value) {
        update_post_meta($id, $key, wp_slash($value));
    }
    $created[] = $id . ' ' . $slug;
}

// SQZ1000 lifts 50 t, so the truck crane category no longer stops at 40 t (or the 25 t the ja/zh copies still say).
$term = $plan_file === 'plan.json' ? get_term_by('slug', 'truck-mounted-crane', 'product_category') : null;
$terms = $term ? [$term->term_id] : [];
foreach ($term && function_exists('pll_get_term') ? ['zh', 'ja'] : [] as $lang) {
    $terms[] = (int) pll_get_term($term->term_id, $lang);
}
$ranges = ['800 kg to 40 t' => '800 kg to 50 t', '800 kg 至 25 t' => '800 kg 至 50 t', '800 kgから25 t' => '800 kgから50 t'];
foreach (array_filter($terms) as $term_id) {
    $t = get_term($term_id, 'product_category');
    $description = strtr($t->description, $ranges);
    $seo = (string) get_term_meta($term_id, '_boblift_seo_description', true);
    if ($description !== $t->description) {
        $dry ?: wp_update_term($term_id, 'product_category', ['description' => $description]);
        echo "  category {$t->slug}: {$description}\n";
    }
    if (strtr($seo, $ranges) !== $seo) {
        $dry ?: update_term_meta($term_id, '_boblift_seo_description', strtr($seo, $ranges));
    }
}

printf("%s: updated %d, created %d, skipped %d%s\n", $dry ? 'DRY RUN' : 'DONE', count($updated), count($created), count($skipped), $missing ? '; missing: ' . implode(', ', $missing) : '');
foreach ($created as $c) echo "  + $c\n";
foreach ($skipped as $s) echo "  - $s\n";
