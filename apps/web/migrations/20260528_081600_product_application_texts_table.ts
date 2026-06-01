import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    CREATE TABLE "products_texts" (
      "id" serial PRIMARY KEY NOT NULL,
      "order" integer NOT NULL,
      "parent_id" integer NOT NULL,
      "path" varchar NOT NULL,
      "text" varchar
    );

    ALTER TABLE "products_texts" ADD CONSTRAINT "products_texts_parent_id_products_id_fk" FOREIGN KEY ("parent_id") REFERENCES "public"."products"("id") ON DELETE cascade ON UPDATE no action;
    CREATE INDEX "products_texts_order_parent_idx" ON "products_texts" USING btree ("order","parent_id");

    INSERT INTO "products_texts" ("order", "parent_id", "path", "text")
    SELECT "order", "parent_id", 'applications', "value"::text
    FROM "products_applications"
    WHERE "value" IS NOT NULL;
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    DROP INDEX "products_texts_order_parent_idx";
    ALTER TABLE "products_texts" DROP CONSTRAINT "products_texts_parent_id_products_id_fk";
    DROP TABLE "products_texts";
  `);
}
