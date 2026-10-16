<?php
get_header();
$options = boblift_options();
$home = $options['home'] ?? [];
$home_ui = function_exists('boblift_site_ui') ? boblift_site_ui('homePage') : [];
$about_page_options = function_exists('boblift_about_page_options') ? boblift_about_page_options() : [];
$customer_visits = is_array($about_page_options['customer_visits'] ?? null) ? $about_page_options['customer_visits'] : [];
$hero_slides = $home['hero_slides'] ?? [];
$trust = $home['trust_bar'] ?? [];
$about = $home['about'] ?? [];
$articles = array_slice(boblift_articles(), 0, 6);
$gallery = $about['gallery'] ?? [];
$default_gallery_alts = is_array($home_ui['defaultGalleryAlts'] ?? null) ? $home_ui['defaultGalleryAlts'] : [];
$default_gallery = [
    ['src' => 'images/truck-mounted-crane.png', 'alt' => (string) ($default_gallery_alts[0] ?? 'Truck mounted crane')],
    ['src' => 'images/factory-workshop.jpg', 'alt' => (string) ($default_gallery_alts[1] ?? 'BOB-LIFT factory video preview'), 'video' => true],
    ['src' => 'images/marine-scene.jpg', 'alt' => (string) ($default_gallery_alts[2] ?? 'Marine crane project')],
    ['src' => 'images/telescopic-banner.jpg', 'alt' => (string) ($default_gallery_alts[3] ?? 'Telescopic boom crane')],
    ['src' => 'images/knuckle-banner.jpg', 'alt' => (string) ($default_gallery_alts[4] ?? 'Knuckle boom crane')],
];
if (count($gallery) < 5) {
    $gallery = array_values(array_replace($default_gallery, $gallery));
}
$active_gallery_index = min(1, max(0, count($gallery) - 1));
$active_gallery = $gallery[$active_gallery_index] ?? $default_gallery[0];
$active_gallery_type = (string) ($active_gallery['type'] ?? 'image');
$active_gallery_poster = boblift_image_setting_url($active_gallery['src'] ?? '', $default_gallery[$active_gallery_index]['src'] ?? 'images/truck-mounted-crane.png');
$active_gallery_video = boblift_media_setting_url($active_gallery['video_file'] ?? '', '');
$active_gallery_youtube = boblift_youtube_embed_url((string) ($active_gallery['youtube_url'] ?? ''));
$active_gallery_is_local_video = $active_gallery_type === 'local_video' && $active_gallery_video !== '';
$active_gallery_is_youtube = $active_gallery_type === 'youtube' && $active_gallery_youtube !== '';
?>
<section class="js-hero relative aspect-[12/5] w-full overflow-hidden bg-white" aria-label="<?php echo esc_attr($home_ui['heroAriaLabel'] ?? 'Featured lifting solutions'); ?>">
    <div class="absolute inset-0 h-full w-full">
        <?php foreach ($hero_slides as $index => $slide) : ?>
            <?php $mobile_image = boblift_image_setting_url($slide['mobile_image'] ?? '', ''); ?>
            <picture class="hero-slide absolute inset-0 block h-full w-full <?php echo $index === 0 ? 'opacity-100 scale-100' : 'opacity-0'; ?>" data-hero-slide>
                <?php if ($mobile_image !== '') : ?><source media="(max-width: 767px)" srcset="<?php echo esc_url($mobile_image); ?>"><?php endif; ?>
                <?php echo boblift_img_from_setting($slide['image'] ?? '', 'images/marine-scene.jpg', '', 'h-full w-full object-contain object-center transition duration-700 ease-out', $index === 0 ? ['loading' => 'eager', 'decoding' => 'async', 'fetchpriority' => 'high'] : ['loading' => 'lazy', 'decoding' => 'async']); ?>
            </picture>
        <?php endforeach; ?>
    </div>
    <?php if (count($hero_slides) > 1) : ?>
        <button class="absolute start-4 top-1/2 z-20 grid h-7 w-7 -translate-y-1/2 place-items-center rounded-2xl border border-blue-100 bg-white/95 text-lg font-black leading-none text-[#08234a] shadow-[0_14px_34px_rgba(8,35,74,0.14)] backdrop-blur transition duration-300 hover:-translate-y-[calc(50%+2px)] hover:border-blue-300 hover:text-blue-700 md:start-8 md:h-10 md:w-10 lg:start-10" type="button" data-hero-prev aria-label="<?php echo esc_attr($home_ui['previousBannerLabel'] ?? 'Previous banner'); ?>"><?php echo boblift_icon('chevron-left', 'h-4 w-4 md:h-5 md:w-5 rtl:-scale-x-100'); ?></button>
        <button class="absolute end-4 top-1/2 z-20 grid h-7 w-7 -translate-y-1/2 place-items-center rounded-2xl border border-blue-100 bg-white/95 text-lg font-black leading-none text-[#08234a] shadow-[0_14px_34px_rgba(8,35,74,0.14)] backdrop-blur transition duration-300 hover:-translate-y-[calc(50%+2px)] hover:border-blue-300 hover:text-blue-700 md:end-8 md:h-10 md:w-10 lg:end-10" type="button" data-hero-next aria-label="<?php echo esc_attr($home_ui['nextBannerLabel'] ?? 'Next banner'); ?>"><?php echo boblift_icon('chevron-right', 'h-4 w-4 md:h-5 md:w-5 rtl:-scale-x-100'); ?></button>
        <div class="absolute bottom-4 left-1/2 z-10 flex -translate-x-1/2 items-center gap-1 rounded-full border border-white/35 bg-white/40 px-2.5 py-1.5 shadow-[0_10px_28px_rgba(8,35,74,0.16)] backdrop-blur-sm md:bottom-8 md:gap-2 md:px-3 md:py-2 lg:bottom-10" data-hero-dots>
            <?php foreach ($hero_slides as $index => $slide) : ?>
                <button class="h-1.5 rounded-full transition-all duration-300 md:h-2 <?php echo $index === 0 ? 'w-7 bg-gold md:w-9' : 'w-1.5 bg-white/90 hover:bg-white md:w-2'; ?>" type="button" data-hero-dot="<?php echo esc_attr((string) $index); ?>" aria-label="<?php echo esc_attr(str_replace('{number}', (string) ($index + 1), (string) ($home_ui['showBannerTemplate'] ?? 'Show banner {number}'))); ?>" aria-current="<?php echo $index === 0 ? 'true' : 'false'; ?>"></button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="page-shell relative z-10 mt-0 max-w-[1500px] overflow-hidden border-y border-line/70 bg-transparent py-2 md:-mt-8 md:rounded-[28px] md:border md:border-white/80 md:bg-white/95 md:p-3 md:py-3 md:shadow-[0_24px_70px_rgba(8,35,74,0.12)] md:ring-1 md:ring-slate-900/5 lg:-mt-12 lg:rounded-[34px]" aria-label="<?php echo esc_attr($home_ui['qualityCertificationsLabel'] ?? 'Quality certifications'); ?>">
    <div class="grid grid-cols-4 items-center gap-0 md:grid-cols-5 md:items-stretch md:gap-2">
        <div class="hidden flex-col gap-4 overflow-hidden justify-center py-0 text-sm text-white ring-white/10 md:flex md:min-h-[118px] md:rounded-[22px] md:bg-gradient-to-br md:from-navy md:to-[#0d356b] xl:min-h-[132px]">
            <div class="grid auto-rows-min items-start gap-1.5 px-5 py-5 xl:px-6">
                <span class="inline-flex h-6 w-fit max-w-full shrink-0 items-center justify-center gap-1 overflow-hidden whitespace-nowrap rounded-full border border-transparent bg-white/10 px-3 py-0.5 text-[10px] font-black uppercase tracking-[0.22em] text-gold ring-1 ring-white/10"><?php echo esc_html($trust['label'] ?? 'Certified'); ?></span>
                <div class="text-xl font-black leading-tight text-white"><?php echo esc_html($trust['title'] ?? 'Quality Assurance'); ?></div>
                <div class="max-w-[190px] text-xs font-semibold leading-5 text-white/62 xl:max-w-none xl:whitespace-nowrap"><?php echo esc_html($trust['description'] ?? 'Export-ready documentation.'); ?></div>
            </div>
        </div>
        <?php foreach (($trust['marks'] ?? []) as $mark) : ?>
            <div class="flex min-w-0 flex-col gap-3 overflow-hidden rounded-none border-0 bg-transparent py-3 text-center text-sm shadow-none ring-0 transition duration-300 hover:-translate-y-0.5 md:min-h-[118px] md:rounded-[22px] md:bg-gradient-to-br md:from-white md:to-[#f7faff] md:ring-1 md:ring-line md:hover:-translate-y-1 md:hover:ring-gold/45 md:hover:shadow-[0_18px_44px_rgba(8,35,74,0.1)] xl:min-h-[132px]">
                <div class="flex h-full flex-col items-center justify-center border-s border-line/65 px-3 py-1 md:border-s-0 md:px-3 md:py-4">
                    <div class="grid h-7 w-full place-items-center md:h-14"><?php echo boblift_img_from_setting($mark['logo'] ?? '', '', (function_exists('boblift_localized_static_text') ? boblift_localized_static_text((string) ($mark['name'] ?? '')) : ($mark['name'] ?? '')) . ' logo', $mark['logo_class'] ?? 'h-8 w-24 max-w-full object-contain md:h-12 md:w-32'); ?></div>
                    <p class="mt-2 hidden text-xs font-bold leading-5 text-slate-500 md:line-clamp-2"><?php echo esc_html(function_exists('boblift_localized_static_text') ? boblift_localized_static_text((string) ($mark['name'] ?? '')) : ($mark['name'] ?? '')); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php boblift_render_home_products(); ?>

