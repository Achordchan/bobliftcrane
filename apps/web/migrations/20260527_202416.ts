import { MigrateUpArgs, MigrateDownArgs, sql } from '@payloadcms/db-postgres'

export async function up({ db, payload, req }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
   ALTER TABLE "home_settings" ADD COLUMN "home_seo_canonical_path" varchar DEFAULT '/';
  ALTER TABLE "home_settings_locales" ADD COLUMN "home_seo_meta_title" varchar DEFAULT 'BOB-LIFT | Truck Mounted Cranes and Lifting Solutions';
  ALTER TABLE "home_settings_locales" ADD COLUMN "home_seo_meta_description" varchar DEFAULT 'BOB-LIFT supplies truck mounted cranes, marine cranes, aerial work platforms and lifting attachments for global projects.';
  ALTER TABLE "page_text_settings" ADD COLUMN "contact_page_ui_canonical_path" varchar DEFAULT '/contact';
  ALTER TABLE "page_text_settings" ADD COLUMN "quote_page_ui_canonical_path" varchar DEFAULT '/quote';
  ALTER TABLE "page_text_settings" ADD COLUMN "news_page_ui_canonical_path" varchar DEFAULT '/news';
  ALTER TABLE "page_text_settings" ADD COLUMN "cases_page_ui_canonical_path" varchar DEFAULT '/cases';
  ALTER TABLE "page_text_settings" ADD COLUMN "about_page_ui_canonical_path" varchar DEFAULT '/about';
  ALTER TABLE "page_text_settings_locales" ADD COLUMN "contact_page_ui_meta_title" varchar DEFAULT 'Contact Us | BOB-LIFT';
  ALTER TABLE "page_text_settings_locales" ADD COLUMN "contact_page_ui_meta_description" varchar DEFAULT 'Contact BOB-LIFT by email, phone, WhatsApp or WeChat for crane quotation and technical consultation.';
  ALTER TABLE "page_text_settings_locales" ADD COLUMN "quote_page_ui_meta_title" varchar DEFAULT 'Request Quote | BOB-LIFT';
  ALTER TABLE "page_text_settings_locales" ADD COLUMN "quote_page_ui_meta_description" varchar DEFAULT 'Send BOB-LIFT your lifting requirement and prepare a quotation inquiry by email or WhatsApp.';`)
}

export async function down({ db, payload, req }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
   ALTER TABLE "home_settings" DROP COLUMN "home_seo_canonical_path";
  ALTER TABLE "home_settings_locales" DROP COLUMN "home_seo_meta_title";
  ALTER TABLE "home_settings_locales" DROP COLUMN "home_seo_meta_description";
  ALTER TABLE "page_text_settings" DROP COLUMN "contact_page_ui_canonical_path";
  ALTER TABLE "page_text_settings" DROP COLUMN "quote_page_ui_canonical_path";
  ALTER TABLE "page_text_settings" DROP COLUMN "news_page_ui_canonical_path";
  ALTER TABLE "page_text_settings" DROP COLUMN "cases_page_ui_canonical_path";
  ALTER TABLE "page_text_settings" DROP COLUMN "about_page_ui_canonical_path";
  ALTER TABLE "page_text_settings_locales" DROP COLUMN "contact_page_ui_meta_title";
  ALTER TABLE "page_text_settings_locales" DROP COLUMN "contact_page_ui_meta_description";
  ALTER TABLE "page_text_settings_locales" DROP COLUMN "quote_page_ui_meta_title";
  ALTER TABLE "page_text_settings_locales" DROP COLUMN "quote_page_ui_meta_description";`)
}
