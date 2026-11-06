<?php
/**
 * REST: the saved words of the site's interface in one language, as the boblift_site_text field on wp/v2/pages (context
 * edit; the page only chooses the language). The WordPress.com connector reaches only the core routes, and these words
 * live in each language's theme options (boblift_theme_options, boblift_theme_options_<lang>), which no other field covers.
 *
 * Read (manage_options): {"lang": "zh", "values": {"site_ui": {...}, "static_text": {...}, "final_cta": {...},
 * "home.hero": {...}, "floating_contact_buttons": [...]}}, what that language saved (defaults are not included).
 *
 * Write (manage_options), sent on its own with the page id; the page itself is saved unchanged:
 *   {"set": {"site_ui.header.quoteLabel": "获取报价", "final_cta.button_label": "获取报价",
 *            "floating_contact_buttons.0.label": "获取报价", "static_text.strings.ee9acf6c3103.text": "获取报价"},
 *    "expect": {"site_ui.header.quoteLabel": "申请报价", ...},
 *    "dry_run": true}
 * Each key of "set" is a dotted path to one saved text in this language, under site_ui. (whose keys may hold - and +, as the
 * capacity bins 0-5 and 25+ do), final_cta., home.hero.,
 * floating_contact_buttons.<n>.<field> or static_text.strings.<id>.text. Only the texts named change; every other
 * saved value stays as it is. A path must already hold text: a typo is refused, not added. A value is cleaned like its
 * current value: wp_kses_post where the saved text holds HTML, sanitize_text_field otherwise. Addresses (keys href, url, link,
 * *_href, *_url) are read but never written here: a request naming one is refused with 400.
 * "expect" (optional) gives the value each path should hold now; if any differs, nothing is written and the
 * request fails with 409, listing them. "dry_run" returns the changes without writing.
 * The response's boblift_site_text then carries "result": {"dry_run", "changes": [{"path", "before", "after"}],
 * "unchanged": [paths already holding the value]}. All or nothing: a refused path writes none of the others.
 */

defined('ABSPATH') || exit;

/** Whether a dotted path names one of the texts this field may change. */
function boblift_site_text_path_allowed(string $path): bool
{
    return (bool) preg_match('/^(?:site_ui\.[A-Za-z0-9_]+(?:\.[A-Za-z0-9_+-]+)+|final_cta\.[A-Za-z0-9_]+|home\.hero\.[A-Za-z0-9_]+|floating_contact_buttons\.\d+\.[A-Za-z0-9_]+|static_text\.strings\.[a-f0-9]{12}\.text)$/', $path);
}

/** The text at a dotted path, or null when the path does not hold text. */
function boblift_site_text_get(array $options, string $path): ?string
{
    $node = $options;
    foreach (explode('.', $path) as $key) {
        if (!is_array($node) || !array_key_exists($key, $node)) {
            return null;
        }
        $node = $node[$key];
    }
    return is_string($node) ? $node : null;
}

function boblift_site_text_set(array $options, string $path, string $value): array
{
    $keys = explode('.', $path);
    $node = &$options;
    foreach ($keys as $key) {
        $node = &$node[$key];
    }
    $node = $value;
    unset($node);
    return $options;
}

/** Whether the text at a path is an address (key href, url or link, or ending in _href or _url): read here, never written. */
function boblift_site_text_is_url(string $path): bool
{
    $key = (string) substr($path, (int) strrpos('.' . $path, '.'));
    return (bool) preg_match('/^(?:href|url|link)$|_(?:href|url)$/', $key);
}

/** A new value cleaned the way its current value is stored: wp_kses_post where it holds HTML, sanitize_text_field otherwise. */
function boblift_site_text_clean(string $value, string $current): string
{
    return $current !== strip_tags($current) ? wp_kses_post($value) : sanitize_text_field($value);
}

/**
 * Works out a write without doing it: the options with the changes, each change with its value before and after, and the
 * paths that already hold their value. A WP_Error (nothing to write) for an invalid request or an "expect" that differs.
 */
