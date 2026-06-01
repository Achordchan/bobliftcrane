import { MigrateUpArgs, MigrateDownArgs, sql } from '@payloadcms/db-postgres'

export async function up({ db, payload, req }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
   CREATE TYPE "public"."enum_product_categories_icon_name" AS ENUM('truck-crane', 'marine-crane', 'attachments', 'knuckle-truck', 'aerial-platform', 'knuckle-marine', 'telescopic-marine', 'pickup-crane', 'forklift-jib', 'mini-dumper', 'other-crane');
  CREATE TYPE "public"."enum_product_categories_menu_group" AS ENUM('truck-mounted-crane', 'marine-crane', 'compact-attachments');
  CREATE TYPE "public"."enum_product_categories_listing_group" AS ENUM('Truck Mounted Crane', 'Marine Crane', 'Aerial Work Platform', 'Mini Dumper', 'Forklift Fly Jib Crane', 'Pickup Truck Crane', 'Crane Accessories');
  CREATE TYPE "public"."enum_product_categories_status" AS ENUM('draft', 'published');
  CREATE TABLE "product_categories_banner_paragraphs" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"text" varchar NOT NULL
  );

  CREATE TABLE "product_categories" (
	"id" serial PRIMARY KEY NOT NULL,
	"title" varchar NOT NULL,
	"slug" varchar NOT NULL,
	"short_title" varchar,
	"legacy_path" varchar,
	"description" varchar,
	"image_id" integer,
	"icon_name" "enum_product_categories_icon_name" DEFAULT 'truck-crane',
	"menu_group" "enum_product_categories_menu_group" DEFAULT 'truck-mounted-crane' NOT NULL,
	"listing_group" "enum_product_categories_listing_group" DEFAULT 'Crane Accessories' NOT NULL,
	"show_in_mega_menu" boolean DEFAULT true,
	"show_on_home" boolean DEFAULT true,
	"sort_order" numeric DEFAULT 100,
	"banner_label" varchar,
	"banner_title" varchar,
	"banner_image_id" integer,
	"seo_meta_title" varchar,
	"seo_meta_description" varchar,
	"seo_canonical_path" varchar,
	"status" "enum_product_categories_status" DEFAULT 'draft' NOT NULL,
	"published_at" timestamp(3) with time zone,
	"updated_at" timestamp(3) with time zone DEFAULT now() NOT NULL,
	"created_at" timestamp(3) with time zone DEFAULT now() NOT NULL
  );

  ALTER TABLE "products" ALTER COLUMN "category" SET DEFAULT 'other-cranes';
  ALTER TABLE "products" ADD COLUMN "category_ref_id" integer;
  ALTER TABLE "payload_locked_documents_rels" ADD COLUMN "product_categories_id" integer;
  ALTER TABLE "product_categories_banner_paragraphs" ADD CONSTRAINT "product_categories_banner_paragraphs_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."product_categories"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "product_categories" ADD CONSTRAINT "product_categories_image_id_media_id_fk" FOREIGN KEY ("image_id") REFERENCES "public"."media"("id") ON DELETE set null ON UPDATE no action;
  ALTER TABLE "product_categories" ADD CONSTRAINT "product_categories_banner_image_id_media_id_fk" FOREIGN KEY ("banner_image_id") REFERENCES "public"."media"("id") ON DELETE set null ON UPDATE no action;
  CREATE INDEX "product_categories_banner_paragraphs_order_idx" ON "product_categories_banner_paragraphs" USING btree ("_order");
  CREATE INDEX "product_categories_banner_paragraphs_parent_id_idx" ON "product_categories_banner_paragraphs" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "product_categories_slug_idx" ON "product_categories" USING btree ("slug");
  CREATE INDEX "product_categories_image_idx" ON "product_categories" USING btree ("image_id");
  CREATE INDEX "product_categories_banner_image_idx" ON "product_categories" USING btree ("banner_image_id");
  CREATE INDEX "product_categories_updated_at_idx" ON "product_categories" USING btree ("updated_at");
  CREATE INDEX "product_categories_created_at_idx" ON "product_categories" USING btree ("created_at");
  ALTER TABLE "products" ADD CONSTRAINT "products_category_ref_id_product_categories_id_fk" FOREIGN KEY ("category_ref_id") REFERENCES "public"."product_categories"("id") ON DELETE set null ON UPDATE no action;
  ALTER TABLE "payload_locked_documents_rels" ADD CONSTRAINT "payload_locked_documents_rels_product_categories_fk" FOREIGN KEY ("product_categories_id") REFERENCES "public"."product_categories"("id") ON DELETE cascade ON UPDATE no action;
  CREATE INDEX "products_category_ref_idx" ON "products" USING btree ("category_ref_id");
  CREATE INDEX "payload_locked_documents_rels_product_categories_id_idx" ON "payload_locked_documents_rels" USING btree ("product_categories_id");

  INSERT INTO "product_categories" ("title", "slug", "short_title", "legacy_path", "description", "icon_name", "menu_group", "listing_group", "show_in_mega_menu", "show_on_home", "sort_order", "banner_label", "banner_title", "status", "published_at", "updated_at", "created_at")
  VALUES
  ('Telescopic Boom Truck Mounted Cranes','telescopic-boom-truck-mounted-cranes','Telescopic Truck Cranes','/telescopic-boom-truck-mounted-crane','Road transport lifting models with straight telescopic boom reach.','truck-crane','truck-mounted-crane','Truck Mounted Crane',true,true,10,'BOB-LIFT Truck Mounted Cranes','Telescopic Boom Truck Mounted Cranes','published',now(),now(),now()),
  ('Knuckle Boom Truck Mounted Cranes','knuckle-boom-truck-mounted-cranes','Knuckle Truck Cranes','/knuckle-boom-truck-mounted-crane','Folding boom truck cranes for flexible loading and compact working areas.','knuckle-truck','truck-mounted-crane','Truck Mounted Crane',true,true,20,'BOB-LIFT Truck Mounted Cranes','Knuckle Boom Truck Mounted Cranes','published',now(),now(),now()),
  ('Knuckle Boom Marine Cranes','knuckle-boom-marine-cranes','Knuckle Marine Cranes','/knuckle-boom-marine-crane','Marine-grade folding cranes for vessel, port and deck operations.','knuckle-marine','marine-crane','Marine Crane',true,true,30,'BOB-LIFT Marine Cranes','Knuckle Boom Marine Cranes','published',now(),now(),now()),
  ('Telescopic Boom Marine Cranes','telescopic-boom-marine-cranes','Telescopic Marine Cranes','/telescopic-boom-marine-crane','Straight boom marine cranes for dock, ship and offshore lifting.','telescopic-marine','marine-crane','Marine Crane',true,true,40,'BOB-LIFT Marine Cranes','Telescopic Boom Marine Cranes','published',now(),now(),now()),
  ('Pickup Cranes','pickup-cranes','Pickup Cranes','/pickup-crane','Light pickup-mounted cranes for mobile service and field loading.','pickup-crane','compact-attachments','Pickup Truck Crane',true,true,50,'BOB-LIFT Light Lifting Range','Pickup Cranes','published',now(),now(),now()),
  ('Forklift Fly Jib Cranes','forklift-fly-jib-cranes','Forklift Fly Jib','/forklift-fly-jib-crane','Forklift-mounted fly jib attachments for warehouse and yard lifting.','forklift-jib','compact-attachments','Forklift Fly Jib Crane',true,true,60,'BOB-LIFT Attachment Range','Forklift Fly Jib Cranes','published',now(),now(),now()),
  ('Mini Dumpers','mini-dumpers','Mini Dumpers','/mini-dumper','Compact dumpers for construction, agriculture and narrow jobsites.','mini-dumper','compact-attachments','Mini Dumper',true,true,70,'BOB-LIFT Compact Equipment','Mini Dumpers','published',now(),now(),now()),
  ('Other Cranes','other-cranes','Other Cranes','/other-crane.html','Special crane configurations and attachments for custom lifting work.','other-crane','compact-attachments','Crane Accessories',true,true,80,'BOB-LIFT Custom Lifting Range','Other Cranes','published',now(),now(),now())
  ON CONFLICT ("slug") DO NOTHING;`)
}

export async function down({ db, payload, req }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
   ALTER TABLE "product_categories_banner_paragraphs" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "product_categories" DISABLE ROW LEVEL SECURITY;
  DROP TABLE "product_categories_banner_paragraphs" CASCADE;
  DROP TABLE "product_categories" CASCADE;
  ALTER TABLE "products" DROP CONSTRAINT "products_category_ref_id_product_categories_id_fk";

  ALTER TABLE "payload_locked_documents_rels" DROP CONSTRAINT "payload_locked_documents_rels_product_categories_fk";

  DROP INDEX "products_category_ref_idx";
  DROP INDEX "payload_locked_documents_rels_product_categories_id_idx";
  ALTER TABLE "products" ALTER COLUMN "category" DROP DEFAULT;
  ALTER TABLE "products" DROP COLUMN "category_ref_id";
  ALTER TABLE "payload_locked_documents_rels" DROP COLUMN "product_categories_id";
  DROP TYPE "public"."enum_product_categories_icon_name";
  DROP TYPE "public"."enum_product_categories_menu_group";
  DROP TYPE "public"."enum_product_categories_listing_group";
  DROP TYPE "public"."enum_product_categories_status";`)
}
