<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Old-site (SaaS) URLs -> new-site 301 redirects.
 *
 * Rules live in the `boblift_legacy_redirects` option; while the option is empty the shipped
 * inc/legacy-redirects.json is used. Maintain them over REST (manage_options required):
 *   GET    /wp-json/boblift/v1/legacy-redirects
 *   POST   /wp-json/boblift/v1/legacy-redirects   {"rules":[{"from","to","note","enabled"}], "replace":false}
 *   DELETE /wp-json/boblift/v1/legacy-redirects?from=/old-path.html   (or ?reset=1 to go back to the shipped file)
 */
const BOBLIFT_LEGACY_REDIRECTS_OPTION = 'boblift_legacy_redirects';

function boblift_legacy_redirects_default_config(): array
{
    $file = get_template_directory() . '/inc/legacy-redirects.json';
    $data = is_readable($file) ? json_decode((string) file_get_contents($file), true) : null;
    return is_array($data) ? $data : ['rules' => []];
}

/** True once an administrator has saved rules, even an empty set; only the reset operation returns to the shipped file. */
function boblift_legacy_redirects_has_custom(): bool
{
    $saved = get_option(BOBLIFT_LEGACY_REDIRECTS_OPTION, null);
    return is_array($saved) && isset($saved['rules']) && is_array($saved['rules']);
}

function boblift_legacy_redirects_config(): array
{
    $default = boblift_legacy_redirects_default_config();
    if (boblift_legacy_redirects_has_custom()) {
        return array_merge($default, get_option(BOBLIFT_LEGACY_REDIRECTS_OPTION));
    }
    return $default;
}

function boblift_legacy_redirect_normalize_path(string $path): string
{
    $path = rawurldecode($path);
    $path = function_exists('mb_strtolower') ? mb_strtolower($path, 'UTF-8') : strtolower($path);
    $path = '/' . ltrim(preg_replace('#/+#', '/', $path) ?? '', '/');
    return $path === '/' ? '/' : rtrim($path, '/');
}

/** Language label when $host is one of the old language subdomains (fr.bobliftcrane.com), else ''. */
function boblift_legacy_redirect_language_host(string $host, array $config): string
{
    $domain = strtolower((string) ($config['legacy_domain'] ?? ''));
    $host = strtolower(preg_replace('/:\d+$/', '', $host) ?? '');
    if ($domain === '' || !str_ends_with($host, '.' . $domain)) {
        return '';
    }
    $label = substr($host, 0, -strlen($domain) - 1);
    return in_array($label, (array) ($config['language_hosts'] ?? []), true) ? $label : '';
}

/**
 * Returns the absolute redirect target for a legacy request, or null when the request is not legacy.
 * $path_exists tells whether a path is a page the new site really serves; without it the same-path
 * fallback for language hosts is off and those requests go to the home page.
 * Pure function: no WordPress state besides the config argument.
 */
function boblift_legacy_redirect_resolve(string $host, string $path, array $config, string $site_base, ?callable $path_exists = null): ?string
{
    $norm = boblift_legacy_redirect_normalize_path($path);
    $language = boblift_legacy_redirect_language_host($host, $config);
    $base = $language !== '' ? 'https://' . ($config['canonical_host'] ?? parse_url($site_base, PHP_URL_HOST)) : rtrim($site_base, '/');

    $target = null;
    foreach ((array) ($config['rules'] ?? []) as $rule) {
        if (!is_array($rule) || empty($rule['from']) || empty($rule['to']) || ($rule['enabled'] ?? true) === false) {
            continue;
        }
        if (boblift_legacy_redirect_normalize_path((string) $rule['from']) === $norm) {
            $target = (string) $rule['to'];
            break;
        }
    }

    if ($target === null && $language !== '') {
        $decoded = rawurldecode($path);
        foreach ((array) ($config['language_host_fallbacks'] ?? []) as $fallback) {
            if (!empty($fallback['match']) && !empty($fallback['to']) && @preg_match('~' . str_replace('~', '\~', (string) $fallback['match']) . '~iu', $decoded) === 1) {
                $target = (string) $fallback['to'];
                break;
            }
        }
        if ($target === null && $norm !== '/' && $path_exists !== null && !empty($config['language_host_same_path']) && $path_exists($norm) && @preg_match('~' . str_replace('~', '\~', (string) $config['language_host_same_path']) . '~', $norm) === 1) {
            $target = $norm; // English-slug pages that exist under the same path on the new site
        }
        $target ??= (string) ($config['language_host_default'] ?? '/');
        if ($norm === '/') {
            $target = '/';
        }
    }

    if ($target === null) {
        return null;
    }
    $url = preg_match('#^https?://#i', $target) ? $target : $base . '/' . ltrim($target, '/');
    if ($language === '') {
        // Already on the exact target host and path (case-only rules must not loop, whatever the query says).
        $parts = parse_url($url);
        $request_host = strtolower(preg_replace('/:\d+$/', '', $host) ?? '');
        $same_host = in_array(strtolower((string) ($parts['host'] ?? '')), [$request_host, strtolower((string) parse_url($site_base, PHP_URL_HOST))], true);
        $target_path = rtrim(rawurldecode((string) ($parts['path'] ?? '/')), '/');
        if ($same_host && $target_path === rtrim(rawurldecode($path), '/')) {
            return null;
        }
    }
    return $url;
}