function boblift_site_text_plan(array $options, mixed $request): array|WP_Error
{
    if (!is_array($request) || !is_array($request['set'] ?? null) || $request['set'] === []) {
        return new WP_Error('boblift_site_text_invalid', 'Send {"set": {"<path>": "<text>", ...}} with at least one path; "expect" and "dry_run" are optional.', ['status' => 400]);
    }
    $unknown = array_diff(array_keys($request), ['set', 'expect', 'dry_run']);
    if ($unknown !== []) {
        return new WP_Error('boblift_site_text_invalid', 'Unknown keys: ' . implode(', ', $unknown) . '.', ['status' => 400]);
    }
    $expect = $request['expect'] ?? [];
    if (!is_array($expect)) {
        return new WP_Error('boblift_site_text_invalid', '"expect" is an object of path => text.', ['status' => 400]);
    }
    $refused = [];
    foreach (array_keys($request['set'] + $expect) as $path) {
        $path = (string) $path;
        if (boblift_site_text_is_url($path)) {
            $refused[] = $path . ' (URL fields are not writable via this API)';
        } elseif (!boblift_site_text_path_allowed($path)) {
            $refused[] = $path . ' (not a site text path)';
        } elseif (boblift_site_text_get($options, $path) === null) {
            $refused[] = $path . ' (not saved in this language)';
        }
    }
    foreach ($request['set'] as $path => $value) {
        if (!is_string($value)) {
            $refused[] = $path . ' (value must be text)';
        }
    }
    if ($refused !== []) {
        return new WP_Error('boblift_site_text_invalid', 'Refused, nothing written: ' . implode('; ', $refused) . '.', ['status' => 400, 'refused' => $refused]);
    }
    $differs = [];
    foreach ($expect as $path => $value) {
        $current = boblift_site_text_get($options, (string) $path);
        if ($current !== (string) $value) {
            $differs[] = ['path' => (string) $path, 'expected' => (string) $value, 'current' => $current];
        }
    }
    if ($differs !== []) {
        return new WP_Error('boblift_site_text_conflict', 'Some texts are not what "expect" says; nothing written.', ['status' => 409, 'differs' => $differs]);
    }
    $changes = [];
    $unchanged = [];
    foreach ($request['set'] as $path => $value) {
        $path = (string) $path;
        $before = (string) boblift_site_text_get($options, $path);
        $after = boblift_site_text_clean($value, $before);
        if ($after === $before) {
            $unchanged[] = $path;
            continue;
        }
        $options = boblift_site_text_set($options, $path, $after);
        $changes[] = ['path' => $path, 'before' => $before, 'after' => $after];
    }
    return ['options' => $options, 'changes' => $changes, 'unchanged' => $unchanged];
}

/** Everything this field can change, as saved for the language. */
function boblift_site_text_values(array $options): array
{
    $pick = static fn(string $key) => is_array($options[$key] ?? null) ? $options[$key] : [];
    return [
        'site_ui' => $pick('site_ui'),
        'static_text' => $pick('static_text'),
        'final_cta' => $pick('final_cta'),
        'home.hero' => is_array($options['home']['hero'] ?? null) ? $options['home']['hero'] : [],
        'floating_contact_buttons' => $pick('floating_contact_buttons'),
    ];
}

/** The permission check of both read and write: 401 signed out, 403 without manage_options, true otherwise. */
function boblift_site_text_permission(): true|WP_Error
{
    if (!is_user_logged_in()) {
        return new WP_Error('boblift_site_text_unauthorized', 'Sign in to read or change the site texts.', ['status' => 401]);
    }
    if (!current_user_can('manage_options')) {
        return new WP_Error('boblift_site_text_forbidden', 'Reading or changing the site texts needs manage_options.', ['status' => 403]);
    }
    return true;
}

/** Plans the request and, unless it is a dry run, saves it in one compare-and-swap write of the language's options. */
function boblift_site_text_apply(string $language, mixed $request): array|WP_Error
{
    global $wpdb;
    $dry_run = is_array($request) && !empty($request['dry_run']) && $request['dry_run'] !== '0';
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $stored = boblift_filter_bins_stored($language);
        if ($stored['raw'] === null) {
            return new WP_Error('boblift_site_text_invalid', 'This language has no saved settings yet.', ['status' => 400]);
        }
        $plan = boblift_site_text_plan($stored['options'], $request);
        if (is_wp_error($plan)) {
            return $plan;
        }
        $result = ['lang' => $language, 'dry_run' => $dry_run, 'changes' => $plan['changes'], 'unchanged' => $plan['unchanged']];
        if ($dry_run || $plan['changes'] === []) {
            return $result;
        }
        $done = (bool) $wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND BINARY option_value = BINARY %s", maybe_serialize($plan['options']), $stored['name'], $stored['raw']));
        wp_cache_delete($stored['name'], 'options');
        wp_cache_delete('alloptions', 'options');
        if ($done) {
            return $result;
        }
    }
    return new WP_Error('boblift_site_text_busy', 'The settings kept changing while saving; try again.', ['status' => 409]);
}

function boblift_site_text_register_page_field(): void
{
    register_rest_field('page', 'boblift_site_text', [
        'get_callback' => static function (array $post) {
            $page = get_post((int) $post['id']);
            if (!$page instanceof WP_Post || boblift_site_text_permission() !== true) {
                return null;
            }
            $language = boblift_filter_bins_page_language($page);
            $read = ['lang' => $language, 'values' => boblift_site_text_values(boblift_filter_bins_stored($language)['options'])];
            // The write in this request, with each text before and after.
            if (isset($GLOBALS['boblift_site_text_result'][$page->ID])) {
                $read['result'] = $GLOBALS['boblift_site_text_result'][$page->ID];
            }
            return $read;
        },
        'update_callback' => static function ($value, WP_Post $post) {
            $allowed = boblift_site_text_permission();
            if ($allowed !== true) {
                return $allowed;
            }
            $result = boblift_site_text_apply(boblift_filter_bins_page_language($post), $value);
            if (is_wp_error($result)) {
                return $result;
            }
            $GLOBALS['boblift_site_text_result'][$post->ID] = $result;
            return true;
        },
        'schema' => [
            'description' => 'Saved interface texts of this page\'s language. Write {"set": {path: text}, "expect": {path: text}, "dry_run": true}.',
            'type' => ['object', 'null'],
            'context' => ['edit'],
        ],
    ]);
}
add_action('rest_api_init', 'boblift_site_text_register_page_field');
