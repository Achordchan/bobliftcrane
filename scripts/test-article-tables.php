<?php
// php scripts/test-article-tables.php: bare tables in a story get a scroll wrapper; block-editor tables are left alone.
function add_filter(...$args) {}
$src = file_get_contents(__DIR__ . '/../inc/setup.php');
preg_match('/function boblift_wrap_bare_tables.*?\n}\n/s', $src, $m);
eval($m[0]);
$failed = 0;
$block = '<figure class="wp-block-table"><table><tr><td>a</td></tr></table><figcaption>c</figcaption></figure>';
if (boblift_wrap_bare_tables($block) !== $block) { $failed++; echo "block table should stay as is\n"; }
$bare = '<h2>x</h2><table class="bl-facts"><tbody><tr><th>a</th><td>b</td></tr></tbody></table><p>y</p>';
$out = boblift_wrap_bare_tables($bare);
if ($out !== '<h2>x</h2><div class="bl-table-scroll"><table class="bl-facts"><tbody><tr><th>a</th><td>b</td></tr></tbody></table></div><p>y</p>') { $failed++; echo "bare table not wrapped: $out\n"; }
if (boblift_wrap_bare_tables($out) !== $out) { $failed++; echo "running twice should change nothing\n"; }
$mixed = $block . '<table><tr><td>z</td></tr></table>';
if (substr_count(boblift_wrap_bare_tables($mixed), 'bl-table-scroll') !== 1) { $failed++; echo "only the bare table should be wrapped\n"; }
echo $failed ? "$failed failed\n" : "ok\n";
exit($failed ? 1 : 0);
