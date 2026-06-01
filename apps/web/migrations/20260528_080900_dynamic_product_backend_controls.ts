import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "products" ALTER COLUMN "boom_type" DROP DEFAULT;
    ALTER TABLE "products" ALTER COLUMN "boom_type" TYPE varchar USING "boom_type"::text;
    ALTER TABLE "products" ALTER COLUMN "boom_type" SET DEFAULT 'custom-solution';
    ALTER TABLE "products" ALTER COLUMN "availability" DROP DEFAULT;
    ALTER TABLE "products" ALTER COLUMN "availability" TYPE varchar USING "availability"::text;
    ALTER TABLE "products" ALTER COLUMN "availability" SET DEFAULT 'customizable';
    ALTER TABLE "products_applications" ALTER COLUMN "value" TYPE varchar USING "value"::text;

    ALTER TABLE "product_categories" ALTER COLUMN "icon_name" DROP DEFAULT;
    ALTER TABLE "product_categories" ALTER COLUMN "icon_name" TYPE varchar USING "icon_name"::text;
    ALTER TABLE "product_categories" ALTER COLUMN "icon_name" SET DEFAULT 'truck-crane';
    ALTER TABLE "product_categories" ALTER COLUMN "menu_group" DROP DEFAULT;
    ALTER TABLE "product_categories" ALTER COLUMN "menu_group" TYPE varchar USING "menu_group"::text;
    ALTER TABLE "product_categories" ALTER COLUMN "menu_group" SET DEFAULT 'truck-mounted-crane';
    ALTER TABLE "product_categories" ALTER COLUMN "listing_group" DROP DEFAULT;
    ALTER TABLE "product_categories" ALTER COLUMN "listing_group" TYPE varchar USING "listing_group"::text;
    ALTER TABLE "product_categories" ALTER COLUMN "listing_group" SET DEFAULT 'Crane Accessories';

    ALTER TABLE "site_settings_product_menu_groups" ALTER COLUMN "group_key" TYPE varchar USING "group_key"::text;
    ALTER TABLE "cap_ranges" ALTER COLUMN "key" TYPE varchar USING "key"::text;
    ALTER TABLE "boom_dict" ALTER COLUMN "key" TYPE varchar USING "key"::text;
    ALTER TABLE "app_dict" ALTER COLUMN "key" TYPE varchar USING "key"::text;
    ALTER TABLE "status_dict" ALTER COLUMN "key" TYPE varchar USING "key"::text;

    CREATE TABLE "products_application_cards" (
      "_order" integer NOT NULL,
      "_parent_id" integer NOT NULL,
      "id" varchar PRIMARY KEY NOT NULL,
      "image_id" integer,
      "image_path" varchar,
      "icon_name" varchar DEFAULT 'construction',
      "sort_order" numeric DEFAULT 100
    );

    CREATE TABLE "products_application_cards_locales" (
      "title" varchar NOT NULL,
      "text" varchar NOT NULL,
      "id" serial PRIMARY KEY NOT NULL,
      "_locale" "_locales" NOT NULL,
      "_parent_id" varchar NOT NULL
    );

    ALTER TABLE "products_application_cards" ADD CONSTRAINT "products_application_cards_image_id_media_id_fk" FOREIGN KEY ("image_id") REFERENCES "public"."media"("id") ON DELETE set null ON UPDATE no action;
    ALTER TABLE "products_application_cards" ADD CONSTRAINT "products_application_cards_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."products"("id") ON DELETE cascade ON UPDATE no action;
    ALTER TABLE "products_application_cards_locales" ADD CONSTRAINT "products_application_cards_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."products_application_cards"("id") ON DELETE cascade ON UPDATE no action;
    CREATE INDEX "products_application_cards_image_idx" ON "products_application_cards" USING btree ("image_id");
    CREATE INDEX "products_application_cards_order_idx" ON "products_application_cards" USING btree ("_order");
    CREATE INDEX "products_application_cards_parent_id_idx" ON "products_application_cards" USING btree ("_parent_id");
    CREATE UNIQUE INDEX "products_application_cards_locales_locale_parent_id_unique" ON "products_application_cards_locales" USING btree ("_locale","_parent_id");
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "products_application_cards_locales" DROP CONSTRAINT "products_application_cards_locales_parent_id_fk";
    ALTER TABLE "products_application_cards" DROP CONSTRAINT "products_application_cards_image_id_media_id_fk";
    ALTER TABLE "products_application_cards" DROP CONSTRAINT "products_application_cards_parent_id_fk";
    DROP INDEX "products_application_cards_locales_locale_parent_id_unique";
    DROP INDEX "products_application_cards_image_idx";
    DROP INDEX "products_application_cards_order_idx";
    DROP INDEX "products_application_cards_parent_id_idx";
    DROP TABLE "products_application_cards_locales";
    DROP TABLE "products_application_cards";
  `);
}
