import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    DO $$ BEGIN
      CREATE TYPE "public"."enum_inquiries_notification_status" AS ENUM('pending', 'sent', 'skipped', 'failed');
    EXCEPTION
      WHEN duplicate_object THEN null;
    END $$;

    ALTER TABLE "site_settings" ADD COLUMN IF NOT EXISTS "inquiry_notification_enabled" boolean DEFAULT true;
    ALTER TABLE "site_settings" ADD COLUMN IF NOT EXISTS "inquiry_notification_recipients" varchar DEFAULT 'info@bobliftcrane.com';
    ALTER TABLE "site_settings" ADD COLUMN IF NOT EXISTS "inquiry_notification_webhook_env_name" varchar DEFAULT 'INQUIRY_WEBHOOK_URL';
    ALTER TABLE "site_settings_locales" ADD COLUMN IF NOT EXISTS "inquiry_notification_subject" varchar DEFAULT 'New BOB-LIFT inquiry: {product}';
    ALTER TABLE "site_settings_locales" ADD COLUMN IF NOT EXISTS "inquiry_notification_intro" varchar DEFAULT 'A new inquiry has been saved in the BOB-LIFT admin. Please review and follow up.';

    ALTER TABLE "inquiries" ADD COLUMN IF NOT EXISTS "notification_status" "enum_inquiries_notification_status" DEFAULT 'pending';
    ALTER TABLE "inquiries" ADD COLUMN IF NOT EXISTS "notified_at" timestamptz;
    ALTER TABLE "inquiries" ADD COLUMN IF NOT EXISTS "notification_error" varchar;
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "inquiries" DROP COLUMN IF EXISTS "notification_error";
    ALTER TABLE "inquiries" DROP COLUMN IF EXISTS "notified_at";
    ALTER TABLE "inquiries" DROP COLUMN IF EXISTS "notification_status";
    ALTER TABLE "site_settings_locales" DROP COLUMN IF EXISTS "inquiry_notification_intro";
    ALTER TABLE "site_settings_locales" DROP COLUMN IF EXISTS "inquiry_notification_subject";
    ALTER TABLE "site_settings" DROP COLUMN IF EXISTS "inquiry_notification_webhook_env_name";
    ALTER TABLE "site_settings" DROP COLUMN IF EXISTS "inquiry_notification_recipients";
    ALTER TABLE "site_settings" DROP COLUMN IF EXISTS "inquiry_notification_enabled";
    DROP TYPE IF EXISTS "public"."enum_inquiries_notification_status";
  `);
}
