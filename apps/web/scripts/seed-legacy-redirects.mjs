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

const directRenderLegacyPaths = new Set([
  "/about.html",
  "/about-371153.html",
  "/authority.html",
  "/message.html",
  "/faqlist.html",
  "/article-inquire.html",
  "/contact-us",
  "/contact-us/",
  "/contact-us.html",
  "/contactus.html",
  "/contact.html",
  "/Company-News.html",
  "/Industry-News.html",
  "/news.html",
  "/Company-News-ic236357.html",
  "/Industry-News-ic226357.html",
  "/products.html",
  "/productcategory.html",
  "/keywords.html",
  "/keyword-index.html",
  "/gallery.html",
  "/videos.html",
  "/video-detail.html",
  "/download.html",
  "/download",
  "/privacy-policy.html",
  "/privacy-policy",
  "/application",
  "/application.html",
  "/Application.html",
  "/service",
  "/service.html",
  "/Service.html",
]);

const auditRedirectRowsSql = buildAuditRedirectRowsSql(resolve(repoDir, "audit/url-preservation-map.csv"));

const sql = `
WITH audit_redirect_rows(source_path, target_path, notes, priority) AS (
  ${auditRedirectRowsSql}
), manual_redirect_rows(source_path, target_path, notes, priority) AS (
  VALUES
    ('/products/mini-dumper.html', '/products/category/mini-dumpers', 'URL 审计表旧站产品分类路径', 20),
    ('/products/Forklift-Fly-Jib-Crane.html', '/products/category/forklift-fly-jib-cranes', 'URL 审计表旧站产品分类路径', 20),
    ('/products/Telescopic-Boom-Crane.html', '/products/category/telescopic-boom-truck-mounted-cranes', 'URL 审计表旧站产品分类路径', 20),
    ('/products/Truck-Mounted-Crane.html', '/products/category/telescopic-boom-truck-mounted-cranes', 'URL 审计表旧站产品分类路径', 20),
    ('/products/Boom-Truck-Crane.html', '/products/category/telescopic-boom-truck-mounted-cranes', 'URL 审计表旧站产品分类路径', 20),
    ('/products/Aerial-Work-Platform.html', '/products/category/aerial-work-platform', 'URL 审计表旧站产品分类路径', 20),
    ('/products/Heavy-duty-Crane.html', '/products', 'URL 审计表旧站 SEO 聚合路径', 20),
    ('/products/Hydraulic-Crane.html', '/products', 'URL 审计表旧站 SEO 聚合路径', 20),
    ('/products/Industrial-Crane.html', '/products', 'URL 审计表旧站 SEO 聚合路径', 20),
    ('/products/Lifting-Equipment.html', '/products', 'URL 审计表旧站 SEO 聚合路径', 20),
    ('/products/Compact-excavator.html', '/products', 'URL 审计表旧站 SEO 聚合路径', 20),
    ('/products/Lightweight-construction-equipment.html', '/products', 'URL 审计表旧站 SEO 聚合路径', 20),
    ('/products/Small-Scale-Excavator.html', '/products', 'URL 审计表旧站 SEO 聚合路径', 20),
    ('/products/Efficient-Earthmover.html', '/products', 'URL 审计表旧站 SEO 聚合路径', 20)
), content_paths AS (
  SELECT legacy_path AS path FROM products WHERE status = 'published' AND legacy_path IS NOT NULL AND legacy_path <> ''
  UNION
  SELECT a.path FROM products p JOIN products_legacy_aliases a ON a._parent_id = p.id WHERE p.status = 'published' AND a.path IS NOT NULL AND a.path <> ''
  UNION
  SELECT legacy_path AS path FROM product_categories WHERE status = 'published' AND legacy_path IS NOT NULL AND legacy_path <> ''
  UNION
  SELECT a.path FROM product_categories c JOIN product_categories_legacy_aliases a ON a._parent_id = c.id WHERE c.status = 'published' AND a.path IS NOT NULL AND a.path <> ''
  UNION
  SELECT legacy_path AS path FROM articles WHERE status = 'published' AND legacy_path IS NOT NULL AND legacy_path <> ''
  UNION
  SELECT a.path FROM articles ar JOIN articles_legacy_aliases a ON a._parent_id = ar.id WHERE ar.status = 'published' AND a.path IS NOT NULL AND a.path <> ''
  UNION
  SELECT legacy_path AS path FROM cases WHERE status = 'published' AND legacy_path IS NOT NULL AND legacy_path <> ''
  UNION
  SELECT a.path FROM cases ca JOIN cases_legacy_aliases a ON a._parent_id = ca.id WHERE ca.status = 'published' AND a.path IS NOT NULL AND a.path <> ''
), redirect_rows AS (
  SELECT source_path, target_path, notes, priority FROM audit_redirect_rows
  UNION ALL
  SELECT source_path, target_path, notes, priority FROM manual_redirect_rows
), normalized AS (
  SELECT DISTINCT ON (source_path)
    source_path,
    target_path,
    notes
  FROM redirect_rows
  WHERE source_path IS NOT NULL
    AND source_path <> ''
    AND source_path <> target_path
    AND NOT EXISTS (SELECT 1 FROM content_paths c WHERE c.path = redirect_rows.source_path)
  ORDER BY source_path, priority, target_path
)
INSERT INTO redirects (source_path, target_path, redirect_type, status, updated_at, created_at)
SELECT source_path, target_path, 'permanent', 'published', now(), now()
FROM normalized
ON CONFLICT (source_path) DO UPDATE SET
  target_path = EXCLUDED.target_path,
  redirect_type = EXCLUDED.redirect_type,
  status = EXCLUDED.status,
  updated_at = now();

WITH content_paths AS (
  SELECT legacy_path AS path FROM products WHERE status = 'published' AND legacy_path IS NOT NULL AND legacy_path <> ''
  UNION
  SELECT a.path FROM products p JOIN products_legacy_aliases a ON a._parent_id = p.id WHERE p.status = 'published' AND a.path IS NOT NULL AND a.path <> ''
  UNION
  SELECT legacy_path AS path FROM product_categories WHERE status = 'published' AND legacy_path IS NOT NULL AND legacy_path <> ''
  UNION
  SELECT a.path FROM product_categories c JOIN product_categories_legacy_aliases a ON a._parent_id = c.id WHERE c.status = 'published' AND a.path IS NOT NULL AND a.path <> ''
  UNION
  SELECT legacy_path AS path FROM articles WHERE status = 'published' AND legacy_path IS NOT NULL AND legacy_path <> ''
  UNION
  SELECT a.path FROM articles ar JOIN articles_legacy_aliases a ON a._parent_id = ar.id WHERE ar.status = 'published' AND a.path IS NOT NULL AND a.path <> ''
  UNION
  SELECT legacy_path AS path FROM cases WHERE status = 'published' AND legacy_path IS NOT NULL AND legacy_path <> ''
  UNION
  SELECT a.path FROM cases ca JOIN cases_legacy_aliases a ON a._parent_id = ca.id WHERE ca.status = 'published' AND a.path IS NOT NULL AND a.path <> ''
)
UPDATE redirects r
SET status = 'draft', updated_at = now()
FROM content_paths c
WHERE r.source_path = c.path
  AND r.status = 'published';
`;

