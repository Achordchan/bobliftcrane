<?php
/**
 * Template Name: BOB-LIFT Service & Operation
 */
get_header();

$ui = boblift_site_ui('service');
$contact = function_exists('boblift_contact_settings') ? boblift_contact_settings() : [];
$after_sales = boblift_video_by_key('service-after-sales');
$guides = [];
foreach ((array) ($ui['guides'] ?? []) as $guide) {
    $video = is_array($guide) ? boblift_video_by_key((string) ($guide['key'] ?? '')) : null;
    if ($video) {
        $guide['video'] = $video;
        $guide['points'] = array_values(array_filter((array) ($guide['points'] ?? []), static fn($point): bool => is_string($point) && trim($point) !== ''));
        $guides[] = $guide;
    }
}
$channels = array_values(array_filter([
    ['icon' => 'whatsapp', 'label' => (string) ($ui['whatsappLabel'] ?? 'WhatsApp'), 'value' => (string) ($contact['whatsapp'] ?? ''), 'href' => (string) ($contact['whatsappHref'] ?? ''), 'external' => true],
    ['icon' => 'message-circle', 'label' => (string) ($ui['wechatLabel'] ?? 'WeChat'), 'value' => (string) ($contact['wechatId'] ?? ''), 'href' => '', 'external' => false],
    ['icon' => 'mail', 'label' => (string) ($ui['emailLabel'] ?? 'Email'), 'value' => (string) ($contact['email'] ?? ''), 'href' => !empty($contact['email']) ? 'mailto:' . $contact['email'] : '', 'external' => false],
    ['icon' => 'phone', 'label' => (string) ($ui['phoneLabel'] ?? 'Phone'), 'value' => (string) ($contact['phone'] ?? ''), 'href' => (string) ($contact['phoneHref'] ?? ''), 'external' => false],
], static fn(array $channel): bool => trim($channel['value']) !== ''));
?>
<div class="page-shell pt-5 md:pt-8">
    <?php
    boblift_page_hero([
        'label' => (string) ($ui['heroLabel'] ?? 'Service & Operation'),
        'title' => (string) ($ui['heroTitle'] ?? ''),
        'text' => (string) ($ui['heroText'] ?? ''),
        'image' => boblift_image_src('images/factory/hall-finished-row.webp'),
        'image_alt' => (string) ($ui['heroImageAlt'] ?? ''),
        'image_position' => 'center 55%',
    ]);
    ?>
</div>

<?php if ($after_sales) : ?>
<section class="py-12 md:py-16" aria-labelledby="service-after-title">
    <div class="page-shell">
    <div class="grid items-center gap-6 rounded border border-line bg-white p-4 shadow-card md:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)] md:p-6 lg:gap-10">
        <div class="md:order-2">
            <p class="section-label mb-3"><?php echo esc_html((string) ($ui['afterLabel'] ?? 'After-sales')); ?></p>
            <h2 id="service-after-title" class="text-[clamp(1.5rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em] text-ink"><?php echo esc_html((string) ($ui['afterTitle'] ?? '')); ?></h2>
            <p class="mt-3 max-w-lg text-sm font-normal leading-7 text-slate-600 md:text-base"><?php echo esc_html((string) ($ui['afterText'] ?? '')); ?></p>
        </div>
        <div class="md:order-1"><?php boblift_render_video_card($after_sales); ?></div>
    </div>
    </div>
    <?php boblift_render_video_player(); ?>
</section>
<?php endif; ?>

<?php if ($guides !== []) : ?>
<section class="border-t border-line bg-mist py-12 md:py-16" aria-labelledby="service-guide-title">
    <div class="page-shell">
        <p class="section-label mb-3"><?php echo esc_html((string) ($ui['guideLabel'] ?? 'Operation guide')); ?></p>
        <h2 id="service-guide-title" class="max-w-[26ch] text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em] text-ink"><?php echo esc_html((string) ($ui['guideTitle'] ?? '')); ?></h2>
        <p class="mt-3 max-w-2xl text-sm font-normal leading-7 text-slate-600 md:text-base"><?php echo esc_html((string) ($ui['guideText'] ?? '')); ?></p>
        <div class="mt-8 grid gap-4 md:grid-cols-2 lg:gap-5">
            <?php foreach ($guides as $guide) : ?>
                <article class="flex flex-col gap-5 rounded border border-line bg-white p-4 shadow-card md:p-5">
                    <?php boblift_render_video_card($guide['video'], 'shadow-none'); ?>
                    <div class="px-1 pb-1">
                        <h3 class="text-lg font-black text-ink md:text-xl"><?php echo esc_html((string) ($guide['title'] ?? '')); ?></h3>
                        <?php if ($guide['points'] !== []) : ?>
                            <ul class="mt-3 grid gap-2">
                                <?php foreach ($guide['points'] as $point) : ?>
                                    <li class="flex items-start gap-2.5 text-sm font-normal leading-6 text-slate-600"><span class="mt-0.5 shrink-0 text-blue-700"><?php echo boblift_icon('check', 'h-4 w-4', '2.5'); ?></span><span><?php echo esc_html($point); ?></span></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php boblift_render_video_player(); ?>
    </div>
