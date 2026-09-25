<?php
get_header();
$options = boblift_options();
$home = $options['home'] ?? [];
$home_ui = function_exists('boblift_site_ui') ? boblift_site_ui('homePage') : [];
$hero = is_array($home['hero'] ?? null) ? $home['hero'] : [];
$hero_text = static fn(string $key, string $fallback = ''): string => boblift_localized_static_text((string) ($hero[$key] ?? $fallback));
$hero_slides = [];
foreach ((is_array($hero['slides'] ?? null) ? $hero['slides'] : []) as $slide) {
    if (is_array($slide) && trim((string) ($slide['title'] ?? '')) !== '') {
        $hero_slides[] = array_map(static fn($value) => is_string($value) ? boblift_localized_static_text($value) : $value, $slide);
    }
}
if ($hero_slides === []) {
    $hero_slides[] = ['title' => get_bloginfo('name')];
}
$hero_count = count($hero_slides);
$hero_default_images = ['images/page-heroes/home-marine.webp', 'images/page-heroes/home-site.webp'];
$trust = $home['trust_bar'] ?? [];
$about = $home['about'] ?? [];
$articles = boblift_articles('', 3);
$gallery = $about['gallery'] ?? [];
$default_gallery_alts = is_array($home_ui['defaultGalleryAlts'] ?? null) ? $home_ui['defaultGalleryAlts'] : [];
$default_gallery = [
    ['src' => 'images/truck-mounted-crane.png', 'alt' => (string) ($default_gallery_alts[0] ?? 'Truck mounted crane')],
    ['src' => 'images/page-heroes/factory-floor.webp', 'alt' => (string) ($default_gallery_alts[1] ?? 'BOB-LIFT factory video preview'), 'video' => true],
    ['src' => 'images/page-heroes/marine-deck.webp', 'alt' => (string) ($default_gallery_alts[2] ?? 'Marine crane project')],
    ['src' => 'images/product-banners/truck-mounted-crane.jpg', 'alt' => (string) ($default_gallery_alts[3] ?? 'Telescopic boom crane')],
    ['src' => 'images/product-banners/default.jpg', 'alt' => (string) ($default_gallery_alts[4] ?? 'Knuckle boom crane')],
];
if (count($gallery) < 5) {
    $gallery = array_values(array_replace($default_gallery, $gallery));
}
$active_gallery_index = min(1, max(0, count($gallery) - 1));
$active_gallery = $gallery[$active_gallery_index] ?? $default_gallery[0];
$active_gallery_type = (string) ($active_gallery['type'] ?? 'image');
$active_gallery_poster = boblift_page_hero_image_setting($active_gallery['src'] ?? '', $default_gallery[$active_gallery_index]['src'] ?? 'images/truck-mounted-crane.png');
$active_gallery_video = boblift_media_setting_url($active_gallery['video_file'] ?? '', '');
$active_gallery_youtube = boblift_youtube_embed_url((string) ($active_gallery['youtube_url'] ?? ''));
$active_gallery_is_local_video = $active_gallery_type === 'local_video' && $active_gallery_video !== '';
$active_gallery_is_youtube = $active_gallery_type === 'youtube' && $active_gallery_youtube !== '';
?>
<section class="boblift-home-hero relative overflow-hidden border-b border-line bg-white" aria-labelledby="home-hero-title"<?php echo $hero_count > 1 ? ' aria-roledescription="carousel" data-home-hero' : ''; ?>>
    <div class="boblift-home-hero-media relative aspect-[16/10] w-full overflow-hidden bg-mist sm:aspect-[2/1] lg:absolute lg:inset-y-0 lg:right-0 lg:aspect-auto lg:w-[66%] lg:bg-white 2xl:w-[62%]">
        <?php foreach ($hero_slides as $index => $slide) : ?>
            <img class="boblift-home-hero-image absolute inset-0 h-full w-full object-cover object-[72%_center] lg:object-[right_20%]" src="<?php echo esc_url(boblift_page_hero_image_setting($slide['image'] ?? [], $hero_default_images[$index] ?? $hero_default_images[0])); ?>" alt="<?php echo esc_attr((string) ($slide['image_alt'] ?? '')); ?>" decoding="async" <?php echo $index === 0 ? 'loading="eager" fetchpriority="high"' : 'fetchpriority="low" aria-hidden="true"'; ?> data-home-hero-image>
        <?php endforeach; ?>
    </div>
    <div class="page-shell relative z-10 py-8 sm:py-10 lg:flex lg:min-h-[540px] lg:items-center lg:py-16 xl:min-h-[600px] 2xl:min-h-[660px]">
        <div class="flex max-w-xl flex-col lg:w-full lg:max-w-[42%] 2xl:max-w-[40%]">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-[#0B3F7D] empty:hidden"><?php echo esc_html($hero_text('label')); ?></p>
            <div class="grid">
                <?php foreach ($hero_slides as $index => $slide) :
                    $heading_tag = $index === 0 ? 'h1' : 'h2';
                    $slide_tab = trim((string) ($slide['tab'] ?? ''));
                ?>
                    <div class="boblift-home-hero-slide [grid-area:1/1]" id="home-hero-slide-<?php echo esc_attr((string) $index); ?>"<?php echo $hero_count > 1 ? ' role="tabpanel" aria-labelledby="home-hero-tab-' . esc_attr((string) $index) . '"' : ''; ?><?php echo $index > 0 ? ' aria-hidden="true"' : ''; ?> data-home-hero-slide>
                        <<?php echo $heading_tag; ?><?php echo $index === 0 ? ' id="home-hero-title"' : ''; ?> class="mt-3 text-[clamp(2.1rem,4vw,4.5rem)] font-black leading-[1.02] tracking-[-0.02em] text-[#0B1B33] [text-wrap:balance] [word-break:auto-phrase]"><?php echo esc_html((string) $slide['title']); ?></<?php echo $heading_tag; ?>>
                        <p class="mt-5 text-base font-semibold leading-7 text-slate-600 empty:hidden [word-break:auto-phrase] md:text-lg md:leading-8"><?php echo esc_html((string) preg_replace('/(\d)\s+(t|kg|m²)(?![\p{L}\d])/u', "$1\u{00A0}$2", (string) ($slide['text'] ?? ''))); ?></p>
                        <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:items-center">
                            <a class="inline-flex h-12 items-center justify-center gap-2 rounded-sm bg-navy px-6 text-sm font-black text-white transition hover:bg-[#0b2d5b]" href="<?php echo esc_url(boblift_url((string) (($slide['primary_href'] ?? '') ?: '/products'))); ?>"><?php echo esc_html((string) (($slide['primary_label'] ?? '') ?: boblift_localized_static_text('View Products'))); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
                            <button class="inline-flex h-12 items-center justify-center gap-2 rounded-sm border border-navy/20 bg-white px-6 text-sm font-black text-navy transition hover:border-blue-300 hover:text-blue-700" type="button" data-inquiry-modal-open data-inquiry-source="<?php echo esc_attr('首页首屏' . ($slide_tab !== '' ? ' · ' . $slide_tab : '')); ?>"><?php echo esc_html($hero_text('secondary_label', 'Request a Quote')); ?></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ($hero_count > 1) : ?>
                <div class="order-first mb-7 flex items-end gap-4 lg:order-none lg:mb-0 lg:mt-12">
                    <div class="grid flex-1 gap-4 <?php echo $hero_count > 2 ? 'grid-cols-3' : 'grid-cols-2'; ?>" role="tablist" aria-label="<?php echo esc_attr($home_ui['heroAriaLabel'] ?? 'Featured lifting solutions'); ?>">
                        <?php foreach ($hero_slides as $index => $slide) : ?>
                            <button class="boblift-home-hero-tab relative flex items-baseline gap-1.5 border-t-2 border-line pt-3 text-left text-sm font-black leading-5 text-slate-500 transition-colors hover:text-navy aria-selected:text-ink" type="button" role="tab" id="home-hero-tab-<?php echo esc_attr((string) $index); ?>" aria-controls="home-hero-slide-<?php echo esc_attr((string) $index); ?>" aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>" tabindex="<?php echo $index === 0 ? '0' : '-1'; ?>" data-home-hero-tab>
                                <span class="boblift-home-hero-tab-bar absolute inset-x-0 -top-0.5 h-0.5 bg-navy" aria-hidden="true"></span>
                                <span class="tabular-nums text-slate-400"><?php echo esc_html(sprintf('%02d', $index + 1)); ?></span>
                                <span><?php echo esc_html(trim((string) ($slide['tab'] ?? ''))); ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <button class="grid size-9 shrink-0 place-items-center rounded-sm border border-line text-navy transition hover:border-blue-300" type="button" aria-label="<?php echo esc_attr($home_ui['pauseHeroLabel'] ?? 'Pause slideshow'); ?>" data-pause-label="<?php echo esc_attr($home_ui['pauseHeroLabel'] ?? 'Pause slideshow'); ?>" data-play-label="<?php echo esc_attr($home_ui['playHeroLabel'] ?? 'Play slideshow'); ?>" data-home-hero-toggle>
                        <?php echo boblift_icon('pause', 'size-3.5 fill-current'); ?>
                        <?php echo boblift_icon('play', 'hidden size-3.5 fill-current'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="page-shell py-6 md:py-8" aria-label="<?php echo esc_attr($home_ui['qualityCertificationsLabel'] ?? 'Quality certifications'); ?>">
    <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between md:gap-10">
        <div class="shrink-0">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-gold"><?php echo esc_html($trust['label'] ?? 'Certified'); ?></p>
            <p class="mt-1 text-lg font-black leading-snug text-ink"><?php echo esc_html($trust['title'] ?? 'Quality Assurance'); ?></p>
            <p class="text-sm font-semibold text-slate-500"><?php echo esc_html($trust['description'] ?? 'Export-ready documentation.'); ?></p>
        </div>
        <ul class="grid grid-cols-4 items-start gap-4 md:flex md:items-center md:gap-10 lg:gap-14">
            <?php foreach (($trust['marks'] ?? []) as $mark) :
                $mark_name = function_exists('boblift_localized_static_text') ? boblift_localized_static_text((string) ($mark['name'] ?? '')) : (string) ($mark['name'] ?? '');
            ?>
                <li class="flex flex-col items-center gap-2 text-center">
                    <?php echo boblift_img_from_setting($mark['logo'] ?? '', '', $mark_name . ' logo', 'h-8 w-20 max-w-full object-contain md:h-11 md:w-28'); ?>
                    <span class="hidden text-xs font-semibold leading-4 text-slate-500 md:block"><?php echo esc_html($mark_name); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<?php boblift_render_home_products(); ?>

<section id="about" class="page-shell grid grid-cols-1 gap-7 overflow-hidden py-8 md:gap-10 md:py-14 lg:grid-cols-[0.95fr_1.05fr] lg:items-start lg:gap-9 lg:py-12 xl:grid-cols-[1fr_0.95fr] xl:items-stretch xl:gap-20 xl:py-14" data-about-gallery>
    <div class="flex h-full min-h-0 flex-col">
        <div class="relative h-[220px] overflow-hidden rounded shadow-card sm:h-[340px] lg:h-[430px] lg:min-h-0 lg:flex-none xl:h-auto xl:min-h-[460px] xl:flex-1" data-about-active-stage>
            <?php if ($active_gallery_is_youtube) : ?>
                <iframe class="h-full w-full bg-slate-100" src="about:blank" data-lazy-iframe-src="<?php echo esc_url($active_gallery_youtube); ?>" title="<?php echo esc_attr($active_gallery['alt'] ?? 'BOB-LIFT video'); ?>" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
            <?php elseif ($active_gallery_is_local_video) : ?>
                <video class="h-full w-full object-cover" controls preload="metadata" poster="<?php echo esc_url($active_gallery_poster); ?>"><source src="<?php echo esc_url($active_gallery_video); ?>"></video>
            <?php else : ?>
                <img src="<?php echo esc_url($active_gallery_poster); ?>" alt="<?php echo esc_attr($active_gallery['alt'] ?? ''); ?>" class="h-full w-full object-cover" loading="lazy" decoding="async" data-about-active-image>
            <?php endif; ?>
            <span class="pointer-events-none absolute left-1/2 top-1/2 <?php echo ($active_gallery_is_youtube || $active_gallery_is_local_video) ? 'grid' : 'hidden'; ?> size-14 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-sm bg-white/94 text-gold shadow-card ring-1 ring-white/70 sm:size-20" data-about-play><?php echo boblift_icon('circle-play', 'size-7 fill-gold sm:size-10'); ?></span>
        </div>
        <div class="mt-3 grid grid-cols-5 gap-2 sm:mt-4 sm:gap-3 lg:mt-3 lg:gap-2 xl:mt-4 xl:gap-3">
            <?php foreach ($gallery as $index => $item) : ?>
                <?php
                $gallery_image = boblift_page_hero_image_setting($item['src'] ?? '', $default_gallery[$index]['src'] ?? 'images/truck-mounted-crane.png');
                $gallery_type = (string) ($item['type'] ?? 'image');
                $gallery_video = boblift_media_setting_url($item['video_file'] ?? '', '');
                $gallery_youtube = boblift_youtube_embed_url((string) ($item['youtube_url'] ?? ''));
                $gallery_is_video = ($gallery_type === 'local_video' && $gallery_video !== '') || ($gallery_type === 'youtube' && $gallery_youtube !== '');
                ?>
                <button class="relative h-12 overflow-hidden rounded-sm border shadow-card transition duration-200 sm:h-16 lg:h-12 xl:h-16 <?php echo $index === $active_gallery_index ? 'border-blue-500 ring-2 ring-blue-500/20' : 'border-white'; ?>" type="button" data-about-thumb data-type="<?php echo esc_attr($gallery_type); ?>" data-src="<?php echo esc_url($gallery_image); ?>" data-alt="<?php echo esc_attr($item['alt'] ?? ''); ?>" data-video-src="<?php echo esc_url($gallery_video); ?>" data-youtube-src="<?php echo esc_url($gallery_youtube); ?>" data-video="<?php echo $gallery_is_video ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr(str_replace('{item}', (string) ($item['alt'] ?? ($home_ui['galleryFallbackLabel'] ?? 'gallery image')), (string) ($home_ui['showGalleryTemplate'] ?? 'Show {item}'))); ?>">
                    <img src="<?php echo esc_url($gallery_image); ?>" alt="<?php echo esc_attr($item['alt'] ?? ''); ?>" class="h-full w-full object-cover" loading="lazy" decoding="async">
                    <?php if ($gallery_is_video) : ?><span class="absolute bottom-1 right-1 rounded-sm bg-navy/85 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-[0.08em] text-white shadow-card sm:bottom-1.5 sm:right-1.5 sm:px-2 sm:text-[9px]"><?php echo esc_html($home_ui['videoLabel'] ?? 'Video'); ?></span><?php endif; ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="pt-0 lg:pt-0 xl:pt-[25px]">
        <p class="section-label mb-3 md:mb-4 lg:mb-3 xl:mb-4"><?php echo esc_html($about['label'] ?? 'About BOB-LIFT'); ?></p>
        <h2 class="text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em] text-ink"><?php echo esc_html($about['title'] ?? 'Engineering Excellence. Powered by People.'); ?></h2>
        <p class="mt-4 text-sm leading-7 text-slate-600 sm:mt-6 sm:text-base sm:leading-8 lg:mt-4 lg:text-sm lg:leading-6 xl:mt-6 xl:text-base xl:leading-8"><?php echo esc_html($about['text'] ?? ''); ?></p>
        <div class="mt-6 grid grid-cols-3 divide-x divide-line border-y border-line sm:mt-8 lg:mt-5 xl:mt-8">
            <?php foreach (($about['stats'] ?? []) as $stat) : ?>
                <p class="px-3 py-4 first:pl-0 sm:px-5 sm:py-5"><strong class="block text-2xl font-black leading-none text-ink sm:text-3xl"><?php echo esc_html($stat['value'] ?? ''); ?></strong><span class="mt-2 block text-xs font-semibold leading-4 text-slate-500"><?php echo esc_html($stat['label'] ?? ''); ?></span></p>
            <?php endforeach; ?>
        </div>
        <div class="mt-7 grid grid-cols-2 gap-4 sm:mt-10 sm:grid-cols-4 sm:gap-5 lg:mt-7 lg:gap-4 xl:mt-10 xl:gap-6">
            <?php foreach (($about['timeline'] ?? []) as $item) : $parts = explode(' ', (string) $item, 2); ?>
                <div class="relative border-t border-line pt-6 lg:pt-5 xl:pt-6"><span class="absolute -top-2 left-0 h-4 w-4 rounded-full border-4 border-white bg-gold shadow-card"></span><strong class="block text-sm font-black text-ink"><?php echo esc_html($parts[0] ?? ''); ?></strong><p class="mt-2 text-xs font-bold leading-5 text-slate-500 lg:mt-1.5 lg:leading-4 xl:mt-2 xl:leading-5"><?php echo esc_html($parts[1] ?? ''); ?></p></div>
            <?php endforeach; ?>
        </div>
        <a class="mt-9 inline-flex items-center gap-2 text-base font-black text-ink md:text-lg lg:mt-6 xl:mt-9" href="<?php echo esc_url(boblift_url($about['cta_href'] ?? '/about')); ?>"><?php echo esc_html($about['cta_label'] ?? 'More About Us'); ?> <?php echo boblift_icon('arrow-right', 'h-5 w-5'); ?></a>
    </div>
</section>

<?php boblift_render_home_factory(); ?>

<?php boblift_render_home_cases(); ?>

<?php if (!empty($articles)) : $news_items = array_slice($articles, 0, 3); ?>
<section id="news" class="relative overflow-hidden bg-[linear-gradient(135deg,#f8fbff_0%,#eef5ff_52%,#ffffff_100%)] py-10 md:py-16">
    <div class="page-shell relative">
        <div class="relative z-10 grid grid-cols-1 gap-4 lg:flex lg:items-end lg:justify-between lg:gap-8">
            <div class="min-w-0"><p class="section-label mb-4 md:mb-5"><?php echo esc_html($home['news']['label'] ?? 'News Center'); ?></p><h2 class="text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em] text-ink"><?php echo esc_html($home['news']['title'] ?? 'Latest Insights from BOB-LIFT.'); ?></h2></div>
            <div class="flex lg:justify-self-end"><a class="inline-flex h-auto w-fit shrink-0 items-center justify-center gap-1.5 whitespace-nowrap rounded-sm border border-transparent bg-navy bg-clip-padding px-5 py-2.5 text-xs font-black text-white shadow-card outline-none select-none transition-all hover:-translate-y-0.5 hover:bg-[#0b2d5b] md:px-6 md:py-3 md:text-sm" href="<?php echo esc_url(boblift_url($home['news']['cta_href'] ?? '/news')); ?>"><?php echo esc_html($home['news']['cta_label'] ?? 'View All News'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a></div>
        </div>
        <div class="relative z-10 mt-7 grid grid-cols-1 gap-4 md:mt-9 md:grid-cols-3 lg:gap-5 xl:gap-6">
            <?php foreach ($news_items as $item) : ?>
                <a class="group grid min-w-0 overflow-hidden rounded border border-line bg-white p-2 shadow-card transition duration-300 hover:border-blue-200" href="<?php echo esc_url($item['url']); ?>">
                    <div class="relative aspect-[1.5/1] overflow-hidden rounded "><img loading="lazy" decoding="async" class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]" src="<?php echo esc_url(boblift_item_image($item)); ?>" alt="<?php echo esc_attr($item['title']); ?>"></div>
                    <div class="flex min-w-0 flex-col px-2 py-4 md:px-3 md:py-5 xl:px-4"><div class="mb-2 flex flex-wrap items-center gap-1.5 md:gap-2"><span class="inline-flex h-6 w-fit shrink-0 items-center justify-center gap-1 overflow-hidden whitespace-nowrap rounded-sm border border-line bg-white px-2.5 py-0.5 text-[10px] font-black uppercase tracking-[0.12em] text-gold transition-all"><?php echo esc_html($item['category']); ?></span><span class="text-xs font-bold text-slate-500"><?php echo esc_html($item['date']); ?></span></div><h3 class="line-clamp-2 text-lg font-black leading-snug tracking-normal text-ink xl:text-xl"><?php echo esc_html($item['title']); ?></h3><p class="mt-2 line-clamp-2 text-sm font-medium leading-6 text-slate-500 empty:hidden"><?php echo esc_html($item['excerpt']); ?></p><span class="mt-4 inline-flex items-center gap-2 text-xs font-black text-ink"><?php echo esc_html($home_ui['readMoreLabel'] ?? 'Read More'); ?> <?php echo boblift_icon('arrow-right', 'h-3.5 w-3.5'); ?></span></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
<?php get_footer(); ?>
