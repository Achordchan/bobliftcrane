<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Google's tags and the cookie banner, the same way the loader site does it (bobliftloader inc/consent.php): the GA4 and
 * Google Ads IDs saved under 表单安全 → 统计与广告 load the Google tag with Consent Mode v2, and a Tag Manager container saved
 * there loads beside it under the same consent defaults. Visitors in the EEA, the UK and
 * Switzerland start with everything denied (Google then sends only cookieless pings) and are asked by the banner; everyone
 * else starts granted and can change it with "Cookie settings" in the footer. The choice is kept in the boblift_consent
 * cookie and applied by assets/js/theme.js. With no ID or container saved, nothing here prints: no tag, no banner, no footer link.
 */
const BOBLIFT_CONSENT_DENIED_REGIONS = ['AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'IS', 'LI', 'NO', 'GB', 'CH'];

/** The local copy never reports to the real GA4 property, Ads account or Tag Manager container. */
function boblift_google_tags_allowed(): bool
{
    return !in_array((string) wp_parse_url(home_url(), PHP_URL_HOST), ['localhost', '127.0.0.1'], true) || apply_filters('boblift_google_tag_on_localhost', false);
}

/**
 * The valid Google tag IDs saved in the admin (comma, space or newline separated), in order, without duplicates. Only GA4 (G-)
 * and Google Ads (AW-) IDs count: each serves one consent category (analytics or advertising).
 */
function boblift_google_tag_ids(): array
{
    if (!boblift_google_tags_allowed()) {
        return [];
    }
    $saved = function_exists('boblift_form_security_options') ? (string) (boblift_form_security_options()['google_tag'] ?? '') : '';
    $ids = preg_split('~[\s,;]+~', strtoupper($saved), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    return array_values(array_unique(array_filter($ids, static fn(string $id): bool => (bool) preg_match('~^(?:G|AW)-[A-Z0-9]{4,20}$~', $id))));
}

/** The Google Ads conversion a sent inquiry reports ("AW-123456789/AbC-dEf"), or '' when none or malformed. */
function boblift_google_conversion(): string
{
    $value = function_exists('boblift_form_security_options') ? trim((string) (boblift_form_security_options()['google_conversion'] ?? '')) : '';
    return preg_match('~^AW-\d{6,15}/[A-Za-z0-9_-]{4,64}$~', $value) ? $value : '';
}

/**
 * The Google Tag Manager container saved in the admin ("GTM-ABC1234"), or '' when none or malformed. The container is for
 * the tags the ad agency manages there; the theme's own GA4 and Ads tags above keep reporting either way.
 */
function boblift_gtm_container(): string
{
    if (!boblift_google_tags_allowed()) {
        return '';
    }
    $value = function_exists('boblift_form_security_options') ? strtoupper(trim((string) (boblift_form_security_options()['gtm_container'] ?? ''))) : '';
    return preg_match('~^GTM-[A-Z0-9]{4,12}$~', $value) ? $value : '';
}

/** The consent categories the saved tags serve, in banner order: analytics for GA4, advertising for Google Ads, both for GTM. */
function boblift_consent_categories(): array
{
    $categories = [];
    foreach (boblift_google_tag_ids() as $id) {
        $categories[str_starts_with($id, 'G-') ? 'analytics' : 'advertising'] = true;
    }
    // What a Tag Manager container fires is set outside the site, so it may need either kind of consent.
    if (boblift_gtm_container() !== '') {
        $categories += ['analytics' => true, 'advertising' => true];
    }
    return array_values(array_intersect(['analytics', 'advertising'], array_keys($categories)));
}

/** The Google tag and Tag Manager container with their consent defaults, for the top of <head>. */
function boblift_google_tag_markup(): string
{
    $ids = boblift_google_tag_ids();
    $gtm = boblift_gtm_container();
    if (!$ids && $gtm === '') {
        return '';
    }
    $denied = ['ad_storage' => 'denied', 'ad_user_data' => 'denied', 'ad_personalization' => 'denied', 'analytics_storage' => 'denied'];
    $granted = array_map(static fn(): string => 'granted', $denied);
    $json = static fn($value): string => (string) wp_json_encode($value, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    // A choice already made overrides both defaults before any tag is configured.
    $script = 'window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}'
        . 'gtag("consent","default",' . $json($denied + ['region' => BOBLIFT_CONSENT_DENIED_REGIONS, 'wait_for_update' => 500]) . ');'
        . 'gtag("consent","default",' . $json($granted) . ');'
        . '(function(){try{var m=document.cookie.match(/(?:^|; )boblift_consent=([^;]*)/),v=m&&JSON.parse(decodeURIComponent(m[1]));if(!v||v.v!==1)return;'
        . 'var a=v.analytics===true?"granted":"denied",d=v.advertising===true?"granted":"denied";'
        . 'gtag("consent","update",{analytics_storage:a,ad_storage:d,ad_user_data:d,ad_personalization:d})}catch(e){}})();'
        . 'gtag("set","ads_data_redaction",true);gtag("js",new Date());window.bobliftTracking=true;'
        . (in_array('advertising', boblift_consent_categories(), true) ? 'window.bobliftAdvertising=true;' : '');
    foreach ($ids as $id) {
        $script .= 'gtag("config",' . $json($id) . ');';
    }
    $conversion = boblift_google_conversion();
    // Only for a Google Ads account whose tag is loaded, which is what puts Advertising in the banner.
    if ($conversion !== '' && in_array(strtok($conversion, '/'), $ids, true)) {
        $script .= 'window.bobliftGoogleConversion=' . $json($conversion) . ';';
    }
    // The Google tag script and the Tag Manager container load straight away, so every visit is counted, including
    // visitors who leave within seconds: the ad team reads short visits per keyword to find search terms that do not
    // work. The commands above sit in dataLayer until the scripts arrive.
    $sources = [];
    if ($ids) {
        $sources[] = 'https://www.googletagmanager.com/gtag/js?id=' . rawurlencode($ids[0]);
    }
    if ($gtm !== '') {
        $sources[] = 'https://www.googletagmanager.com/gtm.js?id=' . rawurlencode($gtm);
    }
    $script .= '(function(){var done=false;function load(){if(done)return;done=true;'
        . ($gtm !== '' ? 'dataLayer.push({"gtm.start":Date.now(),event:"gtm.js"});' : '')
        . $json($sources) . '.forEach(function(u){var s=document.createElement("script");s.async=true;s.src=u;document.head.appendChild(s)})}'
        . 'window.bobliftLoadGoogleTag=load;load()})();';
    return '<script>' . $script . '</script>' . "\n";
}
add_action('wp_head', static function (): void {
    if (!is_admin()) {
        echo boblift_google_tag_markup(); // Built from validated IDs only.
    }
}, 0);

/** The banner's words; translated through inc/translations/consent.php like the rest of the static copy. */
function boblift_consent_text(): array
{
    $text = [
        'title' => 'Cookies and your choices',
        'text' => 'We use analytics and advertising cookies to see how this website is used and to measure our adverts. In the EEA, the UK and Switzerland they stay off unless you agree; everywhere else you can turn them off at any time.',
        'privacy' => 'How we use your information',
        'reject' => 'Reject all',
        'accept' => 'Accept all',
        'choose' => 'Choose',
        'save' => 'Save choices',
        'close' => 'Close',
        'analytics_title' => 'Analytics',
        'analytics_text' => 'Counts visits and shows which pages are useful, so we can improve the website.',
        'advertising_title' => 'Advertising',
        'advertising_text' => 'Measures our adverts and lets us show BOB-LIFT adverts on other websites.',
        'note' => 'This website sets one cookie of its own, to remember your choice for 12 months. You can change it at any time with Cookie settings at the foot of the page.',
        'settings' => 'Cookie settings',
    ];
    return function_exists('boblift_localize_static_value') ? boblift_localize_static_value($text, function_exists('boblift_current_language_slug') ? boblift_current_language_slug() : 'en') : $text;
}

/** The banner, hidden until assets/js/theme.js decides to ask: in the listed regions, or when the footer link is used. */
function boblift_render_consent_banner(): void
{
    $categories = boblift_consent_categories();
    if (!$categories || is_admin()) {
        return;
    }
    $text = boblift_consent_text();
    $button = 'inline-flex min-h-10 flex-1 items-center justify-center rounded-sm border px-4 text-sm font-black transition';
    ?>
    <section class="fixed bottom-3 start-3 z-[270] max-h-[calc(100dvh-24px)] w-[min(400px,calc(100%-24px))] overflow-auto rounded border border-line bg-white p-4 text-ink shadow-raised md:bottom-6 md:start-6 md:p-5" role="dialog" aria-modal="false" aria-labelledby="boblift-consent-title" data-consent-banner data-categories="<?php echo esc_attr(implode(',', $categories)); ?>" data-regions="<?php echo esc_attr(implode(',', BOBLIFT_CONSENT_DENIED_REGIONS)); ?>" hidden>
        <button type="button" class="absolute end-2 top-2 grid h-9 w-9 place-items-center rounded-sm text-slate-500 hover:bg-slate-100 hover:text-ink" data-consent-action="close" aria-label="<?php echo esc_attr($text['close']); ?>" hidden><?php echo boblift_icon('x', 'h-4 w-4'); ?></button>
        <h2 id="boblift-consent-title" class="pe-10 text-base font-black leading-tight focus:outline-none" tabindex="-1"><?php echo esc_html($text['title']); ?></h2>
        <p class="mt-2 text-sm leading-6 text-slate-600"><?php echo esc_html($text['text']); ?> <a class="font-bold text-blue-700 underline underline-offset-2" href="<?php echo esc_url(boblift_url('/privacy-policy.html')); ?>"><?php echo esc_html($text['privacy']); ?></a></p>
        <div class="mt-3 grid gap-2" data-consent-choices hidden>
            <?php foreach ($categories as $category) : ?>
                <label class="flex cursor-pointer gap-3 rounded-sm border border-line p-3">
                    <input class="mt-1 h-4 w-4 shrink-0 accent-blue-700" type="checkbox" name="<?php echo esc_attr($category); ?>">
                    <span class="grid gap-0.5"><strong class="text-sm font-black"><?php echo esc_html($text[$category . '_title']); ?></strong><small class="text-xs leading-5 text-slate-600"><?php echo esc_html($text[$category . '_text']); ?></small></span>
                </label>
            <?php endforeach; ?>
            <p class="text-xs leading-5 text-slate-500"><?php echo esc_html($text['note']); ?></p>
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            <button type="button" class="<?php echo esc_attr($button); ?> border-navy bg-white text-navy hover:bg-slate-50" data-consent-action="reject"><?php echo esc_html($text['reject']); ?></button>
            <button type="button" class="<?php echo esc_attr($button); ?> border-navy bg-navy text-white hover:bg-blue-900" data-consent-action="accept"><?php echo esc_html($text['accept']); ?></button>
            <button type="button" class="<?php echo esc_attr($button); ?> border-navy bg-navy text-white hover:bg-blue-900" data-consent-action="save" hidden><?php echo esc_html($text['save']); ?></button>
            <?php if (count($categories) > 1) : ?>
                <button type="button" class="min-h-10 w-full text-sm font-bold text-blue-700 underline underline-offset-2" data-consent-action="choose" aria-expanded="false"><?php echo esc_html($text['choose']); ?></button>
            <?php endif; ?>
        </div>
    </section>
    <?php
}
add_action('wp_footer', 'boblift_render_consent_banner', 5);

/** The footer's "Cookie settings" link: only when there is something to choose. */
function boblift_consent_settings_link(string $class = ''): string
{
    if (!boblift_consent_categories()) {
        return '';
    }
    return '<button type="button" class="' . esc_attr($class) . '" data-consent-open>' . esc_html(boblift_consent_text()['settings']) . '</button>';
}
