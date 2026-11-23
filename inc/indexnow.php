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
const BOBLIFT_INDEXNOW_LOCK_OPTION = 'boblift_indexnow_wake_lock';
const BOBLIFT_INDEXNOW_LEASE = 15 * 60;
const BOBLIFT_INDEXNOW_ROW_IDS = 1000;
const BOBLIFT_INDEXNOW_RUN_ROWS = 20;
const BOBLIFT_INDEXNOW_RUN_SECONDS = 60;
// Most batch rows read by one worker run or one wake-up reconciliation.
const BOBLIFT_INDEXNOW_SCAN_ROWS = 100;
// Pages of BOBLIFT_INDEXNOW_SCAN_ROWS one scan may read, so rows leased by stalled runs cannot hide the rows behind them.
const BOBLIFT_INDEXNOW_SCAN_PAGES = 5;
// Tries, 100 ms apart, to take the wake-up lock before leaving the rows to the hourly sweep.
const BOBLIFT_INDEXNOW_LOCK_TRIES = 20;

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

/**
 * True only on the live hosting: WordPress.com's Atomic platform defines IS_ATOMIC and ATOMIC_SITE_ID in its own
 * configuration, outside the database, so a copy of the database or theme running anywhere else (a local clone mapped
 * to the live host name, for instance) stays quiet. Another live host can opt in with define('BOBLIFT_INDEXNOW_LIVE', true) in wp-config.php.
 */
function boblift_indexnow_live_platform(): bool
{
    return (defined('IS_ATOMIC') && IS_ATOMIC) || (defined('ATOMIC_SITE_ID') && ATOMIC_SITE_ID) || (defined('BOBLIFT_INDEXNOW_LIVE') && BOBLIFT_INDEXNOW_LIVE);
}

