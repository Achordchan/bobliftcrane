<?php
// Checks the llms.txt article list without WordPress: php scripts/test-llms-articles.php
// get_posts() below applies the same arguments the way WordPress and Polylang do (status, password, language, order,
// limit), over 520 English articles, a Spanish translation and a password-protected article.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
define('ABSPATH', __DIR__);
class WP_Post
{
    public function __construct(public int $ID, public string $post_date, public string $lang = 'en', public string $post_status = 'publish', public string $post_password = '', public string $post_type = 'post')
    {
    }
}
function add_action(...$args): void
{
}
function add_filter(...$args): void
{
}
function wp_strip_all_tags(string $text): string
{
    return strip_tags($text);
}
$GLOBALS['posts'] = [];
for ($i = 1; $i <= 520; $i++) {
    // Two articles per day, so dates tie and the ID has to break them.
    $GLOBALS['posts'][] = new WP_Post($i, date('Y-m-d 10:00:00', strtotime('2024-01-01') + intdiv($i, 2) * 86400));
}
$GLOBALS['posts'][] = new WP_Post(900, '2026-11-20 10:00:00', 'es');
$GLOBALS['posts'][] = new WP_Post(901, '2026-11-21 10:00:00', 'en', 'publish', 'secret');
$GLOBALS['posts'][] = new WP_Post(902, '2026-11-22 10:00:00', 'en', 'draft');
$GLOBALS['last_args'] = [];
function get_posts(array $args): array
{
    $GLOBALS['last_args'] = $args;
    if (isset($args['post__in'])) {
        // Loading a chunk by ID, in the given order: lang '' means all languages.
        $GLOBALS['largest_page'] = max($GLOBALS['largest_page'] ?? 0, count($args['post__in']));
        $by_id = [];
        foreach ($GLOBALS['posts'] as $p) {
            if (in_array($p->ID, $args['post__in'], true) && $p->post_status === $args['post_status'] && $p->post_type === $args['post_type'] && ($args['has_password'] !== false || $p->post_password === '')) {
                $by_id[$p->ID] = $p;
            }
        }
        return array_values(array_filter(array_map(static fn(int $id) => $by_id[$id] ?? null, $args['post__in'])));
    }
    $posts = array_filter($GLOBALS['posts'], static fn(WP_Post $p): bool => $p->post_type === $args['post_type']
        && $p->post_status === $args['post_status']
        && (!array_key_exists('has_password', $args) || $args['has_password'] !== false || $p->post_password === '')
        && (!empty($GLOBALS['ignore_lang']) || !isset($args['lang']) || $p->lang === $args['lang'])
        && (!empty($GLOBALS['ignore_tax']) || !isset($args['tax_query']) || $p->lang === $args['tax_query'][0]['terms']));
    $order = $args['orderby'] ?? ['date' => 'DESC'];
    usort($posts, static function (WP_Post $a, WP_Post $b) use ($order): int {
        foreach ($order as $field => $dir) {
            $cmp = $field === 'ID' ? $a->ID <=> $b->ID : strcmp($a->post_date, $b->post_date);
            if ($cmp !== 0) {
                return strtoupper($dir) === 'DESC' ? -$cmp : $cmp;
            }
        }
        return 0;
    });
    $per_page = $args['posts_per_page'] ?? 5;
    if ($per_page !== -1) {
        $posts = array_slice($posts, (max(1, $args['paged'] ?? 1) - 1) * $per_page, $per_page);
    }
    if (($args['fields'] ?? '') === 'ids') {
        // A post published right after the ID list was read (as during a bulk publish).
        foreach ($GLOBALS['publish_after_ids'] ?? [] as $late) {
            $GLOBALS['posts'][] = $late;
        }
        $GLOBALS['publish_after_ids'] = [];
        $GLOBALS['ids_args'] = $args;
        $GLOBALS['ids_returned'] = count($posts);
        return array_map(static fn(WP_Post $p): int => $p->ID, array_values($posts));
    }
    return array_values($posts);
}
function taxonomy_exists(string $taxonomy): bool
{
    return $taxonomy === 'language';
}
// Polylang's own record of each post's language.
function pll_get_post_language(int $id): string|false
{
    foreach ($GLOBALS['posts'] as $p) {
        if ($p->ID === $id) {
            return $p->lang;
        }
    }
    return false;
}
$GLOBALS['runtime_flushes'] = 0;
function wp_cache_supports(string $feature): bool
{
    return $feature === 'flush_runtime';
}
function wp_cache_flush_runtime(): bool
{
    $GLOBALS['runtime_flushes']++;
    return true;
}
function get_the_title(WP_Post $post): string
{
    return 'Article ' . $post->ID;
}
function get_permalink(WP_Post $post): string
{
    return match ($post->ID) {
        300 => 'https://bobliftcrane.com/news-show-89.html',
        301 => 'https://bobliftcrane.com/language/en/news-show-273.html',
        302 => 'https://bobliftcrane.com/guides/news-show-89.html',
        default => 'https://bobliftcrane.com/a' . $post->ID . '.html',
    };
}
// Polylang configured with the language/ base and a directory for English too, as boblift_strip_language_prefix() sees it.
function boblift_strip_language_prefix(string $path): string
{
    return preg_match('#^/language/(en|es|fr)(/|$)#', $path, $m) === 1 ? (substr($path, strlen('/language/' . $m[1])) ?: '/') : $path;
}
function get_option(string $name, $default = false)
{
    return $name === 'home' ? 'https://bobliftcrane.com' : $default;
}
function wp_parse_url(string $url, int $component = -1)
{
    return parse_url($url, $component);
}
function boblift_seo_post_description(WP_Post $post): string
{
    return 'About article ' . $post->ID;
}
require dirname(__DIR__) . '/inc/company-schema.php';

