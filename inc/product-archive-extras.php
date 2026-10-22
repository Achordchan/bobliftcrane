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

/** The figures of range models as the comparison tables print them: moments in t·m, weights in kg, a photo for each. */
function boblift_compare_models_prepare(array $models): array
{
    // Moments are entered as "13 Ton.m" on some products and as a bare "2.2" on others: show them all as t·m.
    foreach ($models as $index => $item) {
        $moment = trim((string) $item['moment']);
        $moment = (string) preg_replace('/\s*ton\.?\s*m$/i', ' t·m', $moment);
        $models[$index]['moment'] = preg_match('/^\d+(?:\.\d+)?$/', $moment) ? $moment . ' t·m' : $moment;
        $models[$index]['weight'] = (string) preg_replace('/(\d)\s*kg$/i', '$1 kg', trim((string) $item['weight']));
        $models[$index]['capacity_label'] = $item['capacity'] > 0 ? boblift_guide_number($item['capacity']) . ' t' : '';
        // The rest of the spec table (power, hydraulics, oil tank, rotation…) as extra rows, keyed by the English label so
        // the same figure lines up across models; shown in the page's language when the translation keeps the rows.
        $english = function_exists('boblift_product_english_id') ? array_values(array_filter((array) boblift_decode_meta(get_post_meta(boblift_product_english_id((int) $item['id']), '_specs', true), []), 'is_array')) : [];
        $local = array_values(array_filter((array) boblift_decode_meta(get_post_meta((int) $item['id'], '_specs', true), []), 'is_array'));
        $models[$index]['extra'] = [];
        // A translated row stands in for the English one only when the tables line up (boblift_spec_rows_aligned()).
        $aligned = function_exists('boblift_spec_rows_aligned') && boblift_spec_rows_aligned($local, $english);
        foreach ($english as $row_index => $row) {
            $english_label = trim((string) ($row['label'] ?? ''));
            if ($english_label === '' || in_array($english_label, ['Max Lifting Capacity', 'Max Lifting Moment', 'Self Weight', 'Boom Sections'], true)) {
                continue;
            }
            $shown = $aligned ? $local[$row_index] : $row;
            $value = trim((string) ($shown['value'] ?? ''));
            if ($value !== '') {
                $models[$index]['extra'][$english_label] = ['label' => trim((string) ($shown['label'] ?? $english_label)), 'value' => $value];
            }
        }
        // Reach and outrigger figures read off the drawing compare too (radius first: it is what buyers choose by).
        if (function_exists('boblift_product_drawing_data')) {
            foreach ((boblift_product_drawing_data((int) $item['id'])['rows'] ?? []) as $row) {
                $models[$index]['extra']['drawing:' . $row['key']] = ['label' => $row['label'], 'value' => $row['value']];
            }
        }
        $thumbnail = get_the_post_thumbnail_url($item['id'], 'medium');
        $models[$index]['image'] = is_string($thumbnail) && $thumbnail !== '' ? $thumbnail : boblift_post_thumbnail_url(get_post($item['id']), boblift_asset('images/products/crane-accessories.png'));
    }
    return $models;
}

/**
 * Models side by side: one column per model (photo, model, name), one row per published figure, so a range of twelve
 * models stays five rows tall and scrolls sideways with the labels pinned. $options: label_class (the pinned column's
 * colours), this_id (the page's own model, not linked), status (tag each column current or previous), ask (an inquiry
 * button under each previous model).
 */
