<?php
/**
 * Template Name: BOB-LIFT Truck Mounting & Export
 */
get_header();

$ui = boblift_site_ui('mountingShipping');
$detail_ui = boblift_site_ui('productDetail');
$photo = static fn(string $name): array => ['full' => boblift_image_src('images/mounting/' . $name . '.webp'), 'thumb' => boblift_image_src('images/mounting/' . $name . '-sm.webp')];
// Photos per step, taken in the workshop in July 2026 (mounted crane trucks and wrapped cranes in containers);
// the batch step uses the photographer's order photos from the shared folder (2026-10-09).
$step_photos = [
    'lift' => ['lift-crane-hoist', 'lift-crane-chassis'],
    'fix' => ['fix-frame-side', 'fix-frame-close'],
    'support' => ['rear-support-a', 'rear-support-b'],
    'finish' => ['finished-truck-side', 'finished-truck-arm'],
    'batch' => ['batch-row-3-2z', 'batch-row-6-3za3', 'batch-pallets', 'batch-row-6-3z'],
    'wrap' => ['pack-wrapped-row', 'pack-wrapped-close', 'pack-wrapped-hall', 'pack-wrapped-boom'],
    'load' => ['load-hoist-container', 'load-forklift-door', 'load-forklift-inside'],
];
$gallery = [];
$captions = [];
$sections = [];
foreach (['mount' => 'mountSteps', 'pack' => 'packSteps'] as $section => $steps_key) {
    $steps = [];
    foreach ((array) ($ui[$steps_key] ?? []) as $step) {
        $names = is_array($step) ? ($step_photos[(string) ($step['key'] ?? '')] ?? []) : [];
        if ($names === []) {
            continue;
        }
        $step['photos'] = [];
        foreach ($names as $name) {
            $item = $photo($name);
            $step['photos'][] = $item + ['index' => count($gallery)];
            $gallery[] = $item['full'];
            $captions[] = (string) ($step['title'] ?? '');
        }
        $steps[] = $step;
    }
    $sections[$section] = $steps;
}
$needs = array_values(array_filter((array) ($ui['needs'] ?? []), static fn($need): bool => is_string($need) && trim($need) !== ''));
$pad = static fn(int $n): string => str_pad((string) $n, 2, '0', STR_PAD_LEFT);
?>
<div class="page-shell pt-5 md:pt-8">
    <?php
    boblift_page_hero([
        'label' => (string) ($ui['heroLabel'] ?? 'Truck Mounting & Export'),
        'title' => (string) ($ui['heroTitle'] ?? ''),
        'text' => (string) ($ui['heroText'] ?? ''),
        'image' => boblift_image_src('images/mounting/finished-truck-side.webp'),
        'image_alt' => (string) ($ui['heroImageAlt'] ?? ''),
        'image_position' => 'center 55%',
    ]);
    $steps_count = static fn(int $count): string => str_replace('{count}', (string) $count, boblift_localized_static_text('{count} steps'));
    // The two ways a crane leaves the factory, each leading to its steps below.
    $routes = ['mount' => 'finished-truck-arm', 'pack' => 'pack-wrapped-hall'];
    ?>
    <nav class="mt-4 grid gap-3 md:mt-5 md:grid-cols-2 md:gap-4" aria-label="<?php echo esc_attr((string) ($ui['heroLabel'] ?? 'Truck Mounting & Export')); ?>">
        <?php foreach ($routes as $section => $route_photo) : if (($sections[$section] ?? []) === []) continue; $route_image = $photo($route_photo); ?>
            <a class="group grid grid-cols-[112px_minmax(0,1fr)] overflow-hidden rounded border border-line bg-white shadow-card transition hover:border-blue-300 sm:grid-cols-[180px_minmax(0,1fr)]" href="#mounting-<?php echo esc_attr($section); ?>">
                <span class="relative block overflow-hidden bg-[#EDF1F5]"><img class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]" src="<?php echo esc_url($route_image['thumb']); ?>" alt="" loading="lazy" decoding="async"></span>
                <span class="flex min-w-0 flex-col justify-between gap-3 p-4 sm:p-5">
                    <span>
                        <span class="block text-[11px] font-black uppercase tracking-[0.14em] text-slate-500"><?php echo esc_html((string) ($ui[$section . 'Label'] ?? '')); ?></span>
                        <span class="mt-1.5 block text-base font-black leading-snug text-ink sm:text-lg"><?php echo esc_html((string) ($ui[$section . 'Title'] ?? '')); ?></span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-sm font-black text-blue-700"><?php echo esc_html($steps_count(count($sections[$section]))); ?><?php echo boblift_icon('arrow-down', 'h-4 w-4 transition group-hover:translate-y-0.5'); ?></span>
                </span>
            </a>
        <?php endforeach; ?>
    </nav>
