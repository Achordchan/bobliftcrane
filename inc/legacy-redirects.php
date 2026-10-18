<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Old-site (SaaS) URLs -> new-site 301 redirects.
 *
 * The shipped inc/legacy-redirects.json holds the default rules. Administrators change them over REST
 * (manage_options required); the `boblift_legacy_redirects` option stores only those changes (rules added
 * or edited, and paths removed), so rules the administrator never touched keep following the shipped file
 * when a theme update changes them.
 *   GET    /wp-json/boblift/v1/legacy-redirects
 *   POST   /wp-json/boblift/v1/legacy-redirects   {"rules":[{"from","to","note","enabled"}], "replace":false}
 *   DELETE /wp-json/boblift/v1/legacy-redirects?from=/old-path.html   (or ?reset=1 to go back to the shipped file)
 */
const BOBLIFT_LEGACY_REDIRECTS_OPTION = 'boblift_legacy_redirects';

// Old "Crane by Ton" page path (/5-ton-crane or /5-ton-crane.html) => tonnage filter bin key, independent of the bin labels
// shown in the filter. Used by boblift_redirect_old_tonnage_pages() for whatever the redirect rules do not catch.
function boblift_old_tonnage_bin(string $path): ?string
{
    $path = strtolower(trim($path, '/'));
    if (!preg_match('/^(\d+)-ton-crane(?:\.html)?$/', $path, $m)) {
        return null;
    }
    $bins = [1 => '0-1', 2 => '1-2', 3 => '2-3', 4 => '3-4', 5 => '4-5', 6 => '5-6', 8 => '6-8', 10 => '8-10', 12 => '10-12', 16 => '12-16', 20 => '16-20', 25 => '20-25'];
    return $bins[(int) $m[1]] ?? null;
}

function boblift_legacy_redirects_default_config(): array
{
    $file = get_template_directory() . '/inc/legacy-redirects.json';
    $data = is_readable($file) ? json_decode((string) file_get_contents($file), true) : null;
    return is_array($data) ? $data : ['rules' => []];
}

/**
 * The saved changes as ['overrides' => rules, 'removed' => normalized paths, 'replace' => bool], or null.
 * `replace` means the saved overrides are the whole rule set (POST with replace, or an option written before
 * changes were stored separately: that old format was a full snapshot and is read as such).
 */
function boblift_legacy_redirects_saved(): ?array
{
    $saved = get_option(BOBLIFT_LEGACY_REDIRECTS_OPTION, null);
    if (!is_array($saved)) {
        return null;
    }
    if (($saved['format'] ?? 0) === 2) {
        return [
            'overrides' => array_values(array_filter((array) ($saved['overrides'] ?? []), 'is_array')),
            'removed' => array_values(array_map('strval', (array) ($saved['removed'] ?? []))),
            'replace' => !empty($saved['replace']),
        ];
    }
    if (isset($saved['rules']) && is_array($saved['rules'])) {
        return ['overrides' => array_values(array_filter($saved['rules'], 'is_array')), 'removed' => [], 'replace' => true];
    }
    return null;
}

/** True once an administrator has saved changes, even an empty replacement set; only reset returns to the shipped file. */
function boblift_legacy_redirects_has_custom(): bool
{
    return boblift_legacy_redirects_saved() !== null;
}

/** Shipped rules with the saved changes applied: an override replaces the shipped rule with the same path. */
function boblift_legacy_redirects_apply(array $default_rules, array $saved): array
{
    $by_from = [];
    if (!$saved['replace']) {
        $removed = array_flip($saved['removed']);
        foreach ($default_rules as $rule) {
            if (!is_array($rule) || empty($rule['from'])) {
                continue;
            }
            $key = boblift_legacy_redirect_normalize_path((string) $rule['from']);
            if (!isset($removed[$key])) {
                $by_from[$key] = $rule;
            }
        }
    }
    foreach ($saved['overrides'] as $rule) {
        if (!empty($rule['from'])) {
            $by_from[boblift_legacy_redirect_normalize_path((string) $rule['from'])] = $rule;
        }
    }
    return array_values($by_from);
}

