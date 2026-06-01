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

const articles = [
  {
    slug: "bob-lift-sq10za5-limited-time-discount-hot-selling",
    legacyPath: "/BOB-LIFT-SQ10ZA5-Limited-Time-Discount-Hot-Selling-id03356155.html",
    category: "product-news",
    date: "2026-05-20T00:00:00.000Z",
    image: "/images/oldsite/news/bob-lift-sq10za5-limited-time-discount-hot-selling.png",
    locales: {
      en: {
        title: "BOB-LIFT SQ10ZA5 Limited Time Discount Hot Selling!",
        excerpt: "A focused product update for customers comparing 10 ton knuckle boom truck-mounted crane options.",
      },
      zh: {
        title: "BOB-LIFT SQ10ZA5 限时优惠热销",
        excerpt: "面向正在对比 10 吨折臂随车起重机的客户，快速说明 SQ10ZA5 的采购价值。",
      },
      es: {
        title: "BOB-LIFT SQ10ZA5 con promoción por tiempo limitado",
        excerpt: "Actualización para compradores que comparan grúas articuladas sobre camión de 10 toneladas.",
      },
      ar: {
        title: "عرض محدود على BOB-LIFT SQ10ZA5",
        excerpt: "تحديث سريع للعملاء الذين يقارنون رافعات الشاحنات المفصلية حمولة 10 أطنان.",
      },
    },
    sections: [
      "SQ10ZA5 is positioned for customers who need a compact folding boom layout, stable hydraulic control and stronger loading efficiency on truck-mounted working conditions.",
      "Share the target load, truck chassis, working radius and destination country. The engineering team can then match crane capacity, installation layout and export documentation.",
    ],
  },
  {
    slug: "bob-lift-three-models-of-truck-mounted-cranes-are-currently-on-promotion",
    legacyPath: "/BOB-LIFT-Three-Models-of-Truck-mounted-Cranes-Are-Currently-on-Promotion-id06681255.html",
    category: "company-news",
    date: "2026-05-18T00:00:00.000Z",
    image: "/images/oldsite/news/bob-lift-three-models-of-truck-mounted-cranes-are-currently-on-promotion.jpg",
    locales: {
      en: {
        title: "BOB-LIFT Three Models of Truck-mounted Cranes Are Currently on Promotion.",
        excerpt: "Three truck-mounted crane choices are highlighted for customers planning near-term procurement.",
      },
      zh: {
        title: "BOB-LIFT 三款随车起重机正在促销",
        excerpt: "针对近期采购客户，集中展示三款随车起重机的选型方向。",
      },
      es: {
        title: "Tres modelos de grúas sobre camión BOB-LIFT están en promoción",
        excerpt: "Tres opciones de grúa sobre camión para clientes con planes de compra próximos.",
      },
      ar: {
        title: "ثلاثة موديلات من رافعات BOB-LIFT على الشاحنات ضمن العرض",
        excerpt: "ثلاثة اختيارات مهمة للعملاء الذين يخططون للشراء قريباً.",
      },
    },
    sections: [
      "The update helps buyers compare practical crane models by lifting capacity, boom type and installation scenario before requesting a formal quotation.",
      "BOB-LIFT can support model selection, chassis matching, packaging advice and export documents after the customer confirms working conditions.",
    ],
  },
  {
    slug: "enter-the-bob-lift-factory-with-professional-manufacturing",
    legacyPath: "/Enter-The-BOB-LIFT-Factory-With-Professional-Manufacturing-We-Create-More-Reliable-Lifting-Equipment-id09841355.html",
    category: "industry-insight",
    date: "2026-05-11T00:00:00.000Z",
    image: "/images/oldsite/news/enter-the-bob-lift-factory-with-professional-manufacturing.jpg",
    locales: {
      en: {
        title: "Enter The BOB-LIFT Factory: With Professional Manufacturing, We Create More Reliable Lifting Equipment.",
        excerpt: "A factory-oriented article page for showing manufacturing capability, quality workflow and export readiness.",
      },
      zh: {
        title: "走进 BOB-LIFT 工厂：用专业制造打造更可靠的起重设备",
        excerpt: "展示制造能力、质量流程和出口准备情况的工厂内容。",
      },
      es: {
        title: "Dentro de la fábrica BOB-LIFT: fabricación profesional para equipos más fiables",
        excerpt: "Contenido de fábrica sobre capacidad de fabricación, control de calidad y preparación para exportación.",
      },
      ar: {
        title: "داخل مصنع BOB-LIFT: تصنيع احترافي لمعدات رفع أكثر موثوقية",
        excerpt: "محتوى يوضح القدرة التصنيعية وسير الجودة والاستعداد للتصدير.",
      },
    },
    sections: [
      "Reliable lifting equipment depends on repeatable fabrication, stable hydraulic assembly and inspection steps that can be checked before shipment.",
      "From component preparation to final testing, the production workflow is organized around safer lifting performance and easier long-term maintenance.",
    ],
  },
  {
    slug: "bob-lift-offers-efficient-and-reliable-solutions-for-ship-lifting-operations",
    legacyPath: "/BOB-LIFT-Offers-Efficient-And-Reliable-Solutions-for-Ship-Lifting-Operations-id02577455.html",
    category: "industry-insight",
    date: "2026-04-27T00:00:00.000Z",
    image: "/images/oldsite/news/bob-lift-offers-efficient-and-reliable-solutions-for-ship-lifting-operations.jpg",
    locales: {
      en: {
        title: "BOB-LIFT Offers Efficient And Reliable Solutions for Ship Lifting Operations.",
        excerpt: "Marine crane content focused on deck operation, ship lifting support and corrosion-conscious configuration.",
      },
      zh: {
        title: "BOB-LIFT 为船舶吊装作业提供高效可靠方案",
        excerpt: "围绕甲板作业、船舶吊装支持和耐腐蚀配置的船用起重机内容。",
      },
      es: {
        title: "BOB-LIFT ofrece soluciones fiables para operaciones de elevación en barcos",
        excerpt: "Contenido de grúas marinas para cubierta, apoyo de izaje y configuración anticorrosión.",
      },
      ar: {
        title: "BOB-LIFT تقدم حلولاً فعالة وموثوقة لعمليات الرفع البحرية",
        excerpt: "محتوى عن رافعات بحرية لعمليات السطح والدعم البحري ومقاومة التآكل.",
      },
    },
    sections: [
      "Ship and dock operations require cranes that can handle limited deck space, changing load positions and stricter reliability expectations.",
      "Customers should provide vessel type, installation area, working radius, load requirement and destination port so the team can prepare a practical solution.",
    ],
  },
];

