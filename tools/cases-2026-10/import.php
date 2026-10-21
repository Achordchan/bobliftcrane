<?php
// Creates or updates the October 2026 application cases from cases.json (English only).
// Run inside .wordpress:  wp eval-file ../tools/cases-2026-10/import.php
$cases = json_decode((string) file_get_contents(__DIR__ . '/cases.json'), true);
foreach ($cases as $case) {
    $existing = get_page_by_path($case['slug'], OBJECT, 'case');
    $links = '';
    foreach ($case['links'] as $link) {
        if (isset($link['product_id'])) {
            // A product that is not published on this site (e.g. a model moved to draft) gets no link.
            if (get_post_status((int) $link['product_id']) !== 'publish') {
                continue;
            }
            $url = wp_make_link_relative(get_permalink((int) $link['product_id']));
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
        'post_name' => $case['slug'],
        'post_excerpt' => $case['summary'],
        'post_content' => '<h2>See the crane and the customer photos</h2><ul>' . $links . '</ul>'
            . ($references !== '' ? '<h2>Further reading</h2><ul>' . $references . '</ul>' : ''),
    ];
    if ($existing) {
        $postarr['ID'] = $existing->ID;
    }
    $id = wp_insert_post(wp_slash($postarr), true);
    if (is_wp_error($id)) {
        WP_CLI::warning($case['slug'] . ': ' . $id->get_error_message());
        continue;
    }
    if (function_exists('pll_set_post_language')) {
        pll_set_post_language($id, 'en');
    }
    $meta = [
        '_case_kind' => 'scene_case',
        '_type' => $case['type'],
        '_summary' => $case['summary'],
        '_country' => $case['country'],
        '_product_name' => $case['product_name'],
        '_application' => $case['application'],
        '_facts' => wp_json_encode($case['facts'], JSON_UNESCAPED_UNICODE),
        '_sections' => wp_json_encode($case['sections'], JSON_UNESCAPED_UNICODE),
        '_case_gallery' => implode(',', $case['gallery']),
        '_related_product' => (string) $case['product_id'],
        '_seo_title' => $case['seo_title'],
        '_seo_description' => $case['seo_description'],
    ];
    foreach ($meta as $key => $value) {
        update_post_meta($id, $key, wp_slash($value));
    }
    // Article + breadcrumb JSON-LD, printed by boblift_article_schema(). Complete URLs from this site's own permalinks,
    // so subdirectory installs and language prefixes need no path arithmetic.
    $url = get_permalink($id);
    $images = array_values(array_filter(array_map(static fn($attachment): string => (string) wp_get_attachment_image_url((int) $attachment, 'large'), array_slice($case['gallery'], 0, 4))));
    $schema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Article',
                'headline' => $case['title'],
                'description' => $case['seo_description'],
                'mainEntityOfPage' => $url,
                'datePublished' => get_post_time('c', true, $id),
                'dateModified' => get_post_modified_time('c', true, $id),
                'author' => ['@type' => 'Person', 'name' => 'Achord Chan', 'url' => home_url('/about.html'), 'jobTitle' => 'Product Operations Director', 'worksFor' => ['@type' => 'Organization', 'name' => 'BOB-LIFT']],
                'publisher' => ['@type' => 'Organization', 'name' => 'BOB-LIFT', 'url' => home_url('/')],
                'image' => array_map(static fn(string $src): array => ['@type' => 'ImageObject', 'url' => $src], $images),
                'about' => get_post_status((int) $case['product_id']) === 'publish'
                    ? ['@type' => 'Product', 'name' => $case['product_name'], 'url' => get_permalink((int) $case['product_id'])]
                    : ['@type' => 'Product', 'name' => $case['product_name']],
                'contentLocation' => ['@type' => 'Country', 'name' => $case['country']],
            ],
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => home_url('/')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Cases', 'item' => home_url('/cases.html')],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $case['title'], 'item' => $url],
                ],
            ],
        ],
    ];
    update_post_meta($id, '_article_schema_jsonld', wp_slash(wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)));
    // Descriptive alt text for the customer photos used on the page (what each photo shows).
    foreach ($case['photo_alts'] ?? [] as $attachment => $alt) {
        update_post_meta((int) $attachment, '_wp_attachment_image_alt', wp_slash($alt));
    }
    // Cover: the AI illustration once it exists, otherwise the first customer photo.
    $cover = (int) ($case['cover_id'] ?? 0);
    if (!$cover || !wp_attachment_is_image($cover)) {
        $cover = (int) $case['gallery'][0];
    }
    set_post_thumbnail($id, $cover);
    WP_CLI::log($case['slug'] . ' => ' . $id . ' ' . get_permalink($id));
}
