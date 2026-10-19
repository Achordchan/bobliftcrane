<?php
get_header();
$about_page = boblift_about_page_options();
$logo = $about_page['logo'] ?? [];
$strength = $about_page['strength'] ?? [];
$timeline = $about_page['timeline'] ?? [];
$timeline_items = $timeline['items'] ?? [];
$manufacturing_gallery = is_array($about_page['manufacturing_gallery'] ?? null) ? $about_page['manufacturing_gallery'] : [];
$facility_gallery = is_array($manufacturing_gallery['items'] ?? null) ? array_values(array_filter($manufacturing_gallery['items'], static fn($item): bool => is_array($item))) : [];
$about_language = function_exists('boblift_current_language_slug') ? boblift_current_language_slug() : 'en';
$is_zh_about = str_starts_with($about_language, 'zh');
$gallery_label = (string) ($manufacturing_gallery['label'] ?? 'Modern Manufacturing Base');
$gallery_title = (string) ($manufacturing_gallery['title'] ?? 'Intelligent Production.');
$gallery_text = (string) ($manufacturing_gallery['text'] ?? 'Experience our state-of-the-art 37,000 sqm facility where precision engineering meets rigorous quality control.');
$global_footprint = is_array($about_page['global_footprint'] ?? null) ? $about_page['global_footprint'] : [];
$global_stats = is_array($global_footprint['stats'] ?? null) ? array_values(array_filter($global_footprint['stats'], static fn($item): bool => is_array($item) && trim((string) ($item['number'] ?? '')) !== '')) : [];
if ($is_zh_about) {
    $gallery_label = $gallery_label === 'Modern Manufacturing Base' ? '现代化制造基地' : $gallery_label;
    $gallery_title = $gallery_title === 'Intelligent Production.' ? '现代化生产现场' : $gallery_title;
    $gallery_text = $gallery_text === 'Experience our state-of-the-art 37,000 sqm facility where precision engineering meets rigorous quality control.' ? '从结构件加工到整机装配，制造流程围绕稳定质量和出口交付展开。' : $gallery_text;
    $global_stat_label_map = [
        'Countries Exported' => '出口国家和地区',
        'Years Experience' => '经验年限',
        'Units Delivered' => '累计交付设备',
        'Cranes Exported' => '已出口起重机数量',
    ];
    foreach ($global_stats as $global_stat_index => $global_stat) {
        $global_stat_label = (string) ($global_stat['label'] ?? '');
        if (isset($global_stat_label_map[$global_stat_label])) {
            $global_stats[$global_stat_index]['label'] = $global_stat_label_map[$global_stat_label];
        }
    }
}
$certification_options = is_array($about_page['certifications'] ?? null) ? $about_page['certifications'] : [];
$certification_label = (string) ($certification_options['label'] ?? 'International Certifications');
$certification_title = (string) ($certification_options['title'] ?? 'Certification documents for export projects');
$certification_text = (string) ($certification_options['text'] ?? 'Core certification documents supporting overseas procurement, factory audits and project delivery reviews.');
if ($is_zh_about) {
    $certification_label = $certification_label === 'International Certifications' ? '国际资质认证' : $certification_label;
    $certification_title = $certification_title === 'Certification documents for export projects' ? '面向出口项目的认证文件' : $certification_title;
    $certification_text = $certification_text === 'Core certification documents supporting overseas procurement, factory audits and project delivery reviews.' ? '上传真实证书图片，支持海外采购、验厂与项目交付审核。' : $certification_text;
}
$certification_title_map = $is_zh_about ? [
    'ISO 9001 Quality Management' => 'ISO 9001 质量管理体系',
    'CE European Conformity' => 'CE 欧盟符合性认证',
    'EAC Conformity' => 'EAC 欧亚经济联盟认证',
    'SGS Inspection & Certification' => 'SGS 检验认证',
] : [];
$certification_items = is_array($certification_options['items'] ?? null) ? array_values(array_filter($certification_options['items'], static fn($item): bool => is_array($item))) : [];
$certification_items = array_values(array_filter(array_map(static function (array $item) use ($certification_title_map): array {
    $title = (string) ($item['title'] ?? '');
    if (isset($certification_title_map[$title])) {
        $title = $certification_title_map[$title];
    }
    return [
        'image' => is_array($item['image'] ?? null) ? $item['image'] : ['id' => 0, 'path' => (string) ($item['image'] ?? '')],
        'title' => $title,
        'text' => (string) ($item['text'] ?? ''),
    ];
}, $certification_items), static function (array $item): bool {
    $image = $item['image'];
    return (int) ($image['id'] ?? 0) > 0 || trim((string) ($image['path'] ?? '')) !== '';
}));
$about_ui = function_exists('boblift_site_ui') ? boblift_site_ui('aboutPage') : [];
$logo_silhouette_image = $logo['silhouette_image'] ?? boblift_image_option('images/about/about-logo-1.png');
$logo_silhouette_alt = (string) ($logo['silhouette_alt'] ?? 'BOB-LIFT crane silhouette background');
$about_aerial_start_setting = $logo['aerial_start_image'] ?? boblift_image_option('images/about/about-aerial-mid-start.webp');
$about_aerial_end_setting = $logo['aerial_end_image'] ?? boblift_image_option('images/about/about-aerial-mid-end.webp');
$about_aerial_video_setting = is_array($logo['aerial_video'] ?? null) ? $logo['aerial_video'] : ['id' => 0, 'path' => 'videos/about/about-aerial-mid.mp4'];
$about_aerial_start = boblift_image_setting_url($about_aerial_start_setting, 'images/about/about-aerial-mid-start.webp');
$about_aerial_end = boblift_image_setting_url($about_aerial_end_setting, 'images/about/about-aerial-mid-end.webp');
$about_aerial_video = '';
$about_aerial_video_id = (int) ($about_aerial_video_setting['id'] ?? 0);
if ($about_aerial_video_id > 0) {
    $about_aerial_video_url = wp_get_attachment_url($about_aerial_video_id);
    $about_aerial_video = is_string($about_aerial_video_url) ? $about_aerial_video_url : '';
}
if ($about_aerial_video === '') {
    $about_aerial_video_path = (string) ($about_aerial_video_setting['path'] ?? 'videos/about/about-aerial-mid.mp4');
    $about_aerial_video = $about_aerial_video_path !== '' ? boblift_asset($about_aerial_video_path) : '';
}
$about_aerial_start_alt = (string) ($logo['aerial_start_alt'] ?? 'BOB-LIFT factory aerial view opening frame');
$about_aerial_end_alt = (string) ($logo['aerial_end_alt'] ?? 'BOB-LIFT factory aerial view ending frame');
$strength_title = (string) ($strength['title'] ?? 'Manufacturing intelligence for global lifting projects.');
$strength_title_html = esc_html($strength_title);
foreach (['中国徐州', 'Xuzhou, China', '起重设备制造商', 'crane manufacturer', '制造智能', '日常运营', 'Manufacturing intelligence', 'daily operation'] as $strength_highlight) {
    if ($strength_highlight !== '' && str_contains($strength_title, $strength_highlight)) {
        $strength_title_html = str_replace(esc_html($strength_highlight), '<span class="text-blue-700">' . esc_html($strength_highlight) . '</span>', $strength_title_html);
        break;
    }
}
?>
<section class="about-logo-scroll-section relative isolate bg-[#F8FAFC]" data-about-logo-scroll style="--about-logo-image:url('<?php echo esc_url($about_aerial_start); ?>');">
    <div class="about-logo-pin relative overflow-hidden" data-about-logo-pin>
        <h1 class="sr-only"><?php echo esc_html(get_the_title() ?: (string) ($logo['label'] ?? 'About BOB-LIFT')); ?></h1>
        <div class="absolute inset-0 bg-[#F8FAFC]"></div>
        <div class="about-logo-silhouette absolute inset-0" data-about-silhouette>
            <?php echo boblift_img_from_setting($logo_silhouette_image, 'images/about/about-logo-1.png', $logo_silhouette_alt, 'absolute inset-0 h-full w-full object-cover object-center', ['loading' => 'eager', 'decoding' => 'async', 'fetchpriority' => 'high']); ?>
        </div>
        <div class="about-aerial-media about-crane-scene absolute inset-0" data-about-crane-bg data-about-aerial-media>
            <img class="about-aerial-frame about-aerial-start" src="<?php echo esc_url($about_aerial_start); ?>" alt="<?php echo esc_attr($about_aerial_start_alt); ?>" loading="eager" decoding="async" fetchpriority="high" data-about-aerial-start>
            <?php if ($about_aerial_video !== '') : ?>
                <video class="about-aerial-video" muted playsinline preload="metadata" poster="<?php echo esc_url($about_aerial_start); ?>" data-about-aerial-video>
                    <source src="<?php echo esc_url($about_aerial_video); ?>" type="video/mp4">
                </video>
            <?php endif; ?>
            <img class="about-aerial-frame about-aerial-end" src="<?php echo esc_url($about_aerial_end); ?>" alt="<?php echo esc_attr($about_aerial_end_alt); ?>" loading="lazy" decoding="async" data-about-aerial-end>
        </div>
        <div class="about-logo-mask pointer-events-none absolute inset-0 bg-[linear-gradient(180deg,rgba(248,250,252,0.62)_0%,rgba(248,250,252,0.18)_34%,rgba(248,250,252,0.84)_100%),radial-gradient(circle_at_50%_42%,rgba(255,255,255,0.12),rgba(248,250,252,0.72)_72%)]" data-about-mask></div>
        <div class="page-shell about-logo-shell relative z-10 flex h-full flex-col items-center justify-center py-8 md:py-10 lg:py-12">
            <div class="about-logo-stage pointer-events-none absolute inset-0 flex items-center justify-center" data-about-logo-stage aria-hidden="true">
                <div class="about-logo-fill" data-about-logo-text>
                    <?php // The trademark outlines, not a web font: a two-tone solid layer and a photo-filled layer masked by the same shape. ?>
                    <span class="about-logo-layer about-logo-layer--solid about-logo-mark" data-about-logo-solid><?php echo boblift_brand_wordmark_svg('about-logo-mark-svg'); ?></span>
                    <span class="about-logo-layer about-logo-layer--image about-logo-mark about-logo-mark--image" style="--about-logo-mask: url('<?php echo esc_attr(boblift_brand_wordmark_mask_uri()); ?>')" data-about-logo-image>
                        <?php if ($about_aerial_video !== '') : ?>
                            <?php // The letters show the same aerial footage as the background, kept in step by theme.js. ?>
                            <video class="about-logo-mark-video" muted playsinline loop preload="none" poster="<?php echo esc_url($about_aerial_start); ?>" data-about-logo-video>
                                <source src="<?php echo esc_url($about_aerial_video); ?>" type="video/mp4">
                            </video>
                        <?php endif; ?>
                    </span>
                </div>
            </div>

        </div>
    </div>
