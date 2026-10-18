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
    ?>
</div>

<div data-detail-viewer data-detail-images="<?php echo esc_attr(wp_json_encode($gallery, JSON_UNESCAPED_SLASHES)); ?>" data-detail-alt="<?php echo esc_attr((string) ($ui['heroLabel'] ?? 'Truck Mounting & Export')); ?>" data-detail-captions="<?php echo esc_attr(wp_json_encode($captions, JSON_UNESCAPED_UNICODE)); ?>">
<?php foreach (['mount' => 'py-12 md:py-16', 'pack' => 'border-t border-line bg-mist py-12 md:py-16'] as $section => $section_class) : if (($sections[$section] ?? []) === []) continue; ?>
<section class="<?php echo esc_attr($section_class); ?>" aria-labelledby="mounting-<?php echo esc_attr($section); ?>-title">
    <div class="page-shell grid gap-12 md:gap-16">
        <div>
            <p class="section-label mb-3"><?php echo esc_html((string) ($ui[$section . 'Label'] ?? '')); ?></p>
            <h2 id="mounting-<?php echo esc_attr($section); ?>-title" class="max-w-[26ch] text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em] text-ink"><?php echo esc_html((string) ($ui[$section . 'Title'] ?? '')); ?></h2>
        </div>
        <?php foreach ($sections[$section] as $number => $step) : $photos = $step['photos']; $lead = array_shift($photos); ?>
            <article class="grid gap-5 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,2fr)] lg:gap-10" aria-labelledby="mounting-step-<?php echo esc_attr((string) $step['key']); ?>">
                <div class="lg:sticky lg:top-24 lg:self-start lg:pt-2">
                    <p class="text-xs font-black uppercase tracking-[0.14em] text-blue-700"><?php echo esc_html(str_replace('{number}', $pad($number + 1), (string) ($ui['stepLabel'] ?? 'Step {number}'))); ?></p>
                    <h3 id="mounting-step-<?php echo esc_attr((string) $step['key']); ?>" class="mt-2 text-[clamp(1.5rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em] text-ink"><?php echo esc_html((string) $step['title']); ?></h3>
                    <p class="mt-3 max-w-md text-sm font-semibold leading-7 text-slate-600 md:text-base"><?php echo esc_html((string) ($step['text'] ?? '')); ?></p>
                </div>
                <?php
                // Literal class names so Tailwind keeps them: the lead photo spans two rows beside the smaller ones.
                $mosaic = [0 => ['grid-cols-1', 'col-span-1'], 1 => ['grid-cols-2', 'col-span-1'], 2 => ['grid-cols-2 md:grid-cols-3', 'col-span-2 md:row-span-2'], 3 => ['grid-cols-2 md:grid-cols-3', 'col-span-2 md:col-span-3']][count($photos)] ?? ['grid-cols-2 md:grid-cols-3', 'col-span-2 md:col-span-3'];
                ?>
                <div class="grid gap-2 sm:gap-3 <?php echo esc_attr($mosaic[0]); ?>">
                    <button class="group relative overflow-hidden rounded bg-white <?php echo esc_attr($mosaic[1]); ?>" type="button" data-detail-open="<?php echo esc_attr((string) $lead['index']); ?>" aria-label="<?php echo esc_attr((string) $step['title'] . ' ' . $pad($lead['index'] + 1)); ?>">
                        <img class="aspect-[4/3] h-full w-full object-cover transition duration-500 group-hover:scale-[1.03] <?php echo count($photos) === 2 ? 'md:aspect-auto' : (count($photos) === 3 ? 'md:aspect-[16/7]' : ''); ?>" src="<?php echo esc_url($lead['full']); ?>" alt="<?php echo esc_attr((string) $step['title']); ?>" loading="lazy" decoding="async">
                    </button>
                    <?php foreach ($photos as $item) : ?>
                        <button class="group relative overflow-hidden rounded bg-white" type="button" data-detail-open="<?php echo esc_attr((string) $item['index']); ?>" aria-label="<?php echo esc_attr((string) $step['title'] . ' ' . $pad($item['index'] + 1)); ?>">
                            <img class="aspect-[4/3] h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]" src="<?php echo esc_url($item['thumb']); ?>" alt="<?php echo esc_attr((string) $step['title']); ?>" loading="lazy" decoding="async">
                        </button>
                    <?php endforeach; ?>
                </div>
            </article>
        <?php endforeach; ?>
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

<?php if ($needs !== []) : ?>
<section class="page-shell py-12 md:py-16" aria-labelledby="mounting-needs-title">
    <div class="grid gap-8 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,2fr)] lg:gap-10">
        <div class="lg:sticky lg:top-24 lg:self-start">
            <p class="section-label mb-3"><?php echo esc_html((string) ($ui['needsLabel'] ?? '')); ?></p>
            <h2 id="mounting-needs-title" class="text-[clamp(1.5rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em] text-ink"><?php echo esc_html((string) ($ui['needsTitle'] ?? '')); ?></h2>
            <p class="mt-3 max-w-md text-sm font-semibold leading-7 text-slate-600 md:text-base"><?php echo esc_html((string) ($ui['needsText'] ?? '')); ?></p>
        </div>
        <ol class="grid gap-3 sm:grid-cols-2 md:gap-4">
            <?php foreach ($needs as $index => $need) : ?>
                <li class="rounded border border-line bg-white p-5 shadow-card">
                    <span class="text-xs font-black tabular-nums tracking-[0.14em] text-blue-700"><?php echo esc_html($pad($index + 1)); ?></span>
                    <p class="mt-2 text-base font-black leading-6 text-ink"><?php echo esc_html($need); ?></p>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>
<?php endif; ?>

<section class="page-shell pb-12 md:pb-16" aria-labelledby="mounting-cta-title">
    <div class="flex flex-col gap-6 rounded bg-navy p-6 text-white sm:p-10 lg:flex-row lg:items-center lg:justify-between xl:p-12">
        <div class="max-w-2xl">
            <h2 id="mounting-cta-title" class="text-[clamp(1.5rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em]"><?php echo esc_html((string) ($ui['ctaTitle'] ?? '')); ?></h2>
            <p class="mt-3 text-sm font-semibold leading-7 text-white/75 md:text-base"><?php echo esc_html((string) ($ui['ctaText'] ?? '')); ?></p>
        </div>
        <div class="flex shrink-0 flex-wrap gap-3">
            <button class="inline-flex h-12 items-center justify-center gap-2 rounded-sm bg-white px-6 text-sm font-black text-navy transition hover:-translate-y-0.5 hover:bg-blue-50" type="button" data-inquiry-modal-open data-inquiry-source="装车出口页"><?php echo esc_html((string) ($ui['ctaButton'] ?? 'Send an Inquiry')); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></button>
            <a class="inline-flex h-12 items-center justify-center rounded-sm border border-white/30 px-6 text-sm font-black text-white transition hover:border-white" href="<?php echo esc_url(boblift_url('/contact.html')); ?>"><?php echo esc_html((string) ($ui['ctaLink'] ?? 'Contact us')); ?></a>
        </div>
    </div>
</section>

<?php get_footer(); ?>
