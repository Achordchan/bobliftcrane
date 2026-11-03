<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hands the inquiry form to the BOB-LIFT inquiry plugin (achord-wp-inquiry, shared with the loader site) once it is
 * active: the plugin then takes the form's submissions, keeps them under 询盘, sends the classic card email and the
 * DingTalk message, and checks Turnstile, the blocklist and how often one network sends. The theme still draws the forms
 * and the catalog request, and tells the plugin what only it knows: the translated messages, the extra fields, the
 * product cards in the mail, discontinued models, the catalog request's words.
 *
 * The plugin keeps this site's posted names (prefix boblift_), post type (inquiry), REST route (/wp/v2/inquiries) and
 * record (meta.boblift_inquiry), so cached pages, saved inquiries and the connector keep working. Until 设置 → 询盘设置
 * is saved, the plugin reads Turnstile, the blocklist, IP storage and the recipient from 表单安全 and 联系方式, as before.
 *
 * Without the plugin nothing changes: the theme's own handler, mail, guard and DingTalk bridge keep doing the work.
 */

/** Whether the inquiry plugin is active and this theme has handed its inquiries to it. */
function boblift_inquiry_plugin(): bool
{
    return function_exists('achord_inquiry_active') && function_exists('achord_inquiry_handle') && achord_inquiry_active();
}

add_action('after_setup_theme', static function (): void {
    if (!function_exists('achord_inquiry_handle')) {
        return;
    }
    add_theme_support('achord-inquiry', [
        'site_label' => '起重机站',
        'dingtalk_emoji' => '🏗️',
        // The DingTalk robots' form filter already names this source.
        'dingtalk_source' => 'boblift:inquiry',
        'prefix' => 'boblift_',
        'post_type' => 'inquiry',
        'rest_base' => 'inquiries',
        'rest_record' => 'boblift_inquiry',
        'required' => ['email', 'message'],
        'attribution_key' => 'boblift_attribution',
    ]);
});

// Addresses that follow the visitor's language, worked out when they are asked for (after the posted language is set).
add_filter('achord_inquiry_config', static function (array $config): array {
    if (!current_theme_supports('achord-inquiry')) {
        return $config;
    }
    $config['success_url'] = boblift_inquiry_success_url();
    $config['fallback_url'] = function_exists('boblift_url') ? boblift_url('/contact.html') : home_url('/contact.html');
    $logo = function_exists('boblift_asset') ? boblift_asset('images/bob-lift-logo.png') : '';
    if (str_starts_with($logo, '//')) {
        $logo = 'https:' . $logo;
    } elseif (str_starts_with($logo, '/')) {
        $logo = untrailingslashit(home_url()) . $logo;
    }
    $config['logo_url'] = $logo;
    return $config;
});

// assets/js/theme.js already fills in the source fields (boblift_attr_*, boblift_page_referrer …) after the cookie banner.
add_action('wp_enqueue_scripts', static fn() => wp_dequeue_script('achord-inquiry'), 20);

// Messages in the page's language: Polylang switches to the language the form was sent in, as the theme's handler did.
add_action('achord_inquiry_before_handle', 'boblift_switch_to_posted_language');
add_filter('achord_inquiry_message', static function (string $message, string $key): string {
    $ui = function_exists('boblift_site_ui') ? boblift_site_ui('quoteForm') : [];
    $text = $key === 'success' ? ($ui['successInlineText'] ?? '') : ($ui['messages'][$key] ?? '');
    return is_string($text) && trim($text) !== '' ? $text : $message;
}, 10, 2);

/** The quote form's extra questions, with the labels the sales email and DingTalk used. */
const BOBLIFT_INQUIRY_PLUGIN_EXTRA_FIELDS = [
    'truck_available' => ['label' => '是否有卡车', 'short' => '有无卡车'],
    'truck_model' => ['label' => '卡车型号 / 底盘品牌', 'short' => '卡车型号'],
    'truck_specs' => ['label' => '底盘参数', 'short' => '底盘参数', 'long' => true],
    'imported_from_china' => ['label' => '是否从中国进口过', 'short' => '进口经验'],
    'customs_clearance' => ['label' => '清关', 'short' => '清关'],
    'destination_port' => ['label' => '目的港 / 城市', 'short' => '目的港'],
    'preferred_delivery' => ['label' => '期望交期', 'short' => '期望交期'],
    'contact_method' => ['label' => '联系方式偏好', 'short' => '联系方式'],
    'preferred_contact_time' => ['label' => '方便联系时间', 'short' => '联系时间'],
    'other_requirements' => ['label' => '其他要求', 'short' => '其他要求', 'long' => true],
];

add_filter('achord_inquiry_fields', static function (array $fields): array {
    foreach (BOBLIFT_INQUIRY_PLUGIN_EXTRA_FIELDS as $name => $field) {
        $fields[$name] = $field + ['admin' => $field['label'], 'type' => 'textarea'];
    }
    return $fields;
});

