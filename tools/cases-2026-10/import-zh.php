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
            $url = wp_make_link_relative(get_permalink(pll_get_post((int) $link['product_id'], 'zh') ?: (int) $link['product_id']));
        } elseif (isset($link['brochure'])) {
            $url = wp_make_link_relative(boblift_asset('downloads/' . ['truck' => 'bob-lift-truck-mounted-cranes-2026.pdf', 'marine' => 'bob-lift-marine-cranes-2026.pdf'][$link['brochure']]));
        } else {
            $url = $link['url'];
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
    foreach (['_case_kind', '_product_name', '_case_gallery', '_related_product', '_article_schema_jsonld'] as $key) {
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
    WP_CLI::log($case['en_slug'] . ' zh => ' . $id . ' ' . get_permalink($id));
}
