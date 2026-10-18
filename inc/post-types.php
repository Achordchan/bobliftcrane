<?php
if (!defined('ABSPATH')) {
    exit;
}

function boblift_register_content_types(): void
{
    register_post_type('product', [
        'labels' => boblift_post_type_labels('产品', '产品'),
        'public' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-products',
        'has_archive' => 'products',
        'rewrite' => ['slug' => 'products', 'with_front' => false],
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions'],
        'taxonomies' => ['product_category'],
    ]);

    register_post_type('case', [
        'labels' => boblift_post_type_labels('案例', '案例'),
        'public' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-portfolio',
        'has_archive' => 'cases',
        'rewrite' => ['slug' => 'cases', 'with_front' => false],
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions'],
    ]);

    // Customer feedback: photos and videos customers send back. Language-neutral on purpose (only country
    // and model are shown, stored per language in meta), so one entry serves every language of the site.
    register_post_type('feedback', [
        'labels' => boblift_post_type_labels('客户反馈', '客户反馈'),
        'public' => true,
        'has_archive' => false,
        'rewrite' => ['slug' => 'feedback', 'with_front' => false],
        'menu_icon' => 'dashicons-format-gallery',
        'exclude_from_search' => true,
        'supports' => ['title', 'page-attributes'],
    ]);

    register_post_type('inquiry', [
        'labels' => boblift_post_type_labels('询盘', '询盘'),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-email-alt2',
        'supports' => false,
        'capability_type' => 'post',
        'capabilities' => ['create_posts' => 'do_not_allow'],
        'map_meta_cap' => true,
    ]);
}
add_action('init', 'boblift_register_content_types');

function boblift_upgrade_feedback_rewrites(): void
{
    // Bump when feedback routes change (2: language-prefixed twins in inc/polylang-rewrites.php).
    if (get_option('boblift_feedback_rewrite_version') === '2') {
        return;
    }
    flush_rewrite_rules(false);
    update_option('boblift_feedback_rewrite_version', '2', false);
}
add_action('admin_init', 'boblift_upgrade_feedback_rewrites');

function boblift_inquiry_use_classic_editor(bool $use_block_editor, string $post_type): bool
{
    return $post_type === 'inquiry' ? false : $use_block_editor;
}
add_filter('use_block_editor_for_post_type', 'boblift_inquiry_use_classic_editor', 10, 2);

function boblift_inquiry_admin_head(): void
{
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== 'inquiry') {
        return;
    }
    echo '<style>.post-type-inquiry #titlediv,.post-type-inquiry #minor-publishing-actions,.post-type-inquiry #misc-publishing-actions,.post-type-inquiry #delete-action{display:none!important}.post-type-inquiry #post-body-content{margin-bottom:0}.post-type-inquiry #submitdiv .hndle,.post-type-inquiry #submitdiv .handle-actions{display:none}.post-type-inquiry #major-publishing-actions{border-top:0}.post-type-inquiry .wrap .page-title-action{display:none!important}</style>';
}
add_action('admin_head', 'boblift_inquiry_admin_head');

function boblift_inquiry_columns(array $columns): array
{
    return [
        'cb' => $columns['cb'] ?? '<input type="checkbox">',
        'inquiry_customer' => '客户',
        'inquiry_contact' => '联系方式',
        'inquiry_product' => '意向产品',
        'inquiry_country' => '国家',
        'inquiry_source' => '来源',
        'inquiry_date' => '提交时间',
    ];
}
add_filter('manage_inquiry_posts_columns', 'boblift_inquiry_columns');

function boblift_inquiry_column_content(string $column, int $post_id): void
{
    if ($column === 'inquiry_customer') {
        $name = (string) get_post_meta($post_id, '_name', true);
        $title = $name !== '' ? $name : get_the_title($post_id);
        echo '<strong><a class="row-title" href="' . esc_url(get_edit_post_link($post_id)) . '">' . esc_html($title) . '</a></strong>';
        return;
    }
    if ($column === 'inquiry_contact') {
        $email = (string) get_post_meta($post_id, '_email', true);
        $phone = (string) get_post_meta($post_id, '_phone', true);
        echo esc_html(trim($email . ($email && $phone ? ' / ' : '') . $phone));
        return;
    }
    if ($column === 'inquiry_product') {
        echo esc_html((string) get_post_meta($post_id, '_product', true));
        return;
    }
    if ($column === 'inquiry_source') {
        $entry = (string) get_post_meta($post_id, '_source_entry', true);
        $page = (string) get_post_meta($post_id, '_source_page', true);
        $path = $page !== '' ? (string) wp_parse_url($page, PHP_URL_PATH) : '';
        echo esc_html($entry !== '' ? $entry : '—');
        if ($path !== '') {
            echo '<br><a href="' . esc_url($page) . '" target="_blank" rel="noopener">' . esc_html(rawurldecode($path)) . '</a>';
        }
        return;
    }
    if ($column === 'inquiry_country') {
        echo esc_html((string) get_post_meta($post_id, '_country', true));
        return;
    }
    if ($column === 'inquiry_date') {
        echo esc_html(get_the_date('Y-m-d H:i', $post_id));
    }
}
add_action('manage_inquiry_posts_custom_column', 'boblift_inquiry_column_content', 10, 2);


function boblift_post_type_labels(string $singular, string $menu): array
{
    return [
        'name' => $menu,
        'singular_name' => $singular,
        'menu_name' => $menu,
        'name_admin_bar' => $singular,
        'add_new' => '新增' . $singular,
        'add_new_item' => '新增' . $singular,
        'edit_item' => '编辑' . $singular,
        'new_item' => '新' . $singular,
        'view_item' => '查看' . $singular,
        'view_items' => '查看' . $menu,
        'search_items' => '搜索' . $singular,
        'not_found' => '未找到' . $singular,
        'not_found_in_trash' => '回收站中没有' . $singular,
        'all_items' => '全部' . $singular,
        'archives' => $singular . '归档',
        'attributes' => $singular . '属性',
        'insert_into_item' => '插入到' . $singular,
        'uploaded_to_this_item' => '上传到此' . $singular,
        'filter_items_list' => '筛选' . $menu,
        'items_list_navigation' => $menu . '列表导航',
        'items_list' => $menu . '列表',
    ];
}

function boblift_register_taxonomies(): void
{
    register_taxonomy('product_category', ['product'], [
        'labels' => [
            'name' => '产品分类',
            'singular_name' => '产品分类',
            'menu_name' => '产品分类',
            'all_items' => '全部产品分类',
            'edit_item' => '编辑产品分类',
            'view_item' => '查看产品分类',
            'update_item' => '更新产品分类',
            'add_new_item' => '新增产品分类',
            'new_item_name' => '新产品分类名称',
            'parent_item' => '上级产品分类',
            'parent_item_colon' => '上级产品分类：',
            'search_items' => '搜索产品分类',
            'not_found' => '未找到产品分类',
        ],
        'public' => true,
        'hierarchical' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'products/category', 'with_front' => false],
    ]);
    add_rewrite_rule('^products/category/([^/]+)/?$', 'index.php?product_category=$matches[1]', 'top');
    foreach (boblift_product_category_root_rewrite_slugs() as $slug) {
        add_rewrite_rule('^' . preg_quote($slug, '/') . '/?$', 'index.php?boblift_product_category=' . $slug, 'top');
    }
    add_rewrite_rule('^sitemap\.xml$', 'index.php?sitemap=index', 'top');
    add_rewrite_rule('^sitemap\.xsl$', 'index.php?sitemap-stylesheet=sitemap', 'top');
    add_rewrite_rule('^sitemap-index\.xsl$', 'index.php?sitemap-stylesheet=index', 'top');
    add_rewrite_rule('^sitemap-([a-z]+?)-([a-z\d_-]+?)-(\d+?)\.xml$', 'index.php?sitemap=$matches[1]&sitemap-subtype=$matches[2]&paged=$matches[3]', 'top');
    add_rewrite_rule('^sitemap-([a-z]+?)-(\d+?)\.xml$', 'index.php?sitemap=$matches[1]&paged=$matches[2]', 'top');
    add_rewrite_rule('^([^/]+)\.html$', 'index.php?boblift_html_slug=$matches[1]', 'top');

    register_taxonomy('legacy_landing', ['product'], [
        'labels' => [
            'name' => '旧落地页',
            'singular_name' => '旧落地页',
            'menu_name' => '旧落地页',
            'all_items' => '全部旧落地页',
            'edit_item' => '编辑旧落地页',
            'view_item' => '查看旧落地页',
            'update_item' => '更新旧落地页',
            'add_new_item' => '新增旧落地页',
            'new_item_name' => '新旧落地页名称',
            'search_items' => '搜索旧落地页',
            'not_found' => '未找到旧落地页',
        ],
        'public' => false,
        'hierarchical' => false,
        'show_ui' => true,
        'show_admin_column' => false,
        'show_in_rest' => false,
        'meta_box_cb' => false,
    ]);
}
add_action('init', 'boblift_register_taxonomies');

/**
 * Category introduction shown under the product list when one category is selected. Editable on the category screen
 * and through REST: POST /wp/v2/product_category/<id> with {"meta": {"_boblift_intro_title": "...", "_boblift_intro_text": "..."}}.
 * Paragraphs in _boblift_intro_text are separated by a blank line. Each Polylang language term holds its own copy.
 */
function boblift_register_product_category_intro_meta(): void
{
    $auth = static fn(): bool => current_user_can('manage_categories');
    register_term_meta('product_category', '_boblift_intro_title', [
        'type' => 'string',
        'single' => true,
        'default' => '',
        'show_in_rest' => true,
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => $auth,
    ]);
    register_term_meta('product_category', '_boblift_intro_text', [
        'type' => 'string',
        'single' => true,
        'default' => '',
        'show_in_rest' => true,
        'sanitize_callback' => 'sanitize_textarea_field',
        'auth_callback' => $auth,
    ]);
}
add_action('init', 'boblift_register_product_category_intro_meta');

/**
 * One-time move of introductions saved in the retired site-UI section categoryIntros (per language theme options) into
 * the term meta above. Terms that already have an introduction are left alone. Keys may be the language term's slug or
 * the English source slug.
 */
function boblift_migrate_category_intros_to_term_meta(): void
{
    if (get_option('boblift_category_intro_meta_migrated') || !taxonomy_exists('product_category')) {
        return;
    }
    $languages = function_exists('pll_languages_list') ? array_values(array_filter((array) pll_languages_list(['fields' => 'slug']))) : [];
    foreach ($languages ?: [null] as $language) {
        if (function_exists('boblift_mls_get_raw_options') && $language !== null) {
            $raw = boblift_mls_get_raw_options($language);
        } else {
            $raw = get_option('boblift_theme_options', []);
        }
        $intros = is_array($raw['site_ui']['categoryIntros'] ?? null) ? $raw['site_ui']['categoryIntros'] : [];
        foreach ($intros as $slug => $intro) {
            if (!is_array($intro)) {
                continue;
            }
            $term = get_term_by('slug', sanitize_title((string) $slug), 'product_category');
            if (!$term instanceof WP_Term) {
                continue;
            }
            if ($language !== null && function_exists('pll_get_term')) {
                $term_id = (int) pll_get_term($term->term_id, $language);
                if ($term_id <= 0) {
                    continue;
                }
            } else {
                $term_id = (int) $term->term_id;
            }
            $paragraphs = array_values(array_filter(array_map(static fn($text): string => trim((string) $text), (array) ($intro['paragraphs'] ?? [])), static fn(string $text): bool => $text !== ''));
            if ($paragraphs === [] || trim((string) get_term_meta($term_id, '_boblift_intro_text', true)) !== '') {
                continue;
            }
            update_term_meta($term_id, '_boblift_intro_text', sanitize_textarea_field(implode("\n\n", $paragraphs)));
            $title = sanitize_text_field((string) ($intro['title'] ?? ''));
            if ($title !== '' && trim((string) get_term_meta($term_id, '_boblift_intro_title', true)) === '') {
                update_term_meta($term_id, '_boblift_intro_title', $title);
            }
        }
    }
    update_option('boblift_category_intro_meta_migrated', 1, false);
}
add_action('init', 'boblift_migrate_category_intros_to_term_meta', 30);

