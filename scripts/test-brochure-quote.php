<?php
// php scripts/test-brochure-quote.php [archive|product]: the brochure cards' quote button and grid, without WordPress.
// With an argument it prints that block's markup instead (for a browser preview).
define('ABSPATH', __DIR__);
class WP_Term {}
function esc_url($v) { return htmlspecialchars((string) $v, ENT_QUOTES); }
function esc_attr($v) { return htmlspecialchars((string) $v, ENT_QUOTES); }
function esc_html($v) { return htmlspecialchars((string) $v, ENT_QUOTES); }
function wp_json_encode($v, $f = 0) { return json_encode($v, $f); }
function boblift_icon($n, $c = '') { return '<svg class="' . $c . '"></svg>'; }
function boblift_site_ui($section) { return (require __DIR__ . '/../inc/site-ui-sections/brochures.php')[$section]; }
// The PDFs are not in the repository: a throwaway theme folder holds stand-ins of the right names.
$theme = sys_get_temp_dir() . '/boblift-brochure-test';
@mkdir($theme . '/assets/downloads', 0777, true);
foreach (['truck-mounted-cranes', 'marine-cranes', 'spider-cranes'] as $name) {
    file_put_contents($theme . '/assets/downloads/bob-lift-' . $name . '-2026.pdf', str_repeat('x', 1000));
}
function get_template_directory() { return sys_get_temp_dir() . '/boblift-brochure-test'; }
function boblift_asset($path) { return 'https://bobliftcrane.com/wp-content/themes/bobliftcrane/assets/' . $path; }
function size_format($bytes, $d = 0) { return round($bytes / 1048576, $d) . ' MB'; }
function get_posts($args) { return [1]; }
function wp_get_attachment_url($id) { return 'https://bobliftcrane.com/wp-content/uploads/2026/11/file.pdf'; }
function wp_get_attachment_metadata($id) { return ['filesize' => 2500000]; }
function boblift_categories() { return [['slug' => 'forklift-fly-jib-cranes']]; }
function boblift_product_category_hierarchy() { return []; }
require __DIR__ . '/../inc/brochures.php';

$render = static function (array $args): string {
    ob_start();
    boblift_render_brochures($args);
    return (string) ob_get_clean();
};
$archive = $render(['context' => 'archive']);
$product = $render(['context' => 'product', 'families' => ['truck', 'marine'], 'product' => 'SQ5SA2 Truck Mounted Crane', 'product_slug' => 'sq5sa2']);
$single = $render(['context' => 'product', 'families' => ['forklift'], 'product' => 'CC85 Forklift Fly Jib Crane', 'product_slug' => 'cc85']);
if (in_array($argv[1] ?? '', ['archive', 'product'], true)) {
    echo $argv[1] === 'archive' ? $archive : $product;
    exit(0);
}
$failed = 0;
$check = static function (string $name, bool $ok) use (&$failed): void {
    echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n";
    $failed += $ok ? 0 : 1;
};
$buttons = static function (string $html): array {
    preg_match_all('~<button [^>]*data-inquiry-modal-open[^>]*>([^<]*)</button>~', $html, $m, PREG_SET_ORDER);
    return $m;
};
$check('archive: one quote button per brochure', count($buttons($archive)) === 5);
$check('no email-a-copy button left', !str_contains($archive . $product . $single, 'data-catalog-modal-open') && !str_contains($archive, 'Email me a copy'));
$check('button label', $buttons($archive)[0][1] === 'Get a quote for this range');
$check('archive: no card stretches across columns', !preg_match('~col-span-[23]~', $archive));
$check('archive: buttons sit at the bottom of each card', substr_count($archive, 'mt-auto flex flex-wrap') === 5);
preg_match('~data-inquiry-products="([^"]*)"~', $archive, $m);
$picked = json_decode(html_entity_decode($m[1] ?? '', ENT_QUOTES), true);
$check('archive: asks about the brochure, cover as thumbnail', ($picked[0]['title'] ?? '') === 'Truck Mounted Cranes Brochure' && str_ends_with($picked[0]['image'] ?? '', '-cover.webp'));
$check('archive: inquiry source', str_contains($archive, 'data-inquiry-source="产品手册询价"'));
preg_match('~data-inquiry-products="([^"]*)"~', $product, $m);
$picked = json_decode(html_entity_decode($m[1] ?? '', ENT_QUOTES), true);
$check('product page: asks about the product', ($picked[0]['title'] ?? '') === 'SQ5SA2 Truck Mounted Crane' && ($picked[0]['slug'] ?? '') === 'sq5sa2' && ($picked[0]['category'] ?? '') === 'Truck Mounted Cranes Brochure');
$check('product page: slug and title on the button too', str_contains($product, 'data-inquiry-product="SQ5SA2 Truck Mounted Crane"') && str_contains($product, 'data-inquiry-product-slug="sq5sa2"'));
$check('single brochure block keeps one quote button', count($buttons($single)) === 1);
$check('PDF stays a direct download', substr_count($archive, ' download="') === 10);
$tricky = boblift_brochure_quote_button(['title' => 'A "B" <C>', 'cover' => 'https://x/c.webp'], [], 'c');
$check('quotes and tags are escaped', !str_contains($tricky, '<C>') && !preg_match('~data-inquiry-product="A "~', $tricky));
echo $failed ? "\n$failed failed\n" : "\nall passed\n";
exit($failed ? 1 : 0);
