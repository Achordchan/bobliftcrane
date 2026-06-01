import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    WITH product_aliases AS (
      SELECT
        p."id" AS parent_id,
        r."source_path" AS path,
        row_number() OVER (PARTITION BY p."id" ORDER BY r."source_path") - 1 AS order_index
      FROM "redirects" r
      JOIN "products" p ON r."target_path" = '/products/' || p."slug"
      WHERE r."status" = 'published'
        AND coalesce(trim(r."source_path"), '') <> ''
        AND r."source_path" IS DISTINCT FROM p."legacy_path"
        AND NOT EXISTS (
          SELECT 1 FROM "products_legacy_aliases" existing
          WHERE existing."_parent_id" = p."id" AND existing."path" = r."source_path"
        )
    )
    INSERT INTO "products_legacy_aliases" ("_order", "_parent_id", "id", "path")
    SELECT
      coalesce((SELECT max(existing."_order") + 1 FROM "products_legacy_aliases" existing WHERE existing."_parent_id" = product_aliases.parent_id), 0) + product_aliases.order_index,
      product_aliases.parent_id,
      'product-alias-' || product_aliases.parent_id::text || '-' || md5(product_aliases.path),
      product_aliases.path
    FROM product_aliases
    ON CONFLICT ("id") DO NOTHING;

    WITH category_aliases AS (
      SELECT
        c."id" AS parent_id,
        r."source_path" AS path,
        row_number() OVER (PARTITION BY c."id" ORDER BY r."source_path") - 1 AS order_index
      FROM "redirects" r
      JOIN "product_categories" c ON r."target_path" = '/products/category/' || c."slug"
      WHERE r."status" = 'published'
        AND coalesce(trim(r."source_path"), '') <> ''
        AND r."source_path" IS DISTINCT FROM c."legacy_path"
        AND NOT EXISTS (
          SELECT 1 FROM "product_categories_legacy_aliases" existing
          WHERE existing."_parent_id" = c."id" AND existing."path" = r."source_path"
        )
    )
    INSERT INTO "product_categories_legacy_aliases" ("_order", "_parent_id", "id", "path")
    SELECT
      coalesce((SELECT max(existing."_order") + 1 FROM "product_categories_legacy_aliases" existing WHERE existing."_parent_id" = category_aliases.parent_id), 0) + category_aliases.order_index,
      category_aliases.parent_id,
      'category-alias-' || category_aliases.parent_id::text || '-' || md5(category_aliases.path),
      category_aliases.path
    FROM category_aliases
    ON CONFLICT ("id") DO NOTHING;

    WITH article_aliases AS (
      SELECT
        a."id" AS parent_id,
        r."source_path" AS path,
        row_number() OVER (PARTITION BY a."id" ORDER BY r."source_path") - 1 AS order_index
      FROM "redirects" r
      JOIN "articles" a ON r."target_path" = '/news/' || a."slug"
      WHERE r."status" = 'published'
        AND coalesce(trim(r."source_path"), '') <> ''
        AND r."source_path" IS DISTINCT FROM a."legacy_path"
        AND NOT EXISTS (
          SELECT 1 FROM "articles_legacy_aliases" existing
          WHERE existing."_parent_id" = a."id" AND existing."path" = r."source_path"
        )
    )
    INSERT INTO "articles_legacy_aliases" ("_order", "_parent_id", "id", "path")
    SELECT
      coalesce((SELECT max(existing."_order") + 1 FROM "articles_legacy_aliases" existing WHERE existing."_parent_id" = article_aliases.parent_id), 0) + article_aliases.order_index,
      article_aliases.parent_id,
      'article-alias-' || article_aliases.parent_id::text || '-' || md5(article_aliases.path),
      article_aliases.path
    FROM article_aliases
    ON CONFLICT ("id") DO NOTHING;

    WITH case_aliases AS (
      SELECT
        c."id" AS parent_id,
        r."source_path" AS path,
        row_number() OVER (PARTITION BY c."id" ORDER BY r."source_path") - 1 AS order_index
      FROM "redirects" r
      JOIN "cases" c ON r."target_path" = '/cases/' || c."slug"
      WHERE r."status" = 'published'
        AND coalesce(trim(r."source_path"), '') <> ''
        AND r."source_path" IS DISTINCT FROM c."legacy_path"
        AND NOT EXISTS (
          SELECT 1 FROM "cases_legacy_aliases" existing
          WHERE existing."_parent_id" = c."id" AND existing."path" = r."source_path"
        )
    )
    INSERT INTO "cases_legacy_aliases" ("_order", "_parent_id", "id", "path")
    SELECT
      coalesce((SELECT max(existing."_order") + 1 FROM "cases_legacy_aliases" existing WHERE existing."_parent_id" = case_aliases.parent_id), 0) + case_aliases.order_index,
      case_aliases.parent_id,
      'case-alias-' || case_aliases.parent_id::text || '-' || md5(case_aliases.path),
      case_aliases.path
    FROM case_aliases
    ON CONFLICT ("id") DO NOTHING;
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    DELETE FROM "cases_legacy_aliases" WHERE "id" LIKE 'case-alias-%';
    DELETE FROM "articles_legacy_aliases" WHERE "id" LIKE 'article-alias-%';
    DELETE FROM "product_categories_legacy_aliases" WHERE "id" LIKE 'category-alias-%';
    DELETE FROM "products_legacy_aliases" WHERE "id" LIKE 'product-alias-%';
  `);
}
