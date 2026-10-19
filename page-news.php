<?php
/**
 * Template Name: News
 */
get_header();

$news_category_slugs = function_exists('boblift_news_category_source_slugs') ? boblift_news_category_source_slugs() : ['company-news', 'industry-insight'];
$news_categories = array_values(array_filter(array_map(static function (string $slug): ?WP_Term {
    return function_exists('boblift_term_by_slug_for_current_language') ? boblift_term_by_slug_for_current_language($slug, 'category') : get_category_by_slug($slug);
}, $news_category_slugs)));
$allowed_category_slugs = array_map(static fn(WP_Term $term): string => $term->slug, $news_categories);
$news_category_names = [];
foreach ($news_categories as $term) {
    $news_category_names[$term->slug] = function_exists('boblift_localized_static_text') ? boblift_localized_static_text($term->name) : $term->name;
}
$active_category = isset($_GET['news_category']) ? sanitize_title(wp_unslash((string) $_GET['news_category'])) : '';
$active_category = boblift_news_filter_category($active_category, $allowed_category_slugs);
if ($active_category !== '' && !in_array($active_category, $allowed_category_slugs, true) && function_exists('boblift_plain_term_slug')) {
    $active_category = boblift_plain_term_slug($active_category, function_exists('boblift_current_language_slug') ? boblift_current_language_slug() : '');
}
if ($active_category !== '' && !in_array($active_category, $allowed_category_slugs, true)) {
    $active_category = '';
}
$articles = boblift_articles('', -1);
$grid_articles = $articles;
$ui = boblift_site_ui('newsPage');
$hero_image = $ui['heroImage'] ?? 'images/page-heroes/factory-floor.webp';
$language = function_exists('boblift_current_language_slug') ? boblift_current_language_slug() : '';
$all_label = function_exists('boblift_localized_static_text') ? boblift_localized_static_text((string) ($ui['allLabel'] ?? 'All')) : (string) ($ui['allLabel'] ?? 'All');
$empty_title = (string) ($ui['emptyTitle'] ?? 'No matching news found');
$empty_text = (string) ($ui['emptyText'] ?? 'Choose another category to browse more BOB-LIFT updates.');
?>

<div class="page-shell pt-5 md:pt-8">
    <?php
    boblift_page_hero([
        'label' => $ui['heroLabel'] ?? 'BOB-LIFT Newsroom',
        'title' => $ui['heroTitle'] ?? 'News & Insights',
        'text' => $ui['heroDescription'] ?? 'Stay informed with the latest updates from BOB-LIFT, including product innovations, company news, and industry insights.',
        'image' => (string) $hero_image,
        'image_alt' => (string) ($ui['heroImageAlt'] ?? 'Rows of BOB-LIFT knuckle boom cranes in the factory'),
        'image_position' => 'center 45%',
    ]);
    ?>
</div>

