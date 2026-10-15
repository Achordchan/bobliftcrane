<?php
if (!defined('ABSPATH')) {
    exit;
}

function boblift_home_product_section_config(): array
{
    $home = boblift_options()['home'] ?? [];
    $section = $home['product_section'] ?? [];
    $language = function_exists('boblift_current_language_for_data') ? boblift_current_language_for_data() : '';
    $view_all_defaults = ['ja' => '全{count}機種を見る', 'zh' => '查看全部 {count} 款'];
    $product_ui = function_exists('boblift_site_ui') ? boblift_site_ui('productDetail') : [];

    return [
        'label' => $section['label'] ?? 'Main Products',
        'title' => $section['title'] ?? 'BOB-LIFT Crane Solutions',
        'maxCategoryProducts' => (int) ($section['max_category_products'] ?? 4),
        'showCategoryScrollButtons' => (bool) ($section['show_category_scroll_buttons'] ?? true),
        'viewAllLabel' => (string) (($section['view_all_label'] ?? '') ?: ($view_all_defaults[$language] ?? 'View All {count} Models')),
        'requestQuoteLabel' => (string) ($product_ui['requestQuoteLabel'] ?? 'Request Quote'),
        'detailsShortLabel' => $section['details_short_label'] ?? 'Details',
        'viewDetailsLabel' => $section['view_details_label'] ?? 'View Details',
        'previousCategoriesLabel' => $section['previous_categories_label'] ?? 'Previous product categories',
        'nextCategoriesLabel' => $section['next_categories_label'] ?? 'Next product categories',
        'showProductSpecs' => (bool) ($section['show_product_specs'] ?? true),
        'selectedProductSlugs' => array_values(array_filter(array_map('strval', $section['selected_product_slugs'] ?? []))),
    ];
}

function boblift_home_product_catalog(array $section): array
{
    $categories = boblift_categories();
    usort($categories, static fn(array $a, array $b): int => (int) ($a['sort'] ?? 100) <=> (int) ($b['sort'] ?? 100));
    $category_map = [];
    foreach ($categories as $category) {
        $slug = (string) ($category['slug'] ?? '');
        if ($slug !== '') {
            $category_map[$slug] = $category;
        }
    }

    $products = boblift_all_products();
    $seed_by_slug = [];
    foreach (boblift_products_seed() as $seed_product) {
        $seed_by_slug[$seed_product['slug'] ?? ''] = $seed_product;
    }

    foreach ($products as &$product) {
        $seed = $seed_by_slug[$product['slug'] ?? ''] ?? [];
        $product['featured'] = (bool) ($seed['featured'] ?? false);
        $product['popularity'] = (int) ($seed['popularity'] ?? 50);
        $product['home_sort'] = (int) ($seed['sort_order'] ?? 100);
    }
    unset($product);

    $category_totals = [];
    foreach ($products as $product) {
        $slug = (string) ($product['category'] ?? '');
        if ($slug !== '') {
            $category_totals[$slug] = ($category_totals[$slug] ?? 0) + 1;
        }
    }

    $selected_order = array_flip($section['selectedProductSlugs']);
    if ($selected_order !== []) {
        $products = array_values(array_filter($products, static fn(array $product): bool => isset($selected_order[(string) ($product['slug'] ?? '')])));
        usort($products, static fn(array $a, array $b): int => ($selected_order[(string) ($a['slug'] ?? '')] ?? 999) <=> ($selected_order[(string) ($b['slug'] ?? '')] ?? 999));
    }

    $products_by_category = [];
    foreach ($products as $product) {
        $slug = (string) ($product['category'] ?? '');
        if ($slug !== '') {
            $products_by_category[$slug][] = $product;
        }
    }

    $catalog = [];
    foreach (boblift_product_category_hierarchy() as $group) {
        $parent_slug = (string) ($group['slug'] ?? '');
        if ($parent_slug === '') {
            continue;
        }
        $child_slugs = array_values(array_filter(array_map(static fn(array $child): string => (string) ($child['slug'] ?? ''), $group['children'] ?? [])));
        // Categories added below the listed children (grandchildren and deeper) belong to the family too.
        $parent_term = get_term_by('slug', $parent_slug, 'product_category');
        if ($parent_term instanceof WP_Term) {
            $descendants = get_terms(['taxonomy' => 'product_category', 'child_of' => $parent_term->term_id, 'hide_empty' => false, 'fields' => 'id=>slug']);
            $child_slugs = array_values(array_unique(array_merge($child_slugs, is_wp_error($descendants) ? [] : array_values($descendants))));
        }
        $parent_products = $products_by_category[$parent_slug] ?? [];
        $total = $category_totals[$parent_slug] ?? 0;
        $buckets = [];
        foreach ($child_slugs as $slug) {
            $total += $category_totals[$slug] ?? 0;
            if (!empty($products_by_category[$slug])) {
                $buckets[] = $products_by_category[$slug];
            }
        }
        $group_products = array_merge($parent_products, ...$buckets);
        // A product listed in more than one bucket counts once.
        $seen_group_products = [];
        $group_products = array_values(array_filter($group_products, static function (array $product) use (&$seen_group_products): bool {
            $key = (string) ($product['id'] ?? $product['slug'] ?? '');
            if ($key !== '' && isset($seen_group_products[$key])) {
                return false;
            }
            $seen_group_products[$key] = true;
            return true;
        }));
        if ($group_products === []) {
            continue;
        }
        $limit = max(1, $section['maxCategoryProducts']);
        if ($selected_order !== []) {
            usort($group_products, static fn(array $a, array $b): int => ($selected_order[(string) ($a['slug'] ?? '')] ?? 999) <=> ($selected_order[(string) ($b['slug'] ?? '')] ?? 999));
            $picked = array_slice($group_products, 0, $limit);
        } else {
            $picked = boblift_home_product_pick($buckets, $parent_products, $limit);
        }
        boblift_home_product_sort_products($group_products);
        $parent_category = $category_map[$parent_slug] ?? (array) ($group['category'] ?? []);
        $title = (string) ($parent_category['title'] ?? ($group['title'] ?? $parent_slug));
        $image = (string) (($group_products[0]['image'] ?? '') ?: ($parent_category['image'] ?? ''));
        $catalog[] = [
            'name' => $title,
            'image' => $image !== '' ? $image : 'images/products/crane-accessories.png',
            'href' => boblift_product_nav_url('/' . $parent_slug, $title),
            'total' => $total,
            'products' => $picked,
        ];
    }

    return $catalog;
}

