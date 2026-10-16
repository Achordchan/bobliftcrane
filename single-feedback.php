<?php
the_post();
if (post_password_required(get_post())) {
    // Show the standard WordPress password form instead of the entry.
    get_header();
    echo '<section class="page-shell py-16 md:py-24"><div class="mx-auto max-w-xl">' . get_the_password_form() . '</div></section>';
    get_footer();
    exit;
}
$feedback = boblift_feedback_item(get_post());
if (!$feedback) {
    wp_safe_redirect(add_query_arg('tab', 'customer_feedback', boblift_case_archive_url()));
    exit;
}
get_header();
$ui = boblift_site_ui('caseDetail');
$cases_ui = boblift_site_ui('casesPage');
$feedback_label = $cases_ui['customerFeedbackLabel'] ?? 'Customer Feedback';
$feedback_list_url = add_query_arg('tab', 'customer_feedback', boblift_case_archive_url());
$media = $feedback['media'];
$total = count($media);
$caption = implode(' · ', array_filter([$feedback['model'], $feedback['country']]));
$counts = boblift_feedback_counts_text($feedback);
$first = $media[0];
$pad = static fn(int $n): string => str_pad((string) $n, 2, '0', STR_PAD_LEFT);

// More feedback: same model first, then same country, then the rest in list order.
$others = array_values(array_filter(boblift_feedback_items(), static fn(array $item): bool => $item['id'] !== $feedback['id']));
usort($others, static function (array $a, array $b) use ($feedback): int {
    $rank = static fn(array $item): int => $item['model'] === $feedback['model'] ? 0 : ($item['country'] === $feedback['country'] ? 1 : 2);
    return $rank($a) <=> $rank($b);
});
$others = array_slice($others, 0, 4);
?>

