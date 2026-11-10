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
 * item is already done and nothing is saved, so the request can be sent again safely. The document is stored re-encoded
 * the way the importers write it (unescaped slashes and Unicode, pretty-printed when it was).
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
            $stored = (string) get_post_meta($post->ID, '_article_schema_jsonld', true);
            $updated = boblift_schema_replace_apply($stored, $value);
            if (is_wp_error($updated)) {
                return $updated;
            }
            if ($updated !== $stored) {
                update_post_meta($post->ID, '_article_schema_jsonld', wp_slash($updated));
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
            // Occurrences inside an occurrence of "with" do not count, so "5 m" -> "15 m" is not undone by itself.
            $found += substr_count(str_replace($to, "\x00", $node), $from);
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
            return implode($to, array_map(static fn(string $part): string => str_replace($from, $to, $part), explode($to, $node)));
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
    $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | (str_contains($trimmed, "\n") ? JSON_PRETTY_PRINT : 0);
    $encoded = json_encode($replace($document), $flags);
    return is_string($encoded) ? $encoded : $error('The corrected JSON-LD could not be encoded.');
}
