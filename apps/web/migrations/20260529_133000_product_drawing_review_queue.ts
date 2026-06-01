import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    CREATE TABLE IF NOT EXISTS "product_drawing_reviews" (
      "id" serial PRIMARY KEY NOT NULL,
      "title" varchar NOT NULL,
      "review_status" varchar DEFAULT 'needs-review' NOT NULL,
      "parameter_match_status" varchar DEFAULT 'unchecked' NOT NULL,
      "product_id" integer NOT NULL,
      "drawing_row_id" varchar NOT NULL,
      "drawing_order" numeric,
      "product_title_snapshot" varchar,
      "product_model_snapshot" varchar,
      "specs_snapshot" varchar,
      "image_id" integer NOT NULL,
      "source_type" varchar,
      "source_url" varchar,
      "extracted_text" varchar,
      "review_notes" varchar,
      "review_method" varchar DEFAULT 'manual',
      "reviewer" varchar,
      "reviewed_at" timestamp(3) with time zone,
      "sync_status" varchar DEFAULT 'synced',
      "last_synced_at" timestamp(3) with time zone,
      "updated_at" timestamp(3) with time zone DEFAULT now() NOT NULL,
      "created_at" timestamp(3) with time zone DEFAULT now() NOT NULL,
      CONSTRAINT "product_drawing_reviews_drawing_row_id_unique" UNIQUE ("drawing_row_id")
    );

    CREATE INDEX IF NOT EXISTS "product_drawing_reviews_created_at_idx" ON "product_drawing_reviews" USING btree ("created_at");
    CREATE INDEX IF NOT EXISTS "product_drawing_reviews_updated_at_idx" ON "product_drawing_reviews" USING btree ("updated_at");
    CREATE INDEX IF NOT EXISTS "product_drawing_reviews_review_status_idx" ON "product_drawing_reviews" USING btree ("review_status");
    CREATE INDEX IF NOT EXISTS "product_drawing_reviews_parameter_match_status_idx" ON "product_drawing_reviews" USING btree ("parameter_match_status");
    CREATE INDEX IF NOT EXISTS "product_drawing_reviews_product_idx" ON "product_drawing_reviews" USING btree ("product_id");
    CREATE INDEX IF NOT EXISTS "product_drawing_reviews_image_idx" ON "product_drawing_reviews" USING btree ("image_id");

    DO $$ BEGIN
      IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'product_drawing_reviews_product_fk') THEN
        ALTER TABLE "product_drawing_reviews"
          ADD CONSTRAINT "product_drawing_reviews_product_fk"
          FOREIGN KEY ("product_id") REFERENCES "public"."products"("id") ON DELETE CASCADE ON UPDATE NO ACTION;
      END IF;
      IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'product_drawing_reviews_image_fk') THEN
        ALTER TABLE "product_drawing_reviews"
          ADD CONSTRAINT "product_drawing_reviews_image_fk"
          FOREIGN KEY ("image_id") REFERENCES "public"."media"("id") ON DELETE SET NULL ON UPDATE NO ACTION;
      END IF;
    END $$;

    ALTER TABLE "payload_locked_documents_rels" ADD COLUMN IF NOT EXISTS "product_drawing_reviews_id" integer;
    CREATE INDEX IF NOT EXISTS "payload_locked_documents_rels_product_drawing_reviews_id_idx" ON "payload_locked_documents_rels" USING btree ("product_drawing_reviews_id");
    DO $$ BEGIN
      IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'payload_locked_documents_rels_product_drawing_reviews_fk') THEN
        ALTER TABLE "payload_locked_documents_rels"
          ADD CONSTRAINT "payload_locked_documents_rels_product_drawing_reviews_fk"
          FOREIGN KEY ("product_drawing_reviews_id") REFERENCES "public"."product_drawing_reviews"("id") ON DELETE CASCADE ON UPDATE NO ACTION;
      END IF;
    END $$;

    INSERT INTO "product_drawing_reviews" (
      "title", "review_status", "parameter_match_status", "product_id", "drawing_row_id",
      "drawing_order", "product_title_snapshot", "product_model_snapshot", "specs_snapshot",
      "image_id", "source_type", "source_url", "extracted_text", "review_notes",
      "review_method", "reviewer", "reviewed_at", "sync_status", "last_synced_at",
      "updated_at", "created_at"
    )
    SELECT
      coalesce(nullif(pl.title, ''), p.slug, 'Product ' || p.id::text) || ' - Drawing Image ' || (pdi._order + 1)::text,
      coalesce(pdi.review_status, 'needs-review'),
      coalesce(pdi.parameter_match_status, 'unchecked'),
      p.id,
      pdi.id,
      pdi._order,
      coalesce(nullif(pl.title, ''), p.slug, 'Product ' || p.id::text),
      p.model,
      specs.specs_snapshot,
      pdi.image_id,
      pdi.source_type,
      pdi.source_url,
      pdi.extracted_text,
      pdi.review_notes,
      coalesce(pdi.review_method, 'manual'),
      pdi.reviewer,
      pdi.reviewed_at,
      'synced',
      now(),
      now(),
      now()
    FROM products_drawing_images pdi
    JOIN products p ON p.id = pdi._parent_id
    LEFT JOIN products_locales pl ON pl._parent_id = p.id AND pl._locale = 'en'
    LEFT JOIN LATERAL (
      SELECT string_agg(coalesce(psl.label, '') || ': ' || coalesce(psl.value, ''), E'\n' ORDER BY ps._order) AS specs_snapshot
      FROM products_specs ps
      LEFT JOIN products_specs_locales psl ON psl._parent_id = ps.id AND psl._locale = 'en'
      WHERE ps._parent_id = p.id
    ) specs ON true
    ON CONFLICT ("drawing_row_id") DO UPDATE SET
      "title" = EXCLUDED."title",
      "product_id" = EXCLUDED."product_id",
      "drawing_order" = EXCLUDED."drawing_order",
      "product_title_snapshot" = EXCLUDED."product_title_snapshot",
      "product_model_snapshot" = EXCLUDED."product_model_snapshot",
      "specs_snapshot" = EXCLUDED."specs_snapshot",
      "image_id" = EXCLUDED."image_id",
      "source_type" = EXCLUDED."source_type",
      "source_url" = EXCLUDED."source_url",
      "sync_status" = 'synced',
      "last_synced_at" = now(),
      "updated_at" = now();
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "payload_locked_documents_rels" DROP CONSTRAINT IF EXISTS "payload_locked_documents_rels_product_drawing_reviews_fk";
    DROP INDEX IF EXISTS "payload_locked_documents_rels_product_drawing_reviews_id_idx";
    ALTER TABLE "payload_locked_documents_rels" DROP COLUMN IF EXISTS "product_drawing_reviews_id";
    DROP TABLE IF EXISTS "product_drawing_reviews";
  `);
}
