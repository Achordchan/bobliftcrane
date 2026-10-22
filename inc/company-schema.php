<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Who BOB-LIFT is, for search engines and AI assistants: the home page's Organization and WebSite JSON-LD and /llms.txt
 * both carry the registered English name and the Chinese name, so the company is found under 徐州巴布工程机械有限公司
 * too (as on the loader site).
 *
 * Jetpack SEO builds its home-page Organization from the site title alone (no legalName or alternateName), so on the
 * home page its graph is replaced by this one. Both use the same @id (home_url('/') . '#organization'), so Jetpack's
 * Article nodes on other pages keep pointing at this Organization.
 */
const BOBLIFT_COMPANY_CHINESE_NAME = '徐州巴布工程机械有限公司';

/** Public profiles of the company: the group and loader sites plus the real (https) social links saved in the footer. */
function boblift_company_same_as(): array
{
    $urls = ['https://www.boblift.com/', 'https://bobliftloader.com/'];
    $links = boblift_option('footer.social_links', []);
    foreach (is_array($links) ? $links : [] as $link) {
        $href = is_array($link) ? trim((string) ($link['href'] ?? '')) : '';
        // Placeholders point at this site's contact page; only external profiles belong here.
        if (preg_match('#^https://#i', $href) && strcasecmp((string) wp_parse_url($href, PHP_URL_HOST), (string) wp_parse_url(home_url('/'), PHP_URL_HOST)) !== 0) {
            $urls[] = $href;
        }
    }
    return array_values(array_unique($urls));
}

function boblift_company_postal_address(string $street): array
{
    // The stored addresses end in ", Xuzhou (City), Jiangsu (Province), China"; the street part is what precedes the city.
    $street = trim((string) preg_replace('/,\s*Xuzhou(?:\s+City)?,\s*Jiangsu(?:\s+Province)?,\s*China\s*$/i', '', $street));
    return ['@type' => 'PostalAddress', 'streetAddress' => $street, 'addressLocality' => 'Xuzhou', 'addressRegion' => 'Jiangsu', 'addressCountry' => 'CN'];
}

function boblift_company_schema_graph(): array
{
    $contact = boblift_contact_settings();
    $stored = boblift_option('contact', []);
    $stored = is_array($stored) ? $stored : [];
    $id = home_url('/') . '#organization';
    $organization = [
        '@type' => 'Organization',
        '@id' => $id,
        'name' => 'BOB-LIFT',
        'legalName' => BOBLIFT_COMPANY_LEGAL_NAME,
        'alternateName' => [BOBLIFT_COMPANY_LEGAL_NAME, BOBLIFT_COMPANY_CHINESE_NAME],
        'url' => home_url('/'),
        'logo' => boblift_asset('images/bob-lift-logo.png'),
        'foundingDate' => '2003',
        'email' => $contact['email'],
        'telephone' => $contact['phone'],
        'sameAs' => boblift_company_same_as(),
    ];
    $registered = trim((string) ($stored['address'] ?? 'No. 79, Zone B, Jinluotuo Industrial Park, Yunlong District, Xuzhou, Jiangsu, China'));
    if ($registered !== '') {
        $organization['address'] = boblift_company_postal_address($registered);
    }
    $locations = [];
    foreach (['Main factory' => 'factory_address', 'Office' => 'office_address'] as $name => $key) {
        $address = trim((string) ($stored[$key] ?? ''));
        if ($address !== '') {
            $locations[] = ['@type' => 'Place', 'name' => $name, 'address' => boblift_company_postal_address($address)];
        }
    }
    if ($locations) {
        $organization['location'] = $locations;
    }
    $website = [
        '@type' => 'WebSite',
        '@id' => home_url('/') . '#website',
        'name' => 'BOB-LIFT Cranes',
        'alternateName' => 'BOB-LIFT',
        'url' => home_url('/'),
        'inLanguage' => get_bloginfo('language'),
        'publisher' => ['@id' => $id],
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => ['@type' => 'EntryPoint', 'urlTemplate' => home_url('/?s={search_term_string}')],
            'query-input' => 'required name=search_term_string',
        ],
    ];
    return ['@context' => 'https://schema.org', '@graph' => [$organization, $website]];
}

