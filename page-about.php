<?php
get_header();
$about_page = boblift_about_page_options();
$logo = $about_page['logo'] ?? [];
$strength = $about_page['strength'] ?? [];
$timeline = $about_page['timeline'] ?? [];
$executives = $about_page['executives'] ?? [];
$timeline_items = $timeline['items'] ?? [];
$executive_members = $executives['members'] ?? [];
$executive_team_image = $executives['team_image'] ?? ($executives['group_image'] ?? boblift_image_option('images/about/quanyuan-compressed.jpg'));
$executive_team_label = (string) ($executives['team_label'] ?? 'Whole Team');
$executive_team_title = (string) ($executives['team_title'] ?? 'BOB-LIFT Team');
$executive_team_alt = (string) ($executives['team_image_alt'] ?? 'BOB-LIFT full team group photo');
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
    $executive_team_label = $executive_team_label === 'Whole Team' ? '全体团队' : $executive_team_label;
    $executive_team_title = $executive_team_title === 'BOB-LIFT Team' ? 'BOB-LIFT 团队' : $executive_team_title;
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
                    <span class="about-logo-layer about-logo-layer--solid" data-about-logo-solid>BOB-LIFT</span>
                    <span class="about-logo-layer about-logo-layer--image" data-about-logo-image>BOB-LIFT</span>
                </div>
            </div>

        </div>
    </div>
</section>

<section id="about-strength" class="about-manufacturing-section relative isolate overflow-hidden bg-[#F8FAFC] pt-14 md:pt-20 lg:pt-24 pb-16 lg:pb-24" data-about-strength>
    <div class="about-strength-bridge pointer-events-none absolute inset-x-0 top-0 h-20 lg:h-24" data-about-strength-bridge></div>
    <div class="page-shell about-wide-shell relative z-10">
        <div class="about-manufacturing-copy mx-auto w-full text-center" data-about-strength-copy>
            <p class="section-label mb-6 text-center"><?php echo esc_html($strength['label'] ?? 'Company Strength'); ?></p>
            <h2 class="about-strength-title mx-auto max-w-none text-[34px] font-black leading-[1.1] tracking-[-0.005em] text-[#0B1B33] md:text-[52px] xl:text-[68px]"><?php echo wp_kses_post($strength_title_html); ?></h2>
            <p class="mx-auto mt-6 max-w-none text-base font-semibold leading-[1.7] tracking-normal text-[#64748B] md:text-lg xl:text-xl"><?php echo esc_html($strength['text'] ?? 'BOB-LIFT combines product engineering, precision production and responsive export service into one disciplined workflow for overseas B2B buyers.'); ?></p>
        </div>

        <?php if (!empty($global_stats)) : ?>
        <div class="mx-auto mt-14 grid w-full max-w-none gap-6 <?php echo count($global_stats) >= 3 ? 'md:grid-cols-3' : 'md:grid-cols-2'; ?> lg:mt-20 lg:gap-8" data-about-global data-about-strength-features>
            <?php foreach (array_slice($global_stats, 0, 3) as $global_stat_index => $stat) :
                $stat_number_raw = (string) ($stat['number'] ?? '0');
                $stat_target = preg_replace('/[^0-9]/', '', $stat_number_raw) ?: '0';
                $stat_suffix = (string) ($stat['suffix'] ?? '+');
            ?>
                <div class="about-stat-card relative min-h-[190px] overflow-hidden rounded border border-[#E5E7EB] bg-white p-8 text-left shadow-card transition hover:-translate-y-1 lg:p-10 xl:p-12">
                    <span class="pointer-events-none absolute -right-4 -top-8 select-none text-[120px] font-black italic leading-none tracking-[-0.015em] text-[#F1F5F9] lg:-right-6 lg:-top-10 lg:text-[140px]"><?php echo esc_html(str_pad((string) ($global_stat_index + 1), 2, '0', STR_PAD_LEFT)); ?></span>
                    <div class="relative z-10">
                        <p class="flex items-baseline font-black leading-none tracking-[-0.02em] text-[#0B3F7D]">
                            <span class="text-[54px] lg:text-[68px]" data-global-stat="<?php echo esc_attr($stat_target); ?>"><?php echo esc_html($stat_target); ?></span><span class="ml-1 text-[28px] text-[#2563EB] lg:text-[34px]"><?php echo esc_html($stat_suffix); ?></span>
                        </p>
                        <h3 class="mt-6 max-w-none text-lg font-black leading-tight text-[#0B1B33] lg:text-xl"><?php echo esc_html($stat['label'] ?? ''); ?></h3>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</section>

