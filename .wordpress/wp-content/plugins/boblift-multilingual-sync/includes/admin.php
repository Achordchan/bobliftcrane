<?php
if (!defined('ABSPATH')) {
    exit;
}

function boblift_mls_admin_notice_redirect(string $location, string $status, string $message): void
{
    wp_safe_redirect(add_query_arg([
        'boblift_mls_status' => rawurlencode($status),
        'boblift_mls_message' => rawurlencode($message),
    ], $location));
    exit;
}

function boblift_mls_current_admin_url(string $fallback = ''): string
{
    $request_uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    if ($request_uri === '' || $host === '') {
        return $fallback !== '' ? $fallback : admin_url('admin.php?page=boblift-multilingual-sync');
    }
    return (is_ssl() ? 'https://' : 'http://') . $host . $request_uri;
}

function boblift_mls_handle_create_scoped_job(): void
{
    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    $section = isset($_POST['section']) ? sanitize_key(wp_unslash($_POST['section'])) : '';
    $sections = [];
    if ($section === '__settings_page' && isset($_POST['sections'])) {
        $raw_sections = is_array($_POST['sections']) ? $_POST['sections'] : explode(',', (string) wp_unslash($_POST['sections']));
        $allowed_sections = boblift_mls_default_sections();
        foreach ($raw_sections as $raw_section) {
            if (!is_scalar($raw_section)) {
                continue;
            }
            $candidate = sanitize_key((string) wp_unslash($raw_section));
            if ($candidate !== '' && in_array($candidate, $allowed_sections, true)) {
                $sections[] = $candidate;
            }
        }
        $sections = array_values(array_unique($sections));
        if ($sections === []) {
            wp_die('同步区块为空。');
        }
    }
    $language = isset($_POST['language']) ? sanitize_key(wp_unslash($_POST['language'])) : '';
    $mode = isset($_POST['mode']) && sanitize_key(wp_unslash($_POST['mode'])) === 'params' ? 'params' : 'translate';
    $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';
    $redirect_to = isset($_POST['redirect_to']) ? esc_url_raw(wp_unslash($_POST['redirect_to'])) : '';
    if (!wp_verify_nonce($nonce, 'boblift_mls_sync_' . $section . '_' . $language)) {
        wp_die('同步请求已过期。');
    }
    if ($post_id > 0 && !current_user_can('edit_post', $post_id)) {
        wp_die('权限不足。');
    }
    if ($post_id <= 0 && !current_user_can('manage_options')) {
        wp_die('权限不足。');
    }

    $default_location = $post_id > 0 ? (string) get_edit_post_link($post_id, 'raw') : admin_url('admin.php?page=boblift-multilingual-sync');
    $default_location = $redirect_to !== '' ? wp_validate_redirect($redirect_to, $default_location) : $default_location;
    if (!function_exists('boblift_mls_create_translation_job')) {
        boblift_mls_admin_notice_redirect($default_location, 'error', '任务系统不可用。');
    }

    $overwrite_groups = [];
    if ($mode === 'translate') {
        if ($section === '__all__' || $section === '__settings_page') {
            $overwrite_groups = ['sections'];
        } elseif ($post_id > 0 && function_exists('boblift_mls_is_known_page_bundle_section') && boblift_mls_is_known_page_bundle_section($section)) {
            $overwrite_groups = ['sections', 'pages', 'seo'];
        } else {
            $overwrite_groups = [$section === 'seo_meta' ? 'seo' : 'sections'];
        }
    }
    $args = ['section' => $section, 'post_id' => $post_id, 'mode' => $mode, 'return_to' => $default_location];
    if ($sections !== []) {
        $args['sections'] = $sections;
    }
    if ($post_id <= 0 || $mode === 'params') {
        $args['settings_only'] = true;
    }
    $job_result = boblift_mls_create_translation_job($language, $overwrite_groups, $args);
    if (is_wp_error($job_result)) {
        boblift_mls_admin_notice_redirect($default_location, 'error', $job_result->get_error_message());
    }
    wp_safe_redirect(boblift_mls_job_url((int) $job_result, $default_location));
    exit;
}
add_action('admin_post_boblift_mls_create_scoped_job', 'boblift_mls_handle_create_scoped_job');

function boblift_mls_handle_restore_backup(): void
{
    if (!current_user_can('manage_options')) {
        wp_die('权限不足。');
    }
    $language = isset($_POST['language']) ? sanitize_key(wp_unslash($_POST['language'])) : '';
    check_admin_referer('boblift_mls_restore_' . $language);
    $result = function_exists('boblift_mls_restore_language_backup') ? boblift_mls_restore_language_backup($language) : new WP_Error('boblift_mls_no_restore', '恢复函数不可用。');
    $location = admin_url('admin.php?page=boblift-multilingual-sync');
    if (is_wp_error($result)) {
        boblift_mls_admin_notice_redirect($location, 'error', $result->get_error_message());
    }
    boblift_mls_admin_notice_redirect($location, 'updated', '已恢复 ' . $language . ' 最近一次同步前备份。');
}
add_action('admin_post_boblift_mls_restore_backup', 'boblift_mls_handle_restore_backup');


function boblift_mls_handle_save_settings(): void
{
    if (!current_user_can('manage_options')) {
        wp_die('权限不足。');
    }
    check_admin_referer('boblift_mls_save_settings');
    $languages = boblift_mls_languages();
    $source = isset($_POST['source_language']) ? sanitize_key(wp_unslash($_POST['source_language'])) : boblift_mls_source_language();
    if (!in_array($source, $languages, true)) {
        $source = $languages[0] ?? 'en';
    }
    if (function_exists('boblift_mls_migrate_source_language')) {
        $migration = boblift_mls_migrate_source_language($source);
        if (is_wp_error($migration)) {
            boblift_mls_admin_notice_redirect(admin_url('admin.php?page=boblift-multilingual-sync'), 'error', $migration->get_error_message());
        }
    }
    update_option(BOBLIFT_MLS_SETTINGS_OPTION, ['source_language' => $source, 'sections' => boblift_mls_default_sections()], false);
    boblift_mls_admin_notice_redirect(admin_url('admin.php?page=boblift-multilingual-sync'), 'updated', '设置已保存。');
}
add_action('admin_post_boblift_mls_save_settings', 'boblift_mls_handle_save_settings');


function boblift_mls_handle_create_job(): void
{
    if (!current_user_can('manage_options')) {
        wp_die('权限不足。');
    }
    check_admin_referer('boblift_mls_create_job');
    $language = isset($_POST['target_language']) ? sanitize_key(wp_unslash($_POST['target_language'])) : '';
    $overwrite_groups = isset($_POST['overwrite_groups']) && is_array($_POST['overwrite_groups'])
        ? array_map(static fn($value) => sanitize_key((string) wp_unslash($value)), $_POST['overwrite_groups'])
        : [];
    $result = function_exists('boblift_mls_create_translation_job') ? boblift_mls_create_translation_job($language, $overwrite_groups) : new WP_Error('boblift_mls_no_jobs', '任务系统不可用。');
    if (is_wp_error($result)) {
        boblift_mls_admin_notice_redirect(admin_url('admin.php?page=boblift-multilingual-sync'), 'error', $result->get_error_message());
    }
    wp_safe_redirect(boblift_mls_job_url((int) $result));
    exit;
}
add_action('admin_post_boblift_mls_create_job', 'boblift_mls_handle_create_job');

