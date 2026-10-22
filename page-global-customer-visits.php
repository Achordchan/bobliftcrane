<?php
/**
 * Template Name: BOB-LIFT Global Customer Visits
 */
get_header();

$about_page = function_exists('boblift_about_page_options') ? boblift_about_page_options() : [];
$visits = is_array($about_page['customer_visits'] ?? null) ? $about_page['customer_visits'] : [];
$t = static fn(mixed $text): string => boblift_localized_static_text(trim((string) $text));
$has_image = static function ($image): bool {
    if (is_array($image)) {
        return (int) ($image['id'] ?? 0) > 0 || trim((string) ($image['path'] ?? '')) !== '';
    }
    return trim((string) $image) !== '';
};
// Grid tiles use the light "-sm" cut shipped next to theme photos, or the medium_large size of an upload.
$thumb_url = static function ($image): string {
    $id = is_array($image) ? (int) ($image['id'] ?? 0) : 0;
    if ($id > 0) {
        return (string) (wp_get_attachment_image_url($id, 'medium_large') ?: boblift_image_setting_url($image));
    }
    $path = is_array($image) ? (string) ($image['path'] ?? '') : (string) $image;
    $small = preg_replace('/\.webp$/', '-sm.webp', $path);
    if ($small !== $path && is_readable(get_template_directory() . '/assets/' . $small)) {
        return boblift_image_src($small);
    }
    return boblift_image_setting_url($image);
};

// Photos are grouped by the country set on each one; photos without a country close the gallery.
$groups = [];
$more_key = '__more';
foreach ((array) ($visits['items'] ?? []) as $item) {
    if (!is_array($item) || !$has_image($item['image'] ?? '')) {
        continue;
    }
    $country = $t($item['country'] ?? '');
    $key = $country !== '' ? $country : $more_key;
    $groups[$key][] = $item;
}
$more = $groups[$more_key] ?? [];
unset($groups[$more_key]);
$country_count = count($groups);
if ($more) {
    $groups[$more_key] = $more;
}
$gallery = [];
$captions = [];
$cards = [];
foreach ($groups as $key => $photos) {
    $label = $key === $more_key ? $t($visits['more_visits_label'] ?? 'More factory visits') : $key;
    $cards[] = ['label' => $label, 'start' => count($gallery), 'count' => count($photos), 'image' => $photos[0]['image'], 'alt' => $t($photos[0]['title'] ?? $label)];
    foreach ($photos as $photo) {
        $gallery[] = boblift_image_setting_url($photo['image']);
        $captions[] = $label;
    }
}
$steps = array_values(array_filter((array) ($visits['steps'] ?? []), static fn($step): bool => is_array($step) && trim((string) ($step['title'] ?? '')) !== ''));
$plan_points = array_values(array_filter((array) ($visits['plan_points'] ?? []), static fn($point): bool => is_array($point) && trim((string) ($point['text'] ?? '')) !== ''));
$detail_ui = boblift_site_ui('productDetail');
$pad = static fn(int $n): string => str_pad((string) $n, 2, '0', STR_PAD_LEFT);
$wall_alt = $t($visits['label'] ?? 'Global Customer Visits');
?>
<div class="page-shell pb-8 pt-5 md:pb-10 md:pt-8">
    <?php
    boblift_page_hero([
        'label' => (string) ($visits['label'] ?? 'Global Customer Visits'),
        'title' => (string) ($visits['title'] ?? 'Welcoming partners to see how BOB-LIFT builds.'),
        'text' => (string) ($visits['text'] ?? ''),
        'meta' => $country_count > 0 ? $country_count . ' ' . $t($visits['country_count_suffix'] ?? 'countries') : '',
        'image' => boblift_image_setting_url($visits['hero_image'] ?? [], 'images/page-heroes/factory-campus.webp'),
        'image_alt' => (string) ($visits['hero_image_alt'] ?? 'The BOB-LIFT factory campus from the air'),
    ]);
    ?>
    <div class="mt-6 md:mt-8"><?php boblift_about_hub_nav('global-customer-visits'); ?></div>
</div>

<?php if ($country_count > 0) : ?>
<section class="page-shell pb-12 md:pb-16" aria-labelledby="visit-countries-title">
    <div class="grid gap-6 rounded border border-line bg-white p-6 shadow-card sm:p-8 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.6fr)] lg:items-center lg:gap-12 xl:p-10">
        <div>
            <p class="section-label mb-3"><?php echo esc_html($t($visits['countries_label'] ?? 'Visitors from')); ?></p>
            <h2 id="visit-countries-title" class="text-[clamp(1.5rem,2.2vw,2.25rem)] font-black leading-[1.1] tracking-[-0.01em] text-ink"><?php echo esc_html(str_replace('{count}', (string) $country_count, $t($visits['countries_title'] ?? 'Customers from {count} countries have walked our workshop.'))); ?></h2>
        </div>
        <ul class="flex flex-wrap gap-2">
            <?php foreach (array_keys($groups) as $key) : if ($key === $more_key) { continue; } ?>
                <li class="inline-flex h-10 items-center rounded-sm border border-line bg-mist px-3.5 text-sm font-bold text-navy"><?php echo esc_html($key); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
