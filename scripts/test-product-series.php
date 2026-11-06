<?php
// Checks the marine series grouping, model parsing and redirect targets without WordPress: php scripts/test-product-series.php
define('ABSPATH', __DIR__);
function add_action(...$args): void
{
}
function add_filter(...$args): void
{
}
function apply_filters(string $hook, $value, ...$args)
{
    return $value;
}
function boblift_localized_static_text(string $text): string
{
    return $text;
}
if (!function_exists('esc_attr')) {
    function esc_attr($text): string { return htmlspecialchars((string) $text, ENT_QUOTES); }
}
if (!function_exists('esc_html')) {
    function esc_html($text): string { return htmlspecialchars((string) $text, ENT_QUOTES); }
}
require __DIR__ . '/../inc/product-series.php';
require __DIR__ . '/../inc/product-guide.php';

$failures = 0;
$check = static function (string $name, $expected, $actual) use (&$failures): void {
    if ($expected !== $actual) {
        $failures++;
        echo "FAIL {$name}: expected " . json_encode($expected) . ', got ' . json_encode($actual) . "\n";
    }
};

// A configuration joins its series only where the series page is published without a password (#251 review).
$ok = static fn(int $id): bool => in_array($id, [1, 2, 3, 11, 12, 21], true);
$check('member joins in every language its series page is up', ['en' => 11, 'zh' => 12], boblift_series_joined_translations(['en' => 1, 'zh' => 2], ['en' => 11, 'zh' => 12], $ok));
$check('member keeps its own page where the series page is missing', ['en' => 11], boblift_series_joined_translations(['en' => 1], ['en' => 11, 'ja' => 21], $ok));
$check('member keeps its own page where the series page is a draft or protected', ['en' => 11], boblift_series_joined_translations(['en' => 1, 'zh' => 9], ['en' => 11, 'zh' => 12], $ok));
$check('an unavailable member translation is left out', ['en' => 11], boblift_series_joined_translations(['en' => 1, 'zh' => 2], ['en' => 11, 'zh' => 19], $ok));
// The redirect drops the parameters that picked the old page and keeps campaign tracking (#251 review).
$check('redirect drops routing parameters', 'https://x/s.html?model=A&utm_source=g&gclid=1', boblift_series_redirect_url('https://x/s.html?model=A', 'post_type=product&p=5&utm_source=g&page_id=3&name=x&lang=en&gclid=1&model=B'));
$check('redirect without extra parameters', 'https://x/s.html?model=A', boblift_series_redirect_url('https://x/s.html?model=A', 'p=5&post_type=product'));

// Search names: a real model always beats another configuration's former name (#251 review, round 2).
$names = boblift_series_search_names([
    ['model' => 'SQ5ZA3T', 'url' => 'S?model=SQ5ZA3T', 'aliases' => ['SQ5ZA4T', 'SQ5Z-OLD']],
    ['model' => 'SQ5ZA4T', 'url' => 'S?model=SQ5ZA4T', 'aliases' => []],
], 'optional', 'SQ5Z-T', 'S');
$check('a former name never takes a real model', 'S?model=SQ5ZA4T', $names['SQ5ZA4T']);
$check('a free former name opens its configuration', 'S?model=SQ5ZA3T', $names['SQ5Z-OLD']);
$check('the winch twin is a real name', 'S?model=SQ5ZA3TJ', $names['SQ5ZA3TJ']);
$check('the series code opens the series page', 'S', $names['SQ5Z-T']);
// Moments as the data writes them, in t·m (#251 review, round 2).
$check('moment Ton.m', '13 t·m', boblift_series_moment_normalized('13 Ton.m'));
$check('moment ton m', '13 t·m', boblift_series_moment_normalized('13 ton m'));
$check('moment bare number', '2.2 t·m', boblift_series_moment_normalized('2.2'));
$check('moment kN·m', '9.2 t·m', boblift_series_moment_normalized('90 kN·m'));
$check('moment already t·m', '2.2 t·m', boblift_series_moment_normalized('2.2 t·m'));
// Numbers in every language's style (#251 review, round 2).
// Comma-decimal languages: the separator followed by three digits groups thousands, otherwise it is the decimal mark.
foreach (['es', 'fr', 'ru'] as $lang) {
    $check("{$lang} decimal comma", 5.5, boblift_series_parse_number('5,5', $lang));
    $check("{$lang} decimal point", 5.5, boblift_series_parse_number('5.5', $lang));
    $check("{$lang} thousands comma", 6300.0, boblift_series_parse_number('6,300', $lang));
    $check("{$lang} thousands dot", 6300.0, boblift_series_parse_number('6.300', $lang));
    $check("{$lang} thousands narrow space", 12500.0, boblift_series_parse_number("12\u{202F}500", $lang));
    $check("{$lang} thousands nbsp", 12500.0, boblift_series_parse_number("12\u{00A0}500", $lang));
    $check("{$lang} thousands and decimals", 1250.5, boblift_series_parse_number('1,250.5', $lang));
    $check("{$lang} dot thousands and comma decimals", 1250.5, boblift_series_parse_number('1.250,5', $lang));
}
// English (the source data): the point is the decimal point, a comma groups thousands.
$check('en 4.125 m', 4.125, boblift_series_parse_number('4.125', 'en'));
$check('en 0.750 kW', 0.75, boblift_series_parse_number('0.750', 'en'));
$check('en 12,000 kg', 12000.0, boblift_series_parse_number('12,000', 'en'));
$check('en thousands and decimals', 1250.5, boblift_series_parse_number('1,250.5', 'en'));
$check('en narrow-space thousands', 12500.0, boblift_series_parse_number("12\u{202F}500", 'en'));
$check('en a comma is never a decimal mark', null, boblift_series_parse_number('5,5', 'en'));
$check('zh follows the English rule', 4.125, boblift_series_parse_number('4.125', 'zh'));
$check('not a number', null, boblift_series_parse_number('3+1', 'en'));
$check('en range of three-decimal figures', '4.125–4.5 m', boblift_series_compare_range(['4.125 m', '4.5 m'], true, 'en', 'en'));
$check('en kW range', '0.75–1.1 kW', boblift_series_compare_range(['0.750 kW', '1.1 kW'], true, 'en', 'en'));
$check('en thousands range', '1,464–3,020 mm', boblift_series_compare_range(['1,464 mm', '3,020 mm'], true, 'en', 'en'));
$check('en kg', '12,000 kg', boblift_series_compare_range(['12,000 kg'], true, 'en', 'en'));
foreach (['es', 'fr', 'ru'] as $lang) {
    $check("{$lang}: English moments make a range", '4,125–5,5 t·m', boblift_series_compare_range(['4.125 t·m', '5.5 t·m'], true, $lang, 'en'));
}
$check('English figures shown in Spanish', '4,125–4,5 m', boblift_series_compare_range(['4.125 m', '4.5 m'], true, 'es', 'en'));
$check('es range from decimal commas', '5,5–7,5 kW', boblift_series_compare_range(['5,5 kW', '7,5 kW'], false, 'es', 'es'));
$check('fr range from decimal commas', '5,5–7,5 kW', boblift_series_compare_range(['5,5 kW', '7,5 kW'], false, 'fr', 'fr'));
$check('ru range with thousands', '1,200–1,450 kg', boblift_series_compare_range(['1 200 kg', '1 450 kg'], false, 'ru', 'ru'));
$check('en range from English figures', '5.5–7.5 kW', boblift_series_compare_range(['5.5 kW', '7.5 kW'], true, 'en'));
$check('es range from English figures', '5,5–7,5 kW', boblift_series_compare_range(['5.5 kW', '7.5 kW'], true, 'es'));
$check('numbers only gives way on words', '', boblift_series_compare_range(['3+1', '4'], true, 'es'));
$check('words kept when not numbers only', '3+1 / 4', boblift_series_compare_range(['3+1', '4'], false, 'es'));

