<?php
get_header();
the_post();
$localize_page_text = static fn(string $text): string => function_exists('boblift_localize_product_text_fragments') ? (string) boblift_localize_product_text_fragments($text) : $text;
$page_title = $localize_page_text(get_the_title());
$page_excerpt = $localize_page_text(get_the_excerpt());
$page_content = $localize_page_text(apply_filters('the_content', get_the_content()));
$image = get_the_post_thumbnail_url(get_the_ID(), 'full');
$common_ui = function_exists('boblift_site_ui') ? boblift_site_ui('common') : [];
?>
<div class="bg-[#F8FAFC] text-ink">
    <section class="relative overflow-hidden border-b border-line bg-[radial-gradient(circle_at_74%_26%,#ffffff_0%,#edf4ff_42%,#f8fafc_78%)]">
        <div class="page-shell grid min-h-[560px] grid-cols-1 items-center gap-10 py-12 lg:grid-cols-[0.9fr_1.1fr]">
            <div class="relative z-10 max-w-[680px]">
                <p class="section-label mb-5"><?php echo esc_html($common_ui['brandLabel'] ?? 'BOB-LIFT'); ?></p>
                <h1 class="text-[44px] font-black leading-[0.98] tracking-[-0.065em] text-ink md:text-[68px] xl:text-[78px]"><?php echo esc_html($page_title); ?></h1>
                <?php if ($page_excerpt !== '') : ?><p class="mt-7 max-w-[620px] text-lg font-semibold leading-8 text-slate-600"><?php echo esc_html($page_excerpt); ?></p><?php endif; ?>
            </div>
            <div class="relative min-h-[360px] overflow-hidden rounded-[32px] border border-white/80 bg-white shadow-card lg:min-h-[500px]">
                <?php if ($image) : ?><img class="absolute inset-0 h-full w-full object-cover" src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($page_title); ?>" loading="eager" decoding="async" fetchpriority="high"><?php else : ?><div class="absolute inset-0 bg-[linear-gradient(135deg,#ffffff_0%,#edf4ff_48%,#dbeafe_100%)]"></div><?php endif; ?>
                <span class="pointer-events-none absolute inset-0 bg-[linear-gradient(180deg,rgba(8,35,74,0.04),rgba(8,35,74,0.18))]"></span>
            </div>
        </div>
    </section>

    <section class="page-shell py-10 md:py-16">
        <article class="overflow-hidden rounded-[28px] border border-line bg-white p-6 shadow-card md:p-9">
            <div class="cms-richtext"><?php echo wp_kses_post($page_content); ?></div>
        </article>
    </section>
</div>
<?php get_footer(); ?>
