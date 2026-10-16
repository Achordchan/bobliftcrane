<?php
if (!defined('ABSPATH')) {
    exit;
}

function boblift_seed_data(): array
{
    static $data = null;
    if ($data !== null) {
        return $data;
    }

    $file = get_template_directory() . '/inc/seed-data.json';
    $json = is_readable($file) ? file_get_contents($file) : '';
    $decoded = $json ? json_decode($json, true) : [];
    $data = is_array($decoded) ? $decoded : [];
    return $data;
}

function boblift_seed(string $key, mixed $default = []): mixed
{
    $data = boblift_seed_data();
    return $data[$key] ?? $default;
}

function boblift_default_options(): array
{
    return boblift_array_replace_recursive([
        'about_page' => boblift_about_page_defaults(),
        'public_welfare_page' => boblift_public_welfare_page_defaults(),
        'privacy_policy_page' => boblift_privacy_policy_page_defaults(),
        'terms_of_use_page' => boblift_terms_of_use_page_defaults(),
        'sitemap_page' => boblift_sitemap_page_defaults(),
        'product_archive' => boblift_product_archive_defaults(),
        'news_categories' => boblift_news_category_defaults(),
        'social_media_page' => boblift_social_media_page_defaults(),
        'site_ui' => function_exists('boblift_site_ui_defaults') ? boblift_site_ui_defaults() : [],
        'floating_contact_buttons' => boblift_floating_contact_button_defaults(),
        'form_security' => boblift_form_security_defaults(),
    ], boblift_seed('settings', []));
}

function boblift_image_option(string $path): array
{
    return [
        'id' => 0,
        'path' => $path,
    ];
}


function boblift_floating_contact_button_defaults(): array
{
    return [
        ['enabled' => '1', 'type' => 'quote', 'label' => 'Quote', 'href' => '', 'icon' => 'send', 'target' => ''],
        ['enabled' => '1', 'type' => 'email', 'label' => 'Email', 'href' => 'mailto:{email}', 'icon' => 'mail', 'target' => ''],
        ['enabled' => '1', 'type' => 'whatsapp', 'label' => 'WhatsApp', 'href' => 'https://wa.me/{whatsapp}', 'icon' => 'whatsapp', 'target' => '_blank'],
        ['enabled' => '1', 'type' => 'telegram', 'label' => 'Telegram', 'href' => '', 'icon' => 'telegram', 'target' => '_blank'],
        ['enabled' => '1', 'type' => 'phone', 'label' => 'Call', 'href' => '{phone_href}', 'icon' => 'phone-call', 'target' => ''],
        ['enabled' => '1', 'type' => 'wechat', 'label' => 'WeChat', 'href' => '', 'icon' => 'qr', 'target' => ''],
    ];
}


function boblift_product_archive_defaults(): array
{
    return [
        'label' => 'BOB-LIFT Product Range',
        'title' => 'Cranes & Lifting Equipment',
        'text' => 'Explore BOB-LIFT truck mounted cranes, marine cranes and lifting equipment for global projects.',
        'image' => boblift_image_option('images/product-banners/default.jpg'),
        'image_alt' => 'BOB-LIFT knuckle boom crane in the factory yard',
    ];
}

function boblift_social_media_page_defaults(): array
{
    // Platform links stay empty until the real account URL is entered in admin; empty cards render without a link.
    $platform = static fn(string $label, string $icon, string $tone, string $text): array => ['enabled' => '1', 'label' => $label, 'icon' => $icon, 'href' => '', 'handle' => '', 'text' => $text, 'tone' => $tone];
    $post = static fn(string $file, string $title): array => ['enabled' => '1', 'title' => $title, 'image' => boblift_image_option('images/social-media/posts/' . $file . '.webp')];
    $video = static fn(string $key, string $title): array => ['title' => $title, 'video' => ['id' => 0, 'path' => 'videos/social/' . $key . '.mp4'], 'poster' => boblift_image_option('videos/social/' . $key . '.webp')];
    return [
        'hero' => [
            'label' => 'BOB-LIFT Social Media',
            'image' => boblift_image_option('images/social-media/social-media-hero-workshop.webp'),
            'image_alt' => 'A phone on a tripod filming BOB-LIFT knuckle boom cranes in the workshop',
        ],
        'labels' => [
            'main_platforms_title' => 'Follow BOB-LIFT on the platforms you already use.',
            'main_platforms_text' => 'Production, container loading and cranes working on customer sites, posted by our own team.',
            'main_platforms_label' => 'Official accounts',
            'overseas_title' => 'Global platforms',
            'china_title' => 'China platforms',
            'more_title' => 'More platforms',
            'visit_label' => 'Visit',
            'qr_label' => 'Scan to follow',
            'posts_label' => 'Recent posts',
            'posts_title' => 'What we have been posting.',
            'posts_text' => 'A selection of recent posts from our accounts. Tap a post to view it full size.',
            'videos_title' => 'Short videos',
            'image_posts_title' => 'Image posts',
            'play_label' => 'Play video',
        ],
        'featured_platforms' => [
            $platform('LinkedIn', 'linkedin', 'bg-[#0A66C2]', 'Company news and industry updates.'),
            $platform('YouTube', 'youtube', 'bg-[#FF0000]', 'Product videos and factory tours.'),
            $platform('Facebook', 'facebook', 'bg-[#1877F2]', 'Deliveries, visits and customer stories.'),
            $platform('TikTok', 'tiktok', 'bg-[#111827]', 'Short clips from the workshop.'),
            $platform('Instagram', 'instagram', 'bg-[linear-gradient(135deg,#833AB4,#FD1D1D,#FCAF45)]', 'Photos from the factory and job sites.'),
            $platform('X', 'x', 'bg-[#111827]', 'Quick updates and announcements.'),
        ],
        'major_platforms' => [
            $platform('Douyin', 'douyin', 'bg-[#111827]', 'Short videos in Chinese.'),
            $platform('WeChat Channels', 'wechat', 'bg-[#07C160]', 'Videos inside WeChat.'),
            $platform('Xiaohongshu', 'xiaohongshu', 'bg-[#FF2442]', 'Photo notes from the factory.'),
            $platform('Weibo', 'weibo', 'bg-[#E6162D]', 'Company news in Chinese.'),
            $platform('Bilibili', 'bilibili', 'bg-[#00A1D6]', 'Longer product videos.'),
            $platform('Kuaishou', 'kuaishou', 'bg-[#FF6600]', 'Short videos in Chinese.'),
        ],
        'secondary_platforms' => [],
        'content_cards' => [
            $post('2026-8-3', 'Production and global delivery'),
            $post('2026-8-2', 'Customer feedback from Argentina'),
            $post('2026-8-1', '8 t truck mounted crane'),
            $post('2026-7-13', 'Ready for shipment'),
            $post('2026-7-9', 'Turkish customers visit the factory'),
            $post('2026-7-8', 'Operator view'),
            $post('2026-7-6', 'Built by our workshop team'),
            $post('2026-7-5', '10 t crane delivered'),
            $post('2026-7-2', 'SQ10ZA4 knuckle boom crane'),
            $post('2026-6-9', '10 years apart, same reliability'),
            $post('2026-6-7', 'Global delivery'),
            $post('2026-6-1', 'Customer factory visit'),
        ],
        'videos' => [
            $video('telescopic-en', 'Telescopic boom crane'),
            $video('marine-en', 'Knuckle boom marine crane'),
        ],
        'cta' => [
            'label' => 'Talk to us directly',
            'title' => 'Rather message us than follow?',
            'text' => 'Send the load, working radius and truck or vessel details. The sales team replies on the channel you use.',
            'button_label' => 'Send an Inquiry',
            'button_href' => '/contact.html',
        ],
    ];
}

function boblift_social_media_page_options(): array
{
    $value = boblift_option('social_media_page', []);
    $value = is_array($value) ? $value : [];
    $defaults = boblift_social_media_page_defaults();
    // Customised hero copy of the old page now lives in labels.main_platforms_title / _text; carry it over while the new
    // field still holds its built-in default.
    foreach (['title' => 'main_platforms_title', 'text' => 'main_platforms_text'] as $legacy_key => $label_key) {
        $legacy_value = trim((string) ($value['hero'][$legacy_key] ?? ''));
        $default_label = (string) ($defaults['labels'][$label_key] ?? '');
        // Previously saved defaults of the old page count as "not customised" too.
        $old_defaults = [$default_label, ['main_platforms_title' => 'Connect with BOB-LIFT where you work.', 'main_platforms_text' => 'Current key channels are highlighted first. More domestic and international platforms are listed below with lighter visual weight.'][$label_key] ?? ''];
        if ($legacy_value !== '' && in_array((string) ($value['labels'][$label_key] ?? $default_label), $old_defaults, true)) {
            $value['labels'][$label_key] = $legacy_value;
        }
    }
    return boblift_array_replace_recursive($defaults, $value);
}

function boblift_news_category_defaults(): array
{
    return [
        ['slug' => 'company-news'],
        ['slug' => 'product-news'],
        ['slug' => 'industry-insight'],
        ['slug' => 'marine-solution'],
    ];
}

function boblift_news_category_options(): array
{
    $value = boblift_option('news_categories', boblift_news_category_defaults());
    if (!is_array($value)) {
        return boblift_news_category_defaults();
    }

    $items = [];
    foreach ($value as $item) {
        if (!is_array($item)) {
            continue;
        }
        $slug = sanitize_title((string) ($item['slug'] ?? ''));
        if ($slug !== '') {
            $items[] = ['slug' => $slug];
        }
    }

    return $items !== [] ? $items : boblift_news_category_defaults();
}

function boblift_news_category_source_slugs(): array
{
    return array_values(array_unique(array_map(static fn(array $item): string => (string) $item['slug'], boblift_news_category_options())));
}

