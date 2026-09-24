<?php
/** Steel header. */
$options = boblift_options();
$branding = is_array($options['branding'] ?? null) ? $options['branding'] : [];
$top_bar = is_array($options['top_bar'] ?? null) ? $options['top_bar'] : [];
$show_top_bar = !array_key_exists('enabled', $top_bar) || (string) ($top_bar['enabled'] ?? '1') === '1';
$header_ui = function_exists('boblift_site_ui') ? boblift_site_ui('header') : [];
$contact = boblift_steel_contact();
$nav = boblift_steel_primary_nav();
$languages = boblift_steel_languages();
$active_language = null;
foreach ($languages as $language) {
    if ($language['current']) {
        $active_language = $language;
        break;
    }
}
$active_language = $active_language ?? ($languages[0] ?? ['label' => 'English', 'code' => 'en', 'flag' => '', 'href' => '#', 'current' => true]);
$language_code = boblift_steel_current_language();
$logo = boblift_steel_image($branding['logo'] ?? 'images/bob-lift-logo.png', 'images/bob-lift-logo.png');
$logo_alt = (string) ($branding['logo_alt'] ?? 'BOB-LIFT');
$home_url = boblift_url('/');
$quote_label = boblift_steel_t((string) ($header_ui['quoteLabel'] ?? 'Request Quote'));
$product_menu = boblift_steel_product_menu();
$product_total = count(boblift_all_products());
$catalog_url = function_exists('boblift_product_archive_url') ? boblift_product_archive_url() : boblift_url('/products');
?><!doctype html>
<html <?php language_attributes(); ?> class="st-html">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#071628">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php if (function_exists('boblift_body_open')) { boblift_body_open(); } ?>
<a class="st-skip" href="#main"><?php echo esc_html(boblift_steel_t('Skip to content')); ?></a>

