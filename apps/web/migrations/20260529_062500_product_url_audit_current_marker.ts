import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "product_url_audits" ADD COLUMN IF NOT EXISTS "is_current" boolean DEFAULT true;
    ALTER TABLE "product_url_audits" ADD COLUMN IF NOT EXISTS "superseded_at" timestamp(3) with time zone;

    WITH current_audit AS (
      SELECT "id"
      FROM "product_url_audits"
      ORDER BY ("status" = 'completed') DESC, "updated_at" DESC, "id" DESC
      LIMIT 1
    )
    UPDATE "product_url_audits"
    SET
      "is_current" = "product_url_audits"."id" = (SELECT "id" FROM current_audit),
      "superseded_at" = CASE
        WHEN "product_url_audits"."id" = (SELECT "id" FROM current_audit) THEN NULL
        ELSE COALESCE("superseded_at", now())
      END;
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "product_url_audits" DROP COLUMN IF EXISTS "superseded_at";
    ALTER TABLE "product_url_audits" DROP COLUMN IF EXISTS "is_current";
  `);
}
