<?php
namespace WP_CLI\Utils {
    function get_flag_value(array $args, string $key, bool $default): bool
    {
        return $args[$key] ?? $default;
    }
}
namespace {
    require __DIR__ . '/transport-stubs.php';
    define('WP_CLI', true);
    class WP_CLI
    {
        public static array $output = [];
        public static array $commands = [];
        public static function add_command(string $name, string $class): void { self::$commands[$name] = $class; }
        public static function log(string $message): void { self::$output[] = $message; }
        public static function line(string $message): void { self::$output[] = $message; }
        public static function warning(string $message): void { self::$output[] = $message; }
        public static function success(string $message): void { self::$output[] = $message; }
        public static function error(string $message): never { throw new \RuntimeException($message); }
    }
    require __DIR__ . '/../includes/core.php';
    require __DIR__ . '/../includes/jobs.php';
    require __DIR__ . '/../includes/cli.php';
    require __DIR__ . '/../includes/transport.php';
    $checks = 0;
    function check(bool $condition, string $label): void
    {
        global $checks;
        if (!$condition) { throw new \RuntimeException($label); }
        $checks++;
    }
    $command = new BOBLIFT_MLS_CLI_Command();
    $command->export(['ja'], []);
    $package = json_decode(WP_CLI::$output[0], true);
    check($package['format'] === 1 && $package['language'] === 'ja', 'CLI stdout is package JSON');
    check(WP_CLI::$commands['boblift-mls'] === BOBLIFT_MLS_CLI_Command::class, 'CLI uses existing command group');
    $file = tempnam(sys_get_temp_dir(), 'boblift-transport-');
    try {
        $command->export(['ja'], ['file' => $file]);
        check(json_decode(file_get_contents($file), true)['format'] === 1, 'CLI writes export file');
        $package['options']['boblift_theme_options_ja'] = ['home' => ['title' => 'Japanese']];
        file_put_contents($file, json_encode($package));
        $writes = $GLOBALS['fixture']['writes'];
        $command->import([$file], ['dry-run' => true]);
        check($writes === $GLOBALS['fixture']['writes'], 'CLI dry-run does not write');
        $GLOBALS['fixture']['options']['boblift_theme_options_ja'] = ['home' => ['title' => 'Protected']];
        $command->import([$file], ['overwrite' => false, 'only' => 'options']);
        check(boblift_mls_get_raw_options('ja')['home']['title'] === 'Protected', 'CLI no-overwrite preserves existing options');
        $command->import([$file], ['publish' => true, 'only' => 'options']);
        check(boblift_mls_language_status('ja') === 'published' && boblift_mls_get_raw_options('ja')['home']['title'] === 'Japanese', 'CLI publish imports and publishes options');
        file_put_contents($file, '{');
        try {
            $command->import([$file], []);
            check(false, 'CLI invalid JSON must fail');
        } catch (\RuntimeException $error) {
            check(str_contains($error->getMessage(), 'JSON'), 'CLI rejects malformed JSON');
        }
    } finally {
        unlink($file);
    }
    echo 'OK: ' . $checks . " CLI transport checks\n";
}
