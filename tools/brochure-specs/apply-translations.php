<?php
/**
 * Bring the Japanese and Chinese products in line with the brochure-based English products.
 *
 * Usage (from the WordPress root): wp eval-file <path>/apply-translations.php [dry-run]
 * For every published English product with a brochure source (_brochure_source):
 * - an existing ja/zh translation gets the English model, spec table, features, drawing, gallery, main photo
 *   and brochure source, with every label translated here, and a short description rebuilt from the specs;
 * - a missing translation is created (title from a template, same categories and photos) and linked.
 * Titles of existing translations are kept, except ones still carrying English words (timber cranes).
 * Units stay Latin (kg, t·m, kW...) so the spec cells can split number and unit.
 * Every overwritten ja/zh field is saved to backup-translations-<time>.json first. Run apply-body.php after
 * this so new translations get their body text.
 */

$dir = __DIR__;
$dry = in_array('dry-run', $args ?? [], true);
$langs = ['ja', 'zh'];

$labels = [
    'Max Lifting Capacity' => ['ja' => '最大吊り上げ能力', 'zh' => '最大起重量'],
    'Max Lifting Moment' => ['ja' => '最大吊り上げモーメント', 'zh' => '最大起重力矩'],
    'Recommended Power' => ['ja' => '推奨動力', 'zh' => '推荐功率'],
    'Hydraulic System Flow' => ['ja' => '油圧システム流量', 'zh' => '液压系统流量'],
    'Hydraulic System Pressure' => ['ja' => '油圧システム圧力', 'zh' => '液压系统压力'],
    'Self Weight' => ['ja' => '自重', 'zh' => '自重'],
    'Rotation Angle' => ['ja' => '旋回角度', 'zh' => '回转角度'],
    'Installation Space' => ['ja' => '取付スペース', 'zh' => '安装空间'],
    'Oil Tank Capacity' => ['ja' => '作動油タンク容量', 'zh' => '液压油箱容积'],
    'Winch' => ['ja' => 'ウインチ', 'zh' => '卷扬机'],
    'Boom Sections' => ['ja' => 'ブーム段数', 'zh' => '吊臂节数'],
    'Max Working Range' => ['ja' => '最大作業範囲', 'zh' => '最大工作范围'],
    'Max Lifting Height' => ['ja' => '最大揚程', 'zh' => '最大起升高度'],
    'Max Lifting Height (from ground)' => ['ja' => '最大揚程（地上から）', 'zh' => '最大起升高度（离地）'],
    'Rated Working Pressure' => ['ja' => '定格作動圧力', 'zh' => '额定工作压力'],
    'Matching Forklifts' => ['ja' => '適合フォークリフト', 'zh' => '适配叉车'],
    'Working Height' => ['ja' => '作業高さ', 'zh' => '作业高度'],
    'Boom Type' => ['ja' => 'ブーム形式', 'zh' => '吊臂形式'],
    'Max Working Radius' => ['ja' => '最大作業半径', 'zh' => '最大工作半径'],
    'Lifting Angle' => ['ja' => '起伏角度', 'zh' => '变幅角度'],
    'Dimensions (L × W × H)' => ['ja' => '外形寸法（長さ×幅×高さ）', 'zh' => '外形尺寸（长×宽×高）'],
    'Vehicle Weight' => ['ja' => '車両重量', 'zh' => '整车重量'],
];
// Word tokens inside spec values; numbers and units are left as they are.
$value_words = [
    'continuous' => ['ja' => '連続', 'zh' => '连续'],
    'Custom Solution' => ['ja' => 'カスタム対応', 'zh' => '定制方案'],
    'mast' => ['ja' => 'マスト', 'zh' => '门架'],
    'Included' => ['ja' => '標準装備', 'zh' => '标配'],
    'hydraulic' => ['ja' => '油圧', 'zh' => '液压'],
    'manual extension' => ['ja' => '手動伸縮', 'zh' => '手拉出臂'],
];

