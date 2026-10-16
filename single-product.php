<?php
$queried_product = get_queried_object();
if ($queried_product instanceof WP_Post && $queried_product->post_type === 'product' && post_password_required($queried_product)) {
    // Protected product: only the standard WordPress password form, before any specification, drawing or image is read.
    get_header();
    echo '<section class="page-shell py-16 md:py-24"><div class="mx-auto max-w-xl">' . get_the_password_form($queried_product) . '</div></section>';
    get_footer();
    return;
}
get_header();
$product = boblift_find_product(get_post_field('post_name', get_queried_object_id()) ?: get_query_var('product'));
if (!$product && have_posts()) {
    the_post();
    $product = boblift_product_from_post(get_post());
}
if (!$product) {
    status_header(404);
    echo '<section class="page-shell py-20"><h1 class="text-4xl font-black text-navy">' . esc_html((function_exists('boblift_site_ui') ? (boblift_site_ui('productDetail')['notFoundTitle'] ?? 'Product not found') : 'Product not found')) . '</h1></section>';
    get_footer();
    return;
}
$image_url = static function (string $image): string {
    return str_starts_with($image, 'http') ? $image : boblift_asset($image);
};
$main_image = boblift_product_image($product);
$inquiry_products_payload = wp_json_encode([[
    'id' => (string) ($product['id'] ?? ($product['slug'] ?? $product['title'])),
    'title' => (string) ($product['title'] ?? ''),
    'slug' => (string) ($product['slug'] ?? ''),
    'image' => (string) $main_image,
    'category' => (string) ($product['category_title'] ?? 'BOB-LIFT'),
    'detailUrl' => (string) ($product['detail_url'] ?? get_permalink()),
]], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]';
$inquiry_url = $product['inquiry_url'] ?? boblift_url('/contact.html#inquiry');
$contact_url = boblift_url('/contact');
$ui = boblift_site_ui('productDetail');
$hero_quote_list_add_label = (string) ($ui['heroQuoteListLabel'] ?? 'Quote List');
$hero_quote_list_active_label = (string) ($ui['heroQuoteListActiveLabel'] ?? 'In Quote List');
$hero_pdf_label = (string) ($ui['heroPdfLabel'] ?? 'PDF Catalog');
$product_title = (string) ($product['title'] ?? '');
$hero_model = trim((string) ($product['model'] ?? ''));
$hero_title = $hero_model !== '' ? $hero_model : $product_title;
$show_hero_full_name = $hero_model !== '' && strcasecmp($hero_model, $product_title) !== 0;
$specs = $product['specs'] ?? [];
$features = $product['features'] ?? [];
if (!$features) {
    $features = is_array($ui['fallbackFeatures'] ?? null) ? $ui['fallbackFeatures'] : [];
}
$spec_icon = static function (array $spec): string {
    $key = (string) ($spec['key'] ?? '');
    if ($key === '') return 'layers';
    $normalized = strtolower($key);
    if (str_contains($normalized, 'capacity') || str_contains($normalized, 'lift')) return 'weight';
    if (str_contains($normalized, 'boomlength') || str_contains($normalized, 'boom_length')) return 'maximize-2';
    if (str_contains($normalized, 'rotation')) return 'rotate-cw';
    if (str_contains($normalized, 'boomtype') || str_contains($normalized, 'boom_type')) return 'wrench';
    return 'layers';
};
// The hero strip takes the first four short values; long ones (two mast heights) stay in the spec table.
$hero_specs = array_slice(array_values(array_filter($specs, static fn($spec): bool => is_array($spec) && ($spec['label'] ?? '') !== '' && ($spec['value'] ?? '') !== '' && mb_strlen((string) $spec['value']) <= 16)), 0, 4);
$review_status = (string) ($product['parameter_review_status'] ?? 'verified-from-source');
$review_status_labels = is_array($ui['statusLabels'] ?? null) ? $ui['statusLabels'] : [];
$review_status_label = (string) ($review_status_labels[$review_status] ?? ($review_status_labels['verified-from-source'] ?? 'Verified from source'));
if (!$hero_specs) {
    $hero_specs = [['key' => 'specificationStatus', 'label' => (string) ($ui['specLabels']['specificationStatus'] ?? 'Specification Status'), 'value' => $review_status_label]];
}
$hero_spec_value = static function (string $value): string {
    $normalized = strtolower(trim($value));
    $short_values = [
        'narrow or complex working spaces' => 'Narrow Spaces',
        'adjustable spider legs' => 'Adjustable Legs',
    ];
    return $short_values[$normalized] ?? $value;
};
$technical_specs = array_values(array_filter(array_merge(
    $hero_model !== '' ? [['label' => (string) ($ui['specLabels']['model'] ?? 'Model'), 'value' => $hero_model]] : [],
    $specs
), static fn($spec): bool => is_array($spec) && trim((string) ($spec['label'] ?? '')) !== '' && trim((string) ($spec['value'] ?? '')) !== ''));
$drawings = array_values(array_filter($product['drawings'] ?? [], static fn($drawing): bool => is_array($drawing) && ($drawing['image'] ?? '') !== ''));
$brochure_features = get_post_meta((int) ($product['id'] ?? 0), '_features_source', true) === 'brochure'
    ? array_values(array_filter($product['features'] ?? [], static fn($feature): bool => is_array($feature) && trim((string) ($feature['title'] ?? '')) !== ''))
    : [];