// One configuration has to meet every chosen filter (#251 review, round 3). Bins as the archive reads them: "a-b" is above a, up to b.
$in_bin = static function (float $number, string $bin): bool {
    [$min, $max] = array_pad(array_map('floatval', explode('-', $bin)), 2, null);
    return $max === null ? $number > $min : ($min <= 0 ? $number > 0 && $number <= $max : $number > $min && $number <= $max);
};
$configs = [['m' => 12.9, 'w' => 2100], ['m' => 13.2, 'w' => 1950]];
$check('moment from one configuration and weight from another do not match', false, boblift_filter_configs_match($configs, 6.3, ['moment' => ['0-13'], 'weight' => ['0-2000']], $in_bin));
$check('each figure alone matches', true, boblift_filter_configs_match($configs, 6.3, ['moment' => ['0-13']], $in_bin) && boblift_filter_configs_match($configs, 6.3, ['weight' => ['0-2000']], $in_bin));
$check('one configuration meeting both matches', true, boblift_filter_configs_match(array_merge($configs, [['m' => 12.5, 'w' => 1900]]), 6.3, ['moment' => ['0-13'], 'weight' => ['0-2000']], $in_bin));
$check('capacity is the listing figure', false, boblift_filter_configs_match($configs, 6.3, ['capacity' => ['8-12']], $in_bin));
$check('no ranges matches', true, boblift_filter_configs_match($configs, 6.3, ['moment' => [], 'weight' => []], $in_bin));

// Model names.
$check('sections and winch', ['named' => '', 'sections' => 'A4', 'winch' => '1', 'manual' => '0'], boblift_series_parse_model('SQ12ZA4TJ'));
$check('no winch', ['named' => '', 'sections' => 'A6', 'winch' => '0', 'manual' => '0'], boblift_series_parse_model('SQ12ZA6T'));
$check('manual extension', ['named' => '', 'sections' => 'A3', 'winch' => '1', 'manual' => '1'], boblift_series_parse_model('SQ08ZA3TJ+1'));
$check('decimal capacity, telescopic', ['named' => '', 'sections' => 'A3', 'winch' => '0', 'manual' => '0'], boblift_series_parse_model('SQ3.2SA3T'));
$check('BLZ uses K', ['named' => '', 'sections' => 'K5', 'winch' => '1', 'manual' => '0'], boblift_series_parse_model('BLZ90K5TJ'));
$check('SQZ is a choice of its own', ['named' => 'SQZ330T', 'sections' => '', 'winch' => '0', 'manual' => '0'], boblift_series_parse_model('SQZ330T'));
$check('SQZ with winch', ['named' => 'SQZ330T', 'sections' => '', 'winch' => '1', 'manual' => '0'], boblift_series_parse_model('SQZ330TJ'));
foreach (['SQ12ZA6T' => 'SQ12ZA6TJ', 'SQ8ZA6TJ' => 'SQ8ZA6T', 'SQ08ZA3T+1' => 'SQ08ZA3TJ+1', 'SQ08ZA3TJ+1' => 'SQ08ZA3T+1', 'BLZ90K5TJ' => 'BLZ90K5T', 'SQZ430T' => 'SQZ430TJ', 'SQ3.2SA4T' => 'SQ3.2SA4TJ'] as $model => $twin) {
    $check("winch twin of {$model}", $twin, boblift_series_winch_twin($model));
}

// Series names.
foreach (['SQ12ZA6T' => 'SQ12Z-T', 'SQ08ZA3TJ+1' => 'SQ08Z-T', 'SQ3.2SA4T' => 'SQ3.2S-T', 'BLZ90K5TJ' => 'BLZ90-T', 'SQZ500T' => 'SQZ500T', 'SQZ330T' => 'SQZ330T'] as $model => $code) {
    $check("code of {$model}", $code, boblift_series_code($model));
}

