import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "page_text_settings_locales" ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_site_title" varchar DEFAULT 'BOB-LIFT | Engineered Lifting Power for Global Projects';
    ALTER TABLE "page_text_settings_locales" ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_site_description" varchar DEFAULT 'BOB-LIFT manufactures truck mounted cranes, marine cranes and custom lifting solutions for global projects.';
    ALTER TABLE "page_text_settings_locales" ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_generic_title" varchar DEFAULT 'BOB-LIFT';
    ALTER TABLE "page_text_settings_locales" ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_generic_description" varchar DEFAULT 'BOB-LIFT lifting solutions.';
    ALTER TABLE "page_text_settings_locales" ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_product_title" varchar DEFAULT 'Product | BOB-LIFT';
    ALTER TABLE "page_text_settings_locales" ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_product_title_template" varchar DEFAULT '{title} | BOB-LIFT';
    ALTER TABLE "page_text_settings_locales" ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_product_description" varchar DEFAULT 'BOB-LIFT product detail page.';
    ALTER TABLE "page_text_settings_locales" ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_product_description_template" varchar DEFAULT '{product} BOB-LIFT product detail page.';
    ALTER TABLE "page_text_settings_locales" ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_products_title" varchar DEFAULT 'Products | BOB-LIFT';
    ALTER TABLE "page_text_settings_locales" ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_products_title_template" varchar DEFAULT '{title} | BOB-LIFT';
    ALTER TABLE "page_text_settings_locales" ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_products_description" varchar DEFAULT 'Browse BOB-LIFT product categories and crane models.';
    ALTER TABLE "page_text_settings_locales" ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_product_category_description_template" varchar DEFAULT '{category} product list from BOB-LIFT.';
    ALTER TABLE "page_text_settings_locales" ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_product_nav_group_description" varchar DEFAULT 'BOB-LIFT product category';
    ALTER TABLE "page_text_settings_locales" ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_article_title" varchar DEFAULT 'Article | BOB-LIFT';
    ALTER TABLE "page_text_settings_locales" ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_article_title_template" varchar DEFAULT '{title} | BOB-LIFT';
    ALTER TABLE "page_text_settings_locales" ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_article_description" varchar DEFAULT 'BOB-LIFT article detail page.';
    ALTER TABLE "page_text_settings_locales" ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_article_excerpt" varchar DEFAULT 'BOB-LIFT product and project update.';
    ALTER TABLE "page_text_settings_locales" ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_case_title" varchar DEFAULT 'Case | BOB-LIFT';
    ALTER TABLE "page_text_settings_locales" ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_case_title_template" varchar DEFAULT '{title} | BOB-LIFT Case';
    ALTER TABLE "page_text_settings_locales" ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_case_description" varchar DEFAULT 'BOB-LIFT customer case detail page.';
    ALTER TABLE "page_text_settings_locales" ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_case_summary" varchar DEFAULT 'BOB-LIFT customer project case.';
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "page_text_settings_locales" DROP COLUMN IF EXISTS "seo_fallbacks_ui_case_summary";
    ALTER TABLE "page_text_settings_locales" DROP COLUMN IF EXISTS "seo_fallbacks_ui_case_description";
    ALTER TABLE "page_text_settings_locales" DROP COLUMN IF EXISTS "seo_fallbacks_ui_case_title_template";
    ALTER TABLE "page_text_settings_locales" DROP COLUMN IF EXISTS "seo_fallbacks_ui_case_title";
    ALTER TABLE "page_text_settings_locales" DROP COLUMN IF EXISTS "seo_fallbacks_ui_article_excerpt";
    ALTER TABLE "page_text_settings_locales" DROP COLUMN IF EXISTS "seo_fallbacks_ui_article_description";
    ALTER TABLE "page_text_settings_locales" DROP COLUMN IF EXISTS "seo_fallbacks_ui_article_title_template";
    ALTER TABLE "page_text_settings_locales" DROP COLUMN IF EXISTS "seo_fallbacks_ui_article_title";
    ALTER TABLE "page_text_settings_locales" DROP COLUMN IF EXISTS "seo_fallbacks_ui_product_nav_group_description";
    ALTER TABLE "page_text_settings_locales" DROP COLUMN IF EXISTS "seo_fallbacks_ui_product_category_description_template";
    ALTER TABLE "page_text_settings_locales" DROP COLUMN IF EXISTS "seo_fallbacks_ui_products_description";
    ALTER TABLE "page_text_settings_locales" DROP COLUMN IF EXISTS "seo_fallbacks_ui_products_title_template";
    ALTER TABLE "page_text_settings_locales" DROP COLUMN IF EXISTS "seo_fallbacks_ui_products_title";
    ALTER TABLE "page_text_settings_locales" DROP COLUMN IF EXISTS "seo_fallbacks_ui_product_description_template";
    ALTER TABLE "page_text_settings_locales" DROP COLUMN IF EXISTS "seo_fallbacks_ui_product_description";
    ALTER TABLE "page_text_settings_locales" DROP COLUMN IF EXISTS "seo_fallbacks_ui_product_title_template";
    ALTER TABLE "page_text_settings_locales" DROP COLUMN IF EXISTS "seo_fallbacks_ui_product_title";
    ALTER TABLE "page_text_settings_locales" DROP COLUMN IF EXISTS "seo_fallbacks_ui_generic_description";
    ALTER TABLE "page_text_settings_locales" DROP COLUMN IF EXISTS "seo_fallbacks_ui_generic_title";
    ALTER TABLE "page_text_settings_locales" DROP COLUMN IF EXISTS "seo_fallbacks_ui_site_description";
    ALTER TABLE "page_text_settings_locales" DROP COLUMN IF EXISTS "seo_fallbacks_ui_site_title";
  `);
}
