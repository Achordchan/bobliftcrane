<?php
if (!defined('ABSPATH')) {
    exit;
}

/*
 * IndexNow: when a public page, article, product or case is published or updated, tell Bing and the other IndexNow
 * engines right away instead of waiting for the next crawl. ChatGPT search and Copilot answer from the Bing index, so
 * new and changed pages reach them sooner.
 *
 * The key is made once and kept in an option. It is served as plain text at /<key>.txt, which is how the engines
 * check that the ping comes from the site owner. Only the live site (a host in BOBLIFT_INDEXNOW_HOSTS, explicitly
 * public, production) pings; local copies, staging clones, private sites and other hosts stay quiet. Changed posts are
 * queued by blog and ID during the request. At shutdown they are saved as a batch row (no network call on the
 * publishing request). A cron run leases each due row with a compare-and-swap UPDATE, checks each post again, sends
 * the permalinks in POSTs of up to 10,000 URLs, and deletes the row only afterwards; a run that dies mid-way leaves
 * the row to be picked up once its lease expires. A POST that fails (no connection, 429 or 5xx) gets one more try ten
 * minutes later, in a row of its own. One wake-up event is kept, moved earlier only when a batch needs it, and an
 * hourly sweep picks up any row whose event was lost.
 */

const BOBLIFT_INDEXNOW_ENDPOINT = 'https://api.indexnow.org/indexnow';
const BOBLIFT_INDEXNOW_HOSTS = ['bobliftcrane.com'];
const BOBLIFT_INDEXNOW_POST_TYPES = ['page', 'post', 'product', 'case'];
const BOBLIFT_INDEXNOW_BATCH = 10000;
const BOBLIFT_INDEXNOW_KEY_OPTION = 'boblift_indexnow_key';
const BOBLIFT_INDEXNOW_RETRY_HOOK = 'boblift_indexnow_retry';
const BOBLIFT_INDEXNOW_RETRY_PREFIX = 'boblift_indexnow_retry_';
const BOBLIFT_INDEXNOW_SWEEP_HOOK = 'boblift_indexnow_sweep';
const BOBLIFT_INDEXNOW_WAKE_OPTION = 'boblift_indexnow_wake';
const BOBLIFT_INDEXNOW_LEASE = 15 * 60;
const BOBLIFT_INDEXNOW_ROW_IDS = 1000;
const BOBLIFT_INDEXNOW_RUN_ROWS = 20;
const BOBLIFT_INDEXNOW_RUN_SECONDS = 60;

function boblift_indexnow_valid_key(mixed $key): bool
{
    return is_string($key) && preg_match('/^[a-zA-Z0-9-]{8,128}\z/', $key) === 1;
}

/**
 * The stored key, read past the object cache so concurrent requests see the same row. Null only when there is no row;
 * an empty value comes back as '' (get_var() would turn it into null too) so it reaches the repair branch.
 */
