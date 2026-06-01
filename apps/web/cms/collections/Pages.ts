import type { CollectionConfig } from 'payload';

import { isAdmin } from '../access/isAdmin';
import { legacyAliasesField } from '../fields/legacyAliases';
import { publishedOrAdmin } from '../access/publishedOrAdmin';
import { seoFields } from '../fields/seo';
import { slugField } from '../fields/slug';
import { statusField } from '../fields/status';
import { validateUniqueLegacyPaths } from '../hooks/validateUniqueLegacyPaths';

export const Pages: CollectionConfig = {
  slug: 'pages',
  labels: {
    singular: '独立页面（Application / Service）',
    plural: '独立页面（Application / Service）',
  },
  access: {
    create: isAdmin,
    delete: isAdmin,
    read: publishedOrAdmin,
    update: isAdmin,
  },
  admin: {
    defaultColumns: ['title', 'pageType', 'legacyPath', 'showInNavigation', 'sortOrder', 'status'],
    description: {
      zh: '维护产品分类之外的独立落地页，例如旧站 Application、Service 等页面。公开 URL 填旧站当前路径，前台会直接在该路径渲染，不走跳转。',
      en: 'Manage standalone landing pages such as legacy Application and Service pages. Public URL is rendered directly without redirect.',
    },
    group: { zh: '02 前台页面设置', en: '02 Front-end Page Settings' },
    listSearchableFields: ['title', 'slug', 'legacyPath'],
    preview: ({ legacyPath, slug }) => {
      if (typeof legacyPath === 'string' && legacyPath.startsWith('/')) return legacyPath;
      return typeof slug === 'string' && slug ? `/${slug}` : '/';
    },
    useAsTitle: 'title',
  },
  defaultSort: 'sortOrder',
  hooks: {
    beforeValidate: [validateUniqueLegacyPaths('pages')],
  },
  fields: [
    {
      type: 'tabs',
      tabs: [
        {
          label: { zh: '页面内容', en: 'Page Content' },
          fields: [
            { name: 'title', type: 'text', localized: true, label: { zh: '页面标题', en: 'Page Title' }, required: true },
            slugField('title'),
            {
              name: 'legacyPath',
              type: 'text',
              admin: {
                description: {
                  zh: '公开页面 URL。为了和旧站一致，Application 可填 /application，Service 可填 /service 或旧站真实路径。',
                  en: 'Public page URL. Keep it identical to the old site whenever possible.',
                },
                position: 'sidebar',
              },
              label: { zh: '公开页面 URL（保持旧站一致）', en: 'Public Page URL' },
              required: true,
            },
            legacyAliasesField({
              zh: '同一页面曾经使用过的其他路径。主公开 URL 请填上方“公开页面 URL”。',
              en: 'Other historical paths for this page. Put the primary public URL above.',
            }),
            {
              name: 'pageType',
              type: 'select',
              defaultValue: 'custom',
              label: { zh: '页面类型', en: 'Page Type' },
              options: [
                { label: { zh: 'Application 应用页', en: 'Application Page' }, value: 'application' },
                { label: { zh: 'Service 服务页', en: 'Service Page' }, value: 'service' },
                { label: { zh: '通用落地页', en: 'Custom Landing Page' }, value: 'custom' },
              ],
              required: true,
            },
            { name: 'heroLabel', type: 'text', localized: true, label: { zh: '首屏标签', en: 'Hero Label' } },
            { name: 'heroTitle', type: 'text', localized: true, label: { zh: '首屏标题', en: 'Hero Title' }, required: true },
            { name: 'heroDescription', type: 'textarea', localized: true, label: { zh: '首屏说明', en: 'Hero Description' }, maxLength: 360 },
            { name: 'heroImage', type: 'upload', label: { zh: '首屏图片', en: 'Hero Image' }, relationTo: 'media' },
            { name: 'heroImagePath', type: 'text', label: { zh: '首屏备用图片路径', en: 'Hero Fallback Image Path' } },
          ],
        },
        {
          label: { zh: '内容区块', en: 'Content Blocks' },
          fields: [
            {
              name: 'contentBlocks',
              type: 'array',
              admin: { initCollapsed: true },
              fields: [
                { name: 'label', type: 'text', localized: true, label: { zh: '区块标签', en: 'Block Label' } },
                { name: 'title', type: 'text', localized: true, label: { zh: '区块标题', en: 'Block Title' }, required: true },
                { name: 'text', type: 'textarea', localized: true, label: { zh: '区块正文', en: 'Block Text' }, required: true },
                { name: 'image', type: 'upload', label: { zh: '区块图片', en: 'Block Image' }, relationTo: 'media' },
                { name: 'imagePath', type: 'text', label: { zh: '区块备用图片路径', en: 'Block Fallback Image Path' } },
                { name: 'ctaLabel', type: 'text', localized: true, label: { zh: '按钮文案', en: 'CTA Label' } },
                { name: 'ctaHref', type: 'text', label: { zh: '按钮链接', en: 'CTA URL' } },
              ],
              label: { zh: '内容区块', en: 'Content Blocks' },
            },
          ],
        },
        {
          label: { zh: 'SEO 与发布', en: 'SEO & Publish' },
          fields: [
            seoFields,
            statusField,
            { name: 'showInNavigation', type: 'checkbox', defaultValue: false, admin: { position: 'sidebar' }, label: { zh: '允许加入导航', en: 'Allow in Navigation' } },
            { name: 'sortOrder', type: 'number', defaultValue: 100, admin: { position: 'sidebar' }, label: { zh: '排序值', en: 'Sort Order' } },
            { name: 'publishedAt', type: 'date', admin: { position: 'sidebar' }, label: { zh: '发布时间', en: 'Published At' } },
          ],
        },
      ],
    },
  ],
  timestamps: true,
};
