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
$country_options = [];
$product_options = [];
$scene_count = 0;
$feedback_count = 0;
foreach ($cases as $case) {
    $case_kind = boblift_normalize_case_kind((string) ($case['case_kind'] ?? 'scene_case'));
    if ($case_kind === 'customer_feedback') {
        $feedback_count++;
    } else {
        $scene_count++;
    }
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
<section class="boblift-news-hero">
    <div class="page-shell boblift-news-hero-shell grid items-center gap-7 py-8 md:gap-10 md:py-16 lg:grid-cols-[0.92fr_1.08fr] lg:gap-16 2xl:py-20">
        <div class="max-w-3xl">
            <p class="section-label mb-4 md:mb-5"><?php echo esc_html($ui['heroLabel'] ?? 'Case Center'); ?></p>
            <h1 class="text-[clamp(32px,8.8vw,38px)] font-black leading-[1.04] tracking-[-0.05em] text-navy md:text-[50px] xl:text-[56px] 2xl:text-[64px]"><?php echo esc_html($ui['heroTitle'] ?? 'Project stories and customer feedback.'); ?></h1>
            <p class="mt-4 max-w-2xl text-base font-semibold leading-7 text-slate-600 md:mt-6 md:text-lg md:leading-8 2xl:text-xl 2xl:leading-9"><?php echo esc_html($ui['heroDescription'] ?? 'Browse real working conditions and customer feedback to find a BOB-LIFT reference close to your project.'); ?></p>
            <div class="mt-6 grid grid-cols-2 gap-3 md:mt-8 md:flex md:flex-wrap md:gap-4">
                <div class="boblift-news-proof-chip"><?php echo boblift_icon('building-2', 'h-6 w-6 text-gold md:h-8 md:w-8', '1.8'); ?><span><strong><?php echo esc_html((string) $scene_count); ?></strong><small><?php echo esc_html($scene_label); ?></small></span></div>
                <div class="boblift-news-proof-chip"><?php echo boblift_icon('circle-play', 'h-6 w-6 text-gold md:h-8 md:w-8', '1.8'); ?><span><strong><?php echo esc_html((string) $feedback_count); ?></strong><small><?php echo esc_html($feedback_label); ?></small></span></div>
            </div>
        </div>
        <div class="boblift-news-hero-image">
            <?php if (!empty($ui['heroImage'])) : ?><img class="h-full w-full object-cover" src="<?php echo esc_url(boblift_image_src((string) $ui['heroImage'])); ?>" alt="<?php echo esc_attr((string) ($ui['heroImageAlt'] ?? 'BOB-LIFT customer cases')); ?>" loading="eager" decoding="async" fetchpriority="high"><?php endif; ?>
        </div>
    </div>
</section>

<section id="case-list" class="boblift-cases-center page-shell pb-20 pt-8 md:pt-10 2xl:pb-24" data-cases-root data-cases-empty-title="<?php echo esc_attr($empty_title); ?>">
    <div class="mb-12 flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="boblift-cases-tabs flex flex-wrap gap-4" role="tablist" aria-label="<?php echo esc_attr($case_tabs_aria_label); ?>">
                <button class="boblift-cases-tab is-active" type="button" data-cases-tab-button="scene_case" aria-pressed="true"><?php echo esc_html($scene_label); ?></button>
                <button class="boblift-cases-tab" type="button" data-cases-tab-button="customer_feedback" aria-pressed="false"><?php echo esc_html($feedback_label); ?></button>
            </div>
            <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,1fr)_48px] gap-3 xl:min-w-[680px] xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]">
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
                        <span class="boblift-case-card-media relative block h-[260px] overflow-hidden rounded-[14px] bg-[#eef5ff]">
                            <img class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.035]" src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($item['title']); ?>" loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>" decoding="async">
                            <?php if ($media_type === 'video') : ?><span class="absolute inset-0 m-auto grid size-16 place-items-center rounded-full bg-white/90 text-blue-700 shadow-[0_18px_45px_rgba(8,35,74,0.18)]"><?php echo boblift_icon('circle-play', 'h-7 w-7'); ?></span><?php endif; ?>
                        </span>
                        <span class="boblift-case-card-content block p-5">
                            <span class="boblift-case-card-meta flex flex-wrap gap-2 text-[11px] font-black uppercase tracking-[0.14em] text-slate-500">
                                <?php if ($country !== '') : ?><span><?php echo esc_html($country); ?></span><?php endif; ?>
                                <?php if ($product !== '') : ?><span><?php echo esc_html($product); ?></span><?php endif; ?>
                            </span>
                            <span class="boblift-case-card-title mt-3 block text-xl font-black leading-tight tracking-[-0.04em] text-ink"><?php echo esc_html($item['title']); ?></span>
                            <span class="boblift-case-card-summary mt-3 block text-sm font-semibold leading-6 text-slate-600"><?php echo esc_html($feedback_quote !== '' ? $feedback_quote : $item['summary']); ?></span>
                        </span>
                    </button>
                </article>
            <?php else : ?>
                <article class="boblift-case-card group" data-cases-card data-case-kind="scene_case" data-country="<?php echo esc_attr($country); ?>" data-product="<?php echo esc_attr($product); ?>">
                    <a class="block h-full" href="<?php echo esc_url($item['url']); ?>">
                        <span class="boblift-case-card-media relative block h-[260px] overflow-hidden rounded-[14px] bg-[#eef5ff] md:h-[300px]">
                            <img class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.035]" src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($item['title']); ?>" loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>" decoding="async">
                        </span>
                        <span class="boblift-case-card-content block p-5">
                            <span class="boblift-case-card-meta flex flex-wrap gap-2 text-[11px] font-black uppercase tracking-[0.14em] text-slate-500">
                                <?php if ($country !== '') : ?><span><?php echo esc_html($country); ?></span><?php endif; ?>
                                <?php if ($product !== '') : ?><span><?php echo esc_html($product); ?></span><?php endif; ?>
                            </span>
                            <span class="boblift-case-card-title mt-3 block text-xl font-black leading-tight tracking-[-0.04em] text-ink"><?php echo esc_html($item['title']); ?></span>
                            <span class="boblift-case-card-summary mt-3 block text-sm font-semibold leading-6 text-slate-600"><?php echo esc_html($item['summary']); ?></span>
                            <span class="boblift-case-card-action mt-5 inline-flex items-center gap-2 text-sm font-black text-blue-600"><?php echo esc_html($ui['viewCaseLabel'] ?? 'View Case'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4 transition group-hover:translate-x-1'); ?></span>
                        </span>
                    </a>
                </article>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="hidden rounded-[28px] border border-dashed border-blue-200 bg-blue-50/45 p-8 text-center" data-cases-empty>
        <h2 class="text-2xl font-black tracking-[-0.04em] text-ink"><?php echo esc_html($empty_title); ?></h2>
        <p class="mx-auto mt-3 max-w-[560px] text-sm font-semibold leading-7 text-slate-600"><?php echo esc_html($empty_text); ?></p>
        <button class="mt-5 inline-flex h-12 items-center justify-center rounded-full bg-navy px-6 text-sm font-black text-white" type="button" data-cases-reset><?php echo esc_html($reset_label); ?></button>
    </div>

    <div class="boblift-cases-media-modal fixed inset-0 z-[120] hidden items-center justify-center bg-[#071f42]/70 p-4 backdrop-blur-md" data-cases-modal hidden>
        <button class="absolute inset-0 h-full w-full cursor-default" type="button" data-cases-modal-close aria-label="<?php echo esc_attr($case_close_feedback_label); ?>"></button>
        <div class="relative z-10 w-full max-w-[1120px] overflow-hidden rounded-[30px] bg-white p-3 shadow-[0_34px_110px_rgba(0,0,0,0.28)]">
            <div class="flex items-center justify-between gap-4 px-2 pb-3">
                <h2 class="text-base font-black text-ink" data-cases-modal-title></h2>
                <button class="grid size-10 place-items-center rounded-full bg-[#f3f6fb] text-ink transition hover:bg-navy hover:text-white" type="button" data-cases-modal-close aria-label="<?php echo esc_attr($case_close_feedback_label); ?>"><?php echo boblift_icon('x', 'h-5 w-5'); ?></button>
            </div>
            <div class="relative min-h-[260px] overflow-hidden rounded-[24px] bg-[#eef5ff] md:min-h-[620px]" data-cases-modal-media></div>
        </div>
    </div>
</section>
<?php get_footer(); ?>
