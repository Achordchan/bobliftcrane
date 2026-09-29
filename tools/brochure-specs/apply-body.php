<?php
/**
 * Write the product-page body text (正文) from the brochure, in English, Japanese and Chinese.
 *
 * Usage (from the WordPress root): wp eval-file <path>/apply-body.php [dry-run]
 * For every product in phase-c.json with a brochure row it writes, per language:
 * - a lead paragraph that puts the brochure figures into sentences (the specs grid holds the same numbers;
 *   this is the readable, indexable version),
 * - the brochure's optional attachments (knuckle and telescopic cranes that offer them),
 * - a CE sentence only when the model's series appears on the CE certificate printed in the brochure
 *   (marine cranes are not on it).
 * The old post_content is kept in _legacy_content the first time, and _detail_body_ready switches the
 * section on. Keyed by slug, so it can run again.
 */

$dir = __DIR__;
$dry = in_array('dry-run', $args ?? [], true);
$rows = [];
foreach (json_decode((string) file_get_contents($dir . '/brochure.json'), true) as $row) {
    $rows[$row['model']] = $row;
}
$products = json_decode((string) file_get_contents($dir . '/phase-c.json'), true)['products'];

// Series on the CE certificate page of the truck crane brochure.
$ce_series = ['SQ05Z', 'SQ08Z', 'SQ1Z', 'SQ2Z', 'SQ3.2Z', 'SQ4Z', 'SQ5Z', 'SQ6.3Z', 'SQ8Z', 'SQ10Z', 'SQ12Z', 'SQ14Z', 'SQ16Z', 'SQ20Z',
    'SQ25Z', 'SQ28Z', 'SQ30Z', 'SQ34Z', 'SQ2S', 'SQ3.2S', 'SQ4S', 'SQ5S', 'SQ6.3S', 'SQ8S', 'SQ10S', 'SQ12S', 'SQ14S', 'SQ16S', 'SQ20S',
    'BLZ90K', 'BLZ129K', 'ZM65', 'ZM75', 'ZT120', 'ZT160', 'SL70', 'SL150Z', 'CC85', 'CC105', 'SQ4ZG3', 'BLS3.0T', 'SQZ430'];

$series = [
    'Knuckle Boom Truck Mounted Crane' => ['en' => 'knuckle boom truck-mounted crane', 'ja' => 'ナックルブーム式トラック搭載クレーン', 'zh' => '折臂式随车起重机'],
    'Telescopic Boom Truck Mounted Crane' => ['en' => 'telescopic boom truck-mounted crane', 'ja' => '伸縮ブーム式トラック搭載クレーン', 'zh' => '伸缩臂式随车起重机'],
    'Knuckle Boom Marine Crane' => ['en' => 'knuckle boom marine crane', 'ja' => 'ナックルブーム式マリンクレーン', 'zh' => '折臂式船用起重机'],
    'Telescopic Boom Marine Crane' => ['en' => 'telescopic boom marine crane', 'ja' => '伸縮ブーム式マリンクレーン', 'zh' => '伸缩臂式船用起重机'],
];
$category_series = [
    'forklift-fly-jib-cranes' => ['en' => 'forklift fly jib crane', 'ja' => 'フォークリフト用フライジブクレーン', 'zh' => '叉车飞臂吊'],
    'spider-cranes' => ['en' => 'spider crane', 'ja' => 'スパイダークレーン', 'zh' => '蜘蛛吊'],
    'timber-cranes' => ['en' => 'timber crane', 'ja' => '木材クレーン', 'zh' => '抓木起重机'],
    'pickup-cranes' => ['en' => 'pickup crane', 'ja' => 'ピックアップ用クレーン', 'zh' => '皮卡吊'],
];

