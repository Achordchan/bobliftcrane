<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST: the boblift_product_copy field on wp/v2/product, so a product's body copy and its own questions can be written in
 * every language with one request through the WordPress.com connector (which only reaches wp/v2). boblift_specs fixes a
 * figure inside the copy; this field replaces the copy.
 *
 * Read (context edit): {"en": {"faq": [{"question": ..., "answer": ...}]}, "zh": {...}, ...}, the product's own questions
 * by language (the body is the item's content).
 *
 * Write, to the English product only: {"en": {"content": "<p>...</p>", "faq": [{"question": "...", "answer": "..."}]},
 * "zh": {...}, ...}. Each language named may send "content" (the whole body, HTML that wp_kses_post keeps unchanged),
 * "faq" (0 to 8 plain-text items, replacing that language's list; [] removes it), "seo_title" and "seo_description"
 * (plain text, the page's title tag and meta description), in any combination. The questions are shown in the product
 * page's "About the {model}" FAQ group after the generated ones. Languages not named are left as they are.
 * Only the field itself may be sent: a request with any other parameter is refused before anything is saved.
 *
 * Everything is checked before anything is written, under the per-post lock of every language version (the lock
 * boblift_specs and boblift_content_insert take); each changed body leaves a revision.
 */
const BOBLIFT_PRODUCT_COPY_FAQ_KEY = '_boblift_model_faq';

function boblift_register_rest_product_copy_field(): void
{
    register_rest_field('product', 'boblift_product_copy', [
        'get_callback' => static function (array $post): array {
            $copy = [];
            foreach (boblift_product_specs_group((int) $post['id']) as $language => $id) {
                if (current_user_can('edit_post', $id)) {
                    $copy[$language] = ['faq' => boblift_product_copy_faq($id)];
                }
            }
            return $copy;
        },
        'update_callback' => static function ($value, WP_Post $post) {
            $saved = boblift_rest_save_product_copy($post->ID, is_array($value) ? $value : []);
            if (is_wp_error($saved)) {
                return $saved;
            }
            $fresh = get_post($post->ID);
            if ($fresh instanceof WP_Post) {
                $post->post_content = $fresh->post_content;
            }
            return true;
        },
        'schema' => [
            'description' => 'Product questions by language; on the English product, write {"<language>": {"content", "faq", "seo_title", "seo_description"}} to replace them in each language named.',
            'type' => 'object',
            'context' => ['edit'],
        ],
    ]);
}
add_action('rest_api_init', 'boblift_register_rest_product_copy_field');

/** Refuses a request that sends anything else, and checks the whole value before the core update runs. */
function boblift_rest_pre_insert_product_copy(stdClass|WP_Error $prepared, WP_REST_Request $request): stdClass|WP_Error
{
    if (is_wp_error($prepared) || $request['boblift_product_copy'] === null) {
        return $prepared;
    }
    $sent = array_keys(array_merge((array) $request->get_query_params(), (array) $request->get_body_params(), (array) $request->get_json_params()));
    $route_args = array_keys((array) ($request->get_attributes()['args'] ?? []));
    $other = array_diff(array_intersect($sent, $route_args), ['boblift_product_copy', 'id', 'context', '_fields']);
    if ($other !== []) {
        return new WP_Error('boblift_copy_alone', sprintf('Send "boblift_product_copy" on its own; this request also sends: %s.', implode(', ', $other)), ['status' => 400]);
    }
    $post_id = (int) ($prepared->ID ?? 0);
    $value = $request['boblift_product_copy'];
    $plan = boblift_product_copy_plan($post_id, is_array($value) ? $value : []);
    if (is_wp_error($plan)) {
        return $plan;
    }
    // The core update writes the stored row back; keep it exactly as stored (see boblift_product_specs_core_guard()).
    boblift_product_specs_core_guard($post_id);
    return $prepared;
}
add_filter('rest_pre_insert_product', 'boblift_rest_pre_insert_product_copy', 10, 2);

/** The product's own questions as stored, [] when none. */
function boblift_product_copy_faq(int $post_id): array
{
    $items = boblift_decode_meta(get_post_meta($post_id, BOBLIFT_PRODUCT_COPY_FAQ_KEY, true), []);
    return is_array($items) ? array_values(array_filter($items, static fn($item): bool => is_array($item) && trim((string) ($item['question'] ?? '')) !== '' && trim((string) ($item['answer'] ?? '')) !== '')) : [];
}

/** Clean questions, or an error unless every item is a plain-text question and answer. */
function boblift_product_copy_clean_faq(mixed $items, string $language): array|WP_Error
{
    $invalid = new WP_Error('boblift_copy_faq', sprintf('"%s" "faq": send 0 to 8 plain-text items {"question": "...", "answer": "..."}.', $language), ['status' => 400]);
    if (!is_array($items) || count($items) > 8 || !array_is_list($items)) {
        return $invalid;
    }
    $clean = [];
    foreach ($items as $item) {
        $question = is_array($item) && is_string($item['question'] ?? null) ? trim($item['question']) : '';
        $answer = is_array($item) && is_string($item['answer'] ?? null) ? trim($item['answer']) : '';
        if ($question === '' || $answer === '' || sanitize_text_field($question) !== $question || sanitize_text_field($answer) !== $answer) {
            return $invalid;
        }
        $clean[] = ['question' => $question, 'answer' => $answer];
    }
    return $clean;
}

/** Works out a whole request: [language => ['id', 'content' => ?string, 'faq' => ?array]], or an error. */
function boblift_product_copy_plan(int $product_id, array $value): array|WP_Error
{
    $group = boblift_product_specs_group($product_id);
    if (($group['en'] ?? 0) !== $product_id) {
        return new WP_Error('boblift_copy_english', sprintf('Send boblift_product_copy to the English product (%d).', (int) ($group['en'] ?? 0)), ['status' => 400]);
    }
    if ($value === [] || array_is_list($value)) {
        return new WP_Error('boblift_copy_invalid', 'Send {"<language>": {"content": "...", "faq": [...]}} for at least one language.', ['status' => 400]);
    }
    if (array_diff_key($value, $group) !== []) {
        return new WP_Error('boblift_copy_language', sprintf('This product has no version in: %s.', implode(', ', array_keys(array_diff_key($value, $group)))), ['status' => 400]);
    }
    $plan = [];
    foreach ($value as $language => $parts) {
        $language = (string) $language;
        $id = $group[$language];
        if (!current_user_can('edit_post', $id)) {
            return new WP_Error('rest_cannot_edit', sprintf('Sorry, you are not allowed to edit product %d.', $id), ['status' => rest_authorization_required_code()]);
        }
        if (!is_array($parts) || $parts === [] || array_diff(array_keys($parts), ['content', 'faq', 'seo_title', 'seo_description']) !== []) {
            return new WP_Error('boblift_copy_invalid', sprintf('"%s": send any of "content", "faq", "seo_title", "seo_description", and nothing else.', $language), ['status' => 400]);
        }
        $step = ['id' => $id, 'content' => null, 'faq' => null, 'seo' => []];
        foreach (['seo_title' => '_seo_title', 'seo_description' => '_seo_description'] as $key => $meta_key) {
            if (!array_key_exists($key, $parts)) {
                continue;
            }
            $text = is_string($parts[$key]) ? trim($parts[$key]) : '';
            if ($text === '' || sanitize_text_field($text) !== $text) {
                return new WP_Error('boblift_copy_seo', sprintf('"%s" "%s" must be plain text.', $language, $key), ['status' => 400]);
            }
            $step['seo'][$meta_key] = $text;
        }
        if (array_key_exists('content', $parts)) {
            $content = is_string($parts['content']) ? trim($parts['content']) : '';
            if ($content === '' || trim(wp_strip_all_tags($content)) === '') {
                return new WP_Error('boblift_copy_content', sprintf('"%s" "content" must be HTML with text in it.', $language), ['status' => 400]);
            }
            // Refused rather than silently changed: the page must show what was sent.
            if (wp_kses_post($content) !== $content) {
                return new WP_Error('boblift_copy_content', sprintf('"%s" "content" holds markup wp_kses_post would remove; send plain HTML (p, h3, ul, ol, li, table, a, strong, em).', $language), ['status' => 400]);
            }
            $step['content'] = $content;
        }
        if (array_key_exists('faq', $parts)) {
            $faq = boblift_product_copy_clean_faq($parts['faq'], $language);
            if (is_wp_error($faq)) {
                return $faq;
            }
            $step['faq'] = $faq;
        }
        $plan[$language] = $step;
    }
    return $plan;
}

/** Locks every language version, checks the request again, and writes it. */
function boblift_rest_save_product_copy(int $product_id, array $value): bool|WP_Error
{
    $group = boblift_product_specs_group($product_id);
    if (!boblift_product_specs_locks(array_values($group), true)) {
        return new WP_Error('boblift_copy_busy', 'Another save of this product is in progress; nothing was saved, try again.', ['status' => 409]);
    }
    try {
        $plan = boblift_product_copy_plan($product_id, $value);
        if (is_wp_error($plan)) {
            return $plan;
        }
        foreach ($plan as $step) {
            if ($step['content'] !== null && $step['content'] !== (string) get_post_field('post_content', $step['id'], 'raw')) {
                // Only the body column changes; the core guard is cleared so this save is not held to the stored row.
                $guard = boblift_product_specs_core_guard();
                boblift_product_specs_core_guard(0);
                $saved = wp_update_post(wp_slash(['ID' => $step['id'], 'post_content' => $step['content']]), true);
                boblift_product_specs_core_guard($guard);
                if (is_wp_error($saved)) {
                    return $saved;
                }
            }
            if ($step['faq'] !== null) {
                if ($step['faq'] === []) {
                    delete_post_meta($step['id'], BOBLIFT_PRODUCT_COPY_FAQ_KEY);
                } else {
                    update_post_meta($step['id'], BOBLIFT_PRODUCT_COPY_FAQ_KEY, wp_slash((string) wp_json_encode($step['faq'], JSON_UNESCAPED_UNICODE)));
                }
            }
            foreach ($step['seo'] as $meta_key => $text) {
                update_post_meta($step['id'], $meta_key, wp_slash($text));
            }
            clean_post_cache($step['id']);
        }
        return true;
    } finally {
        boblift_product_specs_locks(array_values($group), false);
    }
}
