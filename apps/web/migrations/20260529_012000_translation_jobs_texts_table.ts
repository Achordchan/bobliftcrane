import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    CREATE TABLE IF NOT EXISTS "translation_jobs_texts" (
      "id" serial PRIMARY KEY NOT NULL,
      "order" integer NOT NULL,
      "parent_id" integer NOT NULL,
      "path" varchar NOT NULL,
      "text" varchar
    );

    DO $$ BEGIN
      ALTER TABLE "translation_jobs_texts" ADD CONSTRAINT "translation_jobs_texts_parent_fk" FOREIGN KEY ("parent_id") REFERENCES "public"."translation_jobs"("id") ON DELETE cascade ON UPDATE no action;
    EXCEPTION
      WHEN duplicate_object THEN null;
    END $$;

    CREATE INDEX IF NOT EXISTS "translation_jobs_texts_order_parent_idx" ON "translation_jobs_texts" USING btree ("order", "parent_id");
    CREATE INDEX IF NOT EXISTS "translation_jobs_texts_path_parent_idx" ON "translation_jobs_texts" USING btree ("path", "parent_id");

    INSERT INTO "translation_jobs_texts" ("order", "parent_id", "path", "text")
    SELECT t."order", t."parent_id", 'targetLocales', t."value"::text
    FROM "translation_jobs_target_locales" t
    WHERE t."value" IS NOT NULL
      AND NOT EXISTS (
        SELECT 1 FROM "translation_jobs_texts" existing
        WHERE existing."parent_id"=t."parent_id"
          AND existing."path"='targetLocales'
          AND existing."text"=t."value"::text
      );

    INSERT INTO "translation_jobs_texts" ("order", "parent_id", "path", "text")
    SELECT s."order", s."parent_id", 'scopes', s."value"::text
    FROM "translation_jobs_scopes" s
    WHERE s."value" IS NOT NULL
      AND NOT EXISTS (
        SELECT 1 FROM "translation_jobs_texts" existing
        WHERE existing."parent_id"=s."parent_id"
          AND existing."path"='scopes'
          AND existing."text"=s."value"::text
      );
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    DROP TABLE IF EXISTS "translation_jobs_texts" CASCADE;
  `);
}
