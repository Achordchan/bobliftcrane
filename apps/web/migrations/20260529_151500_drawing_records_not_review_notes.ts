import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    UPDATE "products_locales"
    SET "parameter_review_notes" = replace(
      "parameter_review_notes",
      'Parameters are parsed from original text and must be checked against source/drawing images before marking as verified.',
      'Parameters are parsed from original source text and must be checked against the old-site source page before marking as verified. Drawing images are stored as supporting material only.'
    )
    WHERE "parameter_review_notes" LIKE '%source/drawing images%';

  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    UPDATE "products_locales"
    SET "parameter_review_notes" = replace(
      "parameter_review_notes",
      'Parameters are parsed from original source text and must be checked against the old-site source page before marking as verified. Drawing images are stored as supporting material only.',
      'Parameters are parsed from original text and must be checked against source/drawing images before marking as verified.'
    )
    WHERE "parameter_review_notes" LIKE '%Drawing images are stored as supporting material only.%';

  `);
}
