import { postgresAdapter } from '@payloadcms/db-postgres';
import { lexicalEditor } from '@payloadcms/richtext-lexical';
import { en } from '@payloadcms/translations/languages/en';
import { zh } from '@payloadcms/translations/languages/zh';
import path from 'path';
import { buildConfig } from 'payload';
import sharp from 'sharp';
import { fileURLToPath } from 'url';

import { Articles, Cases, Inquiries, Media, Pages, ProductCategories, ProductDrawingReviews, ProductImportBatches, ProductUrlAudits, Products, Redirects, TranslationJobs, Users } from './cms/collections';
import { HomeSettings } from './cms/globals/HomeSettings';
import { PageTextSettings } from './cms/globals/PageTextSettings';
import { ProductDetailSettings } from './cms/globals/ProductDetailSettings';
import { ProductListingSettings } from './cms/globals/ProductListingSettings';
import { SiteSettings } from './cms/globals/SiteSettings';
import { TranslationSettings } from './cms/globals/TranslationSettings';

const filename = fileURLToPath(import.meta.url);
const dirname = path.dirname(filename);

const contentLocales = [
  { code: 'en', label: 'English' },
  { code: 'es', label: 'Español' },
  { code: 'ru', label: 'Русский' },
  { code: 'ar', label: 'العربية', rtl: true },
  { code: 'pt', label: 'Português' },
  { code: 'fr', label: 'Français' },
  { code: 'de', label: 'Deutsch' },
  { code: 'tr', label: 'Türkçe' },
  { code: 'id', label: 'Bahasa Indonesia' },
  { code: 'vi', label: 'Tiếng Việt' },
  { code: 'th', label: 'ไทย' },
  { code: 'hi', label: 'हिन्दी' },
  { code: 'zh', label: '中文' },
  { code: 'ko', label: '한국어' },
];

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
  collections: [Pages, ProductCategories, ProductDrawingReviews, ProductImportBatches, ProductUrlAudits, Products, Media, Articles, Cases, Redirects, Inquiries, TranslationJobs, Users],
  db: postgresAdapter({
    migrationDir: path.resolve(dirname, 'migrations'),
    pool: {
      connectionString: process.env.DATABASE_URL,
    },
    push: process.env.PAYLOAD_DB_PUSH === 'true',
  }),
  editor: lexicalEditor({}),
  globals: [HomeSettings, ProductListingSettings, ProductDetailSettings, SiteSettings, PageTextSettings, TranslationSettings],
  i18n: {
    fallbackLanguage: 'zh',
    supportedLanguages: { zh, en },
  },
  localization: {
    defaultLocale: 'en',
    fallback: true,
    locales: contentLocales,
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
