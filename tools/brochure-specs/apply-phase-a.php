<?php
/**
 * Update English products to the brochure specs and trash products the brochure does not have.
 *
 * Usage (from the WordPress root): wp eval-file <path>/apply-phase-a.php [dry-run]
 * Writes a backup of every changed field next to this file before saving.
 */

$dir = __DIR__;
$dry = in_array('dry-run', $args ?? [], true);
$plan = json_decode((string) file_get_contents($dir . '/phase-a.json'), true);

$find = static function (string $slug): ?WP_Post {
    // WordPress renames trashed posts to "<slug>__trashed", so a rerun still finds them.
    $posts = get_posts(['post_type' => 'product', 'post_name__in' => [$slug, $slug . '__trashed'], 'post_status' => ['publish', 'draft', 'pending', 'private', 'trash'], 'posts_per_page' => 5, 'lang' => 'en']);
    foreach ($posts as $post) {
        if (!function_exists('pll_get_post_language') || pll_get_post_language($post->ID) === 'en') {
            return $post;
        }
    }
    return null;
};

$backup = [];
$missing = [];
foreach ($plan['updates'] as $u) {
    $post = $find($u['slug']);
    if (!$post) {
        $missing[] = $u['slug'];
        continue;
    }
    $id = $post->ID;
    $keys = ['_model', '_specs', '_capacity', '_capacity_value', '_seo_title', '_seo_description', '_short_description', '_features'];
    $backup[$u['slug']] = ['title' => $post->post_title, 'content' => $post->post_content, 'excerpt' => $post->post_excerpt] + array_combine($keys, array_map(static fn($k) => get_post_meta($id, $k, true), $keys));
    if ($dry) {
        continue;
    }
    update_post_meta($id, '_model', $u['model']);
    update_post_meta($id, '_specs', wp_slash(wp_json_encode($u['specs'], JSON_UNESCAPED_UNICODE)));
    update_post_meta($id, '_capacity', $u['capacity']);
    update_post_meta($id, '_capacity_value', (string) $u['capacity_value']);
    if ($u['title'] !== $u['old_title']) {
        // Rewrite "2.3 Ton Forklift…" style mentions of the old tonnage in the product's own copy.
        preg_match('/^([\d.]+)\s*Ton\b/', $u['old_title'], $old);
        preg_match('/^([\d.]+)\s*Ton\b/', $u['title'], $new);
        $pattern = '/(?<![\d.])' . preg_quote($old[1], '/') . '(\s*-?\s*)(ton|Ton|TON)(?=\s+(?:Knuckle|Forklift|Telescopic))/';
        $retonnage = static fn(string $text): string => (string) preg_replace($pattern, $new[1] . '$1$2', $text);
        wp_update_post(wp_slash(['ID' => $id, 'post_title' => $u['title'], 'post_content' => $retonnage($post->post_content), 'post_excerpt' => $retonnage($post->post_excerpt)]));
        foreach (['_seo_title', '_seo_description', '_short_description', '_features'] as $key) {
            $value = (string) get_post_meta($id, $key, true);
            if ($value !== '') {
                update_post_meta($id, $key, wp_slash($retonnage(str_replace($u['old_title'], $u['title'], $value))));
            }
        }
    }
}

$trashed = [];
foreach ($plan['remove'] as $r) {
    $post = $find($r['slug']);
    if (!$post) {
        $missing[] = $r['slug'];
        continue;
    }
    // A product the brochure lacks goes in every language, so its translations are trashed too.
    $ids = function_exists('pll_get_post_translations') ? array_values(pll_get_post_translations($post->ID)) : [$post->ID];
    foreach (array_unique(array_merge([$post->ID], $ids)) as $id) {
        if (get_post_status($id) !== 'trash') {
            $trashed[] = $id . ' ' . get_the_title($id);
            if (!$dry) {
                wp_trash_post($id);
            }
        }
    }
}

if (!$dry) {
    file_put_contents($dir . '/backup-phase-a-' . gmdate('Ymd-His') . '.json', wp_json_encode($backup, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}
printf("%s: updated %d, trashed %d, missing %d\n", $dry ? 'DRY RUN' : 'DONE', count($backup), count($trashed), count($missing));
foreach ($trashed as $t) echo "  trash $t\n";
foreach ($missing as $m) echo "  missing $m\n";
