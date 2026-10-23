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
    // Short labels (two to four characters) keep the label–value pairs in DingTalk evenly spaced; the email has the long ones.
    $labels = [
        'name' => '姓名', 'email' => '邮箱', 'phone' => '电话', 'company' => '公司', 'website' => '网站', 'country' => '国家',
        'product' => '产品', 'legacy_models' => '老型号', 'quantity' => '数量', 'truck_available' => '有无卡车', 'truck_model' => '卡车型号',
        'truck_specs' => '底盘参数', 'imported_from_china' => '进口经验', 'customs_clearance' => '清关', 'destination_port' => '目的港',
        'preferred_delivery' => '期望交期', 'contact_method' => '联系方式', 'preferred_contact_time' => '联系时间', 'other_requirements' => '其他要求',
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
        // The referring site is shown in 来源渠道; the plugin's ad lines are only for ad clicks and campaign tags.
        if ($name !== 'referrer' && ($fields['attr_' . $name] ?? '') !== '') {
            $attribution[$name] = (string) $fields['attr_' . $name];
        }
    }
    // Where the inquiry came from first (the page's name and its address without ad parameters, then the button), then the
    // channel and device, the ad source, the country and the time.
    $source = function_exists('boblift_inquiry_source_page') ? boblift_inquiry_source_page($fields) : ['url' => (string) ($fields['source_page'] ?? ''), 'title' => '', 'params' => []];
    $meta = [];
    if ($source['title'] !== '') {
        $meta[] = ['label' => '来源页面', 'icon' => '📄', 'value' => $source['title']];
    }
    if ($source['url'] !== '') {
        $meta[] = ['label' => '页面地址', 'icon' => '🔗', 'value' => $source['url'], 'link' => true];
    }
    if (($fields['source_entry'] ?? '') !== '') {
        $meta[] = ['label' => '入口', 'icon' => '🔘', 'value' => (string) $fields['source_entry']];
    }
    // How the visitor came (Google Ads, natural search, another site, direct) and on what device, for every inquiry.
    if (function_exists('boblift_inquiry_channel')) {
        $meta[] = ['label' => '来源渠道', 'icon' => '🚦', 'value' => implode(' · ', array_filter(boblift_inquiry_channel($fields), 'strlen'))];
    }
    if (($fields['device'] ?? '') !== '') {
        $meta[] = ['label' => '访问设备', 'icon' => str_starts_with((string) $fields['device'], '电脑') ? '💻' : '📱', 'value' => (string) $fields['device']];
    }
    $ad_meta = function_exists('achord_dingtalk_cf7_attribution_meta') ? achord_dingtalk_cf7_attribution_meta($attribution) : [];
    if ($ad_meta) {
        $meta = array_merge($meta, $ad_meta);
    } elseif ($source['params']) {
        // Without ad consent there is no attribution, but the address may still carry utm tags worth seeing.
        $pairs = [];
        foreach ($source['params'] as $name => $value) {
            $pairs[] = $name . '=' . $value;
        }
        $meta[] = ['label' => '页面参数', 'icon' => '🏷️', 'value' => implode(' · ', $pairs)];
    }
    if (get_post_meta($post_id, '_mail_sent', true) === '0') {
        $meta[] = ['label' => '注意', 'icon' => '⚠️', 'value' => '通知邮件没有发出去，请到后台查看这条询盘'];
    }
    $country = function_exists('boblift_inquiry_ip_country') ? boblift_inquiry_ip_country(boblift_inquiry_client_ip()) : '';
    if ($country !== '') {
        $meta[] = ['label' => '来源国家', 'icon' => '🌍', 'value' => $country];
    }
    $meta[] = ['label' => '时间', 'icon' => '🕒', 'value' => wp_date('Y-m-d H:i')];

    $links = [];
    if (is_email((string) ($fields['email'] ?? ''))) {
        $links[] = ['label' => '✉️ 回复邮件', 'url' => 'mailto:' . $fields['email']];
    }
    $links[] = ['label' => '📋 后台查看', 'url' => admin_url('post.php?post=' . $post_id . '&action=edit')];

    achord_dingtalk_dispatch([
        // Its own title, so the group can tell this site's inquiries from the loader site's at a glance.
        'title' => '🏗️ 起重机站新询盘',
        'headline' => trim((string) ($fields['product'] ?? '')),
        'fields' => $rows,
        'meta' => $meta,
        'links' => $links,
    ], 'boblift:inquiry');
}
add_action('boblift_inquiry_submitted', 'boblift_dingtalk_inquiry', 10, 2);
