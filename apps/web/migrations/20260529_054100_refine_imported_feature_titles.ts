import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    UPDATE products_key_features_locales
    SET title = 'Reinforced boom interface'
    WHERE _locale = 'en'
      AND text ILIKE ANY (ARRAY['%steel plate%', '%reinforced%', '%interface%']);
  `);
}

export async function down({ db: _db }: MigrateDownArgs): Promise<void> {
  // Data cleanup only. Do not restore less specific imported feature titles.
}
