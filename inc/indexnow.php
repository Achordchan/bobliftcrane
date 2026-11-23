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
 * check that the ping comes from the site owner. Only the live site (a host in BOBLIFT_INDEXNOW_HOSTS, public, production)
 * pings; local copies, staging clones and other hosts stay quiet. Changed posts are queued by ID during the request and
 * resolved at shutdown, after every term and meta field is saved, then sent in non-blocking POSTs of up to 10,000 URLs.
 */

const BOBLIFT_INDEXNOW_ENDPOINT = 'https://api.indexnow.org/indexnow';
const BOBLIFT_INDEXNOW_HOSTS = ['bobliftcrane.com'];
const BOBLIFT_INDEXNOW_POST_TYPES = ['page', 'post', 'product', 'case'];
const BOBLIFT_INDEXNOW_BATCH = 10000;

function boblift_indexnow_valid_key(mixed $key): bool
{
    return is_string($key) && preg_match('/^[a-zA-Z0-9-]{8,128}$/', $key) === 1;
}

function boblift_indexnow_key(): string
{
    $key = get_option('boblift_indexnow_key', '');
    if (boblift_indexnow_valid_key($key)) {
        return $key;
    }
    if ($key !== '' && $key !== false) {
        delete_option('boblift_indexnow_key');
    }
    // add_option() inserts only when the option is absent, so two first requests agree on whichever key was stored first.
    add_option('boblift_indexnow_key', strtolower(wp_generate_password(32, false, false)), '', true);
    wp_cache_delete('boblift_indexnow_key', 'options');
    wp_cache_delete('alloptions', 'options');
    return (string) get_option('boblift_indexnow_key', '');
}

/** The home host without IPv6 brackets or a trailing DNS dot, lower case. */
function boblift_indexnow_host(string $url): string
{
    return rtrim(trim(strtolower((string) wp_parse_url($url, PHP_URL_HOST)), '[]'), '.');
}

function boblift_indexnow_enabled(): bool
{
    return get_option('blog_public')
        && wp_get_environment_type() === 'production'
        && in_array(boblift_indexnow_host((string) get_option('home')), BOBLIFT_INDEXNOW_HOSTS, true);
}

/** Post IDs changed during this request; adding is constant time. */
function boblift_indexnow_queue_add(int $post_id): void
{
    $GLOBALS['boblift_indexnow_queue'][$post_id] = true;
}

function boblift_indexnow_queued_ids(): array
{
    return array_keys($GLOBALS['boblift_indexnow_queue'] ?? []);
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
    if ($host === '') {
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

add_action('transition_post_status', static function (string $new_status, string $old_status, WP_Post $post): void {
    if ($new_status !== 'publish' || !in_array($post->post_type, BOBLIFT_INDEXNOW_POST_TYPES, true)) {
        return;
    }
    if (wp_is_post_revision($post) || wp_is_post_autosave($post) || !boblift_indexnow_enabled()) {
        return;
    }
    boblift_indexnow_queue_add((int) $post->ID);
}, 10, 3);

add_action('shutdown', static function (): void {
    $ids = boblift_indexnow_queued_ids();
    if ($ids === [] || !boblift_indexnow_enabled()) {
        return;
    }
    $urls = [];
    foreach ($ids as $id) {
        clean_post_cache($id);
        $post = get_post($id);
        if (!boblift_indexnow_post_qualifies($post)) {
            continue;
        }
        $url = get_permalink($post);
        if (is_string($url) && $url !== '') {
            $urls[] = $url;
        }
    }
    foreach (boblift_indexnow_payloads($urls, (string) get_option('home'), boblift_indexnow_key()) as $payload) {
        wp_remote_post(BOBLIFT_INDEXNOW_ENDPOINT, [
            'blocking' => false,
            'timeout' => 3,
            'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
            'body' => wp_json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
    }
});

// The key file, answered ahead of the legacy redirects (priority 0) and the 404 template.
add_action('template_redirect', static function (): void {
    $request = rtrim((string) wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/');
    if (preg_match('#/[a-zA-Z0-9-]{8,128}\.txt$#', $request) !== 1 || !boblift_indexnow_enabled()) {
        return;
    }
    $base = rtrim((string) wp_parse_url((string) get_option('home'), PHP_URL_PATH), '/');
    $key = boblift_indexnow_key();
    if ($request !== $base . '/' . $key . '.txt') {
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
