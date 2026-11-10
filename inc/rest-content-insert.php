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
 * An item can instead correct a phrase: {"replace": "slew 360° continuously", "with": "slew 360°"}. Both are plain text
 * (no "<", ">" or quotes); "replace" must occur exactly once in page text (never inside a tag, attribute, comment,
 * script or style), not counting occurrences inside an occurrence of "with". When it no longer occurs and "with" does,
 * the item is already done and is skipped, so this too can be sent again safely. A "replace" item goes on its own: a
 * request that holds one and any other item is refused.
 *
 * An item can also swap an image for a newer copy in the media library: {"image": "/wp-content/uploads/2026/09/a.png",
 * "src": "/wp-content/uploads/2026/11/a-v2.png", "alt": "New alt text"}. "image" is the current src of exactly one
 * <img> in the content; its src becomes "src" (a file in this site's media library) and, when "alt" is sent, its alt
 * becomes that plain text. Other attributes stay as they are. When no <img> has the old src and one has the new src, the
 * item is already done and is skipped. The old srcset and sizes are removed. Like "replace", an "image" item goes on its
 * own, one per request.
 *
 * rest_pre_insert_{type} takes a per-post lock that is held until the save is done, every REST update of the item takes
 * the same lock before it loads the post, and any other save takes it just before its row is written, so nothing lands
 * in between; the request's own core update writes the body as stored at that moment. It then checks the insertion
 * against the stored body before anything is saved, so a snippet that cannot be placed refuses the whole request. The
 * field's update callback writes the insertion with a byte-for-byte compare-and-set on the body it read, retrying from
 * the fresh body if it changed, and the response shows the saved body. The HTML goes through the same KSES filter a
 * normal save applies for the user, and the new body is kept as a revision.
 */
function boblift_content_insert_types(): array
{
    return ['post', 'case', 'product', 'page'];
}

function boblift_register_rest_content_insert_field(): void
{
    register_rest_field(boblift_content_insert_types(), 'boblift_content_insert', [
        'get_callback' => null,
        'update_callback' => static function ($value, WP_Post $post) {
            $saved = boblift_rest_save_content_insert($post->ID, $value);
            if (is_wp_error($saved)) {
                return $saved;
            }
            // The response is built from this object, so it should show the body as saved.
            $post->post_content = $saved;
            return true;
        },
        'schema' => [
            'description' => 'Write only: {"after": snippet, "html": html} adds html after the element holding the snippet; {"replace": text, "with": text} corrects a plain-text phrase; {"image": old src, "src": new src, "alt": text} swaps an image for another media library file. One item or a list.',
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
    if ($post_id > 0) {
        if (!boblift_content_insert_lock($post_id)) {
            return new WP_Error('boblift_insert_busy', 'Another save of this item is in progress; try again.', ['status' => 409]);
        }
        boblift_content_insert_active($post_id);
        // This request loaded the post before the lock was held; drop that copy so nothing reuses it.
        clean_post_cache($post_id);
    }
    $checked = boblift_content_insert_apply(boblift_content_insert_stored($post_id), $value);
    if (is_wp_error($checked)) {
        boblift_content_insert_release();
        return $checked;
    }
    return $prepared;
}
foreach (boblift_content_insert_types() as $boblift_insert_type) {
    add_filter('rest_pre_insert_' . $boblift_insert_type, 'boblift_rest_pre_insert_content_insert', 10, 2);
    add_action('rest_after_insert_' . $boblift_insert_type, 'boblift_content_insert_release');
}
unset($boblift_insert_type);
add_action('shutdown', 'boblift_content_insert_release');

/**
 * Every update of one of these items takes the same per-post lock just before its row is written, so no other save can
 * land between an insertion request's core update and its own write. Limit: a non-REST save (quick edit, a plugin
 * calling wp_update_post()) loads the post inside wp_update_post() before this filter, so if it loads in the moment
 * between an insertion's write and its own lock, it can write the older body back. That is WordPress's usual
 * last-save-wins for such saves, the same as two editors saving one post; insertions are rare manual operator writes,
 * so it is accepted rather than wrapping every save in the site. Within an insertion request the core update
 * writes the body as stored at that moment, never the copy loaded earlier; other saves hold the lock only until their
 * row is written (wp_insert_post). If the lock cannot be had within 10 seconds an ordinary save goes ahead unlocked, as
 * it would without this file.
 */
function boblift_content_insert_guard_core_write(array $data, array $postarr, array $unsanitized, bool $update): array
{
    $post_id = (int) ($postarr['ID'] ?? 0);
    if (!$update || $post_id <= 0 || !in_array($data['post_type'] ?? '', boblift_content_insert_types(), true)) {
        return $data;
    }
    if (boblift_content_insert_active() === $post_id) {
        $data['post_content'] = wp_slash(boblift_content_insert_stored($post_id));
        return $data;
    }
    boblift_content_insert_lock($post_id);
    return $data;
}

/**
 * A REST update of one of these items takes the lock before its handler loads the post and keeps it until the response is
 * built, so a title-only update cannot load a body, wait, and then write it back over an insertion saved meanwhile. The
 * post cache is dropped once the lock is held, so the handler loads the body as stored.
 */
function boblift_content_insert_lock_rest_update($response, $handler, WP_REST_Request $request)
{
    if (!in_array($request->get_method(), ['POST', 'PUT', 'PATCH'], true) || !preg_match('~^/wp/v2/[a-z0-9_-]+/(\d+)$~', $request->get_route(), $id)) {
        return $response;
    }
    $post = get_post((int) $id[1]);
    if ($post instanceof WP_Post && in_array($post->post_type, boblift_content_insert_types(), true) && boblift_content_insert_lock($post->ID)) {
        clean_post_cache($post->ID);
    }
    return $response;
}
add_filter('rest_request_before_callbacks', 'boblift_content_insert_lock_rest_update', 10, 3);
add_filter('rest_request_after_callbacks', static function ($response) {
    boblift_content_insert_release();
    return $response;
});
add_filter('wp_insert_post_data', 'boblift_content_insert_guard_core_write', PHP_INT_MAX, 4);
add_action('wp_insert_post', static function (int $post_id): void {
    if (boblift_content_insert_active() !== $post_id) {
        boblift_content_insert_lock(0, $post_id);
    }
});

/** The item an insertion request in this process is working on, 0 when none; set it by passing an ID. */
function boblift_content_insert_active(?int $set = null): int
{
    static $active = 0;
    if ($set !== null) {
        $active = $set;
    }
    return $active;
}

/** Ends the insertion request's hold: forgets the active item and releases its lock. */
function boblift_content_insert_release(): void
{
    boblift_content_insert_lock(0);
    boblift_content_insert_active(0);
}

/**
 * A named database lock per post. Pass an ID to take it (waiting up to 10 seconds; taking it again is a no-op); pass 0
 * to release whatever is held, or 0 and an ID to release it only if it is that post's. MySQL also drops it when the
 * connection closes.
 */
function boblift_content_insert_lock(int $post_id, int $only = 0): bool
{
    global $wpdb;
    static $held = 0;
    if ($post_id > 0) {
        if ($held !== $post_id) {
            boblift_content_insert_lock(0);
            if ((int) $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 10)', 'boblift_content_insert_' . $post_id)) !== 1) {
                return false;
            }
            $held = $post_id;
        }
        return true;
    }
    if ($held > 0 && ($only === 0 || $only === $held)) {
        $wpdb->query($wpdb->prepare('SELECT RELEASE_LOCK(%s)', 'boblift_content_insert_' . $held));
        $held = 0;
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
    $inserts = is_array($value) && (array_key_exists('after', $value) || array_key_exists('replace', $value) || array_key_exists('image', $value)) ? [$value] : (array) $value;
    // A replacement or an image swap can remove what another item of the request checks for (its own "with", an
    // insertion's HTML, another swap's src), so a retry could not tell that item was done: such an item goes alone.
    if (count($inserts) > 1 && array_filter($inserts, static fn($insert): bool => is_array($insert) && (array_key_exists('replace', $insert) || array_key_exists('image', $insert))) !== []) {
        return new WP_Error('boblift_insert_replace', 'Send a "replace" or "image" item on its own, one per request.', ['status' => 400]);
    }
    foreach ($inserts as $insert) {
        if (is_array($insert) && array_key_exists('replace', $insert)) {
            $content = boblift_content_insert_replace($content, (string) ($insert['replace'] ?? ''), (string) ($insert['with'] ?? ''));
            if (is_wp_error($content)) {
                return $content;
            }
            continue;
        }
        if (is_array($insert) && array_key_exists('image', $insert)) {
            $alt = array_key_exists('alt', $insert) ? (string) $insert['alt'] : null;
            $content = boblift_content_insert_image($content, (string) ($insert['image'] ?? ''), (string) ($insert['src'] ?? ''), $alt);
            if (is_wp_error($content)) {
                return $content;
            }
            continue;
        }
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

/** Writes the insertion and returns the body as saved. */
function boblift_rest_save_content_insert(int $post_id, $value): string|WP_Error
{
    global $wpdb;
    for ($attempt = 0; $attempt < 3; $attempt++) {
        $stored = boblift_content_insert_stored($post_id);
        $content = boblift_content_insert_apply($stored, $value);
        if (is_wp_error($content) || $content === $stored) {
            return $content;
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
            return $content;
        }
    }
    return new WP_Error('boblift_insert_busy', 'The item kept changing while the insertion was saved; try again.', ['status' => 409]);
}

/** Replaces one plain-text phrase in page text, the way boblift_specs "replace" does for products. */
function boblift_content_insert_replace(string $content, string $from, string $to): string|WP_Error
{
    if ($from === '' || $to === '' || $from === $to || preg_match('/[<>"\'\x00]/', $from . $to)) {
        return new WP_Error('boblift_insert_replace', 'A "replace" item needs plain-text "replace" and a different, non-empty "with" (no "<", ">" or quotes).', ['status' => 400]);
    }
    return boblift_product_specs_replace_one($content, $from, $to, 'content');
}

/**
 * Points the one <img> whose src is $old at $new, a file in this site's media library, and sets its alt to $alt when
 * one is given. The old srcset and sizes are dropped, since they list files of the old image. The image's attachment
 * identity follows the file: a wp-image-<id> class is set to the new attachment's ID (other classes kept), and so is
 * the "id" of the image block that encloses this <img>, if any; other blocks are never touched. An <img> inside
 * <picture> or inside another kind of block is refused (see boblift_content_insert_image_block_id). $new must be an
 * image attachment, not a PDF or video. Done already (content returned unchanged) when no <img> has $old and one has $new.
 * Elements and attributes are found with WordPress's HTML tag processor, so a src inside another attribute's value or
 * inside a comment is never taken for an image. A root-relative $new resolves against the site's scheme and host, not its home path, so it also
 * works when WordPress lives in a subdirectory.
 */
function boblift_content_insert_image(string $content, string $old, string $new, ?string $alt): string|WP_Error
{
    $bad_url = static fn(string $url): bool => $url === '' || preg_match('~[\s<>"\'\x00]~', $url) === 1;
    if ($bad_url($old) || $bad_url($new) || $old === $new) {
        return new WP_Error('boblift_insert_image', 'An "image" item needs the current src in "image" and a different new src in "src" (no spaces, "<", ">" or quotes).', ['status' => 400]);
    }
    if ($alt !== null && ($alt === '' || preg_match('/[<>"\x00]/', $alt))) {
        return new WP_Error('boblift_insert_image', 'An "image" item\'s "alt" is plain, non-empty text (no "<", ">" or double quotes).', ['status' => 400]);
    }
    // Only a root-relative or absolute http(s) URL renders the same on every page; an uploads-relative path such as
    // "2026/11/chart.png" would resolve against the article's own URL.
    if (preg_match('~^(/(?!/)|https?://)~i', $new) !== 1) {
        return new WP_Error('boblift_insert_image', 'The new "src" must be a root-relative (/wp-content/…) or absolute http(s) URL.', ['status' => 400]);
    }
    $absolute = $new;
    if (str_starts_with($new, '/')) {
        $home = wp_parse_url(home_url());
        if (!is_array($home) || empty($home['host'])) {
            return new WP_Error('boblift_insert_image', 'The site address could not be read to resolve the new src.', ['status' => 500]);
        }
        $absolute = ($home['scheme'] ?? 'https') . '://' . $home['host'] . (isset($home['port']) ? ':' . $home['port'] : '') . $new;
    }
    $new_id = (int) attachment_url_to_postid($absolute);
    if ($new_id <= 0 || !wp_attachment_is_image($new_id)) {
        return new WP_Error('boblift_insert_image', sprintf('"%s" is not an image in this site\'s media library.', $new), ['status' => 400]);
    }
    $found = 0;
    $done = false;
    $scan = new WP_HTML_Tag_Processor($content);
    while ($scan->next_tag('img')) {
        $src = $scan->get_attribute('src');
        if ($src === $old) {
            $found++;
        } elseif ($src === $new) {
            $done = true;
        }
    }
    if ($found === 0) {
        return $done ? $content : new WP_Error('boblift_insert_image', sprintf('No <img> has the src "%s".', $old), ['status' => 400]);
    }
    if ($found > 1) {
        return new WP_Error('boblift_insert_image', sprintf('%d <img> tags have the src "%s"; it must be exactly one.', $found, $old), ['status' => 400]);
    }
    // A one-off boolean attribute marks the swapped <img> so its offset can be found in the result, then is removed.
    $marker = 'data-boblift-swap-' . bin2hex(random_bytes(6));
    $edit = new WP_HTML_Tag_Processor($content);
    $picture_depth = 0;
    while ($edit->next_tag(['tag_closers' => 'visit'])) {
        $tag = $edit->get_tag();
        if ($tag === 'PICTURE') {
            $picture_depth = max(0, $picture_depth + ($edit->is_tag_closer() ? -1 : 1));
            continue;
        }
        if ($tag !== 'IMG' || $edit->is_tag_closer() || $edit->get_attribute('src') !== $old) {
            continue;
        }
        if ($picture_depth > 0) {
            return new WP_Error('boblift_insert_image', 'This image is inside <picture>; its <source> files cannot be swapped safely.', ['status' => 400]);
        }
        $edit->set_attribute('src', $new);
        $edit->remove_attribute('srcset');
        $edit->remove_attribute('sizes');
        if ($alt !== null) {
            $edit->set_attribute('alt', $alt);
        }
        $had_class = false;
        foreach (iterator_to_array($edit->class_list(), false) as $class) {
            if (preg_match('/^wp-image-\d+$/', $class) === 1) {
                $had_class = true;
                $edit->remove_class($class);
            }
        }
        if ($had_class) {
            $edit->add_class('wp-image-' . $new_id);
        }
        $edit->set_attribute($marker, true);
        break;
    }
    $content = $edit->get_updated_html();
    $at = strpos($content, ' ' . $marker);
    if ($at === false) {
        return new WP_Error('boblift_insert_image', 'The image could not be located after the swap.', ['status' => 500]);
    }
    $content = substr_replace($content, '', $at, strlen($marker) + 1);
    return boblift_content_insert_image_block_id($content, $at, $new_id);
}

/**
 * Keeps the block metadata of the swapped <img> (at byte offset $at) consistent. The enclosing block is found by walking
 * the block comments before $at with a stack. Classic HTML (no block) and a core/html block have no metadata to keep.
 * A core/image block gets its top-level "id" set to $new_id when it has exactly one. Any other enclosing block (cover,
 * media & text and the like keep the image URL in their own attributes) is refused rather than left half-updated.
 * Block comments are matched on the raw text, the same way WordPress's own block parser reads them.
 */
function boblift_content_insert_image_block_id(string $content, int $at, int $new_id): string|WP_Error
{
    $head = substr($content, 0, $at);
    preg_match_all('~<!--\s+(/?)wp:([a-z][a-z0-9_-]*(?:/[a-z][a-z0-9_-]*)?)(\s+\{.*?\})?\s+(/?)-->~s', $head, $tokens, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
    $stack = [];
    foreach ($tokens as $token) {
        $name = $token[2][0];
        if ($token[1][0] === '/') {
            // Close the innermost block of that name; a stray closer is ignored.
            for ($i = count($stack) - 1; $i >= 0; $i--) {
                if ($stack[$i]['name'] === $name) {
                    array_splice($stack, $i);
                    break;
                }
            }
        } elseif (($token[4][0] ?? '') !== '/') {
            $stack[] = ['name' => $name, 'json' => $token[3][0] ?? '', 'offset' => $token[3][1] ?? -1];
        }
    }
    $block = end($stack);
    if ($block === false || in_array($block['name'], ['html', 'core/html'], true)) {
        return $content;
    }
    if (!in_array($block['name'], ['image', 'core/image'], true)) {
        return new WP_Error('boblift_insert_image', sprintf('This image is inside a "%s" block, whose own attributes keep the image; only image blocks and classic HTML can be swapped.', $block['name']), ['status' => 400]);
    }
    $raw = $block['json'];
    $json = trim($raw);
    $attrs = $json === '' ? null : json_decode($json, true);
    if (!is_array($attrs) || !is_int($attrs['id'] ?? null) || $attrs['id'] === $new_id) {
        return $content;
    }
    // JSON allows whitespace around the colon and before the next comma or brace; keep it as written.
    $id_pattern = '~("id"\s*:\s*)' . $attrs['id'] . '(?=\s*[,}])~';
    if (preg_match_all($id_pattern, $raw) !== 1) {
        return $content;
    }
    $raw_new = preg_replace_callback($id_pattern, static fn(array $m): string => $m[1] . $new_id, $raw);
    return substr_replace($content, $raw_new, $block['offset'], strlen($raw));
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
