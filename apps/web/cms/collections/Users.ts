import type { CollectionConfig } from 'payload';

import { isAdmin } from '../access/isAdmin';

export const Users: CollectionConfig = {
  slug: 'users',
  labels: {
    singular: '用户',
    plural: '用户',
  },
  admin: {
    hideAPIURL: true,
    defaultColumns: ['name', 'email', 'updatedAt'],
    group: { zh: '91 系统账号', en: '91 System Accounts' },
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