// Grouping: boom type and capacity, whatever the slug's own number says (the 1-ton page is rated 1.6 t).
$row = static fn(int $id, string $slug, string $model, float $capacity, string $boom, bool $legacy = false): array => compact('id', 'slug', 'model', 'capacity', 'boom', 'legacy');
$groups = boblift_series_group_rows([
    $row(193, '12-ton-knuckle-boom-marine-crane', 'SQ12ZA6T', 12, 'knuckle'),
    $row(4848, '12-ton-knuckle-boom-marine-crane-sq12za4t', 'SQ12ZA4T', 12, 'knuckle', true),
    $row(4851, '12-ton-knuckle-boom-marine-crane-sq12za4tj', 'SQ12ZA4TJ', 12, 'knuckle', true),
    $row(61, '12-ton-telescopic-boom-marine-crane', 'SQ12SA4T', 12, 'telescopic'),
    $row(4878, '12-ton-telescopic-boom-marine-crane-sq12sa5t', 'SQ12SA5T', 12, 'telescopic', true),
    $row(33, '1-ton-knuckle-boom-marine-crane', 'SQ1ZA3TJ+1', 1.6, 'knuckle'),
    $row(4806, '1-6-ton-knuckle-boom-marine-crane-sq1za3t1', 'SQ1ZA3T+1', 1.6, 'knuckle', true),
    $row(199, '25-ton-knuckle-boom-marine-crane', 'SQZ500T', 25, 'knuckle'),
    $row(9, 'no-capacity', 'SQ1ZA3T', 0, 'knuckle'),
]);
$check('series count', ['knuckle-1.6', 'knuckle-12', 'knuckle-25', 'telescopic-12'], array_keys($groups));
$check('home has no model suffix', 193, $groups['knuckle-12']['home']);
$check('1 ton page leads 1.6 t', 33, $groups['knuckle-1.6']['home']);
$check('members in model order', ['SQ12ZA4T', 'SQ12ZA4TJ', 'SQ12ZA6T'], array_column($groups['knuckle-12']['members'], 'model'));
$check('single-model series', [199], array_column($groups['knuckle-25']['members'], 'id'));
$check('series code from home', 'SQ12Z-T', $groups['knuckle-12']['code']);
$noSuffix = boblift_series_group_rows([$row(1, 'x-sq2za4t', 'SQ2ZA4T', 2, 'knuckle', true), $row(2, 'x-sq2za2tj', 'SQ2ZA2TJ', 2, 'knuckle')]);
$check('without a plain slug the current model leads', 2, $noSuffix['knuckle-2']['home']);
$legacyNamed = boblift_series_group_rows([$row(1, '5-ton-knuckle-boom-marine-crane', 'SQ5ZA3T', 5, 'knuckle', true), $row(2, '5-ton-knuckle-boom-marine-crane-sq5za4t', 'SQ5ZA4T', 5, 'knuckle')]);
$check('a previous-model capacity page gives way to a current member', 2, $legacyNamed['knuckle-5']['home']);
$allLegacy = boblift_series_group_rows([$row(1, 'x-sq6za4t', 'SQ6ZA4T', 6, 'knuckle', true), $row(2, '6-ton-knuckle-boom-marine-crane', 'SQ6ZA3T', 6, 'knuckle', true)]);
$check('all previous models: the capacity page still leads', 2, $allLegacy['knuckle-6']['home']);

// Saved picks (featured, related) of a configuration stand for its series page in that language, once each, in order.
$check('picked configuration becomes its series page', [193, 61, 7], boblift_series_listed_ids([4848, 61, 193, 4851, 7], [4848 => 193, 4851 => 193], [4848, 4851]));
$check('picked translation maps in its own language', [1193], boblift_series_listed_ids([14848], [14848 => 1193], [14848]));
$check('configuration without a series page is left out', [5], boblift_series_listed_ids([9, 5], [], [9]));

// Members whose names do not compare are chosen by model; the winch stays its own choice.
$named = static fn(array $models): array => boblift_series_unify_naming(array_map(static fn(string $model): array => ['model' => $model, 'values' => boblift_series_parse_model($model)], $models));
$mixed = $named(['SQ20ZA6', 'SQZ430']);
$check('A sections beside SQZ: asked by model', ['named' => ['SQ20ZA6', 'SQZ430']], boblift_series_dimensions($mixed));
$makes = $named(['SQ6.3ZA4', 'SQ6.3ZA5', 'BLZ129K4']);
$check('SQ beside BLZ: asked by model', ['named' => ['SQ6.3ZA4', 'SQ6.3ZA5', 'BLZ129K4']], boblift_series_dimensions($makes));
$winched = $named(['SQ20ZA6T', 'SQ20ZA6TJ', 'SQZ430T']);
$check('mixed names keep the winch apart', ['named' => ['SQ20ZA6T', 'SQZ430T'], 'winch' => ['0', '1']], boblift_series_dimensions($winched));
$check('one naming is left alone', ['sections' => ['A4', 'A5']], boblift_series_dimensions($named(['SQ6.3ZA4', 'SQ6.3ZA5'])));

// A series page is listed under every category of its configurations, its own first.
$check('category union', ['knuckle-boom-marine-cranes', 'pickup-cranes'], boblift_series_union_categories(['knuckle-boom-marine-cranes'], [['knuckle-boom-marine-cranes'], ['pickup-cranes']]));

// The translated spec row of an English label: by key, else by position only when the tables line up.
$enRows = [['label' => 'Max Lifting Capacity', 'value' => '6,300 kg'], ['label' => 'Self Weight', 'value' => '2,100 kg'], ['label' => 'Winch', 'value' => 'Yes']];
$aligned = static fn(array $local, array $english): bool => boblift_spec_rows_aligned($local, $english);
$check('aligned table: row at the English position', '自重', boblift_series_spec_label_rows($enRows, [['label' => '最大起重量', 'value' => '6300 kg'], ['label' => '自重', 'value' => '2100 kg'], ['label' => '卷扬', 'value' => '有']], 'Self Weight', $aligned));
$check('same row count, other order: no row picked', '', boblift_series_spec_label_rows($enRows, [['label' => '自重', 'value' => '2100 kg'], ['label' => '最大起重量', 'value' => '6300 kg'], ['label' => '卷扬', 'value' => '有']], 'Self Weight', $aligned));
$check('keys decide over position', '自重', boblift_series_spec_label_rows([['key' => 'cap', 'label' => 'Max Lifting Capacity', 'value' => '1'], ['key' => 'weight', 'label' => 'Self Weight', 'value' => '2']], [['key' => 'weight', 'label' => '自重', 'value' => '2'], ['key' => 'cap', 'label' => '最大起重量', 'value' => '1']], 'Self Weight', $aligned));
$check('row not in the English table', '', boblift_series_spec_label_rows($enRows, $enRows, 'Outrigger Span', $aligned));

