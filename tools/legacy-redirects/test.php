<?php
// php tools/legacy-redirects/test.php  — checks the resolver against the shipped rules without WordPress.
define('ABSPATH', __DIR__);
function get_template_directory(): string { return dirname(__DIR__, 2); }
function add_action(...$args): void {}
function get_option(string $name, $default = false) { return $GLOBALS['saved_option'] ?? $default; }
require __DIR__ . '/../../inc/legacy-redirects.php';

$config = json_decode((string) file_get_contents(__DIR__ . '/../../inc/legacy-redirects.json'), true);
$base = 'https://www.bobliftcrane.com';
$cases = [
    ['www.bobliftcrane.com', '/Products-Marine-Cranes.html', 'https://www.bobliftcrane.com/marine-crane'],
    ['www.bobliftcrane.com', '/products-marine-cranes.html/', 'https://www.bobliftcrane.com/marine-crane'],
    ['www.bobliftcrane.com', '/5-ton-mini-dumper.html', 'https://bobliftloader.com/product/5-ton-mini-dumper/'],
    ['www.bobliftcrane.com', '/BL-750-Mini-Skid-Steer-Loader-pd174998538.html', 'https://bobliftloader.com/product/bl-t750-h-mini-skid-steer-loader/'],
    ['www.bobliftcrane.com', '/Truck-Mounted-35-Meter-Aerial-Work-Platform-pd517525938.html', 'https://www.bobliftcrane.com/truck-mounted-35-meter-aerial-work-platform-pd517525938.html'],
    ['www.bobliftcrane.com', '/truck-mounted-35-meter-aerial-work-platform-pd517525938.html', null],
    ['www.bobliftcrane.com', '/about.html', null],
    ['www.bobliftcrane.com', '/', null],
    ['www.bobliftcrane.com', '/marine-crane-buying-guide', 'https://www.bobliftcrane.com/marine-crane-buying-guide.html'], // article imported by tools/content-gap
    ['www.bobliftcrane.com', '/5-ton-crane', 'https://www.bobliftcrane.com/products.html?capacity=4-5-tons'], // tonnage filter (#7)
    ['www.bobliftcrane.com', '/Company-News.html', null],
    ['fr.bobliftcrane.com', '/', 'https://www.bobliftcrane.com/'],
    ['fr.bobliftcrane.com', '/Grue-camion-id46288356.html', 'https://www.bobliftcrane.com/news.html'],
    ['fr.bobliftcrane.com', '/Grue-camion-id46288356.html/', 'https://www.bobliftcrane.com/news.html'],
    ['ru.bobliftcrane.com', '/%D0%9C%D0%B8%D0%BD%D0%B8-pl61907667.html', 'https://www.bobliftcrane.com/products.html'],
    ['es.bobliftcrane.com', '/products/Excavadora-compacta.html', 'https://www.bobliftcrane.com/products.html'],
    ['sa.bobliftcrane.com', '/Products-Marine-Cranes.html', 'https://www.bobliftcrane.com/marine-crane'],
    ['fr.bobliftcrane.com', '/1-ton-knuckle-boom-truck-mounted-crane.html', 'https://www.bobliftcrane.com/1-ton-knuckle-boom-truck-mounted-crane.html'],
    ['fr.bobliftcrane.com', '/Company-News.html', 'https://www.bobliftcrane.com/Company-News.html'],
    ['fr.bobliftcrane.com', '/real-product-pd123456.html', 'https://www.bobliftcrane.com/real-product-pd123456.html'],
    ['fr.bobliftcrane.com', '/a-propos.html', 'https://www.bobliftcrane.com/'], // ASCII translated slug with no English page
    ['fr.bobliftcrane.com', '/some/deep/path', 'https://www.bobliftcrane.com/'],
    ['de.bobliftcrane.com', '/Products-Marine-Cranes.html', 'https://www.bobliftcrane.com/marine-crane'],
];
$fail = 0;
foreach ($cases as [$host, $path, $want]) {
    $got = boblift_legacy_redirect_resolve($host, $path, $config, $base, static fn(string $p): bool => in_array($p, ['/1-ton-knuckle-boom-truck-mounted-crane.html', '/Company-News.html', '/real-product-pd123456.html'], true));
    // de.* is not a legacy language host, so it only gets path rules.
    $ok = $got === $want;
    if (!$ok) { $fail++; }
    printf("%s %s%s -> %s%s\n", $ok ? 'ok  ' : 'FAIL', $host, $path, var_export($got, true), $ok ? '' : ' (wanted ' . var_export($want, true) . ')');
}
// A language directory is used only once it really serves the page.
$live_ru = static fn(string $p): bool => in_array($p, ['/ru', '/ru/news.html'], true);
foreach ([
    ['ru.bobliftcrane.com', '/', 'https://www.bobliftcrane.com/ru/'],
    ['ru.bobliftcrane.com', '/x-id1.html', 'https://www.bobliftcrane.com/ru/news.html'],
    ['ru.bobliftcrane.com', '/x-pd1.html', 'https://www.bobliftcrane.com/products.html'],
    ['fr.bobliftcrane.com', '/x-id1.html', 'https://www.bobliftcrane.com/news.html'],
] as [$host, $path, $want]) {
    $got = boblift_legacy_redirect_resolve($host, $path, $config, $base, $live_ru);
    if ($got !== $want) { $fail++; echo "FAIL $host$path -> " . var_export($got, true) . " (wanted $want)\n"; } else { echo "ok   $host$path -> $got\n"; }
}
// every enabled rule must resolve to itself and must not point at a path that is itself a rule source (no chains)
$sources = [];
foreach ($config['rules'] as $r) { $sources[boblift_legacy_redirect_normalize_path($r['from'])] = true; }
foreach ($config['rules'] as $r) {
    if (($r['enabled'] ?? true) === false) { continue; }
    $got = boblift_legacy_redirect_resolve('www.bobliftcrane.com', $r['from'], $config, $base);
    if ($got === null && rtrim($r['from'], '/') !== rtrim($r['to'], '/')) { echo "FAIL rule not applied: {$r['from']}\n"; $fail++; }
    if ($r['to'][0] === '/' && isset($sources[boblift_legacy_redirect_normalize_path($r['to'])]) && boblift_legacy_redirect_normalize_path($r['to']) !== boblift_legacy_redirect_normalize_path($r['from'])) { echo "FAIL chain: {$r['from']} -> {$r['to']}\n"; $fail++; }
}

