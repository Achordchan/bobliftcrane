<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST: the boblift_feedback_record_edit field on wp/v2/feedback, small edits to a written delivery record
 * (_feedback_record, inc/feedback-record.php) without sending every language of it back through the connector.
 *
 * Write {"edits": [{"lang": "en", "find": "…", "replace": "…"}, {"lang": "es", "after_last": "</table>", "insert": "<p>…</p>"}]}.
 * "find" must occur exactly once in that language's record and "after_last" at least once; text is matched against the
 * record as stored, which is what wp/v2/feedback returns in meta._feedback_record. The edits run in order, and one edit
 * that does not apply rejects them all, so nothing is saved. The result goes through the record's own sanitizing.
 */
function boblift_feedback_record_apply_edits(array $records, mixed $edits): array|WP_Error
{
    $invalid = static fn(string $why): WP_Error => new WP_Error('boblift_record_edit_invalid', $why, ['status' => 400]);
    if (!is_array($edits) || $edits === [] || !array_is_list($edits) || count($edits) > 100) {
        return $invalid('Send {"edits": [...]} with 1 to 100 edits.');
    }
    foreach ($edits as $index => $edit) {
        $language = is_array($edit) && is_string($edit['lang'] ?? null) ? $edit['lang'] : '';
        if (!is_string($records[$language] ?? null)) {
            return $invalid(sprintf('Edit %d: this entry has no record in language "%s".', $index, $language));
        }
        $html = $records[$language];
        if (is_string($edit['find'] ?? null) && $edit['find'] !== '' && is_string($edit['replace'] ?? null)) {
            $count = substr_count($html, $edit['find']);
            if ($count !== 1) {
                return $invalid(sprintf('Edit %d (%s): "find" occurs %d times; it must occur exactly once.', $index, $language, $count));
            }
            $at = strpos($html, $edit['find']);
            $records[$language] = substr($html, 0, $at) . $edit['replace'] . substr($html, $at + strlen($edit['find']));
        } elseif (is_string($edit['after_last'] ?? null) && $edit['after_last'] !== '' && is_string($edit['insert'] ?? null)) {
            $at = strrpos($html, $edit['after_last']);
            if ($at === false) {
                return $invalid(sprintf('Edit %d (%s): "after_last" does not occur in the record.', $index, $language));
            }
            $at += strlen($edit['after_last']);
            $records[$language] = substr($html, 0, $at) . $edit['insert'] . substr($html, $at);
        } else {
            return $invalid(sprintf('Edit %d: give "find" and "replace", or "after_last" and "insert".', $index));
        }
    }
    return $records;
}

function boblift_feedback_record_edit_register_field(): void
{
    register_rest_field('feedback', 'boblift_feedback_record_edit', [
        'get_callback' => static fn(): ?array => null,
        'update_callback' => static function ($value, WP_Post $post) {
            if (!current_user_can('edit_post', $post->ID)) {
                return new WP_Error('boblift_record_edit_forbidden', 'Editing a delivery record needs edit_post on the entry.', ['status' => 403]);
            }
            $records = json_decode((string) get_post_meta($post->ID, '_feedback_record', true), true);
            if (!is_array($records) || $records === []) {
                return new WP_Error('boblift_record_edit_invalid', 'This entry has no delivery record to edit; write meta._feedback_record instead.', ['status' => 400]);
            }
            $edited = boblift_feedback_record_apply_edits($records, is_array($value) ? ($value['edits'] ?? null) : null);
            if (is_wp_error($edited)) {
                return $edited;
            }
            if ($edited !== $records) {
                // Meta writes unslash their input, and the JSON escapes the quotes in the HTML.
                update_post_meta($post->ID, '_feedback_record', wp_slash((string) wp_json_encode($edited, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)));
            }
            return true;
        },
        'schema' => [
            'description' => 'Edits to this entry\'s delivery record. Write {"edits": [{"lang", "find", "replace"} | {"lang", "after_last", "insert"}]}.',
            'type' => ['object', 'null'],
            'context' => ['edit'],
        ],
    ]);
}
add_action('rest_api_init', 'boblift_feedback_record_edit_register_field');
