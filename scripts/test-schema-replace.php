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
$GLOBALS['filters'] = [];
function add_filter(string $hook, mixed $callback, ...$args): void
{
    $GLOBALS['filters'][$hook][] = $callback;
}
// Just enough of WP_REST_Request for the JSON-string decoding filter.
class WP_REST_Request
{
    public function __construct(private string $method, private string $route, private array $params)
    {
    }
    public function get_method(): string
    {
        return $this->method;
    }
    public function get_route(): string
    {
        return $this->route;
    }
    public function get_param(string $key): mixed
    {
        return $this->params[$key] ?? null;
    }
    public function set_param(string $key, mixed $value): void
    {
        $this->params[$key] = $value;
    }
}
require dirname(__DIR__) . '/inc/rest-schema-replace.php';
require dirname(__DIR__) . '/inc/rest-json-string-fields.php';

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
    'with inside replace' => ['{"text":"15.2 m"}', ['replace' => '15.2 m', 'with' => '5.2 m'], '{"text":"5.2 m"}'],
    'with inside replace, done' => ['{"text":"5.2 m"}', ['replace' => '15.2 m', 'with' => '5.2 m'], '{"text":"5.2 m"}'],
    'replace inside with, mixed' => ['{"text":"15 m and 5 m"}', ['replace' => '5 m', 'with' => '15 m'], '{"text":"15 m and 15 m"}'],
    'overlapping matches' => ['{"text":"111 mm"}', ['replace' => '11', 'with' => '1'], 'boblift_schema_replace'],
    'replacement recreates the phrase' => ['{"text":"111 m"}', ['replace' => '11 m', 'with' => '1 m'], 'boblift_schema_replace'],
    'large number kept' => ['{"id":123456789012345678901234567890,"price":1.10,"t":"15.2 m"}', ['replace' => '15.2 m', 'with' => '11 m'], '{"id":123456789012345678901234567890,"price":1.10,"t":"11 m"}'],
    'escapes elsewhere kept' => ['{"url":"https:\\/\\/a.b\\/c","n":"\\u00e9","t":"15.2 m"}', ['replace' => '15.2 m', 'with' => '11 m'], '{"url":"https:\\/\\/a.b\\/c","n":"\\u00e9","t":"11 m"}'],
    'key with escaped quote' => ['{"a\\"15.2 m":"x","t":"15.2 m"}', ['replace' => '15.2 m', 'with' => '11 m'], '{"a\\"15.2 m":"x","t":"11 m"}'],
    'same text' => [$faq, ['replace' => '15.2 m', 'with' => '15.2 m'], 'boblift_schema_replace'],
];
$failed = 0;
// Which stored values a post's REST "meta" may set: "" clears, JSON objects and lists are kept, anything else refused.
foreach ([
    'clear' => [['_article_schema_jsonld' => ''], null],
    'object' => [['_article_schema_jsonld' => '{"@type":"Article"}'], null],
    'list' => [['_article_schema_jsonld' => '[{"@type":"Article"}]'], null],
    'not json' => [['_article_schema_jsonld' => 'Article'], 'error'],
    'number' => [['_article_schema_jsonld' => '5'], 'error'],
    'other meta only' => [['_boblift_pinned_until' => '2026-12-01'], null],
] as $name => [$meta, $want]) {
    $got = boblift_post_schema_meta_error($meta) === null ? null : 'error';
    if ($got !== $want) {
        $failed++;
        echo "FAIL meta {$name}\n";
    }
}
// The registered sanitizer keeps a valid value byte for byte (no lossy re-encode) and stores anything else as "".
foreach ([
    'large number' => ['{"id":123456789012345678901234567890,"price":1.10}', '{"id":123456789012345678901234567890,"price":1.10}'],
    'escapes and spacing' => [" {\n  \"url\": \"https:\\/\\/a.b\\/c\" }\n", "{\n  \"url\": \"https:\\/\\/a.b\\/c\" }"],
    'list' => ['[1,2]', '[1,2]'],
    'empty' => ['', ''],
    'not json' => ['Article', ''],
    'scalar json' => ['"Article"', ''],
    'not a string' => [['@type' => 'Article'], ''],
] as $name => [$value, $want]) {
    if (boblift_sanitize_post_schema_meta($value) !== $want) {
        $failed++;
        echo "FAIL sanitize {$name}\n  got: " . json_encode(boblift_sanitize_post_schema_meta($value)) . "\n";
    }
}
// A password-protected post's REST meta is hidden from readers who cannot edit it.
if (($GLOBALS['filters']['rest_pre_insert_post'] ?? []) === [] || !in_array('boblift_hide_protected_case_rest_meta', $GLOBALS['filters']['rest_prepare_post'] ?? [], true)) {
    $failed++;
    echo "FAIL protected post meta filter not added\n";
}
foreach ($cases as $name => [$stored, $value, $want]) {
    $got = boblift_schema_replace_apply($stored, $value);
    $got = is_wp_error($got) ? $got->code : $got;
    if ($got !== $want) {
        $failed++;
        echo "FAIL {$name}\n  want: " . json_encode($want) . "\n  got:  " . json_encode($got) . "\n";
    }
}
// Compare-and-set: another save lands between this request's read and its write; the correction re-applies on top.
function wp_cache_delete(...$args): bool
{
    return true;
}
$GLOBALS['wpdb'] = new class {
    public string $postmeta = 'wp_postmeta';
    public string $value = '{"a":"15.2 m","b":"old"}';
    public int $writes = 0;
    public function prepare(string $sql, ...$args): array
    {
        return [$sql, $args];
    }
    public function get_row(array $q): object
    {
        return (object) ['meta_id' => 9, 'meta_value' => $this->value];
    }
    public function query(array $q): int
    {
        [, [$new, , $expected]] = $q;
        if (++$this->writes === 1) {
            $this->value = '{"a":"15.2 m","b":"new"}'; // the concurrent save wins the first race
        }
        if ($this->value !== $expected) {
            return 0;
        }
        $this->value = $new;
        return 1;
    }
};
$saved = boblift_schema_replace_save(5160, ['replace' => '15.2 m', 'with' => '11 m']);
if ($saved !== '{"a":"11 m","b":"new"}' || $GLOBALS['wpdb']->value !== $saved) {
    $failed++;
    echo "FAIL compare-and-set\n  got: " . json_encode(is_wp_error($saved) ? $saved->code : $saved) . "\n";
}
// The WordPress.com connector sends the field as a JSON string; it must reach the callback as an object.
$request = new WP_REST_Request('POST', '/wp/v2/posts/5160', ['boblift_schema_replace' => '{"replace":"15.2 m","with":"11 m"}']);
foreach ($GLOBALS['filters']['rest_pre_dispatch'] ?? [] as $filter) {
    $filter(null, null, $request);
}
$decoded = $request->get_param('boblift_schema_replace');
if ($decoded !== ['replace' => '15.2 m', 'with' => '11 m'] || boblift_schema_replace_apply('{"t":"15.2 m"}', $decoded) !== '{"t":"11 m"}') {
    $failed++;
    echo "FAIL connector JSON string\n  got: " . json_encode($decoded) . "\n";
}
// The old-address redirect rules field (inc/legacy-redirects.php) arrives the same way on a page update.
$request = new WP_REST_Request('POST', '/wp/v2/pages/737', ['boblift_legacy_redirects' => '{"rules":[{"from":"/news-show-273.html","to":"/truck-mounted-crane"}]}']);
foreach ($GLOBALS['filters']['rest_pre_dispatch'] ?? [] as $filter) {
    $filter(null, null, $request);
}
if ($request->get_param('boblift_legacy_redirects') !== ['rules' => [['from' => '/news-show-273.html', 'to' => '/truck-mounted-crane']]]) {
    $failed++;
    echo "FAIL connector JSON string (legacy redirects)\n  got: " . json_encode($request->get_param('boblift_legacy_redirects')) . "\n";
}
echo $failed ? "{$failed} failed\n" : (count($cases) + 17) . " passed\n";
exit($failed ? 1 : 0);
