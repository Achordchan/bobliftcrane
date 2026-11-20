<?php
/**
 * The 2026 year-end offers page (/year-end-specials.html in every language), routed by inc/year-end-offers.php.
 * The offers' size and rules only: no prices and no special-price models (company policy). After the last day the
 * offers and the claim section are replaced by a short "ended" note and the way to ask for a quotation.
 */
get_header();

$copy = boblift_offer_copy();
$t = 'boblift_offer_t';
$ended = boblift_offer_ended();
$hero = $copy['hero'];
$coupons = $copy['coupons'];
$deposit = $copy['deposit'];
$terms = $copy['terms'];
$claim = $copy['claim'];
$contact = boblift_contact_settings();
$whatsapp = 'https://wa.me/' . $contact['whatsappNumber'] . '?text=' . rawurlencode(boblift_offer_t($claim['whatsapp_text']));
$quote_source = '年终活动页';
$quote_button = static function (string $label, string $entry, string $classes) use ($quote_source): string {
    return '<button class="' . esc_attr($classes) . '" type="button" data-inquiry-modal-open data-inquiry-source="' . esc_attr($quote_source . ' · ' . $entry) . '">' . esc_html($label) . '</button>';
};
$primary = 'inline-flex h-12 items-center justify-center gap-2 rounded-sm bg-gold px-6 text-sm font-black text-navy transition hover:bg-white';
$secondary = 'inline-flex h-12 items-center justify-center gap-2 rounded-sm border border-navy/20 bg-white px-6 text-sm font-black text-navy transition hover:border-blue-300 hover:text-blue-700';
?>
<section class="boblift-offer-hero relative overflow-hidden bg-navy text-white" aria-labelledby="offer-title"<?php echo $ended ? '' : ' data-offer-page="' . esc_attr(BOBLIFT_OFFER_CODE) . '" data-offer-ends="' . esc_attr(boblift_offer_ends_at()) . '"'; ?>>
    <div class="page-shell relative py-6 md:py-10 lg:py-14">
        <nav class="mb-6 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs font-bold text-white/60 md:mb-8" aria-label="<?php echo esc_attr($t('Breadcrumb')); ?>">
            <a class="transition hover:text-white" href="<?php echo esc_url(boblift_public_path_url('/')); ?>"><?php echo esc_html($t('Home')); ?></a>
            <span aria-hidden="true">/</span>
            <span aria-current="page"><?php echo esc_html($t($hero['breadcrumb'])); ?></span>
        </nav>
        <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)] lg:items-center lg:gap-14">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.12em] text-gold"><?php echo esc_html($t($hero['label'])); ?></p>
                <h1 id="offer-title" class="mt-3 text-[clamp(2.1rem,4.2vw,4.25rem)] font-black leading-[1.03] tracking-[-0.02em] [text-wrap:balance]"><?php echo esc_html($t($hero['title'])); ?></h1>
                <?php if ($ended) : ?>
                    <p class="mt-5 max-w-xl text-base font-normal leading-7 text-white/85 md:text-lg md:leading-8"><?php echo esc_html($t($hero['ended'])); ?></p>
                    <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                        <?php echo $quote_button($t($claim['button']), '活动结束后', $primary); ?>
                        <a class="inline-flex h-12 items-center justify-center gap-2 rounded-sm border border-white/30 px-6 text-sm font-black text-white transition hover:border-white" href="<?php echo esc_url(boblift_product_archive_url()); ?>"><?php echo esc_html($t($claim['browse'])); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4 rtl:rotate-180'); ?></a>
                    </div>
                <?php else : ?>
                    <p class="mt-5 max-w-xl text-base font-normal leading-7 text-white/85 md:text-lg md:leading-8"><?php echo esc_html($t($hero['text'])); ?></p>
                    <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:items-center">
                        <?php echo $quote_button($t($hero['button']), '首屏', $primary); ?>
                        <a class="inline-flex min-h-11 items-center justify-center gap-1.5 text-sm font-black text-white/85 underline-offset-4 transition hover:text-white hover:underline sm:px-3" href="#offer-terms"><?php echo esc_html($t($hero['link'])); ?><?php echo boblift_icon('arrow-down', 'h-4 w-4'); ?></a>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (!$ended) : ?>
                <?php // Both offers at a glance in the first screen: the coupons, then the deposit tiers. ?>
                <div class="grid gap-3" data-offer-live>
                    <div class="rounded border border-white/15 bg-white/[0.06] p-4 md:p-5">
                        <p class="text-[11px] font-black uppercase tracking-[0.12em] text-white/60"><?php echo esc_html($t($coupons['label'])); ?></p>
                        <ul class="mt-3 grid grid-cols-3 gap-2 md:gap-3">
                            <?php foreach ($coupons['items'] as $item) : ?>
                                <li class="boblift-offer-ticket rounded-sm border border-dashed border-gold/70 bg-navy px-2 py-3 text-center md:px-3 md:py-4">
                                    <span class="block text-[clamp(1.15rem,2.2vw,2rem)] font-black leading-none tabular-nums text-gold"><?php echo esc_html($item['save']); ?></span>
                                    <span class="mt-1 block text-[11px] font-black uppercase tracking-[0.08em] text-white"><?php echo esc_html($t($coupons['off'])); ?></span>
                                    <span class="mt-2 block text-[11px] font-normal leading-4 text-white/70 md:text-xs"><?php echo esc_html($t($item['over'])); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="rounded border border-white/15 bg-white/[0.06] p-4 md:p-5">
                        <p class="text-[11px] font-black uppercase tracking-[0.12em] text-white/60"><?php echo esc_html($t($deposit['label'])); ?></p>
                        <ul class="mt-3 grid grid-cols-3 gap-2 md:gap-3">
                            <?php foreach ($deposit['rows'] as [$order, $pay, $worth]) : ?>
                                <li class="rounded-sm bg-navy px-2 py-3 text-center md:px-3">
                                    <span class="block text-sm font-black tabular-nums text-white md:text-base"><?php echo esc_html($pay); ?> <span class="text-white/60" aria-hidden="true">→</span> <span class="text-gold"><?php echo esc_html($worth); ?></span></span>
                                    <span class="mt-1.5 block text-[11px] font-normal leading-4 text-white/70 md:text-xs"><?php echo esc_html($t($order)); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if (!$ended) : ?>
