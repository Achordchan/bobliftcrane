import type { CollectionConfig } from 'payload';

import { isAdmin } from '../access/isAdmin';

export const Users: CollectionConfig = {
  slug: 'users',
  labels: {
    singular: '用户',
    plural: '用户',
  },
  admin: {
    defaultColumns: ['name', 'email', 'updatedAt'],
    group: { zh: '系统设置', en: 'System' },
    useAsTitle: 'email',
  },
  auth: true,
  fields: [
    {
      name: 'name',
      type: 'text',
      label: 'Name',
    },
  ],
  access: {
    create: isAdmin,
    delete: isAdmin,
    read: isAdmin,
    update: isAdmin,
  },
};