function boblift_form_security_defaults(): array
{
    return [
        'turnstile_enabled' => '1',
        'turnstile_local_mode' => '1',
        'turnstile_site_key' => '1x00000000000000000000AA',
        'turnstile_secret_key' => '1x0000000000000000000000000000000AA',
    ];
}

function boblift_form_security_options(): array
{
    $value = boblift_option('form_security', []);
    return boblift_array_replace_recursive(boblift_form_security_defaults(), is_array($value) ? $value : []);
}

function boblift_product_archive_options(): array
{
    $value = boblift_option('product_archive', []);
    return boblift_array_replace_recursive(boblift_product_archive_defaults(), is_array($value) ? $value : []);
}

function boblift_public_welfare_page_defaults(): array
{
    return [
        'hero' => [
            'image' => boblift_image_option('images/public-welfare/public-welfare-hero-banner.webp'),
            'image_alt' => 'BOB-LIFT engineers reviewing work in the factory',
        ],
        'statement' => [
            'label' => 'Daily Responsibility',
            'title' => 'Responsibility is not a slogan. It is our daily operation.',
            'highlight' => 'daily operation',
            'text' => 'From design and build, to how we work and communicate, BOB-LIFT stays focused on doing the right thing every day.',
        ],
        'focus_items' => [
            [
                'icon' => 'building-2',
                'title' => 'Responsible manufacturing',
                'text' => 'We focus on quality, durable products and disciplined production. Each step is managed to support safer lifting equipment and long-term customer trust.',
                'image' => boblift_image_option('images/public-welfare/public-welfare-responsible-manufacturing.webp'),
                'alt' => 'BOB-LIFT crane manufacturing workshop',
            ],
            [
                'icon' => 'shield-check',
                'title' => 'Workplace safety',
                'text' => 'Factory order, safety awareness and practical operating standards help protect people while keeping production work clear and reliable.',
                'image' => boblift_image_option('images/public-welfare/public-welfare-workplace-safety.webp'),
                'alt' => 'BOB-LIFT workshop safety and production area',
            ],
            [
                'icon' => 'heart',
                'title' => 'Community care',
                'text' => 'BOB-LIFT values steady relationships with employees, partners and local communities, keeping responsibility in everyday communication and cooperation.',
                'image' => boblift_image_option('images/public-welfare/public-welfare-community-care.webp'),
                'alt' => 'BOB-LIFT team group photo',
            ],
            [
                'icon' => 'globe',
                'title' => 'Long-term cooperation',
                'text' => 'We keep communication practical and traceable, helping partners build stable cooperation from factory review to after-sales support.',
                'image' => boblift_image_option('images/public-welfare/public-welfare-crane-safety.webp'),
                'alt' => 'BOB-LIFT clean workshop and lifting equipment',
            ],
        ],
        'cta' => [
            'title' => 'Building a safer future, together.',
            'text' => 'We welcome partners who share practical values: safer work, responsible manufacturing and long-term cooperation.',
            'button_label' => 'Contact us',
            'button_href' => '/contact.html',
        ],
    ];
}

function boblift_public_welfare_page_options(): array
{
    $value = boblift_option('public_welfare_page', []);
    return boblift_array_replace_recursive(boblift_public_welfare_page_defaults(), is_array($value) ? $value : []);
}

function boblift_privacy_policy_page_defaults(): array
{
    return [
        'label' => 'BOB-LIFT Policy',
        'title' => 'Privacy Policy',
        'description' => 'This page explains how BOB-LIFT handles contact, inquiry and website usage information submitted through this website.',
        'updated_label' => 'Last updated:',
        'updated' => '2026-09-30',
        'side_title' => 'Scope',
        'side_text' => 'This policy applies to BOB-LIFT website forms, catalog requests, quote requests and direct business communication initiated through the site.',
        'sections' => [
            ['title' => 'Information we collect', 'text' => 'We may collect your name, company, email address, phone number, country, product interest, project requirements and messages submitted through website forms. Basic technical information such as browser type, pages visited and security verification status may also be processed for site reliability and anti-spam protection.'],
            ['title' => 'How we use information', 'text' => 'We use submitted information to respond to inquiries, prepare quotations, send requested catalog materials, provide product selection support, arrange after-sales communication and improve website service quality.'],
            ['title' => 'Sharing and disclosure', 'text' => 'BOB-LIFT does not sell personal information. We only share necessary information with internal sales, engineering, logistics or service teams when required to respond to your request. Information may also be processed by trusted website, email, analytics or security service providers.'],
            ['title' => 'Data retention', 'text' => 'Business inquiry records are kept only as long as needed for communication, quotation, project follow-up, legal compliance and legitimate business record keeping.'],
            ['title' => 'Cookies and browser storage', 'text' => 'This website does not use advertising or cross-site tracking cookies. The spam check on our forms (Cloudflare Turnstile) may set cookies it needs to tell people from bots. Your quote list and display preferences, such as grid or list view on the news page, are kept in your own browser\'s local storage and are only sent to us when you submit a form. You can clear them at any time in your browser settings.'],
            ['title' => 'Third-party services', 'text' => 'Some features load content from other companies: the map on the contact page comes from Google Maps, videos are played through YouTube in its privacy-enhanced mode, and form protection is provided by Cloudflare Turnstile. When you use these features, those companies may receive your IP address and browser information under their own privacy policies. To pre-fill the country field in our forms, the website estimates your country from your IP address; the IP address itself is not stored with your inquiry.'],
            ['title' => 'International transfers', 'text' => 'BOB-LIFT is based in Xuzhou, China. Information you submit is handled by our team in China and may be processed by the service providers named above in other countries. We only transfer what is needed to answer your request.'],
            ['title' => 'Your choices', 'text' => 'You may contact BOB-LIFT to request access, correction or deletion of your inquiry information, subject to applicable business, legal and technical limitations.' . "\n\n" . 'If you are in the European Union, the United Kingdom or another region with data protection laws, you may also object to or ask us to limit how we use your information. We aim to reply to every request within 30 days.'],
            ['title' => 'Changes to this policy', 'text' => 'We may update this policy when our website or services change. The date at the top of this page shows when it was last updated.'],
            ['title' => 'Contact', 'text' => 'For privacy questions, contact us at info@bobliftcrane.com or through the contact page on this website.'],
        ],
    ];
}

function boblift_privacy_policy_page_options(): array
{
    $value = boblift_option('privacy_policy_page', []);
    return boblift_array_replace_recursive(boblift_privacy_policy_page_defaults(), is_array($value) ? $value : []);
}

function boblift_terms_of_use_page_defaults(): array
{
    return [
        'label' => 'BOB-LIFT Website',
        'title' => 'Terms of Use',
        'description' => 'These terms explain the basic conditions for using BOB-LIFT website information, product materials and inquiry services.',
        'updated_label' => 'Last updated:',
        'updated' => '2026-09-30',
        'sections' => [
            ['title' => 'Website information', 'text' => 'Product images, technical descriptions, capacity references and application notes are provided for general business reference. Final specifications, configurations and quotations must be confirmed in writing by BOB-LIFT.'],
            ['title' => 'Product selection', 'text' => 'Crane model selection depends on load, working radius, chassis, installation conditions, local regulations and project requirements. Website information should not replace engineering confirmation.'],
            ['title' => 'Intellectual property', 'text' => 'BOB-LIFT names, product materials, photos, text, layout and other website content may not be copied, modified or reused for commercial purposes without written permission.'],
            ['title' => 'User submissions', 'text' => 'When submitting forms, you agree to provide accurate business contact and project information. Do not submit unlawful, misleading, abusive or unrelated content.'],
            ['title' => 'External links and services', 'text' => 'The website may link to external maps, social platforms, video platforms or service providers. Their content and policies are controlled by those third parties.'],
            ['title' => 'Prices, quotations and orders', 'text' => 'Prices, promotions, lead times and stock information shown on the website are for reference only and may change without notice. An order is binding only when BOB-LIFT confirms it in a written quotation, proforma invoice or contract, and those documents take precedence over the website.'],
            ['title' => 'Limitation of liability', 'text' => 'BOB-LIFT works to keep website information accurate, but does not guarantee uninterrupted availability or that all content is free from errors. Business decisions should rely on confirmed quotations and technical documents.'],
            ['title' => 'Governing law', 'text' => 'These terms are governed by the laws of the People\'s Republic of China. Any dispute about the use of this website should first be settled through friendly discussion.'],
            ['title' => 'Changes to these terms', 'text' => 'We may update these terms from time to time. The date at the top of this page shows the current version, and continued use of the website means you accept it.'],
        ],
    ];
}

function boblift_terms_of_use_page_options(): array
{
    $value = boblift_option('terms_of_use_page', []);
    return boblift_array_replace_recursive(boblift_terms_of_use_page_defaults(), is_array($value) ? $value : []);
}

function boblift_sitemap_page_defaults(): array
{
    return [
        'label' => 'HTML Sitemap',
        'title' => 'Find BOB-LIFT pages quickly.',
        'description' => 'Browse main pages, product ranges, news and customer cases from one structured index.',
        'links_label' => 'Links',
        'core_pages_title' => 'Core Pages',
        'product_range_label' => 'Product Range',
        'product_range_title' => 'Products and categories',
        'view_products_label' => 'View Products',
        'product_categories_title' => 'Product Categories',
        'products_title' => 'Products',
        'news_categories_title' => 'News Categories',
        'news_title' => 'News',
        'cases_title' => 'Cases',
    ];
}

function boblift_sitemap_page_options(): array
{
    $value = boblift_option('sitemap_page', []);
    return boblift_array_replace_recursive(boblift_sitemap_page_defaults(), is_array($value) ? $value : []);
}