/**
 * Language subdomains cannot reach WordPress when the new site is hosted on WordPress.com, so a
 * Cloudflare rule sends fr.bobliftcrane.com/path to https://www.bobliftcrane.com/path?legacy_lang=fr.
 * That label stands in for the original host here.
 */
function boblift_legacy_redirect_effective_host(string $host, array $query, array $config): string
{
    $label = isset($query['legacy_lang']) && is_string($query['legacy_lang']) ? strtolower($query['legacy_lang']) : '';
    if ($label !== '' && in_array($label, (array) ($config['language_hosts'] ?? []), true) && !empty($config['legacy_domain'])) {
        return $label . '.' . $config['legacy_domain'];
    }
    return $host;
}

/** Adds the visitor's query parameters (utm, gclid...) to the destination; the destination's own parameters win, the fragment stays last. */
function boblift_legacy_redirect_merge_query(string $target, array $params): string
{
    if (!$params) {
        return $target;
    }
    $fragment = '';
    if (($hash = strpos($target, '#')) !== false) {
        $fragment = substr($target, $hash);
        $target = substr($target, 0, $hash);
    }
    $base = $target;
    $own = [];
    if (($mark = strpos($target, '?')) !== false) {
        $base = substr($target, 0, $mark);
        parse_str(substr($target, $mark + 1), $own);
    }
    $query = http_build_query($own + $params);
    return $base . ($query !== '' ? '?' . $query : '') . $fragment;
}

/**
 * True when $path is a URL the new site currently serves as the canonical address of its own content,
 * so a legacy rule must not take it over (admin-editable public URLs, products, pages, categories).
 */
function boblift_legacy_redirect_is_live_path(string $path): bool
{
    $raw = rtrim(rawurldecode($path), '/');
    if ($raw === '') {
        return true; // the home page
    }
    if (function_exists('boblift_public_custom_url_routes') && isset(boblift_public_custom_url_routes()[ltrim($raw, '/')])) {
        return true;
    }
    $slug = sanitize_title((string) preg_replace('/\.html$/i', '', ltrim($raw, '/')));
    if ($slug === '' || str_contains($slug, '/')) {
        return false;
    }
    foreach (['page', 'product', 'post'] as $type) {
        $post = get_page_by_path($slug, OBJECT, $type);
        if ($post instanceof WP_Post && $post->post_status === 'publish' && rtrim(rawurldecode((string) parse_url(get_permalink($post), PHP_URL_PATH)), '/') === $raw) {
            return true;
        }
    }
    foreach (['product_category', 'category'] as $taxonomy) {
        $term = get_term_by('slug', $slug, $taxonomy);
        if ($term instanceof WP_Term) {
            $link = get_term_link($term);
            if (!is_wp_error($link) && rtrim(rawurldecode((string) parse_url($link, PHP_URL_PATH)), '/') === $raw) {
                return true;
            }
        }
    }
    return false;
}

function boblift_legacy_redirects_run(): void
{
    if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true) || (defined('WP_CLI') && WP_CLI)) {
        return;
    }
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $path = (string) parse_url($uri, PHP_URL_PATH);
    if (preg_match('#^/(wp-admin|wp-json|wp-login\.php|wp-content|wp-includes|wp-cron\.php|xmlrpc\.php)#', $path) || isset($_GET['rest_route'])) {
        return;
    }
    $config = boblift_legacy_redirects_config();
    $host = boblift_legacy_redirect_effective_host((string) ($_SERVER['HTTP_HOST'] ?? ''), $_GET, $config);
    $target = boblift_legacy_redirect_resolve($host, $path, $config, home_url(), 'boblift_legacy_redirect_is_live_path');
    if ($target === null || (!isset($_GET['legacy_lang']) && boblift_legacy_redirect_is_live_path($path))) {
        return;
    }
    parse_str((string) parse_url($uri, PHP_URL_QUERY), $params);
    unset($params['legacy_lang']);
    $target = boblift_legacy_redirect_merge_query($target, $params);
    header('X-Redirect-By: boblift-legacy-redirects');
    wp_redirect($target, 301, 'BOB-LIFT legacy');
    exit;
}
add_action('init', 'boblift_legacy_redirects_run', 1);

