<?php
/**
 * Template Name: BOB-LIFT Factory Tour
 */
get_header();

$ui = boblift_site_ui('factoryTour');
$detail_ui = boblift_site_ui('productDetail');
$photo = static fn(string $name): array => ['full' => boblift_image_src('images/factory/' . $name . '.webp'), 'thumb' => boblift_image_src('images/factory/' . $name . '-sm.webp')];
// Photos per tour stop, taken on the site in August 2026 (tail lifts and non-crane machines left out).
$stop_photos = [
    'arrival' => ['yard-gate', 'aerial-gate', 'yard-trucks'],
    'assembly' => ['hall-assembly-aisle', 'hall-knuckle-row', 'hall-crane-line', 'hall-finished-row', 'hall-crane-close'],
    'parts' => ['hall-boom-sections', 'hall-parts-racks', 'hall-hydraulics', 'hall-frames', 'hall-workstations'],
    'mounting' => ['hall-truck-mounting', 'hall-crane-test-bay'],
];
$stops = [];
$gallery = [];
$captions = [];
foreach ((array) ($ui['stops'] ?? []) as $stop) {
    $names = $stop_photos[(string) ($stop['key'] ?? '')] ?? [];
    if (!is_array($stop) || $names === []) {
        continue;
    }
    $stop['start'] = count($gallery);
    $stop['photos'] = [];
    foreach ($names as $name) {
        $item = $photo($name);
        $stop['photos'][] = $item + ['index' => count($gallery)];
        $gallery[] = $item['full'];
        $captions[] = (string) ($stop['title'] ?? '');
    }
    $stops[] = $stop;
}
$facts = array_values(array_filter((array) ($ui['facts'] ?? []), static fn($fact): bool => is_array($fact) && trim((string) ($fact['value'] ?? '')) !== ''));
$aerial = boblift_video_by_key('factory-aerial');
$intro = boblift_video_by_key('factory-intro');
$clips = array_values(array_filter([boblift_video_by_key('factory-hall-door'), boblift_video_by_key('factory-hall-cranes')]));
$pad = static fn(int $n): string => str_pad((string) $n, 2, '0', STR_PAD_LEFT);
?>
<div class="page-shell pt-5 md:pt-8">
    <?php
    boblift_page_hero([
        'label' => (string) ($ui['heroLabel'] ?? 'Factory Tour'),
        'title' => (string) ($ui['heroTitle'] ?? ''),
        'text' => (string) ($ui['heroText'] ?? ''),
        'image' => boblift_image_src('images/factory/hall-assembly-aisle.webp'),
        'image_alt' => (string) ($ui['heroImageAlt'] ?? ''),
        'image_position' => 'center 55%',
    ]);
    ?>
</div>

<section class="page-shell pt-8 md:pt-10" aria-label="<?php echo esc_attr((string) ($ui['heroImageAlt'] ?? '')); ?>">
    <div class="relative isolate flex min-h-[320px] items-end overflow-hidden rounded bg-navy p-6 text-white sm:min-h-[420px] sm:p-10 lg:min-h-[520px]">
        <img class="absolute inset-0 -z-20 h-full w-full object-cover" src="<?php echo esc_url($aerial ? boblift_asset($aerial['poster']) : boblift_image_src('images/factory/aerial-site.webp')); ?>" alt="" loading="lazy" decoding="async">
        <?php if ($aerial && $aerial['player'] === 'local') : ?>
            <video class="absolute inset-0 -z-10 h-full w-full object-cover" muted loop playsinline preload="none" aria-hidden="true" data-inview-video data-src="<?php echo esc_url($aerial['src']); ?>"<?php if (($aerial['src_mobile'] ?? '') !== '') : ?> data-src-mobile="<?php echo esc_url($aerial['src_mobile']); ?>"<?php endif; ?>></video>
        <?php endif; ?>
        <span class="absolute inset-0 -z-10 bg-[linear-gradient(180deg,rgba(8,35,74,0)_40%,rgba(8,35,74,0.85)_100%)]"></span>
        <?php if ($facts !== []) : ?>
            <dl class="flex flex-wrap gap-x-10 gap-y-4">
                <?php foreach ($facts as $fact) : ?>
                    <div><dt class="text-xs font-bold text-white/70"><?php echo esc_html((string) ($fact['label'] ?? '')); ?></dt><dd class="mt-1 text-[clamp(1.5rem,2.6vw,2.5rem)] font-black leading-none tracking-[-0.015em]"><?php echo esc_html((string) $fact['value']); ?></dd></div>
                <?php endforeach; ?>
            </dl>
        <?php endif; ?>
    </div>
