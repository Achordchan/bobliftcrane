import type { Field } from 'payload';

export const seoFields: Field = {
  name: 'seo',
  type: 'group',
  admin: {
    description: 'Search result metadata for Google and legacy URL migration.',
  },
  fields: [
    {
      name: 'metaTitle',
      type: 'text',
      admin: {
        description: '按当前语言填写。英语是默认来源，其他语言由翻译流程从英语生成后再复核。',
      },
      label: { zh: 'SEO 标题', en: 'Meta Title' },
      localized: true,
      maxLength: 70,
    },
    {
      name: 'metaDescription',
      type: 'textarea',
      admin: {
        description: '按当前语言填写。英语是默认来源，其他语言由翻译流程从英语生成后再复核。',
      },
      label: { zh: 'SEO 描述', en: 'Meta Description' },
      localized: true,
      maxLength: 360,
    },
    {
      name: 'canonicalPath',
      type: 'text',
      admin: {
        description: 'Optional canonical path, for example: /10-ton-telescopic-boom-truck-mounted-crane.html',
      },
      label: 'Canonical Path',
    },
  ],
};
