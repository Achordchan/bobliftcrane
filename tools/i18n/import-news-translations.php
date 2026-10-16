<?php
/**
 * Creates the Japanese and Chinese versions of the English news posts (Polylang).
 *
 *   wp eval-file tools/i18n/import-news-translations.php [translations-dir] [overwrite]
 *
 * translations-dir (default i18n-export/translations) holds <lang>/<post-id>.json, where <post-id> is the English post's ID
 * in i18n-export/news-en.json and each file has title, excerpt, content, seo_title, seo_description, seo_keywords, tags.
 * English posts are looked up by slug, so the IDs of the target site do not matter. A translation that already exists is
 * left alone; "overwrite" replaces it (the old post and meta go to $BOBLIFT_BACKUP_DIR first).
 * Translations keep the English slug (inc/polylang-slugs.php) and live under /ja/ and /zh/ (Polylang directory mode).
 */
require_once dirname(__DIR__) . '/backup-dir.php';

$overwrite = in_array('overwrite', $args ?? [], true);
$positional = array_values(array_filter($args ?? [], static fn($arg): bool => $arg !== 'overwrite'));
$base = dirname(__DIR__, 2) . '/i18n-export/';
$dir = rtrim((string) ($positional[0] ?? $base . 'translations'), '/') . '/';
$langs = ['ja', 'zh'];
$fields = ['title', 'excerpt', 'content', 'seo_title', 'seo_description', 'seo_keywords'];

if (!function_exists('pll_get_post') || !function_exists('pll_save_post_translations')) { fwrite(STDERR, "Polylang is not active.\n"); exit(1); }
$source = json_decode((string) file_get_contents($base . 'news-en.json'), true);
if (!is_array($source)) { fwrite(STDERR, "Cannot read {$base}news-en.json\n"); exit(1); }
$by_id = [];
foreach ($source as $row) { $by_id[(int) $row['id']] = $row; }

// Preflight: every translation file must parse and hold every field before anything is written.
$jobs = [];
$problems = [];
foreach ($langs as $lang) {
    foreach (glob($dir . $lang . '/*.json') ?: [] as $file) {
        $id = (int) basename($file, '.json');
        $data = json_decode((string) file_get_contents($file), true);
        if (!isset($by_id[$id])) { $problems[] = "$file: no English post with that ID in news-en.json"; continue; }
        if (!is_array($data)) { $problems[] = "$file: not valid JSON"; continue; }
        foreach ($fields as $field) {
            if (!is_string($data[$field] ?? null)) { $problems[] = "$file: field $field"; }
        }
        if (trim((string) ($data['title'] ?? '')) === '' || trim((string) ($data['content'] ?? '')) === '') { $problems[] = "$file: empty title or content"; }
        $english_links = preg_match_all('/<a\b/i', $by_id[$id]['content']);
        if ($english_links !== preg_match_all('/<a\b/i', (string) ($data['content'] ?? ''))) { $problems[] = "$file: link count differs from the English post"; }
        $jobs[] = [$lang, $id, $data];
    }
}
if ($problems) { fwrite(STDERR, "Problems:\n  " . implode("\n  ", $problems) . "\n"); exit(1); }

/** The English post for a source row on the target site, found by slug. */
function bl_news_english(array $row): ?WP_Post {
    $found = get_posts(['post_type' => 'post', 'name' => $row['slug'], 'post_status' => 'publish', 'posts_per_page' => 1, 'lang' => 'en']);
    return $found ? $found[0] : null;
}

/** Old-site absolute links become site-relative, and point at the translated page where there is one. */
function bl_news_links(string $html, string $lang): string {
    $html = preg_replace('#https?://(?:www\.)?bobliftcrane\.com(?=/|"|\')#i', '', $html);
    return preg_replace_callback('/href="(\/[^"#?]*)(#[^"]*)?"/', static function (array $m) use ($lang): string {
        $slug = preg_replace('/\.html$/', '', basename(rtrim($m[1], '/')));
        if ($slug === '') { return $m[0]; }
        $found = get_posts(['post_type' => 'any', 'name' => $slug, 'post_status' => 'publish', 'posts_per_page' => 1, 'lang' => 'en']);
        $translated = $found ? pll_get_post($found[0]->ID, $lang) : 0;
        if (!$translated || $translated === $found[0]->ID) { return $m[0]; }
        return 'href="' . wp_make_link_relative(get_permalink($translated)) . ($m[2] ?? '') . '"';
    }, $html);
}

function bl_news_backup(int $id, string $slug, string $lang): void {
    $snapshot = wp_json_encode(['post' => get_post($id, ARRAY_A), 'meta' => get_post_meta($id)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    $file = boblift_tools_backup_dir() . '/backup-news-i18n-' . $lang . '-' . $slug . '-' . gmdate('Ymd-His') . '.json';
    if (!is_string($snapshot) || file_put_contents($file, $snapshot) !== strlen($snapshot)) { fwrite(STDERR, "Cannot write backup $file; $slug was not changed.\n"); exit(1); }
}

$report = ['created' => 0, 'kept' => 0, 'missing_english' => 0];
foreach ($jobs as [$lang, $id, $data]) {
    $row = $by_id[$id];
    $en = bl_news_english($row);
    if (!$en) { $report['missing_english']++; fwrite(STDERR, "English post {$row['slug']} is not on this site; skipped for $lang.\n"); continue; }
    $existing = (int) pll_get_post($en->ID, $lang);
    if ($existing && !$overwrite) { $report['kept']++; continue; }
    if ($existing) { bl_news_backup($existing, $row['slug'], $lang); }
    $cats = [];
    foreach (wp_get_post_categories($en->ID) as $cat_id) {
        $cats[] = (int) (pll_get_term($cat_id, $lang) ?: $cat_id);
    }
    $postarr = [
        'post_type' => 'post', 'post_status' => $existing ? get_post_status($existing) : 'draft', 'post_title' => $data['title'], 'post_name' => $en->post_name,
        'post_content' => bl_news_links($data['content'], $lang), 'post_excerpt' => $data['excerpt'], 'post_author' => $en->post_author,
        'post_date' => $en->post_date, 'post_date_gmt' => $en->post_date_gmt, 'post_category' => $cats,
        'comment_status' => $en->comment_status, 'ping_status' => $en->ping_status,
    ];
    if ($existing) { $postarr['ID'] = $existing; $new_id = wp_update_post($postarr, true); } else { $new_id = wp_insert_post($postarr, true); }
    if (is_wp_error($new_id)) { fwrite(STDERR, $new_id->get_error_message() . "\n"); exit(1); }
    pll_set_post_language($new_id, $lang);
    $group = pll_get_post_translations($en->ID);
    $group[$lang] = $new_id;
    pll_save_post_translations($group);
    $thumb = get_post_thumbnail_id($en->ID);
    if ($thumb) { set_post_thumbnail($new_id, $thumb); }
    update_post_meta($new_id, '_seo_title', $data['seo_title']);
    update_post_meta($new_id, '_seo_description', $data['seo_description']);
    update_post_meta($new_id, '_seo_keywords', $data['seo_keywords']);
    if (get_post_status($new_id) === 'draft') { wp_update_post(['ID' => $new_id, 'post_status' => 'publish']); }
    $report['created']++;
}
echo json_encode($report), "\n";
