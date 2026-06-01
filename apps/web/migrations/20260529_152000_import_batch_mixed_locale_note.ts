import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    UPDATE "product_import_batches_locales"
    SET "review_checklist" = 'Check every product against the original source page or factory data before changing the product status to verified-from-source. Drawing images are supporting records only and no longer block readiness. Preserve already verified parameters when rerunning imports.'
    WHERE "review_checklist" LIKE '%打开图纸/参数图%';
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    UPDATE "product_import_batches_locales"
    SET "review_checklist" = '1) 对照旧站页面原文复核规格参数；2) 打开图纸/参数图确认图中数值与后台规格一致；3) 全部无误后再把产品图纸复核状态改为已看图复核。'
    WHERE "_locale" = 'en'
      AND "review_checklist" ILIKE '%Drawing images are supporting records only%';
  `);
}
