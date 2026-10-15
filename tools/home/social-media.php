<?php
/**
 * The Social Media page was rebuilt around real posts and account cards (inc/data.php defaults).
 * ja/zh may still hold the old platform lists that all pointed at /contact.html, the old content cards and copy, which
 * would override the new defaults. Each saved field is cleared only when it still holds that old built-in content;
 * a field with any edit (a real account URL, uploaded card images, changed text, a disabled card) is kept and reported. The Chinese page also gets
 * the Chinese-language short videos when it has none.
 *
 *   wp eval-file ../tools/home/social-media.php dry-run
 *   wp eval-file ../tools/home/social-media.php
 */
$dry_run = in_array('dry-run', $args ?? [], true);
$clear = ['labels', 'panel', 'featured_platforms', 'major_platforms', 'secondary_platforms', 'content_cards', 'cta'];
$legacy = json_decode((string) file_get_contents(__DIR__ . '/social-media-legacy-defaults.json'), true) ?: [];
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
    // A field is cleared only when its complete saved value equals the old built-in default (social-media-legacy-defaults.json);
    // any edit to any part of it (text, link, label, enabled flag, image) keeps the whole field.
    $same = static function ($a, $b) use (&$same): bool {
        if (is_array($a) && is_array($b)) {
            if (array_keys($a) !== array_keys($b) && (array_is_list($a) || array_is_list($b))) {
                return false;
            }
            ksort($a);
            ksort($b);
            if (array_keys($a) !== array_keys($b)) {
                return false;
            }
            foreach ($a as $k => $v) {
                if (!$same($v, $b[$k])) {
                    return false;
                }
            }
            return true;
        }
        return (string) $a === (string) $b;
    };
    $had = [];
    foreach ($clear as $field) {
        if (!array_key_exists($field, $page)) {
            continue;
        }
        if (isset($legacy[$field]) && $same($page[$field], $legacy[$field])) {
            $had[] = $field;
        } else {
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
