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
        if (boblift_paragraph_has_price(wp_strip_all_tags($paragraph[0]))) {
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
    $content = substr($content, 0, $start) . $replacement . substr($content, $end);
    // Any further price (an original and a discounted price, say) goes too; the inquiry line is added only once.
    return (string) preg_replace_callback('/<p\b[^>]*>.*?<\/p>/s', static function (array $paragraph): string {
        return boblift_paragraph_has_price(wp_strip_all_tags($paragraph[0])) ? '' : $paragraph[0];
    }, $content);
}

/** A price in the paragraph's text: an amount with USD or a dollar sign on either side, whatever punctuation follows. */
function boblift_paragraph_has_price(string $text): bool
{
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return (bool) preg_match('/\d[\d,.\s]*\+?\s*(?:USD|US\$|\$)|(?:USD|US\$|\$)[\s\p{P}]{0,3}\d/iu', $text);
}

/** The offer posters, by file name, at any size WordPress generates. */
function boblift_price_poster_pattern(): string
{
    return '(?<![\w-])(?:zhang|lQLPJxfmBNvwfCHNBXrNBGKwyMGo9cJN6nAJ4sY0aUe2AA_1122_1402)(?:-scaled)?(?:-\d+x\d+)?\.png';
}

/**
 * The offer posters (price printed on them) wherever they sit: each poster <img> goes on its own, then any <p>, <div> or
 * <figure> it leaves empty. Other markup around it, balanced or not, is left as it was.
 */
function boblift_strip_story_price_posters(string $content): string
{
    $content = (string) preg_replace('/<img\b[^>]*(?:' . boblift_price_poster_pattern() . ')[^>]*>/i', '', $content);
    do {
        $before = $content;
        $content = (string) preg_replace('/\s*<(p|div|figure)\b[^>]*>(?:\s|&nbsp;)*<\/\1>/i', '', $content);
    } while ($content !== $before);
    return $content;
}

/** Which price story a post is, by its own slug or, for a translation whose slug differs, by its Polylang translations. */
function boblift_price_story_of(WP_Post $post): string
{
    static $stories = [];
    if (isset($stories[$post->ID])) {
        return $stories[$post->ID];
    }
    $slugs = boblift_price_story_slugs();
    $story = $slugs[$post->post_name] ?? '';
    if ($story === '' && function_exists('pll_get_post_translations')) {
        foreach ((array) pll_get_post_translations($post->ID) as $translation_id) {
            $story = $slugs[(string) get_post_field('post_name', (int) $translation_id)] ?? '';
            if ($story !== '') {
                break;
            }
        }
    }
    return $stories[$post->ID] = $story;
}

function boblift_hide_published_story_prices(string $content): string
{
    $post = get_post();
    if (!$post instanceof WP_Post || $post->post_type !== 'post') {
        return $content;
    }
    $story = boblift_price_story_of($post);
    if ($story === '') {
        return $content;
    }
    $language = function_exists('pll_get_post_language') ? (string) pll_get_post_language($post->ID) : 'en';
    return boblift_strip_story_price($content, $story, boblift_price_story_inquiry_line($language));
}
// After wpautop (10) and shortcodes (11), so paragraphs saved without <p> tags are matched too.
add_filter('the_content', 'boblift_hide_published_story_prices', 20);

/** A poster set as either story's featured image is not shown (story lead image, news cards). */
function boblift_hide_price_story_poster_thumbnail($thumbnail_id, $post)
{
    $post = get_post($post);
    if (!$thumbnail_id || !$post instanceof WP_Post || boblift_price_story_of($post) === '') {
        return $thumbnail_id;
    }
    $file = (string) get_post_meta((int) $thumbnail_id, '_wp_attached_file', true);
    return preg_match('/(?:' . boblift_price_poster_pattern() . ')$/i', $file) ? 0 : $thumbnail_id;
}
add_filter('post_thumbnail_id', 'boblift_hide_price_story_poster_thumbnail', 10, 2);

/** A saved excerpt of either story that quotes a price is not shown (WordPress returns saved excerpts without the_content). */
function boblift_hide_price_story_excerpt(string $excerpt, $post = null): string
{
    $post = get_post($post);
    if (!$post instanceof WP_Post || boblift_price_story_of($post) === '') {
        return $excerpt;
    }
    return boblift_paragraph_has_price(wp_strip_all_tags($excerpt)) ? '' : $excerpt;
}
add_filter('get_the_excerpt', 'boblift_hide_price_story_excerpt', 20, 2);
