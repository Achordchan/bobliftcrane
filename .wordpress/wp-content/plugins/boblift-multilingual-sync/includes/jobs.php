<?php
if (!defined('ABSPATH')) {
    exit;
}

const BOBLIFT_MLS_DB_VERSION = '20260605_2';

function boblift_mls_jobs_table(): string
{
    global $wpdb;
    return $wpdb->prefix . 'boblift_mls_jobs';
}

function boblift_mls_job_items_table(): string
{
    global $wpdb;
    return $wpdb->prefix . 'boblift_mls_job_items';
}

function boblift_mls_install_jobs_schema(): void
{
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    $jobs = boblift_mls_jobs_table();
    $items = boblift_mls_job_items_table();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta("CREATE TABLE {$jobs} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        job_type VARCHAR(32) NOT NULL DEFAULT 'repair_missing',
        target_language VARCHAR(20) NOT NULL,
        source_language VARCHAR(20) NOT NULL,
        status VARCHAR(32) NOT NULL DEFAULT 'pending',
        total_items INT UNSIGNED NOT NULL DEFAULT 0,
        completed_items INT UNSIGNED NOT NULL DEFAULT 0,
        skipped_items INT UNSIGNED NOT NULL DEFAULT 0,
        failed_items INT UNSIGNED NOT NULL DEFAULT 0,
        overwrite_policy LONGTEXT NULL,
        logs LONGTEXT NULL,
        current_label TEXT NULL,
        created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
        locked_until DATETIME NULL,
        started_at DATETIME NULL,
        finished_at DATETIME NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY job_type (job_type),
        KEY status (status),
        KEY target_language (target_language)
    ) {$charset};");

    dbDelta("CREATE TABLE {$items} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        job_id BIGINT UNSIGNED NOT NULL,
        item_type VARCHAR(32) NOT NULL,
        object_type VARCHAR(64) NOT NULL,
        source_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        target_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        action VARCHAR(64) NOT NULL,
        label TEXT NULL,
        status VARCHAR(32) NOT NULL DEFAULT 'pending',
        field_policy LONGTEXT NULL,
        attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
        error_message TEXT NULL,
        started_at DATETIME NULL,
        finished_at DATETIME NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY job_status (job_id,status),
        KEY action (action),
        KEY object_lookup (item_type,object_type,source_id)
    ) {$charset};");

    update_option('boblift_mls_db_version', BOBLIFT_MLS_DB_VERSION, false);
}

function boblift_mls_maybe_install_jobs_schema(): void
{
    if ((string) get_option('boblift_mls_db_version', '') !== BOBLIFT_MLS_DB_VERSION) {
        boblift_mls_install_jobs_schema();
    }
}
add_action('admin_init', 'boblift_mls_maybe_install_jobs_schema');

function boblift_mls_encode_json(array $value): string
{
    return (string) wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function boblift_mls_decode_json(mixed $value): array
{
    if (!is_string($value) || trim($value) === '') {
        return [];
    }
    $decoded = json_decode($value, true);
    return is_array($decoded) ? $decoded : [];
}

function boblift_mls_job_url(int $job_id, string $return_to = ''): string
{
    $url = admin_url('admin.php?page=boblift-multilingual-sync&job_id=' . $job_id);
    if ($return_to !== '') {
        $url = add_query_arg('return_to', rawurlencode($return_to), $url);
    }
    return $url;
}

function boblift_mls_job_type_label(string $type): string
{
    return [
        'clone_language' => '初始化复制',
        'translate_language' => '翻译文本',
        'publish_language' => '发布语言',
        'repair_missing' => '修复缺失项',
    ][$type] ?? $type;
}

function boblift_mls_validate_job_language(string $target_language): true|WP_Error
{
    if (!in_array($target_language, boblift_mls_languages(), true)) {
        return new WP_Error('boblift_mls_bad_language', '目标语言不存在。');
    }
    if ($target_language === boblift_mls_source_language()) {
        return new WP_Error('boblift_mls_bad_language', '目标语言不能等于主维护语言。');
    }
    return true;
}

function boblift_mls_scan_target_language(string $target_language, array $args = []): array|WP_Error
{
    $source_language = boblift_mls_source_language();
    if (!in_array($target_language, boblift_mls_languages(), true)) {
        return new WP_Error('boblift_mls_bad_language', '目标语言不存在。');
    }
    if ($target_language === $source_language) {
        return new WP_Error('boblift_mls_bad_language', '目标语言不能等于主维护语言。');
    }

    $scope_section = isset($args['section']) ? sanitize_key((string) $args['section']) : '';
    $scope_sections = [];
    if (isset($args['sections']) && is_array($args['sections'])) {
        $allowed_sections = boblift_mls_default_sections();
        foreach ($args['sections'] as $section) {
            $section = sanitize_key((string) $section);
            if ($section !== '' && in_array($section, $allowed_sections, true)) {
                $scope_sections[] = $section;
            }
        }
        $scope_sections = array_values(array_unique($scope_sections));
    }
    $scope_post_id = isset($args['post_id']) ? absint($args['post_id']) : 0;
    $settings_only = !empty($args['settings_only']);
    $scan_mode = isset($args['mode']) && $args['mode'] === 'params' ? 'params' : 'translate';
    $items = [];
    $groups = boblift_mls_empty_scan_groups();

    if (!$settings_only || $scope_section !== '') {
        if ($scope_sections !== []) {
            $sections = $scope_sections;
        } elseif ($scope_section !== '' && $scope_section !== '__all__' && $scope_section !== '__settings_page') {
            $sections = [$scope_section];
        } elseif ($scope_post_id <= 0) {
            $sections = boblift_mls_default_sections();
        } else {
            $sections = [];
        }
        $target_options = boblift_mls_get_raw_options($target_language);
        foreach ($sections as $section) {
            $section = (string) $section;
            if ($section === 'seo_meta' && ($scan_mode === 'params' || (!$settings_only && $scope_section === ''))) {
                continue;
            }
            $group = $section === 'seo_meta' ? 'seo' : 'sections';
            $exists = $section === 'seo_meta' ? false : array_key_exists($section, $target_options);
            boblift_mls_scan_group_add($groups, $group, $exists);
            $items[] = [
                'item_type' => 'section',
                'object_type' => $section,
                'source_id' => 0,
                'target_id' => 0,
                'action' => 'sync_section',
                'label' => boblift_mls_section_label($section),
                'group' => $group,
                'exists' => $exists,
            ];
        }
    }

    foreach (['category', 'product_category'] as $taxonomy) {
        if ($settings_only || $scope_post_id > 0 || ($scope_section !== '' && $scope_section !== '__all__')) {
            continue;
        }
        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false, 'lang' => $source_language, 'orderby' => 'term_id', 'order' => 'ASC']);
        if (is_wp_error($terms) || !function_exists('pll_get_term')) {
            continue;
        }
        foreach ($terms as $term) {
            if (!$term instanceof WP_Term) {
                continue;
            }
            $target_id = (int) pll_get_term($term->term_id, $target_language);
            $exists = $target_id > 0;
            boblift_mls_scan_group_add($groups, 'terms', $exists);
            $items[] = [
                'item_type' => 'term',
                'object_type' => $taxonomy,
                'source_id' => $term->term_id,
                'target_id' => $target_id,
                'action' => 'translate_term',
                'label' => $term->name . '（' . $taxonomy . '）',
                'group' => 'terms',
                'exists' => $exists,
            ];
        }
    }

    $post_types = ['page', 'post', 'case', 'product'];
    foreach ($post_types as $post_type) {
        if ($settings_only) {
            continue;
        }
        if ($scope_post_id > 0) {
            $source_post = boblift_mls_source_post_for_target($scope_post_id);
            if (!$source_post instanceof WP_Post || $source_post->post_type !== $post_type) {
                continue;
            }
            $posts = [$source_post];
        } elseif ($scope_section !== '' && $scope_section !== '__all__') {
            continue;
        } else {
            $posts = get_posts(['post_type' => $post_type, 'post_status' => 'any', 'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC', 'lang' => $source_language]);
        }
        foreach ($posts as $post) {
            if (!$post instanceof WP_Post || !function_exists('pll_get_post')) {
                continue;
            }
            $target_id = $scope_post_id > 0 ? $scope_post_id : (int) pll_get_post($post->ID, $target_language);
            $exists = $target_id > 0;
            $group = boblift_mls_post_type_group($post_type);
            boblift_mls_scan_group_add($groups, $group, $exists);
            $items[] = [
                'item_type' => 'post',
                'object_type' => $post_type,
                'source_id' => $post->ID,
                'target_id' => $target_id,
                'action' => 'translate_post',
                'label' => $post->post_title . '（' . $post_type . '）',
                'group' => $group,
                'exists' => $exists,
            ];
        }
    }

    if (!$settings_only && $scan_mode === 'translate' && $scope_post_id <= 0 && ($scope_section === '' || $scope_section === '__all__')) {
        boblift_mls_append_seo_scan_items($items, $groups, $target_language, $source_language);
    }

    return ['items' => $items, 'groups' => $groups, 'total' => count($items)];
}

