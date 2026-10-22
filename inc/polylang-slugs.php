<?php
/**
 * Lets a translation keep the same slug as the page it translates (/ja/foo.html next to /foo.html).
 *
 * Free Polylang makes WordPress add -2, -3 to a slug that another language already uses. With languages in the URL
 * directory (/ja/, /zh/) the slug only has to be unique inside its own language, so when every post that holds the slug
 * is in a different language than the post being saved, the original slug is kept. Works on posts, pages and custom
 * post types.
 */
/**
 * The "lang" a REST request carries, wherever it was sent (query string, form or JSON body); null outside REST.
 * $_REQUEST misses a JSON body, so the parsed request is remembered before the post is saved.
 */
function boblift_rest_incoming_language(?WP_REST_Request $request = null, bool $store = false): ?string
{
    static $language = null;
    if ($store) {
        $value = $request ? $request->get_param('lang') : null;
        $language = is_string($value) ? $value : null;
    }
    return $language;
}

add_filter('rest_request_before_callbacks', static function ($response, $handler, WP_REST_Request $request) {
    boblift_rest_incoming_language($request, true);
    return $response;
}, 10, 3);

/** WordPress's own reasons to refuse a slug apart from another post holding it: feeds, "embed", page numbers (hierarchical types), date archives (posts). */
function boblift_slug_is_reserved(string $slug, string $post_type, int $post_parent, bool $hierarchical): bool
{
    global $wp_rewrite;
    $feeds = is_array($wp_rewrite->feeds ?? null) ? $wp_rewrite->feeds : [];
    $pagination = $wp_rewrite->pagination_base ?? 'page';
    if (in_array($slug, $feeds, true) || $slug === 'embed') {
        return true;
    }
    if ($hierarchical) {
        // Page-number shaped slugs ("2", "page2") are only reserved for hierarchical types, as in core.
        if (preg_match('@^(' . preg_quote($pagination, '@') . ')?\\d+$@', $slug)) {
            return true;
        }
        return (bool) apply_filters('wp_unique_post_slug_is_bad_hierarchical_slug', false, $slug, $post_type, $post_parent);
    }
    // Same date-archive clash core checks for posts: a two-digit month or day slug under a /%year%/ or /%monthnum%/ structure.
    if ($post_type === 'post' && preg_match('/^\\d+$/', $slug)) {
        $structure = (string) get_option('permalink_structure');
        $number = (int) $slug;
        if ((str_contains($structure, '%year%/%postname%') && $number >= 1 && $number <= 12)
            || (str_contains($structure, '%monthnum%/%postname%') && $number >= 1 && $number <= 31)) {
            return true;
        }
    }
    return (bool) apply_filters('wp_unique_post_slug_is_bad_flat_slug', false, $slug, $post_type);
}

