<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The event notice of the top bar (a trade show): while it is live, the bar shows it in place of its usual words. It is
 * top_bar.event in each language's theme options, edited in 网站设置 → 顶部公告条 and, since the WordPress.com connector
 * reaches only the core routes, as the boblift_top_bar field on wp/v2/pages (context edit, manage_options) for the
 * language of the page sent:
 *
 * Read: {"event": {...}, "live": true}. Write {"event": {"badge_label": "bauma CHINA 2026", "text": "...",
 * "short_text": "...", "link_label": "...", "link_href": "/....html", "starts_on": "", "ends_on": "2026-11-27"}} to
 * set it, {"event": null} to remove it. Send it on its own with the page id: the page itself is saved unchanged.
 *
 * The notice shows from starts_on to ends_on, both Y-m-d in China time and both optional; a date not written Y-m-d keeps
 * it hidden rather than guess. It needs its text; after its last day the bar goes back to its usual words by itself
 * (pages cached at the edge follow when their cache is renewed).
 */
function boblift_top_bar_event_keys(): array
{
    return ['badge_label', 'text', 'short_text', 'link_label', 'link_href', 'starts_on', 'ends_on'];
}

function boblift_notice_live(string $starts, string $ends, ?string $today = null): bool
{
    $today ??= (new DateTimeImmutable('now', new DateTimeZone('Asia/Shanghai')))->format('Y-m-d');
    foreach ([$starts, $ends] as $day) {
        if ($day === '') {
            continue;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $day);
        if (!$date || $date->format('Y-m-d') !== $day) {
            return false;
        }
    }
    return ($starts === '' || $starts <= $today) && ($ends === '' || $today <= $ends);
}

/** The event notice to show now, or null: it needs its text and today inside its dates. */
function boblift_top_bar_live_event(array $top_bar): ?array
{
    $event = is_array($top_bar['event'] ?? null) ? $top_bar['event'] : [];
    $event = array_map(static fn($value): string => trim((string) $value), array_intersect_key($event, array_flip(boblift_top_bar_event_keys())));
    if (($event['text'] ?? '') === '' || !boblift_notice_live($event['starts_on'] ?? '', $event['ends_on'] ?? '')) {
        return null;
    }
    return $event + array_fill_keys(boblift_top_bar_event_keys(), '');
}

function boblift_top_bar_event_clean(mixed $event): array|WP_Error
{
    if (!is_array($event) || array_diff(array_keys($event), boblift_top_bar_event_keys()) !== []) {
        return new WP_Error('boblift_top_bar_invalid', 'Send "event" with only these keys: ' . implode(', ', boblift_top_bar_event_keys()) . ', or null.', ['status' => 400]);
    }
    $clean = [];
    foreach (boblift_top_bar_event_keys() as $key) {
        $value = trim(sanitize_text_field((string) ($event[$key] ?? '')));
        if (in_array($key, ['starts_on', 'ends_on'], true) && $value !== '') {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
            if (!$date || $date->format('Y-m-d') !== $value) {
                return new WP_Error('boblift_top_bar_invalid', sprintf('"%s" is a date written Y-m-d, or empty.', $key), ['status' => 400]);
            }
        }
        $clean[$key] = $value;
    }
    if ($clean['text'] === '') {
        return new WP_Error('boblift_top_bar_invalid', '"text" is needed; send "event": null to remove the notice.', ['status' => 400]);
    }
    return $clean;
}

function boblift_top_bar_event_save(string $language, ?array $event): true|WP_Error
{
    global $wpdb;
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $stored = boblift_filter_bins_stored($language);
        $options = $stored['options'];
        $options['top_bar'] = is_array($options['top_bar'] ?? null) ? $options['top_bar'] : [];
        if ($event === null) {
            unset($options['top_bar']['event']);
        } else {
            $options['top_bar']['event'] = $event;
        }
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
    return new WP_Error('boblift_top_bar_busy', 'The settings kept changing while saving; try again.', ['status' => 409]);
}

function boblift_top_bar_register_page_field(): void
{
    register_rest_field('page', 'boblift_top_bar', [
        'get_callback' => static function (array $post) {
            $page = get_post((int) $post['id']);
            if (!$page instanceof WP_Post || !current_user_can('manage_options')) {
                return null;
            }
            $top_bar = boblift_filter_bins_stored(boblift_filter_bins_page_language($page))['options']['top_bar'] ?? [];
            $top_bar = is_array($top_bar) ? $top_bar : [];
            return ['event' => is_array($top_bar['event'] ?? null) ? $top_bar['event'] : null, 'live' => boblift_top_bar_live_event($top_bar) !== null];
        },
        'update_callback' => static function ($value, WP_Post $post) {
            if (!current_user_can('manage_options')) {
                return new WP_Error('boblift_top_bar_forbidden', 'Changing the top bar needs manage_options.', ['status' => 403]);
            }
            if (!is_array($value) || !array_key_exists('event', $value)) {
                return new WP_Error('boblift_top_bar_invalid', 'Send {"event": {...} | null}.', ['status' => 400]);
            }
            $event = $value['event'];
            if ($event !== null) {
                $event = boblift_top_bar_event_clean($event);
                if (is_wp_error($event)) {
                    return $event;
                }
            }
            $saved = boblift_top_bar_event_save(boblift_filter_bins_page_language($post), $event);
            return is_wp_error($saved) ? $saved : true;
        },
        'schema' => [
            'description' => 'Top bar event notice of this page\'s language. Write {"event": {...} | null}.',
            'type' => ['object', 'null'],
            'context' => ['edit'],
        ],
    ]);
}
add_action('rest_api_init', 'boblift_top_bar_register_page_field');
