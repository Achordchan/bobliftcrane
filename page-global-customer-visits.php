<?php
/**
 * Template Name: BOB-LIFT Global Customer Visits
 */
get_header();

$about_page = function_exists('boblift_about_page_options') ? boblift_about_page_options() : [];
$visits = is_array($about_page['customer_visits'] ?? null) ? $about_page['customer_visits'] : [];
$items = array_values(array_filter($visits['items'] ?? [], static function ($item): bool {
    if (!is_array($item)) {
        return false;
    }
    $image = $item['image'] ?? [];
    if (is_array($image)) {
        return (int) ($image['id'] ?? 0) > 0 || trim((string) ($image['path'] ?? '')) !== '';
    }
    return trim((string) $image) !== '';
}));
$per_page = 12;
$total_items = count($items);
$total_pages = max(1, (int) ceil($total_items / $per_page));
$current_page = isset($_GET['visit_page']) ? max(1, (int) $_GET['visit_page']) : 1;
$current_page = min($current_page, $total_pages);
$paged_items = array_slice($items, ($current_page - 1) * $per_page, $per_page);
$grid_total = count($paged_items);
$pagination_base = function_exists('boblift_url') ? boblift_url('/global-customer-visits.html') : home_url('/global-customer-visits.html');
?>
<div class="page-shell pb-8 pt-5 md:pb-10 md:pt-8">
    <?php
    boblift_page_hero([
        'label' => (string) ($visits['label'] ?? 'Global Customer Visits'),
        'title' => (string) ($visits['title'] ?? 'Welcoming partners to see how BOB-LIFT builds.'),
        'text' => (string) ($visits['text'] ?? 'Factory visits help customers review production details, discuss lifting requirements and align the right solution before cooperation moves forward.'),
        'meta' => $total_items > 0 ? $total_items . ' ' . (string) ($visits['visit_count_suffix'] ?? 'Visit Photos') : '',
        'image' => boblift_image_setting_url($visits['hero_image'] ?? [], 'images/page-heroes/factory-campus.webp'),
        'image_alt' => (string) ($visits['hero_image_alt'] ?? 'The BOB-LIFT factory campus from the air'),
    ]);
    ?>
</div>

<section class="bg-white pb-12 md:pb-16 lg:pb-20">
    <div class="page-shell">
        <?php if ($paged_items !== []) : ?>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:gap-6">
                <?php foreach ($paged_items as $index => $item) :
                    $global_index = (($current_page - 1) * $per_page) + $index;
                    $is_tail_single = $grid_total % 3 === 1 && $index === $grid_total - 1;
                    ?>
                    <figure class="group relative overflow-hidden rounded border border-line bg-[#EEF5FF] shadow-card transition duration-300 hover:-translate-y-1 <?php echo $is_tail_single ? 'lg:col-start-2' : ''; ?>">
                        <div class="relative aspect-[1.18/1] overflow-hidden">
                            <?php echo boblift_img_from_setting($item['image'] ?? [], 'images/factory-workshop.jpg', (string) ($item['title'] ?? $item['label'] ?? 'BOB-LIFT customer visit'), 'absolute inset-0 h-full w-full object-cover object-center transition duration-700 group-hover:scale-[1.035]', ['loading' => $global_index === 0 ? 'eager' : 'lazy', 'decoding' => 'async', 'fetchpriority' => $global_index === 0 ? 'high' : 'auto']); ?>
                        </div>
                    </figure>
                <?php endforeach; ?>
            </div>
            <?php if ($total_pages > 1) : ?>
                <nav class="mt-10 flex flex-wrap items-center justify-center gap-2" aria-label="<?php echo esc_attr((string) ($visits['pagination_label'] ?? 'Customer visit pages')); ?>">
                    <?php for ($page = 1; $page <= $total_pages; $page++) :
                        $url = $page === 1 ? $pagination_base : add_query_arg('visit_page', $page, $pagination_base);
                        $active = $page === $current_page;
                        ?>
                        <a class="inline-flex h-11 min-w-11 items-center justify-center rounded-sm border px-4 text-sm font-black transition <?php echo $active ? 'border-navy bg-navy text-white shadow-card' : 'border-line bg-white text-ink hover:-translate-y-0.5 hover:border-blue-200 hover:text-blue-700'; ?>" href="<?php echo esc_url($url); ?>" <?php echo $active ? 'aria-current="page"' : ''; ?>><?php echo esc_html((string) $page); ?></a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        <?php else : ?>
            <div class="rounded border border-line bg-[#F8FAFC] p-8 text-center">
                <p class="text-base font-bold text-slate-600"><?php echo esc_html((string) ($visits['empty_text'] ?? 'No customer visit photos have been added yet.')); ?></p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php get_footer(); ?>
