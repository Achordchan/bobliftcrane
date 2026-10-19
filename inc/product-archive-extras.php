<?php
/**
 * Product archive, below the listing: with one category picked, the range compared model by model and the articles
 * written about it; on every view, the factory, test bay and shipping pages. Everything comes from published products,
 * posts and pages, so nothing here is written by hand. Strings translate through inc/translations/product-archive.php.
 */

/** Title pattern for the articles about each product family, matched against the English title of each post. */
function boblift_archive_article_patterns(): array
{
    $truck = '/truck[- ]mounted|boom truck|truck crane|knuckle boom crane|telescopic (?:boom )?crane/i';
    $marine = '/marine|deck crane|offshore|vessel|\bship/i';
    return [
        'truck-mounted-crane' => $truck,
        'knuckle-boom-truck-mounted-cranes' => $truck,
        'telescopic-boom-truck-mounted-cranes' => $truck,
        'marine-crane' => $marine,
        'knuckle-boom-marine-cranes' => $marine,
        'telescopic-boom-marine-cranes' => $marine,
        'pickup-cranes' => '/pick-?up/i',
        'forklift-fly-jib-cranes' => '/jib/i',
        'aerial-work-platform' => '/aerial|work platform/i',
        'truck-mounted-aerial-work-platform' => '/aerial|work platform/i',
        'spider-cranes' => '/spider/i',
        'timber-cranes' => '/timber|forestry|log(?:ging)? crane/i',
        'tail-lift' => '/tail ?lift/i',
    ];
}

/** Up to $limit published posts in this page's language whose English title matches the family's pattern, newest first. */
function boblift_archive_family_articles(string $family, int $limit = 3): array
{
    $pattern = boblift_archive_article_patterns()[$family] ?? '';
    if ($pattern === '') {
        return [];
    }
    // IDs and English titles only (one query each, cached for an hour per language); full posts are loaded for the matches alone.
    static $titles = null;
    if ($titles === null) {
        $language = function_exists('boblift_current_language_slug') ? boblift_current_language_slug() : '';
        $cache_key = 'boblift_archive_articles_' . ($language !== '' ? $language : 'default') . '_' . (int) get_option('boblift_archive_articles_version', 0);
        $titles = get_transient($cache_key);
        if (!is_array($titles)) {
            // Polylang filters by language only with filters on; name the page's language as well.
            $args = ['post_type' => 'post', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'date', 'order' => 'DESC', 'fields' => 'ids', 'suppress_filters' => false];
            if ($language !== '') {
                $args['lang'] = $language;
            }
            $ids = array_map('intval', get_posts($args));
            $english = [];
            foreach ($ids as $id) {
                $english[$id] = function_exists('pll_get_post') ? ((int) pll_get_post($id, 'en') ?: $id) : $id;
            }
            $rows = [];
            if ($english) {
                global $wpdb;
                $source_ids = array_values(array_unique($english));
                $placeholders = implode(',', array_fill(0, count($source_ids), '%d'));
                $rows = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title FROM {$wpdb->posts} WHERE ID IN ($placeholders)", $source_ids), OBJECT_K); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- placeholders only.
            }
            $titles = [];
            foreach ($english as $id => $source_id) {
                $titles[$id] = isset($rows[$source_id]) ? (string) $rows[$source_id]->post_title : '';
            }
            set_transient($cache_key, $titles, HOUR_IN_SECONDS);
        }
    }
    $found = [];
    foreach ($titles as $id => $title) {
        if ($title !== '' && preg_match($pattern, $title)) {
            $found[] = (int) $id;
            if (count($found) >= $limit) {
                break;
            }
        }
    }
    return $found ? get_posts(['post_type' => 'post', 'post__in' => $found, 'orderby' => 'post__in', 'numberposts' => $limit, 'lang' => '']) : [];
}

/** Any post saved, trashed or deleted starts a new cache of matched titles. */
function boblift_archive_articles_flush(int $post_id): void
{
    if (get_post_type($post_id) === 'post') {
        update_option('boblift_archive_articles_version', time(), false);
    }
}
add_action('save_post', 'boblift_archive_articles_flush');
add_action('deleted_post', 'boblift_archive_articles_flush');

/** The term behind a category slug of this page's language, with its English (source) slug. */
function boblift_archive_term(string $slug): ?array
{
    $term = get_term_by('slug', $slug, 'product_category');
    if (!$term instanceof WP_Term) {
        return null;
    }
    return ['term' => $term, 'family' => boblift_source_term_slug($term)];
}

