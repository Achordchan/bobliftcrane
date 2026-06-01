import { MigrateUpArgs, MigrateDownArgs, sql } from '@payloadcms/db-postgres'

export async function up({ db, payload, req }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
   ALTER TABLE "home_settings_locales" ADD COLUMN "news_section_read_more_label" varchar DEFAULT 'Read More';
  ALTER TABLE "home_settings_locales" ADD COLUMN "news_section_pinned_label" varchar DEFAULT 'Pinned News';
  ALTER TABLE "home_settings_locales" ADD COLUMN "news_section_background_word" varchar DEFAULT 'NEWS';
  ALTER TABLE "product_listing_ui_locales" ADD COLUMN "product_listing_ui_banner_watermark_label" varchar DEFAULT 'BOB-LIFT';
  ALTER TABLE "product_listing_ui_locales" ADD COLUMN "product_listing_ui_quick_view_close_label" varchar DEFAULT 'Close quick view';`)
}

export async function down({ db, payload, req }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
   ALTER TABLE "home_settings_locales" DROP COLUMN "news_section_read_more_label";
  ALTER TABLE "home_settings_locales" DROP COLUMN "news_section_pinned_label";
  ALTER TABLE "home_settings_locales" DROP COLUMN "news_section_background_word";
  ALTER TABLE "product_listing_ui_locales" DROP COLUMN "product_listing_ui_banner_watermark_label";
  ALTER TABLE "product_listing_ui_locales" DROP COLUMN "product_listing_ui_quick_view_close_label";`)
}
