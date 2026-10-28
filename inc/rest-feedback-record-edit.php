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
 * that does not apply rejects the request. The field must be sent on its own, so nothing else in the request is saved
 * either way. The result goes through the record's own sanitizing and is saved as a compare-and-set, so concurrent edits
 * to one entry do not overwrite each other.
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

/** The stored record of an entry, as decoded languages plus the raw meta row it came from (null when there is none). */
function boblift_feedback_record_edit_stored(int $post_id): ?array
{
    global $wpdb;
    $row = $wpdb->get_row($wpdb->prepare("SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_feedback_record' ORDER BY meta_id LIMIT 1", $post_id));
    $records = $row ? json_decode((string) $row->meta_value, true) : null;
    return is_array($records) && $records !== [] ? ['meta_id' => (int) $row->meta_id, 'raw' => (string) $row->meta_value, 'records' => $records] : null;
}

/**
 * Checks the edits before WordPress saves anything in the request. A request carrying the edits carries nothing else,
 * so a refused edit never leaves part of the request saved.
 */
function boblift_feedback_record_edit_precheck($prepared_post, WP_REST_Request $request)
{
    if (!$request->has_param('boblift_feedback_record_edit') || is_wp_error($prepared_post)) {
        return $prepared_post;
    }
    // Only the edits may be sent: WordPress saves the post's own fields before this field's callback runs, so a title or
    // meta sent alongside could be saved while the edits are then refused.
    // The query string counts too: WordPress takes update fields from it as well. Request controls that write nothing pass.
    $sent = array_keys(array_merge((array) $request->get_query_params(), (array) $request->get_body_params(), (array) $request->get_json_params()));
    if (array_diff($sent, ['boblift_feedback_record_edit', 'id', 'context', '_fields', '_envelope', '_method', '_locale', '_embed', '_wpnonce', 'rest_route']) !== []) {
        return new WP_Error('boblift_record_edit_invalid', 'Send boblift_feedback_record_edit on its own, without other fields; save those in a separate request.', ['status' => 400]);
    }
    $stored = boblift_feedback_record_edit_stored((int) $request->get_param('id'));
    if ($stored === null) {
        return new WP_Error('boblift_record_edit_invalid', 'This entry has no delivery record to edit; write meta._feedback_record instead.', ['status' => 400]);
    }
    $value = $request->get_param('boblift_feedback_record_edit');
    $edited = boblift_feedback_record_apply_edits($stored['records'], is_array($value) ? ($value['edits'] ?? null) : null);
    return is_wp_error($edited) ? $edited : $prepared_post;
}
add_filter('rest_pre_insert_feedback', 'boblift_feedback_record_edit_precheck', 10, 2);

/**
 * Applies the edits and saves the record as a compare-and-set on its meta row, retried when another save changed the
 * row in between, so two requests editing different languages at once both keep their changes.
 */
function boblift_feedback_record_edit_save(int $post_id, mixed $edits): true|WP_Error
{
    global $wpdb;
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $stored = boblift_feedback_record_edit_stored($post_id);
        if ($stored === null) {
            return new WP_Error('boblift_record_edit_invalid', 'This entry has no delivery record to edit; write meta._feedback_record instead.', ['status' => 400]);
        }
        $edited = boblift_feedback_record_apply_edits($stored['records'], $edits);
        if (is_wp_error($edited)) {
            return $edited;
        }
        if ($edited === $stored['records']) {
            return true;
        }
        // The record's own sanitizing (KSES per language), as a meta write through REST would apply it.
        $value = sanitize_meta('_feedback_record', (string) wp_json_encode($edited, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 'post', 'feedback');
        // Sanitizing can strip the whole change (a disallowed attribute, say); then there is nothing to write.
        if ($value === $stored['raw']) {
            return true;
        }
        $done = $wpdb->query($wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_id = %d AND BINARY meta_value = BINARY %s", $value, $stored['meta_id'], $stored['raw']));
        if ($done) {
            wp_cache_delete($post_id, 'post_meta');
            clean_post_cache($post_id);
            return true;
        }
    }
    return new WP_Error('boblift_record_edit_busy', 'The record kept changing while saving; try again.', ['status' => 409]);
}

function boblift_feedback_record_edit_register_field(): void
{
    register_rest_field('feedback', 'boblift_feedback_record_edit', [
        'get_callback' => static fn(): ?array => null,
        'update_callback' => static function ($value, WP_Post $post) {
            if (!current_user_can('edit_post', $post->ID)) {
                return new WP_Error('boblift_record_edit_forbidden', 'Editing a delivery record needs edit_post on the entry.', ['status' => 403]);
            }
            return boblift_feedback_record_edit_save($post->ID, is_array($value) ? ($value['edits'] ?? null) : null);
        },
        'schema' => [
            'description' => 'Edits to this entry\'s delivery record. Write {"edits": [{"lang", "find", "replace"} | {"lang", "after_last", "insert"}]}.',
            'type' => ['object', 'null'],
            'context' => ['edit'],
        ],
    ]);
}
add_action('rest_api_init', 'boblift_feedback_record_edit_register_field');
