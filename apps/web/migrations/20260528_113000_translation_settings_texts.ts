import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    CREATE TABLE IF NOT EXISTS "translation_settings_texts" (
      "id" serial PRIMARY KEY NOT NULL,
      "order" integer NOT NULL,
      "parent_id" integer NOT NULL,
      "path" varchar NOT NULL,
      "text" varchar
    );

    DO $$ BEGIN
      ALTER TABLE "translation_settings_texts" ADD CONSTRAINT "translation_settings_texts_parent_fk" FOREIGN KEY ("parent_id") REFERENCES "public"."translation_settings"("id") ON DELETE cascade ON UPDATE no action;
    EXCEPTION
      WHEN duplicate_object THEN null;
    END $$;

    CREATE INDEX IF NOT EXISTS "translation_settings_texts_order_parent_idx" ON "translation_settings_texts" USING btree ("order", "parent_id");
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    DROP TABLE IF EXISTS "translation_settings_texts" CASCADE;
  `);
}
