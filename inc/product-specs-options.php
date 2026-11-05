<?php
/**
 * TEMPORARY: three layouts for a product page's technical block, shown with ?specs=a|b|c next to the unchanged default.
 * Remove this file and its calls once one is chosen.
 */

defined('ABSPATH') || exit;

function boblift_specs_option(): string
{
    $option = isset($_GET['specs']) ? sanitize_key(wp_unslash((string) $_GET['specs'])) : '';
    return in_array($option, ['a', 'b', 'c'], true) ? $option : '';
}

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

/** The model code in the order it is written: SQ · 1 · Z · A3 · +1. */
function boblift_specs_render_decode_line(string $model, string $winch, int $english_id, string $class = ''): void
{
    $decode = boblift_series_decode_parts($model, $winch, $english_id);
    if (!$decode['parts']) {
        return;
    }
    ?>
    <dl class="flex flex-wrap items-baseline gap-x-2 gap-y-1 text-[14px] leading-6 <?php echo esc_attr($class); ?>">
        <?php foreach ($decode['parts'] as $index => [$code, $meaning]) : ?>
            <div class="inline-flex min-w-0 items-baseline gap-1.5"<?php echo $code === 'J' ? ' data-cfg-decode-winch' . ($decode['has_winch'] ? '' : ' style="display:none"') : ''; ?>>
                <?php if ($index > 0) : ?><span class="text-slate-400" aria-hidden="true">·</span><?php endif; ?>
                <dt class="font-black text-[#071f42]" dir="ltr"><?php echo esc_html($code); ?></dt>
                <dd class="text-slate-500"><?php echo esc_html($meaning); ?></dd>
            </div>
        <?php endforeach; ?>
    </dl>
    <?php if ($decode['former'] !== '') : ?><p class="mt-1 text-[13px] leading-5 text-slate-500"><?php echo esc_html(str_replace('{model}', $decode['former'], boblift_series_text('Former model no. {model}'))); ?></p><?php endif; ?>
    <?php
}

/** The same parts as a short list, for the note that opens from the model name. */
function boblift_specs_render_decode_list(string $model, string $winch, int $english_id): void
{
    $decode = boblift_series_decode_parts($model, $winch, $english_id);
    ?>
    <dl class="mt-2 grid gap-1.5 text-[14px] leading-5">
        <?php foreach ($decode['parts'] as [$code, $meaning]) : ?>
            <div class="grid grid-cols-[3rem_minmax(0,1fr)] gap-2"<?php echo $code === 'J' ? ' data-cfg-decode-winch' . ($decode['has_winch'] ? '' : ' style="display:none"') : ''; ?>>
                <dt class="font-black text-[#071f42]" dir="ltr"><?php echo esc_html($code); ?></dt>
                <dd class="text-slate-600"><?php echo esc_html($meaning); ?></dd>
            </div>
        <?php endforeach; ?>
    </dl>
    <?php
}

/** Option A, and B on a page with one configuration: one table, name and value in two equal columns close together. */
function boblift_specs_render_table(array $sections, string $weight_label, string $winch_label): void
{
    foreach ($sections as $section) :
        if (($section['label'] ?? '') !== '') : ?>
            <h3 class="border-b border-[#E6EDF5] bg-[#F8FAFC] px-4 py-2 text-[12px] font-black uppercase tracking-[0.14em] text-slate-500 rtl:tracking-normal sm:px-6 md:px-8"><?php echo esc_html($section['label']); ?></h3>
        <?php endif; ?>
        <dl>
            <?php foreach ($section['rows'] as $row) : ?>
                <div class="grid grid-cols-2 items-baseline gap-x-4 border-b border-[#E6EDF5] px-4 py-2.5 sm:px-6 md:px-8"<?php echo boblift_specs_row_attributes($row, $weight_label, $winch_label); ?>>
                    <dt class="min-w-0 text-[14px] leading-5 text-slate-500"><?php echo esc_html($row['label']); ?></dt>
                    <dd class="min-w-0 break-words font-bold leading-5 text-[#071f42] [font-variant-numeric:tabular-nums]"><?php echo boblift_specs_value_html((string) $row['value']); ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
    <?php endforeach;
}

/** Option C: the three groups as compact lists, parted by hairlines only. */
function boblift_specs_render_groups(array $sections, string $weight_label, string $winch_label): void
{
    ?>
    <div class="grid grid-cols-1 gap-px border-b border-[#E6EDF5] bg-[#E6EDF5] sm:grid-cols-2 min-[1700px]:grid-cols-3 <?php echo count($sections) === 3 ? 'sm:[&>*:first-child]:row-span-2 min-[1700px]:[&>*:first-child]:row-span-1' : ''; ?>">
        <?php foreach ($sections as $section) : ?>
            <section class="min-w-0 bg-white px-4 py-5 sm:px-6 md:px-8">
                <?php if (($section['label'] ?? '') !== '') : ?><h3 class="text-[12px] font-black uppercase tracking-[0.14em] text-slate-500 rtl:tracking-normal"><?php echo esc_html($section['label']); ?></h3><?php endif; ?>
                <dl class="mt-2">
                    <?php foreach ($section['rows'] as $row) : ?>
                        <div class="flex items-baseline justify-between gap-3 border-b border-[#EEF3F8] py-2 last:border-b-0"<?php echo boblift_specs_row_attributes($row, $weight_label, $winch_label); ?>>
                            <dt class="min-w-0 text-[14px] leading-5 text-slate-500"><?php echo esc_html($row['label']); ?></dt>
                            <dd class="min-w-0 break-words text-end font-bold leading-5 text-[#071f42] [font-variant-numeric:tabular-nums]"><?php echo boblift_specs_value_html((string) $row['value']); ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            </section>
        <?php endforeach; ?>
    </div>
    <?php
}