// A category query finds a series by any configuration filed under it.
$check('category match on a configuration brings its series page', [193, 61], boblift_series_listed_ids([4851, 61], [4851 => 193, 4848 => 193], [4848, 4851]));
$check('tax_query by product category', true, boblift_series_query_has_category([['taxonomy' => 'product_category', 'terms' => [3]]]));
$check('nested tax_query by product category', true, boblift_series_query_has_category(['relation' => 'AND', [['taxonomy' => 'product_category', 'terms' => [3]]]]));
$check('other taxonomy', false, boblift_series_query_has_category([['taxonomy' => 'language', 'terms' => ['en']]]));

// Old configuration addresses, keyed by host and path as each language's home gives them.
$dirMap = [boblift_series_url_key('https://bobliftcrane.com/zh/5-ton-x-sq5za3.html', ['bobliftcrane.com'], 'bobliftcrane.com') => 'https://bobliftcrane.com/zh/5-ton-x.html?model=SQ5ZA3'];
$check('directory mode: relative link', 'https://bobliftcrane.com/zh/5-ton-x.html?model=SQ5ZA3', boblift_series_map_old_url('/zh/5-ton-x-sq5za3.html', $dirMap, ['bobliftcrane.com'], 'bobliftcrane.com'));
$check('directory mode: other language untouched', '/ja/5-ton-x-sq5za3.html', boblift_series_map_old_url('/ja/5-ton-x-sq5za3.html', $dirMap, ['bobliftcrane.com'], 'bobliftcrane.com'));
$check('directory mode: anchor and campaign kept', 'https://bobliftcrane.com/zh/5-ton-x.html?model=SQ5ZA3&utm_source=a#specs', boblift_series_map_old_url('https://www.bobliftcrane.com/zh/5-ton-x-sq5za3.html?utm_source=a#specs', $dirMap, ['bobliftcrane.com', 'www.bobliftcrane.com'], 'bobliftcrane.com'));
$subMap = [boblift_series_url_key('https://ja.example.com/5-ton-x-sq5za3.html', ['example.com'], 'example.com') => 'https://ja.example.com/5-ton-x.html?model=SQ5ZA3'];
$check('subdomain mode: language from the host', 'https://ja.example.com/5-ton-x.html?model=SQ5ZA3', boblift_series_map_old_url('https://ja.example.com/5-ton-x-sq5za3.html', $subMap, ['example.com'], 'example.com'));
$check('subdomain mode: main host is another language', 'https://example.com/5-ton-x-sq5za3.html', boblift_series_map_old_url('https://example.com/5-ton-x-sq5za3.html', $subMap, ['example.com'], 'example.com'));
$check('another site is never touched', null, boblift_series_url_key('https://other.org/5-ton-x-sq5za3.html', ['bobliftcrane.com'], 'bobliftcrane.com'));

// post__in replaces the category: what the query left out stays out (the product itself, previous models).
$check('related products leave the product itself out', [61, 7], boblift_series_without_ids([193, 61, 7], [193]));
$check('lifecycle exclusion still applies', [61], boblift_series_without_ids([4852, 61], [4852, 999]));
$check('nothing left matches nothing', [0], boblift_series_without_ids([193], [193]));

// A product in several grouped categories joins one family: pickup, then marine, then truck mounted.
$check('pickup before knuckle truck', [['id' => 5, 'slug' => 'x', 'category' => 'pickup-cranes']], boblift_series_one_family([['id' => 5, 'slug' => 'x', 'category' => 'knuckle-boom-truck-mounted-cranes'], ['id' => 5, 'slug' => 'x', 'category' => 'pickup-cranes']]));
$check('marine before truck', ['knuckle-boom-marine-cranes', 'telescopic-boom-truck-mounted-cranes'], array_column(boblift_series_one_family([['id' => 6, 'slug' => 'y', 'category' => 'knuckle-boom-truck-mounted-cranes'], ['id' => 6, 'slug' => 'y', 'category' => 'knuckle-boom-marine-cranes'], ['id' => 7, 'slug' => 'z', 'category' => 'telescopic-boom-truck-mounted-cranes']]), 'category'));

// A derived version's block: its model, no self weight, and its winch in words.
$block = '<p data-cfg-model-text>SQ12ZA6T</p><div data-cfg-weight-row><dt>Self Weight</dt><dd>4,350 kg</dd></div><div data-cfg-winch-row><dt>Winch</dt><dd>No</dd></div>';
$derived = boblift_series_derived_block($block, 'SQ12ZA6T', 'SQ12ZA6TJ', 'With winch');
$check('derived winch row says the version has a winch', true, str_contains($derived, '<dd data-cfg-orig="No"><span dir="auto">With winch</span></dd>'));
$check('derived weight row is open', true, str_contains($derived, '<dd data-cfg-orig="4,350 kg"><span class="text-slate-400">—</span></dd>'));
$check('derived model text', true, str_contains($derived, 'data-cfg-orig="SQ12ZA6T">SQ12ZA6TJ</p>'));
$check('winch words', ['With winch', 'No winch', ''], [boblift_series_winch_text('1'), boblift_series_winch_text('0'), boblift_series_winch_text('')]);

// Reach and height: the spec row first, under any of its names; the drawing only when the table has none.
$check('spec row wins over drawing', ['radius' => '10.15 m', 'height' => '12.3 m'], boblift_series_reach_figures(['Max Working Radius' => '10.15 m', 'Max Lifting Height' => '12.3 m'], ['radius' => '10.2 m', 'height' => '12 m']));
$check('alias names count as the row', ['radius' => '8 m', 'height' => '9 m'], boblift_series_reach_figures(['Max Working Range' => '8 m', 'Working Height' => '9 m'], ['radius' => '7.9 m']));
$check('drawing fills a missing row', ['radius' => '7.9 m', 'height' => null], boblift_series_reach_figures(['Max Lifting Height' => ''], ['radius' => '7.9 m']));

