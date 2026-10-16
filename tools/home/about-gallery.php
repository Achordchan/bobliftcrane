<?php
require_once dirname(__DIR__) . '/backup-dir.php';
/**
 * Points the home About gallery at the company photos (workshop, technicians, team, customer visit)
 * instead of product shots, in every language. Video and YouTube slots, and any slot an editor filled with an upload or
 * another URL, are kept: only empty slots and slots still showing a theme image (images/…, the old product shots) change.
 * The previous gallery of every changed language is written to a backup file first. Idempotent.
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
        $current = $item['src'] ?? '';
        $current_path = is_array($current) ? (string) ($current['path'] ?? '') : (string) $current;
        $uploaded = is_array($current) && (int) ($current['id'] ?? 0) > 0;
        // A custom image (upload, or a URL outside the theme) is the editor's; leave the slot alone.
        if ($uploaded || ($current_path !== '' && !str_starts_with($current_path, 'images/'))) {
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
        $file = boblift_tools_backup_dir() . '/backup-about-gallery-' . $key . '-' . gmdate('Ymd-His') . '.json';
        $encoded = wp_json_encode($options['home']['about']['gallery'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $bytes = is_string($encoded) ? file_put_contents($file . '.tmp', $encoded) : false;
        if (!is_string($encoded) || $bytes !== strlen($encoded) || !rename($file . '.tmp', $file)) {
            fwrite(STDERR, "Cannot write backup $file; nothing was changed for $key.\n");
            exit(1);
        }
        $options['home']['about']['gallery'] = array_values($gallery);
        update_option($key, $options);
    }
}