function boblift_mls_empty_scan_groups(): array
{
    return [
        'sections' => ['label' => 'BOB-LIFT 配置区块', 'total' => 0, 'missing' => 0, 'existing' => 0],
        'terms' => ['label' => '分类与产品分类', 'total' => 0, 'missing' => 0, 'existing' => 0],
        'pages' => ['label' => '页面', 'total' => 0, 'missing' => 0, 'existing' => 0],
        'posts' => ['label' => '文章', 'total' => 0, 'missing' => 0, 'existing' => 0],
        'cases' => ['label' => '案例', 'total' => 0, 'missing' => 0, 'existing' => 0],
        'products' => ['label' => '产品', 'total' => 0, 'missing' => 0, 'existing' => 0],
        'seo' => ['label' => 'SEO 标题和描述', 'total' => 0, 'missing' => 0, 'existing' => 0],
    ];
}

function boblift_mls_post_type_group(string $post_type): string
{
    return ['page' => 'pages', 'post' => 'posts', 'case' => 'cases', 'product' => 'products'][$post_type] ?? 'posts';
}

function boblift_mls_scan_group_add(array &$groups, string $group, bool $exists): void
{
    if (!isset($groups[$group])) {
        $groups[$group] = ['label' => $group, 'total' => 0, 'missing' => 0, 'existing' => 0];
    }
    $groups[$group]['total']++;
    $groups[$group][$exists ? 'existing' : 'missing']++;
}

function boblift_mls_append_seo_scan_items(array &$items, array &$groups, string $target_language, string $source_language): void
{
    foreach (['page', 'post', 'product', 'case'] as $post_type) {
        $posts = get_posts(['post_type' => $post_type, 'post_status' => 'any', 'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC', 'lang' => $source_language]);
        foreach ($posts as $source) {
            if (!$source instanceof WP_Post || !function_exists('pll_get_post')) {
                continue;
            }
            $target_id = (int) pll_get_post($source->ID, $target_language);
            if ($target_id <= 0) {
                continue;
            }
            $target_post = get_post($target_id);
            if (!$target_post instanceof WP_Post) {
                continue;
            }
            $current = boblift_mls_post_target_seo_values($target_post, false);
            $exists = trim((string) ($current['seo_title'] ?? '')) !== '' && trim((string) ($current['seo_description'] ?? '')) !== '';
            boblift_mls_scan_group_add($groups, 'seo', $exists);
            $items[] = [
                'item_type' => 'seo_post',
                'object_type' => $post_type,
                'source_id' => $source->ID,
                'target_id' => $target_id,
                'action' => 'sync_post_seo',
                'label' => $source->post_title . ' SEO',
                'group' => 'seo',
                'exists' => $exists,
            ];
        }
    }

    foreach (['category', 'product_category'] as $taxonomy) {
        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false, 'lang' => $source_language]);
        if (is_wp_error($terms) || !function_exists('pll_get_term')) {
            continue;
        }
        foreach ($terms as $source_term) {
            if (!$source_term instanceof WP_Term) {
                continue;
            }
            $target_id = (int) pll_get_term($source_term->term_id, $target_language);
            $target = $target_id > 0 ? get_term($target_id, $taxonomy) : null;
            if (!$target instanceof WP_Term || is_wp_error($target)) {
                continue;
            }
            $current = boblift_mls_term_target_seo_values($target);
            $exists = trim((string) ($current['seo_title'] ?? '')) !== '' && trim((string) ($current['seo_description'] ?? '')) !== '';
            boblift_mls_scan_group_add($groups, 'seo', $exists);
            $items[] = [
                'item_type' => 'seo_term',
                'object_type' => $taxonomy,
                'source_id' => $source_term->term_id,
                'target_id' => $target_id,
                'action' => 'sync_term_seo',
                'label' => $source_term->name . ' SEO',
                'group' => 'seo',
                'exists' => $exists,
            ];
        }
    }
}

function boblift_mls_job_item_should_enqueue(array $item, array $policy, string $mode): bool
{
    $group = (string) ($item['group'] ?? '');
    $exists = !empty($item['exists']);
    if ($mode === 'params') {
        return (string) ($item['action'] ?? '') === 'sync_section';
    }
    if (!$exists) {
        return true;
    }
    return in_array($group, (array) ($policy['overwrite_groups'] ?? []), true);
}

function boblift_mls_clone_scan_items(string $target_language): array|WP_Error
{
    $valid = boblift_mls_validate_job_language($target_language);
    if (is_wp_error($valid)) {
        return $valid;
    }
    $source_language = boblift_mls_source_language();
    $items = [];

    $target_options = boblift_mls_get_raw_options($target_language);
    foreach (boblift_mls_default_sections() as $section) {
        if ($section === 'seo_meta') {
            continue;
        }
        if (array_key_exists((string) $section, $target_options)) {
            continue;
        }
        $items[] = [
            'item_type' => 'section',
            'object_type' => (string) $section,
            'source_id' => 0,
            'target_id' => 0,
            'action' => 'clone_section',
            'label' => boblift_mls_section_label((string) $section),
        ];
    }

    foreach (['category', 'product_category'] as $taxonomy) {
        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false, 'lang' => $source_language, 'orderby' => 'term_id', 'order' => 'ASC']);
        if (is_wp_error($terms) || !function_exists('pll_get_term')) {
            continue;
        }
        $terms = boblift_mls_sort_terms_parent_first($terms);
        foreach ($terms as $term) {
            if (!$term instanceof WP_Term || (int) pll_get_term($term->term_id, $target_language) > 0) {
                continue;
            }
            $items[] = [
                'item_type' => 'term',
                'object_type' => $taxonomy,
                'source_id' => $term->term_id,
                'target_id' => 0,
                'action' => 'clone_term',
                'label' => $term->name . '（' . $taxonomy . '）',
            ];
        }
    }

    foreach (['page', 'post', 'case', 'product'] as $post_type) {
        $posts = get_posts(['post_type' => $post_type, 'post_status' => 'any', 'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC', 'lang' => $source_language]);
        foreach ($posts as $post) {
            if (!$post instanceof WP_Post || !function_exists('pll_get_post') || (int) pll_get_post($post->ID, $target_language) > 0) {
                continue;
            }
            $items[] = [
                'item_type' => 'post',
                'object_type' => $post_type,
                'source_id' => $post->ID,
                'target_id' => 0,
                'action' => 'clone_post',
                'label' => $post->post_title . '（' . $post_type . '）',
            ];
        }
    }

    if ($items === [] && boblift_mls_language_status($target_language) === 'new') {
        $items[] = [
            'item_type' => 'language',
            'object_type' => $target_language,
            'source_id' => 0,
            'target_id' => 0,
            'action' => 'mark_language_staged',
            'label' => '确认语言已初始化',
        ];
    }

    return $items;
}