/**
 * The range as a spec sheet: one column per model (photo, model, name), one row per published figure, so a range of
 * twelve models stays five rows tall and scrolls sideways with the labels pinned.
 */
function boblift_render_archive_compare(WP_Term $term): void
{
    $t = static fn(string $text): string => boblift_localized_static_text($text);
    $models = boblift_product_range_models($term);
    if (count($models) < 2) {
        return;
    }
    // Moments are entered as "13 Ton.m" on some products and as a bare "2.2" on others: show them all as t·m.
    foreach ($models as $index => $item) {
        $moment = trim((string) $item['moment']);
        $moment = (string) preg_replace('/\s*ton\.?\s*m$/i', ' t·m', $moment);
        $models[$index]['moment'] = preg_match('/^\d+(?:\.\d+)?$/', $moment) ? $moment . ' t·m' : $moment;
        $models[$index]['weight'] = (string) preg_replace('/(\d)\s*kg$/i', '$1 kg', trim((string) $item['weight']));
        $models[$index]['capacity_label'] = $item['capacity'] > 0 ? boblift_guide_number($item['capacity']) . ' t' : '';
        $thumbnail = get_the_post_thumbnail_url($item['id'], 'medium');
        $models[$index]['image'] = is_string($thumbnail) && $thumbnail !== '' ? $thumbnail : boblift_post_thumbnail_url(get_post($item['id']), boblift_asset('images/products/crane-accessories.png'));
    }
    // A row shows only when at least one model has the figure.
    $rows = array_filter([
        'capacity_label' => $t('Capacity'),
        'moment' => $t('Max lifting moment'),
        'weight' => $t('Self weight'),
        'sections_label' => $t('Boom sections'),
    ], static fn(string $label, string $key): bool => (bool) array_filter(array_column($models, $key)), ARRAY_FILTER_USE_BOTH);
    $id = 'compare-' . $term->slug;
    $label_cell = 'sticky start-0 z-10 w-[132px] min-w-[132px] border-e border-[#E5E7EB] bg-[#F8FAFC] px-4 text-start text-[13px] font-semibold text-slate-500 sm:w-[180px] sm:min-w-[180px] sm:px-5';
    ?>
    <section class="mt-14 lg:mt-20" aria-labelledby="<?php echo esc_attr($id); ?>">
        <div class="grid gap-4 lg:grid-cols-12 lg:items-end lg:gap-8">
            <div class="lg:col-span-7">
                <p class="section-label"><?php echo esc_html(str_replace('{range}', $term->name, $t('Published figures · {range}'))); ?></p>
                <h2 id="<?php echo esc_attr($id); ?>" class="mt-3 text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em] text-ink"><?php echo esc_html($t('Compare the range')); ?></h2>
            </div>
            <p class="max-w-[46ch] text-sm leading-6 text-slate-600 lg:col-span-5 lg:justify-self-end"><?php echo esc_html($t('Figures from the BOB-LIFT technical sheets. Confirm the configuration for your truck or vessel with our team.')); ?></p>
        </div>
        <div class="mt-6 overflow-x-auto overscroll-x-contain rounded border border-[#E5E7EB] bg-white lg:mt-8" tabindex="0" role="region" aria-labelledby="<?php echo esc_attr($id); ?>">
            <table class="w-full border-separate border-spacing-0 text-sm">
                <thead>
                    <tr>
                        <th class="<?php echo esc_attr($label_cell); ?> border-b-2 border-b-ink align-bottom pb-4" scope="col"><?php echo esc_html($t('Model')); ?></th>
                        <?php foreach ($models as $item) : ?>
                            <th class="min-w-[164px] border-b-2 border-b-ink px-4 pb-4 pt-5 text-start align-bottom font-normal" scope="col">
                                <a class="group block" href="<?php echo esc_url($item['url']); ?>">
                                    <span class="flex aspect-[4/3] items-center justify-center overflow-hidden rounded-sm bg-[#F8FAFC]"><img class="h-full w-full object-contain transition duration-500 group-hover:scale-[1.04]" src="<?php echo esc_url($item['image']); ?>" alt="" loading="lazy" decoding="async"></span>
                                    <span class="mt-3 flex items-center gap-1 text-base font-black text-ink transition group-hover:text-blue-700"><?php echo esc_html($item['model']); ?><?php echo boblift_icon('arrow-up-right', 'h-3.5 w-3.5 shrink-0 text-slate-400 transition group-hover:text-blue-700 rtl:-scale-x-100'); ?></span>
                                    <span class="mt-1 line-clamp-2 block text-xs leading-5 text-slate-500"><?php echo esc_html((string) $item['title']); ?></span>
                                </a>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $key => $label) : ?>
                        <tr class="group/row">
                            <th class="<?php echo esc_attr($label_cell); ?> border-b py-3.5" scope="row"><?php echo esc_html($label); ?></th>
                            <?php foreach ($models as $item) : ?>
                                <td class="whitespace-nowrap border-b border-[#E5E7EB] px-4 py-3.5 font-semibold text-ink [font-variant-numeric:tabular-nums] group-last/row:border-b-0"><?php echo esc_html((string) ($item[$key] ?: '–')); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php
}

