<?php
/** Steel front page — v3 "Jobsite". */
get_header();

$options = boblift_options();
$home = is_array($options['home'] ?? null) ? $options['home'] : [];
$home_ui = function_exists('boblift_site_ui') ? boblift_site_ui('homePage') : [];
$trust = is_array($home['trust_bar'] ?? null) ? $home['trust_bar'] : [];
$about = is_array($home['about'] ?? null) ? $home['about'] : [];
$news = is_array($home['news'] ?? null) ? $home['news'] : [];
$top_bar = is_array($options['top_bar'] ?? null) ? $options['top_bar'] : [];
$about_page = function_exists('boblift_about_page_options') ? boblift_about_page_options() : [];
$visits = is_array($about_page['customer_visits'] ?? null) ? $about_page['customer_visits'] : [];
$strength = is_array($about_page['strength'] ?? null) ? $about_page['strength'] : [];
$contact = boblift_steel_contact();
$product_menu = boblift_steel_product_menu();
$product_total = count(boblift_all_products());
$catalog_url = function_exists('boblift_product_archive_url') ? boblift_product_archive_url() : boblift_url('/products');
$articles = function_exists('boblift_articles') ? array_slice(boblift_articles(), 0, 4) : [];

/* Product programme: merge families the parent lists twice (group + parent category). */
$programme = function_exists('boblift_home_product_section_config') ? boblift_home_product_section_config() : [];
$catalog_raw = function_exists('boblift_home_product_catalog') && $programme ? boblift_home_product_catalog($programme) : [];
$merged = [];
foreach ($catalog_raw as $family) {
    $key = strtolower(trim((string) ($family['name'] ?? '')));
    if ($key === '') {
        continue;
    }
    if (!isset($merged[$key])) {
        $merged[$key] = $family;
        continue;
    }
    $seen_slugs = array_flip(array_map(static fn(array $p): string => (string) ($p['slug'] ?? ''), $merged[$key]['products']));
    foreach ($family['products'] as $product) {
        if (!isset($seen_slugs[(string) ($product['slug'] ?? '')])) {
            $merged[$key]['products'][] = $product;
        }
    }
}
$catalog = array_values($merged);

/* Hero family panels: real photography per family, admin-overridable via filter. */
$panel_photos = apply_filters('boblift_steel_hero_panel_photos', [
    'images/oldsite/news/enter-the-bob-lift-factory-with-professional-manufacturing.jpg',
    'images/oldsite/cases/bob-lift-provides-reliable-support-for-water-operations.jpg',
    'images/oldsite/cases/leg-and-chassis-technology-of-the-bob-lift-aerial-work-platform.jpg',
    'images/oldsite/cases/strong-lifting-combined-with-efficient-grippers.jpg',
]);
$panels = [];
foreach (array_slice($product_menu, 0, 4) as $index => $group) {
    $panels[] = [
        'title' => (string) $group['title'],
        'href' => (string) $group['href'],
        'count' => (int) $group['count'],
        'image' => boblift_asset((string) ($panel_photos[$index] ?? $panel_photos[0])),
    ];
}

$hero = apply_filters('boblift_steel_home_hero', [
    'kicker' => boblift_steel_t('Truck-mounted · Marine · Aerial · Compact'),
    'line_1' => boblift_steel_t('Heavy lifting.'),
    'line_2' => boblift_steel_t('Built for export'),
    'text' => boblift_steel_t('Knuckle and telescopic boom cranes, marine cranes, aerial platforms and compact machinery, engineered and built in our own Xuzhou plant and shipped with export-ready documentation.'),
    'video' => boblift_asset('videos/about/about-aerial-mid.mp4'),
    'poster' => boblift_asset('images/about/about-aerial-mid-start.webp'),
    'primary_label' => boblift_steel_t('Explore the range'),
    'primary_href' => $catalog_url,
    'secondary_label' => boblift_steel_t('Request a quote'),
    'secondary_href' => $contact['quote_url'],
    'data' => array_values(array_filter([
        $strength['text'] ?? '' ? ['label' => boblift_steel_t('Plant'), 'value' => '40,000 m²'] : null,
        ['label' => boblift_steel_t('Base'), 'value' => boblift_steel_t('Xuzhou · CN')],
        !empty($trust['marks']) ? ['label' => boblift_steel_t('Certified'), 'value' => 'ISO 9001 · CE · EAC'] : null,
        ['label' => boblift_steel_t('Models'), 'value' => (string) $product_total],
    ])),
]);

