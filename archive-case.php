<?php
get_header();
$cases = boblift_cases();
$ui = boblift_site_ui('casesPage');
$scene_label = $ui['sceneCasesLabel'] ?? 'Application Cases';
$feedback_label = $ui['customerFeedbackLabel'] ?? 'Customer Feedback';
$all_countries_label = $ui['allCountriesLabel'] ?? 'All countries';
$all_products_label = $ui['allProductsLabel'] ?? 'All products';
$reset_label = $ui['resetFiltersLabel'] ?? 'Reset filters';
$empty_title = $ui['emptyTitle'] ?? 'No matching cases found';
$empty_text = $ui['emptyText'] ?? 'Try another country or product to find a closer reference.';
$case_search_country_label = $ui['searchCountryLabel'] ?? 'Search country';
$case_search_product_label = $ui['searchProductLabel'] ?? 'Search product';
$case_no_results_label = $ui['noResultsLabel'] ?? 'No results';
$case_close_feedback_label = $ui['closeFeedbackMediaLabel'] ?? 'Close feedback media';
$case_tabs_aria_label = $ui['tabsAriaLabel'] ?? 'Case sections';
$feedback_items = function_exists('boblift_feedback_items') ? boblift_feedback_items() : [];
$country_options = [];
$product_options = [];
foreach ($feedback_items as $feedback) {
    if ($feedback['country'] !== '') {
        $country_options[$feedback['country']] = $feedback['country'];
    }
    if ($feedback['model'] !== '') {
        $product_options[$feedback['model']] = $feedback['model'];
    }
}
foreach ($cases as $case) {
    $country = trim((string) ($case['country'] ?? ''));
    $product = trim((string) ($case['product_name'] ?? ''));
    if ($country !== '') {
        $country_options[$country] = $country;
    }
    if ($product !== '') {
        $product_options[$product] = $product;
    }
}
natcasesort($country_options);
natcasesort($product_options);
$render_case_filter = static function (string $id, string $filter, string $all_label, array $options) use ($case_search_country_label, $case_search_product_label, $case_no_results_label): void {
    $search_label = $filter === 'country' ? $case_search_country_label : $case_search_product_label;
    ?>
    <div class="boblift-cases-filter" data-cases-custom-select data-cases-filter-target="<?php echo esc_attr($filter); ?>">
        <label class="sr-only" for="<?php echo esc_attr($id); ?>"><?php echo esc_html($all_label); ?></label>
        <select id="<?php echo esc_attr($id); ?>" class="boblift-cases-native-select" data-cases-filter="<?php echo esc_attr($filter); ?>" tabindex="-1" aria-hidden="true">
            <option value=""><?php echo esc_html($all_label); ?></option>
            <?php foreach ($options as $option) : ?><option value="<?php echo esc_attr($option); ?>"><?php echo esc_html($option); ?></option><?php endforeach; ?>
        </select>
        <button class="boblift-cases-select-trigger" type="button" data-cases-custom-select-toggle aria-haspopup="listbox" aria-expanded="false">
            <span data-cases-custom-select-label><?php echo esc_html($all_label); ?></span>
            <?php echo boblift_icon('chevron-down', 'h-4 w-4 text-slate-400 transition'); ?>
        </button>
        <div class="boblift-cases-select-menu hidden" role="listbox" data-cases-custom-select-menu>
            <div class="boblift-cases-select-search">
                <input type="search" autocomplete="off" spellcheck="false" placeholder="<?php echo esc_attr($search_label); ?>" data-cases-custom-search>
            </div>
            <div class="boblift-cases-select-options" data-cases-custom-options>
                <button class="boblift-cases-select-option is-active" type="button" role="option" aria-selected="true" data-cases-custom-option data-option-text="<?php echo esc_attr($all_label); ?>" value="">
                    <span><?php echo esc_html($all_label); ?></span>
                    <span class="boblift-cases-select-check"><?php echo boblift_icon('check', 'h-4 w-4'); ?></span>
                </button>
                <?php foreach ($options as $option) : ?>
                    <button class="boblift-cases-select-option" type="button" role="option" aria-selected="false" data-cases-custom-option data-option-text="<?php echo esc_attr($option); ?>" value="<?php echo esc_attr($option); ?>">
                        <span><?php echo esc_html($option); ?></span>
                        <span class="boblift-cases-select-check"><?php echo boblift_icon('check', 'h-4 w-4'); ?></span>
                    </button>
                <?php endforeach; ?>
                <div class="boblift-cases-select-empty hidden" data-cases-custom-empty><?php echo esc_html($case_no_results_label); ?></div>
            </div>
        </div>
    </div>
    <?php
};
?>
<div class="page-shell pt-5 md:pt-8">
    <?php
    boblift_page_hero([
        'label' => $ui['heroLabel'] ?? 'Case Center',
        'title' => $ui['heroTitle'] ?? 'Project stories and customer feedback.',
        'text' => $ui['heroDescription'] ?? 'Browse real working conditions and customer feedback to find a BOB-LIFT reference close to your project.',
        'image' => (string) ($ui['heroImage'] ?? ''),
        'image_alt' => (string) ($ui['heroImageAlt'] ?? 'BOB-LIFT customer cases'),
    ]);
    ?>
