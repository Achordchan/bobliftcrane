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

const sql = [
  seedSiteNavigation(),
  seedProductMenuGroups(),
  seedLanguageOptions(),
  seedFooterLinks(),
  seedCategoryLocales(),
  hideEmptyDuplicateCategories(),
].join("\n\n");

execFileSync("psql", [databaseUrl, "-v", "ON_ERROR_STOP=1", "-q"], {
  input: sql,
  stdio: ["pipe", "inherit", "inherit"],
});

console.log("已初始化导航、页脚、语言菜单和产品分类多语言。");

function seedSiteNavigation() {
  return seedChildRows("site_settings", "site_settings_navigation_items", [
    navRow("seed-nav-home", 0, "/", false, { en: "Home", zh: "首页", es: "Inicio", ar: "الرئيسية" }),
    navRow("seed-nav-products", 1, "/products", true, { en: "Products", zh: "产品中心", es: "Productos", ar: "المنتجات" }),
    navRow("seed-nav-application", 2, "/#application", true, { en: "Application", zh: "应用场景", es: "Aplicaciones", ar: "التطبيقات" }),
    navRow("seed-nav-cases", 3, "/cases", false, { en: "Cases", zh: "案例中心", es: "Casos", ar: "الحالات" }),
    navRow("seed-nav-about", 4, "/about", false, { en: "About BOB-LIFT", zh: "关于 BOB-LIFT", es: "Sobre BOB-LIFT", ar: "عن BOB-LIFT" }),
    navRow("seed-nav-news", 5, "/news", false, { en: "News", zh: "新闻中心", es: "Noticias", ar: "الأخبار" }),
    navRow("seed-nav-contact", 6, "/contact", false, { en: "Contact Us", zh: "联系我们", es: "Contacto", ar: "اتصل بنا" }),
  ], "site_settings_navigation_items_locales");
}

function seedProductMenuGroups() {
  return seedChildRows("site_settings", "site_settings_product_menu_groups", [
    menuGroupRow("seed-menu-truck", 0, "truck-mounted-crane", "/products", "truck-crane", 10, {
      en: ["Truck Mounted Crane", "Road transport lifting families"],
      zh: ["随车起重机", "公路运输和现场吊装主力产品"],
      es: ["Grúas sobre camión", "Familias de elevación para transporte y obra"],
      ar: ["رافعات الشاحنات", "حلول رفع للنقل والعمل الميداني"],
    }),
    menuGroupRow("seed-menu-marine", 1, "marine-crane", "/products", "marine-crane", 20, {
      en: ["Marine Crane", "Deck and port lifting choices"],
      zh: ["船用起重机", "甲板、码头和水上作业选择"],
      es: ["Grúas marinas", "Opciones para cubierta y puerto"],
      ar: ["رافعات بحرية", "خيارات للسطح والموانئ"],
    }),
    menuGroupRow("seed-menu-compact", 2, "compact-attachments", "/products", "attachments", 30, {
      en: ["Compact & Attachments", "Smaller equipment and add-ons"],
      zh: ["小型设备与附件", "小型起重设备和配套附件"],
      es: ["Compactos y accesorios", "Equipos pequeños y complementos"],
      ar: ["معدات صغيرة وملحقات", "معدات مدمجة وإضافات"],
    }),
  ], "site_settings_product_menu_groups_locales");
}

