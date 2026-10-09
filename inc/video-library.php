<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Site video library: product series videos, load tests, quality control and factory footage.
 *
 * Each source has a local MP4 (assets/videos/library/, git-ignored, built by tools/video-library/build.py)
 * and a YouTube id. A video shows only when its YouTube id is set or its local file exists, so a server
 * without the MP4s renders nothing until the ids are filled in at launch.
 *
 * Series videos (truck/marine + boom + capacity) are shared by every product of that series.
 */
function boblift_video_library(): array
{
    static $videos = null;
    if ($videos !== null) {
        return $videos;
    }
    $series = static function (string $use, string $boom, string $capacity, array $langs, int $duration) : array {
        $key = $use . '-' . $boom . '-' . $capacity;
        $sources = [];
        foreach ($langs as $lang) {
            $sources[$lang] = ['file' => 'videos/library/' . $key . '-' . $lang . '.mp4', 'youtube' => ''];
        }
        return ['key' => $key, 'group' => $use, 'use' => $use, 'boom' => $boom . '-boom', 'capacity' => (float) $capacity, 'sources' => $sources, 'duration' => $duration];
    };
    $clip = static function (string $key, string $group, string $title, array $langs, int $duration, string $text = '') : array {
        $sources = [];
        foreach ($langs as $lang) {
            $sources[$lang] = ['file' => 'videos/library/' . $key . '-' . $lang . '.mp4', 'youtube' => ''];
        }
        return ['key' => $key, 'group' => $group, 'title' => $title, 'text' => $text, 'sources' => $sources, 'duration' => $duration];
    };
    $both = ['en', 'zh'];
    $any = ['any'];
    $videos = [
        // SQ1ZA3+1 is rated 1.6 t, so the 1 t file matches products by that capacity.
        array_replace($series('truck', 'knuckle', '1', $any, 0), ['capacity' => 1.6]),
        $series('truck', 'knuckle', '2', $both, 0),
        $series('truck', 'knuckle', '3.2', $both, 0),
        $series('truck', 'knuckle', '4', $both, 0),
        $series('truck', 'knuckle', '5', $both, 0),
        $series('truck', 'knuckle', '6.3', $both, 0),
        $series('truck', 'knuckle', '8', $both, 0),
        $series('truck', 'knuckle', '10', $both, 0),
        $series('truck', 'knuckle', '12', $both, 0),
        $series('truck', 'knuckle', '20', $both, 0),
        $series('truck', 'knuckle', '25', $any, 0),
        $series('truck', 'telescopic', '3.2', $any, 0),
        $series('truck', 'telescopic', '4', $any, 0),
        $series('truck', 'telescopic', '5', $both, 0),
        $series('truck', 'telescopic', '10', $both, 0),
        $series('truck', 'telescopic', '12', $both, 0),
        $series('marine', 'knuckle', '3.2', $both, 0),
        $series('marine', 'knuckle', '4', $both, 0),
        $series('marine', 'knuckle', '5', $both, 0),
        $series('marine', 'knuckle', '10', $any, 0),
        $series('marine', 'knuckle', '12', $both, 0),
        $series('marine', 'knuckle', '16', ['en'], 0), // The zh cut has English subtitles, so every site plays the en file.
        $series('marine', 'knuckle', '20', $both, 0),
        $series('marine', 'telescopic', '6.3', $any, 0),
        $series('marine', 'telescopic', '12', $both, 0),
        $series('marine', 'telescopic', '20', $both, 0),
        $clip('test-load-5z', 'test', 'Load test · 5 t knuckle boom crane', $any, 0),
        $clip('test-load-10z', 'test', 'Load test · 10 t knuckle boom crane', $any, 0),
        $clip('test-load-6.3z', 'test', 'Load test · 6.3 t knuckle boom crane', $any, 0),
        $clip('test-basket-6.3z', 'test', 'Basket test · 6.3 t knuckle boom crane', $any, 0),
        $clip('test-load-marine-6.3s', 'test', 'Load test on the test rig · 6.3 t telescopic marine crane', $any, 0),
        $clip('test-load-marine-2z', 'test', 'Function test · 2 t knuckle boom marine crane', $any, 0),
        $clip('test-load-sq8za6', 'test', 'Load test · SQ8ZA6 knuckle boom truck crane', $any, 0),
        $clip('test-batch-3.2z', 'test', 'One order: twenty 3.2 t knuckle boom cranes ready to ship', $any, 0),
        $clip('test-explained', 'test', 'How we test a crane before delivery', $both, 0),
        $clip('quality-control', 'test', 'Quality control in the workshop', $both, 0),
        $clip('factory-intro', 'factory', 'Inside the BOB-LIFT factory', $both, 0),
        $clip('service-after-sales', 'service', 'After-sales support', $both, 0),
        $clip('service-before-operation', 'service', 'Checks before operating the crane', $both, 0),
        $clip('service-during-operation', 'service', 'Operating the crane safely', $both, 0),
        $clip('factory-aerial', 'aerial', 'Factory aerial view', $any, 0),
        $clip('factory-hall-door', 'aerial', 'Into the assembly hall', $any, 0),
        $clip('factory-hall-cranes', 'aerial', 'Finished cranes in the hall', $any, 0),
    ];
    // Width, height, duration and the phone cut of the aerial loops come from the encoder's manifest.
    $meta = [];
    $manifest = get_template_directory() . '/inc/video-library.json';
    foreach ((is_readable($manifest) ? (array) json_decode((string) file_get_contents($manifest), true) : []) as $row) {
        if (is_array($row) && isset($row['key'])) {
            $meta[(string) $row['key']] = $row;
        }
    }
    foreach ($videos as &$video) {
        $video['poster'] = 'images/video-posters/' . $video['key'] . '.webp';
        $row = $meta[$video['key']] ?? [];
        $video['width'] = (int) ($row['width'] ?? 1920);
        $video['height'] = (int) ($row['height'] ?? 1080);
        $video['duration'] = (int) ($row['duration'] ?? $video['duration']);
        $video['mobile'] = (string) ($row['mobile'] ?? '');
    }
    unset($video);
    return $videos;
}

