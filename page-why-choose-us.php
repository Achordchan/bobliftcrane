<?php
/**
 * Template Name: BOB-LIFT Why Choose Us
 */
get_header();

$ui = boblift_site_ui('whyChoose');
$tour_ui = boblift_site_ui('factoryTour');
$about_page = boblift_about_page_options();
$is_zh = str_starts_with(function_exists('boblift_current_language_slug') ? boblift_current_language_slug() : 'en', 'zh');

// Certificates, factory facts and export figures come from the About and Factory Tour settings, so they are edited in one place.
$certification_items = array_values(array_filter(array_map(static function ($item): array {
    if (!is_array($item)) {
        return [];
    }
    $image = is_array($item['image'] ?? null) ? $item['image'] : ['id' => 0, 'path' => (string) ($item['image'] ?? '')];
    return ['image' => $image, 'title' => (string) ($item['title'] ?? ''), 'text' => (string) ($item['text'] ?? '')];
}, (array) ($about_page['certifications']['items'] ?? [])), static fn(array $item): bool => $item !== [] && ((int) ($item['image']['id'] ?? 0) > 0 || trim((string) ($item['image']['path'] ?? '')) !== '')));
$facts = array_values(array_filter((array) ($tour_ui['facts'] ?? []), static fn($fact): bool => is_array($fact) && trim((string) ($fact['value'] ?? '')) !== ''));
$stats = array_values(array_filter((array) ($about_page['global_footprint']['stats'] ?? []), static fn($stat): bool => is_array($stat) && trim((string) ($stat['number'] ?? '')) !== ''));
if ($is_zh) {
    $stat_labels = ['Countries Exported' => '出口国家和地区', 'Years Experience' => '经验年限', 'Units Delivered' => '累计交付设备', 'Cranes Exported' => '已出口起重机数量'];
    foreach ($stats as $index => $stat) {
        $stats[$index]['label'] = $stat_labels[(string) ($stat['label'] ?? '')] ?? (string) ($stat['label'] ?? '');
    }
}
$applications = array_values(array_filter((array) ($ui['applications'] ?? []), static fn($item): bool => is_array($item) && trim((string) ($item['title'] ?? '')) !== ''));
$services = array_values(array_filter((array) ($ui['services'] ?? []), static fn($item): bool => is_array($item) && trim((string) ($item['title'] ?? '')) !== ''));
$pad = static fn(int $n): string => str_pad((string) $n, 2, '0', STR_PAD_LEFT);
$link_class = 'inline-flex w-fit items-center gap-1.5 text-sm font-black text-blue-700 hover:text-blue-900';
?>
<div class="page-shell pt-5 md:pt-8">
    <?php
    boblift_page_hero([
        'label' => (string) ($ui['heroLabel'] ?? 'Why Choose BOB-LIFT'),
        'title' => (string) ($ui['heroTitle'] ?? ''),
        'text' => (string) ($ui['heroText'] ?? ''),
        'image' => boblift_image_src('images/factory/hall-finished-row.webp'),
        'image_alt' => (string) ($ui['heroImageAlt'] ?? ''),
        'image_position' => 'center 50%',
    ]);
    ?>
</div>

<?php if ($applications !== []) : ?>
<section class="py-12 md:py-16" aria-labelledby="why-applications-title">
    <div class="page-shell">
        <p class="section-label mb-3"><?php echo esc_html((string) ($ui['applicationsLabel'] ?? 'Product Application')); ?></p>
        <h2 id="why-applications-title" class="max-w-[26ch] text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em] text-ink"><?php echo esc_html((string) ($ui['applicationsTitle'] ?? '')); ?></h2>
        <p class="mt-3 max-w-2xl text-sm font-normal leading-7 text-slate-600 md:text-base"><?php echo esc_html((string) ($ui['applicationsText'] ?? '')); ?></p>
        <?php
        // The last tile stretches to the end of its row, so an odd count leaves no empty cell.
        $application_spans = array_fill(0, count($applications), 1);
        $application_fill = [
            ([2 => 'col-span-2'])[boblift_grid_last_item_span($application_spans, 2)] ?? '',
            ([1 => 'md:col-span-1', 2 => 'md:col-span-2', 3 => 'md:col-span-3', 4 => 'md:col-span-4'])[boblift_grid_last_item_span($application_spans, 4)] ?? '',
        ];
        ?>
        <ul class="mt-8 grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-4">
            <?php foreach ($applications as $index => $item) : ?>
                <li class="rounded border border-line bg-white p-4 shadow-card md:p-5 <?php echo $index === count($applications) - 1 ? esc_attr(trim(implode(' ', $application_fill))) : ''; ?>">
                    <span class="text-xs font-black tabular-nums tracking-[0.14em] text-blue-700"><?php echo esc_html($pad($index + 1)); ?></span>
                    <p class="mt-2 text-base font-black text-ink md:text-lg"><?php echo esc_html((string) $item['title']); ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
        <a class="mt-6 <?php echo esc_attr($link_class); ?>" href="<?php echo esc_url(boblift_url('/cases.html')); ?>"><?php echo esc_html((string) ($ui['applicationsLink'] ?? 'See customer cases')); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
    </div>
