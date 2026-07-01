<?php
/**
 * Template Name: Privacy Policy
 */
get_header();
$page_options = function_exists('boblift_privacy_policy_page_options') ? boblift_privacy_policy_page_options() : [];
$sections = is_array($page_options['sections'] ?? null) ? array_values(array_filter($page_options['sections'], static fn($item): bool => is_array($item))) : [];
?>
<main class="bg-white text-ink">
    <section class="relative overflow-hidden border-b border-line bg-[linear-gradient(135deg,#f8fbff_0%,#ffffff_58%,#edf5ff_100%)]">
        <div class="page-shell py-14 md:py-20 2xl:py-24">
            <p class="section-label mb-5"><?php echo esc_html((string) ($page_options['label'] ?? 'BOB-LIFT Policy')); ?></p>
            <h1 class="max-w-5xl text-[clamp(38px,6vw,78px)] font-black leading-[1.02] tracking-[-0.06em] text-navy"><?php echo esc_html((string) ($page_options['title'] ?? 'Privacy Policy')); ?></h1>
            <p class="mt-6 max-w-3xl text-base font-semibold leading-8 text-slate-600 md:text-lg"><?php echo esc_html((string) ($page_options['description'] ?? 'This page explains how BOB-LIFT handles contact, inquiry and website usage information submitted through this website.')); ?></p>
            <p class="mt-5 text-sm font-black uppercase tracking-[0.14em] text-slate-400"><?php echo esc_html((string) ($page_options['updated_label'] ?? 'Last updated:')); ?> <?php echo esc_html((string) ($page_options['updated'] ?? '2026-06-29')); ?></p>
        </div>
    </section>

    <section class="page-shell py-12 md:py-16 2xl:py-20">
        <div class="grid gap-8 lg:grid-cols-[0.72fr_1.28fr] lg:gap-12">
            <aside class="h-fit rounded-[28px] border border-blue-100 bg-blue-50/45 p-6 shadow-[0_16px_42px_rgba(8,35,74,0.06)]">
                <h2 class="text-xl font-black tracking-[-0.03em] text-navy"><?php echo esc_html((string) ($page_options['side_title'] ?? 'Scope')); ?></h2>
                <p class="mt-3 text-sm font-semibold leading-7 text-slate-600"><?php echo esc_html((string) ($page_options['side_text'] ?? 'This policy applies to BOB-LIFT website forms, catalog requests, quote requests and direct business communication initiated through the site.')); ?></p>
            </aside>
            <div class="grid gap-5">
                <?php foreach ($sections as $section) : ?>
                    <article class="rounded-[28px] border border-line bg-white p-6 shadow-[0_14px_34px_rgba(8,35,74,0.045)] md:p-8">
                        <h2 class="text-2xl font-black tracking-[-0.035em] text-navy"><?php echo esc_html((string) ($section['title'] ?? '')); ?></h2>
                        <p class="mt-4 text-base font-semibold leading-8 text-slate-600"><?php echo esc_html((string) ($section['text'] ?? '')); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>
<?php get_footer(); ?>