const cases = [
  {
    slug: "bob-lift-provides-reliable-support-for-water-operations",
    image: "/images/oldsite/cases/bob-lift-provides-reliable-support-for-water-operations.jpg",
    date: "2026-05-12T00:00:00.000Z",
    locales: {
      en: ["BOB-LIFT provides reliable support for water operations", "Customer Case · Marine Operations", "Marine Operations", "A marine operation case centered on stable lifting support, deck-side work and equipment reliability near water."],
      zh: ["BOB-LIFT 为水上作业提供可靠支撑", "客户案例 · 水上作业", "水上作业", "围绕稳定吊装、甲板侧作业和水域设备可靠性的船用场景案例。"],
      es: ["BOB-LIFT ofrece apoyo fiable para operaciones sobre el agua", "Caso de cliente · Operaciones marinas", "Operaciones marinas", "Caso marino centrado en elevación estable, trabajo en cubierta y fiabilidad cerca del agua."],
      ar: ["BOB-LIFT توفر دعماً موثوقاً للعمليات المائية", "حالة عميل · عمليات بحرية", "عمليات بحرية", "حالة بحرية تركز على الرفع المستقر والعمل قرب المياه وموثوقية المعدات."],
    },
    facts: [
      { en: ["Scenario", "Water Operations"], zh: ["场景", "水上作业"], es: ["Escenario", "Operaciones sobre agua"], ar: ["المشهد", "عمليات مائية"] },
      { en: ["Product", "Marine Crane"], zh: ["产品", "船用起重机"], es: ["Producto", "Grúa marina"], ar: ["المنتج", "رافعة بحرية"] },
      { en: ["Priority", "Stable Lifting"], zh: ["重点", "稳定吊装"], es: ["Prioridad", "Elevación estable"], ar: ["الأولوية", "رفع مستقر"] },
    ],
    sections: [
      { en: ["Working challenge", "Water operation projects often face limited installation space and higher expectations for stable lifting during repeated loading tasks."], zh: ["作业挑战", "水上作业项目通常安装空间有限，同时对重复吊装的稳定性要求更高。"], es: ["Desafío", "Los proyectos sobre agua tienen espacio limitado y mayores expectativas de estabilidad."], ar: ["التحدي", "تواجه المشاريع المائية مساحة تركيب محدودة ومتطلبات أعلى لاستقرار الرفع."] },
      { en: ["BOB-LIFT response", "The solution focuses on matching boom structure, working radius and hydraulic control to the actual marine operation environment."], zh: ["BOB-LIFT 方案", "方案重点匹配臂架结构、工作半径和液压控制，使其适应真实水上作业环境。"], es: ["Respuesta BOB-LIFT", "La solución adapta la pluma, el radio de trabajo y el control hidráulico al entorno marino."], ar: ["استجابة BOB-LIFT", "يركز الحل على مواءمة بنية الذراع ونطاق العمل والتحكم الهيدروليكي مع البيئة البحرية."] },
    ],
  },
  {
    slug: "strong-lifting-combined-with-efficient-grippers",
    image: "/images/oldsite/cases/strong-lifting-combined-with-efficient-grippers.jpg",
    date: "2026-05-08T00:00:00.000Z",
    locales: {
      en: ["Strong lifting combined with efficient grippers", "Customer Case · Complex Working Conditions", "Material Handling", "A material-handling case showing how lifting equipment and attachments improve loading efficiency."],
      zh: ["强力起吊配合高效抓具", "客户案例 · 复杂工况", "物料搬运", "展示起重设备与属具配合如何提升装卸效率的物料搬运案例。"],
      es: ["Elevación fuerte combinada con pinzas eficientes", "Caso de cliente · Condiciones complejas", "Manejo de materiales", "Caso que muestra cómo grúas y accesorios mejoran la eficiencia de carga."],
      ar: ["رفع قوي مع ملاقط فعالة", "حالة عميل · ظروف عمل معقدة", "مناولة مواد", "حالة توضح كيف تحسن المعدات والملحقات كفاءة التحميل."],
    },
    facts: [
      { en: ["Scenario", "Material Handling"], zh: ["场景", "物料搬运"], es: ["Escenario", "Manejo de materiales"], ar: ["المشهد", "مناولة مواد"] },
      { en: ["Product", "Crane + Gripper"], zh: ["产品", "起重机 + 抓具"], es: ["Producto", "Grúa + pinza"], ar: ["المنتج", "رافعة + ملقط"] },
      { en: ["Priority", "Efficiency"], zh: ["重点", "效率"], es: ["Prioridad", "Eficiencia"], ar: ["الأولوية", "الكفاءة"] },
    ],
    sections: [
      { en: ["Working challenge", "Customers handling irregular materials need lifting equipment that can keep strength, control and grip efficiency in balance."], zh: ["作业挑战", "处理不规则物料的客户，需要在力量、控制和抓取效率之间取得平衡。"], es: ["Desafío", "Los materiales irregulares requieren equilibrio entre fuerza, control y agarre."], ar: ["التحدي", "تتطلب المواد غير المنتظمة توازناً بين القوة والتحكم وكفاءة الإمساك."] },
      { en: ["BOB-LIFT response", "The case highlights matched lifting capacity and attachment logic, reducing repeated manual handling during field work."], zh: ["BOB-LIFT 方案", "案例强调起重能力与属具逻辑匹配，减少现场重复人工搬运。"], es: ["Respuesta BOB-LIFT", "El caso destaca capacidad y accesorios adecuados para reducir manejo manual repetido."], ar: ["استجابة BOB-LIFT", "تبرز الحالة مواءمة قدرة الرفع والملحقات لتقليل المناولة اليدوية المتكررة."] },
    ],
  },
  {
    slug: "leg-and-chassis-technology-of-the-bob-lift-aerial-work-platform",
    image: "/images/oldsite/cases/leg-and-chassis-technology-of-the-bob-lift-aerial-work-platform.jpg",
    date: "2026-05-05T00:00:00.000Z",
    locales: {
      en: ["Leg and chassis technology of the BOB-LIFT aerial work platform", "Customer Case · Aerial Work Platform", "Aerial Work", "A technology case focused on support legs, chassis stability and safe operation around aerial working conditions."],
      zh: ["BOB-LIFT 高空作业平台的支腿与底盘技术", "客户案例 · 高空作业平台", "高空作业", "聚焦支腿、底盘稳定性和高空作业安全性的技术案例。"],
      es: ["Tecnología de patas y chasis de la plataforma aérea BOB-LIFT", "Caso de cliente · Plataforma aérea", "Trabajo en altura", "Caso técnico sobre estabilizadores, chasis y operación segura en altura."],
      ar: ["تقنية الأرجل والشاسيه لمنصة العمل الجوية BOB-LIFT", "حالة عميل · منصة عمل جوية", "عمل على ارتفاع", "حالة تقنية عن الدعامات وثبات الشاسيه والتشغيل الآمن على ارتفاع."],
    },
    facts: [
      { en: ["Scenario", "Aerial Work"], zh: ["场景", "高空作业"], es: ["Escenario", "Trabajo en altura"], ar: ["المشهد", "عمل على ارتفاع"] },
      { en: ["Product", "Aerial Platform"], zh: ["产品", "高空作业平台"], es: ["Producto", "Plataforma aérea"], ar: ["المنتج", "منصة جوية"] },
      { en: ["Priority", "Stability"], zh: ["重点", "稳定性"], es: ["Prioridad", "Estabilidad"], ar: ["الأولوية", "الثبات"] },
    ],
    sections: [
      { en: ["Working challenge", "Aerial work equipment needs stable support and predictable chassis behavior before lifting workers to operating height."], zh: ["作业挑战", "高空作业设备在举升人员前，需要稳定支撑和可预期的底盘表现。"], es: ["Desafío", "El equipo aéreo necesita soporte estable y comportamiento predecible del chasis."], ar: ["التحدي", "تحتاج معدات العمل الجوي إلى دعم ثابت وسلوك شاسيه يمكن توقعه."] },
      { en: ["BOB-LIFT response", "The case presents the role of leg structure and chassis technology in improving safety and operation confidence."], zh: ["BOB-LIFT 方案", "案例说明支腿结构和底盘技术对安全性与操作信心的提升作用。"], es: ["Respuesta BOB-LIFT", "El caso muestra cómo patas y chasis mejoran seguridad y confianza operativa."], ar: ["استجابة BOB-LIFT", "توضح الحالة دور الدعامات والشاسيه في تحسين السلامة والثقة التشغيلية."] },
    ],
  },
];

