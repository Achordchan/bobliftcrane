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
    $error = error_get_last();
    if (!is_array($error) || !in_array($error['type'] ?? 0, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR], true)) {
        return;
    }
    $route = defined('REST_REQUEST') && REST_REQUEST && isset($GLOBALS['wp']->query_vars['rest_route']) ? (string) $GLOBALS['wp']->query_vars['rest_route'] : '';
    $entry = [
        'time' => gmdate('c'),
        'message' => substr((string) ($error['message'] ?? ''), 0, 6000),
        'file' => (string) ($error['file'] ?? ''),
        'line' => (int) ($error['line'] ?? 0),
        'method' => (string) ($_SERVER['REQUEST_METHOD'] ?? ''),
        'uri' => substr((string) preg_replace('/([?&](?:token|signature|nonce|_wpnonce)=)[^&]*/i', '$1…', (string) ($_SERVER['REQUEST_URI'] ?? '')), 0, 500),
        'rest_route' => $route,
    ];
    $log = get_option(BOBLIFT_FATAL_LOG_OPTION, []);
    $log = array_slice(array_merge([$entry], is_array($log) ? $log : []), 0, BOBLIFT_FATAL_LOG_SIZE);
    update_option(BOBLIFT_FATAL_LOG_OPTION, $log, false);
}
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
