<?php
$options = boblift_options();
$footer = $options['footer'] ?? [];
$branding = $options['branding'] ?? [];
$contact = $options['contact'] ?? [];
$cta = $options['final_cta'] ?? [];
// Pages that already end in their own contact or plan-a-visit panel skip the footer CTA. Japanese and
// Chinese copies carry suffixed slugs (about-2, about-3), so the check uses the English page's slug.
$cta_page_slug = '';
if (is_page()) {
    $cta_page_id = (int) get_queried_object_id();
    $cta_source_id = function_exists('pll_get_post') ? (int) (pll_get_post($cta_page_id, 'en') ?: $cta_page_id) : $cta_page_id;
    $cta_page_slug = (string) get_post_field('post_name', $cta_source_id);
}
$show_cta = !in_array($cta_page_slug, ['about', 'quote', 'contact', 'social-media', 'factory-tour', 'quality-testing', 'mounting-shipping', 'service', 'public-welfare', 'global-customer-visits', 'privacy-policy', 'terms-of-use', 'sitemap'], true) && !is_page_template(['page-about.php', 'page-social-media.php', 'page-factory-tour.php', 'page-quality-testing.php', 'page-mounting-shipping.php', 'page-service.php', 'page-public-welfare.php', 'page-global-customer-visits.php', 'page-privacy-policy.php', 'page-terms-of-use.php', 'page-sitemap.php']) && !is_post_type_archive('product') && !is_singular('product');
$email = $contact['email'] ?? 'info@bobliftcrane.com';
$phone = $contact['phone'] ?? '+86 15852499415';
$phone_href = $contact['phone_href'] ?? ('tel:' . preg_replace('/[^\d+]+/', '', $phone));
$whatsapp = preg_replace('/\D+/', '', $contact['whatsapp'] ?? $phone);
$wechat_id = $contact['wechat_id'] ?? preg_replace('/\D+/', '', $phone);
$quote_list_ui = function_exists('boblift_site_ui') ? boblift_site_ui('quoteList') : [];
?>
</main>
<?php if ($show_cta) : ?>
<section id="quote" class="boblift-final-cta page-shell my-10 overflow-hidden rounded border border-[#d6e2ef] bg-navy shadow-card md:my-16">
    <div class="boblift-final-cta-frame relative min-h-[270px] md:min-h-[330px] 2xl:min-h-[360px]">
        <div class="boblift-final-cta-media absolute inset-y-0 end-0 w-full md:w-[min(72%,990px)] 2xl:w-[min(66%,1080px)]">
            <img class="h-full w-full object-cover object-[72%_center] md:object-right" src="<?php echo esc_url(boblift_page_hero_image_setting($cta['image'] ?? [], 'images/page-heroes/site-dusk.webp')); ?>" alt="" loading="lazy" decoding="async">
        </div>
        <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(7,31,66,0.94)_0%,rgba(7,31,66,0.78)_45%,rgba(7,31,66,0.35)_100%)] md:hidden"></div>
        <div class="boblift-final-cta-content relative flex min-h-[270px] items-center px-6 py-8 text-white md:min-h-[330px] md:px-12 lg:px-16 2xl:min-h-[360px]">
            <div class="boblift-final-cta-copy max-w-none">
                <p class="boblift-final-cta-label mb-4 text-xs font-black uppercase tracking-[0.18em] text-white/75"><?php echo esc_html($cta['label'] ?? 'Project Consultation'); ?></p>
                <h2 class="boblift-final-cta-title max-w-none text-[clamp(2rem,3vw,3.75rem)] font-black leading-[1.02] tracking-[-0.02em]"><?php echo esc_html($cta['title'] ?? 'Need a Lifting Solution?'); ?></h2>
                <div class="boblift-final-cta-actions mt-7 flex flex-col gap-4 sm:flex-row sm:items-center sm:gap-8">
                    <button class="boblift-final-cta-button inline-flex h-[54px] shrink-0 items-center justify-center gap-1.5 whitespace-nowrap rounded-sm bg-gold px-7 text-base font-black text-white outline-none transition hover:brightness-95 focus-visible:ring-2 focus-visible:ring-white/70" type="button" data-inquiry-modal-open data-inquiry-source="页脚询价横幅"><?php echo esc_html($cta['button_label'] ?? 'Request a Quote'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></button>
                    <span class="hidden h-10 w-px bg-white/25 sm:block"></span>
                    <a class="boblift-final-cta-email inline-flex items-center gap-3 text-base font-semibold text-white/85 transition hover:text-white" href="mailto:<?php echo esc_attr($email); ?>"><?php echo boblift_icon('mail', 'h-5 w-5', '1.8'); ?><span dir="ltr"><?php echo esc_html($email); ?></span></a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>
<?php
$site_host = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);
// The old defaults pointed every social icon at the contact page (or "#"); those placeholders stay hidden until a real
// destination is saved. Any other saved link is kept: profile URLs, //host addresses (read over https) and site paths
// such as /go/linkedin or go/linkedin, including absolute URLs on this site's own domain.
$footer_social_links = [];
foreach (is_array($footer['social_links'] ?? null) ? $footer['social_links'] : [] as $link) {
    $href = is_array($link) ? trim((string) ($link['href'] ?? '')) : '';
    if (str_starts_with($href, '//') && strlen($href) > 2) {
        $href = 'https:' . $href;
    }
    $host = (string) wp_parse_url($href, PHP_URL_HOST);
    $path = (string) wp_parse_url($href, PHP_URL_PATH);
    $own = $host === '' || strcasecmp($host, $site_host) === 0;
    $placeholder = $href === '' || str_starts_with($href, '#') || ($own && preg_match('#^/?contact(?:\.html)?/?$#i', $path));
    // mailto:, tel: and the other schemes WordPress allows in links carry no host and are kept as saved.
    $allowed_scheme = $host === '' && preg_match('/^([a-z][a-z0-9+.-]*):./i', $href, $scheme) && in_array(strtolower($scheme[1]), wp_allowed_protocols(), true);
    // A relative route such as go/linkedin still resolves through boblift_url(); only a scheme WordPress does not allow is dropped.
    $other_scheme = !$allowed_scheme && preg_match('/^[a-z][a-z0-9+.-]*:/i', $href);
    if ($placeholder || ($host === '' && $other_scheme)) {
        continue;
    }
    $link['href'] = $host === '' && !$allowed_scheme ? boblift_url($href) : $href;
    $footer_social_links[] = $link;
}
$footer_link_groups = array_map(static function (array $group): array {
    if (boblift_footer_is_product_group($group)) {
        $group['links'] = array_map(static fn(array $family): array => ['label' => $family['title'], 'url' => $family['url']], boblift_product_menu_families());
        return $group;
    }
    // Saved company links predate the factory, testing and video pages; list them after About.
    $links = array_values(array_filter((array) ($group['links'] ?? []), 'is_array'));
    $paths = array_map(static fn(array $link): string => preg_replace('/\.html$/', '', trim((string) wp_parse_url((string) ($link['href'] ?? ''), PHP_URL_PATH), '/')), $links);
    $about_at = array_search('about', $paths, true);
    // Only the untouched historical company group (About, Cases, News, Contact with their default labels) gets the new pages;
    // a group an administrator edited keeps exactly the links they saved.
    $labels = array_map(static fn(array $link): string => trim((string) ($link['label'] ?? '')), $links);
    $is_default_company_group = $paths === ['about', 'cases', 'news', 'contact'] && $labels === ['About BOB-LIFT', 'Customer Cases', 'Company News', 'Contact Us'];
    if ($about_at !== false && $is_default_company_group) {
        $extra = [];
        foreach (['factory-tour', 'quality-testing', 'mounting-shipping', 'service', 'videos'] as $slug) {
            $page = in_array($slug, $paths, true) ? null : boblift_find_page_by_slug($slug);
            if ($page instanceof WP_Post && $page->post_status === 'publish') {
                $localized = function_exists('boblift_post_for_current_language') ? boblift_post_for_current_language($page) : $page;
                $extra[] = ['label' => get_the_title($localized), 'url' => get_permalink($localized)];
            }
        }
        array_splice($links, $about_at + 1, 0, $extra);
        $group['links'] = $links;
    }
    return $group;
}, array_values(array_filter(is_array($footer['groups'] ?? null) ? $footer['groups'] : [], 'is_array')));
?>
<footer class="border-t border-line bg-mist pb-[calc(76px+env(safe-area-inset-bottom))] pt-0 lg:pb-24 min-[1780px]:pb-0">
    <div class="page-shell max-w-[2200px]">
        <div class="grid grid-cols-1 gap-0 py-10 md:grid-cols-[0.92fr_1.08fr] md:gap-y-10 md:py-14 xl:grid-cols-[minmax(300px,0.95fr)_minmax(0,1.65fr)_minmax(340px,0.95fr)] xl:gap-y-0 xl:py-16 2xl:grid-cols-[minmax(360px,0.9fr)_minmax(0,1.75fr)_minmax(390px,0.95fr)] 2xl:py-20">
            <div class="border-b border-line pb-8 md:border-b-0 md:pe-9 xl:pe-12 2xl:pe-16">
                <a href="<?php echo esc_url(home_url('/')); ?>"><?php echo boblift_img_from_setting($branding['logo'] ?? 'images/bob-lift-logo.png', 'images/bob-lift-logo.png', $branding['logo_alt'] ?? 'BOB-LIFT', 'h-[52px] w-auto 2xl:h-[64px]'); ?></a>
                <p class="mt-6 max-w-[34rem] text-sm font-medium leading-6 text-slate-500 md:max-w-[360px] md:text-base md:leading-7 2xl:max-w-[440px]"><?php echo esc_html($footer['intro'] ?? 'Engineered lifting solutions for global projects. Built to lift. Built to last.'); ?></p>
                <?php if ($footer_social_links !== []) : ?>
                    <div class="mt-6 flex flex-wrap items-center gap-4">
                        <?php foreach ($footer_social_links as $link) :
                            $social_label = (string) ($link['label'] ?? '');
                            $social_icon = 'images/social-icons/' . sanitize_key(str_replace(' ', '', strtolower($social_label))) . '.svg';
                        ?>
                            <a class="grid h-6 w-6 place-items-center text-slate-400 transition hover:text-navy" href="<?php echo esc_url((string) $link['href']); ?>" target="_blank" rel="noreferrer" aria-label="<?php echo esc_attr($social_label); ?>">
                                <?php if (is_readable(boblift_asset_path($social_icon))) : ?><span class="h-5 w-5 bg-current [mask-position:center] [mask-repeat:no-repeat] [mask-size:contain]" style="-webkit-mask-image: url('<?php echo esc_url(boblift_asset($social_icon)); ?>'); mask-image: url('<?php echo esc_url(boblift_asset($social_icon)); ?>');"></span><?php else : ?><span class="text-xs font-black uppercase"><?php echo esc_html(substr($social_label, 0, 2)); ?></span><?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="grid grid-cols-2 gap-x-6 border-b border-line py-8 md:border-b-0 md:border-s md:py-0 md:ps-8 xl:ps-0 2xl:gap-x-10">
                <?php foreach ($footer_link_groups as $group) : ?>
                    <div class="min-w-0 md:pe-8 xl:ps-12 2xl:ps-16">
                        <h3 class="mb-4 text-sm font-black uppercase tracking-[0.14em] text-navy md:mb-5"><?php echo esc_html((string) ($group['title'] ?? '')); ?></h3>
                        <ul class="grid gap-2 md:gap-3">
                            <?php foreach (($group['links'] ?? []) as $link) : ?><li><a class="text-sm font-medium leading-6 text-slate-500 transition hover:text-blue-700 md:text-[15px]" href="<?php echo esc_url(isset($link['url']) ? (string) $link['url'] : boblift_product_nav_url((string) ($link['href'] ?? '/'), (string) ($link['label'] ?? ''))); ?>"><?php echo esc_html((string) ($link['label'] ?? '')); ?></a></li><?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="pt-8 md:col-span-2 md:border-t md:border-line md:pt-10 xl:col-span-1 xl:border-s xl:border-t-0 xl:ps-12 xl:pt-0 2xl:ps-16">
                <h3 class="mb-5 text-sm font-black uppercase tracking-[0.14em] text-navy"><?php echo esc_html($footer['contact_title'] ?? 'Contact Us'); ?></h3>
                <ul class="grid gap-3 md:grid-cols-3 xl:grid-cols-1">
                    <?php foreach ([['phone', $phone, $phone_href], ['mail', $email, 'mailto:' . $email], ['map-pin', $contact['address'] ?? 'Xuzhou, China', boblift_url('/contact')]] as $row) : ?>
                        <li><a class="inline-flex min-w-0 items-start gap-3 text-sm font-medium leading-6 text-slate-600 transition hover:text-blue-700 md:text-[15px]" href="<?php echo esc_url($row[2]); ?>"><?php echo boblift_icon($row[0], 'mt-1 h-4 w-4 shrink-0 text-slate-400', '1.9'); ?><span class="min-w-0 break-words"><?php echo $row[0] === 'map-pin' ? esc_html($row[1]) : '<span dir="ltr">' . esc_html($row[1]) . '</span>'; ?></span></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php
        // The year follows the calendar: a saved "© 2026" or a {year} placeholder always shows the current year.
        $copyright = (string) ($footer['copyright'] ?? '© {year} BOB-LIFT. All Rights Reserved.');
        $copyright = (string) preg_replace('/(©\s*)(?:19|20)\d{2}/u', '${1}' . wp_date('Y'), str_replace('{year}', wp_date('Y'), $copyright));
        // This site is the crane vertical of the BOB-LIFT Group; the sister sites are linked by domain.
        $network_ui = boblift_site_ui('siteSwitcher');
        $network_links = array_values(array_filter((array) ($network_ui['sites'] ?? []), static fn($site): bool => is_array($site) && empty($site['current']) && trim((string) ($site['url'] ?? '')) !== ''));
        ?>
        <div class="flex flex-col gap-3 border-t border-line py-6 text-sm font-semibold leading-6 text-slate-500 lg:flex-row lg:items-center lg:justify-between lg:gap-6">
            <div class="flex flex-col gap-1 sm:flex-row sm:flex-wrap sm:items-center sm:gap-x-3">
                <span><?php echo esc_html($copyright); ?></span>
                <span class="hidden text-slate-300 sm:inline" aria-hidden="true">·</span>
                <span class="flex flex-wrap items-center gap-x-3 gap-y-1"><span><?php echo esc_html((string) ($network_ui['footerNote'] ?? 'Lifting equipment site of the BOB-LIFT Group')); ?></span><?php foreach ($network_links as $site) : ?><a class="inline-flex items-center gap-1 text-navy transition hover:text-blue-700" href="<?php echo esc_url((string) $site['url']); ?>" target="_blank" rel="noopener" title="<?php echo esc_attr((string) $site['label']); ?>"><?php echo esc_html((string) ($site['domain'] ?? '')); ?><?php echo boblift_icon('arrow-up-right', 'h-3.5 w-3.5 text-slate-400'); ?></a><?php endforeach; ?></span>
            </div>
            <span class="flex flex-wrap gap-x-6 gap-y-2 lg:pe-24"><?php foreach ($footer['legal_links'] ?? [] as $link) : ?><a class="hover:text-blue-700" href="<?php echo esc_url(boblift_url($link['href'])); ?>"><?php echo esc_html($link['label']); ?></a><?php endforeach; ?></span>
        </div>
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
<div class="boblift-floating-contact-bar fixed inset-x-0 bottom-0 z-40 flex items-center gap-2 border-t border-line bg-white px-4 pb-[calc(0.5rem+env(safe-area-inset-bottom))] pt-2 lg:inset-x-auto lg:bottom-4 lg:left-1/2 lg:-translate-x-1/2 lg:gap-1.5 lg:rounded-sm lg:border lg:border-white/80 lg:bg-white/[0.88] lg:p-1.5 lg:shadow-raised lg:backdrop-blur-xl min-[1780px]:bottom-auto min-[1780px]:start-auto min-[1780px]:end-4 min-[1780px]:top-1/2 min-[1780px]:-translate-x-0 min-[1780px]:-translate-y-1/2 min-[1780px]:flex-col min-[1780px]:gap-2 min-[1780px]:p-2">
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
        <span class="absolute -end-1 -top-1 grid h-6 min-w-6 place-items-center rounded-sm bg-gold px-1.5 text-[11px] font-black leading-none text-white ring-2 ring-white" data-quote-list-count>0</span>
    </button>
