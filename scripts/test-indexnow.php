<?php
// Checks the IndexNow helpers without WordPress: php scripts/test-indexnow.php
define('ABSPATH', __DIR__);
$GLOBALS['options'] = ['blog_public' => '1', 'home' => 'https://bobliftcrane.com'];
$GLOBALS['env'] = 'production';
class WP_Post
{
    public function __construct(public int $ID = 1, public string $post_type = 'post', public string $post_status = 'publish', public string $post_password = '')
    {
    }
}
function add_action(...$args): void
{
}
function get_option(string $name, $default = false)
{
    return $GLOBALS['options'][$name] ?? $default;
}
function add_option(string $name, $value, $deprecated = '', $autoload = null): bool
{
    if (array_key_exists($name, $GLOBALS['options'])) {
        return false;
    }
    $GLOBALS['options'][$name] = $value;
    return true;
}
function delete_option(string $name): bool
{
    unset($GLOBALS['options'][$name]);
    return true;
}
function wp_cache_delete(...$args): bool
{
    return true;
}
function wp_generate_password(int $length = 12, bool $special = true, bool $extra = false): string
{
    static $n = 0;
    $n++;
    return substr(str_repeat('AbC123xyz' . $n, 4), 0, $length);
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
$fixed = boblift_indexnow_key();
$check('a broken key is replaced', preg_match('/^[a-z0-9]{32}$/', $fixed) === 1);
$check('a key stored first wins', !add_option('boblift_indexnow_key', 'other') && boblift_indexnow_key() === $fixed);

$check('enabled on the live site', boblift_indexnow_enabled());
$GLOBALS['options']['home'] = 'https://bobliftcrane.com.';
$check('trailing DNS dot still the live site', boblift_indexnow_enabled());
foreach (['https://staging.bobliftcrane.com', 'http://localhost:8080', 'http://localhost.:8080', 'http://127.0.0.1:8080', 'http://[::1]:8080', 'https://site.test.'] as $home) {
    $GLOBALS['options']['home'] = $home;
    $check('quiet on ' . $home, !boblift_indexnow_enabled());
}
$GLOBALS['options']['home'] = 'https://bobliftcrane.com';
$GLOBALS['env'] = 'staging';
$check('quiet on a staging environment', !boblift_indexnow_enabled());
$GLOBALS['env'] = 'production';
$GLOBALS['options']['blog_public'] = '0';
$check('quiet when search engines are discouraged', !boblift_indexnow_enabled());
$GLOBALS['options']['blog_public'] = '1';

boblift_indexnow_queue_add(5);
boblift_indexnow_queue_add(5);
boblift_indexnow_queue_add(7);
$check('queue drops repeats', boblift_indexnow_queued_ids() === [5, 7]);

$check('published post qualifies', boblift_indexnow_post_qualifies(new WP_Post(1, 'post')));
$check('published product and case qualify', boblift_indexnow_post_qualifies(new WP_Post(1, 'product')) && boblift_indexnow_post_qualifies(new WP_Post(1, 'case')));
$check('feedback is left out', !boblift_indexnow_post_qualifies(new WP_Post(1, 'feedback')));
$check('password-protected post is left out', !boblift_indexnow_post_qualifies(new WP_Post(1, 'post', 'publish', 'secret')));
$check('unpublished later in the request is left out', !boblift_indexnow_post_qualifies(new WP_Post(1, 'post', 'draft')));
$check('missing post is left out', !boblift_indexnow_post_qualifies(null));

$urls = ['https://bobliftcrane.com/a.html', 'https://bobliftcrane.com/a.html', 'https://bobliftcrane.com/es/b.html', 'https://www.boblift.com/c.html'];
$payloads = boblift_indexnow_payloads($urls, 'https://bobliftcrane.com', 'abcd1234');
$check('one payload', count($payloads) === 1);
$check('payload host', ($payloads[0]['host'] ?? '') === 'bobliftcrane.com');
$check('payload key location', ($payloads[0]['keyLocation'] ?? '') === 'https://bobliftcrane.com/abcd1234.txt');
$check('payload keeps only this host, once each', ($payloads[0]['urlList'] ?? []) === ['https://bobliftcrane.com/a.html', 'https://bobliftcrane.com/es/b.html']);
$check('nothing to send for other hosts only', boblift_indexnow_payloads(['https://www.boblift.com/c.html'], 'https://bobliftcrane.com', 'abcd1234') === []);
$many = array_map(static fn(int $i): string => 'https://bobliftcrane.com/p' . $i . '.html', range(1, 10001));
$split = boblift_indexnow_payloads($many, 'https://bobliftcrane.com', 'abcd1234');
$check('10,001 URLs go out in two batches, none dropped', count($split) === 2 && count($split[0]['urlList']) === 10000 && $split[1]['urlList'] === ['https://bobliftcrane.com/p10001.html']);

exit($failures === 0 ? 0 : 1);
