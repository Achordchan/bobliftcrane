<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST: the write-only boblift_content_insert field on wp/v2 posts, cases, products and pages. It adds one piece of
 * HTML after a given paragraph without sending the whole body back, so an internal link can go into an old classic-HTML
 * article (which has no blocks for the section routes to edit) through the WordPress.com connector.
 *
 * Write: {"after": "a snippet of the paragraph's raw HTML", "html": "<p>New sentence with a link.</p>"}, or a list of
 * those. The snippet must occur exactly once in the content. The HTML goes after the element that holds it: the
 * paragraph or heading, or the whole outermost list, table, blockquote or figure when the snippet sits inside one, and
 * after the block's closing comment if one follows. HTML that is already in the content is skipped, so sending the same
 * request twice changes nothing. A request that also sends "content" is refused: send one or the other.
 *
 * rest_pre_insert_{type} takes a per-post lock that is held until the save is done, so insertion requests into one item
 * run one at a time and the core update reloads the body saved by the one before, then checks the insertion against the
 * stored body before anything is saved, so a snippet that cannot be placed refuses the whole request. The field's
 * update callback then writes it with a compare-and-set on the body it read, retrying from the fresh body if any other
 * save changed it in between, so no concurrent edit is overwritten. The HTML goes through the same KSES filter a normal
 * save applies for the user, and the new body is kept as a revision.
 */
function boblift_content_insert_types(): array
{
    return ['post', 'case', 'product', 'page'];
}

function boblift_register_rest_content_insert_field(): void
{
    register_rest_field(boblift_content_insert_types(), 'boblift_content_insert', [
        'get_callback' => null,
        'update_callback' => static fn($value, WP_Post $post) => boblift_rest_save_content_insert($post->ID, $value),
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
    if ($request['content'] !== null) {
        return new WP_Error('boblift_insert_content', 'Send either "content" or "boblift_content_insert", not both.', ['status' => 400]);
    }
    // The core update that runs before the field callback writes the whole post back, body included. Holding this lock
    // from here until rest_after_insert keeps a second insertion request from writing a body it loaded before the first
    // one's insertion was saved.
    $post_id = (int) ($prepared->ID ?? 0);
    if ($post_id > 0 && !boblift_content_insert_lock($post_id)) {
        return new WP_Error('boblift_insert_busy', 'Another insertion into this item is being saved; try again.', ['status' => 409]);
    }
    // This request loaded the post before the lock was held; drop that copy so the core update reloads the saved body.
    if ($post_id > 0) {
        clean_post_cache($post_id);
    }
    $checked = boblift_content_insert_apply(boblift_content_insert_stored($post_id), $value);
    if (is_wp_error($checked)) {
        boblift_content_insert_lock(0);
        return $checked;
    }
    return $prepared;
}
foreach (boblift_content_insert_types() as $boblift_insert_type) {
    add_filter('rest_pre_insert_' . $boblift_insert_type, 'boblift_rest_pre_insert_content_insert', 10, 2);
    add_action('rest_after_insert_' . $boblift_insert_type, static fn() => boblift_content_insert_lock(0));
}
unset($boblift_insert_type);
add_action('shutdown', static fn() => boblift_content_insert_lock(0));

/**
 * A named database lock per post, taken with the post's ID and released with 0. If the save fails before
 * rest_after_insert runs, the shutdown hook releases it, and MySQL drops it anyway when the connection closes.
 */
function boblift_content_insert_lock(int $post_id): bool
{
    global $wpdb;
    static $held = '';
    if ($post_id > 0) {
        $name = 'boblift_content_insert_' . $post_id;
        if ($held !== $name) {
            if ((int) $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 10)', $name)) !== 1) {
                return false;
            }
            $held = $name;
        }
        return true;
    }
    if ($held !== '') {
        $wpdb->query($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $held));
        $held = '';
    }
    return true;
}

/** The body as stored right now, read past the object cache. */
function boblift_content_insert_stored(int $post_id): string
{
    global $wpdb;
    return $post_id > 0 ? (string) $wpdb->get_var($wpdb->prepare("SELECT post_content FROM {$wpdb->posts} WHERE ID = %d", $post_id)) : '';
}

/** Applies every insertion in a request value to $content, filtering the HTML the way a save by this user would. */
function boblift_content_insert_apply(string $content, $value): string|WP_Error
{
    $inserts = is_array($value) && array_key_exists('after', $value) ? [$value] : (array) $value;
    foreach ($inserts as $insert) {
        $html = is_array($insert) ? (string) ($insert['html'] ?? '') : '';
        if (!current_user_can('unfiltered_html')) {
            $html = wp_kses_post($html);
        }
        $content = boblift_content_insert_after($content, is_array($insert) ? (string) ($insert['after'] ?? '') : '', $html);
        if (is_wp_error($content)) {
            return $content;
        }
    }
    return $content;
}

function boblift_rest_save_content_insert(int $post_id, $value): bool|WP_Error
{
    global $wpdb;
    for ($attempt = 0; $attempt < 3; $attempt++) {
        $stored = boblift_content_insert_stored($post_id);
        $content = boblift_content_insert_apply($stored, $value);
        if (is_wp_error($content) || $content === $stored) {
            return is_wp_error($content) ? $content : true;
        }
        // Compare-and-set, byte for byte (the column collation ignores case and accents): only replace the body this
        // attempt read; any save in between makes this match nothing.
        $updated = $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->posts} SET post_content = %s, post_modified = %s, post_modified_gmt = %s WHERE ID = %d AND BINARY post_content = BINARY %s",
            $content,
            current_time('mysql'),
            current_time('mysql', true),
            $post_id,
            $stored
        ));
        if ($updated === 1) {
            clean_post_cache($post_id);
            wp_save_post_revision($post_id);
            return true;
        }
    }
    return new WP_Error('boblift_insert_busy', 'The item kept changing while the insertion was saved; try again.', ['status' => 409]);
}

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
 * Where new content can go for text at $pos: after the outermost list, table, blockquote or figure holding it (a
 * paragraph inside a list item, a cell, a quote or a block's figure wrapper is no place for it), else after the
 * innermost paragraph or heading holding it. Returns the offset just past that element's closing tag, or null when
 * there is no such element or it is never closed.
 */
function boblift_content_insert_boundary(string $content, int $pos): ?int
{
    $void = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr'];
    // Comments and script or style bodies hold no live tags: blank them out, keeping every offset.
    $scan = preg_replace_callback(
        '~<!--.*?-->|(?<=<script)\b[^>]*>.*?(?=</script>)|(?<=<style)\b[^>]*>.*?(?=</style>)~is',
        static fn(array $m): string => str_repeat(' ', strlen($m[0])),
        $content
    );
    preg_match_all('~<(/?)([a-z][a-z0-9]*)\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*?(/?)>~i', $scan, $tags, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
    $stack = [];
    $target = null;
    foreach ($tags as $tag) {
        $offset = $tag[0][1];
        $name = strtolower($tag[2][0]);
        $closing = $tag[1][0] === '/';
        if ($offset >= $pos && $target === null) {
            // The stack now holds the snippet's ancestors: pick the element to insert after.
            foreach ($stack as $depth => $open) {
                if (in_array($open, ['ul', 'ol', 'table', 'blockquote', 'figure'], true)) {
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
