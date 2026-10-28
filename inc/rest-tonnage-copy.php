<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hand-written words for one tonnage page (/16-ton-crane.html, inc/tonnage-pages.php) in one language, over the
 * templates every tonnage page shares: its <title>, its meta description, its heading (the banner H1; the breadcrumb
 * and the "See all" links keep the template's name) and a lead put before the generated intro. They live in each
 * language's theme options as site_ui.tonnagePages.<size>, which the WordPress.com connector cannot reach, so they are
 * the boblift_tonnage_copy field on wp/v2/pages (context edit, manage_options) for the language of the page sent:
 *
 * Read: {"pages": {"16": {"title": ..., "description": ..., "heading": ..., "lead": ...}}}, what that language saved.
 *
 * Write {"size": 16, "copy": {"title": "...", "description": "...", "heading": "...", "lead": "..."}} replaces that
 * page's words (each key optional, plain text, at least one), {"size": 16, "copy": null} drops them so the templates
 * apply again. Send it on its own with the page id: the page itself is saved unchanged.
 */
function boblift_tonnage_copy_keys(): array
{
    return ['title' => 70, 'description' => 170, 'heading' => 80, 'lead' => 600];
}

/** The saved words of a tonnage page in the current language, each key present only when set. */
function boblift_tonnage_copy(int $size): array
{
    static $cache = [];
    $language = function_exists('boblift_current_language_slug') ? boblift_current_language_slug() : 'en';
    if (!isset($cache[$language])) {
        $pages = boblift_filter_bins_stored($language)['options']['site_ui']['tonnagePages'] ?? [];
        $cache[$language] = is_array($pages) ? $pages : [];
    }
    $copy = $cache[$language][$size] ?? [];
    $copy = is_array($copy) ? array_intersect_key($copy, boblift_tonnage_copy_keys()) : [];
    return array_filter(array_map(static fn($value): string => trim((string) $value), $copy), static fn(string $value): bool => $value !== '');
}

function boblift_tonnage_copy_clean(mixed $copy): array|WP_Error
{
    $keys = boblift_tonnage_copy_keys();
    $invalid = new WP_Error('boblift_tonnage_copy_invalid', 'Send "copy" with one or more of ' . implode(', ', array_keys($keys)) . ' as plain text (title up to 70 characters, description 170, heading 80, lead 600), or null.', ['status' => 400]);
    if (!is_array($copy) || $copy === [] || array_diff(array_keys($copy), array_keys($keys)) !== []) {
        return $invalid;
    }
    $clean = [];
    foreach ($copy as $key => $value) {
        $value = is_string($value) ? trim($value) : null;
        if ($value === null || $value === '' || sanitize_text_field($value) !== $value || mb_strlen($value) > $keys[$key]) {
            return $invalid;
        }
        $clean[$key] = $value;
    }
    return $clean;
}

function boblift_tonnage_copy_save(string $language, int $size, ?array $copy): true|WP_Error
{
    global $wpdb;
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $stored = boblift_filter_bins_stored($language);
        $options = $stored['options'];
        $options['site_ui'] = is_array($options['site_ui'] ?? null) ? $options['site_ui'] : [];
        $pages = is_array($options['site_ui']['tonnagePages'] ?? null) ? $options['site_ui']['tonnagePages'] : [];
        unset($pages[$size]);
        if ($copy !== null) {
            $pages[$size] = $copy;
        }
        ksort($pages, SORT_NUMERIC);
        if ($pages === []) {
            unset($options['site_ui']['tonnagePages']);
        } else {
            $options['site_ui']['tonnagePages'] = $pages;
        }
        if ($options === $stored['options']) {
            return true;
        }
        if ($stored['raw'] === null) {
            $done = add_option($stored['name'], $options, '', false);
        } else {
            $done = (bool) $wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND BINARY option_value = BINARY %s", maybe_serialize($options), $stored['name'], $stored['raw']));
        }
        wp_cache_delete($stored['name'], 'options');
        wp_cache_delete('alloptions', 'options');
        if ($done) {
            return true;
        }
    }
    return new WP_Error('boblift_tonnage_copy_busy', 'The settings kept changing while saving; try again.', ['status' => 409]);
}

function boblift_tonnage_copy_register_page_field(): void
{
    register_rest_field('page', 'boblift_tonnage_copy', [
        'get_callback' => static function (array $post) {
            $page = get_post((int) $post['id']);
            if (!$page instanceof WP_Post || !current_user_can('manage_options')) {
                return null;
            }
            $pages = boblift_filter_bins_stored(boblift_filter_bins_page_language($page))['options']['site_ui']['tonnagePages'] ?? [];
            return ['pages' => is_array($pages) && $pages !== [] ? $pages : new stdClass()];
        },
        'update_callback' => static function ($value, WP_Post $post) {
            if (!current_user_can('manage_options')) {
                return new WP_Error('boblift_tonnage_copy_forbidden', 'Changing the tonnage page words needs manage_options.', ['status' => 403]);
            }
            $size = is_array($value) && is_int($value['size'] ?? null) ? $value['size'] : 0;
            if (!isset(boblift_tonnage_bins()[$size]) || !array_key_exists('copy', $value)) {
                return new WP_Error('boblift_tonnage_copy_invalid', 'Send {"size": ' . implode(' | ', array_keys(boblift_tonnage_bins())) . ', "copy": {...} | null}.', ['status' => 400]);
            }
            $copy = $value['copy'] === null ? null : boblift_tonnage_copy_clean($value['copy']);
            if (is_wp_error($copy)) {
                return $copy;
            }
            $saved = boblift_tonnage_copy_save(boblift_filter_bins_page_language($post), $size, $copy);
            return is_wp_error($saved) ? $saved : true;
        },
        'schema' => [
            'description' => 'Hand-written title, description, heading and lead of one tonnage page in this page\'s language. Write {"size": n, "copy": {...} | null}.',
            'type' => ['object', 'null'],
            'context' => ['edit'],
        ],
    ]);
}
add_action('rest_api_init', 'boblift_tonnage_copy_register_page_field');
