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
function package_fixture(): array
{
    return ['format' => 1, 'source_language' => 'en', 'language' => 'ja',
        'posts' => [
            ['type' => 'page', 'source_slug' => 'child', 'title' => '子ページ \\ "日本語"', 'content' => '<!-- wp:html -->{"path":"C:\\\\files"}<!-- /wp:html -->',
                'excerpt' => '日本語 \\ excerpt', 'status' => 'publish', 'date' => '2026-10-16 12:00:00', 'date_gmt' => '2026-10-16 04:00:00',
                'parent_source_slug' => 'parent', 'menu_order' => 2, 'meta' => ['json' => '{"value":"C:\\\\files"}', 'number' => '000123',
                    'nested' => ['path' => 'C:\\files'], 'multiple' => 'first', 'password' => 'never'],
                'meta_rows' => ['multiple' => ['first', 'second\\value']],
                'thumbnail' => ['file' => '2026/10/image.jpg', 'alt' => '画像 \\ alt'],
                'terms' => ['category' => [['source_slug' => 'child-category', 'slug' => 'ja-child', 'name' => '子分類']]]],
            ['type' => 'page', 'source_slug' => 'parent', 'title' => '親ページ', 'status' => 'publish'],
        ], 'terms' => [
            ['taxonomy' => 'category', 'source_slug' => 'child-category', 'slug' => 'ja-child', 'name' => '子分類', 'description' => '説明', 'parent_source_slug' => 'parent-category'],
            ['taxonomy' => 'category', 'source_slug' => 'parent-category', 'slug' => 'ja-parent', 'name' => '親分類', 'description' => '', 'parent_source_slug' => ''],
        ], 'options' => ['boblift_theme_options_ja' => ['home' => ['title' => 'ホーム', 'items' => ['b', 'a'], 'api_key' => 'never']],
            'languages' => ['ja' => ['status' => 'ready', 'translated_at' => 123]]]];
}
function fixture_snapshot(): string
{
    return serialize(array_diff_key($GLOBALS['fixture'], array_flip(['hooks', 'routes'])));
}
$GLOBALS['fixture']['posts'] = [1 => new WP_Post(1, 'page', 'child'), 2 => new WP_Post(2, 'page', 'parent'),
    3 => new WP_Post(3, 'attachment', 'image', post_status: 'inherit')];
$GLOBALS['fixture']['post_lang'] = [1 => 'en', 2 => 'en'];
$GLOBALS['fixture']['post_links'] = [1 => ['en' => 1], 2 => ['en' => 2]];
$GLOBALS['fixture']['terms'] = [1 => new WP_Term(1, 'category', 'child-category', 'Child', parent: 2),
    2 => new WP_Term(2, 'category', 'parent-category', 'Parent')];