$features = [
    'High-strength welded structure' => [
        'ja' => ['高強度溶接構造', '高強度鋼板の溶接構造と六角形ブーム断面。', '主要な構造部材はすべて高強度鋼板で溶接されています。六角形のブーム断面により、優れた直進性と剛性、高い耐久性を実現します。'],
        'zh' => ['高强度焊接结构', '高强度钢板焊接，六边形吊臂截面。', '所有关键结构件均采用高强度钢板焊接。六边形吊臂截面对中性好，结构更结实，也更耐用。'],
    ],
    'Dedicated torque limiter' => [
        'ja' => ['専用モーメントリミッター', '全機にモーメントリミッターを装備、安全認証取得。', '専用のモーメントリミッターを装備し、高い安全性を確保しています。各種安全規格の認証を取得しており、吊り上げ作業をより正確、安全、確実に行えます。'],
        'zh' => ['专用力矩限制器', '每台均配力矩限制器，通过安全认证。', '产品配备专用力矩限制器，安全性高。起重机通过多项安全标准认证，吊装作业更精准、更安全、更可靠。'],
    ],
    'Box-type outriggers' => [
        'ja' => ['ボックス型アウトリガー', '6.3 t超の機種はボックス型でシリンダーを保護。', '吊り上げ能力6.3トン超の機種はボックス型アウトリガーを採用し、油圧シリンダーをしっかり保護します。'],
        'zh' => ['箱式支腿', '6.3 吨以上机型采用箱式支腿，保护油缸。', '起重量 6.3 吨以上的机型采用箱式支腿，更好地保护液压油缸。'],
    ],
    'Optional attachments' => [
        'ja' => ['オプションアタッチメント', 'ウインチ、オーガー、バスケット、グラップルに対応。', 'ウインチ、オーガー、バスケット、グラップルなど多彩なオプション構成を用意し、用途に合わせてカスタマイズできます。'],
        'zh' => ['可选属具', '可按需选配卷扬、钻杆、吊篮或抓具。', '可选配卷扬、钻杆、吊篮、抓具等多种配置，按需定制。'],
    ],
    'Operation modes' => [
        'ja' => ['操作方式', '手動レバー、高所シート、無線リモコン。', '高所操作シートや無線リモコンなど多様な操作方式を選べ、作業がより便利になります。'],
        'zh' => ['操作方式', '手动操纵杆、高位座椅或无线遥控。', '提供高位操作座椅、无线遥控等多种操作方式，作业更方便。'],
    ],
    'Dual four-bar linkage' => [
        'ja' => ['ダブル四節リンク', '逆角度まで届き、狭い現場に対応。', 'ダブル四節リンク機構により逆角度での作業が可能で、幅広い用途に柔軟に対応します。'],
        'zh' => ['双四连杆机构', '可反向作业，适合狭窄工况。', '双四连杆机构可实现反向角度作业，适用范围广，操作灵活。'],
    ],
    'Large-span outriggers' => [
        'ja' => ['ワイドスパンアウトリガー', 'ワイド固定式、手動折りたたみはオプション。', '標準のワイドスパン固定式アウトリガーに、手動折りたたみ式をオプションで選べ、作業時の安定性と使いやすさを両立します。'],
        'zh' => ['大跨距支腿', '标配大跨距固定支腿，可选手动翻转。', '标配大跨距固定支腿，可选手动翻转支腿，作业稳定、方便。'],
    ],
    'High-strength steel structure' => [
        'ja' => ['高強度鋼構造', 'コンパクトで軽量、かつ高強度の鋼構造。', '主構造に高強度鋼板を採用し、コンパクトで軽量、より強く耐久性の高い設計です。'],
        'zh' => ['高强度钢结构', '结构紧凑、重量轻、强度高。', '主体结构采用高强度钢板，结构紧凑、重量更轻、强度更高，也更耐用。'],
    ],
    'Custom installation' => [
        'ja' => ['カスタム取付', 'デッキに合わせたペデスタル取付。', '作業条件に合わせて取付方法をカスタマイズできます。'],
        'zh' => ['定制安装', '按甲板情况定制底座安装。', '可根据具体作业场景定制安装方式。'],
    ],
    'Marine anti-rust treatment' => [
        'ja' => ['船舶用防錆処理', '海上使用に向けた多層防錆処理。', '海洋環境向けに設計した複数の防錆処理により、過酷な海上条件でも優れた耐食性を発揮します。'],
        'zh' => ['船用防锈处理', '多层防锈工艺，适合海上作业。', '针对海洋环境的多重防锈工艺，在恶劣海况下也有出色的耐腐蚀性能。'],
    ],
    'Octagonal boom' => [
        'ja' => ['八角形ブーム', '8 t超は八角形ブームで軽量かつ高強度。', '吊り上げ能力8トン超の機種は八角形ブームを採用し、自重が軽く高い耐荷重性能を備えています。'],
        'zh' => ['八边形吊臂', '8 吨以上采用八边形吊臂，轻而强。', '起重量 8 吨以上的机型采用八边形吊臂，自重轻、承载能力强。'],
    ],
    'Overwinding protection' => [
        'ja' => ['過巻き防止', '過巻上げ・過巻下げ防止装置。', '過巻上げ防止・過巻下げ防止の保護装置により、吊り上げの安全性が大きく向上し、クレーンの寿命も延びます。'],
        'zh' => ['防过卷保护', '防过卷、防过放保护。', '防过卷和防过放保护系统显著提升吊装安全性，并延长起重机使用寿命。'],
    ],
];