function seedLanguageOptions() {
  const languages = [
    ["seed-lang-en", 0, "en", "/flags/us.svg", "ltr", "ready", 100, { en: ["English", "United States / Global"], zh: ["English", "美国 / 全球"], es: ["English", "Estados Unidos / Global"], ar: ["English", "الولايات المتحدة / عالمي"] }],
    ["seed-lang-es", 1, "es", "/flags/es.svg", "ltr", "in-progress", 85, { en: ["Español", "Spain / LATAM"], zh: ["Español", "西班牙 / 拉美"], es: ["Español", "España / LATAM"], ar: ["Español", "إسبانيا / أمريكا اللاتينية"] }],
    ["seed-lang-ru", 2, "ru", "/flags/ru.svg", "ltr", "not-started", 0, { en: ["Русский", "Russia / CIS"] }],
    ["seed-lang-ar", 3, "ar", "/flags/sa.svg", "rtl", "in-progress", 85, { en: ["العربية", "MENA / Arabic"], zh: ["العربية", "中东 / 阿语"], es: ["العربية", "MENA / Árabe"], ar: ["العربية", "الشرق الأوسط / العربية"] }],
    ["seed-lang-pt", 4, "pt", "/flags/br.svg", "ltr", "not-started", 0, { en: ["Português", "Brazil / Lusophone"] }],
    ["seed-lang-fr", 5, "fr", "/flags/fr.svg", "ltr", "not-started", 0, { en: ["Français", "France / Africa"] }],
    ["seed-lang-de", 6, "de", "/flags/de.svg", "ltr", "not-started", 0, { en: ["Deutsch", "Germany / EU"] }],
    ["seed-lang-tr", 7, "tr", "/flags/tr.svg", "ltr", "not-started", 0, { en: ["Türkçe", "Türkiye / Central Asia"] }],
    ["seed-lang-id", 8, "id", "/flags/id.svg", "ltr", "not-started", 0, { en: ["Bahasa Indonesia", "Indonesia / ASEAN"] }],
    ["seed-lang-vi", 9, "vi", "/flags/vn.svg", "ltr", "not-started", 0, { en: ["Tiếng Việt", "Vietnam"] }],
    ["seed-lang-th", 10, "th", "/flags/th.svg", "ltr", "not-started", 0, { en: ["ไทย", "Thailand"] }],
    ["seed-lang-hi", 11, "hi", "/flags/in.svg", "ltr", "not-started", 0, { en: ["हिन्दी", "India"] }],
    ["seed-lang-zh", 12, "zh", "/flags/cn.svg", "ltr", "in-progress", 85, { en: ["中文", "China / Global"], zh: ["中文", "中国 / 全球"], es: ["中文", "China / Global"], ar: ["中文", "الصين / عالمي"] }],
    ["seed-lang-ko", 13, "ko", "/flags/kr.svg", "ltr", "not-started", 0, { en: ["한국어", "Korea"] }],
  ];

  const rows = languages.map(([id, order, locale, flag, direction, status, percent, labels]) => ({
    id,
    order,
    values: { locale, flag, direction, translation_status: status, completion_percent: percent, enabled: locale === "en" || (status === "ready" && percent >= 95), sort_order: (order + 1) * 10 },
    locales: Object.fromEntries(Object.entries(labels).map(([key, [label, market]]) => [key, { label, market }])),
  }));
  const syncRows = rows.map((row) => `UPDATE "site_settings_language_options"
SET "locale"=${sqlValue(row.values.locale)}, "flag"=${sqlValue(row.values.flag)}, "direction"=${sqlValue(row.values.direction)}, "translation_status"=${sqlValue(row.values.translation_status)}, "completion_percent"=${sqlValue(row.values.completion_percent)}, "enabled"=${sqlValue(row.values.enabled)}, "sort_order"=${sqlValue(row.values.sort_order)}
WHERE "id"=${sqlValue(row.id)};`).join("\n");

  return `${seedChildRows("site_settings", "site_settings_language_options", rows, "site_settings_language_options_locales")}\n${syncRows}`;
}