execFileSync("psql", [databaseUrl, "-v", "ON_ERROR_STOP=1", "-q"], {
  input: sql,
  stdio: ["pipe", "inherit", "inherit"],
});

const count = execFileSync("psql", [databaseUrl, "-Atc", "SELECT count(*) FROM redirects WHERE status = 'published';"], { encoding: "utf8" }).trim();
console.log(`已同步旧站跳转：redirects=${count}。`);

function buildAuditRedirectRowsSql(csvPath) {
  if (!existsSync(csvPath)) {
    return "SELECT NULL::text AS source_path, NULL::text AS target_path, NULL::text AS notes, NULL::int AS priority WHERE false";
  }

  const rows = readCsv(csvPath)
    .map((row) => {
      const targetPath = targetPathForAuditRow(row);
      if (!targetPath || row.path === "/" || row.path === targetPath) return null;

      return `(${sqlString(row.path)}, ${sqlString(targetPath)}, ${sqlString(`URL 审计表兜底跳转：${row.type || "legacy"}`)}, 90)`;
    })
    .filter(Boolean);

  return rows.length > 0
    ? `VALUES\n    ${rows.join(",\n    ")}`
    : "SELECT NULL::text AS source_path, NULL::text AS target_path, NULL::text AS notes, NULL::int AS priority WHERE false";
}