<section id="about" class="page-shell grid grid-cols-1 gap-7 overflow-hidden py-8 md:gap-10 md:py-14 lg:grid-cols-[0.95fr_1.05fr] lg:items-start lg:gap-9 lg:py-12 xl:grid-cols-[1fr_0.95fr] xl:items-stretch xl:gap-20 xl:py-14" data-about-gallery>
    <div class="flex h-full min-h-0 flex-col">
        <div class="relative h-[220px] overflow-hidden rounded-[22px] shadow-card sm:h-[340px] lg:h-[430px] lg:min-h-0 lg:flex-none xl:h-auto xl:min-h-[460px] xl:flex-1" data-about-active-stage>
            <?php if ($active_gallery_is_youtube) : ?>
                <iframe class="h-full w-full bg-slate-100" src="about:blank" data-lazy-iframe-src="<?php echo esc_url($active_gallery_youtube); ?>" title="<?php echo esc_attr($active_gallery['alt'] ?? 'BOB-LIFT video'); ?>" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
            <?php elseif ($active_gallery_is_local_video) : ?>
                <video class="h-full w-full object-cover" controls preload="metadata" poster="<?php echo esc_url($active_gallery_poster); ?>"><source src="<?php echo esc_url($active_gallery_video); ?>"></video>
            <?php else : ?>
                <img src="<?php echo esc_url($active_gallery_poster); ?>" alt="<?php echo esc_attr($active_gallery['alt'] ?? ''); ?>" class="h-full w-full object-cover" loading="lazy" decoding="async" data-about-active-image>
            <?php endif; ?>
            <span class="pointer-events-none absolute left-1/2 top-1/2 <?php echo ($active_gallery_is_youtube || $active_gallery_is_local_video) ? 'grid' : 'hidden'; ?> size-14 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-white/94 text-gold shadow-[0_18px_50px_rgba(8,35,74,0.18)] ring-1 ring-white/70 sm:size-20" data-about-play><?php echo boblift_icon('circle-play', 'size-7 fill-gold sm:size-10'); ?></span>
        </div>
        <div class="mt-3 grid grid-cols-5 gap-2 sm:mt-4 sm:gap-3 lg:mt-3 lg:gap-2 xl:mt-4 xl:gap-3">
            <?php foreach ($gallery as $index => $item) : ?>
                <?php
                $gallery_image = boblift_image_setting_url($item['src'] ?? '', $default_gallery[$index]['src'] ?? 'images/truck-mounted-crane.png');
                $gallery_type = (string) ($item['type'] ?? 'image');
                $gallery_video = boblift_media_setting_url($item['video_file'] ?? '', '');
                $gallery_youtube = boblift_youtube_embed_url((string) ($item['youtube_url'] ?? ''));
                $gallery_is_video = ($gallery_type === 'local_video' && $gallery_video !== '') || ($gallery_type === 'youtube' && $gallery_youtube !== '');
                ?>
                <button class="relative h-12 overflow-hidden rounded-lg border shadow-card transition duration-200 hover:-translate-y-0.5 sm:h-16 lg:h-12 xl:h-16 <?php echo $index === $active_gallery_index ? 'border-blue-500 ring-2 ring-blue-500/20' : 'border-white'; ?>" type="button" data-about-thumb data-type="<?php echo esc_attr($gallery_type); ?>" data-src="<?php echo esc_url($gallery_image); ?>" data-alt="<?php echo esc_attr($item['alt'] ?? ''); ?>" data-video-src="<?php echo esc_url($gallery_video); ?>" data-youtube-src="<?php echo esc_url($gallery_youtube); ?>" data-video="<?php echo $gallery_is_video ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr(str_replace('{item}', (string) ($item['alt'] ?? ($home_ui['galleryFallbackLabel'] ?? 'gallery image')), (string) ($home_ui['showGalleryTemplate'] ?? 'Show {item}'))); ?>">
                    <img src="<?php echo esc_url($gallery_image); ?>" alt="<?php echo esc_attr($item['alt'] ?? ''); ?>" class="h-full w-full object-cover" loading="lazy" decoding="async">
                    <?php if ($gallery_is_video) : ?><span class="absolute bottom-1 end-1 rounded-full bg-navy/85 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-[0.08em] text-white shadow-card sm:bottom-1.5 sm:end-1.5 sm:px-2 sm:text-[9px]"><?php echo esc_html($home_ui['videoLabel'] ?? 'Video'); ?></span><?php endif; ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="pt-0 lg:pt-0 xl:pt-[25px]">
        <p class="section-label mb-3 md:mb-4 lg:mb-3 xl:mb-4"><?php echo esc_html($about['label'] ?? 'About BOB-LIFT'); ?></p>
        <h2 class="max-w-none text-[clamp(29px,7.1vw,36px)] font-black leading-[1.02] tracking-[-0.025em] text-ink sm:text-4xl sm:leading-tight sm:tracking-[-0.018em] lg:text-[34px] lg:leading-[1.08] xl:text-4xl xl:leading-tight"><?php echo esc_html($about['title'] ?? 'Engineering Excellence. Powered by People.'); ?></h2>
        <p class="mt-4 text-sm leading-7 text-slate-600 sm:mt-6 sm:text-base sm:leading-8 lg:mt-4 lg:text-sm lg:leading-6 xl:mt-6 xl:text-base xl:leading-8"><?php echo esc_html($about['text'] ?? ''); ?></p>
        <div class="mt-6 grid grid-cols-3 gap-2 sm:mt-8 sm:gap-3 lg:mt-5 lg:gap-2 xl:mt-8 xl:gap-3">
            <?php $stat_icons = ['award', 'globe', 'package-check']; foreach (($about['stats'] ?? []) as $index => $stat) : ?>
                <div class="flex flex-col gap-3 overflow-hidden rounded-2xl border-line bg-gradient-to-br from-white to-[#f7faff] py-3 text-sm text-card-foreground shadow-[0_14px_36px_rgba(8,35,74,0.06)] ring-0 lg:py-2 xl:py-3">
                    <div class="flex h-full flex-col items-center justify-center gap-2 p-3 px-3 text-center sm:flex-row sm:justify-start sm:gap-3 sm:p-4 sm:px-3 sm:text-start lg:gap-2 lg:p-3 xl:gap-3 xl:p-4 xl:px-3">
                        <span class="grid size-8 shrink-0 place-items-center rounded-full border border-gold/45 bg-gold/8 text-gold sm:size-10 lg:size-8 xl:size-10"><?php echo boblift_icon($stat['icon_name'] ?? $stat['iconName'] ?? $stat_icons[$index] ?? 'award', 'size-4 xl:size-5'); ?></span>
                        <p><strong class="block text-xl font-black leading-none text-ink sm:text-2xl lg:text-xl xl:text-2xl"><?php echo esc_html($stat['value'] ?? ''); ?></strong><span class="mt-1 block text-[10px] font-bold leading-3 text-slate-500 sm:text-xs sm:leading-4 lg:text-[11px] lg:leading-4 xl:text-xs"><?php echo esc_html($stat['label'] ?? ''); ?></span></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-7 grid grid-cols-2 gap-4 sm:mt-10 sm:grid-cols-4 sm:gap-5 lg:mt-7 lg:gap-4 xl:mt-10 xl:gap-6">
            <?php foreach (($about['timeline'] ?? []) as $item) : $parts = explode(' ', (string) $item, 2); ?>
                <div class="relative border-t border-line pt-6 lg:pt-5 xl:pt-6"><span class="absolute -top-2 start-0 h-4 w-4 rounded-full border-4 border-white bg-gold shadow-card"></span><strong class="block text-sm font-black text-ink"><?php echo esc_html($parts[0] ?? ''); ?></strong><p class="mt-2 text-xs font-bold leading-5 text-slate-500 lg:mt-1.5 lg:leading-4 xl:mt-2 xl:leading-5"><?php echo esc_html($parts[1] ?? ''); ?></p></div>
            <?php endforeach; ?>
        </div>
        <a class="mt-9 inline-flex items-center gap-2 text-base font-black text-ink md:text-lg lg:mt-6 xl:mt-9" href="<?php echo esc_url(boblift_url($about['cta_href'] ?? '/about')); ?>"><?php echo esc_html($about['cta_label'] ?? 'More About Us'); ?> <?php echo boblift_icon('arrow-right', 'h-5 w-5'); ?></a>
    </div>
