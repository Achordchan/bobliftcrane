<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Delivery records for customer feedback entries. An entry used to be a gallery with a model and a country; the page
 * around it is the same template on all 101 entries, so search engines saw near-duplicates. A delivery record is the
 * written part that is the entry's own: what the photos show, where the crane went and what the model is built for.
 *
 * The record is stored per language in _feedback_record ({"en": "<h2>…", "es": "…"}, limited HTML) with its search
 * title and description in _feedback_seo ({"en": {"title": "…", "description": "…"}}). Both are post meta of
 * wp/v2/feedback, so they are written through the REST API or the WordPress.com connector; the edit screen does not
 * touch them. A language without its own record keeps the gallery page and stays out of the index and the sitemap.
 */

/** Value of a per-language JSON meta for one language, without falling back to English. */
function boblift_feedback_record_value(int $post_id, string $key, string $language): mixed
{
    $values = json_decode((string) get_post_meta($post_id, $key, true), true);
    return is_array($values) ? ($values[$language] ?? null) : null;
}

/** The delivery record of an entry in a language ('' when that language has none). */
function boblift_feedback_record(int $post_id, string $language = ''): string
{
    $language = $language !== '' ? $language : boblift_current_language_slug();
    $language = $language !== '' ? $language : 'en';
    return trim((string) boblift_feedback_record_value($post_id, '_feedback_record', $language));
}

/** Search title or description of an entry in the current language ('' when not set). */
function boblift_feedback_seo_text(int $post_id, string $field): string
{
    $language = boblift_current_language_slug() ?: 'en';
    $seo = boblift_feedback_record_value($post_id, '_feedback_seo', $language);
    return is_array($seo) ? trim((string) ($seo[$field] ?? '')) : '';
}

