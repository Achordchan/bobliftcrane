<?php
// Copy for the Why Choose BOB-LIFT page (the old site's "Why Choose Us" page). Certificates, factory facts and export
// figures are read from the About, Factory Tour and About-page options, so each lives in one place.
return [
    'whyChoose' => [
        'heroLabel' => 'Why Choose BOB-LIFT',
        'heroTitle' => 'Why customers choose BOB-LIFT cranes',
        'heroText' => 'A crane factory in Xuzhou with its own production line, test bay and export team. Here is what stands behind every BOB-LIFT crane.',
        'heroImageAlt' => 'Finished BOB-LIFT cranes lined up in the assembly hall',
        'applicationsLabel' => 'Product Application',
        'applicationsTitle' => 'Built for jobs that lift and move materials',
        'applicationsText' => 'BOB-LIFT cranes work wherever materials need to be lifted and moved.',
        'applications' => [
            // Photos from customer feedback, matched to saved entries by title; "aliases" are the wordings the Chinese and
            // Japanese seeds saved. A saved entry may name its own image; an unrecognised one shows text only.
            ['title' => 'Urban construction', 'image' => 'images/why-choose/urban-construction.webp'],
            ['title' => 'Advertising installation', 'image' => 'images/why-choose/advertising-installation.webp', 'aliases' => ['广告安装', '広告物の設置']],
            ['title' => 'Electrical maintenance', 'image' => 'images/why-choose/electrical-maintenance.webp', 'aliases' => ['电气维护', '電気設備のメンテナンス']],
            ['title' => 'Working at height', 'image' => 'images/why-choose/working-at-height.webp'],
            ['title' => 'Port terminals', 'image' => 'images/why-choose/port-terminals.webp'],
            ['title' => 'Factory workshops', 'image' => 'images/why-choose/factory-workshops.webp', 'aliases' => ['工場内作業']],
            ['title' => 'Construction sites', 'image' => 'images/why-choose/construction-sites.webp'],
            ['title' => 'Agriculture and forestry', 'image' => 'images/why-choose/agriculture-forestry.webp'],
        ],
        'applicationsLink' => 'See customer cases',
        'certificatesLabel' => 'Our Certificate',
        'certificatesTitle' => 'Certificates and patents',
        'certificatesText' => 'CE, EAC, ISO 9001 and ISO 14001 certificates, our trademarks and our crane patents. Select a document to view it full size.',
        'certificatesLink' => 'More on the About page',
        'closeLabel' => 'Close certificate preview',
        'productionLabel' => 'Production Equipment',
        'productionTitle' => 'Cutting, forming and machining in our own factory',
        'productionText' => 'Plasma and laser cutting, bending machines, drilling machines, boring machines and shot blasting machines prepare the steel before our assembly line builds and tests each crane.',
        'productionImageAlt' => 'Workstations in the BOB-LIFT assembly hall',
        'factoryLink' => 'Take the factory tour',
        'qualityLink' => 'See how cranes are tested',
        'marketLabel' => 'Production Market',
        'marketTitle' => 'Cranes at work around the world',
        'marketText' => 'Our cranes are exported mainly to Asia, the Americas, Europe and Africa. Russia is one of our strongest markets, with more than 200 cranes delivered there.',
        'visitsLink' => 'Customer visits to our factory',
        'serviceLabel' => 'Our Service',
        'serviceTitle' => 'With you before, during and after the order',
        'services' => [
            ['title' => 'Pre-sales', 'text' => 'Our team uses its product knowledge to help you choose the right crane for your job and chassis.'],
            ['title' => 'In-sales', 'text' => 'We keep you updated on the progress of your order while it is built and tested.'],
            ['title' => 'After-sales', 'text' => 'Technical guidance and after-sales support for installation and operation.'],
        ],
        'serviceLink' => 'Service and operation guides',
        'ctaTitle' => 'Tell us about your lifting job.',
        'ctaText' => 'Send the load, the reach and your truck or vessel, and our sales team will suggest a crane.',
        'ctaButton' => 'Send an Inquiry',
        'ctaLink' => 'View products',
    ],
];
