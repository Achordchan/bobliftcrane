import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    WITH inferred AS (
      SELECT
        p."id",
        (regexp_match(pl."title", '^([0-9]+(?:[.][0-9]+)?)[[:space:]]+Ton\\y', 'i'))[1]::numeric AS title_tons
      FROM "products" p
      JOIN "products_locales" pl
        ON pl."_parent_id" = p."id"
       AND pl."_locale" = 'en'
      WHERE p."import_source" = 'legacy-site'
        AND pl."title" ~* '^[0-9]+(?:[.][0-9]+)?[[:space:]]+Ton\\y'
    )
    UPDATE "products" p
    SET
      "capacity" = inferred."title_tons"::text || ' Ton',
      "capacity_value" = inferred."title_tons",
      "updated_at" = now()
    FROM inferred
    WHERE p."id" = inferred."id"
      AND p."capacity_value" = inferred."title_tons"
      AND p."capacity" IS DISTINCT FROM inferred."title_tons"::text || ' Ton';
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql``);
}