function boblift_mls_handle_create_clone_job(): void
{
    if (!current_user_can('manage_options')) {
        wp_die('权限不足。');
    }
    check_admin_referer('boblift_mls_create_clone_job');
    $language = isset($_POST['target_language']) ? sanitize_key(wp_unslash($_POST['target_language'])) : '';
    $result = function_exists('boblift_mls_create_clone_language_job') ? boblift_mls_create_clone_language_job($language) : new WP_Error('boblift_mls_no_jobs', '任务系统不可用。');
    if (is_wp_error($result)) {
        boblift_mls_admin_notice_redirect(admin_url('admin.php?page=boblift-multilingual-sync&wizard_language=' . rawurlencode($language)), 'error', $result->get_error_message());
    }
    wp_safe_redirect(boblift_mls_job_url((int) $result));
    exit;
}
add_action('admin_post_boblift_mls_create_clone_job', 'boblift_mls_handle_create_clone_job');

function boblift_mls_handle_create_translate_language_job(): void
{
    if (!current_user_can('manage_options')) {
        wp_die('权限不足。');
    }
    check_admin_referer('boblift_mls_create_translate_language_job');
    $language = isset($_POST['target_language']) ? sanitize_key(wp_unslash($_POST['target_language'])) : '';
    $result = function_exists('boblift_mls_create_translate_language_job') ? boblift_mls_create_translate_language_job($language) : new WP_Error('boblift_mls_no_jobs', '任务系统不可用。');
    if (is_wp_error($result)) {
        boblift_mls_admin_notice_redirect(admin_url('admin.php?page=boblift-multilingual-sync&wizard_language=' . rawurlencode($language)), 'error', $result->get_error_message());
    }
    wp_safe_redirect(boblift_mls_job_url((int) $result));
    exit;
}
add_action('admin_post_boblift_mls_create_translate_language_job', 'boblift_mls_handle_create_translate_language_job');

function boblift_mls_handle_create_publish_language_job(): void
{
    if (!current_user_can('manage_options')) {
        wp_die('权限不足。');
    }
    check_admin_referer('boblift_mls_create_publish_language_job');
    $language = isset($_POST['target_language']) ? sanitize_key(wp_unslash($_POST['target_language'])) : '';
    $result = function_exists('boblift_mls_create_publish_language_job') ? boblift_mls_create_publish_language_job($language) : new WP_Error('boblift_mls_no_jobs', '任务系统不可用。');
    if (is_wp_error($result)) {
        boblift_mls_admin_notice_redirect(admin_url('admin.php?page=boblift-multilingual-sync&wizard_language=' . rawurlencode($language)), 'error', $result->get_error_message());
    }
    wp_safe_redirect(boblift_mls_job_url((int) $result));
    exit;
}
add_action('admin_post_boblift_mls_create_publish_language_job', 'boblift_mls_handle_create_publish_language_job');

function boblift_mls_handle_retry_job(): void
{
    if (!current_user_can('manage_options')) {
        wp_die('权限不足。');
    }
    $job_id = isset($_POST['job_id']) ? absint($_POST['job_id']) : 0;
    $return_to = isset($_POST['return_to']) ? wp_validate_redirect(esc_url_raw(wp_unslash($_POST['return_to'])), '') : '';
    $job_url = boblift_mls_job_url($job_id, $return_to);
    check_admin_referer('boblift_mls_retry_job_' . $job_id);
    $result = function_exists('boblift_mls_retry_job') ? boblift_mls_retry_job($job_id) : new WP_Error('boblift_mls_no_retry', '重试函数不可用。');
    if (is_wp_error($result)) {
        boblift_mls_admin_notice_redirect($job_url, 'error', $result->get_error_message());
    }
    boblift_mls_admin_notice_redirect($job_url, 'updated', '失败项已重新排队。');
}
add_action('admin_post_boblift_mls_retry_job', 'boblift_mls_handle_retry_job');


function boblift_mls_handle_pause_job(): void
{
    if (!current_user_can('manage_options')) {
        wp_die('权限不足。');
    }
    $job_id = isset($_POST['job_id']) ? absint($_POST['job_id']) : 0;
    $return_to = isset($_POST['return_to']) ? wp_validate_redirect(esc_url_raw(wp_unslash($_POST['return_to'])), '') : '';
    $job_url = boblift_mls_job_url($job_id, $return_to);
    check_admin_referer('boblift_mls_pause_job_' . $job_id);
    $result = function_exists('boblift_mls_pause_job') ? boblift_mls_pause_job($job_id) : new WP_Error('boblift_mls_no_pause', '暂停函数不可用。');
    if (is_wp_error($result)) {
        boblift_mls_admin_notice_redirect($job_url, 'error', $result->get_error_message());
    }
    boblift_mls_admin_notice_redirect($job_url, 'updated', '任务已暂停。');
}
add_action('admin_post_boblift_mls_pause_job', 'boblift_mls_handle_pause_job');

function boblift_mls_handle_resume_job(): void
{
    if (!current_user_can('manage_options')) {
        wp_die('权限不足。');
    }
    $job_id = isset($_POST['job_id']) ? absint($_POST['job_id']) : 0;
    $return_to = isset($_POST['return_to']) ? wp_validate_redirect(esc_url_raw(wp_unslash($_POST['return_to'])), '') : '';
    $job_url = boblift_mls_job_url($job_id, $return_to);
    check_admin_referer('boblift_mls_resume_job_' . $job_id);
    $result = function_exists('boblift_mls_resume_job') ? boblift_mls_resume_job($job_id) : new WP_Error('boblift_mls_no_resume', '恢复函数不可用。');
    if (is_wp_error($result)) {
        boblift_mls_admin_notice_redirect($job_url, 'error', $result->get_error_message());
    }
    boblift_mls_admin_notice_redirect($job_url, 'updated', '任务已恢复，保持进度页打开时会继续执行。');
}
add_action('admin_post_boblift_mls_resume_job', 'boblift_mls_handle_resume_job');

function boblift_mls_ajax_job_events(): void
{
    if (!current_user_can('manage_options')) {
        status_header(403);
        exit;
    }
    $job_id = isset($_GET['job_id']) ? absint($_GET['job_id']) : 0;
    $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
    if (!wp_verify_nonce($nonce, 'boblift_mls_job_' . $job_id)) {
        status_header(403);
        exit;
    }
    nocache_headers();
    header('Content-Type: text/event-stream; charset=UTF-8');
    header('X-Accel-Buffering: no');
    header('Cache-Control: no-cache, no-transform');
    ignore_user_abort(true);
    if (function_exists('set_time_limit')) {
        @set_time_limit(0);
    }
    $state = boblift_mls_job_public_state($job_id);
    $status = (string) ($state['status'] ?? '');
    if (in_array($status, ['pending', 'running'], true) && function_exists('boblift_mls_run_job_batch')) {
        $run_result = boblift_mls_run_job_batch($job_id, 1);
        if (!is_wp_error($run_result) && is_array($run_result)) {
            $state = $run_result;
        }
    }
    echo 'retry: 2000' . "\n";
    echo 'event: state' . "\n";
    echo 'data: ' . wp_json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
    if (function_exists('wp_ob_end_flush_all')) {
        wp_ob_end_flush_all();
    }
    flush();
    exit;
}
add_action('wp_ajax_boblift_mls_job_events', 'boblift_mls_ajax_job_events');


