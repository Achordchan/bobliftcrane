<?php
if (!defined('ABSPATH')) {
    exit;
}


function boblift_static_text_translations(): array
{
    static $all = null;
    if ($all !== null) {
        return $all;
    }
    $all = [
        'ja' => [
            'Video Center' => '動画センター',
            'See the cranes move before you buy.' => 'ご購入前に、クレーンの動きをご覧ください。',
            'Product walk-arounds, load tests and factory footage, filmed in our own workshop in Xuzhou.' => '製品紹介、荷重試験、工場映像。すべて徐州の自社工場で撮影しています。',
            'Knuckle boom cranes lined up in the BOB-LIFT assembly hall' => 'BOB-LIFT 組立工場に並ぶナックルブームクレーン',
            'All videos' => 'すべての動画',
            'Truck mounted cranes' => 'トラック搭載クレーン',
            'Marine cranes' => '船舶用クレーン',
            'Tests and quality' => '試験と品質',
            'Product video' => '製品動画',
            'Watch this series in action' => 'このシリーズの動きを見る',
            'Series video: it shows a crane of the same capacity and boom type, so paint and options can differ from this model.' => 'シリーズ動画：同じ能力・同じブーム形式のクレーンです。塗装やオプションはこの機種と異なる場合があります。',
            'More videos' => 'その他の動画',
            'Videos are being added.' => '動画を順次追加しています。',
            '{capacity} t knuckle boom truck mounted crane' => '{capacity} t ナックルブームトラッククレーン',
            '{capacity} t telescopic boom truck mounted crane' => '{capacity} t 直伸ブームトラッククレーン',
            '{capacity} t knuckle boom marine crane' => '{capacity} t ナックルブーム船舶用クレーン',
            '{capacity} t telescopic boom marine crane' => '{capacity} t 直伸ブーム船舶用クレーン',
            '{capacity} t crane' => '{capacity} t クレーン',
            'Load test · 5 t knuckle boom crane' => '荷重試験 · 5 t ナックルブームクレーン',
            'Load test · 10 t knuckle boom crane' => '荷重試験 · 10 t ナックルブームクレーン',
            'Load test · 6.3 t knuckle boom crane' => '荷重試験 · 6.3 t ナックルブームクレーン',
            'Basket test · 6.3 t knuckle boom crane' => 'バスケット試験 · 6.3 t ナックルブームクレーン',
            'Load test on the test rig · 6.3 t telescopic marine crane' => '試験台での荷重試験 · 6.3 t 直伸ブーム船舶用クレーン',
            'Function test · 2 t knuckle boom marine crane' => '機能試験 · 2 t ナックルブーム船舶用クレーン',
            'Load test · SQ8ZA6 knuckle boom truck crane' => '荷重試験 · SQ8ZA6 ナックルブームトラッククレーン',
            'One order: twenty 3.2 t knuckle boom cranes ready to ship' => '1 件のご注文、出荷を待つ 3.2 t ナックルブームクレーン 20 台',
            'How we test a crane before delivery' => '出荷前のクレーン試験',
            'Quality control in the workshop' => '工場での品質管理',
            'Inside the BOB-LIFT factory' => 'BOB-LIFT 工場の内部',
            'Factory aerial view' => '工場の空撮',
            'Into the assembly hall' => '組立工場の中へ',
            'Finished cranes in the hall' => '工場内の完成クレーン',
            'Quality & Testing' => '品質と試験',
            'Tested on our own test bay before it ships.' => '出荷前に、自社の試験台で試験します。',
            'Watch the load, basket and function tests our cranes go through in the factory, and how the workshop checks parts along the way.' => '工場で行う荷重・バスケット・機能試験と、工程ごとの部品チェックをご覧ください。',
            'A knuckle boom crane on the BOB-LIFT test bay' => 'BOB-LIFT の試験台に載ったナックルブームクレーン',
            'Before delivery' => '出荷前',
            'Three checks between the assembly line and the container.' => '組立ラインからコンテナまでの 3 つのチェック。',
            'Parts and welds' => '部品と溶接',
            'Boom sections, cylinders and hydraulic parts are checked in the workshop before and during assembly.' => 'ブーム、シリンダー、油圧部品は組立前と組立中に工場で点検します。',
            'Load and function test' => '荷重・機能試験',
            'The finished crane is mounted on the test bay and runs through lifting, slewing and outrigger functions under load.' => '完成したクレーンを試験台に載せ、荷重をかけて巻上げ・旋回・アウトリガーの動作を確認します。',
            'Final check and packing' => '最終点検と梱包',
            'After the test the crane is inspected again, then packed and loaded for export.' => '試験後に再点検し、梱包して輸出用に積み込みます。',
            'In the workshop' => '工場の現場',
            'Test videos' => '試験動画',
            'Load tests filmed on the test bay' => '試験台で撮影した荷重試験',
            'Batch orders, built and checked in one run' => 'まとまったご注文も一括で製造・点検',
            'Twenty 3.2 t knuckle boom cranes for one order, finished in the workshop and lined up for shipping.' => '1 件のご注文の 3.2 t ナックルブームクレーン 20 台。工場で仕上げ、出荷に向けて並べました。',
            'Certificates' => '認証',
            'CE, EAC, ISO 9001 and ISO 14001 certificates and our crane patents are shown on the About page.' => 'CE、EAC、ISO 9001、ISO 14001 の証明書とクレーン特許は会社概要ページに掲載しています。',
            'View certificates' => '証明書を見る',
            'Want to see your crane tested?' => 'ご注文クレーンの試験をご覧になりますか？',
            'Ask for a test video of your order before it ships, or visit the factory and watch the test yourself.' => '出荷前にご注文機の試験動画をご依頼いただくか、工場で試験を直接ご覧ください。',
            'Ask for a Test Video' => '試験動画を依頼',
            'Plan a factory visit' => '工場見学を予約',
            'Factory Tour' => '工場見学',
            'Walk through our crane factory in Xuzhou.' => '徐州のクレーン工場をご案内します。',
            'From the gate to the test bay: where BOB-LIFT cranes are assembled, tested and packed for export.' => '正門から試験台まで。BOB-LIFT のクレーンが組み立てられ、試験され、輸出用に梱包される場所です。',
            'Aerial view of the BOB-LIFT factory in Xuzhou' => 'BOB-LIFT 徐州工場の空撮',
            'Factory area' => '工場面積',
            'Xuzhou, Jiangsu' => '中国江蘇省徐州',
            'Location' => '所在地',
            'Arrival' => '到着',
            'Trucks with mounted cranes wait in the yard for delivery or collection.' => 'クレーンを搭載したトラックが、出荷や引き取りを待っています。',
            'Assembly halls' => '組立工場',
            'Knuckle and telescopic booms are assembled in long rows under overhead cranes.' => '天井クレーンの下で、ナックルブームと直伸ブームを並べて組み立てます。',
            'Parts and hydraulics' => '部品と油圧',
            'Boom sections, cylinders, frames and hydraulic parts are staged next to the assembly line.' => 'ブーム、シリンダー、フレーム、油圧部品を組立ラインの横に準備します。',
            'Truck mounting and testing' => '架装と試験',
            'Cranes are mounted on truck chassis and tested before they leave.' => 'クレーンをトラックシャシーに架装し、試験してから出荷します。',
            'Stop {number}' => 'ポイント {number}',
            'Factory video' => '工場動画',
            'From the air' => '空からの眺め',
            'See how cranes are tested' => 'クレーンの試験を見る',
            'See it in person.' => 'ぜひ現地でご覧ください。',
            'Customers from many countries visit the factory before ordering. Tell us your dates and we will arrange the tour.' => '多くの国のお客様がご発注前に工場を訪れています。ご希望の日程をお知らせいただければ、見学を手配します。',
            'Customer visits' => 'お客様のご来訪',
            'A phone on a tripod filming BOB-LIFT knuckle boom cranes in the workshop' => 'ワークショップで BOB-LIFT のナックルブームクレーンを撮影する三脚付きスマートフォン',
            'BOB-LIFT Social Media' => 'BOB-LIFT ソーシャルメディア',
            'Follow BOB-LIFT on the platforms you already use.' => 'BOB-LIFT 公式アカウント',
            'Production, container loading and cranes working on customer sites, posted by our own team.' => '生産現場、コンテナ積み込み、お客様の現場で働くクレーンを、自社チームが発信しています。',
            'BOB-LIFT factory official social media updates' => 'BOB-LIFT 工場の公式ソーシャルメディア更新',
            'Official accounts' => '公式アカウント',
            'Global platforms' => '海外プラットフォーム',
            'China platforms' => '中国のプラットフォーム',
            'More platforms' => 'その他のプラットフォーム',
            'Scan to follow' => 'スキャンしてフォロー',
            'Recent posts' => '最近の投稿',
            'What we have been posting.' => '最近の投稿内容。',
            'A selection of recent posts from our accounts. Tap a post to view it full size.' => '各アカウントの最近の投稿から抜粋。タップすると拡大表示します。',
            'Short videos' => 'ショート動画',
            'Image posts' => '画像投稿',
            'Company news and industry updates.' => '会社ニュースと業界情報。',
            'Product videos and factory tours.' => '製品動画と工場見学。',
            'Deliveries, visits and customer stories.' => '出荷、ご来訪、お客様の声。',
            'Short clips from the workshop.' => '工場からのショート動画。',
            'Photos from the factory and job sites.' => '工場と作業現場の写真。',
            'Quick updates and announcements.' => '速報とお知らせ。',
            'Douyin' => 'Douyin（抖音）',
            'WeChat Channels' => 'WeChat チャンネル',
            'Xiaohongshu' => '小紅書',
            'Weibo' => 'Weibo',
            'Bilibili' => 'bilibili',
            'Kuaishou' => '快手',
            'Short videos in Chinese.' => '中国語のショート動画。',
            'Videos inside WeChat.' => 'WeChat 内の動画。',
            'Photo notes from the factory.' => '工場の写真ノート。',
            'Company news in Chinese.' => '中国語の会社ニュース。',
            'Longer product videos.' => '長めの製品動画。',
            'Production and global delivery' => '生産とグローバル出荷',
            'Customer feedback from Argentina' => 'アルゼンチンのお客様の声',
            '8 t truck mounted crane' => '8 t トラック搭載クレーン',
            'Ready for shipment' => '出荷準備完了',
            'Turkish customers visit the factory' => 'トルコのお客様が工場を訪問',
            'Operator view' => 'オペレーター視点',
            'Built by our workshop team' => '自社工場チームが製造',
            '10 t crane delivered' => '10 t クレーンを納品',
            'SQ10ZA4 knuckle boom crane' => 'SQ10ZA4 ナックルブームクレーン',
            '10 years apart, same reliability' => '10 年経っても変わらない信頼性',
            'Global delivery' => 'グローバル出荷',
            'Customer factory visit' => 'お客様の工場見学',
            'Telescopic boom crane' => '直伸ブームクレーン',
            'Knuckle boom marine crane' => 'ナックルブーム船舶用クレーン',
            'Knuckle boom truck crane' => 'ナックルブームトラッククレーン',
            'Talk to us directly' => '直接のご相談',
            'Rather message us than follow?' => 'フォローより直接連絡したい方へ',
            'Send the load, working radius and truck or vessel details. The sales team replies on the channel you use.' => '吊り上げ荷重、作業半径、車両または船舶の情報をお送りください。営業チームがご利用のチャネルで返信します。',
            'Send an Inquiry' => 'お問い合わせを送る',
            'Switch BOB-LIFT site' => 'BOB-LIFT の他サイトへ切り替え',
            'BOB-LIFT sites' => 'BOB-LIFT グループサイト',
            'Other BOB-LIFT sites' => 'BOB-LIFT の他サイト',
            'Current' => '現在',
            'You are here' => '現在のサイト',
            'BOB-LIFT' => 'BOB-LIFT',
            'Loader' => 'ローダー',
            'The company, its factory and the full range' => '会社・工場と全製品ラインナップ',
            'Truck-mounted, marine and pickup cranes' => 'トラック搭載・船舶用・ピックアップクレーン',
            'Mini skid steer loaders and mini dumpers' => 'ミニスキッドステアローダーとミニダンパー',
            'You are leaving this website' => 'このサイトから移動します',
            'Official BOB-LIFT website' => 'BOB-LIFT 公式サイト',
            'This is one of our own websites, run by BOB-LIFT. It opens in a new tab, so this page stays open for you.' => 'BOB-LIFT が運営する自社サイトです。新しいタブで開くため、このページはそのまま残ります。',
            'Continue to {domain}' => '{domain} へ移動',
            'Stay here' => 'このページに留まる',
            'Close' => '閉じる',
            'BOB-LIFT Group' => 'BOB-LIFT グループ',
            'Group' => 'グループ',
            'Lifting Equipment' => 'クレーン・揚重機器',
            'Cranes' => 'クレーン',
            'Loading Equipment' => 'ローダー・積込機器',
            'Loaders' => 'ローダー',
            'Home' => 'ホーム',
            'HOME' => 'ホーム',
            'News' => 'ニュース',
            'NEWS' => 'ニュース',
            'Cases' => '導入事例',
            'CASES' => '導入事例',
            'All' => 'すべて',
            'About Us' => '会社概要',
            'Global Customer Visits' => '海外のお客様来訪',
            'Social Media' => 'ソーシャルメディア',
            'Public Welfare' => '社会貢献',
            'Contact Us' => 'お問い合わせ',
            'Company News' => '会社ニュース',
            'Product News' => '製品ニュース',
            'Industry Insight' => '業界インサイト',
            'Marine Solution' => 'マリンソリューション',
            'Customer Case' => 'お客様事例',
            'Customer Case · Marine Operations' => 'お客様事例 · マリン作業',
            'CUSTOMER CASE · MARINE OPERATIONS' => 'お客様事例 · マリン作業',
            'Marine Operations' => 'マリン作業',
            'Customer Case · Complex Working Conditions' => 'お客様事例 · 複雑な作業条件',
            'CUSTOMER CASE · COMPLEX WORKING CONDITIONS' => 'お客様事例 · 複雑な作業条件',
            'Complex Working Conditions' => '複雑な作業条件',
            'Customer Case · Aerial Work Platform' => 'お客様事例 · 高所作業プラットフォーム',
            'CUSTOMER CASE · AERIAL WORK PLATFORM' => 'お客様事例 · 高所作業プラットフォーム',
            'Case Facts' => '事例概要',
            'Project Support' => 'プロジェクトサポート',
            'Discuss Similar Project' => '類似プロジェクトを相談する',
            'Related Cases' => '関連事例',
            'Scenario' => 'シーン',
            'Product' => '製品',
            'Country' => '国・地域',
            'Breadcrumb' => 'パンくずリスト',
            'Article Summary' => '記事概要',
            'Category' => 'カテゴリー',
            'Published' => '公開日',
            'Source' => '出典',
            'Related Updates' => '関連ニュース',
            'Need help choosing a crane?' => 'クレーン選定でお困りですか？',
            'Talk to BOB-LIFT before your next lifting project.' => '次の吊り上げプロジェクトの前にBOB-LIFTへご相談ください。',
            'Request a Quote' => '見積もりを依頼',
            'Back to News' => 'ニュース一覧へ戻る',
            'Related' => '関連',
            'Ask for solution' => 'ソリューションを相談',
            'BOB-LIFT Newsroom' => 'BOB-LIFTニュースルーム',
            'Yes' => 'はい',
            'No' => 'いいえ',
            'Not sure' => '未定',
            'Need support' => 'サポートが必要',
            'Either' => 'どちらでも可',
            'All Rights Reserved.' => 'All Rights Reserved.',
            '© 2026 BOB-LIFT. All Rights Reserved.' => '© 2026 BOB-LIFT. 無断転載を禁じます。',
            'What You Can Follow' => 'フォローできる内容',
            'Real content from real work.' => '実際の現場から届くリアルな情報。',
            'Construction' => '建設',
            'Logistics' => '物流',
            'Municipal Engineering' => '市政工事',
            'Marine' => '海洋',
            'Agriculture' => '農業',
            'Mining' => '鉱業',
            'Truck Mounted Crane' => 'トラック搭載クレーン',
            'Truck Mounted Cranes' => 'トラック搭載クレーン',
            'Knuckle Boom Truck Mounted Crane' => 'ナックルブーム式トラック搭載クレーン',
            'Knuckle Boom Truck Mounted Cranes' => 'ナックルブーム式トラック搭載クレーン',
            'Telescopic Boom Truck Mounted Crane' => '伸縮ブーム式トラック搭載クレーン',
            'Telescopic Boom Truck Mounted Cranes' => '伸縮ブーム式トラック搭載クレーン',
            'Marine Crane' => 'マリンクレーン',
            'Marine Cranes' => 'マリンクレーン',
            'Ship Crane' => '船舶用クレーン',
            'Ship Cranes' => '船舶用クレーン',
            'Knuckle Boom Marine Crane' => 'ナックルブーム式マリンクレーン',
            'Knuckle Boom Marine Cranes' => 'ナックルブーム式マリンクレーン',
            'Telescopic Boom Marine Crane' => '伸縮ブーム式マリンクレーン',
            'Telescopic Boom Marine Cranes' => '伸縮ブーム式マリンクレーン',
            'Pickup Crane' => 'ピックアップクレーン',
            'Pickup Cranes' => 'ピックアップクレーン',
            'Forklift Fly Jib Crane' => 'フォークリフト用フライジブクレーン',
            'Forklift Fly Jib Cranes' => 'フォークリフト用フライジブクレーン',
            'Mini Dumper' => 'ミニダンパー',
            'Mini Dumpers' => 'ミニダンパー',
            'Timber Crane' => '木材クレーン',
            'Timber Cranes' => '木材クレーン',
            'Spider Crane' => 'スパイダークレーン',
            'Spider Cranes' => 'スパイダークレーン',
            'Tail Lift' => 'テールリフト',
            'Aerial Work Platform' => '高所作業車',
            'Truck Mounted' => 'トラック搭載',
            'Meter Aerial Work Platform' => 'メートル高所作業車',
            'Knuckle Spider Crane' => 'ナックルスパイダークレーン',
            'Telescopic Spider Crane' => '伸縮式スパイダークレーン',
            'Pickup Crane With Outriggers' => 'アウトリガー付きピックアップクレーン',
            'Pickup Crane Without Outriggers' => 'アウトリガーなしピックアップクレーン',
            'With Outriggers' => 'アウトリガー付き',
            'Without Outriggers' => 'アウトリガーなし',
            'Arm Length' => 'アーム長',
            'Ton Mini Dumper' => 'トン ミニダンパー',
            'Ton' => 'トン',
            'tons' => 'トン',
            'timely delivery' => '迅速な納品',
            'Project-specific configuration' => 'プロジェクト別構成',
            'Quality Management' => '品質管理',
            'European Conformity' => '欧州適合',
            'Russia / EAEU Conformity Inspection' => 'ロシア / EAEU適合検査',
            'Russia / EAEU Conformity' => 'ロシア / EAEU適合',
            'Inspection & Certification' => '検査・認証',
            'Main product view' => 'メイン製品ビュー',
            'Product photo' => '製品写真',
            'Product Photos' => '製品写真',
            'Product Details' => '製品詳細',
            'Product detail image' => '製品詳細画像',
            'Product stage' => '製品ステージ',
            'Detail scroll' => '詳細スクロール',
            'Product detail sections' => '製品詳細セクション',
            'Product page navigation' => '製品ページナビゲーション',
            'Technical Specifications' => '技術仕様',
            'Quick Specs' => '主要仕様',
            'Key Features' => '主な特徴',
            'image' => '画像',
            'parameter chart' => 'パラメータ表',
            'Specifications' => '仕様',
            'model no' => '型番',
            'Electic starting Yes' => '電動始動 はい',
            'Stable lifting structure' => '安定した吊り上げ構造',
            'Designed for practical jobsite operation with reliable boom movement and load control' => '信頼性の高いブーム動作と荷重制御により、実用的な現場作業向けに設計されています',
            'Export-ready configuration' => '輸出対応構成',
            'Suitable for international procurement with documentation, packing and configuration support' => '書類、梱包、構成サポートを含む国際調達に適しています',
            'Application matching' => '用途適合',
            'Can be matched to working radius, yard or attachment requirements' => '作業半径、ヤード、アタッチメント要件に合わせて適合できます',
            'Crane manufacturer since 2003' => '2003年創業のクレーンメーカー',
            'Truck mounted cranes, built to lift.' => 'トラック搭載クレーン、確かな吊り上げのために。',
            'Knuckle boom and telescopic truck cranes from 800 kg to 50 t, built in our 37,000 m² factory in Xuzhou and exported to 120+ countries.' => '800 kgから50 tまでのナックルブーム式・伸縮ブーム式トラッククレーンを徐州の37,000 m²の自社工場で製造し、120か国以上へ輸出しています。',
            'View Products' => '製品を見る',
            'View Truck Cranes' => 'トラック搭載クレーンを見る',
            'BOB-LIFT knuckle boom truck crane lifting a precast concrete panel' => 'プレキャストコンクリートパネルを吊り上げるBOB-LIFTのナックルブームトラッククレーン',
            'Marine cranes, built for work at sea.' => 'マリンクレーン、海上作業のために。',
            'Knuckle boom and telescopic marine cranes from 800 kg to 25 t for vessels, ports and offshore work, configured to your deck and lifting needs.' => '船舶・港湾・洋上作業向けに、800 kgから25 tまでのナックルブーム式・伸縮ブーム式マリンクレーンを甲板と吊り上げ条件に合わせて構成します。',
            'View Marine Cranes' => 'マリンクレーンを見る',
            'BOB-LIFT knuckle boom marine crane lifting a cargo container on a supply vessel' => '補給船でコンテナを吊り上げるBOB-LIFTのナックルブームマリンクレーン',
            'Pause slideshow' => 'スライドショーを一時停止',
            'Play slideshow' => 'スライドショーを再生',
            'Former model' => '旧型番',
            'Product technical advantages' => '製品の技術的優位性',
            'Series' => 'シリーズ',
            'photos' => '枚の写真',
            'videos' => '本の動画',
            'Previous' => '前へ',
            'Next' => '次へ',
            'Customer Feedback' => 'お客様の声',
            'Application Cases' => '導入事例',
            'Planning a similar project?' => '同様のプロジェクトをご検討中ですか？',
            'View all' => 'すべて見る',
            'Media' => '写真・動画',
            'More customer feedback' => 'その他のお客様の声',
            'Interested in the {model}?' => '{model} にご興味がありますか？',
            'Get a Quote' => '見積もりを依頼',
            'Priority' => '重視点',
            'Site photos' => '現場写真',
            'Product details' => '製品詳細',
            'Click an image to enlarge' => '画像をクリックで拡大',
            'Hydraulics' => '油圧',
            'Hoses, valves and cylinders are fitted and checked on every crane.' => 'ホース・バルブ・シリンダーを一台ずつ取り付け、確認します。',
            'countries' => 'か国',
            'Visitors from' => '来訪国',
            'Customers from {count} countries have walked our workshop.' => '{count}か国のお客様が当社の工場を見学しています。',
            'What a visit covers' => '見学の内容',
            'A working day in the factory, not a showroom tour.' => 'ショールームではなく、稼働中の工場をご覧いただきます。',
            'Walk the production line' => '生産ラインを見学',
            'Follow a crane from welding and assembly to the test stand, and ask questions at every station.' => '溶接・組立から試験台まで、クレーンができる工程を順に見学し、各工程で質問いただけます。',
            'Look at the model you need' => 'ご検討中の機種を確認',
            'Stand next to the crane you are considering and check the boom, hydraulics and finish for yourself.' => 'ご検討中のクレーンを間近で見て、ブーム・油圧・仕上げをご自身で確認できます。',
            'Check parts and packing' => '部品と梱包を確認',
            'Open the spare-part boxes and see how cranes are wrapped and secured for the sea journey.' => '予備部品の箱を開け、海上輸送に向けたクレーンの梱包・固定方法をご覧いただけます。',
            'Join our customer wall' => 'お客様の壁に写真を',
            'Finish in the office in front of the customer wall, where visitors from around the world have left their photos.' => '最後はオフィスのお客様の壁の前で記念撮影。世界各地のお客様の写真が並んでいます。',
            'Visit gallery' => '見学ギャラリー',
            'Photos from recent visits' => '最近の見学の写真',
            'Select a country to see the photos from that visit.' => '国を選ぶと、その見学の写真をご覧いただけます。',
            'More factory visits' => 'その他の工場見学',
            'photos' => '枚',
            'Plan your visit' => '見学のご予約',
            'Come and see the crane before you order it.' => 'ご発注の前に、実機をご覧ください。',
            'Tell us your travel dates and the models you are considering, and we will plan the day around them.' => 'ご来訪日程とご検討中の機種をお知らせいただければ、それに合わせて当日の行程を組みます。',
            'Factory' => '工場所在地',
            'Xuzhou, Jiangsu, China' => '中国江蘇省徐州市',
            'Nearest airport' => '最寄り空港',
            'Xuzhou Guanyin International Airport' => '徐州観音国際空港',
            'High-speed rail' => '高速鉄道',
            'Xuzhou East Railway Station' => '徐州東駅',
            'Contact us' => 'お問い合わせ',
            'Plan a Visit' => '見学を予約',
            'Customers touring the BOB-LIFT workshop' => 'BOB-LIFT工場を見学するお客様',
            'No customer visit photos have been added yet.' => '来訪写真はまだありません。',
            'Algeria' => 'アルジェリア',
            'Customers from Algeria visiting the BOB-LIFT factory' => 'BOB-LIFT工場を見学するアルジェリアのお客様',
            'Azerbaijan' => 'アゼルバイジャン',
            'Customers from Azerbaijan visiting the BOB-LIFT factory' => 'BOB-LIFT工場を見学するアゼルバイジャンのお客様',
            'Germany' => 'ドイツ',
            'Customers from Germany visiting the BOB-LIFT factory' => 'BOB-LIFT工場を見学するドイツのお客様',
            'India' => 'インド',
            'Customers from India visiting the BOB-LIFT factory' => 'BOB-LIFT工場を見学するインドのお客様',
            'Ireland' => 'アイルランド',
            'Customers from Ireland visiting the BOB-LIFT factory' => 'BOB-LIFT工場を見学するアイルランドのお客様',
            'Kazakhstan' => 'カザフスタン',
            'Customers from Kazakhstan visiting the BOB-LIFT factory' => 'BOB-LIFT工場を見学するカザフスタンのお客様',
            'Kenya' => 'ケニア',
            'Customers from Kenya visiting the BOB-LIFT factory' => 'BOB-LIFT工場を見学するケニアのお客様',
            'New Zealand' => 'ニュージーランド',
            'Customers from New Zealand visiting the BOB-LIFT factory' => 'BOB-LIFT工場を見学するニュージーランドのお客様',
            'Nigeria' => 'ナイジェリア',
            'Customers from Nigeria visiting the BOB-LIFT factory' => 'BOB-LIFT工場を見学するナイジェリアのお客様',
            'Peru' => 'ペルー',
            'Customers from Peru visiting the BOB-LIFT factory' => 'BOB-LIFT工場を見学するペルーのお客様',
            'Russia' => 'ロシア',
            'Customers from Russia visiting the BOB-LIFT factory' => 'BOB-LIFT工場を見学するロシアのお客様',
            'South Africa' => '南アフリカ',
            'Customers from South Africa visiting the BOB-LIFT factory' => 'BOB-LIFT工場を見学する南アフリカのお客様',
            'Turkey' => 'トルコ',
            'Customers from Turkey visiting the BOB-LIFT factory' => 'BOB-LIFT工場を見学するトルコのお客様',
            'United States' => 'アメリカ',
            'Customers from United States visiting the BOB-LIFT factory' => 'BOB-LIFT工場を見学するアメリカのお客様',
            'Moment' => 'モーメント',
            'BOB-LIFT assembly workshop in Xuzhou' => '徐州のBOB-LIFT組立工場',
            'BOB-LIFT technician assembling a crane column' => 'クレーンのコラムを組み立てるBOB-LIFTの技術者',
            'Finishing a boom section in the BOB-LIFT workshop' => 'BOB-LIFT工場でのブーム仕上げ作業',
            'The BOB-LIFT team' => 'BOB-LIFTのチーム',
            'Customers visiting the BOB-LIFT factory' => 'BOB-LIFT工場を見学するお客様',
            'Site search' => 'サイト内検索',
            'Search the site' => 'サイト内を検索',
            'Search products and articles' => '製品と記事を検索',
            'Search a model, crane type or topic' => '型式・クレーンの種類・キーワードで検索',
            'Search' => '検索',
            'Browse by category' => 'カテゴリーから探す',
            'Search results' => '検索結果',
            'Results for “{query}”' => '「{query}」の検索結果',
            '{count} results' => '{count} 件',
            'Search BOB-LIFT' => 'BOB-LIFT サイト内検索',
            'Enter a model number such as {example}, a crane type or a topic.' => '{example} などの型式、クレーンの種類、キーワードを入力してください。',
            'All results' => 'すべて',
            'Products' => '製品',
            'News' => 'ニュース',
            'Cases' => '導入事例',
            'View all {count}' => '{count} 件すべて見る',
            'No results for “{query}”' => '「{query}」に一致する結果はありません',
            'Check the model number or try a shorter keyword. You can also browse the categories below.' => '型式をご確認いただくか、短いキーワードでお試しください。下のカテゴリーからも探せます。',
            'Not sure which crane fits?' => 'どのクレーンが合うかお迷いですか？',
            'Tell us the load, reach and vehicle or vessel, and our engineers will recommend a model.' => '吊り荷・作業半径・搭載する車両や船をお知らせいただければ、エンジニアが最適な型式をご提案します。',
            'Ask an engineer' => 'エンジニアに相談',
            'Search: {query} | BOB-LIFT' => '検索：{query} | BOB-LIFT',
            'Search | BOB-LIFT' => '検索 | BOB-LIFT',
            'Search pagination' => '検索結果のページ',
            'Lifting Moment' => '吊り上げモーメント',
            'Crane Self Weight' => 'クレーン自重',
            'Up to 1 t' => '1 t以下',
            'Up to 2 t' => '2 t以下',
            '2–5 t' => '2–5 t',
            'Over 20 t' => '20 t超',
            'Up to 5 t·m' => '5 t·m以下',
            'Over 40 t·m' => '40 t·m超',
            'Up to 1,000 kg' => '1,000 kg以下',
            'Over 5,000 kg' => '5,000 kg超',
            'Dimension Drawing' => '寸法図',
            'Dimensions and load chart' => '寸法と荷重表',
            'Select the drawing to view it full size.' => '図面をクリックすると拡大表示します。',
            'Manufacturing & delivery' => '製造と出荷',
            'From our workshop to your port' => '自社工場からお客様の港まで',
            'Assembly' => '組立',
            'Load test' => '荷重試験',
            'Final check' => '最終検査',
            'Packing' => '梱包',
            'Container loading' => 'コンテナ積込',
            'Marine test stand' => 'マリン試験台',
            'Mounted on a deck-style stand, the crane runs every boom and slewing motion before release.' => '甲板を模した試験台に据え付け、出荷前にブームと旋回の全動作を確認します。',
            'Boom sections' => 'ブーム製作',
            'Slewing base, column and booms are assembled by hand in our Xuzhou workshop.' => '旋回ベース、コラム、ブームを徐州の自社工場で一台ずつ組み立てます。',
            'The crane lifts test weights through its working range before release.' => '出荷前に試験用ウェイトを吊り、作業範囲全体で動作を確認します。',
            'Finished cranes are inspected and lined up for dispatch.' => '完成したクレーンを検査し、出荷に備えて整列させます。',
            'Wrapped in film and strapped to protect paint and hydraulics in transit.' => 'フィルムで包み固定して、輸送中の塗装と油圧部品を保護します。',
            'Loaded into containers at the factory and shipped to your port.' => '工場でコンテナに積み込み、お客様の港へ出荷します。',
            'Telescopic boom sections are finished and fitted before assembly.' => '伸縮ブームの各段を仕上げ、組立前に取り付けます。',
            'Columns, winches and control valves are assembled in our Xuzhou workshop.' => 'コラム、ウインチ、コントロールバルブを徐州の自社工場で組み立てます。',
            'photo' => '枚の写真',
            'video' => '本の動画',
            'Where this crane works' => '導入現場',
            'Photos and videos from customers' => 'お客様から届いた写真・動画',
            'All customer feedback' => 'お客様の声をすべて見る',
        ],
        'zh' => [
            'Video Center' => '视频中心',
            'See the cranes move before you buy.' => '下单前，先看起重机动起来。',
            'Product walk-arounds, load tests and factory footage, filmed in our own workshop in Xuzhou.' => '产品讲解、负载测试和工厂实拍，都在我们徐州自己的车间拍摄。',
            'Knuckle boom cranes lined up in the BOB-LIFT assembly hall' => 'BOB-LIFT 装配车间里排列整齐的折臂起重机',
            'All videos' => '全部视频',
            'Truck mounted cranes' => '随车起重机',
            'Marine cranes' => '船用起重机',
            'Tests and quality' => '测试与质检',
            'Product video' => '产品视频',
            'Watch this series in action' => '看看这个系列的实际作业',
            'Series video: it shows a crane of the same capacity and boom type, so paint and options can differ from this model.' => '系列视频：展示的是同吨位、同臂型的起重机，颜色和配置可能与本型号不同。',
            'More videos' => '更多视频',
            'Videos are being added.' => '视频正在陆续上传。',
            '{capacity} t knuckle boom truck mounted crane' => '{capacity} 吨折臂随车起重机',
            '{capacity} t telescopic boom truck mounted crane' => '{capacity} 吨直臂随车起重机',
            '{capacity} t knuckle boom marine crane' => '{capacity} 吨折臂船用起重机',
            '{capacity} t telescopic boom marine crane' => '{capacity} 吨直臂船用起重机',
            '{capacity} t crane' => '{capacity} 吨起重机',
            'Load test · 5 t knuckle boom crane' => '负载测试 · 5 吨折臂起重机',
            'Load test · 10 t knuckle boom crane' => '负载测试 · 10 吨折臂起重机',
            'Load test · 6.3 t knuckle boom crane' => '负载测试 · 6.3 吨折臂起重机',
            'Basket test · 6.3 t knuckle boom crane' => '吊篮测试 · 6.3 吨折臂起重机',
            'Load test on the test rig · 6.3 t telescopic marine crane' => '试验台负载测试 · 6.3 吨直臂船用起重机',
            'Function test · 2 t knuckle boom marine crane' => '功能测试 · 2 吨折臂船用起重机',
            'Load test · SQ8ZA6 knuckle boom truck crane' => '负载测试 · SQ8ZA6 折臂随车起重机',
            'One order: twenty 3.2 t knuckle boom cranes ready to ship' => '同一订单的 20 台 3.2 吨折臂起重机，整装待发',
            'How we test a crane before delivery' => '交付前我们怎样测试起重机',
            'Quality control in the workshop' => '车间里的质量把控',
            'Inside the BOB-LIFT factory' => '走进 BOB-LIFT 工厂',
            'Factory aerial view' => '工厂航拍',
            'Into the assembly hall' => '走进装配车间',
            'Finished cranes in the hall' => '车间里的成品起重机',
            'Quality & Testing' => '质量与测试',
            'Tested on our own test bay before it ships.' => '出厂前，在我们自己的试验台上测试。',
            'Watch the load, basket and function tests our cranes go through in the factory, and how the workshop checks parts along the way.' => '看看起重机在工厂里经历的负载、吊篮和功能测试，以及车间怎样逐道检查零部件。',
            'A knuckle boom crane on the BOB-LIFT test bay' => 'BOB-LIFT 试验台上的折臂起重机',
            'Before delivery' => '交付之前',
            'Three checks between the assembly line and the container.' => '从装配线到装柜之间的三道检查。',
            'Parts and welds' => '零部件与焊缝',
            'Boom sections, cylinders and hydraulic parts are checked in the workshop before and during assembly.' => '吊臂、油缸和液压件在装配前和装配中都会在车间检查。',
            'Load and function test' => '负载与功能测试',
            'The finished crane is mounted on the test bay and runs through lifting, slewing and outrigger functions under load.' => '整机装上试验台，带载完成起升、回转和支腿等动作。',
            'Final check and packing' => '终检与包装',
            'After the test the crane is inspected again, then packed and loaded for export.' => '测试后再检查一遍，然后包装、装柜出口。',
            'In the workshop' => '车间现场',
            'Test videos' => '测试视频',
            'Load tests filmed on the test bay' => '试验台上拍摄的负载测试',
            'Batch orders, built and checked in one run' => '批量订单，一次完成生产与检查',
            'Twenty 3.2 t knuckle boom cranes for one order, finished in the workshop and lined up for shipping.' => '同一订单的 20 台 3.2 吨折臂起重机，在车间完工后排列待发。',
            'Certificates' => '资质证书',
            'CE, EAC, ISO 9001 and ISO 14001 certificates and our crane patents are shown on the About page.' => 'CE、EAC、ISO 9001、ISO 14001 证书和起重机专利都在"关于我们"页面展示。',
            'View certificates' => '查看证书',
            'Want to see your crane tested?' => '想看你的起重机测试吗？',
            'Ask for a test video of your order before it ships, or visit the factory and watch the test yourself.' => '发货前可以向我们要一段你订单的测试视频，也可以来工厂现场看测试。',
            'Ask for a Test Video' => '索取测试视频',
            'Plan a factory visit' => '预约参观工厂',
            'Factory Tour' => '工厂实景',
            'Walk through our crane factory in Xuzhou.' => '走一遍我们在徐州的起重机工厂。',
            'From the gate to the test bay: where BOB-LIFT cranes are assembled, tested and packed for export.' => '从厂门到试验台：BOB-LIFT 起重机在这里装配、测试、打包出口。',
            'Aerial view of the BOB-LIFT factory in Xuzhou' => 'BOB-LIFT 徐州工厂航拍',
            'Factory area' => '厂区面积',
            'Xuzhou, Jiangsu' => '江苏徐州',
            'Location' => '所在地',
            'Arrival' => '到达厂区',
            'Trucks with mounted cranes wait in the yard for delivery or collection.' => '装好起重机的卡车停在院子里，等待交付或提车。',
            'Assembly halls' => '装配车间',
            'Knuckle and telescopic booms are assembled in long rows under overhead cranes.' => '折臂和直臂起重机在行车下成排装配。',
            'Parts and hydraulics' => '零部件与液压',
            'Boom sections, cylinders, frames and hydraulic parts are staged next to the assembly line.' => '吊臂、油缸、底座和液压件在装配线旁备料。',
            'Truck mounting and testing' => '装车与测试',
            'Cranes are mounted on truck chassis and tested before they leave.' => '起重机装上卡车底盘，测试合格后出厂。',
            'Stop {number}' => '第 {number} 站',
            'Factory video' => '工厂视频',
            'From the air' => '空中视角',
            'See how cranes are tested' => '看看起重机怎样测试',
            'See it in person.' => '来现场看看。',
            'Customers from many countries visit the factory before ordering. Tell us your dates and we will arrange the tour.' => '很多国家的客户下单前都来过工厂。告诉我们你的时间，我们来安排参观。',
            'Customer visits' => '客户来访',
            'A phone on a tripod filming BOB-LIFT knuckle boom cranes in the workshop' => '车间里，三脚架上的手机正在拍摄 BOB-LIFT 折臂起重机',
            'BOB-LIFT Social Media' => 'BOB-LIFT 社交媒体',
            'Follow BOB-LIFT on the platforms you already use.' => 'BOB-LIFT 官方账号',
            'Production, container loading and cranes working on customer sites, posted by our own team.' => '生产车间、装柜发货、客户现场作业，都是我们团队自己拍、自己发的。',
            'BOB-LIFT factory official social media updates' => 'BOB-LIFT 工厂官方社交媒体动态',
            'Official accounts' => '官方账号',
            'Global platforms' => '海外平台',
            'China platforms' => '国内平台',
            'More platforms' => '更多平台',
            'Scan to follow' => '扫码关注',
            'Recent posts' => '最近发布',
            'What we have been posting.' => '我们最近发了什么。',
            'A selection of recent posts from our accounts. Tap a post to view it full size.' => '从各账号近期内容中挑选，点击可查看大图。',
            'Short videos' => '短视频',
            'Image posts' => '图文帖子',
            'Company news and industry updates.' => '公司动态与行业资讯。',
            'Product videos and factory tours.' => '产品视频与工厂参观。',
            'Deliveries, visits and customer stories.' => '发货、来访与客户故事。',
            'Short clips from the workshop.' => '车间短视频。',
            'Photos from the factory and job sites.' => '工厂与作业现场照片。',
            'Quick updates and announcements.' => '快讯与公告。',
            'Douyin' => '抖音',
            'WeChat Channels' => '微信视频号',
            'Xiaohongshu' => '小红书',
            'Weibo' => '微博',
            'Bilibili' => '哔哩哔哩',
            'Kuaishou' => '快手',
            'Short videos in Chinese.' => '中文短视频。',
            'Videos inside WeChat.' => '在微信里看视频。',
            'Photo notes from the factory.' => '工厂图文笔记。',
            'Company news in Chinese.' => '中文公司动态。',
            'Longer product videos.' => '较长的产品视频。',
            'Production and global delivery' => '生产与全球交付',
            'Customer feedback from Argentina' => '阿根廷客户反馈',
            '8 t truck mounted crane' => '8 吨随车起重机',
            'Ready for shipment' => '整装待发',
            'Turkish customers visit the factory' => '土耳其客户来厂参观',
            'Operator view' => '操作视角',
            'Built by our workshop team' => '车间团队制造',
            '10 t crane delivered' => '10 吨起重机交付',
            'SQ10ZA4 knuckle boom crane' => 'SQ10ZA4 折臂起重机',
            '10 years apart, same reliability' => '相隔十年，同样可靠',
            'Global delivery' => '全球交付',
            'Customer factory visit' => '客户来厂参观',
            'Telescopic boom crane' => '直臂随车起重机',
            'Knuckle boom marine crane' => '折臂船用起重机',
            'Knuckle boom truck crane' => '折臂随车起重机',
            'Talk to us directly' => '直接沟通',
            'Rather message us than follow?' => '想直接聊，不想只是关注？',
            'Send the load, working radius and truck or vessel details. The sales team replies on the channel you use.' => '把起重量、工作半径、车辆或船舶信息发给我们，销售团队会在你用的渠道回复。',
            'Send an Inquiry' => '发送询盘',
            'Switch BOB-LIFT site' => '切换 BOB-LIFT 站点',
            'BOB-LIFT sites' => 'BOB-LIFT 旗下站点',
            'Other BOB-LIFT sites' => 'BOB-LIFT 其他站点',
            'Current' => '当前',
            'You are here' => '当前站点',
            'BOB-LIFT' => 'BOB-LIFT',
            'Loader' => '装载设备',
            'The company, its factory and the full range' => '公司、工厂和全部产品线',
            'Truck-mounted, marine and pickup cranes' => '随车吊、船用吊和皮卡吊',
            'Mini skid steer loaders and mini dumpers' => '迷你滑移装载机和迷你翻斗车',
            'You are leaving this website' => '即将离开本站',
            'Official BOB-LIFT website' => 'BOB-LIFT 官方网站',
            'This is one of our own websites, run by BOB-LIFT. It opens in a new tab, so this page stays open for you.' => '这是 BOB-LIFT 自营的官方网站，会在新标签页打开，当前页面仍会保留。',
            'Continue to {domain}' => '前往 {domain}',
            'Stay here' => '留在本页',
            'Close' => '关闭',
            'BOB-LIFT Group' => 'BOB-LIFT 集团主站',
            'Group' => '集团',
            'Lifting Equipment' => '起重设备',
            'Cranes' => '起重设备',
            'Loading Equipment' => '装载设备',
            'Loaders' => '装载设备',
            'Customer Feedback' => '客户反馈',
            'Application Cases' => '应用案例',
            'Planning a similar project?' => '正在筹划类似项目？',
            'View all' => '查看全部',
            'Media' => '图片与视频',
            'More customer feedback' => '更多客户反馈',
            'Interested in the {model}?' => '对 {model} 感兴趣？',
            'Get a Quote' => '获取报价',
            'Country' => '国家/地区',
            'Product' => '产品',
            'Scenario' => '应用场景',
            'Priority' => '关注重点',
            'Site photos' => '现场照片',
            'Product details' => '产品详情',
            'Click an image to enlarge' => '点击图片查看大图',
            'Hydraulics' => '液压',
            'Hoses, valves and cylinders are fitted and checked on every crane.' => '每台起重机的油管、阀组和油缸都逐一安装并检查。',
            'countries' => '个国家',
            'Visitors from' => '来访国家',
            'Customers from {count} countries have walked our workshop.' => '已有来自 {count} 个国家的客户走进我们的车间。',
            'What a visit covers' => '参观内容',
            'A working day in the factory, not a showroom tour.' => '看的是正在生产的工厂，而不是展厅。',
            'Walk the production line' => '走一遍生产线',
            'Follow a crane from welding and assembly to the test stand, and ask questions at every station.' => '从焊接、装配一直看到试验台，每个工位都可以随时提问。',
            'Look at the model you need' => '看您需要的型号',
            'Stand next to the crane you are considering and check the boom, hydraulics and finish for yourself.' => '站在您考虑的那台起重机旁边，亲自检查吊臂、液压和做工。',
            'Check parts and packing' => '检查配件和包装',
            'Open the spare-part boxes and see how cranes are wrapped and secured for the sea journey.' => '打开备件箱，看看起重机如何缠膜、加固，准备海运。',
            'Join our customer wall' => '留影客户见证墙',
            'Finish in the office in front of the customer wall, where visitors from around the world have left their photos.' => '最后在办公室的客户见证墙前合影，墙上是来自世界各地客户的照片。',
            'Visit gallery' => '参观相册',
            'Photos from recent visits' => '近期来访照片',
            'Select a country to see the photos from that visit.' => '点击国家，查看那次来访的照片。',
            'More factory visits' => '更多来访',
            'photos' => '张',
            'Plan your visit' => '预约参观',
            'Come and see the crane before you order it.' => '下单之前，先来看看起重机。',
            'Tell us your travel dates and the models you are considering, and we will plan the day around them.' => '告诉我们您的行程日期和考虑的型号，我们会据此安排当天的参观。',
            'Factory' => '工厂地址',
            'Xuzhou, Jiangsu, China' => '中国江苏省徐州市',
            'Nearest airport' => '最近机场',
            'Xuzhou Guanyin International Airport' => '徐州观音国际机场',
            'High-speed rail' => '高铁',
            'Xuzhou East Railway Station' => '徐州东站',
            'Contact us' => '联系我们',
            'Plan a Visit' => '预约参观',
            'Customers touring the BOB-LIFT workshop' => '客户参观 BOB-LIFT 车间',
            'No customer visit photos have been added yet.' => '暂无客户来访照片。',
            'Algeria' => '阿尔及利亚',
            'Customers from Algeria visiting the BOB-LIFT factory' => '来自阿尔及利亚的客户参观 BOB-LIFT 工厂',
            'Azerbaijan' => '阿塞拜疆',
            'Customers from Azerbaijan visiting the BOB-LIFT factory' => '来自阿塞拜疆的客户参观 BOB-LIFT 工厂',
            'Germany' => '德国',
            'Customers from Germany visiting the BOB-LIFT factory' => '来自德国的客户参观 BOB-LIFT 工厂',
            'India' => '印度',
            'Customers from India visiting the BOB-LIFT factory' => '来自印度的客户参观 BOB-LIFT 工厂',
            'Ireland' => '爱尔兰',
            'Customers from Ireland visiting the BOB-LIFT factory' => '来自爱尔兰的客户参观 BOB-LIFT 工厂',
            'Kazakhstan' => '哈萨克斯坦',
            'Customers from Kazakhstan visiting the BOB-LIFT factory' => '来自哈萨克斯坦的客户参观 BOB-LIFT 工厂',
            'Kenya' => '肯尼亚',
            'Customers from Kenya visiting the BOB-LIFT factory' => '来自肯尼亚的客户参观 BOB-LIFT 工厂',
            'New Zealand' => '新西兰',
            'Customers from New Zealand visiting the BOB-LIFT factory' => '来自新西兰的客户参观 BOB-LIFT 工厂',
            'Nigeria' => '尼日利亚',
            'Customers from Nigeria visiting the BOB-LIFT factory' => '来自尼日利亚的客户参观 BOB-LIFT 工厂',
            'Peru' => '秘鲁',
            'Customers from Peru visiting the BOB-LIFT factory' => '来自秘鲁的客户参观 BOB-LIFT 工厂',
            'Russia' => '俄罗斯',
            'Customers from Russia visiting the BOB-LIFT factory' => '来自俄罗斯的客户参观 BOB-LIFT 工厂',
            'South Africa' => '南非',
            'Customers from South Africa visiting the BOB-LIFT factory' => '来自南非的客户参观 BOB-LIFT 工厂',
            'Turkey' => '土耳其',
            'Customers from Turkey visiting the BOB-LIFT factory' => '来自土耳其的客户参观 BOB-LIFT 工厂',
            'United States' => '美国',
            'Customers from United States visiting the BOB-LIFT factory' => '来自美国的客户参观 BOB-LIFT 工厂',
            'Moment' => '力矩',
            'BOB-LIFT assembly workshop in Xuzhou' => 'BOB-LIFT 徐州装配车间',
            'BOB-LIFT technician assembling a crane column' => 'BOB-LIFT 技术工人装配起重机立柱',
            'Finishing a boom section in the BOB-LIFT workshop' => 'BOB-LIFT 车间打磨吊臂',
            'The BOB-LIFT team' => 'BOB-LIFT 团队',
            'Customers visiting the BOB-LIFT factory' => '客户参观 BOB-LIFT 工厂',
            'Site search' => '站内搜索',
            'Search the site' => '搜索全站',
            'Search products and articles' => '搜索产品和文章',
            'Search a model, crane type or topic' => '输入型号、起重机类型或关键词',
            'Search' => '搜索',
            'Browse by category' => '按类别浏览',
            'Search results' => '搜索结果',
            'Results for “{query}”' => '“{query}”的搜索结果',
            '{count} results' => '共 {count} 条结果',
            'Search BOB-LIFT' => '搜索 BOB-LIFT',
            'Enter a model number such as {example}, a crane type or a topic.' => '输入型号（如 {example}）、起重机类型或关键词。',
            'All results' => '全部',
            'Products' => '产品',
            'News' => '新闻',
            'Cases' => '案例',
            'View all {count}' => '查看全部 {count} 条',
            'No results for “{query}”' => '没有找到与“{query}”相关的结果',
            'Check the model number or try a shorter keyword. You can also browse the categories below.' => '请检查型号，或换一个更短的关键词，也可以从下面的类别浏览。',
            'Not sure which crane fits?' => '不确定哪款起重机合适？',
            'Tell us the load, reach and vehicle or vessel, and our engineers will recommend a model.' => '告诉我们吊重、作业半径以及车辆或船舶情况，我们的工程师会为您推荐合适的型号。',
            'Ask an engineer' => '咨询工程师',
            'Search: {query} | BOB-LIFT' => '搜索：{query} | BOB-LIFT',
            'Search | BOB-LIFT' => '搜索 | BOB-LIFT',
            'Search pagination' => '搜索结果分页',
            'Lifting Moment' => '起重力矩',
            'Crane Self Weight' => '整机自重',
            'Up to 1 t' => '1 t 及以下',
            'Up to 2 t' => '2 t 及以下',
            '2–5 t' => '2–5 t',
            'Over 20 t' => '20 t 以上',
            'Up to 5 t·m' => '5 t·m 及以下',
            'Over 40 t·m' => '40 t·m 以上',
            'Up to 1,000 kg' => '1,000 kg 及以下',
            'Over 5,000 kg' => '5,000 kg 以上',
            'Dimension Drawing' => '尺寸图',
            'Dimensions and load chart' => '尺寸与载荷图',
            'Select the drawing to view it full size.' => '点击图纸查看大图。',
            'Manufacturing & delivery' => '生产与发货',
            'From our workshop to your port' => '从我们的车间到您的港口',
            'Assembly' => '装配',
            'Load test' => '负载测试',
            'Final check' => '出厂检验',
            'Packing' => '包装',
            'Container loading' => '装柜发货',
            'Marine test stand' => '船吊试验台',
            'Mounted on a deck-style stand, the crane runs every boom and slewing motion before release.' => '装在模拟甲板的试验台上，出厂前逐项运行吊臂和回转动作。',
            'Boom sections' => '吊臂制作',
            'Slewing base, column and booms are assembled by hand in our Xuzhou workshop.' => '回转座、立柱和吊臂在徐州自有车间逐台装配。',
            'The crane lifts test weights through its working range before release.' => '出厂前吊起测试配重，在整个工作范围内检验动作。',
            'Finished cranes are inspected and lined up for dispatch.' => '整机检验合格后排列待发。',
            'Wrapped in film and strapped to protect paint and hydraulics in transit.' => '缠膜并加固，运输途中保护漆面和液压件。',
            'Loaded into containers at the factory and shipped to your port.' => '在工厂直接装柜，发往您的港口。',
            'Telescopic boom sections are finished and fitted before assembly.' => '伸缩臂各节加工完成后进入装配。',
            'Columns, winches and control valves are assembled in our Xuzhou workshop.' => '立柱、卷扬和多路阀在徐州自有车间装配。',
            'photo' => '张照片',
            'video' => '个视频',
            'Where this crane works' => '应用现场',
            'Photos and videos from customers' => '客户发来的照片和视频',
            'All customer feedback' => '查看全部客户反馈',
            'Former model' => '旧型号',
            'Product technical advantages' => '产品技术优势',
            'Series' => '系列',
            'photos' => '张照片',
            'videos' => '个视频',
            'Previous' => '上一个',
            'Next' => '下一个',
            'Specifications' => '技术参数',
            'Crane manufacturer since 2003' => '始于2003年的起重机制造商',
            'Truck Mounted Cranes' => '随车起重机',
            'Truck mounted cranes, built to lift.' => '随车起重机，为可靠吊装而造。',
            'Knuckle boom and telescopic truck cranes from 800 kg to 50 t, built in our 37,000 m² factory in Xuzhou and exported to 120+ countries.' => '800 kg 至 50 t 的折臂式与伸缩臂式随车起重机，由徐州 37,000 m² 自有工厂制造，出口至 120 多个国家和地区。',
            'View Products' => '查看产品',
            'View Truck Cranes' => '查看随车起重机',
            'Request a Quote' => '申请报价',
            'BOB-LIFT knuckle boom truck crane lifting a precast concrete panel' => 'BOB-LIFT 折臂式随车起重机吊装预制混凝土墙板',
            'Marine Cranes' => '船用起重机',
            'Marine cranes, built for work at sea.' => '船用起重机，为海上作业而造。',
            'Knuckle boom and telescopic marine cranes from 800 kg to 25 t for vessels, ports and offshore work, configured to your deck and lifting needs.' => '800 kg 至 25 t 的折臂式与伸缩臂式船用起重机，适用于船舶、港口和海上作业，可按甲板与吊装需求配置。',
            'View Marine Cranes' => '查看船用起重机',
            'BOB-LIFT knuckle boom marine crane lifting a cargo container on a supply vessel' => 'BOB-LIFT 折臂式船用起重机在补给船上吊装集装箱',
            'Pause slideshow' => '暂停轮播',
            'Play slideshow' => '播放轮播',
        ],
    ];
    // Page-specific dictionaries live in inc/translations/*.php, each returning a map of language code => [English text => translation].
    foreach (glob(get_template_directory() . '/inc/translations/*.php') ?: [] as $file) {
        $extra = include $file;
        foreach (is_array($extra) ? $extra : [] as $language => $strings) {
            foreach (is_array($strings) ? $strings : [] as $source => $translation) {
                if (is_string($source) && is_string($translation)) {
                    $all[$language][$source] = $translation;
                }
            }
        }
    }
    return $all;
}

