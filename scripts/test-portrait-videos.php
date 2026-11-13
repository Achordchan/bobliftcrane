<?php
// php scripts/test-portrait-videos.php: which video figures in a story are marked as portrait (bl-video-vertical).
function add_filter(...$args) {}
$src = file_get_contents(__DIR__ . '/../inc/setup.php');
preg_match('/function boblift_mark_portrait_videos.*?\n}\n/s', $src, $m);
eval($m[0]);
$failed = 0;
$cases = [
    ['<figure class="wp-block-video"><video width="720" height="1280" src="a.mp4"></video><figcaption>c</figcaption></figure>', true],
    ['<figure class="wp-block-video"><video style="aspect-ratio:9/16" src="a.mp4"></video></figure>', true],
    ['<figure class="wp-block-video"><video height="1080" style="aspect-ratio: 1920 / 1080;" width="1920" src="a.mp4"></video></figure>', false],
    ['<figure class="wp-block-video"><video src="a.mp4"></video></figure>', false],
    ['<figure class="wp-block-video"><video style="aspect-ratio:0.5625" src="a.mp4"></video></figure>', true],
    ['<figure class="wp-block-video"><video style="aspect-ratio: 1.7778" src="a.mp4"></video></figure>', false],
    ['<figure class="wp-block-video"><video style="aspect-ratio: .5625" src="a.mp4"></video></figure>', true],
    ["<figure class='wp-block-video'><video width=\"720\" height=\"1280\"></video></figure>", true],
    ['<figure id="v" class="wp-block-video aligncenter" style="width:340px"><video width="720" height="1280"></video></figure>', true],
    ['<figure class="wp-block-video"><video width="720" height="1280"></video><video width="720" height="1280"></video></figure>', false],
];
foreach ($cases as [$html, $portrait]) {
    $out = boblift_mark_portrait_videos($html);
    if (substr_count($out, 'bl-video-vertical') !== ($portrait ? 1 : 0)) { $failed++; echo "wrong: $html\n"; }
}
$already = '<figure class="wp-block-video bl-video-vertical" style="max-width:340px"><video width="720" height="1280"></video></figure>';
if (boblift_mark_portrait_videos($already) !== $already) { $failed++; echo "already marked without a poster should stay as is\n"; }
// The poster is copied in front of the video as a decorative <img> (it survives wp_kses_post), only once.
$withPoster = boblift_mark_portrait_videos('<figure class="wp-block-video"><video width="720" height="1280" poster="https://x.test/p.jpg?a=1&#038;b=2"></video></figure>');
if (!str_contains($withPoster, '<img class="bl-video-backdrop" src="https://x.test/p.jpg?a=1&#038;b=2" alt="" aria-hidden="true" loading="lazy" decoding="async"><video')) { $failed++; echo "poster backdrop missing: $withPoster\n"; }
$styled = boblift_mark_portrait_videos('<figure class="wp-block-video bl-video-vertical" style="max-width:340px"><video width="720" height="1280" poster="https://x.test/p.jpg"></video></figure>');
if (substr_count($styled, 'bl-video-backdrop') !== 1 || !str_contains($styled, 'style="max-width:340px"') || substr_count($styled, 'bl-video-vertical') !== 1) { $failed++; echo "already marked figure should only gain the backdrop: $styled\n"; }
if (boblift_mark_portrait_videos($styled) !== $styled) { $failed++; echo "running twice should change nothing\n"; }
$odd = boblift_mark_portrait_videos('<figure class="wp-block-video"><video width="720" height="1280" poster="https://x.test/a b.jpg"></video></figure>');
if (str_contains($odd, 'bl-video-backdrop')) { $failed++; echo "unsafe poster address should be skipped: $odd\n"; }
$landscape = '<figure class="wp-block-video"><video width="1920" height="1080" poster="https://x.test/p.jpg"></video></figure>';
if (boblift_mark_portrait_videos($landscape) !== $landscape) { $failed++; echo "landscape video should stay as is\n"; }
echo $failed ? "$failed failed\n" : "ok\n";
exit($failed ? 1 : 0);
