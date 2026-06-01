import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "home_settings" ADD COLUMN IF NOT EXISTS "home_product_section_show_featured_cta" boolean DEFAULT true;
    ALTER TABLE "home_settings" ADD COLUMN IF NOT EXISTS "home_product_section_show_featured_product_model" boolean DEFAULT true;

    ALTER TABLE "product_listing_ui" ADD COLUMN IF NOT EXISTS "product_listing_ui_sort_visibility_recommended" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN IF NOT EXISTS "product_listing_ui_sort_visibility_capacity_asc" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN IF NOT EXISTS "product_listing_ui_sort_visibility_capacity_desc" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN IF NOT EXISTS "product_listing_ui_sort_visibility_newest" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN IF NOT EXISTS "product_listing_ui_sort_visibility_popular" boolean DEFAULT true;
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "product_listing_ui" DROP COLUMN IF EXISTS "product_listing_ui_sort_visibility_popular";
    ALTER TABLE "product_listing_ui" DROP COLUMN IF EXISTS "product_listing_ui_sort_visibility_newest";
    ALTER TABLE "product_listing_ui" DROP COLUMN IF EXISTS "product_listing_ui_sort_visibility_capacity_desc";
    ALTER TABLE "product_listing_ui" DROP COLUMN IF EXISTS "product_listing_ui_sort_visibility_capacity_asc";
    ALTER TABLE "product_listing_ui" DROP COLUMN IF EXISTS "product_listing_ui_sort_visibility_recommended";

    ALTER TABLE "home_settings" DROP COLUMN IF EXISTS "home_product_section_show_featured_product_model";
    ALTER TABLE "home_settings" DROP COLUMN IF EXISTS "home_product_section_show_featured_cta";
  `);
}
