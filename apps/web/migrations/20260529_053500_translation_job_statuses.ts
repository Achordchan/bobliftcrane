import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    ALTER TYPE "public"."enum_translation_jobs_status" ADD VALUE IF NOT EXISTS 'needs-credentials' AFTER 'draft';
    ALTER TYPE "public"."enum_translation_jobs_status" ADD VALUE IF NOT EXISTS 'coverage-needed' AFTER 'needs-credentials';
  `);
}

export async function down({ db: _db }: MigrateDownArgs): Promise<void> {
  // PostgreSQL 不能安全删除 enum value；回滚时保留新增状态值。
}
