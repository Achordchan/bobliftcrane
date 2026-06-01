import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

const arrayTables = [
  'product_categories_default_category_filters',
  'product_categories_default_capacity_filters',
  'product_categories_default_boom_type_filters',
  'product_categories_default_application_filters',
  'product_categories_default_availability_filters',
];

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "product_categories" ADD COLUMN IF NOT EXISTS "show_in_product_filters" boolean DEFAULT true;
  `);

  for (const table of arrayTables) {
    await db.execute(sql`
      CREATE TABLE IF NOT EXISTS ${sql.identifier(table)} (
        "order" integer NOT NULL,
        "parent_id" integer NOT NULL,
        "value" varchar,
        "id" serial PRIMARY KEY
      );
      CREATE INDEX IF NOT EXISTS ${sql.identifier(`${table}_order_idx`)} ON ${sql.identifier(table)} USING btree ("order");
      CREATE INDEX IF NOT EXISTS ${sql.identifier(`${table}_parent_idx`)} ON ${sql.identifier(table)} USING btree ("parent_id");
    `);
    await db.execute(sql.raw(`
      DO $$
      BEGIN
        IF NOT EXISTS (
          SELECT 1 FROM pg_constraint WHERE conname = '${table}_parent_fk'
        ) THEN
          ALTER TABLE "${table}"
          ADD CONSTRAINT "${table}_parent_fk"
          FOREIGN KEY ("parent_id") REFERENCES "public"."product_categories"("id")
          ON DELETE CASCADE ON UPDATE NO ACTION;
        END IF;
      END $$;
    `));
  }
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  for (const table of [...arrayTables].reverse()) {
    await db.execute(sql`DROP TABLE IF EXISTS ${sql.identifier(table)} CASCADE;`);
  }

  await db.execute(sql`
    ALTER TABLE "product_categories" DROP COLUMN IF EXISTS "show_in_product_filters";
  `);
}
