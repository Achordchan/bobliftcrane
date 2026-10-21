<?php
if (!defined('ABSPATH')) {
    exit;
}

function boblift_inquiry_action_url(): string
{
    return esc_url('/?boblift_inquiry_submit=1');
}

function boblift_country_detect_url(): string
{
    return esc_url(add_query_arg('boblift_country_detect', '1', home_url('/')));
}

/**
 * Forms post to the site root, which Polylang treats as English, so replies and the thank-you page came back in English
 * whatever page the visitor was on. The form sends the page's language and the handlers switch to it first.
 */
function boblift_form_hidden_language_field(): string
{
    $language = function_exists('boblift_current_language_slug') ? boblift_current_language_slug() : '';
    return $language !== '' ? '<input type="hidden" name="boblift_lang" value="' . esc_attr($language) . '">' : '';
}

function boblift_switch_to_posted_language(): void
{
    $slug = isset($_POST['boblift_lang']) ? sanitize_key((string) wp_unslash($_POST['boblift_lang'])) : '';
    if ($slug === '' || !function_exists('PLL') || !isset(PLL()->model)) {
        return;
    }
    $language = PLL()->model->get_language($slug);
    if ($language) {
        PLL()->curlang = $language;
    }
}

function boblift_inquiry_success_url(): string
{
    return function_exists('boblift_public_path_url') ? boblift_public_path_url('/success.html') : home_url('/success.html');
}

function boblift_catalog_action_url(): string
{
    return esc_url('/?boblift_catalog_request=1');
}

function boblift_catalog_fail(string $message, int $status = 400): void
{
    wp_send_json_error(['message' => $message], $status);
}