function boblift_mls_sort_terms_parent_first(array $terms): array
{
    $by_parent = [];
    foreach ($terms as $term) {
        if (!$term instanceof WP_Term) {
            continue;
        }
        $by_parent[(int) $term->parent][] = $term;
    }
    $sorted = [];
    $walk = static function (int $parent_id) use (&$walk, &$sorted, &$by_parent): void {
        foreach ($by_parent[$parent_id] ?? [] as $term) {
            $sorted[] = $term;
            $walk((int) $term->term_id);
        }
    };
    $walk(0);
    return $sorted;
}

function boblift_mls_translate_language_scan_items(string $target_language): array|WP_Error
{
    $valid = boblift_mls_validate_job_language($target_language);
    if (is_wp_error($valid)) {
        return $valid;
    }
    if (boblift_mls_language_status($target_language) === 'new') {
        return new WP_Error('boblift_mls_not_initialized', '请先初始化语言，再开始翻译。');
    }
    if (in_array(boblift_mls_language_status($target_language), ['ready', 'published'], true)) {
        return [];
    }
    $source_language = boblift_mls_source_language();
    $items = [];

    foreach (boblift_mls_default_sections() as $section) {
        if ($section === 'seo_meta') {
            continue;
        }
        $items[] = [
            'item_type' => 'section',
            'object_type' => (string) $section,
            'source_id' => 0,
            'target_id' => 0,
            'action' => 'translate_cloned_section',
            'label' => boblift_mls_section_label((string) $section),
        ];
    }

    foreach (['category', 'product_category'] as $taxonomy) {
        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false, 'lang' => $source_language, 'orderby' => 'term_id', 'order' => 'ASC']);
        if (is_wp_error($terms) || !function_exists('pll_get_term')) {
            continue;
        }
        foreach ($terms as $term) {
            if (!$term instanceof WP_Term) {
                continue;
            }
            $target_id = (int) pll_get_term($term->term_id, $target_language);
            if ($target_id <= 0 || (int) get_term_meta($target_id, '_boblift_mls_clone_source_id', true) !== (int) $term->term_id || get_term_meta($target_id, '_boblift_mls_translated_at', true) !== '') {
                continue;
            }
            $items[] = [
                'item_type' => 'term',
                'object_type' => $taxonomy,
                'source_id' => $term->term_id,
                'target_id' => $target_id,
                'action' => 'translate_cloned_term',
                'label' => $term->name . '（' . $taxonomy . '）',
            ];
        }
    }

    foreach (['page', 'post', 'case', 'product'] as $post_type) {
        $posts = get_posts(['post_type' => $post_type, 'post_status' => 'any', 'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC', 'lang' => $source_language]);
        foreach ($posts as $post) {
            if (!$post instanceof WP_Post || !function_exists('pll_get_post')) {
                continue;
            }
            $target_id = (int) pll_get_post($post->ID, $target_language);
            if ($target_id <= 0 || (int) get_post_meta($target_id, '_boblift_mls_clone_source_id', true) !== (int) $post->ID || get_post_meta($target_id, '_boblift_mls_translated_at', true) !== '') {
                continue;
            }
            $items[] = [
                'item_type' => 'post',
                'object_type' => $post_type,
                'source_id' => $post->ID,
                'target_id' => $target_id,
                'action' => 'translate_cloned_post',
                'label' => $post->post_title . '（' . $post_type . '）',
            ];
        }
    }

    return $items;
}

function boblift_mls_publish_scan_items(string $target_language): array|WP_Error
{
    $valid = boblift_mls_validate_job_language($target_language);
    if (is_wp_error($valid)) {
        return $valid;
    }
    if (!in_array(boblift_mls_language_status($target_language), ['ready', 'published'], true)) {
        return new WP_Error('boblift_mls_not_ready', '翻译完成后才能发布语言。');
    }
    return [[
        'item_type' => 'language',
        'object_type' => $target_language,
        'source_id' => 0,
        'target_id' => 0,
        'action' => 'publish_language',
        'label' => '发布语言 ' . $target_language,
    ]];
}

function boblift_mls_create_job_from_items(string $target_language, string $job_type, array $items, array $policy = []): int|WP_Error
{
    global $wpdb;
    boblift_mls_maybe_install_jobs_schema();
    if ($items === []) {
        return new WP_Error('boblift_mls_no_job_items', '没有需要处理的项目。');
    }
    $now = current_time('mysql');
    $policy['job_type'] = $job_type;
    $wpdb->insert(boblift_mls_jobs_table(), [
        'job_type' => $job_type,
        'target_language' => $target_language,
        'source_language' => boblift_mls_source_language(),
        'status' => 'pending',
        'overwrite_policy' => boblift_mls_encode_json($policy),
        'logs' => boblift_mls_encode_json([['time' => $now, 'message' => boblift_mls_job_type_label($job_type) . '任务已创建。']]),
        'created_by' => get_current_user_id(),
        'created_at' => $now,
        'updated_at' => $now,
    ], ['%s','%s','%s','%s','%s','%s','%d','%s','%s']);
    $job_id = (int) $wpdb->insert_id;
    if ($job_id <= 0) {
        return new WP_Error('boblift_mls_job_create_failed', '创建任务失败。');
    }
    foreach ($items as $item) {
        $wpdb->insert(boblift_mls_job_items_table(), [
            'job_id' => $job_id,
            'item_type' => (string) $item['item_type'],
            'object_type' => (string) $item['object_type'],
            'source_id' => (int) $item['source_id'],
            'target_id' => (int) $item['target_id'],
            'action' => (string) $item['action'],
            'label' => (string) $item['label'],
            'status' => 'pending',
            'field_policy' => boblift_mls_encode_json((array) ($item['field_policy'] ?? ['job_type' => $job_type])),
            'created_at' => $now,
            'updated_at' => $now,
        ], ['%d','%s','%s','%d','%d','%s','%s','%s','%s','%s','%s']);
    }
    boblift_mls_refresh_job_stats($job_id);
    return $job_id;
}

function boblift_mls_create_clone_language_job(string $target_language): int|WP_Error
{
    $items = boblift_mls_clone_scan_items($target_language);
    if (is_wp_error($items)) {
        return $items;
    }
    return boblift_mls_create_job_from_items($target_language, 'clone_language', $items);
}

function boblift_mls_create_translate_language_job(string $target_language): int|WP_Error
{
    if (!boblift_mls_translation_available()) {
        return new WP_Error('boblift_mls_no_translation_engine', 'Polytranslate AI 翻译函数不可用。');
    }
    $items = boblift_mls_translate_language_scan_items($target_language);
    if (is_wp_error($items)) {
        return $items;
    }
    boblift_mls_set_language_status($target_language, 'translating', ['translate_started_at' => time()]);
    return boblift_mls_create_job_from_items($target_language, 'translate_language', $items, ['overwrite_groups' => ['sections', 'terms', 'pages', 'posts', 'cases', 'products', 'seo']]);
}

function boblift_mls_create_publish_language_job(string $target_language): int|WP_Error
{
    $items = boblift_mls_publish_scan_items($target_language);
    if (is_wp_error($items)) {
        return $items;
    }
    return boblift_mls_create_job_from_items($target_language, 'publish_language', $items);
}

