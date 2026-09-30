<?php
get_header();
the_post();

$ui = boblift_site_ui('newsDetail');
$article_ui = boblift_site_ui('newsArticle');
$post_id = get_the_ID();
$localize = static fn(string $text): string => function_exists('boblift_localize_product_text_fragments') ? (string) boblift_localize_product_text_fragments($text) : $text;
$category_term = get_the_category($post_id)[0] ?? null;
$category = $category_term instanceof WP_Term ? $category_term->name : (string) ($ui['news'] ?? 'News');
$category = function_exists('boblift_localized_static_text') ? boblift_localized_static_text($category) : $category;
$title = $localize(get_the_title());
$excerpt = boblift_article_card_excerpt($localize(get_the_excerpt()), $title);
[$content, $toc] = boblift_news_article_toc($localize((string) apply_filters('the_content', get_the_content())));
$show_toc = count($toc) >= 3;
$published = get_the_date('Y-m-d');
$modified = get_the_modified_date('Y-m-d');
$minutes = boblift_news_reading_minutes($content);
$reading = str_replace('{minutes}', (string) $minutes, (string) ($article_ui['readingTemplate'] ?? '{minutes} min read'));
$image = get_the_post_thumbnail_url($post_id, 'full') ?: 'images/page-heroes/factory-floor.webp';
$products = boblift_news_related_products($title, $content, 3);
$contact = boblift_contact_settings();

$related_count = max(0, min(3, (int) ($ui['relatedArticleCount'] ?? 3)));
$related_query_args = [
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => $related_count,
    'post__not_in' => [$post_id],
    'orderby' => 'date',
    'order' => 'DESC',
    'ignore_sticky_posts' => true,
];
if ($category_term instanceof WP_Term) {
    $related_query_args['cat'] = (int) $category_term->term_id;
}
$related_query = $related_count > 0 ? new WP_Query($related_query_args) : null;
?>

