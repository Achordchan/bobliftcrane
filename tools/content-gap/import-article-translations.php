<?php
require_once dirname(__DIR__) . '/backup-dir.php';

/**
 * Creates the Japanese and Chinese versions of the two Industry Insight articles (Polylang). Run after import-articles.php:
 *
 *   wp eval-file tools/content-gap/import-article-translations.php [source-dir] [overwrite]
 *
 * source-dir (default tools/content-gap/source) must hold i18n/<article>.json, one file per article with a "ja" and a "zh" block
 * (title, seo_title, desc, tags, keywords, body, schema). The English article must already exist. A translation that already
 * exists is left alone; pass "overwrite" to replace it (its current fields, meta and terms are saved to a backup file first, and
 * its slug and status are kept). Everything (categories, images) is checked before any post is touched.
 * Links to pages that have a published translation are pointed at the translated address; all other internal links point at
 * the English page. Images reuse the attachments the English import uploaded.
 */
$overwrite = in_array('overwrite', $args ?? [], true);
$source_args = array_values(array_filter($args ?? [], static fn($arg): bool => $arg !== 'overwrite'));
$root = rtrim((string) ($source_args[0] ?? getenv('CONTENT_GAP_SOURCE') ?: __DIR__ . '/source'), '/') . '/';
$articles = ['forklift-jib-crane-guide', 'marine-crane-buying-guide'];
$langs = ['ja', 'zh'];

if (!function_exists('pll_get_post') || !function_exists('pll_set_post_language')) { fwrite(STDERR, "Polylang is not active.\n"); exit(1); }

/** Base URL of the English site (the default language), used for links that have no translation. */
function bl_english_base(): string {
    $base = function_exists('pll_home_url') && function_exists('pll_default_language') ? pll_home_url(pll_default_language()) : home_url('/');
    return untrailingslashit((string) $base);
}

/** The attachment the English import uploaded for an image file name (it records the source file name in _bl_gap_source). */
function bl_attachment_for(string $src): int {
    $found = get_posts(['post_type' => 'attachment', 'post_status' => 'any', 'meta_key' => '_bl_gap_source', 'meta_value' => basename($src), 'posts_per_page' => 1, 'fields' => 'ids', 'lang' => '']);
    return $found ? (int) $found[0] : 0;
}

/** Permalink a post has, or will have once published (drafts would otherwise give ?p=ID). */
function bl_public_permalink(int $id): string {
    $post = clone get_post($id);
    if ($post->post_status !== 'publish') {
        // Drafts get their unique slug only on publication, so work out the one they will receive.
        $post->post_name = wp_unique_post_slug($post->post_name !== '' ? $post->post_name : sanitize_title($post->post_title), $post->ID, 'publish', $post->post_type, $post->post_parent);
        $post->post_status = 'publish';
    }
    return (string) get_permalink($post);
}

/** Published post or page in the default language with this slug. */
function bl_english_post(string $slug): ?WP_Post {
    $found = get_posts(['post_type' => 'any', 'name' => $slug, 'post_status' => 'publish', 'posts_per_page' => 1, 'lang' => pll_default_language()]);
    return $found ? $found[0] : null;
}

/** Points internal links at the published translation in $lang when there is one, otherwise at the English page (absolute URLs). */
function bl_localize_links(string $html, string $lang): string {
    return preg_replace_callback('#href="(/(?!/)[^"\#?]*)(\?[^"\#]*)?(\#[^"]*)?"#', static function (array $m) use ($lang): string {
        $tail = ($m[2] ?? '') . ($m[3] ?? '');
        $slug = preg_replace('/\.html$/', '', basename(rtrim($m[1], '/')));
        if ($slug !== '') {
            $en = bl_english_post($slug);
            $translated = $en ? (int) pll_get_post($en->ID, $lang) : 0;
            if ($translated && $translated !== $en->ID && get_post_status($translated) === 'publish') {
                return 'href="' . esc_url(get_permalink($translated) . $tail) . '"';
            }
            if (!$en && function_exists('pll_get_term')) {
                foreach (get_taxonomies(['public' => true]) as $taxonomy) {
                    $term = get_term_by('slug', $slug, $taxonomy);
                    $term_tr = $term ? (int) pll_get_term($term->term_id, $lang) : 0;
                    if ($term_tr && $term_tr !== $term->term_id) {
                        $link = get_term_link($term_tr, $taxonomy);
                        if (!is_wp_error($link)) { return 'href="' . esc_url($link . $tail) . '"'; }
                    }
                }
            }
        }
        $en = $slug !== '' ? bl_english_post($slug) : null;
        return 'href="' . esc_url(($en ? get_permalink($en) : bl_english_base() . $m[1]) . $tail) . '"';
    }, $html);
}

