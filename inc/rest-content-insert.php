<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST: the write-only boblift_content_insert field on wp/v2 posts, cases, products and pages. It adds one piece of HTML
 * after a given paragraph without sending the whole body back, so an internal link can go into an old classic-HTML
 * article (which has no blocks for the section routes to edit) through the WordPress.com connector.
 *
 * Write: {"after": "a snippet of the paragraph's raw HTML", "html": "<p>New sentence with a link.</p>"}, or a list of
 * those. The snippet must occur exactly once in the stored content. The HTML goes after the element that holds it: the
 * paragraph, heading or table cell's closing tag, the whole list for a list item, and the block's closing comment if one
 * follows. HTML that is already in the content is skipped, so sending the same request twice changes nothing.
 * Each write is kept as a revision, so it can be undone from the editor.
 */
function boblift_register_rest_content_insert_field(): void
{
    register_rest_field(['post', 'case', 'product', 'page'], 'boblift_content_insert', [
        'get_callback' => null,
        'update_callback' => static function ($value, WP_Post $post) {
            $inserts = is_array($value) && array_key_exists('after', $value) ? [$value] : (array) $value;
            $content = (string) $post->post_content;
            foreach ($inserts as $insert) {
                $content = boblift_content_insert_after(
                    $content,
                    is_array($insert) ? (string) ($insert['after'] ?? '') : '',
                    is_array($insert) ? (string) ($insert['html'] ?? '') : ''
                );
                if (is_wp_error($content)) {
                    return $content;
                }
            }
            if ($content === (string) $post->post_content) {
                return true;
            }
            $saved = wp_update_post(['ID' => $post->ID, 'post_content' => wp_slash($content)], true);
            return is_wp_error($saved) ? $saved : true;
        },
        'schema' => [
            'description' => 'Write only: {"after": snippet, "html": html} (or a list) adds html after the element holding the snippet.',
            'type' => ['object', 'array'],
            'context' => ['edit'],
        ],
    ]);
}
add_action('rest_api_init', 'boblift_register_rest_content_insert_field');

function boblift_content_insert_after(string $content, string $after, string $html): string|WP_Error
{
    $html = trim($html);
    if ($after === '' || $html === '') {
        return new WP_Error('boblift_insert_empty', 'Send both "after" and "html".', ['status' => 400]);
    }
    if (str_contains($content, $html)) {
        return $content;
    }
    $count = substr_count($content, $after);
    if ($count !== 1) {
        return new WP_Error('boblift_insert_anchor', sprintf('The "after" snippet occurs %d times; it must occur exactly once.', $count), ['status' => 400]);
    }
    $start = strpos($content, $after) + strlen($after);
    // A snippet that ends with the element's own closing tag is searched from that tag.
    if (preg_match('~</(?:p|li|h[1-6]|td|blockquote)>\s*$~i', $after, $tail)) {
        $start -= strlen($tail[0]);
    }
    if (!preg_match('~</(p|li|h[1-6]|td|blockquote)>~i', $content, $close, PREG_OFFSET_CAPTURE, $start)) {
        return new WP_Error('boblift_insert_element', 'No closing paragraph, heading or list item follows the snippet.', ['status' => 400]);
    }
    $end = $close[0][1] + strlen($close[0][0]);
    // A list item's sentence belongs after the whole list.
    if (strtolower($close[1][0]) === 'li') {
        if (!preg_match('~</(ul|ol)>~i', $content, $list, PREG_OFFSET_CAPTURE, $end)) {
            return new WP_Error('boblift_insert_element', 'The list item has no closing list tag.', ['status' => 400]);
        }
        $end = $list[0][1] + strlen($list[0][0]);
    }
    // Keep block markup valid: step past the block's closing comment when one comes next.
    if (preg_match('~\G\s*<!-- /wp:[a-z0-9/-]+ -->~', $content, $comment, 0, $end)) {
        $end += strlen($comment[0]);
    }
    return substr($content, 0, $end) . "\n" . $html . substr($content, $end);
}
