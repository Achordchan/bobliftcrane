<?php
// Checks boblift_schema_replace_apply() without WordPress: php scripts/test-schema-replace.php
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
require dirname(__DIR__) . '/inc/rest-schema-replace.php';

$faq = '{"@context":"https://schema.org","@type":"FAQPage","url":"{{home}}/a.html","mainEntity":[{"@type":"Question","name":"How far?","acceptedAnswer":{"@type":"Answer","text":"From 5.4 m to 15.2 m on the 7 ton model."}}],"empty":{}}';
$pretty = "{\n    \"@type\": \"Article\",\n    \"headline\": \"Reach 15.2 m\"\n}";
$cases = [
    'replace in a nested string' => [$faq, ['replace' => '15.2 m', 'with' => '11 m'], '{"@context":"https://schema.org","@type":"FAQPage","url":"{{home}}/a.html","mainEntity":[{"@type":"Question","name":"How far?","acceptedAnswer":{"@type":"Answer","text":"From 5.4 m to 11 m on the 7 ton model."}}],"empty":{}}'],
    'already done' => ['{"text":"to 11 m"}', ['replace' => '15.2 m', 'with' => '11 m'], '{"text":"to 11 m"}'],
    'missing' => ['{"text":"to 9 m"}', ['replace' => '15.2 m', 'with' => '11 m'], 'boblift_schema_replace'],
    'twice' => ['{"a":"15.2 m","b":"15.2 m"}', ['replace' => '15.2 m', 'with' => '11 m'], 'boblift_schema_replace'],
    'keys untouched' => ['{"15.2 m":"x","t":"15.2 m"}', ['replace' => '15.2 m', 'with' => '11 m'], '{"15.2 m":"x","t":"11 m"}'],
    'inside with not counted' => ['{"t":"15 m"}', ['replace' => '5 m', 'with' => '15 m'], '{"t":"15 m"}'],
    'unicode kept' => ['{"t":"米 15.2 m"}', ['replace' => '15.2 m', 'with' => '11 m'], '{"t":"米 11 m"}'],
    'pretty kept' => [$pretty, ['replace' => '15.2 m', 'with' => '11 m'], "{\n    \"@type\": \"Article\",\n    \"headline\": \"Reach 11 m\"\n}"],
    'no json-ld' => ['', ['replace' => '15.2 m', 'with' => '11 m'], 'boblift_schema_replace'],
    'quote in text' => [$faq, ['replace' => '15.2 m"', 'with' => '11 m'], 'boblift_schema_replace'],
    'backslash in text' => [$faq, ['replace' => '15.2 m', 'with' => '11\\u0020m'], 'boblift_schema_replace'],
    'list instead of object' => [$faq, [['replace' => '15.2 m', 'with' => '11 m']], 'boblift_schema_replace'],
    'extra key' => [$faq, ['replace' => '15.2 m', 'with' => '11 m', 'in' => 'x'], 'boblift_schema_replace'],
    'same text' => [$faq, ['replace' => '15.2 m', 'with' => '15.2 m'], 'boblift_schema_replace'],
];
$failed = 0;
foreach ($cases as $name => [$stored, $value, $want]) {
    $got = boblift_schema_replace_apply($stored, $value);
    $got = is_wp_error($got) ? $got->code : $got;
    if ($got !== $want) {
        $failed++;
        echo "FAIL {$name}\n  want: " . json_encode($want) . "\n  got:  " . json_encode($got) . "\n";
    }
}
echo $failed ? "{$failed} failed\n" : count($cases) . " passed\n";
exit($failed ? 1 : 0);
