<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST: the write-only boblift_schema_replace field on wp/v2 posts, cases and pages corrects a plain-text phrase in the
 * item's own JSON-LD (_article_schema_jsonld), the way boblift_content_insert "replace" does for the body. Imported
 * articles keep their FAQ and Article data there, so a figure corrected in the text must be corrected there too or the
 * page's structured data keeps the old one.
 *
 * Write: {"replace": "15.2 m on the 7 ton model", "with": "11 m on the 7 ton model"}. Both are plain text (no "<", ">",
 * quotes or backslashes). The JSON-LD is decoded and the phrase is looked for in its string values only, never in keys
 * or in the JSON syntax; it must occur exactly once across all of them. When it no longer occurs and "with" does, the
 * item is already done and nothing is saved, so the request can be sent again safely; matches that lie inside an
 * occurrence of "with" are not counted. Only the string literal that holds the match is rewritten; the rest of the
 * stored text (numbers, keys, formatting) is kept byte for byte. The save is a compare-and-set on the row it read.
 */
function boblift_schema_replace_types(): array
{
    return ['post', 'case', 'page'];
}

function boblift_register_rest_schema_replace_field(): void
{
    register_rest_field(boblift_schema_replace_types(), 'boblift_schema_replace', [
        'get_callback' => null,
        'update_callback' => static function ($value, WP_Post $post) {
            if (!current_user_can('edit_post', $post->ID)) {
                return new WP_Error('rest_forbidden', 'You cannot edit this item.', ['status' => 403]);
            }
            $saved = boblift_schema_replace_save($post->ID, $value);
            if (is_wp_error($saved)) {
                return $saved;
            }
            return true;
        },
        'schema' => [
            'description' => 'Write only: {"replace": text, "with": text} corrects a plain-text phrase in the item\'s own JSON-LD (_article_schema_jsonld) string values; it must occur exactly once.',
            'type' => 'object',
            'context' => ['edit'],
        ],
    ]);
}
add_action('rest_api_init', 'boblift_register_rest_schema_replace_field');

/**
 * Applies the correction to the stored JSON-LD and saves it with a byte-for-byte compare-and-set on the row it read, so
 * two corrections sent at the same time cannot overwrite each other: the one that loses re-reads the fresh value and
 * applies itself again (up to five times), and its own checks run against that value. Returns the saved document.
 */
function boblift_schema_replace_save(int $post_id, mixed $value): string|WP_Error
{
    global $wpdb;
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s ORDER BY meta_id ASC LIMIT 1",
            $post_id,
            '_article_schema_jsonld'
        ));
        $stored = $row ? (string) $row->meta_value : '';
        $updated = boblift_schema_replace_apply($stored, $value);
        if (is_wp_error($updated) || $updated === $stored) {
            return $updated;
        }
        $changed = $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_id = %d AND BINARY meta_value = BINARY %s",
            $updated,
            (int) $row->meta_id,
            $stored
        ));
        if ($changed === 1) {
            wp_cache_delete($post_id, 'post_meta');
            return $updated;
        }
    }
    return new WP_Error('boblift_schema_replace_busy', 'The JSON-LD kept changing while the correction was saved; try again.', ['status' => 409]);
}

/** The stored JSON-LD with the phrase replaced, unchanged when already done, or the reason it cannot be applied. */
function boblift_schema_replace_apply(string $stored, mixed $value): string|WP_Error
{
    $error = static fn(string $message): WP_Error => new WP_Error('boblift_schema_replace', $message, ['status' => 400]);
    if (!is_array($value) || array_is_list($value) || array_diff(array_keys($value), ['replace', 'with']) !== []) {
        return $error('Send one object: {"replace": text, "with": text}.');
    }
    $from = $value['replace'] ?? null;
    $to = $value['with'] ?? null;
    if (!is_string($from) || !is_string($to) || $from === '' || $to === '' || $from === $to || preg_match('/[<>"\'\\\\\x00]/', $from . $to)) {
        return $error('"replace" and "with" are different, non-empty plain text (no "<", ">", quotes or backslashes).');
    }
    if (trim($stored) === '' || json_decode($stored) === null) {
        return $error('This item has no JSON-LD of its own to correct.');
    }
    // Only the string values that hold a match are rewritten in place; keys, numbers, formatting and every other string
    // stay byte for byte as stored, so nothing outside the requested correction can change on the way through.
    $values = boblift_schema_replace_string_values($stored);
    $found = 0;
    $already = false;
    foreach ($values as [, , $text]) {
        $found += count(boblift_schema_replace_offsets($text, $from, $to));
        $already = $already || str_contains($text, $to);
    }
    if ($found === 0) {
        return $already ? $stored : $error(sprintf('"%s" does not occur in this item\'s JSON-LD.', $from));
    }
    if ($found > 1) {
        return $error(sprintf('"%s" occurs %d times in this item\'s JSON-LD; it must occur exactly once. Send a longer phrase.', $from, $found));
    }
    $result = $stored;
    // Right to left, so the byte offsets of earlier tokens stay valid.
    foreach (array_reverse($values) as [$offset, $length, $text]) {
        $hits = boblift_schema_replace_offsets($text, $from, $to);
        if ($hits === []) {
            continue;
        }
        foreach (array_reverse($hits) as $at) {
            $text = substr_replace($text, $to, $at, strlen($from));
        }
        $token = json_encode($text, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($token)) {
            return $error('The corrected JSON-LD could not be encoded.');
        }
        $result = substr_replace($result, $token, $offset, $length);
    }
    // A correction is accepted only when sending it again would change nothing: if the result still holds a match
    // (one formed across the edge of the new text, as "11 m" -> "1 m" does in "111 m"), it is refused instead.
    foreach (boblift_schema_replace_string_values($result) as [, , $text]) {
        if (boblift_schema_replace_offsets($text, $from, $to) !== []) {
            return $error(sprintf('Replacing "%s" with "%s" here leaves a new "%s", so the correction could not be repeated safely. Send a longer phrase.', $from, $to, $from));
        }
    }
    return $result;
}

