import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    CREATE TYPE "public"."enum_product_url_audits_status" AS ENUM('draft', 'queued', 'running', 'completed', 'failed');

    CREATE TABLE "product_url_audits" (
      "id" serial PRIMARY KEY NOT NULL,
      "title" varchar DEFAULT '旧站 URL 直出验收' NOT NULL,
      "status" "enum_product_url_audits_status" DEFAULT 'draft' NOT NULL,
      "base_url" varchar DEFAULT 'http://localhost:3000' NOT NULL,
      "source_csv_path" varchar DEFAULT 'audit/url-preservation-map.csv',
      "report_path" varchar,
      "total_urls" numeric DEFAULT 0,
      "direct_render_count" numeric DEFAULT 0,
      "redirect_count" numeric DEFAULT 0,
      "failed_count" numeric DEFAULT 0,
      "last_run_at" timestamp(3) with time zone,
      "completed_at" timestamp(3) with time zone,
      "summary" varchar,
      "error_message" varchar,
      "updated_at" timestamp(3) with time zone DEFAULT now() NOT NULL,
      "created_at" timestamp(3) with time zone DEFAULT now() NOT NULL
    );

    CREATE TABLE "product_url_audits_results" (
      "_order" integer NOT NULL,
      "_parent_id" integer NOT NULL,
      "id" varchar PRIMARY KEY NOT NULL,
      "path" varchar,
      "type" varchar,
      "expected_action" varchar,
      "local_status" varchar,
      "local_location" varchar,
      "local_title" varchar,
      "direct_render" boolean,
      "ok" boolean
    );

    CREATE INDEX "product_url_audits_created_at_idx" ON "product_url_audits" USING btree ("created_at");
    CREATE INDEX "product_url_audits_updated_at_idx" ON "product_url_audits" USING btree ("updated_at");
    CREATE INDEX "product_url_audits_results_order_idx" ON "product_url_audits_results" USING btree ("_order");
    CREATE INDEX "product_url_audits_results_parent_id_idx" ON "product_url_audits_results" USING btree ("_parent_id");

    ALTER TABLE "product_url_audits_results"
      ADD CONSTRAINT "product_url_audits_results_parent_id_fk"
      FOREIGN KEY ("_parent_id") REFERENCES "public"."product_url_audits"("id") ON DELETE CASCADE ON UPDATE NO ACTION;
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "product_url_audits_results" DROP CONSTRAINT IF EXISTS "product_url_audits_results_parent_id_fk";
    DROP TABLE IF EXISTS "product_url_audits_results";
    DROP TABLE IF EXISTS "product_url_audits";
    DROP TYPE IF EXISTS "public"."enum_product_url_audits_status";
  `);
}
