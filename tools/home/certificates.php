<?php
/**
 * Replaces the certification logos on the About page with the scanned certificates and patents
 * (assets/certifications/cert-*.webp, patent-*.webp) in every language. Idempotent.
 *
 *   wp eval-file ../tools/home/certificates.php dry-run
 *   wp eval-file ../tools/home/certificates.php
 */
$dry_run = in_array('dry-run', $args ?? [], true);
$data = array (
  'en' => 
  array (
    'label' => 'Certificates & Patents',
    'title' => 'Certificates and patents',
    'text' => 'CE, EAC and ISO certificates and the patents registered to BOB-LIFT. Select a document to view it full size.',
    'items' => 
    array (
      0 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/cert-ce-cranes.webp',
        ),
        'title' => 'CE Certificate · Cranes',
        'text' => 'Machinery Directive 2006/42/EC · valid to 03/2031',
      ),
      1 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/cert-eac.webp',
        ),
        'title' => 'EAC Certificate of Conformity',
        'text' => 'EAEU TR CU 010/2011 · valid to 10/2030',
      ),
      2 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/cert-iso-9001.webp',
        ),
        'title' => 'ISO 9001 Quality Management',
        'text' => 'Truck-mounted crane production · valid to 04/2029',
      ),
      3 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/cert-iso-9001-marine.webp',
        ),
        'title' => 'ISO 9001 Quality Management · Marine',
        'text' => 'Marine crane production · valid to 04/2029',
      ),
      4 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/cert-iso-14001.webp',
        ),
        'title' => 'ISO 14001 Environmental Management',
        'text' => 'Truck-mounted crane production · valid to 07/2029',
      ),
      5 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/cert-trademark-china-boblift.webp',
        ),
        'title' => 'BOB-LIFT Trademark · China',
        'text' => 'Registration No. 26579311 · valid to 04/2029',
      ),
      6 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/cert-trademark-china-babu.webp',
        ),
        'title' => '巴布 (BABU) Trademark · China',
        'text' => 'Registration No. 27469741, cranes · valid to 12/2028',
      ),
      7 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/cert-trademark-russia.webp',
        ),
        'title' => 'BOB-LIFT Trademark · Russia',
        'text' => 'Registration No. 1211725 · valid to 11/2034',
      ),
      8 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/patent-crane-support.webp',
        ),
        'title' => 'Support mechanism for truck-mounted cranes',
        'text' => 'Utility model patent · ZL 2019 2 1991835.2',
      ),
      9 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/patent-pickup-crane.webp',
        ),
        'title' => 'Crane for pickup trucks',
        'text' => 'Utility model patent · ZL 2020 2 0116421.1',
      ),
      10 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/patent-crane-aerial-platform.webp',
        ),
        'title' => 'Aerial work platform for truck-mounted cranes',
        'text' => 'Utility model patent · ZL 2020 2 0005335.3',
      ),
      11 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/patent-brick-clamp-buffer.webp',
        ),
        'title' => 'Buffer device for truck-mounted brick clamps',
        'text' => 'Utility model patent · ZL 2020 2 0118784.9',
      ),
      12 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/patent-forklift-timber-grab.webp',
        ),
        'title' => 'Timber grab for forklifts',
        'text' => 'Utility model patent · ZL 2020 2 0118811.2',
      ),
    ),
  ),
  'ja' => 
  array (
    'label' => '認証・特許',
    'title' => '認証書と特許',
    'text' => 'CE・EAC・ISOの認証書と、BOB-LIFTが取得した特許です。書類を選ぶと拡大表示します。',
    'items' => 
    array (
      0 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/cert-ce-cranes.webp',
        ),
        'title' => 'CE認証 · クレーン',
        'text' => '機械指令 2006/42/EC · 2031年3月まで有効',
      ),
      1 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/cert-eac.webp',
        ),
        'title' => 'EAC適合証明書',
        'text' => 'EAEU TR CU 010/2011 · 2030年10月まで有効',
      ),
      2 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/cert-iso-9001.webp',
        ),
        'title' => 'ISO 9001 品質マネジメント',
        'text' => 'トラック搭載クレーンの製造 · 2029年4月まで有効',
      ),
      3 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/cert-iso-9001-marine.webp',
        ),
        'title' => 'ISO 9001 品質マネジメント · マリン',
        'text' => 'マリンクレーンの製造 · 2029年4月まで有効',
      ),
      4 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/cert-iso-14001.webp',
        ),
        'title' => 'ISO 14001 環境マネジメント',
        'text' => 'トラック搭載クレーンの製造 · 2029年7月まで有効',
      ),
      5 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/cert-trademark-china-boblift.webp',
        ),
        'title' => 'BOB-LIFT商標 · 中国',
        'text' => '登録番号 26579311 · 2029年4月まで有効',
      ),
      6 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/cert-trademark-china-babu.webp',
        ),
        'title' => '「巴布」商標 · 中国',
        'text' => '登録番号 27469741（クレーン等） · 2028年12月まで有効',
      ),
      7 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/cert-trademark-russia.webp',
        ),
        'title' => 'BOB-LIFT商標 · ロシア',
        'text' => '登録番号 1211725 · 2034年11月まで有効',
      ),
      8 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/patent-crane-support.webp',
        ),
        'title' => 'トラック搭載クレーンの支持機構',
        'text' => '実用新案特許 · ZL 2019 2 1991835.2',
      ),
      9 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/patent-pickup-crane.webp',
        ),
        'title' => 'ピックアップトラック用クレーン',
        'text' => '実用新案特許 · ZL 2020 2 0116421.1',
      ),
      10 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/patent-crane-aerial-platform.webp',
        ),
        'title' => 'トラック搭載クレーン用高所作業台',
        'text' => '実用新案特許 · ZL 2020 2 0005335.3',
      ),
      11 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/patent-brick-clamp-buffer.webp',
        ),
        'title' => 'トラック搭載レンガクランプ用緩衝装置',
        'text' => '実用新案特許 · ZL 2020 2 0118784.9',
      ),
      12 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/patent-forklift-timber-grab.webp',
        ),
        'title' => 'フォークリフト用木材グラップル',
        'text' => '実用新案特許 · ZL 2020 2 0118811.2',
      ),
    ),
  ),
  'zh' => 
  array (
    'label' => '证书与专利',
    'title' => '证书与专利',
    'text' => 'CE、EAC、ISO 认证证书，以及 BOB-LIFT 取得的专利。点击查看原件大图。',
    'items' => 
    array (
      0 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/cert-ce-cranes.webp',
        ),
        'title' => 'CE 认证 · 起重机',
        'text' => '机械指令 2006/42/EC · 有效期至 2031 年 3 月',
      ),
      1 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/cert-eac.webp',
        ),
        'title' => 'EAC 符合性证书',
        'text' => '欧亚经济联盟 TR CU 010/2011 · 有效期至 2030 年 10 月',
      ),
      2 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/cert-iso-9001.webp',
        ),
        'title' => 'ISO 9001 质量管理体系',
        'text' => '随车起重机的生产 · 有效期至 2029 年 4 月',
      ),
      3 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/cert-iso-9001-marine.webp',
        ),
        'title' => 'ISO 9001 质量管理体系 · 船用',
        'text' => '船用起重机的生产 · 有效期至 2029 年 4 月',
      ),
      4 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/cert-iso-14001.webp',
        ),
        'title' => 'ISO 14001 环境管理体系',
        'text' => '随车起重机的生产 · 有效期至 2029 年 7 月',
      ),
      5 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/cert-trademark-china-boblift.webp',
        ),
        'title' => 'BOB-LIFT 商标 · 中国',
        'text' => '注册号 26579311 · 有效期至 2029 年 4 月',
      ),
      6 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/cert-trademark-china-babu.webp',
        ),
        'title' => '“巴布”商标 · 中国',
        'text' => '注册号 27469741，含起重机 · 有效期至 2028 年 12 月',
      ),
      7 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/cert-trademark-russia.webp',
        ),
        'title' => 'BOB-LIFT 商标 · 俄罗斯',
        'text' => '注册号 1211725 · 有效期至 2034 年 11 月',
      ),
      8 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/patent-crane-support.webp',
        ),
        'title' => '一种随车起重机的支撑机构',
        'text' => '实用新型专利 · ZL 2019 2 1991835.2',
      ),
      9 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/patent-pickup-crane.webp',
        ),
        'title' => '一种皮卡车上的随车吊',
        'text' => '实用新型专利 · ZL 2020 2 0116421.1',
      ),
      10 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/patent-crane-aerial-platform.webp',
        ),
        'title' => '一种车载随车吊高空作业台',
        'text' => '实用新型专利 · ZL 2020 2 0005335.3',
      ),
      11 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/patent-brick-clamp-buffer.webp',
        ),
        'title' => '一种随车夹砖机上用缓冲装置',
        'text' => '实用新型专利 · ZL 2020 2 0118784.9',
      ),
      12 => 
      array (
        'image' => 
        array (
          'id' => 0,
          'path' => 'certifications/patent-forklift-timber-grab.webp',
        ),
        'title' => '一种叉车上用抓木装置',
        'text' => '实用新型专利 · ZL 2020 2 0118811.2',
      ),
    ),
  ),
);
foreach (['en' => 'boblift_theme_options', 'ja' => 'boblift_theme_options_ja', 'zh' => 'boblift_theme_options_zh'] as $lang => $key) {
    $options = get_option($key);
    if (!is_array($options)) {
        continue;
    }
    $current = $options['about_page']['certifications'] ?? null;
    $changed = $current !== $data[$lang];
    echo $key . ': ' . ($changed ? 'updated' : 'unchanged') . "\n";
    if ($changed && !$dry_run) {
        $options['about_page']['certifications'] = $data[$lang];
        update_option($key, $options);
    }
}
