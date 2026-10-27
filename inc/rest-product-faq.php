<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST: the boblift_product_faq field on wp/v2/pages, the "Ordering and service" questions every product page shows
 * (site_ui.productDetail.faqItems), which live in each language's theme options where the WordPress.com connector
 * cannot reach them. Like boblift_filter_bins it acts on the language of the page sent, and the page itself is saved
 * unchanged.
 *
 * Read (context edit, manage_options): {"source": "saved" | "default", "items": [{"question": ..., "answer": ...}]},
 * the list as that language stores it, or the theme's list (inc/site-ui.php) when nothing is saved.
 *
 * Write {"items": [{"question": "...", "answer": "..."}, ...]} replaces the language's whole list (1 to 12 items, plain
 * text); {"items": null} drops the saved list so the theme's list applies (English only: the theme's list has no
 * translations). The write is a compare-and-set on the stored option row, retried when another save changed it.
 */
function boblift_product_faq_shown(array $options): array
{
    $saved = $options['site_ui']['productDetail']['faqItems'] ?? null;
    if (is_array($saved) && $saved !== []) {
        return ['source' => 'saved', 'items' => array_values($saved)];
    }
    return ['source' => 'default', 'items' => array_values((array) (boblift_site_ui_defaults()['productDetail']['faqItems'] ?? []))];
}

function boblift_product_faq_clean(mixed $items): array|WP_Error
{
    $invalid = new WP_Error('boblift_faq_invalid', 'Send {"items": [{"question": "...", "answer": "..."}, ...]} with 1 to 12 plain-text items, or {"items": null}.', ['status' => 400]);
    if (!is_array($items) || $items === [] || count($items) > 12 || !array_is_list($items)) {
        return $invalid;
    }
    $clean = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            return $invalid;
        }
        $question = is_string($item['question'] ?? null) ? trim($item['question']) : '';
        $answer = is_string($item['answer'] ?? null) ? trim($item['answer']) : '';
        if ($question === '' || $answer === '' || sanitize_text_field($question) !== $question || sanitize_text_field($answer) !== $answer) {
            return $invalid;
        }
        $clean[] = ['question' => $question, 'answer' => $answer];
    }
    return $clean;
}

function boblift_product_faq_save(string $language, ?array $items): true|WP_Error
{
    global $wpdb;
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $stored = boblift_filter_bins_stored($language);
        $options = $stored['options'];
        $options['site_ui'] = is_array($options['site_ui'] ?? null) ? $options['site_ui'] : [];
        $detail = is_array($options['site_ui']['productDetail'] ?? null) ? $options['site_ui']['productDetail'] : [];
        if ($items === null) {
            unset($detail['faqItems']);
        } else {
            $detail['faqItems'] = $items;
        }
        $options['site_ui']['productDetail'] = $detail;
        if ($options === $stored['options']) {
            return true;
        }
        if ($stored['raw'] === null) {
            $done = add_option($stored['name'], $options, '', false);
        } else {
            $done = (bool) $wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND BINARY option_value = BINARY %s", maybe_serialize($options), $stored['name'], $stored['raw']));
        }
        wp_cache_delete($stored['name'], 'options');
        wp_cache_delete('alloptions', 'options');
        if ($done) {
            return true;
        }
    }
    return new WP_Error('boblift_faq_busy', 'The settings kept changing while saving; try again.', ['status' => 409]);
}

function boblift_product_faq_register_page_field(): void
{
    register_rest_field('page', 'boblift_product_faq', [
        'get_callback' => static function (array $post) {
            $page = get_post((int) $post['id']);
            if (!$page instanceof WP_Post || !current_user_can('manage_options')) {
                return null;
            }
            return boblift_product_faq_shown(boblift_filter_bins_stored(boblift_filter_bins_page_language($page))['options']);
        },
        'update_callback' => static function ($value, WP_Post $post) {
            if (!current_user_can('manage_options')) {
                return new WP_Error('boblift_faq_forbidden', 'Changing the product FAQ needs manage_options.', ['status' => 403]);
            }
            if (!is_array($value) || !array_key_exists('items', $value)) {
                return new WP_Error('boblift_faq_invalid', 'Send {"items": [...]} or {"items": null}.', ['status' => 400]);
            }
            $language = boblift_filter_bins_page_language($post);
            $items = $value['items'];
            if ($items === null) {
                if ($language !== 'en') {
                    return new WP_Error('boblift_faq_invalid', 'Only English can fall back to the theme\'s list; send this language\'s items instead.', ['status' => 400]);
                }
            } else {
                $items = boblift_product_faq_clean($items);
                if (is_wp_error($items)) {
                    return $items;
                }
            }
            $saved = boblift_product_faq_save($language, $items);
            return is_wp_error($saved) ? $saved : true;
        },
        'schema' => [
            'description' => 'Product page "Ordering and service" questions of this page\'s language. Write {"items": [{"question": ..., "answer": ...}] | null}.',
            'type' => ['object', 'null'],
            'context' => ['edit'],
        ],
    ]);
}
add_action('rest_api_init', 'boblift_product_faq_register_page_field');
