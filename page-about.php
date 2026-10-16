<?php
get_header();
$about_page = boblift_about_page_options();
$logo = $about_page['logo'] ?? [];
$strength = $about_page['strength'] ?? [];
$timeline = $about_page['timeline'] ?? [];
$executives = $about_page['executives'] ?? [];
$proof_chips = $logo['proof_chips'] ?? [];
$manufacturing_image = $strength['image'] ?? boblift_image_option('images/about/about-logo-prototype-crane-ai.png');
$manufacturing_cards = $strength['cards'] ?? [];
$timeline_items = $timeline['items'] ?? [];
$executive_members = $executives['members'] ?? [];
$executive_group_image = $executives['group_image'] ?? boblift_image_option('images/about/quanyuan-compressed.jpg');
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
                <div class="about-logo-fill" data-about-logo-text>BOB-LIFT</div>
            </div>
            <div class="about-scroll-copy relative z-10 mx-auto w-full max-w-[1500px] overflow-hidden rounded-[34px] border border-white/80 bg-white/48 px-5 py-6 text-center shadow-[0_24px_70px_rgba(11,27,51,0.08)] backdrop-blur-xl md:px-8 md:py-7 xl:px-10" data-about-copy>
                <p class="section-label mb-4"><?php echo esc_html($logo['label'] ?? 'About BOB-LIFT'); ?></p>
                <h1 class="whitespace-nowrap text-[clamp(24px,3.35vw,64px)] font-black leading-[1.08] tracking-[-0.04em] text-[#0B1B33]"><?php echo esc_html($logo['headline'] ?? 'Engineering Strength Behind Every Lift.'); ?></h1>
                <p class="mx-auto mt-5 max-w-[760px] text-sm font-semibold leading-7 text-[#64748B] md:text-base md:leading-8"><?php echo esc_html($logo['paragraph'] ?? 'BOB-LIFT integrates crane manufacturing, engineering expertise, quality control, and global service capability to deliver reliable lifting solutions for demanding projects worldwide.'); ?></p>
                <div class="about-proof-chips mt-7 grid gap-3 md:grid-cols-3" data-about-chips>
                    <?php foreach ($proof_chips as $chip) : ?>
                        <div class="about-proof-chip rounded-full border border-white/70 bg-white/72 px-5 py-3 text-start shadow-[0_18px_50px_rgba(11,27,51,0.08)] backdrop-blur-xl">
                            <span class="text-xs font-black tracking-[0.22em] text-[#2563EB]"><?php echo esc_html($chip['number'] ?? ''); ?></span>
                            <span class="ms-3 text-sm font-black text-[#0B1B33]"><?php echo esc_html($chip['label'] ?? ''); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="about-strength" class="about-manufacturing-section bg-[#F8FAFC] py-12 lg:py-20">
    <div class="page-shell">
        <div class="about-manufacturing-card overflow-hidden rounded-[36px] border border-[#E5E7EB] bg-white shadow-[0_24px_90px_rgba(11,27,51,0.07)]">
            <div class="grid lg:grid-cols-[1.05fr_0.95fr]">
                <div class="about-manufacturing-media relative min-h-[360px] overflow-hidden bg-[#eef4fb] lg:min-h-[560px]">
                    <?php echo boblift_img_from_setting($manufacturing_image, 'images/about/about-logo-prototype-crane-ai.png', 'BOB-LIFT truck mounted crane manufacturing scene', 'absolute inset-0 h-full w-full object-cover object-center', ['loading' => 'lazy', 'decoding' => 'async']); ?>
                    <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(255,255,255,0)_58%,rgba(11,27,51,0.08)_100%)]"></div>
                </div>
                <div class="about-manufacturing-copy flex flex-col justify-center p-6 md:p-9 lg:p-12 2xl:p-14">
                    <p class="section-label mb-5"><?php echo esc_html($strength['label'] ?? 'Company Strength'); ?></p>
                    <h2 class="max-w-[720px] text-[clamp(34px,5vw,64px)] font-black leading-[1.08] tracking-[-0.045em] text-[#0B1B33]"><?php echo esc_html($strength['title'] ?? 'Manufacturing intelligence for global lifting projects.'); ?></h2>
                    <p class="mt-6 max-w-[680px] text-sm font-semibold leading-7 text-[#64748B] md:text-base md:leading-8"><?php echo esc_html($strength['text'] ?? 'BOB-LIFT combines product engineering, precision production and responsive export service into one disciplined workflow for overseas B2B buyers.'); ?></p>
                    <a class="mt-8 inline-flex h-[52px] w-fit items-center justify-center gap-3 rounded-full bg-[#0B3F7D] px-7 text-sm font-black text-white shadow-[0_18px_42px_rgba(11,63,125,0.18)] transition hover:-translate-y-0.5 hover:bg-[#082f5f]" href="<?php echo esc_url(boblift_url((string) ($strength['cta_href'] ?? '/contact'))); ?>"><?php echo esc_html($strength['cta_label'] ?? 'Talk to Our Team'); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
                </div>
            </div>
            <div class="grid border-t border-[#E5E7EB] md:grid-cols-2 xl:grid-cols-4">
                <?php foreach ($manufacturing_cards as $index => $card) : ?>
                    <article class="about-manufacturing-capability border-b border-[#E5E7EB] p-6 last:border-b-0 md:[&:nth-child(2n)]:border-e-0 md:border-e xl:border-b-0 xl:[&:nth-child(2n)]:border-e xl:last:border-e-0 lg:p-8">
                        <span class="text-xs font-black tracking-[0.22em] text-[#2563EB]"><?php echo esc_html(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></span>
                        <h3 class="mt-5 text-xl font-black tracking-[-0.04em] text-[#0B1B33]"><?php echo esc_html($card['title'] ?? ''); ?></h3>
                        <p class="mt-3 text-sm font-semibold leading-6 text-[#64748B]"><?php echo esc_html($card['text'] ?? ''); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<section class="bg-[#fbfdff] pb-4 lg:pb-6">
    <div class="page-shell">
        <div class="relative overflow-hidden rounded-[34px] border border-line/80 bg-white p-5 shadow-[0_24px_90px_rgba(8,35,74,0.07)] lg:p-8">
            <div class="pointer-events-none absolute -end-24 -top-24 h-72 w-72 rounded-full bg-blue-500/8 blur-3xl"></div>
            <div class="relative z-10 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="section-label mb-4"><?php echo esc_html($timeline['label'] ?? 'Development Timeline'); ?></p>
                    <h2 class="max-w-none text-[clamp(32px,4vw,54px)] font-black leading-[1.08] tracking-[-0.04em] text-ink lg:whitespace-nowrap"><?php echo esc_html($timeline['title'] ?? 'Built step by step for global lifting projects.'); ?></h2>
                </div>
            </div>
            <div class="relative z-10 mt-9">
                <div class="absolute start-[8%] end-[8%] top-5 hidden h-px bg-[linear-gradient(90deg,transparent,#c9d6e8_12%,#c9d6e8_88%,transparent)] lg:block"></div>
                <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                    <?php foreach ($timeline_items as $index => $item) : $year = (string) ($item['year'] ?? ''); $title = (string) ($item['title'] ?? ''); ?>
                        <article class="relative pt-12">
                            <span class="absolute start-5 top-0 hidden size-10 place-items-center rounded-full border border-gold/35 bg-white text-sm font-black text-gold shadow-[0_14px_36px_rgba(8,35,74,0.08)] lg:grid"><?php echo esc_html(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></span>
                            <div class="h-full overflow-hidden rounded-[26px] border border-line/80 bg-[linear-gradient(180deg,#ffffff_0%,#f8fbff_100%)] shadow-[0_18px_60px_rgba(8,35,74,0.055)]">
                                <div class="relative h-32 overflow-hidden bg-[#eef5ff]">
                                    <?php echo boblift_img_from_setting($item['image'] ?? [], 'images/factory-workshop.jpg', $title, 'absolute inset-0 h-full w-full object-cover'); ?>
                                    <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(255,255,255,0)_42%,rgba(8,35,74,0.18)_100%)]"></div>
                                    <span class="absolute start-4 top-4 rounded-full bg-white/88 px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-gold shadow-[0_10px_24px_rgba(8,35,74,0.08)] lg:hidden"><?php echo esc_html($about_ui['stepLabel'] ?? 'Step'); ?> <?php echo esc_html((string) ($index + 1)); ?></span>
                                </div>
                                <div class="p-5">
                                    <strong class="block text-[34px] font-black leading-[1.02] tracking-[-0.035em] text-ink"><?php echo esc_html($year); ?></strong>
                                    <h3 class="mt-3 text-base font-black leading-5 text-ink"><?php echo esc_html($title); ?></h3>
                                    <p class="mt-3 text-sm font-semibold leading-6 text-slate-500"><?php echo esc_html($item['text'] ?? 'A key milestone in BOB-LIFT development.'); ?></p>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>


