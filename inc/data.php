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
    return [
        'hero' => [
            'label' => 'BOB-LIFT Social Media',
            'image' => boblift_image_option('images/social-media/social-media-hero-factory.webp'),
            'image_alt' => 'BOB-LIFT factory official social media updates',
        ],
        'labels' => [
            'main_platforms_label' => 'Our Main Platforms',
            'main_platforms_title' => 'Connect with BOB-LIFT where you work.',
            'main_platforms_text' => 'Current key channels are highlighted first. More domestic and international platforms are listed below with lighter visual weight.',
            'featured_visit_label' => 'Visit channel',
            'major_visit_label' => 'Visit',
            'more_channels_label' => 'More Channels',
            'more_channels_title' => 'Additional platforms around the world.',
            'content_cards_label' => 'What You Can Follow',
            'content_cards_title' => 'Real content from real work.',
        ],
        'panel' => [
            'title' => 'Official Channels',
            'items' => [
                ['text' => 'Real updates'],
                ['text' => 'Factory insights'],
                ['text' => 'Project communication'],
                ['text' => 'Global support'],
            ],
        ],
        'featured_platforms' => [
            ['enabled' => '1', 'label' => 'LinkedIn', 'icon' => 'linkedin', 'href' => '/contact.html', 'text' => 'Professional insights and industry updates.', 'tone' => 'bg-[#0A66C2] text-white'],
            ['enabled' => '1', 'label' => 'YouTube', 'icon' => 'youtube', 'href' => '/contact.html', 'text' => 'Product videos and factory showcases.', 'tone' => 'bg-[#FF0000] text-white'],
            ['enabled' => '1', 'label' => 'Facebook', 'icon' => 'facebook', 'href' => '/contact.html', 'text' => 'News, events and customer stories.', 'tone' => 'bg-[#1877F2] text-white'],
        ],
        'major_platforms' => [
            ['enabled' => '1', 'label' => 'TikTok', 'icon' => 'tiktok', 'href' => '/contact.html', 'tone' => 'bg-[#111827] text-white'],
            ['enabled' => '1', 'label' => 'Douyin', 'icon' => 'douyin', 'href' => '/contact.html', 'tone' => 'bg-[#111827] text-white'],
            ['enabled' => '1', 'label' => 'Xiaohongshu', 'icon' => 'xiaohongshu', 'href' => '/contact.html', 'tone' => 'bg-[#FF2442] text-white'],
            ['enabled' => '1', 'label' => 'WeChat Channels', 'icon' => 'wechat', 'href' => '/contact.html', 'tone' => 'bg-[#07C160] text-white'],
            ['enabled' => '1', 'label' => 'Weibo', 'icon' => 'weibo', 'href' => '/contact.html', 'tone' => 'bg-[#F5C04B] text-[#0B1B33]'],
        ],
        'secondary_platforms' => [
            ['enabled' => '1', 'label' => 'Instagram', 'icon' => 'instagram', 'href' => '/contact.html', 'tone' => 'bg-[linear-gradient(135deg,#833AB4,#FD1D1D,#FCAF45)] text-white'],
            ['enabled' => '1', 'label' => 'X', 'icon' => 'x', 'href' => '/contact.html', 'tone' => 'bg-[#111827] text-white'],
            ['enabled' => '1', 'label' => 'WhatsApp', 'icon' => 'whatsapp', 'href' => '/contact.html', 'tone' => 'bg-[#25D366] text-white'],
            ['enabled' => '1', 'label' => 'Telegram', 'icon' => 'telegram', 'href' => '/contact.html', 'tone' => 'bg-[#229ED9] text-white'],
            ['enabled' => '1', 'label' => 'Pinterest', 'icon' => 'pinterest', 'href' => '/contact.html', 'tone' => 'bg-[#E60023] text-white'],
            ['enabled' => '1', 'label' => 'Bilibili', 'icon' => 'bilibili', 'href' => '/contact.html', 'tone' => 'bg-[#00A1D6] text-white'],
            ['enabled' => '1', 'label' => 'Kuaishou', 'icon' => 'kuaishou', 'href' => '/contact.html', 'tone' => 'bg-[#FF6600] text-white'],
            ['enabled' => '1', 'label' => 'Zhihu', 'icon' => 'zhihu', 'href' => '/contact.html', 'tone' => 'bg-[#1677FF] text-white'],
            ['enabled' => '1', 'label' => 'Baidu Baijiahao', 'icon' => 'baidu', 'href' => '/contact.html', 'tone' => 'bg-[#2F6BFF] text-white'],
            ['enabled' => '1', 'label' => 'Toutiao', 'icon' => 'toutiao', 'href' => '/contact.html', 'tone' => 'bg-[#F04142] text-white'],
            ['enabled' => '1', 'label' => 'Youku', 'icon' => 'youku', 'href' => '/contact.html', 'tone' => 'bg-[#2B8CFF] text-white'],
            ['enabled' => '1', 'label' => 'QQ', 'icon' => 'qq', 'href' => '/contact.html', 'tone' => 'bg-[#111827] text-white'],
            ['enabled' => '1', 'label' => 'LINE', 'icon' => 'line', 'href' => '/contact.html', 'tone' => 'bg-[#06C755] text-white'],
            ['enabled' => '1', 'label' => 'VK', 'icon' => 'vk', 'href' => '/contact.html', 'tone' => 'bg-[#2787F5] text-white'],
            ['enabled' => '1', 'label' => 'OK', 'icon' => 'ok', 'href' => '/contact.html', 'tone' => 'bg-[#EE8208] text-white'],
            ['enabled' => '1', 'label' => 'Threads', 'icon' => 'threads', 'href' => '/contact.html', 'tone' => 'bg-[#111827] text-white'],
            ['enabled' => '1', 'label' => 'Viber', 'icon' => 'viber', 'href' => '/contact.html', 'tone' => 'bg-[#7360F2] text-white'],
            ['enabled' => '1', 'label' => 'Medium', 'icon' => 'medium', 'href' => '/contact.html', 'tone' => 'bg-[#111827] text-white'],
            ['enabled' => '1', 'label' => 'Quora', 'icon' => 'quora', 'href' => '/contact.html', 'tone' => 'bg-[#B92B27] text-white'],
        ],
        'content_cards' => [
            ['enabled' => '1', 'icon' => 'package-check', 'title' => 'Product updates', 'text' => 'New crane models, feature highlights and technical improvements.', 'image' => boblift_image_option('images/social-media/social-media-product-updates.webp')],
            ['enabled' => '1', 'icon' => 'building-2', 'title' => 'Factory news', 'text' => 'Production progress, equipment upgrades and quality control.', 'image' => boblift_image_option('images/social-media/social-media-factory-news.webp')],
            ['enabled' => '1', 'icon' => 'message-circle', 'title' => 'Project communication', 'text' => 'Real project discussions and BOB-LIFT delivery values.', 'image' => boblift_image_option('images/social-media/social-media-project-communication.webp')],
            ['enabled' => '1', 'icon' => 'send', 'title' => 'Export support', 'text' => 'Export policies, shipping support and after-sales information.', 'image' => boblift_image_option('images/social-media/social-media-export-support.webp')],
        ],
        'cta' => [
            'label' => 'Direct Consultation',
            'title' => 'Need a direct reply from BOB-LIFT?',
            'text' => 'Share your working radius, load, installation conditions and target model. Our team will reply through the contact channel you provide.',
            'button_label' => 'Contact Us',
            'button_href' => '/contact.html',
        ],
    ];
}

