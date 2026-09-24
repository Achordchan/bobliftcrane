<?php
/** Steel product detail. */
get_header();

$product = boblift_find_product(get_post_field('post_name', get_queried_object_id()) ?: (string) get_query_var('product'));
if (!$product && have_posts()) {
    the_post();
    $product = boblift_product_from_post(get_post());
}
$ui = function_exists('boblift_site_ui') ? boblift_site_ui('productDetail') : [];
if (!$product) {
    status_header(404);
    echo '<section class="st-section"><div class="st-frame"><h1 class="st-h1">' . esc_html(boblift_steel_t((string) ($ui['notFoundTitle'] ?? 'Product not found'))) . '</h1></div></section>';
    get_footer();
    return;
}

$image_url = static fn(string $image): string => str_starts_with($image, 'http') ? $image : boblift_asset($image);
$main_image = boblift_product_image($product);
$title = (string) ($product['title'] ?? '');
$model = trim((string) ($product['model'] ?? ''));
$heading = boblift_steel_product_heading($product);
$has_model = $model !== '' && strcasecmp($model, $title) !== 0;
$category_title = (string) ($product['category_title'] ?? 'BOB-LIFT');
$category_slug = (string) ($product['category'] ?? '');
$contact = boblift_steel_contact();
$inquiry_url = (string) ($product['inquiry_url'] ?? $contact['quote_url']);
$catalog_pdf = trim((string) ($product['catalog_pdf_url'] ?? ''));
$archive_url = function_exists('boblift_product_archive_url') ? boblift_product_archive_url() : boblift_url('/products');
$category_url = $category_slug !== '' ? boblift_steel_category_url($category_slug) : $archive_url;

$specs = boblift_steel_product_specs($product);
$spec_labels = is_array($ui['specLabels'] ?? null) ? $ui['specLabels'] : [];
$technical = $specs;
if ($model !== '') {
    array_unshift($technical, ['label' => (string) ($spec_labels['model'] ?? 'Model'), 'value' => $model]);
}
$capacity = trim((string) ($product['capacity'] ?? ''));
if ($capacity !== '' && !array_filter($specs, static fn(array $spec): bool => stripos((string) $spec['label'], 'capacity') !== false || stripos((string) $spec['label'], 'lift') !== false)) {
    $technical[] = ['label' => (string) ($spec_labels['maxLift'] ?? 'Max lift'), 'value' => $capacity];
}
$boom_label = function_exists('boblift_boom_label') ? boblift_boom_label((string) ($product['boom_type'] ?? '')) : '';
if ($boom_label !== '') {
    $technical[] = ['label' => boblift_steel_t('Boom type'), 'value' => $boom_label];
}
$status = function_exists('boblift_normalize_product_status') ? boblift_normalize_product_status((string) ($product['status'] ?? 'customizable')) : 'customizable';
$status_label = function_exists('boblift_product_status_label') ? boblift_product_status_label($status) : ucfirst($status);
$technical[] = ['label' => boblift_steel_t('Availability'), 'value' => $status_label];
$applications = array_values(array_filter(array_map('strval', $product['applications'] ?? [])));
if ($applications !== []) {
    $archive_ui = function_exists('boblift_site_ui') ? boblift_site_ui('productArchive') : [];
    $application_labels = is_array($archive_ui['applicationOptions'] ?? null) ? $archive_ui['applicationOptions'] : [];
    $technical[] = ['label' => boblift_steel_t('Applications'), 'value' => implode(', ', array_map(static fn(string $slug): string => boblift_steel_t((string) ($application_labels[$slug] ?? ucwords(str_replace('-', ' ', $slug)))), $applications))];
}
$key_specs = array_slice($specs, 0, 4);
if ($key_specs === [] && $capacity !== '') {
    $key_specs[] = ['label' => (string) ($spec_labels['maxLift'] ?? 'Max lift'), 'value' => $capacity];
}

$features = array_values(array_filter($product['features'] ?? [], static fn($feature): bool => is_array($feature) && trim((string) ($feature['title'] ?? '')) !== ''));
$intro_html = trim((string) ($product['content'] ?? ''));
$short_description = trim((string) ($product['short_description'] ?? ''), " \t\n\r\0\x0B,");
if ($intro_html === '' && $short_description !== '') {
    $intro_html = '<p>' . esc_html($short_description) . '</p>';
}