<section class="about-timeline-section bg-[#F8FAFC] pt-16 lg:pt-24 pb-16 lg:pb-28" data-about-timeline>
    <div class="page-shell about-wide-shell">
        <div class="about-timeline-header mx-auto w-full text-center mb-16 max-w-none lg:mb-24" data-about-timeline-header>
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
                    <p class="mb-2 text-xs font-black uppercase tracking-[0.28em] text-[#2563EB]"><?php echo esc_html($certification_label); ?></p>
                    <h3 class="text-[24px] font-black leading-tight tracking-normal text-[#0B1B33] md:text-[32px]"><?php echo esc_html($certification_title); ?></h3>
                </div>
                <p class="max-w-[760px] text-sm font-semibold leading-7 text-[#64748B]"><?php echo esc_html($certification_text); ?></p>
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
                    <button class="about-certification-card group w-[230px] shrink-0 cursor-zoom-in rounded border border-[#DDE7F2] bg-white p-2 text-left shadow-card transition hover:-translate-y-1 hover:border-[#BFD2EA] md:w-[270px]" type="button"<?php echo $track_index === 1 ? ' tabindex="-1"' : ''; ?> data-certification-open data-certification-src="<?php echo esc_url($certification_image_url); ?>" data-certification-title="<?php echo esc_attr($certification_title_text); ?>">
                        <span class="block overflow-hidden rounded bg-[#F8FAFC] ring-1 ring-[#E5E7EB]">
                            <img class="aspect-[5/7] w-full object-contain p-2 transition duration-500 group-hover:scale-[1.02]" src="<?php echo esc_url($certification_card_url); ?>" alt="<?php echo esc_attr($certification_title_text); ?>" loading="lazy" decoding="async">
                        </span>
                        <span class="block px-2 pb-2 pt-4">
                            <span class="line-clamp-2 text-sm font-black leading-tight text-[#0B1B33]"><?php echo esc_html($certification_title_text); ?></span>
                            <?php if ($certification['text'] !== '') : ?>
                                <span class="mt-2 line-clamp-2 block text-xs font-semibold leading-5 text-[#64748B]"><?php echo esc_html($certification['text']); ?></span>
                            <?php endif; ?>
                        </span>
                    </button>
                    <?php endforeach; ?>
                </div>
                <?php endfor; ?>
            </div>

            <div class="fixed inset-0 z-[999] hidden items-center justify-center bg-[#0B1B33]/88 p-4 backdrop-blur-md" data-certification-lightbox aria-hidden="true">
                <button class="absolute inset-0 cursor-zoom-out" type="button" data-certification-close aria-label="<?php echo esc_attr($is_zh_about ? '关闭证书大图' : 'Close certificate preview'); ?>"></button>
                <figure class="relative z-10 max-h-[92vh] w-full max-w-[980px] rounded bg-white p-3 shadow-card md:p-4">
                    <button class="absolute right-3 top-3 grid h-10 w-10 place-items-center rounded-sm bg-[#0B1B33] text-white shadow-card transition hover:bg-[#0B3F7D]" type="button" data-certification-close aria-label="<?php echo esc_attr($is_zh_about ? '关闭' : 'Close'); ?>">×</button>
                    <img class="max-h-[78vh] w-full rounded object-contain" src="" alt="" data-certification-lightbox-image>
                    <figcaption class="px-2 pb-1 pt-3 text-center text-sm font-black text-[#0B1B33]" data-certification-lightbox-title></figcaption>
                </figure>
            </div>
        </div>
        <?php endif; ?>

    </div>
</section>

<section id="about-manufacturing-gallery" class="relative bg-white py-8 lg:py-12" data-about-gallery>
    <div class="page-shell about-wide-shell mb-5 md:mb-6">
        <div class="flex flex-col items-start justify-between gap-6 md:flex-row md:items-end">
            <div>
                <p class="mb-4 text-xs font-black uppercase tracking-[0.28em] text-[#2563EB]"><?php echo esc_html($gallery_label); ?></p>
                <h2 class="text-[36px] font-black leading-[1.06] tracking-normal text-[#0B1B33] md:text-[52px] xl:text-[64px]"><?php echo esc_html($gallery_title); ?></h2>
            </div>
            <p class="max-w-[760px] text-sm font-semibold leading-7 text-[#64748B] md:text-base"><?php echo esc_html($gallery_text); ?></p>
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
                        <p class="mt-3 max-w-[620px] text-sm font-semibold leading-7 text-white/78 md:text-base"><?php echo esc_html(boblift_localized_static_text((string) ($item['text'] ?? ''))); ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="about-gallery-scroll-spacer hidden lg:block" data-gallery-spacer aria-hidden="true"></div>