// A table whose self weight or winch row cannot be placed gets a note on a derived version.
$check('unplaced weight row', true, boblift_series_rows_unplaced(['Self Weight', 'Winch'], '', '卷扬'));
$check('unplaced winch row', true, boblift_series_rows_unplaced(['Self Weight', 'Winch'], '自重', ''));
$check('both placed', false, boblift_series_rows_unplaced(['Self Weight', 'Winch'], '自重', '卷扬'));
$check('no such rows in English', false, boblift_series_rows_unplaced(['Max Lifting Capacity'], '', ''));
$noted = boblift_series_derived_block('<p class="x" data-cfg-spec-note hidden></p><dl></dl>', 'SQ2ZA2TJ', 'SQ2ZA2T', 'No winch', 'No-winch version: self weight confirmed with your quote');
$check('derived block shows the note', true, str_contains($noted, '<p class="x" data-cfg-spec-note>No-winch version: self weight confirmed with your quote</p>'));

// Sections a configuration may lack: found in its captured blocks, judged for the configuration on screen.
$GLOBALS['boblift_series_render'] = ['series' => ['home_model' => 'A'], 'active' => 'B', 'active_source' => 'B', 'blocks' => ['B' => ['technical' => '<div id="drawing"></div>'], 'C' => ['features' => '<article id="features">']]];
$check('section in one configuration', true, boblift_series_section_in('features'));
$check('section not in the one on screen', false, boblift_series_section_shown('features', true));
$check('section in the one on screen', true, boblift_series_section_shown('drawing', false));
$GLOBALS['boblift_series_render']['active_source'] = 'A';
$check('the series page itself is judged by the template', true, boblift_series_section_shown('features', true));
unset($GLOBALS['boblift_series_render']);

// Which choices are asked.
$members = array_map(static fn(string $model): array => ['model' => $model, 'values' => boblift_series_parse_model($model)], ['SQ08ZA3T+1', 'SQ08ZA3TJ+1']);
$check('one-value dimensions are not asked', ['winch' => ['0', '1']], boblift_series_dimensions($members));
$check('+1 on every configuration', true, boblift_series_all_have($members, 'manual', '1'));
$check('winch not on every configuration', false, boblift_series_all_have($members, 'winch', '1'));
$sqz = array_map(static fn(string $model): array => ['model' => $model, 'values' => boblift_series_parse_model($model)], ['SQZ330T', 'SQZ350T']);
$check('SQZ is asked by model', ['named' => ['SQZ330T', 'SQZ350T']], boblift_series_dimensions($sqz));

// Redirect targets and ?model=.
$home = 'https://bobliftcrane.com/zh/12-ton-knuckle-boom-marine-crane.html';
$check('redirect target', $home . '?model=SQ12ZA4T', boblift_series_model_url($home, 'SQ12ZA4T'));
$check('+1 is encoded', 'https://bobliftcrane.com/0-8.html?model=SQ08ZA3T%2B1', boblift_series_model_url('https://bobliftcrane.com/0-8.html', 'SQ08ZA3T+1'));
$check('existing query string', 'https://x/y?a=1&model=SQ3.2ZA3T', boblift_series_model_url('https://x/y?a=1', 'SQ3.2ZA3T'));
$models = ['SQ08ZA3T+1', 'SQ08ZA3TJ+1'];
$check('?model= picks', 'SQ08ZA3T+1', boblift_series_requested_model($models, 'SQ08ZA3T+1', 'SQ08ZA3TJ+1'));
$check('a bare plus reads as a space', 'SQ08ZA3T+1', boblift_series_requested_model($models, 'SQ08ZA3T 1', 'SQ08ZA3TJ+1'));
$check('any letter case', 'SQ08ZA3T+1', boblift_series_requested_model($models, 'sq08za3t+1', 'SQ08ZA3TJ+1'));
$check('unknown model falls back', 'SQ08ZA3TJ+1', boblift_series_requested_model($models, 'SQ99', 'SQ08ZA3TJ+1'));

// Hidden configurations keep their ids back and ask for no image.
$hidden = boblift_series_hidden_block('<article id="technical" class="a"><img loading="eager" fetchpriority="high" src="x"><span id="details"></span></article>');
$check('hidden block', '<article data-vid="technical" class="a"><img loading="lazy" src="x"><span data-vid="details"></span></article>', $hidden);

