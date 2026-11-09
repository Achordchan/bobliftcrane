<?php
/**
 * How BOB-LIFT model names are built (rules from the user, 2026-10-09).
 *
 * Truck cranes: SQ + capacity class in tons + Z (knuckle) or S (telescopic) + A and the boom count; marine versions
 * add T, a winch adds J, flip-over outriggers add F. Forklift fly jibs: CC + lifting height class.
 * Electric mini cranes: SQ + capacity + Z (knuckle) + -E (electric) + M (manual levers) or R (wireless remote).
 * Only these patterns are decoded; other SQZ, BLZ, timber, spider and other names stay as they are until their rules are known.
 * Product pages show the parts and link to the key on the product archive (#model-names).
 */

/** [code, meaning] pairs for a model name, or [] when its pattern is not known yet. */
function boblift_model_parts(string $model): array
{
    $t = static fn(string $text): string => boblift_localized_static_text($text);
    if (preg_match('/^SQ(?<capacity>\d+(?:\.\d+)?)(?<boom>Z|S)A(?<count>\d)(?<marine>T?)(?<winch>J?)(?<flip>F?)$/', str_replace('+1', '', $model), $m)) {
        $capacity = str_starts_with($m['capacity'], '0') && !str_contains($m['capacity'], '.') ? '0.' . substr($m['capacity'], 1) : $m['capacity'];
        // "1" names the 1.6 t class (SQ1ZA3+1, SQ1ZA4, SQ1ZA3T+1; technical department, 2026-11-09).
        if ($capacity === '1') {
            $capacity = '1.6';
        }
        $hydraulic = (int) $m['count'] - 1;
        $parts = [
            // SQ is the maker's prefix, on truck and marine versions alike.
            ['SQ', $t('BOB-LIFT crane')],
            [$m['capacity'], str_replace('{capacity}', $capacity, $t('{capacity} t capacity class'))],
            [$m['boom'], $t($m['boom'] === 'Z' ? 'Knuckle boom' : 'Telescopic boom')],
            ['A' . $m['count'], str_replace('{count}', (string) $hydraulic, $t($hydraulic === 1 ? '1 main boom + 1 hydraulic boom' : '1 main boom + {count} hydraulic booms'))],
        ];
        if ($m['marine'] !== '') {
            $parts[] = ['T', $t('Marine version')];
        }
        if ($m['winch'] !== '') {
            $parts[] = ['J', $t('With winch')];
        }
        if ($m['flip'] !== '') {
            $parts[] = ['F', $t('Flip-over outriggers')];
        }
        if (str_ends_with($model, '+1')) {
            $parts[] = ['+1', $t('1 added manual extension')];
        }
        return $parts;
    }
    // Electric mini cranes (2026-11-02): SQ + capacity in tons + Z + -E (electric drive) + M (manual levers) or R (wireless
    // remote). One page covers both controls (SQ1Z-E), so a name without M or R shows both. Z is the knuckle (folding) boom.
    if (preg_match('/^SQ(?<capacity>\d+(?:\.\d+)?)Z-E(?<control>[MR]?)$/', $model, $m)) {
        $control = match ($m['control']) {
            'M' => ['M', $t('Manual levers')],
            'R' => ['R', $t('Wireless remote')],
            default => ['M / R', $t('M manual levers, R wireless remote')],
        };
        return [
            ['SQ', $t('BOB-LIFT crane')],
            [$m['capacity'], str_replace('{capacity}', $m['capacity'], $t('{capacity} t capacity class'))],
            ['Z', $t('Knuckle boom')],
            ['E', $t('Electric drive')],
            $control,
        ];
    }
    // SQZ models carry a lifting moment class instead of a capacity: close to the moment in kN·m, not equal to it.
    if (preg_match('/^SQZ(?<moment>\d{3,4})(?<marine>T?)(?<winch>J?)$/', $model, $m)) {
        $parts = [
            ['SQ', $t('BOB-LIFT crane')],
            ['Z', $t('Knuckle boom')],
            [$m['moment'], $t('Lifting moment class')],
        ];
        if ($m['marine'] !== '') {
            $parts[] = ['T', $t('Marine version')];
        }
        if ($m['winch'] !== '') {
            $parts[] = ['J', $t('With winch')];
        }
        return $parts;
    }
    if (preg_match('/^CC(\d+)$/', $model, $m)) {
        return [['CC', $t('Forklift fly jib crane')], [$m[1], $t('Maximum lifting height class')]];
    }
    return [];
}

