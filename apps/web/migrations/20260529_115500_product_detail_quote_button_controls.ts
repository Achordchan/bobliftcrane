import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "product_detail_ui" ADD COLUMN IF NOT EXISTS "product_detail_ui_quote_visibility_request_quote_button" boolean DEFAULT true;
    ALTER TABLE "product_detail_ui" ADD COLUMN IF NOT EXISTS "product_detail_ui_quote_visibility_contact_engineer_button" boolean DEFAULT true;
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "product_detail_ui" DROP COLUMN IF EXISTS "product_detail_ui_quote_visibility_contact_engineer_button";
    ALTER TABLE "product_detail_ui" DROP COLUMN IF EXISTS "product_detail_ui_quote_visibility_request_quote_button";
  `);
}
