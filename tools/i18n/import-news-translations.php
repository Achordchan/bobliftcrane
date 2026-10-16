<?php
/**
 * Creates the Japanese and Chinese versions of the English news posts (Polylang).
 *
 *   wp eval-file tools/i18n/import-news-translations.php [translations-dir] [overwrite] [partial]
 *
 * translations-dir (default i18n-export/translations) holds <lang>/<post-id>.json, where <post-id> is the English post's ID
 * in i18n-export/news-en.json and each file has title, excerpt, content, seo_title, seo_description, seo_keywords, tags.
 * English posts are looked up by slug, so the IDs of the target site do not matter. A translation that already exists is
 * left alone; "overwrite" replaces it (the old post and meta go to $BOBLIFT_BACKUP_DIR first).
 * Translations keep the English slug (inc/polylang-slugs.php) and live under /ja/ and /zh/ (Polylang directory mode).
 */
require_once dirname(__DIR__) . '/backup-dir.php';

$overwrite = in_array('overwrite', $args ?? [], true);
// "partial" lets the import skip translations whose English post is not on this site; without it they stop the run.
$partial = in_array('partial', $args ?? [], true);
$positional = array_values(array_filter($args ?? [], static fn($arg): bool => !in_array($arg, ['overwrite', 'partial'], true)));
$base = dirname(__DIR__, 2) . '/i18n-export/';
$dir = rtrim((string) ($positional[0] ?? $base . 'translations'), '/') . '/';
$langs = ['ja', 'zh'];
$fields = ['title', 'excerpt', 'content', 'seo_title', 'seo_description', 'seo_keywords'];

if (!function_exists('pll_get_post') || !function_exists('pll_save_post_translations')) { fwrite(STDERR, "Polylang is not active.\n"); exit(1); }
$configured = function_exists('pll_languages_list') ? pll_languages_list(['fields' => 'slug']) : [];
$unknown = array_diff(array_merge(['en'], $langs), $configured);
if ($unknown) { fwrite(STDERR, 'Polylang has no language ' . implode(', ', $unknown) . "; add it before importing.\n"); exit(1); }
$source = json_decode((string) file_get_contents($base . 'news-en.json'), true);
if (!is_array($source)) { fwrite(STDERR, "Cannot read {$base}news-en.json\n"); exit(1); }
$by_id = [];
foreach ($source as $row) { $by_id[(int) $row['id']] = $row; }

// Preflight: every translation file must parse and hold every field before anything is written.
$jobs = [];
$problems = [];
// Every language needs its directory and the same, non-empty set of files, so a wrong path or partial copy fails here.
$ids_by_lang = [];
foreach ($langs as $lang) {
    $files = is_dir($dir . $lang) ? glob($dir . $lang . '/*.json') : false;
    if (!$files) { $problems[] = "{$dir}{$lang}/: missing or has no translation files"; continue; }
    $ids_by_lang[$lang] = array_map(static fn(string $file): int => (int) basename($file, '.json'), $files);
    sort($ids_by_lang[$lang]);
}
// Every English post the export lists without a translation in a language must have a file for it.
foreach ($ids_by_lang as $lang => $ids) {
    $expected = array_keys(array_filter($by_id, static fn(array $row): bool => empty($row['translations'][$lang])));
    $absent = array_diff($expected, $ids);
    if ($absent) { $problems[] = "{$dir}{$lang}/: no translation for English post ID " . implode(', ', $absent); }
    // On the target site, an English post that has no translation in this language must also have a file, even if the export
    // records one from the source site.
    foreach (array_diff(array_keys($by_id), $ids) as $source_id) {
        $found = get_posts(['post_type' => 'post', 'name' => $by_id[$source_id]['slug'], 'post_status' => 'publish', 'posts_per_page' => 1, 'lang' => 'en']);
        $translated = $found ? (int) pll_get_post($found[0]->ID, $lang) : 0;
        if ($found && (!$translated || $translated === $found[0]->ID)) { $problems[] = "{$by_id[$source_id]['slug']}: no $lang translation on this site and no file in {$dir}{$lang}/"; }
    }
}
foreach ($langs as $lang) {
    foreach (glob($dir . $lang . '/*.json') ?: [] as $file) {
        $id = (int) basename($file, '.json');
        $data = json_decode((string) file_get_contents($file), true);
        if (!isset($by_id[$id])) { $problems[] = "$file: no English post with that ID in news-en.json"; continue; }
        if (!is_array($data)) { $problems[] = "$file: not valid JSON"; continue; }
        foreach ($fields as $field) {
            if (!is_string($data[$field] ?? null)) { $problems[] = "$file: field $field"; }
        }
        $tags = $data['tags'] ?? null;
        if (!is_array($tags) || array_filter($tags, static fn($tag): bool => !is_string($tag) || trim($tag) === '')) { $problems[] = "$file: field tags (a list of names, may be empty)"; }
        if (preg_match('#https?://(?:localhost|127\.0\.0\.1)(?::\d+)?/(?!wp-content/uploads/)#i', (string) ($data['content'] ?? ''))) { $problems[] = "$file: link to a local development address"; }
        if (trim((string) ($data['title'] ?? '')) === '' || trim((string) ($data['content'] ?? '')) === '') { $problems[] = "$file: empty title or content"; }
        $english_links = preg_match_all('/<a\b/i', $by_id[$id]['content']);
        if ($english_links !== preg_match_all('/<a\b/i', (string) ($data['content'] ?? ''))) { $problems[] = "$file: link count differs from the English post"; }
        $en_post = bl_news_english($by_id[$id]);
        if (!$partial && !$en_post) { $problems[] = "$file: English post {$by_id[$id]['slug']} is not published on this site (pass \"partial\" to skip it)"; continue; }
        // Each English category needs its own translation in this language; an English term is never put on a translation.
        foreach ($en_post ? wp_get_post_categories($en_post->ID) : [] as $cat_id) {
            if (!pll_get_term($cat_id, $lang)) { $problems[] = "category " . get_cat_name($cat_id) . " (ID $cat_id) has no $lang translation; add it in Polylang before importing"; }
        }
        $jobs[] = [$lang, $id, $data];
    }
}
if ($problems) { fwrite(STDERR, "Problems:\n  " . implode("\n  ", array_unique($problems)) . "\n"); exit(1); }

