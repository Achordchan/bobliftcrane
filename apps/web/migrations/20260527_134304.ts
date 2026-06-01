import { MigrateUpArgs, MigrateDownArgs, sql } from '@payloadcms/db-postgres'

export async function up({ db, payload, req }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
   CREATE TYPE "public"."enum_site_settings_product_menu_groups_group_key" AS ENUM('truck-mounted-crane', 'marine-crane', 'compact-attachments');
  CREATE TYPE "public"."enum_site_settings_language_options_locale" AS ENUM('en', 'es', 'ru', 'ar', 'pt', 'fr', 'de', 'tr', 'id', 'vi', 'th', 'hi', 'zh', 'ko');
  CREATE TABLE "site_settings_product_menu_groups" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"group_key" "enum_site_settings_product_menu_groups_group_key" NOT NULL,
	"href" varchar DEFAULT '/products',
	"icon_name" varchar,
	"sort_order" numeric DEFAULT 100
  );

  CREATE TABLE "site_settings_product_menu_groups_locales" (
	"title" varchar NOT NULL,
	"description" varchar,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "site_settings_language_options" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"locale" "enum_site_settings_language_options_locale" NOT NULL,
	"flag" varchar NOT NULL,
	"enabled" boolean DEFAULT true,
	"sort_order" numeric DEFAULT 100
  );

  CREATE TABLE "site_settings_language_options_locales" (
	"label" varchar NOT NULL,
	"market" varchar,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  ALTER TABLE "site_settings_locales" ADD COLUMN "chrome_ui_language_menu_aria_label" varchar DEFAULT 'Change language';
  ALTER TABLE "site_settings_locales" ADD COLUMN "chrome_ui_mobile_menu_aria_label" varchar DEFAULT 'Open mobile navigation';
  ALTER TABLE "site_settings_locales" ADD COLUMN "chrome_ui_mobile_menu_eyebrow" varchar DEFAULT 'Navigation';
  ALTER TABLE "site_settings_locales" ADD COLUMN "chrome_ui_mobile_menu_title" varchar DEFAULT 'BOB-LIFT Menu';
  ALTER TABLE "site_settings_locales" ADD COLUMN "chrome_ui_mobile_product_categories_label" varchar DEFAULT 'Product Categories';
  ALTER TABLE "site_settings_locales" ADD COLUMN "chrome_ui_request_quote_label" varchar DEFAULT 'Request Quote';
  ALTER TABLE "site_settings_locales" ADD COLUMN "chrome_ui_product_catalog_title" varchar DEFAULT 'Explore All Products';
  ALTER TABLE "site_settings_locales" ADD COLUMN "chrome_ui_product_catalog_description" varchar DEFAULT 'Discover our full range of lifting solutions.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "chrome_ui_view_product_catalog_label" varchar DEFAULT 'View Product Catalog';
  ALTER TABLE "site_settings_locales" ADD COLUMN "chrome_ui_floating_quote_label" varchar DEFAULT 'Quote';
  ALTER TABLE "site_settings_locales" ADD COLUMN "chrome_ui_floating_quote_tooltip" varchar DEFAULT 'Request Quote';
  ALTER TABLE "site_settings_locales" ADD COLUMN "chrome_ui_floating_we_chat_label" varchar DEFAULT 'WeChat';
  ALTER TABLE "site_settings_locales" ADD COLUMN "chrome_ui_we_chat_title" varchar DEFAULT 'WeChat QR Code';
  ALTER TABLE "site_settings_locales" ADD COLUMN "chrome_ui_we_chat_description_template" varchar DEFAULT 'Scan or search ID: {wechatId}';
  ALTER TABLE "site_settings_locales" ADD COLUMN "chrome_ui_close_we_chat_label" varchar DEFAULT 'Close WeChat QR code';
  ALTER TABLE "site_settings_product_menu_groups" ADD CONSTRAINT "site_settings_product_menu_groups_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_product_menu_groups_locales" ADD CONSTRAINT "site_settings_product_menu_groups_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings_product_menu_groups"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_language_options" ADD CONSTRAINT "site_settings_language_options_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_language_options_locales" ADD CONSTRAINT "site_settings_language_options_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings_language_options"("id") ON DELETE cascade ON UPDATE no action;
  CREATE INDEX "site_settings_product_menu_groups_order_idx" ON "site_settings_product_menu_groups" USING btree ("_order");
  CREATE INDEX "site_settings_product_menu_groups_parent_id_idx" ON "site_settings_product_menu_groups" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "site_settings_product_menu_groups_locales_locale_parent_id_u" ON "site_settings_product_menu_groups_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "site_settings_language_options_order_idx" ON "site_settings_language_options" USING btree ("_order");
  CREATE INDEX "site_settings_language_options_parent_id_idx" ON "site_settings_language_options" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "site_settings_language_options_locales_locale_parent_id_uniq" ON "site_settings_language_options_locales" USING btree ("_locale","_parent_id");`)
}

export async function down({ db, payload, req }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
   DROP TABLE "site_settings_product_menu_groups" CASCADE;
  DROP TABLE "site_settings_product_menu_groups_locales" CASCADE;
  DROP TABLE "site_settings_language_options" CASCADE;
  DROP TABLE "site_settings_language_options_locales" CASCADE;
  ALTER TABLE "site_settings_locales" DROP COLUMN "chrome_ui_language_menu_aria_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "chrome_ui_mobile_menu_aria_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "chrome_ui_mobile_menu_eyebrow";
  ALTER TABLE "site_settings_locales" DROP COLUMN "chrome_ui_mobile_menu_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "chrome_ui_mobile_product_categories_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "chrome_ui_request_quote_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "chrome_ui_product_catalog_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "chrome_ui_product_catalog_description";
  ALTER TABLE "site_settings_locales" DROP COLUMN "chrome_ui_view_product_catalog_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "chrome_ui_floating_quote_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "chrome_ui_floating_quote_tooltip";
  ALTER TABLE "site_settings_locales" DROP COLUMN "chrome_ui_floating_we_chat_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "chrome_ui_we_chat_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "chrome_ui_we_chat_description_template";
  ALTER TABLE "site_settings_locales" DROP COLUMN "chrome_ui_close_we_chat_label";
  DROP TYPE "public"."enum_site_settings_product_menu_groups_group_key";
  DROP TYPE "public"."enum_site_settings_language_options_locale";`)
}
