<?php
// Checks the marine series grouping, model parsing and redirect targets without WordPress: php scripts/test-marine-series.php
define('ABSPATH', __DIR__);
function add_action(...$args): void
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
require __DIR__ . '/../inc/marine-series.php';

$failures = 0;
$check = static function (string $name, $expected, $actual) use (&$failures): void {
    if ($expected !== $actual) {
        $failures++;
        echo "FAIL {$name}: expected " . json_encode($expected) . ', got ' . json_encode($actual) . "\n";
    }
};

// Model names.
$check('sections and winch', ['named' => '', 'sections' => 'A4', 'winch' => '1', 'manual' => '0'], boblift_marine_parse_model('SQ12ZA4TJ'));
$check('no winch', ['named' => '', 'sections' => 'A6', 'winch' => '0', 'manual' => '0'], boblift_marine_parse_model('SQ12ZA6T'));
$check('manual extension', ['named' => '', 'sections' => 'A3', 'winch' => '1', 'manual' => '1'], boblift_marine_parse_model('SQ08ZA3TJ+1'));
$check('decimal capacity, telescopic', ['named' => '', 'sections' => 'A3', 'winch' => '0', 'manual' => '0'], boblift_marine_parse_model('SQ3.2SA3T'));
$check('BLZ uses K', ['named' => '', 'sections' => 'K5', 'winch' => '1', 'manual' => '0'], boblift_marine_parse_model('BLZ90K5TJ'));
$check('SQZ is a choice of its own', ['named' => 'SQZ330T', 'sections' => '', 'winch' => '', 'manual' => ''], boblift_marine_parse_model('SQZ330T'));

// Series names.
foreach (['SQ12ZA6T' => 'SQ12Z-T', 'SQ08ZA3TJ+1' => 'SQ08Z-T', 'SQ3.2SA4T' => 'SQ3.2S-T', 'BLZ90K5TJ' => 'BLZ90-T', 'SQZ500T' => 'SQZ-T'] as $model => $code) {
    $check("code of {$model}", $code, boblift_marine_series_code($model));
}