add_filter('wp_unique_post_slug', static function (string $slug, int $post_id, string $post_status, string $post_type, int $post_parent, string $original_slug): string {
    // Cases are looked up by their own post-type route, which does not pick the post by language, so each translation keeps a
    // unique slug (Polylang's -ja/-zh style suffix) instead of sharing the English one.
    if ($slug === $original_slug || $post_type === 'attachment' || $post_type === 'case' || !function_exists('pll_get_post_language') || !function_exists('pll_languages_list')) {
        return $slug;
    }
    // Languages only have separate addresses in directory, subdomain or domain mode (force_lang 1-3); when the language comes
    // from the content (0), a shared slug would give two posts one URL.
    $force_lang = (int) (((array) get_option('polylang', []))['force_lang'] ?? 0);
    if ($force_lang < 1 || $force_lang > 3) {
        return $slug;
    }
    $language = pll_get_post_language($post_id);
    if (!$language) {
        return $slug;
    }
    // While a save changes the post's language (classic editor field or REST "lang"), the stored language is stale, so the
    // shared slug is only allowed when the incoming language is the stored one.
    foreach ([$_POST['post_lang_choice'] ?? null, $_REQUEST['lang'] ?? null, boblift_rest_incoming_language()] as $incoming) {
        if (is_string($incoming) && !in_array($incoming, ['', 'all'], true) && sanitize_key($incoming) !== $language) {
            return $slug;
        }
    }
    global $wpdb;
    // WordPress keeps hierarchical slugs unique per parent and other slugs unique across the whole post type.
    $holders = is_post_type_hierarchical($post_type)
        ? $wpdb->get_col($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = %s AND post_parent = %d AND ID <> %d AND post_status NOT IN ('trash', 'auto-draft')",
            $original_slug, $post_type, $post_parent, $post_id
        ))
        : $wpdb->get_col($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = %s AND ID <> %d AND post_status NOT IN ('trash', 'auto-draft')",
            $original_slug, $post_type, $post_id
        ));
    // An attachment holding the slug keeps WordPress's suffix, so attachment and post URLs never coincide.
    $attachment = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'attachment' AND ID <> %d LIMIT 1",
        $original_slug, $post_id
    ));
    if ($attachment) {
        return $slug;
    }
    // No same-type holder means WordPress changed the slug for another reason (reserved word, feed or date clash): keep its choice.
    if (!$holders) {
        return $slug;
    }
    // A holder in another language does not rule out WordPress's other reasons for a suffix, so check them on their own.
    if (boblift_slug_is_reserved($original_slug, $post_type, $post_parent, is_post_type_hierarchical($post_type))) {
        return $slug;
    }
    foreach ($holders as $holder) {
        // A holder with no language (or the same one) shares this namespace, so WordPress's suffix stays.
        $holder_language = pll_get_post_language((int) $holder);
        if (!$holder_language || $holder_language === $language) {
            return $slug;
        }
    }
    return $original_slug;
}, 10, 6);

/**
 * Lets a category translation keep the English category's slug too (/ru/marine-crane next to /marine-crane), as ja and zh
 * already do. Free Polylang suffixes such a slug (-ru) on every save, and wp_update_term() refuses a slug that a term in any
 * language holds, so while a translated term is updated both checks are limited to the term's own language. A new term has
 * no ID yet and keeps the usual checks; the multilingual plugin gives a new translation the shared slug right after linking it.
 */
function boblift_term_slug_state(?array $state = null): array
{
    static $current = [];
    if ($state !== null) {
        $current = $state;
    }
    return $current;
}

// wp_update_term() sanitizes the term ID before the slug, so the term being saved is known when the slug is checked.
add_filter('pre_term_term_id', static function ($term_id) {
    $state = [];
    $force_lang = (int) (((array) get_option('polylang', []))['force_lang'] ?? 0);
    if (is_numeric($term_id) && (int) $term_id > 0 && $force_lang >= 1 && $force_lang <= 3 && function_exists('pll_get_term_language')) {
        $language = (string) pll_get_term_language((int) $term_id, 'slug');
        // A save that also changes the term's language (the edit form's language field) keeps the usual checks.
        foreach ([$_POST['term_lang_choice'] ?? null, $_POST['inline_lang_choice'] ?? null] as $incoming) {
            if (is_string($incoming) && $incoming !== '' && sanitize_key($incoming) !== $language) {
                $language = '';
            }
        }
        if ($language !== '') {
            $state = ['term_id' => (int) $term_id, 'language' => $language];
        }
    }
    boblift_term_slug_state($state);
    return $term_id;
}, 5);

/** True when the other terms of $taxonomy holding $slug all have a language and none of them has $language. */
function boblift_term_slug_free_in_language(string $slug, string $taxonomy, string $language, int $term_id): bool
{
    global $wpdb;
    $holders = $wpdb->get_col($wpdb->prepare(
        "SELECT t.term_id FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id WHERE t.slug = %s AND tt.taxonomy = %s AND t.term_id <> %d",
        $slug, $taxonomy, $term_id
    ));
    foreach ($holders as $holder) {
        $holder_language = (string) pll_get_term_language((int) $holder, 'slug');
        if ($holder_language === '' || $holder_language === $language) {
            return false;
        }
    }
    return true;
}

// Remembers the slug as given, before Polylang (priority 10) may add its language suffix.
add_filter('pre_term_slug', static function ($slug) {
    $state = boblift_term_slug_state();
    if ($state !== []) {
        $state['given'] = is_string($slug) ? $slug : '';
        boblift_term_slug_state($state);
    }
    return $slug;
}, 9);