/** Paragraphs of a category introduction, split on blank lines. */
function boblift_product_category_intro_paragraphs(string $text): array
{
    $parts = preg_split('/\R\s*\R/u', trim(str_replace("\r\n", "\n", $text))) ?: [];
    return array_values(array_filter(array_map(static fn(string $part): string => trim(preg_replace('/\s+/u', ' ', $part) ?? ''), $parts), static fn(string $part): bool => $part !== ''));
}

function boblift_register_news_rewrite(): void
{
    add_rewrite_rule('^news/([^/]+)/?$', 'index.php?name=$matches[1]', 'top');
}
add_action('init', 'boblift_register_news_rewrite');

function boblift_normalize_public_path(string $path): string
{
    $path = trim(wp_unslash($path));
    if ($path === '') {
        return '';
    }
    $parts = wp_parse_url($path);
    if (!empty($parts['scheme']) || !empty($parts['host'])) {
        return '';
    }
    $path = (string) ($parts['path'] ?? $path);
    $path = '/' . ltrim($path, '/');
    $path = preg_replace('#/+#', '/', $path) ?: '';
    if ($path === '/' || str_contains($path, '..')) {
        return '';
    }
    return untrailingslashit($path);
}

function boblift_public_path_url(string $path, ?string $language = null): string
{
    $path = boblift_normalize_public_path($path);
    return boblift_localized_url($path !== '' ? home_url($path) : home_url('/'), $language);
}

function boblift_current_language_slug(): string
{
    if (function_exists('pll_current_language')) {
        $language = (string) pll_current_language('slug');
        if ($language !== '') {
            return $language;
        }
    }

    return '';
}

function boblift_post_for_current_language(WP_Post $post): WP_Post
{
    $language = boblift_current_language_slug();
    if ($language === '' || !function_exists('pll_get_post_language') || !function_exists('pll_get_post')) {
        return $post;
    }

    if ((string) pll_get_post_language($post->ID, 'slug') === $language) {
        return $post;
    }

    $translated_id = (int) pll_get_post($post->ID, $language);
    $translated = $translated_id > 0 ? get_post($translated_id) : null;

    return $translated instanceof WP_Post ? $translated : $post;
}


function boblift_post_translation_for_current_language(WP_Post $post): WP_Post
{
    return boblift_post_for_current_language($post);
}

function boblift_term_for_current_language(WP_Term $term): WP_Term
{
    $language = boblift_current_language_slug();
    if ($language === '' || !function_exists('pll_get_term_language') || !function_exists('pll_get_term')) {
        return $term;
    }

    if ((string) pll_get_term_language($term->term_id, 'slug') === $language) {
        return $term;
    }

    $translated_id = (int) pll_get_term($term->term_id, $language);
    $translated = $translated_id > 0 ? get_term($translated_id, $term->taxonomy) : null;

    return $translated instanceof WP_Term && !is_wp_error($translated) ? $translated : $term;
}

function boblift_term_by_slug_for_current_language(string $slug, string $taxonomy): ?WP_Term
{
    $term = get_term_by('slug', $slug, $taxonomy);
    if (!$term instanceof WP_Term) {
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'slug' => $slug,
            'hide_empty' => false,
            'number' => 1,
            'lang' => '',
        ]);
        $term = !is_wp_error($terms) && !empty($terms) && $terms[0] instanceof WP_Term ? $terms[0] : null;
    }

    if (!$term instanceof WP_Term) {
        return null;
    }

    return boblift_term_for_current_language($term);
}

function boblift_page_is_translation_of_slug(WP_Post $post, string $slug): bool
{
    if ($post->post_type !== 'page' || $post->post_name === $slug) {
        return $post->post_type === 'page' && $post->post_name === $slug;
    }

    if (!function_exists('pll_get_post_language') || !function_exists('pll_get_post')) {
        return false;
    }

    $source = get_page_by_path($slug, OBJECT, 'page');
    if (!$source instanceof WP_Post) {
        return false;
    }

    $language = (string) pll_get_post_language($post->ID, 'slug');
    $translated_id = $language !== '' ? (int) pll_get_post($source->ID, $language) : 0;

    return $translated_id === $post->ID;
}

function boblift_localized_url(string $url, ?string $language = null): string
{
    if ($url === '' || str_starts_with($url, '#') || !preg_match('#^https?://#i', $url)) {
        return $url;
    }

    if (!function_exists('PLL') || !isset(PLL()->links_model) || !method_exists(PLL()->links_model, 'add_language_to_link')) {
        return $url;
    }

    $language = $language ?: boblift_current_language_slug();
    if ($language === '') {
        return $url;
    }

    if (method_exists(PLL()->links_model, 'switch_language_in_link')) {
        return (string) PLL()->links_model->switch_language_in_link($url, $language);
    }

    return (string) PLL()->links_model->add_language_to_link($url, $language);
}

function boblift_html_url(string $slug): string
{
    return boblift_public_path_url('/' . sanitize_title($slug) . '.html');
}

/** True for the static front page and for its translations. */
function boblift_is_front_page_or_translation(int $post_id): bool
{
    $front = (int) get_option('page_on_front');
    if ($front === 0) {
        return false;
    }
    if ($front === $post_id) {
        return true;
    }
    return function_exists('pll_get_post_translations') && in_array($post_id, array_map('intval', pll_get_post_translations($front)), true);
}

function boblift_post_public_path(int|WP_Post $post, string $default = ''): string
{
    $post = $post instanceof WP_Post ? $post : get_post($post);
    if (!$post instanceof WP_Post) {
        return boblift_normalize_public_path($default);
    }
    $custom = boblift_normalize_public_path((string) get_post_meta($post->ID, '_boblift_public_url', true));
    if ($custom !== '') {
        return $custom;
    }
    if ($post->post_type === 'page' && boblift_is_front_page_or_translation($post->ID)) {
        return '/';
    }
    return boblift_normalize_public_path($default !== '' ? $default : '/' . boblift_post_shared_slug($post) . '.html');
}

/**
 * Translations share the default-language slug in their address (/ja/slug.html), because Polylang
 * (free) stores them with a numbered slug such as slug-2. The .html resolver finds the default-language
 * post by that slug and swaps in the current language's translation.
 */
function boblift_post_shared_slug(WP_Post $post): string
{
    if (!function_exists('pll_get_post_language') || !function_exists('pll_default_language') || !function_exists('pll_get_post')) {
        return $post->post_name;
    }
    $language = (string) pll_get_post_language($post->ID, 'slug');
    $default = (string) pll_default_language('slug');
    if ($language === '' || $default === '' || $language === $default) {
        return $post->post_name;
    }
    $source_id = (int) pll_get_post($post->ID, $default);
    $source = $source_id > 0 ? get_post($source_id) : null;
    if (!$source instanceof WP_Post || $source->post_name === '' || $source->post_type !== $post->post_type) {
        return $post->post_name;
    }
    return $source->post_name;
}

/** Old numbered translation addresses (/ja/slug-2.html) answer with a 301 to the shared-slug address. */
function boblift_redirect_numbered_translation_slug(): void
{
    if (!is_singular(['post', 'page', 'product'])) {
        return;
    }
    $post = get_queried_object();
    if (!$post instanceof WP_Post) {
        return;
    }
    // A post with its own public URL keeps it; its numbered address is not redirected.
    if (boblift_normalize_public_path((string) get_post_meta($post->ID, '_boblift_public_url', true)) !== '') {
        return;
    }
    $shared = boblift_post_shared_slug($post);
    if ($shared === $post->post_name) {
        return;
    }
    $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    if (!preg_match('#/([^/]+)\.html$#', $path, $m) || sanitize_title(rawurldecode($m[1])) !== $post->post_name) {
        return;
    }
    $target = (string) get_permalink($post);
    if ($target === '' || untrailingslashit((string) parse_url($target, PHP_URL_PATH)) === untrailingslashit($path)) {
        return;
    }
    $query = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_QUERY);
    if ($query !== '') {
        $target .= (str_contains($target, '?') ? '&' : '?') . $query;
    }
    wp_safe_redirect($target, 301);
    exit;
}
add_action('template_redirect', 'boblift_redirect_numbered_translation_slug', 5);

function boblift_page_public_path_by_slug(string $slug, string $fallback): string
{
    $page = get_page_by_path($slug, OBJECT, 'page');
    return $page instanceof WP_Post ? boblift_post_public_path(boblift_post_for_current_language($page), $fallback) : boblift_normalize_public_path($fallback);
}

function boblift_product_archive_path(): string
{
    return boblift_page_public_path_by_slug('products', '/products.html');
}

function boblift_product_archive_url(): string
{
    return boblift_public_path_url(boblift_product_archive_path());
}

function boblift_case_archive_path(): string
{
    return boblift_page_public_path_by_slug('cases', '/cases.html');
}

function boblift_case_archive_url(): string
{
    return boblift_public_path_url(boblift_case_archive_path());
}

function boblift_product_category_path(WP_Term $term): string
{
    $custom = boblift_normalize_public_path((string) get_term_meta($term->term_id, '_boblift_public_url', true));
    return $custom !== '' ? $custom : boblift_normalize_public_path('/' . $term->slug);
}

function boblift_article_category_path(WP_Term $term): string
{
    $custom = boblift_normalize_public_path((string) get_term_meta($term->term_id, '_boblift_public_url', true));
    return $custom !== '' ? $custom : boblift_normalize_public_path('/' . $term->slug . '.html');
}

function boblift_public_url_for_path(string $path): string
{
    $path = trim($path, '/');
    if ($path === '') {
        return boblift_public_path_url('/');
    }
    if (isset(boblift_public_custom_url_routes()[$path])) {
        return boblift_public_path_url('/' . $path);
    }
    if ($path === 'products') {
        return boblift_product_archive_url();
    }
    if ($path === 'cases') {
        return boblift_case_archive_url();
    }
    if (preg_match('#^products/category/([^/]+)$#', $path, $matches)) {
        $term = get_term_by('slug', sanitize_title($matches[1]), 'product_category');
        return $term instanceof WP_Term ? get_term_link($term) : boblift_public_path_url('/' . $matches[1]);
    }
    if (preg_match('#^products/([^/]+)$#', $path, $matches)) {
        return boblift_html_url($matches[1]);
    }
    if (preg_match('#^news/([^/]+)$#', $path, $matches)) {
        return boblift_html_url($matches[1]);
    }
    if (!str_contains($path, '/')) {
        $slug = sanitize_title((string) preg_replace('/\.html$/', '', $path));
        $page = get_page_by_path($slug, OBJECT, 'page');
        if ($page instanceof WP_Post) {
            return boblift_public_path_url(boblift_post_public_path(boblift_post_for_current_language($page)));
        }
        $product = get_page_by_path($slug, OBJECT, 'product');
        if ($product instanceof WP_Post) {
            return get_permalink($product);
        }
        $post = get_page_by_path($slug, OBJECT, 'post');
        if ($post instanceof WP_Post) {
            return get_permalink($post);
        }
        $product_term = get_term_by('slug', $slug, 'product_category');
        if ($product_term instanceof WP_Term) {
            $term_link = get_term_link($product_term);
            if (!is_wp_error($term_link)) {
                return $term_link;
            }
        }
        $article_term = get_term_by('slug', $slug, 'category');
        if ($article_term instanceof WP_Term) {
            $term_link = get_term_link($article_term);
            if (!is_wp_error($term_link)) {
                return $term_link;
            }
        }
        return str_ends_with($path, '.html') ? boblift_public_path_url('/' . $path) : boblift_html_url($path);
    }
    return boblift_localized_url(home_url('/' . $path . '/'));
}

