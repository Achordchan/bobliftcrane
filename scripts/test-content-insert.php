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
function current_user_can(string $cap): bool
{
    return false;
}
// Stands in for KSES: it rewrites a bare ampersand the way a save without unfiltered_html does.
function wp_kses_post(string $html): string
{
    return preg_replace('~&(?!amp;)~', '&amp;', $html);
}
function esc_attr(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES);
}
function home_url(string $path = ''): string
{
    return 'https://example.com' . $path;
}
// Stands in for the media library: only files under /uploads/new/ are attachments.
function attachment_url_to_postid(string $url): int
{
    return str_starts_with($url, 'https://example.com/wp-content/uploads/new/') ? 7 : 0;
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
    'quoted attribute with a tag' => ['<p>A <span title="use > not </p> here">t</span></p><p>z</p>', 'A', "<p>A <span title=\"use > not </p> here\">t</span></p>\n<p>N</p><p>z</p>"],
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
// A repeat of a filtered insertion finds the filtered copy and changes nothing.
$value = ['after' => 'A', 'html' => '<p><a href="/x?a=1&b=2">x</a></p>'];
$once = boblift_content_insert_apply('<p>A</p>', $value);
if (boblift_content_insert_apply($once, $value) !== $once || !str_contains($once, '&amp;b=2')) {
    $failed++;
    echo "FAIL filtered repeat\n  got: " . json_encode($once) . "\n";
}
// Image swaps: [content, item, wanted content or error code].
$new = '/wp-content/uploads/new/b.png';
$images = [
    'swap src and alt' => ['<p>x</p><figure><img src="/a.png" alt="Old" width="2"></figure>', ['image' => '/a.png', 'src' => $new, 'alt' => "Kid's 11 m"], '<p>x</p><figure><img src="' . $new . '" alt="Kid&#039;s 11 m" width="2"></figure>'],
    'swap src only' => ['<img alt="Old" src=\'/a.png\'>', ['image' => '/a.png', 'src' => $new], '<img alt="Old" src="' . $new . '">'],
    'add a missing alt' => ['<img src="/a.png">', ['image' => '/a.png', 'src' => $new, 'alt' => 'A'], '<img alt="A" src="' . $new . '">'],
    'data-alt untouched' => ['<img data-alt="k" src="/a.png" alt="o">', ['image' => '/a.png', 'src' => $new, 'alt' => 'A'], '<img data-alt="k" src="' . $new . '" alt="A">'],
    'already swapped' => ['<img src="' . $new . '" alt="A">', ['image' => '/a.png', 'src' => $new, 'alt' => 'A'], '<img src="' . $new . '" alt="A">'],
    'only the matching image' => ['<img src="/c.png"><img src="/a.png">', ['image' => '/a.png', 'src' => $new], '<img src="/c.png"><img src="' . $new . '">'],
    'src in text only' => ['<p>/a.png</p>', ['image' => '/a.png', 'src' => $new], 'boblift_insert_image'],
    'image twice' => ['<img src="/a.png"><img src="/a.png">', ['image' => '/a.png', 'src' => $new], 'boblift_insert_image'],
    'not in the media library' => ['<img src="/a.png">', ['image' => '/a.png', 'src' => '/elsewhere/b.png'], 'boblift_insert_image'],
    'quote in src' => ['<img src="/a.png">', ['image' => '/a.png', 'src' => '/wp-content/uploads/new/b".png'], 'boblift_insert_image'],
    'tag in alt' => ['<img src="/a.png">', ['image' => '/a.png', 'src' => $new, 'alt' => '<b>'], 'boblift_insert_image'],
    'two swaps in a list' => ['<img src="/a.png"><img src="/c.png">', [['image' => '/a.png', 'src' => $new], ['image' => '/c.png', 'src' => '/wp-content/uploads/new/d.png']], '<img src="' . $new . '"><img src="/wp-content/uploads/new/d.png">'],
];
foreach ($images as $name => [$content, $item, $want]) {
    $got = boblift_content_insert_apply($content, $item);
    $got = is_wp_error($got) ? $got->code : $got;
    if ($got !== $want) {
        $failed++;
        echo "FAIL image: {$name}\n  want: " . json_encode($want) . "\n  got:  " . json_encode($got) . "\n";
    }
}
echo $failed ? "{$failed} failed\n" : (count($cases) + count($images) + 1) . " passed\n";
exit($failed ? 1 : 0);
