<?php
if (!defined('ABSPATH')) {
    exit;
}

// Factory band content comes from the About page settings, so edits there reach the home page too.
function boblift_home_factory_content(): array
{
    $about = boblift_about_page_options();
    $gallery = is_array($about['manufacturing_gallery'] ?? null) ? $about['manufacturing_gallery'] : [];
    $logo = is_array($about['logo'] ?? null) ? $about['logo'] : [];
    $visits = is_array($about['customer_visits'] ?? null) ? $about['customer_visits'] : [];
    $language = function_exists('boblift_current_language_for_data') ? boblift_current_language_for_data() : '';

    // The About page ships these English defaults to every language; translate them until they are edited in admin.
    $translations = [
        'zh' => [
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

    $poster = boblift_image_setting_url($logo['aerial_start_image'] ?? [], 'images/about/about-aerial-mid-start.webp');
    $video_setting = is_array($logo['aerial_video'] ?? null) ? $logo['aerial_video'] : ['id' => 0, 'path' => 'videos/about/about-aerial-mid.mp4'];
    $video = (int) ($video_setting['id'] ?? 0) > 0 ? (string) wp_get_attachment_url((int) $video_setting['id']) : '';
    if ($video === '' && trim((string) ($video_setting['path'] ?? '')) !== '') {
        $video = boblift_asset((string) $video_setting['path']);
    }

    // Skip photos the page already shows: the video poster and the About section gallery.
    $shown = [$poster];
    foreach ((array) (boblift_options()['home']['about']['gallery'] ?? []) as $item) {
        if (is_array($item) && trim((string) ($item['src'] ?? '')) !== '') {
            $shown[] = boblift_image_src((string) $item['src']);
        }
    }
    $tiles = [];
    foreach ((array) ($gallery['items'] ?? []) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $image = boblift_image_setting_url($item['image'] ?? [], '');
        if ($image === '' || in_array($image, $shown, true)) {
            continue;
        }
        $shown[] = $image;
        $tiles[] = [
            'image' => $image,
            'title' => $translate((string) ($item['title'] ?? '')),
            'text' => $translate((string) ($item['text'] ?? '')),
        ];
    }

    return [
        'label' => $translate((string) ($gallery['label'] ?? 'Modern Manufacturing Base')),
        'title' => $translate((string) ($gallery['title'] ?? 'Intelligent Production.')),
        'text' => $translate((string) ($gallery['text'] ?? '')),
        'poster' => $poster,
        'video' => $video,
        'cta_label' => (string) ($visits['cta_label'] ?? 'Plan a Visit'),
        'cta_href' => (string) ($visits['cta_href'] ?? '/contact.html'),
        'tiles' => array_slice($tiles, 0, 2),
    ];
}

function boblift_render_home_factory(): void
{
    $factory = boblift_home_factory_content();
    ?>
    <section id="factory" class="bg-white pb-10 md:pb-16" aria-labelledby="home-factory-title">
        <div class="page-shell grid gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-[minmax(0,1.65fr)_minmax(0,1fr)] lg:gap-5">
            <div class="relative isolate flex min-h-[440px] flex-col justify-end overflow-hidden rounded bg-navy p-6 text-white sm:col-span-2 sm:min-h-[500px] sm:p-10 lg:col-span-1 lg:row-span-2 lg:min-h-[600px] lg:p-12">
                <img class="absolute inset-0 -z-30 h-full w-full object-cover" alt="" decoding="async" data-inview-src="<?php echo esc_url($factory['poster']); ?>">
                <?php if ($factory['video'] !== '') : ?>
                    <video class="absolute inset-0 -z-20 h-full w-full object-cover" muted loop playsinline preload="none" aria-hidden="true" data-inview-video data-src="<?php echo esc_url($factory['video']); ?>"></video>
                <?php endif; ?>
                <span class="absolute inset-0 -z-10 bg-[linear-gradient(180deg,rgba(8,35,74,0.12)_0%,rgba(8,35,74,0.62)_36%,rgba(8,35,74,0.92)_100%)] lg:bg-[linear-gradient(90deg,rgba(8,35,74,0.92)_0%,rgba(8,35,74,0.6)_46%,rgba(8,35,74,0.08)_100%)]"></span>
                <p class="section-label mb-3"><?php echo esc_html($factory['label']); ?></p>
                <h2 id="home-factory-title" class="max-w-[16ch] text-[clamp(32px,8.5vw,40px)] font-black leading-[1.02] tracking-[-0.01em] sm:text-[52px] lg:text-[60px]"><?php echo esc_html($factory['title']); ?></h2>
                <?php if ($factory['text'] !== '') : ?><p class="mt-4 max-w-[520px] text-sm font-semibold leading-7 text-white/80 md:text-base"><?php echo esc_html($factory['text']); ?></p><?php endif; ?>
                <a class="mt-7 inline-flex h-12 w-fit items-center justify-center gap-2 rounded-sm bg-white px-6 text-sm font-black text-navy shadow-card transition hover:-translate-y-0.5 hover:bg-blue-50" href="<?php echo esc_url(boblift_url($factory['cta_href'])); ?>"><?php echo esc_html($factory['cta_label']); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
            </div>
            <?php foreach ($factory['tiles'] as $index => $tile) : ?>
                <figure class="group relative min-h-[220px] overflow-hidden rounded bg-[#e8eef5] sm:min-h-[260px] lg:min-h-0 ">
                    <img class="absolute inset-0 h-full w-full object-cover transition duration-700 group-hover:scale-[1.04]" src="<?php echo esc_url($tile['image']); ?>" alt="<?php echo esc_attr($tile['title']); ?>" loading="lazy" decoding="async">
                    <span class="absolute inset-0 bg-[linear-gradient(180deg,rgba(8,35,74,0)_30%,rgba(8,35,74,0.85)_100%)]"></span>
                    <figcaption class="absolute inset-x-0 bottom-0 p-5 text-white sm:p-6">
                        <span class="text-[11px] font-black tracking-[0.22em] text-white/65"><?php echo esc_html(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></span>
                        <strong class="mt-2 block text-xl font-black leading-tight sm:text-2xl"><?php echo esc_html($tile['title']); ?></strong>
                        <?php if ($tile['text'] !== '') : ?><span class="mt-1.5 block text-sm font-semibold leading-6 text-white/80"><?php echo esc_html($tile['text']); ?></span><?php endif; ?>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    </section>
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
                    <h2 id="home-cases-title" class="max-w-[22ch] text-[clamp(30px,7.5vw,38px)] font-black leading-[1.02] tracking-[-0.01em] text-ink sm:text-[48px] lg:text-[52px]"><?php echo esc_html($ui['heroTitle'] ?? 'Project stories with real working conditions.'); ?></h2>
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