</section>

<?php if ($stops !== []) : ?>
<section class="py-12 md:py-16" data-detail-viewer data-detail-images="<?php echo esc_attr(wp_json_encode($gallery, JSON_UNESCAPED_SLASHES)); ?>" data-detail-alt="<?php echo esc_attr((string) ($ui['heroLabel'] ?? 'Factory Tour')); ?>" data-detail-captions="<?php echo esc_attr(wp_json_encode($captions, JSON_UNESCAPED_UNICODE)); ?>">
    <div class="page-shell grid gap-12 md:gap-16">
        <?php foreach ($stops as $number => $stop) : $photos = $stop['photos']; $lead = array_shift($photos); ?>
            <article class="grid gap-5 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,2fr)] lg:gap-10" aria-labelledby="factory-stop-<?php echo esc_attr((string) $stop['key']); ?>">
                <div class="lg:pt-2">
                    <p class="text-xs font-black uppercase tracking-[0.14em] text-blue-700"><?php echo esc_html(str_replace('{number}', $pad($number + 1), (string) ($ui['stopLabel'] ?? 'Stop {number}'))); ?></p>
                    <h2 id="factory-stop-<?php echo esc_attr((string) $stop['key']); ?>" class="mt-2 text-[clamp(1.5rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em] text-ink"><?php echo esc_html((string) $stop['title']); ?></h2>
                    <p class="mt-3 max-w-md text-sm font-semibold leading-7 text-slate-600 md:text-base"><?php echo esc_html((string) ($stop['text'] ?? '')); ?></p>
                    <?php if ($stop['key'] === 'mounting') : ?>
                        <a class="mt-5 inline-flex items-center gap-1.5 text-sm font-black text-blue-700 hover:text-blue-900" href="<?php echo esc_url(boblift_url('/quality-testing.html')); ?>"><?php echo esc_html((string) ($ui['qualityLink'] ?? 'See how cranes are tested')); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
                    <?php endif; ?>
                </div>
                <?php
                // Literal class names so Tailwind keeps them: the lead photo spans two rows beside the smaller ones.
                $mosaic = [0 => ['grid-cols-1', 'col-span-1'], 1 => ['grid-cols-2', 'col-span-1'], 2 => ['grid-cols-2 md:grid-cols-3', 'col-span-2 md:row-span-2'], 3 => ['grid-cols-2 md:grid-cols-3', 'col-span-2 md:col-span-3'], 4 => ['grid-cols-2 md:grid-cols-4', 'col-span-2 md:row-span-2']][count($photos)] ?? ['grid-cols-2 md:grid-cols-4', 'col-span-2 md:row-span-2'];
                ?>
                <div class="grid gap-2 sm:gap-3 <?php echo esc_attr($mosaic[0]); ?>">
                    <button class="group relative overflow-hidden rounded bg-mist <?php echo esc_attr($mosaic[1]); ?>" type="button" data-detail-open="<?php echo esc_attr((string) $lead['index']); ?>" aria-label="<?php echo esc_attr((string) $stop['title'] . ' ' . $pad($lead['index'] + 1)); ?>">
                        <img class="aspect-[4/3] h-full w-full object-cover transition duration-500 group-hover:scale-[1.03] md:aspect-auto" src="<?php echo esc_url($lead['full']); ?>" alt="<?php echo esc_attr((string) $stop['title']); ?>" loading="lazy" decoding="async">
                    </button>
                    <?php foreach ($photos as $item) : ?>
                        <button class="group relative overflow-hidden rounded bg-mist" type="button" data-detail-open="<?php echo esc_attr((string) $item['index']); ?>" aria-label="<?php echo esc_attr((string) $stop['title'] . ' ' . $pad($item['index'] + 1)); ?>">
                            <img class="aspect-[4/3] h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]" src="<?php echo esc_url($item['thumb']); ?>" alt="<?php echo esc_attr((string) $stop['title']); ?>" loading="lazy" decoding="async">
                        </button>
                    <?php endforeach; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <div class="fixed inset-0 z-[1000] hidden flex-col bg-[#040f22]" data-detail-lightbox role="dialog" aria-modal="true" aria-hidden="true" aria-label="<?php echo esc_attr((string) ($ui['heroLabel'] ?? 'Factory Tour')); ?>">
        <div class="flex shrink-0 items-center justify-between px-4 py-3 text-white md:px-6">
            <p class="flex min-w-0 items-baseline gap-3 text-xs font-black tracking-[0.08em]" aria-live="polite"><span class="shrink-0 [font-variant-numeric:tabular-nums]"><span data-detail-index>01</span> / <?php echo esc_html($pad(count($gallery))); ?></span><span class="truncate text-sm tracking-normal text-white/80" data-detail-caption></span></p>
            <button class="grid size-10 place-items-center rounded-sm bg-white/10 text-white transition hover:bg-white hover:text-[#071f42]" type="button" data-detail-close aria-label="<?php echo esc_attr($detail_ui['closeLabel'] ?? 'Close'); ?>"><?php echo boblift_icon('x', 'h-5 w-5'); ?></button>
        </div>
        <div class="relative min-h-0 flex-1">
            <div class="absolute inset-0 flex items-center justify-center overflow-auto px-3 pb-4 md:px-20" data-detail-stage>
                <img class="block max-h-full max-w-full object-contain" src="" alt="" data-detail-image>
            </div>
            <button class="boblift-feedback-arrow left-2 md:left-5" type="button" data-detail-prev aria-label="<?php echo esc_attr($detail_ui['previousLabel'] ?? 'Previous'); ?>"><?php echo boblift_icon('chevron-left', 'h-5 w-5'); ?></button>
            <button class="boblift-feedback-arrow right-2 md:right-5" type="button" data-detail-next aria-label="<?php echo esc_attr($detail_ui['nextLabel'] ?? 'Next'); ?>"><?php echo boblift_icon('chevron-right', 'h-5 w-5'); ?></button>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($intro || $clips !== []) : ?>
