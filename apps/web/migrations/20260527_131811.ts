import { MigrateUpArgs, MigrateDownArgs, sql } from '@payloadcms/db-postgres'

export async function up({ db, payload, req }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
   CREATE TYPE "public"."enum_inquiries_status" AS ENUM('new', 'in-progress', 'replied', 'closed');
  CREATE TABLE "cases_facts" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL
  );

  CREATE TABLE "cases_facts_locales" (
	"label" varchar NOT NULL,
	"value" varchar NOT NULL,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "cases_sections" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL
  );

  CREATE TABLE "cases_sections_locales" (
	"heading" varchar NOT NULL,
	"body" varchar NOT NULL,
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  CREATE TABLE "inquiries" (
	"id" serial PRIMARY KEY NOT NULL,
	"name" varchar NOT NULL,
	"email" varchar NOT NULL,
	"phone" varchar,
	"country" varchar,
	"product_interest" varchar,
	"quantity" varchar,
	"message" varchar,
	"status" "enum_inquiries_status" DEFAULT 'new' NOT NULL,
	"source_page" varchar,
	"submitted_at" timestamp(3) with time zone NOT NULL,
	"user_agent" varchar,
	"updated_at" timestamp(3) with time zone DEFAULT now() NOT NULL,
	"created_at" timestamp(3) with time zone DEFAULT now() NOT NULL
  );

  ALTER TABLE "cases" ADD COLUMN "content_html" varchar;
  ALTER TABLE "cases_locales" ADD COLUMN "case_type" varchar;
  ALTER TABLE "payload_locked_documents_rels" ADD COLUMN "inquiries_id" integer;
  ALTER TABLE "cases_facts" ADD CONSTRAINT "cases_facts_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."cases"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "cases_facts_locales" ADD CONSTRAINT "cases_facts_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."cases_facts"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "cases_sections" ADD CONSTRAINT "cases_sections_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."cases"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "cases_sections_locales" ADD CONSTRAINT "cases_sections_locales_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."cases_sections"("id") ON DELETE cascade ON UPDATE no action;
  CREATE INDEX "cases_facts_order_idx" ON "cases_facts" USING btree ("_order");
  CREATE INDEX "cases_facts_parent_id_idx" ON "cases_facts" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "cases_facts_locales_locale_parent_id_unique" ON "cases_facts_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "cases_sections_order_idx" ON "cases_sections" USING btree ("_order");
  CREATE INDEX "cases_sections_parent_id_idx" ON "cases_sections" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "cases_sections_locales_locale_parent_id_unique" ON "cases_sections_locales" USING btree ("_locale","_parent_id");
  CREATE INDEX "inquiries_updated_at_idx" ON "inquiries" USING btree ("updated_at");
  CREATE INDEX "inquiries_created_at_idx" ON "inquiries" USING btree ("created_at");
  ALTER TABLE "payload_locked_documents_rels" ADD CONSTRAINT "payload_locked_documents_rels_inquiries_fk" FOREIGN KEY ("inquiries_id") REFERENCES "public"."inquiries"("id") ON DELETE cascade ON UPDATE no action;
  CREATE INDEX "payload_locked_documents_rels_inquiries_id_idx" ON "payload_locked_documents_rels" USING btree ("inquiries_id");`)
}

export async function down({ db, payload, req }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
   ALTER TABLE "cases_facts" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "cases_facts_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "cases_sections" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "cases_sections_locales" DISABLE ROW LEVEL SECURITY;
  ALTER TABLE "inquiries" DISABLE ROW LEVEL SECURITY;
  DROP TABLE "cases_facts" CASCADE;
  DROP TABLE "cases_facts_locales" CASCADE;
  DROP TABLE "cases_sections" CASCADE;
  DROP TABLE "cases_sections_locales" CASCADE;
  DROP TABLE "inquiries" CASCADE;
  ALTER TABLE "payload_locked_documents_rels" DROP CONSTRAINT "payload_locked_documents_rels_inquiries_fk";

  DROP INDEX "payload_locked_documents_rels_inquiries_id_idx";
  ALTER TABLE "cases" DROP COLUMN "content_html";
  ALTER TABLE "cases_locales" DROP COLUMN "case_type";
  ALTER TABLE "payload_locked_documents_rels" DROP COLUMN "inquiries_id";
  DROP TYPE "public"."enum_inquiries_status";`)
}
