<?php
/**
 * Template Name: Contact
 */
get_header();
$ui = boblift_site_ui('contactPage');
$contact = boblift_contact_settings();
$support_items = is_array($ui['supportItems'] ?? null) ? $ui['supportItems'] : [];
$map_query = rawurlencode((string) ($contact['address'] ?? 'Xuzhou, China'));
$map_embed_url = 'https://www.google.com/maps?q=' . $map_query . '&output=embed';
$map_link_url = 'https://www.google.com/maps/search/?api=1&query=' . $map_query;
?>
<div class="page-shell pt-5 md:pt-8">
    <?php
    boblift_page_hero([
        'label' => $ui['heroLabel'] ?? 'Contact Us',
        'title' => $ui['heroTitle'] ?? '',
        'text' => $ui['heroDescription'] ?? '',
        'image' => (string) ($ui['heroImage'] ?? ''),
        'image_alt' => (string) ($ui['heroImageAlt'] ?? 'The BOB-LIFT sales and service team'),
        'image_position' => 'right 20%',
        'links' => [
            ['label' => $contact['email'], 'href' => 'mailto:' . $contact['email'], 'icon' => 'mail'],
            ['label' => $contact['whatsapp'], 'href' => $contact['whatsappHref'], 'icon' => 'message-circle', 'external' => true],
        ],
    ]);
    ?>
</div>

<section class="page-shell space-y-6 py-10 md:py-16">
    <?php boblift_render_inquiry_form(isset($_GET['product']) ? sanitize_text_field(wp_unslash($_GET['product'])) : '', ['compact' => true, 'source' => '联系页表单']); ?>

    <article class="overflow-hidden rounded border border-blue-100 bg-[linear-gradient(135deg,#ffffff_0%,#f2f7ff_100%)] p-5 shadow-card md:p-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div class="min-w-0">
                <p class="text-[11px] font-black uppercase tracking-[0.2em] text-blue-700"><?php echo esc_html($ui['supportTitle'] ?? 'Global Support'); ?></p>
                <p class="mt-2 max-w-3xl text-sm font-semibold leading-7 text-slate-600"><?php echo esc_html($ui['supportDescription'] ?? 'Use the form to share your project details. Our team will help turn it into a practical crane selection and export plan.'); ?></p>
            </div>
        </div>
        <div class="mt-5 grid gap-3 md:grid-cols-3">
            <?php foreach ($support_items as $item) : ?>
                <div class="flex min-w-0 gap-3 rounded border border-blue-100 bg-white/82 p-4">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-sm bg-blue-50 text-blue-700"><?php echo boblift_icon((string) ($item['iconName'] ?? 'check-circle'), 'h-5 w-5'); ?></span>
                    <span class="min-w-0">
                        <span class="block text-sm font-black leading-5 text-navy"><?php echo esc_html((string) ($item['title'] ?? 'Support')); ?></span>
                        <span class="mt-1 block text-xs font-semibold leading-5 text-slate-500"><?php echo esc_html((string) ($item['text'] ?? '')); ?></span>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </article>

    <article id="map" class="overflow-hidden rounded border border-line bg-white p-5 shadow-card md:p-6">
        <div class="flex items-start gap-4">
            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-sm bg-navy text-white"><?php echo boblift_icon('map-pin', 'h-5 w-5'); ?></span>
            <div class="min-w-0 flex-1">
                <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-400"><?php echo esc_html($ui['locationTitle'] ?? 'Factory contact point'); ?></p>
                <p class="mt-1 text-lg font-black leading-6 text-ink"><?php echo esc_html($contact['address']); ?></p>
                <p class="mt-3 text-sm font-semibold leading-7 text-slate-600"><?php echo esc_html(str_replace('{address}', (string) $contact['address'], (string) ($ui['locationDescriptionTemplate'] ?? 'BOB-LIFT is based in {address}. For quick China-side communication, use WeChat or submit the structured inquiry form.'))); ?></p>
            </div>
        </div>
        <div class="relative mt-6 overflow-hidden rounded border border-blue-100 bg-[#eef5ff]">
            <iframe class="w-full bg-[#eef5ff]" style="height:min(430px,62vw);min-height:300px" src="about:blank" data-lazy-iframe-src="<?php echo esc_url($map_embed_url); ?>" title="<?php echo esc_attr((string) ($ui['mapTitle'] ?? 'BOB-LIFT factory location map')); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            <a class="absolute bottom-4 left-4 inline-flex h-11 items-center justify-center gap-2 rounded-sm bg-white/95 px-4 text-xs font-black text-navy shadow-card backdrop-blur transition hover:-translate-y-0.5 hover:text-blue-700" href="<?php echo esc_url($map_link_url); ?>" target="_blank" rel="noreferrer"><?php echo boblift_icon('map-pin', 'h-4 w-4'); ?> <?php echo esc_html((string) ($ui['viewMapLabel'] ?? 'View on map')); ?></a>
        </div>
    </article>
</section>
<?php get_footer(); ?>
