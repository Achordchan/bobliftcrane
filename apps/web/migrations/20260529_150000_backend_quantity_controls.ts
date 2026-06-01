import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "product_listing_ui"
      ADD COLUMN IF NOT EXISTS "product_listing_ui_max_card_specs" numeric DEFAULT 3,
      ADD COLUMN IF NOT EXISTS "product_listing_ui_max_list_item_specs" numeric DEFAULT 3,
      ADD COLUMN IF NOT EXISTS "product_listing_ui_max_quick_view_specs" numeric DEFAULT 3;

    ALTER TABLE "product_detail_ui"
      ADD COLUMN IF NOT EXISTS "product_detail_ui_max_hero_specs" numeric DEFAULT 4,
      ADD COLUMN IF NOT EXISTS "product_detail_ui_max_application_cards" numeric DEFAULT 4,
      ADD COLUMN IF NOT EXISTS "product_detail_ui_max_hotspots" numeric DEFAULT 6;

    ALTER TABLE "page_text_settings"
      ADD COLUMN IF NOT EXISTS "quote_form_ui_product_option_limit" numeric DEFAULT 0,
      ADD COLUMN IF NOT EXISTS "news_detail_ui_related_article_count" numeric DEFAULT 3,
      ADD COLUMN IF NOT EXISTS "about_page_ui_gallery_image_count" numeric DEFAULT 4;
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "page_text_settings"
      DROP COLUMN IF EXISTS "about_page_ui_gallery_image_count",
      DROP COLUMN IF EXISTS "news_detail_ui_related_article_count",
      DROP COLUMN IF EXISTS "quote_form_ui_product_option_limit";

    ALTER TABLE "product_detail_ui"
      DROP COLUMN IF EXISTS "product_detail_ui_max_hotspots",
      DROP COLUMN IF EXISTS "product_detail_ui_max_application_cards",
      DROP COLUMN IF EXISTS "product_detail_ui_max_hero_specs";

    ALTER TABLE "product_listing_ui"
      DROP COLUMN IF EXISTS "product_listing_ui_max_quick_view_specs",
      DROP COLUMN IF EXISTS "product_listing_ui_max_list_item_specs",
      DROP COLUMN IF EXISTS "product_listing_ui_max_card_specs";
  `);
}
