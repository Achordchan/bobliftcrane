<?php
get_header();
$page_options = function_exists('boblift_public_welfare_page_options') ? boblift_public_welfare_page_options() : [];
$hero = is_array($page_options['hero'] ?? null) ? $page_options['hero'] : [];
$statement = is_array($page_options['statement'] ?? null) ? $page_options['statement'] : [];
$focus_items = is_array($page_options['focus_items'] ?? null) ? array_values(array_filter($page_options['focus_items'], static fn($item): bool => is_array($item))) : [];
$cta = is_array($page_options['cta'] ?? null) ? $page_options['cta'] : [];
$statement_title = (string) ($statement['title'] ?? 'Responsibility is not a slogan. It is our daily operation.');
$statement_highlight = trim((string) ($statement['highlight'] ?? 'daily operation'));
$statement_title_html = esc_html($statement_title);
if ($statement_highlight !== '' && str_contains($statement_title, $statement_highlight)) {
    $statement_title_html = str_replace(esc_html($statement_highlight), '<span class="text-blue-700">' . esc_html($statement_highlight) . '</span>', $statement_title_html);
}
?>
<section class="relative overflow-hidden bg-[#071f42]" aria-label="<?php echo esc_attr((string) ($hero['aria_label'] ?? 'Public Welfare')); ?>">
    <div class="relative h-[42vh] min-h-[360px] max-h-[620px] w-full">
        <?php echo boblift_img_from_setting($hero['image'] ?? [], 'images/public-welfare/public-welfare-hero-banner.webp', (string) ($hero['image_alt'] ?? 'BOB-LIFT public welfare factory visit banner'), 'absolute inset-0 h-full w-full object-cover object-center', ['loading' => 'eager', 'decoding' => 'async', 'fetchpriority' => 'high']); ?>
    </div>
</section>

<section class="relative overflow-hidden bg-white py-12 md:py-16 lg:py-24">
    <div class="pointer-events-none absolute -start-24 top-1/2 h-72 w-72 -translate-y-1/2 rounded-full bg-blue-500/10 blur-3xl"></div>
    <div class="pointer-events-none absolute -end-24 top-1/3 h-80 w-80 rounded-full bg-blue-500/8 blur-3xl"></div>
    <div class="page-shell relative">
        <div class="mx-auto max-w-[1600px] py-8 text-center md:py-10 lg:py-14">
            <p class="section-label mb-8 justify-center"><?php echo esc_html((string) ($statement['label'] ?? 'Daily Responsibility')); ?></p>
            <h2 class="boblift-welfare-statement-title mx-auto w-full font-black text-[#0B1B33]">
                <?php echo $statement_title_html; ?>
            </h2>
            <p class="mx-auto mt-9 max-w-[1600px] text-[clamp(18px,1.75vw,30px)] font-black leading-[1.35] tracking-[-0.025em] text-[#64748B] [text-wrap:balance]">
                <?php echo esc_html((string) ($statement['text'] ?? 'From design and build, to how we work and communicate, BOB-LIFT stays focused on doing the right thing every day.')); ?>
            </p>
        </div>
    </div>
</section>

<section class="bg-[#F8FAFC] pb-12 pt-4 lg:pb-20">
    <div class="page-shell">
        <div class="mx-auto grid max-w-[2200px] gap-6 lg:grid-cols-2 lg:gap-8">
            <?php foreach ($focus_items as $index => $item) : ?>
                <article class="grid overflow-hidden rounded-[30px] border border-[#DDE7F2] bg-white shadow-[0_22px_70px_rgba(11,27,51,0.055)] md:grid-cols-[minmax(280px,0.9fr)_minmax(0,1.1fr)] lg:rounded-[36px]">
                    <div class="relative min-h-[260px] overflow-hidden bg-[#EAF1F8] md:min-h-[340px] lg:min-h-[360px]">
                        <?php echo boblift_img_from_setting($item['image'] ?? [], 'images/factory-workshop.jpg', (string) ($item['alt'] ?? ''), 'absolute inset-0 h-full w-full object-cover object-center', ['loading' => $index === 0 ? 'eager' : 'lazy', 'decoding' => 'async']); ?>
                        <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(255,255,255,0)_54%,rgba(11,27,51,0.18)_100%)]"></div>
                    </div>
                    <div class="flex flex-col justify-center bg-[linear-gradient(135deg,#FFFFFF_0%,#F3F8FF_100%)] p-6 md:p-8 lg:p-10">
                        <div class="grid h-16 w-16 place-items-center rounded-[24px] border border-blue-100 bg-white text-blue-700 shadow-[0_14px_34px_rgba(8,35,74,0.07)]">
                            <?php echo boblift_icon((string) ($item['icon'] ?? 'check-circle'), 'h-8 w-8', '1.8'); ?>
                        </div>
                        <h3 class="mt-6 text-[clamp(26px,2.2vw,38px)] font-black leading-tight tracking-[-0.04em] text-[#0B1B33]"><?php echo esc_html((string) ($item['title'] ?? '')); ?></h3>
                        <p class="mt-5 max-w-[720px] text-sm font-semibold leading-7 text-[#64748B] md:text-base md:leading-8"><?php echo esc_html((string) ($item['text'] ?? '')); ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="bg-white pb-14 pt-2 lg:pb-20">
    <div class="page-shell">
        <div class="relative mx-auto max-w-[2200px] overflow-hidden rounded-[34px] border border-[#0D4F94]/20 bg-[linear-gradient(135deg,#073A78_0%,#0B5AA8_58%,#084785_100%)] p-6 text-white shadow-[0_28px_90px_rgba(11,63,125,0.22)] md:p-8 lg:rounded-[38px] lg:p-12 2xl:p-14">
            <div class="pointer-events-none absolute -end-24 -top-24 h-72 w-72 rounded-full bg-white/15 blur-3xl"></div>
            <div class="pointer-events-none absolute inset-0 opacity-[0.14] [background-image:radial-gradient(circle_at_1px_1px,rgba(255,255,255,0.9)_1px,transparent_0)] [background-size:28px_28px]"></div>
            <div class="relative z-10 grid gap-8 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center 2xl:gap-16">
                <div>
                    <span class="mb-6 block h-1 w-16 rounded-full bg-[#F5A23A]"></span>
                    <h2 class="max-w-[1280px] text-[clamp(34px,4.2vw,70px)] font-black leading-[1.04] tracking-[-0.05em]"><?php echo esc_html((string) ($cta['title'] ?? 'Building a safer future, together.')); ?></h2>
                    <p class="mt-5 max-w-[1120px] text-sm font-semibold leading-7 text-white/80 md:text-base md:leading-8"><?php echo esc_html((string) ($cta['text'] ?? 'We welcome partners who share practical values: safer work, responsible manufacturing and long-term cooperation.')); ?></p>
                </div>
                <a class="inline-flex h-[58px] w-fit shrink-0 items-center justify-center gap-3 rounded-[18px] border border-white/70 bg-white/8 px-7 text-sm font-black text-white backdrop-blur transition hover:-translate-y-0.5 hover:bg-white hover:text-[#0B3F7D] md:h-[64px] md:px-8 md:text-base" href="<?php echo esc_url(boblift_url((string) ($cta['button_href'] ?? '/contact.html'))); ?>">
                    <?php echo boblift_icon('mail', 'h-5 w-5'); ?><?php echo esc_html((string) ($cta['button_label'] ?? 'Contact us')); ?><?php echo boblift_icon('arrow-right', 'h-5 w-5'); ?>
                </a>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>