function targetPathForAuditRow(row) {
  const path = normalizePath(row.path);
  if (!path || path === "/") return null;
  if (directRenderLegacyPaths.has(path)) return null;

  const exactTargets = {
    "/about.html": "/about",
    "/about-371153.html": "/about",
    "/authority.html": "/about",
    "/message.html": "/quote",
    "/faqlist.html": "/quote",
    "/article-inquire.html": "/quote",
    "/contact-us": "/quote",
    "/contact-us/": "/quote",
    "/contactus.html": "/contact",
    "/contact.html": "/contact",
    "/Company-News.html": "/news",
    "/Industry-News.html": "/news",
    "/news.html": "/news",
    "/Company-News-ic236357.html": "/news",
    "/Industry-News-ic226357.html": "/news",
    "/products.html": "/products",
    "/productcategory.html": "/products",
    "/download.html": "/products",
    "/keywords.html": "/products",
    "/keyword-index.html": "/products",
    "/gallery.html": "/products",
    "/videos.html": "/products",
    "/video-detail.html": "/products",
    "/privacy-policy.html": "/about",
    "/Products-Truck-Mounted-Cranes.html": "/products",
    "/Products-Marine-Cranes.html": "/products",
    "/Products-Pickup-Cranes.html": "/products/category/pickup-cranes",
    "/Products-Forklift-Fly-Jib-Cranes.html": "/products/category/forklift-fly-jib-cranes",
    "/Products-Crane-by-Ton.html": "/products",
    "/Products-Mini-Dumpers.html": "/products/category/mini-dumpers",
    "/Products-Excavator.html": "/products",
    "/Products-Excavator-by-Ton.html": "/products",
    "/Products-Other-Crane.html": "/products/category/other-cranes",
    "/truck-mounted-crane": "/products",
    "/marine-crane": "/products",
    "/crane-by-ton": "/products",
    "/1-ton-crane": "/products",
    "/2-ton-crane": "/products",
    "/3-ton-crane": "/products",
    "/4-ton-crane": "/products",
    "/5-ton-crane": "/products",
    "/6-ton-crane": "/products",
    "/8-ton-crane": "/products",
    "/10-ton-crane": "/products",
    "/12-ton-crane": "/products",
    "/16-ton-crane": "/products",
    "/20-ton-crane": "/products",
    "/25-ton-crane": "/products",
    "/knuckle-boom-truck-mounted-crane": "/products/category/knuckle-boom-truck-mounted-cranes",
    "/telescopic-boom-truck-mounted-crane": "/products/category/telescopic-boom-truck-mounted-cranes",
    "/knuckle-boom-marine-crane": "/products/category/knuckle-boom-marine-cranes",
    "/telescopic-boom-marine-crane": "/products/category/telescopic-boom-marine-cranes",
    "/pickup-crane": "/products/category/pickup-cranes",
    "/forklift-fly-jib-crane": "/products/category/forklift-fly-jib-cranes",
    "/mini-dumper": "/products/category/mini-dumpers",
    "/other-crane.html": "/products/category/other-cranes",
    "/timber-crane.html": "/products/category/timber-cranes",
    "/aerial-work-platform.html": "/products/category/aerial-work-platform",
    "/Tail-Lift-pl41102097.html": "/products/category/tail-lift",
    "/Spider-Crane-pl41706186.html": "/products/category/spider-cranes",
    "/Knuckle-Boom-Truck-Mounted-Cranes.html": "/products/category/knuckle-boom-truck-mounted-cranes",
    "/Telescopic-Boom-Truck-Mounted-Cranes.html": "/products/category/telescopic-boom-truck-mounted-cranes",
    "/Knuckle-Boom-Marine-Cranes.html": "/products/category/knuckle-boom-marine-cranes",
    "/Telescopic-Boom-Marine-Cranes.html": "/products/category/telescopic-boom-marine-cranes",
    "/Medium-Excavator-1.html": "/products",
    "/Mini-Excavator-1.html": "/products",
    "/Timber-Crane-1.html": "/products/category/timber-cranes",
    "/truck-mounted-aerial-work-platform.html": "/products/category/aerial-work-platform",
  };

  if (exactTargets[path]) return exactTargets[path];

  if (path.startsWith("/news/") || row.type === "article" || row.type === "news_index" || row.type === "news_tag") {
    return "/news";
  }

  if (row.type === "product_index" || row.type === "product_category_legacy" || row.type === "seo_category" || row.type === "product_detail") {
    return "/products";
  }

  if (row.type === "support_media_legal") {
    return "/products";
  }

  return null;
}

function readCsv(path) {
  const [headerLine, ...lines] = readFileSync(path, "utf8").trim().split(/\r?\n/);
  const headers = parseCsvLine(headerLine);

  return lines.map((line) => Object.fromEntries(parseCsvLine(line).map((value, index) => [headers[index], value])));
}

function parseCsvLine(line) {
  const values = [];
  let current = "";
  let inQuotes = false;

  for (let index = 0; index < line.length; index += 1) {
    const char = line[index];
    if (char === '"') {
      if (inQuotes && line[index + 1] === '"') {
        current += '"';
        index += 1;
      } else {
        inQuotes = !inQuotes;
      }
    } else if (char === "," && !inQuotes) {
      values.push(current);
      current = "";
    } else {
      current += char;
    }
  }

  values.push(current);
  return values;
}

function normalizePath(path) {
  if (!path) return "";
  return path.startsWith("/") ? path : `/${path}`;
}

function sqlString(value) {
  return `'${String(value).replaceAll("'", "''")}'`;
}

function loadDotEnv(path) {
  if (!existsSync(path)) return;
  const contents = readFileSync(path, "utf8");
  for (const line of contents.split(/\r?\n/)) {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith("#") || !trimmed.includes("=")) continue;
    const index = trimmed.indexOf("=");
    const key = trimmed.slice(0, index).trim();
    if (process.env[key]) continue;
    process.env[key] = trimmed.slice(index + 1).trim().replace(/^["']|["']$/g, "");
  }
}