$GLOBALS['fixture']['term_lang'] = [1 => 'en', 2 => 'en'];
$GLOBALS['fixture']['term_links'] = [1 => ['en' => 1], 2 => ['en' => 2]];
$GLOBALS['fixture']['meta'] = [3 => ['_wp_attached_file' => ['2026/10/image.jpg']], 1 => ['source_json' => ['{"path":"C:\\\\files"}'], 'api_key' => ['never']]];
$GLOBALS['fixture']['options'][BOBLIFT_MLS_STATE_OPTION] = ['languages' => ['fr' => ['status' => 'published']]];
$package = package_fixture();
$before = fixture_snapshot();
$report = boblift_mls_import_language($package, ['dry_run' => true]);
check(!is_wp_error($report) && $report['created'] === 6 && $report['errors'] === [], 'dry run predicts creates');
check($before === fixture_snapshot(), 'dry run performs zero writes');
check(count(array_filter($report['warnings'], static fn($warning) => str_contains($warning, '父页面') || str_contains($warning, '父分类'))) === 0, 'dry run resolves parents planned in the same package');
$bad = $package;
$bad['posts'][] = ['type' => 'attachment', 'source_slug' => 'invalid', 'title' => 'Invalid'];
check(is_wp_error(boblift_mls_import_language($bad)), 'unknown post type rejected');
check($before === fixture_snapshot(), 'validation rejects before any writes');
foreach ([['format' => 2], ['language' => 'xx'], ['language' => 'en'], ['source_language' => 'fr'], ['posts' => 'bad']] as $change) {
    check(is_wp_error(boblift_mls_import_language(array_replace($package, $change))), 'invalid envelope rejected');
}
$bad = $package;
$bad['options']['boblift_theme_options_ja']['home'] = 'bad';
check(is_wp_error(boblift_mls_import_language($bad)), 'invalid option sections rejected before writes');
check($before === fixture_snapshot(), 'all invalid packages leave state unchanged');
$empty_report = boblift_mls_import_language($package, ['only' => []]);
check(!is_wp_error($empty_report) && $empty_report['created'] === 0 && $before === fixture_snapshot(), 'empty only selects no writes');
foreach ([['only' => [['posts']]], ['only' => ['unknown']], ['dry_run' => 'invalid'], ['overwrite' => []]] as $bad_args) {
    check(is_wp_error(boblift_mls_import_language($package, $bad_args)), 'invalid transport arguments rejected');
}
$report = boblift_mls_import_language($package);
check($report['errors'] === [] && $report['missing_sources'] === [], 'initial import succeeds');
$child = pll_get_post(1, 'ja');
$parent = pll_get_post(2, 'ja');
check(get_post($child)->post_parent === $parent, 'child-first page hierarchy mapped');
check(get_post($child)->post_name === 'child', 'English-compatible slug requested');
check(get_post($child)->post_status === 'draft', 'default import stays draft');
check(get_post($child)->post_title === $package['posts'][0]['title'] && get_post($child)->post_content === $package['posts'][0]['content'], 'post backslashes and charset preserved');
check(get_post_meta($child, 'json', true) === $package['posts'][0]['meta']['json'], 'JSON meta preserved');
check(get_post_meta($child, 'number', true) === '000123', 'numeric strings stay strings');
check(get_post_meta($child, 'nested', true) === ['path' => 'C:\\files'], 'nested backslashes preserved');
check(get_post_meta($child, 'multiple') === ['first', 'second\\value'], 'multiple metadata rows preserved');
check(get_post_meta($child, 'source_json', true) === get_post_meta(1, 'source_json', true), 'source helper metadata retains backslashes');
check(get_post_meta($child, 'password', true) === '' && get_post_meta($child, 'api_key', true) === '', 'secrets not imported or copied');
check(get_post_thumbnail_id($child) === 3 && get_post_meta(3, '_wp_attachment_image_alt', true) === '画像 \\ alt', 'attachment matched by path');
check(get_term(pll_get_term(1, 'ja'), 'category')->parent === pll_get_term(2, 'ja'), 'child-first term hierarchy mapped');
check($GLOBALS['fixture']['assignments'][$child]['category'] === [pll_get_term(1, 'ja')], 'term assignment uses target translation');
check(boblift_mls_get_raw_options('ja')['home'] === ['title' => 'ホーム', 'items' => ['b', 'a']], 'option ordering preserved and secret removed');
check(boblift_mls_language_status('fr') === 'published' && boblift_mls_language_status('ja') === 'ready', 'language state merged');
$state_before = boblift_mls_state();
$options_before = boblift_mls_get_raw_options('ja');
$report = boblift_mls_import_language($package, ['only' => ['posts'], 'publish' => true]);
check(boblift_mls_state() === $state_before && boblift_mls_get_raw_options('ja') === $options_before, 'only posts leaves options and language state untouched');
$post_count = count($GLOBALS['fixture']['posts']);
$term_count = count($GLOBALS['fixture']['terms']);
$report = boblift_mls_import_language($package);
check($report['created'] === 0 && count($GLOBALS['fixture']['posts']) === $post_count && count($GLOBALS['fixture']['terms']) === $term_count, 'second import creates no duplicates');
check(get_post_meta($child, 'multiple') === ['first', 'second\\value'], 'repeat import has no duplicate metadata rows');
$writes = $GLOBALS['fixture']['writes'];
$report = boblift_mls_import_language($package, ['overwrite' => false]);
check($report['skipped'] === 6 && $GLOBALS['fixture']['writes'] === $writes, 'no-overwrite protects posts terms options and state');
$report = boblift_mls_import_language($package, ['publish' => true]);
check(get_post($child)->post_status === 'publish' && boblift_mls_language_status('ja') === 'published', 'publish imports statuses and publishes language');
$package['posts'][0]['source_slug'] = 'missing';
$report = boblift_mls_import_language($package, ['only' => ['posts'], 'dry_run' => true]);
check(in_array('page:missing', $report['missing_sources'], true), 'missing source reported');
$package = package_fixture();
$package['posts'][0]['thumbnail']['file'] = 'missing.jpg';
$report = boblift_mls_import_language($package, ['dry_run' => true, 'only' => ['posts']]);
check(count(array_filter($report['warnings'], static fn($warning) => str_contains($warning, 'missing.jpg'))) === 1, 'missing media warning');
$package = package_fixture();
$package['terms'][0]['source_slug'] = 'missing-term';
$report = boblift_mls_import_language($package, ['only' => ['terms'], 'dry_run' => true]);
check(in_array('category:missing-term', $report['missing_sources'], true), 'missing source term reported');
$package = package_fixture();
$package['terms'][0]['parent_source_slug'] = 'missing-parent';
$report = boblift_mls_import_language($package, ['only' => ['terms'], 'dry_run' => true]);
check(count(array_filter($report['warnings'], static fn($warning) => str_contains($warning, 'missing-parent'))) === 1, 'dry run detects missing term parent');
$export = boblift_mls_export_language('ja');
check(!is_wp_error($export) && $export['format'] === 1 && count($export['posts']) === 2, 'export includes linked target posts');
check(!str_contains(json_encode($export), 'never'), 'export contains no fixture secrets');
check($export['posts'][0]['meta']['number'] === '000123', 'export leaves stored strings unchanged');
check($export['posts'][0]['meta_rows']['multiple'] === ['first', 'second\\value'], 'export retains multi-row metadata');
check($export['posts'][0]['terms']['category'][0]['source_slug'] === 'child-category', 'export term uses source slug');
$report = boblift_mls_import_language(json_decode(json_encode($export), true), ['dry_run' => true]);
check(!is_wp_error($report) && $report['errors'] === [], 'export package roundtrip validates');
$unknown = package_fixture();
$unknown['unexpected'] = 'ignored';
$unknown['posts'][0]['unexpected'] = 'ignored';
$unknown['options']['polytranslate_ai_options'] = ['api_key' => 'never'];
$report = boblift_mls_import_language($unknown, ['dry_run' => true]);
check(count($report['warnings']) >= 3, 'unknown keys produce warnings');
$request = new WP_REST_Request(json_encode(package_fixture()), ['dry_run' => 'false', 'overwrite' => 'false', 'only' => 'options']);
$writes = $GLOBALS['fixture']['writes'];
$report = boblift_mls_transport_rest_import($request);
check(!is_wp_error($report) && $report['skipped'] === 2 && $GLOBALS['fixture']['writes'] === $writes, 'REST parses false flags correctly');
check(boblift_mls_transport_rest_import(new WP_REST_Request('{'))->get_error_data()['status'] === 400, 'invalid JSON returns 400');
check(boblift_mls_export_language('xx')->get_error_data()['status'] === 404, 'unknown language returns 404');
$large = new WP_REST_Request(str_repeat(' ', BOBLIFT_MLS_TRANSPORT_MAX_BYTES + 1));
check(boblift_mls_transport_rest_import($large)->get_error_data()['status'] === 413, 'oversized body returns 413');
$guard = $GLOBALS['fixture']['hooks']['rest_pre_dispatch'][0];
check($guard(null, null, $large)->get_error_data()['status'] === 413, 'size guard runs before REST JSON parsing');
foreach ($GLOBALS['fixture']['hooks']['rest_api_init'] as $callback) { $callback(); }
$export_route = $GLOBALS['fixture']['routes']['/export/(?P<lang>[a-z0-9_-]+)'];
$route_result = $export_route['callback'](new WP_REST_Request(query: ['lang' => 'ja']));
check(isset($route_result['format']) && !isset($route_result['data']), 'REST export returns package directly');
foreach (['post', 'product', 'case'] as $index => $type) {
    $source_id = 10 + $index;
    $GLOBALS['fixture']['posts'][$source_id] = new WP_Post($source_id, $type, 'source-' . $type);
    $GLOBALS['fixture']['post_lang'][$source_id] = 'en';
    $GLOBALS['fixture']['post_links'][$source_id] = ['en' => $source_id];
    $typed_package = ['format' => 1, 'source_language' => 'en', 'language' => 'ja', 'posts' => [
        ['type' => $type, 'source_slug' => 'source-' . $type, 'title' => '日本語']]];
    $report = boblift_mls_import_language($typed_package, ['only' => ['posts']]);
    $translated = get_post(pll_get_post($source_id, 'ja'));
    check($report['created'] === 1 && $translated instanceof WP_Post && $translated->post_type === $type, 'imports supported type ' . $type);
}
check(preg_match('#^/export/(?P<lang>[a-z0-9_-]+)$#', '/export/pt-br') === 1, 'REST export route accepts regional language slugs');
$GLOBALS['fixture']['posts'][$child]->post_name = 'child-ja';
$report = boblift_mls_import_language(package_fixture(), ['publish' => true]);
check($report['errors'] === [] && get_post($child)->post_name === 'child-ja', 'update keeps the existing translated slug');
$GLOBALS['fixture']['meta'][$child]['number'] = ['old1', 'old2'];
boblift_mls_import_language(package_fixture(), ['only' => ['posts']]);
check(get_post_meta($child, 'number') === ['000123'], 'single-value import replaces multiple existing rows');
$state = boblift_mls_state();
$state['languages']['ja']['status'] = 'ready';
boblift_mls_update_state($state);
$failing = package_fixture();
$failing['options']['boblift_theme_options_ja']['home']['title'] = '新しいホーム';
$GLOBALS['fixture']['fail_options'] = true;
$report = boblift_mls_import_language($failing, ['publish' => true]);
$GLOBALS['fixture']['fail_options'] = false;
check($report['errors'] !== [] && boblift_mls_language_status('ja') === 'ready', 'failed option write is reported and blocks publishing');
$report = boblift_mls_import_language(package_fixture(), ['only' => ['options']]);
check($report['errors'] === [], 'unchanged option write is not treated as a failure');
$ja_term = pll_get_term(1, 'ja');
$GLOBALS['fixture']['terms'][$ja_term]->slug = 'deployed-ja-child';
boblift_mls_import_language(package_fixture(), ['only' => ['terms']]);
check(get_term($ja_term, 'category')->slug === 'deployed-ja-child', 'update keeps the existing translated term slug');
$GLOBALS['fixture']['posts'][20] = new WP_Post(20, 'case', 'project');
$GLOBALS['fixture']['post_lang'][20] = 'en';
$GLOBALS['fixture']['post_links'][20] = ['en' => 20];
boblift_mls_import_language(['format' => 1, 'source_language' => 'en', 'language' => 'ja', 'posts' => [
    ['type' => 'case', 'source_slug' => 'project', 'slug' => 'project-ja', 'title' => '事例']]], ['only' => ['posts']]);
