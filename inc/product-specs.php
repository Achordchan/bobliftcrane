<?php
/**
 * The technical block of a product page: the model code strip, the list of configurations to pick from, and the figures in
 * three bordered groups (lifting, hydraulics and power, machine) beside the drawings.
 */

defined('ABSPATH') || exit;

/** A figure with its unit a size down, as elsewhere on the page. */
function boblift_specs_value_html(string $value, string $size = 'text-[15px]'): string
{
    $value = trim($value);
    $number = $value;
    $unit = '';
    if (preg_match('/^([\d.,+]+)\s*(kg|t·m|kN·m|kW|L\/min|MPa|mm|m|t|°)$/u', $value, $match)) {
        [$number, $unit] = [$match[1], $match[2]];
    }
    if (preg_match('/^\d{4,}$/', $number)) {
        $number = number_format((int) $number, 0, '.', ',');
    }
    $html = '<span class="' . esc_attr($size) . '">' . esc_html($number) . '</span>';
    if ($unit !== '') {
        $html .= '<span class="' . ($unit === '°' ? '' : 'ms-1 ') . 'text-[13px] font-semibold text-slate-500">' . esc_html($unit) . '</span>';
    }
    return $html;
}

function boblift_specs_row_attributes(array $row, string $weight_label, string $winch_label): string
{
    if ($weight_label !== '' && ($row['label'] ?? '') === $weight_label) {
        return ' data-cfg-weight-row';
    }
    return $winch_label !== '' && ($row['label'] ?? '') === $winch_label ? ' data-cfg-winch-row' : '';
}

/**
 * The configurations a layout lists: every choice of the series, including a winch version that has no product of its own, so the
 * row or column marked is the one the page shows. Such a version shares its source's figures except the self weight (not known).
 */
function boblift_specs_choices(array $state): array
{
    $members = [];
    foreach (boblift_series_members_with_figures($state['series']) as $member) {
        $members[$member['model']] = $member;
    }
    $choices = [];
    foreach ((array) ($state['variants'] ?? []) as $variant) {
        $source = $members[$variant['source'] ?? $variant['model']] ?? null;
        if (!$source) {
            continue;
        }
        $figures = (array) $source['figures'];
        if (!empty($variant['derived'])) {
            $figures['weight'] = null;
        }
        $choices[] = array_merge($source, ['model' => (string) $variant['model'], 'url' => (string) ($variant['url'] ?? $source['url']), 'values' => (array) ($variant['values'] ?? $source['values']), 'derived' => !empty($variant['derived']), 'source' => (string) ($variant['source'] ?? $variant['model']), 'figures' => $figures]);
    }
    return $choices ?: array_values($members);
}

