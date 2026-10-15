<?php
/**
 * The Social Media page was rebuilt around real posts and account cards (inc/data.php defaults).
 * ja/zh still hold the old platform lists that all pointed at /contact.html, the old content cards
 * and copy, which would override the new defaults. This clears them in every language and gives the
 * Chinese page the Chinese-language short videos. A language whose lists already hold a real https://
 * account link is left alone, so re-running after accounts are filled in does not wipe them.
 *
 *   wp eval-file ../tools/home/social-media.php dry-run
 *   wp eval-file ../tools/home/social-media.php
 */
$dry_run = in_array('dry-run', $args ?? [], true);
$clear = ['labels', 'panel', 'featured_platforms', 'major_platforms', 'secondary_platforms', 'content_cards', 'cta'];
$zh_videos = [];
foreach ([['knuckle-zh', '折臂随车起重机'], ['telescopic-zh', '直臂随车起重机'], ['marine-zh', '折臂船用起重机']] as [$file, $title]) {
    $zh_videos[] = ['title' => $title, 'video' => ['id' => 0, 'path' => 'videos/social/' . $file . '.mp4'], 'poster' => ['id' => 0, 'path' => 'videos/social/' . $file . '.webp']];
}
foreach (['boblift_theme_options', 'boblift_theme_options_ja', 'boblift_theme_options_zh', 'boblift_theme_options_es', 'boblift_theme_options_ru'] as $key) {
    $options = get_option($key);
    if (!is_array($options)) {
        continue;
    }
    $page = is_array($options['social_media_page'] ?? null) ? $options['social_media_page'] : [];
    // Only the old placeholder lists are cleared: a list holding any real https:// account link is left alone.
    $has_real_link = false;
    foreach (['featured_platforms', 'major_platforms', 'secondary_platforms'] as $group) {
        foreach ((array) ($page[$group] ?? []) as $row) {
            if (is_array($row) && preg_match('#^https?://#i', trim((string) ($row['href'] ?? '')))) {
                $has_real_link = true;
            }
        }
    }
    $had = $has_real_link ? [] : array_values(array_intersect($clear, array_keys($page)));
    foreach ($had as $field) {
        unset($page[$field]);
    }
    // The AI-made factory hero was replaced by one built from a real workshop frame; only the untouched old path is swapped.
    $old_hero = 'images/social-media/social-media-hero-factory.webp';
    $set_hero = (int) ($page['hero']['image']['id'] ?? 0) === 0 && (string) ($page['hero']['image']['path'] ?? '') === $old_hero;
    if ($set_hero) {
        $page['hero']['image'] = ['id' => 0, 'path' => 'images/social-media/social-media-hero-workshop.webp'];
        unset($page['hero']['image_alt']);
    }
    // Only an absent or empty list is initialised; a list an editor has set up (own videos or uploads) is never replaced.
    $set_videos = $key === 'boblift_theme_options_zh' && empty($page['videos']);
    if ($set_videos) {
        $page['videos'] = $zh_videos;
    }
    $changes = array_merge($had ? ['cleared ' . implode(', ', $had)] : [], $set_hero ? ['new hero photo'] : [], $set_videos ? ['set Chinese videos'] : [], $has_real_link ? ['kept (real account links found)'] : []);
    echo $key . ': ' . ($changes ? implode('; ', $changes) : 'unchanged') . "\n";
    if (($had || $set_hero || $set_videos) && !$dry_run) {
        $options['social_media_page'] = $page;
        update_option($key, $options);
    }
}
