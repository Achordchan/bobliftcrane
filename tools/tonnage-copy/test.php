<?php
// php tools/tonnage-copy/test.php  — checks the boblift_tonnage_copy field's validation, save and read without WordPress.
define('ABSPATH', __DIR__);
class WP_Error { public function __construct(public string $code = '', public string $message = '', public array $data = []) {} }
class WP_Post {}
function is_wp_error($thing): bool { return $thing instanceof WP_Error; }
function add_action(...$args): void {}
function sanitize_text_field(string $text): string { return trim(preg_replace('/[\r\n\t ]+/', ' ', strip_tags($text))); }
function maybe_serialize($data) { return serialize($data); }
function wp_cache_delete(...$args): void {}
function add_option(string $name, $value): bool { $GLOBALS['db'][$name] = serialize($value); return true; }
function boblift_current_language_slug(): string { return $GLOBALS['language']; }
function boblift_tonnage_bins(): array { return [12 => '10-12', 16 => '12-16', 20 => '16-20', 25 => '20-25']; }
function boblift_filter_bins_stored(string $language): array
{
    $name = 'boblift_theme_options_' . $language;
    $raw = $GLOBALS['db'][$name] ?? null;
    $options = $raw === null ? [] : unserialize($raw);
    return ['name' => $name, 'raw' => $raw, 'options' => is_array($options) ? $options : []];
}
$wpdb = new class {
    public string $options = 'wp_options';
    public function prepare(string $query, ...$args): array { return $args; }
    public function query(array $args): int
    {
        [$value, $name, $raw] = $args;
        if (($GLOBALS['db'][$name] ?? null) !== $raw) {
            return 0;
        }
        $GLOBALS['db'][$name] = $value;
        return 1;
    }
};
require __DIR__ . '/../../inc/rest-tonnage-copy.php';

$failures = 0;
$check = static function (string $label, bool $ok) use (&$failures): void {
    echo ($ok ? 'ok   ' : 'FAIL ') . $label . "\n";
    $failures += $ok ? 0 : 1;
};

$check('rejects an empty copy', is_wp_error(boblift_tonnage_copy_clean([])));
$check('rejects an unknown key', is_wp_error(boblift_tonnage_copy_clean(['intro' => 'x'])));
$check('rejects HTML', is_wp_error(boblift_tonnage_copy_clean(['lead' => '<b>x</b>'])));
$check('rejects a title over 70 characters', is_wp_error(boblift_tonnage_copy_clean(['title' => str_repeat('a', 71)])));
$check('keeps plain text', boblift_tonnage_copy_clean(['title' => ' 16 Ton Truck Crane | BOB-LIFT ']) === ['title' => '16 Ton Truck Crane | BOB-LIFT']);

$GLOBALS['db'] = ['boblift_theme_options_en' => serialize(['site_ui' => ['productDetail' => ['faqItems' => [1]]], 'company' => 'x'])];
$check('saves a page', boblift_tonnage_copy_save('en', 16, ['heading' => '16 Ton Truck Cranes', 'lead' => 'A lead.']) === true);
$check('saves a second page', boblift_tonnage_copy_save('en', 12, ['title' => '12 Ton Truck Crane']) === true);
$saved = unserialize($GLOBALS['db']['boblift_theme_options_en']);
$check('keeps the other options', $saved['company'] === 'x' && $saved['site_ui']['productDetail']['faqItems'] === [1]);
$check('stores both pages in size order', array_keys($saved['site_ui']['tonnagePages']) === [12, 16]);
$check('leaves other languages alone', !isset($GLOBALS['db']['boblift_theme_options_zh']));

$GLOBALS['language'] = 'en';
$check('reads the page back', boblift_tonnage_copy(16) === ['heading' => '16 Ton Truck Cranes', 'lead' => 'A lead.']);
$check('a page without words reads empty', boblift_tonnage_copy(20) === []);

$check('drops a page', boblift_tonnage_copy_save('en', 12, null) === true);
$check('drops the last page and the key', boblift_tonnage_copy_save('en', 16, null) === true && !isset(unserialize($GLOBALS['db']['boblift_theme_options_en'])['site_ui']['tonnagePages']));

exit($failures > 0 ? 1 : 0);