<section id="about-leadership" class="about-executive-section relative overflow-visible bg-[#F8FAFC] pb-12 pt-0 lg:pb-0 lg:pt-0" data-about-executive>
    <div class="about-executive-pin flex min-h-screen items-start pt-6 lg:pt-8" data-executive-pin>
        <div class="page-shell">
            <div class="about-executive-flip relative" data-executive-flip>
                <div class="about-executive-card relative overflow-hidden rounded-[42px] border border-[#E5E7EB] bg-white p-5 shadow-[0_34px_120px_rgba(11,27,51,0.10)] md:p-7 lg:p-8 2xl:p-10" data-executive-card>
                <div class="about-executive-aura pointer-events-none absolute inset-0"></div>
                <div class="about-executive-watermark pointer-events-none absolute -end-10 top-4 text-[clamp(72px,12vw,230px)] font-black leading-none tracking-[-0.11em] text-[#0B3F7D]/[0.055]" data-executive-watermark><?php echo esc_html($about_ui['leadershipWatermark'] ?? 'LEADERSHIP'); ?></div>
                <div class="relative z-10 mb-6 grid gap-6 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)] lg:items-end">
                    <div data-executive-copy-item>
                        <p class="mb-4 text-xs font-black uppercase tracking-[0.42em] text-[#0B3F7D]"><?php echo esc_html($executives['label'] ?? 'Executive Leadership'); ?></p>
                        <h2 class="max-w-none whitespace-nowrap text-[clamp(34px,4.2vw,72px)] font-black leading-[1.02] tracking-[-0.045em] text-[#0B1B33]"><?php echo esc_html($executives['title'] ?? 'Leadership Team'); ?></h2>
                    </div>
                </div>
                <div class="about-executive-grid relative z-10 grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:gap-5" data-executive-grid>
                    <?php foreach ($executive_members as $member) : ?>
                        <article class="about-executive-member group grid overflow-hidden rounded-[30px] border border-[#E5E7EB] bg-white shadow-[0_22px_70px_rgba(11,27,51,0.08)] md:grid-cols-[minmax(170px,0.72fr)_minmax(0,1fr)] xl:grid-cols-[minmax(190px,0.72fr)_minmax(0,1fr)]" data-executive-member>
                            <div class="relative aspect-square w-full overflow-hidden bg-[#EAF1F8]">
                                <?php echo boblift_img_from_setting($member['image'] ?? [], 'images/about/executives/executive-01-square.png', ($member['name'] ?? 'BOB-LIFT executive') . ' portrait', 'absolute inset-0 h-full w-full object-cover object-center transition duration-700 group-hover:scale-[1.035]', ['loading' => 'lazy', 'decoding' => 'async', 'data-executive-image' => '']); ?>
                            </div>
                            <div class="flex min-w-0 flex-col justify-center p-5 2xl:p-6">
                                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-[#2563EB]"><?php echo esc_html($member['role'] ?? ''); ?></p>
                                <h3 class="mt-3 text-[clamp(24px,1.75vw,36px)] font-black leading-[1.04] tracking-[-0.035em] text-[#0B1B33]"><?php echo esc_html($member['name'] ?? ''); ?></h3>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                </div>
                <div class="about-executive-photo absolute inset-0 flex flex-col overflow-hidden rounded-[42px] border border-[#DDE7F2] bg-white shadow-[0_34px_120px_rgba(11,27,51,0.12)]" data-executive-photo aria-hidden="true">
                    <div class="relative z-10 shrink-0 px-5 pb-4 pt-5 md:px-7 md:pt-7 lg:px-8 lg:pt-8 2xl:px-10 2xl:pt-10">
                        <p class="mb-4 text-xs font-black uppercase tracking-[0.42em] text-[#0B3F7D]"><?php echo esc_html($executives['label'] ?? 'Executive Leadership'); ?></p>
                        <h2 class="max-w-none whitespace-nowrap text-[clamp(34px,4.2vw,72px)] font-black leading-[1.02] tracking-[-0.045em] text-[#0B1B33]"><?php echo esc_html($executives['title'] ?? 'Leadership Team'); ?></h2>
                    </div>
                    <div class="relative min-h-0 flex-1 bg-[#F8FAFC] px-4 pb-5 md:px-7 md:pb-7 lg:px-8 lg:pb-8 2xl:px-10 2xl:pb-10">
                        <?php echo boblift_img_from_setting($executive_group_image, 'images/about/quanyuan-compressed.jpg', 'BOB-LIFT leadership and factory team group photo', 'h-full w-full object-contain object-center', ['loading' => 'lazy', 'decoding' => 'async']); ?>
                    </div>
                    <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(180deg,rgba(248,250,252,0.04)_0%,rgba(11,27,51,0.035)_100%)]"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>