// Figures and the words made from them.
$check('kilograms', 3950, boblift_series_kilograms('3950 kg'));
$check('no guess from other units', null, boblift_series_kilograms('4 t'));
$check('thousands', '12,000 kg', boblift_series_format_kg(12000));
$check('approximate height', ['≈ 16.5', 'm'], boblift_series_figure_parts('≈ 16.5 m'));
$figures = static fn(?int $weight, ?string $radius = null, ?string $height = null): array => ['weight' => $weight, 'capacity' => 12000, 'moment' => '27 t·m', 'radius' => $radius, 'height' => $height];
$check('the same five figures in the same order', ['capacity', 'moment', 'radius', 'height', 'weight'], array_column(boblift_series_hero_figures($figures(4350, '14.5 m', '≈ 16.5 m')), 'key'));
$check('also when some are missing', ['12,000 kg', '27 t·m', '—', '—', '3,900 kg'], array_column(boblift_series_hero_figures($figures(3900)), 'value'));
$twelve = [];
foreach (['SQ12ZA4T' => 3900, 'SQ12ZA4TJ' => 3950, 'SQ12ZA5T' => 4200, 'SQ12ZA5TJ' => 4400, 'SQ12ZA6T' => 4350] as $model => $weight) {
    $twelve[] = ['model' => $model, 'values' => boblift_series_parse_model($model), 'figures' => $figures($weight, $model === 'SQ12ZA6T' ? '14.5 m' : null)];
}
$dimensions = boblift_series_dimensions($twelve);
$a6 = $twelve[4]['values'];
$a4j = $twelve[1]['values'];
$twelve_all = boblift_series_expand_variants($twelve, 'https://x/12.html');
$check('every configuration gets the other winch version', ['SQ12ZA4T', 'SQ12ZA4TJ', 'SQ12ZA5T', 'SQ12ZA5TJ', 'SQ12ZA6T', 'SQ12ZA6TJ'], array_column($twelve_all, 'model'));
$derived = $twelve_all[5];
$check('derived from its configuration', [true, 'SQ12ZA6T', 'https://x/12.html?model=SQ12ZA6TJ', null, '14.5 m'], [$derived['derived'], $derived['source'], $derived['url'], $derived['figures']['weight'], $derived['figures']['radius']]);
$check('real configurations stay as they are', [false, 3950], [$twelve_all[1]['derived'], $twelve_all[1]['figures']['weight']]);
$all_dimensions = boblift_series_dimensions($twelve_all);
$check('with winch versions nothing is missing', [], boblift_series_unavailable_choices($twelve_all, $all_dimensions, $derived['values']));
$check('derived wording', 'Self weight' === '' ? '' : 'With winch (optional)', boblift_series_configuration_difference($derived, $twelve[4]));
$single = boblift_series_expand_variants([['model' => 'SQZ430T', 'url' => 'u', 'id' => 1, 'slug' => 's', 'is_home' => true, 'values' => boblift_series_parse_model('SQZ430T'), 'figures' => $figures(4900)]], 'https://x/20.html');
$check('a single model still has both', ['SQZ430T', 'SQZ430TJ'], array_column($single, 'model'));
$check('and asks only about the winch', ['winch' => ['0', '1']], boblift_series_dimensions($single));
$check('WhatsApp names the twin once', 'for%20SQ12ZA6TJ%3A%20u%3Fmodel%3DSQ12ZA6TJ', boblift_series_meta_for_twin(['whatsapp' => 'for%20SQ12ZA6T%3A%20u%3Fmodel%3DSQ12ZA6T'], 'SQ12ZA6T', 'SQ12ZA6TJ', 'u')['whatsapp']);
$check('the +1 in a WhatsApp address', 'SQ08ZA3TJ%2B1%20model%3DSQ08ZA3TJ%252B1', boblift_series_meta_for_twin(['whatsapp' => 'SQ08ZA3T%2B1%20model%3DSQ08ZA3T%252B1'], 'SQ08ZA3T+1', 'SQ08ZA3TJ+1', 'u')['whatsapp']);
$tele = array_map(static fn(string $model): array => ['model' => $model, 'url' => 'u', 'id' => 1, 'slug' => 's', 'is_home' => false, 'values' => boblift_series_winch_values(boblift_series_parse_model($model), 'standard'), 'figures' => $figures(4000)], ['SQ12SA4T', 'SQ12SA5T']);
$check('telescopic has no other winch version', ['SQ12SA4T', 'SQ12SA5T'], array_column(boblift_series_expand_variants($tele, 'https://x', 'standard'), 'model'));
$check('and is not asked about the winch', ['sections' => ['A4', 'A5']], boblift_series_dimensions(boblift_series_expand_variants($tele, 'https://x', 'standard')));
$check('telescopic always has it', '1', $tele[0]['values']['winch']);
$check('a telescopic name with J is the same crane', 'SQ10SA4T', boblift_series_normalize_requested('SQ10SA4TJ', 'standard'));
$check('a knuckle name with J stays', 'SQ12ZA6TJ', boblift_series_normalize_requested('SQ12ZA6TJ', 'optional'));
$check('knuckle keeps what the name says', '0', boblift_series_winch_values(boblift_series_parse_model('SQ12ZA6T'), 'optional')['winch']);
$sqz_figures = [['model' => 'SQZ330T', 'values' => boblift_series_parse_model('SQZ330T'), 'figures' => ['moment' => '34.8 t·m']], ['model' => 'SQZ350T', 'values' => boblift_series_parse_model('SQZ350T'), 'figures' => ['moment' => null]]];
$check('SQZ choice shows its moment', ['SQZ330', '34.8 t·m'], boblift_series_choice_label('named', 'SQZ330T', $sqz_figures));
$check('no moment, no figure', ['SQZ350', ''], boblift_series_choice_label('named', 'SQZ350T', $sqz_figures));
$check('an SQZ series is called by its models', 'SQZ330T / SQZ350T', boblift_series_label(['code' => 'SQZ330T', 'members' => $sqz_figures]));
// Truck mounted cranes share all of it, without a winch question.
$check('truck series code', ['SQ12Z', 'SQ3.2S', 'BLZ90', 'SQZ330'], array_map('boblift_series_code', ['SQ12ZA6', 'SQ3.2SA4', 'BLZ90K5', 'SQZ330']));
$check('truck model', ['named' => '', 'sections' => 'A6', 'winch' => '0', 'manual' => '0'], boblift_series_parse_model('SQ12ZA6'));
$check('truck model with +1', ['named' => '', 'sections' => 'A3', 'winch' => '0', 'manual' => '1'], boblift_series_parse_model('SQ1ZA3+1'));
$check('truck BLZ', ['named' => '', 'sections' => 'K5', 'winch' => '0', 'manual' => '0'], boblift_series_parse_model('BLZ90K5'));
$check('truck SQZ', ['named' => 'SQZ330', 'sections' => '', 'winch' => '0', 'manual' => '0'], boblift_series_parse_model('SQZ330'));
$check('winch modes', ['optional', 'standard', 'none', 'none'], [boblift_series_winch_mode('marine', 'knuckle'), boblift_series_winch_mode('marine', 'telescopic'), boblift_series_winch_mode('truck', 'knuckle'), boblift_series_winch_mode('truck', 'telescopic')]);
$truck_row = static fn(int $id, string $slug, string $model, float $capacity, string $boom, bool $legacy = false): array => compact('id', 'slug', 'model', 'capacity', 'boom', 'legacy') + ['family' => 'truck'];
$trucks = boblift_series_group_rows([
    $truck_row(1, '12-ton-knuckle-boom-truck-mounted-crane', 'SQ12ZA6', 12, 'knuckle'),
    $truck_row(2, '12-ton-knuckle-boom-truck-mounted-crane-sq12za4', 'SQ12ZA4', 12, 'knuckle', true),
    $row(3, '12-ton-knuckle-boom-marine-crane', 'SQ12ZA6T', 12, 'knuckle'),
    $truck_row(4, '6-3-ton-knuckle-boom-truck-mounted-crane', 'SQ6.3ZA5', 6.3, 'knuckle'),
    $truck_row(5, '6-3-ton-knuckle-boom-truck-mounted-crane-blz129k4', 'BLZ129K4', 6.3, 'knuckle'),
]);
$check('trucks and marine apart, another make in the same series', ['knuckle-12', 'truck-knuckle-6.3', 'truck-knuckle-12'], array_keys($trucks));
$check('truck home', 1, $trucks['truck-knuckle-12']['home']);
$check('the plain slug leads a series of two makes', 4, $trucks['truck-knuckle-6.3']['home']);
$check('another make comes after the SQ models', ['SQ6.3ZA5', 'BLZ129K4'], array_column($trucks['truck-knuckle-6.3']['members'], 'model'));
$two_makes = array_map(static fn(array $member): array => $member + ['values' => array_merge(boblift_series_parse_model($member['model']), ['named' => $member['model'], 'sections' => '']), 'figures' => ['moment' => '12.9 t·m']], [['model' => 'SQ6.3ZA4'], ['model' => 'BLZ129K4']]);
$check('two makes are asked by model, in order', ['named' => ['SQ6.3ZA4', 'BLZ129K4']], boblift_series_dimensions($two_makes));
$check('an SQ model says its booms', ['SQ6.3ZA4', '3 hydraulic booms'], boblift_series_choice_label('named', 'SQ6.3ZA4', $two_makes));
$check('a BLZ model says its moment', ['BLZ129K4', "Max lifting moment \u{2066}12.9 t·m\u{2069}"], boblift_series_choice_label('named', 'BLZ129K4', $two_makes));
$truck_variants = boblift_series_expand_variants(array_map(static fn(string $model): array => ['model' => $model, 'url' => 'u', 'id' => 1, 'slug' => 's', 'is_home' => false, 'values' => boblift_series_parse_model($model), 'figures' => $figures(1000)], ['SQ12ZA4', 'SQ12ZA6']), 'https://x', 'none');
$check('no models made up for trucks', ['SQ12ZA4', 'SQ12ZA6'], array_column($truck_variants, 'model'));
$check('no winch question for trucks', ['sections' => ['A4', 'A6']], boblift_series_dimensions($truck_variants));
$mixed = array_map(static fn(string $model): array => ['model' => $model, 'values' => boblift_series_parse_model($model), 'figures' => $figures(1000)], ['SQ1ZA3+1', 'SQ1ZA4']);
$check('+1 settled by the section is no choice', ['sections' => ['A3', 'A4']], boblift_series_dimensions($mixed));
$check('and does not make the other section look missing', [], boblift_series_unavailable_choices($mixed, boblift_series_dimensions($mixed), $mixed[0]['values']));
$check('and is told on the section', ['2 hydraulic booms +1', ''], boblift_series_choice_label('sections', 'A3', $mixed));
$check('not on the one without', ['3 hydraulic booms', ''], boblift_series_choice_label('sections', 'A4', $mixed));

