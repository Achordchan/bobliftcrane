import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "home_settings_home_hero_slides_locales"
      DROP COLUMN IF EXISTS "description";
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "home_settings_home_hero_slides_locales"
      ADD COLUMN IF NOT EXISTS "description" varchar;
  `);
}
