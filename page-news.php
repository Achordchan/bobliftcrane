<?php
/**
 * Template Name: News
 */
get_header();

$news_category_slugs = function_exists('boblift_news_category_source_slugs') ? boblift_news_category_source_slugs() : ['company-news', 'product-news', 'industry-insight', 'marine-solution'];
$news_categories = array_values(array_filter(array_map(static function (string $slug): ?WP_Term {
    return function_exists('boblift_term_by_slug_for_current_language') ? boblift_term_by_slug_for_current_language($slug, 'category') : get_category_by_slug($slug);
}, $news_category_slugs)));
$allowed_category_slugs = array_map(static fn(WP_Term $term): string => $term->slug, $news_categories);
$active_category = isset($_GET['news_category']) ? sanitize_title(wp_unslash((string) $_GET['news_category'])) : '';
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
// The old hero carried two proof chips. Figures an editor changed from the built-in ones stay visible under the new hero.
$news_static = static fn(string $text): string => function_exists('boblift_localized_static_text') ? boblift_localized_static_text($text) : $text;
$news_proof_chips = array_values(array_filter(array_map(static function (array $chip) use ($ui, $news_static): ?array {
    $value = trim((string) ($ui[$chip[0]] ?? ''));
    $label = trim((string) ($ui[$chip[1]] ?? ''));
    $customised = ($value !== '' && $value !== $chip[2]) || ($label !== '' && !in_array($label, [$chip[3], $news_static($chip[3])], true));
    return $customised && $value !== '' ? ['value' => $value, 'label' => $label !== '' ? $label : $news_static($chip[3]), 'icon' => $chip[4]] : null;
}, [
    ['countriesValue', 'countriesLabel', '80+', 'Countries Served', 'globe'],
    ['yearsValue', 'yearsLabel', '20+', 'Years of Expertise', 'building-2'],
])));
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
    <?php if ($news_proof_chips !== []) : ?>
        <ul class="mt-5 flex flex-wrap gap-3">
            <?php foreach ($news_proof_chips as $chip) : ?>
                <li class="flex items-center gap-3 rounded border border-line bg-white px-4 py-3"><?php echo boblift_icon($chip['icon'], 'h-6 w-6 text-gold', '1.8'); ?><span><strong class="block text-xl font-black text-navy"><?php echo esc_html($chip['value']); ?></strong><small class="block text-sm font-semibold text-slate-600"><?php echo esc_html($chip['label']); ?></small></span></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<section id="news-updates" class="page-shell scroll-mt-[120px] pb-20 pt-8 md:pt-10 2xl:pb-24" data-news-filter-root data-news-max-visible="6" data-news-initial-category="<?php echo esc_attr($active_category); ?>">
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
                $article_category_slug = sanitize_title((string) ($article['category_slug'] ?? $article['category'] ?? 'news'));
                $matches_initial_filter = $active_category === '' || $active_category === $article_category_slug;
                $visible_on_load = $matches_initial_filter && $initial_visible_count < 6;
                if ($visible_on_load) {
                    $initial_visible_count++;
                }
                ?>
                <a class="boblift-news-result-card group grid min-w-0 overflow-hidden rounded border border-line bg-white/92 p-2 shadow-card transition duration-300 hover:-translate-y-1 hover:bg-white <?php echo $visible_on_load ? '' : 'hidden'; ?>" href="<?php echo esc_url($article['url']); ?>" data-news-card data-news-category="<?php echo esc_attr($article_category_slug); ?>">
                    <div class="boblift-news-result-media relative aspect-[1.5/1] overflow-hidden rounded "><img loading="lazy" decoding="async" class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]" src="<?php echo esc_url(boblift_item_image($article)); ?>" alt="<?php echo esc_attr($article['title']); ?>"></div>
                    <div class="boblift-news-result-content flex min-w-0 flex-col px-2 py-4 md:px-3 md:py-5 xl:px-4"><div class="boblift-news-result-meta mb-2 flex flex-wrap items-center gap-1.5 md:gap-2"><span class="boblift-news-result-category inline-flex h-6 w-fit shrink-0 items-center justify-center gap-1 overflow-hidden whitespace-nowrap rounded-sm border border-line bg-white px-2.5 py-0.5 text-[10px] font-black uppercase tracking-[0.12em] text-gold transition-all"><?php echo esc_html($article['category']); ?></span><span class="boblift-news-result-date text-xs font-bold text-slate-500"><?php echo esc_html($article['date']); ?></span></div><h3 class="boblift-news-result-title line-clamp-2 text-lg font-black leading-snug tracking-normal text-ink xl:text-xl"><?php echo esc_html($article['title']); ?></h3><p class="boblift-news-result-excerpt mt-2 line-clamp-2 text-sm font-medium leading-6 text-slate-500 empty:hidden"><?php echo esc_html($article['excerpt']); ?></p><span class="boblift-news-result-readmore mt-4 inline-flex items-center gap-2 text-xs font-black text-ink"><?php echo esc_html($ui['readMoreLabel'] ?? 'Read More'); ?> <?php echo boblift_icon('arrow-right', 'h-3.5 w-3.5'); ?></span></div>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="hidden rounded border border-dashed border-blue-200 bg-blue-50/45 p-8 text-center" data-news-empty>
            <h2 class="text-2xl font-black tracking-[-0.015em] text-ink"><?php echo esc_html($empty_title); ?></h2>
            <p class="mx-auto mt-3 max-w-[560px] text-sm font-semibold leading-7 text-slate-600"><?php echo esc_html($empty_text); ?></p>
        </div>
    <?php endif; ?>
</section>

<?php get_footer(); ?>