// Before Jetpack SEO's wp_head output (priority 5): take its home-page graph out and print this one instead.
add_action('wp_head', static function (): void {
    if (!is_front_page()) {
        return;
    }
    if (class_exists('Automattic\Jetpack\SEO\Schema_Builder')) {
        remove_action('wp_head', ['Automattic\Jetpack\SEO\Schema_Builder', 'emit'], 5);
    }
    echo '<script type="application/ld+json">' . wp_json_encode(boblift_company_schema_graph(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . "</script>\n";
}, 4);

/**
 * /llms.txt: a Markdown map of the English site for AI assistants (llmstxt.org format), built from the live product
 * categories and articles on each request. Served only while the site is public; Google does not read it, so the response
 * carries noindex. Jetpack's own generated llms.txt is switched off so it cannot answer first.
 */
function boblift_llms_txt_line(string $text): string
{
    $text = html_entity_decode(wp_strip_all_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return strtr(trim((string) preg_replace('/\s+/u', ' ', $text)), ['[' => '(', ']' => ')']);
}

function boblift_llms_txt_link(string $title, string $url, string $description = ''): string
{
    $line = '- [' . boblift_llms_txt_line($title) . '](' . $url . ')';
    $description = boblift_llms_txt_line($description);
    return $description !== '' ? $line . ': ' . $description : $line;
}

function boblift_llms_txt(): string
{
    $contact = boblift_contact_settings();
    $out = "# BOB-LIFT Cranes\n\n"
        . '> Truck mounted cranes, marine cranes and other lifting equipment from ' . BOBLIFT_COMPANY_LEGAL_NAME . ' (' . BOBLIFT_COMPANY_CHINESE_NAME . "), a crane manufacturer in Xuzhou, Jiangsu, China.\n\n"
        . 'This is the crane website of ' . BOBLIFT_COMPANY_LEGAL_NAME . ' (Chinese name ' . BOBLIFT_COMPANY_CHINESE_NAME . ", brand BOB-LIFT), founded in 2003 in Xuzhou. Cranes are built at the company's own factory and exported worldwide.\n\n"
        . "Key facts:\n"
        . "- Knuckle boom and telescopic boom truck mounted cranes from 800 kg to 50 t\n"
        . "- Knuckle boom and telescopic boom marine cranes from 0.8 to 25 t\n"
        . "- Also pickup truck cranes, forklift fly jib cranes, spider cranes, timber cranes, truck tail lifts and truck mounted aerial work platforms\n"
        . "- Specifications on each product page are the published figures; confirm configuration and price with the sales team\n";

    $categories = [];
    $terms = get_terms(['taxonomy' => 'product_category', 'hide_empty' => true, 'lang' => 'en']);
    $terms = is_array($terms) ? $terms : [];
    // Product families first, then their sub-types.
    usort($terms, static fn(WP_Term $a, WP_Term $b): int => [(int) ($a->parent !== 0), $a->name] <=> [(int) ($b->parent !== 0), $b->name]);
    foreach ($terms as $term) {
        $link = get_term_link($term);
        if (!is_wp_error($link)) {
            $categories[] = boblift_llms_txt_link($term->name, $link, boblift_seo_term_description($term));
        }
    }
    $articles = [];
    foreach (get_posts(['post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 60, 'lang' => 'en']) as $post) {
        $articles[] = boblift_llms_txt_link(get_the_title($post), get_permalink($post), boblift_seo_post_description($post));
    }
    $company = [boblift_llms_txt_link('All products', boblift_product_archive_url(), 'Every crane model with filters for type, boom and capacity')];
    foreach (['about' => 'About BOB-LIFT', 'factory-tour' => 'Factory tour', 'quality-testing' => 'Quality and testing', 'cases' => 'Application cases and customer feedback', 'contact' => 'Contact and inquiry form'] as $slug => $title) {
        $page = boblift_find_page_by_slug($slug);
        if ($page instanceof WP_Post && $page->post_status === 'publish') {
            $company[] = boblift_llms_txt_link($title, get_permalink($page));
        }
    }
    $company[] = '- Email: ' . $contact['email'];
    $company[] = '- Phone and WhatsApp: ' . $contact['phone'];
    $company[] = '- Group website: https://www.boblift.com/ ; loader equipment: https://bobliftloader.com/';

    foreach (['Product categories' => $categories, 'Company' => $company, 'Guides and news' => $articles] as $heading => $lines) {
        if ($lines) {
            $out .= "\n## " . $heading . "\n\n" . implode("\n", $lines) . "\n";
        }
    }
    return $out;
}

add_filter('jetpack_seo_llms_txt_can_serve', '__return_false');

// Ahead of the legacy redirects (priority 0) and the 404 template.
add_action('template_redirect', static function (): void {
    // Compared with the path home_url() gives, so an install in a subdirectory (/cranes/llms.txt) answers too. Polylang
    // adds no language prefix here: llms.txt describes the English (default-language) site.
    $request = rtrim((string) wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/');
    $base = rtrim((string) wp_parse_url((string) get_option('home'), PHP_URL_PATH), '/');
    if ($request !== $base . '/llms.txt' || !get_option('blog_public')) {
        return;
    }
    status_header(200);
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Robots-Tag: noindex');
    header('Cache-Control: public, max-age=3600');
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') {
        echo boblift_llms_txt();
    }
    exit;
}, -1);
