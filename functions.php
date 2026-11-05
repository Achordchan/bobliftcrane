<?php
if (!defined('ABSPATH')) {
    exit;
}

/*
 * Theme modules, loaded in this order.
 *
 * A deploy copies files one by one, so for a few seconds a new functions.php can list a module that has not arrived
 * yet. A plain require_once then kills the request with a fatal error (seen 2026-10-28 and 2026-11-03). Instead, when
 * any module is missing, answer with a short uncached 503 and Retry-After, so visitors, crawlers and the edge cache
 * retry a moment later instead of getting (and caching) an error page.
 */
$boblift_theme_modules = [
    'fatal-log.php',
    'site-ui.php',
    'data.php',
    'inquiry-plugin.php',
    'post-types.php',
    'category-root-route.php',
    'product-lifecycle.php',
    'case-rest.php',
    'gallery-rest.php',
    'feedback-record.php',
    'template-functions.php',
    'home-products.php',
    'home-sections.php',
    'video-library.php',
    'video-center.php',
    'model-names.php',
    'product-guide.php',
    'product-archive-extras.php',
    'category-buying-guide.php',
    'category-video.php',
    'page-guide.php',
    'marine-brochure-models.php',
    'product-data-2026-10.php',
    'legacy-product-intros.php',
    'news-price-cleanup.php',
    'forms.php',
    'inquiry-mail.php',
    'inquiry-guard.php',
    'consent.php',
    'dingtalk-bridge.php',
    'brochures.php',
    'news-article.php',
    'news-pinning.php',
    'legal-pages.php',
    'brand-wordmark.php',
    'admin.php',
    'heading-punctuation.php',
    'importer.php',
    'setup.php',
    'seo-archives.php',
    'seo-rest.php',
    'cache-warm.php',
    'company-schema.php',
    'about-facts.php',
    'legacy-redirects.php',
    'tonnage-pages.php',
    'polylang-slugs.php',
    'rest-json-string-fields.php',
    'rest-translations.php',
    'rest-product-setup.php',
    'rest-content-insert.php',
    'rest-product-specs.php',
    'rest-legal-pages.php',
    'rest-filter-bins.php',
    'rest-top-bar.php',
    'rest-product-faq.php',
    'rest-product-copy.php',
    'rest-feedback-record-edit.php',
    'rest-tonnage-copy.php',
    'polylang-rewrites.php',
    'family-layout-migration.php',
];

$boblift_theme_dir = get_template_directory();
$boblift_missing_modules = [];
foreach ($boblift_theme_modules as $boblift_module) {
    if (!is_readable($boblift_theme_dir . '/inc/' . $boblift_module)) {
        $boblift_missing_modules[] = $boblift_module;
    }
}

if ($boblift_missing_modules !== []) {
    error_log('bobliftcrane: theme modules not ready (deploy in progress?): ' . implode(', ', $boblift_missing_modules));
    if (!headers_sent()) {
        http_response_code(503);
        header('Retry-After: 15');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Content-Type: text/html; charset=utf-8');
    }
    echo '<!doctype html><meta charset="utf-8"><meta name="robots" content="noindex"><title>BOB-LIFT</title>'
        . '<p style="font-family:sans-serif;margin:3rem auto;max-width:32rem;text-align:center">'
        . 'The site is updating. Please refresh in a few seconds.</p>';
    exit;
}

foreach ($boblift_theme_modules as $boblift_module) {
    require_once $boblift_theme_dir . '/inc/' . $boblift_module;
}
unset($boblift_theme_modules, $boblift_theme_dir, $boblift_missing_modules, $boblift_module);
