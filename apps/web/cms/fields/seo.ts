import type { Field } from 'payload';

export const seoFields: Field = {
  name: 'seo',
  type: 'group',
  label: { zh: '搜索结果设置', en: 'Search Result Settings' },
  admin: {
    description: { zh: '控制这个页面在搜索结果中的标题和描述，日常可以只填标题和描述。', en: 'Search result metadata for Google and legacy URL migration.' },
  },
  fields: [
    {
      name: 'metaTitle',
      type: 'text',
      admin: {
        description: '按当前语言填写。英语是默认来源，其他语言后续再统一处理。',
      },
      label: { zh: '搜索结果标题', en: 'Meta Title' },
      localized: true,
      maxLength: 70,
    },
    {
      name: 'metaDescription',
      type: 'textarea',
      admin: {
        description: '按当前语言填写。英语是默认来源，其他语言后续再统一处理。',
      },
      label: { zh: '搜索结果描述', en: 'Meta Description' },
      localized: true,
      maxLength: 360,
    },
    {
      name: 'canonicalPath',
      type: 'text',
      admin: {
        description: { zh: '管理员使用。用于指定搜索引擎应收录的标准页面地址。日常可以不填。', en: 'Optional canonical path, for example: /10-ton-telescopic-boom-truck-mounted-crane.html' },
      },
      label: { zh: '标准页面地址（一般不用）', en: 'Canonical Path' },
    },
  ],
};