// Product kinds: the features kicker, the noun in the short description and the title template.
$kinds = [
    'knuckle-boom-truck-mounted-cranes' => ['series' => 'Knuckle Boom Truck Mounted Crane', 'ja' => 'ナックルブーム式トラック搭載クレーン', 'zh' => '折臂式随车起重机'],
    'telescopic-boom-truck-mounted-cranes' => ['series' => 'Telescopic Boom Truck Mounted Crane', 'ja' => '伸縮ブーム式トラック搭載クレーン', 'zh' => '伸缩臂式随车起重机'],
    'knuckle-boom-marine-cranes' => ['series' => 'Knuckle Boom Marine Crane', 'ja' => 'ナックルブーム式マリンクレーン', 'zh' => '折臂式船用起重机'],
    'telescopic-boom-marine-cranes' => ['series' => 'Telescopic Boom Marine Crane', 'ja' => '伸縮ブーム式マリンクレーン', 'zh' => '伸缩臂式船用起重机'],
    'forklift-fly-jib-cranes' => ['ja' => 'フォークリフト用フライジブクレーン', 'zh' => '叉车飞臂起重机'],
    'pickup-cranes' => ['ja' => 'ピックアップクレーン', 'zh' => '皮卡起重机'],
    'spider-cranes' => ['ja' => 'スパイダークレーン', 'zh' => '蜘蛛式起重机'],
    'timber-cranes' => ['ja' => '木材クレーン', 'zh' => '木材起重机'],
    'garbage' => ['ja' => 'ごみ収集クレーン', 'zh' => '垃圾抓斗起重机'],
    'tow' => ['ja' => 'レッカー車用クレーン', 'zh' => '清障车起重机'],
];
// One naming scheme for titles and category names (agreed with the user: 中文 "折臂式随车起重机").
// Kinds whose titles follow the template: "{n} 吨{noun}" / "{n}トン {noun}", or "{model} {noun}".
$templated_kinds = ['knuckle-boom-truck-mounted-cranes', 'telescopic-boom-truck-mounted-cranes', 'knuckle-boom-marine-cranes', 'telescopic-boom-marine-cranes', 'forklift-fly-jib-cranes', 'timber-cranes', 'garbage', 'tow'];
// Category names per language, keyed by the English category slug.
$category_names = [
    'truck-mounted-crane' => ['ja' => 'トラック搭載クレーン', 'zh' => '随车起重机'],
    'marine-crane' => ['ja' => 'マリンクレーン', 'zh' => '船用起重机'],
    'knuckle-boom-truck-mounted-cranes' => ['ja' => 'ナックルブーム式トラック搭載クレーン', 'zh' => '折臂式随车起重机'],
    'telescopic-boom-truck-mounted-cranes' => ['ja' => '伸縮ブーム式トラック搭載クレーン', 'zh' => '伸缩臂式随车起重机'],
    'knuckle-boom-marine-cranes' => ['ja' => 'ナックルブーム式マリンクレーン', 'zh' => '折臂式船用起重机'],
    'telescopic-boom-marine-cranes' => ['ja' => '伸縮ブーム式マリンクレーン', 'zh' => '伸缩臂式船用起重机'],
];
$series_names = [];
foreach ($kinds as $kind) {
    if (isset($kind['series'])) {
        $series_names[$kind['series']] = ['ja' => $kind['ja'], 'zh' => $kind['zh']];
    }
}
$books = [
    'Truck Mounted Crane' => ['ja' => 'トラック搭載クレーン', 'zh' => '随车起重机'],
    'Marine Crane' => ['ja' => 'マリンクレーン', 'zh' => '船用起重机'],
];

