<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Privacy Policy and Terms of Use share one reading layout: the page hero, a sticky contents list with a
 * contact card on the left and the clauses as one continuous document in the article reading style.
 */
function boblift_render_legal_page(array $options, string $inquiry_source, string $hero_image = ''): void
{
    $ui = boblift_site_ui('legalPage');
    $sections = array_values(array_filter(is_array($options['sections'] ?? null) ? $options['sections'] : [], static fn($item): bool => is_array($item) && (trim((string) ($item['title'] ?? '')) !== '' || trim((string) ($item['text'] ?? '')) !== '')));
    $intro = trim((string) ($options['side_text'] ?? ''));
    $contact = boblift_contact_settings();
    $contact_card = static function () use ($ui, $contact, $inquiry_source): void { ?>
                        <section class="rounded border border-line bg-mist p-5">
                            <h2 class="text-base font-black leading-snug text-ink"><?php echo esc_html((string) ($ui['contactTitle'] ?? 'Questions about this page?')); ?></h2>
                            <p class="mt-2 text-sm font-semibold leading-6 text-slate-600"><?php echo esc_html((string) ($ui['contactText'] ?? '')); ?></p>
                            <div class="mt-4 grid gap-2 text-sm font-bold">
                                <a class="inline-flex min-w-0 items-center gap-2 text-navy transition hover:text-blue-700" href="mailto:<?php echo esc_attr($contact['email']); ?>"><?php echo boblift_icon('mail', 'h-4 w-4 shrink-0 text-slate-400'); ?><span class="truncate"><?php echo esc_html($contact['email']); ?></span></a>
                                <a class="inline-flex min-w-0 items-center gap-2 text-navy transition hover:text-blue-700" href="<?php echo esc_url(boblift_url('/contact.html')); ?>" data-inquiry-source="<?php echo esc_attr($inquiry_source); ?>"><?php echo boblift_icon('message-circle', 'h-4 w-4 shrink-0 text-slate-400'); ?><span class="truncate"><?php echo esc_html((string) ($ui['contactLink'] ?? 'Contact page')); ?></span></a>
                            </div>
                        </section>
    <?php };
    $current = get_queried_object();
    // Sibling documents for the right rail: every legal page links to the others.
    $documents = array_values(array_filter([
        ['slug' => 'privacy-policy', 'label' => (string) ($ui['privacyLabel'] ?? 'Privacy Policy')],
        ['slug' => 'terms-of-use', 'label' => (string) ($ui['termsLabel'] ?? 'Terms of Use')],
        ['slug' => 'sitemap', 'label' => (string) ($ui['sitemapLabel'] ?? 'Sitemap')],
    ], static fn(array $doc): bool => !($current instanceof WP_Post && ($current->post_name === $doc['slug'] || (function_exists('boblift_page_is_translation_of_slug') && boblift_page_is_translation_of_slug($current, $doc['slug']))))));
    ?>
    <div class="bg-white text-ink">
        <div class="page-shell pt-5 md:pt-8">
            <?php
            boblift_page_hero([
                'label' => (string) ($options['label'] ?? ''),
                'title' => (string) ($options['title'] ?? ''),
                'text' => (string) ($options['description'] ?? ''),
                'meta' => trim((string) ($options['updated_label'] ?? 'Last updated:') . ' ' . (string) ($options['updated'] ?? '')),
                'image' => (string) ($options['hero_image'] ?? $hero_image),
                'image_alt' => (string) ($options['title'] ?? ''),
                'image_position' => 'center 45%',
            ]);
            ?>
        </div>

        <div class="page-shell py-8 md:py-12 lg:py-14">
            <div class="grid gap-8 lg:grid-cols-[17.5rem_minmax(0,1fr)] lg:gap-12 xl:grid-cols-[16.5rem_minmax(0,1fr)_17.5rem] 2xl:gap-16">
                <div class="min-w-0 lg:order-2">
                    <div class="boblift-article-body boblift-legal-body xl:mx-auto">
                        <?php if ($intro !== '') : ?>
                            <p class="boblift-legal-intro"><?php echo esc_html($intro); ?></p>
                        <?php endif; ?>
                        <?php foreach ($sections as $index => $section) : ?>
                            <?php if (trim((string) ($section['title'] ?? '')) !== '') : ?>
                                <h2 id="section-<?php echo esc_attr((string) ($index + 1)); ?>"><span class="boblift-legal-number"><?php echo esc_html(sprintf('%02d', $index + 1)); ?></span><?php echo esc_html((string) $section['title']); ?></h2>
                            <?php else : ?>
                                <span id="section-<?php echo esc_attr((string) ($index + 1)); ?>"></span>
                            <?php endif; ?>
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
                                    <?php foreach ($sections as $index => $section) : if (trim((string) ($section['title'] ?? '')) === '') continue; ?>
                                        <li><a href="#section-<?php echo esc_attr((string) ($index + 1)); ?>"><?php echo esc_html((string) $section['title']); ?></a></li>
                                    <?php endforeach; ?>
                                </ol>
                            </nav>
                        <?php endif; ?>

                        <div class="xl:hidden"><?php $contact_card(); ?></div>
                    </div>
                </aside>
                <aside class="hidden min-w-0 xl:order-3 xl:block">
                    <div class="grid gap-4 xl:sticky xl:top-6">
                        <?php $contact_card(); ?>
                        <?php if ($documents !== []) : ?>
                            <nav class="rounded border border-line bg-white p-5" aria-label="<?php echo esc_attr((string) ($ui['documentsTitle'] ?? 'Related documents')); ?>">
                                <h2 class="text-xs font-black uppercase tracking-[0.14em] text-slate-500"><?php echo esc_html((string) ($ui['documentsTitle'] ?? 'Related documents')); ?></h2>
                                <ul class="mt-3 grid gap-1">
                                    <?php foreach ($documents as $doc) : ?>
                                        <li><a class="flex items-center justify-between gap-3 rounded-sm px-1 py-2 text-sm font-bold text-navy transition hover:bg-mist hover:text-blue-700" href="<?php echo esc_url(boblift_url('/' . $doc['slug'] . '.html')); ?>"><?php echo esc_html($doc['label']); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4 text-slate-400'); ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </aside>
            </div>
        </div>
    </div>
    <?php
}