function boblift_news_permalink(string $permalink, WP_Post $post): string
{
    if ($post->post_type !== 'post') {
        return $permalink;
    }
    $language = function_exists('pll_get_post_language') ? (string) pll_get_post_language($post->ID, 'slug') : null;
    return boblift_public_path_url(boblift_post_public_path($post), $language ?: null);
}
add_filter('post_link', 'boblift_news_permalink', 10, 2);

function boblift_page_html_permalink(string $link, int $post_id): string
{
    $post = get_post($post_id);
    if (!$post instanceof WP_Post || $post->post_type !== 'page') {
        return $link;
    }
    $language = function_exists('pll_get_post_language') ? (string) pll_get_post_language($post->ID, 'slug') : null;
    return boblift_public_path_url(boblift_post_public_path($post), $language ?: null);
}
add_filter('page_link', 'boblift_page_html_permalink', 10, 2);

function boblift_product_html_permalink(string $link, WP_Post $post): string
{
    if ($post->post_type !== 'product') {
        return $link;
    }
    $language = function_exists('pll_get_post_language') ? (string) pll_get_post_language($post->ID, 'slug') : null;
    return boblift_public_path_url(boblift_post_public_path($post), $language ?: null);
}
add_filter('post_type_link', 'boblift_product_html_permalink', 10, 2);

// Feedback is one entry for every language, so its link stays on the domain of the page showing it.
function boblift_feedback_permalink(string $link, WP_Post $post): string
{
    if ($post->post_type !== 'feedback' || $post->post_status !== 'publish') {
        return $link;
    }
    return trailingslashit(boblift_public_path_url('/feedback/' . $post->post_name));
}
add_filter('post_type_link', 'boblift_feedback_permalink', 10, 2);

function boblift_product_archive_html_link(string $link, string $post_type): string
{
    if ($post_type === 'product') {
        return boblift_product_archive_url();
    }
    if ($post_type === 'case') {
        return boblift_case_archive_url();
    }
    return $link;
}
add_filter('post_type_archive_link', 'boblift_product_archive_html_link', 10, 2);

function boblift_keep_public_url_canonical(string|false $redirect_url, string $requested_url): string|false
{
    $path = boblift_normalize_public_path(boblift_strip_language_prefix((string) parse_url($requested_url, PHP_URL_PATH)));
    if ($path === '') {
        return $redirect_url;
    }
    if ($path === '/sitemap.xml' || $path === '/sitemap.xsl' || $path === '/sitemap-index.xsl' || (str_starts_with($path, '/sitemap-') && str_ends_with($path, '.xml'))) {
        return false;
    }
    $slug = trim($path, '/');
    if ($path === boblift_product_archive_path() || $path === boblift_case_archive_path() || str_ends_with($path, '.html') || isset(boblift_public_custom_url_routes()[$slug]) || get_term_by('slug', $slug, 'product_category') instanceof WP_Term) {
        return false;
    }
    return $redirect_url;
}
add_filter('redirect_canonical', 'boblift_keep_public_url_canonical', 10, 2);

function boblift_product_meta_keys(): array
{
    return ['model', 'capacity', 'capacity_value', 'boom_type', 'catalog_pdf_url', 'applications', 'status', 'short_description', 'tags', 'specs', 'features', 'detail_images', 'gallery', 'hotspots', 'seo_title', 'seo_description'];
}

function boblift_case_meta_keys(): array
{
    return ['type', 'summary', 'facts', 'sections', 'case_kind', 'country', 'product_name', 'application', 'feedback_quote', 'feedback_media_type', 'feedback_media_url', 'seo_title', 'seo_description'];
}

function boblift_register_meta_boxes(): void
{
    add_meta_box('boblift_product_details', '产品参数', 'boblift_product_meta_box', 'product', 'normal', 'high');
    add_meta_box('boblift_case_details', '案例详情', 'boblift_case_meta_box', 'case', 'normal', 'high');
    add_meta_box('boblift_inquiry_details', '询盘详情', 'boblift_inquiry_meta_box', 'inquiry', 'normal', 'high');
    add_meta_box('boblift_feedback_details', '反馈内容', 'boblift_feedback_meta_box', 'feedback', 'normal', 'high');
}
add_action('add_meta_boxes', 'boblift_register_meta_boxes');

function boblift_admin_metabox_styles(): void
{
    echo '<style>.boblift-structured-admin{display:grid;gap:18px;max-width:1180px}.boblift-admin-box{border:1px solid #dcdcde;border-radius:14px;background:#fff;padding:18px}.boblift-admin-box h3{margin:0 0 14px;font-size:15px}.boblift-admin-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.boblift-admin-field{display:grid;gap:6px}.boblift-admin-field label{font-weight:700;color:#1d2327}.boblift-admin-field input,.boblift-admin-field textarea,.boblift-admin-field select{width:100%}.boblift-admin-field textarea{min-height:92px}.boblift-admin-field-full{grid-column:1/-1}.boblift-admin-repeat{display:grid;gap:10px}.boblift-admin-repeat-row{display:grid;grid-template-columns:minmax(0,0.82fr) minmax(0,1.18fr);gap:10px;align-items:start;padding:12px;border:1px solid #dcdcde;border-radius:12px;background:#fbfbfc}.boblift-admin-repeat-row textarea{min-height:74px}.boblift-hotspot-editor{display:grid;gap:14px}.boblift-hotspot-stage{position:relative;display:block;aspect-ratio:1/1;width:min(100%,620px);overflow:hidden;border:1px solid #dcdcde;border-radius:16px;background:linear-gradient(135deg,#f8fafc,#eef2f7);cursor:crosshair}.boblift-hotspot-stage img{position:absolute;inset:0;width:100%;height:100%;object-fit:contain;padding:0}.boblift-hotspot-layer{position:absolute;inset:0}.boblift-hotspot-point{position:absolute;display:grid;width:32px;height:32px;place-items:center;transform:translate(-50%,-50%);border:4px solid rgba(255,255,255,.86);border-radius:999px;background:#2563eb;color:#fff;font-size:12px;font-weight:900;box-shadow:0 12px 28px rgba(37,99,235,.26);cursor:grab}.boblift-hotspot-point.is-active{outline:3px solid #f59e0b}.boblift-hotspot-list{display:grid;gap:8px;max-width:760px}.boblift-hotspot-row{display:grid;grid-template-columns:32px minmax(0,1fr) auto;gap:8px;align-items:center;padding:10px;border:1px solid #dcdcde;border-radius:12px;background:#fbfbfc}.boblift-hotspot-row.is-active{border-color:#f59e0b;background:#fffbeb}.boblift-hotspot-row-index{display:grid;width:26px;height:26px;place-items:center;border-radius:999px;background:#2563eb;color:#fff;font-size:12px;font-weight:900}.boblift-admin-help{margin:6px 0 0;color:#646970;font-size:12px}.boblift-inquiry-copy{margin:0 0 12px}.boblift-inquiry-table th{width:150px}@media(max-width:782px){.boblift-admin-fields,.boblift-admin-repeat-row{grid-template-columns:1fr}.boblift-admin-field-full{grid-column:auto}}</style>';
}

function boblift_meta_value(int $post_id, string $key): string
{
    return (string) get_post_meta($post_id, '_' . $key, true);
}

function boblift_decode_meta_rows(mixed $value): array
{
    if (function_exists('boblift_decode_meta')) {
        $decoded = boblift_decode_meta($value, []);
        return is_array($decoded) ? array_values($decoded) : [];
    }
    $decoded = is_string($value) ? json_decode($value, true) : $value;
    return is_array($decoded) ? array_values($decoded) : [];
}

function boblift_lines_from_csv(string $value): string
{
    return implode("\n", array_values(array_filter(array_map('trim', explode(',', $value)))));
}

function boblift_render_text_field(string $name, string $label, string $value, string $type = 'text'): void
{
    echo '<div class="boblift-admin-field"><label>' . esc_html($label) . '</label><input type="' . esc_attr($type) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '"></div>';
}

function boblift_render_select_field(string $name, string $label, string $value, array $options, string $empty_label = '不适用 / 未设置'): void
{
    echo '<div class="boblift-admin-field"><label>' . esc_html($label) . '</label><select name="' . esc_attr($name) . '">';
    echo '<option value=""' . selected($value, '', false) . '>' . esc_html($empty_label) . '</option>';
    foreach ($options as $option_value => $option_label) {
        echo '<option value="' . esc_attr((string) $option_value) . '"' . selected($value, (string) $option_value, false) . '>' . esc_html((string) $option_label) . '</option>';
    }
    echo '</select></div>';
}

function boblift_render_textarea_field(string $name, string $label, string $value, string $class = ''): void
{
    echo '<div class="boblift-admin-field ' . esc_attr($class) . '"><label>' . esc_html($label) . '</label><textarea name="' . esc_attr($name) . '">' . esc_textarea($value) . '</textarea></div>';
}

function boblift_render_pair_rows(string $base, array $rows, array $keys, array $labels, int $min_rows = 4): void
{
    $count = max($min_rows, count($rows) + 2);
    echo '<div class="boblift-admin-repeat">';
    for ($i = 0; $i < $count; $i++) {
        $row = is_array($rows[$i] ?? null) ? $rows[$i] : [];
        echo '<div class="boblift-admin-repeat-row">';
        foreach ($keys as $index => $key) {
            $tag = $index >= 1 && in_array($key, ['text', 'body', 'detail'], true) ? 'textarea' : 'input';
            echo '<div class="boblift-admin-field"><label>' . esc_html($labels[$index]) . '</label>';
            if ($tag === 'textarea') {
                echo '<textarea name="' . esc_attr($base . '[' . $i . '][' . $key . ']') . '">' . esc_textarea((string) ($row[$key] ?? '')) . '</textarea>';
            } else {
                $value = (string) ($row[$key] ?? ($key === 'image' ? ($row['url'] ?? '') : ''));
                echo '<input type="text" name="' . esc_attr($base . '[' . $i . '][' . $key . ']') . '" value="' . esc_attr($value) . '">';
            }
            echo '</div>';
        }
        echo '</div>';
    }
    echo '</div>';
}

function boblift_admin_asset_url(string $image): string
{
    return str_starts_with($image, 'http') ? $image : boblift_asset($image);
}

