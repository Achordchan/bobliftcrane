import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

type LandingCategory = {
  slug: string;
  title: string;
  shortTitle: string;
  legacyPath: string;
  aliases?: string[];
  description: string;
  defaultCategoryFilters?: string[];
  defaultCapacityFilters?: string[];
  defaultBoomTypeFilters?: string[];
  sortOrder: number;
};

const truckCategories = ['telescopic-boom-truck-mounted-cranes', 'knuckle-boom-truck-mounted-cranes'];
const marineCategories = ['knuckle-boom-marine-cranes', 'telescopic-boom-marine-cranes'];
const craneCategories = [
  ...truckCategories,
  ...marineCategories,
  'pickup-cranes',
  'forklift-fly-jib-cranes',
  'aerial-work-platform',
  'tail-lift',
  'spider-cranes',
  'timber-cranes',
  'other-cranes',
];

const capacityPages: LandingCategory[] = [
  capacityPage('1-ton-crane', '1 Ton Cranes', '1 Ton', '0 - 5 tons', 731),
  capacityPage('2-ton-crane', '2 Ton Cranes', '2 Ton', '0 - 5 tons', 732),
  capacityPage('3-ton-crane', '3 Ton Crane Manufacturers and Suppliers', '3 Ton', '0 - 5 tons', 733),
  capacityPage('4-ton-crane', '4 Ton Crane Manufacturers and Suppliers', '4 Ton', '0 - 5 tons', 734),
  capacityPage('5-ton-crane', '5 Ton Crane Manufacturers and Suppliers', '5 Ton', '0 - 5 tons', 735),
  capacityPage('6-ton-crane', '6 Ton Crane Manufacturers and Suppliers', '6 Ton', '5 - 10 tons', 736),
  capacityPage('8-ton-crane', '8 Ton Crane Manufacturers and Suppliers', '8 Ton', '5 - 10 tons', 738),
  capacityPage('10-ton-crane', '10 Ton Crane Manufacturers and Suppliers', '10 Ton', '5 - 10 tons', 740),
  capacityPage('12-ton-crane', '12 Ton Crane Manufacturers and Suppliers', '12 Ton', '10 - 20 tons', 742),
  capacityPage('16-ton-crane', '16 Ton Crane Manufacturers and Suppliers', '16 Ton', '10 - 20 tons', 746),
  capacityPage('20-ton-crane', '20 Ton Crane Manufacturers and Suppliers', '20 Ton', '10 - 20 tons', 750),
  capacityPage('25-ton-crane', '25 Ton Crane Manufacturers and Suppliers', '25 Ton', '20 - 50 tons', 755),
];

