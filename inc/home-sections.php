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
                <?php if ($factory['text'] !== '') : ?><p class="mt-4 max-w-[520px] text-sm font-normal leading-7 text-white/80 md:text-base"><?php echo esc_html($factory['text']); ?></p><?php endif; ?>
                <div class="mt-7 flex flex-wrap items-center gap-x-6 gap-y-3">
                    <a class="inline-flex h-12 w-fit items-center justify-center gap-2 rounded-sm bg-white px-6 text-sm font-black text-navy shadow-card transition hover:-translate-y-0.5 hover:bg-blue-50" href="<?php echo esc_url(boblift_url($factory['cta_href'])); ?>"><?php echo esc_html($factory['cta_label']); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
                    <a class="inline-flex min-h-11 items-center gap-2 text-sm font-black text-white transition hover:text-blue-100" href="<?php echo esc_url(boblift_url('/videos.html')); ?>"><?php echo boblift_icon('circle-play', 'h-5 w-5'); ?><?php echo esc_html(boblift_localized_static_text('Watch factory videos')); ?></a>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// Buyers' questions with confirmed answers only (warranty and minimum order apply to cranes), plus FAQPage data.
function boblift_render_home_faq(): void
{
    $t = static fn(string $text): string => boblift_localized_static_text($text);
    $items = [
        ['What warranty do BOB-LIFT cranes carry?', 'Every BOB-LIFT crane carries a 12-month warranty.', null],
        ['What is the minimum order?', 'One unit: you can order a single crane.', null],
        ['Which certificates do you hold?', 'BOB-LIFT holds CE certificates for its cranes, the EAC certificate of conformity, and ISO 9001 and ISO 14001 management certificates. You can view the scans on our About page.', ['All certificates and patents', boblift_url('/about.html#about-certifications')]],
        ['Can I visit the factory before ordering?', 'Yes. Our own 37,000 m² factory is in Xuzhou, Jiangsu, China. Contact us to arrange a visit.', null],
        ['Can the crane be matched to my truck or vessel?', 'Yes. Tell us your truck chassis or deck layout and the loads you lift, and we configure the crane for it. We can also mount the crane on the truck and arrange export shipping.', null],
        ['Which countries do you export to?', 'BOB-LIFT cranes have been exported to more than 120 countries.', null],
    ];
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(static fn(array $item): array => ['@type' => 'Question', 'name' => $t($item[0]), 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $t($item[1])]], $items),
    ];
    ?>
    <section id="faq" class="border-t border-line bg-white py-10 md:py-14" aria-labelledby="home-faq-title">
        <div class="page-shell grid gap-6 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)] lg:gap-14">
            <div>
                <p class="section-label mb-3"><?php echo esc_html($t('FAQ')); ?></p>
                <h2 id="home-faq-title" class="text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em] text-ink [text-wrap:balance] [word-break:auto-phrase]"><?php echo esc_html($t('Questions buyers ask before ordering.')); ?></h2>
            </div>
            <div class="divide-y divide-line border-y border-line">
                <?php foreach ($items as $item) : ?>
                    <details class="group">
                        <summary class="flex min-h-14 cursor-pointer list-none items-center justify-between gap-4 py-3 text-base font-black leading-snug text-ink transition hover:text-blue-700 [&::-webkit-details-marker]:hidden"><?php echo esc_html($t($item[0])); ?><?php echo boblift_icon('chevron-down', 'h-5 w-5 shrink-0 text-slate-500 transition group-open:rotate-180'); ?></summary>
                        <div class="pb-5 pe-9 text-[15px] leading-7 text-slate-600 md:text-base">
                            <p><?php echo esc_html($t($item[1])); ?></p>
                            <?php if ($item[2]) : ?><a class="mt-2 inline-flex min-h-11 items-center gap-1.5 text-sm font-black text-blue-700 hover:text-[#071f42]" href="<?php echo esc_url($item[2][1]); ?>"><?php echo esc_html($t($item[2][0])); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4 rtl:-scale-x-100'); ?></a><?php endif; ?>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>
        <script type="application/ld+json"><?php echo wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    </section>
    <?php
}