$visit_items = array_values(array_filter($visits['items'] ?? [], static fn($item): bool => is_array($item) && boblift_steel_image($item['image'] ?? '', '') !== ''));
$ticker_items = [];
foreach (($trust['marks'] ?? []) as $mark) {
    if (is_array($mark) && boblift_steel_image($mark['logo'] ?? '', '') !== '') {
        $ticker_items[] = ['logo' => boblift_steel_image($mark['logo'], ''), 'text' => boblift_steel_t((string) ($mark['name'] ?? ''))];
    }
}
foreach ([(string) ($top_bar['text'] ?? ''), (string) ($trust['description'] ?? ''), $contact['address'] !== '' ? 'Manufacturing base · ' . $contact['address'] : ''] as $phrase) {
    $phrase = trim($phrase);
    if ($phrase !== '') {
        $ticker_items[] = ['logo' => '', 'text' => boblift_steel_t($phrase)];
    }
}
$section = 0;
?>

<section class="st-hero" aria-label="<?php echo esc_attr(boblift_steel_t((string) ($home_ui['heroAriaLabel'] ?? 'Featured lifting solutions'))); ?>">
    <div class="st-hero__main">
        <div class="st-hero__media st-blueprint" data-parallax="0.14">
            <?php if (!empty($hero['video'])) : ?>
                <video autoplay muted loop playsinline preload="metadata" poster="<?php echo esc_url($hero['poster']); ?>" aria-hidden="true">
                    <source src="<?php echo esc_url($hero['video']); ?>" type="video/mp4">
                </video>
            <?php else : ?>
                <img src="<?php echo esc_url($hero['poster']); ?>" alt="" fetchpriority="high" decoding="async">
            <?php endif; ?>
        </div>
        <div class="st-hero__content">
            <p class="st-eyebrow st-eyebrow--light st-reveal"><?php echo esc_html($hero['kicker']); ?></p>
            <h1 class="st-display st-reveal st-reveal--split" data-split><?php echo esc_html($hero['line_1']); ?><br><?php echo esc_html($hero['line_2']); ?><span class="st-dot">.</span></h1>
            <p class="st-hero__text st-reveal" style="--d:.35s"><?php echo esc_html($hero['text']); ?></p>
            <div class="st-hero__actions st-reveal" style="--d:.45s">
                <?php echo boblift_steel_button($hero['primary_label'], $hero['primary_href'], 'primary'); ?>
                <?php echo boblift_steel_button($hero['secondary_label'], $hero['secondary_href'], 'ghost'); ?>
            </div>
        </div>
        <div class="st-hero__data">
            <?php foreach ($hero['data'] as $item) : ?>
                <span><?php echo esc_html($item['label']); ?> <b><?php echo esc_html($item['value']); ?></b></span>
            <?php endforeach; ?>
            <span class="st-hero__scroll"><?php echo esc_html(boblift_steel_t('Scroll')); ?><i></i></span>
        </div>
    </div>

    <?php if ($panels !== []) : ?>
        <nav class="st-hero__panels" aria-label="<?php echo esc_attr(boblift_steel_t('Product families')); ?>" data-hero-panels>
            <?php foreach ($panels as $index => $panel) : ?>
                <a class="st-panel-hero <?php echo $index === 0 ? 'is-active' : ''; ?>" href="<?php echo esc_url($panel['href']); ?>" data-hero-panel>
                    <span class="st-panel-hero__media"><img src="<?php echo esc_url($panel['image']); ?>" alt="" loading="<?php echo $index < 2 ? 'eager' : 'lazy'; ?>" decoding="async"></span>
                    <span class="st-panel-hero__num"><?php echo esc_html(boblift_steel_index($index + 1)); ?></span>
                    <span class="st-panel-hero__body">
                        <span class="st-panel-hero__name"><?php echo esc_html($panel['title']); ?></span>
                        <span class="st-panel-hero__count"><?php echo esc_html((string) $panel['count']); ?> <?php echo esc_html(boblift_steel_t('models')); ?></span>
                        <span class="st-panel-hero__more"><?php echo esc_html(boblift_steel_t('View range')); ?><?php echo boblift_steel_icon('arrow-right'); ?></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>
