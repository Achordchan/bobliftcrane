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

const now = "now()";

const sql = [
  insertIfMissing("site_settings", {
    email: "info@bobliftcrane.com",
    phone: "+86 15852499415",
    whatsapp: "+86 15852499415",
    wechat_id: "15852499415",
    quote_path: "/quote",
    logo_path: "/images/bob-lift-logo.png",
    logo_width: 188,
    logo_height: 91,
    product_menu_background_image_path: "/images/telescopic-banner.jpg",
    header_top_bar_link_href: "/about",
    created_at: raw(now),
    updated_at: raw(now),
  }),
  insertIfMissing("home_settings", {
    about_section_cta_href: "/about",
    news_section_cta_href: "/news",
    final_cta_button_href: "/quote",
    final_cta_image_path: "/images/cta-lift-photo.png",
    trust_bar_show_intro_card: true,
    trust_bar_show_mark_names: true,
    about_section_show_gallery: true,
    about_section_show_stats: true,
    about_section_show_timeline: true,
    about_section_show_cta: true,
    news_section_show_cta: true,
    news_section_show_excerpt: true,
    news_section_show_date: true,
    news_section_show_category: true,
    news_section_max_articles: 4,
    home_seo_canonical_path: "/",
    created_at: raw(now),
    updated_at: raw(now),
  }),
  insertIfMissing("product_listing_ui", {
    product_listing_ui_canonical_path: "/products",
    product_listing_ui_rows_per_page: 3,
    product_listing_ui_sort_visibility_recommended: true,
    product_listing_ui_sort_visibility_capacity_asc: true,
    product_listing_ui_sort_visibility_capacity_desc: true,
    product_listing_ui_sort_visibility_newest: true,
    product_listing_ui_sort_visibility_popular: true,
    product_listing_ui_show_filter_counts: true,
    created_at: raw(now),
    updated_at: raw(now),
  }),
  insertIfMissing("product_detail_ui", {
    product_detail_ui_catalog_visibility_download_button: true,
    product_detail_ui_section_visibility_features: true,
    created_at: raw(now),
    updated_at: raw(now),
  }),
  insertIfMissing("page_text_settings", {
    contact_page_ui_hero_image: "/images/global-network-blue.jpg",
    contact_page_ui_canonical_path: "/contact",
    quote_page_ui_hero_image: "/images/cta-lift-photo.png",
    quote_page_ui_canonical_path: "/quote",
    quote_page_ui_product_link_href: "/products",
    news_page_ui_hero_image: "/images/factory-workshop.jpg",
    news_page_ui_canonical_path: "/news",
    cases_page_ui_hero_image: "/images/marine-scene.jpg",
    cases_page_ui_canonical_path: "/cases",
    about_page_ui_canonical_path: "/about",
    created_at: raw(now),
    updated_at: raw(now),
  }),
  insertIfMissing("translation_settings", {
    enabled: false,
    provider: "deepl",
    auth_key_env_name: "DEEPL_AUTH_KEY",
    deeplx_api_url_env_name: "DEEPLX_API_URL",
    deeplx_auth_key_env_name: "DEEPLX_API_KEY",
    source_locale: "en",
    formality: "default",
    preserve_variables: true,
    preserve_brand_terms: true,
    notes: "1) Coverage: npm run payload:translate -- --coverage --include-stale --sync-readiness. 2) DeepL: DEEPL_AUTH_KEY=xxx npm run payload:translate -- --provider=deepl --include-stale --apply. 3) DeepLX: DEEPLX_API_KEY=xxx npm run payload:translate -- --provider=deeplx --include-stale --apply. Optional custom endpoint: DEEPLX_API_URL=https://... 4) Default targets cover zh, es, ar, fr, de, pt, ru, tr, id, vi, th, hi, ko; scope includes pages, products, categories, articles, cases, and media Alt/Caption.",
    created_at: raw(now),
    updated_at: raw(now),
  }),
  seedLocales("site_settings_locales", "site_settings", {
    en: {
      site_name: "BOB-LIFT",
      address: "Xuzhou, China",
      inquiry_subject: "BOB-LIFT quotation request",
      default_inquiry_text: "Hello BOB-LIFT, I would like to request a quotation.",
      header_top_bar_highlight: "Global Lifting Solutions.",
      header_top_bar_text: "Exported to 80+ Countries Worldwide.",
      header_top_bar_link_label: "Learn More +",
      chrome_ui_language_menu_aria_label: "Change language",
      chrome_ui_main_navigation_label: "Main navigation",
      chrome_ui_logo_home_label: "BOB-LIFT Home",
      chrome_ui_logo_image_alt: "BOB-LIFT Cranes",
      chrome_ui_mobile_menu_aria_label: "Open mobile navigation",
      chrome_ui_mobile_menu_eyebrow: "Navigation",
      chrome_ui_mobile_menu_title: "BOB-LIFT Menu",
      chrome_ui_mobile_product_categories_label: "Product Categories",
      chrome_ui_request_quote_label: "Request Quote",
      chrome_ui_short_request_quote_label: "Quote",
      chrome_ui_product_catalog_title: "Explore All Products",
      chrome_ui_product_catalog_description: "Discover our full range of lifting solutions.",
      chrome_ui_view_product_catalog_label: "View Product Catalog",
      chrome_ui_floating_quote_label: "Quote",
      chrome_ui_floating_quote_tooltip: "Request Quote",
      chrome_ui_floating_email_label: "Email",
      chrome_ui_floating_whats_app_label: "WhatsApp",
      chrome_ui_floating_call_label: "Call",
      chrome_ui_floating_we_chat_label: "WeChat",
      chrome_ui_we_chat_title: "WeChat QR Code",
      chrome_ui_we_chat_description_template: "Scan or search ID: {wechatId}",
      chrome_ui_we_chat_qr_alt: "BOB-LIFT WeChat QR code",
      chrome_ui_close_we_chat_label: "Close WeChat QR code",
      chrome_ui_back_to_top_label: "Back to top",
      footer_intro: "Engineered lifting solutions for global projects. Built to lift. Built to last.",
      footer_contact_title: "Contact Us",
      copyright: "© 2026 BOB-LIFT. All Rights Reserved.",
    },
    zh: {
      site_name: "BOB-LIFT",
      address: "中国徐州",
      inquiry_subject: "BOB-LIFT 报价咨询",
      default_inquiry_text: "您好 BOB-LIFT，我想咨询产品报价。",
      header_top_bar_highlight: "全球起重解决方案。",
      header_top_bar_text: "产品出口 80+ 个国家和地区。",
      header_top_bar_link_label: "了解更多 +",
      chrome_ui_language_menu_aria_label: "切换语言",
      chrome_ui_main_navigation_label: "主导航",
      chrome_ui_logo_home_label: "BOB-LIFT 首页",
      chrome_ui_logo_image_alt: "BOB-LIFT 起重机",
      chrome_ui_mobile_menu_aria_label: "打开移动端导航",
      chrome_ui_mobile_menu_eyebrow: "网站导航",
      chrome_ui_mobile_menu_title: "BOB-LIFT 菜单",
      chrome_ui_mobile_product_categories_label: "产品分类",
      chrome_ui_request_quote_label: "获取报价",
      chrome_ui_short_request_quote_label: "报价",
      chrome_ui_product_catalog_title: "查看全部产品",
      chrome_ui_product_catalog_description: "浏览 BOB-LIFT 起重设备与配套方案。",
      chrome_ui_view_product_catalog_label: "进入产品中心",
      chrome_ui_floating_quote_label: "询价",
      chrome_ui_floating_quote_tooltip: "获取报价",
      chrome_ui_floating_email_label: "邮箱",
      chrome_ui_floating_whats_app_label: "WhatsApp",
      chrome_ui_floating_call_label: "电话",
      chrome_ui_floating_we_chat_label: "微信",
      chrome_ui_we_chat_title: "微信二维码",
      chrome_ui_we_chat_description_template: "扫码或搜索微信号：{wechatId}",
      chrome_ui_we_chat_qr_alt: "BOB-LIFT 微信二维码",
      chrome_ui_close_we_chat_label: "关闭微信二维码",
      chrome_ui_back_to_top_label: "返回顶部",
      footer_intro: "面向全球工程项目的起重解决方案，可靠起吊，长期耐用。",
      footer_contact_title: "联系我们",
      copyright: "© 2026 BOB-LIFT. 保留所有权利。",
    },
    es: {
      site_name: "BOB-LIFT",
      address: "Xuzhou, China",
      inquiry_subject: "Solicitud de cotización BOB-LIFT",
      default_inquiry_text: "Hola BOB-LIFT, quiero solicitar una cotización.",
      header_top_bar_highlight: "Soluciones globales de elevación.",
      header_top_bar_text: "Exportado a más de 80 países.",
      header_top_bar_link_label: "Más información +",
      chrome_ui_language_menu_aria_label: "Cambiar idioma",
      chrome_ui_main_navigation_label: "Navegación principal",
      chrome_ui_logo_home_label: "Inicio BOB-LIFT",
      chrome_ui_logo_image_alt: "Grúas BOB-LIFT",
      chrome_ui_mobile_menu_aria_label: "Abrir navegación móvil",
      chrome_ui_mobile_menu_eyebrow: "Navegación",
      chrome_ui_mobile_menu_title: "Menú BOB-LIFT",
      chrome_ui_mobile_product_categories_label: "Categorías de productos",
      chrome_ui_request_quote_label: "Solicitar cotización",
      chrome_ui_short_request_quote_label: "Cotizar",
      chrome_ui_product_catalog_title: "Ver todos los productos",
      chrome_ui_product_catalog_description: "Explore la gama completa de soluciones de elevación.",
      chrome_ui_view_product_catalog_label: "Ver catálogo",
      chrome_ui_floating_quote_label: "Cotizar",
      chrome_ui_floating_quote_tooltip: "Solicitar cotización",
      chrome_ui_floating_email_label: "Correo",
      chrome_ui_floating_whats_app_label: "WhatsApp",
      chrome_ui_floating_call_label: "Llamar",
      chrome_ui_floating_we_chat_label: "WeChat",
      chrome_ui_we_chat_title: "Código QR de WeChat",
      chrome_ui_we_chat_description_template: "Escanee o busque el ID: {wechatId}",
      chrome_ui_we_chat_qr_alt: "Código QR de WeChat de BOB-LIFT",
      chrome_ui_close_we_chat_label: "Cerrar código QR de WeChat",
      chrome_ui_back_to_top_label: "Volver arriba",
      footer_intro: "Soluciones de elevación para proyectos globales. Diseñadas para levantar y durar.",
      footer_contact_title: "Contacto",
      copyright: "© 2026 BOB-LIFT. Todos los derechos reservados.",
    },
    ar: {
      site_name: "BOB-LIFT",
      address: "شوتشو، الصين",
      inquiry_subject: "طلب عرض سعر من BOB-LIFT",
      default_inquiry_text: "مرحباً BOB-LIFT، أود طلب عرض سعر.",
      header_top_bar_highlight: "حلول رفع عالمية.",
      header_top_bar_text: "يتم التصدير إلى أكثر من 80 دولة.",
      header_top_bar_link_label: "اعرف المزيد +",
      chrome_ui_language_menu_aria_label: "تغيير اللغة",
      chrome_ui_main_navigation_label: "التنقل الرئيسي",
      chrome_ui_logo_home_label: "صفحة BOB-LIFT الرئيسية",
      chrome_ui_logo_image_alt: "رافعات BOB-LIFT",
      chrome_ui_mobile_menu_aria_label: "فتح قائمة الجوال",
      chrome_ui_mobile_menu_eyebrow: "التنقل",
      chrome_ui_mobile_menu_title: "قائمة BOB-LIFT",
      chrome_ui_mobile_product_categories_label: "فئات المنتجات",
      chrome_ui_request_quote_label: "اطلب عرض سعر",
      chrome_ui_short_request_quote_label: "عرض سعر",
      chrome_ui_product_catalog_title: "استكشف كل المنتجات",
      chrome_ui_product_catalog_description: "تصفح حلول الرفع من BOB-LIFT.",
      chrome_ui_view_product_catalog_label: "عرض كتالوج المنتجات",
      chrome_ui_floating_quote_label: "عرض سعر",
      chrome_ui_floating_quote_tooltip: "اطلب عرض سعر",
      chrome_ui_floating_email_label: "البريد الإلكتروني",
      chrome_ui_floating_whats_app_label: "WhatsApp",
      chrome_ui_floating_call_label: "اتصال",
      chrome_ui_floating_we_chat_label: "WeChat",
      chrome_ui_we_chat_title: "رمز WeChat QR",
      chrome_ui_we_chat_description_template: "امسح الرمز أو ابحث عن المعرف: {wechatId}",
      chrome_ui_we_chat_qr_alt: "رمز WeChat QR الخاص بـ BOB-LIFT",
      chrome_ui_close_we_chat_label: "إغلاق رمز WeChat",
      chrome_ui_back_to_top_label: "العودة إلى الأعلى",
      footer_intro: "حلول رفع للمشاريع العالمية، مصممة للعمل طويل الأمد.",
      footer_contact_title: "اتصل بنا",
      copyright: "© 2026 BOB-LIFT. جميع الحقوق محفوظة.",
    },
  }),
  seedLocales("home_settings_locales", "home_settings", homeLocaleRows()),
  seedHomeArrays(),
  seedLocales("product_listing_ui_locales", "product_listing_ui", productListingLocaleRows()),
  seedLocales("product_detail_ui_locales", "product_detail_ui", productDetailLocaleRows()),
  seedLocales("page_text_settings_locales", "page_text_settings", pageTextLocaleRows()),
  seedProductListingArrays(),
  seedProductDetailArrays(),
  seedTranslationSettingsArrays(),
  seedQuoteWorkflowItems(),
  patchSeededLocaleText(),
].join("\n\n");

