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
 * occurrence of "with" are not counted. The document is stored re-encoded the way the importers write it (unescaped
 * slashes and Unicode, pretty-printed when it was), with a compare-and-set on the row it read.
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
    $trimmed = trim($stored);
    // Decoded to objects so an empty {} stays an object when the document is written back.
    $document = $trimmed === '' ? null : json_decode($trimmed);
    if (!is_array($document) && !is_object($document)) {
        return $error('This item has no JSON-LD of its own to correct.');
    }
    $found = 0;
    $already = false;
    $count = static function (mixed $node) use (&$count, &$found, &$already, $from, $to): void {
        if (is_string($node)) {
            $found += count(boblift_schema_replace_offsets($node, $from, $to));
            $already = $already || str_contains($node, $to);
            return;
        }
        if (is_array($node) || is_object($node)) {
            foreach ($node as $child) {
                $count($child);
            }
        }
    };
    $count($document);
    if ($found === 0) {
        return $already ? $stored : $error(sprintf('"%s" does not occur in this item\'s JSON-LD.', $from));
    }
    if ($found > 1) {
        return $error(sprintf('"%s" occurs %d times in this item\'s JSON-LD; it must occur exactly once. Send a longer phrase.', $from, $found));
    }
    $replace = static function (mixed $node) use (&$replace, $from, $to): mixed {
        if (is_string($node)) {
            // Right to left, so earlier offsets stay valid.
            foreach (array_reverse(boblift_schema_replace_offsets($node, $from, $to)) as $offset) {
                $node = substr_replace($node, $to, $offset, strlen($from));
            }
            return $node;
        }
        if (is_array($node)) {
            return array_map($replace, $node);
        }
        if (is_object($node)) {
            foreach (get_object_vars($node) as $key => $child) {
                $node->$key = $replace($child);
            }
        }
        return $node;
    };
    $result = $replace($document);
    // A correction is accepted only when sending it again would change nothing: if the result still holds a match
    // (one formed across the edge of the new text, as "11 m" -> "1 m" does in "111 m"), it is refused instead.
    $found = 0;
    $count($result);
    if ($found > 0) {
        return $error(sprintf('Replacing "%s" with "%s" here leaves a new "%s", so the correction could not be repeated safely. Send a longer phrase.', $from, $to, $from));
    }
    $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | (str_contains($trimmed, "\n") ? JSON_PRETTY_PRINT : 0);
    $encoded = json_encode($result, $flags);
    return is_string($encoded) ? $encoded : $error('The corrected JSON-LD could not be encoded.');
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