// Grouping: boom type and capacity, whatever the slug's own number says (the 1-ton page is rated 1.6 t).
$row = static fn(int $id, string $slug, string $model, float $capacity, string $boom, bool $legacy = false): array => compact('id', 'slug', 'model', 'capacity', 'boom', 'legacy');
$groups = boblift_marine_group_rows([
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
$noSuffix = boblift_marine_group_rows([$row(1, 'x-sq2za4t', 'SQ2ZA4T', 2, 'knuckle', true), $row(2, 'x-sq2za2tj', 'SQ2ZA2TJ', 2, 'knuckle')]);
$check('without a plain slug the current model leads', 2, $noSuffix['knuckle-2']['home']);

// Which choices are asked.
$members = array_map(static fn(string $model): array => ['model' => $model, 'values' => boblift_marine_parse_model($model)], ['SQ08ZA3T+1', 'SQ08ZA3TJ+1']);
$check('one-value dimensions are not asked', ['winch' => ['0', '1']], boblift_marine_series_dimensions($members));
$check('+1 on every configuration', true, boblift_marine_series_all_have($members, 'manual', '1'));
$check('winch not on every configuration', false, boblift_marine_series_all_have($members, 'winch', '1'));
$sqz = array_map(static fn(string $model): array => ['model' => $model, 'values' => boblift_marine_parse_model($model)], ['SQZ330T', 'SQZ350T']);
$check('SQZ is asked by model', ['named' => ['SQZ330T', 'SQZ350T']], boblift_marine_series_dimensions($sqz));

// Redirect targets and ?model=.
$home = 'https://bobliftcrane.com/zh/12-ton-knuckle-boom-marine-crane.html';
$check('redirect target', $home . '?model=SQ12ZA4T', boblift_marine_model_url($home, 'SQ12ZA4T'));
$check('+1 is encoded', 'https://bobliftcrane.com/0-8.html?model=SQ08ZA3T%2B1', boblift_marine_model_url('https://bobliftcrane.com/0-8.html', 'SQ08ZA3T+1'));
$check('existing query string', 'https://x/y?a=1&model=SQ3.2ZA3T', boblift_marine_model_url('https://x/y?a=1', 'SQ3.2ZA3T'));
$models = ['SQ08ZA3T+1', 'SQ08ZA3TJ+1'];
$check('?model= picks', 'SQ08ZA3T+1', boblift_marine_requested_model($models, 'SQ08ZA3T+1', 'SQ08ZA3TJ+1'));
$check('a bare plus reads as a space', 'SQ08ZA3T+1', boblift_marine_requested_model($models, 'SQ08ZA3T 1', 'SQ08ZA3TJ+1'));
$check('any letter case', 'SQ08ZA3T+1', boblift_marine_requested_model($models, 'sq08za3t+1', 'SQ08ZA3TJ+1'));
$check('unknown model falls back', 'SQ08ZA3TJ+1', boblift_marine_requested_model($models, 'SQ99', 'SQ08ZA3TJ+1'));

// Hidden configurations keep their ids back and ask for no image.
$hidden = boblift_marine_hidden_block('<article id="technical" class="a"><img loading="eager" fetchpriority="high" src="x"><span id="details"></span></article>');
$check('hidden block', '<article data-vid="technical" class="a"><img loading="lazy" src="x"><span data-vid="details"></span></article>', $hidden);

// Figures and the words made from them.
$check('kilograms', 3950, boblift_marine_kilograms('3950 kg'));
$check('no guess from other units', null, boblift_marine_kilograms('4 t'));
$check('thousands', '12,000 kg', boblift_marine_format_kg(12000));
$check('approximate height', ['≈ 16.5', 'm'], boblift_marine_figure_parts('≈ 16.5 m'));
$figures = static fn(?int $weight, ?string $radius = null, ?string $height = null): array => ['weight' => $weight, 'capacity' => 12000, 'moment' => '27 t·m', 'radius' => $radius, 'height' => $height];
$check('changing figures first', ['weight', 'radius', 'height', 'capacity'], array_column(boblift_marine_hero_figures($figures(4350, '14.5 m', '≈ 16.5 m')), 'key'));
$check('only figures the data has', ['weight', 'capacity', 'moment'], array_column(boblift_marine_hero_figures($figures(3900)), 'key'));
$twelve = [];
foreach (['SQ12ZA4T' => 3900, 'SQ12ZA4TJ' => 3950, 'SQ12ZA5T' => 4200, 'SQ12ZA5TJ' => 4400, 'SQ12ZA6T' => 4350] as $model => $weight) {
    $twelve[] = ['model' => $model, 'values' => boblift_marine_parse_model($model), 'figures' => $figures($weight, $model === 'SQ12ZA6T' ? '14.5 m' : null)];
}
$dimensions = boblift_marine_series_dimensions($twelve);
$a6 = $twelve[4]['values'];
$a4j = $twelve[1]['values'];
$check('no J on A6', ['winch|1'], boblift_marine_unavailable_choices($twelve, $dimensions, $a6));
$check('A6 with winch does not exist', ['sections|A6'], boblift_marine_unavailable_choices($twelve, $dimensions, $a4j));
$check('note under winch', ['winch' => '5 hydraulic booms: without winch only'], boblift_marine_combination_notes($twelve, $dimensions, $a6));
$check('note under sections', ['sections' => '5 hydraulic booms: without winch only'], boblift_marine_combination_notes($twelve, $dimensions, $a4j));
$check('winch weight', '+50 kg', boblift_marine_winch_difference($twelve, $a4j));
$check('no pair, no difference', '', boblift_marine_winch_difference($twelve, $a6));
$check('reach when the section has it', ['5 hydraulic booms', 'reach 14.5 m'], boblift_marine_choice_label('sections', 'A6', $twelve));
$check('booms otherwise', ['3 hydraulic booms', ''], boblift_marine_choice_label('sections', 'A4', $twelve));
$check('difference from the series model', "Self weight \u{2066}−400 kg\u{2069} · With winch", boblift_marine_configuration_difference($twelve[1], $twelve[4]));
$check('compare label', 'A4 · With winch', boblift_marine_compare_label($a4j, $dimensions));
$check('a figure only one configuration has stays out of the comparison', [['Self weight', 'weight']], boblift_marine_compare_columns($twelve));
$check('no difference from itself', '', boblift_marine_configuration_difference($twelve[4], $twelve[4]));

echo $failures === 0 ? "ok\n" : "{$failures} failed\n";
exit($failures === 0 ? 0 : 1);