function boblift_indexnow_stored_key(): ?string
{
    global $wpdb;
    $row = $wpdb->get_row($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", BOBLIFT_INDEXNOW_KEY_OPTION), ARRAY_A);
    return is_array($row) ? (string) $row['option_value'] : null;
}

/**
 * The verification key, made on first use. The insert is INSERT IGNORE and the repair of a broken value is a
 * compare-and-swap UPDATE, so two first requests always end up agreeing on whichever key the database kept.
 * Returns '' when no valid key could be stored; callers then send nothing.
 */
function boblift_indexnow_key(): string
{
    global $wpdb;
    $key = get_option(BOBLIFT_INDEXNOW_KEY_OPTION, null);
    if (boblift_indexnow_valid_key($key)) {
        return $key;
    }
    // Not wp_generate_password(): its random_password filter could add characters IndexNow does not accept.
    $candidate = bin2hex(random_bytes(16));
    $stored = boblift_indexnow_stored_key();
    if ($stored === null) {
        $wpdb->query($wpdb->prepare("INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'yes')", BOBLIFT_INDEXNOW_KEY_OPTION, $candidate));
    } elseif (!boblift_indexnow_valid_key($stored)) {
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s", $candidate, BOBLIFT_INDEXNOW_KEY_OPTION, $stored));
    }
    wp_cache_delete(BOBLIFT_INDEXNOW_KEY_OPTION, 'options');
    wp_cache_delete('alloptions', 'options');
    wp_cache_delete('notoptions', 'options');
    $key = boblift_indexnow_stored_key();
    return boblift_indexnow_valid_key($key) ? $key : '';
}

/** The host without IPv6 brackets or a trailing DNS dot, lower case. */
function boblift_indexnow_host(string $url): string
{
    return rtrim(trim(strtolower((string) wp_parse_url($url, PHP_URL_HOST)), '[]'), '.');
}

function boblift_indexnow_enabled(): bool
{
    // WordPress.com stores -1 for a private site, so only an explicit '1' counts as public.
    return (string) get_option('blog_public') === '1'
        && wp_get_environment_type() === 'production'
        && in_array(boblift_indexnow_host((string) get_option('home')), BOBLIFT_INDEXNOW_HOSTS, true);
}

/** Post IDs changed during this request, grouped by blog; adding is constant time. */
function boblift_indexnow_queue_add(int $post_id, int $blog_id = 1): void
{
    $GLOBALS['boblift_indexnow_queue'][$blog_id][$post_id] = true;
}

/** @return array<int, int[]> blog ID => post IDs */
function boblift_indexnow_queued_ids(): array
{
    return array_map('array_keys', $GLOBALS['boblift_indexnow_queue'] ?? []);
}

function boblift_indexnow_post_qualifies(mixed $post): bool
{
    return $post instanceof WP_Post
        && $post->post_status === 'publish'
        && in_array($post->post_type, BOBLIFT_INDEXNOW_POST_TYPES, true)
        && (string) $post->post_password === '';
}

/** Request bodies for the URLs, at most BOBLIFT_INDEXNOW_BATCH each, keeping only URLs on the site's own host. */
function boblift_indexnow_payloads(array $urls, string $home, string $key): array
{
    $host = boblift_indexnow_host($home);
    if ($host === '' || !boblift_indexnow_valid_key($key)) {
        return [];
    }
    $same_host = array_values(array_unique(array_filter($urls, static fn(string $url): bool => boblift_indexnow_host($url) === $host)));
    $payloads = [];
    foreach (array_chunk($same_host, BOBLIFT_INDEXNOW_BATCH) as $batch) {
        $payloads[] = [
            'host' => $host,
            'key' => $key,
            'keyLocation' => rtrim($home, '/') . '/' . $key . '.txt',
            'urlList' => $batch,
        ];
    }
    return $payloads;
}

/** @return array<string, int[]> permalink => post IDs, for the queued posts that still qualify, read in the current blog. */
function boblift_indexnow_resolve(array $post_ids): array
{
    $urls = [];
    foreach ($post_ids as $id) {
        $post = get_post((int) $id);
        if (!boblift_indexnow_post_qualifies($post)) {
            continue;
        }
        $url = get_permalink($post);
        if (is_string($url) && $url !== '') {
            $urls[$url][] = (int) $post->ID;
        }
    }
    return $urls;
}

/**
 * Sends the posts' current permalinks; runs from cron. When $retry is true, the IDs behind a POST that failed (no
 * connection, 429 or 5xx) are saved for one more try ten minutes later; that try itself does not retry. Returns false
 * only when such a retry could not be saved anywhere, so the caller keeps its own copy of the batch.
 */
function boblift_indexnow_send(array $post_ids, bool $retry = true): bool
{
    if (!boblift_indexnow_enabled()) {
        return true;
    }
    $by_url = boblift_indexnow_resolve($post_ids);
    if ($by_url === []) {
        return true;
    }
    $key = boblift_indexnow_key();
    if ($key === '') {
        // The key could not be stored or read back: keep the posts for the retry instead of dropping them.
        return !$retry || boblift_indexnow_save_batch(array_merge(...array_values($by_url)), time() + 10 * MINUTE_IN_SECONDS, false);
    }
    $kept = true;
    foreach (boblift_indexnow_payloads(array_keys($by_url), (string) get_option('home'), $key) as $payload) {
        // Blocking, from cron only: a non-blocking request can hide a failed connection.
        $response = wp_remote_post(BOBLIFT_INDEXNOW_ENDPOINT, [
            'timeout' => 10,
            'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
            'body' => wp_json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
        $code = is_wp_error($response) ? 0 : (int) wp_remote_retrieve_response_code($response);
        // IndexNow answers 200 or 202; 429 and 5xx are worth another try, other 4xx (bad key or URLs) are not.
        if (($code === 0 || $code === 429 || $code >= 500) && $retry) {
            $ids = [];
            foreach ($payload['urlList'] as $url) {
                array_push($ids, ...$by_url[$url]);
            }
            $kept = boblift_indexnow_save_batch(array_values(array_unique($ids)), time() + 10 * MINUTE_IN_SECONDS, false) && $kept;
        }
    }
    return $kept;
}

/**
 * Saves post IDs as batch rows of at most BOBLIFT_INDEXNOW_ROW_IDS each (unique names, so concurrent requests never
 * touch each other's rows, and each row is small enough for one worker step) and moves the wake-up to $due if needed.
 * $retry says whether a failed POST of the batch gets one more try. Returns false if any part could not be kept.
 */
function boblift_indexnow_save_batch(array $post_ids, int $due, bool $retry): bool
{
    $kept = true;
    foreach (array_chunk(array_map('intval', $post_ids), BOBLIFT_INDEXNOW_ROW_IDS) as $ids) {
        $batch = ['due' => $due, 'ids' => $ids, 'retry' => $retry];
        if (add_option(BOBLIFT_INDEXNOW_RETRY_PREFIX . str_replace('-', '', wp_generate_uuid4()), $batch, '', 'no')) {
            // The new row's own due time is enough here; the worker and the hourly sweep reconcile the rest.
            boblift_indexnow_wake_at($due);
        } else {
            // The row could not be written: carry the batch in the cron event itself so it is not lost.
            $kept = boblift_indexnow_schedule_event($due, [$batch]) && $kept;
        }
    }
    return $kept;
}

/** Schedules a run of the batch worker; a failure is logged and reported to the caller. */
function boblift_indexnow_schedule_event(int $time, array $args = []): bool
{
    $scheduled = wp_schedule_single_event($time, BOBLIFT_INDEXNOW_RETRY_HOOK, $args, true);
    if ($scheduled !== true) {
        error_log(sprintf('IndexNow: could not schedule a batch run (%s).', is_wp_error($scheduled) ? $scheduled->get_error_message() : 'cron write failed'));
        return false;
    }
    return true;
}

/** @return string[] option names of saved retry batches in the current blog */
function boblift_indexnow_retry_names(): array
{
    global $wpdb;
    return array_map('strval', (array) $wpdb->get_col($wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like(BOBLIFT_INDEXNOW_RETRY_PREFIX) . '%')));
}

/**
 * Reads a saved batch. Returns null when the read itself failed (the row is kept for later), false when the row was
 * read but holds no valid batch, otherwise ['due' => int, 'ids' => int[], 'retry' => bool, 'lease' => int, 'raw' => the
 * stored string, for the compare-and-swap lease].
 */
function boblift_indexnow_read_retry(string $name): array|false|null
{
    global $wpdb;
    $row = $wpdb->get_row($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", $name), ARRAY_A);
    if (!is_array($row)) {
        return null;
    }
    $batch = maybe_unserialize($row['option_value']);
    if (!is_array($batch) || !is_array($batch['ids'] ?? null) || !is_numeric($batch['due'] ?? null)) {
        return false;
    }
    return ['due' => (int) $batch['due'], 'ids' => array_map('intval', $batch['ids']), 'retry' => (bool) ($batch['retry'] ?? false), 'lease' => (int) ($batch['lease'] ?? 0), 'raw' => (string) $row['option_value']];
}

/** Removes a row; true only for the request whose DELETE removed it. */
function boblift_indexnow_claim_retry(string $name): bool
{
    global $wpdb;
    $claimed = (int) $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name = %s", $name)) === 1;
    wp_cache_delete($name, 'options');
    return $claimed;
}

/**
 * Leases a due row: a compare-and-swap UPDATE from the value just read to the same batch with a lease time and a token
 * of this run, so only one run wins. Returns the leased value (what this run owns), or null when another run won.
 */
function boblift_indexnow_lease_retry(string $name, array $batch): ?string
{
    global $wpdb;
    $leased = ['due' => $batch['due'], 'ids' => $batch['ids'], 'retry' => $batch['retry'], 'lease' => time() + BOBLIFT_INDEXNOW_LEASE, 'token' => wp_generate_uuid4()];
    $value = maybe_serialize($leased);
    $won = (int) $wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s", $value, $name, $batch['raw'])) === 1;
    wp_cache_delete($name, 'options');
    return $won ? $value : null;
}

/** True while the row still holds exactly the lease this run took, so no other run has taken it over. */
function boblift_indexnow_owns_retry(string $name, string $leased): bool
{
    $batch = boblift_indexnow_read_retry($name);
    return is_array($batch) && $batch['raw'] === $leased;
}

/** Deletes the row only if it still holds this run's lease. */
function boblift_indexnow_finish_retry(string $name, string $leased): bool
{
    global $wpdb;
    $done = (int) $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $name, $leased)) === 1;
    wp_cache_delete($name, 'options');
    return $done;
}

/** When the row can next be run: its due time, or the end of a lease still held by another run. */
function boblift_indexnow_runnable_at(array $batch): int
{
    return max($batch['due'], $batch['lease']);
}

/**
 * Keeps one wake-up event at the earliest time a waiting batch can run. An existing wake-up at or before that time is
 * reused (also when cron is late and it is overdue); it is replaced only when a batch needs an earlier run. A batch
 * that could not be read counts as runnable in ten minutes.
 */
function boblift_indexnow_schedule_retry(): void
{
    $next = null;
    foreach (boblift_indexnow_retry_names() as $name) {
        $batch = boblift_indexnow_read_retry($name);
        if ($batch === false) {
            boblift_indexnow_claim_retry($name);
            continue;
        }
        $at = $batch === null ? time() + 10 * MINUTE_IN_SECONDS : boblift_indexnow_runnable_at($batch);
        $next = $next === null ? $at : min($next, $at);
    }
    if ($next !== null) {
        boblift_indexnow_wake_at($next);
    }
}

/**
 * Makes sure the one wake-up event runs no later than $at: an existing wake-up at or before it is reused (also when
 * it is overdue), otherwise it is moved to $at. Reads only the wake-up option, never the batch rows.
 */
function boblift_indexnow_wake_at(int $at): void
{
    $wake = (int) get_option(BOBLIFT_INDEXNOW_WAKE_OPTION, 0);
    if ($wake > 0 && $wake <= max($at, time()) && wp_next_scheduled(BOBLIFT_INDEXNOW_RETRY_HOOK, [['at' => $wake]])) {
        return;
    }
    $next = max($at, time());
    if ($wake > 0) {
        wp_unschedule_event($wake, BOBLIFT_INDEXNOW_RETRY_HOOK, [['at' => $wake]]);
    }
    update_option(BOBLIFT_INDEXNOW_WAKE_OPTION, $next, false);
    boblift_indexnow_schedule_event($next, [['at' => $next]]);
}

/**
 * Sends runnable batches, one row (at most BOBLIFT_INDEXNOW_ROW_IDS posts) at a time, within a budget of
 * BOBLIFT_INDEXNOW_RUN_ROWS rows and BOBLIFT_INDEXNOW_RUN_SECONDS per call; what is left is picked up by the wake-up
 * scheduled at the end. Each row is leased, checked to be still ours right before its POST, and deleted only by its
 * owner after the POST and any retry row were saved; if the retry could not be saved the row stays and runs again when
 * the lease ends. A row that could not be read, is not due, or is leased by a live run stays; a row that was read but
 * holds no valid batch is removed. $carried is a batch that travelled in the cron event because its row could not be
 * written.
 */
function boblift_indexnow_run_retries(array $carried = []): void
{
    $started = time();
    if (is_array($carried['ids'] ?? null)) {
        boblift_indexnow_send(array_map('intval', $carried['ids']), (bool) ($carried['retry'] ?? false));
    }
    $rows = 0;
    foreach (boblift_indexnow_retry_names() as $name) {
        if ($rows >= BOBLIFT_INDEXNOW_RUN_ROWS || time() - $started >= BOBLIFT_INDEXNOW_RUN_SECONDS) {
            break;
        }
        $batch = boblift_indexnow_read_retry($name);
        if ($batch === false) {
            boblift_indexnow_claim_retry($name);
            continue;
        }
        if ($batch === null || boblift_indexnow_runnable_at($batch) > time()) {
            continue;
        }
        $leased = boblift_indexnow_lease_retry($name, $batch);
        if ($leased === null || !boblift_indexnow_owns_retry($name, $leased)) {
            continue;
        }
        $rows++;
        if (boblift_indexnow_send($batch['ids'], $batch['retry'])) {
            boblift_indexnow_finish_retry($name, $leased);
        }
    }
    boblift_indexnow_schedule_retry();
}

add_action('transition_post_status', static function (string $new_status, string $old_status, WP_Post $post): void {
    if ($new_status !== 'publish' || !in_array($post->post_type, BOBLIFT_INDEXNOW_POST_TYPES, true)) {
        return;
    }
    if (wp_is_post_revision($post) || wp_is_post_autosave($post) || !boblift_indexnow_enabled()) {
        return;
    }
    boblift_indexnow_queue_add((int) $post->ID, (int) get_current_blog_id());
}, 10, 3);

add_action('shutdown', static function (): void {
    foreach (boblift_indexnow_queued_ids() as $blog_id => $post_ids) {
        $switched = is_multisite() && (int) $blog_id !== (int) get_current_blog_id();
        if ($switched) {
            switch_to_blog((int) $blog_id);
        }
        // Saved for the cron worker, so a slow IndexNow endpoint never holds up the publishing request.
        boblift_indexnow_save_batch($post_ids, time(), true);
        if ($switched) {
            restore_current_blog();
        }
    }
});

// Cron runs in the blog that scheduled it, where its batch rows live.
add_action(BOBLIFT_INDEXNOW_RETRY_HOOK, 'boblift_indexnow_run_retries');
add_action(BOBLIFT_INDEXNOW_SWEEP_HOOK, 'boblift_indexnow_run_retries');

// The hourly sweep runs rows whose own event was lost, without waiting for the next publish.
add_action('init', static function (): void {
    if (boblift_indexnow_enabled() && !wp_next_scheduled(BOBLIFT_INDEXNOW_SWEEP_HOOK)) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', BOBLIFT_INDEXNOW_SWEEP_HOOK);
    }
});

// Switching away from this theme drops its cron events, so nothing keeps running without a callback.
add_action('switch_theme', static function (): void {
    wp_clear_scheduled_hook(BOBLIFT_INDEXNOW_SWEEP_HOOK);
    wp_unschedule_hook(BOBLIFT_INDEXNOW_RETRY_HOOK);
    delete_option(BOBLIFT_INDEXNOW_WAKE_OPTION);
});

// The key file, answered ahead of the legacy redirects (priority 0) and the 404 template.
add_action('template_redirect', static function (): void {
    $request = rtrim((string) wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/');
    if (preg_match('#/[a-zA-Z0-9-]{8,128}\.txt\z#', $request) !== 1 || !boblift_indexnow_enabled()) {
        return;
    }
    $base = rtrim((string) wp_parse_url((string) get_option('home'), PHP_URL_PATH), '/');
    $key = boblift_indexnow_key();
    if ($key === '' || $request !== $base . '/' . $key . '.txt') {
        return;
    }
    status_header(200);
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Robots-Tag: noindex');
    header('Cache-Control: public, max-age=86400');
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') {
        echo $key;
    }
    exit;
}, -1);
