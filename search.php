<?php
get_header();

$ui = boblift_site_ui('search');
$product_detail_ui = boblift_site_ui('productDetail');
$query = trim(get_search_query(false));
$type = isset($_GET['type']) && is_string($_GET['type']) ? sanitize_key(wp_unslash($_GET['type'])) : '';
$type = in_array($type, ['product', 'news', 'case'], true) ? $type : '';
$page = isset($_GET['pg']) ? max(1, absint($_GET['pg'])) : 1;
$news_per_page = $type === 'news' ? 10 : 4;
$case_per_page = $type === 'case' ? 12 : 3;
$product_limit = $type === 'product' ? 0 : 6;

$products = $query !== '' ? boblift_search_products($query) : [];
$news_query = $query !== '' ? boblift_search_posts($query, 'post', $news_per_page, $type === 'news' ? $page : 1) : null;
$case_query = $query !== '' ? boblift_search_posts($query, 'case', $case_per_page, $type === 'case' ? $page : 1) : null;
$counts = [
    'product' => count($products),
    'news' => $news_query ? (int) $news_query->found_posts : 0,
    'case' => $case_query ? (int) $case_query->found_posts : 0,
];
$total = array_sum($counts);
// A selected tab with no matches (e.g. a new search from the News tab that only matches products) falls back to All results.
if ($type !== '' && $total > 0 && ($counts[$type] ?? 0) === 0) {
    $type = '';
    $page = 1;
    $product_limit = 6;
    $news_per_page = 4;
    $case_per_page = 3;
    $news_query = boblift_search_posts($query, 'post', $news_per_page, 1);
    $case_query = boblift_search_posts($query, 'case', $case_per_page, 1);
}
$families = function_exists('boblift_product_menu_families') ? boblift_product_menu_families() : [];
$view_details_label = (string) ($product_detail_ui['viewDetailsLabel'] ?? 'View Details');

$search_url = static function (string $tab = '', int $pg = 1) use ($query): string {
    $args = ['s' => $query];
    if ($tab !== '') {
        $args['type'] = $tab;
    }
    if ($pg > 1) {
        $args['pg'] = $pg;
    }
    return add_query_arg(array_map('rawurlencode', $args), home_url('/'));
};
// Wraps the searched words in the escaped text so readers can see why each result matched.
$highlight = static function (string $text) use ($query): string {
    $words = array_filter(preg_split('/\s+/u', $query) ?: [], static fn(string $word): bool => mb_strlen($word) >= 2);
    if (!$words) {
        return esc_html($text);
    }
    // Match the plain text and escape each piece, so a word never matches inside an HTML entity (&amp; ...).
    $pattern = '/(' . implode('|', array_map(static fn(string $word): string => preg_quote($word, '/'), $words)) . ')/iu';
    $parts = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    if ($parts === false) {
        return esc_html($text);
    }
    $html = '';
    foreach ($parts as $index => $part) {
        $html .= $index % 2 === 1 ? '<mark class="bg-transparent text-blue-700">' . esc_html($part) . '</mark>' : esc_html($part);
    }
    return $html;
};
$format = static fn(string $template, array $values): string => strtr($template, $values);
$example_model = '';
if ($query === '') {
    foreach (boblift_all_products() as $product) {
        if (trim((string) ($product['model'] ?? '')) !== '') {
            $example_model = (string) $product['model'];
            break;
        }
    }
}