</section>

<?php if ($ticker_items !== []) : ?>
<div class="st-ticker" aria-label="<?php echo esc_attr(boblift_steel_t((string) ($home_ui['qualityCertificationsLabel'] ?? 'Quality certifications'))); ?>">
    <div class="st-ticker__track">
        <?php for ($loop = 0; $loop < 2; $loop++) : ?>
            <div class="st-ticker__group" <?php echo $loop === 1 ? 'aria-hidden="true"' : ''; ?>>
                <?php foreach ($ticker_items as $item) : ?>
                    <span class="st-ticker__item"><?php if ($item['logo'] !== '') : ?><img src="<?php echo esc_url($item['logo']); ?>" alt="" loading="lazy" decoding="async"><?php endif; ?><span><?php echo esc_html($item['text']); ?></span></span>
                <?php endforeach; ?>
            </div>
        <?php endfor; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($catalog !== []) : $section++; ?>
<section class="st-section st-programme" id="products" data-tabs data-hover-tabs>
    <div class="st-frame">
        <?php boblift_steel_section_head([
            'index' => boblift_steel_index($section),
            'eyebrow' => boblift_steel_t((string) ($programme['label'] ?? 'Main products')),
            'title' => boblift_steel_t((string) ($programme['findModelTitle'] ?? 'Find your model')),
            'text' => boblift_steel_t((string) ($programme['title'] ?? '')),
            'link_label' => boblift_steel_t('Full catalog') . ' · ' . $product_total,
            'link_href' => $catalog_url,
        ]); ?>
        <div class="st-bracket st-programme__frame"><span class="st-bracket__c"></span>
            <div class="st-toc st-reveal" role="tablist" aria-label="<?php echo esc_attr(boblift_steel_t('Product families')); ?>">
                <?php foreach ($catalog as $index => $family) : ?>
                    <button class="st-toc__item <?php echo $index === 0 ? 'is-active' : ''; ?>" type="button" role="tab" id="st-tab-<?php echo esc_attr((string) $index); ?>" aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-controls="st-panel-<?php echo esc_attr((string) $index); ?>" data-tab="<?php echo esc_attr((string) $index); ?>">
                        <span class="st-toc__index"><?php echo esc_html(boblift_steel_index($index + 1)); ?></span>
                        <span class="st-toc__name"><?php echo esc_html((string) $family['name']); ?></span>
                        <span class="st-toc__count"><?php echo esc_html((string) count($family['products'])); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
            <?php foreach ($catalog as $index => $family) : ?>
                <div class="st-tabs__panel <?php echo $index === 0 ? 'is-active' : ''; ?>" role="tabpanel" id="st-panel-<?php echo esc_attr((string) $index); ?>" aria-labelledby="st-tab-<?php echo esc_attr((string) $index); ?>" data-tab-panel="<?php echo esc_attr((string) $index); ?>"<?php echo $index === 0 ? '' : ' hidden'; ?>>
                    <div class="st-grid st-grid--products">
                        <?php foreach (array_slice($family['products'], 0, 4) as $product_index => $product) : boblift_steel_product_card($product, ['eager' => $index === 0, 'class' => 'st-reveal', 'index' => $product_index + 1]); endforeach; ?>
                    </div>
                    <div class="st-tabs__more">
                        <a class="st-textlink" href="<?php echo esc_url((string) $family['href']); ?>"><span><?php echo esc_html(boblift_steel_t('All')); ?> <?php echo esc_html((string) $family['name']); ?> · <?php echo esc_html((string) count($family['products'])); ?></span><?php echo boblift_steel_icon('arrow-right'); ?></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($about['stats'])) : ?>
