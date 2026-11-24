<?php
// php scripts/test-body-videos.php: which films in a product's body move to the video column and which stay in the copy.
define('ABSPATH', __DIR__);
function add_action(...$args) {}
function add_filter(...$args) {}
require __DIR__ . '/../inc/video-library.php';
$failed = 0;
$V='<video poster="/p.jpg" src="https://v/%s.mp4"></video>';
$move=['<figure class="wp-block-video"><video height="1080" style="aspect-ratio: 1920 / 1080;" width="1920" controls playsinline preload="none" poster="/p.jpg" src="https://v/b.mp4" aria-label="L"></video><figcaption class="wp-element-caption">SQ (0:55)</figcaption></figure>', '<figure class="wp-block-video">'.sprintf($V,'plain').'</figure>', "<figure class=\"wp-block-video bl\" style=\"x\">\n".sprintf($V,'html')."\n<figcaption style=\"c\">Cap &amp; x</figcaption>\n</figure>", '<figure class="wp-block-video"><video controls playsinline preload="none" poster="https://b/p.jpg?a=1&#038;b=2" src="https://v/live.mp4"></video><figcaption class="wp-element-caption">SQ (0:55)</figcaption></figure>'];
$stay=['<!-- <figure class="wp-block-video">'.sprintf($V,'cm').'</figure> -->','<figure class="wp-block-video"><video muted poster="/p.jpg" src="https://v/mu.mp4"></video></figure>','<figure class="wp-block-video"><video muted autoplay poster="/p.jpg" src="https://v/am.mp4"></video></figure>','<figure id=x class="wp-block-video">'.sprintf($V,'q').'</figure>','<figure class="wp-block-video"><video id=y poster="/p.jpg" src="https://v/u.mp4"></video></figure>','<figure class="wp-block-video">'.sprintf($V,'r').'<figcaption id=z>t</figcaption></figure>','<figure class="wp-block-video"><video data-x="1" poster="/p.jpg" src="https://v/d.mp4"></video></figure>','<figure class="wp-block-video">'.sprintf($V,'i').'<figcaption>Credit <img src="/l.png"></figcaption></figure>','<figure id="a" class="wp-block-video">'.sprintf($V,'fid').'</figure>','<figure class="wp-block-video">'.sprintf($V,'one').sprintf($V,'two').'</figure>','<figure class="wp-block-video"><video poster="/p.jpg" src="https://v/t.mp4"><track src="c.vtt"></video></figure>','<figure class="wp-block-video"><video poster="/p.jpg"><source src="https://v/s.mp4"></video></figure>','<figure class="wp-block-video"><video src="https://v/np.mp4"></video></figure>','<figure class="wp-block-video"><video poster="/p.jpg" src="c/r.mp4"></video></figure>','<figure class="wp-block-video"><video poster="../p.jpg" src="https://v/r.mp4"></video></figure>','<figure class="wp-block-video">'.sprintf($V,'l').'<figcaption>See <a href="x">y</a></figcaption></figure>','<figure class="wp-block-video"><video id="v" poster="/p.jpg" src="https://v/vid.mp4"></video></figure>','<figure class="wp-block-video">'.sprintf($V,'c').'<figcaption id="c">t</figcaption></figure>','<figure class="wp-block-video"><p>x</p>'.sprintf($V,'p').'</figure>'];
foreach ($move as $m) {
    [$b, $v] = boblift_split_body_videos("A$m", 'T');
    if ($b !== 'A' || count($v) !== 1) { $failed++; echo "should move: $m\n"; }
}
foreach ($stay as $m) {
    [$b, $v] = boblift_split_body_videos("A$m", 'T');
    if ($b !== "A$m" || $v) { $failed++; echo "should stay: $m\n"; }
}
// A loop film is an animation: it moves with or without muted and carries the flag; a word "loop" in a value is not the attribute.
foreach (['<video loop poster="/p.jpg" src="https://v/lp.mp4"></video>' => true, '<video controls playsinline preload="none" loop muted poster="/p.jpg" src="https://v/lm.mp4"></video>' => true, '<video title="Crane loop demo" aria-label="loop" poster="/p.jpg" src="https://v/tl.mp4"></video>' => false, sprintf($V, 'ord') => false] as $tag => $loop) {
    [$b, $v] = boblift_split_body_videos('A<figure class="wp-block-video">' . $tag . '</figure>', 'T');
    if ($b !== 'A' || count($v) !== 1 || $v[0]['loop'] !== $loop) { $failed++; echo 'loop flag should be ' . ($loop ? 'true' : 'false') . ": $tag\n"; }
}
[$b, $v] = boblift_split_body_videos($move[1] . '<p>m</p>' . $move[1], 'T');
if ($b !== '<p>m</p>' || $v[0]['label'] !== 'T 1' || $v[1]['label'] !== 'T 2') { $failed++; echo "uncaptioned films should be numbered\n"; }
[$b, $v] = boblift_split_body_videos('<!-- wp:video -->' . $move[1] . '<!-- /wp:video -->', 'T');
if (count($v) !== 1) { $failed++; echo "a film inside block comments should move\n"; }
echo $failed ? "$failed failed\n" : "ok\n";
exit($failed ? 1 : 0);
