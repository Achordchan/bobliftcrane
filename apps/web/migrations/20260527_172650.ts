import { MigrateUpArgs, MigrateDownArgs, sql } from '@payloadcms/db-postgres'

export async function up({ db, payload, req }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
   CREATE TABLE "products_detail_hotspots" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL
  );

  CREATE TABLE "products_detail_hotspots_locales" (
	"label" varchar NOT NULL,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  ALTER TABLE "products_detail_hotspots" ADD CONSTRAINT "products_detail_hotspots_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."products"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "products_detail_hotspots_locales" ADD CONSTRAINT "products_detail_hotspots_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."products_detail_hotspots"("id") ON DELETE cascade ON UPDATE no action;
  CREATE INDEX "products_detail_hotspots_order_idx" ON "products_detail_hotspots" USING btree ("_order");
  CREATE INDEX "products_detail_hotspots_parent_id_idx" ON "products_detail_hotspots" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "products_detail_hotspots_locales_locale_parent_id_unique" ON "products_detail_hotspots_locales" USING btree ("_locale","_parent_id");
  UPDATE "products"
  SET "parameter_review_status"='in-review', "import_batch_id"=COALESCE("import_batch_id", 'legacy-before-20260527-172650'), "updated_at"=now()
  WHERE "import_source"='legacy-site' AND "parameter_review_status"='verified-from-source' AND "parameter_reviewed_at" IS NULL;
  UPDATE "products_specs_locales"
  SET "source_note"='Parsed from old-site specification text: ' || "products"."source_url"
  FROM "products_specs"
  INNER JOIN "products" ON "products"."id"="products_specs"."_parent_id"
  WHERE "products_specs_locales"."_parent_id"="products_specs"."id"
    AND "products"."import_source"='legacy-site'
    AND "products"."source_url" IS NOT NULL
    AND "products_specs_locales"."source_note" IS NULL;`)
}

export async function down({ db, payload, req }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
   DROP TABLE "products_detail_hotspots" CASCADE;
  DROP TABLE "products_detail_hotspots_locales" CASCADE;`)
}
