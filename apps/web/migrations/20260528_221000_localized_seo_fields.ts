import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "products_locales" ADD COLUMN IF NOT EXISTS "seo_meta_title" varchar;
    ALTER TABLE "products_locales" ADD COLUMN IF NOT EXISTS "seo_meta_description" varchar;
    ALTER TABLE "product_categories_locales" ADD COLUMN IF NOT EXISTS "seo_meta_title" varchar;
    ALTER TABLE "product_categories_locales" ADD COLUMN IF NOT EXISTS "seo_meta_description" varchar;
    ALTER TABLE "articles_locales" ADD COLUMN IF NOT EXISTS "seo_meta_title" varchar;
    ALTER TABLE "articles_locales" ADD COLUMN IF NOT EXISTS "seo_meta_description" varchar;
    ALTER TABLE "cases_locales" ADD COLUMN IF NOT EXISTS "seo_meta_title" varchar;
    ALTER TABLE "cases_locales" ADD COLUMN IF NOT EXISTS "seo_meta_description" varchar;

    UPDATE "products_locales" l
    SET "seo_meta_title"=p."seo_meta_title",
        "seo_meta_description"=p."seo_meta_description"
    FROM "products" p
    WHERE l."_parent_id"=p."id"
      AND l."_locale"='en'
      AND (l."seo_meta_title" IS NULL OR l."seo_meta_title"='')
      AND (p."seo_meta_title" IS NOT NULL OR p."seo_meta_description" IS NOT NULL);

    UPDATE "product_categories_locales" l
    SET "seo_meta_title"=c."seo_meta_title",
        "seo_meta_description"=c."seo_meta_description"
    FROM "product_categories" c
    WHERE l."_parent_id"=c."id"
      AND l."_locale"='en'
      AND (l."seo_meta_title" IS NULL OR l."seo_meta_title"='')
      AND (c."seo_meta_title" IS NOT NULL OR c."seo_meta_description" IS NOT NULL);

    UPDATE "articles_locales" l
    SET "seo_meta_title"=a."seo_meta_title",
        "seo_meta_description"=a."seo_meta_description"
    FROM "articles" a
    WHERE l."_parent_id"=a."id"
      AND l."_locale"='en'
      AND (l."seo_meta_title" IS NULL OR l."seo_meta_title"='')
      AND (a."seo_meta_title" IS NOT NULL OR a."seo_meta_description" IS NOT NULL);

    UPDATE "cases_locales" l
    SET "seo_meta_title"=c."seo_meta_title",
        "seo_meta_description"=c."seo_meta_description"
    FROM "cases" c
    WHERE l."_parent_id"=c."id"
      AND l."_locale"='en'
      AND (l."seo_meta_title" IS NULL OR l."seo_meta_title"='')
      AND (c."seo_meta_title" IS NOT NULL OR c."seo_meta_description" IS NOT NULL);
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    ALTER TABLE "products_locales" DROP COLUMN IF EXISTS "seo_meta_title";
    ALTER TABLE "products_locales" DROP COLUMN IF EXISTS "seo_meta_description";
    ALTER TABLE "product_categories_locales" DROP COLUMN IF EXISTS "seo_meta_title";
    ALTER TABLE "product_categories_locales" DROP COLUMN IF EXISTS "seo_meta_description";
    ALTER TABLE "articles_locales" DROP COLUMN IF EXISTS "seo_meta_title";
    ALTER TABLE "articles_locales" DROP COLUMN IF EXISTS "seo_meta_description";
    ALTER TABLE "cases_locales" DROP COLUMN IF EXISTS "seo_meta_title";
    ALTER TABLE "cases_locales" DROP COLUMN IF EXISTS "seo_meta_description";
  `);
}