/** The configurations as a list to pick from, one row each, with a radio dot; it switches the page like the box above. */
function boblift_specs_render_choices(array $state): void
{
    $choices = boblift_specs_choices($state);
    $dimensions = boblift_series_dimensions($choices);
    $labelled = (bool) array_intersect(['sections', 'winch', 'manual'], array_keys($dimensions));
    $columns = array_values(array_filter(boblift_series_compare_columns($choices), static fn(array $column): bool => $column[1] !== 'moment'));
    $missing = false;
    $derived_weight = [];
    $manual = array_filter($choices, static fn(array $choice): bool => ($choice['values']['manual'] ?? '') === '1') && isset($dimensions['sections']);
    // Each row a thin box, cells parted by hairlines; the chosen row takes the dark edge.
    $cell = 'border-y border-s border-[#DDE7F2] px-3 py-3 first:rounded-s-sm last:rounded-e-sm last:border-e [[aria-current]>&]:border-[#0B3F7D] [&:not(:first-child)]:border-s-[#E6EDF5] [[aria-current]>&:not(:first-child)]:border-s-[#C9D8EC]';
    ?>
    <div data-cfg-compare>
        <div class="overflow-x-auto [scrollbar-width:thin]">
            <table class="w-full min-w-[460px] border-separate border-spacing-y-1.5 text-[14px] [font-variant-numeric:tabular-nums]">
                <thead>
                    <tr class="whitespace-nowrap text-[12px] font-semibold uppercase lg:whitespace-normal tracking-[0.08em] text-slate-500 rtl:tracking-normal">
                        <th class="px-3 pb-1 ps-11 text-start font-semibold" scope="col"><?php echo esc_html(boblift_series_text('Model')); ?></th>
                        <?php if ($labelled) : ?><th class="px-3 pb-1 text-start font-semibold" scope="col"><?php echo esc_html(boblift_series_text('Configuration')); ?></th><?php endif; ?>
                        <?php foreach ($columns as [$label]) : ?><th class="px-3 pb-1 text-start font-semibold" scope="col"><?php echo esc_html(boblift_series_text($label)); ?></th><?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($choices as $choice) : $current = $choice['model'] === $state['active']; ?>
                        <tr class="boblift-cfg-row boblift-specs-choice cursor-pointer text-[#071f42] transition-colors hover:bg-[#F5F8FC]" data-cfg-row="<?php echo esc_attr($choice['model']); ?>"<?php echo $current ? ' aria-current="true"' : ''; ?>>
                            <th class="<?php echo esc_attr($cell); ?> text-start font-black" scope="row"><a class="inline-flex items-center gap-3 whitespace-nowrap focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400" href="<?php echo esc_url($choice['url']); ?>" dir="ltr"><span class="boblift-cfg-dot" aria-hidden="true"></span><?php echo esc_html($choice['model']); ?></a></th>
                            <?php if ($labelled) : ?><td class="<?php echo esc_attr($cell); ?> whitespace-nowrap font-normal"><?php echo esc_html(boblift_series_compare_label($choice['values'], $dimensions, (string) $state['series']['family'])); ?></td><?php endif; ?>
                            <?php foreach ($columns as [, $key]) :
                                $value = $choice['figures'][$key] ?? null;
                                // A derived winch version's weight is unknown, said by its own note; other gaps point to the drawing.
                                $missing = $missing || ($value === null && !($choice['derived'] && $key === 'weight'));
                                $derived_weight[($choice['values']['winch'] ?? '') === '1' ? 'on' : 'off'] = ($derived_weight[($choice['values']['winch'] ?? '') === '1' ? 'on' : 'off'] ?? false) || ($value === null && $choice['derived'] && $key === 'weight');
                                ?>
                                <td class="<?php echo esc_attr($cell); ?> whitespace-nowrap font-bold"><span dir="ltr"><?php echo $value === null ? '<span class="font-normal text-slate-400">—</span>' : esc_html($key === 'weight' ? boblift_series_format_kg($value) : (string) $value); ?></span></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        $notes = array_filter([boblift_series_winch_sentence((string) $state['series']['winch']), $manual ? boblift_series_text('+1 = one added manual extension') : '', !empty($derived_weight['on']) ? '— ' . boblift_series_text('Winch version: self weight confirmed with your quote') : '', !empty($derived_weight['off']) ? '— ' . boblift_series_text('No-winch version: self weight confirmed with your quote') : '', $missing ? boblift_series_text('—: not listed here, see the drawing for this configuration.') : '']);
        foreach ($notes as $note) : ?>
            <p class="mt-1.5 text-[13px] leading-5 text-slate-500"><?php echo esc_html($note); ?></p>
        <?php endforeach; ?>
    </div>
    <?php
}