</section>
<?php endif; ?>

<?php if ($channels !== []) : ?>
<section class="page-shell py-12 md:py-16" aria-labelledby="service-support-title">
    <div class="grid gap-8 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,2fr)] lg:gap-10">
        <div class="lg:sticky lg:top-[calc(var(--boblift-header-h)+1.5rem)] lg:self-start">
            <p class="section-label mb-3"><?php echo esc_html((string) ($ui['supportLabel'] ?? '')); ?></p>
            <h2 id="service-support-title" class="text-[clamp(1.5rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em] text-ink"><?php echo esc_html((string) ($ui['supportTitle'] ?? '')); ?></h2>
            <p class="mt-3 max-w-md text-sm font-normal leading-7 text-slate-600 md:text-base"><?php echo esc_html((string) ($ui['supportText'] ?? '')); ?></p>
        </div>
        <ul class="grid gap-3 sm:grid-cols-2 md:gap-4">
            <?php foreach ($channels as $channel) : ?>
                <li class="min-w-0">
                    <?php $tag = $channel['href'] !== '' ? 'a' : 'div'; ?>
                    <<?php echo $tag; ?> class="flex h-full items-center gap-4 rounded border border-line bg-white p-5 shadow-card <?php echo $channel['href'] !== '' ? 'transition duration-300 hover:-translate-y-0.5 hover:border-blue-300' : ''; ?>"<?php if ($channel['href'] !== '') : ?> href="<?php echo esc_url($channel['href']); ?>"<?php echo $channel['external'] ? ' target="_blank" rel="noreferrer"' : ''; endif; ?>>
                        <span class="grid size-11 shrink-0 place-items-center rounded-sm bg-blue-50 text-blue-700"><?php echo boblift_icon($channel['icon'], 'h-5 w-5'); ?></span>
                        <span class="min-w-0"><span class="block text-xs font-black uppercase tracking-[0.14em] text-slate-400"><?php echo esc_html($channel['label']); ?></span><span class="mt-1 block break-words text-base font-black text-ink"><?php echo esc_html($channel['value']); ?></span></span>
                    </<?php echo $tag; ?>>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
<?php endif; ?>

<section class="page-shell pb-12 md:pb-16" aria-labelledby="service-cta-title">
    <div class="flex flex-col gap-6 rounded bg-navy p-6 text-white sm:p-10 lg:flex-row lg:items-center lg:justify-between xl:p-12">
        <div class="max-w-2xl">
            <h2 id="service-cta-title" class="text-[clamp(1.5rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em]"><?php echo esc_html((string) ($ui['ctaTitle'] ?? '')); ?></h2>
            <p class="mt-3 text-sm font-normal leading-7 text-white/75 md:text-base"><?php echo esc_html((string) ($ui['ctaText'] ?? '')); ?></p>
        </div>
        <div class="flex shrink-0 flex-wrap gap-3">
            <button class="inline-flex h-12 items-center justify-center gap-2 rounded-sm bg-white px-6 text-sm font-black text-navy transition hover:-translate-y-0.5 hover:bg-blue-50" type="button" data-inquiry-modal-open data-inquiry-source="售后操作页"><?php echo esc_html((string) ($ui['ctaButton'] ?? 'Send an Inquiry')); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></button>
            <a class="inline-flex h-12 items-center justify-center rounded-sm border border-white/30 px-6 text-sm font-black text-white transition hover:border-white" href="<?php echo esc_url(boblift_url('/contact.html')); ?>"><?php echo esc_html((string) ($ui['ctaLink'] ?? 'Contact us')); ?></a>
        </div>
    </div>
</section>

<?php get_footer(); ?>
