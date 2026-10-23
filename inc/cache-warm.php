<?php
if (!defined('ABSPATH')) {
    exit;
}

// Every deploy and every content save retires the products cache (boblift_cache_signature()) in all languages at once,
// and the non-English pages get few visits, so their first visitor waited 3–5 s while the page was built from scratch.
// After a change the cache is rebuilt in the background: WP-Cron requests the home, products and product category pages
// of every language a few at a time, as an anonymous visitor would, so the stored entries are ready before anyone comes.

const BOBLIFT_CACHE_WARM_STATE = 'boblift_cache_warm_state';
const BOBLIFT_CACHE_WARM_HOOK = 'boblift_cache_warm';
const BOBLIFT_CACHE_WARM_BATCH = 8;
const BOBLIFT_CACHE_WARM_TRIES = 3;

/** The pages to build, in every language: the home page, the products page and each product category with products. */
function boblift_cache_warm_urls(): array
{
    $languages = function_exists('pll_languages_list') ? (array) pll_languages_list(['fields' => 'slug']) : [''];
    $urls = [];
    foreach ($languages as $language) {
        $language = (string) $language;
        $urls[] = $language !== '' && function_exists('pll_home_url') ? pll_home_url($language) : home_url('/');
        $urls[] = boblift_public_path_url('/products.html', $language !== '' ? $language : null);
        $args = ['taxonomy' => 'product_category', 'hide_empty' => true];
        if ($language !== '') {
            $args['lang'] = $language;
        }
        $terms = get_terms($args);
        foreach (is_wp_error($terms) ? [] : $terms as $term) {
            $link = get_term_link($term);
            if (is_string($link) && $link !== '') {
                $urls[] = $link;
            }
        }
    }
    return array_values(array_unique(array_filter($urls)));
}

/** Schedules a rebuild on the first public request after the cache was retired. */
function boblift_cache_warm_schedule(): void
{
    // The rebuild's own requests never schedule another one.
    if (isset($_GET['boblift_warm']) || !boblift_products_cache_persistent() || wp_next_scheduled(BOBLIFT_CACHE_WARM_HOOK)) {
        return;
    }
    $state = get_option(BOBLIFT_CACHE_WARM_STATE);
    if (is_array($state) && ($state['signature'] ?? '') === boblift_cache_signature()) {
        return;
    }
    // A minute's delay lets a burst of saves (a product edit, a translation import) settle before the rebuild starts.
    wp_schedule_single_event(time() + MINUTE_IN_SECONDS, BOBLIFT_CACHE_WARM_HOOK);
}
add_action('template_redirect', 'boblift_cache_warm_schedule', 99);

/**
 * Builds the next few pages; a change made while it runs starts the list again for the new version. A page that times
 * out or answers with a server error is tried again after the list, up to BOBLIFT_CACHE_WARM_TRIES times in all.
 */
function boblift_cache_warm_run(): void
{
    $signature = boblift_cache_signature();
    $state = get_option(BOBLIFT_CACHE_WARM_STATE);
    $same = is_array($state) && ($state['signature'] ?? '') === $signature;
    $offset = $same ? (int) ($state['offset'] ?? 0) : 0;
    $retry = $same ? (array) ($state['retry'] ?? []) : [];
    $urls = boblift_cache_warm_urls();
    $batch = array_slice($urls, $offset, BOBLIFT_CACHE_WARM_BATCH);
    $offset += count($batch);
    if (!$batch) {
        $batch = array_slice(array_keys($retry), 0, BOBLIFT_CACHE_WARM_BATCH);
    }
    // Saved before the requests, so pages built by them see this version as already being rebuilt.
    update_option(BOBLIFT_CACHE_WARM_STATE, ['signature' => $signature, 'offset' => $offset, 'retry' => $retry], false);
    foreach ($batch as $url) {
        // The query string skips the CDN's copy so the request reaches PHP; it is not part of any cache key.
        $response = wp_remote_get(add_query_arg('boblift_warm', '1', $url), ['timeout' => 30, 'redirection' => 0, 'headers' => ['Cache-Control' => 'no-cache']]);
        $tries = (int) ($retry[$url] ?? 0) + 1;
        unset($retry[$url]);
        if ((is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) >= 500) && $tries < BOBLIFT_CACHE_WARM_TRIES) {
            $retry[$url] = $tries;
        }
    }
    update_option(BOBLIFT_CACHE_WARM_STATE, ['signature' => $signature, 'offset' => $offset, 'retry' => $retry], false);
    if ($offset < count($urls)) {
        wp_schedule_single_event(time(), BOBLIFT_CACHE_WARM_HOOK);
    } elseif ($retry) {
        // A struggling server gets a few minutes before the failed pages are asked for again.
        wp_schedule_single_event(time() + 5 * MINUTE_IN_SECONDS, BOBLIFT_CACHE_WARM_HOOK);
    }
}
add_action(BOBLIFT_CACHE_WARM_HOOK, 'boblift_cache_warm_run');
