<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sends each saved inquiry to the DingTalk group through the achord-wp-dingtalk plugin (the one the loader site uses),
 * when that plugin is active. The plugin reads Contact Form 7 by itself; this site's own form hands it the same message
 * structure through achord_dingtalk_dispatch(), with source "boblift:inquiry". The robots, keywords and @mentions are
 * set in the plugin (设置 → 钉钉推送). As in the sales email, the country and the visitor's IP address are shown; the raw ad
 * tracking values are left to the email's foot.
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

    // Where the inquiry came from first (the page's name and its address without ad parameters, then the button), then the
    // channel (which already says which ad), the device, the landing page when it was another page, the country, IP and time.
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
    $landing = (string) ($fields['attr_landing'] ?? '');
    // The page address keeps the parameters that pick a page (?page_id=); only a trailing slash and a # are ignored.
    $page_key = static fn(string $url): string => (string) preg_replace('#/(?=\?|$)#', '', (string) preg_replace('/#.*$/s', '', $url), 1);
    if ($landing !== '' && $page_key($landing) !== $page_key($source['url'])) {
        $meta[] = ['label' => '落地页', 'icon' => '🧭', 'value' => $landing, 'link' => true];
    }
    if (get_post_meta($post_id, '_mail_sent', true) === '0') {
        $meta[] = ['label' => '注意', 'icon' => '⚠️', 'value' => '通知邮件没有发出去，请到后台查看这条询盘'];
    }
    $ip = function_exists('boblift_inquiry_client_ip') ? boblift_inquiry_client_ip() : '';
    $country = $ip !== '' && function_exists('boblift_inquiry_ip_country') ? boblift_inquiry_ip_country($ip) : '';
    if ($country !== '') {
        $meta[] = ['label' => '来源国家', 'icon' => '🌍', 'value' => $country];
    }
    if ($ip !== '') {
        $meta[] = ['label' => 'IP 地址', 'icon' => '🖥️', 'value' => $ip];
    }
    $meta[] = ['label' => '时间', 'icon' => '🕒', 'value' => wp_date('Y-m-d H:i')];

    $links = [];
    if (is_email((string) ($fields['email'] ?? ''))) {
        $links[] = ['label' => '✉️ 回复邮件', 'url' => 'mailto:' . $fields['email']];
    }
    $links[] = ['label' => '📋 后台查看', 'url' => admin_url('post.php?post=' . $post_id . '&action=edit')];

    // A PDF catalog request (inc/forms.php) is told apart by its title, with the PDF it asked for, or a note that the team
    // has to send one.
    $catalog = ($fields['lead_type'] ?? '') === 'catalog_request';
    if ($catalog) {
        $rows = array_values(array_filter($rows, static fn(array $row): bool => $row['label'] !== '留言'));
        $url = trim((string) ($fields['catalog_url'] ?? ''));
        $rows[] = $url !== '' ? ['label' => '目录文件', 'value' => $url, 'long' => true] : ['label' => '目录文件', 'value' => '没有现成 PDF，请手动发给客户', 'long' => true];
    }

    achord_dingtalk_dispatch([
        // Its own title, so the group can tell this site's inquiries from the loader site's at a glance.
        'title' => $catalog ? '📘 起重机站目录下载' : '🏗️ 起重机站新询盘',
        'headline' => trim((string) ($fields['product'] ?? '')),
        'fields' => $rows,
        'meta' => $meta,
        'links' => $links,
    ], 'boblift:inquiry');
}
add_action('boblift_inquiry_submitted', 'boblift_dingtalk_inquiry', 10, 2);
