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

/** The decoded name under the product title, linking to the key. */
function boblift_render_model_decode(string $model): void
{
    $parts = boblift_model_parts($model);
    if (!$parts) {
        return;
    }
    ?>
    <p class="mt-4 flex flex-wrap items-center gap-x-2 gap-y-2 text-xs font-semibold text-slate-600">
        <?php foreach ($parts as [$code, $meaning]) : ?>
            <span class="inline-flex items-baseline gap-1.5 rounded-sm border border-[#D5DEE9] bg-white px-2.5 py-1"><b class="font-black text-[#071f42]"><?php echo esc_html($code); ?></b><?php echo esc_html($meaning); ?></span>
        <?php endforeach; ?>
        <a class="font-black text-blue-700 hover:text-blue-900" href="<?php echo esc_url(boblift_product_archive_url() . '#model-names'); ?>"><?php echo esc_html(boblift_localized_static_text('How model names work')); ?></a>
    </p>
    <?php
}

/** "How to read a model name" on the product archive. */
function boblift_render_model_key(): void
{
    $t = static fn(string $text): string => boblift_localized_static_text($text);
    $groups = [
        [$t('Truck mounted cranes'), 'SQ8ZA5', [
            ['SQ', $t('Truck mounted crane')],
            ['8', $t('Capacity class in tons (08 = 0.8 t)')],
            ['Z / S', $t('Z knuckle boom, S telescopic boom')],
            ['A5', $t('Boom count: 1 main boom + 4 hydraulic booms')],
            ['F', $t('Flip-over outriggers')],
        ]],
        [$t('Marine cranes'), 'SQ8ZA6TJ', [
            ['T', $t('Marine version of the truck crane model')],
            ['J', $t('With winch')],
        ]],
        [$t('Forklift fly jib cranes'), 'CC85', [
            ['CC', $t('Forklift fly jib crane')],
            ['85', $t('Maximum lifting height class')],
        ]],
    ];
    ?>
    <section id="model-names" class="scroll-mt-24 border-t border-line bg-white py-12 md:py-16" aria-labelledby="model-names-title">
        <div class="page-shell grid gap-8 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,2fr)] lg:gap-12">
            <div class="lg:sticky lg:top-24 lg:self-start">
                <p class="section-label mb-3"><?php echo esc_html($t('How to read a model name')); ?></p>
                <h2 id="model-names-title" class="text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em] text-ink"><?php echo esc_html($t('The name tells you what the crane is.')); ?></h2>
                <p class="mt-4 max-w-md text-sm font-semibold leading-7 text-slate-600"><?php echo esc_html($t('SQ8ZA5 is an 8 t class knuckle boom truck crane with one main boom and four hydraulic booms. SQ8ZA6TJ is the marine version with a winch.')); ?></p>
            </div>
            <div class="grid gap-4 md:grid-cols-3">
                <?php foreach ($groups as [$title, $example, $parts]) : ?>
                    <div class="rounded border border-[#DDE7F2] p-5">
                        <p class="text-xs font-black uppercase tracking-[0.14em] text-blue-700"><?php echo esc_html($title); ?></p>
                        <p class="mt-2 text-2xl font-black tracking-[-0.01em] text-[#071f42]"><?php echo esc_html($example); ?></p>
                        <dl class="mt-4 grid gap-2.5">
                            <?php foreach ($parts as [$code, $meaning]) : ?>
                                <div class="grid grid-cols-[3.5rem_minmax(0,1fr)] gap-3 text-sm"><dt class="font-black text-[#071f42]"><?php echo esc_html($code); ?></dt><dd class="font-semibold leading-6 text-slate-600"><?php echo esc_html($meaning); ?></dd></div>
                            <?php endforeach; ?>
                        </dl>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
}