$kind_of = static function (int $id, string $title) use ($kinds): string {
    $slugs = wp_get_post_terms($id, 'product_category', ['fields' => 'slugs']);
    foreach (is_array($slugs) ? $slugs : [] as $slug) {
        if (isset($kinds[$slug])) {
            return $slug;
        }
    }
    return stripos($title, 'garbage') !== false ? 'garbage' : (stripos($title, 'tow truck') !== false ? 'tow' : '');
};

$translate_value = static function (string $value, string $lang) use ($value_words): string {
    foreach ($value_words as $word => $words) {
        $value = preg_replace('/\b' . preg_quote($word, '/') . '\b/', $words[$lang], $value) ?? $value;
    }
    // "3600 mm (3 m mast)" -> full-width brackets in running Japanese/Chinese text.
    return preg_replace('/\s*\(([^)]*)\)/u', '（$1）', $value) ?? $value;
};

$short_description = static function (string $model, string $noun, array $specs, array $aliases, string $lang): string {
    $by_label = [];
    foreach ($specs as $row) {
        $by_label[$row['label']] = $row['value'];
    }
    $parts_def = [
        'Max Lifting Capacity' => ['ja' => '最大吊り上げ能力 %s', 'zh' => '最大起重量 %s'],
        'Max Lifting Moment' => ['ja' => '最大吊り上げモーメント %s', 'zh' => '最大起重力矩 %s'],
        'Max Working Radius' => ['ja' => '最大作業半径 %s', 'zh' => '最大工作半径 %s'],
        'Max Working Range' => ['ja' => '最大作業範囲 %s', 'zh' => '最大工作范围 %s'],
        'Self Weight' => ['ja' => '自重 %s', 'zh' => '自重 %s'],
    ];
    $parts = [];
    foreach ($parts_def as $label => $formats) {
        if (isset($by_label[$label])) {
            $parts[] = sprintf($formats[$lang], $by_label[$label]);
        }
    }
    if ($lang === 'ja') {
        $text = "BOB-LIFT {$model} {$noun}は、" . implode('、', $parts) . 'です。';
        return $aliases ? $text . '旧型番：' . implode('、', $aliases) . '。' : $text;
    }
    $text = "BOB-LIFT {$model} {$noun}，" . implode('，', $parts) . '。';
    return $aliases ? $text . '原型号 ' . implode('、', $aliases) . '。' : $text;
};

// "40 Ton Knuckle Boom Truck Mounted Crane" / "... SQ5SA3" / "ZT160Z-2A Timber Crane" -> localized title.
$make_title = static function (string $en_title, string $model, string $kind, string $lang) use ($kinds): string {
    $noun = $kinds[$kind][$lang] ?? '';
    $suffix = preg_match('/\s' . preg_quote($model, '/') . '$/', $en_title) ? ' ' . $model : '';
    if (preg_match('/^([\d.]+)\s*Ton\b/i', $en_title, $m)) {
        return $lang === 'ja' ? "{$m[1]}トン {$noun}{$suffix}" : "{$m[1]} 吨{$noun}{$suffix}";
    }
    return "{$model} {$noun}";
};

$localize_capacity = static function (string $capacity, string $lang): string {
    return preg_replace('/\s*\bton\b/i', $lang === 'ja' ? 'トン' : ' 吨', $capacity) ?? $capacity;
};

