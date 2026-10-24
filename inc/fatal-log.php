<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Keeps the last few PHP fatal errors (message with its stack trace, file, line, and the request that hit it), so a
 * failure that WordPress.com only reports as "the site is temporarily unavailable" can be read back through the
 * connector, which reaches wp/v2 only and has no access to the server's error log.
 *
 * Read: GET /wp/v2/pages/<any page id>?context=edit, field boblift_fatal_log (newest first; administrators only).
 * Clear: POST /wp/v2/pages/<id> with {"boblift_fatal_log": []}.
 */
const BOBLIFT_FATAL_LOG_OPTION = 'boblift_fatal_log';
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
    global $wpdb;
    // Two requests failing at once would otherwise both read the same log and the later write would drop the other
    // entry; the lock serialises the read-modify-write (read past the object cache), and MySQL drops it if the
    // connection closes first.
    $locked = (int) $wpdb->get_var("SELECT GET_LOCK('boblift_fatal_log', 5)") === 1;
    wp_cache_delete(BOBLIFT_FATAL_LOG_OPTION, 'options');
    wp_cache_delete('notoptions', 'options');
    $log = get_option(BOBLIFT_FATAL_LOG_OPTION, []);
    $log = array_slice(array_merge([$entry], is_array($log) ? $log : []), 0, BOBLIFT_FATAL_LOG_SIZE);
    update_option(BOBLIFT_FATAL_LOG_OPTION, $log, false);
    if ($locked) {
        $wpdb->query("SELECT RELEASE_LOCK('boblift_fatal_log')");
    }
}
// Kept until shutdown so that the handler has memory to work with after the limit was hit.
$GLOBALS['boblift_fatal_log_reserve'] = str_repeat(' ', 256 * 1024);
register_shutdown_function('boblift_fatal_log_record');

function boblift_fatal_log_register_field(): void
{
    register_rest_field('page', 'boblift_fatal_log', [
        'get_callback' => static function () {
            if (!current_user_can('manage_options')) {
                return null;
            }
            $log = get_option(BOBLIFT_FATAL_LOG_OPTION, []);
            return is_array($log) ? array_values($log) : [];
        },
        'update_callback' => static function ($value) {
            if (!current_user_can('manage_options')) {
                return new WP_Error('boblift_fatal_log_forbidden', 'Clearing the fatal error log needs manage_options.', ['status' => 403]);
            }
            if ($value !== []) {
                return new WP_Error('boblift_fatal_log_invalid', 'Send [] to clear the log; it cannot be written otherwise.', ['status' => 400]);
            }
            delete_option(BOBLIFT_FATAL_LOG_OPTION);
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
