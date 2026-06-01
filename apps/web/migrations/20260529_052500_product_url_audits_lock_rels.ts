import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "payload_locked_documents_rels"
      ADD COLUMN IF NOT EXISTS "product_url_audits_id" integer;

    CREATE INDEX IF NOT EXISTS "payload_locked_documents_rels_product_url_audits_id_idx"
      ON "payload_locked_documents_rels" USING btree ("product_url_audits_id");

    ALTER TABLE "payload_locked_documents_rels"
      ADD CONSTRAINT "payload_locked_documents_rels_product_url_audits_fk"
      FOREIGN KEY ("product_url_audits_id") REFERENCES "public"."product_url_audits"("id")
      ON DELETE CASCADE ON UPDATE NO ACTION;
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "payload_locked_documents_rels"
      DROP CONSTRAINT IF EXISTS "payload_locked_documents_rels_product_url_audits_fk";
    DROP INDEX IF EXISTS "payload_locked_documents_rels_product_url_audits_id_idx";
    ALTER TABLE "payload_locked_documents_rels"
      DROP COLUMN IF EXISTS "product_url_audits_id";
  `);
}
