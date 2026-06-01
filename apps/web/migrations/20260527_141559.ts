import { MigrateUpArgs, MigrateDownArgs, sql } from '@payloadcms/db-postgres'

export async function up({ db, payload, req }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
   ALTER TABLE "site_settings" ADD COLUMN "news_page_ui_hero_image" varchar DEFAULT '/images/factory-workshop.jpg';
  ALTER TABLE "site_settings" ADD COLUMN "cases_page_ui_hero_image" varchar DEFAULT '/images/marine-scene.jpg';
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
  ALTER TABLE "site_settings_locales" ADD COLUMN "footer_contact_title" varchar DEFAULT 'Contact Us';`)
}

export async function down({ db, payload, req }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
   ALTER TABLE "site_settings" DROP COLUMN "news_page_ui_hero_image";
  ALTER TABLE "site_settings" DROP COLUMN "cases_page_ui_hero_image";
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
  ALTER TABLE "site_settings_locales" DROP COLUMN "footer_contact_title";`)
}
