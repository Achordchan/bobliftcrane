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
// Customised old location copy (locationTitle / locationDescriptionTemplate) is carried into these fields by boblift_site_ui().
$location_heading = $t('locationHeading');
$location_description = str_replace('{address}', $address, $t('locationDescription'));
// Visits take place at the main factory, so the map buttons search for it (the registered address when it is not set).
$contact_option = boblift_option('contact', []);
$factory_address = trim((string) (is_array($contact_option) ? ($contact_option['factory_address'] ?? '') : ''));
$map_query = $factory_address !== '' ? $factory_address : $address;
// Amap and Baidu search a Chinese keyword: one an editor saved (factory keyword first, then the older company keyword),
// else the built-in Chinese address while the factory address is the built-in one, else the configured address itself.
$contact_defaults = boblift_site_ui_defaults('contactPage');
$saved_keyword = static fn(string $key): string => trim($t($key)) !== '' && $t($key) !== (string) ($contact_defaults[$key] ?? '') && $t($key) !== boblift_localized_static_text((string) ($contact_defaults[$key] ?? '')) ? $t($key) : '';
$default_factory_address = (string) (boblift_default_options()['contact']['factory_address'] ?? '');
if ($factory_address === '') {
    $map_query_cn = $t('mapKeywordCn', $address);
} elseif ($saved_keyword('mapFactoryKeywordCn') !== '') {
    $map_query_cn = $saved_keyword('mapFactoryKeywordCn');
} elseif ($saved_keyword('mapKeywordCn') !== '') {
    $map_query_cn = $saved_keyword('mapKeywordCn');
} elseif ($factory_address === $default_factory_address) {
    $map_query_cn = (string) ($contact_defaults['mapFactoryKeywordCn'] ?? $factory_address);
} else {
    $map_query_cn = boblift_localized_static_text($factory_address, 'zh');
}
$map_links = [
    'google' => 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($map_query),
    'amap' => 'https://uri.amap.com/search?keyword=' . rawurlencode($map_query_cn),
    'baidu' => 'https://map.baidu.com/search/' . rawurlencode($map_query_cn),
];
$map_labels = is_array($ui['mapLabels'] ?? null) ? $ui['mapLabels'] : [];

