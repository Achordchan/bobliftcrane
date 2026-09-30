<?php
/**
 * BOB-LIFT was founded in Xuzhou in 2003 (the About page timeline). The homepage still carried the
 * old placeholder story: "since 1989", "35+ years" and a 1989/2005/2015/2022 timeline. This rewrites
 * those saved values in every language to match the About page. Only the exact old values are
 * replaced, so edits made in WP admin since are left alone and the script can be re-run.
 *
 *   wp eval-file ../tools/home/company-years.php dry-run
 *   wp eval-file ../tools/home/company-years.php
 */
$dry_run = in_array('dry-run', $args ?? [], true);
$timelines = [
    'boblift_theme_options' => ['2003 Company founded in Xuzhou', '2014 Foreign trade department established', '2023 Canton Fair debut', '2026 Serving global markets'],
    'boblift_theme_options_ja' => ['2003年 徐州で会社設立', '2014年 海外貿易部を設立', '2023年 広州交易会に初出展', '2026年 世界市場へ展開'],
    'boblift_theme_options_zh' => ['2003 徐州成立', '2014 成立外贸部', '2023 首次亮相广交会', '2026 服务全球市场'],
];
$old_first = ['1989 Company Founded', '1989年 会社設立', '1989 公司成立'];
$text_swaps = ['35+ years of experience' => '20+ years of experience', '35年以上の経験' => '20年以上の経験', '35 年以上经验' => '20 年以上经验'];
foreach ($timelines as $key => $timeline) {
    $options = get_option($key);
    if (!is_array($options)) {
        continue;
    }
    $changes = [];
    $about = is_array($options['home']['about'] ?? null) ? $options['home']['about'] : [];
    if (isset($about['timeline'][0]) && in_array($about['timeline'][0], $old_first, true)) {
        $about['timeline'] = $timeline;
        $changes[] = 'home.about.timeline';
    }
    if (isset($about['text']) && is_string($about['text'])) {
        $text = strtr($about['text'], $text_swaps);
        if ($text !== $about['text']) {
            $about['text'] = $text;
            $changes[] = 'home.about.text';
        }
    }
    foreach ((array) ($about['stats'] ?? []) as $index => $stat) {
        if (is_array($stat) && ($stat['value'] ?? '') === '35+') {
            $about['stats'][$index]['value'] = '20+';
            $changes[] = "home.about.stats.$index";
        }
    }
    if ($about !== []) {
        $options['home']['about'] = $about;
    }
    if (($options['site_ui']['newsPage']['yearsValue'] ?? '') === '35+') {
        $options['site_ui']['newsPage']['yearsValue'] = '20+';
        $changes[] = 'site_ui.newsPage.yearsValue';
    }
    if (($options['home']['hero']['label'] ?? '') === 'Crane manufacturer since 1989') {
        $options['home']['hero']['label'] = 'Crane manufacturer since 2003';
        $changes[] = 'home.hero.label';
    }
    if ($changes === []) {
        WP_CLI::log("$key: nothing to change");
        continue;
    }
    if (!$dry_run) {
        update_option($key, $options);
    }
    WP_CLI::log(($dry_run ? '[dry-run] ' : '') . "$key: " . implode(', ', $changes));
}
