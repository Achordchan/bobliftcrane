<?php
if (!defined('ABSPATH')) exit;

/**
 * Rewrite internal links to point to translated versions in the current language.
 *
 * Optimizations applied:
 * - Static in-memory caches for url_to_postid() and pll_get_post() to minimize DB queries
 * - Early bail-out for wp-content, non-internal, and special protocol URLs
 * - Output buffer approach via template_redirect catches ALL page HTML
 *   (headers, footers, nav menus, widgets, block themes, page builders)
 * - Cache plugin compatibility: output buffer runs at template_redirect (priority 1),
 *   before most cache plugins capture output, and we avoid touching any cache APIs
 * - No static state persists between requests (everything scoped per request)
 *
 * Cache plugin compatibility notes:
 * - WP Super Cache / W3 Total Cache: they capture output after WordPress finishes;
 *   our ob_start fires during the request so the HTML they store is already rewritten.
 * - LiteSpeed Cache / WP Rocket: same flow — they cache the final HTML output,
 *   which will already have correct translated URLs.
 * - Object cache (Redis/Memcached): unaffected — we only use static PHP variables
 *   scoped to the current request; nothing is written to the object cache.
 * - Varnish / CDN full-page cache: URLs are rewritten per-language before the page
 *   is served, so language-specific cache variants will store the correct links.
 *
 * @param string $content HTML content (full page HTML or partial content block)
 * @return string Content with rewritten internal links
 */
