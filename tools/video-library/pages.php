<?php
/**
 * Creates the Factory Tour, Quality & Testing and Video Center pages in en/ja/zh, linked as Polylang
 * translations, the English ones under About. Idempotent: existing pages are left as they are.
 *
 *   wp eval-file ../tools/video-library/pages.php dry-run
 *   wp eval-file ../tools/video-library/pages.php
 */
$dry_run = in_array('dry-run', $args ?? [], true);
$pages = [
    'factory-tour' => ['en' => 'Factory Tour', 'ja' => '工場見学', 'zh' => '工厂实景'],
    'quality-testing' => ['en' => 'Quality & Testing', 'ja' => '品質と試験', 'zh' => '质量与测试'],
    'videos' => ['en' => 'Video Center', 'ja' => '動画センター', 'zh' => '视频中心'],
];
$about = get_page_by_path('about', OBJECT, 'page');
foreach ($pages as $slug => $titles) {
    $en = get_page_by_path($slug, OBJECT, 'page');
    $translations = $en instanceof WP_Post && function_exists('pll_get_post_translations') ? pll_get_post_translations($en->ID) : [];
    foreach ($titles as $lang => $title) {
        if (!empty($translations[$lang])) {
            echo "$slug $lang: exists (#{$translations[$lang]})\n";
            continue;
        }
        echo "$slug $lang: create \"$title\"\n";
        if ($dry_run) {
            continue;
        }
        $id = wp_insert_post([
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_title' => $title,
            'post_name' => $slug,
            'post_content' => '',
            'post_parent' => $lang === 'en' && $about instanceof WP_Post ? $about->ID : 0,
        ]);
        if (is_wp_error($id) || !$id) {
            echo "  failed\n";
            continue;
        }
        update_post_meta($id, '_wp_page_template', 'default');
        update_post_meta($id, '_boblift_public_url', '/' . $slug . '.html');
        if (function_exists('pll_set_post_language')) {
            pll_set_post_language($id, $lang);
        }
        $translations[$lang] = $id;
    }
    if (!$dry_run && function_exists('pll_save_post_translations') && count($translations) > 1) {
        pll_save_post_translations($translations);
    }
}