function boblift_mls_create_translation_job(string $target_language, array $overwrite_groups = [], array $args = []): int|WP_Error
{
    global $wpdb;
    $mode = isset($args['mode']) && $args['mode'] === 'params' ? 'params' : 'translate';
    boblift_mls_maybe_install_jobs_schema();
    $scan = boblift_mls_scan_target_language($target_language, $args);
    if (is_wp_error($scan)) {
        return $scan;
    }
    if (($scan['items'] ?? []) === []) {
        return new WP_Error('boblift_mls_no_job_items', '没有可处理的同步或翻译项目。');
    }
    if ($mode === 'translate' && !boblift_mls_translation_available()) {
        return new WP_Error('boblift_mls_no_translation_engine', 'Polytranslate AI 翻译函数不可用。');
    }

    $now = current_time('mysql');
    $policy = [
        'overwrite_groups' => array_values(array_intersect(array_map('sanitize_key', $overwrite_groups), array_keys(boblift_mls_empty_scan_groups()))),
        'scope' => $args,
        'mode' => $mode,
        'job_type' => 'repair_missing',
    ];
    $wpdb->insert(boblift_mls_jobs_table(), [
        'job_type' => 'repair_missing',
        'target_language' => $target_language,
        'source_language' => boblift_mls_source_language(),
        'status' => 'pending',
        'overwrite_policy' => boblift_mls_encode_json($policy),
        'logs' => boblift_mls_encode_json([['time' => $now, 'message' => '任务已创建，等待执行。']]),
        'created_by' => get_current_user_id(),
        'created_at' => $now,
        'updated_at' => $now,
    ], ['%s','%s','%s','%s','%s','%s','%d','%s','%s']);
    $job_id = (int) $wpdb->insert_id;
    if ($job_id <= 0) {
        return new WP_Error('boblift_mls_job_create_failed', '创建任务失败。');
    }

    $inserted_items = 0;
    foreach ($scan['items'] as $item) {
        if (!boblift_mls_job_item_should_enqueue($item, $policy, $mode)) {
            continue;
        }
        $group = (string) ($item['group'] ?? '');
        $exists = !empty($item['exists']);
        $overwrite = $exists && in_array($group, $policy['overwrite_groups'], true);
        $field_policy = ['group' => $group, 'exists' => $exists, 'overwrite' => $overwrite, 'mode' => $mode];
        $wpdb->insert(boblift_mls_job_items_table(), [
            'job_id' => $job_id,
            'item_type' => (string) $item['item_type'],
            'object_type' => (string) $item['object_type'],
            'source_id' => (int) $item['source_id'],
            'target_id' => (int) $item['target_id'],
            'action' => (string) $item['action'],
            'label' => (string) $item['label'],
            'status' => 'pending',
            'field_policy' => boblift_mls_encode_json($field_policy),
            'created_at' => $now,
            'updated_at' => $now,
        ], ['%d','%s','%s','%d','%d','%s','%s','%s','%s','%s','%s']);
        if ((int) $wpdb->insert_id > 0) {
            $inserted_items++;
        }
    }
    if ($inserted_items === 0) {
        $wpdb->delete(boblift_mls_jobs_table(), ['id' => $job_id], ['%d']);
        return new WP_Error('boblift_mls_no_missing_items', '没有需要补充的缺失项目；已有内容默认不会重复翻译。');
    }
    boblift_mls_refresh_job_stats($job_id);
    return $job_id;
}

function boblift_mls_get_recent_jobs(int $limit = 20): array
{
    global $wpdb;
    $limit = max(1, min(100, $limit));
    $rows = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . boblift_mls_jobs_table() . ' ORDER BY id DESC LIMIT %d', $limit), ARRAY_A);
    if (!is_array($rows)) {
        return [];
    }
    foreach ($rows as &$row) {
        $row['overwrite_policy'] = boblift_mls_decode_json($row['overwrite_policy'] ?? '');
        $row['logs'] = boblift_mls_decode_json($row['logs'] ?? '');
    }
    unset($row);
    return $rows;
}

function boblift_mls_job_status_label(string $status): string
{
    return [
        'pending' => '等待执行',
        'running' => '执行中',
        'paused' => '已暂停',
        'completed' => '已完成',
        'completed_with_errors' => '完成但有失败',
        'failed' => '失败',
    ][$status] ?? $status;
}

function boblift_mls_get_job(int $job_id): ?array
{
    global $wpdb;
    $job = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . boblift_mls_jobs_table() . ' WHERE id = %d', $job_id), ARRAY_A);
    if (!is_array($job)) {
        return null;
    }
    $job['overwrite_policy'] = boblift_mls_decode_json($job['overwrite_policy'] ?? '');
    $job['logs'] = boblift_mls_decode_json($job['logs'] ?? '');
    return $job;
}

function boblift_mls_get_job_items(int $job_id, array $statuses = [], int $limit = 50): array
{
    global $wpdb;
    $sql = 'SELECT * FROM ' . boblift_mls_job_items_table() . ' WHERE job_id = %d';
    $params = [$job_id];
    if ($statuses !== []) {
        $placeholders = implode(',', array_fill(0, count($statuses), '%s'));
        $sql .= ' AND status IN (' . $placeholders . ')';
        $params = array_merge($params, $statuses);
    }
    $sql .= ' ORDER BY id ASC LIMIT %d';
    $params[] = $limit;
    $rows = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
    return is_array($rows) ? $rows : [];
}

function boblift_mls_job_public_state(int $job_id): array
{
    $job = boblift_mls_get_job($job_id);
    if (!$job) {
        return ['missing' => true];
    }
    global $wpdb;
    $avg_seconds = (float) $wpdb->get_var($wpdb->prepare(
        'SELECT AVG(TIMESTAMPDIFF(SECOND, started_at, finished_at)) FROM ' . boblift_mls_job_items_table() . ' WHERE job_id = %d AND status IN ("done","skipped","failed") AND started_at IS NOT NULL AND finished_at IS NOT NULL',
        $job_id
    ));
    $remaining = max(0, (int) $job['total_items'] - (int) $job['completed_items'] - (int) $job['skipped_items'] - (int) $job['failed_items']);
    $failed = boblift_mls_get_job_items($job_id, ['failed'], 20);
    return [
        'id' => $job_id,
        'job_type' => (string) ($job['job_type'] ?? 'repair_missing'),
        'job_type_label' => boblift_mls_job_type_label((string) ($job['job_type'] ?? 'repair_missing')),
        'status' => $job['status'],
        'target_language' => $job['target_language'],
        'source_language' => $job['source_language'],
        'total_items' => (int) $job['total_items'],
        'completed_items' => (int) $job['completed_items'],
        'skipped_items' => (int) $job['skipped_items'],
        'failed_items' => (int) $job['failed_items'],
        'current_label' => (string) ($job['current_label'] ?? ''),
        'logs' => array_slice($job['logs'], -30),
        'estimated_remaining_seconds' => $avg_seconds > 0 ? (int) round($avg_seconds * $remaining) : 0,
        'failed' => array_map(static function (array $item): array {
            return ['id' => (int) $item['id'], 'label' => (string) $item['label'], 'error' => (string) $item['error_message']];
        }, $failed),
    ];
}

