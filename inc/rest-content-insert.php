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
 * those. The snippet must occur exactly once in the content. The HTML goes after the element that holds it: the
 * paragraph or heading, or the whole outermost list or table when the snippet sits inside one, and after the block's
 * closing comment if one follows. HTML that is already in the content is skipped, so sending the same request twice
 * changes nothing.
 * The insertion is worked out in rest_pre_insert_{type}, before anything is saved, against the content the request
 * sends or else the stored content, so a snippet that cannot be placed refuses the whole request. The result is saved as
 * part of the normal update, which keeps a revision that can be restored from the editor.
 */
function boblift_content_insert_types(): array
{
    return ['post', 'case', 'product', 'page'];
}

function boblift_register_rest_content_insert_field(): void
{
    register_rest_field(boblift_content_insert_types(), 'boblift_content_insert', [
        'get_callback' => null,
        // The work is done before the save in boblift_rest_pre_insert_content_insert().
        'update_callback' => static fn(): bool => true,
        'schema' => [
            'description' => 'Write only: {"after": snippet, "html": html} (or a list) adds html after the element holding the snippet.',
            'type' => ['object', 'array'],
            'context' => ['edit'],
        ],
    ]);
}
add_action('rest_api_init', 'boblift_register_rest_content_insert_field');

function boblift_rest_pre_insert_content_insert(stdClass|WP_Error $prepared, WP_REST_Request $request): stdClass|WP_Error
{
    $value = $request['boblift_content_insert'];
    if (is_wp_error($prepared) || $value === null) {
        return $prepared;
    }
    if (isset($prepared->post_content)) {
        $content = (string) $prepared->post_content;
    } else {
        $post = !empty($prepared->ID) ? get_post((int) $prepared->ID) : null;
        $content = $post instanceof WP_Post ? (string) $post->post_content : '';
    }
    $inserts = is_array($value) && array_key_exists('after', $value) ? [$value] : (array) $value;
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
    $prepared->post_content = $content;
    return $prepared;
}
foreach (boblift_content_insert_types() as $boblift_insert_type) {
    add_filter('rest_pre_insert_' . $boblift_insert_type, 'boblift_rest_pre_insert_content_insert', 10, 2);
}
unset($boblift_insert_type);

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
    // The snippet's position is its first text, so tags it opens count as its ancestors.
    preg_match('~^(?:\s*<[^>]+>)*~', $after, $lead);
    $end = boblift_content_insert_boundary($content, strpos($content, $after) + strlen($lead[0]));
    if ($end === null) {
        return new WP_Error('boblift_insert_element', 'The snippet is not inside a paragraph, heading, list or table whose end can be found.', ['status' => 400]);
    }
    // Keep block markup valid: step past the block's closing comment when one comes next.
    if (preg_match('~\G\s*<!-- /wp:[a-z0-9/-]+ -->~', $content, $comment, 0, $end)) {
        $end += strlen($comment[0]);
    }
    return substr($content, 0, $end) . "\n" . $html . substr($content, $end);
}

/**
 * Where new content can go for text at $pos: after the outermost list or table holding it (a paragraph inside a list
 * item or a cell is no place for it), else after the innermost paragraph or heading holding it. Returns the offset just
 * past that element's closing tag, or null when there is no such element or it is never closed.
 */
function boblift_content_insert_boundary(string $content, int $pos): ?int
{
    $void = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr'];
    preg_match_all('~<(/?)([a-z][a-z0-9]*)\b[^>]*?(/?)>~i', $content, $tags, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
    $stack = [];
    $target = null;
    foreach ($tags as $tag) {
        $offset = $tag[0][1];
        $name = strtolower($tag[2][0]);
        $closing = $tag[1][0] === '/';
        if ($offset >= $pos && $target === null) {
            // The stack now holds the snippet's ancestors: pick the element to insert after.
            foreach ($stack as $depth => $open) {
                if (in_array($open, ['ul', 'ol', 'table'], true)) {
                    $target = $depth;
                    break;
                }
            }
            if ($target === null) {
                foreach (array_reverse($stack, true) as $depth => $open) {
                    if (preg_match('~^(p|h[1-6])$~', $open)) {
                        $target = $depth;
                        break;
                    }
                }
            }
            if ($target === null) {
                return null;
            }
        }
        if (in_array($name, $void, true) || $tag[3][0] === '/') {
            continue;
        }
        if (!$closing) {
            $stack[] = $name;
            continue;
        }
        // A close tag also ends anything left open inside it (an unclosed <p> or <li> in classic HTML).
        $at = array_search($name, array_reverse($stack, true), true);
        if ($at === false) {
            continue;
        }
        $stack = array_slice($stack, 0, $at);
        if ($target !== null && $at <= $target) {
            return $offset + strlen($tag[0][0]);
        }
    }
    return null;
}
