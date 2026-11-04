<?php
// php scripts/test-video-center.php: Video Center picks up films from product bodies (one card per film, filed by family)
// and validates the extra-films list written through the boblift_video_center_extra REST field.
define('ABSPATH', __DIR__);
define('HOUR_IN_SECONDS', 3600);
class WP_Error { public function __construct(public string $code = '', public string $message = '', public array $data = []) {} }
class WP_Post { public int $ID = 0; public string $post_content = ''; public string $post_title = ''; }
function is_wp_error($v) { return $v instanceof WP_Error; }
function add_action(...$args) {}
function add_filter(...$args) {}
function sanitize_key($v) { return strtolower(preg_replace('/[^a-z0-9_\-]/i', '', $v)); }
function sanitize_text_field($v) { return trim(strip_tags($v)); }
function esc_url_raw($v) { return $v; }
$options = [];
function get_option($k, $d = false) { global $options; return $options[$k] ?? $d; }
function update_option($k, $v) { global $options; $options[$k] = $v; return true; }
$transients = [];
function get_transient($k) { global $transients; return $transients[$k] ?? false; }
function set_transient($k, $v) { global $transients; $transients[$k] = $v; }
function get_post_type($id) { return $id > 0 ? 'product' : 'page'; }
function boblift_current_language_slug() { return 'en'; }
function post_password_required($p) { return false; }
function boblift_product_content_ready($id, $flag) { return $id !== 4; }
function get_the_title($p) { return $p->post_title; }
class WP_Term { public int $term_id = 0; public function __construct(public string $slug = '') {} }
function get_the_terms($id, $tax) { $slug = [1 => 'knuckle-boom-truck-mounted-cranes', 2 => 'forklift-fly-jib-cranes', 3 => 'electric-mini-cranes', 4 => 'marine-cranes'][$id] ?? ''; return $slug === '' ? false : [new WP_Term($slug)]; }
function get_ancestors(...$args) { return []; }
$fig = static fn(string $src, string $cap = '') => '<figure class="wp-block-video"><video controls poster="https://x/p.jpg" src="https://v/' . $src . '.mp4"></video>' . ($cap !== '' ? '<figcaption>' . $cap . '</figcaption>' : '') . '</figure>';
$posts = [];
foreach ([[1, $fig('a', 'SQ5 film')], [2, '<p>x</p>' . $fig('jib')], [3, $fig('jib', 'Other cap') . $fig('mini', 'Mini')], [4, $fig('hidden')], [5, '<p>no film</p>']] as [$id, $content]) {
    $p = new WP_Post();
    $p->ID = $id;
    $p->post_content = $content;
    $p->post_title = "P$id";
    $posts[] = $p;
}
function get_posts($args) { global $posts; return $posts; }
require __DIR__ . '/../inc/video-library.php';
require __DIR__ . '/../inc/video-center.php';
$failed = 0;
$check = static function (bool $ok, string $what) use (&$failed) { if (!$ok) { $failed++; echo "fail: $what\n"; } };

$v = boblift_video_center_product_videos();
$check(count($v) === 3, 'three distinct films (shared film once, unready product skipped)');
$check(($v[0]['label'] ?? '') === 'SQ5 film' && $v[0]['group'] === 'truck', 'captioned truck film');
$check(($v[1]['src'] ?? '') === 'https://v/jib.mp4' && $v[1]['label'] === 'P2' && $v[1]['group'] === 'product', 'uncaptioned film named after its product, other family');
$check(($v[2]['label'] ?? '') === 'Mini' && ($v[2]['poster_src'] ?? '') === 'https://x/p.jpg', 'poster kept');
$posts = [];
$check(count(boblift_video_center_product_videos()) === 3, 'cached');
boblift_video_center_flush_products(1);
$check(boblift_video_center_product_videos() === [], 'a product save drops the cache');

$good = ['src' => 'https://videos.files.wordpress.com/a/b.mp4', 'poster' => 'https://bobliftcrane.com/p.jpg', 'title' => 'CC85 demo', 'group' => 'product'];
$check(boblift_video_center_clean_extra([$good]) === [$good], 'valid item');
$check(boblift_video_center_clean_extra([]) === [], 'empty list clears');
foreach ([['src' => 'http://x/b.mp4'], ['src' => 'https://x/b.mov'], ['poster' => ''], ['title' => ''], ['title' => '<b>x</b>'], ['group' => 'aerial']] as $bad) {
    $check(is_wp_error(boblift_video_center_clean_extra([array_replace($good, $bad)])), 'rejects ' . json_encode($bad));
}
$check(is_wp_error(boblift_video_center_clean_extra(['a' => $good])), 'rejects a map');
$check(is_wp_error(boblift_video_center_clean_extra(array_fill(0, 41, $good))), 'rejects more than 40');
echo $failed ? "$failed failed\n" : "ok\n";
exit($failed ? 1 : 0);
