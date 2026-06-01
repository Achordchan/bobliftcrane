import { MigrateUpArgs, MigrateDownArgs, sql } from '@payloadcms/db-postgres'

export async function up({ db, payload, req }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
   ALTER TABLE "product_listing_ui" ADD COLUMN "product_listing_ui_canonical_path" varchar DEFAULT '/products';
  ALTER TABLE "product_listing_ui_locales" ADD COLUMN "product_listing_ui_meta_title" varchar DEFAULT 'Construction Equipment';
  ALTER TABLE "product_listing_ui_locales" ADD COLUMN "product_listing_ui_meta_description" varchar DEFAULT 'Browse BOB-LIFT truck mounted cranes, marine cranes, aerial work platforms, mini dumpers and lifting attachments.';`)
}

export async function down({ db, payload, req }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
   ALTER TABLE "product_listing_ui" DROP COLUMN "product_listing_ui_canonical_path";
  ALTER TABLE "product_listing_ui_locales" DROP COLUMN "product_listing_ui_meta_title";
  ALTER TABLE "product_listing_ui_locales" DROP COLUMN "product_listing_ui_meta_description";`)
}