execFileSync("psql", [databaseUrl, "-v", "ON_ERROR_STOP=1", "-q"], {
  input: sql,
  stdio: ["pipe", "inherit", "inherit"],
});

console.log("已初始化前台后台设置：site/home/product listing/product detail/page text globals。");

function homeLocaleRows() {
  return {
    en: {
      home_seo_meta_title: "BOB-LIFT | Truck Mounted Cranes and Lifting Solutions",
      home_seo_meta_description: "BOB-LIFT supplies truck mounted cranes, marine cranes, aerial work platforms and lifting attachments for global projects.",
      home_hero_ui_aria_label: "Featured lifting solutions",
      home_hero_ui_previous_slide_label: "Previous banner",
      home_hero_ui_next_slide_label: "Next banner",
      home_hero_ui_slide_indicator_label_template: "Show banner {index}",
      home_product_section_label: "Main Products",
      home_product_section_title: "BOB-LIFT Crane Solutions",
      home_product_section_find_model_title: "Find Your Model",
      home_product_section_details_short_label: "Details",
      home_product_section_view_details_label: "View Details",
      home_product_section_back_to_models_label: "Back to Models",
      home_product_section_previous_categories_label: "Previous product categories",
      home_product_section_next_categories_label: "Next product categories",
      home_product_section_product_name_label: "Product Name",
      home_product_section_model_label: "Model",
      home_product_section_model_spec_label: "Model",
      home_product_section_capacity_spec_label: "Capacity",
      home_product_section_type_spec_label: "Type",
      home_product_section_system_spec_label: "System",
      home_product_section_system_value: "Hydraulic",
      home_product_section_visual_watermark_label: "BOB-LIFT",
      trust_bar_label: "Certified",
      trust_bar_title: "Quality Assurance",
      trust_bar_description: "Export-ready documentation.",
      about_section_label: "About BOB-LIFT",
      about_section_title: "Engineering Excellence. Powered by People.",
      about_section_text: "BOB-LIFT combines manufacturing, engineering and export support for practical lifting projects.",
      about_section_cta_label: "More About Us",
      about_section_gallery_thumbnail_label_template: "Show {title}",
      about_section_video_label: "Video",
      news_section_label: "News Center",
      news_section_title: "Latest Insights from BOB-LIFT.",
      news_section_cta_label: "View All News",
      news_section_read_more_label: "Read More",
      news_section_pinned_label: "Pinned News",
      news_section_background_word: "NEWS",
      final_cta_label: "Project Consultation",
      final_cta_title: "Need a Lifting Solution?",
      final_cta_button_label: "Request a Quote",
    },
    zh: {
      home_seo_meta_title: "BOB-LIFT | 随车吊与起重解决方案",
      home_seo_meta_description: "BOB-LIFT 提供随车起重机、船用起重机、高空作业平台和起重附件，服务全球工程项目。",
      home_hero_ui_aria_label: "重点起重解决方案",
      home_hero_ui_previous_slide_label: "上一张横幅",
      home_hero_ui_next_slide_label: "下一张横幅",
      home_hero_ui_slide_indicator_label_template: "显示第 {index} 张横幅",
      home_product_section_label: "主营产品",
      home_product_section_title: "BOB-LIFT 起重解决方案",
      home_product_section_find_model_title: "选择型号",
      home_product_section_details_short_label: "详情",
      home_product_section_view_details_label: "查看详情",
      home_product_section_back_to_models_label: "返回型号",
      home_product_section_previous_categories_label: "上一组产品分类",
      home_product_section_next_categories_label: "下一组产品分类",
      home_product_section_product_name_label: "产品名称",
      home_product_section_model_label: "型号",
      home_product_section_model_spec_label: "型号",
      home_product_section_capacity_spec_label: "起重能力",
      home_product_section_type_spec_label: "类型",
      home_product_section_system_spec_label: "系统",
      home_product_section_system_value: "液压",
      home_product_section_visual_watermark_label: "BOB-LIFT",
      trust_bar_label: "认证",
      trust_bar_title: "质量保障",
      trust_bar_description: "支持出口所需资料。",
      about_section_label: "关于 BOB-LIFT",
      about_section_title: "制造、工程与出口服务一体化。",
      about_section_text: "BOB-LIFT 为全球项目提供起重设备制造、方案匹配和出口支持。",
      about_section_cta_label: "了解我们",
      about_section_gallery_thumbnail_label_template: "显示 {title}",
      about_section_video_label: "视频",
      news_section_label: "新闻中心",
      news_section_title: "BOB-LIFT 最新动态。",
      news_section_cta_label: "查看全部新闻",
      news_section_read_more_label: "阅读更多",
      news_section_pinned_label: "重点新闻",
      news_section_background_word: "新闻",
      final_cta_label: "项目咨询",
      final_cta_title: "需要起重解决方案？",
      final_cta_button_label: "获取报价",
    },
    es: {
      home_seo_meta_title: "BOB-LIFT | Grúas sobre camión y soluciones de elevación",
      home_seo_meta_description: "BOB-LIFT suministra grúas sobre camión, grúas marinas, plataformas y accesorios de elevación.",
      home_hero_ui_aria_label: "Soluciones destacadas de elevación",
      home_hero_ui_previous_slide_label: "Banner anterior",
      home_hero_ui_next_slide_label: "Banner siguiente",
      home_hero_ui_slide_indicator_label_template: "Mostrar banner {index}",
      home_product_section_label: "Productos principales",
      home_product_section_title: "Soluciones de grúas BOB-LIFT",
      home_product_section_find_model_title: "Encuentre su modelo",
      home_product_section_details_short_label: "Detalles",
      home_product_section_view_details_label: "Ver detalles",
      home_product_section_back_to_models_label: "Volver a modelos",
      home_product_section_previous_categories_label: "Categorías anteriores",
      home_product_section_next_categories_label: "Categorías siguientes",
      home_product_section_product_name_label: "Producto",
      home_product_section_model_label: "Modelo",
      home_product_section_model_spec_label: "Modelo",
      home_product_section_capacity_spec_label: "Capacidad",
      home_product_section_type_spec_label: "Tipo",
      home_product_section_system_spec_label: "Sistema",
      home_product_section_system_value: "Hidráulico",
      home_product_section_visual_watermark_label: "BOB-LIFT",
      trust_bar_label: "Certificado",
      trust_bar_title: "Control de calidad",
      trust_bar_description: "Documentación lista para exportación.",
      about_section_label: "Sobre BOB-LIFT",
      about_section_title: "Fabricación, ingeniería y soporte de exportación.",
      about_section_text: "BOB-LIFT combina fabricación, selección técnica y soporte de exportación para proyectos globales.",
      about_section_cta_label: "Más sobre nosotros",
      about_section_gallery_thumbnail_label_template: "Mostrar {title}",
      about_section_video_label: "Video",
      news_section_label: "Noticias",
      news_section_title: "Novedades de BOB-LIFT.",
      news_section_cta_label: "Ver noticias",
      news_section_read_more_label: "Leer más",
      news_section_pinned_label: "Noticia destacada",
      news_section_background_word: "NOTICIAS",
      final_cta_label: "Consulta de proyecto",
      final_cta_title: "¿Necesita una solución de elevación?",
      final_cta_button_label: "Solicitar cotización",
    },
    ar: {
      home_seo_meta_title: "BOB-LIFT | رافعات شاحنات وحلول رفع",
      home_seo_meta_description: "توفر BOB-LIFT رافعات شاحنات ورافعات بحرية ومنصات عمل وملحقات رفع للمشاريع العالمية.",
      home_hero_ui_aria_label: "حلول رفع مميزة",
      home_hero_ui_previous_slide_label: "الشريحة السابقة",
      home_hero_ui_next_slide_label: "الشريحة التالية",
      home_hero_ui_slide_indicator_label_template: "عرض الشريحة {index}",
      home_product_section_label: "المنتجات الرئيسية",
      home_product_section_title: "حلول رافعات BOB-LIFT",
      home_product_section_find_model_title: "اختر الموديل",
      home_product_section_details_short_label: "تفاصيل",
      home_product_section_view_details_label: "عرض التفاصيل",
      home_product_section_back_to_models_label: "العودة إلى الموديلات",
      home_product_section_previous_categories_label: "فئات المنتجات السابقة",
      home_product_section_next_categories_label: "فئات المنتجات التالية",
      home_product_section_product_name_label: "اسم المنتج",
      home_product_section_model_label: "الموديل",
      home_product_section_model_spec_label: "الموديل",
      home_product_section_capacity_spec_label: "القدرة",
      home_product_section_type_spec_label: "النوع",
      home_product_section_system_spec_label: "النظام",
      home_product_section_system_value: "هيدروليكي",
      home_product_section_visual_watermark_label: "BOB-LIFT",
      trust_bar_label: "اعتماد",
      trust_bar_title: "ضمان الجودة",
      trust_bar_description: "وثائق جاهزة للتصدير.",
      about_section_label: "عن BOB-LIFT",
      about_section_title: "تصنيع وهندسة ودعم تصدير.",
      about_section_text: "تجمع BOB-LIFT بين التصنيع والاختيار الفني ودعم التصدير للمشاريع العالمية.",
      about_section_cta_label: "المزيد عنا",
      about_section_gallery_thumbnail_label_template: "عرض {title}",
      about_section_video_label: "فيديو",
      news_section_label: "الأخبار",
      news_section_title: "آخر أخبار BOB-LIFT.",
      news_section_cta_label: "عرض كل الأخبار",
      news_section_read_more_label: "اقرأ المزيد",
      news_section_pinned_label: "خبر مميز",
      news_section_background_word: "أخبار",
      final_cta_label: "استشارة مشروع",
      final_cta_title: "هل تحتاج إلى حل رفع؟",
      final_cta_button_label: "اطلب عرض سعر",
    },
  };
}

