import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "home_settings_locales" ADD COLUMN "about_section_gallery_thumbnail_label_template" varchar DEFAULT 'Show {title}';
    ALTER TABLE "home_settings_locales" ADD COLUMN "about_section_video_label" varchar DEFAULT 'Video';

    UPDATE "home_settings_locales"
    SET
      "about_section_gallery_thumbnail_label_template" = '显示 {title}',
      "about_section_video_label" = '视频'
    WHERE "_locale" = 'zh';
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "home_settings_locales" DROP COLUMN "about_section_video_label";
    ALTER TABLE "home_settings_locales" DROP COLUMN "about_section_gallery_thumbnail_label_template";
  `);
}