function boblift_social_media_page_options(): array
{
    $value = boblift_option('social_media_page', []);
    return boblift_array_replace_recursive(boblift_social_media_page_defaults(), is_array($value) ? $value : []);
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
        'updated' => '2026-06-29',
        'side_title' => 'Scope',
        'side_text' => 'This policy applies to BOB-LIFT website forms, catalog requests, quote requests and direct business communication initiated through the site.',
        'sections' => [
            ['title' => 'Information we collect', 'text' => 'We may collect your name, company, email address, phone number, country, product interest, project requirements and messages submitted through website forms. Basic technical information such as browser type, pages visited and security verification status may also be processed for site reliability and anti-spam protection.'],
            ['title' => 'How we use information', 'text' => 'We use submitted information to respond to inquiries, prepare quotations, send requested catalog materials, provide product selection support, arrange after-sales communication and improve website service quality.'],
            ['title' => 'Sharing and disclosure', 'text' => 'BOB-LIFT does not sell personal information. We only share necessary information with internal sales, engineering, logistics or service teams when required to respond to your request. Information may also be processed by trusted website, email, analytics or security service providers.'],
            ['title' => 'Data retention', 'text' => 'Business inquiry records are kept only as long as needed for communication, quotation, project follow-up, legal compliance and legitimate business record keeping.'],
            ['title' => 'Your choices', 'text' => 'You may contact BOB-LIFT to request access, correction or deletion of your inquiry information, subject to applicable business, legal and technical limitations.'],
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
        'updated' => '2026-06-29',
        'sections' => [
            ['title' => 'Website information', 'text' => 'Product images, technical descriptions, capacity references and application notes are provided for general business reference. Final specifications, configurations and quotations must be confirmed in writing by BOB-LIFT.'],
            ['title' => 'Product selection', 'text' => 'Crane model selection depends on load, working radius, chassis, installation conditions, local regulations and project requirements. Website information should not replace engineering confirmation.'],
            ['title' => 'Intellectual property', 'text' => 'BOB-LIFT names, product materials, photos, text, layout and other website content may not be copied, modified or reused for commercial purposes without written permission.'],
            ['title' => 'User submissions', 'text' => 'When submitting forms, you agree to provide accurate business contact and project information. Do not submit unlawful, misleading, abusive or unrelated content.'],
            ['title' => 'External links and services', 'text' => 'The website may link to external maps, social platforms, video platforms or service providers. Their content and policies are controlled by those third parties.'],
            ['title' => 'Limitation of liability', 'text' => 'BOB-LIFT works to keep website information accurate, but does not guarantee uninterrupted availability or that all content is free from errors. Business decisions should rely on confirmed quotations and technical documents.'],
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
            ['label' => 'Global Customer Visits', 'href' => '/global-customer-visits.html', 'slug' => 'global-customer-visits'],
            ['label' => 'Social Media', 'href' => '/social-media.html', 'slug' => 'social-media'],
            ['label' => 'Public Welfare', 'href' => '/public-welfare.html', 'slug' => 'public-welfare'],
            ['label' => 'Contact Us', 'href' => '/contact.html', 'slug' => 'contact'],
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
            'label' => 'International Certifications',
            'title' => 'Certification documents for export projects',
            'text' => 'Core certification documents supporting overseas procurement, factory audits and project delivery reviews.',
            'items' => [
                ['image' => boblift_image_option('certifications/iso.svg'), 'title' => 'ISO 9001 Quality Management', 'text' => 'Quality management system certificate'],
                ['image' => boblift_image_option('certifications/ce.svg'), 'title' => 'CE European Conformity', 'text' => 'European conformity certificate'],
                ['image' => boblift_image_option('certifications/eac.svg'), 'title' => 'EAC Conformity', 'text' => 'EAEU conformity certificate'],
                ['image' => boblift_image_option('certifications/sgs.svg'), 'title' => 'SGS Inspection & Certification', 'text' => 'Inspection and certification document'],
            ],
        ],
        'global_footprint' => [
            'stats' => [
                ['number' => '120', 'suffix' => '+', 'label' => 'Countries Exported'],
                ['number' => '35', 'suffix' => '+', 'label' => 'Years Experience'],
            ],
        ],
        'timeline' => [
            'label' => 'Development Timeline',
            'title' => 'Built step by step for global lifting projects.',
            'items' => [
                ['year' => '1989', 'title' => 'Company Founded', 'text' => 'BOB-LIFT started with practical lifting equipment engineering and manufacturing.', 'image' => boblift_image_option('images/truck-mounted-crane.png')],
                ['year' => '2005', 'title' => 'Global Export Expansion', 'text' => 'Export service expanded from local projects to global distributors and buyers.', 'image' => boblift_image_option('images/marine-scene.jpg')],
                ['year' => '2015', 'title' => 'Advanced R&D Center Established', 'text' => 'Engineering, testing and product development became a dedicated capability.', 'image' => boblift_image_option('images/factory-workshop.jpg')],
                ['year' => '2022', 'title' => 'Smart Manufacturing Upgraded', 'text' => 'Production, inspection and delivery workflows were upgraded for global demand.', 'image' => boblift_image_option('images/aerial-platform.jpg')],
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

function boblift_about_page_options(): array
{
    $value = boblift_option('about_page', []);
    return boblift_array_replace_recursive(boblift_about_page_defaults(), is_array($value) ? $value : []);
}

function boblift_options(): array
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

// A photo uploaded in the category's "产品页 Banner 图片" field wins over the built-in one.
function boblift_product_banner_photo_url(?WP_Term $term = null): string
{
    $image_id = $term instanceof WP_Term ? (int) get_term_meta($term->term_id, '_boblift_image_id', true) : 0;
    $url = $image_id > 0 ? (string) wp_get_attachment_image_url($image_id, 'full') : '';
    return $url !== '' ? $url : boblift_asset(boblift_product_banner_builtin_photo($term));
}

// The Products mega menu, built from the live category tree so it always matches the catalog:
// every top-level family with its photo and model count, plus its subcategories, or its models
// when it has fewer than two subcategories with products.
function boblift_product_menu_families(): array
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
        $icon = sanitize_key((string) ($group['category']['icon'] ?? ''));
        $families[] = [
            'slug' => $term->slug,
            'source_slug' => boblift_source_term_slug($term),
            'title' => $term->name,
            'url' => $term_link($term),
            'count' => $total,
            'icon' => $icon !== '' && is_readable(boblift_asset_path('images/mega-menu-icons/' . $icon . '.png')) ? $icon : 'truck-crane',
            'photo' => boblift_product_banner_photo_url($term),
            'children' => $children,
            'models' => $models,
        ];
    }
    return $families;
}

/**
 * The footer's product column follows the product category tree, like the Products menu. It is the
 * link group saved with source "product_categories" or, in options saved before that existed, the
 * group whose links all point into the catalog.
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
    foreach ($links as $link) {
        if (!is_array($link) || !preg_match('~^/?products(?:\.html)?(?:[/#?]|$)~', trim((string) ($link['href'] ?? '')))) {
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
