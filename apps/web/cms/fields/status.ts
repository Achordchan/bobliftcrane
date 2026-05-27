import type { Field } from 'payload';

export const statusField: Field = {
  name: 'status',
  type: 'select',
  admin: {
    position: 'sidebar',
  },
  defaultValue: 'draft',
  label: { zh: '发布状态', en: 'Status' },
  options: [
    { label: { zh: '草稿', en: 'Draft' }, value: 'draft' },
    { label: { zh: '已发布', en: 'Published' }, value: 'published' },
  ],
  required: true,
};
