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
 * publishing request); a cron run claims each due row with an atomic DELETE, checks each post again and sends the
 * permalinks in non-blocking POSTs of up to 10,000 URLs. A POST that cannot even be dispatched gets one more try ten
 * minutes later, in a row of its own. An hourly sweep picks up any row whose cron event was lost.
 */

const BOBLIFT_INDEXNOW_ENDPOINT = 'https://api.indexnow.org/indexnow';
const BOBLIFT_INDEXNOW_HOSTS = ['bobliftcrane.com'];
const BOBLIFT_INDEXNOW_POST_TYPES = ['page', 'post', 'product', 'case'];
const BOBLIFT_INDEXNOW_BATCH = 10000;
const BOBLIFT_INDEXNOW_KEY_OPTION = 'boblift_indexnow_key';
const BOBLIFT_INDEXNOW_RETRY_HOOK = 'boblift_indexnow_retry';
const BOBLIFT_INDEXNOW_RETRY_PREFIX = 'boblift_indexnow_retry_';
const BOBLIFT_INDEXNOW_SWEEP_HOOK = 'boblift_indexnow_sweep';

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
 * Sends the posts' current permalinks; runs from cron. When $retry is true, the IDs behind any POST that could not be
 * dispatched are saved for one more try ten minutes later; that try itself does not retry.
 */
function boblift_indexnow_send(array $post_ids, bool $retry = true): void
{
    if (!boblift_indexnow_enabled()) {
        return;
    }
    $by_url = boblift_indexnow_resolve($post_ids);
    if ($by_url === []) {
        return;
    }
    $key = boblift_indexnow_key();
    if ($key === '') {
        // The key could not be stored or read back: keep the posts for the retry instead of dropping them.
        if ($retry) {
            boblift_indexnow_save_batch(array_merge(...array_values($by_url)), time() + 10 * MINUTE_IN_SECONDS, false);
        }
        return;
    }
    foreach (boblift_indexnow_payloads(array_keys($by_url), (string) get_option('home'), $key) as $payload) {
        $response = wp_remote_post(BOBLIFT_INDEXNOW_ENDPOINT, [
            'blocking' => false,
            'timeout' => 3,
            'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
            'body' => wp_json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
        if (is_wp_error($response) && $retry) {
            // One retry row per failed POST, so a slow retry of one batch never takes the others down with it.
            $ids = [];
            foreach ($payload['urlList'] as $url) {
                array_push($ids, ...$by_url[$url]);
            }
            boblift_indexnow_save_batch(array_values(array_unique($ids)), time() + 10 * MINUTE_IN_SECONDS, false);
        }
    }
}

/**
 * Saves post IDs as a batch row of their own (a unique name, so concurrent requests never touch each other's row) and
 * makes sure a cron run is scheduled. $retry says whether a failed POST of this batch gets one more try.
 */
function boblift_indexnow_save_batch(array $post_ids, int $due, bool $retry): void
{
    $batch = ['due' => $due, 'ids' => array_map('intval', $post_ids), 'retry' => $retry];
    if (!add_option(BOBLIFT_INDEXNOW_RETRY_PREFIX . str_replace('-', '', wp_generate_uuid4()), $batch, '', 'no')) {
        // The row could not be written: carry the batch in the cron event itself so it is not lost.
        boblift_indexnow_schedule_event($due, [$batch]);
        return;
    }
    boblift_indexnow_schedule_retry();
}

/** Schedules a run of the batch worker; a failure is logged, and the hourly sweep still finds any saved row. */
function boblift_indexnow_schedule_event(int $time, array $args = []): void
{
    $scheduled = wp_schedule_single_event($time, BOBLIFT_INDEXNOW_RETRY_HOOK, $args, true);
    if ($scheduled !== true) {
        error_log(sprintf('IndexNow: could not schedule a batch run (%s).', is_wp_error($scheduled) ? $scheduled->get_error_message() : 'cron write failed'));
    }
}

/** @return string[] option names of saved retry batches in the current blog */
function boblift_indexnow_retry_names(): array
{
    global $wpdb;
    return array_map('strval', (array) $wpdb->get_col($wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like(BOBLIFT_INDEXNOW_RETRY_PREFIX) . '%')));
}

/**
 * Reads a saved batch. Returns null when the read itself failed (the row is kept for later), false when the row was
 * read but holds no valid batch, otherwise ['due' => int, 'ids' => int[], 'retry' => bool].
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
    return ['due' => (int) $batch['due'], 'ids' => array_map('intval', $batch['ids']), 'retry' => (bool) ($batch['retry'] ?? false)];
}

/** Claims a row with an atomic DELETE; true only for the request whose DELETE removed it. */
function boblift_indexnow_claim_retry(string $name): bool
{
    global $wpdb;
    $claimed = (int) $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name = %s", $name)) === 1;
    wp_cache_delete($name, 'options');
    return $claimed;
}

/**
 * Makes sure a run is scheduled at the earliest waiting batch's due time, even when a later run is already scheduled.
 * The event carries that time as its argument, so runs at different times never collide. A batch that could not be
 * read counts as due in ten minutes.
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
        $due = $batch === null ? time() + 10 * MINUTE_IN_SECONDS : max(time(), $batch['due']);
        $next = $next === null ? $due : min($next, $due);
    }
    if ($next !== null && !wp_next_scheduled(BOBLIFT_INDEXNOW_RETRY_HOOK, [['at' => $next]])) {
        boblift_indexnow_schedule_event($next, [['at' => $next]]);
    }
}

/**
 * Sends each due batch once, after claiming its row. A row that could not be read, or is not due yet, stays for a
 * later run; a row that was read but holds no valid batch is removed so it cannot keep the worker alive. $carried is a
 * batch that travelled in the cron event because its row could not be written.
 */
function boblift_indexnow_run_retries(array $carried = []): void
{
    if (is_array($carried['ids'] ?? null)) {
        boblift_indexnow_send(array_map('intval', $carried['ids']), (bool) ($carried['retry'] ?? false));
    }
    foreach (boblift_indexnow_retry_names() as $name) {
        $batch = boblift_indexnow_read_retry($name);
        if ($batch === false) {
            boblift_indexnow_claim_retry($name);
            continue;
        }
        if ($batch === null || $batch['due'] > time()) {
            continue;
        }
        if (boblift_indexnow_claim_retry($name)) {
            boblift_indexnow_send($batch['ids'], $batch['retry']);
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
