<?php
// Checks the "relabel" planning of the boblift_specs field without WordPress: php scripts/test-product-specs-relabel.php
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
function sanitize_text_field(string $value): string
{
    return trim(preg_replace('/\s+/', ' ', strip_tags($value)));
}
require __DIR__ . '/../inc/rest-product-specs.php';
// The alignment check of inc/product-guide.php, copied without the rest of that file.
function boblift_spec_rows_aligned(array $local, array $english): bool
{
    return $local !== [] && count($local) === count($english);
}

$failures = 0;
function check(string $name, bool $ok): void
{
    global $failures;
    echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n";
    $failures += $ok ? 0 : 1;
}

$tables = [
    'en' => [['label' => 'Max Lifting Capacity', 'value' => '800 kg'], ['label' => 'Winch', 'value' => 'Included']],
    'ar' => [['label' => 'أقصى قدرة رفع', 'value' => '800 kg'], ['label' => 'رافعة', 'value' => 'مضمّن']],
    'zh' => [['label' => '最大起重量', 'value' => '800 kg'], ['label' => '卷扬', 'value' => '标配']],
];

$rows = boblift_product_specs_plan_rows($tables, ['relabel' => [['label' => 'Winch', 'to' => ['ar' => 'الونش']]]]);
check('renames the ar row only', !is_wp_error($rows) && array_keys($rows) === ['ar'] && $rows['ar'][1] === ['label' => 'الونش', 'value' => 'مضمّن'] && $rows['ar'][0] === $tables['ar'][0]);

$done = $tables;
$done['ar'][1]['label'] = 'الونش';
$rows = boblift_product_specs_plan_rows($done, ['relabel' => [['label' => 'Winch', 'to' => ['ar' => 'الونش']]]]);
check('sending it again changes nothing', $rows === []);

$rows = boblift_product_specs_plan_rows($tables, ['relabel' => [['label' => 'Winch', 'to' => ['en' => 'Hoist']]]]);
check('refuses an English label', is_wp_error($rows) && $rows->code === 'boblift_specs_relabel');

$rows = boblift_product_specs_plan_rows($tables, ['relabel' => [['label' => 'Winch', 'to' => ['fr' => 'Treuil']]]]);
check('refuses a language the product lacks', is_wp_error($rows) && $rows->code === 'boblift_specs_language');

$rows = boblift_product_specs_plan_rows($tables, ['relabel' => [['label' => 'Boom', 'to' => ['ar' => 'الذراع']]]]);
check('refuses an English label not in the table', is_wp_error($rows) && $rows->code === 'boblift_specs_label');

$rows = boblift_product_specs_plan_rows($tables, ['relabel' => [['label' => 'Winch', 'to' => ['ar' => '  ']]]]);
check('refuses an empty label', is_wp_error($rows) && $rows->code === 'boblift_specs_relabel');

$rows = boblift_product_specs_plan_rows($tables, ['relabel' => [['label' => 'Winch', 'to' => ['ar' => 'أقصى قدرة رفع']]]]);
check('refuses a label another row has', is_wp_error($rows) && $rows->code === 'boblift_specs_relabel');

$rows = boblift_product_specs_plan_rows($tables, ['relabel' => [['label' => 'Winch', 'to' => ['الونش']]]]);
check('refuses a "to" list instead of a map', is_wp_error($rows) && $rows->code === 'boblift_specs_relabel');

$rows = boblift_product_specs_plan_rows($tables, ['set' => [['label' => 'Winch', 'value' => ['en' => 'Included', 'ar' => 'مضمّن', 'zh' => '标配']]], 'relabel' => [['label' => 'Winch', 'to' => ['ar' => 'الونش', 'zh' => '卷扬']]]]);
check('works with "set" in the same request', !is_wp_error($rows) && array_keys($rows) === ['ar'] && $rows['ar'][1]['label'] === 'الونش');

exit($failures === 0 ? 0 : 1);
