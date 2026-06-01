import type { GlobalConfig } from 'payload';

import { isAdmin } from '../access/isAdmin';
import { productUiSettingsFields } from '../fields/productUiSettings';

export const ProductDetailSettings: GlobalConfig = {
  slug: 'product-detail-ui',
  label: { zh: '产品详情页设置', en: 'Product Detail Settings' },
  access: {
    read: () => true,
    update: isAdmin,
  },
  admin: {
    group: { zh: '02 前台页面设置', en: '02 Front-end Page Settings' },
  },
  fields: [productUiSettingsFields[1]!],
};