function seedFooterLinks() {
  const groups = [
    footerGroup("seed-footer-products", 0, { en: "Products", zh: "产品中心", es: "Productos", ar: "المنتجات" }, [
      ["/products/category/telescopic-boom-truck-mounted-cranes", { en: "Telescopic Boom Truck Mounted Cranes", zh: "伸缩臂随车起重机", es: "Grúas telescópicas sobre camión", ar: "رافعات شاحنات تلسكوبية" }],
      ["/products/category/knuckle-boom-truck-mounted-cranes", { en: "Knuckle Boom Truck Mounted Cranes", zh: "折臂随车起重机", es: "Grúas articuladas sobre camión", ar: "رافعات شاحنات مفصلية" }],
      ["/products/category/knuckle-boom-marine-cranes", { en: "Marine Cranes", zh: "船用起重机", es: "Grúas marinas", ar: "رافعات بحرية" }],
      ["/products/category/pickup-cranes", { en: "Pickup Cranes", zh: "皮卡起重机", es: "Grúas pickup", ar: "رافعات بيك أب" }],
      ["/products/category/mini-dumpers", { en: "Mini Dumpers", zh: "迷你自卸车", es: "Mini volquetes", ar: "قلاب صغير" }],
    ]),
    footerGroup("seed-footer-application", 1, { en: "Application", zh: "应用场景", es: "Aplicaciones", ar: "التطبيقات" }, [
      ["/#application", { en: "Construction", zh: "建筑施工", es: "Construcción", ar: "البناء" }],
      ["/#application", { en: "Logistics & Transport", zh: "物流运输", es: "Logística y transporte", ar: "اللوجستيات والنقل" }],
      ["/#application", { en: "Port Operations", zh: "港口作业", es: "Operaciones portuarias", ar: "عمليات الموانئ" }],
      ["/#application", { en: "Marine Operations", zh: "船舶作业", es: "Operaciones marinas", ar: "عمليات بحرية" }],
      ["/#application", { en: "Municipal Engineering", zh: "市政工程", es: "Ingeniería municipal", ar: "الهندسة البلدية" }],
    ]),
    footerGroup("seed-footer-company", 2, { en: "Company", zh: "公司信息", es: "Empresa", ar: "الشركة" }, [
      ["/about", { en: "About BOB-LIFT", zh: "关于 BOB-LIFT", es: "Sobre BOB-LIFT", ar: "عن BOB-LIFT" }],
      ["/cases", { en: "Customer Cases", zh: "客户案例", es: "Casos de clientes", ar: "حالات العملاء" }],
      ["/news", { en: "Company News", zh: "公司新闻", es: "Noticias", ar: "أخبار الشركة" }],
      ["/contact", { en: "Contact Us", zh: "联系我们", es: "Contacto", ar: "اتصل بنا" }],
    ]),
  ];
  const groupSql = seedChildRows("site_settings", "site_settings_footer_groups", groups, "site_settings_footer_groups_locales");
  const linkSql = groups.map((group) => seedStaticParentChildRows(group.id, "site_settings_footer_groups_links", group.links, "site_settings_footer_groups_links_locales")).join("\n");
  const socialSql = seedChildRows("site_settings", "site_settings_social_links", [
    simpleLinkRow("seed-social-linkedin", 0, "/contact", { en: "in", zh: "in", es: "in", ar: "in" }),
    simpleLinkRow("seed-social-youtube", 1, "/contact", { en: "yt", zh: "yt", es: "yt", ar: "yt" }),
    simpleLinkRow("seed-social-facebook", 2, "/contact", { en: "fb", zh: "fb", es: "fb", ar: "fb" }),
  ], "site_settings_social_links_locales");
  const legalSql = seedChildRows("site_settings", "site_settings_legal_links", [
    simpleLinkRow("seed-legal-privacy", 0, "/privacy-policy.html", { en: "Privacy Policy", zh: "隐私政策", es: "Política de privacidad", ar: "سياسة الخصوصية" }),
    simpleLinkRow("seed-legal-terms", 1, "/contact", { en: "Terms of Use", zh: "使用条款", es: "Términos de uso", ar: "شروط الاستخدام" }),
    simpleLinkRow("seed-legal-sitemap", 2, "/contact", { en: "Sitemap", zh: "网站地图", es: "Mapa del sitio", ar: "خريطة الموقع" }),
  ], "site_settings_legal_links_locales");

  return [groupSql, linkSql, socialSql, legalSql].join("\n");
}

