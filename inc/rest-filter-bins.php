<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST: boblift/v1/filter-bins, the product list's spec filter bins (Lifting Capacity, Lifting Moment, Crane Self Weight)
 * of each language, which live in the language's theme options (site_ui.productArchive.capacityBins ...) where the
 * WordPress.com connector cannot reach them. The capacity bins should match the tonnage pages' bins
 * (boblift_tonnage_bins()), which a category page's "Browse by capacity" chips tick.
 *
 * GET ?language=en (all languages when left out): {"en": {"capacity": {"source": "saved", "bins": {"0-5": "0 - 5 tons"}},
 * "moment": ..., "weight": ...}}. "source" is "saved" (bins saved in 产品列表 settings), "legacy" (an old saved
 * capacityRanges list carried over, see boblift_site_ui_legacy_overrides()) or "default" (the theme's list).
 *
 * POST {"language": "en", "filter": "capacity", "bins": {"0-1": "Up to 1 t", "25+": "Over 25 t"}} saves that language's
 * bins; "bins": null drops them (and, for capacity, the legacy capacityRanges list) so the theme's list applies. Keys are
 * "min-max" or "min+" in the filter's unit, decimals written with "_"; labels are plain text. The write is a
 * compare-and-set on the stored option row, retried when another save changed it in between.
 *
 * The WordPress.com connector reaches only the core routes, so the same reads and writes are also the boblift_filter_bins
 * field on wp/v2/pages (context edit, manage_options), for the language of the page sent: reading it gives that
 * language's {"capacity": ..., "moment": ..., "weight": ...}; writing {"filter": "capacity", "bins": {...} | null} saves
 * as the POST above. Send it on its own with the page id: the page itself is saved unchanged.
 */
function boblift_filter_bins_keys(): array
{
    return ['capacity' => 'capacityBins', 'moment' => 'momentBins', 'weight' => 'weightBins'];
}

function boblift_filter_bins_languages(): array
{
    return function_exists('boblift_mls_languages') ? array_values(boblift_mls_languages()) : ['en'];
}

/** The language's theme options as stored now, read past the object cache. */
function boblift_filter_bins_stored(string $language): array
{
    global $wpdb;
    $name = function_exists('boblift_mls_option_key') ? boblift_mls_option_key($language) : 'boblift_theme_options';
    $raw = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $name));
    $options = $raw === null ? [] : maybe_unserialize($raw);
    return ['name' => $name, 'raw' => $raw, 'options' => is_array($options) ? $options : []];
}

/** Each filter's bins as the list shows them in this language, and where they come from. */
function boblift_filter_bins_shown(array $options): array
{
    $site_ui = is_array($options['site_ui'] ?? null) ? $options['site_ui'] : [];
    $archive = is_array($site_ui['productArchive'] ?? null) ? $site_ui['productArchive'] : [];
    $legacy = boblift_site_ui_legacy_overrides($site_ui)['productArchive'] ?? [];
    $defaults = boblift_site_ui_defaults()['productArchive'] ?? [];
    $shown = [];
    foreach (boblift_filter_bins_keys() as $filter => $key) {
        if (is_array($archive[$key] ?? null) && $archive[$key] !== []) {
            $shown[$filter] = ['source' => 'saved', 'bins' => $archive[$key]];
        } elseif (is_array($legacy[$key] ?? null) && $legacy[$key] !== []) {
            $shown[$filter] = ['source' => 'legacy', 'bins' => $legacy[$key]];
        } else {
            $shown[$filter] = ['source' => 'default', 'bins' => (array) ($defaults[$key] ?? [])];
        }
    }
    return $shown;
}

function boblift_filter_bins_clean(mixed $bins): array|WP_Error
{
    if (!is_array($bins) || $bins === []) {
        return new WP_Error('boblift_bins_invalid', '"bins" must be a non-empty object of "min-max" => label, or null.', ['status' => 400]);
    }
    $clean = [];
    foreach ($bins as $key => $label) {
        $key = (string) $key;
        $text = is_string($label) ? trim(sanitize_text_field($label)) : '';
        // The list matches a bin as min < figure <= max, so a bounded bin needs max above min.
        $bounds = explode('-', str_replace('_', '.', $key));
        $empty_range = count($bounds) === 2 && (float) $bounds[1] <= (float) $bounds[0];
        if (!preg_match('/^\d+(?:_\d+)?(?:-\d+(?:_\d+)?|\+)$/', $key) || $empty_range || $text === '' || $text !== trim((string) $label)) {
            return new WP_Error('boblift_bins_invalid', sprintf('Bin "%s" needs a "min-max" or "min+" key and a plain-text label.', $key), ['status' => 400]);
        }
        $clean[$key] = $text;
    }
    return $clean;
}