/** The comparison for two or three models: one card each, the same figures in the same order so they line up across. */
function boblift_render_model_cards(array $models, string $labelled_by, array $rows, array $options = []): void
{
    $t = static fn(string $text): string => boblift_localized_static_text($text);
    $this_id = (int) ($options['this_id'] ?? 0);
    $status = !empty($options['status']);
    $ask = !empty($options['ask']);
    $columns = count($models) === 3 ? 'md:grid-cols-2 xl:grid-cols-3' : 'md:grid-cols-2';
    ?>
    <ul class="grid gap-4 <?php echo esc_attr($columns); ?>" role="list" aria-labelledby="<?php echo esc_attr($labelled_by); ?>">
        <?php foreach ($models as $item) :
            $is_this = $item['id'] === $this_id;
            ?>
            <li class="flex flex-col rounded border <?php echo $is_this ? 'border-[#BFD3EA] bg-[#F5F8FC]' : 'border-[#E5E7EB] bg-white'; ?> p-4 sm:p-5">
                <div class="flex items-center gap-4 sm:gap-5">
                    <span class="flex h-24 w-28 shrink-0 items-center justify-center overflow-hidden rounded-sm bg-[#F8FAFC] sm:h-28 sm:w-36"><img class="h-full w-full object-contain" src="<?php echo esc_url($item['image']); ?>" alt="" loading="lazy" decoding="async"></span>
                    <div class="min-w-0">
                        <?php if ($status) : ?>
                            <span class="mb-2 inline-flex items-center rounded-sm px-2 py-1 text-[11px] font-black uppercase leading-none tracking-[0.08em] <?php echo $item['legacy'] ? 'bg-[#EEF1F5] text-slate-600' : 'bg-blue-50 text-blue-700'; ?>"><?php echo esc_html($t($item['legacy'] ? 'Previous model' : 'Current model')); ?></span>
                        <?php endif; ?>
                        <?php if ($is_this) : ?>
                            <p class="flex flex-wrap items-center gap-x-2 text-lg font-black text-ink"><?php echo esc_html($item['model']); ?><span class="text-[11px] font-black uppercase tracking-[0.1em] text-slate-500"><?php echo esc_html($t('This model')); ?></span></p>
                        <?php else : ?>
                            <a class="inline-flex items-center gap-1 text-lg font-black text-ink transition hover:text-blue-700" href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['model']); ?><?php echo boblift_icon('arrow-up-right', 'h-3.5 w-3.5 shrink-0 text-slate-400 rtl:-scale-x-100'); ?></a>
                        <?php endif; ?>
                        <p class="mt-1 line-clamp-2 text-xs leading-5 text-slate-500"><?php echo esc_html((string) $item['title']); ?></p>
                    </div>
                </div>
                <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 border-t border-[#E5E7EB] pt-4 <?php echo esc_attr([2 => 'sm:grid-cols-2', 3 => 'sm:grid-cols-3 md:grid-cols-2 xl:grid-cols-3', 4 => 'sm:grid-cols-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4'][min(4, max(2, count($rows)))]); ?>">
                    <?php foreach ($rows as $key => $label) : ?>
                        <div class="min-w-0"><dt class="text-xs font-semibold leading-4 text-slate-500"><?php echo esc_html($label); ?></dt><dd class="mt-1 break-words text-base font-black leading-5 text-ink [font-variant-numeric:tabular-nums]" dir="ltr"><?php echo esc_html((string) ($item[$key] ?: '–')); ?></dd></div>
                    <?php endforeach; ?>
                </dl>
                <?php if ($ask && !$item['legacy'] && !$is_this) : ?>
                    <a class="mt-4 inline-flex h-10 w-fit items-center gap-1.5 rounded-sm bg-[#071f42] px-4 text-sm font-black text-white transition hover:bg-[#0B3F7D]" href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html(str_replace('{model}', (string) $item['model'], $t('View {model}'))); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4 rtl:rotate-180'); ?></a>
                <?php endif; ?>
                <?php if ($ask && $item['legacy']) : ?>
                    <button class="mt-4 inline-flex w-fit items-center gap-1.5 text-sm font-black text-blue-700 transition hover:text-[#071f42]" type="button" data-inquiry-modal-open data-inquiry-source="历史型号" data-inquiry-product="<?php echo esc_attr((string) $item['title']); ?>" data-inquiry-products="<?php echo esc_attr(boblift_inquiry_product_payload((int) $item['id'], (string) $item['title'], (string) $item['image'])); ?>"><?php echo esc_html($t('Ask about this model')); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4 shrink-0 rtl:rotate-180'); ?></button>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
}