const sql = [
  ...articles.map(articleSql),
  ...cases.map(caseSql),
].join("\n\n");

execFileSync("psql", [databaseUrl, "-v", "ON_ERROR_STOP=1", "-q"], {
  input: sql,
  stdio: ["pipe", "inherit", "inherit"],
});

console.log(`已导入旧站新闻与案例：articles=${articles.length}, cases=${cases.length}。`);

function articleSql(article) {
  const html = article.sections.map((body) => `<p>${escapeHtml(body)}</p>`).join("");
  return `INSERT INTO "articles" ("slug", "legacy_path", "category", "cover_image_path", "seo_meta_title", "seo_meta_description", "seo_canonical_path", "status", "published_at", "updated_at", "created_at", "content_html")
VALUES (${q(article.slug)}, ${q(article.legacyPath)}, ${q(article.category)}, ${q(article.image)}, ${q(article.locales.en.title)}, ${q(article.locales.en.excerpt)}, ${q(`/news/${article.slug}`)}, 'published', ${q(article.date)}, now(), now(), ${q(html)})
ON CONFLICT ("slug") DO UPDATE SET
  "legacy_path" = EXCLUDED."legacy_path",
  "category" = EXCLUDED."category",
  "cover_image_path" = EXCLUDED."cover_image_path",
  "seo_meta_title" = EXCLUDED."seo_meta_title",
  "seo_meta_description" = EXCLUDED."seo_meta_description",
  "seo_canonical_path" = EXCLUDED."seo_canonical_path",
  "status" = 'published',
  "published_at" = EXCLUDED."published_at",
  "content_html" = EXCLUDED."content_html",
  "updated_at" = now();
${Object.entries(article.locales).map(([locale, row]) => `INSERT INTO "articles_locales" ("title", "excerpt", "_locale", "_parent_id")
SELECT ${q(row.title)}, ${q(row.excerpt)}, ${q(locale)}, "id" FROM "articles" WHERE "slug" = ${q(article.slug)}
ON CONFLICT ("_locale", "_parent_id") DO UPDATE SET "title" = EXCLUDED."title", "excerpt" = EXCLUDED."excerpt";`).join("\n")}`;
}