</div>

<div data-detail-viewer data-detail-images="<?php echo esc_attr(wp_json_encode($gallery, JSON_UNESCAPED_SLASHES)); ?>" data-detail-alt="<?php echo esc_attr((string) ($ui['heroLabel'] ?? 'Truck Mounting & Export')); ?>" data-detail-captions="<?php echo esc_attr(wp_json_encode($captions, JSON_UNESCAPED_UNICODE)); ?>">
<?php foreach (['mount' => 'py-12 md:py-16', 'pack' => 'border-y border-line bg-mist py-12 md:py-16'] as $section => $section_class) : if (($sections[$section] ?? []) === []) continue;
    $section_steps = $sections[$section];
    // A thumbnail row only where some step has three photos or more; with one extra photo each, a count on the lead photo is enough.
    $section_thumbs = max(array_map(static fn(array $step): int => count($step['photos']), $section_steps)) >= 3;
    ?>
<section id="mounting-<?php echo esc_attr($section); ?>" class="scroll-mt-24 <?php echo esc_attr($section_class); ?>" aria-labelledby="mounting-<?php echo esc_attr($section); ?>-title">
    <div class="page-shell">
        <div class="flex flex-wrap items-end justify-between gap-x-8 gap-y-2">
            <div>
                <p class="section-label mb-3"><?php echo esc_html((string) ($ui[$section . 'Label'] ?? '')); ?></p>
                <h2 id="mounting-<?php echo esc_attr($section); ?>-title" class="max-w-[26ch] text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em] text-ink"><?php echo esc_html((string) ($ui[$section . 'Title'] ?? '')); ?></h2>
            </div>
            <p class="text-sm font-black text-slate-500"><?php echo esc_html($steps_count(count($section_steps))); ?></p>
        </div>
        <?php // One row of steps on desktop, joined by a numbered rail; photo, then title and text, so every column lines up.
        // Phones get one compact row per step (photo left, text right); the extra photos stay in the viewer. ?>
        <ol class="mt-6 grid gap-x-5 gap-y-6 sm:mt-8 sm:grid-cols-2 sm:gap-y-10 md:mt-10 <?php echo count($section_steps) >= 4 ? 'lg:grid-cols-4' : 'lg:grid-cols-3'; ?>">
            <?php foreach ($section_steps as $number => $step) : $photos = $step['photos']; $lead = array_shift($photos); $is_last = $number === count($section_steps) - 1; ?>
                <li class="grid min-w-0 grid-cols-[minmax(0,0.9fr)_minmax(0,1fr)] content-start gap-x-4 sm:flex sm:flex-col" aria-labelledby="mounting-step-<?php echo esc_attr((string) $step['key']); ?>">
                    <div class="col-start-2 row-start-1 flex items-center gap-3" aria-hidden="true">
                        <span class="grid size-8 shrink-0 place-items-center rounded-sm bg-[#071f42] text-xs font-black sm:size-9 text-white [font-variant-numeric:tabular-nums]"><?php echo esc_html($pad($number + 1)); ?></span>
                        <?php if (!$is_last) : ?><span class="hidden h-px flex-1 bg-[#C9D6E6] lg:block"></span><?php endif; ?>
                    </div>
                    <button class="group relative col-start-1 row-span-3 row-start-1 block self-start overflow-hidden rounded bg-[#EDF1F5] sm:mt-4" type="button" data-detail-open="<?php echo esc_attr((string) $lead['index']); ?>" aria-label="<?php echo esc_attr((string) $step['title'] . ' ' . $pad($lead['index'] + 1)); ?>">
                        <img class="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-[1.03]" src="<?php echo esc_url($lead['full']); ?>" alt="<?php echo esc_attr((string) $step['title']); ?>" loading="lazy" decoding="async">
                        <?php if (!$section_thumbs && $photos) : ?><span class="absolute bottom-2 right-2 inline-flex items-center gap-1.5 rounded-sm bg-[#071f42]/80 px-2 py-1 text-xs font-black text-white [font-variant-numeric:tabular-nums]"><?php echo boblift_icon('images', 'h-3.5 w-3.5'); ?><?php echo esc_html((string) (count($photos) + 1)); ?></span><?php endif; ?>
                    </button>
                    <?php if ($section_thumbs) : ?>
                    <?php // A fixed row of three slots, so the titles below stay on one line across the steps. ?>
                    <div class="mt-2 hidden grid-cols-3 gap-2 sm:grid">
                        <?php foreach (array_slice($photos, 0, 3) as $item) : ?>
                            <button class="group relative block overflow-hidden rounded-sm bg-[#EDF1F5]" type="button" data-detail-open="<?php echo esc_attr((string) $item['index']); ?>" aria-label="<?php echo esc_attr((string) $step['title'] . ' ' . $pad($item['index'] + 1)); ?>">
                                <img class="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-[1.06]" src="<?php echo esc_url($item['thumb']); ?>" alt="" loading="lazy" decoding="async">
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <h3 id="mounting-step-<?php echo esc_attr((string) $step['key']); ?>" class="col-start-2 mt-3 text-base font-black leading-snug tracking-[-0.01em] text-ink sm:mt-5 sm:text-lg md:text-xl"><?php echo esc_html((string) $step['title']); ?></h3>
                    <p class="col-start-2 mt-1.5 text-[13px] font-normal leading-5 text-slate-600 sm:mt-2 sm:text-sm sm:leading-6 md:text-[15px]"><?php echo esc_html((string) ($step['text'] ?? '')); ?></p>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>