</div>

<section id="case-list" class="boblift-cases-center page-shell pb-20 pt-8 md:pt-10 2xl:pb-24" data-cases-root data-cases-empty-title="<?php echo esc_attr($empty_title); ?>">
    <div class="mb-12 flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="boblift-cases-tabs flex flex-wrap gap-4" role="tablist" aria-label="<?php echo esc_attr($case_tabs_aria_label); ?>">
                <button class="boblift-cases-tab is-active" type="button" data-cases-tab-button="scene_case" aria-pressed="true"><?php echo esc_html($scene_label); ?></button>
                <button class="boblift-cases-tab" type="button" data-cases-tab-button="customer_feedback" aria-pressed="false"><?php echo esc_html($feedback_label); ?></button>
            </div>
            <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] gap-3 xl:min-w-[680px]">
                <?php $render_case_filter('case-country-filter', 'country', $all_countries_label, $country_options); ?>
                <?php $render_case_filter('case-product-filter', 'product', $all_products_label, $product_options); ?>
                <button class="boblift-cases-reset-button" type="button" data-cases-reset aria-label="<?php echo esc_attr($reset_label); ?>"><?php echo boblift_icon('rotate-cw', 'h-4 w-4'); ?><span><?php echo esc_html($reset_label); ?></span></button>
            </div>
    </div>

    <div class="boblift-cases-grid grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3" data-cases-grid>
        <?php foreach ($cases as $index => $item) :
            $kind = boblift_normalize_case_kind((string) ($item['case_kind'] ?? 'scene_case'));
            $country = trim((string) ($item['country'] ?? ''));
            $product = trim((string) ($item['product_name'] ?? ''));
            $application = trim((string) ($item['application'] ?? ''));
            $image = boblift_item_image($item);
            $media_type = ($item['feedback_media_type'] ?? 'image') === 'video' ? 'video' : 'image';
            $media_src = boblift_case_media_url($item);
            $feedback_quote = trim((string) ($item['feedback_quote'] ?? ''));
        ?>
            <?php if ($kind === 'customer_feedback') : ?>
                <article class="boblift-case-card group" data-cases-card data-case-kind="customer_feedback" data-country="<?php echo esc_attr($country); ?>" data-product="<?php echo esc_attr($product); ?>">
                    <button class="block h-full w-full text-start" type="button" data-cases-media-open data-media-type="<?php echo esc_attr($media_type); ?>" data-media-src="<?php echo esc_url($media_src); ?>" data-media-title="<?php echo esc_attr($item['title']); ?>">
                        <span class="boblift-case-card-media relative block h-[260px] overflow-hidden rounded bg-[#eef5ff]">
                            <img class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.035]" src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($item['title']); ?>" loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>" decoding="async">
                            <?php if ($media_type === 'video') : ?><span class="absolute inset-0 m-auto grid size-16 place-items-center rounded-sm bg-white/90 text-blue-700 shadow-card"><?php echo boblift_icon('circle-play', 'h-7 w-7'); ?></span><?php endif; ?>
                        </span>
                        <span class="boblift-case-card-content block p-5">
                            <span class="boblift-case-card-meta flex flex-wrap gap-2 text-[11px] font-black uppercase tracking-[0.14em] text-slate-500">
                                <?php if ($country !== '') : ?><span><?php echo esc_html($country); ?></span><?php endif; ?>
                                <?php if ($product !== '') : ?><span><?php echo esc_html($product); ?></span><?php endif; ?>
                            </span>
                            <span class="boblift-case-card-title mt-3 block text-xl font-black leading-tight tracking-[-0.015em] text-ink"><?php echo esc_html($item['title']); ?></span>
                            <span class="boblift-case-card-summary mt-3 block text-sm font-normal leading-6 text-slate-600"><?php echo esc_html($feedback_quote !== '' ? $feedback_quote : $item['summary']); ?></span>
                        </span>
                    </button>
                </article>
            <?php else : ?>
                <article class="boblift-case-card group" data-cases-card data-case-kind="scene_case" data-country="<?php echo esc_attr($country); ?>" data-product="<?php echo esc_attr($product); ?>">
                    <a class="block h-full" href="<?php echo esc_url($item['url']); ?>">
                        <span class="boblift-case-card-media relative block h-[260px] overflow-hidden rounded bg-[#eef5ff] md:h-[300px]">
                            <img class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.035]" src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($item['title']); ?>" loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>" decoding="async">
                        </span>
                        <span class="boblift-case-card-content block p-5">
                            <span class="boblift-case-card-meta flex flex-wrap gap-2 text-[11px] font-black uppercase tracking-[0.14em] text-slate-500">
                                <?php if ($country !== '') : ?><span><?php echo esc_html($country); ?></span><?php endif; ?>
                                <?php if ($product !== '') : ?><span><?php echo esc_html($product); ?></span><?php endif; ?>
                            </span>
                            <span class="boblift-case-card-title mt-3 block text-xl font-black leading-tight tracking-[-0.015em] text-ink"><?php echo esc_html($item['title']); ?></span>
                            <span class="boblift-case-card-summary mt-3 block text-sm font-normal leading-6 text-slate-600"><?php echo esc_html($item['summary']); ?></span>
                            <span class="boblift-case-card-action mt-5 inline-flex items-center gap-2 text-sm font-black text-blue-600"><?php echo esc_html($ui['viewCaseLabel'] ?? 'View Case'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4 transition group-hover:translate-x-1'); ?></span>
                        </span>
                    </a>
                </article>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php foreach ($feedback_items as $feedback) :
            $feedback_title = trim($feedback['model'] . ($feedback['note'] !== '' ? ' ' . $feedback['note'] : ''));
            $feedback_caption = implode(' · ', array_filter([$feedback['model'], $feedback['country']]));
            $feedback_counts = boblift_feedback_counts_text($feedback);
        ?>
            <article class="boblift-case-card group" data-cases-card data-case-kind="customer_feedback" data-country="<?php echo esc_attr($feedback['country']); ?>" data-product="<?php echo esc_attr($feedback['model']); ?>">
                <a class="block h-full" href="<?php echo esc_url($feedback['url']); ?>">
                    <span class="boblift-case-card-media relative block h-[260px] overflow-hidden rounded bg-[#eef5ff]">
                        <?php if ($feedback['cover'] !== '') : ?><img class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.035]" src="<?php echo esc_url($feedback['cover']); ?>" alt="<?php echo esc_attr($feedback_caption); ?>" loading="lazy" decoding="async"><?php endif; ?>
                        <?php if (($feedback['media'][0]['type'] ?? '') === 'video') : ?><span class="absolute inset-0 m-auto grid size-16 place-items-center rounded-sm bg-white/90 text-blue-700 shadow-card"><?php echo boblift_icon('circle-play', 'h-7 w-7'); ?></span><?php endif; ?>
                        <?php if (count($feedback['media']) > 1) : ?><span class="absolute bottom-3 right-3 inline-flex items-center gap-1.5 rounded-sm bg-[#071f42]/80 px-2 py-1 text-xs font-black text-white"><?php echo boblift_icon('images', 'h-3.5 w-3.5'); ?><?php echo esc_html((string) count($feedback['media'])); ?></span><?php endif; ?>
                    </span>
                    <span class="boblift-case-card-content block p-5">
                        <span class="boblift-case-card-meta flex flex-wrap gap-2 text-[11px] font-black uppercase tracking-[0.14em] text-slate-500">
                            <?php if ($feedback['country'] !== '') : ?><span><?php echo esc_html($feedback['country']); ?></span><?php endif; ?>
                        </span>
                        <span class="boblift-case-card-title mt-3 block text-xl font-black leading-tight tracking-[-0.015em] text-ink"><?php echo esc_html($feedback_title); ?></span>
                        <?php if ($feedback['excerpt'] !== '') : ?><span class="boblift-case-card-summary mt-2 line-clamp-2 block text-sm font-normal leading-6 text-slate-600"><?php echo esc_html($feedback['excerpt']); ?></span><?php endif; ?>
                        <?php if ($feedback_counts !== '') : ?><span class="boblift-case-card-summary mt-2 block text-sm font-normal leading-6 text-slate-500"><?php echo esc_html($feedback_counts); ?></span><?php endif; ?>
                    </span>
                </a>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="hidden rounded border border-dashed border-blue-200 bg-blue-50/45 p-8 text-center" data-cases-empty>
        <h2 class="text-2xl font-black tracking-[-0.015em] text-ink"><?php echo esc_html($empty_title); ?></h2>
        <p class="mx-auto mt-3 max-w-[560px] text-sm font-normal leading-7 text-slate-600"><?php echo esc_html($empty_text); ?></p>
        <button class="mt-5 inline-flex h-12 items-center justify-center rounded-sm bg-navy px-6 text-sm font-black text-white" type="button" data-cases-reset><?php echo esc_html($reset_label); ?></button>
    </div>

    <div class="boblift-cases-media-modal fixed inset-0 z-[250] hidden items-center justify-center bg-[#071f42]/70 p-4 backdrop-blur-md" data-cases-modal hidden>
        <button class="absolute inset-0 h-full w-full cursor-default" type="button" data-cases-modal-close aria-label="<?php echo esc_attr($case_close_feedback_label); ?>"></button>
        <div class="relative z-10 w-full max-w-[1120px] overflow-hidden rounded bg-white p-3 shadow-card">
            <div class="flex items-center justify-between gap-4 px-2 pb-3">
                <h2 class="text-base font-black text-ink" data-cases-modal-title></h2>
                <button class="grid size-10 place-items-center rounded-sm bg-[#f3f6fb] text-ink transition hover:bg-navy hover:text-white" type="button" data-cases-modal-close aria-label="<?php echo esc_attr($case_close_feedback_label); ?>"><?php echo boblift_icon('x', 'h-5 w-5'); ?></button>
            </div>
            <div class="relative min-h-[260px] overflow-hidden rounded bg-[#eef5ff] md:min-h-[620px]" data-cases-modal-media></div>
            <div class="hidden items-center justify-between gap-3 px-2 pt-3" data-cases-modal-nav>
                <button class="grid size-10 place-items-center rounded-sm border border-[#DDE7F2] bg-white text-ink transition hover:border-blue-300" type="button" data-cases-modal-prev aria-label="<?php echo esc_attr($ui['previousLabel'] ?? 'Previous'); ?>"><?php echo boblift_icon('chevron-left', 'h-5 w-5'); ?></button>
                <span class="text-sm font-black text-slate-500 [font-variant-numeric:tabular-nums]" data-cases-modal-counter></span>
                <button class="grid size-10 place-items-center rounded-sm border border-[#DDE7F2] bg-white text-ink transition hover:border-blue-300" type="button" data-cases-modal-next aria-label="<?php echo esc_attr($ui['nextLabel'] ?? 'Next'); ?>"><?php echo boblift_icon('chevron-right', 'h-5 w-5'); ?></button>
            </div>
        </div>
    </div>
</section>
<?php get_footer(); ?>