function productListingLocaleRows() {
  return {
    en: {
      product_listing_ui_meta_title: "Construction Equipment",
      product_listing_ui_meta_description: "Browse BOB-LIFT truck mounted cranes, marine cranes, aerial work platforms, mini dumpers and lifting attachments.",
      product_listing_ui_banner_label: "BOB-LIFT Product Range",
      product_listing_ui_banner_title: "Construction Equipment",
      product_listing_ui_banner_image_alt: "BOB-LIFT truck mounted crane",
      product_listing_ui_banner_watermark_label: "BOB-LIFT",
      product_listing_ui_result_summary_template: "Showing {shown} of {total} products",
      product_listing_ui_filter_button_label: "Filter",
      product_listing_ui_filters_title: "Filters",
      product_listing_ui_clear_all_label: "Clear All",
      product_listing_ui_close_filters_label: "Close filters",
      product_listing_ui_filter_groups_categories: "Product Category",
      product_listing_ui_filter_groups_capacities: "Lifting Capacity",
      product_listing_ui_filter_groups_boom_types: "Boom Type",
      product_listing_ui_filter_groups_applications: "Application",
      product_listing_ui_filter_groups_availability: "Availability",
      product_listing_ui_sort_label: "Sort by:",
      product_listing_ui_sort_options_recommended: "Recommended",
      product_listing_ui_sort_options_capacity_asc: "Capacity: Low to High",
      product_listing_ui_sort_options_capacity_desc: "Capacity: High to Low",
      product_listing_ui_sort_options_newest: "Newest",
      product_listing_ui_sort_options_popular: "Popular",
      product_listing_ui_view_mode_label: "Product view mode",
      product_listing_ui_grid_view_label: "Grid view",
      product_listing_ui_list_view_label: "List view",
      product_listing_ui_favorite_add_label: "Add to favorites",
      product_listing_ui_favorite_remove_label: "Remove from favorites",
      product_listing_ui_quick_view_label_template: "Quick view {product}",
      product_listing_ui_quick_view_close_label: "Close quick view",
      product_listing_ui_request_quote_label: "Request Quote",
      product_listing_ui_view_details_label: "View Details",
      product_listing_ui_empty_title: "No matching products found",
      product_listing_ui_empty_text: "Try adjusting your filters or contact us for a custom lifting solution.",
      product_listing_ui_clear_filters_button_label: "Clear Filters",
      product_listing_ui_previous_page_label: "Previous",
      product_listing_ui_next_page_label: "Next",
      product_listing_ui_pagination_label: "Product pagination",
      product_listing_ui_status_labels_in_stock: "In Stock",
      product_listing_ui_status_labels_best_seller: "Best Seller",
      product_listing_ui_status_labels_customizable: "Customizable",
      product_listing_ui_status_labels_pre_order: "Pre-Order",
      product_listing_ui_spec_labels_max_lift: "Max Lift",
      product_listing_ui_spec_labels_boom_length: "Boom Length",
      product_listing_ui_spec_labels_rotation: "Rotation",
      product_listing_ui_spec_labels_boom_type: "Boom Type",
      product_listing_ui_spec_labels_specification_status: "Specification Status",
      product_listing_ui_spec_labels_parameter_review: "Parameter Review",
    },
    zh: {
      product_listing_ui_meta_title: "产品中心",
      product_listing_ui_meta_description: "浏览 BOB-LIFT 随车起重机、船用起重机、高空作业平台、迷你自卸车和起重附件。",
      product_listing_ui_banner_label: "BOB-LIFT 产品系列",
      product_listing_ui_banner_title: "工程起重设备",
      product_listing_ui_banner_image_alt: "BOB-LIFT 随车起重机",
      product_listing_ui_banner_watermark_label: "BOB-LIFT",
      product_listing_ui_result_summary_template: "显示 {shown} / {total} 个产品",
      product_listing_ui_filter_button_label: "筛选",
      product_listing_ui_filters_title: "筛选条件",
      product_listing_ui_clear_all_label: "清空",
      product_listing_ui_close_filters_label: "关闭筛选",
      product_listing_ui_filter_groups_categories: "产品分类",
      product_listing_ui_filter_groups_capacities: "起重能力",
      product_listing_ui_filter_groups_boom_types: "臂架类型",
      product_listing_ui_filter_groups_applications: "应用场景",
      product_listing_ui_filter_groups_availability: "可售状态",
      product_listing_ui_sort_label: "排序：",
      product_listing_ui_sort_options_recommended: "推荐排序",
      product_listing_ui_sort_options_capacity_asc: "能力从低到高",
      product_listing_ui_sort_options_capacity_desc: "能力从高到低",
      product_listing_ui_sort_options_newest: "最新发布",
      product_listing_ui_sort_options_popular: "热门优先",
      product_listing_ui_view_mode_label: "产品视图模式",
      product_listing_ui_grid_view_label: "网格视图",
      product_listing_ui_list_view_label: "列表视图",
      product_listing_ui_favorite_add_label: "加入收藏",
      product_listing_ui_favorite_remove_label: "移出收藏",
      product_listing_ui_quick_view_label_template: "快速预览 {product}",
      product_listing_ui_quick_view_close_label: "关闭快速预览",
      product_listing_ui_request_quote_label: "获取报价",
      product_listing_ui_view_details_label: "查看详情",
      product_listing_ui_empty_title: "没有匹配产品",
      product_listing_ui_empty_text: "请调整筛选条件，或联系我们匹配定制方案。",
      product_listing_ui_clear_filters_button_label: "清空筛选",
      product_listing_ui_previous_page_label: "上一页",
      product_listing_ui_next_page_label: "下一页",
      product_listing_ui_pagination_label: "产品分页",
      product_listing_ui_status_labels_in_stock: "现货",
      product_listing_ui_status_labels_best_seller: "热销",
      product_listing_ui_status_labels_customizable: "可定制",
      product_listing_ui_status_labels_pre_order: "可预订",
      product_listing_ui_spec_labels_max_lift: "最大起重量",
      product_listing_ui_spec_labels_boom_length: "臂长",
      product_listing_ui_spec_labels_rotation: "回转",
      product_listing_ui_spec_labels_boom_type: "臂架类型",
      product_listing_ui_spec_labels_specification_status: "参数状态",
      product_listing_ui_spec_labels_parameter_review: "参数复核",
    },
    es: {
      product_listing_ui_meta_title: "Centro de productos",
      product_listing_ui_meta_description: "Explore grúas sobre camión, grúas marinas, plataformas, mini volquetes y accesorios BOB-LIFT.",
      product_listing_ui_banner_label: "Gama de productos BOB-LIFT",
      product_listing_ui_banner_title: "Equipos de elevación",
      product_listing_ui_banner_image_alt: "Grúa sobre camión BOB-LIFT",
      product_listing_ui_banner_watermark_label: "BOB-LIFT",
      product_listing_ui_result_summary_template: "Mostrando {shown} de {total} productos",
      product_listing_ui_filter_button_label: "Filtrar",
      product_listing_ui_filters_title: "Filtros",
      product_listing_ui_clear_all_label: "Limpiar",
      product_listing_ui_close_filters_label: "Cerrar filtros",
      product_listing_ui_filter_groups_categories: "Categoría",
      product_listing_ui_filter_groups_capacities: "Capacidad de elevación",
      product_listing_ui_filter_groups_boom_types: "Tipo de pluma",
      product_listing_ui_filter_groups_applications: "Aplicación",
      product_listing_ui_filter_groups_availability: "Disponibilidad",
      product_listing_ui_sort_label: "Ordenar:",
      product_listing_ui_sort_options_recommended: "Recomendado",
      product_listing_ui_sort_options_capacity_asc: "Capacidad: menor a mayor",
      product_listing_ui_sort_options_capacity_desc: "Capacidad: mayor a menor",
      product_listing_ui_sort_options_newest: "Más reciente",
      product_listing_ui_sort_options_popular: "Popular",
      product_listing_ui_view_mode_label: "Modo de vista",
      product_listing_ui_grid_view_label: "Vista de cuadrícula",
      product_listing_ui_list_view_label: "Vista de lista",
      product_listing_ui_favorite_add_label: "Agregar a favoritos",
      product_listing_ui_favorite_remove_label: "Quitar de favoritos",
      product_listing_ui_quick_view_label_template: "Vista rápida de {product}",
      product_listing_ui_quick_view_close_label: "Cerrar vista rápida",
      product_listing_ui_request_quote_label: "Solicitar cotización",
      product_listing_ui_view_details_label: "Ver detalles",
      product_listing_ui_empty_title: "No se encontraron productos",
      product_listing_ui_empty_text: "Ajuste los filtros o contáctenos para una solución personalizada.",
      product_listing_ui_clear_filters_button_label: "Limpiar filtros",
      product_listing_ui_previous_page_label: "Anterior",
      product_listing_ui_next_page_label: "Siguiente",
      product_listing_ui_pagination_label: "Paginación de productos",
      product_listing_ui_status_labels_in_stock: "En stock",
      product_listing_ui_status_labels_best_seller: "Más vendido",
      product_listing_ui_status_labels_customizable: "Personalizable",
      product_listing_ui_status_labels_pre_order: "Pedido anticipado",
      product_listing_ui_spec_labels_max_lift: "Carga máxima",
      product_listing_ui_spec_labels_boom_length: "Longitud de pluma",
      product_listing_ui_spec_labels_rotation: "Rotación",
      product_listing_ui_spec_labels_boom_type: "Tipo de pluma",
      product_listing_ui_spec_labels_specification_status: "Estado de especificaciones",
      product_listing_ui_spec_labels_parameter_review: "Revisión de parámetros",
    },
    ar: {
      product_listing_ui_meta_title: "مركز المنتجات",
      product_listing_ui_meta_description: "تصفح رافعات الشاحنات والرافعات البحرية ومنصات العمل والملحقات من BOB-LIFT.",
      product_listing_ui_banner_label: "مجموعة منتجات BOB-LIFT",
      product_listing_ui_banner_title: "معدات الرفع",
      product_listing_ui_banner_image_alt: "رافعة شاحنة BOB-LIFT",
      product_listing_ui_banner_watermark_label: "BOB-LIFT",
      product_listing_ui_result_summary_template: "عرض {shown} من {total} منتج",
      product_listing_ui_filter_button_label: "تصفية",
      product_listing_ui_filters_title: "الفلاتر",
      product_listing_ui_clear_all_label: "مسح الكل",
      product_listing_ui_close_filters_label: "إغلاق الفلاتر",
      product_listing_ui_filter_groups_categories: "فئة المنتج",
      product_listing_ui_filter_groups_capacities: "قدرة الرفع",
      product_listing_ui_filter_groups_boom_types: "نوع الذراع",
      product_listing_ui_filter_groups_applications: "التطبيق",
      product_listing_ui_filter_groups_availability: "التوفر",
      product_listing_ui_sort_label: "ترتيب:",
      product_listing_ui_sort_options_recommended: "موصى به",
      product_listing_ui_sort_options_capacity_asc: "القدرة من الأقل إلى الأعلى",
      product_listing_ui_sort_options_capacity_desc: "القدرة من الأعلى إلى الأقل",
      product_listing_ui_sort_options_newest: "الأحدث",
      product_listing_ui_sort_options_popular: "الأكثر طلباً",
      product_listing_ui_view_mode_label: "طريقة عرض المنتجات",
      product_listing_ui_grid_view_label: "عرض شبكي",
      product_listing_ui_list_view_label: "عرض قائمة",
      product_listing_ui_favorite_add_label: "إضافة إلى المفضلة",
      product_listing_ui_favorite_remove_label: "إزالة من المفضلة",
      product_listing_ui_quick_view_label_template: "عرض سريع {product}",
      product_listing_ui_quick_view_close_label: "إغلاق العرض السريع",
      product_listing_ui_request_quote_label: "اطلب عرض سعر",
      product_listing_ui_view_details_label: "عرض التفاصيل",
      product_listing_ui_empty_title: "لا توجد منتجات مطابقة",
      product_listing_ui_empty_text: "عدّل الفلاتر أو تواصل معنا لحل مخصص.",
      product_listing_ui_clear_filters_button_label: "مسح الفلاتر",
      product_listing_ui_previous_page_label: "السابق",
      product_listing_ui_next_page_label: "التالي",
      product_listing_ui_pagination_label: "ترقيم صفحات المنتجات",
      product_listing_ui_status_labels_in_stock: "متوفر",
      product_listing_ui_status_labels_best_seller: "الأكثر مبيعاً",
      product_listing_ui_status_labels_customizable: "قابل للتخصيص",
      product_listing_ui_status_labels_pre_order: "طلب مسبق",
      product_listing_ui_spec_labels_max_lift: "أقصى حمولة",
      product_listing_ui_spec_labels_boom_length: "طول الذراع",
      product_listing_ui_spec_labels_rotation: "الدوران",
      product_listing_ui_spec_labels_boom_type: "نوع الذراع",
      product_listing_ui_spec_labels_specification_status: "حالة المواصفات",
      product_listing_ui_spec_labels_parameter_review: "مراجعة المعلمات",
    },
  };
}

