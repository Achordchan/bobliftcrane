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
    $posts = array_filter($GLOBALS['posts'], static fn(WP_Post $p): bool => $p->post_type === $args['post_type']
        && $p->post_status === $args['post_status']
        && (!array_key_exists('has_password', $args) || $args['has_password'] !== false || $p->post_password === '')
        && (!isset($args['lang']) || $p->lang === $args['lang']));
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
    return array_slice($posts, 0, $args['posts_per_page'] ?? 5);
}
function get_the_title(WP_Post $post): string
{
    return 'Article ' . $post->ID;
}
function get_permalink(WP_Post $post): string
{
    return match ($post->ID) {
        300 => 'https://bobliftcrane.com/news-show-89.html',
        301 => 'https://bobliftcrane.com/en/news-show-273.html',
        default => 'https://bobliftcrane.com/a' . $post->ID . '.html',
    };
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
$check('old news held back for 301 or noindex is left out, with or without a language prefix', !array_filter($lines, static fn(string $l): bool => str_contains($l, 'news-show-')));
$check('held-back posts do not use up slots: the 500 are filled with older articles', $has(19));
$check('newest English article first, ID breaking the date tie', str_starts_with($lines[0], '- [Article 520](https://bobliftcrane.com/a520.html): About article 520'));
$check('an older article past the old 60 is listed', $has(100) && $has(21));
$check('the oldest articles past 500 are left out', !$has(18) && !$has(1));
$check('a translation is left out', !$has(900));
$check('a password-protected article is left out', !$has(901));
$check('a draft is left out', !$has(902));
$again = boblift_llms_txt_articles();
$check('the order is the same on every request', $again === $lines);
exit($failures === 0 ? 0 : 1);
