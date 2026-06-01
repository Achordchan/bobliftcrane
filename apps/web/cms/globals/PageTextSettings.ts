import type { GlobalConfig } from 'payload';

import { isAdmin } from '../access/isAdmin';
import { pageUiSettingsFields } from '../fields/pageUiSettings';

export const PageTextSettings: GlobalConfig = {
  slug: 'page-text-settings',
  label: { zh: '页面文案设置', en: 'Page Text Settings' },
  access: {
    read: () => true,
    update: isAdmin,
  },
  admin: {
    group: { zh: '02 前台页面设置', en: '02 Front-end Page Settings' },
  },
  fields: [
    {
      type: 'tabs',
      tabs: [
        {
          label: { zh: '页面文案', en: 'Page Text' },
          fields: pageUiSettingsFields,
        },
      ],
    },
  ],
};
