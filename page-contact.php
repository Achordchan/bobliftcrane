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

$eyebrow = 'text-[11px] font-black uppercase tracking-[0.18em] text-slate-400';
$after_steps = is_array($ui['afterSteps'] ?? null) ? $ui['afterSteps'] : [];
$visit_links = is_array($ui['visitLinks'] ?? null) ? $ui['visitLinks'] : [];
?>
<div class="page-shell pt-5 md:pt-8">
    <?php
    // heroImage / heroImageAlt are editable settings (defaults in inc/site-ui-sections/contact-page.php).
    $hero_image = boblift_image_setting_url($ui['heroImage'] ?? '', 'images/factory/yard-gate.webp');
    $hero_image_alt = trim((string) ($ui['heroImageAlt'] ?? ''));
    boblift_page_hero([
        'label' => $t('heroLabel', 'Contact Us'),
        'title' => $t('heroTitle'),
        'text' => $t('heroDescription'),
        'image' => $hero_image,
        'image_alt' => $hero_image_alt !== '' ? $hero_image_alt : $t('heroAlt'),
        'image_position' => 'center 60%',
    ]);
    ?>
</div>

<section class="page-shell py-8 md:py-12">
    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_340px] lg:gap-8 xl:grid-cols-[minmax(0,1fr)_380px]">
        <div class="min-w-0">
            <?php boblift_render_inquiry_form(isset($_GET['product']) ? sanitize_text_field(wp_unslash($_GET['product'])) : '', ['compact' => true, 'source' => '联系页表单']); ?>
        </div>
        <aside class="min-w-0 space-y-4 lg:sticky lg:top-6">
            <?php // One place for the direct channels: phone and WhatsApp share a number, so they share a row. ?>
            <section class="rounded border border-line bg-white p-5 md:p-6" aria-labelledby="contact-direct-title">
                <h2 id="contact-direct-title" class="text-lg font-black leading-7 text-navy"><?php echo esc_html($t('channelsLabel', 'Direct contact')); ?></h2>
                <dl class="mt-4 divide-y divide-line border-y border-line">
                    <?php
                    // One row when phone and WhatsApp are the same number; two rows when the saved numbers differ.
                    $digits = static fn(string $number): string => preg_replace('/\D+/', '', $number) ?? '';
                    $same_number = $digits((string) $contact['whatsapp']) === '' || $digits((string) $contact['phone']) === $digits((string) $contact['whatsapp']);
                    ?>
                    <?php if ($same_number) : ?>
                    <div class="py-3.5">
                        <dt class="<?php echo esc_attr($eyebrow); ?>"><?php echo esc_html($t('phoneWhatsappLabel', 'Phone / WhatsApp')); ?></dt>
                        <dd class="mt-1 flex flex-wrap items-center justify-between gap-x-4 gap-y-1">
                            <a class="text-base font-black text-ink transition hover:text-blue-700" href="<?php echo esc_url((string) $contact['phoneHref']); ?>"><span dir="ltr"><?php echo esc_html($phone_text); ?></span></a>
                            <a class="inline-flex items-center gap-1.5 text-sm font-bold text-blue-700 transition hover:text-navy" href="<?php echo esc_url($contact['whatsappHref']); ?>" target="_blank" rel="noreferrer"><?php echo boblift_icon('whatsapp', 'h-4 w-4'); ?><?php echo esc_html((string) ($ui['cardLabels']['whatsapp'] ?? 'WhatsApp')); ?></a>
                        </dd>
                    </div>
                    <?php else : ?>
                    <div class="py-3.5">
                        <dt class="<?php echo esc_attr($eyebrow); ?>"><?php echo esc_html($t('phoneLabel', 'Phone')); ?></dt>
                        <dd class="mt-1"><a class="text-base font-black text-ink transition hover:text-blue-700" href="<?php echo esc_url((string) $contact['phoneHref']); ?>"><span dir="ltr"><?php echo esc_html($phone_text); ?></span></a></dd>
                    </div>
                    <div class="py-3.5">
                        <dt class="<?php echo esc_attr($eyebrow); ?>">WhatsApp</dt>
                        <dd class="mt-1 flex flex-wrap items-center justify-between gap-x-4 gap-y-1">
                            <span class="text-base font-black text-ink"><span dir="ltr"><?php echo esc_html($whatsapp_text); ?></span></span>
                            <a class="inline-flex items-center gap-1.5 text-sm font-bold text-blue-700 transition hover:text-navy" href="<?php echo esc_url($contact['whatsappHref']); ?>" target="_blank" rel="noreferrer"><?php echo boblift_icon('whatsapp', 'h-4 w-4'); ?><?php echo esc_html((string) ($ui['cardLabels']['whatsapp'] ?? 'WhatsApp')); ?></a>
                        </dd>
                    </div>
                    <?php endif; ?>
                    <div class="py-3.5">
                        <dt class="<?php echo esc_attr($eyebrow); ?>"><?php echo esc_html((string) ($ui['cardLabels']['email'] ?? 'Email')); ?></dt>
                        <dd class="mt-1"><a class="break-all text-base font-black text-ink transition hover:text-blue-700" href="<?php echo esc_url('mailto:' . $contact['email']); ?>"><?php echo esc_html((string) $contact['email']); ?></a></dd>
                    </div>
                    <?php if ($wechat_id !== '' || $qr_url !== '') : ?>
                        <div class="flex items-center justify-between gap-4 py-3.5">
                            <div class="min-w-0">
                                <dt class="<?php echo esc_attr($eyebrow); ?>"><?php echo esc_html($t('weChatTitle', 'WeChat')); ?></dt>
                                <dd class="mt-1">
                                    <?php if ($wechat_id !== '') : ?>
                                        <span class="block text-base font-black text-ink"><?php echo esc_html($wechat_id); ?></span>
                                        <button type="button" class="mt-1 text-sm font-bold text-blue-700 transition hover:text-navy" data-copy="<?php echo esc_attr($wechat_id); ?>" data-done="<?php echo esc_attr($t('channelWeChatCopied', 'Copied')); ?>" onclick="var b=this,l=b.textContent;navigator.clipboard&amp;&amp;navigator.clipboard.writeText(b.dataset.copy);b.textContent=b.dataset.done;setTimeout(function(){b.textContent=l;},1800);"><?php echo esc_html($t('channelWeChatAction', 'Copy WeChat ID')); ?></button>
                                    <?php endif; ?>
                                </dd>
                            </div>
                            <?php if ($qr_url !== '') : ?>
                                <img class="h-20 w-20 shrink-0 rounded-sm border border-line bg-white object-contain p-1" src="<?php echo esc_url($qr_url); ?>" alt="<?php echo esc_attr($t('weChatQrAlt', 'BOB-LIFT WeChat QR code')); ?>" width="80" height="80" loading="lazy" decoding="async">
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </dl>
                <p class="mt-4 flex items-start gap-2 text-sm font-semibold leading-6 text-slate-600">
                    <?php echo boblift_icon('clock-3', 'mt-0.5 h-4 w-4 shrink-0 text-slate-400'); ?>
                    <span><?php echo esc_html($t('hoursText')); ?></span>
                </p>
            </section>
            <section class="rounded border border-line bg-mist p-5 md:p-6" aria-labelledby="contact-after-title">
                <h2 id="contact-after-title" class="text-lg font-black leading-7 text-navy"><?php echo esc_html($t('afterTitle', 'What happens next')); ?></h2>
                <ol class="mt-4 space-y-4">
                    <?php foreach ($after_steps as $index => $step) : ?>
                        <li class="flex gap-3">
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-sm bg-navy text-xs font-black tabular-nums text-white"><?php echo esc_html((string) ($index + 1)); ?></span>
                            <span class="min-w-0">
                                <span class="block text-sm font-black leading-6 text-navy"><?php echo esc_html((string) ($step['title'] ?? '')); ?></span>
                                <span class="block text-sm font-semibold leading-6 text-slate-600"><?php echo esc_html((string) ($step['text'] ?? '')); ?></span>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </section>
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