// Accepts a YouTube watch/short/embed URL or a bare 11-character id.
function boblift_youtube_id(string $value): string
{
    $value = trim($value);
    if (preg_match('/^[A-Za-z0-9_-]{11}$/', $value)) {
        return $value;
    }
    return preg_match('#(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/))([A-Za-z0-9_-]{11})#', $value, $m) ? $m[1] : '';
}

function boblift_video_by_key(string $key): ?array
{
    foreach (boblift_video_library() as $video) {
        if ($video['key'] === $key) {
            return boblift_video_resolve($video);
        }
    }
    return null;
}

// Picks the source for the current language (then English, then any) and reports YouTube or local playback.
function boblift_video_resolve(array $video): ?array
{
    $language = function_exists('boblift_current_language_slug') ? (string) boblift_current_language_slug() : 'en';
    $order = array_values(array_unique([$language, 'en', 'any', 'zh']));
    foreach ($order as $lang) {
        $source = $video['sources'][$lang] ?? null;
        if (!is_array($source)) {
            continue;
        }
        $youtube = trim((string) ($source['youtube'] ?? ''));
        if ($youtube !== '') {
            return $video + ['player' => 'youtube', 'youtube' => $youtube, 'src' => '', 'src_mobile' => '', 'label' => boblift_video_title($video)];
        }
        $file = (string) ($source['file'] ?? '');
        if ($file !== '' && is_readable(get_template_directory() . '/assets/' . $file)) {
            $mobile = (string) ($video['mobile'] ?? '');
            $mobile_src = $mobile !== '' && is_readable(get_template_directory() . '/assets/' . $mobile) ? boblift_asset($mobile) : '';
            return $video + ['player' => 'local', 'youtube' => '', 'src' => boblift_asset($file), 'src_mobile' => $mobile_src, 'label' => boblift_video_title($video)];
        }
    }
    return null;
}

function boblift_video_title(array $video): string
{
    if (isset($video['capacity'])) {
        $template = [
            'truck-knuckle-boom' => '{capacity} t knuckle boom truck mounted crane',
            'truck-telescopic-boom' => '{capacity} t telescopic boom truck mounted crane',
            'marine-knuckle-boom' => '{capacity} t knuckle boom marine crane',
            'marine-telescopic-boom' => '{capacity} t telescopic boom marine crane',
        ][$video['use'] . '-' . $video['boom']] ?? '{capacity} t crane';
        $capacity = rtrim(rtrim(number_format((float) $video['capacity'], 1, '.', ''), '0'), '.');
        return str_replace('{capacity}', $capacity, boblift_localized_static_text($template));
    }
    return boblift_localized_static_text((string) ($video['title'] ?? ''));
}

function boblift_videos_in_group(string ...$groups): array
{
    $items = [];
    foreach (boblift_video_library() as $video) {
        if (in_array($video['group'], $groups, true) && ($resolved = boblift_video_resolve($video))) {
            $items[] = $resolved;
        }
    }
    return $items;
}

