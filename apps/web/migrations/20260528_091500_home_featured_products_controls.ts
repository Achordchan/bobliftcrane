import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "home_settings" ADD COLUMN "home_section_visibility_featured_products" boolean DEFAULT true;
    ALTER TABLE "home_settings" ADD COLUMN "home_product_section_featured_cta_href" varchar DEFAULT '/products';

    ALTER TABLE "home_settings_locales" ADD COLUMN "home_product_section_featured_label" varchar DEFAULT 'Featured Products';
    ALTER TABLE "home_settings_locales" ADD COLUMN "home_product_section_featured_title" varchar DEFAULT 'High Performance Cranes. Built for Extreme Demands.';
    ALTER TABLE "home_settings_locales" ADD COLUMN "home_product_section_featured_cta_label" varchar DEFAULT 'View All Products';
    ALTER TABLE "home_settings_locales" ADD COLUMN "home_product_section_featured_capacity_label" varchar DEFAULT 'Capacity';
    ALTER TABLE "home_settings_locales" ADD COLUMN "home_product_section_featured_type_label" varchar DEFAULT 'Type';
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "home_settings_locales" DROP COLUMN "home_product_section_featured_type_label";
    ALTER TABLE "home_settings_locales" DROP COLUMN "home_product_section_featured_capacity_label";
    ALTER TABLE "home_settings_locales" DROP COLUMN "home_product_section_featured_cta_label";
    ALTER TABLE "home_settings_locales" DROP COLUMN "home_product_section_featured_title";
    ALTER TABLE "home_settings_locales" DROP COLUMN "home_product_section_featured_label";

    ALTER TABLE "home_settings" DROP COLUMN "home_product_section_featured_cta_href";
    ALTER TABLE "home_settings" DROP COLUMN "home_section_visibility_featured_products";
  `);
}