</section>
<?php endif; ?>

<?php if ($certification_items !== []) : ?>
<section class="border-t border-line bg-mist py-12 md:py-16" aria-labelledby="why-certificates-title" data-about-certifications>
    <div class="page-shell">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between md:gap-10">
            <div>
                <p class="section-label mb-3"><?php echo esc_html((string) ($ui['certificatesLabel'] ?? 'Our Certificate')); ?></p>
                <h2 id="why-certificates-title" class="text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em] text-ink"><?php echo esc_html((string) ($ui['certificatesTitle'] ?? '')); ?></h2>
            </div>
            <p class="max-w-xl text-sm font-normal leading-7 text-slate-600"><?php echo esc_html((string) ($ui['certificatesText'] ?? '')); ?></p>
        </div>
        <?php // Phones swipe through the documents in one row instead of scrolling past a long two-column wall. ?>
        <ul class="-mx-4 mt-8 flex snap-x snap-mandatory scroll-px-4 gap-3 overflow-x-auto px-4 pb-2 [scrollbar-width:none] sm:mx-0 sm:grid sm:snap-none sm:grid-cols-3 sm:overflow-visible sm:px-0 sm:pb-0 lg:grid-cols-5 lg:gap-4 [&::-webkit-scrollbar]:hidden">
            <?php foreach ($certification_items as $certification) :
                $full_url = boblift_image_setting_url($certification['image'], (string) ($certification['image']['path'] ?? ''));
                // Cards use the light "-sm" scan shipped next to theme documents; the viewer opens the full page.
                $path = (string) ($certification['image']['path'] ?? '');
                $small = (string) preg_replace('/\.webp$/', '-sm.webp', $path);
                $card_url = (int) ($certification['image']['id'] ?? 0) === 0 && $small !== $path && is_readable(get_template_directory() . '/assets/' . $small) ? boblift_image_src($small) : $full_url;
                $title = $certification['title'] !== '' ? $certification['title'] : (string) ($ui['certificatesLabel'] ?? 'Our Certificate');
            ?>
                <li class="w-[44%] shrink-0 snap-start sm:w-auto">
                    <button class="group flex h-full w-full cursor-zoom-in flex-col rounded border border-line bg-white p-2 text-start shadow-card transition hover:-translate-y-1" type="button" data-certification-open data-certification-src="<?php echo esc_url($full_url); ?>" data-certification-title="<?php echo esc_attr($title); ?>">
                        <span class="block overflow-hidden rounded bg-[#F8FAFC] ring-1 ring-[#E5E7EB]">
                            <img class="aspect-[5/7] w-full object-contain p-2" src="<?php echo esc_url($card_url); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy" decoding="async">
                        </span>
                        <span class="block px-1 pb-1 pt-3 text-sm font-black leading-tight text-ink"><?php echo esc_html($title); ?></span>
                        <?php if ($certification['text'] !== '') : ?>
                            <span class="mt-1 block px-1 pb-1 text-xs font-normal leading-5 text-slate-500"><?php echo esc_html($certification['text']); ?></span>
                        <?php endif; ?>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>
        <a class="mt-6 <?php echo esc_attr($link_class); ?>" href="<?php echo esc_url(boblift_url('/about.html') . '#about-certifications'); ?>"><?php echo esc_html((string) ($ui['certificatesLink'] ?? 'More on the About page')); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
    </div>
