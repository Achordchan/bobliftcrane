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
$inquiry_url = $product['inquiry_url'] ?? boblift_url('/quote');
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
$hero_specs = array_slice(array_values(array_filter($specs, static fn($spec): bool => is_array($spec) && ($spec['label'] ?? '') !== '' && ($spec['value'] ?? '') !== '')), 0, 4);
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
$technical_specs = array_merge(
    [['label' => (string) ($ui['specLabels']['model'] ?? 'Model'), 'value' => $product['model'] ?? '-']],
    $specs,
    [['label' => (string) ($ui['specLabels']['parameterReview'] ?? 'Parameter Review'), 'value' => $review_status_label]]
);
$product_intro_html = trim((string) ($product['content'] ?? ''));
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
$color_variants = [];
foreach (($product['color_variants'] ?? []) as $variant) {
    if (!is_array($variant)) continue;
    $variant_image = trim((string) ($variant['image'] ?? ($variant['url'] ?? '')));
    $variant_label = trim((string) ($variant['label'] ?? ''));
    if ($variant_image === '' || $variant_label === '') continue;
    $variant_color = sanitize_hex_color((string) ($variant['color'] ?? '')) ?: '#BD2F42';
    $color_variants[] = ['label' => $variant_label, 'color' => $variant_color, 'image' => $image_url($variant_image)];
}
if (!$color_variants) {
    $color_variants[] = ['label' => (string) ($ui['defaultColorLabel'] ?? 'Default'), 'color' => '#BD2F42', 'image' => $main_image];
}
$hero_image = (string) ($color_variants[0]['image'] ?? $main_image);
$show_color_switcher = count($color_variants) > 1;
$hero_gallery_items = [];
$hero_gallery_seen = [];
$add_hero_gallery_item = static function (string $image, string $label) use (&$hero_gallery_items, &$hero_gallery_seen): void {
    $image = trim($image);
    if ($image === '') return;
    $key = strtolower($image);
    if (isset($hero_gallery_seen[$key])) return;
    $hero_gallery_seen[$key] = true;
    $hero_gallery_items[] = ['image' => $image, 'label' => $label];
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
if ($show_detail_images) {
    $anchors[] = ['id' => 'details', 'label' => (string) ($ui['detailsLabel'] ?? 'Product Details')];
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
        <div class="pointer-events-none absolute inset-0 boblift-cinema-grid opacity-65"></div>
        <div class="pointer-events-none absolute -end-[14%] top-[8%] h-[560px] w-[560px] rounded-full bg-blue-300/28 blur-[120px]"></div>
        <div class="pointer-events-none absolute start-[8%] top-[14%] h-[360px] w-[360px] rounded-full bg-gold/16 blur-[110px]"></div>
        <div class="page-shell relative z-10 py-4 lg:py-5 xl:py-6">
            <div class="grid grid-cols-1 items-center gap-6 lg:grid-cols-2 lg:gap-8 xl:gap-10">
                <div class="relative z-20 order-2 max-w-none lg:order-1">
                    <p class="boblift-product-hero-kicker inline-flex rounded-full border border-blue-100 bg-white/72 px-4 py-2 text-[11px] font-black uppercase tracking-[0.24em] text-[#0B3F7D] shadow-[0_14px_34px_rgba(8,35,74,0.08)] backdrop-blur-xl"><?php echo esc_html($product['category_title'] ?? 'BOB-LIFT Product'); ?></p>
                    <h1 class="boblift-product-hero-title mt-5 max-w-none text-[clamp(46px,4.8vw,92px)] font-black leading-[0.88] tracking-[-0.045em] text-[#071f42] drop-shadow-[0_18px_28px_rgba(8,35,74,0.10)]"><?php echo esc_html($hero_title); ?></h1>
                    <?php if ($show_hero_full_name) : ?>
                        <p class="mt-3 max-w-[760px] text-[clamp(20px,1.55vw,30px)] font-black leading-tight tracking-[-0.045em] text-[#0B3F7D]"><?php echo esc_html($product_title); ?></p>
                    <?php endif; ?>
                    <p class="mt-5 line-clamp-4 max-w-[720px] text-[clamp(14px,0.95vw,18px)] font-semibold leading-7 text-slate-600 md:leading-8"><?php echo esc_html($product['short_description'] ?? ''); ?></p>
                    <div class="boblift-product-hero-actions mt-5 flex flex-nowrap items-center gap-2.5 overflow-x-auto pb-2 pt-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:gap-3">
                        <button class="boblift-product-hero-request inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-full bg-[#071f42] px-5 text-[13px] font-black text-white shadow-[0_24px_54px_rgba(8,35,74,0.18)] transition hover:-translate-y-0.5 hover:bg-[#0B3F7D] sm:px-6 sm:text-sm" type="button" data-inquiry-modal-open data-inquiry-product="<?php echo esc_attr($product['title']); ?>" data-inquiry-product-slug="<?php echo esc_attr((string) ($product['slug'] ?? '')); ?>" data-inquiry-products="<?php echo esc_attr($inquiry_products_payload); ?>"><?php echo esc_html($ui['requestQuoteLabel'] ?? 'Request Quote'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></button>
                        <button class="boblift-product-hero-quote-toggle inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-full border border-blue-100 bg-white/74 px-5 text-[13px] font-black text-[#0B3F7D] shadow-[0_16px_38px_rgba(8,35,74,0.08)] backdrop-blur-xl transition hover:-translate-y-0.5 hover:border-gold/60 hover:bg-white sm:px-6 sm:text-sm" type="button" data-product-quote-add data-product-id="<?php echo esc_attr((string) ($product['id'] ?? ($product['slug'] ?? $product['title']))); ?>" data-product-slug="<?php echo esc_attr((string) ($product['slug'] ?? '')); ?>" data-title="<?php echo esc_attr($product['title']); ?>" data-image="<?php echo esc_url($main_image); ?>" data-category-title="<?php echo esc_attr($product['category_title'] ?? 'BOB-LIFT'); ?>" data-detail-url="<?php echo esc_url($product['detail_url'] ?? get_permalink()); ?>" data-summary="<?php echo esc_attr($product['short_description'] ?? ''); ?>" data-label-add="<?php echo esc_attr($hero_quote_list_add_label); ?>" data-label-active="<?php echo esc_attr($hero_quote_list_active_label); ?>"><?php echo boblift_icon('heart', 'h-4 w-4'); ?><span data-product-quote-add-label><?php echo esc_html($hero_quote_list_add_label); ?></span></button>
                        <button class="boblift-product-hero-catalog inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-full border border-[#D8E2EF] bg-white/54 px-5 text-[13px] font-black text-[#071f42] transition hover:-translate-y-0.5 hover:border-blue-300 hover:bg-white sm:px-6 sm:text-sm" type="button" data-catalog-modal-open data-catalog-product="<?php echo esc_attr($product['title']); ?>" data-catalog-product-slug="<?php echo esc_attr((string) ($product['slug'] ?? '')); ?>" data-catalog-url="<?php echo esc_url($catalog_url); ?>"><?php echo boblift_icon('download', 'h-4 w-4'); ?><?php echo esc_html($hero_pdf_label); ?></button>
                    </div>
                    <div class="mt-5 grid grid-cols-2 gap-3 2xl:grid-cols-4">
                        <?php foreach ($hero_specs as $spec) : ?>
                            <div class="min-h-[96px] rounded-[20px] border border-white/80 bg-white/68 p-4 shadow-[0_16px_42px_rgba(8,35,74,0.08)] backdrop-blur-xl"><?php echo boblift_icon($spec_icon($spec), 'h-4 w-4 text-[#0B3F7D]'); ?><p class="mt-2 text-[9px] font-black uppercase tracking-[0.14em] text-slate-500"><?php echo esc_html($spec['label']); ?></p><p class="mt-1 text-base font-black leading-tight tracking-[-0.035em] text-[#071f42] 2xl:text-lg"><?php echo esc_html($hero_spec_value((string) ($spec['value'] ?? ''))); ?></p></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="relative order-1 grid min-h-[320px] place-items-center overflow-visible sm:min-h-[420px] lg:order-2 lg:min-h-[540px] xl:min-h-[620px]" data-product-color-root data-product-hero-gallery-root data-product-hero-primary-src="<?php echo esc_url($hero_image); ?>" data-product-hero-active-index="0">
                    <div class="boblift-cinema-product-stage relative z-10 aspect-[1.35/1] w-full max-w-none" data-product-hotspot-stage>
                        <img class="absolute inset-0 z-10 h-full w-full object-contain p-0 drop-shadow-[0_54px_42px_rgba(8,35,74,0.20)] transition-opacity duration-500 ease-out" src="<?php echo esc_url($hero_image); ?>" alt="<?php echo esc_attr($product['title']); ?>" loading="eager" fetchpriority="high" decoding="async" data-product-color-current data-product-hero-gallery-image>
                        <img class="absolute inset-0 z-20 h-full w-full object-contain p-0 opacity-0 drop-shadow-[0_54px_42px_rgba(8,35,74,0.20)] transition-opacity duration-500 ease-out" src="<?php echo esc_url($hero_image); ?>" alt="" aria-hidden="true" loading="lazy" decoding="async" data-product-color-next>
                        <div class="absolute inset-0 z-30 hidden lg:block" data-product-hotspot-root data-product-hero-primary-only>
                            <?php foreach ($hotspots as $index => $hotspot) :
                                $label = (string) ($hotspot['label'] ?? '');
                                $x = max(0, min(100, (float) ($hotspot['x'] ?? 50)));
                                $y = max(0, min(100, (float) ($hotspot['y'] ?? 50)));
                                $label_position = $x > 58 ? 'right-full mr-3' : 'left-full ml-3';
                            ?>
                                <button class="absolute grid h-7 w-7 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full border border-white/85 bg-[#071f42]/90 text-[11px] font-bold text-white shadow-[0_10px_22px_rgba(8,35,74,0.18)] ring-2 ring-white/70 backdrop-blur transition hover:scale-105 hover:bg-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-200/70" style="left:<?php echo esc_attr((string) $x); ?>%;top:<?php echo esc_attr((string) $y); ?>%" type="button" data-product-hotspot aria-expanded="false" aria-label="<?php echo esc_attr($label); ?>">
                                    <span><?php echo esc_html((string) ($index + 1)); ?></span>
                                    <span class="pointer-events-none absolute <?php echo esc_attr($label_position); ?> top-1/2 z-40 hidden -translate-y-1/2 whitespace-nowrap rounded-[10px] border border-white/70 bg-[#071f42]/95 px-2.5 py-1.5 text-[11px] font-bold leading-none tracking-normal text-white shadow-[0_12px_28px_rgba(8,35,74,0.16)] backdrop-blur" data-product-hotspot-label><?php echo esc_html($label); ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php if (count($hero_gallery_items) > 1) : ?>
                        <div class="absolute left-1/2 z-40 flex max-w-[calc(100%-2rem)] -translate-x-1/2 items-center gap-2 overflow-x-auto rounded-full border border-white/80 bg-white/76 p-2 shadow-[0_22px_54px_rgba(8,35,74,0.14)] backdrop-blur-xl [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden <?php echo $show_color_switcher ? 'bottom-16' : 'bottom-3'; ?>" aria-label="<?php echo esc_attr($ui['photoGalleryTitle'] ?? 'Product Photos'); ?>">
                            <?php foreach ($hero_gallery_items as $index => $item) : ?>
                                <button class="grid h-11 w-14 shrink-0 place-items-center overflow-hidden rounded-full border p-1 transition <?php echo $index === 0 ? 'border-blue-500 bg-blue-50 shadow-[0_12px_30px_rgba(37,99,235,0.12)]' : 'border-[#DDE5EF] bg-white/80 hover:border-blue-300'; ?>" type="button" data-product-hero-gallery-thumb data-product-hero-gallery-index="<?php echo esc_attr((string) $index); ?>" data-product-hero-gallery-src="<?php echo esc_url($item['image']); ?>" data-product-hero-gallery-label="<?php echo esc_attr($item['label']); ?>" aria-pressed="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr($item['label']); ?>">
                                    <img class="h-full w-full rounded-full object-cover" src="<?php echo esc_url($item['image']); ?>" alt="" loading="lazy" decoding="async">
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($show_color_switcher) : ?>
                        <div class="absolute bottom-3 left-1/2 z-40 flex max-w-[calc(100%-2rem)] -translate-x-1/2 items-center gap-2 overflow-x-auto rounded-full border border-white/80 bg-white/76 p-2 shadow-[0_22px_54px_rgba(8,35,74,0.14)] backdrop-blur-xl" aria-label="<?php echo esc_attr($ui['colorOptionsLabel'] ?? 'Product color options'); ?>" data-product-hero-primary-only data-product-hero-color-controls>
                            <?php foreach ($color_variants as $index => $variant) : ?>
                                <button class="inline-flex h-9 shrink-0 items-center gap-2 rounded-full border px-2.5 text-xs font-black transition <?php echo $index === 0 ? 'border-blue-500 bg-blue-50 text-[#0B3F7D]' : 'border-[#DDE5EF] bg-white/80 text-slate-600 hover:border-blue-300 hover:text-[#0B3F7D]'; ?>" type="button" data-product-color-option data-product-color-image="<?php echo esc_url($variant['image']); ?>" data-product-color-label="<?php echo esc_attr($variant['label']); ?>" aria-pressed="<?php echo $index === 0 ? 'true' : 'false'; ?>">
                                    <span class="h-4 w-4 rounded-full border border-black/10 shadow-inner" style="background-color:<?php echo esc_attr($variant['color']); ?>"></span>
                                    <span><?php echo esc_html($variant['label']); ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <div class="sticky top-0 z-30 border-y border-[#DDE7F2] bg-white/82 shadow-[0_18px_44px_rgba(8,35,74,0.08)] backdrop-blur-xl"><nav class="page-shell flex min-h-14 items-center overflow-x-auto [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" aria-label="<?php echo esc_attr($ui['sectionNavLabel'] ?? 'Product detail sections'); ?>"><a class="boblift-cinema-nav-link grid h-14 min-w-14 place-items-center border-x border-[#DDE7F2] text-[#0B3F7D]" href="#product-hero" data-product-section-link aria-label="<?php echo esc_attr($product['title'] ?? 'Product hero'); ?>"><?php echo boblift_icon('home', 'h-5 w-5'); ?></a><?php foreach ($anchors as $anchor) : ?><a class="boblift-cinema-nav-link flex h-14 min-w-max items-center justify-center border-e border-[#DDE7F2] px-5 text-xs font-black text-[#334155] transition hover:bg-blue-50 hover:text-[#0B3F7D] md:px-6 md:text-sm" href="#<?php echo esc_attr($anchor['id']); ?>" data-product-section-link><?php echo esc_html($anchor['label']); ?></a><?php endforeach; ?></nav></div>
    <section class="relative overflow-hidden bg-[linear-gradient(180deg,#F5F7FB_0%,#EEF4FB_42%,#F8FAFC_100%)] py-10">
        <div class="pointer-events-none absolute inset-0 boblift-cinema-grid opacity-35"></div>
        <div class="page-shell relative z-10 min-w-0 space-y-8">
            <article id="technical" class="boblift-cinema-panel scroll-mt-24 overflow-hidden rounded-[30px] border border-white/80 bg-white/78 shadow-[0_30px_90px_rgba(8,35,74,0.10)] backdrop-blur-xl">
                <div class="border-b border-[#DDE7F2] bg-white/58 p-6 md:p-8 lg:p-10">
                    <p class="text-[11px] font-black uppercase tracking-[0.2em] text-[#0B3F7D]"><?php echo esc_html($ui['productIntroLabel'] ?? 'Product overview'); ?></p>
                    <div class="mt-4 max-w-[1120px] space-y-3 text-sm font-semibold leading-7 text-slate-600 md:text-[15px] md:leading-8 [&_p]:m-0 [&_strong]:text-[#071f42] [&_strong]:font-black [&_ul]:m-0 [&_ul]:list-disc [&_ul]:space-y-1 [&_ul]:ps-5"><?php echo wp_kses_post($product_intro_html); ?></div>
                </div>
                <div class="p-5 md:p-6 lg:p-7">
                    <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-[#0B3F7D]"><?php echo esc_html($ui['cinemaSpecsLabel'] ?? 'Engineering data'); ?></p>
                        </div>
                    </div>
                    <dl class="boblift-technical-spec-grid grid overflow-hidden rounded-[22px] border border-[#DDE7F2] bg-[#F8FAFC]/80 xl:grid-cols-3">
                        <?php foreach ($technical_specs as $index => $spec) : ?>
                            <div class="boblift-technical-spec-item min-w-0 border-b border-[#DDE7F2] p-4 md:p-5">
                                <dt class="boblift-technical-spec-label min-w-0 font-black uppercase text-slate-500"><?php echo esc_html($spec['label'] ?? ''); ?></dt>
                                <dd class="boblift-technical-spec-value mt-2 min-w-0 break-words font-black text-[#071f42]"><?php echo esc_html($spec['value'] ?? ''); ?></dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                </div>
            </article>
            <?php if ($show_detail_images) : ?>
                <article id="details" class="boblift-cinema-panel scroll-mt-24 rounded-[30px] border border-white/80 bg-white/74 p-5 shadow-[0_30px_90px_rgba(8,35,74,0.10)] backdrop-blur-xl md:p-7"><div class="mb-5 flex flex-col gap-2 md:flex-row md:items-end md:justify-between"><h2 class="text-3xl font-black tracking-[-0.04em] text-[#071f42] md:text-4xl"><?php echo esc_html($ui['detailImagesTitle'] ?? ($ui['detailsLabel'] ?? 'Product Details')); ?></h2><span class="text-xs font-black uppercase tracking-[0.18em] text-slate-500"><?php echo esc_html($ui['cinemaDetailsLabel'] ?? 'Detail scroll'); ?></span></div><div class="mx-auto max-w-[1180px] overflow-hidden rounded-[22px] border border-[#DDE7F2] bg-white shadow-[inset_0_0_0_1px_rgba(255,255,255,0.55)]"><?php foreach ($detail_image_items as $item) : ?><img class="block h-auto w-full border-b border-[#DDE7F2] last:border-b-0" src="<?php echo esc_url($item['image']); ?>" alt="<?php echo esc_attr($item['label']); ?>" loading="lazy" decoding="async"><?php endforeach; ?></div></article>
            <?php endif; ?>
            <article id="photos" class="boblift-cinema-panel scroll-mt-24 rounded-[30px] border border-white/80 bg-white/74 p-5 shadow-[0_30px_90px_rgba(8,35,74,0.10)] backdrop-blur-xl md:p-7" data-product-gallery-root><div class="mb-5 flex flex-col gap-2 md:flex-row md:items-end md:justify-between"><h2 class="text-3xl font-black tracking-[-0.04em] text-[#071f42] md:text-4xl"><?php echo esc_html($ui['photoGalleryTitle'] ?? ($ui['photosLabel'] ?? 'Product Photos')); ?></h2><span class="text-xs font-black uppercase tracking-[0.18em] text-slate-500"><?php echo esc_html($ui['cinemaGalleryLabel'] ?? 'Product stage'); ?></span></div><div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1fr)_260px]"><div class="relative min-h-[360px] overflow-hidden rounded-[24px] border border-[#DDE7F2] bg-[radial-gradient(circle_at_52%_38%,rgba(255,255,255,0.95)_0%,rgba(219,234,254,0.78)_42%,rgba(226,232,240,0.92)_100%)] md:min-h-[560px]"><span class="pointer-events-none absolute inset-x-[18%] bottom-[8%] h-20 rounded-full bg-slate-500/18 blur-3xl"></span><img class="absolute inset-0 h-full w-full object-contain p-6 drop-shadow-[0_42px_38px_rgba(8,35,74,0.18)] transition-opacity duration-200 md:p-10" src="<?php echo esc_url($gallery_items[0]['image'] ?? $main_image); ?>" alt="<?php echo esc_attr($product['title']); ?>" loading="lazy" data-product-gallery-image><span class="absolute bottom-5 start-5 rounded-full border border-white/80 bg-white/80 px-4 py-2 text-xs font-black text-[#071f42] shadow-[0_14px_34px_rgba(8,35,74,0.12)] backdrop-blur-xl" data-product-gallery-caption><?php echo esc_html($gallery_items[0]['label'] ?? ($ui['mainProductViewLabel'] ?? 'Main product view')); ?></span></div><?php if (count($gallery_items) > 1) : ?><div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-1"><?php foreach ($gallery_items as $index => $item) : ?><button class="group grid min-h-[104px] grid-cols-[86px_1fr] items-center gap-3 rounded-[18px] border p-3 text-start transition hover:-translate-y-0.5 <?php echo $index === 0 ? 'border-blue-500 bg-blue-50 shadow-[0_12px_30px_rgba(37,99,235,0.12)]' : 'border-[#DDE5EF] bg-white/80 hover:border-blue-300'; ?>" type="button" data-product-gallery-thumb data-product-gallery-src="<?php echo esc_url($item['image']); ?>" data-product-gallery-label="<?php echo esc_attr($item['label']); ?>" aria-pressed="<?php echo $index === 0 ? 'true' : 'false'; ?>"><span class="relative block h-20 w-20 overflow-hidden rounded-[14px] bg-white"><img class="absolute inset-0 h-full w-full object-cover" src="<?php echo esc_url($item['image']); ?>" alt="<?php echo esc_attr($item['label']); ?>" loading="lazy"></span><span class="text-sm font-black leading-5 text-[#071f42]"><?php echo esc_html($item['label']); ?></span></button><?php endforeach; ?></div><?php endif; ?></div></article>
            <section id="faq" class="scroll-mt-24"><h2 class="mb-5 text-3xl font-black tracking-[-0.04em] text-[#071f42] md:text-4xl"><?php echo esc_html($ui['faqLabel'] ?? 'FAQ'); ?></h2><div class="grid grid-cols-1 gap-4 lg:grid-cols-2"><?php foreach ($faqs as $faq) : ?><details class="group rounded-[22px] border border-white/80 bg-white/74 px-5 py-4 shadow-[0_18px_54px_rgba(8,35,74,0.08)] backdrop-blur-xl"><summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-black text-[#071f42]"><?php echo esc_html($faq['question']); ?><?php echo boblift_icon('chevron-down', 'h-4 w-4 transition group-open:rotate-180'); ?></summary><p class="mt-3 text-sm font-semibold leading-6 text-slate-600"><?php echo esc_html($faq['answer']); ?></p></details><?php endforeach; ?></div></section>
            <section id="quote" class="boblift-product-quote-section relative left-1/2 w-screen -translate-x-1/2 scroll-mt-24 overflow-hidden border-y border-[#DDE7F2] bg-[radial-gradient(circle_at_70%_28%,rgba(245,154,60,0.16),transparent_28%),linear-gradient(135deg,#ffffff_0%,#eaf2ff_100%)] py-12 text-[#071f42] shadow-[0_30px_90px_rgba(8,35,74,0.12)] md:py-16">
                <div class="page-shell grid items-center gap-8 lg:grid-cols-[minmax(0,1fr)_auto] xl:gap-12">
                    <div class="boblift-product-quote-copy flex items-start gap-5 md:gap-6">
                        <div class="hidden h-20 w-20 shrink-0 place-items-center rounded-[24px] border border-blue-100 bg-white/74 text-[#0B3F7D] shadow-[0_16px_38px_rgba(8,35,74,0.08)] md:grid"><?php echo boblift_icon('mail', 'h-10 w-10'); ?></div>
                        <div class="min-w-0">
                            <h2 class="boblift-product-quote-title max-w-[1280px] text-[clamp(34px,3vw,58px)] font-black leading-[1.02] tracking-[-0.055em]"><?php echo esc_html($quote_title); ?></h2>
                            <p class="boblift-product-quote-text mt-4 max-w-[1120px] text-sm font-semibold leading-7 text-slate-600 md:text-base md:leading-8"><?php echo esc_html($quote_description); ?></p>
                        </div>
                    </div>
                    <div class="boblift-product-quote-actions flex flex-wrap gap-3 lg:justify-end">
                        <button class="boblift-product-quote-action boblift-product-quote-action-primary inline-flex h-14 items-center justify-center gap-3 rounded-full bg-[#071f42] px-7 text-sm font-black text-white transition hover:-translate-y-0.5 hover:bg-[#0B3F7D]" type="button" data-inquiry-modal-open data-inquiry-product="<?php echo esc_attr($product['title']); ?>" data-inquiry-product-slug="<?php echo esc_attr((string) ($product['slug'] ?? '')); ?>" data-inquiry-products="<?php echo esc_attr($inquiry_products_payload); ?>"><?php echo esc_html($ui['requestQuoteLabel'] ?? 'Request Quote'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></button>
                        <a class="boblift-product-quote-action boblift-product-quote-action-secondary inline-flex h-14 items-center justify-center gap-3 rounded-full border border-blue-100 bg-white/70 px-7 text-sm font-black text-[#0B3F7D] transition hover:-translate-y-0.5 hover:border-blue-300" href="<?php echo esc_url($contact_url); ?>"><?php echo esc_html($ui['contactEngineerLabel'] ?? 'Contact Engineer'); ?> <?php echo boblift_icon('mail', 'h-4 w-4'); ?></a>
                    </div>
                </div>
            </section>
        </div>
    </section>
</div>
<?php get_footer(); ?>