<section id="offer-coupons" data-offer-live class="scroll-mt-24 py-12 md:py-16" aria-labelledby="offer-coupons-title">
    <div class="page-shell">
        <p class="section-label mb-3"><?php echo esc_html($t($coupons['label'])); ?></p>
        <h2 id="offer-coupons-title" class="max-w-[26ch] text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em] text-ink"><?php echo esc_html($t($coupons['title'])); ?></h2>
        <ol class="mt-7 grid gap-3 sm:grid-cols-3 md:mt-9 md:gap-5">
            <?php foreach ($coupons['items'] as $index => $item) : ?>
                <li class="boblift-offer-coupon relative overflow-hidden rounded border border-line bg-white p-5 shadow-card md:p-7">
                    <span class="text-xs font-black tabular-nums text-slate-500"><?php echo esc_html(sprintf('%02d', $index + 1)); ?></span>
                    <span class="mt-3 block text-[clamp(2.25rem,3.6vw,3.5rem)] font-black leading-none tracking-[-0.02em] tabular-nums text-navy"><?php echo esc_html($item['save']); ?></span>
                    <span class="mt-1 block text-sm font-black uppercase tracking-[0.1em] text-blue-700"><?php echo esc_html($t($coupons['off'])); ?></span>
                    <span class="mt-4 block border-t border-dashed border-line pt-4 text-base font-bold leading-6 text-ink"><?php echo esc_html($t($item['over'])); ?></span>
                </li>
            <?php endforeach; ?>
        </ol>
        <p class="mt-5 max-w-3xl text-sm font-normal leading-6 text-slate-600 md:text-base md:leading-7"><?php echo esc_html($t($coupons['note'])); ?></p>
    </div>
</section>

