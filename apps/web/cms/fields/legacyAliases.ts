import type { Field } from 'payload';

export function legacyAliasesField(description: { zh: string; en: string }): Field {
  return {
    name: 'legacyAliases',
    type: 'array',
    admin: { description, initCollapsed: true },
    fields: [
      {
        name: 'path',
        type: 'text',
        label: { zh: '旧页面地址', en: 'Old Path' },
        required: true,
      },
    ],
    label: { zh: '其他旧地址（一般不用）', en: 'Historical Alias Paths' },
  };
}
