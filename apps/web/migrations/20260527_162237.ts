import { MigrateUpArgs, MigrateDownArgs, sql } from '@payloadcms/db-postgres'

export async function up({ db, payload, req }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
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
  ALTER TABLE "site_settings_locales" ADD COLUMN "home_product_section_system_value" varchar DEFAULT 'Hydraulic';`)
}

export async function down({ db, payload, req }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
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
  ALTER TABLE "site_settings_locales" DROP COLUMN "home_product_section_system_value";`)
}