function boblift_feedback_rest_language_map(mixed $value, callable $clean): string
{
    $decoded = is_string($value) ? json_decode($value, true) : $value;
    if (!is_array($decoded) || array_is_list($decoded) && $decoded !== []) {
        return '';
    }
    $out = [];
    foreach ($decoded as $language => $item) {
        $language = sanitize_key((string) $language);
        $item = $clean($item);
        if ($language !== '' && $item !== null && $item !== '' && $item !== []) {
            $out[$language] = $item;
        }
    }
    return $out ? (string) wp_json_encode($out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
}

function boblift_register_feedback_record_meta(): void
{
    $fields = [
        '_feedback_record' => static fn($value): string => boblift_feedback_rest_language_map($value, static fn($html): string => trim(wp_kses_post((string) $html))),
        '_feedback_seo' => static fn($value): string => boblift_feedback_rest_language_map($value, static function ($seo): ?array {
            if (!is_array($seo)) {
                return null;
            }
            return array_filter([
                'title' => sanitize_text_field((string) ($seo['title'] ?? '')),
                'description' => sanitize_text_field((string) ($seo['description'] ?? '')),
            ]);
        }),
    ];
    foreach ($fields as $key => $sanitize) {
        register_post_meta('feedback', $key, [
            'type' => 'string',
            'single' => true,
            'default' => '',
            'show_in_rest' => true,
            'sanitize_callback' => $sanitize,
            'auth_callback' => static fn($allowed, $meta_key, $post_id): bool => current_user_can('edit_post', (int) $post_id),
        ]);
    }
    // The gallery ("12,34,56", attachment IDs in display order; the first image is the cover) is written the same way,
    // so a new customer photo goes in without the edit screen. IDs that are not attachments are dropped.
    register_post_meta('feedback', '_feedback_media', [
        'type' => 'string',
        'single' => true,
        'default' => '',
        'show_in_rest' => true,
        'sanitize_callback' => static fn($value): string => implode(',', array_unique(array_filter(
            array_map('intval', is_array($value) ? $value : explode(',', (string) $value)),
            static fn(int $id): bool => $id > 0 && get_post_type($id) === 'attachment'
        ))),
        'auth_callback' => static fn($allowed, $meta_key, $post_id): bool => current_user_can('edit_post', (int) $post_id),
    ]);
    // Model and country are written the same way, so a new entry can be set up without the edit screen: the model as
    // plain text ("SQ5SA4") and the country per language ({"en": "Kenya", "zh": "肯尼亚"}), as the edit screen saves them.
    $plain = static fn($value): string => sanitize_text_field((string) $value);
    $identity = [
        '_feedback_model' => $plain,
        '_feedback_country' => static fn($value): string => boblift_feedback_rest_language_map($value, $plain),
    ];
    foreach ($identity as $key => $sanitize) {
        register_post_meta('feedback', $key, [
            'type' => 'string',
            'single' => true,
            'default' => '',
            'show_in_rest' => true,
            'sanitize_callback' => $sanitize,
            'auth_callback' => static fn($allowed, $meta_key, $post_id): bool => current_user_can('edit_post', (int) $post_id),
        ]);
    }
    add_post_type_support('feedback', 'custom-fields');
}
add_action('init', 'boblift_register_feedback_record_meta', 20);

// Core REST returns registered meta whatever the post password, so a protected entry keeps its gallery and record out of
// responses for anyone who cannot edit it, as protected cases, products and pages do (inc/case-rest.php).
add_filter('rest_prepare_feedback', 'boblift_hide_protected_case_rest_meta', 10, 2);

// Gallery-only entries share almost all of their text with the other 100; keep them out of the index until written up.
function boblift_feedback_record_robots(array $robots): array
{
    if (is_singular('feedback') && boblift_feedback_record(get_queried_object_id()) === '') {
        $robots['noindex'] = true;
        $robots['follow'] = true;
    }
    return $robots;
}
add_filter('wp_robots', 'boblift_feedback_record_robots', 20);

// The feedback sitemap lists the English URLs, so it keeps only the entries written up in English.
function boblift_feedback_record_sitemap_args(array $args, string $post_type): array
{
    if ($post_type !== 'feedback') {
        return $args;
    }
    $ids = get_posts(['post_type' => 'feedback', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_feedback_record', 'lang' => '']);
    $ids = array_values(array_filter(array_map('intval', $ids), static fn(int $id): bool => boblift_feedback_record($id, 'en') !== ''));
    $args['post__in'] = $ids ?: [0];
    return $args;
}
add_filter('wp_sitemaps_posts_query_args', 'boblift_feedback_record_sitemap_args', 10, 2);

// The live site serves Jetpack's sitemap (/sitemap-posts-feedback-1.xml), which ignores the core filter above.
function boblift_feedback_record_jetpack_sitemap_skip(bool $skip, $post): bool
{
    if ($skip || !is_object($post) || ($post->post_type ?? '') !== 'feedback') {
        return $skip;
    }
    return boblift_feedback_record((int) $post->ID, 'en') === '';
}
add_filter('jetpack_sitemap_skip_post', 'boblift_feedback_record_jetpack_sitemap_skip', 10, 2);

// Polylang links no translations for language-neutral feedback, so the languages that carry a record point at each
// other here; a language without a record is noindex and stays out of the set.
function boblift_feedback_record_hreflang(): void
{
    if (!is_singular('feedback') || !function_exists('pll_languages_list')) {
        return;
    }
    $post = get_queried_object();
    if (!$post instanceof WP_Post || boblift_feedback_record($post->ID) === '') {
        return;
    }
    $links = [];
    foreach ((array) pll_languages_list(['fields' => 'slug']) as $language) {
        $language = (string) $language;
        if (boblift_feedback_record($post->ID, $language) !== '') {
            $links[$language] = boblift_public_path_url('/feedback/' . $post->post_name, $language);
        }
    }
    if (count($links) < 2) {
        return;
    }
    if (isset($links['en'])) {
        $links['x-default'] = $links['en'];
    }
    foreach ($links as $language => $url) {
        echo '<link rel="alternate" href="' . esc_url(trailingslashit($url)) . '" hreflang="' . esc_attr($language) . '" />' . "\n";
    }
}
add_action('wp_head', 'boblift_feedback_record_hreflang', 2);

// The language switcher also asks Polylang for each language's version, finds no translation and falls back to that
// language's home page. Every entry answers at /<lang>/feedback/<slug>/, so the switcher stays on the same entry.
function boblift_feedback_record_translation_url($url, string $language)
{
    if (!is_singular('feedback')) {
        return $url;
    }
    $post = get_queried_object();
    return $post instanceof WP_Post ? trailingslashit(boblift_public_path_url('/feedback/' . $post->post_name, $language)) : $url;
}
add_filter('pll_translation_url', 'boblift_feedback_record_translation_url', 10, 2);

// Polylang prints hreflang links from the same URLs; on feedback they come from boblift_feedback_record_hreflang()
// instead, which leaves out the languages without a record (those are noindex).
function boblift_feedback_record_polylang_hreflang(array $hreflangs): array
{
    return is_singular('feedback') ? [] : $hreflangs;
}
add_filter('pll_rel_hreflang_attributes', 'boblift_feedback_record_polylang_hreflang', 20);

/**
 * A written-up entry is an article in its own right: its record's title and description, and the first customer photo,
 * become the social preview (Jetpack otherwise shows "Model · Country" and the site's generic image), and the page gets
 * an Article node signed by the record's author, with the customer photos as its images.
 */
function boblift_feedback_record_photos(WP_Post $post, int $limit = 3): array
{
    $photos = [];
    foreach (array_filter(array_map('intval', explode(',', (string) get_post_meta($post->ID, '_feedback_media', true)))) as $id) {
        $image = wp_attachment_is('image', $id) ? wp_get_attachment_image_src($id, 'full') : false;
        if (is_array($image) && (string) $image[0] !== '') {
            $photos[] = ['url' => (string) $image[0], 'width' => (int) $image[1], 'height' => (int) $image[2]];
        }
        if (count($photos) >= $limit) {
            break;
        }
    }
    return $photos;
}

function boblift_feedback_record_social_tags($tags)
{
    $post = get_queried_object();
    if (!is_array($tags) || !is_singular('feedback') || !$post instanceof WP_Post || post_password_required($post) || boblift_feedback_record($post->ID) === '') {
        return $tags;
    }
    $title = boblift_feedback_seo_text($post->ID, 'title');
    $description = boblift_feedback_seo_text($post->ID, 'description');
    if ($title !== '') {
        $tags['og:title'] = $title;
        $tags['twitter:text:title'] = $title;
        $tags['twitter:title'] = $title;
    }
    if ($description !== '') {
        $tags['og:description'] = $description;
        $tags['twitter:description'] = $description;
    }
    $photo = boblift_feedback_record_photos($post, 1)[0] ?? null;
    if ($photo) {
        $alt = $title !== '' ? (string) preg_replace('/\s*\|\s*BOB-LIFT$/', '', $title) : (string) get_the_title($post);
        $tags['og:image'] = $photo['url'];
        $tags['og:image:width'] = $photo['width'];
        $tags['og:image:height'] = $photo['height'];
        $tags['og:image:alt'] = $alt;
        $tags['twitter:image'] = $photo['url'];
        $tags['twitter:image:alt'] = $alt;
        $tags['twitter:card'] = 'summary_large_image';
    }
    $tags['og:type'] = 'article';
    return $tags;
}
// After the theme's other Open Graph filters (20, 30), so the record's values are the ones printed.
add_filter('jetpack_open_graph_tags', 'boblift_feedback_record_social_tags', 40);

function boblift_feedback_record_schema(): void
{
    $post = get_queried_object();
    if (!is_singular('feedback') || !$post instanceof WP_Post || post_password_required($post) || boblift_feedback_record($post->ID) === '') {
        return;
    }
    $home = untrailingslashit((string) get_option('home'));
    $url = (string) get_permalink($post);
    $title = (string) preg_replace('/\s*\|\s*BOB-LIFT$/', '', boblift_feedback_seo_text($post->ID, 'title'));
    $article_ui = boblift_site_ui('newsArticle');
    $person = [
        '@type' => 'Person',
        '@id' => $home . '/#achord-chan',
        'name' => trim((string) ($article_ui['authorName'] ?? '')) ?: 'Achord Chan',
        'jobTitle' => 'Product Operations Director',
        'url' => boblift_url('/about.html'),
        'image' => boblift_asset('images/authors/achord-chan.webp'),
        'worksFor' => ['@id' => boblift_organization_id()],
    ];
    $article = array_filter([
        '@type' => 'Article',
        '@id' => $url . '#article',
        'headline' => $title !== '' ? $title : (string) get_the_title($post),
        'description' => boblift_feedback_seo_text($post->ID, 'description'),
        'datePublished' => get_post_time('c', true, $post),
        'dateModified' => get_post_modified_time('c', true, $post),
        'inLanguage' => get_bloginfo('language'),
        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $url],
        // Named in full here as well as in the graph, so readers that do not resolve @id still see the author.
        'author' => $person,
        'publisher' => [
            '@type' => 'Organization',
            '@id' => boblift_organization_id(),
            'name' => 'BOB-LIFT',
            'url' => BOBLIFT_ORGANIZATION_URL,
            'logo' => boblift_organization_logo(),
        ],
        'image' => array_map(static fn(array $photo): array => ['@type' => 'ImageObject'] + $photo, boblift_feedback_record_photos($post)),
    ]);
    echo '<script type="application/ld+json">' . wp_json_encode(['@context' => 'https://schema.org', '@graph' => [$article, $person]], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . "</script>\n";
}
add_action('wp_head', 'boblift_feedback_record_schema', 4);

function boblift_feedback_record_author_meta(): void
{
    $post = get_queried_object();
    if (!is_singular('feedback') || !$post instanceof WP_Post || post_password_required($post) || boblift_feedback_record($post->ID) === '') {
        return;
    }
    $name = trim((string) (boblift_site_ui('newsArticle')['authorName'] ?? '')) ?: 'Achord Chan';
    echo '<meta name="author" content="' . esc_attr($name) . '">' . "\n";
}
add_action('wp_head', 'boblift_feedback_record_author_meta', 4);