function boblift_mls_is_admin_page(): bool
{
    return isset($_GET['page']) && sanitize_key(wp_unslash($_GET['page'])) === 'boblift-multilingual-sync';
}

function boblift_mls_hide_polylang_admin_bar_filter(array $items): array
{
    return boblift_mls_is_admin_page() ? [] : $items;
}
add_filter('pll_admin_languages_filter', 'boblift_mls_hide_polylang_admin_bar_filter', 20);

function boblift_mls_admin_menu(): void
{
    add_submenu_page('boblift-global-settings', '多语言同步', '多语言同步', 'manage_options', 'boblift-multilingual-sync', 'boblift_mls_admin_page');
}
add_action('admin_menu', 'boblift_mls_admin_menu', 30);

function boblift_mls_admin_notices(): void
{
    if (!isset($_GET['boblift_mls_status'], $_GET['boblift_mls_message'])) {
        return;
    }
    $class = sanitize_key(wp_unslash($_GET['boblift_mls_status'])) === 'error' ? 'notice-error' : 'notice-success';
    $message = sanitize_text_field(wp_unslash($_GET['boblift_mls_message']));
    echo '<div class="notice ' . esc_attr($class) . ' is-dismissible"><p>' . esc_html($message) . '</p></div>';
}
add_action('admin_notices', 'boblift_mls_admin_notices');


function boblift_mls_section_label(string $section): string
{
    return [
        'branding' => '品牌 Logo',
        'contact' => '联系方式',
        'floating_contact_buttons' => '悬浮客服按钮',
        'top_bar' => '顶部公告条',
        'nav_items' => '主导航菜单',
        'product_nav_groups' => '二级菜单',
        'news_categories' => 'News 分类筛选',
        'site_ui' => '通用页面文案',
        'static_text' => '固定界面文案',
        'seo_meta' => 'SEO 标题和描述',
        'footer' => '通用底部',
        'final_cta' => '底部 CTA',
        'home' => '首页内容',
        'about_page' => '关于页内容',
        'social_media_page' => 'Social Media 页面',
        'public_welfare_page' => 'Public Welfare 页面',
        'privacy_policy_page' => 'Privacy Policy 页面',
        'terms_of_use_page' => 'Terms of Use 页面',
        'sitemap_page' => 'Sitemap 页面',
        'product_archive' => '产品页默认 Banner',
    ][$section] ?? $section;
}

function boblift_mls_section_note(string $section): string
{
    return [
        'branding' => 'Header 与 Footer 的站点 Logo。',
        'contact' => '电话、邮箱、WhatsApp、地址和微信二维码。',
        'floating_contact_buttons' => '前台悬浮客服按钮列表、排序和链接。',
        'top_bar' => '网站顶部深蓝色公告条。',
        'nav_items' => 'Header 横向主导航。',
        'product_nav_groups' => 'Header 产品二级菜单。',
        'news_categories' => 'News 页面顶部分类筛选。',
        'site_ui' => 'Contact、Quote、News、Success、详情页等通用 UI 文案。',
        'static_text' => '主题内置的按钮、标签和页面固定文字，每条保留英文原文，可逐条翻译或覆盖。',
        'seo_meta' => '页面、文章、产品、案例、分类的 SEO 标题和描述。',
        'footer' => 'Footer 简介、链接组、社媒、版权。',
        'final_cta' => 'Footer 上方的大图询盘横幅。',
        'home' => '首页 Hero、认证、产品区、About 区、News 区。',
        'about_page' => 'About 页面大元框内容。',
        'social_media_page' => 'Social Media 页面首屏、平台分组、内容卡片和 CTA。',
        'public_welfare_page' => 'Public Welfare 页面 Banner、主声明、责任卡片和 CTA。',
        'privacy_policy_page' => 'Privacy Policy 页面头部、侧栏和条款正文。',
        'terms_of_use_page' => 'Terms of Use 页面头部和条款正文。',
        'sitemap_page' => 'HTML Sitemap 页面标题、区块标题和说明文案。',
        'product_archive' => 'Products 页面默认 Banner。',
    ][$section] ?? '';
}

function boblift_mls_status_label(string $language, string $section): string
{
    $current = boblift_mls_source_section_hash($section);
    $synced = boblift_mls_last_synced_hash($language, $section);
    if ($synced === '') {
        return '未同步';
    }
    return hash_equals($current, $synced) ? '已同步' : '源语言已更新，需要同步';
}

function boblift_mls_status_is_synced(string $status): bool
{
    return str_starts_with($status, '已同步');
}

function boblift_mls_change_path_label(string $path): string
{
    $parts = array_values(array_filter(explode('.', $path), static fn(string $part): bool => $part !== ''));
    $last = (string) end($parts);
    $labels = [
        'label' => '小标签',
        'title' => '标题',
        'text' => '正文',
        'paragraph' => '段落',
        'description' => '描述',
        'headline' => '主标题',
        'image' => '图片',
        'image_alt' => '图片 Alt',
        'href' => '链接',
        'phone' => '电话',
        'email' => '邮箱',
        'whatsapp' => 'WhatsApp',
        'telegram' => 'Telegram',
        'address' => '地址',
        'button_label' => '按钮文字',
        'contact_title' => '联系标题',
        'copyright' => '版权文案',
        'intro' => '简介',
    ];
    $label = $labels[$last] ?? $last;
    return $label . '（' . $path . '）';
}

function boblift_mls_section_change_summary(string $language, string $section): string
{
    if (!function_exists('boblift_mls_source_section_change_paths')) {
        return '';
    }
    $paths = boblift_mls_source_section_change_paths($language, $section, 6);
    if ($paths === []) {
        return '需要同步：主语言这个区块已变化。旧记录没有字段快照，无法精确标出字段；下次同步后会开始记录字段级变化。';
    }
    $labels = array_map('boblift_mls_change_path_label', $paths);
    return '变化字段：' . implode('、', $labels) . (count($paths) >= 6 ? ' 等' : '');
}

function boblift_mls_render_settings_section_card(string $section, string $language, string $status): void
{
    $needs_sync = !boblift_mls_status_is_synced($status);
    $border = $needs_sync ? '#f59a3c' : '#dcdcde';
    $background = $needs_sync ? '#fffaf3' : '#fff';
    echo '<div style="padding:10px 12px;border:1px solid ' . esc_attr($border) . ';border-radius:10px;background:' . esc_attr($background) . ';">';
    echo '<div style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between;">';
    echo '<strong>' . esc_html(boblift_mls_section_label($section)) . '</strong>';
    echo '<span class="description">状态：' . esc_html($status) . '</span>';
    echo '</div>';
    if ($needs_sync) {
        echo '<p class="description" style="margin:8px 0 0;color:#8a4b00;">' . esc_html(boblift_mls_section_change_summary($language, $section)) . '</p>';
    }
    echo '<details style="margin-top:8px;" ' . ($needs_sync ? 'open' : '') . '><summary class="description" style="cursor:pointer;">更多操作</summary><div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px;">';
    boblift_mls_render_sync_button($section, $language, 'translate', '从主语言同步并翻译这个区块', 0, !boblift_mls_translation_available());
    boblift_mls_render_sync_button($section, $language, 'params', '只同步图片、链接、数字，不改译文', 0);
    echo '</div></details></div>';
}

