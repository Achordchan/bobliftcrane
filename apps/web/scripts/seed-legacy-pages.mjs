#!/usr/bin/env node

import { execFileSync } from "node:child_process";
import { existsSync, readFileSync } from "node:fs";
import { dirname, resolve } from "node:path";
import { fileURLToPath } from "node:url";

const scriptDir = dirname(fileURLToPath(import.meta.url));
const appDir = resolve(scriptDir, "..");
const repoDir = resolve(appDir, "../..");

loadDotEnv(resolve(appDir, ".env.local"));
loadDotEnv(resolve(repoDir, ".env"));

const databaseUrl = process.env.DATABASE_URL;
if (!databaseUrl) {
  console.error("缺少 DATABASE_URL。请先配置 apps/web/.env.local。");
  process.exit(1);
}

const pages = [
  {
    slug: "application",
    legacyPath: "/application",
    aliases: ["/application.html", "/Application.html"],
    pageType: "application",
    heroImagePath: "/images/construction-scene.jpg",
    showInNavigation: true,
    sortOrder: 30,
    title: "Application",
    heroLabel: "Application",
    heroTitle: "BOB-LIFT cranes for real working conditions.",
    heroDescription: "Match truck-mounted cranes, marine cranes, pickup cranes and compact lifting equipment to construction, logistics, port, marine and municipal scenarios.",
    metaTitle: "Application | BOB-LIFT",
    metaDescription: "BOB-LIFT crane application scenarios for construction, logistics, port operations, marine work and municipal engineering.",
    blocks: [
      {
        label: "Construction & Logistics",
        title: "Lifting support for construction and transport fleets.",
        text: "BOB-LIFT truck-mounted cranes help contractors and fleet buyers load, unload and reposition materials where working radius, chassis matching and stable hydraulic control matter.",
        imagePath: "/images/oldsite/products/10-ton-telescopic-boom-truck-mounted-crane.png",
        ctaLabel: "View Truck Cranes",
        ctaHref: "/products/category/telescopic-boom-truck-mounted-cranes",
      },
      {
        label: "Marine & Port",
        title: "Deck-side and dock-side lifting for marine projects.",
        text: "Marine crane selections focus on installation space, corrosion-conscious configuration, rotation range and reliable lifting around vessels, docks and water-operation sites.",
        imagePath: "/images/marine-scene.jpg",
        ctaLabel: "View Marine Cranes",
        ctaHref: "/products/category/knuckle-boom-marine-cranes",
      },
      {
        label: "Utility & Compact Work",
        title: "Smaller lifting solutions for tighter jobsites.",
        text: "Pickup cranes, forklift fly jib cranes, mini dumpers and accessories cover utility maintenance, warehouse handling, farm support and compact material movement tasks.",
        imagePath: "/images/aerial-platform.jpg",
        ctaLabel: "View Compact Products",
        ctaHref: "/products/category/pickup-cranes",
      },
    ],
  },
  {
    slug: "service",
    legacyPath: "/service",
    aliases: ["/service.html", "/Service.html"],
    pageType: "service",
    heroImagePath: "/images/factory-workshop.jpg",
    showInNavigation: true,
    sortOrder: 35,
    title: "Service",
    heroLabel: "Service",
    heroTitle: "Engineering, export and after-sales support in one workflow.",
    heroDescription: "From model selection and chassis matching to quotation, production, shipment documents and after-sales response, BOB-LIFT keeps service steps visible for buyers.",
    metaTitle: "Service | BOB-LIFT",
    metaDescription: "BOB-LIFT service workflow for model selection, quotation, production coordination, export documents and after-sales support.",
    blocks: [
      {
        label: "Before Quote",
        title: "Requirement confirmation before price matching.",
        text: "Share lifting capacity, working radius, truck chassis, quantity, destination country and deadline. The team can then match a practical crane configuration instead of giving a generic quote.",
        imagePath: "/images/global-network-blue.jpg",
        ctaLabel: "Send Requirement",
        ctaHref: "/message.html",
      },
      {
        label: "Production & Delivery",
        title: "Visible production coordination for export orders.",
        text: "BOB-LIFT coordinates technical confirmation, manufacturing, inspection, packaging and export documentation so international buyers can track what is being prepared.",
        imagePath: "/images/oldsite/news/enter-the-bob-lift-factory-with-professional-manufacturing.jpg",
        ctaLabel: "Contact Team",
        ctaHref: "/contactus.html",
      },
      {
        label: "After Sales",
        title: "Support built around the product and working site.",
        text: "After-sales communication keeps model information, spare parts, installation context and service notes connected with the original inquiry and product selection.",
        imagePath: "/images/cta-lift-photo.png",
        ctaLabel: "Request Support",
        ctaHref: "/contactus.html",
      },
    ],
  },
  {
    slug: "download",
    legacyPath: "/download.html",
    aliases: ["/download"],
    pageType: "custom",
    heroImagePath: "/images/telescopic-banner.jpg",
    showInNavigation: false,
    sortOrder: 80,
    title: "Download",
    heroLabel: "Download",
    heroTitle: "Download product information for model comparison.",
    heroDescription: "Use product pages and inquiry channels to request catalogs, technical parameters and model-selection support from BOB-LIFT.",
    metaTitle: "Download | BOB-LIFT",
    metaDescription: "BOB-LIFT download page for product catalogs, parameters and model-selection documents.",
    blocks: [
      {
        label: "Catalog Request",
        title: "Request the right document by product model.",
        text: "Legacy product parameters are now connected to product detail pages. Choose a product model first, then request the catalog or technical file through the inquiry form.",
        imagePath: "/images/oldsite/products/10-ton-telescopic-boom-truck-mounted-crane.png",
        ctaLabel: "View Products",
        ctaHref: "/products",
      },
    ],
  },
  {
    slug: "privacy-policy",
    legacyPath: "/privacy-policy.html",
    aliases: ["/privacy-policy"],
    pageType: "custom",
    heroImagePath: "/images/global-network-blue.jpg",
    showInNavigation: false,
    sortOrder: 120,
    title: "Privacy Policy",
    heroLabel: "Privacy Policy",
    heroTitle: "Privacy Policy",
    heroDescription: "This page explains how inquiry and contact information is collected and used when visitors communicate with BOB-LIFT.",
    metaTitle: "Privacy Policy | BOB-LIFT",
    metaDescription: "BOB-LIFT privacy policy for inquiry, contact and website communication information.",
    blocks: [
      {
        label: "Information We Collect",
        title: "Inquiry information is used for quotation and service communication.",
        text: "When you submit an inquiry, BOB-LIFT may collect your name, email, phone, country, product interest and project details so the team can respond to your request.",
        imagePath: "/images/contact-world-map.png",
        ctaLabel: "Contact BOB-LIFT",
        ctaHref: "/contactus.html",
      },
      {
        label: "Your Request",
        title: "You can contact the team about your information.",
        text: "For access, correction or deletion requests, use the contact information on the website and include the email address used in the original inquiry.",
        imagePath: "/images/factory-workshop.jpg",
        ctaLabel: "Send Inquiry",
        ctaHref: "/message.html",
      },
    ],
  },
];

