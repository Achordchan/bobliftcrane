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
// Just enough of $wpdb for the key's INSERT IGNORE and compare-and-swap UPDATE, backed by the same options array.
class Fake_Wpdb
{
    public string $options = 'wp_options';
    public function prepare(string $sql, ...$args): array
    {
        return [$sql, $args];
    }
    // Like wpdb: get_var() gives null for a missing row and for an empty value alike.
    public function get_var(array $q)
    {
        $value = $GLOBALS['options'][$q[1][0]] ?? null;
        return $value === '' ? null : $value;
    }
    public function get_row(array $q, string $output = 'OBJECT')
    {
        return array_key_exists($q[1][0], $GLOBALS['options']) ? ['option_value' => (string) $GLOBALS['options'][$q[1][0]]] : null;
    }
    public function query(array $q): int
    {
        [$sql, $args] = $q;
        if (str_starts_with($sql, 'INSERT IGNORE')) {
            if (array_key_exists($args[0], $GLOBALS['options'])) {
                return 0;
            }
            $GLOBALS['options'][$args[0]] = $args[1];
            return 1;
        }
        if (($GLOBALS['options'][$args[1]] ?? null) === $args[2]) {
            $GLOBALS['options'][$args[1]] = $args[0];
            return 1;
        }
        return 0;
    }
}
const ARRAY_A = 'ARRAY_A';
$GLOBALS['wpdb'] = new Fake_Wpdb();
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
const MINUTE_IN_SECONDS = 60;
class WP_Error
{
}
$GLOBALS['remote_fails'] = false;
$GLOBALS['sent'] = [];
$GLOBALS['scheduled'] = [];
$GLOBALS['posts'] = [];
function get_post(int $id)
{
    return $GLOBALS['posts'][$id] ?? null;
}
function get_permalink(WP_Post $post): string
{
    return 'https://bobliftcrane.com/p' . $post->ID . '.html';
}
function get_current_blog_id(): int
{
    return 1;
}
function is_multisite(): bool
{
    return false;
}
function is_wp_error($thing): bool
{
    return $thing instanceof WP_Error;
}
function wp_remote_post(string $url, array $args)
{
    $GLOBALS['sent'][] = json_decode($args['body'], true)['urlList'];
    return $GLOBALS['remote_fails'] ? new WP_Error() : [];
}
function wp_json_encode($data, int $flags = 0)
{
    return json_encode($data, $flags);
}
function update_option(string $name, $value, $autoload = null): bool
{
    $GLOBALS['options'][$name] = $value;
    return true;
}
function wp_schedule_single_event(int $time, string $hook, array $args = []): bool
{
    $GLOBALS['scheduled'][] = $args;
    return true;
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
$GLOBALS['options']['boblift_indexnow_key'] = '';
$check('an empty stored key is repaired', preg_match('/^[a-z0-9]{32}$/', boblift_indexnow_key()) === 1);
unset($GLOBALS['options']['boblift_indexnow_key']);
$GLOBALS['options']['boblift_indexnow_key'] = 'winner-key-1234';
$check('a key another request stored is kept', boblift_indexnow_key() === 'winner-key-1234');

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
$GLOBALS['options']['blog_public'] = '-1';
$check('quiet on a private WordPress.com site', !boblift_indexnow_enabled());
$GLOBALS['options']['blog_public'] = '1';

boblift_indexnow_queue_add(5);
boblift_indexnow_queue_add(5);
boblift_indexnow_queue_add(7);
boblift_indexnow_queue_add(9, 3);
$check('queue drops repeats and keeps the blog', boblift_indexnow_queued_ids() === [1 => [5, 7], 3 => [9]]);

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
$check('nothing to send without a valid key', boblift_indexnow_payloads($urls, 'https://bobliftcrane.com', '') === []);
$check('nothing to send for other hosts only', boblift_indexnow_payloads(['https://www.boblift.com/c.html'], 'https://bobliftcrane.com', 'abcd1234') === []);
$many = array_map(static fn(int $i): string => 'https://bobliftcrane.com/p' . $i . '.html', range(1, 10001));
$split = boblift_indexnow_payloads($many, 'https://bobliftcrane.com', 'abcd1234');
$check('10,001 URLs go out in two batches, none dropped', count($split) === 2 && count($split[0]['urlList']) === 10000 && $split[1]['urlList'] === ['https://bobliftcrane.com/p10001.html']);

$GLOBALS['options']['boblift_indexnow_key'] = 'abcd1234';
$GLOBALS['posts'] = [11 => new WP_Post(11), 12 => new WP_Post(12), 13 => new WP_Post(13, 'post', 'private')];
$GLOBALS['sent'] = [];
boblift_indexnow_send([11, 13, 99]);
$check('only still-public posts are sent', $GLOBALS['sent'] === [['https://bobliftcrane.com/p11.html']]);
$GLOBALS['remote_fails'] = true;
boblift_indexnow_send([11]);
boblift_indexnow_send([12]);
$check('each failed dispatch gets its own retry event with its post IDs', $GLOBALS['scheduled'] === [[1, [11]], [1, [12]]]);
$GLOBALS['scheduled'] = [];
$GLOBALS['posts'][11]->post_password = 'secret';
$GLOBALS['remote_fails'] = false;
$GLOBALS['sent'] = [];
boblift_indexnow_send([11, 12], false);
$check('a retry checks visibility again', $GLOBALS['sent'] === [['https://bobliftcrane.com/p12.html']]);
$GLOBALS['remote_fails'] = true;
boblift_indexnow_send([12], false);
$check('a failed retry is not retried again', $GLOBALS['scheduled'] === []);
$GLOBALS['remote_fails'] = false;
$GLOBALS['posts'] = [];
foreach (range(1, 10001) as $i) {
    $GLOBALS['posts'][$i] = new WP_Post($i);
}
$GLOBALS['remote_fails'] = true;
boblift_indexnow_send(range(1, 10001));
$check('10,001 failed posts are all kept for the retry', count($GLOBALS['scheduled']) === 1 && count($GLOBALS['scheduled'][0][1]) === 10001);
$GLOBALS['remote_fails'] = false;

exit($failures === 0 ? 0 : 1);
