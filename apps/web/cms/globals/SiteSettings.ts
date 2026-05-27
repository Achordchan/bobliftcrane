import type { GlobalConfig } from 'payload';

import { isAdmin } from '../access/isAdmin';

export const SiteSettings: GlobalConfig = {
  slug: 'site-settings',
  label: { zh: '网站设置', en: 'Site Settings' },
  access: {
    read: () => true,
    update: isAdmin,
  },
  admin: {
    group: { zh: '系统设置', en: 'System' },
  },
  fields: [
    { name: 'siteName', type: 'text', defaultValue: 'BOB-LIFT' },
    { name: 'email', type: 'email', defaultValue: 'info@bobliftcrane.com' },
    { name: 'phone', type: 'text', defaultValue: '+86 15852499415' },
    { name: 'whatsapp', type: 'text', defaultValue: '+86 15852499415' },
    { name: 'address', type: 'text', defaultValue: 'Xuzhou, China' },
  ],
};
