import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    WITH latest_settings AS (
      SELECT id
      FROM "translation_settings"
      ORDER BY "created_at" DESC NULLS LAST, "id" DESC
      LIMIT 1
    ),
    target_locales AS (
      SELECT *
      FROM (VALUES
        (0, 'zh'),
        (1, 'es'),
        (2, 'ar'),
        (3, 'fr'),
        (4, 'de'),
        (5, 'pt'),
        (6, 'ru'),
        (7, 'tr'),
        (8, 'id'),
        (9, 'vi'),
        (10, 'th'),
        (11, 'hi'),
        (12, 'ko')
      ) AS locale_list("order", "value")
    )
    INSERT INTO "translation_settings_target_locales" ("order", "parent_id", "value")
    SELECT target_locales."order", latest_settings.id, target_locales."value"
    FROM latest_settings
    CROSS JOIN target_locales
    WHERE NOT EXISTS (
      SELECT 1
      FROM "translation_settings_target_locales" existing
      WHERE existing."parent_id" = latest_settings.id
        AND existing."value" = target_locales."value"
    );

    DELETE FROM "translation_settings_texts"
    WHERE "path" = 'targetLocales';

    UPDATE "translation_settings"
    SET
      "notes" = '1) Coverage: npm run payload:translate -- --coverage --include-stale --sync-readiness. 2) DeepL Apply: DEEPL_AUTH_KEY=xxx npm run payload:translate -- --provider=deepl --include-stale --apply. 3) DeepLX Apply: DEEPLX_API_URL=https://... npm run payload:translate -- --provider=deeplx --include-stale --apply. 4) Default targets cover zh, es, ar, fr, de, pt, ru, tr, id, vi, th, hi, ko; scope includes pages, products, categories, articles, cases, and media Alt/Caption. 5) Go live only after the target language has no missing/stale fields and is marked ready.',
      "updated_at" = now()
    WHERE "id" IN (SELECT id FROM "translation_settings" ORDER BY "created_at" DESC NULLS LAST, "id" DESC LIMIT 1);
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    DELETE FROM "translation_settings_target_locales"
    WHERE "value" IN ('fr', 'de', 'pt', 'ru', 'tr', 'id', 'vi', 'th', 'hi', 'ko');
  `);
}
