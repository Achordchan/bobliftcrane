import { MigrateUpArgs, MigrateDownArgs, sql } from '@payloadcms/db-postgres'

export async function up({ db, payload, req }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
   CREATE TYPE "public"."enum_cap_ranges_key" AS ENUM('0 - 5 tons', '5 - 10 tons', '10 - 20 tons', '20 - 50 tons', '50+ tons');
  CREATE TYPE "public"."enum_boom_dict_key" AS ENUM('Telescopic Boom', 'Knuckle Boom', 'Straight Boom', 'Fly Jib', 'Custom Solution');
  CREATE TYPE "public"."enum_app_dict_key" AS ENUM('Construction', 'Logistics', 'Municipal Engineering', 'Marine', 'Agriculture', 'Mining');
  CREATE TYPE "public"."enum_app_dict_icon_name" AS ENUM('construction', 'truck', 'building', 'ship', 'tractor', 'wrench');
  CREATE TYPE "public"."enum_status_dict_key" AS ENUM('In Stock', 'Best Seller', 'Customizable', 'Pre-Order');
  CREATE TABLE "cap_ranges" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"key" "enum_cap_ranges_key" NOT NULL,
	"min" numeric NOT NULL,
	"max" numeric,
	"enabled" boolean DEFAULT true,
	"sort_order" numeric DEFAULT 100
  );

  CREATE TABLE "cap_ranges_locales" (
	"label" varchar DEFAULT '0 - 5 tons',
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "boom_dict" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"key" "enum_boom_dict_key" NOT NULL,
	"enabled" boolean DEFAULT true,
	"sort_order" numeric DEFAULT 100
  );

  CREATE TABLE "boom_dict_locales" (
	"label" varchar DEFAULT 'Telescopic Boom',
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "app_dict" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"key" "enum_app_dict_key" NOT NULL,
	"image_id" integer,
	"image_path" varchar,
	"icon_name" "enum_app_dict_icon_name" DEFAULT 'construction',
	"enabled" boolean DEFAULT true,
	"sort_order" numeric DEFAULT 100
  );

  CREATE TABLE "app_dict_locales" (
	"label" varchar DEFAULT 'Construction',
	"title" varchar DEFAULT 'Construction',
	"text" varchar DEFAULT 'Building sites, structural lifting, material handling.',
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "status_dict" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"key" "enum_status_dict_key" NOT NULL,
	"enabled" boolean DEFAULT true,
	"sort_order" numeric DEFAULT 100
  );

  CREATE TABLE "status_dict_locales" (
	"label" varchar DEFAULT 'In Stock',
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  ALTER TABLE "cap_ranges" ADD CONSTRAINT "cap_ranges_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "cap_ranges_locales" ADD CONSTRAINT "cap_ranges_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."cap_ranges"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "boom_dict" ADD CONSTRAINT "boom_dict_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "boom_dict_locales" ADD CONSTRAINT "boom_dict_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."boom_dict"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "app_dict" ADD CONSTRAINT "app_dict_image_id_media_id_fk" FOREIGN KEY ("image_id") REFERENCES "public"."media"("id") ON DELETE set null ON UPDATE no action;
  ALTER TABLE "app_dict" ADD CONSTRAINT "app_dict_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "app_dict_locales" ADD CONSTRAINT "app_dict_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."app_dict"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "status_dict" ADD CONSTRAINT "status_dict_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "status_dict_locales" ADD CONSTRAINT "status_dict_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."status_dict"("id") ON DELETE cascade ON UPDATE no action;
  CREATE INDEX "cap_ranges_order_idx" ON "cap_ranges" USING btree ("_order");
  CREATE INDEX "cap_ranges_parent_id_idx" ON "cap_ranges" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "cap_ranges_locales_locale_parent_id_unique" ON "cap_ranges_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "boom_dict_order_idx" ON "boom_dict" USING btree ("_order");
  CREATE INDEX "boom_dict_parent_id_idx" ON "boom_dict" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "boom_dict_locales_locale_parent_id_unique" ON "boom_dict_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "app_dict_order_idx" ON "app_dict" USING btree ("_order");
  CREATE INDEX "app_dict_parent_id_idx" ON "app_dict" USING btree ("_parent_id");
  CREATE INDEX "app_dict_image_idx" ON "app_dict" USING btree ("image_id");
  CREATE UNIQUE INDEX "app_dict_locales_locale_parent_id_unique" ON "app_dict_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "status_dict_order_idx" ON "status_dict" USING btree ("_order");
  CREATE INDEX "status_dict_parent_id_idx" ON "status_dict" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "status_dict_locales_locale_parent_id_unique" ON "status_dict_locales" USING btree ("_locale","_parent_id");`)
}

export async function down({ db, payload, req }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
   DROP TABLE "cap_ranges" CASCADE;
  DROP TABLE "cap_ranges_locales" CASCADE;
  DROP TABLE "boom_dict" CASCADE;
  DROP TABLE "boom_dict_locales" CASCADE;
  DROP TABLE "app_dict" CASCADE;
  DROP TABLE "app_dict_locales" CASCADE;
  DROP TABLE "status_dict" CASCADE;
  DROP TABLE "status_dict_locales" CASCADE;
  DROP TYPE "public"."enum_cap_ranges_key";
  DROP TYPE "public"."enum_boom_dict_key";
  DROP TYPE "public"."enum_app_dict_key";
  DROP TYPE "public"."enum_app_dict_icon_name";
  DROP TYPE "public"."enum_status_dict_key";`)
}