$gallery = array_values(array_filter($product['gallery'] ?? []));
if (!$gallery) {
    $gallery = [$main_image];
}
$gallery_items = array_map(static function ($item, $index) use ($image_url, $product, $ui) {
    $src = is_string($item) ? $item : ($item['image'] ?? $item['url'] ?? '');
    $default_label = $index === 0 ? (string) ($ui['mainProductViewLabel'] ?? 'Main product view') : (string) ($ui['productPhotoLabel'] ?? 'Product photo');
    return ['image' => $image_url((string) $src), 'label' => is_array($item) && !empty($item['label']) ? (string) $item['label'] : $default_label];
}, $gallery, array_keys($gallery));
$detail_images = array_values(array_filter($product['detail_images'] ?? []));
$has_configured_detail_images = $detail_images !== [];
$detail_image_items = array_map(static function ($item, $index) use ($image_url, $product, $ui) {
    $src = is_string($item) ? $item : ($item['image'] ?? $item['url'] ?? '');
    $default_label = str_replace('{product}', (string) ($product['title'] ?? ''), (string) ($ui['detailImageAltTemplate'] ?? '{product} detail image'));
    return ['image' => $image_url((string) $src), 'label' => is_array($item) && !empty($item['label']) ? (string) $item['label'] : $default_label];
}, $detail_images, array_keys($detail_images));
if (!$has_configured_detail_images) {
    $photo_items = [];
    foreach ($gallery_items as $item) {
        $label = strtolower((string) ($item['label'] ?? ''));
        if (preg_match('/\b(parameter|chart|dimension|specification)\b/', $label)) {
            $detail_image_items[] = $item;
            continue;
        }
        $photo_items[] = $item;
    }
    $gallery_items = $photo_items;
}
if (!$gallery_items) {
    $gallery_items = [['image' => $main_image, 'label' => (string) ($ui['mainProductViewLabel'] ?? 'Main product view')]];
}
$show_detail_images = $detail_image_items !== [];
$hotspot_labels = is_array($ui['hotspotLabels'] ?? null) ? $ui['hotspotLabels'] : ['Telescopic Boom', 'Hydraulic Cylinder', 'Outriggers', 'Control System', 'Slewing Mechanism', 'Truck Mounting Base'];
$default_hotspot_positions = [[58, 18], [65, 36], [48, 85], [66, 55]];
$hotspots = [];
foreach (($product['hotspots'] ?? []) as $hotspot) {
    if (!is_array($hotspot) || trim((string) ($hotspot['label'] ?? '')) === '') continue;
    $hotspots[] = [
        'label' => (string) $hotspot['label'],
        'x' => max(0, min(100, (float) ($hotspot['x'] ?? 50))),
        'y' => max(0, min(100, (float) ($hotspot['y'] ?? 50))),
    ];
}
if (!$hotspots) {
    foreach (array_slice($hotspot_labels, 0, 4) as $index => $label) {
        $position = $default_hotspot_positions[$index] ?? [50, 50];
        $hotspots[] = ['label' => (string) $label, 'x' => $position[0], 'y' => $position[1]];
    }
}
$hero_image = $main_image;
// Cut-out product images float on the hero; a site photo (factory or brochure) sits in a framed card
// instead, without the part hotspots that are positioned for cut-outs.
$hero_is_cutout = boblift_image_fit($hero_image) === 'cutout';
// Cut-outs stand on the floor line with only a thin margin, so they fill as much of the stage as their shape allows.
$hero_cutout_class = 'object-contain object-bottom px-3 pt-3 pb-6 sm:px-4 sm:pt-4 sm:pb-8 lg:px-6 lg:pt-6 lg:pb-10';
// Photos are never cropped: the whole photo shows on a plain mat, since their shapes (square, 3:2, portrait) vary.
$hero_photo_class = 'object-contain bg-[#EDF1F5]';
$hero_image_class = $hero_is_cutout ? $hero_cutout_class : $hero_photo_class;
// Part hotspots only when an editor placed them for this product; the generic defaults do not match the image.
$has_custom_hotspots = !empty($product['hotspots']);
$hero_summary = trim((string) ($product['short_description'] ?? ''));
$former_models = trim((string) ($product['model_aliases'] ?? ''));
// "2950 kg" -> ["2950", "kg"] so the hero can set the number large and the unit small.
$hero_spec_parts = static function (string $value): array {
    $value = trim($value);
    if (preg_match('/^([\d.,+]+)\s*(kg|t·m|kN·m|kW|L\/min|MPa|mm|m|t|°)$/u', $value, $m)) {
        return [$m[1], $m[2]];
    }
    return [$value, ''];
};
$hero_gallery_items = [];
$hero_gallery_seen = [];
$add_hero_gallery_item = static function (string $image, string $label) use (&$hero_gallery_items, &$hero_gallery_seen): void {
    $image = trim($image);
    if ($image === '') return;
    $key = strtolower($image);
    if (isset($hero_gallery_seen[$key])) return;
    $hero_gallery_seen[$key] = true;
    $hero_gallery_items[] = ['image' => $image, 'label' => $label, 'fit' => boblift_image_fit($image)];
};
$add_hero_gallery_item($hero_image, (string) ($ui['mainProductViewLabel'] ?? 'Main product view'));
foreach ($gallery_items as $item) {
    $add_hero_gallery_item((string) ($item['image'] ?? ''), (string) ($item['label'] ?? ($ui['productPhotoLabel'] ?? 'Product photo')));
}
// Photos saved as colour variants (_color_variants) join the gallery with their labels, so they stay reachable now that the colour switcher is gone.
foreach (boblift_decode_meta(get_post_meta((int) ($product['id'] ?? 0), '_color_variants', true), []) as $variant) {
    if (!is_array($variant)) continue;
    $variant_image = trim((string) ($variant['image'] ?? $variant['url'] ?? ''));
    if ($variant_image === '') continue;
    $add_hero_gallery_item(preg_match('#^(https?:)?//#i', $variant_image) || str_starts_with($variant_image, '/') ? $variant_image : boblift_asset($variant_image), trim((string) boblift_localize_product_text_fragments((string) ($variant['label'] ?? ''))) ?: (string) ($ui['productPhotoLabel'] ?? 'Product photo'));
}
$faqs = is_array($ui['faqItems'] ?? null) ? $ui['faqItems'] : [];
$faqs = array_values(array_filter(array_map(static function ($faq): ?array {
    if (!is_array($faq)) return null;
    $question = (string) ($faq['question'] ?? '');
    $answer = (string) ($faq['answer'] ?? '');
    if (str_contains(strtolower($question), 'truck chassis')) {
        $question = 'Which configuration should we choose for this model?';
    }
    $answer = str_replace(
        ['truck chassis information', 'truck chassis', 'chassis details', 'chassis'],
        ['site access and delivery schedule', 'site access', 'site access details', 'site access'],
        $answer
    );
    return ['question' => $question, 'answer' => $answer];
}, $faqs)));
// Body text (正文) from the editor, only once an editor has switched it on: old-site copy stays hidden.
// Imported old-site copy stays hidden until a product is reviewed (saved after its import, or ticked in the editor); products that already had their own content keep showing it. See boblift_product_content_ready().
$detail_body_html = boblift_product_content_ready(get_the_ID(), '_detail_body_ready') ? trim((string) ($product['content'] ?? '')) : '';
// Text, or media-only bodies (images, galleries, embedded video), count as content.
$detail_body_html = trim(wp_strip_all_tags($detail_body_html)) !== '' || preg_match('/<(img|figure|video|audio|iframe|picture|embed|object)\b/i', $detail_body_html) ? $detail_body_html : '';
// Designed detail images (详情图), shown as a one-screen grid with a viewer, only once an editor has switched them on.
$detail_gallery = [];
if ($has_configured_detail_images && boblift_product_content_ready(get_the_ID(), '_detail_images_ready')) {
    foreach ($detail_images as $item) {
        $src = html_entity_decode((string) (is_string($item) ? $item : ($item['image'] ?? $item['url'] ?? '')));
        // Old imports point at the WebP converter ("webpc-passthru.php?src=<upload url>"): use the upload itself.
        if (preg_match('/[?&]src=([^&]+)/', $src, $match)) {
            $src = rawurldecode($match[1]);
        }
        // Match on the uploads path: the stored host can differ from this language's domain.
        // Only this site's own addresses (any language domain, relative or the uploads host) are rewritten; an image on another
        // host stays as saved unless the same file exists in this site's media library.
        if (preg_match('#/wp-content/uploads/(.+)$#', $src, $match)) {
            $local_src = trailingslashit(wp_get_upload_dir()['baseurl']) . $match[1];
            $src_host = strtolower((string) wp_parse_url($src, PHP_URL_HOST));
            $own_hosts = array_filter([strtolower((string) wp_parse_url(home_url(), PHP_URL_HOST)), strtolower((string) wp_parse_url(wp_get_upload_dir()['baseurl'], PHP_URL_HOST))]);
            foreach (function_exists('pll_languages_list') ? pll_languages_list() : [] as $language_slug) {
                $own_hosts[] = strtolower((string) wp_parse_url((string) (function_exists('pll_home_url') ? pll_home_url($language_slug) : ''), PHP_URL_HOST));
            }
            if ($src_host === '' || in_array($src_host, $own_hosts, true) || attachment_url_to_postid($local_src) > 0) {
                $src = $local_src;
            }
        }
        // Theme-relative paths (images/products/…) resolve against the theme assets, not the product page.
        if ($src !== '' && !preg_match('#^(https?:)?//#i', $src) && !str_starts_with($src, '/')) {
            $src = boblift_asset($src);
        }
        $attachment_id = $src !== '' ? attachment_url_to_postid($src) : 0;
        $full = $attachment_id ? wp_get_attachment_image_src($attachment_id, 'full') : false;
        $preview = $attachment_id ? wp_get_attachment_image_src($attachment_id, 'medium_large') : false;
        if ($src === '' || ($attachment_id && !$full)) {
            continue;
        }
        $detail_gallery[] = [
            'full' => $full ? (string) $full[0] : $src,
            'preview' => $preview ? (string) $preview[0] : ($full ? (string) $full[0] : $src),
            'width' => $full ? (int) $full[1] : 0,
            'height' => $full ? (int) $full[2] : 0,
        ];
    }
}
// Body layout: text before the first h3 is the lead (left); each h3 section stacks in the right column,
// so a short body fills the card width instead of one narrow column.
// Split only at top-level <h3> elements (DOM children), so headings nested inside Group or Columns blocks never cut a block in two.
$detail_body_lead = '';
$detail_body_sections = '';
if ($detail_body_html !== '') {
    $detail_body_lead = $detail_body_html;
    if (class_exists('DOMDocument') && stripos($detail_body_html, '<h3') !== false) {
        $detail_dom = new DOMDocument();
        $previous_libxml = libxml_use_internal_errors(true);
        $detail_dom->loadHTML('<?xml encoding="utf-8" ?><div id="boblift-detail-root">' . $detail_body_html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous_libxml);
        $detail_root = $detail_dom->getElementById('boblift-detail-root');
        if ($detail_root instanceof DOMElement) {
            $lead_html = '';
            $section_html = '';
            $in_sections = false;
            foreach (iterator_to_array($detail_root->childNodes) as $detail_node) {
                if (!$in_sections && $detail_node instanceof DOMElement && strtolower($detail_node->tagName) === 'h3') {
                    $in_sections = true;
                }
                if ($in_sections) {
                    $section_html .= $detail_dom->saveHTML($detail_node);
                } else {
                    $lead_html .= $detail_dom->saveHTML($detail_node);
                }
            }
            if ($section_html !== '') {
                $detail_body_lead = trim($lead_html);
                $detail_body_sections = $section_html;
            }
        }
    }
}
$render_detail_body = static function (string $wrapper_class) use ($detail_body_lead, $detail_body_sections): void {
    $split = $detail_body_lead !== '' && trim($detail_body_sections) !== '';
    ?>
    <div class="<?php echo esc_attr($wrapper_class . ($split ? ' grid gap-8 lg:grid-cols-12 lg:gap-12' : '')); ?>">
        <?php if ($detail_body_lead !== '') : ?>
            <div class="boblift-story-prose boblift-product-body boblift-product-lead <?php echo $split ? 'lg:col-span-5' : 'max-w-[900px]'; ?>"><?php echo wp_kses_post($detail_body_lead); ?></div>
        <?php endif; ?>
        <?php if (trim($detail_body_sections) !== '') : ?>
            <div class="boblift-story-prose boblift-product-body boblift-product-sections <?php echo $split ? 'lg:col-span-7' : ''; ?>"><?php echo wp_kses_post($detail_body_sections); ?></div>
        <?php endif; ?>
    </div>
    <?php
};
$detail_alt = str_replace('{product}', $product_title, (string) ($ui['detailImageAltTemplate'] ?? '{product} detail image'));
$anchors = [];
$anchors[] = ['id' => 'technical', 'label' => (string) ($ui['technicalLabel'] ?? 'Technical Specifications')];
if ($detail_body_html !== '' || $detail_gallery) {
    $anchors[] = ['id' => 'overview', 'label' => (string) ($ui['detailBodyLabel'] ?? 'Product details')];
}
if ($drawings) {
    $anchors[] = ['id' => 'drawing', 'label' => (string) ($ui['drawingLabel'] ?? 'Dimension Drawing')];
}
if ($brochure_features) {
    $anchors[] = ['id' => 'features', 'label' => (string) ($ui['featuresLabel'] ?? 'Key Features')];
}
// Manufacturing and delivery steps: knuckle or telescopic set, by the English product's category.
$process_slugs = wp_get_post_terms(boblift_product_english_id(get_the_ID()), 'product_category', ['fields' => 'slugs']);
$process_slugs = is_array($process_slugs) ? $process_slugs : [];
$process_sets = [
    'knuckle-boom-marine-cranes' => 'knuckle-marine',
    'telescopic-boom-marine-cranes' => 'telescopic-marine',
    'knuckle-boom-truck-mounted-cranes' => 'knuckle',
    'telescopic-boom-truck-mounted-cranes' => 'telescopic',
];
$process_set = $process_sets[current(array_intersect(array_keys($process_sets), $process_slugs)) ?: ''] ?? '';
$process_steps = $process_set !== '' ? array_values(array_filter((array) ($ui['processSteps'][$process_set] ?? []), static fn(mixed $step): bool => is_array($step) && trim((string) ($step['image'] ?? '')) !== '')) : [];
$video_ui = boblift_site_ui('videoCenter');
$series_video = boblift_product_series_video(get_the_ID());
if ($series_video) {
    $anchors[] = ['id' => 'video', 'label' => (string) ($video_ui['productVideoLabel'] ?? 'Product video')];
}
if ($process_steps) {
    $anchors[] = ['id' => 'process', 'label' => (string) ($ui['processLabel'] ?? 'Manufacturing & delivery')];
}
$cases_ui = boblift_site_ui('casesPage');
$product_cases = boblift_product_cases(get_the_ID());
$product_feedback = boblift_product_feedback(get_the_ID());
$product_feedback_total = $product_feedback ? boblift_product_feedback_count(get_the_ID()) : 0;
if ($product_cases) {
    $anchors[] = ['id' => 'cases', 'label' => (string) ($cases_ui['sceneCasesLabel'] ?? 'Application Cases')];
}
if ($product_feedback) {
    $anchors[] = ['id' => 'feedback', 'label' => (string) ($cases_ui['customerFeedbackLabel'] ?? 'Customer Feedback')];
}
$anchors[] = ['id' => 'faq', 'label' => (string) ($ui['faqLabel'] ?? 'FAQ')];
$anchors[] = ['id' => 'quote', 'label' => (string) ($ui['quoteAnchorLabel'] ?? 'Request Quote')];
$quote_title = (string) ($ui['quoteTitle'] ?? 'Need a crane matched to your site and lifting task?');
if (str_contains(strtolower($quote_title), 'truck crane')) {
    $quote_title = 'Need a crane matched to your site and lifting task?';
}
$quote_description = (string) ($ui['quoteDescription'] ?? 'Send your working radius, load requirement, site access, destination country, and delivery schedule. BOB-LIFT will help match a practical configuration.');
if (str_contains(strtolower($quote_description), 'truck chassis')) {
    $quote_description = 'Send your working radius, load requirement, site access, destination country, and delivery schedule. BOB-LIFT will help match a practical configuration.';
}
?>
<div class="boblift-product-cinema bg-[#F5F7FB] text-[#0B1B33]">
    <section id="product-hero" class="boblift-cinema-hero relative overflow-hidden border-b border-[#DDE7F2]">
        <div class="pointer-events-none absolute inset-0 boblift-cinema-grid opacity-40"></div>
        <div class="page-shell relative z-10 py-6 md:py-8 lg:py-10">
            <div class="grid grid-cols-1 items-center gap-6 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)] lg:gap-10 xl:gap-14">
                <div class="relative z-20 order-2 min-w-0 lg:order-1">
                    <p class="boblift-product-hero-kicker text-[11px] font-black uppercase tracking-[0.22em] text-[#0B3F7D]"><?php echo esc_html($product['category_title'] ?? 'BOB-LIFT Product'); ?></p>
                    <h1 class="boblift-product-hero-title mt-3 text-[clamp(44px,4.4vw,84px)] font-black leading-[0.92] tracking-[-0.02em] text-[#071f42]"><?php echo esc_html($hero_title); ?></h1>
                    <?php if ($show_hero_full_name) : ?>
                        <p class="mt-3 text-[clamp(19px,1.45vw,27px)] font-black leading-tight tracking-[-0.01em] text-[#0B3F7D]"><?php echo esc_html($product_title); ?></p>
                    <?php endif; ?>
                    <?php boblift_render_model_decode($hero_model); ?>
                    <?php if ($hero_summary !== '') : ?>
                        <p class="mt-4 max-w-[620px] text-sm font-semibold leading-7 text-slate-600 md:text-[15px]"><?php echo esc_html($hero_summary); ?></p>
                    <?php endif; ?>
                    <dl class="mt-6 grid grid-cols-2 border-y border-[#D5DEE9] sm:grid-cols-4 lg:mt-7">
                        <?php foreach ($hero_specs as $index => $spec) :
                            [$spec_number, $spec_unit] = $hero_spec_parts((string) ($spec['value'] ?? ''));
                        ?>
                            <div class="flex min-w-0 flex-col py-4 pr-3 <?php echo $index % 2 === 1 ? 'border-l border-[#D5DEE9] ps-4' : ''; ?> <?php echo $index >= 2 ? 'border-t border-[#D5DEE9] sm:border-t-0' : ''; ?> <?php echo $index === 2 ? 'sm:border-l sm:ps-4' : ''; ?>">
                                <dt class="text-[10px] font-black uppercase leading-4 tracking-[0.12em] text-slate-500"><?php echo esc_html($spec['label']); ?></dt>
                                <dd class="mt-auto break-words pt-1.5 font-black leading-tight text-[#071f42] [font-variant-numeric:tabular-nums]"><span class="<?php echo ($spec_unit !== '' || mb_strlen($spec_number) <= 8) ? 'text-[clamp(22px,1.9vw,32px)]' : 'text-base'; ?>"><?php echo esc_html($spec_number); ?></span><?php if ($spec_unit !== '') : ?><span class="<?php echo $spec_unit === '°' ? '' : 'ms-1'; ?> text-sm font-bold text-slate-500"><?php echo esc_html($spec_unit); ?></span><?php endif; ?></dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                    <div class="boblift-product-hero-actions mt-6 flex flex-wrap items-center gap-2.5 sm:gap-3 lg:mt-7">
                        <button class="boblift-product-hero-request inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-sm bg-[#071f42] px-6 text-sm font-black text-white transition hover:bg-[#0B3F7D]" type="button" data-inquiry-modal-open data-inquiry-source="产品页首屏" data-inquiry-product="<?php echo esc_attr($product['title']); ?>" data-inquiry-product-slug="<?php echo esc_attr((string) ($product['slug'] ?? '')); ?>" data-inquiry-products="<?php echo esc_attr($inquiry_products_payload); ?>"><?php echo esc_html($ui['requestQuoteLabel'] ?? 'Request Quote'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></button>
                        <button class="boblift-product-hero-quote-toggle inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-sm border border-[#D5DEE9] bg-white px-5 text-sm font-black text-[#0B3F7D] transition hover:border-blue-300" type="button" data-product-quote-add data-product-id="<?php echo esc_attr((string) ($product['id'] ?? ($product['slug'] ?? $product['title']))); ?>" data-product-slug="<?php echo esc_attr((string) ($product['slug'] ?? '')); ?>" data-title="<?php echo esc_attr($product['title']); ?>" data-image="<?php echo esc_url($main_image); ?>" data-category-title="<?php echo esc_attr($product['category_title'] ?? 'BOB-LIFT'); ?>" data-detail-url="<?php echo esc_url($product['detail_url'] ?? get_permalink()); ?>" data-summary="<?php echo esc_attr($product['short_description'] ?? ''); ?>" data-label-add="<?php echo esc_attr($hero_quote_list_add_label); ?>" data-label-active="<?php echo esc_attr($hero_quote_list_active_label); ?>"><?php echo boblift_icon('heart', 'h-4 w-4'); ?><span data-product-quote-add-label><?php echo esc_html($hero_quote_list_add_label); ?></span></button>
                    </div>
                </div>
                <div class="relative order-1 min-w-0 lg:order-2" data-product-hero-gallery-root data-product-hero-active-index="0">
                    <div class="boblift-product-stage relative aspect-[4/3] overflow-hidden rounded border border-[#DDE7F2] bg-[linear-gradient(180deg,#FFFFFF_0%,#F2F6FB_62%,#E8EEF6_100%)] shadow-card lg:aspect-[5/4] xl:aspect-[4/3]" data-product-hotspot-stage>
                        <span class="pointer-events-none absolute inset-x-[8%] bottom-6 h-px bg-[#CBD6E3] sm:bottom-8 lg:bottom-10" aria-hidden="true"></span>
                        <span class="pointer-events-none absolute bottom-2 left-1/2 h-8 w-[58%] -translate-x-1/2 rounded-[50%] bg-[radial-gradient(closest-side,rgba(11,27,51,0.16),transparent)] sm:bottom-4 lg:bottom-6" aria-hidden="true"></span>
                        <div class="absolute inset-0">
                            <img class="absolute inset-0 z-10 h-full w-full transition-opacity duration-500 ease-out <?php echo esc_attr($hero_image_class); ?>" src="<?php echo esc_url($hero_image); ?>" alt="<?php echo esc_attr($product['title']); ?>" loading="eager" fetchpriority="high" decoding="async" data-product-hero-gallery-current data-product-hero-gallery-image>
                            <img class="absolute inset-0 z-20 h-full w-full opacity-0 transition-opacity duration-500 ease-out <?php echo esc_attr($hero_image_class); ?>" src="<?php echo esc_url($hero_image); ?>" alt="" aria-hidden="true" loading="lazy" decoding="async" data-product-hero-gallery-next>
                            <?php if ($hero_is_cutout && $has_custom_hotspots) : ?>
                                <div class="absolute inset-0 z-30 hidden lg:block" data-product-hotspot-root data-product-hero-primary-only>
                                    <?php foreach ($hotspots as $index => $hotspot) :
                                $label = (string) ($hotspot['label'] ?? '');
                                $x = max(0, min(100, (float) ($hotspot['x'] ?? 50)));
                                $y = max(0, min(100, (float) ($hotspot['y'] ?? 50)));
                                $label_position = $x > 58 ? 'end-full mr-3' : 'start-full ms-3';
                            ?>
                                <button class="absolute grid h-7 w-7 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-sm border border-white/85 bg-[#071f42]/90 text-[11px] font-bold text-white shadow-card ring-2 ring-white/70 backdrop-blur transition hover:scale-105 hover:bg-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-200/70" style="left:<?php echo esc_attr((string) $x); ?>%;top:<?php echo esc_attr((string) $y); ?>%" type="button" data-product-hotspot aria-expanded="false" aria-label="<?php echo esc_attr($label); ?>">
                                    <span><?php echo esc_html((string) ($index + 1)); ?></span>
                                    <span class="pointer-events-none absolute <?php echo esc_attr($label_position); ?> top-1/2 z-40 hidden -translate-y-1/2 whitespace-nowrap rounded border border-white/70 bg-[#071f42]/95 px-2.5 py-1.5 text-[11px] font-bold leading-none tracking-normal text-white shadow-raised backdrop-blur" data-product-hotspot-label><?php echo esc_html($label); ?></span>
                                </button>
                            <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (count($hero_gallery_items) > 1) : ?>
                        <div class="mt-3 flex">
                            <div class="flex max-w-full items-center gap-2 overflow-x-auto [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" aria-label="<?php echo esc_attr($ui['photoGalleryTitle'] ?? 'Product Photos'); ?>">
                                <?php foreach ($hero_gallery_items as $index => $item) : ?>
                                    <button class="grid h-11 w-14 shrink-0 place-items-center overflow-hidden rounded-sm border p-0.5 transition md:h-12 md:w-16 <?php echo $index === 0 ? 'border-blue-500 bg-blue-50 shadow-card' : 'border-[#DDE5EF] bg-white hover:border-blue-300'; ?>" type="button" data-product-hero-gallery-thumb data-product-hero-gallery-index="<?php echo esc_attr((string) $index); ?>" data-product-hero-gallery-src="<?php echo esc_url($item['image']); ?>" data-product-hero-gallery-label="<?php echo esc_attr($item['label']); ?>" data-product-hero-gallery-fit="<?php echo esc_attr($item['fit']); ?>" aria-pressed="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr($item['label']); ?>">
                                        <img class="h-full w-full rounded-sm object-cover" src="<?php echo esc_url($item['image']); ?>" alt="" loading="lazy" decoding="async">
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <div class="sticky top-0 z-30 border-y border-[#DDE7F2] bg-white/82 shadow-card backdrop-blur-xl"><nav class="page-shell flex min-h-14 items-center overflow-x-auto [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" aria-label="<?php echo esc_attr($ui['sectionNavLabel'] ?? 'Product detail sections'); ?>"><a class="boblift-cinema-nav-link grid h-14 min-w-14 place-items-center border-x border-[#DDE7F2] text-[#0B3F7D]" href="#product-hero" data-product-section-link aria-label="<?php echo esc_attr($product['title'] ?? 'Product hero'); ?>"><?php echo boblift_icon('home', 'h-5 w-5'); ?></a><?php foreach ($anchors as $anchor) : ?><a class="boblift-cinema-nav-link flex h-14 min-w-max items-center justify-center border-e border-[#DDE7F2] px-5 text-xs font-black text-[#334155] transition hover:bg-blue-50 hover:text-[#0B3F7D] md:px-6 md:text-sm" href="#<?php echo esc_attr($anchor['id']); ?>" data-product-section-link><?php echo esc_html($anchor['label']); ?></a><?php endforeach; ?></nav></div>
    <section class="bg-[#F5F7FB] py-8 md:py-12">
        <div class="page-shell min-w-0 space-y-6 md:space-y-8">
            <article id="technical" class="scroll-mt-24 overflow-hidden rounded border border-[#DDE7F2] bg-white shadow-card">
                <div class="p-6 md:p-8">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-[11px] font-black uppercase tracking-[0.2em] text-[#0B3F7D]"><?php echo esc_html($ui['cinemaSpecsLabel'] ?? 'Engineering data'); ?></p>
                            <h2 class="mt-3 text-[clamp(1.6rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em] text-[#071f42]"><?php echo esc_html($ui['specsHeading'] ?? 'Specifications'); ?></h2>
                        </div>
                        <?php if ($former_models !== '') : ?>
                            <?php // Old-site model names stay on the page so searches for them still land here. ?>
                            <dl class="shrink-0 rounded-sm border border-[#DDE7F2] bg-[#F5F8FC] px-3 py-2 text-right sm:px-4">
                                <dt class="text-[11px] font-semibold leading-4 text-slate-500"><?php echo esc_html($ui['formerModelLabel'] ?? 'Former model'); ?></dt>
                                <dd class="mt-0.5 text-sm font-black leading-5 text-[#071f42]"><?php echo esc_html($former_models); ?></dd>
                            </dl>
                        <?php endif; ?>
                    </div>
                </div>
                <dl class="-mb-px -mr-px grid grid-cols-2 border-t border-[#E6EDF5] sm:grid-cols-3 lg:grid-cols-4">
                    <?php foreach ($technical_specs as $index => $spec) :
                        [$spec_number, $spec_unit] = $hero_spec_parts((string) $spec['value']);
                        $spec_wide = mb_strlen((string) $spec['value']) > 22;
                    ?>
                        <div class="flex min-w-0 flex-col border-b border-e border-[#E6EDF5] px-4 py-4 sm:px-6 sm:py-5 <?php echo $spec_wide ? 'col-span-2' : ''; ?> <?php echo $index === 0 ? 'bg-[#F5F8FC]' : ''; ?>">
                            <dt class="text-xs font-semibold leading-5 text-slate-500"><?php echo esc_html($spec['label']); ?></dt>
                            <dd class="mt-auto break-words pt-1.5 font-black leading-tight text-[#071f42] [font-variant-numeric:tabular-nums]"><span class="<?php echo ($spec_unit !== '' || mb_strlen($spec_number) <= 12) ? 'text-lg md:text-xl' : 'text-sm md:text-base'; ?>"><?php echo esc_html($spec_number); ?></span><?php if ($spec_unit !== '') : ?><span class="<?php echo $spec_unit === '°' ? '' : 'ms-1'; ?> text-xs font-bold text-slate-500 md:text-sm"><?php echo esc_html($spec_unit); ?></span><?php endif; ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            </article>
            <?php if ($detail_gallery) : ?>
                <?php
                $pad = static fn(int $n): string => str_pad((string) $n, 2, '0', STR_PAD_LEFT);
                // Up to 7 slices sit in one justified row on desktop (same height, width by aspect ratio); more wrap in a grid.
                $detail_row = count($detail_gallery) <= 7 && !in_array(0, array_column($detail_gallery, 'height'), true);
                ?>
                <article id="overview" class="scroll-mt-24 overflow-hidden rounded border border-[#DDE7F2] bg-white shadow-card" data-detail-viewer data-detail-images="<?php echo esc_attr(wp_json_encode(array_column($detail_gallery, 'full'), JSON_UNESCAPED_SLASHES)); ?>" data-detail-alt="<?php echo esc_attr($detail_alt); ?>">
                    <div class="p-6 md:p-8">
                        <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-2">
                            <div>
                                <p class="text-[11px] font-black uppercase tracking-[0.2em] text-[#0B3F7D]"><?php echo esc_html($ui['detailBodyLabel'] ?? 'Product details'); ?></p>
                                <h2 class="mt-3 text-[clamp(1.6rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em] text-[#071f42]"><?php echo esc_html($hero_model !== '' ? $hero_model : $product_title); ?></h2>
                            </div>
                            <p class="flex items-center gap-1.5 text-xs font-bold text-slate-500"><?php echo boblift_icon('maximize-2', 'h-3.5 w-3.5'); ?><?php echo esc_html($ui['detailImagesHint'] ?? 'Click an image to enlarge'); ?></p>
                        </div>
                        <div class="mt-6 grid grid-cols-2 items-start gap-2 md:grid-cols-3 md:gap-3 <?php echo $detail_row ? 'lg:flex' : 'lg:grid-cols-4'; ?>">
                            <?php foreach ($detail_gallery as $index => $image) : ?>
                                <button class="group relative block overflow-hidden rounded-sm border border-[#E6EDF5] bg-[#EDF1F5] text-start transition hover:border-blue-300 lg:min-w-0 lg:[flex:var(--detail-ratio)_1_0%]"<?php if ($detail_row) : ?> style="--detail-ratio: <?php echo esc_attr(number_format($image['width'] / $image['height'], 4, '.', '')); ?>"<?php endif; ?> type="button" data-detail-open="<?php echo esc_attr((string) $index); ?>" aria-label="<?php echo esc_attr($detail_alt . ' ' . $pad($index + 1) . ' / ' . $pad(count($detail_gallery))); ?>">
                                    <img class="block h-auto w-full" src="<?php echo esc_url($image['preview']); ?>" alt="<?php echo esc_attr($detail_alt . ' ' . ($index + 1)); ?>"<?php if ($image['width'] && $image['height']) : ?> width="<?php echo esc_attr((string) $image['width']); ?>" height="<?php echo esc_attr((string) $image['height']); ?>"<?php endif; ?> loading="lazy" decoding="async">
                                    <span class="absolute bottom-1.5 start-1.5 rounded-sm bg-[#071f42]/80 px-1.5 py-0.5 text-[10px] font-black tracking-[0.08em] text-white [font-variant-numeric:tabular-nums]"><?php echo esc_html($pad($index + 1)); ?></span>
                                    <span class="absolute inset-0 grid place-items-center bg-[#071f42]/0 text-white opacity-0 transition group-hover:bg-[#071f42]/30 group-hover:opacity-100"><?php echo boblift_icon('maximize-2', 'h-6 w-6'); ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($detail_body_html !== '') : ?>
                            <?php $render_detail_body('mt-8 border-t border-[#E6EDF5] pt-7 md:mt-10 md:pt-8'); ?>
                        <?php endif; ?>
                    </div>
                    <div class="fixed inset-0 z-[1000] hidden flex-col bg-[#040f22]" data-detail-lightbox role="dialog" aria-modal="true" aria-hidden="true" aria-label="<?php echo esc_attr($detail_alt); ?>">
                        <div class="flex shrink-0 items-center justify-between px-4 py-3 text-white md:px-6">
                            <p class="text-xs font-black tracking-[0.08em] [font-variant-numeric:tabular-nums]" aria-live="polite"><span data-detail-index>01</span> / <?php echo esc_html($pad(count($detail_gallery))); ?></p>
                            <button class="grid size-10 place-items-center rounded-sm bg-white/10 text-white transition hover:bg-white hover:text-[#071f42]" type="button" data-detail-close aria-label="<?php echo esc_attr($ui['closeLabel'] ?? 'Close'); ?>"><?php echo boblift_icon('x', 'h-5 w-5'); ?></button>
                        </div>
                        <div class="relative min-h-0 flex-1">
                            <div class="absolute inset-0 overflow-auto px-3 pb-4 md:px-20" data-detail-stage>
                                <img class="mx-auto block h-auto w-full max-w-[750px] md:h-full md:w-auto md:max-w-full md:object-contain" src="" alt="" data-detail-image>
                            </div>
                            <?php if (count($detail_gallery) > 1) : ?>
                                <button class="boblift-feedback-arrow left-2 md:start-5" type="button" data-detail-prev aria-label="<?php echo esc_attr($ui['previousLabel'] ?? 'Previous'); ?>"><?php echo boblift_icon('chevron-left', 'h-5 w-5'); ?></button>
                                <button class="boblift-feedback-arrow right-2 md:end-5" type="button" data-detail-next aria-label="<?php echo esc_attr($ui['nextLabel'] ?? 'Next'); ?>"><?php echo boblift_icon('chevron-right', 'h-5 w-5'); ?></button>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php elseif ($detail_body_html !== '') : ?>
                <article id="overview" class="scroll-mt-24 overflow-hidden rounded border border-[#DDE7F2] bg-white shadow-card">
                    <div class="grid gap-6 p-6 md:p-8 lg:grid-cols-[minmax(0,280px)_minmax(0,1fr)] lg:gap-12">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.2em] text-[#0B3F7D]"><?php echo esc_html($ui['detailBodyLabel'] ?? 'Product details'); ?></p>
                            <h2 class="mt-3 text-[clamp(1.6rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em] text-[#071f42]"><?php echo esc_html($hero_model !== '' ? $hero_model : $product_title); ?></h2>
                        </div>
                        <?php $render_detail_body(''); ?>
                    </div>
                </article>
            <?php endif; ?>
            <?php if ($drawings) : ?>
                <article id="drawing" class="scroll-mt-24 overflow-hidden rounded border border-[#DDE7F2] bg-white shadow-card">
                    <div class="flex flex-col gap-2 border-b border-[#DDE7F2] p-6 md:flex-row md:items-end md:justify-between md:p-8">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.2em] text-[#0B3F7D]"><?php echo esc_html($ui['drawingLabel'] ?? 'Dimension Drawing'); ?></p>
                            <h2 class="mt-3 text-[clamp(1.6rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em] text-[#071f42]"><?php echo esc_html($ui['drawingTitle'] ?? 'Dimensions and load chart'); ?></h2>
                        </div>
                        <p class="text-xs font-semibold text-slate-500"><?php echo esc_html($hero_model !== '' ? $hero_model : $product_title); ?></p>
                    </div>
                    <?php // Outline drawing and load chart side by side when the technical drawings supply both. ?>
                    <div class="grid <?php echo count($drawings) > 1 ? 'md:grid-cols-2' : ''; ?>">
                    <?php foreach ($drawings as $drawing) :
                        $drawing_ratio = ($drawing['height'] ?? 0) > 0 ? $drawing['width'] / $drawing['height'] : 2;
                    ?>
                    <div data-drawing-root>
                    <div class="p-4 sm:p-6 md:p-8">
                        <button class="block w-full cursor-zoom-in rounded-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500" type="button" data-drawing-open aria-label="<?php echo esc_attr($drawing['label']); ?>">
                            <img class="mx-auto h-auto w-full <?php echo $drawing_ratio < 1.4 ? 'max-w-[560px]' : ''; ?>" src="<?php echo esc_url($drawing['image']); ?>" width="<?php echo esc_attr((string) $drawing['width']); ?>" height="<?php echo esc_attr((string) $drawing['height']); ?>" alt="<?php echo esc_attr($drawing['label']); ?>" loading="lazy" decoding="async">
                        </button>
                        <p class="mt-4 text-center text-xs font-semibold text-slate-500"><?php echo esc_html($ui['drawingHint'] ?? 'Select the drawing to view it full size.'); ?></p>
                    </div>
                    <dialog class="boblift-drawing-dialog m-auto h-[92vh] w-[96vw] max-w-none overflow-hidden rounded border border-[#DDE7F2] bg-white p-0 shadow-raised" data-drawing-dialog aria-label="<?php echo esc_attr($drawing['label']); ?>">
                        <div class="flex h-full flex-col">
                            <div class="flex items-center justify-between gap-4 border-b border-[#DDE7F2] px-4 py-3 md:px-6">
                                <p class="truncate text-sm font-black text-[#071f42]"><?php echo esc_html($drawing['label']); ?></p>
                                <button class="grid size-9 shrink-0 place-items-center rounded-sm border border-[#DDE7F2] text-[#071f42] transition hover:border-blue-300" type="button" data-drawing-close aria-label="<?php echo esc_attr($ui['drawingCloseLabel'] ?? 'Close drawing'); ?>"><?php echo boblift_icon('x', 'h-4 w-4'); ?></button>
                            </div>
                            <div class="min-h-0 flex-1 overflow-auto p-4 md:p-6"><img class="mx-auto h-auto max-w-none" style="width:max(100%, <?php echo esc_attr((string) min(2000, (int) $drawing['width'])); ?>px)" src="<?php echo esc_url($drawing['image']); ?>" alt="" loading="lazy" decoding="async"></div>
                        </div>
                    </dialog>
                    </div>
                    <?php endforeach; ?>
                    </div>
                </article>
            <?php endif; ?>
            <?php if ($brochure_features) : ?>
                <?php
                $features_series = trim((string) ($product['features_series'] ?? ''));
                $feature_cards = array_values(array_filter($brochure_features, static fn(array $f): bool => trim((string) ($f['image'] ?? '')) !== ''));
                $feature_rest = array_values(array_filter($brochure_features, static fn(array $f): bool => trim((string) ($f['image'] ?? '')) === ''));
                // Literal class names so Tailwind keeps them.
                $feature_cols = [1 => 'lg:grid-cols-3', 2 => 'lg:grid-cols-3', 3 => 'lg:grid-cols-3', 4 => 'lg:grid-cols-4'][count($feature_cards)] ?? 'lg:grid-cols-5';
                ?>
                <article id="features" class="scroll-mt-24 overflow-hidden rounded border border-[#DDE7F2] bg-white shadow-card">
                    <div class="p-6 pb-0 md:p-8 md:pb-0">
                        <p class="text-[11px] font-black uppercase tracking-[0.2em] text-[#0B3F7D]"><?php echo esc_html($features_series !== '' ? $features_series : ($ui['featuresLabel'] ?? 'Key Features')); ?></p>
                        <h2 class="mt-3 text-[clamp(1.6rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em] text-[#071f42]"><?php echo esc_html($ui['featuresTitle'] ?? 'Product technical advantages'); ?></h2>
                    </div>
                    <?php if ($feature_cards) : ?>
                        <ul class="grid grid-cols-2 gap-x-4 gap-y-6 p-6 md:gap-x-5 md:p-8 <?php echo esc_attr($feature_cols); ?>">
                            <?php foreach ($feature_cards as $feature) : ?>
                                <li class="min-w-0">
                                    <div class="aspect-[4/3] overflow-hidden rounded-sm bg-[#EDF1F5]">
                                        <img class="h-full w-full object-cover" src="<?php echo esc_url((string) $feature['image']); ?>" alt="<?php echo esc_attr((string) $feature['title']); ?>" loading="lazy" decoding="async">
                                    </div>
                                    <h3 class="mt-3 text-sm font-black leading-snug text-[#071f42] md:text-base"><?php echo esc_html((string) $feature['title']); ?></h3>
                                    <p class="mt-1 text-xs font-semibold leading-5 text-slate-600 md:text-sm md:leading-6"><?php echo esc_html((string) ($feature['text'] ?? '')); ?></p>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <?php if ($feature_rest) : ?>
                        <ul class="grid gap-x-8 gap-y-3 border-t border-[#E6EDF5] px-6 py-5 md:grid-cols-2 md:px-8 xl:grid-cols-3 <?php echo $feature_cards ? '' : 'mt-6'; ?>">
                            <?php foreach ($feature_rest as $feature) : ?>
                                <li class="flex min-w-0 gap-2.5 text-sm leading-6">
                                    <?php echo boblift_icon('check', 'mt-1 h-4 w-4 shrink-0 text-[#0B3F7D]'); ?>
                                    <span class="min-w-0"><strong class="font-black text-[#071f42]"><?php echo esc_html((string) $feature['title']); ?></strong> <span class="font-semibold text-slate-600"><?php echo esc_html((string) ($feature['text'] ?? '')); ?></span></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </article>
            <?php endif; ?>
            <?php if ($series_video) : ?>
                <article id="video" class="scroll-mt-24 overflow-hidden rounded border border-[#DDE7F2] bg-white shadow-card">
                    <div class="grid gap-6 p-6 md:p-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)] lg:items-center lg:gap-10">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.2em] text-[#0B3F7D]"><?php echo esc_html($video_ui['productVideoLabel'] ?? 'Product video'); ?></p>
                            <h2 class="mt-3 text-[clamp(1.6rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em] text-[#071f42]"><?php echo esc_html($video_ui['productVideoTitle'] ?? 'Watch this series in action'); ?></h2>
                            <p class="mt-3 text-sm font-semibold leading-6 text-slate-500"><?php echo esc_html($video_ui['productVideoNote'] ?? ''); ?></p>
                            <a class="mt-5 inline-flex items-center gap-1.5 text-sm font-black text-blue-700 hover:text-blue-900" href="<?php echo esc_url(boblift_url('/videos.html')); ?>"><?php echo esc_html($video_ui['moreVideosLabel'] ?? 'More videos'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
                        </div>
                        <?php boblift_render_video_card($series_video); ?>
                    </div>
                    <?php boblift_render_video_player(); ?>
                </article>
            <?php endif; ?>
            <?php if ($process_steps) :
                // Literal class names so Tailwind keeps them.
                $process_cols = [3 => 'lg:grid-cols-3', 4 => 'lg:grid-cols-4', 5 => 'lg:grid-cols-5'][count($process_steps)] ?? 'lg:grid-cols-6';
                ?>
                <article id="process" class="scroll-mt-24 overflow-hidden rounded border border-[#DDE7F2] bg-white shadow-card">
                    <div class="p-6 pb-0 md:p-8 md:pb-0">
                        <p class="text-[11px] font-black uppercase tracking-[0.2em] text-[#0B3F7D]"><?php echo esc_html($ui['processLabel'] ?? 'Manufacturing & delivery'); ?></p>
                        <h2 class="mt-3 text-[clamp(1.6rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em] text-[#071f42]"><?php echo esc_html($ui['processTitle'] ?? 'From our workshop to your port'); ?></h2>
                    </div>
                    <ol class="grid grid-cols-2 gap-x-4 gap-y-6 p-6 md:grid-cols-3 md:gap-x-5 md:p-8 <?php echo esc_attr($process_cols); ?>">
                        <?php foreach ($process_steps as $index => $step) :
                            $step_asset = static fn(string $path): string => $path === '' || preg_match('#^(https?:)?//#', $path) || str_starts_with($path, '/') ? $path : boblift_asset($path);
                            $step_image = $step_asset(trim((string) $step['image']));
                            $step_video = $step_asset(trim((string) ($step['video'] ?? '')));
                            ?>
                            <li class="min-w-0">
                                <div class="relative aspect-[4/3] overflow-hidden rounded-sm bg-[#EDF1F5]">
                                    <?php if ($step_video !== '') : ?>
                                        <video class="h-full w-full object-cover" src="<?php echo esc_url($step_video); ?>" poster="<?php echo esc_url($step_image); ?>" muted loop playsinline preload="none" aria-label="<?php echo esc_attr((string) ($step['title'] ?? '')); ?>" data-inview-video></video>
                                        <span class="pointer-events-none absolute bottom-2 right-2 grid size-7 place-items-center rounded-sm bg-[#071f42]/85 text-white"><?php echo boblift_icon('play', 'h-3.5 w-3.5'); ?></span>
                                    <?php else : ?>
                                        <img class="h-full w-full object-cover" src="<?php echo esc_url($step_image); ?>" alt="<?php echo esc_attr((string) ($step['title'] ?? '')); ?>" loading="lazy" decoding="async">
                                    <?php endif; ?>
                                    <span class="absolute left-2 top-2 rounded-sm bg-[#071f42]/85 px-2 py-0.5 text-[11px] font-black tracking-[0.08em] text-white [font-variant-numeric:tabular-nums]"><?php echo esc_html(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></span>
                                </div>
                                <h3 class="mt-3 text-sm font-black leading-snug text-[#071f42] md:text-base"><?php echo esc_html((string) ($step['title'] ?? '')); ?></h3>
                                <p class="mt-1 text-xs font-semibold leading-5 text-slate-600 md:text-sm md:leading-6"><?php echo esc_html((string) ($step['text'] ?? '')); ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </article>
            <?php endif; ?>
            <?php if ($product_cases) : ?>
                <article id="cases" class="scroll-mt-24 overflow-hidden rounded border border-[#DDE7F2] bg-white shadow-card">
                    <div class="flex items-end justify-between gap-4 p-6 pb-0 md:p-8 md:pb-0">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.2em] text-[#0B3F7D]"><?php echo esc_html($cases_ui['sceneCasesLabel'] ?? 'Application Cases'); ?></p>
                            <h2 class="mt-3 text-[clamp(1.6rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em] text-[#071f42]"><?php echo esc_html($ui['casesTitle'] ?? 'Where this crane works'); ?></h2>
                        </div>
                    </div>
                    <?php if (count($product_cases) === 1) : $case_item = $product_cases[0]; ?>
                        <a class="group grid items-center gap-6 p-6 md:grid-cols-2 md:gap-10 md:p-8" href="<?php echo esc_url($case_item['url']); ?>">
                            <span class="block aspect-[3/2] overflow-hidden rounded-sm bg-[#EDF1F5]"><img class="h-full w-full object-cover" src="<?php echo esc_url(boblift_item_image($case_item)); ?>" alt="<?php echo esc_attr($case_item['title']); ?>" loading="lazy" decoding="async"></span>
                            <span class="block min-w-0">
                                <?php if ($case_item['country'] !== '' || $case_item['application'] !== '') : ?><span class="block text-[11px] font-black uppercase tracking-[0.14em] text-slate-500"><?php echo esc_html(implode(' · ', array_filter([$case_item['country'], $case_item['application']]))); ?></span><?php endif; ?>
                                <span class="mt-2 block text-xl font-black leading-snug text-[#071f42] transition group-hover:text-blue-700 md:text-2xl"><?php echo esc_html($case_item['title']); ?></span>
                                <?php if (trim((string) $case_item['summary']) !== '') : ?><span class="mt-3 line-clamp-3 block text-sm font-semibold leading-6 text-slate-600 md:text-base md:leading-7"><?php echo esc_html($case_item['summary']); ?></span><?php endif; ?>
                                <span class="mt-5 inline-flex items-center gap-1.5 text-sm font-black text-blue-700"><?php echo esc_html($cases_ui['viewCaseLabel'] ?? 'View Case'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4 transition group-hover:translate-x-1'); ?></span>
                            </span>
                        </a>
                    <?php else : ?>
                    <div class="grid gap-5 p-6 md:p-8 <?php echo count($product_cases) === 2 ? 'md:grid-cols-2' : 'md:grid-cols-3'; ?>">
                        <?php foreach ($product_cases as $case_item) : ?>
                            <a class="group block min-w-0" href="<?php echo esc_url($case_item['url']); ?>">
                                <span class="block aspect-[3/2] overflow-hidden rounded-sm bg-[#EDF1F5]"><img class="h-full w-full object-cover" src="<?php echo esc_url(boblift_item_image($case_item)); ?>" alt="<?php echo esc_attr($case_item['title']); ?>" loading="lazy" decoding="async"></span>
                                <?php if ($case_item['country'] !== '' || $case_item['application'] !== '') : ?><span class="mt-3 block text-[11px] font-black uppercase tracking-[0.14em] text-slate-500"><?php echo esc_html(implode(' · ', array_filter([$case_item['country'], $case_item['application']]))); ?></span><?php endif; ?>
                                <span class="mt-1.5 line-clamp-2 block text-base font-black leading-snug text-[#071f42] transition group-hover:text-blue-700 md:text-lg"><?php echo esc_html($case_item['title']); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </article>
            <?php endif; ?>
            <?php if ($product_feedback) :
                $feedback_list_url = add_query_arg('tab', 'customer_feedback', boblift_case_archive_url());
                $feedback_shown = array_slice($product_feedback, 0, 4);
                ?>
                <article id="feedback" class="scroll-mt-24 overflow-hidden rounded border border-[#DDE7F2] bg-white shadow-card">
                    <div class="flex items-end justify-between gap-4 p-6 pb-0 md:p-8 md:pb-0">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.2em] text-[#0B3F7D]"><?php echo esc_html($cases_ui['customerFeedbackLabel'] ?? 'Customer Feedback'); ?></p>
                            <h2 class="mt-3 text-[clamp(1.6rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em] text-[#071f42]"><?php echo esc_html($ui['feedbackTitle'] ?? 'Photos and videos from customers'); ?></h2>
                        </div>
                        <?php if ($product_feedback_total > count($feedback_shown)) : ?>
                            <a class="hidden shrink-0 items-center gap-1.5 text-sm font-black text-blue-700 transition hover:text-[#071f42] sm:inline-flex" href="<?php echo esc_url($feedback_list_url); ?>"><?php echo esc_html($ui['viewAllFeedbackLabel'] ?? 'All customer feedback'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
                        <?php endif; ?>
                    </div>
                    <div class="grid grid-cols-2 gap-x-4 gap-y-6 p-6 md:gap-x-5 md:p-8 lg:grid-cols-4">
                        <?php foreach ($feedback_shown as $feedback_item) :
                            $feedback_count_text = boblift_feedback_counts_text($feedback_item);
                            ?>
                            <a class="group block min-w-0" href="<?php echo esc_url($feedback_item['url']); ?>">
                                <span class="relative block aspect-[4/3] overflow-hidden rounded-sm bg-[#EDF1F5]">
                                    <?php if ($feedback_item['cover'] !== '') : ?><img class="h-full w-full object-cover" src="<?php echo esc_url($feedback_item['cover']); ?>" alt="<?php echo esc_attr($feedback_item['model'] . ' · ' . $feedback_item['country']); ?>" loading="lazy" decoding="async"><?php endif; ?>
                                    <?php if ($feedback_item['media'][0]['type'] === 'video') : ?><span class="absolute inset-0 m-auto grid size-12 place-items-center rounded-sm bg-white/90 text-[#0B3F7D]"><?php echo boblift_icon('play', 'h-5 w-5'); ?></span><?php endif; ?>
                                </span>
                                <span class="mt-3 flex items-center gap-1.5 text-sm font-black text-[#071f42] transition group-hover:text-blue-700 md:text-base"><?php echo boblift_icon('map-pin', 'h-4 w-4 shrink-0 text-gold'); ?><?php echo esc_html($feedback_item['country']); ?></span>
                                <span class="mt-0.5 block text-xs font-semibold text-slate-500 md:text-sm"><?php echo esc_html(implode(' · ', array_filter([$feedback_item['model'], $feedback_count_text]))); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($product_feedback_total > count($feedback_shown)) : ?>
                        <a class="flex items-center justify-center gap-1.5 border-t border-[#E6EDF5] px-6 py-4 text-sm font-black text-blue-700 sm:hidden" href="<?php echo esc_url($feedback_list_url); ?>"><?php echo esc_html($ui['viewAllFeedbackLabel'] ?? 'All customer feedback'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
                    <?php endif; ?>
                </article>
            <?php endif; ?>
            <section id="faq" class="scroll-mt-24"><h2 class="mb-5 text-3xl font-black tracking-[-0.015em] text-[#071f42] md:text-4xl"><?php echo esc_html($ui['faqLabel'] ?? 'FAQ'); ?></h2><div class="grid grid-cols-1 gap-4 lg:grid-cols-2"><?php foreach ($faqs as $faq) : ?><details class="group rounded border border-[#DDE7F2] bg-white px-5 py-4 shadow-card"><summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-black text-[#071f42]"><?php echo esc_html($faq['question']); ?><?php echo boblift_icon('chevron-down', 'h-4 w-4 transition group-open:rotate-180'); ?></summary><p class="mt-3 text-sm font-semibold leading-6 text-slate-600"><?php echo esc_html($faq['answer']); ?></p></details><?php endforeach; ?></div></section>
            <?php // A catalog saved on this product stays downloadable next to the family brochures. ?>
            <?php $catalog_url = function_exists('boblift_product_catalog_url') ? boblift_product_catalog_url($product) : ''; if ($catalog_url !== '') : ?>
                <p class="boblift-product-catalog-link"><a class="inline-flex h-12 items-center justify-center gap-2 rounded-sm border border-[#D8E2EF] bg-white px-5 text-sm font-black text-[#071f42] transition hover:-translate-y-0.5 hover:border-blue-300 hover:text-blue-700" href="<?php echo esc_url($catalog_url); ?>" target="_blank" rel="noopener"><?php echo boblift_icon('file-text', 'h-4 w-4'); ?><?php echo esc_html((string) ($ui['heroPdfLabel'] ?? 'PDF Catalog')); ?></a></p>
            <?php endif; ?>
            <?php boblift_render_brochures(['context' => 'product', 'families' => boblift_brochure_families_for_product($product), 'product' => $product_title, 'product_slug' => (string) ($product['slug'] ?? '')]); ?>
            <section id="quote" class="boblift-product-quote-section relative left-1/2 w-screen -translate-x-1/2 rtl:left-auto rtl:right-1/2 rtl:translate-x-1/2 scroll-mt-24 overflow-hidden border-y border-[#DDE7F2] bg-[radial-gradient(circle_at_70%_28%,rgba(245,154,60,0.16),transparent_28%),linear-gradient(135deg,#ffffff_0%,#eaf2ff_100%)] py-12 text-[#071f42] shadow-card md:py-16">
                <div class="page-shell grid items-center gap-8 lg:grid-cols-[minmax(0,1fr)_auto] xl:gap-12">
                    <div class="boblift-product-quote-copy flex items-start gap-5 md:gap-6">
                        <div class="hidden h-20 w-20 shrink-0 place-items-center rounded border border-blue-100 bg-white/74 text-[#0B3F7D] shadow-card md:grid"><?php echo boblift_icon('mail', 'h-10 w-10'); ?></div>
                        <div class="min-w-0">
                            <h2 class="boblift-product-quote-title max-w-[1280px] text-[clamp(34px,3vw,58px)] font-black leading-[1.02] tracking-[-0.02em]"><?php echo esc_html($quote_title); ?></h2>
                            <p class="boblift-product-quote-text mt-4 max-w-[1120px] text-sm font-semibold leading-7 text-slate-600 md:text-base md:leading-8"><?php echo esc_html($quote_description); ?></p>
                        </div>
                    </div>
                    <div class="boblift-product-quote-actions flex flex-wrap gap-3 lg:justify-end">
                        <button class="boblift-product-quote-action boblift-product-quote-action-primary inline-flex h-14 items-center justify-center gap-3 rounded-sm bg-[#071f42] px-7 text-sm font-black text-white transition hover:bg-[#0B3F7D]" type="button" data-inquiry-modal-open data-inquiry-source="产品页询价区" data-inquiry-product="<?php echo esc_attr($product['title']); ?>" data-inquiry-product-slug="<?php echo esc_attr((string) ($product['slug'] ?? '')); ?>" data-inquiry-products="<?php echo esc_attr($inquiry_products_payload); ?>"><?php echo esc_html($ui['requestQuoteLabel'] ?? 'Request Quote'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></button>
                        <a class="boblift-product-quote-action boblift-product-quote-action-secondary inline-flex h-14 items-center justify-center gap-3 rounded-sm border border-blue-100 bg-white/70 px-7 text-sm font-black text-[#0B3F7D] transition hover:border-blue-300" href="<?php echo esc_url($contact_url); ?>"><?php echo esc_html($ui['contactEngineerLabel'] ?? 'Contact Engineer'); ?> <?php echo boblift_icon('mail', 'h-4 w-4'); ?></a>
                    </div>
                </div>
            </section>
        </div>
    </section>
</div>
<?php get_footer(); ?>
