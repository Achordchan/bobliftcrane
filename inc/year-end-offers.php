<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The 2026 year-end offers (order coupons and the deposit offer, 20 November to 31 December 2026, China time):
 * the page /year-end-specials.html (and /zh/…, /es/… in every language), the home page banner under the hero, and the
 * 参加活动 field that tells the sales team an inquiry came through the offers.
 *
 * Only the size and rules of the offers are written: never a price, a price list or the year-end special-price models,
 * which the sales team quotes per order (company policy). The words are English here and translate through the static
 * text dictionary (inc/translations/year-end-offers.php); 站点文案 can override any of them per language.
 *
 * The page answers from the theme like the tonnage pages (no WordPress page behind it). After the last day the banner is
 * gone by itself (the page also hides it in the browser, for copies the edge cache still holds), and the page stays at
 * its address so shared links keep working, says the offers have ended, is marked noindex and leaves the sitemap.
 */

const BOBLIFT_OFFER_SLUG = 'year-end-specials';
const BOBLIFT_OFFER_CODE = 'year-end-2026';
const BOBLIFT_OFFER_STARTS = '2026-11-20';
const BOBLIFT_OFFER_ENDS = '2026-12-31';

/** The offer's days, Y-m-d in China time; a filter can move them without a deploy of this file. */
function boblift_offer_dates(): array
{
    $dates = (array) apply_filters('boblift_offer_dates', ['starts' => BOBLIFT_OFFER_STARTS, 'ends' => BOBLIFT_OFFER_ENDS]);
    return ['starts' => (string) ($dates['starts'] ?? BOBLIFT_OFFER_STARTS), 'ends' => (string) ($dates['ends'] ?? BOBLIFT_OFFER_ENDS)];
}

function boblift_offer_today(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone('Asia/Shanghai')))->format('Y-m-d');
}

/** Whether the offers run today (both days included). */
function boblift_offer_live(?string $today = null): bool
{
    $dates = boblift_offer_dates();
    $today ??= boblift_offer_today();
    return $dates['starts'] <= $today && $today <= $dates['ends'];
}

/** Whether the offers are over (from the day after the last day). */
function boblift_offer_ended(?string $today = null): bool
{
    return ($today ?? boblift_offer_today()) > boblift_offer_dates()['ends'];
}

/** The moment the offers stop, for the browser: midnight after the last day, China time, as an ISO timestamp. */
function boblift_offer_ends_at(): string
{
    $end = DateTimeImmutable::createFromFormat('!Y-m-d', boblift_offer_dates()['ends'], new DateTimeZone('Asia/Shanghai'));
    return $end ? $end->modify('+1 day')->format(DATE_ATOM) : '';
}

function boblift_offer_url(?string $language = null): string
{
    return boblift_public_path_url('/' . BOBLIFT_OFFER_SLUG . '.html', $language);
}

/** Whether the current request is the offers page. */
function boblift_offer_page(): bool
{
    return (string) get_query_var('boblift_offer') === '1';
}

/** One line of the offers' copy in the page's language. */
function boblift_offer_t(string $text): string
{
    return function_exists('boblift_localized_static_text') ? boblift_localized_static_text($text) : $text;
}

/**
 * Everything the page and the banner say, in English (each line goes through boblift_offer_t() where it is shown).
 * Amounts are US dollars, written once here so the banner, the page and the description agree.
 */