<section class="st-section st-section--tight" aria-label="<?php echo esc_attr(boblift_steel_t('Key figures')); ?>">
    <div class="st-frame">
        <dl class="st-stats st-reveal">
            <?php foreach ($about['stats'] as $stat) : if (!is_array($stat)) { continue; } [$number, $suffix] = boblift_steel_split_stat((string) ($stat['value'] ?? '')); ?>
                <div class="st-stats__item">
                    <dd><span class="st-stats__number" data-count="<?php echo esc_attr(preg_replace('/[^0-9.]/', '', $number)); ?>"><?php echo esc_html($number); ?></span><span class="st-stats__suffix"><?php echo esc_html($suffix); ?></span></dd>
                    <dt><?php echo esc_html(boblift_steel_t((string) ($stat['label'] ?? ''))); ?></dt>
                </div>
            <?php endforeach; ?>
        </dl>
    </div>
</section>
<?php endif; ?>

<?php $section++; ?>
<section class="st-factory" id="about">
    <div class="st-factory__bg st-blueprint" data-parallax="0.12">
        <img src="<?php echo esc_url(boblift_asset('images/oldsite/news/enter-the-bob-lift-factory-with-professional-manufacturing.jpg')); ?>" alt="" loading="lazy" decoding="async">
    </div>
    <div class="st-frame st-factory__inner">
        <div class="st-factory__copy">
            <?php boblift_steel_section_head([
                'index' => boblift_steel_index($section),
                'eyebrow' => boblift_steel_t((string) ($about['label'] ?? 'About BOB-LIFT')),
                'title' => boblift_steel_t((string) ($about['title'] ?? 'Engineering excellence. Powered by people.')),
                'tone' => 'st-section__head--stack',
            ]); ?>
            <p class="st-factory__text st-reveal"><?php echo esc_html(boblift_steel_t((string) ($about['text'] ?? ''))); ?></p>
            <?php if (!empty($strength['text'])) : ?><p class="st-factory__text st-factory__text--muted st-reveal" style="--d:.1s"><?php echo esc_html(boblift_steel_t((string) $strength['text'])); ?></p><?php endif; ?>
            <div class="st-reveal" style="--d:.2s"><?php echo boblift_steel_button(boblift_steel_t((string) ($about['cta_label'] ?? 'More about us')), boblift_url((string) ($about['cta_href'] ?? '/about')), 'light'); ?></div>
        </div>
        <?php if (!empty($about['timeline'])) : ?>
            <ol class="st-timeline st-reveal" role="list" style="--d:.15s">
                <?php foreach ($about['timeline'] as $entry) : [$year, $label] = boblift_steel_split_timeline((string) $entry); ?>
                    <li><span class="st-timeline__year"><?php echo esc_html($year); ?></span><span class="st-timeline__label"><?php echo esc_html(boblift_steel_t($label)); ?></span></li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </div>
</section>

