import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    UPDATE product_categories AS child
    SET
      parent_category_id = parent.id,
      updated_at = now()
    FROM product_categories AS parent
    WHERE parent.slug = 'other-cranes'
      AND child.slug IN ('tail-lift', 'spider-cranes', 'timber-cranes')
      AND (child.parent_category_id IS DISTINCT FROM parent.id);
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    UPDATE product_categories
    SET
      parent_category_id = NULL,
      updated_at = now()
    WHERE slug IN ('tail-lift', 'spider-cranes', 'timber-cranes')
      AND parent_category_id = (SELECT id FROM product_categories WHERE slug = 'other-cranes' LIMIT 1);
  `);
}
