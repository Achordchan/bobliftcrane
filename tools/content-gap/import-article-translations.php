<?php
require_once dirname(__DIR__) . '/backup-dir.php';

/**
 * Creates the Japanese and Chinese versions of the two Industry Insight articles (Polylang). Run after import-articles.php:
 *
 *   wp eval-file tools/content-gap/import-article-translations.php [source-dir] [overwrite]
 *
 * source-dir (default tools/content-gap/source) must hold i18n/<article>.json, one file per article with a "ja" and a "zh" block
 * (title, seo_title, desc, tags, keywords, body, schema). The English article must already exist. A translation that already
 * exists is left alone; pass "overwrite" to replace it (its current fields and meta are saved to a backup file first).
 * Links to pages that have a translation are pointed at the translated address.
 */
$overwrite = in_array('overwrite', $args ?? [], true);
$source_args = array_values(array_filter($args ?? [], static fn($arg): bool => $arg !== 'overwrite'));
$root = rtrim((string) ($source_args[0] ?? getenv('CONTENT_GAP_SOURCE') ?: __DIR__ . '/source'), '/') . '/';
$articles = ['forklift-jib-crane-guide', 'marine-crane-buying-guide'];
$suffix = ['ja' => '-2', 'zh' => '-3'];

if (!function_exists('pll_get_post') || !function_exists('pll_set_post_language')) { fwrite(STDERR, "Polylang is not active.\n"); exit(1); }
$missing = [];
foreach ($articles as $slug) {
    if (!is_file($root . 'i18n/' . $slug . '.json')) { $missing[] = $root . 'i18n/' . $slug . '.json'; }
    $en = get_posts(['post_type' => 'post', 'name' => $slug, 'post_status' => 'publish', 'posts_per_page' => 1, 'lang' => 'en']);
    if (!$en) { $missing[] = 'English article: ' . $slug . ' (run import-articles.php first)'; }
}
$cat_en = get_term_by('slug', 'industry-insight', 'category');
if (!$cat_en) { $missing[] = 'category: industry-insight'; }
if ($missing) { fwrite(STDERR, "Missing:\n  " . implode("\n  ", $missing) . "\n"); exit(1); }

/** Points relative links at the translated page when the target has a translation in $lang. */
function bl_localize_links(string $html, string $lang): string {
    return preg_replace_callback('/href="(\/[^"#?]*)(#[^"]*)?"/', static function (array $m) use ($lang): string {
        $slug = basename(rtrim($m[1], '/'));
        $slug = preg_replace('/\.html$/', '', $slug);
        if ($slug === '') { return $m[0]; }
        $found = get_posts(['post_type' => 'any', 'name' => $slug, 'post_status' => 'publish', 'posts_per_page' => 1, 'lang' => 'en']);
        $translated = $found ? pll_get_post($found[0]->ID, $lang) : 0;
        if (!$translated || $translated === $found[0]->ID) { return $m[0]; }
        return 'href="' . wp_make_link_relative(get_permalink($translated)) . ($m[2] ?? '') . '"';
    }, $html);
}

