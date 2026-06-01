import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    ALTER TYPE "public"."enum_product_import_batches_status" ADD VALUE IF NOT EXISTS 'queued' BEFORE 'in-review';
    CREATE TYPE "public"."enum_product_import_batches_run_action" AS ENUM('source-audit', 'import-and-source-audit', 'dry-run-import');

    ALTER TABLE "product_import_batches"
      ADD COLUMN IF NOT EXISTS "run_action" "enum_product_import_batches_run_action" DEFAULT 'source-audit' NOT NULL,
      ADD COLUMN IF NOT EXISTS "report_path" varchar,
      ADD COLUMN IF NOT EXISTS "summary" varchar,
      ADD COLUMN IF NOT EXISTS "error_message" varchar;
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "product_import_batches"
      DROP COLUMN IF EXISTS "run_action",
      DROP COLUMN IF EXISTS "report_path",
      DROP COLUMN IF EXISTS "summary",
      DROP COLUMN IF EXISTS "error_message";
    DROP TYPE IF EXISTS "public"."enum_product_import_batches_run_action";
  `);
}