function boblift_about_page_defaults(): array
{
    return [
        'secondary_nav' => [
            ['label' => 'About Us', 'href' => '/about.html', 'slug' => 'about'],
            ['label' => 'Factory Tour', 'href' => '/factory-tour.html', 'slug' => 'factory-tour'],
            ['label' => 'Quality & Testing', 'href' => '/quality-testing.html', 'slug' => 'quality-testing'],
            ['label' => 'Global Customer Visits', 'href' => '/global-customer-visits.html', 'slug' => 'global-customer-visits'],
        ],
        'logo' => [
            'silhouette_image' => boblift_image_option('images/about/about-logo-1.png'),
            'silhouette_alt' => 'BOB-LIFT crane silhouette background',
            'aerial_start_image' => boblift_image_option('images/about/about-aerial-mid-start.webp'),
            'aerial_end_image' => boblift_image_option('images/about/about-aerial-mid-end.webp'),
            'aerial_video' => ['id' => 0, 'path' => 'videos/about/about-aerial-mid.mp4'],
            'aerial_start_alt' => 'BOB-LIFT factory aerial view opening frame',
            'aerial_end_alt' => 'BOB-LIFT factory aerial view ending frame',
        ],
        'strength' => [
            'label' => 'Company Profile',
            'title' => 'BOB-LIFT is a crane manufacturer based in Xuzhou, China.',
            'text' => 'Xuzhou BOB-LIFT Construction Machinery Co., Ltd. designs, manufactures and exports truck-mounted cranes and related lifting equipment from a 37,000㎡ manufacturing base in Xuzhou, Jiangsu, China. Our core products include truck-mounted cranes, marine cranes, aerial work platforms, pickup cranes and spider cranes for overseas B2B projects.',
        ],
        'certifications' => [
            'label' => 'Certificates & Patents',
            'title' => 'Certificates and patents',
            'text' => 'CE, EAC and ISO certificates and the patents registered to BOB-LIFT. Select a document to view it full size.',
            'items' => [
                ['image' => boblift_image_option('certifications/cert-ce-cranes.webp'), 'title' => 'CE Certificate · Cranes', 'text' => 'Machinery Directive 2006/42/EC · valid to 03/2031'],
                ['image' => boblift_image_option('certifications/cert-eac.webp'), 'title' => 'EAC Certificate of Conformity', 'text' => 'EAEU TR CU 010/2011 · valid to 10/2030'],
                ['image' => boblift_image_option('certifications/cert-iso-9001.webp'), 'title' => 'ISO 9001 Quality Management', 'text' => 'Truck-mounted crane production · valid to 04/2029'],
                ['image' => boblift_image_option('certifications/cert-iso-9001-marine.webp'), 'title' => 'ISO 9001 Quality Management · Marine', 'text' => 'Marine crane production · valid to 04/2029'],
                ['image' => boblift_image_option('certifications/cert-iso-14001.webp'), 'title' => 'ISO 14001 Environmental Management', 'text' => 'Truck-mounted crane production · valid to 07/2029'],
                ['image' => boblift_image_option('certifications/cert-trademark-china-boblift.webp'), 'title' => 'BOB-LIFT Trademark · China', 'text' => 'Registration No. 26579311 · valid to 04/2029'],
                ['image' => boblift_image_option('certifications/cert-trademark-china-babu.webp'), 'title' => '巴布 (BABU) Trademark · China', 'text' => 'Registration No. 27469741, cranes · valid to 12/2028'],
                ['image' => boblift_image_option('certifications/cert-trademark-russia.webp'), 'title' => 'BOB-LIFT Trademark · Russia', 'text' => 'Registration No. 1211725 · valid to 11/2034'],
                ['image' => boblift_image_option('certifications/patent-crane-support.webp'), 'title' => 'Support mechanism for truck-mounted cranes', 'text' => 'Utility model patent · ZL 2019 2 1991835.2'],
                ['image' => boblift_image_option('certifications/patent-pickup-crane.webp'), 'title' => 'Crane for pickup trucks', 'text' => 'Utility model patent · ZL 2020 2 0116421.1'],
                ['image' => boblift_image_option('certifications/patent-crane-aerial-platform.webp'), 'title' => 'Aerial work platform for truck-mounted cranes', 'text' => 'Utility model patent · ZL 2020 2 0005335.3'],
                ['image' => boblift_image_option('certifications/patent-brick-clamp-buffer.webp'), 'title' => 'Buffer device for truck-mounted brick clamps', 'text' => 'Utility model patent · ZL 2020 2 0118784.9'],
                ['image' => boblift_image_option('certifications/patent-forklift-timber-grab.webp'), 'title' => 'Timber grab for forklifts', 'text' => 'Utility model patent · ZL 2020 2 0118811.2'],
            ],
        ],
        'global_footprint' => [
            'stats' => [
                ['number' => '120', 'suffix' => '+', 'label' => 'Countries Exported'],
                ['number' => '20', 'suffix' => '+', 'label' => 'Years Experience'],
            ],
        ],
        'timeline' => [
            'label' => 'Development Timeline',
            'title' => 'Built step by step for global lifting projects.',
            'items' => [
                ['year' => '2003', 'title' => 'BOB-LIFT’s First Folding Boom Crane Takes Shape', 'text' => 'In the year BOB-LIFT was founded in Xuzhou, Jiangsu, we completed the development and field testing of our first folding boom crane.', 'image' => boblift_image_option('images/truck-mounted-crane.png')],
                ['year' => '2014', 'title' => 'BOB-LIFT Establishes Its Foreign Trade Department', 'text' => 'BOB-LIFT formally established its Foreign Trade Department, the start of our journey toward overseas markets and global customers.', 'image' => boblift_image_option('images/marine-scene.jpg')],
                ['year' => '2023', 'title' => 'BOB-LIFT Makes Its Canton Fair Debut', 'text' => 'BOB-LIFT took part in the Canton Fair for the first time and met customers and partners from around the world face to face.', 'image' => boblift_image_option('images/factory-workshop.jpg')],
                ['year' => '2026', 'title' => 'Advancing Manufacturing, Serving Global Markets', 'text' => 'BOB-LIFT keeps improving product development, production capability and quality management to support customers worldwide.', 'image' => boblift_image_option('images/aerial-platform.jpg')],
            ],
        ],
        'customer_visits' => [
            'label' => 'Global Customer Visits',
            'title' => 'Welcoming partners to see how BOB-LIFT builds.',
            'text' => 'Factory visits help customers review production details, discuss lifting requirements and align the right solution before cooperation moves forward.',
            'hero_image' => boblift_image_option('images/page-heroes/factory-campus.webp'),
            'hero_image_alt' => 'The BOB-LIFT factory campus from the air',
            'cta_label' => 'Plan a Visit',
            'cta_href' => '/contact.html',
            'home_title' => 'Customer Visit Gallery',
            'home_cta_label' => 'View More Photos',
            'visit_count_suffix' => 'Visit Photos',
            'country_count_suffix' => 'countries',
            'pagination_label' => 'Customer visit pages',
            'empty_text' => 'No customer visit photos have been added yet.',
            'countries_label' => 'Visitors from',
            'countries_title' => 'Customers from {count} countries have walked our workshop.',
            'steps_label' => 'What a visit covers',
            'steps_title' => 'A working day in the factory, not a showroom tour.',
            'steps' => [
                ['image' => boblift_image_option('images/customer-visits/step-production-line.webp'), 'title' => 'Walk the production line', 'text' => 'Follow a crane from welding and assembly to the test stand, and ask questions at every station.'],
                ['image' => boblift_image_option('images/customer-visits/step-your-model.webp'), 'title' => 'Look at the model you need', 'text' => 'Stand next to the crane you are considering and check the boom, hydraulics and finish for yourself.'],
                ['image' => boblift_image_option('images/customer-visits/step-parts-packing.webp'), 'title' => 'Check parts and packing', 'text' => 'Open the spare-part boxes and see how cranes are wrapped and secured for the sea journey.'],
                ['image' => boblift_image_option('images/customer-visits/step-customer-wall.webp'), 'title' => 'Join our customer wall', 'text' => 'Finish in the office in front of the customer wall, where visitors from around the world have left their photos.'],
            ],
            'wall_label' => 'Visit gallery',
            'wall_title' => 'Photos from recent visits',
            'wall_text' => 'Select a country to see the photos from that visit.',
            'more_visits_label' => 'More factory visits',
            'photo_count_suffix' => 'photos',
            'plan_label' => 'Plan your visit',
            'plan_title' => 'Come and see the crane before you order it.',
            'plan_text' => 'Tell us your travel dates and the models you are considering, and we will plan the day around them.',
            'plan_points' => [
                ['title' => 'Factory', 'text' => 'Xuzhou, Jiangsu, China'],
                ['title' => 'Nearest airport', 'text' => 'Xuzhou Guanyin International Airport'],
                ['title' => 'High-speed rail', 'text' => 'Xuzhou East Railway Station'],
            ],
            'plan_secondary_label' => 'Contact us',
            'items' => [
                ['image' => boblift_image_option('images/customer-visits/algeria-1.webp'), 'country' => 'Algeria', 'title' => 'Customers from Algeria visiting the BOB-LIFT factory'],
                ['image' => boblift_image_option('images/customer-visits/algeria-2.webp'), 'country' => 'Algeria', 'title' => 'Customers from Algeria visiting the BOB-LIFT factory'],
                ['image' => boblift_image_option('images/customer-visits/algeria-3.webp'), 'country' => 'Algeria', 'title' => 'Customers from Algeria visiting the BOB-LIFT factory'],
                ['image' => boblift_image_option('images/customer-visits/azerbaijan-1.webp'), 'country' => 'Azerbaijan', 'title' => 'Customers from Azerbaijan visiting the BOB-LIFT factory'],
                ['image' => boblift_image_option('images/customer-visits/germany-1.webp'), 'country' => 'Germany', 'title' => 'Customers from Germany visiting the BOB-LIFT factory'],
                ['image' => boblift_image_option('images/customer-visits/india-1.webp'), 'country' => 'India', 'title' => 'Customers from India visiting the BOB-LIFT factory'],
                ['image' => boblift_image_option('images/customer-visits/india-2.webp'), 'country' => 'India', 'title' => 'Customers from India visiting the BOB-LIFT factory'],
                ['image' => boblift_image_option('images/customer-visits/india-3.webp'), 'country' => 'India', 'title' => 'Customers from India visiting the BOB-LIFT factory'],
                ['image' => boblift_image_option('images/customer-visits/india-4.webp'), 'country' => 'India', 'title' => 'Customers from India visiting the BOB-LIFT factory'],
                ['image' => boblift_image_option('images/customer-visits/ireland-1.webp'), 'country' => 'Ireland', 'title' => 'Customers from Ireland visiting the BOB-LIFT factory'],
                ['image' => boblift_image_option('images/customer-visits/kazakhstan-1.webp'), 'country' => 'Kazakhstan', 'title' => 'Customers from Kazakhstan visiting the BOB-LIFT factory'],
                ['image' => boblift_image_option('images/customer-visits/kenya-1.webp'), 'country' => 'Kenya', 'title' => 'Customers from Kenya visiting the BOB-LIFT factory'],
                ['image' => boblift_image_option('images/customer-visits/kenya-2.webp'), 'country' => 'Kenya', 'title' => 'Customers from Kenya visiting the BOB-LIFT factory'],
                ['image' => boblift_image_option('images/customer-visits/kenya-3.webp'), 'country' => 'Kenya', 'title' => 'Customers from Kenya visiting the BOB-LIFT factory'],
                ['image' => boblift_image_option('images/customer-visits/new-zealand-1.webp'), 'country' => 'New Zealand', 'title' => 'Customers from New Zealand visiting the BOB-LIFT factory'],
                ['image' => boblift_image_option('images/customer-visits/nigeria-1.webp'), 'country' => 'Nigeria', 'title' => 'Customers from Nigeria visiting the BOB-LIFT factory'],
                ['image' => boblift_image_option('images/customer-visits/peru-1.webp'), 'country' => 'Peru', 'title' => 'Customers from Peru visiting the BOB-LIFT factory'],
                ['image' => boblift_image_option('images/customer-visits/peru-2.webp'), 'country' => 'Peru', 'title' => 'Customers from Peru visiting the BOB-LIFT factory'],
                ['image' => boblift_image_option('images/customer-visits/peru-3.webp'), 'country' => 'Peru', 'title' => 'Customers from Peru visiting the BOB-LIFT factory'],
                ['image' => boblift_image_option('images/customer-visits/russia-1.webp'), 'country' => 'Russia', 'title' => 'Customers from Russia visiting the BOB-LIFT factory'],
                ['image' => boblift_image_option('images/customer-visits/russia-2.webp'), 'country' => 'Russia', 'title' => 'Customers from Russia visiting the BOB-LIFT factory'],
                ['image' => boblift_image_option('images/customer-visits/south-africa-1.webp'), 'country' => 'South Africa', 'title' => 'Customers from South Africa visiting the BOB-LIFT factory'],
                ['image' => boblift_image_option('images/customer-visits/south-africa-2.webp'), 'country' => 'South Africa', 'title' => 'Customers from South Africa visiting the BOB-LIFT factory'],
                ['image' => boblift_image_option('images/customer-visits/turkey-1.webp'), 'country' => 'Turkey', 'title' => 'Customers from Turkey visiting the BOB-LIFT factory'],
                ['image' => boblift_image_option('images/customer-visits/turkey-2.webp'), 'country' => 'Turkey', 'title' => 'Customers from Turkey visiting the BOB-LIFT factory'],
                ['image' => boblift_image_option('images/customer-visits/usa-1.webp'), 'country' => 'United States', 'title' => 'Customers from United States visiting the BOB-LIFT factory'],
                ['image' => boblift_image_option('images/customer-visits/more-1.webp'), 'country' => '', 'title' => 'Customers touring the BOB-LIFT workshop'],
                ['image' => boblift_image_option('images/customer-visits/more-2.webp'), 'country' => '', 'title' => 'Customers touring the BOB-LIFT workshop'],
                ['image' => boblift_image_option('images/customer-visits/more-3.webp'), 'country' => '', 'title' => 'Customers touring the BOB-LIFT workshop'],
                ['image' => boblift_image_option('images/customer-visits/more-4.webp'), 'country' => '', 'title' => 'Customers touring the BOB-LIFT workshop'],
                ['image' => boblift_image_option('images/customer-visits/more-5.webp'), 'country' => '', 'title' => 'Customers touring the BOB-LIFT workshop'],
                ['image' => boblift_image_option('images/customer-visits/more-6.webp'), 'country' => '', 'title' => 'Customers touring the BOB-LIFT workshop'],
                ['image' => boblift_image_option('images/customer-visits/more-7.webp'), 'country' => '', 'title' => 'Customers touring the BOB-LIFT workshop'],
                ['image' => boblift_image_option('images/customer-visits/more-8.webp'), 'country' => '', 'title' => 'Customers touring the BOB-LIFT workshop'],
                ['image' => boblift_image_option('images/customer-visits/more-9.webp'), 'country' => '', 'title' => 'Customers touring the BOB-LIFT workshop'],
                ['image' => boblift_image_option('images/customer-visits/more-10.webp'), 'country' => '', 'title' => 'Customers touring the BOB-LIFT workshop'],
                ['image' => boblift_image_option('images/customer-visits/more-11.webp'), 'country' => '', 'title' => 'Customers touring the BOB-LIFT workshop'],
                ['image' => boblift_image_option('images/customer-visits/more-12.webp'), 'country' => '', 'title' => 'Customers touring the BOB-LIFT workshop'],
            ],
        ],
        'manufacturing_gallery' => [
            'label' => 'Modern Manufacturing Base',
            'title' => 'Intelligent Production.',
            'text' => 'Experience our state-of-the-art 37,000 sqm facility where precision engineering meets rigorous quality control.',
            'items' => [
                ['image' => boblift_image_option('images/about/mfg-1-boom-sections.webp'), 'title' => 'Boom sections', 'text' => 'Telescopic boom sections are finished and fitted before assembly.'],
                ['image' => boblift_image_option('images/about/mfg-2-assembly.webp'), 'title' => 'Assembly', 'text' => 'Columns, winches and control valves are assembled in our Xuzhou workshop.'],
                ['image' => boblift_image_option('images/about/mfg-3-hydraulics.webp'), 'title' => 'Hydraulics', 'text' => 'Hoses, valves and cylinders are fitted and checked on every crane.'],
                ['image' => boblift_image_option('images/about/mfg-4-final-check.webp'), 'title' => 'Final check', 'text' => 'Finished cranes are inspected and lined up for dispatch.'],
                ['image' => boblift_image_option('images/about/mfg-5-packing.webp'), 'title' => 'Packing', 'text' => 'Wrapped in film and strapped to protect paint and hydraulics in transit.'],
                ['image' => boblift_image_option('images/about/mfg-6-container-loading.webp'), 'title' => 'Container loading', 'text' => 'Loaded into containers at the factory and shipped to your port.'],
            ],
        ],
        'executives' => [
            'label' => 'Executive Leadership',
            'title' => 'Leadership Team',
            'team_label' => 'Whole Team',
            'team_title' => 'BOB-LIFT Team',
            'team_image' => boblift_image_option('images/about/quanyuan-compressed.jpg'),
            'team_image_alt' => 'BOB-LIFT full team group photo',
            'members' => [
                ['image' => boblift_image_option('images/about/executives/executive-01-square.png'), 'name' => 'Daniel Chen', 'role' => 'Chief Executive Officer'],
                ['image' => boblift_image_option('images/about/executives/executive-02-square.png'), 'name' => 'Sophia Lin', 'role' => 'Chief Operating Officer'],
                ['image' => boblift_image_option('images/about/executives/executive-03-square.png'), 'name' => 'Robert Miller', 'role' => 'Chief Engineering Officer'],
                ['image' => boblift_image_option('images/about/executives/executive-04-square.png'), 'name' => 'Michael Zhang', 'role' => 'Manufacturing Director'],
                ['image' => boblift_image_option('images/about/executives/executive-05-square.png'), 'name' => 'Ethan Wang', 'role' => 'Global Sales Director'],
                ['image' => boblift_image_option('images/about/executives/executive-06-square.png'), 'name' => 'Grace Wilson', 'role' => 'Service Director'],
            ],
        ],
    ];
}

