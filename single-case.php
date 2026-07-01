<?php
the_post();
$case_kind = function_exists('boblift_normalize_case_kind') ? boblift_normalize_case_kind((string) get_post_meta(get_the_ID(), '_case_kind', true)) : 'scene_case';
if ($case_kind === 'customer_feedback') {
    $cases_url = function_exists('boblift_case_archive_url') ? boblift_case_archive_url() : boblift_url('/cases.html');
    wp_safe_redirect(add_query_arg('tab', 'customer_feedback', $cases_url));
    exit;
}
get_header();
$ui = boblift_site_ui('caseDetail');
$post_id = get_the_ID();
$localize_case_fragments = static fn(mixed $value): mixed => function_exists('boblift_localize_product_text_fragments') ? boblift_localize_product_text_fragments($value) : $value;
$facts = $localize_case_fragments(boblift_decode_meta(get_post_meta($post_id, '_facts', true), []));
$country = trim((string) $localize_case_fragments((string) get_post_meta($post_id, '_country', true)));
$product_name = trim((string) $localize_case_fragments((string) get_post_meta($post_id, '_product_name', true)));
$application = trim((string) $localize_case_fragments((string) get_post_meta($post_id, '_application', true)));
$type = trim((string) $localize_case_fragments((string) get_post_meta($post_id, '_type', true)));
$summary = trim((string) $localize_case_fragments((string) (get_post_meta($post_id, '_summary', true) ?: get_the_excerpt())));
$title = (string) $localize_case_fragments(get_the_title());
$content = apply_filters('the_content', get_the_content());
$content = (string) $localize_case_fragments($content);
$image = get_the_post_thumbnail_url($post_id, 'full') ?: boblift_asset('images/marine-scene.jpg');
$cases_url = function_exists('boblift_case_archive_url') ? boblift_case_archive_url() : boblift_url('/cases.html');
$case_label = $type !== '' ? $type : (string) ($ui['defaultTypeLabel'] ?? 'Customer Case');
$case_label = function_exists('boblift_localized_static_text') ? boblift_localized_static_text($case_label) : $case_label;
$case_label = (string) $localize_case_fragments($case_label);
$display_facts = $facts;
$localize_case_text = static fn(string $text): string => (string) $localize_case_fragments(function_exists('boblift_localized_static_text') ? boblift_localized_static_text($text) : $text);
if (!$display_facts) {
    $display_facts = array_values(array_filter([
        $application !== '' ? ['label' => $localize_case_text('Scenario'), 'value' => $application] : null,
        $product_name !== '' ? ['label' => $localize_case_text('Product'), 'value' => $product_name] : null,
        $country !== '' ? ['label' => $localize_case_text('Country'), 'value' => $country] : null,
    ]));
}
$meta_badges = array_values(array_filter([$country, $product_name, $application]));
$related_query = new WP_Query([
    'post_type' => 'case',
    'post_status' => 'publish',
    'posts_per_page' => 2,
    'post__not_in' => [$post_id],
    'meta_query' => [
        [
            'key' => '_case_kind',
            'value' => 'scene_case',
            'compare' => '=',
        ],
    ],
    'orderby' => 'date',
    'order' => 'DESC',
    'ignore_sticky_posts' => true,
]);
?>