</section>
<?php
// An introduction saved in the old layout (headline, paragraph, proof chips) is still shown, as a block under the hero.
// Only text an editor changed counts; the built-in introduction copy is not repeated under the redesigned hero.
$legacy_intro = boblift_about_legacy_intro($logo);
$legacy_headline = $legacy_intro['headline'];
$legacy_paragraph = $legacy_intro['paragraph'];
$legacy_chips = $legacy_intro['chips'];
// The old introduction's eyebrow label, when an editor changed it from the built-in "About BOB-LIFT".
$legacy_label = boblift_customised_legacy_text($logo['label'] ?? '', 'About BOB-LIFT');
if ($legacy_label !== '' || $legacy_headline !== '' || $legacy_paragraph !== '' || $legacy_chips !== []) : ?>
<section class="page-shell py-10 md:py-14" aria-label="<?php echo esc_attr($legacy_headline !== '' ? $legacy_headline : ($legacy_label !== '' ? $legacy_label : 'About BOB-LIFT')); ?>">
    <div class="mx-auto max-w-[860px] text-center">
        <?php if ($legacy_label !== '') : ?><p class="section-label mb-4 justify-center"><?php echo esc_html($legacy_label); ?></p><?php endif; ?>
        <?php if ($legacy_headline !== '') : ?><h2 class="text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.08] tracking-[-0.02em] text-[#0B1B33]"><?php echo esc_html($legacy_headline); ?></h2><?php endif; ?>
        <?php if ($legacy_paragraph !== '') : ?><p class="mx-auto mt-5 max-w-[760px] text-sm font-normal leading-7 text-[#64748B] md:text-base md:leading-8"><?php echo esc_html($legacy_paragraph); ?></p><?php endif; ?>
    </div>
    <?php if ($legacy_chips !== []) : ?>
        <div class="mt-7 grid gap-3 md:grid-cols-3">
            <?php foreach ($legacy_chips as $chip) : ?>
                <div class="rounded-full border border-line bg-white px-5 py-3 text-start shadow-card">
                    <span class="text-xs font-black tracking-[0.22em] text-[#2563EB]"><?php echo esc_html((string) ($chip['number'] ?? '')); ?></span>
                    <span class="ms-3 text-sm font-black text-[#0B1B33]"><?php echo esc_html((string) ($chip['label'] ?? '')); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php endif; ?>

