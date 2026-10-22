<?php
// Writes the English SEO titles and descriptions in tdk.json (pages that carried the old site's rankings).
// Run from the WordPress root:  wp eval-file <theme>/tools/seo-tdk/apply.php dry-run   (then without dry-run)
// The values replaced are written to $BOBLIFT_BACKUP_DIR/seo-tdk-<time>.json first.
require_once __DIR__ . '/../backup-dir.php';

$dry = in_array('dry-run', (array) ($args ?? []), true);
$data = json_decode((string) file_get_contents(__DIR__ . '/tdk.json'), true);
$backup = [];
$writes = [];

$english_term = static function (string $slug): ?WP_Term {
    $terms = get_terms(['taxonomy' => 'product_category', 'slug' => $slug, 'hide_empty' => false, 'lang' => '']);
    foreach (is_array($terms) ? $terms : [] as $term) {
        if (!function_exists('pll_get_term_language') || in_array(pll_get_term_language($term->term_id), [false, '', 'en'], true)) {
            return $term;
        }
    }
    return null;
};
$english_post = static function (string $slug): ?WP_Post {
    $posts = get_posts(['post_type' => 'post', 'name' => $slug, 'post_status' => 'publish', 'posts_per_page' => -1, 'lang' => '']);
    foreach ($posts as $post) {
        if (!function_exists('pll_get_post_language') || in_array(pll_get_post_language($post->ID), [false, '', 'en'], true)) {
            return $post;
        }
    }
    return null;
};

foreach ($data['items'] as $item) {
    if ($item['kind'] === 'term') {
        $term = $english_term($item['slug']);
        if (!$term) {
            WP_CLI::warning("no English product category {$item['slug']}");
            continue;
        }
        $id = $term->term_id;
        $get = static fn(string $key) => (string) get_term_meta($id, $key, true);
        $set = static fn(string $key, string $value) => update_term_meta($id, $key, $value);
        $keys = ['title' => '_boblift_seo_title', 'description' => '_boblift_seo_description'];
        $label = "term $id {$item['slug']}";
    } else {
        $post = $item['kind'] === 'front_page' ? get_post((int) get_option('page_on_front')) : $english_post($item['slug']);
        if (!$post instanceof WP_Post) {
            WP_CLI::warning("no English {$item['kind']} {$item['path']}");
            continue;
        }
        $id = $post->ID;
        $get = static fn(string $key) => (string) get_post_meta($id, $key, true);
        $set = static fn(string $key, string $value) => update_post_meta($id, $key, $value);
        $keys = ['title' => '_seo_title', 'description' => '_seo_description'];
        $label = "{$item['kind']} $id {$item['path']}";
    }
    foreach ($keys as $field => $key) {
        $new = trim((string) ($item[$field] ?? ''));
        $old = $get($key);
        if ($new === '' || $new === $old) {
            continue;
        }
        $backup[] = ['target' => $label, 'key' => $key, 'old' => $old, 'new' => $new];
        $writes[] = static fn() => $set($key, $new);
        WP_CLI::log(($dry ? '[dry-run] ' : '') . "$label $key: \"$old\" -> \"$new\"");
    }
}

if (!$dry && $backup) {
    // The old values are saved before anything is written.
    $file = boblift_tools_backup_dir() . '/seo-tdk-' . gmdate('Ymd-His') . '.json';
    if (file_put_contents($file, wp_json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
        WP_CLI::error("could not write the backup $file; nothing was changed");
    }
    WP_CLI::log("backup: $file");
    foreach ($writes as $write) {
        $write();
    }
}
WP_CLI::success(($dry ? 'dry-run: ' : '') . count($backup) . ' values ' . ($dry ? 'would change' : 'changed'));
