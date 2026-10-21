<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Chinese and Japanese headings end without a full stop: "下单前买家常问的问题" rather than "…问题。". The titles come from many
 * places (theme options, translation files, page and post titles), so the stop is dropped from the finished page:
 * only a "。" that closes an h1, h2 or h3 goes; sentences in body text keep theirs. Other languages are left as written.
 */
function boblift_strip_heading_full_stops(string $html): string
{
    return (string) preg_replace('~(<h([1-3])\b[^>]*>(?:(?!</h\2>).)*?)。((?:\s*</(?!h[1-3]\b)[a-z]+>)*\s*</h\2>)~su', '$1$3', $html);
}

add_action('template_redirect', static function (): void {
    if (is_admin() || wp_doing_ajax() || is_feed() || !function_exists('boblift_current_language_slug') || !in_array(boblift_current_language_slug(), ['zh', 'ja'], true)) {
        return;
    }
    ob_start('boblift_strip_heading_full_stops');
});