// One model per sub-type in turn, so the preview shows the range of a family instead of its heaviest few
// of one type. Products filed directly under the parent only fill leftover slots.
function boblift_home_product_pick(array $buckets, array $parent_products, int $limit): array
{
    foreach ($buckets as &$bucket) {
        boblift_home_product_sort_products($bucket);
    }
    unset($bucket);
    boblift_home_product_sort_products($parent_products);

    $picked = [];
    for ($round = 0; count($picked) < $limit; $round++) {
        $added = false;
        foreach ($buckets as $bucket) {
            if (isset($bucket[$round]) && count($picked) < $limit) {
                $picked[] = $bucket[$round];
                $added = true;
            }
        }
        if (!$added) {
            break;
        }
    }
    foreach ($parent_products as $product) {
        if (count($picked) >= $limit) {
            break;
        }
        $picked[] = $product;
    }

    return $picked;
}

function boblift_home_product_sort_products(array &$products): void
{
    usort($products, static function (array $a, array $b): int {
        return ((int) ($b['featured'] ?? false) <=> (int) ($a['featured'] ?? false))
            ?: ((int) ($a['home_sort'] ?? 100) <=> (int) ($b['home_sort'] ?? 100))
            ?: (boblift_home_product_capacity_value($b) <=> boblift_home_product_capacity_value($a))
            ?: ((int) ($b['popularity'] ?? 50) <=> (int) ($a['popularity'] ?? 50));
    });
}

function boblift_home_product_capacity_value(array $product): float
{
    if (isset($product['capacity_value']) && is_numeric($product['capacity_value'])) {
        return (float) $product['capacity_value'];
    }
    if (preg_match('/([0-9]+(?:[.-][0-9]+)?)/', (string) ($product['capacity'] ?? $product['title'] ?? ''), $match)) {
        return (float) str_replace('-', '.', $match[1]);
    }
    return 0.0;
}