function boblift_filter_bins_save(string $language, string $filter, ?array $bins): true|WP_Error
{
    global $wpdb;
    $key = boblift_filter_bins_keys()[$filter];
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $stored = boblift_filter_bins_stored($language);
        $options = $stored['options'];
        $archive = is_array($options['site_ui']['productArchive'] ?? null) ? $options['site_ui']['productArchive'] : [];
        if ($bins === null) {
            unset($archive[$key]);
            if ($filter === 'capacity') {
                unset($archive['capacityRanges']);
            }
        } else {
            $archive[$key] = $bins;
        }
        $options['site_ui'] = is_array($options['site_ui'] ?? null) ? $options['site_ui'] : [];
        $options['site_ui']['productArchive'] = $archive;
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
    return new WP_Error('boblift_bins_busy', 'The settings kept changing while saving; try again.', ['status' => 409]);
}

function boblift_filter_bins_rest_register(): void
{
    $permission = static fn() => current_user_can('manage_options');
    register_rest_route('boblift/v1', '/filter-bins', [
        [
            'methods' => 'GET',
            'permission_callback' => $permission,
            'callback' => static function (WP_REST_Request $request) {
                $language = sanitize_key((string) $request->get_param('language'));
                $languages = boblift_filter_bins_languages();
                if ($language !== '' && !in_array($language, $languages, true)) {
                    return new WP_Error('boblift_bins_language', 'Unknown language.', ['status' => 400]);
                }
                $result = [];
                foreach ($language !== '' ? [$language] : $languages as $item) {
                    $result[$item] = boblift_filter_bins_shown(boblift_filter_bins_stored($item)['options']);
                }
                return $result;
            },
        ],
        [
            'methods' => 'POST',
            'permission_callback' => $permission,
            'callback' => static function (WP_REST_Request $request) {
                $language = sanitize_key((string) $request->get_param('language'));
                $filter = (string) $request->get_param('filter');
                if (!in_array($language, boblift_filter_bins_languages(), true)) {
                    return new WP_Error('boblift_bins_language', 'Unknown language.', ['status' => 400]);
                }
                if (!isset(boblift_filter_bins_keys()[$filter])) {
                    return new WP_Error('boblift_bins_filter', '"filter" is capacity, moment or weight.', ['status' => 400]);
                }
                if (!$request->has_param('bins')) {
                    return new WP_Error('boblift_bins_invalid', 'Send "bins": an object of bins, or null for the theme\'s list.', ['status' => 400]);
                }
                $bins = $request->get_param('bins');
                if ($bins !== null) {
                    $bins = boblift_filter_bins_clean($bins);
                    if (is_wp_error($bins)) {
                        return $bins;
                    }
                }
                $saved = boblift_filter_bins_save($language, $filter, $bins);
                if (is_wp_error($saved)) {
                    return $saved;
                }
                return [$language => boblift_filter_bins_shown(boblift_filter_bins_stored($language)['options'])];
            },
        ],
    ]);
}
add_action('rest_api_init', 'boblift_filter_bins_rest_register');

function boblift_filter_bins_page_language(WP_Post $post): string
{
    return function_exists('boblift_mls_post_language') ? (string) boblift_mls_post_language($post) : 'en';
}

function boblift_filter_bins_register_page_field(): void
{
    register_rest_field('page', 'boblift_filter_bins', [
        'get_callback' => static function (array $post) {
            $page = get_post((int) $post['id']);
            if (!$page instanceof WP_Post || !current_user_can('manage_options')) {
                return null;
            }
            return boblift_filter_bins_shown(boblift_filter_bins_stored(boblift_filter_bins_page_language($page))['options']);
        },
        'update_callback' => static function ($value, WP_Post $post) {
            if (!current_user_can('manage_options')) {
                return new WP_Error('boblift_bins_forbidden', 'Changing the filter bins needs manage_options.', ['status' => 403]);
            }
            $value = is_array($value) ? $value : [];
            $filter = (string) ($value['filter'] ?? '');
            if (!isset(boblift_filter_bins_keys()[$filter]) || !array_key_exists('bins', $value)) {
                return new WP_Error('boblift_bins_invalid', 'Send {"filter": "capacity" | "moment" | "weight", "bins": {...} | null}.', ['status' => 400]);
            }
            $bins = $value['bins'];
            if ($bins !== null) {
                $bins = boblift_filter_bins_clean($bins);
                if (is_wp_error($bins)) {
                    return $bins;
                }
            }
            $saved = boblift_filter_bins_save(boblift_filter_bins_page_language($post), $filter, $bins);
            return is_wp_error($saved) ? $saved : true;
        },
        'schema' => [
            'description' => 'Product list filter bins of this page\'s language. Write {"filter": "capacity", "bins": {"min-max": label} | null}.',
            'type' => ['object', 'null'],
            'context' => ['edit'],
        ],
    ]);
}
add_action('rest_api_init', 'boblift_filter_bins_register_page_field');