function ptai_rewrite_links_to_current_language($content)
{
  // Exit early if Polylang is not available
  if (!function_exists('pll_current_language')) {
    return $content;
  }

  $current_lang = pll_current_language();
  if (empty($current_lang)) {
    return $content;
  }

  // ── Skip if already on the default language — links need no rewriting ────
  if (function_exists('pll_default_language') && $current_lang === pll_default_language()) {
    return $content;
  }

  // Skip processing if no anchor links found (performance optimization)
  if (strpos($content, '<a ') === false && strpos($content, '<a\t') === false) {
    return $content;
  }

  // Get site information (computed once per request via static cache)
  static $home_url  = null;
  static $site_host = null;

  if ($home_url === null) {
    $home_url  = home_url('/');
    $site_host = strtolower(wp_parse_url($home_url, PHP_URL_HOST));
  }

  // Protocols to skip entirely
  static $skip_protocols = ['mailto:', 'tel:', 'javascript:', 'data:', 'sms:', '#'];

  // Per-request caches to avoid repeated DB queries for the same URLs
  static $postid_cache      = [];  // url           => post_id (int|0)
  static $translated_cache  = [];  // "postid_lang" => translated_post_id

  $callback = function ($matches) use (
    $current_lang,
    $home_url,
    $site_host,
    $skip_protocols,
    &$postid_cache,
    &$translated_cache
  ) {
    $full_tag = $matches[0];   // Complete <a ...> tag
    $quote    = $matches[1];   // Quote character (" or ')
    $raw_href = $matches[2];   // Original href value as it appears in HTML
    $href     = html_entity_decode($raw_href, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // ── Skip Polylang language-switcher links ────────────────────────────
    // Polylang marks switcher <a> tags in several ways depending on context
    // (widget, nav menu, block). We check every known identifier:
    //   hreflang=          → always present on switcher <a> tags
    //   lang="xx"          → standalone lang attribute (e.g. lang="fr"), NOT
    //                        substrings like class="my-lang-link"
    //   rel="alternate"    → used in block-based switchers
    //   lang-item          → CSS class on switcher list items
    //   current-lang       → CSS class on the active language link
    //   polylang           → general Polylang class
    //   pll-parent-menu-item → nav menu dropdown parent class
    if (
      stripos($full_tag, 'hreflang=')       !== false ||
      preg_match('/\slang=["\'][a-z]{2}(-[a-z]{2,4})?["\']/i', $full_tag) || // lang="fr" or lang='fr-CA' as a standalone attribute
      stripos($full_tag, 'rel="alternate"') !== false ||
      stripos($full_tag, "rel='alternate'") !== false ||
      preg_match('/\b(lang-item|current-lang|polylang|pll-parent-menu-item)\b/i', $full_tag)
    ) {
      return $full_tag;
    }

    // ── Skip special protocols ───────────────────────────────────────────
    foreach ($skip_protocols as $proto) {
      if (stripos($href, $proto) === 0) {
        return $full_tag;
      }
    }

    // ── Skip media / asset URLs immediately (no DB lookup needed) ────────
    if (
      strpos($href, '/wp-content/') !== false ||
      strpos($href, '/wp-includes/') !== false
    ) {
      return $full_tag;
    }

    // ── Convert relative URLs to absolute ────────────────────────────────
    $parsed = wp_parse_url($href);
    if (!isset($parsed['host'])) {
      // Truly relative path — prefix with home URL
      $href = rtrim($home_url, '/') . '/' . ltrim($href, '/');
    }

    // ── Only process internal links (same domain) ─────────────────────────
    $href_host = strtolower(wp_parse_url($href, PHP_URL_HOST) ?? '');
    if (!empty($href_host) && $href_host !== $site_host) {
      return $full_tag;
    }

    // ── Strip query string and fragment temporarily ───────────────────────
    $clean_url = $href;

    $pos_query = strpos($clean_url, '?');
    if ($pos_query !== false) {
      $clean_url = substr($clean_url, 0, $pos_query);
    }

    $pos_fragment = strpos($clean_url, '#');
    if ($pos_fragment !== false) {
      $clean_url = substr($clean_url, 0, $pos_fragment);
    }

    // Preserve the suffix (query string + fragment) to re-append later
    $query_fragment = substr($href, strlen($clean_url));

    // ── Handle the home page ─────────────────────────────────────────────
    if (rtrim($clean_url, '/') === rtrim($home_url, '/')) {
      if (function_exists('pll_home_url')) {
        $translated_url = pll_home_url($current_lang);
        $new_href       = $translated_url . ltrim($query_fragment, '/');
        return str_replace(
          $quote . $raw_href . $quote,
          $quote . esc_url($new_href) . $quote,
          $full_tag
        );
      }
      return $full_tag;
    }

    // ── Resolve post ID from URL (cached) ────────────────────────────────
    if (!array_key_exists($clean_url, $postid_cache)) {
      $postid_cache[$clean_url] = ptai_cached_url_to_postid($clean_url, $current_lang); // int or 0
    }
    $post_id = $postid_cache[$clean_url];

    if (empty($post_id)) {
      return $full_tag; // Not a recognisable post/page URL
    }

    // Skip attachment posts (usually media links)
    if (get_post_type($post_id) === 'attachment') {
      return $full_tag;
    }

    // ── Get translated post ID (cached) ──────────────────────────────────
    $cache_key = $post_id . '_' . $current_lang;

    if (!array_key_exists($cache_key, $translated_cache)) {
      $translated_id = $post_id; // Default: same post if no translation found

      if (function_exists('pll_get_post')) {
        $maybe = pll_get_post($post_id, $current_lang);
        if (!empty($maybe)) {
          $translated_id = $maybe;
        }
      } elseif (function_exists('pll_get_post_translations')) {
        $translations = pll_get_post_translations($post_id);
        if (is_array($translations) && !empty($translations[$current_lang])) {
          $translated_id = $translations[$current_lang];
        }
      }

      $translated_cache[$cache_key] = $translated_id;
    }

    $translated_id = $translated_cache[$cache_key];

    // ── Build the new URL ─────────────────────────────────────────────────
    $translated_url = get_permalink($translated_id);

    if (empty($translated_url)) {
      return $full_tag;
    }

    $new_href = $translated_url . $query_fragment;

    return str_replace(
      $quote . $raw_href . $quote,
      $quote . esc_url($new_href) . $quote,
      $full_tag
    );
  };

  // Match all <a href="..."> and <a href='...'> tags
  $pattern = '/<a(?:\s[^>]*)?\shref=("|\')(.*?)\1[^>]*>/is';

  return preg_replace_callback($pattern, $callback, $content);
}


/**
 * BOB-LIFT change (2026-10-22, not in upstream 5.0.1): url_to_postid() runs one WP_Query per link, about 170 on a
 * product archive in Chinese or Japanese, so its results are also kept across requests, in a transient per language
 * and host. A version option, bumped when any post, permalink setting or Polylang language changes, retires them.
 */
function ptai_cached_url_to_postid($url, $lang)
{
  static $state = null;
  if ($state === null) {
    $version = (string) get_option('ptai_url_ids_version', '0');
    $name    = 'ptai_url_ids_' . md5($lang . '|' . home_url('/') . '|' . ($_SERVER['HTTP_HOST'] ?? ''));
    $stored  = get_transient($name);
    $state   = [
      'name'    => $name,
      'version' => $version,
      'map'     => (is_array($stored) && ($stored['version'] ?? null) === $version && is_array($stored['map'] ?? null)) ? $stored['map'] : [],
      'dirty'   => false,
    ];
  }

  if (!array_key_exists($url, $state['map'])) {
    $state['map'][$url] = (int) url_to_postid($url);
    if (!$state['dirty']) {
      $state['dirty'] = true;
      // The page buffer is rewritten while WordPress shuts down, so the new entries are saved after that.
      register_shutdown_function(static function () use (&$state) {
        set_transient($state['name'], ['version' => $state['version'], 'map' => $state['map']], DAY_IN_SECONDS);
      });
    }
  }
  return $state['map'][$url];
}

function ptai_bump_url_ids_version()
{
  // A fresh token per change (never an increment, which concurrent saves could repeat), once when the change burst
  // starts and once more when the request ends, so a map saved against the intermediate token is retired too.
  static $bumped = false;
  if ($bumped) {
    return;
  }
  $bumped = true;
  ptai_set_url_ids_version();
  add_action('shutdown', 'ptai_set_url_ids_version', 0);
}

function ptai_set_url_ids_version()
{
  update_option('ptai_url_ids_version', wp_generate_uuid4(), true);
}

foreach (['save_post', 'deleted_post', 'trashed_post', 'untrashed_post'] as $ptai_post_hook) {
  add_action($ptai_post_hook, static function ($post_id) {
    if (!wp_is_post_revision($post_id)) {
      ptai_bump_url_ids_version();
    }
  });
}
foreach (['update_option_permalink_structure', 'update_option_rewrite_rules', 'update_option_polylang', 'update_option_page_on_front', 'update_option_page_for_posts'] as $ptai_option_hook) {
  add_action($ptai_option_hook, 'ptai_bump_url_ids_version');
}
// Assigning a post's language or translation group (pll_set_post_language, pll_save_post_translations) only changes its
// term relationships, so those changes retire the cached lookups too.
// Permalinks can carry term slugs (%category%), so any term edit or relationship change also retires the lookups.
foreach (['set_object_terms', 'deleted_term_relationships', 'created_term', 'edited_term', 'delete_term'] as $ptai_term_hook) {
  add_action($ptai_term_hook, 'ptai_bump_url_ids_version', 10, 0);
}


/**
 * Output buffer wrapper — rewrites ALL links on the page, including headers,
 * footers, navigation menus, widgets, and third-party page-builder output.
 *
 * Fires at template_redirect priority 1, which means it starts the buffer
 * before WordPress begins rendering. The buffer is flushed naturally at
 * shutdown, after which cache plugins (WP Rocket, W3TC, LiteSpeed, etc.)
 * receive the already-rewritten HTML and store it as normal.
 *
 * We guard against:
 * - Admin pages (is_admin)
 * - REST API requests (REST_REQUEST constant)
 * - AJAX requests (DOING_AJAX)
 * - WP-CLI (WP_CLI)
 * - Cron jobs (DOING_CRON)
 * - Non-frontend XML/feed requests (is_feed)
 */
function ptai_start_output_buffer()
{
  // Only run on front-end page requests
  if (
    is_admin() ||
    (defined('REST_REQUEST')  && REST_REQUEST)  ||
    (defined('DOING_AJAX')    && DOING_AJAX)    ||
    (defined('WP_CLI')        && WP_CLI)         ||
    (defined('DOING_CRON')    && DOING_CRON)
  ) {
    return;
  }

  // ── Skip output buffer entirely on default language pages ─────────────────
  // pll_default_language() reads from an already-loaded option, so it's free.
  if (
    function_exists('pll_current_language') &&
    function_exists('pll_default_language') &&
    pll_current_language() === pll_default_language()
  ) {
    return;
  }

  ob_start(function ($html) {
    // Safety check: only process text/HTML responses
    if (empty($html) || !is_string($html)) {
      return $html;
    }

    // Skip feeds — they have their own XML structure
    if (is_feed()) {
      return $html;
    }

    return ptai_rewrite_links_to_current_language($html);
  });
}


// ── Hook registration ─────────────────────────────────────────────────────────

if (ptai_get_option('polytranslate_ai_options', 'translate_link_targets', 1)) {

  /**
   * Output buffer (catches everything: header, footer, nav, widgets, builders).
   * Priority 1 ensures we start before any other output-buffer plugins that
   * might fire on template_redirect.
   */
  add_action('template_redirect', 'ptai_start_output_buffer', 1);

  /**
   * Keep the individual content filters as a fallback for edge cases where
   * template_redirect might not fire (e.g. some REST-adjacent hybrid requests)
   * or when only partial content is being filtered by another plugin.
   */
  add_filter('the_content',                        'ptai_rewrite_links_to_current_language', 20);
  add_filter('the_excerpt',                        'ptai_rewrite_links_to_current_language', 20);
  add_filter('elementor/frontend/the_content',     'ptai_rewrite_links_to_current_language', 20);

  /**
   * Extra hooks for common contexts not covered by the_content.
   * These are redundant when the output buffer is active but serve as a
   * safety net if ob_start ever fails or is disabled by the host.
   */
  add_filter('widget_text',                        'ptai_rewrite_links_to_current_language', 20);
  add_filter('widget_block_content',               'ptai_rewrite_links_to_current_language', 20);
  add_filter('render_block',                       'ptai_rewrite_links_to_current_language', 20);
}
