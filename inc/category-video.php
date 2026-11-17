<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * A product category's film, shown with the category intro and brochure as one block: the intro heading with the film
 * under it on the left, the intro text and the brochure strip on the right (stacked on phones). The film's title, text
 * and points stand in when the category has no intro ("eyebrow" is kept but not shown). Each Polylang language term holds its own copy in
 * the term meta _boblift_category_video, a JSON string written through REST:
 * POST /wp/v2/product_category/<id> with {"meta": {"_boblift_category_video": "{...}"}}.
 *   {"src": "https://…/film.mp4", "youtube": "", "poster": "https://…/poster.jpg", "eyebrow": "", "title": "",
 *    "text": "", "points": [""], "duration": 66, "upload_date": "2026-11-04"}
 * "upload_date" is a day (taken as midnight Beijing time) or an ISO 8601 date-time with its offset; the VideoObject
 * always gets the full date-time with the offset, since Search Console flags a bare day as missing its time zone.
 * A film needs a poster and either an MP4 (https) or a YouTube id. A poster with neither is a still photo instead: same
 * frame, no play button, its "alt" as the image's alt text, no VideoObject (categories without footage of their own).
 * Without a poster or a title, "" (or anything else) removes the card.
 * Plain text throughout. The page also gets a VideoObject for a film.
 */