function boblift_legacy_redirects_config(): array
{
    $default = boblift_legacy_redirects_default_config();
    $saved = boblift_legacy_redirects_saved();
    if ($saved !== null) {
        $default['rules'] = boblift_legacy_redirects_apply((array) ($default['rules'] ?? []), $saved);
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
        $exact = rtrim($decoded, '/');
        // An English page that really exists under the same path wins over the generic suffix fallbacks.
        if ($exact !== '' && $path_exists !== null && !empty($config['language_host_same_path']) && @preg_match('~' . str_replace('~', '\~', (string) $config['language_host_same_path']) . '~i', $exact) === 1 && $path_exists($exact)) {
            $target = $exact;
        }
        foreach ($target === null ? (array) ($config['language_host_fallbacks'] ?? []) : [] as $fallback) {
            if (!empty($fallback['match']) && !empty($fallback['to']) && @preg_match('~' . str_replace('~', '\~', (string) $fallback['match']) . '~iu', $exact) === 1) {
                $target = (string) $fallback['to'];
                break;
            }
        }
        $target ??= (string) ($config['language_host_default'] ?? '/');
        if ($norm === '/') {
            $target = '/';
        }
    }

    if ($target === null) {
        return null;
    }
    if ($language !== '' && !preg_match('#^https?://#i', $target)) {
        // Language subdomains land in their own language directory once that language is live.
        $prefix = trim((string) (((array) ($config['language_host_prefix'] ?? []))[$language] ?? ''), '/');
        if ($prefix !== '' && $path_exists !== null) {
            // Only once that language really serves the page; until then the English target stays (no 404 on a 301).
            $localized = '/' . $prefix . '/' . ltrim($target, '/');
            $localized_path = rtrim((string) parse_url($localized, PHP_URL_PATH), '/');
            if ($path_exists($localized_path)) {
                $target = $localized;
            }
        }
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
    if (function_exists('pll_languages_list') && in_array(ltrim($raw, '/'), (array) pll_languages_list(['fields' => 'slug']), true)) {
        return true; // a language home such as /ru
    }
    // A language directory (/fr/contact.html): the remainder names the English route, which is live in that
    // language only when a published translation of its page or term exists there.
    $rest = $raw;
    $lang = '';
    if (function_exists('pll_languages_list') && preg_match('#^/([^/]+)(/.+)$#', $raw, $m) && in_array($m[1], (array) pll_languages_list(['fields' => 'slug']), true)) {
        $lang = $m[1];
        $rest = $m[2];
    }
    if (function_exists('boblift_public_custom_url_routes')) {
        $route = boblift_public_custom_url_routes()[ltrim($rest, '/')] ?? null;
        if (is_string($route) && $lang === '') {
            return true;
        }
        if (is_string($route) && preg_match('/^(page|category|product_category):(\d+)$/', $route, $r)) {
            if ($r[1] === 'page') {
                $id = function_exists('pll_get_post') ? (int) pll_get_post((int) $r[2], $lang) : 0;
                if ($id > 0 && get_post_status($id) === 'publish') {
                    return true;
                }
            } elseif (function_exists('pll_get_term') && (int) pll_get_term((int) $r[2], $lang) > 0) {
                return true;
            }
        }
    }
    $post_id = url_to_postid(home_url($raw));
    if ($post_id > 0 && get_post_status($post_id) === 'publish' && rtrim(rawurldecode((string) parse_url(get_permalink($post_id), PHP_URL_PATH)), '/') === $raw) {
        return true; // case, feedback and other post types served under their own prefix
    }
    $slug = sanitize_title((string) preg_replace('/\.html$/i', '', ltrim($rest, '/')));
    if ($slug === '' || str_contains($slug, '/')) {
        return false;
    }
    // Translations can share a slug across languages, so every published holder of the slug is checked, in any language.
    foreach (['page', 'product', 'post', 'case'] as $type) {
        $posts = get_posts(['post_type' => $type, 'name' => $slug, 'post_status' => 'publish', 'posts_per_page' => -1, 'lang' => '']);
        foreach ($posts as $post) {
            if (rtrim(rawurldecode((string) parse_url(get_permalink($post), PHP_URL_PATH)), '/') === $raw) {
                return true;
            }
        }
    }
    foreach (['product_category', 'category'] as $taxonomy) {
        $terms = get_terms(['taxonomy' => $taxonomy, 'slug' => $slug, 'hide_empty' => false, 'lang' => '']);
        foreach (is_array($terms) ? $terms : [] as $term) {
            $link = $term instanceof WP_Term ? get_term_link($term) : '';
            if ($link && !is_wp_error($link) && rtrim(rawurldecode((string) parse_url($link, PHP_URL_PATH)), '/') === $raw) {
                return true;
            }
        }
    }
    return false;
}

/**
 * An old path typed under a language directory (/fr/Products-Marine-Cranes.html): apply the rules to the
 * remainder and keep the language when that language serves the target, else use the English target.
 */
function boblift_legacy_redirect_resolve_in_language_directory(string $host, string $path, array $config): ?string
{
    if (!function_exists('pll_languages_list') || boblift_legacy_redirect_language_host($host, $config) !== '') {
        return null;
    }
    if (!preg_match('#^/([^/]+)(/.+)$#', $path, $m) || !in_array($m[1], (array) pll_languages_list(['fields' => 'slug']), true) || boblift_legacy_redirect_is_live_path($path)) {
        return null;
    }
    $target = boblift_legacy_redirect_resolve($host, $m[2], $config, home_url(), 'boblift_legacy_redirect_is_live_path');
    if ($target === null) {
        return null;
    }
    $parts = parse_url($target);
    if (strtolower((string) ($parts['host'] ?? '')) !== strtolower((string) parse_url(home_url(), PHP_URL_HOST))) {
        return $target; // another site, such as bobliftloader.com
    }
    $localized = '/' . $m[1] . '/' . ltrim((string) ($parts['path'] ?? '/'), '/');
    if (!boblift_legacy_redirect_is_live_path($localized)) {
        return $target;
    }
    return home_url($localized) . (isset($parts['query']) ? '?' . $parts['query'] : '');
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
    if ($target === null) {
        $target = boblift_legacy_redirect_resolve_in_language_directory($host, $path, $config);
    }
    // Only the new site's own host is protected; a legacy language host always redirects, even where the path exists here.
    if ($target === null || (boblift_legacy_redirect_language_host($host, $config) === '' && boblift_legacy_redirect_is_live_path($path))) {
        return;
    }
    parse_str((string) parse_url($uri, PHP_URL_QUERY), $params);
    unset($params['legacy_lang']);
    $target = boblift_legacy_redirect_merge_query($target, $params);
    header('X-Redirect-By: boblift-legacy-redirects');
    wp_redirect($target, 301, 'BOB-LIFT legacy');
    exit;
}
// template_redirect (not init): post types and taxonomies are registered by then, which the live-path guard needs.
add_action('template_redirect', 'boblift_legacy_redirects_run', 0);

/** Returns a description of the first redirect cycle among enabled rules that stay on the site (relative, or absolute on one of $site_hosts), or null. */
function boblift_legacy_redirects_find_cycle(array $rules, array $site_hosts = []): ?string
{
    $next = [];
    foreach ($rules as $rule) {
        if (($rule['enabled'] ?? true) === false || empty($rule['from']) || empty($rule['to'])) {
            continue;
        }
        $to_host = strtolower((string) parse_url((string) $rule['to'], PHP_URL_HOST));
        if (($rule['to'][0] ?? '') !== '/' && !($to_host !== '' && in_array($to_host, array_map('strtolower', $site_hosts), true))) {
            continue; // leaves the site (for example bobliftloader.com)
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
                $replace = rest_sanitize_boolean($request->get_param('replace'));
                $saved = $replace ? ['overrides' => [], 'removed' => [], 'replace' => true] : (boblift_legacy_redirects_saved() ?? ['overrides' => [], 'removed' => [], 'replace' => false]);
                $overrides = [];
                foreach (array_merge($saved['overrides'], $clean) as $rule) {
                    $overrides[boblift_legacy_redirect_normalize_path((string) $rule['from'])] = $rule;
                }
                $saved['overrides'] = array_values($overrides);
                $saved['removed'] = array_values(array_diff($saved['removed'], array_keys($overrides)));
                $rules = boblift_legacy_redirects_apply((array) (boblift_legacy_redirects_default_config()['rules'] ?? []), $saved);
                $cycle = boblift_legacy_redirects_find_cycle($rules, array_filter([(string) ($current['canonical_host'] ?? ''), (string) parse_url(home_url(), PHP_URL_HOST)]));
                if ($cycle !== null) {
                    return new WP_Error('redirect_cycle', 'These rules would create a redirect cycle (' . $cycle . '); nothing was saved.', ['status' => 400]);
                }
                update_option(BOBLIFT_LEGACY_REDIRECTS_OPTION, ['format' => 2] + $saved, false);
                return ['saved' => count($clean), 'total' => count($rules)];
            },
        ],
        [
            'methods' => 'DELETE',
            'permission_callback' => $permission,
            'callback' => static function (WP_REST_Request $request) {
                if (rest_sanitize_boolean($request->get_param('reset'))) {
                    delete_option(BOBLIFT_LEGACY_REDIRECTS_OPTION);
                    return ['reset' => true];
                }
                $from = boblift_legacy_redirect_normalize_path((string) $request->get_param('from'));
                $saved = boblift_legacy_redirects_saved() ?? ['overrides' => [], 'removed' => [], 'replace' => false];
                $saved['overrides'] = array_values(array_filter($saved['overrides'], static fn($rule) => boblift_legacy_redirect_normalize_path((string) ($rule['from'] ?? '')) !== $from));
                $saved['removed'] = array_values(array_unique(array_merge($saved['removed'], [$from])));
                update_option(BOBLIFT_LEGACY_REDIRECTS_OPTION, ['format' => 2] + $saved, false);
                $rules = boblift_legacy_redirects_config()['rules'];
                return ['removed' => $from, 'total' => count($rules)];
            },
        ],
    ]);
}
add_action('rest_api_init', 'boblift_legacy_redirects_rest_register');
