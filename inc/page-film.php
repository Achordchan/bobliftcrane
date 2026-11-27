<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * One film for a template page that has no body of its own (Global Customer Visits, Factory Tour): a customer interview,
 * say. Each Polylang language page holds its own copy in the page meta _boblift_page_film, a JSON string written through
 * REST, in the same shape as a category film (inc/category-video.php):
 *   POST /wp/v2/pages/<id> {"meta": {"_boblift_page_film": "{\"src\": \"https://…/film.mp4\", \"poster\": \"https://…/poster.jpg\",
 *     \"eyebrow\": \"\", \"title\": \"\", \"text\": \"\", \"points\": [\"\"], \"duration\": 88, \"upload_date\": \"2026-11-27\"}"}}
 * A film needs a poster, a title and an MP4 (https) or a YouTube id; "" removes it. Plain text throughout.
 * The section prints a VideoObject next to the player.
 */

function boblift_register_page_film_meta(): void
{
    register_post_meta('page', '_boblift_page_film', [
        'type' => 'string',
        'single' => true,
        'default' => '',
        'show_in_rest' => true,
        'sanitize_callback' => static function ($value): string {
            $film = json_decode(boblift_category_video_sanitize($value), true);
            // A still photo is a category-only fallback; a page film must play.
            return is_array($film) && boblift_category_video_playable($film) ? (string) wp_json_encode($film, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        },
        'auth_callback' => static fn($allowed, $meta_key, $post_id): bool => current_user_can('edit_post', (int) $post_id),
    ]);
}
add_action('init', 'boblift_register_page_film_meta', 20);

/** The page's film in its own language, or [] when it has none. */
function boblift_page_film(int $page_id): array
{
    if ($page_id <= 0) {
        return [];
    }
    $film = json_decode(boblift_category_video_sanitize((string) get_post_meta($page_id, '_boblift_page_film', true)), true);
    return is_array($film) && boblift_category_video_playable($film) ? $film : [];
}

/** The film block: the player on one side, its label, title, text and points on the other (stacked on phones). */
function boblift_render_page_film(int $page_id, string $class = ''): void
{
    $film = boblift_page_film($page_id);
    if (!$film) {
        return;
    }
    $player = $film['src'] !== '' ? 'local' : 'youtube';
    $schema = boblift_category_video_schema($film);
    ?>
    <section id="page-film" class="scroll-mt-24 <?php echo esc_attr($class); ?>" data-page-film aria-labelledby="page-film-title">
        <div class="page-shell">
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)] lg:items-center lg:gap-[clamp(2.5rem,4vw,4rem)]">
                <div class="min-w-0 overflow-hidden rounded border border-line shadow-card" data-inline-videos>
                    <div class="relative aspect-video overflow-hidden bg-[#071f42]" data-inline-video-stage>
                        <button class="group absolute inset-0 block w-full focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-inset focus-visible:ring-blue-200" type="button" data-inline-video-play data-video-player="<?php echo esc_attr($player); ?>" data-video-src="<?php echo esc_url($film['src']); ?>" data-video-youtube="<?php echo esc_attr($film['youtube']); ?>" data-video-title="<?php echo esc_attr($film['title']); ?>" data-video-poster="<?php echo esc_url($film['poster']); ?>" aria-label="<?php echo esc_attr($film['title']); ?>">
                            <img class="absolute inset-0 h-full w-full object-cover" src="<?php echo esc_url($film['poster']); ?>" alt="" loading="lazy" decoding="async">
                            <span class="absolute inset-0 m-auto grid size-14 place-items-center rounded-sm bg-white/90 text-[#071f42] shadow-card transition group-hover:bg-white md:size-16"><?php echo boblift_icon('play', 'h-6 w-6'); ?></span>
                        </button>
                    </div>
                </div>
                <div class="min-w-0">
                    <?php if ($film['eyebrow'] !== '') : ?><p class="section-label mb-3"><?php echo esc_html($film['eyebrow']); ?></p><?php endif; ?>
                    <h2 id="page-film-title" class="text-[clamp(1.5rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em] text-ink"><?php echo esc_html($film['title']); ?></h2>
                    <?php if ($film['text'] !== '') : ?><p class="mt-4 text-sm font-normal leading-7 text-slate-600 md:text-base"><?php echo esc_html($film['text']); ?></p><?php endif; ?>
                    <?php if ($film['points']) : ?>
                        <ul class="mt-5 grid gap-2.5">
                            <?php foreach ($film['points'] as $point) : ?>
                                <li class="flex items-start gap-2.5 text-sm font-semibold leading-6 text-ink md:text-[15px]"><span class="mt-[0.6rem] size-1.5 shrink-0 bg-[#F59E0B]" aria-hidden="true"></span><span class="min-w-0"><?php echo esc_html($point); ?></span></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php if ($schema) : ?>
            <script type="application/ld+json"><?php echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG); ?></script>
        <?php endif; ?>
    </section>
    <?php
}