$cap = static fn(string $kg): array => (float) $kg >= 1000
    ? ['en' => rtrim(rtrim(number_format((float) $kg / 1000, 2, '.', ''), '0'), '.') . ' t', 'ja' => rtrim(rtrim(number_format((float) $kg / 1000, 2, '.', ''), '0'), '.') . ' t', 'zh' => rtrim(rtrim(number_format((float) $kg / 1000, 2, '.', ''), '0'), '.') . ' 吨']
    : ['en' => $kg . ' kg', 'ja' => $kg . ' kg', 'zh' => $kg . ' 公斤'];
$rotation = static function (string $value): array {
    $deg = (int) $value;
    return str_contains($value, 'Continuous')
        ? ['en' => "continuous {$deg}° slewing", 'ja' => "{$deg}°の連続旋回", 'zh' => "{$deg}° 连续回转"]
        : ['en' => "{$deg}° slewing", 'ja' => "{$deg}°の旋回", 'zh' => "{$deg}° 回转"];
};
$m = static fn(string $mm): string => rtrim(rtrim(number_format((float) $mm / 1000, 2, '.', ''), '0'), '.');

$attachments = [
    'en' => ['<h3>Optional attachments</h3>', '<ul>', '<li><strong>Hydraulic gripper</strong> opened and closed by its own cylinder to grab and release loads.</li>', '<li><strong>Clamshell bucket</strong> for loading, unloading and light digging of waste, chippings and soil.</li>', '<li><strong>Orange peel grab</strong> for scrap and bulk material.</li>', '<li><strong>Timber grapple</strong> for logs and timber.</li>', '<li><strong>Auger</strong> for tree-planting holes, utility poles, foundation piles and soil sampling.</li>', '</ul>'],
    'ja' => ['<h3>オプションアタッチメント</h3>', '<ul>', '<li><strong>油圧グリッパー</strong>：専用シリンダーで開閉し、荷をつかんで放します。</li>', '<li><strong>クラムシェルバケット</strong>：廃棄物、砕石、土砂の積み下ろしや軽掘削に。</li>', '<li><strong>オレンジピールグラブ</strong>：スクラップやばら物の荷役に。</li>', '<li><strong>木材グラップル</strong>：丸太や木材の荷役に。</li>', '<li><strong>オーガー</strong>：植樹穴、電柱、基礎杭の穴掘りや土壌サンプリングに。</li>', '</ul>'],
    'zh' => ['<h3>可选属具</h3>', '<ul>', '<li><strong>液压抓具</strong>：由独立油缸控制开合，抓取和释放物料。</li>', '<li><strong>贝壳斗</strong>：用于垃圾、碎石、土方的装卸和轻度挖掘。</li>', '<li><strong>桔瓣抓斗</strong>：用于废钢和散料装卸。</li>', '<li><strong>抓木器</strong>：用于原木和木材装卸。</li>', '<li><strong>钻杆</strong>：用于植树坑、电线杆、基础桩钻孔和取土。</li>', '</ul>'],
];


// Store the body as editor blocks (paragraph, heading, list) so the block editor opens it as normal,
// editable blocks instead of one "Classic" block.
$to_blocks = static function (array $lines): string {
    $blocks = [];
    foreach ($lines as $line) {
        if (str_starts_with($line, '<p>')) {
            $blocks[] = "<!-- wp:paragraph -->\n{$line}\n<!-- /wp:paragraph -->";
        } elseif (str_starts_with($line, '<h3>')) {
            $blocks[] = "<!-- wp:heading {\"level\":3} -->\n" . str_replace('<h3>', '<h3 class="wp-block-heading">', $line) . "\n<!-- /wp:heading -->";
        } elseif ($line === '<ul>') {
            $blocks[] = "<!-- wp:list -->\n<ul class=\"wp-block-list\">";
        } elseif (str_starts_with($line, '<li>')) {
            $blocks[count($blocks) - 1] .= "<!-- wp:list-item -->\n{$line}\n<!-- /wp:list-item -->";
        } elseif ($line === '</ul>') {
            $blocks[count($blocks) - 1] .= "</ul>\n<!-- /wp:list -->";
        }
    }
    return implode("\n\n", $blocks);
};