function boblift_mls_sync_confirm_message(string $section, string $language, string $mode): string
{
    if ($section === '__settings_page') {
        return $mode === 'translate'
            ? '确认为 ' . $language . ' 创建当前设置页全部区块的重新翻译任务吗？任务会在进度页执行。'
            : '确认为 ' . $language . ' 创建当前设置页参数同步任务吗？这不会覆盖已有译文。';
    }
    if ($section === '__all__') {
        return $mode === 'translate'
            ? '确认为 ' . $language . ' 创建 BOB-LIFT 配置与 SEO 翻译任务吗？任务会在进度页执行。'
            : '确认为 ' . $language . ' 创建参数同步任务吗？这不会覆盖已有译文，SEO 会跳过。';
    }
    if ($mode === 'translate') {
        return '确认为 ' . $language . ' 创建这个区块的同步任务吗？';
    }
    return '';
}

function boblift_mls_render_sync_form(string $section, string $language, string $mode, string $label, int $post_id = 0, bool $disabled = false): void
{
    $confirm = boblift_mls_sync_confirm_message($section, $language, $mode);
    $onsubmit = $confirm !== '' ? ' onsubmit="return confirm(' . esc_attr(wp_json_encode($confirm, JSON_UNESCAPED_UNICODE)) . ');"' : '';
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline-block;margin:0 8px 8px 0;"' . $onsubmit . '>';
    echo '<input type="hidden" name="action" value="boblift_mls_create_scoped_job">';
    echo '<input type="hidden" name="post_id" value="' . esc_attr((string) $post_id) . '">';
    echo '<input type="hidden" name="section" value="' . esc_attr($section) . '">';
    echo '<input type="hidden" name="language" value="' . esc_attr($language) . '">';
    echo '<input type="hidden" name="mode" value="' . esc_attr($mode) . '">';
    wp_nonce_field('boblift_mls_sync_' . $section . '_' . $language);
    echo '<button type="submit" class="button" ' . disabled($disabled, true, false) . '>' . esc_html($label) . '</button>';
    echo '</form>';
}

function boblift_mls_render_sync_button(string $section, string $language, string $mode, string $label, int $post_id = 0, bool $disabled = false, string $redirect_to = ''): void
{
    if ($redirect_to === '') {
        $redirect_to = boblift_mls_current_admin_url(admin_url('admin.php?page=boblift-multilingual-sync'));
    }
    $confirm = boblift_mls_sync_confirm_message($section, $language, $mode);
    echo '<button type="button" class="button boblift-mls-sync-button" style="margin:0 8px 8px 0;" ' . disabled($disabled, true, false)
        . ' data-confirm="' . esc_attr($confirm) . '"'
        . ' data-section="' . esc_attr($section) . '"'
        . ' data-language="' . esc_attr($language) . '"'
        . ' data-mode="' . esc_attr($mode) . '"'
        . ' data-post-id="' . esc_attr((string) $post_id) . '"'
        . ' data-nonce="' . esc_attr(wp_create_nonce('boblift_mls_sync_' . $section . '_' . $language)) . '"'
        . ' data-redirect="' . esc_attr($redirect_to) . '">' . esc_html($label) . '</button>';
    boblift_mls_render_sync_button_script();
}

function boblift_mls_render_settings_page_sync_button(array $sections, string $language, string $mode, string $label, bool $disabled = false): void
{
    $sections = array_values(array_filter(array_map('sanitize_key', $sections)));
    if ($sections === []) {
        return;
    }
    if (!in_array($mode, ['translate', 'params'], true)) {
        $mode = 'translate';
    }
    $return_to = boblift_mls_current_admin_url(admin_url('admin.php?page=' . sanitize_key((string) ($_GET['page'] ?? 'boblift-global-settings'))));
    $confirm = boblift_mls_sync_confirm_message('__settings_page', $language, $mode);
    echo '<button type="button" class="button boblift-mls-sync-button" style="margin:0 8px 8px 0;" ' . disabled($disabled, true, false)
        . ' data-confirm="' . esc_attr($confirm) . '"'
        . ' data-section="__settings_page"'
        . ' data-sections="' . esc_attr(implode(',', $sections)) . '"'
        . ' data-language="' . esc_attr($language) . '"'
        . ' data-mode="' . esc_attr($mode) . '"'
        . ' data-post-id="0"'
        . ' data-nonce="' . esc_attr(wp_create_nonce('boblift_mls_sync___settings_page_' . $language)) . '"'
        . ' data-redirect="' . esc_attr($return_to) . '">' . esc_html($label) . '</button>';
    boblift_mls_render_sync_button_script();
}

function boblift_mls_render_sync_button_script(): void
{
    static $rendered = false;
    if ($rendered) {
        return;
    }
    $rendered = true;
    echo <<<'HTML'
<script>
document.addEventListener('click', function (event) {
  var button = event.target.closest('.boblift-mls-sync-button');
  if (!button || button.disabled) {
    return;
  }
  event.preventDefault();
  var confirmMessage = button.dataset.confirm || '';
  if (confirmMessage && !window.confirm(confirmMessage)) {
    return;
  }
  var form = document.createElement('form');
  form.method = 'post';
  form.action = window.ajaxurl ? ajaxurl.replace('admin-ajax.php', 'admin-post.php') : 'admin-post.php';
  var fields = {
    action: 'boblift_mls_create_scoped_job',
    post_id: button.dataset.postId || '0',
    section: button.dataset.section || '',
    sections: button.dataset.sections || '',
    language: button.dataset.language || '',
    mode: button.dataset.mode || 'params',
    _wpnonce: button.dataset.nonce || '',
    redirect_to: button.dataset.redirect || window.location.href
  };
  Object.keys(fields).forEach(function (name) {
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = fields[name];
    form.appendChild(input);
  });
  document.body.appendChild(form);
  form.submit();
});
</script>
HTML;
}

function boblift_mls_settings_sections_for_page(string $page): array
{
    return [
        'boblift-global-settings' => ['branding', 'contact', 'floating_contact_buttons', 'top_bar', 'final_cta', 'product_archive', 'site_ui'],
        'boblift-navigation-settings' => ['nav_items', 'product_nav_groups'],
        'boblift-footer-settings' => ['footer'],
        'boblift-news-settings' => ['news_categories'],
    ][$page] ?? [];
}

