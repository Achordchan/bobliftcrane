<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * A product category's film: one wide card under the category intro and brochure, the player on the left and a title,
 * a short text and up to four points on the right (stacked on phones). Each Polylang language term holds its own copy in
 * the term meta _boblift_category_video, a JSON string written through REST:
 * POST /wp/v2/product_category/<id> with {"meta": {"_boblift_category_video": "{...}"}}.
 *   {"src": "https://…/film.mp4", "youtube": "", "poster": "https://…/poster.jpg", "eyebrow": "", "title": "",
 *    "text": "", "points": [""], "duration": 66, "upload_date": "2026-11-04"}
 * A film needs a poster and either an MP4 (https) or a YouTube id; "" (or anything else) removes the card.
 * Plain text throughout. The page also gets a VideoObject for the film.
 */

/** Keeps only the film's own keys; anything that cannot make a card is stored as ''. */
function boblift_category_video_sanitize($value): string
{
    // update_metadata() has already unslashed the value; unslashing again would break escaped quotes in the JSON.
    $data = json_decode(is_string($value) ? $value : '', true);
    if (!is_array($data)) {
        return '';
    }
    $plain = static fn($text): string => trim(sanitize_text_field((string) $text));
    $https = static function ($url): string {
        $url = trim((string) $url);
        return preg_match('~^https://[^\s"\'<>]+$~i', $url) ? esc_url_raw($url) : '';
    };
    $youtube = $plain($data['youtube'] ?? '');
    $date = $plain($data['upload_date'] ?? '');
    $video = [
        'src' => $https($data['src'] ?? ''),
        'youtube' => preg_match('/^[A-Za-z0-9_-]{6,20}$/', $youtube) ? $youtube : '',
        'poster' => $https($data['poster'] ?? ''),
        'eyebrow' => $plain($data['eyebrow'] ?? ''),
        'title' => $plain($data['title'] ?? ''),
        'text' => $plain($data['text'] ?? ''),
        'points' => [],
        'duration' => max(0, min(36000, (int) ($data['duration'] ?? 0))),
        'upload_date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : '',
    ];
    foreach ((array) ($data['points'] ?? []) as $point) {
        $point = $plain(is_scalar($point) ? $point : '');
        if ($point !== '' && count($video['points']) < 4) {
            $video['points'][] = $point;
        }
    }
    if ($video['poster'] === '' || ($video['src'] === '' && $video['youtube'] === '') || $video['title'] === '') {
        return '';
    }
    return (string) wp_json_encode($video, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function boblift_register_category_video_meta(): void
{
    register_term_meta('product_category', '_boblift_category_video', [
        'type' => 'string',
        'single' => true,
        'default' => '',
        'show_in_rest' => true,
        'sanitize_callback' => 'boblift_category_video_sanitize',
        'auth_callback' => static fn(): bool => current_user_can('manage_categories'),
    ]);
}
add_action('init', 'boblift_register_category_video_meta');

/** The film of a category term (its own language), or [] when it has none. */
function boblift_category_video(WP_Term $term): array
{
    $stored = (string) get_term_meta($term->term_id, '_boblift_category_video', true);
    $video = json_decode(boblift_category_video_sanitize($stored), true);
    return is_array($video) ? $video : [];
}

/** "PT1M6S" for 66 seconds; '' for none. */
function boblift_category_video_iso_duration(int $seconds): string
{
    if ($seconds <= 0) {
        return '';
    }
    $hours = intdiv($seconds, 3600);
    $minutes = intdiv($seconds % 3600, 60);
    $rest = $seconds % 60;
    return 'PT' . ($hours ? $hours . 'H' : '') . ($minutes ? $minutes . 'M' : '') . ($rest || (!$hours && !$minutes) ? $rest . 'S' : '');
}

/** "1:06" for 66 seconds; '' for none. */
function boblift_category_video_clock(int $seconds): string
{
    if ($seconds <= 0) {
        return '';
    }
    $hours = intdiv($seconds, 3600);
    $minutes = intdiv($seconds % 3600, 60);
    $rest = $seconds % 60;
    return $hours ? sprintf('%d:%02d:%02d', $hours, $minutes, $rest) : sprintf('%d:%02d', $minutes, $rest);
}

/** The VideoObject for a category film, or [] when Google's required fields (name, thumbnail, upload date) are missing. */
function boblift_category_video_schema(array $video): array
{
    if (($video['title'] ?? '') === '' || ($video['poster'] ?? '') === '' || ($video['upload_date'] ?? '') === '') {
        return [];
    }
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'VideoObject',
        'name' => (string) $video['title'],
        'description' => (string) (($video['text'] ?? '') !== '' ? $video['text'] : $video['title']),
        'thumbnailUrl' => [(string) $video['poster']],
        'uploadDate' => (string) $video['upload_date'],
    ];
    if (($video['src'] ?? '') !== '') {
        $schema['contentUrl'] = (string) $video['src'];
    } elseif (($video['youtube'] ?? '') !== '') {
        $schema['embedUrl'] = 'https://www.youtube-nocookie.com/embed/' . rawurlencode((string) $video['youtube']);
    }
    $duration = boblift_category_video_iso_duration((int) ($video['duration'] ?? 0));
    if ($duration !== '') {
        $schema['duration'] = $duration;
    }
    return $schema;
}

/**
 * The film card on the category page, inside .boblift-archive-about after the intro and brochure (a full-width row there,
 * input.css). It reuses the product page's in-place player (data-inline-videos in theme.js): the poster and a play
 * button until clicked, so the film costs nothing until then.
 */
function boblift_render_category_video(WP_Term $term): void
{
    $video = boblift_category_video($term);
    if (!$video) {
        return;
    }
    $title_id = 'category-video-title-' . $term->term_id;
    $clock = boblift_category_video_clock((int) $video['duration']);
    $eyebrow = $video['eyebrow'] !== '' ? $video['eyebrow'] : boblift_localized_static_text('Product video');
    $player = $video['src'] !== '' ? 'local' : 'youtube';
    $schema = boblift_category_video_schema($video);
    ?>
    <section id="category-video" class="mt-12 scroll-mt-24 overflow-hidden rounded border border-[#E5E7EB] bg-white shadow-card lg:mt-16" data-category-video aria-labelledby="<?php echo esc_attr($title_id); ?>">
        <div class="grid lg:grid-cols-[minmax(0,1.35fr)_minmax(0,1fr)] lg:items-center">
            <div class="min-w-0" data-inline-videos>
                <div class="relative aspect-video overflow-hidden bg-[#071f42]" data-inline-video-stage>
                    <button class="group absolute inset-0 block w-full focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-inset focus-visible:ring-blue-200" type="button" data-inline-video-play data-video-player="<?php echo esc_attr($player); ?>" data-video-src="<?php echo esc_url($video['src']); ?>" data-video-youtube="<?php echo esc_attr($video['youtube']); ?>" data-video-title="<?php echo esc_attr($video['title']); ?>" data-video-poster="<?php echo esc_url($video['poster']); ?>" aria-label="<?php echo esc_attr($video['title']); ?>">
                        <img class="absolute inset-0 h-full w-full object-cover" src="<?php echo esc_url($video['poster']); ?>" alt="" loading="lazy" decoding="async">
                        <span class="absolute inset-0 m-auto grid size-14 place-items-center rounded-sm bg-white/90 text-[#071f42] shadow-card transition group-hover:bg-white md:size-16"><?php echo boblift_icon('play', 'h-6 w-6'); ?></span>
                    </button>
                </div>
            </div>
            <div class="min-w-0 px-5 py-6 sm:px-8 sm:py-8 lg:px-10 lg:py-10 xl:px-12">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700 rtl:tracking-normal"><?php echo esc_html($eyebrow); ?><?php if ($clock !== '') : ?><span class="text-slate-400"> · </span><span class="[font-variant-numeric:tabular-nums]" dir="ltr"><?php echo esc_html($clock); ?></span><?php endif; ?></p>
                <h2 id="<?php echo esc_attr($title_id); ?>" class="mt-2 text-[clamp(1.4rem,2vw,2rem)] font-black leading-tight tracking-[-0.01em] text-[#0B1B33]"><?php echo esc_html($video['title']); ?></h2>
                <?php if ($video['text'] !== '') : ?><p class="mt-4 text-sm font-normal leading-7 text-slate-600 md:text-base"><?php echo esc_html($video['text']); ?></p><?php endif; ?>
                <?php if ($video['points']) : ?>
                    <ul class="mt-5 grid gap-2.5">
                        <?php foreach ($video['points'] as $point) : ?>
                            <li class="flex items-start gap-2.5 text-sm font-semibold leading-6 text-[#0B1B33] md:text-[15px]"><span class="mt-[0.6rem] size-1.5 shrink-0 bg-[#F59E0B]" aria-hidden="true"></span><span class="min-w-0"><?php echo esc_html($point); ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($schema) : ?>
            <script type="application/ld+json"><?php echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG); ?></script>
        <?php endif; ?>
    </section>
    <?php
}
