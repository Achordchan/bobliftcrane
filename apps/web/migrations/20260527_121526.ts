import { MigrateUpArgs, MigrateDownArgs, sql } from '@payloadcms/db-postgres'

export async function up({ db, payload, req }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
   CREATE TABLE "site_settings_product_listing_ui_banner_paragraphs" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL
  );

  CREATE TABLE "site_settings_product_listing_ui_banner_paragraphs_locales" (
	"text" varchar DEFAULT 'Explore reliable BOB-LIFT lifting and construction machinery for global projects.',
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "site_settings_product_detail_ui_hotspots" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL
  );

  CREATE TABLE "site_settings_product_detail_ui_hotspots_locales" (
	"label" varchar DEFAULT 'Telescopic Boom',
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "site_settings_product_detail_ui_default_features" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL
  );

  CREATE TABLE "site_settings_product_detail_ui_default_features_locales" (
	"title" varchar DEFAULT 'Flexible Boom Operation',
	"text" varchar DEFAULT 'Telescopic boom extends for versatile lifting in multiple working conditions.',
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "site_settings_product_detail_ui_default_gallery_labels" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL
  );

  CREATE TABLE "site_settings_product_detail_ui_default_gallery_labels_locales" (
	"label" varchar DEFAULT 'Full crane structure diagram',
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "site_settings_product_detail_ui_default_faqs" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL
  );

  CREATE TABLE "site_settings_product_detail_ui_default_faqs_locales" (
	"question" varchar DEFAULT 'Which truck chassis can be matched with this model?',
	"answer" varchar DEFAULT 'Send the working conditions, destination and chassis details to BOB-LIFT. The engineering team will confirm a practical configuration before quotation.',
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  ALTER TABLE "site_settings" ADD COLUMN "product_listing_ui_banner_image_id" integer;
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_banner_label" varchar DEFAULT 'BOB-LIFT Product Range';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_banner_title" varchar DEFAULT 'Construction Equipment';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_banner_image_alt" varchar DEFAULT 'BOB-LIFT truck mounted crane';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_result_summary_template" varchar DEFAULT 'Showing {shown} of {total} products';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_filter_button_label" varchar DEFAULT 'Filter';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_filters_title" varchar DEFAULT 'Filters';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_clear_all_label" varchar DEFAULT 'Clear All';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_close_filters_label" varchar DEFAULT 'Close filters';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_filter_groups_categories" varchar DEFAULT 'Product Category';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_filter_groups_capacities" varchar DEFAULT 'Lifting Capacity';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_filter_groups_boom_types" varchar DEFAULT 'Boom Type';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_filter_groups_applications" varchar DEFAULT 'Application';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_filter_groups_availability" varchar DEFAULT 'Availability';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_sort_label" varchar DEFAULT 'Sort by:';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_sort_options_recommended" varchar DEFAULT 'Recommended';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_sort_options_capacity_asc" varchar DEFAULT 'Capacity: Low to High';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_sort_options_capacity_desc" varchar DEFAULT 'Capacity: High to Low';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_sort_options_newest" varchar DEFAULT 'Newest';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_sort_options_popular" varchar DEFAULT 'Popular';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_view_mode_label" varchar DEFAULT 'Product view mode';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_grid_view_label" varchar DEFAULT 'Grid view';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_list_view_label" varchar DEFAULT 'List view';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_favorite_add_label" varchar DEFAULT 'Add to favorites';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_favorite_remove_label" varchar DEFAULT 'Remove from favorites';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_quick_view_label_template" varchar DEFAULT 'Quick view {product}';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_request_quote_label" varchar DEFAULT 'Request Quote';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_view_details_label" varchar DEFAULT 'View Details';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_empty_title" varchar DEFAULT 'No matching products found';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_empty_text" varchar DEFAULT 'Try adjusting your filters or contact us for a custom lifting solution.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_clear_filters_button_label" varchar DEFAULT 'Clear Filters';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_previous_page_label" varchar DEFAULT 'Previous';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_next_page_label" varchar DEFAULT 'Next';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_pagination_label" varchar DEFAULT 'Product pagination';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_status_labels_in_stock" varchar DEFAULT 'In Stock';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_status_labels_best_seller" varchar DEFAULT 'Best Seller';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_status_labels_customizable" varchar DEFAULT 'Customizable';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_status_labels_pre_order" varchar DEFAULT 'Pre-Order';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_spec_labels_max_lift" varchar DEFAULT 'Max Lift';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_spec_labels_boom_length" varchar DEFAULT 'Boom Length';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_spec_labels_rotation" varchar DEFAULT 'Rotation';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_spec_labels_boom_type" varchar DEFAULT 'Boom Type';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_spec_labels_specification_status" varchar DEFAULT 'Specification Status';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_listing_ui_spec_labels_parameter_review" varchar DEFAULT 'Parameter Review';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_request_quote_label" varchar DEFAULT 'Request Quote';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_download_catalog_label" varchar DEFAULT 'Download Catalog';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_view_technical_specs_label" varchar DEFAULT 'View Technical Specs';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_anchor_labels_overview" varchar DEFAULT 'Overview';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_anchor_labels_features" varchar DEFAULT 'Key Features';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_anchor_labels_technical" varchar DEFAULT 'Technical Specifications';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_anchor_labels_details" varchar DEFAULT 'Product Details';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_anchor_labels_catalog" varchar DEFAULT 'Catalog';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_anchor_labels_applications" varchar DEFAULT 'Applications';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_anchor_labels_faq" varchar DEFAULT 'FAQ';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_anchor_labels_quote" varchar DEFAULT 'Request Quote';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_overview_title" varchar DEFAULT 'Built for reliable truck-mounted lifting work.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_overview_text_template" varchar DEFAULT '{description} The configuration focuses on smooth operation, stable control and practical export use for contractors, fleet buyers and project teams.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_technical_title" varchar DEFAULT 'Technical Specifications';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_catalog_brand_label" varchar DEFAULT 'BOB-LIFT';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_catalog_cover_title" varchar DEFAULT 'Product Catalog';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_download_center_label" varchar DEFAULT 'Download Center';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_download_catalog_title" varchar DEFAULT 'Download Product Catalog';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_catalog_description_template" varchar DEFAULT '{catalogTitle} includes core specifications, working range information, and configuration notes for project evaluation.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_request_pdf_label" varchar DEFAULT 'Request PDF by Email';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_gallery_title" varchar DEFAULT 'Product Detail Gallery';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_gallery_empty_label" varchar DEFAULT 'Product Detail View';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_gallery_empty_title" varchar DEFAULT 'Engineering visuals for product evaluation.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_gallery_empty_text" varchar DEFAULT 'Present boom structure, installation layout, hydraulic system, operating range, and chassis mounting details in one focused section.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_applications_title" varchar DEFAULT 'Applications';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_faq_title" varchar DEFAULT 'FAQ';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_quote_title" varchar DEFAULT 'Need a truck crane matched to your chassis and lifting task?';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_quote_text" varchar DEFAULT 'Send your working radius, load requirement, destination country, and truck chassis information. BOB-LIFT will help match a practical configuration.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_contact_engineer_label" varchar DEFAULT 'Contact Engineer';
  ALTER TABLE "site_settings_product_listing_ui_banner_paragraphs" ADD CONSTRAINT "site_settings_product_listing_ui_banner_paragraphs_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_product_listing_ui_banner_paragraphs_locales" ADD CONSTRAINT "site_settings_product_listing_ui_banner_paragraphs_locale_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings_product_listing_ui_banner_paragraphs"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_product_detail_ui_hotspots" ADD CONSTRAINT "site_settings_product_detail_ui_hotspots_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_product_detail_ui_hotspots_locales" ADD CONSTRAINT "site_settings_product_detail_ui_hotspots_locales_parent_i_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings_product_detail_ui_hotspots"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_product_detail_ui_default_features" ADD CONSTRAINT "site_settings_product_detail_ui_default_features_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_product_detail_ui_default_features_locales" ADD CONSTRAINT "site_settings_product_detail_ui_default_features_locales__fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings_product_detail_ui_default_features"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_product_detail_ui_default_gallery_labels" ADD CONSTRAINT "site_settings_product_detail_ui_default_gallery_labels_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_product_detail_ui_default_gallery_labels_locales" ADD CONSTRAINT "site_settings_product_detail_ui_default_gallery_labels_lo_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings_product_detail_ui_default_gallery_labels"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_product_detail_ui_default_faqs" ADD CONSTRAINT "site_settings_product_detail_ui_default_faqs_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_product_detail_ui_default_faqs_locales" ADD CONSTRAINT "site_settings_product_detail_ui_default_faqs_locales_pare_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings_product_detail_ui_default_faqs"("id") ON DELETE cascade ON UPDATE no action;
  CREATE INDEX "site_settings_product_listing_ui_banner_paragraphs_order_idx" ON "site_settings_product_listing_ui_banner_paragraphs" USING btree ("_order");
  CREATE INDEX "site_settings_product_listing_ui_banner_paragraphs_parent_id_idx" ON "site_settings_product_listing_ui_banner_paragraphs" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "site_settings_product_listing_ui_banner_paragraphs_locales_l" ON "site_settings_product_listing_ui_banner_paragraphs_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "site_settings_product_detail_ui_hotspots_order_idx" ON "site_settings_product_detail_ui_hotspots" USING btree ("_order");
  CREATE INDEX "site_settings_product_detail_ui_hotspots_parent_id_idx" ON "site_settings_product_detail_ui_hotspots" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "site_settings_product_detail_ui_hotspots_locales_locale_pare" ON "site_settings_product_detail_ui_hotspots_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "site_settings_product_detail_ui_default_features_order_idx" ON "site_settings_product_detail_ui_default_features" USING btree ("_order");
  CREATE INDEX "site_settings_product_detail_ui_default_features_parent_id_idx" ON "site_settings_product_detail_ui_default_features" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "site_settings_product_detail_ui_default_features_locales_loc" ON "site_settings_product_detail_ui_default_features_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "site_settings_product_detail_ui_default_gallery_labels_order_idx" ON "site_settings_product_detail_ui_default_gallery_labels" USING btree ("_order");
  CREATE INDEX "site_settings_product_detail_ui_default_gallery_labels_parent_id_idx" ON "site_settings_product_detail_ui_default_gallery_labels" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "site_settings_product_detail_ui_default_gallery_labels_local" ON "site_settings_product_detail_ui_default_gallery_labels_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "site_settings_product_detail_ui_default_faqs_order_idx" ON "site_settings_product_detail_ui_default_faqs" USING btree ("_order");
  CREATE INDEX "site_settings_product_detail_ui_default_faqs_parent_id_idx" ON "site_settings_product_detail_ui_default_faqs" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "site_settings_product_detail_ui_default_faqs_locales_locale_" ON "site_settings_product_detail_ui_default_faqs_locales" USING btree ("_locale","_parent_id");
  ALTER TABLE "site_settings" ADD CONSTRAINT "site_settings_product_listing_ui_banner_image_id_media_id_fk" FOREIGN KEY ("product_listing_ui_banner_image_id") REFERENCES "public"."media"("id") ON DELETE set null ON UPDATE no action;
  CREATE INDEX "site_settings_product_listing_ui_product_listing_ui_bann_idx" ON "site_settings" USING btree ("product_listing_ui_banner_image_id");`)
}

export async function down({ db, payload, req }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
   ALTER TABLE "site_settings_product_listing_ui_banner_paragraphs" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_product_listing_ui_banner_paragraphs_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_product_detail_ui_hotspots" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_product_detail_ui_hotspots_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_product_detail_ui_default_features" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_product_detail_ui_default_features_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_product_detail_ui_default_gallery_labels" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_product_detail_ui_default_gallery_labels_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_product_detail_ui_default_faqs" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_product_detail_ui_default_faqs_locales" DISABLE ROW LEVEL SECURITY;
  DROP TABLE "site_settings_product_listing_ui_banner_paragraphs" CASCADE;
  DROP TABLE "site_settings_product_listing_ui_banner_paragraphs_locales" CASCADE;
  DROP TABLE "site_settings_product_detail_ui_hotspots" CASCADE;
  DROP TABLE "site_settings_product_detail_ui_hotspots_locales" CASCADE;
  DROP TABLE "site_settings_product_detail_ui_default_features" CASCADE;
  DROP TABLE "site_settings_product_detail_ui_default_features_locales" CASCADE;
  DROP TABLE "site_settings_product_detail_ui_default_gallery_labels" CASCADE;
  DROP TABLE "site_settings_product_detail_ui_default_gallery_labels_locales" CASCADE;
  DROP TABLE "site_settings_product_detail_ui_default_faqs" CASCADE;
  DROP TABLE "site_settings_product_detail_ui_default_faqs_locales" CASCADE;
  ALTER TABLE "site_settings" DROP CONSTRAINT "site_settings_product_listing_ui_banner_image_id_media_id_fk";

  DROP INDEX "site_settings_product_listing_ui_product_listing_ui_bann_idx";
  ALTER TABLE "site_settings" DROP COLUMN "product_listing_ui_banner_image_id";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_banner_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_banner_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_banner_image_alt";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_result_summary_template";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_filter_button_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_filters_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_clear_all_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_close_filters_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_filter_groups_categories";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_filter_groups_capacities";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_filter_groups_boom_types";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_filter_groups_applications";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_filter_groups_availability";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_sort_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_sort_options_recommended";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_sort_options_capacity_asc";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_sort_options_capacity_desc";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_sort_options_newest";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_sort_options_popular";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_view_mode_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_grid_view_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_list_view_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_favorite_add_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_favorite_remove_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_quick_view_label_template";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_request_quote_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_view_details_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_empty_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_empty_text";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_clear_filters_button_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_previous_page_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_next_page_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_pagination_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_status_labels_in_stock";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_status_labels_best_seller";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_status_labels_customizable";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_status_labels_pre_order";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_spec_labels_max_lift";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_spec_labels_boom_length";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_spec_labels_rotation";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_spec_labels_boom_type";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_spec_labels_specification_status";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_listing_ui_spec_labels_parameter_review";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_request_quote_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_download_catalog_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_view_technical_specs_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_anchor_labels_overview";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_anchor_labels_features";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_anchor_labels_technical";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_anchor_labels_details";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_anchor_labels_catalog";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_anchor_labels_applications";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_anchor_labels_faq";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_anchor_labels_quote";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_overview_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_overview_text_template";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_technical_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_catalog_brand_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_catalog_cover_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_download_center_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_download_catalog_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_catalog_description_template";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_request_pdf_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_gallery_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_gallery_empty_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_gallery_empty_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_gallery_empty_text";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_applications_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_faq_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_quote_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_quote_text";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_contact_engineer_label";`)
}