$product_result = static function (array $product) use ($highlight, $view_details_label): void {
    $title = trim((string) ($product['title'] ?? ''));
    $model = trim((string) ($product['model'] ?? ''));
    $name = $model !== '' ? (string) preg_replace('/^[\s\-_–—|\/:]+|[\s\-_–—|\/:]+$/u', '', str_ireplace($model, '', $title)) : '';
    $name = $name !== '' ? $name : (string) ($product['category_title'] ?? '');
    $image = boblift_product_image($product);
    $image_class = boblift_image_fit($image) === 'cutout' ? 'object-contain p-5 drop-shadow-[0_6px_8px_rgba(11,27,51,0.10)]' : 'object-contain bg-[#EDF1F5]';
    ?>
    <a class="group flex min-w-0 overflow-hidden rounded border border-[#E5E7EB] bg-white shadow-card transition duration-300 hover:-translate-y-0.5 hover:border-blue-200 sm:flex-col" href="<?php echo esc_url(boblift_product_url($product)); ?>">
        <div class="relative w-[120px] shrink-0 overflow-hidden bg-[radial-gradient(circle_at_48%_34%,#ffffff_0%,#f8fafc_34%,#eef2f7_64%,#e2e8f0_100%)] sm:aspect-[4/3] sm:w-auto">
            <img class="absolute inset-0 h-full w-full transition duration-500 group-hover:scale-[1.03] <?php echo esc_attr($image_class); ?>" src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy" decoding="async">
        </div>
        <div class="flex min-w-0 flex-1 flex-col p-4 sm:p-5">
            <p class="line-clamp-2 text-[13px] font-semibold leading-5 text-slate-500"><?php echo $highlight($name); ?></p>
            <h3 class="mt-1 break-words text-xl font-black sm:text-2xl uppercase leading-tight tracking-[0.02em] text-[#0B3F7D]"><?php echo $highlight($model !== '' ? $model : $title); ?></h3>
            <?php $specs = boblift_product_card_specs($product); if ($specs) : ?>
                <dl class="mt-3 grid grid-cols-2 gap-3 border-t border-[#E5E7EB] pt-3 sm:mt-4 sm:grid-cols-3 sm:pt-4 [&>div:nth-child(3)]:hidden sm:[&>div:nth-child(3)]:block">
                    <?php foreach ($specs as $spec) : ?>
                        <div class="min-w-0"><dt class="truncate text-[11px] font-bold text-slate-400"><?php echo esc_html((string) $spec['label']); ?></dt><dd class="mt-1 truncate text-sm font-black text-ink"><?php echo esc_html((string) $spec['value']); ?></dd></div>
                    <?php endforeach; ?>
                </dl>
            <?php endif; ?>
            <span class="mt-auto hidden items-center gap-2 pt-5 text-sm font-black sm:inline-flex text-navy transition group-hover:text-blue-700"><?php echo esc_html($view_details_label); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4 transition group-hover:translate-x-0.5'); ?></span>
        </div>
    </a>
    <?php
};

$post_result = static function (WP_Post $post) use ($highlight): void {
    $title = boblift_localize_product_text_fragments(get_the_title($post));
    if ($post->post_type === 'case') {
        $item = boblift_case_item_from_post($post);
        $meta = array_filter([(string) ($item['country'] ?? ''), (string) ($item['product_name'] ?? '')]);
        $excerpt = (string) ($item['summary'] ?? '');
        $image = boblift_item_image($item);
    } else {
        $category = get_the_category($post->ID)[0] ?? null;
        $meta = array_filter([$category instanceof WP_Term ? boblift_localized_static_text($category->name) : '', get_the_date('Y-m-d', $post)]);
        $excerpt = boblift_article_card_excerpt(boblift_localize_product_text_fragments(get_the_excerpt($post)), $title);
        $image = boblift_post_thumbnail_url($post, boblift_asset('images/page-heroes/factory-floor.webp'));
    }
    ?>
    <a class="group grid min-w-0 grid-cols-[104px_minmax(0,1fr)] items-start gap-4 rounded border border-[#E5E7EB] bg-white p-3 shadow-card transition duration-300 hover:-translate-y-0.5 hover:border-blue-200 sm:grid-cols-[200px_minmax(0,1fr)] sm:gap-6 sm:p-4" href="<?php echo esc_url(get_permalink($post)); ?>">
        <div class="relative aspect-[4/3] overflow-hidden rounded-sm bg-mist"><img class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]" src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy" decoding="async"></div>
        <div class="min-w-0 py-0.5 sm:py-2">
            <?php if ($meta) : ?><p class="truncate text-xs font-bold text-slate-400"><?php echo esc_html(implode(' · ', $meta)); ?></p><?php endif; ?>
            <h3 class="mt-1.5 line-clamp-2 text-base font-black leading-snug text-ink transition group-hover:text-blue-700 sm:text-lg"><?php echo $highlight($title); ?></h3>
            <?php if ($excerpt !== '') : ?><p class="mt-2 hidden text-sm font-semibold leading-6 text-slate-600 sm:line-clamp-2"><?php echo $highlight(wp_strip_all_tags($excerpt)); ?></p><?php endif; ?>
        </div>
    </a>
    <?php
};

