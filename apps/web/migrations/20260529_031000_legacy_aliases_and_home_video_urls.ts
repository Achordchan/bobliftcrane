import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    CREATE TABLE IF NOT EXISTS "products_legacy_aliases" (
      "_order" integer NOT NULL,
      "_parent_id" integer NOT NULL,
      "id" varchar PRIMARY KEY NOT NULL,
      "path" varchar NOT NULL
    );

    CREATE TABLE IF NOT EXISTS "articles_legacy_aliases" (
      "_order" integer NOT NULL,
      "_parent_id" integer NOT NULL,
      "id" varchar PRIMARY KEY NOT NULL,
      "path" varchar NOT NULL
    );

    CREATE TABLE IF NOT EXISTS "cases_legacy_aliases" (
      "_order" integer NOT NULL,
      "_parent_id" integer NOT NULL,
      "id" varchar PRIMARY KEY NOT NULL,
      "path" varchar NOT NULL
    );

    DO $$ BEGIN
      ALTER TABLE "products_legacy_aliases" ADD CONSTRAINT "products_legacy_aliases_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."products"("id") ON DELETE cascade ON UPDATE no action;
    EXCEPTION
      WHEN duplicate_object THEN null;
    END $$;

    DO $$ BEGIN
      ALTER TABLE "articles_legacy_aliases" ADD CONSTRAINT "articles_legacy_aliases_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."articles"("id") ON DELETE cascade ON UPDATE no action;
    EXCEPTION
      WHEN duplicate_object THEN null;
    END $$;

    DO $$ BEGIN
      ALTER TABLE "cases_legacy_aliases" ADD CONSTRAINT "cases_legacy_aliases_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."cases"("id") ON DELETE cascade ON UPDATE no action;
    EXCEPTION
      WHEN duplicate_object THEN null;
    END $$;

    CREATE INDEX IF NOT EXISTS "products_legacy_aliases_order_idx" ON "products_legacy_aliases" USING btree ("_order");
    CREATE INDEX IF NOT EXISTS "products_legacy_aliases_parent_id_idx" ON "products_legacy_aliases" USING btree ("_parent_id");
    CREATE INDEX IF NOT EXISTS "articles_legacy_aliases_order_idx" ON "articles_legacy_aliases" USING btree ("_order");
    CREATE INDEX IF NOT EXISTS "articles_legacy_aliases_parent_id_idx" ON "articles_legacy_aliases" USING btree ("_parent_id");
    CREATE INDEX IF NOT EXISTS "cases_legacy_aliases_order_idx" ON "cases_legacy_aliases" USING btree ("_order");
    CREATE INDEX IF NOT EXISTS "cases_legacy_aliases_parent_id_idx" ON "cases_legacy_aliases" USING btree ("_parent_id");

    ALTER TABLE "home_settings_about_section_gallery" ADD COLUMN IF NOT EXISTS "video_url" varchar;
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "home_settings_about_section_gallery" DROP COLUMN IF EXISTS "video_url";
    DROP TABLE IF EXISTS "cases_legacy_aliases" CASCADE;
    DROP TABLE IF EXISTS "articles_legacy_aliases" CASCADE;
    DROP TABLE IF EXISTS "products_legacy_aliases" CASCADE;
  `);
}