function boblift_render_model_table(array $models, string $labelled_by, array $options = []): void
{
    $t = static fn(string $text): string => boblift_localized_static_text($text);
    $this_id = (int) ($options['this_id'] ?? 0);
    $status = !empty($options['status']);
    $ask = !empty($options['ask']);
    // A row shows only when at least one model has the figure.
    $rows = array_filter([
        'capacity_label' => $t('Capacity'),
        'moment' => $t('Max lifting moment'),
        'weight' => $t('Self weight'),
        'sections_label' => $t('Boom sections'),
    ], static fn(string $label, string $key): bool => (bool) array_filter(array_column($models, $key)), ARRAY_FILTER_USE_BOTH);
    // Then every other spec-table row any of the models has, in the order the tables list them.
    foreach ($models as $index => $item) {
        foreach ((array) ($item['extra'] ?? []) as $english_label => $spec) {
            $key = 'extra:' . $english_label;
            $rows[$key] ??= $spec['label'];
        }
    }
    foreach ($models as $index => $item) {
        foreach ($rows as $key => $label) {
            if (str_starts_with($key, 'extra:')) {
                $models[$index][$key] = (string) ($item['extra'][substr($key, 6)]['value'] ?? '');
            }
        }
    }
    // Two or three models compare better as cards side by side: a table column that wide stretches a small photo.
    if (count($models) <= 3) {
        boblift_render_model_cards($models, $labelled_by, $rows, $options);
        return;
    }
    $label_cell = 'sticky start-0 z-10 w-[132px] min-w-[132px] border-e border-[#E5E7EB] px-4 text-start text-[13px] font-semibold text-slate-500 sm:w-[180px] sm:min-w-[180px] sm:px-5 ' . (string) ($options['label_class'] ?? 'bg-[#F8FAFC]');
    $ask_label = $t('Ask about this model');
    ?>
    <div class="overflow-x-auto overscroll-x-contain rounded border border-[#E5E7EB] bg-white" tabindex="0" role="region" aria-labelledby="<?php echo esc_attr($labelled_by); ?>">
        <table class="w-full border-separate border-spacing-0 text-sm">
            <thead>
                <tr>
                    <th class="<?php echo esc_attr($label_cell); ?> border-b-2 border-b-ink align-bottom pb-4" scope="col"><?php echo esc_html($t('Model')); ?></th>
                    <?php foreach ($models as $item) :
                        $is_this = $item['id'] === $this_id;
                        ?>
                        <th class="min-w-[164px] border-b-2 border-b-ink px-4 pb-4 pt-5 text-start align-bottom font-normal <?php echo $is_this ? 'bg-[#F5F8FC]' : ''; ?>" scope="col">
                            <?php if ($status) : ?>
                                <span class="mb-3 inline-flex items-center rounded-sm px-2 py-1 text-[11px] font-black uppercase leading-none tracking-[0.08em] <?php echo $item['legacy'] ? 'bg-[#EEF1F5] text-slate-600' : 'bg-blue-50 text-blue-700'; ?>"><?php echo esc_html($t($item['legacy'] ? 'Previous model' : 'Current model')); ?></span>
                            <?php endif; ?>
                            <?php if ($is_this) : ?>
                                <span class="block">
                                    <span class="flex aspect-[4/3] items-center justify-center overflow-hidden rounded-sm bg-white"><img class="h-full w-full object-contain" src="<?php echo esc_url($item['image']); ?>" alt="" loading="lazy" decoding="async"></span>
                                    <span class="mt-3 flex items-center gap-2 text-base font-black text-ink"><?php echo esc_html($item['model']); ?><span class="text-[11px] font-black uppercase tracking-[0.1em] text-slate-500"><?php echo esc_html($t('This model')); ?></span></span>
                                    <span class="mt-1 line-clamp-2 block text-xs leading-5 text-slate-500"><?php echo esc_html((string) $item['title']); ?></span>
                                </span>
                            <?php else : ?>
                                <a class="group block" href="<?php echo esc_url($item['url']); ?>">
                                    <span class="flex aspect-[4/3] items-center justify-center overflow-hidden rounded-sm bg-[#F8FAFC]"><img class="h-full w-full object-contain transition duration-500 group-hover:scale-[1.04]" src="<?php echo esc_url($item['image']); ?>" alt="" loading="lazy" decoding="async"></span>
                                    <span class="mt-3 flex items-center gap-1 text-base font-black text-ink transition group-hover:text-blue-700"><?php echo esc_html($item['model']); ?><?php echo boblift_icon('arrow-up-right', 'h-3.5 w-3.5 shrink-0 text-slate-400 transition group-hover:text-blue-700 rtl:-scale-x-100'); ?></span>
                                    <span class="mt-1 line-clamp-2 block text-xs leading-5 text-slate-500"><?php echo esc_html((string) $item['title']); ?></span>
                                </a>
                            <?php endif; ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $key => $label) : ?>
                    <tr class="group/row">
                        <th class="<?php echo esc_attr($label_cell); ?> border-b py-3.5" scope="row"><?php echo esc_html($label); ?></th>
                        <?php foreach ($models as $item) : ?>
                            <td class="whitespace-nowrap border-b border-[#E5E7EB] px-4 py-3.5 font-semibold text-ink [font-variant-numeric:tabular-nums] <?php echo $ask ? '' : 'group-last/row:border-b-0'; ?> <?php echo $item['id'] === $this_id ? 'bg-[#F5F8FC]' : ''; ?>"><?php echo esc_html((string) ($item[$key] ?: '–')); ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ask) : ?>
                    <tr>
                        <?php // The last row holds the actions: ask about a previous model, or go to a current one. ?>
                        <th class="<?php echo esc_attr($label_cell); ?> py-3.5" scope="row"><span class="sr-only"><?php echo esc_html($t('Built to order')); ?></span></th>
                        <?php foreach ($models as $item) : ?>
                            <td class="px-4 py-3.5 <?php echo $item['id'] === $this_id ? 'bg-[#F5F8FC]' : ''; ?>">
                                <?php if ($item['legacy']) : ?>
                                    <button class="inline-flex items-center gap-1.5 text-start text-sm font-black text-blue-700 transition hover:text-[#071f42]" type="button" data-inquiry-modal-open data-inquiry-source="历史型号" data-inquiry-product="<?php echo esc_attr((string) $item['title']); ?>" data-inquiry-products="<?php echo esc_attr(boblift_inquiry_product_payload((int) $item['id'], (string) $item['title'], (string) $item['image'])); ?>"><?php echo esc_html($ask_label); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4 shrink-0 rtl:rotate-180'); ?></button>
                                <?php elseif ($item['id'] !== $this_id) : ?>
                                    <a class="inline-flex items-center gap-1.5 text-sm font-black text-[#071f42] transition hover:text-blue-700" href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html(str_replace('{model}', (string) $item['model'], $t('View {model}'))); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4 shrink-0 rtl:rotate-180'); ?></a>
                                <?php else : ?>
                                    <span class="text-slate-400">–</span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/** The range compared model by model, current models only. */
