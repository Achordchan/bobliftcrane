<?php
/**
 * Template Name: BOB-LIFT Quality & Testing
 */
get_header();

$ui = boblift_site_ui('qualityTesting');
$steps = array_values(array_filter(is_array($ui['steps'] ?? null) ? $ui['steps'] : [], static fn($step): bool => is_array($step) && trim((string) ($step['title'] ?? '')) !== ''));
$featured = array_values(array_filter([boblift_video_by_key('test-explained'), boblift_video_by_key('quality-control')]));
$batch = boblift_video_by_key('test-batch-3.2z');
$skip = ['test-explained', 'quality-control', 'test-batch-3.2z'];
$tests = array_values(array_filter(boblift_videos_in_group('test'), static fn(array $video): bool => !in_array($video['key'], $skip, true)));
$has_video = $featured !== [] || $batch || $tests !== [];
?>
<div class="page-shell pt-5 md:pt-8">
    <?php
    boblift_page_hero([
        'label' => (string) ($ui['heroLabel'] ?? 'Quality & Testing'),
        'title' => (string) ($ui['heroTitle'] ?? ''),
        'text' => (string) ($ui['heroText'] ?? ''),
        'image' => boblift_image_src('images/factory/hall-crane-test-bay.webp'),
        'image_alt' => (string) ($ui['heroImageAlt'] ?? ''),
        'image_position' => 'center 45%',
    ]);
    ?>
</div>

<?php if ($steps !== []) : ?>
<section class="py-12 md:py-16" aria-labelledby="quality-steps-title">
    <div class="page-shell">
        <p class="section-label mb-3"><?php echo esc_html((string) ($ui['stepsLabel'] ?? 'Before delivery')); ?></p>
        <h2 id="quality-steps-title" class="max-w-[26ch] text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em] text-ink"><?php echo esc_html((string) ($ui['stepsTitle'] ?? '')); ?></h2>
        <ol class="mt-8 grid gap-3 md:grid-cols-3 md:gap-5">
            <?php foreach ($steps as $index => $step) : ?>
                <li class="rounded border border-line bg-white p-5 shadow-card md:p-6">
                    <span class="text-xs font-black tabular-nums tracking-[0.14em] text-blue-700"><?php echo esc_html(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></span>
                    <h3 class="mt-2 text-lg font-black text-ink md:text-xl"><?php echo esc_html((string) $step['title']); ?></h3>
                    <p class="mt-2 text-sm font-semibold leading-6 text-slate-600"><?php echo esc_html((string) ($step['text'] ?? '')); ?></p>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>
<?php endif; ?>

<?php if ($has_video) : ?>
<section class="border-t border-line bg-mist py-12 md:py-16" aria-labelledby="quality-videos-title">
    <div class="page-shell">
        <?php if ($featured !== []) : ?>
            <p class="section-label mb-4"><?php echo esc_html((string) ($ui['featuredLabel'] ?? 'In the workshop')); ?></p>
            <div class="mb-10 grid gap-4 md:grid-cols-2 lg:gap-5 md:mb-12">
                <?php foreach ($featured as $video) { boblift_render_video_card($video); } ?>
            </div>
        <?php endif; ?>

        <?php if ($batch) : ?>
            <div class="mb-10 grid items-center gap-6 rounded border border-line bg-white p-4 shadow-card md:mb-12 md:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] md:p-6 lg:gap-10">
                <?php boblift_render_video_card($batch); ?>
                <div>
                    <h2 class="text-[clamp(1.4rem,2vw,2rem)] font-black leading-tight tracking-[-0.01em] text-ink"><?php echo esc_html((string) ($ui['batchTitle'] ?? '')); ?></h2>
                    <p class="mt-3 text-sm font-semibold leading-7 text-slate-600 md:text-base"><?php echo esc_html((string) ($ui['batchText'] ?? '')); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($tests !== []) : ?>
            <p class="section-label mb-3"><?php echo esc_html((string) ($ui['testsLabel'] ?? 'Test videos')); ?></p>
            <h2 id="quality-videos-title" class="mb-6 text-[clamp(1.5rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em] text-ink"><?php echo esc_html((string) ($ui['testsTitle'] ?? '')); ?></h2>
            <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 lg:gap-5">
                <?php foreach ($tests as $video) : ?><li><?php boblift_render_video_card($video, 'h-full'); ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php boblift_render_video_player(); ?>
    </div>
</section>
<?php endif; ?>

<section class="page-shell py-12 md:py-16" aria-labelledby="quality-cta-title">
    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.6fr)] lg:gap-5">
        <div class="flex flex-col justify-between gap-5 rounded border border-line bg-white p-6 shadow-card sm:p-8">
            <div>
                <h2 class="text-xl font-black text-ink md:text-2xl"><?php echo esc_html((string) ($ui['certificatesTitle'] ?? 'Certificates')); ?></h2>
                <p class="mt-2 text-sm font-semibold leading-6 text-slate-600"><?php echo esc_html((string) ($ui['certificatesText'] ?? '')); ?></p>
            </div>
            <a class="inline-flex w-fit items-center gap-1.5 text-sm font-black text-blue-700 hover:text-blue-900" href="<?php echo esc_url(boblift_url('/about.html') . '#about-certifications'); ?>"><?php echo esc_html((string) ($ui['certificatesLink'] ?? 'View certificates')); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
        </div>
        <div class="flex flex-col gap-6 rounded bg-navy p-6 text-white sm:p-8 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-xl">
                <h2 id="quality-cta-title" class="text-[clamp(1.5rem,2.2vw,2.25rem)] font-black leading-[1.08] tracking-[-0.015em]"><?php echo esc_html((string) ($ui['ctaTitle'] ?? '')); ?></h2>
                <p class="mt-3 text-sm font-semibold leading-7 text-white/75 md:text-base"><?php echo esc_html((string) ($ui['ctaText'] ?? '')); ?></p>
            </div>
            <div class="flex shrink-0 flex-wrap gap-3">
                <button class="inline-flex h-12 items-center justify-center gap-2 rounded-sm bg-white px-6 text-sm font-black text-navy transition hover:-translate-y-0.5 hover:bg-blue-50" type="button" data-inquiry-modal-open data-inquiry-source="质量测试页"><?php echo esc_html((string) ($ui['ctaButton'] ?? 'Ask for a Test Video')); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></button>
                <a class="inline-flex h-12 items-center justify-center rounded-sm border border-white/30 px-6 text-sm font-black text-white transition hover:border-white" href="<?php echo esc_url(boblift_url('/global-customer-visits.html')); ?>"><?php echo esc_html((string) ($ui['visitLabel'] ?? 'Plan a factory visit')); ?></a>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>