$eff = [
    ['www.bobliftcrane.com', ['legacy_lang' => 'fr'], 'fr.bobliftcrane.com'],
    ['www.bobliftcrane.com', ['legacy_lang' => 'de'], 'www.bobliftcrane.com'],
    ['www.bobliftcrane.com', [], 'www.bobliftcrane.com'],
];
foreach ($eff as [$h, $q, $want]) {
    if (boblift_legacy_redirect_effective_host($h, $q, $config) !== $want) { echo "FAIL effective host for " . json_encode($q) . "\n"; $fail++; }
}
foreach ([
    ['/a.html', ['utm_source' => 'x'], '/a.html?utm_source=x'],
    ['/a.html?category=marine', ['gclid' => '1'], '/a.html?category=marine&gclid=1'],
    ['/a.html?category=marine#top', ['gclid' => '1'], '/a.html?category=marine&gclid=1#top'],
    ['/a.html#top', [], '/a.html#top'],
] as [$dest, $in, $want]) {
    $got = boblift_legacy_redirect_merge_query($dest, $in);
    if ($got !== $want) { echo "FAIL merge $dest -> $got\n"; $fail++; }
}
$cycle = boblift_legacy_redirects_find_cycle([['from' => '/marine-crane', 'to' => '/Products-Marine-Cranes.html'], ['from' => '/Products-Marine-Cranes.html', 'to' => '/marine-crane']]);
if ($cycle === null) { echo "FAIL cycle not detected\n"; $fail++; }
if (boblift_legacy_redirects_find_cycle($config['rules']) !== null) { echo "FAIL shipped rules contain a cycle\n"; $fail++; }
if (boblift_legacy_redirects_find_cycle([['from' => '/old-a', 'to' => 'https://www.bobliftcrane.com/old-b'], ['from' => '/old-b', 'to' => '/old-a']], ['www.bobliftcrane.com']) === null) { echo "FAIL absolute internal cycle not detected\n"; $fail++; }
if (boblift_legacy_redirects_find_cycle([['from' => '/old-a', 'to' => 'https://bobliftloader.com/old-b'], ['from' => '/old-b', 'to' => '/old-a']], ['www.bobliftcrane.com']) !== null) { echo "FAIL external target treated as cycle\n"; $fail++; }
// An absolute lowercase target must not loop on its own rule; the mixed-case URL still redirects.
$selfcfg = ['rules' => [['from' => '/about.html', 'to' => 'https://www.bobliftcrane.com/about.html'], ['from' => '/Example.html', 'to' => 'https://www.bobliftcrane.com/example.html'], ['from' => '/Q.html', 'to' => '/q.html?x=1']]];
foreach ([['/example.html', null], ['/Example.html', 'https://www.bobliftcrane.com/example.html'], ['/q.html', null], ['/about.html', null]] as [$pth, $want]) {
    $got = boblift_legacy_redirect_resolve('www.bobliftcrane.com', $pth, $selfcfg, $base);
    if ($got !== $want) { echo "FAIL self-redirect $pth -> " . var_export($got, true) . "\n"; $fail++; }
}
// An explicitly saved empty rule set is honoured; no option means the shipped rules.
$GLOBALS['saved_option'] = ['rules' => []];
if (!boblift_legacy_redirects_has_custom() || boblift_legacy_redirects_config()['rules'] !== []) { echo "FAIL empty saved rules were not honoured\n"; $fail++; }
unset($GLOBALS['saved_option']);
if (boblift_legacy_redirects_has_custom()) { echo "FAIL absent option reported as custom\n"; $fail++; }
echo $fail ? "$fail FAILED\n" : "effective-host ok\n";
exit($fail ? 1 : 0);