$renamed = [];
foreach ($category_names as $en_slug => $names) {
    $en_term = get_term_by('slug', $en_slug, 'product_category');
    foreach ($en_term ? $langs : [] as $lang) {
        $term_id = (int) pll_get_term($en_term->term_id, $lang);
        $term = $term_id ? get_term($term_id, 'product_category') : null;
        if ($term && !is_wp_error($term) && $term->name !== $names[$lang]) {
            $renamed[] = "{$lang} category: {$term->name} -> {$names[$lang]}";
            if (!$dry) {
                wp_update_term($term_id, 'product_category', ['name' => $names[$lang]]);
            }
        }
    }
}

// Saved site texts (SEO title, alts, archive intro, footer labels) still using the old names.
$option_replacements = [
    'boblift_theme_options_zh' => ['伸缩臂车载起重机' => '伸缩臂式随车起重机', '折臂式车载起重机' => '折臂式随车起重机', '伸缩臂随车起重机' => '伸缩臂式随车起重机', '折臂随车起重机' => '折臂式随车起重机', '车载起重机' => '随车起重机'],
    'boblift_theme_options_ja' => ['ナックルブームトラック搭載クレーン' => 'ナックルブーム式トラック搭載クレーン', '車載クレーン、船舶用クレーン' => 'トラック搭載クレーン、マリンクレーン'],
];
$replace_deep = static function ($value, array $map) use (&$replace_deep) {
    if (is_array($value)) {
        return array_map(static fn($child) => $replace_deep($child, $map), $value);
    }
    return is_string($value) ? strtr($value, $map) : $value;
};
foreach ($option_replacements as $option => $map) {
    $current = get_option($option);
    $next = $replace_deep($current, $map);
    if ($next !== $current) {
        $renamed[] = "{$option}: old names replaced";
        if (!$dry) {
            update_option($option, $next);
        }
    }
}

$json = static fn($value): string => wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$backup = [];
// The backup file is rewritten and checked before every overwrite, so an interrupted run or an unwritable directory never leaves changed data without it.
$backup_file = $dir . '/backup-translations-' . gmdate('Ymd-His') . '.json';
$persist_backup = static function () use (&$backup, $backup_file, $json, $dry): void {
    if ($dry || !$backup) {
        return;
    }
    $bytes = file_put_contents($backup_file, $json($backup));
    if ($bytes === false || $bytes === 0) {
        fwrite(STDERR, "Cannot write backup $backup_file; nothing further was changed.\n");
        exit(1);
    }
};
$report = [];
$problems = [];
$updated = 0;
$created = 0;