// Keeps the given slug (dropping Polylang's suffix, if added) when only other languages hold it.
add_filter('pre_term_slug', static function ($slug, $taxonomy) {
    $state = boblift_term_slug_state();
    $given = (string) ($state['given'] ?? '');
    if ($given === '' || !is_string($slug) || !in_array($slug, [$given, $given . '-' . $state['language']], true)
        || !boblift_term_slug_free_in_language($given, (string) $taxonomy, $state['language'], $state['term_id'])) {
        return $slug;
    }
    $state['slug'] = $given;
    boblift_term_slug_state($state);
    return $given;
}, 11, 2);

// wp_update_term() applies this filter right before its duplicate check, after every early return, so only that lookup is armed.
add_filter('wp_update_term_parent', static function ($parent, $term_id) {
    $state = boblift_term_slug_state();
    if (isset($state['slug']) && (int) $term_id === $state['term_id']) {
        $state['armed'] = true;
        boblift_term_slug_state($state);
    } else {
        boblift_term_slug_state([]);
    }
    return $parent;
}, 10, 2);

// The duplicate check (get_term_by) then only looks in the term's own language; the state is spent on that one lookup.
add_filter('get_terms_args', static function (array $args) {
    $state = boblift_term_slug_state();
    if (!empty($state['armed']) && ($args['slug'] ?? null) === $state['slug'] && (int) ($args['number'] ?? 0) === 1 && ($args['get'] ?? '') === 'all') {
        $args['lang'] = $state['language'];
        boblift_term_slug_state([]);
    }
    return $args;
}, 5);

add_action('edit_terms', static function () {
    boblift_term_slug_state([]);
});

/**
 * A category address with a generated slug (/ru/marine-crane-ru-61, /fr/company-news-fr-11.html) answers with a 301 to the
 * category that now holds the plain slug in that language, so links saved before the rename keep working.
 */
