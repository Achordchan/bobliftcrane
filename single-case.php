<?php
the_post();
$case_kind = function_exists('boblift_normalize_case_kind') ? boblift_normalize_case_kind((string) get_post_meta(get_the_ID(), '_case_kind', true)) : 'scene_case';
if ($case_kind === 'customer_feedback') {
    $cases_url = function_exists('boblift_case_archive_url') ? boblift_case_archive_url() : boblift_url('/cases.html');
    wp_safe_redirect(add_query_arg('tab', 'customer_feedback', $cases_url));
    exit;
}
if (post_password_required()) {
    // Protected case: only the standard WordPress password form, before any section, gallery or fact is read.
    get_header();
    echo '<section class="page-shell py-16 md:py-24"><div class="mx-auto max-w-xl">' . get_the_password_form() . '</div></section>';
    get_footer();
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
$sections = [];
foreach ((array) $localize_case_fragments(boblift_decode_meta(get_post_meta($post_id, '_sections', true), [])) as $section) {
    $heading = trim((string) ($section['heading'] ?? ''));
    $body = trim((string) ($section['body'] ?? ''));
    if ($heading !== '' || $body !== '') {
        $sections[] = ['heading' => $heading, 'body' => $body];
    }
}
// The editor copy often repeats the sections word for word; show it only when it adds something.
$flatten = static fn(string $text): string => preg_replace('/\s+/u', '', wp_strip_all_tags($text)) ?? '';
$sections_text = implode('', array_map(static fn(array $section): string => $section['heading'] . $section['body'], $sections));
// Media (photos, video, embeds) and markup the plain-text sections cannot carry (links, tables, lists) are never duplicate
// content, so they keep the body visible.
$content_has_media = (bool) preg_match('/<(img|figure|video|audio|iframe|picture|embed|object|table|ul|ol)\b|<a\s[^>]*href=/i', $content);
$show_content = ($flatten($content) !== '' || $content_has_media) && ($content_has_media || !$sections || $flatten($content) !== $flatten($sections_text));
$gallery = [];
foreach (array_filter(array_map('intval', explode(',', (string) get_post_meta($post_id, '_case_gallery', true)))) as $attachment_id) {
    if ($src = (string) wp_get_attachment_image_url($attachment_id, 'large')) {
        $gallery[] = ['src' => $src, 'alt' => trim((string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true))];
    }
}
$section_images = array_slice($gallery, 0, count($sections));
$extra_photos = array_slice($gallery, count($sections));
$pad = static fn(int $n): string => str_pad((string) $n, 2, '0', STR_PAD_LEFT);
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
        <div class="mt-6 grid gap-4 md:mt-8 lg:grid-cols-[minmax(0,1.35fr)_minmax(0,1fr)] lg:items-end lg:gap-16">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700"><?php echo esc_html($case_label); ?></p>
                <h1 class="mt-3 max-w-[22ch] text-[2.125rem] font-black leading-[1.06] tracking-[-0.025em] text-ink md:text-5xl"><?php echo esc_html($title); ?></h1>
            </div>
            <?php if ($summary !== '') : ?>
                <p class="max-w-[720px] text-base font-medium leading-7 text-slate-600 md:text-lg md:leading-8 lg:pb-1"><?php echo esc_html($summary); ?></p>
            <?php endif; ?>
        </div>
    </header>

    <div class="page-shell mt-8 md:mt-10">
        <?php if ($image !== '') : ?>
            <img class="block aspect-[4/3] max-h-[70vh] w-full rounded-t object-cover md:aspect-[16/9] lg:aspect-[21/9]<?php echo $display_facts ? '' : ' rounded-b'; ?>" src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>" loading="eager" fetchpriority="high" decoding="async">
        <?php endif; ?>
        <?php if ($display_facts) : ?>
            <dl class="grid grid-cols-2 overflow-hidden border-l border-t border-line <?php echo $image !== '' ? 'rounded-b border-t-0' : 'rounded'; ?> <?php echo [1 => 'lg:grid-cols-1', 2 => 'lg:grid-cols-2', 3 => 'lg:grid-cols-3'][count($display_facts)] ?? 'lg:grid-cols-4'; ?>" aria-label="<?php echo esc_attr($ui['factsTitle'] ?? 'Case Facts'); ?>">
                <?php foreach ($display_facts as $fact) : ?>
                    <div class="border-b border-r border-line px-4 py-4 md:px-6 md:py-5 [&:last-child:nth-child(odd)]:col-span-2 lg:[&:last-child:nth-child(odd)]:col-span-1">
                        <dt class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-400"><?php echo esc_html($fact['label']); ?></dt>
                        <dd class="mt-1 text-base font-black text-ink md:text-lg"><?php echo esc_html($fact['value']); ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        <?php endif; ?>
    </div>

    <div class="page-shell py-14 md:py-20">
        <?php if ($sections && $section_images) : ?>
            <div class="grid gap-14 md:gap-20">
                <?php foreach ($sections as $index => $section) : $photo = $section_images[$index] ?? null; ?>
                    <section class="grid items-center gap-6 <?php echo $photo ? 'lg:grid-cols-2 lg:gap-16' : 'max-w-[760px]'; ?>">
                        <?php if ($photo) : ?>
                            <img class="aspect-[4/3] w-full rounded object-cover <?php echo $index % 2 ? 'lg:order-2' : ''; ?>" src="<?php echo esc_url($photo['src']); ?>" alt="<?php echo esc_attr($photo['alt'] !== '' ? $photo['alt'] : $section['heading']); ?>" loading="lazy" decoding="async">
                        <?php endif; ?>
                        <div>
                            <p class="text-5xl font-black leading-none tracking-[-0.03em] text-gold/50 md:text-6xl" aria-hidden="true"><?php echo esc_html($pad($index + 1)); ?></p>
                            <?php if ($section['heading'] !== '') : ?><h2 class="mt-4 text-2xl font-black leading-tight tracking-[-0.015em] text-ink md:text-3xl"><?php echo esc_html($section['heading']); ?></h2><?php endif; ?>
                            <?php if ($section['body'] !== '') : ?><div class="boblift-story-prose mt-4"><?php echo wp_kses_post(wpautop(esc_html($section['body']))); ?></div><?php endif; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        <?php elseif ($sections) : ?>
            <div class="grid gap-10 md:grid-cols-2 md:gap-x-16 md:gap-y-14">
                <?php foreach ($sections as $index => $section) : ?>
                    <section class="border-t border-line pt-6">
                        <p class="text-5xl font-black leading-none tracking-[-0.03em] text-gold/50 md:text-6xl" aria-hidden="true"><?php echo esc_html($pad($index + 1)); ?></p>
                        <?php if ($section['heading'] !== '') : ?><h2 class="mt-4 text-2xl font-black leading-tight tracking-[-0.015em] text-ink md:text-3xl"><?php echo esc_html($section['heading']); ?></h2><?php endif; ?>
                        <?php if ($section['body'] !== '') : ?><div class="boblift-story-prose mt-4"><?php echo wp_kses_post(wpautop(esc_html($section['body']))); ?></div><?php endif; ?>
                    </section>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($show_content) : ?>
            <div class="boblift-story-prose max-w-[760px] <?php echo $sections ? 'mt-14 md:mt-20' : ''; ?>"><?php echo wp_kses_post($content); ?></div>
        <?php endif; ?>

        <?php if ($extra_photos) : ?>
            <section class="mt-14 md:mt-20">
                <h2 class="text-2xl font-black tracking-[-0.015em] text-ink md:text-3xl"><?php echo esc_html($ui['sitePhotosLabel'] ?? 'Site photos'); ?></h2>
                <div class="mt-6 grid grid-cols-2 gap-3 md:gap-5 lg:grid-cols-3">
                    <?php foreach ($extra_photos as $photo) : ?>
                        <img class="aspect-[4/3] w-full rounded object-cover" src="<?php echo esc_url($photo['src']); ?>" alt="<?php echo esc_attr($photo['alt'] !== '' ? $photo['alt'] : $title); ?>" loading="lazy" decoding="async">
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <div class="mt-14 flex flex-col gap-4 rounded border border-line bg-mist p-5 sm:flex-row sm:items-center sm:justify-between md:mt-20 md:p-6">
            <p class="text-lg font-black leading-snug text-ink"><?php echo esc_html($ui['similarProjectPrompt'] ?? 'Planning a similar project?'); ?></p>
            <a class="btn-primary rounded-sm" href="<?php echo esc_url($quote_url); ?>" data-inquiry-modal-open data-inquiry-source="案例详情"<?php echo $product_name !== '' ? ' data-inquiry-product="' . esc_attr($product_name) . '"' : ''; ?>><?php echo esc_html($ui['discussSimilarLabel'] ?? 'Discuss Similar Project'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4 text-gold'); ?></a>
        </div>
    </div>

    <?php if ($related_query->have_posts()) : ?>
        <section class="border-t border-line py-14 md:py-20">
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
