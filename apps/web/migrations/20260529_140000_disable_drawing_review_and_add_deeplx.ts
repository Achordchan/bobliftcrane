import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    ALTER TYPE "public"."enum_translation_jobs_provider" ADD VALUE IF NOT EXISTS 'deeplx';
    ALTER TYPE "public"."enum_translation_settings_provider" ADD VALUE IF NOT EXISTS 'deeplx';

    ALTER TABLE "translation_settings" ADD COLUMN IF NOT EXISTS "deeplx_api_url_env_name" varchar DEFAULT 'DEEPLX_API_URL';
    ALTER TABLE "translation_settings" ADD COLUMN IF NOT EXISTS "deeplx_auth_key_env_name" varchar DEFAULT 'DEEPLX_API_KEY';

    UPDATE "translation_settings"
    SET
      "deeplx_api_url_env_name" = COALESCE(NULLIF("deeplx_api_url_env_name", ''), 'DEEPLX_API_URL'),
      "deeplx_auth_key_env_name" = COALESCE(NULLIF("deeplx_auth_key_env_name", ''), 'DEEPLX_API_KEY'),
      "notes" = '1) Coverage: npm run payload:translate -- --coverage --include-stale --sync-readiness. 2) DeepL Apply: DEEPL_AUTH_KEY=xxx npm run payload:translate -- --provider=deepl --include-stale --apply. 3) DeepLX Apply: DEEPLX_API_URL=https://... npm run payload:translate -- --provider=deeplx --include-stale --apply. 4) Go live only after the target language has no missing/stale fields and is marked ready.'
    WHERE "id" IS NOT NULL;

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
    ),
    drawing_stats AS (
      SELECT
        p.id,
        count(pdi.id) AS drawing_total_count
      FROM products p
      LEFT JOIN products_drawing_images pdi ON pdi._parent_id = p.id
      GROUP BY p.id
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
      drawing_review_status = 'not-required',
      drawing_review_summary = CASE
        WHEN coalesce(drawing_stats.drawing_total_count, 0) = 0 THEN '未上传图纸/参数图；当前不要求人工图纸复核'
        ELSE drawing_stats.drawing_total_count::text || ' 张图纸/参数图已记录；当前不要求人工图纸复核'
      END,
      drawing_review_needed_count = 0,
      drawing_reviewed_count = coalesce(drawing_stats.drawing_total_count, 0),
      updated_at = now()
    FROM checklist
    LEFT JOIN drawing_stats ON drawing_stats.id = checklist.id
    WHERE checklist.id = p.id;

    WITH stats AS (
      SELECT
        p.import_batch_id,
        count(*) AS product_count,
        count(*) FILTER (WHERE p.parameter_review_status::text = 'verified-from-source') AS verified_count,
        count(*) FILTER (WHERE p.parameter_review_status::text = 'in-review') AS in_review_count,
        count(*) FILTER (WHERE coalesce(p.parameter_review_status::text, 'needs-review') NOT IN ('verified-from-source', 'in-review')) AS needs_review_count,
        count(pdi.id) AS drawing_record_count
      FROM products p
      LEFT JOIN products_drawing_images pdi ON pdi._parent_id = p.id
      WHERE NULLIF(p.import_batch_id, '') IS NOT NULL
      GROUP BY p.import_batch_id
    )
    UPDATE product_import_batches pib
    SET
      product_count = stats.product_count,
      verified_count = stats.verified_count,
      in_review_count = stats.in_review_count,
      needs_review_count = stats.needs_review_count,
      drawing_review_needed_count = 0,
      drawing_reviewed_count = stats.drawing_record_count,
      updated_at = now()
    FROM stats
    WHERE pib.batch_id = stats.import_batch_id;
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "translation_settings" DROP COLUMN IF EXISTS "deeplx_api_url_env_name";
    ALTER TABLE "translation_settings" DROP COLUMN IF EXISTS "deeplx_auth_key_env_name";
  `);
}
