<?php
/**
 * Imports the Company News post "BOB-LIFT Crane Enquiries Now Go to info@boblift.com" in all seven languages (Polylang):
 *
 *   wp eval-file tools/company-news/email-switch/import.php [publish]
 *
 * Posts are created as drafts unless "publish" is passed. Running it again updates the posts it created earlier (found by the
 * _bl_news_source meta) instead of adding new ones, and reuses the images it uploaded. Internal links in the source are
 * root-relative English paths; each language gets the path of its Polylang home URL in front (site subfolder included).
 */
if (!defined('WP_CLI') || !WP_CLI) { exit; }
$publish = in_array('publish', $args ?? [], true);
$dir = __DIR__ . '/';
$source_key = 'email-switch-2026-10-21';
$slug = 'bob-lift-crane-email-info-boblift-com';
$date = '2026-10-21 09:00:00';

if (!function_exists('pll_set_post_language')) { fwrite(STDERR, "Polylang is not active.\n"); exit(1); }
$articles = json_decode((string) file_get_contents($dir . 'articles.json'), true);
$schema_template = (string) file_get_contents($dir . 'schema-en.jsonld');
if (!is_array($articles) || $schema_template === '') { fwrite(STDERR, "Missing articles.json or schema-en.jsonld.\n"); exit(1); }
// Company News is found by its English slug and Polylang's translation of it, so term IDs may differ between sites.
$company_news = get_term_by('slug', 'company-news', 'category');
if (!$company_news instanceof WP_Term || pll_get_term_language($company_news->term_id) !== 'en') { fwrite(STDERR, "Missing the English Company News category.\n"); exit(1); }
$category_ids = [];
foreach ($articles as $lang => $a) {
    foreach (['cover', 'diagram'] as $img) {
        if (!is_file($dir . $a[$img])) { fwrite(STDERR, "Missing image {$a[$img]}.\n"); exit(1); }
    }
    $term_id = (int) pll_get_term($company_news->term_id, $lang);
    if ($term_id <= 0 || pll_get_term_language($term_id) !== $lang) { fwrite(STDERR, "Company News has no $lang translation.\n"); exit(1); }
    $category_ids[$lang] = $term_id;
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

function bl_news_attachment(string $file, string $alt, string $key): int {
    $found = get_posts(['post_type' => 'attachment', 'post_status' => 'any', 'meta_key' => '_bl_news_image', 'meta_value' => $key . ':' . basename($file), 'posts_per_page' => 1, 'fields' => 'ids', 'lang' => '']);
    if ($found) { return (int) $found[0]; }
    $tmp = wp_tempnam(basename($file));
    copy($file, $tmp);
    $id = media_handle_sideload(['name' => basename($file), 'tmp_name' => $tmp], 0, $alt);
    if (is_wp_error($id)) { fwrite(STDERR, $id->get_error_message() . "\n"); exit(1); }
    update_post_meta($id, '_bl_news_image', $key . ':' . basename($file));
    update_post_meta($id, '_wp_attachment_image_alt', $alt);
    return (int) $id;
}

/** Path of a language's home page as Polylang builds it ("" for an unprefixed language, "/ja" or "/language/ja" otherwise). */
function bl_news_language_path(string $lang): string {
    $home_path = untrailingslashit((string) wp_parse_url(home_url('/'), PHP_URL_PATH));
    $lang_path = untrailingslashit((string) wp_parse_url((string) pll_home_url($lang), PHP_URL_PATH));
    return str_starts_with($lang_path, $home_path) ? substr($lang_path, strlen($home_path)) : $lang_path;
}

/** Permalink a post has, or will have once published, with the site address replaced by {{home}} for the JSON-LD. */
function bl_news_schema_url(int $id): string {
    $post = clone get_post($id);
    $post->post_status = 'publish';
    return str_replace(untrailingslashit(home_url()), '{{home}}', (string) get_permalink($post));
}

$ids = [];
foreach ($articles as $lang => $a) {
    $prefix = bl_news_language_path($lang);
    // Full path of the language's home page, including any subfolder WordPress is installed in.
    $link_base = untrailingslashit((string) wp_parse_url((string) pll_home_url($lang), PHP_URL_PATH));
    $cover_id = bl_news_attachment($dir . $a['cover'], $a['title'], $source_key);
    $diagram_id = bl_news_attachment($dir . $a['diagram'], 'info@boblift.com: bobliftcrane.com, boblift.com, bobliftloader.com', $source_key);
    $cover_url = (string) wp_get_attachment_url($cover_id);
    $body = str_replace('{{diagram}}', (string) wp_get_attachment_url($diagram_id), (string) $a['body']);
    // Root-relative site links get the language home path in front; external, mailto, tel and wa.me links are left alone.
    $body = preg_replace_callback('#href="(/[^"/][^"]*)"#', static fn(array $m): string => 'href="' . esc_attr($link_base . $m[1]) . '"', $body);

    $existing = get_posts(['post_type' => 'post', 'post_status' => 'any', 'meta_key' => '_bl_news_source', 'meta_value' => $source_key . ':' . $lang, 'posts_per_page' => 1, 'fields' => 'ids', 'lang' => '']);
    $postarr = [
        'post_type' => 'post',
        'post_title' => $a['title'],
        'post_excerpt' => $a['excerpt'],
        'post_content' => $body,
        'post_name' => $slug,
        'post_date' => $date,
        'post_status' => $publish ? 'publish' : ($existing ? get_post_status($existing[0]) : 'draft'),
        'post_category' => [$category_ids[$lang]],
    ];
    if ($existing) { $postarr['ID'] = (int) $existing[0]; }
    $id = $existing ? wp_update_post(wp_slash($postarr), true) : wp_insert_post(wp_slash($postarr), true);
    if (is_wp_error($id)) { fwrite(STDERR, $id->get_error_message() . "\n"); exit(1); }
    pll_set_post_language($id, $lang);
    wp_set_post_categories($id, [$category_ids[$lang]]);
    set_post_thumbnail($id, $cover_id);
    update_post_meta($id, '_bl_news_source', $source_key . ':' . $lang);
    update_post_meta($id, '_seo_title', $a['seo_title']);
    update_post_meta($id, '_seo_description', $a['desc']);
    update_post_meta($id, '_seo_keywords', $a['keywords']);

    $schema = json_decode($schema_template, true);
    foreach ($schema['@graph'] as &$node) {
        if ($node['@type'] === 'NewsArticle') {
            $url = bl_news_schema_url((int) $id);
            $node['@id'] = $url . '#article';
            $node['mainEntityOfPage'] = $url;
            $node['headline'] = $a['title'];
            $node['description'] = $a['desc'];
            $node['keywords'] = $a['keywords'];
            $node['image']['url'] = $cover_url;
            $node['inLanguage'] = $lang;
            $node['articleSection'] = get_term($category_ids[$lang], 'category')->name;
        } elseif ($node['@type'] === 'BreadcrumbList') {
            $home_names = ['en' => 'Home', 'zh' => '首页', 'ja' => 'ホーム', 'es' => 'Inicio', 'ru' => 'Главная', 'fr' => 'Accueil', 'ar' => 'الرئيسية'];
            $node['itemListElement'][0]['name'] = $home_names[$lang] ?? 'Home';
            $node['itemListElement'][0]['item'] = '{{home}}' . $prefix . '/';
            $news_page = get_page_by_path('news', OBJECT, 'page');
            $news_id = $news_page ? (int) pll_get_post($news_page->ID, $lang) : 0;
            $node['itemListElement'][1]['item'] = $news_id ? bl_news_schema_url($news_id) : '{{home}}' . $prefix . '/news.html';
            if ($news_id) {
                $node['itemListElement'][1]['name'] = get_the_title($news_id);
            }
            $node['itemListElement'][2]['name'] = $a['title'];
            $node['itemListElement'][2]['item'] = bl_news_schema_url((int) $id);
        } elseif ($node['@type'] === 'FAQPage') {
            $node['mainEntity'] = array_map(static fn(array $qa): array => ['@type' => 'Question', 'name' => $qa[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa[1]]], $a['faqs']);
        }
    }
    unset($node);
    update_post_meta($id, '_article_schema_jsonld', wp_slash(wp_json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)));
    $ids[$lang] = (int) $id;
    WP_CLI::log(sprintf('%s: %d (%s)', $lang, $id, get_post_status($id)));
}
pll_save_post_translations($ids);
WP_CLI::success('Linked translations: ' . wp_json_encode($ids));
