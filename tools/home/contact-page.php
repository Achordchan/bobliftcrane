<?php
/**
 * The Contact page was rebuilt (form first, one direct-contact card, factory photo instead of the map). The saved
 * site_ui.contactPage values in every language can still hold the old default hero copy and image, and keys the page no
 * longer reads; they override the new copy from inc/site-ui-sections/contact-page.php. This removes the keys the page no
 * longer reads, and heroDescription / heroImage / heroImageAlt only when they still equal one of the old defaults:
 * values an administrator customised (these are editable settings) are kept.
 *
 *   wp eval-file ../tools/home/contact-page.php dry-run
 *   wp eval-file ../tools/home/contact-page.php
 */
$dry_run = in_array('dry-run', $args ?? [], true);
$remove = ['supportTitle', 'supportDescription', 'supportItems', 'locationTitle', 'locationDescriptionTemplate', 'mapTitle', 'viewMapLabel'];
// Old built-in values of the editable hero fields; anything else is the administrator's own.
$legacy_defaults = [
    'heroDescription' => [
        'For model selection, site access matching, marine installation and export questions, use the direct contact channels or create a structured inquiry draft.',
        'For model selection, site access matching, marine installation and export questions, use the direct contact channels or send an inquiry below.',
    ],
    'heroImage' => ['images/global-network-blue.jpg', 'images/page-heroes/sales-team.webp'],
    'heroImageAlt' => ['BOB-LIFT contact support', 'The BOB-LIFT sales and service team'],
];
foreach (['boblift_theme_options', 'boblift_theme_options_ja', 'boblift_theme_options_zh'] as $key) {
    $options = get_option($key);
    if (!is_array($options) || !is_array($options['site_ui']['contactPage'] ?? null)) {
        WP_CLI::log("$key: nothing saved");
        continue;
    }
    $saved = $options['site_ui']['contactPage'];
    $had = array_values(array_intersect($remove, array_keys($saved)));
    foreach ($legacy_defaults as $field => $known) {
        $value = $saved[$field] ?? null;
        $value = is_array($value) ? (string) ($value['path'] ?? '') : trim((string) $value);
        if ($value !== '' && in_array($value, $known, true) && !(is_array($saved[$field]) && (int) ($saved[$field]['id'] ?? 0) > 0)) {
            $had[] = $field;
        }
    }
    if ($had === []) {
        WP_CLI::log("$key: nothing to change");
        continue;
    }
    foreach ($had as $field) {
        unset($options['site_ui']['contactPage'][$field]);
    }
    if (!$dry_run) {
        update_option($key, $options);
    }
    WP_CLI::log(($dry_run ? '[dry-run] ' : '') . "$key: removed " . implode(', ', $had));
}
