<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Who BOB-LIFT is, for search engines and AI assistants: the home page's Organization and WebSite JSON-LD and /llms.txt
 * both carry the registered English name and the Chinese name, so the company is found under 徐州巴布工程机械有限公司
 * too (as on the loader site).
 *
 * One company, one entity: this site, the loader site and the group site describe the same Organization with the same
 * @id, name, official website (the group site), logo and profile list, so Google can tie them to a single brand entity
 * (the knowledge panel). Every node on this site that points at the company (articles, collections, authors) uses
 * boblift_organization_id(), in every language.
 *
 * Jetpack SEO builds its home-page Organization from the site title alone (no legalName or alternateName), so on the
 * home page its graph is replaced by this one.
 */
const BOBLIFT_COMPANY_CHINESE_NAME = '徐州巴布工程机械有限公司';

/** The group site is the company's official website and the home of its entity; the loader site uses the same @id. */
const BOBLIFT_ORGANIZATION_URL = 'https://www.boblift.com/';

/** Unified social credit code from the national enterprise credit register (business licence, checked 2026-11-12). */
const BOBLIFT_COMPANY_CREDIT_CODE = '91320300078204097G';

/**
 * The company's own supplier listings on B2B directories (checked 2026-10-23: each names Xuzhou BOB-LIFT and lists its
 * cranes). They tell search engines these profiles and this site are the same manufacturer.
 */
const BOBLIFT_COMPANY_DIRECTORY_PROFILES = [
    'https://bobliftcrane.en.made-in-china.com/',
    'https://boblift.en.made-in-china.com/',
    'https://www.directindustry.com/prod/bob-lift-241919.html',
    'https://www.exporthub.com/boblift/profile.html',
];

/**
 * The company's profiles that are not in this site's footer: the loader line's YouTube and Facebook (the same addresses
 * as the loader site's company settings) and the company's Wikidata item (created 2026-11-20).
 */
const BOBLIFT_COMPANY_OTHER_PROFILES = [
    'https://www.youtube.com/@bobliftloader',
    'https://www.facebook.com/@bobliftloader',
    'https://www.wikidata.org/wiki/Q142220180',
];

function boblift_organization_id(): string
{
    return BOBLIFT_ORGANIZATION_URL . '#organization';
}

/** Square logo (600 × 600, white ground) for search engines: Google needs at least 112 px on each side. */
function boblift_organization_logo(): array
{
    return ['@type' => 'ImageObject', '@id' => BOBLIFT_ORGANIZATION_URL . '#logo', 'url' => boblift_asset('images/bob-lift-logo-square-600.png'), 'contentUrl' => boblift_asset('images/bob-lift-logo-square-600.png'), 'width' => 600, 'height' => 600, 'caption' => 'BOB-LIFT'];
}

/**
 * Public profiles of the company: its crane and loader websites, its directory listings, the real (https) social links
 * saved in the footer and the loader line's profiles.
 */
