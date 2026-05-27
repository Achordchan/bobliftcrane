import { postgresAdapter } from '@payloadcms/db-postgres';
import { lexicalEditor } from '@payloadcms/richtext-lexical';
import { en } from '@payloadcms/translations/languages/en';
import { zh } from '@payloadcms/translations/languages/zh';
import path from 'path';
import { buildConfig } from 'payload';
import sharp from 'sharp';
import { fileURLToPath } from 'url';

import { Articles, Cases, Media, Products, Users } from './cms/collections';
import { SiteSettings } from './cms/globals/SiteSettings';

const filename = fileURLToPath(import.meta.url);
const dirname = path.dirname(filename);

export default buildConfig({
  admin: {
    importMap: {
      baseDir: path.resolve(dirname),
      importMapFile: path.resolve(dirname, 'app/(payload)/admin/importMap.js'),
    },
    meta: {
      titleSuffix: '- BOB-LIFT Content',
    },
    user: Users.slug,
  },
  collections: [Users, Media, Products, Articles, Cases],
  db: postgresAdapter({
    migrationDir: path.resolve(dirname, 'migrations'),
    pool: {
      connectionString: process.env.DATABASE_URL,
    },
    push: process.env.PAYLOAD_DB_PUSH === 'true',
  }),
  editor: lexicalEditor({}),
  globals: [SiteSettings],
  i18n: {
    fallbackLanguage: 'zh',
    supportedLanguages: { zh, en },
  },
  graphQL: {
    schemaOutputFile: path.resolve(dirname, 'payload-schema.graphql'),
  },
  routes: {
    admin: '/admin',
    api: '/api/payload',
    graphQL: '/api/graphql',
    graphQLPlayground: '/api/graphql-playground',
  },
  secret: process.env.PAYLOAD_SECRET || '',
  serverURL: process.env.NEXT_PUBLIC_SITE_URL || 'http://localhost:3000',
  sharp,
  typescript: {
    outputFile: path.resolve(dirname, 'payload-types.ts'),
  },
});