/**
 * The built-in strings above, as editable theme options: static_text.strings holds one entry per English original,
 * stored per language like every other option section, so translations can be imported and synced through the
 * multilingual plugin instead of code. A language's entry wins over the dictionary when its text differs from the original.
 */
function boblift_static_text_key(string $original): string
{
    return substr(md5($original), 0, 12);
}

function boblift_static_text_defaults(): array
{
    static $defaults = null;
    if ($defaults !== null) {
        return $defaults;
    }
    $strings = [];
    foreach (boblift_static_text_translations() as $dictionary) {
        foreach ($dictionary as $original => $translation) {
            $strings[boblift_static_text_key((string) $original)] ??= ['original' => (string) $original, 'text' => (string) $original];
        }
    }
    return $defaults = ['strings' => $strings];
}

function boblift_static_text_overrides(string $language): array
{
    static $cache = [];
    if (isset($cache[$language])) {
        return $cache[$language];
    }
    $section = function_exists('boblift_mls_section_options') ? boblift_mls_section_options('static_text', $language) : [];
    $overrides = [];
    foreach (is_array($section['strings'] ?? null) ? $section['strings'] : [] as $entry) {
        $original = is_array($entry) ? trim((string) ($entry['original'] ?? '')) : '';
        $text = is_array($entry) ? (string) ($entry['text'] ?? '') : '';
        if ($original !== '' && trim($text) !== '' && $text !== $original) {
            $overrides[$original] = $text;
        }
    }
    return $cache[$language] = $overrides;
}