function boblift_default_product_hotspots(): array
{
    return [
        ['label' => 'Telescopic Boom', 'x' => 58, 'y' => 18],
        ['label' => 'Hydraulic Cylinder', 'x' => 65, 'y' => 36],
        ['label' => 'Outriggers', 'x' => 48, 'y' => 85],
        ['label' => 'Control System', 'x' => 66, 'y' => 55],
    ];
}

function boblift_render_hotspot_editor(string $base, array $rows, string $image): void
{
    $items = [];
    foreach ($rows as $row) {
        if (!is_array($row) || trim((string) ($row['label'] ?? '')) === '') {
            continue;
        }
        $items[] = [
            'label' => (string) $row['label'],
            'x' => max(0, min(100, (float) ($row['x'] ?? 50))),
            'y' => max(0, min(100, (float) ($row['y'] ?? 50))),
        ];
    }
    if (!$items) {
        $items = boblift_default_product_hotspots();
    }

    echo '<div class="boblift-hotspot-editor" data-boblift-hotspot-editor data-base="' . esc_attr($base) . '" data-next-index="' . esc_attr((string) count($items)) . '">';
    echo '<div class="boblift-hotspot-stage" data-hotspot-stage><img src="' . esc_url($image) . '" alt=""><div class="boblift-hotspot-layer" data-hotspot-layer>';
    foreach ($items as $index => $item) {
        echo '<button type="button" class="boblift-hotspot-point" data-hotspot-point data-index="' . esc_attr((string) $index) . '" style="left:' . esc_attr((string) $item['x']) . '%;top:' . esc_attr((string) $item['y']) . '%">' . esc_html((string) ($index + 1)) . '</button>';
    }
    echo '</div></div>';
    echo '<p class="boblift-admin-help">点击图片新增锚点；拖动蓝色圆点调整位置。前台使用同一个正方形图片舞台，所以后台看到的位置就是前台位置。</p>';
    echo '<div class="boblift-hotspot-list" data-hotspot-list>';
    foreach ($items as $index => $item) {
        echo '<div class="boblift-hotspot-row" data-hotspot-row data-index="' . esc_attr((string) $index) . '"><span class="boblift-hotspot-row-index">' . esc_html((string) ($index + 1)) . '</span><input type="text" name="' . esc_attr($base . '[' . $index . '][label]') . '" value="' . esc_attr((string) $item['label']) . '" data-hotspot-label><input type="hidden" name="' . esc_attr($base . '[' . $index . '][x]') . '" value="' . esc_attr((string) $item['x']) . '" data-hotspot-x><input type="hidden" name="' . esc_attr($base . '[' . $index . '][y]') . '" value="' . esc_attr((string) $item['y']) . '" data-hotspot-y><button type="button" class="button" data-hotspot-remove>删除</button></div>';
    }
    echo '</div><button type="button" class="button" data-hotspot-add>添加锚点</button>';
    echo '</div>';
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-boblift-hotspot-editor]').forEach(function (editor) {
            if (editor.dataset.ready === '1') return;
            editor.dataset.ready = '1';
            var stage = editor.querySelector('[data-hotspot-stage]');
            var layer = editor.querySelector('[data-hotspot-layer]');
            var list = editor.querySelector('[data-hotspot-list]');
            var addButton = editor.querySelector('[data-hotspot-add]');
            var base = editor.dataset.base || 'boblift_meta[hotspot_rows]';
            var dragging = null;
            var nextIndex = Number(editor.dataset.nextIndex || 0);
            var clamp = function (value) { return Math.max(0, Math.min(100, value)); };
            var pointByIndex = function (index) { return editor.querySelector('[data-hotspot-point][data-index="' + index + '"]'); };
            var rowByIndex = function (index) { return editor.querySelector('[data-hotspot-row][data-index="' + index + '"]'); };
            var renumber = function () {
                Array.from(editor.querySelectorAll('[data-hotspot-point]')).forEach(function (point, order) { point.textContent = String(order + 1); });
                Array.from(editor.querySelectorAll('.boblift-hotspot-row-index')).forEach(function (node, order) { node.textContent = String(order + 1); });
            };
            var select = function (index) {
                var selectedIndex = String(index);
                editor.querySelectorAll('[data-hotspot-point], [data-hotspot-row]').forEach(function (node) { node.classList.toggle('is-active', node.dataset.index === selectedIndex); });
            };
            var setPosition = function (index, x, y) {
                x = clamp(x); y = clamp(y);
                var point = pointByIndex(index);
                var row = rowByIndex(index);
                if (!point || !row) return;
                point.style.left = x + '%';
                point.style.top = y + '%';
                var xInput = row.querySelector('[data-hotspot-x]');
                var yInput = row.querySelector('[data-hotspot-y]');
                if (xInput) xInput.value = x.toFixed(2);
                if (yInput) yInput.value = y.toFixed(2);
            };
            var positionFromEvent = function (event) {
                var rect = stage.getBoundingClientRect();
                return { x: clamp(((event.clientX - rect.left) / rect.width) * 100), y: clamp(((event.clientY - rect.top) / rect.height) * 100) };
            };
            var escapeAttr = function (value) { return String(value).replace(/&/g, '&amp;').replace(/"/g, '&quot;'); };
            var createHotspot = function (x, y, label) {
                var index = nextIndex++;
                var row = document.createElement('div');
                row.className = 'boblift-hotspot-row';
                row.dataset.hotspotRow = '';
                row.dataset.index = String(index);
                row.innerHTML = '<span class="boblift-hotspot-row-index"></span><input type="text" name="' + base + '[' + index + '][label]" value="' + escapeAttr(label) + '" data-hotspot-label><input type="hidden" name="' + base + '[' + index + '][x]" data-hotspot-x><input type="hidden" name="' + base + '[' + index + '][y]" data-hotspot-y><button type="button" class="button" data-hotspot-remove>删除</button>';
                list.appendChild(row);
                var point = document.createElement('button');
                point.type = 'button';
                point.className = 'boblift-hotspot-point';
                point.dataset.hotspotPoint = '';
                point.dataset.index = String(index);
                layer.appendChild(point);
                setPosition(index, x, y);
                renumber();
                select(index);
            };
            editor.addEventListener('input', function (event) {
                var input = event.target.closest('[data-hotspot-label]');
                if (!input) return;
                var row = input.closest('[data-hotspot-row]');
                var point = row ? pointByIndex(row.dataset.index) : null;
                if (point) point.setAttribute('aria-label', input.value);
            });
            editor.addEventListener('click', function (event) {
                var remove = event.target.closest('[data-hotspot-remove]');
                if (remove) {
                    var row = remove.closest('[data-hotspot-row]');
                    var point = row ? pointByIndex(row.dataset.index) : null;
                    if (point) point.remove();
                    if (row) row.remove();
                    renumber();
                    return;
                }
                var row = event.target.closest('[data-hotspot-row]');
                if (row) select(row.dataset.index);
            });
            stage.addEventListener('click', function (event) {
                if (event.target.closest('[data-hotspot-point]') || dragging) return;
                var pos = positionFromEvent(event);
                createHotspot(pos.x, pos.y, '新锚点');
            });
            layer.addEventListener('pointerdown', function (event) {
                var point = event.target.closest('[data-hotspot-point]');
                if (!point) return;
                event.preventDefault();
                event.stopPropagation();
                select(point.dataset.index);
                dragging = point.dataset.index;
                point.setPointerCapture && point.setPointerCapture(event.pointerId);
            });
            layer.addEventListener('pointermove', function (event) {
                if (dragging === null) return;
                var pos = positionFromEvent(event);
                setPosition(dragging, pos.x, pos.y);
            });
            layer.addEventListener('pointerup', function () { window.setTimeout(function () { dragging = null; }, 0); });
            addButton && addButton.addEventListener('click', function () { createHotspot(50, 50, '新锚点'); });
            renumber();
            var first = editor.querySelector('[data-hotspot-row]');
            if (first) select(first.dataset.index);
        });
    });
    </script>
    <?php
}

function boblift_product_meta_box(WP_Post $post): void
{
    wp_nonce_field('boblift_save_product_meta', 'boblift_product_meta_nonce');
    boblift_admin_metabox_styles();
    $specs = boblift_decode_meta_rows(get_post_meta($post->ID, '_specs', true));
    $features = boblift_decode_meta_rows(get_post_meta($post->ID, '_features', true));
    $detail_images = boblift_decode_meta_rows(get_post_meta($post->ID, '_detail_images', true));
    $gallery = boblift_decode_meta_rows(get_post_meta($post->ID, '_gallery', true));
    $hotspots = boblift_decode_meta_rows(get_post_meta($post->ID, '_hotspots', true));
    $thumbnail = get_the_post_thumbnail_url($post, 'large');
    $hotspot_image = $thumbnail ?: boblift_asset('images/products/crane-accessories.png');
    ?>
    <div class="boblift-structured-admin">
        <div class="boblift-admin-box"><h3>产品正文</h3><label><input type="checkbox" name="boblift_meta[detail_body_ready]" value="1" <?php checked(boblift_product_content_ready((int) $post->ID, '_detail_body_ready')); ?>> 在产品页显示正文</label><p class="boblift-admin-help">正文就是上方编辑器里的内容，显示在"技术参数"下方。勾选前请确认内容已按画册核对；未勾选时产品页不显示正文。旧网站的原文已备份在 _legacy_content。</p></div>
        <div class="boblift-admin-box"><h3>基础信息</h3><div class="boblift-admin-fields">
            <?php boblift_render_text_field('boblift_meta[model]', '型号', boblift_meta_value($post->ID, 'model')); ?>
            <?php boblift_render_text_field('boblift_meta[capacity]', '吨位 / 起重能力', boblift_meta_value($post->ID, 'capacity')); ?>
            <?php boblift_render_text_field('boblift_meta[capacity_value]', '吨位数值', boblift_meta_value($post->ID, 'capacity_value'), 'number'); ?>
            <?php
                $boom_raw = trim((string) boblift_meta_value($post->ID, 'boom_type'));
                $boom_options = boblift_product_boom_options();
                $boom_selected = boblift_normalize_product_boom_type($boom_raw);
                // A value saved before the options were fixed stays selectable, so an unrelated save does not blank it.
                if ($boom_raw !== '' && $boom_selected === '' && !isset($boom_options[$boom_raw])) {
                    $boom_options[$boom_raw] = $boom_raw . '（原有值）';
                    $boom_selected = $boom_raw;
                }
                boblift_render_select_field('boblift_meta[boom_type]', '吊臂类型', $boom_selected, $boom_options);
            ?>
            <?php boblift_render_text_field('boblift_meta[catalog_pdf_url]', 'PDF目录文件URL', boblift_meta_value($post->ID, 'catalog_pdf_url'), 'url'); ?>
            <?php boblift_render_text_field('boblift_meta[status]', '状态', boblift_meta_value($post->ID, 'status')); ?>
            <?php boblift_render_text_field('boblift_meta[seo_title]', 'SEO 标题', boblift_meta_value($post->ID, 'seo_title')); ?>
            <?php boblift_render_textarea_field('boblift_meta[seo_description]', 'SEO 描述', boblift_meta_value($post->ID, 'seo_description'), 'boblift-admin-field-full'); ?>
        </div></div>
        <div class="boblift-admin-box"><h3>描述信息</h3><div class="boblift-admin-fields">
            <?php boblift_render_textarea_field('boblift_meta[short_description]', '短描述', boblift_meta_value($post->ID, 'short_description'), 'boblift-admin-field-full'); ?>
            <?php boblift_render_textarea_field('boblift_meta[applications_lines]', '应用场景，每行一个', boblift_lines_from_csv(boblift_meta_value($post->ID, 'applications'))); ?>
            <?php boblift_render_textarea_field('boblift_meta[tags_lines]', '标签，每行一个', boblift_lines_from_csv(boblift_meta_value($post->ID, 'tags'))); ?>
        </div></div>
        <div class="boblift-admin-box"><h3>技术参数</h3><p class="boblift-admin-help">保存后仍写入原 _specs JSON，前台无需改动。</p><?php boblift_render_pair_rows('boblift_meta[specs_rows]', $specs, ['label', 'value'], ['参数名', '参数值'], 8); ?></div>
        <div class="boblift-admin-box"><h3>产品特点</h3><?php boblift_render_pair_rows('boblift_meta[features_rows]', $features, ['title', 'text', 'detail', 'image'], ['标题', '说明', '详细说明（可选，品册导入的内容）', '图片地址（可选，品册导入的内容）'], 5); ?></div>
        <div class="boblift-admin-box"><h3>产品详情图（已停用）</h3><p class="boblift-admin-help">产品页不再显示详情图，这里的数据仅作保留。</p><?php boblift_render_pair_rows('boblift_meta[detail_image_rows]', $detail_images, ['image', 'label'], ['详情图地址', '图片说明'], 4); ?></div>
        <?php
        // Older rows keep their photo under "url"; show it as the image so a save does not drop it.
        $color_variants_saved = array_map(static function ($row) {
            if (is_array($row) && trim((string) ($row['image'] ?? '')) === '' && trim((string) ($row['url'] ?? '')) !== '') {
                $row['image'] = (string) $row['url'];
            }
            return $row;
        }, boblift_decode_meta_rows(get_post_meta($post->ID, '_color_variants', true)));
        ?>
        <?php if ($color_variants_saved !== []) : ?>
            <div class="boblift-admin-box"><h3>旧版颜色图片（仍显示在产品画廊里）</h3><p class="boblift-admin-help">这些是旧版「颜色切换」保存的图片，现在作为产品画廊的一部分显示。可以修改、删除；全部清空并保存即可移除。</p>
                <input type="hidden" name="boblift_meta[color_variants_present]" value="1">
                <?php boblift_render_pair_rows('boblift_meta[color_variant_rows]', $color_variants_saved, ['label', 'image', 'color'], ['名称', '图片地址', '颜色（可选）'], 3); ?>
            </div>
        <?php endif; ?>
        <div class="boblift-admin-box"><h3>产品照片画廊</h3><p class="boblift-admin-help">只放产品实拍照片，不放参数表、详情长图。<strong>尺寸：横版 4:3，建议 1600×1200 像素（不小于 1200×900），JPG 或 WebP，单张 500KB 以内。</strong>画面铺满、不留白边，产品完整不被裁切，不加 Logo、水印和文字。比例不是 4:3 的照片也能完整显示，但两侧或上下会留灰底。填写图片 URL 或 assets 相对路径，例如 images/products/crane-accessories.png。</p><?php boblift_render_pair_rows('boblift_meta[gallery_rows]', $gallery, ['image', 'label'], ['照片地址', '照片标签'], 8); ?></div>
        <div class="boblift-admin-box"><h3>产品图锚点</h3><?php boblift_render_hotspot_editor('boblift_meta[hotspot_rows]', $hotspots, $hotspot_image); ?></div>
    </div>
    <?php
}