function boblift_redirect_generated_term_slug(): void
{
    if (!is_404() || !function_exists('pll_languages_list') || !function_exists('boblift_request_url_language')) {
        return;
    }
    $language = boblift_request_url_language();
    if ($language === '' || $language === (string) pll_default_language()) {
        return;
    }
    $segment = basename((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
    if (!preg_match('/^(.+)-' . preg_quote($language, '/') . '(?:-\d+)?(?:\.html)?$/', $segment, $matches)) {
        return;
    }
    foreach (['product_category', 'category'] as $taxonomy) {
        $terms = taxonomy_exists($taxonomy) ? get_terms(['taxonomy' => $taxonomy, 'slug' => $matches[1], 'hide_empty' => false, 'number' => 1, 'lang' => $language]) : [];
        if (is_array($terms) && $terms && $terms[0] instanceof WP_Term) {
            $link = get_term_link($terms[0]);
            if (!is_wp_error($link)) {
                $query = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);
                wp_safe_redirect($link . ($query !== '' ? (str_contains($link, '?') ? '&' : '?') . $query : ''), 301);
                exit;
            }
        }
    }
}
add_action('template_redirect', 'boblift_redirect_generated_term_slug', 1);

/** A news filter saved with a generated category slug (company-news-ru-11) means the category now called company-news. */
function boblift_plain_term_slug(string $slug, string $language): string
{
    return $language !== '' ? (string) preg_replace('/-' . preg_quote($language, '/') . '(?:-\d+)?$/', '', $slug) : $slug;
}

function boblift_migrate_shared_case_slugs(): void
{
    if (get_option('boblift_case_slug_version') === '1'
        || !function_exists('pll_get_post_language') || !function_exists('pll_get_post')) {
        return;
    }
    // Run only on a request that submits nothing (and with the metabox saver unhooked), so a case edit form posted as
    // the first admin request after deployment is never written onto the translated cases being renamed.
    if (!empty($_POST) || (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') || wp_doing_ajax()) {
        return;
    }
    global $wpdb;
    $cases = $wpdb->get_results("SELECT ID, post_name FROM {$wpdb->posts} WHERE post_type = 'case' AND post_status NOT IN ('trash', 'auto-draft')");
    if ($cases === null || $wpdb->last_error !== '') {
        return;
    }
    foreach ($cases as $case) {
        $language = pll_get_post_language((int) $case->ID);
        if (!$language || $language === 'en' || $case->post_name === '') {
            continue;
        }
        $english_id = (int) pll_get_post((int) $case->ID, 'en');
        $english = $english_id ? get_post($english_id) : null;
        if (!$english instanceof WP_Post || $english->post_type !== 'case' || $english->post_name !== $case->post_name) {
            continue;
        }
        $meta_saver = has_action('save_post', 'boblift_save_meta_boxes');
        if ($meta_saver !== false) {
            remove_action('save_post', 'boblift_save_meta_boxes', $meta_saver);
        }
        $result = wp_update_post([
            'ID' => (int) $case->ID,
            'post_name' => $case->post_name . '-' . $language,
        ], true);
        if ($meta_saver !== false) {
            add_action('save_post', 'boblift_save_meta_boxes', $meta_saver);
        }
        if (is_wp_error($result) || !$result) {
            return;
        }
    }
    flush_rewrite_rules(false);
    update_option('boblift_case_slug_version', '1', false);
}
add_action('admin_init', 'boblift_migrate_shared_case_slugs');

/**
 * The language the requested address belongs to, in every Polylang URL mode: the directory (/ja/...), the subdomain
 * (ja.example.com) or a language domain, falling back to the default language there; in content mode only an explicit
 * ?lang= counts. "" when the address does not say (or without Polylang).
 */
function boblift_request_url_language(): string
{
    if (!function_exists('pll_languages_list') || !function_exists('pll_default_language')) {
        return '';
    }
    $options = (array) get_option('polylang', []);
    $languages = array_map('strval', (array) pll_languages_list(['fields' => 'slug']));
    $host = strtolower((string) parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST));
    switch ((int) ($options['force_lang'] ?? 0)) {
        case 1:
            $path = '/' . ltrim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
            $base = function_exists('boblift_language_path_base') ? boblift_language_path_base() : '';
            foreach ($languages as $slug) {
                if (preg_match('#^/' . preg_quote($base . $slug, '#') . '(/|$)#', $path)) {
                    return $slug;
                }
            }
            break;
        case 2:
            $label = explode('.', $host)[0] ?? '';
            if (in_array($label, $languages, true)) {
                return $label;
            }
            break;
        case 3:
            foreach ((array) ($options['domains'] ?? []) as $slug => $url) {
                if ($host !== '' && strtolower((string) parse_url((string) $url, PHP_URL_HOST)) === $host && in_array((string) $slug, $languages, true)) {
                    return (string) $slug;
                }
            }
            break;
        default:
            // Language set from the content: the address says nothing unless it carries ?lang=.
            $lang = isset($_GET['lang']) ? sanitize_key(wp_unslash((string) $_GET['lang'])) : '';
            return in_array($lang, $languages, true) ? $lang : '';
    }
    return (string) pll_default_language();
}

/**
 * Case URLs are language-aware: before boblift_migrate_shared_case_slugs() a translated case shared its English slug, so
 * an old /ja/<slug>.html (or ja.<host>/<slug>.html) now matches the English case. When the URL's language differs from the language of the
 * case it resolved to and that case has a published translation in the URL's language, send the visitor there (301).
 */
function boblift_redirect_case_to_url_language(): void
{
    if (!is_singular('case') || !function_exists('pll_get_post_language') || !function_exists('pll_get_post')) {
        return;
    }
    // Until the slug migration (admin_init) has run, translations still share the English permalink: redirecting would loop.
    if (get_option('boblift_case_slug_version') !== '1') {
        return;
    }
    $post = get_queried_object();
    if (!$post instanceof WP_Post) {
        return;
    }
    $url_language = boblift_request_url_language();
    $post_language = (string) pll_get_post_language($post->ID);
    if ($url_language === '' || $post_language === '' || $url_language === $post_language) {
        return;
    }
    $translation = (int) pll_get_post($post->ID, $url_language);
    if ($translation <= 0 || $translation === $post->ID || get_post_status($translation) !== 'publish') {
        return;
    }
    $target = get_permalink($translation);
    if (!is_string($target) || $target === '') {
        return;
    }
    // Keep campaign parameters, but not WordPress routing ones: /?post_type=case&name=<slug> would resolve the English case
    // again on the translated address and loop.
    $query = wp_unslash($_GET);
    $query = is_array($query) ? $query : [];
    foreach (['p', 'page_id', 'name', 'pagename', 'post_type', 'case', 'preview', 'preview_id'] as $routing_key) {
        unset($query[$routing_key]);
    }
    // Never redirect to the address that was requested (same host and path), whatever state the slugs are in.
    $requested_path = (string) wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    $requested_host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $target_host = strtolower((string) wp_parse_url($target, PHP_URL_HOST)) . (wp_parse_url($target, PHP_URL_PORT) ? ':' . wp_parse_url($target, PHP_URL_PORT) : '');
    if (untrailingslashit($requested_path) === untrailingslashit((string) wp_parse_url($target, PHP_URL_PATH)) && ($target_host === '' || $target_host === $requested_host)) {
        return;
    }
    $query_string = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    wp_safe_redirect($target . ($query_string !== '' ? (str_contains($target, '?') ? '&' : '?') . $query_string : ''), 301);
    exit;
}
add_action('template_redirect', 'boblift_redirect_case_to_url_language', 2);

/**
 * A case that is no longer published (moved to draft or deleted) sends its address to the case list in the URL's language
 * (301), instead of a 404 or WordPress guessing a similarly named case in another language. An old slug of a case that is
 * still published goes to that case instead.
 */
function boblift_redirect_missing_case_to_list(): void
{
    if (!is_404()) {
        return;
    }
    $path = '/' . ltrim((string) wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH), '/');
    $base = function_exists('boblift_language_path_base') ? boblift_language_path_base() : '';
    $languages = function_exists('pll_languages_list') ? array_map('strval', (array) pll_languages_list(['fields' => 'slug'])) : [];
    $prefix = $languages ? '(?:(?:' . implode('|', array_map(static fn(string $slug): string => preg_quote($slug, '#'), $languages)) . ')/)?' : '';
    if (!preg_match('#^/' . preg_quote($base, '#') . $prefix . 'cases/[^/]+/?$#', $path)) {
        return;
    }
    $language = boblift_request_url_language();
    // A published case whose slug was changed (WordPress keeps the old one in _wp_old_slug) moves to its new address,
    // preferably the one in the URL's language. This runs before core's own old-slug redirect, so it has to look itself.
    $old_slug = sanitize_title(rawurldecode(basename(untrailingslashit($path))));
    $moved = $old_slug === '' ? [] : get_posts(['post_type' => 'case', 'post_status' => 'publish', 'posts_per_page' => 10, 'fields' => 'ids',
        'lang' => '', 'meta_key' => '_wp_old_slug', 'meta_value' => $old_slug, 'orderby' => 'ID', 'order' => 'ASC']);
    if ($moved) {
        $url_language = $language !== '' ? $language : (function_exists('pll_default_language') ? (string) pll_default_language() : '');
        $same_language = array_values(array_filter($moved, static fn(int $id): bool => function_exists('pll_get_post_language') && pll_get_post_language($id) === $url_language));
        wp_safe_redirect(get_permalink($same_language[0] ?? $moved[0]), 301);
        exit;
    }
    wp_safe_redirect(boblift_localized_url(boblift_case_archive_url(), $language !== '' ? $language : null), 301);
    exit;
}
add_action('template_redirect', 'boblift_redirect_missing_case_to_list', 1);

// English is the hidden default language, so its URL is the x-default alternate on every translated page
// (Polylang adds x-default only on the front page, and only when the default language shows in the URL).
add_filter('pll_rel_hreflang_attributes', static function (array $hreflangs): array {
    if (!isset($hreflangs['x-default']) && isset($hreflangs['en'])) {
        $hreflangs['x-default'] = $hreflangs['en'];
    }
    return $hreflangs;
});
