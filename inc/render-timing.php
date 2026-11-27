<?php
/**
 * Temporary render profiler (2026-11-27): finds which parts of a page are slow to build on a cache miss.
 *
 * Only a request carrying ?boblift_timing=1 records anything. It ends the page with an HTML comment listing each
 * marked step: milliseconds since the request started, milliseconds since the previous mark and database queries
 * run so far. Everyone else gets the page unchanged. Remove this file once the slow parts are cached.
 */

function boblift_timing_enabled(): bool
{
    static $enabled = null;
    if ($enabled === null) {
        $enabled = isset($_GET['boblift_timing']) && $_GET['boblift_timing'] === '1';
    }
    return $enabled;
}

function boblift_timing(string $label): void
{
    if (!boblift_timing_enabled()) {
        return;
    }
    $GLOBALS['boblift_timing_marks'][] = [$label, microtime(true), function_exists('get_num_queries') ? get_num_queries() : 0];
}

boblift_timing('theme_loaded');
add_action('init', static fn() => boblift_timing('init_start'), 0);
add_action('wp_loaded', static fn() => boblift_timing('wp_loaded'), PHP_INT_MAX);
add_action('template_redirect', static fn() => boblift_timing('template_redirect'), 0);
add_action('get_header', static fn() => boblift_timing('get_header'), 0);
add_action('wp_head', static fn() => boblift_timing('wp_head_start'), 0);
add_action('wp_head', static fn() => boblift_timing('wp_head_end'), PHP_INT_MAX);
add_action('wp_body_open', static fn() => boblift_timing('body_open'), 0);
add_action('get_footer', static fn() => boblift_timing('get_footer'), 0);
add_action('wp_footer', static fn() => boblift_timing('wp_footer_start'), 0);
add_action('wp_footer', static fn() => boblift_timing('wp_footer_end'), PHP_INT_MAX);

add_action('shutdown', static function (): void {
    if (!boblift_timing_enabled() || empty($GLOBALS['boblift_timing_marks']) || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }
    $start = (float) ($_SERVER['REQUEST_TIME_FLOAT'] ?? $GLOBALS['boblift_timing_marks'][0][1]);
    $previous = $start;
    $lines = [];
    foreach ($GLOBALS['boblift_timing_marks'] as [$label, $time, $queries]) {
        $lines[] = sprintf('%-22s %7.1f %7.1f q=%d', preg_replace('/[^a-z0-9_:.-]/i', '', $label), ($time - $start) * 1000, ($time - $previous) * 1000, $queries);
        $previous = $time;
    }
    $lines[] = sprintf('%-22s %7.1f %7.1f q=%d', 'shutdown', (microtime(true) - $start) * 1000, (microtime(true) - $previous) * 1000, function_exists('get_num_queries') ? get_num_queries() : 0);
    if (function_exists('boblift_fragment_generation')) {
        $lines[] = 'fragment generation ' . boblift_fragment_generation();
        foreach (boblift_fragment_flush_log() as $entry) {
            $lines[] = 'flush ' . preg_replace('/[^\w:.\/ -]/', '', (string) $entry);
        }
    }
    echo "\n<!-- boblift-timing\n" . implode("\n", $lines) . "\n-->";
}, 0);
