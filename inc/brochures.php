<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Downloadable brochures. The PDFs live in assets/downloads/ and are built from the print brochures
 * (contact block already carries the public phone number). Copy comes from inc/site-ui-sections/brochures.php.
 * "pages" is the PDF page count (each PDF page is a two-page spread, except the spider leaflet).
 */
function boblift_brochure_files(): array
{
    return [
        'truck' => ['file' => 'bob-lift-truck-mounted-cranes-2026.pdf', 'cover' => 'images/brochures/bob-lift-truck-mounted-cranes-2026-cover.webp', 'pages' => 22],
        'marine' => ['file' => 'bob-lift-marine-cranes-2026.pdf', 'cover' => 'images/brochures/bob-lift-marine-cranes-2026-cover.webp', 'pages' => 12],
        'spider' => ['file' => 'bob-lift-spider-cranes-2026.pdf', 'cover' => 'images/brochures/bob-lift-spider-cranes-2026-cover.webp', 'pages' => 2],
    ];
}

/** Brochure family for one category slug (any language), or '' when no brochure covers it. */
function boblift_brochure_family_of_slug(string $slug): string
{
    if ($slug === '') {
        return '';
    }
    $term = function_exists('get_term_by') ? get_term_by('slug', $slug, 'product_category') : false;
    $family = boblift_brochure_family_of_source_slug($term instanceof WP_Term && function_exists('boblift_source_term_slug') ? boblift_source_term_slug($term) : $slug);
    // A subcategory an editor added under a covered category (e.g. heavy-duty under truck cranes) takes its ancestor's brochure.
    if ($family === '' && $term instanceof WP_Term && function_exists('get_ancestors')) {
        foreach (get_ancestors($term->term_id, 'product_category', 'taxonomy') as $ancestor_id) {
            $ancestor = get_term($ancestor_id, 'product_category');
            if ($ancestor instanceof WP_Term) {
                $family = boblift_brochure_family_of_source_slug(function_exists('boblift_source_term_slug') ? boblift_source_term_slug($ancestor) : $ancestor->slug);
                if ($family !== '') {
                    break;
                }
            }
        }
    }
    return $family;
}

function boblift_brochure_family_of_source_slug(string $source): string
{
    if ($source === 'marine-crane' || str_ends_with($source, 'marine-cranes')) {
        return 'marine';
    }
    if ($source === 'spider-cranes') {
        return 'spider';
    }
    if ($source === 'truck-mounted-crane' || str_ends_with($source, 'truck-mounted-cranes') || in_array($source, ['pickup-cranes', 'timber-cranes', 'forklift-fly-jib-cranes'], true)) {
        return 'truck';
    }
    return '';
}

/** Brochure families that apply to a product, from its categories. */
function boblift_brochure_families_for_product(array $product): array
{
    $slugs = array_values(array_unique(array_filter(array_map('strval', (array) ($product['categories'] ?? [($product['category'] ?? '')])))));
    $families = [];
    foreach ($slugs as $slug) {
        $family = boblift_brochure_family_of_slug($slug);
        if ($family !== '') {
            $families[$family] = true;
        }
    }
    return array_keys($families);
}

function boblift_brochure_items(array $families = []): array
{
    $ui = boblift_site_ui('brochures');
    $copy = is_array($ui['items'] ?? null) ? $ui['items'] : [];
    $items = [];
    foreach (boblift_brochure_files() as $key => $definition) {
        if ($families && !in_array($key, $families, true)) {
            continue;
        }
        $path = get_template_directory() . '/assets/downloads/' . $definition['file'];
        if (!is_readable($path)) {
            continue;
        }
        $items[$key] = [
            'key' => $key,
            'title' => (string) ($copy[$key]['title'] ?? $key),
            'text' => (string) ($copy[$key]['text'] ?? ''),
            'url' => boblift_asset('downloads/' . $definition['file']),
            'file' => $definition['file'],
            'cover' => boblift_asset($definition['cover']),
            'pages' => (int) $definition['pages'],
            'size' => size_format((int) filesize($path), 1),
        ];
    }
    return $items;
}

/**
 * Renders the brochure cards. Args: context ('archive' | 'product'), families (keys to show, empty = all),
 * source (lead source for the email link), product (title and slug for the email link).
 * On the archive the block follows the category filter: data-brochure-map maps every category slug to its family.
 */
