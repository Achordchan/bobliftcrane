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

const batchId = process.argv[2] || latestProductBatchId() || "legacy-20260528-spec-refresh";
const titleByLocale = {
  en: `Legacy product import: ${batchId}`,
  zh: `旧站产品导入批次：${batchId}`,
  es: `Importación de productos del sitio anterior: ${batchId}`,
  ar: `دفعة استيراد منتجات الموقع السابق: ${batchId}`,
};
const checklistByLocale = {
  en: "Check every product against the original page and drawing images before changing the product status to verified-from-source. Preserve already verified parameters when rerunning imports.",
  zh: "把产品状态改为“已按来源核对”前，必须逐项对照旧站原页面和图纸/参数图；重跑导入时保留已核对参数。",
  es: "Antes de marcar el producto como verificado, revise cada parámetro con la página original y las imágenes técnicas. Mantenga los parámetros ya verificados al reimportar.",
  ar: "قبل اعتماد المنتج، راجع كل معلمة مع صفحة المصدر والصور الفنية. عند إعادة الاستيراد يجب الحفاظ على المعلمات المعتمدة.",
};
const notesByLocale = {
  en: "Created from the current local database after crawling bobliftcrane.com. Products without parseable specification tables remain in needs-review.",
  zh: "基于当前本地数据库和 bobliftcrane.com 抓取结果生成；旧站无法解析到规格表的产品保留为待复核。",
  es: "Creado desde la base local tras rastrear bobliftcrane.com. Los productos sin tabla técnica analizable quedan pendientes de revisión.",
  ar: "تم إنشاؤه من قاعدة البيانات المحلية بعد زحف bobliftcrane.com. تبقى المنتجات التي لا تحتوي على جدول مواصفات قابل للتحليل قيد المراجعة.",
};