$section_head = static function (string $label, int $count, string $tab, int $shown) use ($ui, $type, $search_url, $format): void {
    ?>
    <div class="mb-4 flex items-end justify-between gap-4 md:mb-5">
        <h2 class="text-xl font-black text-ink md:text-2xl"><?php echo esc_html($label); ?> <span class="text-base font-bold tabular-nums text-slate-400"><?php echo esc_html((string) $count); ?></span></h2>
        <?php if ($type === '' && $count > $shown) : ?>
            <a class="inline-flex shrink-0 items-center gap-1.5 text-sm font-black text-navy transition hover:text-blue-700" href="<?php echo esc_url($search_url($tab)); ?>"><?php echo esc_html($format((string) ($ui['viewAllTemplate'] ?? 'View all {count}'), ['{count}' => (string) $count])); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
        <?php endif; ?>
    </div>
    <?php
};

$category_links = static function () use ($families): void {
    if (!$families) {
        return;
    }
    ?>
    <div class="flex flex-wrap gap-2">
        <?php foreach ($families as $family) : ?>
            <a class="inline-flex h-10 items-center gap-2 rounded-sm border border-line bg-white px-3.5 text-sm font-bold text-navy transition hover:border-blue-300 hover:text-blue-700" href="<?php echo esc_url($family['url']); ?>"><?php echo esc_html($family['title']); ?><span class="text-xs font-bold tabular-nums text-slate-400"><?php echo esc_html((string) $family['count']); ?></span></a>
        <?php endforeach; ?>
    </div>
    <?php
};
?>

