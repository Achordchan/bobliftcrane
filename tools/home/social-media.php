<?php
/**
 * The Social Media page was rebuilt around real posts and account cards (inc/data.php defaults).
 * ja/zh may still hold the old platform lists that all pointed at /contact.html, the old content cards and copy, which
 * would override the new defaults. Each saved field is cleared only when it still holds that old built-in content;
 * a list with a real account URL, uploaded card images or edited texts is kept and reported. The Chinese page also gets
 * the Chinese-language short videos when it has none.
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
    // Each field is cleared only when it still holds the old built-in content; anything an editor customised stays.
    $is_placeholder_list = static function ($rows): bool {
        foreach ((array) $rows as $row) {
            $href = is_array($row) ? trim((string) ($row['href'] ?? '')) : '';
            if ($href !== '' && $href !== '/contact.html') {
                return false; // a real account URL
            }
        }
        return true;
    };
    $is_default_text = static fn($value, array $known): bool => in_array(trim((string) $value), $known, true);
    $is_placeholder = [
        'labels' => static fn($v): bool => is_array($v) && $is_default_text($v['content_cards_label'] ?? '', ['', 'What You Can Follow']) && $is_default_text($v['content_cards_title'] ?? '', ['', 'Real content from real work.']),
        'panel' => static fn($v): bool => is_array($v) && $is_default_text($v['title'] ?? '', ['', 'Official Channels']),
        'cta' => static fn($v): bool => is_array($v) && $is_default_text($v['label'] ?? '', ['', 'Direct Consultation']),
        'content_cards' => static function ($v) use ($is_default_text): bool {
            foreach ((array) $v as $card) {
                $image = is_array($card) ? ($card['image'] ?? null) : null;
                $uploaded = is_array($image) && (int) ($image['id'] ?? 0) > 0;
                if ($uploaded || !$is_default_text($card['title'] ?? '', ['', 'Product updates', 'Factory news', 'Project communication', 'Export support'])) {
                    return false;
                }
            }
            return true;
        },
        'featured_platforms' => $is_placeholder_list,
        'major_platforms' => $is_placeholder_list,
        'secondary_platforms' => $is_placeholder_list,
    ];
    $had = [];
    foreach ($clear as $field) {
        if (array_key_exists($field, $page) && isset($is_placeholder[$field]) && $is_placeholder[$field]($page[$field])) {
            $had[] = $field;
        } elseif (array_key_exists($field, $page)) {
            echo "$key: kept customised $field\n";
        }
    }
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
    $changes = array_merge($had ? ['cleared ' . implode(', ', $had)] : [], $set_hero ? ['new hero photo'] : [], $set_videos ? ['set Chinese videos'] : [], []);
    echo $key . ': ' . ($changes ? implode('; ', $changes) : 'unchanged') . "\n";
    if (($had || $set_hero || $set_videos) && !$dry_run) {
        $options['social_media_page'] = $page;
        update_option($key, $options);
    }
}