/** "2026-11-04T00:00:00+08:00" for "2026-11-04" or a date-time with an offset ("Z" becomes "+00:00"); '' for anything else. */
function boblift_category_video_upload_date(string $date): string
{
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $day)) {
        return checkdate((int) $day[2], (int) $day[3], (int) $day[1]) ? $date . 'T00:00:00+08:00' : '';
    }
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})T([01]\d|2[0-3]):([0-5]\d)(?::([0-5]\d))?(Z|[+-](?:[01]\d|2[0-3]):[0-5]\d)$/', $date, $at)) {
        return '';
    }
    if (!checkdate((int) $at[2], (int) $at[3], (int) $at[1])) {
        return '';
    }
    return sprintf('%s-%s-%sT%s:%s:%s%s', $at[1], $at[2], $at[3], $at[4], $at[5], $at[6] !== '' ? $at[6] : '00', $at[7] === 'Z' ? '+00:00' : $at[7]);
}

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
        'alt' => $plain($data['alt'] ?? ''),
        'text' => $plain($data['text'] ?? ''),
        'points' => [],
        'duration' => max(0, min(36000, (int) ($data['duration'] ?? 0))),
        'upload_date' => boblift_category_video_upload_date($date),
    ];
    foreach ((array) ($data['points'] ?? []) as $point) {
        $point = $plain(is_scalar($point) ? $point : '');
        if ($point !== '' && count($video['points']) < 4) {
            $video['points'][] = $point;
        }
    }
    // A film whose MP4 or YouTube id was refused is dropped, not shown as a still photo.
    $bad_src = trim((string) ($data['src'] ?? '')) !== '' && $video['src'] === '';
    $bad_youtube = $youtube !== '' && $video['youtube'] === '';
    if ($video['poster'] === '' || $video['title'] === '' || $bad_src || $bad_youtube) {
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

/** Whether the card is a film (an MP4 or a YouTube id) rather than a still photo. */
function boblift_category_video_playable(array $video): bool
{
    return ($video['src'] ?? '') !== '' || ($video['youtube'] ?? '') !== '';
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

/** The VideoObject for a category film, or [] when Google's required fields (name, thumbnail, upload date) are missing. */
function boblift_category_video_schema(array $video): array
{
    if (($video['title'] ?? '') === '' || ($video['poster'] ?? '') === '' || ($video['upload_date'] ?? '') === '' || !boblift_category_video_playable($video)) {
        return [];
    }
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'VideoObject',
        'name' => (string) $video['title'],
        'description' => (string) (($video['text'] ?? '') !== '' ? $video['text'] : $video['title']),
        'thumbnailUrl' => [(string) $video['poster']],
        'uploadDate' => boblift_category_video_upload_date((string) $video['upload_date']),
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
 * The category's intro, film and brochure as one block inside .boblift-archive-about. Desktop: two columns, the heading
 * with the film right under it on the left, the intro paragraphs with the brochure strip at the foot on the right, the
 * strip's bottom on the film's bottom when the text is the shorter side. From 1600px the film is capped at 40rem and the
 * text steps up, since a wider film grows taller faster than the text shortens; this keeps the hole above the strip
 * small on wide screens. Phones stack heading, film, text, strip.
 * $intro is ['slug' => …, 'title' => …, 'paragraphs' => […]] from the category; without paragraphs the film's own title,
 * text and points stand in. $brochure is an item of boblift_brochure_items() or null.
 * The player is the product page's in-place player (data-inline-videos in theme.js): poster and play button until clicked.
 */
function boblift_render_category_video(WP_Term $term, ?array $brochure = null, array $intro = []): void
{
    $video = boblift_category_video($term);
    if (!$video) {
        return;
    }
    $slug = (string) ($intro['slug'] ?? $term->slug);
    $paragraphs = array_values(array_filter(array_map('strval', (array) ($intro['paragraphs'] ?? [])), static fn(string $text): bool => trim($text) !== ''));
    $points = [];
    $heading = trim((string) ($intro['title'] ?? ''));
    if (!$paragraphs) {
        $heading = $video['title'];
        $paragraphs = $video['text'] !== '' ? [$video['text']] : [];
        $points = $video['points'];
    }
    $title_id = 'category-intro-' . $slug;
    $playable = boblift_category_video_playable($video);
    $player = $video['src'] !== '' ? 'local' : 'youtube';
    $schema = boblift_category_video_schema($video);
    ?>
    <section id="category-video" class="mt-12 scroll-mt-24 lg:mt-16" data-category-video data-category-intro="<?php echo esc_attr($slug); ?>" aria-labelledby="<?php echo esc_attr($title_id); ?>">
        <?php // The heading spans both columns; below it the film and the text start on one line and, on desktop, end on one line too (see .boblift-category-film in input.css). ?>
        <h2 id="<?php echo esc_attr($title_id); ?>" class="text-[clamp(1.4rem,2vw,2rem)] font-black leading-tight tracking-[-0.01em] text-[#0B1B33]"><?php echo esc_html($heading); ?></h2>
        <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] min-[1600px]:grid-cols-2 lg:gap-x-[clamp(2.5rem,4vw,4rem)]">
            <div class="boblift-category-film min-w-0">
                <?php if ($playable) : ?>
                <div class="boblift-category-film-frame overflow-hidden rounded border border-[#E5E7EB] shadow-card" data-inline-videos>
                    <div class="boblift-category-film-stage relative aspect-video overflow-hidden bg-[#071f42]" data-inline-video-stage>
                        <button class="group absolute inset-0 block w-full focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-inset focus-visible:ring-blue-200" type="button" data-inline-video-play data-video-player="<?php echo esc_attr($player); ?>" data-video-src="<?php echo esc_url($video['src']); ?>" data-video-youtube="<?php echo esc_attr($video['youtube']); ?>" data-video-title="<?php echo esc_attr($video['title']); ?>" data-video-poster="<?php echo esc_url($video['poster']); ?>" aria-label="<?php echo esc_attr($video['title']); ?>">
                            <img class="absolute inset-0 h-full w-full object-cover" src="<?php echo esc_url($video['poster']); ?>" alt="" loading="lazy" decoding="async">
                            <span class="absolute inset-0 m-auto grid size-14 place-items-center rounded-sm bg-white/90 text-[#071f42] shadow-card transition group-hover:bg-white md:size-16"><?php echo boblift_icon('play', 'h-6 w-6'); ?></span>
                        </button>
                    </div>
                </div>
                <?php else : ?>
                <div class="boblift-category-film-frame overflow-hidden rounded border border-[#E5E7EB] shadow-card" data-category-photo>
                    <div class="boblift-category-film-stage relative aspect-video overflow-hidden bg-[#071f42]">
                        <img class="absolute inset-0 h-full w-full object-cover" src="<?php echo esc_url($video['poster']); ?>" alt="<?php echo esc_attr(($video['alt'] ?? '') !== '' ? $video['alt'] : $video['title']); ?>" loading="lazy" decoding="async">
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="boblift-category-film-text flex min-w-0 flex-col">
                <?php foreach ($paragraphs as $index => $paragraph) : ?>
                    <p class="<?php echo $index ? 'mt-4 lg:mt-3' : 'lg:mt-0'; ?> text-sm font-normal leading-7 text-slate-600 md:text-base min-[1600px]:text-[1.0625rem] min-[1600px]:leading-[1.9]"><?php echo esc_html($paragraph); ?></p>
                <?php endforeach; ?>
                <?php if ($points) : ?>
                    <ul class="mt-5 grid gap-2.5">
                        <?php foreach ($points as $point) : ?>
                            <li class="flex items-start gap-2.5 text-sm font-semibold leading-6 text-[#0B1B33] md:text-[15px]"><span class="mt-[0.6rem] size-1.5 shrink-0 bg-[#F59E0B]" aria-hidden="true"></span><span class="min-w-0"><?php echo esc_html($point); ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if ($brochure && function_exists('boblift_brochure_quote_button')) :
                    $brochure_ui = boblift_site_ui('brochures');
                    $brochure_meta = array_values(array_filter([
                        (string) ($brochure_ui['pdfLabel'] ?? 'PDF'),
                        (string) ($brochure_ui['languageLabel'] ?? 'EN'),
                        str_replace('{count}', (string) $brochure['pages'], (string) ($brochure_ui['pagesLabel'] ?? '{count} pages')),
                        (string) $brochure['size'],
                    ], 'strlen'));
                    $download_title = str_replace('{title}', $brochure['title'], (string) ($brochure_ui['downloadTitleTemplate'] ?? 'Download {title} as PDF'));
                    ?>
                    <?php // The spacer pins the strip to the column's foot with at least 1.25rem above it; two rows beside the cover: name and chips, then the buttons. ?>
                    <div class="hidden min-h-4 flex-1 lg:block" aria-hidden="true"></div>
                    <div id="brochures" class="mt-6 grid lg:mt-0 scroll-mt-24 grid-cols-[2.75rem_minmax(0,1fr)] items-center gap-4 rounded border border-[#E5E7EB] bg-white px-4 py-3 shadow-card" data-category-video-brochure data-brochure-family="<?php echo esc_attr($brochure['key']); ?>">
                        <a class="block" href="<?php echo esc_url($brochure['url']); ?>" download="<?php echo esc_attr($brochure['file']); ?>" tabindex="-1" aria-hidden="true"><img class="aspect-[520/711] w-full rounded-sm border border-line bg-white object-cover" src="<?php echo esc_url($brochure['cover']); ?>" width="520" height="711" alt="" loading="lazy" decoding="async"></a>
                        <div class="min-w-0">
                            <p class="text-sm font-black leading-5 text-ink"><?php echo esc_html($brochure['title']); ?><span class="ms-2 whitespace-nowrap text-[11px] font-black uppercase tracking-[0.1em] text-slate-500"><?php echo esc_html(implode(' · ', $brochure_meta)); ?></span></p>
                            <div class="mt-2 flex flex-wrap items-center gap-x-5 gap-y-1">
                                <a class="inline-flex h-9 items-center justify-center gap-2 rounded-sm bg-navy px-4 text-sm font-black text-white transition hover:bg-[#0b2d5b]" href="<?php echo esc_url($brochure['url']); ?>" download="<?php echo esc_attr($brochure['file']); ?>" title="<?php echo esc_attr($download_title); ?>"><?php echo boblift_icon('download', 'h-4 w-4'); ?><?php echo esc_html((string) ($brochure_ui['downloadLabel'] ?? 'Download PDF')); ?></a>
                                <?php echo boblift_brochure_quote_button($brochure, [], 'inline-flex h-9 items-center text-sm font-black text-blue-700 transition hover:text-navy'); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($schema) : ?>
            <script type="application/ld+json"><?php echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG); ?></script>
        <?php endif; ?>
    </section>
    <?php
}