function boblift_localized_static_text(string $text, ?string $language = null): string
{
    $trimmed = trim($text);
    if ($trimmed === '') {
        return $text;
    }

    $language = $language ?: (function_exists('boblift_mls_current_language') ? boblift_mls_current_language() : (function_exists('pll_current_language') ? (string) pll_current_language('slug') : ''));
    $source_language = function_exists('boblift_mls_source_language') ? boblift_mls_source_language() : 'en';
    if ($language === '' || $language === $source_language) {
        return $text;
    }

    $overrides = boblift_static_text_overrides($language);
    if (isset($overrides[$trimmed])) {
        return $overrides[$trimmed];
    }

    $translations = boblift_static_text_translations()[$language] ?? [];
    if (isset($translations[$trimmed])) {
        return $translations[$trimmed];
    }

    return $text;
}

function boblift_localize_static_value(mixed $value, ?string $language = null): mixed
{
    if (is_string($value)) {
        return boblift_localized_static_text($value, $language);
    }
    if (!is_array($value)) {
        return $value;
    }
    foreach ($value as $key => $child) {
        $value[$key] = boblift_localize_static_value($child, $language);
    }
    return $value;
}

function boblift_site_ui_defaults(?string $section = null): array
{
    $ui = [
        'contactPage' => [
            'heroLabel' => 'Contact Us',
            'heroTitle' => 'Talk to BOB-LIFT before choosing a crane.',
            'heroDescription' => 'For model selection, site access matching, marine installation and export questions, use the direct contact channels or send an inquiry below.',
            'heroImage' => 'images/page-heroes/sales-team.webp',
            'heroImageAlt' => 'The BOB-LIFT sales and service team',
            'cardLabels' => ['email' => 'Email', 'phone' => 'Phone', 'whatsapp' => 'WhatsApp', 'address' => 'Address'],
            'weChatTitle' => 'WeChat',
            'weChatDescriptionTemplate' => 'Scan the QR code or search ID {wechatId}. The QR block is kept as a compact card so it does not dominate mobile screens.',
            'weChatQrAlt' => 'BOB-LIFT WeChat QR code',
            'locationTitle' => 'Factory contact point',
            'locationDescriptionTemplate' => 'BOB-LIFT is based in {address}. For quick China-side communication, use WeChat or submit the structured inquiry form.',
            'mapTitle' => 'BOB-LIFT factory location map',
            'viewMapLabel' => 'View on map',
        ],
        'quotePage' => [
            'heroLabel' => 'Request Quote',
            'heroTitle' => 'Request a tailored BOB-LIFT quotation.',
            'heroDescription' => 'Share your product interest, destination country, contact details and working conditions. The team can then match a practical lifting solution for your project.',
            'heroImage' => 'images/cta-lift-photo.png',
            'workflowLabel' => 'Quote Workflow',
            'workflowTitle' => 'Fill once, send through your preferred channel.',
            'workflowItems' => [
                ['iconName' => 'file-text', 'title' => 'Describe requirement', 'text' => 'Model, quantity, load and working radius.'],
                ['iconName' => 'mail-check', 'title' => 'Send your inquiry', 'text' => 'Your details become a clean quotation message.'],
                ['iconName' => 'message-circle', 'title' => 'Send by Email or WhatsApp', 'text' => 'You control the final sending channel.'],
            ],
            'productLinkLabel' => 'Need to choose a model first?',
            'productLinkHref' => '/products',
        ],
        'quoteForm' => [
            'label' => 'Request Quote',
            'title' => 'Tell us your lifting requirement.',
            'badge' => '24h reply',
            'modalLabel' => 'Contact BOB-LIFT',
            'modalTitle' => 'Send your inquiry',
            'submitLabel' => 'Send Inquiry',
            'sideLabel' => 'BOB-LIFT Inquiry',
            'sideTitle' => 'Tell us what you need to lift.',
            'sideDescription' => 'Share your contact details and working conditions. Our team will turn it into a clear quotation request.',
            'privacyBubbleText' => 'We strictly protect your privacy information.',
            'fieldRequiredLabel' => 'Required',
            'closeLabel' => 'Close inquiry form',
            'successInlineText' => 'Inquiry sent. Our team will contact you soon.',
            'defaultInquiryText' => 'Hello BOB-LIFT, I would like to request a quotation.',
            'inquirySubject' => 'BOB-LIFT quotation request',
            'selectedProductsLabel' => 'Selected products',
            'selectedProductsTitle' => 'Products in this inquiry',
            'selectedProductsSummary' => 'products will be included in this inquiry.',
            'selectedProductsAriaLabel' => 'Selected products',
            'selectedProductRemoveLabelTemplate' => 'Remove {product} from this inquiry',
            'extraToggleOpen' => 'Add details for an accurate solution',
            'extraToggleClose' => 'Hide additional details',
            'extraSelectPlaceholder' => 'Select one',
            'selectOptions' => [
                'truck_available' => ['Yes', 'No', 'Not sure'],
                'imported_from_china' => ['Yes', 'No'],
                'customs_clearance' => ['Yes', 'No', 'Need support'],
                'contact_method' => ['WhatsApp', 'Email', 'Either'],
            ],
            'contactTimeNote' => 'Our working hours are Monday to Friday, 8:30 AM–6:00 PM Beijing time.',
            'fields' => [
                'name' => 'Full Name',
                'email' => 'Email',
                'phone' => 'Phone / WhatsApp',
                'company' => 'Company',
                'website' => 'Website',
                'country' => 'Country / Region',
                'product' => 'Product Interest',
                'productNotDecided' => 'Not decided yet',
                'quantity' => 'Expected Quantity',
                'message' => 'Project Details',
                'messagePlaceholder' => 'Load capacity, working radius, site access, destination port, deadline...',
            ],
            'extraFields' => [
                'truck_available' => 'Do you already have a truck?',
                'truck_model' => 'Truck model or chassis brand',
                'truck_specs' => 'Truck chassis specs',
                'imported_from_china' => 'Have you imported from China before?',
                'customs_clearance' => 'Can you handle customs clearance?',
                'destination_port' => 'Destination port / city',
                'preferred_delivery' => 'Preferred delivery time',
                'contact_method' => 'How would you like us to contact you?',
                'preferred_contact_time' => 'Preferred contact time',
                'other_requirements' => 'Other requirements',
            ],
            'buttons' => ['save' => 'Send Inquiry', 'saving' => 'Sending...', 'email' => 'Send Email', 'whatsapp' => 'WhatsApp'],
            'messages' => [
                'successTitle' => 'Inquiry received.',
                'successText' => 'Our team will contact you soon. For anything urgent, use Email or WhatsApp.',
                'requiredFields' => 'Please enter a valid email address and project details.',
                'verificationRequired' => 'Please complete the verification and try again.',
                'verificationLoading' => 'Loading verification...',
                'verificationSubmitAgain' => 'Please complete the verification and submit again.',
                'verificationLoadFailed' => 'Verification could not be loaded. Please try again.',
                'saving' => 'Sending your inquiry...',
                'serverInvalid' => 'The server returned an invalid response. Please refresh and try again.',
                'saveFailed' => 'The inquiry could not be sent. Please try again.',
                'invalidRequest' => 'Invalid inquiry request. Please refresh and try again.',
                'mailSubject' => 'New BOB-LIFT inquiry',
            ],
            'inquiryLineLabels' => ['name' => 'Name', 'email' => 'Email', 'phone' => 'Phone / WhatsApp', 'company' => 'Company', 'website' => 'Website', 'country' => 'Country', 'product' => 'Product', 'quantity' => 'Quantity', 'message' => 'Project details', 'truck_available' => 'Truck available', 'truck_model' => 'Truck model or chassis brand', 'truck_specs' => 'Truck chassis specs', 'imported_from_china' => 'Imported from China before', 'customs_clearance' => 'Customs clearance', 'destination_port' => 'Destination port / city', 'preferred_delivery' => 'Preferred delivery time',
                'contact_method' => 'How would you like us to contact you?',
                'preferred_contact_time' => 'Preferred contact time', 'other_requirements' => 'Other requirements'],
        ],
        'quoteList' => [
            'openLabel' => 'Open Quote List',
            'title' => 'Quote List',
            'brandLabel' => 'BOB-LIFT',
            'countSuffix' => 'products selected',
            'emptyText' => 'No products selected yet.',
            'requestLabel' => 'Request Quote',
            'clearLabel' => 'Clear All',
            'closeLabel' => 'Close Quote List',
            'backTopLabel' => 'Back to top',
            'wechatCloseLabel' => 'Close WeChat QR code',
            'wechatTitle' => 'WeChat QR Code',
            'quoteShortLabel' => 'Quote',
            'wechatScanTemplate' => 'Scan or search ID: {id}',
            'viewDetailsLabel' => 'View details',
            'removeLabelTemplate' => 'Remove {product} from Quote List',
            'addLabel' => 'Add to Quote List',
            'removeLabel' => 'Remove from Quote List',
            'emailAriaLabel' => 'Email',
            'whatsappAriaLabel' => 'WhatsApp',
            'callAriaLabel' => 'Call',
            'wechatAriaLabel' => 'WeChat',
        ],
        'header' => [
            'homeAriaLabel' => 'BOB-LIFT Home',
            'mainNavigationLabel' => 'Main navigation',
            'changeLanguageLabel' => 'Change language',
            'mobileOpenLabel' => 'Open mobile navigation',
            'mobileLabel' => 'Navigation',
            'mobileTitle' => 'BOB-LIFT Menu',
            'productCategoriesLabel' => 'Product Categories',
            'expandProductCategoriesLabel' => 'Expand product categories',
            'collapseProductCategoriesLabel' => 'Collapse product categories',
            'quoteShortLabel' => 'Quote',
            'quoteLabel' => 'Request Quote',
            'languageMarkets' => ['en' => 'Global Market', 'es' => 'Spanish Market', 'ja' => 'Japanese Market', 'zh' => 'Chinese Market', 'ru' => 'Russian Market', 'fr' => 'French Market', 'ar' => 'Arabic Market'],
            'regionalMarketLabel' => 'Regional Market',
            'languageFlagAltTemplate' => '{language} flag',
            'megaTitle' => 'Explore All Products',
            'megaDescription' => 'Discover our full range of lifting solutions.',
            'megaButtonLabel' => 'View Product Catalog',
        ],
        'videoCenter' => [
            'heroLabel' => 'Video Center',
            'heroTitle' => 'See the cranes move before you buy.',
            'heroText' => 'Product walk-arounds, load tests and factory footage, filmed in our own workshop in Xuzhou.',
            'heroImageAlt' => 'Knuckle boom cranes lined up in the BOB-LIFT assembly hall',
            'allLabel' => 'All videos',
            'truckLabel' => 'Truck mounted cranes',
            'marineLabel' => 'Marine cranes',
            'testLabel' => 'Tests and quality',
            'factoryLabel' => 'Factory video',
            'countSuffix' => 'videos',
            'productVideoLabel' => 'Product video',
            'productVideoTitle' => 'Watch this series in action',
            'productVideoNote' => 'Series video: it shows a crane of the same capacity and boom type, so paint and options can differ from this model.',
            'moreVideosLabel' => 'More videos',
            'emptyText' => 'Videos are being added.',
        ],
        'qualityTesting' => [
            'heroLabel' => 'Quality & Testing',
            'heroTitle' => 'Tested on our own test bay before it ships.',
            'heroText' => 'Watch the load, basket and function tests our cranes go through in the factory, and how the workshop checks parts along the way.',
            'heroImageAlt' => 'A knuckle boom crane on the BOB-LIFT test bay',
            'stepsLabel' => 'Before delivery',
            'stepsTitle' => 'Three checks between the assembly line and the container.',
            'steps' => [
                ['title' => 'Parts and welds', 'text' => 'Boom sections, cylinders and hydraulic parts are checked in the workshop before and during assembly.'],
                ['title' => 'Load and function test', 'text' => 'The finished crane is mounted on the test bay and runs through lifting, slewing and outrigger functions under load.'],
                ['title' => 'Final check and packing', 'text' => 'After the test the crane is inspected again, then packed and loaded for export.'],
            ],
            'featuredLabel' => 'In the workshop',
            'testsLabel' => 'Test videos',
            'testsTitle' => 'Load tests filmed on the test bay',
            'batchTitle' => 'Batch orders, built and checked in one run',
            'batchText' => 'Twenty 3.2 t knuckle boom cranes for one order, finished in the workshop and lined up for shipping.',
            'certificatesTitle' => 'Certificates',
            'certificatesText' => 'CE, EAC, ISO 9001 and ISO 14001 certificates and our crane patents are shown on the About page.',
            'certificatesLink' => 'View certificates',
            'ctaTitle' => 'Want to see your crane tested?',
            'ctaText' => 'Ask for a test video of your order before it ships, or visit the factory and watch the test yourself.',
            'ctaButton' => 'Ask for a Test Video',
            'visitLabel' => 'Plan a factory visit',
        ],
        'factoryTour' => [
            'heroLabel' => 'Factory Tour',
            'heroTitle' => 'Walk through our crane factory in Xuzhou.',
            'heroText' => 'From the gate to the test bay: where BOB-LIFT cranes are assembled, tested and packed for export.',
            'heroImageAlt' => 'Aerial view of the BOB-LIFT factory in Xuzhou',
            'facts' => [
                ['value' => '37,000 m²', 'label' => 'Factory area'],
                ['value' => 'Xuzhou, Jiangsu', 'label' => 'Location'],
            ],
            'stops' => [
                ['key' => 'arrival', 'title' => 'Arrival', 'text' => 'Trucks with mounted cranes wait in the yard for delivery or collection.'],
                ['key' => 'assembly', 'title' => 'Assembly halls', 'text' => 'Knuckle and telescopic booms are assembled in long rows under overhead cranes.'],
                ['key' => 'parts', 'title' => 'Parts and hydraulics', 'text' => 'Boom sections, cylinders, frames and hydraulic parts are staged next to the assembly line.'],
                ['key' => 'mounting', 'title' => 'Truck mounting and testing', 'text' => 'Cranes are mounted on truck chassis and tested before they leave.'],
            ],
            'stopLabel' => 'Stop {number}',
            'videoLabel' => 'Factory video',
            'videoTitle' => 'Inside the BOB-LIFT factory',
            'clipsTitle' => 'From the air',
            'qualityLink' => 'See how cranes are tested',
            'ctaTitle' => 'See it in person.',
            'ctaText' => 'Customers from many countries visit the factory before ordering. Tell us your dates and we will arrange the tour.',
            'ctaButton' => 'Plan a Visit',
            'visitsLink' => 'Customer visits',
            'showsTitle' => 'Meet us at trade shows',
            'showsText' => 'You can also see BOB-LIFT cranes at international trade shows. Upcoming shows are announced on our group site.',
            'shows' => [
                ['name' => 'Canton Fair', 'detail' => 'Guangzhou · Booth 4.0G18 · April 2026'],
                ['name' => 'Construction Indonesia', 'detail' => 'Jakarta · September 2026'],
            ],
            'showsLink' => 'Trade shows on boblift.com',
            'showsUrl' => 'https://boblift.com/',
        ],
        'siteSwitcher' => [
            'openLabel' => 'Switch BOB-LIFT site',
            'title' => 'BOB-LIFT sites',
            'mobileTitle' => 'Other BOB-LIFT sites',
            'currentLabel' => 'You are here',
            'footerNote' => 'Lifting equipment site of the BOB-LIFT Group',
            // Leaving for a sister site asks first, the way the loader site does: a green check says it is ours.
            'leaveLabel' => 'You are leaving this website',
            'leaveVerified' => 'Official BOB-LIFT website',
            'leaveText' => 'This is one of our own websites, run by BOB-LIFT. It opens in a new tab, so this page stays open for you.',
            'leaveContinue' => 'Continue to {domain}',
            'leaveStay' => 'Stay here',
            'leaveClose' => 'Close',
            'sites' => [
                ['label' => 'BOB-LIFT', 'short' => 'BOB-LIFT', 'mark' => 'B', 'description' => 'The company, its factory and the full range', 'domain' => 'boblift.com', 'url' => 'https://boblift.com/'],
                ['label' => 'Cranes', 'short' => 'Cranes', 'mark' => 'C', 'description' => 'Truck-mounted, marine and pickup cranes', 'domain' => 'bobliftcrane.com', 'current' => true],
                ['label' => 'Loader', 'short' => 'Loader', 'mark' => 'L', 'description' => 'Mini skid steer loaders and mini dumpers', 'domain' => 'bobliftloader.com', 'url' => 'https://bobliftloader.com/'],
            ],
        ],
        'newsPage' => [
            'heroLabel' => 'BOB-LIFT Newsroom',
            'heroTitle' => 'News & Insights',
            'heroDescription' => 'Stay informed with the latest updates from BOB-LIFT, including product innovations, company news, and industry insights.',
            'heroImage' => 'images/page-heroes/factory-floor.webp',
            'allLabel' => 'All',
            'featuredUpdatesLabel' => 'Featured Updates',
            'readFeaturedLabel' => 'Read Featured',
            'requestQuoteLabel' => 'Request Quote',
            'featuredLabel' => 'Featured Article',
            'readArticleLabel' => 'Read Article',
            'readMoreLabel' => 'Read More',
            'emptyTitle' => 'No matching news found',
            'emptyText' => 'Choose another category to browse more BOB-LIFT updates.',
            'viewModeLabel' => 'News display mode',
            'defaultViewLabel' => 'Default',
            'listViewLabel' => 'List',
            'paginationLabel' => 'News pagination',
        ],
        'newsDetail' => [
            'home' => 'Home',
            'news' => 'News',
            'source' => 'BOB-LIFT Newsroom',
            'breadcrumbLabel' => 'Breadcrumb',
            'summary' => 'Article Summary',
            'category' => 'Category',
            'published' => 'Published',
            'sourceLabel' => 'Source',
            'sourceValue' => 'BOB-LIFT',
            'related' => 'Related Updates',
            'ctaLabel' => 'Need help choosing a crane?',
            'ctaTitle' => 'Talk to BOB-LIFT before your next lifting project.',
            'ctaButton' => 'Request a Quote',
            'relatedArticleCount' => 3,
            'backLabel' => 'Back to News',
            'relatedLabel' => 'Related',
            'quoteCtaLabel' => 'Ask for solution',
        ],
        'casesPage' => [
            'heroLabel' => 'Case Center',
            'heroTitle' => 'Project stories with real working conditions.',
            'heroDescription' => 'Browse customer cases across marine operations, material handling and aerial work applications, then discuss a similar lifting solution with BOB-LIFT.',
            'heroImage' => 'images/page-heroes/marine-deck.webp',
            'heroImageAlt' => 'BOB-LIFT marine knuckle boom cranes on a working deck',
            'primaryCtaLabel' => 'Plan My Project',
            'secondaryCtaLabel' => 'View Cases',
            'listLabel' => 'Field Evidence',
            'listTitle' => 'From lifting challenge to equipment match.',
            'cardTypeLabel' => 'Customer Case',
            'viewCaseLabel' => 'View Case',
            'defaultTypeLabel' => 'Case',
            'sceneCasesLabel' => 'Application Cases',
            'customerFeedbackLabel' => 'Customer Feedback',
            'allCountriesLabel' => 'All countries',
            'allProductsLabel' => 'All products',
            'resetFiltersLabel' => 'Reset filters',
            'emptyTitle' => 'No matching cases found',
            'emptyText' => 'Try another country or product to find a closer reference.',
            'searchCountryLabel' => 'Search country',
            'searchProductLabel' => 'Search product',
            'noResultsLabel' => 'No results',
            'closeFeedbackMediaLabel' => 'Close feedback media',
            'feedbackPhotosLabel' => 'photos',
            'feedbackVideosLabel' => 'videos',
            'feedbackPhotoLabel' => 'photo',
            'feedbackVideoLabel' => 'video',
            'previousLabel' => 'Previous',
            'nextLabel' => 'Next',
            'tabsAriaLabel' => 'Case sections',
        ],
        'caseDetail' => ['home' => 'Home', 'cases' => 'Cases', 'breadcrumbLabel' => 'Breadcrumb', 'backLabel' => 'Back to Case Center', 'factsTitle' => 'Case Facts', 'projectSupportLabel' => 'Project Support', 'discussSimilarLabel' => 'Discuss Similar Project', 'relatedCasesLabel' => 'Related Cases', 'defaultTypeLabel' => 'Customer Case', 'similarProjectPrompt' => 'Planning a similar project?', 'viewAllLabel' => 'View all', 'countryLabel' => 'Country', 'mediaLabel' => 'Media', 'moreFeedbackLabel' => 'More customer feedback', 'askModelTemplate' => 'Interested in the {model}?', 'getQuoteLabel' => 'Get a Quote', 'sitePhotosLabel' => 'Site photos'],
        'successPage' => [
            'label' => 'Inquiry Submitted',
            'title' => 'Thank you for your inquiry.',
            'description' => 'Your request has been received. The BOB-LIFT team will review your project details and contact you soon.',
            'backHomeLabel' => 'Back to Home',
            'browseProductsLabel' => 'Browse Products',
        ],
        'productArchive' => [
            'filtersTitle' => 'Filters',
            'clearAllLabel' => 'Clear All',
            'clearFiltersLabel' => 'Clear Filters',
            'closeFiltersLabel' => 'Close filters',
            'applyFiltersLabel' => 'Apply',
            'categoryFilterLabel' => 'Product Category',
            'allCategoriesLabel' => 'All Products',
            'allInCategoryLabel' => 'All',
            'removeFilterLabel' => 'Remove {label}',
            'capacityFilterLabel' => 'Capacity by Ton',
            'boomFilterLabel' => 'Boom Type',
            'applicationFilterLabel' => 'Application',
            'availabilityFilterLabel' => 'Availability',
            'momentFilterLabel' => 'Lifting Moment',
            'momentShortLabel' => 'Moment',
            'weightFilterLabel' => 'Crane Self Weight',
            // Filter bins; value "min-max" or "min+" (tonnes, t·m, kg), min excluded and max included. Keys hold no dots because the
            // settings editor addresses fields by dotted paths. The capacity bins are the tonnage classes of the old site's
            // "Crane by Ton" pages (1-25 t): each class holds the models above the previous class and up to its own size.
            'capacityBins' => ['0-1' => 'Up to 1 t', '1-2' => '1–2 t', '2-3' => '2–3 t', '3-4' => '3–4 t', '4-5' => '4–5 t', '5-6' => '5–6 t', '6-8' => '6–8 t', '8-10' => '8–10 t', '10-12' => '10–12 t', '12-16' => '12–16 t', '16-20' => '16–20 t', '20-25' => '20–25 t', '25+' => 'Over 25 t'],
            'momentBins' => ['0-5' => 'Up to 5 t·m', '5-10' => '5–10 t·m', '10-20' => '10–20 t·m', '20-40' => '20–40 t·m', '40+' => 'Over 40 t·m'],
            'weightBins' => ['0-1000' => 'Up to 1,000 kg', '1000-2500' => '1,000–2,500 kg', '2500-5000' => '2,500–5,000 kg', '5000+' => 'Over 5,000 kg'],
            'showingTemplate' => 'Showing {count} of {total} products',
            'pageTemplate' => 'Page {page} / {pages}',
            'filterButtonLabel' => 'Filter',
            'sortByLabel' => 'Sort by:',
            'viewModeLabel' => 'Product view mode',
            'gridViewLabel' => 'Grid view',
            'listViewLabel' => 'List view',
            'paginationLabel' => 'Product pagination',
            'previousLabel' => 'Previous',
            'nextLabel' => 'Next',
            'emptyTitle' => 'No matching products found',
            'emptyText' => 'Try adjusting your filters or contact us for a custom lifting solution.',
            // Tonnage classes of the old site's "Crane by Ton" pages (1-25 t): each holds the models above the previous class and up to its own size.
            'capacityRanges' => [
                ['value' => '0 - 1 tons', 'label' => 'Up to 1 t'],
                ['value' => '1 - 2 tons', 'label' => '1–2 t'],
                ['value' => '2 - 3 tons', 'label' => '2–3 t'],
                ['value' => '3 - 4 tons', 'label' => '3–4 t'],
                ['value' => '4 - 5 tons', 'label' => '4–5 t'],
                ['value' => '5 - 6 tons', 'label' => '5–6 t'],
                ['value' => '6 - 8 tons', 'label' => '6–8 t'],
                ['value' => '8 - 10 tons', 'label' => '8–10 t'],
                ['value' => '10 - 12 tons', 'label' => '10–12 t'],
                ['value' => '12 - 16 tons', 'label' => '12–16 t'],
                ['value' => '16 - 20 tons', 'label' => '16–20 t'],
                ['value' => '20 - 25 tons', 'label' => '20–25 t'],
                ['value' => '25+ tons', 'label' => 'Over 25 t'],
            ],
            'boomOptions' => ['telescopic-boom' => 'Telescopic Boom', 'knuckle-boom' => 'Knuckle Boom', 'straight-boom' => 'Straight Boom', 'fly-jib' => 'Fly Jib'],
            'applicationOptions' => ['construction' => 'Construction', 'logistics' => 'Logistics', 'municipal-engineering' => 'Municipal Engineering', 'marine' => 'Marine', 'agriculture' => 'Agriculture', 'mining' => 'Mining'],
            'statusOptions' => ['in-stock' => 'In Stock', 'best-seller' => 'Best Seller', 'customizable' => 'Customizable', 'pre-order' => 'Pre-Order'],
            'sortOptions' => ['recommended' => 'Recommended', 'capacityAsc' => 'Capacity: Low to High', 'capacityDesc' => 'Capacity: High to Low', 'newest' => 'Newest', 'popular' => 'Popular'],
        ],
        'productDetail' => [
            'notFoundTitle' => 'Product not found',
            'specsHeading' => 'Specifications',
            'formerModelLabel' => 'Former model',
            'sourceLabel' => 'Source',
            'drawingLabel' => 'Dimension Drawing',
            'drawingTitle' => 'Dimensions and load chart',
            'drawingHint' => 'Select the drawing to view it full size.',
            'drawingCloseLabel' => 'Close drawing',
            'featuresLabel' => 'Key Features',
            'featuresTitle' => 'Product technical advantages',
            'casesTitle' => 'Where this crane works',
            'detailBodyLabel' => 'Product details',
            'detailImagesHint' => 'Click an image to enlarge',
            'processLabel' => 'Manufacturing & delivery',
            'processTitle' => 'From our workshop to your port',
            // Placeholders pending the user's figures (2026-10-19): lead time in working days and units per container by
            // capacity tier (max t, 0 = above the last tier). boblift_product_buying_terms() flags them to editors until changed.
            'buyingLeadTime' => '15–30',
            'buyingContainers' => [['max' => 3.2, 'gp20' => 4, 'hq40' => 8], ['max' => 8, 'gp20' => 2, 'hq40' => 4], ['max' => 16, 'gp20' => 1, 'hq40' => 2], ['max' => 0, 'gp20' => 0, 'hq40' => 1]],
            // One set per crane type; the product page picks it from the English product category.
            'processSteps' => [
                'knuckle' => [
                    ['image' => 'images/process/knuckle-1-assembly.webp', 'title' => 'Assembly', 'text' => 'Slewing base, column and booms are assembled by hand in our Xuzhou workshop.'],
                    ['image' => 'images/process/knuckle-2-load-test.webp', 'title' => 'Load test', 'text' => 'The crane lifts test weights through its working range before release.'],
                    ['image' => 'images/process/knuckle-3-ready.webp', 'title' => 'Final check', 'text' => 'Finished cranes are inspected and lined up for dispatch.'],
                    ['image' => 'images/process/knuckle-4-packing.webp', 'title' => 'Packing', 'text' => 'Wrapped in film and strapped to protect paint and hydraulics in transit.'],
                    ['image' => 'images/process/knuckle-5-loading.webp', 'title' => 'Container loading', 'text' => 'Loaded into containers at the factory and shipped to your port.'],
                ],
                // Marine sets swap the truck load test for a video of the crane running on the marine test stand.
                'knuckle-marine' => [
                    ['image' => 'images/process/knuckle-1-assembly.webp', 'title' => 'Assembly', 'text' => 'Slewing base, column and booms are assembled by hand in our Xuzhou workshop.'],
                    ['image' => 'videos/process/knuckle-marine-test-poster.webp', 'video' => 'videos/process/knuckle-marine-test.mp4', 'title' => 'Marine test stand', 'text' => 'Mounted on a deck-style stand, the crane runs every boom and slewing motion before release.'],
                    ['image' => 'images/process/knuckle-3-ready.webp', 'title' => 'Final check', 'text' => 'Finished cranes are inspected and lined up for dispatch.'],
                    ['image' => 'images/process/knuckle-4-packing.webp', 'title' => 'Packing', 'text' => 'Wrapped in film and strapped to protect paint and hydraulics in transit.'],
                    ['image' => 'images/process/knuckle-5-loading.webp', 'title' => 'Container loading', 'text' => 'Loaded into containers at the factory and shipped to your port.'],
                ],
                'telescopic-marine' => [
                    ['image' => 'images/process/telescopic-1-boom.webp', 'title' => 'Boom sections', 'text' => 'Telescopic boom sections are finished and fitted before assembly.'],
                    ['image' => 'images/process/telescopic-2-assembly.webp', 'title' => 'Assembly', 'text' => 'Columns, winches and control valves are assembled in our Xuzhou workshop.'],
                    ['image' => 'videos/process/telescopic-marine-test-poster.webp', 'video' => 'videos/process/telescopic-marine-test.mp4', 'title' => 'Marine test stand', 'text' => 'Mounted on a deck-style stand, the crane runs every boom and slewing motion before release.'],
                    ['image' => 'images/process/telescopic-4-packing.webp', 'title' => 'Packing', 'text' => 'Wrapped in film and strapped to protect paint and hydraulics in transit.'],
                    ['image' => 'images/process/telescopic-5-loading.webp', 'title' => 'Container loading', 'text' => 'Loaded into containers at the factory and shipped to your port.'],
                ],
                'telescopic' => [
                    ['image' => 'images/process/telescopic-1-boom.webp', 'title' => 'Boom sections', 'text' => 'Telescopic boom sections are finished and fitted before assembly.'],
                    ['image' => 'images/process/telescopic-2-assembly.webp', 'title' => 'Assembly', 'text' => 'Columns, winches and control valves are assembled in our Xuzhou workshop.'],
                    ['image' => 'images/process/telescopic-3-ready.webp', 'title' => 'Final check', 'text' => 'Finished cranes are inspected and lined up for dispatch.'],
                    ['image' => 'images/process/telescopic-4-packing.webp', 'title' => 'Packing', 'text' => 'Wrapped in film and strapped to protect paint and hydraulics in transit.'],
                    ['image' => 'images/process/telescopic-5-loading.webp', 'title' => 'Container loading', 'text' => 'Loaded into containers at the factory and shipped to your port.'],
                ],
            ],
            'feedbackTitle' => 'Photos and videos from customers',
            'viewAllFeedbackLabel' => 'All customer feedback',
            'featuresSeriesLabel' => 'Series',
            'requestQuoteLabel' => 'Request Quote',
            'viewDetailsLabel' => 'View Details',
            'viewShortLabel' => 'View',
            'quickViewLabel' => 'Quick view',
            'closeQuickViewLabel' => 'Close quick view',
            'requestPdfLabel' => 'Request PDF by Email',
            'heroPdfLabel' => 'PDF Catalog',
            'catalogLead' => [
                'label' => 'PDF Catalog',
                'title' => 'Get the product catalog',
                'description' => 'Enter your email to receive the PDF catalog. The download link will appear immediately after submission.',
                'noFileDescription' => 'Enter your email and our team will send the correct PDF catalog to you shortly.',
                'emailLabel' => 'Email',
                'nameLabel' => 'Name',
                'phoneLabel' => 'WhatsApp / Phone',
                'companyLabel' => 'Company',
                'requiredLabel' => 'Required',
                'submitLabel' => 'Get PDF',
                'downloadLabel' => 'Download PDF',
                'closeLabel' => 'Close catalog request',
                'privacyText' => 'We only use your email to send the requested catalog and follow up professionally.',
                'saving' => 'Saving your catalog request...',
                'successText' => 'Catalog request saved. We also sent the catalog link to your email.',
                'successNoFileText' => 'Request saved. We will email the PDF.',
                'successNoFileToast' => 'Request saved. We will email the PDF.',
                'requiredFields' => 'Please enter a valid email address.',
                'invalidRequest' => 'Invalid catalog request. Please refresh and try again.',
                'saveFailed' => 'The catalog request could not be saved. Please try again.',
                'rateLimited' => 'Please wait a few minutes before requesting another catalog.',
                'adminMailSubject' => 'New BOB-LIFT catalog request',
                'customerMailSubject' => 'Your BOB-LIFT %s catalog',
                'customerMailGreeting' => 'Thank you for requesting the %s catalog.',
                'customerMailDownloadIntro' => 'You can download it here:',
                'customerMailNoFile' => 'Our team will send the correct PDF catalog to you shortly.',
            ],
            'addToQuoteListLabel' => 'Add to Quote List',
            'addedToQuoteListLabel' => 'Added to Quote List',
            'heroQuoteListLabel' => 'Quote List',
            'heroQuoteListActiveLabel' => 'In Quote List',
            'productIntroLabel' => 'Product overview',
            'cinemaSpecsLabel' => 'Engineering data',
            'cinemaApplicationsLabel' => 'Field scenes',
            'cinemaDetailsLabel' => 'Detail scroll',
            'cinemaGalleryLabel' => 'Product stage',
            'viewTechnicalLabel' => 'View Technical Specs',
            'quoteAnchorLabel' => 'Request Quote',
            'quoteTitle' => 'Need a crane matched to your site and lifting task?',
            'quoteDescription' => 'Send your working radius, load requirement, site access, destination country, and delivery schedule. BOB-LIFT will help match a practical configuration.',
            'contactEngineerLabel' => 'Contact Engineer',
            'sectionNavLabel' => 'Product detail sections',
            'pageNavigationLabel' => 'Product page navigation',
            'overviewLabel' => 'Quick Specs',
            'buyerDecisionNavLabel' => 'Quick Specs',
            'buyerDecisionLabel' => 'Quick fit check',
            'buyerDecisionTitle' => 'Confirm fit before requesting a quote.',
            'buyerDecisionDescription' => 'Start with application, key parameters, configuration and export support.',
            'buyerApplicationLabel' => 'Where it fits',
            'buyerSpecLabel' => 'Key checks',
            'buyerCustomLabel' => 'Configuration',
            'buyerExportLabel' => 'Export support',
            'buyerApplicationFallback' => 'Tight access, indoor work, maintenance and compact lifting tasks.',
            'buyerSpecFallback' => 'Share load, radius, lifting height and site limits.',
            'buyerCustomFallback' => 'Confirm radius, load, power, access and destination.',
            'buyerExportFallback' => 'Documents, delivery schedule and support notes.',
            'featuresLabel' => 'Key Features',
            'technicalLabel' => 'Technical Specifications',
            'detailsLabel' => 'Product Details',
            'photosLabel' => 'Product Photos',
            'catalogLabel' => 'Catalog',
            'applicationsLabel' => 'Applications',
            'faqLabel' => 'FAQ',
            'overviewTitle' => 'Built for reliable lifting work.',
            'spiderOverviewTitle' => 'Built for precise lifting in tight working spaces.',
            'overviewSuffix' => 'The configuration focuses on smooth operation, stable control and practical export use for contractors, fleet buyers and project teams.',
            'featureFallbackTitle' => 'Feature',
            'detailImagesTitle' => 'Product Details',
            'photoGalleryTitle' => 'Product Photos',
            'galleryTitle' => 'Product Photos',
            'mainProductViewLabel' => 'Main product view',
            'productDetailImageLabel' => 'Product detail image',
            'productPhotoLabel' => 'Product photo',
            'detailImageAltTemplate' => '{product} detail image',
            'hotspotLabels' => ['Telescopic Boom', 'Hydraulic Cylinder', 'Outriggers', 'Control System', 'Slewing Mechanism', 'Mounting Base'],
            'galleryFallbackItems' => [
                ['label' => 'Boom extension detail', 'icon' => 'maximize-2'],
                ['label' => 'Outrigger stability detail', 'icon' => 'shield-check'],
                ['label' => 'Control system detail', 'icon' => 'sliders-horizontal'],
                ['label' => 'Installation and mounting detail', 'icon' => 'wrench'],
            ],
            'catalogTitle' => 'Product Catalog',
            'downloadCenterLabel' => 'Download Center',
            'downloadCatalogTitle' => 'Download Product Catalog',
            'catalogDescriptionTemplate' => '{model} Product Catalog includes core specifications, working range information, and configuration notes for project evaluation.',
            'catalogCoverAltTemplate' => '{product} catalog cover',
            'applicationOptions' => [
                'construction' => ['title' => 'Construction', 'text' => 'Building sites, structural lifting, material handling.', 'image' => 'images/construction-scene.jpg', 'icon' => 'construction'],
                'logistics' => ['title' => 'Logistics', 'text' => 'Loading, unloading, and cargo handling at various sites.', 'image' => 'images/cta-lift-photo.png', 'icon' => 'truck'],
                'municipal-engineering' => ['title' => 'Municipal Engineering', 'text' => 'Urban construction, road maintenance, and public facility projects.', 'image' => 'images/global-network-blue.jpg', 'icon' => 'building-2'],
                'marine' => ['title' => 'Marine', 'text' => 'Deck service, port handling, and offshore lifting operations.', 'image' => 'images/marine-scene.jpg', 'icon' => 'ship'],
                'agriculture' => ['title' => 'Agriculture', 'text' => 'Farm loading, field transport, and rural equipment support.', 'image' => 'images/telescopic-banner.jpg', 'icon' => 'tractor'],
                'mining' => ['title' => 'Mining', 'text' => 'Remote site handling and heavy material support work.', 'image' => 'images/factory-workshop.jpg', 'icon' => 'wrench'],
            ],
            'faqItems' => [
                ['question' => 'Which configuration should we choose for this model?', 'answer' => 'Send the load, working radius, lifting height, site access and destination. The engineering team will confirm whether the standard configuration fits before quotation.'],
                ['question' => 'Can the crane be customized for our working conditions?', 'answer' => 'BOB-LIFT can adjust configuration around lifting radius, site access, operation scenario and destination requirements.'],
                ['question' => 'What is the typical delivery time?', 'answer' => 'Delivery time depends on model configuration, order quantity and destination. BOB-LIFT confirms the schedule together with the quotation.'],
                ['question' => 'What export documents are provided?', 'answer' => 'Common export support can include commercial invoice, packing list, bill of lading and product documents required for the confirmed order.'],
                ['question' => 'Do you provide installation and operation training?', 'answer' => 'Installation guidance, operation notes and remote technical support can be arranged according to the project requirement.'],
                ['question' => 'What after-sales support is available?', 'answer' => 'After-sales support covers parts consultation, troubleshooting guidance and service coordination for confirmed export orders.'],
            ],
            'fallbackFeatures' => [
                ['title' => 'Flexible Boom Operation', 'text' => 'Telescopic boom extends for versatile lifting in multiple working conditions.'],
                ['title' => 'Stable & Safe Lifting', 'text' => 'Outriggers support stable operation for material handling and site work.'],
                ['title' => 'Wide Application', 'text' => 'Designed for construction, logistics, utility work and municipal projects.'],
                ['title' => 'Export-Ready Configuration', 'text' => 'Prepared for overseas projects with practical documentation and delivery support.'],
                ['title' => 'Custom Configuration Matching', 'text' => 'Can be matched with compatible site access, load and destination requirements.'],
            ],
            'statusLabels' => ['needs-review' => 'Needs review', 'in-review' => 'In review', 'changes-required' => 'Changes required', 'verified-from-source' => 'Verified from source'],
            'productStatusLabels' => ['in-stock' => 'In Stock', 'best-seller' => 'Best Seller', 'pre-order' => 'Pre-Order', 'customizable' => 'Customizable'],
            'specLabels' => ['specificationStatus' => 'Specification Status', 'model' => 'Model', 'parameterReview' => 'Parameter Review', 'maxLift' => 'Max Lift', 'boomLength' => 'Boom Length', 'rotation' => 'Rotation', 'customValue' => 'Custom'],
        ],
        'aboutPage' => [
            'primaryCtaLabel' => 'Contact Team',
            'secondaryCtaLabel' => 'View Products',
            'strengthLabel' => 'Company Strength',
            'strengthTitle' => 'Manufacturing, export and service in one team.',
            'timelineLabel' => 'Development Timeline',
            'stepLabel' => 'Step',
            'leadershipWatermark' => 'LEADERSHIP',
            'galleryImageCount' => 4,
        ],
        'homePage' => [
            'heroAriaLabel' => 'Featured lifting solutions',
            'pauseHeroLabel' => 'Pause slideshow',
            'playHeroLabel' => 'Play slideshow',
            'qualityCertificationsLabel' => 'Quality certifications',
            'showGalleryTemplate' => 'Show {item}',
            'galleryFallbackLabel' => 'gallery image',
            'videoLabel' => 'Video',
            'readMoreLabel' => 'Read More',
            'defaultGalleryAlts' => ['BOB-LIFT assembly workshop in Xuzhou', 'BOB-LIFT technician assembling a crane column', 'Finishing a boom section in the BOB-LIFT workshop', 'The BOB-LIFT team', 'Customers visiting the BOB-LIFT factory'],
        ],
        'search' => [
            'panelLabel' => 'Site search',
            'openLabel' => 'Search the site',
            'inputLabel' => 'Search products and articles',
            'placeholder' => 'Search a model, crane type or topic',
            'submitLabel' => 'Search',
            'browseLabel' => 'Browse by category',
            'pageLabel' => 'Search results',
            'titleTemplate' => 'Results for “{query}”',
            'countTemplate' => '{count} results',
            'emptyQueryTitle' => 'Search BOB-LIFT',
            'emptyQueryText' => 'Enter a model number such as {example}, a crane type or a topic.',
            'tabAll' => 'All results',
            'tabProducts' => 'Products',
            'tabNews' => 'News',
            'tabCases' => 'Cases',
            'viewAllTemplate' => 'View all {count}',
            'noResultsTitle' => 'No results for “{query}”',
            'noResultsText' => 'Check the model number or try a shorter keyword. You can also browse the categories below.',
            'helpTitle' => 'Not sure which crane fits?',
            'helpText' => 'Tell us the load, reach and vehicle or vessel, and our engineers will recommend a model.',
            'helpButton' => 'Ask an engineer',
            'seoTitleTemplate' => 'Search: {query} | BOB-LIFT',
            'seoTitleEmpty' => 'Search | BOB-LIFT',
            'paginationLabel' => 'Search pagination',
        ],
        'common' => [
            'brandLabel' => 'BOB-LIFT',
            'noContentFound' => 'No content found',
        ],
    ];

    // Page-specific copy lives in inc/site-ui-sections/*.php, each returning [sectionKey => [...]].
    foreach (glob(get_template_directory() . '/inc/site-ui-sections/*.php') ?: [] as $file) {
        $extra = include $file;
        $ui = array_merge($ui, is_array($extra) ? $extra : []);
    }
    return $section ? ($ui[$section] ?? []) : $ui;
}

