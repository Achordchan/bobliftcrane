import type { CollectionConfig } from 'payload';

import { isAdmin } from '../access/isAdmin';
import { stampDrawingReviewSyncBeforeChange, syncDrawingReviewToProductAfterChange } from '../hooks/syncDrawingReviewToProduct';

export const ProductDrawingReviews: CollectionConfig = {
  slug: 'product-drawing-reviews',
  labels: {
    singular: '产品资料图片',
    plural: '产品资料图片',
  },
  access: {
    create: isAdmin,
    delete: isAdmin,
    read: isAdmin,
    update: isAdmin,
  },
  admin: {
    hideAPIURL: true,
    defaultColumns: ['title', 'reviewStatus', 'parameterMatchStatus', 'product', 'sourceType', 'reviewer', 'reviewedAt', 'updatedAt'],
    description: {
      zh: '这里集中查看每一张图纸/参数图。当前只做资料归档；保存备注会反写到对应产品，但不会阻止产品进入首页和产品列表。',
      en: 'Central record for every drawing or parameter image. These records are for evidence archiving only and do not block front-end readiness.',
    },
    group: { zh: '90 管理员工具（一般不用）', en: '90 Admin Tools' },
    listSearchableFields: ['title', 'productTitleSnapshot', 'productModelSnapshot', 'sourceUrl', 'extractedText', 'reviewNotes'],
    useAsTitle: 'title',
  },
  defaultSort: ['reviewStatus', 'productTitleSnapshot', 'drawingOrder'],
  hooks: {
    beforeChange: [stampDrawingReviewSyncBeforeChange],
    afterChange: [syncDrawingReviewToProductAfterChange],
  },
  fields: [
    { name: 'title', type: 'text', label: { zh: '资料记录标题', en: 'Evidence Record Title' }, required: true },
    {
      name: 'reviewStatus',
      type: 'select',
      defaultValue: 'needs-review',
      label: { zh: '资料记录状态', en: 'Evidence Record Status' },
      options: [
        { label: { zh: '已记录', en: 'Recorded' }, value: 'needs-review' },
        { label: { zh: '资料整理中', en: 'Record Cleanup In Progress' }, value: 'in-review' },
        { label: { zh: '资料已确认', en: 'Record Confirmed' }, value: 'reviewed' },
        { label: { zh: '无需处理', en: 'Not Required' }, value: 'not-required' },
      ],
      required: true,
    },
    {
      name: 'parameterMatchStatus',
      type: 'select',
      defaultValue: 'unchecked',
      label: { zh: '资料参数匹配记录', en: 'Parameter Match Note' },
      options: [
        { label: { zh: '未记录', en: 'Not Recorded' }, value: 'unchecked' },
        { label: { zh: '记录为与产品参数一致', en: 'Recorded as Matching Product Specs' }, value: 'matches-product-specs' },
        { label: { zh: '记录到参数差异', en: 'Spec Difference Recorded' }, value: 'spec-mismatch' },
        { label: { zh: '图片不清晰 / 暂无法记录', en: 'Unreadable Image' }, value: 'image-unreadable' },
      ],
      required: true,
    },
    {
      type: 'tabs',
      tabs: [
        {
          label: { zh: '图纸与产品', en: 'Drawing & Product' },
          fields: [
            { name: 'product', type: 'relationship', label: { zh: '关联产品', en: 'Related Product' }, relationTo: 'products', required: true },
            { name: 'drawingRowId', type: 'text', admin: { readOnly: true }, label: { zh: '产品内图纸行 ID', en: 'Product Drawing Row ID' }, required: true, unique: true },
            { name: 'drawingOrder', type: 'number', admin: { readOnly: true }, label: { zh: '图纸顺序', en: 'Drawing Order' } },
            { name: 'productTitleSnapshot', type: 'text', admin: { readOnly: true }, label: { zh: '产品名称快照', en: 'Product Title Snapshot' } },
            { name: 'productModelSnapshot', type: 'text', admin: { readOnly: true }, label: { zh: '产品型号快照', en: 'Product Model Snapshot' } },
            { name: 'specsSnapshot', type: 'textarea', admin: { readOnly: true }, label: { zh: '当前产品参数快照', en: 'Current Product Specs Snapshot' } },
            { name: 'image', type: 'upload', label: { zh: '图纸/参数图', en: 'Drawing Image' }, relationTo: 'media', required: true },
            { name: 'sourceType', type: 'text', label: { zh: '导入来源', en: 'Source Type' } },
            { name: 'sourceUrl', type: 'text', label: { zh: '旧网站原图地址', en: 'Legacy Source Image URL' } },
          ],
        },
        {
          label: { zh: '资料备注', en: 'Evidence Notes' },
          fields: [
            {
              name: 'extractedText',
              type: 'textarea',
              admin: { description: { zh: '从图纸或参数图中摘录的关键文字、型号、起重量、工作半径等。', en: 'Key text recorded from the drawing or parameter image.' } },
              label: { zh: '图纸资料摘录', en: 'Drawing Evidence Notes' },
            },
            { name: 'reviewNotes', type: 'textarea', label: { zh: '图纸资料备注', en: 'Drawing Evidence Notes' } },
            {
              name: 'reviewMethod',
              type: 'select',
              defaultValue: 'manual',
              label: { zh: '记录方式', en: 'Record Method' },
              options: [
                { label: { zh: '人工录入', en: 'Manual Entry' }, value: 'manual' },
                { label: { zh: '图片识别辅助记录', en: 'Image-assisted Record' }, value: 'image2-assisted' },
                { label: { zh: '供应商资料记录', en: 'Supplier Document Record' }, value: 'supplier-document' },
              ],
            },
            { name: 'reviewer', type: 'text', label: { zh: '记录人', en: 'Record Owner' } },
            { name: 'reviewedAt', type: 'date', label: { zh: '记录时间', en: 'Recorded At' } },
          ],
        },
        {
          label: { zh: '同步状态', en: 'Sync Status' },
          fields: [
            { name: 'syncStatus', type: 'text', admin: { readOnly: true }, defaultValue: 'synced', label: { zh: '同步状态', en: 'Sync Status' } },
            { name: 'lastSyncedAt', type: 'date', admin: { readOnly: true }, label: { zh: '最近同步时间', en: 'Last Synced At' } },
          ],
        },
      ],
    },
  ],
  timestamps: true,
};