const landingCategories: LandingCategory[] = [
  {
    slug: 'truck-mounted-crane',
    title: 'Cranes for Truck | Truck Mounted Cranes',
    shortTitle: 'Truck Mounted Cranes',
    legacyPath: '/truck-mounted-crane',
    aliases: ['/Products-Truck-Mounted-Cranes.html'],
    description: 'Truck mounted crane landing page preserved from the legacy BOB-LIFT site, combining telescopic and knuckle boom truck crane families.',
    defaultCategoryFilters: truckCategories,
    sortOrder: 701,
  },
  {
    slug: 'marine-crane',
    title: 'Marine Cranes | Ship Cranes',
    shortTitle: 'Marine Cranes',
    legacyPath: '/marine-crane',
    aliases: ['/Products-Marine-Cranes.html'],
    description: 'Marine crane and ship crane landing page preserved from the legacy BOB-LIFT site.',
    defaultCategoryFilters: marineCategories,
    sortOrder: 702,
  },
  {
    slug: 'crane-by-ton',
    title: 'China Crane Manufacturer and Supplier',
    shortTitle: 'Crane by Ton',
    legacyPath: '/crane-by-ton',
    aliases: ['/Products-Crane-by-Ton.html'],
    description: 'Legacy crane-by-ton landing page for buyers comparing BOB-LIFT lifting capacity ranges.',
    defaultCategoryFilters: craneCategories,
    sortOrder: 703,
  },
  ...capacityPages,
  {
    slug: 'compact-excavator',
    title: 'China Compact excavator manufacturers, Compact excavator suppliers, Compact excavator wholesaler',
    shortTitle: 'Compact Excavator',
    legacyPath: '/products/Compact-excavator.html',
    aliases: ['/Products-Excavator.html', '/Products-Excavator-by-Ton.html', '/Medium-Excavator-1.html', '/Mini-Excavator-1.html', '/products/Small-Scale-Excavator.html'],
    description: 'Legacy compact excavator URL group kept as a direct-render landing page for old search and sitemap traffic.',
    defaultCategoryFilters: ['other-cranes'],
    sortOrder: 781,
  },
  {
    slug: 'heavy-duty-crane',
    title: 'China Heavy-duty Crane manufacturers, Heavy-duty Crane suppliers, Heavy-duty Crane wholesaler',
    shortTitle: 'Heavy-duty Crane',
    legacyPath: '/products/Heavy-duty-Crane.html',
    description: 'Legacy heavy-duty crane SEO landing page controlled from the backend.',
    defaultCategoryFilters: craneCategories,
    sortOrder: 782,
  },
  {
    slug: 'hydraulic-crane',
    title: 'China Hydraulic Crane manufacturers, Hydraulic Crane suppliers, Hydraulic Crane wholesaler',
    shortTitle: 'Hydraulic Crane',
    legacyPath: '/products/Hydraulic-Crane.html',
    description: 'Legacy hydraulic crane SEO landing page controlled from the backend.',
    defaultCategoryFilters: craneCategories,
    sortOrder: 783,
  },
  {
    slug: 'industrial-crane',
    title: 'China Industrial Crane manufacturers, Industrial Crane suppliers, Industrial Crane wholesaler',
    shortTitle: 'Industrial Crane',
    legacyPath: '/products/Industrial-Crane.html',
    description: 'Legacy industrial crane SEO landing page controlled from the backend.',
    defaultCategoryFilters: craneCategories,
    sortOrder: 784,
  },
  {
    slug: 'lifting-equipment-machines',
    title: 'China Lifting Equipment manufacturers, Lifting Equipment suppliers, Lifting Equipment wholesaler',
    shortTitle: 'Lifting Equipment',
    legacyPath: '/products/Lifting-Equipment.html',
    aliases: ['/products/Lightweight-construction-equipment.html', '/products/Efficient-Earthmover.html'],
    description: 'Legacy lifting equipment and machines landing page preserved as a backend-managed direct-render page.',
    defaultCategoryFilters: craneCategories,
    sortOrder: 785,
  },
];

export async function up({ db }: MigrateUpArgs): Promise<void> {
  for (const category of landingCategories) {
    await db.execute(sql.raw(upsertCategorySql(category)));
  }

  await db.execute(sql.raw(updateMiniDumperSeoSql()));
  await db.execute(sql.raw(updateAerialWorkPlatformSeoSql()));
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  const slugs = landingCategories.map((item) => q(item.slug)).join(', ');
  await db.execute(sql.raw(`
    DELETE FROM "product_categories" WHERE "slug" IN (${slugs});
    UPDATE "products"
    SET "seo_meta_title"=NULL, "seo_meta_description"=NULL, "updated_at"=now()
    WHERE "slug"='truck-mounted-18-meter-aerial-work-platform';
  `));
}

function capacityPage(slug: string, title: string, shortTitle: string, capacityFilter: string, sortOrder: number): LandingCategory {
  return {
    slug,
    title,
    shortTitle,
    legacyPath: `/${slug}`,
    description: `Legacy ${shortTitle.toLowerCase()} crane landing page preserved for old BOB-LIFT URLs and current product testing.`,
    defaultCategoryFilters: craneCategories,
    defaultCapacityFilters: [capacityFilter],
    sortOrder,
  };
}

