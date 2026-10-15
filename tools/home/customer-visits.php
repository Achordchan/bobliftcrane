<?php
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
$placeholder_paths = [
    'images/factory-workshop.jpg',
    'images/about/quanyuan-compressed.jpg',
    'images/oldsite/news/enter-the-bob-lift-factory-with-professional-manufacturing.jpg',
];
$is_placeholder_list = static function (array $items) use ($placeholder_paths): bool {
    if ($items === []) {
        return false;
    }
    foreach ($items as $item) {
        $image = is_array($item) ? ($item['image'] ?? null) : null;
        $id = is_array($image) ? (int) ($image['id'] ?? 0) : 0;
        $path = is_array($image) ? (string) ($image['path'] ?? '') : (string) $image;
        if ($id > 0 || !in_array($path, $placeholder_paths, true)) {
            return false;
        }
    }
    return true;
};
$backup = [];
$changes = [];
foreach (['boblift_theme_options', 'boblift_theme_options_ja', 'boblift_theme_options_zh', 'boblift_theme_options_es', 'boblift_theme_options_ru'] as $key) {
    $options = get_option($key);
    if (!is_array($options) || !is_array($options['about_page']['customer_visits'] ?? null)) {
        continue;
    }
    $visits = $options['about_page']['customer_visits'];
    $clear = is_array($visits['items'] ?? null) && $is_placeholder_list($visits['items']);
    echo $key . ': ' . ($clear ? 'cleared placeholder items' : 'unchanged') . "\n";
    if ($clear && !$dry_run) {
        $backup[$key] = $visits;
        $changes[$key] = $options;
    }
}
if ($changes) {
    $file = __DIR__ . '/backup-customer-visits-' . gmdate('Ymd-His') . '.json';
    $bytes = file_put_contents($file, wp_json_encode($backup, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    if ($bytes === false || $bytes === 0) {
        fwrite(STDERR, "Cannot write backup $file; nothing was changed.\n");
        exit(1);
    }
    foreach ($changes as $key => $options) {
        unset($options['about_page']['customer_visits']['items']);
        update_option($key, $options);
    }
}
