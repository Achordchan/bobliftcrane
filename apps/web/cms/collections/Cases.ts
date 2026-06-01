import {
  FixedToolbarFeature,
  lexicalEditor,
  lexicalHTMLField,
} from '@payloadcms/richtext-lexical';
import type { CollectionConfig } from 'payload';

import { isAdmin } from '../access/isAdmin';
import { legacyAliasesField } from '../fields/legacyAliases';
import { publishedOrAdmin } from '../access/publishedOrAdmin';
import { seoFields } from '../fields/seo';
import { slugField } from '../fields/slug';
import { statusField } from '../fields/status';
import { validateUniqueLegacyPaths } from '../hooks/validateUniqueLegacyPaths';

export const Cases: CollectionConfig = {
  slug: 'cases',
  labels: {
    singular: '客户案例',
    plural: '客户案例',
  },
  access: {
    create: isAdmin,
    delete: isAdmin,
    read: publishedOrAdmin,
    update: isAdmin,
  },
  admin: {
    defaultColumns: ['title', 'caseType', 'country', 'status', 'updatedAt'],
    group: { zh: '03 内容与旧站 URL', en: '03 Content & Legacy URLs' },
    preview: ({ legacyPath, slug }) => {
      if (typeof legacyPath === 'string' && legacyPath.startsWith('/')) return legacyPath;
      return typeof slug === 'string' && slug ? `/cases/${slug}` : '/cases';
    },
    useAsTitle: 'title',
  },
  hooks: {
    beforeValidate: [validateUniqueLegacyPaths('cases')],
  },
  fields: [
    { name: 'title', type: 'text', localized: true, label: { zh: '案例标题', en: 'Case Title' }, required: true },
    slugField('title'),
    {
      name: 'legacyPath',
      type: 'text',
      admin: {
        description: { zh: '当前公开案例 URL。为了让改版后链接与旧站一致，优先填写旧站真实 .html 路径，例如：/customer-case.html。', en: 'Current public case URL. Use the original old-site .html path whenever possible.' },
        position: 'sidebar',
      },
      label: { zh: '公开案例 URL（保持旧站一致）', en: 'Public Case URL' },
    },
    legacyAliasesField({
      zh: '同一案例曾经用过的其他旧站路径。主公开 URL 请填上方“公开案例 URL”，这些别名也会直接渲染案例详情页。',
      en: 'Other historical paths for this case. Put the main public URL in Public Case URL above. These aliases also render the case detail page directly.',
    }),
    { name: 'caseType', type: 'text', localized: true, label: { zh: '案例类型', en: 'Case Type' } },
    { name: 'country', type: 'text', localized: true, label: { zh: '国家/地区', en: 'Country / Region' } },
    { name: 'industry', type: 'text', localized: true, label: { zh: '行业', en: 'Industry' } },
    { name: 'summary', type: 'textarea', localized: true, label: { zh: '摘要', en: 'Summary' }, maxLength: 320 },
    { name: 'coverImage', type: 'upload', label: { zh: '封面图', en: 'Cover Image' }, relationTo: 'media' },
    {
      name: 'coverImagePath',
      type: 'text',
      admin: {
        description: {
          zh: '可选。旧站图片已在 public 下时可填路径，例如 /images/oldsite/cases/example.jpg；上传封面图优先级更高。',
          en: 'Optional static image path, e.g. /images/oldsite/cases/example.jpg. Uploaded cover image takes priority.',
        },
      },
      label: { zh: '封面图静态路径', en: 'Static Cover Image Path' },
    },
    {
      name: 'facts',
      type: 'array',
      admin: { description: { zh: '详情页左侧事实卡片，例如：场景、产品、重点。', en: 'Fact cards shown on the case detail page.' }, initCollapsed: true },
      fields: [
        { name: 'label', type: 'text', localized: true, label: { zh: '名称', en: 'Label' }, required: true },
        { name: 'value', type: 'text', localized: true, label: { zh: '内容', en: 'Value' }, required: true },
      ],
      label: { zh: '案例事实卡片', en: 'Case Facts' },
    },
    {
      name: 'sections',
      type: 'array',
      admin: { description: { zh: '结构化正文；未填写时前台会使用富文本正文。', en: 'Structured article sections; rich text is used when empty.' }, initCollapsed: true },
      fields: [
        { name: 'heading', type: 'text', localized: true, label: { zh: '小标题', en: 'Heading' }, required: true },
        { name: 'body', type: 'textarea', localized: true, label: { zh: '正文', en: 'Body' }, required: true },
      ],
      label: { zh: '正文段落', en: 'Content Sections' },
    },
    {
      name: 'content',
      type: 'richText',
      localized: true,
      editor: lexicalEditor({
        features: ({ defaultFeatures }) => [
          ...defaultFeatures,
          FixedToolbarFeature(),
        ],
      }),
      label: { zh: '富文本正文', en: 'Rich Text Content' },
    },
    lexicalHTMLField({
      htmlFieldName: 'contentHtml',
      lexicalFieldName: 'content',
    }),
    seoFields,
    statusField,
    { name: 'publishedAt', type: 'date', admin: { position: 'sidebar' }, label: { zh: '发布时间', en: 'Published At' } },
  ],
  timestamps: true,
};
