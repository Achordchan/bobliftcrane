import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    ALTER TYPE "public"."enum_product_url_audits_status" ADD VALUE IF NOT EXISTS 'content-mismatch';

    ALTER TABLE "product_url_audits" ADD COLUMN IF NOT EXISTS "content_match_count" numeric DEFAULT 0;
    ALTER TABLE "product_url_audits" ADD COLUMN IF NOT EXISTS "content_mismatch_count" numeric DEFAULT 0;
    ALTER TABLE "product_url_audits" ADD COLUMN IF NOT EXISTS "compatible_fallback_count" numeric DEFAULT 0;

    ALTER TABLE "product_url_audits_results" ADD COLUMN IF NOT EXISTS "expected_title" varchar;
    ALTER TABLE "product_url_audits_results" ADD COLUMN IF NOT EXISTS "content_match" boolean;
    ALTER TABLE "product_url_audits_results" ADD COLUMN IF NOT EXISTS "compatible_fallback" boolean;
    ALTER TABLE "product_url_audits_results" ADD COLUMN IF NOT EXISTS "content_match_note" varchar;

    UPDATE "product_url_audits"
    SET
      "content_match_count" = COALESCE("direct_render_count", 0),
      "content_mismatch_count" = 0,
      "compatible_fallback_count" = 0
    WHERE "content_match_count" IS NULL OR "content_match_count" = 0;
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "product_url_audits_results" DROP COLUMN IF EXISTS "content_match_note";
    ALTER TABLE "product_url_audits_results" DROP COLUMN IF EXISTS "compatible_fallback";
    ALTER TABLE "product_url_audits_results" DROP COLUMN IF EXISTS "content_match";
    ALTER TABLE "product_url_audits_results" DROP COLUMN IF EXISTS "expected_title";

    ALTER TABLE "product_url_audits" DROP COLUMN IF EXISTS "compatible_fallback_count";
    ALTER TABLE "product_url_audits" DROP COLUMN IF EXISTS "content_mismatch_count";
    ALTER TABLE "product_url_audits" DROP COLUMN IF EXISTS "content_match_count";
  `);
}
