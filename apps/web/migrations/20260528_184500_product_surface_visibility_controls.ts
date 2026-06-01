import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "products" ADD COLUMN "show_in_product_list" boolean DEFAULT true;
    ALTER TABLE "products" ADD COLUMN "show_on_home" boolean DEFAULT true;
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "products" DROP COLUMN "show_in_product_list";
    ALTER TABLE "products" DROP COLUMN "show_on_home";
  `);
}