/**
 * Option B: rows are figures, columns are configurations. Every cell carries data-cfg-row, so the theme script's comparison code
 * marks the shown column and a click on it switches the page, as a row of the comparison table does.
 */
function boblift_specs_matrix_html(array $state): string
{
    $members = boblift_series_members_with_figures($state['series']);
    $dimensions = boblift_series_dimensions($members);
    $labelled = (bool) array_intersect(['sections', 'winch', 'manual'], array_keys($dimensions));
    $tables = (array) ($state['spec_tables'] ?? []);
    $groups = [];
    foreach ($members as $member) {
        foreach ((array) ($tables[$member['model']] ?? []) as $section) {
            $group = (string) ($section['key'] ?? 'all');
            $groups[$group] ??= ['label' => (string) ($section['label'] ?? ''), 'rows' => []];
            foreach ($section['rows'] as $row) {
                $key = (string) ($row['en'] ?? $row['label']);
                $groups[$group]['rows'][$key] ??= ['label' => (string) $row['label'], 'values' => []];
                $groups[$group]['rows'][$key]['values'][$member['model']] = trim((string) $row['value']);
            }
        }
    }
    $columns = count($members);
    ob_start();
    ?>
    <div data-cfg-compare class="border-b border-[#E6EDF5]">
        <div class="overflow-x-auto [scrollbar-width:thin]">
            <table class="w-full border-collapse text-[14px] [font-variant-numeric:tabular-nums]" style="min-width:<?php echo esc_attr((string) (190 + 108 * $columns)); ?>px">
                <thead>
                    <tr class="border-b border-[#D5DEE9]">
                        <td class="sticky start-0 z-[1] w-[34%] min-w-[140px] bg-white ps-4 sm:min-w-[190px] sm:ps-6 md:ps-8"></td>
                        <?php foreach ($members as $member) : ?>
                            <th class="cursor-pointer px-3 py-3 text-start align-top transition-colors aria-[current=true]:bg-[#eaf1fb]" scope="col" data-cfg-row="<?php echo esc_attr($member['model']); ?>"<?php echo $member['model'] === $state['active_source'] ? ' aria-current="true"' : ''; ?>>
                                <a class="inline-block whitespace-nowrap text-[15px] font-black text-[#071f42] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400" dir="ltr" href="<?php echo esc_url((string) $member['url']); ?>"><?php echo esc_html($member['model']); ?></a>
                                <?php if ($labelled) : ?><span class="mt-0.5 block text-[13px] font-normal leading-4 text-slate-500"><?php echo esc_html(boblift_series_compare_label($member['values'], $dimensions, (string) $state['series']['family'])); ?></span><?php endif; ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groups as $group) : ?>
                        <?php if ($group['label'] !== '') : ?>
                            <tr><th class="border-b border-[#E6EDF5] bg-[#F8FAFC] px-4 py-2 text-start text-[12px] font-black uppercase tracking-[0.14em] text-slate-500 rtl:tracking-normal sm:px-6 md:px-8" colspan="<?php echo esc_attr((string) ($columns + 1)); ?>" scope="colgroup"><?php echo esc_html($group['label']); ?></th></tr>
                        <?php endif; ?>
                        <?php foreach ($group['rows'] as $row) :
                            $differs = count(array_unique(array_map(static fn(array $member): string => $row['values'][$member['model']] ?? '', $members))) > 1;
                            ?>
                            <tr class="border-b border-[#E6EDF5]">
                                <th class="sticky start-0 z-[1] bg-white py-2.5 pe-3 ps-4 text-start font-normal leading-5 text-slate-500 sm:ps-6 md:ps-8" scope="row"><?php echo esc_html($row['label']); ?></th>
                                <?php foreach ($members as $member) :
                                    $value = $row['values'][$member['model']] ?? '';
                                    ?>
                                    <td class="cursor-pointer px-3 py-2.5 leading-5 transition-colors aria-[current=true]:bg-[#eaf1fb] <?php echo $differs ? 'font-black text-[#071f42]' : 'font-normal text-slate-600'; ?>" data-cfg-row="<?php echo esc_attr($member['model']); ?>"<?php echo $member['model'] === $state['active_source'] ? ' aria-current="true"' : ''; ?>>
                                        <?php echo $value === '' ? '<span class="font-normal text-slate-400">—</span>' : '<span dir="ltr">' . boblift_specs_value_html($value, 'text-[14px]') . '</span>'; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}
