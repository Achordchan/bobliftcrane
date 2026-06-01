import { MigrateUpArgs, MigrateDownArgs, sql } from '@payloadcms/db-postgres'

export async function up({ db, payload, req }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
   ALTER TABLE "products_documents" ALTER COLUMN "url" DROP NOT NULL;
  ALTER TABLE "products_documents" ADD COLUMN "file_id" integer;
  ALTER TABLE "products_documents" ADD CONSTRAINT "products_documents_file_id_media_id_fk" FOREIGN KEY ("file_id") REFERENCES "public"."media"("id") ON DELETE set null ON UPDATE no action;
  CREATE INDEX "products_documents_file_idx" ON "products_documents" USING btree ("file_id");`)
}

export async function down({ db, payload, req }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
   ALTER TABLE "products_documents" DROP CONSTRAINT "products_documents_file_id_media_id_fk";

  DROP INDEX "products_documents_file_idx";
  ALTER TABLE "products_documents" ALTER COLUMN "url" SET NOT NULL;
  ALTER TABLE "products_documents" DROP COLUMN "file_id";`)
}