<section id="about-strength" class="about-manufacturing-section relative isolate overflow-hidden bg-[#F8FAFC] pt-14 md:pt-20 lg:pt-24 pb-16 lg:pb-24" data-about-strength>
    <div class="about-strength-bridge pointer-events-none absolute inset-x-0 top-0 h-20 lg:h-24" data-about-strength-bridge></div>
    <div class="page-shell about-wide-shell relative z-10">
        <div class="about-manufacturing-copy mx-auto w-full text-center" data-about-strength-copy>
            <p class="section-label mb-6 text-center"><?php echo esc_html($strength['label'] ?? 'Company Strength'); ?></p>
            <h2 class="about-strength-title mx-auto max-w-none text-[34px] font-black leading-[1.1] tracking-[-0.005em] text-[#0B1B33] md:text-[52px] xl:text-[68px]"><?php echo wp_kses_post($strength_title_html); ?></h2>
            <p class="mx-auto mt-6 max-w-none text-base font-normal leading-[1.7] tracking-normal text-[#64748B] md:text-lg xl:text-xl"><?php echo esc_html($strength['text'] ?? 'BOB-LIFT combines product engineering, precision production and responsive export service into one disciplined workflow for overseas B2B buyers.'); ?></p>
        </div>

        <?php if (!empty($global_stats)) : ?>
        <div class="mx-auto mt-14 grid w-full max-w-none gap-6 <?php echo count($global_stats) >= 3 ? 'md:grid-cols-3' : 'md:grid-cols-2'; ?> lg:mt-20 lg:gap-8" data-about-global data-about-strength-features>
            <?php foreach (array_slice($global_stats, 0, 3) as $global_stat_index => $stat) :
                $stat_number_raw = (string) ($stat['number'] ?? '0');
                // Whole numbers count up; anything else (2.5, 1,200) is shown exactly as saved, without the count-up.
                $stat_display = trim($stat_number_raw) !== '' ? trim($stat_number_raw) : '0';
                $stat_animates = preg_match('/^\d+$/', $stat_display) === 1;
                $stat_suffix = (string) ($stat['suffix'] ?? '+');
            ?>
                <div class="about-stat-card relative min-h-[190px] overflow-hidden rounded border border-[#E5E7EB] bg-white p-8 text-start shadow-card transition hover:-translate-y-1 lg:p-10 xl:p-12">
                    <span class="pointer-events-none absolute -end-4 -top-8 select-none text-[120px] font-black italic leading-none tracking-[-0.015em] text-[#F1F5F9] lg:-end-6 lg:-top-10 lg:text-[140px]"><?php echo esc_html(str_pad((string) ($global_stat_index + 1), 2, '0', STR_PAD_LEFT)); ?></span>
                    <div class="relative z-10">
                        <p class="flex items-baseline font-black leading-none tracking-[-0.02em] text-[#0B3F7D]">
                            <span class="text-[54px] lg:text-[68px]"<?php if ($stat_animates) : ?> data-global-stat="<?php echo esc_attr($stat_display); ?>"<?php endif; ?>><?php echo esc_html($stat_display); ?></span><span class="ms-1 text-[28px] text-[#2563EB] lg:text-[34px]"><?php echo esc_html($stat_suffix); ?></span>
                        </p>
                        <h3 class="mt-6 max-w-none text-lg font-black leading-tight text-[#0B1B33] lg:text-xl"><?php echo esc_html($stat['label'] ?? ''); ?></h3>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</section>

