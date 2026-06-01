import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    WITH content_paths AS (
      SELECT legacy_path AS path FROM products WHERE legacy_path IS NOT NULL AND legacy_path <> ''
      UNION
      SELECT path FROM products_legacy_aliases WHERE path IS NOT NULL AND path <> ''
      UNION
      SELECT legacy_path FROM product_categories WHERE legacy_path IS NOT NULL AND legacy_path <> ''
      UNION
      SELECT path FROM product_categories_legacy_aliases WHERE path IS NOT NULL AND path <> ''
      UNION
      SELECT legacy_path FROM articles WHERE legacy_path IS NOT NULL AND legacy_path <> ''
      UNION
      SELECT path FROM articles_legacy_aliases WHERE path IS NOT NULL AND path <> ''
      UNION
      SELECT legacy_path FROM cases WHERE legacy_path IS NOT NULL AND legacy_path <> ''
      UNION
      SELECT path FROM cases_legacy_aliases WHERE path IS NOT NULL AND path <> ''
    )
    UPDATE redirects r
    SET
      status = 'draft',
      updated_at = now()
    FROM content_paths c
    WHERE r.source_path = c.path
      AND r.status = 'published';
  `);
}

export async function down({ db: _db }: MigrateDownArgs): Promise<void> {
  // 旧跳转已由内容直出替代，回滚不自动重新启用，避免恢复旧站跳转。
}