<?php endif; ?>

<?php if ($steps) : ?>
<section class="bg-[#F8FAFC] py-12 md:py-16 lg:py-20" aria-labelledby="visit-steps-title">
    <div class="page-shell">
        <p class="section-label mb-3"><?php echo esc_html($t($visits['steps_label'] ?? 'What a visit covers')); ?></p>
        <h2 id="visit-steps-title" class="max-w-[28ch] text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em] text-ink"><?php echo esc_html($t($visits['steps_title'] ?? '')); ?></h2>
        <ol class="mt-8 grid gap-5 sm:grid-cols-2 md:mt-10 lg:grid-cols-4 xl:gap-6">
            <?php foreach ($steps as $index => $step) : ?>
                <li class="flex flex-col overflow-hidden rounded border border-line bg-white shadow-card">
                    <?php if ($has_image($step['image'] ?? '')) : ?>
                        <div class="relative aspect-[16/9] overflow-hidden bg-mist sm:aspect-[4/3]"><img class="absolute inset-0 h-full w-full object-cover" src="<?php echo esc_url($thumb_url($step['image'])); ?>" alt="<?php echo esc_attr($t($step['title'])); ?>" loading="lazy" decoding="async"></div>
                    <?php endif; ?>
                    <div class="flex flex-1 flex-col p-5 xl:p-6">
                        <span class="text-xs font-black tabular-nums tracking-[0.14em] text-blue-700"><?php echo esc_html($pad($index + 1)); ?></span>
                        <h3 class="mt-2 text-lg font-black leading-snug text-ink xl:text-xl"><?php echo esc_html($t($step['title'])); ?></h3>
                        <?php if (trim((string) ($step['text'] ?? '')) !== '') : ?><p class="mt-2 text-sm font-normal leading-6 text-slate-600"><?php echo esc_html($t($step['text'])); ?></p><?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>
<?php endif; ?>