function boblift_mls_render_settings_controller(string $page, ?string $language = null): void
{
    $sections = boblift_mls_settings_sections_for_page($page);
    if ($sections === [] || !current_user_can('manage_options')) {
        return;
    }
    $language = $language ?: boblift_mls_current_language();
    $source = boblift_mls_source_language();
    $section_statuses = [];
    $needs_sections = [];
    $synced_sections = [];
    foreach ($sections as $section) {
        $status = boblift_mls_status_label($language, $section);
        $section_statuses[$section] = $status;
        if (boblift_mls_status_is_synced($status)) {
            $synced_sections[] = $section;
        } else {
            $needs_sections[] = $section;
        }
    }
    $synced_count = count($synced_sections);
    $needs_count = count($needs_sections);
    $needs_summary = $needs_count > 0 ? '；需要同步：' . implode('、', array_map('boblift_mls_section_label', $needs_sections)) : '';
    $summary = $language === $source ? '主语言维护页' : '本页 ' . count($sections) . ' 个区块：' . $synced_count . ' 个已同步，' . $needs_count . ' 个需要同步' . $needs_summary;
    echo '<details class="boblift-mls-controller" style="max-width:1180px;margin:12px 0 18px;border:1px solid #c3c4c7;border-radius:12px;background:#f6f7f7;">';
    echo '<summary style="cursor:pointer;padding:14px 16px;font-weight:700;">高级：当前设置页同步修复 <span class="description" style="margin-left:10px;font-weight:400;">' . esc_html($summary) . '</span></summary>';
    echo '<div style="padding:0 16px 16px;">';
    echo '<p style="margin:0 0 10px;">当前语言：' . esc_html($language) . '；源语言：' . esc_html($source) . '。</p>';
    if ($language === $source) {
        echo '<p class="description" style="margin:0;">这里是主维护语言。本工具仅用于目标语言设置页的高级修复；新增语言和批量翻译请使用 BOB-LIFT → 多语言同步。</p>';
        echo '</div></details>';
        return;
    }
    echo '<p class="description" style="margin:0 0 12px;">这里只修复当前设置页包含的区块。新增语言和批量翻译请使用 BOB-LIFT → 多语言同步。</p>';
    echo '<div style="display:flex;flex-wrap:wrap;gap:8px;margin:0 0 14px;">';
    boblift_mls_render_settings_page_sync_button($sections, $language, 'translate', '重新翻译本页全部区块', !boblift_mls_translation_available());
    boblift_mls_render_settings_page_sync_button($sections, $language, 'params', '只同步本页参数');
    echo '</div>';
    echo '<div style="display:grid;gap:8px;">';
    if ($needs_sections === []) {
        echo '<div style="padding:10px 12px;border:1px solid #dcdcde;border-radius:10px;background:#fff;"><strong>当前没有需要同步的区块。</strong></div>';
    } else {
        foreach ($needs_sections as $section) {
            boblift_mls_render_settings_section_card($section, $language, $section_statuses[$section] ?? boblift_mls_status_label($language, $section));
        }
    }
    if ($synced_sections !== []) {
        echo '<details style="margin-top:4px;"><summary class="description" style="cursor:pointer;">已同步区块（' . esc_html((string) count($synced_sections)) . '）</summary><div style="display:grid;gap:8px;margin-top:8px;">';
        foreach ($synced_sections as $section) {
            boblift_mls_render_settings_section_card($section, $language, $section_statuses[$section] ?? '已同步');
        }
        echo '</div></details>';
    }
    echo '</div>';
    if (!boblift_mls_translation_available()) {
        echo '<p class="description" style="margin:10px 0 0;">Polytranslate AI 翻译函数不可用，当前只能同步参数。</p>';
    }
    echo '</div></details>';
}

function boblift_mls_render_page_controller(WP_Post $post, string $section): void
{
    $language = boblift_mls_post_language($post);
    $source = boblift_mls_source_language();
    $status = '源语言';
    if ($language !== $source) {
        $status = function_exists('boblift_mls_page_bundle_status_label')
            ? boblift_mls_page_bundle_status_label($language, $post->ID, $section)
            : boblift_mls_status_label($language, $section);
    }
    echo '<details class="boblift-mls-controller" style="margin:0 0 16px;border:1px solid #c3c4c7;border-radius:10px;background:#f6f7f7;">';
    echo '<summary style="cursor:pointer;padding:12px 14px;font-weight:700;">高级：单页同步修复</summary>';
    echo '<div style="padding:0 14px 14px;">';
    echo '<p style="margin:0 0 10px;">当前语言：' . esc_html($language) . '；源语言：' . esc_html($source) . '；状态：' . esc_html($status) . '。</p>';
    if ($language !== $source) {
        echo '<p class="description" style="margin:0 0 12px;">这里仅用于修复当前页面。重新翻译会覆盖本页文字、标题和 SEO；只同步参数不会改译文。</p>';
        echo '<div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">';
        boblift_mls_render_sync_button($section, $language, 'translate', '重新翻译本页文字 + 同步图片和 SEO', $post->ID, !boblift_mls_translation_available(), (string) get_edit_post_link($post->ID, 'raw'));
        boblift_mls_render_sync_button($section, $language, 'params', '只同步图片、链接、数字，不改译文', $post->ID, false, (string) get_edit_post_link($post->ID, 'raw'));
        echo '</div>';
        if (!boblift_mls_translation_available()) {
            echo '<p class="description" style="margin:8px 0 0;">Polytranslate AI 翻译函数不可用，当前只能同步参数。</p>';
        }
    } else {
        echo '<p class="description" style="margin:0;">这是主语言维护页。这里的同步工具仅用于目标语言页面的单页修复。新增语言和批量翻译请使用 BOB-LIFT → 多语言同步。</p>';
    }
    echo '</div></details>';
}

function boblift_mls_count_missing_option_sections(string $language): int
{
    $options = boblift_mls_get_raw_options($language);
    $missing = 0;
    foreach (boblift_mls_default_sections() as $section) {
        if ($section === 'seo_meta') {
            continue;
        }
        if (!array_key_exists($section, $options)) {
            $missing++;
        }
    }
    return $missing;
}

function boblift_mls_seo_coverage_counts(string $language): array
{
    $source_language = boblift_mls_source_language();
    $total = 0;
    $missing = 0;
    foreach (['page', 'post', 'product', 'case'] as $post_type) {
        $posts = get_posts(['post_type' => $post_type, 'post_status' => 'any', 'numberposts' => -1, 'lang' => $source_language]);
        foreach ($posts as $source_post) {
            if (!$source_post instanceof WP_Post || !function_exists('pll_get_post')) {
                continue;
            }
            $target_id = (int) pll_get_post($source_post->ID, $language);
            if ($target_id <= 0) {
                continue;
            }
            $total++;
            if (trim((string) get_post_meta($target_id, '_seo_title', true)) === '' || trim((string) get_post_meta($target_id, '_seo_description', true)) === '') {
                $missing++;
            }
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
            $target_id = (int) pll_get_term($source_term->term_id, $language);
            if ($target_id <= 0) {
                continue;
            }
            $total++;
            if (trim((string) get_term_meta($target_id, '_boblift_seo_title', true)) === '' || trim((string) get_term_meta($target_id, '_boblift_seo_description', true)) === '') {
                $missing++;
            }
        }
    }
    return ['total' => $total, 'missing' => $missing];
}