function boblift_company_same_as(): array
{
    $urls = array_merge(['https://bobliftcrane.com/', 'https://bobliftloader.com/'], BOBLIFT_COMPANY_DIRECTORY_PROFILES);
    $links = boblift_option('footer.social_links', []);
    foreach (is_array($links) ? $links : [] as $link) {
        $href = is_array($link) ? trim((string) ($link['href'] ?? '')) : '';
        // Placeholders point at this site's contact page; only external profiles belong here.
        if (preg_match('#^https://#i', $href) && strcasecmp((string) wp_parse_url($href, PHP_URL_HOST), (string) wp_parse_url(home_url('/'), PHP_URL_HOST)) !== 0) {
            $urls[] = $href;
        }
    }
    return array_values(array_unique(array_merge($urls, BOBLIFT_COMPANY_OTHER_PROFILES)));
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
    $id = boblift_organization_id();
    $organization = [
        '@type' => 'Organization',
        '@id' => $id,
        'name' => 'BOB-LIFT',
        'legalName' => BOBLIFT_COMPANY_LEGAL_NAME,
        'alternateName' => [BOBLIFT_COMPANY_LEGAL_NAME, BOBLIFT_COMPANY_CHINESE_NAME, 'BOBLIFT'],
        'description' => 'BOB-LIFT (' . BOBLIFT_COMPANY_LEGAL_NAME . ') is a Chinese manufacturer of truck mounted cranes, marine cranes and loader equipment in Xuzhou, Jiangsu.',
        'url' => BOBLIFT_ORGANIZATION_URL,
        'logo' => boblift_organization_logo(),
        'image' => ['@id' => BOBLIFT_ORGANIZATION_URL . '#logo'],
        'identifier' => ['@type' => 'PropertyValue', 'propertyID' => 'Unified Social Credit Code', 'value' => BOBLIFT_COMPANY_CREDIT_CODE],
        'foundingDate' => '2003',
        'foundingLocation' => ['@type' => 'Place', 'name' => 'Xuzhou, Jiangsu, China', 'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'Xuzhou', 'addressRegion' => 'Jiangsu', 'addressCountry' => 'CN']],
        'email' => $contact['email'],
        'telephone' => $contact['phone'],
        'contactPoint' => ['@type' => 'ContactPoint', 'contactType' => 'sales', 'email' => $contact['email'], 'telephone' => $contact['phone'], 'availableLanguage' => ['English', 'Chinese']],
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

/**
 * Old news that the 2026-11-23 cleanup list (#8 to #34) will 301 into other pages or noindex. They stay on the site until
 * that work is done, but llms.txt already leaves them out so AI assistants are not pointed at pages about to go.
 */
const BOBLIFT_LLMS_TXT_HELD_BACK_PATHS = [
    '/news-show-89.html',
    '/news-show-273.html',
    '/news-show-311.html',
    '/how-to-choose-the-right-truck-mounted-crane-and-maintain-it-id45131527.html',
    '/a-comprehensive-analysis-of-6-major-types-helping-you-choose-the-right-device-to-save-both-time-and-money-id44259316.html',
    '/the-triple-safety-barriers-of-the-bob-lift-crane-id40278975.html',
    '/application-cases-and-successful-experience-of-ship-cranes-id49901337.html',
    '/heavy-lifting-for-global-infrastructure-and-industrial-projects-id40628636.html',
    '/explore-the-versatile-boblift-800kg-pickup-crane-efficient-lifting-anytime-anywhere-id49431156.html',
    '/bob-lift-14-meter-high-altitude-work-vehicle-steadily-arrives-calmly-operates-id09491155.html',
    '/blockbuster-new-product-8-ton-orange-knuckle-boom-crane-the-perfect-combination-of-strength-and-aesthetics-id41994416.html',
    '/overload-lifting-solution-facilitating-efficient-engineering-operations-id09867845.html',
    '/bob-lift-offers-efficient-and-reliable-solutions-for-ship-lifting-operations-id02577455.html',
    '/bob-lift-invites-you-to-the-137th-canton-fair-id41017926.html',
    '/artistry-creates-quality-hard-work-earns-trust-id06477355.html',
    '/the-meticulous-craftsmanship-of-bob-lift-supports-every-entrustment-id45058175.html',
    '/enter-the-bob-lift-factory-with-professional-manufacturing-we-create-more-reliable-lifting-equipment-id09841355.html',
    '/bob-lift-welcomes-russian-customers-to-visit-the-factory-and-receives-overwhelming-praise-from-them-id41604765.html',
    '/bob-lift-engages-in-face-to-face-communication-and-cooperation-with-customers-for-the-future-id04130055.html',
    '/bob-lift-truck-mounted-cranes-dispatched-in-batches-chasing-for-the-trust-of-our-customers-id47108475.html',
    '/bob-lift-truck-mounted-cranes-are-about-to-be-shipped-overseas-in-large-quantities-id49408865.html',
    '/nccco-foundation-and-national-safety-council-develop-crane-safety-tool-id44029726.html',
    '/the-dublin-world-crane-and-transport-summit-will-take-place-on-18-19-november-id46494326.html',
    '/bob-lift-performs-steadily-in-extreme-environments-pushing-the-limits-and-achieving-extraordinary-results-id49007126.html',
    '/good-luck-starting-in-2026-bob-lift-helps-you-lift-to-new-heights-in-the-new-year-id08112365.html',
    '/the-advanced-path-of-bob-lift-s-global-strategy-id02590965.html',
    '/strength-speaks-louder-than-words-why-bob-lift-is-highly-favored-at-the-canton-fair-id06952655.html',
];

/**
 * Up to 500 public (not password-protected) English articles, newest first (ID breaks date ties), so older posts that AI
 * answers already cite stay listed; the held-back old news is left out. Checked by scripts/test-llms-articles.php.
 */
function boblift_llms_txt_articles(): array
{
    $articles = [];
    // Matched on the last path segment, so a language or subdirectory prefix (/en/news-show-89.html) still matches.
    $held_back = array_fill_keys(array_map('basename', BOBLIFT_LLMS_TXT_HELD_BACK_PATHS), true);
    // Fetch enough to fill 500 after the held-back posts are dropped.
    $posts = get_posts(['post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 500 + count($held_back), 'has_password' => false, 'orderby' => ['date' => 'DESC', 'ID' => 'DESC'], 'no_found_rows' => true, 'lang' => 'en']);
    foreach ($posts as $post) {
        $url = get_permalink($post);
        if (!is_string($url) || isset($held_back[basename((string) wp_parse_url($url, PHP_URL_PATH))])) {
            continue;
        }
        $articles[] = boblift_llms_txt_link(get_the_title($post), $url, boblift_seo_post_description($post));
        if (count($articles) === 500) {
            break;
        }
    }
    return $articles;
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
    $articles = boblift_llms_txt_articles();
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
