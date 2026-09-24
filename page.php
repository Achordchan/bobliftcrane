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
    <div class="page-shell pt-5 md:pt-8">
        <?php
        boblift_page_hero([
            'label' => (string) ($common_ui['brandLabel'] ?? 'BOB-LIFT'),
            'title' => $page_title,
            'text' => $page_excerpt,
            'image' => $image ?: '',
            'image_alt' => $page_title,
        ]);
        ?>
    </div>

    <section class="page-shell pb-10 pt-8 md:pb-16 md:pt-10">
        <article class="overflow-hidden rounded border border-line bg-white p-6 shadow-card md:p-9">
            <div class="cms-richtext"><?php echo wp_kses_post($page_content); ?></div>
        </article>
    </section>
</div>
<?php get_footer(); ?>
