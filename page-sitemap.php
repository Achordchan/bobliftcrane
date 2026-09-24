<?php
/**
 * Template Name: HTML Sitemap
 */
get_header();
$sitemap_ui = function_exists('boblift_sitemap_page_options') ? boblift_sitemap_page_options() : [];


function boblift_sitemap_label(string $label): string
{
    if (function_exists('boblift_localize_product_text_fragments')) {
        $label = (string) boblift_localize_product_text_fragments($label);
    }
    return function_exists('boblift_localized_static_text') ? boblift_localized_static_text($label) : $label;
}

function boblift_sitemap_pages(): array
{
    $pages = get_pages([
        'sort_column' => 'menu_order,post_title',
        'sort_order' => 'ASC',
        'post_status' => 'publish',
        'exclude' => function_exists('boblift_utility_page_ids') ? boblift_utility_page_ids() : [],
    ]);
    $pages = array_values(array_filter($pages, static fn($page): bool => $page instanceof WP_Post));

    // Home first, then the rest in menu order.
    $front_id = (int) get_option('page_on_front');
    if ($front_id > 0 && function_exists('pll_get_post')) {
        $front_id = (int) (pll_get_post($front_id) ?: $front_id);
    }
    usort($pages, static fn(WP_Post $a, WP_Post $b): int => (int) ($a->ID !== $front_id) <=> (int) ($b->ID !== $front_id));

    return $pages;
}

function boblift_sitemap_posts(string $post_type, int $limit = 120): array
{
    return get_posts([
        'post_type' => $post_type,
        'post_status' => 'publish',
        'numberposts' => $limit,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);
}

function boblift_sitemap_terms(string $taxonomy): array
{
    $terms = get_terms([
        'taxonomy' => $taxonomy,
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC',
    ]);
    return is_wp_error($terms) ? [] : array_values(array_filter($terms, static fn($term): bool => $term instanceof WP_Term));
}

function boblift_sitemap_link_rows(array $items): array
{
    return array_values(array_filter($items, static function (array $item): bool {
        return trim((string) ($item[0] ?? '')) !== '' && !is_wp_error($item[1] ?? '');
    }));
}

function boblift_render_sitemap_block(string $title, array $links, string $columns = 'md:grid-cols-2'): void
{
    $links = boblift_sitemap_link_rows($links);
    if (!$links) {
        return;
    }
    ?>
    <section class="rounded border border-line bg-white p-6 shadow-card md:p-8">
        <div class="mb-5 flex items-center justify-between gap-4">
            <h2 class="text-2xl font-black tracking-[-0.01em] text-navy"><?php echo esc_html($title); ?></h2>
            <span class="rounded-sm border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-black text-blue-700"><?php echo esc_html((string) count($links)); ?></span>
        </div>
        <ul class="grid gap-2 <?php echo esc_attr($columns); ?>">
            <?php foreach ($links as [$label, $url]) : ?>
                <li>
                    <a class="group flex min-h-11 items-center justify-between gap-4 rounded-sm px-3 py-2 text-sm font-bold leading-6 text-slate-600 transition hover:bg-blue-50 hover:text-blue-700" href="<?php echo esc_url((string) $url); ?>">
                        <span class="min-w-0"><?php echo esc_html((string) $label); ?></span>
                        <span class="shrink-0 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-blue-600" aria-hidden="true">›</span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php
}

$core_pages = boblift_sitemap_link_rows(array_map(static fn(WP_Post $page): array => [boblift_sitemap_label(get_the_title($page)), get_permalink($page)], boblift_sitemap_pages()));
$product_categories = boblift_sitemap_link_rows(array_map(static fn(WP_Term $term): array => [boblift_sitemap_label($term->name), get_term_link($term)], boblift_sitemap_terms('product_category')));
$products = boblift_sitemap_link_rows(array_map(static fn(WP_Post $post): array => [boblift_sitemap_label(get_the_title($post)), get_permalink($post)], boblift_sitemap_posts('product', 160)));
$news_categories = boblift_sitemap_link_rows(array_map(static fn(WP_Term $term): array => [boblift_sitemap_label($term->name), get_term_link($term)], boblift_sitemap_terms('category')));
$news = boblift_sitemap_link_rows(array_map(static fn(WP_Post $post): array => [boblift_sitemap_label(get_the_title($post)), get_permalink($post)], boblift_sitemap_posts('post', 80)));
$cases = boblift_sitemap_link_rows(array_map(static fn(WP_Post $post): array => [boblift_sitemap_label(get_the_title($post)), get_permalink($post)], boblift_sitemap_posts('case', 80)));
$total_links = count($core_pages) + count($product_categories) + count($products) + count($news_categories) + count($news) + count($cases);
?>
<div class="bg-white text-ink">
    <div class="page-shell pt-5 md:pt-8">
        <?php
        boblift_page_hero([
            'label' => (string) ($sitemap_ui['label'] ?? 'HTML Sitemap'),
            'title' => (string) ($sitemap_ui['title'] ?? 'Find BOB-LIFT pages quickly.'),
            'text' => (string) ($sitemap_ui['description'] ?? 'Browse main pages, product ranges, news and customer cases from one structured index.'),
            'meta' => $total_links . ' ' . (string) ($sitemap_ui['links_label'] ?? 'Links'),
        ]);
        ?>
    </div>

    <section class="page-shell pb-12 pt-8 md:pb-16 md:pt-10 2xl:pb-20">
        <div class="grid gap-8">
            <?php boblift_render_sitemap_block((string) ($sitemap_ui['core_pages_title'] ?? 'Core Pages'), $core_pages, 'sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4'); ?>

            <section class="rounded border border-blue-100 bg-[linear-gradient(135deg,#ffffff_0%,#f8fbff_100%)] p-6 shadow-card md:p-8">
                <div class="mb-7 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                    <div>
                        <p class="section-label mb-3"><?php echo esc_html((string) ($sitemap_ui['product_range_label'] ?? 'Product Range')); ?></p>
                        <h2 class="text-3xl font-black tracking-[-0.015em] text-navy md:text-4xl"><?php echo esc_html((string) ($sitemap_ui['product_range_title'] ?? 'Products and categories')); ?></h2>
                    </div>
                    <a class="inline-flex w-fit rounded-sm border border-blue-100 bg-white px-5 py-3 text-sm font-black text-navy transition hover:bg-blue-50 hover:text-blue-700" href="<?php echo esc_url(boblift_url('/products.html')); ?>"><?php echo esc_html((string) ($sitemap_ui['view_products_label'] ?? 'View Products')); ?></a>
                </div>
                <div class="grid gap-6">
                    <?php boblift_render_sitemap_block((string) ($sitemap_ui['product_categories_title'] ?? 'Product Categories'), $product_categories, 'sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4'); ?>
                    <?php boblift_render_sitemap_block((string) ($sitemap_ui['products_title'] ?? 'Products'), $products, 'sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4'); ?>
                </div>
            </section>

            <div class="grid gap-8 lg:grid-cols-2">
                <?php boblift_render_sitemap_block((string) ($sitemap_ui['news_categories_title'] ?? 'News Categories'), $news_categories, 'grid-cols-1'); ?>
                <?php boblift_render_sitemap_block((string) ($sitemap_ui['news_title'] ?? 'News'), $news, 'grid-cols-1'); ?>
            </div>

            <?php boblift_render_sitemap_block((string) ($sitemap_ui['cases_title'] ?? 'Cases'), $cases, 'sm:grid-cols-2 lg:grid-cols-3'); ?>
        </div>
    </section>
</div>
<?php get_footer(); ?>
