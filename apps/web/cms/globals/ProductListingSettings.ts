import type { GlobalConfig } from 'payload';

import { isAdmin } from '../access/isAdmin';
import { productUiSettingsFields } from '../fields/productUiSettings';

export const ProductListingSettings: GlobalConfig = {
  slug: 'product-listing-ui',
  label: { zh: '产品列表页', en: 'Product Listing Settings' },
  access: {
    read: () => true,
    update: isAdmin,
  },
  admin: {
    hideAPIURL: true,
    group: { zh: '02 页面内容', en: '02 Page Content' },
  },
  fields: [productUiSettingsFields[0]!],
};
