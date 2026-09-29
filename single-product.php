<?php
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
$catalog_url = function_exists('boblift_product_catalog_url') ? boblift_product_catalog_url($product) : '';
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
$overview_title = (string) ($ui['overviewTitle'] ?? 'Built for reliable lifting work.');
if (str_contains(strtolower($overview_title), 'truck-mounted') && !str_contains(strtolower((string) ($product['category_title'] ?? '')), 'truck')) {
    $overview_title = (string) ($ui['spiderOverviewTitle'] ?? 'Built for precise lifting in tight working spaces.');
}
$technical_specs = array_values(array_filter(array_merge(
    $hero_model !== '' ? [['label' => (string) ($ui['specLabels']['model'] ?? 'Model'), 'value' => $hero_model]] : [],
    trim((string) ($product['model_aliases'] ?? '')) !== '' ? [['label' => (string) ($ui['formerModelLabel'] ?? 'Former Model Name'), 'value' => (string) $product['model_aliases']]] : [],
    $specs
), static fn($spec): bool => is_array($spec) && trim((string) ($spec['label'] ?? '')) !== '' && trim((string) ($spec['value'] ?? '')) !== ''));
$brochure_source = trim((string) ($product['brochure_source'] ?? ''));
$drawings = array_values(array_filter($product['drawings'] ?? [], static fn($drawing): bool => is_array($drawing) && ($drawing['image'] ?? '') !== ''));
$brochure_features = get_post_meta((int) ($product['id'] ?? 0), '_features_source', true) === 'brochure'
    ? array_values(array_filter($product['features'] ?? [], static fn($feature): bool => is_array($feature) && trim((string) ($feature['title'] ?? '')) !== ''))
    : [];
