import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

const directRenderPaths = [
  '/about.html',
  '/about-371153.html',
  '/authority.html',
  '/message.html',
  '/faqlist.html',
  '/article-inquire.html',
  '/contact-us',
  '/contact-us/',
  '/contact-us.html',
  '/contactus.html',
  '/contact.html',
  '/Company-News.html',
  '/Industry-News.html',
  '/news.html',
  '/Company-News-ic236357.html',
  '/Industry-News-ic226357.html',
  '/products.html',
  '/productcategory.html',
  '/keywords.html',
  '/keyword-index.html',
  '/gallery.html',
  '/videos.html',
  '/video-detail.html',
  '/download.html',
  '/download',
  '/privacy-policy.html',
  '/privacy-policy',
  '/application',
  '/application.html',
  '/Application.html',
  '/service',
  '/service.html',
  '/Service.html',
];

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql.raw(`
    UPDATE "redirects"
    SET "status" = 'draft',
        "updated_at" = now()
    WHERE "source_path" IN (${sqlList(directRenderPaths)});
  `));
}

export async function down({ db }: MigrateDownArgs): Promise<void> {
  await db.execute(sql.raw(`
    UPDATE "redirects"
    SET "status" = 'published',
        "updated_at" = now()
    WHERE "source_path" IN (${sqlList(directRenderPaths)});
  `));
}

function sqlList(values: string[]) {
  return values.map((value) => `'${value.replaceAll("'", "''")}'`).join(', ');
}