function caseSql(item) {
  const [title, , , summary] = item.locales.en;
  return `INSERT INTO "cases" ("slug", "cover_image_path", "seo_meta_title", "seo_meta_description", "seo_canonical_path", "status", "published_at", "updated_at", "created_at")
VALUES (${q(item.slug)}, ${q(item.image)}, ${q(title)}, ${q(summary)}, ${q(`/cases/${item.slug}`)}, 'published', ${q(item.date)}, now(), now())
ON CONFLICT ("slug") DO UPDATE SET
  "cover_image_path" = EXCLUDED."cover_image_path",
  "seo_meta_title" = EXCLUDED."seo_meta_title",
  "seo_meta_description" = EXCLUDED."seo_meta_description",
  "seo_canonical_path" = EXCLUDED."seo_canonical_path",
  "status" = 'published',
  "published_at" = EXCLUDED."published_at",
  "updated_at" = now();
${Object.entries(item.locales).map(([locale, row]) => `INSERT INTO "cases_locales" ("title", "case_type", "industry", "summary", "_locale", "_parent_id")
SELECT ${q(row[0])}, ${q(row[1])}, ${q(row[2])}, ${q(row[3])}, ${q(locale)}, "id" FROM "cases" WHERE "slug" = ${q(item.slug)}
ON CONFLICT ("_locale", "_parent_id") DO UPDATE SET "title" = EXCLUDED."title", "case_type" = EXCLUDED."case_type", "industry" = EXCLUDED."industry", "summary" = EXCLUDED."summary";`).join("\n")}
DELETE FROM "cases_facts" WHERE "_parent_id" = (SELECT "id" FROM "cases" WHERE "slug" = ${q(item.slug)});
DELETE FROM "cases_sections" WHERE "_parent_id" = (SELECT "id" FROM "cases" WHERE "slug" = ${q(item.slug)});
${item.facts.map((fact, index) => childRows("cases_facts", "cases_facts_locales", item.slug, "fact", index, fact)).join("\n")}
${item.sections.map((section, index) => childRows("cases_sections", "cases_sections_locales", item.slug, "section", index, section, ["heading", "body"])).join("\n")}`;
}

function childRows(table, localeTable, caseSlug, kind, order, localizedRows, columns = ["label", "value"]) {
  const rowId = `seed-${caseSlug}-${kind}-${order}`;
  return `INSERT INTO "${table}" ("_order", "_parent_id", "id")
SELECT ${order}, "id", ${q(rowId)} FROM "cases" WHERE "slug" = ${q(caseSlug)}
ON CONFLICT ("id") DO UPDATE SET "_order" = EXCLUDED."_order", "_parent_id" = EXCLUDED."_parent_id";
${Object.entries(localizedRows).map(([locale, values]) => `INSERT INTO "${localeTable}" (${columns.map((column) => `"${column}"`).join(", ")}, "_locale", "_parent_id")
VALUES (${columns.map((_, index) => q(values[index] ?? "")).join(", ")}, ${q(locale)}, ${q(rowId)})
ON CONFLICT ("_locale", "_parent_id") DO UPDATE SET ${columns.map((column) => `"${column}" = EXCLUDED."${column}"`).join(", ")};`).join("\n")}`;
}

function q(value) {
  if (value === null || typeof value === "undefined") return "NULL";
  return `'${String(value).replaceAll("'", "''")}'`;
}

function escapeHtml(value) {
  return value.replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;");
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