</section>
<div class="fixed inset-0 z-[999] hidden items-center justify-center bg-[#0B1B33]/88 p-4 backdrop-blur-md" data-certification-lightbox aria-hidden="true">
    <button class="absolute inset-0 cursor-zoom-out" type="button" data-certification-close aria-label="<?php echo esc_attr((string) ($ui['closeLabel'] ?? 'Close certificate preview')); ?>"></button>
    <figure class="relative z-10 max-h-[92vh] w-full max-w-[980px] rounded bg-white p-3 shadow-card md:p-4">
        <button class="absolute end-3 top-3 grid h-10 w-10 place-items-center rounded-sm bg-[#0B1B33] text-white shadow-card transition hover:bg-[#0B3F7D]" type="button" data-certification-close aria-label="<?php echo esc_attr((string) ($ui['closeLabel'] ?? 'Close certificate preview')); ?>">×</button>
        <img class="max-h-[78vh] w-full rounded object-contain" src="" alt="" data-certification-lightbox-image>
        <figcaption class="px-2 pb-1 pt-3 text-center text-sm font-black text-[#0B1B33]" data-certification-lightbox-title></figcaption>
    </figure>
</div>
<?php endif; ?>

<section class="py-12 md:py-16" aria-labelledby="why-production-title">
    <div class="page-shell grid items-center gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)] lg:gap-14">
        <div>
            <p class="section-label mb-3"><?php echo esc_html((string) ($ui['productionLabel'] ?? 'Production Equipment')); ?></p>
            <h2 id="why-production-title" class="text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em] text-ink"><?php echo esc_html((string) ($ui['productionTitle'] ?? '')); ?></h2>
            <p class="mt-4 text-sm font-normal leading-7 text-slate-600 md:text-base"><?php echo esc_html((string) ($ui['productionText'] ?? '')); ?></p>
            <?php if ($facts !== []) : ?>
                <dl class="mt-6 flex flex-wrap gap-x-10 gap-y-4">
                    <?php foreach ($facts as $fact) : ?>
                        <div><dt class="text-xs font-bold text-slate-500"><?php echo esc_html((string) ($fact['label'] ?? '')); ?></dt><dd class="mt-1 text-[clamp(1.4rem,2.2vw,2rem)] font-black leading-none tracking-[-0.015em] text-ink"><?php echo esc_html((string) $fact['value']); ?></dd></div>
                    <?php endforeach; ?>
                </dl>
            <?php endif; ?>
            <div class="mt-6 flex flex-wrap gap-x-6 gap-y-3">
                <a class="<?php echo esc_attr($link_class); ?>" href="<?php echo esc_url(boblift_url('/factory-tour.html')); ?>"><?php echo esc_html((string) ($ui['factoryLink'] ?? 'Take the factory tour')); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
                <a class="<?php echo esc_attr($link_class); ?>" href="<?php echo esc_url(boblift_url('/quality-testing.html')); ?>"><?php echo esc_html((string) ($ui['qualityLink'] ?? 'See how cranes are tested')); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
            </div>
        </div>
        <img class="aspect-[4/3] w-full rounded object-cover shadow-card" src="<?php echo esc_url(boblift_image_src('images/factory/hall-workstations.webp')); ?>" alt="<?php echo esc_attr((string) ($ui['productionImageAlt'] ?? '')); ?>" loading="lazy" decoding="async">
    </div>
</section>

