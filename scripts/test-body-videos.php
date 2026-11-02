<?php
// php scripts/test-body-videos.php: which films in a product's body move to the video column and which stay in the copy.
define('ABSPATH', __DIR__);
function wp_strip_all_tags($s) { return trim(strip_tags($s)); }
function add_action(...$args) {}
function add_filter(...$args) {}
require __DIR__ . '/../inc/video-library.php';
$failed = 0;
$V='<video poster="/p.jpg" src="https://v/%s.mp4"></video>';
$move=[ '<figure class="wp-block-video">'.sprintf($V,'plain').'</figure>', "<figure class=\"wp-block-video bl\" style=\"x\">\n".sprintf($V,'html')."\n<figcaption style=\"c\">Cap <em>x</em></figcaption>\n</figure>", '<figure class="wp-block-video"><video controls playsinline preload="none" poster="https://b/p.jpg?a=1&#038;b=2" src="https://v/live.mp4"></video><figcaption class="wp-element-caption">SQ (0:55)</figcaption></figure>'];
$stay=['<figure id="a" class="wp-block-video">'.sprintf($V,'fid').'</figure>','<figure class="wp-block-video">'.sprintf($V,'one').sprintf($V,'two').'</figure>','<figure class="wp-block-video"><video poster="/p.jpg" src="https://v/t.mp4"><track src="c.vtt"></video></figure>','<figure class="wp-block-video"><video poster="/p.jpg"><source src="https://v/s.mp4"></video></figure>','<figure class="wp-block-video"><video src="https://v/np.mp4"></video></figure>','<figure class="wp-block-video"><video poster="/p.jpg" src="c/r.mp4"></video></figure>','<figure class="wp-block-video"><video poster="../p.jpg" src="https://v/r.mp4"></video></figure>','<figure class="wp-block-video">'.sprintf($V,'l').'<figcaption>See <a href="x">y</a></figcaption></figure>','<figure class="wp-block-video"><video id="v" poster="/p.jpg" src="https://v/vid.mp4"></video></figure>','<figure class="wp-block-video">'.sprintf($V,'c').'<figcaption id="c">t</figcaption></figure>','<figure class="wp-block-video"><p>x</p>'.sprintf($V,'p').'</figure>'];
foreach ($move as $m) {
    [$b, $v] = boblift_split_body_videos("A$m", 'T');
    if ($b !== 'A' || count($v) !== 1) { $failed++; echo "should move: $m\n"; }
}
foreach ($stay as $m) {
    [$b, $v] = boblift_split_body_videos("A$m", 'T');
    if ($b !== "A$m" || $v) { $failed++; echo "should stay: $m\n"; }
}
[$b, $v] = boblift_split_body_videos($move[0] . '<p>m</p>' . $move[0], 'T');
if ($b !== '<p>m</p>' || $v[0]['label'] !== 'T 1' || $v[1]['label'] !== 'T 2') { $failed++; echo "uncaptioned films should be numbered\n"; }
echo $failed ? "$failed failed\n" : "ok\n";
exit($failed ? 1 : 0);
