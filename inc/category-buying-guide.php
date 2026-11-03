<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * A product category's buying guide: numbered points on choosing a model, then frequently asked questions, shown on the
 * category page between the range comparison and the factory steps (boblift_render_archive_extras()). Each Polylang
 * language term holds its own copy in the term meta _boblift_buying_guide, a JSON string edited on the category screen or
 * through REST: POST /wp/v2/product_category/<id> with {"meta": {"_boblift_buying_guide": "{...}"}}.
 *   {"eyebrow": "", "title": "", "intro": "", "points": [{"title": "", "text": ""}], "faq": [{"q": "", "a": ""}]}
 * intro, points[].text and faq[].a may hold links, bold and italics (a[href], strong, em); everything else is plain text.
 */

/** The tags the guide's longer texts may keep. */
function boblift_buying_guide_allowed_html(): array
{
    return ['a' => ['href' => true], 'strong' => [], 'em' => []];
}

/** Keeps only the guide's own keys and allowed markup; anything that is not a JSON object is stored as ''. */
function boblift_buying_guide_sanitize($value): string
{
    // update_metadata() has already unslashed the value; unslashing again would break escaped quotes in the JSON.
    $data = json_decode(is_string($value) ? $value : '', true);
    if (!is_array($data)) {
        return '';
    }
    $plain = static fn($text): string => trim(sanitize_text_field((string) $text));
    $rich = static fn($text): string => trim(wp_kses((string) $text, boblift_buying_guide_allowed_html()));
    $guide = [
        'eyebrow' => $plain($data['eyebrow'] ?? ''),
        'title' => $plain($data['title'] ?? ''),
        'intro' => $rich($data['intro'] ?? ''),
        'points' => [],
        'faq' => [],
    ];
    foreach ((array) ($data['points'] ?? []) as $point) {
        if (is_array($point) && $plain($point['title'] ?? '') !== '') {
            $guide['points'][] = ['title' => $plain($point['title']), 'text' => $rich($point['text'] ?? '')];
        }
    }
    foreach ((array) ($data['faq'] ?? []) as $item) {
        if (is_array($item) && $plain($item['q'] ?? '') !== '' && $rich($item['a'] ?? '') !== '') {
            $guide['faq'][] = ['q' => $plain($item['q']), 'a' => $rich($item['a'])];
        }
    }
    if ($guide['title'] === '' && !$guide['points'] && !$guide['faq']) {
        return '';
    }
    return (string) wp_json_encode($guide, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function boblift_register_buying_guide_meta(): void
{
    register_term_meta('product_category', '_boblift_buying_guide', [
        'type' => 'string',
        'single' => true,
        'default' => '',
        'show_in_rest' => true,
        'sanitize_callback' => 'boblift_buying_guide_sanitize',
        'auth_callback' => static fn(): bool => current_user_can('manage_categories'),
    ]);
}
add_action('init', 'boblift_register_buying_guide_meta');

/**
 * Adds questions to a guide's FAQ without resending the whole guide: write a JSON list [{"q": "", "a": ""}] to the term
 * meta _boblift_buying_guide_faq_add (REST: {"meta": {"_boblift_buying_guide_faq_add": "[...]"}}). Each question whose
 * text is not already in the FAQ (case-insensitive) is appended to _boblift_buying_guide, then the key is cleared, so it
 * reads back as '' and the next write is applied again.
 */
function boblift_buying_guide_faq_add_sanitize($value): string
{
    $items = json_decode(is_string($value) ? $value : '', true);
    if (!is_array($items) || ($items !== [] && !isset($items[0]))) {
        return '';
    }
    $items = array_values(array_filter($items, static fn($item): bool => is_array($item) && trim((string) ($item['q'] ?? '')) !== '' && trim((string) ($item['a'] ?? '')) !== ''));
    return $items ? (string) wp_json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
}

function boblift_register_buying_guide_faq_add_meta(): void
{
    register_term_meta('product_category', '_boblift_buying_guide_faq_add', [
        'type' => 'string',
        'single' => true,
        'default' => '',
        'show_in_rest' => true,
        'sanitize_callback' => 'boblift_buying_guide_faq_add_sanitize',
        'auth_callback' => static fn(): bool => current_user_can('manage_categories'),
    ]);
}
add_action('init', 'boblift_register_buying_guide_faq_add_meta');

function boblift_apply_buying_guide_faq_add($meta_id, $term_id, $meta_key, $meta_value): void
{
    if ($meta_key !== '_boblift_buying_guide_faq_add') {
        return;
    }
    $term_id = (int) $term_id;
    $items = json_decode((string) $meta_value, true);
    delete_term_meta($term_id, '_boblift_buying_guide_faq_add');
    if (!is_array($items) || $items === []) {
        return;
    }
    $guide = json_decode((string) get_term_meta($term_id, '_boblift_buying_guide', true), true);
    $guide = is_array($guide) ? $guide : [];
    $faq = array_values((array) ($guide['faq'] ?? []));
    $asked = array_map(static fn($item): string => mb_strtolower(trim((string) ($item['q'] ?? ''))), $faq);
    foreach ($items as $item) {
        $question = mb_strtolower(trim((string) ($item['q'] ?? '')));
        if ($question !== '' && !in_array($question, $asked, true)) {
            $faq[] = ['q' => (string) $item['q'], 'a' => (string) ($item['a'] ?? '')];
            $asked[] = $question;
        }
    }
    $guide['faq'] = $faq;
    // update_metadata() unslashes the value; slash it so the escaped quotes inside the JSON survive.
    update_term_meta($term_id, '_boblift_buying_guide', wp_slash((string) wp_json_encode($guide, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
}
add_action('added_term_meta', 'boblift_apply_buying_guide_faq_add', 10, 4);
add_action('updated_term_meta', 'boblift_apply_buying_guide_faq_add', 10, 4);

/** The guide of a category term (its own language), or [] when it has none. */
function boblift_buying_guide(WP_Term $term): array
{
    $guide = json_decode((string) get_term_meta($term->term_id, '_boblift_buying_guide', true), true);
    if (!is_array($guide) || (trim((string) ($guide['title'] ?? '')) === '' && empty($guide['points']) && empty($guide['faq']))) {
        return [];
    }
    return $guide;
}

/** $is_current: the page's own category, which alone gets the #buying-guide anchor and the FAQPage JSON-LD. */
function boblift_render_buying_guide(WP_Term $term, bool $is_current = true): void
{
    $guide = boblift_buying_guide($term);
    if (!$guide) {
        return;
    }
    boblift_render_guide_block($guide, $is_current ? 'buying-guide' : 'buying-guide-' . $term->term_id, 'buying-guide-title-' . $term->term_id, $is_current, 'mt-14 lg:mt-20');
}

/**
 * A guide block (eyebrow, title, intro, optional key figures, numbered points, FAQ), shared by the category buying guides
 * and the page guides (inc/page-guide.php). $faq_schema prints the FAQPage JSON-LD for the questions.
 */
function boblift_render_guide_block(array $guide, string $section_id, string $title_id, bool $faq_schema, string $class = ''): void
{
    $allowed = boblift_buying_guide_allowed_html();
    $points = array_values((array) ($guide['points'] ?? []));
    $faq = array_values((array) ($guide['faq'] ?? []));
    $facts = array_values((array) ($guide['facts'] ?? []));
    ?>
    <?php // Two columns from lg on, header and body alike: the title over the points on the left, the intro over the questions on the right. ?>
    <section id="<?php echo esc_attr($section_id); ?>" class="scroll-mt-24 <?php echo esc_attr($class); ?>" aria-labelledby="<?php echo esc_attr($title_id); ?>">
        <div class="grid gap-x-12 gap-y-4 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] lg:items-end xl:gap-x-16">
            <div class="min-w-0">
                <?php if (($guide['eyebrow'] ?? '') !== '') : ?><p class="section-label"><?php echo esc_html($guide['eyebrow']); ?></p><?php endif; ?>
                <h2 id="<?php echo esc_attr($title_id); ?>" class="mt-3 max-w-[22ch] text-[clamp(1.75rem,2.8vw,3rem)] font-black leading-[1.05] tracking-[-0.015em] text-ink"><?php echo esc_html((string) (($guide['title'] ?? '') ?: boblift_localized_static_text('Buying guide'))); ?></h2>
            </div>
            <?php if (($guide['intro'] ?? '') !== '') : ?><p class="text-sm leading-7 text-slate-600 md:text-base [&_a]:font-bold [&_a]:text-blue-700 [&_a]:underline-offset-4 [&_a:hover]:underline"><?php echo wp_kses((string) $guide['intro'], $allowed); ?></p><?php endif; ?>
        </div>
        <?php if ($facts) : ?>
            <dl class="mt-8 grid grid-cols-2 gap-px overflow-hidden rounded border border-[#E5E7EB] bg-[#E5E7EB] lg:mt-10 <?php echo count($facts) % 3 === 0 ? 'md:grid-cols-3' : 'md:grid-cols-4'; ?>">
                <?php foreach ($facts as $fact) : ?>
                    <div class="flex min-w-0 flex-col bg-white px-4 py-5 md:px-6 md:py-6">
                        <dt class="order-2 mt-1.5 text-sm leading-6 text-slate-600"><?php echo esc_html((string) ($fact['label'] ?? '')); ?></dt>
                        <dd class="order-1 break-words text-2xl font-black leading-tight tracking-[-0.01em] text-ink md:text-3xl"><?php echo esc_html((string) ($fact['value'] ?? '')); ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        <?php endif; ?>
        <div class="mt-8 grid gap-x-12 gap-y-12 border-t border-[#E5E7EB] pt-8 lg:mt-10 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] lg:items-start lg:pt-10 xl:gap-x-16">
            <?php if ($points) : ?>
                <?php // The number sits on the title's baseline, so number, title and text share one left edge. ?>
                <ol class="grid min-w-0 gap-y-8">
                    <?php foreach ($points as $index => $point) : ?>
                        <li class="min-w-0">
                            <h3 class="text-lg font-black leading-snug text-ink md:text-xl"><span class="me-2 text-sm font-black text-blue-700 [font-variant-numeric:tabular-nums]"><?php echo esc_html(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></span><?php echo esc_html((string) ($point['title'] ?? '')); ?></h3>
                            <?php if (($point['text'] ?? '') !== '') : ?><p class="mt-2 text-sm leading-7 text-slate-600 md:text-base [&_a]:font-bold [&_a]:text-blue-700 [&_a]:underline-offset-4 [&_a:hover]:underline"><?php echo wp_kses((string) $point['text'], $allowed); ?></p><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
            <?php if ($faq) : ?>
                <div class="min-w-0 lg:sticky lg:top-[calc(var(--boblift-header-h)+1.25rem)]">
                    <h3 class="text-xl font-black leading-tight text-ink md:text-2xl"><?php echo esc_html(boblift_localized_static_text('Frequently asked questions')); ?></h3>
                    <div class="mt-4 border-t border-[#E5E7EB]">
                        <?php foreach ($faq as $index => $item) : ?>
                            <details class="group border-b border-[#E5E7EB]"<?php echo $index === 0 ? ' open' : ''; ?>>
                                <summary class="flex cursor-pointer list-none items-start justify-between gap-6 py-4 text-base font-black leading-snug text-ink transition hover:text-blue-700 [&::-webkit-details-marker]:hidden"><span><?php echo esc_html((string) $item['q']); ?></span><?php echo boblift_icon('chevron-down', 'mt-0.5 h-5 w-5 shrink-0 text-slate-500 transition group-open:rotate-180'); ?></summary>
                                <p class="pb-5 text-sm leading-7 text-slate-600 md:text-base [&_a]:font-bold [&_a]:text-blue-700 [&_a]:underline-offset-4 [&_a:hover]:underline"><?php echo wp_kses((string) $item['a'], $allowed); ?></p>
                            </details>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php if ($faq && $faq_schema) : ?>
            <?php
            $schema = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => array_map(static fn(array $item): array => [
                    '@type' => 'Question',
                    'name' => wp_strip_all_tags((string) $item['q']),
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => wp_strip_all_tags((string) $item['a'])],
                ], $faq),
            ];
            ?>
            <script type="application/ld+json"><?php echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG); ?></script>
        <?php endif; ?>
    </section>
    <?php
}
