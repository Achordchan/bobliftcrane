<?php
require __DIR__ . '/transport-stubs.php';
require __DIR__ . '/../includes/core.php';
require __DIR__ . '/../includes/jobs.php';
require __DIR__ . '/../includes/transport.php';

$checks = 0;
function check(bool $condition, string $label): void
{
    global $checks;
    if (!$condition) { throw new RuntimeException($label); }
    $checks++;
}
function snapshot(): string
{
    return serialize(array_diff_key($GLOBALS['fixture'], array_flip(['hooks', 'routes'])));
}

$GLOBALS['fixture']['terms'][1] = new WP_Term(1, 'product_category', 'cranes', 'Cranes');
$GLOBALS['fixture']['term_lang'][1] = 'en';
$GLOBALS['fixture']['term_links'][1] = ['en' => 1];
$title = '製品 &amp; "紹介" \\ path';
$text = "第一段 &amp; &#39; <strong>紹介</strong>\n\n第二段 C:\\files \'quoted\' 😀";
$meta = ['_boblift_intro_title' => $title, '_boblift_intro_text' => $text];
$package = ['format' => 1, 'source_language' => 'en', 'language' => 'ja', 'terms' => [
    ['taxonomy' => 'product_category', 'source_slug' => 'cranes', 'slug' => 'ja-cranes', 'name' => 'クレーン', 'meta' => $meta],
]];
$before = snapshot();
$report = boblift_mls_import_language($package, ['dry_run' => true]);
check($report['terms_meta_written'] === 2 && $report['created'] === 1, 'dry run predicts metadata for new term');
check(snapshot() === $before, 'dry run does not write metadata');
foreach (BOBLIFT_MLS_TRANSPORT_TERM_META_KEYS as $key) {
    foreach ([null, false, 0, [], new stdClass(), str_repeat('界', 20001), str_repeat('a', 20001)] as $value) {
        $bad = $package;
        $bad['terms'][0]['meta'][$key] = $value;
        check(is_wp_error(boblift_mls_import_language($bad)), 'invalid metadata rejected: ' . $key);
        check(snapshot() === $before, 'validation precedes all writes');
    }
    $boundary = $package;
    $boundary['terms'][0]['meta'][$key] = str_repeat('界', 19999) . '😀';
    check(!is_wp_error(boblift_mls_import_language($boundary, ['dry_run' => true])), '20000 Unicode characters allowed');
}
foreach ([null, 'invalid', false] as $value) {
    $bad = $package;
    $bad['terms'][0]['meta'] = $value;
    check(is_wp_error(boblift_mls_import_language($bad)) && snapshot() === $before, 'invalid metadata container rejected');
}
$report = boblift_mls_import_language(json_decode(json_encode($package), true));
$target_id = pll_get_term(1, 'ja');
check($target_id > 0 && $report['errors'] === [] && $report['terms_meta_written'] === 2, 'new linked term receives both fields');
foreach ($meta as $key => $value) {
    check(get_term_meta($target_id, $key, true) === $value, 'stored value preserved exactly: ' . $key);
}
check(!metadata_exists('term', 1, '_boblift_intro_title'), 'source term unchanged');
update_term_meta($target_id, '_unknown', 'private');
$export = boblift_mls_export_language('ja');
check($export['terms'][0]['meta'] === $meta, 'export allowlist and exact strings');
$report = boblift_mls_import_language(json_decode(json_encode($export), true), ['only' => ['terms']]);
check($report['terms_meta_written'] === 2 && $report['created'] === 0 && $report['errors'] === [], 'unchanged values successfully applied');
$changed = $package;
$changed['terms'][0]['slug'] = 'different-target-slug';
$changed['terms'][0]['meta'] = ['_boblift_intro_title' => '更新 \\ "引用"'];
$report = boblift_mls_import_language($changed);
check(pll_get_term(1, 'ja') === $target_id && $report['terms_meta_written'] === 1, 'existing term matched by source slug');
check(get_term_meta($target_id, '_boblift_intro_title', true) === $changed['terms'][0]['meta']['_boblift_intro_title'], 'existing value updated');
check(get_term_meta($target_id, '_boblift_intro_text', true) === $text, 'omitted field preserved');
foreach ([[], null] as $empty) {
    $missing = $package;
    if ($empty === null) { unset($missing['terms'][0]['meta']); }
    else { $missing['terms'][0]['meta'] = $empty; }
    $report = boblift_mls_import_language($missing);
    check($report['terms_meta_written'] === 0 && get_term_meta($target_id, '_boblift_intro_text', true) === $text, 'absent or empty metadata preserves existing fields');
}
$empty = $package;
$empty['terms'][0]['meta'] = ['_boblift_intro_text' => ''];
$report = boblift_mls_import_language($empty);
check($report['terms_meta_written'] === 1 && metadata_exists('term', $target_id, '_boblift_intro_text')
    && get_term_meta($target_id, '_boblift_intro_text', true) === '', 'explicit empty string clears value without deleting key');
