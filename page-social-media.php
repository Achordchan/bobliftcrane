<?php
get_header();

$site_options = function_exists('boblift_options') ? boblift_options() : [];
$social_page = function_exists('boblift_social_media_page_options') ? boblift_social_media_page_options() : [];
$footer_options = is_array($site_options['footer'] ?? null) ? $site_options['footer'] : [];
$social_links = is_array($footer_options['social_links'] ?? null) ? $footer_options['social_links'] : [];
$contact = is_array($site_options['contact'] ?? null) ? $site_options['contact'] : [];
$email = (string) ($contact['email'] ?? 'info@bobliftcrane.com');

$hero = is_array($social_page['hero'] ?? null) ? $social_page['hero'] : [];
$panel = is_array($social_page['panel'] ?? null) ? $social_page['panel'] : [];
$cta = is_array($social_page['cta'] ?? null) ? $social_page['cta'] : [];
$labels = is_array($social_page['labels'] ?? null) ? $social_page['labels'] : [];

$link_map = [];
foreach ($social_links as $link) {
    $label = strtolower(trim((string) ($link['label'] ?? '')));
    $href = trim((string) ($link['href'] ?? ''));
    if ($label !== '' && $href !== '') {
        $link_map[$label] = $href;
    }
}

$platform_href = static function (array $platform) use ($link_map): string {
    $href = trim((string) ($platform['href'] ?? ''));
    if ($href !== '') {
        return $href;
    }
    $key = strtolower(trim((string) ($platform['label'] ?? '')));
    foreach ($link_map as $configured_label => $configured_href) {
        if ($configured_label !== '' && ($key === $configured_label || str_contains($key, $configured_label) || str_contains($configured_label, $key))) {
            return $configured_href;
        }
    }
    return '/contact.html';
};

$platforms = static function (string $key) use ($social_page): array {
    $rows = is_array($social_page[$key] ?? null) ? $social_page[$key] : [];
    return array_values(array_filter($rows, static function ($row): bool {
        return is_array($row) && (string) ($row['enabled'] ?? '1') !== '0' && trim((string) ($row['label'] ?? '')) !== '';
    }));
};

$social_icon = static function (string $icon, string $class = 'h-7 w-7 object-contain'): string {
    $icon = sanitize_key($icon);
    $path = 'images/social-icons/' . $icon . '.svg';
    if ($icon !== '' && function_exists('boblift_asset_path') && is_readable(boblift_asset_path($path))) {
        return '<img src="' . esc_url(boblift_asset($path)) . '" alt="" class="' . esc_attr($class) . '" loading="lazy" decoding="async">';
    }
    return function_exists('boblift_icon') ? boblift_icon('globe', $class) : '';
};

