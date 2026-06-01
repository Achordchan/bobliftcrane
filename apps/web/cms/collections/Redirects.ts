import type { CollectionConfig } from 'payload';

import { isAdmin } from '../access/isAdmin';
import { publishedOrAdmin } from '../access/publishedOrAdmin';
import { validateUniqueLegacyPaths } from '../hooks/validateUniqueLegacyPaths';

export const Redirects: CollectionConfig = {
  slug: 'redirects',
  labels: {
    singular: '旧 URL 兜底规则（非主要入口）',
    plural: '旧 URL 兜底规则（非主要入口）',
  },
  access: {
    create: isAdmin,
    delete: isAdmin,
    read: publishedOrAdmin,
    update: isAdmin,
  },
  admin: {
    defaultColumns: ['sourcePath', 'targetPath', 'status', 'redirectType', 'updatedAt'],
    description: { zh: '这里只处理少数无法直接渲染的历史路径。产品、分类、新闻、案例必须优先在各自内容里填写“公开 URL”，让当前链接和旧站一致并直接渲染；不要把这里当旧站跳转入口。', en: 'Fallback rules only. Products, categories, articles, and cases should use their public URL fields first so current URLs match legacy URLs and render directly.' },
    group: { zh: '03 内容与旧站 URL', en: '03 Content & Legacy URLs' },
    useAsTitle: 'sourcePath',
  },
  hooks: {
    beforeValidate: [validateUniqueLegacyPaths('redirects')],
  },
  fields: [
    {
      name: 'sourcePath',
      type: 'text',
      admin: { description: { zh: '需要兼容的访问路径，必须以 / 开头，例如 /telescopic-boom-truck-mounted-crane。能直接填到产品/分类公开 URL 的，不要在这里维护。', en: 'Path to support, starting with /. Prefer product/category public URL fields when possible.' } },
      label: { zh: '旧访问路径', en: 'Legacy Source Path' },
      required: true,
      unique: true,
    },
    {
      name: 'targetPath',
      type: 'text',
      admin: { description: { zh: '兜底目标路径；仅在不能把该 URL 直接填到产品/分类/文章/案例时使用。', en: 'Fallback target path; use only when direct same-URL rendering is not possible.' } },
      label: { zh: '兜底目标路径', en: 'Target Path' },
      required: true,
    },
    {
      name: 'redirectType',
      type: 'select',
      defaultValue: 'permanent',
      label: { zh: '最后兜底方式（一般不用）', en: 'Last-Resort Fallback Type' },
      options: [
        { label: { zh: '永久跳转（仅无直出组件时）', en: 'Permanent Redirect, Only Without Direct Render' }, value: 'permanent' },
        { label: { zh: '临时跳转（仅无直出组件时）', en: 'Temporary Redirect, Only Without Direct Render' }, value: 'temporary' },
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