$gallery = [];
foreach (($product['gallery'] ?? []) as $item) {
    $src = is_string($item) ? $item : (string) ($item['image'] ?? ($item['url'] ?? ''));
    if (trim($src) === '') {
        continue;
    }
    $gallery[] = ['image' => $image_url($src), 'label' => is_array($item) && !empty($item['label']) ? (string) $item['label'] : $title];
}
$detail_images = [];
foreach (($product['detail_images'] ?? []) as $item) {
    $src = is_string($item) ? $item : (string) ($item['image'] ?? ($item['url'] ?? ''));
    if (trim($src) === '') {
        continue;
    }
    $detail_images[] = ['image' => $image_url($src), 'label' => is_array($item) && !empty($item['label']) ? (string) $item['label'] : $title];
}
$stage_items = [];
$seen = [];
foreach (array_merge([['image' => $main_image, 'label' => (string) ($ui['mainProductViewLabel'] ?? 'Main product view')]], $gallery) as $item) {
    $key = strtolower(trim((string) $item['image']));
    if ($key === '' || isset($seen[$key])) {
        continue;
    }
    $seen[$key] = true;
    $stage_items[] = $item;
}
$hotspots = [];
foreach (($product['hotspots'] ?? []) as $hotspot) {
    if (!is_array($hotspot) || trim((string) ($hotspot['label'] ?? '')) === '') {
        continue;
    }
    $hotspots[] = ['label' => (string) $hotspot['label'], 'x' => max(0, min(100, (float) ($hotspot['x'] ?? 50))), 'y' => max(0, min(100, (float) ($hotspot['y'] ?? 50)))];
}

$faqs = [];
foreach ((is_array($ui['faqItems'] ?? null) ? $ui['faqItems'] : []) as $faq) {
    if (is_array($faq) && trim((string) ($faq['question'] ?? '')) !== '') {
        $faqs[] = ['question' => (string) $faq['question'], 'answer' => (string) ($faq['answer'] ?? '')];
    }
}

$related = [];
foreach (boblift_all_products() as $candidate) {
    if (($candidate['slug'] ?? '') === ($product['slug'] ?? '')) {
        continue;
    }
    if ($category_slug !== '' && in_array($category_slug, boblift_steel_product_category_slugs($candidate), true)) {
        $related[] = $candidate;
    }
    if (count($related) >= 4) {
        break;
    }
}

$anchors = [['id' => 'overview', 'label' => (string) ($ui['overviewLabel'] ?? 'Overview')]];
$anchors[] = ['id' => 'technical', 'label' => (string) ($ui['technicalLabel'] ?? 'Technical specifications')];
if ($features !== []) {
    $anchors[] = ['id' => 'features', 'label' => boblift_steel_t('Features')];
}
if ($detail_images !== []) {
    $anchors[] = ['id' => 'details', 'label' => (string) ($ui['detailsLabel'] ?? 'Product details')];
}
if (count($gallery) > 0) {
    $anchors[] = ['id' => 'photos', 'label' => (string) ($ui['photosLabel'] ?? 'Product photos')];
}
if ($faqs !== []) {
    $anchors[] = ['id' => 'faq', 'label' => (string) ($ui['faqLabel'] ?? 'FAQ')];
}
$anchors[] = ['id' => 'inquiry', 'label' => (string) ($ui['quoteAnchorLabel'] ?? 'Request quote')];
$request_label = boblift_steel_t((string) ($ui['requestQuoteLabel'] ?? 'Request quote'));
$section_index = 0;
?>

