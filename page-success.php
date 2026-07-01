<?php
/**
 * Template Name: Inquiry Success
 */
if (!defined('ABSPATH')) {
    exit;
}
get_header();
$ui = boblift_site_ui('successPage');
?>
<section class="page-shell py-14 md:py-24">
    <div class="mx-auto max-w-4xl rounded-[34px] border border-line bg-white p-8 text-center shadow-[0_28px_90px_rgba(8,35,74,0.10)] md:p-12">
        <div class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
            <?php echo boblift_icon('check-circle', 'h-8 w-8'); ?>
        </div>
        <p class="mt-7 text-[11px] font-black uppercase tracking-[0.24em] text-blue-700"><?php echo esc_html($ui['label'] ?? 'Inquiry Submitted'); ?></p>
        <h1 class="mt-4 text-4xl font-black leading-tight tracking-[-0.055em] text-ink md:text-6xl"><?php echo esc_html($ui['title'] ?? 'Thank you for your inquiry.'); ?></h1>
        <p class="mx-auto mt-5 max-w-2xl text-base font-semibold leading-8 text-slate-600 md:text-lg"><?php echo esc_html($ui['description'] ?? 'Your request has been received. The BOB-LIFT team will review your project details and contact you soon.'); ?></p>
        <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
            <a class="inline-flex h-12 items-center justify-center gap-2 rounded-full bg-navy px-7 text-sm font-black text-white shadow-[0_18px_42px_rgba(8,35,74,0.18)] transition hover:-translate-y-0.5 hover:bg-[#0b2d5b]" href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html($ui['backHomeLabel'] ?? 'Back to Home'); ?> <?php echo boblift_icon('arrow-right', 'h-4 w-4'); ?></a>
            <a class="inline-flex h-12 items-center justify-center gap-2 rounded-full border border-line bg-white px-7 text-sm font-black text-ink transition hover:-translate-y-0.5 hover:border-blue-200 hover:text-blue-700" href="<?php echo esc_url(function_exists('boblift_product_archive_url') ? boblift_product_archive_url() : home_url('/products.html')); ?>"><?php echo esc_html($ui['browseProductsLabel'] ?? 'Browse Products'); ?></a>
        </div>
    </div>
</section>
<?php get_footer(); ?>
