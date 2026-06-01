import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    WITH checklist AS (
      SELECT
        p.id,
        array_remove(ARRAY[
          CASE WHEN p.category_ref_id IS NULL THEN '产品分类' END,
          CASE WHEN p.cover_image_id IS NULL THEN '封面图' END,
          CASE WHEN NOT EXISTS (SELECT 1 FROM products_locales pl WHERE pl._parent_id = p.id AND coalesce(trim(pl.intro), '') <> '') THEN '简短介绍' END,
          CASE WHEN coalesce(trim(p.capacity), '') = '' AND p.capacity_value IS NULL THEN '起重能力' END,
          CASE WHEN coalesce(trim(p.boom_type), '') = '' THEN '臂架类型' END,
          CASE WHEN NOT EXISTS (SELECT 1 FROM products_applications pa WHERE pa.parent_id = p.id AND coalesce(trim(pa.value::text), '') <> '') THEN '应用场景' END,
          CASE WHEN NOT EXISTS (SELECT 1 FROM products_specs ps WHERE ps._parent_id = p.id) THEN '规格参数' END,
          CASE WHEN NOT EXISTS (SELECT 1 FROM products_gallery pg WHERE pg._parent_id = p.id) AND NOT EXISTS (SELECT 1 FROM products_drawing_images pdi WHERE pdi._parent_id = p.id) THEN '产品图库或图纸' END,
          CASE WHEN p.parameter_review_status::text IS DISTINCT FROM 'verified-from-source' THEN '参数未按来源核对' END
        ], NULL) AS missing
      FROM products p
    )
    UPDATE products p
    SET
      frontend_content_status = CASE
        WHEN cardinality(checklist.missing) = 0 THEN 'ready'
        WHEN cardinality(checklist.missing) = 1 AND checklist.missing[1] = '参数未按来源核对' THEN 'needs-parameter-review'
        ELSE 'needs-content'
      END,
      frontend_missing_fields_note = CASE
        WHEN cardinality(checklist.missing) = 0 THEN '已具备前台测试需要的基础内容'
        ELSE array_to_string(checklist.missing, '、')
      END,
      updated_at = now()
    FROM checklist
    WHERE checklist.id = p.id;
  `);
}

export async function down({ db: _db }: MigrateDownArgs): Promise<void> {
  // 前台状态是派生数据，回滚不覆盖后台当前复核结果。
}
