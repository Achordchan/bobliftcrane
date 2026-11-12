<?php
// Checks the home page best-seller helpers without WordPress: php scripts/test-home-products.php
define('ABSPATH', __DIR__);
function boblift_localized_static_text(string $text): string
{
    return $text;
}
require __DIR__ . '/../inc/home-products.php';

$failures = 0;
$check = static function (string $name, $expected, $actual) use (&$failures): void {
    if ($expected !== $actual) {
        $failures++;
        echo "FAIL {$name}: expected " . json_encode($expected, JSON_UNESCAPED_UNICODE) . ', got ' . json_encode($actual, JSON_UNESCAPED_UNICODE) . "\n";
    }
};

// A tail lift's capacity as its spec table writes it.
$check('tons range', '1–2 t', boblift_home_tonnage_text('1-2 tons'));
$check('t range with spaces', '1.5–3 t', boblift_home_tonnage_text('1.5 - 3 t'));
$check('other values unchanged', '3,500 kg', boblift_home_tonnage_text('3,500 kg'));
// Saved best sellers merge with the defaults field by field (#282 review).
$picks = boblift_home_best_sellers([
    'marine-crane' => ['product' => '', 'model' => 'SQ1ZA3TJ+1'],
    'truck-mounted-crane' => ['product' => '12-ton-knuckle-boom-truck-mounted-crane', 'model' => ''],
    'electric-mini-cranes' => ['product' => '', 'model' => ''],
    'forklift-fly-jib-cranes' => 'not an array',
]);
$check('a model alone applies to the default product', ['product' => '1-ton-knuckle-boom-marine-crane', 'model' => 'SQ1ZA3TJ+1'], $picks['marine-crane']);
$check('a new product drops the default model', ['product' => '12-ton-knuckle-boom-truck-mounted-crane'], $picks['truck-mounted-crane']);
$check('blank fields keep the default', boblift_home_best_seller_defaults()['electric-mini-cranes'], $picks['electric-mini-cranes']);
$check('the default model stays with a blank override', 'SQ10SA4', boblift_home_best_sellers(['truck-mounted-crane' => ['product' => '', 'model' => '']])['truck-mounted-crane']['model']);
$check('a malformed entry is ignored', boblift_home_best_seller_defaults()['forklift-fly-jib-cranes'], $picks['forklift-fly-jib-cranes']);
// Every default best seller is a family with a photo.
$check('each default family has a photo', [], array_values(array_diff(array_keys(boblift_home_best_seller_defaults()), array_keys(boblift_home_family_photos()))));
foreach (boblift_home_family_photos() as $family => $photo) {
    $check("photo file exists: {$family}", true, is_readable(__DIR__ . '/../assets/' . $photo['image']));
}

echo $failures === 0 ? "All home products checks passed\n" : "{$failures} failure(s)\n";
exit($failures === 0 ? 0 : 1);
