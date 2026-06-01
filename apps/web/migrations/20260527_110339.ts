import { MigrateUpArgs, MigrateDownArgs, sql } from '@payloadcms/db-postgres'

export async function up({ db, payload, req }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
   CREATE TYPE "public"."enum_products_import_source" AS ENUM('manual', 'legacy-site', 'ai-assisted');
  CREATE TYPE "public"."enum_products_parameter_review_status" AS ENUM('needs-review', 'verified-from-source');
  CREATE TABLE "products_drawing_images" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"image_id" integer NOT NULL
  );

  CREATE TABLE "products_drawing_images_locales" (
	"label" varchar,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "products_documents" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"url" varchar NOT NULL
  );

  CREATE TABLE "products_documents_locales" (
	"title" varchar NOT NULL,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  ALTER TABLE "products" ADD COLUMN "source_url" varchar;
  ALTER TABLE "products" ADD COLUMN "import_source" "enum_products_import_source" DEFAULT 'manual';
  ALTER TABLE "products" ADD COLUMN "parameter_review_status" "enum_products_parameter_review_status" DEFAULT 'needs-review';
  ALTER TABLE "products_locales" ADD COLUMN "parameter_review_notes" varchar;
  ALTER TABLE "products_drawing_images" ADD CONSTRAINT "products_drawing_images_image_id_media_id_fk" FOREIGN KEY ("image_id") REFERENCES "public"."media"("id") ON DELETE set null ON UPDATE no action;
  ALTER TABLE "products_drawing_images" ADD CONSTRAINT "products_drawing_images_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."products"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "products_drawing_images_locales" ADD CONSTRAINT "products_drawing_images_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."products_drawing_images"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "products_documents" ADD CONSTRAINT "products_documents_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."products"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "products_documents_locales" ADD CONSTRAINT "products_documents_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."products_documents"("id") ON DELETE cascade ON UPDATE no action;
  CREATE INDEX "products_drawing_images_order_idx" ON "products_drawing_images" USING btree ("_order");
  CREATE INDEX "products_drawing_images_parent_id_idx" ON "products_drawing_images" USING btree ("_parent_id");
  CREATE INDEX "products_drawing_images_image_idx" ON "products_drawing_images" USING btree ("image_id");
  CREATE UNIQUE INDEX "products_drawing_images_locales_locale_parent_id_unique" ON "products_drawing_images_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "products_documents_order_idx" ON "products_documents" USING btree ("_order");
  CREATE INDEX "products_documents_parent_id_idx" ON "products_documents" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "products_documents_locales_locale_parent_id_unique" ON "products_documents_locales" USING btree ("_locale","_parent_id");`)
}

export async function down({ db, payload, req }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
   DROP TABLE "products_drawing_images" CASCADE;
  DROP TABLE "products_drawing_images_locales" CASCADE;
  DROP TABLE "products_documents" CASCADE;
  DROP TABLE "products_documents_locales" CASCADE;
  ALTER TABLE "products" DROP COLUMN "source_url";
  ALTER TABLE "products" DROP COLUMN "import_source";
  ALTER TABLE "products" DROP COLUMN "parameter_review_status";
  ALTER TABLE "products_locales" DROP COLUMN "parameter_review_notes";
  DROP TYPE "public"."enum_products_import_source";
  DROP TYPE "public"."enum_products_parameter_review_status";`)
}