function productDetailLocaleRows() {
  const rows = {
    en: detailRow("Request Quote", "Download Catalog", "View Technical Specs", "Overview", "Key Features", "Technical Specifications", "Product Details", "Catalog", "Applications", "FAQ", "Product detail sections", "Product page navigation", "Built for reliable truck-mounted lifting work.", "{description} The configuration focuses on smooth operation, stable control and practical export use for contractors, fleet buyers and project teams.", "Technical Specifications", "Product Catalog", "{model} Product Catalog", "Download Center", "Download Product Catalog", "Request PDF by Email", "Product Detail Gallery", "Product Detail View", "Engineering visuals for product evaluation.", "Main product view", "Product detail image", "Specification drawing", "Needs review", "In review", "Verified from source", "Changes required", "Available", "Available on request", "Model", "Max Lift", "Boom Length", "Rotation", "Boom Type", "Parameter Review", "Need a truck crane matched to your chassis and lifting task?", "Send your working radius, load requirement, destination country, and truck chassis information. BOB-LIFT will help match a practical configuration.", "Contact Engineer"),
    zh: detailRow("获取报价", "下载产品资料", "查看技术参数", "概览", "核心卖点", "技术参数", "产品详情", "资料", "应用场景", "常见问题", "产品详情区块", "产品页侧边导航", "适合稳定随车起重作业。", "{description} 该配置重点关注平稳操作、稳定控制和出口项目中的实用性。", "技术参数", "产品资料", "{model} 产品资料", "下载中心", "下载产品资料", "邮件获取 PDF", "产品详情图库", "产品详情视图", "用于产品评估的工程图片。", "产品主图", "产品详情图", "参数图纸", "待复核", "复核中", "已按来源核对", "退回修改", "可定制", "可按需定制", "型号", "最大起重量", "臂长", "回转", "臂架类型", "参数复核", "需要匹配底盘和工况的随车吊？", "发送工作半径、载荷需求、目的国和底盘信息，BOB-LIFT 将协助匹配实用配置。", "联系工程师"),
    es: detailRow("Solicitar cotización", "Descargar catálogo", "Ver especificaciones", "Resumen", "Características clave", "Especificaciones técnicas", "Detalles del producto", "Catálogo", "Aplicaciones", "FAQ", "Secciones del producto", "Navegación de producto", "Diseñado para trabajos de elevación confiables.", "{description} La configuración se centra en operación suave, control estable y uso práctico para exportación.", "Especificaciones técnicas", "Catálogo de producto", "Catálogo {model}", "Centro de descargas", "Descargar catálogo", "Solicitar PDF por email", "Galería del producto", "Vista del producto", "Imágenes técnicas para evaluación.", "Vista principal", "Imagen de detalle", "Plano de especificaciones", "Pendiente de revisión", "En revisión", "Verificado con fuente", "Requiere cambios", "Disponible", "Disponible bajo solicitud", "Modelo", "Carga máxima", "Longitud de pluma", "Rotación", "Tipo de pluma", "Revisión de parámetros", "¿Necesita una grúa adaptada a su chasis?", "Envíe radio de trabajo, carga, país de destino y datos del chasis. BOB-LIFT ayudará a definir la configuración.", "Contactar ingeniero"),
    ar: detailRow("اطلب عرض سعر", "تحميل الكتالوج", "عرض المواصفات الفنية", "نظرة عامة", "المزايا الرئيسية", "المواصفات الفنية", "تفاصيل المنتج", "الكتالوج", "التطبيقات", "الأسئلة الشائعة", "أقسام تفاصيل المنتج", "تنقل صفحة المنتج", "مصمم لأعمال رفع موثوقة.", "{description} يركز هذا التكوين على التشغيل السلس والتحكم المستقر والاستخدام العملي للتصدير.", "المواصفات الفنية", "كتالوج المنتج", "كتالوج {model}", "مركز التحميل", "تحميل كتالوج المنتج", "طلب PDF بالبريد", "معرض تفاصيل المنتج", "عرض تفاصيل المنتج", "صور هندسية لتقييم المنتج.", "الصورة الرئيسية", "صورة تفاصيل المنتج", "رسم المواصفات", "بحاجة إلى مراجعة", "قيد المراجعة", "تم التحقق من المصدر", "يتطلب تعديلات", "متاح", "متاح عند الطلب", "الموديل", "أقصى حمولة", "طول الذراع", "الدوران", "نوع الذراع", "مراجعة المعلمات", "هل تحتاج إلى رافعة مناسبة لشاسيه ومهمة الرفع؟", "أرسل نصف قطر العمل والحمولة وبلد الوجهة ومعلومات الشاسيه، وسيساعدك فريق BOB-LIFT في اختيار التكوين.", "تواصل مع مهندس"),
  };
  rows.zh.product_detail_ui_catalog_description_template = "{catalogTitle} 包含核心参数、作业范围信息和项目评估所需的配置说明。";
  rows.es.product_detail_ui_catalog_description_template = "{catalogTitle} incluye especificaciones clave, alcance de trabajo y notas de configuración para evaluación.";
  rows.ar.product_detail_ui_catalog_description_template = "يتضمن {catalogTitle} المواصفات الأساسية ومعلومات نطاق العمل وملاحظات التكوين للتقييم.";
  rows.zh.product_detail_ui_gallery_empty_text = "集中展示臂架结构、安装布局、液压系统、作业范围和底盘安装细节。";
  rows.es.product_detail_ui_gallery_empty_text = "Muestra estructura, instalación, sistema hidráulico, rango de trabajo y montaje en chasis.";
  rows.ar.product_detail_ui_gallery_empty_text = "يعرض هيكل الذراع وتخطيط التركيب والنظام الهيدروليكي ونطاق العمل وتفاصيل تثبيت الشاسيه.";
  return rows;
}