<article class="boblift-news-detail boblift-case-detail boblift-middle-breadcrumb-detail">
    <header class="boblift-news-detail-hero">
        <div class="page-shell py-12 md:py-16 2xl:py-20">
            <div class="boblift-news-detail-frame">
                <div class="boblift-news-detail-hero-inner">
                    <div class="boblift-news-detail-heading">
                        <p class="boblift-news-detail-category"><?php echo esc_html($case_label); ?></p>
                        <h1 class="boblift-news-detail-title"><?php echo esc_html($title); ?></h1>
                        <?php if ($summary !== '') : ?>
                            <p class="boblift-news-detail-excerpt"><?php echo esc_html($summary); ?></p>
                        <?php endif; ?>
                        <?php if ($meta_badges) : ?>
                            <div class="mt-7 flex flex-wrap items-center gap-2">
                                <?php foreach ($meta_badges as $badge) : ?>
                                    <span class="rounded-full border border-line bg-white px-4 py-2 text-xs font-black uppercase tracking-[0.14em] text-slate-600 shadow-[0_10px_24px_rgba(8,35,74,0.04)]"><?php echo esc_html($badge); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="boblift-news-detail-hero-image">
                        <img class="h-full w-full object-cover" src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>" loading="eager" decoding="async" fetchpriority="high">
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="boblift-detail-breadcrumb-band boblift-case-breadcrumb-band">
        <div class="page-shell">
            <nav class="boblift-news-detail-breadcrumb boblift-detail-breadcrumb boblift-case-breadcrumb" aria-label="<?php echo esc_attr($ui['breadcrumbLabel'] ?? 'Breadcrumb'); ?>">
                <a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html($ui['home'] ?? 'Home'); ?></a>
                <span>&gt;</span>
                <a href="<?php echo esc_url($cases_url); ?>"><?php echo esc_html($ui['cases'] ?? 'Cases'); ?></a>
                <span>&gt;</span>
                <span><?php echo esc_html($case_label); ?></span>
            </nav>
        </div>
    </div>

    <div class="boblift-news-detail-body-shell page-shell py-12 md:py-16 2xl:py-20">
        <div class="boblift-news-detail-frame">
            <div class="boblift-news-detail-layout">
                <div class="cms-richtext boblift-news-detail-content">
                    <?php if (trim(wp_strip_all_tags($content)) !== '') : ?>
                        <?php echo wp_kses_post($content); ?>
                    <?php elseif ($summary !== '') : ?>
                        <p><?php echo esc_html($summary); ?></p>
                    <?php endif; ?>
                </div>

                <aside class="boblift-news-detail-sidebar" aria-label="<?php echo esc_attr($ui['factsTitle'] ?? 'Case Facts'); ?>">
                    <div class="boblift-news-detail-sidebar-inner">
                        <div class="boblift-news-detail-summary">
                            <p class="section-label"><?php echo esc_html($ui['factsTitle'] ?? 'Case Facts'); ?></p>
                            <?php if ($display_facts) : ?>
                                <dl class="boblift-news-detail-summary-list">
                                    <?php foreach ($display_facts as $fact) :
                                        $label = trim((string) ($fact['label'] ?? ''));
                                        $value = trim((string) ($fact['value'] ?? ''));
                                        if ($label === '' || $value === '') {
                                            continue;
                                        }
                                        ?>
                                        <div>
                                            <dt><?php echo esc_html($label); ?></dt>
                                            <dd><?php echo esc_html($value); ?></dd>
                                        </div>
                                    <?php endforeach; ?>
                                </dl>
                            <?php endif; ?>
                        </div>

                        <div class="boblift-news-detail-quote-card">
                            <p class="section-label"><?php echo esc_html($ui['projectSupportLabel'] ?? 'Project Support'); ?></p>
                            <h2><?php echo esc_html($ui['discussSimilarLabel'] ?? 'Discuss Similar Project'); ?></h2>
                            <a class="btn-primary rounded-full" href="<?php echo esc_url(boblift_url('/quote')); ?>"><?php echo esc_html($ui['discussSimilarLabel'] ?? 'Discuss Similar Project'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4 text-gold'); ?></a>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>

    <?php if ($related_query->have_posts()) : ?>
        <section class="page-shell pb-12 md:pb-16 2xl:pb-20">
            <div class="boblift-news-detail-frame">
                <div class="boblift-news-detail-related">
                    <div class="mb-7 flex items-center gap-4">
                        <span class="h-9 w-1 rounded-full bg-gold"></span>
                        <h2 class="text-3xl font-black tracking-[-0.04em] text-navy md:text-4xl"><?php echo esc_html($ui['relatedCasesLabel'] ?? 'Related Cases'); ?></h2>
                    </div>
                    <div class="grid gap-7 md:grid-cols-2">
                        <?php while ($related_query->have_posts()) : $related_query->the_post();
                            $related_type = trim((string) $localize_case_fragments((string) get_post_meta(get_the_ID(), '_type', true)));
                            $related_title = (string) $localize_case_fragments(get_the_title());
                            ?>
                            <article class="boblift-news-detail-related-card">
                                <a class="boblift-news-detail-related-image" href="<?php the_permalink(); ?>">
                                    <img class="h-full w-full object-cover transition duration-500 hover:scale-[1.04]" src="<?php echo esc_url(boblift_post_thumbnail_url(get_post(), boblift_asset('images/factory-workshop.jpg'))); ?>" alt="<?php echo esc_attr($related_title); ?>" loading="lazy" decoding="async">
                                </a>
                                <div class="p-7">
                                    <div class="flex flex-wrap items-center gap-4 text-xs font-black uppercase tracking-[0.14em]">
                                        <span class="text-blue-700"><?php echo esc_html($localize_case_text($related_type !== '' ? $related_type : (string) ($ui['defaultTypeLabel'] ?? 'Customer Case'))); ?></span>
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
