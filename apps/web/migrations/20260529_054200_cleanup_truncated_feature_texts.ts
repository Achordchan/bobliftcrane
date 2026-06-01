import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    UPDATE products_key_features_locales
    SET text = CASE id
      WHEN 2773 THEN 'The dumper can move 1.5 tons of materials and can be controlled through steering for convenient small-site transport.'
      WHEN 297 THEN 'The 12-ton knuckle boom marine crane is designed for efficient maneuvering in limited deck space while reaching practical working heights.'
      WHEN 2685 THEN 'The boom interface is reinforced with a steel plate, while square legs help protect the cylinder during lifting work.'
      WHEN 2740 THEN 'It also has a rack cylinder with a folding design that makes it more compact, provides better use of space, and has a low center of gravity for better maneuverability and stability.'
      WHEN 2694 THEN 'The 5-ton telescopic boom crane uses a balanced pentagon-and-hexagon boom structure to improve stability under load.'
      WHEN 2698 THEN 'The crane uses a two-section telescopic cylinder system, with one cylinder driving the main boom and the other extending the telescopic boom through wire rope.'
      ELSE text
    END
    WHERE id IN (2773, 297, 2685, 2740, 2694, 2698);
  `);
}

export async function down({ db: _db }: MigrateDownArgs): Promise<void> {
  // Data cleanup only. Do not restore truncated imported feature text.
}
