<?php
require_once dirname(__DIR__) . '/backup-dir.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
/**
 * Imports the 7 Ton Forklift Fly Jib Crane (CC165) and 800 kg Pickup Crane Without Outriggers (SQ08ZA3) products:
 *
 *   tools/content-gap/fetch-old-images.sh [source-dir]     (once: downloads the old site's photos)
 *   wp eval-file tools/content-gap/import-products.php [source-dir] [overwrite]
 *
 * A product that already exists is left alone. Pass "overwrite" to replace it; its current fields and meta are saved to a backup file first.
 *
 * source-dir defaults to tools/content-gap/source (or CONTENT_GAP_SOURCE); its products/ folder holds the photos.
 * The 7 t CC165 article photos must already be in the media library (run import-articles.php first). All files are checked before any post is touched.
 */
$overwrite = in_array('overwrite', $args ?? [], true);
$source_args = array_values(array_filter($args ?? [], static fn($arg): bool => $arg !== 'overwrite'));
$old = rtrim((string) ($source_args[0] ?? getenv('CONTENT_GAP_SOURCE') ?: __DIR__ . '/source'), '/') . '/products/';
$uploads = wp_get_upload_dir()['basedir'];

/** Term assignments of a post by taxonomy (categories, tags, ...), so a backup can restore what an import replaces. */
function bl_post_terms(int $id): array {
    $terms = [];
    foreach (get_object_taxonomies(get_post_type($id)) as $taxonomy) {
        $assigned = wp_get_object_terms($id, $taxonomy, ['fields' => 'all']);
        $terms[$taxonomy] = is_wp_error($assigned) ? [] : array_map(static fn(WP_Term $term): array => ['term_id' => $term->term_id, 'slug' => $term->slug, 'name' => $term->name], $assigned);
    }
    return $terms;
}