/**
 * Copy an administrator customised in fields the redesign replaced, carried into the new fields until those are saved
 * themselves. "Saved" means present in the stored site_ui (any settings save stores the whole editor), so the check does not
 * depend on the language the defaults are shown in. The settings editor is prefilled with the same values, so the first save
 * moves the old copy into the new fields instead of replacing it with defaults. Covered: the contact page location copy,
 * and the old article sidebar's CTA, source credit and related-articles heading.
 * Returns [section => [key => value]] for the new keys only.
 */
function boblift_site_ui_legacy_overrides(array $saved): array
{
    $overrides = [];
    $section = static fn(string $name): array => is_array($saved[$name] ?? null) ? $saved[$name] : [];
    $legacy_text = static function (array $values, string $key, array $old_defaults): string {
        // A translated site stores the old defaults translated: those are not customisations either.
        foreach ($old_defaults as $old_default) {
            if (function_exists('boblift_localized_static_text')) {
                $old_defaults[] = trim((string) boblift_localized_static_text($old_default));
            }
        }
        $value = trim((string) ($values[$key] ?? ''));
        return $value !== '' && !in_array($value, $old_defaults, true) ? $value : '';
    };

    $contact = $section('contactPage');
    $map = ['locationTitle' => 'locationHeading', 'locationDescriptionTemplate' => 'locationDescription'];
    foreach ($map as $legacy_key => $new_key) {
        $value = $legacy_text($contact, $legacy_key, []);
        if ($value !== '' && !array_key_exists($new_key, $contact)) {
            $overrides['contactPage'][$new_key] = $value;
        }
    }

    $detail = $section('newsDetail');
    $article = $section('newsArticle');
    $map = [
        'ctaTitle' => ['contactTitle', ['Talk to BOB-LIFT before your next lifting project.']],
        'ctaButton' => ['contactButton', ['Request a Quote']],
    ];
    $map_related = ['related' => ['relatedTitle', ['Related Updates']]];
    foreach ($map + $map_related as $legacy_key => [$new_key, $old_defaults]) {
        $value = $legacy_text($detail, $legacy_key, $old_defaults);
        if ($value !== '' && !array_key_exists($new_key, $article)) {
            $overrides['newsArticle'][$new_key] = $value;
        }
    }
    // A customised old source credit (e.g. an external publisher) becomes the author card, so the article is not presented
    // as written in-house.
    $source = $legacy_text($detail, 'sourceValue', ['BOB-LIFT']);
    if ($source !== '' && !array_key_exists('authorName', $article) && !array_key_exists('authorText', $article)) {
        $overrides['newsArticle']['authorName'] = $source;
        $overrides['newsArticle']['authorText'] = '';
    }

    // Saved capacity ranges ("1 - 2 tons" => label) become capacity bins ("1-2" => label) until the bins themselves are saved.
    $archive = $section('productArchive');
    if (!array_key_exists('capacityBins', $archive) && is_array($archive['capacityRanges'] ?? null)) {
        $bins = [];
        foreach ($archive['capacityRanges'] as $range) {
            if (!is_array($range)) {
                continue;
            }
            $label = trim((string) ($range['label'] ?? ''));
            if (!preg_match('/^\s*(\d+(?:\.\d+)?)\s*(?:(?:-|–|—|to)\s*(\d+(?:\.\d+)?)|(\+))/u', (string) ($range['value'] ?? ''), $match)) {
                continue;
            }
            // Bin keys hold no dots (the settings editor addresses fields by dotted paths), so decimals use "_".
            $min = str_replace('.', '_', $match[1]);
            $key = ($match[3] ?? '') === '+' ? $min . '+' : $min . '-' . str_replace('.', '_', $match[2]);
            $bins[$key] = $label !== '' ? $label : $key;
        }
        if ($bins !== []) {
            $overrides['productArchive']['capacityBins'] = $bins;
        }
    }
    return $overrides;
}