function bl_backup_translation(int $id, string $slug): void {
    $snapshot = wp_json_encode(['post' => get_post($id, ARRAY_A), 'meta' => get_post_meta($id)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    $file = boblift_tools_backup_dir() . '/backup-content-gap-i18n-' . $slug . '-' . gmdate('Ymd-His') . '.json';
    $bytes = is_string($snapshot) ? file_put_contents($file . '.tmp', $snapshot) : false;
    if (!is_string($snapshot) || $bytes !== strlen($snapshot) || !rename($file . '.tmp', $file)) {
        fwrite(STDERR, "Cannot write backup $file; {$slug} was not changed.\n");
        exit(1);
    }
    echo "backup: $file\n";
}

$report = [];
foreach ($articles as $slug) {
    $data = json_decode(file_get_contents($root . 'i18n/' . $slug . '.json'), true);
    $en = get_posts(['post_type' => 'post', 'name' => $slug, 'post_status' => 'publish', 'posts_per_page' => 1, 'lang' => 'en'])[0];
    $en_schema = json_decode(wp_unslash((string) get_post_meta($en->ID, '_article_schema_jsonld', true)), true);
    $image_url = '';
    foreach (($en_schema['@graph'] ?? []) as $node) { if (($node['@type'] ?? '') === 'BlogPosting') { $image_url = $node['image']['url'] ?? ''; } }
    foreach (['ja', 'zh'] as $lang) {
        $t = $data[$lang] ?? null;
        if (!$t) { fwrite(STDERR, "No $lang block for $slug\n"); exit(1); }
        $new_slug = $slug . $suffix[$lang];
        $existing_id = pll_get_post($en->ID, $lang);
        if ($existing_id && !$overwrite) { $report[] = "$lang $slug: $existing_id (exists, left unchanged; pass \"overwrite\" to replace it)"; continue; }
        if ($existing_id) { bl_backup_translation($existing_id, $new_slug); }
        $cat = pll_get_term($cat_en->term_id, $lang);
        $postarr = [
            'post_type' => 'post', 'post_status' => $existing_id ? get_post_status($existing_id) : 'draft', 'post_title' => $t['title'], 'post_name' => $new_slug,
            'post_content' => bl_localize_links($t['body'], $lang), 'post_excerpt' => $t['desc'], 'post_author' => $en->post_author,
            'post_date' => $en->post_date, 'post_date_gmt' => $en->post_date_gmt, 'post_category' => $cat ? [$cat] : [],
            'comment_status' => 'closed', 'ping_status' => 'closed',
        ];
        if ($existing_id) { $postarr['ID'] = $existing_id; $id = wp_update_post($postarr, true); } else { $id = wp_insert_post($postarr, true); }
        if (is_wp_error($id)) { throw new Exception($id->get_error_message()); }
        pll_set_post_language($id, $lang);
        // Tags are language-specific terms in Polylang.
        $tag_ids = [];
        foreach ($t['tags'] as $name) {
            $term = term_exists($name, 'post_tag');
            $term_id = $term ? (int) (is_array($term) ? $term['term_id'] : $term) : (int) wp_insert_term($name, 'post_tag')['term_id'];
            pll_set_term_language($term_id, $lang);
            $tag_ids[] = $term_id;
        }
        wp_set_post_terms($id, $tag_ids, 'post_tag');
        $thumb = get_post_thumbnail_id($en->ID);
        if ($thumb) { set_post_thumbnail($id, $thumb); }
        update_post_meta($id, '_seo_title', $t['seo_title']);
        update_post_meta($id, '_seo_description', $t['desc']);
        update_post_meta($id, '_seo_keywords', $t['keywords']);
        $schema = wp_json_encode($t['schema'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        // The JSON-LD in source/ still names the old domain; {{home}} follows whichever host serves the site.
        $schema = str_replace('https://www.bobliftcrane.com', '{{home}}', $schema);
        $schema = str_replace('{{home}}/' . $slug . '.html', '{{home}}/' . $new_slug . '.html', $schema);
        // The marine source names the article without ".html"; the English import adds it, so do the same here.
        $schema = str_replace(['{{home}}/' . $slug . '"', '{{home}}/' . $slug . '#'], ['{{home}}/' . $new_slug . '.html"', '{{home}}/' . $new_slug . '.html#'], $schema);
        $schema = str_replace('"item": "{{home}}/Industry-News.html"', '"item": "{{home}}/news.html"', $schema);
        if ($image_url !== '') {
            $schema = preg_replace('#("image":\s*\{\s*"@type":\s*"ImageObject",\s*"url":\s*)"[^"]*"#', '$1' . wp_json_encode($image_url, JSON_UNESCAPED_SLASHES), $schema, 1);
        }
        update_post_meta($id, '_article_schema_jsonld', wp_slash($schema));
        $group = pll_get_post_translations($en->ID);
        $group[$lang] = $id;
        pll_save_post_translations($group);
        if (get_post_status($id) === 'draft') { wp_update_post(['ID' => $id, 'post_status' => 'publish']); }
        $report[] = "$lang $slug: $id " . get_permalink($id);
    }
}
echo implode("\n", $report), "\n";