check(boblift_mls_export_language('ja')['terms'][0]['meta']['_boblift_intro_text'] === '', 'export retains explicit empty string');
unset($GLOBALS['fixture']['term_meta'][$target_id]['_boblift_intro_title']);
check(!array_key_exists('_boblift_intro_title', boblift_mls_export_language('ja')['terms'][0]['meta']), 'export omits unset fields');
$before = snapshot();
foreach ([['overwrite' => false], ['only' => ['posts']], ['only' => []]] as $args) {
    $report = boblift_mls_import_language($package, $args);
    check($report['terms_meta_written'] === 0 && snapshot() === $before, 'overwrite and scope protections preserved');
}
$report = boblift_mls_import_language($package, ['dry_run' => true]);
check($report['terms_meta_written'] === 2 && snapshot() === $before, 'dry run predicts updates without writes');
$unknown = $package;
$unknown['terms'][0]['meta'] = ['_unknown' => ['invalid' => true]];
$report = boblift_mls_import_language($unknown);
check($report['terms_meta_written'] === 0 && count($report['warnings']) === 1
    && str_contains($report['warnings'][0], '_unknown'), 'unknown metadata warns and skips value validation');
check(get_term_meta($target_id, '_unknown', true) === 'private', 'unknown existing metadata preserved');
$mixed = $package;
$mixed['terms'][0]['meta'] = ['_boblift_intro_title' => '0', '_boblift_intro_text' => " \n\n ", '_unknown' => false];
$report = boblift_mls_transport_rest_import(new WP_REST_Request(json_encode($mixed)));
check($report['terms_meta_written'] === 2 && count($report['warnings']) === 1
    && get_term_meta($target_id, '_boblift_intro_title', true) === '0'
    && get_term_meta($target_id, '_boblift_intro_text', true) === " \n\n ", 'REST applies known keys including zero and whitespace verbatim');
$missing = $package;
$missing['terms'][0]['source_slug'] = 'missing';
$before = snapshot();
$report = boblift_mls_import_language($missing);
check($report['terms_meta_written'] === 0 && snapshot() === $before && $report['missing_sources'] !== [], 'missing source never writes metadata');
foreach ([false, new WP_Error('test', 'Write failed')] as $failure) {
    $GLOBALS['fixture']['term_meta_failure'] = $failure;
    $report = boblift_mls_import_language($package);
    check($report['terms_meta_written'] === 0 && count($report['errors']) === 2, 'failed writes reported and not counted');
}
unset($GLOBALS['fixture']['term_meta_failure']);
$GLOBALS['fixture']['terms'][2] = new WP_Term(2, 'category', 'news', 'News');
$GLOBALS['fixture']['term_lang'][2] = 'en';
$GLOBALS['fixture']['term_links'][2] = ['en' => 2];
$other = $package;
$other['terms'][0] = ['taxonomy' => 'category', 'source_slug' => 'news', 'slug' => 'ja-news', 'name' => 'ニュース', 'meta' => $meta];
$report = boblift_mls_import_language($other);
$other_id = pll_get_term(2, 'ja');
check($report['terms_meta_written'] === 0 && count($report['warnings']) === 1
    && !metadata_exists('term', $other_id, '_boblift_intro_title'), 'metadata only applies to product categories');
update_term_meta($other_id, '_boblift_intro_title', wp_slash($title));
$export = boblift_mls_export_language('ja');
$other_record = array_values(array_filter($export['terms'], static fn($term) => $term['taxonomy'] === 'category'))[0];
check(!array_key_exists('meta', $other_record), 'other taxonomies never export metadata');
echo 'OK: ' . $checks . " term metadata transport checks\n";
