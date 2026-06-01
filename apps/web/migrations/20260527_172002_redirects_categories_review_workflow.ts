import { MigrateUpArgs, MigrateDownArgs, sql } from '@payloadcms/db-postgres'

export async function up({ db, payload, req }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
   CREATE TYPE "public"."enum_product_categories_page_type" AS ENUM('product-listing', 'seo-landing', 'redirect-only');
  CREATE TYPE "public"."enum_redirects_redirect_type" AS ENUM('permanent', 'temporary');
  CREATE TYPE "public"."enum_redirects_status" AS ENUM('published', 'draft');
  ALTER TYPE "public"."enum_products_parameter_review_status" ADD VALUE 'in-review' BEFORE 'verified-from-source';
  ALTER TYPE "public"."enum_products_parameter_review_status" ADD VALUE 'changes-required';
  CREATE TABLE "product_categories_legacy_aliases" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"path" varchar NOT NULL
  );

  CREATE TABLE "redirects" (
	"id" serial PRIMARY KEY NOT NULL,
	"source_path" varchar NOT NULL,
	"target_path" varchar NOT NULL,
	"redirect_type" "enum_redirects_redirect_type" DEFAULT 'permanent' NOT NULL,
	"status" "enum_redirects_status" DEFAULT 'published' NOT NULL,
	"updated_at" timestamp(3) with time zone DEFAULT now() NOT NULL,
	"created_at" timestamp(3) with time zone DEFAULT now() NOT NULL
  );

  CREATE TABLE "redirects_locales" (
	"notes" varchar,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" integer NOT NULL
  );

  ALTER TABLE "products" ALTER COLUMN "category_ref_id" SET NOT NULL;
  ALTER TABLE "products" ALTER COLUMN "source_url" SET NOT NULL;
  ALTER TABLE "products" ALTER COLUMN "cover_image_id" SET NOT NULL;
  ALTER TABLE "product_categories" ADD COLUMN "parent_category_id" integer;
  ALTER TABLE "product_categories" ADD COLUMN "page_type" "enum_product_categories_page_type" DEFAULT 'product-listing' NOT NULL;
  ALTER TABLE "products_specs" ADD COLUMN "source_image_id" integer;
  ALTER TABLE "products_specs_locales" ADD COLUMN "source_note" varchar;
  ALTER TABLE "products" ADD COLUMN "parameter_reviewer" varchar;
  ALTER TABLE "products" ADD COLUMN "parameter_reviewed_at" timestamp(3) with time zone;
  ALTER TABLE "products" ADD COLUMN "import_batch_id" varchar;
  ALTER TABLE "payload_locked_documents_rels" ADD COLUMN "redirects_id" integer;
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_review_status_labels_in_review" varchar DEFAULT 'In review';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_review_status_labels_changes_required" varchar DEFAULT 'Changes required';
  ALTER TABLE "product_categories_legacy_aliases" ADD CONSTRAINT "product_categories_legacy_aliases_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."product_categories"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "redirects_locales" ADD CONSTRAINT "redirects_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."redirects"("id") ON DELETE cascade ON UPDATE no action;
  CREATE INDEX "product_categories_legacy_aliases_order_idx" ON "product_categories_legacy_aliases" USING btree ("_order");
  CREATE INDEX "product_categories_legacy_aliases_parent_id_idx" ON "product_categories_legacy_aliases" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "redirects_source_path_idx" ON "redirects" USING btree ("source_path");
  CREATE INDEX "redirects_updated_at_idx" ON "redirects" USING btree ("updated_at");
  CREATE INDEX "redirects_created_at_idx" ON "redirects" USING btree ("created_at");
  CREATE UNIQUE INDEX "redirects_locales_locale_parent_id_unique" ON "redirects_locales" USING btree ("_locale","_parent_id");
  ALTER TABLE "product_categories" ADD CONSTRAINT "product_categories_parent_category_id_product_categories_id_fk" FOREIGN KEY ("parent_category_id") REFERENCES "public"."product_categories"("id") ON DELETE set null ON UPDATE no action;
  ALTER TABLE "products_specs" ADD CONSTRAINT "products_specs_source_image_id_media_id_fk" FOREIGN KEY ("source_image_id") REFERENCES "public"."media"("id") ON DELETE set null ON UPDATE no action;
  ALTER TABLE "payload_locked_documents_rels" ADD CONSTRAINT "payload_locked_documents_rels_redirects_fk" FOREIGN KEY ("redirects_id") REFERENCES "public"."redirects"("id") ON DELETE cascade ON UPDATE no action;
  CREATE INDEX "product_categories_parent_category_idx" ON "product_categories" USING btree ("parent_category_id");
  CREATE INDEX "products_specs_source_image_idx" ON "products_specs" USING btree ("source_image_id");
  CREATE INDEX "payload_locked_documents_rels_redirects_id_idx" ON "payload_locked_documents_rels" USING btree ("redirects_id");`)
}

export async function down({ db, payload, req }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
   ALTER TABLE "product_categories_legacy_aliases" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "redirects" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "redirects_locales" DISABLE ROW LEVEL SECURITY;
  DROP TABLE "product_categories_legacy_aliases" CASCADE;
  DROP TABLE "redirects" CASCADE;
  DROP TABLE "redirects_locales" CASCADE;
  ALTER TABLE "product_categories" DROP CONSTRAINT "product_categories_parent_category_id_product_categories_id_fk";

  ALTER TABLE "products_specs" DROP CONSTRAINT "products_specs_source_image_id_media_id_fk";

  ALTER TABLE "payload_locked_documents_rels" DROP CONSTRAINT "payload_locked_documents_rels_redirects_fk";

  ALTER TABLE "products" ALTER COLUMN "parameter_review_status" SET DATA TYPE text;
  ALTER TABLE "products" ALTER COLUMN "parameter_review_status" SET DEFAULT 'needs-review'::text;
  DROP TYPE "public"."enum_products_parameter_review_status";
  CREATE TYPE "public"."enum_products_parameter_review_status" AS ENUM('needs-review', 'verified-from-source');
  ALTER TABLE "products" ALTER COLUMN "parameter_review_status" SET DEFAULT 'needs-review'::"public"."enum_products_parameter_review_status";
  ALTER TABLE "products" ALTER COLUMN "parameter_review_status" SET DATA TYPE "public"."enum_products_parameter_review_status" USING "parameter_review_status"::"public"."enum_products_parameter_review_status";
  DROP INDEX "product_categories_parent_category_idx";
  DROP INDEX "products_specs_source_image_idx";
  DROP INDEX "payload_locked_documents_rels_redirects_id_idx";
  ALTER TABLE "products" ALTER COLUMN "category_ref_id" DROP NOT NULL;
  ALTER TABLE "products" ALTER COLUMN "source_url" DROP NOT NULL;
  ALTER TABLE "products" ALTER COLUMN "cover_image_id" DROP NOT NULL;
  ALTER TABLE "product_categories" DROP COLUMN "parent_category_id";
  ALTER TABLE "product_categories" DROP COLUMN "page_type";
  ALTER TABLE "products_specs" DROP COLUMN "source_image_id";
  ALTER TABLE "products_specs_locales" DROP COLUMN "source_note";
  ALTER TABLE "products" DROP COLUMN "parameter_reviewer";
  ALTER TABLE "products" DROP COLUMN "parameter_reviewed_at";
  ALTER TABLE "products" DROP COLUMN "import_batch_id";
  ALTER TABLE "payload_locked_documents_rels" DROP COLUMN "redirects_id";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_review_status_labels_in_review";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_review_status_labels_changes_required";
  DROP TYPE "public"."enum_product_categories_page_type";
  DROP TYPE "public"."enum_redirects_redirect_type";
  DROP TYPE "public"."enum_redirects_status";`)
}
