<?php
if (!defined('ABSPATH')) {
    exit;
}

function boblift_home_product_section_config(): array
{
    $home = boblift_options()['home'] ?? [];
    $section = $home['product_section'] ?? [];

    return [
        'label' => $section['label'] ?? 'Main Products',
        'title' => $section['title'] ?? 'BOB-LIFT Crane Solutions',
        'maxCategoryProducts' => (int) ($section['max_category_products'] ?? 8),
        'showCategoryScrollButtons' => (bool) ($section['show_category_scroll_buttons'] ?? true),
        'findModelTitle' => $section['find_model_title'] ?? 'Find Your Model',
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

    if (!empty($section['selectedProductSlugs'])) {
        $selected_order = array_flip($section['selectedProductSlugs']);
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
    $grouped_slugs = [];
    foreach (boblift_product_category_group_definitions() as $group) {
        $children = array_values(array_filter(array_map('strval', $group['children'] ?? [])));
        if ($children === []) {
            continue;
        }
        $group_products = [];
        foreach ($children as $child_slug) {
            $grouped_slugs[$child_slug] = true;
            foreach (($products_by_category[$child_slug] ?? []) as $product) {
                $group_products[] = $product;
            }
        }
        if ($group_products === []) {
            continue;
        }
        boblift_home_product_sort_products($group_products);
        $parent_slug = (string) ($group['slug'] ?? '');
        $parent_category = $category_map[$parent_slug] ?? [];
        $catalog[] = [
            'name' => (string) ($group['title'] ?? ($parent_category['title'] ?? $parent_slug)),
            'image' => (string) ($parent_category['image'] ?? ($group_products[0]['image'] ?? 'images/products/crane-accessories.png')),
            'href' => boblift_product_nav_url('/' . $parent_slug, (string) ($group['title'] ?? '')),
            'products' => array_slice($group_products, 0, max(1, $section['maxCategoryProducts'])),
        ];
    }

    foreach ($categories as $category) {
        $slug = (string) ($category['slug'] ?? '');
        if ($slug === '' || isset($grouped_slugs[$slug])) {
            continue;
        }
        $category_products = $products_by_category[$slug] ?? [];
        if (!$category_products) {
            continue;
        }
        boblift_home_product_sort_products($category_products);
        $catalog[] = [
            'name' => $category['title'] ?? $slug,
            'image' => $category_products[0]['image'] ?? ($category['image'] ?? 'images/products/crane-accessories.png'),
            'href' => boblift_product_nav_url('/' . $slug, (string) ($category['title'] ?? '')),
            'products' => array_slice($category_products, 0, max(1, $section['maxCategoryProducts'])),
        ];
    }

    return $catalog;
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
                        <h2 class="max-w-none whitespace-nowrap text-[clamp(26px,6.5vw,32px)] font-black leading-[0.96] tracking-[-0.025em] text-ink sm:text-[48px] lg:text-[62px]"><?php echo esc_html($section['title']); ?></h2>
                    </div>
                </div>

                <div class="relative z-10">
                    <?php if ($section['showCategoryScrollButtons']) : ?><button class="group absolute -start-4 top-1/2 z-20 hidden h-10 w-10 -translate-y-1/2 place-items-center rounded-2xl border border-blue-100 bg-white/95 text-[#08234a] shadow-[0_14px_34px_rgba(8,35,74,0.14)] backdrop-blur transition duration-300 hover:scale-105 hover:border-blue-300 hover:text-blue-700 hover:shadow-[0_18px_42px_rgba(37,99,235,0.16)] disabled:pointer-events-none disabled:opacity-30 lg:grid" type="button" data-product-scroll="prev" aria-label="<?php echo esc_attr($section['previousCategoriesLabel']); ?>" disabled><?php echo boblift_icon('chevron-left', 'h-5 w-5 drop-shadow-sm stroke-[3]'); ?></button><?php endif; ?>
                    <div class="boblift-home-category-scroller -mx-4 overflow-x-auto scroll-smooth px-4 py-2 [-ms-overflow-style:none] [scrollbar-width:none] sm:mx-0 sm:px-0.5 [&::-webkit-scrollbar]:hidden" data-product-category-scroller>
                        <div class="boblift-home-category-track grid auto-cols-[calc((100vw-56px)/2)] grid-flow-col gap-3 sm:auto-cols-[230px] md:gap-4 lg:auto-cols-[calc((100%-48px)/4)] xl:auto-cols-[270px]">
                            <?php foreach ($catalog as $index => $category) : ?>
                                <button class="boblift-home-category-card group relative h-[146px] overflow-hidden rounded-[18px] border bg-white text-ink shadow-[0_10px_28px_rgba(8,35,74,0.045)] transition duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-[0_18px_44px_rgba(8,35,74,0.08)] sm:h-[208px] xl:h-[224px] <?php echo $index === 0 ? 'border-blue-500 bg-blue-50/55 shadow-[0_18px_48px_rgba(37,99,235,0.14)]' : 'border-line'; ?>" type="button" aria-pressed="<?php echo $index === 0 ? 'true' : 'false'; ?>" data-product-category-tab="<?php echo esc_attr((string) $index); ?>">
                                    <span class="absolute inset-x-8 bottom-0 h-[3px] rounded-t-full transition <?php echo $index === 0 ? 'bg-blue-600' : 'bg-transparent group-hover:bg-blue-200'; ?>" data-product-category-line></span>
                                    <span class="boblift-home-category-media-wrap block px-3 pt-3 sm:px-4 sm:pt-4"><span class="boblift-home-category-media relative block h-14 rounded-[16px] bg-[radial-gradient(circle_at_50%_52%,rgba(248,251,255,0.95),rgba(255,255,255,0)_70%)] sm:h-28"><img class="absolute inset-0 h-full w-full object-contain p-1.5 transition duration-300 group-hover:scale-[1.045]" src="<?php echo esc_url(boblift_image_src($category['image'])); ?>" alt="<?php echo esc_attr($category['name']); ?>" loading="lazy" decoding="async"></span></span>
                                    <span class="boblift-home-category-title-wrap block px-3 pb-4 pt-2 text-center sm:px-4 sm:pb-5"><span class="boblift-home-category-title line-clamp-3 text-sm font-black leading-5 tracking-[-0.02em] text-ink sm:line-clamp-2 sm:text-base sm:leading-6"><?php echo esc_html($category['name']); ?></span></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php if ($section['showCategoryScrollButtons']) : ?><button class="group absolute -end-4 top-1/2 z-20 hidden h-10 w-10 -translate-y-1/2 place-items-center rounded-2xl border border-blue-100 bg-white/95 text-[#08234a] shadow-[0_14px_34px_rgba(8,35,74,0.14)] backdrop-blur transition duration-300 hover:scale-105 hover:border-blue-300 hover:text-blue-700 hover:shadow-[0_18px_42px_rgba(37,99,235,0.16)] disabled:pointer-events-none disabled:opacity-30 lg:grid" type="button" data-product-scroll="next" aria-label="<?php echo esc_attr($section['nextCategoriesLabel']); ?>" disabled><?php echo boblift_icon('chevron-right', 'h-5 w-5 drop-shadow-sm stroke-[3]'); ?></button><?php endif; ?>
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
    ?>
    <div class="product-flip-panel <?php echo $index === 0 ? '' : 'hidden'; ?>" data-product-grid-panel="<?php echo esc_attr((string) $index); ?>">
        <div class="mb-6 flex flex-col gap-3 md:mb-8 md:gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="mb-3 flex items-center gap-2 text-[11px] font-black uppercase leading-5 tracking-[0.16em] text-blue-600 sm:mb-4 sm:gap-3 sm:text-sm sm:tracking-[0.24em]"><span class="h-2 w-7 shrink-0 skew-x-[-24deg] bg-blue-600 sm:h-2.5 sm:w-9"></span><?php echo esc_html($category['name']); ?></p>
                <h3 class="text-[clamp(34px,9vw,42px)] font-black leading-none tracking-[-0.025em] text-ink sm:text-[56px] lg:text-[68px]"><?php echo esc_html($section['findModelTitle']); ?></h3>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3 sm:gap-6 lg:grid-cols-3 xl:grid-cols-4 xl:gap-7">
            <?php foreach ($category['products'] as $product_index => $product) : ?>
                <a class="group flex min-h-[260px] flex-col overflow-hidden rounded-[18px] border border-line bg-white text-start shadow-[0_12px_32px_rgba(8,35,74,0.055)] transition duration-300 hover:-translate-y-1.5 hover:border-blue-300 hover:shadow-[0_26px_70px_rgba(37,99,235,0.13)] sm:min-h-[410px] sm:rounded-[22px] lg:min-h-[360px] xl:min-h-[410px]" href="<?php echo esc_url(boblift_product_url($product)); ?>">
                    <span class="relative block h-[136px] w-full overflow-hidden bg-[radial-gradient(circle_at_48%_34%,#ffffff_0%,#f8fafc_34%,#eef2f7_64%,#e2e8f0_100%)] sm:h-auto sm:aspect-[1.3/1]"><span class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_50%_82%,rgba(15,23,42,0.12),transparent_44%)]"></span><span class="pointer-events-none absolute bottom-4 start-[16%] end-[16%] h-8 rounded-full bg-slate-400/18 blur-xl sm:bottom-6 sm:h-12 sm:blur-2xl"></span><img class="absolute inset-0 z-10 h-full w-full object-contain p-3 drop-shadow-[0_24px_28px_rgba(11,27,51,0.14)] transition duration-300 group-hover:scale-[1.035] sm:p-5" src="<?php echo esc_url(boblift_product_image($product)); ?>" alt="<?php echo esc_attr($product['title'] ?? ''); ?>" loading="lazy" decoding="async"></span>
                    <?php $product_name = trim(str_replace((string) ($product['model'] ?? ''), '', (string) ($product['title'] ?? ''))); ?>
                    <span class="mt-3 block px-3 text-[11px] font-black uppercase leading-4 tracking-[0.08em] text-slate-400 sm:mt-5 sm:px-6 sm:text-xs sm:leading-5 sm:tracking-[0.1em]"><?php echo esc_html($product_name !== '' ? $product_name : ($product['title'] ?? 'BOB-LIFT Crane')); ?></span>
                    <span class="boblift-home-product-card-title mt-1 block min-h-12 px-3 text-[clamp(22px,5.5vw,30px)] font-black uppercase leading-none tracking-[0.025em] text-ink sm:min-h-14 sm:px-6 sm:text-[34px] sm:tracking-[0.035em]"><?php echo esc_html($product['model'] ?? 'BOB-LIFT'); ?></span>
                    <span class="mx-3 mb-3 mt-auto flex flex-col gap-2 border-t border-line pt-3 text-xs font-bold text-slate-500 sm:mx-6 sm:mb-6 sm:flex-row sm:items-center sm:justify-between sm:gap-3 sm:pt-4 sm:text-sm">
                        <?php if ($section['showProductSpecs']) : ?><span class="flex min-w-0 items-center gap-1.5"><span><?php echo esc_html($product['capacity'] ?? 'Custom'); ?></span></span><?php endif; ?>
                        <span class="inline-flex items-center gap-1.5 whitespace-nowrap font-black text-blue-600"><span class="sm:hidden"><?php echo esc_html($section['detailsShortLabel']); ?></span><span class="hidden sm:inline"><?php echo esc_html($section['viewDetailsLabel']); ?></span><?php echo boblift_icon('arrow-right', 'h-3.5 w-3.5 transition duration-300 group-hover:translate-x-1'); ?></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}
