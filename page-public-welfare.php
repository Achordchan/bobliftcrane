<?php
get_header();
$page_options = function_exists('boblift_public_welfare_page_options') ? boblift_public_welfare_page_options() : [];
$hero = is_array($page_options['hero'] ?? null) ? $page_options['hero'] : [];
$statement = is_array($page_options['statement'] ?? null) ? $page_options['statement'] : [];
$focus_items = is_array($page_options['focus_items'] ?? null) ? array_values(array_filter($page_options['focus_items'], static fn($item): bool => is_array($item))) : [];
$cta = is_array($page_options['cta'] ?? null) ? $page_options['cta'] : [];
?>
<div class="page-shell pb-8 pt-5 md:pb-10 md:pt-8">
    <?php
    boblift_page_hero([
        'label' => (string) ($statement['label'] ?? 'Daily Responsibility'),
        'title' => (string) ($statement['title'] ?? 'Responsibility is not a slogan. It is our daily operation.'),
        'title_highlight' => (string) ($statement['highlight'] ?? 'daily operation'),
        'text' => (string) ($statement['text'] ?? 'From design and build, to how we work and communicate, BOB-LIFT stays focused on doing the right thing every day.'),
        'image' => boblift_image_setting_url($hero['image'] ?? [], 'images/public-welfare/public-welfare-hero-banner.webp'),
        'image_alt' => (string) ($hero['image_alt'] ?? 'BOB-LIFT engineers reviewing work in the factory'),
    ]);
    ?>
</div>

<section class="pb-12 lg:pb-20">
    <div class="page-shell">
        <div class="mx-auto grid max-w-[2200px] gap-6 lg:gap-8 xl:grid-cols-2">
            <?php foreach ($focus_items as $index => $item) : ?>
                <article class="grid overflow-hidden rounded border border-[#DDE7F2] bg-white shadow-card md:grid-cols-[minmax(280px,0.9fr)_minmax(0,1.1fr)] ">
                    <div class="relative min-h-[260px] overflow-hidden bg-[#EAF1F8] md:min-h-[340px] lg:min-h-[360px]">
                        <?php echo boblift_img_from_setting($item['image'] ?? [], 'images/factory-workshop.jpg', (string) ($item['alt'] ?? ''), 'absolute inset-0 h-full w-full object-cover object-center', ['loading' => $index === 0 ? 'eager' : 'lazy', 'decoding' => 'async']); ?>
                        <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(255,255,255,0)_54%,rgba(11,27,51,0.18)_100%)]"></div>
                    </div>
                    <div class="flex flex-col justify-center bg-[linear-gradient(135deg,#FFFFFF_0%,#F3F8FF_100%)] p-6 md:p-8 lg:p-10">
                        <div class="grid h-16 w-16 place-items-center rounded border border-blue-100 bg-white text-blue-700 shadow-card">
                            <?php echo boblift_icon((string) ($item['icon'] ?? 'check-circle'), 'h-8 w-8', '1.8'); ?>
                        </div>
                        <h3 class="mt-6 text-[clamp(26px,2.2vw,38px)] font-black leading-tight tracking-[-0.015em] text-[#0B1B33]"><?php echo esc_html((string) ($item['title'] ?? '')); ?></h3>
                        <p class="mt-5 max-w-[720px] text-sm font-semibold leading-7 text-[#64748B] md:text-base md:leading-8"><?php echo esc_html((string) ($item['text'] ?? '')); ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="bg-white pb-14 pt-2 lg:pb-20">
    <div class="page-shell">
        <div class="relative mx-auto max-w-[2200px] overflow-hidden rounded border border-[#0D4F94]/20 bg-[linear-gradient(135deg,#073A78_0%,#0B5AA8_58%,#084785_100%)] p-6 text-white shadow-card md:p-8 lg:p-12 2xl:p-14">
            <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-white/15 blur-3xl"></div>
            <div class="pointer-events-none absolute inset-0 opacity-[0.14] [background-image:radial-gradient(circle_at_1px_1px,rgba(255,255,255,0.9)_1px,transparent_0)] [background-size:28px_28px]"></div>
            <div class="relative z-10 grid gap-8 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center 2xl:gap-16">
                <div>
                    <span class="mb-6 block h-1 w-16 rounded-full bg-[#F5A23A]"></span>
                    <h2 class="max-w-[1280px] text-[clamp(34px,4.2vw,70px)] font-black leading-[1.04] tracking-[-0.015em]"><?php echo esc_html((string) ($cta['title'] ?? 'Building a safer future, together.')); ?></h2>
                    <p class="mt-5 max-w-[1120px] text-sm font-semibold leading-7 text-white/80 md:text-base md:leading-8"><?php echo esc_html((string) ($cta['text'] ?? 'We welcome partners who share practical values: safer work, responsible manufacturing and long-term cooperation.')); ?></p>
                </div>
                <a class="inline-flex h-[58px] w-fit shrink-0 items-center justify-center gap-3 rounded-sm border border-white/70 bg-white/8 px-7 text-sm font-black text-white backdrop-blur transition hover:-translate-y-0.5 hover:bg-white hover:text-[#0B3F7D] md:h-[64px] md:px-8 md:text-base" href="<?php echo esc_url(boblift_url((string) ($cta['button_href'] ?? '/contact.html'))); ?>">
                    <?php echo boblift_icon('mail', 'h-5 w-5'); ?><?php echo esc_html((string) ($cta['button_label'] ?? 'Contact us')); ?><?php echo boblift_icon('arrow-right', 'h-5 w-5'); ?>
                </a>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>