function boblift_mls_missing_translation_object_counts(string $language): array
{
    $source_language = boblift_mls_source_language();
    $counts = ['posts' => 0, 'terms' => 0, 'total' => 0];
    foreach (['page', 'post', 'product', 'case'] as $post_type) {
        $posts = get_posts(['post_type' => $post_type, 'post_status' => 'any', 'numberposts' => -1, 'lang' => $source_language]);
        foreach ($posts as $source_post) {
            if (!$source_post instanceof WP_Post || !function_exists('pll_get_post')) {
                continue;
            }
            if ((int) pll_get_post($source_post->ID, $language) <= 0) {
                $counts['posts']++;
                $counts['total']++;
            }
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
            if ((int) pll_get_term($source_term->term_id, $language) <= 0) {
                $counts['terms']++;
                $counts['total']++;
            }
        }
    }
    return $counts;
}

function boblift_mls_missing_option_section_labels(string $language): array
{
    $options = boblift_mls_get_raw_options($language);
    $missing = [];
    foreach (boblift_mls_default_sections() as $section) {
        if ($section === 'seo_meta') {
            continue;
        }
        if (!array_key_exists($section, $options)) {
            $missing[] = boblift_mls_section_label($section);
        }
    }
    return $missing;
}

function boblift_mls_render_restore_backup_form(string $language): void
{
    $backup = get_option('boblift_mls_backup_' . sanitize_key($language), []);
    if (!is_array($backup) || !is_array($backup['options'] ?? null)) {
        echo '<span class="description">暂无备份</span>';
        return;
    }
    $time = !empty($backup['time']) ? date_i18n('Y-m-d H:i', (int) $backup['time']) : '未知时间';
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline-block;margin:0 8px 8px 0;" onsubmit="return confirm(' . esc_attr(wp_json_encode('确认恢复 ' . $language . ' 最近一次同步前备份吗？当前目标语言配置会被备份内容覆盖。', JSON_UNESCAPED_UNICODE)) . ');">';
    echo '<input type="hidden" name="action" value="boblift_mls_restore_backup">';
    echo '<input type="hidden" name="language" value="' . esc_attr($language) . '">';
    wp_nonce_field('boblift_mls_restore_' . $language);
    echo '<button type="submit" class="button">恢复上次备份</button> <span class="description">' . esc_html($time) . '</span>';
    echo '</form>';
}

function boblift_mls_render_coverage_overview(array $languages, string $source): void
{
    echo '<h2 style="margin-top:28px;">语言覆盖概览</h2>';
    echo '<table class="widefat striped"><thead><tr><th>目标语言</th><th>主题配置缺失区块</th><th>缺失翻译对象</th><th>SEO 覆盖</th><th>推荐入口</th></tr></thead><tbody>';
    foreach ($languages as $language) {
        if ($language === $source) {
            continue;
        }
        $missing_labels = boblift_mls_missing_option_section_labels($language);
        $seo = boblift_mls_seo_coverage_counts($language);
        $missing_objects = boblift_mls_missing_translation_object_counts($language);
        echo '<tr><td><strong>' . esc_html($language) . '</strong></td>';
        echo '<td>' . ($missing_labels === [] ? '无' : esc_html(implode('、', $missing_labels))) . '</td>';
        echo '<td>' . ($missing_objects['total'] > 0 ? esc_html('缺页面/内容 ' . $missing_objects['posts'] . ' 个，缺分类 ' . $missing_objects['terms'] . ' 个') : '无') . '</td>';
        echo '<td>' . esc_html((string) max(0, $seo['total'] - $seo['missing'])) . ' / ' . esc_html((string) $seo['total']) . ' 已有 SEO</td>';
        echo '<td>新增语言走向导；已有语言用高级修复缺失项</td></tr>';
    }
    echo '</tbody></table>';
}


