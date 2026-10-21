<?php
// News author: Achord Chan (title per language) in each translated language's saved theme options. Prints old values first.
$titles = ['zh' => 'BOB-LIFT 产品运营总监', 'ja' => 'BOB-LIFT 製品運営ディレクター', 'es' => 'Director de Operaciones de Producto, BOB-LIFT', 'ru' => 'Директор по продуктовым операциям, BOB-LIFT', 'fr' => 'Directeur des opérations produit, BOB-LIFT', 'ar' => 'مدير عمليات المنتجات، BOB-LIFT'];
foreach ($titles as $lang => $title) {
    $options = get_option("boblift_theme_options_$lang");
    if (!is_array($options)) {
        WP_CLI::log("$lang: no options");
        continue;
    }
    $old = $options['site_ui']['newsArticle'] ?? [];
    WP_CLI::log("$lang before: " . wp_json_encode(['authorName' => $old['authorName'] ?? null, 'authorText' => $old['authorText'] ?? null], JSON_UNESCAPED_UNICODE));
    $options['site_ui']['newsArticle']['authorName'] = 'Achord Chan';
    $options['site_ui']['newsArticle']['authorText'] = $title;
    update_option("boblift_theme_options_$lang", $options);
}
