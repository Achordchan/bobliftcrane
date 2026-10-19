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
    static $posts = null;
    if ($posts === null) {
        // Polylang filters by language only with filters on; name the page's language as well.
        $language = function_exists('boblift_current_language_slug') ? boblift_current_language_slug() : '';
        $args = ['post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 200, 'orderby' => 'date', 'order' => 'DESC', 'suppress_filters' => false];
        if ($language !== '') {
            $args['lang'] = $language;
        }
        $posts = get_posts($args);
    }
    $found = [];
    foreach ($posts as $post) {
        $english_id = function_exists('pll_get_post') ? (int) pll_get_post($post->ID, 'en') : 0;
        if (preg_match($pattern, (string) get_the_title($english_id ?: $post->ID))) {
            $found[] = $post;
            if (count($found) >= $limit) {
                break;
            }
        }
    }
    return $found;
}

/** The term behind a category slug of this page's language, with its English (source) slug. */
function boblift_archive_term(string $slug): ?array
{
    $term = get_term_by('slug', $slug, 'product_category');
    if (!$term instanceof WP_Term) {
        return null;
    }
    return ['term' => $term, 'family' => boblift_source_term_slug($term)];
}

function boblift_render_archive_compare(WP_Term $term): void
{
    $t = static fn(string $text): string => boblift_localized_static_text($text);
    $models = boblift_product_range_models($term);
    // Moments are entered as "13 Ton.m" on some products and as a bare "2.2" on others: show them all as t·m.
    foreach ($models as $index => $item) {
        $moment = trim((string) $item['moment']);
        $moment = (string) preg_replace('/\s*ton\.?\s*m$/i', ' t·m', $moment);
        $models[$index]['moment'] = preg_match('/^\d+(?:\.\d+)?$/', $moment) ? $moment . ' t·m' : $moment;
        $models[$index]['weight'] = (string) preg_replace('/(\d)\s*kg$/i', '$1 kg', trim((string) $item['weight']));
    }
    if (count($models) < 2) {
        return;
    }
    // A column shows only when at least one model has the figure.
    $columns = array_filter([
        'moment' => $t('Max lifting moment'),
        'weight' => $t('Self weight'),
        'sections_label' => $t('Boom sections'),
    ], static fn(string $label, string $key): bool => (bool) array_filter(array_column($models, $key)), ARRAY_FILTER_USE_BOTH);
    ?>
    <section class="mt-12 lg:mt-16" aria-labelledby="compare-<?php echo esc_attr($term->slug); ?>">
        <p class="section-label"><?php echo esc_html($t('Compare the range')); ?></p>
        <h2 id="compare-<?php echo esc_attr($term->slug); ?>" class="mt-3 text-[clamp(1.4rem,2vw,2rem)] font-black leading-tight tracking-[-0.01em] text-[#0B1B33]"><?php echo esc_html(str_replace('{range}', $term->name, $t('{range}: every model side by side'))); ?></h2>
        <div class="mt-6 overflow-x-auto rounded border border-[#E5E7EB] bg-white shadow-card">
            <table class="w-full min-w-[560px] border-collapse text-start text-sm">
                <thead class="bg-[#F8FAFC] text-xs font-black uppercase tracking-[0.08em] text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5 text-start" scope="col"><?php echo esc_html($t('Model')); ?></th>
                        <th class="px-5 py-3.5 text-start" scope="col"><?php echo esc_html($t('Capacity')); ?></th>
                        <?php foreach ($columns as $label) : ?><th class="px-5 py-3.5 text-start" scope="col"><?php echo esc_html($label); ?></th><?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E5E7EB] text-slate-700">
                    <?php foreach ($models as $item) : ?>
                        <tr class="transition hover:bg-[#F8FAFC]">
                            <th class="px-5 py-3.5 text-start font-black text-[#0B1B33]" scope="row"><a class="inline-flex items-center gap-1.5 transition hover:text-blue-700" href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['model']); ?><?php echo boblift_icon('arrow-right', 'h-3.5 w-3.5 text-slate-400 rtl:-scale-x-100'); ?></a></th>
                            <td class="whitespace-nowrap px-5 py-3.5"><?php echo esc_html($item['capacity'] > 0 ? boblift_guide_number($item['capacity']) . ' t' : '–'); ?></td>
                            <?php foreach (array_keys($columns) as $key) : ?><td class="whitespace-nowrap px-5 py-3.5"><?php echo esc_html((string) ($item[$key] ?: '–')); ?></td><?php endforeach; ?>
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
    ?>
    <section class="mt-12 lg:mt-16" aria-labelledby="articles-<?php echo esc_attr($slug); ?>">
        <p class="section-label"><?php echo esc_html($t('Guides and news')); ?></p>
        <h2 id="articles-<?php echo esc_attr($slug); ?>" class="mt-3 text-[clamp(1.4rem,2vw,2rem)] font-black leading-tight tracking-[-0.01em] text-[#0B1B33]"><?php echo esc_html($t('Read before you buy')); ?></h2>
        <div class="mt-6 grid gap-5 md:grid-cols-3 xl:gap-6">
            <?php foreach ($posts as $post) : ?>
                <a class="group overflow-hidden rounded border border-[#E5E7EB] bg-white shadow-card transition hover:-translate-y-0.5 hover:border-blue-200" href="<?php echo esc_url(get_permalink($post)); ?>">
                    <span class="block aspect-[16/10] overflow-hidden bg-mist"><img class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]" src="<?php echo esc_url(boblift_post_thumbnail_url($post, boblift_asset('images/page-heroes/factory-floor.webp'))); ?>" alt="" loading="lazy" decoding="async"></span>
                    <span class="block p-5">
                        <time class="text-xs font-bold text-slate-500" datetime="<?php echo esc_attr(get_the_date('Y-m-d', $post)); ?>"><?php echo esc_html(get_the_date('', $post)); ?></time>
                        <span class="mt-2 line-clamp-3 block text-base font-black leading-snug text-[#0B1B33] transition group-hover:text-blue-700"><?php echo esc_html(get_the_title($post)); ?></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}