$featured_platforms = $platforms('featured_platforms');
$major_platforms = $platforms('major_platforms');
$secondary_platforms = $platforms('secondary_platforms');
$content_cards = array_values(array_filter(is_array($social_page['content_cards'] ?? null) ? $social_page['content_cards'] : [], static function ($card): bool {
    return is_array($card) && (string) ($card['enabled'] ?? '1') !== '0' && trim((string) ($card['title'] ?? '')) !== '';
}));
$panel_items = array_values(array_filter(is_array($panel['items'] ?? null) ? $panel['items'] : [], static fn($row): bool => is_array($row) && trim((string) ($row['text'] ?? '')) !== ''));
?>
<section class="relative overflow-hidden bg-[linear-gradient(180deg,#F8FAFC_0%,#FFFFFF_68%,#F8FAFC_100%)] py-10 md:py-14 lg:py-20">
    <div class="pointer-events-none absolute -left-28 top-16 h-80 w-80 rounded-full bg-blue-500/10 blur-3xl"></div>
    <div class="page-shell relative">
        <div class="grid gap-8 lg:grid-cols-[minmax(0,0.72fr)_minmax(0,1.28fr)] lg:items-center xl:gap-12">
            <div>
                <p class="section-label mb-5"><?php echo esc_html((string) ($hero['label'] ?? 'BOB-LIFT Social Media')); ?></p>
                <span class="mb-8 block h-1 w-16 rounded-full bg-[#F5A23A]"></span>
                <h1 class="max-w-[760px] text-[clamp(42px,6vw,88px)] font-black leading-[0.98] tracking-[-0.06em] text-[#0B1B33]"><?php echo esc_html((string) ($hero['title'] ?? 'Follow BOB-LIFT official updates.')); ?></h1>
                <p class="mt-7 max-w-[620px] text-sm font-semibold leading-7 text-[#64748B] md:text-base md:leading-8"><?php echo esc_html((string) ($hero['text'] ?? 'Stay connected with BOB-LIFT on the platforms you trust.')); ?></p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a class="inline-flex h-[52px] items-center justify-center gap-3 rounded-[14px] bg-[#0B3F7D] px-7 text-sm font-black text-white shadow-[0_18px_42px_rgba(11,63,125,0.18)] transition hover:-translate-y-0.5 hover:bg-[#082f5f]" href="#official-channels"><?php echo esc_html((string) ($hero['button_label'] ?? 'Explore Channels')); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
                    <?php if ($email !== '') : ?>
                        <a class="inline-flex h-[52px] items-center justify-center gap-3 rounded-[14px] border border-[#DDE7F2] bg-white px-7 text-sm font-black text-[#0B1B33] shadow-[0_14px_34px_rgba(8,35,74,0.06)] transition hover:-translate-y-0.5 hover:border-blue-200 hover:text-blue-700" href="mailto:<?php echo esc_attr($email); ?>"><?php echo boblift_icon('mail', 'h-4 w-4'); ?><?php echo esc_html((string) ($hero['email_label'] ?? 'Email Us')); ?></a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="relative min-h-[360px] overflow-hidden rounded-[34px] border border-[#DDE7F2] bg-white shadow-[0_28px_90px_rgba(11,27,51,0.10)] lg:min-h-[560px]">
                <?php echo boblift_img_from_setting($hero['image'] ?? boblift_image_option('images/social-media/social-media-hero-factory.webp'), 'images/social-media/social-media-hero-factory.webp', (string) ($hero['image_alt'] ?? 'BOB-LIFT factory official social media updates'), 'absolute inset-0 h-full w-full object-cover object-center', ['loading' => 'eager', 'decoding' => 'async', 'fetchpriority' => 'high']); ?>
                <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(255,255,255,0.04)_0%,rgba(11,27,51,0.20)_56%,rgba(11,27,51,0.48)_100%)]"></div>
                <div class="absolute bottom-5 right-5 w-[min(360px,calc(100%-40px))] rounded-[26px] border border-white/35 bg-[#08234A]/86 p-5 text-white shadow-[0_20px_54px_rgba(8,35,74,0.22)] backdrop-blur-xl md:p-6">
                    <p class="text-xl font-black tracking-[-0.04em]"><?php echo esc_html((string) ($panel['title'] ?? 'Official Channels')); ?></p>
                    <?php if ($panel_items !== []) : ?>
                        <div class="mt-4 grid gap-3 text-sm font-semibold text-white/86">
                            <?php foreach ($panel_items as $row) : ?>
                                <div class="flex items-center gap-3 border-t border-white/12 pt-3 first:border-t-0 first:pt-0"><span class="grid h-7 w-7 place-items-center rounded-full border border-white/35 text-white"><?php echo boblift_icon('check', 'h-3.5 w-3.5'); ?></span><?php echo esc_html((string) ($row['text'] ?? '')); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="official-channels" class="bg-white py-12 lg:py-16">
    <div class="page-shell">
        <div class="text-center">
            <p class="section-label mb-4"><?php echo esc_html((string) ($labels['main_platforms_label'] ?? 'Our Main Platforms')); ?></p>
            <h2 class="text-[clamp(30px,4vw,54px)] font-black leading-tight tracking-[-0.045em] text-[#0B1B33]"><?php echo esc_html((string) ($labels['main_platforms_title'] ?? 'Connect with BOB-LIFT where you work.')); ?></h2>
            <p class="mx-auto mt-4 max-w-[680px] text-sm font-semibold leading-7 text-[#64748B] md:text-base"><?php echo esc_html((string) ($labels['main_platforms_text'] ?? 'Current key channels are highlighted first. More domestic and international platforms are listed below with lighter visual weight.')); ?></p>
        </div>

        <?php if ($featured_platforms !== []) : ?>
            <div class="mt-8 grid gap-4 md:grid-cols-3">
                <?php foreach ($featured_platforms as $platform) : $href = $platform_href($platform); ?>
                    <a class="group flex min-h-[230px] flex-col justify-between rounded-[28px] p-5 text-white shadow-[0_22px_60px_rgba(8,35,74,0.10)] transition hover:-translate-y-1 hover:shadow-[0_26px_70px_rgba(8,35,74,0.14)] md:p-6 <?php echo esc_attr((string) ($platform['tone'] ?? 'bg-[#0B3F7D] text-white')); ?>" href="<?php echo esc_url(boblift_url($href)); ?>">
                        <span class="grid h-16 w-16 place-items-center rounded-[22px] bg-white/18 p-3 ring-1 ring-white/25"><?php echo $social_icon((string) ($platform['icon'] ?? ''), 'h-9 w-9 object-contain'); ?></span>
                        <span class="block">
                            <span class="block text-2xl font-black tracking-[-0.04em]"><?php echo esc_html((string) ($platform['label'] ?? '')); ?></span>
                            <span class="mt-3 block text-sm font-semibold leading-6 text-white/82"><?php echo esc_html((string) ($platform['text'] ?? '')); ?></span>
                            <span class="mt-5 inline-flex items-center gap-2 text-sm font-black"><?php echo esc_html((string) ($labels['featured_visit_label'] ?? 'Visit channel')); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($major_platforms !== []) : ?>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <?php foreach ($major_platforms as $platform) : $href = $platform_href($platform); ?>
                    <a class="group flex min-h-[168px] flex-col justify-between rounded-[26px] border border-[#DDE7F2] bg-[#F8FBFF] p-5 shadow-[0_18px_48px_rgba(8,35,74,0.055)] transition hover:-translate-y-0.5 hover:border-blue-200 hover:bg-white" href="<?php echo esc_url(boblift_url($href)); ?>">
                        <span class="grid h-14 w-14 place-items-center rounded-[18px] p-3 <?php echo esc_attr((string) ($platform['tone'] ?? 'bg-[#0B3F7D] text-white')); ?>"><?php echo $social_icon((string) ($platform['icon'] ?? ''), 'h-8 w-8 object-contain'); ?></span>
                        <span class="block">
                            <span class="block text-lg font-black leading-6 tracking-[-0.035em] text-[#0B1B33]"><?php echo esc_html((string) ($platform['label'] ?? '')); ?></span>
                            <span class="mt-3 inline-flex items-center gap-2 text-xs font-black uppercase tracking-[0.14em] text-blue-700"><?php echo esc_html((string) ($labels['major_visit_label'] ?? 'Visit')); ?><?php echo boblift_icon('arrow-right', 'h-3.5 w-3.5'); ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($secondary_platforms !== []) : ?>
    <section class="bg-[#F8FAFC] py-12 lg:py-16">
        <div class="page-shell">
            <div class="overflow-hidden rounded-[34px] border border-[#DDE7F2] bg-white p-5 shadow-[0_24px_90px_rgba(11,27,51,0.06)] md:p-8 lg:p-10">
                <div class="text-center">
                    <p class="section-label mb-4"><?php echo esc_html((string) ($labels['more_channels_label'] ?? 'More Channels')); ?></p>
                    <h2 class="text-[clamp(28px,3.4vw,46px)] font-black leading-tight tracking-[-0.04em] text-[#0B1B33]"><?php echo esc_html((string) ($labels['more_channels_title'] ?? 'Additional platforms around the world.')); ?></h2>
                </div>
                <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-10">
                    <?php foreach ($secondary_platforms as $platform) : $href = $platform_href($platform); ?>
                        <a class="group flex min-h-[104px] flex-col items-center justify-center rounded-[22px] border border-[#E3ECF6] bg-[#F8FBFF] p-3 text-center transition hover:-translate-y-0.5 hover:border-blue-200 hover:bg-white hover:shadow-[0_16px_38px_rgba(8,35,74,0.07)]" href="<?php echo esc_url(boblift_url($href)); ?>" aria-label="<?php echo esc_attr((string) ($platform['label'] ?? '')); ?>">
                            <span class="grid h-11 w-11 place-items-center rounded-2xl p-2.5 <?php echo esc_attr((string) ($platform['tone'] ?? 'bg-[#0B3F7D] text-white')); ?>"><?php echo $social_icon((string) ($platform['icon'] ?? ''), 'h-6 w-6 object-contain'); ?></span>
                            <span class="mt-3 line-clamp-2 text-xs font-black leading-4 text-[#0B1B33]"><?php echo esc_html((string) ($platform['label'] ?? '')); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if ($content_cards !== []) : ?>
    <section class="bg-white py-12 lg:py-20">
        <div class="page-shell">
            <div class="text-center">
                <p class="section-label mb-4"><?php echo esc_html((string) ($labels['content_cards_label'] ?? 'What You Can Follow')); ?></p>
                <h2 class="text-[clamp(30px,4vw,54px)] font-black leading-tight tracking-[-0.045em] text-[#0B1B33]"><?php echo esc_html((string) ($labels['content_cards_title'] ?? 'Real content from real work.')); ?></h2>
            </div>
            <div class="mt-8 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                <?php foreach ($content_cards as $card) : ?>
                    <article class="group overflow-hidden rounded-[28px] border border-[#DDE7F2] bg-white shadow-[0_18px_54px_rgba(8,35,74,0.06)] transition hover:-translate-y-0.5 hover:shadow-[0_24px_70px_rgba(8,35,74,0.10)]">
                        <div class="relative h-44 overflow-hidden bg-[#EAF1F8]">
                            <?php echo boblift_img_from_setting($card['image'] ?? boblift_image_option('images/social-media/social-media-factory-news.webp'), 'images/social-media/social-media-factory-news.webp', (string) ($card['title'] ?? ''), 'absolute inset-0 h-full w-full object-cover object-center transition duration-700 group-hover:scale-[1.035]', ['loading' => 'lazy', 'decoding' => 'async']); ?>
                        </div>
                        <div class="p-5 md:p-6">
                            <span class="-mt-12 grid h-14 w-14 place-items-center rounded-2xl border border-[#DDE7F2] bg-white text-blue-700 shadow-[0_14px_34px_rgba(8,35,74,0.08)]"><?php echo boblift_icon((string) ($card['icon'] ?? 'package-check'), 'h-6 w-6'); ?></span>
                            <h3 class="mt-5 text-xl font-black tracking-[-0.035em] text-[#0B1B33]"><?php echo esc_html((string) ($card['title'] ?? '')); ?></h3>
                            <p class="mt-3 text-sm font-semibold leading-6 text-[#64748B]"><?php echo esc_html((string) ($card['text'] ?? '')); ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<section class="bg-white pb-14 lg:pb-20">
    <div class="page-shell">
        <div class="relative overflow-hidden rounded-[34px] border border-[#0D4F94]/20 bg-[linear-gradient(135deg,#073A78_0%,#0B5AA8_58%,#084785_100%)] p-6 text-white shadow-[0_26px_80px_rgba(11,63,125,0.18)] md:p-8 lg:p-10">
            <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-white/15 blur-3xl"></div>
            <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <span class="mb-5 block h-1 w-16 rounded-full bg-[#F5A23A]"></span>
                    <p class="text-xs font-black uppercase tracking-[0.28em] text-white/70"><?php echo esc_html((string) ($cta['label'] ?? 'Direct Consultation')); ?></p>
                    <h2 class="mt-4 text-[clamp(30px,4vw,54px)] font-black leading-[1.04] tracking-[-0.045em]"><?php echo esc_html((string) ($cta['title'] ?? 'Need a direct reply from BOB-LIFT?')); ?></h2>
                    <p class="mt-4 max-w-[680px] text-sm font-semibold leading-7 text-white/78 md:text-base"><?php echo esc_html((string) ($cta['text'] ?? 'Share your lifting task and our team will reply through the contact channel you provide.')); ?></p>
                </div>
                <a class="inline-flex h-[58px] shrink-0 items-center justify-center gap-3 rounded-[18px] bg-white px-7 text-sm font-black text-[#0B3F7D] shadow-[0_18px_44px_rgba(255,255,255,0.18)] transition hover:-translate-y-0.5" href="<?php echo esc_url(boblift_url((string) ($cta['button_href'] ?? '/contact.html'))); ?>"><?php echo esc_html((string) ($cta['button_label'] ?? 'Contact Us')); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>
