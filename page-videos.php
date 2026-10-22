<?php
/**
 * Template Name: BOB-LIFT Video Center
 */
get_header();

$ui = boblift_site_ui('videoCenter');
$groups = [
    'truck' => (string) ($ui['truckLabel'] ?? 'Truck mounted cranes'),
    'marine' => (string) ($ui['marineLabel'] ?? 'Marine cranes'),
    'test' => (string) ($ui['testLabel'] ?? 'Tests and quality'),
    'factory' => (string) ($ui['factoryLabel'] ?? 'Factory video'),
    'service' => (string) ($ui['serviceLabel'] ?? boblift_localized_static_text('Service and operation')),
];
// Aerial loops join the factory tab; the tab only lists groups that have a playable video.
$videos = [];
foreach (boblift_videos_in_group('truck', 'marine', 'test', 'factory', 'service', 'aerial') as $video) {
    $video['tab'] = $video['group'] === 'aerial' ? 'factory' : $video['group'];
    $videos[] = $video;
}
$counts = array_count_values(array_column($videos, 'tab'));
?>
<div class="page-shell pt-5 md:pt-8">
    <?php
    boblift_page_hero([
        'label' => (string) ($ui['heroLabel'] ?? 'Video Center'),
        'title' => (string) ($ui['heroTitle'] ?? ''),
        'text' => (string) ($ui['heroText'] ?? ''),
        'meta' => $videos !== [] ? count($videos) . ' ' . (string) ($ui['countSuffix'] ?? 'videos') : '',
        'image' => boblift_image_src('images/factory/hall-knuckle-row.webp'),
        'image_alt' => (string) ($ui['heroImageAlt'] ?? ''),
        'image_position' => 'center 55%',
    ]);
    ?>
    <div class="mt-6 md:mt-8"><?php boblift_news_hub_nav('videos'); ?></div>
</div>

<section class="py-10 md:py-14" data-video-filter-root aria-label="<?php echo esc_attr((string) ($ui['heroLabel'] ?? 'Video Center')); ?>">
    <div class="page-shell">
        <?php if ($videos !== []) : ?>
            <div class="-mx-4 mb-6 flex gap-2 overflow-x-auto px-4 pb-1 [scrollbar-width:none] md:mx-0 md:mb-8 md:flex-wrap md:px-0 [&::-webkit-scrollbar]:hidden" role="group">
                <button class="boblift-video-tab" type="button" data-video-filter="all" aria-pressed="true"><?php echo esc_html((string) ($ui['allLabel'] ?? 'All videos')); ?> <span class="tabular-nums opacity-60"><?php echo esc_html((string) count($videos)); ?></span></button>
                <?php foreach ($groups as $key => $label) : if (empty($counts[$key])) continue; ?>
                    <button class="boblift-video-tab" type="button" data-video-filter="<?php echo esc_attr($key); ?>" aria-pressed="false"><?php echo esc_html($label); ?> <span class="tabular-nums opacity-60"><?php echo esc_html((string) $counts[$key]); ?></span></button>
                <?php endforeach; ?>
            </div>
            <?php // Two to a row on phones too: one per row made the full library about 17,000px long. ?>
            <ul class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3 lg:gap-5 xl:grid-cols-4">
                <?php foreach ($videos as $video) : ?>
                    <li data-video-group="<?php echo esc_attr($video['tab']); ?>"><?php boblift_render_video_card($video, 'h-full'); ?></li>
                <?php endforeach; ?>
            </ul>
            <?php boblift_render_video_player(); ?>
        <?php else : ?>
            <p class="rounded border border-line bg-mist p-8 text-center text-base font-bold text-slate-600"><?php echo esc_html((string) ($ui['emptyText'] ?? 'Videos are being added.')); ?></p>
        <?php endif; ?>
    </div>
</section>

<?php get_footer(); ?>
