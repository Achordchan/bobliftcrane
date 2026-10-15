<?php
if (!defined('ABSPATH')) {
    exit;
}

// Factory band content comes from the About page settings, so edits there reach the home page too.
function boblift_home_factory_content(): array
{
    $about = boblift_about_page_options();
    $logo = is_array($about['logo'] ?? null) ? $about['logo'] : [];
    $visits = is_array($about['customer_visits'] ?? null) ? $about['customer_visits'] : [];
    $language = function_exists('boblift_current_language_for_data') ? boblift_current_language_for_data() : '';

    // The About page ships these English defaults to every language; translate them until they are edited in admin.
    $translations = [
        'zh' => [
            'Built in our own 37,000 m² factory in Xuzhou.' => '在徐州 37,000㎡ 自有工厂制造。',
            'Welding, assembly and load testing all happen under one roof. Come and see the production line before you order.' => '焊接、装配、负载测试都在同一座工厂里完成。下单前，欢迎来工厂看生产线。',
            'Modern Manufacturing Base' => '现代化制造基地',
            'Intelligent Production.' => '现代化生产现场',
            'Experience our state-of-the-art 37,000 sqm facility where precision engineering meets rigorous quality control.' => '占地 37,000 平方米的现代化工厂，精密制造与严格质检贯穿每一台起重机。',
            'Precision Workshop' => '精密车间',
            'Structured production flow for crane welding, assembly and inspection.' => '起重机焊接、装配与检验按标准流程进行。',
            'Manufacturing Process' => '生产流程',
            'Factory operations built around stable output and repeatable quality.' => '以稳定产出和可复现的质量为核心组织生产。',
            'Factory Scale' => '工厂规模',
            'A modern manufacturing base supporting global lifting equipment demand.' => '现代化制造基地，支撑全球起重设备需求。',
            'Quality Control' => '质量控制',
            'Production details are documented for export-ready delivery.' => '生产细节全程记录，满足出口交付要求。',
        ],
        'ja' => [
            'Built in our own 37,000 m² factory in Xuzhou.' => '徐州の自社工場（37,000㎡）で製造。',
            'Welding, assembly and load testing all happen under one roof. Come and see the production line before you order.' => '溶接・組立・荷重試験まで、すべて同じ工場で行っています。ご発注前に、ぜひ生産ラインをご覧ください。',
            'Modern Manufacturing Base' => '近代的な製造拠点',
            'Intelligent Production.' => 'スマートな生産体制。',
            'Experience our state-of-the-art 37,000 sqm facility where precision engineering meets rigorous quality control.' => '37,000㎡の最新鋭工場で、精密なエンジニアリングと厳格な品質管理を両立しています。',
            'Precision Workshop' => '精密加工工場',
            'Structured production flow for crane welding, assembly and inspection.' => 'クレーンの溶接・組立・検査を体系的な工程で行います。',
            'Manufacturing Process' => '製造工程',
            'Factory operations built around stable output and repeatable quality.' => '安定した生産と再現性のある品質を軸にした工場運営。',
            'Factory Scale' => '工場規模',
            'A modern manufacturing base supporting global lifting equipment demand.' => '世界の揚重機器需要を支える近代的な製造拠点。',
            'Quality Control' => '品質管理',
            'Production details are documented for export-ready delivery.' => '輸出対応の納品に向け、生産の詳細を記録しています。',
        ],
    ][$language] ?? [];
    $translate = static fn(string $text): string => $translations[$text] ?? $text;

    // The video opens over the lake; the factory frame reads better as its still.
    $poster = boblift_image_setting_url($logo['aerial_end_image'] ?? [], 'images/about/about-aerial-mid-end.webp');
    $builtin_video = 'videos/about/about-aerial-mid.mp4';
    $video_setting = $logo['aerial_video'] ?? '';
    // Resolve the saved setting the way the About page does, so string URLs and theme paths keep working.
    $video = boblift_media_setting_url($video_setting, $builtin_video);
    $video_setting_path = is_array($video_setting) ? trim((string) ($video_setting['path'] ?? '')) : trim((string) $video_setting);
    $video_setting_id = is_array($video_setting) ? (int) ($video_setting['id'] ?? 0) : 0;
    // The built-in clip has a lighter phone cut; any other video plays as is on every screen.
    $video_mobile = $video_setting_id === 0 && ($video_setting_path === '' || $video_setting_path === $builtin_video)
        ? boblift_asset('videos/about/about-aerial-mid-mobile.mp4')
        : '';
    $home_factory = (array) boblift_option('home.factory', []);
    $saved_title = trim((string) ($home_factory['title'] ?? ''));
    $saved_text = trim((string) ($home_factory['text'] ?? ''));

    return [
        // The band closes the home About chapter; its heading is edited in 首页设置 › About 区 › 工厂视频区.
        'title' => $saved_title !== '' ? $saved_title : $translate('Built in our own 37,000 m² factory in Xuzhou.'),
        'text' => $saved_text !== '' ? $saved_text : $translate('Welding, assembly and load testing all happen under one roof. Come and see the production line before you order.'),
        'video_mobile' => $video_mobile,
        'poster' => $poster,
        'video' => $video,
        'cta_label' => (string) ($visits['cta_label'] ?? 'Plan a Visit'),
        'cta_href' => (string) ($visits['cta_href'] ?? '/contact.html'),
    ];
}

