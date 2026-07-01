<?php
get_header();
the_post();

$ui = boblift_site_ui('newsDetail');
$post_id = get_the_ID();
$image = get_the_post_thumbnail_url($post_id, 'full') ?: boblift_asset('images/factory-workshop.jpg');
$category_term = get_the_category($post_id)[0] ?? null;
$category = $category_term instanceof WP_Term ? $category_term->name : (string) ($ui['news'] ?? 'News');
$category = function_exists('boblift_localized_static_text') ? boblift_localized_static_text($category) : $category;
$title = function_exists('boblift_localize_product_text_fragments') ? (string) boblift_localize_product_text_fragments(get_the_title()) : get_the_title();
$excerpt = function_exists('boblift_localize_product_text_fragments') ? (string) boblift_localize_product_text_fragments(get_the_excerpt()) : get_the_excerpt();
$content = apply_filters('the_content', get_the_content());
$content = function_exists('boblift_localize_product_text_fragments') ? (string) boblift_localize_product_text_fragments($content) : $content;
$published_date = get_the_date('Y-m-d');
$related_count = max(0, (int) ($ui['relatedArticleCount'] ?? 2));
$related_query_args = [
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => min(2, $related_count),
    'post__not_in' => [$post_id],
    'orderby' => 'date',
    'order' => 'DESC',
    'ignore_sticky_posts' => true,
];
if ($category_term instanceof WP_Term) {
    $related_query_args['cat'] = (int) $category_term->term_id;
}
$related_query = $related_count > 0 ? new WP_Query($related_query_args) : null;
$labels = $ui;
?>

<article class="boblift-news-detail boblift-middle-breadcrumb-detail">
    <header class="boblift-news-detail-hero">
        <div class="page-shell py-12 md:py-16 2xl:py-20">
            <div class="boblift-news-detail-frame">
                <div class="boblift-news-detail-hero-inner">
                    <div class="boblift-news-detail-heading">
                        <h1 class="boblift-news-detail-title"><?php echo esc_html($title); ?></h1>
                        <?php if ($excerpt !== '') : ?>
                            <p class="boblift-news-detail-excerpt"><?php echo esc_html($excerpt); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="boblift-news-detail-hero-image">
                        <img class="h-full w-full object-cover" src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>" loading="eager" decoding="async" fetchpriority="high">
                    </div>
                </div>
            </div>
        </div>
    </header>


    <div class="boblift-detail-breadcrumb-band">
        <div class="page-shell">
            <div class="boblift-detail-breadcrumb-row">
                <nav class="boblift-news-detail-breadcrumb boblift-detail-breadcrumb" aria-label="<?php echo esc_attr($labels['breadcrumbLabel'] ?? 'Breadcrumb'); ?>">
                    <a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html($labels['home']); ?></a>
                    <span>&gt;</span>
                    <a href="<?php echo esc_url(boblift_url('/news')); ?>"><?php echo esc_html($labels['news']); ?></a>
                </nav>
                <div class="boblift-detail-breadcrumb-meta" aria-label="Article meta">
                    <span><?php echo esc_html($category); ?></span>
                    <span class="boblift-detail-breadcrumb-dot" aria-hidden="true"></span>
                    <time datetime="<?php echo esc_attr($published_date); ?>"><?php echo esc_html($published_date); ?></time>
                </div>
            </div>
        </div>
    </div>

    <div class="boblift-news-detail-body-shell page-shell py-12 md:py-16 2xl:py-20">
        <div class="boblift-news-detail-frame">
            <div class="boblift-news-detail-layout">
                <div class="cms-richtext boblift-news-detail-content">
                    <?php echo wp_kses_post($content); ?>
                </div>

                <aside class="boblift-news-detail-sidebar" aria-label="<?php echo esc_attr($labels['summary']); ?>">
                    <div class="boblift-news-detail-sidebar-inner">
                        <div class="boblift-news-detail-summary">
                            <p class="section-label"><?php echo esc_html($labels['summary']); ?></p>
                            <dl class="boblift-news-detail-summary-list">
                                <div>
                                    <dt><?php echo esc_html($labels['category']); ?></dt>
                                    <dd><?php echo esc_html($category); ?></dd>
                                </div>
                                <div>
                                    <dt><?php echo esc_html($labels['published']); ?></dt>
                                    <dd><?php echo esc_html($published_date); ?></dd>
                                </div>
                                <div>
                                    <dt><?php echo esc_html($labels['sourceLabel']); ?></dt>
                                    <dd><?php echo esc_html($labels['sourceValue'] ?? 'BOB-LIFT'); ?></dd>
                                </div>
                            </dl>
                        </div>

                        <div class="boblift-news-detail-quote-card">
                            <p class="section-label"><?php echo esc_html($labels['ctaLabel']); ?></p>
                            <h2><?php echo esc_html($labels['ctaTitle']); ?></h2>
                            <a class="btn-primary rounded-full" href="<?php echo esc_url(boblift_url('/quote')); ?>"><?php echo esc_html($labels['ctaButton']); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4 text-gold'); ?></a>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>

    <?php if ($related_query instanceof WP_Query && $related_query->have_posts()) : ?>
        <section class="page-shell pb-12 md:pb-16 2xl:pb-20">
            <div class="boblift-news-detail-frame">
                <div class="boblift-news-detail-related">
                    <div class="mb-7 flex items-center gap-4">
                        <span class="h-9 w-1 rounded-full bg-gold"></span>
                        <h2 class="text-3xl font-black tracking-[-0.04em] text-navy md:text-4xl"><?php echo esc_html($labels['related']); ?></h2>
                    </div>
                    <div class="grid gap-7 md:grid-cols-2">
                        <?php while ($related_query->have_posts()) : $related_query->the_post();
                            $related_title = function_exists('boblift_localize_product_text_fragments') ? (string) boblift_localize_product_text_fragments(get_the_title()) : get_the_title();
                            ?>
                            <article class="boblift-news-detail-related-card">
                                <a class="boblift-news-detail-related-image" href="<?php the_permalink(); ?>">
                                    <img class="h-full w-full object-cover transition duration-500 hover:scale-[1.04]" src="<?php echo esc_url(boblift_post_thumbnail_url(get_post(), boblift_asset('images/factory-workshop.jpg'))); ?>" alt="<?php echo esc_attr($related_title); ?>" loading="lazy" decoding="async">
                                </a>
                                <div class="p-7">
                                    <div class="flex flex-wrap items-center gap-4 text-xs font-black uppercase tracking-[0.14em]">
                                        <span class="text-blue-700"><?php $related_category = get_the_category()[0]->name ?? ($ui['news'] ?? 'News'); echo esc_html(function_exists('boblift_localized_static_text') ? boblift_localized_static_text((string) $related_category) : (string) $related_category); ?></span>
                                        <span class="text-slate-500"><?php echo esc_html(get_the_date('Y-m-d')); ?></span>
                                    </div>
                                    <h3 class="mt-3 line-clamp-2 text-2xl font-black leading-tight tracking-[-0.035em] text-navy">
                                        <a class="transition hover:text-blue-700" href="<?php the_permalink(); ?>"><?php echo esc_html($related_title); ?></a>
                                    </h3>
                                </div>
                            </article>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>
</article>

<?php get_footer(); ?>
