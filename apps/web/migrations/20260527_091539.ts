import { MigrateUpArgs, MigrateDownArgs, sql } from '@payloadcms/db-postgres'

export async function up({ db, payload, req }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
   CREATE TYPE "public"."enum_products_applications" AS ENUM('construction', 'marine', 'logistics', 'agriculture', 'mining', 'municipal-engineering');
  CREATE TYPE "public"."enum_products_boom_type" AS ENUM('telescopic-boom', 'knuckle-boom', 'straight-boom', 'fly-jib', 'custom-solution');
  CREATE TYPE "public"."enum_products_availability" AS ENUM('in-stock', 'best-seller', 'customizable', 'pre-order');
  ALTER TYPE "public"."enum_products_category" ADD VALUE 'aerial-work-platform' BEFORE 'pickup-cranes';
  ALTER TYPE "public"."enum_products_category" ADD VALUE 'crane-accessories' BEFORE 'other-cranes';
  CREATE TABLE "products_applications" (
	"order" integer NOT NULL,
	"parent_id" integer NOT NULL,
	"value" "enum_products_applications",
	"id" serial PRIMARY KEY NOT NULL
  );

  CREATE TABLE "products_tags" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"label" varchar NOT NULL
  );

  CREATE TABLE "products_key_features" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"title" varchar NOT NULL,
	"text" varchar NOT NULL
  );

  CREATE TABLE "products_faqs" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"question" varchar NOT NULL,
	"answer" varchar NOT NULL
  );

  CREATE TABLE "site_settings_home_hero_slides" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"title" varchar NOT NULL,
	"image_id" integer NOT NULL
  );

  CREATE TABLE "site_settings_rels" (
	"id" serial PRIMARY KEY NOT NULL,
	"order" integer,
	"parent_id" integer NOT NULL,
	"path" varchar NOT NULL,
	"products_id" integer
  );

  ALTER TABLE "products_gallery" ADD COLUMN "label" varchar;
  ALTER TABLE "products" ADD COLUMN "capacity_value" numeric;
  ALTER TABLE "products" ADD COLUMN "boom_type" "enum_products_boom_type" DEFAULT 'custom-solution' NOT NULL;
  ALTER TABLE "products" ADD COLUMN "availability" "enum_products_availability" DEFAULT 'customizable' NOT NULL;
  ALTER TABLE "products" ADD COLUMN "is_featured" boolean DEFAULT false;
  ALTER TABLE "products" ADD COLUMN "popularity" numeric DEFAULT 50;
  ALTER TABLE "products" ADD COLUMN "sort_order" numeric DEFAULT 100;
  ALTER TABLE "products_applications" ADD CONSTRAINT "products_applications_parent_fk" FOREIGN KEY ("parent_id") REFERENCES "public"."products"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "products_tags" ADD CONSTRAINT "products_tags_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."products"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "products_key_features" ADD CONSTRAINT "products_key_features_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."products"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "products_faqs" ADD CONSTRAINT "products_faqs_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."products"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_home_hero_slides" ADD CONSTRAINT "site_settings_home_hero_slides_image_id_media_id_fk" FOREIGN KEY ("image_id") REFERENCES "public"."media"("id") ON DELETE set null ON UPDATE no action;
  ALTER TABLE "site_settings_home_hero_slides" ADD CONSTRAINT "site_settings_home_hero_slides_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_rels" ADD CONSTRAINT "site_settings_rels_parent_fk" FOREIGN KEY ("parent_id") REFERENCES "public"."site_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_rels" ADD CONSTRAINT "site_settings_rels_products_fk" FOREIGN KEY ("products_id") REFERENCES "public"."products"("id") ON DELETE cascade ON UPDATE no action;
  CREATE INDEX "products_applications_order_idx" ON "products_applications" USING btree ("order");
  CREATE INDEX "products_applications_parent_idx" ON "products_applications" USING btree ("parent_id");
  CREATE INDEX "products_tags_order_idx" ON "products_tags" USING btree ("_order");
  CREATE INDEX "products_tags_parent_id_idx" ON "products_tags" USING btree ("_parent_id");
  CREATE INDEX "products_key_features_order_idx" ON "products_key_features" USING btree ("_order");
  CREATE INDEX "products_key_features_parent_id_idx" ON "products_key_features" USING btree ("_parent_id");
  CREATE INDEX "products_faqs_order_idx" ON "products_faqs" USING btree ("_order");
  CREATE INDEX "products_faqs_parent_id_idx" ON "products_faqs" USING btree ("_parent_id");
  CREATE INDEX "site_settings_home_hero_slides_order_idx" ON "site_settings_home_hero_slides" USING btree ("_order");
  CREATE INDEX "site_settings_home_hero_slides_parent_id_idx" ON "site_settings_home_hero_slides" USING btree ("_parent_id");
  CREATE INDEX "site_settings_home_hero_slides_image_idx" ON "site_settings_home_hero_slides" USING btree ("image_id");
  CREATE INDEX "site_settings_rels_order_idx" ON "site_settings_rels" USING btree ("order");
  CREATE INDEX "site_settings_rels_parent_idx" ON "site_settings_rels" USING btree ("parent_id");
  CREATE INDEX "site_settings_rels_path_idx" ON "site_settings_rels" USING btree ("path");
  CREATE INDEX "site_settings_rels_products_id_idx" ON "site_settings_rels" USING btree ("products_id");`)
}

export async function down({ db, payload, req }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
   DROP TABLE "products_applications" CASCADE;
  DROP TABLE "products_tags" CASCADE;
  DROP TABLE "products_key_features" CASCADE;
  DROP TABLE "products_faqs" CASCADE;
  DROP TABLE "site_settings_home_hero_slides" CASCADE;
  DROP TABLE "site_settings_rels" CASCADE;
  ALTER TABLE "products" ALTER COLUMN "category" SET DATA TYPE text;
  DROP TYPE "public"."enum_products_category";
  CREATE TYPE "public"."enum_products_category" AS ENUM('telescopic-boom-truck-mounted-cranes', 'knuckle-boom-truck-mounted-cranes', 'knuckle-boom-marine-cranes', 'telescopic-boom-marine-cranes', 'pickup-cranes', 'forklift-fly-jib-cranes', 'mini-dumpers', 'other-cranes');
  ALTER TABLE "products" ALTER COLUMN "category" SET DATA TYPE "public"."enum_products_category" USING "category"::"public"."enum_products_category";
  ALTER TABLE "products_gallery" DROP COLUMN "label";
  ALTER TABLE "products" DROP COLUMN "capacity_value";
  ALTER TABLE "products" DROP COLUMN "boom_type";
  ALTER TABLE "products" DROP COLUMN "availability";
  ALTER TABLE "products" DROP COLUMN "is_featured";
  ALTER TABLE "products" DROP COLUMN "popularity";
  ALTER TABLE "products" DROP COLUMN "sort_order";
  DROP TYPE "public"."enum_products_applications";
  DROP TYPE "public"."enum_products_boom_type";
  DROP TYPE "public"."enum_products_availability";`)
}
