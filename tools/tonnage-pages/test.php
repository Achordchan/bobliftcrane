<?php
// php tools/tonnage-pages/test.php  — checks which tonnage page lists which capacity (inc/tonnage-pages.php) without WordPress.
define('ABSPATH', __DIR__);
function add_action(...$args): void {}
function add_filter(...$args): void {}
function boblift_current_language_slug(): string { return 'en'; }
function get_post_meta(...$args) { return ''; }
function boblift_decode_meta($value, $fallback) { return $fallback; }
function boblift_product_english_id(int $id): int { return $id; }
require __DIR__ . '/../../inc/legacy-redirects.php';
// The current models' listed capacities on 2026-11-13 (the live tonnage pages), one product each.
function boblift_all_products(): array
{
    $capacities = [0.6, 0.7, 0.7, 0.8, 0.8, 0.8, 1, 1, 1.3, 1.6, 1.6, 1.6, 1.8, 2, 2, 2, 2, 2, 2, 2.3, 2.45, 2.5, 2.95, 3.2, 3.2, 3.2, 3.2, 3.2,
        3.5, 4, 4, 4, 4, 4, 4, 4.6, 4.6, 4.6, 4.9, 5, 5, 5, 5, 5, 5, 5.15, 6.3, 6.3, 6.3, 6.3, 6.3, 6.3, 7, 8, 8, 8, 8, 10, 10, 10, 10,
        12, 12, 12, 12, 14, 16, 16, 16, 16, 20, 20, 20, 20, 25, 25];
    return array_map(static fn(float $capacity): array => ['id' => 0, 'filter_values' => ['capacity' => $capacity]], $capacities);
}
require __DIR__ . '/../../inc/tonnage-pages.php';

$failures = 0;
$check = static function (string $label, bool $ok) use (&$failures): void {
    echo ($ok ? 'ok   ' : 'FAIL ') . $label . "\n";
    $failures += $ok ? 0 : 1;
};
$listed = static fn(int $size): array => array_values(array_unique(array_map(static fn(array $product): float => (float) $product['filter_values']['capacity'], boblift_tonnage_products($size))));

$check('3 Ton page lists 3.2 t and 3.5 t', in_array(3.2, $listed(3), true) && in_array(3.5, $listed(3), true));
$check('4 Ton page still lists 3.2 t', in_array(3.2, $listed(4), true));
$check('6 Ton page lists 6.3 t and 7 t', in_array(6.3, $listed(6), true) && in_array(7.0, $listed(6), true));
$check('8 Ton page still lists 6.3 t', in_array(6.3, $listed(8), true));
$check('4 Ton page leaves out 4.6 t (a 5 ton crane)', !in_array(4.6, $listed(4), true));
$check('12 Ton page lists 14 t, 16 Ton page too', in_array(14.0, $listed(12), true) && in_array(14.0, $listed(16), true));
$check('25 Ton page stops at 25 t', boblift_tonnage_page_range(25) === [20.0, 25.0]);
$check('3 Ton range key', boblift_tonnage_page_range_key(3) === '2-3.5');
$check('1 Ton range key', boblift_tonnage_page_range_key(1) === '0-1.5');
$check('6 Ton range key', boblift_tonnage_page_range_key(6) === '5-7');
foreach ([[3.2, 3], [3.5, 3], [6.3, 6], [4.6, 5], [5.15, 5], [7.0, 6], [14.0, 12], [2.95, 3], [0.6, 1], [25.0, 25]] as [$capacity, $size]) {
    $check("$capacity t links to the $size Ton page", boblift_tonnage_size_for_capacity((float) $capacity) === $size);
}
// Every model stays on the page its bin gave it before.
foreach (boblift_tonnage_bins() as $size => $bin) {
    [$min, $max] = boblift_tonnage_bin_range($bin);
    $before = array_filter(boblift_all_products(), static fn(array $product): bool => $product['filter_values']['capacity'] > $min && $product['filter_values']['capacity'] <= $max);
    $check("$size Ton page keeps its bin's models", count(array_diff(array_map(static fn($p) => (float) $p['filter_values']['capacity'], $before), $listed($size))) === 0);
}
foreach (array_keys(boblift_tonnage_bins()) as $size) {
    echo "  $size t (" . boblift_tonnage_page_range_key($size) . "): " . implode(', ', $listed($size)) . "\n";
}
exit($failures ? 1 : 0);
