import { MigrateUpArgs, MigrateDownArgs, sql } from '@payloadcms/db-postgres'

export async function up({ db, payload, req }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
   CREATE TYPE "public"."_locales" AS ENUM('en', 'es', 'ru', 'ar', 'pt', 'fr', 'de', 'tr', 'id', 'vi', 'th', 'hi', 'zh', 'ko');
  CREATE TYPE "public"."enum_site_settings_application_section_items_icon_name" AS ENUM('award', 'building', 'factory', 'globe', 'network', 'package-check', 'ship', 'truck', 'wrench');
  CREATE TYPE "public"."enum_site_settings_about_section_stats_icon_name" AS ENUM('award', 'building', 'factory', 'globe', 'network', 'package-check', 'ship', 'truck', 'wrench');
  CREATE TABLE "product_categories_banner_paragraphs_locales" (
	"text" varchar NOT NULL,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "product_categories_locales" (
	"title" varchar NOT NULL,
	"short_title" varchar,
	"description" varchar,
	"banner_label" varchar,
	"banner_title" varchar,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" integer NOT NULL
  );

  CREATE TABLE "products_tags_locales" (
	"label" varchar NOT NULL,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "products_gallery_locales" (
	"label" varchar,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "products_specs_locales" (
	"label" varchar NOT NULL,
	"value" varchar NOT NULL,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "products_key_features_locales" (
	"title" varchar NOT NULL,
	"text" varchar NOT NULL,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "products_faqs_locales" (
	"question" varchar NOT NULL,
	"answer" varchar NOT NULL,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "products_locales" (
	"title" varchar NOT NULL,
	"product_type" varchar,
	"intro" varchar,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" integer NOT NULL
  );

  CREATE TABLE "articles_locales" (
	"title" varchar NOT NULL,
	"excerpt" varchar,
	"content" jsonb,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" integer NOT NULL
  );

  CREATE TABLE "cases_locales" (
	"title" varchar NOT NULL,
	"country" varchar,
	"industry" varchar,
	"summary" varchar,
	"content" jsonb,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" integer NOT NULL
  );

  CREATE TABLE "site_settings_navigation_items" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"href" varchar NOT NULL,
	"has_menu" boolean DEFAULT false,
	"show_in_header" boolean DEFAULT true,
	"sort_order" numeric DEFAULT 100
  );

  CREATE TABLE "site_settings_navigation_items_locales" (
	"label" varchar NOT NULL,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "site_settings_home_hero_slides_locales" (
	"title" varchar NOT NULL,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "site_settings_trust_bar_marks" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"logo_path" varchar,
	"logo_image_id" integer
  );

  CREATE TABLE "site_settings_trust_bar_marks_locales" (
	"name" varchar NOT NULL,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "site_settings_application_section_items" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"image_id" integer,
	"image_path" varchar,
	"icon_name" "enum_site_settings_application_section_items_icon_name" DEFAULT 'factory'
  );

  CREATE TABLE "site_settings_application_section_items_locales" (
	"title" varchar NOT NULL,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "site_settings_about_section_gallery" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"image_id" integer,
	"image_path" varchar,
	"video" boolean DEFAULT false
  );

  CREATE TABLE "site_settings_about_section_gallery_locales" (
	"alt" varchar NOT NULL,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "site_settings_about_section_stats" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"icon_name" "enum_site_settings_about_section_stats_icon_name" DEFAULT 'award'
  );

  CREATE TABLE "site_settings_about_section_stats_locales" (
	"value" varchar NOT NULL,
	"label" varchar NOT NULL,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "site_settings_about_section_timeline" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL
  );

  CREATE TABLE "site_settings_about_section_timeline_locales" (
	"text" varchar NOT NULL,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "site_settings_social_links" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"href" varchar NOT NULL
  );

  CREATE TABLE "site_settings_social_links_locales" (
	"label" varchar NOT NULL,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "site_settings_footer_groups_links" (
	"_order" integer NOT NULL,
	"_parent_id" varchar NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"href" varchar NOT NULL
  );

  CREATE TABLE "site_settings_footer_groups_links_locales" (
	"label" varchar NOT NULL,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "site_settings_footer_groups" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL
  );

  CREATE TABLE "site_settings_footer_groups_locales" (
	"title" varchar NOT NULL,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "site_settings_legal_links" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"href" varchar NOT NULL
  );

  CREATE TABLE "site_settings_legal_links_locales" (
	"label" varchar NOT NULL,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "site_settings_locales" (
	"site_name" varchar DEFAULT 'BOB-LIFT',
	"address" varchar DEFAULT 'Xuzhou, China',
	"inquiry_subject" varchar DEFAULT 'BOB-LIFT quotation request',
	"default_inquiry_text" varchar DEFAULT 'Hello BOB-LIFT, I would like to request a quotation.',
	"header_top_bar_highlight" varchar DEFAULT 'Global Lifting Solutions.',
	"header_top_bar_text" varchar DEFAULT 'Exported to 80+ Countries Worldwide.',
	"header_top_bar_link_label" varchar DEFAULT 'Learn More +',
	"home_product_section_label" varchar DEFAULT 'Main Products',
	"home_product_section_title" varchar DEFAULT 'BOB-LIFT Crane Solutions',
	"trust_bar_label" varchar DEFAULT 'Certified',
	"trust_bar_title" varchar DEFAULT 'Quality Assurance',
	"trust_bar_description" varchar DEFAULT 'Export-ready documentation.',
	"application_section_label" varchar DEFAULT 'Solutions for Every Industry',
	"application_section_title" varchar DEFAULT 'Lifting Solutions That Build Stronger Futures.',
	"application_section_cta_label" varchar DEFAULT 'View All Applications',
	"about_section_label" varchar DEFAULT 'About BOB-LIFT',
	"about_section_title" varchar DEFAULT 'Engineering Excellence. Powered by People.',
	"about_section_text" varchar,
	"about_section_cta_label" varchar DEFAULT 'More About Us',
	"news_section_label" varchar DEFAULT 'News Center',
	"news_section_title" varchar DEFAULT 'Latest Insights from BOB-LIFT.',
	"news_section_cta_label" varchar DEFAULT 'View All News',
	"final_cta_label" varchar DEFAULT 'Project Consultation',
	"final_cta_title" varchar DEFAULT 'Need a Lifting Solution?',
	"final_cta_button_label" varchar DEFAULT 'Request a Quote',
	"footer_intro" varchar DEFAULT 'Engineered lifting solutions for global projects. Built to lift. Built to last.',
	"copyright" varchar DEFAULT '© 2026 BOB-LIFT. All Rights Reserved.',
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" integer NOT NULL
  );

  ALTER TABLE "site_settings" ADD COLUMN "wechat_id" varchar DEFAULT '15852499415';
  ALTER TABLE "site_settings" ADD COLUMN "wechat_qr_id" integer;
  ALTER TABLE "site_settings" ADD COLUMN "quote_path" varchar DEFAULT '/quote';
  ALTER TABLE "site_settings" ADD COLUMN "header_top_bar_link_href" varchar DEFAULT '/about';
  ALTER TABLE "site_settings" ADD COLUMN "application_section_cta_href" varchar DEFAULT '#application';
  ALTER TABLE "site_settings" ADD COLUMN "about_section_cta_href" varchar DEFAULT '/about';
  ALTER TABLE "site_settings" ADD COLUMN "news_section_cta_href" varchar DEFAULT '/news';
  ALTER TABLE "site_settings" ADD COLUMN "final_cta_button_href" varchar DEFAULT '/quote';
  ALTER TABLE "site_settings" ADD COLUMN "final_cta_image_id" integer;
  ALTER TABLE "site_settings" ADD COLUMN "final_cta_image_path" varchar DEFAULT '/images/cta-lift-photo.png';
  ALTER TABLE "product_categories_banner_paragraphs_locales" ADD CONSTRAINT "product_categories_banner_paragraphs_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."product_categories_banner_paragraphs"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "product_categories_locales" ADD CONSTRAINT "product_categories_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."product_categories"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "products_tags_locales" ADD CONSTRAINT "products_tags_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."products_tags"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "products_gallery_locales" ADD CONSTRAINT "products_gallery_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."products_gallery"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "products_specs_locales" ADD CONSTRAINT "products_specs_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."products_specs"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "products_key_features_locales" ADD CONSTRAINT "products_key_features_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."products_key_features"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "products_faqs_locales" ADD CONSTRAINT "products_faqs_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."products_faqs"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "products_locales" ADD CONSTRAINT "products_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."products"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "articles_locales" ADD CONSTRAINT "articles_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."articles"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "cases_locales" ADD CONSTRAINT "cases_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."cases"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_navigation_items" ADD CONSTRAINT "site_settings_navigation_items_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_navigation_items_locales" ADD CONSTRAINT "site_settings_navigation_items_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings_navigation_items"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_home_hero_slides_locales" ADD CONSTRAINT "site_settings_home_hero_slides_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings_home_hero_slides"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_trust_bar_marks" ADD CONSTRAINT "site_settings_trust_bar_marks_logo_image_id_media_id_fk" FOREIGN KEY ("logo_image_id") REFERENCES "public"."media"("id") ON DELETE set null ON UPDATE no action;
  ALTER TABLE "site_settings_trust_bar_marks" ADD CONSTRAINT "site_settings_trust_bar_marks_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_trust_bar_marks_locales" ADD CONSTRAINT "site_settings_trust_bar_marks_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings_trust_bar_marks"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_application_section_items" ADD CONSTRAINT "site_settings_application_section_items_image_id_media_id_fk" FOREIGN KEY ("image_id") REFERENCES "public"."media"("id") ON DELETE set null ON UPDATE no action;
  ALTER TABLE "site_settings_application_section_items" ADD CONSTRAINT "site_settings_application_section_items_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_application_section_items_locales" ADD CONSTRAINT "site_settings_application_section_items_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings_application_section_items"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_about_section_gallery" ADD CONSTRAINT "site_settings_about_section_gallery_image_id_media_id_fk" FOREIGN KEY ("image_id") REFERENCES "public"."media"("id") ON DELETE set null ON UPDATE no action;
  ALTER TABLE "site_settings_about_section_gallery" ADD CONSTRAINT "site_settings_about_section_gallery_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_about_section_gallery_locales" ADD CONSTRAINT "site_settings_about_section_gallery_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings_about_section_gallery"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_about_section_stats" ADD CONSTRAINT "site_settings_about_section_stats_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_about_section_stats_locales" ADD CONSTRAINT "site_settings_about_section_stats_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings_about_section_stats"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_about_section_timeline" ADD CONSTRAINT "site_settings_about_section_timeline_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_about_section_timeline_locales" ADD CONSTRAINT "site_settings_about_section_timeline_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings_about_section_timeline"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_social_links" ADD CONSTRAINT "site_settings_social_links_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_social_links_locales" ADD CONSTRAINT "site_settings_social_links_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings_social_links"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_footer_groups_links" ADD CONSTRAINT "site_settings_footer_groups_links_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings_footer_groups"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_footer_groups_links_locales" ADD CONSTRAINT "site_settings_footer_groups_links_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings_footer_groups_links"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_footer_groups" ADD CONSTRAINT "site_settings_footer_groups_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_footer_groups_locales" ADD CONSTRAINT "site_settings_footer_groups_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings_footer_groups"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_legal_links" ADD CONSTRAINT "site_settings_legal_links_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_legal_links_locales" ADD CONSTRAINT "site_settings_legal_links_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings_legal_links"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_locales" ADD CONSTRAINT "site_settings_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings"("id") ON DELETE cascade ON UPDATE no action;
  CREATE UNIQUE INDEX "product_categories_banner_paragraphs_locales_locale_parent_i" ON "product_categories_banner_paragraphs_locales" USING btree ("_locale","_parent_id");
  CREATE UNIQUE INDEX "product_categories_locales_locale_parent_id_unique" ON "product_categories_locales" USING btree ("_locale","_parent_id");
  CREATE UNIQUE INDEX "products_tags_locales_locale_parent_id_unique" ON "products_tags_locales" USING btree ("_locale","_parent_id");
  CREATE UNIQUE INDEX "products_gallery_locales_locale_parent_id_unique" ON "products_gallery_locales" USING btree ("_locale","_parent_id");
  CREATE UNIQUE INDEX "products_specs_locales_locale_parent_id_unique" ON "products_specs_locales" USING btree ("_locale","_parent_id");
  CREATE UNIQUE INDEX "products_key_features_locales_locale_parent_id_unique" ON "products_key_features_locales" USING btree ("_locale","_parent_id");
  CREATE UNIQUE INDEX "products_faqs_locales_locale_parent_id_unique" ON "products_faqs_locales" USING btree ("_locale","_parent_id");
  CREATE UNIQUE INDEX "products_locales_locale_parent_id_unique" ON "products_locales" USING btree ("_locale","_parent_id");
  CREATE UNIQUE INDEX "articles_locales_locale_parent_id_unique" ON "articles_locales" USING btree ("_locale","_parent_id");
  CREATE UNIQUE INDEX "cases_locales_locale_parent_id_unique" ON "cases_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "site_settings_navigation_items_order_idx" ON "site_settings_navigation_items" USING btree ("_order");
  CREATE INDEX "site_settings_navigation_items_parent_id_idx" ON "site_settings_navigation_items" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "site_settings_navigation_items_locales_locale_parent_id_uniq" ON "site_settings_navigation_items_locales" USING btree ("_locale","_parent_id");
  CREATE UNIQUE INDEX "site_settings_home_hero_slides_locales_locale_parent_id_uniq" ON "site_settings_home_hero_slides_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "site_settings_trust_bar_marks_order_idx" ON "site_settings_trust_bar_marks" USING btree ("_order");
  CREATE INDEX "site_settings_trust_bar_marks_parent_id_idx" ON "site_settings_trust_bar_marks" USING btree ("_parent_id");
  CREATE INDEX "site_settings_trust_bar_marks_logo_image_idx" ON "site_settings_trust_bar_marks" USING btree ("logo_image_id");
  CREATE UNIQUE INDEX "site_settings_trust_bar_marks_locales_locale_parent_id_uniqu" ON "site_settings_trust_bar_marks_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "site_settings_application_section_items_order_idx" ON "site_settings_application_section_items" USING btree ("_order");
  CREATE INDEX "site_settings_application_section_items_parent_id_idx" ON "site_settings_application_section_items" USING btree ("_parent_id");
  CREATE INDEX "site_settings_application_section_items_image_idx" ON "site_settings_application_section_items" USING btree ("image_id");
  CREATE UNIQUE INDEX "site_settings_application_section_items_locales_locale_paren" ON "site_settings_application_section_items_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "site_settings_about_section_gallery_order_idx" ON "site_settings_about_section_gallery" USING btree ("_order");
  CREATE INDEX "site_settings_about_section_gallery_parent_id_idx" ON "site_settings_about_section_gallery" USING btree ("_parent_id");
  CREATE INDEX "site_settings_about_section_gallery_image_idx" ON "site_settings_about_section_gallery" USING btree ("image_id");
  CREATE UNIQUE INDEX "site_settings_about_section_gallery_locales_locale_parent_id" ON "site_settings_about_section_gallery_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "site_settings_about_section_stats_order_idx" ON "site_settings_about_section_stats" USING btree ("_order");
  CREATE INDEX "site_settings_about_section_stats_parent_id_idx" ON "site_settings_about_section_stats" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "site_settings_about_section_stats_locales_locale_parent_id_u" ON "site_settings_about_section_stats_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "site_settings_about_section_timeline_order_idx" ON "site_settings_about_section_timeline" USING btree ("_order");
  CREATE INDEX "site_settings_about_section_timeline_parent_id_idx" ON "site_settings_about_section_timeline" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "site_settings_about_section_timeline_locales_locale_parent_i" ON "site_settings_about_section_timeline_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "site_settings_social_links_order_idx" ON "site_settings_social_links" USING btree ("_order");
  CREATE INDEX "site_settings_social_links_parent_id_idx" ON "site_settings_social_links" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "site_settings_social_links_locales_locale_parent_id_unique" ON "site_settings_social_links_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "site_settings_footer_groups_links_order_idx" ON "site_settings_footer_groups_links" USING btree ("_order");
  CREATE INDEX "site_settings_footer_groups_links_parent_id_idx" ON "site_settings_footer_groups_links" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "site_settings_footer_groups_links_locales_locale_parent_id_u" ON "site_settings_footer_groups_links_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "site_settings_footer_groups_order_idx" ON "site_settings_footer_groups" USING btree ("_order");
  CREATE INDEX "site_settings_footer_groups_parent_id_idx" ON "site_settings_footer_groups" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "site_settings_footer_groups_locales_locale_parent_id_unique" ON "site_settings_footer_groups_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "site_settings_legal_links_order_idx" ON "site_settings_legal_links" USING btree ("_order");
  CREATE INDEX "site_settings_legal_links_parent_id_idx" ON "site_settings_legal_links" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "site_settings_legal_links_locales_locale_parent_id_unique" ON "site_settings_legal_links_locales" USING btree ("_locale","_parent_id");
  CREATE UNIQUE INDEX "site_settings_locales_locale_parent_id_unique" ON "site_settings_locales" USING btree ("_locale","_parent_id");
  ALTER TABLE "site_settings" ADD CONSTRAINT "site_settings_wechat_qr_id_media_id_fk" FOREIGN KEY ("wechat_qr_id") REFERENCES "public"."media"("id") ON DELETE set null ON UPDATE no action;
  ALTER TABLE "site_settings" ADD CONSTRAINT "site_settings_final_cta_image_id_media_id_fk" FOREIGN KEY ("final_cta_image_id") REFERENCES "public"."media"("id") ON DELETE set null ON UPDATE no action;
  CREATE INDEX "site_settings_wechat_qr_idx" ON "site_settings" USING btree ("wechat_qr_id");
  CREATE INDEX "site_settings_final_cta_final_cta_image_idx" ON "site_settings" USING btree ("final_cta_image_id");
  INSERT INTO "product_categories_banner_paragraphs_locales" ("_parent_id", "_locale", "text")
  SELECT "id", 'en', "text" FROM "product_categories_banner_paragraphs" WHERE "text" IS NOT NULL;
  INSERT INTO "product_categories_locales" ("_parent_id", "_locale", "title", "short_title", "description", "banner_label", "banner_title")
  SELECT "id", 'en', "title", "short_title", "description", "banner_label", "banner_title" FROM "product_categories" WHERE "title" IS NOT NULL;
  INSERT INTO "products_tags_locales" ("_parent_id", "_locale", "label")
  SELECT "id", 'en', "label" FROM "products_tags" WHERE "label" IS NOT NULL;
  INSERT INTO "products_gallery_locales" ("_parent_id", "_locale", "label")
  SELECT "id", 'en', "label" FROM "products_gallery";
  INSERT INTO "products_specs_locales" ("_parent_id", "_locale", "label", "value")
  SELECT "id", 'en', "label", "value" FROM "products_specs" WHERE "label" IS NOT NULL AND "value" IS NOT NULL;
  INSERT INTO "products_key_features_locales" ("_parent_id", "_locale", "title", "text")
  SELECT "id", 'en', "title", "text" FROM "products_key_features" WHERE "title" IS NOT NULL AND "text" IS NOT NULL;
  INSERT INTO "products_faqs_locales" ("_parent_id", "_locale", "question", "answer")
  SELECT "id", 'en', "question", "answer" FROM "products_faqs" WHERE "question" IS NOT NULL AND "answer" IS NOT NULL;
  INSERT INTO "products_locales" ("_parent_id", "_locale", "title", "product_type", "intro")
  SELECT "id", 'en', "title", "product_type", "intro" FROM "products" WHERE "title" IS NOT NULL;
  INSERT INTO "articles_locales" ("_parent_id", "_locale", "title", "excerpt", "content")
  SELECT "id", 'en', "title", "excerpt", "content" FROM "articles" WHERE "title" IS NOT NULL;
  INSERT INTO "cases_locales" ("_parent_id", "_locale", "title", "country", "industry", "summary", "content")
  SELECT "id", 'en', "title", "country", "industry", "summary", "content" FROM "cases" WHERE "title" IS NOT NULL;
  INSERT INTO "site_settings_home_hero_slides_locales" ("_parent_id", "_locale", "title")
  SELECT "id", 'en', "title" FROM "site_settings_home_hero_slides" WHERE "title" IS NOT NULL;
  INSERT INTO "site_settings_locales" ("_parent_id", "_locale", "site_name", "address")
  SELECT "id", 'en', "site_name", "address" FROM "site_settings";
  ALTER TABLE "product_categories_banner_paragraphs" DROP COLUMN "text";
  ALTER TABLE "product_categories" DROP COLUMN "title";
  ALTER TABLE "product_categories" DROP COLUMN "short_title";
  ALTER TABLE "product_categories" DROP COLUMN "description";
  ALTER TABLE "product_categories" DROP COLUMN "banner_label";
  ALTER TABLE "product_categories" DROP COLUMN "banner_title";
  ALTER TABLE "products_tags" DROP COLUMN "label";
  ALTER TABLE "products_gallery" DROP COLUMN "label";
  ALTER TABLE "products_specs" DROP COLUMN "label";
  ALTER TABLE "products_specs" DROP COLUMN "value";
  ALTER TABLE "products_key_features" DROP COLUMN "title";
  ALTER TABLE "products_key_features" DROP COLUMN "text";
  ALTER TABLE "products_faqs" DROP COLUMN "question";
  ALTER TABLE "products_faqs" DROP COLUMN "answer";
  ALTER TABLE "products" DROP COLUMN "title";
  ALTER TABLE "products" DROP COLUMN "product_type";
  ALTER TABLE "products" DROP COLUMN "intro";
  ALTER TABLE "articles" DROP COLUMN "title";
  ALTER TABLE "articles" DROP COLUMN "excerpt";
  ALTER TABLE "articles" DROP COLUMN "content";
  ALTER TABLE "cases" DROP COLUMN "title";
  ALTER TABLE "cases" DROP COLUMN "country";
  ALTER TABLE "cases" DROP COLUMN "industry";
  ALTER TABLE "cases" DROP COLUMN "summary";
  ALTER TABLE "cases" DROP COLUMN "content";
  ALTER TABLE "site_settings_home_hero_slides" DROP COLUMN "title";
  ALTER TABLE "site_settings" DROP COLUMN "site_name";
  ALTER TABLE "site_settings" DROP COLUMN "address";`)
}

export async function down({ db, payload, req }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
   ALTER TABLE "product_categories_banner_paragraphs_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "product_categories_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "products_tags_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "products_gallery_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "products_specs_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "products_key_features_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "products_faqs_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "products_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "articles_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "cases_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_navigation_items" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_navigation_items_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_home_hero_slides_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_trust_bar_marks" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_trust_bar_marks_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_application_section_items" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_application_section_items_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_about_section_gallery" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_about_section_gallery_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_about_section_stats" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_about_section_stats_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_about_section_timeline" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_about_section_timeline_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_social_links" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_social_links_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_footer_groups_links" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_footer_groups_links_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_footer_groups" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_footer_groups_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_legal_links" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_legal_links_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_locales" DISABLE ROW LEVEL SECURITY;
  DROP TABLE "product_categories_banner_paragraphs_locales" CASCADE;
  DROP TABLE "product_categories_locales" CASCADE;
  DROP TABLE "products_tags_locales" CASCADE;
  DROP TABLE "products_gallery_locales" CASCADE;
  DROP TABLE "products_specs_locales" CASCADE;
  DROP TABLE "products_key_features_locales" CASCADE;
  DROP TABLE "products_faqs_locales" CASCADE;
  DROP TABLE "products_locales" CASCADE;
  DROP TABLE "articles_locales" CASCADE;
  DROP TABLE "cases_locales" CASCADE;
  DROP TABLE "site_settings_navigation_items" CASCADE;
  DROP TABLE "site_settings_navigation_items_locales" CASCADE;
  DROP TABLE "site_settings_home_hero_slides_locales" CASCADE;
  DROP TABLE "site_settings_trust_bar_marks" CASCADE;
  DROP TABLE "site_settings_trust_bar_marks_locales" CASCADE;
  DROP TABLE "site_settings_application_section_items" CASCADE;
  DROP TABLE "site_settings_application_section_items_locales" CASCADE;
  DROP TABLE "site_settings_about_section_gallery" CASCADE;
  DROP TABLE "site_settings_about_section_gallery_locales" CASCADE;
  DROP TABLE "site_settings_about_section_stats" CASCADE;
  DROP TABLE "site_settings_about_section_stats_locales" CASCADE;
  DROP TABLE "site_settings_about_section_timeline" CASCADE;
  DROP TABLE "site_settings_about_section_timeline_locales" CASCADE;
  DROP TABLE "site_settings_social_links" CASCADE;
  DROP TABLE "site_settings_social_links_locales" CASCADE;
  DROP TABLE "site_settings_footer_groups_links" CASCADE;
  DROP TABLE "site_settings_footer_groups_links_locales" CASCADE;
  DROP TABLE "site_settings_footer_groups" CASCADE;
  DROP TABLE "site_settings_footer_groups_locales" CASCADE;
  DROP TABLE "site_settings_legal_links" CASCADE;
  DROP TABLE "site_settings_legal_links_locales" CASCADE;
  DROP TABLE "site_settings_locales" CASCADE;
  ALTER TABLE "site_settings" DROP CONSTRAINT "site_settings_wechat_qr_id_media_id_fk";

  ALTER TABLE "site_settings" DROP CONSTRAINT "site_settings_final_cta_image_id_media_id_fk";

  DROP INDEX "site_settings_wechat_qr_idx";
  DROP INDEX "site_settings_final_cta_final_cta_image_idx";
  ALTER TABLE "product_categories_banner_paragraphs" ADD COLUMN "text" varchar NOT NULL;
  ALTER TABLE "product_categories" ADD COLUMN "title" varchar NOT NULL;
  ALTER TABLE "product_categories" ADD COLUMN "short_title" varchar;
  ALTER TABLE "product_categories" ADD COLUMN "description" varchar;
  ALTER TABLE "product_categories" ADD COLUMN "banner_label" varchar;
  ALTER TABLE "product_categories" ADD COLUMN "banner_title" varchar;
  ALTER TABLE "products_tags" ADD COLUMN "label" varchar NOT NULL;
  ALTER TABLE "products_gallery" ADD COLUMN "label" varchar;
  ALTER TABLE "products_specs" ADD COLUMN "label" varchar NOT NULL;
  ALTER TABLE "products_specs" ADD COLUMN "value" varchar NOT NULL;
  ALTER TABLE "products_key_features" ADD COLUMN "title" varchar NOT NULL;
  ALTER TABLE "products_key_features" ADD COLUMN "text" varchar NOT NULL;
  ALTER TABLE "products_faqs" ADD COLUMN "question" varchar NOT NULL;
  ALTER TABLE "products_faqs" ADD COLUMN "answer" varchar NOT NULL;
  ALTER TABLE "products" ADD COLUMN "title" varchar NOT NULL;
  ALTER TABLE "products" ADD COLUMN "product_type" varchar;
  ALTER TABLE "products" ADD COLUMN "intro" varchar;
  ALTER TABLE "articles" ADD COLUMN "title" varchar NOT NULL;
  ALTER TABLE "articles" ADD COLUMN "excerpt" varchar;
  ALTER TABLE "articles" ADD COLUMN "content" jsonb;
  ALTER TABLE "cases" ADD COLUMN "title" varchar NOT NULL;
  ALTER TABLE "cases" ADD COLUMN "country" varchar;
  ALTER TABLE "cases" ADD COLUMN "industry" varchar;
  ALTER TABLE "cases" ADD COLUMN "summary" varchar;
  ALTER TABLE "cases" ADD COLUMN "content" jsonb;
  ALTER TABLE "site_settings_home_hero_slides" ADD COLUMN "title" varchar NOT NULL;
  ALTER TABLE "site_settings" ADD COLUMN "site_name" varchar DEFAULT 'BOB-LIFT';
  ALTER TABLE "site_settings" ADD COLUMN "address" varchar DEFAULT 'Xuzhou, China';
  ALTER TABLE "site_settings" DROP COLUMN "wechat_id";
  ALTER TABLE "site_settings" DROP COLUMN "wechat_qr_id";
  ALTER TABLE "site_settings" DROP COLUMN "quote_path";
  ALTER TABLE "site_settings" DROP COLUMN "header_top_bar_link_href";
  ALTER TABLE "site_settings" DROP COLUMN "application_section_cta_href";
  ALTER TABLE "site_settings" DROP COLUMN "about_section_cta_href";
  ALTER TABLE "site_settings" DROP COLUMN "news_section_cta_href";
  ALTER TABLE "site_settings" DROP COLUMN "final_cta_button_href";
  ALTER TABLE "site_settings" DROP COLUMN "final_cta_image_id";
  ALTER TABLE "site_settings" DROP COLUMN "final_cta_image_path";
  DROP TYPE "public"."_locales";
  DROP TYPE "public"."enum_site_settings_application_section_items_icon_name";
  DROP TYPE "public"."enum_site_settings_about_section_stats_icon_name";`)
}