<?php if ($show_top_bar) : ?>
<div class="st-topbar">
    <div class="st-frame st-topbar__inner">
        <p class="st-topbar__note">
            <?php if (!empty($top_bar['highlight'])) : ?><strong><?php echo esc_html(boblift_steel_t((string) $top_bar['highlight'])); ?></strong><?php endif; ?>
            <?php if (!empty($top_bar['text'])) : ?><span><?php echo esc_html(boblift_steel_t((string) $top_bar['text'])); ?></span><?php endif; ?>
            <?php if (!empty($top_bar['link_label']) && !empty($top_bar['link_href'])) : ?><a href="<?php echo esc_url(boblift_url((string) $top_bar['link_href'])); ?>"><?php echo esc_html(boblift_steel_t((string) $top_bar['link_label'])); ?></a><?php endif; ?>
        </p>
        <div class="st-topbar__tools">
            <?php if ($contact['phone'] !== '') : ?><a class="st-topbar__link" href="<?php echo esc_url($contact['phone_href']); ?>"><?php echo boblift_steel_icon('phone'); ?><span><?php echo esc_html($contact['phone']); ?></span></a><?php endif; ?>
            <a class="st-topbar__link" href="mailto:<?php echo esc_attr($contact['email']); ?>"><?php echo boblift_steel_icon('mail'); ?><span><?php echo esc_html($contact['email']); ?></span></a>
            <?php if (count($languages) > 1) : ?>
                <div class="st-lang" data-lang>
                    <button class="st-lang__toggle" type="button" aria-haspopup="true" aria-expanded="false" aria-label="<?php echo esc_attr(boblift_steel_t((string) ($header_ui['changeLanguageLabel'] ?? 'Change language'))); ?>">
                        <?php echo boblift_steel_icon('globe'); ?><span><?php echo esc_html($active_language['label']); ?></span><?php echo boblift_steel_icon('chevron-down', 'st-lang__chevron'); ?>
                    </button>
                    <ul class="st-lang__menu" role="list">
                        <?php foreach ($languages as $language) : ?>
                            <li><a class="st-lang__option <?php echo $language['current'] ? 'is-current' : ''; ?>" href="<?php echo esc_url($language['href']); ?>" hreflang="<?php echo esc_attr($language['code']); ?>"<?php echo $language['current'] ? ' aria-current="true"' : ''; ?>><span class="st-lang__code"><?php echo esc_html(strtoupper($language['code'])); ?></span><span><?php echo esc_html($language['label']); ?></span></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<header class="st-header" data-header>
    <div class="st-frame st-header__bar">
        <a class="st-brand" href="<?php echo esc_url($home_url); ?>" aria-label="<?php echo esc_attr(boblift_steel_t((string) ($header_ui['homeAriaLabel'] ?? 'BOB-LIFT Home'))); ?>">
            <img src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr($logo_alt); ?>" width="<?php echo esc_attr((string) ($branding['logo_width'] ?? '188')); ?>" height="<?php echo esc_attr((string) ($branding['logo_height'] ?? '91')); ?>" decoding="async">
        </a>

        <nav class="st-nav" aria-label="<?php echo esc_attr(boblift_steel_t((string) ($header_ui['mainNavigationLabel'] ?? 'Main navigation'))); ?>">
            <ul class="st-nav__list" role="list">
                <?php foreach ($nav as $item) : ?>
                    <li class="st-nav__item st-nav__item--<?php echo esc_attr($item['type']); ?> <?php echo $item['active'] ? 'is-active' : ''; ?>" data-nav-item>
                        <a class="st-nav__link" href="<?php echo esc_url($item['href']); ?>"<?php echo $item['type'] !== 'link' ? ' aria-haspopup="true" aria-expanded="false"' : ''; ?><?php echo $item['active'] ? ' aria-current="page"' : ''; ?>>
                            <span><?php echo esc_html(boblift_steel_t($item['label'])); ?></span>
                            <?php if ($item['type'] !== 'link') { echo boblift_steel_icon('chevron-down', 'st-nav__chevron'); } ?>
                        </a>

                        <?php if ($item['type'] === 'children' && $item['children'] !== []) : ?>
                            <div class="st-dropdown" data-nav-panel>
                                <ul role="list">
                                    <?php foreach ($item['children'] as $child) : ?>
                                        <li><a href="<?php echo esc_url($child['href']); ?>"><?php echo esc_html($child['label']); ?><?php echo boblift_steel_icon('arrow-right'); ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php elseif ($item['type'] === 'mega') : ?>
                            <div class="st-mega" data-nav-panel>
                                <div class="st-frame st-mega__inner">
                                    <div class="st-mega__groups">
                                        <?php foreach ($product_menu as $index => $group) : ?>
                                            <div class="st-mega__group">
                                                <a class="st-mega__title" href="<?php echo esc_url($group['href']); ?>">
                                                    <span class="st-mega__index"><?php echo esc_html(boblift_steel_index($index + 1)); ?></span>
                                                    <span class="st-mega__name"><?php echo esc_html($group['title']); ?></span>
                                                    <span class="st-mega__count"><?php echo esc_html((string) $group['count']); ?></span>
                                                </a>
                                                <?php if ($group['children'] !== []) : ?>
                                                    <ul class="st-mega__links" role="list">
                                                        <?php foreach ($group['children'] as $child) : ?>
                                                            <li><a href="<?php echo esc_url($child['href']); ?>"><span><?php echo esc_html($child['title']); ?></span><span class="st-mega__count"><?php echo esc_html((string) $child['count']); ?></span></a></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <aside class="st-mega__aside">
                                        <p class="st-eyebrow"><?php echo esc_html(boblift_steel_t((string) ($header_ui['megaTitle'] ?? 'Explore all products'))); ?></p>
                                        <p class="st-mega__figure"><?php echo esc_html((string) $product_total); ?><small><?php echo esc_html(boblift_steel_t('models')); ?></small></p>
                                        <p class="st-mega__text"><?php echo esc_html(boblift_steel_t((string) ($header_ui['megaDescription'] ?? 'Discover our full range of lifting solutions.'))); ?></p>
                                        <?php echo boblift_steel_button(boblift_steel_t((string) ($header_ui['megaButtonLabel'] ?? 'View product catalog')), $catalog_url, 'ghost'); ?>
                                    </aside>
                                </div>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div class="st-header__tools">
            <?php echo boblift_steel_button($quote_label, $contact['quote_url'], 'primary', 'arrow-right', ['class' => 'st-btn st-btn--primary st-header__cta']); ?>
            <button class="st-burger" type="button" data-menu-open aria-expanded="false" aria-controls="st-menu" aria-label="<?php echo esc_attr(boblift_steel_t((string) ($header_ui['mobileOpenLabel'] ?? 'Open navigation'))); ?>">
                <span></span><span></span>
            </button>
        </div>
    </div>
</header>

