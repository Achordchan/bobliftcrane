<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * A page's guide: key figures, numbered points and frequently asked questions, shown on the Service & Operation, Factory
 * Tour and Truck Mounting & Export pages with the same layout as the category buying guides. Each Polylang language page
 * holds its own copy in the page meta _boblift_page_guide, a JSON string written through REST:
 * POST /wp/v2/pages/<id> with {"meta": {"_boblift_page_guide": "{...}"}}.
 *   {"eyebrow": "", "title": "", "intro": "", "facts": [{"value": "", "label": ""}], "points": [{"title": "", "text": ""}],
 *    "faq": [{"q": "", "a": ""}]}
 * intro, points[].text and faq[].a may hold links, bold and italics (a[href], strong, em); everything else is plain text.
 */

/** The buying guide's cleaning plus the key figures; anything that is not a JSON object is stored as ''. */
function boblift_page_guide_sanitize($value): string
{
    $data = json_decode(is_string($value) ? $value : '', true);
    if (!is_array($data)) {
        return '';
    }
    $guide = json_decode(boblift_buying_guide_sanitize($value), true);
    $guide = is_array($guide) ? $guide : ['eyebrow' => '', 'title' => '', 'intro' => '', 'points' => [], 'faq' => []];
    $plain = static fn($text): string => trim(sanitize_text_field((string) $text));
    $guide['facts'] = [];
    foreach ((array) ($data['facts'] ?? []) as $fact) {
        if (is_array($fact) && $plain($fact['value'] ?? '') !== '' && $plain($fact['label'] ?? '') !== '') {
            $guide['facts'][] = ['value' => $plain($fact['value']), 'label' => $plain($fact['label'])];
        }
    }
    // A page guide always has its own heading (there is no generic fallback title for it), so one without is not stored.
    if ($guide['title'] === '' || (!$guide['points'] && !$guide['faq'] && !$guide['facts'])) {
        return '';
    }
    return (string) wp_json_encode($guide, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function boblift_register_page_guide_meta(): void
{
    register_post_meta('page', '_boblift_page_guide', [
        'type' => 'string',
        'single' => true,
        'default' => '',
        'show_in_rest' => true,
        'sanitize_callback' => 'boblift_page_guide_sanitize',
        'auth_callback' => static fn($allowed, $meta_key, $post_id): bool => current_user_can('edit_post', (int) $post_id),
    ]);
}
add_action('init', 'boblift_register_page_guide_meta');

/** The guide of the current page, wrapped in the page's section spacing; nothing when the page has none. */
function boblift_render_page_guide(string $section_class = 'border-t border-line py-12 md:py-16'): void
{
    $post_id = (int) get_queried_object_id();
    $guide = $post_id ? json_decode((string) get_post_meta($post_id, '_boblift_page_guide', true), true) : null;
    if (!is_array($guide) || trim((string) ($guide['title'] ?? '')) === '' || (empty($guide['points']) && empty($guide['faq']) && empty($guide['facts']))) {
        return;
    }
    ?>
    <div class="<?php echo esc_attr($section_class); ?>">
        <div class="page-shell">
            <?php boblift_render_guide_block($guide, 'page-guide', 'page-guide-title', true); ?>
        </div>
    </div>
    <?php
}