/** The decoded name under the product title. */
function boblift_render_model_decode(string $model): void
{
    $parts = boblift_model_parts($model);
    if (!$parts) {
        return;
    }
    ?>
    <div class="boblift-model-decode mt-5 max-w-[620px]">
        <dl class="grid grid-cols-2 gap-x-5 gap-y-2.5">
            <?php foreach ($parts as [$code, $meaning]) : ?>
                <div class="flex min-w-0 items-start gap-2">
                    <dt class="inline-flex h-6 min-w-6 shrink-0 items-center justify-center rounded-sm bg-[#071f42] px-1.5 text-[12px] font-black leading-none tracking-[0.02em] text-white [font-variant-numeric:tabular-nums]"><?php echo esc_html($code); ?></dt>
                    <dd class="min-w-0 pt-[3px] text-[13px] font-normal leading-[1.35] text-slate-600"><?php echo esc_html($meaning); ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
    </div>
    <?php
}

/**
 * The orderable versions of a model that one product page covers, for the version choice under the product title
 * (user, 2026-11-02): [['model' => 'SQ2Z-ER', 'code' => 'R', 'label' => 'Wireless remote', 'default' => true], ...].
 * Only electric mini cranes (SQ1Z-E: SQ1Z-EM manual levers, SQ1Z-ER wireless remote) have them; the wireless version is
 * the default because the photos show it. Empty for every other model.
 */
function boblift_model_variants(string $model): array
{
    if (!preg_match('/^SQ\d+(?:\.\d+)?Z-E$/', $model)) {
        return [];
    }
    return [
        ['model' => $model . 'M', 'code' => 'M', 'label' => boblift_localized_static_text('Manual levers'), 'default' => false],
        ['model' => $model . 'R', 'code' => 'R', 'label' => boblift_localized_static_text('Wireless remote'), 'default' => true],
    ];
}

/** The version of $model that is chosen when the page opens, or null. */
function boblift_model_default_variant(string $model): ?array
{
    foreach (boblift_model_variants($model) as $variant) {
        if ($variant['default']) {
            return $variant;
        }
    }
    return null;
}

/** The version choice under the product title: a radio group the theme script wires to the quote buttons. */
function boblift_render_model_variants(string $model, string $product_slug): void
{
    $variants = boblift_model_variants($model);
    if (!$variants) {
        return;
    }
    $legend = boblift_localized_static_text('Choose the controls');
    ?>
    <div class="boblift-model-variants mt-5" data-product-variants data-product-variants-slug="<?php echo esc_attr($product_slug); ?>" data-product-variants-base="<?php echo esc_attr($model); ?>">
        <p class="text-[11px] font-black uppercase leading-4 tracking-[0.12em] text-slate-500 rtl:tracking-normal" id="boblift-model-variants-label"><?php echo esc_html($legend); ?></p>
        <div class="mt-2 flex flex-wrap gap-2" role="radiogroup" aria-labelledby="boblift-model-variants-label">
            <?php foreach ($variants as $variant) : $checked = $variant['default']; ?>
                <button class="boblift-model-variant inline-flex min-h-11 flex-col items-start justify-center gap-0.5 rounded-sm border px-3.5 py-2 text-start transition sm:flex-row sm:items-center sm:gap-2 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-blue-100 <?php echo $checked ? 'border-[#071f42] bg-[#071f42] text-white' : 'border-[#D5DEE9] bg-white text-[#071f42] hover:border-blue-300'; ?>" type="button" role="radio" aria-checked="<?php echo $checked ? 'true' : 'false'; ?>" tabindex="<?php echo $checked ? '0' : '-1'; ?>" data-product-variant="<?php echo esc_attr($variant['model']); ?>" data-product-variant-code="<?php echo esc_attr($variant['code']); ?>" data-product-variant-label="<?php echo esc_attr($variant['label']); ?>">
                    <span class="text-sm font-black [font-variant-numeric:tabular-nums]"><?php echo esc_html($variant['model']); ?></span>
                    <span class="text-[13px] font-normal opacity-80"><?php echo esc_html($variant['label']); ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}
