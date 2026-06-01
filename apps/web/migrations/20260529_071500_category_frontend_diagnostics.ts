import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "product_categories" ADD COLUMN IF NOT EXISTS "frontend_surface_status" varchar DEFAULT 'hidden';
    ALTER TABLE "product_categories" ADD COLUMN IF NOT EXISTS "frontend_surface_note" varchar;

    WITH checklist AS (
      SELECT
        pc.id,
        array_remove(ARRAY[
          CASE WHEN pc.status::text IS DISTINCT FROM 'published' THEN '分类未发布' END,
          CASE WHEN pc.page_type::text = 'redirect-only' THEN '栏目类型为不在前台展示' END,
          CASE WHEN NOT EXISTS (SELECT 1 FROM product_categories_locales pcl WHERE pcl._parent_id = pc.id AND coalesce(trim(pcl.title), '') <> '') THEN '分类名称' END,
          CASE WHEN coalesce(trim(pc.slug), '') = '' THEN 'slug' END
        ], NULL) AS blocking,
        array_remove(ARRAY[
          CASE WHEN coalesce(trim(pc.legacy_path), '') = '' THEN '未填写旧站公开 URL，将使用 /products/category/{slug}' END,
          CASE WHEN pc.show_in_mega_menu IS NOT TRUE THEN '不进入顶部 Products 菜单' END,
          CASE WHEN pc.show_on_home IS NOT TRUE THEN '不进入首页产品分类' END
        ], NULL) AS notes
      FROM product_categories pc
    )
    UPDATE product_categories pc
    SET
      frontend_surface_status = CASE
        WHEN cardinality(checklist.blocking) > 0 THEN 'hidden'
        WHEN EXISTS (SELECT 1 FROM unnest(checklist.notes) AS n(note) WHERE n.note LIKE '%公开 URL%') THEN 'needs-url-review'
        ELSE 'ready'
      END,
      frontend_surface_note = CASE
        WHEN cardinality(checklist.blocking) > 0 THEN array_to_string(checklist.blocking || checklist.notes, '、')
        WHEN cardinality(checklist.notes) > 0 THEN array_to_string(checklist.notes, '、')
        ELSE '已具备产品列表筛选、分类页、顶部菜单和首页分类测试条件'
      END,
      updated_at = now()
    FROM checklist
    WHERE checklist.id = pc.id;
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "product_categories" DROP COLUMN IF EXISTS "frontend_surface_note";
    ALTER TABLE "product_categories" DROP COLUMN IF EXISTS "frontend_surface_status";
  `);
}
