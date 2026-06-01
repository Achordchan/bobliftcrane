import { MigrateUpArgs, MigrateDownArgs, sql } from '@payloadcms/db-postgres'

export async function up({ db, payload, req }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
   CREATE TYPE "public"."enum_site_settings_language_options_direction" AS ENUM('ltr', 'rtl');
  CREATE TYPE "public"."enum_site_settings_language_options_translation_status" AS ENUM('not-started', 'in-progress', 'ready');
  ALTER TABLE "site_settings_language_options" ADD COLUMN "direction" "enum_site_settings_language_options_direction" DEFAULT 'ltr' NOT NULL;
  ALTER TABLE "site_settings_language_options" ADD COLUMN "translation_status" "enum_site_settings_language_options_translation_status" DEFAULT 'in-progress' NOT NULL;
  ALTER TABLE "site_settings_language_options" ADD COLUMN "completion_percent" numeric DEFAULT 0;
  ALTER TABLE "site_settings_language_options" ADD COLUMN "translation_owner" varchar;
  ALTER TABLE "site_settings_language_options" ADD COLUMN "last_reviewed_at" timestamp(3) with time zone;
  UPDATE "site_settings_language_options"
  SET "translation_status"='ready', "completion_percent"=100
  WHERE "locale"='en';
  UPDATE "site_settings_language_options"
  SET "direction"='rtl'
  WHERE "locale"='ar';`)
}

export async function down({ db, payload, req }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
   ALTER TABLE "site_settings_language_options" DROP COLUMN "direction";
  ALTER TABLE "site_settings_language_options" DROP COLUMN "translation_status";
  ALTER TABLE "site_settings_language_options" DROP COLUMN "completion_percent";
  ALTER TABLE "site_settings_language_options" DROP COLUMN "translation_owner";
  ALTER TABLE "site_settings_language_options" DROP COLUMN "last_reviewed_at";
  DROP TYPE "public"."enum_site_settings_language_options_direction";
  DROP TYPE "public"."enum_site_settings_language_options_translation_status";`)
}
