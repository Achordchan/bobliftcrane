<?php
/**
 * Template Name: BOB-LIFT Social Media
 */
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
<section class="relative overflow-hidden bg-[#071f42]" aria-label="<?php echo esc_attr((string) ($hero['label'] ?? 'BOB-LIFT Social Media')); ?>">
    <div class="relative h-[42vh] min-h-[320px] max-h-[560px] w-full">
        <?php echo boblift_img_from_setting($hero['image'] ?? boblift_image_option('images/social-media/social-media-hero-factory.webp'), 'images/social-media/social-media-hero-factory.webp', (string) ($hero['image_alt'] ?? 'BOB-LIFT factory official social media updates'), 'absolute inset-0 h-full w-full object-cover object-center', ['loading' => 'eager', 'decoding' => 'async', 'fetchpriority' => 'high']); ?>
    </div>
</section>

<section id="official-channels" class="bg-white py-12 lg:py-16">
    <div class="page-shell">
        <div class="text-center">
            <p class="section-label mb-4"><?php echo esc_html((string) ($labels['main_platforms_label'] ?? 'Our Main Platforms')); ?></p>
            <h2 class="text-[clamp(30px,4vw,54px)] font-black leading-tight tracking-[-0.015em] text-[#0B1B33]"><?php echo esc_html((string) ($labels['main_platforms_title'] ?? 'Connect with BOB-LIFT where you work.')); ?></h2>
            <p class="mx-auto mt-4 max-w-[680px] text-sm font-semibold leading-7 text-[#64748B] md:text-base"><?php echo esc_html((string) ($labels['main_platforms_text'] ?? 'Current key channels are highlighted first. More domestic and international platforms are listed below with lighter visual weight.')); ?></p>
        </div>

        <?php
        $all_main_platforms = array_merge($featured_platforms, $major_platforms);
        if ($all_main_platforms !== []) :
        ?>
            <div class="mt-12 grid auto-rows-[280px] gap-4 md:grid-cols-2 xl:grid-cols-4 lg:gap-6">
                <?php foreach ($all_main_platforms as $index => $platform) :
                    $href = $platform_href($platform);
                ?>
                    <a class="group relative flex flex-col justify-between overflow-hidden rounded p-8 text-white shadow-card transition duration-500 hover:-translate-y-2 col-span-1 row-span-1" href="<?php echo esc_url(boblift_url($href)); ?>">
                        <!-- Background -->
                        <div class="absolute inset-0 transition duration-700 group-hover:scale-105 <?php echo esc_attr((string) ($platform['tone'] ?? 'bg-[#0B3F7D]')); ?>"></div>
                        <!-- Inner content -->
                        <div class="relative z-10 flex h-full flex-col justify-between">
                            <span class="grid h-20 w-20 place-items-center rounded bg-white/15 p-4 ring-1 ring-white/20 backdrop-blur-md transition duration-300 group-hover:scale-110 group-hover:bg-white/30 group-hover:ring-white/40 shadow-card">
                                <?php echo $social_icon((string) ($platform['icon'] ?? ''), 'h-10 w-10 object-contain drop-shadow-md'); ?>
                            </span>
                            <div class="mt-auto pr-2">
                                <span class="block text-[28px] font-black tracking-[-0.01em]"><?php echo esc_html((string) ($platform['label'] ?? '')); ?></span>
                                <?php if (!empty($platform['text'])) : ?>
                                <span class="mt-3 block text-sm font-semibold leading-relaxed text-white/85 line-clamp-2"><?php echo esc_html($platform['text']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <!-- Hover Action Button (Top Right) -->
                        <div class="absolute right-8 top-8 z-10 -translate-x-4 opacity-0 transition duration-300 group-hover:translate-x-0 group-hover:opacity-100">
                            <div class="grid h-12 w-12 place-items-center rounded-sm bg-white text-[#0B1B33] shadow-card">
                                <?php echo boblift_icon('arrow-right', 'h-5 w-5'); ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($secondary_platforms !== []) : ?>
    <section class="bg-white py-12 lg:py-20" data-social-marquee>
        <div class="page-shell text-center mb-10">
            <p class="section-label mb-4"><?php echo esc_html((string) ($labels['more_channels_label'] ?? 'More Channels')); ?></p>
            <h2 class="text-[clamp(28px,3.4vw,46px)] font-black leading-tight tracking-[-0.015em] text-[#0B1B33]"><?php echo esc_html((string) ($labels['more_channels_title'] ?? 'Additional platforms around the world.')); ?></h2>
        </div>

        <div class="relative flex w-full overflow-hidden">
            <!-- Fade overlays for the edges -->
            <div class="pointer-events-none absolute bottom-0 left-0 top-0 z-10 w-24 bg-gradient-to-r from-white to-transparent"></div>
            <div class="pointer-events-none absolute bottom-0 right-0 top-0 z-10 w-24 bg-gradient-to-l from-white to-transparent"></div>

            <div class="flex shrink-0 items-center gap-4 px-2 md:gap-6 md:px-3" data-marquee-track>
                <?php foreach ($secondary_platforms as $platform) : $href = $platform_href($platform); ?>
                    <a class="group flex h-20 w-52 shrink-0 items-center gap-4 rounded border border-[#E5E7EB] bg-[#F8FAFC] p-4 transition hover:-translate-y-1 hover:border-blue-200 hover:bg-white " href="<?php echo esc_url(boblift_url($href)); ?>" aria-label="<?php echo esc_attr((string) ($platform['label'] ?? '')); ?>">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-sm p-2.5 <?php echo esc_attr((string) ($platform['tone'] ?? 'bg-[#0B3F7D] text-white')); ?>"><?php echo $social_icon((string) ($platform['icon'] ?? ''), 'h-6 w-6 object-contain'); ?></span>
                        <span class="line-clamp-2 text-sm font-black leading-tight text-[#0B1B33]"><?php echo esc_html((string) ($platform['label'] ?? '')); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <!-- Duplicate for seamless scroll -->
            <div class="flex shrink-0 items-center gap-4 px-2 md:gap-6 md:px-3" data-marquee-track aria-hidden="true">
                <?php foreach ($secondary_platforms as $platform) : $href = $platform_href($platform); ?>
                    <a class="group flex h-20 w-52 shrink-0 items-center gap-4 rounded border border-[#E5E7EB] bg-[#F8FAFC] p-4 transition hover:-translate-y-1 hover:border-blue-200 hover:bg-white " href="<?php echo esc_url(boblift_url($href)); ?>" aria-label="<?php echo esc_attr((string) ($platform['label'] ?? '')); ?>">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-sm p-2.5 <?php echo esc_attr((string) ($platform['tone'] ?? 'bg-[#0B3F7D] text-white')); ?>"><?php echo $social_icon((string) ($platform['icon'] ?? ''), 'h-6 w-6 object-contain'); ?></span>
                        <span class="line-clamp-2 text-sm font-black leading-tight text-[#0B1B33]"><?php echo esc_html((string) ($platform['label'] ?? '')); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if ($content_cards !== []) : ?>
    <section class="bg-white py-16 lg:py-32">
        <div class="page-shell">
            <div class="text-center">
                <p class="section-label mb-4"><?php echo esc_html((string) ($labels['content_cards_label'] ?? 'What You Can Follow')); ?></p>
                <h2 class="text-[clamp(30px,4vw,54px)] font-black leading-tight tracking-[-0.015em] text-[#0B1B33]"><?php echo esc_html((string) ($labels['content_cards_title'] ?? 'Real content from real work.')); ?></h2>
            </div>
            <div class="mt-16 grid gap-6 md:grid-cols-2 xl:grid-cols-4" style="perspective: 1200px;" data-social-cards>
                <?php foreach ($content_cards as $card) : ?>
                    <article class="group relative overflow-hidden rounded border border-[#E5E7EB] bg-white shadow-card transition hover:-translate-y-2 " data-social-card>
                        <div class="relative h-60 overflow-hidden bg-[#F8FAFC]">
                            <?php echo boblift_img_from_setting($card['image'] ?? boblift_image_option('images/social-media/social-media-factory-news.webp'), 'images/social-media/social-media-factory-news.webp', (string) ($card['title'] ?? ''), 'absolute inset-0 h-full w-full object-cover object-center transition duration-700 group-hover:scale-[1.05]', ['loading' => 'lazy', 'decoding' => 'async']); ?>
                        </div>
                        <div class="p-6 md:p-8">
                            <span class="absolute right-6 top-48 z-10 grid h-16 w-16 place-items-center rounded border-4 border-white bg-[#0B3F7D] text-white shadow-raised"><?php echo boblift_icon((string) ($card['icon'] ?? 'package-check'), 'h-7 w-7'); ?></span>
                            <h3 class="mt-4 text-xl font-black tracking-[-0.01em] text-[#0B1B33] lg:text-2xl"><?php echo esc_html((string) ($card['title'] ?? '')); ?></h3>
                            <p class="mt-4 text-sm font-semibold leading-relaxed text-[#64748B]"><?php echo esc_html((string) ($card['text'] ?? '')); ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<section class="relative mt-20 overflow-hidden bg-[#0B1B33] py-24 text-white lg:py-32">
    <!-- Glowing Aura -->
    <div class="pointer-events-none absolute left-1/2 top-1/2 h-[600px] w-[800px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-blue-500/10 blur-[100px]" aria-hidden="true"></div>

    <div class="page-shell relative z-10 text-center">
        <p class="mb-5 text-xs font-black uppercase tracking-[0.42em] text-[#3B82F6]"><?php echo esc_html((string) ($cta['label'] ?? 'Direct Consultation')); ?></p>
        <h2 class="mx-auto max-w-[800px] text-[clamp(34px,4.5vw,64px)] font-black leading-[1.04] tracking-[-0.015em]"><?php echo esc_html((string) ($cta['title'] ?? 'Need a direct reply from BOB-LIFT?')); ?></h2>
        <p class="mx-auto mt-6 max-w-[680px] text-base font-semibold leading-relaxed text-[#94A3B8] md:text-lg"><?php echo esc_html((string) ($cta['text'] ?? 'Share your lifting task and our team will reply through the contact channel you provide.')); ?></p>
        <div class="mt-12 flex justify-center">
            <a class="group inline-flex h-[60px] items-center justify-center gap-4 rounded-sm bg-[#2563EB] px-10 text-base font-black text-white shadow-card transition duration-300 hover:-translate-y-1 hover:bg-[#1D4ED8] " href="<?php echo esc_url(boblift_url((string) ($cta['button_href'] ?? '/contact.html'))); ?>">
                <?php echo esc_html((string) ($cta['button_label'] ?? 'Contact Us')); ?>
                <span class="transition duration-300 group-hover:translate-x-1"><?php echo boblift_icon('arrow-right', 'h-5 w-5'); ?></span>
            </a>
        </div>
    </div>
</section>

<?php get_footer(); ?>