// The two pickup cranes are one series of their own, chosen by their outriggers.
$pickups = boblift_series_group_rows([
    ['id' => 65, 'slug' => '800-kg-pickup-crane-with-outriggers', 'model' => 'SQ08ZA3+1', 'capacity' => 0.8, 'boom' => 'knuckle', 'legacy' => false, 'family' => 'pickup'],
    ['id' => 5164, 'slug' => '800-kg-pickup-crane-without-outriggers', 'model' => 'SQ08ZA3', 'capacity' => 0.8, 'boom' => 'knuckle', 'legacy' => false, 'family' => 'pickup'],
]);
$check('pickup series', ['pickup-knuckle-0.8'], array_keys($pickups));
$check('pickup page keeps its address', 65, $pickups['pickup-knuckle-0.8']['home']);
$check('pickup code', 'SQ08Z', $pickups['pickup-knuckle-0.8']['code']);
$pickup_members = array_map(static fn(array $row): array => $row + ['values' => boblift_series_parse_model($row['model']), 'figures' => $figures(300)], $pickups['pickup-knuckle-0.8']['members']);
$check('pickup asks about the outriggers', ['manual' => ['0', '1']], boblift_series_dimensions($pickup_members));
$check('in words', ['With outriggers', ''], boblift_series_choice_label('manual', '1', $pickup_members, 'pickup'));
$check('elsewhere +1 stays', ['+1', ''], boblift_series_choice_label('manual', '1', $pickup_members, 'truck'));
$check('pickup compare label', 'Without outriggers', boblift_series_compare_label($pickup_members[0]['values'], ['manual' => ['0', '1']], 'pickup'));
$check('an inquiry naming the other winch version', 'SQ12ZA6TJ', boblift_series_inquired_model('SQ12ZA6T', '12 Ton Knuckle Boom Marine Crane SQ12ZA6TJ'));
$check('an inquiry naming the model itself', 'SQ12ZA6T', boblift_series_inquired_model('SQ12ZA6T', '12 Ton Knuckle Boom Marine Crane SQ12ZA6T'));
$check('+1 in the inquiry text', 'SQ08ZA3TJ+1', boblift_series_inquired_model('SQ08ZA3T+1', 'Crane SQ08ZA3TJ+1'));
$check('old address keeps its tracking', 'https://x/y.html?model=A&utm_source=g&gclid=1', boblift_series_redirect_url('https://x/y.html?model=A', 'utm_source=g&model=B&gclid=1'));
$check('nothing to keep', 'https://x/y.html?model=A', boblift_series_redirect_url('https://x/y.html?model=A', ''));
$read = static fn(int $id): array => [1 => [['id' => 'a'], ['id' => 'b']], 2 => [['id' => 'b'], ['id' => 'c']]][$id];
$check('items of every configuration once', ['a', 'b', 'c'], array_column(boblift_series_related_items(['members' => [['id' => 1], ['id' => 2]]], $read), 'id'));
$check('quote details follow the twin', 'Crane SQ12ZA6TJ', boblift_series_meta_for_twin(['inquiry' => ['title' => 'Crane SQ12ZA6T']], 'SQ12ZA6T', 'SQ12ZA6TJ', 'u')['inquiry']['title']);
$check('winch weight', '+50 kg', boblift_series_winch_difference($twelve, $a4j));
$check('no pair, no difference', '', boblift_series_winch_difference($twelve, $a6));
$check('reach when the section has it', ['5 hydraulic booms', 'reach 14.5 m'], boblift_series_choice_label('sections', 'A6', $twelve));
$check('booms otherwise', ['3 hydraulic booms', ''], boblift_series_choice_label('sections', 'A4', $twelve));
$check('difference from the series model', "Self weight \u{2066}−400 kg\u{2069} · With winch", boblift_series_configuration_difference($twelve[1], $twelve[4]));
$check('compare label', 'A4 · With winch', boblift_series_compare_label($a4j, $dimensions));
$check('a figure only one configuration has stays out of the comparison', [['Self weight', 'weight']], boblift_series_compare_columns($twelve));
$with = fn(array $fig): array => ['model' => 'B', 'values' => boblift_series_parse_model('SQ08ZA3'), 'figures' => $fig];
$check('every figure that differs', "Max lifting moment \u{2066}−0.3 t·m\u{2069} · Max reach \u{2066}+1.5 m\u{2069} · Lifting height \u{2066}+2 m\u{2069} · Self weight \u{2066}−50 kg\u{2069}", boblift_series_configuration_difference($with(['weight' => 280, 'capacity' => 800, 'moment' => '1 t·m', 'radius' => '5.65 m', 'height' => '≈ 7.5 m']), ['model' => 'A', 'values' => boblift_series_parse_model('SQ08ZA3'), 'figures' => ['weight' => 330, 'capacity' => 800, 'moment' => '1.3 t·m', 'radius' => '4.15 m', 'height' => '≈ 5.5 m']]));
$check('a figure only one has is left out', "Self weight \u{2066}−50 kg\u{2069}", boblift_series_configuration_difference($with(['weight' => 280, 'capacity' => 800, 'moment' => null, 'radius' => null, 'height' => null]), ['model' => 'A', 'values' => boblift_series_parse_model('SQ08ZA3'), 'figures' => ['weight' => 330, 'capacity' => 800, 'moment' => null, 'radius' => '4.15 m', 'height' => '≈ 5.5 m']]));
$check('no difference from itself', '', boblift_series_configuration_difference($twelve[4], $twelve[4]));

