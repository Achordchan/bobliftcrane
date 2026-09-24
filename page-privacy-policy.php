<?php
/**
 * Template Name: Privacy Policy
 */
get_header();
$page_options = function_exists('boblift_privacy_policy_page_options') ? boblift_privacy_policy_page_options() : [];
$sections = is_array($page_options['sections'] ?? null) ? array_values(array_filter($page_options['sections'], static fn($item): bool => is_array($item))) : [];
?>
<main class="bg-white text-ink">
    <div class="page-shell pt-5 md:pt-8">
        <?php
        boblift_page_hero([
            'label' => (string) ($page_options['label'] ?? 'BOB-LIFT Policy'),
            'title' => (string) ($page_options['title'] ?? 'Privacy Policy'),
            'text' => (string) ($page_options['description'] ?? 'This page explains how BOB-LIFT handles contact, inquiry and website usage information submitted through this website.'),
            'meta' => trim((string) ($page_options['updated_label'] ?? 'Last updated:') . ' ' . (string) ($page_options['updated'] ?? '2026-06-29')),
        ]);
        ?>
    </div>

    <section class="page-shell pb-12 pt-8 md:pb-16 md:pt-10 2xl:pb-20">
        <div class="grid gap-8 lg:grid-cols-[0.72fr_1.28fr] lg:gap-12">
            <aside class="h-fit rounded border border-blue-100 bg-blue-50/45 p-6 shadow-card">
                <h2 class="text-xl font-black tracking-[-0.01em] text-navy"><?php echo esc_html((string) ($page_options['side_title'] ?? 'Scope')); ?></h2>
                <p class="mt-3 text-sm font-semibold leading-7 text-slate-600"><?php echo esc_html((string) ($page_options['side_text'] ?? 'This policy applies to BOB-LIFT website forms, catalog requests, quote requests and direct business communication initiated through the site.')); ?></p>
            </aside>
            <div class="grid gap-5">
                <?php foreach ($sections as $section) : ?>
                    <article class="rounded border border-line bg-white p-6 shadow-card md:p-8">
                        <h2 class="text-2xl font-black tracking-[-0.01em] text-navy"><?php echo esc_html((string) ($section['title'] ?? '')); ?></h2>
                        <p class="mt-4 text-base font-semibold leading-8 text-slate-600"><?php echo esc_html((string) ($section['text'] ?? '')); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>
<?php get_footer(); ?>