// Discontinued models among the chosen products, so the sales team knows they are built to order.
add_filter('achord_inquiry_submission_fields', static function (array $fields): array {
    $fields['legacy_models'] = function_exists('boblift_inquiry_legacy_models') ? boblift_inquiry_legacy_models((string) ($fields['product_slug'] ?? '')) : '';
    return $fields;
});
add_filter('achord_inquiry_mail_rows', static function (array $rows, array $fields): array {
    $legacy = trim((string) ($fields['legacy_models'] ?? ''));
    if ($legacy !== '') {
        $rows[] = ['老型号', esc_html($legacy) . '（需定制生产）'];
    }
    return $rows;
}, 10, 2);
add_filter('achord_inquiry_dingtalk_rows', static function (array $rows, array $fields): array {
    $legacy = trim((string) ($fields['legacy_models'] ?? ''));
    if ($legacy !== '') {
        // After the product, as the theme's message had it.
        $at = 0;
        foreach ($rows as $index => $row) {
            if (($row['label'] ?? '') === '产品') {
                $at = $index + 1;
            }
        }
        array_splice($rows, $at, 0, [['label' => '老型号', 'value' => $legacy, 'long' => false]]);
    }
    if (($fields['lead_type'] ?? '') === 'catalog_request') {
        $url = trim((string) ($fields['catalog_url'] ?? ''));
        $rows[] = ['label' => '目录文件', 'value' => $url !== '' ? $url : '没有现成 PDF，请手动发给客户', 'long' => true];
    }
    return $rows;
}, 10, 2);
add_filter('achord_inquiry_product_column', static function (string $html, int $post_id): string {
    $legacy = (string) get_post_meta($post_id, '_legacy_models', true);
    return ($legacy !== '' ? '<span style="display:inline-block;margin:0 0 4px;padding:1px 6px;border:1px solid #dc2626;border-radius:2px;color:#b91c1c;font-weight:600">老型号 ' . esc_html($legacy) . '</span><br>' : '') . $html;
}, 10, 2);
add_filter('achord_inquiry_admin_labels', static fn(array $labels): array => $labels + ['legacy_models' => '老型号', 'catalog_url' => '目录文件']);

// The product cards (photo, model, type, link) under 产品信息, or the catalog asked for.
add_filter('achord_inquiry_mail_products_html', static function (string $html, array $fields): string {
    // The form is handled on init priority 0, before the theme registers products and their categories.
    if (!taxonomy_exists('product_category') && function_exists('boblift_register_taxonomies')) {
        boblift_register_content_types();
        boblift_register_taxonomies();
    }
    if (($fields['lead_type'] ?? '') === 'catalog_request') {
        return boblift_inquiry_mail_catalog_card($fields);
    }
    $products = boblift_inquiry_mail_products($fields);
    return $products ? implode('', array_map('boblift_inquiry_mail_product_card', $products)) : $html;
}, 10, 2);

// The PDF catalog request (inc/forms.php) is the same card with its own words.
add_filter('achord_inquiry_lead_types', static fn(array $types): array => $types + ['catalog_request' => [
    'headline' => '新目录下载请求',
    'intro' => '起重机站有客户留邮箱索取 PDF 目录，内容如下，请及时跟进。',
    'subject' => '【起重机站】客户索取目录，请及时跟进：',
    'kind' => '目录下载',
    'section' => '索取的目录',
    'form_name' => '目录下载表单',
    'dingtalk_title' => '📘 起重机站目录下载',
    'show_message' => false,
    'title' => 'Catalog request from %s - %s',
]]);

// A subject an editor saved in 站点文案 (English options only, as the theme's mail read it) still wins over the default.
add_filter('achord_inquiry_mail_subject', static function (string $subject, array $fields): string {
    $catalog = ($fields['lead_type'] ?? '') === 'catalog_request';
    $options = get_option('boblift_theme_options', []);
    $saved = trim((string) (is_array($options) ? ($catalog ? ($options['site_ui']['productDetail']['catalogLead']['adminMailSubject'] ?? '') : ($options['site_ui']['quoteForm']['messages']['mailSubject'] ?? '')) : ''));
    return $saved !== '' && $saved !== ($catalog ? 'New BOB-LIFT catalog request' : 'New BOB-LIFT inquiry') ? $saved : $subject;
}, 10, 2);

// A trusted-proxy setup hooked on boblift_inquiry_client_ip keeps resolving the visitor's address under the plugin, for
// plugin submissions and catalog requests alike (both go through achord_inquiry_client_ip).
add_filter('achord_inquiry_client_ip', static fn(string $ip, string $peer = ''): string => (string) apply_filters('boblift_inquiry_client_ip', $ip, $peer), 10, 2);

// Until 询盘设置 names an address: 联系方式 → email, else info@boblift.com, as before.
add_filter('achord_inquiry_recipient', static fn(): string => boblift_inquiry_recipient());

// Until 询盘设置 is saved: the Turnstile keys, blocklist and IP storage of 表单安全.
add_filter('achord_inquiry_default_options', static function (array $defaults): array {
    $options = function_exists('boblift_form_security_options') ? boblift_form_security_options() : [];
    foreach (['turnstile_enabled' => 'turnstile_enabled', 'turnstile_local_mode' => 'turnstile_local_mode', 'turnstile_site_key' => 'turnstile_site_key', 'turnstile_secret_key' => 'turnstile_secret_key', 'blocklist' => 'inquiry_blocklist', 'store_ip' => 'inquiry_store_ip'] as $key => $theme_key) {
        if (isset($options[$theme_key]) && is_scalar($options[$theme_key])) {
            $defaults[$key] = (string) $options[$theme_key];
        }
    }
    return $defaults;
});

// The theme's own inquiry admin (list columns, details box, read-only REST record) steps aside for the plugin's.
add_action('achord_inquiry_booted', static function (): void {
    remove_filter('use_block_editor_for_post_type', 'boblift_inquiry_use_classic_editor', 10);
    remove_action('admin_head', 'boblift_inquiry_admin_head');
    remove_filter('manage_inquiry_posts_columns', 'boblift_inquiry_columns');
    remove_action('manage_inquiry_posts_custom_column', 'boblift_inquiry_column_content', 10);
    remove_action('boblift_inquiry_submitted', 'boblift_dingtalk_inquiry', 10);
});