$check('short title, telescopic', '2 Ton Telescopic Boom', boblift_series_short_title(2, 'telescopic'));
$check('short title, knuckle', '6.3 Ton Knuckle Boom', boblift_series_short_title(6.3, 'knuckle'));
$check('timber crane title', '1.3 Ton Timber Crane', boblift_series_timber_title(1.3, 'ZT120Z-2A Timber Crane'));
$check('timber crane with arm length', '0.7 Ton Timber Crane · 7.6 m', boblift_series_timber_title(0.7, 'ZM65: Arm Length 7600MM -Timber Crane'));
$check('and the other arm', '0.7 Ton Timber Crane · 7.67 m', boblift_series_timber_title(0.7, 'ZM75: Arm Length 7667MM -Timber Crane'));

$check('moment range', '12.9–13.2 t·m', boblift_series_compare_range(['13.2 t·m', '12.9 t·m', '13.2 t·m']));
$check('booms range', '3–5', boblift_series_compare_range(['3', '', '5', '4']));
$check('booms with +1 stay as written', '3+1 / 4', boblift_series_compare_range(['3+1', '4']));
$check('+1 alone stays as written', '3+1', boblift_series_compare_range(['3+1', '3+1']));
$check('weight with a thousands comma', '1,850–2,310 kg', boblift_series_compare_range(['2310 kg', '1850 kg']));
$check('height keeps approx', '≈ 10.5–14.5 m', boblift_series_compare_range(['≈ 10.5 m', '≈ 14.5 m']));
$check('rotation', '360–400°', boblift_series_compare_range(['400°', '360°']));
$check('one value', '360°', boblift_series_compare_range(['360°', '360°']));
$check('different units stay as written', '9 t·m / 90 kN·m', boblift_series_compare_range(['9 t·m', '90 kN·m']));
$check('nothing', '', boblift_series_compare_range(['', '']));
$check('kN·m converts', '9.2 t·m', boblift_series_moment_in_tm('90 kN·m'));
$check('t·m stays', '13.2 t·m', boblift_series_moment_in_tm('13.2 t·m'));

$check('sub-category short name', 'Knuckle Boom', boblift_series_subcategory_label('knuckle-boom-marine-cranes', 'Knuckle Boom Marine Cranes'));
$check('and with a language suffix', 'Telescopic Boom', boblift_series_subcategory_label('telescopic-boom-truck-mounted-cranes-ja', 'x'));
$check('others keep their name', 'Timber Cranes', boblift_series_subcategory_label('timber-cranes', 'Timber Cranes'));

$check('in the catalogue', 'Standard production model', boblift_series_status_text('SQ1ZA3+1', true));
$check('in the catalogue, not the base', 'Standard production model', boblift_series_status_text('SQ12ZA6', false));
$check('not in it, the base', 'Base configuration for this series', boblift_series_status_text('SQZ1000', true));
$check('not in it, not the base', '', boblift_series_status_text('SQ1ZA4', false));
$check('the other winch version is not the catalogued one', '', boblift_series_status_text('SQ4ZA3T', false));

echo $failures === 0 ? "ok\n" : "{$failures} failed\n";
exit($failures === 0 ? 0 : 1);
