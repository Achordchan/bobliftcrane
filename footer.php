<?php
$options = boblift_options();
$footer = $options['footer'] ?? [];
$branding = $options['branding'] ?? [];
$contact = $options['contact'] ?? [];
$cta = $options['final_cta'] ?? [];
$show_cta = !is_page(['about', 'quote', 'contact', 'social-media', 'public-welfare', 'global-customer-visits', 'privacy-policy', 'terms-of-use', 'sitemap']) && !is_page_template(['page-about.php', 'page-social-media.php', 'page-public-welfare.php', 'page-global-customer-visits.php', 'page-privacy-policy.php', 'page-terms-of-use.php', 'page-sitemap.php']) && !is_post_type_archive('product') && !is_singular('product');
$email = $contact['email'] ?? 'info@bobliftcrane.com';
$phone = $contact['phone'] ?? '+86 15852499415';
$phone_href = $contact['phone_href'] ?? ('tel:' . preg_replace('/[^\d+]+/', '', $phone));
$whatsapp = preg_replace('/\D+/', '', $contact['whatsapp'] ?? $phone);
$wechat_id = $contact['wechat_id'] ?? preg_replace('/\D+/', '', $phone);
$quote_list_ui = function_exists('boblift_site_ui') ? boblift_site_ui('quoteList') : [];
?>
</main>
<?php if ($show_cta) : ?>
<section id="quote" class="boblift-final-cta mx-auto my-10 w-[calc(100%_-_32px)] overflow-hidden rounded border border-[#d6e2ef] bg-navy shadow-card md:my-16 md:w-[calc(100%_-_96px)] ">
    <div class="boblift-final-cta-frame relative min-h-[270px] md:min-h-[330px]">
        <?php echo boblift_img_from_setting($cta['image'] ?? 'images/cta-lift-photo.png', 'images/cta-lift-photo.png', '', 'absolute inset-0 h-full w-full object-cover object-center', ['loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(min-width: 3840px) 3200px, (min-width: 1921px) 83vw, 1600px']); ?>
        <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(7,31,66,0.98)_0%,rgba(7,31,66,0.88)_28%,rgba(7,31,66,0.46)_58%,rgba(247,251,255,0.18)_100%)]"></div>
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_78%_18%,rgba(255,255,255,0.36),transparent_32%)]"></div>
        <div class="boblift-final-cta-content relative flex min-h-[270px] items-center px-6 py-8 text-white md:min-h-[330px] md:px-12 lg:px-16">
            <div class="boblift-final-cta-copy max-w-none">
                <p class="boblift-final-cta-label mb-5 text-[11px] font-black uppercase tracking-[0.34em] text-white/90"><?php echo esc_html($cta['label'] ?? 'Project Consultation'); ?></p>
                <h2 class="boblift-final-cta-title max-w-none text-[clamp(34px,5.2vw,76px)] font-semibold leading-[0.95] tracking-[-0.015em] whitespace-nowrap"><?php echo esc_html($cta['title'] ?? 'Need a Lifting Solution?'); ?></h2>
                <div class="boblift-final-cta-actions mt-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:gap-8">
                    <button class="boblift-final-cta-button inline-flex h-[58px] shrink-0 items-center justify-center gap-1.5 whitespace-nowrap rounded-sm border border-transparent bg-gold bg-clip-padding px-8 text-base font-black text-white shadow-card outline-none transition-all hover:-translate-y-0.5 hover:bg-gold" type="button" data-inquiry-modal-open data-inquiry-source="页脚询价横幅"><?php echo esc_html($cta['button_label'] ?? 'Request a Quote'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></button>
                    <span class="hidden h-10 w-px bg-white/30 sm:block"></span>
                    <a class="boblift-final-cta-email inline-flex items-center gap-3 text-base font-semibold text-white/90 transition hover:text-white" href="mailto:<?php echo esc_attr($email); ?>"><?php echo boblift_icon('mail', 'h-5 w-5', '1.8'); ?><?php echo esc_html($email); ?></a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>
<footer class="border-t border-[#d8e3ef] bg-[linear-gradient(180deg,#ffffff_0%,#f6f9ff_45%,#edf5ff_100%)] pb-32 pt-0 lg:pb-0">
    <div class="page-shell max-w-[2200px]">
        <div class="grid grid-cols-1 gap-0 py-10 md:grid-cols-[0.92fr_1.08fr] md:gap-y-10 md:py-14 xl:grid-cols-[minmax(300px,0.95fr)_minmax(0,1.65fr)_minmax(340px,0.95fr)] xl:gap-y-0 xl:py-20 2xl:grid-cols-[minmax(360px,0.9fr)_minmax(0,1.75fr)_minmax(390px,0.95fr)] 2xl:py-24">
            <div class="border-b border-[#dbe6f2] pb-8 md:border-b-0 md:pr-9 xl:pr-12 2xl:pr-16">
                <a href="<?php echo esc_url(home_url('/')); ?>"><?php echo boblift_img_from_setting($branding['logo'] ?? 'images/bob-lift-logo.png', 'images/bob-lift-logo.png', $branding['logo_alt'] ?? 'BOB-LIFT', 'h-[56px] w-auto 2xl:h-[72px]'); ?></a>
                <p class="mt-6 max-w-[34rem] text-sm font-medium leading-6 text-slate-500 md:max-w-[360px] md:text-base md:leading-7 2xl:mt-8 2xl:max-w-[460px] 2xl:text-lg 2xl:leading-8"><?php echo esc_html($footer['intro'] ?? 'Engineered lifting solutions for global projects. Built to lift. Built to last.'); ?></p>
                <div class="mt-6 flex flex-wrap gap-3 2xl:mt-8 2xl:gap-4">
                    <?php foreach ($footer['social_links'] ?? [] as $link) : $label = strtolower((string) ($link['label'] ?? '')); ?>
                        <a class="group grid h-11 w-11 place-items-center rounded-sm border border-[#dfe8f2] bg-white shadow-card transition duration-300 hover:-translate-y-0.5 hover:border-blue-200 2xl:h-12 2xl:w-12" href="<?php echo esc_url(boblift_url($link['href'] ?? '/contact')); ?>" aria-label="<?php echo esc_attr($link['label'] ?? 'Social'); ?>">
                            <?php if (str_contains($label, 'linkedin')) : ?><span class="grid h-6 w-6 place-items-center rounded-sm bg-[#0A66C2] text-[13px] font-black leading-none text-white">in</span><?php elseif (str_contains($label, 'youtube')) : ?><span class="relative h-[18px] w-[26px] rounded-sm bg-[#FF0000]"><span class="absolute left-[10px] top-1/2 h-0 w-0 -translate-y-1/2 border-y-[5px] border-l-[8px] border-y-transparent border-l-white"></span></span><?php elseif (str_contains($label, 'facebook')) : ?><span class="grid h-6 w-6 place-items-center rounded-sm bg-[#1877F2] font-serif text-[19px] font-black leading-none text-white">f</span><?php else : ?><span class="text-xs font-black uppercase text-navy"><?php echo esc_html(substr((string) ($link['label'] ?? 'SN'), 0, 2)); ?></span><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="grid border-b border-[#dbe6f2] md:grid-cols-2 md:border-b-0 md:border-l md:border-[#dbe6f2] md:pl-8 xl:pl-0 2xl:gap-x-10">
                <?php foreach ($footer['groups'] ?? [] as $group) : ?>
                    <div class="border-b border-[#dbe6f2] py-8 last:border-b-0 md:border-b-0 md:py-0 md:pr-8 xl:pl-12 2xl:pl-16">
                        <h3 class="mb-5 text-sm font-black uppercase tracking-[0.14em] text-navy 2xl:mb-7 2xl:text-base"><?php echo esc_html($group['title']); ?></h3>
                        <div class="grid gap-3.5 2xl:gap-5">
                            <?php foreach ($group['links'] as $link) : ?><a class="group flex min-w-0 items-center justify-between gap-4 text-sm font-medium leading-6 text-slate-500 transition hover:text-blue-700 md:text-[15px] 2xl:text-base 2xl:leading-7" href="<?php echo esc_url(boblift_product_nav_url((string) ($link['href'] ?? '/'), (string) ($link['label'] ?? ''))); ?>"><span class="min-w-0"><?php echo esc_html($link['label']); ?></span><span class="shrink-0 text-slate-400 transition group-hover:translate-x-1 group-hover:text-blue-600"><?php echo boblift_icon('chevron-right', 'h-4 w-4', '1.9'); ?></span></a><?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="pt-8 md:col-span-2 md:border-t md:border-[#dbe6f2] md:pt-10 xl:col-span-1 xl:border-l xl:border-t-0 xl:pl-12 xl:pt-0 2xl:pl-16">
                <h3 class="mb-5 text-sm font-black uppercase tracking-[0.14em] text-navy 2xl:mb-7 2xl:text-base"><?php echo esc_html($footer['contact_title'] ?? 'Contact Us'); ?></h3>
                <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-1 2xl:gap-6">
                    <?php foreach ([['phone', $phone, $phone_href], ['mail', $email, 'mailto:' . $email], ['map-pin', $contact['address'] ?? 'Xuzhou, China', boblift_url('/contact')]] as $row) : ?>
                        <a class="group flex min-w-0 items-start gap-3.5 border-b border-[#e5edf6] pb-4 last:border-b-0 last:pb-0 md:border-b-0 md:pb-0 xl:border-b xl:pb-5 xl:last:border-b-0 xl:last:pb-0 2xl:gap-4 2xl:pb-6" href="<?php echo esc_url($row[2]); ?>"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-sm bg-white text-navy shadow-card ring-1 ring-[#e2eaf3] transition group-hover:text-blue-700 2xl:h-[54px] 2xl:w-[54px] "><?php echo boblift_icon($row[0], 'h-5 w-5', '1.9'); ?></span><span class="min-w-0 break-words text-sm font-medium leading-6 text-slate-600 transition group-hover:text-blue-700 md:text-[15px] md:leading-7 2xl:text-base"><?php echo esc_html($row[1]); ?></span></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="flex flex-col gap-3 border-t border-[#c9dced] py-6 text-sm font-semibold leading-6 text-slate-500 lg:flex-row lg:items-center lg:justify-between lg:gap-4 2xl:py-8 2xl:text-[15px]"><span><?php echo esc_html($footer['copyright'] ?? '© 2026 BOB-LIFT. All Rights Reserved.'); ?></span><span class="flex flex-wrap gap-x-6 gap-y-2 md:pr-24"><?php foreach ($footer['legal_links'] ?? [] as $link) : ?><a class="hover:text-blue-700" href="<?php echo esc_url(boblift_url($link['href'])); ?>"><?php echo esc_html($link['label']); ?></a><?php endforeach; ?></span></div>
    </div>
</footer>
<?php
$request_path = (string) (wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '');
$request_path = function_exists('boblift_normalize_public_path') ? boblift_normalize_public_path($request_path) : '/' . trim($request_path, '/');
$product_archive_path = function_exists('boblift_product_archive_path') ? boblift_product_archive_path() : '/products.html';
$product_archive_path = function_exists('boblift_normalize_public_path') ? boblift_normalize_public_path($product_archive_path) : '/' . trim($product_archive_path, '/');
$show_quote_list = is_post_type_archive('product') || is_singular('product') || is_tax('product_category') || (bool) get_query_var('boblift_product_category') || is_page(['products', 'product']) || $request_path === $product_archive_path;
$floating_contact_buttons = function_exists('boblift_floating_contact_buttons') ? boblift_floating_contact_buttons() : [];
?>
<?php if ($floating_contact_buttons !== []) : ?>
<div class="boblift-floating-contact-bar fixed bottom-4 left-1/2 z-40 flex -translate-x-1/2 items-center justify-center gap-1.5 rounded-sm border border-white/80 bg-white/[0.88] p-1.5 shadow-raised backdrop-blur-xl min-[1780px]:bottom-auto min-[1780px]:left-auto min-[1780px]:right-4 min-[1780px]:top-1/2 min-[1780px]:-translate-x-0 min-[1780px]:-translate-y-1/2 min-[1780px]:flex-col min-[1780px]:gap-2 min-[1780px]:p-2">
    <?php foreach ($floating_contact_buttons as $floating_contact_button) : ?>
        <?php boblift_render_floating_contact_button($floating_contact_button, $contact, $quote_list_ui); ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?php if ($show_quote_list) : ?>
<div class="boblift-quote-list-float hidden" data-quote-list-root>
    <button class="group relative inline-flex h-14 min-w-14 items-center justify-center gap-2 rounded-sm border border-white/80 bg-white/[0.86] px-4 text-sm font-black text-navy shadow-card ring-1 ring-slate-900/5 backdrop-blur-xl transition duration-300 hover:-translate-y-0.5 hover:bg-white hover:text-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100 md:h-16 md:min-w-16 md:px-5" type="button" data-quote-list-toggle aria-label="<?php echo esc_attr($quote_list_ui['openLabel'] ?? 'Open Quote List'); ?>" aria-expanded="false">
        <?php echo boblift_icon('package-check', 'h-5 w-5'); ?>
        <span class="hidden md:inline"><?php echo esc_html($quote_list_ui['title'] ?? 'Quote List'); ?></span>
        <span class="absolute -right-1 -top-1 grid h-6 min-w-6 place-items-center rounded-sm bg-gold px-1.5 text-[11px] font-black leading-none text-white ring-2 ring-white" data-quote-list-count>0</span>
    </button>
</div>
<?php endif; ?>
<?php if ($show_quote_list) : ?>
<div class="fixed inset-0 z-[240] hidden bg-[#071f42]/45 backdrop-blur-sm" data-quote-list-drawer aria-hidden="true">
    <button class="absolute inset-0 h-full w-full cursor-default" type="button" data-quote-list-close aria-label="<?php echo esc_attr($quote_list_ui['closeLabel'] ?? 'Close Quote List'); ?>"></button>
    <aside class="absolute inset-x-0 bottom-0 flex max-h-[86dvh] flex-col overflow-hidden rounded-t border border-white/70 bg-white shadow-raised md:inset-y-0 md:left-auto md:right-0 md:h-full md:max-h-none md:w-[420px] md:rounded-tr-none" role="dialog" aria-modal="true" aria-labelledby="quote-list-title" data-quote-list-panel tabindex="-1">
        <div class="flex items-start justify-between gap-4 border-b border-line px-5 py-5 md:px-6">
            <div>
                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-blue-700"><?php echo esc_html($quote_list_ui['brandLabel'] ?? 'BOB-LIFT'); ?></p>
                <h2 id="quote-list-title" class="mt-1 text-2xl font-black tracking-[-0.015em] text-ink"><?php echo esc_html($quote_list_ui['title'] ?? 'Quote List'); ?></h2>
                <p class="mt-1 text-sm font-semibold text-slate-500"><span data-quote-list-panel-count>0</span> <?php echo esc_html($quote_list_ui['countSuffix'] ?? 'products selected'); ?></p>
            </div>
            <button class="grid h-10 w-10 shrink-0 place-items-center rounded-sm border border-line bg-white text-slate-500 transition hover:border-blue-200 hover:text-blue-700" type="button" data-quote-list-close aria-label="<?php echo esc_attr($quote_list_ui['closeLabel'] ?? 'Close Quote List'); ?>"><?php echo boblift_icon('x', 'h-5 w-5'); ?></button>
        </div>
        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4 md:px-6">
            <div class="hidden rounded border border-dashed border-line bg-[#f8fbff] p-5 text-center text-sm font-semibold leading-6 text-slate-500" data-quote-list-empty><?php echo esc_html($quote_list_ui['emptyText'] ?? 'No products selected yet.'); ?></div>
            <div class="grid gap-3" data-quote-list-items></div>
        </div>
        <div class="grid gap-3 border-t border-line bg-white px-5 py-4 md:px-6">
            <button class="inline-flex h-12 items-center justify-center gap-2 rounded-sm bg-navy px-6 text-sm font-black text-white shadow-card transition hover:-translate-y-0.5 hover:bg-[#0b2d5b] disabled:cursor-not-allowed disabled:opacity-50" type="button" data-quote-list-request><?php echo esc_html($quote_list_ui['requestLabel'] ?? 'Request Quote'); ?> <?php echo boblift_icon('send', 'h-4 w-4'); ?></button>
            <button class="inline-flex h-11 items-center justify-center rounded-sm border border-line bg-white px-6 text-sm font-black text-ink transition hover:-translate-y-0.5 hover:border-blue-200 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-50" type="button" data-quote-list-clear><?php echo esc_html($quote_list_ui['clearLabel'] ?? 'Clear All'); ?></button>
        </div>
    </aside>
</div>
<?php endif; ?>
<button class="fixed bottom-28 right-4 z-30 size-10 translate-y-4 rounded-sm bg-navy p-0 text-white opacity-0 shadow-raised transition duration-300 hover:-translate-y-1 hover:bg-[#0b2d5b] pointer-events-none md:bottom-24 md:z-40 lg:bottom-8 lg:right-8 lg:size-14" type="button" data-back-top aria-label="<?php echo esc_attr($quote_list_ui['backTopLabel'] ?? 'Back to top'); ?>"><?php echo boblift_icon('arrow-up', 'mx-auto h-5 w-5'); ?></button>
<div class="fixed inset-0 z-50 hidden place-items-center bg-ink/18 p-4 backdrop-blur-[2px]" data-qr-modal>
    <div class="relative w-full max-w-[300px] rounded border border-line bg-white p-5 text-center shadow-card">
        <button class="absolute right-3 top-3 grid size-8 place-items-center rounded-sm bg-[#f4f7fb] text-slate-500 transition hover:bg-navy hover:text-white" type="button" data-qr-close aria-label="<?php echo esc_attr($quote_list_ui['wechatCloseLabel'] ?? 'Close WeChat QR code'); ?>"><?php echo boblift_icon('x', 'h-4 w-4'); ?></button>
        <h2 class="pr-7 text-lg font-black tracking-[-0.01em] text-ink"><?php echo esc_html($quote_list_ui['wechatTitle'] ?? 'WeChat QR Code'); ?></h2>
        <p class="mt-1 text-xs font-semibold text-slate-500"><?php echo esc_html(str_replace('{id}', (string) $wechat_id, (string) ($quote_list_ui['wechatScanTemplate'] ?? 'Scan or search ID: {id}'))); ?></p>
        <div class="mx-auto mt-5 w-[210px] rounded border border-line bg-white p-2 shadow-card"><?php echo boblift_img_from_setting($contact['wechat_qr'] ?? 'images/wechat-qr.png', 'images/wechat-qr.png', $quote_list_ui['wechatTitle'] ?? 'BOB-LIFT WeChat QR code', 'h-auto w-full rounded'); ?></div>
    </div>
</div>
<?php boblift_render_inquiry_modal(); ?>
<?php boblift_render_catalog_modal(); ?>
<?php wp_footer(); ?>
</body>
</html>