$english = get_posts(['post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => -1, 'lang' => 'en', 'orderby' => 'title', 'order' => 'ASC']);
foreach ($english as $en) {
    if (trim((string) get_post_meta($en->ID, '_brochure_source', true)) === '') {
        continue;
    }
    $model = trim((string) get_post_meta($en->ID, '_model', true));
    $kind = $kind_of($en->ID, $en->post_title);
    if ($kind === '') {
        $problems[] = "{$model}: no known product kind";
        continue;
    }
    $specs = json_decode((string) get_post_meta($en->ID, '_specs', true), true) ?: [];
    $aliases = array_values(array_filter(array_map('trim', explode(',', (string) get_post_meta($en->ID, '_model_aliases', true)))));
    $en_features = json_decode((string) get_post_meta($en->ID, '_features', true), true) ?: [];
    $en_series = (string) get_post_meta($en->ID, '_features_series', true);
    $en_features_source = (string) get_post_meta($en->ID, '_features_source', true);
    $en_drawings = json_decode((string) get_post_meta($en->ID, '_drawings', true), true) ?: [];
    $en_gallery = json_decode((string) get_post_meta($en->ID, '_gallery', true), true) ?: [];
    $en_source = (string) get_post_meta($en->ID, '_brochure_source', true);

    foreach ($langs as $lang) {
        $target = function_exists('pll_get_post') ? (int) pll_get_post($en->ID, $lang) : 0;
        $title = $target ? get_the_title($target) : $make_title($en->post_title, $model, $kind, $lang);
        // Keep editor titles, but not ones that still carry English product words.
        // Templated kinds always follow the naming scheme; others keep their title unless it still has English words.
        $title_words = str_ireplace(array_merge([$model, 'BOB-LIFT'], $aliases), '', html_entity_decode($title));
        $wanted = $make_title($en->post_title, $model, $kind, $lang);
        $retitle = $target && (in_array($kind, $templated_kinds, true) ? $title !== $wanted : (bool) preg_match('/[A-Za-z]{4,}/', $title_words));
        if ($retitle) {
            $title = $make_title($en->post_title, $model, $kind, $lang);
        }

        $meta = [];
        $meta['_model'] = $model;
        $meta['_model_aliases'] = implode(', ', $aliases);
        $meta['_capacity'] = $localize_capacity((string) get_post_meta($en->ID, '_capacity', true), $lang);
        $spec_rows = [];
        foreach ($specs as $row) {
            if (!isset($labels[$row['label']])) {
                $problems[] = "{$model}: spec label \"{$row['label']}\" has no {$lang} translation";
                continue 3;
            }
            $spec_rows[] = ['label' => $labels[$row['label']][$lang], 'value' => $translate_value((string) $row['value'], $lang)];
        }
        $meta['_specs'] = $json($spec_rows);
        // Only brochure features show on the page; older feature rows are left as they are.
        $feature_rows = [];
        foreach ($en_features_source === 'brochure' ? $en_features : [] as $feature) {
            if (!isset($features[$feature['title']])) {
                $problems[] = "{$model}: feature \"{$feature['title']}\" has no {$lang} translation";
                continue 3;
            }
            [$f_title, $f_text, $f_detail] = $features[$feature['title']][$lang];
            $feature_rows[] = array_filter(['title' => $f_title, 'text' => $f_text, 'detail' => $f_detail, 'image' => $feature['image'] ?? ''], static fn($v): bool => $v !== '');
        }
        if ($en_features_source === 'brochure') {
            $meta['_features'] = $json($feature_rows);
            $meta['_features_source'] = 'brochure';
            $meta['_features_series'] = $series_names[$en_series][$lang] ?? '';
        }
        $meta['_drawings'] = $json(array_map(static fn(array $d): array => [
            'id' => $d['id'],
            'label' => $model . ($lang === 'ja' ? ' 寸法図・荷重表' : ' 尺寸图与载荷图'),
        ], $en_drawings));
        $meta['_gallery'] = $json(array_map(static fn(array $g): array => ['image' => $g['image'] ?? '', 'label' => $title], $en_gallery));
        if (preg_match('/^BOB-LIFT (.+) Product Manual, PDF page (\d+)$/', $en_source, $m) && isset($books[$m[1]])) {
            $meta['_brochure_source'] = $lang === 'ja'
                ? "BOB-LIFT {$books[$m[1]]['ja']}製品マニュアル（PDF {$m[2]}ページ）"
                : "BOB-LIFT {$books[$m[1]]['zh']}产品手册，PDF 第 {$m[2]} 页";
        } elseif (preg_match('/^BOB-LIFT technical drawing (\S+) \(technical department, ([\d-]+)\)$/', $en_source, $m)) {
            $meta['_brochure_source'] = $lang === 'ja' ? "BOB-LIFT 技術図面 {$m[1]}（技術部、{$m[2]}）" : "BOB-LIFT 技术图纸 {$m[1]}（技术部，{$m[2]}）";
        } else {
            $problems[] = "{$model}: brochure source \"{$en_source}\" not recognised";
            continue;
        }
        $summary = $short_description($model, $kinds[$kind][$lang], $specs, $aliases, $lang);
        $meta['_short_description'] = $summary;
        $meta['_seo_description'] = $summary;
        $meta['_seo_title'] = $title . ' | BOB-LIFT';
        $thumbnail = (int) get_post_thumbnail_id($en->ID);

        $report[] = sprintf('%-4s %-12s %-6s %s', $lang, $model, $target ? ($retitle ? 'retitle' : 'update') : 'CREATE', $title);
        if ($dry) {
            $target ? $updated++ : $created++;
            continue;
        }

        if ($target) {
            $old = ['post_title' => get_the_title($target), 'post_excerpt' => get_post_field('post_excerpt', $target), '_thumbnail_id' => get_post_meta($target, '_thumbnail_id', true)];
            foreach (array_keys($meta) as $key) {
                $old[$key] = get_post_meta($target, $key, true);
            }
            $backup[$target] = $old;
            $persist_backup();
            $post_update = ['ID' => $target, 'post_excerpt' => $summary];
            if ($retitle) {
                $post_update['post_title'] = $title;
            }
            wp_update_post(wp_slash($post_update));
            $updated++;
        } else {
            $target = wp_insert_post(wp_slash([
                'post_type' => 'product',
                'post_status' => 'publish',
                'post_title' => $title,
                'post_name' => $en->post_name . ($lang === 'ja' ? '-2' : '-3'),
                'post_excerpt' => $summary,
                'menu_order' => $en->menu_order,
            ]), true);
            if (is_wp_error($target)) {
                $problems[] = "{$model} {$lang}: " . $target->get_error_message();
                continue;
            }
            pll_set_post_language($target, $lang);
            $map = function_exists('pll_get_post_translations') ? pll_get_post_translations($en->ID) : [];
            $map['en'] = $en->ID;
            $map[$lang] = $target;
            pll_save_post_translations($map);
            $terms = [];
            foreach (wp_get_post_terms($en->ID, 'product_category', ['fields' => 'ids']) as $term_id) {
                $translated = (int) pll_get_term($term_id, $lang);
                if ($translated) {
                    $terms[] = $translated;
                }
            }
            wp_set_object_terms($target, $terms, 'product_category');
            foreach (['_boom_type', '_status', '_catalog_pdf_url', '_capacity_value', '_detail_images'] as $key) {
                $value = get_post_meta($en->ID, $key, true);
                if ($value !== '') {
                    update_post_meta($target, $key, wp_slash($value));
                }
            }
            $created++;
        }
        foreach ($meta as $key => $value) {
            update_post_meta($target, $key, wp_slash($value));
        }
        if ($thumbnail) {
            set_post_thumbnail($target, $thumbnail);
        }
    }
}

// Products outside the title template (aerial platforms, spider and pickup cranes), named in the same scheme.
$title_overrides = [
    'knuckle-spider-crane' => ['ja' => 'ナックルブーム式スパイダークレーン', 'zh' => '折臂式蜘蛛起重机'],
    'telescopic-spider-crane' => ['ja' => '伸縮ブーム式スパイダークレーン', 'zh' => '伸缩臂式蜘蛛起重机'],
    '800-kg-pickup-crane-with-outriggers' => ['ja' => '800 kg アウトリガー付きピックアップクレーン', 'zh' => '800 kg 带支腿皮卡起重机'],
];
foreach (get_posts(['post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => -1, 'lang' => 'en']) as $en) {
    $names = $title_overrides[$en->post_name] ?? null;
    if (!$names && preg_match('/^Truck Mounted (\d+)-Meter Aerial Work Platform$/', $en->post_title, $m)) {
        $names = ['ja' => "トラック搭載型{$m[1]}メートル高所作業車", 'zh' => "车载式{$m[1]}米高空作业平台"];
    }
    foreach ($names ? $langs : [] as $lang) {
        $target = (int) pll_get_post($en->ID, $lang);
        if (!$target || (get_the_title($target) === $names[$lang] && get_post_meta($target, '_seo_title', true) === $names[$lang] . ' | BOB-LIFT')) {
            continue;
        }
        $report[] = sprintf('%-4s %-12s %-6s %s', $lang, $en->post_name, 'rename', $names[$lang]);
        if (!$dry) {
            $backup[$target]['post_title'] ??= get_the_title($target);
            $backup[$target]['_seo_title'] ??= get_post_meta($target, '_seo_title', true);
            $persist_backup();
            wp_update_post(wp_slash(['ID' => $target, 'post_title' => $names[$lang]]));
            update_post_meta($target, '_seo_title', wp_slash($names[$lang] . ' | BOB-LIFT'));
        }
    }
}

$persist_backup();
echo implode("\n", array_merge($renamed, $report)), "\n";
if ($problems) {
    echo "\nPROBLEMS:\n", implode("\n", $problems), "\n";
}
printf("%s: %d translations updated, %d created\n", $dry ? 'DRY RUN' : 'DONE', $updated, $created);
