<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helpers for the news article page (single.php): table of contents, reading time and related products.
 */

/**
 * Gives every <h2> in the article an id and returns [content, [[id, text], ...]].
 * Existing ids are kept, so links shared from an older version of the page still land.
 */
function boblift_news_article_toc(string $content): array
{
    $items = [];
    // Reserve every id already in the article first, so generated ids never collide with an explicit anchor further down.
    // Attribute values are HTML-encoded (care&amp;maintenance); ids are compared and linked in their decoded DOM form.
    $decode_id = static fn(array $m): string => html_entity_decode((string) ($m[1] ?? '') . (string) ($m[2] ?? '') . (string) ($m[3] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // Opening tags are read attribute by attribute, so "id=" inside another attribute's value or in the text is not reserved.
    $attribute_pattern = '/\s+([^\s=>\/"\']+)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'>]+)))?/';
    $used = [];
    preg_match_all('#<[a-z][^\s/>]*((?:\s+[^\s=>/"\']+(?:\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s"\'>]+))?)*)\s*/?>#i', $content, $tags);
    foreach ($tags[1] as $tag_attributes) {
        preg_match_all($attribute_pattern, $tag_attributes, $tag_tokens, PREG_SET_ORDER);
        foreach ($tag_tokens as $token) {
            if (strtolower($token[1]) === 'id' && ($existing = $decode_id(array_slice($token, 1))) !== '') {
                $used[$existing] = true;
            }
        }
    }
    // The opening tag's attributes are matched value by value, so a ">" inside a quoted value does not end the tag.
    $h2_pattern = '#<h2((?:\s+[^\s=>/"\']+(?:\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s"\'>]+))?)*)\s*>(.*?)</h2>#is';
    $content = (string) preg_replace_callback($h2_pattern, static function (array $match) use (&$items, &$used, $decode_id, $attribute_pattern): string {
        $attributes = (string) ($match[1] ?? '');
        $text = trim(html_entity_decode(wp_strip_all_tags($match[2]), ENT_QUOTES, 'UTF-8'));
        if ($text === '') {
            return $match[0];
        }
        // Attributes are read token by token, so "id=" inside another attribute's quoted value is not taken for the id.
        preg_match_all($attribute_pattern, $attributes, $tokens, PREG_SET_ORDER);
        $id = '';
        $kept = '';
        foreach ($tokens as $token) {
            if (strtolower($token[1]) === 'id') {
                $id = $id !== '' ? $id : $decode_id(array_slice($token, 1));
                continue;
            }
            $kept .= $token[0];
        }
        if ($id === '') {
            // sanitize_title() percent-encodes non-ASCII letters; decode them so the id and the TOC fragment are the same text.
            $base = (string) preg_replace('/\s+/u', '-', rawurldecode(sanitize_title($text)));
            $base = $base !== '' ? $base : 'section';
            $id = $base;
            for ($n = 2; isset($used[$id]); $n++) {
                $id = $base . '-' . $n;
            }
            // An empty id attribute is replaced, not left in front of the generated one.
            $attributes = $kept . ' id="' . esc_attr($id) . '"';
        }
        $used[$id] = true;
        $items[] = ['id' => $id, 'text' => $text];
        return '<h2' . $attributes . '>' . $match[2] . '</h2>';
    }, $content);
    return [$content, $items];
}

/** Minutes to read: ~220 English words or ~400 CJK characters a minute. */
function boblift_news_reading_minutes(string $content): int
{
    $text = html_entity_decode(wp_strip_all_tags($content), ENT_QUOTES, 'UTF-8');
    $cjk = preg_match_all('/[\x{3040}-\x{30ff}\x{3400}-\x{9fff}]/u', $text);
    // Unicode-aware, so Cyrillic, Arabic and accented Latin words count too (str_word_count() sees ASCII letters only).
    $words = preg_match_all('/[\p{L}\p{N}]+(?:[\'’-][\p{L}\p{N}]+)*/u', (string) preg_replace('/[\x{3040}-\x{30ff}\x{3400}-\x{9fff}]/u', ' ', $text));
    return max(1, (int) round($words / 220 + $cjk / 400));
}