function detailRow(requestQuote, downloadCatalog, viewSpecs, overview, features, technical, details, catalog, applications, faq, anchorNav, sideNav, overviewTitle, overviewTextTemplate, technicalTitle, catalogCoverTitle, catalogTitleTemplate, downloadCenter, downloadCatalogTitle, requestPdf, galleryTitle, galleryEmptyLabel, galleryEmptyTitle, mainImage, detailImage, drawing, needsReview, inReview, verified, changes, available, availableOnRequest, model, maxLift, boomLength, rotation, boomType, parameterReview, quoteTitle, quoteText, contactEngineer) {
  return {
    product_detail_ui_request_quote_label: requestQuote,
    product_detail_ui_download_catalog_label: downloadCatalog,
    product_detail_ui_view_technical_specs_label: viewSpecs,
    product_detail_ui_anchor_labels_overview: overview,
    product_detail_ui_anchor_labels_features: features,
    product_detail_ui_anchor_labels_technical: technical,
    product_detail_ui_anchor_labels_details: details,
    product_detail_ui_anchor_labels_catalog: catalog,
    product_detail_ui_anchor_labels_applications: applications,
    product_detail_ui_anchor_labels_faq: faq,
    product_detail_ui_anchor_labels_quote: requestQuote,
    product_detail_ui_aria_labels_anchor_nav: anchorNav,
    product_detail_ui_aria_labels_side_nav: sideNav,
    product_detail_ui_overview_title: overviewTitle,
    product_detail_ui_overview_text_template: overviewTextTemplate,
    product_detail_ui_technical_title: technicalTitle,
    product_detail_ui_catalog_brand_label: "BOB-LIFT",
    product_detail_ui_catalog_cover_title: catalogCoverTitle,
    product_detail_ui_catalog_title_template: catalogTitleTemplate,
    product_detail_ui_catalog_cover_alt_template: "{product} catalog cover",
    product_detail_ui_download_center_label: downloadCenter,
    product_detail_ui_download_catalog_title: downloadCatalogTitle,
    product_detail_ui_catalog_description_template: "{catalogTitle} includes core specifications, working range information, and configuration notes for project evaluation.",
    product_detail_ui_request_pdf_label: requestPdf,
    product_detail_ui_gallery_title: galleryTitle,
    product_detail_ui_gallery_empty_label: galleryEmptyLabel,
    product_detail_ui_gallery_empty_title: galleryEmptyTitle,
    product_detail_ui_gallery_empty_text: "Present boom structure, installation layout, hydraulic system, operating range, and chassis mounting details in one focused section.",
    product_detail_ui_gallery_main_image_label: mainImage,
    product_detail_ui_gallery_detail_image_label: detailImage,
    product_detail_ui_gallery_drawing_label: drawing,
    product_detail_ui_applications_title: applications,
    product_detail_ui_review_status_labels_needs_review: needsReview,
    product_detail_ui_review_status_labels_in_review: inReview,
    product_detail_ui_review_status_labels_verified_from_source: verified,
    product_detail_ui_review_status_labels_changes_required: changes,
    product_detail_ui_customization_labels_available: available,
    product_detail_ui_customization_labels_available_on_request: availableOnRequest,
    product_detail_ui_spec_labels_model: model,
    product_detail_ui_spec_labels_max_lift: maxLift,
    product_detail_ui_spec_labels_max_lifting_capacity: maxLift,
    product_detail_ui_spec_labels_boom_length: boomLength,
    product_detail_ui_spec_labels_max_boom_length: boomLength,
    product_detail_ui_spec_labels_rotation: rotation,
    product_detail_ui_spec_labels_boom_type: boomType,
    product_detail_ui_spec_labels_application: applications,
    product_detail_ui_spec_labels_customization: available,
    product_detail_ui_spec_labels_specification_status: needsReview,
    product_detail_ui_spec_labels_parameter_review: parameterReview,
    product_detail_ui_faq_title: faq,
    product_detail_ui_quote_title: quoteTitle,
    product_detail_ui_quote_text: quoteText,
    product_detail_ui_contact_engineer_label: contactEngineer,
  };
}

function pageTextLocaleRows() {
  return {
    en: { contact_page_ui_meta_title: "Contact Us | BOB-LIFT", quote_page_ui_meta_title: "Request Quote | BOB-LIFT", quote_form_ui_label: "Request Quote", quote_form_ui_title: "Tell us your lifting requirement.", quote_form_ui_buttons_save: "Save Inquiry Draft", quote_form_ui_buttons_saving: "Saving Inquiry...", quote_form_ui_buttons_email: "Send Email", quote_form_ui_buttons_whatsapp: "WhatsApp", quote_form_ui_messages_success_title: "Inquiry saved in the admin.", quote_form_ui_messages_error_fallback: "Inquiry could not be saved." },
    zh: { contact_page_ui_meta_title: "联系我们 | BOB-LIFT", quote_page_ui_meta_title: "获取报价 | BOB-LIFT", quote_form_ui_label: "获取报价", quote_form_ui_title: "告诉我们您的起重需求。", quote_form_ui_buttons_save: "保存询盘", quote_form_ui_buttons_saving: "正在保存询盘...", quote_form_ui_buttons_email: "发送邮件", quote_form_ui_buttons_whatsapp: "WhatsApp", quote_form_ui_messages_success_title: "询盘已保存到后台。", quote_form_ui_messages_error_fallback: "询盘保存失败。" },
    es: { contact_page_ui_meta_title: "Contacto | BOB-LIFT", quote_page_ui_meta_title: "Solicitar cotización | BOB-LIFT", quote_form_ui_label: "Solicitar cotización", quote_form_ui_title: "Cuéntenos su necesidad de elevación.", quote_form_ui_buttons_save: "Guardar consulta", quote_form_ui_buttons_saving: "Guardando consulta...", quote_form_ui_buttons_email: "Enviar email", quote_form_ui_buttons_whatsapp: "WhatsApp", quote_form_ui_messages_success_title: "Consulta guardada en el panel.", quote_form_ui_messages_error_fallback: "No se pudo guardar la consulta." },
    ar: { contact_page_ui_meta_title: "اتصل بنا | BOB-LIFT", quote_page_ui_meta_title: "اطلب عرض سعر | BOB-LIFT", quote_form_ui_label: "اطلب عرض سعر", quote_form_ui_title: "أخبرنا بمتطلبات الرفع.", quote_form_ui_buttons_save: "حفظ الاستفسار", quote_form_ui_buttons_saving: "جارٍ حفظ الاستفسار...", quote_form_ui_buttons_email: "إرسال بريد", quote_form_ui_buttons_whatsapp: "WhatsApp", quote_form_ui_messages_success_title: "تم حفظ الاستفسار في لوحة الإدارة.", quote_form_ui_messages_error_fallback: "تعذر حفظ الاستفسار." },
  };
}