function boblift_catalog_rate_limit_key(string $email): string
{
    $ip = sanitize_text_field((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    return 'boblift_catalog_' . md5(strtolower($email) . '|' . $ip);
}

function boblift_turnstile_config(): array
{
    $options = function_exists('boblift_form_security_options') ? boblift_form_security_options() : [];
    $enabled = (string) ($options['turnstile_enabled'] ?? '0') === '1';
    $local_mode = (string) ($options['turnstile_local_mode'] ?? '0') === '1';
    $site_key = $local_mode ? '1x00000000000000000000AA' : (string) ($options['turnstile_site_key'] ?? '');
    $secret_key = $local_mode ? '1x0000000000000000000000000000000AA' : (string) ($options['turnstile_secret_key'] ?? '');

    return [
        'enabled' => $enabled,
        'site_key' => trim($site_key),
        'secret_key' => trim($secret_key),
    ];
}

function boblift_turnstile_is_enabled(): bool
{
    $config = boblift_turnstile_config();
    return $config['enabled'] && $config['site_key'] !== '' && $config['secret_key'] !== '';
}

function boblift_render_turnstile_widget(string $class = 'mt-4', bool $lazy = false): void
{
    if (!boblift_turnstile_is_enabled()) {
        return;
    }
    $config = boblift_turnstile_config();
    if (!$lazy) {
        wp_enqueue_script('boblift-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', [], null, ['strategy' => 'defer', 'in_footer' => true]);
    }
    ?>
    <div class="boblift-turnstile-wrap <?php echo esc_attr($class); ?>">
        <?php if ($lazy) : ?>
            <div data-turnstile-widget data-sitekey="<?php echo esc_attr($config['site_key']); ?>" data-theme="light"></div>
        <?php else : ?>
            <div class="cf-turnstile" data-sitekey="<?php echo esc_attr($config['site_key']); ?>" data-theme="light"></div>
        <?php endif; ?>
    </div>
    <?php
}

function boblift_render_inquiry_form(string $product = '', array $args = []): void
{
    $ui = boblift_site_ui('quoteForm');
    $contact = boblift_contact_settings();
    $compact = !empty($args['compact']);
    $product_options = $args['product_options'] ?? (function_exists('boblift_product_interest_options') ? boblift_product_interest_options() : boblift_product_options());
    $selected_product = $product;
    $class = 'rounded border border-line bg-white/94 shadow-card ' . ($compact ? 'p-4 md:p-5' : 'p-5 md:p-8');
    $has_selected_option = $selected_product === '';

    foreach ($product_options as $option) {
        if (($option['value'] ?? '') === $selected_product) {
            $has_selected_option = true;
            break;
        }
    }
    ?>
    <form id="<?php echo esc_attr((string) ($args['id'] ?? 'inquiry')); ?>" class="boblift-quote-form scroll-mt-6 <?php echo esc_attr($class); ?>" method="post" action="<?php echo boblift_inquiry_action_url(); ?>" data-quote-form data-country-detect-url="<?php echo esc_url(boblift_country_detect_url()); ?>" data-contact-email="<?php echo esc_attr($contact['email']); ?>" data-whatsapp-number="<?php echo esc_attr($contact['whatsappNumber']); ?>" data-inquiry-subject="<?php echo esc_attr($contact['inquirySubject']); ?>" data-default-inquiry-text="<?php echo esc_attr($contact['defaultInquiryText']); ?>">
        <input type="hidden" name="action" value="boblift_submit_inquiry">
        <?php wp_nonce_field('boblift_submit_inquiry', 'boblift_inquiry_nonce'); ?>
        <input type="hidden" name="source_entry" value="<?php echo esc_attr((string) ($args['source'] ?? '页面表单')); ?>">

        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="section-label mb-3"><?php echo esc_html($ui['label']); ?></p>
                <h2 class="text-2xl font-black leading-tight tracking-[-0.015em] text-ink md:text-4xl"><?php echo esc_html($ui['title']); ?></h2>
            </div>
            <span class="hidden rounded bg-navy px-4 py-2 text-xs font-black text-white md:inline-flex"><?php echo esc_html($ui['badge']); ?></span>
        </div>

        <?php if (isset($_GET['inquiry']) && $_GET['inquiry'] === 'success') : ?>
            <div class="mt-5 rounded border border-emerald-100 bg-emerald-50 p-4 text-sm font-medium leading-6 text-emerald-800">
                <p class="flex items-center gap-2 font-black"><?php echo boblift_icon('check-circle', 'h-4 w-4'); ?> <?php echo esc_html($ui['messages']['successTitle']); ?></p>
                <p class="mt-1"><?php echo esc_html($ui['messages']['successText']); ?></p>
            </div>
        <?php elseif (isset($_GET['inquiry']) && $_GET['inquiry'] === 'verification') : ?>
            <div class="mt-5 rounded border border-red-100 bg-red-50 p-4 text-sm font-medium leading-6 text-red-800"><?php echo esc_html($ui['messages']['verificationRequired'] ?? 'Please complete the verification and try again.'); ?></div>
        <?php elseif (isset($_GET['inquiry']) && $_GET['inquiry'] === 'missing') : ?>
            <div class="mt-5 rounded border border-red-100 bg-red-50 p-4 text-sm font-medium leading-6 text-red-800"><?php echo esc_html($ui['messages']['requiredFields']); ?></div>
        <?php endif; ?>

        <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
            <?php boblift_form_field('name', $ui['fields']['name']); ?>
            <?php boblift_form_field('email', $ui['fields']['email'], 'email', true); ?>
            <?php boblift_form_field('phone', $ui['fields']['phone']); ?>
            <?php boblift_form_field('country', $ui['fields']['country']); ?>

            <label class="block" data-inquiry-select>
                <span class="mb-2 block text-xs font-black uppercase tracking-[0.14em] text-slate-500"><?php echo esc_html($ui['fields']['product']); ?></span>
                <input type="hidden" name="product" data-quote-field="product" value="<?php echo esc_attr($selected_product); ?>" data-inquiry-select-input data-placeholder="<?php echo esc_attr($ui['fields']['productNotDecided']); ?>">
                <span class="relative block">
                    <button class="flex h-12 w-full items-center justify-between gap-3 rounded-sm border border-line bg-[#f8fbff] px-4 text-start text-sm font-bold text-ink outline-none transition hover:border-blue-200 focus:border-blue-300 focus:ring-4 focus:ring-blue-100" type="button" data-inquiry-select-toggle aria-expanded="false">
                        <span class="min-w-0 truncate" data-inquiry-select-label><?php echo esc_html($ui['fields']['productNotDecided']); ?></span>
                        <?php echo boblift_icon('chevron-down', 'h-4 w-4 shrink-0 text-slate-400'); ?>
                    </button>
                    <span class="absolute start-0 end-0 top-full z-50 mt-2 hidden max-h-72 overflow-y-auto rounded border border-blue-100 bg-white p-2 shadow-raised" data-inquiry-select-menu>
                        <button class="flex w-full items-center justify-between gap-3 rounded px-3 py-2.5 text-start text-sm font-bold text-slate-500 transition hover:bg-blue-50 hover:text-blue-700" type="button" data-inquiry-select-option data-value="" data-product-slug="">
                            <span class="min-w-0 truncate" data-inquiry-select-option-label><?php echo esc_html($ui['fields']['productNotDecided']); ?></span>
                            <span class="hidden text-blue-600" data-inquiry-select-check aria-hidden="true"><?php echo boblift_icon('check', 'h-4 w-4'); ?></span>
                        </button>
                        <?php if (!$has_selected_option) : ?>
                            <button class="flex w-full items-center justify-between gap-3 rounded px-3 py-2.5 text-start text-sm font-bold text-slate-700 transition hover:bg-blue-50 hover:text-blue-700" type="button" data-inquiry-select-option data-value="<?php echo esc_attr($selected_product); ?>" data-product-slug="">
                                <span class="min-w-0 truncate" data-inquiry-select-option-label><?php echo esc_html($selected_product); ?></span>
                                <span class="hidden text-blue-600" data-inquiry-select-check aria-hidden="true"><?php echo boblift_icon('check', 'h-4 w-4'); ?></span>
                            </button>
                        <?php endif; ?>
                        <?php foreach ($product_options as $option) : ?>
                            <button class="flex w-full items-center justify-between gap-3 rounded px-3 py-2.5 text-start text-sm font-bold text-slate-700 transition hover:bg-blue-50 hover:text-blue-700" type="button" data-inquiry-select-option data-value="<?php echo esc_attr($option['value']); ?>" data-product-slug="<?php echo esc_attr($option['slug'] ?? ''); ?>">
                                <span class="min-w-0 truncate" data-inquiry-select-option-label><?php echo esc_html($option['label']); ?></span>
                                <span class="hidden text-blue-600" data-inquiry-select-check aria-hidden="true"><?php echo boblift_icon('check', 'h-4 w-4'); ?></span>
                            </button>
                        <?php endforeach; ?>
                    </span>
                </span>
                <input type="hidden" name="product_slug" data-product-slug-field value="">
            </label>

            <?php boblift_form_field('quantity', $ui['fields']['quantity']); ?>

            <label class="block md:col-span-2">
                <span class="mb-2 block text-xs font-black uppercase tracking-[0.14em] text-slate-500"><?php echo esc_html($ui['fields']['message']); ?><?php echo boblift_required_indicator($ui); ?></span>
                <textarea class="min-h-[140px] w-full resize-y rounded border border-line bg-[#f8fbff] px-4 py-3 text-sm font-normal leading-6 text-ink outline-none transition focus:border-blue-300 focus:ring-4 focus:ring-blue-100" name="message" data-quote-field="message" placeholder="<?php echo esc_attr($ui['fields']['messagePlaceholder']); ?>" required></textarea>
            </label>

            <div class="md:col-span-2">
                <?php boblift_render_inquiry_extra_fields($ui); ?>
            </div>
        </div>

        <div class="mt-6 flex flex-col gap-3">
            <?php boblift_render_turnstile_widget('mt-0'); ?>
            <div class="grid grid-cols-3 gap-2 sm:gap-3">
                <button class="inline-flex min-h-12 min-w-0 items-center justify-center gap-1.5 rounded-sm bg-navy px-2 text-[11px] font-black leading-tight text-white shadow-card transition hover:-translate-y-0.5 hover:bg-[#0b2d5b] sm:gap-2 sm:px-5 sm:text-sm lg:px-7" type="submit"><span class="min-w-0 truncate"><?php echo esc_html($ui['buttons']['save']); ?></span> <?php echo boblift_icon('send', 'h-4 w-4 shrink-0'); ?></button>
                <a class="inline-flex min-h-12 min-w-0 items-center justify-center gap-1.5 rounded-sm border border-line bg-white px-2 text-[11px] font-black leading-tight text-ink transition hover:-translate-y-0.5 hover:border-gold sm:gap-2 sm:px-5 sm:text-sm lg:px-7" href="mailto:<?php echo esc_attr($contact['email']); ?>" data-quote-email><span class="min-w-0 truncate"><?php echo esc_html($ui['buttons']['email']); ?></span> <?php echo boblift_icon('mail', 'h-4 w-4 shrink-0'); ?></a>
                <a class="inline-flex min-h-12 min-w-0 items-center justify-center gap-1.5 rounded-sm border border-emerald-100 bg-emerald-50 px-2 text-[11px] font-black leading-tight text-emerald-700 transition hover:-translate-y-0.5 sm:gap-2 sm:px-5 sm:text-sm lg:px-7" href="<?php echo esc_url($contact['whatsappHref']); ?>" rel="noreferrer" target="_blank" data-quote-whatsapp><span class="min-w-0 truncate"><?php echo esc_html($ui['buttons']['whatsapp']); ?></span> <?php echo boblift_icon('message-circle', 'h-4 w-4 shrink-0'); ?></a>
            </div>
        </div>
    </form>
    <?php
}

function boblift_render_catalog_modal(): void
{
    if (is_admin()) {
        return;
    }
    $ui = function_exists('boblift_site_ui') ? boblift_site_ui('productDetail') : [];
    $catalog_ui = is_array($ui['catalogLead'] ?? null) ? $ui['catalogLead'] : [];
    $brochure_ui = function_exists('boblift_site_ui') ? boblift_site_ui('brochures') : [];
    ?>
    <div class="fixed inset-0 z-[270] hidden items-end justify-center bg-[#071f42]/45 p-0 backdrop-blur-sm md:items-center md:p-6" data-catalog-modal data-catalog-direct-text="<?php echo esc_attr($brochure_ui['modalDirectText'] ?? ''); ?>" aria-hidden="true">
        <button class="absolute inset-0 h-full w-full cursor-default" type="button" data-catalog-modal-close aria-label="<?php echo esc_attr($catalog_ui['closeLabel'] ?? 'Close catalog request'); ?>"></button>
        <div class="relative w-full max-w-[560px] overflow-hidden rounded-t border border-white/70 bg-white shadow-card " role="dialog" aria-modal="true" aria-labelledby="catalog-modal-title" tabindex="-1">
            <form class="p-5 md:p-7" method="post" action="<?php echo boblift_catalog_action_url(); ?>" data-catalog-form novalidate>
                <input type="hidden" name="action" value="boblift_request_catalog">
                <input type="hidden" name="boblift_catalog_response" value="json">
                <?php echo boblift_form_hidden_language_field(); ?>
                <?php wp_nonce_field('boblift_catalog_request', 'boblift_catalog_nonce'); ?>
                <input type="hidden" name="product" data-catalog-product-field value="">
                <input type="hidden" name="product_slug" data-catalog-product-slug-field value="">
                <input type="hidden" name="catalog_url" data-catalog-url-field value="">
                <label class="sr-only" aria-hidden="true">Website <input class="hidden" type="text" name="boblift_catalog_website" tabindex="-1" autocomplete="off"></label>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="boblift-kicker"><?php echo esc_html($catalog_ui['label'] ?? 'PDF Catalog'); ?></p>
                        <h2 id="catalog-modal-title" class="mt-2 text-2xl font-black leading-tight tracking-[-0.015em] text-ink md:text-3xl"><?php echo esc_html($catalog_ui['title'] ?? 'Get the product catalog'); ?></h2>
                        <p class="mt-3 text-sm font-normal leading-6 text-slate-600" data-catalog-description><?php echo esc_html($catalog_ui['description'] ?? 'Enter your email to receive the PDF catalog. The download link will appear immediately after submission.'); ?></p>
                    </div>
                    <button class="grid h-10 w-10 shrink-0 place-items-center rounded-sm border border-line bg-white text-slate-500 transition hover:border-blue-200 hover:text-blue-700" type="button" data-catalog-modal-close aria-label="<?php echo esc_attr($catalog_ui['closeLabel'] ?? 'Close catalog request'); ?>"><?php echo boblift_icon('x', 'h-5 w-5'); ?></button>
                </div>
                <a class="mt-5 hidden h-11 items-center justify-center gap-2 rounded-sm border border-line bg-white px-5 text-sm font-black text-navy transition hover:border-blue-300" href="#" download data-catalog-direct-link><?php echo boblift_icon('download', 'h-4 w-4'); ?><?php echo esc_html($brochure_ui['modalDirectLabel'] ?? 'Download the PDF now'); ?></a>
                <div class="mt-5 hidden rounded border border-line bg-[#f8fbff] p-3 text-sm font-normal leading-6 text-slate-600" data-catalog-status role="status"></div>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <label class="block sm:col-span-2">
                        <span class="mb-2 block text-xs font-black uppercase tracking-[0.14em] text-slate-500"><?php echo esc_html($catalog_ui['emailLabel'] ?? 'Email'); ?><?php echo boblift_required_indicator(['fieldRequiredLabel' => $catalog_ui['requiredLabel'] ?? 'Required']); ?></span>
                        <input class="h-12 w-full rounded-sm border border-line bg-[#f8fbff] px-4 text-sm font-bold text-ink outline-none transition focus:border-blue-300 focus:ring-4 focus:ring-blue-100" name="email" type="email" required autocomplete="email" data-catalog-email-field>
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-xs font-black uppercase tracking-[0.14em] text-slate-500"><?php echo esc_html($catalog_ui['nameLabel'] ?? 'Name'); ?></span>
                        <input class="h-12 w-full rounded-sm border border-line bg-[#f8fbff] px-4 text-sm font-bold text-ink outline-none transition focus:border-blue-300 focus:ring-4 focus:ring-blue-100" name="name" type="text" autocomplete="name">
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-xs font-black uppercase tracking-[0.14em] text-slate-500"><?php echo esc_html($catalog_ui['phoneLabel'] ?? 'WhatsApp / Phone'); ?></span>
                        <input class="h-12 w-full rounded-sm border border-line bg-[#f8fbff] px-4 text-sm font-bold text-ink outline-none transition focus:border-blue-300 focus:ring-4 focus:ring-blue-100" name="phone" type="text" autocomplete="tel">
                    </label>
                    <label class="block sm:col-span-2">
                        <span class="mb-2 block text-xs font-black uppercase tracking-[0.14em] text-slate-500"><?php echo esc_html($catalog_ui['companyLabel'] ?? 'Company'); ?></span>
                        <input class="h-12 w-full rounded-sm border border-line bg-[#f8fbff] px-4 text-sm font-bold text-ink outline-none transition focus:border-blue-300 focus:ring-4 focus:ring-blue-100" name="company" type="text" autocomplete="organization">
                    </label>
                </div>
                <div class="mt-5 hidden rounded border border-emerald-100 bg-emerald-50 p-4 text-sm font-bold leading-6 text-emerald-800" data-catalog-success>
                    <span data-catalog-success-text><?php echo esc_html($catalog_ui['successText'] ?? 'Catalog request saved. We also sent the catalog link to your email.'); ?></span>
                    <a class="mt-3 inline-flex h-11 items-center justify-center gap-2 rounded-sm bg-navy px-5 text-sm font-black text-white transition hover:-translate-y-0.5 hover:bg-[#0b2d5b]" href="#" target="_blank" rel="noreferrer" data-catalog-download-link><?php echo boblift_icon('download', 'h-4 w-4'); ?><?php echo esc_html($catalog_ui['downloadLabel'] ?? 'Download PDF'); ?></a>
                </div>
                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs font-normal leading-5 text-slate-500"><?php echo esc_html($catalog_ui['privacyText'] ?? 'We only use your email to send the requested catalog and follow up professionally.'); ?></p>
                    <button class="h-12 shrink-0 rounded-sm bg-navy px-7 text-sm font-black text-white shadow-card transition hover:-translate-y-0.5 hover:bg-[#0b2d5b] disabled:cursor-not-allowed disabled:opacity-60" type="submit" data-catalog-submit><?php echo esc_html($catalog_ui['submitLabel'] ?? 'Get PDF'); ?> <?php echo boblift_icon('send', 'ms-1 inline h-4 w-4'); ?></button>
                </div>
            </form>
        </div>
    </div>
    <?php
}

function boblift_render_inquiry_modal(): void
{
    if (is_admin()) {
        return;
    }
    $ui = boblift_site_ui('quoteForm');
    $contact = boblift_contact_settings();
    ?>
    <div class="boblift-inquiry-modal fixed inset-0 z-[260] hidden items-end justify-center bg-[#071f42]/45 p-0 backdrop-blur-sm md:items-center md:p-6" data-global-inquiry-modal aria-hidden="true">
        <button class="absolute inset-0 h-full w-full cursor-default" type="button" data-inquiry-modal-close aria-label="<?php echo esc_attr($ui['closeLabel'] ?? 'Close inquiry form'); ?>"></button>
        <div class="boblift-inquiry-dialog relative flex outline-none max-h-[94dvh] w-full max-w-5xl flex-col overflow-hidden rounded-t bg-white shadow-raised md:max-h-[calc(100dvh-32px)] md:rounded" role="dialog" aria-modal="true" aria-labelledby="global-inquiry-title" tabindex="-1">
            <div class="boblift-inquiry-layout grid min-h-0 md:grid-cols-[0.72fr_1.28fr]">
                <aside class="relative hidden bg-[linear-gradient(145deg,#071f42_0%,#0b3f7d_58%,#2563eb_100%)] p-8 text-white md:block">
                    <span class="pointer-events-none absolute -end-16 top-10 h-48 w-48 rounded-full bg-white/12 blur-2xl"></span>
                    <span class="pointer-events-none absolute -bottom-20 start-8 h-56 w-56 rounded-full bg-gold/18 blur-3xl"></span>
                    <div class="relative z-10 max-w-[320px]">
                        <p class="text-[11px] font-black uppercase tracking-[0.28em] text-gold"><?php echo esc_html($ui['sideLabel'] ?? 'BOB-LIFT Inquiry'); ?></p>
                        <h2 class="mt-5 text-4xl font-black leading-[0.98] tracking-[-0.02em]"><?php echo esc_html($ui['sideTitle'] ?? 'Tell us what you need to lift.'); ?></h2>
                        <p class="mt-5 text-sm font-normal leading-7 text-white/76"><?php echo esc_html($ui['sideDescription'] ?? 'Share your contact details and working conditions. Our team will turn it into a clear quotation request.'); ?></p>
                    </div>
                    <div class="boblift-inquiry-character-stage relative mt-6 h-[300px] w-full" data-inquiry-characters>
                        <div class="boblift-character-privacy-bubble" data-character-privacy-note aria-hidden="true"><?php echo esc_html($ui['privacyBubbleText'] ?? 'We strictly protect your privacy information.'); ?></div>
                        <div class="boblift-character-stage" data-character-stage aria-hidden="true">
                            <div class="boblift-character-body boblift-character-body-purple" data-character-role="purple">
                                <div class="boblift-character-face boblift-character-face-purple" data-character-face="purple">
                                    <div class="boblift-character-eye boblift-character-eye-large" data-character-eye="left"><div class="boblift-character-pupil boblift-character-pupil-large" data-character-pupil="left"></div></div>
                                    <div class="boblift-character-eye boblift-character-eye-large" data-character-eye="right"><div class="boblift-character-pupil boblift-character-pupil-large" data-character-pupil="right"></div></div>
                                </div>
                            </div>
                            <div class="boblift-character-body boblift-character-body-black" data-character-role="black">
                                <div class="boblift-character-face boblift-character-face-black" data-character-face="black">
                                    <div class="boblift-character-eye boblift-character-eye-small" data-character-eye="left"><div class="boblift-character-pupil boblift-character-pupil-small" data-character-pupil="left"></div></div>
                                    <div class="boblift-character-eye boblift-character-eye-small" data-character-eye="right"><div class="boblift-character-pupil boblift-character-pupil-small" data-character-pupil="right"></div></div>
                                </div>
                            </div>
                            <div class="boblift-character-body boblift-character-body-crane" data-character-role="orange">
                                <img class="boblift-character-crane-image" data-inquiry-lazy-src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/ui/inquiry-crane-mascot.webp'); ?>" alt="" loading="lazy" decoding="async">
                                <div class="boblift-character-face boblift-character-face-crane" data-character-face="orange">
                                    <div class="boblift-character-eye boblift-character-eye-crane" data-character-eye="left"><div class="boblift-character-pupil boblift-character-pupil-crane" data-character-pupil="left"></div></div>
                                    <div class="boblift-character-eye boblift-character-eye-crane" data-character-eye="right"><div class="boblift-character-pupil boblift-character-pupil-crane" data-character-pupil="right"></div></div>
                                </div>
                            </div>
                            <div class="boblift-character-body boblift-character-body-yellow" data-character-role="yellow">
                                <div class="boblift-character-face boblift-character-face-yellow" data-character-face="yellow">
                                    <div class="boblift-character-pupil boblift-character-pupil-alone" data-character-pupil="left"></div>
                                    <div class="boblift-character-pupil boblift-character-pupil-alone" data-character-pupil="right"></div>
                                </div>
                                <div class="boblift-character-mouth" data-character-mouth></div>
                            </div>
                        </div>
                    </div>
                </aside>
                <form class="boblift-inquiry-modal-form flex min-h-0 flex-col overflow-hidden p-5 md:p-6 lg:p-7" method="post" action="<?php echo boblift_inquiry_action_url(); ?>" data-inquiry-modal-form data-quote-form data-success-url="<?php echo esc_url(boblift_inquiry_success_url()); ?>" data-country-detect-url="<?php echo esc_url(boblift_country_detect_url()); ?>" data-contact-email="<?php echo esc_attr($contact['email']); ?>" data-whatsapp-number="<?php echo esc_attr($contact['whatsappNumber']); ?>" data-inquiry-subject="<?php echo esc_attr($contact['inquirySubject']); ?>" data-default-inquiry-text="<?php echo esc_attr($contact['defaultInquiryText']); ?>" novalidate>
                    <input type="hidden" name="action" value="boblift_submit_inquiry">
                    <input type="hidden" name="boblift_inquiry_response" value="json">
                    <?php echo boblift_form_hidden_language_field(); ?>
                    <?php wp_nonce_field('boblift_submit_inquiry', 'boblift_inquiry_nonce'); ?>
                    <input type="hidden" name="source_entry" value="" data-inquiry-source-field>
                    <input type="hidden" name="product" data-quote-field="product" value="">
                    <input type="hidden" name="product_slug" data-product-slug-field value="">
                    <input type="hidden" name="quantity" data-quote-field="quantity" value="">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="boblift-kicker"><?php echo esc_html($ui['modalLabel'] ?? 'Contact BOB-LIFT'); ?></p>
                            <h2 id="global-inquiry-title" class="mt-2 text-2xl font-black leading-tight tracking-[-0.015em] text-ink md:text-3xl"><?php echo esc_html($ui['modalTitle'] ?? 'Send your inquiry'); ?></h2>
                        </div>
                        <button class="grid h-10 w-10 shrink-0 place-items-center rounded-sm border border-line bg-white text-slate-500 transition hover:border-blue-200 hover:text-blue-700" type="button" data-inquiry-modal-close aria-label="<?php echo esc_attr($ui['closeLabel'] ?? 'Close inquiry form'); ?>"><?php echo boblift_icon('x', 'h-5 w-5'); ?></button>
                    </div>

                    <div class="mt-4 rounded border border-line bg-[#f8fbff] p-3 text-sm font-normal leading-6 text-slate-600 hidden" data-inquiry-modal-status role="status"></div>

                    <?php // The 4px padding (offset by the negative margin) keeps the inputs' focus ring inside the scroll box, which would clip it. ?>
                    <div class="boblift-inquiry-fields-scroll -mx-1 mt-4 grid min-h-0 flex-1 gap-4 overflow-y-auto p-1">
                        <div class="boblift-inquiry-primary-fields grid gap-4 md:grid-cols-2">
                            <?php boblift_form_field('name', $ui['fields']['name']); ?>
                            <?php boblift_form_field('email', $ui['fields']['email'], 'email', true); ?>
                            <?php boblift_form_field('phone', $ui['fields']['phone']); ?>
                            <?php boblift_form_field('country', $ui['fields']['country']); ?>
                        </div>
                        <label class="block">
                            <span class="mb-2 block text-xs font-black uppercase tracking-[0.14em] text-slate-500"><?php echo esc_html($ui['fields']['message']); ?><?php echo boblift_required_indicator($ui); ?></span>
                            <textarea class="block min-h-[120px] w-full resize-y rounded border border-line bg-[#f8fbff] px-4 py-3 text-sm font-normal leading-6 text-ink outline-none transition focus:border-blue-300 focus:ring-4 focus:ring-blue-100 md:min-h-[128px] lg:min-h-[140px]" name="message" data-quote-field="message" placeholder="<?php echo esc_attr($ui['fields']['messagePlaceholder']); ?>" required></textarea>
                        </label>
                        <?php boblift_render_inquiry_extra_fields($ui); ?>
                    </div>

                    <div class="mt-4 shrink-0 border-t border-line pt-4"><div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="hidden rounded border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-bold leading-6 text-emerald-800" data-inquiry-modal-success><?php echo boblift_icon('check-circle', 'mr-2 inline h-4 w-4'); ?><?php echo esc_html($ui['successInlineText'] ?? 'Inquiry sent. Our team will contact you soon.'); ?></div>
                        <?php boblift_render_turnstile_widget('mt-0 shrink-0', true); ?>
                        <button class="h-12 rounded-sm bg-navy px-7 text-sm font-black text-white shadow-card transition hover:-translate-y-0.5 hover:bg-[#0b2d5b] disabled:cursor-not-allowed disabled:opacity-60 sm:ms-auto" type="submit" data-inquiry-modal-submit><?php echo esc_html($ui['submitLabel'] ?? 'Send Inquiry'); ?> <?php echo boblift_icon('send', 'ms-1 inline h-4 w-4'); ?></button>
                    </div></div>
                </form>
                <aside class="boblift-inquiry-products-panel hidden min-h-0 border-t border-line bg-[#F8FBFF] p-5 md:border-s md:border-t-0 md:p-6" data-inquiry-selected-products aria-label="<?php echo esc_attr($ui['selectedProductsAriaLabel'] ?? 'Selected products'); ?>">
                    <div class="flex h-full min-h-0 flex-col">
                        <div class="shrink-0">
                            <p class="boblift-kicker"><?php echo esc_html($ui['selectedProductsLabel'] ?? 'Selected products'); ?></p>
                            <h3 class="mt-2 text-xl font-black tracking-[-0.015em] text-ink"><?php echo esc_html($ui['selectedProductsTitle'] ?? 'Products in this inquiry'); ?></h3>
                            <p class="mt-2 text-sm font-normal leading-6 text-slate-500"><span data-inquiry-selected-products-count>0</span> <?php echo esc_html($ui['selectedProductsSummary'] ?? 'products will be included in this inquiry.'); ?></p>
                        </div>
                        <div class="mt-4 flex min-h-0 flex-1 flex-col gap-3 overflow-y-auto pe-1" data-inquiry-selected-products-list></div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
    <?php
}

function boblift_required_indicator(array $ui): string
{
    $label = (string) ($ui['fieldRequiredLabel'] ?? 'Required');
    return ' <span class="text-red-500" aria-hidden="true">*</span><span class="sr-only"> ' . esc_html($label) . '</span>';
}

function boblift_inquiry_extra_field_names(): array
{
    return ['truck_available', 'truck_model', 'truck_specs', 'imported_from_china', 'customs_clearance', 'destination_port', 'preferred_delivery', 'contact_method', 'preferred_contact_time', 'other_requirements'];
}

function boblift_inquiry_extra_field_label(array $ui, string $name, string $fallback): string
{
    return (string) (($ui['extraFields'][$name] ?? '') ?: $fallback);
}

function boblift_render_inquiry_extra_select(array $ui, string $name, string $label, array $options, string $class = ''): void
{
    $placeholder = (string) ($ui['extraSelectPlaceholder'] ?? 'Select one');
    ?>
    <div class="block <?php echo esc_attr($class); ?>" data-inquiry-select>
        <span class="mb-2 block text-[11px] font-black uppercase tracking-[0.12em] text-slate-500"><?php echo esc_html($label); ?></span>
        <input type="hidden" name="<?php echo esc_attr($name); ?>" data-quote-field="<?php echo esc_attr($name); ?>" value="" data-inquiry-select-input data-placeholder="<?php echo esc_attr($placeholder); ?>">
        <span class="relative block">
            <button class="flex h-11 w-full items-center justify-between gap-3 rounded-sm border border-line bg-white px-3 text-start text-sm font-bold text-ink outline-none transition hover:border-blue-200 focus:border-blue-300 focus:ring-4 focus:ring-blue-100" type="button" data-inquiry-select-toggle aria-expanded="false">
                <span class="min-w-0 truncate" data-inquiry-select-label><?php echo esc_html($placeholder); ?></span>
                <span class="shrink-0 text-xs text-slate-400" aria-hidden="true">⌄</span>
            </button>
            <span class="absolute start-0 end-0 top-full z-50 mt-2 hidden max-h-52 overflow-y-auto rounded border border-blue-100 bg-white p-1.5 shadow-raised" data-inquiry-select-menu>
                <button class="flex w-full items-center justify-between rounded px-3 py-2 text-start text-sm font-bold text-slate-500 transition hover:bg-blue-50 hover:text-blue-700" type="button" data-inquiry-select-option data-value="">
                    <span class="min-w-0 truncate" data-inquiry-select-option-label><?php echo esc_html($placeholder); ?></span>
                    <span class="hidden text-blue-600" data-inquiry-select-check aria-hidden="true">✓</span>
                </button>
                <?php foreach ($options as $option) : ?>
                    <button class="flex w-full items-center justify-between rounded px-3 py-2 text-start text-sm font-bold text-slate-700 transition hover:bg-blue-50 hover:text-blue-700" type="button" data-inquiry-select-option data-value="<?php echo esc_attr($option); ?>">
                        <span class="min-w-0 truncate" data-inquiry-select-option-label><?php echo esc_html($option); ?></span>
                        <span class="hidden text-blue-600" data-inquiry-select-check aria-hidden="true">✓</span>
                    </button>
                <?php endforeach; ?>
            </span>
        </span>
    </div>
    <?php
}

function boblift_render_inquiry_extra_input(string $name, string $label, string $class = ''): void
{
    ?>
    <label class="block <?php echo esc_attr($class); ?>">
        <span class="mb-2 block text-[11px] font-black uppercase tracking-[0.12em] text-slate-500"><?php echo esc_html($label); ?></span>
        <input class="h-11 w-full rounded-sm border border-line bg-white px-3 text-sm font-bold text-ink outline-none transition focus:border-blue-300 focus:ring-4 focus:ring-blue-100" name="<?php echo esc_attr($name); ?>" data-quote-field="<?php echo esc_attr($name); ?>" type="text">
    </label>
    <?php
}

function boblift_render_inquiry_extra_fields(array $ui): void
{
    $select_options = is_array($ui['selectOptions'] ?? null) ? $ui['selectOptions'] : [];
    $option_list = static function (string $name, array $fallback) use ($select_options): array {
        $options = is_array($select_options[$name] ?? null) ? $select_options[$name] : $fallback;
        return array_values(array_filter(array_map('strval', $options), static fn(string $option): bool => trim($option) !== ''));
    };
    ?>
    <div>
        <button class="inline-flex min-h-9 items-center justify-center rounded-sm border border-blue-100 bg-blue-50 px-4 py-2 text-xs font-black text-blue-700 transition hover:-translate-y-0.5 hover:border-blue-200 hover:bg-white" type="button" data-inquiry-extra-toggle data-label-open="<?php echo esc_attr($ui['extraToggleOpen'] ?? 'Add details for an accurate solution'); ?>" data-label-close="<?php echo esc_attr($ui['extraToggleClose'] ?? 'Hide additional details'); ?>" aria-expanded="false">
            <span data-inquiry-extra-toggle-label><?php echo esc_html($ui['extraToggleOpen'] ?? 'Add details for an accurate solution'); ?></span>
        </button>
        <div class="mt-3 hidden rounded border border-blue-100 bg-[#f8fbff] p-4 shadow-card" data-inquiry-extra-panel>
            <div class="grid gap-3 md:grid-cols-2">
                <?php
                boblift_render_inquiry_extra_input('company', (string) ($ui['fields']['company'] ?? 'Company'));
                boblift_render_inquiry_extra_input('website', (string) ($ui['fields']['website'] ?? 'Website'));
                boblift_render_inquiry_extra_select($ui, 'truck_available', boblift_inquiry_extra_field_label($ui, 'truck_available', 'Do you already have a truck?'), $option_list('truck_available', ['Yes', 'No', 'Not sure']));
                boblift_render_inquiry_extra_input('truck_model', boblift_inquiry_extra_field_label($ui, 'truck_model', 'Truck model or chassis brand'), 'hidden');
                boblift_render_inquiry_extra_input('truck_specs', boblift_inquiry_extra_field_label($ui, 'truck_specs', 'Truck chassis specs'), 'hidden');
                boblift_render_inquiry_extra_select($ui, 'imported_from_china', boblift_inquiry_extra_field_label($ui, 'imported_from_china', 'Have you imported from China before?'), $option_list('imported_from_china', ['Yes', 'No']));
                boblift_render_inquiry_extra_select($ui, 'customs_clearance', boblift_inquiry_extra_field_label($ui, 'customs_clearance', 'Can you handle customs clearance?'), $option_list('customs_clearance', ['Yes', 'No', 'Need support']));
                boblift_render_inquiry_extra_input('destination_port', boblift_inquiry_extra_field_label($ui, 'destination_port', 'Destination port / city'));
                boblift_render_inquiry_extra_input('preferred_delivery', boblift_inquiry_extra_field_label($ui, 'preferred_delivery', 'Preferred delivery time'));
                boblift_render_inquiry_extra_select($ui, 'contact_method', boblift_inquiry_extra_field_label($ui, 'contact_method', 'How would you like us to contact you?'), $option_list('contact_method', ['WhatsApp', 'Email', 'Either']));
                boblift_render_inquiry_extra_input('preferred_contact_time', boblift_inquiry_extra_field_label($ui, 'preferred_contact_time', 'Preferred contact time'));
                // The cell beside the last short field tells buyers all of this is optional.
                ?>
                <p class="self-end text-sm font-bold leading-6 text-slate-500 md:pb-2.5"><?php echo esc_html(boblift_localized_static_text('Additional details are optional — you can send your inquiry without them.')); ?></p>
                <?php
                ?>
                <label class="block md:col-span-2">
                    <span class="mb-2 block text-[11px] font-black uppercase tracking-[0.12em] text-slate-500"><?php echo esc_html(boblift_inquiry_extra_field_label($ui, 'other_requirements', 'Other requirements')); ?></span>
                    <textarea class="min-h-[84px] w-full resize-y rounded border border-line bg-white px-3 py-3 text-sm font-normal leading-6 text-ink outline-none transition focus:border-blue-300 focus:ring-4 focus:ring-blue-100" name="other_requirements" data-quote-field="other_requirements"></textarea>
                </label>
                <p class="md:col-span-2 text-xs font-bold leading-5 text-slate-500"><?php echo esc_html($ui['contactTimeNote'] ?? 'Our working hours are Monday to Friday, 8:30 AM–6:00 PM Beijing time.'); ?></p>
            </div>
        </div>
    </div>
    <?php
}

function boblift_form_field(string $name, string $label, string $type = 'text', bool $required = false): void
{
    $ui = function_exists('boblift_site_ui') ? boblift_site_ui('quoteForm') : [];
    ?>
    <label class="block">
        <span class="mb-2 block text-xs font-black uppercase tracking-[0.14em] text-slate-500"><?php echo esc_html($label); ?><?php echo $required ? boblift_required_indicator($ui) : ''; ?></span>
        <input class="h-12 w-full rounded-sm border border-line bg-[#f8fbff] px-4 text-sm font-bold text-ink outline-none transition focus:border-blue-300 focus:ring-4 focus:ring-blue-100" name="<?php echo esc_attr($name); ?>" data-quote-field="<?php echo esc_attr($name); ?>" type="<?php echo esc_attr($type); ?>" <?php echo $required ? 'required' : ''; ?> autocomplete="<?php echo esc_attr($name === 'email' ? 'email' : ($name === 'name' ? 'name' : '')); ?>">
    </label>
    <?php
}

function boblift_inquiry_wants_json(): bool
{
    $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
    $requested_with = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
    $response_format = isset($_POST['boblift_inquiry_response']) ? sanitize_key((string) wp_unslash($_POST['boblift_inquiry_response'])) : '';
    return $response_format === 'json' || str_contains($accept, 'application/json') || $requested_with === 'xmlhttprequest';
}

function boblift_inquiry_fail(string $message, string $redirect_status = 'missing'): void
{
    if (boblift_inquiry_wants_json()) {
        wp_send_json_error(['message' => $message], 400);
    }
    wp_safe_redirect(add_query_arg('inquiry', $redirect_status, wp_get_referer() ?: boblift_url('/contact.html')));
    exit;
}

function boblift_country_code_from_request(): string
{
    $header_keys = [
        'HTTP_CF_IPCOUNTRY',
        'HTTP_CLOUDFRONT_VIEWER_COUNTRY',
        'HTTP_X_APPENGINE_COUNTRY',
        'HTTP_X_COUNTRY_CODE',
        'HTTP_X_GEOIP_COUNTRY_CODE',
        'HTTP_GEOIP_COUNTRY_CODE',
    ];
    foreach ($header_keys as $key) {
        $code = strtoupper(trim((string) ($_SERVER[$key] ?? '')));
        if (preg_match('/^[A-Z]{2}$/', $code) && !in_array($code, ['XX', 'ZZ'], true)) {
            return $code;
        }
    }
    return '';
}

function boblift_handle_country_detect_front_controller(): void
{
    if (!isset($_GET['boblift_country_detect']) || (string) $_GET['boblift_country_detect'] !== '1') {
        return;
    }
    nocache_headers();
    wp_send_json_success(['countryCode' => boblift_country_code_from_request()]);
}
add_action('init', 'boblift_handle_country_detect_front_controller', 0);

function boblift_verify_turnstile(): bool
{
    if (!boblift_turnstile_is_enabled()) {
        return true;
    }
    $token = isset($_POST['cf-turnstile-response']) ? sanitize_text_field((string) wp_unslash($_POST['cf-turnstile-response'])) : '';
    if ($token === '') {
        return false;
    }
    $config = boblift_turnstile_config();
    $response = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
        'timeout' => 8,
        'body' => [
            'secret' => $config['secret_key'],
            'response' => $token,
            'remoteip' => sanitize_text_field((string) ($_SERVER['REMOTE_ADDR'] ?? '')),
        ],
    ]);
    if (is_wp_error($response)) {
        return false;
    }
    $body = json_decode((string) wp_remote_retrieve_body($response), true);
    return is_array($body) && !empty($body['success']);
}

