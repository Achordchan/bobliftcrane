import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "home_settings" ADD COLUMN IF NOT EXISTS "trust_bar_show_intro_card" boolean DEFAULT true;
    ALTER TABLE "home_settings" ADD COLUMN IF NOT EXISTS "trust_bar_show_mark_names" boolean DEFAULT true;
    ALTER TABLE "home_settings" ADD COLUMN IF NOT EXISTS "application_section_show_cta" boolean DEFAULT true;
    ALTER TABLE "home_settings" ADD COLUMN IF NOT EXISTS "application_section_show_images" boolean DEFAULT true;
    ALTER TABLE "home_settings" ADD COLUMN IF NOT EXISTS "application_section_show_icons" boolean DEFAULT true;
    ALTER TABLE "home_settings" ADD COLUMN IF NOT EXISTS "about_section_show_gallery" boolean DEFAULT true;
    ALTER TABLE "home_settings" ADD COLUMN IF NOT EXISTS "about_section_show_stats" boolean DEFAULT true;
    ALTER TABLE "home_settings" ADD COLUMN IF NOT EXISTS "about_section_show_timeline" boolean DEFAULT true;
    ALTER TABLE "home_settings" ADD COLUMN IF NOT EXISTS "about_section_show_cta" boolean DEFAULT true;
    ALTER TABLE "home_settings" ADD COLUMN IF NOT EXISTS "news_section_show_cta" boolean DEFAULT true;
    ALTER TABLE "home_settings" ADD COLUMN IF NOT EXISTS "news_section_show_excerpt" boolean DEFAULT true;
    ALTER TABLE "home_settings" ADD COLUMN IF NOT EXISTS "news_section_show_date" boolean DEFAULT true;
    ALTER TABLE "home_settings" ADD COLUMN IF NOT EXISTS "news_section_show_category" boolean DEFAULT true;
    ALTER TABLE "home_settings" ADD COLUMN IF NOT EXISTS "news_section_max_articles" numeric DEFAULT 4;

    ALTER TABLE "product_listing_ui" ADD COLUMN IF NOT EXISTS "product_listing_ui_show_filter_counts" boolean DEFAULT true;

    ALTER TABLE "product_detail_ui" ADD COLUMN IF NOT EXISTS "product_detail_ui_catalog_visibility_download_button" boolean DEFAULT true;
    ALTER TABLE "product_detail_ui" ADD COLUMN IF NOT EXISTS "product_detail_ui_section_visibility_features" boolean DEFAULT true;
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "product_detail_ui" DROP COLUMN IF EXISTS "product_detail_ui_section_visibility_features";
    ALTER TABLE "product_detail_ui" DROP COLUMN IF EXISTS "product_detail_ui_catalog_visibility_download_button";

    ALTER TABLE "product_listing_ui" DROP COLUMN IF EXISTS "product_listing_ui_show_filter_counts";

    ALTER TABLE "home_settings" DROP COLUMN IF EXISTS "news_section_max_articles";
    ALTER TABLE "home_settings" DROP COLUMN IF EXISTS "news_section_show_category";
    ALTER TABLE "home_settings" DROP COLUMN IF EXISTS "news_section_show_date";
    ALTER TABLE "home_settings" DROP COLUMN IF EXISTS "news_section_show_excerpt";
    ALTER TABLE "home_settings" DROP COLUMN IF EXISTS "news_section_show_cta";
    ALTER TABLE "home_settings" DROP COLUMN IF EXISTS "about_section_show_cta";
    ALTER TABLE "home_settings" DROP COLUMN IF EXISTS "about_section_show_timeline";
    ALTER TABLE "home_settings" DROP COLUMN IF EXISTS "about_section_show_stats";
    ALTER TABLE "home_settings" DROP COLUMN IF EXISTS "about_section_show_gallery";
    ALTER TABLE "home_settings" DROP COLUMN IF EXISTS "application_section_show_icons";
    ALTER TABLE "home_settings" DROP COLUMN IF EXISTS "application_section_show_images";
    ALTER TABLE "home_settings" DROP COLUMN IF EXISTS "application_section_show_cta";
    ALTER TABLE "home_settings" DROP COLUMN IF EXISTS "trust_bar_show_mark_names";
    ALTER TABLE "home_settings" DROP COLUMN IF EXISTS "trust_bar_show_intro_card";
  `);
}
