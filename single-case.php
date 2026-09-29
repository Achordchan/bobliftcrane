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
$localize_case_text = static fn(string $text): string => (string) $localize_case_fragments(function_exists('boblift_localized_static_text') ? boblift_localized_static_text($text) : $text);
$facts = $localize_case_fragments(boblift_decode_meta(get_post_meta($post_id, '_facts', true), []));
$country = trim((string) $localize_case_fragments((string) get_post_meta($post_id, '_country', true)));
$product_name = trim((string) $localize_case_fragments((string) get_post_meta($post_id, '_product_name', true)));
$application = trim((string) $localize_case_fragments((string) get_post_meta($post_id, '_application', true)));
$type = trim((string) $localize_case_fragments((string) get_post_meta($post_id, '_type', true)));
$summary = trim((string) $localize_case_fragments((string) (get_post_meta($post_id, '_summary', true) ?: get_the_excerpt())));
$title = (string) $localize_case_fragments(get_the_title());
$content = (string) $localize_case_fragments(apply_filters('the_content', get_the_content()));
if (trim(wp_strip_all_tags($content)) === '') {
    // Older cases keep their story in the structured sections instead of the editor.
    $sections = $localize_case_fragments(boblift_decode_meta(get_post_meta($post_id, '_sections', true), []));
    $content = '';
    foreach (is_array($sections) ? $sections : [] as $section) {
        $heading = trim((string) ($section['heading'] ?? ''));
        $body = trim((string) ($section['body'] ?? ''));
        $content .= ($heading !== '' ? '<h2>' . esc_html($heading) . '</h2>' : '') . ($body !== '' ? wpautop(esc_html($body)) : '');
    }
}
$image = get_the_post_thumbnail_url($post_id, 'full') ?: '';
$cases_url = function_exists('boblift_case_archive_url') ? boblift_case_archive_url() : boblift_url('/cases.html');
$case_label = $localize_case_text($type !== '' ? $type : (string) ($ui['defaultTypeLabel'] ?? 'Customer Case'));
$scene_label = (string) (boblift_site_ui('casesPage')['sceneCasesLabel'] ?? 'Application Cases');

$display_facts = [];
foreach (is_array($facts) ? $facts : [] as $fact) {
    $label = trim((string) ($fact['label'] ?? ''));
    $value = trim((string) ($fact['value'] ?? ''));
    if ($label !== '' && $value !== '') {
        $display_facts[] = ['label' => $localize_case_text($label), 'value' => $value];
    }
}
if (!$display_facts) {
    $display_facts = array_values(array_filter([
        $country !== '' ? ['label' => $localize_case_text('Country'), 'value' => $country] : null,
        $product_name !== '' ? ['label' => $localize_case_text('Product'), 'value' => $product_name] : null,
        $application !== '' ? ['label' => $localize_case_text('Scenario'), 'value' => $application] : null,
    ]));
}
$meta_line = array_values(array_filter([$country, $product_name, $application]));
$quote_url = boblift_url($product_name !== '' ? '/contact.html?product=' . rawurlencode($product_name) . '#inquiry' : '/contact.html#inquiry');

$related_query = new WP_Query([
    'post_type' => 'case',
    'post_status' => 'publish',
    'posts_per_page' => 3,
    'post__not_in' => [$post_id],
    'meta_query' => ['relation' => 'OR', ['key' => '_case_kind', 'value' => 'customer_feedback', 'compare' => '!='], ['key' => '_case_kind', 'compare' => 'NOT EXISTS']],
    'orderby' => 'date',
    'order' => 'DESC',
    'ignore_sticky_posts' => true,
]);
?>

