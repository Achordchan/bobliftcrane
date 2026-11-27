<?php
/**
 * Cached HTML for the slow homepage sections (product families and customer feedback).
 *
 * Building these sections on a cache miss takes 0.3–0.9 s with a warm object cache and up to 4.7 s with a cold one
 * (measured 2026-11-27 with inc/render-timing.php). Their markup depends only on the page's language and on saved
 * content, so each is kept as a transient per language. Saving, trashing or deleting any post, changing its meta,
 * editing any term or changing a theme setting starts a new generation, so an edit shows on the next uncached request; the transients also
 * expire after six hours as a safety net.
 */
if (!defined('ABSPATH')) {
    exit;
}

const BOBLIFT_FRAGMENT_TTL = 6 * HOUR_IN_SECONDS;

function boblift_fragment_generation(): string
{
    return (string) get_option('boblift_fragment_generation', '0');
}

/** A fresh random token: two requests changing content at once can never land on the same generation. */
function boblift_fragment_new_generation(): void
{
    update_option('boblift_fragment_generation', str_replace('-', '', wp_generate_uuid4()), false);
}

/**
 * Starts a new generation at the first change in a request and again when the request ends, so a copy built by another
 * visitor while a long save (an import, a bulk REST edit) was still writing is dropped as well. Changes in between
 * write nothing.
 */
function boblift_fragment_flush($subject = null, string $detail = ''): void
{
    static $pending = false;
    if ($pending) {
        return;
    }
    $pending = true;
    boblift_fragment_log_flush($subject, $detail);
    boblift_fragment_new_generation();
    add_action('shutdown', 'boblift_fragment_new_generation', 1);
}

/**
 * Prints the section that $render prints, from the cache when it holds this language's copy. Logged-in visitors and
 * previews always get a fresh build, so an editor checking a change never sees an old copy.
 */
function boblift_fragment(string $name, callable $render): void
{
    $language = function_exists('boblift_current_language_slug') ? (string) boblift_current_language_slug() : '';
    $key = 'boblift_frag_' . $name . '_' . ($language !== '' ? $language : 'default') . '_' . boblift_fragment_generation();
    $bypass = is_user_logged_in() || is_preview();
    if (!$bypass) {
        $html = get_transient($key);
        if (is_string($html)) {
            echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup the theme rendered and escaped earlier.
            return;
        }
    }
    ob_start();
    try {
        $render();
    } finally {
        $html = (string) ob_get_clean();
    }
    if (!$bypass) {
        set_transient($key, $html, BOBLIFT_FRAGMENT_TTL);
    }
    echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup the theme rendered and escaped above.
}

add_action('save_post', 'boblift_fragment_flush');
add_action('deleted_post', 'boblift_fragment_flush');
add_action('trashed_post', 'boblift_fragment_flush');
add_action('untrashed_post', 'boblift_fragment_flush');
add_action('created_term', 'boblift_fragment_flush');
add_action('edited_term', 'boblift_fragment_flush');
add_action('delete_term', 'boblift_fragment_flush');
add_action('switch_theme', 'boblift_fragment_flush');
// Post meta set through the REST routes (specs, FAQ, copy) without a full save.
add_action('updated_post_meta', 'boblift_fragment_meta_flush', 10, 3);
add_action('added_post_meta', 'boblift_fragment_meta_flush', 10, 3);
add_action('deleted_post_meta', 'boblift_fragment_meta_flush', 10, 3);
add_action('updated_option', 'boblift_fragment_option_flush');
add_action('added_option', 'boblift_fragment_option_flush');

function boblift_fragment_meta_flush($meta_ids, $object_id, $meta_key): void
{
    $meta_key = (string) $meta_key;
    $bookkeeping = function_exists('boblift_indexnow_meta_is_bookkeeping')
        ? boblift_indexnow_meta_is_bookkeeping($meta_key)
        : in_array($meta_key, ['_edit_lock', '_edit_last'], true) || str_starts_with($meta_key, '_oembed_');
    if (!$bookkeeping) {
        boblift_fragment_flush($object_id, $meta_key);
    }
}

/**
 * The options the cached sections read: theme settings (site texts included), Polylang's settings, the site name, its
 * addresses (links and images are absolute) and
 * front page, and the theme's own version counters for product data and the product menu. Options written while a
 * page renders (image facts, IndexNow's queue) are not on the list, so a page view never empties the cache.
 */
function boblift_fragment_option_flush($option): void
{
    $option = (string) $option;
    $watched = ['boblift_theme_options', 'polylang', 'blogname', 'blogdescription', 'home', 'siteurl', 'page_on_front', 'sticky_posts', 'boblift_products_cache_version', 'boblift_product_menu_version'];
    if (in_array($option, $watched, true) || str_starts_with($option, 'theme_mods_')) {
        boblift_fragment_flush(null, $option);
    }
}

/**
 * Temporary (2026-11-27): the latest changes that started a new generation (the newest 40 rows are kept), with the hook, the post or option behind
 * it and the request, so the profiler can show why the cached sections were rebuilt. Remove with inc/render-timing.php.
 */
function boblift_fragment_log_flush($subject, string $detail): void
{
    global $wpdb;
    $request = (defined('DOING_CRON') && DOING_CRON) ? 'cron' : ((defined('REST_REQUEST') && REST_REQUEST) || str_contains((string) ($_SERVER['REQUEST_URI'] ?? ''), '/wp-json/') ? 'rest' : (is_admin() ? 'admin' : 'front'));
    $entry = gmdate('H:i:s') . ' ' . current_filter() . ' ' . (is_scalar($subject) ? (string) $subject : '') . ' ' . $detail . ' ' . $request . ' ' . substr((string) strtok((string) ($_SERVER['REQUEST_URI'] ?? ''), '?'), 0, 60);
    // One row per change, under its own name, so two requests logging at once cannot overwrite each other's entry.
    $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')", 'boblift_fflog_' . str_replace('-', '', wp_generate_uuid4()), $entry));
    $cutoff = $wpdb->get_var("SELECT option_id FROM {$wpdb->options} WHERE option_name LIKE 'boblift\\_fflog\\_%' ORDER BY option_id DESC LIMIT 1 OFFSET 40");
    if ($cutoff) {
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'boblift\\_fflog\\_%%' AND option_id <= %d", (int) $cutoff));
    }
}

/** The 20 most recent logged changes, newest first. */
function boblift_fragment_flush_log(): array
{
    global $wpdb;
    return array_map('strval', (array) $wpdb->get_col("SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE 'boblift\\_fflog\\_%' ORDER BY option_id DESC LIMIT 20"));
}