function seedProductListingArrays() {
  const chunks = [
    seedChildRows("product_listing_ui", "product_listing_ui_product_listing_ui_banner_paragraphs", [
      { id: "seed-product-listing-banner-1", order: 0, values: {}, locales: {
        en: { text: "Explore reliable BOB-LIFT lifting and construction machinery for global projects." },
        zh: { text: "浏览适合全球工程项目的 BOB-LIFT 起重设备与配套机械。" },
        es: { text: "Explore equipos de elevación BOB-LIFT confiables para proyectos globales." },
        ar: { text: "تصفح معدات وحلول رفع موثوقة من BOB-LIFT للمشاريع العالمية." },
      } },
    ], "product_listing_ui_product_listing_ui_banner_paragraphs_locales"),
    seedChildRows("product_listing_ui", "cap_ranges", [
      dictRow("seed-cap-0-5", 0, { key: "0 - 5 tons", min: 0, max: 5, enabled: true, sort_order: 10 }, { en: "0 - 5 tons", zh: "0 - 5 吨", es: "0 - 5 toneladas", ar: "0 - 5 طن" }),
      dictRow("seed-cap-5-10", 1, { key: "5 - 10 tons", min: 5, max: 10, enabled: true, sort_order: 20 }, { en: "5 - 10 tons", zh: "5 - 10 吨", es: "5 - 10 toneladas", ar: "5 - 10 طن" }),
      dictRow("seed-cap-10-20", 2, { key: "10 - 20 tons", min: 10, max: 20, enabled: true, sort_order: 30 }, { en: "10 - 20 tons", zh: "10 - 20 吨", es: "10 - 20 toneladas", ar: "10 - 20 طن" }),
      dictRow("seed-cap-20-50", 3, { key: "20 - 50 tons", min: 20, max: 50, enabled: true, sort_order: 40 }, { en: "20 - 50 tons", zh: "20 - 50 吨", es: "20 - 50 toneladas", ar: "20 - 50 طن" }),
      dictRow("seed-cap-50-plus", 4, { key: "50+ tons", min: 50, max: null, enabled: true, sort_order: 50 }, { en: "50+ tons", zh: "50 吨以上", es: "50+ toneladas", ar: "أكثر من 50 طن" }),
    ], "cap_ranges_locales"),
    seedChildRows("product_listing_ui", "boom_dict", [
      dictRow("seed-boom-telescopic", 0, { key: "telescopic-boom", enabled: true, sort_order: 10 }, { en: "Telescopic Boom", zh: "伸缩臂", es: "Pluma telescópica", ar: "ذراع تلسكوبي" }),
      dictRow("seed-boom-knuckle", 1, { key: "knuckle-boom", enabled: true, sort_order: 20 }, { en: "Knuckle Boom", zh: "折臂", es: "Pluma articulada", ar: "ذراع مفصلي" }),
      dictRow("seed-boom-straight", 2, { key: "straight-boom", enabled: true, sort_order: 30 }, { en: "Straight Boom", zh: "直臂", es: "Pluma recta", ar: "ذراع مستقيم" }),
      dictRow("seed-boom-fly-jib", 3, { key: "fly-jib", enabled: true, sort_order: 40 }, { en: "Fly Jib", zh: "飞臂吊", es: "Plumín", ar: "ذراع إضافي" }),
      dictRow("seed-boom-custom", 4, { key: "custom-solution", enabled: true, sort_order: 50 }, { en: "Custom Solution", zh: "定制方案", es: "Solución personalizada", ar: "حل مخصص" }),
    ], "boom_dict_locales"),
    seedChildRows("product_listing_ui", "status_dict", [
      dictRow("seed-status-stock", 0, { key: "in-stock", enabled: true, sort_order: 10 }, { en: "In Stock", zh: "现货", es: "En stock", ar: "متوفر" }),
      dictRow("seed-status-best", 1, { key: "best-seller", enabled: true, sort_order: 20 }, { en: "Best Seller", zh: "热销", es: "Más vendido", ar: "الأكثر مبيعاً" }),
      dictRow("seed-status-custom", 2, { key: "customizable", enabled: true, sort_order: 30 }, { en: "Customizable", zh: "可定制", es: "Personalizable", ar: "قابل للتخصيص" }),
      dictRow("seed-status-preorder", 3, { key: "pre-order", enabled: true, sort_order: 40 }, { en: "Pre-Order", zh: "可预订", es: "Pedido anticipado", ar: "طلب مسبق" }),
    ], "status_dict_locales"),
    seedChildRows("product_listing_ui", "app_dict", [
      appDictRow("seed-app-construction", 0, "construction", "/images/construction-scene.jpg", "construction", { en: ["Construction", "Construction", "Building sites, structural lifting, material handling."], zh: ["建筑施工", "建筑施工", "建筑工地、结构吊装和材料搬运。"], es: ["Construcción", "Construcción", "Obras, izaje estructural y manejo de materiales."], ar: ["البناء", "البناء", "مواقع البناء والرفع الإنشائي ومناولة المواد."] }),
      appDictRow("seed-app-logistics", 1, "logistics", "/images/cta-lift-photo.png", "truck", { en: ["Logistics", "Logistics", "Loading, unloading, and cargo handling at various sites."], zh: ["物流运输", "物流运输", "装卸、转运和现场货物搬运。"], es: ["Logística", "Logística", "Carga, descarga y manejo de carga en distintos sitios."], ar: ["اللوجستيات", "اللوجستيات", "التحميل والتفريغ ومناولة البضائع في مواقع مختلفة."] }),
      appDictRow("seed-app-municipal", 2, "municipal-engineering", "/images/global-network-blue.jpg", "building", { en: ["Municipal Engineering", "Municipal Engineering", "Urban construction, road maintenance, and public facility projects."], zh: ["市政工程", "市政工程", "城市建设、道路维护和公共设施项目。"], es: ["Ingeniería municipal", "Ingeniería municipal", "Construcción urbana, mantenimiento vial e instalaciones públicas."], ar: ["الهندسة البلدية", "الهندسة البلدية", "البناء الحضري وصيانة الطرق ومشاريع المرافق العامة."] }),
      appDictRow("seed-app-marine", 3, "marine", "/images/marine-scene.jpg", "ship", { en: ["Marine", "Marine", "Deck service, port handling, and offshore lifting operations."], zh: ["船舶/码头", "船舶与码头", "甲板作业、港口搬运和水上起重。"], es: ["Marino", "Marino", "Servicio en cubierta, puertos y operaciones offshore."], ar: ["بحري", "بحري", "خدمة السطح ومناولة الموانئ وعمليات الرفع البحرية."] }),
      appDictRow("seed-app-agriculture", 4, "agriculture", "/images/telescopic-banner.jpg", "tractor", { en: ["Agriculture", "Agriculture", "Farm loading, field transport, and rural equipment support."], zh: ["农业", "农业", "农场装卸、田间运输和乡村设备支持。"], es: ["Agricultura", "Agricultura", "Carga agrícola, transporte en campo y soporte rural."], ar: ["الزراعة", "الزراعة", "تحميل المزارع والنقل الحقلي ودعم المعدات الريفية."] }),
      appDictRow("seed-app-mining", 5, "mining", "/images/factory-workshop.jpg", "wrench", { en: ["Mining", "Mining", "Remote site handling and heavy material support work."], zh: ["矿山", "矿山", "偏远现场搬运和重物支持作业。"], es: ["Minería", "Minería", "Manejo en sitios remotos y soporte de materiales pesados."], ar: ["التعدين", "التعدين", "مناولة المواقع البعيدة ودعم المواد الثقيلة."] }),
    ], "app_dict_locales"),
  ];

  return chunks.join("\n");
}

function seedHomeArrays() {
  return [
    seedChildRows("home_settings", "home_settings_home_hero_slides", [
      homeHeroSlideRow("seed-hero-marine", 0, "/images/marine-scene.jpg"),
      homeHeroSlideRow("seed-hero-truck", 1, "/images/telescopic-banner.jpg"),
      homeHeroSlideRow("seed-hero-knuckle", 2, "/images/knuckle-banner.jpg"),
    ], "home_settings_home_hero_slides_locales"),
    seedChildRows("home_settings", "home_settings_trust_bar_marks", [
      {
        id: "seed-trust-iso",
        order: 0,
        values: { logo_path: "/certifications/iso.svg" },
        locales: {
          en: { name: "Quality Management" },
          zh: { name: "质量管理体系" },
          es: { name: "Gestión de calidad" },
          ar: { name: "إدارة الجودة" },
        },
      },
      {
        id: "seed-trust-ce",
        order: 1,
        values: { logo_path: "/certifications/ce.svg" },
        locales: {
          en: { name: "European Conformity" },
          zh: { name: "欧盟 CE 合规" },
          es: { name: "Conformidad europea" },
          ar: { name: "المطابقة الأوروبية" },
        },
      },
      {
        id: "seed-trust-eac",
        order: 2,
        values: { logo_path: "/certifications/eac.svg" },
        locales: {
          en: { name: "Russia / EAEU Conformity" },
          zh: { name: "俄罗斯 / 欧亚合规" },
          es: { name: "Conformidad Rusia / EAEU" },
          ar: { name: "مطابقة روسيا / الاتحاد الأوراسي" },
        },
      },
      {
        id: "seed-trust-sgs",
        order: 3,
        values: { logo_path: "/certifications/sgs.svg" },
        locales: {
          en: { name: "Inspection & Certification" },
          zh: { name: "检验与认证" },
          es: { name: "Inspección y certificación" },
          ar: { name: "الفحص والاعتماد" },
        },
      },
    ], "home_settings_trust_bar_marks_locales"),
    seedChildRows("home_settings", "home_settings_about_section_gallery", [
      homeGalleryRow("seed-about-gallery-truck", 0, "/images/truck-mounted-crane.png", false, {
        en: "Truck mounted crane",
        zh: "随车起重机",
        es: "Grúa sobre camión",
        ar: "رافعة مثبتة على شاحنة",
      }),
      homeGalleryRow("seed-about-gallery-factory", 1, "/images/factory-workshop.jpg", true, {
        en: "BOB-LIFT factory video preview",
        zh: "BOB-LIFT 工厂视频预览",
        es: "Vista previa de la fábrica BOB-LIFT",
        ar: "معاينة فيديو مصنع BOB-LIFT",
      }),
      homeGalleryRow("seed-about-gallery-marine", 2, "/images/marine-scene.jpg", false, {
        en: "Marine crane project",
        zh: "船用起重机项目",
        es: "Proyecto de grúa marina",
        ar: "مشروع رافعة بحرية",
      }),
      homeGalleryRow("seed-about-gallery-telescopic", 3, "/images/telescopic-banner.jpg", false, {
        en: "Telescopic boom crane",
        zh: "伸缩臂起重机",
        es: "Grúa de pluma telescópica",
        ar: "رافعة بذراع تلسكوبي",
      }),
      homeGalleryRow("seed-about-gallery-knuckle", 4, "/images/knuckle-banner.jpg", false, {
        en: "Knuckle boom crane",
        zh: "折臂起重机",
        es: "Grúa de pluma articulada",
        ar: "رافعة بذراع مفصلي",
      }),
    ], "home_settings_about_section_gallery_locales"),
    seedChildRows("home_settings", "home_settings_about_section_stats", [
      homeStatRow("seed-about-stat-years", 0, "award", {
        en: ["35+", "Years Experience"],
        zh: ["35+", "行业经验"],
        es: ["35+", "Años de experiencia"],
        ar: ["35+", "سنوات خبرة"],
      }),
      homeStatRow("seed-about-stat-countries", 1, "globe", {
        en: ["120+", "Export Countries"],
        zh: ["120+", "出口国家和地区"],
        es: ["120+", "Países de exportación"],
        ar: ["120+", "دول التصدير"],
      }),
      homeStatRow("seed-about-stat-cranes", 2, "package-check", {
        en: ["9999+", "Cranes Exported"],
        zh: ["9999+", "出口起重设备"],
        es: ["9999+", "Grúas exportadas"],
        ar: ["9999+", "رافعات مصدرة"],
      }),
    ], "home_settings_about_section_stats_locales"),
    seedChildRows("home_settings", "home_settings_about_section_timeline", [
      homeTimelineRow("seed-about-timeline-founded", 0, {
        en: "1989 Company Founded",
        zh: "1989 公司成立",
        es: "1989 Fundación de la empresa",
        ar: "1989 تأسيس الشركة",
      }),
      homeTimelineRow("seed-about-timeline-export", 1, {
        en: "2005 Global Export Expansion",
        zh: "2005 全球出口业务拓展",
        es: "2005 Expansión global de exportación",
        ar: "2005 توسع التصدير العالمي",
      }),
      homeTimelineRow("seed-about-timeline-rd", 2, {
        en: "2015 Advanced R&D Center Established",
        zh: "2015 建立先进研发中心",
        es: "2015 Centro avanzado de I+D",
        ar: "2015 إنشاء مركز بحث وتطوير متقدم",
      }),
      homeTimelineRow("seed-about-timeline-smart", 3, {
        en: "2022 Smart Manufacturing Upgraded",
        zh: "2022 智能制造升级",
        es: "2022 Actualización de fabricación inteligente",
        ar: "2022 ترقية التصنيع الذكي",
      }),
    ], "home_settings_about_section_timeline_locales"),
  ].join("\n");
}

