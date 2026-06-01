import type { GlobalConfig } from 'payload';

import { isAdmin } from '../access/isAdmin';
import { pageUiSettingsFields } from '../fields/pageUiSettings';

export const PageTextSettings: GlobalConfig = {
  slug: 'page-text-settings',
  label: { zh: '通用文案', en: 'Page Text Settings' },
  access: {
    read: () => true,
    update: isAdmin,
  },
  admin: {
    hideAPIURL: true,
    group: { zh: '02 页面内容', en: '02 Page Content' },
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
