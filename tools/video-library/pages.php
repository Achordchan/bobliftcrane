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
    'mounting-shipping' => ['en' => 'Truck Mounting & Export', 'ja' => 'トラック架装と輸出', 'zh' => '装车与出口'],
    'service' => ['en' => 'Service & Operation', 'ja' => 'アフターサービスと操作', 'zh' => '售后与操作'],
];
$about = get_page_by_path('about', OBJECT, 'page');
foreach ($pages as $slug => $titles) {
    // English pages sit under About (about/<slug>), so look them up by slug and language rather than by top-level path.
    $en = null;
    foreach (get_posts(['post_type' => 'page', 'post_status' => ['publish', 'draft', 'pending', 'private'], 'name' => $slug, 'posts_per_page' => -1, 'lang' => '']) as $candidate) {
        $candidate_lang = function_exists('pll_get_post_language') ? pll_get_post_language($candidate->ID) : 'en';
        if ($candidate_lang === 'en' || $candidate_lang === false || $candidate_lang === '') {
            $en = $candidate;
            break;
        }
    }
    $translations = $en instanceof WP_Post && function_exists('pll_get_post_translations') ? pll_get_post_translations($en->ID) : [];
    // Seed the English entry from the page found above, so it is reused (not created again) even without Polylang or a language assignment.
    if ($en instanceof WP_Post && empty($translations['en'])) {
        $translations['en'] = $en->ID;
    }
    foreach ($titles as $lang => $title) {
        // Without Polylang the translated copies could not be linked or told apart, so only the English page is created.
        if ($lang !== 'en' && !(function_exists('pll_set_post_language') && function_exists('pll_save_post_translations'))) {
            echo "$slug $lang: skipped (Polylang is not active)\n";
            continue;
        }
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
