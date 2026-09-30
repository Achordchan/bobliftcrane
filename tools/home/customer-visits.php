<?php
/**
 * The Global Customer Visits page now reads its photos, steps and copy from the theme defaults
 * (inc/data.php). Saved options in ja/zh still hold the three old placeholder photos, which would
 * override them, so this clears the saved photo list and any saved steps in every language. Idempotent.
 *
 *   wp eval-file ../tools/home/customer-visits.php dry-run
 *   wp eval-file ../tools/home/customer-visits.php
 */
$dry_run = in_array('dry-run', $args ?? [], true);
foreach (['boblift_theme_options', 'boblift_theme_options_ja', 'boblift_theme_options_zh', 'boblift_theme_options_es', 'boblift_theme_options_ru'] as $key) {
    $options = get_option($key);
    if (!is_array($options) || !is_array($options['about_page']['customer_visits'] ?? null)) {
        continue;
    }
    $visits = $options['about_page']['customer_visits'];
    $had = array_intersect(['items', 'steps'], array_keys($visits));
    unset($visits['items'], $visits['steps']);
    echo $key . ': ' . ($had ? 'cleared ' . implode(', ', $had) : 'unchanged') . "\n";
    if ($had && !$dry_run) {
        $options['about_page']['customer_visits'] = $visits;
        update_option($key, $options);
    }
}