check(get_post(pll_get_post(20, 'ja'))->post_name === 'project-ja', 'new translation takes the exported slug');
$GLOBALS['fixture']['fail_meta'] = 'json';
$failing = package_fixture();
$failing['posts'][0]['meta']['json'] = '{"changed":true}';
$report = boblift_mls_import_language($failing, ['only' => ['posts'], 'publish' => true]);
$GLOBALS['fixture']['fail_meta'] = '';
check(count(array_filter($report['errors'], static fn($error) => str_contains($error, 'json'))) === 1, 'failed post meta write is reported');
$report = boblift_mls_import_language(package_fixture(), ['only' => ['posts']]);
check($report['errors'] === [], 'unchanged post meta is not a failure');
$GLOBALS['fixture']['options']['boblift_mls_transport_lock'] = 'other-import';
$locked = boblift_mls_import_language(package_fixture(), ['only' => ['posts']]);
check(is_wp_error($locked) && $locked->get_error_data()['status'] === 409, 'overlapping import is refused');
$fr_locked = boblift_mls_import_language(array_replace(package_fixture(), ['language' => 'fr', 'options' => []]), ['only' => ['options']]);
check(is_wp_error($fr_locked), 'the lock also covers other languages that share the language state');
check(!is_wp_error(boblift_mls_import_language(package_fixture(), ['dry_run' => true])), 'dry run does not need the lock');
boblift_mls_transport_unlock('not-the-owner');
check($GLOBALS['fixture']['options']['boblift_mls_transport_lock'] === 'other-import', 'a lock is released only by its owner');
unset($GLOBALS['fixture']['options']['boblift_mls_transport_lock']);
check(!is_wp_error(boblift_mls_import_language(package_fixture(), ['only' => ['posts']])), 'import runs once the lock is free');
check(!array_key_exists('boblift_mls_transport_lock', $GLOBALS['fixture']['options']), 'lock is released after import');
$GLOBALS['fixture']['posts'][30] = new WP_Post(30, 'page', 'about');
$GLOBALS['fixture']['posts'][31] = new WP_Post(31, 'page', 'team');
$GLOBALS['fixture']['post_lang'] += [30 => 'en', 31 => 'en'];
$GLOBALS['fixture']['post_links'] += [30 => ['en' => 30], 31 => ['en' => 31]];
$report = boblift_mls_import_language(['format' => 1, 'source_language' => 'en', 'language' => 'ja', 'posts' => [
    ['type' => 'page', 'source_slug' => 'team', 'title' => 'チーム', 'parent_source_slug' => 'about'],
    ['type' => 'page', 'source_slug' => 'about', 'slug' => 'about-ja', 'title' => '会社概要']]], ['only' => ['posts'], 'dry_run' => true]);
check(count(array_filter($report['warnings'], static fn($warning) => str_contains($warning, '父页面'))) === 0, 'dry run finds a planned parent by source slug');
$GLOBALS['fixture']['authorized'] = false;
foreach ($GLOBALS['fixture']['routes'] as $route) { check(!$route['permission_callback'](), 'REST requires manage_options'); }
echo 'OK: ' . $checks . " transport checks\n";
