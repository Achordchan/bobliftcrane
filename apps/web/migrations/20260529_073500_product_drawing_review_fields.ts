import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "products_drawing_images" ADD COLUMN IF NOT EXISTS "source_type" varchar DEFAULT 'parameter-chart';
    ALTER TABLE "products_drawing_images" ADD COLUMN IF NOT EXISTS "source_url" varchar;
    ALTER TABLE "products_drawing_images" ADD COLUMN IF NOT EXISTS "review_status" varchar DEFAULT 'needs-review';
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "products_drawing_images" DROP COLUMN IF EXISTS "review_status";
    ALTER TABLE "products_drawing_images" DROP COLUMN IF EXISTS "source_url";
    ALTER TABLE "products_drawing_images" DROP COLUMN IF EXISTS "source_type";
  `);
}