/**
 * The introduction an editor saved in the old About layout (headline, paragraph, proof chips) that the redesigned hero no
 * longer prints. Built-in default copy (both historical sets) is not returned. Returns only the customised parts.
 */
function boblift_about_legacy_intro(array $logo): array
{
    $default_headlines = ['Engineering Strength Behind Every Lift.'];
    $default_paragraphs = ['BOB-LIFT integrates crane manufacturing, engineering expertise, quality control, and global service capability to deliver reliable lifting solutions for demanding projects worldwide.'];
    $default_chip_labels = [['Advanced Crane Manufacturing', 'Global Export Capability', 'Strict Quality Control'], ['Safety-focused crane manufacturing', 'Technology-driven product development', 'Service support for global buyers']];
    $headline = trim((string) ($logo['headline'] ?? ''));
    $paragraph = trim((string) ($logo['paragraph'] ?? ''));
    $chips = array_values(array_filter((array) ($logo['proof_chips'] ?? []), static fn($chip): bool => is_array($chip) && (trim((string) ($chip['number'] ?? '')) !== '' || trim((string) ($chip['label'] ?? '')) !== '')));
    $chip_labels = array_map(static fn(array $chip): string => trim((string) ($chip['label'] ?? '')), $chips);
    return [
        'headline' => in_array($headline, $default_headlines, true) ? '' : $headline,
        'paragraph' => in_array($paragraph, $default_paragraphs, true) ? '' : $paragraph,
        'chips' => in_array($chip_labels, $default_chip_labels, true) ? [] : $chips,
    ];
}

