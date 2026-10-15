<?php
// php tools/legacy-redirects/test.php  — checks the resolver against the shipped rules without WordPress.
define('ABSPATH', __DIR__);
function add_action(...$args): void {}
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
    ['www.bobliftcrane.com', '/marine-crane-buying-guide', null], // disabled until the article is migrated
    ['www.bobliftcrane.com', '/Company-News.html', null],
    ['fr.bobliftcrane.com', '/', 'https://www.bobliftcrane.com/'],
    ['fr.bobliftcrane.com', '/Grue-camion-id46288356.html', 'https://www.bobliftcrane.com/news.html'],
    ['ru.bobliftcrane.com', '/%D0%9C%D0%B8%D0%BD%D0%B8-pl61907667.html', 'https://www.bobliftcrane.com/products.html'],
    ['es.bobliftcrane.com', '/products/Excavadora-compacta.html', 'https://www.bobliftcrane.com/products.html'],
    ['sa.bobliftcrane.com', '/Products-Marine-Cranes.html', 'https://www.bobliftcrane.com/marine-crane'],
    ['de.bobliftcrane.com', '/Products-Marine-Cranes.html', 'https://www.bobliftcrane.com/marine-crane'],
];
$fail = 0;
foreach ($cases as [$host, $path, $want]) {
    $got = boblift_legacy_redirect_resolve($host, $path, $config, $base);
    // de.* is not a legacy language host, so it only gets path rules.
    $ok = $got === $want;
    if (!$ok) { $fail++; }
    printf("%s %s%s -> %s%s\n", $ok ? 'ok  ' : 'FAIL', $host, $path, var_export($got, true), $ok ? '' : ' (wanted ' . var_export($want, true) . ')');
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
echo $fail ? "$fail FAILED\n" : "all passed\n";
exit($fail ? 1 : 0);
