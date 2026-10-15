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

function boblift_legacy_redirects_config(): array
{
    $saved = get_option(BOBLIFT_LEGACY_REDIRECTS_OPTION, []);
    $default = boblift_legacy_redirects_default_config();
    if (is_array($saved) && !empty($saved['rules']) && is_array($saved['rules'])) {
        return array_merge($default, $saved);
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
 * Pure function: no WordPress state besides the config argument.
 */
function boblift_legacy_redirect_resolve(string $host, string $path, array $config, string $site_base): ?string
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
        $target ??= (string) ($config['language_host_default'] ?? '/');
        if ($norm === '/') {
            $target = '/';
        }
    }

    if ($target === null) {
        return null;
    }
    $url = preg_match('#^https?://#i', $target) ? $target : $base . '/' . ltrim($target, '/');
    if ($language === '' && $target[0] === '/' && rtrim(rawurldecode($path), '/') === rtrim($target, '/')) {
        return null; // already on the exact target path (case-only rules must not loop)
    }
    return $url;
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
    $target = boblift_legacy_redirect_resolve((string) ($_SERVER['HTTP_HOST'] ?? ''), $path, $config, home_url());
    if ($target === null) {
        return;
    }
    $query = (string) parse_url($uri, PHP_URL_QUERY);
    if ($query !== '' && !str_contains($target, '?')) {
        $target .= '?' . $query;
    }
    header('X-Redirect-By: boblift-legacy-redirects');
    wp_redirect($target, 301, 'BOB-LIFT legacy');
    exit;
}
add_action('init', 'boblift_legacy_redirects_run', 1);

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
                $custom = get_option(BOBLIFT_LEGACY_REDIRECTS_OPTION, []);
                return ['source' => !empty($custom['rules']) ? 'custom' : 'default', 'count' => count(boblift_legacy_redirects_config()['rules']), 'config' => boblift_legacy_redirects_config()];
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
