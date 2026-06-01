import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    UPDATE products
    SET
      seo_meta_title = 'Truck Mounted 18-Meter Aerial Work Platform - Buy Product on BOB-LIFT',
      seo_meta_description = 'Buy Product on BOB-LIFT: truck mounted 18-meter aerial work platform for elevated maintenance and project work.',
      updated_at = now()
    WHERE slug = 'truck-mounted-18-meter-aerial-work-platform';

    UPDATE products_locales
    SET
      seo_meta_title = 'Truck Mounted 18-Meter Aerial Work Platform - Buy Product on BOB-LIFT',
      seo_meta_description = 'Buy Product on BOB-LIFT: truck mounted 18-meter aerial work platform for elevated maintenance and project work.'
    WHERE _parent_id = (SELECT id FROM products WHERE slug = 'truck-mounted-18-meter-aerial-work-platform' LIMIT 1)
      AND _locale = 'en';
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    UPDATE products_locales
    SET seo_meta_title = 'Truck Mounted 18-Meter Aerial Work Platform', seo_meta_description = NULL
    WHERE _parent_id = (SELECT id FROM products WHERE slug = 'truck-mounted-18-meter-aerial-work-platform' LIMIT 1)
      AND _locale = 'en';
  `);
}
