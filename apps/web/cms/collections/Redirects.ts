import type { CollectionConfig } from 'payload';

import { isAdmin } from '../access/isAdmin';
import { publishedOrAdmin } from '../access/publishedOrAdmin';
import { validateUniqueLegacyPaths } from '../hooks/validateUniqueLegacyPaths';

export const Redirects: CollectionConfig = {
  slug: 'redirects',
  labels: {
    singular: '历史链接规则（一般不用）',
    plural: '历史链接规则（一般不用）',
  },
  access: {
    create: isAdmin,
    delete: isAdmin,
    read: publishedOrAdmin,
    update: isAdmin,
  },
  admin: {
    hideAPIURL: true,
    defaultColumns: ['sourcePath', 'targetPath', 'status', 'redirectType', 'updatedAt'],
    description: { zh: '这里是历史链接规则，日常运营一般不用。产品、分类、新闻、案例请优先在各自内容里填写“页面地址”。', en: 'Backup rules only. Products, categories, articles, and cases should use their public URL fields first so current URLs match legacy URLs and render directly.' },
    group: { zh: '90 管理员工具（一般不用）', en: '90 Admin Tools' },
    useAsTitle: 'sourcePath',
  },
  hooks: {
    beforeValidate: [validateUniqueLegacyPaths('redirects')],
  },
  fields: [
    {
      name: 'sourcePath',
      type: 'text',
      admin: { description: { zh: '旧网站地址，必须以 / 开头，例如 /telescopic-boom-truck-mounted-crane。能填到产品或分类里的，不要在这里维护。', en: 'Path to support, starting with /. Prefer product/category public URL fields when possible.' } },
      label: { zh: '旧页面地址', en: 'Legacy Source Path' },
      required: true,
      unique: true,
    },
    {
      name: 'targetPath',
      type: 'text',
      admin: { description: { zh: '这个旧地址最终应该打开到哪个新页面。', en: 'Backup target path; use only when direct same-网址 rendering is not possible.' } },
      label: { zh: '打开到哪个页面', en: 'Target Path' },
      required: true,
    },
    {
      name: 'redirectType',
      type: 'select',
      defaultValue: 'permanent',
      label: { zh: '处理方式（管理员）', en: 'Backup Type' },
      options: [
        { label: { zh: '长期使用这条规则', en: 'Permanent Redirect, Only Without Direct Render' }, value: 'permanent' },
        { label: { zh: '临时使用这条规则', en: 'Temporary Redirect, Only Without Direct Render' }, value: 'temporary' },
      ],
      required: true,
    },
    { name: 'notes', type: 'textarea', localized: true, label: { zh: '备注', en: 'Notes' } },
    {
      name: 'status',
      type: 'select',
      defaultValue: 'published',
      label: { zh: '状态', en: 'Status' },
      options: [
        { label: { zh: '已启用', en: 'Enabled' }, value: 'published' },
        { label: { zh: '草稿', en: 'Draft' }, value: 'draft' },
      ],
      required: true,
    },
  ],
  timestamps: true,
};
