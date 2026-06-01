import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "products_drawing_images" ADD COLUMN IF NOT EXISTS "parameter_match_status" varchar DEFAULT 'unchecked';
    ALTER TABLE "products_drawing_images" ADD COLUMN IF NOT EXISTS "extracted_text" varchar;
    ALTER TABLE "products_drawing_images" ADD COLUMN IF NOT EXISTS "review_notes" varchar;
    ALTER TABLE "products_drawing_images" ADD COLUMN IF NOT EXISTS "review_method" varchar DEFAULT 'manual';
    ALTER TABLE "products_drawing_images" ADD COLUMN IF NOT EXISTS "reviewer" varchar;
    ALTER TABLE "products_drawing_images" ADD COLUMN IF NOT EXISTS "reviewed_at" timestamp with time zone;
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "products_drawing_images" DROP COLUMN IF EXISTS "reviewed_at";
    ALTER TABLE "products_drawing_images" DROP COLUMN IF EXISTS "reviewer";
    ALTER TABLE "products_drawing_images" DROP COLUMN IF EXISTS "review_method";
    ALTER TABLE "products_drawing_images" DROP COLUMN IF EXISTS "review_notes";
    ALTER TABLE "products_drawing_images" DROP COLUMN IF EXISTS "extracted_text";
    ALTER TABLE "products_drawing_images" DROP COLUMN IF EXISTS "parameter_match_status";
  `);
}
