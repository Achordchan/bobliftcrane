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
        'title' => 'Construction Equipment',
        'text' => 'Explore reliable BOB-LIFT lifting and construction machinery for global projects.',
        'image' => boblift_image_option('images/products/truck-mounted-crane.png'),
        'image_alt' => 'BOB-LIFT truck mounted crane',
        'watermark' => 'BOB-LIFT',
    ];
}

function boblift_social_media_page_defaults(): array
{
    return [
        'hero' => [
            'label' => 'BOB-LIFT Social Media',
            'title' => 'Follow BOB-LIFT official updates.',
            'text' => 'Stay connected with BOB-LIFT on the platforms you trust. Get product updates, factory news, project communication and export-ready lifting solution insights from official channels.',
            'button_label' => 'Explore Channels',
            'email_label' => 'Email Us',
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
            'aria_label' => 'Public Welfare',
            'image' => boblift_image_option('images/public-welfare/public-welfare-hero-banner.webp'),
            'image_alt' => 'BOB-LIFT public welfare factory visit banner',
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
        'auto_label' => 'Updated',
        'auto_value' => 'Auto',
        'core_pages_title' => 'Core Pages',
        'product_range_label' => 'Product Range',
        'product_range_title' => 'Products and categories',
        'view_products_label' => 'View Products',
        'product_categories_title' => 'Product Categories',
        'products_title' => 'Products',
        'news_categories_title' => 'News Categories',
        'news_title' => 'News',
        'cases_title' => 'Cases',
        'help_title' => 'How this updates',
        'html_note_label' => 'HTML Sitemap:',
        'html_note' => 'generated from WordPress queries, so published pages, products, news and cases appear automatically.',
        'xml_note_label' => 'XML Sitemap:',
        'xml_note' => 'use WordPress Core at /sitemap.xml, or a mature SEO plugin such as Yoast SEO or Rank Math on production.',
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
            'texture_image' => boblift_image_option('images/about/about-logo-cranes-background-realistic-v3.png'),
            'silhouette_image' => boblift_image_option('images/about/about-logo-1.png'),
            'silhouette_alt' => 'BOB-LIFT crane silhouette background',
            'aerial_start_image' => boblift_image_option('images/about/about-aerial-mid-start.webp'),
            'aerial_end_image' => boblift_image_option('images/about/about-aerial-mid-end.webp'),
            'aerial_video' => ['id' => 0, 'path' => 'videos/about/about-aerial-mid.mp4'],
            'aerial_start_alt' => 'BOB-LIFT factory aerial view opening frame',
            'aerial_end_alt' => 'BOB-LIFT factory aerial view ending frame',
            'label' => 'About BOB-LIFT',
            'headline' => 'Engineering Strength Behind Every Lift.',
            'paragraph' => 'BOB-LIFT integrates crane manufacturing, engineering expertise, quality control, and global service capability to deliver reliable lifting solutions for demanding projects worldwide.',
            'proof_chips' => [
                ['number' => '01', 'label' => 'Advanced Crane Manufacturing'],
                ['number' => '02', 'label' => 'Global Export Capability'],
                ['number' => '03', 'label' => 'Strict Quality Control'],
            ],
        ],
        'strength' => [
            'image' => boblift_image_option('images/about/about-logo-prototype-crane-ai.png'),
            'label' => 'Company Strength',
            'title' => 'Manufacturing intelligence for global lifting projects.',
            'text' => 'BOB-LIFT combines product engineering, precision production and responsive export service into one disciplined workflow for overseas B2B buyers.',
            'cta_label' => 'Talk to Our Team',
            'cta_href' => '/contact',
            'cards' => [
                ['title' => 'R&D and Engineering', 'text' => 'Engineering teams support practical crane selection, structural design and project-specific configurations.'],
                ['title' => 'Precision Manufacturing', 'text' => 'Controlled welding, assembly and inspection workflows keep every lifting system stable and repeatable.'],
                ['title' => 'Global Delivery', 'text' => 'Export-ready documentation, packing and logistics support help projects move across international markets.'],
                ['title' => 'After-Sales Support', 'text' => 'Service guidance, spare parts coordination and technical response help customers keep equipment working.'],
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
            'cta_label' => 'Plan a Visit',
            'cta_href' => '/contact.html',
            'home_title' => 'Customer Visit Gallery',
            'home_cta_label' => 'View More Photos',
            'visit_count_suffix' => 'Visit Photos',
            'pagination_label' => 'Customer visit pages',
            'empty_text' => 'No customer visit photos have been added yet.',
            'items' => [
                ['image' => boblift_image_option('images/factory-workshop.jpg'), 'label' => 'Factory Walkthrough', 'title' => 'Production line review', 'text' => 'Customers can review workshop order, assembly workflow and product quality details with the BOB-LIFT team.'],
                ['image' => boblift_image_option('images/about/quanyuan-compressed.jpg'), 'label' => 'Technical Discussion', 'title' => 'Project requirement meeting', 'text' => 'Our team discusses load capacity, working radius, installation conditions and export requirements with visiting partners.'],
                ['image' => boblift_image_option('images/oldsite/news/enter-the-bob-lift-factory-with-professional-manufacturing.jpg'), 'label' => 'Product Inspection', 'title' => 'Close look at crane details', 'text' => 'Visitors can compare product structures, manufacturing details and practical configuration options before choosing a model.'],
            ],
        ],
        'executives' => [
            'label' => 'Executive Leadership',
            'title' => 'Leadership Team',
            'group_image' => boblift_image_option('images/about/quanyuan-compressed.jpg'),
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
            'children' => ['telescopic-boom-truck-mounted-cranes', 'knuckle-boom-truck-mounted-cranes'],
        ],
        [
            'slug' => 'marine-crane',
            'title' => 'Marine Crane',
            'children' => ['knuckle-boom-marine-cranes', 'telescopic-boom-marine-cranes'],
        ],
    ];
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