/** English products with the fields the matcher needs, built once per request. */
function boblift_news_product_index(): array
{
    static $index = null;
    if ($index !== null) {
        return $index;
    }
    $index = [];
    $args = ['post_type' => 'product', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'menu_order date', 'order' => 'ASC'];
    if (function_exists('pll_current_language')) {
        $args['lang'] = 'en';
    }
    foreach (get_posts($args) as $product) {
        // Placeholder models of imported products (TRUCK-MOUNTED, Z1, a repeated product name) are not model codes.
        $title = (string) $product->post_title;
        $models = array_filter(array_map(
            static fn(string $model): string => function_exists('boblift_clean_product_model') ? boblift_clean_product_model($model, $title) : trim($model),
            array_merge(
                [(string) get_post_meta($product->ID, '_model', true)],
                preg_split('/[,;\n]+/', (string) get_post_meta($product->ID, '_model_aliases', true)) ?: []
            )
        ), static fn(string $model): bool => strlen($model) >= 4);
        // get_the_terms() reads the term cache get_posts() primed, instead of one query per product.
        $terms = get_the_terms($product, 'product_category');
        $terms = is_array($terms) ? wp_list_pluck($terms, 'slug') : [];
        $index[] = [
            'id' => $product->ID,
            'models' => array_values(array_unique($models)),
            'capacity' => function_exists('boblift_product_filter_values') ? (float) (boblift_product_filter_values($product->ID)['capacity'] ?? 0) : 0.0,
            'categories' => array_values($terms),
        ];
    }
    return $index;
}

/**
 * Products the article is about, in the current language, best match first:
 * 1. a model code named in the title or text (SQ10ZA5 ...);
 * 2. the capacity, boom type and use named in the title ("8 ton knuckle boom truck crane");
 * 3. the product family the article talks about most (marine, fly jib, tail lift ...).
 */