$failures = 0;
$check = static function (string $name, bool $ok) use (&$failures): void {
    echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n";
    $failures += $ok ? 0 : 1;
};
$lines = boblift_llms_txt_articles();
$has = static fn(int $id): bool => (bool) array_filter($lines, static fn(string $l): bool => str_contains($l, '(https://bobliftcrane.com/a' . $id . '.html)'));
$check('lists 500 articles, not the old 60', count($lines) === 500);
$check('old news held back for 301 or noindex is left out, with or without a language prefix (also under language/en/)', !array_filter($lines, static fn(string $l): bool => str_contains($l, '(https://bobliftcrane.com/news-show-89.html)') || str_contains($l, 'news-show-273')));
$check('an article with the same file name under another directory is kept', (bool) array_filter($lines, static fn(string $l): bool => str_contains($l, '(https://bobliftcrane.com/guides/news-show-89.html)')));
$check('held-back posts do not use up slots: the 500 are filled with older articles', $has(19));
$check('newest English article first, ID breaking the date tie', str_starts_with($lines[0], '- [Article 520](https://bobliftcrane.com/a520.html): About article 520'));
$pos = static fn(int $id): int|false => array_search(true, array_map(static fn(string $l): bool => str_contains($l, '(https://bobliftcrane.com/a' . $id . '.html)'), $lines), true);
$check('articles 519 and 518 share a date and the higher ID comes first', $pos(519) !== false && $pos(518) !== false && $pos(519) < $pos(518) && $pos(519) === 1);
$check('an older article past the old 60 is listed', $has(100) && $has(21));
$check('the oldest articles past 500 are left out', !$has(18) && !$has(1));
$check('a translation is left out', !$has(900));
$check('a password-protected article is left out', !$has(901));
$check('a draft is left out', !$has(902));
$check('posts are loaded in chunks of at most 100, the request-local cache dropped after each chunk', $GLOBALS['largest_page'] <= 100 && $GLOBALS['runtime_flushes'] >= 5);
$GLOBALS['publish_after_ids'] = [new WP_Post(950, '2026-11-24 10:00:00')];
$during = boblift_llms_txt_articles();
$check('an article published while the list is built neither repeats nor drops one', $during === $lines && count(array_unique($during)) === 500);
$GLOBALS['posts'] = array_values(array_filter($GLOBALS['posts'], static fn(WP_Post $p): bool => $p->ID !== 950));
$again = boblift_llms_txt_articles();
$check('the order is the same on every request', $again === $lines);
// If the query's language filter is not applied, 6,000 newer Spanish translations must not push English articles out.
for ($i = 1000; $i < 7000; $i++) {
    $GLOBALS['posts'][] = new WP_Post($i, '2026-11-23 10:00:00', 'es');
}
$GLOBALS['ignore_lang'] = true;
$taxed = boblift_llms_txt_articles();
$check('the ID query also constrains on Polylang\'s language taxonomy, so translations are left out by the database', ($GLOBALS['ids_args']['tax_query'][0] ?? []) === ['taxonomy' => 'language', 'field' => 'slug', 'terms' => 'en'] && $taxed === $lines && $GLOBALS['ids_returned'] === 520);
$GLOBALS['ignore_tax'] = true;
$mixed = boblift_llms_txt_articles();
$check('with no language constraint applied at all, translations are still dropped by their Polylang language', !array_filter($mixed, static fn(string $l): bool => preg_match('#/a(9\d\d|[1-6]\d\d\d)\.html#', $l) === 1));
$check('without the language filter, later pages are read until 500 English articles are listed', $mixed === $lines);
exit($failures === 0 ? 0 : 1);
