<?php
// Checks boblift_content_insert_after() without WordPress: php scripts/test-content-insert.php
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
require dirname(__DIR__) . '/inc/rest-content-insert.php';

$cases = [
    'classic paragraph' => ['<p>A.</p> <p>● Notes - z.</p> <p><br></p>', 'Notes - z.', "<p>A.</p> <p>● Notes - z.</p>\n<p>N</p> <p><br></p>"],
    'snippet ends with its close tag' => ['<p>A.</p><p>B.</p>', 'A.</p>', "<p>A.</p>\n<p>N</p><p>B.</p>"],
    'snippet starts with its open tag' => ['<p>A.</p><p>B.</p>', '<p>A', "<p>A.</p>\n<p>N</p><p>B.</p>"],
    'block paragraph' => ["<!-- wp:paragraph -->\n<p>Hi</p>\n<!-- /wp:paragraph -->\n<p>z</p>", 'Hi', "<!-- wp:paragraph -->\n<p>Hi</p>\n<!-- /wp:paragraph -->\n<p>N</p>\n<p>z</p>"],
    'block list' => ['<!-- wp:list --><ul><li>a</li><li>b</li></ul><!-- /wp:list --><p>z</p>', '<li>a', "<!-- wp:list --><ul><li>a</li><li>b</li></ul><!-- /wp:list -->\n<p>N</p><p>z</p>"],
    'paragraph in a list item' => ['<ul><li><p>A</p><ul><li>i</li></ul></li></ul><p>z</p>', 'A', "<ul><li><p>A</p><ul><li>i</li></ul></li></ul>\n<p>N</p><p>z</p>"],
    'nested list' => ['<ul><li>x<ul><li>A</li></ul> t</li></ul><p>z</p>', 'A', "<ul><li>x<ul><li>A</li></ul> t</li></ul>\n<p>N</p><p>z</p>"],
    'table cell' => ['<table><tr><td>A</td></tr></table><p>z</p>', 'A', "<table><tr><td>A</td></tr></table>\n<p>N</p><p>z</p>"],
    'block table' => ["<!-- wp:table -->\n<figure class=\"wp-block-table\"><table><tbody><tr><td>A</td></tr></tbody></table><figcaption>c</figcaption></figure>\n<!-- /wp:table --><p>z</p>", 'A', "<!-- wp:table -->\n<figure class=\"wp-block-table\"><table><tbody><tr><td>A</td></tr></tbody></table><figcaption>c</figcaption></figure>\n<!-- /wp:table -->\n<p>N</p><p>z</p>"],
    'image caption' => ['<figure><img src="x.jpg" alt=""><figcaption>Cap</figcaption></figure><p>z</p>', 'Cap', "<figure><img src=\"x.jpg\" alt=\"\"><figcaption>Cap</figcaption></figure>\n<p>N</p><p>z</p>"],
    'bare blockquote' => ['<blockquote>Quote</blockquote><p>z</p>', 'Quote', "<blockquote>Quote</blockquote>\n<p>N</p><p>z</p>"],
    'paragraph in a blockquote' => ['<blockquote><p>Quote</p></blockquote><p>z</p>', 'Quote', "<blockquote><p>Quote</p></blockquote>\n<p>N</p><p>z</p>"],
    'tag inside a comment' => ['<p>A <!-- </p> --> b</p><p>z</p>', 'A', "<p>A <!-- </p> --> b</p>\n<p>N</p><p>z</p>"],
    'tag inside a script' => ['<p>A<script>var s="</p>";</script> b</p><p>z</p>', 'A', "<p>A<script>var s=\"</p>\";</script> b</p>\n<p>N</p><p>z</p>"],
    'already inserted' => ["<p>A</p>\n<p>N</p>", 'A', "<p>A</p>\n<p>N</p>"],
    'unclosed paragraphs' => ['<p>one<p>A two<p>three', 'A', 'boblift_insert_element'],
    'div only' => ['<div>A</div>', 'A', 'boblift_insert_element'],
    'snippet twice' => ['<p>A</p><p>A</p>', 'A', 'boblift_insert_anchor'],
    'snippet missing' => ['<p>A</p>', 'Q', 'boblift_insert_anchor'],
];
$failed = 0;
foreach ($cases as $name => [$content, $after, $want]) {
    $got = boblift_content_insert_after($content, $after, '<p>N</p>');
    $got = is_wp_error($got) ? $got->code : $got;
    if ($got !== $want) {
        $failed++;
        echo "FAIL {$name}\n  want: " . json_encode($want) . "\n  got:  " . json_encode($got) . "\n";
    }
}
echo $failed ? "{$failed} failed\n" : count($cases) . " passed\n";
exit($failed ? 1 : 0);
