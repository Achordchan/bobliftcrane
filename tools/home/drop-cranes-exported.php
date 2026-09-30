<?php
/**
 * Drops the placeholder "9999+ cranes exported" figure from the home About stats and the
 * About page export stats, in every language's saved options. Idempotent.
 *
 *   wp eval-file ../tools/home/drop-cranes-exported.php dry-run
 *   wp eval-file ../tools/home/drop-cranes-exported.php
 */
$dry_run = in_array('dry-run', $args ?? [], true);
$is_placeholder = static fn($stat): bool => is_array($stat) && str_starts_with(trim((string) ($stat['value'] ?? $stat['number'] ?? '')), '9999');

foreach (['boblift_theme_options', 'boblift_theme_options_ja', 'boblift_theme_options_zh', 'boblift_theme_options_es', 'boblift_theme_options_ru'] as $key) {
    $options = get_option($key);
    if (!is_array($options)) {
        continue;
    }
    $changed = 0;
    foreach ([['home', 'about', 'stats'], ['about_page', 'global_footprint', 'stats']] as $path) {
        [$a, $b, $c] = $path;
        $stats = $options[$a][$b][$c] ?? null;
        if (!is_array($stats)) {
            continue;
        }
        $kept = array_values(array_filter($stats, static fn($stat): bool => !$is_placeholder($stat)));
        if (count($kept) !== count($stats)) {
            $options[$a][$b][$c] = $kept;
            $changed += count($stats) - count($kept);
        }
    }
    echo $key . ': ' . $changed . " removed\n";
    if ($changed && !$dry_run) {
        update_option($key, $options);
    }
}
