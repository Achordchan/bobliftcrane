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
 * boblift_specs and boblift_content_insert take), and written in one database transaction, so a failed request leaves
 * nothing behind; each changed body leaves a revision.
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
        // Rows read before the locks (core's permission checks, the pre-insert check) may be stale now.
        foreach ($group as $id) {
            clean_post_cache($id);
        }
        $plan = boblift_product_copy_plan($product_id, $value);
        if (is_wp_error($plan)) {
            return $plan;
        }
        $saved = boblift_product_copy_apply($plan);
        if (is_wp_error($saved)) {
            // Nothing of a failed request stays: drop whatever the cache picked up from the rolled-back writes.
            foreach ($group as $id) {
                clean_post_cache($id);
                wp_cache_delete($id, 'post_meta');
            }
        }
        return $saved;
    } finally {
        boblift_product_specs_locks(array_values($group), false);
    }
}

/** Writes a checked plan in one database transaction (bodies, revisions and meta), rolled back on any failure. */
function boblift_product_copy_apply(array $plan): true|WP_Error
{
    global $wpdb;
    if ($wpdb->query('START TRANSACTION') === false) {
        return new WP_Error('boblift_copy_write', 'Nothing was saved: the database transaction could not start; try again.', ['status' => 500]);
    }
    $saved = boblift_product_copy_apply_steps($plan);
    if (is_wp_error($saved)) {
        $wpdb->query('ROLLBACK');
        return $saved;
    }
    if ($wpdb->query('COMMIT') === false) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('boblift_copy_write', 'Nothing was saved: the database transaction could not be committed; try again.', ['status' => 500]);
    }
    return true;
}

/** The writes of a plan, in order; the first failure stops them. */
function boblift_product_copy_apply_steps(array $plan): true|WP_Error
{
    foreach ($plan as $step) {
        $id = $step['id'];
        if ($step['content'] !== null) {
            $saved = boblift_product_copy_write_content($id, $step['content']);
            if (is_wp_error($saved)) {
                return $saved;
            }
        }
        if ($step['faq'] !== null) {
            $saved = boblift_product_copy_write_meta($id, BOBLIFT_PRODUCT_COPY_FAQ_KEY, $step['faq'] === [] ? null : (string) wp_json_encode($step['faq'], JSON_UNESCAPED_UNICODE));
            if (is_wp_error($saved)) {
                return $saved;
            }
        }
        foreach ($step['seo'] as $meta_key => $text) {
            $saved = boblift_product_copy_write_meta($id, $meta_key, $text);
            if (is_wp_error($saved)) {
                return $saved;
            }
        }
        clean_post_cache($id);
    }
    return true;
}

/**
 * Writes the body column only, as a compare-and-set on the row read under the lock, so the title, excerpt and every
 * other column stay exactly as stored. The body itself already passed wp_kses_post unchanged (boblift_product_copy_plan()).
 */
function boblift_product_copy_write_content(int $id, string $content): true|WP_Error
{
    global $wpdb;
    $stored = $wpdb->get_var($wpdb->prepare("SELECT post_content FROM {$wpdb->posts} WHERE ID = %d", $id));
    if (!is_string($stored)) {
        return new WP_Error('boblift_copy_write', sprintf('Product %d could not be read; try again.', $id), ['status' => 500]);
    }
    if ($stored === $content) {
        return true;
    }
    $done = $wpdb->query($wpdb->prepare("UPDATE {$wpdb->posts} SET post_content = %s, post_modified = %s, post_modified_gmt = %s WHERE ID = %d AND BINARY post_content = BINARY %s", $content, current_time('mysql'), current_time('mysql', true), $id, $stored));
    if ($done !== 1) {
        return new WP_Error('boblift_copy_write', sprintf('The body of product %d was not saved (%s); try again.', $id, $done === false ? $wpdb->last_error : 'it changed meanwhile'), ['status' => $done === false ? 500 : 409]);
    }
    clean_post_cache($id);
    wp_save_post_revision($id);
    return true;
}

/** Sets (or, with null, deletes) one meta value and checks that the stored value is now the one requested. */
function boblift_product_copy_write_meta(int $id, string $key, ?string $value): true|WP_Error
{
    if ($value === null) {
        delete_post_meta($id, $key);
    } else {
        update_post_meta($id, $key, wp_slash($value));
    }
    wp_cache_delete($id, 'post_meta');
    $now = get_post_meta($id, $key, false);
    // A key stored in several rows gets the value in each (update_post_meta() changes them all).
    $ok = $value === null ? $now === [] : $now !== [] && array_unique($now) === [$value];
    return $ok ? true : new WP_Error('boblift_copy_write', sprintf('"%s" of product %d was not saved; try again.', $key, $id), ['status' => 500]);
}