<section class="border-t border-line bg-mist py-12 md:py-16" aria-labelledby="factory-video-title">
    <div class="page-shell">
        <p class="section-label mb-3"><?php echo esc_html((string) ($ui['videoLabel'] ?? 'Factory video')); ?></p>
        <h2 id="factory-video-title" class="mb-6 text-[clamp(1.5rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em] text-ink"><?php echo esc_html((string) ($ui['videoTitle'] ?? '')); ?></h2>
        <div class="grid gap-4 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)] lg:gap-5">
            <?php if ($intro) { boblift_render_video_card($intro); } ?>
            <?php if ($clips !== []) : ?>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                    <?php foreach ($clips as $clip) { boblift_render_video_card($clip); } ?>
                </div>
            <?php endif; ?>
        </div>
        <?php boblift_render_video_player(); ?>
    </div>
</section>
<?php endif; ?>

<section class="page-shell py-12 md:py-16" aria-labelledby="factory-cta-title">
    <div class="flex flex-col gap-6 rounded bg-navy p-6 text-white sm:p-10 lg:flex-row lg:items-center lg:justify-between xl:p-12">
        <div class="max-w-2xl">
            <h2 id="factory-cta-title" class="text-[clamp(1.5rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em]"><?php echo esc_html((string) ($ui['ctaTitle'] ?? '')); ?></h2>
            <p class="mt-3 text-sm font-semibold leading-7 text-white/75 md:text-base"><?php echo esc_html((string) ($ui['ctaText'] ?? '')); ?></p>
        </div>
        <div class="flex shrink-0 flex-wrap gap-3">
            <button class="inline-flex h-12 items-center justify-center gap-2 rounded-sm bg-white px-6 text-sm font-black text-navy transition hover:-translate-y-0.5 hover:bg-blue-50" type="button" data-inquiry-modal-open data-inquiry-source="工厂实景页"><?php echo esc_html((string) ($ui['ctaButton'] ?? 'Plan a Visit')); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></button>
            <a class="inline-flex h-12 items-center justify-center rounded-sm border border-white/30 px-6 text-sm font-black text-white transition hover:border-white" href="<?php echo esc_url(boblift_url('/global-customer-visits.html')); ?>"><?php echo esc_html((string) ($ui['visitsLink'] ?? 'Customer visits')); ?></a>
        </div>
    </div>
</section>

<?php get_footer(); ?>
