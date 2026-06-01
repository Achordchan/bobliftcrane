import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "page_text_settings"
      ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_site_title" varchar,
      ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_site_description" varchar,
      ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_generic_title" varchar,
      ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_generic_description" varchar,
      ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_product_title" varchar,
      ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_product_title_template" varchar,
      ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_product_description" varchar,
      ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_product_description_template" varchar,
      ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_products_title" varchar,
      ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_products_title_template" varchar,
      ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_products_description" varchar,
      ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_product_category_description_template" varchar,
      ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_product_nav_group_description" varchar,
      ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_article_title" varchar,
      ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_article_title_template" varchar,
      ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_article_description" varchar,
      ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_article_excerpt" varchar,
      ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_case_title" varchar,
      ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_case_title_template" varchar,
      ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_case_description" varchar,
      ADD COLUMN IF NOT EXISTS "seo_fallbacks_ui_case_summary" varchar;

    UPDATE "page_text_settings" pts
    SET "seo_fallbacks_ui_site_title" = COALESCE(pts."seo_fallbacks_ui_site_title", ptl."seo_fallbacks_ui_site_title")
    FROM "page_text_settings_locales" ptl
    WHERE ptl."_parent_id" = pts."id" AND ptl."_locale" = 'en';
    UPDATE "page_text_settings" pts
    SET "seo_fallbacks_ui_site_description" = COALESCE(pts."seo_fallbacks_ui_site_description", ptl."seo_fallbacks_ui_site_description")
    FROM "page_text_settings_locales" ptl
    WHERE ptl."_parent_id" = pts."id" AND ptl."_locale" = 'en';
    UPDATE "page_text_settings" pts
    SET "seo_fallbacks_ui_generic_title" = COALESCE(pts."seo_fallbacks_ui_generic_title", ptl."seo_fallbacks_ui_generic_title")
    FROM "page_text_settings_locales" ptl
    WHERE ptl."_parent_id" = pts."id" AND ptl."_locale" = 'en';
    UPDATE "page_text_settings" pts
    SET "seo_fallbacks_ui_generic_description" = COALESCE(pts."seo_fallbacks_ui_generic_description", ptl."seo_fallbacks_ui_generic_description")
    FROM "page_text_settings_locales" ptl
    WHERE ptl."_parent_id" = pts."id" AND ptl."_locale" = 'en';
    UPDATE "page_text_settings" pts
    SET "seo_fallbacks_ui_product_title" = COALESCE(pts."seo_fallbacks_ui_product_title", ptl."seo_fallbacks_ui_product_title")
    FROM "page_text_settings_locales" ptl
    WHERE ptl."_parent_id" = pts."id" AND ptl."_locale" = 'en';
    UPDATE "page_text_settings" pts
    SET "seo_fallbacks_ui_product_title_template" = COALESCE(pts."seo_fallbacks_ui_product_title_template", ptl."seo_fallbacks_ui_product_title_template")
    FROM "page_text_settings_locales" ptl
    WHERE ptl."_parent_id" = pts."id" AND ptl."_locale" = 'en';
    UPDATE "page_text_settings" pts
    SET "seo_fallbacks_ui_product_description" = COALESCE(pts."seo_fallbacks_ui_product_description", ptl."seo_fallbacks_ui_product_description")
    FROM "page_text_settings_locales" ptl
    WHERE ptl."_parent_id" = pts."id" AND ptl."_locale" = 'en';
    UPDATE "page_text_settings" pts
    SET "seo_fallbacks_ui_product_description_template" = COALESCE(pts."seo_fallbacks_ui_product_description_template", ptl."seo_fallbacks_ui_product_description_template")
    FROM "page_text_settings_locales" ptl
    WHERE ptl."_parent_id" = pts."id" AND ptl."_locale" = 'en';
    UPDATE "page_text_settings" pts
    SET "seo_fallbacks_ui_products_title" = COALESCE(pts."seo_fallbacks_ui_products_title", ptl."seo_fallbacks_ui_products_title")
    FROM "page_text_settings_locales" ptl
    WHERE ptl."_parent_id" = pts."id" AND ptl."_locale" = 'en';
    UPDATE "page_text_settings" pts
    SET "seo_fallbacks_ui_products_title_template" = COALESCE(pts."seo_fallbacks_ui_products_title_template", ptl."seo_fallbacks_ui_products_title_template")
    FROM "page_text_settings_locales" ptl
    WHERE ptl."_parent_id" = pts."id" AND ptl."_locale" = 'en';
    UPDATE "page_text_settings" pts
    SET "seo_fallbacks_ui_products_description" = COALESCE(pts."seo_fallbacks_ui_products_description", ptl."seo_fallbacks_ui_products_description")
    FROM "page_text_settings_locales" ptl
    WHERE ptl."_parent_id" = pts."id" AND ptl."_locale" = 'en';
    UPDATE "page_text_settings" pts
    SET "seo_fallbacks_ui_product_category_description_template" = COALESCE(pts."seo_fallbacks_ui_product_category_description_template", ptl."seo_fallbacks_ui_product_category_description_template")
    FROM "page_text_settings_locales" ptl
    WHERE ptl."_parent_id" = pts."id" AND ptl."_locale" = 'en';
    UPDATE "page_text_settings" pts
    SET "seo_fallbacks_ui_product_nav_group_description" = COALESCE(pts."seo_fallbacks_ui_product_nav_group_description", ptl."seo_fallbacks_ui_product_nav_group_description")
    FROM "page_text_settings_locales" ptl
    WHERE ptl."_parent_id" = pts."id" AND ptl."_locale" = 'en';
    UPDATE "page_text_settings" pts
    SET "seo_fallbacks_ui_article_title" = COALESCE(pts."seo_fallbacks_ui_article_title", ptl."seo_fallbacks_ui_article_title")
    FROM "page_text_settings_locales" ptl
    WHERE ptl."_parent_id" = pts."id" AND ptl."_locale" = 'en';
    UPDATE "page_text_settings" pts
    SET "seo_fallbacks_ui_article_title_template" = COALESCE(pts."seo_fallbacks_ui_article_title_template", ptl."seo_fallbacks_ui_article_title_template")
    FROM "page_text_settings_locales" ptl
    WHERE ptl."_parent_id" = pts."id" AND ptl."_locale" = 'en';
    UPDATE "page_text_settings" pts
    SET "seo_fallbacks_ui_article_description" = COALESCE(pts."seo_fallbacks_ui_article_description", ptl."seo_fallbacks_ui_article_description")
    FROM "page_text_settings_locales" ptl
    WHERE ptl."_parent_id" = pts."id" AND ptl."_locale" = 'en';
    UPDATE "page_text_settings" pts
    SET "seo_fallbacks_ui_article_excerpt" = COALESCE(pts."seo_fallbacks_ui_article_excerpt", ptl."seo_fallbacks_ui_article_excerpt")
    FROM "page_text_settings_locales" ptl
    WHERE ptl."_parent_id" = pts."id" AND ptl."_locale" = 'en';
    UPDATE "page_text_settings" pts
    SET "seo_fallbacks_ui_case_title" = COALESCE(pts."seo_fallbacks_ui_case_title", ptl."seo_fallbacks_ui_case_title")
    FROM "page_text_settings_locales" ptl
    WHERE ptl."_parent_id" = pts."id" AND ptl."_locale" = 'en';
    UPDATE "page_text_settings" pts
    SET "seo_fallbacks_ui_case_title_template" = COALESCE(pts."seo_fallbacks_ui_case_title_template", ptl."seo_fallbacks_ui_case_title_template")
    FROM "page_text_settings_locales" ptl
    WHERE ptl."_parent_id" = pts."id" AND ptl."_locale" = 'en';
    UPDATE "page_text_settings" pts
    SET "seo_fallbacks_ui_case_description" = COALESCE(pts."seo_fallbacks_ui_case_description", ptl."seo_fallbacks_ui_case_description")
    FROM "page_text_settings_locales" ptl
    WHERE ptl."_parent_id" = pts."id" AND ptl."_locale" = 'en';
    UPDATE "page_text_settings" pts
    SET "seo_fallbacks_ui_case_summary" = COALESCE(pts."seo_fallbacks_ui_case_summary", ptl."seo_fallbacks_ui_case_summary")
    FROM "page_text_settings_locales" ptl
    WHERE ptl."_parent_id" = pts."id" AND ptl."_locale" = 'en';
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "page_text_settings"
      DROP COLUMN IF EXISTS "seo_fallbacks_ui_case_summary",
      DROP COLUMN IF EXISTS "seo_fallbacks_ui_case_description",
      DROP COLUMN IF EXISTS "seo_fallbacks_ui_case_title_template",
      DROP COLUMN IF EXISTS "seo_fallbacks_ui_case_title",
      DROP COLUMN IF EXISTS "seo_fallbacks_ui_article_excerpt",
      DROP COLUMN IF EXISTS "seo_fallbacks_ui_article_description",
      DROP COLUMN IF EXISTS "seo_fallbacks_ui_article_title_template",
      DROP COLUMN IF EXISTS "seo_fallbacks_ui_article_title",
      DROP COLUMN IF EXISTS "seo_fallbacks_ui_product_nav_group_description",
      DROP COLUMN IF EXISTS "seo_fallbacks_ui_product_category_description_template",
      DROP COLUMN IF EXISTS "seo_fallbacks_ui_products_description",
      DROP COLUMN IF EXISTS "seo_fallbacks_ui_products_title_template",
      DROP COLUMN IF EXISTS "seo_fallbacks_ui_products_title",
      DROP COLUMN IF EXISTS "seo_fallbacks_ui_product_description_template",
      DROP COLUMN IF EXISTS "seo_fallbacks_ui_product_description",
      DROP COLUMN IF EXISTS "seo_fallbacks_ui_product_title_template",
      DROP COLUMN IF EXISTS "seo_fallbacks_ui_product_title",
      DROP COLUMN IF EXISTS "seo_fallbacks_ui_generic_description",
      DROP COLUMN IF EXISTS "seo_fallbacks_ui_generic_title",
      DROP COLUMN IF EXISTS "seo_fallbacks_ui_site_description",
      DROP COLUMN IF EXISTS "seo_fallbacks_ui_site_title";
  `);
}
