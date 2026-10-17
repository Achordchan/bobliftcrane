<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
define('ABSPATH', __DIR__ . '/');
define('BOBLIFT_MLS_STATE_OPTION', 'boblift_mls_state');
define('BOBLIFT_MLS_SETTINGS_OPTION', 'boblift_mls_settings');

class WP_Error
{
    public function __construct(public string $code, public string $message, public array $data = []) {}
    public function get_error_message(): string { return $this->message; }
    public function get_error_data(): array { return $this->data; }
}
class WP_Post
{
    public function __construct(public int $ID, public string $post_type = 'page', public string $post_name = '',
        public string $post_title = '', public string $post_content = '', public string $post_excerpt = '',
        public string $post_status = 'draft', public int $post_parent = 0, public int $menu_order = 0,
        public string $post_date = '2026-10-17 00:00:00', public string $post_date_gmt = '2026-10-17 00:00:00', public string $post_password = '') {}
}
class Stub_WPDB
{
    public string $prefix = 'wp_';
    public function prepare(string $query, ...$args): string { return vsprintf(str_replace('%s', "'%s'", $query), $args); }
    public function get_var(string $query): ?string
    {
        if (!str_contains($query, 'GET_LOCK')) { return null; }
        if (!empty($GLOBALS['stub_db_lock'])) { return '0'; }
        $GLOBALS['stub_db_lock'] = true;
        return '1';
    }
    public function query(string $query): int
    {
        if (str_contains($query, 'RELEASE_LOCK')) { $GLOBALS['stub_db_lock'] = false; }
        return 1;
    }
}
$GLOBALS['wpdb'] = new Stub_WPDB();
class WP_Term
{
    public function __construct(public int $term_id, public string $taxonomy = 'category', public string $slug = '',
        public string $name = '', public string $description = '', public int $parent = 0) {}
}
class WP_REST_Request implements ArrayAccess
{
    public function __construct(public string $body = '', public array $query = [], public string $route = '/boblift-mls/v1/import') {}
    public function get_body(): string { return $this->body; }
    public function get_query_params(): array { return $this->query; }
    public function get_route(): string { return $this->route; }
    public function offsetExists(mixed $key): bool { return isset($this->query[$key]); }
    public function offsetGet(mixed $key): mixed { return $this->query[$key] ?? null; }
    public function offsetSet(mixed $key, mixed $value): void { $this->query[$key] = $value; }
    public function offsetUnset(mixed $key): void { unset($this->query[$key]); }
}
$GLOBALS['fixture'] = ['posts' => [], 'terms' => [], 'meta' => [], 'term_meta' => [], 'options' => [], 'post_lang' => [],
    'term_lang' => [], 'post_links' => [], 'term_links' => [], 'assignments' => [], 'writes' => 0, 'hooks' => [], 'routes' => []];