<?php
// Cards and call to action saved in the old Company Strength layout stay visible until an editor removes them.
$legacy_strength = boblift_about_legacy_strength($strength);
if ($legacy_strength['cards'] !== [] || $legacy_strength['cta'] !== null || $legacy_strength['image'] !== null) : ?>
<section class="bg-[#F8FAFC] pb-12 lg:pb-16" aria-label="<?php echo esc_attr((string) ($strength['label'] ?? 'Company Strength')); ?>">
    <div class="page-shell">
        <?php if ($legacy_strength['image'] !== null) : ?>
            <div class="mb-8 overflow-hidden rounded border border-line bg-white shadow-card"><?php echo boblift_img_from_setting($legacy_strength['image'], 'images/about/about-logo-prototype-crane-ai.png', (string) ($strength['title'] ?? ''), 'mx-auto block h-auto max-h-[520px] w-full object-contain'); ?></div>
        <?php endif; ?>
        <?php if ($legacy_strength['cards'] !== []) : ?>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <?php foreach ($legacy_strength['cards'] as $legacy_card) : ?>
                    <div class="rounded border border-line bg-white p-6 shadow-card">
                        <h3 class="text-lg font-black leading-tight text-[#0B1B33]"><?php echo esc_html((string) ($legacy_card['title'] ?? '')); ?></h3>
                        <p class="mt-3 text-sm font-normal leading-7 text-[#64748B]"><?php echo esc_html((string) ($legacy_card['text'] ?? '')); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($legacy_strength['cta'] !== null) : ?>
            <p class="mt-8 text-center"><a class="inline-flex h-12 items-center justify-center gap-2 rounded-sm bg-navy px-6 text-sm font-black text-white transition hover:bg-[#0b2d5b]" href="<?php echo esc_url(boblift_url($legacy_strength['cta']['href'])); ?>"><?php echo esc_html($legacy_strength['cta']['label']); ?></a></p>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<section class="about-timeline-section bg-[#F8FAFC] pt-16 lg:pt-24 pb-16 lg:pb-28" data-about-timeline>
    <div class="page-shell about-wide-shell">
        <div class="about-timeline-header mx-auto w-full text-center mb-16 max-w-none lg:mb-24" data-about-timeline-header>
            <?php
            // The redesigned section has no eyebrow; a label an editor changed from the built-in "Development Timeline" stays visible.
            $timeline_label = trim((string) ($timeline['label'] ?? ''));
            $timeline_label_defaults = ['Development Timeline', function_exists('boblift_localized_static_text') ? boblift_localized_static_text('Development Timeline') : 'Development Timeline'];
            if ($timeline_label !== '' && !in_array($timeline_label, $timeline_label_defaults, true)) : ?>
                <p class="mb-4 text-xs font-black uppercase tracking-[0.14em] text-slate-500"><?php echo esc_html($timeline_label); ?></p>
            <?php endif; ?>
            <h2 class="text-[28px] font-black leading-[1.15] tracking-[-0.005em] text-[#0B1B33] md:text-[36px] xl:text-[44px]"><?php echo esc_html($timeline['title'] ?? 'Built step by step for global lifting projects.'); ?></h2>
        </div>
        <div class="about-timeline-track" data-about-timeline-track>
            <div class="about-timeline-spine" data-about-timeline-spine></div>
            <?php foreach ($timeline_items as $index => $item) :
                $year  = (string) ($item['year']  ?? '');
                $title = (string) ($item['title'] ?? '');
                $even  = ($index % 2 === 0);
            ?>
            <div class="about-timeline-entry<?php echo $even ? ' entry--right' : ' entry--left'; ?>"
                 data-about-timeline-entry
                 data-entry-index="<?php echo esc_attr((string) $index); ?>">
                <div class="about-timeline-node" data-about-timeline-node>
                    <div class="about-timeline-dot" data-about-timeline-dot></div>
                    <?php if ($year !== '') : ?>
                        <span class="about-timeline-year" data-about-timeline-year><?php echo esc_html($year); ?></span>
                    <?php endif; ?>
                </div>
                <div class="about-timeline-panel" data-about-timeline-panel>
                    <div class="about-timeline-panel-media">
                        <?php echo boblift_img_from_setting($item['image'] ?? [], 'images/factory-workshop.jpg', $title, 'absolute inset-0 h-full w-full object-cover', ['loading' => 'lazy', 'decoding' => 'async']); ?>
                        <div class="about-timeline-panel-media-overlay"></div>
                    </div>
                    <div class="about-timeline-panel-body">
                        <h3 class="about-timeline-panel-title"><?php echo esc_html($title); ?></h3>
                        <p class="about-timeline-panel-text"><?php echo esc_html($item['text'] ?? 'A key milestone in BOB-LIFT development.'); ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<!-- Manufacturing Facility (Gallery) -->

