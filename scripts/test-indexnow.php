<?php
// Checks the IndexNow helpers without WordPress: php scripts/test-indexnow.php
define('ABSPATH', __DIR__);
$GLOBALS['options'] = ['blog_public' => '1', 'home' => 'https://bobliftcrane.com'];
$GLOBALS['env'] = 'production';
function add_action(...$args): void
{
}
function get_option(string $name, $default = false)
{
    return $GLOBALS['options'][$name] ?? $default;
}
function update_option(string $name, $value, $autoload = null): bool
{
    $GLOBALS['options'][$name] = $value;
    return true;
}
function wp_generate_password(int $length = 12, bool $special = true, bool $extra = false): string
{
    return substr(str_repeat('AbC123xyz9', 4), 0, $length);
}
function wp_get_environment_type(): string
{
    return $GLOBALS['env'];
}
function wp_parse_url(string $url, int $component = -1)
{
    return parse_url($url, $component);
}
require dirname(__DIR__) . '/inc/indexnow.php';

$failures = 0;
$check = static function (string $name, bool $ok) use (&$failures): void {
    echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n";
    $failures += $ok ? 0 : 1;
};

$key = boblift_indexnow_key();
$check('key is made once and is valid', preg_match('/^[a-z0-9]{32}$/', $key) === 1 && boblift_indexnow_key() === $key);
$GLOBALS['options']['boblift_indexnow_key'] = 'bad key!';
$check('a broken key is replaced', preg_match('/^[a-z0-9]{32}$/', boblift_indexnow_key()) === 1);

$check('enabled on the public production site', boblift_indexnow_enabled());
$GLOBALS['env'] = 'staging';
$check('quiet on staging', !boblift_indexnow_enabled());
$GLOBALS['env'] = 'production';
$GLOBALS['options']['home'] = 'http://localhost:8080';
$check('quiet on localhost', !boblift_indexnow_enabled());
$GLOBALS['options']['home'] = 'http://127.0.0.1:8080';
$check('quiet on an IP address', !boblift_indexnow_enabled());
$GLOBALS['options']['home'] = 'https://bobliftcrane.com';
$GLOBALS['options']['blog_public'] = '0';
$check('quiet when search engines are discouraged', !boblift_indexnow_enabled());
$GLOBALS['options']['blog_public'] = '1';

boblift_indexnow_queue('https://bobliftcrane.com/a.html');
boblift_indexnow_queue('https://bobliftcrane.com/a.html');
boblift_indexnow_queue('https://bobliftcrane.com/es/b.html');
boblift_indexnow_queue('https://www.boblift.com/c.html');
$queue = boblift_indexnow_queue();
$check('queue drops repeats', count($queue) === 3);

$payload = boblift_indexnow_payload($queue, 'https://bobliftcrane.com', 'abcd1234');
$check('payload host', ($payload['host'] ?? '') === 'bobliftcrane.com');
$check('payload key location', ($payload['keyLocation'] ?? '') === 'https://bobliftcrane.com/abcd1234.txt');
$check('payload keeps only this host', ($payload['urlList'] ?? []) === ['https://bobliftcrane.com/a.html', 'https://bobliftcrane.com/es/b.html']);
$check('nothing to send for other hosts only', boblift_indexnow_payload(['https://www.boblift.com/c.html'], 'https://bobliftcrane.com', 'abcd1234') === null);

exit($failures === 0 ? 0 : 1);