/** The English post for a source row on the target site, found by slug. */
function bl_news_english(array $row): ?WP_Post {
    $found = get_posts(['post_type' => 'post', 'name' => $row['slug'], 'post_status' => 'publish', 'posts_per_page' => 1, 'lang' => 'en']);
    return $found ? $found[0] : null;
}

/** Old-site absolute links become site-relative, and point at the translated page where there is one. */
function bl_news_links(string $html, string $lang, array $pending = []): string {
    // Upload URLs from the export (local development host or the old domain) point at this site's own uploads.
    $uploads = trailingslashit(wp_get_upload_dir()['baseurl']);
    $html = preg_replace('#https?://(?:localhost|127\.0\.0\.1|(?:www\.)?bobliftcrane\.com)(?::\d+)?/wp-content/uploads/#i', $uploads, $html);
    // A bare origin is the home page, so it becomes "/" rather than an empty link.
    $html = preg_replace('#https?://(?:www\.)?bobliftcrane\.com(?="|\')#i', '/', $html);
    $html = preg_replace('#https?://(?:www\.)?bobliftcrane\.com(?=/)#i', '', $html);
    // Old-site paths are root paths; on a site installed under a subdirectory they live below the site's home path.
    $home_path = untrailingslashit((string) wp_parse_url(home_url('/'), PHP_URL_PATH));
    // Root-relative links in either quote style; the query string and fragment are kept as they are.
    return preg_replace_callback('#href=(["\'])(/(?!/)[^"\'\#?]*)((?:\?[^"\'\#]*)?(?:\#[^"\']*)?)\1#', static function (array $m) use ($lang, $pending, $home_path): string {
        [, $q, $path, $tail] = $m;
        $site_link = 'href=' . $q . esc_attr($home_path . $path) . $tail . $q;
        $slug = preg_replace('/\.html$/', '', basename(rtrim($path, '/')));
        if ($slug === '') { return $site_link; }
        // The link's target is the English post whose own public path is exactly this one, so a custom address that merely
        // ends in another post's slug is never swapped for an unrelated translation.
        $candidates = get_posts(['post_type' => 'any', 'name' => $slug, 'post_status' => 'publish', 'posts_per_page' => -1, 'lang' => 'en']);
        // Pages served at a saved custom address (theme meta _boblift_public_url) are candidates too, whatever their slug.
        $custom_path = function_exists('boblift_normalize_public_path') ? boblift_normalize_public_path($path) : $path;
        $candidates = array_merge($candidates, get_posts(['post_type' => 'any', 'post_status' => 'publish', 'posts_per_page' => -1, 'lang' => 'en', 'meta_key' => '_boblift_public_url', 'meta_value' => $custom_path]));
        $found = array_values(array_filter($candidates, static fn(WP_Post $post): bool => untrailingslashit((string) wp_parse_url(get_permalink($post), PHP_URL_PATH)) === untrailingslashit($home_path . $path)));
        $translated = $found ? pll_get_post($found[0]->ID, $lang) : 0;
        // Only a published translation replaces the link; a draft or private one would be unreachable for visitors.
        // $pending holds translations this run is about to publish.
        if (!$translated || $translated === $found[0]->ID || (get_post_status($translated) !== 'publish' && !isset($pending[$translated]))) { return $site_link; }
        return 'href=' . $q . wp_make_link_relative(get_permalink($translated)) . $tail . $q;
    }, $html);
}

