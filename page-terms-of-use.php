<?php
/**
 * Template Name: Terms of Use
 */
get_header();
$page_options = function_exists('boblift_terms_of_use_page_options') ? boblift_terms_of_use_page_options() : [];
$sections = is_array($page_options['sections'] ?? null) ? array_values(array_filter($page_options['sections'], static fn($item): bool => is_array($item))) : [];
?>
<main class="bg-white text-ink">
    <div class="page-shell pt-5 md:pt-8">
        <?php
        boblift_page_hero([
            'label' => (string) ($page_options['label'] ?? 'BOB-LIFT Website'),
            'title' => (string) ($page_options['title'] ?? 'Terms of Use'),
            'text' => (string) ($page_options['description'] ?? 'These terms explain the basic conditions for using BOB-LIFT website information, product materials and inquiry services.'),
            'meta' => trim((string) ($page_options['updated_label'] ?? 'Last updated:') . ' ' . (string) ($page_options['updated'] ?? '2026-06-29')),
        ]);
        ?>
    </div>

    <section class="page-shell pb-12 pt-8 md:pb-16 md:pt-10 2xl:pb-20">
        <div class="grid gap-5 md:grid-cols-2">
            <?php foreach ($sections as $section) : ?>
                <article class="rounded border border-line bg-white p-6 shadow-card md:p-8">
                    <h2 class="text-2xl font-black tracking-[-0.01em] text-navy"><?php echo esc_html((string) ($section['title'] ?? '')); ?></h2>
                    <p class="mt-4 text-base font-semibold leading-8 text-slate-600"><?php echo esc_html((string) ($section['text'] ?? '')); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>
<?php get_footer(); ?>
