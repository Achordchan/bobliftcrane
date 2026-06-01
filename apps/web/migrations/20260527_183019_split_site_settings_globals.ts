import { MigrateUpArgs, MigrateDownArgs, sql } from '@payloadcms/db-postgres'

export async function up({ db, payload, req }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
   CREATE TYPE "public"."enum_home_settings_application_section_items_icon_name" AS ENUM('award', 'building', 'factory', 'globe', 'network', 'package-check', 'ship', 'truck', 'wrench');
  CREATE TYPE "public"."enum_home_settings_about_section_stats_icon_name" AS ENUM('award', 'building', 'factory', 'globe', 'network', 'package-check', 'ship', 'truck', 'wrench');
  CREATE TYPE "public"."enum_page_text_settings_quote_page_ui_workflow_items_icon_name" AS ENUM('file-text', 'mail-check', 'message-circle');
  CREATE TABLE "home_settings_home_hero_slides" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"image_id" integer NOT NULL
  );

  CREATE TABLE "home_settings_home_hero_slides_locales" (
	"title" varchar NOT NULL,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "home_settings_trust_bar_marks" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"logo_path" varchar,
	"logo_image_id" integer
  );

  CREATE TABLE "home_settings_trust_bar_marks_locales" (
	"name" varchar NOT NULL,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "home_settings_application_section_items" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"image_id" integer,
	"image_path" varchar,
	"icon_name" "enum_home_settings_application_section_items_icon_name" DEFAULT 'factory'
  );

  CREATE TABLE "home_settings_application_section_items_locales" (
	"title" varchar NOT NULL,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "home_settings_about_section_gallery" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"image_id" integer,
	"image_path" varchar,
	"video" boolean DEFAULT false
  );

  CREATE TABLE "home_settings_about_section_gallery_locales" (
	"alt" varchar NOT NULL,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "home_settings_about_section_stats" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"icon_name" "enum_home_settings_about_section_stats_icon_name" DEFAULT 'award'
  );

  CREATE TABLE "home_settings_about_section_stats_locales" (
	"value" varchar NOT NULL,
	"label" varchar NOT NULL,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "home_settings_about_section_timeline" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL
  );

  CREATE TABLE "home_settings_about_section_timeline_locales" (
	"text" varchar NOT NULL,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "home_settings" (
	"id" serial PRIMARY KEY NOT NULL,
	"application_section_cta_href" varchar DEFAULT '#application',
	"about_section_cta_href" varchar DEFAULT '/about',
	"news_section_cta_href" varchar DEFAULT '/news',
	"final_cta_button_href" varchar DEFAULT '/quote',
	"final_cta_image_id" integer,
	"final_cta_image_path" varchar DEFAULT '/images/cta-lift-photo.png',
	"updated_at" timestamp(3) with time zone,
	"created_at" timestamp(3) with time zone
  );

  CREATE TABLE "home_settings_locales" (
	"home_product_section_label" varchar DEFAULT 'Main Products',
	"home_product_section_title" varchar DEFAULT 'BOB-LIFT Crane Solutions',
	"home_product_section_find_model_title" varchar DEFAULT 'Find Your Model',
	"home_product_section_details_short_label" varchar DEFAULT 'Details',
	"home_product_section_view_details_label" varchar DEFAULT 'View Details',
	"home_product_section_back_to_models_label" varchar DEFAULT 'Back to Models',
	"home_product_section_product_name_label" varchar DEFAULT 'Product Name',
	"home_product_section_model_label" varchar DEFAULT 'Model',
	"home_product_section_model_spec_label" varchar DEFAULT 'Model',
	"home_product_section_capacity_spec_label" varchar DEFAULT 'Capacity',
	"home_product_section_type_spec_label" varchar DEFAULT 'Type',
	"home_product_section_system_spec_label" varchar DEFAULT 'System',
	"home_product_section_system_value" varchar DEFAULT 'Hydraulic',
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
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" integer NOT NULL
  );

  CREATE TABLE "home_settings_rels" (
	"id" serial PRIMARY KEY NOT NULL,
	"order" integer,
	"parent_id" integer NOT NULL,
	"path" varchar NOT NULL,
	"products_id" integer
  );

  CREATE TABLE "product_listing_ui_product_listing_ui_banner_paragraphs" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL
  );

  CREATE TABLE "product_listing_ui_product_listing_ui_banner_paragraphs_locales" (
	"text" varchar DEFAULT 'Explore reliable BOB-LIFT lifting and construction machinery for global projects.',
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "product_listing_ui" (
	"id" serial PRIMARY KEY NOT NULL,
	"product_listing_ui_banner_image_id" integer,
	"updated_at" timestamp(3) with time zone,
	"created_at" timestamp(3) with time zone
  );

  CREATE TABLE "product_listing_ui_locales" (
	"product_listing_ui_banner_label" varchar DEFAULT 'BOB-LIFT Product Range',
	"product_listing_ui_banner_title" varchar DEFAULT 'Construction Equipment',
	"product_listing_ui_banner_image_alt" varchar DEFAULT 'BOB-LIFT truck mounted crane',
	"product_listing_ui_result_summary_template" varchar DEFAULT 'Showing {shown} of {total} products',
	"product_listing_ui_filter_button_label" varchar DEFAULT 'Filter',
	"product_listing_ui_filters_title" varchar DEFAULT 'Filters',
	"product_listing_ui_clear_all_label" varchar DEFAULT 'Clear All',
	"product_listing_ui_close_filters_label" varchar DEFAULT 'Close filters',
	"product_listing_ui_filter_groups_categories" varchar DEFAULT 'Product Category',
	"product_listing_ui_filter_groups_capacities" varchar DEFAULT 'Lifting Capacity',
	"product_listing_ui_filter_groups_boom_types" varchar DEFAULT 'Boom Type',
	"product_listing_ui_filter_groups_applications" varchar DEFAULT 'Application',
	"product_listing_ui_filter_groups_availability" varchar DEFAULT 'Availability',
	"product_listing_ui_sort_label" varchar DEFAULT 'Sort by:',
	"product_listing_ui_sort_options_recommended" varchar DEFAULT 'Recommended',
	"product_listing_ui_sort_options_capacity_asc" varchar DEFAULT 'Capacity: Low to High',
	"product_listing_ui_sort_options_capacity_desc" varchar DEFAULT 'Capacity: High to Low',
	"product_listing_ui_sort_options_newest" varchar DEFAULT 'Newest',
	"product_listing_ui_sort_options_popular" varchar DEFAULT 'Popular',
	"product_listing_ui_view_mode_label" varchar DEFAULT 'Product view mode',
	"product_listing_ui_grid_view_label" varchar DEFAULT 'Grid view',
	"product_listing_ui_list_view_label" varchar DEFAULT 'List view',
	"product_listing_ui_favorite_add_label" varchar DEFAULT 'Add to favorites',
	"product_listing_ui_favorite_remove_label" varchar DEFAULT 'Remove from favorites',
	"product_listing_ui_quick_view_label_template" varchar DEFAULT 'Quick view {product}',
	"product_listing_ui_request_quote_label" varchar DEFAULT 'Request Quote',
	"product_listing_ui_view_details_label" varchar DEFAULT 'View Details',
	"product_listing_ui_empty_title" varchar DEFAULT 'No matching products found',
	"product_listing_ui_empty_text" varchar DEFAULT 'Try adjusting your filters or contact us for a custom lifting solution.',
	"product_listing_ui_clear_filters_button_label" varchar DEFAULT 'Clear Filters',
	"product_listing_ui_previous_page_label" varchar DEFAULT 'Previous',
	"product_listing_ui_next_page_label" varchar DEFAULT 'Next',
	"product_listing_ui_pagination_label" varchar DEFAULT 'Product pagination',
	"product_listing_ui_status_labels_in_stock" varchar DEFAULT 'In Stock',
	"product_listing_ui_status_labels_best_seller" varchar DEFAULT 'Best Seller',
	"product_listing_ui_status_labels_customizable" varchar DEFAULT 'Customizable',
	"product_listing_ui_status_labels_pre_order" varchar DEFAULT 'Pre-Order',
	"product_listing_ui_spec_labels_max_lift" varchar DEFAULT 'Max Lift',
	"product_listing_ui_spec_labels_boom_length" varchar DEFAULT 'Boom Length',
	"product_listing_ui_spec_labels_rotation" varchar DEFAULT 'Rotation',
	"product_listing_ui_spec_labels_boom_type" varchar DEFAULT 'Boom Type',
	"product_listing_ui_spec_labels_specification_status" varchar DEFAULT 'Specification Status',
	"product_listing_ui_spec_labels_parameter_review" varchar DEFAULT 'Parameter Review',
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" integer NOT NULL
  );

  CREATE TABLE "product_detail_ui_product_detail_ui_hotspots" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL
  );

  CREATE TABLE "product_detail_ui_product_detail_ui_hotspots_locales" (
	"label" varchar DEFAULT 'Telescopic Boom',
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "product_detail_ui_product_detail_ui_default_features" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL
  );

  CREATE TABLE "product_detail_ui_product_detail_ui_default_features_locales" (
	"title" varchar DEFAULT 'Flexible Boom Operation',
	"text" varchar DEFAULT 'Telescopic boom extends for versatile lifting in multiple working conditions.',
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "product_detail_ui_product_detail_ui_default_gallery_labels" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL
  );

  CREATE TABLE "product_detail_ui_product_detail_ui_default_gallery_labels_locales" (
	"label" varchar DEFAULT 'Full crane structure diagram',
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "product_detail_ui_product_detail_ui_default_faqs" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL
  );

  CREATE TABLE "product_detail_ui_product_detail_ui_default_faqs_locales" (
	"question" varchar DEFAULT 'Which truck chassis can be matched with this model?',
	"answer" varchar DEFAULT 'Send the working conditions, destination and chassis details to BOB-LIFT. The engineering team will confirm a practical configuration before quotation.',
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "product_detail_ui" (
	"id" serial PRIMARY KEY NOT NULL,
	"updated_at" timestamp(3) with time zone,
	"created_at" timestamp(3) with time zone
  );

  CREATE TABLE "product_detail_ui_locales" (
	"product_detail_ui_request_quote_label" varchar DEFAULT 'Request Quote',
	"product_detail_ui_download_catalog_label" varchar DEFAULT 'Download Catalog',
	"product_detail_ui_view_technical_specs_label" varchar DEFAULT 'View Technical Specs',
	"product_detail_ui_anchor_labels_overview" varchar DEFAULT 'Overview',
	"product_detail_ui_anchor_labels_features" varchar DEFAULT 'Key Features',
	"product_detail_ui_anchor_labels_technical" varchar DEFAULT 'Technical Specifications',
	"product_detail_ui_anchor_labels_details" varchar DEFAULT 'Product Details',
	"product_detail_ui_anchor_labels_catalog" varchar DEFAULT 'Catalog',
	"product_detail_ui_anchor_labels_applications" varchar DEFAULT 'Applications',
	"product_detail_ui_anchor_labels_faq" varchar DEFAULT 'FAQ',
	"product_detail_ui_anchor_labels_quote" varchar DEFAULT 'Request Quote',
	"product_detail_ui_aria_labels_anchor_nav" varchar DEFAULT 'Product detail sections',
	"product_detail_ui_aria_labels_side_nav" varchar DEFAULT 'Product page navigation',
	"product_detail_ui_overview_title" varchar DEFAULT 'Built for reliable truck-mounted lifting work.',
	"product_detail_ui_overview_text_template" varchar DEFAULT '{description} The configuration focuses on smooth operation, stable control and practical export use for contractors, fleet buyers and project teams.',
	"product_detail_ui_technical_title" varchar DEFAULT 'Technical Specifications',
	"product_detail_ui_catalog_brand_label" varchar DEFAULT 'BOB-LIFT',
	"product_detail_ui_catalog_cover_title" varchar DEFAULT 'Product Catalog',
	"product_detail_ui_catalog_cover_alt_template" varchar DEFAULT '{product} catalog cover',
	"product_detail_ui_download_center_label" varchar DEFAULT 'Download Center',
	"product_detail_ui_download_catalog_title" varchar DEFAULT 'Download Product Catalog',
	"product_detail_ui_catalog_description_template" varchar DEFAULT '{catalogTitle} includes core specifications, working range information, and configuration notes for project evaluation.',
	"product_detail_ui_request_pdf_label" varchar DEFAULT 'Request PDF by Email',
	"product_detail_ui_gallery_title" varchar DEFAULT 'Product Detail Gallery',
	"product_detail_ui_gallery_empty_label" varchar DEFAULT 'Product Detail View',
	"product_detail_ui_gallery_empty_title" varchar DEFAULT 'Engineering visuals for product evaluation.',
	"product_detail_ui_gallery_empty_text" varchar DEFAULT 'Present boom structure, installation layout, hydraulic system, operating range, and chassis mounting details in one focused section.',
	"product_detail_ui_gallery_main_image_label" varchar DEFAULT 'Main product view',
	"product_detail_ui_gallery_detail_image_label" varchar DEFAULT 'Product detail image',
	"product_detail_ui_gallery_drawing_label" varchar DEFAULT 'Specification drawing',
	"product_detail_ui_applications_title" varchar DEFAULT 'Applications',
	"product_detail_ui_review_status_labels_needs_review" varchar DEFAULT 'Needs review',
	"product_detail_ui_review_status_labels_in_review" varchar DEFAULT 'In review',
	"product_detail_ui_review_status_labels_verified_from_source" varchar DEFAULT 'Verified from source',
	"product_detail_ui_review_status_labels_changes_required" varchar DEFAULT 'Changes required',
	"product_detail_ui_customization_labels_available" varchar DEFAULT 'Available',
	"product_detail_ui_customization_labels_available_on_request" varchar DEFAULT 'Available on request',
	"product_detail_ui_spec_labels_model" varchar DEFAULT 'Model',
	"product_detail_ui_spec_labels_max_lift" varchar DEFAULT 'Max Lift',
	"product_detail_ui_spec_labels_max_lifting_capacity" varchar DEFAULT 'Max Lifting Capacity',
	"product_detail_ui_spec_labels_boom_length" varchar DEFAULT 'Boom Length',
	"product_detail_ui_spec_labels_max_boom_length" varchar DEFAULT 'Max Boom Length',
	"product_detail_ui_spec_labels_rotation" varchar DEFAULT 'Rotation',
	"product_detail_ui_spec_labels_boom_type" varchar DEFAULT 'Boom Type',
	"product_detail_ui_spec_labels_application" varchar DEFAULT 'Application',
	"product_detail_ui_spec_labels_customization" varchar DEFAULT 'Customization',
	"product_detail_ui_spec_labels_specification_status" varchar DEFAULT 'Specification Status',
	"product_detail_ui_spec_labels_parameter_review" varchar DEFAULT 'Parameter Review',
	"product_detail_ui_faq_title" varchar DEFAULT 'FAQ',
	"product_detail_ui_quote_title" varchar DEFAULT 'Need a truck crane matched to your chassis and lifting task?',
	"product_detail_ui_quote_text" varchar DEFAULT 'Send your working radius, load requirement, destination country, and truck chassis information. BOB-LIFT will help match a practical configuration.',
	"product_detail_ui_contact_engineer_label" varchar DEFAULT 'Contact Engineer',
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" integer NOT NULL
  );

  CREATE TABLE "page_text_settings_quote_page_ui_workflow_items" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"icon_name" "enum_page_text_settings_quote_page_ui_workflow_items_icon_name" DEFAULT 'file-text'
  );

  CREATE TABLE "page_text_settings_quote_page_ui_workflow_items_locales" (
	"title" varchar DEFAULT '',
	"text" varchar DEFAULT '',
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "page_text_settings" (
	"id" serial PRIMARY KEY NOT NULL,
	"contact_page_ui_hero_image" varchar DEFAULT '/images/global-network-blue.jpg',
	"quote_page_ui_hero_image" varchar DEFAULT '/images/cta-lift-photo.png',
	"quote_page_ui_product_link_href" varchar DEFAULT '/products',
	"news_page_ui_hero_image" varchar DEFAULT '/images/factory-workshop.jpg',
	"cases_page_ui_hero_image" varchar DEFAULT '/images/marine-scene.jpg',
	"updated_at" timestamp(3) with time zone,
	"created_at" timestamp(3) with time zone
  );

  CREATE TABLE "page_text_settings_locales" (
	"contact_page_ui_hero_label" varchar DEFAULT 'Contact Us',
	"contact_page_ui_hero_title" varchar DEFAULT 'Talk to BOB-LIFT before choosing a crane.',
	"contact_page_ui_hero_description" varchar DEFAULT 'For model selection, truck chassis matching, marine installation and export questions, use the direct contact channels or create a structured inquiry draft.',
	"contact_page_ui_card_labels_email" varchar DEFAULT 'Email',
	"contact_page_ui_card_labels_phone" varchar DEFAULT 'Phone',
	"contact_page_ui_card_labels_whatsapp" varchar DEFAULT 'WhatsApp',
	"contact_page_ui_card_labels_address" varchar DEFAULT 'Address',
	"contact_page_ui_whatsapp_value" varchar DEFAULT 'Start chat',
	"contact_page_ui_we_chat_title" varchar DEFAULT 'WeChat',
	"contact_page_ui_we_chat_description_template" varchar DEFAULT 'Scan the QR code or search ID {wechatId}. The QR block is kept as a compact card so it does not dominate mobile screens.',
	"contact_page_ui_we_chat_qr_alt" varchar DEFAULT 'BOB-LIFT WeChat QR code',
	"quote_page_ui_hero_label" varchar DEFAULT 'Request Quote',
	"quote_page_ui_hero_title" varchar DEFAULT 'Request a tailored BOB-LIFT quotation.',
	"quote_page_ui_hero_description" varchar DEFAULT 'Share your product interest, destination country, contact details and working conditions. The team can then match a practical lifting solution for your project.',
	"quote_page_ui_workflow_label" varchar DEFAULT 'Quote Workflow',
	"quote_page_ui_workflow_title" varchar DEFAULT 'Fill once, send through your preferred channel.',
	"quote_page_ui_product_link_label" varchar DEFAULT 'Need to choose a model first?',
	"quote_form_ui_label" varchar DEFAULT 'Request Quote',
	"quote_form_ui_title" varchar DEFAULT 'Tell us your lifting requirement.',
	"quote_form_ui_badge" varchar DEFAULT '24h reply',
	"quote_form_ui_fields_name" varchar DEFAULT 'Full Name',
	"quote_form_ui_fields_email" varchar DEFAULT 'Email',
	"quote_form_ui_fields_phone" varchar DEFAULT 'Phone / WhatsApp',
	"quote_form_ui_fields_country" varchar DEFAULT 'Country / Region',
	"quote_form_ui_fields_product" varchar DEFAULT 'Product Interest',
	"quote_form_ui_fields_product_not_decided" varchar DEFAULT 'Not decided yet',
	"quote_form_ui_fields_quantity" varchar DEFAULT 'Expected Quantity',
	"quote_form_ui_fields_message" varchar DEFAULT 'Project Details',
	"quote_form_ui_fields_message_placeholder" varchar DEFAULT 'Load capacity, working radius, truck chassis, destination port, deadline...',
	"quote_form_ui_buttons_save" varchar DEFAULT 'Save Inquiry Draft',
	"quote_form_ui_buttons_saving" varchar DEFAULT 'Saving Inquiry...',
	"quote_form_ui_buttons_email" varchar DEFAULT 'Send Email',
	"quote_form_ui_buttons_whatsapp" varchar DEFAULT 'WhatsApp',
	"quote_form_ui_messages_success_title" varchar DEFAULT 'Inquiry saved in the admin.',
	"quote_form_ui_messages_success_text" varchar DEFAULT 'Use Email or WhatsApp if you also want to send the draft through your preferred app.',
	"quote_form_ui_messages_error_fallback" varchar DEFAULT 'Inquiry could not be saved.',
	"quote_form_ui_inquiry_line_labels_name" varchar DEFAULT 'Name',
	"quote_form_ui_inquiry_line_labels_email" varchar DEFAULT 'Email',
	"quote_form_ui_inquiry_line_labels_phone" varchar DEFAULT 'Phone / WhatsApp',
	"quote_form_ui_inquiry_line_labels_country" varchar DEFAULT 'Country',
	"quote_form_ui_inquiry_line_labels_product" varchar DEFAULT 'Product',
	"quote_form_ui_inquiry_line_labels_quantity" varchar DEFAULT 'Quantity',
	"quote_form_ui_inquiry_line_labels_message" varchar DEFAULT 'Project details',
	"news_page_ui_meta_title" varchar DEFAULT 'News | BOB-LIFT',
	"news_page_ui_meta_description" varchar DEFAULT 'BOB-LIFT product news, company news and lifting solution insights.',
	"news_page_ui_hero_label" varchar DEFAULT 'News Center',
	"news_page_ui_hero_title" varchar DEFAULT 'Updates that help buyers choose lifting equipment faster.',
	"news_page_ui_hero_description" varchar DEFAULT 'Read BOB-LIFT product updates, factory stories and marine solution articles for practical purchasing and equipment-selection reference.',
	"news_page_ui_read_featured_label" varchar DEFAULT 'Read Featured',
	"news_page_ui_request_quote_label" varchar DEFAULT 'Request Quote',
	"news_page_ui_featured_label" varchar DEFAULT 'Featured Article',
	"news_page_ui_read_article_label" varchar DEFAULT 'Read Article',
	"news_page_ui_read_more_label" varchar DEFAULT 'Read More',
	"news_detail_ui_back_label" varchar DEFAULT 'Back to News',
	"news_detail_ui_related_label" varchar DEFAULT 'Related',
	"news_detail_ui_quote_cta_label" varchar DEFAULT 'Ask for solution',
	"cases_page_ui_meta_title" varchar DEFAULT 'Case Center | BOB-LIFT',
	"cases_page_ui_meta_description" varchar DEFAULT 'BOB-LIFT customer cases for marine operations, material handling and aerial work platform applications.',
	"cases_page_ui_hero_label" varchar DEFAULT 'Case Center',
	"cases_page_ui_hero_title" varchar DEFAULT 'Project stories with real working conditions.',
	"cases_page_ui_hero_description" varchar DEFAULT 'Browse customer cases across marine operations, material handling and aerial work applications, then discuss a similar lifting solution with BOB-LIFT.',
	"cases_page_ui_primary_cta_label" varchar DEFAULT 'Plan My Project',
	"cases_page_ui_secondary_cta_label" varchar DEFAULT 'View Cases',
	"cases_page_ui_list_label" varchar DEFAULT 'Field Evidence',
	"cases_page_ui_list_title" varchar DEFAULT 'From lifting challenge to equipment match.',
	"cases_page_ui_card_type_label" varchar DEFAULT 'Customer Case',
	"cases_page_ui_view_case_label" varchar DEFAULT 'View Case',
	"case_detail_ui_back_label" varchar DEFAULT 'Back to Case Center',
	"case_detail_ui_facts_title" varchar DEFAULT 'Case Facts',
	"case_detail_ui_discuss_similar_label" varchar DEFAULT 'Discuss Similar Project',
	"about_page_ui_meta_title" varchar DEFAULT 'About BOB-LIFT | BOB-LIFT',
	"about_page_ui_meta_description" varchar DEFAULT 'Learn about BOB-LIFT manufacturing, export capability and engineering experience.',
	"about_page_ui_primary_cta_label" varchar DEFAULT 'Contact Team',
	"about_page_ui_secondary_cta_label" varchar DEFAULT 'View Products',
	"about_page_ui_strength_label" varchar DEFAULT 'Company Strength',
	"about_page_ui_strength_title" varchar DEFAULT 'Manufacturing, export and service in one team.',
	"about_page_ui_timeline_label" varchar DEFAULT 'Development Timeline',
	"content_labels_ui_article_categories_company_news" varchar DEFAULT 'Company News',
	"content_labels_ui_article_categories_product_news" varchar DEFAULT 'Product News',
	"content_labels_ui_article_categories_industry_insight" varchar DEFAULT 'Industry Insight',
	"content_labels_ui_case_facts_region" varchar DEFAULT 'Region',
	"content_labels_ui_case_facts_industry" varchar DEFAULT 'Industry',
	"content_labels_ui_case_facts_priority" varchar DEFAULT 'Priority',
	"content_labels_ui_case_facts_default_priority" varchar DEFAULT 'Equipment Match',
	"content_labels_ui_case_facts_default_case_type" varchar DEFAULT 'Customer Case',
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" integer NOT NULL
  );

  ALTER TABLE "site_settings_home_hero_slides" DISABLE ROW LEVEL SECURITY;
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
  ALTER TABLE "site_settings_quote_page_ui_workflow_items" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_quote_page_ui_workflow_items_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "site_settings_rels" DISABLE ROW LEVEL SECURITY;
  DROP TABLE "site_settings_home_hero_slides" CASCADE;
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
  DROP TABLE "site_settings_quote_page_ui_workflow_items" CASCADE;
  DROP TABLE "site_settings_quote_page_ui_workflow_items_locales" CASCADE;
  DROP TABLE "site_settings_rels" CASCADE;
  ALTER TABLE "cap_ranges" DROP CONSTRAINT "cap_ranges_parent_id_fk";

  ALTER TABLE "boom_dict" DROP CONSTRAINT "boom_dict_parent_id_fk";

  ALTER TABLE "app_dict" DROP CONSTRAINT "app_dict_parent_id_fk";

  ALTER TABLE "status_dict" DROP CONSTRAINT "status_dict_parent_id_fk";

  ALTER TABLE "site_settings" DROP CONSTRAINT "site_settings_final_cta_image_id_media_id_fk";

  ALTER TABLE "site_settings" DROP CONSTRAINT "site_settings_product_listing_ui_banner_image_id_media_id_fk";

  DROP INDEX "site_settings_final_cta_final_cta_image_idx";
  DROP INDEX "site_settings_product_listing_ui_product_listing_ui_bann_idx";
  ALTER TABLE "home_settings_home_hero_slides" ADD CONSTRAINT "home_settings_home_hero_slides_image_id_media_id_fk" FOREIGN KEY ("image_id") REFERENCES "public"."media"("id") ON DELETE set null ON UPDATE no action;
  ALTER TABLE "home_settings_home_hero_slides" ADD CONSTRAINT "home_settings_home_hero_slides_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."home_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "home_settings_home_hero_slides_locales" ADD CONSTRAINT "home_settings_home_hero_slides_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."home_settings_home_hero_slides"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "home_settings_trust_bar_marks" ADD CONSTRAINT "home_settings_trust_bar_marks_logo_image_id_media_id_fk" FOREIGN KEY ("logo_image_id") REFERENCES "public"."media"("id") ON DELETE set null ON UPDATE no action;
  ALTER TABLE "home_settings_trust_bar_marks" ADD CONSTRAINT "home_settings_trust_bar_marks_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."home_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "home_settings_trust_bar_marks_locales" ADD CONSTRAINT "home_settings_trust_bar_marks_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."home_settings_trust_bar_marks"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "home_settings_application_section_items" ADD CONSTRAINT "home_settings_application_section_items_image_id_media_id_fk" FOREIGN KEY ("image_id") REFERENCES "public"."media"("id") ON DELETE set null ON UPDATE no action;
  ALTER TABLE "home_settings_application_section_items" ADD CONSTRAINT "home_settings_application_section_items_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."home_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "home_settings_application_section_items_locales" ADD CONSTRAINT "home_settings_application_section_items_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."home_settings_application_section_items"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "home_settings_about_section_gallery" ADD CONSTRAINT "home_settings_about_section_gallery_image_id_media_id_fk" FOREIGN KEY ("image_id") REFERENCES "public"."media"("id") ON DELETE set null ON UPDATE no action;
  ALTER TABLE "home_settings_about_section_gallery" ADD CONSTRAINT "home_settings_about_section_gallery_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."home_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "home_settings_about_section_gallery_locales" ADD CONSTRAINT "home_settings_about_section_gallery_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."home_settings_about_section_gallery"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "home_settings_about_section_stats" ADD CONSTRAINT "home_settings_about_section_stats_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."home_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "home_settings_about_section_stats_locales" ADD CONSTRAINT "home_settings_about_section_stats_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."home_settings_about_section_stats"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "home_settings_about_section_timeline" ADD CONSTRAINT "home_settings_about_section_timeline_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."home_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "home_settings_about_section_timeline_locales" ADD CONSTRAINT "home_settings_about_section_timeline_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."home_settings_about_section_timeline"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "home_settings" ADD CONSTRAINT "home_settings_final_cta_image_id_media_id_fk" FOREIGN KEY ("final_cta_image_id") REFERENCES "public"."media"("id") ON DELETE set null ON UPDATE no action;
  ALTER TABLE "home_settings_locales" ADD CONSTRAINT "home_settings_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."home_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "home_settings_rels" ADD CONSTRAINT "home_settings_rels_parent_fk" FOREIGN KEY ("parent_id") REFERENCES "public"."home_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "home_settings_rels" ADD CONSTRAINT "home_settings_rels_products_fk" FOREIGN KEY ("products_id") REFERENCES "public"."products"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "product_listing_ui_product_listing_ui_banner_paragraphs" ADD CONSTRAINT "product_listing_ui_product_listing_ui_banner_paragraphs_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."product_listing_ui"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "product_listing_ui_product_listing_ui_banner_paragraphs_locales" ADD CONSTRAINT "product_listing_ui_product_listing_ui_banner_paragraphs_l_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."product_listing_ui_product_listing_ui_banner_paragraphs"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "product_listing_ui" ADD CONSTRAINT "product_listing_ui_product_listing_ui_banner_image_id_media_id_fk" FOREIGN KEY ("product_listing_ui_banner_image_id") REFERENCES "public"."media"("id") ON DELETE set null ON UPDATE no action;
  ALTER TABLE "product_listing_ui_locales" ADD CONSTRAINT "product_listing_ui_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."product_listing_ui"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "product_detail_ui_product_detail_ui_hotspots" ADD CONSTRAINT "product_detail_ui_product_detail_ui_hotspots_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."product_detail_ui"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "product_detail_ui_product_detail_ui_hotspots_locales" ADD CONSTRAINT "product_detail_ui_product_detail_ui_hotspots_locales_pare_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."product_detail_ui_product_detail_ui_hotspots"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "product_detail_ui_product_detail_ui_default_features" ADD CONSTRAINT "product_detail_ui_product_detail_ui_default_features_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."product_detail_ui"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "product_detail_ui_product_detail_ui_default_features_locales" ADD CONSTRAINT "product_detail_ui_product_detail_ui_default_features_loca_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."product_detail_ui_product_detail_ui_default_features"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "product_detail_ui_product_detail_ui_default_gallery_labels" ADD CONSTRAINT "product_detail_ui_product_detail_ui_default_gallery_labels_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."product_detail_ui"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "product_detail_ui_product_detail_ui_default_gallery_labels_locales" ADD CONSTRAINT "product_detail_ui_product_detail_ui_default_gallery_label_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."product_detail_ui_product_detail_ui_default_gallery_labels"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "product_detail_ui_product_detail_ui_default_faqs" ADD CONSTRAINT "product_detail_ui_product_detail_ui_default_faqs_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."product_detail_ui"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "product_detail_ui_product_detail_ui_default_faqs_locales" ADD CONSTRAINT "product_detail_ui_product_detail_ui_default_faqs_locales__fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."product_detail_ui_product_detail_ui_default_faqs"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "product_detail_ui_locales" ADD CONSTRAINT "product_detail_ui_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."product_detail_ui"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "page_text_settings_quote_page_ui_workflow_items" ADD CONSTRAINT "page_text_settings_quote_page_ui_workflow_items_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."page_text_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "page_text_settings_quote_page_ui_workflow_items_locales" ADD CONSTRAINT "page_text_settings_quote_page_ui_workflow_items_locales_p_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."page_text_settings_quote_page_ui_workflow_items"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "page_text_settings_locales" ADD CONSTRAINT "page_text_settings_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."page_text_settings"("id") ON DELETE cascade ON UPDATE no action;
  CREATE INDEX "home_settings_home_hero_slides_order_idx" ON "home_settings_home_hero_slides" USING btree ("_order");
  CREATE INDEX "home_settings_home_hero_slides_parent_id_idx" ON "home_settings_home_hero_slides" USING btree ("_parent_id");
  CREATE INDEX "home_settings_home_hero_slides_image_idx" ON "home_settings_home_hero_slides" USING btree ("image_id");
  CREATE UNIQUE INDEX "home_settings_home_hero_slides_locales_locale_parent_id_uniq" ON "home_settings_home_hero_slides_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "home_settings_trust_bar_marks_order_idx" ON "home_settings_trust_bar_marks" USING btree ("_order");
  CREATE INDEX "home_settings_trust_bar_marks_parent_id_idx" ON "home_settings_trust_bar_marks" USING btree ("_parent_id");
  CREATE INDEX "home_settings_trust_bar_marks_logo_image_idx" ON "home_settings_trust_bar_marks" USING btree ("logo_image_id");
  CREATE UNIQUE INDEX "home_settings_trust_bar_marks_locales_locale_parent_id_uniqu" ON "home_settings_trust_bar_marks_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "home_settings_application_section_items_order_idx" ON "home_settings_application_section_items" USING btree ("_order");
  CREATE INDEX "home_settings_application_section_items_parent_id_idx" ON "home_settings_application_section_items" USING btree ("_parent_id");
  CREATE INDEX "home_settings_application_section_items_image_idx" ON "home_settings_application_section_items" USING btree ("image_id");
  CREATE UNIQUE INDEX "home_settings_application_section_items_locales_locale_paren" ON "home_settings_application_section_items_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "home_settings_about_section_gallery_order_idx" ON "home_settings_about_section_gallery" USING btree ("_order");
  CREATE INDEX "home_settings_about_section_gallery_parent_id_idx" ON "home_settings_about_section_gallery" USING btree ("_parent_id");
  CREATE INDEX "home_settings_about_section_gallery_image_idx" ON "home_settings_about_section_gallery" USING btree ("image_id");
  CREATE UNIQUE INDEX "home_settings_about_section_gallery_locales_locale_parent_id" ON "home_settings_about_section_gallery_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "home_settings_about_section_stats_order_idx" ON "home_settings_about_section_stats" USING btree ("_order");
  CREATE INDEX "home_settings_about_section_stats_parent_id_idx" ON "home_settings_about_section_stats" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "home_settings_about_section_stats_locales_locale_parent_id_u" ON "home_settings_about_section_stats_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "home_settings_about_section_timeline_order_idx" ON "home_settings_about_section_timeline" USING btree ("_order");
  CREATE INDEX "home_settings_about_section_timeline_parent_id_idx" ON "home_settings_about_section_timeline" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "home_settings_about_section_timeline_locales_locale_parent_i" ON "home_settings_about_section_timeline_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "home_settings_final_cta_final_cta_image_idx" ON "home_settings" USING btree ("final_cta_image_id");
  CREATE UNIQUE INDEX "home_settings_locales_locale_parent_id_unique" ON "home_settings_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "home_settings_rels_order_idx" ON "home_settings_rels" USING btree ("order");
  CREATE INDEX "home_settings_rels_parent_idx" ON "home_settings_rels" USING btree ("parent_id");
  CREATE INDEX "home_settings_rels_path_idx" ON "home_settings_rels" USING btree ("path");
  CREATE INDEX "home_settings_rels_products_id_idx" ON "home_settings_rels" USING btree ("products_id");
  CREATE INDEX "product_listing_ui_product_listing_ui_banner_paragraphs_order_idx" ON "product_listing_ui_product_listing_ui_banner_paragraphs" USING btree ("_order");
  CREATE INDEX "product_listing_ui_product_listing_ui_banner_paragraphs_parent_id_idx" ON "product_listing_ui_product_listing_ui_banner_paragraphs" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "product_listing_ui_product_listing_ui_banner_paragraphs_loca" ON "product_listing_ui_product_listing_ui_banner_paragraphs_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "product_listing_ui_product_listing_ui_product_listing_ui_idx" ON "product_listing_ui" USING btree ("product_listing_ui_banner_image_id");
  CREATE UNIQUE INDEX "product_listing_ui_locales_locale_parent_id_unique" ON "product_listing_ui_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "product_detail_ui_product_detail_ui_hotspots_order_idx" ON "product_detail_ui_product_detail_ui_hotspots" USING btree ("_order");
  CREATE INDEX "product_detail_ui_product_detail_ui_hotspots_parent_id_idx" ON "product_detail_ui_product_detail_ui_hotspots" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "product_detail_ui_product_detail_ui_hotspots_locales_locale_" ON "product_detail_ui_product_detail_ui_hotspots_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "product_detail_ui_product_detail_ui_default_features_order_idx" ON "product_detail_ui_product_detail_ui_default_features" USING btree ("_order");
  CREATE INDEX "product_detail_ui_product_detail_ui_default_features_parent_id_idx" ON "product_detail_ui_product_detail_ui_default_features" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "product_detail_ui_product_detail_ui_default_features_local_1" ON "product_detail_ui_product_detail_ui_default_features_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "product_detail_ui_product_detail_ui_default_gallery_labels_order_idx" ON "product_detail_ui_product_detail_ui_default_gallery_labels" USING btree ("_order");
  CREATE INDEX "product_detail_ui_product_detail_ui_default_gallery_labels_parent_id_idx" ON "product_detail_ui_product_detail_ui_default_gallery_labels" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "product_detail_ui_product_detail_ui_default_gallery_labels_l" ON "product_detail_ui_product_detail_ui_default_gallery_labels_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "product_detail_ui_product_detail_ui_default_faqs_order_idx" ON "product_detail_ui_product_detail_ui_default_faqs" USING btree ("_order");
  CREATE INDEX "product_detail_ui_product_detail_ui_default_faqs_parent_id_idx" ON "product_detail_ui_product_detail_ui_default_faqs" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "product_detail_ui_product_detail_ui_default_faqs_locales_loc" ON "product_detail_ui_product_detail_ui_default_faqs_locales" USING btree ("_locale","_parent_id");
  CREATE UNIQUE INDEX "product_detail_ui_locales_locale_parent_id_unique" ON "product_detail_ui_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "page_text_settings_quote_page_ui_workflow_items_order_idx" ON "page_text_settings_quote_page_ui_workflow_items" USING btree ("_order");
  CREATE INDEX "page_text_settings_quote_page_ui_workflow_items_parent_id_idx" ON "page_text_settings_quote_page_ui_workflow_items" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "page_text_settings_quote_page_ui_workflow_items_locales_loca" ON "page_text_settings_quote_page_ui_workflow_items_locales" USING btree ("_locale","_parent_id");
  CREATE UNIQUE INDEX "page_text_settings_locales_locale_parent_id_unique" ON "page_text_settings_locales" USING btree ("_locale","_parent_id");
  ALTER TABLE "cap_ranges" ADD CONSTRAINT "cap_ranges_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."product_listing_ui"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "boom_dict" ADD CONSTRAINT "boom_dict_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."product_listing_ui"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "app_dict" ADD CONSTRAINT "app_dict_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."product_listing_ui"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "status_dict" ADD CONSTRAINT "status_dict_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."product_listing_ui"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings" DROP COLUMN "application_section_cta_href";
  ALTER TABLE "site_settings" DROP COLUMN "about_section_cta_href";
  ALTER TABLE "site_settings" DROP COLUMN "news_section_cta_href";
  ALTER TABLE "site_settings" DROP COLUMN "final_cta_button_href";
  ALTER TABLE "site_settings" DROP COLUMN "final_cta_image_id";
  ALTER TABLE "site_settings" DROP COLUMN "final_cta_image_path";
  ALTER TABLE "site_settings" DROP COLUMN "product_listing_ui_banner_image_id";
  ALTER TABLE "site_settings" DROP COLUMN "contact_page_ui_hero_image";
  ALTER TABLE "site_settings" DROP COLUMN "quote_page_ui_hero_image";
  ALTER TABLE "site_settings" DROP COLUMN "quote_page_ui_product_link_href";
  ALTER TABLE "site_settings" DROP COLUMN "news_page_ui_hero_image";
  ALTER TABLE "site_settings" DROP COLUMN "cases_page_ui_hero_image";
  ALTER TABLE "site_settings_locales" DROP COLUMN "home_product_section_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "home_product_section_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "home_product_section_find_model_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "home_product_section_details_short_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "home_product_section_view_details_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "home_product_section_back_to_models_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "home_product_section_product_name_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "home_product_section_model_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "home_product_section_model_spec_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "home_product_section_capacity_spec_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "home_product_section_type_spec_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "home_product_section_system_spec_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "home_product_section_system_value";
  ALTER TABLE "site_settings_locales" DROP COLUMN "trust_bar_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "trust_bar_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "trust_bar_description";
  ALTER TABLE "site_settings_locales" DROP COLUMN "application_section_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "application_section_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "application_section_cta_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "about_section_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "about_section_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "about_section_text";
  ALTER TABLE "site_settings_locales" DROP COLUMN "about_section_cta_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "news_section_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "news_section_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "news_section_cta_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "final_cta_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "final_cta_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "final_cta_button_label";
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
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_aria_labels_anchor_nav";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_aria_labels_side_nav";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_overview_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_overview_text_template";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_technical_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_catalog_brand_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_catalog_cover_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_catalog_cover_alt_template";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_download_center_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_download_catalog_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_catalog_description_template";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_request_pdf_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_gallery_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_gallery_empty_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_gallery_empty_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_gallery_empty_text";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_gallery_main_image_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_gallery_detail_image_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_gallery_drawing_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_applications_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_review_status_labels_needs_review";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_review_status_labels_in_review";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_review_status_labels_verified_from_source";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_review_status_labels_changes_required";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_customization_labels_available";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_customization_labels_available_on_request";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_spec_labels_model";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_spec_labels_max_lift";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_spec_labels_max_lifting_capacity";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_spec_labels_boom_length";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_spec_labels_max_boom_length";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_spec_labels_rotation";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_spec_labels_boom_type";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_spec_labels_application";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_spec_labels_customization";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_spec_labels_specification_status";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_spec_labels_parameter_review";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_faq_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_quote_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_quote_text";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_contact_engineer_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "contact_page_ui_hero_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "contact_page_ui_hero_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "contact_page_ui_hero_description";
  ALTER TABLE "site_settings_locales" DROP COLUMN "contact_page_ui_card_labels_email";
  ALTER TABLE "site_settings_locales" DROP COLUMN "contact_page_ui_card_labels_phone";
  ALTER TABLE "site_settings_locales" DROP COLUMN "contact_page_ui_card_labels_whatsapp";
  ALTER TABLE "site_settings_locales" DROP COLUMN "contact_page_ui_card_labels_address";
  ALTER TABLE "site_settings_locales" DROP COLUMN "contact_page_ui_whatsapp_value";
  ALTER TABLE "site_settings_locales" DROP COLUMN "contact_page_ui_we_chat_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "contact_page_ui_we_chat_description_template";
  ALTER TABLE "site_settings_locales" DROP COLUMN "contact_page_ui_we_chat_qr_alt";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_page_ui_hero_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_page_ui_hero_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_page_ui_hero_description";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_page_ui_workflow_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_page_ui_workflow_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_page_ui_product_link_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_badge";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_fields_name";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_fields_email";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_fields_phone";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_fields_country";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_fields_product";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_fields_product_not_decided";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_fields_quantity";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_fields_message";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_fields_message_placeholder";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_buttons_save";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_buttons_saving";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_buttons_email";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_buttons_whatsapp";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_messages_success_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_messages_success_text";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_messages_error_fallback";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_inquiry_line_labels_name";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_inquiry_line_labels_email";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_inquiry_line_labels_phone";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_inquiry_line_labels_country";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_inquiry_line_labels_product";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_inquiry_line_labels_quantity";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_inquiry_line_labels_message";
  ALTER TABLE "site_settings_locales" DROP COLUMN "news_page_ui_meta_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "news_page_ui_meta_description";
  ALTER TABLE "site_settings_locales" DROP COLUMN "news_page_ui_hero_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "news_page_ui_hero_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "news_page_ui_hero_description";
  ALTER TABLE "site_settings_locales" DROP COLUMN "news_page_ui_read_featured_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "news_page_ui_request_quote_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "news_page_ui_featured_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "news_page_ui_read_article_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "news_page_ui_read_more_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "news_detail_ui_back_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "news_detail_ui_related_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "news_detail_ui_quote_cta_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "cases_page_ui_meta_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "cases_page_ui_meta_description";
  ALTER TABLE "site_settings_locales" DROP COLUMN "cases_page_ui_hero_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "cases_page_ui_hero_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "cases_page_ui_hero_description";
  ALTER TABLE "site_settings_locales" DROP COLUMN "cases_page_ui_primary_cta_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "cases_page_ui_secondary_cta_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "cases_page_ui_list_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "cases_page_ui_list_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "cases_page_ui_card_type_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "cases_page_ui_view_case_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "case_detail_ui_back_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "case_detail_ui_facts_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "case_detail_ui_discuss_similar_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "about_page_ui_meta_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "about_page_ui_meta_description";
  ALTER TABLE "site_settings_locales" DROP COLUMN "about_page_ui_primary_cta_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "about_page_ui_secondary_cta_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "about_page_ui_strength_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "about_page_ui_strength_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "about_page_ui_timeline_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "content_labels_ui_article_categories_company_news";
  ALTER TABLE "site_settings_locales" DROP COLUMN "content_labels_ui_article_categories_product_news";
  ALTER TABLE "site_settings_locales" DROP COLUMN "content_labels_ui_article_categories_industry_insight";
  ALTER TABLE "site_settings_locales" DROP COLUMN "content_labels_ui_case_facts_region";
  ALTER TABLE "site_settings_locales" DROP COLUMN "content_labels_ui_case_facts_industry";
  ALTER TABLE "site_settings_locales" DROP COLUMN "content_labels_ui_case_facts_priority";
  ALTER TABLE "site_settings_locales" DROP COLUMN "content_labels_ui_case_facts_default_priority";
  ALTER TABLE "site_settings_locales" DROP COLUMN "content_labels_ui_case_facts_default_case_type";
  DROP TYPE "public"."enum_site_settings_application_section_items_icon_name";
  DROP TYPE "public"."enum_site_settings_about_section_stats_icon_name";
  DROP TYPE "public"."enum_site_settings_quote_page_ui_workflow_items_icon_name";`)
}

export async function down({ db, payload, req }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
   CREATE TYPE "public"."enum_site_settings_application_section_items_icon_name" AS ENUM('award', 'building', 'factory', 'globe', 'network', 'package-check', 'ship', 'truck', 'wrench');
  CREATE TYPE "public"."enum_site_settings_about_section_stats_icon_name" AS ENUM('award', 'building', 'factory', 'globe', 'network', 'package-check', 'ship', 'truck', 'wrench');
  CREATE TYPE "public"."enum_site_settings_quote_page_ui_workflow_items_icon_name" AS ENUM('file-text', 'mail-check', 'message-circle');
  CREATE TABLE "site_settings_home_hero_slides" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"image_id" integer NOT NULL
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

  CREATE TABLE "site_settings_quote_page_ui_workflow_items" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"icon_name" "enum_site_settings_quote_page_ui_workflow_items_icon_name" DEFAULT 'file-text'
  );

  CREATE TABLE "site_settings_quote_page_ui_workflow_items_locales" (
	"title" varchar DEFAULT '',
	"text" varchar DEFAULT '',
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "site_settings_rels" (
	"id" serial PRIMARY KEY NOT NULL,
	"order" integer,
	"parent_id" integer NOT NULL,
	"path" varchar NOT NULL,
	"products_id" integer
  );

  ALTER TABLE "home_settings_home_hero_slides" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "home_settings_home_hero_slides_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "home_settings_trust_bar_marks" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "home_settings_trust_bar_marks_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "home_settings_application_section_items" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "home_settings_application_section_items_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "home_settings_about_section_gallery" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "home_settings_about_section_gallery_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "home_settings_about_section_stats" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "home_settings_about_section_stats_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "home_settings_about_section_timeline" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "home_settings_about_section_timeline_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "home_settings" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "home_settings_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "home_settings_rels" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "product_listing_ui_product_listing_ui_banner_paragraphs" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "product_listing_ui_product_listing_ui_banner_paragraphs_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "product_listing_ui" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "product_listing_ui_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "product_detail_ui_product_detail_ui_hotspots" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "product_detail_ui_product_detail_ui_hotspots_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "product_detail_ui_product_detail_ui_default_features" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "product_detail_ui_product_detail_ui_default_features_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "product_detail_ui_product_detail_ui_default_gallery_labels" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "product_detail_ui_product_detail_ui_default_gallery_labels_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "product_detail_ui_product_detail_ui_default_faqs" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "product_detail_ui_product_detail_ui_default_faqs_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "product_detail_ui" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "product_detail_ui_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "page_text_settings_quote_page_ui_workflow_items" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "page_text_settings_quote_page_ui_workflow_items_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "page_text_settings" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "page_text_settings_locales" DISABLE ROW LEVEL SECURITY;
  DROP TABLE "home_settings_home_hero_slides" CASCADE;
  DROP TABLE "home_settings_home_hero_slides_locales" CASCADE;
  DROP TABLE "home_settings_trust_bar_marks" CASCADE;
  DROP TABLE "home_settings_trust_bar_marks_locales" CASCADE;
  DROP TABLE "home_settings_application_section_items" CASCADE;
  DROP TABLE "home_settings_application_section_items_locales" CASCADE;
  DROP TABLE "home_settings_about_section_gallery" CASCADE;
  DROP TABLE "home_settings_about_section_gallery_locales" CASCADE;
  DROP TABLE "home_settings_about_section_stats" CASCADE;
  DROP TABLE "home_settings_about_section_stats_locales" CASCADE;
  DROP TABLE "home_settings_about_section_timeline" CASCADE;
  DROP TABLE "home_settings_about_section_timeline_locales" CASCADE;
  DROP TABLE "home_settings" CASCADE;
  DROP TABLE "home_settings_locales" CASCADE;
  DROP TABLE "home_settings_rels" CASCADE;
  DROP TABLE "product_listing_ui_product_listing_ui_banner_paragraphs" CASCADE;
  DROP TABLE "product_listing_ui_product_listing_ui_banner_paragraphs_locales" CASCADE;
  DROP TABLE "product_listing_ui" CASCADE;
  DROP TABLE "product_listing_ui_locales" CASCADE;
  DROP TABLE "product_detail_ui_product_detail_ui_hotspots" CASCADE;
  DROP TABLE "product_detail_ui_product_detail_ui_hotspots_locales" CASCADE;
  DROP TABLE "product_detail_ui_product_detail_ui_default_features" CASCADE;
  DROP TABLE "product_detail_ui_product_detail_ui_default_features_locales" CASCADE;
  DROP TABLE "product_detail_ui_product_detail_ui_default_gallery_labels" CASCADE;
  DROP TABLE "product_detail_ui_product_detail_ui_default_gallery_labels_locales" CASCADE;
  DROP TABLE "product_detail_ui_product_detail_ui_default_faqs" CASCADE;
  DROP TABLE "product_detail_ui_product_detail_ui_default_faqs_locales" CASCADE;
  DROP TABLE "product_detail_ui" CASCADE;
  DROP TABLE "product_detail_ui_locales" CASCADE;
  DROP TABLE "page_text_settings_quote_page_ui_workflow_items" CASCADE;
  DROP TABLE "page_text_settings_quote_page_ui_workflow_items_locales" CASCADE;
  DROP TABLE "page_text_settings" CASCADE;
  DROP TABLE "page_text_settings_locales" CASCADE;
  ALTER TABLE "cap_ranges" DROP CONSTRAINT "cap_ranges_parent_id_fk";

  ALTER TABLE "boom_dict" DROP CONSTRAINT "boom_dict_parent_id_fk";

  ALTER TABLE "app_dict" DROP CONSTRAINT "app_dict_parent_id_fk";

  ALTER TABLE "status_dict" DROP CONSTRAINT "status_dict_parent_id_fk";

  ALTER TABLE "site_settings" ADD COLUMN "application_section_cta_href" varchar DEFAULT '#application';
  ALTER TABLE "site_settings" ADD COLUMN "about_section_cta_href" varchar DEFAULT '/about';
  ALTER TABLE "site_settings" ADD COLUMN "news_section_cta_href" varchar DEFAULT '/news';
  ALTER TABLE "site_settings" ADD COLUMN "final_cta_button_href" varchar DEFAULT '/quote';
  ALTER TABLE "site_settings" ADD COLUMN "final_cta_image_id" integer;
  ALTER TABLE "site_settings" ADD COLUMN "final_cta_image_path" varchar DEFAULT '/images/cta-lift-photo.png';
  ALTER TABLE "site_settings" ADD COLUMN "product_listing_ui_banner_image_id" integer;
  ALTER TABLE "site_settings" ADD COLUMN "contact_page_ui_hero_image" varchar DEFAULT '/images/global-network-blue.jpg';
  ALTER TABLE "site_settings" ADD COLUMN "quote_page_ui_hero_image" varchar DEFAULT '/images/cta-lift-photo.png';
  ALTER TABLE "site_settings" ADD COLUMN "quote_page_ui_product_link_href" varchar DEFAULT '/products';
  ALTER TABLE "site_settings" ADD COLUMN "news_page_ui_hero_image" varchar DEFAULT '/images/factory-workshop.jpg';
  ALTER TABLE "site_settings" ADD COLUMN "cases_page_ui_hero_image" varchar DEFAULT '/images/marine-scene.jpg';
  ALTER TABLE "site_settings_locales" ADD COLUMN "home_product_section_label" varchar DEFAULT 'Main Products';
  ALTER TABLE "site_settings_locales" ADD COLUMN "home_product_section_title" varchar DEFAULT 'BOB-LIFT Crane Solutions';
  ALTER TABLE "site_settings_locales" ADD COLUMN "home_product_section_find_model_title" varchar DEFAULT 'Find Your Model';
  ALTER TABLE "site_settings_locales" ADD COLUMN "home_product_section_details_short_label" varchar DEFAULT 'Details';
  ALTER TABLE "site_settings_locales" ADD COLUMN "home_product_section_view_details_label" varchar DEFAULT 'View Details';
  ALTER TABLE "site_settings_locales" ADD COLUMN "home_product_section_back_to_models_label" varchar DEFAULT 'Back to Models';
  ALTER TABLE "site_settings_locales" ADD COLUMN "home_product_section_product_name_label" varchar DEFAULT 'Product Name';
  ALTER TABLE "site_settings_locales" ADD COLUMN "home_product_section_model_label" varchar DEFAULT 'Model';
  ALTER TABLE "site_settings_locales" ADD COLUMN "home_product_section_model_spec_label" varchar DEFAULT 'Model';
  ALTER TABLE "site_settings_locales" ADD COLUMN "home_product_section_capacity_spec_label" varchar DEFAULT 'Capacity';
  ALTER TABLE "site_settings_locales" ADD COLUMN "home_product_section_type_spec_label" varchar DEFAULT 'Type';
  ALTER TABLE "site_settings_locales" ADD COLUMN "home_product_section_system_spec_label" varchar DEFAULT 'System';
  ALTER TABLE "site_settings_locales" ADD COLUMN "home_product_section_system_value" varchar DEFAULT 'Hydraulic';
  ALTER TABLE "site_settings_locales" ADD COLUMN "trust_bar_label" varchar DEFAULT 'Certified';
  ALTER TABLE "site_settings_locales" ADD COLUMN "trust_bar_title" varchar DEFAULT 'Quality Assurance';
  ALTER TABLE "site_settings_locales" ADD COLUMN "trust_bar_description" varchar DEFAULT 'Export-ready documentation.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "application_section_label" varchar DEFAULT 'Solutions for Every Industry';
  ALTER TABLE "site_settings_locales" ADD COLUMN "application_section_title" varchar DEFAULT 'Lifting Solutions That Build Stronger Futures.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "application_section_cta_label" varchar DEFAULT 'View All Applications';
  ALTER TABLE "site_settings_locales" ADD COLUMN "about_section_label" varchar DEFAULT 'About BOB-LIFT';
  ALTER TABLE "site_settings_locales" ADD COLUMN "about_section_title" varchar DEFAULT 'Engineering Excellence. Powered by People.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "about_section_text" varchar;
  ALTER TABLE "site_settings_locales" ADD COLUMN "about_section_cta_label" varchar DEFAULT 'More About Us';
  ALTER TABLE "site_settings_locales" ADD COLUMN "news_section_label" varchar DEFAULT 'News Center';
  ALTER TABLE "site_settings_locales" ADD COLUMN "news_section_title" varchar DEFAULT 'Latest Insights from BOB-LIFT.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "news_section_cta_label" varchar DEFAULT 'View All News';
  ALTER TABLE "site_settings_locales" ADD COLUMN "final_cta_label" varchar DEFAULT 'Project Consultation';
  ALTER TABLE "site_settings_locales" ADD COLUMN "final_cta_title" varchar DEFAULT 'Need a Lifting Solution?';
  ALTER TABLE "site_settings_locales" ADD COLUMN "final_cta_button_label" varchar DEFAULT 'Request a Quote';
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
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_aria_labels_anchor_nav" varchar DEFAULT 'Product detail sections';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_aria_labels_side_nav" varchar DEFAULT 'Product page navigation';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_overview_title" varchar DEFAULT 'Built for reliable truck-mounted lifting work.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_overview_text_template" varchar DEFAULT '{description} The configuration focuses on smooth operation, stable control and practical export use for contractors, fleet buyers and project teams.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_technical_title" varchar DEFAULT 'Technical Specifications';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_catalog_brand_label" varchar DEFAULT 'BOB-LIFT';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_catalog_cover_title" varchar DEFAULT 'Product Catalog';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_catalog_cover_alt_template" varchar DEFAULT '{product} catalog cover';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_download_center_label" varchar DEFAULT 'Download Center';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_download_catalog_title" varchar DEFAULT 'Download Product Catalog';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_catalog_description_template" varchar DEFAULT '{catalogTitle} includes core specifications, working range information, and configuration notes for project evaluation.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_request_pdf_label" varchar DEFAULT 'Request PDF by Email';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_gallery_title" varchar DEFAULT 'Product Detail Gallery';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_gallery_empty_label" varchar DEFAULT 'Product Detail View';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_gallery_empty_title" varchar DEFAULT 'Engineering visuals for product evaluation.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_gallery_empty_text" varchar DEFAULT 'Present boom structure, installation layout, hydraulic system, operating range, and chassis mounting details in one focused section.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_gallery_main_image_label" varchar DEFAULT 'Main product view';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_gallery_detail_image_label" varchar DEFAULT 'Product detail image';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_gallery_drawing_label" varchar DEFAULT 'Specification drawing';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_applications_title" varchar DEFAULT 'Applications';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_review_status_labels_needs_review" varchar DEFAULT 'Needs review';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_review_status_labels_in_review" varchar DEFAULT 'In review';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_review_status_labels_verified_from_source" varchar DEFAULT 'Verified from source';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_review_status_labels_changes_required" varchar DEFAULT 'Changes required';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_customization_labels_available" varchar DEFAULT 'Available';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_customization_labels_available_on_request" varchar DEFAULT 'Available on request';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_spec_labels_model" varchar DEFAULT 'Model';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_spec_labels_max_lift" varchar DEFAULT 'Max Lift';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_spec_labels_max_lifting_capacity" varchar DEFAULT 'Max Lifting Capacity';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_spec_labels_boom_length" varchar DEFAULT 'Boom Length';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_spec_labels_max_boom_length" varchar DEFAULT 'Max Boom Length';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_spec_labels_rotation" varchar DEFAULT 'Rotation';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_spec_labels_boom_type" varchar DEFAULT 'Boom Type';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_spec_labels_application" varchar DEFAULT 'Application';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_spec_labels_customization" varchar DEFAULT 'Customization';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_spec_labels_specification_status" varchar DEFAULT 'Specification Status';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_spec_labels_parameter_review" varchar DEFAULT 'Parameter Review';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_faq_title" varchar DEFAULT 'FAQ';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_quote_title" varchar DEFAULT 'Need a truck crane matched to your chassis and lifting task?';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_quote_text" varchar DEFAULT 'Send your working radius, load requirement, destination country, and truck chassis information. BOB-LIFT will help match a practical configuration.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_contact_engineer_label" varchar DEFAULT 'Contact Engineer';
  ALTER TABLE "site_settings_locales" ADD COLUMN "contact_page_ui_hero_label" varchar DEFAULT 'Contact Us';
  ALTER TABLE "site_settings_locales" ADD COLUMN "contact_page_ui_hero_title" varchar DEFAULT 'Talk to BOB-LIFT before choosing a crane.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "contact_page_ui_hero_description" varchar DEFAULT 'For model selection, truck chassis matching, marine installation and export questions, use the direct contact channels or create a structured inquiry draft.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "contact_page_ui_card_labels_email" varchar DEFAULT 'Email';
  ALTER TABLE "site_settings_locales" ADD COLUMN "contact_page_ui_card_labels_phone" varchar DEFAULT 'Phone';
  ALTER TABLE "site_settings_locales" ADD COLUMN "contact_page_ui_card_labels_whatsapp" varchar DEFAULT 'WhatsApp';
  ALTER TABLE "site_settings_locales" ADD COLUMN "contact_page_ui_card_labels_address" varchar DEFAULT 'Address';
  ALTER TABLE "site_settings_locales" ADD COLUMN "contact_page_ui_whatsapp_value" varchar DEFAULT 'Start chat';
  ALTER TABLE "site_settings_locales" ADD COLUMN "contact_page_ui_we_chat_title" varchar DEFAULT 'WeChat';
  ALTER TABLE "site_settings_locales" ADD COLUMN "contact_page_ui_we_chat_description_template" varchar DEFAULT 'Scan the QR code or search ID {wechatId}. The QR block is kept as a compact card so it does not dominate mobile screens.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "contact_page_ui_we_chat_qr_alt" varchar DEFAULT 'BOB-LIFT WeChat QR code';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_page_ui_hero_label" varchar DEFAULT 'Request Quote';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_page_ui_hero_title" varchar DEFAULT 'Request a tailored BOB-LIFT quotation.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_page_ui_hero_description" varchar DEFAULT 'Share your product interest, destination country, contact details and working conditions. The team can then match a practical lifting solution for your project.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_page_ui_workflow_label" varchar DEFAULT 'Quote Workflow';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_page_ui_workflow_title" varchar DEFAULT 'Fill once, send through your preferred channel.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_page_ui_product_link_label" varchar DEFAULT 'Need to choose a model first?';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_label" varchar DEFAULT 'Request Quote';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_title" varchar DEFAULT 'Tell us your lifting requirement.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_badge" varchar DEFAULT '24h reply';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_fields_name" varchar DEFAULT 'Full Name';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_fields_email" varchar DEFAULT 'Email';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_fields_phone" varchar DEFAULT 'Phone / WhatsApp';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_fields_country" varchar DEFAULT 'Country / Region';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_fields_product" varchar DEFAULT 'Product Interest';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_fields_product_not_decided" varchar DEFAULT 'Not decided yet';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_fields_quantity" varchar DEFAULT 'Expected Quantity';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_fields_message" varchar DEFAULT 'Project Details';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_fields_message_placeholder" varchar DEFAULT 'Load capacity, working radius, truck chassis, destination port, deadline...';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_buttons_save" varchar DEFAULT 'Save Inquiry Draft';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_buttons_saving" varchar DEFAULT 'Saving Inquiry...';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_buttons_email" varchar DEFAULT 'Send Email';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_buttons_whatsapp" varchar DEFAULT 'WhatsApp';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_messages_success_title" varchar DEFAULT 'Inquiry saved in the admin.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_messages_success_text" varchar DEFAULT 'Use Email or WhatsApp if you also want to send the draft through your preferred app.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_messages_error_fallback" varchar DEFAULT 'Inquiry could not be saved.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_inquiry_line_labels_name" varchar DEFAULT 'Name';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_inquiry_line_labels_email" varchar DEFAULT 'Email';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_inquiry_line_labels_phone" varchar DEFAULT 'Phone / WhatsApp';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_inquiry_line_labels_country" varchar DEFAULT 'Country';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_inquiry_line_labels_product" varchar DEFAULT 'Product';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_inquiry_line_labels_quantity" varchar DEFAULT 'Quantity';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_inquiry_line_labels_message" varchar DEFAULT 'Project details';
  ALTER TABLE "site_settings_locales" ADD COLUMN "news_page_ui_meta_title" varchar DEFAULT 'News | BOB-LIFT';
  ALTER TABLE "site_settings_locales" ADD COLUMN "news_page_ui_meta_description" varchar DEFAULT 'BOB-LIFT product news, company news and lifting solution insights.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "news_page_ui_hero_label" varchar DEFAULT 'News Center';
  ALTER TABLE "site_settings_locales" ADD COLUMN "news_page_ui_hero_title" varchar DEFAULT 'Updates that help buyers choose lifting equipment faster.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "news_page_ui_hero_description" varchar DEFAULT 'Read BOB-LIFT product updates, factory stories and marine solution articles for practical purchasing and equipment-selection reference.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "news_page_ui_read_featured_label" varchar DEFAULT 'Read Featured';
  ALTER TABLE "site_settings_locales" ADD COLUMN "news_page_ui_request_quote_label" varchar DEFAULT 'Request Quote';
  ALTER TABLE "site_settings_locales" ADD COLUMN "news_page_ui_featured_label" varchar DEFAULT 'Featured Article';
  ALTER TABLE "site_settings_locales" ADD COLUMN "news_page_ui_read_article_label" varchar DEFAULT 'Read Article';
  ALTER TABLE "site_settings_locales" ADD COLUMN "news_page_ui_read_more_label" varchar DEFAULT 'Read More';
  ALTER TABLE "site_settings_locales" ADD COLUMN "news_detail_ui_back_label" varchar DEFAULT 'Back to News';
  ALTER TABLE "site_settings_locales" ADD COLUMN "news_detail_ui_related_label" varchar DEFAULT 'Related';
  ALTER TABLE "site_settings_locales" ADD COLUMN "news_detail_ui_quote_cta_label" varchar DEFAULT 'Ask for solution';
  ALTER TABLE "site_settings_locales" ADD COLUMN "cases_page_ui_meta_title" varchar DEFAULT 'Case Center | BOB-LIFT';
  ALTER TABLE "site_settings_locales" ADD COLUMN "cases_page_ui_meta_description" varchar DEFAULT 'BOB-LIFT customer cases for marine operations, material handling and aerial work platform applications.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "cases_page_ui_hero_label" varchar DEFAULT 'Case Center';
  ALTER TABLE "site_settings_locales" ADD COLUMN "cases_page_ui_hero_title" varchar DEFAULT 'Project stories with real working conditions.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "cases_page_ui_hero_description" varchar DEFAULT 'Browse customer cases across marine operations, material handling and aerial work applications, then discuss a similar lifting solution with BOB-LIFT.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "cases_page_ui_primary_cta_label" varchar DEFAULT 'Plan My Project';
  ALTER TABLE "site_settings_locales" ADD COLUMN "cases_page_ui_secondary_cta_label" varchar DEFAULT 'View Cases';
  ALTER TABLE "site_settings_locales" ADD COLUMN "cases_page_ui_list_label" varchar DEFAULT 'Field Evidence';
  ALTER TABLE "site_settings_locales" ADD COLUMN "cases_page_ui_list_title" varchar DEFAULT 'From lifting challenge to equipment match.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "cases_page_ui_card_type_label" varchar DEFAULT 'Customer Case';
  ALTER TABLE "site_settings_locales" ADD COLUMN "cases_page_ui_view_case_label" varchar DEFAULT 'View Case';
  ALTER TABLE "site_settings_locales" ADD COLUMN "case_detail_ui_back_label" varchar DEFAULT 'Back to Case Center';
  ALTER TABLE "site_settings_locales" ADD COLUMN "case_detail_ui_facts_title" varchar DEFAULT 'Case Facts';
  ALTER TABLE "site_settings_locales" ADD COLUMN "case_detail_ui_discuss_similar_label" varchar DEFAULT 'Discuss Similar Project';
  ALTER TABLE "site_settings_locales" ADD COLUMN "about_page_ui_meta_title" varchar DEFAULT 'About BOB-LIFT | BOB-LIFT';
  ALTER TABLE "site_settings_locales" ADD COLUMN "about_page_ui_meta_description" varchar DEFAULT 'Learn about BOB-LIFT manufacturing, export capability and engineering experience.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "about_page_ui_primary_cta_label" varchar DEFAULT 'Contact Team';
  ALTER TABLE "site_settings_locales" ADD COLUMN "about_page_ui_secondary_cta_label" varchar DEFAULT 'View Products';
  ALTER TABLE "site_settings_locales" ADD COLUMN "about_page_ui_strength_label" varchar DEFAULT 'Company Strength';
  ALTER TABLE "site_settings_locales" ADD COLUMN "about_page_ui_strength_title" varchar DEFAULT 'Manufacturing, export and service in one team.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "about_page_ui_timeline_label" varchar DEFAULT 'Development Timeline';
  ALTER TABLE "site_settings_locales" ADD COLUMN "content_labels_ui_article_categories_company_news" varchar DEFAULT 'Company News';
  ALTER TABLE "site_settings_locales" ADD COLUMN "content_labels_ui_article_categories_product_news" varchar DEFAULT 'Product News';
  ALTER TABLE "site_settings_locales" ADD COLUMN "content_labels_ui_article_categories_industry_insight" varchar DEFAULT 'Industry Insight';
  ALTER TABLE "site_settings_locales" ADD COLUMN "content_labels_ui_case_facts_region" varchar DEFAULT 'Region';
  ALTER TABLE "site_settings_locales" ADD COLUMN "content_labels_ui_case_facts_industry" varchar DEFAULT 'Industry';
  ALTER TABLE "site_settings_locales" ADD COLUMN "content_labels_ui_case_facts_priority" varchar DEFAULT 'Priority';
  ALTER TABLE "site_settings_locales" ADD COLUMN "content_labels_ui_case_facts_default_priority" varchar DEFAULT 'Equipment Match';
  ALTER TABLE "site_settings_locales" ADD COLUMN "content_labels_ui_case_facts_default_case_type" varchar DEFAULT 'Customer Case';
  ALTER TABLE "site_settings_home_hero_slides" ADD CONSTRAINT "site_settings_home_hero_slides_image_id_media_id_fk" FOREIGN KEY ("image_id") REFERENCES "public"."media"("id") ON DELETE set null ON UPDATE no action;
  ALTER TABLE "site_settings_home_hero_slides" ADD CONSTRAINT "site_settings_home_hero_slides_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings"("id") ON DELETE cascade ON UPDATE no action;
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
  ALTER TABLE "site_settings_quote_page_ui_workflow_items" ADD CONSTRAINT "site_settings_quote_page_ui_workflow_items_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_quote_page_ui_workflow_items_locales" ADD CONSTRAINT "site_settings_quote_page_ui_workflow_items_locales_parent_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings_quote_page_ui_workflow_items"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_rels" ADD CONSTRAINT "site_settings_rels_parent_fk" FOREIGN KEY ("parent_id") REFERENCES "public"."site_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_rels" ADD CONSTRAINT "site_settings_rels_products_fk" FOREIGN KEY ("products_id") REFERENCES "public"."products"("id") ON DELETE cascade ON UPDATE no action;
  CREATE INDEX "site_settings_home_hero_slides_order_idx" ON "site_settings_home_hero_slides" USING btree ("_order");
  CREATE INDEX "site_settings_home_hero_slides_parent_id_idx" ON "site_settings_home_hero_slides" USING btree ("_parent_id");
  CREATE INDEX "site_settings_home_hero_slides_image_idx" ON "site_settings_home_hero_slides" USING btree ("image_id");
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
  CREATE INDEX "site_settings_quote_page_ui_workflow_items_order_idx" ON "site_settings_quote_page_ui_workflow_items" USING btree ("_order");
  CREATE INDEX "site_settings_quote_page_ui_workflow_items_parent_id_idx" ON "site_settings_quote_page_ui_workflow_items" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "site_settings_quote_page_ui_workflow_items_locales_locale_pa" ON "site_settings_quote_page_ui_workflow_items_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "site_settings_rels_order_idx" ON "site_settings_rels" USING btree ("order");
  CREATE INDEX "site_settings_rels_parent_idx" ON "site_settings_rels" USING btree ("parent_id");
  CREATE INDEX "site_settings_rels_path_idx" ON "site_settings_rels" USING btree ("path");
  CREATE INDEX "site_settings_rels_products_id_idx" ON "site_settings_rels" USING btree ("products_id");
  ALTER TABLE "site_settings" ADD CONSTRAINT "site_settings_final_cta_image_id_media_id_fk" FOREIGN KEY ("final_cta_image_id") REFERENCES "public"."media"("id") ON DELETE set null ON UPDATE no action;
  ALTER TABLE "site_settings" ADD CONSTRAINT "site_settings_product_listing_ui_banner_image_id_media_id_fk" FOREIGN KEY ("product_listing_ui_banner_image_id") REFERENCES "public"."media"("id") ON DELETE set null ON UPDATE no action;
  ALTER TABLE "cap_ranges" ADD CONSTRAINT "cap_ranges_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "boom_dict" ADD CONSTRAINT "boom_dict_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "app_dict" ADD CONSTRAINT "app_dict_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "status_dict" ADD CONSTRAINT "status_dict_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings"("id") ON DELETE cascade ON UPDATE no action;
  CREATE INDEX "site_settings_final_cta_final_cta_image_idx" ON "site_settings" USING btree ("final_cta_image_id");
  CREATE INDEX "site_settings_product_listing_ui_product_listing_ui_bann_idx" ON "site_settings" USING btree ("product_listing_ui_banner_image_id");
  DROP TYPE "public"."enum_home_settings_application_section_items_icon_name";
  DROP TYPE "public"."enum_home_settings_about_section_stats_icon_name";
  DROP TYPE "public"."enum_page_text_settings_quote_page_ui_workflow_items_icon_name";`)
}