/** Term assignments of a post by taxonomy, so a backup can restore what an overwrite replaces. */
function bl_news_terms(int $id): array {
    $terms = [];
    foreach (get_object_taxonomies(get_post_type($id)) as $taxonomy) {
        $assigned = wp_get_object_terms($id, $taxonomy, ['fields' => 'all']);
        $terms[$taxonomy] = is_wp_error($assigned) ? [] : array_map(static fn(WP_Term $term): array => ['term_id' => $term->term_id, 'slug' => $term->slug, 'name' => $term->name], $assigned);
    }
    return $terms;
}

/** The tag with this name in this language, created (and assigned the language) when it does not exist yet. */
function bl_news_tag(string $name, string $lang): int {
    $terms = get_terms(['taxonomy' => 'post_tag', 'name' => $name, 'hide_empty' => false, 'lang' => $lang]);
    foreach (is_array($terms) ? $terms : [] as $term) {
        if (pll_get_term_language($term->term_id) === $lang) { return (int) $term->term_id; }
    }
    $inserted = wp_insert_term($name, 'post_tag');
    if (is_wp_error($inserted)) {
        // The name (or its slug) is taken by another language's tag: create this language's own term beside it.
        $inserted = wp_insert_term($name, 'post_tag', ['slug' => sanitize_title($name) . '-' . $lang]);
    }
    if (is_wp_error($inserted)) { fwrite(STDERR, "tag $name ($lang): " . $inserted->get_error_message() . "\n"); exit(1); }
    pll_set_term_language((int) $inserted['term_id'], $lang);
    return (int) $inserted['term_id'];
}

/** A draft an interrupted run inserted for this English post and language before linking it into the translation group. */
function bl_news_unlinked_staged(int $en_id, string $lang): int {
    $found = get_posts(['post_type' => 'post', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids', 'lang' => '',
        'meta_query' => [['key' => '_news_i18n_source', 'value' => $en_id . ':' . $lang], ['key' => '_news_i18n_staging', 'compare' => 'EXISTS']]]);
    return $found ? (int) $found[0] : 0;
}