/**
 * Cards and call to action an editor wrote in the old About "Company Strength" section, which the redesigned page replaced
 * with the export statistics. Built-in copy (the historical default sets, English and the Japanese and Chinese seeds) is
 * not returned; only content that differs is, so it can stay visible and editable.
 */
function boblift_about_legacy_strength(array $strength): array
{
    // Complete [title, text] pairs of the historical default card sets (original, English seed, Japanese and Chinese seeds).
    $default_card_sets = [[['R&D and Engineering', 'Engineering teams support practical crane selection, structural design and project-specific configurations.'], ['Precision Manufacturing', 'Controlled welding, assembly and inspection workflows keep every lifting system stable and repeatable.'], ['Global Delivery', 'Export-ready documentation, packing and logistics support help projects move across international markets.'], ['After-Sales Support', 'Service guidance, spare parts coordination and technical response help customers keep equipment working.']], [['Who we are', 'A high-tech construction machinery enterprise headquartered in Xuzhou, China, focused on practical lifting equipment for global buyers.'], ['What we build', 'Truck-mounted cranes remain the core product line, supported by marine cranes, aerial work platforms, pickup cranes and spider cranes.'], ['How we develop', 'Our team keeps product development focused on safety, technology and reliable engineering while absorbing international crane design experience.'], ['How we support buyers', 'We support customers through selection, manufacturing, testing, spare parts, remote technical support, installation guidance and operator training.']], [['私たちについて', '中国・徐州に本社を置くハイテク建設機械企業で、グローバルバイヤー向けの実用的な吊り上げ機器に注力しています。'], ['私たちが製造するもの', 'トラック搭載型クレーンを中核製品ラインとし、これをマリンクレーン、高所作業車、ピックアップクレーン、スパイダークレーンが支えています。'], ['私たちの開発方法', '当社チームは、国際的なクレーン設計の経験を取り入れながら、安全性、技術、信頼できるエンジニアリングに重点を置いて製品開発を進めています。'], ['バイヤーへのサポート方法', '当社は、選定、製造、試験、スペアパーツ、リモート技術サポート、設置指導、オペレーター研修を通じてお客様を支援します。']], [['我们是谁', '一家总部位于中国徐州的高新技术工程机械企业，专注于为全球买家提供实用型起重设备。'], ['我们制造什么', '随车起重机仍是核心产品线，同时辅以船用起重机、高空作业平台、皮卡起重机和蜘蛛起重机。'], ['我们如何发展', '我们的团队始终专注于安全、技术和可靠工程，推进产品开发，同时吸收国际起重机设计经验。'], ['我们如何支持买家', '我们通过选型、制造、测试、备件、远程技术支持、安装指导和操作员培训来支持客户。']]];
    $default_ctas = [['Talk to Our Team', '/contact'], ['Talk to Our Team', '/contact.html'], ['当社チームにご相談ください', '/contact'], ['与我们的团队联系', '/contact']];
    $cards = array_values(array_filter((array) ($strength['cards'] ?? []), static fn($card): bool => is_array($card) && (trim((string) ($card['title'] ?? '')) !== '' || trim((string) ($card['text'] ?? '')) !== '')));
    $pairs = array_map(static fn(array $card): array => [trim((string) ($card['title'] ?? '')), trim((string) ($card['text'] ?? ''))], $cards);
    $cta = [trim((string) ($strength['cta_label'] ?? '')), trim((string) ($strength['cta_href'] ?? ''))];
    // An uploaded image, or a path other than the built-in prototype picture, is the editor's own.
    $image = $strength['image'] ?? null;
    $image_path = is_array($image) ? trim((string) ($image['path'] ?? '')) : trim((string) $image);
    $image_custom = (is_array($image) && (int) ($image['id'] ?? 0) > 0) || ($image_path !== '' && $image_path !== 'images/about/about-logo-prototype-crane-ai.png');
    return [
        'image' => $image_custom ? $image : null,
        'cards' => in_array($pairs, $default_card_sets, true) ? [] : $cards,
        'cta' => ($cta[0] === '' || in_array($cta, $default_ctas, true)) ? null : ['label' => $cta[0], 'href' => $cta[1] !== '' ? $cta[1] : '/contact.html'],
    ];
}

function boblift_about_page_options(): array
{
    $value = boblift_option('about_page', []);
    $value = is_array($value) ? $value : [];
    // Sites saved before the team photo was renamed keep it in executives.group_image; use it until team_image is saved.
    $legacy_team_image = $value['executives']['group_image'] ?? null;
    // boblift_option() already merges the defaults, so "not saved" means empty or still equal to the built-in default.
    $default_team_image = boblift_about_page_defaults()['executives']['team_image'] ?? null;
    $saved_team_image = $value['executives']['team_image'] ?? null;
    if (!empty($legacy_team_image) && (empty($saved_team_image) || wp_json_encode($saved_team_image) === wp_json_encode($default_team_image))) {
        $value['executives']['team_image'] = $legacy_team_image;
    }
    $options = boblift_array_replace_recursive(boblift_about_page_defaults(), $value);
    // Saved menus in every language still list retired pages and pages that now live under Services, News or the
    // top-level Contact link; drop them so the About menu keeps to the company pages.
    if (is_array($options['secondary_nav'] ?? null)) {
        $options['secondary_nav'] = array_values(array_filter($options['secondary_nav'], static function ($item): bool {
            if (!is_array($item)) {
                return false;
            }
            $slug = (string) ($item['slug'] ?? '');
            $path = trim((string) wp_parse_url((string) ($item['href'] ?? ''), PHP_URL_PATH), '/');
            $skip = array_merge(boblift_retired_page_slugs(), boblift_about_nav_moved_slugs());
            return !in_array($slug, $skip, true) && !in_array(preg_replace('/\.html$/', '', $path), $skip, true);
        }));
        // Saved menus predate newer About pages; add any default child that is missing, after the one before it. Once an
        // editor has saved the menu (secondary_nav_version), the list is theirs and a removed page stays removed.
        $reviewed_menu = (int) ($value['secondary_nav_version'] ?? 0) >= 1;
        $saved_slugs = array_map(static fn(array $item): string => (string) ($item['slug'] ?? ''), $options['secondary_nav']);
        $previous = '';
        foreach ($reviewed_menu ? [] : boblift_about_page_defaults()['secondary_nav'] as $default_child) {
            $slug = (string) $default_child['slug'];
            if (!in_array($slug, $saved_slugs, true)) {
                $at = $previous !== '' ? array_search($previous, $saved_slugs, true) : false;
                $position = $at === false ? count($options['secondary_nav']) : $at + 1;
                array_splice($options['secondary_nav'], $position, 0, [$default_child]);
                array_splice($saved_slugs, $position, 0, [$slug]);
            }
            $previous = $slug;
        }
    }
    return $options;
}

// Pages taken off this site; their URLs redirect to About. Public Welfare moves to the group site boblift.com.
function boblift_retired_page_slugs(): array
{
    return ['public-welfare'];
}

/** Pages that left the About menu: Services (mounting, service), News (videos, social media) and top-level Contact. */
function boblift_about_nav_moved_slugs(): array
{
    return ['mounting-shipping', 'service', 'videos', 'social-media', 'contact'];
}

function boblift_options(): array
{
    return boblift_migrate_legacy_hero_slides(boblift_merged_options());
}

function boblift_merged_options(): array
{
    if (function_exists('boblift_mls_theme_options')) {
        $options = boblift_mls_theme_options();
        if (is_array($options) && !empty($options)) {
            return $options;
        }
    }
    $saved = get_option('boblift_theme_options', []);
    return boblift_array_replace_recursive(boblift_default_options(), is_array($saved) ? $saved : []);
}

/**
 * Sites saved before the hero redesign keep their photos in home.hero_slides (one image per slide). Carry those over to
 * the new home.hero.slides by position, but only where the new slide still shows its built-in image, so photos chosen in
 * the new editor are never overwritten.
 */
function boblift_migrate_legacy_hero_slides(array $options): array
{
    $legacy = $options['home']['hero_slides'] ?? null;
    $slides = $options['home']['hero']['slides'] ?? null;
    if (!is_array($legacy) || !is_array($slides)) {
        return $options;
    }
    $defaults = boblift_default_options()['home']['hero']['slides'] ?? [];
    // The three photos the old seed saved for everyone are not customisations; only other photos are carried over.
    $seed_images = ['images/marine-scene.jpg', 'images/telescopic-banner.jpg', 'images/knuckle-banner.jpg'];
    $is_custom = static function (mixed $image) use ($seed_images): bool {
        $path = is_array($image) ? trim((string) ($image['path'] ?? '')) : trim((string) $image);
        return (is_array($image) && (int) ($image['id'] ?? 0) > 0) || ($path !== '' && !in_array($path, $seed_images, true));
    };
    foreach (array_values($legacy) as $index => $old) {
        if (!is_array($old)) {
            continue;
        }
        // Saved slides beyond the new list (the old editor allowed three) become new slides, so no custom photo is unreachable.
        if (!isset($slides[$index])) {
            if ($is_custom($old['image'] ?? '') || $is_custom($old['mobile_image'] ?? '')) {
                $extra = ['image' => $old['image'] ?? '', 'image_alt' => (string) ($old['image_alt'] ?? ''), 'title' => (string) (($old['title'] ?? '') ?: get_bloginfo('name')), 'tab' => (string) ($old['tab'] ?? $old['title'] ?? get_bloginfo('name'))];
                foreach (['mobile_image', 'text', 'primary_label', 'primary_href'] as $copy_key) {
                    if (!empty($old[$copy_key])) {
                        $extra[$copy_key] = $old[$copy_key];
                    }
                }
                $options['home']['hero']['slides'][$index] = $extra;
            }
            continue;
        }
        // A saved mobile-specific image is carried over unless the new slide already has one.
        if (!empty($old['mobile_image']) && empty($slides[$index]['mobile_image'])) {
            $options['home']['hero']['slides'][$index]['mobile_image'] = $old['mobile_image'];
        }
        if (empty($old['image']) || !$is_custom($old['image'])) {
            continue;
        }
        $default_image = $defaults[$index]['image'] ?? null;
        if ($default_image !== null && ($slides[$index]['image'] ?? $default_image) === $default_image) {
            $options['home']['hero']['slides'][$index]['image'] = $old['image'];
            if (!empty($old['image_alt'])) {
                $options['home']['hero']['slides'][$index]['image_alt'] = $old['image_alt'];
            }
        }
    }
    return $options;
}