/** Swaps each inline image for the attachment the English import uploaded. */
function bl_localize_images(string $html): string {
    return preg_replace_callback('/(<img\b[^>]*\bsrc=")([^"]+)(")/i', static function (array $m): string {
        $id = bl_attachment_for($m[2]);
        if (!$id) { throw new Exception('no uploaded image for ' . $m[2]); }
        return $m[1] . esc_url((string) wp_get_attachment_url($id)) . $m[3];
    }, $html);
}

/** Term assignments of a post by taxonomy, so a backup can restore what an import replaces. */
function bl_post_terms(int $id): array {
    $terms = [];
    foreach (get_object_taxonomies(get_post_type($id)) as $taxonomy) {
        $assigned = wp_get_object_terms($id, $taxonomy, ['fields' => 'all']);
        $terms[$taxonomy] = is_wp_error($assigned) ? [] : array_map(static fn(WP_Term $term): array => ['term_id' => $term->term_id, 'slug' => $term->slug, 'name' => $term->name], $assigned);
    }
    return $terms;
}

function bl_backup_translation(int $id, string $name): void {
    $snapshot = wp_json_encode(['post' => get_post($id, ARRAY_A), 'meta' => get_post_meta($id), 'terms' => bl_post_terms($id)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    $file = boblift_tools_backup_dir() . '/backup-content-gap-i18n-' . $name . '-' . gmdate('Ymd-His') . '.json';
    $bytes = is_string($snapshot) ? file_put_contents($file . '.tmp', $snapshot) : false;
    if (!is_string($snapshot) || $bytes !== strlen($snapshot) || !rename($file . '.tmp', $file)) {
        fwrite(STDERR, "Cannot write backup $file; {$name} was not changed.\n");
        exit(1);
    }
    echo "backup: $file\n";
}

/** Tag term in $lang with this name; a term of another language is never reused or reassigned. */
function bl_tag_in_language(string $name, string $lang): int {
    $terms = get_terms(['taxonomy' => 'post_tag', 'name' => $name, 'hide_empty' => false, 'lang' => $lang]);
    foreach (is_array($terms) ? $terms : [] as $term) {
        if (pll_get_term_language($term->term_id) === $lang) { return (int) $term->term_id; }
    }
    $inserted = wp_insert_term($name, 'post_tag');
    if (is_wp_error($inserted)) {
        // The name (or its slug) is taken by another language's tag: create this language's own term beside it.
        $inserted = wp_insert_term($name, 'post_tag', ['slug' => sanitize_title($name) . '-' . $lang]);
    }
    if (is_wp_error($inserted)) { throw new Exception("tag $name ($lang): " . $inserted->get_error_message()); }
    pll_set_term_language((int) $inserted['term_id'], $lang);
    return (int) $inserted['term_id'];
}

/** Article JSON-LD with this translation's own addresses and the cover image. */
function bl_translation_schema(array $schema, int $id, string $lang, string $title, string $news_label, string $home_label, int $cover_id): string {
    $url = bl_public_permalink($id);
    $news = get_page_by_path('news');
    $news_tr = $news ? (int) pll_get_post($news->ID, $lang) : 0;
    $news_url = $news_tr && get_post_status($news_tr) === 'publish' ? get_permalink($news_tr) : ($news ? get_permalink($news) : bl_english_base() . '/news.html');
    $image = '';
    if ($cover_id) {
        $meta = wp_get_attachment_metadata($cover_id);
        $image = ['@type' => 'ImageObject', 'url' => (string) wp_get_attachment_url($cover_id)];
        if (!empty($meta['width']) && !empty($meta['height'])) { $image['width'] = (int) $meta['width']; $image['height'] = (int) $meta['height']; }
    }
    foreach ($schema['@graph'] as &$node) {
        if (($node['@type'] ?? '') === 'BlogPosting') {
            $node['@id'] = $url . '#article';
            $node['mainEntityOfPage'] = $url;
            if ($image) { $node['image'] = $image; } else { unset($node['image']); }
        }
        if (($node['@type'] ?? '') === 'BreadcrumbList') {
            $node['itemListElement'] = [
                ['@type' => 'ListItem', 'position' => 1, 'name' => $home_label, 'item' => trailingslashit((string) pll_home_url($lang))],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $news_label, 'item' => $news_url],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $title, 'item' => $url],
            ];
        }
    }
    unset($node);
    $json = wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    // Site-wide nodes (organization, author) still name the old domain; {{home}} follows whichever host serves the site.
    return str_replace(['https://www.bobliftcrane.com/', 'https://www.bobliftcrane.com"'], ['{{home}}/', '{{home}}"'], (string) $json);
}

