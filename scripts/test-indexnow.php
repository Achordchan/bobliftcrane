<?php
// Checks the IndexNow helpers without WordPress: php scripts/test-indexnow.php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
define('ABSPATH', __DIR__);
// The live platform's own constant; a child run without it checks that a copy elsewhere stays quiet.
if (getenv('INDEXNOW_NO_PLATFORM') === false) {
    define('IS_ATOMIC', true);
}
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
    public string $posts = 'wp_posts';
    public string $last_error = '';
    public bool $fail_posts = false;
    public function get_col(array $q): array
    {
        [$sql, $args] = $q;
        if (str_contains($sql, 'wp_posts')) {
            // SELECT ID FROM posts WHERE ID IN (...): a failed query sets last_error and returns no rows, like wpdb.
            $this->last_error = $this->fail_posts ? 'Lost connection' : '';
            return $this->fail_posts ? [] : array_values(array_filter($args, static fn($id): bool => isset($GLOBALS['posts'][$id]) || in_array($id, $GLOBALS['ghost_posts'] ?? [], true)));
        }
        $this->scans++;
        $prefix = stripslashes(rtrim($args[0], '%'));
        // LIKE prefix% AND option_name > after AND option_name < upper ORDER BY option_name LIMIT n
        $after = $args[1];
        $upper = count($args) === 4 ? $args[2] : null;
        $names = array_filter(array_map('strval', array_keys($GLOBALS['options'])), static fn(string $name): bool => str_starts_with($name, $prefix) && strcmp($name, $after) > 0 && ($upper === null || strcmp($name, $upper) < 0));
        sort($names, SORT_STRING);
        return array_slice(array_values($names), 0, (int) end($args));
    }
    public bool $fail_reads = false;
    public int $scans = 0;
    public ?string $steal_after_read = null;
    public int $reads = 0;
    public function get_row(array $q, string $output = 'OBJECT')
    {
        $this->reads++;
        if ($this->fail_reads || (!empty($GLOBALS['key_reads_fail']) && $q[1][0] === 'boblift_indexnow_key')) {
            return null;
        }
        if ($this->steal_after_read !== null && $this->steal_after_read === $q[1][0] && array_key_exists($q[1][0], $GLOBALS['options'])) {
            $row = ['option_value' => maybe_serialize($GLOBALS['options'][$q[1][0]])];
            unset($GLOBALS['options'][$q[1][0]]);
            return $row;
        }
        // Stored like WordPress does: arrays serialized.
        return array_key_exists($q[1][0], $GLOBALS['options']) ? ['option_value' => (string) maybe_serialize($GLOBALS['options'][$q[1][0]])] : null;
    }
    public function query(array $q): int
    {
        [$sql, $args] = $q;
        if (str_starts_with($sql, 'DELETE')) {
            if (!array_key_exists($args[0], $GLOBALS['options'])) {
                return 0;
            }
            // DELETE ... AND option_value = %s only removes the row while it still holds that value.
            if (isset($args[1]) && maybe_serialize($GLOBALS['options'][$args[0]]) !== $args[1]) {
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
        if (str_contains($sql, 'SET option_name')) {
            // UPDATE ... SET option_name = new, option_value = value WHERE option_name = old AND option_value = raw
            [$new, $value, $old, $raw] = $args;
            if (!array_key_exists($old, $GLOBALS['options']) || (string) maybe_serialize($GLOBALS['options'][$old]) !== (string) $raw || array_key_exists($new, $GLOBALS['options'])) {
                return 0;
            }
            unset($GLOBALS['options'][$old]);
            $GLOBALS['options'][$new] = maybe_unserialize($value);
            return 1;
        }
        if (array_key_exists($args[1], $GLOBALS['options']) && (string) maybe_serialize($GLOBALS['options'][$args[1]]) === (string) $args[2]) {
            $GLOBALS['options'][$args[1]] = maybe_unserialize($args[0]);
            return 1;
        }
        return 0;
    }
}
const ARRAY_A = 'ARRAY_A';
$GLOBALS['wpdb'] = new Fake_Wpdb();
$GLOBALS['add_option_fails'] = false;
function add_option(string $name, $value, $deprecated = '', $autoload = null): bool
{
    if ($GLOBALS['add_option_fails'] || array_key_exists($name, $GLOBALS['options'])) {
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
const HOUR_IN_SECONDS = 3600;
class WP_Error
{
    public function get_error_message(): string
    {
        return 'connection refused';
    }
}
$GLOBALS['remote_fails'] = false;
$GLOBALS['sent'] = [];
$GLOBALS['scheduled'] = $GLOBALS['events'] = [];
$GLOBALS['posts'] = [];
function _prime_post_caches(array $ids, bool $terms = true, bool $meta = true): void
{
    $GLOBALS['primed'][] = $ids;
}
// A batch row's name for a due time, as boblift_indexnow_row_name() makes it, with a readable suffix.
function rn(int $due, string $tag): string
{
    return 'boblift_indexnow_retry_' . sprintf('%010d', max(0, $due)) . '_' . $tag;
}
// The IDs of every stored batch row, wherever leasing has renamed it.
function row_ids(): array
{
    return array_values(array_map(static fn($v) => $v['ids'] ?? null, array_filter($GLOBALS['options'], static fn($v, $k): bool => str_starts_with((string) $k, 'boblift_indexnow_retry_'), ARRAY_FILTER_USE_BOTH)));
}
function clear_rows(): void
{
    foreach (array_keys($GLOBALS['options']) as $k) {
        if (str_starts_with((string) $k, 'boblift_indexnow_retry_')) {
            unset($GLOBALS['options'][$k]);
        }
    }
}
$GLOBALS['runtime_flushes'] = 0;
function wp_cache_flush_runtime(): bool
{
    $GLOBALS['runtime_flushes']++;
    return true;
}
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
    if (isset($GLOBALS['watch_ids'])) {
        // Is a batch row with these IDs still stored while the POST is in flight?
        $GLOBALS['row_seen_during_send'] = (bool) array_filter($GLOBALS['options'], static fn($v, $k): bool => str_starts_with((string) $k, 'boblift_indexnow_retry_') && is_array($v) && ($v['ids'] ?? null) === $GLOBALS['watch_ids'], ARRAY_FILTER_USE_BOTH);
    }
    if ($GLOBALS['remote_fails']) {
        return new WP_Error();
    }
    return ['response' => ['code' => $GLOBALS['remote_code'] ?? 200]];
}
function wp_remote_retrieve_response_code($response)
{
    return $response['response']['code'] ?? '';
}
function wp_unschedule_event(int $time, string $hook, array $args = []): bool
{
    $GLOBALS['events'] = array_values(array_filter($GLOBALS['events'] ?? [], static fn($e) => !($e[0] === $hook && $e[1] === $args && $e[2] === $time)));
    return true;
}
function wp_clear_scheduled_hook(string $hook): int
{
    $GLOBALS['events'] = array_values(array_filter($GLOBALS['events'] ?? [], static fn($e) => $e[0] !== $hook));
    return 1;
}
function wp_unschedule_hook(string $hook): int
{
    return wp_clear_scheduled_hook($hook);
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
$GLOBALS['schedule_fails'] = false;
function wp_schedule_single_event(int $time, string $hook, array $args = [], bool $wp_error = false): bool
{
    if ($GLOBALS['schedule_fails']) {
        return false;
    }
    $GLOBALS['scheduled'][] = $hook;
    $GLOBALS['scheduled_at'][] = $time;
    $GLOBALS['scheduled_args'][] = $args;
    $GLOBALS['events'][] = [$hook, $args, $time];
    return true;
}
function wp_next_scheduled(string $hook, array $args = [])
{
    foreach ($GLOBALS['events'] ?? [] as [$h, $a, $t]) {
        if ($h === $hook && $a === $args) {
            return $t;
        }
    }
    return false;
}
function wp_schedule_event(int $time, string $recurrence, string $hook): bool
{
    $GLOBALS['scheduled'][] = $hook;
    $GLOBALS['events'][] = [$hook, [], $time];
    return true;
}
function wp_generate_uuid4(): string
{
    return bin2hex(random_bytes(16));
}
function maybe_serialize($value)
{
    return is_array($value) ? serialize($value) : $value;
}
function maybe_unserialize($value)
{
    if (is_string($value) && preg_match('/^a:\d+:\{/', $value) === 1) {
        $data = @unserialize($value);
        return $data === false ? $value : $data;
    }
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

if (getenv('INDEXNOW_NO_PLATFORM') !== false) {
    echo boblift_indexnow_enabled() ? 'ENABLED' : 'QUIET';
    exit;
}

// Child mode: run the key-file route for one path and print what it answers (it exits when it serves the key).
if (($route = getenv('INDEXNOW_ROUTE')) !== false) {
    $GLOBALS['options']['boblift_indexnow_key'] = 'abcd1234';
    $_SERVER['REQUEST_URI'] = $route;
    ($GLOBALS['hooks']['template_redirect'])();
    echo 'FALLTHROUGH';
    exit;
}

// Diagnostics go to a null log unless a check reads them.
ini_set('error_log', '/dev/null');
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
$check('quiet on a copy of the live site outside the live platform', shell_exec('INDEXNOW_NO_PLATFORM=1 ' . escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__)) === 'QUIET');

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
$check('only URLs under the key file\'s directory are sent on a subdirectory install', (boblift_indexnow_payloads(['https://bobliftcrane.com/cranes/a.html', 'https://bobliftcrane.com/other/b.html', 'https://bobliftcrane.com/cranesx/c.html'], 'https://bobliftcrane.com/cranes', 'abcd1234')[0]['urlList'] ?? []) === ['https://bobliftcrane.com/cranes/a.html']);
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
$saved = $ids($rows);
sort($saved);
$check('each failed dispatch is saved in its own row', count($rows) === 2 && $saved === [[11], [12]]);
$check('each row is due ten minutes after its own failure', $GLOBALS['options'][$rows[0]]['due'] >= time() + 590);
$check('one retry run is scheduled', $GLOBALS['scheduled'] === ['boblift_indexnow_retry']);
$GLOBALS['scheduled'] = $GLOBALS['events'] = [];
boblift_indexnow_schedule_retry();
$check('a lost retry event is scheduled again while rows wait', $GLOBALS['scheduled'] === ['boblift_indexnow_retry']);
$GLOBALS['posts'][11]->post_password = 'secret';
$GLOBALS['remote_fails'] = false;
$GLOBALS['sent'] = [];
$GLOBALS['scheduled'] = $GLOBALS['events'] = [];
($GLOBALS['hooks']['boblift_indexnow_retry'])();
$check('rows that are not due yet are left for a later run', $GLOBALS['sent'] === [] && count(boblift_indexnow_retry_names()) === 2 && $GLOBALS['scheduled'] === ['boblift_indexnow_retry']);
foreach ($rows as $i => $name) {
    $GLOBALS['options'][rn(time() - 1, 'due' . $i)] = ['due' => time() - 1] + $GLOBALS['options'][$name];
    unset($GLOBALS['options'][$name]);
}
($GLOBALS['hooks']['boblift_indexnow_retry'])();
$check('a due retry checks visibility again and clears its rows', $GLOBALS['sent'] === [['https://bobliftcrane.com/p12.html']] && boblift_indexnow_retry_names() === []);
add_option(rn(0, 'x'), ['due' => 0, 'ids' => [12]], '', 'no');
$GLOBALS['wpdb']->steal_after_read = rn(0, 'x');
$GLOBALS['sent'] = [];
boblift_indexnow_run_retries();
$GLOBALS['wpdb']->steal_after_read = null;
$check('a row another run claims between read and DELETE is not sent', $GLOBALS['sent'] === [] && boblift_indexnow_retry_names() === []);
add_option(rn(0, 'bad'), 'truncated', '', 'no');
$GLOBALS['scheduled'] = $GLOBALS['events'] = [];
boblift_indexnow_run_retries();
$check('a malformed row is removed and schedules nothing', !isset($GLOBALS['options'][rn(0, 'bad')]) && $GLOBALS['scheduled'] === []);
$row_a = rn(time() + 600, 'a');
$row_b = rn(time() + 60, 'b');
add_option($row_a, ['due' => time() + 600, 'ids' => [12]], '', 'no');
add_option($row_b, ['due' => time() + 60, 'ids' => [12]], '', 'no');
$GLOBALS['scheduled_at'] = [];
boblift_indexnow_schedule_retry();
$GLOBALS['scheduled'] = $GLOBALS['events'] = [];
wp_schedule_single_event(time() + 600, 'boblift_indexnow_retry', [['at' => time() + 600]]);
boblift_indexnow_schedule_retry();
$check('a run is added for an earlier batch even when a later run exists', count($GLOBALS['events']) === 2 && $GLOBALS['events'][1][2] <= time() + 60);
$check('the next run is scheduled for the earliest waiting batch', ($GLOBALS['scheduled_at'][0] ?? 0) <= time() + 60);
unset($GLOBALS['options'][$row_a], $GLOBALS['options'][$row_b]);
$GLOBALS['scheduled'] = $GLOBALS['events'] = [];
$GLOBALS['wpdb']->fail_reads = true;
add_option(rn(0, 'y'), ['due' => 0, 'ids' => [12]], '', 'no');
boblift_indexnow_run_retries();
$GLOBALS['wpdb']->fail_reads = false;
$check('a row that could not be read is kept', isset($GLOBALS['options'][rn(0, 'y')]) && $GLOBALS['sent'] === []);
unset($GLOBALS['options'][rn(0, 'y')]);
$GLOBALS['remote_fails'] = true;
$GLOBALS['scheduled'] = $GLOBALS['events'] = [];
boblift_indexnow_send([12], false);
$check('a failed retry is not saved again', boblift_indexnow_retry_names() === [] && $GLOBALS['scheduled'] === []);
$GLOBALS['posts'] = [];
foreach (range(1, 10001) as $i) {
    $GLOBALS['posts'][$i] = new WP_Post($i);
}
boblift_indexnow_send(range(1, 10001));
$rows = boblift_indexnow_retry_names();
$check('10,001 failed posts are all kept, in rows of at most 1,000', count($rows) === 11 && max(array_map('count', $ids($rows))) === 1000 && array_sum(array_map('count', $ids($rows))) === 10001);
foreach ($rows as $name) {
    unset($GLOBALS['options'][$name]);
}
$GLOBALS['remote_fails'] = false;

$GLOBALS['posts'] = [31 => new WP_Post(31)];
$GLOBALS['remote_fails'] = true;
$GLOBALS['add_option_fails'] = true;
$GLOBALS['scheduled'] = $GLOBALS['scheduled_args'] = $GLOBALS['events'] = [];
boblift_indexnow_send([31]);
$GLOBALS['add_option_fails'] = false;
$carried = $GLOBALS['scheduled_args'][0][0] ?? [];
$check('a retry row that cannot be written travels in the cron event', ($carried['ids'] ?? null) === [31] && ($carried['retry'] ?? null) === false);
$GLOBALS['remote_fails'] = false;
$GLOBALS['sent'] = [];
boblift_indexnow_run_retries($carried);
$check('the cron event sends the IDs it carries', $GLOBALS['sent'] === [['https://bobliftcrane.com/p31.html']]);
add_option(rn(0, 'bad2'), 'truncated', '', 'no');
$GLOBALS['scheduled'] = $GLOBALS['events'] = [];
boblift_indexnow_schedule_retry();
$check('scheduling removes a malformed row and schedules nothing', !isset($GLOBALS['options'][rn(0, 'bad2')]) && $GLOBALS['scheduled'] === []);
$saved_key = $GLOBALS['options']['boblift_indexnow_key'];
$GLOBALS['options']['boblift_indexnow_key'] = 'bad key!';
$GLOBALS['wpdb']->fail_reads = true;
$GLOBALS['sent'] = [];
boblift_indexnow_send([31]);
$GLOBALS['wpdb']->fail_reads = false;
$rows = boblift_indexnow_retry_names();
$check('posts are kept for the retry when the key cannot be read', $GLOBALS['sent'] === [] && count($rows) === 1 && $GLOBALS['options'][$rows[0]]['ids'] === [31]);
unset($GLOBALS['options'][$rows[0]]);
$GLOBALS['options']['boblift_indexnow_key'] = $saved_key;

$GLOBALS['posts'] = [41 => new WP_Post(41)];
$log = tempnam(sys_get_temp_dir(), 'indexnow');
ini_set('error_log', $log);
foreach ([503 => 1, 429 => 1, 422 => 0, 202 => 0] as $code => $rows_expected) {
    $GLOBALS['remote_code'] = $code;
    boblift_indexnow_send([41]);
    $rows = boblift_indexnow_retry_names();
    $check('HTTP ' . $code . ($rows_expected ? ' gets one more try' : ' is not retried'), count($rows) === $rows_expected);
    foreach ($rows as $name) {
        unset($GLOBALS['options'][$name]);
    }
}
unset($GLOBALS['remote_code']);
$logged = (string) file_get_contents($log);
unlink($log);
$check('a response that is not retried is logged with its status and URL count', str_contains($logged, 'IndexNow: 1 URLs not accepted (HTTP 422)') && !str_contains($logged, 'HTTP 202'));

add_option(rn(0, 'leased'), ['due' => 0, 'ids' => [41], 'retry' => false, 'lease' => time() + 600], '', 'no');
$GLOBALS['sent'] = [];
boblift_indexnow_run_retries();
$check('a row leased by a live run is skipped', $GLOBALS['sent'] === [] && isset($GLOBALS['options'][rn(0, 'leased')]));
$GLOBALS['options'][rn(0, 'leased')]['lease'] = time() - 1;
$GLOBALS['watch_ids'] = [41];
boblift_indexnow_run_retries();
unset($GLOBALS['watch_ids']);
$check('an expired lease is picked up, the row kept until sent, then removed', $GLOBALS['sent'] === [['https://bobliftcrane.com/p41.html']] && ($GLOBALS['row_seen_during_send'] ?? false) && boblift_indexnow_retry_names() === []);

$GLOBALS['scheduled'] = $GLOBALS['events'] = [];
$row_late = rn(time() - 100, 'late');
add_option($row_late, ['due' => time() - 100, 'ids' => [41], 'retry' => false], '', 'no');
$t = time();
$GLOBALS['options']['boblift_indexnow_wake'] = $t - 5;
$GLOBALS['events'][] = ['boblift_indexnow_retry', [['at' => $t - 5]], $t - 5];
boblift_indexnow_schedule_retry();
$check('an overdue wake-up is reused, not duplicated', count($GLOBALS['events']) === 1);
$GLOBALS['events'] = [['boblift_indexnow_retry', [['at' => $t + 600]], $t + 600]];
$GLOBALS['options']['boblift_indexnow_wake'] = $t + 600;
boblift_indexnow_schedule_retry();
$check('a later wake-up is replaced by an earlier one', count($GLOBALS['events']) === 1 && $GLOBALS['events'][0][2] <= time());
unset($GLOBALS['options'][$row_late]);
$GLOBALS['events'][] = ['boblift_indexnow_sweep', [], time() + 3600];
($GLOBALS['hooks']['switch_theme'])();
$check('switching themes clears the IndexNow cron events', $GLOBALS['events'] === [] && !isset($GLOBALS['options']['boblift_indexnow_wake']));
$GLOBALS['scheduled'] = $GLOBALS['events'] = [];

add_option(rn(time() + 300, 'waiting'), ['due' => time() + 300, 'ids' => [41], 'retry' => false], '', 'no');
$GLOBALS['wpdb']->scans = 0;
boblift_indexnow_save_batch([41], time(), true);
$check('saving a batch schedules from its own due time without scanning the queue', $GLOBALS['wpdb']->scans === 0 && ($GLOBALS['options']['boblift_indexnow_wake'] ?? PHP_INT_MAX) <= time());
foreach (boblift_indexnow_retry_names() as $name) {
    unset($GLOBALS['options'][$name]);
}
$GLOBALS['scheduled'] = $GLOBALS['events'] = [];
unset($GLOBALS['options']['boblift_indexnow_wake']);

$GLOBALS['posts'] = [];
foreach (range(1, 25) as $i) {
    $GLOBALS['posts'][$i] = new WP_Post($i);
    add_option(rn(0, 'budget' . str_pad((string) $i, 2, '0', STR_PAD_LEFT)), ['due' => 0, 'ids' => [$i], 'retry' => true], '', 'no');
}
$GLOBALS['sent'] = [];
$GLOBALS['scheduled'] = $GLOBALS['events'] = [];
unset($GLOBALS['options']['boblift_indexnow_wake']);
boblift_indexnow_run_retries();
$check('one run sends at most 20 rows and wakes again for the rest', count($GLOBALS['sent']) === 20 && count(boblift_indexnow_retry_names()) === 5 && ($GLOBALS['options']['boblift_indexnow_wake'] ?? PHP_INT_MAX) <= time());
boblift_indexnow_run_retries();
$check('the next run finishes the rest', count($GLOBALS['sent']) === 25 && boblift_indexnow_retry_names() === []);

$GLOBALS['options'][rn(0, 'owned')] = ['due' => 0, 'ids' => [1], 'retry' => true];
$batch = boblift_indexnow_read_retry(rn(0, 'owned'));
$before = time();
[$owned, $leased] = boblift_indexnow_lease_retry(rn(0, 'owned'), $batch) ?? ['', ''];
$check('a lease carries this run\'s token', $leased !== '' && isset($GLOBALS['options'][$owned]['token']) && boblift_indexnow_owns_retry($owned, $leased));
$check('a leased row is renamed to its lease end, so it sorts behind rows that can run now', !isset($GLOBALS['options'][rn(0, 'owned')]) && boblift_indexnow_name_due($owned) >= $before + BOBLIFT_INDEXNOW_LEASE && boblift_indexnow_retry_names(10, time()) === []);
$GLOBALS['options'][$owned]['token'] = 'another-run';
$check('a run that lost its lease no longer owns the row and cannot delete it', !boblift_indexnow_owns_retry($owned, $leased) && !boblift_indexnow_finish_retry($owned, $leased) && isset($GLOBALS['options'][$owned]));
unset($GLOBALS['options'][$owned]);

add_option(rn(0, 'keep'), ['due' => 0, 'ids' => [1], 'retry' => true], '', 'no');
$GLOBALS['remote_code'] = 503;
$GLOBALS['add_option_fails'] = true;
$GLOBALS['schedule_fails'] = true;
$log = tempnam(sys_get_temp_dir(), 'indexnow');
ini_set('error_log', $log);
boblift_indexnow_run_retries();
$GLOBALS['add_option_fails'] = $GLOBALS['schedule_fails'] = false;
unset($GLOBALS['remote_code']);
unlink($log);
$check('when the retry cannot be saved anywhere, the original row is kept', row_ids() === [[1]]);
clear_rows();
$GLOBALS['scheduled'] = $GLOBALS['events'] = [];
unset($GLOBALS['options']['boblift_indexnow_wake']);

$GLOBALS['scheduled'] = $GLOBALS['events'] = [];
$base = time();
$GLOBALS['options']['boblift_indexnow_wake'] = $base + 600;
$GLOBALS['events'][] = ['boblift_indexnow_retry', [['at' => $base + 600]], $base + 600];
add_option('boblift_indexnow_wake_lock', $base, '', 'no');
boblift_indexnow_wake_at($base);
$check('while another request holds the wake-up lock, it does not write to cron at all and leaves the rows to the sweep', $GLOBALS['options']['boblift_indexnow_wake'] === $base + 600 && $GLOBALS['events'] === [['boblift_indexnow_retry', [['at' => $base + 600]], $base + 600]]);
$GLOBALS['options']['boblift_indexnow_wake_lock'] = time() - 60;
$GLOBALS['events'] = [['boblift_indexnow_retry', [['at' => $base + 600]], $base + 600]];
boblift_indexnow_wake_at(time());
$check('a stale lock is taken over, the wake-up replaced and the lock released', count($GLOBALS['events']) === 1 && $GLOBALS['events'][0][2] <= time() && !isset($GLOBALS['options']['boblift_indexnow_wake_lock']));
$GLOBALS['scheduled'] = $GLOBALS['events'] = [];
unset($GLOBALS['options']['boblift_indexnow_wake']);

$base = time();
$GLOBALS['options']['boblift_indexnow_wake'] = $base + 600;
$GLOBALS['events'] = [['boblift_indexnow_retry', [['at' => $base + 600]], $base + 600]];
$GLOBALS['schedule_fails'] = true;
$log = tempnam(sys_get_temp_dir(), 'indexnow');
ini_set('error_log', $log);
boblift_indexnow_wake_at($base);
$GLOBALS['schedule_fails'] = false;
unlink($log);
$check('when the earlier wake-up cannot be scheduled, the existing one is kept', $GLOBALS['options']['boblift_indexnow_wake'] === $base + 600 && $GLOBALS['events'] === [['boblift_indexnow_retry', [['at' => $base + 600]], $base + 600]]);
$GLOBALS['scheduled'] = $GLOBALS['events'] = [];
unset($GLOBALS['options']['boblift_indexnow_wake']);

// Post reads: a failed lookup keeps the batch; existing posts are read with primed caches.
$GLOBALS['posts'] = [51 => new WP_Post(51)];
$GLOBALS['scheduled'] = $GLOBALS['events'] = $GLOBALS['sent'] = [];
add_option(rn(0, 'readfail'), ['due' => 0, 'ids' => [51], 'retry' => true], '', 'no');
$GLOBALS['wpdb']->fail_posts = true;
boblift_indexnow_run_retries();
$GLOBALS['wpdb']->fail_posts = false;
$check('a batch whose posts could not be read is kept, not dropped as deleted', $GLOBALS['sent'] === [] && row_ids() === [[51]]);
clear_rows();
$GLOBALS['ghost_posts'] = [52];
add_option(rn(0, 'ghost'), ['due' => 0, 'ids' => [51, 52], 'retry' => true], '', 'no');
boblift_indexnow_run_retries();
$check('a post that exists but could not be loaded keeps the batch', $GLOBALS['sent'] === [] && row_ids() === [[51, 52]]);
clear_rows();
unset($GLOBALS['ghost_posts']);
$GLOBALS['posts'] = [];
foreach (range(1, 250) as $i) {
    $GLOBALS['posts'][$i] = new WP_Post($i);
}
$GLOBALS['primed'] = [];
$GLOBALS['runtime_flushes'] = 0;
$check('a deleted post is skipped and the rest resolved', count(boblift_indexnow_resolve(array_merge(range(1, 250), [999]))) === 250);
$check('post, term and meta caches are primed in chunks of 100', array_map('count', $GLOBALS['primed']) === [100, 100, 50]);
$check('the request-local cache is dropped after each chunk', $GLOBALS['runtime_flushes'] === 3);

// The wake-up scan reads a bounded number of rows, earliest due first. One base time for the whole setup.
$GLOBALS['scheduled'] = $GLOBALS['events'] = [];
unset($GLOBALS['options']['boblift_indexnow_wake']);
$base = time();
foreach (range(0, 599) as $i) {
    $GLOBALS['options'][rn($base + 1000 + $i, 'scan' . $i)] = ['due' => $base + 1000 + $i, 'ids' => [1], 'retry' => true];
}
$GLOBALS['wpdb']->reads = 0;
boblift_indexnow_schedule_retry();
$check('the wake-up scan stops once later rows cannot run sooner', $GLOBALS['wpdb']->reads <= 3 && ($GLOBALS['options']['boblift_indexnow_wake'] ?? 0) === $base + 1000);
foreach (range(0, 599) as $i) {
    $GLOBALS['options'][rn($base + 1000 + $i, 'scan' . $i)]['lease'] = $base + 5000;
}
$GLOBALS['scheduled'] = $GLOBALS['events'] = [];
unset($GLOBALS['options']['boblift_indexnow_wake']);
$GLOBALS['wpdb']->reads = 0;
boblift_indexnow_schedule_retry();
$check('with more rows than one scan reads, the wake-up is no later than the last due time read', $GLOBALS['wpdb']->reads <= 500 && ($GLOBALS['options']['boblift_indexnow_wake'] ?? 0) === $base + 1499);
foreach (boblift_indexnow_retry_names(1000) as $name) {
    unset($GLOBALS['options'][$name]);
}
// 150 due rows leased by stalled runs do not hide a runnable row behind them, and do not make the wake-up loop.
$GLOBALS['posts'] = [71 => new WP_Post(71)];
foreach (range(0, 149) as $i) {
    $GLOBALS['options'][rn($base - 1000 + $i, 'stuck' . $i)] = ['due' => $base - 1000 + $i, 'ids' => [71], 'retry' => true, 'lease' => $base + 600];
}
$GLOBALS['options'][rn($base - 10, 'behind')] = ['due' => $base - 10, 'ids' => [71], 'retry' => true];
$GLOBALS['sent'] = $GLOBALS['scheduled'] = $GLOBALS['events'] = [];
unset($GLOBALS['options']['boblift_indexnow_wake']);
boblift_indexnow_run_retries();
$check('a runnable row behind 150 leased rows is sent', $GLOBALS['sent'] === [['https://bobliftcrane.com/p71.html']] && !isset($GLOBALS['options'][rn($base - 10, 'behind')]));
$check('the leased rows wake the worker when their leases end, not at once', ($GLOBALS['options']['boblift_indexnow_wake'] ?? 0) >= $base + 600);
foreach (boblift_indexnow_retry_names(1000) as $name) {
    unset($GLOBALS['options'][$name]);
}
$GLOBALS['scheduled'] = $GLOBALS['events'] = [];
unset($GLOBALS['options']['boblift_indexnow_wake']);

// A run whose lease is taken over while it resolves permalinks sends nothing and leaves the row to the new owner.
add_option(rn(0, 'takeover'), ['due' => 0, 'ids' => [71], 'retry' => true], '', 'no');
$GLOBALS['sent'] = [];
$check('a send stops when ownership is lost before the POST', boblift_indexnow_send([71], true, static fn(): bool => false) === false && $GLOBALS['sent'] === []);
unset($GLOBALS['options'][rn(0, 'takeover')]);

// A batch carried in a cron event is saved as a row before it is sent, so a failure cannot lose it.
$GLOBALS['posts'] = [61 => new WP_Post(61)];
$GLOBALS['wpdb']->fail_posts = true;
boblift_indexnow_run_retries(['due' => time(), 'ids' => [61], 'retry' => false]);
$GLOBALS['wpdb']->fail_posts = false;
$rows = boblift_indexnow_retry_names();
$check('a carried batch that fails is kept as a row', count($rows) === 1 && $GLOBALS['options'][$rows[0]]['ids'] === [61]);
unset($GLOBALS['options'][$rows[0]]);
$GLOBALS['scheduled'] = $GLOBALS['events'] = [];
unset($GLOBALS['options']['boblift_indexnow_wake']);

$GLOBALS['add_option_fails'] = true;
$GLOBALS['sent'] = [];
boblift_indexnow_run_retries(['due' => time(), 'ids' => [61], 'retry' => false]);
$GLOBALS['add_option_fails'] = false;
$carried_again = array_values(array_filter($GLOBALS['events'], static fn($e): bool => ($e[1][0]['ids'] ?? null) === [61]));
$check('a carried batch whose row still cannot be written is carried again ten minutes out, not at once', $GLOBALS['sent'] === [] && count($carried_again) === 1 && $carried_again[0][2] >= time() + 590);
$GLOBALS['scheduled'] = $GLOBALS['events'] = [];
unset($GLOBALS['options']['boblift_indexnow_wake']);

// A retry whose key cannot be read makes no POST and keeps its row.
$saved_key = $GLOBALS['options']['boblift_indexnow_key'];
$GLOBALS['options']['boblift_indexnow_key'] = 'bad key!';
add_option(rn(0, 'nokey'), ['due' => 0, 'ids' => [61], 'retry' => false], '', 'no');
$GLOBALS['wpdb']->fail_reads = false;
$GLOBALS['key_reads_fail'] = true;
$GLOBALS['sent'] = [];
$check('a retry that cannot read the key reports failure', boblift_indexnow_send([61], false) === false && $GLOBALS['sent'] === []);
unset($GLOBALS['key_reads_fail'], $GLOBALS['options'][rn(0, 'nokey')]);
$GLOBALS['options']['boblift_indexnow_key'] = $saved_key;

// The lock is owned by a token: a request whose lock was taken over leaves the new owner's lock alone.
$old = boblift_indexnow_lock();
$check('the lock is taken with a token and not twice', is_string($old) && boblift_indexnow_lock() === null);
$GLOBALS['options']['boblift_indexnow_wake_lock'] = (time() - 60) . ':' . substr((string) $old, strpos((string) $old, ':') + 1);
$stale = $GLOBALS['options']['boblift_indexnow_wake_lock'];
$new = boblift_indexnow_lock();
boblift_indexnow_unlock($stale);
$check('a stale lock is taken over and its old owner cannot release the new lock', is_string($new) && ($GLOBALS['options']['boblift_indexnow_wake_lock'] ?? null) === $new);
boblift_indexnow_unlock($new);
$check('the owner releases its own lock', !isset($GLOBALS['options']['boblift_indexnow_wake_lock']));

// Publish to shutdown through the registered hooks: queued on publish, sent once at shutdown with the final permalink.
$GLOBALS['posts'] = [21 => new WP_Post(21, 'product')];
$GLOBALS['sent'] = [];
$GLOBALS['boblift_indexnow_queue'] = [];
($GLOBALS['hooks']['transition_post_status'])('publish', 'draft', $GLOBALS['posts'][21]);
($GLOBALS['hooks']['transition_post_status'])('publish', 'publish', $GLOBALS['posts'][21]);
$check('a publish queues without sending', $GLOBALS['sent'] === [] && boblift_indexnow_queued_ids() === [1 => [21]]);
$GLOBALS['scheduled'] = $GLOBALS['scheduled_at'] = $GLOBALS['events'] = [];
($GLOBALS['hooks']['shutdown'])();
$rows = boblift_indexnow_retry_names();
$check('shutdown makes no network call and saves one batch due now', $GLOBALS['sent'] === [] && count($rows) === 1 && $GLOBALS['options'][$rows[0]]['retry'] === true && ($GLOBALS['scheduled_at'][0] ?? PHP_INT_MAX) <= time());
($GLOBALS['hooks']['boblift_indexnow_retry'])();
$check('the cron run sends the permalink once and clears the batch', $GLOBALS['sent'] === [['https://bobliftcrane.com/p21.html']] && boblift_indexnow_retry_names() === []);
$GLOBALS['remote_fails'] = true;
($GLOBALS['hooks']['transition_post_status'])('publish', 'draft', $GLOBALS['posts'][21]);
($GLOBALS['hooks']['shutdown'])();
$GLOBALS['boblift_indexnow_queue'] = [];
($GLOBALS['hooks']['boblift_indexnow_retry'])();
$rows = boblift_indexnow_retry_names();
$check('a first try that cannot be dispatched leaves one retry ten minutes out', count($rows) === 1 && $GLOBALS['options'][$rows[0]]['retry'] === false && $GLOBALS['options'][$rows[0]]['due'] >= time() + 590);
unset($GLOBALS['options'][$rows[0]]);
$GLOBALS['remote_fails'] = false;
$GLOBALS['scheduled'] = $GLOBALS['events'] = [];
($GLOBALS['hooks']['init'])();
$check('the hourly sweep is scheduled on the live site', in_array('boblift_indexnow_sweep', $GLOBALS['scheduled'], true));
$log = tempnam(sys_get_temp_dir(), 'indexnow');
ini_set('error_log', $log);
$GLOBALS['schedule_fails'] = true;
boblift_indexnow_save_batch([21], time(), true);
$GLOBALS['schedule_fails'] = false;
$rows = boblift_indexnow_retry_names();
$check('a failed schedule is logged and the row waits for the sweep', str_contains((string) file_get_contents($log), 'IndexNow: could not schedule') && count($rows) === 1);
unset($GLOBALS['options'][$rows[0]]);
unlink($log);
$check('the sweep runs the batch worker', ($GLOBALS['hooks']['boblift_indexnow_sweep'] ?? null) === 'boblift_indexnow_run_retries');

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