function bl_news_backup(int $id, string $slug, string $lang): void {
    $snapshot = wp_json_encode(['post' => get_post($id, ARRAY_A), 'meta' => get_post_meta($id), 'terms' => bl_news_terms($id)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    $file = boblift_tools_backup_dir() . '/backup-news-i18n-' . $lang . '-' . $slug . '-' . gmdate('Ymd-His') . '.json';
    if (!is_string($snapshot) || file_put_contents($file, $snapshot) !== strlen($snapshot)) { fwrite(STDERR, "Cannot write backup $file; $slug was not changed.\n"); exit(1); }
}

$report = ['created' => 0, 'updated' => 0, 'kept' => 0, 'missing_english' => 0, 'failed' => 0];
// Pass 1 creates or updates every translation; links are resolved in pass 2, once all of them exist.
$done = [];
foreach ($jobs as [$lang, $id, $data]) {
    $row = $by_id[$id];
    $en = bl_news_english($row);
    if (!$en) { $report['missing_english']++; fwrite(STDERR, "English post {$row['slug']} is not on this site; skipped for $lang.\n"); continue; }
    $existing = (int) pll_get_post($en->ID, $lang);
    // pll_get_post returns the English post itself when the language has none.
    if ($existing === $en->ID) { $existing = 0; }
    if (!$existing) { $existing = bl_news_unlinked_staged($en->ID, $lang); }
    // A translation an earlier run created but did not finish publishing carries the staging marker and is resumed.
    // The marker is "publish" for a translation the importer created, "update" for an overwritten one.
    $marker = $existing ? (string) get_post_meta($existing, '_news_i18n_staging', true) : '';
    $staged = $marker !== '';
    if ($existing && !$overwrite && !$staged) { $report['kept']++; continue; }
    if ($existing && !$staged) { bl_news_backup($existing, $row['slug'], $lang); }
    $cats = [];
    foreach (wp_get_post_categories($en->ID) as $cat_id) {
        $cats[] = (int) pll_get_term($cat_id, $lang);
    }
    $postarr = [
        'post_type' => 'post', 'post_status' => $existing ? get_post_status($existing) : 'draft', 'post_title' => $data['title'], 'post_name' => $en->post_name,
        // Upload URLs and links are normalized now, so even a published post never shows the raw export; pass 2 re-resolves links.
        'post_content' => bl_news_links($data['content'], $lang), 'post_excerpt' => $data['excerpt'], 'post_author' => $en->post_author,
        'post_category' => $cats,
        'comment_status' => $en->comment_status, 'ping_status' => $en->ping_status,
        // A password-protected English post keeps its protection in every language.
        'post_password' => $en->post_password !== '' ? $en->post_password : ($existing ? (string) get_post($existing)->post_password : ''),
    ];
    if ($existing) {
        // An existing translation keeps its own dates, so a scheduled one stays scheduled.
        $postarr['ID'] = $existing;
        if (!$staged) { update_post_meta($existing, '_news_i18n_staging', 'update'); }
        $new_id = wp_update_post(wp_slash($postarr), true);
    } else {
        $postarr['post_date'] = $en->post_date;
        $postarr['post_date_gmt'] = $en->post_date_gmt;
        // The source marker lets a rerun find this draft even if the run stops before it joins the translation group.
        $postarr['meta_input'] = ['_news_i18n_staging' => 'publish', '_news_i18n_source' => $en->ID . ':' . $lang];
        $new_id = wp_insert_post(wp_slash($postarr), true);
    }
    if (is_wp_error($new_id)) { fwrite(STDERR, $new_id->get_error_message() . "\n"); exit(1); }
    pll_set_post_language($new_id, $lang);
    $group = pll_get_post_translations($en->ID);
    $group[$lang] = $new_id;
    pll_save_post_translations($group);
    // The file's tag list replaces the post's tags in its own language; an empty list clears them.
    $tag_ids = wp_set_post_terms($new_id, array_map(static fn(string $name): int => bl_news_tag(trim($name), $lang), $data['tags']), 'post_tag');
    if (is_wp_error($tag_ids)) { fwrite(STDERR, "tags of $new_id ($lang): " . $tag_ids->get_error_message() . "\n"); exit(1); }
    $thumb = get_post_thumbnail_id($en->ID);
    if ($thumb) { set_post_thumbnail($new_id, $thumb); }
    update_post_meta($new_id, '_seo_title', wp_slash($data['seo_title']));
    update_post_meta($new_id, '_seo_description', wp_slash($data['seo_description']));
    update_post_meta($new_id, '_seo_keywords', wp_slash($data['seo_keywords']));
    // Only a translation this importer created is published; an existing one keeps the status its editor gave it.
    $done[(int) $new_id] = [$lang, $data, get_post_meta($new_id, '_news_i18n_staging', true) === 'publish'];
}

// Pass 2: localize links (every translation now exists) and publish the ones this importer created.
$pending = array_filter($done, static fn(array $item): bool => $item[2]);
foreach ($done as $new_id => [$lang, $data, $publish]) {
    $update = ['ID' => $new_id, 'post_content' => bl_news_links($data['content'], $lang, $pending)];
    if ($publish && get_post_status($new_id) === 'draft') { $update['post_status'] = 'publish'; }
    $result = wp_update_post(wp_slash($update), true);
    if (is_wp_error($result) || !$result || ($publish && get_post_status($new_id) !== 'publish')) {
        $report['failed']++;
        fwrite(STDERR, "Could not finish translation $new_id ($lang); it stays staged and a rerun will retry.\n");
        continue;
    }
    delete_post_meta($new_id, '_news_i18n_staging');
    delete_post_meta($new_id, '_news_i18n_source');
    $publish ? $report['created']++ : $report['updated']++;
}
echo json_encode($report), "\n";
exit($report['failed'] ? 1 : 0);