function boblift_mls_refresh_job_stats(int $job_id): void
{
    global $wpdb;
    $table = boblift_mls_job_items_table();
    $counts = $wpdb->get_results($wpdb->prepare("SELECT status, COUNT(*) AS total FROM {$table} WHERE job_id = %d GROUP BY status", $job_id), ARRAY_A);
    $map = [];
    foreach ((array) $counts as $row) {
        $map[(string) $row['status']] = (int) $row['total'];
    }
    $total = array_sum($map);
    $done = (int) ($map['done'] ?? 0);
    $skipped = (int) ($map['skipped'] ?? 0);
    $failed = (int) ($map['failed'] ?? 0);
    $queued = (int) ($map['pending'] ?? 0);
    $running = (int) ($map['running'] ?? 0);
    $pending = $queued + $running;
    $status = $pending > 0 ? ($running > 0 ? 'running' : 'pending') : ($failed > 0 ? 'completed_with_errors' : 'completed');
    if ($total === 0) {
        $status = 'completed';
    }
    $job = boblift_mls_get_job($job_id);
    if ($job && in_array((string) $job['status'], ['pending', 'running'], true) && in_array($status, ['completed', 'completed_with_errors'], true)) {
        boblift_mls_add_job_log($job_id, $status === 'completed' ? '任务已完成。' : '任务完成，但存在失败项。');
    }
    $wpdb->update(boblift_mls_jobs_table(), [
        'status' => $status,
        'total_items' => $total,
        'completed_items' => $done,
        'skipped_items' => $skipped,
        'failed_items' => $failed,
        'finished_at' => in_array($status, ['pending', 'running'], true) ? null : current_time('mysql'),
        'updated_at' => current_time('mysql'),
        'locked_until' => null,
    ], ['id' => $job_id], ['%s','%d','%d','%d','%d','%s','%s','%s'], ['%d']);
    if ($job && in_array($status, ['completed', 'completed_with_errors'], true)) {
        boblift_mls_transition_language_after_job($job, $status);
    }
}

function boblift_mls_transition_language_after_job(array $job, string $status): void
{
    if ($status !== 'completed') {
        return;
    }
    $language = (string) ($job['target_language'] ?? '');
    $type = (string) ($job['job_type'] ?? 'repair_missing');
    if ($language === '') {
        return;
    }
    if ($type === 'clone_language') {
        boblift_mls_set_language_status($language, 'staged', ['clone_job_id' => (int) $job['id'], 'initialized_at' => time()]);
    } elseif ($type === 'translate_language') {
        boblift_mls_set_language_status($language, 'ready', ['translate_job_id' => (int) $job['id'], 'translated_at' => time()]);
    } elseif ($type === 'publish_language') {
        boblift_mls_set_language_status($language, 'published', ['publish_job_id' => (int) $job['id'], 'published_at' => time()]);
    }
}

function boblift_mls_add_job_log(int $job_id, string $message): void
{
    global $wpdb;
    $job = boblift_mls_get_job($job_id);
    if (!$job) {
        return;
    }
    $logs = is_array($job['logs']) ? $job['logs'] : [];
    $logs[] = ['time' => current_time('mysql'), 'message' => $message];
    $logs = array_slice($logs, -120);
    $wpdb->update(boblift_mls_jobs_table(), ['logs' => boblift_mls_encode_json($logs), 'updated_at' => current_time('mysql')], ['id' => $job_id], ['%s','%s'], ['%d']);
}

function boblift_mls_unschedule_job(int $job_id): void
{
    while ($timestamp = wp_next_scheduled('boblift_mls_run_job_cron', [$job_id])) {
        wp_unschedule_event($timestamp, 'boblift_mls_run_job_cron', [$job_id]);
    }
}

function boblift_mls_schedule_job(int $job_id): void
{
    boblift_mls_unschedule_job($job_id);
}
add_action('boblift_mls_run_job_cron', 'boblift_mls_run_job_cron');

function boblift_mls_run_job_cron(int $job_id): void
{
    boblift_mls_unschedule_job($job_id);
}

function boblift_mls_job_lock_seconds(): int
{
    return 600;
}

function boblift_mls_fast_skip_protected_items(int $job_id): void
{
    global $wpdb;
    $items_table = boblift_mls_job_items_table();
    $pending = boblift_mls_get_job_items($job_id, ['pending'], 200);
    $skip_ids = [];
    foreach ($pending as $item) {
        $policy = boblift_mls_decode_json($item['field_policy'] ?? '');
        if (!empty($policy['exists']) && empty($policy['overwrite']) && (($policy['mode'] ?? 'translate') === 'translate')) {
            $skip_ids[] = (int) $item['id'];
        }
    }
    if ($skip_ids === []) {
        return;
    }
    $placeholders = implode(',', array_fill(0, count($skip_ids), '%d'));
    $params = array_merge([current_time('mysql'), current_time('mysql')], $skip_ids);
    $wpdb->query($wpdb->prepare("UPDATE {$items_table} SET status = 'skipped', error_message = '', finished_at = %s, updated_at = %s WHERE id IN ({$placeholders})", $params));
    boblift_mls_add_job_log($job_id, '已快速跳过 ' . count($skip_ids) . ' 个受保护的已有项目；任务只继续处理缺失项。');
}

function boblift_mls_run_job_batch(int $job_id, int $limit = 1): array|WP_Error
{
    global $wpdb;
    $job = boblift_mls_get_job($job_id);
    if (!$job) {
        return new WP_Error('boblift_mls_job_missing', '任务不存在。');
    }
    if (!in_array((string) $job['status'], ['pending', 'running'], true)) {
        return boblift_mls_job_public_state($job_id);
    }
    $now = current_time('mysql');
    $lock_until = date('Y-m-d H:i:s', current_time('timestamp') + boblift_mls_job_lock_seconds());
    $jobs_table = boblift_mls_jobs_table();
    $locked = $wpdb->query($wpdb->prepare("UPDATE {$jobs_table} SET status = 'running', locked_until = %s, started_at = COALESCE(started_at, %s), updated_at = %s WHERE id = %d AND (locked_until IS NULL OR locked_until < %s)", $lock_until, $now, $now, $job_id, $now));
    if ($locked === 0) {
        return boblift_mls_job_public_state($job_id);
    }

    boblift_mls_fast_skip_protected_items($job_id);
    $items = boblift_mls_get_job_items($job_id, ['pending'], max(1, $limit));
    if ($items === []) {
        boblift_mls_refresh_job_stats($job_id);
        return boblift_mls_job_public_state($job_id);
    }

    foreach ($items as $item) {
        boblift_mls_process_job_item($job_id, $item);
    }
    boblift_mls_refresh_job_stats($job_id);
    $state = boblift_mls_job_public_state($job_id);
    return $state;
}

function boblift_mls_process_job_item(int $job_id, array $item): void
{
    global $wpdb;
    $items_table = boblift_mls_job_items_table();
    $label = (string) ($item['label'] ?? '未命名项目');
    $now = current_time('mysql');
    $wpdb->update($items_table, ['status' => 'running', 'attempts' => ((int) $item['attempts']) + 1, 'started_at' => $now, 'updated_at' => $now], ['id' => (int) $item['id']], ['%s','%d','%s','%s'], ['%d']);
    $wpdb->update(boblift_mls_jobs_table(), ['current_label' => $label, 'updated_at' => $now], ['id' => $job_id], ['%s','%s'], ['%d']);

    $result = boblift_mls_execute_job_item($item);
    $status = 'done';
    $error = '';
    if (is_wp_error($result)) {
        $status = 'failed';
        $error = $result->get_error_message();
        boblift_mls_add_job_log($job_id, '失败：' . $label . '：' . $error);
    } elseif (is_array($result) && ($result['status'] ?? '') === 'skipped') {
        $status = 'skipped';
        boblift_mls_add_job_log($job_id, '跳过：' . $label . '：' . (string) ($result['message'] ?? '无需处理'));
    } else {
        boblift_mls_add_job_log($job_id, '完成：' . $label);
    }

    $wpdb->update($items_table, [
        'status' => $status,
        'error_message' => $error,
        'finished_at' => current_time('mysql'),
        'updated_at' => current_time('mysql'),
    ], ['id' => (int) $item['id']], ['%s','%s','%s','%s'], ['%d']);
}