const sql = pages.map(pageSql).join("\n\n");

execFileSync("psql", [databaseUrl, "-v", "ON_ERROR_STOP=1", "-q"], {
  input: sql,
  stdio: ["pipe", "inherit", "inherit"],
});

console.log(`已初始化独立页面：${pages.map((page) => page.legacyPath).join(", ")}`);

function pageSql(page) {
  const pageId = `(SELECT "id" FROM "pages" WHERE "slug"=${sqlValue(page.slug)})`;
  return `
INSERT INTO "pages" (
  "slug", "legacy_path", "page_type", "hero_image_path", "seo_canonical_path", "status", "show_in_navigation", "sort_order", "published_at", "updated_at", "created_at"
) VALUES (
  ${sqlValue(page.slug)}, ${sqlValue(page.legacyPath)}, ${sqlValue(page.pageType)}, ${sqlValue(page.heroImagePath)}, ${sqlValue(page.legacyPath)}, 'published', ${sqlValue(page.showInNavigation)}, ${sqlValue(page.sortOrder)}, now(), now(), now()
)
ON CONFLICT ("slug") DO UPDATE SET
  "legacy_path"=EXCLUDED."legacy_path",
  "page_type"=EXCLUDED."page_type",
  "hero_image_path"=EXCLUDED."hero_image_path",
  "seo_canonical_path"=EXCLUDED."seo_canonical_path",
  "status"=EXCLUDED."status",
  "show_in_navigation"=EXCLUDED."show_in_navigation",
  "sort_order"=EXCLUDED."sort_order",
  "published_at"=COALESCE("pages"."published_at", now()),
  "updated_at"=now();

DELETE FROM "pages_locales" WHERE "_parent_id"=${pageId} AND "_locale"='en';
INSERT INTO "pages_locales" ("title", "hero_label", "hero_title", "hero_description", "seo_meta_title", "seo_meta_description", "_locale", "_parent_id")
SELECT ${sqlValue(page.title)}, ${sqlValue(page.heroLabel)}, ${sqlValue(page.heroTitle)}, ${sqlValue(page.heroDescription)}, ${sqlValue(page.metaTitle)}, ${sqlValue(page.metaDescription)}, 'en', "id"
FROM "pages" WHERE "slug"=${sqlValue(page.slug)};

DELETE FROM "pages_legacy_aliases" WHERE "_parent_id"=${pageId};
${page.aliases.map((alias, index) => `INSERT INTO "pages_legacy_aliases" ("_order", "_parent_id", "id", "path") SELECT ${index}, "id", ${sqlValue(`seed-page-${page.slug}-alias-${index}`)}, ${sqlValue(alias)} FROM "pages" WHERE "slug"=${sqlValue(page.slug)};`).join("\n")}

DELETE FROM "pages_content_blocks" WHERE "_parent_id"=${pageId};
${page.blocks.map((block, index) => blockSql(page, block, index)).join("\n")}
`;
}

