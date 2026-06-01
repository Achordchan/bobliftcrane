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
    singular: '其他页面',
    plural: '其他页面',
  },
  access: {
    create: isAdmin,
    delete: isAdmin,
    read: publishedOrAdmin,
    update: isAdmin,
  },
  admin: {
    hideAPIURL: true,
    defaultColumns: ['title', 'pageType', 'legacyPath', 'showInNavigation', 'sortOrder', 'status'],
    description: {
      zh: '维护产品分类之外的独立页面，例如旧网站 Application、Service 等页面。页面地址尽量沿用旧网站路径。',
      en: 'Manage standalone landing pages such as legacy Application and Service pages. Public URL is rendered directly without redirect.',
    },
    group: { zh: '02 页面内容', en: '02 Page Content' },
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
                  zh: '页面访问地址。为了和旧网站一致，Application 可填 /application，Service 可填 /service 或旧网站真实路径。',
                  en: 'Public page URL. Keep it identical to the old site whenever possible.',
                },
                position: 'sidebar',
              },
              label: { zh: '页面访问地址', en: 'Public Page URL' },
              required: true,
            },
            legacyAliasesField({
              zh: '同一页面曾经用过的其他旧地址，一般不用改。主页面地址填上方“页面访问地址”。',
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
            { name: 'heroLabel', type: 'text', localized: true, label: { zh: '顶部区域标签', en: 'Hero Label' } },
            { name: 'heroTitle', type: 'text', localized: true, label: { zh: '顶部区域标题', en: 'Hero Title' }, required: true },
            { name: 'heroDescription', type: 'textarea', localized: true, label: { zh: '顶部区域说明', en: 'Hero Description' }, maxLength: 360 },
            { name: 'heroImage', type: 'upload', label: { zh: '顶部区域图片', en: 'Hero Image' }, relationTo: 'media' },
            { name: 'heroImagePath', type: 'text', label: { zh: '图片路径（一般不用）', en: 'Hero Backup Image Path' } },
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
                { name: 'imagePath', type: 'text', label: { zh: '图片路径（一般不用）', en: 'Block Backup Image Path' } },
                { name: 'ctaLabel', type: 'text', localized: true, label: { zh: '按钮文案', en: '按钮 Label' } },
                { name: 'ctaHref', type: 'text', label: { zh: '按钮链接', en: 'Button URL' } },
              ],
              label: { zh: '内容区块', en: 'Content Blocks' },
            },
          ],
        },
        {
          label: { zh: '搜索设置与发布', en: 'Search Settings & Publish' },
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