function boblift_site_ui(?string $section = null): array
{
    static $cache = [];
    $language = function_exists('boblift_mls_current_language') ? boblift_mls_current_language() : (function_exists('pll_current_language') ? (string) pll_current_language('slug') : 'default');
    $language = $language !== '' ? $language : 'default';
    if (!isset($cache[$language])) {
        $defaults = boblift_site_ui_defaults();
        $saved = function_exists('boblift_option') ? boblift_option('site_ui', []) : [];
        $merged = function_exists('boblift_array_replace_recursive') ? boblift_array_replace_recursive($defaults, is_array($saved) ? $saved : []) : array_replace_recursive($defaults, is_array($saved) ? $saved : []);
        // boblift_option() already merges the defaults in; the legacy carry-over needs what was actually stored.
        $raw = function_exists('boblift_mls_get_raw_options') ? boblift_mls_get_raw_options($language !== 'default' ? $language : null) : get_option('boblift_theme_options', []);
        $raw_site_ui = is_array($raw['site_ui'] ?? null) ? $raw['site_ui'] : [];
        $overrides = boblift_site_ui_legacy_overrides($raw_site_ui);
        $merged = array_replace_recursive($merged, $overrides);
        // Saved (or migrated) filter bins replace the default list rather than merging into it: their keys are the ranges.
        $raw_archive = is_array($raw_site_ui['productArchive'] ?? null) ? $raw_site_ui['productArchive'] : [];
        foreach (['capacityBins', 'momentBins', 'weightBins'] as $bins_key) {
            $bins = $overrides['productArchive'][$bins_key] ?? $raw_archive[$bins_key] ?? null;
            if (is_array($bins) && $bins !== []) {
                $merged['productArchive'][$bins_key] = $bins;
            }
        }
        $cache[$language] = boblift_localize_static_value($merged, $language);
    }
    return $section ? ($cache[$language][$section] ?? []) : $cache[$language];
}

