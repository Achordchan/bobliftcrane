import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    CREATE TABLE IF NOT EXISTS "media_locales" (
      "alt" varchar NOT NULL,
      "caption" varchar,
      "id" serial PRIMARY KEY NOT NULL,
      "_locale" "_locales" NOT NULL,
      "_parent_id" integer NOT NULL
    );

    ALTER TABLE "media"
      ALTER COLUMN "alt" DROP NOT NULL;

    DO $$
    BEGIN
      IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'media_locales_parent_id_fk'
      ) THEN
        ALTER TABLE "media_locales"
          ADD CONSTRAINT "media_locales_parent_id_fk"
          FOREIGN KEY ("_parent_id") REFERENCES "public"."media"("id")
          ON DELETE cascade ON UPDATE no action;
      END IF;
    END $$;

    CREATE UNIQUE INDEX IF NOT EXISTS "media_locales_locale_parent_id_unique"
      ON "media_locales" USING btree ("_locale", "_parent_id");

    INSERT INTO "media_locales" ("_parent_id", "_locale", "alt", "caption")
    SELECT "id", 'en', COALESCE(NULLIF("alt", ''), "filename", 'Media asset'), NULLIF("caption", '')
    FROM "media"
    ON CONFLICT ("_locale", "_parent_id") DO UPDATE
    SET "alt" = EXCLUDED."alt",
        "caption" = EXCLUDED."caption";
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    UPDATE "media" m
    SET "alt" = ml."alt",
        "caption" = ml."caption"
    FROM "media_locales" ml
    WHERE ml."_parent_id" = m."id"
      AND ml."_locale" = 'en';

    UPDATE "media"
    SET "alt" = COALESCE(NULLIF("alt", ''), "filename", 'Media asset')
    WHERE "alt" IS NULL OR "alt" = '';

    ALTER TABLE "media"
      ALTER COLUMN "alt" SET NOT NULL;

    DROP TABLE IF EXISTS "media_locales";
  `);
}
