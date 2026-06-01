import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "page_text_settings_locales" ADD COLUMN IF NOT EXISTS "quote_form_ui_messages_invalid_payload" varchar DEFAULT 'The inquiry form was not submitted correctly. Please refresh the page and try again.';
    ALTER TABLE "page_text_settings_locales" ADD COLUMN IF NOT EXISTS "quote_form_ui_messages_required_fields" varchar DEFAULT 'Please enter your name and a valid email address.';
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "page_text_settings_locales" DROP COLUMN IF EXISTS "quote_form_ui_messages_invalid_payload";
    ALTER TABLE "page_text_settings_locales" DROP COLUMN IF EXISTS "quote_form_ui_messages_required_fields";
  `);
}