function seedProductDetailArrays() {
  return [
    seedChildRows("product_detail_ui", "product_detail_ui_product_detail_ui_hotspots", [
      labelRow("seed-hotspot-boom", 0, { en: "Telescopic Boom", zh: "伸缩臂", es: "Pluma telescópica", ar: "ذراع تلسكوبي" }),
      labelRow("seed-hotspot-cylinder", 1, { en: "Hydraulic Cylinder", zh: "液压油缸", es: "Cilindro hidráulico", ar: "أسطوانة هيدروليكية" }),
      labelRow("seed-hotspot-outrigger", 2, { en: "Outriggers", zh: "支腿", es: "Estabilizadores", ar: "الدعامات" }),
      labelRow("seed-hotspot-control", 3, { en: "Control System", zh: "控制系统", es: "Sistema de control", ar: "نظام التحكم" }),
      labelRow("seed-hotspot-slewing", 4, { en: "Slewing Mechanism", zh: "回转机构", es: "Mecanismo de giro", ar: "آلية الدوران" }),
      labelRow("seed-hotspot-base", 5, { en: "Truck Mounting Base", zh: "底盘安装座", es: "Base de montaje", ar: "قاعدة تركيب الشاحنة" }),
    ], "product_detail_ui_product_detail_ui_hotspots_locales"),
    seedChildRows("product_detail_ui", "product_detail_ui_product_detail_ui_default_features", [
      featureRow("seed-feature-boom", 0, { en: ["Flexible Boom Operation", "Telescopic boom extends for versatile lifting in multiple working conditions."], zh: ["灵活臂架作业", "伸缩臂可适应多种吊装工况。"], es: ["Operación flexible", "La pluma telescópica se extiende para distintas condiciones de trabajo."], ar: ["تشغيل مرن للذراع", "يمتد الذراع التلسكوبي ليناسب ظروف رفع متعددة."] }),
      featureRow("seed-feature-stable", 1, { en: ["Stable & Safe Lifting", "Outriggers support stable operation for material handling and site work."], zh: ["稳定安全吊装", "支腿提升现场搬运和吊装作业稳定性。"], es: ["Elevación estable y segura", "Los estabilizadores ayudan en trabajos de carga y obra."], ar: ["رفع مستقر وآمن", "تدعم الدعامات التشغيل المستقر في مناولة المواد والعمل الميداني."] }),
      featureRow("seed-feature-application", 2, { en: ["Wide Application", "Designed for construction, logistics, utility work and municipal projects."], zh: ["应用范围广", "适用于建筑、物流、市政和公用工程项目。"], es: ["Aplicación amplia", "Diseñada para construcción, logística y proyectos municipales."], ar: ["تطبيق واسع", "مصممة للبناء واللوجستيات والمرافق والمشاريع البلدية."] }),
      featureRow("seed-feature-export", 3, { en: ["Export-Ready Configuration", "Prepared for overseas projects with practical documentation and delivery support."], zh: ["出口配置完善", "支持海外项目所需资料和交付配合。"], es: ["Configuración para exportación", "Preparada para proyectos internacionales con documentación práctica."], ar: ["تكوين جاهز للتصدير", "مجهزة للمشاريع الخارجية مع وثائق ودعم تسليم عملي."] }),
    ], "product_detail_ui_product_detail_ui_default_features_locales"),
    seedChildRows("product_detail_ui", "product_detail_ui_product_detail_ui_default_gallery_labels", [
      labelRow("seed-gallery-full", 0, { en: "Full crane structure diagram", zh: "整机结构图", es: "Diagrama completo de la grúa", ar: "مخطط هيكل الرافعة الكامل" }),
      labelRow("seed-gallery-boom", 1, { en: "Boom extension detail", zh: "臂架伸缩细节", es: "Detalle de extensión de pluma", ar: "تفاصيل امتداد الذراع" }),
      labelRow("seed-gallery-outrigger", 2, { en: "Outrigger stability detail", zh: "支腿稳定细节", es: "Detalle de estabilizadores", ar: "تفاصيل استقرار الدعامات" }),
      labelRow("seed-gallery-control", 3, { en: "Control system detail", zh: "控制系统细节", es: "Detalle del sistema de control", ar: "تفاصيل نظام التحكم" }),
      labelRow("seed-gallery-mounting", 4, { en: "Installation and mounting detail", zh: "安装与固定细节", es: "Detalle de instalación y montaje", ar: "تفاصيل التركيب والتثبيت" }),
    ], "product_detail_ui_product_detail_ui_default_gallery_labels_loca"),
    seedChildRows("product_detail_ui", "product_detail_ui_product_detail_ui_default_faqs", [
      faqRow("seed-faq-chassis", 0, { en: ["Which truck chassis can be matched with this model?", "Send the working conditions, destination and chassis details to BOB-LIFT. The engineering team will confirm a practical configuration before quotation."], zh: ["这款产品能匹配哪些底盘？", "请提供工况、目的地和底盘信息，BOB-LIFT 工程团队会在报价前确认可行配置。"], es: ["¿Qué chasis puede combinarse con este modelo?", "Envíe condiciones de trabajo, destino y datos del chasis. El equipo técnico confirmará la configuración antes de cotizar."], ar: ["ما هي شاسيهات الشاحنات المناسبة لهذا الموديل؟", "أرسل ظروف العمل والوجهة وتفاصيل الشاسيه، وسيؤكد الفريق الهندسي التكوين قبل عرض السعر."] }),
      faqRow("seed-faq-custom", 1, { en: ["Can the crane be customized for our working conditions?", "Yes. Share load, radius, chassis and environment details so the engineering team can evaluate the configuration."], zh: ["能按工况定制吗？", "可以。请提供载荷、工作半径、底盘和现场环境，工程团队会评估配置。"], es: ["¿Se puede personalizar para nuestras condiciones?", "Sí. Comparta carga, radio, chasis y ambiente para evaluar la configuración."], ar: ["هل يمكن تخصيص الرافعة حسب ظروف العمل؟", "نعم. شارك الحمولة ونصف القطر والشاسيه وبيئة العمل لتقييم التكوين."] }),
      faqRow("seed-faq-delivery", 2, { en: ["What is the typical delivery time?", "Delivery depends on model, configuration and production schedule. Confirm the required model and destination for a clear estimate."], zh: ["通常交期多久？", "交期取决于型号、配置和生产排期；确认型号和目的地后可给出明确预估。"], es: ["¿Cuál es el plazo de entrega?", "Depende del modelo, configuración y producción. Confirme modelo y destino para una estimación clara."], ar: ["ما مدة التسليم المعتادة؟", "تعتمد على الموديل والتكوين وجدول الإنتاج. أكد الموديل والوجهة للحصول على تقدير واضح."] }),
    ], "product_detail_ui_product_detail_ui_default_faqs_locales"),
  ].join("\n");
}

function seedTranslationSettingsArrays() {
  const targetLocales = ["zh", "es", "ar", "fr", "de", "pt", "ru", "tr", "id", "vi", "th", "hi", "ko"];
  const targetSql = targetLocales.map((locale, index) => `INSERT INTO "translation_settings_target_locales" ("order", "parent_id", "value")
SELECT ${index}, id, ${sqlValue(locale)}
FROM "translation_settings"
WHERE NOT EXISTS (
  SELECT 1
  FROM "translation_settings_target_locales"
  WHERE "parent_id" = "translation_settings"."id" AND "value" = ${sqlValue(locale)}
)
ORDER BY id
LIMIT 1;`).join("\n");

  const protectedTerms = ["BOB-LIFT", "SQ", "ZB", "FY", "ZM"];
  const protectedSql = protectedTerms.map((term, index) => `INSERT INTO "translation_settings_protected_terms" ("_order", "_parent_id", "id", "term")
SELECT ${index + 1}, id, ${sqlValue(`protected-${index + 1}`)}, ${sqlValue(term)}
FROM "translation_settings"
ORDER BY id
LIMIT 1
ON CONFLICT ("id") DO NOTHING;`).join("\n");

  return `${targetSql}\n${protectedSql}`;
}

function seedQuoteWorkflowItems() {
  return seedChildRows("page_text_settings", "page_text_settings_quote_page_ui_workflow_items", [
    workflowRow("seed-workflow-requirement", 0, "file-text", { en: ["Describe requirement", "Model, quantity, load and working radius."], zh: ["填写需求", "型号、数量、载荷和工作半径。"], es: ["Describir necesidad", "Modelo, cantidad, carga y radio de trabajo."], ar: ["وصف المتطلبات", "الموديل والكمية والحمولة ونصف قطر العمل."] }),
    workflowRow("seed-workflow-draft", 1, "mail-check", { en: ["Create inquiry draft", "Your details become a clean quotation message."], zh: ["生成询盘草稿", "系统将信息整理成清晰的报价消息。"], es: ["Crear borrador", "Sus datos se convierten en un mensaje claro."], ar: ["إنشاء مسودة استفسار", "تتحول التفاصيل إلى رسالة عرض سعر واضحة."] }),
    workflowRow("seed-workflow-send", 2, "message-circle", { en: ["Send by Email or WhatsApp", "You control the final sending channel."], zh: ["邮件或 WhatsApp 发送", "您可以选择最终发送渠道。"], es: ["Enviar por email o WhatsApp", "Usted elige el canal final."], ar: ["الإرسال بالبريد أو WhatsApp", "يمكنك اختيار قناة الإرسال النهائية."] }),
  ], "page_text_settings_quote_page_ui_workflow_items_locales");
}