function boblift_offer_copy(): array
{
    return [
        'title' => 'Year-End Crane Offers: Up to US$2,500 Off | BOB-LIFT',
        'description' => 'Until 31 December 2026: up to US$2,500 off crane orders of 12 tons and below, or pay a small deposit now and it counts double when you order.',
        'banner' => [
            'label' => 'Year-End Specials · until 31 Dec 2026',
            'title' => 'Up to US$2,500 off your crane order',
            'text' => 'Or pay a small deposit now and it counts double when you order.',
            'button' => 'See the offers',
        ],
        'hero' => [
            'label' => 'Year-End Specials · 20 Nov to 31 Dec 2026',
            'title' => 'Year-end offers on BOB-LIFT cranes',
            'text' => 'Two offers run until 31 December 2026. Take a coupon off a confirmed crane order, or hold a later order with a small deposit that counts double when you place it. Each order takes one offer.',
            'button' => 'Claim an offer',
            'link' => 'Read the terms',
            'breadcrumb' => 'Year-end offers',
            'ended' => 'These offers ended on 31 December 2026. Ask our team about your order: we reply with a quotation and the current terms.',
        ],
        'coupons' => [
            'label' => 'Offer 1 · Order coupons',
            'title' => 'The bigger the order, the more you save',
            'off' => 'Discount',
            'items' => [
                ['save' => 'US$500', 'over' => 'orders of US$10,000 or more'],
                ['save' => 'US$1,500', 'over' => 'orders of US$30,000 or more'],
                ['save' => 'US$2,500', 'over' => 'orders of US$50,000 or more'],
            ],
            'note' => 'The coupon comes off the order total in your contract. It applies to cranes of 12 tons and below.',
        ],
        'deposit' => [
            'label' => 'Offer 2 · Deposit offer',
            'title' => 'Pay a deposit now. It counts double.',
            'intro' => 'For buyers with a real order who can only pay in the coming weeks: pay a small deposit by 31 December 2026, and it counts double against the order when you place it.',
            'columns' => ['Order value', 'You pay', 'Counts as'],
            'rows' => [
                ['US$5,000 to 15,000', 'US$500', 'US$1,000'],
                ['US$15,001 to 30,000', 'US$700', 'US$1,400'],
                ['Above US$30,000', 'US$1,000', 'US$2,000'],
            ],
            'points' => [
                ['Paid to our company account', 'Pay the deposit by bank transfer to the company account we give you. We then send an official receipt by email; that receipt is your proof of the doubled deposit.'],
                ['Taken off the contract payment', 'The doubled amount is deducted from the payment in your contract.'],
                ['Refunded if you do not order', 'If you decide not to place the order, we refund the deposit in full.'],
                ['A smaller order than planned', 'If the final order is small, we agree with you how much of the doubled deposit applies, or carry it over as prepayment for orders in several batches.'],
            ],
        ],
        'terms' => [
            'label' => 'Terms',
            'title' => 'The rules in plain words',
            'items' => [
                'The offers run from 20 November to 31 December 2026. A deposit must be paid by 31 December 2026.',
                'Each order takes one offer: a coupon cannot be combined with the deposit offer, or used on models already at a year-end special price.',
                'Coupons apply to cranes of 12 tons and below. For larger cranes, ask our sales team about year-end pricing.',
                'The offers do not combine with other major company promotions. The signed contract sets the final terms.',
                'We quote every order individually. Send us your requirements for the price of the crane you need.',
            ],
        ],
        'claim' => [
            'label' => 'Claim an offer',
            'title' => 'Tell us what you need',
            'text' => 'Send the crane model, quantity and destination. Our sales team replies with a quotation and tells you which offer saves you more on that order.',
            'button' => 'Get a quote',
            'whatsapp' => 'Ask on WhatsApp',
            'browse' => 'Browse cranes',
            'whatsapp_text' => 'Hello BOB-LIFT, I would like to ask about the year-end crane offers.',
        ],
    ];
}

/* ---------------------------------------------------------------------------------------------------------------- */
/* Routing                                                                                                          */
/* ---------------------------------------------------------------------------------------------------------------- */

add_filter('query_vars', static function (array $vars): array {
    $vars[] = 'boblift_offer';
    return $vars;
});

// /year-end-specials.html arrives as an .html slug (boblift_html_request_vars()); it becomes the offers page and keeps the
// language directory's `lang`. Runs before the .html lookup so no page, product or post of that slug is searched for.
function boblift_offer_request_vars(array $vars): array
{
    if (sanitize_title((string) ($vars['boblift_html_slug'] ?? '')) !== BOBLIFT_OFFER_SLUG) {
        return $vars;
    }
    return array_filter(['boblift_offer' => '1', 'lang' => $vars['lang'] ?? null]);
}
add_filter('request', 'boblift_offer_request_vars', 9);

add_filter('template_include', static function (string $template): string {
    if (!boblift_offer_page()) {
        return $template;
    }
    $offer_template = locate_template('page-year-end-specials.php');
    return $offer_template !== '' ? $offer_template : $template;
});