$report = [];
$written = 0;
foreach ($products as $product) {
    $row = $rows[$product['model']] ?? null;
    $post = get_page_by_path($product['slug'], OBJECT, 'product');
    if (!$row || !$post instanceof WP_Post) {
        $report[] = sprintf('%-12s skipped (%s)', $product['model'], $row ? 'no product' : 'no brochure row');
        continue;
    }
    $cats = wp_get_post_terms($post->ID, 'product_category', ['fields' => 'slugs']);
    $name = $series[$product['features_series'] ?? ''] ?? null;
    foreach (is_array($cats) ? $cats : [] as $cat) {
        $name = $name ?? ($category_series[$cat] ?? null);
    }
    if (!$name) {
        $report[] = sprintf('%-12s skipped (no series name)', $product['model']);
        continue;
    }
    $c = $cap((string) $row['cap']);
    $model = $product['model'];
    $lead = [];
    if ($row['kind'] === 'flyjib') {
        preg_match_all('/(\d+)\s*\(([\d.]+) m mast\)/', (string) $row['lift_height_mm'], $mast, PREG_SET_ORDER);
        $heights_en = implode(' or ', array_map(static fn(array $x): string => $m($x[1]) . ' m with a ' . $x[2] . ' m mast', $mast));
        $heights_ja = implode('、', array_map(static fn(array $x): string => $x[2] . ' mマストで' . $m($x[1]) . ' m', $mast));
        $heights_zh = implode('，', array_map(static fn(array $x): string => $x[2] . ' 米门架时 ' . $m($x[1]) . ' 米', $mast));
        $lead['en'] = "The {$model} is a {$name['en']} rated at {$c['en']}, with a working range of {$m($row['working_range_mm'])} m and a lifting height of {$heights_en}. It weighs {$row['weight']} kg and is matched to {$row['forklift']} forklifts.";
        $lead['ja'] = "{$model}は定格荷重{$c['ja']}の{$name['ja']}です。作業範囲は{$m($row['working_range_mm'])} m、揚程は{$heights_ja}。自重は{$row['weight']} kgで、{$row['forklift']}のフォークリフトに対応します。";
        $lead['zh'] = "{$model} 是一款额定起重量 {$c['zh']} 的{$name['zh']}，作业范围 {$m($row['working_range_mm'])} 米，起升高度{$heights_zh}。整机自重 {$row['weight']} 公斤，适配 {$row['forklift']} 叉车。";
    } elseif ($row['kind'] === 'spider') {
        $r = $rotation((string) $row['rotation']);
        $lead['en'] = "The {$model} is a {$name['en']} with a lifting capacity of {$c['en']}, a working radius of {$row['radius']} m and a lifting height of {$row['height']} m. Folded it measures {$row['dims']} mm, weighs {$row['weight']} kg and offers {$r['en']}.";
        $lead['ja'] = "{$model}は吊上げ能力{$c['ja']}の{$name['ja']}で、作業半径{$row['radius']} m、揚程{$row['height']} mです。格納時の寸法は{$row['dims']} mm、自重{$row['weight']} kg、{$r['ja']}が可能です。";
        $lead['zh'] = "{$model} 是一款起重量 {$c['zh']} 的{$name['zh']}，作业半径 {$row['radius']} 米，起升高度 {$row['height']} 米。收拢尺寸 {$row['dims']} 毫米，自重 {$row['weight']} 公斤，支持 {$r['zh']}。";
    } else {
        $r = $rotation((string) $row['rotation']);
        $space = !empty($row['space']) ? ['en' => " It needs {$row['space']} mm of installation space.", 'ja' => "取付スペースは{$row['space']} mmです。", 'zh' => "安装空间 {$row['space']} 毫米。"] : ['en' => '', 'ja' => '', 'zh' => ''];
        $lead['en'] = "The {$model} is a {$c['en']} {$name['en']} with a maximum lifting moment of {$row['moment']} t·m. It runs on a recommended {$row['power']} kW power supply with {$row['flow']} L/min of hydraulic flow at {$row['pressure']} MPa, weighs {$row['weight']} kg and offers {$r['en']}.{$space['en']}";
        $lead['ja'] = "{$model}は最大吊上げ能力{$c['ja']}、最大吊上げモーメント{$row['moment']} t·mの{$name['ja']}です。推奨動力{$row['power']} kW、油圧流量{$row['flow']} L/min、作動圧{$row['pressure']} MPaで、自重{$row['weight']} kg、{$r['ja']}に対応します。{$space['ja']}";
        $lead['zh'] = "{$model} 是一款最大起重量 {$c['zh']}、最大起重力矩 {$row['moment']} 吨·米的{$name['zh']}。推荐功率 {$row['power']} kW，液压流量 {$row['flow']} L/min，系统压力 {$row['pressure']} MPa，整机自重 {$row['weight']} 公斤，支持 {$r['zh']}。{$space['zh']}";
    }

    // The attachment page is in the truck crane brochure; augers and clamshells are not marine equipment.
    $has_attachments = $row['book'] === 'truck' && (bool) array_filter($product['features'] ?? [], static fn(array $f): bool => stripos((string) $f['title'], 'attachment') !== false);
    $ce = null;
    if ($row['book'] === 'truck') {
        // Longest listed series the model name starts with (SQ4ZG3T is SQ4ZG3, not SQ4Z).
        foreach ($ce_series as $token) {
            if (str_starts_with($model, $token) && strlen($token) > strlen((string) $ce)) {
                $ce = $token;
            }
        }
    }
    $html = [];
    foreach (['en', 'ja', 'zh'] as $lang) {
        $parts = ['<p>' . esc_html($lead[$lang]) . '</p>'];
        if ($has_attachments) {
            $parts = array_merge($parts, $attachments[$lang]);
        }
        if ($ce) {
            $parts[] = ['en' => '<h3>Certification</h3>', 'ja' => '<h3>認証</h3>', 'zh' => '<h3>认证</h3>'][$lang];
            $parts[] = '<p>' . esc_html([
                'en' => "The {$ce} series holds a CE certificate of conformity under the EU Machinery Directive 2006/42/EC.",
                'ja' => "{$ce}シリーズは、EU機械指令2006/42/ECに基づくCE適合証明書を取得しています。",
                'zh' => "{$ce} 系列已取得依据欧盟机械指令 2006/42/EC 的 CE 符合性证书。",
            ][$lang]) . '</p>';
        }
        $html[$lang] = $to_blocks($parts);
    }

    $targets = ['en' => $post->ID];
    foreach (['ja', 'zh'] as $lang) {
        $translated = function_exists('pll_get_post') ? (int) pll_get_post($post->ID, $lang) : 0;
        if ($translated) {
            $targets[$lang] = $translated;
        }
    }
    $report[] = sprintf('%-12s %-40s attachments:%s ce:%s langs:%s', $model, $name['en'], $has_attachments ? 'y' : '-', $ce ?? '-', implode(',', array_keys($targets)));
    if ($dry) {
        continue;
    }
    foreach ($targets as $lang => $id) {
        if (get_post_meta($id, '_legacy_content', true) === '' && !get_post_meta($id, '_detail_body_ready', true)) {
            update_post_meta($id, '_legacy_content', wp_slash((string) get_post_field('post_content', $id)));
        }
        wp_update_post(['ID' => $id, 'post_content' => wp_slash($html[$lang])]);
        update_post_meta($id, '_detail_body_ready', '1');
        $written++;
    }
}
echo implode("\n", $report), "\n";
printf("%s: %d posts written\n", $dry ? 'DRY RUN' : 'DONE', $written);
