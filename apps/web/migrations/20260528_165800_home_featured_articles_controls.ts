import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "home_settings_rels" ADD COLUMN "articles_id" integer;
    ALTER TABLE "home_settings_rels" ADD CONSTRAINT "home_settings_rels_articles_fk" FOREIGN KEY ("articles_id") REFERENCES "articles"("id") ON DELETE cascade ON UPDATE no action;
    CREATE INDEX "home_settings_rels_articles_id_idx" ON "home_settings_rels" USING btree ("articles_id");
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    DROP INDEX "home_settings_rels_articles_id_idx";
    ALTER TABLE "home_settings_rels" DROP CONSTRAINT "home_settings_rels_articles_fk";
    ALTER TABLE "home_settings_rels" DROP COLUMN "articles_id";
  `);
}
