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
// Media (photos, video, embeds), markup the plain-text sections cannot carry (links, tables, lists) and anchors that shared
// links may point at (id or name attributes) are never duplicate content, so they keep the body visible.
$content_has_media = (bool) preg_match('/<(img|figure|video|audio|iframe|picture|embed|object|table|ul|ol)\b|<a\s[^>]*href=|<[a-z][^>]*\s(?:id|name)\s*=/i', $content);
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
// Section text is plain text; a paragraph whose lines all start "1. ", "2. " ... becomes a numbered list.
$section_html = static function (string $body): string {
    $html = '';
    foreach (preg_split('/\n\s*\n/', trim($body)) as $paragraph) {
        $lines = array_values(array_filter(array_map('trim', explode("\n", $paragraph)), 'strlen'));
        $items = array_filter($lines, static fn(string $line): bool => (bool) preg_match('/^\d+\.\s/', $line));
        if ($items && count($items) === count($lines) - (preg_match('/^\d+\.\s/', $lines[0]) ? 0 : 1)) {
            $lead = preg_match('/^\d+\.\s/', $lines[0]) ? '' : '<p>' . esc_html(array_shift($lines)) . '</p>';
            $html .= $lead . '<ol>' . implode('', array_map(static fn(string $line): string => '<li>' . esc_html((string) preg_replace('/^\d+\.\s+/', '', $line)) . '</li>', $lines)) . '</ol>';
        } else {
            $html .= wpautop(esc_html($paragraph));
        }
    }
    return wp_kses_post($html);
};
$image = get_the_post_thumbnail_url($post_id, 'full') ?: '';
// The cover's own alt text and caption: AI covers say so ("Illustration: ..."), so the page must not relabel them as a photo.
$image_id = (int) get_post_thumbnail_id($post_id);
$image_alt = trim((string) get_post_meta($image_id, '_wp_attachment_image_alt', true)) ?: $title;
$image_caption = $image_id ? trim((string) wp_get_attachment_caption($image_id)) : '';
$image_caption = $image_caption !== '' && function_exists('boblift_localized_static_text') ? boblift_localized_static_text($image_caption) : $image_caption;
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
$case_products = boblift_item_related_products($post_id);
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
// Related: other application cases first; with only a handful of those, customer feedback fills the row.
$related_cards = [];
while ($related_query->have_posts()) {
    $related_query->the_post();
    $related_type = trim((string) $localize_case_fragments((string) get_post_meta(get_the_ID(), '_type', true)));
    $related_cards[] = [
        'url' => get_permalink(),
        'title' => (string) $localize_case_fragments(get_the_title()),
        'image' => boblift_post_thumbnail_url(get_post(), boblift_asset('images/factory-workshop.jpg')),
        'label' => $localize_case_text($related_type !== '' ? $related_type : (string) ($ui['defaultTypeLabel'] ?? 'Customer Case')),
    ];
}
wp_reset_postdata();
if (count($related_cards) < 3 && function_exists('boblift_feedback_items')) {
    $feedback_label = (string) (boblift_site_ui('casesPage')['customerFeedbackLabel'] ?? 'Customer Feedback');
    $feedback_pool = boblift_feedback_items();
    // Same product first, so the filler stays relevant.
    usort($feedback_pool, static fn(array $a, array $b): int => (int) ($b['model'] !== '' && $b['model'] === $product_name) <=> (int) ($a['model'] !== '' && $a['model'] === $product_name));
    foreach (array_slice($feedback_pool, 0, 3 - count($related_cards)) as $item) {
        $related_cards[] = [
            'url' => $item['url'],
            'title' => trim($item['model'] . ($item['note'] !== '' ? ' ' . $item['note'] : '')) . ($item['country'] !== '' ? ' · ' . $item['country'] : ''),
            'image' => $item['cover'],
            'label' => $feedback_label,
        ];
    }
}
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
        <?php // Byline: the person who reviews every case page (E-E-A-T). Shown under the summary here on phones and in the side card on desktop. ?>
        <?php
        $byline_name = (string) ($ui['bylineName'] ?? 'Achord Chan');
        ob_start();
        ?>
        <div class="flex items-start gap-3">
            <img class="size-12 shrink-0 rounded-sm bg-mist object-cover" src="<?php echo esc_url(boblift_asset('images/authors/achord-chan.webp')); ?>" alt="<?php echo esc_attr($byline_name); ?>" width="48" height="48" loading="lazy" decoding="async">
            <span class="min-w-0 leading-tight">
                <span class="flex flex-wrap items-baseline gap-x-2">
                    <span class="text-xs text-slate-400"><?php echo esc_html($ui['bylineLabel'] ?? 'Written by'); ?></span>
                    <a class="text-sm font-black text-ink transition hover:text-blue-700" href="<?php echo esc_url(boblift_url('/about')); ?>"><?php echo esc_html($byline_name); ?></a>
                </span>
                <span class="mt-1 block text-xs text-slate-500"><?php echo esc_html($ui['bylineTitle'] ?? 'Product Operations Director, BOB-LIFT'); ?></span>
                <span class="mt-1 block text-xs text-slate-400"><?php echo esc_html($ui['bylineUpdatedLabel'] ?? 'Updated'); ?> <time class="text-slate-600 [font-variant-numeric:tabular-nums]" datetime="<?php echo esc_attr(get_the_modified_date('c')); ?>"><?php echo esc_html(get_the_modified_date('Y-m-d')); ?></time></span>
            </span>
        </div>
        <?php $byline_html = (string) ob_get_clean(); ?>
        <div class="mt-6 md:mt-8">
            <p class="boblift-kicker boblift-case-kicker"><?php echo esc_html($case_label); ?></p>
            <h1 class="mt-3 text-[2.125rem] font-black leading-[1.06] tracking-[-0.025em] text-ink md:text-5xl"><?php echo esc_html($title); ?></h1>
            <div class="lg:hidden">
                <?php if ($summary !== '') : ?>
                    <p class="mt-4 max-w-[720px] text-base font-medium leading-7 text-slate-600 md:text-lg md:leading-8"><?php echo esc_html($summary); ?></p>
                <?php endif; ?>
                <div class="mt-5"><?php echo $byline_html; ?></div>
            </div>
        </div>
    </header>

    <div class="page-shell mt-8 md:mt-10">
        <?php if ($image !== '') : ?>
            <?php // The whole cover is shown (object-contain); on wide screens a blurred copy fills the space beside it instead of cropping it. ?>
            <figure class="boblift-case-hero relative m-0 overflow-hidden bg-[#0B1B33]<?php echo $display_facts ? ' rounded-t' : ' rounded'; ?>">
                <div class="boblift-feedback-backdrop" style="background-image:url('<?php echo esc_url($image); ?>')" aria-hidden="true"></div>
                <img class="relative block aspect-[3/2] w-full object-contain md:aspect-auto md:h-[clamp(420px,62vh,760px)]" src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($image_alt); ?>" loading="eager" fetchpriority="high" decoding="async">
                <?php if ($image_caption !== '') : ?><figcaption class="absolute bottom-3 start-3 max-w-[calc(100%-1.5rem)] rounded-sm bg-[#071f42]/80 px-2.5 py-1 text-xs font-bold text-white"><?php echo esc_html($image_caption); ?></figcaption><?php endif; ?>
            </figure>
        <?php endif; ?>
        <?php if ($display_facts) : ?>
            <dl class="boblift-case-facts grid grid-cols-1 overflow-hidden sm:grid-cols-2 border-l border-t border-line <?php echo $image !== '' ? 'rounded-b border-t-0' : 'rounded'; ?> <?php echo [1 => 'lg:grid-cols-1', 2 => 'lg:grid-cols-2', 3 => 'lg:grid-cols-3'][count($display_facts)] ?? 'lg:grid-cols-4'; ?>" aria-label="<?php echo esc_attr($ui['factsTitle'] ?? 'Case Facts'); ?>">
                <?php foreach ($display_facts as $fact) : ?>
                    <div class="border-b border-r border-line flex items-baseline justify-between gap-4 px-4 py-3 sm:block sm:py-4 md:px-6 md:py-5 sm:[&:last-child:nth-child(odd)]:col-span-2 lg:[&:last-child:nth-child(odd)]:col-span-1">
                        <dt class="shrink-0 text-[11px] font-black uppercase tracking-[0.16em] text-slate-400"><?php echo esc_html($fact['label']); ?></dt>
                        <dd class="min-w-0 text-end text-[0.9375rem] font-black text-ink sm:mt-1 sm:text-start sm:text-base md:text-lg"><?php echo esc_html($fact['value']); ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        <?php endif; ?>
    </div>

    <div class="page-shell py-14 md:py-20">
        <div class="boblift-case-body">
        <div class="boblift-case-main min-w-0">
        <?php if ($sections && $section_images) : ?>
            <div class="grid gap-14 md:gap-20">
                <?php foreach ($sections as $index => $section) : $photo = $section_images[$index] ?? null; ?>
                    <section class="grid items-center gap-6 <?php echo $photo ? 'lg:grid-cols-2 lg:gap-12' : 'max-w-[760px]'; ?>">
                        <?php if ($photo) : ?>
                            <img class="aspect-[4/3] w-full rounded object-cover <?php echo $index % 2 ? 'lg:order-2' : ''; ?>" src="<?php echo esc_url($photo['src']); ?>" alt="<?php echo esc_attr($photo['alt'] !== '' ? $photo['alt'] : $section['heading']); ?>" loading="lazy" decoding="async">
                        <?php endif; ?>
                        <div>
                            <p class="text-xs font-black tabular-nums tracking-[0.14em] text-blue-700" aria-hidden="true"><?php echo esc_html($pad($index + 1)); ?></p>
                            <?php if ($section['heading'] !== '') : ?><h2 class="mt-2 text-2xl font-black leading-tight tracking-[-0.015em] text-ink md:text-3xl"><?php echo esc_html($section['heading']); ?></h2><?php endif; ?>
                            <?php if ($section['body'] !== '') : ?><div class="boblift-story-prose mt-4"><?php echo $section_html($section['body']); ?></div><?php endif; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        <?php elseif ($sections) : ?>
            <div class="grid gap-10 md:gap-12">
                <?php foreach ($sections as $index => $section) : ?>
                    <section class="max-w-[760px] border-t border-line pt-6">
                        <p class="text-xs font-black tabular-nums tracking-[0.14em] text-blue-700" aria-hidden="true"><?php echo esc_html($pad($index + 1)); ?></p>
                        <?php if ($section['heading'] !== '') : ?><h2 class="mt-2 text-2xl font-black leading-tight tracking-[-0.015em] text-ink md:text-3xl"><?php echo esc_html($section['heading']); ?></h2><?php endif; ?>
                        <?php if ($section['body'] !== '') : ?><div class="boblift-story-prose mt-4"><?php echo $section_html($section['body']); ?></div><?php endif; ?>
                    </section>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php // The products this case used, linked back to their pages (product pages already list their cases). ?>
        <?php // Desktop shows them in the Project Support card beside the story; phones get the cards here. ?>
        <?php if ($case_products) : ?>
            <div class="mt-14 md:mt-20 lg:hidden"><?php boblift_render_item_products($case_products, (string) ($ui['caseProductsTitle'] ?? 'Products in this case')); ?></div>
        <?php endif; ?>

        <?php
        // A body made only of "heading + list of links" blocks (the related links under a case) is shown as a link panel, not as prose.
        $resource_blocks = [];
        if ($show_content && preg_match_all('#<h2>([^<]*)</h2>\s*<ul>((?:\s*<li>[^<]*<a [^>]*>[^<]*</a>\s*</li>)+\s*)</ul>#i', $content, $blocks, PREG_SET_ORDER)) {
            // Converted only when the whole body is plain heading + link-list blocks; anything else (media, ids, other
            // markup, list items that are not a single link) keeps the normal prose rendering so nothing is lost.
            $leftover = trim((string) preg_replace('#<h2>([^<]*)</h2>\s*<ul>((?:\s*<li>[^<]*<a [^>]*>[^<]*</a>\s*</li>)+\s*)</ul>#i', '', $content));
            foreach ($blocks as $block) {
                $item_count = preg_match_all('#<li\b#i', $block[2]);
                // Only links the importer writes (href, optionally target="_blank" rel="noopener"); a link with any other
                // attribute (id, download, ...) keeps the body in the prose renderer so its behaviour is not lost.
                preg_match_all('#<li>\s*<a href="([^"]*)"( target="_blank" rel="noopener")?>([^<]*)</a>\s*</li>#i', $block[2], $items, PREG_SET_ORDER);
                if ($leftover !== '' || $item_count === 0 || count($items) !== $item_count) {
                    $resource_blocks = [];
                    break;
                }
                $resource_blocks[] = [
                    'heading' => wp_strip_all_tags($block[1]),
                    'links' => array_map(static fn(array $item): array => ['href' => html_entity_decode($item[1]), 'label' => html_entity_decode($item[3]), 'external' => ($item[2] ?? '') !== ''], $items),
                ];
            }
        }
        ?>
        <?php if ($resource_blocks) : ?>
            <section class="boblift-case-resources mt-14 grid gap-8 rounded border border-line bg-white p-5 shadow-card md:mt-20 md:p-8 <?php echo count($resource_blocks) > 1 ? 'xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)] xl:gap-10' : ''; ?>">
                <?php foreach ($resource_blocks as $block_index => $block) : ?>
                    <div class="min-w-0">
                        <h2 class="text-lg font-black tracking-[-0.01em] text-ink"><?php echo esc_html($block['heading']); ?></h2>
                        <ul class="mt-4 grid gap-2 <?php echo $block_index === 0 ? 'sm:grid-cols-2' : ''; ?>">
                            <?php foreach ($block['links'] as $link) : ?>
                                <li><a class="group flex h-full items-center justify-between gap-3 rounded-sm border border-line px-4 py-3 text-sm font-bold leading-snug text-ink transition hover:border-blue-300 hover:text-blue-700" href="<?php echo esc_url($link['href']); ?>"<?php echo $link['external'] ? ' target="_blank" rel="noopener"' : ''; ?>><span><?php echo esc_html($link['label']); ?></span><?php echo boblift_icon($link['external'] ? 'arrow-up-right' : 'arrow-right', 'h-4 w-4 shrink-0 text-slate-400 transition group-hover:text-blue-600 rtl:-scale-x-100'); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php elseif ($show_content) : ?>
            <div class="boblift-story-prose max-w-[760px] <?php echo $sections ? 'mt-14 md:mt-20' : ''; ?>"><?php echo wp_kses_post($content); ?></div>
        <?php endif; ?>

        <?php if ($extra_photos) : ?>
            <section class="mt-14 md:mt-20">
                <h2 class="text-2xl font-black tracking-[-0.015em] text-ink md:text-3xl"><?php echo esc_html($ui['sitePhotosLabel'] ?? 'Site photos'); ?></h2>
                <div class="mt-6 grid grid-cols-2 gap-3 md:gap-5">
                    <?php foreach ($extra_photos as $photo) : ?>
                        <img class="aspect-[4/3] w-full rounded object-cover" src="<?php echo esc_url($photo['src']); ?>" alt="<?php echo esc_attr($photo['alt'] !== '' ? $photo['alt'] : $title); ?>" loading="lazy" decoding="async">
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        </div>

        <?php // Desktop keeps the inquiry beside the story; phones get it as a band after the text. ?>
        <aside class="boblift-case-aside">
            <div class="boblift-case-aside-card rounded border border-line bg-white p-6 shadow-card">
                <?php if ($summary !== '') : ?>
                    <p class="boblift-kicker"><?php echo esc_html($ui['summaryLabel'] ?? 'Case Summary'); ?></p>
                    <p class="mt-3 text-[0.9375rem] leading-7 text-slate-700"><?php echo esc_html($summary); ?></p>
                <?php endif; ?>
                <div class="mt-5 border-t border-line pt-5"><?php echo $byline_html; ?></div>
                <div class="my-6 border-t border-line"></div>
                <p class="boblift-kicker"><?php echo esc_html($ui['projectSupportLabel'] ?? 'Project Support'); ?></p>
                <p class="mt-3 text-xl font-black leading-snug text-ink"><?php echo esc_html($ui['similarProjectPrompt'] ?? 'Planning a similar project?'); ?></p>
                <?php if ($case_products) : ?>
                    <ul class="mt-4 grid gap-2">
                        <?php foreach ($case_products as $case_product) :
                            $case_product_title = get_the_title($case_product);
                            $case_product_model = (string) get_post_meta(boblift_product_english_id($case_product->ID), '_model', true);
                            $case_product_specs = array_slice(array_values(array_filter(boblift_decode_meta(get_post_meta($case_product->ID, '_specs', true), []), static fn($row): bool => is_array($row) && trim((string) ($row['value'] ?? '')) !== '')), 0, 1);
                            ?>
                            <li>
                                <a class="group grid grid-cols-[4.5rem_minmax(0,1fr)] items-center gap-3 rounded-sm border border-line p-2 transition hover:border-blue-300" href="<?php echo esc_url(get_permalink($case_product)); ?>">
                                    <span class="block aspect-[4/3] overflow-hidden rounded-sm bg-mist"><img class="h-full w-full object-contain p-1" src="<?php echo esc_url(boblift_post_thumbnail_url($case_product, boblift_asset('images/products/crane-accessories.png'))); ?>" alt="" loading="lazy" decoding="async"></span>
                                    <span class="min-w-0">
                                        <span class="block text-sm font-black leading-tight text-ink transition group-hover:text-blue-700"><?php echo esc_html($case_product_model !== '' ? $case_product_model : $case_product_title); ?></span>
                                        <span class="mt-0.5 line-clamp-1 block text-xs text-slate-500"><?php echo esc_html($case_product_specs ? $case_product_specs[0]['label'] . ' ' . $case_product_specs[0]['value'] : $case_product_title); ?></span>
                                        <span class="mt-1 inline-flex items-center gap-1 text-xs font-black text-blue-700"><?php echo esc_html($ui['viewProductLabel'] ?? 'View product'); ?> <?php echo boblift_icon('arrow-right', 'h-3.5 w-3.5 rtl:-scale-x-100'); ?></span>
                                    </span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php elseif ($product_name !== '') : ?><p class="mt-2 text-sm leading-6 text-slate-600"><?php echo esc_html($product_name); ?></p><?php endif; ?>
                <a class="btn-primary mt-5 w-full justify-center rounded-sm" href="<?php echo esc_url($quote_url); ?>" data-inquiry-modal-open data-inquiry-source="案例详情"<?php echo $product_name !== '' ? ' data-inquiry-product="' . esc_attr($product_name) . '"' : ''; ?>><?php echo esc_html($ui['discussSimilarLabel'] ?? 'Discuss Similar Project'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4 text-gold'); ?></a>
                <a class="mt-4 inline-flex items-center gap-1.5 text-sm font-black text-blue-700 transition hover:text-ink" href="<?php echo esc_url($cases_url); ?>"><?php echo boblift_icon('chevron-left', 'h-4 w-4 rtl:-scale-x-100'); ?><?php echo esc_html($ui['backLabel'] ?? 'Back to Case Center'); ?></a>
            </div>
        </aside>
        </div>

        <div class="mt-14 flex flex-col gap-4 rounded border border-line bg-mist p-5 sm:flex-row sm:items-center sm:justify-between md:mt-20 md:p-6 lg:hidden">
            <p class="text-lg font-black leading-snug text-ink"><?php echo esc_html($ui['similarProjectPrompt'] ?? 'Planning a similar project?'); ?></p>
            <a class="btn-primary rounded-sm" href="<?php echo esc_url($quote_url); ?>" data-inquiry-modal-open data-inquiry-source="案例详情"<?php echo $product_name !== '' ? ' data-inquiry-product="' . esc_attr($product_name) . '"' : ''; ?>><?php echo esc_html($ui['discussSimilarLabel'] ?? 'Discuss Similar Project'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4 text-gold'); ?></a>
        </div>
    </div>

    <?php if ($related_cards) : ?>
        <section class="border-t border-line py-14 md:py-20">
            <div class="page-shell">
                <div class="mb-6 flex items-end justify-between gap-4 md:mb-8">
                    <h2 class="text-2xl font-black tracking-[-0.015em] text-ink md:text-3xl"><?php echo esc_html($ui['relatedCasesLabel'] ?? 'Related Cases'); ?></h2>
                    <a class="inline-flex shrink-0 items-center gap-1.5 text-sm font-black text-blue-700 transition hover:text-ink" href="<?php echo esc_url($cases_url); ?>"><?php echo esc_html($ui['viewAllLabel'] ?? 'View all'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
                </div>
                <div class="boblift-case-related grid gap-5 md:grid-cols-3">
                    <?php foreach ($related_cards as $card) : ?>
                        <a class="group block overflow-hidden rounded border border-line bg-white transition hover:border-blue-300" href="<?php echo esc_url($card['url']); ?>">
                            <span class="block aspect-[4/3] overflow-hidden bg-[#EDF1F5]">
                                <?php if ($card['image'] !== '') : ?><img class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.035]" src="<?php echo esc_url($card['image']); ?>" alt="<?php echo esc_attr($card['title']); ?>" loading="lazy" decoding="async"><?php endif; ?>
                            </span>
                            <span class="block p-5">
                                <span class="boblift-kicker block"><?php echo esc_html($card['label']); ?></span>
                                <span class="mt-2 line-clamp-2 block text-lg font-black leading-snug text-ink transition group-hover:text-blue-700"><?php echo esc_html($card['title']); ?></span>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
</article>

<?php get_footer(); ?>
