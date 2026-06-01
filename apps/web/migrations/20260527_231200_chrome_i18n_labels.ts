import { MigrateUpArgs, MigrateDownArgs, sql } from '@payloadcms/db-postgres'

export async function up({ db, payload, req }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
   ALTER TABLE "site_settings_locales" ADD COLUMN "chrome_ui_main_navigation_label" varchar DEFAULT 'Main navigation';
  ALTER TABLE "site_settings_locales" ADD COLUMN "chrome_ui_logo_home_label" varchar DEFAULT 'BOB-LIFT Home';
  ALTER TABLE "site_settings_locales" ADD COLUMN "chrome_ui_logo_image_alt" varchar DEFAULT 'BOB-LIFT Cranes';
  ALTER TABLE "site_settings_locales" ADD COLUMN "chrome_ui_floating_email_label" varchar DEFAULT 'Email';
  ALTER TABLE "site_settings_locales" ADD COLUMN "chrome_ui_floating_whats_app_label" varchar DEFAULT 'WhatsApp';
  ALTER TABLE "site_settings_locales" ADD COLUMN "chrome_ui_floating_call_label" varchar DEFAULT 'Call';
  ALTER TABLE "site_settings_locales" ADD COLUMN "chrome_ui_we_chat_qr_alt" varchar DEFAULT 'BOB-LIFT WeChat QR code';
  ALTER TABLE "site_settings_locales" ADD COLUMN "chrome_ui_back_to_top_label" varchar DEFAULT 'Back to top';`)
}

export async function down({ db, payload, req }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
   ALTER TABLE "site_settings_locales" DROP COLUMN "chrome_ui_main_navigation_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "chrome_ui_logo_home_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "chrome_ui_logo_image_alt";
  ALTER TABLE "site_settings_locales" DROP COLUMN "chrome_ui_floating_email_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "chrome_ui_floating_whats_app_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "chrome_ui_floating_call_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "chrome_ui_we_chat_qr_alt";
  ALTER TABLE "site_settings_locales" DROP COLUMN "chrome_ui_back_to_top_label";`)
}
