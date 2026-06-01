import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    DELETE FROM "home_settings_rels" WHERE "path" = 'homeFeaturedProducts';

    DROP INDEX IF EXISTS "home_settings_rels_products_id_idx";
    ALTER TABLE "home_settings_rels" DROP CONSTRAINT IF EXISTS "home_settings_rels_products_fk";
    ALTER TABLE "home_settings_rels" DROP COLUMN IF EXISTS "products_id";

    ALTER TABLE "home_settings"
      DROP COLUMN IF EXISTS "home_section_visibility_featured_products",
      DROP COLUMN IF EXISTS "home_product_section_featured_cta_href",
      DROP COLUMN IF EXISTS "home_product_section_max_featured_products",
      DROP COLUMN IF EXISTS "home_product_section_show_featured_product_specs",
      DROP COLUMN IF EXISTS "home_product_section_show_featured_cta",
      DROP COLUMN IF EXISTS "home_product_section_show_featured_product_model";

    ALTER TABLE "home_settings_locales"
      DROP COLUMN IF EXISTS "home_product_section_featured_label",
      DROP COLUMN IF EXISTS "home_product_section_featured_title",
      DROP COLUMN IF EXISTS "home_product_section_featured_cta_label",
      DROP COLUMN IF EXISTS "home_product_section_featured_capacity_label",
      DROP COLUMN IF EXISTS "home_product_section_featured_type_label";
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "home_settings"
      ADD COLUMN IF NOT EXISTS "home_section_visibility_featured_products" boolean DEFAULT true,
      ADD COLUMN IF NOT EXISTS "home_product_section_featured_cta_href" varchar DEFAULT '/products',
      ADD COLUMN IF NOT EXISTS "home_product_section_max_featured_products" numeric DEFAULT 8,
      ADD COLUMN IF NOT EXISTS "home_product_section_show_featured_product_specs" boolean DEFAULT true,
      ADD COLUMN IF NOT EXISTS "home_product_section_show_featured_cta" boolean DEFAULT true,
      ADD COLUMN IF NOT EXISTS "home_product_section_show_featured_product_model" boolean DEFAULT true;

    ALTER TABLE "home_settings_locales"
      ADD COLUMN IF NOT EXISTS "home_product_section_featured_label" varchar DEFAULT 'Featured Products',
      ADD COLUMN IF NOT EXISTS "home_product_section_featured_title" varchar DEFAULT 'High Performance Cranes. Built for Extreme Demands.',
      ADD COLUMN IF NOT EXISTS "home_product_section_featured_cta_label" varchar DEFAULT 'View All Products',
      ADD COLUMN IF NOT EXISTS "home_product_section_featured_capacity_label" varchar DEFAULT 'Capacity',
      ADD COLUMN IF NOT EXISTS "home_product_section_featured_type_label" varchar DEFAULT 'Type';

    ALTER TABLE "home_settings_rels" ADD COLUMN IF NOT EXISTS "products_id" integer;
    CREATE INDEX IF NOT EXISTS "home_settings_rels_products_id_idx" ON "home_settings_rels" USING btree ("products_id");

    DO $$
    BEGIN
      IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'home_settings_rels_products_fk'
      ) THEN
        ALTER TABLE "home_settings_rels"
          ADD CONSTRAINT "home_settings_rels_products_fk"
          FOREIGN KEY ("products_id") REFERENCES "products"("id") ON DELETE CASCADE;
      END IF;
    END $$;
  `);
}