function boblift_news_related_products(string $title, string $content, int $limit = 3, int $post_id = 0): array
{
    $text = $title . ' ' . html_entity_decode(wp_strip_all_tags($content), ENT_QUOTES, 'UTF-8');
    // A translated article is matched on its source-language text as well: the family keywords and title patterns below
    // cover English, Chinese and Japanese, not every translation (морские краны, grúas marinas ...).
    $source_title = '';
    if ($post_id > 0 && function_exists('pll_get_post') && function_exists('pll_get_post_language')) {
        $source_language = function_exists('boblift_mls_source_language') ? (string) boblift_mls_source_language() : 'en';
        $source_id = (int) pll_get_post($post_id, $source_language);
        $source = $source_id > 0 && $source_id !== $post_id ? get_post($source_id) : null;
        if ($source instanceof WP_Post) {
            $source_title = (string) $source->post_title;
            $text .= ' ' . $source_title . ' ' . html_entity_decode(wp_strip_all_tags((string) $source->post_content), ENT_QUOTES, 'UTF-8');
        }
    }
    $haystack = strtolower($title . ' ' . $source_title . ' ' . $text);
    $scores = [];
    $index = boblift_news_product_index();

    foreach ($index as $product) {
        foreach ($product['models'] as $model) {
            if (preg_match('/(?<![a-z0-9])' . preg_quote(strtolower($model), '/') . '(?![a-z0-9])/', $haystack)) {
                $scores[$product['id']] = max($scores[$product['id']] ?? 0, 30 + strlen($model));
            }
        }
    }
    // Older articles name superseded models (SQ10ZA5 is now SQ10ZA6): match on capacity + boom letter,
    // with the T/TJ suffix telling marine from truck.
    if (preg_match_all('/(?<![a-z0-9])sq(?<capacity>\d+(?:\.\d+)?)(?<boom>[zs])a\d+(?<marine>tj|t)?/', $haystack, $codes, PREG_SET_ORDER)) {
        foreach ($codes as $code) {
            $prefix = 'sq' . $code['capacity'] . $code['boom'] . 'a';
            $marine = ($code['marine'] ?? '') !== '';
            foreach ($index as $product) {
                foreach ($product['models'] as $model) {
                    $model = strtolower($model);
                    if (str_starts_with($model, $prefix) && (bool) preg_match('/\d(tj|t)(\+|$)/', $model) === $marine) {
                        $scores[$product['id']] = max($scores[$product['id']] ?? 0, 25);
                    }
                }
            }
        }
    }

    $title_lower = strtolower(trim($title . ' ' . $source_title));
    $family_keywords = [
        'marine' => ['marine', 'ship', 'vessel', 'offshore', '船用', '船吊', '船舶', '船用'],
        'fly-jib' => ['fly jib', 'forklift', '叉车', 'フォークリフト'],
        'tail-lift' => ['tail lift', 'tailboard', 'tail board', '尾板', 'テールゲート'],
        'aerial' => ['aerial work', 'aerial platform', 'high-altitude work', 'high altitude work', 'work platform', '高空作业', '高所作業'],
        'timber' => ['timber', 'log', '木材', '抓木'],
        'spider' => ['spider', '蜘蛛', 'スパイダー'],
        'pickup' => ['pickup', 'pick-up', '皮卡', 'ピックアップ'],
        'truck' => ['truck crane', 'truck mounted crane', 'truck-mounted crane', 'knuckle boom crane', 'telescopic boom crane', '随车吊', '随车起重机', 'トラック搭載'],
    ];
    $family_categories = [
        'marine' => ['knuckle-boom-marine-cranes', 'telescopic-boom-marine-cranes'],
        'fly-jib' => ['forklift-fly-jib-cranes'],
        'tail-lift' => ['tail-lift'],
        'aerial' => ['truck-mounted-aerial-work-platform'],
        'timber' => ['timber-cranes'],
        'spider' => ['spider-cranes'],
        'pickup' => ['pickup-cranes'],
        'truck' => ['knuckle-boom-truck-mounted-cranes', 'telescopic-boom-truck-mounted-cranes'],
    ];
    $family_hits = [];
    // Latin keywords match whole words only ("ship" must not hit "relationship"); CJK keywords match anywhere.
    $count = static function (string $keyword, string $text): int {
        $pattern = preg_match('/^[a-z -]+$/', $keyword) ? '/\\b' . preg_quote(trim($keyword), '/') . 's?\\b/u' : '/' . preg_quote($keyword, '/') . '/u';
        return (int) preg_match_all($pattern, $text);
    };
    foreach ($family_keywords as $family => $keywords) {
        foreach ($keywords as $keyword) {
            $family_hits[$family] = ($family_hits[$family] ?? 0) + $count($keyword, $haystack) + 3 * $count($keyword, $title_lower);
        }
    }
    arsort($family_hits);
    $family = (string) array_key_first($family_hits);
    $family = ($family_hits[$family] ?? 0) > 0 ? $family : 'truck';

    // Boom type: telescopic when the title says so, knuckle for "knuckle/folding", otherwise either.
    $boom = '';
    if (preg_match('/telescopic|straight boom|伸缩|直臂|直伸/u', $title_lower)) {
        $boom = 'telescopic';
    } elseif (preg_match('/knuckle|folding|折叠|折臂|ナックル/u', $title_lower)) {
        $boom = 'knuckle';
    }
    $categories = array_values(array_filter($family_categories[$family], static fn(string $slug): bool => $boom === '' || !str_contains($slug, 'boom') || str_starts_with($slug, $boom)));
    $categories = $categories !== [] ? $categories : $family_categories[$family];

    // The source-language title is read first; decimal commas (12,5 toneladas) count, and a match never starts inside a number.
    $capacity = preg_match('/(?<![\d.,])(\d+(?:[.,]\d+)?)\s*-?\s*(?:tons?|tonnes?|toneladas?|t\b|吨|トン)/iu', trim($source_title . ' ' . $title), $capacity_match) ? (float) str_replace(',', '.', $capacity_match[1]) : 0.0;
    // Whole kilogram figures only (12500 kg, 12,500 kg), never the tail of a longer number.
    if ($capacity === 0.0 && preg_match('/(?<![\d.,])(\d{1,3}(?:,\d{3})+|\d+)\s*kg\b/i', $title . ' ' . $source_title, $kg_match)) {
        $kilograms = (float) str_replace(',', '', $kg_match[1]);
        $capacity = $kilograms >= 100 ? $kilograms / 1000 : 0.0;
    }

    foreach ($index as $order => $product) {
        $slugs = array_map(static fn(string $slug): string => preg_replace('/-(ja|zh)(-\d+)?$|-\d+$/', '', $slug), $product['categories']);
        if (array_intersect($slugs, $categories) === []) {
            continue;
        }
        $score = 1 - $order / 10000;
        if ($capacity > 0 && $product['capacity'] > 0 && abs($product['capacity'] - $capacity) < 0.05) {
            $score = 20;
        }
        $scores[$product['id']] = max($scores[$product['id']] ?? 0, $score);
    }

    arsort($scores);
    $language = function_exists('pll_current_language') ? (string) pll_current_language('slug') : '';
    $products = [];
    foreach (array_keys($scores) as $english_id) {
        $id = $language !== '' && function_exists('pll_get_post') ? (int) pll_get_post($english_id, $language) : $english_id;
        $post = $id ? get_post($id) : null;
        if ($post instanceof WP_Post && $post->post_status === 'publish') {
            $products[] = $post;
        }
        if (count($products) >= $limit) {
            break;
        }
    }
    return $products;
}