function boblift_case_meta_box(WP_Post $post): void
{
    wp_nonce_field('boblift_save_case_meta', 'boblift_case_meta_nonce');
    boblift_admin_metabox_styles();
    $facts = boblift_decode_meta_rows(get_post_meta($post->ID, '_facts', true));
    $sections = boblift_decode_meta_rows(get_post_meta($post->ID, '_sections', true));
    ?>
    <div class="boblift-structured-admin">
        <div class="boblift-admin-box"><h3>基础信息</h3><div class="boblift-admin-fields">
            <div class="boblift-admin-field"><label>案例板块</label><select name="boblift_case_meta[case_kind]">
                <?php $case_kind = boblift_meta_value($post->ID, 'case_kind') ?: 'scene_case'; ?>
                <option value="scene_case" <?php selected($case_kind, 'scene_case'); ?>>使用场景案例</option>
                <option value="customer_feedback" <?php selected($case_kind, 'customer_feedback'); ?>>客户反馈</option>
            </select></div>
            <?php boblift_render_text_field('boblift_case_meta[type]', '案例类型', boblift_meta_value($post->ID, 'type')); ?>
            <?php boblift_render_text_field('boblift_case_meta[country]', '国家 / 地区', boblift_meta_value($post->ID, 'country')); ?>
            <?php boblift_render_text_field('boblift_case_meta[product_name]', '产品 / 设备名称', boblift_meta_value($post->ID, 'product_name')); ?>
            <?php boblift_render_text_field('boblift_case_meta[application]', '使用场景 / 工况', boblift_meta_value($post->ID, 'application')); ?>
            <?php boblift_render_text_field('boblift_case_meta[seo_title]', 'SEO 标题', boblift_meta_value($post->ID, 'seo_title')); ?>
            <?php boblift_render_textarea_field('boblift_case_meta[summary]', '摘要', boblift_meta_value($post->ID, 'summary'), 'boblift-admin-field-full'); ?>
            <?php boblift_render_textarea_field('boblift_case_meta[seo_description]', 'SEO 描述', boblift_meta_value($post->ID, 'seo_description'), 'boblift-admin-field-full'); ?>
        </div></div>
        <div class="boblift-admin-box"><h3>客户反馈媒体</h3><div class="boblift-admin-fields">
            <div class="boblift-admin-field"><label>媒体类型</label><select name="boblift_case_meta[feedback_media_type]">
                <?php $feedback_media_type = boblift_meta_value($post->ID, 'feedback_media_type') ?: 'image'; ?>
                <option value="image" <?php selected($feedback_media_type, 'image'); ?>>图片</option>
                <option value="video" <?php selected($feedback_media_type, 'video'); ?>>视频（直链 / YouTube）</option>
            </select></div>
            <?php boblift_render_text_field('boblift_case_meta[feedback_media_url]', '反馈图片 / 视频 URL', boblift_meta_value($post->ID, 'feedback_media_url')); ?>
            <?php boblift_render_textarea_field('boblift_case_meta[feedback_quote]', '客户反馈短句', boblift_meta_value($post->ID, 'feedback_quote'), 'boblift-admin-field-full'); ?>
        </div><p class="boblift-admin-help">客户反馈板块会读取这里的媒体 URL；视频支持 .mp4 / .webm 直链，也支持 YouTube 链接。图片 URL 留空时使用特色图。</p></div>
        <div class="boblift-admin-box"><h3>案例事实</h3><?php boblift_render_pair_rows('boblift_case_meta[facts_rows]', $facts, ['label', 'value'], ['标签', '值'], 4); ?></div>
        <div class="boblift-admin-box"><h3>关联产品</h3><?php boblift_render_related_products_field('boblift_case_meta[related]', $post->ID); ?></div>
        <div class="boblift-admin-box"><h3>现场照片</h3><p class="boblift-admin-help">按顺序配在每个内容段落旁边（第 1 张配第 1 段），多出的照片在段落下方排成照片墙。横版 4:3 或 3:2，长边 1600 像素以上；不要客户人脸、电话、公司名。</p><?php boblift_render_media_picker('boblift_case_meta[gallery]', array_filter(array_map('intval', explode(',', boblift_meta_value($post->ID, 'case_gallery')))), ['image'], '可多选，按选择顺序显示。'); ?></div>
        <div class="boblift-admin-box"><h3>内容段落</h3><?php boblift_render_pair_rows('boblift_case_meta[sections_rows]', $sections, ['heading', 'body'], ['段落标题', '段落正文'], 4); ?></div>
    </div>
    <?php
}

function boblift_inquiry_meta_box(WP_Post $post): void
{
    boblift_admin_metabox_styles();
    $labels = [
        'name' => '姓名',
        'email' => '邮箱',
        'phone' => '电话 / WhatsApp',
        'company' => '公司',
        'website' => '网站',
        'country' => '国家',
        'product' => '意向产品',
        'product_slug' => '产品标识',
        'quantity' => '数量',
        'message' => '项目详情',
        'lead_type' => '线索类型',
        'catalog_url' => 'PDF目录链接',
        'truck_available' => '是否已有卡车',
        'truck_model' => '卡车型号 / 底盘品牌',
        'truck_specs' => '卡车底盘参数',
        'imported_from_china' => '是否从中国进口过',
        'customs_clearance' => '是否可自行清关',
        'destination_port' => '目的港 / 城市',
        'preferred_delivery' => '期盼的交货时间',
        'contact_method' => '期望联系方式',
        'preferred_contact_time' => '期望联系时间',
        'other_requirements' => '其他需求',
        'source_entry' => '来源入口',
        'source_page' => '来源页面',
    ];
    $copy_lines = [];
    foreach ($labels as $key => $label) {
        $value = (string) get_post_meta($post->ID, '_' . $key, true);
        if ($value !== '') {
            $copy_lines[] = $label . '：' . $value;
        }
    }
    echo '<div class="boblift-admin-box"><h3>客户询盘信息</h3><p class="boblift-admin-help">这是前台询盘表单生成的只读详情，不需要像文章一样编辑标题或正文。</p>';
    echo '<button type="button" class="button button-primary boblift-inquiry-copy" onclick="navigator.clipboard && navigator.clipboard.writeText(this.dataset.copy);this.textContent=\'已复制客户信息\';" data-copy="' . esc_attr(implode("\n", $copy_lines)) . '">复制客户信息</button>';
    echo '<table class="widefat striped boblift-inquiry-table"><tbody>';
    foreach ($labels as $key => $label) {
        echo '<tr><th>' . esc_html($label) . '</th><td>' . nl2br(esc_html((string) get_post_meta($post->ID, '_' . $key, true))) . '</td></tr>';
    }
    echo '</tbody></table></div>';
}

function boblift_rows_to_json(array $rows, array $keys): string
{
    $items = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $item = [];
        foreach ($keys as $key) {
            $item[$key] = sanitize_textarea_field($row[$key] ?? '');
        }
        if (implode('', array_map('strval', $item)) !== '') {
            $items[] = $item;
        }
    }
    return wp_json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
}

function boblift_lines_to_csv(mixed $value): string
{
    $lines = is_string($value) ? preg_split('/\r\n|\r|\n/', $value) : [];
    return implode(',', array_values(array_filter(array_map('sanitize_text_field', array_map('trim', $lines ?: [])))));
}

function boblift_hotspots_to_json(array $rows): string
{
    $items = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $label = sanitize_text_field($row['label'] ?? '');
        if ($label === '') {
            continue;
        }
        $items[] = [
            'label' => $label,
            'x' => max(0, min(100, round((float) ($row['x'] ?? 50), 2))),
            'y' => max(0, min(100, round((float) ($row['y'] ?? 50), 2))),
        ];
    }
    return wp_json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
}