function boblift_render_archive_compare(WP_Term $term): void
{
    $t = static fn(string $text): string => boblift_localized_static_text($text);
    $models = boblift_product_range_models($term);
    if (count($models) < 2) {
        return;
    }
    $id = 'compare-' . $term->slug;
    ?>
    <section class="mt-14 lg:mt-20" aria-labelledby="<?php echo esc_attr($id); ?>">
        <div class="grid gap-4 lg:grid-cols-12 lg:items-end lg:gap-8">
            <div class="lg:col-span-7">
                <p class="section-label"><?php echo esc_html(str_replace('{range}', $term->name, $t('Published figures · {range}'))); ?></p>
                <h2 id="<?php echo esc_attr($id); ?>" class="mt-3 text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em] text-ink"><?php echo esc_html($t('Compare the range')); ?></h2>
            </div>
            <p class="max-w-[46ch] text-sm leading-6 text-slate-600 lg:col-span-5 lg:justify-self-end"><?php echo esc_html($t('Figures from the BOB-LIFT technical sheets. Confirm the configuration for your truck or vessel with our team.')); ?></p>
        </div>
        <div class="mt-6 lg:mt-8"><?php boblift_render_model_table(boblift_compare_models_prepare($models), $id); ?></div>
    </section>
    <?php
}