function boblift_render_home_products(): void
{
    $section = boblift_home_product_section_config();
    $catalog = boblift_home_product_catalog($section);
    if (!$catalog) {
        return;
    }
    ?>
    <section id="products" class="relative overflow-hidden bg-[linear-gradient(180deg,#ffffff_0%,#f8fbff_48%,#ffffff_100%)] py-8 md:py-14 lg:py-20" data-home-products>
        <div class="page-shell">
            <div class="relative">
                <div class="mb-6 flex flex-col gap-3 md:mb-8 md:gap-4 lg:mb-10 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="section-label mb-3"><?php echo esc_html($section['label']); ?></p>
                        <h2 class="text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em] text-ink"><?php echo esc_html($section['title']); ?></h2>
                    </div>
                </div>

                <?php $category_count = count($catalog); $fits = $category_count <= 6; ?>
                <div class="boblift-cat-wrap relative z-10 <?php echo $fits ? 'is-fit' : ''; ?>" style="--cat-count: <?php echo esc_attr((string) $category_count); ?>">
                    <?php if ($section['showCategoryScrollButtons']) : ?><button class="boblift-cat-arrow group absolute -left-4 top-1/2 z-20 hidden h-10 w-10 -translate-y-1/2 place-items-center rounded-sm border border-blue-100 bg-white/95 text-[#08234a] shadow-raised backdrop-blur transition duration-300 hover:scale-105 hover:border-blue-300 hover:text-blue-700 disabled:pointer-events-none disabled:opacity-30 lg:grid" type="button" data-product-scroll="prev" aria-label="<?php echo esc_attr($section['previousCategoriesLabel']); ?>" disabled><?php echo boblift_icon('chevron-left', 'h-5 w-5 drop-shadow-sm stroke-[3]'); ?></button><?php endif; ?>
                    <div class="boblift-cat-scroller" data-product-category-scroller>
                        <div class="boblift-cat-track">
                            <?php foreach ($catalog as $index => $category) : ?>
                                <button class="boblift-cat-card group" type="button" aria-pressed="<?php echo $index === 0 ? 'true' : 'false'; ?>" data-product-category-tab="<?php echo esc_attr((string) $index); ?>">
                                    <span class="boblift-cat-media"><img src="<?php echo esc_url(boblift_image_src($category['image'])); ?>" alt="" loading="lazy" decoding="async"></span>
                                    <span class="boblift-cat-label"><span class="boblift-cat-name"><?php echo esc_html($category['name']); ?></span><?php echo boblift_icon('arrow-right', 'boblift-cat-arrow-icon h-[18px] w-[18px]'); ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php if ($section['showCategoryScrollButtons']) : ?><button class="boblift-cat-arrow group absolute -right-4 top-1/2 z-20 hidden h-10 w-10 -translate-y-1/2 place-items-center rounded-sm border border-blue-100 bg-white/95 text-[#08234a] shadow-raised backdrop-blur transition duration-300 hover:scale-105 hover:border-blue-300 hover:text-blue-700 disabled:pointer-events-none disabled:opacity-30 lg:grid" type="button" data-product-scroll="next" aria-label="<?php echo esc_attr($section['nextCategoriesLabel']); ?>" disabled><?php echo boblift_icon('chevron-right', 'h-5 w-5 drop-shadow-sm stroke-[3]'); ?></button><?php endif; ?>
                </div>

                <div class="relative z-10 mt-8 md:mt-10" data-product-stage>
                    <?php foreach ($catalog as $index => $category) : boblift_render_home_product_grid($category, $index, $section); endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    <?php
}

