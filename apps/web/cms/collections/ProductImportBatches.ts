import type { CollectionConfig } from 'payload';

import { isAdmin } from '../access/isAdmin';
import { runProductImportBatchAfterChange } from '../hooks/runProductImportBatch';

export const ProductImportBatches: CollectionConfig = {
  slug: 'product-import-batches',
  labels: {
    singular: '产品导入与参数复核',
    plural: '产品导入与参数复核',
  },
  access: {
    create: isAdmin,
    delete: isAdmin,
    read: isAdmin,
    update: isAdmin,
  },
  admin: {
    defaultColumns: ['title', 'batchId', 'status', 'runAction', 'productCount', 'needsReviewCount', 'verifiedCount', 'drawingReviewedCount', 'updatedAt'],
    description: {
      zh: '这里从 bobliftcrane.com 抓取产品、导入图片和参数，并执行参数来源复核。选择执行动作后把状态改为“已排队”并保存；系统会保留已核对参数，不会静默覆盖 verified 产品。导入后还要看产品里的“前台可见状态”。',
      en: 'Review and run legacy-site crawls, product imports, and parameter review batches here. Choose an action, set Status to Queued, and save. Verified products are preserved.',
    },
    group: { zh: '01 产品中心', en: '01 Product Center' },
    listSearchableFields: ['title', 'batchId', 'sourceBaseUrl'],
    useAsTitle: 'title',
  },
  defaultSort: '-startedAt',
  hooks: {
    afterChange: [runProductImportBatchAfterChange],
  },
  fields: [
    {
      type: 'tabs',
      tabs: [
        {
          label: { zh: '批次信息', en: 'Batch Info' },
          fields: [
            { name: 'title', type: 'text', localized: true, label: { zh: '批次名称', en: 'Batch Title' }, required: true },
            { name: 'batchId', type: 'text', admin: { position: 'sidebar' }, label: { zh: '批次 ID', en: 'Batch ID' }, required: true, unique: true },
            {
              name: 'status',
              type: 'select',
              admin: { position: 'sidebar' },
              defaultValue: 'in-review',
              label: { zh: '批次状态', en: 'Batch Status' },
              options: [
                { label: { zh: '已排队', en: 'Queued' }, value: 'queued' },
                { label: { zh: '已导入待复核', en: 'Imported, Needs Review' }, value: 'in-review' },
                { label: { zh: '复核中', en: 'Reviewing' }, value: 'reviewing' },
                { label: { zh: '已完成', en: 'Completed' }, value: 'completed' },
                { label: { zh: '失败/需重跑', en: 'Failed / Needs Rerun' }, value: 'failed' },
              ],
              required: true,
            },
            {
              name: 'runAction',
              type: 'select',
              defaultValue: 'source-audit',
              label: { zh: '执行动作', en: 'Run Action' },
              options: [
                { label: { zh: '只跑参数来源复核', en: 'Run Source Audit Only' }, value: 'source-audit' },
                { label: { zh: '抓取旧站产品并复核', en: 'Import Legacy Products and Audit' }, value: 'import-and-source-audit' },
                { label: { zh: '仅预演抓取，不写入产品', en: 'Dry Run Import Only' }, value: 'dry-run-import' },
              ],
              required: true,
              admin: {
                description: {
                  zh: '推荐日常使用“只跑参数来源复核”。需要重新从 bobliftcrane.com 抓取产品、图片和参数时，再选择“抓取旧站产品并复核”。',
                  en: 'Use Source Audit Only for routine verification. Use Import Legacy Products and Audit when you need to crawl bobliftcrane.com again.',
                },
              },
            },
            {
              name: 'sourceType',
              type: 'select',
              defaultValue: 'legacy-site',
              label: { zh: '来源类型', en: 'Source Type' },
              options: [
                { label: { zh: '旧站抓取', en: 'Legacy Site Crawl' }, value: 'legacy-site' },
                { label: { zh: '手动录入', en: 'Manual Entry' }, value: 'manual' },
                { label: { zh: 'AI 辅助生成', en: 'AI Assisted' }, value: 'ai-assisted' },
              ],
              required: true,
            },
            { name: 'sourceBaseUrl', type: 'text', defaultValue: 'https://www.bobliftcrane.com', label: { zh: '来源站点', en: 'Source Site' } },
            { name: 'sitemapUrl', type: 'text', defaultValue: 'https://www.bobliftcrane.com/sitemap.xml', label: { zh: 'Sitemap URL', en: 'Sitemap URL' } },
            { name: 'reportPath', type: 'text', admin: { readOnly: true }, label: { zh: '复核报告文件', en: 'Audit Report Path' } },
            { name: 'startedAt', type: 'date', admin: { readOnly: true, position: 'sidebar' }, label: { zh: '开始时间', en: 'Started At' } },
            { name: 'completedAt', type: 'date', admin: { readOnly: true, position: 'sidebar' }, label: { zh: '完成时间', en: 'Completed At' } },
          ],
        },
        {
          label: { zh: '导入统计', en: 'Import Stats' },
          fields: [
            { name: 'productCount', type: 'number', defaultValue: 0, admin: { readOnly: true }, label: { zh: '产品总数', en: 'Product Count' }, min: 0 },
            { name: 'importedCount', type: 'number', defaultValue: 0, admin: { readOnly: true }, label: { zh: '导入成功数', en: 'Imported Count' }, min: 0 },
            { name: 'skippedCount', type: 'number', defaultValue: 0, admin: { readOnly: true }, label: { zh: '跳过数', en: 'Skipped Count' }, min: 0 },
            { name: 'failedCount', type: 'number', defaultValue: 0, admin: { readOnly: true }, label: { zh: '失败数', en: 'Failed Count' }, min: 0 },
            { name: 'needsReviewCount', type: 'number', defaultValue: 0, admin: { readOnly: true }, label: { zh: '待复核产品数', en: 'Needs Review Count' }, min: 0 },
            { name: 'inReviewCount', type: 'number', defaultValue: 0, admin: { readOnly: true }, label: { zh: '复核中产品数', en: 'In Review Count' }, min: 0 },
            { name: 'verifiedCount', type: 'number', defaultValue: 0, admin: { readOnly: true }, label: { zh: '已核对产品数', en: 'Verified Count' }, min: 0 },
            { name: 'drawingReviewNeededCount', type: 'number', defaultValue: 0, admin: { readOnly: true }, label: { zh: '图纸待处理数（已停用）', en: 'Drawing Pending Count (Disabled)' }, min: 0 },
            { name: 'drawingReviewedCount', type: 'number', defaultValue: 0, admin: { readOnly: true }, label: { zh: '图纸资料记录数', en: 'Drawing Evidence Count' }, min: 0 },
            {
              name: 'overwritePolicy',
              type: 'select',
              defaultValue: 'preserve-verified',
              label: { zh: '重跑覆盖策略', en: 'Rerun Overwrite Policy' },
              options: [
                { label: { zh: '保留已核对参数', en: 'Preserve Verified Parameters' }, value: 'preserve-verified' },
                { label: { zh: '只覆盖未核对产品', en: 'Rewrite Unverified Products Only' }, value: 'rewrite-unverified' },
                { label: { zh: '仅记录，不覆盖', en: 'Record Only' }, value: 'record-only' },
              ],
              required: true,
            },
          ],
        },
        {
          label: { zh: '复核说明', en: 'Review Notes' },
          fields: [
            {
              name: 'reviewChecklist',
              type: 'textarea',
              localized: true,
              label: { zh: '参数复核要求', en: 'Parameter Review Checklist' },
            },
            {
              name: 'notes',
              type: 'textarea',
              localized: true,
              label: { zh: '批次备注', en: 'Batch Notes' },
            },
            { name: 'summary', type: 'textarea', admin: { readOnly: true }, label: { zh: '执行摘要', en: 'Run Summary' } },
            { name: 'errorMessage', type: 'textarea', admin: { readOnly: true }, label: { zh: '错误信息', en: 'Error Message' } },
            {
              name: 'failureLogs',
              type: 'array',
              admin: { initCollapsed: true },
              fields: [
                { name: 'sourceUrl', type: 'text', label: { zh: '来源 URL', en: 'Source URL' } },
                { name: 'productTitle', type: 'text', label: { zh: '产品标题', en: 'Product Title' } },
                { name: 'error', type: 'textarea', label: { zh: '错误说明', en: 'Error' } },
              ],
              label: { zh: '失败记录', en: 'Failure Logs' },
            },
            {
              name: 'products',
              type: 'relationship',
              admin: { description: { zh: '可选。把本批次导入的产品关联进来，便于后台集中检查。', en: 'Optional products imported in this batch.' } },
              hasMany: true,
              label: { zh: '关联产品', en: 'Related Products' },
              relationTo: 'products',
            },
          ],
        },
      ],
    },
  ],
  timestamps: true,
};