function boblift_render_archive_articles(string $family, string $slug): void
{
    $posts = boblift_archive_family_articles($family);
    if (!$posts) {
        return;
    }
    $t = static fn(string $text): string => boblift_localized_static_text($text);
    $article_ui = boblift_site_ui('newsArticle');
    $single = count($posts) === 1;
    ?>
    <section class="mt-14 lg:mt-20" aria-labelledby="articles-<?php echo esc_attr($slug); ?>">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="section-label"><?php echo esc_html($t('Guides and news')); ?></p>
                <h2 id="articles-<?php echo esc_attr($slug); ?>" class="mt-3 text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em] text-ink"><?php echo esc_html($t('Read before you buy')); ?></h2>
            </div>
            <a class="inline-flex shrink-0 items-center gap-2 text-sm font-bold text-navy transition hover:text-blue-700" href="<?php echo esc_url(boblift_url('/news')); ?>"><?php echo esc_html((string) ($article_ui['relatedLink'] ?? 'All news')); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4 rtl:-scale-x-100'); ?></a>
        </div>
        <div class="mt-6 grid gap-5 lg:mt-8 <?php echo $single ? '' : (count($posts) === 2 ? 'md:grid-cols-2' : 'md:grid-cols-3'); ?> xl:gap-6">
            <?php foreach ($posts as $post) : $excerpt = function_exists('boblift_article_card_excerpt') ? boblift_article_card_excerpt((string) get_the_excerpt($post), (string) get_the_title($post)) : ''; ?>
                <a class="group grid overflow-hidden rounded border border-[#E5E7EB] bg-white transition hover:border-blue-200 <?php echo $single ? 'md:grid-cols-12' : ''; ?>" href="<?php echo esc_url(get_permalink($post)); ?>">
                    <span class="relative block aspect-[16/9] overflow-hidden bg-mist <?php echo $single ? 'md:col-span-5 md:aspect-auto md:min-h-[280px]' : ''; ?>"><img class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]" src="<?php echo esc_url(boblift_post_thumbnail_url($post, boblift_asset('images/page-heroes/factory-floor.webp'))); ?>" alt="" loading="lazy" decoding="async"></span>
                    <span class="flex flex-col p-5 <?php echo $single ? 'md:col-span-7 md:justify-center md:p-10' : ''; ?>">
                        <time class="text-xs font-bold text-slate-500" datetime="<?php echo esc_attr(get_the_date('Y-m-d', $post)); ?>"><?php echo esc_html(get_the_date('Y-m-d', $post)); ?></time>
                        <span class="mt-2 line-clamp-2 font-black leading-snug text-ink transition group-hover:text-blue-700 <?php echo $single ? 'text-[clamp(1.25rem,1.8vw,1.75rem)]' : 'text-lg'; ?>"><?php echo esc_html(get_the_title($post)); ?></span>
                        <?php if ($excerpt !== '') : ?><span class="mt-3 line-clamp-2 text-sm leading-6 text-slate-600"><?php echo esc_html($excerpt); ?></span><?php endif; ?>
                        <span class="mt-auto inline-flex items-center gap-2 pt-5 text-sm font-bold text-navy transition group-hover:text-blue-700"><?php echo esc_html($t('Read the article')); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4 transition group-hover:translate-x-0.5 rtl:-scale-x-100 rtl:group-hover:-translate-x-0.5'); ?></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}

/**
 * How a crane is made, from the factory tour, test bay and truck mounting pages: a headline and numbered steps on the
 * left, the photo of each step on the right in two staggered columns. Titles and captions are those pages' own copy.
 */