function boblift_render_home_product_grid(array $category, int $index, array $section): void
{
    $total = (int) ($category['total'] ?? count($category['products']));
    $view_all_label = $total > 1 ? str_replace('{count}', (string) $total, $section['viewAllLabel']) : '';
    ?>
    <div class="product-flip-panel <?php echo $index === 0 ? '' : 'hidden'; ?>" data-product-grid-panel="<?php echo esc_attr((string) $index); ?>">
        <div class="mb-5 flex items-center justify-between gap-4 border-b border-line pb-4 md:mb-7 md:pb-5">
            <h3 class="flex min-w-0 items-center gap-3 text-[22px] font-black leading-tight tracking-[-0.005em] text-ink sm:text-[28px] lg:text-[32px]"><span class="h-2 w-7 shrink-0 skew-x-[-24deg] bg-blue-600 sm:h-2.5 sm:w-9"></span><span class="min-w-0"><?php echo esc_html($category['name']); ?></span></h3>
            <?php if ($view_all_label !== '') : ?><a class="hidden shrink-0 items-center justify-center gap-1.5 whitespace-nowrap rounded-sm bg-navy px-5 py-2.5 text-xs font-black text-white shadow-card transition hover:-translate-y-0.5 hover:bg-[#0b2d5b] sm:inline-flex md:px-6 md:py-3 md:text-sm" href="<?php echo esc_url($category['href']); ?>"><?php echo esc_html($view_all_label); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a><?php endif; ?>
        </div>
        <?php if (count($category['products']) === 1) : ?>
            <?php boblift_render_home_product_feature($category['products'][0], $section); ?>
        <?php else : ?>
        <?php $moment_label = (string) (boblift_site_ui('productArchive')['momentShortLabel'] ?? 'Moment'); ?>
        <div class="grid grid-cols-2 gap-3 sm:gap-6 lg:grid-cols-4 lg:gap-5 xl:gap-7">
            <?php foreach ($category['products'] as $product_index => $product) : ?>
                <a class="group flex min-h-[260px] flex-col overflow-hidden rounded border border-line bg-white text-left shadow-card transition duration-300 hover:-translate-y-1.5 hover:border-blue-300 sm:min-h-[410px]  lg:min-h-[360px] xl:min-h-[410px]" href="<?php echo esc_url(boblift_product_url($product)); ?>">
                    <span class="relative block h-[136px] w-full overflow-hidden bg-[radial-gradient(circle_at_48%_34%,#ffffff_0%,#f8fafc_34%,#eef2f7_64%,#e2e8f0_100%)] sm:h-auto sm:aspect-[1.3/1]"><span class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_50%_82%,rgba(15,23,42,0.12),transparent_44%)]"></span><img class="absolute inset-0 z-10 h-full w-full object-contain p-3 drop-shadow-[0_6px_8px_rgba(11,27,51,0.10)] transition duration-300 group-hover:scale-[1.035] sm:p-5" src="<?php echo esc_url(boblift_product_image($product)); ?>" alt="<?php echo esc_attr($product['title'] ?? ''); ?>" loading="lazy" decoding="async"></span>
                    <?php
                    $card_model = trim((string) ($product['model'] ?? ''));
                    $product_name = trim(str_ireplace($card_model, '', (string) ($product['title'] ?? '')), " \t\n\r\0\x0B-_–—|/:");
                    ?>
                    <span class="mt-3 line-clamp-2 min-h-8 px-3 text-[11px] font-black uppercase leading-4 tracking-[0.08em] text-slate-400 sm:mt-5 sm:min-h-10 sm:px-6 sm:text-xs sm:leading-5 sm:tracking-[0.1em]"><?php echo esc_html($card_model === '' ? (string) ($product['category_title'] ?? 'BOB-LIFT') : ($product_name !== '' ? $product_name : ($product['title'] ?? 'BOB-LIFT Crane'))); ?></span>
                    <?php if ($card_model !== '') : ?>
                        <span class="boblift-home-product-card-title mt-1 block min-h-12 px-3 text-[clamp(22px,5.5vw,30px)] font-black uppercase leading-none tracking-[0.025em] text-ink sm:min-h-14 sm:px-6 sm:text-[34px] sm:tracking-[0.035em]"><?php echo esc_html($card_model); ?></span>
                    <?php else : ?>
                        <span class="mt-1 mb-3 line-clamp-3 px-3 text-base font-black leading-tight text-ink sm:px-6 sm:text-xl"><?php echo esc_html((string) ($product['title'] ?? 'BOB-LIFT')); ?></span>
                    <?php endif; ?>
                    <span class="mx-3 mb-3 mt-auto flex flex-col gap-2 border-t border-line pt-3 text-xs font-bold text-slate-500 sm:mx-6 sm:mb-6 sm:flex-row sm:items-center sm:justify-between sm:gap-3 sm:pt-4 sm:text-sm">
                        <?php
                        // Tonnage is already in the name and the model, so the footer shows the lifting moment instead.
                        $card_moment = (float) ($product['filter_values']['moment'] ?? 0);
                        if ($section['showProductSpecs'] && $card_moment > 0) : ?><span class="flex min-w-0 items-baseline gap-1.5 whitespace-nowrap"><span class="font-semibold text-slate-400"><?php echo esc_html($moment_label); ?></span><span class="font-black text-ink"><?php echo esc_html(rtrim(rtrim(number_format($card_moment, 2, '.', ''), '0'), '.') . ' t·m'); ?></span></span><?php else : ?><span></span><?php endif; ?>
                        <span class="inline-flex items-center gap-1.5 whitespace-nowrap font-black text-blue-600"><span class="sm:hidden"><?php echo esc_html($section['detailsShortLabel']); ?></span><span class="hidden sm:inline"><?php echo esc_html($section['viewDetailsLabel']); ?></span><?php echo boblift_icon('arrow-right', 'h-3.5 w-3.5 transition duration-300 group-hover:translate-x-1'); ?></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if ($view_all_label !== '') : ?><a class="mt-5 flex h-12 w-full items-center justify-center gap-1.5 rounded-sm bg-navy px-5 text-sm font-black text-white shadow-card transition hover:bg-[#0b2d5b] sm:hidden" href="<?php echo esc_url($category['href']); ?>"><?php echo esc_html($view_all_label); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a><?php endif; ?>
    </div>
    <?php
}

