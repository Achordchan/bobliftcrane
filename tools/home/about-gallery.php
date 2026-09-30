<?php
/**
 * Points the home About gallery at the company photos (workshop, technicians, team, customer visit)
 * instead of product shots, in every language, keeping any video or YouTube slot an editor set up. Idempotent.
 *
 *   wp eval-file ../tools/home/about-gallery.php dry-run
 *   wp eval-file ../tools/home/about-gallery.php
 */
$dry_run = in_array('dry-run', $args ?? [], true);
$files = ['home-workshop-floor', 'home-technician-assembly', 'home-boom-grinding', 'home-team', 'home-customer-visit'];
$alts = [
    'en' => ['BOB-LIFT assembly workshop in Xuzhou', 'BOB-LIFT technician assembling a crane column', 'Finishing a boom section in the BOB-LIFT workshop', 'The BOB-LIFT team', 'Customers visiting the BOB-LIFT factory'],
    'ja' => ['徐州のBOB-LIFT組立工場', 'クレーンのコラムを組み立てるBOB-LIFTの技術者', 'BOB-LIFT工場でのブーム仕上げ作業', 'BOB-LIFTのチーム', 'BOB-LIFT工場を見学するお客様'],
    'zh' => ['BOB-LIFT 徐州装配车间', 'BOB-LIFT 技术工人装配起重机立柱', 'BOB-LIFT 车间打磨吊臂', 'BOB-LIFT 团队', '客户参观 BOB-LIFT 工厂'],
];
foreach (['en' => 'boblift_theme_options', 'ja' => 'boblift_theme_options_ja', 'zh' => 'boblift_theme_options_zh'] as $lang => $key) {
    $options = get_option($key);
    if (!is_array($options)) {
        continue;
    }
    $gallery = is_array($options['home']['about']['gallery'] ?? null) ? $options['home']['about']['gallery'] : [];
    foreach ($files as $i => $file) {
        $item = is_array($gallery[$i] ?? null) ? $gallery[$i] : [];
        if (in_array((string) ($item['type'] ?? ''), ['local_video', 'youtube'], true)) {
            continue;
        }
        $item['src'] = 'images/about/' . $file . '.webp';
        $item['alt'] = $alts[$lang][$i];
        $gallery[$i] = $item;
    }
    ksort($gallery);
    $changed = $gallery !== ($options['home']['about']['gallery'] ?? null);
    echo $key . ': ' . ($changed ? 'updated' : 'unchanged') . "\n";
    if ($changed && !$dry_run) {
        $options['home']['about']['gallery'] = array_values($gallery);
        update_option($key, $options);
    }
}