function seedCategoryLocales() {
  const categories = {
    "telescopic-boom-truck-mounted-cranes": { zh: "伸缩臂随车起重机", es: "Grúas telescópicas sobre camión", ar: "رافعات شاحنات تلسكوبية" },
    "knuckle-boom-truck-mounted-cranes": { zh: "折臂随车起重机", es: "Grúas articuladas sobre camión", ar: "رافعات شاحنات مفصلية" },
    "knuckle-boom-marine-cranes": { zh: "折臂船用起重机", es: "Grúas marinas articuladas", ar: "رافعات بحرية مفصلية" },
    "telescopic-boom-marine-cranes": { zh: "伸缩臂船用起重机", es: "Grúas marinas telescópicas", ar: "رافعات بحرية تلسكوبية" },
    "pickup-cranes": { zh: "皮卡起重机", es: "Grúas pickup", ar: "رافعات بيك أب" },
    "forklift-fly-jib-cranes": { zh: "叉车飞臂吊", es: "Plumines para montacargas", ar: "أذرع رفع للرافعات الشوكية" },
    "mini-dumpers": { zh: "迷你自卸车", es: "Mini volquetes", ar: "قلاب صغير" },
    "aerial-work-platform": { zh: "高空作业平台", es: "Plataformas de trabajo aéreo", ar: "منصات عمل جوية" },
    "crane-accessories": { zh: "起重机配件", es: "Accesorios para grúas", ar: "ملحقات الرافعات" },
    "tail-lift": { zh: "汽车尾板", es: "Plataforma elevadora trasera", ar: "رافعة خلفية للشاحنة" },
    "spider-cranes": { zh: "蜘蛛吊", es: "Grúas araña", ar: "رافعات عنكبوتية" },
    "timber-cranes": { zh: "木材起重机", es: "Grúas forestales", ar: "رافعات أخشاب" },
    "other-cranes": { zh: "其他起重机", es: "Otras grúas", ar: "رافعات أخرى" },
  };

  return Object.entries(categories).flatMap(([slug, labels]) =>
    Object.entries(labels).map(([locale, title]) => `INSERT INTO "product_categories_locales" ("title", "short_title", "description", "banner_label", "banner_title", "_locale", "_parent_id")
SELECT ${q(title)}, ${q(title)}, ${q(categoryDescription(title, locale))}, ${q(categoryBannerLabel(locale))}, ${q(title)}, ${q(locale)}, id
FROM "product_categories"
WHERE "slug" = ${q(slug)}
ON CONFLICT ("_locale", "_parent_id") DO UPDATE
SET "title" = EXCLUDED."title", "short_title" = EXCLUDED."short_title", "description" = EXCLUDED."description", "banner_label" = EXCLUDED."banner_label", "banner_title" = EXCLUDED."banner_title";`)
  ).join("\n");
}

function hideEmptyDuplicateCategories() {
  return `UPDATE "product_categories" c
SET "show_in_mega_menu" = FALSE, "show_on_home" = FALSE, "status" = 'draft'
WHERE c."slug" LIKE '%-copy'
  AND NOT EXISTS (SELECT 1 FROM "products" p WHERE p."category_ref_id" = c."id");`;
}
function categoryBannerLabel(locale) {
  if (locale === "zh") return "BOB-LIFT 产品系列";
  if (locale === "es") return "Gama de productos BOB-LIFT";
  if (locale === "ar") return "مجموعة منتجات BOB-LIFT";
  return "BOB-LIFT Product Range";
}

function categoryDescription(title, locale) {
  if (locale === "zh") return `浏览 ${title} 型号，并获取匹配工况的 BOB-LIFT 配置方案。`;
  if (locale === "es") return `Explore modelos de ${title} y solicite una configuración BOB-LIFT adecuada para sus condiciones de trabajo.`;
  if (locale === "ar") return `استعرض موديلات ${title} واطلب تكوين BOB-LIFT المناسب لظروف العمل.`;
  return `Explore BOB-LIFT ${title} models and request a configuration matched to your working conditions.`;
}


function navRow(id, order, href, hasMenu, labels) {
  return { id, order, values: { href, has_menu: hasMenu, show_in_header: true, sort_order: (order + 1) * 10 }, locales: labelLocales(labels) };
}

function menuGroupRow(id, order, groupKey, href, iconName, sortOrder, labels) {
  return {
    id,
    order,
    values: { group_key: groupKey, href, icon_name: iconName, sort_order: sortOrder },
    locales: Object.fromEntries(Object.entries(labels).map(([locale, [title, description]]) => [locale, { title, description }])),
  };
}