/**
 * The model library: previous models, no longer built as standard but still built to order, grouped by category. It is
 * a view of the listing (opened from the category tabs or the line under the pager) rather than a block under it, so
 * the page stays short. Each row points to its current equivalent and opens an inquiry.
 */
function boblift_render_legacy_panel(array $groups): void
{
    if ($groups === []) {
        return;
    }
    $t = static fn(string $text): string => boblift_localized_static_text($text);
    $head = 'whitespace-nowrap border-b-2 border-b-ink px-4 pb-3 pt-4 text-start align-bottom text-[13px] font-semibold text-slate-500';
    $cell = 'border-b border-[#E5E7EB] px-4 py-3.5 align-top group-last/row:border-b-0';
    $sticky = 'sticky start-0 z-10 w-[132px] min-w-[132px] border-e border-[#E5E7EB] bg-white sm:w-[180px] sm:min-w-[180px] sm:px-5';
    ?>
    <section id="previous-models" class="boblift-legacy-panel scroll-mt-6" aria-labelledby="legacy-library-title" data-legacy-panel>
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between md:gap-8">
            <div class="min-w-0">
                <h2 id="legacy-library-title" class="text-[clamp(1.5rem,2.2vw,2.25rem)] font-black leading-[1.08] tracking-[-0.015em] text-ink"><?php echo esc_html($t('Previous models')); ?><span class="text-slate-400" data-legacy-scope-title hidden></span></h2>
                <p class="mt-2 max-w-[60ch] text-sm leading-6 text-slate-600 md:text-base md:leading-7"><?php echo esc_html($t('Configurations we no longer build as standard. Each can still be built to order on request.')); ?></p>
            </div>
            <button class="inline-flex h-11 w-fit shrink-0 items-center gap-2 rounded-sm border border-[#D8DEE8] bg-white px-4 text-sm font-black text-[#0B1B33] shadow-card transition hover:border-blue-300 hover:text-blue-700" type="button" data-legacy-close><?php echo boblift_icon('arrow-right', 'h-4 w-4 rotate-180 rtl:rotate-0'); ?><?php echo esc_html($t('Back to current models')); ?></button>
        </div>
        <div class="mt-6 hidden flex-col items-center justify-center rounded border border-dashed border-[#CBD5E1] bg-white px-6 py-14 text-center shadow-card lg:mt-8" data-legacy-empty>
            <span class="inline-flex h-14 w-14 items-center justify-center rounded-sm bg-blue-50 text-blue-700"><?php echo boblift_icon('clock-3', 'h-7 w-7'); ?></span>
            <h3 class="mt-5 text-2xl font-black tracking-[-0.015em] text-ink"><?php echo esc_html($t('No previous models in this category')); ?></h3>
            <p class="mt-3 max-w-md text-sm leading-6 text-slate-600"><?php echo esc_html($t('Tell us the model or configuration you need and our team will check it for you.')); ?></p>
            <button class="mt-6 inline-flex min-h-12 items-center gap-2 rounded-sm bg-navy px-6 text-sm font-black text-white shadow-card transition hover:-translate-y-0.5 hover:bg-[#0b2d5b]" type="button" data-inquiry-modal-open data-inquiry-source="历史型号库（无记录）"><?php echo boblift_icon('file-text', 'h-4 w-4'); ?><?php echo esc_html($t('Send your request')); ?></button>
        </div>
        <div class="mt-6 grid gap-8 lg:mt-8" data-legacy-groups>
            <?php foreach ($groups as $group) :
                $group_id = 'legacy-library-' . $group['slug'];
                $models = boblift_compare_models_prepare($group['models']);
                ?>
                <div class="min-w-0" data-legacy-group="<?php echo esc_attr($group['slug']); ?>" data-legacy-size="<?php echo esc_attr((string) count($models)); ?>">
                    <h3 id="<?php echo esc_attr($group_id); ?>" class="text-lg font-black leading-snug text-ink"><?php echo esc_html($group['title']); ?></h3>
                    <div class="relative mt-3 overflow-x-auto overscroll-x-contain rounded border border-[#E5E7EB] bg-white" tabindex="0" role="region" aria-labelledby="<?php echo esc_attr($group_id); ?>">
                        <table class="w-full border-separate border-spacing-0 text-sm">
                            <thead>
                                <tr>
                                    <th class="<?php echo esc_attr($head . ' ' . $sticky); ?>" scope="col"><?php echo esc_html($t('Model')); ?></th>
                                    <th class="<?php echo esc_attr($head); ?>" scope="col"><?php echo esc_html($t('Capacity')); ?></th>
                                    <th class="<?php echo esc_attr($head); ?>" scope="col"><?php echo esc_html($t('Max lifting moment')); ?></th>
                                    <th class="<?php echo esc_attr($head); ?>" scope="col"><?php echo esc_html($t('Self weight')); ?></th>
                                    <th class="<?php echo esc_attr($head); ?> min-w-[160px]" scope="col"><?php echo esc_html($t('Current equivalent')); ?></th>
                                    <th class="<?php echo esc_attr($head); ?>" scope="col"><span class="sr-only"><?php echo esc_html($t('Built to order')); ?></span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($models as $item) : ?>
                                    <tr class="group/row">
                                        <th class="<?php echo esc_attr($cell . ' ' . $sticky); ?> text-start" scope="row"><a class="inline-flex items-center gap-1 font-black text-ink transition hover:text-blue-700" href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['model']); ?><?php echo boblift_icon('arrow-up-right', 'h-3.5 w-3.5 shrink-0 text-slate-400 rtl:-scale-x-100'); ?></a></th>
                                        <td class="<?php echo esc_attr($cell); ?> whitespace-nowrap font-semibold text-ink [font-variant-numeric:tabular-nums]"><?php echo esc_html($item['capacity_label'] ?: '–'); ?></td>
                                        <td class="<?php echo esc_attr($cell); ?> whitespace-nowrap font-semibold text-ink [font-variant-numeric:tabular-nums]"><?php echo esc_html($item['moment'] ?: '–'); ?></td>
                                        <td class="<?php echo esc_attr($cell); ?> whitespace-nowrap font-semibold text-ink [font-variant-numeric:tabular-nums]"><?php echo esc_html($item['weight'] ?: '–'); ?></td>
                                        <td class="<?php echo esc_attr($cell); ?> font-black [&_a]:text-blue-700 [&_a]:underline-offset-4 [&_a:hover]:underline"><?php echo $item['current'] ? boblift_guide_model_links($item['current']) : '<span class="text-slate-400">–</span>'; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped by boblift_guide_model_links. ?></td>
                                        <td class="<?php echo esc_attr($cell); ?> whitespace-nowrap"><button class="inline-flex items-center gap-1.5 text-sm font-black text-blue-700 transition hover:text-[#071f42]" type="button" data-inquiry-modal-open data-inquiry-source="历史型号库" data-inquiry-product="<?php echo esc_attr((string) $item['title']); ?>" data-inquiry-products="<?php echo esc_attr(boblift_inquiry_product_payload((int) $item['id'], (string) $item['title'], (string) $item['image'])); ?>"><?php echo esc_html($t('Ask about this model')); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4 shrink-0 rtl:rotate-180'); ?></button></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
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
    // Parts, assembly and the load test, then the two ways a crane leaves: mounted on a truck, or packed for export.
    $candidates = [
        [$by_key((array) ($tour['stops'] ?? []), 'parts'), 'factory/hall-boom-sections', '/factory-tour.html'],
        [(array) ($quality['steps'][0] ?? []), 'factory/hall-parts-racks', '/quality-testing.html'],
        [$by_key((array) ($tour['stops'] ?? []), 'assembly'), 'factory/hall-knuckle-row', '/factory-tour.html'],
        [(array) ($quality['steps'][1] ?? []), 'factory/hall-crane-test-bay', '/quality-testing.html'],
        [$by_key((array) ($mounting['mountSteps'] ?? []), 'lift'), 'mounting/lift-crane-hoist', '/mounting-shipping.html'],
        [$by_key((array) ($mounting['mountSteps'] ?? []), 'fix'), 'mounting/fix-frame-close', '/mounting-shipping.html'],
        [$by_key((array) ($mounting['mountSteps'] ?? []), 'finish'), 'mounting/finished-truck-arm', '/mounting-shipping.html'],
        [$by_key((array) ($mounting['packSteps'] ?? []), 'batch'), 'mounting/batch-row-6-3z', '/mounting-shipping.html'],
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
    // The comparison sits right under the listing, the factory steps next, and the articles close the page. Previous
    // models are a view of the listing itself (boblift_render_legacy_panel()).
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

/**
 * Product page: this model beside the other configurations of its capacity when previous models are among them
 * (inc/product-lifecycle.php), in the page's card style. $models from boblift_product_model_history().
 */
function boblift_render_product_model_history(array $models, int $product_id, bool $is_legacy): void
{
    if ($models === []) {
        return;
    }
    $t = static fn(string $text): string => boblift_localized_static_text($text);
    ?>
    <section id="model-history" class="scroll-mt-24 overflow-hidden rounded border border-[#DDE7F2] bg-white shadow-card" aria-labelledby="model-history-title">
        <div class="grid gap-x-8 gap-y-3 p-6 pb-0 md:p-8 md:pb-0 lg:grid-cols-12 lg:items-end">
            <div class="lg:col-span-7">
                <p class="boblift-kicker"><?php echo esc_html($t('Model history')); ?></p>
                <h2 id="model-history-title" class="mt-3 text-[clamp(1.6rem,2.4vw,2.5rem)] font-black leading-[1.08] tracking-[-0.015em] text-[#071f42]"><?php echo esc_html($t($is_legacy ? 'Compare with the current range' : 'Earlier configurations')); ?></h2>
            </div>
            <p class="max-w-[46ch] text-sm leading-6 text-slate-600 lg:col-span-5 lg:justify-self-end"><?php echo esc_html($t('Previous models are configurations we no longer build as standard. They can still be built to order.')); ?></p>
        </div>
        <?php
        // On a previous model's page the way on comes first: its current model(s), each a direct link.
        $current_models = $is_legacy ? array_values(array_filter($models, static fn(array $item): bool => !$item['legacy'])) : [];
        if ($current_models) : ?>
            <div class="mx-6 mt-6 flex flex-wrap items-center gap-x-4 gap-y-3 rounded-sm border border-[#BFD3EA] bg-[#F5F8FC] px-4 py-3 md:mx-8">
                <span class="text-sm font-black text-[#071f42]"><?php echo esc_html($t('Current model')); ?></span>
                <?php foreach ($current_models as $current) : ?>
                    <a class="inline-flex h-10 items-center gap-1.5 rounded-sm bg-[#071f42] px-4 text-sm font-black text-white transition hover:bg-[#0B3F7D]" href="<?php echo esc_url($current['url']); ?>"><?php echo esc_html(str_replace('{model}', (string) $current['model'], $t('View {model}'))); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4 rtl:rotate-180'); ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="p-6 md:p-8"><?php boblift_render_model_table(boblift_compare_models_prepare($models), 'model-history-title', ['this_id' => $product_id, 'status' => true, 'ask' => true, 'label_class' => 'bg-white']); ?></div>
    </section>
    <?php
}
