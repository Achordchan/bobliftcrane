import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

const obsoleteArrayTables = [
  'product_categories_default_category_filters',
  'product_categories_default_capacity_filters',
  'product_categories_default_boom_type_filters',
  'product_categories_default_application_filters',
  'product_categories_default_availability_filters',
];

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    CREATE TABLE IF NOT EXISTS "product_categories_texts" (
      "id" serial PRIMARY KEY,
      "order" integer NOT NULL,
      "parent_id" integer NOT NULL,
      "path" varchar NOT NULL,
      "text" varchar
    );
    CREATE INDEX IF NOT EXISTS "product_categories_texts_order_parent_idx" ON "product_categories_texts" USING btree ("order", "parent_id");
    CREATE INDEX IF NOT EXISTS "product_categories_texts_path_parent_idx" ON "product_categories_texts" USING btree ("path", "parent_id");
  `);

  await db.execute(sql`
    DO $$
    BEGIN
      IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'product_categories_texts_parent_fk'
      ) THEN
        ALTER TABLE "product_categories_texts"
        ADD CONSTRAINT "product_categories_texts_parent_fk"
        FOREIGN KEY ("parent_id") REFERENCES "public"."product_categories"("id")
        ON DELETE CASCADE ON UPDATE NO ACTION;
      END IF;
    END $$;
  `);

  for (const table of obsoleteArrayTables) {
    await db.execute(sql.raw(`DROP TABLE IF EXISTS "${table}" CASCADE;`));
  }
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`DROP TABLE IF EXISTS "product_categories_texts" CASCADE;`);
}