function boblift_product_boom_options(): array
{
    $archive_ui = boblift_site_ui('productArchive');
    $configured = is_array($archive_ui['boomOptions'] ?? null) ? $archive_ui['boomOptions'] : [];
    $options = [];
    foreach ($configured as $key => $label) {
        $key = sanitize_title((string) $key);
        if ($key === '' || $key === 'custom-solution') {
            continue;
        }
        $options[$key] = (string) $label;
    }
    return $options;
}

function boblift_normalize_product_boom_type(string $boom_type): string
{
    $boom_type = sanitize_title($boom_type);
    return isset(boblift_product_boom_options()[$boom_type]) ? $boom_type : '';
}

function boblift_contact_settings(): array
{
    $contact = boblift_option('contact', []);
    $email = $contact['email'] ?? 'info@bobliftcrane.com';
    $phone = $contact['phone'] ?? '+86 15852499415';
    $phone_href = $contact['phone_href'] ?? ('tel:' . preg_replace('/[^\d+]+/', '', $phone));
    $whatsapp = $contact['whatsapp'] ?? $phone;
    $whatsapp_number = preg_replace('/\D+/', '', $whatsapp) ?: '8615852499415';

    return [
        'email' => $email,
        'phone' => $phone,
        'phoneHref' => $phone_href,
        'whatsapp' => $whatsapp,
        'whatsappNumber' => $whatsapp_number,
        'whatsappHref' => 'https://wa.me/' . $whatsapp_number . '?text=' . rawurlencode('Hello BOB-LIFT, I would like to request a lifting solution.'),
        'telegram' => $contact['telegram'] ?? ($contact['whatsapp'] ?? $phone),
        'wechatId' => $contact['wechat_id'] ?? $whatsapp_number,
        'wechatQr' => $contact['wechat_qr'] ?? 'images/wechat-qr.png',
        'address' => $contact['address'] ?? 'Xuzhou, China',
        'landline' => trim((string) ($contact['landline'] ?? '')),
        'inquirySubject' => (string) ((boblift_site_ui('quoteForm')['inquirySubject'] ?? 'BOB-LIFT quotation request')),
        'defaultInquiryText' => (string) ((boblift_site_ui('quoteForm')['defaultInquiryText'] ?? 'Hello BOB-LIFT, I would like to request a quotation.')),
    ];
}