function boblift_save_meta_boxes(int $post_id): void
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (isset($_POST['boblift_product_meta_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['boblift_product_meta_nonce'])), 'boblift_save_product_meta')) {
        $meta = isset($_POST['boblift_meta']) && is_array($_POST['boblift_meta']) ? wp_unslash($_POST['boblift_meta']) : [];
        $plain_keys = ['model', 'capacity', 'capacity_value', 'status', 'seo_title'];
        foreach ($plain_keys as $key) {
            update_post_meta($post_id, '_' . $key, sanitize_text_field($meta[$key] ?? ''));
        }
        $posted_boom = trim((string) ($meta['boom_type'] ?? ''));
        $current_boom = trim((string) get_post_meta($post_id, '_boom_type', true));
        // The unchanged "original value" option keeps whatever was saved; anything else is normalised to the known types.
        update_post_meta($post_id, '_boom_type', ($posted_boom !== '' && $posted_boom === $current_boom) ? $current_boom : boblift_normalize_product_boom_type($posted_boom));
        update_post_meta($post_id, '_catalog_pdf_url', esc_url_raw((string) ($meta['catalog_pdf_url'] ?? '')));
        update_post_meta($post_id, '_detail_body_ready', empty($meta['detail_body_ready']) ? '' : '1');
        update_post_meta($post_id, '_detail_images_ready', empty($meta['detail_images_ready']) ? '' : '1');
        foreach (['short_description', 'seo_description'] as $key) {
            update_post_meta($post_id, '_' . $key, sanitize_textarea_field($meta[$key] ?? ''));
        }
        update_post_meta($post_id, '_applications', boblift_lines_to_csv($meta['applications_lines'] ?? ''));
        update_post_meta($post_id, '_tags', boblift_lines_to_csv($meta['tags_lines'] ?? ''));
        update_post_meta($post_id, '_specs', boblift_rows_to_json(is_array($meta['specs_rows'] ?? null) ? $meta['specs_rows'] : [], ['label', 'value']));
        update_post_meta($post_id, '_features', boblift_rows_to_json(is_array($meta['features_rows'] ?? null) ? $meta['features_rows'] : [], ['title', 'text', 'detail', 'image']));
        update_post_meta($post_id, '_detail_images', boblift_rows_to_json(is_array($meta['detail_image_rows'] ?? null) ? $meta['detail_image_rows'] : [], ['image', 'label']));
        update_post_meta($post_id, '_gallery', boblift_rows_to_json(is_array($meta['gallery_rows'] ?? null) ? $meta['gallery_rows'] : [], ['image', 'label']));
        update_post_meta($post_id, '_hotspots', boblift_hotspots_to_json(is_array($meta['hotspot_rows'] ?? null) ? $meta['hotspot_rows'] : []));
        if (isset($meta['color_variants_present'])) {
            update_post_meta($post_id, '_color_variants', boblift_rows_to_json(is_array($meta['color_variant_rows'] ?? null) ? $meta['color_variant_rows'] : [], ['label', 'image', 'color']));
        }
        $post = get_post($post_id);
        if ($post instanceof WP_Post) {
            if (trim((string) get_post_meta($post_id, '_seo_title', true)) === '') {
                update_post_meta($post_id, '_seo_title', boblift_seo_default_title_for_post($post));
            }
            if (trim((string) get_post_meta($post_id, '_seo_description', true)) === '') {
                update_post_meta($post_id, '_seo_description', boblift_seo_default_description_for_post($post));
            }
        }
    }
    if (isset($_POST['boblift_case_meta_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['boblift_case_meta_nonce'])), 'boblift_save_case_meta')) {
        $meta = isset($_POST['boblift_case_meta']) && is_array($_POST['boblift_case_meta']) ? wp_unslash($_POST['boblift_case_meta']) : [];
        $case_kind = sanitize_key((string) ($meta['case_kind'] ?? 'scene_case'));
        update_post_meta($post_id, '_case_kind', in_array($case_kind, ['scene_case', 'customer_feedback'], true) ? $case_kind : 'scene_case');
        $feedback_media_type = sanitize_key((string) ($meta['feedback_media_type'] ?? 'image'));
        update_post_meta($post_id, '_feedback_media_type', $feedback_media_type === 'video' ? 'video' : 'image');
        foreach (['type', 'country', 'product_name', 'application', 'seo_title'] as $key) {
            update_post_meta($post_id, '_' . $key, sanitize_text_field($meta[$key] ?? ''));
        }
        foreach (['summary', 'feedback_quote', 'seo_description'] as $key) {
            update_post_meta($post_id, '_' . $key, sanitize_textarea_field($meta[$key] ?? ''));
        }
        update_post_meta($post_id, '_feedback_media_url', esc_url_raw((string) ($meta['feedback_media_url'] ?? '')));
        update_post_meta($post_id, '_facts', boblift_rows_to_json(is_array($meta['facts_rows'] ?? null) ? $meta['facts_rows'] : [], ['label', 'value']));
        boblift_save_related_products($post_id, $meta['related'] ?? null);
        update_post_meta($post_id, '_case_gallery', implode(',', array_filter(array_map('intval', explode(',', (string) ($meta['gallery'] ?? ''))))));
        update_post_meta($post_id, '_sections', boblift_rows_to_json(is_array($meta['sections_rows'] ?? null) ? $meta['sections_rows'] : [], ['heading', 'body']));
        $post = get_post($post_id);
        if ($post instanceof WP_Post) {
            if (trim((string) get_post_meta($post_id, '_seo_title', true)) === '') {
                update_post_meta($post_id, '_seo_title', boblift_seo_default_title_for_post($post));
            }
            if (trim((string) get_post_meta($post_id, '_seo_description', true)) === '') {
                update_post_meta($post_id, '_seo_description', boblift_seo_default_description_for_post($post));
            }
        }
    }
}
add_action('save_post', 'boblift_save_meta_boxes');

function boblift_product_category_root_rewrite_slugs(): array
{
    if (!function_exists('get_terms')) {
        return [];
    }
    $terms = get_terms(['taxonomy' => 'product_category', 'hide_empty' => false]);
    if (is_wp_error($terms) || empty($terms)) {
        return [];
    }
    $slugs = [];
    foreach ($terms as $term) {
        if ($term instanceof WP_Term) {
            $slugs[] = $term->slug;
        }
    }
    return $slugs;
}

function boblift_public_custom_url_routes(): array
{
    static $routes = null;
    if (is_array($routes)) {
        return $routes;
    }
    $routes = [];
    $pages = get_posts(['post_type' => 'page', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_boblift_public_url']);
    foreach ($pages as $page_id) {
        $path = boblift_normalize_public_path((string) get_post_meta((int) $page_id, '_boblift_public_url', true));
        if ($path !== '') {
            $routes[ltrim($path, '/')] = 'page:' . (int) $page_id;
        }
    }

    foreach (['category', 'product_category'] as $taxonomy) {
        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false, 'lang' => '']);
        if (is_wp_error($terms)) {
            continue;
        }
        foreach ($terms as $term) {
            if (!$term instanceof WP_Term) {
                continue;
            }
            $path = boblift_normalize_public_path((string) get_term_meta($term->term_id, '_boblift_public_url', true));
            if ($path !== '') {
                $route_key = ltrim($path, '/');
                if (!isset($routes[$route_key])) {
                    $routes[$route_key] = $taxonomy . ':' . (int) $term->term_id;
                }
            }
        }
    }
    return $routes;
}

function boblift_public_custom_url_route_target(string $path): string
{
    $path = ltrim(boblift_normalize_public_path($path), '/');
    if ($path === '') {
        return '';
    }

    $language = boblift_current_language_slug();
    $fallback = '';
    $translation_fallback = '';
    $pages = get_posts(['post_type' => 'page', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_boblift_public_url']);
    foreach ($pages as $page_id) {
        $page_path = ltrim(boblift_normalize_public_path((string) get_post_meta((int) $page_id, '_boblift_public_url', true)), '/');
        if ($page_path !== $path) {
            continue;
        }
        $target = 'page:' . (int) $page_id;
        if ($fallback === '') {
            $fallback = $target;
        }
        if ($language !== '' && function_exists('pll_get_post_language') && (string) pll_get_post_language((int) $page_id, 'slug') === $language) {
            return $target;
        }
    }

    foreach (['category', 'product_category'] as $taxonomy) {
        // Every language: a translation may not carry the address itself (ru/fr/ar categories have none), so the page's
        // own language must not hide the term that does.
        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false, 'lang' => '']);
        if (is_wp_error($terms)) {
            continue;
        }
        foreach ($terms as $term) {
            if (!$term instanceof WP_Term) {
                continue;
            }
            $term_path = ltrim(boblift_normalize_public_path((string) get_term_meta($term->term_id, '_boblift_public_url', true)), '/');
            if ($term_path !== $path) {
                continue;
            }
            $target = $taxonomy . ':' . (int) $term->term_id;
            if ($language !== '' && function_exists('pll_get_term_language') && (string) pll_get_term_language($term->term_id, 'slug') === $language) {
                return $target;
            }
            // Otherwise the current language's translation of the term holding the address serves it.
            $translated = $language !== '' && function_exists('pll_get_term') ? (int) pll_get_term($term->term_id, $language) : 0;
            if ($translated > 0 && $translation_fallback === '') {
                $translation_fallback = $taxonomy . ':' . $translated;
            }
            if ($fallback === '') {
                $fallback = $target;
            }
        }
    }

    return $translation_fallback !== '' && !str_starts_with($fallback, 'page:') ? $translation_fallback : $fallback;
}

function boblift_register_public_url_rewrites(): void
{
    foreach (boblift_public_custom_url_routes() as $path => $target) {
        add_rewrite_rule('^' . preg_quote($path, '#') . '/?$', 'index.php?boblift_custom_url=' . rawurlencode($path), 'top');
    }
}
add_action('init', 'boblift_register_public_url_rewrites', 20);

function boblift_product_category_query_vars(array $vars): array
{
    $vars[] = 'boblift_product_category';
    $vars[] = 'boblift_html_slug';
    $vars[] = 'boblift_custom_url';
    return $vars;
}
add_filter('query_vars', 'boblift_product_category_query_vars');

function boblift_resolve_custom_url_request(string $path): array
{
    $path = ltrim(boblift_normalize_public_path($path), '/');
    if ($path === '') {
        return [];
    }

    foreach (boblift_public_custom_url_routes() as $route_path => $target) {
        if ($route_path !== $path) {
            continue;
        }
        $target = boblift_public_custom_url_route_target($path) ?: $target;
        [$type, $id] = array_pad(explode(':', $target, 2), 2, '0');
        if ($type === 'page') {
            $post = get_post((int) $id);
            return $post instanceof WP_Post ? ['page_id' => $post->ID] : [];
        }
        if ($type === 'category') {
            $term = get_term((int) $id, 'category');
            return $term instanceof WP_Term && !is_wp_error($term) ? ['cat' => $term->term_id] : [];
        }
        if ($type === 'product_category') {
            $term = get_term((int) $id, 'product_category');
            return $term instanceof WP_Term && !is_wp_error($term) ? ['boblift_product_category' => $term->slug] : [];
        }
    }
    return [];
}

function boblift_html_request_vars(array $vars): array
{
    $sitemap_name = sanitize_title((string) ($vars['pagename'] ?? $vars['name'] ?? ''));
    if ($sitemap_name === 'sitemap-xml') {
        return ['sitemap' => 'index'];
    }

    $custom_path = (string) ($vars['boblift_custom_url'] ?? '');
    if ($custom_path !== '') {
        $resolved = boblift_resolve_custom_url_request($custom_path);
        if ($resolved) {
            return $resolved;
        }
    }

    $slug = sanitize_title((string) ($vars['boblift_html_slug'] ?? ''));
    if ($slug === '') {
        return $vars;
    }
    unset($vars['boblift_html_slug']);

    $page = get_page_by_path($slug, OBJECT, 'page');
    if (!$page instanceof WP_Post) {
        $custom_target = boblift_public_custom_url_route_target($slug . '.html');
        [$custom_type, $custom_id] = array_pad(explode(':', $custom_target, 2), 2, '0');
        if ($custom_type === 'page') {
            $page = get_post((int) $custom_id);
        }
    }
    if ($page instanceof WP_Post) {
        $page = boblift_post_for_current_language($page);
        return ['page_id' => $page->ID];
    }
    $product = get_page_by_path($slug, OBJECT, 'product');
    if ($product instanceof WP_Post) {
        $product = boblift_post_translation_for_current_language($product);
        return ['post_type' => 'product', 'p' => $product->ID];
    }
    $post = get_page_by_path($slug, OBJECT, 'post');
    if ($post instanceof WP_Post) {
        $post = boblift_post_translation_for_current_language($post);
        return ['p' => $post->ID];
    }
    $category = boblift_term_by_slug_for_current_language($slug, 'category');
    if ($category instanceof WP_Term) {
        return ['cat' => $category->term_id];
    }
    $product_category = boblift_term_by_slug_for_current_language($slug, 'product_category');
    if ($product_category instanceof WP_Term) {
        return ['boblift_product_category' => $product_category->slug];
    }
    return ['error' => '404'];
}
add_filter('request', 'boblift_html_request_vars');

function boblift_product_category_term_link(string $termlink, WP_Term $term, string $taxonomy): string
{
    $language = function_exists('pll_get_term_language') ? (string) pll_get_term_language($term->term_id, 'slug') : null;
    if ($taxonomy === 'product_category') {
        return boblift_public_path_url(boblift_product_category_path($term), $language ?: null);
    }
    if ($taxonomy === 'category') {
        return boblift_public_path_url(boblift_article_category_path($term), $language ?: null);
    }
    return $termlink;
}
add_filter('term_link', 'boblift_product_category_term_link', 10, 3);

function boblift_product_category_template(string $template): string
{
    $slug = sanitize_title((string) get_query_var('boblift_product_category'));
    if ($slug === '') {
        return $template;
    }
    $term = get_term_by('slug', $slug, 'product_category');
    if (!$term instanceof WP_Term) {
        return $template;
    }
    $archive_template = locate_template('archive-product.php');
    return $archive_template !== '' ? $archive_template : $template;
}
add_filter('template_include', 'boblift_product_category_template');

function boblift_public_page_archive_template(string $template): string
{
    if (!is_page()) {
        return $template;
    }

    $post = get_queried_object();
    if (!$post instanceof WP_Post) {
        return $template;
    }

    $template_map = [
        'about' => 'page-about.php',
        'why-choose-us' => 'page-why-choose-us.php',
        'global-customer-visits' => 'page-global-customer-visits.php',
        'social-media' => 'page-social-media.php',
        'factory-tour' => 'page-factory-tour.php',
        'quality-testing' => 'page-quality-testing.php',
        'mounting-shipping' => 'page-mounting-shipping.php',
        'service' => 'page-service.php',
        'videos' => 'page-videos.php',
        'public-welfare' => 'page-public-welfare.php',
        'news' => 'page-news.php',
        'contact' => 'page-contact.php',
        'privacy-policy' => 'page-privacy-policy.php',
        'terms-of-use' => 'page-terms-of-use.php',
        'sitemap' => 'page-sitemap.php',
        'quote' => 'page-quote.php',
        'success' => 'page-success.php',
        'products' => 'archive-product.php',
        'cases' => 'archive-case.php',
    ];

    foreach ($template_map as $source_slug => $template_file) {
        if (boblift_page_is_translation_of_slug($post, $source_slug)) {
            $resolved = locate_template($template_file);
            return $resolved !== '' ? $resolved : $template;
        }
    }

    return $template;
}
add_filter('template_include', 'boblift_public_page_archive_template', 20);

function boblift_flush_product_category_rewrites(): void
{
    flush_rewrite_rules(false);
}
add_action('created_product_category', 'boblift_flush_product_category_rewrites', 20);
add_action('edited_product_category', 'boblift_flush_product_category_rewrites', 20);
add_action('delete_product_category', 'boblift_flush_product_category_rewrites', 20);

function boblift_redirect_with_query(string $target, array $remove = []): void
{
    $query = $_GET;
    foreach ($remove as $key) {
        unset($query[$key]);
    }
    if ($query) {
        $query = array_map(static fn($value) => is_array($value) ? array_map('sanitize_text_field', wp_unslash($value)) : sanitize_text_field(wp_unslash($value)), $query);
        $target = add_query_arg($query, $target);
    }
    wp_safe_redirect($target, 301);
    exit;
}

function boblift_legacy_html_redirects(): void
{
    if (is_admin() || wp_doing_ajax()) {
        return;
    }

    $path = boblift_normalize_public_path(boblift_strip_language_prefix((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH)));
    if ($path === '') {
        return;
    }

    if (is_post_type_archive('case') && $path !== boblift_case_archive_path()) {
        boblift_redirect_with_query(boblift_case_archive_url());
    }

    if (is_post_type_archive('product') && $path !== boblift_product_archive_path()) {
        $category = isset($_GET['category']) && is_string($_GET['category']) ? sanitize_title(wp_unslash($_GET['category'])) : '';
        if ($category !== '') {
            $term = get_term_by('slug', $category, 'product_category');
            if ($term instanceof WP_Term) {
                boblift_redirect_with_query(get_term_link($term), ['category']);
            }
        }
        boblift_redirect_with_query(boblift_product_archive_url());
    }

    if (is_tax('product_category')) {
        $term = get_queried_object();
        if ($term instanceof WP_Term && $path !== boblift_product_category_path($term)) {
            boblift_redirect_with_query(get_term_link($term));
        }
    }

    $category_slug = sanitize_title((string) get_query_var('boblift_product_category'));
    if ($category_slug !== '') {
        $term = get_term_by('slug', $category_slug, 'product_category');
        if ($term instanceof WP_Term && $path !== boblift_product_category_path($term)) {
            boblift_redirect_with_query(get_term_link($term));
        }
    }

    if (is_category()) {
        $term = get_queried_object();
        if ($term instanceof WP_Term && $path !== boblift_article_category_path($term)) {
            boblift_redirect_with_query(get_term_link($term));
        }
    }

    if (is_singular('product') || (is_single() && get_post_type() === 'post') || (is_page() && !is_front_page())) {
        $canonical_path = boblift_normalize_public_path(boblift_strip_language_prefix((string) parse_url(get_permalink(), PHP_URL_PATH)));
        if ($canonical_path !== '' && $path !== $canonical_path) {
            boblift_redirect_with_query(get_permalink());
        }
    }
}
add_action('template_redirect', 'boblift_legacy_html_redirects', 1);

// The product featured image is the hero and card image; tell editors what the stage expects.
add_filter('admin_post_thumbnail_html', static function (string $html, int $post_id): string {
    if (get_post_type($post_id) === 'case') {
        return $html . '<p class="description" style="margin-top:10px;line-height:1.6"><strong>案例封面规格</strong><br>现场实拍横版照片，3:2 或 16:9，长边 1600 像素以上，主体放在画面中间。详情页会按宽幅裁切上下边缘，所以不要把重要内容贴着上下边。不加 Logo、水印和文字。</p>';
    }
    if (get_post_type($post_id) !== 'product') {
        return $html;
    }
    $help = '<p class="description" style="margin-top:10px;line-height:1.6">'
        . '<strong>产品主图规格（二选一）</strong><br>'
        . '1. 透明底抠图（推荐）：PNG，四周只留很窄的透明边（约为图片长边的 2%，不要大片留白），长边 1600 像素以上；产品会站在展示台的地面线上。<br>'
        . '2. 实拍照片：横版 4:3，1600×1200 像素，JPG/WebP，画面铺满、不留白边。<br>'
        . '都不要加 Logo、水印和文字。上传后前台会按图片本身判断是抠图还是照片。'
        . '</p>';
    return $html . $help;
}, 10, 2);

// Record whether a new upload is a cut-out (transparent edges) or a photo, so the product stage frames it
// correctly without relying on the file name. Mirrors the rule tools/image-fit used for the existing library.
add_filter('wp_generate_attachment_metadata', static function (array $metadata, int $attachment_id): array {
    $file = get_attached_file($attachment_id);
    if (!$file || !function_exists('imagecreatefromstring') || get_post_meta($attachment_id, '_boblift_image_fit', true) !== '') {
        return $metadata;
    }
    $fit = 'photo';
    if (preg_match('/\.png$/i', $file)) {
        $image = @imagecreatefrompng($file);
        if ($image && !imageistruecolor($image)) {
            // PNG-8: imagecolorat() returns palette indexes, so convert to truecolor to read the alpha of each pixel.
            imagepalettetotruecolor($image);
        }
        if ($image) {
            $w = imagesx($image);
            $h = imagesy($image);
            $clear = 0;
            $total = 0;
            // Sample the outer 2% ring; a cut-out's edge is mostly transparent, a photo's is opaque.
            $band = max(2, (int) round(min($w, $h) * 0.02));
            $step = max(1, (int) round(max($w, $h) / 400));
            for ($x = 0; $x < $w; $x += $step) {
                foreach ([0, $band - 1, $h - $band, $h - 1] as $y) {
                    $total++;
                    $clear += ((imagecolorat($image, $x, $y) >> 24) & 0x7F) > 64 ? 1 : 0;
                }
            }
            for ($y = 0; $y < $h; $y += $step) {
                foreach ([0, $band - 1, $w - $band, $w - 1] as $x) {
                    $total++;
                    $clear += ((imagecolorat($image, $x, $y) >> 24) & 0x7F) > 64 ? 1 : 0;
                }
            }
            if ($total > 0 && $clear / $total > 0.9) {
                $fit = 'cutout';
            }
        }
    }
    update_post_meta($attachment_id, '_boblift_image_fit', $fit);
    return $metadata;
}, 10, 2);

function boblift_feedback_meta_box(WP_Post $post): void
{
    wp_nonce_field('boblift_feedback_meta', 'boblift_feedback_nonce');
    $country = json_decode((string) get_post_meta($post->ID, '_feedback_country', true), true) ?: [];
    $note = json_decode((string) get_post_meta($post->ID, '_feedback_note', true), true) ?: [];
    $body = json_decode((string) get_post_meta($post->ID, '_feedback_body', true), true) ?: [];
    $ids = array_filter(array_map('intval', explode(',', (string) get_post_meta($post->ID, '_feedback_media', true))));
    ?>
    <style>.boblift-feedback-box .boblift-admin-field{display:grid;grid-template-columns:170px minmax(0,1fr);gap:12px;margin:0 0 12px}.boblift-feedback-box input[type=text]{width:100%;max-width:520px;margin-bottom:6px}</style>
    <div class="boblift-feedback-box">
    <p class="boblift-admin-help">前台只显示国家和型号，不显示客户信息。上传前请确认照片里没有客户人脸、电话、公司名或经销商品牌。</p>
    <?php $languages = boblift_feedback_languages(); ?>
    <?php foreach ($languages as $lang => $lang_label) : ?>
        <div class="boblift-admin-field"><label>国家（<?php echo esc_html($lang_label); ?>）</label><input type="text" name="boblift_feedback[country_<?php echo esc_attr($lang); ?>]" value="<?php echo esc_attr((string) ($country[$lang] ?? '')); ?>"<?php echo $lang === 'en' ? ' placeholder="Mexico"' : ''; ?>></div>
    <?php endforeach; ?>
    <div class="boblift-admin-field"><label>型号</label><input type="text" name="boblift_feedback[model]" value="<?php echo esc_attr((string) get_post_meta($post->ID, '_feedback_model', true)); ?>" placeholder="SQ6.3ZA4"></div>
    <div class="boblift-admin-field"><label>配置说明（各语言，可空）</label><div>
        <?php foreach ($languages as $lang => $lang_label) : ?>
            <input type="text" name="boblift_feedback[note_<?php echo esc_attr($lang); ?>]" value="<?php echo esc_attr((string) ($note[$lang] ?? '')); ?>" placeholder="<?php echo esc_attr($lang === 'en' ? 'with clamshell bucket' : $lang_label); ?>">
        <?php endforeach; ?>
    </div></div>
    <div class="boblift-admin-field"><label>正文（各语言，可空）</label><div class="boblift-feedback-body">
        <?php foreach ($languages as $lang => $lang_label) : ?>
            <p class="description" style="margin:0 0 4px"><?php echo esc_html($lang_label); ?></p>
            <textarea name="boblift_feedback[body_<?php echo esc_attr($lang); ?>]" rows="5" style="width:100%;max-width:720px;margin-bottom:10px"><?php echo esc_textarea((string) ($body[$lang] ?? '')); ?></textarea>
        <?php endforeach; ?>
        <p class="description">显示在详情页图片下方，空行分段。留空时详情页只显示图片 / 视频。同样不要写客户姓名、公司名。</p>
    </div></div>
    <div class="boblift-admin-field"><label>关联产品</label><div><?php boblift_render_related_products_field('boblift_feedback[related]', $post->ID); ?></div></div>
    <div class="boblift-admin-field"><label>图片 / 视频</label><?php boblift_render_media_picker('boblift_feedback[media]', $ids, ['image', 'video'], '可多选；按选择顺序显示，第一张作为封面。视频建议 60 秒以内、720p、无声。'); ?></div>
    </div>
    <?php
}

// Media library picker storing attachment IDs (comma-separated) in one hidden field; order follows the selection.
// "关联产品": checkboxes of the English products, grouped by category. The product page resolves
// the English ID for every language, so one link covers all translations.
function boblift_render_related_products_field(string $name, int $post_id): void
{
    $selected = array_map('intval', get_post_meta($post_id, '_related_product'));
    $groups = [];
    $products = get_posts(['post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => -1, 'lang' => 'en', 'orderby' => 'title', 'order' => 'ASC']);
    // Linked products that are unpublished for now (draft, pending, private, trashed) stay listed and ticked, so saving this entry
    // keeps their link for when they are published again.
    $listed = array_map(static fn(WP_Post $product): int => $product->ID, $products);
    $unlisted = array_values(array_diff($selected, $listed));
    if ($unlisted !== []) {
        $products = array_merge($products, get_posts(['post_type' => 'product', 'post_status' => ['draft', 'pending', 'private', 'future', 'trash'], 'posts_per_page' => -1, 'lang' => '', 'post__in' => $unlisted, 'orderby' => 'title', 'order' => 'ASC']));
    }
    foreach ($products as $product) {
        $terms = get_the_terms($product, 'product_category');
        $group = $terms && !is_wp_error($terms) ? $terms[0]->name : '其他';
        $model = trim((string) get_post_meta($product->ID, '_model', true));
        $status = $product->post_status !== 'publish' ? '（未发布）' : '';
        $groups[$group][] = ['id' => $product->ID, 'label' => ($model !== '' ? $model . ' — ' : '') . $product->post_title . $status];
    }
    ksort($groups);
    echo '<input type="hidden" name="' . esc_attr($name) . '[]" value="">';
    echo '<div style="max-height:320px;overflow:auto;border:1px solid #dcdcde;padding:8px 12px;background:#fff">';
    foreach ($groups as $group => $items) {
        echo '<p style="margin:10px 0 4px;font-weight:700">' . esc_html($group) . '</p>';
        foreach ($items as $item) {
            echo '<label style="display:block;margin:2px 0"><input type="checkbox" name="' . esc_attr($name) . '[]" value="' . esc_attr((string) $item['id']) . '"' . checked(in_array($item['id'], $selected, true), true, false) . '> ' . esc_html($item['label']) . '</label>';
        }
    }
    echo '</div><p class="description">勾选后，这条内容会出现在对应产品详情页。可多选；不勾选就不在任何产品页显示。</p>';
}

function boblift_save_related_products(int $post_id, mixed $values): void
{
    if (!is_array($values)) {
        return;
    }
    delete_post_meta($post_id, '_related_product');
    foreach (array_unique(array_filter(array_map('intval', $values))) as $product_id) {
        if (get_post_type($product_id) === 'product') {
            add_post_meta($post_id, '_related_product', (string) $product_id);
        }
    }
}

function boblift_render_media_picker(string $name, array $ids, array $types, string $help): void
{
    ?>
    <div class="boblift-media-picker" data-types="<?php echo esc_attr(implode(',', $types)); ?>">
        <input type="hidden" class="boblift-media-picker-ids" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr(implode(',', $ids)); ?>">
        <p><button type="button" class="button boblift-media-picker-select"><?php echo esc_html(in_array('video', $types, true) ? '选择图片 / 视频' : '选择图片'); ?></button> <button type="button" class="button boblift-media-picker-clear">清除全部</button> <span class="description"><?php echo esc_html($help); ?></span></p>
        <div class="boblift-media-picker-preview" style="display:flex;flex-wrap:wrap;gap:6px">
            <?php foreach ($ids as $id) : $thumb = wp_attachment_is('video', $id) ? (string) get_the_post_thumbnail_url($id, 'thumbnail') : (string) wp_get_attachment_image_url($id, 'thumbnail'); ?>
                <span style="width:72px;height:54px;border:1px solid #dcdcde;background:#f6f7f7 center/cover no-repeat;<?php echo $thumb !== '' ? 'background-image:url(' . esc_url($thumb) . ')' : ''; ?>" title="<?php echo esc_attr(get_the_title($id)); ?>"><?php echo wp_attachment_is('video', $id) ? '&#9654;' : ''; ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    <script>
    jQuery(function ($) {
      if (window.bobliftMediaPickerReady) return;
      window.bobliftMediaPickerReady = true;
      // Empty the selection (the media modal cannot confirm an empty one): clears the stored IDs and the preview.
      $(document).on('click', '.boblift-media-picker-clear', function (event) {
        event.preventDefault();
        var field = $(this).closest('.boblift-media-picker');
        field.find('.boblift-media-picker-ids').val('');
        field.find('.boblift-media-picker-preview').empty();
      });
      $(document).on('click', '.boblift-media-picker-select', function (event) {
        event.preventDefault();
        var field = $(this).closest('.boblift-media-picker');
        var frame = wp.media({ title: $(this).text(), button: { text: '使用所选' }, multiple: 'add', library: { type: String(field.data('types')).split(',') } });
        frame.on('open', function () {
          var selection = frame.state().get('selection');
          (field.find('.boblift-media-picker-ids').val() || '').split(',').filter(Boolean).forEach(function (id) {
            var attachment = wp.media.attachment(id); attachment.fetch(); selection.add(attachment);
          });
        });
        frame.on('select', function () {
          var items = frame.state().get('selection').toJSON();
          field.find('.boblift-media-picker-ids').val(items.map(function (item) { return item.id; }).join(','));
          var preview = field.find('.boblift-media-picker-preview').empty();
          items.forEach(function (item) {
            var thumb = (item.sizes && item.sizes.thumbnail && item.sizes.thumbnail.url) || (item.image && item.image.src) || '';
            $('<span>').css({ width: 72, height: 54, border: '1px solid #dcdcde', background: '#f6f7f7 center/cover no-repeat', backgroundImage: thumb ? 'url(' + thumb + ')' : '' }).text(item.type === 'video' ? '\u25B6' : '').appendTo(preview);
          });
        });
        frame.open();
      });
    });
    </script>
    <?php
}

/** Languages the feedback editor offers: English, Japanese, Chinese and every other configured site language. */
function boblift_feedback_languages(): array
{
    $languages = ['en' => '英文', 'ja' => '日文', 'zh' => '中文'];
    if (function_exists('pll_languages_list')) {
        $slugs = (array) pll_languages_list(['fields' => 'slug']);
        $names = (array) pll_languages_list(['fields' => 'name']);
        foreach ($slugs as $i => $slug) {
            $slug = sanitize_key((string) $slug);
            if ($slug !== '' && !isset($languages[$slug])) {
                $languages[$slug] = (string) ($names[$i] ?? $slug);
            }
        }
    }
    return $languages;
}

function boblift_save_feedback_meta(int $post_id): void
{
    if (!isset($_POST['boblift_feedback_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['boblift_feedback_nonce'])), 'boblift_feedback_meta')) {
        return;
    }
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $post_id)) {
        return;
    }
    $data = is_array($_POST['boblift_feedback'] ?? null) ? wp_unslash($_POST['boblift_feedback']) : [];
    $text = static fn(string $key): string => sanitize_text_field((string) ($data[$key] ?? ''));
    // Each language is saved from its own field; languages the form did not post (e.g. added by an import) are kept.
    $save_languages = static function (string $meta_key, string $prefix, bool $textarea) use ($post_id, $data): array {
        $values = json_decode((string) get_post_meta($post_id, $meta_key, true), true);
        $values = is_array($values) ? $values : [];
        foreach (array_keys(boblift_feedback_languages()) as $lang) {
            if (array_key_exists($prefix . $lang, $data)) {
                $raw = (string) $data[$prefix . $lang];
                $values[$lang] = trim($textarea ? sanitize_textarea_field($raw) : sanitize_text_field($raw));
            }
        }
        return $values;
    };
    $country = $save_languages('_feedback_country', 'country_', false);
    update_post_meta($post_id, '_feedback_country', wp_slash(wp_json_encode($country, JSON_UNESCAPED_UNICODE)));
    $note = $save_languages('_feedback_note', 'note_', false);
    update_post_meta($post_id, '_feedback_note', array_filter($note) ? wp_slash(wp_json_encode($note, JSON_UNESCAPED_UNICODE)) : '');
    $body = $save_languages('_feedback_body', 'body_', true);
    update_post_meta($post_id, '_feedback_body', array_filter($body) ? wp_slash(wp_json_encode($body, JSON_UNESCAPED_UNICODE)) : '');
    update_post_meta($post_id, '_feedback_model', $text('model'));
    boblift_save_related_products($post_id, $data['related'] ?? null);
    update_post_meta($post_id, '_feedback_media', implode(',', array_filter(array_map('intval', explode(',', $text('media'))))));
}
add_action('save_post_feedback', 'boblift_save_feedback_meta');

// The media picker on the feedback editor needs the media library scripts.
add_action('admin_enqueue_scripts', static function (): void {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if ($screen && in_array($screen->post_type, ['feedback', 'case'], true) && $screen->base === 'post') {
        wp_enqueue_media();
    }
});
