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
// The headline figures (years, export countries) sit in the certification bar, near the top of the page.
$about_stats = array_values(array_filter((array) ($about['stats'] ?? []), static fn($stat): bool => is_array($stat) && trim((string) ($stat['value'] ?? '')) !== ''));
$articles = boblift_articles('', 3);
$gallery = $about['gallery'] ?? [];
$default_gallery_alts = is_array($home_ui['defaultGalleryAlts'] ?? null) ? $home_ui['defaultGalleryAlts'] : [];
$default_gallery = [
    ['src' => 'images/about/home-workshop-floor.webp', 'alt' => (string) ($default_gallery_alts[0] ?? 'BOB-LIFT assembly workshop in Xuzhou')],
    ['src' => 'images/about/home-technician-assembly.webp', 'alt' => (string) ($default_gallery_alts[1] ?? 'BOB-LIFT technician assembling a crane column')],
    ['src' => 'images/about/home-boom-grinding.webp', 'alt' => (string) ($default_gallery_alts[2] ?? 'Finishing a boom section in the BOB-LIFT workshop')],
    ['src' => 'images/about/home-team.webp', 'alt' => (string) ($default_gallery_alts[3] ?? 'The BOB-LIFT team')],
    ['src' => 'images/about/home-customer-visit.webp', 'alt' => (string) ($default_gallery_alts[4] ?? 'Customers visiting the BOB-LIFT factory')],
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
    <div class="boblift-home-hero-media relative aspect-[16/10] w-full overflow-hidden bg-mist sm:aspect-[2/1] lg:absolute lg:inset-y-0 lg:end-0 lg:aspect-auto lg:w-[66%] lg:bg-white 2xl:w-[62%]">
        <?php foreach ($hero_slides as $index => $slide) : ?>
            <?php $hero_mobile_image = boblift_image_setting_url($slide['mobile_image'] ?? '', ''); ?>
            <picture class="contents">
                <?php if ($hero_mobile_image !== '') : ?><source media="(max-width: 767px)" srcset="<?php echo esc_url($hero_mobile_image); ?>"><?php endif; ?>
                <img class="boblift-home-hero-image absolute inset-0 h-full w-full object-cover object-[72%_center] lg:object-[right_20%]" src="<?php echo esc_url(boblift_page_hero_image_setting($slide['image'] ?? [], $hero_default_images[$index] ?? $hero_default_images[0])); ?>" alt="<?php echo esc_attr((string) ($slide['image_alt'] ?? '')); ?>" decoding="async" <?php echo $index === 0 ? 'loading="eager" fetchpriority="high"' : 'fetchpriority="low" aria-hidden="true"'; ?> data-home-hero-image>
            </picture>
        <?php endforeach; ?>
    </div>
    <div class="page-shell relative z-10 py-8 sm:py-10 lg:flex lg:min-h-[540px] lg:items-center lg:py-16 xl:min-h-[600px] 2xl:min-h-[660px]">
        <div class="flex max-w-xl flex-col lg:w-full lg:max-w-[42%] 2xl:max-w-[40%]">
            <p class="boblift-kicker empty:hidden"><?php echo esc_html($hero_text('label')); ?></p>
            <div class="grid">
                <?php foreach ($hero_slides as $index => $slide) :
                    $heading_tag = $index === 0 ? 'h1' : 'h2';
                    $slide_tab = trim((string) ($slide['tab'] ?? ''));
                ?>
                    <div class="boblift-home-hero-slide [grid-area:1/1]" id="home-hero-slide-<?php echo esc_attr((string) $index); ?>"<?php echo $hero_count > 1 ? ' role="tabpanel" aria-labelledby="home-hero-tab-' . esc_attr((string) $index) . '"' : ''; ?><?php echo $index > 0 ? ' aria-hidden="true"' : ''; ?> data-home-hero-slide>
                        <<?php echo $heading_tag; ?><?php echo $index === 0 ? ' id="home-hero-title"' : ''; ?> class="mt-3 text-[clamp(2.1rem,4vw,4.5rem)] font-black leading-[1.02] tracking-[-0.02em] text-[#0B1B33] [text-wrap:balance] [word-break:auto-phrase]"><?php echo esc_html((string) $slide['title']); ?></<?php echo $heading_tag; ?>>
                        <p class="mt-5 text-base font-normal leading-7 text-slate-600 empty:hidden [word-break:auto-phrase] md:text-lg md:leading-8"><?php echo esc_html((string) preg_replace('/(\d)\s+(t|kg|m²)(?![\p{L}\d])/u', "$1\u{00A0}$2", (string) ($slide['text'] ?? ''))); ?></p>
                        <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:items-center">
                            <a class="inline-flex h-12 items-center justify-center gap-2 rounded-sm bg-navy px-6 text-sm font-black text-white transition hover:bg-[#0b2d5b]" href="<?php echo esc_url(boblift_url((string) (($slide['primary_href'] ?? '') ?: '/products'))); ?>"><?php echo esc_html((string) (($slide['primary_label'] ?? '') ?: boblift_localized_static_text('View Products'))); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
                            <?php // Below 1024px the floating contact bar already carries the quote button, so the hero keeps one call to action. ?>
                            <button class="hidden h-12 items-center justify-center gap-2 rounded-sm border border-navy/20 bg-white px-6 text-sm font-black text-navy transition hover:border-blue-300 hover:text-blue-700 lg:inline-flex" type="button" data-inquiry-modal-open data-inquiry-source="<?php echo esc_attr('首页首屏' . ($slide_tab !== '' ? ' · ' . $slide_tab : '')); ?>"><?php echo esc_html($hero_text('secondary_label', 'Request a Quote')); ?></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ($hero_count > 1) : ?>
                <div class="order-first mb-7 flex items-end gap-4 lg:order-none lg:mb-0 lg:mt-12">
                    <div class="grid flex-1 gap-4 <?php echo $hero_count > 2 ? 'grid-cols-3' : 'grid-cols-2'; ?>" role="tablist" aria-label="<?php echo esc_attr($home_ui['heroAriaLabel'] ?? 'Featured lifting solutions'); ?>">
                        <?php foreach ($hero_slides as $index => $slide) : ?>
                            <button class="boblift-home-hero-tab relative flex min-h-11 items-baseline gap-1.5 border-t-2 border-line pt-3 text-start text-sm font-black leading-5 text-slate-500 transition-colors hover:text-navy aria-selected:text-ink" type="button" role="tab" id="home-hero-tab-<?php echo esc_attr((string) $index); ?>" aria-controls="home-hero-slide-<?php echo esc_attr((string) $index); ?>" aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>" tabindex="<?php echo $index === 0 ? '0' : '-1'; ?>" data-home-hero-tab>
                                <span class="boblift-home-hero-tab-bar absolute inset-x-0 -top-0.5 h-0.5 bg-navy" aria-hidden="true"></span>
                                <span class="tabular-nums text-slate-400"><?php echo esc_html(sprintf('%02d', $index + 1)); ?></span>
                                <span><?php echo esc_html(trim((string) ($slide['tab'] ?? ''))); ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <button class="grid size-11 shrink-0 place-items-center rounded-sm border border-line text-navy transition hover:border-blue-300 lg:size-9" type="button" aria-label="<?php echo esc_attr($home_ui['pauseHeroLabel'] ?? 'Pause slideshow'); ?>" data-pause-label="<?php echo esc_attr($home_ui['pauseHeroLabel'] ?? 'Pause slideshow'); ?>" data-play-label="<?php echo esc_attr($home_ui['playHeroLabel'] ?? 'Play slideshow'); ?>" data-home-hero-toggle>
                        <?php echo boblift_icon('pause', 'size-3.5 fill-current'); ?>
                        <?php echo boblift_icon('play', 'hidden size-3.5 fill-current'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="page-shell py-5 md:py-8" aria-label="<?php echo esc_attr($home_ui['qualityCertificationsLabel'] ?? 'Quality certifications'); ?>">
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between md:gap-10">
        <div class="shrink-0">
            <?php // Phones skip the heading lines: the figures and the marks carry the section on their own. ?>
            <div class="hidden md:block">
                <p class="boblift-kicker"><?php echo esc_html($trust['label'] ?? 'Certified'); ?></p>
                <p class="mt-1 text-lg font-black leading-snug text-ink"><?php echo esc_html($trust['title'] ?? 'Quality Assurance'); ?></p>
                <p class="text-sm font-normal text-slate-500"><?php echo esc_html($trust['description'] ?? 'Export-ready documentation.'); ?></p>
            </div>
            <?php if ($about_stats) : ?>
                <p class="grid grid-cols-2 gap-2 text-sm font-normal text-slate-500 md:mt-3 md:flex md:flex-wrap md:gap-x-6 md:gap-y-1">
                    <?php foreach (array_slice($about_stats, 0, 3) as $stat) : ?><span class="flex flex-col gap-1 rounded border border-line bg-white px-4 py-3 md:block md:border-0 md:bg-transparent md:p-0"><strong class="text-2xl font-black leading-none text-ink md:me-1.5 md:text-xl"><?php echo esc_html($stat['value'] ?? ''); ?></strong><span class="text-xs md:text-sm"><?php echo esc_html($stat['label'] ?? ''); ?></span></span><?php endforeach; ?>
                </p>
            <?php endif; ?>
        </div>
        <ul class="grid grid-cols-4 gap-2 md:flex md:items-center md:gap-10 lg:gap-14">
            <?php foreach (($trust['marks'] ?? []) as $mark) :
                $mark_name = function_exists('boblift_localized_static_text') ? boblift_localized_static_text((string) ($mark['name'] ?? '')) : (string) ($mark['name'] ?? '');
            ?>
                <?php // Phones show the marks in four equal tiles; wider screens list them with their names. ?>
                <li class="flex h-14 flex-col items-center justify-center gap-2 rounded border border-line bg-white px-2 text-center md:h-auto md:justify-start md:border-0 md:bg-transparent md:px-0">
                    <?php echo boblift_img_from_setting($mark['logo'] ?? '', '', $mark_name . ' logo', 'h-7 w-full max-w-[64px] object-contain md:h-11 md:w-28 md:max-w-full'); ?>
                    <span class="hidden text-xs font-normal leading-4 text-slate-500 md:block"><?php echo esc_html($mark_name); ?></span>
                </li>
            <?php endforeach; ?>
            <?php
            // The marks are a sample: the last item leads on to the full wall of certificates and patents on the About page.
            $more_certificates_url = boblift_url('/about.html#about-certifications');
            $more_certificates_label = boblift_localized_static_text('Patents & more');
            ?>
            <li class="hidden md:block">
                <a class="group flex flex-col items-center gap-2 text-center" href="<?php echo esc_url($more_certificates_url); ?>" aria-label="<?php echo esc_attr(boblift_localized_static_text('All certificates and patents')); ?>">
                    <span class="flex h-11 w-20 items-center justify-center gap-2 text-slate-400 transition group-hover:text-blue-700" aria-hidden="true"><span class="size-1.5 rounded-full bg-current"></span><span class="size-1.5 rounded-full bg-current"></span><span class="size-1.5 rounded-full bg-current"></span></span>
                    <span class="inline-flex items-center gap-1 whitespace-nowrap text-xs font-bold leading-4 text-slate-500 transition group-hover:text-blue-700"><?php echo esc_html($more_certificates_label); ?><?php echo boblift_icon('arrow-right', 'h-3 w-3 rtl:-scale-x-100'); ?></span>
                </a>
            </li>
        </ul>
        <a class="inline-flex min-h-10 items-center gap-1 self-end text-xs font-bold text-slate-500 transition hover:text-blue-700 md:hidden" href="<?php echo esc_url($more_certificates_url); ?>"><?php echo esc_html($more_certificates_label); ?><?php echo boblift_icon('arrow-right', 'h-3 w-3 rtl:-scale-x-100'); ?></a>
    </div>
</section>

<?php boblift_render_home_products(); ?>

<?php // About and the factory are one chapter: who we are, then the factory you can come and see. ?>
<section id="about" class="pb-10 md:pb-16">
<div class="page-shell grid grid-cols-1 gap-7 overflow-hidden pb-6 pt-8 md:gap-10 md:pb-10 md:pt-14 lg:grid-cols-[0.95fr_1.05fr] lg:items-start lg:gap-9 lg:pt-12 xl:grid-cols-[1fr_0.95fr] xl:items-stretch xl:gap-20 xl:pt-14" data-about-gallery>
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
                    <?php if ($gallery_is_video) : ?><span class="absolute bottom-1 end-1 rounded-sm bg-navy/85 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-[0.08em] text-white shadow-card sm:bottom-1.5 sm:end-1.5 sm:px-2 sm:text-[9px]"><?php echo esc_html($home_ui['videoLabel'] ?? 'Video'); ?></span><?php endif; ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php // The text column is shorter than the photo wall beside it, so wide screens centre it against the photos. ?>
    <div class="xl:flex xl:flex-col xl:justify-center">
        <p class="section-label mb-3 md:mb-4 lg:mb-3 xl:mb-4"><?php echo esc_html($about['label'] ?? 'About BOB-LIFT'); ?></p>
        <h2 class="text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em] text-ink [text-wrap:balance] [word-break:auto-phrase]"><?php echo esc_html($about['title'] ?? 'Engineering Excellence. Powered by People.'); ?></h2>
        <p class="mt-4 text-[15px] leading-7 text-slate-600 sm:mt-6 sm:text-base sm:leading-8 lg:mt-4 lg:text-sm lg:leading-6 xl:mt-6 xl:text-base xl:leading-8"><?php echo esc_html($about['text'] ?? ''); ?></p>
        <?php // Phones swipe the milestones in one row; wider screens show all four. ?>
        <div class="-mx-4 mt-7 flex snap-x snap-mandatory scroll-px-4 gap-4 overflow-x-auto px-4 pt-2 [scrollbar-width:none] sm:mx-0 sm:mt-10 sm:grid sm:grid-cols-4 sm:gap-5 sm:overflow-visible sm:px-0 sm:pt-0 lg:mt-7 lg:gap-4 xl:mt-10 xl:gap-6 [&::-webkit-scrollbar]:hidden">
            <?php foreach (($about['timeline'] ?? []) as $item) : $parts = explode(' ', (string) $item, 2); ?>
                <div class="relative w-[40%] shrink-0 snap-start border-t border-line pt-6 sm:w-auto lg:pt-5 xl:pt-6"><span class="absolute -top-2 start-0 h-4 w-4 rounded-full border-4 border-white bg-gold shadow-card"></span><strong class="block text-sm font-black text-ink"><?php echo esc_html($parts[0] ?? ''); ?></strong><p class="mt-2 text-xs font-bold leading-5 text-slate-500 lg:mt-1.5 lg:leading-4 xl:mt-2 xl:leading-5"><?php echo esc_html($parts[1] ?? ''); ?></p></div>
            <?php endforeach; ?>
        </div>
        <a class="mt-7 inline-flex min-h-11 items-center gap-2 text-base font-black text-ink md:text-lg lg:mt-4 xl:mt-7" href="<?php echo esc_url(boblift_url($about['cta_href'] ?? '/about')); ?>"><?php echo esc_html($about['cta_label'] ?? 'More About Us'); ?> <?php echo boblift_icon('arrow-right', 'h-5 w-5'); ?></a>
    </div>
</div>
<?php boblift_render_home_factory(); ?>
</section>

<?php boblift_render_home_cases(); ?>

<?php boblift_render_home_feedback(); ?>

<?php boblift_render_home_faq(); ?>

<?php if (!empty($articles)) : $news_items = array_slice($articles, 0, 3); ?>
<section id="news" class="relative overflow-hidden bg-[linear-gradient(135deg,#f8fbff_0%,#eef5ff_52%,#ffffff_100%)] py-10 md:py-16">
    <div class="page-shell relative">
        <div class="relative z-10 grid grid-cols-1 gap-4 lg:flex lg:items-end lg:justify-between lg:gap-8">
            <div class="min-w-0"><p class="section-label mb-4 md:mb-5"><?php echo esc_html($home['news']['label'] ?? 'News Center'); ?></p><h2 class="text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em] text-ink [text-wrap:balance] [word-break:auto-phrase]"><?php echo esc_html($home['news']['title'] ?? 'Latest Insights from BOB-LIFT.'); ?></h2></div>
            <?php // Phones read the latest stories first; the button to all news follows the list there. ?>
            <div class="hidden lg:flex lg:justify-self-end"><a class="inline-flex h-auto w-fit shrink-0 items-center justify-center gap-1.5 whitespace-nowrap rounded-sm border border-transparent bg-navy bg-clip-padding min-h-11 px-5 py-2.5 text-xs font-black md:min-h-0 text-white shadow-card outline-none select-none transition-all hover:-translate-y-0.5 hover:bg-[#0b2d5b] md:px-6 md:py-3 md:text-sm" href="<?php echo esc_url(boblift_url($home['news']['cta_href'] ?? '/news')); ?>"><?php echo esc_html($home['news']['cta_label'] ?? 'View All News'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a></div>
        </div>
        <div class="relative z-10 mt-7 grid grid-cols-1 gap-3 md:mt-9 md:grid-cols-3 md:gap-4 lg:gap-5 xl:gap-6">
            <?php foreach ($news_items as $item) : ?>
                <a class="group grid min-w-0 grid-cols-[128px_minmax(0,1fr)] items-start gap-3 overflow-hidden rounded border border-line bg-white p-3 shadow-card transition duration-300 hover:border-blue-200 md:grid-cols-1 md:gap-0" href="<?php echo esc_url($item['url']); ?>">
                    <?php // Covers are 16:9 posters with text near the edges, so the frame keeps their shape instead of cropping them. ?>
                    <div class="relative aspect-video overflow-hidden rounded bg-[#eef3f9] ring-1 ring-inset ring-line"><img loading="lazy" decoding="async" class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]" src="<?php echo esc_url(boblift_item_image($item)); ?>" alt="<?php echo esc_attr($item['title']); ?>"></div>
                    <div class="flex min-w-0 flex-col md:px-3 md:py-5 xl:px-4"><div class="mb-1.5 flex flex-wrap items-center gap-1.5 md:mb-2 md:gap-2"><span class="inline-flex h-5 w-fit max-w-full shrink-0 items-center justify-center gap-1 overflow-hidden whitespace-nowrap rounded-sm border border-line bg-white px-2 text-[10px] md:h-6 md:px-2.5 font-black uppercase tracking-[0.12em] text-gold transition-all"><?php echo esc_html($item['category']); ?></span><span class="text-xs font-bold text-slate-500"><?php echo esc_html($item['date']); ?></span></div><h3 class="line-clamp-2 text-[15px] font-black leading-snug tracking-normal text-ink md:line-clamp-2 md:text-lg xl:text-xl"><?php echo esc_html($item['title']); ?></h3><p class="mt-2 hidden text-sm font-medium leading-6 text-slate-500 empty:hidden md:line-clamp-2"><?php echo esc_html($item['excerpt']); ?></p><span class="mt-4 hidden items-center gap-2 text-xs font-black text-ink md:inline-flex"><?php echo esc_html($home_ui['readMoreLabel'] ?? 'Read More'); ?> <?php echo boblift_icon('arrow-right', 'h-3.5 w-3.5'); ?></span></div>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="relative z-10 mt-5 lg:hidden"><a class="inline-flex h-auto w-full shrink-0 items-center justify-center gap-1.5 whitespace-nowrap rounded-sm border border-transparent bg-navy bg-clip-padding min-h-11 px-5 py-2.5 text-xs font-black md:min-h-0 text-white shadow-card outline-none select-none transition-all hover:-translate-y-0.5 hover:bg-[#0b2d5b] md:px-6 md:py-3 md:text-sm" href="<?php echo esc_url(boblift_url($home['news']['cta_href'] ?? '/news')); ?>"><?php echo esc_html($home['news']['cta_label'] ?? 'View All News'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a></div>
    </div>
</section>
<?php endif; ?>
<?php get_footer(); ?>
