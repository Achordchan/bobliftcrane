<?php
/**
 * Reusable markup components: section heads, product card, inquiry form.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Section head with a mono index, eyebrow, headline and optional trailing link.
 */
function boblift_steel_section_head(array $args): void
{
    $index = (string) ($args['index'] ?? '');
    $eyebrow = (string) ($args['eyebrow'] ?? '');
    $title = (string) ($args['title'] ?? '');
    $text = (string) ($args['text'] ?? '');
    $link_label = (string) ($args['link_label'] ?? '');
    $link_href = (string) ($args['link_href'] ?? '');
    $tone = (string) ($args['tone'] ?? '');
    $level = (string) ($args['level'] ?? 'h2');
    ?>
    <div class="st-section__head <?php echo esc_attr($tone); ?> st-reveal">
        <div class="st-section__lead">
            <?php if ($eyebrow !== '' || $index !== '') : ?>
                <p class="st-eyebrow"><?php if ($index !== '') : ?><span class="st-eyebrow__index"><?php echo esc_html($index); ?></span><?php endif; ?><?php echo esc_html($eyebrow); ?></p>
            <?php endif; ?>
            <<?php echo esc_attr($level); ?> class="st-h2" data-split><?php echo esc_html($title); ?></<?php echo esc_attr($level); ?>>
        </div>
        <?php if ($text !== '' || $link_label !== '') : ?>
            <div class="st-section__aside">
                <?php if ($text !== '') : ?><p class="st-lead"><?php echo esc_html($text); ?></p><?php endif; ?>
                <?php if ($link_label !== '' && $link_href !== '') : ?>
                    <a class="st-textlink" href="<?php echo esc_url($link_href); ?>"><span><?php echo esc_html($link_label); ?></span><?php echo boblift_steel_icon('arrow-right'); ?></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Button with a nested trailing icon.
 */
function boblift_steel_button(string $label, string $href, string $variant = 'primary', string $icon = 'arrow-right', array $attrs = []): string
{
    $classes = 'st-btn st-btn--' . $variant;
    if (!empty($attrs['class'])) {
        $classes .= ' ' . trim(str_replace(['st-btn st-btn--primary', 'st-btn'], '', (string) $attrs['class']));
        unset($attrs['class']);
    }
    $attributes = '';
    foreach ($attrs as $key => $value) {
        $attributes .= ' ' . esc_attr((string) $key) . '="' . esc_attr((string) $value) . '"';
    }
    $icon_html = $icon !== '' ? '<span class="st-btn__icon">' . boblift_steel_icon($icon) . '</span>' : '';
    return '<a class="' . esc_attr(trim(preg_replace('/\s+/', ' ', $classes))) . '" href="' . esc_url($href) . '"' . $attributes . '><span class="st-btn__label">' . esc_html($label) . '</span>' . $icon_html . '</a>';
}

/**
 * Product card used on the home programme, archive and related lists.
 */
function boblift_steel_product_card(array $product, array $args = []): void
{
    $ui = function_exists('boblift_site_ui') ? boblift_site_ui('productDetail') : [];
    $eager = !empty($args['eager']);
    $detail_url = function_exists('boblift_product_url') ? boblift_product_url($product) : (string) ($product['detail_url'] ?? '#');
    $image = function_exists('boblift_product_image') ? boblift_product_image($product) : (string) ($product['image'] ?? '');
    $heading = boblift_steel_product_heading($product);
    $name = boblift_steel_product_name($product);
    $model = trim((string) ($product['model'] ?? ''));
    $has_model = $model !== '' && strcasecmp($model, (string) ($product['title'] ?? '')) !== 0;
    $category_title = (string) ($product['category_title'] ?? 'BOB-LIFT');
    $rows = boblift_steel_card_rows($product);
    $status = function_exists('boblift_normalize_product_status') ? boblift_normalize_product_status((string) ($product['status'] ?? 'customizable')) : 'customizable';
    $status_label = function_exists('boblift_product_status_label') ? boblift_product_status_label($status) : ucfirst($status);
    $inquiry_url = (string) ($product['inquiry_url'] ?? boblift_url('/quote'));
    $view_label = boblift_steel_t((string) ($ui['viewDetailsLabel'] ?? 'View details'));
    $quote_label = boblift_steel_t((string) ($ui['requestQuoteLabel'] ?? 'Request quote'));
    $created = strtotime((string) ($product['created_at'] ?? '')) ?: 0;
    $classes = 'st-card' . (!empty($args['class']) ? ' ' . (string) $args['class'] : '');
    ?>
    <article class="<?php echo esc_attr($classes); ?>"
        data-product-card
        data-id="<?php echo esc_attr((string) ($product['id'] ?? $product['slug'] ?? '')); ?>"
        data-title="<?php echo esc_attr((string) ($product['title'] ?? '')); ?>"
        data-categories="<?php echo esc_attr(implode(',', boblift_steel_product_category_slugs($product))); ?>"
        data-capacity="<?php echo esc_attr((string) (float) ($product['capacity_value'] ?? 0)); ?>"
        data-boom="<?php echo esc_attr((string) ($product['boom_type'] ?? '')); ?>"
        data-applications="<?php echo esc_attr(implode(',', array_map('strval', $product['applications'] ?? []))); ?>"
        data-status="<?php echo esc_attr($status); ?>"
        data-popularity="<?php echo esc_attr((string) (int) ($product['popularity'] ?? 50)); ?>"
        data-featured="<?php echo !empty($product['featured']) ? '1' : '0'; ?>"
        data-created="<?php echo esc_attr((string) $created); ?>">
        <div class="st-card__head">
            <span class="st-card__index">/<?php echo esc_html(boblift_steel_index((int) ($args['index'] ?? 0))); ?></span>
            <span class="st-tag st-tag--<?php echo esc_attr($status); ?>"><?php echo esc_html($status_label); ?></span>
        </div>
        <div class="st-card__body">
            <p class="st-card__category"><?php echo esc_html($category_title); ?></p>
            <h3 class="st-card__title <?php echo $has_model ? 'st-card__title--model' : ''; ?>"><a href="<?php echo esc_url($detail_url); ?>"><?php echo esc_html($heading); ?></a></h3>
            <?php if ($has_model && $name !== '' && strcasecmp($name, $heading) !== 0) : ?>
                <p class="st-card__name"><?php echo esc_html($name); ?></p>
            <?php endif; ?>
        </div>
        <a class="st-card__media" href="<?php echo esc_url($detail_url); ?>" aria-label="<?php echo esc_attr($view_label . ': ' . (string) ($product['title'] ?? '')); ?>">
            <?php if ($image !== '') : ?>
                <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr((string) ($product['title'] ?? '')); ?>" loading="<?php echo $eager ? 'eager' : 'lazy'; ?>" decoding="async">
            <?php endif; ?>
        </a>
        <?php if ($rows !== []) : ?>
            <dl class="st-rows st-card__rows">
                <?php foreach ($rows as [$label, $value]) : ?>
                    <div class="st-rows__row"><dt><?php echo esc_html($label); ?></dt><dd><?php echo esc_html($value); ?></dd></div>
                <?php endforeach; ?>
            </dl>
        <?php endif; ?>
        <div class="st-card__actions">
            <a href="<?php echo esc_url($detail_url); ?>"><span><?php echo esc_html($view_label); ?></span><?php echo boblift_steel_icon('arrow-right'); ?></a>
            <a class="st-card__quote" href="<?php echo esc_url($inquiry_url); ?>"><span><?php echo esc_html($quote_label); ?></span><?php echo boblift_steel_icon('arrow-up-right'); ?></a>
        </div>
    </article>
    <?php
}

/**
 * Inquiry form posting to the parent theme's handler.
 */
function boblift_steel_inquiry_form(array $args = []): void
{
    $ui = function_exists('boblift_site_ui') ? boblift_site_ui('quoteForm') : [];
    $fields = is_array($ui['fields'] ?? null) ? $ui['fields'] : [];
    $buttons = is_array($ui['buttons'] ?? null) ? $ui['buttons'] : [];
    $messages = is_array($ui['messages'] ?? null) ? $ui['messages'] : [];
    $contact = boblift_steel_contact();
    $product = (string) ($args['product'] ?? '');
    $product_slug = (string) ($args['product_slug'] ?? '');
    $options = function_exists('boblift_product_interest_options') ? boblift_product_interest_options() : (function_exists('boblift_product_options') ? boblift_product_options() : []);
    $has_option = $product === '';
    foreach ($options as $option) {
        if ((string) ($option['value'] ?? '') === $product) {
            $has_option = true;
            break;
        }
    }
    $state = isset($_GET['inquiry']) ? sanitize_key((string) wp_unslash($_GET['inquiry'])) : '';
    $action = function_exists('boblift_inquiry_action_url') ? boblift_inquiry_action_url() : admin_url('admin-post.php');
    $id = (string) ($args['id'] ?? 'inquiry');
    ?>
    <form class="st-form" id="<?php echo esc_attr($id); ?>" method="post" action="<?php echo esc_url($action); ?>" novalidate>
        <input type="hidden" name="action" value="boblift_submit_inquiry">
        <?php wp_nonce_field('boblift_submit_inquiry', 'boblift_inquiry_nonce'); ?>
        <input type="hidden" name="product_slug" value="<?php echo esc_attr($product_slug); ?>">

        <?php if ($state === 'success') : ?>
            <p class="st-form__notice st-form__notice--ok"><strong><?php echo esc_html(boblift_steel_t((string) ($messages['successTitle'] ?? 'Inquiry received.'))); ?></strong> <?php echo esc_html(boblift_steel_t((string) ($messages['successText'] ?? ''))); ?></p>
        <?php elseif ($state === 'verification') : ?>
            <p class="st-form__notice st-form__notice--error"><?php echo esc_html(boblift_steel_t((string) ($messages['verificationRequired'] ?? 'Please complete the verification and try again.'))); ?></p>
        <?php elseif ($state === 'missing' || $state === 'error') : ?>
            <p class="st-form__notice st-form__notice--error"><?php echo esc_html(boblift_steel_t((string) ($messages['requiredFields'] ?? 'Please enter a valid email address and project details.'))); ?></p>
        <?php endif; ?>

        <div class="st-form__grid">
            <label class="st-field">
                <span class="st-field__label"><?php echo esc_html(boblift_steel_t((string) ($fields['name'] ?? 'Full name'))); ?></span>
                <input class="st-field__input" type="text" name="name" autocomplete="name">
            </label>
            <label class="st-field">
                <span class="st-field__label"><?php echo esc_html(boblift_steel_t((string) ($fields['email'] ?? 'Email'))); ?> <em>*</em></span>
                <input class="st-field__input" type="email" name="email" autocomplete="email" required>
            </label>
            <label class="st-field">
                <span class="st-field__label"><?php echo esc_html(boblift_steel_t((string) ($fields['phone'] ?? 'Phone / WhatsApp'))); ?></span>
                <input class="st-field__input" type="tel" name="phone" autocomplete="tel">
            </label>
            <label class="st-field">
                <span class="st-field__label"><?php echo esc_html(boblift_steel_t((string) ($fields['country'] ?? 'Country / Region'))); ?></span>
                <input class="st-field__input" type="text" name="country" autocomplete="country-name">
            </label>
            <label class="st-field">
                <span class="st-field__label"><?php echo esc_html(boblift_steel_t((string) ($fields['product'] ?? 'Product interest'))); ?></span>
                <span class="st-field__select">
                    <select class="st-field__input" name="product">
                        <option value=""<?php selected($product, ''); ?>><?php echo esc_html(boblift_steel_t((string) ($fields['productNotDecided'] ?? 'Not decided yet'))); ?></option>
                        <?php if (!$has_option) : ?><option value="<?php echo esc_attr($product); ?>" selected><?php echo esc_html($product); ?></option><?php endif; ?>
                        <?php foreach ($options as $option) : ?>
                            <option value="<?php echo esc_attr((string) ($option['value'] ?? '')); ?>"<?php selected($product, (string) ($option['value'] ?? '')); ?>><?php echo esc_html((string) ($option['label'] ?? '')); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php echo boblift_steel_icon('chevron-down'); ?>
                </span>
            </label>
            <label class="st-field">
                <span class="st-field__label"><?php echo esc_html(boblift_steel_t((string) ($fields['quantity'] ?? 'Expected quantity'))); ?></span>
                <input class="st-field__input" type="text" name="quantity" inputmode="numeric">
            </label>
            <label class="st-field st-field--wide">
                <span class="st-field__label"><?php echo esc_html(boblift_steel_t((string) ($fields['message'] ?? 'Project details'))); ?> <em>*</em></span>
                <textarea class="st-field__input st-field__textarea" name="message" rows="5" placeholder="<?php echo esc_attr(boblift_steel_t((string) ($fields['messagePlaceholder'] ?? ''))); ?>" required></textarea>
            </label>
        </div>

        <?php if (function_exists('boblift_render_turnstile_widget')) { boblift_render_turnstile_widget('st-form__turnstile'); } ?>

        <div class="st-form__actions">
            <button class="st-btn st-btn--primary" type="submit"><span class="st-btn__label"><?php echo esc_html(boblift_steel_t((string) ($args['submit_label'] ?? ($ui['submitLabel'] ?? 'Send inquiry')))); ?></span><span class="st-btn__icon"><?php echo boblift_steel_icon('arrow-right'); ?></span></button>
            <a class="st-textlink" href="<?php echo esc_url($contact['whatsapp_href']); ?>" target="_blank" rel="noreferrer"><span><?php echo esc_html(boblift_steel_t((string) ($buttons['whatsapp'] ?? 'WhatsApp'))); ?></span><?php echo boblift_steel_icon('arrow-up-right'); ?></a>
            <a class="st-textlink" href="mailto:<?php echo esc_attr($contact['email']); ?>"><span><?php echo esc_html($contact['email']); ?></span><?php echo boblift_steel_icon('arrow-up-right'); ?></a>
        </div>
        <?php if (!empty($ui['contactTimeNote'])) : ?><p class="st-form__note"><?php echo esc_html(boblift_steel_t((string) $ui['contactTimeNote'])); ?></p><?php endif; ?>
    </form>
    <?php
}