<div class="st-menu" id="st-menu" data-menu aria-hidden="true">
    <div class="st-menu__bar st-frame">
        <span class="st-menu__title"><?php echo esc_html(boblift_steel_t((string) ($header_ui['mobileTitle'] ?? 'Menu'))); ?></span>
        <button class="st-menu__close" type="button" data-menu-close aria-label="<?php echo esc_attr(boblift_steel_t('Close navigation')); ?>"><?php echo boblift_steel_icon('close'); ?></button>
    </div>
    <div class="st-frame st-menu__body">
        <nav class="st-menu__nav" aria-label="<?php echo esc_attr(boblift_steel_t((string) ($header_ui['mobileLabel'] ?? 'Navigation'))); ?>">
            <ol class="st-menu__list" role="list">
                <?php foreach ($nav as $index => $item) : ?>
                    <li class="st-menu__item" style="--i:<?php echo esc_attr((string) $index); ?>">
                        <?php if ($item['type'] === 'mega' && $product_menu !== []) : ?>
                            <details class="st-menu__details">
                                <summary><span class="st-menu__num"><?php echo esc_html(boblift_steel_index($index + 1)); ?></span><span><?php echo esc_html(boblift_steel_t($item['label'])); ?></span><?php echo boblift_steel_icon('plus', 'st-menu__plus'); ?></summary>
                                <ul class="st-menu__sub" role="list">
                                    <li><a href="<?php echo esc_url($item['href']); ?>"><?php echo esc_html(boblift_steel_t('All products')); ?><span class="st-mega__count"><?php echo esc_html((string) $product_total); ?></span></a></li>
                                    <?php foreach ($product_menu as $group) : ?>
                                        <li><a href="<?php echo esc_url($group['href']); ?>"><?php echo esc_html($group['title']); ?><span class="st-mega__count"><?php echo esc_html((string) $group['count']); ?></span></a></li>
                                        <?php foreach ($group['children'] as $child) : ?>
                                            <li class="is-child"><a href="<?php echo esc_url($child['href']); ?>"><?php echo esc_html($child['title']); ?><span class="st-mega__count"><?php echo esc_html((string) $child['count']); ?></span></a></li>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </ul>
                            </details>
                        <?php elseif ($item['type'] === 'children' && $item['children'] !== []) : ?>
                            <details class="st-menu__details">
                                <summary><span class="st-menu__num"><?php echo esc_html(boblift_steel_index($index + 1)); ?></span><span><?php echo esc_html(boblift_steel_t($item['label'])); ?></span><?php echo boblift_steel_icon('plus', 'st-menu__plus'); ?></summary>
                                <ul class="st-menu__sub" role="list">
                                    <?php foreach ($item['children'] as $child) : ?>
                                        <li><a href="<?php echo esc_url($child['href']); ?>"><?php echo esc_html($child['label']); ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </details>
                        <?php else : ?>
                            <a class="st-menu__link" href="<?php echo esc_url($item['href']); ?>"><span class="st-menu__num"><?php echo esc_html(boblift_steel_index($index + 1)); ?></span><span><?php echo esc_html(boblift_steel_t($item['label'])); ?></span></a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
                <li class="st-menu__item" style="--i:<?php echo esc_attr((string) count($nav)); ?>">
                    <a class="st-menu__link" href="<?php echo esc_url($contact['contact_url']); ?>"><span class="st-menu__num"><?php echo esc_html(boblift_steel_index(count($nav) + 1)); ?></span><span><?php echo esc_html(boblift_steel_t('Contact Us')); ?></span></a>
                </li>
            </ol>
        </nav>
        <div class="st-menu__foot">
            <?php echo boblift_steel_button($quote_label, $contact['quote_url'], 'light'); ?>
            <div class="st-menu__contact">
                <a href="mailto:<?php echo esc_attr($contact['email']); ?>"><?php echo esc_html($contact['email']); ?></a>
                <?php if ($contact['phone'] !== '') : ?><a href="<?php echo esc_url($contact['phone_href']); ?>"><?php echo esc_html($contact['phone']); ?></a><?php endif; ?>
            </div>
            <?php if (count($languages) > 1) : ?>
                <ul class="st-menu__langs" role="list">
                    <?php foreach ($languages as $language) : ?>
                        <li><a class="<?php echo $language['current'] ? 'is-current' : ''; ?>" href="<?php echo esc_url($language['href']); ?>" hreflang="<?php echo esc_attr($language['code']); ?>"><?php echo esc_html($language['label']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<main id="main" class="st-main">
