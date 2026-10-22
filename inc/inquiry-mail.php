<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The two emails an inquiry sends: the notification the sales team receives (the loader site's design C "nameplate"
 * card, so the three BOB-LIFT sites look the same in the inbox) and the confirmation the customer receives in the
 * language of the page they wrote from. Both are HTML tables with inline styles, the only layout mail programs agree on.
 */

const BOBLIFT_INQUIRY_DEFAULT_RECIPIENT = 'info@boblift.com';

/** Where inquiry notifications go: the sales email in 联系方式 (English options), else info@boblift.com. Never admin_email. */
function boblift_inquiry_recipient(): string
{
    $contact = get_option('boblift_theme_options', []);
    $email = is_array($contact) ? sanitize_email((string) ($contact['contact']['email'] ?? '')) : '';
    return (string) apply_filters('boblift_inquiry_recipient', is_email($email) ? $email : BOBLIFT_INQUIRY_DEFAULT_RECIPIENT);
}

/** Who both emails come from: the site title, at the site's own domain so receiving servers accept it. */
function boblift_inquiry_mail_from_header(): string
{
    $host = preg_replace('/^www\./i', '', (string) wp_parse_url(home_url(), PHP_URL_HOST));
    $name = trim(wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES));
    $name = $name === '' || stripos($name, 'zealous') !== false ? 'BOB-LIFT' : $name;
    return 'From: ' . str_replace(['"', "\r", "\n"], '', $name) . ' <wordpress@' . $host . '>';
}

/** The logo as an absolute address: mail programs have no page to resolve a relative one against. */
function boblift_inquiry_mail_logo(): string
{
    $src = boblift_asset('images/bob-lift-logo.png');
    if (str_starts_with($src, '//')) {
        $src = 'https:' . $src;
    } elseif (str_starts_with($src, '/')) {
        $src = untrailingslashit(home_url()) . $src;
    }
    return '<img src="' . esc_url($src) . '" width="120" alt="BOB-LIFT" style="display:block;width:120px;height:auto;border:0;">';
}

/** The country the sender's IP address points to, in Chinese (e.g. 加拿大); empty when it cannot be told. */
function boblift_inquiry_ip_country(string $ip): string
{
    $code = function_exists('boblift_country_code_from_request') ? boblift_country_code_from_request() : '';
    if ($code === '' && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        $key = 'boblift_ip_country_' . md5($ip);
        $code = get_transient($key);
        if ($code === false) {
            $response = wp_remote_get('https://ipinfo.io/' . rawurlencode($ip) . '/country', ['timeout' => 3]);
            $text = strtoupper(trim((string) wp_remote_retrieve_body($response)));
            $code = !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200 && preg_match('/^[A-Z]{2}$/', $text) ? $text : '';
            set_transient($key, $code, $code !== '' ? WEEK_IN_SECONDS : HOUR_IN_SECONDS);
        }
    }
    if ($code === '') {
        return '';
    }
    static $names = null;
    $names ??= require __DIR__ . '/country-names-zh.php';
    return $names[$code] ?? $code;
}

/** The products named in the inquiry: model, type, photo and link of each chosen product, else the typed product name. */
function boblift_inquiry_mail_products(array $fields): array
{
    $products = [];
    foreach (array_filter(array_map('trim', explode(',', (string) ($fields['product_slug'] ?? '')))) as $slug) {
        $post = get_page_by_path(sanitize_title($slug), OBJECT, 'product');
        if (!$post instanceof WP_Post) {
            continue;
        }
        $english_id = function_exists('boblift_product_english_id') ? boblift_product_english_id($post->ID) : $post->ID;
        $title = get_the_title($english_id);
        $model = function_exists('boblift_clean_product_model') ? boblift_clean_product_model((string) get_post_meta($english_id, '_model', true), $title) : (string) get_post_meta($english_id, '_model', true);
        // 'lang' => '' keeps Polylang from limiting the terms to the language of the page the inquiry came from.
        $terms = wp_get_object_terms($english_id, 'product_category', ['fields' => 'names', 'lang' => '']);
        $products[] = [
            'title' => $title,
            'model' => $model,
            'type' => is_array($terms) && $terms ? (string) $terms[0] : '',
            'url' => (string) get_permalink($english_id),
            'image' => (string) (get_the_post_thumbnail_url($english_id, 'thumbnail') ?: ''),
        ];
    }
    if (!$products && trim((string) ($fields['product'] ?? '')) !== '') {
        $products[] = ['title' => trim((string) $fields['product']), 'model' => '', 'type' => '', 'url' => '', 'image' => ''];
    }
    return $products;
}