function boblift_mls_execute_job_item(array $item): true|array|WP_Error
{
    $policy = boblift_mls_decode_json($item['field_policy'] ?? '');
    $overwrite = !empty($policy['overwrite']);
    $target_language = (string) (boblift_mls_get_job((int) $item['job_id'])['target_language'] ?? '');
    if ($target_language === '') {
        return new WP_Error('boblift_mls_job_language_missing', '任务缺少目标语言。');
    }

    switch ((string) $item['action']) {
        case 'clone_section':
            return boblift_mls_clone_section_item((string) $item['object_type'], $target_language);
        case 'clone_post':
            return boblift_mls_clone_post_item($item, $target_language);
        case 'clone_term':
            return boblift_mls_clone_term_item($item, $target_language);
        case 'mark_language_staged':
            boblift_mls_set_language_status($target_language, 'staged', ['initialized_at' => time()]);
            return true;
        case 'translate_cloned_section':
            return boblift_mls_sync_section((string) $item['object_type'], $target_language, 'translate');
        case 'translate_cloned_post':
            $post_result = boblift_mls_translate_post_item($item, $target_language, true);
            if (!is_wp_error($post_result) && ($post_result === true || is_array($post_result))) {
                $target_id = (int) ($item['target_id'] ?? 0);
                if ($target_id > 0) {
                    update_post_meta($target_id, '_boblift_mls_translated_at', time());
                }
            }
            return $post_result;
        case 'translate_cloned_term':
            $term_result = boblift_mls_translate_term_item($item, $target_language, true);
            if (!is_wp_error($term_result) && ($term_result === true || is_array($term_result))) {
                $target_id = (int) ($item['target_id'] ?? 0);
                if ($target_id > 0) {
                    update_term_meta($target_id, '_boblift_mls_translated_at', time());
                }
            }
            return $term_result;
        case 'publish_language':
            return boblift_mls_publish_language_item($target_language);
        case 'sync_section':
            $mode = isset($policy['mode']) && $policy['mode'] === 'params' ? 'params' : 'translate';
            if ($mode === 'translate' && !empty($policy['exists']) && !$overwrite) {
                return ['status' => 'skipped', 'message' => '目标语言已有该配置区块，默认保护已有内容。'];
            }
            return boblift_mls_sync_section((string) $item['object_type'], $target_language, $mode);
        case 'translate_post':
            return boblift_mls_translate_post_item($item, $target_language, $overwrite);
        case 'translate_term':
            return boblift_mls_translate_term_item($item, $target_language, $overwrite);
        case 'sync_post_seo':
            return boblift_mls_sync_post_seo_item($item, $target_language, $overwrite);
        case 'sync_term_seo':
            return boblift_mls_sync_term_seo_item($item, $target_language, $overwrite);
    }
    return new WP_Error('boblift_mls_unknown_job_action', '不支持的任务动作。');
}

function boblift_mls_clone_section_item(string $section, string $target_language): true|WP_Error
{
    if (!in_array($section, boblift_mls_default_sections(), true) || $section === 'seo_meta') {
        return true;
    }
    $source_section = boblift_mls_section_options($section, boblift_mls_source_language());
    $existing = boblift_mls_get_raw_options($target_language);
    if (!array_key_exists($section, $existing)) {
        $existing[$section] = $source_section;
        unset($existing['form_security']);
        boblift_mls_update_language_options($existing, $target_language);
    }
    $state = boblift_mls_state();
    $state['sync'][$target_language][$section] = ['source_hash' => boblift_mls_source_section_hash($section), 'source_snapshot' => boblift_mls_source_section_snapshot($section), 'mode' => 'clone', 'time' => time()];
    boblift_mls_update_state($state);
    return true;
}

function boblift_mls_clone_post_item(array $item, string $target_language): true|array|WP_Error
{
    $source = get_post((int) $item['source_id']);
    if (!$source instanceof WP_Post) {
        return new WP_Error('boblift_mls_source_post_missing', '源内容不存在。');
    }
    if (function_exists('pll_get_post') && (int) pll_get_post($source->ID, $target_language) > 0) {
        return ['status' => 'skipped', 'message' => '目标语言内容已存在，默认保护。'];
    }
    $target_id = wp_insert_post([
        'post_title' => $source->post_title,
        'post_content' => wp_slash($source->post_content),
        'post_excerpt' => $source->post_excerpt,
        'post_type' => $source->post_type,
        'post_status' => 'draft',
        'post_author' => (int) $source->post_author,
        'post_name' => $source->post_name,
        'menu_order' => (int) $source->menu_order,
        'post_parent' => 0,
    ], true);
    if (is_wp_error($target_id)) {
        return $target_id;
    }
    boblift_mls_copy_post_base_meta($source->ID, (int) $target_id);
    update_post_meta((int) $target_id, '_boblift_mls_clone_source_id', $source->ID);
    update_post_meta((int) $target_id, '_boblift_mls_source_status', $source->post_status);
    update_post_meta((int) $target_id, '_boblift_mls_clone_hash', boblift_mls_stable_hash([
        'title' => $source->post_title,
        'content' => $source->post_content,
        'excerpt' => $source->post_excerpt,
    ]));
    boblift_mls_link_post_translation($source->ID, (int) $target_id, $target_language);
    boblift_mls_copy_post_media_and_terms($source, (int) $target_id, $target_language);
    $section = function_exists('boblift_mls_section_for_source_page_bundle') ? boblift_mls_section_for_source_page_bundle($source) : '';
    $section_hash = $section !== '' ? boblift_mls_last_synced_hash($target_language, $section) : '';
    if ($section !== '' && $section_hash !== '' && hash_equals(boblift_mls_source_section_hash($section), $section_hash) && function_exists('boblift_mls_record_page_bundle_sync')) {
        boblift_mls_record_page_bundle_sync($target_language, (int) $target_id, $section, 'clone');
    }
    return true;
}

function boblift_mls_clone_term_item(array $item, string $target_language): true|array|WP_Error
{
    $taxonomy = (string) $item['object_type'];
    $source = get_term((int) $item['source_id'], $taxonomy);
    if (!$source instanceof WP_Term || is_wp_error($source)) {
        return new WP_Error('boblift_mls_source_term_missing', '源分类不存在。');
    }
    if (function_exists('pll_get_term') && (int) pll_get_term($source->term_id, $target_language) > 0) {
        boblift_mls_fix_cloned_term_parent($source, $target_language);
        return ['status' => 'skipped', 'message' => '目标语言分类已存在，默认保护。'];
    }
    $parent = 0;
    if ((int) $source->parent > 0 && function_exists('pll_get_term')) {
        $parent = (int) pll_get_term((int) $source->parent, $target_language);
    }
    $created = wp_insert_term(
        boblift_mls_temp_term_name($source, $target_language),
        $taxonomy,
        [
            'description' => $source->description,
            'parent' => $parent,
            'slug' => sanitize_title($source->slug . '-' . $target_language . '-' . $source->term_id),
        ]
    );
    if (is_wp_error($created)) {
        return $created;
    }
    $target_id = (int) $created['term_id'];
    boblift_mls_copy_term_base_meta($source->term_id, $target_id);
    update_term_meta($target_id, '_boblift_mls_clone_source_id', $source->term_id);
    update_term_meta($target_id, '_boblift_mls_clone_hash', boblift_mls_stable_hash(['name' => $source->name, 'description' => $source->description]));
    boblift_mls_link_term_translation($source->term_id, $target_id, $target_language);
    boblift_mls_force_term_display_name($target_id, $taxonomy, $source->name);
    boblift_mls_sync_translated_child_term_parents($source, $target_id, $target_language);
    return true;
}

function boblift_mls_temp_term_name(WP_Term $source, string $target_language): string
{
    return $source->name . ' [' . $target_language . ' init ' . $source->term_id . ']';
}