// A family with a single model gets one wide card instead of a lone tile in a four-column row.
function boblift_render_home_product_feature(array $product, array $section): void
{
    $model = trim((string) ($product['model'] ?? ''));
    $specs = array_values(array_filter((array) ($product['specs'] ?? []), static fn($spec): bool => is_array($spec) && trim((string) ($spec['label'] ?? '')) !== '' && trim((string) ($spec['value'] ?? '')) !== ''));
    $summary = trim(wp_strip_all_tags((string) ($product['short_description'] ?? '')));
    $detail_url = boblift_product_url($product);
    ?>
    <article class="grid overflow-hidden rounded border border-line bg-white shadow-card lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)]">
        <a class="group relative block aspect-[1.4/1] overflow-hidden bg-[radial-gradient(circle_at_48%_34%,#ffffff_0%,#f8fafc_34%,#eef2f7_64%,#e2e8f0_100%)] lg:aspect-auto lg:min-h-[420px]" href="<?php echo esc_url($detail_url); ?>"><img class="absolute inset-0 z-10 h-full w-full object-contain p-6 drop-shadow-[0_6px_8px_rgba(11,27,51,0.10)] transition duration-300 group-hover:scale-[1.03] sm:p-10" src="<?php echo esc_url(boblift_product_image($product)); ?>" alt="<?php echo esc_attr($product['title'] ?? ''); ?>" loading="lazy" decoding="async"></a>
        <div class="flex min-w-0 flex-col justify-center p-5 sm:p-8 lg:p-10 xl:p-14">
            <?php if ($model !== '') : ?><p class="text-[11px] font-black uppercase tracking-[0.1em] text-slate-400 sm:text-xs"><?php echo esc_html($model); ?></p><?php endif; ?>
            <h4 class="mt-2 text-[26px] font-black leading-[1.05] tracking-[-0.005em] text-ink sm:text-[36px] xl:text-[44px]"><?php echo esc_html($product['title'] ?? ''); ?></h4>
            <?php if ($section['showProductSpecs'] && $specs) : ?>
                <dl class="mt-5 grid gap-px overflow-hidden rounded border border-line bg-line sm:mt-6">
                    <?php foreach (array_slice($specs, 0, 3) as $spec) : ?>
                        <div class="flex items-center justify-between gap-4 bg-white px-4 py-3 text-sm"><dt class="font-bold text-slate-500"><?php echo esc_html((string) $spec['label']); ?></dt><dd class="text-right font-black text-ink"><?php echo esc_html((string) $spec['value']); ?></dd></div>
                    <?php endforeach; ?>
                </dl>
            <?php endif; ?>
            <?php if ($summary !== '') : ?><p class="mt-5 line-clamp-3 text-sm leading-6 text-slate-500 sm:text-base sm:leading-7"><?php echo esc_html($summary); ?></p><?php endif; ?>
            <div class="mt-6 flex flex-wrap gap-3 sm:mt-8">
                <a class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-sm bg-navy px-5 py-3 text-sm font-black text-white shadow-card transition hover:-translate-y-0.5 hover:bg-[#0b2d5b] md:px-6" href="<?php echo esc_url($detail_url); ?>"><?php echo esc_html($section['viewDetailsLabel']); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
                <a class="inline-flex items-center justify-center whitespace-nowrap rounded-sm border border-line bg-white px-5 py-3 text-sm font-black text-ink transition hover:-translate-y-0.5 hover:border-blue-300 hover:text-blue-700 md:px-6" href="<?php echo esc_url((string) ($product['inquiry_url'] ?? boblift_url('/contact.html#inquiry'))); ?>" data-inquiry-modal-open data-inquiry-source="首页产品卡片" data-inquiry-product="<?php echo esc_attr((string) ($product['title'] ?? '')); ?>" data-inquiry-product-slug="<?php echo esc_attr((string) ($product['slug'] ?? '')); ?>"><?php echo esc_html($section['requestQuoteLabel']); ?></a>
            </div>
        </div>
    </article>
    <?php
}