<section class="bg-navy py-12 text-white md:py-16" aria-labelledby="why-market-title">
    <div class="page-shell grid gap-8 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)] lg:items-end lg:gap-14">
        <div>
            <p class="mb-3 text-xs font-black uppercase tracking-[0.28em] text-blue-200"><?php echo esc_html((string) ($ui['marketLabel'] ?? 'Production Market')); ?></p>
            <h2 id="why-market-title" class="text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em]"><?php echo esc_html((string) ($ui['marketTitle'] ?? '')); ?></h2>
            <p class="mt-4 max-w-2xl text-sm font-normal leading-7 text-white/75 md:text-base"><?php echo esc_html((string) ($ui['marketText'] ?? '')); ?></p>
            <a class="mt-6 inline-flex w-fit items-center gap-1.5 text-sm font-black text-white hover:text-blue-100" href="<?php echo esc_url(boblift_url('/global-customer-visits.html')); ?>"><?php echo esc_html((string) ($ui['visitsLink'] ?? 'Customer visits to our factory')); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
        </div>
        <?php if ($stats !== []) : ?>
            <dl class="grid grid-cols-2 gap-4">
                <?php foreach ($stats as $stat) : ?>
                    <div class="flex flex-col-reverse rounded border border-white/15 p-5"><dt class="mt-2 text-xs font-bold text-white/70"><?php echo esc_html((string) ($stat['label'] ?? '')); ?></dt><dd class="text-[clamp(2rem,3.4vw,3.25rem)] font-black leading-none tracking-[-0.02em]"><?php echo esc_html((string) $stat['number'] . (string) ($stat['suffix'] ?? '')); ?></dd></div>
                <?php endforeach; ?>
            </dl>
        <?php endif; ?>
    </div>
</section>

<?php if ($services !== []) : ?>
<section class="py-12 md:py-16" aria-labelledby="why-service-title">
    <div class="page-shell">
        <p class="section-label mb-3"><?php echo esc_html((string) ($ui['serviceLabel'] ?? 'Our Service')); ?></p>
        <h2 id="why-service-title" class="max-w-[26ch] text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em] text-ink"><?php echo esc_html((string) ($ui['serviceTitle'] ?? '')); ?></h2>
        <ol class="mt-8 grid gap-3 md:grid-cols-3 md:gap-5">
            <?php foreach ($services as $index => $service) : ?>
                <li class="rounded border border-line bg-white p-5 shadow-card md:p-6">
                    <span class="text-xs font-black tabular-nums tracking-[0.14em] text-blue-700"><?php echo esc_html($pad($index + 1)); ?></span>
                    <h3 class="mt-2 text-lg font-black text-ink md:text-xl"><?php echo esc_html((string) $service['title']); ?></h3>
                    <p class="mt-2 text-sm font-normal leading-6 text-slate-600"><?php echo esc_html((string) ($service['text'] ?? '')); ?></p>
                </li>
            <?php endforeach; ?>
        </ol>
        <a class="mt-6 <?php echo esc_attr($link_class); ?>" href="<?php echo esc_url(boblift_url('/service.html')); ?>"><?php echo esc_html((string) ($ui['serviceLink'] ?? 'Service and operation guides')); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
    </div>
</section>
<?php endif; ?>

<section class="page-shell pb-12 md:pb-16" aria-labelledby="why-cta-title">
    <div class="flex flex-col gap-6 rounded bg-navy p-6 text-white sm:p-8 lg:flex-row lg:items-center lg:justify-between">
        <div class="max-w-xl">
            <h2 id="why-cta-title" class="text-[clamp(1.5rem,2.2vw,2.25rem)] font-black leading-[1.08] tracking-[-0.015em]"><?php echo esc_html((string) ($ui['ctaTitle'] ?? '')); ?></h2>
            <p class="mt-3 text-sm font-normal leading-7 text-white/75 md:text-base"><?php echo esc_html((string) ($ui['ctaText'] ?? '')); ?></p>
        </div>
        <div class="flex shrink-0 flex-wrap gap-3">
            <button class="inline-flex h-12 items-center justify-center gap-2 rounded-sm bg-white px-6 text-sm font-black text-navy transition hover:-translate-y-0.5 hover:bg-blue-50" type="button" data-inquiry-modal-open data-inquiry-source="为什么选择页"><?php echo esc_html((string) ($ui['ctaButton'] ?? 'Send an Inquiry')); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></button>
            <a class="inline-flex h-12 items-center justify-center rounded-sm border border-white/30 px-6 text-sm font-black text-white transition hover:border-white" href="<?php echo esc_url(boblift_url('/products.html')); ?>"><?php echo esc_html((string) ($ui['ctaLink'] ?? 'View products')); ?></a>
        </div>
    </div>
</section>

<?php get_footer(); ?>
