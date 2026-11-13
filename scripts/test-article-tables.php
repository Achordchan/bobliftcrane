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
// A table nested in a cell stays inside its parent: only the outermost table is wrapped, once.
$nested = '<table><tr><td><table><tr><td>in</td></tr></table></td></tr></table><p>after</p>';
$out = boblift_wrap_bare_tables($nested);
if ($out !== '<div class="bl-table-scroll">' . substr($nested, 0, -strlen('<p>after</p>')) . '</div><p>after</p>') { $failed++; echo "nested table wrapped wrongly: $out\n"; }
if (boblift_wrap_bare_tables($out) !== $out) { $failed++; echo "nested: running twice should change nothing\n"; }
$inBlock = '<figure class="wp-block-table"><table><tr><td><table><tr><td>in</td></tr></table></td></tr></table></figure>';
if (boblift_wrap_bare_tables($inBlock) !== $inBlock) { $failed++; echo "nested table in a block figure should stay as is\n"; }
$upper = '<TABLE><TR><TD>a</TD></TR></TABLE>';
if (boblift_wrap_bare_tables($upper) !== '<div class="bl-table-scroll">' . $upper . '</div>') { $failed++; echo "uppercase table should be wrapped\n"; }
$open = '<table><tr><td>never closed';
if (boblift_wrap_bare_tables($open) !== $open) { $failed++; echo "unclosed table should stay as is\n"; }
echo $failed ? "$failed failed\n" : "ok\n";
exit($failed ? 1 : 0);
