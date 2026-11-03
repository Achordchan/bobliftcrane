<?php
// Checks boblift_product_category_root_request_fallback() without WordPress: php scripts/test-category-root-route.php
define('ABSPATH', __DIR__);
class WP_Term
{
    public function __construct(public string $slug)
    {
    }
}
function add_filter(...$args): void
{
}
function is_wp_error($thing): bool
{
    return false;
}
function get_option(string $name)
{
    return $name === 'home' ? 'https://bobliftcrane.com' : null;
}
function sanitize_title(string $title): string
{
    return strtolower(preg_replace('/[^A-Za-z0-9_-]+/', '-', $title));
}
function wp_parse_url(string $url, int $component = -1)
{
    return parse_url($url, $component);
}
// Every language's categories, as get_terms(['lang' => '']) returns them.
function get_terms(array $args): array
{
    return in_array($args['slug'] ?? '', ['marine-crane', 'truck-mounted-crane'], true) ? [new WP_Term($args['slug'])] : [];
}
function boblift_language_path_base(): string
{
    return '';
}
function boblift_prefixed_languages(): array
{
    return ['zh', 'ja', 'es', 'ru', 'fr', 'ar'];
}
require dirname(__DIR__) . '/inc/category-root-route.php';

$cases = [
    'rule missing, page rule matched' => ['/marine-crane/', ['pagename' => 'marine-crane'], ['boblift_product_category' => 'marine-crane']],
    'rule missing, nothing matched' => ['/marine-crane', [], ['boblift_product_category' => 'marine-crane']],
    'language directory' => ['/zh/marine-crane/', ['lang' => 'zh', 'pagename' => 'marine-crane'], ['boblift_product_category' => 'marine-crane', 'lang' => 'zh']],
    'query string ignored' => ['/truck-mounted-crane/?capacity=4-5', ['pagename' => 'truck-mounted-crane'], ['boblift_product_category' => 'truck-mounted-crane']],
    'rule present: untouched' => ['/marine-crane/', ['boblift_product_category' => 'marine-crane'], ['boblift_product_category' => 'marine-crane']],
    'custom url: untouched' => ['/some-path/', ['boblift_custom_url' => 'some-path'], ['boblift_custom_url' => 'some-path']],
    'page: untouched' => ['/contact/', ['pagename' => 'contact'], ['pagename' => 'contact']],
    'html page: untouched' => ['/marine-crane.html', ['boblift_html_slug' => 'marine-crane'], ['boblift_html_slug' => 'marine-crane']],
    'two segments: untouched' => ['/news/marine-crane/', ['name' => 'marine-crane'], ['name' => 'marine-crane']],
    'home: untouched' => ['/', [], []],
    'language home: untouched' => ['/zh/', ['lang' => 'zh'], ['lang' => 'zh']],
    'feed: untouched' => ['/marine-crane/feed/', ['feed' => 'feed'], ['feed' => 'feed']],
];
$failed = 0;
foreach ($cases as $name => [$uri, $vars, $expected]) {
    $_SERVER['REQUEST_URI'] = $uri;
    $actual = boblift_product_category_root_request_fallback($vars);
    if ($actual !== $expected) {
        $failed++;
        echo "FAIL {$name}: " . json_encode($actual) . ' expected ' . json_encode($expected) . "\n";
    }
}
echo $failed ? "{$failed} failed\n" : count($cases) . " passed\n";
exit($failed ? 1 : 0);