function boblift_option(string $path, mixed $default = null): mixed
{
    $value = boblift_options();
    foreach (explode('.', $path) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

function boblift_array_replace_recursive(array $base, array $replacements): array
{
    foreach ($replacements as $key => $value) {
        if (is_array($value) && isset($base[$key]) && is_array($base[$key]) && !array_is_list($value)) {
            $base[$key] = boblift_array_replace_recursive($base[$key], $value);
        } else {
            $base[$key] = $value;
        }
    }
    return $base;
}

function boblift_all_seed_categories(): array
{
    return boblift_seed('categories', []);
}

function boblift_legacy_landing_categories(): array
{
    return array_values(array_filter(boblift_all_seed_categories(), static function (array $category): bool {
        $slug = (string) ($category['slug'] ?? '');
        if (in_array($slug, ['truck-mounted-crane', 'marine-crane'], true)) {
            return false;
        }
        $description = (string) ($category['description'] ?? '');
        return str_starts_with($description, 'Legacy') || str_contains(strtolower($description), 'landing page');
    }));
}

function boblift_seed_product_categories(): array
{
    return array_values(array_filter(boblift_all_seed_categories(), static function (array $category): bool {
        $slug = (string) ($category['slug'] ?? '');
        if (in_array($slug, ['truck-mounted-crane', 'marine-crane'], true)) {
            return true;
        }
        $description = (string) ($category['description'] ?? '');
        return !str_starts_with($description, 'Legacy') && !str_contains(strtolower($description), 'landing page');
    }));
}

function boblift_current_language_for_data(): string
{
    if (function_exists('boblift_current_language_slug')) {
        return (string) boblift_current_language_slug();
    }
    if (function_exists('pll_current_language')) {
        return (string) pll_current_language('slug');
    }
    return '';
}

function boblift_term_matches_current_language(WP_Term $term): bool
{
    $language = boblift_current_language_for_data();
    if ($language === '' || !function_exists('pll_get_term_language')) {
        return true;
    }
    $term_language = (string) pll_get_term_language($term->term_id, 'slug');
    return $term_language === '' || $term_language === $language;
}

function boblift_source_term_slug(WP_Term $term): string
{
    if (!function_exists('pll_get_term_translations')) {
        return $term->slug;
    }

    $translations = pll_get_term_translations($term->term_id);
    if (!is_array($translations) || empty($translations)) {
        return $term->slug;
    }

    $source_id = (int) ($translations['en'] ?? 0);
    if ($source_id <= 0) {
        $source_id = (int) reset($translations);
    }
    $source = $source_id > 0 ? get_term($source_id, $term->taxonomy) : null;

    return $source instanceof WP_Term && !is_wp_error($source) ? $source->slug : $term->slug;
}

function boblift_categories(): array
{
    $categories = boblift_seed_product_categories();
    if (!function_exists('taxonomy_exists') || !taxonomy_exists('product_category')) {
        return $categories;
    }

    $terms = get_terms([
        'taxonomy' => 'product_category',
        'hide_empty' => false,
    ]);
    if (is_wp_error($terms) || empty($terms)) {
        return $categories;
    }

    $legacy_slugs = array_fill_keys(array_map(static fn(array $category): string => (string) ($category['slug'] ?? ''), boblift_legacy_landing_categories()), true);
    $seed_map = [];
    foreach ($categories as $category) {
        $slug = (string) ($category['slug'] ?? '');
        if ($slug !== '') {
            $seed_map[$slug] = $category;
        }
    }

    $current_terms = array_values(array_filter($terms, static function ($term) use ($legacy_slugs): bool {
        return $term instanceof WP_Term
            && !isset($legacy_slugs[$term->slug])
            && !isset($legacy_slugs[boblift_source_term_slug($term)])
            && boblift_term_matches_current_language($term);
    }));

    $category_map = $current_terms ? [] : $seed_map;
    foreach ($current_terms ?: $terms as $term) {
        if (!$term instanceof WP_Term || isset($legacy_slugs[$term->slug]) || !boblift_term_matches_current_language($term)) {
            continue;
        }
        $source_slug = boblift_source_term_slug($term);
        if (isset($legacy_slugs[$source_slug])) {
            continue;
        }
        $fallback = $seed_map[$source_slug] ?? $seed_map[$term->slug] ?? ['slug' => $term->slug, 'sort' => 100];
        $image_id = (int) get_term_meta($term->term_id, '_boblift_image_id', true);
        $image = $image_id > 0 ? (string) wp_get_attachment_image_url($image_id, 'large') : '';
        if ($image === '') {
            $image = (string) get_term_meta($term->term_id, '_boblift_image', true);
        }
        $icon = (string) get_term_meta($term->term_id, '_boblift_icon', true);
        $sort = get_term_meta($term->term_id, '_boblift_sort', true);
        $banner_label = (string) get_term_meta($term->term_id, '_boblift_banner_label', true);
        $banner_title = (string) get_term_meta($term->term_id, '_boblift_banner_title', true);
        $category_map[$term->slug] = array_merge($fallback, [
            'slug' => $term->slug,
            'source_slug' => $source_slug,
            'title' => $term->name,
            'description' => $term->description !== '' ? $term->description : (string) ($fallback['description'] ?? ''),
            'image' => $image !== '' ? $image : (string) ($fallback['image'] ?? ''),
            'icon' => $icon !== '' ? $icon : (string) ($fallback['icon'] ?? 'truck-crane'),
            'sort' => $sort !== '' ? (int) $sort : (int) ($fallback['sort'] ?? 100),
            'banner_label' => $banner_label !== '' ? $banner_label : (string) ($fallback['banner_label'] ?? 'BOB-LIFT Product Range'),
            'banner_title' => $banner_title !== '' ? $banner_title : $term->name,
        ]);
    }

    usort($category_map, static fn(array $a, array $b): int => ((int) ($a['sort'] ?? 100)) <=> ((int) ($b['sort'] ?? 100)));
    return array_values($category_map);
}

function boblift_product_category_group_definitions(): array
{
    return [
        [
            'slug' => 'truck-mounted-crane',
            'title' => 'Truck Mounted Crane',
            'children' => ['telescopic-boom-truck-mounted-cranes', 'knuckle-boom-truck-mounted-cranes', 'pickup-cranes'],
        ],
        [
            'slug' => 'marine-crane',
            'title' => 'Marine Crane',
            'children' => ['knuckle-boom-marine-cranes', 'telescopic-boom-marine-cranes'],
        ],
        [
            'slug' => 'aerial-work-platform',
            'title' => 'Aerial Work Platform',
            'children' => ['truck-mounted-aerial-work-platform'],
        ],
        [
            'slug' => 'other-cranes',
            'title' => 'Other Cranes',
            'children' => ['spider-cranes', 'timber-cranes'],
        ],
    ];
}

// Product list banner photos. Each top-level product family has a built-in photo in
// assets/images/product-banners/ named after its English slug; a subcategory uses its family's photo.
function boblift_product_banner_builtin_photo(?WP_Term $term = null): string
{
    $family = 'default';
    if ($term instanceof WP_Term) {
        $ancestors = get_ancestors($term->term_id, 'product_category', 'taxonomy');
        $top = $ancestors ? get_term((int) end($ancestors), 'product_category') : $term;
        if ($top instanceof WP_Term) {
            $family = sanitize_key(boblift_source_term_slug($top));
        }
    }
    $path = 'images/product-banners/' . $family . '.jpg';
    return is_readable(boblift_asset_path($path)) ? $path : 'images/product-banners/default.jpg';
}

/** Card pictures the importer writes into _boblift_image for every category; they are not banners an editor chose. */
function boblift_seed_category_images(): array
{
    static $images = null;
    return $images ??= array_values(array_unique(array_filter(array_map('strval', array_merge(array_column(boblift_all_seed_categories(), 'image'), ['images/products/crane-accessories.png'])))));
}

// A photo uploaded in the category's "产品页 Banner 图片" field wins over the built-in one.
function boblift_product_banner_photo_url(?WP_Term $term = null): string
{
    // A subcategory without its own upload inherits the nearest ancestor's upload.
    $chain = $term instanceof WP_Term ? array_merge([$term->term_id], array_map('intval', get_ancestors($term->term_id, 'product_category', 'taxonomy'))) : [];
    foreach ($chain as $term_id) {
        $image_id = (int) get_term_meta($term_id, '_boblift_image_id', true);
        $url = $image_id > 0 ? (string) wp_get_attachment_image_url($image_id, 'full') : '';
        if ($url !== '') {
            return $url;
        }
        // A banner saved as a theme path or URL (no attachment) still counts; the importer's seed card images (images/products/…) do not.
        $legacy = trim((string) get_term_meta($term_id, '_boblift_image', true));
        if ($legacy !== '' && !in_array($legacy, boblift_seed_category_images(), true)) {
            return boblift_image_src($legacy);
        }
    }
    return boblift_asset(boblift_product_banner_builtin_photo($term));
}

// The Products mega menu, built from the live category tree so it always matches the catalog:
// every top-level family with its photo and model count, plus its subcategories, or its models
// when it has fewer than two subcategories with products.
function boblift_product_menu_families(): array
{
    // Built once per request and language: the header and the footer both need it.
    static $cache = [];
    $language = function_exists('boblift_current_language_slug') ? boblift_current_language_slug() : '';
    return $cache[$language] ??= (boblift_curated_product_menu_families() ?: boblift_cached_product_menu_families($language));
}

/**
 * The category-generated menu, kept in a transient per language and site address so ordinary requests do not walk the catalog.
 * It is rebuilt after any product, category, term meta or theme option change (the version option below is bumped).
 */
function boblift_cached_product_menu_families(string $language): array
{
    $key = 'boblift_pmenu_' . md5($language . '|' . home_url() . '|' . (string) get_option('boblift_product_menu_version', '0'));
    $cached = get_transient($key);
    if (is_array($cached)) {
        return $cached;
    }
    $families = boblift_build_product_menu_families();
    set_transient($key, $families, DAY_IN_SECONDS);
    return $families;
}

function boblift_bump_product_menu_version(): void
{
    update_option('boblift_product_menu_version', (string) microtime(true), false);
}
foreach (['save_post_product', 'deleted_post', 'trashed_post', 'untrashed_post', 'set_object_terms', 'created_product_category', 'edited_product_category', 'delete_product_category', 'updated_term_meta', 'added_term_meta', 'deleted_term_meta'] as $boblift_menu_hook) {
    add_action($boblift_menu_hook, 'boblift_bump_product_menu_version');
}
// Product meta feeds the menu (model, capacity), so changes made without saving the post invalidate it too.
foreach (['added_post_meta', 'updated_post_meta', 'deleted_post_meta'] as $boblift_menu_meta_hook) {
    add_action($boblift_menu_meta_hook, static function ($meta_id, $post_id, $meta_key): void {
        if (in_array((string) $meta_key, ['_model', '_capacity', '_capacity_value', '_specs', '_boom_type', '_thumbnail_id'], true) && get_post_type((int) $post_id) === 'product') {
            boblift_bump_product_menu_version();
        }
    }, 10, 3);
}
add_action('updated_option', static function (string $option): void {
    if (str_starts_with($option, 'boblift_theme_options') || $option === 'permalink_structure' || $option === 'rewrite_rules') {
        boblift_bump_product_menu_version();
    }
});

/**
 * Product menu groups an administrator curated in the saved "product_nav_groups" (anything different from the built-in
 * default) keep driving the menu; untouched sites get the menu generated from the category tree.
 */
function boblift_curated_product_menu_families(): array
{
    $options = boblift_options();
    $saved = $options['product_nav_groups'] ?? null;
    $default = boblift_default_options()['product_nav_groups'] ?? null;
    $slug_of = static function (string $href): string {
        $path = (string) wp_parse_url($href, PHP_URL_FRAGMENT);
        return $path !== '' ? sanitize_title($path) : sanitize_title(basename(trim((string) wp_parse_url($href, PHP_URL_PATH), '/'), '.html'));
    };
    // Destination identity for the comparison: the default stores /slug and saved options /products#slug, which are the same
    // destination; a custom host, a query string or any other path is kept so a changed link counts as customised.
    $site_host = strtolower((string) wp_parse_url(home_url(), PHP_URL_HOST));
    $dest_of = static function (string $href) use ($site_host): string {
        $parts = (array) wp_parse_url(trim($href));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = strtolower(trim((string) preg_replace('/\.html$/i', '', (string) ($parts['path'] ?? '')), '/'));
        $query = (string) ($parts['query'] ?? '');
        $fragment = strtolower((string) ($parts['fragment'] ?? ''));
        if ($host !== '' && $host !== $site_host) {
            return 'ext:' . $host . '|' . $path . '|' . $query . '|' . $fragment;
        }
        // The two historical spellings of a category link are the same destination: /products#slug and /slug.
        if ($query === '' && $path === 'products' && $fragment !== '') {
            return 'cat:' . $fragment;
        }
        if ($query === '' && $fragment === '' && $path !== '' && !str_contains($path, '/')) {
            return 'cat:' . $path;
        }
        return 'int:' . $path . '|' . $query . '|' . $fragment;
    };
    // Compare every editable field (title, description, icon, image, link labels, icons and destinations).
    // Texts: the saved menu of a translated site carries translated labels while the defaults are English. A text counts as
    // "untouched" when it equals the English default or the translated default saved for that language (by position); any
    // other text is an edit, so a menu whose only change is a label is still treated as customised.
    $source_language = function_exists('boblift_mls_source_language') ? (string) boblift_mls_source_language() : 'en';
    $current_language = function_exists('boblift_current_language_slug') ? (string) boblift_current_language_slug() : $source_language;
    $translated_defaults = [];
    if ($current_language !== '' && $current_language !== $source_language) {
        $translated_file = get_theme_file_path('inc/translations/product-nav-defaults.php');
        $all_translated = is_file($translated_file) ? include $translated_file : [];
        $translated_defaults = is_array($all_translated[$current_language] ?? null) ? $all_translated[$current_language] : [];
    }
    $default_groups = array_values(array_filter((array) $default, 'is_array'));
    $known_text = static function (int $group, ?int $link, string $field, array $values = []) use ($default_groups, $translated_defaults): array {
        $english = $link === null ? ($default_groups[$group][$field] ?? '') : ($default_groups[$group]['links'][$link]['label'] ?? '');
        $local = $link === null ? ($translated_defaults[$group][$field] ?? '') : ($translated_defaults[$group]['links'][$link] ?? '');
        return array_filter([trim((string) $english), trim((string) $local)], static fn($v): bool => $v !== '');
    };
    $signature = static function ($groups, bool $is_saved) use ($dest_of, $known_text): string {
        $rows = [];
        foreach (array_values(array_filter((array) $groups, 'is_array')) as $group_index => $g) {
            $token = static fn(string $value, array $known): string => $is_saved && !in_array(trim($value), $known, true) ? trim($value) : 'default';
            $links = [];
            foreach (array_values(array_filter((array) ($g['links'] ?? []), 'is_array')) as $link_index => $l) {
                $links[] = [$token((string) ($l['label'] ?? ''), $known_text($group_index, $link_index, 'label')), $dest_of((string) ($l['href'] ?? '')), sanitize_key((string) ($l['icon'] ?? ''))];
            }
            $rows[] = [
                $token((string) ($g['title'] ?? ''), $known_text($group_index, null, 'title')), $dest_of((string) ($g['href'] ?? '')),
                $token((string) ($g['description'] ?? ''), $known_text($group_index, null, 'description')), sanitize_key((string) ($g['icon'] ?? '')),
                is_array($g['image'] ?? null) ? ((int) ($g['image']['id'] ?? 0) > 0 ? 'id:' . (int) $g['image']['id'] : (string) ($g['image']['path'] ?? '')) : (string) ($g['image'] ?? ''),
                $links,
            ];
        }
        return (string) wp_json_encode($rows);
    };
    if (!is_array($saved) || $saved === [] || $signature($saved, true) === $signature($default, false)) {
        return [];
    }
    $families = [];
    foreach ($saved as $group) {
        if (!is_array($group) || trim((string) ($group['title'] ?? '')) === '') {
            continue;
        }
        $children = [];
        $count = 0;
        foreach ((array) ($group['links'] ?? []) as $link) {
            if (!is_array($link) || trim((string) ($link['label'] ?? '')) === '') {
                continue;
            }
            $term = get_term_by('slug', $slug_of((string) ($link['href'] ?? '')), 'product_category');
            $link_count = $term instanceof WP_Term ? (int) $term->count : 0;
            $count += $link_count;
            $children[] = ['title' => (string) $link['label'], 'url' => boblift_product_nav_url((string) ($link['href'] ?? '/products')), 'count' => $link_count];
        }
        $group_term = get_term_by('slug', $slug_of((string) ($group['href'] ?? '')), 'product_category');
        $icon = sanitize_key((string) ($group['icon'] ?? ''));
        $families[] = [
            'slug' => sanitize_title((string) $group['title']),
            'source_slug' => $group_term instanceof WP_Term ? boblift_source_term_slug($group_term) : sanitize_title((string) $group['title']),
            'title' => (string) $group['title'],
            'url' => boblift_product_nav_url((string) ($group['href'] ?? '/products')),
            'count' => $count,
            'icon' => $icon !== '' ? $icon : 'truck-crane',
            'photo' => boblift_image_setting_url($group['image'] ?? '', 'images/telescopic-banner.jpg'),
            'children' => $children,
            'models' => [],
            'featured' => [],
            'group' => 'crane',
        ];
    }
    return $families;
}

function boblift_build_product_menu_families(): array
{
    $term_link = static function (WP_Term $term): string {
        $url = get_term_link($term);
        return is_string($url) ? $url : boblift_url('/' . $term->slug);
    };
    $families = [];
    foreach (boblift_product_category_hierarchy() as $group) {
        $term = get_term_by('slug', (string) ($group['slug'] ?? ''), 'product_category');
        if (!$term instanceof WP_Term) {
            continue;
        }
        // Distinct products in the whole family; a product filed under both a parent and a child counts once.
        $total = (int) (new WP_Query([
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'tax_query' => [['taxonomy' => 'product_category', 'terms' => $term->term_id, 'include_children' => true]],
        ]))->found_posts;
        $children = [];
        foreach (($group['children'] ?? []) as $child) {
            $child_term = get_term_by('slug', (string) ($child['slug'] ?? ''), 'product_category');
            if (!$child_term instanceof WP_Term || (int) $child_term->count === 0) {
                continue;
            }
            $children[] = ['title' => $child_term->name, 'url' => $term_link($child_term), 'count' => (int) $child_term->count];
        }
        if ($total === 0) {
            continue;
        }
        $models = [];
        if (count($children) < 2) {
            $children = [];
            $posts = get_posts([
                'post_type' => 'product',
                'post_status' => 'publish',
                'numberposts' => 12,
                'tax_query' => [['taxonomy' => 'product_category', 'terms' => $term->term_id, 'include_children' => true]],
            ]);
            usort($posts, static fn(WP_Post $a, WP_Post $b): int => ((float) get_post_meta($a->ID, '_capacity_value', true) <=> (float) get_post_meta($b->ID, '_capacity_value', true)) ?: strnatcasecmp($a->post_title, $b->post_title));
            foreach (array_slice($posts, 0, 5) as $post) {
                $models[] = ['title' => (string) boblift_localize_product_text_fragments(get_the_title($post)), 'url' => (string) get_permalink($post)];
            }
        }
        // Families split into categories also show a few models across the capacity range.
        $featured = [];
        if ($children !== []) {
            // Only IDs are loaded; full posts are fetched for the four featured models below.
            $posts = get_posts([
                'post_type' => 'product',
                'post_status' => 'publish',
                'numberposts' => -1,
                'fields' => 'ids',
                'update_post_term_cache' => false,
                'tax_query' => [['taxonomy' => 'product_category', 'terms' => $term->term_id, 'include_children' => true]],
            ]);
            _prime_post_caches($posts, false, true);
            usort($posts, static fn(int $a, int $b): int => ((float) get_post_meta($a, '_capacity_value', true) <=> (float) get_post_meta($b, '_capacity_value', true)) ?: strnatcasecmp(get_the_title($a), get_the_title($b)));
            $picked = [];
            $last = count($posts) - 1;
            for ($i = 0; $i < 4 && $last >= 0; $i++) {
                $picked[(int) round($i * $last / 3)] = true;
            }
            foreach (array_keys($picked) as $index) {
                $post = get_post($posts[$index]);
                $english_id = function_exists('boblift_product_english_id') ? boblift_product_english_id($post->ID) : $post->ID;
                $title = (string) boblift_localize_product_text_fragments(get_the_title($post));
                $model = trim((string) get_post_meta($english_id, '_model', true));
                $capacity = function_exists('boblift_product_filter_values') ? (float) (boblift_product_filter_values($post->ID)['capacity'] ?? 0) : 0.0;
                // Short label for the two-column list: model code and capacity, e.g. "SQ5ZA3 · 5 t".
                $short = $model !== '' && $capacity > 0 ? $model . ' · ' . rtrim(rtrim(number_format($capacity, 2, '.', ''), '0'), '.') . ' t' : $title;
                $featured[] = ['title' => $title, 'short' => $short, 'url' => (string) get_permalink($post), 'model' => $model];
            }
        }
        $icon = sanitize_key((string) ($group['category']['icon'] ?? ''));
        $families[] = [
            'slug' => $term->slug,
            'source_slug' => boblift_source_term_slug($term),
            'title' => $term->name,
            'url' => $term_link($term),
            'count' => $total,
            'icon' => $icon !== '' ? $icon : 'truck-crane',
            'photo' => boblift_product_banner_photo_url($term),
            'children' => $children,
            'models' => $models,
            'featured' => $featured,
            'group' => boblift_product_family_group(boblift_source_term_slug($term)),
        ];
    }
    // Cranes first, then the labelled related-equipment groups (fly jibs, aerial work platforms).
    $order = ['crane' => 0, 'attachment' => 1, 'access' => 2];
    $position = array_flip(array_keys($families));
    uksort($families, static fn($a, $b): int => ($order[$families[$a]['group']] <=> $order[$families[$b]['group']]) ?: ($position[$a] <=> $position[$b]));
    return array_values($families);
}

// A forklift fly jib hoists a hanging load, so it counts as a crane attachment. Aerial work platforms lift
// people (特种设备目录 / EN 280 / OSHA 1926.1400(c)(5) keep them apart from cranes) and tail lifts raise loads
// onto a truck bed, so both sit under their own "other lifting equipment" label.
function boblift_product_family_group(string $source_slug): string
{
    return ['forklift-fly-jib-cranes' => 'attachment', 'aerial-work-platform' => 'access', 'tail-lift' => 'access'][$source_slug] ?? 'crane';
}

function boblift_product_family_group_label(string $group): string
{
    $label = ['attachment' => 'Crane attachments', 'access' => 'Other lifting equipment'][$group] ?? '';
    return $label !== '' && function_exists('boblift_localized_static_text') ? boblift_localized_static_text($label) : $label;
}

/**
 * The footer's product column follows the product category tree, like the Products menu. It is the
 * link group saved with source "product_categories" or, in options saved before that existed, the group that still
 * holds the exact historical default product links.
 */
function boblift_footer_is_product_group(array $group): bool
{
    if (($group['source'] ?? '') === 'product_categories') {
        return true;
    }
    $links = is_array($group['links'] ?? null) ? $group['links'] : [];
    if ($links === []) {
        return false;
    }
    // Only the complete historical default counts: the original seven links with their original labels, in their original
    // order. A group an administrator edited (relabelled, reordered, other links) is left as saved.
    $historical = [
        ['Telescopic Boom Truck Mounted Cranes', 'telescopic-boom-truck-mounted-cranes'],
        ['Knuckle Boom Truck Mounted Cranes', 'knuckle-boom-truck-mounted-cranes'],
        ['Pickup Cranes', 'pickup-cranes'],
        ['Marine Cranes', 'knuckle-boom-marine-cranes'],
        ['Forklift Fly Jib Cranes', 'forklift-fly-jib-cranes'],
        ['Aerial Work Platform', 'aerial-work-platform'],
        ['Tail Lift', 'tail-lift'],
    ];
    if (count($links) !== count($historical)) {
        return false;
    }
    foreach (array_values($links) as $index => $link) {
        if (!is_array($link) || !preg_match('~^/?products(?:\.html)?#([a-z0-9-]+)$~', trim((string) ($link['href'] ?? '')), $m)
            || $m[1] !== $historical[$index][1] || trim((string) ($link['label'] ?? '')) !== $historical[$index][0]) {
            return false;
        }
    }
    return true;
}

function boblift_product_category_hierarchy(): array
{
    $categories = boblift_categories();
    $category_map = [];
    foreach ($categories as $category) {
        $slug = (string) ($category['slug'] ?? '');
        if ($slug !== '') {
            $category_map[$slug] = $category;
        }
    }

    $parent_children = [];
    $has_wp_terms = false;
    if (function_exists('taxonomy_exists') && taxonomy_exists('product_category')) {
        $terms = get_terms(['taxonomy' => 'product_category', 'hide_empty' => false]);
        if (!is_wp_error($terms) && !empty($terms)) {
            $terms = array_values(array_filter($terms, static fn($term): bool => $term instanceof WP_Term && boblift_term_matches_current_language($term)));
            $has_wp_terms = !empty($terms);
            $term_slug_by_id = [];
            foreach ($terms as $term) {
                if ($term instanceof WP_Term) {
                    $term_slug_by_id[(int) $term->term_id] = $term->slug;
                }
            }
            foreach ($terms as $term) {
                if (!$term instanceof WP_Term || (int) $term->parent <= 0) {
                    continue;
                }
                $parent_slug = $term_slug_by_id[(int) $term->parent] ?? '';
                if ($parent_slug !== '') {
                    $parent_children[$parent_slug][] = $term->slug;
                }
            }
        }
    }

    if (!$has_wp_terms) {
        foreach (boblift_product_category_group_definitions() as $group) {
            $parent_slug = (string) ($group['slug'] ?? '');
            foreach (($group['children'] ?? []) as $child_slug) {
                if ($parent_slug !== '' && !in_array($child_slug, $parent_children[$parent_slug] ?? [], true)) {
                    $parent_children[$parent_slug][] = $child_slug;
                }
            }
        }
    }

    $normalized_parent_children = [];
    $child_lookup = [];
    foreach ($parent_children as $parent_slug => $child_slugs) {
        if (!isset($category_map[$parent_slug])) {
            continue;
        }
        $child_slugs = array_values(array_unique(array_filter($child_slugs, static fn(string $child_slug): bool => isset($category_map[$child_slug]))));
        usort($child_slugs, static function (string $a, string $b) use ($category_map): int {
            $sort = ((int) ($category_map[$a]['sort'] ?? 100)) <=> ((int) ($category_map[$b]['sort'] ?? 100));
            return $sort !== 0 ? $sort : strcasecmp((string) ($category_map[$a]['title'] ?? $a), (string) ($category_map[$b]['title'] ?? $b));
        });
        $normalized_parent_children[$parent_slug] = $child_slugs;
        foreach ($child_slugs as $child_slug) {
            $child_lookup[$child_slug] = true;
        }
    }

    $result = [];
    foreach ($categories as $category) {
        $slug = (string) ($category['slug'] ?? '');
        if ($slug === '' || isset($child_lookup[$slug])) {
            continue;
        }
        $child_slugs = $normalized_parent_children[$slug] ?? [];
        $result[] = [
            'slug' => $slug,
            'title' => $category['title'] ?? $slug,
            'category' => $category,
            'children' => array_values(array_map(static fn(string $child_slug): array => $category_map[$child_slug], $child_slugs)),
        ];
    }

    return $result;
}

function boblift_product_parent_category_map(): array
{
    $map = [];
    foreach (boblift_product_category_hierarchy() as $group) {
        foreach (($group['children'] ?? []) as $child) {
            $child_slug = (string) ($child['slug'] ?? '');
            if ($child_slug !== '') {
                $map[$child_slug] = (string) ($group['slug'] ?? '');
            }
        }
    }
    return $map;
}

function boblift_products_seed(): array
{
    return boblift_seed('products', []);
}

function boblift_articles_seed(): array
{
    return boblift_seed('articles', []);
}

function boblift_cases_seed(): array
{
    return boblift_seed('cases', []);
}

function boblift_find_seed_item(string $group, string $slug): ?array
{
    foreach (boblift_seed($group, []) as $item) {
        if (($item['slug'] ?? '') === $slug) {
            return $item;
        }
    }
    return null;
}
