<?php
/**
 * The Contact page was rebuilt (form first, one direct-contact card, factory photo instead of the map). The saved
 * site_ui.contactPage values in every language still hold the old hero description and image and keys the page no
 * longer reads; they override the new copy from inc/site-ui-sections/contact-page.php, so this removes them.
 *
 *   wp eval-file ../tools/home/contact-page.php dry-run
 *   wp eval-file ../tools/home/contact-page.php
 */
$dry_run = in_array('dry-run', $args ?? [], true);
$remove = ['heroDescription', 'heroImage', 'heroImageAlt', 'supportTitle', 'supportDescription', 'supportItems', 'locationTitle', 'locationDescriptionTemplate', 'mapTitle', 'viewMapLabel'];
foreach (['boblift_theme_options', 'boblift_theme_options_ja', 'boblift_theme_options_zh'] as $key) {
    $options = get_option($key);
    if (!is_array($options) || !is_array($options['site_ui']['contactPage'] ?? null)) {
        WP_CLI::log("$key: nothing saved");
        continue;
    }
    $had = array_values(array_intersect($remove, array_keys($options['site_ui']['contactPage'])));
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