// Products checked against the product manual show the brochure-based summary, not old-site copy.
$product_intro_html = trim((string) ($product['brochure_source'] ?? '')) !== '' ? '' : trim((string) ($product['content'] ?? ''));
if ($product_intro_html === '') {
    $product_intro_html = '<p>' . esc_html((string) ($product['short_description'] ?? ($overview_title . ' ' . ($ui['overviewSuffix'] ?? '')))) . '</p>';
}
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
$anchors = [];
$anchors[] = ['id' => 'technical', 'label' => (string) ($ui['technicalLabel'] ?? 'Technical Specifications')];
if ($drawings) {
    $anchors[] = ['id' => 'drawing', 'label' => (string) ($ui['drawingLabel'] ?? 'Dimension Drawing')];
}
if ($brochure_features) {
    $anchors[] = ['id' => 'features', 'label' => (string) ($ui['featuresLabel'] ?? 'Key Features')];
}
$anchors[] = ['id' => 'photos', 'label' => (string) ($ui['photosLabel'] ?? 'Product Photos')];
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
                    <?php if ($hero_summary !== '') : ?>
                        <p class="mt-4 max-w-[620px] text-sm font-semibold leading-7 text-slate-600 md:text-[15px]"><?php echo esc_html($hero_summary); ?></p>
                    <?php endif; ?>
                    <dl class="mt-6 grid grid-cols-2 border-y border-[#D5DEE9] sm:grid-cols-4 lg:mt-7">
                        <?php foreach ($hero_specs as $index => $spec) :
                            [$spec_number, $spec_unit] = $hero_spec_parts((string) ($spec['value'] ?? ''));
                        ?>
                            <div class="flex min-w-0 flex-col py-4 pr-3 <?php echo $index % 2 === 1 ? 'border-l border-[#D5DEE9] pl-4' : ''; ?> <?php echo $index >= 2 ? 'border-t border-[#D5DEE9] sm:border-t-0' : ''; ?> <?php echo $index === 2 ? 'sm:border-l sm:pl-4' : ''; ?>">
                                <dt class="text-[10px] font-black uppercase leading-4 tracking-[0.12em] text-slate-500"><?php echo esc_html($spec['label']); ?></dt>
                                <dd class="mt-auto break-words pt-1.5 font-black leading-tight text-[#071f42] [font-variant-numeric:tabular-nums]"><span class="<?php echo ($spec_unit !== '' || mb_strlen($spec_number) <= 8) ? 'text-[clamp(22px,1.9vw,32px)]' : 'text-base'; ?>"><?php echo esc_html($spec_number); ?></span><?php if ($spec_unit !== '') : ?><span class="ml-1 text-sm font-bold text-slate-500"><?php echo esc_html($spec_unit); ?></span><?php endif; ?></dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                    <div class="boblift-product-hero-actions mt-6 flex flex-wrap items-center gap-2.5 sm:gap-3 lg:mt-7">
                        <button class="boblift-product-hero-request inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-sm bg-[#071f42] px-6 text-sm font-black text-white transition hover:bg-[#0B3F7D]" type="button" data-inquiry-modal-open data-inquiry-source="产品页首屏" data-inquiry-product="<?php echo esc_attr($product['title']); ?>" data-inquiry-product-slug="<?php echo esc_attr((string) ($product['slug'] ?? '')); ?>" data-inquiry-products="<?php echo esc_attr($inquiry_products_payload); ?>"><?php echo esc_html($ui['requestQuoteLabel'] ?? 'Request Quote'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></button>
                        <button class="boblift-product-hero-quote-toggle inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-sm border border-[#D5DEE9] bg-white px-5 text-sm font-black text-[#0B3F7D] transition hover:border-blue-300" type="button" data-product-quote-add data-product-id="<?php echo esc_attr((string) ($product['id'] ?? ($product['slug'] ?? $product['title']))); ?>" data-product-slug="<?php echo esc_attr((string) ($product['slug'] ?? '')); ?>" data-title="<?php echo esc_attr($product['title']); ?>" data-image="<?php echo esc_url($main_image); ?>" data-category-title="<?php echo esc_attr($product['category_title'] ?? 'BOB-LIFT'); ?>" data-detail-url="<?php echo esc_url($product['detail_url'] ?? get_permalink()); ?>" data-summary="<?php echo esc_attr($product['short_description'] ?? ''); ?>" data-label-add="<?php echo esc_attr($hero_quote_list_add_label); ?>" data-label-active="<?php echo esc_attr($hero_quote_list_active_label); ?>"><?php echo boblift_icon('heart', 'h-4 w-4'); ?><span data-product-quote-add-label><?php echo esc_html($hero_quote_list_add_label); ?></span></button>
                        <button class="boblift-product-hero-catalog inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-sm border border-[#D5DEE9] bg-white px-5 text-sm font-black text-[#071f42] transition hover:border-blue-300" type="button" data-catalog-modal-open data-catalog-product="<?php echo esc_attr($product['title']); ?>" data-catalog-product-slug="<?php echo esc_attr((string) ($product['slug'] ?? '')); ?>" data-catalog-url="<?php echo esc_url($catalog_url); ?>"><?php echo boblift_icon('download', 'h-4 w-4'); ?><?php echo esc_html($hero_pdf_label); ?></button>
                    </div>
                    <?php if ($brochure_source !== '') : ?>
                        <p class="mt-5 flex items-center gap-2 text-xs font-semibold text-slate-500"><?php echo boblift_icon('book-open', 'h-4 w-4 shrink-0 text-[#0B3F7D]'); ?><span><?php echo esc_html(($ui['sourceLabel'] ?? 'Source') . ': ' . $brochure_source); ?></span></p>
                    <?php endif; ?>
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
                                $label_position = $x > 58 ? 'right-full mr-3' : 'left-full ml-3';
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
    <div class="sticky top-0 z-30 border-y border-[#DDE7F2] bg-white/82 shadow-card backdrop-blur-xl"><nav class="page-shell flex min-h-14 items-center overflow-x-auto [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" aria-label="<?php echo esc_attr($ui['sectionNavLabel'] ?? 'Product detail sections'); ?>"><a class="boblift-cinema-nav-link grid h-14 min-w-14 place-items-center border-x border-[#DDE7F2] text-[#0B3F7D]" href="#product-hero" data-product-section-link aria-label="<?php echo esc_attr($product['title'] ?? 'Product hero'); ?>"><?php echo boblift_icon('home', 'h-5 w-5'); ?></a><?php foreach ($anchors as $anchor) : ?><a class="boblift-cinema-nav-link flex h-14 min-w-max items-center justify-center border-r border-[#DDE7F2] px-5 text-xs font-black text-[#334155] transition hover:bg-blue-50 hover:text-[#0B3F7D] md:px-6 md:text-sm" href="#<?php echo esc_attr($anchor['id']); ?>" data-product-section-link><?php echo esc_html($anchor['label']); ?></a><?php endforeach; ?></nav></div>
    <section class="bg-[#F5F7FB] py-8 md:py-12">
        <div class="page-shell min-w-0 space-y-6 md:space-y-8">
            <article id="technical" class="scroll-mt-24 overflow-hidden rounded border border-[#DDE7F2] bg-white shadow-card">
                <div class="grid lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
                    <div class="border-b border-[#DDE7F2] p-6 md:p-8 lg:border-b-0 lg:border-r lg:p-10">
                        <p class="text-[11px] font-black uppercase tracking-[0.2em] text-[#0B3F7D]"><?php echo esc_html($ui['cinemaSpecsLabel'] ?? 'Engineering data'); ?></p>
                        <h2 class="mt-3 text-[clamp(1.6rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em] text-[#071f42]"><?php echo esc_html($ui['specsHeading'] ?? 'Specifications'); ?></h2>
                        <div class="mt-5 space-y-3 text-sm font-semibold leading-7 text-slate-600 md:text-[15px] [&_p]:m-0 [&_strong]:font-black [&_strong]:text-[#071f42] [&_ul]:m-0 [&_ul]:list-disc [&_ul]:space-y-1 [&_ul]:pl-5"><?php echo wp_kses_post($product_intro_html); ?></div>
                        <?php if ($brochure_source !== '') : ?>
                            <p class="mt-6 flex items-start gap-2 border-t border-[#E6EDF5] pt-5 text-xs font-semibold leading-5 text-slate-500"><?php echo boblift_icon('book-open', 'mt-0.5 h-4 w-4 shrink-0 text-[#0B3F7D]'); ?><span><?php echo esc_html(($ui['sourceLabel'] ?? 'Source') . ': ' . $brochure_source); ?></span></p>
                        <?php endif; ?>
                    </div>
                    <dl class="divide-y divide-[#E6EDF5] self-start">
                        <?php foreach ($technical_specs as $index => $spec) : ?>
                            <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)] gap-4 px-6 py-3.5 md:px-8 lg:px-10 <?php echo $index === 0 ? 'bg-[#F5F8FC]' : ''; ?>">
                                <dt class="text-sm font-semibold leading-6 text-slate-500"><?php echo esc_html($spec['label']); ?></dt>
                                <dd class="min-w-0 break-words text-sm font-black leading-6 text-[#071f42] [font-variant-numeric:tabular-nums] md:text-[15px]"><?php echo esc_html($spec['value']); ?></dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                </div>
            </article>
            <?php foreach ($drawings as $drawing) :
                $drawing_ratio = ($drawing['height'] ?? 0) > 0 ? $drawing['width'] / $drawing['height'] : 2;
            ?>
                <article id="drawing" class="scroll-mt-24 overflow-hidden rounded border border-[#DDE7F2] bg-white shadow-card" data-drawing-root>
                    <div class="flex flex-col gap-2 border-b border-[#DDE7F2] p-6 md:flex-row md:items-end md:justify-between md:p-8">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.2em] text-[#0B3F7D]"><?php echo esc_html($ui['drawingLabel'] ?? 'Dimension Drawing'); ?></p>
                            <h2 class="mt-3 text-[clamp(1.6rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em] text-[#071f42]"><?php echo esc_html($ui['drawingTitle'] ?? 'Dimensions and load chart'); ?></h2>
                        </div>
                        <p class="text-xs font-semibold text-slate-500"><?php echo esc_html($hero_model !== '' ? $hero_model : $product_title); ?><?php echo $brochure_source !== '' ? ' · ' . esc_html($brochure_source) : ''; ?></p>
                    </div>
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
                </article>
            <?php break; endforeach; ?>
            <?php if ($brochure_features) : ?>
                <article id="features" class="scroll-mt-24 overflow-hidden rounded border border-[#DDE7F2] bg-white shadow-card">
                    <div class="border-b border-[#DDE7F2] p-6 md:p-8">
                        <p class="text-[11px] font-black uppercase tracking-[0.2em] text-[#0B3F7D]"><?php echo esc_html($ui['featuresLabel'] ?? 'Key Features'); ?></p>
                        <h2 class="mt-3 text-[clamp(1.6rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em] text-[#071f42]"><?php echo esc_html($ui['featuresTitle'] ?? 'Built into every crane in this series'); ?></h2>
                    </div>
                    <ol class="-mb-px -mr-px grid sm:grid-cols-2 xl:grid-cols-3">
                        <?php foreach ($brochure_features as $index => $feature) : ?>
                            <li class="border-b border-r border-[#E6EDF5] p-6 md:p-7">
                                <span class="text-xs font-black text-[#0B3F7D] [font-variant-numeric:tabular-nums]"><?php echo esc_html(sprintf('%02d', $index + 1)); ?></span>
                                <h3 class="mt-2 text-base font-black leading-snug text-[#071f42]"><?php echo esc_html((string) $feature['title']); ?></h3>
                                <p class="mt-2 text-sm font-semibold leading-6 text-slate-600"><?php echo esc_html((string) ($feature['text'] ?? '')); ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </article>
            <?php endif; ?>
            <article id="photos" class="scroll-mt-24 overflow-hidden rounded border border-[#DDE7F2] bg-white p-5 shadow-card md:p-8" data-product-gallery-root>
                <div class="mb-5 flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.2em] text-[#0B3F7D]"><?php echo esc_html($ui['cinemaGalleryLabel'] ?? 'Product stage'); ?></p>
                        <h2 class="mt-3 text-[clamp(1.6rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em] text-[#071f42]"><?php echo esc_html($ui['photoGalleryTitle'] ?? ($ui['photosLabel'] ?? 'Product Photos')); ?></h2>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="relative aspect-[4/3] overflow-hidden rounded border border-[#DDE7F2] bg-[#F5F8FC] md:aspect-[2/1]">
                        <img class="absolute inset-0 h-full w-full object-contain p-4 transition-opacity duration-200 md:p-8" src="<?php echo esc_url($gallery_items[0]['image'] ?? $main_image); ?>" alt="<?php echo esc_attr($product['title']); ?>" loading="lazy" data-product-gallery-image>
                        <span class="absolute bottom-3 left-3 max-w-[calc(100%-1.5rem)] truncate rounded-sm border border-[#DDE7F2] bg-white px-3 py-1.5 text-xs font-black text-[#071f42]" data-product-gallery-caption><?php echo esc_html($gallery_items[0]['label'] ?? ($ui['mainProductViewLabel'] ?? 'Main product view')); ?></span>
                    </div>
                    <?php if (count($gallery_items) > 1) : ?>
                        <div class="grid grid-cols-4 gap-2 sm:grid-cols-6 lg:grid-cols-8 xl:grid-cols-10">
                            <?php foreach ($gallery_items as $index => $item) : ?>
                                <button class="relative block aspect-[4/3] overflow-hidden rounded-sm border bg-[#F5F8FC] transition <?php echo $index === 0 ? 'border-blue-500 ring-2 ring-blue-500/20' : 'border-[#DDE5EF] hover:border-blue-300'; ?>" type="button" data-product-gallery-thumb data-product-gallery-src="<?php echo esc_url($item['image']); ?>" data-product-gallery-label="<?php echo esc_attr($item['label']); ?>" aria-pressed="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr($item['label']); ?>" title="<?php echo esc_attr($item['label']); ?>">
                                    <img class="absolute inset-0 h-full w-full object-cover" src="<?php echo esc_url($item['image']); ?>" alt="" loading="lazy">
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
            <section id="faq" class="scroll-mt-24"><h2 class="mb-5 text-3xl font-black tracking-[-0.015em] text-[#071f42] md:text-4xl"><?php echo esc_html($ui['faqLabel'] ?? 'FAQ'); ?></h2><div class="grid grid-cols-1 gap-4 lg:grid-cols-2"><?php foreach ($faqs as $faq) : ?><details class="group rounded border border-[#DDE7F2] bg-white px-5 py-4 shadow-card"><summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-black text-[#071f42]"><?php echo esc_html($faq['question']); ?><?php echo boblift_icon('chevron-down', 'h-4 w-4 transition group-open:rotate-180'); ?></summary><p class="mt-3 text-sm font-semibold leading-6 text-slate-600"><?php echo esc_html($faq['answer']); ?></p></details><?php endforeach; ?></div></section>
            <section id="quote" class="boblift-product-quote-section relative left-1/2 w-screen -translate-x-1/2 scroll-mt-24 overflow-hidden border-y border-[#DDE7F2] bg-[radial-gradient(circle_at_70%_28%,rgba(245,154,60,0.16),transparent_28%),linear-gradient(135deg,#ffffff_0%,#eaf2ff_100%)] py-12 text-[#071f42] shadow-card md:py-16">
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
