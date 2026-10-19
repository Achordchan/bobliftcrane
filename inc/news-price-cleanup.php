<?php
/**
 * BOB-LIFT does not publish prices. Two old promotion stories (SQ10SA5 and SQ10ZA5, May–June 2026) printed a price and
 * an offer poster with it; in every language, the price lines and the poster go and the SQ10SA5 story asks for an
 * inquiry instead (the SQ10ZA5 story already does in its next paragraph). Runs once; edits nothing that lacks the price.
 */
function boblift_price_story_slugs(): array
{
    // Story slug => how many paragraphs before the price line belong to the offer (lead-in and dates).
    return [
        'balancing-efficiency-and-practicality-the-sq10sa5-is-currently-on-sale-at-a-discount-id08025155' => 1,
        'bob-lift-sq10za5-limited-time-discount-hot-selling-id03356155' => 2,
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

/** The story without its price lines and offer poster; unchanged when it has no price line. */
function boblift_strip_story_price(string $content, int $lead_in, string $inquiry_line): string
{
    if (!preg_match_all('/<p\b[^>]*>.*?<\/p>/s', $content, $paragraphs, PREG_OFFSET_CAPTURE)) {
        return $content;
    }
    $price_at = null;
    foreach ($paragraphs[0] as $index => $paragraph) {
        if (preg_match('/\bUSD\s*!/', $paragraph[0])) {
            $price_at = $index;
            break;
        }
    }
    if ($price_at === null || $price_at < $lead_in) {
        return $content;
    }
    $first = $paragraphs[0][$price_at - $lead_in];
    $last = $paragraphs[0][$price_at];
    $start = (int) $first[1];
    $end = (int) $last[1] + strlen($last[0]);
    // The SQ10SA5 story ends right after its offer, so it gets an inquiry line in its place.
    $replacement = $lead_in === 1 ? '<p><span>' . esc_html($inquiry_line) . '</span></p>' : '';
    $content = substr($content, 0, $start) . $replacement . substr($content, $end);
    // The offer poster (price printed on it) follows the price line: an <img>, then the same image in a <div>, then </figure>.
    return (string) preg_replace('/\s*(?:<p>\s*)?<img\b[^>]*\b(?:zhang\.png|lQLPJxfmBNvwfCHNBXrNBGKwyMGo9cJN6nAJ4sY0aUe2AA_1122_1402\.png)[^>]*>\s*(?:<\/p>\s*)?<div>\s*<img\b[^>]*>\s*<\/div>\s*<\/figure>/', '', $content);
}

function boblift_remove_published_story_prices(): void
{
    if (get_option('boblift_news_prices_removed_v1') !== false || !post_type_exists('post')) {
        return;
    }
    $failed = 0;
    $updated = 0;
    // A visitor's request has no unfiltered_html, so kses would rewrite the stories' markup on save; this edit only removes text.
    $kses = has_filter('content_save_pre', 'wp_filter_post_kses') !== false;
    if ($kses) {
        kses_remove_filters();
    }
    foreach (boblift_price_story_slugs() as $slug => $lead_in) {
        $ids = get_posts([
            'post_type' => 'post',
            'post_status' => ['publish', 'draft', 'pending', 'private', 'future'],
            'name' => $slug,
            'numberposts' => -1,
            'fields' => 'ids',
            'suppress_filters' => true,
            'lang' => '',
        ]);
        if (function_exists('pll_get_post_translations')) {
            foreach ($ids as $id) {
                $ids = array_merge($ids, array_values(pll_get_post_translations((int) $id)));
            }
        }
        foreach (array_unique(array_map('intval', $ids)) as $id) {
            $post = get_post($id);
            if (!$post instanceof WP_Post || $post->post_type !== 'post') {
                continue;
            }
            $language = function_exists('pll_get_post_language') ? (string) pll_get_post_language($id) : 'en';
            $content = boblift_strip_story_price($post->post_content, $lead_in, boblift_price_story_inquiry_line($language));
            if ($content === $post->post_content) {
                continue;
            }
            $result = wp_update_post(['ID' => $id, 'post_content' => wp_slash($content)], true);
            if (is_wp_error($result) || !$result) {
                $failed++;
            } else {
                $updated++;
            }
        }
    }
    if ($kses) {
        kses_init_filters();
    }
    // A run that only finds stories already cleaned changes nothing, so a retry after a failure is safe; only a clean
    // run is recorded.
    if ($failed === 0) {
        update_option('boblift_news_prices_removed_v1', $updated, false);
    }
}
add_action('init', 'boblift_remove_published_story_prices', 41);
