import type { GlobalConfig } from 'payload';

import { isAdmin } from '../access/isAdmin';
import { productUiSettingsFields } from '../fields/productUiSettings';

export const ProductDetailSettings: GlobalConfig = {
  slug: 'product-detail-ui',
  label: { zh: '产品详情页', en: 'Product Detail Settings' },
  access: {
    read: () => true,
    update: isAdmin,
  },
  admin: {
    hideAPIURL: true,
    group: { zh: '02 页面内容', en: '02 Page Content' },
  },
  fields: [productUiSettingsFields[1]!],
};
