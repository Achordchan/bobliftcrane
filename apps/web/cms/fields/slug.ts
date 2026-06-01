import type { Field } from 'payload';

function toSlug(value: string) {
  return value
    .toLowerCase()
    .trim()
    .replace(/&/g, ' and ')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');
}

export function slugField(sourceField = 'title'): Field {
  return {
    name: 'slug',
    type: 'text',
    admin: {
      description: {
        zh: '系统会根据标题自动生成，通常不用改。不含 .html，例如：10-ton-telescopic-boom-truck-mounted-crane',
        en: 'URL slug without .html, for example: 10-ton-telescopic-boom-truck-mounted-crane',
      },
      position: 'sidebar',
    },
    hooks: {
      beforeValidate: [({ data, value }) => {
        if (value) return toSlug(String(value));
        const source = data?.[sourceField];
        return source ? toSlug(String(source)) : value;
      }],
    },
    index: true,
    label: { zh: '页面短地址', en: 'URL Slug' },
    required: true,
    unique: true,
  };
}