function boblift_render_archive_made(): void
{
    $t = static fn(string $text): string => boblift_localized_static_text($text);
    $tour = boblift_site_ui('factoryTour');
    $quality = boblift_site_ui('qualityTesting');
    $mounting = boblift_site_ui('mountingShipping');
    $by_key = static function (array $items, string $key): array {
        foreach ($items as $item) {
            if (is_array($item) && ($item['key'] ?? '') === $key) {
                return $item;
            }
        }
        return [];
    };
    $candidates = [
        [$by_key((array) ($tour['stops'] ?? []), 'assembly'), 'factory/hall-knuckle-row', '/factory-tour.html'],
        [$by_key((array) ($tour['stops'] ?? []), 'parts'), 'factory/hall-hydraulics', '/factory-tour.html'],
        [(array) ($quality['steps'][1] ?? []), 'factory/hall-crane-test-bay', '/quality-testing.html'],
        [$by_key((array) ($mounting['mountSteps'] ?? []), 'lift'), 'mounting/lift-crane-hoist', '/mounting-shipping.html'],
        [$by_key((array) ($mounting['packSteps'] ?? []), 'wrap'), 'mounting/pack-wrapped-row', '/mounting-shipping.html'],
        [$by_key((array) ($mounting['packSteps'] ?? []), 'load'), 'mounting/load-hoist-container', '/mounting-shipping.html'],
    ];
    $steps = [];
    foreach ($candidates as [$copy, $image, $path]) {
        $title = trim((string) ($copy['title'] ?? ''));
        if ($title !== '') {
            $steps[] = ['title' => $title, 'text' => (string) ($copy['text'] ?? ''), 'image' => boblift_image_src('images/' . $image . '.webp'), 'url' => boblift_url($path)];
        }
    }
    if (!$steps) {
        return;
    }
    $total = str_pad((string) count($steps), 2, '0', STR_PAD_LEFT);
    $number = static fn(int $index): string => str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
    ?>
    <section class="mt-16 border-t border-[#E5E7EB] pt-14 lg:mt-24 lg:pt-20" aria-labelledby="archive-made-title">
        <div class="grid gap-10 lg:grid-cols-12 lg:gap-12">
            <div class="lg:col-span-4">
                <div class="lg:sticky lg:top-[calc(var(--boblift-header-h)+1.25rem)]">
                    <p class="section-label"><?php echo esc_html($t('How our cranes are made')); ?></p>
                    <h2 id="archive-made-title" class="mt-4 text-[clamp(2.5rem,4.4vw,4.75rem)] font-black leading-[0.98] tracking-[-0.025em] text-ink"><?php echo esc_html($t('From steel')); ?><br><span class="text-blue-700"><?php echo esc_html($t('to shipment.')); ?></span></h2>
                    <p class="mt-6 max-w-[40ch] text-base leading-7 text-slate-600"><?php echo esc_html($t('Photographed at our factory in Xuzhou, Jiangsu: the steps a crane goes through before it leaves.')); ?></p>
                    <ol class="mt-8 hidden border-t border-[#D9DEE7] lg:block" data-scroll-steps>
                        <?php foreach ($steps as $index => $step) : ?>
                            <li><a class="group flex items-baseline gap-4 border-b border-[#D9DEE7] py-3 text-slate-400 transition hover:text-ink aria-[current=true]:border-b-blue-700 aria-[current=true]:text-ink [&[aria-current=true]>span:first-child]:text-blue-700" href="#archive-made-<?php echo esc_attr($number($index)); ?>" aria-current="<?php echo $index === 0 ? 'true' : 'false'; ?>"><span class="text-xs font-bold text-slate-400 [font-variant-numeric:tabular-nums]"><?php echo esc_html($number($index)); ?></span><span class="text-lg font-black leading-snug"><?php echo esc_html($step['title']); ?></span></a></li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            </div>
            <?php // Two columns, every second photo set lower, so they read as a staggered wall rather than a grid of cards. ?>
            <div class="grid gap-8 sm:grid-cols-2 sm:gap-x-6 sm:gap-y-10 sm:pb-24 lg:col-span-8 xl:gap-x-8 sm:[&>*:nth-child(even)]:translate-y-24">
                <?php foreach ($steps as $index => $step) : ?>
                    <figure id="archive-made-<?php echo esc_attr($number($index)); ?>" class="scroll-mt-5">
                        <a class="group block overflow-hidden rounded-sm bg-mist" href="<?php echo esc_url($step['url']); ?>"><img class="aspect-[4/3] h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]" src="<?php echo esc_url($step['image']); ?>" alt="<?php echo esc_attr($step['title']); ?>" loading="lazy" decoding="async"></a>
                        <figcaption class="mt-4 grid grid-cols-[auto_1fr] gap-x-4">
                            <span class="pt-1.5 text-xs font-bold text-blue-700 [font-variant-numeric:tabular-nums]"><?php echo esc_html($number($index) . ' / ' . $total); ?></span>
                            <span class="text-xl font-black leading-snug text-ink"><?php echo esc_html($step['title']); ?></span>
                            <span class="col-start-2 mt-1.5 text-sm leading-6 text-slate-600"><?php echo esc_html($step['text']); ?></span>
                        </figcaption>
                    </figure>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
}