<section class="py-12 md:py-16 lg:py-20" aria-labelledby="visit-wall-title">
    <div class="page-shell" data-detail-viewer data-detail-images="<?php echo esc_attr(wp_json_encode($gallery, JSON_UNESCAPED_SLASHES)); ?>" data-detail-alt="<?php echo esc_attr($wall_alt); ?>" data-detail-captions="<?php echo esc_attr(wp_json_encode($captions, JSON_UNESCAPED_UNICODE)); ?>">
        <div class="mb-8 flex flex-col gap-3 md:mb-10 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="section-label mb-3"><?php echo esc_html($t($visits['wall_label'] ?? 'Visit gallery')); ?></p>
                <h2 id="visit-wall-title" class="text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em] text-ink"><?php echo esc_html($t($visits['wall_title'] ?? 'Photos from recent visits')); ?></h2>
            </div>
            <?php if ($cards) : ?><p class="max-w-md text-sm font-normal leading-6 text-slate-500"><?php echo esc_html($t($visits['wall_text'] ?? '')); ?></p><?php endif; ?>
        </div>
        <?php if ($cards) : ?>
            <?php
            // The last card stretches to the end of its row at each column count, so an odd row leaves no empty cell.
            $card_spans = array_fill(0, count($cards), 1);
            $last_fill = [boblift_grid_last_item_span($card_spans, 2), boblift_grid_last_item_span($card_spans, 3), boblift_grid_last_item_span($card_spans, 5)];
            $last_classes = trim(implode(' ', [
                ([2 => 'col-span-2'])[$last_fill[0]] ?? '',
                ([1 => 'md:col-span-1', 2 => 'md:col-span-2', 3 => 'md:col-span-3'])[$last_fill[1]] ?? '',
                ([1 => 'lg:col-span-1', 2 => 'lg:col-span-2', 3 => 'lg:col-span-3', 4 => 'lg:col-span-4', 5 => 'lg:col-span-5'])[$last_fill[2]] ?? '',
            ]));
            // A card stretched across both phone columns gets a wider photo so it is not twice as tall as the rest.
            $last_media = $last_fill[0] === 2 ? 'aspect-[8/3] md:aspect-[4/3]' : 'aspect-[4/3]';
            ?>
            <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-5 xl:gap-5">
                <?php foreach (array_values($cards) as $card_index => $card) : $is_last_card = $card_index === count($cards) - 1; ?>
                    <button class="<?php echo $is_last_card ? esc_attr($last_classes) . ' ' : ''; ?>group flex min-w-0 flex-col overflow-hidden rounded border border-line bg-white text-left shadow-card transition duration-300 hover:-translate-y-1 hover:border-blue-300 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-blue-100" type="button" data-detail-open="<?php echo esc_attr((string) $card['start']); ?>" aria-label="<?php echo esc_attr($card['label'] . ' · ' . $card['count'] . ' ' . $t($visits['photo_count_suffix'] ?? 'photos')); ?>">
                        <span class="relative block <?php echo esc_attr($is_last_card ? $last_media : 'aspect-[4/3]'); ?> overflow-hidden bg-mist"><img class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]" src="<?php echo esc_url($thumb_url($card['image'])); ?>" alt="<?php echo esc_attr($card['alt']); ?>" loading="lazy" decoding="async"></span>
                        <span class="flex items-center justify-between gap-2 px-3.5 py-3 sm:px-4">
                            <span class="min-w-0 truncate text-sm font-black text-ink sm:text-base"><?php echo esc_html($card['label']); ?></span>
                            <span class="inline-flex shrink-0 items-center gap-1 text-xs font-bold tabular-nums text-slate-400"><?php echo boblift_icon('images', 'h-3.5 w-3.5'); ?><?php echo esc_html((string) $card['count']); ?></span>
                        </span>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="fixed inset-0 z-[1000] hidden flex-col bg-[#040f22]" data-detail-lightbox role="dialog" aria-modal="true" aria-hidden="true" aria-label="<?php echo esc_attr($wall_alt); ?>">
                <div class="flex shrink-0 items-center justify-between px-4 py-3 text-white md:px-6">
                    <p class="flex min-w-0 items-baseline gap-3 text-xs font-black tracking-[0.08em]" aria-live="polite"><span class="shrink-0 [font-variant-numeric:tabular-nums]"><span data-detail-index>01</span> / <?php echo esc_html($pad(count($gallery))); ?></span><span class="truncate text-sm tracking-normal text-white/80" data-detail-caption></span></p>
                    <button class="grid size-10 place-items-center rounded-sm bg-white/10 text-white transition hover:bg-white hover:text-[#071f42]" type="button" data-detail-close aria-label="<?php echo esc_attr($detail_ui['closeLabel'] ?? 'Close'); ?>"><?php echo boblift_icon('x', 'h-5 w-5'); ?></button>
                </div>
                <div class="relative min-h-0 flex-1">
                    <div class="absolute inset-0 flex items-center justify-center overflow-auto px-3 pb-4 md:px-20" data-detail-stage>
                        <img class="block max-h-full max-w-full object-contain" src="" alt="" data-detail-image>
                    </div>
                    <?php if (count($gallery) > 1) : ?>
                        <button class="boblift-feedback-arrow left-2 md:start-5" type="button" data-detail-prev aria-label="<?php echo esc_attr($detail_ui['previousLabel'] ?? 'Previous'); ?>"><?php echo boblift_icon('chevron-left', 'h-5 w-5'); ?></button>
                        <button class="boblift-feedback-arrow right-2 md:end-5" type="button" data-detail-next aria-label="<?php echo esc_attr($detail_ui['nextLabel'] ?? 'Next'); ?>"><?php echo boblift_icon('chevron-right', 'h-5 w-5'); ?></button>
                    <?php endif; ?>
                </div>
            </div>
        <?php else : ?>
            <div class="rounded border border-line bg-[#F8FAFC] p-8 text-center">
                <p class="text-base font-bold text-slate-600"><?php echo esc_html($t($visits['empty_text'] ?? 'No customer visit photos have been added yet.')); ?></p>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="page-shell pb-16 md:pb-24" aria-labelledby="visit-plan-title">
    <div class="grid gap-8 rounded bg-navy p-6 text-white sm:p-10 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)] lg:items-center lg:gap-14 xl:p-14">
        <div>
            <p class="section-label mb-3"><?php echo esc_html($t($visits['plan_label'] ?? 'Plan your visit')); ?></p>
            <h2 id="visit-plan-title" class="max-w-[22ch] text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em]"><?php echo esc_html($t($visits['plan_title'] ?? 'Come and see the crane before you order it.')); ?></h2>
            <p class="mt-4 max-w-xl text-sm font-normal leading-7 text-white/75 md:text-base"><?php echo esc_html($t($visits['plan_text'] ?? '')); ?></p>
            <div class="mt-7 flex flex-wrap gap-3">
                <button class="inline-flex h-12 items-center justify-center gap-2 rounded-sm bg-white px-6 text-sm font-black text-navy transition hover:-translate-y-0.5 hover:bg-blue-50" type="button" data-inquiry-modal-open data-inquiry-source="参厂页"><?php echo esc_html($t($visits['cta_label'] ?? 'Plan a Visit')); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></button>
                <a class="inline-flex h-12 items-center justify-center rounded-sm border border-white/30 px-6 text-sm font-black text-white transition hover:border-white" href="<?php echo esc_url(boblift_url((string) ($visits['cta_href'] ?? '/contact.html'))); ?>"><?php echo esc_html($t($visits['plan_secondary_label'] ?? 'Contact us')); ?></a>
            </div>
        </div>
        <?php if ($plan_points) : ?>
            <dl class="grid gap-px overflow-hidden rounded border border-white/15 bg-white/15">
                <?php foreach ($plan_points as $point) : ?>
                    <div class="bg-navy px-5 py-4"><dt class="text-xs font-bold text-white/55"><?php echo esc_html($t($point['title'] ?? '')); ?></dt><dd class="mt-1 text-base font-black"><?php echo esc_html($t($point['text'])); ?></dd></div>
                <?php endforeach; ?>
            </dl>
        <?php endif; ?>
    </div>
</section>

<?php get_footer(); ?>
