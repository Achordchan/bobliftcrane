<?php
/**
 * Template Name: Contact
 */
get_header();
$ui = boblift_site_ui('contactPage');
$contact = boblift_contact_settings();
$t = static fn(string $key, string $fallback = ''): string => (string) ($ui[$key] ?? $fallback);

// +86 15852499415 is shown as +86 158 5249 9415; anything else is shown as saved.
$format_phone = static function (string $phone): string {
    return preg_match('/^\+86\s?(\d{3})(\d{4})(\d{4})$/', trim($phone), $m) ? '+86 ' . $m[1] . ' ' . $m[2] . ' ' . $m[3] : $phone;
};
$phone_text = $format_phone((string) $contact['phone']);
$whatsapp_text = $format_phone((string) $contact['whatsapp']);

// WeChat QR: an uploaded attachment or a theme path; only shown when the file exists.
$qr_setting = $contact['wechatQr'];
$qr_id = is_array($qr_setting) ? (int) ($qr_setting['id'] ?? 0) : 0;
$qr_path = is_array($qr_setting) ? (string) ($qr_setting['path'] ?? '') : (string) $qr_setting;
$qr_url = '';
if ($qr_id > 0 || ($qr_path !== '' && preg_match('#^https?://#i', $qr_path)) || ($qr_path !== '' && is_file(get_theme_file_path('assets/' . ltrim($qr_path, '/'))))) {
    $qr_url = boblift_image_setting_url($qr_setting, 'images/wechat-qr.png');
}
$wechat_id = trim((string) $contact['wechatId']);

$address = (string) $contact['address'];
$company_cn = $t('mapKeywordCn', $address);
$map_links = [
    'google' => 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($address),
    'amap' => 'https://uri.amap.com/search?keyword=' . rawurlencode($company_cn),
    'baidu' => 'https://map.baidu.com/search/' . rawurlencode($company_cn),
];
$map_labels = is_array($ui['mapLabels'] ?? null) ? $ui['mapLabels'] : [];

$channel_card = 'flex min-w-0 flex-col rounded border border-line bg-white p-4 shadow-card md:p-5';
$channel_action = 'mt-auto inline-flex h-11 w-full items-center justify-center gap-2 rounded-sm border border-line px-3 text-sm font-black text-navy transition hover:border-blue-700 hover:text-blue-700';
$eyebrow = 'text-[11px] font-black uppercase tracking-[0.18em] text-slate-400';
$after_steps = is_array($ui['afterSteps'] ?? null) ? $ui['afterSteps'] : [];
$visit_links = is_array($ui['visitLinks'] ?? null) ? $ui['visitLinks'] : [];
?>
<div class="page-shell pt-5 md:pt-8">
    <?php
    boblift_page_hero([
        'label' => $t('heroLabel', 'Contact Us'),
        'title' => $t('heroTitle'),
        'text' => $t('heroDescription'),
        'image' => 'images/factory/yard-gate.webp',
        'image_alt' => $t('heroAlt'),
        'image_position' => 'center 60%',
    ]);
    ?>
</div>

