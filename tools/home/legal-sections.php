<?php
/**
 * Privacy Policy and Terms of Use gained clauses on 2026-09-30 (cookies and browser storage, third-party
 * services, international transfers, data-protection rights, changes; prices and orders, governing law,
 * changes). English reads them from the theme defaults. ja/zh keep their saved lists, which replace the
 * defaults wholesale, so this inserts the translated clauses into those saved lists and bumps the date.
 * Only a list still holding the original six clauses is touched, so admin edits and re-runs are safe.
 *
 *   wp eval-file ../tools/home/legal-sections.php dry-run
 *   wp eval-file ../tools/home/legal-sections.php
 */
$dry_run = in_array('dry-run', $args ?? [], true);
$updated = '2026-09-30';
$privacy = [
    'ja' => [
        'insert_after_retention' => [
            ['title' => 'Cookie とブラウザへの保存', 'text' => '当サイトでは、広告用やサイト横断のトラッキング Cookie を使用していません。フォームのスパム対策（Cloudflare Turnstile）が、人とボットを見分けるために必要な Cookie を設定する場合があります。見積もりリストや、ニュースページのグリッド／リスト表示などの設定は、お使いのブラウザのローカルストレージに保存され、フォームを送信したときにのみ当社に送られます。これらはブラウザの設定からいつでも削除できます。'],
            ['title' => '外部サービス', 'text' => '一部の機能では他社のコンテンツを読み込みます。お問い合わせページの地図は Google マップ、動画は YouTube のプライバシー強化モード、フォームの保護は Cloudflare Turnstile を利用しています。これらの機能を使うと、各社がそれぞれのプライバシーポリシーに基づき、お客様の IP アドレスやブラウザ情報を受け取る場合があります。フォームの国名欄を自動入力するため、IP アドレスからおおよその国を推定しますが、IP アドレス自体はお問い合わせ内容と一緒に保存しません。'],
            ['title' => '国外への移転', 'text' => 'BOB-LIFT は中国・徐州を拠点としています。送信いただいた情報は中国の当社チームが取り扱い、上記のサービス提供者により他国で処理される場合があります。移転するのは、ご依頼への対応に必要な情報のみです。'],
        ],
        'choices_extra' => 'EU、英国など個人情報保護に関する法律がある地域にお住まいの場合は、情報の利用に対する異議の申し立てや、利用の制限を求めることもできます。すべてのご請求に 30 日以内の回答を目指します。',
        'insert_before_contact' => ['title' => '本ポリシーの変更', 'text' => 'ウェブサイトやサービスの変更に応じて、本ポリシーを更新することがあります。最終更新日はこのページの上部に記載しています。'],
    ],
    'zh' => [
        'insert_after_retention' => [
            ['title' => 'Cookie 与浏览器存储', 'text' => '本网站不使用广告 Cookie 或跨站跟踪 Cookie。表单的防垃圾验证（Cloudflare Turnstile）可能会设置区分真人与机器人所需的 Cookie。您的询价清单和显示偏好（例如新闻页的网格或列表视图）保存在您自己浏览器的本地存储中，只有在您提交表单时才会发送给我们。您可以随时在浏览器设置中清除。'],
            ['title' => '第三方服务', 'text' => '部分功能会加载其他公司的内容：联系页面的地图来自 Google 地图，视频通过 YouTube 隐私增强模式播放，表单保护由 Cloudflare Turnstile 提供。使用这些功能时，这些公司可能会依据各自的隐私政策获取您的 IP 地址和浏览器信息。为预填表单中的国家字段，网站会根据您的 IP 地址估算所在国家，IP 地址本身不会随询盘一起保存。'],
            ['title' => '跨境传输', 'text' => 'BOB-LIFT 位于中国徐州。您提交的信息由我们在中国的团队处理，也可能由上述服务提供商在其他国家处理。我们只传输回应您的请求所必需的信息。'],
        ],
        'choices_extra' => '如果您位于欧盟、英国或其他设有数据保护法律的地区，您还可以反对或要求我们限制对您信息的使用。我们力争在 30 天内回复每一项请求。',
        'insert_before_contact' => ['title' => '本政策的变更', 'text' => '当网站或服务发生变化时，我们可能会更新本政策。页面顶部的日期为最近一次更新的时间。'],
    ],
];
$terms = [
    'ja' => [
        'before_liability' => ['title' => '価格・見積もり・ご注文', 'text' => 'ウェブサイトに掲載している価格、キャンペーン、納期、在庫情報は参考情報であり、予告なく変更されることがあります。ご注文は、BOB-LIFT が書面の見積書、プロフォーマインボイスまたは契約書で確認した時点で確定し、これらの書類の内容がウェブサイトより優先されます。'],
        'after_liability' => [
            ['title' => '準拠法', 'text' => '本規約は中華人民共和国の法律に準拠します。本ウェブサイトの利用に関する紛争は、まず友好的な協議による解決を図るものとします。'],
            ['title' => '本規約の変更', 'text' => '本規約は随時更新することがあります。このページ上部の日付が現在の版を示し、引き続きウェブサイトを利用された場合は、その版に同意したものとみなします。'],
        ],
    ],
    'zh' => [
        'before_liability' => ['title' => '价格、报价与订单', 'text' => '网站上展示的价格、促销、交货期和库存信息仅供参考，可能随时变更，恕不另行通知。订单仅在 BOB-LIFT 以书面报价单、形式发票或合同确认后生效，这些文件的效力优先于网站内容。'],
        'after_liability' => [
            ['title' => '适用法律', 'text' => '本条款受中华人民共和国法律管辖。因使用本网站产生的任何争议，应首先通过友好协商解决。'],
            ['title' => '条款变更', 'text' => '我们可能会不时更新本条款。页面顶部的日期表示当前版本，继续使用本网站即表示您接受该版本。'],
        ],
    ],
];
// Titles of the six historical clauses, in order. The positional migration below runs only when the saved list is exactly
// that list; a customised, reordered or replaced list is left untouched.
$historical_titles = [
    'privacy' => [
        'ja' => ['当社が収集する情報', '情報の利用方法', '共有および開示', 'データ保持', 'お客様の選択肢', '連絡先'],
        'zh' => ['我们收集的信息', '我们如何使用信息', '共享与披露', '数据保留', '您的选择', '联系方式'],
    ],
    'terms' => [
        'ja' => ['ウェブサイト情報', '製品選定', '知的財産', 'ユーザーによる送信内容', '外部リンクおよびサービス', '責任の制限'],
        'zh' => ['网站信息', '产品选型', '知识产权', '用户提交内容', '外部链接和服务', '责任限制'],
    ],
];
$has_historical_titles = static fn(array $list, array $titles): bool => array_map(static fn($row): string => trim((string) ($row['title'] ?? '')), $list) === $titles;
foreach (['ja', 'zh'] as $language) {
    $key = 'boblift_theme_options_' . $language;
    $options = get_option($key);
    if (!is_array($options)) {
        continue;
    }
    $changes = [];
    $list = array_values((array) ($options['privacy_policy_page']['sections'] ?? []));
    if (count($list) === 6 && $has_historical_titles($list, $historical_titles['privacy'][$language])) {
        $p = $privacy[$language];
        $list[4]['text'] = rtrim((string) ($list[4]['text'] ?? '')) . "\n\n" . $p['choices_extra'];
        $list = array_merge(array_slice($list, 0, 4), $p['insert_after_retention'], [$list[4], $p['insert_before_contact'], $list[5]]);
        $options['privacy_policy_page']['sections'] = $list;
        $options['privacy_policy_page']['updated'] = $updated;
        $changes[] = 'privacy (+5 clauses)';
    }
    $list = array_values((array) ($options['terms_of_use_page']['sections'] ?? []));
    if (count($list) === 6 && $has_historical_titles($list, $historical_titles['terms'][$language])) {
        $t = $terms[$language];
        $list = array_merge(array_slice($list, 0, 5), [$t['before_liability'], $list[5]], $t['after_liability']);
        $options['terms_of_use_page']['sections'] = $list;
        $options['terms_of_use_page']['updated'] = $updated;
        $changes[] = 'terms (+3 clauses)';
    }
    if ($changes === []) {
        WP_CLI::log("$key: nothing to change");
        continue;
    }
    if (!$dry_run) {
        update_option($key, $options);
    }
    WP_CLI::log(($dry_run ? '[dry-run] ' : '') . "$key: " . implode(', ', $changes));
}