/**
 * Question and answer pairs from the article's FAQ section: the <h3> questions under an <h2> titled FAQ (in any of the
 * site's languages), each answered by the text up to the next question. Returns [] when there is no such section.
 */
function boblift_news_article_faq(string $content): array
{
    $heading = '/\bFAQs?\b|frequently asked|常见问题|常見問題|よくある(?:ご)?質問|preguntas frecuentes|часто задаваемые вопросы|questions fréquentes|foire aux questions|الأسئلة الشائعة|الأسئلة المتكررة/iu';
    $text = static fn(string $html): string => trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(wp_strip_all_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    $sections = preg_split('#(<h2\b[^>]*>.*?</h2>)#is', $content, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
    $items = [];
    for ($i = 1; $i < count($sections) - 1; $i += 2) {
        if (!preg_match($heading, $text($sections[$i]))) {
            continue;
        }
        // A rule ends the FAQ: what follows it (an author note, a call to action) is not part of the questions.
        $section = (string) preg_split('#<hr\b#i', $sections[$i + 1], 2)[0];
        $parts = preg_split('#<h3\b[^>]*>(.*?)</h3>#is', $section, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        for ($j = 1; $j < count($parts) - 1; $j += 2) {
            $question = $text($parts[$j]);
            $answer = $text($parts[$j + 1]);
            if ($question !== '' && $answer !== '') {
                $items[] = ['@type' => 'Question', 'name' => $question, 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $answer]];
            }
        }
    }
    return $items;
}

/** Schema.org types the page's own JSON-LD already describes, at any depth (_article_schema_jsonld and JSON-LD written in the body). */
function boblift_post_stored_schema_types(int $post_id): array
{
    $home = untrailingslashit((string) get_option('home'));
    $documents = [trim((string) get_post_meta($post_id, '_article_schema_jsonld', true))];
    $content = (string) get_post_field('post_content', $post_id, 'raw');
    if (stripos($content, 'application/ld+json') !== false && function_exists('boblift_content_jsonld_pattern') && preg_match_all(boblift_content_jsonld_pattern(), $content, $matches)) {
        $documents = array_merge($documents, $matches[2]);
    }
    $types = [];
    $collect = static function ($node) use (&$collect, &$types): void {
        if (!is_array($node)) {
            return;
        }
        foreach ((array) ($node['@type'] ?? []) as $type) {
            $types[(string) $type] = true;
        }
        // Nested nodes count too: @graph entries, lists, and objects held by a property (a WebPage's breadcrumb).
        foreach ($node as $child) {
            $collect($child);
        }
    };
    foreach ($documents as $json) {
        $collect(json_decode(str_replace('{{home}}', $home, trim((string) $json)), true));
    }
    return array_keys($types);
}

/**
 * The news article's JSON-LD graph: Article with its author (Achord Chan, who signs every article on the page) and the
 * publisher, the BreadcrumbList shown above the headline (Home / News / article) and, when the text has an FAQ section,
 * an FAQPage. Nodes the article's own JSON-LD already holds are left out, so a hand-written Article is not doubled.
 */
function boblift_news_article_schema_graph(WP_Post $post): array
{
    $home = untrailingslashit((string) get_option('home'));
    $stored = boblift_post_stored_schema_types($post->ID);
    $localize = static fn(string $text): string => function_exists('boblift_localize_product_text_fragments') ? (string) boblift_localize_product_text_fragments($text) : $text;
    $plain = static fn(string $text): string => trim(html_entity_decode(wp_strip_all_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $title = $plain($localize(get_the_title($post)));
    $url = (string) get_permalink($post);
    $article_ui = boblift_site_ui('newsArticle');
    $ui = boblift_site_ui('newsDetail');
    $nodes = [];

    if (array_intersect($stored, ['Article', 'NewsArticle', 'BlogPosting']) === []) {
        $person = [
            '@type' => 'Person',
            '@id' => $home . '/#achord-chan',
            'name' => trim((string) ($article_ui['authorName'] ?? '')) ?: 'Achord Chan',
            'jobTitle' => 'Product Operations Director',
            'url' => boblift_url('/about.html'),
            'image' => boblift_asset('images/authors/achord-chan.webp'),
            'worksFor' => ['@id' => $home . '/#organization'],
        ];
        $article = [
            '@type' => 'Article',
            '@id' => $url . '#article',
            'headline' => $title,
            'description' => $plain(boblift_seo_post_description($post)),
            'datePublished' => get_post_time('c', true, $post),
            'dateModified' => get_post_modified_time('c', true, $post),
            'inLanguage' => get_bloginfo('language'),
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $url],
            'author' => ['@id' => $person['@id']],
            'publisher' => [
                '@type' => 'Organization',
                '@id' => $home . '/#organization',
                'name' => 'BOB-LIFT',
                'url' => $home . '/',
                'logo' => ['@type' => 'ImageObject', 'url' => boblift_asset('images/bob-lift-logo.png')],
            ],
        ];
        $image = wp_get_attachment_image_src((int) get_post_thumbnail_id($post), 'full');
        if (is_array($image) && (string) $image[0] !== '') {
            $article['image'] = array_filter(['@type' => 'ImageObject', 'url' => (string) $image[0], 'width' => (int) $image[1], 'height' => (int) $image[2]]);
        }
        $nodes[] = $article;
        $nodes[] = $person;
    }

    if (!in_array('BreadcrumbList', $stored, true)) {
        $nodes[] = [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => (string) ($ui['home'] ?? 'Home'), 'item' => home_url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => (string) ($ui['news'] ?? 'News'), 'item' => boblift_url('/news')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $title, 'item' => $url],
            ],
        ];
    }

    if (!in_array('FAQPage', $stored, true)) {
        $faq = boblift_news_article_faq($localize((string) $post->post_content));
        if ($faq !== []) {
            $nodes[] = ['@type' => 'FAQPage', '@id' => $url . '#faq', 'mainEntity' => $faq];
        }
    }

    return $nodes === [] ? [] : ['@context' => 'https://schema.org', '@graph' => $nodes];
}