function boblift_floating_contact_buttons(): array
{
    $defaults = function_exists('boblift_floating_contact_button_defaults') ? boblift_floating_contact_button_defaults() : [];
    $value = function_exists('boblift_option') ? boblift_option('floating_contact_buttons', $defaults) : $defaults;
    if (!is_array($value)) {
        return $defaults;
    }

    $buttons = [];
    foreach ($value as $button) {
        if (!is_array($button) || (string) ($button['enabled'] ?? '1') !== '1') {
            continue;
        }
        $type = sanitize_key((string) ($button['type'] ?? 'custom'));
        $label = trim((string) ($button['label'] ?? ''));
        $icon = sanitize_key((string) ($button['icon'] ?? $type));
        $buttons[] = [
            'enabled' => '1',
            'type' => $type !== '' ? $type : 'custom',
            'label' => $label !== '' ? $label : ucfirst($type),
            'href' => trim((string) ($button['href'] ?? '')),
            'icon' => $icon !== '' ? $icon : $type,
            'target' => (string) ($button['target'] ?? '') === '_blank' ? '_blank' : '',
        ];
    }

    return $buttons;
}

function boblift_telegram_contact_href(string $telegram): string
{
    $value = trim($telegram);
    if ($value === '') {
        return '';
    }
    if (preg_match('#^(https?://|tg://)#i', $value)) {
        return $value;
    }
    if (str_starts_with($value, '@')) {
        return 'https://t.me/' . rawurlencode(ltrim($value, '@'));
    }
    $digits = preg_replace('/\D+/', '', $value);
    if ($digits !== '') {
        return 'https://t.me/+' . $digits;
    }
    return 'https://t.me/' . rawurlencode($value);
}

function boblift_floating_contact_href(array $button, array $contact): string
{
    $type = sanitize_key((string) ($button['type'] ?? 'custom'));
    $href = trim((string) ($button['href'] ?? ''));
    $email = trim((string) ($contact['email'] ?? 'info@bobliftcrane.com'));
    $phone = trim((string) ($contact['phone'] ?? '+86 15852499415'));
    $phone_href = trim((string) ($contact['phone_href'] ?? ('tel:' . preg_replace('/[^\d+]+/', '', $phone))));
    $whatsapp_raw = trim((string) ($contact['whatsapp'] ?? $phone));
    $whatsapp = preg_replace('/\D+/', '', $whatsapp_raw);
    $wechat_id = trim((string) ($contact['wechat_id'] ?? $whatsapp));
    $telegram = trim((string) ($contact['telegram'] ?? $whatsapp_raw));
    $telegram_href = boblift_telegram_contact_href($telegram !== '' ? $telegram : $whatsapp_raw);

    if ($href === '' || ($type === 'telegram' && rtrim($href, '/') === 'https://t.me')) {
        $href = match ($type) {
            'email' => 'mailto:{email}',
            'whatsapp' => 'https://wa.me/{whatsapp}',
            'phone' => '{phone_href}',
            'telegram' => '{telegram_href}',
            default => '',
        };
    }

    $href = str_replace(
        ['{email}', '{phone}', '{phone_href}', '{whatsapp}', '{whatsapp_raw}', '{wechat_id}', '{telegram}', '{telegram_href}'],
        [$email, $phone, $phone_href, $whatsapp, $whatsapp_raw, $wechat_id, $telegram, $telegram_href],
        $href
    );

    return trim($href);
}

function boblift_floating_contact_icon(array $button, string $class = 'h-5 w-5'): string
{
    $icon = sanitize_key((string) ($button['icon'] ?? $button['type'] ?? 'arrow-right'));
    return boblift_icon($icon !== '' ? $icon : 'arrow-right', $class);
}