</section>

<?php boblift_render_customer_visits_section($customer_visits, 'home'); ?>

<?php if (!empty($articles)) : $news_items = array_slice($articles, 0, 6); ?>
<section id="news" class="relative overflow-hidden bg-[linear-gradient(135deg,#f8fbff_0%,#eef5ff_52%,#ffffff_100%)] py-10 md:py-16">
    <div class="page-shell relative">
        <span class="pointer-events-none absolute end-0 top-3 select-none font-serif text-[68px] font-black italic leading-none tracking-[-0.14em] text-ink/[0.035] md:top-0 md:text-[150px]"><?php echo esc_html($home['news']['background_word'] ?? 'NEWS'); ?></span>
        <div class="relative z-10 grid grid-cols-1 gap-4 lg:flex lg:items-end lg:justify-between lg:gap-8">
            <div class="min-w-0"><p class="section-label mb-4 md:mb-5"><?php echo esc_html($home['news']['label'] ?? 'News Center'); ?></p><h2 class="max-w-none text-[clamp(31px,7.2vw,38px)] font-black leading-[0.98] tracking-[-0.025em] text-ink sm:text-[48px] lg:text-[52px] 2xl:whitespace-nowrap 2xl:text-[64px]"><?php echo esc_html($home['news']['title'] ?? 'Latest Insights from BOB-LIFT.'); ?></h2></div>
            <div class="flex lg:justify-self-end"><a class="inline-flex h-auto w-fit shrink-0 items-center justify-center gap-1.5 whitespace-nowrap rounded-full border border-transparent bg-navy bg-clip-padding px-5 py-2.5 text-xs font-black text-white shadow-card outline-none select-none transition-all hover:-translate-y-0.5 hover:bg-[#0b2d5b] md:px-6 md:py-3 md:text-sm" href="<?php echo esc_url(boblift_url($home['news']['cta_href'] ?? '/news')); ?>"><?php echo esc_html($home['news']['cta_label'] ?? 'View All News'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a></div>
        </div>
        <div class="relative z-10 mt-7 grid grid-cols-1 gap-4 md:mt-9 md:grid-cols-3 lg:gap-5 xl:gap-6">
            <?php foreach ($news_items as $item) : ?>
                <a class="group grid min-w-0 overflow-hidden rounded-[24px] border border-line bg-white/92 p-2 shadow-card transition duration-300 hover:-translate-y-1 hover:bg-white hover:shadow-soft md:rounded-[28px]" href="<?php echo esc_url($item['url']); ?>">
                    <div class="relative aspect-[1.5/1] overflow-hidden rounded-[18px] md:rounded-[22px]"><img loading="lazy" decoding="async" class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]" src="<?php echo esc_url(boblift_item_image($item)); ?>" alt="<?php echo esc_attr($item['title']); ?>"></div>
                    <div class="flex min-w-0 flex-col px-2 py-4 md:px-3 md:py-5 xl:px-4"><div class="mb-2 flex flex-wrap items-center gap-1.5 md:gap-2"><span class="inline-flex h-6 w-fit shrink-0 items-center justify-center gap-1 overflow-hidden whitespace-nowrap rounded-full border border-line bg-white px-2.5 py-0.5 text-[10px] font-black uppercase tracking-[0.12em] text-gold transition-all"><?php echo esc_html($item['category']); ?></span><span class="text-xs font-bold text-slate-500"><?php echo esc_html($item['date']); ?></span></div><h3 class="line-clamp-2 text-lg font-black leading-snug tracking-[-0.01em] text-ink xl:text-xl"><?php echo esc_html($item['title']); ?></h3><p class="mt-2 line-clamp-2 text-sm font-medium leading-6 text-slate-500"><?php echo esc_html($item['excerpt']); ?></p><span class="mt-4 inline-flex items-center gap-2 text-xs font-black text-ink"><?php echo esc_html($home_ui['readMoreLabel'] ?? 'Read More'); ?> <?php echo boblift_icon('arrow-right', 'h-3.5 w-3.5'); ?></span></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
<?php get_footer(); ?>
