<?php
// Checks boblift_correct_optional_lmi_features() without WordPress: php scripts/test-optional-lmi.php
define('ABSPATH', __DIR__);
function boblift_test_load(string $file, array $functions): void
{
    $source = file_get_contents($file);
    foreach ($functions as $name) {
        if (!preg_match('/\n(\/\*\*(?:(?!\*\/).)*\*\/\n)?function ' . $name . '\(.*?\n}\n/s', $source, $m)) {
            fwrite(STDERR, "missing $name\n");
            exit(1);
        }
        eval(preg_replace('/^\n/', '', $m[0]));
    }
}
boblift_test_load(__DIR__ . '/../inc/template-functions.php', ['boblift_optional_lmi_feature_text', 'boblift_feature_names_lmi', 'boblift_correct_optional_lmi_features']);
$fail = 0;
$cases = [
    ['en', 'Dedicated torque limiter', 'Load moment limiter on every crane, safety certified.', 'Optional torque limiter'],
    ['zh', '专用力矩限制器', '每台均配力矩限制器，通过安全认证。', '选装力矩限制器'],
    ['ja', '専用モーメントリミッター', '全機にモーメントリミッターを装備、安全認証取得。', 'モーメントリミッター（オプション）'],
    ['es', 'Limitador de par específico', 'Limitador del momento de carga en cada grúa, con certificación de seguridad.', 'Limitador de par opcional'],
    ['es', 'Limitador de momento dedicado', 'Limitador de momento de carga con certificación de seguridad.', 'Limitador de par opcional'],
    ['fr', 'Limiteur de couple dédié', 'Limiteur de moment de charge sur chaque grue, certifié pour la sécurité.', 'Limiteur de couple en option'],
    ['fr', 'Limiteur de moment dédié', 'Limiteur de moment de charge certifié pour la sécurité.', 'Limiteur de couple en option'],
    ['ru', 'Специализированный ограничитель грузового момента', 'Ограничитель грузового момента на каждом кране.', 'Ограничитель грузового момента (опция)'],
    ['ru', 'Специализированный ограничитель крутящего момента', 'x', 'Ограничитель грузового момента (опция)'],
    ['ar', 'محدد عزم مخصص', 'محدد عزم الحمولة على كل رافعة، مع اعتماد السلامة.', 'محدد عزم اختياري'],
];
foreach ($cases as [$lang, $title, $text, $want]) {
    $rows = [['title' => 'Wide outriggers', 'text' => 'Fixed.', 'image' => ''], ['title' => $title, 'text' => $text, 'image' => '/a.webp']];
    $got = boblift_correct_optional_lmi_features($rows, $lang);
    if ($got[1]['title'] !== $want || $got[1]['image'] !== '/a.webp' || $got[0] !== $rows[0] || $got[1]['text'] === $text) {
        $fail++;
        fwrite(STDERR, "FAIL $lang $title => " . json_encode($got[1], JSON_UNESCAPED_UNICODE) . "\n");
    }
}
foreach (['Anti-two-block protection', '防过卷保护', 'Protección contre le mouflage excessif', 'Защита от перемотки', 'الحماية من الرفع المفرط'] as $title) {
    $rows = [['title' => $title, 'text' => 't']];
    if (boblift_correct_optional_lmi_features($rows, 'en') !== $rows) {
        $fail++;
        fwrite(STDERR, "FAIL changed $title\n");
    }
}
if (boblift_correct_optional_lmi_features('x', 'en') !== 'x') {
    $fail++;
}
echo $fail ? "$fail failed\n" : "ok\n";
exit($fail ? 1 : 0);
