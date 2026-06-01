import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    WITH product_batches AS (
      SELECT DISTINCT p.import_batch_id AS batch_id
      FROM products p
      WHERE coalesce(trim(p.import_batch_id), '') <> ''
    ),
    stats AS (
      SELECT
        p.import_batch_id AS batch_id,
        count(*) AS product_count,
        count(*) FILTER (WHERE p.parameter_review_status::text = 'verified-from-source') AS verified_count,
        count(*) FILTER (WHERE p.parameter_review_status::text = 'in-review') AS in_review_count,
        count(*) FILTER (WHERE p.parameter_review_status::text = 'needs-review') AS needs_review_count,
        coalesce((
          SELECT count(*)
          FROM products_drawing_images pdi
          JOIN products p2 ON p2.id = pdi._parent_id
          WHERE p2.import_batch_id = p.import_batch_id
            AND coalesce(pdi.review_status, 'needs-review') NOT IN ('reviewed', 'not-required')
        ), 0) AS drawing_review_needed_count,
        coalesce((
          SELECT count(*)
          FROM products_drawing_images pdi
          JOIN products p2 ON p2.id = pdi._parent_id
          WHERE p2.import_batch_id = p.import_batch_id
            AND coalesce(pdi.review_status, 'needs-review') IN ('reviewed', 'not-required')
        ), 0) AS drawing_reviewed_count
      FROM products p
      WHERE coalesce(trim(p.import_batch_id), '') <> ''
      GROUP BY p.import_batch_id
    ),
    inserted AS (
      INSERT INTO product_import_batches (
        batch_id,
        status,
        source_type,
        source_base_url,
        sitemap_url,
        product_count,
        imported_count,
        skipped_count,
        failed_count,
        needs_review_count,
        in_review_count,
        verified_count,
        drawing_review_needed_count,
        drawing_reviewed_count,
        overwrite_policy,
        run_action,
        summary,
        updated_at,
        created_at
      )
      SELECT
        product_batches.batch_id,
        'in-review'::enum_product_import_batches_status,
        'legacy-site'::enum_product_import_batches_source_type,
        'https://www.bobliftcrane.com',
        'https://www.bobliftcrane.com/sitemap.xml',
        coalesce(stats.product_count, 0),
        coalesce(stats.product_count, 0),
        0,
        0,
        coalesce(stats.needs_review_count, 0),
        coalesce(stats.in_review_count, 0),
        coalesce(stats.verified_count, 0),
        coalesce(stats.drawing_review_needed_count, 0),
        coalesce(stats.drawing_reviewed_count, 0),
        'preserve-verified'::enum_product_import_batches_overwrite_policy,
        'source-audit'::enum_product_import_batches_run_action,
        '系统根据现有产品 import_batch_id 自动补建，便于在后台集中查看产品、参数复核与图纸复核状态。',
        now(),
        now()
      FROM product_batches
      LEFT JOIN stats ON stats.batch_id = product_batches.batch_id
      WHERE NOT EXISTS (
        SELECT 1 FROM product_import_batches existing WHERE existing.batch_id = product_batches.batch_id
      )
      RETURNING id, batch_id
    )
    INSERT INTO product_import_batches_locales (title, review_checklist, notes, _locale, _parent_id)
    SELECT
      'Legacy product batch ' || inserted.batch_id,
      '1) 对照旧站页面原文复核规格参数；2) 打开图纸/参数图确认图中数值与后台规格一致；3) 全部无误后再把产品图纸复核状态改为已看图复核。',
      '自动补建的历史导入批次。',
      'en'::_locales,
      inserted.id
    FROM inserted
    UNION ALL
    SELECT
      '旧站产品批次 ' || inserted.batch_id,
      '1) 对照旧站页面原文复核规格参数；2) 打开图纸/参数图确认图中数值与后台规格一致；3) 全部无误后再把产品图纸复核状态改为已看图复核。',
      '自动补建的历史导入批次。',
      'zh'::_locales,
      inserted.id
    FROM inserted;

    WITH stats AS (
      SELECT
        p.import_batch_id AS batch_id,
        count(*) AS product_count,
        count(*) FILTER (WHERE p.parameter_review_status::text = 'verified-from-source') AS verified_count,
        count(*) FILTER (WHERE p.parameter_review_status::text = 'in-review') AS in_review_count,
        count(*) FILTER (WHERE p.parameter_review_status::text = 'needs-review') AS needs_review_count,
        coalesce((
          SELECT count(*)
          FROM products_drawing_images pdi
          JOIN products p2 ON p2.id = pdi._parent_id
          WHERE p2.import_batch_id = p.import_batch_id
            AND coalesce(pdi.review_status, 'needs-review') NOT IN ('reviewed', 'not-required')
        ), 0) AS drawing_review_needed_count,
        coalesce((
          SELECT count(*)
          FROM products_drawing_images pdi
          JOIN products p2 ON p2.id = pdi._parent_id
          WHERE p2.import_batch_id = p.import_batch_id
            AND coalesce(pdi.review_status, 'needs-review') IN ('reviewed', 'not-required')
        ), 0) AS drawing_reviewed_count
      FROM products p
      WHERE coalesce(trim(p.import_batch_id), '') <> ''
      GROUP BY p.import_batch_id
    )
    UPDATE product_import_batches pib
    SET
      product_count = stats.product_count,
      imported_count = stats.product_count,
      needs_review_count = stats.needs_review_count,
      in_review_count = stats.in_review_count,
      verified_count = stats.verified_count,
      drawing_review_needed_count = stats.drawing_review_needed_count,
      drawing_reviewed_count = stats.drawing_reviewed_count,
      updated_at = now()
    FROM stats
    WHERE stats.batch_id = pib.batch_id;

    DELETE FROM product_import_batches_rels WHERE path = 'products';
    INSERT INTO product_import_batches_rels ("order", parent_id, path, products_id)
    SELECT
      row_number() OVER (PARTITION BY pib.id ORDER BY p.id) - 1,
      pib.id,
      'products',
      p.id
    FROM product_import_batches pib
    JOIN products p ON p.import_batch_id = pib.batch_id
    WHERE coalesce(trim(p.import_batch_id), '') <> ''
    ORDER BY pib.id, p.id;
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    DELETE FROM product_import_batches_rels
    WHERE parent_id IN (
      SELECT pib.id
      FROM product_import_batches pib
      WHERE pib.summary = '系统根据现有产品 import_batch_id 自动补建，便于在后台集中查看产品、参数复核与图纸复核状态。'
    );

    DELETE FROM product_import_batches_locales
    WHERE _parent_id IN (
      SELECT pib.id
      FROM product_import_batches pib
      WHERE pib.summary = '系统根据现有产品 import_batch_id 自动补建，便于在后台集中查看产品、参数复核与图纸复核状态。'
    );

    DELETE FROM product_import_batches
    WHERE summary = '系统根据现有产品 import_batch_id 自动补建，便于在后台集中查看产品、参数复核与图纸复核状态。';
  `);
}