/** The model code as one strip in the order the name is written. */
function boblift_specs_render_decode_strip(string $model, string $winch, int $english_id): void
{
    $decode = boblift_series_decode_parts($model, $winch, $english_id);
    if (!$decode['parts']) {
        return;
    }
    ?>
    <div class="mt-5 flex flex-wrap items-center gap-y-2.5 rounded-sm border border-[#DDE7F2] bg-[#F8FAFC] px-4 py-3">
        <p class="boblift-kicker me-4 shrink-0"><?php echo esc_html(boblift_series_text('Model code explained')); ?></p>
        <dl class="contents">
            <?php foreach ($decode['parts'] as [$code, $meaning]) : ?>
                <div class="flex min-w-0 items-center gap-2 border-s border-[#D5DEE9] px-4"<?php echo $code === 'J' ? ' data-cfg-decode-winch' . ($decode['has_winch'] ? '' : ' style="display:none"') : ''; ?>>
                    <dt class="inline-flex h-6 min-w-6 shrink-0 items-center justify-center rounded-sm bg-[#071f42] px-1.5 text-[12px] font-black leading-none text-white [font-variant-numeric:tabular-nums]" dir="ltr"><?php echo esc_html($code); ?></dt>
                    <dd class="text-[14px] leading-5 text-slate-600"><?php echo esc_html($meaning); ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
    </div>
    <?php if ($decode['former'] !== '') : ?><p class="mt-2 text-[13px] leading-5 text-slate-500"><?php echo esc_html(str_replace('{model}', $decode['former'], boblift_series_text('Former model no. {model}'))); ?></p><?php endif; ?>
    <?php
}

/** Each group a bordered box, a pale label block with its icon on the start side, figures in two columns beside it. */
function boblift_specs_render_group_boxes(array $sections, string $weight_label, string $winch_label): void
{
    $icons = ['lifting' => 'weight', 'hydraulics' => 'sliders-horizontal', 'machine' => 'layers'];
    ?>
    <div class="grid gap-3">
        <?php foreach ($sections as $section) :
            // A long value (a span, a bolt pattern) takes a whole line after the short ones instead of wrapping in half a line.
            $is_long = static fn(array $row): bool => boblift_text_width(trim((string) $row['value'])) > 11;
            $short = array_values(array_filter($section['rows'], static fn(array $row): bool => !$is_long($row)));
            $rows = array_merge($short, array_values(array_filter($section['rows'], $is_long)));
            ?>
            <section class="grid overflow-hidden rounded border border-[#DDE7F2] bg-white sm:grid-cols-[9.5rem_minmax(0,1fr)]">
                <?php if (($section['label'] ?? '') !== '') : ?>
                    <h3 class="flex items-center gap-2.5 border-b border-[#E6EDF5] bg-[#F5F8FC] px-4 py-3 text-[13px] font-black uppercase leading-5 tracking-[0.08em] text-[#071f42] rtl:tracking-normal sm:flex-col sm:items-start sm:justify-center sm:border-b-0 sm:border-e sm:py-4">
                        <?php echo boblift_icon($icons[$section['key'] ?? ''] ?? 'list', 'h-6 w-6 shrink-0 text-[#0B3F7D]', '1.75'); ?>
                        <span><?php echo esc_html($section['label']); ?></span>
                    </h3>
                <?php endif; ?>
                <dl class="grid gap-px bg-[#E6EDF5] sm:grid-cols-2 <?php echo ($section['label'] ?? '') === '' ? 'sm:col-span-2' : ''; ?>">
                    <?php foreach ($rows as $index => $row) :
                        if ($index === count($short) && count($short) % 2 === 1) : ?><div class="hidden bg-white sm:block" aria-hidden="true"></div><?php endif; ?>
                        <div class="flex min-w-0 items-baseline justify-between gap-3 bg-white px-4 py-3 <?php echo $index >= count($short) ? 'sm:col-span-2' : ''; ?>"<?php echo boblift_specs_row_attributes($row, $weight_label, $winch_label); ?>>
                            <dt class="min-w-0 text-[14px] leading-5 text-slate-500"><?php echo esc_html($row['label']); ?></dt>
                            <dd class="shrink-0 whitespace-nowrap text-end font-black leading-6 text-[#071f42] [font-variant-numeric:tabular-nums]"><span dir="ltr"><?php echo boblift_specs_value_html((string) $row['value'], 'text-[18px]'); ?></span></dd>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($rows) === count($short) && count($short) % 2 === 1) : ?><div class="hidden bg-white sm:block" aria-hidden="true"></div><?php endif; ?>
                </dl>
            </section>
        <?php endforeach; ?>
    </div>
    <?php
}

