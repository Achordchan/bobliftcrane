<?php
// php scripts/test-category-video.php: the category film's stored JSON, its card and its VideoObject, without WordPress.
define('ABSPATH', __DIR__);
class WP_Term { public int $term_id = 7; public string $slug = 'forklift-fly-jib-cranes'; }
function add_action(...$args) {}
function sanitize_text_field($v) { return trim(preg_replace('/\s+/', ' ', strip_tags((string) $v))); }
function esc_url_raw($v) { return $v; }
function esc_url($v) { return htmlspecialchars((string) $v, ENT_QUOTES); }
function esc_attr($v) { return htmlspecialchars((string) $v, ENT_QUOTES); }
function esc_html($v) { return htmlspecialchars((string) $v, ENT_QUOTES); }
function wp_json_encode($v, $f = 0) { return json_encode($v, $f); }
function boblift_icon($n, $c = '') { return '<svg></svg>'; }
function boblift_localized_static_text($t) { return $t; }
function boblift_site_ui($section) { return ['pdfLabel' => 'PDF', 'languageLabel' => 'EN', 'pagesLabel' => '{count} pages', 'downloadLabel' => 'Download PDF', 'downloadTitleTemplate' => 'Download {title} as PDF']; }
function boblift_brochure_quote_button(array $item, array $args, string $class): string { return '<button data-inquiry-modal-open data-quote-for="' . $item['title'] . '">Get a quote for this range</button>'; }
$meta = '';
function get_term_meta($id, $key, $single) { global $meta; return $meta; }
require __DIR__ . '/../inc/category-video.php';
$failed = 0;
$check = static function (string $name, bool $ok) use (&$failed): void {
    echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n";
    $failed += $ok ? 0 : 1;
};
$full = [
    'src' => 'https://videos.files.wordpress.com/x/film-en.mp4', 'youtube' => '', 'poster' => 'https://bobliftcrane.com/p.jpg',
    'title' => 'Forklift fly jib crane on <b>video</b>', 'text' => 'Factory footage.', 'points' => ['One', '', 'Two', 'Three', 'Four', 'Five'],
    'duration' => 66, 'upload_date' => '2026-11-04', 'extra' => 'dropped',
];
$saved = json_decode(boblift_category_video_sanitize(json_encode($full)), true);
$check('keeps a full film', is_array($saved) && $saved['src'] === $full['src'] && $saved['title'] === 'Forklift fly jib crane on video');
$check('drops unknown keys', !isset($saved['extra']));
$check('keeps up to four non-empty points', $saved['points'] === ['One', 'Two', 'Three', 'Four']);
$check('needs a poster', boblift_category_video_sanitize(json_encode(['poster' => ''] + $full)) === '');
$check('needs a title', boblift_category_video_sanitize(json_encode(['title' => ''] + $full)) === '');
$photo = json_decode(boblift_category_video_sanitize(json_encode(['src' => '', 'alt' => 'SQ8ZA6 on a <i>truck</i>'] + $full)), true);
$check('a poster without an mp4 or YouTube id is a still photo', is_array($photo) && $photo['src'] === '' && $photo['youtube'] === '' && $photo['alt'] === 'SQ8ZA6 on a truck' && !boblift_category_video_playable($photo));
$check('no VideoObject for a still photo', boblift_category_video_schema($photo) === []);
$check('a YouTube id alone is enough', json_decode(boblift_category_video_sanitize(json_encode(['src' => '', 'youtube' => 'dQw4w9WgXcQ'] + $full)), true)['youtube'] === 'dQw4w9WgXcQ');
$check('refuses http and script URLs', boblift_category_video_sanitize(json_encode(['src' => 'http://x/a.mp4'] + $full)) === '' && boblift_category_video_sanitize(json_encode(['poster' => 'javascript:alert(1)'] + $full)) === '');
$date = static fn(string $d): string => (string) json_decode(boblift_category_video_sanitize(json_encode(['upload_date' => $d] + $full)), true)['upload_date'];
$check('a bare day is stored as midnight Beijing time', $date('2026-11-04') === '2026-11-04T00:00:00+08:00');
$check('keeps a date-time with its offset', $date('2026-11-06T10:00:00+08:00') === '2026-11-06T10:00:00+08:00' && $date('2026-11-06T10:00+08:00') === '2026-11-06T10:00:00+08:00' && $date('2026-11-06T02:00:00Z') === '2026-11-06T02:00:00+00:00');
$check('refuses a date-time without an offset or an impossible day', $date('2026-11-06T10:00:00') === '' && $date('2026-11-06 10:00:00+08:00') === '' && $date('2026-02-30') === '' && $date('2026-11-06T25:00:00+08:00') === '');
$check('refuses a bad date', json_decode(boblift_category_video_sanitize(json_encode(['upload_date' => '4 Nov'] + $full)), true)['upload_date'] === '');
$check('empty or broken JSON clears the film', boblift_category_video_sanitize('') === '' && boblift_category_video_sanitize('{nope') === '' && boblift_category_video_sanitize('"x"') === '');
$check('durations', boblift_category_video_iso_duration(66) === 'PT1M6S' && boblift_category_video_iso_duration(60) === 'PT1M' && boblift_category_video_iso_duration(3605) === 'PT1H5S' && boblift_category_video_iso_duration(0) === '');
$schema = boblift_category_video_schema($saved);
$check('VideoObject', $schema['@type'] === 'VideoObject' && $schema['contentUrl'] === $full['src'] && $schema['duration'] === 'PT1M6S' && $schema['uploadDate'] === '2026-11-04T00:00:00+08:00');
$check('a day stored before the fix still gets a time zone', boblift_category_video_schema(['upload_date' => '2026-10-29'] + $saved)['uploadDate'] === '2026-10-29T00:00:00+08:00');
$check('no VideoObject without an upload date', boblift_category_video_schema(['upload_date' => ''] + $saved) === []);
$meta = '';
ob_start(); boblift_render_category_video(new WP_Term()); $html = ob_get_clean();
$check('no card without a film', trim($html) === '');
$meta = json_encode($saved);
ob_start(); boblift_render_category_video(new WP_Term()); $html = ob_get_clean();
$check('card uses the in-place player', str_contains($html, 'data-inline-videos') && str_contains($html, 'data-inline-video-play') && str_contains($html, 'data-video-player="local"'));
$check('without an intro the film\'s title, text and points stand in', str_contains($html, '>Forklift fly jib crane on video</h2>') && str_contains($html, '>Factory footage.</p>') && substr_count($html, '<li ') === 4);
$check('block carries the category intro marker', str_contains($html, 'data-category-intro="forklift-fly-jib-cranes"') && str_contains($html, 'aria-labelledby="category-intro-forklift-fly-jib-cranes"'));
$check('card prints the VideoObject', str_contains($html, '"@type":"VideoObject"'));
$check('the page prints the upload date with its time zone', str_contains($html, '"uploadDate":"2026-11-04T00:00:00+08:00"'));
$brochure = ['key' => 'forklift', 'title' => 'Forklift Fly Jib Cranes Brochure', 'url' => 'https://x/b.pdf', 'file' => 'b.pdf', 'cover' => 'https://x/c.webp', 'pages' => 8, 'size' => '2.5 MB'];
$check('no brochure band unless one is passed', !str_contains($html, 'data-category-video-brochure'));
ob_start(); boblift_render_category_video(new WP_Term(), $brochure); $html = ob_get_clean();
$check('brochure band: title, chips, download and quote', str_contains($html, 'data-category-video-brochure') && str_contains($html, '>Forklift Fly Jib Cranes Brochure<span') && str_contains($html, 'PDF · EN · 8 pages · 2.5 MB') && substr_count($html, 'download="b.pdf"') === 2 && str_contains($html, 'data-quote-for="Forklift Fly Jib Cranes Brochure"'));
$check('brochure band keeps the #brochures anchor', str_contains($html, 'id="brochures"'));
$check('band sits inside the block, before the VideoObject', strpos($html, 'data-category-video-brochure') < strpos($html, 'application/ld+json') && strrpos($html, '</section>') > strpos($html, 'data-category-video-brochure'));
$intro = ['slug' => 'forklift-fly-jib-cranes', 'title' => 'About our forklift fly jib cranes', 'paragraphs' => ['One.', '', 'Two.', 'Three.']];
ob_start(); boblift_render_category_video(new WP_Term(), $brochure, $intro); $html = ob_get_clean();
$check('intro: its heading and paragraphs replace the film text', str_contains($html, '>About our forklift fly jib cranes</h2>') && substr_count($html, '<p class=') === 4 && !str_contains($html, '<li ') && !str_contains($html, '>Factory footage.</p>'));
$check('intro: heading above both columns, film on the left, text and strip on the right', strpos($html, '</h2>') < strpos($html, 'class="mt-6 grid') && strpos($html, 'class="mt-6 grid') < strpos($html, 'class="boblift-category-film ') && strpos($html, 'data-inline-videos') < strpos($html, 'class="boblift-category-film-text') && strpos($html, 'class="boblift-category-film-text') < strpos($html, '>One.</p>') && strpos($html, '>Three.</p>') < strpos($html, 'data-category-video-brochure'));
$check('film frame and stage carry the classes that size them to the text column', str_contains($html, 'class="boblift-category-film-frame ') && str_contains($html, 'class="boblift-category-film-stage relative aspect-video'));
$check('the film keeps its own title for the player and VideoObject', str_contains($html, 'aria-label="Forklift fly jib crane on video"') && str_contains($html, '"name":"Forklift fly jib crane on video"'));
$meta = json_encode($photo);
ob_start(); boblift_render_category_video(new WP_Term(), $brochure, $intro); $html = ob_get_clean();
$check('photo: same block, the photo in the film\'s place with its alt and no player', str_contains($html, 'data-category-photo') && str_contains($html, 'alt="SQ8ZA6 on a truck"') && !str_contains($html, 'data-inline-videos') && !str_contains($html, 'data-inline-video-play') && strpos($html, '</h2>') < strpos($html, 'data-category-photo') && strpos($html, 'data-category-photo') < strpos($html, '>One.</p>'));
$check('photo: no VideoObject, brochure strip kept', !str_contains($html, 'application/ld+json') && str_contains($html, 'data-category-video-brochure'));
exit($failed ? 1 : 0);
