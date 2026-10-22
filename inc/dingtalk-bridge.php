<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sends each saved inquiry to the DingTalk group through the achord-wp-dingtalk plugin (the one the loader site uses),
 * when that plugin is active. The plugin reads Contact Form 7 by itself; this site's own form hands it the same message
 * structure through achord_dingtalk_dispatch(), with source "boblift:inquiry". The robots, keywords and @mentions are
 * set in the plugin (设置 → 钉钉推送). As in the sales email, the visitor's raw IP is left out: only the country is shown.
 */
function boblift_dingtalk_inquiry(int $post_id, array $fields): void
{
    if ($post_id <= 0 || !function_exists('achord_dingtalk_dispatch')) {
        return;
    }
    $labels = [
        'name' => '姓名', 'email' => '邮箱', 'phone' => '电话 / WhatsApp', 'company' => '公司', 'website' => '网站', 'country' => '国家（客户填写）',
        'product' => '产品', 'legacy_models' => '老型号（需定制生产）', 'quantity' => '数量', 'truck_available' => '是否有卡车', 'truck_model' => '卡车型号 / 底盘品牌',
        'truck_specs' => '底盘参数', 'imported_from_china' => '是否从中国进口过', 'customs_clearance' => '清关', 'destination_port' => '目的港 / 城市',
        'preferred_delivery' => '期望交期', 'contact_method' => '联系方式偏好', 'preferred_contact_time' => '方便联系时间', 'other_requirements' => '其他要求',
        'message' => '留言',
    ];
    $rows = [];
    foreach ($labels as $key => $label) {
        $value = trim((string) ($fields[$key] ?? ''));
        if ($value !== '') {
            $rows[] = ['label' => $label, 'value' => $value, 'long' => in_array($key, ['message', 'other_requirements', 'truck_specs'], true)];
        }
    }

    $attribution = [];
    foreach (array_keys(defined('BOBLIFT_INQUIRY_ATTRIBUTION') ? BOBLIFT_INQUIRY_ATTRIBUTION : []) as $name) {
        if (($fields['attr_' . $name] ?? '') !== '') {
            $attribution[$name] = (string) $fields['attr_' . $name];
        }
    }
    $meta = function_exists('achord_dingtalk_cf7_attribution_meta') ? achord_dingtalk_cf7_attribution_meta($attribution) : [];
    if (get_post_meta($post_id, '_mail_sent', true) === '0') {
        $meta[] = ['label' => '注意', 'icon' => '⚠️', 'value' => '通知邮件没有发出去，请到后台查看这条询盘'];
    }
    $country = function_exists('boblift_inquiry_ip_country') ? boblift_inquiry_ip_country(boblift_inquiry_client_ip()) : '';
    if ($country !== '') {
        $meta[] = ['label' => '来源国家', 'icon' => '🌍', 'value' => $country];
    }
    if (($fields['source_entry'] ?? '') !== '') {
        $meta[] = ['label' => '入口', 'icon' => '🔘', 'value' => (string) $fields['source_entry']];
    }
    if (($fields['source_page'] ?? '') !== '') {
        $meta[] = ['label' => '来源页', 'icon' => '🔗', 'value' => (string) $fields['source_page'], 'link' => true];
    }
    $meta[] = ['label' => '时间', 'icon' => '🕒', 'value' => wp_date('Y-m-d H:i') . '　起重机站询盘表单'];

    $links = [];
    if (is_email((string) ($fields['email'] ?? ''))) {
        $links[] = ['label' => '✉️ 回复邮件', 'url' => 'mailto:' . $fields['email']];
    }
    $links[] = ['label' => '📋 后台查看', 'url' => admin_url('post.php?post=' . $post_id . '&action=edit')];

    achord_dingtalk_dispatch([
        'title' => '📩 新询盘',
        'headline' => trim((string) ($fields['product'] ?? '')),
        'fields' => $rows,
        'meta' => $meta,
        'links' => $links,
    ], 'boblift:inquiry');
}
add_action('boblift_inquiry_submitted', 'boblift_dingtalk_inquiry', 10, 2);
