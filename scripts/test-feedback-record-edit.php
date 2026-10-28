<?php
// Checks boblift_feedback_record_apply_edits() without WordPress: php scripts/test-feedback-record-edit.php
define('ABSPATH', __DIR__);
class WP_Error
{
    public function __construct(public string $code = '', public string $message = '', public array $data = [])
    {
    }
}
function is_wp_error($thing): bool
{
    return $thing instanceof WP_Error;
}
function add_action(...$args): void
{
}
function add_filter(...$args): void
{
}
// Stands in for the REST request: the parameters the edit field arrives with, by source.
class WP_REST_Request
{
    // The update route's args, as WordPress registers them for the feedback type (a sample of the writable ones).
    public function __construct(private array $query = [], private array $body = [], private array $json = [], private array $args = ['id' => [], 'context' => [], 'title' => [], 'status' => [], 'meta' => [], 'boblift_feedback_record_edit' => []])
    {
    }
    public function get_attributes(): array
    {
        return ['args' => $this->args];
    }
    public function has_param(string $key): bool
    {
        return array_key_exists($key, $this->json + $this->body + $this->query);
    }
    public function get_param(string $key): mixed
    {
        return ($this->json + $this->body + $this->query)[$key] ?? null;
    }
    public function get_query_params(): array
    {
        return $this->query;
    }
    public function get_body_params(): array
    {
        return $this->body;
    }
    public function get_json_params(): ?array
    {
        return $this->json;
    }
}
require dirname(__DIR__) . '/inc/rest-feedback-record-edit.php';

$fail = 0;
$check = static function (string $name, bool $ok) use (&$fail): void {
    echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n";
    $fail += $ok ? 0 : 1;
};
$records = ['en' => '<p>The SQ5 is a crane.</p><table>a</table><table>b</table><p>End</p>', 'es' => '<p>La SQ5</p><table>a</table>'];

$out = boblift_feedback_record_apply_edits($records, [
    ['lang' => 'en', 'find' => 'The SQ5 is', 'replace' => 'The <strong>SQ5</strong> is'],
    ['lang' => 'en', 'after_last' => '</table>', 'insert' => '<p>New</p>'],
    ['lang' => 'es', 'after_last' => '</table>', 'insert' => '<p>Nuevo</p>'],
]);
$check('find/replace and insert after the last match', !is_wp_error($out)
    && $out['en'] === '<p>The <strong>SQ5</strong> is a crane.</p><table>a</table><table>b</table><p>New</p><p>End</p>'
    && $out['es'] === '<p>La SQ5</p><table>a</table><p>Nuevo</p>');
$check('find that occurs twice is rejected', is_wp_error(boblift_feedback_record_apply_edits($records, [['lang' => 'en', 'find' => '</table>', 'replace' => '']])));
$check('find that is missing is rejected', is_wp_error(boblift_feedback_record_apply_edits($records, [['lang' => 'en', 'find' => 'nope', 'replace' => 'x']])));
$check('unknown language is rejected', is_wp_error(boblift_feedback_record_apply_edits($records, [['lang' => 'ja', 'find' => 'SQ5', 'replace' => 'x']])));
$check('a failing edit rejects the ones before it', is_wp_error(boblift_feedback_record_apply_edits($records, [
    ['lang' => 'en', 'find' => 'End', 'replace' => 'Fin'],
    ['lang' => 'es', 'after_last' => '<ol>', 'insert' => 'x'],
])));
$check('empty or malformed edit lists are rejected', is_wp_error(boblift_feedback_record_apply_edits($records, []))
    && is_wp_error(boblift_feedback_record_apply_edits($records, null))
    && is_wp_error(boblift_feedback_record_apply_edits($records, [['lang' => 'en']])));
$check('edits see the result of the edits before them', !is_wp_error($two = boblift_feedback_record_apply_edits($records, [
    ['lang' => 'en', 'find' => 'End', 'replace' => 'Fin'],
    ['lang' => 'en', 'find' => 'Fin', 'replace' => 'Done'],
])) && str_ends_with($two['en'], '<p>Done</p>'));
$edit = ['edits' => [['lang' => 'en', 'find' => 'End', 'replace' => 'Fin']]];
$refused = static fn($result): bool => is_wp_error($result) && str_contains($result->message, 'on its own');
$check('edits with meta in the query string are refused before any save', $refused(boblift_feedback_record_edit_precheck(new stdClass(), new WP_REST_Request(['meta' => ['_feedback_record' => '{}']], [], ['boblift_feedback_record_edit' => $edit]))));
$check('edits with a title in the body are refused before any save', $refused(boblift_feedback_record_edit_precheck(new stdClass(), new WP_REST_Request([], ['title' => 'x'], ['boblift_feedback_record_edit' => $edit]))));
$check('edits with meta in the JSON are refused before any save', $refused(boblift_feedback_record_edit_precheck(new stdClass(), new WP_REST_Request([], [], ['boblift_feedback_record_edit' => $edit, 'meta' => ['_feedback_record' => '{}']]))));
// A database with no stored record: a request that gets past the field check stops at the record lookup instead.
$wpdb = new class {
    public string $postmeta = 'postmeta';
    public function prepare(string $query, ...$args): string
    {
        return $query;
    }
    public function get_row(string $query): ?object
    {
        return null;
    }
};
$check('a parameter the route does not accept (a connector\'s post_type) is not refused', !$refused(boblift_feedback_record_edit_precheck(new stdClass(), new WP_REST_Request(['post_type' => 'feedback', 'user_confirmed' => true], [], ['boblift_feedback_record_edit' => $edit]))));
$check('the refusal names the other fields', ($named = boblift_feedback_record_edit_precheck(new stdClass(), new WP_REST_Request([], ['title' => 'x', 'status' => 'draft'], ['boblift_feedback_record_edit' => $edit]))) instanceof WP_Error && str_contains($named->message, 'title, status'));
$plain = new stdClass();
$check('requests without the edit field pass untouched', boblift_feedback_record_edit_precheck($plain, new WP_REST_Request([], ['title' => 'x'])) === $plain);
exit($fail ? 1 : 0);