<section class="flow-root bg-[#F8FAFC] pb-12 pt-0 lg:pb-16">
    <div class="page-shell about-wide-shell relative z-10">
<?php if ($certification_items !== []) : ?>
        <div id="about-certifications" class="mt-10 border-t border-[#DDE7F2] pt-8 md:mt-12 md:pt-10 lg:mt-14" data-about-certifications>
            <div class="mb-6 flex flex-col gap-3 md:mb-8 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="boblift-kicker mb-2"><?php echo esc_html($certification_label); ?></p>
                    <h3 class="text-[24px] font-black leading-tight tracking-normal text-[#0B1B33] md:text-[32px]"><?php echo esc_html($certification_title); ?></h3>
                </div>
                <p class="max-w-[760px] text-sm font-normal leading-7 text-[#64748B]"><?php echo esc_html($certification_text); ?></p>
            </div>

            <div class="relative -mx-4 flex overflow-hidden px-4 py-2 md:-mx-6 md:px-6" data-certification-marquee>
                <?php for ($track_index = 0; $track_index < 2; $track_index++) : ?>
                <div class="flex shrink-0 items-stretch gap-5 px-2 md:gap-6" data-marquee-track<?php echo $track_index === 1 ? ' aria-hidden="true"' : ''; ?>>
                    <?php foreach ($certification_items as $certification) :
                        $certification_image_url = boblift_image_setting_url($certification['image'], (string) ($certification['image']['path'] ?? ''));
                        $certification_title_text = $certification['title'] !== '' ? $certification['title'] : $certification_label;
                        // Cards show the light "-sm" scan shipped next to theme documents; the viewer opens the full page.
                        $certification_path = (string) ($certification['image']['path'] ?? '');
                        $certification_small = preg_replace('/\.webp$/', '-sm.webp', $certification_path);
                        $certification_card_url = (int) ($certification['image']['id'] ?? 0) === 0 && $certification_small !== $certification_path && is_readable(get_template_directory() . '/assets/' . $certification_small)
                            ? boblift_image_src($certification_small)
                            : $certification_image_url;
                    ?>
                    <button class="about-certification-card group w-[230px] shrink-0 cursor-zoom-in rounded border border-[#DDE7F2] bg-white p-2 text-start shadow-card transition hover:-translate-y-1 hover:border-[#BFD2EA] md:w-[270px]" type="button"<?php echo $track_index === 1 ? ' tabindex="-1"' : ''; ?> data-certification-open data-certification-src="<?php echo esc_url($certification_image_url); ?>" data-certification-title="<?php echo esc_attr($certification_title_text); ?>">
                        <span class="block overflow-hidden rounded bg-[#F8FAFC] ring-1 ring-[#E5E7EB]">
                            <img class="aspect-[5/7] w-full object-contain p-2 transition duration-500 group-hover:scale-[1.02]" src="<?php echo esc_url($certification_card_url); ?>" alt="<?php echo esc_attr($certification_title_text); ?>" loading="lazy" decoding="async">
                        </span>
                        <span class="block px-2 pb-2 pt-4">
                            <span class="line-clamp-2 text-sm font-black leading-tight text-[#0B1B33]"><?php echo esc_html($certification_title_text); ?></span>
                            <?php if ($certification['text'] !== '') : ?>
                                <span class="mt-2 line-clamp-2 block text-xs font-normal leading-5 text-[#64748B]"><?php echo esc_html($certification['text']); ?></span>
                            <?php endif; ?>
                        </span>
                    </button>
                    <?php endforeach; ?>
                </div>
                <?php endfor; ?>
            </div>

        </div>
        <?php endif; ?>

    </div>
