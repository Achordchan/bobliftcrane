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
exit($fail ? 1 : 0);
