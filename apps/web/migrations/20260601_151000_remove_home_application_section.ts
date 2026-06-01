import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    DELETE FROM "site_settings_navigation_items" WHERE "href" = '/#application';

    DELETE FROM "site_settings_footer_groups_links" WHERE "href" = '/#application';
    DELETE FROM "site_settings_footer_groups"
    WHERE "id" ILIKE '%application%'
      AND NOT EXISTS (
        SELECT 1 FROM "site_settings_footer_groups_links"
        WHERE "site_settings_footer_groups_links"."_parent_id" = "site_settings_footer_groups"."id"
      );

    DROP TABLE IF EXISTS "home_settings_application_section_items_locales" CASCADE;
    DROP TABLE IF EXISTS "home_settings_application_section_items" CASCADE;
    DROP TYPE IF EXISTS "enum_home_settings_application_section_items_icon_name";

    ALTER TABLE "home_settings"
      DROP COLUMN IF EXISTS "home_section_visibility_application_section",
      DROP COLUMN IF EXISTS "application_section_cta_href",
      DROP COLUMN IF EXISTS "application_section_show_cta",
      DROP COLUMN IF EXISTS "application_section_show_images",
      DROP COLUMN IF EXISTS "application_section_show_icons";

    ALTER TABLE "home_settings_locales"
      DROP COLUMN IF EXISTS "application_section_label",
      DROP COLUMN IF EXISTS "application_section_title",
      DROP COLUMN IF EXISTS "application_section_cta_label";
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    DO $$
    BEGIN
      IF NOT EXISTS (
        SELECT 1 FROM pg_type WHERE typname = 'enum_home_settings_application_section_items_icon_name'
      ) THEN
        CREATE TYPE "enum_home_settings_application_section_items_icon_name" AS ENUM('award', 'building', 'factory', 'globe', 'network', 'package-check', 'ship', 'truck', 'wrench');
      END IF;
    END $$;

    ALTER TABLE "home_settings"
      ADD COLUMN IF NOT EXISTS "home_section_visibility_application_section" boolean DEFAULT true,
      ADD COLUMN IF NOT EXISTS "application_section_cta_href" varchar DEFAULT '#application',
      ADD COLUMN IF NOT EXISTS "application_section_show_cta" boolean DEFAULT true,
      ADD COLUMN IF NOT EXISTS "application_section_show_images" boolean DEFAULT true,
      ADD COLUMN IF NOT EXISTS "application_section_show_icons" boolean DEFAULT true;

    ALTER TABLE "home_settings_locales"
      ADD COLUMN IF NOT EXISTS "application_section_label" varchar DEFAULT 'Solutions for Every Industry',
      ADD COLUMN IF NOT EXISTS "application_section_title" varchar DEFAULT 'Lifting Solutions That Build Stronger Futures.',
      ADD COLUMN IF NOT EXISTS "application_section_cta_label" varchar DEFAULT 'View All Applications';

    CREATE TABLE IF NOT EXISTS "home_settings_application_section_items" (
      "_order" integer NOT NULL,
      "_parent_id" integer NOT NULL,
      "id" varchar PRIMARY KEY NOT NULL,
      "image_id" integer,
      "image_path" varchar,
      "icon_name" "enum_home_settings_application_section_items_icon_name" DEFAULT 'factory'
    );

    CREATE TABLE IF NOT EXISTS "home_settings_application_section_items_locales" (
      "title" varchar NOT NULL,
      "id" serial PRIMARY KEY NOT NULL,
      "_locale" "_locales" NOT NULL,
      "_parent_id" varchar NOT NULL
    );

    DO $$
    BEGIN
      IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'home_settings_application_section_items_image_id_media_id_fk') THEN
        ALTER TABLE "home_settings_application_section_items"
          ADD CONSTRAINT "home_settings_application_section_items_image_id_media_id_fk"
          FOREIGN KEY ("image_id") REFERENCES "media"("id") ON DELETE SET NULL;
      END IF;

      IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'home_settings_application_section_items_parent_id_fk') THEN
        ALTER TABLE "home_settings_application_section_items"
          ADD CONSTRAINT "home_settings_application_section_items_parent_id_fk"
          FOREIGN KEY ("_parent_id") REFERENCES "home_settings"("id") ON DELETE CASCADE;
      END IF;

      IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'home_settings_application_section_items_locales_parent_id_fk') THEN
        ALTER TABLE "home_settings_application_section_items_locales"
          ADD CONSTRAINT "home_settings_application_section_items_locales_parent_id_fk"
          FOREIGN KEY ("_parent_id") REFERENCES "home_settings_application_section_items"("id") ON DELETE CASCADE;
      END IF;
    END $$;

    CREATE INDEX IF NOT EXISTS "home_settings_application_section_items_order_idx" ON "home_settings_application_section_items" USING btree ("_order");
    CREATE INDEX IF NOT EXISTS "home_settings_application_section_items_parent_id_idx" ON "home_settings_application_section_items" USING btree ("_parent_id");
    CREATE INDEX IF NOT EXISTS "home_settings_application_section_items_image_idx" ON "home_settings_application_section_items" USING btree ("image_id");
    CREATE UNIQUE INDEX IF NOT EXISTS "home_settings_application_section_items_locales_locale_paren" ON "home_settings_application_section_items_locales" USING btree ("_locale","_parent_id");
  `);
}