<section class="boblift-search bg-[#F8FAFC] pb-16 pt-6 md:pb-24 md:pt-10">
    <div class="page-shell">
        <header class="rounded border border-line bg-white px-5 pt-7 shadow-card sm:px-8 md:pt-10 xl:px-12 <?php echo $query !== '' ? '' : 'pb-8 md:pb-12'; ?>">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-[#0B3F7D]"><?php echo esc_html($ui['pageLabel'] ?? 'Search results'); ?></p>
            <h1 class="mt-3 break-words text-[clamp(1.75rem,2.6vw,3rem)] font-black leading-[1.08] tracking-[-0.02em] text-[#0B1B33]">
                <?php if ($query !== '') :
                    echo str_replace('{query}', '<span class="text-blue-700">' . esc_html($query) . '</span>', esc_html((string) ($ui['titleTemplate'] ?? 'Results for “{query}”')));
                else :
                    echo esc_html($ui['emptyQueryTitle'] ?? 'Search BOB-LIFT');
                endif; ?>
            </h1>
            <?php if ($query === '') : ?>
                <p class="mt-3 max-w-2xl text-sm font-semibold leading-7 text-slate-600 md:text-base"><?php echo esc_html($format((string) ($ui['emptyQueryText'] ?? ''), ['{example}' => $example_model])); ?></p>
            <?php endif; ?>
            <form class="mt-6 flex max-w-3xl gap-2 md:gap-3" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                <label class="relative min-w-0 flex-1">
                    <span class="sr-only"><?php echo esc_html($ui['inputLabel'] ?? 'Search products and articles'); ?></span>
                    <?php echo boblift_icon('search', 'pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400'); ?>
                    <input class="h-12 w-full rounded-sm border border-[#D8DEE8] bg-white pl-11 pr-4 text-base font-bold text-ink outline-none transition placeholder:font-semibold placeholder:text-slate-400 focus:border-navy focus:ring-4 focus:ring-blue-100 md:h-14 [&::-webkit-search-cancel-button]:hidden" type="search" name="s" value="<?php echo esc_attr($query); ?>" placeholder="<?php echo esc_attr($ui['placeholder'] ?? 'Search a model, crane type or topic'); ?>" autocomplete="off" enterkeyhint="search" <?php echo $query === '' ? 'autofocus' : ''; ?>>
                </label>
                <?php if ($type !== '') : ?><input type="hidden" name="type" value="<?php echo esc_attr($type); ?>"><?php endif; ?>
                <button class="inline-flex h-12 shrink-0 items-center justify-center rounded-sm bg-navy px-5 text-sm font-black text-white transition hover:bg-[#0b2d5b] md:h-14 md:px-8 md:text-base" type="submit"><?php echo esc_html($ui['submitLabel'] ?? 'Search'); ?></button>
            </form>
            <?php if ($query === '') : ?>
                <div class="mt-6"><?php $category_links(); ?></div>
            <?php endif; ?>
            <?php if ($query !== '') : ?>
                <nav class="-mx-1 mt-7 flex gap-1 overflow-x-auto md:mt-9" aria-label="<?php echo esc_attr($ui['pageLabel'] ?? 'Search results'); ?>">
                    <?php
                    $tabs = [
                        '' => [(string) ($ui['tabAll'] ?? 'All results'), $total],
                        'product' => [(string) ($ui['tabProducts'] ?? 'Products'), $counts['product']],
                        'news' => [(string) ($ui['tabNews'] ?? 'News'), $counts['news']],
                        'case' => [(string) ($ui['tabCases'] ?? 'Cases'), $counts['case']],
                    ];
                    foreach ($tabs as $tab => [$label, $count]) :
                        if ($tab !== '' && $count === 0) {
                            continue;
                        }
                        $active = $tab === $type;
                        ?>
                        <a class="relative inline-flex h-12 shrink-0 items-center gap-2 whitespace-nowrap px-3 text-sm font-black transition after:absolute after:inset-x-3 after:bottom-0 after:h-0.5 md:px-4 md:text-base <?php echo $active ? 'text-ink after:bg-navy' : 'text-slate-500 hover:text-ink'; ?>" href="<?php echo esc_url($search_url($tab)); ?>" <?php echo $active ? 'aria-current="page"' : ''; ?>><?php echo esc_html($label); ?><span class="text-xs font-bold tabular-nums <?php echo $active ? 'text-blue-700' : 'text-slate-400'; ?>"><?php echo esc_html((string) $count); ?></span></a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>
        </header>

        <?php if ($query !== '' && $total === 0) : ?>
            <div class="mt-6 rounded border border-line bg-white px-5 py-10 text-center shadow-card sm:px-8 md:mt-8 md:py-14">
                <span class="mx-auto grid h-14 w-14 place-items-center rounded-sm bg-mist text-slate-400"><?php echo boblift_icon('search-x', 'h-7 w-7'); ?></span>
                <h2 class="mx-auto mt-5 max-w-2xl break-words text-2xl font-black text-ink"><?php echo esc_html(str_replace('{query}', $query, (string) ($ui['noResultsTitle'] ?? 'No results for “{query}”'))); ?></h2>
                <p class="mx-auto mt-3 max-w-xl text-sm font-semibold leading-7 text-slate-600"><?php echo esc_html($ui['noResultsText'] ?? ''); ?></p>
                <div class="mt-6 flex justify-center"><?php $category_links(); ?></div>
            </div>
        <?php elseif ($query !== '') : ?>
            <div class="mt-8 grid gap-10 md:mt-10 md:gap-12">
                <?php if ($counts['product'] > 0 && in_array($type, ['', 'product'], true)) : ?>
                    <section>
                        <?php $section_head((string) ($ui['tabProducts'] ?? 'Products'), $counts['product'], 'product', $product_limit ?: $counts['product']); ?>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:gap-5 2xl:grid-cols-4">
                            <?php foreach ($product_limit ? array_slice($products, 0, $product_limit) : $products as $product) : $product_result($product); endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
                <?php if ($counts['news'] > 0 && in_array($type, ['', 'news'], true)) : ?>
                    <section>
                        <?php $section_head((string) ($ui['tabNews'] ?? 'News'), $counts['news'], 'news', count($news_query->posts)); ?>
                        <div class="grid gap-3 lg:grid-cols-2 lg:gap-4">
                            <?php foreach ($news_query->posts as $post_item) : $post_result($post_item); endforeach; ?>
                        </div>
                        <?php if ($type === 'news' && $news_query->max_num_pages > 1) : ?>
                            <nav class="mt-8 flex flex-wrap items-center justify-center gap-2" aria-label="<?php echo esc_attr($ui['paginationLabel'] ?? 'Search pagination'); ?>">
                                <?php for ($i = 1; $i <= (int) $news_query->max_num_pages; $i++) : ?>
                                    <a class="inline-flex h-10 min-w-10 items-center justify-center rounded-sm border px-3 text-sm font-black tabular-nums transition <?php echo $i === $page ? 'border-navy bg-navy text-white' : 'border-[#D8DEE8] bg-white text-ink hover:border-blue-300 hover:text-blue-700'; ?>" href="<?php echo esc_url($search_url('news', $i)); ?>" <?php echo $i === $page ? 'aria-current="page"' : ''; ?>><?php echo esc_html((string) $i); ?></a>
                                <?php endfor; ?>
                            </nav>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>
                <?php if ($counts['case'] > 0 && in_array($type, ['', 'case'], true)) : ?>
                    <section>
                        <?php $section_head((string) ($ui['tabCases'] ?? 'Cases'), $counts['case'], 'case', count($case_query->posts)); ?>
                        <div class="grid gap-3 lg:grid-cols-2 lg:gap-4">
                            <?php foreach ($case_query->posts as $post_item) : $post_result($post_item); endforeach; ?>
                        </div>
                        <?php if ($type === 'case' && $case_query->max_num_pages > 1) : ?>
                            <nav class="mt-8 flex flex-wrap items-center justify-center gap-2" aria-label="<?php echo esc_attr($ui['paginationLabel'] ?? 'Search pagination'); ?>">
                                <?php for ($i = 1; $i <= (int) $case_query->max_num_pages; $i++) : ?>
                                    <a class="inline-flex h-10 min-w-10 items-center justify-center rounded-sm border px-3 text-sm font-black tabular-nums transition <?php echo $i === $page ? 'border-navy bg-navy text-white' : 'border-[#D8DEE8] bg-white text-ink hover:border-blue-300 hover:text-blue-700'; ?>" href="<?php echo esc_url($search_url('case', $i)); ?>" <?php echo $i === $page ? 'aria-current="page"' : ''; ?>><?php echo esc_html((string) $i); ?></a>
                                <?php endfor; ?>
                            </nav>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($query !== '') : ?>
            <aside class="mt-10 flex flex-col gap-5 rounded border border-line bg-white px-5 py-6 shadow-card sm:px-8 md:mt-12 md:flex-row md:items-center md:justify-between xl:px-12">
                <div>
                    <h2 class="text-lg font-black text-ink md:text-xl"><?php echo esc_html($ui['helpTitle'] ?? 'Not sure which crane fits?'); ?></h2>
                    <p class="mt-1.5 max-w-2xl text-sm font-semibold leading-6 text-slate-600"><?php echo esc_html($ui['helpText'] ?? ''); ?></p>
                </div>
                <button class="inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-sm bg-navy px-6 text-sm font-black text-white transition hover:bg-[#0b2d5b]" type="button" data-inquiry-modal-open data-inquiry-source="搜索页"><?php echo esc_html($ui['helpButton'] ?? 'Ask an engineer'); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></button>
            </aside>
        <?php endif; ?>
    </div>
</section>

<?php get_footer(); ?>
