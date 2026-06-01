import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    UPDATE "product_import_batches_locales"
    SET "review_checklist" = '把产品状态改为“已按来源核对”前，必须逐项对照旧站原页面或厂家资料；图纸/参数图只作为资料记录，不再作为复核阻塞。重跑导入时保留已核对参数。'
    WHERE "_locale" = 'zh'
      AND "review_checklist" LIKE '%图纸/参数图%';

    UPDATE "product_import_batches_locales"
    SET "review_checklist" = 'Check every product against the original source page or factory data before changing the product status to verified-from-source. Drawing images are supporting records only and no longer block readiness. Preserve already verified parameters when rerunning imports.'
    WHERE "_locale" = 'en'
      AND ("review_checklist" ILIKE '%drawing images%' OR "review_checklist" ILIKE '%drawing%');
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    UPDATE "product_import_batches_locales"
    SET "review_checklist" = '把产品状态改为“已按来源核对”前，必须逐项对照旧站原页面和图纸/参数图；重跑导入时保留已核对参数。'
    WHERE "_locale" = 'zh'
      AND "review_checklist" LIKE '%图纸/参数图只作为资料记录%';

    UPDATE "product_import_batches_locales"
    SET "review_checklist" = 'Check every product against the original page and drawing images before changing the product status to verified-from-source. Preserve already verified parameters when rerunning imports.'
    WHERE "_locale" = 'en'
      AND "review_checklist" ILIKE '%Drawing images are supporting records only%';
  `);
}
