import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    ALTER TYPE "public"."enum_translation_jobs_status" ADD VALUE IF NOT EXISTS 'machine-translated' AFTER 'running';
    ALTER TYPE "public"."enum_translation_jobs_status" ADD VALUE IF NOT EXISTS 'human-review' AFTER 'machine-translated';
    ALTER TYPE "public"."enum_translation_jobs_status" ADD VALUE IF NOT EXISTS 'approved' AFTER 'human-review';

    CREATE TABLE IF NOT EXISTS "translation_source_hashes" (
      "id" serial PRIMARY KEY,
      "table_name" varchar NOT NULL,
      "parent_id" varchar NOT NULL,
      "column_name" varchar NOT NULL,
      "source_locale" varchar DEFAULT 'en' NOT NULL,
      "target_locale" varchar NOT NULL,
      "source_hash" varchar NOT NULL,
      "translated_at" timestamp(3) with time zone DEFAULT now() NOT NULL,
      "updated_at" timestamp(3) with time zone DEFAULT now() NOT NULL
    );

    CREATE UNIQUE INDEX IF NOT EXISTS "translation_source_hashes_unique_field_idx"
      ON "translation_source_hashes" ("table_name", "parent_id", "column_name", "source_locale", "target_locale");
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    DROP TABLE IF EXISTS "translation_source_hashes";
  `);
}