function blockSql(page, block, index) {
  const blockId = `seed-page-${page.slug}-block-${index}`;
  return `
INSERT INTO "pages_content_blocks" ("_order", "_parent_id", "id", "image_path", "cta_href")
SELECT ${index}, "id", ${sqlValue(blockId)}, ${sqlValue(block.imagePath)}, ${sqlValue(block.ctaHref)} FROM "pages" WHERE "slug"=${sqlValue(page.slug)};
INSERT INTO "pages_content_blocks_locales" ("label", "title", "text", "cta_label", "_locale", "_parent_id")
VALUES (${sqlValue(block.label)}, ${sqlValue(block.title)}, ${sqlValue(block.text)}, ${sqlValue(block.ctaLabel)}, 'en', ${sqlValue(blockId)});
`;
}

function loadDotEnv(filePath) {
  if (!existsSync(filePath)) return;
  const content = readFileSync(filePath, "utf8");
  for (const line of content.split(/\r?\n/)) {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith("#")) continue;
    const match = trimmed.match(/^([A-Za-z_][A-Za-z0-9_]*)=(.*)$/);
    if (!match) continue;
    const [, key, rawValue] = match;
    if (process.env[key]) continue;
    process.env[key] = rawValue.replace(/^['"]|['"]$/g, "");
  }
}

function sqlValue(value) {
  if (value === null || value === undefined) return "NULL";
  if (typeof value === "number") return Number.isFinite(value) ? String(value) : "NULL";
  if (typeof value === "boolean") return value ? "true" : "false";
  return `'${String(value).replace(/'/g, "''")}'`;
}
