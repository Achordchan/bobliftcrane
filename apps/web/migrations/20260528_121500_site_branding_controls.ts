import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "site_settings" ADD COLUMN "logo_image_id" integer;
    ALTER TABLE "site_settings" ADD COLUMN "logo_path" varchar DEFAULT '/images/bob-lift-logo.png';
    ALTER TABLE "site_settings" ADD COLUMN "logo_width" numeric DEFAULT 188;
    ALTER TABLE "site_settings" ADD COLUMN "logo_height" numeric DEFAULT 91;
    ALTER TABLE "site_settings" ADD COLUMN "product_menu_background_image_id" integer;
    ALTER TABLE "site_settings" ADD COLUMN "product_menu_background_image_path" varchar DEFAULT '/images/telescopic-banner.jpg';

    ALTER TABLE "site_settings" ADD CONSTRAINT "site_settings_logo_image_id_media_id_fk" FOREIGN KEY ("logo_image_id") REFERENCES "public"."media"("id") ON DELETE set null ON UPDATE no action;
    ALTER TABLE "site_settings" ADD CONSTRAINT "site_settings_product_menu_background_image_id_media_id_fk" FOREIGN KEY ("product_menu_background_image_id") REFERENCES "public"."media"("id") ON DELETE set null ON UPDATE no action;

    CREATE INDEX "site_settings_logo_image_idx" ON "site_settings" USING btree ("logo_image_id");
    CREATE INDEX "site_settings_product_menu_background_image_idx" ON "site_settings" USING btree ("product_menu_background_image_id");
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    DROP INDEX "site_settings_product_menu_background_image_idx";
    DROP INDEX "site_settings_logo_image_idx";

    ALTER TABLE "site_settings" DROP CONSTRAINT "site_settings_product_menu_background_image_id_media_id_fk";
    ALTER TABLE "site_settings" DROP CONSTRAINT "site_settings_logo_image_id_media_id_fk";

    ALTER TABLE "site_settings" DROP COLUMN "product_menu_background_image_path";
    ALTER TABLE "site_settings" DROP COLUMN "product_menu_background_image_id";
    ALTER TABLE "site_settings" DROP COLUMN "logo_height";
    ALTER TABLE "site_settings" DROP COLUMN "logo_width";
    ALTER TABLE "site_settings" DROP COLUMN "logo_path";
    ALTER TABLE "site_settings" DROP COLUMN "logo_image_id";
  `);
}
