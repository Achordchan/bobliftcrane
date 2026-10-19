<?php
/**
 * How BOB-LIFT model names are built (rules from the user, 2026-10-09).
 *
 * Truck cranes: SQ + capacity class in tons + Z (knuckle) or S (telescopic) + A and the boom count; marine versions
 * add T, a winch adds J, flip-over outriggers add F. Forklift fly jibs: CC + lifting height class.
 * Only these patterns are decoded; SQZ, BLZ, timber, spider and other names stay as they are until their rules are known.
 * Product pages show the parts and link to the key on the product archive (#model-names).
 */

/** [code, meaning] pairs for a model name, or [] when its pattern is not known yet. */
function boblift_model_parts(string $model): array
{
    $t = static fn(string $text): string => boblift_localized_static_text($text);
    if (preg_match('/^SQ(?<capacity>\d+(?:\.\d+)?)(?<boom>Z|S)A(?<count>\d)(?<marine>T?)(?<winch>J?)(?<flip>F?)$/', str_replace('+1', '', $model), $m)) {
        $capacity = str_starts_with($m['capacity'], '0') && !str_contains($m['capacity'], '.') ? '0.' . substr($m['capacity'], 1) : $m['capacity'];
        $hydraulic = (int) $m['count'] - 1;
        $parts = [
            ['SQ', $t('Truck mounted crane')],
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