<section id="news-updates" class="page-shell scroll-mt-[120px] pb-20 pt-8 md:pt-10 2xl:pb-24" data-news-filter-root data-news-max-visible="9" data-news-initial-category="<?php echo esc_attr($active_category); ?>">
    <div class="mb-14 flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div class="boblift-news-view-switch me-auto" aria-label="<?php echo esc_attr((string) ($ui['viewModeLabel'] ?? 'News display mode')); ?>">
            <button class="boblift-news-view-button is-active" type="button" data-news-view-button="grid" aria-pressed="true"><?php echo boblift_icon('grid-3x3', 'h-4 w-4'); ?><span><?php echo esc_html((string) ($ui['defaultViewLabel'] ?? 'Default')); ?></span></button>
            <button class="boblift-news-view-button" type="button" data-news-view-button="list" aria-pressed="false"><?php echo boblift_icon('list', 'h-4 w-4'); ?><span><?php echo esc_html((string) ($ui['listViewLabel'] ?? 'List')); ?></span></button>
        </div>
        <div class="flex flex-wrap justify-start gap-4 xl:ms-auto xl:justify-end">
            <button class="boblift-news-category-pill <?php echo $active_category === '' ? 'is-active' : ''; ?>" type="button" data-news-filter-button value="" aria-pressed="<?php echo $active_category === '' ? 'true' : 'false'; ?>"><?php echo esc_html($all_label); ?></button>
            <?php foreach ($news_categories as $category) : ?>
                <button class="boblift-news-category-pill <?php echo $active_category === $category->slug ? 'is-active' : ''; ?>" type="button" data-news-filter-button value="<?php echo esc_attr($category->slug); ?>" aria-pressed="<?php echo $active_category === $category->slug ? 'true' : 'false'; ?>"><?php echo esc_html(function_exists('boblift_localized_static_text') ? boblift_localized_static_text($category->name) : $category->name); ?></button>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($grid_articles) : ?>
        <div class="mb-8 flex items-center gap-4">
            <span class="h-9 w-1 rounded-full bg-gold"></span>
            <h2 class="text-3xl font-black tracking-[-0.015em] text-navy md:text-4xl"><?php echo esc_html($ui['featuredUpdatesLabel'] ?? 'Featured Updates'); ?></h2>
        </div>
        <div class="boblift-news-results-grid grid grid-cols-1 gap-4 md:grid-cols-3 lg:gap-5 xl:gap-6" data-news-grid>
            <?php
            $initial_visible_count = 0;
            foreach ($grid_articles as $article) :
                $article_category_slug = boblift_news_filter_category(sanitize_title((string) ($article['category_slug'] ?? $article['category'] ?? 'news')), $allowed_category_slugs);
                $article_category_name = $news_category_names[$article_category_slug] ?? (string) $article['category'];
                $matches_initial_filter = $active_category === '' || $active_category === $article_category_slug;
                $visible_on_load = $matches_initial_filter && $initial_visible_count < 9;
                if ($visible_on_load) {
                    $initial_visible_count++;
                }
                ?>
                <a class="boblift-news-result-card group grid min-w-0 overflow-hidden rounded border border-line bg-white/92 p-2 shadow-card transition duration-300 hover:-translate-y-1 hover:bg-white <?php echo $visible_on_load ? '' : 'hidden'; ?>" href="<?php echo esc_url($article['url']); ?>" data-news-card data-news-category="<?php echo esc_attr($article_category_slug); ?>">
                    <div class="boblift-news-result-media relative aspect-[1.5/1] overflow-hidden rounded "><img loading="lazy" decoding="async" class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]" src="<?php echo esc_url(boblift_item_image($article)); ?>" alt="<?php echo esc_attr($article['title']); ?>"></div>
                    <div class="boblift-news-result-content flex min-w-0 flex-col px-2 py-4 md:px-3 md:py-5 xl:px-4"><div class="boblift-news-result-meta mb-2 flex flex-wrap items-center gap-1.5 md:gap-2"><span class="boblift-news-result-category inline-flex h-6 w-fit max-w-full shrink-0 items-center justify-center gap-1 overflow-hidden whitespace-nowrap rounded-sm border border-line bg-white px-2.5 py-0.5 text-[10px] font-black uppercase tracking-[0.12em] text-gold transition-all"><?php echo esc_html($article_category_name); ?></span><span class="boblift-news-result-date text-xs font-bold text-slate-500"><?php echo esc_html($article['date']); ?></span></div><h3 class="boblift-news-result-title line-clamp-2 text-lg font-black leading-snug tracking-normal text-ink xl:text-xl"><?php echo esc_html($article['title']); ?></h3><p class="boblift-news-result-excerpt mt-2 line-clamp-2 text-sm font-medium leading-6 text-slate-500 empty:hidden"><?php echo esc_html($article['excerpt']); ?></p><span class="boblift-news-result-readmore mt-4 inline-flex items-center gap-2 text-xs font-black text-ink"><?php echo esc_html($ui['readMoreLabel'] ?? 'Read More'); ?> <?php echo boblift_icon('arrow-right', 'h-3.5 w-3.5'); ?></span></div>
                </a>
            <?php endforeach; ?>
        </div>
        <?php $pager_ui = boblift_site_ui('productArchive'); ?>
        <nav class="mt-10 flex flex-wrap items-center justify-center gap-1.5 sm:gap-2<?php echo $initial_visible_count < count($grid_articles) ? '' : ' hidden'; ?>" aria-label="<?php echo esc_attr((string) ($ui['paginationLabel'] ?? 'News pagination')); ?>" data-news-pagination data-previous-label="<?php echo esc_attr((string) ($pager_ui['previousLabel'] ?? 'Previous')); ?>" data-next-label="<?php echo esc_attr((string) ($pager_ui['nextLabel'] ?? 'Next')); ?>"></nav>
        <div class="hidden rounded border border-dashed border-blue-200 bg-blue-50/45 p-8 text-center" data-news-empty>
            <h2 class="text-2xl font-black tracking-[-0.015em] text-ink"><?php echo esc_html($empty_title); ?></h2>
            <p class="mx-auto mt-3 max-w-[560px] text-sm font-normal leading-7 text-slate-600"><?php echo esc_html($empty_text); ?></p>
        </div>
    <?php endif; ?>
</section>

<?php get_footer(); ?>
