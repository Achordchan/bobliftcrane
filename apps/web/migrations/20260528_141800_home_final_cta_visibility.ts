import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "home_settings" ADD COLUMN IF NOT EXISTS "home_section_visibility_final_cta" boolean DEFAULT true;

    UPDATE "home_settings"
    SET "home_section_visibility_final_cta" = true
    WHERE "home_section_visibility_final_cta" IS NULL;
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "home_settings" DROP COLUMN IF EXISTS "home_section_visibility_final_cta";
  `);
}