/** Factory tour, test bay and truck mounting pages, as three picture links (titles and text from those pages' own settings). */
function boblift_render_archive_made(): void
{
    $t = static fn(string $text): string => boblift_localized_static_text($text);
    $pages = [
        ['ui' => 'factoryTour', 'path' => '/factory-tour.html', 'image' => 'images/factory/hall-assembly-aisle.webp'],
        ['ui' => 'qualityTesting', 'path' => '/quality-testing.html', 'image' => 'images/factory/hall-crane-test-bay.webp'],
        ['ui' => 'mountingShipping', 'path' => '/mounting-shipping.html', 'image' => 'images/mounting/finished-truck-side.webp'],
    ];
    ?>
    <section class="mt-12 border-t border-[#E5E7EB] pt-12 lg:mt-16 lg:pt-16" aria-labelledby="archive-made-title">
        <p class="section-label"><?php echo esc_html($t('From steel to shipment')); ?></p>
        <h2 id="archive-made-title" class="mt-3 text-[clamp(1.4rem,2vw,2rem)] font-black leading-tight tracking-[-0.01em] text-[#0B1B33]"><?php echo esc_html($t('See where your crane is built, tested and shipped.')); ?></h2>
        <div class="mt-6 grid gap-5 md:grid-cols-3 xl:gap-6">
            <?php foreach ($pages as $page) : $ui = boblift_site_ui($page['ui']); ?>
                <a class="group overflow-hidden rounded border border-[#E5E7EB] bg-white shadow-card transition hover:-translate-y-0.5 hover:border-blue-200" href="<?php echo esc_url(boblift_url($page['path'])); ?>">
                    <span class="block aspect-[16/10] overflow-hidden bg-mist"><img class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]" src="<?php echo esc_url(boblift_image_src($page['image'])); ?>" alt="<?php echo esc_attr((string) ($ui['heroImageAlt'] ?? '')); ?>" loading="lazy" decoding="async"></span>
                    <span class="block p-5">
                        <span class="flex items-center justify-between gap-3 text-base font-black text-[#0B1B33] transition group-hover:text-blue-700"><?php echo esc_html((string) ($ui['heroLabel'] ?? '')); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4 shrink-0 rtl:-scale-x-100'); ?></span>
                        <span class="mt-2 line-clamp-3 block text-sm font-normal leading-6 text-slate-600"><?php echo esc_html((string) ($ui['heroText'] ?? '')); ?></span>
                    </span>
                </a>
            <?php endforeach; ?>
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
    foreach ($categories as $category) {
        $slug = (string) ($category['slug'] ?? '');
        $found = $slug !== '' ? boblift_archive_term($slug) : null;
        if (!$found) {
            continue;
        }
        ob_start();
        boblift_render_archive_compare($found['term']);
        boblift_render_archive_articles($found['family'], $slug);
        $html = trim((string) ob_get_clean());
        if ($html === '') {
            continue;
        }
        printf('<div data-category-extra="%s"%s>%s</div>', esc_attr($slug), $slug === $initial_category ? '' : ' hidden', $html); // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts above.
    }
    boblift_render_archive_made();
}