function boblift_mls_render_jobs_overview(): void
{
    $jobs = function_exists('boblift_mls_get_recent_jobs') ? boblift_mls_get_recent_jobs(20) : [];
    echo '<h2 style="margin-top:28px;">任务记录</h2>';
    echo '<div style="max-width:1180px;">';
    if ($jobs === []) {
        echo '<p class="description">还没有创建过批量翻译任务。</p></div>';
        return;
    }
    echo '<table class="widefat striped"><thead><tr><th>任务</th><th>类型</th><th>语言</th><th>状态</th><th>进度</th><th>当前处理</th><th>时间</th><th>操作</th></tr></thead><tbody>';
    foreach ($jobs as $job) {
        $job_id = (int) $job['id'];
        $total = (int) $job['total_items'];
        $done = (int) $job['completed_items'];
        $skipped = (int) $job['skipped_items'];
        $failed = (int) $job['failed_items'];
        $processed = $done + $skipped + $failed;
        $percent = $total > 0 ? min(100, round($processed * 100 / $total)) : 0;
        echo '<tr>';
        echo '<td><strong>#' . esc_html((string) $job_id) . '</strong></td>';
        echo '<td>' . esc_html(function_exists('boblift_mls_job_type_label') ? boblift_mls_job_type_label((string) ($job['job_type'] ?? 'repair_missing')) : (string) ($job['job_type'] ?? 'repair_missing')) . '</td>';
        echo '<td>' . esc_html((string) $job['source_language']) . ' → ' . esc_html((string) $job['target_language']) . '</td>';
        echo '<td>' . esc_html(function_exists('boblift_mls_job_status_label') ? boblift_mls_job_status_label((string) $job['status']) : (string) $job['status']) . '</td>';
        echo '<td><div style="height:8px;width:160px;background:#f0f0f1;border-radius:999px;overflow:hidden;margin-bottom:4px;"><div style="height:100%;width:' . esc_attr((string) $percent) . '%;background:#2271b1;"></div></div>';
        echo esc_html($processed . ' / ' . $total . '；成功 ' . $done . '，跳过 ' . $skipped . '，失败 ' . $failed) . '</td>';
        echo '<td>' . esc_html((string) ($job['current_label'] ?: '—')) . '</td>';
        echo '<td>' . esc_html((string) $job['updated_at']) . '</td>';
        echo '<td><a class="button button-small" href="' . esc_url(boblift_mls_job_url($job_id)) . '">查看详情</a></td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

function boblift_mls_render_language_wizard(array $languages, string $source): void
{
    $target = isset($_GET['wizard_language']) ? sanitize_key(wp_unslash($_GET['wizard_language'])) : '';
    echo '<h2 style="margin-top:28px;">新增语言向导</h2>';
    echo '<div style="padding:16px;background:#fff;border:1px solid #c3c4c7;border-radius:12px;max-width:1180px;">';
    echo '<p style="margin-top:0;">流程：先把英语站完整复制成隐藏副本，再翻译文本，最后人工发布。初始化不调用 AI。</p>';
    echo '<form method="get" action="' . esc_url(admin_url('admin.php')) . '" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:16px;">';
    echo '<input type="hidden" name="page" value="boblift-multilingual-sync">';
    echo '<label><strong>目标语言</strong> <select name="wizard_language">';
    foreach ($languages as $language) {
        if ($language === $source) {
            continue;
        }
        echo '<option value="' . esc_attr($language) . '" ' . selected($target, $language, false) . '>' . esc_html($language) . '</option>';
    }
    echo '</select></label> ';
    submit_button('查看语言状态', 'secondary', 'submit', false);
    echo '</form>';

    if ($target === '' || $target === $source || !in_array($target, $languages, true)) {
        echo '<p class="description" style="margin-bottom:0;">第一步：选择目标语言；第二步：初始化语言；第三步：开始翻译；第四步：检查后发布。</p>';
        echo '</div>';
        return;
    }
    $status = function_exists('boblift_mls_language_status') ? boblift_mls_language_status($target) : 'new';
    $clone_items = function_exists('boblift_mls_clone_scan_items') ? boblift_mls_clone_scan_items($target) : [];
    $translate_items = function_exists('boblift_mls_translate_language_scan_items') ? boblift_mls_translate_language_scan_items($target) : [];
    $clone_count = is_wp_error($clone_items) ? 0 : count($clone_items);
    $translate_count = is_wp_error($translate_items) ? 0 : count($translate_items);
    echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;margin:12px 0;">';
    echo '<div style="border:1px solid #dcdcde;border-radius:10px;padding:12px;background:#f6f7f7;"><strong>当前状态</strong><p style="margin:8px 0 0;">' . esc_html(function_exists('boblift_mls_language_status_label') ? boblift_mls_language_status_label($status) : $status) . '</p></div>';
    echo '<div style="border:1px solid #dcdcde;border-radius:10px;padding:12px;background:#f6f7f7;"><strong>初始化待复制</strong><p style="margin:8px 0 0;">' . esc_html((string) $clone_count) . ' 个对象 / 区块</p></div>';
    echo '<div style="border:1px solid #dcdcde;border-radius:10px;padding:12px;background:#f6f7f7;"><strong>翻译待处理</strong><p style="margin:8px 0 0;">' . esc_html((string) $translate_count) . ' 个对象 / 区块</p></div>';
    echo '</div>';
    echo '<div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">';
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="boblift_mls_create_clone_job"><input type="hidden" name="target_language" value="' . esc_attr($target) . '">';
    wp_nonce_field('boblift_mls_create_clone_job');
    submit_button('初始化语言', 'primary', 'submit', false, $clone_count <= 0 ? ['disabled' => 'disabled'] : []);
    echo '</form>';
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="boblift_mls_create_translate_language_job"><input type="hidden" name="target_language" value="' . esc_attr($target) . '">';
    wp_nonce_field('boblift_mls_create_translate_language_job');
    submit_button('开始翻译', 'secondary', 'submit', false, ($translate_count <= 0 || !in_array($status, ['staged', 'translating'], true) || !boblift_mls_translation_available()) ? ['disabled' => 'disabled'] : []);
    echo '</form>';
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="boblift_mls_create_publish_language_job"><input type="hidden" name="target_language" value="' . esc_attr($target) . '">';
    wp_nonce_field('boblift_mls_create_publish_language_job');
    submit_button('发布语言', 'secondary', 'submit', false, $status !== 'ready' ? ['disabled' => 'disabled'] : []);
    echo '</form>';
    echo '</div>';
    if (!boblift_mls_translation_available()) {
        echo '<p class="description" style="margin:10px 0 0;">Polytranslate AI 翻译函数不可用，暂时不能开始翻译。</p>';
    }
    echo '</div>';
}

function boblift_mls_job_return_url(array $job): string
{
    $fallback = admin_url('admin.php?page=boblift-multilingual-sync');
    $return_to = isset($_GET['return_to']) ? esc_url_raw(wp_unslash($_GET['return_to'])) : '';
    if ($return_to === '') {
        $policy = is_array($job['overwrite_policy'] ?? null) ? $job['overwrite_policy'] : [];
        $scope = is_array($policy['scope'] ?? null) ? $policy['scope'] : [];
        $return_to = isset($scope['return_to']) ? esc_url_raw((string) $scope['return_to']) : '';
    }
    return $return_to !== '' ? wp_validate_redirect($return_to, $fallback) : $fallback;
}

function boblift_mls_job_return_label(string $return_url): string
{
    $page = '';
    $query = wp_parse_url($return_url, PHP_URL_QUERY);
    if (is_string($query)) {
        parse_str($query, $params);
        $page = isset($params['page']) ? sanitize_key((string) $params['page']) : '';
    }
    return match ($page) {
        'boblift-global-settings' => '返回全局设置',
        'boblift-navigation-settings' => '返回导航菜单',
        'boblift-footer-settings' => '返回页脚设置',
        'boblift-news-settings' => '返回 News 分类',
        default => '返回任务来源页',
    };
}

function boblift_mls_render_job_page(int $job_id): void
{
    $job = function_exists('boblift_mls_get_job') ? boblift_mls_get_job($job_id) : null;
    if (!$job) {
        echo '<div class="notice notice-error"><p>任务不存在。</p></div>';
        return;
    }
    $nonce = wp_create_nonce('boblift_mls_job_' . $job_id);
    $events_url = add_query_arg([
        'action' => 'boblift_mls_job_events',
        'job_id' => $job_id,
        '_wpnonce' => $nonce,
    ], admin_url('admin-ajax.php'));
    $return_url = boblift_mls_job_return_url($job);
    echo '<p><a class="button" href="' . esc_url($return_url) . '">' . esc_html(boblift_mls_job_return_label($return_url)) . '</a> <a class="button button-secondary" href="' . esc_url(admin_url('admin.php?page=boblift-multilingual-sync')) . '">查看全部任务</a></p>';
    echo '<div class="boblift-mls-job" data-job-id="' . esc_attr((string) $job_id) . '" data-nonce="' . esc_attr($nonce) . '" data-events-url="' . esc_url($events_url) . '" style="max-width:980px;background:#fff;border:1px solid #c3c4c7;border-radius:12px;padding:18px;">';
    echo '<h2 style="margin-top:0;">' . esc_html(function_exists('boblift_mls_job_type_label') ? boblift_mls_job_type_label((string) ($job['job_type'] ?? 'repair_missing')) : '批量任务') . ' #' . esc_html((string) $job_id) . '</h2>';
    echo '<p>源语言：<strong>' . esc_html((string) $job['source_language']) . '</strong>；目标语言：<strong>' . esc_html((string) $job['target_language']) . '</strong></p>';
    echo '<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:10px 0 14px;">';
    if (in_array((string) $job['status'], ['pending', 'running'], true)) {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="boblift_mls_pause_job"><input type="hidden" name="job_id" value="' . esc_attr((string) $job_id) . '"><input type="hidden" name="return_to" value="' . esc_attr($return_url) . '">';
        wp_nonce_field('boblift_mls_pause_job_' . $job_id);
        submit_button('暂停任务', 'secondary', 'submit', false);
        echo '</form>';
    } elseif ((string) $job['status'] === 'paused') {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="boblift_mls_resume_job"><input type="hidden" name="job_id" value="' . esc_attr((string) $job_id) . '"><input type="hidden" name="return_to" value="' . esc_attr($return_url) . '">';
        wp_nonce_field('boblift_mls_resume_job_' . $job_id);
        submit_button('继续执行', 'primary', 'submit', false);
        echo '</form>';
    }
    echo '<span class="description">任务执行只在后台进度页推进，不再挂到前台访问。</span>';
    echo '</div>';
    echo '<div style="height:16px;background:#f0f0f1;border-radius:999px;overflow:hidden;"><div class="boblift-mls-job-bar" style="height:100%;width:0;background:#2271b1;"></div></div>';
    echo '<p class="boblift-mls-job-summary">准备读取任务状态。</p>';
    echo '<p class="boblift-mls-job-eta description"></p>';
    echo '<p><strong>当前处理：</strong><span class="boblift-mls-current">等待开始</span></p>';
    echo '<h3>任务日志</h3><ol class="boblift-mls-logs" style="max-height:260px;overflow:auto;margin-left:20px;"></ol>';
    echo '<div class="boblift-mls-failed"></div>';
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="boblift-mls-retry" style="display:none;margin-top:14px;">';
    echo '<input type="hidden" name="action" value="boblift_mls_retry_job"><input type="hidden" name="job_id" value="' . esc_attr((string) $job_id) . '"><input type="hidden" name="return_to" value="' . esc_attr($return_url) . '">';
    wp_nonce_field('boblift_mls_retry_job_' . $job_id);
    submit_button('重试失败项', 'secondary', 'submit', false);
    echo '</form></div>';
    boblift_mls_render_job_page_script();
}

function boblift_mls_render_job_page_script(): void
{
    static $rendered = false;
    if ($rendered) {
        return;
    }
    $rendered = true;
    echo <<<'HTML'
<script>
(function () {
  var root = document.querySelector('.boblift-mls-job');
  if (!root) return;
  var jobId = root.dataset.jobId;
  var nonce = root.dataset.nonce;
  var bar = root.querySelector('.boblift-mls-job-bar');
  var summary = root.querySelector('.boblift-mls-job-summary');
  var eta = root.querySelector('.boblift-mls-job-eta');
  var current = root.querySelector('.boblift-mls-current');
  var logs = root.querySelector('.boblift-mls-logs');
  var failed = root.querySelector('.boblift-mls-failed');
  var retry = root.querySelector('.boblift-mls-retry');
  var terminal = false;

  function text(value) { return value == null ? '' : String(value); }
  function formatSeconds(seconds) {
    seconds = Number(seconds || 0);
    if (seconds <= 0) return '暂无法估算';
    var minutes = Math.ceil(seconds / 60);
    return minutes < 60 ? minutes + ' 分钟内' : Math.ceil(minutes / 60) + ' 小时内';
  }
  function render(state) {
    if (!state || state.missing) return;
    var total = Number(state.total_items || 0);
    var done = Number(state.completed_items || 0) + Number(state.skipped_items || 0) + Number(state.failed_items || 0);
    var percent = total > 0 ? Math.round(done * 100 / total) : 100;
    bar.style.width = percent + '%';
    summary.textContent = '类型：' + text(state.job_type_label) + '；状态：' + text(state.status) + '；进度：' + done + ' / ' + total + '；成功 ' + text(state.completed_items) + '，跳过 ' + text(state.skipped_items) + '，失败 ' + text(state.failed_items) + '。';
    if (eta) eta.textContent = '预计剩余：' + formatSeconds(state.estimated_remaining_seconds);
    current.textContent = text(state.current_label || '等待下一个项目');
    logs.innerHTML = '';
    (state.logs || []).forEach(function (item) {
      var li = document.createElement('li');
      li.textContent = text(item.time) + '  ' + text(item.message);
      logs.appendChild(li);
    });
    failed.innerHTML = '';
    if ((state.failed || []).length) {
      var title = document.createElement('h3');
      title.textContent = '失败项';
      failed.appendChild(title);
      var list = document.createElement('ul');
      (state.failed || []).forEach(function (item) {
        var li = document.createElement('li');
        li.textContent = text(item.label) + '：' + text(item.error);
        list.appendChild(li);
      });
      failed.appendChild(list);
      retry.style.display = 'block';
    }
    terminal = ['completed', 'completed_with_errors', 'failed'].indexOf(String(state.status)) !== -1;
  }

  if (window.EventSource && root.dataset.eventsUrl) {
    var source = new EventSource(root.dataset.eventsUrl);
    source.addEventListener('state', function (event) {
      render(JSON.parse(event.data));
      if (terminal) source.close();
    });
  }
}());
</script>
HTML;
}

function boblift_mls_admin_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $languages = boblift_mls_languages();
    $source = boblift_mls_source_language();
    $sections = boblift_mls_settings()['sections'] ?? boblift_mls_default_sections();
    echo '<div class="wrap"><h1>BOB-LIFT 多语言同步</h1>';
    $job_id = isset($_GET['job_id']) ? absint($_GET['job_id']) : 0;
    if ($job_id > 0) {
        boblift_mls_render_job_page($job_id);
        echo '</div>';
        return;
    }
    echo '<p>这个页面只用于新增语言流程：初始化复制、翻译文本、人工发布。不要使用顶部 Polylang 语言筛选；请在“新增语言向导”里选择目标语言。</p>';
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="margin:16px 0 24px;padding:16px;background:#fff;border:1px solid #c3c4c7;max-width:720px;">';
    echo '<input type="hidden" name="action" value="boblift_mls_save_settings">';
    wp_nonce_field('boblift_mls_save_settings');
    echo '<label><strong>主维护语言</strong> <select name="source_language">';
    foreach ($languages as $language) {
        echo '<option value="' . esc_attr($language) . '" ' . selected($source, $language, false) . '>' . esc_html($language) . '</option>';
    }
    echo '</select></label> ';
    submit_button('保存设置', 'secondary', 'submit', false);
    echo '</form>';
    boblift_mls_render_language_wizard($languages, $source);
    boblift_mls_render_jobs_overview();
    boblift_mls_render_coverage_overview($languages, $source);
    echo '<p class="description">已有语言默认保护人工内容；需要补洞时使用下面的高级修复工具。</p>';
    echo '<details style="margin-top:28px;"><summary style="cursor:pointer;font-size:1.2em;font-weight:600;">高级：修复缺失项</summary>';
    echo '<p class="description">这里只用于修复单个配置区块或旧语言缺失项，不作为新增语言主流程。所有操作都会创建后台任务。</p>';
    echo '<table class="widefat striped"><thead><tr><th>目标语言</th><th>配置区块</th><th>前台位置</th><th>状态</th><th>操作</th></tr></thead><tbody>';
    foreach ($languages as $language) {
        if ($language === $source) {
            continue;
        }
        foreach ($sections as $section) {
            echo '<tr><td>' . esc_html($language) . '</td><td><strong>' . esc_html(boblift_mls_section_label((string) $section)) . '</strong><br><code>' . esc_html((string) $section) . '</code></td><td>' . esc_html(boblift_mls_section_note((string) $section)) . '</td><td>' . esc_html(boblift_mls_status_label($language, $section)) . '</td><td>';
            boblift_mls_render_sync_form((string) $section, $language, 'translate', '修复并翻译', 0, !boblift_mls_translation_available());
            if ((string) $section !== 'seo_meta') {
                boblift_mls_render_sync_form((string) $section, $language, 'params', '只同步参数', 0);
            } else {
                echo '<span class="description">SEO 无参数模式</span>';
            }
            echo '</td></tr>';
        }
    }
    echo '</tbody></table>';
    echo '</details>';
    echo '</div>';
}
