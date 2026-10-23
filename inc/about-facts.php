<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Company at a glance on the About page: a table of checked company facts, edited in the About settings (about_page.facts,
 * defaults in inc/data.php). Labels and values go through the static-text dictionary (inc/translations/about-facts.php),
 * so each language shows its own wording until an editor saves that language's rows. A value may hold links (a[href]) and
 * placeholders filled here from the contact settings and the company's public profiles, so an address or phone number is
 * kept in one place only.
 */

/** The company's public profiles: its B2B directory listings, then the external social links saved in the footer. */
function boblift_company_profile_links(): array
{
    $names = [
        'bobliftcrane.en.made-in-china.com' => 'Made-in-China (BOB-LIFT Crane)',
        'boblift.en.made-in-china.com' => 'Made-in-China (BOB-LIFT)',
        'www.directindustry.com' => 'DirectIndustry',
        'www.exporthub.com' => 'ExportHub',
    ];
    $links = [];
    foreach (defined('BOBLIFT_COMPANY_DIRECTORY_PROFILES') ? BOBLIFT_COMPANY_DIRECTORY_PROFILES : [] as $url) {
        $host = (string) wp_parse_url($url, PHP_URL_HOST);
        $links[] = ['label' => $names[$host] ?? $host, 'href' => $url];
    }
    foreach ((array) boblift_option('footer.social_links', []) as $link) {
        $href = is_array($link) ? trim((string) ($link['href'] ?? '')) : '';
        // Placeholders point at this site's contact page; only external profiles are listed.
        if (preg_match('#^https://#i', $href) && strcasecmp((string) wp_parse_url($href, PHP_URL_HOST), (string) wp_parse_url(home_url('/'), PHP_URL_HOST)) !== 0) {
            $links[] = ['label' => trim((string) ($link['label'] ?? '')) ?: (string) wp_parse_url($href, PHP_URL_HOST), 'href' => $href];
        }
    }
    return $links;
}

/** A fact's value as HTML: the saved links kept, the placeholders filled in. */
function boblift_about_fact_value_html(string $value): string
{
    $contact = boblift_contact_settings();
    $link = 'font-bold text-blue-700 underline-offset-4 hover:underline';
    $profiles = array_map(static fn(array $profile): string => sprintf(
        '<a class="inline-flex items-center gap-1 %s" href="%s" target="_blank" rel="noopener">%s%s</a>',
        $link,
        esc_url($profile['href']),
        esc_html($profile['label']),
        boblift_icon('arrow-up-right', 'h-3.5 w-3.5 text-slate-500 rtl:-scale-x-100')
    ), boblift_company_profile_links());
    $html = wp_kses($value, ['a' => ['href' => true]]);
    // Links saved in the value (#about-certifications ...) take the same link style.
    $html = str_replace('<a href=', '<a class="' . $link . '" href=', $html);
    return strtr($html, [
        '{factory_address}' => esc_html(trim((string) boblift_option('contact.factory_address', ''))),
        '{office_address}' => esc_html(trim((string) boblift_option('contact.office_address', ''))),
        '{email}' => '<a class="' . $link . '" href="mailto:' . esc_attr($contact['email']) . '">' . esc_html($contact['email']) . '</a>',
        '{phone}' => '<a class="' . $link . '" href="' . esc_url($contact['phoneHref'], ['tel']) . '" dir="ltr">' . esc_html($contact['phone']) . '</a>',
        '{profiles}' => '<span class="flex flex-wrap gap-x-4 gap-y-1">' . implode('', $profiles) . '</span>',
    ]);
}

function boblift_render_about_facts(array $facts): void
{
    $t = static fn(string $text): string => boblift_localized_static_text($text);
    $items = array_values(array_filter(array_map(static fn($item): array => is_array($item) ? ['label' => trim((string) ($item['label'] ?? '')), 'value' => trim((string) ($item['value_html'] ?? ''))] : ['label' => '', 'value' => ''], (array) ($facts['items'] ?? [])), static fn(array $item): bool => $item['label'] !== '' && $item['value'] !== ''));
    if (!$items) {
        return;
    }
    // From xl the rows run down two columns, the first column holding the larger half.
    $rows = (int) ceil(count($items) / 2);
    ?>
    <section id="about-facts" class="bg-[#F8FAFC] pb-12 lg:pb-16" aria-labelledby="about-facts-title">
        <div class="page-shell about-wide-shell">
            <h2 id="about-facts-title" class="text-[28px] font-black leading-[1.15] tracking-[-0.005em] text-[#0B1B33] md:text-[36px]"><?php echo esc_html($t((string) ($facts['title'] ?? 'Company at a glance'))); ?></h2>
            <dl class="mt-6 grid border-t border-[#DDE7F2] md:mt-8 xl:grid-flow-col xl:gap-x-12 xl:[grid-template-rows:repeat(var(--about-fact-rows),auto)]" style="--about-fact-rows: <?php echo esc_attr((string) $rows); ?>">
                <?php foreach ($items as $item) : ?>
                    <div class="grid gap-1 border-b border-[#DDE7F2] py-4 sm:grid-cols-[minmax(0,180px)_minmax(0,1fr)] sm:gap-6">
                        <dt class="text-sm font-black leading-7 text-[#0B1B33]"><?php echo esc_html($t($item['label'])); ?></dt>
                        <dd class="text-sm leading-7 text-[#475569] md:text-base"><?php echo boblift_about_fact_value_html($t($item['value'])); // phpcs:ignore WordPress.Security.EscapeOutput -- built from kses-filtered and escaped parts. ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        </div>
    </section>
    <?php
}
