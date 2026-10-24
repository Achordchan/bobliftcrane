<?php
get_header();
$localize_archive_text = static fn(string $text): string => function_exists('boblift_localize_product_text_fragments') ? (string) boblift_localize_product_text_fragments($text) : $text;
// A news category page (Company News, Industry Insight) is a landing page in the sitemap: it gets its own H1 and intro.
$archive_heading = '';
$archive_intro = '';
if (is_category()) {
    $archive_heading = function_exists('boblift_localized_static_text') ? boblift_localized_static_text(single_term_title('', false)) : single_term_title('', false);
    $archive_intro = trim(wp_strip_all_tags(term_description()));
}
if (have_posts()) : ?>
<section class="page-shell py-12 md:py-20"><?php if ($archive_heading !== '') : ?><header class="mb-8 md:mb-12"><h1 class="text-4xl font-black text-navy"><?php echo esc_html($archive_heading); ?></h1><?php if ($archive_intro !== '') : ?><p class="mt-4 max-w-3xl text-base font-normal leading-7 text-slate-500"><?php echo esc_html($archive_intro); ?></p><?php endif; ?></header><?php endif; ?><div class="grid grid-cols-1 gap-5 md:grid-cols-3">
<?php while (have_posts()) : the_post();
    $archive_title = $localize_archive_text(get_the_title());
    $archive_excerpt = $localize_archive_text(get_the_excerpt());
    ?><a class="card-hover rounded border border-line bg-white p-5 shadow-card" href="<?php the_permalink(); ?>"><h2 class="text-2xl font-black text-navy"><?php echo esc_html($archive_title); ?></h2><p class="mt-3 text-sm font-normal leading-6 text-slate-500"><?php echo esc_html($archive_excerpt); ?></p></a><?php endwhile; ?>
</div></section>
<?php else : ?>
<section class="page-shell py-20"><h1 class="text-4xl font-black text-navy"><?php echo esc_html((function_exists('boblift_site_ui') ? (boblift_site_ui('common')['noContentFound'] ?? 'No content found') : 'No content found')); ?></h1></section>
<?php endif; get_footer(); ?>
