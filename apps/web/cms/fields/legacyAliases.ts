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
        label: { zh: '旧路径', en: 'Old Path' },
        required: true,
      },
    ],
    label: { zh: '历史别名路径', en: 'Historical Alias Paths' },
  };
}