<section class="page-shell pt-6 md:pt-8" aria-label="<?php echo esc_attr($t('channelsLabel', 'Direct contact')); ?>">
    <ul class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4 lg:gap-4">
        <li class="<?php echo esc_attr($channel_card); ?>">
            <span class="grid h-10 w-10 place-items-center rounded-sm bg-blue-50 text-blue-700"><?php echo boblift_icon('whatsapp', 'h-5 w-5'); ?></span>
            <p class="mt-4 <?php echo esc_attr($eyebrow); ?>"><?php echo esc_html((string) ($ui['cardLabels']['whatsapp'] ?? 'WhatsApp')); ?></p>
            <p class="mt-1 break-words text-lg font-black leading-6 text-ink"><?php echo esc_html($whatsapp_text); ?></p>
            <p class="mb-4 mt-2 text-sm font-semibold leading-6 text-slate-600"><?php echo esc_html($t('channelWhatsappNote')); ?></p>
            <a class="<?php echo esc_attr($channel_action); ?>" href="<?php echo esc_url($contact['whatsappHref']); ?>" target="_blank" rel="noreferrer"><?php echo esc_html($t('channelWhatsappAction')); ?> <?php echo boblift_icon('arrow-up-right', 'h-4 w-4'); ?></a>
        </li>
        <li class="<?php echo esc_attr($channel_card); ?>">
            <div class="flex items-start justify-between gap-3">
                <span class="grid h-10 w-10 place-items-center rounded-sm bg-blue-50 text-blue-700"><?php echo boblift_icon('message-circle', 'h-5 w-5'); ?></span>
                <?php if ($qr_url !== '') : ?>
                    <img class="h-[88px] w-[88px] rounded-sm border border-line bg-white object-contain p-1" src="<?php echo esc_url($qr_url); ?>" alt="<?php echo esc_attr($t('weChatQrAlt', 'BOB-LIFT WeChat QR code')); ?>" width="88" height="88" loading="lazy" decoding="async">
                <?php endif; ?>
            </div>
            <p class="mt-4 <?php echo esc_attr($eyebrow); ?>"><?php echo esc_html($t('weChatTitle', 'WeChat')); ?></p>
            <p class="mt-1 break-words text-lg font-black leading-6 text-ink"><span class="text-sm font-bold text-slate-500"><?php echo esc_html($t('channelWeChatIdLabel', 'WeChat ID')); ?></span> <?php echo esc_html($wechat_id); ?></p>
            <p class="mb-4 mt-2 text-sm font-semibold leading-6 text-slate-600"><?php echo esc_html($t('channelWeChatNote')); ?></p>
            <?php if ($wechat_id !== '') : ?>
                <button type="button" class="<?php echo esc_attr($channel_action); ?>" data-copy="<?php echo esc_attr($wechat_id); ?>" data-done="<?php echo esc_attr($t('channelWeChatCopied', 'Copied')); ?>" onclick="var b=this,l=b.textContent;navigator.clipboard&amp;&amp;navigator.clipboard.writeText(b.dataset.copy);b.textContent=b.dataset.done;setTimeout(function(){b.textContent=l;},1800);"><?php echo esc_html($t('channelWeChatAction', 'Copy WeChat ID')); ?></button>
            <?php endif; ?>
        </li>
        <li class="<?php echo esc_attr($channel_card); ?>">
            <span class="grid h-10 w-10 place-items-center rounded-sm bg-blue-50 text-blue-700"><?php echo boblift_icon('mail', 'h-5 w-5'); ?></span>
            <p class="mt-4 <?php echo esc_attr($eyebrow); ?>"><?php echo esc_html((string) ($ui['cardLabels']['email'] ?? 'Email')); ?></p>
            <p class="mt-1 break-words text-lg font-black leading-6 text-ink"><?php echo esc_html((string) $contact['email']); ?></p>
            <p class="mb-4 mt-2 text-sm font-semibold leading-6 text-slate-600"><?php echo esc_html($t('channelEmailNote')); ?></p>
            <a class="<?php echo esc_attr($channel_action); ?>" href="<?php echo esc_url('mailto:' . $contact['email']); ?>"><?php echo esc_html($t('channelEmailAction')); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
        </li>
        <li class="<?php echo esc_attr($channel_card); ?>">
            <span class="grid h-10 w-10 place-items-center rounded-sm bg-blue-50 text-blue-700"><?php echo boblift_icon('phone', 'h-5 w-5'); ?></span>
            <p class="mt-4 <?php echo esc_attr($eyebrow); ?>"><?php echo esc_html((string) ($ui['cardLabels']['phone'] ?? 'Phone')); ?></p>
            <p class="mt-1 break-words text-lg font-black leading-6 text-ink"><?php echo esc_html($phone_text); ?></p>
            <p class="mb-4 mt-2 text-sm font-semibold leading-6 text-slate-600"><?php echo esc_html($t('channelPhoneNote')); ?></p>
            <a class="<?php echo esc_attr($channel_action); ?>" href="<?php echo esc_url((string) $contact['phoneHref']); ?>"><?php echo esc_html($t('channelPhoneAction')); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
        </li>
    </ul>
    <p class="mt-4 flex items-start gap-2 text-sm font-semibold leading-6 text-slate-600">
        <?php echo boblift_icon('clock-3', 'mt-0.5 h-4 w-4 shrink-0 text-slate-400'); ?>
        <span><span class="font-black text-navy"><?php echo esc_html($t('hoursLabel', 'Working hours')); ?>:</span> <?php echo esc_html($t('hoursText')); ?></span>
    </p>
</section>

