<?php
// php scripts/test-video-backdrops.php: the blurred poster copy single.php adds behind a portrait video.
$src = file_get_contents(__DIR__ . '/../inc/setup.php');
preg_match('/function boblift_add_video_backdrops.*?\n}\n/s', $src, $m);
eval($m[0]);
$failed = 0;
$img = '<img class="bl-video-backdrop" src="https://x.test/p.jpg?a=1&amp;b=2" alt="" aria-hidden="true" loading="lazy" decoding="async">';
$portrait = '<figure class="wp-block-video bl-video-vertical" style="max-width:340px"><video width="720" height="1280" poster="https://x.test/p.jpg?a=1&amp;b=2"></video><figcaption>c</figcaption></figure>';
$out = boblift_add_video_backdrops($portrait);
if ($out !== '<figure class="wp-block-video bl-video-vertical" style="max-width:340px">' . $img . '<video width="720" height="1280" poster="https://x.test/p.jpg?a=1&amp;b=2"></video><figcaption>c</figcaption></figure>') { $failed++; echo "backdrop missing: $out\n"; }
if (boblift_add_video_backdrops($out) !== $out) { $failed++; echo "running twice should change nothing\n"; }
$single = "<figure class='wp-block-video bl-video-vertical'><video poster='https://x.test/p.jpg'></video></figure>";
if (substr_count(boblift_add_video_backdrops($single), 'bl-video-backdrop') !== 1) { $failed++; echo "single-quoted markup should get the backdrop\n"; }
$landscape = '<figure class="wp-block-video"><video width="1920" height="1080" poster="https://x.test/p.jpg"></video></figure>';
if (boblift_add_video_backdrops($landscape) !== $landscape) { $failed++; echo "landscape video should stay as is\n"; }
$noPoster = '<figure class="wp-block-video bl-video-vertical"><video width="720" height="1280"></video></figure>';
if (boblift_add_video_backdrops($noPoster) !== $noPoster) { $failed++; echo "no poster, no backdrop\n"; }
$odd = '<figure class="wp-block-video bl-video-vertical"><video poster="https://x.test/a b.jpg"></video></figure>';
if (boblift_add_video_backdrops($odd) !== $odd) { $failed++; echo "unsafe poster address should be skipped\n"; }
$two = $portrait . '<p>x</p>' . $portrait;
if (substr_count(boblift_add_video_backdrops($two), 'bl-video-backdrop') !== 2) { $failed++; echo "each portrait figure gets its own backdrop\n"; }
echo $failed ? "$failed failed\n" : "ok\n";
exit($failed ? 1 : 0);