</section>



<section id="about-leadership" class="about-executive-section relative overflow-visible bg-[#F8FAFC] pb-12 pt-0 lg:pb-0 lg:pt-0" data-about-executive>
    <div class="about-executive-pin flex min-h-screen items-center py-12" data-executive-pin>
        <div class="page-shell">
            <div class="about-executive-flip relative" data-executive-flip>
                <div class="about-executive-card relative overflow-hidden rounded border border-[#E5E7EB] bg-white p-5 shadow-card md:p-7 lg:p-8 2xl:p-10" data-executive-card>
                <div class="about-executive-aura pointer-events-none absolute inset-0"></div>
                <div class="about-executive-watermark pointer-events-none absolute -right-10 top-4 text-[clamp(72px,12vw,230px)] font-black leading-none tracking-[-0.03em] text-[#0B3F7D]/[0.055]" data-executive-watermark><?php echo esc_html($about_ui['leadershipWatermark'] ?? 'LEADERSHIP'); ?></div>
                <div class="relative z-10 mb-6 grid gap-6 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)] lg:items-end">
                    <div data-executive-copy-item>
                        <p class="mb-4 text-xs font-black uppercase tracking-[0.42em] text-[#0B3F7D]"><?php echo esc_html($executives['label'] ?? 'Executive Leadership'); ?></p>
                        <h2 class="max-w-none whitespace-nowrap text-[clamp(34px,4.2vw,72px)] font-black leading-[1.02] tracking-[-0.015em] text-[#0B1B33]"><?php echo esc_html($executives['title'] ?? 'Leadership Team'); ?></h2>
                    </div>
                </div>
                <div class="about-executive-grid relative z-10 grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:gap-5" data-executive-grid>
                    <?php foreach ($executive_members as $member) : ?>
                        <article class="about-executive-member group grid overflow-hidden rounded border border-[#E5E7EB] bg-white shadow-card md:grid-cols-[clamp(150px,12vw,200px)_minmax(0,1fr)]" data-executive-member>
                            <div class="relative aspect-square w-full overflow-hidden bg-[#EAF1F8] md:aspect-[4/5]">
                                <?php echo boblift_img_from_setting($member['image'] ?? [], 'images/about/executives/executive-01-square.png', ($member['name'] ?? 'BOB-LIFT executive') . ' portrait', 'absolute inset-0 h-full w-full object-cover object-center transition duration-700 group-hover:scale-[1.035]', ['loading' => 'lazy', 'decoding' => 'async', 'data-executive-image' => '']); ?>
                            </div>
                            <div class="flex min-w-0 flex-col justify-center p-5 2xl:p-6">
                                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-[#2563EB]"><?php echo esc_html($member['role'] ?? ''); ?></p>
                                <h3 class="mt-3 text-[clamp(24px,1.75vw,36px)] font-black leading-[1.04] tracking-[-0.01em] text-[#0B1B33]"><?php echo esc_html($member['name'] ?? ''); ?></h3>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                </div>
                <div class="about-executive-photo absolute inset-0 flex flex-col overflow-hidden rounded border border-[#DDE7F2] bg-white shadow-card" data-executive-photo aria-hidden="true">
                    <div class="relative z-10 shrink-0 px-5 pb-4 pt-5 md:px-7 md:pt-7 lg:px-8 lg:pt-8 2xl:px-10 2xl:pt-10">
                        <p class="mb-4 text-xs font-black uppercase tracking-[0.42em] text-[#0B3F7D]"><?php echo esc_html($executive_team_label); ?></p>
                        <h2 class="max-w-none whitespace-nowrap text-[clamp(34px,4.2vw,72px)] font-black leading-[1.02] tracking-[-0.015em] text-[#0B1B33]"><?php echo esc_html($executive_team_title); ?></h2>
                    </div>
                    <div class="relative min-h-0 flex-1 bg-[#F8FAFC] px-4 pb-5 md:px-7 md:pb-7 lg:px-8 lg:pb-8 2xl:px-10 2xl:pb-10">
                        <?php echo boblift_img_from_setting($executive_team_image, 'images/about/quanyuan-compressed.jpg', $executive_team_alt, 'h-full w-full object-contain object-center', ['loading' => 'lazy', 'decoding' => 'async']); ?>
                    </div>
                    <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(180deg,rgba(248,250,252,0.04)_0%,rgba(11,27,51,0.035)_100%)]"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>