function boblift_inquiry_mail_product_card(array $product): string
{
    $tag = static fn(string $label, string $text): string => '<span style="display:inline-block;margin:0 6px 6px 0;padding:4px 10px;border-radius:4px;background:#f1f4f9;font-size:13px;color:#334155;white-space:nowrap;"><span style="color:#64748b;">' . $label . '</span> ' . esc_html($text) . '</span>';
    $tags = ($product['model'] !== '' ? $tag('型号', $product['model']) : '') . $tag('类型', $product['type'] !== '' ? $product['type'] : '—');
    $name = $product['url'] !== ''
        ? '<a href="' . esc_url($product['url']) . '" target="_blank" style="display:block;padding-bottom:10px;font-size:17px;line-height:1.45;font-weight:bold;color:#0b1f33;text-decoration:none;">' . esc_html($product['title']) . '</a>'
        : '<div style="padding-bottom:10px;font-size:17px;line-height:1.45;font-weight:bold;color:#0b1f33;">' . esc_html($product['title']) . '</div>';
    $photo = $product['image'] !== '' ? '<td width="96" style="width:96px;padding:16px 0 16px 16px;vertical-align:top;"><img src="' . esc_url($product['image']) . '" width="96" height="96" alt="' . esc_attr($product['title']) . '" style="display:block;width:96px;height:96px;border-radius:4px;border:1px solid #edf0f4;object-fit:cover;"></td>' : '';
    return '<table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:10px;border:1px solid #e3e8ef;border-radius:4px;border-collapse:separate;"><tr>' . $photo . '<td style="padding:16px 18px 10px;vertical-align:top;">' . $name . $tags . '</td></tr></table>';
}

