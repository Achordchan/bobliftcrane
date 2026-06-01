import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    CREATE TYPE "public"."enum_translation_jobs_provider" AS ENUM('deepl');
    CREATE TYPE "public"."enum_translation_jobs_status" AS ENUM('draft', 'queued', 'running', 'completed', 'failed');
    CREATE TYPE "public"."enum_translation_settings_provider" AS ENUM('deepl');
    CREATE TYPE "public"."enum_translation_settings_formality" AS ENUM('default', 'more', 'less');

    CREATE TABLE "translation_jobs" (
      "id" serial PRIMARY KEY NOT NULL,
      "provider" "enum_translation_jobs_provider" DEFAULT 'deepl' NOT NULL,
      "status" "enum_translation_jobs_status" DEFAULT 'draft' NOT NULL,
      "source_locale" varchar DEFAULT 'en' NOT NULL,
      "overwrite_existing" boolean DEFAULT false,
      "dry_run" boolean DEFAULT true,
      "preserve_brand_terms" boolean DEFAULT true,
      "glossary_id" varchar,
      "last_run_at" timestamp(3) with time zone,
      "summary" varchar,
      "error_message" varchar,
      "updated_at" timestamp(3) with time zone DEFAULT now() NOT NULL,
      "created_at" timestamp(3) with time zone DEFAULT now() NOT NULL
    );

    CREATE TABLE "translation_jobs_locales" (
      "title" varchar NOT NULL,
      "id" serial PRIMARY KEY NOT NULL,
      "_locale" "_locales" NOT NULL,
      "_parent_id" integer NOT NULL
    );

    CREATE TABLE "translation_jobs_target_locales" (
      "order" integer NOT NULL,
      "parent_id" integer NOT NULL,
      "value" varchar,
      "id" serial PRIMARY KEY NOT NULL
    );

    CREATE TABLE "translation_jobs_scopes" (
      "order" integer NOT NULL,
      "parent_id" integer NOT NULL,
      "value" varchar,
      "id" serial PRIMARY KEY NOT NULL
    );

    CREATE TABLE "translation_settings" (
      "id" serial PRIMARY KEY NOT NULL,
      "enabled" boolean DEFAULT true,
      "provider" "enum_translation_settings_provider" DEFAULT 'deepl' NOT NULL,
      "auth_key_env_name" varchar DEFAULT 'DEEPL_AUTH_KEY',
      "source_locale" varchar DEFAULT 'en' NOT NULL,
      "formality" "enum_translation_settings_formality" DEFAULT 'default',
      "preserve_variables" boolean DEFAULT true,
      "preserve_brand_terms" boolean DEFAULT true,
      "notes" varchar DEFAULT 'Run: npm run payload:translate -- --targets=zh,es,ar --apply',
      "updated_at" timestamp(3) with time zone,
      "created_at" timestamp(3) with time zone
    );

    CREATE TABLE "translation_settings_texts" (
      "id" serial PRIMARY KEY NOT NULL,
      "order" integer NOT NULL,
      "parent_id" integer NOT NULL,
      "path" varchar NOT NULL,
      "text" varchar
    );

    CREATE TABLE "translation_settings_target_locales" (
      "order" integer NOT NULL,
      "parent_id" integer NOT NULL,
      "value" varchar,
      "id" serial PRIMARY KEY NOT NULL
    );

    CREATE TABLE "translation_settings_protected_terms" (
      "_order" integer NOT NULL,
      "_parent_id" integer NOT NULL,
      "id" varchar PRIMARY KEY NOT NULL,
      "term" varchar NOT NULL
    );

    ALTER TABLE "payload_locked_documents_rels" ADD COLUMN "translation_jobs_id" integer;

    ALTER TABLE "translation_jobs_locales" ADD CONSTRAINT "translation_jobs_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."translation_jobs"("id") ON DELETE cascade ON UPDATE no action;
    ALTER TABLE "translation_jobs_target_locales" ADD CONSTRAINT "translation_jobs_target_locales_parent_fk" FOREIGN KEY ("parent_id") REFERENCES "public"."translation_jobs"("id") ON DELETE cascade ON UPDATE no action;
    ALTER TABLE "translation_jobs_scopes" ADD CONSTRAINT "translation_jobs_scopes_parent_fk" FOREIGN KEY ("parent_id") REFERENCES "public"."translation_jobs"("id") ON DELETE cascade ON UPDATE no action;
    ALTER TABLE "translation_settings_texts" ADD CONSTRAINT "translation_settings_texts_parent_fk" FOREIGN KEY ("parent_id") REFERENCES "public"."translation_settings"("id") ON DELETE cascade ON UPDATE no action;
    ALTER TABLE "translation_settings_target_locales" ADD CONSTRAINT "translation_settings_target_locales_parent_fk" FOREIGN KEY ("parent_id") REFERENCES "public"."translation_settings"("id") ON DELETE cascade ON UPDATE no action;
    ALTER TABLE "translation_settings_protected_terms" ADD CONSTRAINT "translation_settings_protected_terms_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."translation_settings"("id") ON DELETE cascade ON UPDATE no action;
    ALTER TABLE "payload_locked_documents_rels" ADD CONSTRAINT "payload_locked_documents_rels_translation_jobs_fk" FOREIGN KEY ("translation_jobs_id") REFERENCES "public"."translation_jobs"("id") ON DELETE cascade ON UPDATE no action;

    CREATE INDEX "translation_jobs_updated_at_idx" ON "translation_jobs" USING btree ("updated_at");
    CREATE INDEX "translation_jobs_created_at_idx" ON "translation_jobs" USING btree ("created_at");
    CREATE UNIQUE INDEX "translation_jobs_locales_locale_parent_id_unique" ON "translation_jobs_locales" USING btree ("_locale", "_parent_id");
    CREATE INDEX "translation_jobs_target_locales_order_idx" ON "translation_jobs_target_locales" USING btree ("order");
    CREATE INDEX "translation_jobs_target_locales_parent_idx" ON "translation_jobs_target_locales" USING btree ("parent_id");
    CREATE INDEX "translation_jobs_scopes_order_idx" ON "translation_jobs_scopes" USING btree ("order");
    CREATE INDEX "translation_jobs_scopes_parent_idx" ON "translation_jobs_scopes" USING btree ("parent_id");
    CREATE INDEX "translation_settings_texts_order_parent_idx" ON "translation_settings_texts" USING btree ("order", "parent_id");
    CREATE INDEX "translation_settings_target_locales_order_idx" ON "translation_settings_target_locales" USING btree ("order");
    CREATE INDEX "translation_settings_target_locales_parent_idx" ON "translation_settings_target_locales" USING btree ("parent_id");
    CREATE INDEX "translation_settings_protected_terms_order_idx" ON "translation_settings_protected_terms" USING btree ("_order");
    CREATE INDEX "translation_settings_protected_terms_parent_id_idx" ON "translation_settings_protected_terms" USING btree ("_parent_id");
    CREATE INDEX "payload_locked_documents_rels_translation_jobs_id_idx" ON "payload_locked_documents_rels" USING btree ("translation_jobs_id");
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "payload_locked_documents_rels" DROP CONSTRAINT IF EXISTS "payload_locked_documents_rels_translation_jobs_fk";
    ALTER TABLE "translation_settings_protected_terms" DROP CONSTRAINT IF EXISTS "translation_settings_protected_terms_parent_id_fk";
    ALTER TABLE "translation_settings_target_locales" DROP CONSTRAINT IF EXISTS "translation_settings_target_locales_parent_fk";
    ALTER TABLE "translation_jobs_scopes" DROP CONSTRAINT IF EXISTS "translation_jobs_scopes_parent_fk";
    ALTER TABLE "translation_jobs_target_locales" DROP CONSTRAINT IF EXISTS "translation_jobs_target_locales_parent_fk";
    ALTER TABLE "translation_jobs_locales" DROP CONSTRAINT IF EXISTS "translation_jobs_locales_parent_id_fk";

    DROP INDEX IF EXISTS "payload_locked_documents_rels_translation_jobs_id_idx";
    ALTER TABLE "payload_locked_documents_rels" DROP COLUMN IF EXISTS "translation_jobs_id";

    DROP TABLE IF EXISTS "translation_settings_protected_terms" CASCADE;
    DROP TABLE IF EXISTS "translation_settings_target_locales" CASCADE;
    DROP TABLE IF EXISTS "translation_settings_texts" CASCADE;
    DROP TABLE IF EXISTS "translation_settings" CASCADE;
    DROP TABLE IF EXISTS "translation_jobs_scopes" CASCADE;
    DROP TABLE IF EXISTS "translation_jobs_target_locales" CASCADE;
    DROP TABLE IF EXISTS "translation_jobs_locales" CASCADE;
    DROP TABLE IF EXISTS "translation_jobs" CASCADE;

    DROP TYPE IF EXISTS "public"."enum_translation_settings_formality";
    DROP TYPE IF EXISTS "public"."enum_translation_settings_provider";
    DROP TYPE IF EXISTS "public"."enum_translation_jobs_status";
    DROP TYPE IF EXISTS "public"."enum_translation_jobs_provider";
  `);
}