</div>
<?php endif; ?>
<?php if ($show_quote_list) : ?>
<div class="fixed inset-0 z-[240] hidden bg-[#071f42]/45 backdrop-blur-sm" data-quote-list-drawer aria-hidden="true">
    <button class="absolute inset-0 h-full w-full cursor-default" type="button" data-quote-list-close aria-label="<?php echo esc_attr($quote_list_ui['closeLabel'] ?? 'Close Quote List'); ?>"></button>
    <aside class="absolute inset-x-0 bottom-0 flex max-h-[86dvh] flex-col overflow-hidden rounded-t border border-white/70 bg-white shadow-raised md:inset-y-0 md:start-auto md:end-0 md:h-full md:max-h-none md:w-[420px] md:rounded-se-none" role="dialog" aria-modal="true" aria-labelledby="quote-list-title" data-quote-list-panel tabindex="-1">
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
<button class="fixed bottom-8 end-8 z-40 hidden size-11 translate-y-4 rounded-sm bg-navy p-0 text-white opacity-0 shadow-raised transition duration-300 hover:bg-[#0b2d5b] pointer-events-none lg:block" type="button" data-back-top aria-label="<?php echo esc_attr($quote_list_ui['backTopLabel'] ?? 'Back to top'); ?>"><?php echo boblift_icon('arrow-up', 'mx-auto h-5 w-5'); ?></button>
<div class="fixed inset-0 z-50 hidden place-items-center bg-ink/18 p-4 backdrop-blur-[2px]" data-qr-modal>
    <div class="relative w-full max-w-[300px] rounded border border-line bg-white p-5 text-center shadow-card">
        <button class="absolute end-3 top-3 grid size-8 place-items-center rounded-sm bg-[#f4f7fb] text-slate-500 transition hover:bg-navy hover:text-white" type="button" data-qr-close aria-label="<?php echo esc_attr($quote_list_ui['wechatCloseLabel'] ?? 'Close WeChat QR code'); ?>"><?php echo boblift_icon('x', 'h-4 w-4'); ?></button>
        <h2 class="pe-7 text-lg font-black tracking-[-0.01em] text-ink"><?php echo esc_html($quote_list_ui['wechatTitle'] ?? 'WeChat QR Code'); ?></h2>
        <p class="mt-1 text-xs font-semibold text-slate-500"><?php echo esc_html(str_replace('{id}', (string) $wechat_id, (string) ($quote_list_ui['wechatScanTemplate'] ?? 'Scan or search ID: {id}'))); ?></p>
        <div class="mx-auto mt-5 w-[210px] rounded border border-line bg-white p-2 shadow-card"><?php echo boblift_img_from_setting($contact['wechat_qr'] ?? 'images/wechat-qr.png', 'images/wechat-qr.png', $quote_list_ui['wechatTitle'] ?? 'BOB-LIFT WeChat QR code', 'h-auto w-full rounded'); ?></div>
    </div>
</div>
<?php boblift_render_inquiry_modal(); ?>
<?php boblift_render_catalog_modal(); ?>
<?php wp_footer(); ?>
</body>
</html>