/** Existing posts are skipped unless "overwrite" is passed; with it, the current post and all its meta are written to a backup file first. */
function bl_backup_post(int $id, string $slug): void {
    $snapshot = wp_json_encode(['post' => get_post($id, ARRAY_A), 'meta' => get_post_meta($id), 'terms' => bl_post_terms($id)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    $file = boblift_tools_backup_dir() . '/backup-content-gap-' . $slug . '-' . gmdate('Ymd-His') . '.json';
    $bytes = is_string($snapshot) ? file_put_contents($file . '.tmp', $snapshot) : false;
    if (!is_string($snapshot) || $bytes !== strlen($snapshot) || !rename($file . '.tmp', $file)) {
        fwrite(STDERR, "Cannot write backup $file; {$slug} was not changed.\n");
        exit(1);
    }
    echo "backup: $file\n";
}

// Reuse an attachment already in the library when the file is byte-identical; otherwise upload it.
function bl_media(string $path, string $name, string $alt, string $uploads, int $parent): int {
    $md5 = md5_file($path); $size = filesize($path);
    $cache = get_option('bl_gap_md5_index', []);
    if (isset($cache[$md5]) && get_post($cache[$md5])) { return $cache[$md5]; }
    global $wpdb;
    $rows = $wpdb->get_col("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_wp_attached_file' AND meta_value REGEXP '\\\\.(jpg|jpeg|png)$'");
    foreach ($rows as $pid) {
        $f = $uploads . '/' . get_post_meta($pid, '_wp_attached_file', true);
        if (is_file($f) && filesize($f) === $size && md5_file($f) === $md5) { $cache[$md5] = (int) $pid; update_option('bl_gap_md5_index', $cache, false); return (int) $pid; }
    }
    $tmp = wp_tempnam($name); copy($path, $tmp);
    $id = media_handle_sideload(['name' => $name, 'tmp_name' => $tmp], $parent, null, ['post_author' => 1]);
    if (is_wp_error($id)) { throw new Exception($name . ': ' . $id->get_error_message()); }
    update_post_meta($id, '_wp_attachment_image_alt', $alt);
    if (function_exists('pll_set_post_language')) { pll_set_post_language($id, 'en'); }
    $cache[$md5] = $id; update_option('bl_gap_md5_index', $cache, false);
    return $id;
}
function bl_url(int $id): string { return wp_make_link_relative(trailingslashit(wp_get_upload_dir()['baseurl']) . get_post_meta($id, '_wp_attached_file', true)); }
function bl_abs(int $id): string { return trailingslashit(wp_get_upload_dir()['baseurl']) . get_post_meta($id, '_wp_attached_file', true); }

$products = [
 [
  'slug' => '7-ton-forklift-fly-jib-crane', 'title' => '7 Ton Forklift Fly Jib Crane', 'model' => 'CC165', 'cat' => 'forklift-fly-jib-cranes',
  'capacity' => '7 ton', 'cap_value' => '7', 'boom' => 'fly-jib', 'apps' => 'logistics,construction',
  'old_url' => 'https://www.bobliftcrane.com/7-ton-forklift-fly-jib-crane.html',
  'short' => 'The BOB-LIFT CC165 forklift fly jib crane has a maximum lifting capacity of 7000 kg, a maximum lifting moment of 180 kN·m, a maximum working range of 15200 mm and a self weight of 2000 kg.',
  'content' => '<p>The CC165 is a forklift fly jib crane rated at 7 t with a 180 kN·m lifting moment and a maximum working range of 15.2 m. It weighs 2000 kg and is matched to 10 t forklifts.</p>',
  'specs' => [['Max Lifting Capacity','7000 kg'],['Max Lifting Moment','180 kN·m'],['Max Working Range','15200 mm'],['Min Working Range','2500 mm'],['Max Lifting Height','17500 mm (3 m mast) / 19000 mm (4.5 m mast)'],['Rated Working Pressure','20 MPa'],['Rated Flow','80 L/min'],['Self Weight','2000 kg'],['Matching Forklifts','10 t']],
  'features' => [
    ['title'=>'Fits irregular loads','text'=>'Lifts odd-shaped objects over high spaces or onto simple platforms.'],
    ['title'=>'Compact for tight spaces','text'=>'Small enough to work in construction areas and pits.'],
    ['title'=>'Telescopic working capability','text'=>'The telescopic boom adjusts reach to the job.'],
  ],
  'main' => [ // [file, name, alt]
    [$old.'fork-7ton-main-1.jpg','7-ton-forklift-fly-jib-crane-800-800.jpg'],
    [$old.'fork-7ton-main-2.jpg','7-ton-forklift-fly-jib-crane-1-800-800.jpg'],
  ],
  'extra_existing' => ['7-ton-forklift-fly-jib-crane-b3d36eaa2d-900x700.jpg', '7-ton-forklift-fly-jib-crane-4a2bdf81b8-900x700.jpg'], // real photos from the article pack, already in the library
  'details' => ['fork-7ton-detail-1.jpg','fork-7ton-detail-2.jpg','fork-7ton-detail-3.jpg','fork-7ton-detail-4.jpg','fork-7ton-detail-5.jpg','fork-7ton-detail-6.jpg','fork-7ton-detail-7.jpg','fork-7ton-detail-8.jpg'],
  'thumb_first_existing' => true,
 ],
 [
  'slug' => '800-kg-pickup-crane-without-outriggers', 'title' => '800 kg Pickup Crane Without Outriggers', 'model' => 'SQ08ZA3', 'cat' => 'pickup-cranes',
  'capacity' => '0.8 ton', 'cap_value' => '0.8', 'boom' => 'knuckle-boom', 'apps' => 'logistics,agriculture,construction',
  'old_url' => 'https://www.bobliftcrane.com/800-kg-pickup-crane-without-outriggers.html',
  'short' => 'The BOB-LIFT SQ08ZA3 pickup crane has a maximum lifting capacity of 800 kg, a maximum lifting moment of 1 t·m and a self weight of 280 kg. It needs 850 mm of installation space and slews 330°.',
  'content' => '<p>The SQ08ZA3 is an 800 kg knuckle boom pickup crane without outriggers and a maximum lifting moment of 1 t·m. It runs on a recommended 2.5 kW power supply with 8 L/min of hydraulic flow at 16 MPa, weighs 280 kg and offers 330° slewing.</p>',
  'specs' => [['Max Lifting Capacity','800 kg'],['Max Lifting Moment','1 t·m'],['Recommended Power','2.5 kW'],['Hydraulic System Flow','8 L/min'],['Hydraulic System Pressure','16 MPa'],['Oil Tank Capacity','15 L'],['Installation Space','850 mm'],['Self Weight','280 kg'],['Rotation Angle','330°']],
  'features' => [
    ['title'=>'Strong steel plate structure','text'=>'Steel plate construction improves safety in use, with a hydraulic drive.'],
    ['title'=>'Balancing valves and hydraulic locks','text'=>'Added protection when holding a load.'],
    ['title'=>'Tested parts','text'=>'Built from thoroughly tested parts for stable, reliable performance.'],
    ['title'=>'Simple to operate','text'=>'Easy controls cut work time, and the hook rotates for easy hanging and pick-up.'],
  ],
  'main' => [
    [$old.'pickup-main-1.jpg','0-800-800.jpg'],[$old.'pickup-main-2.jpg','1-800-800.jpg'],[$old.'pickup-main-3.jpg','2-800-800.jpg'],
    [$old.'pickup-main-4.jpg','7-800-800.jpg'],[$old.'pickup-main-5.jpg','8-800-800.jpg'],
  ],
  'extra_existing' => [],
  'drawing' => [$old.'pickup-SQ08Z-drawing.jpg','SQ08Z-dimension-drawing-and-load-chart.jpg','SQ08Z dimension drawing and load chart'],
  'details' => ['pickup-detail-1.jpg','pickup-detail-2.jpg','pickup-detail-3.jpg','pickup-detail-4.jpg','pickup-detail-5.jpg','pickup-detail-6.jpg','pickup-detail-7.jpg'],
 ],
];
$out = [];
// Preflight: every photo must exist, and the CC165 article photos must already be imported, before any post is written.
$missing = [];
foreach ($products as $p) {
    $files = array_merge(array_column($p['main'], 0), array_map(static fn(string $f): string => $old . $f, $p['details']), !empty($p['drawing']) ? [$p['drawing'][0]] : []);
    foreach ($files as $f) {
        if (!is_file($f)) { $missing[] = $f; }
    }
    if (!get_term_by('slug', $p['cat'], 'product_category')) { $missing[] = 'product category: ' . $p['cat']; }
    foreach ($p['extra_existing'] as $title) {
        if (!get_posts(['post_type'=>'attachment','meta_key'=>'_bl_gap_source','meta_value'=>$title,'post_status'=>'any','numberposts'=>1,'lang'=>''])) { $missing[] = 'media library item: ' . $title . ' (run import-articles.php first)'; }
    }
}
if ($missing) { fwrite(STDERR, "Missing sources:\n  " . implode("\n  ", $missing) . "\n"); exit(1); }
foreach ($products as $p) {
    // With Polylang only the English product counts as "existing"; a translation sharing the slug is never touched.
    $ex = get_posts(['post_type'=>'product','name'=>$p['slug'],'post_status'=>'any','numberposts'=>1,'lang'=>function_exists('pll_default_language') ? 'en' : '']);
    // A product left as a draft by an interrupted run is resumed; a published one is protected unless "overwrite" is passed.
    $resuming = $ex && $ex[0]->post_status === 'draft' && get_post_meta($ex[0]->ID, '_content_gap_staging', true) === '1';
    if ($ex && !$overwrite && !$resuming) {
        $out[] = $ex[0]->ID . ' ' . get_permalink($ex[0]) . ' (exists, left unchanged; pass "overwrite" to replace it)';
        continue;
    }
    if ($ex && !$resuming) { bl_backup_post($ex[0]->ID, $p['slug']); }
    // New products are staged as drafts and published only after their media and meta are complete, so an interrupted run can be resumed.
    $arr = ['post_type'=>'product','post_status'=>($ex ? $ex[0]->post_status : 'draft'),'post_title'=>$p['title'],'post_name'=>$p['slug'],'post_author'=>0,
        'post_content'=>"<!-- wp:paragraph -->\n" . $p['content'] . "\n<!-- /wp:paragraph -->", 'comment_status'=>'closed','ping_status'=>'closed'];
    if ($ex) { $arr['ID'] = $ex[0]->ID; $id = wp_update_post($arr, true); } else { $id = wp_insert_post($arr, true); }
    if (is_wp_error($id)) { throw new Exception($id->get_error_message()); }
    // Only a new post, or one already staged by this importer, is marked for automatic publication; an editorial draft stays a draft.
    if (get_post_status($id) === 'draft' && (!$ex || $resuming)) { update_post_meta($id, '_content_gap_staging', '1'); }
    $gallery = []; $thumb = 0;
    foreach ($p['extra_existing'] as $title) {
        $a = get_posts(['post_type'=>'attachment','meta_key'=>'_bl_gap_source','meta_value'=>$title,'post_status'=>'any','numberposts'=>1,'lang'=>'']);
        if ($a) { $gallery[] = ['image'=>bl_abs($a[0]->ID),'label'=>$p['title']]; if (!$thumb) { $thumb = $a[0]->ID; } }
    }
    foreach ($p['main'] as [$f, $n]) {
        $mid = bl_media($f, $n, $p['title'], $uploads, $id);
        $gallery[] = ['image'=>bl_abs($mid),'label'=>$p['title']]; if (!$thumb) { $thumb = $mid; }
    }
    $details = [];
    foreach ($p['details'] as $f) {
        $b = basename($f);
        $mid = bl_media($old.$f, $b, $p['title'] . ' detail', $uploads, $id);
        $details[] = ['image'=>bl_abs($mid),'label'=>$p['title']];
    }
    $specs = array_map(fn($r)=>['label'=>$r[0],'value'=>$r[1]], $p['specs']);
    $meta = ['_model'=>$p['model'],'_capacity'=>$p['capacity'],'_capacity_value'=>$p['cap_value'],'_boom_type'=>$p['boom'],'_status'=>'in-stock',
        '_applications'=>$p['apps'],'_short_description'=>$p['short'],'_seo_title'=>'China ' . $p['title'] . ' - BOB-LIFT','_seo_description'=>$p['short'],
        '_old_site_url'=>$p['old_url'],'_model_aliases'=>'','_detail_body_ready'=>1,'_detail_images_ready'=>1,
        '_specs'=>wp_json_encode($specs, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),'_features'=>wp_json_encode($p['features'], JSON_UNESCAPED_UNICODE),
        '_gallery'=>wp_json_encode($gallery, JSON_UNESCAPED_SLASHES),'_detail_images'=>wp_json_encode($details, JSON_UNESCAPED_SLASHES)];
    foreach ($meta as $k => $v) { update_post_meta($id, $k, wp_slash($v)); }
    if (!empty($p['drawing'])) {
        $did = bl_media($p['drawing'][0], $p['drawing'][1], $p['drawing'][2], $uploads, $id);
        update_post_meta($id, '_drawings', wp_slash(wp_json_encode([['id'=>$did,'label'=>$p['drawing'][2]]], JSON_UNESCAPED_UNICODE)));
        update_post_meta($id, '_brochure_source', 'BOB-LIFT technical drawing SQ08Z (old site page, 2026-10-15)');
    }
    set_post_thumbnail($id, $thumb);
    $term = get_term_by('slug', $p['cat'], 'product_category');
    wp_set_object_terms($id, [$term->term_id], 'product_category');
    if (function_exists('pll_set_post_language')) { pll_set_post_language($id, 'en'); }
    if (get_post_status($id) === 'draft' && get_post_meta($id, '_content_gap_staging', true) === '1') { wp_update_post(['ID'=>$id,'post_status'=>'publish']); delete_post_meta($id, '_content_gap_staging'); }
    $out[] = $id . ' ' . get_permalink($id);
}
echo implode("\n", $out), "\n";
