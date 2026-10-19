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
    <div class="boblift-model-decode mt-5 max-w-[620px]">
        <dl class="grid grid-cols-2 gap-x-5 gap-y-2.5">
            <?php foreach ($parts as [$code, $meaning]) : ?>
                <div class="flex min-w-0 items-start gap-2">
                    <dt class="inline-flex h-6 min-w-6 shrink-0 items-center justify-center rounded-sm bg-[#071f42] px-1.5 text-[12px] font-black leading-none tracking-[0.02em] text-white [font-variant-numeric:tabular-nums]"><?php echo esc_html($code); ?></dt>
                    <dd class="min-w-0 pt-[3px] text-[13px] font-normal leading-[1.35] text-slate-600"><?php echo esc_html($meaning); ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
        <a class="mt-3 inline-flex items-center gap-1 text-xs font-black text-blue-700 transition hover:text-blue-900" href="<?php echo esc_url(boblift_product_archive_url() . '#model-names'); ?>"><?php echo esc_html(boblift_localized_static_text('How model names work')); ?><?php echo boblift_icon('arrow-right', 'h-3.5 w-3.5'); ?></a>
    </div>
    <?php
}

/** "How to read a model name" on the product archive: each example name split into its parts, one caption under each. */
function boblift_render_model_key(): void
{
    $t = static fn(string $text): string => boblift_localized_static_text($text);
    // Parts are [code, meaning, kind]: "base" is the truck crane name a variant builds on, "suffix" is added only when it applies.
    $truck = [$t('Truck mounted cranes'), [
        ['SQ', $t('Truck mounted crane'), ''],
        ['8', $t('Capacity class in tons (08 = 0.8 t)'), ''],
        ['Z / S', $t('Z knuckle boom, S telescopic boom'), ''],
        ['A5', $t('Boom count: 1 main boom + 4 hydraulic booms'), ''],
        ['F', $t('Flip-over outriggers'), 'suffix'],
    ]];
    $variants = [
        [$t('Marine cranes'), [
            ['SQ8ZA6', '', 'base'],
            ['T', $t('Marine version of the truck crane model'), ''],
            ['J', $t('With winch'), 'suffix'],
        ]],
        [$t('Forklift fly jib cranes'), [
            ['CC', $t('Forklift fly jib crane'), ''],
            ['85', $t('Maximum lifting height class'), ''],
        ]],
    ];
    // Phones: one row per part. From sm up the codes join into one name, each caption hanging under its code.
    $strip = static function (array $parts, bool $large) : void { ?>
        <ol class="grid gap-2 sm:auto-cols-fr sm:grid-flow-col sm:gap-0">
            <?php foreach ($parts as $index => $part) :
                // A part without a kind is a plain code.
                [$code, $meaning, $kind] = $part + [0 => '', 1 => '', 2 => ''];
                $box = 'flex items-center justify-center border font-black tracking-[-0.01em] [font-variant-numeric:tabular-nums] sm:rounded-none';
                // The long base name gets a smaller size on phones so it clears the narrow code box.
                $box .= $kind === 'base' ? ' h-14 text-lg' : ' h-14 text-2xl';
                $box .= $large ? ' sm:h-24 sm:text-[clamp(2rem,3.4vw,3.5rem)]' : ' sm:h-[4.5rem] sm:text-[clamp(1.5rem,2.4vw,2.5rem)]';
                $box .= $kind === 'base' ? ' bg-[#F1F5F9] text-slate-400' : ' bg-white text-[#071f42]';
                $box .= $kind === 'suffix' ? ' relative z-[1] border-dashed border-slate-400' : ' border-[#C9D6E6]';
                $box .= ' rounded-sm' . ($index > 0 ? ' sm:-ms-px' : ' sm:rounded-s-sm') . ($index === count($parts) - 1 ? ' sm:rounded-e-sm' : '');
                ?>
                <li class="grid grid-cols-[5.5rem_minmax(0,1fr)] items-center gap-4 sm:grid-cols-1 sm:items-start sm:gap-0">
                    <span class="<?php echo esc_attr($box); ?>"><?php echo esc_html($code); ?></span>
                    <?php if ($meaning !== '') : ?>
                        <span class="text-sm font-normal leading-6 text-slate-600 sm:px-4 sm:pt-4 sm:text-center sm:before:mx-auto sm:before:mb-3 sm:before:block sm:before:h-4 sm:before:w-px sm:before:bg-[#C9D6E6] sm:before:content-['']"><?php echo esc_html($meaning); ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php };
    ?>
    <section id="model-names" class="scroll-mt-24 border-t border-line bg-white py-12 md:py-16 lg:py-20" aria-labelledby="model-names-title">
        <div class="mx-auto max-w-[2320px] px-4 sm:px-6 lg:px-12 min-[1780px]:px-32">
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:items-end lg:gap-16">
                <div>
                    <p class="section-label mb-3"><?php echo esc_html($t('How to read a model name')); ?></p>
                    <h2 id="model-names-title" class="text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em] text-ink"><?php echo esc_html($t('The name tells you what the crane is.')); ?></h2>
                </div>
                <p class="max-w-xl text-sm font-normal leading-7 text-slate-600 md:text-base"><?php echo esc_html($t('SQ8ZA5 is an 8 t class knuckle boom truck crane with one main boom and four hydraulic booms. SQ8ZA6TJ is the marine version with a winch.')); ?></p>
            </div>
            <div class="mt-8 rounded border border-[#DDE7F2] bg-[#F8FAFC] p-5 md:mt-10 md:p-8">
                <p class="boblift-kicker mb-5 md:mb-6"><?php echo esc_html($truck[0]); ?></p>
                <?php $strip($truck[1], true); ?>
            </div>
            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                <?php foreach ($variants as [$title, $parts]) : ?>
                    <div class="rounded border border-[#DDE7F2] p-5 md:p-8">
                        <p class="boblift-kicker mb-5 md:mb-6"><?php echo esc_html($title); ?></p>
                        <?php $strip($parts, false); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
}
