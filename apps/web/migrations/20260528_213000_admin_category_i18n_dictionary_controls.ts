import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    UPDATE "boom_dict" SET "key"='telescopic-boom' WHERE "key"='Telescopic Boom';
    UPDATE "boom_dict" SET "key"='knuckle-boom' WHERE "key"='Knuckle Boom';
    UPDATE "boom_dict" SET "key"='straight-boom' WHERE "key"='Straight Boom';
    UPDATE "boom_dict" SET "key"='fly-jib' WHERE "key"='Fly Jib';
    UPDATE "boom_dict" SET "key"='custom-solution' WHERE "key"='Custom Solution';

    UPDATE "app_dict" SET "key"='construction' WHERE "key"='Construction';
    UPDATE "app_dict" SET "key"='logistics' WHERE "key"='Logistics';
    UPDATE "app_dict" SET "key"='municipal-engineering' WHERE "key"='Municipal Engineering';
    UPDATE "app_dict" SET "key"='marine' WHERE "key"='Marine';
    UPDATE "app_dict" SET "key"='agriculture' WHERE "key"='Agriculture';
    UPDATE "app_dict" SET "key"='mining' WHERE "key"='Mining';

    UPDATE "status_dict" SET "key"='in-stock' WHERE "key"='In Stock';
    UPDATE "status_dict" SET "key"='best-seller' WHERE "key"='Best Seller';
    UPDATE "status_dict" SET "key"='customizable' WHERE "key"='Customizable';
    UPDATE "status_dict" SET "key"='pre-order' WHERE "key"='Pre-Order';

    UPDATE "site_settings_language_options"
    SET "translation_status"='in-progress', "enabled"=false
    WHERE "locale" <> 'en'
      AND "translation_status"='ready'
      AND ("completion_percent" IS NULL OR "completion_percent" < 95);

    UPDATE "site_settings_language_options"
    SET "enabled"=false
    WHERE "locale" <> 'en'
      AND "translation_status" <> 'ready';

    UPDATE "site_settings_language_options"
    SET "enabled"=true, "translation_status"='ready', "completion_percent"=100
    WHERE "locale"='en';
  `);
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
    UPDATE "boom_dict" SET "key"='Telescopic Boom' WHERE "key"='telescopic-boom';
    UPDATE "boom_dict" SET "key"='Knuckle Boom' WHERE "key"='knuckle-boom';
    UPDATE "boom_dict" SET "key"='Straight Boom' WHERE "key"='straight-boom';
    UPDATE "boom_dict" SET "key"='Fly Jib' WHERE "key"='fly-jib';
    UPDATE "boom_dict" SET "key"='Custom Solution' WHERE "key"='custom-solution';

    UPDATE "app_dict" SET "key"='Construction' WHERE "key"='construction';
    UPDATE "app_dict" SET "key"='Logistics' WHERE "key"='logistics';
    UPDATE "app_dict" SET "key"='Municipal Engineering' WHERE "key"='municipal-engineering';
    UPDATE "app_dict" SET "key"='Marine' WHERE "key"='marine';
    UPDATE "app_dict" SET "key"='Agriculture' WHERE "key"='agriculture';
    UPDATE "app_dict" SET "key"='Mining' WHERE "key"='mining';

    UPDATE "status_dict" SET "key"='In Stock' WHERE "key"='in-stock';
    UPDATE "status_dict" SET "key"='Best Seller' WHERE "key"='best-seller';
    UPDATE "status_dict" SET "key"='Customizable' WHERE "key"='customizable';
    UPDATE "status_dict" SET "key"='Pre-Order' WHERE "key"='pre-order';
  `);
}
