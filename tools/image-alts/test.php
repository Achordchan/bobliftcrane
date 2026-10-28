<?php
// Run: WP_HTML_API=/path/to/wordpress/wp-includes php tools/image-alts/test.php
// Checks boblift_fill_missing_image_alts() with WordPress's own HTML tag processor but without a WordPress install.
$wp_includes = rtrim((string) getenv('WP_HTML_API'), '/');
if ($wp_includes === '' || !is_file($wp_includes . '/html-api/class-wp-html-tag-processor.php')) {
    fwrite(STDERR, "Set WP_HTML_API to a WordPress wp-includes folder.\n");
    exit(2);
}
function __($s){return $s;} function _doing_it_wrong(){} function wp_kses_uri_attributes(){return ['href','src'];} function esc_url($s){return htmlspecialchars($s,ENT_QUOTES);} function wp_has_noncharacters($s){return false;}
require $wp_includes . '/class-wp-token-map.php';
foreach (['span','text-replacement','attribute-token','decoder','tag-processor'] as $part) { require $wp_includes . "/html-api/class-wp-html-$part.php"; }
function in_the_loop(){return true;} function is_singular(){return true;} function get_the_ID(){return 5;}
function get_the_title($i){return $GLOBALS['t'];} function wp_strip_all_tags($s){return strip_tags($s);}
function esc_attr($s){return htmlspecialchars($s,ENT_QUOTES);} // Like WordPress, only an attachment's own file URL resolves (5.jpg, big-scaled.jpg), never a generated size.
function attachment_url_to_postid($u){return in_array($u,['/u/5.jpg','/u/big-scaled.jpg','https://bobliftcrane.com/u/5.jpg'],true)?77:0;}
function get_post_meta($id,$k,$s){return $id===77?'Crane on deck':'';} function add_filter(){}
$GLOBALS['lang']='en'; $GLOBALS['t']='$10,000 truck crane & "x"'; function boblift_current_language_slug(){return $GLOBALS['lang'];}
eval(preg_replace('/^.*?(function boblift_fill_missing_image_alts.*?\n}\n).*$/s','$1',file_get_contents(__DIR__ . '/../../inc/setup.php')));
$cases=[
'<img src="/u/5.jpg" alt="">'=>'<img src="/u/5.jpg" alt="Crane on deck">',
'<img alt="" src="/x/8.jpg">'=>'<img alt="$10,000 truck crane &amp; &quot;x&quot;" src="/x/8.jpg">',
'<img src="/y.jpg">'=>'<img alt="$10,000 truck crane &amp; &quot;x&quot;" src="/y.jpg">',
'<img src="/y.jpg" alt />'=>'<img src="/y.jpg" alt="$10,000 truck crane &amp; &quot;x&quot;" />',
'<img src="/z.jpg" alt="Crane\'s folding boom">'=>'<img src="/z.jpg" alt="Crane\'s folding boom">',
"<img src='/z.jpg' alt='12\" reach'>"=>"<img src='/z.jpg' alt='12\" reach'>",
'<img src="/z.jpg" alt=Kept>'=>'<img src="/z.jpg" alt=Kept>',
'<img src="/d.jpg" alt="" role="presentation">'=>'<img src="/d.jpg" alt="" role="presentation">',
'<img src="/d.jpg" aria-hidden=\'true\'>'=>'<img src="/d.jpg" aria-hidden=\'true\'>',
'<img class="x wp-image-77" src="/q.jpg" alt=" ">'=>'<img class="x wp-image-77" src="/q.jpg" alt="Crane on deck">',
'<img src="/a.jpg" data-alt="Real" alt="">'=>'<img src="/a.jpg" data-alt="Real" alt="$10,000 truck crane &amp; &quot;x&quot;">',
'<img alt="Lifting capacity > 5 tonnes" src="/crane.jpg">'=>'<img alt="Lifting capacity > 5 tonnes" src="/crane.jpg">',
'<img title="alt=\'\'" src="/t.jpg">'=>'<img alt="$10,000 truck crane &amp; &quot;x&quot;" title="alt=\'\'" src="/t.jpg">',
'<img title=\'has alt="" inside\' alt="" src="/t.jpg">'=>'<img title=\'has alt="" inside\' alt="$10,000 truck crane &amp; &quot;x&quot;" src="/t.jpg">',
'<p>a > b</p><img src="/y.jpg"><p>c</p>'=>'<p>a > b</p><img alt="$10,000 truck crane &amp; &quot;x&quot;" src="/y.jpg"><p>c</p>',
'<img src="/u/5-300x200.jpg" alt="">'=>'<img src="/u/5-300x200.jpg" alt="Crane on deck">',
'<img src="https://i0.wp.com/bobliftcrane.com/u/5-1024x683.jpg?ssl=1">'=>'<img alt="Crane on deck" src="https://i0.wp.com/bobliftcrane.com/u/5-1024x683.jpg?ssl=1">',
'<img src="/u/big-768x512.jpg">'=>'<img alt="Crane on deck" src="/u/big-768x512.jpg">',
'<img src="/u/other-300x200.jpg">'=>'<img alt="$10,000 truck crane &amp; &quot;x&quot;" src="/u/other-300x200.jpg">',
'<div data-preview="<img src=\'/photo.jpg\'>"><img src="/y.jpg"></div>'=>'<div data-preview="<img src=\'/photo.jpg\'>"><img alt="$10,000 truck crane &amp; &quot;x&quot;" src="/y.jpg"></div>',
'<!-- <img src="/c.jpg"> --><img src="/y.jpg">'=>'<!-- <img src="/c.jpg"> --><img alt="$10,000 truck crane &amp; &quot;x&quot;" src="/y.jpg">',
];
$f=0; foreach($cases as $in=>$want){$g=boblift_fill_missing_image_alts($in); if($g!==$want){echo "FAIL $in\n  got  $g\n  want $want\n";$f++;}} echo $f ? "$f FAILED\n" : "all ok\n"; exit($f ? 1 : 0);