function is_wp_error(mixed $value): bool { return $value instanceof WP_Error; }
function metadata_exists(string $type, int $id, string $key): bool
{
    return array_key_exists($key, $GLOBALS['fixture'][$type === 'term' ? 'term_meta' : 'meta'][$id] ?? []);
}
function get_term_meta(int $id, string $key, bool $single = false): mixed
{
    $values = $GLOBALS['fixture']['term_meta'][$id][$key] ?? [];
    return $single ? ($values[0] ?? '') : $values;
}
function update_term_meta(int $id, string $key, mixed $value): int|bool|WP_Error
{
    if (isset($GLOBALS['fixture']['term_meta_failure'])) {
        return $GLOBALS['fixture']['term_meta_failure'];
    }
    $value = wp_unslash($value);
    if (metadata_exists('term', $id, $key) && get_term_meta($id, $key, true) === $value) {
        return false;
    }
    $GLOBALS['fixture']['writes']++;
    $GLOBALS['fixture']['term_meta'][$id][$key] = [$value];
    return true;
}
function add_action(string $hook, callable|string $callback, ...$args): void { $GLOBALS['fixture']['hooks'][$hook][] = $callback; }
function add_filter(string $hook, callable|string $callback, ...$args): void { add_action($hook, $callback); }
function current_user_can(string $cap): bool { return $GLOBALS['fixture']['authorized'] ?? true; }
function register_rest_route(string $namespace, string $path, array $args): void { $GLOBALS['fixture']['routes'][$path] = $args; }
function home_url(): string { return 'https://local.invalid'; }
function wp_json_encode(mixed $value, int $flags = 0): string|false { return json_encode($value, $flags); }
function sanitize_key(string $key): string { return strtolower($key); }
function pll_languages_list(array $args): array { return ['en', 'ja', 'fr']; }
function wp_generate_password(int $length = 12, bool $special = true): string { return bin2hex(random_bytes(intdiv($length, 2))); }
function add_option(string $key, mixed $value = '', string $deprecated = '', string $autoload = 'yes'): bool
{
    if (array_key_exists($key, $GLOBALS['fixture']['options'] ?? [])) { return false; }
    $GLOBALS['fixture']['options'][$key] = $value;
    return true;
}
function delete_option(string $key): bool { unset($GLOBALS['fixture']['options'][$key]); return true; }
function get_option(string $key, mixed $default = false): mixed { return $GLOBALS['fixture']['options'][$key] ?? $default; }
function update_option(string $key, mixed $value, bool $autoload = false): bool
{
    $GLOBALS['fixture']['writes']++;
    if (!empty($GLOBALS['fixture']['fail_options'])) { return false; }
    if (array_key_exists($key, $GLOBALS['fixture']['options']) && $GLOBALS['fixture']['options'][$key] === $value) { return false; }
    $GLOBALS['fixture']['options'][$key] = $value;
    return true;
}
function wp_slash(mixed $value): mixed
{
    return is_array($value) ? array_map('wp_slash', $value) : (is_string($value) ? addslashes($value) : $value);
}
function wp_unslash(mixed $value): mixed
{
    return is_array($value) ? array_map('wp_unslash', $value) : (is_string($value) ? stripslashes($value) : $value);
}
function maybe_unserialize(mixed $value): mixed
{
    if (!is_string($value) || !preg_match('/^(a|s|i|b|d|O):|^N;/', $value)) { return $value; }
    $decoded = @unserialize($value, ['allowed_classes' => false]);
    return $decoded !== false || $value === 'b:0;' ? $decoded : $value;
}
function get_post(int $id): ?WP_Post { return $GLOBALS['fixture']['posts'][$id] ?? null; }
function get_posts(array $args): array
{
    $posts = array_values(array_filter($GLOBALS['fixture']['posts'], static function ($post) use ($args): bool {
        if (!in_array($post->post_type, (array) ($args['post_type'] ?? 'post'), true)) { return false; }
        if (isset($args['name']) && $post->post_name !== $args['name']) { return false; }
        if (!empty($args['lang']) && pll_get_post_language($post->ID) !== $args['lang']) { return false; }
        if (isset($args['meta_key']) && get_post_meta($post->ID, $args['meta_key'], true) !== $args['meta_value']) { return false; }
        if (($args['post_status'] ?? 'any') !== 'any' && !in_array($post->post_status, (array) $args['post_status'], true)) { return false; }
        return true;
    }));
    $limit = $args['posts_per_page'] ?? $args['numberposts'] ?? 5;
    return $limit < 0 ? $posts : array_slice($posts, (($args['paged'] ?? 1) - 1) * $limit, $limit);
}
function wp_insert_post(array $data, bool $error = false): int|WP_Error
{
    $id = count($GLOBALS['fixture']['posts']) + 100;
    $GLOBALS['fixture']['posts'][$id] = new WP_Post($id);
    $data['ID'] = $id;
    return wp_update_post($data, $error);
}
function wp_update_post(array $data, bool $error = false): int|WP_Error
{
    $GLOBALS['fixture']['writes']++;
    $data = wp_unslash($data);
    foreach ($data as $key => $value) { $GLOBALS['fixture']['posts'][$data['ID']]->$key = $value; }
    return $data['ID'];
}
function post_type_exists(string $type): bool { return in_array($type, ['page', 'post', 'product', 'case'], true); }
function get_post_meta(int $id, string $key = '', bool $single = false): mixed
{
    $meta = $GLOBALS['fixture']['meta'][$id] ?? [];
    if ($key === '') { return $meta; }
    return $single ? maybe_unserialize($meta[$key][0] ?? '') : array_map('maybe_unserialize', $meta[$key] ?? []);
}
function update_post_meta(int $id, string $key, mixed $value): bool
{
    $GLOBALS['fixture']['writes']++;
    if (($GLOBALS['fixture']['fail_meta'] ?? '') === $key) { return false; }
    // Like WordPress: without a previous value every existing row is updated in place.
    $rows = count($GLOBALS['fixture']['meta'][$id][$key] ?? []);
    $GLOBALS['fixture']['meta'][$id][$key] = array_fill(0, max(1, $rows), wp_unslash($value));
    return true;
}
function add_post_meta(int $id, string $key, mixed $value): bool
{
    $GLOBALS['fixture']['writes']++;
    if (($GLOBALS['fixture']['fail_meta'] ?? '') === $key) { return false; }
    $GLOBALS['fixture']['meta'][$id][$key][] = wp_unslash($value);
    return true;
}
function delete_post_meta(int $id, string $key): bool
{
    $GLOBALS['fixture']['writes']++;
    unset($GLOBALS['fixture']['meta'][$id][$key]);
    return true;
}
function get_post_thumbnail_id(int $id): int { return (int) get_post_meta($id, '_thumbnail_id', true); }
function set_post_thumbnail(int $id, int $attachment): bool { return update_post_meta($id, '_thumbnail_id', $attachment); }
function delete_post_thumbnail(int $id): bool { return delete_post_meta($id, '_thumbnail_id'); }
function pll_get_post_language(int $id, string $field = 'slug'): string { return $GLOBALS['fixture']['post_lang'][$id] ?? ''; }
function pll_set_post_language(int $id, string $lang): void { $GLOBALS['fixture']['writes']++; $GLOBALS['fixture']['post_lang'][$id] = $lang; }
function pll_get_post_translations(int $id): array { return $GLOBALS['fixture']['post_links'][$id] ?? []; }
function pll_get_post(int $id, string $lang): int { return pll_get_post_translations($id)[$lang] ?? 0; }
function pll_save_post_translations(array $links): void
{
    $GLOBALS['fixture']['writes']++;
    foreach ($links as $id) { $GLOBALS['fixture']['post_links'][$id] = $links; }
}
function taxonomy_exists(string $taxonomy): bool { return in_array($taxonomy, ['category', 'post_tag', 'product_category'], true); }
function is_object_in_taxonomy(string $type, string $taxonomy): bool { return $type === 'product' ? $taxonomy === 'product_category' : in_array($taxonomy, ['category', 'post_tag'], true); }
function get_object_taxonomies(string $type): array { return $type === 'product' ? ['product_category'] : ['category', 'post_tag']; }
function get_term(int $id, string $taxonomy): ?WP_Term
{
    $term = $GLOBALS['fixture']['terms'][$id] ?? null;
    return $term && $term->taxonomy === $taxonomy ? $term : null;
}
function get_terms(array $args): array
{
    return array_values(array_filter($GLOBALS['fixture']['terms'], static fn($term): bool => $term->taxonomy === $args['taxonomy']
        && (!isset($args['slug']) || $term->slug === $args['slug'])
        && (!isset($args['lang']) || pll_get_term_language($term->term_id) === $args['lang'])));
}
function wp_insert_term(string $name, string $taxonomy, array $args): array|WP_Error
{
    $id = count($GLOBALS['fixture']['terms']) + 100;
    $GLOBALS['fixture']['terms'][$id] = new WP_Term($id, $taxonomy);
    return wp_update_term($id, $taxonomy, ['name' => $name] + $args);
}
function wp_update_term(int $id, string $taxonomy, array $args): array|WP_Error
{
    $GLOBALS['fixture']['writes']++;
    foreach (wp_unslash($args) as $key => $value) { $GLOBALS['fixture']['terms'][$id]->$key = $value; }
    return ['term_id' => $id];
}
function pll_get_term_language(int $id, string $field = 'slug'): string { return $GLOBALS['fixture']['term_lang'][$id] ?? ''; }
function pll_set_term_language(int $id, string $lang): void { $GLOBALS['fixture']['writes']++; $GLOBALS['fixture']['term_lang'][$id] = $lang; }
function pll_get_term_translations(int $id): array { return $GLOBALS['fixture']['term_links'][$id] ?? []; }
function pll_get_term(int $id, string $lang): int { return pll_get_term_translations($id)[$lang] ?? 0; }
function pll_save_term_translations(array $links): void
{
    $GLOBALS['fixture']['writes']++;
    foreach ($links as $id) { $GLOBALS['fixture']['term_links'][$id] = $links; }
}
function wp_get_object_terms(int $id, string $taxonomy, array $args = []): array
{
    $ids = $GLOBALS['fixture']['assignments'][$id][$taxonomy] ?? [];
    return ($args['fields'] ?? '') === 'ids' ? $ids : array_map(static fn($term_id) => get_term($term_id, $taxonomy), $ids);
}
function wp_set_object_terms(int $id, array $terms, string $taxonomy, bool $append = false): array
{
    $GLOBALS['fixture']['writes']++;
    $GLOBALS['fixture']['assignments'][$id][$taxonomy] = $terms;
    return $terms;
}
