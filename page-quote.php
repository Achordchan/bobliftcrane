<?php
/**
 * Template Name: Quote
 */
get_header();
$product = isset($_GET['product']) ? sanitize_text_field(wp_unslash($_GET['product'])) : '';
$ui = boblift_site_ui('quotePage');
$workflow_items = is_array($ui['workflowItems'] ?? null) ? array_values(array_filter($ui['workflowItems'], static fn($item): bool => is_array($item))) : [];
?>
<div class="page-shell pt-5 md:pt-8">
    <?php boblift_page_hero(['label' => $ui['heroLabel'] ?? '', 'title' => $ui['heroTitle'] ?? '', 'text' => $ui['heroDescription'] ?? '', 'image' => (string) ($ui['heroImage'] ?? '')]); ?>
</div>
<section class="page-shell grid grid-cols-1 gap-8 py-10 md:py-16 lg:grid-cols-[0.72fr_1.28fr] lg:gap-12">
    <aside class="rounded border border-line bg-[linear-gradient(180deg,#071f42_0%,#0b3a78_100%)] p-6 text-white shadow-card md:p-8 lg:sticky lg:top-[calc(var(--boblift-header-h)+2rem)] lg:h-fit">
        <p class="text-[11px] font-black uppercase tracking-[0.24em] text-gold"><?php echo esc_html($ui['workflowLabel']); ?></p>
        <h2 class="mt-4 text-3xl font-black leading-tight tracking-[-0.015em] md:text-4xl"><?php echo esc_html($ui['workflowTitle']); ?></h2>
        <div class="mt-7 grid gap-4">
            <?php foreach ($workflow_items as $item) : ?>
                <div class="rounded border border-white/10 bg-white/8 p-4">
                    <?php echo boblift_icon((string) ($item['iconName'] ?? 'check-circle'), 'h-5 w-5 text-gold'); ?>
                    <h3 class="mt-3 text-base font-black"><?php echo esc_html((string) ($item['title'] ?? 'Quote step')); ?></h3>
                    <p class="mt-1 text-sm font-normal leading-6 text-white/70"><?php echo esc_html((string) ($item['text'] ?? '')); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        <a class="mt-7 inline-flex items-center gap-2 text-sm font-black text-gold" href="<?php echo esc_url(boblift_url((string) ($ui['productLinkHref'] ?? '/products.html'))); ?>"><?php echo esc_html($ui['productLinkLabel']); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
    </aside>

    <?php boblift_render_inquiry_form($product); ?>
</section>
<?php get_footer(); ?>