function upsertCategorySql(category: LandingCategory) {
  const aliasInserts = (category.aliases ?? []).map((path, index) => `
    INSERT INTO "product_categories_legacy_aliases" ("_order", "_parent_id", "id", "path")
    SELECT ${index}, "id", ${q(`legacy-seo-${category.slug}-${index}`)}, ${q(path)}
    FROM "product_categories"
    WHERE "slug"=${q(category.slug)}
    ON CONFLICT ("id") DO UPDATE SET "path"=EXCLUDED."path", "_order"=EXCLUDED."_order";
  `).join('\n');
  const textRows = [
    ...textArrayRows(category.slug, 'defaultCategoryFilters', category.defaultCategoryFilters),
    ...textArrayRows(category.slug, 'defaultCapacityFilters', category.defaultCapacityFilters),
    ...textArrayRows(category.slug, 'defaultBoomTypeFilters', category.defaultBoomTypeFilters),
  ].join('\n');

  return `
    INSERT INTO "product_categories" (
      "slug", "legacy_path", "icon_name", "page_type", "menu_group", "listing_group",
      "show_in_mega_menu", "show_on_home", "show_in_product_filters", "sort_order",
      "seo_meta_title", "seo_meta_description", "seo_canonical_path", "status",
      "published_at", "frontend_surface_status", "frontend_surface_note", "updated_at", "created_at"
    ) VALUES (
      ${q(category.slug)}, ${q(category.legacyPath)}, 'truck-crane', 'seo-landing',
      'legacy-seo', 'Legacy SEO Landing', false, false, false, ${category.sortOrder},
      ${q(`${category.title} - BOB-LIFT`)}, ${q(category.description)}, ${q(category.legacyPath)},
      'published', now(), 'ready', '旧站 SEO 落地页，后台控制默认筛选并直接渲染旧 URL。', now(), now()
    )
    ON CONFLICT ("slug") DO UPDATE SET
      "legacy_path"=EXCLUDED."legacy_path",
      "page_type"=EXCLUDED."page_type",
      "menu_group"=EXCLUDED."menu_group",
      "listing_group"=EXCLUDED."listing_group",
      "show_in_mega_menu"=false,
      "show_on_home"=false,
      "show_in_product_filters"=false,
      "sort_order"=EXCLUDED."sort_order",
      "seo_meta_title"=EXCLUDED."seo_meta_title",
      "seo_meta_description"=EXCLUDED."seo_meta_description",
      "seo_canonical_path"=EXCLUDED."seo_canonical_path",
      "status"='published',
      "frontend_surface_status"='ready',
      "frontend_surface_note"=EXCLUDED."frontend_surface_note",
      "updated_at"=now();

    INSERT INTO "product_categories_locales" (
      "_parent_id", "_locale", "title", "short_title", "description", "banner_label", "banner_title",
      "seo_meta_title", "seo_meta_description"
    )
    SELECT "id", 'en', ${q(category.title)}, ${q(category.shortTitle)}, ${q(category.description)}, 'BOB-LIFT Product Range',
      ${q(category.title)}, ${q(`${category.title} - BOB-LIFT`)}, ${q(category.description)}
    FROM "product_categories"
    WHERE "slug"=${q(category.slug)}
    ON CONFLICT ("_locale", "_parent_id") DO UPDATE SET
      "title"=EXCLUDED."title",
      "short_title"=EXCLUDED."short_title",
      "description"=EXCLUDED."description",
      "banner_label"=EXCLUDED."banner_label",
      "banner_title"=EXCLUDED."banner_title",
      "seo_meta_title"=EXCLUDED."seo_meta_title",
      "seo_meta_description"=EXCLUDED."seo_meta_description";

    DELETE FROM "product_categories_texts"
    WHERE "parent_id"=(SELECT "id" FROM "product_categories" WHERE "slug"=${q(category.slug)})
      AND "path" IN ('defaultCategoryFilters', 'defaultCapacityFilters', 'defaultBoomTypeFilters');
    ${textRows}
    ${aliasInserts}
  `;
}

function textArrayRows(slug: string, path: string, values: string[] = []) {
  return values.map((value, index) => `
    INSERT INTO "product_categories_texts" ("order", "parent_id", "path", "text")
    SELECT ${index}, "id", ${q(path)}, ${q(value)}
    FROM "product_categories"
    WHERE "slug"=${q(slug)};
  `);
}

function updateMiniDumperSeoSql() {
  const title = 'China mini dumper manufacturers, mini dumper suppliers, mini dumper wholesaler';
  return `
    UPDATE "product_categories"
    SET "seo_meta_title"=${q(`${title} - BOB-LIFT`)}, "updated_at"=now()
    WHERE "slug"='mini-dumpers';

    UPDATE "product_categories_locales"
    SET "banner_title"=${q(title)}, "seo_meta_title"=${q(`${title} - BOB-LIFT`)}
    WHERE "_parent_id"=(SELECT "id" FROM "product_categories" WHERE "slug"='mini-dumpers')
      AND "_locale"='en';
  `;
}

function updateAerialWorkPlatformSeoSql() {
  return `
    UPDATE "products"
    SET
      "seo_meta_title"='Truck Mounted 18-Meter Aerial Work Platform - Buy Product on BOB-LIFT',
      "seo_meta_description"='Buy Product on BOB-LIFT: truck mounted 18-meter aerial work platform for elevated maintenance and project work.',
      "updated_at"=now()
    WHERE "slug"='truck-mounted-18-meter-aerial-work-platform';
  `;
}

function q(value: string) {
  return `'${value.replaceAll("'", "''")}'`;
}