/**
 * The string values of a JSON document as [byte offset, byte length, decoded text], keys left out. String literals are
 * matched on the raw text (a literal cannot hold an unescaped quote, so the scan cannot lose its place), and a literal
 * followed by ":" is a key.
 */
function boblift_schema_replace_string_values(string $json): array
{
    preg_match_all('/"(?:[^"\\\\]|\\\\.)*"(?=(\s*:)?)/s', $json, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
    $values = [];
    foreach ($matches as $match) {
        if (($match[1][0] ?? '') !== '') {
            continue;
        }
        $text = json_decode($match[0][0]);
        if (is_string($text)) {
            $values[] = [$match[0][1], strlen($match[0][0]), $text];
        }
    }
    return $values;
}

/**
 * Byte offsets of $from in $text that do not lie inside an occurrence of $to, so a correction such as "5 m" -> "15 m"
 * does not count its own result as a new match. When $to is shorter than or part of $from (e.g. "15.2 m" -> "5.2 m"), no
 * occurrence of $to can hold a whole $from, so every match counts.
 */
function boblift_schema_replace_offsets(string $text, string $from, string $to): array
{
    $spans = [];
    for ($at = strpos($text, $to); $at !== false; $at = strpos($text, $to, $at + 1)) {
        $spans[] = [$at, $at + strlen($to)];
    }
    $offsets = [];
    // Overlapping matches count too ("11" occurs twice in "111"), so an ambiguous phrase is refused, not half-applied.
    for ($at = strpos($text, $from); $at !== false; $at = strpos($text, $from, $at + 1)) {
        $end = $at + strlen($from);
        $inside = false;
        foreach ($spans as [$start, $stop]) {
            if ($start <= $at && $end <= $stop) {
                $inside = true;
                break;
            }
        }
        if (!$inside) {
            $offsets[] = $at;
        }
    }
    return $offsets;
}

/**
 * Posts also expose _article_schema_jsonld itself over REST (cases already do, in case-rest.php), so an item's stored
 * JSON-LD can be replaced or cleared through the "meta" object of an update. Clearing it ("") lets the theme build the
 * Article, BreadcrumbList and FAQPage from the page itself (boblift_news_article_schema_graph), in the page's language
 * and with its own address, which is what a translation whose stored copy was left in English needs. A value that is not
 * empty and not a JSON object or list is refused before anything is saved.
 */
function boblift_register_post_schema_meta(): void
{
    register_post_meta('post', '_article_schema_jsonld', [
        'type' => 'string',
        'single' => true,
        'default' => '',
        'show_in_rest' => true,
        'sanitize_callback' => 'boblift_sanitize_post_schema_meta',
        'auth_callback' => static fn($allowed, $meta_key, $post_id): bool => current_user_can('edit_post', (int) $post_id),
    ]);
}
add_action('init', 'boblift_register_post_schema_meta', 20);

/**
 * A JSON object or list is stored exactly as given (trimmed), never decoded and re-encoded, so numbers beyond PHP's
 * integer range, escapes and formatting survive; anything else is stored as "". It also runs on update_post_meta().
 */
function boblift_sanitize_post_schema_meta(mixed $value): string
{
    $value = is_string($value) ? trim($value) : '';
    return $value !== '' && is_array(json_decode($value, true, 512, JSON_BIGINT_AS_STRING)) ? $value : '';
}

// Core REST returns registered meta whatever the post password, so a password-protected post's meta (its stored JSON-LD
// can carry FAQ and article text) is left out for anyone who cannot edit it, as for cases, products and pages.
add_filter('rest_prepare_post', 'boblift_hide_protected_case_rest_meta', 10, 2);

/** The reason a requested _article_schema_jsonld value cannot be stored, or null when it can ("" clears it). */
function boblift_post_schema_meta_error(mixed $meta): ?string
{
    if (!is_array($meta) || !array_key_exists('_article_schema_jsonld', $meta)) {
        return null;
    }
    $value = $meta['_article_schema_jsonld'];
    if ($value === null || (is_string($value) && trim($value) === '')) {
        return null;
    }
    return is_string($value) && is_array(json_decode($value, true)) ? null : '_article_schema_jsonld: expected a JSON object or list, or "" to clear it.';
}

function boblift_validate_post_schema_meta(stdClass|WP_Error $prepared, WP_REST_Request $request): stdClass|WP_Error
{
    if (is_wp_error($prepared)) {
        return $prepared;
    }
    $message = boblift_post_schema_meta_error($request->get_param('meta'));
    return $message === null ? $prepared : new WP_Error('rest_invalid_param', $message, ['status' => 400, 'meta_key' => '_article_schema_jsonld']);
}
add_filter('rest_pre_insert_post', 'boblift_validate_post_schema_meta', 10, 2);
