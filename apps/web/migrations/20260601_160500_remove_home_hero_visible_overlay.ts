import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "home_settings_home_hero_slides"
      DROP COLUMN IF EXISTS "primary_cta_href",
      DROP COLUMN IF EXISTS "secondary_cta_href";

    ALTER TABLE "home_settings_home_hero_slides_locales"
      DROP COLUMN IF EXISTS "eyebrow",
      DROP COLUMN IF EXISTS "title",
      DROP COLUMN IF EXISTS "primary_cta_label",
      DROP COLUMN IF EXISTS "secondary_cta_label";

    ALTER TABLE "home_settings"
      DROP COLUMN IF EXISTS "home_hero_ui_show_overlay";

    UPDATE "home_settings_locales"
    SET "home_hero_ui_slide_indicator_label_template" = CASE "_locale"
      WHEN 'zh' THEN '显示第 {index} 张横幅'
      WHEN 'es' THEN 'Mostrar banner {index}'
      WHEN 'ar' THEN 'عرض الشريحة {index}'
      ELSE 'Show banner {index}'
    END
    WHERE "home_hero_ui_slide_indicator_label_template" LIKE '%{title}%';
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "home_settings_home_hero_slides"
      ADD COLUMN IF NOT EXISTS "primary_cta_href" varchar,
      ADD COLUMN IF NOT EXISTS "secondary_cta_href" varchar;

    ALTER TABLE "home_settings_home_hero_slides_locales"
      ADD COLUMN IF NOT EXISTS "eyebrow" varchar,
      ADD COLUMN IF NOT EXISTS "title" varchar,
      ADD COLUMN IF NOT EXISTS "primary_cta_label" varchar,
      ADD COLUMN IF NOT EXISTS "secondary_cta_label" varchar;

    ALTER TABLE "home_settings"
      ADD COLUMN IF NOT EXISTS "home_hero_ui_show_overlay" boolean DEFAULT true;
  `);
}
