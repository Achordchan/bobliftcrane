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
 * check that the ping comes from the site owner. Only a public production site pings; local copies and staging stay
 * quiet. All URLs changed in one request go out in a single non-blocking POST at the end of the request.
 */

const BOBLIFT_INDEXNOW_ENDPOINT = 'https://api.indexnow.org/indexnow';

function boblift_indexnow_key(): string
{
    $key = (string) get_option('boblift_indexnow_key', '');
    if (preg_match('/^[a-zA-Z0-9-]{8,128}$/', $key) !== 1) {
        $key = strtolower(wp_generate_password(32, false, false));
        update_option('boblift_indexnow_key', $key, true);
    }
    return $key;
}

function boblift_indexnow_enabled(): bool
{
    if (!get_option('blog_public') || wp_get_environment_type() !== 'production') {
        return false;
    }
    $host = strtolower((string) wp_parse_url((string) get_option('home'), PHP_URL_HOST));
    return $host !== ''
        && $host !== 'localhost'
        && filter_var($host, FILTER_VALIDATE_IP) === false
        && !str_ends_with($host, '.local')
        && !str_ends_with($host, '.test');
}

/** URLs queued during this request, keyed by URL so each goes out once. */
function boblift_indexnow_queue(?string $url = null): array
{
    static $queue = [];
    if ($url !== null) {
        $queue[$url] = true;
    }
    return array_keys($queue);
}

/** The request body for the queued URLs, or null when there is nothing on this site's host to send. */
function boblift_indexnow_payload(array $urls, string $home, string $key): ?array
{
    $host = strtolower((string) wp_parse_url($home, PHP_URL_HOST));
    $same_host = array_values(array_filter($urls, static fn(string $url): bool => strtolower((string) wp_parse_url($url, PHP_URL_HOST)) === $host));
    if ($host === '' || $same_host === []) {
        return null;
    }
    return [
        'host' => $host,
        'key' => $key,
        'keyLocation' => rtrim($home, '/') . '/' . $key . '.txt',
        'urlList' => array_slice($same_host, 0, 10000),
    ];
}

add_action('transition_post_status', static function (string $new_status, string $old_status, WP_Post $post): void {
    if ($new_status !== 'publish' || !is_post_type_viewable($post->post_type) || !boblift_indexnow_enabled()) {
        return;
    }
    if (wp_is_post_revision($post) || wp_is_post_autosave($post) || post_password_required($post)) {
        return;
    }
    $url = get_permalink($post);
    if (is_string($url) && $url !== '') {
        boblift_indexnow_queue($url);
    }
}, 10, 3);

add_action('shutdown', static function (): void {
    $urls = boblift_indexnow_queue();
    if ($urls === [] || !boblift_indexnow_enabled()) {
        return;
    }
    $payload = boblift_indexnow_payload($urls, (string) get_option('home'), boblift_indexnow_key());
    if ($payload === null) {
        return;
    }
    wp_remote_post(BOBLIFT_INDEXNOW_ENDPOINT, [
        'blocking' => false,
        'timeout' => 3,
        'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
        'body' => wp_json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ]);
});

// The key file, answered ahead of the legacy redirects (priority 0) and the 404 template.
add_action('template_redirect', static function (): void {
    $request = rtrim((string) wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/');
    if (preg_match('#/[a-zA-Z0-9-]{8,128}\.txt$#', $request) !== 1) {
        return;
    }
    if (!boblift_indexnow_enabled()) {
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