function patchSeededLocaleText() {
  return [
    patchLocaleValue("site_settings_locales", "zh", "chrome_ui_main_navigation_label", "Main navigation", "主导航"),
    patchLocaleValue("site_settings_locales", "zh", "chrome_ui_logo_home_label", "BOB-LIFT Home", "BOB-LIFT 首页"),
    patchLocaleValue("site_settings_locales", "zh", "chrome_ui_logo_image_alt", "BOB-LIFT Cranes", "BOB-LIFT 起重机"),
    patchLocaleValue("site_settings_locales", "zh", "chrome_ui_floating_email_label", "Email", "邮箱"),
    patchLocaleValue("site_settings_locales", "zh", "chrome_ui_floating_call_label", "Call", "电话"),
    patchLocaleValue("site_settings_locales", "zh", "chrome_ui_we_chat_qr_alt", "BOB-LIFT WeChat QR code", "BOB-LIFT 微信二维码"),
    patchLocaleValue("site_settings_locales", "zh", "chrome_ui_back_to_top_label", "Back to top", "返回顶部"),
    patchLocaleValue("site_settings_locales", "es", "chrome_ui_main_navigation_label", "Main navigation", "Navegación principal"),
    patchLocaleValue("site_settings_locales", "es", "chrome_ui_logo_home_label", "BOB-LIFT Home", "Inicio BOB-LIFT"),
    patchLocaleValue("site_settings_locales", "es", "chrome_ui_logo_image_alt", "BOB-LIFT Cranes", "Grúas BOB-LIFT"),
    patchLocaleValue("site_settings_locales", "es", "chrome_ui_floating_email_label", "Email", "Correo"),
    patchLocaleValue("site_settings_locales", "es", "chrome_ui_floating_call_label", "Call", "Llamar"),
    patchLocaleValue("site_settings_locales", "es", "chrome_ui_we_chat_qr_alt", "BOB-LIFT WeChat QR code", "Código QR de WeChat de BOB-LIFT"),
    patchLocaleValue("site_settings_locales", "es", "chrome_ui_back_to_top_label", "Back to top", "Volver arriba"),
    patchLocaleValue("site_settings_locales", "ar", "chrome_ui_main_navigation_label", "Main navigation", "التنقل الرئيسي"),
    patchLocaleValue("site_settings_locales", "ar", "chrome_ui_logo_home_label", "BOB-LIFT Home", "صفحة BOB-LIFT الرئيسية"),
    patchLocaleValue("site_settings_locales", "ar", "chrome_ui_logo_image_alt", "BOB-LIFT Cranes", "رافعات BOB-LIFT"),
    patchLocaleValue("site_settings_locales", "ar", "chrome_ui_floating_email_label", "Email", "البريد الإلكتروني"),
    patchLocaleValue("site_settings_locales", "ar", "chrome_ui_floating_call_label", "Call", "اتصال"),
    patchLocaleValue("site_settings_locales", "ar", "chrome_ui_we_chat_qr_alt", "BOB-LIFT WeChat QR code", "رمز WeChat QR الخاص بـ BOB-LIFT"),
    patchLocaleValue("site_settings_locales", "ar", "chrome_ui_back_to_top_label", "Back to top", "العودة إلى الأعلى"),
    patchLocaleValue("product_detail_ui_locales", "zh", "product_detail_ui_catalog_description_template", "{catalogTitle} includes core specifications, working range information, and configuration notes for project evaluation.", "{catalogTitle} 包含核心参数、作业范围信息和项目评估所需的配置说明。"),
    patchLocaleValue("product_detail_ui_locales", "es", "product_detail_ui_catalog_description_template", "{catalogTitle} includes core specifications, working range information, and configuration notes for project evaluation.", "{catalogTitle} incluye especificaciones clave, alcance de trabajo y notas de configuración para evaluación."),
    patchLocaleValue("product_detail_ui_locales", "ar", "product_detail_ui_catalog_description_template", "{catalogTitle} includes core specifications, working range information, and configuration notes for project evaluation.", "يتضمن {catalogTitle} المواصفات الأساسية ومعلومات نطاق العمل وملاحظات التكوين للتقييم."),
    patchLocaleValue("product_detail_ui_locales", "zh", "product_detail_ui_gallery_empty_text", "Present boom structure, installation layout, hydraulic system, operating range, and chassis mounting details in one focused section.", "集中展示臂架结构、安装布局、液压系统、作业范围和底盘安装细节。"),
    patchLocaleValue("product_detail_ui_locales", "es", "product_detail_ui_gallery_empty_text", "Present boom structure, installation layout, hydraulic system, operating range, and chassis mounting details in one focused section.", "Muestra estructura, instalación, sistema hidráulico, rango de trabajo y montaje en chasis."),
    patchLocaleValue("product_detail_ui_locales", "ar", "product_detail_ui_gallery_empty_text", "Present boom structure, installation layout, hydraulic system, operating range, and chassis mounting details in one focused section.", "يعرض هيكل الذراع وتخطيط التركيب والنظام الهيدروليكي ونطاق العمل وتفاصيل تثبيت الشاسيه."),
  ].join("\n");
}

function patchLocaleValue(table, locale, column, from, to) {
  return `UPDATE ${ident(table)}
SET ${ident(column)} = ${sqlValue(to)}
WHERE "_locale" = ${sqlValue(locale)}
  AND ${ident(column)} = ${sqlValue(from)};`;
}

function dictRow(id, order, values, labels) {
  return {
    id,
    order,
    values,
    locales: Object.fromEntries(Object.entries(labels).map(([locale, label]) => [locale, { label }])),
  };
}

function appDictRow(id, order, key, imagePath, iconName, locales) {
  return {
    id,
    order,
    values: { key, image_path: imagePath, icon_name: iconName, enabled: true, sort_order: (order + 1) * 10 },
    locales: Object.fromEntries(Object.entries(locales).map(([locale, [label, title, text]]) => [locale, { label, title, text }])),
  };
}

function labelRow(id, order, labels) {
  return {
    id,
    order,
    values: {},
    locales: Object.fromEntries(Object.entries(labels).map(([locale, label]) => [locale, { label }])),
  };
}

function featureRow(id, order, locales) {
  return {
    id,
    order,
    values: {},
    locales: Object.fromEntries(Object.entries(locales).map(([locale, [title, text]]) => [locale, { title, text }])),
  };
}

function faqRow(id, order, locales) {
  return {
    id,
    order,
    values: {},
    locales: Object.fromEntries(Object.entries(locales).map(([locale, [question, answer]]) => [locale, { question, answer }])),
  };
}

function workflowRow(id, order, iconName, locales) {
  return {
    id,
    order,
    values: { icon_name: iconName },
    locales: Object.fromEntries(Object.entries(locales).map(([locale, [title, text]]) => [locale, { title, text }])),
  };
}

function homeHeroSlideRow(id, order, imagePath) {
  return {
    id,
    order,
    values: { image_path: imagePath },
    locales: {},
  };
}

function homeGalleryRow(id, order, imagePath, video, labels) {
  return {
    id,
    order,
    values: { image_path: imagePath, video },
    locales: Object.fromEntries(Object.entries(labels).map(([locale, alt]) => [locale, { alt }])),
  };
}

function homeStatRow(id, order, iconName, locales) {
  return {
    id,
    order,
    values: { icon_name: iconName },
    locales: Object.fromEntries(Object.entries(locales).map(([locale, [value, label]]) => [locale, { value, label }])),
  };
}

function homeTimelineRow(id, order, labels) {
  return {
    id,
    order,
    values: {},
    locales: Object.fromEntries(Object.entries(labels).map(([locale, text]) => [locale, { text }])),
  };
}

function seedChildRows(parentTable, childTable, rows, localeTable) {
  return rows.map((row) => {
    const valueColumns = Object.keys(row.values);
    const insertColumns = ["_order", "_parent_id", "id", ...valueColumns];
    const insertValues = [row.order, raw("id"), row.id, ...valueColumns.map((column) => row.values[column])];
    const childSql = `INSERT INTO ${ident(childTable)} (${insertColumns.map(ident).join(", ")})
SELECT ${insertValues.map(sqlValue).join(", ")}
FROM ${ident(parentTable)}
ORDER BY id
LIMIT 1
ON CONFLICT ("id") DO NOTHING;`;
    const localeSql = Object.entries(row.locales).map(([locale, values]) => {
      const localeColumns = Object.keys(values);
      return `INSERT INTO ${ident(localeTable)} (${localeColumns.map(ident).join(", ")}, "_locale", "_parent_id")
SELECT ${localeColumns.map((column) => sqlValue(values[column])).join(", ")}, ${sqlValue(locale)}, ${sqlValue(row.id)}
WHERE EXISTS (SELECT 1 FROM ${ident(childTable)} WHERE "id" = ${sqlValue(row.id)})
ON CONFLICT ("_locale", "_parent_id") DO NOTHING;`;
    }).join("\n");

    return `${childSql}\n${localeSql}`;
  }).join("\n");
}

function seedLocales(table, parentTable, rowsByLocale) {
  return Object.entries(rowsByLocale)
    .map(([locale, values]) => {
      const columns = Object.keys(values);
      const selectValues = columns.map((column) => sqlValue(values[column]));
      return `INSERT INTO ${ident(table)} (${columns.map(ident).join(", ")}, "_locale", "_parent_id")
SELECT ${selectValues.join(", ")}, ${sqlValue(locale)}, id
FROM ${ident(parentTable)}
ORDER BY id
LIMIT 1
ON CONFLICT ("_locale", "_parent_id") DO NOTHING;`;
    })
    .join("\n");
}

function insertIfMissing(table, values) {
  const columns = Object.keys(values);
  return `INSERT INTO ${ident(table)} (${columns.map(ident).join(", ")})
SELECT ${columns.map((column) => sqlValue(values[column])).join(", ")}
WHERE NOT EXISTS (SELECT 1 FROM ${ident(table)});`;
}

function ident(value) {
  return `"${String(value).replaceAll('"', '""')}"`;
}

function raw(value) {
  return { raw: value };
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
  const content = readFileSync(path, "utf8");
  for (const line of content.split(/\r?\n/)) {
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