<?php if ($visit_items !== []) : $section++; ?>
<section class="st-section st-section--alt st-visits">
    <div class="st-frame">
        <?php boblift_steel_section_head([
            'index' => boblift_steel_index($section),
            'eyebrow' => boblift_steel_t((string) ($visits['label'] ?? 'Global customer visits')),
            'title' => boblift_steel_t((string) ($visits['home_title'] ?? ($visits['title'] ?? 'Customer visit gallery'))),
            'text' => boblift_steel_t((string) ($visits['text'] ?? '')),
            'link_label' => boblift_steel_t((string) ($visits['home_cta_label'] ?? 'View more photos')),
            'link_href' => boblift_url('/global-customer-visits.html'),
        ]); ?>
        <div class="st-tiles">
            <?php foreach (array_slice($visit_items, 0, 3) as $index => $item) : ?>
                <article class="st-tile st-reveal" style="--d:<?php echo esc_attr((string) ($index * 0.1)); ?>s">
                    <div class="st-tile__media"><img src="<?php echo esc_url(boblift_steel_image($item['image'] ?? '', '')); ?>" alt="<?php echo esc_attr(boblift_steel_t((string) ($item['title'] ?? ''))); ?>" loading="lazy" decoding="async"></div>
                    <div class="st-tile__body">
                        <span class="st-tag st-tile__tag"><?php echo esc_html(boblift_steel_t((string) ($item['label'] ?? ''))); ?></span>
                        <h3><?php echo esc_html(boblift_steel_t((string) ($item['title'] ?? ''))); ?></h3>
                        <p><?php echo esc_html(boblift_steel_t((string) ($item['text'] ?? ''))); ?></p>
                        <div class="st-tile__actions">
                            <a class="st-block" href="<?php echo esc_url(boblift_url('/global-customer-visits.html')); ?>"><span><?php echo esc_html(boblift_steel_t('See the visit')); ?></span><?php echo boblift_steel_icon('arrow-right'); ?></a>
                            <?php if ($index === 0) : ?><a class="st-block st-block--signal" href="<?php echo esc_url($contact['contact_url']); ?>"><span><?php echo esc_html(boblift_steel_t((string) ($visits['cta_label'] ?? 'Plan a visit'))); ?></span><?php echo boblift_steel_icon('arrow-right'); ?></a><?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($articles !== []) : $section++; ?>
<section class="st-section st-news" id="news">
    <div class="st-frame">
        <?php boblift_steel_section_head([
            'index' => boblift_steel_index($section),
            'eyebrow' => boblift_steel_t((string) ($news['label'] ?? 'News center')),
            'title' => boblift_steel_t((string) ($news['title'] ?? 'Latest from BOB-LIFT')),
            'link_label' => boblift_steel_t((string) ($news['cta_label'] ?? 'View all news')),
            'link_href' => boblift_url((string) ($news['cta_href'] ?? '/news')),
        ]); ?>
        <ol class="st-news__list" role="list">
            <?php foreach ($articles as $index => $item) : ?>
                <li class="st-news__item st-reveal" style="--d:<?php echo esc_attr((string) ($index * 0.07)); ?>s">
                    <a class="st-news__link" href="<?php echo esc_url((string) $item['url']); ?>">
                        <span class="st-news__meta"><span class="st-news__index">/<?php echo esc_html(boblift_steel_index($index + 1)); ?></span><time datetime="<?php echo esc_attr((string) $item['date']); ?>"><?php echo esc_html((string) $item['date']); ?></time><span class="st-news__category"><?php echo esc_html((string) $item['category']); ?></span></span>
                        <span class="st-news__title"><?php echo esc_html((string) $item['title']); ?></span>
                        <span class="st-news__excerpt"><?php echo esc_html((string) $item['excerpt']); ?></span>
                        <span class="st-news__thumb"><img src="<?php echo esc_url(function_exists('boblift_item_image') ? boblift_item_image($item) : (string) $item['image']); ?>" alt="" loading="lazy" decoding="async"></span>
                        <span class="st-news__arrow"><?php echo boblift_steel_icon('arrow-up-right'); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>
<?php endif; ?>

<?php get_footer(); ?>
