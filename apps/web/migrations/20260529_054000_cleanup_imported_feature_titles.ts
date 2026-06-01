import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    UPDATE products_key_features_locales AS locale
    SET
      title = CASE
        WHEN locale.text ILIKE '%telescopic%' OR locale.text ILIKE '%cylinder%' OR locale.text ILIKE '%boom extension%' THEN 'Telescopic boom design'
        WHEN locale.text ILIKE '%hexagon%' OR locale.text ILIKE '%pentagon%' OR locale.text ILIKE '%anti-bending%' OR locale.text ILIKE '%bending%' THEN 'Stable boom structure'
        WHEN locale.text ILIKE '%steel plate%' OR locale.text ILIKE '%reinforced%' OR locale.text ILIKE '%interface%' THEN 'Reinforced boom interface'
        WHEN locale.text ILIKE '%truck%' OR locale.text ILIKE '%mount%' THEN 'Truck-mounted installation'
        WHEN locale.text ILIKE '%leg%' OR locale.text ILIKE '%outrigger%' OR locale.text ILIKE '%support%' THEN 'Stable support system'
        WHEN locale.text ILIKE '%safe%' OR locale.text ILIKE '%operator%' OR locale.text ILIKE '%load%' THEN 'Safer lifting operation'
        WHEN locale.text ILIKE '%cargo%' OR locale.text ILIKE '%construction%' OR locale.text ILIKE '%forestry%' OR locale.text ILIKE '%job%' THEN 'Practical jobsite handling'
        WHEN locale.text ILIKE '%lifting%' OR locale.text ILIKE '%power%' OR locale.text ILIKE '%capacity%' THEN 'Reliable lifting performance'
        WHEN feature._order = 0 THEN 'Practical product configuration'
        WHEN feature._order = 1 THEN 'Reliable operating structure'
        WHEN feature._order = 2 THEN 'Project-ready performance'
        ELSE 'Export-oriented build quality'
      END,
      text = replace(locale.text, 'does not enhance its lifting power', 'helps improve lifting performance')
    FROM products_key_features AS feature
    WHERE locale._parent_id = feature.id
      AND locale._locale = 'en'
      AND locale.title LIKE 'Source feature%';
  `);
}

export async function down({ db: _db }: MigrateDownArgs): Promise<void> {
  // Data cleanup only. Do not restore imported placeholder feature titles.
}
