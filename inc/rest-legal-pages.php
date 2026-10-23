<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST: the boblift_legal_page field on wp/v2/pages, for the Privacy Policy and Terms of Use pages of every language.
 * Their wording is not in the page body but in the theme options of the page's language (privacy_policy_page /
 * terms_of_use_page), which the WordPress.com connector cannot reach; this field lets one sentence be corrected in one
 * language without opening the editor.
 *
 * Read (context edit): {"section": "privacy_policy_page", "language": "es", "stored": true, "fields": {"label": ...,
 * "title": ..., "description": ..., "updated_label": ..., "updated": ..., "side_title": ..., "side_text": ...},
 * "sections": [{"title": ..., "text": ...}]}, the wording the page shows (the language's stored options over the theme
 * defaults); "stored" is false when the language has no stored options for the page and shows the defaults.
 *
 * Write: one change or a list of them, each {"key": "sections.4.text", "from": "the text as shown now", "to": "new text"}.
 * "key" is one of the field names above or sections.N.title / sections.N.text for an existing clause. A change applies
 * only while the key still shows "from"; when it already shows "to" it is skipped, so sending the same request again
 * changes nothing. Any other value refuses the whole request. "to" is not empty and must come through the theme's own
 * sanitizing unchanged (plain text; line breaks only in "text"). Only the field may be sent: a request with any other
 * parameter is refused before anything is saved.
 *
 * The changes are checked in rest_pre_insert_page, before the core update, against the options as stored at that
 * moment (read past the object cache). The update callback writes them with a compare-and-set on the stored option
 * row, retrying from the fresh row if another save changed it in between, so a concurrent save of another page's
 * settings in the same language is never lost. A clause list that is changed is written whole into the language's
 * options (lists are not merged with the defaults), which is also what saving the page in the editor does. The core
 * update keeps the page's own title, body and excerpt exactly as stored.
 */
function boblift_register_rest_legal_page_field(): void
{
    register_rest_field('page', 'boblift_legal_page', [
        'get_callback' => static function (array $post) {
            $page = get_post((int) $post['id']);
            $section = $page instanceof WP_Post ? boblift_legal_page_section($page) : '';
            if ($section === '' || !current_user_can('edit_post', $page->ID)) {
                return null;
            }
            $language = boblift_legal_page_language($page);
            $stored = boblift_legal_page_stored_options($language);
            $shown = boblift_legal_page_shown($section, $stored['options']);
            return [
                'section' => $section,
                'language' => $language,
                'stored' => isset($stored['options'][$section]) && is_array($stored['options'][$section]),
                'fields' => array_intersect_key($shown, array_flip(boblift_legal_page_scalar_keys())),
                'sections' => $shown['sections'],
            ];
        },
        'update_callback' => static function ($value, WP_Post $post) {
            $saved = boblift_rest_save_legal_page($post, $value);
            return is_wp_error($saved) ? $saved : true;
        },
        'schema' => [
            'description' => 'Privacy Policy / Terms of Use wording of this page\'s language. Write: {"key": "sections.N.text", "from": text, "to": text}, one or a list.',
            'type' => ['object', 'array', 'null'],
            'context' => ['edit'],
        ],
    ]);
}
add_action('rest_api_init', 'boblift_register_rest_legal_page_field');

function boblift_rest_pre_insert_legal_page(stdClass|WP_Error $prepared, WP_REST_Request $request): stdClass|WP_Error
{
    if (is_wp_error($prepared) || $request['boblift_legal_page'] === null) {
        return $prepared;
    }
    $sent = array_keys(array_merge((array) $request->get_query_params(), (array) $request->get_body_params(), (array) $request->get_json_params()));
    // As for boblift_specs: of the parameters the route accepts, only the field and the response-shaping ones may come.
    $route_args = array_keys((array) ($request->get_attributes()['args'] ?? []));
    $other = array_diff(array_intersect($sent, $route_args), ['boblift_legal_page', 'id', 'context', '_fields']);
    if ($other !== []) {
        return new WP_Error('boblift_legal_alone', sprintf('Send "boblift_legal_page" on its own; this request also sends: %s.', implode(', ', $other)), ['status' => 400]);
    }
    $post = get_post((int) ($prepared->ID ?? 0));
    if (!$post instanceof WP_Post) {
        return $prepared;
    }
    $plan = boblift_legal_page_plan($post, $request['boblift_legal_page']);
    if (is_wp_error($plan)) {
        return $plan;
    }
    // The core update writes the page row back; keep its title, body and excerpt as stored (boblift_specs' guard).
    boblift_product_specs_core_guard($post->ID);
    return $prepared;
}
add_filter('rest_pre_insert_page', 'boblift_rest_pre_insert_legal_page', 10, 2);
add_action('rest_after_insert_page', static fn() => boblift_product_specs_core_guard(0));

/** privacy_policy_page or terms_of_use_page for a legal page in any language, '' for any other page. */
function boblift_legal_page_section(WP_Post $post): string
{
    if (boblift_is_privacy_policy_page_post($post)) {
        return 'privacy_policy_page';
    }
    return boblift_is_terms_of_use_page_post($post) ? 'terms_of_use_page' : '';
}

function boblift_legal_page_language(WP_Post $post): string
{
    return function_exists('boblift_mls_post_language') ? boblift_mls_post_language($post) : 'en';
}

function boblift_legal_page_scalar_keys(): array
{
    return ['label', 'title', 'description', 'updated_label', 'updated', 'side_title', 'side_text'];
}

