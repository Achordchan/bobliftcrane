import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "page_text_settings"
      ADD COLUMN IF NOT EXISTS "quote_form_ui_field_controls_name_visible" boolean DEFAULT true,
      ADD COLUMN IF NOT EXISTS "quote_form_ui_field_controls_name_required" boolean DEFAULT true,
      ADD COLUMN IF NOT EXISTS "quote_form_ui_field_controls_email_visible" boolean DEFAULT true,
      ADD COLUMN IF NOT EXISTS "quote_form_ui_field_controls_email_required" boolean DEFAULT true,
      ADD COLUMN IF NOT EXISTS "quote_form_ui_field_controls_phone_visible" boolean DEFAULT true,
      ADD COLUMN IF NOT EXISTS "quote_form_ui_field_controls_phone_required" boolean DEFAULT false,
      ADD COLUMN IF NOT EXISTS "quote_form_ui_field_controls_country_visible" boolean DEFAULT true,
      ADD COLUMN IF NOT EXISTS "quote_form_ui_field_controls_country_required" boolean DEFAULT false,
      ADD COLUMN IF NOT EXISTS "quote_form_ui_field_controls_product_visible" boolean DEFAULT true,
      ADD COLUMN IF NOT EXISTS "quote_form_ui_field_controls_product_required" boolean DEFAULT false,
      ADD COLUMN IF NOT EXISTS "quote_form_ui_field_controls_quantity_visible" boolean DEFAULT true,
      ADD COLUMN IF NOT EXISTS "quote_form_ui_field_controls_quantity_required" boolean DEFAULT false,
      ADD COLUMN IF NOT EXISTS "quote_form_ui_field_controls_message_visible" boolean DEFAULT true,
      ADD COLUMN IF NOT EXISTS "quote_form_ui_field_controls_message_required" boolean DEFAULT false,
      ADD COLUMN IF NOT EXISTS "quote_form_ui_button_channels_save" boolean DEFAULT true,
      ADD COLUMN IF NOT EXISTS "quote_form_ui_button_channels_email" boolean DEFAULT true,
      ADD COLUMN IF NOT EXISTS "quote_form_ui_button_channels_whatsapp" boolean DEFAULT true;
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "page_text_settings"
      DROP COLUMN IF EXISTS "quote_form_ui_button_channels_whatsapp",
      DROP COLUMN IF EXISTS "quote_form_ui_button_channels_email",
      DROP COLUMN IF EXISTS "quote_form_ui_button_channels_save",
      DROP COLUMN IF EXISTS "quote_form_ui_field_controls_message_required",
      DROP COLUMN IF EXISTS "quote_form_ui_field_controls_message_visible",
      DROP COLUMN IF EXISTS "quote_form_ui_field_controls_quantity_required",
      DROP COLUMN IF EXISTS "quote_form_ui_field_controls_quantity_visible",
      DROP COLUMN IF EXISTS "quote_form_ui_field_controls_product_required",
      DROP COLUMN IF EXISTS "quote_form_ui_field_controls_product_visible",
      DROP COLUMN IF EXISTS "quote_form_ui_field_controls_country_required",
      DROP COLUMN IF EXISTS "quote_form_ui_field_controls_country_visible",
      DROP COLUMN IF EXISTS "quote_form_ui_field_controls_phone_required",
      DROP COLUMN IF EXISTS "quote_form_ui_field_controls_phone_visible",
      DROP COLUMN IF EXISTS "quote_form_ui_field_controls_email_required",
      DROP COLUMN IF EXISTS "quote_form_ui_field_controls_email_visible",
      DROP COLUMN IF EXISTS "quote_form_ui_field_controls_name_required",
      DROP COLUMN IF EXISTS "quote_form_ui_field_controls_name_visible";
  `);
}
