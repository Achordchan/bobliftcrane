<?php
/** Steel footer. */
$options = boblift_options();
$footer = is_array($options['footer'] ?? null) ? $options['footer'] : [];
$cta = is_array($options['final_cta'] ?? null) ? $options['final_cta'] : [];
$contact = boblift_steel_contact();
$languages = boblift_steel_languages();
$dock = boblift_steel_dock();
$is_utility_page = is_page(['quote', 'contact', 'success', 'privacy-policy', 'terms-of-use', 'sitemap'])
    || is_page_template(['page-quote.php', 'page-contact.php', 'page-success.php', 'page-privacy-policy.php', 'page-terms-of-use.php', 'page-sitemap.php']);
$show_cta = !$is_utility_page && !is_singular('product') && !is_post_type_archive('product') && !is_tax('product_category') && (string) get_query_var('boblift_product_category') === '';
$cta_image = boblift_steel_image($cta['image'] ?? 'images/cta-lift-photo.png', 'images/cta-lift-photo.png');
$year = (string) wp_date('Y');
$copyright = (string) ($footer['copyright'] ?? ('© ' . $year . ' BOB-LIFT. All Rights Reserved.'));
$legacy = !boblift_steel_is_redesigned();
?>
</main>

<?php if ($show_cta) : ?>
<section class="st-cta" aria-labelledby="st-cta-title">
    <div class="st-cta__media">
        <img src="<?php echo esc_url($cta_image); ?>" alt="" loading="lazy" decoding="async">
    </div>
    <div class="st-frame st-cta__inner">
        <div class="st-cta__copy st-reveal">
            <p class="st-eyebrow st-eyebrow--light"><?php echo esc_html(boblift_steel_t((string) ($cta['label'] ?? 'Project consultation'))); ?></p>
            <h2 class="st-h1 st-h1--light" id="st-cta-title"><?php echo esc_html(boblift_steel_t((string) ($cta['title'] ?? 'Need a lifting solution?'))); ?></h2>
            <div class="st-cta__actions">
                <?php echo boblift_steel_button(boblift_steel_t((string) ($cta['button_label'] ?? 'Request a quote')), boblift_url((string) ($cta['button_href'] ?? '/quote')), 'light'); ?>
                <a class="st-textlink st-textlink--light" href="mailto:<?php echo esc_attr($contact['email']); ?>"><span><?php echo esc_html($contact['email']); ?></span><?php echo boblift_steel_icon('arrow-up-right'); ?></a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<footer class="st-footer">
    <div class="st-frame">
        <div class="st-footer__top">
            <div class="st-footer__brand">
                <p class="st-wordmark">BOB-LIFT</p>
                <?php if (!empty($footer['intro'])) : ?><p class="st-footer__intro"><?php echo esc_html(boblift_steel_t(trim(preg_replace('/\s+en$/', '', (string) $footer['intro'])))); ?></p><?php endif; ?>
            </div>
            <dl class="st-footer__contact">
                <div><dt><?php echo esc_html(boblift_steel_t('Email')); ?></dt><dd><a href="mailto:<?php echo esc_attr($contact['email']); ?>"><?php echo esc_html($contact['email']); ?></a></dd></div>
                <?php if ($contact['phone'] !== '') : ?><div><dt><?php echo esc_html(boblift_steel_t('Phone / WhatsApp')); ?></dt><dd><a href="<?php echo esc_url($contact['phone_href']); ?>"><?php echo esc_html($contact['phone']); ?></a></dd></div><?php endif; ?>
                <?php if ($contact['address'] !== '') : ?><div><dt><?php echo esc_html(boblift_steel_t('Factory')); ?></dt><dd><?php echo esc_html($contact['address']); ?></dd></div><?php endif; ?>
            </dl>
        </div>

        <div class="st-footer__grid">
            <?php foreach (($footer['groups'] ?? []) as $group) : if (!is_array($group)) { continue; } ?>
                <div class="st-footer__col">
                    <p class="st-footer__heading"><?php echo esc_html(boblift_steel_t((string) ($group['title'] ?? ''))); ?></p>
                    <ul role="list">
                        <?php foreach (($group['links'] ?? []) as $link) : if (!is_array($link)) { continue; } ?>
                            <li><a href="<?php echo esc_url(function_exists('boblift_product_nav_url') ? boblift_product_nav_url((string) ($link['href'] ?? '/'), (string) ($link['label'] ?? '')) : boblift_url((string) ($link['href'] ?? '/'))); ?>"><?php echo esc_html(boblift_steel_t((string) ($link['label'] ?? ''))); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
            <?php if (!empty($footer['social_links'])) : ?>
                <div class="st-footer__col">
                    <p class="st-footer__heading"><?php echo esc_html(boblift_steel_t('Follow')); ?></p>
                    <ul role="list">
                        <?php foreach ($footer['social_links'] as $link) : if (!is_array($link)) { continue; } ?>
                            <li><a href="<?php echo esc_url(boblift_url((string) ($link['href'] ?? '/'))); ?>"><?php echo esc_html((string) ($link['label'] ?? '')); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <?php if (count($languages) > 1) : ?>
                <div class="st-footer__col">
                    <p class="st-footer__heading"><?php echo esc_html(boblift_steel_t('Languages')); ?></p>
                    <ul role="list">
                        <?php foreach ($languages as $language) : ?>
                            <li><a class="<?php echo $language['current'] ? 'is-current' : ''; ?>" href="<?php echo esc_url($language['href']); ?>" hreflang="<?php echo esc_attr($language['code']); ?>"><?php echo esc_html($language['label']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <div class="st-footer__legal">
            <p><?php echo esc_html(str_replace('2026', $year, $copyright)); ?></p>
            <ul role="list">
                <?php foreach (($footer['legal_links'] ?? []) as $link) : if (!is_array($link)) { continue; } ?>
                    <li><a href="<?php echo esc_url(boblift_url((string) ($link['href'] ?? '/'))); ?>"><?php echo esc_html(boblift_steel_t((string) ($link['label'] ?? ''))); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</footer>

<?php if ($dock !== []) : ?>
<nav class="st-dock" aria-label="<?php echo esc_attr(boblift_steel_t('Quick contact')); ?>">
    <?php foreach ($dock as $entry) : ?>
        <?php if ($entry['type'] === 'wechat') : ?>
            <button class="st-dock__item" type="button" data-wechat-open aria-label="<?php echo esc_attr($entry['label']); ?>"><?php echo boblift_steel_icon($entry['icon']); ?><span class="st-dock__label"><?php echo esc_html($entry['label']); ?></span></button>
        <?php else : ?>
            <a class="st-dock__item" href="<?php echo esc_url($entry['href']); ?>"<?php echo $entry['target'] !== '' ? ' target="' . esc_attr($entry['target']) . '" rel="noreferrer"' : ''; ?> aria-label="<?php echo esc_attr($entry['label']); ?>"><?php echo boblift_steel_icon($entry['icon']); ?><span class="st-dock__label"><?php echo esc_html($entry['label']); ?></span></a>
        <?php endif; ?>
    <?php endforeach; ?>
    <button class="st-dock__item st-dock__item--top" type="button" data-scroll-top aria-label="<?php echo esc_attr(boblift_steel_t('Back to top')); ?>"><?php echo boblift_steel_icon('arrow-down', 'st-icon--flip'); ?></button>
</nav>
<dialog class="st-dialog" data-wechat-dialog>
    <div class="st-dialog__inner">
        <button class="st-dialog__close" type="button" data-wechat-close aria-label="<?php echo esc_attr(boblift_steel_t('Close')); ?>"><?php echo boblift_steel_icon('close'); ?></button>
        <p class="st-eyebrow">WeChat</p>
        <img src="<?php echo esc_url($contact['wechat_qr']); ?>" alt="BOB-LIFT WeChat QR code" width="240" height="240" loading="lazy">
        <?php if ($contact['wechat_id'] !== '') : ?><p class="st-dialog__id"><?php echo esc_html($contact['wechat_id']); ?></p><?php endif; ?>
    </div>
</dialog>
<?php endif; ?>

<?php
if ($legacy) {
    if (function_exists('boblift_render_inquiry_modal')) {
        boblift_render_inquiry_modal();
    }
    if (function_exists('boblift_render_catalog_modal')) {
        boblift_render_catalog_modal();
    }
}
wp_footer();
?>
</body>
</html>
