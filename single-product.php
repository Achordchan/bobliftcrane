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
// The routed product is already the one for this language; a slug lookup could return the English product it shares a slug with.
$product = $queried_product instanceof WP_Post && $queried_product->post_type === 'product' && $queried_product->post_status === 'publish'
    ? boblift_product_from_post($queried_product)
    : boblift_find_product(get_post_field('post_name', get_queried_object_id()) ?: get_query_var('product'));
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
    // The inquiry form marks a previous model, so the customer sees which one they ask about.
    'badge' => boblift_product_is_legacy((int) get_the_ID()) ? boblift_localized_static_text('Previous model') : '',
]], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]';
$inquiry_url = $product['inquiry_url'] ?? boblift_url('/contact.html#inquiry');
$contact_url = boblift_url('/contact');
$ui = boblift_site_ui('productDetail');
// The hero downloads this product's own catalog, else the brochure of its range.
$hero_pdf_url = boblift_product_catalog_url($product);
$hero_pdf_file = '';
$hero_pdf_size = '';
if ($hero_pdf_url === '') {
    $hero_brochure = current(boblift_brochure_items(boblift_brochure_families_for_product($product)));
    if ($hero_brochure) {
        $hero_pdf_url = $hero_brochure['url'];
        $hero_pdf_file = $hero_brochure['file'];
        $hero_pdf_size = (string) ($hero_brochure['size'] ?? '');
    }
}
$hero_pdf_label = boblift_localized_static_text('Download PDF');
// WhatsApp inquiry with the model and page link filled in.
$hero_whatsapp_href = '';
$hero_whatsapp_number = (string) (boblift_contact_settings()['whatsappNumber'] ?? '');
if ($hero_whatsapp_number !== '') {
    $hero_whatsapp_product = trim(($product['model'] ?? '') !== '' && strcasecmp((string) $product['model'], (string) ($product['title'] ?? '')) !== 0 ? $product['model'] . ' (' . ($product['title'] ?? '') . ')' : (string) ($product['title'] ?? ''));
    $hero_whatsapp_href = 'https://wa.me/' . $hero_whatsapp_number . '?text=' . rawurlencode('Hello BOB-LIFT, I would like a quote for the ' . $hero_whatsapp_product . ': ' . ($product['detail_url'] ?? get_permalink()));
}
// Written out under the buttons, so visitors can also copy them: "8615852499415" reads as "+86 158 5249 9415".
$hero_contact = boblift_contact_settings();
$hero_whatsapp_display = preg_match('/^86(\d{3})(\d{4})(\d{4})$/', $hero_whatsapp_number, $m) ? "+86 {$m[1]} {$m[2]} {$m[3]}" : ($hero_whatsapp_number !== '' ? '+' . $hero_whatsapp_number : '');
$hero_email = trim((string) ($hero_contact['email'] ?? ''));
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
// Chart and parameter images belong with the drawings, not in the photo gallery.
$photo_items = [];
$drawing_images = array_column($drawings, 'image');
foreach ($gallery_items as $item) {
    if (!preg_match('/\b(parameter|chart|dimension|specification)\b/', strtolower((string) ($item['label'] ?? '')))) {
        $photo_items[] = $item;
        continue;
    }
    if ($item['image'] === '' || in_array($item['image'], $drawing_images, true)) {
        continue;
    }
    $chart_id = attachment_url_to_postid($item['image']);
    $chart_size = $chart_id ? wp_get_attachment_image_src($chart_id, 'full') : false;
    $drawings[] = ['image' => $item['image'], 'label' => $item['label'], 'width' => $chart_size ? (int) $chart_size[1] : 0, 'height' => $chart_size ? (int) $chart_size[2] : 0];
    $drawing_images[] = $item['image'];
}
$gallery_items = $photo_items;
if (!$gallery_items) {
    $gallery_items = [['image' => $main_image, 'label' => (string) ($ui['mainProductViewLabel'] ?? 'Main product view')]];
}
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
$video_ui = boblift_site_ui('videoCenter');
$product_videos = boblift_product_videos(get_the_ID());
// Other models from this product's most specific category, nearest in capacity first, shown in capacity order.
$related_products = [];
$related_term = null;
$related_terms = get_the_terms(get_the_ID(), 'product_category');
if (is_array($related_terms) && $related_terms) {
    // Deepest category first, by ancestor count rather than parent id.
    $term_depth = static fn(WP_Term $term): int => count(get_ancestors($term->term_id, 'product_category', 'taxonomy'));
    usort($related_terms, static fn(WP_Term $a, WP_Term $b): int => ($term_depth($b) <=> $term_depth($a)) ?: strcasecmp($a->name, $b->name) ?: ($a->term_id <=> $b->term_id));
    $related_term = $related_terms[0];
    // Current models only: previous models are set beside their current equivalent in Model history instead.
    $related_ids = get_posts(boblift_without_legacy([
        'post_type' => 'product',
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields' => 'ids',
        'post__not_in' => [get_the_ID()],
        'tax_query' => [['taxonomy' => 'product_category', 'field' => 'term_id', 'terms' => $related_term->term_id]],
    ]));
    // One meta query for all candidates, then sort on the loaded capacities.
    update_meta_cache('post', $related_ids);
    $capacities = [];
    foreach ($related_ids as $related_id) {
        $capacities[$related_id] = (float) get_post_meta($related_id, '_capacity_value', true);
    }
    $own_capacity = (float) get_post_meta(get_the_ID(), '_capacity_value', true);
    usort($related_ids, static fn(int $a, int $b): int => (abs($capacities[$a] - $own_capacity) <=> abs($capacities[$b] - $own_capacity)) ?: ($a <=> $b));
    $related_ids = array_slice($related_ids, 0, 4);
    usort($related_ids, static fn(int $a, int $b): int => ($capacities[$a] <=> $capacities[$b]) ?: ($a <=> $b));
    foreach ($related_ids as $related_id) {
        $related_products[] = boblift_product_from_post(get_post($related_id));
    }
}
// Previous models (inc/product-lifecycle.php): this model beside the other configurations of its capacity.
$is_legacy_model = boblift_product_is_legacy((int) get_the_ID());
$model_history = $related_term instanceof WP_Term ? boblift_product_model_history((int) get_the_ID(), $related_term) : [];
// The English product's categories with their ancestors, so a model filed under a sub-type still counts for its family and step set.
$process_slugs = boblift_product_category_lineage(get_the_ID());
// Generated range guide, spec groups and model FAQs (inc/product-guide.php), for truck and marine cranes with a decoded model code.
$crane_family = boblift_product_crane_family($process_slugs);
$product_guide = boblift_product_guide($product, $crane_family, $related_term);
if ($model_history) {
    // Model history sets the same capacity class side by side, previous models included.
    $product_guide['compare'] = [];
}
$has_product_guide = $product_guide['intro'] !== '' || $product_guide['cards'] || $product_guide['compare'] || $product_guide['steps'] !== '' || $product_guide['supply'] !== '';
// The editor's short summary opens Product details (it used to sit in the hero), whether or not there is body text.
$product_summary = trim((string) ($product['short_description'] ?? ''));
$has_overview_copy = $has_product_guide || $product_summary !== '';
$spec_groups = boblift_product_spec_groups(get_the_ID(), $technical_specs, $hero_model !== '');
// Reach, boom angle, outrigger span and the load chart, read off the drawing (see boblift_product_drawing_data()).
$drawing_data = boblift_product_drawing_data((int) get_the_ID());
$anchors = [];
$anchors[] = ['id' => 'technical', 'label' => (string) ($ui['technicalLabel'] ?? 'Technical Specifications')];
if ($detail_body_html !== '' || $product_videos || $has_overview_copy) {
    $anchors[] = ['id' => 'overview', 'label' => (string) ($ui['detailBodyLabel'] ?? 'Product details')];
}
if ($drawings) {
    $anchors[] = ['id' => 'drawing', 'label' => (string) ($ui['drawingLabel'] ?? 'Dimension Drawing')];
}
if ($brochure_features) {
    $anchors[] = ['id' => 'features', 'label' => (string) ($ui['featuresLabel'] ?? 'Key Features')];
}
// Manufacturing and delivery steps: knuckle or telescopic set, by the English product's category.
$process_sets = [
    'knuckle-boom-marine-cranes' => 'knuckle-marine',
    'telescopic-boom-marine-cranes' => 'telescopic-marine',
    'knuckle-boom-truck-mounted-cranes' => 'knuckle',
    'telescopic-boom-truck-mounted-cranes' => 'telescopic',
];
// Order terms (from the user, 2026-10-19) hold for cranes; aerial work platforms, tail lifts and fly jibs are not covered.
$hero_terms_apply = boblift_product_crane_terms_apply(get_the_ID());
$process_set = $process_sets[current(array_intersect(array_keys($process_sets), $process_slugs)) ?: ''] ?? '';
$process_steps = $process_set !== '' ? array_values(array_filter((array) ($ui['processSteps'][$process_set] ?? []), static fn(mixed $step): bool => is_array($step) && trim((string) ($step['image'] ?? '')) !== '')) : [];
if ($process_steps) {
    $anchors[] = ['id' => 'process', 'label' => (string) ($ui['processLabel'] ?? 'Manufacturing & delivery')];
}
$buying_terms = $hero_terms_apply ? boblift_product_buying_terms((float) boblift_product_filter_values(get_the_ID())['capacity'], $ui) : null;
if ($buying_terms) {
    $anchors[] = ['id' => 'buying', 'label' => boblift_localized_static_text('Shipping & warranty')];
}
// Model questions from the data, shown as their own group beside the shared ones.
$model_faqs = boblift_product_guide_faqs($product, $product_guide, $crane_family, $hero_terms_apply);
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
if ($model_history) {
    $anchors[] = ['id' => 'model-history', 'label' => boblift_localized_static_text('Model history')];
}
$related_ui = boblift_site_ui('newsDetail');
$case_detail_ui = boblift_site_ui('caseDetail');
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
        <?php // Old shared links to #photos land on the photos, now in the hero. ?><span id="photos" class="block scroll-mt-24" aria-hidden="true"></span>
        <div class="pointer-events-none absolute inset-0 boblift-cinema-grid opacity-40"></div>
        <div class="page-shell relative z-10 py-6 md:py-8 lg:py-10">
            <div class="grid grid-cols-1 items-center gap-6 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)] lg:gap-10 xl:gap-14">
                <div class="relative z-20 order-2 min-w-0 lg:order-1">
                    <p class="boblift-kicker boblift-product-hero-kicker"><?php echo esc_html($product['category_title'] ?? 'BOB-LIFT Product'); ?></p>
                    <?php // The full name sits inside the h1 with the model, so the heading keeps the product keyword the old site ranked on. ?>
                    <h1 class="mt-3 text-[#071f42]"><span class="boblift-product-hero-title block text-[clamp(44px,4.4vw,84px)] font-black leading-[0.92] tracking-[-0.02em]"><?php echo esc_html($hero_title); ?></span><?php if ($show_hero_full_name) : ?><span class="mt-3 block text-[clamp(18px,1.35vw,24px)] font-medium leading-tight tracking-normal text-slate-700"><?php echo esc_html($product_title); ?></span><?php endif; ?></h1>
                    <?php if ($is_legacy_model) :
                        $legacy_target = $model_history ? '#model-history' : ($related_term instanceof WP_Term && !is_wp_error(get_term_link($related_term)) ? (string) get_term_link($related_term) : boblift_product_archive_url());
                        // The current models of the same capacity (or the nearest), each a direct link: the way on from a previous model.
                        $legacy_current = array_values(array_filter($model_history, static fn(array $item): bool => !$item['legacy']));
                        ?>
                        <?php // A previous model must not pass for a current one: a red-bordered notice right under the name. ?>
                        <div class="mt-5 w-fit max-w-full rounded-sm border border-red-600 bg-red-50 px-4 py-3 text-sm font-semibold leading-6 text-red-900">
                            <p class="flex flex-wrap items-center gap-x-3 gap-y-2">
                                <span class="inline-flex items-center rounded-sm bg-red-600 px-2 py-1 text-[11px] font-black uppercase leading-none tracking-[0.08em] text-white rtl:tracking-normal"><?php echo esc_html(boblift_localized_static_text('Previous model')); ?></span>
                                <span><?php echo esc_html(boblift_localized_static_text('No longer in regular production. It can still be built to order.')); ?></span>
                            </p>
                            <?php if ($legacy_current) : ?>
                                <p class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-2 border-t border-red-200 pt-3">
                                    <span class="text-slate-700"><?php echo esc_html(boblift_localized_static_text('Current model')); ?></span>
                                    <?php foreach ($legacy_current as $current) : ?>
                                        <a class="inline-flex h-9 items-center gap-1.5 rounded-sm bg-[#071f42] px-3.5 text-sm font-black text-white transition hover:bg-[#0B3F7D]" href="<?php echo esc_url($current['url']); ?>"><?php echo esc_html(str_replace('{model}', (string) $current['model'], boblift_localized_static_text('View {model}'))); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4 rtl:rotate-180'); ?></a>
                                    <?php endforeach; ?>
                                    <a class="inline-flex items-center gap-1 font-black text-blue-700 transition hover:text-[#071f42]" href="#model-history"><?php echo esc_html(boblift_localized_static_text('Compare with the current range')); ?></a>
                                </p>
                            <?php else : ?>
                                <a class="mt-2 inline-flex items-center gap-1.5 font-black text-blue-700 transition hover:text-[#071f42]" href="<?php echo esc_url($legacy_target); ?>"><?php echo esc_html(boblift_localized_static_text('See the current models')); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4 rtl:rotate-180'); ?></a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php boblift_render_model_decode($hero_model); ?>
                    <dl class="mt-6 grid grid-cols-2 border-y border-[#D5DEE9] sm:grid-cols-4 lg:mt-7">
                        <?php foreach ($hero_specs as $index => $spec) :
                            [$spec_number, $spec_unit] = $hero_spec_parts((string) ($spec['value'] ?? ''));
                        ?>
                            <div class="flex min-w-0 flex-col py-4 pr-3 <?php echo $index % 2 === 1 ? 'border-l border-[#D5DEE9] ps-4' : ''; ?> <?php echo $index >= 2 ? 'border-t border-[#D5DEE9] sm:border-t-0' : ''; ?> <?php echo $index === 2 ? 'sm:border-l sm:ps-4' : ''; ?>">
                                <dt class="text-[10px] font-black uppercase leading-4 tracking-[0.12em] text-slate-500"><?php echo esc_html($spec['label']); ?></dt>
                                <dd class="mt-auto break-words pt-1.5 font-black leading-tight text-[#071f42] [font-variant-numeric:tabular-nums]"><span class="<?php echo ($spec_unit !== '' || mb_strlen($spec_number) <= 8) ? 'text-[clamp(22px,1.9vw,32px)]' : 'text-base'; ?>"><?php echo esc_html($spec_number); ?></span><?php if ($spec_unit !== '') : ?><span class="<?php echo $spec_unit === '°' ? '' : 'ms-1'; ?> whitespace-nowrap text-sm font-bold text-slate-500"><?php echo esc_html($spec_unit); ?></span><?php endif; ?></dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                    <div class="boblift-product-hero-actions mt-6 flex flex-wrap items-center gap-2.5 sm:gap-3 lg:mt-7">
                        <button class="boblift-product-hero-request inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-sm bg-[#071f42] px-6 text-sm font-black text-white transition hover:bg-[#0B3F7D]" type="button" data-inquiry-modal-open data-inquiry-source="产品页首屏" data-inquiry-product="<?php echo esc_attr($product['title']); ?>" data-inquiry-product-slug="<?php echo esc_attr((string) ($product['slug'] ?? '')); ?>" data-inquiry-products="<?php echo esc_attr($inquiry_products_payload); ?>"><?php echo esc_html($ui['requestQuoteLabel'] ?? 'Request Quote'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></button>
                        <?php if ($hero_pdf_url !== '') : ?>
                            <a class="boblift-product-hero-pdf inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-sm border border-[#D5DEE9] bg-white px-5 text-sm font-black text-[#0B3F7D] transition hover:border-blue-300" href="<?php echo esc_url($hero_pdf_url); ?>" <?php echo $hero_pdf_file !== '' ? 'download="' . esc_attr($hero_pdf_file) . '"' : 'target="_blank" rel="noopener"'; ?>><?php echo boblift_icon('download', 'h-4 w-4'); ?><?php echo esc_html($hero_pdf_label); ?><?php if ($hero_pdf_size !== '') : ?><span class="hidden text-xs font-bold text-slate-400 sm:inline"><?php echo esc_html($hero_pdf_size); ?></span><?php endif; ?></a>
                        <?php endif; ?>
                    </div>
                    <?php if ($hero_whatsapp_href !== '' || $hero_email !== '') : ?>
                        <div class="mt-5 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
                            <span class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-500"><?php echo esc_html(boblift_localized_static_text('Or message us')); ?></span>
                            <?php if ($hero_whatsapp_href !== '') : ?>
                                <a class="inline-flex items-center gap-2 border-b border-[#C9D6E6] pb-0.5 font-black text-[#071f42] transition hover:border-[#25D366]" href="<?php echo esc_url($hero_whatsapp_href); ?>" target="_blank" rel="noopener"><?php echo boblift_icon('whatsapp', 'h-4 w-4 text-[#25D366]'); ?><span class="[font-variant-numeric:tabular-nums]"><?php echo esc_html($hero_whatsapp_display); ?></span></a>
                            <?php endif; ?>
                            <?php if ($hero_email !== '') : ?>
                                <a class="inline-flex items-center gap-2 border-b border-[#C9D6E6] pb-0.5 font-black text-[#071f42] transition hover:border-blue-500" href="<?php echo esc_url('mailto:' . $hero_email . '?subject=' . rawurlencode('BOB-LIFT quote: ' . ($hero_model !== '' ? $hero_model : $product_title))); ?>"><?php echo boblift_icon('mail', 'h-4 w-4 text-slate-400'); ?><?php echo esc_html($hero_email); ?></a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($hero_terms_apply) : ?>
                        <div class="mt-6 border-t border-[#D5DEE9] pt-5">
                            <ul class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-slate-600">
                                <li class="inline-flex items-center gap-2"><?php echo boblift_icon('shield-check', 'h-4 w-4 text-[#0B3F7D]'); ?><?php echo esc_html(boblift_localized_static_text('12-month warranty')); ?></li>
                                <li class="inline-flex items-center gap-2"><?php echo boblift_icon('package-check', 'h-4 w-4 text-[#0B3F7D]'); ?><?php echo esc_html(boblift_localized_static_text('Order from 1 unit')); ?></li>
                                <li><a class="inline-flex items-center gap-1 border-b border-[#C9D6E6] pb-0.5 font-black text-[#071f42] transition hover:border-blue-500" href="#buying"><?php echo esc_html(boblift_localized_static_text('Shipping & warranty')); ?><?php echo boblift_icon('arrow-down', 'h-3.5 w-3.5'); ?></a></li>
                            </ul>
                            <p class="mt-3 text-xs font-normal leading-5 text-slate-500"><?php echo esc_html(boblift_localized_static_text('Confirm your configuration and current availability with our team.')); ?></p>
                        </div>
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
                                $label_position = $x > 58 ? 'end-full mr-3' : 'start-full ms-3';
                            ?>
                                <button class="absolute grid h-7 w-7 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-sm border border-white/85 bg-[#071f42]/90 text-[11px] font-bold text-white shadow-card ring-2 ring-white/70 backdrop-blur transition hover:scale-105 hover:bg-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-200/70" style="left:<?php echo esc_attr((string) $x); ?>%;top:<?php echo esc_attr((string) $y); ?>%" type="button" data-product-hotspot data-hotspot-x="<?php echo esc_attr((string) $x); ?>" data-hotspot-y="<?php echo esc_attr((string) $y); ?>" aria-expanded="false" aria-label="<?php echo esc_attr($label); ?>">
                                    <span><?php echo esc_html((string) ($index + 1)); ?></span>
                                    <span class="pointer-events-none absolute <?php echo esc_attr($label_position); ?> top-1/2 z-40 hidden -translate-y-1/2 whitespace-nowrap rounded border border-white/70 bg-[#071f42]/95 px-2.5 py-1.5 text-[11px] font-bold leading-none tracking-normal text-white shadow-raised backdrop-blur" data-product-hotspot-label><?php echo esc_html($label); ?></span>
                                </button>
                            <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if (count($hero_gallery_items) > 1) : ?>
                            <?php foreach ([[-1, 'start-2 sm:start-3', 'chevron-left', 'Previous photo'], [1, 'end-2 sm:end-3', 'chevron-right', 'Next photo']] as [$step, $side, $icon, $label]) : ?>
                                <button class="absolute <?php echo esc_attr($side); ?> top-1/2 z-40 grid size-10 -translate-y-1/2 place-items-center rounded-sm border border-[#D5DEE9] bg-white/90 text-[#071f42] shadow-card backdrop-blur transition hover:border-blue-300 hover:bg-white hover:text-blue-700 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-blue-100 md:size-11" type="button" data-product-hero-gallery-step="<?php echo esc_attr((string) $step); ?>" aria-label="<?php echo esc_attr(boblift_localized_static_text($label)); ?>"><?php echo boblift_icon($icon, 'h-5 w-5 rtl:-scale-x-100'); ?></button>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
    <div class="sticky top-[var(--boblift-header-h)] z-30 border-y border-[#DDE7F2] bg-white shadow-card"><nav class="boblift-product-section-nav page-shell flex min-h-12 items-center overflow-x-auto [-ms-overflow-style:none] [scrollbar-width:none] md:min-h-14 [&::-webkit-scrollbar]:hidden" aria-label="<?php echo esc_attr($ui['sectionNavLabel'] ?? 'Product detail sections'); ?>" data-product-section-nav><a class="boblift-cinema-nav-link grid h-12 min-w-12 shrink-0 place-items-center border-x border-[#DDE7F2] text-[#0B3F7D] md:h-14 md:min-w-14" href="#product-hero" data-product-section-link aria-label="<?php echo esc_attr($product['title'] ?? 'Product hero'); ?>"><?php echo boblift_icon('home', 'h-5 w-5'); ?></a><?php foreach ($anchors as $anchor) : ?><a class="boblift-cinema-nav-link flex h-12 min-w-max items-center justify-center border-e border-[#DDE7F2] px-4 text-xs font-black md:h-14 text-[#334155] transition hover:bg-blue-50 hover:text-[#0B3F7D] md:px-6 md:text-sm" href="#<?php echo esc_attr($anchor['id']); ?>" data-product-section-link><?php echo esc_html($anchor['label']); ?></a><?php endforeach; ?></nav></div>
    <section class="bg-[#F5F7FB] py-8 md:py-12">
        <div class="page-shell min-w-0 space-y-6 md:space-y-8">
            <article id="technical" class="scroll-mt-24 overflow-hidden rounded border border-[#DDE7F2] bg-white shadow-card">
                <div class="p-6 md:p-8">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="boblift-kicker"><?php echo esc_html($ui['cinemaSpecsLabel'] ?? 'Engineering data'); ?></p>
                            <h2 class="mt-3 text-[clamp(1.6rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em] text-[#071f42]"><?php echo esc_html(trim($hero_model . ' ' . ($ui['specsHeading'] ?? 'Specifications'))); ?></h2>
                        </div>
                        <?php if ($former_models !== '') : ?>
                            <?php // Old-site model names stay on the page so searches for them still land here. ?>
                            <dl class="shrink-0 rounded-sm border border-[#DDE7F2] bg-[#F5F8FC] px-3 py-2 text-right sm:px-4">
                                <dt class="text-[11px] font-normal leading-4 text-slate-500"><?php echo esc_html($ui['formerModelLabel'] ?? 'Former model'); ?></dt>
                                <dd class="mt-0.5 text-sm font-black leading-5 text-[#071f42]"><?php echo esc_html($former_models); ?></dd>
                            </dl>
                        <?php endif; ?>
                    </div>
                </div>
                <?php // With drawings, the specs take the left half and the drawings the right, as on the loader site, so a wide drawing is not blown up to the full card width. ?>
                <?php if ($drawings) : ?><div class="grid grid-cols-[minmax(0,1fr)] border-t border-[#E6EDF5] lg:grid-cols-2"><?php endif; ?>
                <?php
                // Grouped (lifting, hydraulics, machine) when the English table lines up with this language's rows; one table otherwise.
                $spec_sections = $spec_groups ?: [['label' => '', 'rows' => $technical_specs]];
                $spec_range_link = $related_term instanceof WP_Term ? get_term_link($related_term) : '';
                ?>
                <?php
                if (!empty($drawing_data['rows'])) {
                    $spec_sections[] = ['label' => boblift_localized_static_text('Reach and outriggers'), 'rows' => $drawing_data['rows']];
                }
                ?>
                <div class="min-w-0 <?php echo $drawings ? '' : 'border-t border-[#E6EDF5]'; ?>">
                    <?php
                    // A spec sheet, not tiles: label and value on one line, two columns (three without drawings on wide screens),
                    // so fourteen figures fit in the height four tile rows took. The last row of a group fills what is left of its line.
                    $spec_cols = $drawings ? 2 : 3;
                    $spec_fill = ['sm' => [2 => 'sm:col-span-2'], 'lg' => [2 => 'lg:col-span-2', 3 => 'lg:col-span-3']];
                    ?>
                    <?php foreach ($spec_sections as $spec_section) :
                        $spec_rows = array_values($spec_section['rows']);
                        $spec_ones = array_fill(0, count($spec_rows), 1);
                        ?>
                        <?php if ($spec_section['label'] !== '') : ?>
                            <h3 class="border-b border-[#E6EDF5] bg-[#F8FAFC] px-6 py-2 text-[11px] font-black uppercase tracking-[0.14em] text-slate-500 rtl:tracking-normal md:px-8"><?php echo esc_html($spec_section['label']); ?></h3>
                        <?php endif; ?>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 <?php echo $spec_cols === 3 ? 'lg:grid-cols-3' : ''; ?>">
                            <?php foreach ($spec_rows as $spec_index => $spec) :
                                [$spec_number, $spec_unit] = $hero_spec_parts((string) $spec['value']);
                                $spec_is_model = !$spec_groups && $hero_model !== '' && $spec === $technical_specs[0];
                                $spec_last = $spec_index === count($spec_rows) - 1 ? trim(($spec_fill['sm'][boblift_grid_last_item_span($spec_ones, 2)] ?? '') . ' ' . ($spec_cols === 3 ? ($spec_fill['lg'][boblift_grid_last_item_span($spec_ones, 3)] ?? 'lg:col-span-1') : '')) : '';
                            ?>
                                <div class="flex min-w-0 items-baseline justify-between gap-4 border-b border-[#E6EDF5] px-6 py-3 md:px-8 <?php echo esc_attr($spec_last); ?> <?php echo $spec_is_model ? 'bg-[#F5F8FC]' : ''; ?>">
                                    <dt class="min-w-0 text-sm font-normal leading-5 text-slate-500"><?php echo esc_html($spec['label']); ?></dt>
                                    <?php // Short figures stay on one line; a long value (two configurations, a note) wraps within 60% of the row. ?>
                                    <dd class="<?php echo mb_strlen((string) $spec['value']) <= 18 ? 'shrink-0 whitespace-nowrap' : 'min-w-0 max-w-[60%] break-words'; ?> text-end font-black leading-tight text-[#071f42] [font-variant-numeric:tabular-nums]"><span class="text-base md:text-lg"><?php echo esc_html($spec_number); ?></span><?php if ($spec_unit !== '') : ?><span class="<?php echo $spec_unit === '°' ? '' : 'ms-1'; ?> text-xs font-bold text-slate-500 md:text-sm"><?php echo esc_html($spec_unit); ?></span><?php endif; ?></dd>
                                </div>
                            <?php endforeach; ?>
                        </dl>
                    <?php endforeach; ?>
                    <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2 border-t border-[#E6EDF5] px-6 py-4 md:px-8">
                        <p class="max-w-xl text-xs font-normal leading-5 text-slate-500"><?php echo esc_html(boblift_localized_static_text('Figures from the BOB-LIFT brochure for this range. Confirm the final specification with our team before ordering.')); ?></p>
                        <?php if (is_string($spec_range_link) && $spec_range_link !== '') : ?>
                            <a class="inline-flex shrink-0 items-center gap-1.5 text-sm font-black text-blue-700 transition hover:text-[#071f42]" href="<?php echo esc_url($spec_range_link); ?>"><?php echo esc_html(str_replace('{range}', $related_term->name, boblift_localized_static_text('Compare all {range}'))); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4 rtl:rotate-180'); ?></a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($drawings) : ?>
                    <div id="drawing" class="flex scroll-mt-24 flex-col justify-center gap-6 border-t border-[#E6EDF5] p-6 md:p-8 lg:-ms-px lg:border-s lg:border-t-0">
                        <p class="boblift-kicker"><?php echo esc_html($ui['drawingTitle'] ?? 'Dimensions and load chart'); ?></p>
                        <?php // Two drawings sit side by side; every drawing is capped in height, so a portrait sheet stays a preview and opens full size on click.
                        // On phones several drawings become a swipe row at nearly the card's width, with the next one peeking in, instead of two thumbnails,
                        // and the height cap rises to 70% of the screen so a tall, narrow sheet is not shrunk to a sliver. ?>
                        <div class="<?php echo count($drawings) > 1 ? 'boblift-drawing-strip -mx-6 flex snap-x snap-mandatory items-center gap-3 overflow-x-auto scroll-px-6 px-6 [-ms-overflow-style:none] [scrollbar-width:none] sm:mx-0 sm:grid sm:grid-cols-2 sm:gap-4 sm:overflow-visible sm:px-0 [&::-webkit-scrollbar]:hidden' : 'grid items-center'; ?>">
                        <?php foreach ($drawings as $drawing) : ?>
                        <div class="<?php echo count($drawings) > 1 ? 'w-[80%] shrink-0 snap-start sm:w-auto' : ''; ?>" data-drawing-root>
                        <div>
                            <button class="block w-full cursor-zoom-in rounded-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500" type="button" data-drawing-open aria-label="<?php echo esc_attr($drawing['label']); ?>">
                                <img class="mx-auto h-auto max-h-[70svh] w-auto max-w-full sm:max-h-[460px]" src="<?php echo esc_url($drawing['image']); ?>" <?php if (($drawing['width'] ?? 0) > 0 && ($drawing['height'] ?? 0) > 0) : ?>width="<?php echo esc_attr((string) $drawing['width']); ?>" height="<?php echo esc_attr((string) $drawing['height']); ?>"<?php endif; ?> alt="<?php echo esc_attr($drawing['label']); ?>" loading="lazy" decoding="async">
                            </button>
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
                        <p class="text-center text-xs font-normal text-slate-500"><?php echo esc_html($ui['drawingHint'] ?? 'Select the drawing to view it full size.'); ?></p>
                        <?php if (!empty($drawing_data['chart'])) :
                            $chart_max = max(array_column($drawing_data['chart'], 'load'));
                            $chart_kg = static fn(float $kg): string => number_format($kg, 0, '.', ',');
                            ?>
                            <?php
                            // The load chart printed under the drawing, explained in words first: how much the crane lifts as the hook moves out.
                            $chart_first = $drawing_data['chart'][0];
                            $chart_last = $drawing_data['chart'][count($drawing_data['chart']) - 1];
                            $chart_m = static fn(float $m): string => rtrim(rtrim(number_format($m, 2, '.', ''), '0'), '.');
                            $chart_text = strtr(boblift_localized_static_text('The further the hook is from the crane, the less it can lift: {load1} kg at {radius1} m, {load2} kg at {radius2} m.'), ['{load1}' => $chart_kg($chart_first['load']), '{radius1}' => $chart_m($chart_first['radius']), '{load2}' => $chart_kg($chart_last['load']), '{radius2}' => $chart_m($chart_last['radius'])]);
                            ?>
                            <figure class="border-t border-[#E6EDF5] pt-5" aria-labelledby="load-chart-title">
                                <figcaption>
                                    <span id="load-chart-title" class="block text-sm font-black text-[#071f42]"><?php echo esc_html(boblift_localized_static_text('How much it lifts at each reach')); ?></span>
                                    <span class="mt-1 block text-xs leading-5 text-slate-500"><?php echo esc_html($chart_text); ?></span>
                                </figcaption>
                                <div class="-mx-6 mt-4 overflow-x-auto px-6 [scrollbar-width:thin] sm:mx-0 sm:px-0">
                                    <table class="w-full min-w-[340px] border-separate border-spacing-0 text-center [font-variant-numeric:tabular-nums]">
                                        <tbody>
                                            <tr class="align-bottom">
                                                <th class="w-20 pe-3 text-start align-bottom text-[11px] font-semibold leading-4 text-slate-500" scope="row"><?php echo esc_html(boblift_localized_static_text('Lifts (kg)')); ?></th>
                                                <?php foreach ($drawing_data['chart'] as $point) : ?>
                                                    <td class="px-0.5"><span class="block text-[11px] font-black leading-4 text-[#071f42]" dir="ltr"><?php echo esc_html($chart_kg($point['load'])); ?></span><span class="mx-auto mt-1 block w-full max-w-7 rounded-t-sm bg-[#0B3F7D]" style="height:<?php echo esc_attr((string) max(4, round(72 * $point['load'] / $chart_max))); ?>px" aria-hidden="true"></span></td>
                                                <?php endforeach; ?>
                                            </tr>
                                            <tr>
                                                <th class="pe-3 pt-1.5 text-start text-[11px] font-semibold leading-4 text-slate-500" scope="row"><?php echo esc_html(boblift_localized_static_text('Reach (m)')); ?></th>
                                                <?php foreach ($drawing_data['chart'] as $point) : ?>
                                                    <td class="border-t border-[#CBD5E1] px-0.5 pt-1.5 text-[11px] font-semibold text-slate-600" dir="ltr"><?php echo esc_html($chart_m($point['radius'])); ?></td>
                                                <?php endforeach; ?>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <p class="mt-3 text-[11px] leading-4 text-slate-400"><?php echo esc_html(boblift_localized_static_text('Reach is measured from the slewing centre to the hook. Figures from the drawing above.')); ?></p>
                            </figure>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </article>
            <?php if ($detail_body_html !== '' || $product_videos || $has_overview_copy) : ?>
                <?php // overflow-clip, not hidden: a hidden overflow would trap the sticky video column. ?>
                <article id="overview" class="scroll-mt-24 overflow-clip rounded border border-[#DDE7F2] bg-white shadow-card">
                    <?php // Old shared links to #details land on the product details. ?><span id="details" class="block scroll-mt-24" aria-hidden="true"></span>
                    <?php
                    // Left: the full product name, the range intro, key figures and any film; right: the body copy, the
                    // range comparison and the next-model note. One text style throughout, and no film placeholder.
                    $detail_has_sections = trim($detail_body_sections) !== '';
                    $detail_has_body = $detail_body_lead !== '' || $detail_has_sections;
                    // The editor's summary only stands in when there is no body: the body's first paragraph says the same.
                    $detail_summary = $detail_has_body ? '' : $product_summary;
                    $detail_has_right = $detail_has_body || $product_guide['compare'] || $product_guide['steps'] !== '' || $product_guide['supply'] !== '' || $product_guide['intro'] !== '' || $product_guide['cards'] || $detail_summary !== '';
                    $detail_model_label = $hero_model !== '' && strcasecmp($hero_model, $product_title) !== 0 ? $hero_model : '';
                    $detail_text = 'text-[15px] font-normal leading-7 text-slate-600 md:text-base md:leading-[1.8]';
                    ?>
                    <div class="grid gap-8 p-5 sm:p-6 md:p-10 xl:p-12 <?php echo $detail_has_right ? 'lg:grid-cols-2 lg:gap-14 xl:gap-20' : ''; ?>">
                        <div class="min-w-0 <?php echo $detail_has_right ? 'lg:sticky lg:top-[calc(var(--boblift-header-h)+6rem)] lg:self-start' : ''; ?>">
                            <p class="boblift-kicker"><?php echo esc_html($ui['detailBodyLabel'] ?? 'Product details'); ?><?php if ($detail_model_label !== '') : ?><span class="text-slate-400"> · </span><?php echo esc_html($detail_model_label); ?><?php endif; ?></p>
                            <h2 class="mt-3 text-[clamp(1.75rem,3vw,3rem)] font-black leading-[1.05] tracking-[-0.02em] text-[#071f42] [text-wrap:balance]"><?php echo esc_html($product_title); ?></h2>
                        <?php if ($product_videos) :
                            $first_video = $product_videos[0];
                            $video_attrs = static fn(array $video): string => sprintf(
                                'data-video-player="%s" data-video-src="%s" data-video-youtube="%s" data-video-title="%s" data-video-poster="%s"',
                                esc_attr($video['player']),
                                esc_url($video['src']),
                                esc_attr($video['youtube']),
                                esc_attr((string) $video['label']),
                                esc_url(boblift_asset($video['poster']))
                            );
                        ?>
                            <?php // Films play in place; a list below switches between them. Old shared links to #video land here. ?>
                            <div id="video" class="mt-6 scroll-mt-24 lg:mt-8" data-inline-videos>
                                <div class="relative aspect-video overflow-hidden rounded-sm bg-[#071f42]" data-inline-video-stage>
                                    <button class="group absolute inset-0 block w-full focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-blue-200" type="button" data-inline-video-play <?php echo $video_attrs($first_video); ?> aria-label="<?php echo esc_attr((string) $first_video['label']); ?>">
                                        <img class="absolute inset-0 h-full w-full object-cover" src="<?php echo esc_url(boblift_asset($first_video['poster'])); ?>" alt="" loading="lazy" decoding="async" data-inline-video-poster>
                                        <span class="absolute inset-0 m-auto grid size-14 place-items-center rounded-sm bg-white/90 text-[#071f42] shadow-card transition group-hover:bg-white"><?php echo boblift_icon('play', 'h-6 w-6'); ?></span>
                                    </button>
                                </div>
                                <p class="sr-only" data-inline-video-caption><?php echo esc_html((string) $first_video['label']); ?></p>
                                <?php if (count($product_videos) > 1) : ?>
                                    <?php // A strip of small thumbnails, each with its title, like a playlist under the player. ?>
                                    <p class="mt-4 text-[11px] font-black uppercase tracking-[0.14em] text-[#071f42] rtl:tracking-normal"><?php echo esc_html($video_ui['moreVideosLabel'] ?? 'Videos'); ?><span class="ms-2 font-semibold text-slate-400 [font-variant-numeric:tabular-nums]"><?php echo esc_html((string) count($product_videos)); ?></span></p>
                                    <ul class="mt-2 flex flex-wrap gap-3">
                                        <?php foreach ($product_videos as $index => $video) : ?>
                                            <li class="w-24 sm:w-28">
                                                <button class="group block w-full text-start focus-visible:outline-none" type="button" data-inline-video-item <?php echo $video_attrs($video); ?> aria-pressed="<?php echo $index === 0 ? 'true' : 'false'; ?>">
                                                    <span class="relative block aspect-video overflow-hidden rounded-sm bg-[#071f42] ring-2 ring-transparent transition group-hover:ring-blue-300 group-focus-visible:ring-blue-400 group-aria-pressed:ring-[#0B3F7D]">
                                                        <img class="absolute inset-0 h-full w-full object-cover" src="<?php echo esc_url(boblift_asset($video['poster'])); ?>" alt="" loading="lazy" decoding="async">
                                                        <span class="absolute inset-0 m-auto grid size-6 place-items-center rounded-full bg-white/85 text-[#071f42]"><?php echo boblift_icon('play', 'h-3 w-3'); ?></span>
                                                    </span>
                                                    <span class="mt-1.5 line-clamp-2 text-[11px] font-semibold leading-4 text-slate-500 group-aria-pressed:text-[#071f42]"><?php echo esc_html((string) $video['label']); ?></span>
                                                </button>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                <p class="mt-4 text-xs font-normal leading-5 text-slate-500"><?php echo esc_html($video_ui['productVideoNote'] ?? ''); ?></p>
                                <a class="mt-3 inline-flex items-center gap-1.5 text-sm font-black text-blue-700 hover:text-blue-900" href="<?php echo esc_url(boblift_url('/videos.html')); ?>"><?php echo esc_html($video_ui['moreVideosLabel'] ?? 'More videos'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
                            </div>
                        <?php endif; ?>
                        </div>
                        <?php if ($detail_has_right) : ?>
                            <div class="min-w-0 lg:pt-1">
                                <?php if ($product_guide['intro'] !== '') : ?>
                                    <p class="text-base font-normal leading-7 text-slate-700 md:text-lg md:leading-8 [&_strong]:font-black [&_strong]:text-[#071f42]"><?php echo wp_kses_post($product_guide['intro']); ?></p>
                                <?php endif; ?>
                                <?php if ($detail_summary !== '') : ?>
                                    <p class="text-base font-normal leading-7 text-slate-700 md:text-lg md:leading-8"><?php echo esc_html($detail_summary); ?></p>
                                <?php endif; ?>
                                <?php if ($detail_body_lead !== '') : ?>
                                    <div class="boblift-story-prose boblift-product-body boblift-product-lead <?php echo $product_guide['intro'] !== '' || $detail_summary !== '' ? 'mt-5' : ''; ?>"><?php echo wp_kses_post($detail_body_lead); ?></div>
                                <?php endif; ?>
                                <?php if ($detail_has_sections) : ?>
                                    <div class="boblift-story-prose boblift-product-body boblift-product-sections <?php echo $detail_body_lead !== '' ? 'mt-6' : ''; ?>"><?php echo wp_kses_post($detail_body_sections); ?></div>
                                <?php endif; ?>
                                <?php if ($product_guide['cards']) : ?>
                                    <?php // Three figures as one ruled strip under the copy, not three loose boxes. ?>
                                    <ul class="mt-8 grid grid-cols-1 border border-[#DDE7F2] sm:grid-cols-3">
                                        <?php foreach ($product_guide['cards'] as $card_index => $card) : ?>
                                            <li class="min-w-0 p-4 sm:p-5 <?php echo $card_index > 0 ? 'border-t border-[#DDE7F2] sm:border-s sm:border-t-0' : ''; ?>">
                                                <p class="text-[11px] font-black uppercase leading-4 tracking-[0.12em] text-slate-500 rtl:tracking-normal"><?php echo esc_html($card['label']); ?></p>
                                                <p class="mt-2 text-2xl font-black leading-none text-[#071f42] [font-variant-numeric:tabular-nums]"><?php echo esc_html($card['value']); ?></p>
                                                <?php if ($card['text'] !== '') : ?><p class="mt-2 text-sm font-normal leading-5 text-slate-600"><?php echo esc_html($card['text']); ?></p><?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                <?php if ($product_guide['compare']) : ?>
                                    <h3 class="mt-8 text-lg font-black leading-snug text-[#071f42]"><?php echo esc_html($product_guide['compare_title']); ?></h3>
                                    <div class="mt-3 overflow-x-auto">
                                        <table class="w-full min-w-[420px] border-collapse text-start text-sm">
                                            <thead>
                                                <tr class="border-b border-[#D5DEE9] text-[11px] font-black uppercase tracking-[0.1em] text-slate-500">
                                                    <th class="py-2 pe-4 text-start font-black" scope="col"><?php echo esc_html($ui['specLabels']['model'] ?? 'Model'); ?></th>
                                                    <th class="py-2 pe-4 text-start font-black" scope="col"><?php echo esc_html(boblift_localized_static_text('Boom sections')); ?></th>
                                                    <th class="py-2 pe-4 text-start font-black" scope="col"><?php echo esc_html(boblift_localized_static_text('Max lifting moment')); ?></th>
                                                    <th class="py-2 text-start font-black" scope="col"><?php echo esc_html(boblift_localized_static_text('Self weight')); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody class="[font-variant-numeric:tabular-nums]">
                                                <?php foreach ($product_guide['compare'] as $row) : ?>
                                                    <tr class="border-b border-[#E6EDF5] <?php echo $row['current'] ? 'bg-[#F5F8FC]' : ''; ?>">
                                                        <th class="py-2.5 pe-4 text-start font-black text-[#071f42]" scope="row"><?php if ($row['current']) : ?><?php echo esc_html($row['model']); ?> <span class="ms-1 text-[11px] font-black uppercase tracking-[0.1em] text-slate-500"><?php echo esc_html(boblift_localized_static_text('This model')); ?></span><?php else : ?><a class="text-blue-700 underline-offset-4 hover:underline" href="<?php echo esc_url($row['url']); ?>"><?php echo esc_html($row['model']); ?></a><?php endif; ?></th>
                                                        <td class="py-2.5 pe-4 text-slate-700"><?php echo esc_html($row['sections_label'] !== '' ? $row['sections_label'] : '–'); ?></td>
                                                        <td class="py-2.5 pe-4 text-slate-700"><?php echo esc_html($row['moment'] !== '' ? $row['moment'] : '–'); ?></td>
                                                        <td class="py-2.5 text-slate-700"><?php echo esc_html($row['weight'] !== '' ? $row['weight'] : '–'); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                                <?php if ($product_guide['steps'] !== '' || $product_guide['supply'] !== '') : ?>
                                    <?php // The next-model note closes the copy, set apart so its links read as a pointer, not body text. ?>
                                    <div class="<?php echo $detail_has_body || $product_guide['compare'] ? 'mt-8' : ''; ?> rounded border border-[#DDE7F2] bg-[#F8FAFC] p-5">
                                        <h3 class="text-base font-black leading-snug text-[#071f42]"><?php echo esc_html(boblift_localized_static_text('Choosing the right model')); ?></h3>
                                        <?php foreach (array_filter([$product_guide['steps'], $product_guide['supply']]) as $guide_paragraph) : ?>
                                            <p class="mt-2 <?php echo esc_attr($detail_text); ?> [&_a]:font-black [&_a]:text-blue-700 [&_a:hover]:underline"><?php echo wp_kses_post($guide_paragraph); ?></p>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
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
                        <p class="boblift-kicker"><?php echo esc_html($features_series !== '' ? $features_series : ($ui['featuresLabel'] ?? 'Key Features')); ?></p>
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
                                    <p class="mt-1 text-xs font-normal leading-5 text-slate-600 md:text-sm md:leading-6"><?php echo esc_html((string) ($feature['text'] ?? '')); ?></p>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <?php if ($feature_rest) : ?>
                        <ul class="grid gap-x-8 gap-y-3 border-t border-[#E6EDF5] px-6 py-5 md:grid-cols-2 md:px-8 xl:grid-cols-3 <?php echo $feature_cards ? '' : 'mt-6'; ?>">
                            <?php foreach ($feature_rest as $feature) : ?>
                                <li class="flex min-w-0 gap-2.5 text-sm leading-6">
                                    <?php echo boblift_icon('check', 'mt-1 h-4 w-4 shrink-0 text-[#0B3F7D]'); ?>
                                    <span class="min-w-0"><strong class="font-black text-[#071f42]"><?php echo esc_html((string) $feature['title']); ?></strong> <span class="font-normal text-slate-600"><?php echo esc_html((string) ($feature['text'] ?? '')); ?></span></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </article>
            <?php endif; ?>
            <?php if ($process_steps) :
                // Literal class names so Tailwind keeps them.
                $process_cols = [3 => 'lg:grid-cols-3', 4 => 'lg:grid-cols-4', 5 => 'lg:grid-cols-5'][count($process_steps)] ?? 'lg:grid-cols-6';
                ?>
                <article id="process" class="scroll-mt-24 overflow-hidden rounded border border-[#DDE7F2] bg-white shadow-card">
                    <div class="p-6 pb-0 md:p-8 md:pb-0">
                        <p class="boblift-kicker"><?php echo esc_html($ui['processLabel'] ?? 'Manufacturing & delivery'); ?></p>
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
                                <p class="mt-1 text-xs font-normal leading-5 text-slate-600 md:text-sm md:leading-6"><?php echo esc_html((string) ($step['text'] ?? '')); ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </article>
            <?php endif; ?>
            <?php if ($buying_terms) :
                // Every brochure of the product's families, as the brochure card this column replaces showed them.
                $buying_brochures = boblift_brochure_items(boblift_brochure_families_for_product($product));
                $brochure_ui = boblift_site_ui('brochures');
                // Placeholder figures carry a mark for signed-in editors only, so they are replaced before launch.
                $buying_flag = $buying_terms['pending'] && current_user_can('edit_posts') ? '<span class="ms-2 inline-flex rounded-sm border border-amber-300 bg-amber-50 px-1.5 py-0.5 align-middle text-[10px] font-black uppercase tracking-[0.08em] text-amber-700">Placeholder</span>' : '';
                $buying_units = static fn(int $count): string => str_replace('{count}', (string) $count, boblift_localized_static_text($count === 1 ? '{count} unit' : '{count} units'));
                ?>
                <article id="buying" class="scroll-mt-24 overflow-hidden rounded border border-[#DDE7F2] bg-white shadow-card">
                    <div class="p-6 pb-0 md:p-8 md:pb-0">
                        <p class="boblift-kicker"><?php echo esc_html(boblift_localized_static_text('Shipping & warranty')); ?></p>
                        <h2 class="mt-3 text-[clamp(1.6rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em] text-[#071f42]"><?php echo esc_html(boblift_localized_static_text('Shipping, warranty and documents')); ?></h2>
                        <p class="mt-3 max-w-2xl text-sm font-normal leading-7 text-slate-600 md:text-base"><?php echo esc_html(str_replace('{model}', $hero_title, boblift_localized_static_text('How the {model} reaches you, and what happens after it arrives.'))); ?></p>
                    </div>
                    <div class="mt-6 grid gap-px border-t border-[#E6EDF5] bg-[#E6EDF5] md:mt-8 md:grid-cols-3">
                        <section class="bg-white p-6 md:p-8" aria-labelledby="buying-shipping">
                            <h3 id="buying-shipping" class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-500"><?php echo esc_html(boblift_localized_static_text('Shipping')); ?><?php echo $buying_flag; ?></h3>
                            <dl class="mt-4 grid grid-cols-2 gap-4 [font-variant-numeric:tabular-nums]">
                                <?php foreach (['gp20' => '20GP container', 'hq40' => '40HQ container'] as $box_key => $box_label) : if ($buying_terms['loading'][$box_key] <= 0) { continue; } ?>
                                    <div>
                                        <dt class="text-xs font-normal text-slate-500"><?php echo esc_html(boblift_localized_static_text($box_label)); ?></dt>
                                        <dd class="mt-1 text-2xl font-black leading-none text-[#071f42]"><?php echo esc_html($buying_units($buying_terms['loading'][$box_key])); ?></dd>
                                    </div>
                                <?php endforeach; ?>
                            </dl>
                            <p class="mt-5 text-sm font-normal leading-6 text-slate-700"><?php echo esc_html(str_replace('{days}', $buying_terms['lead'], boblift_localized_static_text('Ships in {days} working days from order.'))); ?></p>
                            <p class="mt-2 text-xs font-normal leading-5 text-slate-500"><?php echo esc_html(boblift_localized_static_text('Loading figures are approximate; we confirm the plan with your order.')); ?></p>
                        </section>
                        <section class="bg-white p-6 md:p-8" aria-labelledby="buying-warranty">
                            <h3 id="buying-warranty" class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-500"><?php echo esc_html(boblift_localized_static_text('Warranty and parts')); ?><?php echo $buying_flag; ?></h3>
                            <p class="mt-4 text-2xl font-black leading-none text-[#071f42]"><?php echo esc_html(boblift_localized_static_text('12 months')); ?></p>
                            <p class="mt-3 text-sm font-normal leading-6 text-slate-700"><?php echo esc_html(boblift_localized_static_text('Send the model, serial number and a photo or video of the issue; our team guides the repair and ships the parts.')); ?></p>
                            <p class="mt-4 inline-flex items-center gap-2 text-sm font-black text-[#071f42]"><?php echo boblift_icon('package-check', 'h-4 w-4 text-[#0B3F7D]'); ?><?php echo esc_html(boblift_localized_static_text('Order from 1 unit')); ?></p>
                        </section>
                        <section class="bg-white p-6 md:p-8" aria-labelledby="buying-documents">
                            <?php // Links to #brochures (the brochure card this column replaces on crane pages) land here. ?><span id="brochures" class="block scroll-mt-24" aria-hidden="true"></span>
                            <h3 id="buying-documents" class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-500"><?php echo esc_html(boblift_localized_static_text('Documents')); ?></h3>
                            <ul class="mt-4 grid gap-3 text-sm">
                                <?php foreach ($buying_brochures as $buying_brochure) : ?>
                                    <li class="mb-2 grid grid-cols-[64px_minmax(0,1fr)] items-start gap-4 border-b border-[#E6EDF5] pb-5">
                                        <a class="block" href="<?php echo esc_url($buying_brochure['url']); ?>" download="<?php echo esc_attr($buying_brochure['file']); ?>" tabindex="-1" aria-hidden="true"><img class="aspect-[520/711] w-full rounded-sm border border-[#DDE7F2] bg-white object-cover" src="<?php echo esc_url($buying_brochure['cover']); ?>" width="520" height="711" alt="" loading="lazy" decoding="async"></a>
                                        <div class="min-w-0">
                                            <p class="font-black leading-snug text-[#071f42]"><?php echo esc_html($buying_brochure['title']); ?></p>
                                            <p class="mt-1 text-[11px] font-black uppercase tracking-[0.1em] text-slate-400"><?php echo esc_html(implode(' · ', array_filter(['PDF', str_replace('{count}', (string) $buying_brochure['pages'], (string) ($brochure_ui['pagesLabel'] ?? '{count} pages')), (string) $buying_brochure['size']]))); ?></p>
                                            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2">
                                                <a class="inline-flex items-center gap-1.5 font-black text-[#0B3F7D] transition hover:text-blue-700" href="<?php echo esc_url($buying_brochure['url']); ?>" download="<?php echo esc_attr($buying_brochure['file']); ?>"><?php echo boblift_icon('download', 'h-4 w-4'); ?><?php echo esc_html((string) ($brochure_ui['downloadLabel'] ?? 'Download PDF')); ?></a>
                                                <button class="font-black text-blue-700 transition hover:text-[#071f42]" type="button" data-catalog-modal-open data-catalog-product="<?php echo esc_attr($product_title); ?>" data-catalog-product-slug="<?php echo esc_attr((string) ($product['slug'] ?? '')); ?>" data-catalog-url="<?php echo esc_url($buying_brochure['url']); ?>"><?php echo esc_html((string) ($brochure_ui['emailLabel'] ?? 'Email me a copy')); ?></button>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                                <?php if ($drawings) : ?>
                                    <li><a class="inline-flex items-start gap-2 font-black text-[#071f42] transition hover:text-blue-700" href="#drawing"><?php echo boblift_icon('maximize-2', 'mt-0.5 h-4 w-4 shrink-0 text-[#0B3F7D]'); ?><?php echo esc_html($ui['drawingTitle'] ?? 'Dimensions and load chart'); ?></a></li>
                                <?php endif; ?>
                                <li><a class="inline-flex items-start gap-2 font-black text-[#071f42] transition hover:text-blue-700" href="#technical"><?php echo boblift_icon('file-text', 'mt-0.5 h-4 w-4 shrink-0 text-[#0B3F7D]'); ?><?php echo esc_html(trim($hero_model . ' ' . ($ui['specsHeading'] ?? 'Specifications'))); ?></a></li>
                            </ul>
                        </section>
                    </div>
                </article>
            <?php endif; ?>
            <?php if ($product_cases) : ?>
                <article id="cases" class="scroll-mt-24 overflow-hidden rounded border border-[#DDE7F2] bg-white shadow-card">
                    <div class="flex items-end justify-between gap-4 p-6 pb-0 md:p-8 md:pb-0">
                        <div>
                            <p class="boblift-kicker"><?php echo esc_html($cases_ui['sceneCasesLabel'] ?? 'Application Cases'); ?></p>
                            <h2 class="mt-3 text-[clamp(1.6rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em] text-[#071f42]"><?php echo esc_html($ui['casesTitle'] ?? 'Where this crane works'); ?></h2>
                        </div>
                    </div>
                    <?php if (count($product_cases) === 1) : $case_item = $product_cases[0]; ?>
                        <a class="group grid items-center gap-6 p-6 md:grid-cols-2 md:gap-10 md:p-8" href="<?php echo esc_url($case_item['url']); ?>">
                            <span class="block aspect-[3/2] overflow-hidden rounded-sm bg-[#EDF1F5]"><img class="h-full w-full object-cover" src="<?php echo esc_url(boblift_item_image($case_item)); ?>" alt="<?php echo esc_attr($case_item['title']); ?>" loading="lazy" decoding="async"></span>
                            <span class="block min-w-0">
                                <?php if ($case_item['country'] !== '' || $case_item['application'] !== '') : ?><span class="block text-[11px] font-black uppercase tracking-[0.14em] text-slate-500"><?php echo esc_html(implode(' · ', array_filter([$case_item['country'], $case_item['application']]))); ?></span><?php endif; ?>
                                <span class="mt-2 block text-xl font-black leading-snug text-[#071f42] transition group-hover:text-blue-700 md:text-2xl"><?php echo esc_html($case_item['title']); ?></span>
                                <?php if (trim((string) $case_item['summary']) !== '') : ?><span class="mt-3 line-clamp-3 block text-sm font-normal leading-6 text-slate-600 md:text-base md:leading-7"><?php echo esc_html($case_item['summary']); ?></span><?php endif; ?>
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
                            <p class="boblift-kicker"><?php echo esc_html($cases_ui['customerFeedbackLabel'] ?? 'Customer Feedback'); ?></p>
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
                                    <?php if (($feedback_item['media'][0]['type'] ?? '') === 'video') : ?><span class="absolute inset-0 m-auto grid size-12 place-items-center rounded-sm bg-white/90 text-[#0B3F7D]"><?php echo boblift_icon('play', 'h-5 w-5'); ?></span><?php endif; ?>
                                </span>
                                <span class="mt-3 flex items-center gap-1.5 text-sm font-black text-[#071f42] transition group-hover:text-blue-700 md:text-base"><?php echo boblift_icon('map-pin', 'h-4 w-4 shrink-0 text-gold'); ?><?php echo esc_html($feedback_item['country']); ?></span>
                                <span class="mt-0.5 block text-xs font-normal text-slate-500 md:text-sm"><?php echo esc_html(implode(' · ', array_filter([$feedback_item['model'], $feedback_count_text]))); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($product_feedback_total > count($feedback_shown)) : ?>
                        <a class="flex items-center justify-center gap-1.5 border-t border-[#E6EDF5] px-6 py-4 text-sm font-black text-blue-700 sm:hidden" href="<?php echo esc_url($feedback_list_url); ?>"><?php echo esc_html($ui['viewAllFeedbackLabel'] ?? 'All customer feedback'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
                    <?php endif; ?>
                </article>
            <?php endif; ?>
            <?php
            // Model questions and the shared ones as two groups side by side; the help box sits under the shorter group.
            $faq_groups = array_values(array_filter([
                ['label' => str_replace('{model}', $hero_title, boblift_localized_static_text('About the {model}')), 'items' => $model_faqs],
                ['label' => boblift_localized_static_text('Ordering and service'), 'items' => $faqs],
            ], static fn(array $group): bool => $group['items'] !== []));
            $faq_help_column = count($faq_groups) > 1 ? 0 : 1;
            ?>
            <section id="faq" class="scroll-mt-24 overflow-hidden rounded border border-[#DDE7F2] bg-white shadow-card" aria-labelledby="faq-title">
                <div class="border-b border-[#E6EDF5] p-6 md:p-8">
                    <p class="boblift-kicker"><?php echo esc_html($ui['faqLabel'] ?? 'FAQ'); ?></p>
                    <h2 id="faq-title" class="mt-3 text-[clamp(1.6rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em] text-[#071f42]"><?php echo esc_html(str_replace('{model}', $hero_title, boblift_localized_static_text('Questions about the {model}'))); ?></h2>
                </div>
                <div class="grid lg:grid-cols-2">
                    <?php for ($faq_column = 0; $faq_column < 2; $faq_column++) :
                        $faq_group = $faq_groups[$faq_column] ?? null;
                        if (!$faq_group && $faq_column !== $faq_help_column) { continue; }
                        ?>
                        <div class="min-w-0 p-6 md:p-8 <?php echo $faq_column === 1 ? 'border-t border-[#E6EDF5] lg:border-s lg:border-t-0' : ''; ?>">
                            <?php if ($faq_group) : ?>
                                <h3 class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-500"><?php echo esc_html($faq_group['label']); ?></h3>
                                <div class="mt-3 border-t border-[#E6EDF5]">
                                    <?php foreach ($faq_group['items'] as $faq_index => $faq) : ?>
                                        <details class="group border-b border-[#E6EDF5]">
                                            <summary class="flex cursor-pointer list-none items-start gap-3 py-4 text-[15px] font-black leading-6 text-[#071f42] transition hover:text-blue-700 md:gap-4 md:text-base [&::-webkit-details-marker]:hidden">
                                                <span class="w-6 shrink-0 text-xs font-black leading-6 text-slate-400 [font-variant-numeric:tabular-nums]"><?php echo esc_html(str_pad((string) ($faq_index + 1), 2, '0', STR_PAD_LEFT)); ?></span>
                                                <span class="min-w-0 flex-1"><?php echo esc_html($faq['question']); ?></span>
                                                <span class="grid size-6 shrink-0 place-items-center rounded-sm border border-[#DDE7F2] text-slate-500 transition group-open:rotate-180 group-open:border-[#0B3F7D] group-open:text-[#0B3F7D]"><?php echo boblift_icon('chevron-down', 'h-3.5 w-3.5'); ?></span>
                                            </summary>
                                            <p class="pb-5 pe-9 ps-9 text-sm font-normal leading-7 text-slate-600 md:pe-10 md:ps-10 md:text-[15px]"><?php echo esc_html($faq['answer']); ?></p>
                                        </details>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($faq_column === $faq_help_column) : ?>
                                <div class="<?php echo $faq_group ? 'mt-8' : ''; ?> rounded border border-[#DDE7F2] bg-[#F8FAFC] p-5 md:p-6">
                                    <p class="text-base font-black text-[#071f42]"><?php echo esc_html(boblift_localized_static_text('Not answered here?')); ?></p>
                                    <p class="mt-1.5 text-sm font-normal leading-6 text-slate-600"><?php echo esc_html(boblift_localized_static_text('Send us your question and our team replies with answers for your site.')); ?></p>
                                    <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-3">
                                        <button class="inline-flex h-11 items-center justify-center gap-2 rounded-sm bg-[#071f42] px-5 text-sm font-black text-white transition hover:bg-[#0B3F7D]" type="button" data-inquiry-modal-open data-inquiry-source="产品页常见问题" data-inquiry-product="<?php echo esc_attr($product['title']); ?>" data-inquiry-product-slug="<?php echo esc_attr((string) ($product['slug'] ?? '')); ?>" data-inquiry-products="<?php echo esc_attr($inquiry_products_payload); ?>"><?php echo esc_html(boblift_localized_static_text('Ask a question')); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></button>
                                        <?php if ($hero_whatsapp_href !== '') : ?>
                                            <a class="inline-flex items-center gap-2 border-b border-[#C9D6E6] pb-0.5 text-sm font-black text-[#071f42] transition hover:border-[#25D366]" href="<?php echo esc_url($hero_whatsapp_href); ?>" target="_blank" rel="noopener"><?php echo boblift_icon('whatsapp', 'h-4 w-4 text-[#25D366]'); ?><span class="[font-variant-numeric:tabular-nums]"><?php echo esc_html($hero_whatsapp_display); ?></span></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </section>
            <?php // A catalog saved on this product stays downloadable next to the family brochures. ?>
            <?php $catalog_url = function_exists('boblift_product_catalog_url') ? boblift_product_catalog_url($product) : ''; if ($catalog_url !== '') : ?>
                <p class="boblift-product-catalog-link"><a class="inline-flex h-12 items-center justify-center gap-2 rounded-sm border border-[#D8E2EF] bg-white px-5 text-sm font-black text-[#071f42] transition hover:-translate-y-0.5 hover:border-blue-300 hover:text-blue-700" href="<?php echo esc_url($catalog_url); ?>" target="_blank" rel="noopener"><?php echo boblift_icon('file-text', 'h-4 w-4'); ?><?php echo esc_html((string) ($ui['heroPdfLabel'] ?? 'PDF Catalog')); ?></a></p>
            <?php endif; ?>
            <?php if (!$buying_terms) boblift_render_brochures(['context' => 'product', 'families' => boblift_brochure_families_for_product($product), 'product' => $product_title, 'product_slug' => (string) ($product['slug'] ?? '')]); ?>
            <?php boblift_render_product_model_history($model_history, (int) get_the_ID(), $is_legacy_model); ?>
            <?php if ($related_products && $related_term instanceof WP_Term) :
                $related_link = get_term_link($related_term);
                ?>
                <section id="related" class="scroll-mt-24 overflow-hidden rounded border border-[#DDE7F2] bg-white shadow-card" aria-labelledby="related-title">
                    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-3 p-6 pb-0 md:p-8 md:pb-0">
                        <div>
                            <p class="boblift-kicker"><?php echo esc_html(boblift_localized_static_text('Related products')); ?></p>
                            <h2 id="related-title" class="mt-3 text-[clamp(1.6rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em] text-[#071f42]"><?php echo esc_html($related_term->name); ?></h2>
                        </div>
                        <?php if (!is_wp_error($related_link)) : ?>
                            <a class="inline-flex items-center gap-1.5 text-sm font-black text-blue-700 transition hover:text-[#071f42]" href="<?php echo esc_url($related_link); ?>"><?php echo esc_html($case_detail_ui['viewAllLabel'] ?? 'View all'); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4 rtl:rotate-180'); ?></a>
                        <?php endif; ?>
                    </div>
                    <?php // Product cards, not article cards: the cut-out on the product backdrop, the capacity line, two figures and the way to the page. ?>
                    <ul class="grid grid-cols-2 gap-3 p-6 md:gap-5 md:p-8 lg:grid-cols-4">
                        <?php foreach ($related_products as $related) :
                            $related_model = trim((string) ($related['model'] ?? ''));
                            $related_image = boblift_product_image($related);
                            $related_fit = boblift_image_fit($related_image) === 'cutout' ? 'object-contain p-4 drop-shadow-[0_6px_8px_rgba(11,27,51,0.10)]' : 'object-contain';
                            $related_kicker = function_exists('boblift_home_product_card_kicker') ? boblift_home_product_card_kicker($related) : esc_html((string) ($related['category_title'] ?? ''));
                            $related_specs = function_exists('boblift_home_product_card_specs') ? boblift_home_product_card_specs($related) : [];
                            ?>
                            <li class="min-w-0">
                                <a class="group flex h-full flex-col overflow-hidden rounded border border-[#E5E7EB] bg-white transition hover:-translate-y-0.5 hover:border-blue-200" href="<?php echo esc_url(boblift_product_url($related)); ?>">
                                    <span class="relative block aspect-[4/3] overflow-hidden bg-[radial-gradient(circle_at_48%_34%,#ffffff_0%,#f8fafc_34%,#eef2f7_64%,#e2e8f0_100%)]"><img class="absolute inset-0 h-full w-full transition duration-500 group-hover:scale-[1.03] <?php echo esc_attr($related_fit); ?>" src="<?php echo esc_url($related_image); ?>" alt="<?php echo esc_attr((string) $related['title']); ?>" loading="lazy" decoding="async"></span>
                                    <span class="flex flex-1 flex-col p-3 sm:p-4">
                                        <?php if ($related_kicker !== '') : ?><span class="block truncate text-xs leading-4 text-slate-500"><?php echo wp_kses($related_kicker, ['bdi' => ['class' => true, 'dir' => true]]); ?></span><?php endif; ?>
                                        <span class="mt-1 block truncate text-xl font-black uppercase leading-tight tracking-[0.02em] text-[#0B3F7D] transition group-hover:text-blue-700 md:text-2xl"><?php echo esc_html($related_model !== '' ? $related_model : (string) $related['title']); ?></span>
                                        <?php if ($related_specs) : ?>
                                            <span class="mt-3 grid grid-cols-2 gap-2 border-t border-[#E5E7EB] pt-3">
                                                <?php foreach ($related_specs as $spec) : ?>
                                                    <span class="min-w-0"><span class="block truncate text-[11px] font-semibold leading-4 text-slate-400"><?php echo esc_html($spec['label']); ?></span><span class="mt-0.5 block truncate text-sm font-black text-[#071f42]" dir="ltr"><?php echo esc_html($spec['value']); ?></span></span>
                                                <?php endforeach; ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="mt-auto inline-flex items-center gap-1.5 pt-3 text-sm font-black text-blue-700"><?php echo esc_html((string) ($ui['viewDetailsLabel'] ?? 'View Details')); ?><?php echo boblift_icon('arrow-right', 'h-3.5 w-3.5 transition group-hover:translate-x-1 rtl:-scale-x-100'); ?></span>
                                    </span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
            <?php if ($related_term instanceof WP_Term) boblift_render_product_articles($related_term); ?>
            <section id="quote" class="boblift-product-quote-section relative left-1/2 w-screen -translate-x-1/2 rtl:left-auto rtl:right-1/2 rtl:translate-x-1/2 scroll-mt-24 overflow-hidden border-y border-[#DDE7F2] bg-[radial-gradient(circle_at_70%_28%,rgba(245,154,60,0.16),transparent_28%),linear-gradient(135deg,#ffffff_0%,#eaf2ff_100%)] py-12 text-[#071f42] shadow-card md:py-16">
                <div class="page-shell grid items-center gap-8 lg:grid-cols-[minmax(0,1fr)_auto] xl:gap-12">
                    <div class="boblift-product-quote-copy flex items-start gap-5 md:gap-6">
                        <div class="hidden h-20 w-20 shrink-0 place-items-center rounded border border-blue-100 bg-white/74 text-[#0B3F7D] shadow-card md:grid"><?php echo boblift_icon('mail', 'h-10 w-10'); ?></div>
                        <div class="min-w-0">
                            <h2 class="boblift-product-quote-title max-w-[1280px] text-[clamp(34px,3vw,58px)] font-black leading-[1.02] tracking-[-0.02em]"><?php echo esc_html($quote_title); ?></h2>
                            <p class="boblift-product-quote-text mt-4 max-w-[1120px] text-sm font-normal leading-7 text-slate-600 md:text-base md:leading-8"><?php echo esc_html($quote_description); ?></p>
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
