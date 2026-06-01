import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    CREATE TYPE "public"."enum_product_listing_ui_product_listing_ui_default_sort_option" AS ENUM('recommended', 'capacityAsc', 'capacityDesc', 'newest', 'popular');
    CREATE TYPE "public"."enum_product_listing_ui_product_listing_ui_default_view_mode" AS ENUM('grid', 'list');

    ALTER TABLE "product_listing_ui" ADD COLUMN "product_listing_ui_default_sort_option" "enum_product_listing_ui_product_listing_ui_default_sort_option" DEFAULT 'recommended';
    ALTER TABLE "product_listing_ui" ADD COLUMN "product_listing_ui_default_view_mode" "enum_product_listing_ui_product_listing_ui_default_view_mode" DEFAULT 'grid';
    ALTER TABLE "product_listing_ui" ADD COLUMN "product_listing_ui_show_filters" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN "product_listing_ui_show_sort_dropdown" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN "product_listing_ui_show_view_mode_toggle" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN "product_listing_ui_show_favorite_button" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN "product_listing_ui_show_quick_view_button" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN "product_listing_ui_show_pagination" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN "product_listing_ui_filter_visibility_categories" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN "product_listing_ui_filter_visibility_capacities" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN "product_listing_ui_filter_visibility_boom_types" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN "product_listing_ui_filter_visibility_applications" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN "product_listing_ui_filter_visibility_availability" boolean DEFAULT true;

    ALTER TABLE "product_detail_ui" ADD COLUMN "product_detail_ui_section_visibility_anchor_nav" boolean DEFAULT true;
    ALTER TABLE "product_detail_ui" ADD COLUMN "product_detail_ui_section_visibility_side_nav" boolean DEFAULT true;
    ALTER TABLE "product_detail_ui" ADD COLUMN "product_detail_ui_section_visibility_overview" boolean DEFAULT true;
    ALTER TABLE "product_detail_ui" ADD COLUMN "product_detail_ui_section_visibility_technical" boolean DEFAULT true;
    ALTER TABLE "product_detail_ui" ADD COLUMN "product_detail_ui_section_visibility_gallery" boolean DEFAULT true;
    ALTER TABLE "product_detail_ui" ADD COLUMN "product_detail_ui_section_visibility_catalog" boolean DEFAULT true;
    ALTER TABLE "product_detail_ui" ADD COLUMN "product_detail_ui_section_visibility_applications" boolean DEFAULT true;
    ALTER TABLE "product_detail_ui" ADD COLUMN "product_detail_ui_section_visibility_faq" boolean DEFAULT true;
    ALTER TABLE "product_detail_ui" ADD COLUMN "product_detail_ui_section_visibility_quote" boolean DEFAULT true;

    ALTER TABLE "home_settings" ADD COLUMN "home_section_visibility_hero" boolean DEFAULT true;
    ALTER TABLE "home_settings" ADD COLUMN "home_section_visibility_trust_bar" boolean DEFAULT true;
    ALTER TABLE "home_settings" ADD COLUMN "home_section_visibility_product_section" boolean DEFAULT true;
    ALTER TABLE "home_settings" ADD COLUMN "home_section_visibility_application_section" boolean DEFAULT true;
    ALTER TABLE "home_settings" ADD COLUMN "home_section_visibility_about_section" boolean DEFAULT true;
    ALTER TABLE "home_settings" ADD COLUMN "home_section_visibility_news_section" boolean DEFAULT true;
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "home_settings" DROP COLUMN "home_section_visibility_news_section";
    ALTER TABLE "home_settings" DROP COLUMN "home_section_visibility_about_section";
    ALTER TABLE "home_settings" DROP COLUMN "home_section_visibility_application_section";
    ALTER TABLE "home_settings" DROP COLUMN "home_section_visibility_product_section";
    ALTER TABLE "home_settings" DROP COLUMN "home_section_visibility_trust_bar";
    ALTER TABLE "home_settings" DROP COLUMN "home_section_visibility_hero";

    ALTER TABLE "product_detail_ui" DROP COLUMN "product_detail_ui_section_visibility_quote";
    ALTER TABLE "product_detail_ui" DROP COLUMN "product_detail_ui_section_visibility_faq";
    ALTER TABLE "product_detail_ui" DROP COLUMN "product_detail_ui_section_visibility_applications";
    ALTER TABLE "product_detail_ui" DROP COLUMN "product_detail_ui_section_visibility_catalog";
    ALTER TABLE "product_detail_ui" DROP COLUMN "product_detail_ui_section_visibility_gallery";
    ALTER TABLE "product_detail_ui" DROP COLUMN "product_detail_ui_section_visibility_technical";
    ALTER TABLE "product_detail_ui" DROP COLUMN "product_detail_ui_section_visibility_overview";
    ALTER TABLE "product_detail_ui" DROP COLUMN "product_detail_ui_section_visibility_side_nav";
    ALTER TABLE "product_detail_ui" DROP COLUMN "product_detail_ui_section_visibility_anchor_nav";

    ALTER TABLE "product_listing_ui" DROP COLUMN "product_listing_ui_filter_visibility_availability";
    ALTER TABLE "product_listing_ui" DROP COLUMN "product_listing_ui_filter_visibility_applications";
    ALTER TABLE "product_listing_ui" DROP COLUMN "product_listing_ui_filter_visibility_boom_types";
    ALTER TABLE "product_listing_ui" DROP COLUMN "product_listing_ui_filter_visibility_capacities";
    ALTER TABLE "product_listing_ui" DROP COLUMN "product_listing_ui_filter_visibility_categories";
    ALTER TABLE "product_listing_ui" DROP COLUMN "product_listing_ui_show_pagination";
    ALTER TABLE "product_listing_ui" DROP COLUMN "product_listing_ui_show_quick_view_button";
    ALTER TABLE "product_listing_ui" DROP COLUMN "product_listing_ui_show_favorite_button";
    ALTER TABLE "product_listing_ui" DROP COLUMN "product_listing_ui_show_view_mode_toggle";
    ALTER TABLE "product_listing_ui" DROP COLUMN "product_listing_ui_show_sort_dropdown";
    ALTER TABLE "product_listing_ui" DROP COLUMN "product_listing_ui_show_filters";
    ALTER TABLE "product_listing_ui" DROP COLUMN "product_listing_ui_default_view_mode";
    ALTER TABLE "product_listing_ui" DROP COLUMN "product_listing_ui_default_sort_option";

    DROP TYPE "public"."enum_product_listing_ui_product_listing_ui_default_view_mode";
    DROP TYPE "public"."enum_product_listing_ui_product_listing_ui_default_sort_option";
  `);
}
