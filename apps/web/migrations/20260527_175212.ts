import { MigrateUpArgs, MigrateDownArgs, sql } from '@payloadcms/db-postgres'

export async function up({ db, payload, req }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
   CREATE TYPE "public"."enum_inquiries_priority" AS ENUM('normal', 'high', 'urgent');
  CREATE TABLE "inquiries_follow_up_notes" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"note" varchar NOT NULL,
	"created_at" timestamp(3) with time zone
  );

  ALTER TABLE "inquiries" ADD COLUMN "product_id" integer;
  ALTER TABLE "inquiries" ADD COLUMN "priority" "enum_inquiries_priority" DEFAULT 'normal' NOT NULL;
  ALTER TABLE "inquiries" ADD COLUMN "source_path" varchar;
  ALTER TABLE "inquiries" ADD COLUMN "locale" varchar;
  ALTER TABLE "inquiries" ADD COLUMN "utm_source" varchar;
  ALTER TABLE "inquiries" ADD COLUMN "utm_medium" varchar;
  ALTER TABLE "inquiries" ADD COLUMN "utm_campaign" varchar;
  ALTER TABLE "inquiries" ADD COLUMN "utm_term" varchar;
  ALTER TABLE "inquiries" ADD COLUMN "utm_content" varchar;
  ALTER TABLE "inquiries" ADD COLUMN "ip_address" varchar;
  ALTER TABLE "inquiries" ADD COLUMN "assignee_id" integer;
  ALTER TABLE "inquiries" ADD COLUMN "follow_up_at" timestamp(3) with time zone;
  ALTER TABLE "inquiries_follow_up_notes" ADD CONSTRAINT "inquiries_follow_up_notes_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."inquiries"("id") ON DELETE cascade ON UPDATE no action;
  CREATE INDEX "inquiries_follow_up_notes_order_idx" ON "inquiries_follow_up_notes" USING btree ("_order");
  CREATE INDEX "inquiries_follow_up_notes_parent_id_idx" ON "inquiries_follow_up_notes" USING btree ("_parent_id");
  ALTER TABLE "inquiries" ADD CONSTRAINT "inquiries_product_id_products_id_fk" FOREIGN KEY ("product_id") REFERENCES "public"."products"("id") ON DELETE set null ON UPDATE no action;
  ALTER TABLE "inquiries" ADD CONSTRAINT "inquiries_assignee_id_users_id_fk" FOREIGN KEY ("assignee_id") REFERENCES "public"."users"("id") ON DELETE set null ON UPDATE no action;
  CREATE INDEX "inquiries_product_idx" ON "inquiries" USING btree ("product_id");
  CREATE INDEX "inquiries_assignee_idx" ON "inquiries" USING btree ("assignee_id");`)
}

export async function down({ db, payload, req }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
   ALTER TABLE "inquiries_follow_up_notes" DISABLE ROW LEVEL SECURITY;
  DROP TABLE "inquiries_follow_up_notes" CASCADE;
  ALTER TABLE "inquiries" DROP CONSTRAINT "inquiries_product_id_products_id_fk";

  ALTER TABLE "inquiries" DROP CONSTRAINT "inquiries_assignee_id_users_id_fk";

  DROP INDEX "inquiries_product_idx";
  DROP INDEX "inquiries_assignee_idx";
  ALTER TABLE "inquiries" DROP COLUMN "product_id";
  ALTER TABLE "inquiries" DROP COLUMN "priority";
  ALTER TABLE "inquiries" DROP COLUMN "source_path";
  ALTER TABLE "inquiries" DROP COLUMN "locale";
  ALTER TABLE "inquiries" DROP COLUMN "utm_source";
  ALTER TABLE "inquiries" DROP COLUMN "utm_medium";
  ALTER TABLE "inquiries" DROP COLUMN "utm_campaign";
  ALTER TABLE "inquiries" DROP COLUMN "utm_term";
  ALTER TABLE "inquiries" DROP COLUMN "utm_content";
  ALTER TABLE "inquiries" DROP COLUMN "ip_address";
  ALTER TABLE "inquiries" DROP COLUMN "assignee_id";
  ALTER TABLE "inquiries" DROP COLUMN "follow_up_at";
  DROP TYPE "public"."enum_inquiries_priority";`)
}
