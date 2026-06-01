import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "home_settings" ADD COLUMN IF NOT EXISTS "home_hero_ui_autoplay" boolean DEFAULT true;
    ALTER TABLE "home_settings" ADD COLUMN IF NOT EXISTS "home_hero_ui_autoplay_interval_ms" numeric DEFAULT 5600;
    ALTER TABLE "home_settings" ADD COLUMN IF NOT EXISTS "home_hero_ui_show_controls" boolean DEFAULT true;
    ALTER TABLE "home_settings" ADD COLUMN IF NOT EXISTS "home_hero_ui_show_indicators" boolean DEFAULT true;
    ALTER TABLE "home_settings" ADD COLUMN IF NOT EXISTS "home_hero_ui_show_overlay" boolean DEFAULT true;
    ALTER TABLE "home_settings" ADD COLUMN IF NOT EXISTS "home_product_section_show_category_scroll_buttons" boolean DEFAULT true;
    ALTER TABLE "home_settings" ADD COLUMN IF NOT EXISTS "home_product_section_show_inline_product_detail" boolean DEFAULT true;
    ALTER TABLE "home_settings" ADD COLUMN IF NOT EXISTS "home_product_section_show_product_specs" boolean DEFAULT true;
    ALTER TABLE "home_settings" ADD COLUMN IF NOT EXISTS "home_product_section_show_product_visual_watermark" boolean DEFAULT true;
    ALTER TABLE "home_settings" ADD COLUMN IF NOT EXISTS "home_product_section_show_featured_product_specs" boolean DEFAULT true;

    ALTER TABLE "product_listing_ui" ADD COLUMN IF NOT EXISTS "product_listing_ui_show_banner" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN IF NOT EXISTS "product_listing_ui_show_result_summary" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN IF NOT EXISTS "product_listing_ui_card_visibility_status_badge" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN IF NOT EXISTS "product_listing_ui_card_visibility_category_label" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN IF NOT EXISTS "product_listing_ui_card_visibility_capacity_label" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN IF NOT EXISTS "product_listing_ui_card_visibility_spec_grid" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN IF NOT EXISTS "product_listing_ui_card_visibility_cta_buttons" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN IF NOT EXISTS "product_listing_ui_list_item_visibility_status_badge" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN IF NOT EXISTS "product_listing_ui_list_item_visibility_category_label" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN IF NOT EXISTS "product_listing_ui_list_item_visibility_capacity_label" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN IF NOT EXISTS "product_listing_ui_list_item_visibility_boom_type_label" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN IF NOT EXISTS "product_listing_ui_list_item_visibility_description" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN IF NOT EXISTS "product_listing_ui_list_item_visibility_spec_grid" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN IF NOT EXISTS "product_listing_ui_list_item_visibility_cta_buttons" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN IF NOT EXISTS "product_listing_ui_quick_view_visibility_status_badge" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN IF NOT EXISTS "product_listing_ui_quick_view_visibility_category_label" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN IF NOT EXISTS "product_listing_ui_quick_view_visibility_description" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN IF NOT EXISTS "product_listing_ui_quick_view_visibility_spec_grid" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN IF NOT EXISTS "product_listing_ui_quick_view_visibility_cta_buttons" boolean DEFAULT true;
    ALTER TABLE "product_listing_ui" ADD COLUMN IF NOT EXISTS "product_listing_ui_quick_view_visibility_favorite_button" boolean DEFAULT true;

    ALTER TABLE "product_detail_ui" ADD COLUMN IF NOT EXISTS "product_detail_ui_hero_visibility_category_label" boolean DEFAULT true;
    ALTER TABLE "product_detail_ui" ADD COLUMN IF NOT EXISTS "product_detail_ui_hero_visibility_description" boolean DEFAULT true;
    ALTER TABLE "product_detail_ui" ADD COLUMN IF NOT EXISTS "product_detail_ui_hero_visibility_hero_specs" boolean DEFAULT true;
    ALTER TABLE "product_detail_ui" ADD COLUMN IF NOT EXISTS "product_detail_ui_hero_visibility_hotspots" boolean DEFAULT true;
    ALTER TABLE "product_detail_ui" ADD COLUMN IF NOT EXISTS "product_detail_ui_hero_visibility_request_quote_button" boolean DEFAULT true;
    ALTER TABLE "product_detail_ui" ADD COLUMN IF NOT EXISTS "product_detail_ui_hero_visibility_catalog_button" boolean DEFAULT true;
    ALTER TABLE "product_detail_ui" ADD COLUMN IF NOT EXISTS "product_detail_ui_hero_visibility_technical_button" boolean DEFAULT true;
    ALTER TABLE "product_detail_ui" ADD COLUMN IF NOT EXISTS "product_detail_ui_catalog_visibility_cover" boolean DEFAULT true;
    ALTER TABLE "product_detail_ui" ADD COLUMN IF NOT EXISTS "product_detail_ui_catalog_visibility_request_pdf_button" boolean DEFAULT true;
    ALTER TABLE "product_detail_ui" ADD COLUMN IF NOT EXISTS "product_detail_ui_catalog_visibility_extra_document_links" boolean DEFAULT true;
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "product_detail_ui" DROP COLUMN IF EXISTS "product_detail_ui_catalog_visibility_extra_document_links";
    ALTER TABLE "product_detail_ui" DROP COLUMN IF EXISTS "product_detail_ui_catalog_visibility_request_pdf_button";
    ALTER TABLE "product_detail_ui" DROP COLUMN IF EXISTS "product_detail_ui_catalog_visibility_cover";
    ALTER TABLE "product_detail_ui" DROP COLUMN IF EXISTS "product_detail_ui_hero_visibility_technical_button";
    ALTER TABLE "product_detail_ui" DROP COLUMN IF EXISTS "product_detail_ui_hero_visibility_catalog_button";
    ALTER TABLE "product_detail_ui" DROP COLUMN IF EXISTS "product_detail_ui_hero_visibility_request_quote_button";
    ALTER TABLE "product_detail_ui" DROP COLUMN IF EXISTS "product_detail_ui_hero_visibility_hotspots";
    ALTER TABLE "product_detail_ui" DROP COLUMN IF EXISTS "product_detail_ui_hero_visibility_hero_specs";
    ALTER TABLE "product_detail_ui" DROP COLUMN IF EXISTS "product_detail_ui_hero_visibility_description";
    ALTER TABLE "product_detail_ui" DROP COLUMN IF EXISTS "product_detail_ui_hero_visibility_category_label";

    ALTER TABLE "product_listing_ui" DROP COLUMN IF EXISTS "product_listing_ui_quick_view_visibility_favorite_button";
    ALTER TABLE "product_listing_ui" DROP COLUMN IF EXISTS "product_listing_ui_quick_view_visibility_cta_buttons";
    ALTER TABLE "product_listing_ui" DROP COLUMN IF EXISTS "product_listing_ui_quick_view_visibility_spec_grid";
    ALTER TABLE "product_listing_ui" DROP COLUMN IF EXISTS "product_listing_ui_quick_view_visibility_description";
    ALTER TABLE "product_listing_ui" DROP COLUMN IF EXISTS "product_listing_ui_quick_view_visibility_category_label";
    ALTER TABLE "product_listing_ui" DROP COLUMN IF EXISTS "product_listing_ui_quick_view_visibility_status_badge";
    ALTER TABLE "product_listing_ui" DROP COLUMN IF EXISTS "product_listing_ui_list_item_visibility_cta_buttons";
    ALTER TABLE "product_listing_ui" DROP COLUMN IF EXISTS "product_listing_ui_list_item_visibility_spec_grid";
    ALTER TABLE "product_listing_ui" DROP COLUMN IF EXISTS "product_listing_ui_list_item_visibility_description";
    ALTER TABLE "product_listing_ui" DROP COLUMN IF EXISTS "product_listing_ui_list_item_visibility_boom_type_label";
    ALTER TABLE "product_listing_ui" DROP COLUMN IF EXISTS "product_listing_ui_list_item_visibility_capacity_label";
    ALTER TABLE "product_listing_ui" DROP COLUMN IF EXISTS "product_listing_ui_list_item_visibility_category_label";
    ALTER TABLE "product_listing_ui" DROP COLUMN IF EXISTS "product_listing_ui_list_item_visibility_status_badge";
    ALTER TABLE "product_listing_ui" DROP COLUMN IF EXISTS "product_listing_ui_card_visibility_cta_buttons";
    ALTER TABLE "product_listing_ui" DROP COLUMN IF EXISTS "product_listing_ui_card_visibility_spec_grid";
    ALTER TABLE "product_listing_ui" DROP COLUMN IF EXISTS "product_listing_ui_card_visibility_capacity_label";
    ALTER TABLE "product_listing_ui" DROP COLUMN IF EXISTS "product_listing_ui_card_visibility_category_label";
    ALTER TABLE "product_listing_ui" DROP COLUMN IF EXISTS "product_listing_ui_card_visibility_status_badge";
    ALTER TABLE "product_listing_ui" DROP COLUMN IF EXISTS "product_listing_ui_show_result_summary";
    ALTER TABLE "product_listing_ui" DROP COLUMN IF EXISTS "product_listing_ui_show_banner";

    ALTER TABLE "home_settings" DROP COLUMN IF EXISTS "home_product_section_show_featured_product_specs";
    ALTER TABLE "home_settings" DROP COLUMN IF EXISTS "home_product_section_show_product_visual_watermark";
    ALTER TABLE "home_settings" DROP COLUMN IF EXISTS "home_product_section_show_product_specs";
    ALTER TABLE "home_settings" DROP COLUMN IF EXISTS "home_product_section_show_inline_product_detail";
    ALTER TABLE "home_settings" DROP COLUMN IF EXISTS "home_product_section_show_category_scroll_buttons";
    ALTER TABLE "home_settings" DROP COLUMN IF EXISTS "home_hero_ui_show_overlay";
    ALTER TABLE "home_settings" DROP COLUMN IF EXISTS "home_hero_ui_show_indicators";
    ALTER TABLE "home_settings" DROP COLUMN IF EXISTS "home_hero_ui_show_controls";
    ALTER TABLE "home_settings" DROP COLUMN IF EXISTS "home_hero_ui_autoplay_interval_ms";
    ALTER TABLE "home_settings" DROP COLUMN IF EXISTS "home_hero_ui_autoplay";
  `);
}
