import { MigrateUpArgs, MigrateDownArgs, sql } from '@payloadcms/db-postgres'

export async function up({ db, payload, req }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
   ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_aria_labels_anchor_nav" varchar DEFAULT 'Product detail sections';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_aria_labels_side_nav" varchar DEFAULT 'Product page navigation';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_catalog_cover_alt_template" varchar DEFAULT '{product} catalog cover';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_gallery_main_image_label" varchar DEFAULT 'Main product view';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_gallery_detail_image_label" varchar DEFAULT 'Product detail image';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_gallery_drawing_label" varchar DEFAULT 'Specification drawing';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_review_status_labels_needs_review" varchar DEFAULT 'Needs review';
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_review_status_labels_verified_from_source" varchar DEFAULT 'Verified from source';
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
  ALTER TABLE "site_settings_locales" ADD COLUMN "product_detail_ui_spec_labels_parameter_review" varchar DEFAULT 'Parameter Review';`)
}

export async function down({ db, payload, req }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
   ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_aria_labels_anchor_nav";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_aria_labels_side_nav";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_catalog_cover_alt_template";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_gallery_main_image_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_gallery_detail_image_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_gallery_drawing_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_review_status_labels_needs_review";
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_review_status_labels_verified_from_source";
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
  ALTER TABLE "site_settings_locales" DROP COLUMN "product_detail_ui_spec_labels_parameter_review";`)
}