$eyebrow = 'text-[11px] font-black uppercase tracking-[0.18em] text-slate-400';
$after_steps = is_array($ui['afterSteps'] ?? null) ? $ui['afterSteps'] : [];
$support_defaults = boblift_localize_static_value(boblift_site_ui_defaults('contactPage'));
$has_custom_support = false;
foreach (['supportTitle', 'supportDescription', 'supportItems'] as $support_key) {
    if (($ui[$support_key] ?? null) !== ($support_defaults[$support_key] ?? null)) {
        $has_custom_support = true;
        break;
    }
}
$support_items = is_array($ui['supportItems'] ?? null) ? $ui['supportItems'] : [];
$brochures = function_exists('boblift_brochure_items') ? boblift_brochure_items() : [];
$telegram_href = function_exists('boblift_telegram_contact_href') ? boblift_telegram_contact_href((string) $contact['telegram']) : '';
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
        <div class="min-w-0 lg:col-start-1 lg:row-start-1">
            <?php boblift_render_inquiry_form(isset($_GET['product']) ? sanitize_text_field(wp_unslash($_GET['product'])) : '', ['compact' => true, 'source' => '联系页表单', 'badge' => false, 'direct_links' => false]); ?>
        </div>
        <?php // The direct channels span both rows on desktop (sticky beside the form); on phones they follow the form directly. ?>
        <aside class="min-w-0 space-y-4 lg:sticky lg:top-[calc(var(--boblift-header-h)+1.5rem)] lg:col-start-2 lg:row-span-2 lg:row-start-1">
            <?php // One place for the direct channels: phone and WhatsApp share a number, so they share a row. ?>
            <section class="rounded border border-line bg-white p-5 md:p-6" aria-labelledby="contact-direct-title">
                <h2 id="contact-direct-title" class="text-lg font-black leading-7 text-navy"><?php echo esc_html($t('channelsLabel', 'Direct contact')); ?></h2>
                <dl class="mt-4 divide-y divide-line border-y border-line">
                    <?php
                    // One row when phone and WhatsApp are the same number; two rows when the saved numbers differ.
                    $digits = static fn(string $number): string => preg_replace('/\D+/', '', $number) ?? '';
                    $same_number = $digits((string) $contact['whatsapp']) === '' || $digits((string) $contact['phone']) === $digits((string) $contact['whatsapp']);
                    // Telegram joins that row when it is the same number, and gets its own row otherwise.
                    $telegram_same = $telegram_href !== '' && $digits((string) $contact['telegram']) === $digits((string) $contact['phone']);
                    $telegram_link = $telegram_href !== '' ? '<a class="inline-flex items-center gap-1.5 text-sm font-bold text-blue-700 transition hover:text-navy" href="' . esc_url($telegram_href) . '" target="_blank" rel="noreferrer">' . boblift_icon('telegram', 'h-4 w-4') . esc_html($t('telegramLabel', 'Telegram')) . '</a>' : '';
                    ?>
                    <?php if ($same_number) : ?>
                    <div class="py-3.5">
                        <dt class="<?php echo esc_attr($eyebrow); ?>"><?php echo esc_html($telegram_same ? $t('phoneMessengerLabel', 'Phone / WhatsApp / Telegram') : $t('phoneWhatsappLabel', 'Phone / WhatsApp')); ?></dt>
                        <dd class="mt-1">
                            <a class="text-base font-black text-ink transition hover:text-blue-700" href="<?php echo esc_url((string) $contact['phoneHref']); ?>"><span dir="ltr"><?php echo esc_html($phone_text); ?></span></a>
                            <span class="mt-2 flex flex-wrap items-center gap-x-5 gap-y-2">
                                <a class="inline-flex items-center gap-1.5 text-sm font-bold text-blue-700 transition hover:text-navy" href="<?php echo esc_url($contact['whatsappHref']); ?>" target="_blank" rel="noreferrer"><?php echo boblift_icon('whatsapp', 'h-4 w-4'); ?><?php echo esc_html((string) ($ui['cardLabels']['whatsapp'] ?? 'WhatsApp')); ?></a>
                                <?php echo $telegram_same ? $telegram_link : ''; ?>
                            </span>
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
                    <?php // Its own row unless the shared phone / WhatsApp row above already carries it. ?>
                    <?php if ($telegram_link !== '' && !($telegram_same && $same_number)) : ?>
                    <div class="py-3.5">
                        <dt class="<?php echo esc_attr($eyebrow); ?>"><?php echo esc_html($t('telegramLabel', 'Telegram')); ?></dt>
                        <dd class="mt-1"><?php echo $telegram_link; ?></dd>
                    </div>
                    <?php endif; ?>
                    <?php if ($contact['landline'] !== '') : ?>
                    <div class="py-3.5">
                        <dt class="<?php echo esc_attr($eyebrow); ?>"><?php echo esc_html($t('landlineLabel', 'Office phone')); ?></dt>
                        <dd class="mt-1"><a class="text-base font-black text-ink transition hover:text-blue-700" href="<?php echo esc_url('tel:' . preg_replace('/[^\d+]+/', '', $contact['landline'])); ?>"><span dir="ltr"><?php echo esc_html($contact['landline']); ?></span></a></dd>
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
                                        <button type="button" class="mt-1 text-sm font-bold text-blue-700 transition hover:text-navy" data-copy="<?php echo esc_attr($wechat_id); ?>" data-done="<?php echo esc_attr($t('channelWeChatCopied', 'Copied')); ?>" onclick="var b=this,l=b.textContent,show=function(t){b.textContent=t;setTimeout(function(){b.textContent=l;},2400);},fallback=function(){var a=document.createElement('textarea'),ok=false;a.value=b.dataset.copy;a.setAttribute('readonly','');a.style.position='fixed';a.style.opacity='0';document.body.appendChild(a);a.select();try{ok=document.execCommand('copy');}catch(e){}a.remove();show(ok?b.dataset.done:b.dataset.copy);};if(navigator.clipboard&amp;&amp;window.isSecureContext){navigator.clipboard.writeText(b.dataset.copy).then(function(){show(b.dataset.done);},fallback);}else{fallback();}"><?php echo esc_html($t('channelWeChatAction', 'Copy WeChat ID')); ?></button>
                                    <?php endif; ?>
                                </dd>
                            </div>
                            <?php if ($qr_url !== '') : ?>
                                <img class="h-20 w-20 shrink-0 rounded-sm border border-line bg-white object-contain p-1" src="<?php echo esc_url($qr_url); ?>" alt="<?php echo esc_attr($t('weChatQrAlt', 'BOB-LIFT WeChat QR code')); ?>" width="80" height="80" loading="lazy" decoding="async">
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </dl>
                <p class="mt-4 flex items-start gap-2 text-sm font-normal leading-6 text-slate-600">
                    <?php echo boblift_icon('clock-3', 'mt-0.5 h-4 w-4 shrink-0 text-slate-400'); ?>
                    <span><?php echo esc_html($t('hoursText')); ?></span>
                </p>
                <?php if ($t('languagesText') !== '') : ?>
                    <p class="mt-2 flex items-start gap-2 text-sm font-normal leading-6 text-slate-600">
                        <?php echo boblift_icon('globe', 'mt-0.5 h-4 w-4 shrink-0 text-slate-400'); ?>
                        <span><?php echo esc_html($t('languagesText')); ?></span>
                    </p>
                <?php endif; ?>
            </section>
            <section class="rounded border border-line bg-mist p-5 md:p-6" aria-labelledby="contact-after-title">
                <h2 id="contact-after-title" class="text-lg font-black leading-7 text-navy"><?php echo esc_html($t('afterTitle', 'What happens next')); ?></h2>
                <ol class="mt-4 space-y-4">
                    <?php foreach ($after_steps as $index => $step) : ?>
                        <li class="flex gap-3">
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-sm bg-navy text-xs font-black tabular-nums text-white"><?php echo esc_html((string) ($index + 1)); ?></span>
                            <span class="min-w-0">
                                <span class="block text-sm font-black leading-6 text-navy"><?php echo esc_html((string) ($step['title'] ?? '')); ?></span>
                                <span class="block text-sm font-normal leading-6 text-slate-600"><?php echo esc_html((string) ($step['text'] ?? '')); ?></span>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </section>
        </aside>
        <?php // Brochures, and support copy an editor wrote, fill the space under the form; the direct channels and next steps sit beside it. ?>
        <?php if ($brochures !== [] || $has_custom_support) : ?>
        <div class="grid min-w-0 items-start gap-4 lg:col-start-1 lg:row-start-2">
            <?php if ($brochures !== []) : ?>
                <section class="min-w-0 rounded border border-line bg-white p-5 md:p-6" aria-labelledby="contact-brochure-title">
                    <h2 id="contact-brochure-title" class="text-lg font-black leading-7 text-navy"><?php echo esc_html($t('brochureTitle', 'Download a brochure')); ?></h2>
                    <p class="mt-1 text-sm font-normal leading-6 text-slate-600"><?php echo esc_html($t('brochureText')); ?></p>
                    <ul class="mt-4 grid gap-3 sm:grid-cols-3">
                        <?php foreach ($brochures as $brochure) : ?>
                            <li class="min-w-0">
                                <a class="group flex h-full items-center gap-3 rounded-sm border border-line bg-mist p-3 transition hover:border-blue-200 hover:bg-white" href="<?php echo esc_url($brochure['url']); ?>" download>
                                    <img class="h-16 w-12 shrink-0 rounded-sm border border-line bg-white object-cover" src="<?php echo esc_url($brochure['cover']); ?>" alt="" width="48" height="64" loading="lazy" decoding="async">
                                    <span class="min-w-0">
                                        <span class="block text-sm font-black leading-5 text-ink group-hover:text-blue-700"><?php echo esc_html($brochure['title']); ?></span>
                                        <span class="mt-1 inline-flex items-center gap-1 text-xs font-bold text-slate-500"><?php echo boblift_icon('download', 'h-3.5 w-3.5'); ?>PDF · <?php echo esc_html($brochure['size']); ?></span>
                                    </span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
            <?php // Support copy an editor wrote in the old layout stays visible; the built-in support overview is not repeated. ?>
            <?php if ($has_custom_support) : ?>
                <section class="rounded border border-line bg-mist p-5 md:p-6" aria-labelledby="contact-support-title">
                    <h2 id="contact-support-title" class="text-lg font-black leading-7 text-navy"><?php echo esc_html($t('supportTitle')); ?></h2>
                    <p class="mt-3 text-sm font-normal leading-6 text-slate-600"><?php echo esc_html($t('supportDescription')); ?></p>
                    <ul class="mt-4 space-y-4">
                        <?php foreach ($support_items as $item) : ?>
                            <li class="flex gap-3">
                                <?php echo boblift_icon((string) ($item['iconName'] ?? 'shield-check'), 'mt-0.5 h-4 w-4 shrink-0 text-slate-400'); ?>
                                <span class="min-w-0">
                                    <span class="block text-sm font-black leading-6 text-navy"><?php echo esc_html((string) ($item['title'] ?? '')); ?></span>
                                    <span class="block text-sm font-normal leading-6 text-slate-600"><?php echo esc_html((string) ($item['text'] ?? '')); ?></span>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<section id="map" class="page-shell pb-10 md:pb-14">
    <article class="overflow-hidden rounded border border-line bg-white shadow-card">
        <div class="grid grid-cols-1 lg:grid-cols-2">
            <div class="relative aspect-[16/9] min-h-[220px] bg-mist lg:aspect-auto lg:min-h-[380px]">
                <img class="absolute inset-0 h-full w-full object-cover" src="<?php echo esc_url(boblift_image_src('images/factory/aerial-site.webp')); ?>" alt="<?php echo esc_attr($t('locationImageAlt')); ?>" loading="lazy" decoding="async">
            </div>
            <div class="min-w-0 p-5 md:p-8">
                <p class="<?php echo esc_attr($eyebrow); ?>"><?php echo esc_html($t('locationLabel', 'Factory location')); ?></p>
                <h2 class="mt-1 text-2xl font-black leading-8 text-navy"><?php echo esc_html($location_heading); ?></h2>
                <p class="mt-3 text-sm font-normal leading-7 text-slate-600"><?php echo esc_html($location_description); ?></p>
                <?php // Registered address, main factory and office; the map buttons below search for the main factory. ?>
                <ul class="mt-5 grid gap-4">
                    <?php foreach (boblift_company_addresses() as $row) : ?>
                        <li class="flex items-start gap-3 text-[15px] font-bold leading-6 text-ink md:text-base md:font-black">
                            <?php echo boblift_icon('map-pin', 'mt-0.5 h-5 w-5 shrink-0 text-blue-700'); ?>
                            <span class="min-w-0"><span class="block <?php echo esc_attr($eyebrow); ?>"><?php echo esc_html($row['label']); ?></span><?php echo esc_html($row['address']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p class="mt-5 <?php echo esc_attr($eyebrow); ?>"><?php echo esc_html($t('mapHint', 'Directions to the main factory')); ?></p>
                <div class="mt-2 grid grid-cols-3 gap-2">
                    <?php foreach ($map_links as $key => $href) : ?>
                        <a class="inline-flex h-11 min-w-0 items-center justify-center gap-1 rounded-sm bg-navy px-2 text-[13px] font-black text-white transition hover:bg-blue-700 sm:gap-2 sm:px-3 sm:text-sm" href="<?php echo esc_url($href); ?>" target="_blank" rel="noreferrer noopener"><span class="truncate"><?php echo esc_html((string) ($map_labels[$key] ?? ucfirst($key))); ?></span> <?php echo boblift_icon('arrow-up-right', 'hidden h-4 w-4 shrink-0 sm:block'); ?></a>
                    <?php endforeach; ?>
                </div>
                <?php if ($visit_links !== []) : ?>
                    <p class="mt-6 border-t border-line pt-4 text-sm font-normal leading-7 text-slate-600">
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
        <p class="min-w-0 text-sm font-normal leading-6 text-slate-600"><span class="font-black text-navy"><?php echo esc_html($t('socialTitle')); ?></span> &middot; <?php echo esc_html($t('socialText')); ?></p>
        <a class="inline-flex shrink-0 items-center gap-2 text-sm font-black text-blue-700 transition hover:text-navy" href="<?php echo esc_url(boblift_url($t('socialHref', '/social-media.html'))); ?>"><?php echo esc_html($t('socialAction')); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
    </div>
</section>
<?php get_footer(); ?>