<article class="boblift-case-story">
    <header class="page-shell pt-6 md:pt-10">
        <nav class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs font-bold text-slate-500" aria-label="<?php echo esc_attr($ui['breadcrumbLabel'] ?? 'Breadcrumb'); ?>">
            <a class="transition hover:text-blue-700" href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html($ui['home'] ?? 'Home'); ?></a>
            <span aria-hidden="true">/</span>
            <a class="transition hover:text-blue-700" href="<?php echo esc_url($cases_url); ?>"><?php echo esc_html($ui['cases'] ?? 'Cases'); ?></a>
            <span aria-hidden="true">/</span>
            <span><?php echo esc_html($scene_label); ?></span>
        </nav>

        <div class="mx-auto mt-10 max-w-[980px] text-center md:mt-16">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700"><?php echo esc_html($case_label); ?></p>
            <h1 class="mt-4 text-[2.25rem] font-black leading-[1.05] tracking-[-0.025em] text-ink md:text-6xl"><?php echo esc_html($title); ?></h1>
            <?php if ($summary !== '') : ?>
                <p class="mx-auto mt-5 max-w-[720px] text-base font-medium leading-7 text-slate-600 md:mt-6 md:text-xl md:leading-8"><?php echo esc_html($summary); ?></p>
            <?php endif; ?>
            <?php if ($meta_line) : ?>
                <p class="mt-6 flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-xs font-black uppercase tracking-[0.14em] text-slate-500 md:mt-8">
                    <?php foreach ($meta_line as $index => $item) : ?>
                        <?php if ($index > 0) : ?><span class="size-1 rounded-full bg-gold" aria-hidden="true"></span><?php endif; ?>
                        <span><?php echo esc_html($item); ?></span>
                    <?php endforeach; ?>
                </p>
            <?php endif; ?>
        </div>
    </header>

    <?php if ($image !== '') : ?>
        <figure class="page-shell mt-10 md:mt-14">
            <div class="overflow-hidden rounded bg-[#EDF1F5]">
                <img class="mx-auto block h-auto max-h-[80vh] w-full object-contain" src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>" loading="eager" fetchpriority="high" decoding="async">
            </div>
        </figure>
    <?php endif; ?>

    <div class="page-shell py-12 md:py-20">
        <div class="grid gap-10 lg:grid-cols-[260px_minmax(0,720px)] lg:justify-center lg:gap-16 xl:gap-24">
            <aside class="lg:sticky lg:top-28 lg:self-start" aria-label="<?php echo esc_attr($ui['factsTitle'] ?? 'Case Facts'); ?>">
                <?php if ($display_facts) : ?>
                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-400"><?php echo esc_html($ui['factsTitle'] ?? 'Case Facts'); ?></p>
                    <dl class="mt-3 grid grid-cols-2 border-t border-line lg:grid-cols-1">
                        <?php foreach ($display_facts as $fact) : ?>
                            <div class="border-b border-line py-3">
                                <dt class="text-xs font-bold text-slate-500"><?php echo esc_html($fact['label']); ?></dt>
                                <dd class="mt-0.5 text-base font-black text-ink"><?php echo esc_html($fact['value']); ?></dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                <?php endif; ?>
            </aside>

            <div class="min-w-0">
                <?php if (trim(wp_strip_all_tags($content, true)) !== '' || str_contains($content, '<img')) : ?>
                    <div class="boblift-story-prose"><?php echo wp_kses_post($content); ?></div>
                <?php endif; ?>
                <div class="mt-12 flex flex-col gap-4 border-t border-line pt-8 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-lg font-black leading-snug text-ink"><?php echo esc_html($ui['similarProjectPrompt'] ?? 'Planning a similar project?'); ?></p>
                    <a class="btn-primary rounded-sm" href="<?php echo esc_url($quote_url); ?>" data-inquiry-modal-open data-inquiry-source="案例详情"<?php echo $product_name !== '' ? ' data-inquiry-product="' . esc_attr($product_name) . '"' : ''; ?>><?php echo esc_html($ui['discussSimilarLabel'] ?? 'Discuss Similar Project'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4 text-gold'); ?></a>
                </div>
            </div>
        </div>
    </div>

    <?php if ($related_query->have_posts()) : ?>
        <section class="border-t border-line bg-mist py-14 md:py-20">
            <div class="page-shell">
                <div class="mb-6 flex items-end justify-between gap-4 md:mb-8">
                    <h2 class="text-2xl font-black tracking-[-0.015em] text-ink md:text-3xl"><?php echo esc_html($ui['relatedCasesLabel'] ?? 'Related Cases'); ?></h2>
                    <a class="inline-flex shrink-0 items-center gap-1.5 text-sm font-black text-blue-700 transition hover:text-ink" href="<?php echo esc_url($cases_url); ?>"><?php echo esc_html($ui['viewAllLabel'] ?? 'View all'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
                </div>
                <div class="grid gap-5 md:grid-cols-3">
                    <?php while ($related_query->have_posts()) : $related_query->the_post();
                        $related_title = (string) $localize_case_fragments(get_the_title());
                        $related_type = trim((string) $localize_case_fragments((string) get_post_meta(get_the_ID(), '_type', true)));
                        ?>
                        <a class="group block overflow-hidden rounded border border-line bg-white transition hover:border-blue-300" href="<?php the_permalink(); ?>">
                            <span class="block aspect-[3/2] overflow-hidden bg-[#EDF1F5]">
                                <img class="h-full w-full object-cover" src="<?php echo esc_url(boblift_post_thumbnail_url(get_post(), boblift_asset('images/factory-workshop.jpg'))); ?>" alt="<?php echo esc_attr($related_title); ?>" loading="lazy" decoding="async">
                            </span>
                            <span class="block p-5">
                                <span class="block text-[11px] font-black uppercase tracking-[0.14em] text-blue-700"><?php echo esc_html($localize_case_text($related_type !== '' ? $related_type : (string) ($ui['defaultTypeLabel'] ?? 'Customer Case'))); ?></span>
                                <span class="mt-2 line-clamp-2 block text-lg font-black leading-snug text-ink transition group-hover:text-blue-700"><?php echo esc_html($related_title); ?></span>
                            </span>
                        </a>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
</article>

<?php get_footer(); ?>
