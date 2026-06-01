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

const args = parseArgs(process.argv.slice(2));
const csvPath = resolve(repoDir, args.input || "audit/url-preservation-map.csv");
const shouldFetch = args.fetch !== "false" && args.noFetch !== true;
const concurrency = Number(args.concurrency || process.env.LEGACY_ARTICLE_IMPORT_CONCURRENCY || 4);
const limit = args.limit ? Number(args.limit) : null;

if (!existsSync(csvPath)) {
  console.error(`找不到旧站 URL 清单：${csvPath}`);
  process.exit(1);
}

const rows = readCsv(csvPath)
  .filter((row) => row.type === "article" && normalizePath(row.path))
  .slice(0, limit || undefined);

const imported = await mapWithConcurrency(rows, concurrency, importArticleRow);
const okRows = imported.filter(Boolean);

if (okRows.length === 0) {
  console.log("未发现可导入的旧站文章。");
  process.exit(0);
}

const sql = okRows.map(articleSql).join("\n\n");
execFileSync("psql", [databaseUrl, "-v", "ON_ERROR_STOP=1", "-q"], {
  input: sql,
  stdio: ["pipe", "inherit", "inherit"],
  maxBuffer: 128 * 1024 * 1024,
});

const count = execFileSync("psql", [databaseUrl, "-Atc", "SELECT count(*) FROM articles WHERE status = 'published';"], { encoding: "utf8" }).trim();
console.log(`已导入旧站文章：imported=${okRows.length}, publishedArticles=${count}。`);

async function importArticleRow(row, index) {
  const sourceUrl = row.old_url || new URL(normalizePath(row.path), "https://www.bobliftcrane.com").toString();
  const fallbackTitle = cleanLegacyTitle(row.title) || titleFromPath(row.path);
  let detail = null;

  if (shouldFetch) {
    try {
      const response = await fetch(sourceUrl, { redirect: "follow" });
      if (response.ok) {
        detail = extractArticleDetail(await response.text(), sourceUrl);
      } else {
        console.warn(`[legacy-articles] ${sourceUrl} HTTP ${response.status}，使用 URL 清单标题兜底。`);
      }
    } catch (error) {
      console.warn(`[legacy-articles] ${sourceUrl} 抓取失败：${error instanceof Error ? error.message : String(error)}，使用 URL 清单标题兜底。`);
    }
  }

  const title = detail?.title || fallbackTitle;
  if (!title) return null;

  const legacyPath = normalizePath(row.path);
  const slug = slugify(title) || slugify(legacyPath.replace(/\.html$/i, ""));
  const contentHtml = detail?.contentHtml || fallbackArticleHtml(title, sourceUrl);
  const excerpt = truncateText(detail?.excerpt || textFromHtml(contentHtml), 230) || `Legacy BOB-LIFT article preserved from ${legacyPath}.`;
  const publishedAt = detail?.publishedAt || "2026-05-01";
  const coverImagePath = detail?.coverImagePath || "/images/oldsite/news/enter-the-bob-lift-factory-with-professional-manufacturing.jpg";

  console.log(`[${index + 1}/${rows.length}] ${legacyPath} -> ${title}`);

  return {
    slug,
    legacyPath,
    category: classifyArticle(title),
    title,
    excerpt,
    contentHtml,
    coverImagePath,
    publishedAt: `${publishedAt}T00:00:00.000Z`,
    seoTitle: row.title?.trim() || `${title} - BOB-LIFT`,
    seoDescription: excerpt,
  };
}

