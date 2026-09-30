<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Privacy Policy and Terms of Use share one reading layout: the page hero, a sticky contents list with a
 * contact card on the left and the clauses as one continuous document in the article reading style.
 */
function boblift_render_legal_page(array $options, string $inquiry_source): void
{
    $ui = boblift_site_ui('legalPage');
    $sections = array_values(array_filter(is_array($options['sections'] ?? null) ? $options['sections'] : [], static fn($item): bool => is_array($item) && trim((string) ($item['title'] ?? '')) !== ''));
    $intro = trim((string) ($options['side_text'] ?? ''));
    $contact = boblift_contact_settings();
    ?>
    <div class="bg-white text-ink">
        <div class="page-shell pt-5 md:pt-8">
            <?php
            boblift_page_hero([
                'label' => (string) ($options['label'] ?? ''),
                'title' => (string) ($options['title'] ?? ''),
                'text' => (string) ($options['description'] ?? ''),
                'meta' => trim((string) ($options['updated_label'] ?? 'Last updated:') . ' ' . (string) ($options['updated'] ?? '')),
            ]);
            ?>
        </div>

        <div class="page-shell py-8 md:py-12 lg:py-14">
            <div class="grid gap-8 lg:grid-cols-[17.5rem_minmax(0,1fr)] lg:gap-12 xl:grid-cols-[19rem_minmax(0,1fr)] 2xl:gap-16">
                <div class="min-w-0 lg:order-2">
                    <div class="boblift-article-body boblift-legal-body">
                        <?php if ($intro !== '') : ?>
                            <p class="boblift-legal-intro"><?php echo esc_html($intro); ?></p>
                        <?php endif; ?>
                        <?php foreach ($sections as $index => $section) : ?>
                            <h2 id="section-<?php echo esc_attr((string) ($index + 1)); ?>"><span class="boblift-legal-number"><?php echo esc_html(sprintf('%02d', $index + 1)); ?></span><?php echo esc_html((string) $section['title']); ?></h2>
                            <?php foreach (preg_split('/\n\s*\n/', trim((string) ($section['text'] ?? ''))) ?: [] as $paragraph) : if (trim($paragraph) === '') continue; ?>
                                <p><?php echo nl2br(esc_html(trim($paragraph))); ?></p>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <aside class="min-w-0 lg:order-1">
                    <div class="grid gap-4 lg:sticky lg:top-6">
                        <?php if (count($sections) >= 3) : ?>
                            <nav class="hidden rounded border border-line bg-white p-5 lg:block" aria-label="<?php echo esc_attr((string) ($ui['tocTitle'] ?? 'On this page')); ?>" data-news-toc>
                                <h2 class="text-xs font-black uppercase tracking-[0.14em] text-slate-500"><?php echo esc_html((string) ($ui['tocTitle'] ?? 'On this page')); ?></h2>
                                <ol class="boblift-news-toc-list mt-3">
                                    <?php foreach ($sections as $index => $section) : ?>
                                        <li><a href="#section-<?php echo esc_attr((string) ($index + 1)); ?>"><?php echo esc_html((string) $section['title']); ?></a></li>
                                    <?php endforeach; ?>
                                </ol>
                            </nav>
                        <?php endif; ?>

                        <section class="rounded border border-line bg-mist p-5">
                            <h2 class="text-base font-black leading-snug text-ink"><?php echo esc_html((string) ($ui['contactTitle'] ?? 'Questions about this page?')); ?></h2>
                            <p class="mt-2 text-sm font-semibold leading-6 text-slate-600"><?php echo esc_html((string) ($ui['contactText'] ?? '')); ?></p>
                            <div class="mt-4 grid gap-2 text-sm font-bold">
                                <a class="inline-flex min-w-0 items-center gap-2 text-navy transition hover:text-blue-700" href="mailto:<?php echo esc_attr($contact['email']); ?>"><?php echo boblift_icon('mail', 'h-4 w-4 shrink-0 text-slate-400'); ?><span class="truncate"><?php echo esc_html($contact['email']); ?></span></a>
                                <a class="inline-flex min-w-0 items-center gap-2 text-navy transition hover:text-blue-700" href="<?php echo esc_url(boblift_url('/contact.html')); ?>" data-inquiry-source="<?php echo esc_attr($inquiry_source); ?>"><?php echo boblift_icon('message-circle', 'h-4 w-4 shrink-0 text-slate-400'); ?><span class="truncate"><?php echo esc_html((string) ($ui['contactLink'] ?? 'Contact page')); ?></span></a>
                            </div>
                        </section>
                    </div>
                </aside>
            </div>
        </div>
    </div>
    <?php
}
