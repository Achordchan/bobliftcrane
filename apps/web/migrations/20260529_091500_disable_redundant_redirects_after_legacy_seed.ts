import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    WITH content_paths AS (
      SELECT legacy_path AS path FROM products WHERE status = 'published' AND legacy_path IS NOT NULL AND legacy_path <> ''
      UNION
      SELECT a.path FROM products p JOIN products_legacy_aliases a ON a._parent_id = p.id WHERE p.status = 'published' AND a.path IS NOT NULL AND a.path <> ''
      UNION
      SELECT legacy_path FROM product_categories WHERE status = 'published' AND legacy_path IS NOT NULL AND legacy_path <> ''
      UNION
      SELECT a.path FROM product_categories c JOIN product_categories_legacy_aliases a ON a._parent_id = c.id WHERE c.status = 'published' AND a.path IS NOT NULL AND a.path <> ''
      UNION
      SELECT legacy_path FROM articles WHERE status = 'published' AND legacy_path IS NOT NULL AND legacy_path <> ''
      UNION
      SELECT a.path FROM articles ar JOIN articles_legacy_aliases a ON a._parent_id = ar.id WHERE ar.status = 'published' AND a.path IS NOT NULL AND a.path <> ''
      UNION
      SELECT legacy_path FROM cases WHERE status = 'published' AND legacy_path IS NOT NULL AND legacy_path <> ''
      UNION
      SELECT a.path FROM cases ca JOIN cases_legacy_aliases a ON a._parent_id = ca.id WHERE ca.status = 'published' AND a.path IS NOT NULL AND a.path <> ''
    )
    UPDATE redirects r
    SET status = 'draft', updated_at = now()
    FROM content_paths c
    WHERE r.source_path = c.path
      AND r.status = 'published';
  `);
}

export async function down({ db: _db }: MigrateDownArgs): Promise<void> {
  // 内容已经支持旧 URL 直出，回滚不重新启用冗余跳转，避免再次造成后台 URL 冲突。
}