/** Returns a description of the first redirect cycle among enabled site-internal rules, or null. */
function boblift_legacy_redirects_find_cycle(array $rules): ?string
{
    $next = [];
    foreach ($rules as $rule) {
        if (($rule['enabled'] ?? true) === false || empty($rule['from']) || empty($rule['to']) || ($rule['to'][0] ?? '') !== '/') {
            continue;
        }
        $from = boblift_legacy_redirect_normalize_path((string) $rule['from']);
        $to = boblift_legacy_redirect_normalize_path((string) (parse_url((string) $rule['to'], PHP_URL_PATH) ?? '/'));
        if ($from !== $to) { // a case-only rule maps a path onto itself; the runtime guard handles it
            $next[$from] = $to;
        }
    }
    foreach (array_keys($next) as $start) {
        $seen = [$start => true];
        for ($node = $next[$start]; isset($next[$node]); $node = $next[$node]) {
            if (isset($seen[$node])) {
                return $start . ' -> ... -> ' . $node;
            }
            $seen[$node] = true;
        }
    }
    return null;
}

function boblift_legacy_redirects_sanitize_rule($rule): ?array
{
    if (!is_array($rule)) {
        return null;
    }
    $from = trim((string) ($rule['from'] ?? ''));
    $to = trim((string) ($rule['to'] ?? ''));
    if ($from === '' || $from[0] !== '/' || str_contains($from, '..') || strlen($from) > 500) {
        return null;
    }
    if ($to === '' || strlen($to) > 500 || !($to[0] === '/' || preg_match('#^https://[a-z0-9.-]+(/|$)#i', $to))) {
        return null;
    }
    $clean = ['from' => $from, 'to' => $to, 'note' => sanitize_text_field((string) ($rule['note'] ?? ''))];
    if (array_key_exists('enabled', $rule) && !filter_var($rule['enabled'], FILTER_VALIDATE_BOOLEAN)) {
        $clean['enabled'] = false;
    }
    return $clean;
}

function boblift_legacy_redirects_rest_register(): void
{
    $permission = static fn() => current_user_can('manage_options');
    register_rest_route('boblift/v1', '/legacy-redirects', [
        [
            'methods' => 'GET',
            'permission_callback' => $permission,
            'callback' => static function () {
                return ['source' => boblift_legacy_redirects_has_custom() ? 'custom' : 'default', 'count' => count(boblift_legacy_redirects_config()['rules']), 'config' => boblift_legacy_redirects_config()];
            },
        ],
        [
            'methods' => 'POST',
            'permission_callback' => $permission,
            'callback' => static function (WP_REST_Request $request) {
                $incoming = (array) $request->get_param('rules');
                $clean = [];
                $rejected = [];
                foreach ($incoming as $rule) {
                    $item = boblift_legacy_redirects_sanitize_rule($rule);
                    $item ? $clean[] = $item : $rejected[] = $rule;
                }
                if ($rejected) {
                    return new WP_Error('invalid_rules', 'Some rules are invalid; nothing was saved.', ['status' => 400, 'rejected' => $rejected]);
                }
                $current = boblift_legacy_redirects_config();
                $existing = $request->get_param('replace') ? [] : (array) $current['rules'];
                $by_from = [];
                foreach (array_merge($existing, $clean) as $rule) {
                    $by_from[boblift_legacy_redirect_normalize_path((string) $rule['from'])] = $rule;
                }
                $cycle = boblift_legacy_redirects_find_cycle(array_values($by_from));
                if ($cycle !== null) {
                    return new WP_Error('redirect_cycle', 'These rules would create a redirect cycle (' . $cycle . '); nothing was saved.', ['status' => 400]);
                }
                update_option(BOBLIFT_LEGACY_REDIRECTS_OPTION, ['rules' => array_values($by_from)], false);
                return ['saved' => count($clean), 'total' => count($by_from)];
            },
        ],
        [
            'methods' => 'DELETE',
            'permission_callback' => $permission,
            'callback' => static function (WP_REST_Request $request) {
                if ($request->get_param('reset')) {
                    delete_option(BOBLIFT_LEGACY_REDIRECTS_OPTION);
                    return ['reset' => true];
                }
                $from = boblift_legacy_redirect_normalize_path((string) $request->get_param('from'));
                $rules = array_values(array_filter(boblift_legacy_redirects_config()['rules'], static fn($rule) => boblift_legacy_redirect_normalize_path((string) $rule['from']) !== $from));
                update_option(BOBLIFT_LEGACY_REDIRECTS_OPTION, ['rules' => $rules], false);
                return ['removed' => $from, 'total' => count($rules)];
            },
        ],
    ]);
}
add_action('rest_api_init', 'boblift_legacy_redirects_rest_register');
