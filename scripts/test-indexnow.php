<?php
// Checks the IndexNow helpers without WordPress: php scripts/test-indexnow.php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
define('ABSPATH', __DIR__);
$GLOBALS['options'] = ['blog_public' => '1', 'home' => 'https://bobliftcrane.com'];
$GLOBALS['env'] = 'production';
class WP_Post
{
    public function __construct(public int $ID = 1, public string $post_type = 'post', public string $post_status = 'publish', public string $post_password = '')
    {
    }
}
$GLOBALS['hooks'] = [];
function add_action(string $hook, callable $callback, int $priority = 10, int $args = 1): void
{
    $GLOBALS['hooks'][$hook] = $callback;
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
    public function esc_like(string $text): string
    {
        return addcslashes($text, '_%\\');
    }
    public function get_col(array $q): array
    {
        $prefix = stripslashes(rtrim($q[1][0], '%'));
        return array_values(array_filter(array_keys($GLOBALS['options']), static fn($name): bool => str_starts_with((string) $name, $prefix)));
    }
    public bool $fail_reads = false;
    public function get_row(array $q, string $output = 'OBJECT')
    {
        if ($this->fail_reads) {
            return null;
        }
        return array_key_exists($q[1][0], $GLOBALS['options']) ? ['option_value' => $GLOBALS['options'][$q[1][0]]] : null;
    }
    public function query(array $q): int
    {
        [$sql, $args] = $q;
        if (str_starts_with($sql, 'DELETE')) {
            if (!array_key_exists($args[0], $GLOBALS['options'])) {
                return 0;
            }
            unset($GLOBALS['options'][$args[0]]);
            return 1;
        }
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
    $GLOBALS['scheduled'][] = $hook;
    return true;
}
function wp_next_scheduled(string $hook)
{
    return in_array($hook, $GLOBALS['scheduled'], true) ? time() : false;
}
function wp_generate_uuid4(): string
{
    return bin2hex(random_bytes(16));
}
function maybe_unserialize($value)
{
    return $value;
}
function wp_is_post_revision($post): bool
{
    return false;
}
function wp_is_post_autosave($post): bool
{
    return false;
}
function status_header(int $code): void
{
    http_response_code($code);
}
require dirname(__DIR__) . '/inc/indexnow.php';

// Child mode: run the key-file route for one path and print what it answers (it exits when it serves the key).
if (($route = getenv('INDEXNOW_ROUTE')) !== false) {
    $GLOBALS['options']['boblift_indexnow_key'] = 'abcd1234';
    $_SERVER['REQUEST_URI'] = $route;
    ($GLOBALS['hooks']['template_redirect'])();
    echo 'FALLTHROUGH';
    exit;
}

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
$rows = boblift_indexnow_retry_names();
$ids = static fn(array $rows): array => array_map(static fn($n) => $GLOBALS['options'][$n]['ids'], $rows);
$check('each failed dispatch is saved in its own row', count($rows) === 2 && $ids($rows) === [[11], [12]]);
$check('each row is due ten minutes after its own failure', $GLOBALS['options'][$rows[0]]['due'] >= time() + 590);
$check('one retry run is scheduled', $GLOBALS['scheduled'] === ['boblift_indexnow_retry']);
$GLOBALS['scheduled'] = [];
boblift_indexnow_schedule_retry();
$check('a lost retry event is scheduled again while rows wait', $GLOBALS['scheduled'] === ['boblift_indexnow_retry']);
$GLOBALS['posts'][11]->post_password = 'secret';
$GLOBALS['remote_fails'] = false;
$GLOBALS['sent'] = [];
$GLOBALS['scheduled'] = [];
($GLOBALS['hooks']['boblift_indexnow_retry'])();
$check('rows that are not due yet are left for a later run', $GLOBALS['sent'] === [] && count(boblift_indexnow_retry_names()) === 2 && $GLOBALS['scheduled'] === ['boblift_indexnow_retry']);
$GLOBALS['options'][$rows[0]]['due'] = time() - 1;
$GLOBALS['options'][$rows[1]]['due'] = time() - 1;
($GLOBALS['hooks']['boblift_indexnow_retry'])();
$check('a due retry checks visibility again and clears its rows', $GLOBALS['sent'] === [['https://bobliftcrane.com/p12.html']] && boblift_indexnow_retry_names() === []);
add_option('boblift_indexnow_retry_x', ['due' => 0, 'ids' => [12]], '', 'no');
$GLOBALS['wpdb']->query(['DELETE', ['boblift_indexnow_retry_x']]);
$GLOBALS['sent'] = [];
boblift_indexnow_run_retries();
$check('a row another run already claimed is not sent twice', $GLOBALS['sent'] === []);
$GLOBALS['wpdb']->fail_reads = true;
add_option('boblift_indexnow_retry_y', ['due' => 0, 'ids' => [12]], '', 'no');
boblift_indexnow_run_retries();
$GLOBALS['wpdb']->fail_reads = false;
$check('a row that could not be read is kept', isset($GLOBALS['options']['boblift_indexnow_retry_y']) && $GLOBALS['sent'] === []);
unset($GLOBALS['options']['boblift_indexnow_retry_y']);
$GLOBALS['remote_fails'] = true;
$GLOBALS['scheduled'] = [];
boblift_indexnow_send([12], false);
$check('a failed retry is not saved again', boblift_indexnow_retry_names() === [] && $GLOBALS['scheduled'] === []);
$GLOBALS['posts'] = [];
foreach (range(1, 10001) as $i) {
    $GLOBALS['posts'][$i] = new WP_Post($i);
}
boblift_indexnow_send(range(1, 10001));
$rows = boblift_indexnow_retry_names();
$check('10,001 failed posts are all kept for the retry', count($rows) === 1 && count($ids($rows)[0]) === 10001);
foreach ($rows as $name) {
    unset($GLOBALS['options'][$name]);
}
$GLOBALS['remote_fails'] = false;

// Publish to shutdown through the registered hooks: queued on publish, sent once at shutdown with the final permalink.
$GLOBALS['posts'] = [21 => new WP_Post(21, 'product')];
$GLOBALS['sent'] = [];
$GLOBALS['boblift_indexnow_queue'] = [];
($GLOBALS['hooks']['transition_post_status'])('publish', 'draft', $GLOBALS['posts'][21]);
($GLOBALS['hooks']['transition_post_status'])('publish', 'publish', $GLOBALS['posts'][21]);
$check('a publish queues without sending', $GLOBALS['sent'] === [] && boblift_indexnow_queued_ids() === [1 => [21]]);
($GLOBALS['hooks']['shutdown'])();
$check('shutdown sends the permalink once', $GLOBALS['sent'] === [['https://bobliftcrane.com/p21.html']]);

$GLOBALS['options']['boblift_indexnow_key'] = "abcd1234\n";
$check('a key with a trailing newline is repaired', preg_match('/^[a-z0-9]{32}\z/', boblift_indexnow_key()) === 1);

$route = static function (string $path): string {
    return (string) shell_exec('INDEXNOW_ROUTE=' . escapeshellarg($path) . ' ' . escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__));
};
$check('the advertised key file answers with the exact key', $route('/abcd1234.txt') === 'abcd1234');
$check('the key file also answers with a trailing slash', $route('/abcd1234.txt/') === 'abcd1234');
$check('an unrelated .txt path falls through', $route('/robots-file.txt') === 'FALLTHROUGH');
$check('a key path with a trailing newline falls through', $route("/abcd1234.txt%0A") === 'FALLTHROUGH');

exit($failures === 0 ? 0 : 1);
