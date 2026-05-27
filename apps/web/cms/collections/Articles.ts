import {
  FixedToolbarFeature,
  lexicalEditor,
  lexicalHTMLField,
} from '@payloadcms/richtext-lexical';
import type { CollectionConfig } from 'payload';

import { isAdmin } from '../access/isAdmin';
import { publishedOrAdmin } from '../access/publishedOrAdmin';
import { seoFields } from '../fields/seo';
import { slugField } from '../fields/slug';
import { statusField } from '../fields/status';

export const Articles: CollectionConfig = {
  slug: 'articles',
  labels: {
    singular: { zh: '新闻文章', en: 'Article' },
    plural: { zh: '新闻文章', en: 'Articles' },
  },
  access: {
    create: isAdmin,
    delete: isAdmin,
    read: publishedOrAdmin,
    update: isAdmin,
  },
  admin: {
    defaultColumns: ['title', 'category', 'status', 'publishedAt'],
    group: { zh: '内容管理', en: 'Content' },
    preview: ({ legacyPath, slug }) => {
      if (typeof legacyPath === 'string' && legacyPath.startsWith('/')) return legacyPath;
      if (typeof slug === 'string' && slug) return `/${slug}.html`;
      return '/';
    },
    useAsTitle: 'title',
  },
  fields: [
    { name: 'title', type: 'text', label: { zh: '文章标题', en: 'Title' }, required: true },
    slugField('title'),
    {
      name: 'legacyPath',
      type: 'text',
      admin: {
        description: {
          zh: '旧站兼容 URL，例如：/company-news.html',
          en: 'Old-site compatible URL path, for example: /company-news.html',
        },
        position: 'sidebar',
      },
      label: { zh: '旧站 .html 路径', en: 'Legacy .html Path' },
    },
    {
      name: 'category',
      type: 'select',
      defaultValue: 'company-news',
      label: { zh: '文章分类', en: 'Category' },
      options: [
        { label: { zh: '公司新闻', en: 'Company News' }, value: 'company-news' },
        { label: { zh: '产品新闻', en: 'Product News' }, value: 'product-news' },
        { label: { zh: '行业洞察', en: 'Industry Insight' }, value: 'industry-insight' },
      ],
      required: true,
    },
    { name: 'excerpt', type: 'textarea', label: { zh: '摘要', en: 'Excerpt' }, maxLength: 260 },
    { name: 'coverImage', type: 'upload', label: { zh: '封面图', en: 'Cover Image' }, relationTo: 'media' },
    {
      name: 'content',
      type: 'richText',
      editor: lexicalEditor({
        features: ({ defaultFeatures }) => [
          ...defaultFeatures,
          FixedToolbarFeature(),
        ],
      }),
      label: { zh: '文章正文', en: 'Article Content' },
    },
    lexicalHTMLField({
      htmlFieldName: 'contentHtml',
      lexicalFieldName: 'content',
    }),
    seoFields,
    statusField,
    {
      name: 'publishedAt',
      type: 'date',
      admin: { position: 'sidebar' },
      label: { zh: '发布时间', en: 'Published At' },
    },
  ],
  timestamps: true,
};
