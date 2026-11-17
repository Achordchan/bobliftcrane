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
$GLOBALS['test_home'] = 'https://example.com';
function home_url(string $path = ''): string
{
    return $GLOBALS['test_home'] . $path;
}
function wp_parse_url(string $url): array|false
{
    return parse_url($url);
}
// Stands in for the media library: only files under /uploads/new/ (at the site root or in /blog) are attachments;
// .pdf ones are attachment 8, which is not an image.
function attachment_url_to_postid(string $url): int
{
    if (preg_match('~^https://example\.com(/blog)?/wp-content/uploads/new/~', $url) !== 1) {
        return 0;
    }
    return str_ends_with($url, '.pdf') ? 8 : 7;
}
function wp_attachment_is_image(int $id): bool
{
    return $id === 7;
}
// The image cases need WordPress's HTML API: WP_CORE_DIR=/path/to/wordpress php scripts/test-content-insert.php
$wp_core = getenv('WP_CORE_DIR') ?: '';
if ($wp_core !== '') {
    function esc_url(string $url): string
    {
        return str_replace(['"', "'", ' '], ['%22', '%27', '%20'], $url);
    }
    function wp_kses_uri_attributes(): array
    {
        return ['href', 'src', 'srcset', 'action', 'cite', 'longdesc', 'usemap', 'data', 'formaction', 'poster', 'xmlns'];
    }
    function wp_has_noncharacters(string $text): bool
    {
        return false;
    }
    function __(string $text): string
    {
        return $text;
    }
    function _doing_it_wrong(...$args): void
    {
    }
    require_once "{$wp_core}/wp-includes/class-wp-token-map.php";
    foreach (['class-wp-html-attribute-token', 'class-wp-html-span', 'class-wp-html-text-replacement', 'class-wp-html-decoder', 'html5-named-character-references', 'class-wp-html-doctype-info', 'class-wp-html-tag-processor'] as $file) {
        if (is_file("{$wp_core}/wp-includes/html-api/{$file}.php")) {
            require_once "{$wp_core}/wp-includes/html-api/{$file}.php";
        }
    }
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
    'swap src and alt' => ['<p>x</p><figure><img src="/a.png" alt="Old" width="2"></figure>', ['image' => '/a.png', 'src' => $new, 'alt' => "Kid's \$10 chart"], '<p>x</p><figure><img src="' . $new . '" alt="Kid&apos;s $10 chart" width="2"></figure>'],
    'swap src only' => ["<img alt=\"Old\" src='/a.png'>", ['image' => '/a.png', 'src' => $new], '<img alt="Old" src="' . $new . '">'],
    'add a missing alt' => ['<img src="/a.png">', ['image' => '/a.png', 'src' => $new, 'alt' => '$1 A'], '<img alt="$1 A" src="' . $new . '">'],
    'drop the old srcset' => ['<img src="/a.png" srcset="/a-300.png 300w" sizes="100vw" alt="o">', ['image' => '/a.png', 'src' => $new], '<img src="' . $new . '"   alt="o">'],
    'data-alt untouched' => ['<img data-alt="k" src="/a.png" alt="o">', ['image' => '/a.png', 'src' => $new, 'alt' => 'A'], '<img data-alt="k" src="' . $new . '" alt="A">'],
    'already swapped' => ['<img src="' . $new . '" alt="A">', ['image' => '/a.png', 'src' => $new, 'alt' => 'A'], '<img src="' . $new . '" alt="A">'],
    'only the matching image' => ['<img src="/c.png"><img src="/a.png">', ['image' => '/a.png', 'src' => $new], '<img src="/c.png"><img src="' . $new . '">'],
    'src inside another attribute' => ["<img title='x src=\"/a.png\"' src=\"/c.png\">", ['image' => '/a.png', 'src' => $new], 'boblift_insert_image'],
    'image inside a comment' => ['<!-- <img src="/a.png"> --><img src="/a.png">', ['image' => '/a.png', 'src' => $new], '<!-- <img src="/a.png"> --><img src="' . $new . '">'],
    'src in text only' => ['<p>/a.png</p>', ['image' => '/a.png', 'src' => $new], 'boblift_insert_image'],
    'image twice' => ['<img src="/a.png"><img src="/a.png">', ['image' => '/a.png', 'src' => $new], 'boblift_insert_image'],
    'not in the media library' => ['<img src="/a.png">', ['image' => '/a.png', 'src' => '/elsewhere/b.png'], 'boblift_insert_image'],
    'quote in src' => ['<img src="/a.png">', ['image' => '/a.png', 'src' => '/wp-content/uploads/new/b".png'], 'boblift_insert_image'],
    'tag in alt' => ['<img src="/a.png">', ['image' => '/a.png', 'src' => $new, 'alt' => '<b>'], 'boblift_insert_image'],
    'attachment class follows' => ['<img class="aligncenter wp-image-3 size-full" src="/a.png">', ['image' => '/a.png', 'src' => $new], '<img class="aligncenter size-full wp-image-7" src="' . $new . '">'],
    'block id follows' => ['<!-- wp:image {"id":3,"sizeSlug":"full"} --><figure class="wp-block-image"><img src="/a.png" class="wp-image-3"></figure><!-- /wp:image --><!-- wp:image {"id":31} -->', ['image' => '/a.png', 'src' => $new], '<!-- wp:image {"id":7,"sizeSlug":"full"} --><figure class="wp-block-image"><img src="' . $new . '" class="wp-image-7"></figure><!-- /wp:image --><!-- wp:image {"id":31} -->'],
    'only the enclosing block' => ['<!-- wp:image {"id":3} --><figure><img src="/b.png" class="wp-image-3"></figure><!-- /wp:image --><!-- wp:image {"id":3} --><figure><img src="/a.png" class="wp-image-3"></figure><!-- /wp:image -->', ['image' => '/a.png', 'src' => $new], '<!-- wp:image {"id":3} --><figure><img src="/b.png" class="wp-image-3"></figure><!-- /wp:image --><!-- wp:image {"id":7} --><figure><img src="' . $new . '" class="wp-image-7"></figure><!-- /wp:image -->'],
    'classic image beside a block' => ['<!-- wp:image {"id":3} --><figure><img src="/b.png" class="wp-image-3"></figure><!-- /wp:image --><p><img src="/a.png" class="wp-image-3"></p>', ['image' => '/a.png', 'src' => $new], '<!-- wp:image {"id":3} --><figure><img src="/b.png" class="wp-image-3"></figure><!-- /wp:image --><p><img src="' . $new . '" class="wp-image-7"></p>'],
    'block id without a class' => ['<!-- wp:image {"id":3,"lightbox":{"enabled":true}} --><figure><img src="/a.png"></figure><!-- /wp:image -->', ['image' => '/a.png', 'src' => $new], '<!-- wp:image {"id":7,"lightbox":{"enabled":true}} --><figure><img src="' . $new . '"></figure><!-- /wp:image -->'],
    'spaced block id' => ['<!-- wp:image {"id": 3 ,"sizeSlug":"full"} --><figure><img src="/a.png" class="wp-image-3"></figure><!-- /wp:image -->', ['image' => '/a.png', 'src' => $new], '<!-- wp:image {"id": 7 ,"sizeSlug":"full"} --><figure><img src="' . $new . '" class="wp-image-7"></figure><!-- /wp:image -->'],
    'self-closing block before' => ['<!-- wp:image {"id":3} /--><p><img src="/a.png"></p>', ['image' => '/a.png', 'src' => $new], '<!-- wp:image {"id":3} /--><p><img src="' . $new . '"></p>'],
    'image inside picture' => ['<picture><source srcset="/a.webp"><img src="/a.png"></picture>', ['image' => '/a.png', 'src' => $new], 'boblift_insert_image'],
    'image after a closed picture' => ['<picture><img src="/c.png"></picture><img src="/a.png">', ['image' => '/a.png', 'src' => $new], '<picture><img src="/c.png"></picture><img src="' . $new . '">'],
    'image inside a cover block' => ['<!-- wp:cover {"url":"/a.png","id":3} --><div class="wp-block-cover"><img src="/a.png" class="wp-block-cover__image-background wp-image-3"></div><!-- /wp:cover -->', ['image' => '/a.png', 'src' => $new], 'boblift_insert_image'],
    'image block inside a group' => ['<!-- wp:group --><div><!-- wp:image {"id":3} --><figure><img src="/a.png"></figure><!-- /wp:image --></div><!-- /wp:group -->', ['image' => '/a.png', 'src' => $new], '<!-- wp:group --><div><!-- wp:image {"id":7} --><figure><img src="' . $new . '"></figure><!-- /wp:image --></div><!-- /wp:group -->'],
    'image in an html block' => ['<!-- wp:html --><img src="/a.png"><!-- /wp:html -->', ['image' => '/a.png', 'src' => $new], '<!-- wp:html --><img src="' . $new . '"><!-- /wp:html -->'],
    'classic image after a closed cover' => ['<!-- wp:cover {"id":3} --><div></div><!-- /wp:cover --><img src="/a.png">', ['image' => '/a.png', 'src' => $new], '<!-- wp:cover {"id":3} --><div></div><!-- /wp:cover --><img src="' . $new . '">'],
    'commented picture closer' => ['<picture><source srcset="/a.webp"><!-- </picture> --><img src="/a.png"></picture>', ['image' => '/a.png', 'src' => $new], 'boblift_insert_image'],
    'commented picture opener' => ['<!-- <picture> --><img src="/a.png">', ['image' => '/a.png', 'src' => $new], '<!-- <picture> --><img src="' . $new . '">'],
    'uploads-relative src' => ['<img src="/a.png">', ['image' => '/a.png', 'src' => '2026/11/b.png'], 'boblift_insert_image'],
    'protocol-relative src' => ['<img src="/a.png">', ['image' => '/a.png', 'src' => '//example.com/wp-content/uploads/new/b.png'], 'boblift_insert_image'],
    'absolute src' => ['<img src="/a.png">', ['image' => '/a.png', 'src' => 'https://example.com' . $new], '<img src="https://example.com' . $new . '">'],
    'not an image attachment' => ['<img src="/a.png">', ['image' => '/a.png', 'src' => '/wp-content/uploads/new/b.pdf'], 'boblift_insert_image'],
    'no class, no id added' => ['<img class="big" src="/a.png">', ['image' => '/a.png', 'src' => $new], '<img class="big" src="' . $new . '">'],
    'swap with another item' => ['<p>A</p><img src="/a.png">', [['after' => 'A', 'html' => '<p>N</p>'], ['image' => '/a.png', 'src' => $new]], 'boblift_insert_replace'],
];
$vo = 'https://videos.files.wordpress.com/AbC/a.mp4';
$vn = 'https://videos.files.wordpress.com/XyZ/a-hd.mp4';
$vp = '/wp-content/uploads/2026/11/a-poster.jpg';
$images += [
    'video in classic html' => ['<figure><video controls src="' . $vo . '" poster="/p.jpg"></video></figure>', ['video' => $vo, 'src' => $vn], '<figure><video controls src="' . $vn . '" poster="/p.jpg"></video></figure>'],
    'video with new poster' => ['<video src="' . $vo . '" poster="/p.jpg"></video>', ['video' => $vo, 'src' => $vn, 'poster' => $vp], '<video src="' . $vn . '" poster="' . $vp . '"></video>'],
    'video in an html block' => ["<!-- wp:html -->\n<video src=\"{$vo}\"></video>\n<!-- /wp:html -->", ['video' => $vo, 'src' => $vn], "<!-- wp:html -->\n<video src=\"{$vn}\"></video>\n<!-- /wp:html -->"],
    'video already swapped' => ['<video src="' . $vn . '"></video>', ['video' => $vo, 'src' => $vn], '<video src="' . $vn . '"></video>'],
    'video not found' => ['<video src="/other.mp4"></video>', ['video' => $vo, 'src' => $vn], 'boblift_insert_video'],
    'video twice' => ['<video src="' . $vo . '"></video><video src="' . $vo . '"></video>', ['video' => $vo, 'src' => $vn], 'boblift_insert_video'],
    'video src in a link only' => ['<a href="' . $vo . '">v</a>', ['video' => $vo, 'src' => $vn], 'boblift_insert_video'],
    'video jsonld contentUrl follows' => ['<video src="' . $vo . '"></video><script type="application/ld+json">{"contentUrl":"' . $vo . '","description":"See ' . $vo . '"}</script>', ['video' => $vo, 'src' => $vn], '<video src="' . $vn . '"></video><script type="application/ld+json">{"contentUrl":"' . $vn . '","description":"See ' . $vo . '"}</script>'],
    'video jsonld escaped slashes' => ['<video src="' . $vo . '"></video><script type="application/ld+json">{"contentUrl":' . json_encode($vo) . '}</script>', ['video' => $vo, 'src' => $vn], '<video src="' . $vn . '"></video><script type="application/ld+json">{"contentUrl":' . json_encode($vn) . '}</script>'],
    'video done, jsonld still old' => ['<video src="' . $vn . '"></video><script type="application/ld+json">{"contentUrl":"' . $vo . '"}</script>', ['video' => $vo, 'src' => $vn], '<video src="' . $vn . '"></video><script type="application/ld+json">{"contentUrl":"' . $vn . '"}</script>'],
    'video url outside jsonld kept' => ['<video src="' . $vo . '"></video><p>"' . $vo . '"</p><script>var u="' . $vo . '";</script>', ['video' => $vo, 'src' => $vn], '<video src="' . $vn . '"></video><p>"' . $vo . '"</p><script>var u="' . $vo . '";</script>'],
    'video in a video block' => ['<!-- wp:video {"id":3} --><figure class="wp-block-video"><video src="' . $vo . '"></video></figure><!-- /wp:video -->', ['video' => $vo, 'src' => $vn], 'boblift_insert_video'],
    'video relative src' => ['<video src="' . $vo . '"></video>', ['video' => $vo, 'src' => 'a-hd.mp4'], 'boblift_insert_video'],
    'video bad poster' => ['<video src="' . $vo . '"></video>', ['video' => $vo, 'src' => $vn, 'poster' => '//x.com/p.jpg'], 'boblift_insert_video'],
    'video with another item' => ['<p>A</p><video src="' . $vo . '"></video>', [['after' => 'A', 'html' => '<p>N</p>'], ['video' => $vo, 'src' => $vn]], 'boblift_insert_replace'],
];
if (!class_exists('WP_HTML_Tag_Processor')) {
    echo 'SKIPPED ' . count($images) . " image and video cases: set WP_CORE_DIR to a WordPress copy\n";
    $images = [];
}
foreach ($images as $name => [$content, $item, $want]) {
    $got = boblift_content_insert_apply($content, $item);
    $got = is_wp_error($got) ? $got->code : $got;
    if ($got !== $want) {
        $failed++;
        echo "FAIL image: {$name}\n  want: " . json_encode($want) . "\n  got:  " . json_encode($got) . "\n";
    }
}
// WordPress in a subdirectory: a root-relative src already carries the /blog path.
$extra = 0;
if ($images) {
    $extra = 1;
    $GLOBALS['test_home'] = 'https://example.com/blog';
    $got = boblift_content_insert_apply('<img src="/a.png">', ['image' => '/a.png', 'src' => '/blog' . $new]);
    $GLOBALS['test_home'] = 'https://example.com';
    if ($got !== '<img src="/blog' . $new . '">') {
        $failed++;
        echo "FAIL image: subdirectory home\n  got:  " . json_encode(is_wp_error($got) ? $got->code : $got) . "\n";
    }
}
echo $failed ? "{$failed} failed\n" : (count($cases) + count($images) + $extra + 1) . " passed\n";
exit($failed ? 1 : 0);
