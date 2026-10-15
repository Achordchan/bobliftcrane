<?php
// Import the two Industry Insight articles from tmp/content-gap into the local WordPress.
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

/**
 * Imports the two Industry Insight articles. Run on the target site:
 *
 *   wp eval-file tools/content-gap/import-articles.php [source-dir] [overwrite]
 *
 * An article that already exists is left alone. Pass "overwrite" to replace it; its current fields and meta are saved to a backup file first.
 *
 * source-dir (default tools/content-gap/source, or CONTENT_GAP_SOURCE) must hold the two article .html files, the two
 * .jsonld files, the two image .zip packs and forklift-charts/ (made by charts.py). Everything is checked before any post is touched.
 */
$overwrite = in_array('overwrite', $args ?? [], true);
$source_args = array_values(array_filter($args ?? [], static fn($arg): bool => $arg !== 'overwrite'));
$root = rtrim((string) ($source_args[0] ?? getenv('CONTENT_GAP_SOURCE') ?: __DIR__ . '/source'), '/') . '/';
$img_root = rtrim(sys_get_temp_dir(), '/') . '/content-gap-' . wp_generate_password(8, false) . '/';
$link_map = [
    '/contactus.html' => '/contact.html',
    '/message.html' => '/contact.html#inquiry', '/quote.html' => '/contact.html#inquiry',
    '/knuckle-boom-marine-crane' => '/knuckle-boom-marine-cranes',
    '/telescopic-boom-marine-crane' => '/telescopic-boom-marine-cranes',
];
$lower_post_links = ['Structure-of-Marine-Cranes-id47987376', 'Types-of-Cranes-Used-on-A-Ship-id41221616'];

/**
 * URL of an upload from the uploads API, so custom upload locations and multisite work. It is site-relative when the uploads
 * live on the site's own host, and stays absolute when they are served from another host or CDN.
 */
function bl_upload_path(int $id): string {
    $url = trailingslashit(wp_get_upload_dir()['baseurl']) . get_post_meta($id, '_wp_attached_file', true);
    return strcasecmp((string) wp_parse_url($url, PHP_URL_HOST), (string) wp_parse_url(home_url(), PHP_URL_HOST)) === 0 ? wp_make_link_relative($url) : $url;
}

