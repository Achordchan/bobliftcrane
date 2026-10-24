<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Keeps the last few PHP fatal errors (message with its stack trace, file, line, and the request that hit it), so a
 * failure that WordPress.com only reports as "the site is temporarily unavailable" can be read back through the
 * connector, which reaches wp/v2 only and has no access to the server's error log.
 *
 * Read: GET /wp/v2/pages/<any page id>?context=edit, field boblift_fatal_log (newest first; administrators only). The
 * WordPress.com connector returns only core fields and registered meta, so the same list is also the read-only page meta
 * _boblift_fatal_log, as a JSON string.
 * Clear: POST /wp/v2/pages/<id> with {"boblift_fatal_log": []}.
 */
const BOBLIFT_FATAL_LOG_PREFIX = 'boblift_fatal_log_';
const BOBLIFT_FATAL_LOG_SIZE = 5;

function boblift_fatal_log_record(): void
{
    // Frees the headroom kept below, so an out-of-memory error can still be recorded.
    $GLOBALS['boblift_fatal_log_reserve'] = null;
    $error = error_get_last();
    if (!is_array($error) || !in_array($error['type'] ?? 0, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR], true)) {
        return;
    }
    $route = defined('REST_REQUEST') && REST_REQUEST && isset($GLOBALS['wp']->query_vars['rest_route']) ? (string) $GLOBALS['wp']->query_vars['rest_route'] : '';
    $entry = [
        'time' => gmdate('c'),
        // Stack trace lines keep the function names only: their argument values could hold passwords or tokens.
        'message' => substr((string) preg_replace('/^(#\d+ [^\n]*?: [^\n(]+)\([^\n]*\)$/m', '$1()', (string) ($error['message'] ?? '')), 0, 6000),
        'file' => (string) ($error['file'] ?? ''),
        'line' => (int) ($error['line'] ?? 0),
        'method' => (string) ($_SERVER['REQUEST_METHOD'] ?? ''),
        // The path only: query strings can carry credentials (signed-request tokens, reset keys, access tokens).
        'uri' => substr((string) wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), 0, 500),
        'rest_route' => $route,
    ];
    // Each entry is its own option (added, never rewritten), so concurrent failures and a concurrent clear cannot
    // overwrite one another; the name sorts by time. Entries beyond the newest few are pruned.
    add_option(BOBLIFT_FATAL_LOG_PREFIX . sprintf('%.6F', microtime(true)) . '_' . bin2hex(random_bytes(4)), $entry, '', false);
    foreach (array_slice(boblift_fatal_log_names(), BOBLIFT_FATAL_LOG_SIZE) as $name) {
        delete_option($name);
    }
}

/** Names of the stored entries, newest first. */
function boblift_fatal_log_names(): array
{
    global $wpdb;
    return array_map('strval', (array) $wpdb->get_col($wpdb->prepare(
        "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_name DESC",
        $wpdb->esc_like(BOBLIFT_FATAL_LOG_PREFIX) . '%'
    )));
}

// Kept until shutdown so that the handler has memory to work with after the limit was hit.
$GLOBALS['boblift_fatal_log_reserve'] = str_repeat(' ', 256 * 1024);
register_shutdown_function('boblift_fatal_log_record');

/** The stored entries, newest first. */
function boblift_fatal_log_entries(): array
{
    return array_values(array_filter(array_map(static fn(string $name) => get_option($name), array_slice(boblift_fatal_log_names(), 0, BOBLIFT_FATAL_LOG_SIZE)), 'is_array'));
}

function boblift_fatal_log_register_meta(): void
{
    register_post_meta('page', '_boblift_fatal_log', [
        'type' => 'string',
        'single' => true,
        'default' => '',
        'show_in_rest' => true,
        // Read only: nothing is stored under this key, the value is the log itself. Writes and deletes are accepted and dropped
        // (below), so an editor or client that sends every registered meta back on save is not refused.
        'auth_callback' => static fn(): bool => current_user_can('manage_options'),
    ]);
}
add_action('init', 'boblift_fatal_log_register_meta', 20);

foreach (['add_post_metadata', 'update_post_metadata', 'delete_post_metadata'] as $boblift_fatal_log_hook) {
    add_filter($boblift_fatal_log_hook, static fn($check, $object_id, $meta_key) => $meta_key === '_boblift_fatal_log' ? true : $check, 10, 3);
}
unset($boblift_fatal_log_hook);

add_filter('get_post_metadata', static function ($value, $object_id, $meta_key, $single) {
    if ($meta_key !== '_boblift_fatal_log') {
        return $value;
    }
    $json = current_user_can('manage_options') ? (string) wp_json_encode(boblift_fatal_log_entries(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
    return $single ? $json : [$json];
}, 10, 4);

function boblift_fatal_log_register_field(): void
{
    register_rest_field('page', 'boblift_fatal_log', [
        'get_callback' => static function () {
            if (!current_user_can('manage_options')) {
                return null;
            }
            return boblift_fatal_log_entries();
        },
        'update_callback' => static function ($value) {
            if (!current_user_can('manage_options')) {
                return new WP_Error('boblift_fatal_log_forbidden', 'Clearing the fatal error log needs manage_options.', ['status' => 403]);
            }
            if ($value !== []) {
                return new WP_Error('boblift_fatal_log_invalid', 'Send [] to clear the log; it cannot be written otherwise.', ['status' => 400]);
            }
            foreach (boblift_fatal_log_names() as $name) {
                delete_option($name);
            }
            return true;
        },
        'schema' => [
            'description' => 'Last PHP fatal errors, newest first (administrators only). Send [] to clear.',
            'type' => ['array', 'null'],
            'context' => ['edit'],
        ],
    ]);
}
add_action('rest_api_init', 'boblift_fatal_log_register_field');
