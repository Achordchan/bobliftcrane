<?php
// Run: php tools/image-alts/test.php — checks boblift_fill_missing_image_alts() without WordPress.
function in_the_loop(){return true;} function is_singular(){return true;} function get_the_ID(){return 5;}
function get_the_title($i){return $GLOBALS['t'];} function wp_strip_all_tags($s){return strip_tags($s);}
function esc_attr($s){return htmlspecialchars($s,ENT_QUOTES);} function attachment_url_to_postid($u){return str_contains($u,'5.jpg')?77:0;}
function get_post_meta($id,$k,$s){return $id===77?'Crane on deck':'';} function add_filter(){}
$GLOBALS['lang']='en'; $GLOBALS['t']='$10,000 truck crane & "x"'; function boblift_current_language_slug(){return $GLOBALS['lang'];}
eval(preg_replace('/^.*?(function boblift_fill_missing_image_alts.*?\n}\n).*$/s','$1',file_get_contents(__DIR__ . '/../../inc/setup.php')));
$cases=[
'<img src="/u/5.jpg" alt="">'=>'<img alt="Crane on deck" src="/u/5.jpg">',
'<img alt="" src="/x/8.jpg">'=>'<img alt="$10,000 truck crane &amp; &quot;x&quot;" src="/x/8.jpg">',
'<img src="/y.jpg">'=>'<img alt="$10,000 truck crane &amp; &quot;x&quot;" src="/y.jpg">',
'<img src="/y.jpg" alt />'=>'<img alt="$10,000 truck crane &amp; &quot;x&quot;" src="/y.jpg" />',
'<img src="/z.jpg" alt="Crane\'s folding boom">'=>'<img src="/z.jpg" alt="Crane\'s folding boom">',
"<img src='/z.jpg' alt='12\" reach'>"=>"<img src='/z.jpg' alt='12\" reach'>",
'<img src="/z.jpg" alt=Kept>'=>'<img src="/z.jpg" alt=Kept>',
'<img src="/d.jpg" alt="" role="presentation">'=>'<img src="/d.jpg" alt="" role="presentation">',
'<img src="/d.jpg" aria-hidden=\'true\'>'=>'<img src="/d.jpg" aria-hidden=\'true\'>',
'<img class="x wp-image-77" src="/q.jpg" alt=" ">'=>'<img alt="Crane on deck" class="x wp-image-77" src="/q.jpg">',
'<img src="/a.jpg" data-alt="Real" alt="">'=>'<img alt="$10,000 truck crane &amp; &quot;x&quot;" src="/a.jpg" data-alt="Real">',
'<img alt="Lifting capacity > 5 tonnes" src="/crane.jpg">'=>'<img alt="Lifting capacity > 5 tonnes" src="/crane.jpg">',
'<img title="alt=\'\'" src="/t.jpg">'=>'<img alt="$10,000 truck crane &amp; &quot;x&quot;" title="alt=\'\'" src="/t.jpg">',
'<img title=\'has alt="" inside\' alt="" src="/t.jpg">'=>'<img alt="$10,000 truck crane &amp; &quot;x&quot;" title=\'has alt="" inside\' src="/t.jpg">',
'<p>a > b</p><img src="/y.jpg"><p>c</p>'=>'<p>a > b</p><img alt="$10,000 truck crane &amp; &quot;x&quot;" src="/y.jpg"><p>c</p>',
];
$f=0; foreach($cases as $in=>$want){$g=boblift_fill_missing_image_alts($in); if($g!==$want){echo "FAIL $in\n  got  $g\n  want $want\n";$f++;}} echo $f ? "$f FAILED\n" : "all ok\n"; exit($f ? 1 : 0);
