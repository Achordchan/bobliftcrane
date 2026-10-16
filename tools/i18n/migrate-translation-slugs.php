<?php
/**
 * Gives existing translations the slug of the English post they translate (foo-2 → foo), without touching their content.
 *
 *   wp eval-file tools/i18n/migrate-translation-slugs.php [apply]
 *
 * Without "apply" it only lists what would change. With it, each changed post's row and meta are saved to
 * $BOBLIFT_BACKUP_DIR first. Covers posts, pages and products. Run after Polylang is switched to directory mode, so
 * inc/polylang-slugs.php lets a translation share the English slug. The old numbered addresses keep answering with a
 * 301 (boblift_redirect_numbered_translation_slug), and permalinks already use the English slug either way.
 */
require_once dirname(__DIR__) . '/backup-dir.php';

$apply = in_array('apply', $args ?? [], true);
if (!function_exists('pll_get_post_language') || !function_exists('pll_get_post') || !function_exists('pll_default_language')) {
    fwrite(STDERR, "Polylang is not active.\n");
    exit(1);
}
$default = (string) pll_default_language('slug');
$report = ['renamed' => 0, 'unchanged' => 0, 'blocked' => 0];
$ids = get_posts(['post_type' => ['post', 'page', 'product'], 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'lang' => '']);
foreach ($ids as $id) {
    $post = get_post($id);
    $language = (string) pll_get_post_language($id, 'slug');
    if (!$post instanceof WP_Post || $language === '' || $language === $default) {
        continue;
    }
    $source_id = (int) pll_get_post($id, $default);
    $source = $source_id ? get_post($source_id) : null;
    if (!$source instanceof WP_Post || $source->post_type !== $post->post_type || $source->post_name === '' || $source->post_name === $post->post_name) {
        $report['unchanged']++;
        continue;
    }
    // The slug filter decides whether the shared slug is allowed (no same-language, unassigned or attachment holder).
    $allowed = wp_unique_post_slug($source->post_name, $id, 'publish', $post->post_type, (int) $post->post_parent);
    if ($allowed !== $source->post_name) {
        $report['blocked']++;
        echo "blocked: {$post->post_type} $id ($language) {$post->post_name} → {$source->post_name} is taken\n";
        continue;
    }
    echo ($apply ? 'renamed' : 'would rename') . ": {$post->post_type} $id ($language) {$post->post_name} → {$source->post_name}\n";
    if (!$apply) {
        $report['renamed']++;
        continue;
    }
    $snapshot = wp_json_encode(['post' => get_post($id, ARRAY_A), 'meta' => get_post_meta($id)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    $file = boblift_tools_backup_dir() . '/backup-slug-' . $language . '-' . $post->post_name . '-' . gmdate('Ymd-His') . '.json';
    if (!is_string($snapshot) || file_put_contents($file, $snapshot) !== strlen($snapshot)) {
        fwrite(STDERR, "Cannot write backup $file; stopped before changing post $id.\n");
        exit(1);
    }
    // Only the slug is written; content, status and dates stay as they are.
    $result = wp_update_post(['ID' => $id, 'post_name' => $source->post_name], true);
    if (is_wp_error($result) || get_post_field('post_name', $id) !== $source->post_name) {
        fwrite(STDERR, "Could not rename post $id.\n");
        exit(1);
    }
    $report['renamed']++;
}
echo json_encode($report + ['applied' => $apply]), "\n";