// Customer feedback: photos and videos buyers sent back, one row of six, each from a different country where possible.
function boblift_render_home_feedback(): void
{
    // Six entries with a cover, different countries first, then the next ones in order. Building an entry reads all of
    // its media, so only the entries that can still be picked are built: the country is read first (the query primed
    // every post's meta) and an entry whose country is already shown is passed over in the first round.
    $posts = get_posts(['post_type' => 'feedback', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'], 'lang' => '']);
    // Featured images in one query rather than one per built entry.
    _prime_post_caches(array_values(array_filter(array_map(static fn(WP_Post $post): int => (int) get_post_thumbnail_id($post), $posts))), false, true);
    $built = [];
    $item_at = static function (int $index) use ($posts, &$built): ?array {
        if (!array_key_exists($index, $built)) {
            $item = boblift_feedback_item($posts[$index]);
            $built[$index] = $item && (string) ($item['cover'] ?? '') !== '' ? $item : null;
        }
        return $built[$index];
    };
    $picked = [];
    $countries = [];
    foreach (array_keys($posts) as $index) {
        if (count($picked) >= 6) {
            break;
        }
        if (isset($countries[boblift_feedback_text($posts[$index]->ID, '_feedback_country')])) {
            continue;
        }
        $item = $item_at($index);
        if ($item !== null) {
            $picked[$index] = $item;
            $countries[(string) $item['country']] = true;
        }
    }
    foreach (array_keys($posts) as $index) {
        if (count($picked) >= 6) {
            break;
        }
        if (!isset($picked[$index]) && ($item = $item_at($index)) !== null) {
            $picked[$index] = $item;
        }
    }
    if ($picked === []) {
        return;
    }
    $ui = boblift_site_ui('casesPage');
    $all_url = add_query_arg('tab', 'customer_feedback', boblift_case_archive_url());
    ?>
    <section id="feedback" class="bg-white py-10 md:py-14" aria-labelledby="home-feedback-title">
        <div class="page-shell">
            <div class="mb-6 flex flex-col gap-4 md:mb-8 md:flex-row md:items-end md:justify-between">
                <div class="min-w-0">
                    <p class="section-label mb-3"><?php echo esc_html($ui['customerFeedbackLabel'] ?? 'Customer Feedback'); ?></p>
                    <h2 id="home-feedback-title" class="max-w-[24ch] text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em] text-ink [text-wrap:balance] [word-break:auto-phrase]"><?php echo esc_html(boblift_localized_static_text('Photos and videos sent back by our customers.')); ?></h2>
                </div>
                <a class="inline-flex w-fit shrink-0 items-center justify-center gap-1.5 whitespace-nowrap rounded-sm border border-line bg-white min-h-11 px-5 py-2.5 text-xs font-black md:min-h-0 text-ink transition hover:border-blue-300 hover:text-blue-700 md:px-6 md:py-3 md:text-sm" href="<?php echo esc_url($all_url); ?>"><?php echo esc_html(boblift_localized_static_text('All customer feedback')); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4 rtl:-scale-x-100'); ?></a>
            </div>
            <?php // Phones swipe through the photos; wider screens show the six in one row. ?>
            <div class="-mx-4 flex snap-x snap-mandatory scroll-px-4 gap-3 overflow-x-auto px-4 pb-2 [scrollbar-width:none] md:mx-0 md:grid md:snap-none md:grid-cols-3 md:gap-4 md:overflow-visible md:px-0 md:pb-0 lg:grid-cols-6 [&::-webkit-scrollbar]:hidden">
                <?php foreach ($picked as $item) :
                    $caption = implode(' · ', array_filter([(string) $item['model'], (string) $item['country']]));
                ?>
                    <a class="group flex w-[44%] shrink-0 snap-start flex-col overflow-hidden rounded border border-line bg-white shadow-card transition duration-300 hover:border-blue-300 md:w-auto" href="<?php echo esc_url($item['url']); ?>">
                        <span class="relative block aspect-[4/3] overflow-hidden bg-[#eef5ff]">
                            <img class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]" <?php echo boblift_img_src($item['cover'], '(min-width: 1024px) 17vw, (min-width: 768px) 33vw, 44vw'); ?> alt="<?php echo esc_attr($caption); ?>" loading="lazy" decoding="async">
                            <?php if (($item['media'][0]['type'] ?? '') === 'video') : ?><span class="absolute inset-0 m-auto grid size-11 place-items-center rounded-sm bg-white/90 text-blue-700 shadow-card"><?php echo boblift_icon('circle-play', 'h-6 w-6'); ?></span><?php endif; ?>
                        </span>
                        <span class="block px-3 py-3">
                            <?php if ($item['model'] !== '') : ?><span class="block truncate text-sm font-black text-ink"><?php echo esc_html($item['model']); ?></span><?php endif; ?>
                            <?php if ($item['country'] !== '') : ?><span class="mt-1 flex items-center gap-1 text-xs font-semibold text-slate-500"><?php echo boblift_icon('map-pin', 'h-3.5 w-3.5 shrink-0 text-blue-600'); ?><span class="truncate"><?php echo esc_html($item['country']); ?></span></span><?php endif; ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
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
                    <h2 id="home-cases-title" class="max-w-[22ch] text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em] text-ink [text-wrap:balance] [word-break:auto-phrase]"><?php echo esc_html($ui['heroTitle'] ?? 'Project stories with real working conditions.'); ?></h2>
                </div>
                <a class="inline-flex w-fit shrink-0 items-center justify-center gap-1.5 whitespace-nowrap rounded-sm bg-navy min-h-11 px-5 py-2.5 text-xs font-black md:min-h-0 text-white shadow-card transition hover:-translate-y-0.5 hover:bg-[#0b2d5b] md:px-6 md:py-3 md:text-sm" href="<?php echo esc_url(boblift_case_archive_url()); ?>"><?php echo esc_html($ui['secondaryCtaLabel'] ?? 'View Cases'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
            </div>
            <div class="-mx-4 flex snap-x snap-mandatory scroll-px-4 gap-3 overflow-x-auto px-4 pb-2 [scrollbar-width:none] md:mx-0 md:grid md:snap-none md:grid-cols-3 md:gap-5 md:overflow-visible md:px-0 md:pb-0 [&::-webkit-scrollbar]:hidden">
                <?php foreach (array_slice($cases, 0, 3) as $item) :
                    $meta = array_values(array_filter([trim((string) ($item['country'] ?? '')), trim((string) ($item['application'] ?? ''))]));
                ?>
                    <a class="group flex w-[82%] shrink-0 snap-start flex-col overflow-hidden rounded border border-line bg-white shadow-card transition duration-300 hover:-translate-y-1 hover:border-blue-300 md:w-auto" href="<?php echo esc_url($item['url']); ?>">
                        <span class="relative block aspect-[4/3] overflow-hidden bg-[#eef5ff]"><img class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]" <?php echo boblift_img_src(boblift_item_image($item), '(min-width: 768px) 33vw, 80vw'); ?> alt="<?php echo esc_attr($item['title']); ?>" loading="lazy" decoding="async"></span>
                        <span class="flex flex-1 flex-col p-5 lg:p-6">
                            <?php if ($meta !== []) : ?><span class="mb-2 text-[11px] font-black uppercase tracking-[0.14em] text-slate-500"><?php echo esc_html(implode(' · ', $meta)); ?></span><?php endif; ?>
                            <span class="line-clamp-2 text-lg font-black leading-tight tracking-[-0.01em] text-ink lg:text-xl"><?php echo esc_html($item['title']); ?></span>
                            <?php if (trim((string) ($item['summary'] ?? '')) !== '') : ?><span class="mt-2.5 line-clamp-3 text-[15px] font-normal leading-6 text-slate-600 lg:text-sm"><?php echo esc_html($item['summary']); ?></span><?php endif; ?>
                            <span class="mt-auto inline-flex items-center gap-1.5 pt-4 text-sm font-black text-blue-600"><?php echo esc_html($ui['viewCaseLabel'] ?? 'View Case'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4 transition group-hover:translate-x-1'); ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
}