</section>
<?php if ($certification_items !== []) : ?>
<?php // Outside the section's stacking context so it covers the header and the floating contact bar. ?>
<div class="fixed inset-0 z-[999] hidden items-center justify-center bg-[#0B1B33]/88 p-4 backdrop-blur-md" data-certification-lightbox aria-hidden="true">
    <button class="absolute inset-0 cursor-zoom-out" type="button" data-certification-close aria-label="<?php echo esc_attr($is_zh_about ? '关闭证书大图' : 'Close certificate preview'); ?>"></button>
    <figure class="relative z-10 max-h-[92vh] w-full max-w-[980px] rounded bg-white p-3 shadow-card md:p-4">
        <button class="absolute end-3 top-3 grid h-10 w-10 place-items-center rounded-sm bg-[#0B1B33] text-white shadow-card transition hover:bg-[#0B3F7D]" type="button" data-certification-close aria-label="<?php echo esc_attr($is_zh_about ? '关闭' : 'Close'); ?>">×</button>
        <img class="max-h-[78vh] w-full rounded object-contain" src="" alt="" data-certification-lightbox-image>
        <figcaption class="px-2 pb-1 pt-3 text-center text-sm font-black text-[#0B1B33]" data-certification-lightbox-title></figcaption>
    </figure>
