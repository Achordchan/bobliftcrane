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
<section class="boblift-news-hero boblift-contact-hero">
    <div class="page-shell boblift-news-hero-shell grid items-center gap-7 py-8 md:gap-10 md:py-16 lg:grid-cols-[0.92fr_1.08fr] lg:gap-16 2xl:py-20">
        <div class="max-w-3xl">
            <p class="mb-4 text-xs font-extrabold uppercase tracking-[0.2em] text-blue-700 md:mb-5 lg:text-sm"><?php echo esc_html($ui['heroLabel']); ?></p>
            <h1 class="text-[clamp(32px,8.8vw,38px)] font-black leading-[1.04] tracking-[-0.015em] text-navy md:text-[50px] xl:text-[56px] 2xl:text-[64px]"><?php echo esc_html($ui['heroTitle']); ?></h1>
            <p class="mt-4 max-w-2xl text-base font-semibold leading-7 text-slate-600 md:mt-6 md:text-lg md:leading-8 2xl:text-xl 2xl:leading-9"><?php echo esc_html($ui['heroDescription']); ?></p>
            <div class="mt-6 grid grid-cols-2 gap-3 md:mt-8 md:flex md:flex-wrap md:gap-4">
                <a class="boblift-contact-proof-chip" href="mailto:<?php echo esc_attr($contact['email']); ?>"><?php echo boblift_icon('mail', 'h-6 w-6 text-blue-700 md:h-7 md:w-7', '1.8'); ?><span><strong><?php echo esc_html($ui['cardLabels']['email']); ?></strong><small><?php echo esc_html($contact['email']); ?></small></span></a>
                <a class="boblift-contact-proof-chip" href="<?php echo esc_url($contact['whatsappHref']); ?>" target="_blank" rel="noreferrer"><?php echo boblift_icon('message-circle', 'h-6 w-6 text-blue-700 md:h-7 md:w-7', '1.8'); ?><span><strong><?php echo esc_html($ui['cardLabels']['whatsapp']); ?></strong><small><?php echo esc_html($ui['whatsappValue']); ?></small></span></a>
            </div>
        </div>
        <div class="boblift-news-hero-image">
            <?php if (!empty($ui['heroImage'])) : ?><img class="h-full w-full object-cover" src="<?php echo esc_url(boblift_image_src((string) $ui['heroImage'])); ?>" alt="<?php echo esc_attr((string) ($ui['heroImageAlt'] ?? 'BOB-LIFT contact support')); ?>" loading="eager" decoding="async" fetchpriority="high"><?php endif; ?>
        </div>
    </div>
</section>

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