function footerGroup(id, order, labels, links) {
  return {
    id,
    order,
    values: {},
    locales: Object.fromEntries(Object.entries(labels).map(([locale, title]) => [locale, { title }])),
    links: links.map(([href, linkLabels], index) => simpleLinkRow(`${id}-link-${index}`, index, href, linkLabels)),
  };
}

function simpleLinkRow(id, order, href, labels) {
  return { id, order, values: { href }, locales: labelLocales(labels) };
}

function labelLocales(labels) {
  return Object.fromEntries(Object.entries(labels).map(([locale, label]) => [locale, { label }]));
}

function seedChildRows(parentTable, childTable, rows, localeTable) {
  return rows.map((row) => {
    const valueColumns = Object.keys(row.values);
    const columns = ["_order", "_parent_id", "id", ...valueColumns];
    const values = [row.order, raw("id"), row.id, ...valueColumns.map((column) => row.values[column])];
    return `INSERT INTO ${id(childTable)} (${columns.map(id).join(", ")})
SELECT ${values.map(sqlValue).join(", ")}
FROM ${id(parentTable)}
ORDER BY id
LIMIT 1
ON CONFLICT ("id") DO NOTHING;
${seedLocales(localeTable, childTable, row.id, row.locales)}`;
  }).join("\n");
}

function seedStaticParentChildRows(parentId, childTable, rows, localeTable) {
  return rows.map((row) => {
    const valueColumns = Object.keys(row.values);
    const columns = ["_order", "_parent_id", "id", ...valueColumns];
    const values = [row.order, parentId, row.id, ...valueColumns.map((column) => row.values[column])];
    return `INSERT INTO ${id(childTable)} (${columns.map(id).join(", ")})
SELECT ${values.map(sqlValue).join(", ")}
WHERE EXISTS (SELECT 1 FROM "site_settings_footer_groups" WHERE "id" = ${q(parentId)})
ON CONFLICT ("id") DO NOTHING;
${seedLocales(localeTable, childTable, row.id, row.locales)}`;
  }).join("\n");
}

function seedLocales(localeTable, childTable, parentId, locales) {
  return Object.entries(locales).map(([locale, values]) => {
    const columns = Object.keys(values);
    return `INSERT INTO ${id(localeTable)} (${columns.map(id).join(", ")}, "_locale", "_parent_id")
SELECT ${columns.map((column) => sqlValue(values[column])).join(", ")}, ${q(locale)}, ${q(parentId)}
WHERE EXISTS (SELECT 1 FROM ${id(childTable)} WHERE "id" = ${q(parentId)})
ON CONFLICT ("_locale", "_parent_id") DO UPDATE
SET ${columns.map((column) => `${id(column)} = EXCLUDED.${id(column)}`).join(", ")};`;
  }).join("\n");
}

function id(value) {
  return `"${String(value).replaceAll('"', '""')}"`;
}

function raw(value) {
  return { raw: value };
}

function q(value) {
  return sqlValue(value);
}

function sqlValue(value) {
  if (value && typeof value === "object" && "raw" in value) return value.raw;
  if (value === null || typeof value === "undefined") return "NULL";
  if (typeof value === "number") return Number.isFinite(value) ? String(value) : "NULL";
  if (typeof value === "boolean") return value ? "TRUE" : "FALSE";
  return `'${String(value).replaceAll("'", "''")}'`;
}

function loadDotEnv(path) {
  if (!existsSync(path)) return;
  for (const line of readFileSync(path, "utf8").split(/\r?\n/)) {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith("#") || !trimmed.includes("=")) continue;
    const index = trimmed.indexOf("=");
    const key = trimmed.slice(0, index).trim();
    if (process.env[key]) continue;
    let value = trimmed.slice(index + 1).trim();
    if ((value.startsWith('"') && value.endsWith('"')) || (value.startsWith("'") && value.endsWith("'"))) {
      value = value.slice(1, -1);
    }
    process.env[key] = value;
  }
}