function extractArticleDetail(html, sourceUrl) {
  const title = decodeEntities(stripTags(matchFirst(html, /<h1[^>]*>([\s\S]*?)<\/h1>/i)) || cleanLegacyTitle(matchFirst(html, /<title[^>]*>([\s\S]*?)<\/title>/i)));
  const publishedAt = matchFirst(html, /Publish\s*Time:\s*(\d{4}-\d{2}-\d{2})/i) || matchFirst(html, /(\d{4}-\d{2}-\d{2})/i);
  const body = matchFirst(html, /<div\s+class=["'][^"']*\barticledetail-cont\b[^"']*["'][^>]*>([\s\S]*?)<\/div>\s*<ul class=["'][^"']*previousAndNext/i)
    || matchFirst(html, /<div\s+class=["'][^"']*\barticledetail-cont\b[^"']*["'][^>]*>([\s\S]*?)<\/div>\s*<\/div>\s*<\/div>\s*<\/div>/i);
  const contentHtml = sanitizeArticleHtml(body || "", sourceUrl);
  const firstImage = matchFirst(contentHtml, /<img[^>]+src=["']([^"']+)["']/i);

  return {
    title,
    publishedAt,
    contentHtml,
    excerpt: truncateText(textFromHtml(contentHtml), 260),
    coverImagePath: firstImage && isSafeRemoteAsset(firstImage) ? firstImage : null,
  };
}

function sanitizeArticleHtml(html, sourceUrl) {
  const source = new URL(sourceUrl);
  return html
    .replace(/<script[\s\S]*?<\/script>/gi, "")
    .replace(/<style[\s\S]*?<\/style>/gi, "")
    .replace(/<input\b[\s\S]*?>/gi, "")
    .replace(/\s+on[a-z]+=("[^"]*"|'[^']*'|[^\s>]+)/gi, "")
    .replace(/\s+src=(['"])\s*\1/gi, "")
    .replace(/\s+data-src=(['"])(\/\/[^'"]+)\1/gi, " src=$1https:$2$1")
    .replace(/\s+data-original=(['"])(\/\/[^'"]+)\1/gi, " data-original=$1https:$2$1")
    .replace(/\s+src=(['"])\/\//gi, " src=$1https://")
    .replace(/\s+href=(['"])https?:\/\/www\.bobliftcrane\.com([^'"]*)\1/gi, " href=$1$2$1")
    .replace(/\s+href=(['"])\/phoenix\/[^'"]*\1/gi, "")
    .replace(/\s+href=(['"])\s*\1/gi, "")
    .replace(/\s+style=("[^"]*"|'[^']*')/gi, "")
    .replace(/\s+class=("[^"]*"|'[^']*')/gi, "")
    .replace(new RegExp(`https?:\\/\\/${escapeRegExp(source.hostname)}`, "gi"), "")
    .trim();
}

function fallbackArticleHtml(title, sourceUrl) {
  return `<p>${escapeHtml(title)} is a legacy BOB-LIFT article imported from the old website URL.</p><p>Source: <a href="${escapeHtml(sourceUrl)}">${escapeHtml(sourceUrl)}</a></p>`;
}

function articleSql(article) {
  return `WITH existing AS (
  SELECT "id" FROM "articles" WHERE "legacy_path"=${q(article.legacyPath)} LIMIT 1
), inserted AS (
  INSERT INTO "articles" (
    "slug", "legacy_path", "category", "cover_image_path", "seo_meta_title", "seo_meta_description", "seo_canonical_path",
    "status", "published_at", "content_html", "updated_at", "created_at"
  )
  SELECT ${q(article.slug)}, ${q(article.legacyPath)}, ${q(article.category)}, ${q(article.coverImagePath)}, ${q(article.seoTitle)}, ${q(article.seoDescription)}, ${q(article.legacyPath)},
    'published', ${q(article.publishedAt)}, ${q(article.contentHtml)}, now(), now()
  WHERE NOT EXISTS (SELECT 1 FROM existing)
  ON CONFLICT ("slug") DO UPDATE SET
    "legacy_path"=EXCLUDED."legacy_path",
    "category"=EXCLUDED."category",
    "cover_image_path"=EXCLUDED."cover_image_path",
    "seo_meta_title"=EXCLUDED."seo_meta_title",
    "seo_meta_description"=EXCLUDED."seo_meta_description",
    "seo_canonical_path"=EXCLUDED."seo_canonical_path",
    "status"='published',
    "published_at"=EXCLUDED."published_at",
    "content_html"=EXCLUDED."content_html",
    "updated_at"=now()
  RETURNING "id"
), target AS (
  SELECT "id" FROM existing
  UNION ALL
  SELECT "id" FROM inserted
  LIMIT 1
), updated AS (
  UPDATE "articles"
  SET
    "legacy_path"=${q(article.legacyPath)},
    "category"=${q(article.category)},
    "cover_image_path"=${q(article.coverImagePath)},
    "seo_meta_title"=${q(article.seoTitle)},
    "seo_meta_description"=${q(article.seoDescription)},
    "seo_canonical_path"=${q(article.legacyPath)},
    "status"='published',
    "published_at"=${q(article.publishedAt)},
    "content_html"=${q(article.contentHtml)},
    "updated_at"=now()
  WHERE "id"=(SELECT "id" FROM target)
  RETURNING "id"
)
INSERT INTO "articles_locales" ("title", "excerpt", "seo_meta_title", "seo_meta_description", "_locale", "_parent_id")
SELECT ${q(article.title)}, ${q(article.excerpt)}, ${q(article.seoTitle)}, ${q(article.seoDescription)}, 'en', "id" FROM updated
ON CONFLICT ("_locale", "_parent_id") DO UPDATE SET
  "title"=EXCLUDED."title",
  "excerpt"=EXCLUDED."excerpt",
  "seo_meta_title"=EXCLUDED."seo_meta_title",
  "seo_meta_description"=EXCLUDED."seo_meta_description";`;
}

function classifyArticle(title) {
  const text = title.toLowerCase();
  if (/discount|promotion|new product|hot selling|sq\d|fy\d|zm\d|model|sale/.test(text)) return "product-news";
  if (/bob-lift|factory|canton fair|delivered|shipped|customers|global strategy|trust|artistry|moldova|botswana/.test(text)) return "company-news";
  return "industry-insight";
}

async function mapWithConcurrency(items, workerCount, mapper) {
  const results = Array.from({ length: items.length });
  let nextIndex = 0;
  await Promise.all(Array.from({ length: Math.max(1, Math.min(workerCount, items.length)) }, async () => {
    while (nextIndex < items.length) {
      const index = nextIndex;
      nextIndex += 1;
      results[index] = await mapper(items[index], index);
    }
  }));
  return results;
}

function cleanLegacyTitle(value) {
  return decodeEntities(String(value || "").replace(/\s*-\s*BOB-LIFT\s*$/i, "").replace(/\s+/g, " ").trim());
}

function titleFromPath(path) {
  return normalizePath(path).split("/").pop()?.replace(/\.html$/i, "").replace(/[-_]+/g, " ").trim() || "Legacy BOB-LIFT Article";
}

function slugify(value) {
  return String(value || "")
    .toLowerCase()
    .replace(/&/g, " and ")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "")
    .slice(0, 96)
    .replace(/-+$/g, "");
}

function textFromHtml(html) {
  return decodeEntities(stripTags(html)).replace(/\s+/g, " ").trim();
}

function stripTags(html) {
  return String(html || "").replace(/<[^>]+>/g, " ");
}

function truncateText(value, maxLength) {
  const text = String(value || "").replace(/\s+/g, " ").trim();
  return text.length > maxLength ? `${text.slice(0, maxLength - 1).trim()}…` : text;
}

function matchFirst(text, regex) {
  return text.match(regex)?.[1]?.trim() || "";
}

function isSafeRemoteAsset(value) {
  return /^https:\/\//i.test(value) || value.startsWith("/");
}

function normalizePath(path) {
  if (!path) return "";
  const trimmed = String(path).trim();
  return trimmed.startsWith("/") ? trimmed : `/${trimmed}`;
}

function readCsv(path) {
  const [headerLine, ...lines] = readFileSync(path, "utf8").trim().split(/\r?\n/);
  const headers = parseCsvLine(headerLine);
  return lines.filter(Boolean).map((line) => Object.fromEntries(parseCsvLine(line).map((value, index) => [headers[index], value])));
}

function parseCsvLine(line) {
  const values = [];
  let current = "";
  let quoted = false;
  for (let index = 0; index < line.length; index += 1) {
    const char = line[index];
    if (char === '"') {
      if (quoted && line[index + 1] === '"') {
        current += '"';
        index += 1;
      } else {
        quoted = !quoted;
      }
    } else if (char === "," && !quoted) {
      values.push(current);
      current = "";
    } else {
      current += char;
    }
  }
  values.push(current);
  return values;
}

function decodeEntities(value) {
  return String(value || "")
    .replace(/&nbsp;/gi, " ")
    .replace(/&amp;/gi, "&")
    .replace(/&quot;/gi, '"')
    .replace(/&#39;/gi, "'")
    .replace(/&lt;/gi, "<")
    .replace(/&gt;/gi, ">")
    .replace(/&#(\d+);/g, (_, code) => String.fromCharCode(Number(code)));
}

function escapeHtml(value) {
  return String(value || "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;");
}

function escapeRegExp(value) {
  return value.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}

function q(value) {
  if (value === null || typeof value === "undefined") return "NULL";
  return `'${String(value).replaceAll("'", "''")}'`;
}

function parseArgs(argv) {
  const output = {};
  for (let index = 0; index < argv.length; index += 1) {
    const arg = argv[index];
    if (!arg.startsWith("--")) continue;
    const equalsIndex = arg.indexOf("=");
    if (equalsIndex > -1) {
      output[toCamel(arg.slice(2, equalsIndex))] = arg.slice(equalsIndex + 1);
    } else {
      const key = toCamel(arg.slice(2));
      const next = argv[index + 1];
      if (next && !next.startsWith("--")) {
        output[key] = next;
        index += 1;
      } else {
        output[key] = true;
      }
    }
  }
  return output;
}

function toCamel(value) {
  return value.replace(/-([a-z])/g, (_, letter) => letter.toUpperCase());
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