<article class="boblift-feedback-detail page-shell pb-16 pt-6 md:pb-24 md:pt-10">
    <nav class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs font-bold text-slate-500" aria-label="<?php echo esc_attr($ui['breadcrumbLabel'] ?? 'Breadcrumb'); ?>">
        <a class="transition hover:text-blue-700" href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html($ui['home'] ?? 'Home'); ?></a>
        <span aria-hidden="true">/</span>
        <a class="transition hover:text-blue-700" href="<?php echo esc_url(boblift_case_archive_url()); ?>"><?php echo esc_html($ui['cases'] ?? 'Cases'); ?></a>
        <span aria-hidden="true">/</span>
        <a class="transition hover:text-blue-700" href="<?php echo esc_url($feedback_list_url); ?>"><?php echo esc_html($feedback_label); ?></a>
    </nav>

    <header class="mt-5 flex flex-col gap-5 border-b border-line pb-5 md:mt-6 md:flex-row md:items-end md:justify-between md:pb-6">
        <div class="min-w-0">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700"><?php echo esc_html($feedback_label); ?></p>
            <h1 class="mt-3 text-4xl font-black leading-none tracking-[-0.02em] text-ink md:text-[3.25rem]"><?php echo esc_html($feedback['model']); ?></h1>
            <?php if ($feedback['note'] !== '') : ?><p class="mt-3 text-base font-semibold text-slate-600 md:text-lg"><?php echo esc_html($feedback['note']); ?></p><?php endif; ?>
        </div>
        <dl class="grid shrink-0 grid-cols-2 gap-x-10 gap-y-1 text-sm md:text-right">
            <?php if ($feedback['country'] !== '') : ?>
                <div>
                    <dt class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-400"><?php echo esc_html($ui['countryLabel'] ?? 'Country'); ?></dt>
                    <dd class="mt-1 flex items-center gap-1.5 font-black text-ink md:justify-end"><?php echo boblift_icon('map-pin', 'h-4 w-4 text-gold'); ?><?php echo esc_html($feedback['country']); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($counts !== '') : ?>
                <div>
                    <dt class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-400"><?php echo esc_html($ui['mediaLabel'] ?? 'Media'); ?></dt>
                    <dd class="mt-1 font-black text-ink"><?php echo esc_html($counts); ?></dd>
                </div>
            <?php endif; ?>
        </dl>
    </header>

    <section class="mt-5 md:mt-6" data-feedback-gallery data-feedback-media="<?php echo esc_attr(wp_json_encode($media, JSON_UNESCAPED_SLASHES)); ?>" data-feedback-title="<?php echo esc_attr($caption); ?>" aria-label="<?php echo esc_attr($caption); ?>">
        <div class="relative aspect-[4/3] overflow-hidden rounded bg-[#EDF1F5] md:aspect-auto md:h-[clamp(380px,56vh,720px)]" data-feedback-stage>
            <div class="absolute inset-0" data-feedback-stage-media>
                <?php if ($first['type'] === 'video') : ?>
                    <video class="h-full w-full object-contain" src="<?php echo esc_url($first['src']); ?>" poster="<?php echo esc_url($first['poster']); ?>" controls muted playsinline preload="metadata"></video>
                <?php else : ?>
                    <img class="h-full w-full object-contain" src="<?php echo esc_url($first['src']); ?>" alt="<?php echo esc_attr($caption); ?>" loading="eager" fetchpriority="high" decoding="async">
                <?php endif; ?>
            </div>
            <?php if ($total > 1) : ?>
                <button class="boblift-feedback-arrow left-3 md:left-5" type="button" data-feedback-prev aria-label="<?php echo esc_attr($cases_ui['previousLabel'] ?? 'Previous'); ?>"><?php echo boblift_icon('chevron-left', 'h-5 w-5'); ?></button>
                <button class="boblift-feedback-arrow right-3 md:right-5" type="button" data-feedback-next aria-label="<?php echo esc_attr($cases_ui['nextLabel'] ?? 'Next'); ?>"><?php echo boblift_icon('chevron-right', 'h-5 w-5'); ?></button>
                <p class="pointer-events-none absolute left-3 top-3 z-10 rounded-sm bg-[#071f42]/80 px-2.5 py-1 text-xs font-black tracking-[0.08em] text-white [font-variant-numeric:tabular-nums] md:left-5 md:top-5" aria-live="polite"><span data-feedback-index>01</span> / <?php echo esc_html($pad($total)); ?></p>
            <?php endif; ?>
        </div>

        <?php if ($total > 1) : ?>
            <div class="boblift-feedback-thumbs mt-3 flex gap-2 overflow-x-auto pb-1" data-feedback-thumbs>
                <?php foreach ($media as $index => $item) : ?>
                    <button class="boblift-feedback-thumb<?php echo $index === 0 ? ' is-active' : ''; ?>" type="button" data-feedback-thumb="<?php echo esc_attr((string) $index); ?>" aria-pressed="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr($pad($index + 1) . ' / ' . $pad($total)); ?>">
                        <?php if ($item['thumb'] !== '') : ?><img class="h-full w-full object-cover" src="<?php echo esc_url($item['thumb']); ?>" alt="" loading="lazy" decoding="async"><?php endif; ?>
                        <?php if ($item['type'] === 'video') : ?><span class="absolute inset-0 grid place-items-center bg-[#071f42]/35 text-white"><?php echo boblift_icon('play', 'h-4 w-4'); ?></span><?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($feedback['body'] !== '') : ?>
        <div class="boblift-story-prose mx-auto mt-12 max-w-[760px] md:mt-16">
            <?php echo wp_kses_post(wpautop(esc_html($feedback['body']))); ?>
        </div>
    <?php endif; ?>

    <div class="mt-12 flex flex-col gap-4 rounded border border-line bg-mist p-5 sm:flex-row sm:items-center sm:justify-between md:mt-16 md:p-6">
        <p class="text-lg font-black leading-snug text-ink"><?php echo esc_html(str_replace('{model}', $feedback['model'], $ui['askModelTemplate'] ?? 'Interested in the {model}?')); ?></p>
        <div class="flex flex-wrap gap-3">
            <a class="btn-primary rounded-sm" href="<?php echo esc_url(boblift_url('/contact.html?product=' . rawurlencode($feedback['model']) . '#inquiry')); ?>" data-inquiry-modal-open data-inquiry-source="客户反馈详情" data-inquiry-product="<?php echo esc_attr($feedback['model']); ?>"><?php echo esc_html($ui['getQuoteLabel'] ?? 'Get a Quote'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4 text-gold'); ?></a>
        </div>
    </div>

    <?php if ($others) : ?>
        <section class="mt-14 md:mt-20">
            <div class="mb-6 flex items-end justify-between gap-4">
                <h2 class="text-2xl font-black tracking-[-0.015em] text-ink md:text-3xl"><?php echo esc_html($ui['moreFeedbackLabel'] ?? 'More customer feedback'); ?></h2>
                <a class="inline-flex shrink-0 items-center gap-1.5 text-sm font-black text-blue-700 transition hover:text-ink" href="<?php echo esc_url($feedback_list_url); ?>"><?php echo esc_html($ui['viewAllLabel'] ?? 'View all'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
            </div>
            <div class="grid grid-cols-2 gap-3 md:gap-5 lg:grid-cols-4">
                <?php foreach ($others as $other) : ?>
                    <a class="group block overflow-hidden rounded border border-line bg-white transition hover:border-blue-300" href="<?php echo esc_url($other['url']); ?>">
                        <span class="relative block aspect-[4/3] overflow-hidden bg-[#EDF1F5]">
                            <?php if ($other['cover'] !== '') : ?><img class="h-full w-full object-cover" src="<?php echo esc_url($other['cover']); ?>" alt="<?php echo esc_attr($other['model'] . ' · ' . $other['country']); ?>" loading="lazy" decoding="async"><?php endif; ?>
                        </span>
                        <span class="block p-3 md:p-4">
                            <span class="block text-[11px] font-black uppercase tracking-[0.14em] text-slate-500"><?php echo esc_html($other['country']); ?></span>
                            <span class="mt-1 block text-base font-black text-ink md:text-lg"><?php echo esc_html($other['model']); ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</article>

<?php get_footer(); ?>
