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
 * minutes later, in a row of its own. A recurring worker event runs every minute; publishing requests only write
 * batch rows and never touch the shared cron option, so they cannot race WordPress or other plugins scheduling there.
 */

const BOBLIFT_INDEXNOW_ENDPOINT = 'https://api.indexnow.org/indexnow';
const BOBLIFT_INDEXNOW_HOSTS = ['bobliftcrane.com'];
const BOBLIFT_INDEXNOW_POST_TYPES = ['page', 'post', 'product', 'case'];
const BOBLIFT_INDEXNOW_BATCH = 10000;
const BOBLIFT_INDEXNOW_KEY_OPTION = 'boblift_indexnow_key';
const BOBLIFT_INDEXNOW_RETRY_PREFIX = 'boblift_indexnow_retry_';
const BOBLIFT_INDEXNOW_SWEEP_HOOK = 'boblift_indexnow_sweep';
const BOBLIFT_INDEXNOW_INTERVAL = 'boblift_indexnow_minute';
const BOBLIFT_INDEXNOW_LEASE = 15 * 60;
// One row is one step of the worker: 100 posts resolved and sent in one POST, so a row never runs long past the budget.
const BOBLIFT_INDEXNOW_ROW_IDS = 100;
const BOBLIFT_INDEXNOW_RUN_ROWS = 200;
// Leased rows are sent together, up to this many post IDs per request, so many small rows cost few requests.
const BOBLIFT_INDEXNOW_GROUP_IDS = 1000;
defined('BOBLIFT_INDEXNOW_RUN_SECONDS') || define('BOBLIFT_INDEXNOW_RUN_SECONDS', 60);
// Most batch rows read per page of a worker scan.
const BOBLIFT_INDEXNOW_SCAN_ROWS = 100;
// Pages of BOBLIFT_INDEXNOW_SCAN_ROWS one scan may read, so rows leased by stalled runs cannot hide the rows behind them.
const BOBLIFT_INDEXNOW_SCAN_PAGES = 5;

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

const BOBLIFT_INDEXNOW_PLATFORM_SITE = 'boblift_indexnow_platform_site';

/**
 * True only on the live hosting. WordPress.com's Atomic platform defines ATOMIC_SITE_ID in its own configuration, outside
 * the database, so a copy of the database or theme running anywhere else (a local clone mapped to the live host name,
 * for instance) stays quiet. The site's ID is recorded by the first web request for the live host name (which
 * WordPress.com routes only to the live site); from then on only that Atomic site submits, so a backup restored to
 * another Atomic site (same home URL, production environment) stays quiet too. Another live host can opt in with define('BOBLIFT_INDEXNOW_LIVE', true) in wp-config.php.
 */