function boblift_mls_force_term_display_name(int $target_id, string $taxonomy, string $name): void
{
    global $wpdb;
    $wpdb->update($wpdb->terms, ['name' => $name], ['term_id' => $target_id], ['%s'], ['%d']);
    clean_term_cache($target_id, $taxonomy);
}

function boblift_mls_fix_cloned_term_parent(WP_Term $source, string $target_language): void
{
    if (!function_exists('pll_get_term')) {
        return;
    }
    $target_id = (int) pll_get_term($source->term_id, $target_language);
    if ($target_id <= 0) {
        return;
    }
    $parent = (int) $source->parent > 0 ? (int) pll_get_term((int) $source->parent, $target_language) : 0;
    wp_update_term($target_id, $source->taxonomy, ['parent' => $parent]);
}

function boblift_mls_sync_translated_child_term_parents(WP_Term $source_parent, int $target_parent_id, string $target_language): void
{
    if (!function_exists('pll_get_term')) {
        return;
    }
    $children = get_terms([
        'taxonomy' => $source_parent->taxonomy,
        'hide_empty' => false,
        'parent' => $source_parent->term_id,
        'lang' => boblift_mls_source_language(),
    ]);
    if (is_wp_error($children)) {
        return;
    }
    foreach ($children as $child) {
        if (!$child instanceof WP_Term) {
            continue;
        }
        $target_child_id = (int) pll_get_term($child->term_id, $target_language);
        if ($target_child_id > 0) {
            wp_update_term($target_child_id, $source_parent->taxonomy, ['parent' => $target_parent_id]);
        }
    }
}

function boblift_mls_publish_language_item(string $target_language): true|WP_Error
{
    $source_language = boblift_mls_source_language();
    foreach (['page', 'post', 'case', 'product'] as $post_type) {
        $posts = get_posts(['post_type' => $post_type, 'post_status' => 'any', 'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC', 'lang' => $source_language]);
        foreach ($posts as $source) {
            if (!$source instanceof WP_Post || !function_exists('pll_get_post')) {
                continue;
            }
            $target_id = (int) pll_get_post($source->ID, $target_language);
            if ($target_id <= 0) {
                continue;
            }
            $status = in_array($source->post_status, ['publish', 'private'], true) ? $source->post_status : 'draft';
            wp_update_post(['ID' => $target_id, 'post_status' => $status]);
        }
    }
    return true;
}

function boblift_mls_translate_post_text(string $text, string $target_language, string $source_language): string|WP_Error
{
    if (trim($text) === '') {
        return '';
    }
    if (function_exists('ptai_translate_content')) {
        $translated = ptai_translate_content($text, $target_language, $source_language);
        $translated = is_string($translated) ? $translated : $text;
        return boblift_mls_apply_static_title_fallback($text, $translated, $target_language, $source_language);
    }
    $result = ptai_call_translation_ai([$text], $target_language, $source_language);
    if (is_array($result) && isset($result['success']) && $result['success'] === false) {
        return new WP_Error('boblift_mls_translate_failed', (string) ($result['error'] ?? '翻译失败。'));
    }
    $translated = is_array($result) && isset($result[0]) ? (string) $result[0] : $text;
    return boblift_mls_apply_static_title_fallback($text, $translated, $target_language, $source_language);
}

function boblift_mls_translate_post_item(array $item, string $target_language, bool $overwrite): true|array|WP_Error
{
    $source = get_post((int) $item['source_id']);
    if (!$source instanceof WP_Post) {
        return new WP_Error('boblift_mls_source_post_missing', '源内容不存在。');
    }
    $target_id = (int) ($item['target_id'] ?? 0);
    if ($target_id <= 0 && function_exists('pll_get_post')) {
        $target_id = (int) pll_get_post($source->ID, $target_language);
    }
    if ($target_id > 0 && !$overwrite) {
        return ['status' => 'skipped', 'message' => '目标语言内容已存在，默认不覆盖。'];
    }

    $source_language = boblift_mls_source_language();
    $title = boblift_mls_translate_post_text($source->post_title, $target_language, $source_language);
    if (is_wp_error($title)) {
        return $title;
    }
    $content = boblift_mls_translate_post_text($source->post_content, $target_language, $source_language);
    if (is_wp_error($content)) {
        return $content;
    }
    $excerpt = boblift_mls_translate_post_text($source->post_excerpt, $target_language, $source_language);
    if (is_wp_error($excerpt)) {
        return $excerpt;
    }

    $post_data = [
        'post_title' => $title,
        'post_content' => wp_slash($content),
        'post_excerpt' => $excerpt,
        'post_type' => $source->post_type,
        'post_status' => boblift_mls_language_status($target_language) === 'published' ? (function_exists('ptai_get_option') ? ptai_get_option('polytranslate_ai_options', 'post_status', 'publish') : $source->post_status) : 'draft',
        'post_author' => (int) $source->post_author,
    ];

    if ($target_id > 0) {
        $post_data['ID'] = $target_id;
        $updated = wp_update_post($post_data, true);
        if (is_wp_error($updated)) {
            return $updated;
        }
    } else {
        $target_id = wp_insert_post($post_data, true);
        if (is_wp_error($target_id)) {
            return $target_id;
        }
        boblift_mls_copy_post_base_meta($source->ID, (int) $target_id);
    }

    boblift_mls_link_post_translation($source->ID, (int) $target_id, $target_language);
    boblift_mls_copy_post_media_and_terms($source, (int) $target_id, $target_language);
    if (function_exists('ptai_boblift_translate_post_meta') && ($item['target_id'] <= 0 || $overwrite)) {
        $meta_result = ptai_boblift_translate_post_meta($source->ID, (int) $target_id, $target_language, $source_language, $source->post_type);
        if (is_wp_error($meta_result)) {
            return $meta_result;
        }
    }
    return true;
}

function boblift_mls_copy_post_base_meta(int $source_id, int $target_id): void
{
    $skip = ['_edit_lock', '_edit_last', '_thumbnail_id', '_wp_old_slug', '_icl_lang_duplicate_of', '_polylang_translation', '_pll_language', '_pll_trid'];
    foreach (get_post_meta($source_id) as $key => $values) {
        if (in_array($key, $skip, true)) {
            continue;
        }
        delete_post_meta($target_id, $key);
        foreach ((array) $values as $value) {
            add_post_meta($target_id, $key, maybe_unserialize($value));
        }
    }
}

function boblift_mls_link_post_translation(int $source_id, int $target_id, string $target_language): void
{
    if (!function_exists('pll_set_post_language')) {
        return;
    }
    pll_set_post_language($target_id, $target_language);
    if (function_exists('pll_get_post_translations') && function_exists('pll_save_post_translations')) {
        $translations = pll_get_post_translations($source_id);
        $translations[$target_language] = $target_id;
        pll_save_post_translations($translations);
    }
}

function boblift_mls_copy_post_media_and_terms(WP_Post $source, int $target_id, string $target_language): void
{
    $thumbnail_id = get_post_thumbnail_id($source->ID);
    if ($thumbnail_id) {
        set_post_thumbnail($target_id, $thumbnail_id);
    }
    foreach (get_object_taxonomies($source->post_type) as $taxonomy) {
        $terms = wp_get_object_terms($source->ID, $taxonomy, ['fields' => 'ids']);
        if (is_wp_error($terms) || $terms === []) {
            continue;
        }
        $target_terms = [];
        foreach ($terms as $term_id) {
            $translated_id = function_exists('pll_get_term') ? (int) pll_get_term((int) $term_id, $target_language) : 0;
            if ($translated_id > 0) {
                $target_terms[] = $translated_id;
            }
        }
        if ($target_terms !== []) {
            wp_set_object_terms($target_id, $target_terms, $taxonomy, false);
        }
    }
}

