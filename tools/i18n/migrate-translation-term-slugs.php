<?php
/**
 * Gives existing category translations the slug of the English category they translate
 * (marine-crane-ru-61 → marine-crane), so their archives read /ru/marine-crane instead of /ru/marine-crane-ru-61.
 * Only generated slugs (<english>-<lang> or <english>-<lang>-<number>) are renamed; any other slug is left alone.
 *
 *   wp eval-file tools/i18n/migrate-translation-term-slugs.php [apply]
 *
 * Without "apply" it only lists what would change. With it, each changed term's row and meta are saved to
 * $BOBLIFT_BACKUP_DIR first. Covers every taxonomy Polylang translates. Names, descriptions and meta stay as they are.
 */
require_once dirname(__DIR__) . '/backup-dir.php';

$apply = in_array('apply', $args ?? [], true);
if (!function_exists('pll_get_term_language') || !function_exists('pll_get_term') || !function_exists('pll_default_language')) {
    fwrite(STDERR, "Polylang is not active.\n");
    exit(1);
}
$default = (string) pll_default_language('slug');
$taxonomies = function_exists('PLL') ? PLL()->model->get_translated_taxonomies() : ['category', 'post_tag'];
$report = ['renamed' => 0, 'unchanged' => 0, 'blocked' => 0];
$terms = get_terms(['taxonomy' => array_values($taxonomies), 'hide_empty' => false, 'lang' => '']);
foreach (is_array($terms) ? $terms : [] as $term) {
    $language = (string) pll_get_term_language($term->term_id, 'slug');
    if ($language === '' || $language === $default) {
        continue;
    }
    $source_id = (int) pll_get_term($term->term_id, $default);
    $source = $source_id ? get_term($source_id, $term->taxonomy) : null;
    // Only slugs the tools generated are renamed: Polylang's suffix (marine-crane-ru) or the clone job's (marine-crane-ru-61).
    // Any other slug was chosen on purpose and may already be linked, so it stays.
    if (!$source instanceof WP_Term || $source->slug === $term->slug
        || !preg_match('/^' . preg_quote($source->slug, '/') . '-' . preg_quote($language, '/') . '(-\d+)?$/', $term->slug)) {
        $report['unchanged']++;
        continue;
    }
    $label = "{$term->taxonomy} {$term->term_id} ($language) {$term->slug} → {$source->slug}";
    $holder = get_terms(['taxonomy' => $term->taxonomy, 'slug' => $source->slug, 'hide_empty' => false, 'lang' => $language, 'fields' => 'ids']);
    if (is_array($holder) && array_diff($holder, [$term->term_id])) {
        $report['blocked']++;
        echo "blocked: $label is taken\n";
        continue;
    }
    echo ($apply ? 'renamed' : 'would rename') . ": $label\n";
    if (!$apply) {
        $report['renamed']++;
        continue;
    }
    $snapshot = wp_json_encode(['term' => get_term($term->term_id, $term->taxonomy, ARRAY_A), 'meta' => get_term_meta($term->term_id)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    $file = boblift_tools_backup_dir() . '/backup-term-slug-' . $term->taxonomy . '-' . $term->term_id . '-' . gmdate('Ymd-His') . '.json';
    $handle = is_string($snapshot) ? @fopen($file, 'x') : false;
    $written = $handle ? fwrite($handle, $snapshot) : false;
    if ($handle) {
        fclose($handle);
    }
    if ($written !== strlen((string) $snapshot)) {
        fwrite(STDERR, "Cannot write backup $file; stopped before changing term {$term->term_id}.\n");
        exit(1);
    }
    $result = wp_update_term($term->term_id, $term->taxonomy, ['slug' => $source->slug]);
    if (is_wp_error($result) || get_term($term->term_id, $term->taxonomy)->slug !== $source->slug) {
        fwrite(STDERR, "Could not rename term {$term->term_id}" . (is_wp_error($result) ? ': ' . $result->get_error_message() : '.') . "\n");
        exit(1);
    }
    $report['renamed']++;
}
echo json_encode($report + ['applied' => $apply]), "\n";
