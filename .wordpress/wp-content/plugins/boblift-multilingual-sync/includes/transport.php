<?php
if (!defined('ABSPATH')) {
    exit;
}

const BOBLIFT_MLS_TRANSPORT_MAX_BYTES = 32 * 1024 * 1024;
const BOBLIFT_MLS_TRANSPORT_TERM_META_KEYS = ['_boblift_intro_title', '_boblift_intro_text'];

require_once __DIR__ . '/transport-validation.php';
require_once __DIR__ . '/transport-export.php';
require_once __DIR__ . '/transport-import.php';

function boblift_mls_transport_error(string $message, int $status = 400): WP_Error
{
    return new WP_Error('boblift_mls_transport_' . $status, $message, ['status' => $status]);
}

function boblift_mls_transport_language(string $language): true|WP_Error
{
    if (!function_exists('pll_get_post') || !function_exists('pll_get_term')) {
        return boblift_mls_transport_error('需要启用 Polylang。');
    }
    if (!in_array($language, boblift_mls_languages(), true)) {
        return boblift_mls_transport_error('目标语言不存在。', 404);
    }
    return $language === boblift_mls_source_language()
        ? boblift_mls_transport_error('目标语言不能等于主维护语言。') : true;
}

function boblift_mls_transport_source_post(string $type, string $slug): ?WP_Post
{
    $posts = get_posts(['post_type' => $type, 'post_name__in' => [$slug], 'post_status' => 'any',
        'numberposts' => 2, 'lang' => boblift_mls_source_language(), 'suppress_filters' => false]);
    $matches = array_values(array_filter($posts, static fn($post): bool => $post instanceof WP_Post
        && $post->post_name === $slug && boblift_mls_post_language($post) === boblift_mls_source_language()));
    return count($matches) === 1 ? $matches[0] : null;
}

function boblift_mls_transport_source_term(string $taxonomy, string $slug): ?WP_Term
{
    $terms = get_terms(['taxonomy' => $taxonomy, 'slug' => $slug, 'hide_empty' => false,
        'lang' => boblift_mls_source_language()]);
    if (is_wp_error($terms)) {
        return null;
    }
    $matches = array_values(array_filter($terms, static fn($term): bool => $term instanceof WP_Term
        && pll_get_term_language($term->term_id, 'slug') === boblift_mls_source_language()));
    return count($matches) === 1 ? $matches[0] : null;
}

function boblift_mls_transport_rest_import(WP_REST_Request $request): array|WP_Error
{
    if (strlen($request->get_body()) > BOBLIFT_MLS_TRANSPORT_MAX_BYTES) {
        return boblift_mls_transport_error('数据包超过 32 MB。', 413);
    }
    $package = json_decode($request->get_body(), true);
    if (!is_array($package) || json_last_error() !== JSON_ERROR_NONE) {
        return boblift_mls_transport_error('请求必须是有效 JSON 数据包。');
    }
    $query = $request->get_query_params();
    $args = array_diff_key($query, array_flip(['context', '_fields', '_locale', '_envelope']));
    foreach (['dry_run', 'overwrite', 'publish', 'only'] as $key) {
        if (array_key_exists($key, $query) || array_key_exists($key, $package)) {
            $args[$key] = $query[$key] ?? $package[$key];
        }
        unset($package[$key]);
    }
    return boblift_mls_import_language($package, $args);
}

add_action('rest_api_init', static function (): void {
    $permission = static fn(): bool => current_user_can('manage_options');
    register_rest_route('boblift-mls/v1', '/export/(?P<lang>[a-z0-9_-]+)', [
        'methods' => 'GET', 'permission_callback' => $permission,
        'callback' => static fn(WP_REST_Request $request): array|WP_Error => boblift_mls_export_language($request['lang']),
    ]);
    register_rest_route('boblift-mls/v1', '/import', [
        'methods' => 'POST', 'permission_callback' => $permission, 'callback' => 'boblift_mls_transport_rest_import',
    ]);
});

add_filter('rest_pre_dispatch', static function ($result, $server, WP_REST_Request $request) {
    if ($request->get_route() === '/boblift-mls/v1/import'
        && strlen($request->get_body()) > BOBLIFT_MLS_TRANSPORT_MAX_BYTES) {
        return boblift_mls_transport_error('数据包超过 32 MB。', 413);
    }
    return $result;
}, 10, 3);