<?php endforeach; ?>
    <div class="fixed inset-0 z-[1000] hidden flex-col bg-[#040f22]" data-detail-lightbox role="dialog" aria-modal="true" aria-hidden="true" aria-label="<?php echo esc_attr((string) ($ui['heroLabel'] ?? 'Truck Mounting & Export')); ?>">
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
</div>

<?php // The questions sales will ask and the inquiry buttons in one closing block (the old separate CTA repeated the questions). ?>
<section class="page-shell py-12 md:py-16" aria-labelledby="mounting-needs-title">
    <div class="grid overflow-hidden rounded bg-navy text-white lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
        <div class="flex flex-col justify-between gap-8 p-6 sm:p-10 xl:p-12">
            <div>
                <p class="text-[11px] font-black uppercase tracking-[0.18em] text-gold"><?php echo esc_html((string) ($ui['needsLabel'] ?? '')); ?></p>
                <h2 id="mounting-needs-title" class="mt-3 text-[clamp(1.6rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em]"><?php echo esc_html((string) ($ui['needsTitle'] ?? '')); ?></h2>
                <p class="mt-3 max-w-md text-sm font-normal leading-7 text-white/75 md:text-base"><?php echo esc_html((string) ($ui['needsText'] ?? '')); ?></p>
            </div>
            <?php // The saved closing copy (ctaTitle / ctaText) stays above the buttons, so an edited version keeps showing. ?>
            <div class="border-t border-white/10 pt-6">
                <?php if (trim((string) ($ui['ctaTitle'] ?? '')) !== '') : ?><p class="text-lg font-black leading-snug"><?php echo esc_html((string) $ui['ctaTitle']); ?></p><?php endif; ?>
                <?php if (trim((string) ($ui['ctaText'] ?? '')) !== '') : ?><p class="mt-1.5 max-w-md text-sm font-normal leading-6 text-white/70"><?php echo esc_html((string) $ui['ctaText']); ?></p><?php endif; ?>
            <div class="mt-5 flex flex-wrap gap-3">
                <button class="inline-flex h-12 items-center justify-center gap-2 rounded-sm bg-white px-6 text-sm font-black text-navy transition hover:bg-blue-50" type="button" data-inquiry-modal-open data-inquiry-source="装车出口页"><?php echo esc_html((string) ($ui['ctaButton'] ?? 'Send an Inquiry')); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></button>
                <a class="inline-flex h-12 items-center justify-center rounded-sm border border-white/30 px-6 text-sm font-black text-white transition hover:border-white" href="<?php echo esc_url(boblift_url('/contact.html')); ?>"><?php echo esc_html((string) ($ui['ctaLink'] ?? 'Contact us')); ?></a>
            </div>
            </div>
        </div>
        <?php if ($needs !== []) : ?>
            <ol class="divide-y divide-white/10 border-t border-white/10 px-6 sm:px-10 lg:border-s lg:border-t-0 xl:px-12">
                <?php foreach ($needs as $index => $need) : ?>
                    <li class="flex items-start gap-4 py-5 md:py-6">
                        <span class="grid size-8 shrink-0 place-items-center rounded-sm border border-white/25 text-xs font-black text-white/80 [font-variant-numeric:tabular-nums]"><?php echo esc_html($pad($index + 1)); ?></span>
                        <p class="pt-1 text-base font-black leading-6 md:text-lg"><?php echo esc_html($need); ?></p>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </div>
</section>

<?php get_footer(); ?>
