<?php
/**
 * BOB-LIFT does not publish prices. Two old promotion stories (SQ10SA5 and SQ10ZA5, May–June 2026) printed a price and
 * an offer poster with it. Their text is filtered as it is shown, in every language: the price line, the offer lines
 * before it and the poster go, and the SQ10SA5 story asks for an inquiry instead (the SQ10ZA5 story already does in its
 * next paragraph). Filtering on display keeps working when translations are imported or re-imported later.
 */
function boblift_price_story_slugs(): array
{
    return [
        'balancing-efficiency-and-practicality-the-sq10sa5-is-currently-on-sale-at-a-discount-id08025155' => 'sq10sa5',
        'bob-lift-sq10za5-limited-time-discount-hot-selling-id03356155' => 'sq10za5',
    ];
}

function boblift_price_story_inquiry_line(string $language): string
{
    $lines = [
        'en' => 'For the price of your configuration, send us an inquiry and our team will reply with a quotation.',
        'zh' => '如需了解您所需配置的价格，欢迎联系我们询价，我们的团队会为您报价。',
        'ja' => 'ご希望の仕様の価格については、お気軽にお問い合わせください。担当者よりお見積もりをお送りします。',
        'es' => 'Para conocer el precio de su configuración, envíenos una consulta y nuestro equipo le responderá con una cotización.',
        'fr' => 'Pour connaître le prix de votre configuration, envoyez-nous une demande et notre équipe vous répondra avec un devis.',
        'ru' => 'Чтобы узнать цену для вашей комплектации, отправьте нам запрос, и наша команда пришлёт коммерческое предложение.',
        'ar' => 'لمعرفة سعر التكوين الذي تحتاجونه، أرسلوا إلينا استفساركم وسيرد فريقنا بعرض سعر.',
    ];
    return $lines[$language] ?? $lines['en'];
}

/**
 * The story without its price line, the offer lines that introduce it and the offer poster. An offer line goes only when
 * it is recognisably part of the offer (the SQ10SA5 lead-in names the model; the SQ10ZA5 dates read 20 and 30 and the line
 * before them speaks of the price), so a story an editor has already reworded keeps its other paragraphs.
 */
function boblift_strip_story_price(string $content, string $story, string $inquiry_line): string
{
    // The posters go whether or not the price text is still there.
    $content = boblift_strip_story_price_posters($content);
    if (!preg_match_all('/<p\b[^>]*>.*?<\/p>/s', $content, $found, PREG_OFFSET_CAPTURE)) {
        return $content;
    }
    $paragraphs = $found[0];
    $price_at = null;
    foreach ($paragraphs as $index => $paragraph) {
        if (preg_match('/\bUSD\s*!/', $paragraph[0])) {
            $price_at = $index;
            break;
        }
    }
    if ($price_at === null) {
        return $content;
    }
    $text = static fn(int $index): string => $index >= 0 ? wp_strip_all_tags($paragraphs[$index][0]) : '';
    $first = $price_at;
    $replacement = '';
    if ($story === 'sq10sa5') {
        if (stripos($text($price_at - 1), 'SQ10SA5') !== false) {
            $first = $price_at - 1;
        }
        // The story ends right after its offer, so it gets an inquiry line in its place.
        $replacement = '<p><span>' . esc_html($inquiry_line) . '</span></p>';
    } elseif (preg_match('/20\D.*30/u', $text($price_at - 1))) {
        $first = $price_at - 1;
        if (preg_match('/price|价格|価格|precio|prix|цен|السعر/iu', $text($price_at - 2))) {
            $first = $price_at - 2;
        }
    }
    $start = (int) $paragraphs[$first][1];
    $end = (int) $paragraphs[$price_at][1] + strlen($paragraphs[$price_at][0]);
    return substr($content, 0, $start) . $replacement . substr($content, $end);
}

/** The offer posters (price printed on them): an <img> (wrapped in <p> once rendered), the same image in a <div>, then </figure>. */
function boblift_strip_story_price_posters(string $content): string
{
    return (string) preg_replace('/\s*(?:<p>\s*)?<img\b[^>]*\b(?:zhang\.png|lQLPJxfmBNvwfCHNBXrNBGKwyMGo9cJN6nAJ4sY0aUe2AA_1122_1402\.png)[^>]*>\s*(?:<\/p>\s*)?<div>\s*<img\b[^>]*>\s*<\/div>\s*<\/figure>/', '', $content);
}

function boblift_hide_published_story_prices(string $content): string
{
    $post = get_post();
    if (!$post instanceof WP_Post || $post->post_type !== 'post') {
        return $content;
    }
    $story = boblift_price_story_slugs()[$post->post_name] ?? '';
    if ($story === '') {
        return $content;
    }
    $language = function_exists('pll_get_post_language') ? (string) pll_get_post_language($post->ID) : 'en';
    return boblift_strip_story_price($content, $story, boblift_price_story_inquiry_line($language));
}
add_filter('the_content', 'boblift_hide_published_story_prices', 4);