<article class="boblift-news-article">
    <div class="page-shell pt-5 md:pt-8">
        <?php
        boblift_page_hero([
            'label' => $category,
            'title' => $title,
            'text' => $excerpt,
            'meta' => $published . ' · ' . $reading,
            'image' => $image,
            'image_alt' => $title,
            'image_position' => 'center',
            'class' => 'boblift-news-hero',
        ]);
        ?>
    </div>

    <div class="page-shell py-8 md:py-12 lg:py-14">
        <div class="grid gap-8 lg:grid-cols-[minmax(0,17.5rem)_minmax(0,1fr)] lg:gap-12 xl:grid-cols-[19rem_minmax(0,1fr)] xl:gap-16">
            <div class="min-w-0 lg:order-2">
                <?php if ($show_toc) : ?>
                    <details class="boblift-news-toc-mobile mb-6 rounded border border-line bg-mist lg:hidden">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-sm font-black text-ink">
                            <span class="inline-flex items-center gap-2"><?php echo boblift_icon('list', 'h-4 w-4 text-slate-400'); ?><?php echo esc_html((string) ($article_ui['tocTitle'] ?? 'In this article')); ?></span>
                            <?php echo boblift_icon('chevron-down', 'boblift-news-toc-chevron h-4 w-4 text-slate-400 transition'); ?>
                        </summary>
                        <ol class="boblift-news-toc-list border-t border-line px-4 py-3">
                            <?php foreach ($toc as $item) : ?>
                                <li><a href="#<?php echo esc_attr($item['id']); ?>"><?php echo esc_html($item['text']); ?></a></li>
                            <?php endforeach; ?>
                        </ol>
                    </details>
                <?php endif; ?>

                <div class="boblift-article-body">
                    <?php echo wp_kses_post($content); ?>
                </div>

                <div class="mt-10 flex flex-wrap items-center justify-between gap-4 border-t border-line pt-6 text-sm font-bold text-slate-500">
                    <span><?php echo esc_html((string) ($article_ui['updatedLabel'] ?? 'Updated')); ?> <time datetime="<?php echo esc_attr($modified); ?>"><?php echo esc_html($modified); ?></time></span>
                    <a class="inline-flex items-center gap-2 text-navy transition hover:text-blue-700" href="<?php echo esc_url(boblift_url('/news')); ?>"><?php echo boblift_icon('chevron-left', 'h-4 w-4'); ?><?php echo esc_html((string) ($article_ui['backLabel'] ?? 'Back to News')); ?></a>
                </div>
            </div>

            <aside class="min-w-0 lg:order-1" aria-label="<?php echo esc_attr((string) ($ui['summary'] ?? 'Article Summary')); ?>">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                    <section class="rounded border border-line bg-white p-5">
                        <div class="flex items-center gap-3">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-sm bg-navy text-white"><?php echo boblift_icon('building-2', 'h-5 w-5'); ?></span>
                            <div class="min-w-0">
                                <p class="text-sm font-black leading-tight text-ink"><?php echo esc_html((string) ($article_ui['authorName'] ?? 'BOB-LIFT Editorial Team')); ?></p>
                                <p class="mt-1 text-xs font-semibold leading-5 text-slate-500"><?php echo esc_html((string) ($article_ui['authorText'] ?? '')); ?></p>
                            </div>
                        </div>
                        <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 border-t border-line pt-4 text-xs">
                            <div>
                                <dt class="font-bold text-slate-400"><?php echo esc_html((string) ($article_ui['publishedLabel'] ?? 'Published')); ?></dt>
                                <dd class="mt-1 font-black text-ink"><time datetime="<?php echo esc_attr($published); ?>"><?php echo esc_html($published); ?></time></dd>
                            </div>
                            <div>
                                <dt class="font-bold text-slate-400"><?php echo esc_html((string) ($ui['category'] ?? 'Category')); ?></dt>
                                <dd class="mt-1 font-black text-ink"><?php echo esc_html($category); ?></dd>
                            </div>
                            <div class="col-span-2 inline-flex items-center gap-2 font-bold text-slate-500"><?php echo boblift_icon('clock-3', 'h-4 w-4 text-slate-400'); ?><?php echo esc_html($reading); ?></div>
                        </dl>
                    </section>

                    <?php if ($products !== []) : ?>
                        <section class="rounded border border-line bg-white p-5">
                            <h2 class="text-xs font-black uppercase tracking-[0.14em] text-slate-500"><?php echo esc_html((string) ($article_ui['productsTitle'] ?? 'Products in this article')); ?></h2>
                            <ul class="mt-3 divide-y divide-line">
                                <?php foreach ($products as $product) :
                                    $product_title = $localize(get_the_title($product));
                                    $model = (string) get_post_meta(boblift_product_english_id($product->ID), '_model', true);
                                    // Only real model codes (letters and digits, 4+ chars) not already in the title.
                                    $model = strlen($model) >= 4 && preg_match('/\d/', $model) && preg_match('/[a-z]/i', $model) && stripos($product_title, $model) === false ? $model : '';
                                    ?>
                                    <li>
                                        <a class="group flex items-center gap-3 py-3" href="<?php echo esc_url(get_permalink($product)); ?>">
                                            <span class="h-14 w-16 shrink-0 overflow-hidden rounded-sm border border-line bg-mist"><img class="h-full w-full object-contain p-1" src="<?php echo esc_url(boblift_post_thumbnail_url($product, boblift_asset('images/products/crane-accessories.png'))); ?>" alt="" loading="lazy" decoding="async"></span>
                                            <span class="min-w-0">
                                                <span class="line-clamp-2 text-sm font-black leading-snug text-ink transition group-hover:text-blue-700"><?php echo esc_html($product_title); ?></span>
                                                <?php if ($model !== '') : ?><span class="mt-0.5 block text-xs font-bold text-slate-400"><?php echo esc_html($model); ?></span><?php endif; ?>
                                            </span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <a class="mt-2 inline-flex items-center gap-2 text-sm font-bold text-navy transition hover:text-blue-700" href="<?php echo esc_url(boblift_url('/products.html')); ?>"><?php echo esc_html((string) ($article_ui['productsLink'] ?? 'View all products')); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
                        </section>
                    <?php endif; ?>

                    <div class="grid gap-4 sm:col-span-2 lg:sticky lg:top-6 lg:col-span-1">
                        <?php if ($show_toc) : ?>
                            <nav class="boblift-news-toc hidden rounded border border-line bg-white p-5 lg:block" aria-label="<?php echo esc_attr((string) ($article_ui['tocTitle'] ?? 'In this article')); ?>" data-news-toc>
                                <h2 class="text-xs font-black uppercase tracking-[0.14em] text-slate-500"><?php echo esc_html((string) ($article_ui['tocTitle'] ?? 'In this article')); ?></h2>
                                <ol class="boblift-news-toc-list mt-3">
                                    <?php foreach ($toc as $item) : ?>
                                        <li><a href="#<?php echo esc_attr($item['id']); ?>"><?php echo esc_html($item['text']); ?></a></li>
                                    <?php endforeach; ?>
                                </ol>
                            </nav>
                        <?php endif; ?>

                        <section class="rounded border border-navy bg-navy p-5 text-white">
                            <h2 class="text-lg font-black leading-snug"><?php echo esc_html((string) ($article_ui['contactTitle'] ?? '')); ?></h2>
                            <p class="mt-2 text-sm font-semibold leading-6 text-white/70"><?php echo esc_html((string) ($article_ui['contactText'] ?? '')); ?></p>
                            <a class="btn-white mt-4 w-full justify-center rounded-sm" href="<?php echo esc_url(boblift_url('/contact.html#inquiry')); ?>" data-inquiry-modal-open data-inquiry-source="新闻详情"><?php echo esc_html((string) ($article_ui['contactButton'] ?? 'Request a Quote')); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
                            <div class="mt-4 grid gap-2 border-t border-white/15 pt-4 text-sm font-bold">
                                <a class="inline-flex min-w-0 items-center gap-2 text-white/85 transition hover:text-white" href="<?php echo esc_url($contact['whatsappHref']); ?>" target="_blank" rel="noreferrer"><?php echo boblift_icon('whatsapp', 'h-4 w-4 shrink-0'); ?><span class="truncate"><?php echo esc_html((string) ($article_ui['whatsappLabel'] ?? 'WhatsApp')); ?> · <?php echo esc_html($contact['whatsapp']); ?></span></a>
                                <a class="inline-flex min-w-0 items-center gap-2 text-white/85 transition hover:text-white" href="mailto:<?php echo esc_attr($contact['email']); ?>"><?php echo boblift_icon('mail', 'h-4 w-4 shrink-0'); ?><span class="truncate"><?php echo esc_html($contact['email']); ?></span></a>
                            </div>
                        </section>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <?php if ($related_query instanceof WP_Query && $related_query->have_posts()) : ?>
        <section class="border-t border-line bg-mist py-10 md:py-14">
            <div class="page-shell">
                <div class="mb-6 flex items-end justify-between gap-4">
                    <h2 class="text-[clamp(1.5rem,2.2vw,2.25rem)] font-black leading-tight tracking-[-0.015em] text-ink"><?php echo esc_html((string) ($article_ui['relatedTitle'] ?? 'More news')); ?></h2>
                    <a class="inline-flex shrink-0 items-center gap-2 text-sm font-bold text-navy transition hover:text-blue-700" href="<?php echo esc_url(boblift_url('/news')); ?>"><?php echo esc_html((string) ($article_ui['relatedLink'] ?? 'All news')); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 lg:gap-5">
                    <?php while ($related_query->have_posts()) : $related_query->the_post();
                        $related_title = $localize(get_the_title());
                        $related_category = get_the_category()[0]->name ?? ($ui['news'] ?? 'News');
                        $related_category = function_exists('boblift_localized_static_text') ? boblift_localized_static_text((string) $related_category) : (string) $related_category;
                        ?>
                        <article class="group overflow-hidden rounded border border-line bg-white">
                            <a class="block aspect-[16/9] overflow-hidden bg-mist" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
                                <img class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]" src="<?php echo esc_url(boblift_post_thumbnail_url(get_post(), boblift_asset('images/page-heroes/factory-floor.webp'))); ?>" alt="" loading="lazy" decoding="async">
                            </a>
                            <div class="p-5">
                                <p class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-bold text-slate-500"><span class="font-black uppercase tracking-[0.12em] text-blue-700"><?php echo esc_html($related_category); ?></span><time datetime="<?php echo esc_attr(get_the_date('Y-m-d')); ?>"><?php echo esc_html(get_the_date('Y-m-d')); ?></time></p>
                                <h3 class="mt-2 line-clamp-2 text-lg font-black leading-snug text-ink"><a class="transition hover:text-blue-700" href="<?php the_permalink(); ?>"><?php echo esc_html($related_title); ?></a></h3>
                            </div>
                        </article>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
</article>

<?php get_footer(); ?>