function boblift_render_floating_contact_button(array $button, array $contact, array $quote_list_ui): void
{
    $type = sanitize_key((string) ($button['type'] ?? 'custom'));
    $label = trim((string) ($button['label'] ?? ''));
    $label = $label !== '' ? $label : ucfirst($type);
    $aria_label = match ($type) {
        'email' => (string) ($quote_list_ui['emailAriaLabel'] ?? $label),
        'whatsapp' => (string) ($quote_list_ui['whatsappAriaLabel'] ?? $label),
        'phone' => (string) ($quote_list_ui['callAriaLabel'] ?? $label),
        'wechat' => (string) ($quote_list_ui['wechatAriaLabel'] ?? $label),
        default => $label,
    };

    if ($type === 'quote') {
        $inquiry_attrs = '';
        $current_post = get_post();
        if (is_singular('product') && $current_post instanceof WP_Post && function_exists('boblift_product_from_post')) {
            $product = boblift_product_from_post($current_post);
            $product_title = (string) ($product['title'] ?? get_the_title($current_post));
            $product_slug = (string) ($product['slug'] ?? $current_post->post_name);
            $product_item = [
                'id' => (string) ($product['id'] ?? ($product_slug !== '' ? $product_slug : (string) $current_post->ID)),
                'title' => $product_title,
                'slug' => $product_slug,
                'image' => function_exists('boblift_product_image') ? boblift_product_image($product) : '',
                'category' => (string) ($product['category_title'] ?? 'BOB-LIFT'),
                'detailUrl' => function_exists('boblift_product_url') ? boblift_product_url($product) : get_permalink($current_post),
            ];
            $inquiry_attrs = ' data-inquiry-product="' . esc_attr($product_title) . '" data-inquiry-product-slug="' . esc_attr($product_slug) . '" data-inquiry-products="' . esc_attr((string) wp_json_encode([$product_item], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '"';
        }
        echo '<button class="inline-flex h-11 min-w-0 flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-sm bg-navy px-4 text-sm font-black text-white transition hover:bg-[#0b2d5b] lg:size-[40px] lg:flex-none lg:px-0 min-[1780px]:size-12" type="button" data-inquiry-modal-open data-inquiry-source="浮动联系按钮"' . $inquiry_attrs . ' aria-label="' . esc_attr($aria_label) . '"><span class="lg:hidden">' . esc_html($label) . '</span>' . boblift_floating_contact_icon($button, 'h-4 w-4') . '</button>';
        return;
    }

    if ($type === 'wechat') {
        echo '<button class="floating-contact-link" type="button" data-qr-open aria-label="' . esc_attr($aria_label) . '">' . boblift_floating_contact_icon($button) . '</button>';
        return;
    }

    $href = boblift_floating_contact_href($button, $contact);
    if ($href === '') {
        return;
    }
    $target = (string) ($button['target'] ?? '') === '_blank' ? ' target="_blank" rel="noreferrer"' : '';
    echo '<a class="floating-contact-link" href="' . esc_url($href) . '"' . $target . ' aria-label="' . esc_attr($aria_label) . '">' . boblift_floating_contact_icon($button) . '</a>';
}

function boblift_product_options(int $limit = 0): array
{
    $items = array_map(static function (array $product): array {
        return [
            'key' => (string) ($product['id'] ?? $product['slug'] ?? $product['title']),
            'label' => (string) ($product['title'] ?? ''),
            'value' => (string) ($product['title'] ?? ''),
            'slug' => (string) ($product['slug'] ?? ''),
        ];
    }, boblift_all_products());

    $items = array_values(array_filter($items, static fn(array $item): bool => $item['label'] !== ''));
    return $limit > 0 ? array_slice($items, 0, $limit) : $items;
}

function boblift_product_interest_options(): array
{
    $localized_category_title = static function (string $slug, string $fallback): string {
        $term = function_exists('boblift_term_by_slug_for_current_language') ? boblift_term_by_slug_for_current_language($slug, 'product_category') : null;
        if ($term instanceof WP_Term && trim((string) $term->name) !== '') {
            return (string) $term->name;
        }
        return function_exists('boblift_localized_static_text') ? boblift_localized_static_text($fallback) : $fallback;
    };
    $categories = function_exists('boblift_seed_product_categories')
        ? boblift_seed_product_categories()
        : (function_exists('boblift_categories') ? boblift_categories() : []);
    $category_map = [];
    foreach ($categories as $category) {
        $slug = (string) ($category['slug'] ?? '');
        if ($slug !== '') {
            $category_map[$slug] = $category;
        }
    }

    $skip_slugs = [];
    $group_titles = [];
    $items = [];
    $groups = function_exists('boblift_product_category_group_definitions') ? boblift_product_category_group_definitions() : [];
    $group_of_child = [];
    foreach ($groups as $group) {
        foreach (($group['children'] ?? []) as $child_slug) {
            $skip_slugs[(string) $child_slug] = true;
            $group_of_child[(string) $child_slug] = (string) ($group['slug'] ?? '');
        }
        $group_titles[(string) ($group['slug'] ?? '')] = (string) ($group['title'] ?? '');
    }

    usort($categories, static fn(array $a, array $b): int => ((int) ($a['sort'] ?? 100)) <=> ((int) ($b['sort'] ?? 100)));
    foreach ($categories as $category) {
        $slug = (string) ($category['slug'] ?? '');
        // Catalogs with the child categories but no parent category of their own (the synthetic grouping) still offer the
        // family once, from the group definition, where its first child would have been.
        $group_slug = $group_of_child[$slug] ?? '';
        if ($group_slug !== '' && !isset($category_map[$group_slug]) && !isset($items[$group_slug])) {
            $group_title = $localized_category_title($group_slug, $group_titles[$group_slug] ?? '');
            if ($group_title !== '') {
                $items[$group_slug] = ['key' => $group_slug, 'label' => $group_title, 'value' => $group_title, 'slug' => $group_slug];
            }
        }
        $title = $localized_category_title($slug, (string) (($group_titles[$slug] ?? '') ?: ($category['title'] ?? '')));
        if ($slug === '' || $title === '' || isset($skip_slugs[$slug]) || isset($items[$slug])) {
            continue;
        }
        $items[$slug] = [
            'key' => $slug,
            'label' => $title,
            'value' => $title,
            'slug' => $slug,
        ];
    }

    return array_values($items);
}

function boblift_icon(string $name, string $class = 'h-5 w-5', string $stroke_width = '2'): string
{
    $brand_paths = [
        'whatsapp' => 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.296-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.71.306 1.263.489 1.694.625.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347M12.05 21.785h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.999-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.002-5.45 4.436-9.884 9.889-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.887 9.884M20.52 3.449C18.258 1.185 15.25-.061 12.05-.062 5.445-.062.07 5.313.068 11.92c0 2.111.55 4.172 1.595 5.989L-.033 24.105l6.342-1.663a11.95 11.95 0 0 0 5.736 1.461h.005c6.607 0 11.982-5.375 11.985-11.983A11.906 11.906 0 0 0 20.52 3.45',
        'telegram' => 'M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 11.944 0m4.962 7.224c.132-.002.428.03.62.186.162.131.207.308.229.433.022.126.05.412.028.636-.252 2.653-1.343 9.09-1.898 12.06-.235 1.257-.697 1.68-1.145 1.72-.973.09-1.712-.643-2.655-1.261-1.475-.967-2.308-1.568-3.74-2.512-1.653-1.09-.581-1.69.36-2.667.247-.256 4.539-4.159 4.622-4.512.01-.044.02-.21-.078-.297-.098-.087-.243-.057-.347-.033-.148.033-2.507 1.59-7.077 4.674-.67.46-1.276.684-1.82.672-.6-.013-1.754-.34-2.612-.619-1.052-.342-1.888-.523-1.815-1.104.038-.302.454-.611 1.248-.928 4.896-2.133 8.16-3.54 9.794-4.22 4.662-1.94 5.63-2.277 6.286-2.288',
    ];
    if (isset($brand_paths[$name])) {
        return '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="' . esc_attr($brand_paths[$name]) . '"/></svg>';
    }
    $paths = [
        'mail' => '<path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7"/><rect x="2" y="4" width="20" height="16" rx="2"/>',
        'phone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.32 1.77.59 2.61a2 2 0 0 1-.45 2.11L8 9.69a16 16 0 0 0 6 6l1.25-1.25a2 2 0 0 1 2.11-.45c.84.27 1.71.47 2.61.59A2 2 0 0 1 22 16.92Z"/>',
        'phone-call' => '<path d="M13 2a9 9 0 0 1 9 9"/><path d="M13 6a5 5 0 0 1 5 5"/><path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384"/>',
        'message-circle' => '<path d="M2.992 16.342a2 2 0 0 1 .094 1.167l-1.065 3.29a1 1 0 0 0 1.236 1.168l3.413-.998a2 2 0 0 1 1.099.092 10 10 0 1 0-4.777-4.719"/>',
        'map-pin' => '<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>',
        'file-text' => '<path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>',
        'download' => '<path d="M12 15V3"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/>',
        'book-open' => '<path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/>',
        'home' => '<path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-6a2 2 0 0 1 2.582 0l7 6A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
        'layers' => '<path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83z"/><path d="M2 12a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 12"/><path d="M2 17a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 17"/>',
        'maximize-2' => '<path d="M15 3h6v6"/><path d="m21 3-7 7"/><path d="m3 21 7-7"/><path d="M9 21H3v-6"/>',
        'rotate-cw' => '<path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/>',
        'shield-check' => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
        'building-2' => '<path d="M10 12h4"/><path d="M10 8h4"/><path d="M14 21v-3a2 2 0 0 0-4 0v3"/><path d="M6 10H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2"/><path d="M6 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"/>',
        'truck' => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
        'wrench' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.106-3.105c.32-.322.863-.22.983.218a6 6 0 0 1-8.259 7.057l-7.91 7.91a1 1 0 0 1-2.999-3l7.91-7.91a6 6 0 0 1 7.057-8.259c.438.12.54.662.219.984z"/>',
        'construction' => '<rect x="2" y="6" width="20" height="8" rx="1"/><path d="M17 14v7"/><path d="M7 14v7"/><path d="M17 3v3"/><path d="M7 3v3"/><path d="M10 14 2.3 6.3"/><path d="m14 6 7.7 7.7"/><path d="m8 6 8 8"/>',
        'ship' => '<path d="M12 10.189V14"/><path d="M12 2v3"/><path d="M19 13V7a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v6"/><path d="M19.38 20A11.6 11.6 0 0 0 21 14l-8.188-3.639a2 2 0 0 0-1.624 0L3 14a11.6 11.6 0 0 0 2.81 7.76"/><path d="M2 21c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1s1.2 1 2.5 1c2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/>',
        'tractor' => '<path d="m10 11 11 .9a1 1 0 0 1 .8 1.1l-.665 4.158a1 1 0 0 1-.988.842H20"/><path d="M16 18h-5"/><path d="M18 5a1 1 0 0 0-1 1v5.573"/><path d="M3 4h8.129a1 1 0 0 1 .99.863L13 11.246"/><path d="M4 11V4"/><path d="M7 15h.01"/><path d="M8 10.1V4"/><circle cx="18" cy="18" r="2"/><circle cx="7" cy="15" r="5"/>',
        'mail-check' => '<path d="M22 13V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v12c0 1.1.9 2 2 2h8"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/><path d="m16 19 2 2 4-4"/>',
        'send' => '<path d="M14.536 21.686a.5.5 0 0 0 .937-.024l6.5-19a.496.496 0 0 0-.635-.635l-19 6.5a.5.5 0 0 0-.024.937l7.93 3.18a2 2 0 0 1 1.112 1.11z"/><path d="m21.854 2.147-10.94 10.939"/>',
        'calendar' => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>',
        'clock-3' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6h4"/>',
        'flame' => '<path d="M12 3q1 4 4 6.5t3 5.5a1 1 0 0 1-14 0 5 5 0 0 1 1-3 1 1 0 0 0 5 0c0-2-1.5-3-1.5-5q0-2 2.5-4"/>',
        'sliders-horizontal' => '<path d="M10 5H3"/><path d="M12 19H3"/><path d="M14 3v4"/><path d="M16 17v4"/><path d="M21 12h-9"/><path d="M21 19h-5"/><path d="M21 5h-7"/><path d="M8 10v4"/><path d="M8 12H3"/>',
        'grid-3x3' => '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M3 15h18"/><path d="M9 3v18"/><path d="M15 3v18"/>',
        'list' => '<path d="M3 5h.01"/><path d="M3 12h.01"/><path d="M3 19h.01"/><path d="M8 5h13"/><path d="M8 12h13"/><path d="M8 19h13"/>',
        'menu' => '<path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'arrow-up-right' => '<path d="M7 7h10v10"/><path d="M7 17 17 7"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'eye' => '<path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/>',
        'heart' => '<path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5"/>',
        'weight' => '<circle cx="12" cy="5" r="3"/><path d="M6.5 8a2 2 0 0 0-1.905 1.46L2.1 18.5A2 2 0 0 0 4 21h16a2 2 0 0 0 1.925-2.54L19.4 9.5A2 2 0 0 0 17.48 8Z"/>',
        'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'search-x' => '<path d="m13.5 8.5-5 5"/><path d="m8.5 8.5 5 5"/><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'x' => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        'chevron-left' => '<path d="m15 18-6-6 6-6"/>',
        'images' => '<path d="M18 22H4a2 2 0 0 1-2-2V6"/><path d="m22 13-1.296-1.296a2.41 2.41 0 0 0-3.408 0L11 18"/><circle cx="12" cy="8" r="2"/><rect width="16" height="16" x="6" y="2" rx="2"/>',
        'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
        'arrow-right' => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
        'arrow-up' => '<path d="m5 12 7-7 7 7"/><path d="M12 19V5"/>',
        'arrow-down' => '<path d="M12 5v14"/><path d="m19 12-7 7-7-7"/>',
        'check-circle' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/>',
        'qr' => '<rect width="5" height="5" x="3" y="3" rx="1"/><rect width="5" height="5" x="16" y="3" rx="1"/><rect width="5" height="5" x="3" y="16" rx="1"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/><path d="M21 21v.01"/><path d="M12 7v3a2 2 0 0 1-2 2H7"/><path d="M3 12h.01"/><path d="M12 3h.01"/><path d="M12 16v.01"/><path d="M16 12h1"/><path d="M21 12v.01"/><path d="M12 21v-1"/>',
        'circle-play' => '<path d="M9 9.003a1 1 0 0 1 1.517-.859l4.997 2.997a1 1 0 0 1 0 1.718l-4.997 2.997A1 1 0 0 1 9 14.996z"/><circle cx="12" cy="12" r="10"/>',
        'pause' => '<rect x="14" y="3" width="5" height="18" rx="1"/><rect x="5" y="3" width="5" height="18" rx="1"/>',
        'play' => '<path d="M5 5a2 2 0 0 1 3.008-1.728l11.997 6.998a2 2 0 0 1 .003 3.458l-12 7A2 2 0 0 1 5 19z"/>',
        'award' => '<path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526"/><circle cx="12" cy="8" r="6"/>',
        'globe' => '<circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/>',
        'globe-2' => '<path d="M21.54 15H17a2 2 0 0 0-2 2v4.54"/><path d="M7 3.34V5a3 3 0 0 0 3 3a2 2 0 0 1 2 2c0 1.1.9 2 2 2a2 2 0 0 0 2-2c0-1.1.9-2 2-2h3.17"/><path d="M11 21.95V18a2 2 0 0 0-2-2a2 2 0 0 1-2-2v-1a2 2 0 0 0-2-2H2.05"/><circle cx="12" cy="12" r="10"/>',
        'package-check' => '<path d="M12 22V12"/><path d="m16 17 2 2 4-4"/><path d="M21 11.127V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.729l7 4a2 2 0 0 0 2 .001l1.32-.753"/><path d="M3.29 7 12 12l8.71-5"/><path d="m7.5 4.27 8.997 5.148"/>',
    ];
    $path = $paths[$name] ?? $paths['arrow-right'];
    return '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="' . esc_attr($stroke_width) . '" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}