<article class="st-product">
    <section class="st-product__hero" id="top">
        <div class="st-frame">
            <nav class="st-archive__crumbs" aria-label="Breadcrumb">
                <a href="<?php echo esc_url(boblift_url('/')); ?>"><?php echo esc_html(boblift_steel_t('Home')); ?></a><span>/</span>
                <a href="<?php echo esc_url($archive_url); ?>"><?php echo esc_html(boblift_steel_t('Products')); ?></a><span>/</span>
                <a href="<?php echo esc_url($category_url); ?>"><?php echo esc_html($category_title); ?></a>
            </nav>
            <div class="st-product__grid">
                <div class="st-product__copy">
                    <p class="st-eyebrow"><span class="st-eyebrow__index"><?php echo esc_html($category_title); ?></span><span class="st-tag st-tag--<?php echo esc_attr($status); ?>"><?php echo esc_html($status_label); ?></span></p>
                    <div>
                        <h1 class="<?php echo $has_model ? 'st-product__model' : 'st-h1'; ?>"><?php echo esc_html($heading); ?></h1>
                        <?php if ($has_model) : ?><p class="st-product__name"><?php echo esc_html($title); ?></p><?php endif; ?>
                    </div>
                    <?php if ($short_description !== '') : ?><p class="st-product__intro"><?php echo esc_html($short_description); ?></p><?php endif; ?>
                    <div class="st-product__actions">
                        <?php echo boblift_steel_button($request_label, $inquiry_url, 'primary'); ?>
                        <?php if ($catalog_pdf !== '') : ?>
                            <a class="st-textlink" href="<?php echo esc_url($catalog_pdf); ?>" target="_blank" rel="noreferrer"><span><?php echo esc_html(boblift_steel_t((string) ($ui['heroPdfLabel'] ?? 'PDF catalog'))); ?></span><?php echo boblift_steel_icon('download'); ?></a>
                        <?php else : ?>
                            <a class="st-textlink" href="<?php echo esc_url($inquiry_url); ?>"><span><?php echo esc_html(boblift_steel_t((string) ($ui['requestPdfLabel'] ?? 'Request PDF by email'))); ?></span><?php echo boblift_steel_icon('file'); ?></a>
                        <?php endif; ?>
                        <a class="st-textlink" href="<?php echo esc_url($contact['whatsapp_href']); ?>" target="_blank" rel="noreferrer"><span>WhatsApp</span><?php echo boblift_steel_icon('arrow-up-right'); ?></a>
                    </div>
                    <?php if ($key_specs !== []) : ?>
                        <dl class="st-rows st-product__keyspecs">
                            <?php foreach ($key_specs as $spec) : ?>
                                <div class="st-rows__row"><dt><?php echo esc_html((string) $spec['label']); ?></dt><dd><?php echo esc_html((string) $spec['value']); ?></dd></div>
                            <?php endforeach; ?>
                        </dl>
                    <?php endif; ?>
                </div>

                <div class="st-product__media">
                    <?php if ($has_model) : ?><span class="st-product__ghost" aria-hidden="true"><?php echo esc_html($model); ?></span><?php endif; ?>
                    <div class="st-stage" data-stage>
                        <span class="st-stage__meta" data-stage-meta><?php echo esc_html($stage_items[0]['label'] ?? ''); ?></span>
                        <img class="st-stage__img" src="<?php echo esc_url($main_image); ?>" alt="<?php echo esc_attr($title); ?>" fetchpriority="high" decoding="async" data-stage-image>
                        <?php if ($hotspots !== []) : ?>
                            <div data-hotspots>
                                <?php foreach ($hotspots as $index => $hotspot) : ?>
                                    <button class="st-hotspot <?php echo $hotspot['x'] > 60 ? 'st-hotspot--left' : ''; ?>" type="button" style="left:<?php echo esc_attr((string) $hotspot['x']); ?>%;top:<?php echo esc_attr((string) $hotspot['y']); ?>%" data-hotspot="<?php echo esc_attr((string) $index); ?>" aria-label="<?php echo esc_attr($hotspot['label']); ?>">
                                        <?php echo esc_html((string) ($index + 1)); ?>
                                        <span class="st-hotspot__label"><?php echo esc_html($hotspot['label']); ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($hotspots !== []) : ?>
                        <ul class="st-legend" role="list">
                            <?php foreach ($hotspots as $index => $hotspot) : ?>
                                <li><button type="button" data-legend="<?php echo esc_attr((string) $index); ?>"><b><?php echo esc_html(boblift_steel_index($index + 1)); ?></b><?php echo esc_html($hotspot['label']); ?></button></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <?php if (count($stage_items) > 1) : ?>
                        <div class="st-thumbs" role="group" aria-label="<?php echo esc_attr(boblift_steel_t((string) ($ui['photoGalleryTitle'] ?? 'Product photos'))); ?>">
                            <?php foreach ($stage_items as $index => $item) : ?>
                                <button type="button" class="<?php echo $index === 0 ? 'is-active' : ''; ?>" data-thumb="<?php echo esc_url($item['image']); ?>" data-label="<?php echo esc_attr($item['label']); ?>" aria-label="<?php echo esc_attr($item['label']); ?>"><img src="<?php echo esc_url($item['image']); ?>" alt="" loading="lazy" decoding="async"></button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <nav class="st-subnav" data-subnav aria-label="<?php echo esc_attr(boblift_steel_t((string) ($ui['sectionNavLabel'] ?? 'Product detail sections'))); ?>">
        <div class="st-frame st-subnav__inner">
            <?php foreach ($anchors as $anchor) : ?>
                <a href="#<?php echo esc_attr($anchor['id']); ?>"><?php echo esc_html(boblift_steel_t($anchor['label'])); ?></a>
            <?php endforeach; ?>
            <span class="st-subnav__cta"><a class="st-textlink" href="<?php echo esc_url($inquiry_url); ?>"><span><?php echo esc_html($request_label); ?></span><?php echo boblift_steel_icon('arrow-right'); ?></a></span>
        </div>
    </nav>

    <section class="st-product__section" id="overview">
        <div class="st-frame">
            <?php $section_index++; boblift_steel_section_head(['index' => boblift_steel_index($section_index), 'eyebrow' => boblift_steel_t((string) ($ui['productIntroLabel'] ?? 'Product overview')), 'title' => $title, 'tone' => 'st-section__head--stack']); ?>
            <div class="st-lead st-reveal" style="max-width:72ch">
                <?php echo $intro_html !== '' ? wp_kses_post($intro_html) : '<p>' . esc_html(boblift_steel_t((string) ($ui['overviewTitle'] ?? 'Built for reliable lifting work.'))) . '</p>'; ?>
            </div>
        </div>
    </section>

    <section class="st-product__section" id="technical">
        <div class="st-frame">
            <?php $section_index++; boblift_steel_section_head(['index' => boblift_steel_index($section_index), 'eyebrow' => boblift_steel_t((string) ($ui['cinemaSpecsLabel'] ?? 'Engineering data')), 'title' => boblift_steel_t((string) ($ui['technicalLabel'] ?? 'Technical specifications')), 'tone' => 'st-section__head--stack']); ?>
            <dl class="st-specs st-reveal">
                <?php foreach ($technical as $spec) : ?>
                    <div class="st-specs__row"><dt><?php echo esc_html((string) $spec['label']); ?></dt><dd><?php echo esc_html((string) $spec['value']); ?></dd></div>
                <?php endforeach; ?>
            </dl>
        </div>
    </section>

    <?php if ($features !== []) : ?>
    <section class="st-product__section" id="features">
        <div class="st-frame">
            <?php $section_index++; boblift_steel_section_head(['index' => boblift_steel_index($section_index), 'eyebrow' => boblift_steel_t('Why this model'), 'title' => boblift_steel_t('Features'), 'tone' => 'st-section__head--stack']); ?>
            <ul class="st-features st-reveal" role="list">
                <?php foreach ($features as $index => $feature) : ?>
                    <li><span class="st-features__num"><?php echo esc_html(boblift_steel_index($index + 1)); ?></span><h3><?php echo esc_html((string) $feature['title']); ?></h3><?php if (!empty($feature['text'])) : ?><p><?php echo esc_html((string) $feature['text']); ?></p><?php endif; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($detail_images !== []) : ?>
    <section class="st-product__section" id="details">
        <div class="st-frame">
            <?php $section_index++; boblift_steel_section_head(['index' => boblift_steel_index($section_index), 'eyebrow' => boblift_steel_t((string) ($ui['cinemaDetailsLabel'] ?? 'Detail scroll')), 'title' => boblift_steel_t((string) ($ui['detailImagesTitle'] ?? ($ui['detailsLabel'] ?? 'Product details'))), 'tone' => 'st-section__head--stack']); ?>
            <div class="st-details st-reveal">
                <?php foreach ($detail_images as $item) : ?>
                    <img src="<?php echo esc_url($item['image']); ?>" alt="<?php echo esc_attr($item['label']); ?>" loading="lazy" decoding="async">
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if (count($gallery) > 0) : ?>
    <section class="st-product__section" id="photos">
        <div class="st-frame">
            <?php $section_index++; boblift_steel_section_head(['index' => boblift_steel_index($section_index), 'eyebrow' => boblift_steel_t((string) ($ui['cinemaGalleryLabel'] ?? 'Product stage')), 'title' => boblift_steel_t((string) ($ui['photoGalleryTitle'] ?? ($ui['photosLabel'] ?? 'Product photos'))), 'tone' => 'st-section__head--stack']); ?>
            <div class="st-gallery st-reveal">
                <?php foreach ($gallery as $index => $item) : ?>
                    <figure><button type="button" data-lightbox-open="<?php echo esc_url($item['image']); ?>" data-label="<?php echo esc_attr($item['label']); ?>" aria-label="<?php echo esc_attr($item['label']); ?>"><img src="<?php echo esc_url($item['image']); ?>" alt="<?php echo esc_attr($item['label']); ?>" loading="lazy" decoding="async"><figcaption><?php echo esc_html(boblift_steel_index($index + 1)); ?></figcaption></button></figure>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <dialog class="st-lightbox" data-lightbox>
        <div class="st-lightbox__inner">
            <img src="" alt="">
            <button class="st-lightbox__btn st-lightbox__btn--close" type="button" data-lightbox-close aria-label="<?php echo esc_attr(boblift_steel_t('Close')); ?>"><?php echo boblift_steel_icon('close'); ?></button>
            <?php if (count($gallery) > 1) : ?>
                <button class="st-lightbox__btn st-lightbox__btn--prev" type="button" data-lightbox-prev aria-label="<?php echo esc_attr(boblift_steel_t('Previous')); ?>"><?php echo boblift_steel_icon('arrow-left'); ?></button>
                <button class="st-lightbox__btn st-lightbox__btn--next" type="button" data-lightbox-next aria-label="<?php echo esc_attr(boblift_steel_t('Next')); ?>"><?php echo boblift_steel_icon('arrow-right'); ?></button>
            <?php endif; ?>
            <p class="st-lightbox__caption" data-lightbox-caption></p>
        </div>
    </dialog>
    <?php endif; ?>

    <?php if ($faqs !== []) : ?>
    <section class="st-product__section" id="faq">
        <div class="st-frame">
            <?php $section_index++; boblift_steel_section_head(['index' => boblift_steel_index($section_index), 'eyebrow' => boblift_steel_t('Before you order'), 'title' => boblift_steel_t((string) ($ui['faqLabel'] ?? 'FAQ')), 'tone' => 'st-section__head--stack']); ?>
            <div class="st-faq st-reveal">
                <?php foreach ($faqs as $faq) : ?>
                    <details><summary><?php echo esc_html($faq['question']); ?><?php echo boblift_steel_icon('plus'); ?></summary><p><?php echo esc_html($faq['answer']); ?></p></details>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($related !== []) : ?>
    <section class="st-product__section st-related" style="padding-top:var(--section)">
        <div class="st-frame">
            <?php boblift_steel_section_head(['eyebrow' => $category_title, 'title' => boblift_steel_t('Related models'), 'link_label' => boblift_steel_t('All') . ' ' . $category_title, 'link_href' => $category_url]); ?>
            <div class="st-grid st-grid--products">
                <?php foreach ($related as $item_index => $item) : boblift_steel_product_card($item, ['class' => 'st-reveal', 'index' => $item_index + 1]); endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="st-product__section st-inquiry" id="inquiry" style="padding-top:var(--section)">
        <div class="st-frame st-inquiry__grid">
            <div class="st-inquiry__copy">
                <p class="st-eyebrow st-eyebrow--light"><?php echo esc_html($request_label); ?></p>
                <h2 class="st-h2"><?php echo esc_html(boblift_steel_t('Need this model matched to your site and lifting task?')); ?></h2>
                <p><?php echo esc_html(boblift_steel_t('Send your working radius, load requirement, site access, destination country and delivery schedule. Our engineers reply with a practical configuration and quotation.')); ?></p>
                <div class="st-inquiry__contact">
                    <a href="mailto:<?php echo esc_attr($contact['email']); ?>"><?php echo esc_html($contact['email']); ?></a>
                    <?php if ($contact['phone'] !== '') : ?><a href="<?php echo esc_url($contact['phone_href']); ?>"><?php echo esc_html($contact['phone']); ?></a><?php endif; ?>
                    <?php if ($contact['address'] !== '') : ?><span><?php echo esc_html($contact['address']); ?></span><?php endif; ?>
                </div>
            </div>
            <?php boblift_steel_inquiry_form(['product' => $title, 'product_slug' => (string) ($product['slug'] ?? ''), 'submit_label' => $request_label]); ?>
        </div>
    </section>
</article>

<?php get_footer(); ?>