// The series video for a truck or marine crane with the same boom type and max lifting capacity.
function boblift_product_series_video(int $product_id): ?array
{
    $english_id = function_exists('boblift_product_english_id') ? boblift_product_english_id($product_id) : $product_id;
    $categories = wp_get_post_terms($english_id, 'product_category', ['fields' => 'slugs']);
    $categories = is_array($categories) ? $categories : [];
    $use = '';
    foreach ($categories as $slug) {
        if (str_ends_with($slug, 'truck-mounted-cranes')) {
            $use = 'truck';
        } elseif (str_ends_with($slug, 'marine-cranes')) {
            $use = 'marine';
        }
    }
    $boom = (string) get_post_meta($english_id, '_boom_type', true);
    $capacity = (float) (boblift_product_filter_values($english_id)['capacity'] ?? 0);
    if ($use === '' || $capacity <= 0) {
        return null;
    }
    foreach (boblift_video_library() as $video) {
        if (($video['use'] ?? '') === $use && ($video['boom'] ?? '') === $boom && abs((float) $video['capacity'] - $capacity) < 0.05) {
            return boblift_video_resolve($video);
        }
    }
    return null;
}

function boblift_video_duration_label(array $video): string
{
    $seconds = (int) ($video['duration'] ?? 0);
    return $seconds > 0 ? sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60) : '';
}

// A poster card that opens the shared video player; attributes carry what the player needs.
function boblift_render_video_card(array $video, string $class = ''): void
{
    $label = (string) $video['label'];
    $duration = boblift_video_duration_label($video);
    $portrait = (int) ($video['height'] ?? 0) > (int) ($video['width'] ?? 0);
    ?>
    <button class="group flex w-full min-w-0 flex-col overflow-hidden rounded border border-line bg-white text-left shadow-card transition duration-300 hover:-translate-y-0.5 hover:border-blue-300 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-blue-100 <?php echo esc_attr($class); ?>" type="button" data-video-open data-video-player="<?php echo esc_attr($video['player']); ?>" data-video-src="<?php echo esc_url($video['src']); ?>" data-video-youtube="<?php echo esc_attr($video['youtube']); ?>" data-video-title="<?php echo esc_attr($label); ?>"<?php echo $portrait ? ' data-video-portrait' : ''; ?>>
        <span class="relative block aspect-video overflow-hidden bg-navy">
            <img class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]" src="<?php echo esc_url(boblift_asset($video['poster'])); ?>" alt="" loading="lazy" decoding="async">
            <span class="absolute inset-0 m-auto grid size-12 place-items-center rounded-sm bg-white/90 text-navy shadow-card transition group-hover:bg-white"><?php echo boblift_icon('play', 'h-5 w-5'); ?></span>
            <?php if ($duration !== '') : ?><span class="absolute bottom-2 right-2 rounded-sm bg-navy/80 px-1.5 py-0.5 text-[11px] font-black tabular-nums text-white"><?php echo esc_html($duration); ?></span><?php endif; ?>
        </span>
        <span class="block px-3.5 py-3 text-sm font-black leading-5 text-ink sm:px-4"><?php echo esc_html($label); ?></span>
    </button>
    <?php
}

// One player dialog per page, shared by every video card. It prints in the footer so no section's
// stacking context can put the floating contact bar above it.
function boblift_render_video_player(): void
{
    static $queued = false;
    if (!$queued) {
        $queued = true;
        add_action('wp_footer', 'boblift_print_video_player', 5);
    }
}

function boblift_print_video_player(): void
{
    $ui = function_exists('boblift_site_ui') ? boblift_site_ui('productDetail') : [];
    ?>
    <div class="fixed inset-0 z-[1000] hidden flex-col bg-[#040f22]" data-video-dialog role="dialog" aria-modal="true" aria-hidden="true">
        <div class="flex shrink-0 items-center justify-between gap-4 px-4 py-3 text-white md:px-6">
            <p class="min-w-0 truncate text-sm font-black" data-video-dialog-title></p>
            <button class="grid size-10 shrink-0 place-items-center rounded-sm bg-white/10 text-white transition hover:bg-white hover:text-[#071f42]" type="button" data-video-close aria-label="<?php echo esc_attr($ui['closeLabel'] ?? 'Close'); ?>"><?php echo boblift_icon('x', 'h-5 w-5'); ?></button>
        </div>
        <div class="flex min-h-0 flex-1 items-center justify-center px-3 pb-4 md:px-10" data-video-stage></div>
    </div>
    <?php
}
