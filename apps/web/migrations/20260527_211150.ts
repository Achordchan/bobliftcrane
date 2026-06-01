import { MigrateUpArgs, MigrateDownArgs, sql } from '@payloadcms/db-postgres'

export async function up({ db, payload, req }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
   ALTER TABLE "home_settings_locales" ADD COLUMN "home_hero_ui_aria_label" varchar DEFAULT 'Featured lifting solutions';
  ALTER TABLE "home_settings_locales" ADD COLUMN "home_hero_ui_previous_slide_label" varchar DEFAULT 'Previous banner';
  ALTER TABLE "home_settings_locales" ADD COLUMN "home_hero_ui_next_slide_label" varchar DEFAULT 'Next banner';
  ALTER TABLE "home_settings_locales" ADD COLUMN "home_hero_ui_slide_indicator_label_template" varchar DEFAULT 'Show {title}';
  ALTER TABLE "home_settings_locales" ADD COLUMN "home_product_section_previous_categories_label" varchar DEFAULT 'Previous product categories';
  ALTER TABLE "home_settings_locales" ADD COLUMN "home_product_section_next_categories_label" varchar DEFAULT 'Next product categories';
  ALTER TABLE "home_settings_locales" ADD COLUMN "home_product_section_visual_watermark_label" varchar DEFAULT 'BOB-LIFT';
  ALTER TABLE "product_listing_ui" ADD COLUMN "product_listing_ui_rows_per_page" numeric DEFAULT 3;
  ALTER TABLE "product_detail_ui_locales" ADD COLUMN "product_detail_ui_catalog_title_template" varchar DEFAULT '{model} Product Catalog';`)
}

export async function down({ db, payload, req }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
   ALTER TABLE "home_settings_locales" DROP COLUMN "home_hero_ui_aria_label";
  ALTER TABLE "home_settings_locales" DROP COLUMN "home_hero_ui_previous_slide_label";
  ALTER TABLE "home_settings_locales" DROP COLUMN "home_hero_ui_next_slide_label";
  ALTER TABLE "home_settings_locales" DROP COLUMN "home_hero_ui_slide_indicator_label_template";
  ALTER TABLE "home_settings_locales" DROP COLUMN "home_product_section_previous_categories_label";
  ALTER TABLE "home_settings_locales" DROP COLUMN "home_product_section_next_categories_label";
  ALTER TABLE "home_settings_locales" DROP COLUMN "home_product_section_visual_watermark_label";
  ALTER TABLE "product_listing_ui" DROP COLUMN "product_listing_ui_rows_per_page";
  ALTER TABLE "product_detail_ui_locales" DROP COLUMN "product_detail_ui_catalog_title_template";`)
}