/** The option row holding this language's theme options, as stored right now: name, raw row value (null when there is no row) and decoded options. */
function boblift_legal_page_stored_options(string $language): array
{
    global $wpdb;
    $name = function_exists('boblift_mls_option_key') ? boblift_mls_option_key($language) : 'boblift_theme_options';
    $raw = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $name));
    $options = $raw === null ? [] : maybe_unserialize($raw);
    return ['name' => $name, 'raw' => $raw, 'options' => is_array($options) ? $options : []];
}

/** The wording the page shows: the stored section over the theme defaults, clauses as a plain list of {title, text}. */
function boblift_legal_page_shown(string $section, array $options): array
{
    $defaults = $section === 'privacy_policy_page' ? boblift_privacy_policy_page_defaults() : boblift_terms_of_use_page_defaults();
    $shown = boblift_array_replace_recursive($defaults, is_array($options[$section] ?? null) ? $options[$section] : []);
    $shown['sections'] = array_values(array_map(static fn($item): array => [
        'title' => (string) (is_array($item) ? ($item['title'] ?? '') : ''),
        'text' => (string) (is_array($item) ? ($item['text'] ?? '') : ''),
    ], is_array($shown['sections'] ?? null) ? $shown['sections'] : []));
    return $shown;
}

/**
 * Checks the changes against the options as stored now and returns them as [key => to], leaving out the ones already
 * done; [] when nothing is left to do.
 */
function boblift_legal_page_plan(WP_Post $post, mixed $value, ?array $stored = null): array|WP_Error
{
    $section = boblift_legal_page_section($post);
    if ($section === '') {
        return new WP_Error('boblift_legal_page', 'boblift_legal_page only works on the Privacy Policy and Terms of Use pages.', ['status' => 400]);
    }
    $items = is_array($value) && array_is_list($value) ? $value : [$value];
    if ($items === []) {
        return new WP_Error('boblift_legal_empty', 'Send at least one change.', ['status' => 400]);
    }
    $stored ??= boblift_legal_page_stored_options(boblift_legal_page_language($post));
    $shown = boblift_legal_page_shown($section, $stored['options']);
    $plan = [];
    $seen = [];
    foreach ($items as $n => $item) {
        if (!is_array($item) || !is_string($item['key'] ?? null) || !is_string($item['from'] ?? null) || !is_string($item['to'] ?? null)) {
            return new WP_Error('boblift_legal_item', sprintf('Change %d needs string "key", "from" and "to".', $n), ['status' => 400]);
        }
        $key = $item['key'];
        if (isset($seen[$key])) {
            return new WP_Error('boblift_legal_item', sprintf('"%s" is changed twice in this request.', $key), ['status' => 400]);
        }
        $seen[$key] = true;
        if (in_array($key, boblift_legal_page_scalar_keys(), true)) {
            $current = (string) ($shown[$key] ?? '');
        } elseif (preg_match('/^sections\.(\d+)\.(title|text)$/', $key, $m) && isset($shown['sections'][(int) $m[1]])) {
            $current = $shown['sections'][(int) $m[1]][$m[2]];
        } else {
            return new WP_Error('boblift_legal_key', sprintf('Unknown key "%s" (this page has %d clauses, numbered from 0).', $key, count($shown['sections'])), ['status' => 400]);
        }
        $to = $item['to'];
        $clean = str_ends_with($key, '.text') || $key === 'description' || $key === 'side_text' ? sanitize_textarea_field($to) : sanitize_text_field($to);
        if (trim($to) === '' || $clean !== $to) {
            return new WP_Error('boblift_legal_to', sprintf('"to" for "%s" must be non-empty plain text that needs no cleaning.', $key), ['status' => 400]);
        }
        if ($current === $to) {
            continue;
        }
        if ($current !== $item['from']) {
            return new WP_Error('boblift_legal_stale', sprintf('"%s" no longer shows "from"; it now reads: %s', $key, $current), ['status' => 409]);
        }
        $plan[$key] = $to;
    }
    return $plan;
}

function boblift_rest_save_legal_page(WP_Post $post, mixed $value): true|WP_Error
{
    global $wpdb;
    $section = boblift_legal_page_section($post);
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $stored = boblift_legal_page_stored_options(boblift_legal_page_language($post));
        $plan = boblift_legal_page_plan($post, $value, $stored);
        if (is_wp_error($plan) || $plan === []) {
            return is_wp_error($plan) ? $plan : true;
        }
        $options = $stored['options'];
        $page = is_array($options[$section] ?? null) ? $options[$section] : [];
        $clauses = boblift_legal_page_shown($section, $options)['sections'];
        foreach ($plan as $key => $to) {
            if (preg_match('/^sections\.(\d+)\.(title|text)$/', $key, $m)) {
                $clauses[(int) $m[1]][$m[2]] = $to;
                $page['sections'] = $clauses;
            } else {
                $page[$key] = $to;
            }
        }
        $options[$section] = $page;
        if ($stored['raw'] === null) {
            // No row yet: add_option fails when another save created one first, and the next attempt reads that row.
            $done = add_option($stored['name'], $options, '', false);
        } else {
            $done = (bool) $wpdb->update($wpdb->options, ['option_value' => maybe_serialize($options)], ['option_name' => $stored['name'], 'option_value' => $stored['raw']]);
        }
        wp_cache_delete($stored['name'], 'options');
        wp_cache_delete('alloptions', 'options');
        if ($done) {
            return true;
        }
    }
    return new WP_Error('boblift_legal_busy', 'The settings kept changing while saving; try again.', ['status' => 409]);
}