const sql = `
WITH stats AS (
  SELECT
    count(*)::int AS product_count,
    count(*)::int AS imported_count,
    count(*) FILTER (WHERE parameter_review_status = 'needs-review')::int AS needs_review_count,
    count(*) FILTER (WHERE parameter_review_status = 'in-review')::int AS in_review_count,
    count(*) FILTER (WHERE parameter_review_status = 'verified-from-source')::int AS verified_count,
    min(created_at) AS started_at,
    max(updated_at) AS completed_at
  FROM "products"
  WHERE "import_batch_id" = ${q(batchId)}
),
upsert AS (
  INSERT INTO "product_import_batches" (
    "batch_id",
    "status",
    "source_type",
    "source_base_url",
    "sitemap_url",
    "started_at",
    "completed_at",
    "product_count",
    "imported_count",
    "skipped_count",
    "failed_count",
    "needs_review_count",
    "in_review_count",
    "verified_count",
    "overwrite_policy",
    "updated_at",
    "created_at"
  )
  SELECT
    ${q(batchId)},
    CASE WHEN needs_review_count > 0 OR in_review_count > 0 THEN 'in-review'::enum_product_import_batches_status ELSE 'completed'::enum_product_import_batches_status END,
    'legacy-site',
    'https://www.bobliftcrane.com',
    'https://www.bobliftcrane.com/sitemap.xml',
    started_at,
    completed_at,
    product_count,
    imported_count,
    0,
    0,
    needs_review_count,
    in_review_count,
    verified_count,
    'preserve-verified',
    now(),
    now()
  FROM stats
  ON CONFLICT ("batch_id") DO UPDATE SET
    "status" = EXCLUDED."status",
    "product_count" = EXCLUDED."product_count",
    "imported_count" = EXCLUDED."imported_count",
    "needs_review_count" = EXCLUDED."needs_review_count",
    "in_review_count" = EXCLUDED."in_review_count",
    "verified_count" = EXCLUDED."verified_count",
    "completed_at" = EXCLUDED."completed_at",
    "updated_at" = now()
  RETURNING "id"
)
${Object.keys(titleByLocale).map((locale) => `INSERT INTO "product_import_batches_locales" ("title", "review_checklist", "notes", "_locale", "_parent_id")
SELECT ${q(titleByLocale[locale])}, ${q(checklistByLocale[locale])}, ${q(notesByLocale[locale])}, ${q(locale)}, "id" FROM "product_import_batches" WHERE "batch_id" = ${q(batchId)}
ON CONFLICT ("_locale", "_parent_id") DO UPDATE SET
  "title" = EXCLUDED."title",
  "review_checklist" = EXCLUDED."review_checklist",
  "notes" = EXCLUDED."notes";`).join("\n")}
DELETE FROM "product_import_batches_rels"
WHERE "parent_id" = (SELECT "id" FROM "product_import_batches" WHERE "batch_id" = ${q(batchId)})
  AND "path" = 'products';
INSERT INTO "product_import_batches_rels" ("order", "parent_id", "path", "products_id")
SELECT row_number() OVER (ORDER BY p."sort_order", p."id") - 1, b."id", 'products', p."id"
FROM "product_import_batches" b
JOIN "products" p ON p."import_batch_id" = b."batch_id"
WHERE b."batch_id" = ${q(batchId)};

DELETE FROM "product_import_batches_failure_logs"
WHERE "_parent_id" = (SELECT "id" FROM "product_import_batches" WHERE "batch_id" = ${q(batchId)});
INSERT INTO "product_import_batches_failure_logs" ("_order", "_parent_id", "id", "source_url", "product_title", "error")
SELECT
  row_number() OVER (ORDER BY p."slug") - 1,
  b."id",
  'review-' || p."slug",
  p."source_url",
  COALESCE(pl."title", p."slug"),
  CASE
    WHEN count(ps."id") = 0 THEN '未解析到可结构化规格参数表；禁止自动编造参数，需人工从旧站页面或厂家资料复核后填写。图纸只作为资料记录。'
    ELSE '产品仍处于待复核/退回状态；需人工对照旧站来源后再改为已按来源核对。图纸不再作为复核阻塞。'
  END
FROM "product_import_batches" b
JOIN "products" p ON p."import_batch_id" = b."batch_id"
LEFT JOIN "products_locales" pl ON pl."_parent_id" = p."id" AND pl."_locale" = 'en'
LEFT JOIN "products_specs" ps ON ps."_parent_id" = p."id"
WHERE b."batch_id" = ${q(batchId)}
  AND p."parameter_review_status" IN ('needs-review', 'changes-required')
GROUP BY b."id", p."slug", p."source_url", pl."title", p."parameter_review_status";
`;

execFileSync("psql", [databaseUrl, "-v", "ON_ERROR_STOP=1", "-q"], {
  input: sql,
  stdio: ["pipe", "inherit", "inherit"],
});

console.log(`已同步产品导入批次：${batchId}`);

function q(value) {
  if (value === null || typeof value === "undefined") return "NULL";
  return `'${String(value).replaceAll("'", "''")}'`;
}

function latestProductBatchId() {
  const result = execFileSync("psql", [
    databaseUrl,
    "-At",
    "-v",
    "ON_ERROR_STOP=1",
    "-c",
    `SELECT "import_batch_id"
     FROM "products"
     WHERE "import_batch_id" IS NOT NULL AND "import_batch_id" <> ''
     GROUP BY "import_batch_id"
     ORDER BY max("updated_at") DESC NULLS LAST, count(*) DESC
     LIMIT 1;`,
  ], { encoding: "utf8" });
  return result.trim();
}

function loadDotEnv(path) {
  if (!existsSync(path)) return;
  const contents = readFileSync(path, "utf8");
  for (const line of contents.split(/\r?\n/)) {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith("#") || !trimmed.includes("=")) continue;
    const [key, ...rest] = trimmed.split("=");
    if (process.env[key]) continue;
    process.env[key] = rest.join("=").trim().replace(/^['"]|['"]$/g, "");
  }
}