function boblift_render_home_factory(): void
{
    $factory = boblift_home_factory_content();
    ?>
    <div id="factory" class="scroll-mt-[120px]">
        <div class="page-shell">
            <div class="relative isolate flex min-h-[400px] flex-col justify-end overflow-hidden rounded bg-navy p-6 text-white sm:min-h-[440px] sm:p-10 lg:min-h-[480px] lg:justify-center lg:p-14">
                <img class="absolute inset-0 -z-30 h-full w-full object-cover" alt="" decoding="async" data-inview-src="<?php echo esc_url($factory['poster']); ?>">
                <?php if ($factory['video'] !== '') : ?>
                    <video class="absolute inset-0 -z-20 h-full w-full object-cover" muted loop playsinline preload="none" aria-hidden="true" data-inview-video data-src="<?php echo esc_url($factory['video']); ?>"<?php if ($factory['video_mobile'] !== '') : ?> data-src-mobile="<?php echo esc_url($factory['video_mobile']); ?>"<?php endif; ?>></video>
                <?php endif; ?>
                <span class="absolute inset-0 -z-10 bg-[linear-gradient(180deg,rgba(8,35,74,0.12)_0%,rgba(8,35,74,0.62)_36%,rgba(8,35,74,0.92)_100%)] lg:bg-[linear-gradient(90deg,rgba(8,35,74,0.92)_0%,rgba(8,35,74,0.6)_46%,rgba(8,35,74,0.08)_100%)]"></span>
                <h3 id="home-factory-title" class="max-w-[24ch] text-[clamp(1.625rem,2.3vw,2.625rem)] font-black leading-[1.08] tracking-[-0.015em]"><?php echo esc_html($factory['title']); ?></h3>
                <?php if ($factory['text'] !== '') : ?><p class="mt-4 max-w-[520px] text-sm font-semibold leading-7 text-white/80 md:text-base"><?php echo esc_html($factory['text']); ?></p><?php endif; ?>
                <a class="mt-7 inline-flex h-12 w-fit items-center justify-center gap-2 rounded-sm bg-white px-6 text-sm font-black text-navy shadow-card transition hover:-translate-y-0.5 hover:bg-blue-50" href="<?php echo esc_url(boblift_url($factory['cta_href'])); ?>"><?php echo esc_html($factory['cta_label']); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
            </div>
        </div>
    </div>
    <?php
}

function boblift_render_home_cases(): void
{
    $cases = array_values(array_filter(boblift_cases(), static fn(array $item): bool => boblift_normalize_case_kind((string) ($item['case_kind'] ?? '')) === 'scene_case'));
    if ($cases === []) {
        return;
    }
    $ui = boblift_site_ui('casesPage');
    ?>
    <section id="cases" class="bg-[linear-gradient(180deg,#ffffff_0%,#f8fbff_100%)] py-10 md:py-16" aria-labelledby="home-cases-title">
        <div class="page-shell">
            <div class="mb-6 flex flex-col gap-4 md:mb-8 md:flex-row md:items-end md:justify-between">
                <div class="min-w-0">
                    <p class="section-label mb-3"><?php echo esc_html($ui['heroLabel'] ?? 'Case Center'); ?></p>
                    <h2 id="home-cases-title" class="max-w-[22ch] text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em] text-ink"><?php echo esc_html($ui['heroTitle'] ?? 'Project stories with real working conditions.'); ?></h2>
                </div>
                <a class="inline-flex w-fit shrink-0 items-center justify-center gap-1.5 whitespace-nowrap rounded-sm bg-navy px-5 py-2.5 text-xs font-black text-white shadow-card transition hover:-translate-y-0.5 hover:bg-[#0b2d5b] md:px-6 md:py-3 md:text-sm" href="<?php echo esc_url(boblift_case_archive_url()); ?>"><?php echo esc_html($ui['secondaryCtaLabel'] ?? 'View Cases'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
            </div>
            <div class="-mx-4 flex snap-x snap-mandatory scroll-px-4 gap-3 overflow-x-auto px-4 pb-2 [scrollbar-width:none] md:mx-0 md:grid md:snap-none md:grid-cols-3 md:gap-5 md:overflow-visible md:px-0 md:pb-0 [&::-webkit-scrollbar]:hidden">
                <?php foreach (array_slice($cases, 0, 3) as $item) :
                    $meta = array_values(array_filter([trim((string) ($item['country'] ?? '')), trim((string) ($item['application'] ?? ''))]));
                ?>
                    <a class="group flex w-[82%] shrink-0 snap-start flex-col overflow-hidden rounded border border-line bg-white shadow-card transition duration-300 hover:-translate-y-1 hover:border-blue-300 md:w-auto" href="<?php echo esc_url($item['url']); ?>">
                        <span class="relative block aspect-[4/3] overflow-hidden bg-[#eef5ff]"><img class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]" src="<?php echo esc_url(boblift_item_image($item)); ?>" alt="<?php echo esc_attr($item['title']); ?>" loading="lazy" decoding="async"></span>
                        <span class="flex flex-1 flex-col p-5 lg:p-6">
                            <?php if ($meta !== []) : ?><span class="mb-2 text-[11px] font-black uppercase tracking-[0.14em] text-slate-400"><?php echo esc_html(implode(' · ', $meta)); ?></span><?php endif; ?>
                            <span class="line-clamp-2 text-lg font-black leading-tight tracking-[-0.01em] text-ink lg:text-xl"><?php echo esc_html($item['title']); ?></span>
                            <?php if (trim((string) ($item['summary'] ?? '')) !== '') : ?><span class="mt-2.5 line-clamp-3 text-sm font-semibold leading-6 text-slate-600"><?php echo esc_html($item['summary']); ?></span><?php endif; ?>
                            <span class="mt-auto inline-flex items-center gap-1.5 pt-4 text-sm font-black text-blue-600"><?php echo esc_html($ui['viewCaseLabel'] ?? 'View Case'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4 transition group-hover:translate-x-1'); ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
}
