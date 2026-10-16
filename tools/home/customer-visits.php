<?php
require_once dirname(__DIR__) . '/backup-dir.php';
/**
 * The Global Customer Visits page now reads its photos and copy from the theme defaults (inc/data.php).
 * ja/zh may still hold the three old placeholder photos, which would override them, so this clears the saved photo
 * list in every language, but only when it is exactly that placeholder list (theme images, no uploads). A list an
 * editor customised (uploaded photos, other images, any change) and any saved steps are left untouched.
 * The previous customer_visits block is written to a backup file before anything is changed. Idempotent.
 *
 *   wp eval-file ../tools/home/customer-visits.php dry-run
 *   wp eval-file ../tools/home/customer-visits.php
 */
$dry_run = in_array('dry-run', $args ?? [], true);
$placeholder_items = [
    ['image' => ['id' => 0, 'path' => 'images/factory-workshop.jpg'], 'label' => 'Factory Walkthrough', 'title' => 'Production line review', 'text' => 'Customers can review workshop order, assembly workflow and product quality details with the BOB-LIFT team.'],
    ['image' => ['id' => 0, 'path' => 'images/about/quanyuan-compressed.jpg'], 'label' => 'Technical Discussion', 'title' => 'Project requirement meeting', 'text' => 'Our team discusses load capacity, working radius, installation conditions and export requirements with visiting partners.'],
    ['image' => ['id' => 0, 'path' => 'images/oldsite/news/enter-the-bob-lift-factory-with-professional-manufacturing.jpg'], 'label' => 'Product Inspection', 'title' => 'Close look at crane details', 'text' => 'Visitors can compare product structures, manufacturing details and practical configuration options before choosing a model.'],
];
$backup = [];
$changes = [];
foreach (['boblift_theme_options', 'boblift_theme_options_ja', 'boblift_theme_options_zh', 'boblift_theme_options_es', 'boblift_theme_options_ru'] as $key) {
    $options = get_option($key);
    if (!is_array($options) || !is_array($options['about_page']['customer_visits'] ?? null)) {
        continue;
    }
    $visits = $options['about_page']['customer_visits'];
    $clear = ($visits['items'] ?? null) === $placeholder_items;
    echo $key . ': ' . ($clear ? 'cleared placeholder items' : 'unchanged') . "\n";
    if ($clear && !$dry_run) {
        $backup[$key] = $visits;
        $changes[$key] = $options;
    }
}
if ($changes) {
    $file = boblift_tools_backup_dir() . '/backup-customer-visits-' . gmdate('Ymd-His') . '.json';
    $encoded = wp_json_encode($backup, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    $bytes = is_string($encoded) ? file_put_contents($file . '.tmp', $encoded) : false;
    if (!is_string($encoded) || $bytes !== strlen($encoded) || !rename($file . '.tmp', $file)) {
        fwrite(STDERR, "Cannot write backup $file; nothing was changed.\n");
        exit(1);
    }
    foreach ($changes as $key => $options) {
        unset($options['about_page']['customer_visits']['items']);
        update_option($key, $options);
    }
}