// Preflight: sources, English articles, translated categories and every image, before any post is written.
$missing = [];
$sources = [];
$cat_en = get_term_by('slug', 'industry-insight', 'category');
if (!$cat_en) { $missing[] = 'category: industry-insight'; }
$cats = [];
foreach ($langs as $lang) {
    $cats[$lang] = $cat_en ? (int) pll_get_term($cat_en->term_id, $lang) : 0;
    if ($cat_en && !$cats[$lang]) { $missing[] = "category: industry-insight has no $lang translation"; }
}
foreach ($articles as $slug) {
    $file = $root . 'i18n/' . $slug . '.json';
    $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
    if (!is_array($data)) { $missing[] = $file; continue; }
    $sources[$slug] = $data;
    $en = get_posts(['post_type' => 'post', 'name' => $slug, 'post_status' => 'publish', 'posts_per_page' => 1, 'lang' => pll_default_language()]);
    if (!$en) { $missing[] = 'English article: ' . $slug . ' (run import-articles.php first)'; }
    foreach ($langs as $lang) {
        $t = $data[$lang] ?? null;
        if (!is_array($t) || empty($t['body']) || empty($t['schema']['@graph'])) { $missing[] = "$slug: no complete $lang block"; continue; }
        preg_match_all('/<img\b[^>]*\bsrc="([^"]+)"/i', $t['body'], $srcs);
        foreach (array_unique($srcs[1]) as $src) {
            if (!bl_attachment_for($src)) { $missing[] = "$slug ($lang) image: " . basename($src) . ' (run import-articles.php first)'; }
        }
    }
}
if ($missing) { fwrite(STDERR, "Missing:\n  " . implode("\n  ", $missing) . "\n"); exit(1); }

$report = [];
foreach ($articles as $slug) {
    $data = $sources[$slug];
    $en = get_posts(['post_type' => 'post', 'name' => $slug, 'post_status' => 'publish', 'posts_per_page' => 1, 'lang' => pll_default_language()])[0];
    $cover_id = (int) get_post_thumbnail_id($en->ID);
    foreach ($langs as $lang) {
        $t = $data[$lang];
        $existing_id = (int) pll_get_post($en->ID, $lang);
        if ($existing_id === $en->ID) { $existing_id = 0; }
        $resuming = $existing_id && get_post_status($existing_id) === 'draft' && get_post_meta($existing_id, '_content_gap_staging', true) === '1';
        if ($existing_id && !$overwrite && !$resuming) { $report[] = "$lang $slug: $existing_id (exists, left unchanged; pass \"overwrite\" to replace it)"; continue; }
        if ($existing_id && !$resuming) { bl_backup_translation($existing_id, $slug . '-' . $lang); }
        $postarr = [
            // New translations are staged as drafts and published once terms and meta are complete (an interrupted run resumes).
            'post_type' => 'post', 'post_status' => $existing_id ? get_post_status($existing_id) : 'draft', 'post_title' => $t['title'],
            'post_content' => bl_localize_images(bl_localize_links($t['body'], $lang)), 'post_excerpt' => $t['desc'], 'post_author' => $en->post_author,
            'post_date' => $en->post_date, 'post_date_gmt' => $en->post_date_gmt, 'post_category' => [$cats[$lang]],
            'comment_status' => 'closed', 'ping_status' => 'closed',
        ];
        if ($existing_id) {
            // An existing translation keeps its slug, so its address does not change.
            $postarr['ID'] = $existing_id;
            $id = wp_update_post($postarr, true);
        } else {
            $postarr['post_name'] = $slug;
            $id = wp_insert_post($postarr, true);
        }
        if (is_wp_error($id)) { throw new Exception($id->get_error_message()); }
        if (!$existing_id) { update_post_meta($id, '_content_gap_staging', '1'); }
        pll_set_post_language($id, $lang);
        $group = pll_get_post_translations($en->ID);
        $group[$lang] = $id;
        pll_save_post_translations($group);
        wp_set_post_terms($id, array_map(static fn(string $name): int => bl_tag_in_language($name, $lang), $t['tags']), 'post_tag');
        if ($cover_id) { set_post_thumbnail($id, $cover_id); }
        update_post_meta($id, '_seo_title', $t['seo_title']);
        update_post_meta($id, '_seo_description', $t['desc']);
        update_post_meta($id, '_seo_keywords', $t['keywords']);
        if (get_post_status($id) === 'draft' && get_post_meta($id, '_content_gap_staging', true) === '1') {
            $published = wp_update_post(['ID' => $id, 'post_status' => 'publish'], true);
            // The staging marker is removed only once the post really is published; otherwise a rerun retries the publication.
            if (is_wp_error($published) || !$published || get_post_status($id) !== 'publish') {
                fwrite(STDERR, "Could not publish $lang $slug; it stays staged and a rerun will retry.\n");
                continue;
            }
            delete_post_meta($id, '_content_gap_staging');
        }
        // Written after publication so @id, mainEntityOfPage and the breadcrumb carry the address the post really has.
        update_post_meta($id, '_article_schema_jsonld', wp_slash(bl_translation_schema($t['schema'], $id, $lang, $t['title'], $t['news'] ?? $t['section'], $t['home'] ?? 'Home', $cover_id)));
        $report[] = "$lang $slug: $id " . get_permalink($id);
    }
}
echo implode("\n", $report), "\n";