add_action('template_redirect', static function (): void {
    if (!boblift_offer_page()) {
        return;
    }
    // The language query var makes WordPress see a language archive and print its feed link; the page has no feed.
    remove_action('wp_head', 'feed_links_extra', 3);
    // On the last day the page is not cached, so no copy with the live offers outlives the deadline (the browser
    // script reloads one that does, past the cache). The rest of the time it caches like any page.
    $ends = strtotime(boblift_offer_ends_at());
    if (!boblift_offer_ended() && $ends && $ends - time() < DAY_IN_SECONDS && function_exists('boblift_send_uncached_headers')) {
        boblift_send_uncached_headers();
    }
}, 1);

// Its own title (the language archive WordPress sees would otherwise title it after the site).
add_filter('document_title_parts', static function (array $title): array {
    if (boblift_offer_page()) {
        $title = ['title' => boblift_offer_t(boblift_offer_copy()['title'])];
    }
    return $title;
}, 30);

// The same page in another language: the language switcher and Polylang's hreflang links (it would otherwise send every
// language to its home page, the request being no post or term).
add_filter('pll_translation_url', static fn($url, string $language) => boblift_offer_page() ? boblift_offer_url($language) : $url, 10, 2);

add_action('wp_head', static function (): void {
    if (!boblift_offer_page()) {
        return;
    }
    echo '<link rel="canonical" href="' . esc_url(boblift_offer_url()) . '" />' . "\n";
    $items = [
        ['@type' => 'ListItem', 'position' => 1, 'name' => boblift_offer_t('Home'), 'item' => boblift_public_path_url('/')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => boblift_offer_t(boblift_offer_copy()['hero']['breadcrumb']), 'item' => boblift_offer_url()],
    ];
    echo '<script type="application/ld+json">' . wp_json_encode(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . "</script>\n";
}, 1);

// Over: kept at its address for the links already shared, but out of search.
add_filter('wp_robots', static function (array $robots): array {
    if (boblift_offer_page() && boblift_offer_ended()) {
        $robots['noindex'] = true;
        $robots['follow'] = true;
        unset($robots['max-image-preview']);
    }
    return $robots;
}, 20);

/** The share picture (1200 × 630, BOB-LIFT branded), for Facebook, WhatsApp and LinkedIn previews of the page. */
function boblift_offer_share_image(): string
{
    $url = function_exists('boblift_asset') ? boblift_asset('images/offers/year-end-2026-share.jpg') : '';
    if (str_starts_with($url, '//')) {
        $url = 'https:' . $url;
    } elseif (str_starts_with($url, '/')) {
        $url = untrailingslashit(home_url()) . $url;
    }
    return $url;
}

add_filter('jetpack_open_graph_tags', static function ($tags) {
    if (!is_array($tags) || !boblift_offer_page()) {
        return $tags;
    }
    $copy = boblift_offer_copy();
    $title = boblift_offer_t($copy['title']);
    $description = boblift_offer_t($copy['description']);
    $image = boblift_offer_share_image();
    return array_merge($tags, array_filter([
        'og:type' => 'website',
        'og:title' => $title,
        'og:description' => $description,
        'og:url' => boblift_offer_url(),
        'og:image' => $image,
        'og:image:width' => $image !== '' ? 1200 : null,
        'og:image:height' => $image !== '' ? 630 : null,
        'twitter:card' => 'summary_large_image',
        'twitter:title' => $title,
        'twitter:description' => $description,
        'twitter:image' => $image,
    ]));
}, 30);

/** The page in the sitemap while the offers run, in the sitemap's language (Polylang serves one per language). */
add_action('init', static function (): void {
    if (!class_exists('WP_Sitemaps_Provider')) {
        return;
    }
    wp_register_sitemap_provider('offers', new class extends WP_Sitemaps_Provider {
        public function __construct()
        {
            $this->name = 'offers';
            $this->object_type = 'offers';
        }

        public function get_url_list($page_num, $object_subtype = '')
        {
            return (int) $page_num === 1 && boblift_offer_live() ? [['loc' => boblift_offer_url()]] : [];
        }

        public function get_max_num_pages($object_subtype = '')
        {
            return boblift_offer_live() ? 1 : 0;
        }
    });
}, 20);

/* ---------------------------------------------------------------------------------------------------------------- */
/* The home page banner                                                                                             */
/* ---------------------------------------------------------------------------------------------------------------- */

/**
 * The strip under the home page hero while the offers run. data-offer-ends lets the browser take it away at the end
 * (assets/js/theme.js) on a page the edge cache keeps from before the last day.
 */
function boblift_offer_home_banner(): void
{
    if (!boblift_offer_live()) {
        return;
    }
    $banner = boblift_offer_copy()['banner'];
    $amounts = ['US$500', 'US$1,500', 'US$2,500'];
    ?>
    <section class="boblift-offer-banner relative overflow-hidden bg-navy text-white" aria-labelledby="offer-banner-title" data-offer-ends="<?php echo esc_attr(boblift_offer_ends_at()); ?>">
        <div class="page-shell relative flex flex-col gap-5 py-6 md:py-7 lg:flex-row lg:items-center lg:justify-between lg:gap-10">
            <div class="min-w-0">
                <p class="text-xs font-black uppercase tracking-[0.12em] text-gold"><?php echo esc_html(boblift_offer_t($banner['label'])); ?></p>
                <h2 id="offer-banner-title" class="mt-2 text-2xl font-black leading-tight tracking-[-0.01em] [text-wrap:balance] md:text-3xl"><?php echo esc_html(boblift_offer_t($banner['title'])); ?></h2>
                <p class="mt-1.5 text-sm font-normal leading-6 text-white/80 md:text-base"><?php echo esc_html(boblift_offer_t($banner['text'])); ?></p>
            </div>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center lg:shrink-0">
                <ul class="grid grid-cols-3 gap-2" aria-hidden="true">
                    <?php foreach ($amounts as $amount) : ?>
                        <li class="boblift-offer-ticket rounded-sm border border-dashed border-white/40 px-3 py-2 text-center"><span class="block text-lg font-black leading-none tabular-nums md:text-xl"><?php echo esc_html($amount); ?></span><span class="mt-1 block text-[11px] font-bold uppercase tracking-[0.08em] text-white/70"><?php echo esc_html(boblift_offer_t('Discount')); ?></span></li>
                    <?php endforeach; ?>
                </ul>
                <a class="inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-sm bg-gold px-6 text-sm font-black text-navy transition hover:bg-white" href="<?php echo esc_url(boblift_offer_url()); ?>"><?php echo esc_html(boblift_offer_t($banner['button'])); ?><?php echo boblift_icon('arrow-right', 'h-4 w-4 rtl:rotate-180'); ?></a>
            </div>
        </div>
    </section>
    <?php
}

/* ---------------------------------------------------------------------------------------------------------------- */
/* Inquiries: the 参加活动 field                                                                                     */
/* ---------------------------------------------------------------------------------------------------------------- */

/**
 * The hidden field every inquiry form carries. It is filled with the offer code on the offers page, and in the browser
 * on any page once the visitor has opened the offers page in this tab session (assets/js/theme.js), so the sales team
 * sees the offers brought the inquiry. The plugin keeps it as 参加活动.
 */
function boblift_offer_hidden_field(): string
{
    $value = boblift_offer_page() && !boblift_offer_ended() ? BOBLIFT_OFFER_CODE : '';
    return '<input type="hidden" name="promo" value="' . esc_attr($value) . '" data-offer-field>';
}

/** The words the sales team reads for an offer code; anything else is dropped. */
function boblift_offer_field_label(string $code): string
{
    return $code === BOBLIFT_OFFER_CODE ? '年终活动 2026（满减券 / 定金膨胀）' : '';
}

add_filter('achord_inquiry_fields', static function (array $fields): array {
    $fields['promo'] = ['label' => '参加活动', 'short' => '活动', 'admin' => '参加活动', 'type' => 'text'];
    return $fields;
}, 20);

add_filter('achord_inquiry_submission_fields', static function (array $fields): array {
    $fields['promo'] = boblift_offer_field_label(trim((string) ($fields['promo'] ?? '')));
    return $fields;
}, 20);
