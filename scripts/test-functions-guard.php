<?php
// php scripts/test-functions-guard.php — checks functions.php answers 503 (not a fatal) while a module is missing.
$root = dirname(__DIR__);
$tmp = sys_get_temp_dir() . '/boblift-guard-' . getmypid();
@mkdir($tmp . '/inc', 0777, true);
copy($root . '/functions.php', $tmp . '/functions.php');
preg_match_all("/^    '([a-z0-9-]+\\.php)',$/m", file_get_contents($root . '/functions.php'), $m);
$modules = $m[1];
$fail = 0;
$check = function (string $name, bool $ok) use (&$fail) { echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n"; if (!$ok) { $fail++; } };
$check('module list parsed (' . count($modules) . ')', count($modules) > 40);
foreach ($modules as $i => $f) {
    if ($f !== 'category-root-route.php') {
        file_put_contents($tmp . '/inc/' . $f, "<?php \$GLOBALS['loaded'][] = '$f';\n");
    }
}
$runner = $tmp . '/run.php';
file_put_contents($runner, "<?php define('ABSPATH', '/'); function get_template_directory() { return '$tmp'; } include '$tmp/functions.php'; echo \"\\nLOADED:\" . count(\$GLOBALS['loaded'] ?? []);\n");
$cli = shell_exec('php ' . escapeshellarg($runner) . ' 2>&1') ?? '';
$check('missing module: no fatal', strpos($cli, 'Fatal') === false);
$check('missing module: update notice shown', strpos($cli, 'The site is updating') !== false);
$check('missing module: nothing loaded', strpos($cli, 'LOADED:') === false);
file_put_contents($tmp . '/inc/category-root-route.php', "<?php \$GLOBALS['loaded'][] = 'category-root-route.php';\n");
$cli = shell_exec('php ' . escapeshellarg($runner) . ' 2>&1') ?? '';
$check('all modules present: all loaded in order', strpos($cli, 'LOADED:' . count($modules)) !== false && strpos($cli, 'updating') === false);
array_map('unlink', glob($tmp . '/inc/*'));
rmdir($tmp . '/inc');
array_map('unlink', glob($tmp . '/*'));
rmdir($tmp);
echo $fail ? "$fail failed\n" : "all passed\n";
exit($fail ? 1 : 0);
