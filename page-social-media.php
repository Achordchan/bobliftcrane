<?php
/**
 * Template Name: BOB-LIFT Social Media
 */
get_header();

$site_options = function_exists('boblift_options') ? boblift_options() : [];
$social_page = function_exists('boblift_social_media_page_options') ? boblift_social_media_page_options() : [];
$contact = is_array($site_options['contact'] ?? null) ? $site_options['contact'] : [];
$t = static fn(mixed $text): string => boblift_localized_static_text(trim((string) $text));

$hero = is_array($social_page['hero'] ?? null) ? $social_page['hero'] : [];
$labels = is_array($social_page['labels'] ?? null) ? $social_page['labels'] : [];
$cta = is_array($social_page['cta'] ?? null) ? $social_page['cta'] : [];
$detail_ui = function_exists('boblift_site_ui') ? boblift_site_ui('productDetail') : [];

$has_media = static function ($media): bool {
    if (is_array($media)) {
        return (int) ($media['id'] ?? 0) > 0 || trim((string) ($media['path'] ?? '')) !== '';
    }
    return trim((string) $media) !== '';
};
$media_url = static function ($media): string {
    $id = is_array($media) ? (int) ($media['id'] ?? 0) : 0;
    if ($id > 0) {
        return (string) wp_get_attachment_url($id);
    }
    $path = trim(is_array($media) ? (string) ($media['path'] ?? '') : (string) $media);
    return $path === '' ? '' : (preg_match('#^https?://#', $path) ? $path : boblift_asset($path));
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

// Real account URLs saved in the footer social links, keyed by lowercase label.
$footer_social = is_array($site_options['footer']['social_links'] ?? null) ? $site_options['footer']['social_links'] : [];
$link_map = [];
foreach ($footer_social as $link) {
    $label = strtolower(trim((string) (is_array($link) ? ($link['label'] ?? '') : '')));
    $href = trim((string) (is_array($link) ? ($link['href'] ?? '') : ''));
    if ($label !== '' && preg_match('#^https?://#i', $href)) {
        $link_map[$label] = $href;
    }
}
// A card links out only once a real account URL is saved (its own, or the footer's for the same platform); placeholders and old /contact fallbacks stay unlinked.
$account_url = static function (array $platform) use ($link_map): string {
    $href = trim((string) ($platform['href'] ?? ''));
    if (preg_match('#^https?://#i', $href)) {
        return $href;
    }
    $key = strtolower(trim((string) ($platform['label'] ?? '')));
    foreach ($link_map as $configured_label => $configured_href) {
        if ($key !== '' && ($key === $configured_label || str_contains($key, $configured_label) || str_contains($configured_label, $key))) {
            return $configured_href;
        }
    }
    return '';
};
$platforms = static function (string $key) use ($social_page): array {
    $rows = is_array($social_page[$key] ?? null) ? $social_page[$key] : [];
    return array_values(array_filter($rows, static fn($row): bool => is_array($row) && (string) ($row['enabled'] ?? '1') !== '0' && trim((string) ($row['label'] ?? '')) !== ''));
};
$social_icon = static function (string $icon): string {
    $icon = sanitize_key($icon);
    $path = 'images/social-icons/' . $icon . '.svg';
    if ($icon !== '' && function_exists('boblift_asset_path') && is_readable(boblift_asset_path($path))) {
        return '<img src="' . esc_url(boblift_asset($path)) . '" alt="" class="h-5 w-5 object-contain" loading="lazy" decoding="async">';
    }
    return boblift_icon('globe', 'h-5 w-5 text-white');
};

// Rows added in admin have no colour of their own, so the icon picks its brand colour.
$platform_tone = static function (array $platform): string {
    $tone = trim((string) ($platform['tone'] ?? ''));
    if ($tone !== '') {
        return $tone;
    }
    $tones = ['linkedin' => 'bg-[#0A66C2]', 'youtube' => 'bg-[#FF0000]', 'facebook' => 'bg-[#1877F2]', 'instagram' => 'bg-[linear-gradient(135deg,#833AB4,#FD1D1D,#FCAF45)]', 'whatsapp' => 'bg-[#25D366]', 'telegram' => 'bg-[#229ED9]', 'pinterest' => 'bg-[#E60023]', 'wechat' => 'bg-[#07C160]', 'xiaohongshu' => 'bg-[#FF2442]', 'weibo' => 'bg-[#E6162D]', 'bilibili' => 'bg-[#00A1D6]', 'kuaishou' => 'bg-[#FF6600]', 'zhihu' => 'bg-[#1677FF]', 'baidu' => 'bg-[#2F6BFF]', 'toutiao' => 'bg-[#F04142]', 'youku' => 'bg-[#2B8CFF]', 'line' => 'bg-[#06C755]', 'vk' => 'bg-[#2787F5]', 'ok' => 'bg-[#EE8208]', 'viber' => 'bg-[#7360F2]', 'quora' => 'bg-[#B92B27]'];
    return $tones[sanitize_key((string) ($platform['icon'] ?? ''))] ?? 'bg-[#111827]';
};
$platform_groups = array_values(array_filter([
    ['id' => 'overseas', 'title' => $t($labels['overseas_title'] ?? 'Global platforms'), 'rows' => $platforms('featured_platforms')],
    ['id' => 'china', 'title' => $t($labels['china_title'] ?? 'China platforms'), 'rows' => $platforms('major_platforms')],
    ['id' => 'more', 'title' => $t($labels['more_title'] ?? 'More platforms'), 'rows' => $platforms('secondary_platforms')],
], static fn(array $group): bool => $group['rows'] !== []));

$posts = array_values(array_filter(is_array($social_page['content_cards'] ?? null) ? $social_page['content_cards'] : [], static fn($card): bool => is_array($card) && (string) ($card['enabled'] ?? '1') !== '0' && ($has_media($card['image'] ?? '') || trim((string) ($card['title'] ?? '')) !== '' || trim((string) ($card['text'] ?? '')) !== '')));
// A saved card without a picture keeps its text and shows the built-in factory-news photo, as the old page did.
$posts = array_map(static function (array $post) use ($has_media): array {
    if (!$has_media($post['image'] ?? '')) {
        $post['image'] = ['id' => 0, 'path' => 'images/social-media/social-media-factory-news.webp'];
    }
    return $post;
}, $posts);
$gallery = array_map(static fn(array $post): string => boblift_image_setting_url($post['image']), $posts);
$captions = array_map(static fn(array $post): string => $t($post['title'] ?? ''), $posts);
// The lightbox caption carries the saved description too ("Title — description"), so older cards keep their text.
$lightbox_captions = array_map(static fn(array $post): string => trim($t($post['title'] ?? '') . (trim((string) ($post['text'] ?? '')) !== '' ? ' — ' . $t($post['text']) : ''), ' —'), $posts);
$posts_title = $t($labels['posts_title'] ?? 'What we have been posting.');

// A short plays from YouTube once its link is saved; until then from the MP4, which only exists on a local preview.
$local_video = static function ($media) use ($media_url): string {
    if (is_array($media) && (int) ($media['id'] ?? 0) > 0) {
        return $media_url($media);
    }
    $path = trim(is_array($media) ? (string) ($media['path'] ?? '') : (string) $media);
    return $path !== '' && is_readable(get_template_directory() . '/assets/' . $path) ? $media_url($media) : '';
};
$videos = [];
foreach (is_array($social_page['videos'] ?? null) ? $social_page['videos'] : [] as $row) {
    if (!is_array($row)) {
        continue;
    }
    $row['youtube_id'] = boblift_youtube_id((string) ($row['youtube'] ?? ''));
    $row['src'] = $row['youtube_id'] === '' ? $local_video($row['video'] ?? '') : '';
    if ($row['youtube_id'] !== '' || $row['src'] !== '') {
        $videos[] = $row;
    }
}

$whatsapp = preg_replace('/\D+/', '', (string) ($contact['whatsapp'] ?? ($contact['phone'] ?? '')));
?>
<div class="page-shell pt-5 md:pt-8">
    <?php
    boblift_page_hero([
        'label' => $t($hero['label'] ?? 'BOB-LIFT Social Media'),
        'title' => $t($labels['main_platforms_title'] ?? 'Follow BOB-LIFT on the platforms you already use.'),
        'text' => $t($labels['main_platforms_text'] ?? ''),
        'image' => boblift_image_setting_url($hero['image'] ?? [], 'images/social-media/social-media-hero-workshop.webp'),
        'image_alt' => $t($hero['image_alt'] ?? 'A phone on a tripod filming BOB-LIFT knuckle boom cranes in the workshop'),
        'image_position' => 'center 40%',
    ]);
    ?>
</div>

<?php
// Old hero copy an editor customised, which differs from the new heading, stays visible under the hero.
$legacy_hero_title = trim((string) ($hero['title'] ?? ''));
$legacy_hero_text = trim((string) ($hero['text'] ?? ''));
$shown_title = trim((string) ($labels['main_platforms_title'] ?? ''));
$shown_text = trim((string) ($labels['main_platforms_text'] ?? ''));
if (($legacy_hero_title !== '' && $legacy_hero_title !== $shown_title) || ($legacy_hero_text !== '' && $legacy_hero_text !== $shown_text)) : ?>
<section class="page-shell pt-10 md:pt-12">
    <div class="mx-auto max-w-3xl text-center">
        <?php if ($legacy_hero_title !== '' && $legacy_hero_title !== $shown_title) : ?><h2 class="text-[clamp(1.5rem,2.4vw,2.25rem)] font-black leading-tight text-ink"><?php echo esc_html($t($legacy_hero_title)); ?></h2><?php endif; ?>
        <?php if ($legacy_hero_text !== '' && $legacy_hero_text !== $shown_text) : ?><p class="mt-3 text-sm font-semibold leading-7 text-slate-600 md:text-base"><?php echo esc_html($t($legacy_hero_text)); ?></p><?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($platform_groups !== []) : ?>
<section id="official-channels" class="py-12 md:py-16" aria-labelledby="social-accounts-title">
    <div class="page-shell">
        <p class="section-label mb-3"><?php echo esc_html($t($labels['main_platforms_label'] ?? 'Official accounts')); ?></p>
        <h2 id="social-accounts-title" class="sr-only"><?php echo esc_html($t($labels['main_platforms_label'] ?? 'Official accounts')); ?></h2>
        <div class="grid gap-10 md:gap-12">
            <?php foreach ($platform_groups as $group) : ?>
                <div>
                    <h3 id="social-group-<?php echo esc_attr($group['id']); ?>" class="mb-4 text-xl font-black tracking-[-0.01em] text-ink md:text-2xl"><?php echo esc_html($group['title']); ?></h3>
                    <ul class="grid grid-cols-2 gap-2.5 sm:gap-3 lg:grid-cols-3 lg:gap-4">
                        <?php foreach ($group['rows'] as $platform) :
                            $url = $account_url($platform);
                            $label = $t($platform['label'] ?? '');
                            $handle = trim((string) ($platform['handle'] ?? ''));
                            $text = $t($platform['text'] ?? '');
                            $has_qr = $has_media($platform['qr'] ?? '');
                            $tag = $url !== '' ? 'a' : 'div';
                        ?>
                            <li>
                                <<?php echo $tag; ?> class="group relative flex h-full flex-col items-start gap-3 rounded border border-line bg-white p-3.5 shadow-card sm:flex-row sm:items-center sm:gap-4 sm:p-5<?php echo $url !== '' ? ' transition duration-300 hover:-translate-y-0.5 hover:border-blue-300' : ''; ?>"<?php if ($url !== '') : ?> href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener"<?php endif; ?>>
                                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-sm <?php echo esc_attr($platform_tone($platform)); ?>"><?php echo $social_icon((string) ($platform['icon'] ?? '')); ?></span>
                                    <span class="min-w-0 w-full flex-1">
                                        <span class="block truncate text-[15px] font-black text-ink sm:text-base"><?php echo esc_html($label); ?></span>
                                        <?php if ($handle !== '') : ?><span class="block truncate text-xs font-bold text-blue-700"><?php echo esc_html($handle); ?></span><?php endif; ?>
                                        <?php if ($text !== '') : ?><span class="mt-0.5 block text-xs font-semibold leading-5 text-slate-500 sm:text-sm"><?php echo esc_html($text); ?></span><?php endif; ?>
                                    </span>
                                    <?php if ($has_qr) : ?>
                                        <img class="h-16 w-16 shrink-0 rounded-sm border border-line bg-white object-contain p-1" src="<?php echo esc_url(boblift_image_setting_url($platform['qr'])); ?>" alt="<?php echo esc_attr($label . ' · ' . $t($labels['qr_label'] ?? 'Scan to follow')); ?>" loading="lazy" decoding="async">
                                    <?php elseif ($url !== '') : ?>
                                        <?php echo boblift_icon('arrow-up-right', 'absolute right-3 top-3 h-5 w-5 shrink-0 text-slate-400 transition group-hover:text-blue-600 sm:static'); ?>
                                    <?php endif; ?>
                                </<?php echo $tag; ?>>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($posts !== [] || $videos !== []) : ?>
<section class="border-t border-line bg-mist py-12 md:py-16 lg:py-20" aria-labelledby="social-posts-title">
    <div class="page-shell">
        <div class="mb-8 flex flex-col gap-3 md:mb-10 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="section-label mb-3"><?php echo esc_html($t($labels['posts_label'] ?? 'Recent posts')); ?></p>
                <h2 id="social-posts-title" class="text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em] text-ink"><?php echo esc_html($posts_title); ?></h2>
            </div>
            <?php if (trim((string) ($labels['posts_text'] ?? '')) !== '') : ?><p class="max-w-md text-sm font-semibold leading-6 text-slate-500"><?php echo esc_html($t($labels['posts_text'])); ?></p><?php endif; ?>
        </div>

        <div class="grid gap-10 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:gap-8 xl:gap-10">
        <?php if ($videos !== []) : ?>
            <div class="min-w-0">
                <h3 class="mb-4 text-lg font-black text-ink"><?php echo esc_html($t($labels['videos_title'] ?? 'Short videos')); ?></h3>
                <div class="-mx-4 flex snap-x snap-mandatory scroll-px-4 gap-3 overflow-x-auto px-4 pb-2 [scrollbar-width:none] sm:mx-0 sm:grid sm:overflow-visible sm:px-0 sm:pb-0 lg:gap-4 <?php echo count($videos) >= 3 ? 'sm:grid-cols-3' : 'sm:grid-cols-2'; ?> [&::-webkit-scrollbar]:hidden">
                    <?php foreach ($videos as $video) :
                        $video_title = $t($video['title'] ?? '');
                        $poster = $has_media($video['poster'] ?? '') ? boblift_image_setting_url($video['poster']) : '';
                    ?>
                        <figure class="w-[62%] shrink-0 snap-start overflow-hidden rounded border border-line bg-white shadow-card sm:w-auto">
                            <div class="relative aspect-[9/16] bg-navy">
                                <?php if ($video['youtube_id'] !== '') : boblift_render_video_player(); ?>
                                    <button class="group absolute inset-0 block h-full w-full" type="button" data-video-open data-video-player="youtube" data-video-src="" data-video-youtube="<?php echo esc_attr($video['youtube_id']); ?>" data-video-title="<?php echo esc_attr($video_title); ?>" data-video-portrait aria-label="<?php echo esc_attr($t($labels['play_label'] ?? 'Play video') . ' · ' . $video_title); ?>">
                                        <?php if ($poster !== '') : ?><img class="absolute inset-0 h-full w-full object-cover" src="<?php echo esc_url($poster); ?>" alt="" loading="lazy" decoding="async"><?php endif; ?>
                                        <span class="absolute inset-0 m-auto grid size-12 place-items-center rounded-sm bg-white/90 text-navy shadow-card transition group-hover:bg-white"><?php echo boblift_icon('play', 'h-5 w-5'); ?></span>
                                    </button>
                                <?php else : ?>
                                    <video class="absolute inset-0 h-full w-full object-cover" playsinline preload="none" controls<?php if ($poster !== '') : ?> poster="<?php echo esc_url($poster); ?>"<?php endif; ?> src="<?php echo esc_url($video['src']); ?>" aria-label="<?php echo esc_attr($video_title); ?>" data-social-video></video>
                                <?php endif; ?>
                            </div>
                            <?php if ($video_title !== '') : ?><figcaption class="truncate px-3.5 py-3 text-sm font-black text-ink"><?php echo esc_html($video_title); ?></figcaption><?php endif; ?>
                        </figure>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($posts !== []) : ?>
            <div class="min-w-0<?php echo $videos === [] ? ' lg:col-span-2' : ''; ?>" data-detail-viewer data-detail-images="<?php echo esc_attr(wp_json_encode($gallery, JSON_UNESCAPED_SLASHES)); ?>" data-detail-alt="<?php echo esc_attr($posts_title); ?>" data-detail-captions="<?php echo esc_attr(wp_json_encode($lightbox_captions, JSON_UNESCAPED_UNICODE)); ?>">
                <h3 class="mb-4 text-lg font-black text-ink"><?php echo esc_html($t($labels['image_posts_title'] ?? 'Image posts')); ?></h3>
                <ul class="grid grid-cols-3 gap-2 sm:gap-3 lg:grid-cols-4 lg:gap-4">
                    <?php foreach ($posts as $index => $post) : ?>
                        <li>
                            <button class="group block w-full overflow-hidden rounded border border-line bg-white text-left shadow-card transition duration-300 hover:-translate-y-0.5 hover:border-blue-300 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-blue-100" type="button" data-detail-open="<?php echo esc_attr((string) $index); ?>" aria-label="<?php echo esc_attr($captions[$index]); ?>">
                                <span class="relative block aspect-square overflow-hidden bg-mist"><img class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]" src="<?php echo esc_url($thumb_url($post['image'])); ?>" alt="<?php echo esc_attr($captions[$index]); ?>" loading="lazy" decoding="async"></span>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="fixed inset-0 z-[1000] hidden flex-col bg-[#040f22]" data-detail-lightbox role="dialog" aria-modal="true" aria-hidden="true" aria-label="<?php echo esc_attr($posts_title); ?>">
                    <div class="flex shrink-0 items-center justify-between px-4 py-3 text-white md:px-6">
                        <p class="flex min-w-0 items-baseline gap-3 text-xs font-black tracking-[0.08em]" aria-live="polite"><span class="shrink-0 [font-variant-numeric:tabular-nums]"><span data-detail-index>01</span> / <?php echo esc_html(str_pad((string) count($gallery), 2, '0', STR_PAD_LEFT)); ?></span><span class="truncate text-sm tracking-normal text-white/80" data-detail-caption></span></p>
                        <button class="grid size-10 place-items-center rounded-sm bg-white/10 text-white transition hover:bg-white hover:text-[#071f42]" type="button" data-detail-close aria-label="<?php echo esc_attr($detail_ui['closeLabel'] ?? 'Close'); ?>"><?php echo boblift_icon('x', 'h-5 w-5'); ?></button>
                    </div>
                    <div class="relative min-h-0 flex-1">
                        <div class="absolute inset-0 flex items-center justify-center overflow-auto px-3 pb-4 md:px-20" data-detail-stage>
                            <img class="block max-h-full max-w-full object-contain" src="" alt="" data-detail-image>
                        </div>
                        <?php if (count($gallery) > 1) : ?>
                            <button class="boblift-feedback-arrow left-2 md:left-5" type="button" data-detail-prev aria-label="<?php echo esc_attr($detail_ui['previousLabel'] ?? 'Previous'); ?>"><?php echo boblift_icon('chevron-left', 'h-5 w-5'); ?></button>
                            <button class="boblift-feedback-arrow right-2 md:right-5" type="button" data-detail-next aria-label="<?php echo esc_attr($detail_ui['nextLabel'] ?? 'Next'); ?>"><?php echo boblift_icon('chevron-right', 'h-5 w-5'); ?></button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="page-shell py-12 md:py-16" aria-labelledby="social-direct-title">
    <div class="flex flex-col gap-6 rounded bg-navy p-6 text-white sm:p-10 lg:flex-row lg:items-center lg:justify-between lg:gap-12 xl:p-12">
        <div class="max-w-2xl">
            <p class="section-label mb-3"><?php echo esc_html($t($cta['label'] ?? 'Talk to us directly')); ?></p>
            <h2 id="social-direct-title" class="text-[clamp(1.5rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em]"><?php echo esc_html($t($cta['title'] ?? 'Rather message us than follow?')); ?></h2>
            <?php if (trim((string) ($cta['text'] ?? '')) !== '') : ?><p class="mt-3 text-sm font-semibold leading-7 text-white/75 md:text-base"><?php echo esc_html($t($cta['text'])); ?></p><?php endif; ?>
        </div>
        <div class="flex shrink-0 flex-wrap gap-3">
            <?php
            // A destination saved for this button (booking page, external channel, ...) is honoured; the default inquiry action opens the modal.
            $cta_href = trim((string) ($cta['button_href'] ?? ''));
            $cta_is_default = $cta_href === '' || in_array(rtrim($cta_href, '/'), ['/contact.html', '/contact', '/quote.html'], true);
            $cta_class = 'inline-flex h-12 items-center justify-center gap-2 rounded-sm bg-white px-6 text-sm font-black text-navy transition hover:-translate-y-0.5 hover:bg-blue-50';
            ?>
            <?php if ($cta_is_default) : ?>
                <button class="<?php echo esc_attr($cta_class); ?>" type="button" data-inquiry-modal-open data-inquiry-source="社媒页"><?php echo esc_html($t($cta['button_label'] ?? 'Send an Inquiry')); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></button>
            <?php else : ?>
                <a class="<?php echo esc_attr($cta_class); ?>" href="<?php echo esc_url(boblift_url($cta_href)); ?>" ><?php echo esc_html($t($cta['button_label'] ?? 'Send an Inquiry')); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
            <?php endif; ?>
            <?php if ($whatsapp !== '') : ?>
                <a class="inline-flex h-12 items-center justify-center gap-2 rounded-sm border border-white/30 px-6 text-sm font-black text-white transition hover:border-white" href="<?php echo esc_url('https://wa.me/' . $whatsapp); ?>" target="_blank" rel="noopener">WhatsApp</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php get_footer(); ?>