function boblift_render_brochures(array $args = []): void
{
    $context = (string) ($args['context'] ?? 'archive');
    $families = array_values(array_filter(array_map('strval', (array) ($args['families'] ?? []))));
    if ($context !== 'archive' && !$families) {
        return;
    }
    $items = boblift_brochure_items($context === 'archive' ? [] : $families);
    if (!$items) {
        return;
    }
    $ui = boblift_site_ui('brochures');
    $is_archive = $context === 'archive';
    $title = (string) ($is_archive ? ($ui['archiveTitle'] ?? 'Download the BOB-LIFT brochures') : ($ui['productTitle'] ?? 'Download the brochure for this range'));
    $map = [];
    if ($is_archive) {
        foreach (boblift_categories() as $category) {
            $slug = (string) ($category['slug'] ?? '');
            $family = boblift_brochure_family_of_slug($slug);
            if ($slug !== '') {
                $map[$slug] = $family;
            }
        }
        foreach (boblift_product_category_hierarchy() as $group) {
            $parent = (string) ($group['slug'] ?? '');
            if ($parent !== '' && !isset($map[$parent])) {
                $map[$parent] = boblift_brochure_family_of_slug($parent);
            }
        }
    }
    $initial_family = $is_archive && !empty($args['initial_category']) ? ($map[(string) $args['initial_category']] ?? '') : '';
    $hide_all = $is_archive && !empty($args['initial_category']) && $initial_family === '';
    $wrapper = ($is_archive ? 'mt-10 scroll-mt-24 lg:mt-14' : 'scroll-mt-24 rounded border border-[#DDE7F2] bg-white p-6 shadow-card md:p-8') . ($hide_all ? ' hidden' : '');
    $card_bg = $is_archive ? 'bg-white' : 'bg-[#F8FAFC]';
    $single = !$is_archive && count($items) === 1;
    ?>
    <section id="brochures" class="<?php echo esc_attr($wrapper); ?>" data-brochures<?php echo $is_archive ? ' data-brochure-map="' . esc_attr(wp_json_encode($map, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . '"' : ''; ?><?php echo $initial_family !== '' ? ' data-brochure-single' : ''; ?> aria-labelledby="brochures-title">
        <?php if ($single) :
            $item = current($items);
            $download_title = str_replace('{title}', $item['title'], (string) ($ui['downloadTitleTemplate'] ?? 'Download {title} as PDF'));
            $meta = [
                (string) ($ui['pdfLabel'] ?? 'PDF'),
                (string) ($ui['languageLabel'] ?? 'EN'),
                str_replace('{count}', (string) $item['pages'], (string) ($ui['pagesLabel'] ?? '{count} pages')),
                (string) $item['size'],
            ];
            ?>
            <?php // On a product page the one brochure is the whole block: its cover beside its title, no card inside the card. ?>
            <div class="grid grid-cols-[88px_minmax(0,1fr)] items-start gap-5 sm:grid-cols-[140px_minmax(0,1fr)] sm:items-center sm:gap-8 lg:grid-cols-[170px_minmax(0,1fr)] lg:gap-12" data-brochure-card data-brochure-family="<?php echo esc_attr($item['key']); ?>">
                <a class="block" href="<?php echo esc_url($item['url']); ?>" download="<?php echo esc_attr($item['file']); ?>" tabindex="-1" aria-hidden="true">
                    <img class="aspect-[520/711] w-full rounded-sm border border-line bg-white object-cover shadow-card" src="<?php echo esc_url($item['cover']); ?>" width="520" height="711" alt="" loading="lazy" decoding="async">
                </a>
                <div class="min-w-0">
                    <p class="boblift-kicker"><?php echo esc_html((string) ($ui['label'] ?? 'Brochures')); ?></p>
                    <h2 id="brochures-title" class="mt-2 text-[clamp(1.25rem,2vw,2rem)] font-black leading-[1.12] tracking-[-0.015em] text-[#071f42]"><?php echo esc_html($item['title']); ?></h2>
                    <p class="mt-3 max-w-2xl text-sm font-normal leading-7 text-slate-600 md:text-base"><?php echo esc_html($item['text']); ?></p>
                    <p class="mt-3 text-[11px] font-black uppercase tracking-[0.1em] text-slate-500"><?php echo esc_html(implode(' · ', $meta)); ?></p>
                    <div class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2">
                        <a class="inline-flex h-11 items-center justify-center gap-2 rounded-sm bg-navy px-5 text-sm font-black text-white transition hover:bg-[#0b2d5b]" href="<?php echo esc_url($item['url']); ?>" download="<?php echo esc_attr($item['file']); ?>" title="<?php echo esc_attr($download_title); ?>"><?php echo boblift_icon('download', 'h-4 w-4'); ?><?php echo esc_html((string) ($ui['downloadLabel'] ?? 'Download PDF')); ?></a>
                        <button class="inline-flex h-11 items-center text-sm font-black text-blue-700 transition hover:text-navy" type="button" data-catalog-modal-open data-catalog-product="<?php echo esc_attr((string) ($args['product'] ?? $item['title'])); ?>" data-catalog-product-slug="<?php echo esc_attr((string) ($args['product_slug'] ?? '')); ?>" data-catalog-url="<?php echo esc_url($item['url']); ?>"><?php echo esc_html((string) ($ui['emailLabel'] ?? 'Email me a copy')); ?></button>
                    </div>
                </div>
            </div>
        <?php else : ?>
        <div class="max-w-3xl">
            <p class="section-label"><?php echo esc_html((string) ($ui['label'] ?? 'Brochures')); ?></p>
            <h2 id="brochures-title" class="<?php echo $is_archive ? 'mt-2 text-2xl font-black leading-tight tracking-[-0.015em] text-ink md:text-3xl' : 'mt-3 text-[clamp(1.6rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em] text-[#071f42]'; ?>"><?php echo esc_html($title); ?></h2>
            <p class="mt-3 text-sm font-normal leading-7 text-slate-600 md:text-base"><?php echo esc_html((string) ($ui['text'] ?? '')); ?></p>
        </div>
        <div class="mt-6 grid gap-4 <?php echo $is_archive ? 'md:grid-cols-2 min-[1700px]:grid-cols-3' : 'md:grid-cols-2 xl:grid-cols-3'; ?>" data-brochure-grid>
            <?php
            // The last card fills what is left of its row, so an odd count leaves no empty cell (two columns, three from 1700px on the archive).
            $spans = array_fill(0, count($items), 1);
            $last_span_class = [1 => '', 2 => 'md:col-span-2'][boblift_grid_last_item_span($spans, 2)] . ' ' . ($is_archive ? [1 => 'min-[1700px]:col-span-1', 2 => 'min-[1700px]:col-span-2', 3 => 'min-[1700px]:col-span-3'] : [1 => 'xl:col-span-1', 2 => 'xl:col-span-2', 3 => 'xl:col-span-3'])[boblift_grid_last_item_span($spans, 3)];
            $last_key = array_key_last($items);
            foreach ($items as $item_key => $item) :
                $hidden = $is_archive && $initial_family !== '' && $initial_family !== $item['key'];
                $download_title = str_replace('{title}', $item['title'], (string) ($ui['downloadTitleTemplate'] ?? 'Download {title} as PDF'));
                $meta = [
                    (string) ($ui['pdfLabel'] ?? 'PDF'),
                    (string) ($ui['languageLabel'] ?? 'EN'),
                    str_replace('{count}', (string) $item['pages'], (string) ($ui['pagesLabel'] ?? '{count} pages')),
                    (string) $item['size'],
                ];
                ?>
                <article class="<?php echo $hidden ? 'hidden' : 'flex'; ?> gap-4 rounded border border-line <?php echo esc_attr($card_bg); ?> p-4 shadow-card<?php echo $item_key === $last_key ? ' ' . esc_attr(trim($last_span_class)) : ''; ?>" data-brochure-card data-brochure-family="<?php echo esc_attr($item['key']); ?>">
                    <a class="block w-24 shrink-0 self-start sm:w-28" data-brochure-cover href="<?php echo esc_url($item['url']); ?>" download="<?php echo esc_attr($item['file']); ?>" tabindex="-1" aria-hidden="true">
                        <img class="aspect-[520/711] w-full rounded-sm border border-line bg-white object-cover" src="<?php echo esc_url($item['cover']); ?>" width="520" height="711" alt="" loading="lazy" decoding="async">
                    </a>
                    <div class="flex min-w-0 flex-1 flex-col">
                        <ul class="flex flex-wrap gap-1.5" aria-label="<?php echo esc_attr($item['title']); ?>">
                            <?php foreach ($meta as $chip) : ?>
                                <li class="rounded-sm border border-line bg-white px-2 py-0.5 text-[11px] font-black uppercase tracking-[0.08em] text-slate-500"><?php echo esc_html($chip); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <h3 class="mt-3 text-lg font-black leading-snug text-ink"><?php echo esc_html($item['title']); ?></h3>
                        <p class="mt-2 text-sm font-normal leading-6 text-slate-600"><?php echo esc_html($item['text']); ?></p>
                        <div class="mt-auto flex flex-wrap items-center gap-x-4 gap-y-2 pt-4">
                            <a class="inline-flex h-11 items-center justify-center gap-2 rounded-sm bg-navy px-5 text-sm font-black text-white transition hover:bg-[#0b2d5b]" href="<?php echo esc_url($item['url']); ?>" download="<?php echo esc_attr($item['file']); ?>" title="<?php echo esc_attr($download_title); ?>"><?php echo boblift_icon('download', 'h-4 w-4'); ?><?php echo esc_html((string) ($ui['downloadLabel'] ?? 'Download PDF')); ?></a>
                            <button class="inline-flex h-11 items-center text-sm font-black text-blue-700 transition hover:text-navy" type="button" data-catalog-modal-open data-catalog-product="<?php echo esc_attr((string) ($args['product'] ?? $item['title'])); ?>" data-catalog-product-slug="<?php echo esc_attr((string) ($args['product_slug'] ?? '')); ?>" data-catalog-url="<?php echo esc_url($item['url']); ?>"><?php echo esc_html((string) ($ui['emailLabel'] ?? 'Email me a copy')); ?></button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
    <?php
}