function boblift_mls_translate_term_item(array $item, string $target_language, bool $overwrite): true|array|WP_Error
{
    $taxonomy = (string) $item['object_type'];
    $source = get_term((int) $item['source_id'], $taxonomy);
    if (!$source instanceof WP_Term || is_wp_error($source)) {
        return new WP_Error('boblift_mls_source_term_missing', '源分类不存在。');
    }
    $target_id = (int) ($item['target_id'] ?? 0);
    if ($target_id <= 0 && function_exists('pll_get_term')) {
        $target_id = (int) pll_get_term($source->term_id, $target_language);
    }
    if ($target_id > 0 && !$overwrite) {
        return ['status' => 'skipped', 'message' => '目标语言分类已存在，默认不覆盖。'];
    }

    $source_language = boblift_mls_source_language();
    $name = boblift_mls_translate_post_text($source->name, $target_language, $source_language);
    if (is_wp_error($name)) {
        return $name;
    }
    $description = boblift_mls_translate_post_text($source->description, $target_language, $source_language);
    if (is_wp_error($description)) {
        return $description;
    }
    $parent = 0;
    if ((int) $source->parent > 0 && function_exists('pll_get_term')) {
        $parent = (int) pll_get_term((int) $source->parent, $target_language);
    }

    if ($target_id > 0) {
        $updated = wp_update_term($target_id, $taxonomy, ['name' => $name, 'description' => $description, 'parent' => $parent]);
        if (is_wp_error($updated)) {
            return $updated;
        }
    } else {
        $created = wp_insert_term($name, $taxonomy, ['description' => $description, 'parent' => $parent]);
        if (is_wp_error($created)) {
            return $created;
        }
        $target_id = (int) $created['term_id'];
        boblift_mls_copy_term_base_meta($source->term_id, $target_id);
    }

    boblift_mls_link_term_translation($source->term_id, $target_id, $target_language);
    if (function_exists('ptai_boblift_translate_term_meta') && ($item['target_id'] <= 0 || $overwrite)) {
        $meta_result = ptai_boblift_translate_term_meta($source->term_id, $target_id, $target_language, $source_language, $taxonomy);
        if (is_wp_error($meta_result)) {
            return $meta_result;
        }
    }
    return true;
}

function boblift_mls_copy_term_base_meta(int $source_id, int $target_id): void
{
    foreach (get_term_meta($source_id) as $key => $values) {
        delete_term_meta($target_id, $key);
        foreach ((array) $values as $value) {
            add_term_meta($target_id, $key, maybe_unserialize($value));
        }
    }
}

function boblift_mls_link_term_translation(int $source_id, int $target_id, string $target_language): void
{
    if (function_exists('pll_set_term_language')) {
        pll_set_term_language($target_id, $target_language);
    }
    if (function_exists('pll_get_term_translations') && function_exists('pll_save_term_translations')) {
        $translations = pll_get_term_translations($source_id);
        $translations[$target_language] = $target_id;
        pll_save_term_translations($translations);
    }
}

function boblift_mls_sync_post_seo_item(array $item, string $target_language, bool $overwrite): true|array|WP_Error
{
    $source = get_post((int) $item['source_id']);
    $target = get_post((int) $item['target_id']);
    if (!$source instanceof WP_Post || !$target instanceof WP_Post) {
        return ['status' => 'skipped', 'message' => '缺少目标语言内容，先创建译文后再同步 SEO。'];
    }
    $current = boblift_mls_post_target_seo_values($target, false);
    if (!$overwrite && trim((string) $current['seo_title']) !== '' && trim((string) $current['seo_description']) !== '') {
        return ['status' => 'skipped', 'message' => '目标语言 SEO 已存在，默认不重复翻译。'];
    }
    return boblift_mls_sync_post_meta_seo($source, $target, $target_language, 'translate', false, $overwrite);
}

function boblift_mls_sync_term_seo_item(array $item, string $target_language, bool $overwrite): true|array|WP_Error
{
    $source = get_term((int) $item['source_id'], (string) $item['object_type']);
    $target = get_term((int) $item['target_id'], (string) $item['object_type']);
    if (!$source instanceof WP_Term || !$target instanceof WP_Term || is_wp_error($source) || is_wp_error($target)) {
        return ['status' => 'skipped', 'message' => '缺少目标语言分类，先创建译文后再同步 SEO。'];
    }
    $current = boblift_mls_term_target_seo_values($target);
    if (!$overwrite && trim((string) $current['seo_title']) !== '' && trim((string) $current['seo_description']) !== '') {
        return ['status' => 'skipped', 'message' => '目标语言 SEO 已存在，默认不重复翻译。'];
    }
    return boblift_mls_sync_term_meta_seo($source, $target, $target_language, 'translate', $overwrite);
}

function boblift_mls_pause_job(int $job_id): true|WP_Error
{
    global $wpdb;
    $job = boblift_mls_get_job($job_id);
    if (!$job) {
        return new WP_Error('boblift_mls_job_missing', '任务不存在。');
    }
    if (!in_array((string) $job['status'], ['pending', 'running'], true)) {
        return true;
    }
    boblift_mls_unschedule_job($job_id);
    $wpdb->update(boblift_mls_job_items_table(), [
        'status' => 'pending',
        'started_at' => null,
        'updated_at' => current_time('mysql'),
    ], ['job_id' => $job_id, 'status' => 'running'], ['%s','%s','%s'], ['%d','%s']);
    $wpdb->update(boblift_mls_jobs_table(), [
        'status' => 'paused',
        'locked_until' => null,
        'current_label' => '',
        'updated_at' => current_time('mysql'),
    ], ['id' => $job_id], ['%s','%s','%s','%s'], ['%d']);
    boblift_mls_add_job_log($job_id, '任务已暂停，不再占用前台访问资源。');
    return true;
}

function boblift_mls_resume_job(int $job_id): true|WP_Error
{
    global $wpdb;
    $job = boblift_mls_get_job($job_id);
    if (!$job) {
        return new WP_Error('boblift_mls_job_missing', '任务不存在。');
    }
    if ((string) $job['status'] !== 'paused') {
        return true;
    }
    $wpdb->update(boblift_mls_jobs_table(), [
        'status' => 'pending',
        'locked_until' => null,
        'updated_at' => current_time('mysql'),
    ], ['id' => $job_id], ['%s','%s','%s'], ['%d']);
    boblift_mls_add_job_log($job_id, '任务已恢复；保持进度页打开时才会继续执行。');
    return true;
}

function boblift_mls_pause_running_jobs(): int
{
    global $wpdb;
    $ids = $wpdb->get_col("SELECT id FROM " . boblift_mls_jobs_table() . " WHERE status IN ('pending','running')");
    $count = 0;
    foreach ((array) $ids as $id) {
        $result = boblift_mls_pause_job((int) $id);
        if (!is_wp_error($result)) {
            $count++;
        }
    }
    return $count;
}

function boblift_mls_retry_job(int $job_id): true|WP_Error
{
    global $wpdb;
    $job = boblift_mls_get_job($job_id);
    if (!$job) {
        return new WP_Error('boblift_mls_job_missing', '任务不存在。');
    }
    $wpdb->update(boblift_mls_job_items_table(), ['status' => 'pending', 'error_message' => '', 'updated_at' => current_time('mysql')], ['job_id' => $job_id, 'status' => 'failed'], ['%s','%s','%s'], ['%d','%s']);
    $wpdb->update(boblift_mls_jobs_table(), ['status' => 'pending', 'finished_at' => null, 'updated_at' => current_time('mysql')], ['id' => $job_id], ['%s','%s','%s'], ['%d']);
    boblift_mls_add_job_log($job_id, '已重新排队失败项。');
    return true;
}