function boblift_indexnow_enabled(): bool
{
    // WordPress.com stores -1 for a private site, so only an explicit '1' counts as public.
    return boblift_indexnow_live_platform()
        && (string) get_option('blog_public') === '1'
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

/**
 * Request bodies for the URLs, at most BOBLIFT_INDEXNOW_BATCH each, keeping only URLs on the site's own host and under
 * the directory the key file is served from (the key only verifies URLs there; one URL outside it fails the batch).
 */
function boblift_indexnow_payloads(array $urls, string $home, string $key): array
{
    $host = boblift_indexnow_host($home);
    if ($host === '' || !boblift_indexnow_valid_key($key)) {
        return [];
    }
    $scope = rtrim((string) wp_parse_url($home, PHP_URL_PATH), '/') . '/';
    $same_host = array_values(array_unique(array_filter($urls, static fn(string $url): bool => boblift_indexnow_host($url) === $host && str_starts_with((string) wp_parse_url($url, PHP_URL_PATH), $scope))));
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

/**
 * @return array<string, int[]>|null permalink => post IDs for the queued posts that still qualify, read in the current
 * blog; null when a post could not be read, so the caller keeps the batch. Existence is checked in one query per chunk
 * (a failed query is told apart from a deleted post by wpdb's last_error), then the post, term and meta caches are
 * primed for the chunk so permalinks do not cost one round trip per post.
 */
function boblift_indexnow_resolve(array $post_ids): ?array
{
    global $wpdb;
    $ids = array_values(array_unique(array_filter(array_map('intval', $post_ids), static fn(int $id): bool => $id > 0)));
    $urls = [];
    foreach (array_chunk($ids, 100) as $chunk) {
        $in = implode(',', array_fill(0, count($chunk), '%d'));
        $found = $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE ID IN ($in)", ...$chunk));
        if ((string) $wpdb->last_error !== '' || !is_array($found)) {
            return null;
        }
        $found = array_map('intval', $found);
        _prime_post_caches($found, true, true);
        foreach ($found as $id) {
            $post = get_post($id);
            if (!$post instanceof WP_Post) {
                // The row was there a moment ago: treat it as a failed read and keep the batch for another run.
                return null;
            }
            if (!boblift_indexnow_post_qualifies($post)) {
                continue;
            }
            $url = get_permalink($post);
            if (is_string($url) && $url !== '') {
                $urls[$url][] = (int) $post->ID;
            }
        }
        // Drop this chunk's primed posts, terms and meta from the request-local cache before the next one, so a
        // 1,000-post row never holds them all at once (the persistent object cache, if any, keeps them).
        if (function_exists('wp_cache_flush_runtime')) {
            wp_cache_flush_runtime();
        }
    }
    return $urls;
}

/**
 * Sends the posts' current permalinks; runs from cron. When $retry is true, the IDs behind a POST that failed (no
 * connection, 429 or 5xx) are saved for one more try ten minutes later; that try itself does not retry. Returns false
 * when the posts could not be read or such a retry could not be saved anywhere, so the caller keeps its copy of the batch.
 * $still_owned, when given, is checked right before each POST; once it says the batch was taken over, nothing more is
 * sent and false is returned (the run that took it over sends it).
 */
function boblift_indexnow_send(array $post_ids, bool $retry = true, ?callable $still_owned = null): bool
{
    if (!boblift_indexnow_enabled()) {
        return true;
    }
    $by_url = boblift_indexnow_resolve($post_ids);
    if ($by_url === null) {
        return false;
    }
    if ($by_url === []) {
        return true;
    }
    $key = boblift_indexnow_key();
    if ($key === '') {
        // The key could not be stored or read back, so nothing was submitted: a first try is saved as the retry, and a
        // retry reports failure so its row is kept and runs again (only a real POST uses up the last attempt).
        return $retry && boblift_indexnow_save_batch(array_merge(...array_values($by_url)), time() + 10 * MINUTE_IN_SECONDS, false);
    }
    $kept = true;
    foreach (boblift_indexnow_payloads(array_keys($by_url), (string) get_option('home'), $key) as $payload) {
        if ($still_owned !== null && !$still_owned()) {
            return false;
        }
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
        } elseif ($code < 200 || $code >= 300) {
            // Not retried (a bad key or URL list, or the last try failed): logged, so a broken key route is noticed.
            error_log(sprintf('IndexNow: %d URLs not accepted (%s).', count($payload['urlList']), $code === 0 ? 'no connection: ' . $response->get_error_message() : 'HTTP ' . $code));
        }
    }
    return $kept;
}

/**
/**
 * A batch row's option name: the due time, zero-padded so names sort by it, then a unique suffix so concurrent requests
 * never touch each other's rows. Sorting by name lets the worker and the wake-up read the earliest rows first.
 */
function boblift_indexnow_row_name(int $due): string
{
    return BOBLIFT_INDEXNOW_RETRY_PREFIX . sprintf('%010d', max(0, $due)) . '_' . str_replace('-', '', wp_generate_uuid4());
}

/** The due time written into a row's name (0 for a name without one, so it sorts and runs first). */
function boblift_indexnow_name_due(string $name): int
{
    return preg_match('/^' . preg_quote(BOBLIFT_INDEXNOW_RETRY_PREFIX, '/') . '(\d{10})_/', $name, $m) === 1 ? (int) $m[1] : 0;
}

/**
 * Saves post IDs as batch rows of at most BOBLIFT_INDEXNOW_ROW_IDS each (unique names, so concurrent requests never
 * touch each other's rows, and each row is small enough for one worker step) and moves the wake-up to $due if needed.
 * $retry says whether a failed POST of the batch gets one more try. Returns false if any part could not be kept.
 */
function boblift_indexnow_save_batch(array $post_ids, int $due, bool $retry): bool
{
    $kept = true;
    $saved = false;
    foreach (array_chunk(array_map('intval', $post_ids), BOBLIFT_INDEXNOW_ROW_IDS) as $ids) {
        $batch = ['due' => $due, 'ids' => $ids, 'retry' => $retry];
        if (add_option(boblift_indexnow_row_name($due), $batch, '', 'no')) {
            $saved = true;
        } else {
            // The row could not be written: carry the batch in the cron event itself so it is not lost.
            $kept = boblift_indexnow_schedule_event($due, [$batch]) && $kept;
        }
    }
    // All rows share one due time: one wake-up after they are all saved. The worker and the hourly sweep reconcile the rest.
    if ($saved) {
        boblift_indexnow_wake_at($due);
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

/**
 * @return string[] option names of saved batch rows in the current blog, earliest due first, at most $limit of them;
 * with $due_by, only rows due by then; with $after, only rows after that name (the next page of a scan).
 */
function boblift_indexnow_retry_names(int $limit = BOBLIFT_INDEXNOW_SCAN_ROWS, ?int $due_by = null, string $after = ''): array
{
    global $wpdb;
    $args = [$wpdb->esc_like(BOBLIFT_INDEXNOW_RETRY_PREFIX) . '%', $after === '' ? BOBLIFT_INDEXNOW_RETRY_PREFIX : $after];
    $where = 'option_name LIKE %s AND option_name > %s';
    // Only digits are compared at the bound, so the result does not depend on the table's collation.
    if ($due_by !== null) {
        $where .= ' AND option_name < %s';
        $args[] = BOBLIFT_INDEXNOW_RETRY_PREFIX . sprintf('%010d', $due_by + 1);
    }
    $args[] = $limit;
    $query = $wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE {$where} ORDER BY option_name ASC LIMIT %d", ...$args);
    return array_map('strval', (array) $wpdb->get_col($query));
}

/**
 * Walks batch rows earliest due first, page by page, for at most BOBLIFT_INDEXNOW_SCAN_PAGES pages; $visit returns false
 * to stop. Returns true when every row was visited or $visit stopped the walk, false when rows were left unread.
 */
function boblift_indexnow_walk_rows(?int $due_by, callable $visit): bool
{
    $after = '';
    for ($page = 0; $page < BOBLIFT_INDEXNOW_SCAN_PAGES; $page++) {
        $names = boblift_indexnow_retry_names(BOBLIFT_INDEXNOW_SCAN_ROWS, $due_by, $after);
        foreach ($names as $name) {
            $after = $name;
            if ($visit($name) === false) {
                return true;
            }
        }
        if (count($names) < BOBLIFT_INDEXNOW_SCAN_ROWS) {
            return true;
        }
    }
    return false;
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
 * Leases a due row: a compare-and-swap UPDATE from the name and value just read to a new name carrying the lease end
 * (so a leased row sorts by the time it can next run, behind the rows that can run now) and the same batch with the
 * lease time and a token of this run. Only one run wins. Returns [new name, leased value] (what this run owns), or
 * null when another run won.
 */
function boblift_indexnow_lease_retry(string $name, array $batch): ?array
{
    global $wpdb;
    $until = time() + BOBLIFT_INDEXNOW_LEASE;
    $leased_name = boblift_indexnow_row_name($until);
    $value = maybe_serialize(['due' => $batch['due'], 'ids' => $batch['ids'], 'retry' => $batch['retry'], 'lease' => $until, 'token' => wp_generate_uuid4()]);
    $won = (int) $wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_name = %s, option_value = %s WHERE option_name = %s AND option_value = %s", $leased_name, $value, $name, $batch['raw'])) === 1;
    wp_cache_delete($name, 'options');
    wp_cache_delete($leased_name, 'options');
    return $won ? [$leased_name, $value] : null;
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
 * Keeps one wake-up event at the earliest time a waiting batch can run. Reads rows earliest due first, at most
 * BOBLIFT_INDEXNOW_SCAN_PAGES pages, and stops as soon as the next row's due time is past the earliest runnable time
 * found (a row never runs before its due time). If rows remain unread, the wake-up is set no later than the due time of
 * the last row read (the earliest any unread row can run), but at least a minute out, so rows leased by stalled runs
 * cannot make it fire in a loop. A batch that could not be read counts as runnable in ten minutes.
 */
function boblift_indexnow_schedule_retry(): void
{
    $next = null;
    $last_due = null;
    $complete = boblift_indexnow_walk_rows(null, static function (string $name) use (&$next, &$last_due): bool {
        $last_due = boblift_indexnow_name_due($name);
        if ($next !== null && $last_due >= $next) {
            return false;
        }
        $batch = boblift_indexnow_read_retry($name);
        if ($batch === false) {
            boblift_indexnow_claim_retry($name);
            return true;
        }
        $at = $batch === null ? time() + 10 * MINUTE_IN_SECONDS : boblift_indexnow_runnable_at($batch);
        $next = $next === null ? $at : min($next, $at);
        return true;
    });
    if (!$complete && $last_due !== null) {
        $bound = max($last_due, time() + MINUTE_IN_SECONDS);
        $next = $next === null ? $bound : min($next, $bound);
    }
    if ($next !== null) {
        boblift_indexnow_wake_at($next);
    }
}

/**
 * Makes sure the one wake-up event runs no later than $at: an existing wake-up at or before it is reused (also when
 * it is overdue), otherwise it is moved to $at. Reads only the wake-up option, never the batch rows. The read, check
 * and replacement run under a short lock row held by a token, so two requests never both replace the same wake-up and
 * only the lock holder writes the cron option. A request that cannot get the lock within about two seconds leaves the
 * wake-up alone; its rows are already saved and the hourly sweep runs them.
 */
function boblift_indexnow_wake_at(int $at): void
{
    $next = max($at, time());
    $lock = boblift_indexnow_lock();
    for ($try = 1; $lock === null && $try < BOBLIFT_INDEXNOW_LOCK_TRIES; $try++) {
        usleep(100000);
        $lock = boblift_indexnow_lock();
    }
    if ($lock === null) {
        return;
    }
    // Read the wake-up and the cron array fresh from the database: this request may hold copies loaded before another
    // request changed them under the lock.
    foreach ([BOBLIFT_INDEXNOW_WAKE_OPTION, 'cron', 'alloptions', 'notoptions'] as $cached) {
        wp_cache_delete($cached, 'options');
    }
    $wake = (int) get_option(BOBLIFT_INDEXNOW_WAKE_OPTION, 0);
    $wake_exists = $wake > 0 && wp_next_scheduled(BOBLIFT_INDEXNOW_RETRY_HOOK, [['at' => $wake]]);
    // The replacement is scheduled first; the old wake-up is removed only once it is in place, so a failed cron write
    // never leaves the waiting batches without one.
    if (!($wake_exists && $wake <= $next) && boblift_indexnow_schedule_event($next, [['at' => $next]])) {
        if ($wake_exists) {
            wp_unschedule_event($wake, BOBLIFT_INDEXNOW_RETRY_HOOK, [['at' => $wake]]);
        }
        update_option(BOBLIFT_INDEXNOW_WAKE_OPTION, $next, false);
    }
    boblift_indexnow_unlock($lock);
}

/**
 * Takes the wake-up lock and returns this request's token, or null when another request holds it. The lock row is
 * created with INSERT IGNORE (atomic in the database, unlike add_option()); a lock older than 30 seconds belongs to a
 * request that died and is taken over with a compare-and-swap UPDATE, so only one request wins it.
 */
function boblift_indexnow_lock(): ?string
{
    global $wpdb;
    $token = time() . ':' . wp_generate_uuid4();
    $inserted = (int) $wpdb->query($wpdb->prepare("INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')", BOBLIFT_INDEXNOW_LOCK_OPTION, $token)) === 1;
    wp_cache_delete(BOBLIFT_INDEXNOW_LOCK_OPTION, 'options');
    if ($inserted) {
        return $token;
    }
    $held = $wpdb->get_row($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", BOBLIFT_INDEXNOW_LOCK_OPTION), ARRAY_A);
    // The token starts with the time it was taken; (int) reads that leading number.
    if (!is_array($held) || (int) $held['option_value'] > time() - 30) {
        return null;
    }
    $taken = (int) $wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s", $token, BOBLIFT_INDEXNOW_LOCK_OPTION, (string) $held['option_value'])) === 1;
    wp_cache_delete(BOBLIFT_INDEXNOW_LOCK_OPTION, 'options');
    return $taken ? $token : null;
}

/** Releases the lock only if it still holds this request's token, so a request whose lock was taken over leaves the new owner's lock alone. */
function boblift_indexnow_unlock(string $token): void
{
    global $wpdb;
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", BOBLIFT_INDEXNOW_LOCK_OPTION, $token));
    wp_cache_delete(BOBLIFT_INDEXNOW_LOCK_OPTION, 'options');
}

/**
 * Sends runnable batches, one row (at most BOBLIFT_INDEXNOW_ROW_IDS posts) at a time, within a budget of
 * BOBLIFT_INDEXNOW_RUN_ROWS rows and BOBLIFT_INDEXNOW_RUN_SECONDS per call; what is left is picked up by the wake-up
 * scheduled at the end. Each row is leased, checked to be still ours right before its POST, and deleted only by its
 * owner after the POST and any retry row were saved; if the retry could not be saved the row stays and runs again when
 * the lease ends. A row that could not be read, is not due, or is leased by a live run stays; a row that was read but
 * holds no valid batch is removed. Only rows already due are read, earliest first, at most BOBLIFT_INDEXNOW_SCAN_ROWS.
 * $carried is a batch that travelled in the cron event because its row could not be written; WP-Cron has already
 * removed that event, so the batch is saved as a row first and runs below like any other. If rows still cannot be
 * written it is carried again in an event ten minutes out; only if that also fails is it sent straight away, and a
 * failure there is logged.
 */
function boblift_indexnow_run_retries(array $carried = []): void
{
    $started = time();
    if (is_array($carried['ids'] ?? null)) {
        $ids = array_map('intval', $carried['ids']);
        $retry = (bool) ($carried['retry'] ?? false);
        if (!add_option(boblift_indexnow_row_name(time()), ['due' => time(), 'ids' => $ids, 'retry' => $retry], '', 'no')) {
            // Rows still cannot be written: carry it again, ten minutes later rather than at once, or send it now.
            $later = time() + 10 * MINUTE_IN_SECONDS;
            if (!boblift_indexnow_schedule_event($later, [['due' => $later, 'ids' => $ids, 'retry' => $retry]]) && !boblift_indexnow_send($ids, $retry)) {
                error_log(sprintf('IndexNow: a batch of %d posts could not be sent or saved.', count($ids)));
            }
        }
    }
    $rows = 0;
    boblift_indexnow_walk_rows(time(), static function (string $name) use (&$rows, $started): bool {
        if ($rows >= BOBLIFT_INDEXNOW_RUN_ROWS || time() - $started >= BOBLIFT_INDEXNOW_RUN_SECONDS) {
            return false;
        }
        $batch = boblift_indexnow_read_retry($name);
        if ($batch === false) {
            boblift_indexnow_claim_retry($name);
            return true;
        }
        if ($batch === null || boblift_indexnow_runnable_at($batch) > time()) {
            return true;
        }
        $lease = boblift_indexnow_lease_retry($name, $batch);
        if ($lease === null) {
            return true;
        }
        [$name, $leased] = $lease;
        if (!boblift_indexnow_owns_retry($name, $leased)) {
            return true;
        }
        $rows++;
        // Ownership is checked again right before each POST, after the permalinks are resolved.
        if (boblift_indexnow_send($batch['ids'], $batch['retry'], static fn(): bool => boblift_indexnow_owns_retry($name, $leased))) {
            boblift_indexnow_finish_retry($name, $leased);
        }
        return true;
    });
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
