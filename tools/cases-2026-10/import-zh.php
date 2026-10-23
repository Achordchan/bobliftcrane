<?php
// Chinese versions of the October 2026 cases, linked to the English posts as Polylang translations.
// Run inside .wordpress after import.php:  wp eval-file ../tools/cases-2026-10/import-zh.php
$cases = json_decode((string) file_get_contents(__DIR__ . '/cases-zh.json'), true);
foreach ($cases as $case) {
    $en = get_page_by_path($case['en_slug'], OBJECT, 'case');
    if (!$en) {
        WP_CLI::warning('Missing English case ' . $case['en_slug']);
        continue;
    }
    $translations = pll_get_post_translations($en->ID);
    $links = '';
    foreach ($case['links'] as $link) {
        if (isset($link['product_id'])) {
            // A product that is not published on this site (e.g. a model moved to draft) gets no link.
            $product_id = pll_get_post((int) $link['product_id'], 'zh') ?: (int) $link['product_id'];
            if (get_post_status($product_id) !== 'publish') {
                continue;
            }
            $url = wp_make_link_relative(get_permalink($product_id));
        } elseif (isset($link['brochure'])) {
            $url = wp_make_link_relative(boblift_asset('downloads/' . ['truck' => 'bob-lift-truck-mounted-cranes-2026.pdf', 'marine' => 'bob-lift-marine-cranes-2026.pdf'][$link['brochure']]));
        } else {
            // Site-relative paths go through home_url() so a subdirectory install keeps its base path.
            $url = str_starts_with($link['url'], '/') ? home_url($link['url']) : $link['url'];
        }
        $links .= '<li><a href="' . esc_url($url) . '">' . esc_html($link['label']) . '</a></li>';
    }
    $references = '';
    foreach ($case['references'] ?? [] as $reference) {
        $references .= '<li><a href="' . esc_url($reference['url']) . '" target="_blank" rel="noopener">' . esc_html($reference['label']) . '</a></li>';
    }
    $postarr = [
        'post_type' => 'case',
        'post_status' => 'publish',
        'post_title' => $case['title'],
        'post_name' => $case['en_slug'] . '-zh',
        'post_excerpt' => $case['summary'],
        'post_date' => $en->post_date,
        'post_content' => '<h2>查看起重机和客户照片</h2><ul>' . $links . '</ul>'
            . ($references !== '' ? '<h2>延伸阅读</h2><ul>' . $references . '</ul>' : ''),
    ];
    if (!empty($translations['zh'])) {
        $postarr['ID'] = (int) $translations['zh'];
    }
    $id = wp_insert_post(wp_slash($postarr), true);
    if (is_wp_error($id)) {
        WP_CLI::warning($case['en_slug'] . ': ' . $id->get_error_message());
        continue;
    }
    pll_set_post_language($id, 'zh');
    $translations['zh'] = $id;
    pll_save_post_translations($translations);
    // Same photos, cover, product and kind as the English case; text fields in Chinese.
    foreach (['_case_kind', '_product_name', '_case_gallery', '_related_product'] as $key) {
        update_post_meta($id, $key, wp_slash((string) get_post_meta($en->ID, $key, true)));
    }
    $meta = [
        '_type' => $case['type'],
        '_summary' => $case['summary'],
        '_country' => $case['country'],
        '_application' => $case['application'],
        '_facts' => wp_json_encode($case['facts'], JSON_UNESCAPED_UNICODE),
        '_sections' => wp_json_encode($case['sections'], JSON_UNESCAPED_UNICODE),
        '_seo_title' => $case['seo_title'],
        '_seo_description' => $case['seo_description'],
    ];
    foreach ($meta as $key => $value) {
        update_post_meta($id, $key, wp_slash($value));
    }
    set_post_thumbnail($id, (int) get_post_thumbnail_id($en->ID));
    // The Chinese page's own Article + breadcrumb JSON-LD (its URL, text and dates), not a copy of the English one.
    // Complete URLs from this site's own permalinks: no assumptions about how the language prefix or base path look.
    $url = get_permalink($id);
    // Media lives at the site root, not under /zh, so image URLs stay absolute.
    $images = array_values(array_filter(array_map(static fn($attachment): string => (string) wp_get_attachment_image_url((int) $attachment, 'large'), array_slice(array_filter(explode(',', (string) get_post_meta($en->ID, '_case_gallery', true))), 0, 4))));
    $product_id = pll_get_post((int) get_post_meta($en->ID, '_related_product', true), 'zh') ?: (int) get_post_meta($en->ID, '_related_product', true);
    $schema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Article',
                'inLanguage' => 'zh-CN',
                'headline' => $case['title'],
                'description' => $case['seo_description'],
                'mainEntityOfPage' => $url,
                'datePublished' => get_post_time('c', true, $id),
                'dateModified' => get_post_modified_time('c', true, $id),
                'author' => ['@type' => 'Person', 'name' => 'Achord Chan', 'url' => pll_home_url('zh') . 'about.html', 'jobTitle' => 'BOB-LIFT 产品运营总监', 'worksFor' => ['@type' => 'Organization', 'name' => 'BOB-LIFT']],
                'publisher' => ['@type' => 'Organization', 'name' => 'BOB-LIFT', 'url' => home_url('/')],
                'image' => array_map(static fn(string $src): array => ['@type' => 'ImageObject', 'url' => $src], $images),
                'about' => get_post_status($product_id) === 'publish'
                    ? ['@type' => 'Thing', 'name' => (string) get_post_meta($en->ID, '_product_name', true), 'url' => get_permalink($product_id)]
                    : ['@type' => 'Thing', 'name' => (string) get_post_meta($en->ID, '_product_name', true)],
                'contentLocation' => ['@type' => 'Country', 'name' => $case['country']],
            ],
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => '首页', 'item' => pll_home_url('zh')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => '案例', 'item' => pll_home_url('zh') . 'cases.html'],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $case['title'], 'item' => $url],
                ],
            ],
        ],
    ];
    update_post_meta($id, '_article_schema_jsonld', wp_slash(wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)));
    WP_CLI::log($case['en_slug'] . ' zh => ' . $id . ' ' . get_permalink($id));
}