function boblift_indexnow_live_platform(bool $queue_only = false): bool
{
    // Strict checks: a constant set to the string 'false', '0' or '' must not switch submissions on.
    if (boblift_indexnow_flag('BOBLIFT_INDEXNOW_LIVE')) {
        return true;
    }
    if (!defined('ATOMIC_SITE_ID')) {
        return false;
    }
    $site = filter_var(constant('ATOMIC_SITE_ID'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($site === false) {
        return false;
    }
    $pinned = get_option(BOBLIFT_INDEXNOW_PLATFORM_SITE);
    if ($pinned === false) {
        // The ID is recorded only by a web request that WordPress.com routed here for the live host name: the domain is
        // attached to the live site alone, so a copy (reached on its own address, or running cron or WP-CLI) can never
        // record its ID, even from a database backup taken before the ID was first recorded.
        $host = boblift_indexnow_host('https://' . (string) ($_SERVER['HTTP_HOST'] ?? ''));
        if (wp_doing_cron() || (defined('WP_CLI') && WP_CLI) || !in_array($host, BOBLIFT_INDEXNOW_HOSTS, true)) {
            // Until then changes are still queued (a publish right after deploying is not lost), but nothing is sent:
            // the worker and send() need the recorded ID.
            return $queue_only;
        }
        // add_option() never replaces a value another request stored first; whichever is stored is what counts.
        add_option(BOBLIFT_INDEXNOW_PLATFORM_SITE, (string) $site, '', 'yes');
        $pinned = get_option(BOBLIFT_INDEXNOW_PLATFORM_SITE);
    }
    return (string) $pinned === (string) $site;
}

/** True only for a defined constant that is boolean true, 1, or a string such as 'true', '1', 'yes' or 'on'. */
function boblift_indexnow_flag(string $name): bool
{
    if (!defined($name)) {
        return false;
    }
    $value = constant($name);
    if (!is_bool($value) && !is_int($value) && !is_string($value)) {
        return false;
    }
    return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === true;
}

/**
 * Whether this site submits. With $queue_only, changes may be queued before the live site's ID has been recorded (they
 * are sent once it is); sending always needs the full check.
 */
function boblift_indexnow_enabled(bool $queue_only = false): bool
{
    // WordPress.com stores -1 for a private site, so only an explicit '1' counts as public.
    // The platform check comes last: it records the live site's ID, which only the live site may do.
    return (string) get_option('blog_public') === '1'
        && wp_get_environment_type() === 'production'
        && in_array(boblift_indexnow_host((string) get_option('home')), BOBLIFT_INDEXNOW_HOSTS, true)
        && boblift_indexnow_live_platform($queue_only);
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
 * the directory the key file is served from (the key only verifies URLs there; one URL outside it fails the batch), and
 * no longer than IndexNow's 2,048-character limit.
 */
/** The advertised key-file URL, always on HTTPS (the scheme IndexNow fetches), even if home is stored as http. */
function boblift_indexnow_key_url(string $home, string $key): string
{
    return (string) preg_replace('#^http://#i', 'https://', rtrim($home, '/')) . '/' . $key . '.txt';
}

function boblift_indexnow_payloads(array $urls, string $home, string $key): array
{
    $host = boblift_indexnow_host($home);
    if ($host === '' || !boblift_indexnow_valid_key($key)) {
        return [];
    }
    $scope = rtrim((string) wp_parse_url($home, PHP_URL_PATH), '/') . '/';
    $urls = array_values(array_unique($urls));
    $same_host = array_values(array_filter($urls, static fn(string $url): bool => boblift_indexnow_host($url) === $host && str_starts_with(((string) wp_parse_url($url, PHP_URL_PATH)) ?: '/', $scope)));
    // This site serves every language under its own host (/es/, /fr/ ...). A permalink on another host or outside the
    // key's directory (for example if Polylang were switched to subdomains or separate domains) cannot be verified by
    // this key, so it is left out and logged, so such a change is noticed instead of silently skipped.
    $elsewhere = array_diff($urls, $same_host);
    if ($elsewhere) {
        error_log(sprintf('IndexNow: %d URLs outside %s left out (another host or directory), e.g. %s.', count($elsewhere), rtrim($home, '/') . '/', (string) reset($elsewhere)));
    }
    // IndexNow rejects the whole POST (422) when one URL is longer than 2,048 characters: leave those out and log them.
    $too_long = array_filter($same_host, static fn(string $url): bool => strlen($url) > 2048);
    if ($too_long) {
        error_log(sprintf('IndexNow: %d URLs longer than 2,048 characters left out.', count($too_long)));
        $same_host = array_values(array_diff($same_host, $too_long));
    }
    $payloads = [];
    foreach (array_chunk($same_host, BOBLIFT_INDEXNOW_BATCH) as $batch) {
        $payloads[] = [
            'host' => $host,
            'key' => $key,
            'keyLocation' => boblift_indexnow_key_url($home, $key),
            'urlList' => $batch,
        ];
    }
    return $payloads;
}

/**
 * @return array<string, int[]>|null permalink => post IDs for the queued posts that still qualify, read in the current
 * blog; null when a post could not be read, so the caller keeps the batch. Existence is checked in one query per chunk
 * (a failed query is told apart from a deleted post by wpdb's last_error), then the post, term and meta caches are
 * primed for the chunk so permalinks do not cost one round trip per post. Chunks not started by $until (when given) are
 * returned in $unresolved.
 */
function boblift_indexnow_resolve(array $post_ids, int $until = 0, array &$unresolved = []): ?array
{
    global $wpdb;
    $ids = array_values(array_unique(array_filter(array_map('intval', $post_ids), static fn(int $id): bool => $id > 0)));
    $urls = [];
    foreach (array_chunk($ids, 100) as $n => $chunk) {
        // With a time limit, chunks not started by then are handed back unresolved, so a large group still sends what
        // was resolved instead of using up the whole run.
        if ($until !== 0 && $n > 0 && time() >= $until) {
            $unresolved = array_merge(...array_slice(array_chunk($ids, 100), $n));
            break;
        }
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
        // Drop this chunk's primed posts, terms and meta from the request-local cache before the next one, so a run of
        // many rows never holds them all at once (the persistent object cache, if any, keeps them).
        // Only where the cache can drop its request-local copies alone: elsewhere wp_cache_flush_runtime() falls back to
        // flushing the whole shared cache.
        if (function_exists('wp_cache_flush_runtime') && function_exists('wp_cache_supports') && wp_cache_supports('flush_runtime')) {
            wp_cache_flush_runtime();
        }
    }
    return $urls;
}

/** Post IDs behind the URLs of the given payloads. */
function boblift_indexnow_payload_ids(array $payloads, array $by_url): array
{
    $ids = [];
    foreach ($payloads as $payload) {
        foreach ($payload['urlList'] as $url) {
            array_push($ids, ...$by_url[$url]);
        }
    }
    return array_values(array_unique($ids));
}

/**
 * Sends the posts' current permalinks; runs from cron. When $retry is true, the IDs behind a POST that failed (no
 * connection, 429 or 5xx) are kept for one more try ten minutes later; that try itself does not retry. Returns false
 * when nothing should be acknowledged: submissions are off (or the settings could not be read), the posts could not be
 * read, or such a retry could not be kept, so the caller keeps its copy of the batch.
 * $still_owned, when given, is checked right before each POST; once it says the batch was taken over, nothing more is
 * sent and false is returned (the run that took it over sends it).
 * $keep(int[] $ids, int $due, bool $retry): bool keeps IDs for a later try; by default they are saved as new batch
 * rows. The worker passes its own, so it can move its leased row instead of adding a second one.
 */
const BOBLIFT_INDEXNOW_HTTP_TIMEOUT = 10;

/** Seconds a run keeps free for one POST: the HTTP timeout, or a third of a shorter run budget. */
function boblift_indexnow_post_margin(): int
{
    return min(BOBLIFT_INDEXNOW_HTTP_TIMEOUT, intdiv(BOBLIFT_INDEXNOW_RUN_SECONDS, 3));
}

/** The site's search visibility read from the database, not the request's cached options. */
function boblift_indexnow_still_public(): bool
{
    global $wpdb;
    $value = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", 'blog_public'));
    return (string) $value === '1';
}

function boblift_indexnow_send(array $post_ids, bool $retry = true, ?callable $still_owned = null, ?callable $keep = null, int $deadline = 0): bool
{
    if (!boblift_indexnow_enabled()) {
        return false;
    }
    $keep ??= 'boblift_indexnow_save_batch';
    $margin = boblift_indexnow_post_margin();
    $unresolved = [];
    // Resolving stops early enough to leave time for the POST; the rest stays queued, runnable at once.
    $by_url = boblift_indexnow_resolve($post_ids, $deadline !== 0 ? $deadline - 2 * $margin : 0, $unresolved);
    if ($by_url === null) {
        return false;
    }
    $kept = $unresolved === [] || (bool) $keep($unresolved, time(), $retry);
    if ($by_url === []) {
        return $kept;
    }
    $key = boblift_indexnow_key();
    if ($key === '') {
        // The key could not be stored or read back, so nothing was submitted: the batch is deferred ten minutes with
        // its retry allowance unchanged (only a real POST uses up an attempt).
        return (bool) $keep(array_merge(...array_values($by_url)), time() + 10 * MINUTE_IN_SECONDS, $retry) && $kept;
    }
    $payloads = boblift_indexnow_payloads(array_keys($by_url), (string) get_option('home'), $key);
    foreach ($payloads as $index => $payload) {
        if ($still_owned !== null && !$still_owned()) {
            return false;
        }
        // The worker's time is up (resolving permalinks can be slow on a cold cache): the rest is kept, runnable again at
        // once with its retry allowance unchanged, instead of starting another blocking POST.
        // Another run may have hit a 429 or 403 meanwhile: then nothing more is posted and the rest is kept.
        $cooldown = boblift_indexnow_cooldown_until();
        if ($cooldown !== 0) {
            return (bool) $keep(boblift_indexnow_payload_ids(array_slice($payloads, $index), $by_url), max(time() + MINUTE_IN_SECONDS, (int) $cooldown), $retry) && $kept;
        }
        // The site may have been made private while permalinks were resolved: read the setting afresh, keep the posts.
        if (!boblift_indexnow_still_public()) {
            return (bool) $keep(boblift_indexnow_payload_ids(array_slice($payloads, $index), $by_url), time() + HOUR_IN_SECONDS, $retry) && $kept;
        }
        // Checked last, right before the POST (the queries above can stall). A POST only starts when its full timeout fits
        // in the run; otherwise the posts wait for the next run without using up a try.
        if ($deadline !== 0 && time() > $deadline - $margin) {
            return (bool) $keep(boblift_indexnow_payload_ids(array_slice($payloads, $index), $by_url), time(), $retry) && $kept;
        }
        // Blocking, from cron only: a non-blocking request can hide a failed connection.
        $response = wp_remote_post(BOBLIFT_INDEXNOW_ENDPOINT, [
            'timeout' => BOBLIFT_INDEXNOW_HTTP_TIMEOUT,
            'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
            'body' => wp_json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
        $code = is_wp_error($response) ? 0 : (int) wp_remote_retrieve_response_code($response);
        // IndexNow answers 200 or 202. 429 (throttled) and 403 (key not verified) keep the posts and pause the queue;
        // 5xx and no connection are worth one more try; other 4xx (bad URLs) are not.
        if ($code === 429) {
            // Throttled: IndexNow asks the client to wait. This and every later payload of the batch are kept, with the
            // retry allowance unchanged (being asked to wait is not a failed try), and the whole queue pauses for the
            // Retry-After time, so other rows are not spent while the endpoint is still throttling. Retention is
            // deliberate and bounded in rate: under the shared pause the whole site makes at most one POST per
            // Retry-After window (10 minutes to a day), so posts are never lost to throttling and never hammer the API.
            $wait = boblift_indexnow_retry_after($response);
            boblift_indexnow_extend_cooldown(time() + $wait);
            return (bool) $keep(boblift_indexnow_payload_ids(array_slice($payloads, $index), $by_url), time() + $wait, $retry) && $kept;
        }
        if ($code === 403) {
            // Key not verified. The local self-check cannot prove IndexNow's verifier can reach the file (a CDN or
            // firewall may block it), so the posts are kept, retry allowance unchanged, and the queue pauses for an hour
            // (logged), so fixing access never needs the content republished.
            $held = (bool) $keep(boblift_indexnow_payload_ids(array_slice($payloads, $index), $by_url), time() + HOUR_IN_SECONDS, $retry);
            boblift_indexnow_extend_cooldown(time() + HOUR_IN_SECONDS);
            error_log(sprintf('IndexNow: key not verified (HTTP 403) for %s; submissions paused for an hour, posts kept.', $payload['keyLocation']));
            return $held && $kept;
        }
        if (($code === 0 || $code >= 500) && $retry) {
            $ids = [];
            foreach ($payload['urlList'] as $url) {
                array_push($ids, ...$by_url[$url]);
            }
            $kept = (bool) $keep(array_values(array_unique($ids)), time() + 10 * MINUTE_IN_SECONDS, false) && $kept;
        } elseif ($code < 200 || $code >= 300) {
            // Not retried (a bad key or URL list, or the last try failed): logged, so a broken key route is noticed.
            $why = is_wp_error($response) ? 'no connection: ' . $response->get_error_message() : ($code === 0 ? 'no HTTP status' : 'HTTP ' . $code);
            error_log(sprintf('IndexNow: %d URLs not accepted (%s).', count($payload['urlList']), $why));
        }
    }
    return $kept;
}

/**
/**
 * A batch row's option name: the due time, zero-padded so names sort by it, then a unique suffix so concurrent requests
 * never touch each other's rows. Sorting by name lets the worker read the earliest rows first.
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
 * touch each other's rows, and each row is small enough for one worker step); the every-minute worker runs them once
 * due. $retry says whether a failed POST of the batch gets one more try. Nothing here writes the shared cron option:
 * a row that cannot be written is tried once more, then its post IDs are logged and false is returned.
 */
function boblift_indexnow_save_batch(array $post_ids, int $due, bool $retry): bool
{
    $kept = true;
    foreach (array_chunk(array_map('intval', $post_ids), BOBLIFT_INDEXNOW_ROW_IDS) as $ids) {
        $batch = ['due' => $due, 'ids' => $ids, 'retry' => $retry];
        // The every-minute worker picks the row up once it is due; nothing else is scheduled.
        if (!add_option(boblift_indexnow_row_name($due), $batch, '', 'no') && !add_option(boblift_indexnow_row_name($due), $batch, '', 'no')) {
            error_log(sprintf('IndexNow: could not save a batch row; posts not submitted: %s.', implode(',', $ids)));
            $kept = false;
        }
    }
    return $kept;
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

/**
 * Moves a leased row to a later due time with new IDs and retry allowance, in one compare-and-swap UPDATE, so a failed
 * batch is never in two rows at once. Does nothing (false) if the row no longer holds this run's lease.
 */
function boblift_indexnow_reschedule_retry(string $name, string $leased, array $ids, int $due, bool $retry): bool
{
    global $wpdb;
    $new_name = boblift_indexnow_row_name($due);
    $value = maybe_serialize(['due' => $due, 'ids' => array_values(array_map('intval', $ids)), 'retry' => $retry]);
    $done = (int) $wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_name = %s, option_value = %s WHERE option_name = %s AND option_value = %s", $new_name, $value, $name, $leased)) === 1;
    wp_cache_delete($name, 'options');
    wp_cache_delete($new_name, 'options');
    return $done;
}

/** When the row can next be run: its due time, or the end of a lease still held by another run. */
function boblift_indexnow_runnable_at(array $batch): int
{
    return max($batch['due'], $batch['lease']);
}

const BOBLIFT_INDEXNOW_KEY_CHECK = 'boblift_indexnow_key_file_checked';
const BOBLIFT_INDEXNOW_COOLDOWN = 'boblift_indexnow_cooldown_until';

/**
 * End of the shared submission pause (0 when none), read from the database so every worker sees the same value. When the
 * read fails, a pause cannot be ruled out, so a short one (a minute) is assumed: nothing is posted and the posts are kept.
 */
function boblift_indexnow_cooldown_until(): int
{
    global $wpdb;
    $wpdb->last_error = '';
    $value = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", BOBLIFT_INDEXNOW_COOLDOWN));
    if ($value === null && $wpdb->last_error !== '') {
        return time() + 60;
    }
    $until = max((int) $value, (int) ($GLOBALS['boblift_indexnow_local_pause'] ?? 0));
    return $until > time() ? $until : 0;
}

/**
 * Pauses submissions until $until. The deadline only ever moves later: it is created with INSERT IGNORE or raised with
 * a conditional UPDATE in one statement, so a shorter Retry-After from an overlapping worker cannot cut a longer pause.
 */
function boblift_indexnow_extend_cooldown(int $until): void
{
    global $wpdb;
    // Kept in this request too, so the current run stops even if the shared value cannot be written.
    $GLOBALS['boblift_indexnow_local_pause'] = max((int) ($GLOBALS['boblift_indexnow_local_pause'] ?? 0), $until);
    $inserted = $wpdb->query($wpdb->prepare("INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')", BOBLIFT_INDEXNOW_COOLDOWN, (string) $until));
    if ((int) $inserted === 1) {
        return;
    }
    $updated = $wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND CAST(option_value AS UNSIGNED) < %d", (string) $until, BOBLIFT_INDEXNOW_COOLDOWN, $until));
    wp_cache_delete(BOBLIFT_INDEXNOW_COOLDOWN, 'options');
    if ($inserted === false || $updated === false) {
        error_log(sprintf('IndexNow: the submission pause until %s could not be saved; other runs may not see it.', gmdate('c', $until)));
    }
}

/** The key-check result is stored per advertised key-file URL, so a new key or home URL is always checked afresh. */
function boblift_indexnow_key_check_name(string $key): string
{
    return BOBLIFT_INDEXNOW_KEY_CHECK . '_' . md5(boblift_indexnow_key_url((string) get_option('home'), $key));
}

/**
 * Seconds IndexNow asked the client to wait (Retry-After as seconds or an HTTP date), at least ten minutes and at most a
 * day; ten minutes when the header is missing or unreadable.
 */
function boblift_indexnow_retry_after(mixed $response): int
{
    $header = is_wp_error($response) ? '' : trim((string) wp_remote_retrieve_header($response, 'retry-after'));
    $wait = ctype_digit($header) ? (int) $header : ($header !== '' && ($at = strtotime($header)) !== false ? $at - time() : 0);
    return min(DAY_IN_SECONDS, max(10 * MINUTE_IN_SECONDS, $wait));
}

/**
 * Fetches the advertised key file over HTTPS, as IndexNow will, and logs when it does not answer 200 with exactly the
 * key (for example when the host serves its own 404 for .txt paths before WordPress runs). Returns whether it did.
 */
function boblift_indexnow_check_key_file(string $home, string $key): bool
{
    $location = boblift_indexnow_key_url($home, $key);
    $response = wp_remote_get($location, ['timeout' => 10, 'redirection' => 0]);
    $code = is_wp_error($response) ? 0 : (int) wp_remote_retrieve_response_code($response);
    $ok = $code === 200 && (string) wp_remote_retrieve_body($response) === $key;
    if (!$ok) {
        error_log(sprintf('IndexNow: the key file %s did not answer 200 with the key (%s); submissions will be rejected until it does.', $location, is_wp_error($response) ? 'no connection' : 'HTTP ' . $code));
    }
    return $ok;
}

/**
 * Whether the key file was served correctly at the last check. Checked through the real HTTP stack once a day while it
 * works and hourly while it fails, or now when $recheck is set.
 */
function boblift_indexnow_key_file_ok(string $key, bool $recheck = false): bool
{
    $state = $recheck ? false : get_transient(boblift_indexnow_key_check_name($key));
    if ($state === false) {
        $ok = boblift_indexnow_check_key_file((string) get_option('home'), $key);
        $state = $ok ? 'ok' : 'failed';
        set_transient(boblift_indexnow_key_check_name($key), $state, $ok ? DAY_IN_SECONDS : HOUR_IN_SECONDS);
    }
    return $state === 'ok';
}

/**
 * Sends runnable batches, one row (at most BOBLIFT_INDEXNOW_ROW_IDS posts) at a time, within a budget of
 * BOBLIFT_INDEXNOW_RUN_ROWS rows and BOBLIFT_INDEXNOW_RUN_SECONDS per call, rows sent together up to
 * BOBLIFT_INDEXNOW_GROUP_IDS posts per request; what is left is picked up by the next run a
 * minute later. Each row is leased, checked to be still ours right before its POST, and then either deleted by its
 * owner or, when its posts get one more try, moved to the retry's due time in the same compare-and-swap UPDATE. A row that could not be read, is not due, or is leased by a live run stays; a row that was read but
 * holds no valid batch is removed. Only rows already due are read, earliest first, a page of
 * BOBLIFT_INDEXNOW_SCAN_ROWS at a time.
 */
function boblift_indexnow_run_retries(): void
{
    // While submissions are off (or the settings could not be read) rows are left alone, not acknowledged.
    if (!boblift_indexnow_enabled()) {
        return;
    }
    // The daily key-file self-check is a diagnostic only (it logs a failure): a loopback request can be blocked by a
    // firewall while IndexNow still reaches the file. Rows are held back only when IndexNow itself answers 403 and the
    // recheck fails too (see boblift_indexnow_send()).
    $key = boblift_indexnow_key();
    if ($key !== '') {
        boblift_indexnow_key_file_ok($key);
    }
    // After a 429 nothing is posted until the wait IndexNow asked for (Retry-After) has passed.
    if (boblift_indexnow_cooldown_until() !== 0) {
        return;
    }
    $started = time();
    $rows = 0;
    // Leased rows wait in a group per retry allowance and go out together; each row is still acknowledged on its own.
    $groups = [0 => [], 1 => []];
    $group_ids = [0 => 0, 1 => 0];
    boblift_indexnow_walk_rows(time(), static function (string $name) use (&$rows, $started, &$groups, &$group_ids): bool {
        // Leasing stops early enough to leave time for resolving and sending what was leased.
        if ($rows >= BOBLIFT_INDEXNOW_RUN_ROWS || time() - $started >= BOBLIFT_INDEXNOW_RUN_SECONDS - min(20, intdiv(BOBLIFT_INDEXNOW_RUN_SECONDS, 2))) {
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
        $flag = $batch['retry'] ? 1 : 0;
        // A group is sent before a row that would take it past the cap joins it (a row holds at most 100 posts).
        $stop = false;
        if ($groups[$flag] !== [] && $group_ids[$flag] + count($batch['ids']) > BOBLIFT_INDEXNOW_GROUP_IDS) {
            if (time() - $started >= BOBLIFT_INDEXNOW_RUN_SECONDS) {
                // Time is up (leasing can be slow): nothing more is sent; the final step below releases every leased row.
                $groups[$flag][] = [$name, $leased, $batch['ids']];
                return false;
            }
            boblift_indexnow_send_group($groups[$flag], (bool) $flag, $started + BOBLIFT_INDEXNOW_RUN_SECONDS);
            $groups[$flag] = [];
            $group_ids[$flag] = 0;
            // A 429 or 403 starts a cooldown, which stops this run: later rows would only be rejected the same way.
            $stop = boblift_indexnow_cooldown_until() !== 0;
        }
        $groups[$flag][] = [$name, $leased, $batch['ids']];
        $group_ids[$flag] += count($batch['ids']);
        return !$stop;
    });
    // What is left goes out now; during a cooldown send() posts nothing and keeps the rows for its end. Once the run's
    // time is used up, rows not yet sent are released (runnable again at once) instead of waiting out their lease.
    foreach ($groups as $flag => $group) {
        if ($group === []) {
            continue;
        }
        if (time() - $started >= BOBLIFT_INDEXNOW_RUN_SECONDS) {
            foreach ($group as [$name, $leased, $ids]) {
                boblift_indexnow_reschedule_retry($name, $leased, $ids, time(), (bool) $flag);
            }
            continue;
        }
        boblift_indexnow_send_group($group, (bool) $flag, $started + BOBLIFT_INDEXNOW_RUN_SECONDS);
    }
}

/**
 * Sends the post IDs of several leased rows (same retry allowance) in one go. Ownership of every row is checked right
 * before each POST. Afterwards each row is acknowledged on its own: deleted when none of its posts need another try,
 * otherwise moved to the retry's due time with just those posts, in one compare-and-swap UPDATE (never a second row).
 *
 * @param array<int, array{0: string, 1: string, 2: int[]}> $group [row name, leased value, post IDs]
 */
function boblift_indexnow_send_group(array $group, bool $retry, int $deadline = 0): void
{
    $again = [];
    $again_due = 0;
    $again_retry = false;
    $collect = static function (array $ids, int $due, bool $retry) use (&$again, &$again_due, &$again_retry): bool {
        array_push($again, ...$ids);
        $again_due = max($again_due, $due);
        $again_retry = $retry;
        return true;
    };
    $owned = static function () use ($group): bool {
        foreach ($group as [$name, $leased]) {
            if (!boblift_indexnow_owns_retry($name, $leased)) {
                return false;
            }
        }
        return true;
    };
    $ids = array_values(array_unique(array_merge(...array_map(static fn(array $row): array => $row[2], $group))));
    if (!boblift_indexnow_send($ids, $retry, $owned, $collect, $deadline)) {
        // Nothing acknowledged: the rows stay leased and run again when their leases end.
        return;
    }
    $again = array_flip($again);
    foreach ($group as [$name, $leased, $row_ids]) {
        $left = array_values(array_filter($row_ids, static fn(int $id): bool => isset($again[$id])));
        if ($left === []) {
            boblift_indexnow_finish_retry($name, $leased);
        } else {
            boblift_indexnow_reschedule_retry($name, $leased, $left, $again_due, $again_retry);
        }
    }
}

add_action('transition_post_status', static function (string $new_status, string $old_status, WP_Post $post): void {
    if ($new_status !== 'publish' || !in_array($post->post_type, BOBLIFT_INDEXNOW_POST_TYPES, true)) {
        return;
    }
    if (wp_is_post_revision($post) || wp_is_post_autosave($post) || !boblift_indexnow_enabled(true)) {
        return;
    }
    boblift_indexnow_queue_add((int) $post->ID, (int) get_current_blog_id());
}, 10, 3);

// Custom fields change the page too, even when edited without a status change (WP-CLI, imports, the REST API). This
// theme keeps most of its page content in underscore keys (_seo_title, _seo_description, _specs, _model, _gallery and
// more), so every key counts except WordPress's and plugins' own bookkeeping, which never reaches the page.
const BOBLIFT_INDEXNOW_IGNORED_META = ['_edit_lock', '_edit_last', '_encloseme', '_pingme', '_wp_old_slug', '_wp_old_date', '_wp_desired_post_slug', '_wp_trash_meta_status', '_wp_trash_meta_time', '_wp_attachment_metadata', '_wp_attached_file'];
const BOBLIFT_INDEXNOW_IGNORED_META_PREFIXES = ['_oembed_', '_jetpack_', '_publicize_', '_wpas_', '_transient_'];

function boblift_indexnow_meta_is_bookkeeping(string $meta_key): bool
{
    if ($meta_key === '' || in_array($meta_key, BOBLIFT_INDEXNOW_IGNORED_META, true)) {
        return true;
    }
    foreach (BOBLIFT_INDEXNOW_IGNORED_META_PREFIXES as $prefix) {
        if (str_starts_with($meta_key, $prefix)) {
            return true;
        }
    }
    return false;
}

// The per-request queue removes duplicates.
$boblift_indexnow_meta_changed = static function (mixed $meta_id, int $post_id, string $meta_key): void {
    if (boblift_indexnow_meta_is_bookkeeping($meta_key)) {
        return;
    }
    $post = get_post($post_id);
    if (!$post instanceof WP_Post || $post->post_status !== 'publish' || !in_array($post->post_type, BOBLIFT_INDEXNOW_POST_TYPES, true)) {
        return;
    }
    if (wp_is_post_revision($post) || !boblift_indexnow_enabled(true)) {
        return;
    }
    boblift_indexnow_queue_add((int) $post->ID, (int) get_current_blog_id());
};
// Public terms (categories, tags, product categories) shown on the page: a change made on its own, without a status or
// field change (wp post term add, a bulk edit), queues the post. Saves that set the same terms again are ignored.
function boblift_indexnow_terms_changed(int $object_id, string $taxonomy): void
{
    // Polylang's language taxonomy is not public, but it decides the URL's language directory, so it counts too.
    $tax = get_taxonomy($taxonomy);
    if ($taxonomy !== 'language' && (!$tax || empty($tax->public))) {
        return;
    }
    $post = get_post($object_id);
    if (!$post instanceof WP_Post || !boblift_indexnow_post_qualifies($post) || wp_is_post_revision($post) || !boblift_indexnow_enabled(true)) {
        return;
    }
    boblift_indexnow_queue_add((int) $post->ID, (int) get_current_blog_id());
}
// Only real changes count: added_term_relationship fires once per term actually attached (re-adding a term the post
// already has, or re-saving the same terms, fires nothing), deleted_term_relationships once per removal.
add_action('added_term_relationship', static function (int $object_id, int $tt_id, string $taxonomy): void {
    boblift_indexnow_terms_changed($object_id, $taxonomy);
}, 10, 3);
add_action('deleted_term_relationships', static function (int $object_id, array $tt_ids, string $taxonomy): void {
    if ($tt_ids !== []) {
        boblift_indexnow_terms_changed($object_id, $taxonomy);
    }
}, 10, 3);
add_action('added_post_meta', $boblift_indexnow_meta_changed, 10, 3);
add_action('updated_post_meta', $boblift_indexnow_meta_changed, 10, 3);
add_action('deleted_post_meta', $boblift_indexnow_meta_changed, 10, 3);

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
add_action(BOBLIFT_INDEXNOW_SWEEP_HOOK, 'boblift_indexnow_run_retries');

add_filter('cron_schedules', static function (array $schedules): array {
    $schedules[BOBLIFT_INDEXNOW_INTERVAL] = ['interval' => MINUTE_IN_SECONDS, 'display' => 'Every minute (IndexNow)'];
    return $schedules;
});

const BOBLIFT_INDEXNOW_SCHEDULE_LOCK = 'boblift_indexnow_schedule_lock';

/** Takes the scheduling lock: an INSERT IGNORE, or a compare-and-swap over a lock left for more than five minutes. */
function boblift_indexnow_take_schedule_lock(): ?string
{
    global $wpdb;
    $mine = time() . '_' . wp_generate_uuid4();
    if ((int) $wpdb->query($wpdb->prepare("INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')", BOBLIFT_INDEXNOW_SCHEDULE_LOCK, $mine)) === 1) {
        return $mine;
    }
    $held = (string) $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", BOBLIFT_INDEXNOW_SCHEDULE_LOCK));
    if ($held === '' || (int) $held > time() - 5 * MINUTE_IN_SECONDS) {
        return null;
    }
    return (int) $wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s", $mine, BOBLIFT_INDEXNOW_SCHEDULE_LOCK, $held)) === 1 ? $mine : null;
}

/**
 * Keeps exactly one worker event while submissions are on and none while they are off, through the WordPress cron API
 * (so a host's own cron backend sees the same events its runner runs). It runs from cron requests and from
 * front-end page views (so the first event is added even when no other cron job is due), never from admin, AJAX or REST
 * requests, where posts are published, and it writes only when the event is missing or no longer wanted.
 * Changes are made under a lock after reloading the autoloaded options, so concurrent requests do not add the event
 * twice. If another request later saves a stale cron array and drops the event, the next cron run or page view adds it
 * back; queued rows are kept in their own options meanwhile, so nothing is lost.
 */
function boblift_indexnow_sync_worker(): void
{
    $enabled = boblift_indexnow_enabled();
    // wp_next_scheduled() goes through pre_get_scheduled_event, so a host's own cron backend answers it.
    if ($enabled === (wp_next_scheduled(BOBLIFT_INDEXNOW_SWEEP_HOOK) !== false)) {
        return;
    }
    $lock = boblift_indexnow_take_schedule_lock();
    if ($lock === null) {
        return;
    }
    // Reread the options from the database (another request may have just changed cron) before deciding.
    // The default object cache ignores wp_load_alloptions()'s force flag, so the cached copies are dropped explicitly.
    wp_cache_delete('cron', 'options');
    wp_cache_delete('alloptions', 'options');
    if (function_exists('wp_load_alloptions')) {
        wp_load_alloptions();
    }
    $scheduled = wp_next_scheduled(BOBLIFT_INDEXNOW_SWEEP_HOOK) !== false;
    // Only a missing event is added and an unwanted one cleared. Extra occurrences are left alone: WP-Cron itself
    // briefly holds two while it reschedules a recurring event, and a second worker run is harmless (rows are leased).
    if ($enabled && !$scheduled) {
        wp_schedule_event(time() + MINUTE_IN_SECONDS, BOBLIFT_INDEXNOW_INTERVAL, BOBLIFT_INDEXNOW_SWEEP_HOOK);
    } elseif (!$enabled && $scheduled) {
        wp_clear_scheduled_hook(BOBLIFT_INDEXNOW_SWEEP_HOOK);
    }
    global $wpdb;
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", BOBLIFT_INDEXNOW_SCHEDULE_LOCK, $lock));
}

// The worker runs every minute and picks up whatever rows are due; it is the only regular cron write this file makes.
// When the site stops submitting (made private, or a copy off the live platform), the worker event is removed.
// WP-CLI is left out on purpose: an import or bulk publish run from the command line never writes the cron option.
add_action('init', static function (): void {
    if (wp_doing_cron()) {
        boblift_indexnow_sync_worker();
    }
});
// 'wp' fires only for front-end page requests (REST requests are answered before it, admin and AJAX never reach it).
add_action('wp', static function (): void {
    if (in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) {
        boblift_indexnow_sync_worker();
    }
});

// Switching away from this theme drops its cron events, so nothing keeps running without a callback.
add_action('switch_theme', static function (): void {
    wp_clear_scheduled_hook(BOBLIFT_INDEXNOW_SWEEP_HOOK);
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