/** The notification the sales team receives, as [subject, html body]. */
function boblift_inquiry_admin_mail(array $fields, int $post_id): array
{
    $font = "font-family:-apple-system,BlinkMacSystemFont,'PingFang SC','Microsoft YaHei','Helvetica Neue',Arial,sans-serif;";
    $heading = static fn(string $text): string => '<tr><td style="padding:28px 36px 12px;font-size:13px;font-weight:bold;letter-spacing:1px;color:#64748b;">' . $text . '</td></tr>';
    $row = static fn(string $label, string $value): string => '<tr><td width="130" style="width:130px;padding:13px 12px 13px 0;border-bottom:1px solid #edf0f4;font-size:14px;color:#64748b;vertical-align:top;">' . $label . '</td><td style="padding:13px 0;border-bottom:1px solid #edf0f4;font-size:16px;color:#0b1f33;line-height:1.6;vertical-align:top;word-break:break-word;">' . $value . '</td></tr>';
    $meta = static fn(string $label, string $value): string => '<td width="33%" style="width:33%;padding:16px 18px;vertical-align:top;"><div style="font-size:12px;color:#64748b;padding-bottom:4px;">' . $label . '</div><div style="font-size:15px;font-weight:bold;color:#0b1f33;line-height:1.4;word-break:break-word;">' . $value . '</div></td>';

    $email = (string) $fields['email'];
    $mailto = '<a href="mailto:' . esc_attr($email) . '" style="color:#215bdb;text-decoration:none;">' . esc_html($email) . '</a>';
    $labels = [
        'name' => '姓名', 'email' => '邮箱', 'phone' => '电话 / WhatsApp', 'company' => '公司', 'website' => '网站', 'country' => '国家（客户填写）', 'quantity' => '数量',
        'truck_available' => '是否有卡车', 'truck_model' => '卡车型号 / 底盘品牌', 'truck_specs' => '底盘参数', 'imported_from_china' => '是否从中国进口过',
        'customs_clearance' => '清关', 'destination_port' => '目的港 / 城市', 'preferred_delivery' => '期望交期', 'contact_method' => '联系方式偏好',
        'preferred_contact_time' => '方便联系时间', 'other_requirements' => '其他要求',
    ];
    $rows = [];
    foreach ($labels as $key => $label) {
        if ($key === 'email') {
            $rows[] = $row($label, $mailto);
            continue;
        }
        $value = trim((string) ($fields[$key] ?? ''));
        if ($value !== '') {
            $rows[] = $row($label, $key === 'website' ? '<a href="' . esc_url(preg_match('#^https?://#i', $value) ? $value : 'https://' . $value) . '" style="color:#215bdb;text-decoration:none;">' . esc_html($value) . '</a>' : nl2br(esc_html($value), false));
        }
    }
    if (trim((string) ($fields['legacy_models'] ?? '')) !== '') {
        $rows[] = $row('老型号', esc_html($fields['legacy_models']) . '（需定制生产）');
    }

    $products = boblift_inquiry_mail_products($fields);
    $product_html = $products ? implode('', array_map('boblift_inquiry_mail_product_card', $products)) : '<div style="padding:14px 18px;border:1px solid #e3e8ef;border-radius:4px;font-size:15px;color:#64748b;">未选择产品</div>';
    $kind = $products ? '产品询盘' : '询盘表单';
    $entry = trim((string) ($fields['source_entry'] ?? ''));
    $page = (string) ($fields['source_page'] ?? '');
    $page_html = $page !== '' ? '<a href="' . esc_url($page) . '" target="_blank" style="color:#215bdb;text-decoration:none;">' . esc_html($page) . '</a>' : '—';
    $ip = sanitize_text_field((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    $country = $ip !== '' ? (boblift_inquiry_ip_country($ip) ?: '未能识别') : '未能识别';
    $time = wp_date('Y-m-d H:i');
    $message = trim((string) $fields['message']);
    $message_block = $message !== '' ? $heading('留言') . '<tr><td style="padding:0 36px;"><div style="padding:16px 20px;background:#f5f7fa;border-radius:4px;font-size:16px;line-height:1.75;color:#0b1f33;word-break:break-word;">' . nl2br(esc_html($message), false) . '</div></td></tr>' : '';
    $admin_link = $post_id > 0 ? '<a href="' . esc_url(admin_url('post.php?post=' . $post_id . '&action=edit')) . '" style="color:#94a3b8;">网站后台「询盘」</a>' : '网站后台「询盘」';
    $host = esc_html((string) wp_parse_url(home_url(), PHP_URL_HOST));

    $body = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head><body style="margin:0;padding:0;background:#eef1f5;">'
        . '<table width="100%" cellspacing="0" cellpadding="0" style="width:100%;background:#eef1f5;-webkit-text-size-adjust:100%;text-size-adjust:100%;' . $font . '"><tr><td align="center" style="padding:32px 0;">'
        . '<table width="660" cellspacing="0" cellpadding="0" style="width:660px;background:#ffffff;border:1px solid #e3e8ef;border-radius:4px;border-collapse:separate;overflow:hidden;text-align:left;">'
        . '<tr><td height="6" style="height:6px;background:#215bdb;font-size:0;line-height:0;">&nbsp;</td></tr>'
        . '<tr><td style="padding:22px 36px;border-bottom:1px solid #edf0f4;"><table width="100%" cellspacing="0" cellpadding="0"><tr><td style="vertical-align:middle;">' . boblift_inquiry_mail_logo() . '</td><td align="right" style="vertical-align:middle;font-size:13px;color:#64748b;">' . esc_html($time) . '</td></tr></table></td></tr>'
        . '<tr><td style="padding:30px 36px 0;"><div style="font-size:26px;line-height:1.3;font-weight:bold;color:#0b1f33;">新询盘通知</div><div style="padding-top:10px;font-size:15px;line-height:1.7;color:#475569;">起重机站收到一条新询盘，内容如下，请及时跟进。</div></td></tr>'
        . '<tr><td style="padding:20px 36px 0;"><table width="100%" cellspacing="0" cellpadding="0" style="background:#f5f7fa;border-radius:4px;"><tr><td style="padding:14px 18px;"><div style="font-size:12px;color:#64748b;padding-bottom:6px;">来源页面 &nbsp;<span style="display:inline-block;padding:2px 8px;border-radius:2px;background:#e3ebfc;color:#215bdb;font-size:12px;font-weight:bold;">' . $kind . '</span>' . ($entry !== '' ? ' &nbsp;<span style="font-size:12px;color:#475569;">入口：' . esc_html($entry) . '</span>' : '') . '</div><div style="font-size:14px;line-height:1.6;word-break:break-all;">' . $page_html . '</div></td></tr></table></td></tr>'
        . $heading('产品信息') . '<tr><td style="padding:0 36px;">' . $product_html . '</td></tr>'
        . $heading('客户信息') . '<tr><td style="padding:0 36px;"><table width="100%" cellspacing="0" cellpadding="0" style="border-top:1px solid #edf0f4;">' . implode('', $rows) . '</table></td></tr>'
        . $message_block
        . '<tr><td style="padding:24px 36px 0;"><table width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #edf0f4;border-radius:4px;border-collapse:separate;"><tr>' . $meta('来源国家', esc_html($country)) . $meta('IP 地址', esc_html($ip ?: '—')) . $meta('提交时间', esc_html($time)) . '</tr></table></td></tr>'
        . '<tr><td align="center" style="padding:32px 36px 12px;"><table cellspacing="0" cellpadding="0"><tr><td align="center" style="background:#215bdb;border-radius:2px;"><a href="mailto:' . esc_attr($email) . '" style="display:inline-block;width:260px;line-height:50px;font-size:17px;font-weight:bold;color:#ffffff;text-decoration:none;text-align:center;letter-spacing:1px;">立即回复客户</a></td></tr></table></td></tr>'
        . '<tr><td align="center" style="padding:0 36px 32px;font-size:13px;line-height:1.6;color:#64748b;">也可以直接回复这封邮件，回信会发到 ' . esc_html($email) . '</td></tr>'
        . '</table>'
        . '<table width="660" cellspacing="0" cellpadding="0" style="width:660px;"><tr><td align="center" style="padding:18px 36px 0;font-size:12px;line-height:1.7;color:#94a3b8;">此邮件由 ' . $host . ' 询盘表单自动发送 · 副本见' . $admin_link . '</td></tr></table>'
        . '</td></tr></table></body></html>';

    $who = trim((string) ($fields['company'] ?: $fields['name'])) ?: $email;
    $subject = '【起重机站】询盘飞奔而来，请及时跟进：' . $who . ($country !== '未能识别' ? '（' . $country . '）' : '');
    return [$subject, $body];
}

/** The customer confirmation's words, by language; any other language uses English. */
function boblift_inquiry_customer_copy(string $language): array
{
    $copy = [
        'en' => [
            'subject' => 'We have received your inquiry – BOB-LIFT',
            'title' => 'Thank you for your inquiry',
            'greeting' => 'Dear %s,',
            'greetingFallback' => 'Hello,',
            'intro' => 'Thank you for contacting BOB-LIFT. We have received your inquiry, and our sales team will review your requirements and reply to you by email as soon as possible.',
            'summary' => 'Your inquiry',
            'product' => 'Product',
            'message' => 'Your message',
            'next' => 'To help us recommend the right crane, you can reply to this email with your truck chassis model, the lifting capacity and working radius you need, and your destination port.',
            'contact' => 'Contact us',
            'phone' => 'Phone / WhatsApp',
            'email' => 'Email',
            'address' => 'Address',
            'website' => 'Website',
            'signoff' => 'Best regards,',
            'team' => 'BOB-LIFT Sales Team',
            'footer' => 'This is an automatic confirmation of the inquiry you sent on %s. If you did not send it, please ignore this email.',
        ],
        'zh' => [
            'subject' => '我们已收到您的询价 – BOB-LIFT',
            'title' => '感谢您的询价',
            'greeting' => '%s，您好：',
            'greetingFallback' => '您好：',
            'intro' => '感谢您联系 BOB-LIFT。我们已收到您的询价，销售团队会根据您的需求尽快通过邮件回复您。',
            'summary' => '您的询价内容',
            'product' => '产品',
            'message' => '您的留言',
            'next' => '为了更准确地为您推荐机型，您可以直接回复这封邮件，告诉我们卡车底盘型号、所需起重量和工作幅度，以及目的港。',
            'contact' => '联系我们',
            'phone' => '电话 / WhatsApp',
            'email' => '邮箱',
            'address' => '地址',
            'website' => '网站',
            'signoff' => '此致',
            'team' => 'BOB-LIFT 销售团队',
            'footer' => '这是您在 %s 提交询价后的自动确认邮件。如果这不是您本人提交的，请忽略本邮件。',
        ],
        'ja' => [
            'subject' => 'お問い合わせを受け付けました – BOB-LIFT',
            'title' => 'お問い合わせありがとうございます',
            'greeting' => '%s 様',
            'greetingFallback' => 'お客様',
            'intro' => 'BOB-LIFT にお問い合わせいただき、ありがとうございます。内容を確認のうえ、営業担当よりメールにてご連絡いたします。',
            'summary' => 'お問い合わせ内容',
            'product' => '製品',
            'message' => 'メッセージ',
            'next' => '最適な機種をご提案するため、トラックのシャーシ型式、必要な吊り上げ能力と作業半径、仕向け港をこのメールへの返信でお知らせください。',
            'contact' => 'お問い合わせ先',
            'phone' => '電話 / WhatsApp',
            'email' => 'メール',
            'address' => '所在地',
            'website' => 'ウェブサイト',
            'signoff' => 'よろしくお願いいたします。',
            'team' => 'BOB-LIFT 営業チーム',
            'footer' => 'このメールは %s に送信されたお問い合わせの自動確認です。お心当たりのない場合は、このメールを破棄してください。',
        ],
    ];
    return $copy[$language] ?? $copy['en'];
}

/** The confirmation the customer receives, as [subject, html body]. No prices, lead times or postcode. */
function boblift_inquiry_customer_mail(array $fields, string $language): array
{
    $copy = boblift_inquiry_customer_copy($language);
    $font = "font-family:-apple-system,BlinkMacSystemFont,'PingFang SC','Hiragino Sans','Microsoft YaHei','Helvetica Neue',Arial,sans-serif;";
    $contact = boblift_contact_settings();
    // The Japanese options keep the Chinese address; Japanese customers read the English one more easily.
    $address = $language === 'ja' ? (string) ((get_option('boblift_theme_options', [])['contact']['address'] ?? '') ?: $contact['address']) : (string) $contact['address'];
    $address = trim((string) preg_replace('/\s*\b\d{6}\b\s*/u', ' ', $address));
    $company = $language === 'zh' ? BOBLIFT_COMPANY_CHINESE_NAME : BOBLIFT_COMPANY_LEGAL_NAME;
    $site = home_url('/');
    $name = trim((string) $fields['name']);
    $greeting = $name !== '' ? sprintf($copy['greeting'], esc_html($name)) : esc_html($copy['greetingFallback']);

    $row = static fn(string $label, string $value): string => '<tr><td width="140" style="width:140px;padding:10px 12px 10px 0;border-bottom:1px solid #edf0f4;font-size:14px;color:#64748b;vertical-align:top;">' . esc_html($label) . '</td><td style="padding:10px 0;border-bottom:1px solid #edf0f4;font-size:15px;color:#0b1f33;line-height:1.6;vertical-align:top;word-break:break-word;">' . $value . '</td></tr>';
    $summary = [];
    $products = array_map(static fn(array $p): string => $p['title'], boblift_inquiry_mail_products($fields));
    if ($products) {
        $summary[] = $row($copy['product'], esc_html(implode('; ', $products)));
    }
    if (trim((string) $fields['message']) !== '') {
        $summary[] = $row($copy['message'], nl2br(esc_html(wp_trim_words((string) $fields['message'], 120, '…')), false));
    }
    $contact_rows = $row($copy['phone'], esc_html($contact['phone']))
        . $row($copy['email'], '<a href="mailto:' . esc_attr($contact['email']) . '" style="color:#215bdb;text-decoration:none;">' . esc_html($contact['email']) . '</a>')
        . ($address !== '' ? $row($copy['address'], esc_html($address)) : '')
        . $row($copy['website'], '<a href="' . esc_url($site) . '" style="color:#215bdb;text-decoration:none;">' . esc_html(untrailingslashit(preg_replace('#^https?://#', '', $site))) . '</a>');
    $section = static fn(string $title, string $rows): string => '<tr><td style="padding:24px 36px 8px;font-size:13px;font-weight:bold;letter-spacing:1px;color:#64748b;">' . esc_html($title) . '</td></tr><tr><td style="padding:0 36px;"><table width="100%" cellspacing="0" cellpadding="0" style="border-top:1px solid #edf0f4;">' . $rows . '</table></td></tr>';
    $dir = $language === 'ar' ? 'rtl' : 'ltr';

    $body = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head><body style="margin:0;padding:0;background:#eef1f5;">'
        . '<table width="100%" cellspacing="0" cellpadding="0" dir="' . $dir . '" style="width:100%;background:#eef1f5;-webkit-text-size-adjust:100%;text-size-adjust:100%;' . $font . '"><tr><td align="center" style="padding:32px 0;">'
        . '<table width="620" cellspacing="0" cellpadding="0" style="width:620px;max-width:100%;background:#ffffff;border:1px solid #e3e8ef;border-radius:4px;border-collapse:separate;overflow:hidden;text-align:left;">'
        . '<tr><td height="6" style="height:6px;background:#215bdb;font-size:0;line-height:0;">&nbsp;</td></tr>'
        . '<tr><td style="padding:22px 36px;border-bottom:1px solid #edf0f4;">' . boblift_inquiry_mail_logo() . '</td></tr>'
        . '<tr><td style="padding:30px 36px 0;"><div style="font-size:24px;line-height:1.3;font-weight:bold;color:#0b1f33;">' . esc_html($copy['title']) . '</div>'
        . '<div style="padding-top:18px;font-size:15px;line-height:1.75;color:#0b1f33;">' . $greeting . '</div>'
        . '<div style="padding-top:10px;font-size:15px;line-height:1.75;color:#334155;">' . esc_html($copy['intro']) . '</div></td></tr>'
        . ($summary ? $section($copy['summary'], implode('', $summary)) : '')
        . '<tr><td style="padding:22px 36px 0;"><div style="padding:14px 18px;background:#f5f7fa;border-radius:4px;font-size:14px;line-height:1.7;color:#334155;">' . esc_html($copy['next']) . '</div></td></tr>'
        . $section($copy['contact'], $contact_rows)
        . '<tr><td style="padding:26px 36px 32px;font-size:15px;line-height:1.7;color:#0b1f33;">' . esc_html($copy['signoff']) . '<br><strong>' . esc_html($copy['team']) . '</strong><br><span style="font-size:13px;color:#64748b;">' . esc_html($company) . '</span></td></tr>'
        . '</table>'
        . '<table width="620" cellspacing="0" cellpadding="0" style="width:620px;max-width:100%;"><tr><td align="center" style="padding:18px 36px 0;font-size:12px;line-height:1.7;color:#94a3b8;">' . esc_html(sprintf($copy['footer'], untrailingslashit(preg_replace('#^https?://#', '', $site)))) . '</td></tr></table>'
        . '</td></tr></table></body></html>';
    return [$copy['subject'], $body];
}

/** Send both emails for a saved inquiry. The customer copy is skipped for an address that is not a valid email. */
function boblift_send_inquiry_mails(array $fields, int $post_id, string $language): void
{
    // The form is handled on init priority 0, before the theme registers products and their categories; the product
    // card needs both (its type and permalink).
    if (!taxonomy_exists('product_category') && function_exists('boblift_register_taxonomies')) {
        boblift_register_content_types();
        boblift_register_taxonomies();
    }
    $from = boblift_inquiry_mail_from_header();
    [$subject, $body] = boblift_inquiry_admin_mail($fields, $post_id);
    $reply_to = 'Reply-To: ' . str_replace(["\r", "\n"], '', (string) $fields['email']);
    $sent = wp_mail(boblift_inquiry_recipient(), $subject, $body, ['Content-Type: text/html; charset=UTF-8', $from, $reply_to]);
    if ($post_id > 0) {
        update_post_meta($post_id, '_mail_sent', $sent ? '1' : '0');
    }

    if (is_email((string) $fields['email']) && apply_filters('boblift_inquiry_send_customer_mail', true, $fields)) {
        [$customer_subject, $customer_body] = boblift_inquiry_customer_mail($fields, $language);
        $contact = boblift_contact_settings();
        wp_mail((string) $fields['email'], $customer_subject, $customer_body, ['Content-Type: text/html; charset=UTF-8', $from, 'Reply-To: ' . $contact['email']]);
    }
}