function boblift_handle_catalog_request(): void
{
    boblift_switch_to_posted_language();
    $ui = function_exists('boblift_site_ui') ? boblift_site_ui('productDetail') : [];
    $catalog_ui = is_array($ui['catalogLead'] ?? null) ? $ui['catalogLead'] : [];
    if (!isset($_POST['boblift_catalog_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['boblift_catalog_nonce'])), 'boblift_catalog_request')) {
        boblift_catalog_fail((string) ($catalog_ui['invalidRequest'] ?? 'Invalid catalog request. Please refresh and try again.'), 403);
    }
    $honeypot = isset($_POST['boblift_catalog_website']) ? trim((string) wp_unslash($_POST['boblift_catalog_website'])) : '';
    if ($honeypot !== '') {
        boblift_catalog_fail((string) ($catalog_ui['saveFailed'] ?? 'The catalog request could not be saved. Please try again.'), 400);
    }

    $fields = [];
    foreach (['name', 'email', 'phone', 'company', 'product', 'product_slug'] as $key) {
        $value = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : '';
        $fields[$key] = $key === 'email' ? sanitize_email($value) : sanitize_text_field($value);
    }
    $fields['catalog_url'] = isset($_POST['catalog_url']) ? esc_url_raw((string) wp_unslash($_POST['catalog_url'])) : '';
    $fields['message'] = 'PDF catalog request';
    $fields['lead_type'] = 'catalog_request';

    if ($fields['email'] === '' || !is_email($fields['email'])) {
        boblift_catalog_fail((string) ($catalog_ui['requiredFields'] ?? 'Please enter a valid email address.'));
    }

    $rate_key = boblift_catalog_rate_limit_key($fields['email']);
    if (get_transient($rate_key)) {
        boblift_catalog_fail((string) ($catalog_ui['rateLimited'] ?? 'Please wait a few minutes before requesting another catalog.'), 429);
    }
    set_transient($rate_key, '1', 5 * MINUTE_IN_SECONDS);

    $customer_title = $fields['name'] !== '' ? $fields['name'] : ($fields['company'] !== '' ? $fields['company'] : $fields['email']);
    $post_id = wp_insert_post([
        'post_type' => 'inquiry',
        'post_status' => 'publish',
        'post_title' => sprintf('Catalog request from %s - %s', $customer_title, current_time('Y-m-d H:i')),
    ], true);

    if (is_wp_error($post_id)) {
        boblift_catalog_fail((string) ($catalog_ui['saveFailed'] ?? 'The catalog request could not be saved. Please try again.'), 500);
    }

    foreach ($fields as $key => $value) {
        update_post_meta((int) $post_id, '_' . $key, $value);
    }

    $labels = [
        'lead_type' => 'Lead type',
        'name' => 'Name',
        'email' => 'Email',
        'phone' => 'WhatsApp / Phone',
        'company' => 'Company',
        'product' => 'Product',
        'catalog_url' => 'Catalog URL',
    ];
    $lines = ['New BOB-LIFT PDF catalog request'];
    foreach ($labels as $key => $label) {
        $lines[] = $label . ': ' . ($fields[$key] !== '' ? $fields[$key] : '-');
    }
    wp_mail(get_option('admin_email'), (string) ($catalog_ui['adminMailSubject'] ?? 'New BOB-LIFT catalog request'), implode("\n", $lines), ['Reply-To: ' . $fields['email']]);

    $product_label = $fields['product'] !== '' ? $fields['product'] : 'BOB-LIFT product';
    $customer_lines = [
        sprintf((string) ($catalog_ui['customerMailGreeting'] ?? 'Thank you for requesting the %s catalog.'), $product_label),
    ];
    if ($fields['catalog_url'] !== '') {
        $customer_lines[] = (string) ($catalog_ui['customerMailDownloadIntro'] ?? 'You can download it here:');
        $customer_lines[] = $fields['catalog_url'];
    } else {
        $customer_lines[] = (string) ($catalog_ui['customerMailNoFile'] ?? 'Our team will send the correct PDF catalog to you shortly.');
    }
    $customer_lines[] = '';
    $customer_lines[] = 'BOB-LIFT';
    wp_mail($fields['email'], sprintf((string) ($catalog_ui['customerMailSubject'] ?? 'Your BOB-LIFT %s catalog'), $product_label), implode("\n", $customer_lines));

    wp_send_json_success([
        'message' => $fields['catalog_url'] !== '' ? (string) ($catalog_ui['successText'] ?? 'Catalog request saved. We also sent the catalog link to your email.') : (string) ($catalog_ui['successNoFileToast'] ?? 'Request saved. We will email the PDF.'),
        'id' => (int) $post_id,
        'downloadUrl' => $fields['catalog_url'],
    ]);
}

function boblift_handle_catalog_front_controller(): void
{
    if (!isset($_GET['boblift_catalog_request']) || (string) $_GET['boblift_catalog_request'] !== '1') {
        return;
    }
    if (!isset($_POST['action']) || sanitize_key((string) wp_unslash($_POST['action'])) !== 'boblift_request_catalog') {
        return;
    }
    boblift_handle_catalog_request();
}
add_action('init', 'boblift_handle_catalog_front_controller', 0);
add_action('admin_post_boblift_request_catalog', 'boblift_handle_catalog_request');
add_action('admin_post_nopriv_boblift_request_catalog', 'boblift_handle_catalog_request');

function boblift_handle_inquiry(): void
{
    boblift_switch_to_posted_language();
    if (!isset($_POST['boblift_inquiry_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['boblift_inquiry_nonce'])), 'boblift_submit_inquiry')) {
        if (boblift_inquiry_wants_json()) {
            $ui = function_exists('boblift_site_ui') ? boblift_site_ui('quoteForm') : [];
            wp_send_json_error(['message' => (string) ($ui['messages']['invalidRequest'] ?? 'Invalid inquiry request. Please refresh and try again.')], 403);
        }
        wp_die(esc_html($ui['messages']['invalidRequest'] ?? __('Invalid inquiry request.', 'boblift')));
    }

    $fields = [];
    $field_keys = array_merge(['name', 'email', 'phone', 'company', 'website', 'country', 'product', 'product_slug', 'quantity', 'message'], boblift_inquiry_extra_field_names());
    foreach ($field_keys as $key) {
        $value = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : '';
        $fields[$key] = $key === 'email' ? sanitize_email($value) : sanitize_textarea_field($value);
    }

    // Which button opened the form, and the page it was on (the browser's referer for this POST).
    $fields['source_entry'] = mb_substr(sanitize_text_field(wp_unslash((string) ($_POST['source_entry'] ?? ''))), 0, 120);
    $fields['source_page'] = esc_url_raw((string) wp_get_referer());

    if ($fields['email'] === '' || $fields['message'] === '') {
        $ui = function_exists('boblift_site_ui') ? boblift_site_ui('quoteForm') : [];
        boblift_inquiry_fail((string) ($ui['messages']['requiredFields'] ?? 'Please enter a valid email address and project details.'));
    }
    if (!boblift_verify_turnstile()) {
        $ui = $ui ?? (function_exists('boblift_site_ui') ? boblift_site_ui('quoteForm') : []);
        boblift_inquiry_fail((string) ($ui['messages']['verificationRequired'] ?? 'Please complete the verification and try again.'), 'verification');
    }

    $customer_title = $fields['name'] !== '' ? $fields['name'] : ($fields['company'] !== '' ? $fields['company'] : $fields['email']);
    $post_id = wp_insert_post([
        'post_type' => 'inquiry',
        'post_status' => 'publish',
        'post_title' => sprintf('Inquiry from %s - %s', $customer_title, current_time('Y-m-d H:i')),
    ], true);

    if (is_wp_error($post_id)) {
        $ui = $ui ?? (function_exists('boblift_site_ui') ? boblift_site_ui('quoteForm') : []);
        boblift_inquiry_fail((string) ($ui['messages']['saveFailed'] ?? 'The inquiry could not be sent. Please try again.'), 'error');
    }

    foreach ($fields as $key => $value) {
        update_post_meta((int) $post_id, '_' . $key, $value);
    }

    $admin_email = get_option('admin_email');
    $quote_ui = boblift_site_ui('quoteForm');
    $labels = $quote_ui['inquiryLineLabels'] ?? [];
    $messages = $quote_ui['messages'] ?? [];
    $lines = [boblift_contact_settings()['defaultInquiryText']];
    foreach ($fields as $key => $value) {
        if ($key === 'product_slug') {
            continue;
        }
        $lines[] = ($labels[$key] ?? (['source_entry' => 'Entry point', 'source_page' => 'Page'][$key] ?? ucfirst($key))) . ': ' . ($value !== '' ? $value : '-');
    }
    wp_mail($admin_email, (string) ($messages['mailSubject'] ?? 'New BOB-LIFT inquiry'), implode("\n", $lines), ['Reply-To: ' . $fields['email']]);

    if (boblift_inquiry_wants_json()) {
        wp_send_json_success(['message' => (string) ($quote_ui['successInlineText'] ?? 'Inquiry sent. Our team will contact you soon.'), 'id' => (int) $post_id, 'redirect' => boblift_inquiry_success_url()]);
    }

    wp_safe_redirect(boblift_inquiry_success_url());
    exit;
}

// The standalone quote page was folded into the contact page. Old links, ads and search results
// still resolve: they land on the contact form with the product prefilled.
function boblift_redirect_quote_page(): void
{
    if (is_admin() || !is_page()) {
        return;
    }
    $page = get_queried_object();
    if (!$page instanceof WP_Post || !function_exists('boblift_page_is_translation_of_slug') || !boblift_page_is_translation_of_slug($page, 'quote')) {
        return;
    }
    // A quote page whose copy an editor customised stays reachable (page-quote.php still prints it).
    if (boblift_quote_page_customized()) {
        return;
    }
    // Keep every incoming parameter (utm_*, gclid, ...) so campaign attribution survives the move; only `product` is cleaned.
    $query = wp_unslash($_GET);
    $query = is_array($query) ? $query : [];
    // WordPress routing parameters (/?page_id=<quote page>) would route the contact address back to this page: drop them.
    foreach (['page_id', 'p', 'pagename', 'name', 'preview', 'preview_id'] as $routing_key) {
        unset($query[$routing_key]);
    }
    if (isset($query['product'])) {
        $product = is_string($query['product']) ? sanitize_text_field($query['product']) : '';
        if ($product !== '') {
            $query['product'] = $product;
        } else {
            unset($query['product']);
        }
    }
    $query_string = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    $target = boblift_url('/contact.html' . ($query_string !== '' ? '?' . $query_string : '') . '#inquiry');
    wp_safe_redirect($target, 301);
    exit;
}
add_action('template_redirect', 'boblift_redirect_quote_page', 1);

/** Whether the stored Quote page copy (site_ui.quotePage) differs from the built-in copy, current or historical, in any language form. */
function boblift_quote_page_customized(?string $language = null): bool
{
    $language ??= function_exists('boblift_current_language_slug') ? boblift_current_language_slug() : '';
    $raw = function_exists('boblift_mls_get_raw_options') ? boblift_mls_get_raw_options($language !== '' ? $language : null) : get_option('boblift_theme_options', []);
    $saved = is_array($raw['site_ui']['quotePage'] ?? null) ? $raw['site_ui']['quotePage'] : [];
    if ($saved === [] || !function_exists('boblift_site_ui_defaults')) {
        return false;
    }
    $defaults = boblift_site_ui_defaults()['quotePage'] ?? [];
    // Copy shipped by earlier theme versions, keyed by the same paths as the defaults.
    $historical = ['workflowItems.1.title' => ['Create inquiry draft']];
    $flatten = static function (array $values, string $prefix = '') use (&$flatten): array {
        $flat = [];
        foreach ($values as $key => $value) {
            $path = $prefix . (string) $key;
            $flat += is_array($value) ? $flatten($value, $path . '.') : [$path => is_scalar($value) ? trim((string) $value) : ''];
        }
        return $flat;
    };
    $default_flat = $flatten($defaults);
    foreach ($flatten($saved) as $path => $value) {
        $known = array_merge([(string) ($default_flat[$path] ?? '')], $historical[$path] ?? []);
        foreach ($known as $known_value) {
            $known[] = function_exists('boblift_localized_static_text') ? trim(boblift_localized_static_text($known_value, $language !== '' ? $language : null)) : $known_value;
        }
        if ($value !== '' && !in_array($value, $known, true)) {
            return true;
        }
    }
    return false;
}

function boblift_handle_inquiry_front_controller(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return;
    }
    if (!isset($_GET['boblift_inquiry_submit']) || (string) $_GET['boblift_inquiry_submit'] !== '1') {
        return;
    }
    if (!isset($_POST['action']) || sanitize_key((string) wp_unslash($_POST['action'])) !== 'boblift_submit_inquiry') {
        return;
    }
    boblift_handle_inquiry();
}
add_action('init', 'boblift_handle_inquiry_front_controller', 0);
add_action('admin_post_boblift_submit_inquiry', 'boblift_handle_inquiry');
add_action('admin_post_nopriv_boblift_submit_inquiry', 'boblift_handle_inquiry');