<section id="offer-deposit" data-offer-live class="scroll-mt-24 border-y border-line bg-mist py-12 md:py-16" aria-labelledby="offer-deposit-title">
    <div class="page-shell grid gap-8 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)] lg:gap-14">
        <div>
            <p class="section-label mb-3"><?php echo esc_html($t($deposit['label'])); ?></p>
            <h2 id="offer-deposit-title" class="max-w-[22ch] text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em] text-ink"><?php echo esc_html($t($deposit['title'])); ?></h2>
            <p class="mt-4 max-w-2xl text-base font-normal leading-7 text-slate-600"><?php echo esc_html($t($deposit['intro'])); ?></p>
            <div class="mt-7 overflow-hidden rounded border border-line bg-white shadow-card">
                <table class="w-full text-start text-sm md:text-base">
                    <thead class="bg-navy text-white">
                        <tr>
                            <?php foreach ($deposit['columns'] as $column) : ?><th class="px-3 py-3 text-start text-xs font-black uppercase tracking-[0.06em] md:px-5"><?php echo esc_html($t($column)); ?></th><?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deposit['rows'] as [$order, $pay, $worth]) : ?>
                            <tr class="border-t border-line">
                                <td class="px-3 py-4 font-bold text-ink md:px-5"><?php echo esc_html($t($order)); ?></td>
                                <td class="px-3 py-4 font-black tabular-nums text-ink md:px-5"><?php echo esc_html($pay); ?></td>
                                <td class="px-3 py-4 font-black tabular-nums text-blue-700 md:px-5"><?php echo esc_html($worth); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <ul class="grid content-start gap-3 lg:pt-10">
            <?php foreach ($deposit['points'] as [$point_title, $point_text]) : ?>
                <li class="rounded border border-line bg-white p-4 md:p-5">
                    <h3 class="text-base font-black leading-snug text-ink"><?php echo esc_html($t($point_title)); ?></h3>
                    <p class="mt-1.5 text-sm font-normal leading-6 text-slate-600"><?php echo esc_html($t($point_text)); ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<section id="offer-terms" data-offer-live class="scroll-mt-24 py-12 md:py-16" aria-labelledby="offer-terms-title">
    <div class="page-shell grid gap-6 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)] lg:gap-14">
        <div>
            <p class="section-label mb-3"><?php echo esc_html($t($terms['label'])); ?></p>
            <h2 id="offer-terms-title" class="max-w-[18ch] text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em] text-ink"><?php echo esc_html($t($terms['title'])); ?></h2>
        </div>
        <ol class="grid gap-0 border-t border-line">
            <?php foreach ($terms['items'] as $index => $item) : ?>
                <li class="grid grid-cols-[2.25rem_minmax(0,1fr)] gap-3 border-b border-line py-4">
                    <span class="text-sm font-black tabular-nums text-blue-700"><?php echo esc_html(sprintf('%02d', $index + 1)); ?></span>
                    <span class="text-base font-normal leading-7 text-ink"><?php echo esc_html($t($item)); ?></span>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>

<section id="offer-claim" class="scroll-mt-24 bg-navy py-12 text-white md:py-16" aria-labelledby="offer-claim-title">
    <div class="page-shell flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between lg:gap-14">
        <div class="max-w-2xl">
            <p class="text-xs font-black uppercase tracking-[0.12em] text-gold"><?php echo esc_html($t($claim['label'])); ?></p>
            <h2 id="offer-claim-title" class="mt-3 text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em]"><?php echo esc_html($t($claim['title'])); ?></h2>
            <p class="mt-4 text-base font-normal leading-7 text-white/80"><?php echo esc_html($t($claim['text'])); ?></p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row lg:shrink-0">
            <?php echo $quote_button($t($claim['button']), '页尾', $primary); ?>
            <a class="<?php echo esc_attr($secondary); ?>" href="<?php echo esc_url($whatsapp); ?>" target="_blank" rel="noopener"><?php echo esc_html($t($claim['whatsapp'])); ?></a>
            <a class="inline-flex h-12 items-center justify-center gap-2 rounded-sm border border-white/30 px-6 text-sm font-black text-white transition hover:border-white" href="<?php echo esc_url(boblift_product_archive_url()); ?>"><?php echo esc_html($t($claim['browse'])); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4 rtl:rotate-180'); ?></a>
        </div>
    </div>
</section>
<?php endif; ?>
<?php
get_footer();
