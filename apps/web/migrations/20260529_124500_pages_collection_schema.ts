import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    DO $$ BEGIN
      CREATE TYPE "public"."enum_pages_page_type" AS ENUM('application', 'service', 'custom');
    EXCEPTION WHEN duplicate_object THEN null;
    END $$;

    DO $$ BEGIN
      CREATE TYPE "public"."enum_pages_status" AS ENUM('draft', 'published');
    EXCEPTION WHEN duplicate_object THEN null;
    END $$;

    CREATE TABLE IF NOT EXISTS "pages" (
      "id" serial PRIMARY KEY NOT NULL,
      "slug" varchar NOT NULL,
      "legacy_path" varchar NOT NULL,
      "page_type" "public"."enum_pages_page_type" DEFAULT 'custom' NOT NULL,
      "hero_image_id" integer,
      "hero_image_path" varchar,
      "seo_canonical_path" varchar,
      "status" "public"."enum_pages_status" DEFAULT 'draft' NOT NULL,
      "show_in_navigation" boolean DEFAULT false,
      "sort_order" numeric DEFAULT 100,
      "published_at" timestamp(3) with time zone,
      "updated_at" timestamp(3) with time zone DEFAULT now() NOT NULL,
      "created_at" timestamp(3) with time zone DEFAULT now() NOT NULL
    );

    CREATE TABLE IF NOT EXISTS "pages_locales" (
      "title" varchar NOT NULL,
      "hero_label" varchar,
      "hero_title" varchar NOT NULL,
      "hero_description" varchar,
      "seo_meta_title" varchar,
      "seo_meta_description" varchar,
      "id" serial PRIMARY KEY NOT NULL,
      "_locale" "_locales" NOT NULL,
      "_parent_id" integer NOT NULL
    );

    CREATE TABLE IF NOT EXISTS "pages_legacy_aliases" (
      "_order" integer NOT NULL,
      "_parent_id" integer NOT NULL,
      "id" varchar PRIMARY KEY NOT NULL,
      "path" varchar NOT NULL
    );

    CREATE TABLE IF NOT EXISTS "pages_content_blocks" (
      "_order" integer NOT NULL,
      "_parent_id" integer NOT NULL,
      "id" varchar PRIMARY KEY NOT NULL,
      "image_id" integer,
      "image_path" varchar,
      "cta_href" varchar
    );

    CREATE TABLE IF NOT EXISTS "pages_content_blocks_locales" (
      "label" varchar,
      "title" varchar NOT NULL,
      "text" varchar NOT NULL,
      "cta_label" varchar,
      "id" serial PRIMARY KEY NOT NULL,
      "_locale" "_locales" NOT NULL,
      "_parent_id" varchar NOT NULL
    );

    ALTER TABLE "payload_locked_documents_rels"
      ADD COLUMN IF NOT EXISTS "pages_id" integer;

    CREATE UNIQUE INDEX IF NOT EXISTS "pages_slug_idx"
      ON "pages" USING btree ("slug");
    CREATE INDEX IF NOT EXISTS "pages_hero_image_idx"
      ON "pages" USING btree ("hero_image_id");
    CREATE INDEX IF NOT EXISTS "pages_updated_at_idx"
      ON "pages" USING btree ("updated_at");
    CREATE INDEX IF NOT EXISTS "pages_created_at_idx"
      ON "pages" USING btree ("created_at");

    CREATE UNIQUE INDEX IF NOT EXISTS "pages_locales_locale_parent_id_unique"
      ON "pages_locales" USING btree ("_locale", "_parent_id");

    CREATE INDEX IF NOT EXISTS "pages_legacy_aliases_order_idx"
      ON "pages_legacy_aliases" USING btree ("_order");
    CREATE INDEX IF NOT EXISTS "pages_legacy_aliases_parent_id_idx"
      ON "pages_legacy_aliases" USING btree ("_parent_id");

    CREATE INDEX IF NOT EXISTS "pages_content_blocks_order_idx"
      ON "pages_content_blocks" USING btree ("_order");
    CREATE INDEX IF NOT EXISTS "pages_content_blocks_parent_id_idx"
      ON "pages_content_blocks" USING btree ("_parent_id");
    CREATE INDEX IF NOT EXISTS "pages_content_blocks_image_idx"
      ON "pages_content_blocks" USING btree ("image_id");

    CREATE UNIQUE INDEX IF NOT EXISTS "pages_content_blocks_locales_locale_parent_id_unique"
      ON "pages_content_blocks_locales" USING btree ("_locale", "_parent_id");

    CREATE INDEX IF NOT EXISTS "payload_locked_documents_rels_pages_id_idx"
      ON "payload_locked_documents_rels" USING btree ("pages_id");
  `);

  await db.execute(sql`
    DO $$ BEGIN
      IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'pages_hero_image_id_media_id_fk') THEN
        ALTER TABLE "pages"
          ADD CONSTRAINT "pages_hero_image_id_media_id_fk"
          FOREIGN KEY ("hero_image_id") REFERENCES "public"."media"("id")
          ON DELETE SET NULL ON UPDATE NO ACTION;
      END IF;
    END $$;

    DO $$ BEGIN
      IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'pages_locales_parent_id_fk') THEN
        ALTER TABLE "pages_locales"
          ADD CONSTRAINT "pages_locales_parent_id_fk"
          FOREIGN KEY ("_parent_id") REFERENCES "public"."pages"("id")
          ON DELETE CASCADE ON UPDATE NO ACTION;
      END IF;
    END $$;

    DO $$ BEGIN
      IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'pages_legacy_aliases_parent_id_fk') THEN
        ALTER TABLE "pages_legacy_aliases"
          ADD CONSTRAINT "pages_legacy_aliases_parent_id_fk"
          FOREIGN KEY ("_parent_id") REFERENCES "public"."pages"("id")
          ON DELETE CASCADE ON UPDATE NO ACTION;
      END IF;
    END $$;

    DO $$ BEGIN
      IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'pages_content_blocks_parent_id_fk') THEN
        ALTER TABLE "pages_content_blocks"
          ADD CONSTRAINT "pages_content_blocks_parent_id_fk"
          FOREIGN KEY ("_parent_id") REFERENCES "public"."pages"("id")
          ON DELETE CASCADE ON UPDATE NO ACTION;
      END IF;
    END $$;

    DO $$ BEGIN
      IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'pages_content_blocks_image_id_media_id_fk') THEN
        ALTER TABLE "pages_content_blocks"
          ADD CONSTRAINT "pages_content_blocks_image_id_media_id_fk"
          FOREIGN KEY ("image_id") REFERENCES "public"."media"("id")
          ON DELETE SET NULL ON UPDATE NO ACTION;
      END IF;
    END $$;

    DO $$ BEGIN
      IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'pages_content_blocks_locales_parent_id_fk') THEN
        ALTER TABLE "pages_content_blocks_locales"
          ADD CONSTRAINT "pages_content_blocks_locales_parent_id_fk"
          FOREIGN KEY ("_parent_id") REFERENCES "public"."pages_content_blocks"("id")
          ON DELETE CASCADE ON UPDATE NO ACTION;
      END IF;
    END $$;

    DO $$ BEGIN
      IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'payload_locked_documents_rels_pages_fk') THEN
        ALTER TABLE "payload_locked_documents_rels"
          ADD CONSTRAINT "payload_locked_documents_rels_pages_fk"
          FOREIGN KEY ("pages_id") REFERENCES "public"."pages"("id")
          ON DELETE CASCADE ON UPDATE NO ACTION;
      END IF;
    END $$;
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "payload_locked_documents_rels"
      DROP CONSTRAINT IF EXISTS "payload_locked_documents_rels_pages_fk";
    DROP INDEX IF EXISTS "payload_locked_documents_rels_pages_id_idx";
    ALTER TABLE "payload_locked_documents_rels"
      DROP COLUMN IF EXISTS "pages_id";

    ALTER TABLE "pages_content_blocks_locales" DISABLE ROW LEVEL SECURITY;
    ALTER TABLE "pages_content_blocks" DISABLE ROW LEVEL SECURITY;
    ALTER TABLE "pages_legacy_aliases" DISABLE ROW LEVEL SECURITY;
    ALTER TABLE "pages_locales" DISABLE ROW LEVEL SECURITY;
    ALTER TABLE "pages" DISABLE ROW LEVEL SECURITY;

    DROP TABLE IF EXISTS "pages_content_blocks_locales" CASCADE;
    DROP TABLE IF EXISTS "pages_content_blocks" CASCADE;
    DROP TABLE IF EXISTS "pages_legacy_aliases" CASCADE;
    DROP TABLE IF EXISTS "pages_locales" CASCADE;
    DROP TABLE IF EXISTS "pages" CASCADE;

    DROP TYPE IF EXISTS "public"."enum_pages_status";
    DROP TYPE IF EXISTS "public"."enum_pages_page_type";
  `);
}