<section class="page-shell py-10 md:py-14">
    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_360px] lg:gap-8 xl:grid-cols-[minmax(0,1fr)_400px]">
        <div class="min-w-0">
            <?php boblift_render_inquiry_form(isset($_GET['product']) ? sanitize_text_field(wp_unslash($_GET['product'])) : '', ['compact' => true, 'source' => '联系页表单']); ?>
        </div>
        <aside class="min-w-0 space-y-4 lg:sticky lg:top-28">
            <div class="rounded border border-line bg-white p-5 shadow-card md:p-6">
                <p class="<?php echo esc_attr($eyebrow); ?>"><?php echo esc_html($t('afterLabel', 'After you send')); ?></p>
                <h2 class="mt-1 text-xl font-black leading-7 text-navy"><?php echo esc_html($t('afterTitle', 'What happens next')); ?></h2>
                <ol class="mt-5 space-y-5">
                    <?php foreach ($after_steps as $index => $step) : ?>
                        <li class="flex gap-4">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-sm bg-navy text-sm font-black tabular-nums text-white"><?php echo esc_html((string) ($index + 1)); ?></span>
                            <span class="min-w-0">
                                <span class="block text-sm font-black leading-6 text-navy"><?php echo esc_html((string) ($step['title'] ?? '')); ?></span>
                                <span class="mt-1 block text-sm font-semibold leading-6 text-slate-600"><?php echo esc_html((string) ($step['text'] ?? '')); ?></span>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
            <div class="rounded border border-line bg-mist p-5 md:p-6">
                <p class="text-sm font-black leading-6 text-navy"><?php echo esc_html($t('talkFirstTitle')); ?></p>
                <p class="mt-1 text-sm font-semibold leading-6 text-slate-600"><?php echo esc_html($t('talkFirstText')); ?></p>
                <a class="mt-3 inline-flex items-center gap-2 text-sm font-black text-blue-700 transition hover:text-navy" href="<?php echo esc_url($contact['whatsappHref']); ?>" target="_blank" rel="noreferrer"><?php echo boblift_icon('whatsapp', 'h-4 w-4'); ?> <?php echo esc_html($t('talkFirstAction')); ?> <?php echo boblift_icon('arrow-up-right', 'h-4 w-4'); ?></a>
            </div>
        </aside>
    </div>
</section>

<section id="map" class="page-shell pb-10 md:pb-14">
    <article class="overflow-hidden rounded border border-line bg-white shadow-card">
        <div class="grid lg:grid-cols-2">
            <div class="relative aspect-[16/9] min-h-[220px] bg-mist lg:aspect-auto lg:min-h-[380px]">
                <img class="absolute inset-0 h-full w-full object-cover" src="<?php echo esc_url(boblift_image_src('images/factory/aerial-site.webp')); ?>" alt="<?php echo esc_attr($t('locationImageAlt')); ?>" loading="lazy" decoding="async">
            </div>
            <div class="min-w-0 p-5 md:p-8">
                <p class="<?php echo esc_attr($eyebrow); ?>"><?php echo esc_html($t('locationLabel', 'Factory location')); ?></p>
                <h2 class="mt-1 text-2xl font-black leading-8 text-navy"><?php echo esc_html($t('locationHeading')); ?></h2>
                <p class="mt-3 text-sm font-semibold leading-7 text-slate-600"><?php echo esc_html($t('locationDescription')); ?></p>
                <p class="mt-5 flex items-start gap-3 text-base font-black leading-6 text-ink">
                    <?php echo boblift_icon('map-pin', 'mt-0.5 h-5 w-5 shrink-0 text-blue-700'); ?>
                    <span class="min-w-0"><span class="block <?php echo esc_attr($eyebrow); ?>"><?php echo esc_html($t('addressLabel', 'Address')); ?></span><?php echo esc_html($address); ?></span>
                </p>
                <div class="mt-5 grid gap-2 sm:grid-cols-3">
                    <?php foreach ($map_links as $key => $href) : ?>
                        <a class="inline-flex h-11 items-center justify-center gap-2 rounded-sm bg-navy px-3 text-sm font-black text-white transition hover:bg-blue-700" href="<?php echo esc_url($href); ?>" target="_blank" rel="noreferrer noopener"><?php echo esc_html((string) ($map_labels[$key] ?? ucfirst($key))); ?> <?php echo boblift_icon('arrow-up-right', 'h-4 w-4'); ?></a>
                    <?php endforeach; ?>
                </div>
                <?php if ($visit_links !== []) : ?>
                    <p class="mt-6 border-t border-line pt-4 text-sm font-semibold leading-7 text-slate-600">
                        <span class="font-black text-navy"><?php echo esc_html($t('visitLabel')); ?>:</span>
                        <?php foreach ($visit_links as $i => $link) : ?>
                            <?php echo $i > 0 ? '<span class="text-slate-300" aria-hidden="true"> / </span>' : ''; ?><a class="font-black text-blue-700 underline-offset-4 hover:underline" href="<?php echo esc_url(boblift_url((string) ($link['href'] ?? ''))); ?>"><?php echo esc_html((string) ($link['label'] ?? '')); ?></a>
                        <?php endforeach; ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </article>

    <div class="mt-4 flex flex-col gap-2 rounded border border-line bg-mist px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="min-w-0 text-sm font-semibold leading-6 text-slate-600"><span class="font-black text-navy"><?php echo esc_html($t('socialTitle')); ?></span> &middot; <?php echo esc_html($t('socialText')); ?></p>
        <a class="inline-flex shrink-0 items-center gap-2 text-sm font-black text-blue-700 transition hover:text-navy" href="<?php echo esc_url(boblift_url($t('socialHref', '/social-media.html'))); ?>"><?php echo esc_html($t('socialAction')); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
    </div>
</section>
<?php get_footer(); ?>
