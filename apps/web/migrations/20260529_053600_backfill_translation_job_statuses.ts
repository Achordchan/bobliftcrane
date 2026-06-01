import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    UPDATE translation_jobs
    SET
      status = 'needs-credentials',
      updated_at = now()
    WHERE status = 'queued'
      AND dry_run IS TRUE
      AND coalesce(error_message, '') ILIKE '%DEEPL_AUTH_KEY%';
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    UPDATE translation_jobs
    SET
      status = 'queued',
      updated_at = now()
    WHERE status = 'needs-credentials'
      AND dry_run IS TRUE
      AND coalesce(error_message, '') ILIKE '%DEEPL_AUTH_KEY%';
  `);
}
