import { MigrateUpArgs, MigrateDownArgs, sql } from '@payloadcms/db-postgres'

export async function up({ db, payload, req }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
   ALTER TABLE "home_settings_home_hero_slides" ALTER COLUMN "image_id" DROP NOT NULL;
  ALTER TABLE "home_settings_home_hero_slides" ADD COLUMN "image_path" varchar;
  ALTER TABLE "home_settings_home_hero_slides" ADD COLUMN "mobile_image_id" integer;
  ALTER TABLE "home_settings_home_hero_slides" ADD COLUMN "mobile_image_path" varchar;
  ALTER TABLE "home_settings_home_hero_slides" ADD COLUMN "primary_cta_href" varchar;
  ALTER TABLE "home_settings_home_hero_slides" ADD COLUMN "secondary_cta_href" varchar;
  ALTER TABLE "home_settings_home_hero_slides_locales" ADD COLUMN "eyebrow" varchar;
  ALTER TABLE "home_settings_home_hero_slides_locales" ADD COLUMN "description" varchar;
  ALTER TABLE "home_settings_home_hero_slides_locales" ADD COLUMN "primary_cta_label" varchar;
  ALTER TABLE "home_settings_home_hero_slides_locales" ADD COLUMN "secondary_cta_label" varchar;
  ALTER TABLE "home_settings_home_hero_slides" ADD CONSTRAINT "home_settings_home_hero_slides_mobile_image_id_media_id_fk" FOREIGN KEY ("mobile_image_id") REFERENCES "public"."media"("id") ON DELETE set null ON UPDATE no action;
  CREATE INDEX "home_settings_home_hero_slides_mobile_image_idx" ON "home_settings_home_hero_slides" USING btree ("mobile_image_id");`)
}

export async function down({ db, payload, req }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
   ALTER TABLE "home_settings_home_hero_slides" DROP CONSTRAINT "home_settings_home_hero_slides_mobile_image_id_media_id_fk";

  DROP INDEX "home_settings_home_hero_slides_mobile_image_idx";
  ALTER TABLE "home_settings_home_hero_slides" ALTER COLUMN "image_id" SET NOT NULL;
  ALTER TABLE "home_settings_home_hero_slides" DROP COLUMN "image_path";
  ALTER TABLE "home_settings_home_hero_slides" DROP COLUMN "mobile_image_id";
  ALTER TABLE "home_settings_home_hero_slides" DROP COLUMN "mobile_image_path";
  ALTER TABLE "home_settings_home_hero_slides" DROP COLUMN "primary_cta_href";
  ALTER TABLE "home_settings_home_hero_slides" DROP COLUMN "secondary_cta_href";
  ALTER TABLE "home_settings_home_hero_slides_locales" DROP COLUMN "eyebrow";
  ALTER TABLE "home_settings_home_hero_slides_locales" DROP COLUMN "description";
  ALTER TABLE "home_settings_home_hero_slides_locales" DROP COLUMN "primary_cta_label";
  ALTER TABLE "home_settings_home_hero_slides_locales" DROP COLUMN "secondary_cta_label";`)
}