/** Existing posts are skipped unless "overwrite" is passed; with it, the current post and all its meta are written to a backup file first. */
function bl_backup_post(int $id, string $slug): void {
    $snapshot = wp_json_encode(['post' => get_post($id, ARRAY_A), 'meta' => get_post_meta($id)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    $file = __DIR__ . '/backup-content-gap-' . $slug . '-' . gmdate('Ymd-His') . '.json';
    $bytes = is_string($snapshot) ? file_put_contents($file . '.tmp', $snapshot) : false;
    if (!is_string($snapshot) || $bytes !== strlen($snapshot) || !rename($file . '.tmp', $file)) {
        fwrite(STDERR, "Cannot write backup $file; {$slug} was not changed.\n");
        exit(1);
    }
    echo "backup: $file\n";
}

function bl_sideload(string $path, string $alt, int $post_id = 0): array {
    $name = basename($path);
    $existing = get_posts(['post_type' => 'attachment', 'post_status' => 'any', 'meta_key' => '_bl_gap_source', 'meta_value' => $name, 'posts_per_page' => 1, 'lang' => '']);
    if ($existing) { $id = $existing[0]->ID; }
    else {
        $tmp = wp_tempnam($name); copy($path, $tmp);
        $id = media_handle_sideload(['name' => preg_replace('/-\d+x\d+(\.\w+)$/', '$1', $name), 'tmp_name' => $tmp], $post_id, null, ['post_author' => 1]);
        if (is_wp_error($id)) { throw new Exception($name . ': ' . $id->get_error_message()); }
        update_post_meta($id, '_bl_gap_source', $name);
    }
    if ($alt !== '') { update_post_meta($id, '_wp_attachment_image_alt', $alt); }
    if (function_exists('pll_set_post_language')) { pll_set_post_language($id, 'en'); }
    return [$id, bl_upload_path($id)];
}

$articles = [
    [
        'file' => 'marine-crane-buying-guide', 'imgdir' => 'marine', 'zip' => 'marine-crane-buying-guide-images.zip',
        'title' => 'Marine Crane Buying Guide: Knuckle Boom vs Telescopic Boom and How to Size One',
        'slug' => 'marine-crane-buying-guide', 'date' => '2026-09-29 09:00:00',
        'seo_title' => 'Marine Crane Buying Guide: Knuckle vs Telescopic | BOB-LIFT',
        'desc' => 'How to choose a marine crane for your vessel: knuckle boom vs telescopic boom, how to read a load chart and how to size one to your deck.',
        'tags' => ['marine crane', 'ship crane', 'knuckle boom marine crane', 'telescopic boom marine crane', 'lifting equipment'],
        'cover' => 'bob-lift-16-ton-telescopic-marine-crane-on-vessel.jpg',
        'cover_alt' => 'Blue BOB-LIFT 16 ton telescopic boom marine crane mounted on a vessel deck in a harbor, with a crew member handling rope below the boom',
        'schema' => 'marine-schema.jsonld', 'keywords' => 'marine crane, ship crane, marine deck crane, knuckle boom marine crane, telescopic boom marine crane, marine crane load chart',
    ],
    [
        'file' => 'forklift-jib-crane-guide', 'imgdir' => 'fork', 'zip' => 'forklift-jib-crane-images.zip', 'extra_dir' => 'forklift-charts',
        'title' => 'Forklift Jib Crane Guide: How a Fly Jib Turns Your Forklift Into a Crane',
        'slug' => 'forklift-jib-crane-guide', 'date' => '2026-09-28 09:00:00',
        'seo_title' => 'Forklift Jib Crane Guide: Fly Jib vs Fork Jib | BOB-LIFT',
        'desc' => 'What a forklift jib crane is, how hydraulic fly jibs differ from simple fork jibs, how capacity falls with reach, and how to match one to your forklift.',
        'tags' => ['forklift jib crane', 'forklift fly jib crane', 'forklift crane attachment', 'forklift boom attachment'],
        'cover' => '4-ton-forklift-fly-jib-crane-301a3ed016-900x700.jpg',
        'cover_alt' => 'BOB-LIFT 4 ton forklift fly jib crane mounted on a forklift with the boom extended',
        'schema' => 'forklift-schema.jsonld', 'keywords' => 'forklift jib crane, forklift fly jib crane, forklift crane attachment, forklift boom attachment',
    ],
];

// Preflight: every source file and every image the article uses must exist before anything is written.
$missing = [];
foreach ($articles as $a) {
    foreach ([$a['file'] . '.html', $a['schema'], $a['zip']] as $f) {
        if (!is_file($root . $f)) { $missing[] = $root . $f; }
    }
}
if (!class_exists('ZipArchive')) { $missing[] = 'PHP ZipArchive extension'; }
if ($missing) { fwrite(STDERR, "Missing source files:\n  " . implode("\n  ", $missing) . "\n"); exit(1); }
foreach ($articles as $a) {
    $dir = $img_root . $a['imgdir'] . '/';
    wp_mkdir_p($dir);
    $zip = new ZipArchive();
    if ($zip->open($root . $a['zip']) !== true) { fwrite(STDERR, 'Cannot open ' . $a['zip'] . "\n"); exit(1); }
    foreach (range(0, $zip->numFiles - 1) as $i) {
        $entry = $zip->getNameIndex($i);
        if (substr($entry, -1) !== '/') { file_put_contents($dir . basename($entry), $zip->getFromIndex($i)); }
    }
    $zip->close();
    if (!empty($a['extra_dir']) && is_dir($root . $a['extra_dir'])) {
        foreach (glob($root . $a['extra_dir'] . '/*') as $f) { copy($f, $dir . basename($f)); }
    }
    preg_match_all('/<img\b[^>]*\bsrc="([^"]+)"/i', file_get_contents($root . $a['file'] . '.html'), $srcs);
    foreach (array_merge($srcs[1], [$a['cover']]) as $src) {
        if (!is_file($dir . basename($src))) { $missing[] = $a['imgdir'] . ': ' . basename($src); }
    }
}
if (!get_term_by('slug', 'industry-insight', 'category')) { $missing[] = 'category: industry-insight (create it first)'; }
if ($missing) { fwrite(STDERR, "Missing images:\n  " . implode("\n  ", $missing) . "\n"); exit(1); }
$cat = get_term_by('slug', 'industry-insight', 'category');
$report = [];
foreach ($articles as $a) {
    $html = file_get_contents($root . $a['file'] . '.html');
    // images: collect alt by src, upload, swap
    $post_id = 0;
    $existing = get_posts(['post_type' => 'post', 'name' => $a['slug'], 'post_status' => 'any', 'posts_per_page' => 1, 'lang' => '']);
    if ($existing) { $post_id = $existing[0]->ID; }
    $resuming = $existing && $existing[0]->post_status === 'draft';
    if ($existing && !$overwrite && !$resuming) {
        $report[] = $post_id . ' ' . get_permalink($post_id) . ' (exists, left unchanged; pass "overwrite" to replace it)';
        continue;
    }
    if ($existing && !$resuming) { bl_backup_post($post_id, $a['slug']); }
    $html = preg_replace_callback('/<img\b[^>]*>/i', function ($m) use ($a, $img_root, $post_id) {
        $tag = $m[0];
        preg_match('/src="([^"]+)"/', $tag, $s); preg_match('/alt="([^"]*)"/', $tag, $al);
        $file = basename($s[1]);
        $path = $img_root . $a['imgdir'] . '/' . $file;
        if (!is_file($path)) { throw new Exception('missing image ' . $file); }
        [, $url] = bl_sideload($path, html_entity_decode($al[1] ?? '', ENT_QUOTES), $post_id);
        return str_replace($s[1], $url, $tag);
    }, $html);
    foreach ($link_map as $from => $to) { $html = str_replace('href="' . $from . '"', 'href="' . $to . '"', $html); }
    foreach ($lower_post_links as $l) { $html = str_replace('href="/' . $l . '.html"', 'href="/' . strtolower($l) . '.html"', $html); }
    $postarr = [
        // New articles are staged as drafts and published after their images and meta are complete (an interrupted run resumes).
        'post_type' => 'post', 'post_status' => ($existing ? $existing[0]->post_status : 'draft'), 'post_title' => $a['title'], 'post_name' => $a['slug'],
        'post_content' => $html, 'post_excerpt' => $a['desc'], 'post_author' => 1,
        'post_date' => $a['date'], 'post_date_gmt' => get_gmt_from_date($a['date']), 'post_category' => [$cat->term_id],
        'comment_status' => 'closed', 'ping_status' => 'closed',
    ];
    if ($post_id) { $postarr['ID'] = $post_id; $post_id = wp_update_post($postarr, true); } else { $post_id = wp_insert_post($postarr, true); }
    if (is_wp_error($post_id)) { throw new Exception($post_id->get_error_message()); }
    wp_set_post_tags($post_id, $a['tags']);
    [$cover_id] = bl_sideload($img_root . $a['imgdir'] . '/' . $a['cover'], $a['cover_alt'], $post_id);
    set_post_thumbnail($post_id, $cover_id);
    update_post_meta($post_id, '_seo_title', $a['seo_title']);
    update_post_meta($post_id, '_seo_description', $a['desc']);
    update_post_meta($post_id, '_seo_keywords', $a['keywords']);
    // JSON-LD: keep it with {{home}} in place of the old domain so it follows whichever host serves the site
    $schema = file_get_contents($root . $a['schema']);
    $schema = str_replace('https://www.bobliftcrane.com', '{{home}}', $schema);
    $schema = str_replace('{{home}}/marine-crane-buying-guide"', '{{home}}/marine-crane-buying-guide.html"', $schema);
    $schema = str_replace('{{home}}/marine-crane-buying-guide#', '{{home}}/marine-crane-buying-guide.html#', $schema);
    $schema = str_replace('"image": "bob-lift-16-ton-telescopic-marine-crane-on-vessel.jpg"', '"image": "' . (str_starts_with(bl_upload_path($cover_id), 'http') ? '' : '{{home}}') . bl_upload_path($cover_id) . '"', $schema);
    $schema = str_replace('"item": "{{home}}/Industry-News.html"', '"item": "{{home}}/news.html"', $schema);
    $schema = str_replace('{{home}}/wp-content/uploads/2026/09/4-ton-forklift-fly-jib-crane-301a3ed016-900x700.jpg', (str_starts_with(bl_upload_path($cover_id), 'http') ? '' : '{{home}}') . bl_upload_path($cover_id), $schema);
    update_post_meta($post_id, '_article_schema_jsonld', wp_slash($schema));
    if (get_post_status($post_id) === 'draft') { wp_update_post(['ID' => $post_id, 'post_status' => 'publish']); }
    if (function_exists('pll_set_post_language')) { pll_set_post_language($post_id, 'en'); }
    $report[] = $post_id . ' ' . get_permalink($post_id);
}
echo implode("\n", $report), "\n";
// Remove the unpacked images.
foreach (glob($img_root . '*/*') ?: [] as $f) { @unlink($f); }
foreach (glob($img_root . '*', GLOB_ONLYDIR) ?: [] as $d) { @rmdir($d); }
@rmdir($img_root);