/**
 * Everything below the listing that depends on the category: one block per category, shown by the archive script
 * (like the category intros) when that category alone is picked.
 */
function boblift_render_archive_extras(array $categories, string $initial_category): void
{
    $found = [];
    foreach ($categories as $category) {
        $slug = (string) ($category['slug'] ?? '');
        $term = $slug !== '' ? boblift_archive_term($slug) : null;
        if ($term) {
            $found[$slug] = $term;
        }
    }
    // The comparison sits right under the listing, the factory steps next, and the articles close the page.
    $blocks = static function (callable $render) use ($found, $initial_category): void {
        foreach ($found as $slug => $term) {
            ob_start();
            $render($term, (string) $slug);
            $html = trim((string) ob_get_clean());
            if ($html !== '') {
                printf('<div data-category-extra="%s"%s>%s</div>', esc_attr((string) $slug), $slug === $initial_category ? '' : ' hidden', $html); // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts.
            }
        }
    };
    $blocks(static fn(array $term) => boblift_render_archive_compare($term['term']));
    boblift_render_archive_made();
    $blocks(static fn(array $term, string $slug) => boblift_render_archive_articles($term['family'], $slug));
}

/** Product page: up to three articles about the product's family, in the page's card style, after the related models. */
function boblift_render_product_articles(WP_Term $term): void
{
    $posts = boblift_archive_family_articles(boblift_source_term_slug($term));
    if (!$posts) {
        return;
    }
    $t = static fn(string $text): string => boblift_localized_static_text($text);
    $article_ui = boblift_site_ui('newsArticle');
    // One article lies beside its photo, so it does not sit alone in a three-column row.
    $single = count($posts) === 1;
    ?>
    <section id="articles" class="scroll-mt-24 overflow-hidden rounded border border-[#DDE7F2] bg-white shadow-card" aria-labelledby="articles-title">
        <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-3 p-6 pb-0 md:p-8 md:pb-0">
            <div>
                <p class="boblift-kicker"><?php echo esc_html($t('Guides and news')); ?></p>
                <h2 id="articles-title" class="mt-3 text-[clamp(1.6rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em] text-[#071f42]"><?php echo esc_html($t('Read before you buy')); ?></h2>
            </div>
            <a class="inline-flex items-center gap-1.5 text-sm font-black text-blue-700 transition hover:text-[#071f42]" href="<?php echo esc_url(boblift_url('/news')); ?>"><?php echo esc_html((string) ($article_ui['relatedLink'] ?? 'All news')); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4 rtl:rotate-180'); ?></a>
        </div>
        <ul class="grid gap-x-5 gap-y-6 p-6 md:p-8 <?php echo $single ? '' : 'sm:grid-cols-2 lg:grid-cols-3'; ?>">
            <?php foreach ($posts as $post) : ?>
                <li class="min-w-0">
                    <a class="group <?php echo $single ? 'grid items-center gap-x-8 gap-y-3 sm:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]' : 'block'; ?>" href="<?php echo esc_url(get_permalink($post)); ?>">
                        <span class="block aspect-[16/9] overflow-hidden rounded-sm bg-[#EDF1F5]"><img class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]" src="<?php echo esc_url(boblift_post_thumbnail_url($post, boblift_asset('images/page-heroes/factory-floor.webp'))); ?>" alt="" loading="lazy" decoding="async"></span>
                        <span class="block min-w-0">
                            <time class="mt-3 block text-xs font-bold text-slate-500 <?php echo $single ? 'sm:mt-0' : ''; ?>" datetime="<?php echo esc_attr(get_the_date('Y-m-d', $post)); ?>"><?php echo esc_html(get_the_date('Y-m-d', $post)); ?></time>
                            <span class="mt-1.5 line-clamp-2 block font-black leading-snug text-[#071f42] transition group-hover:text-blue-700 <?php echo $single ? 'text-[clamp(1.125rem,1.6vw,1.5rem)]' : 'text-base'; ?>"><?php echo esc_html(get_the_title($post)); ?></span>
                            <?php if ($single) : ?><span class="mt-4 inline-flex items-center gap-2 text-sm font-black text-blue-700"><?php echo esc_html($t('Read the article')); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4 rtl:rotate-180'); ?></span><?php endif; ?>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php
}
