import { MigrateUpArgs, MigrateDownArgs, sql } from '@payloadcms/db-postgres'

export async function up({ db, payload, req }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
   CREATE TYPE "public"."enum_product_import_batches_status" AS ENUM('in-review', 'reviewing', 'completed', 'failed');
  CREATE TYPE "public"."enum_product_import_batches_source_type" AS ENUM('legacy-site', 'manual', 'ai-assisted');
  CREATE TYPE "public"."enum_product_import_batches_overwrite_policy" AS ENUM('preserve-verified', 'rewrite-unverified', 'record-only');
  CREATE TABLE "product_import_batches_failure_logs" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"source_url" varchar,
	"product_title" varchar,
	"error" varchar
  );

  CREATE TABLE "product_import_batches" (
	"id" serial PRIMARY KEY NOT NULL,
	"batch_id" varchar NOT NULL,
	"status" "enum_product_import_batches_status" DEFAULT 'in-review' NOT NULL,
	"source_type" "enum_product_import_batches_source_type" DEFAULT 'legacy-site' NOT NULL,
	"source_base_url" varchar DEFAULT 'https://www.bobliftcrane.com',
	"sitemap_url" varchar DEFAULT 'https://www.bobliftcrane.com/sitemap.xml',
	"started_at" timestamp(3) with time zone,
	"completed_at" timestamp(3) with time zone,
	"product_count" numeric DEFAULT 0,
	"imported_count" numeric DEFAULT 0,
	"skipped_count" numeric DEFAULT 0,
	"failed_count" numeric DEFAULT 0,
	"needs_review_count" numeric DEFAULT 0,
	"in_review_count" numeric DEFAULT 0,
	"verified_count" numeric DEFAULT 0,
	"overwrite_policy" "enum_product_import_batches_overwrite_policy" DEFAULT 'preserve-verified' NOT NULL,
	"updated_at" timestamp(3) with time zone DEFAULT now() NOT NULL,
	"created_at" timestamp(3) with time zone DEFAULT now() NOT NULL
  );

  CREATE TABLE "product_import_batches_locales" (
	"title" varchar NOT NULL,
	"review_checklist" varchar,
	"notes" varchar,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" integer NOT NULL
  );

  CREATE TABLE "product_import_batches_rels" (
	"id" serial PRIMARY KEY NOT NULL,
	"order" integer,
	"parent_id" integer NOT NULL,
	"path" varchar NOT NULL,
	"products_id" integer
  );

  ALTER TABLE "payload_locked_documents_rels" ADD COLUMN "product_import_batches_id" integer;
  ALTER TABLE "product_import_batches_failure_logs" ADD CONSTRAINT "product_import_batches_failure_logs_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."product_import_batches"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "product_import_batches_locales" ADD CONSTRAINT "product_import_batches_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."product_import_batches"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "product_import_batches_rels" ADD CONSTRAINT "product_import_batches_rels_parent_fk" FOREIGN KEY ("parent_id") REFERENCES "public"."product_import_batches"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "product_import_batches_rels" ADD CONSTRAINT "product_import_batches_rels_products_fk" FOREIGN KEY ("products_id") REFERENCES "public"."products"("id") ON DELETE cascade ON UPDATE no action;
  CREATE INDEX "product_import_batches_failure_logs_order_idx" ON "product_import_batches_failure_logs" USING btree ("_order");
  CREATE INDEX "product_import_batches_failure_logs_parent_id_idx" ON "product_import_batches_failure_logs" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "product_import_batches_batch_id_idx" ON "product_import_batches" USING btree ("batch_id");
  CREATE INDEX "product_import_batches_updated_at_idx" ON "product_import_batches" USING btree ("updated_at");
  CREATE INDEX "product_import_batches_created_at_idx" ON "product_import_batches" USING btree ("created_at");
  CREATE UNIQUE INDEX "product_import_batches_locales_locale_parent_id_unique" ON "product_import_batches_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "product_import_batches_rels_order_idx" ON "product_import_batches_rels" USING btree ("order");
  CREATE INDEX "product_import_batches_rels_parent_idx" ON "product_import_batches_rels" USING btree ("parent_id");
  CREATE INDEX "product_import_batches_rels_path_idx" ON "product_import_batches_rels" USING btree ("path");
  CREATE INDEX "product_import_batches_rels_products_id_idx" ON "product_import_batches_rels" USING btree ("products_id");
  ALTER TABLE "payload_locked_documents_rels" ADD CONSTRAINT "payload_locked_documents_rels_product_import_batches_fk" FOREIGN KEY ("product_import_batches_id") REFERENCES "public"."product_import_batches"("id") ON DELETE cascade ON UPDATE no action;
  CREATE INDEX "payload_locked_documents_rels_product_import_batches_id_idx" ON "payload_locked_documents_rels" USING btree ("product_import_batches_id");`)
}

export async function down({ db, payload, req }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
   ALTER TABLE "product_import_batches_failure_logs" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "product_import_batches" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "product_import_batches_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "product_import_batches_rels" DISABLE ROW LEVEL SECURITY;
  DROP TABLE "product_import_batches_failure_logs" CASCADE;
  DROP TABLE "product_import_batches" CASCADE;
  DROP TABLE "product_import_batches_locales" CASCADE;
  DROP TABLE "product_import_batches_rels" CASCADE;
  ALTER TABLE "payload_locked_documents_rels" DROP CONSTRAINT "payload_locked_documents_rels_product_import_batches_fk";

  DROP INDEX "payload_locked_documents_rels_product_import_batches_id_idx";
  ALTER TABLE "payload_locked_documents_rels" DROP COLUMN "product_import_batches_id";
  DROP TYPE "public"."enum_product_import_batches_status";
  DROP TYPE "public"."enum_product_import_batches_source_type";
  DROP TYPE "public"."enum_product_import_batches_overwrite_policy";`)
}
