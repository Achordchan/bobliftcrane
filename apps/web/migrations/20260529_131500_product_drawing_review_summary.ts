import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "products" ADD COLUMN IF NOT EXISTS "drawing_review_status" varchar DEFAULT 'not-required';
    ALTER TABLE "products" ADD COLUMN IF NOT EXISTS "drawing_review_summary" varchar;
    ALTER TABLE "products" ADD COLUMN IF NOT EXISTS "drawing_review_needed_count" numeric DEFAULT 0;
    ALTER TABLE "products" ADD COLUMN IF NOT EXISTS "drawing_reviewed_count" numeric DEFAULT 0;

    WITH drawing_stats AS (
      SELECT
        p.id,
        count(pdi.id) AS drawing_total_count,
        count(pdi.id) FILTER (
          WHERE coalesce(pdi.review_status, 'needs-review') NOT IN ('reviewed', 'not-required')
        ) AS drawing_review_needed_count,
        count(pdi.id) FILTER (
          WHERE coalesce(pdi.review_status, 'needs-review') IN ('reviewed', 'not-required')
        ) AS drawing_reviewed_count
      FROM products p
      LEFT JOIN products_drawing_images pdi ON pdi._parent_id = p.id
      GROUP BY p.id
    )
    UPDATE products p
    SET
      drawing_review_status = CASE
        WHEN coalesce(drawing_stats.drawing_total_count, 0) = 0 THEN 'not-required'
        WHEN coalesce(drawing_stats.drawing_review_needed_count, 0) = 0 THEN 'reviewed'
        WHEN coalesce(drawing_stats.drawing_reviewed_count, 0) > 0 THEN 'partially-reviewed'
        ELSE 'needs-review'
      END,
      drawing_review_summary = CASE
        WHEN coalesce(drawing_stats.drawing_total_count, 0) = 0 THEN '未上传图纸/参数图；如参数来自文字资料，可继续按参数核对状态判断'
        WHEN coalesce(drawing_stats.drawing_review_needed_count, 0) = 0 THEN drawing_stats.drawing_reviewed_count::text || '/' || drawing_stats.drawing_total_count::text || ' 张图纸/参数图已完成复核'
        ELSE drawing_stats.drawing_reviewed_count::text || '/' || drawing_stats.drawing_total_count::text || ' 张已复核，剩余 ' || drawing_stats.drawing_review_needed_count::text || ' 张待人工看图'
      END,
      drawing_review_needed_count = coalesce(drawing_stats.drawing_review_needed_count, 0),
      drawing_reviewed_count = coalesce(drawing_stats.drawing_reviewed_count, 0),
      updated_at = now()
    FROM drawing_stats
    WHERE drawing_stats.id = p.id;
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "products" DROP COLUMN IF EXISTS "drawing_reviewed_count";
    ALTER TABLE "products" DROP COLUMN IF EXISTS "drawing_review_needed_count";
    ALTER TABLE "products" DROP COLUMN IF EXISTS "drawing_review_summary";
    ALTER TABLE "products" DROP COLUMN IF EXISTS "drawing_review_status";
  `);
}