</div>
<?php endif; ?>

<section id="about-manufacturing-gallery" class="relative bg-white py-8 lg:py-12" data-about-gallery>
    <div class="page-shell about-wide-shell mb-5 md:mb-6">
        <div class="flex flex-col items-start justify-between gap-6 md:flex-row md:items-end">
            <div>
                <p class="boblift-kicker mb-4"><?php echo esc_html($gallery_label); ?></p>
                <h2 class="text-[36px] font-black leading-[1.06] tracking-normal text-[#0B1B33] md:text-[52px] xl:text-[64px]"><?php echo esc_html($gallery_title); ?></h2>
            </div>
            <p class="max-w-[760px] text-sm font-normal leading-7 text-[#64748B] md:text-base"><?php echo esc_html($gallery_text); ?></p>
        </div>
    </div>
    <div class="about-gallery-pin" data-gallery-pin>
        <div class="about-gallery-track flex w-max h-full items-center gap-6 px-4 md:px-8" data-gallery-track>
            <?php foreach ($facility_gallery as $index => $item) : ?>
                <article class="about-gallery-item group relative shrink-0 overflow-hidden rounded bg-[#E8EEF5] shadow-card" data-gallery-item>
                    <?php echo boblift_img_from_setting($item['image'] ?? [], 'images/factory-workshop.jpg', boblift_localized_static_text((string) ($item['title'] ?? 'BOB-LIFT factory view')), 'absolute inset-0 h-full w-full object-cover transition duration-700 group-hover:scale-[1.035]', ['loading' => 'lazy', 'decoding' => 'async']); ?>
                    <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(180deg,rgba(11,27,51,0.02)_0%,rgba(11,27,51,0.12)_48%,rgba(11,27,51,0.72)_100%)]"></div>
                    <div class="absolute inset-x-0 bottom-0 p-5 text-white md:p-7">
                        <span class="text-xs font-black tracking-[0.22em] text-white/70"><?php echo esc_html(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></span>
                        <h3 class="mt-3 text-3xl font-black leading-tight tracking-normal md:text-4xl"><?php echo esc_html(boblift_localized_static_text((string) ($item['title'] ?? ''))); ?></h3>
                        <p class="mt-3 max-w-[620px] text-sm font-normal leading-7 text-white/78 md:text-base"><?php echo esc_html(boblift_localized_static_text((string) ($item['text'] ?? ''))); ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="about-gallery-scroll-spacer hidden lg:block" data-gallery-spacer aria-hidden="true"></div>
</section>

<?php get_footer(); ?>
