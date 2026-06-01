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

export const Articles: CollectionConfig = {
  slug: 'articles',
  labels: {
    singular: '新闻文章',
    plural: '新闻文章',
  },
  access: {
    create: isAdmin,
    delete: isAdmin,
    read: publishedOrAdmin,
    update: isAdmin,
  },
  admin: {
    hideAPIURL: true,
    defaultColumns: ['title', 'category', 'status', 'publishedAt'],
    group: { zh: '03 新闻与案例', en: '03 News & Cases' },
    preview: ({ legacyPath, slug }) => {
      if (typeof legacyPath === 'string' && legacyPath.startsWith('/')) return legacyPath;
      if (typeof slug === 'string' && slug) return `/${slug}.html`;
      return '/';
    },
    useAsTitle: 'title',
  },
  hooks: {
    beforeValidate: [validateUniqueLegacyPaths('articles')],
  },
  fields: [
    { name: 'title', type: 'text', localized: true, label: { zh: '文章标题', en: 'Title' }, required: true },
    slugField('title'),
    {
      name: 'legacyPath',
      type: 'text',
      admin: {
        description: {
          zh: '文章页面地址。为了让改版后链接与旧网站一致，优先填写旧网站真实 .html 路径，例如：/company-news.html。',
          en: 'Current public article URL. Use the original old-site .html path whenever possible, for example: /company-news.html.',
        },
        position: 'sidebar',
      },
      label: { zh: '文章页面地址', en: 'Public Article URL' },
    },
    legacyAliasesField({
      zh: '同一文章曾经用过的其他旧地址，一般不用改。主页面地址填上方“文章页面地址”。',
      en: 'Other historical paths for this article. Put the main public URL in Public Article URL above. These aliases also render the article detail page directly.',
    }),
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
    { name: 'excerpt', type: 'textarea', localized: true, label: { zh: '摘要', en: 'Excerpt' }, maxLength: 260 },
    { name: 'coverImage', type: 'upload', label: { zh: '封面图', en: 'Cover Image' }, relationTo: 'media' },
    {
      name: 'coverImagePath',
      type: 'text',
      admin: {
        description: {
        zh: '可选。旧网站图片已整理到网站文件夹时可填路径，例如 /images/oldsite/news/example.jpg；上传封面图优先级更高。',
          en: 'Optional static image path, e.g. /images/oldsite/news/example.jpg. Uploaded cover image takes priority.',
        },
      },
      label: { zh: '封面图片路径（一般不用）', en: 'Static Cover Image Path' },
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
