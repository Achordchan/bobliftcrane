import type { CollectionConfig } from 'payload';

import { isAdmin } from '../access/isAdmin';
import { runProductUrlAuditAfterChange } from '../hooks/runProductUrlAudit';

export const ProductUrlAudits: CollectionConfig = {
  slug: 'product-url-audits',
  labels: {
    singular: '产品 URL 验收',
    plural: '产品 URL 验收',
  },
  access: {
    create: isAdmin,
    delete: isAdmin,
    read: isAdmin,
    update: isAdmin,
  },
  admin: {
    defaultColumns: ['title', 'status', 'isCurrent', 'totalUrls', 'directRenderCount', 'contentMatchCount', 'contentMismatchCount', 'failedCount', 'lastRunAt', 'updatedAt'],
    description: {
      zh: '从后台触发旧站 URL 直出验收。把状态改为“已排队”并保存，会检查 audit/url-preservation-map.csv 中的旧站路径，结果要求 200 且无跳转；列表里只把“当前有效”的记录当作最新结论。',
      en: 'Run legacy URL direct-render audits from the admin. Set Status to Queued and save to check audit/url-preservation-map.csv. Passing URLs must return 200 without redirect. Treat the Current row as the latest conclusion.',
    },
    group: { zh: '01 产品中心', en: '01 Product Center' },
    listSearchableFields: ['title', 'baseUrl', 'reportPath'],
    useAsTitle: 'title',
  },
  defaultSort: '-updatedAt',
  hooks: {
    afterChange: [runProductUrlAuditAfterChange],
  },
  fields: [
    {
      type: 'tabs',
      tabs: [
        {
          label: { zh: '执行设置', en: 'Run Settings' },
          fields: [
            { name: 'title', type: 'text', defaultValue: '旧站 URL 直出验收', label: { zh: '验收名称', en: 'Audit Title' }, required: true },
            {
              name: 'status',
              type: 'select',
              admin: { position: 'sidebar' },
              defaultValue: 'draft',
              label: { zh: '执行状态', en: 'Run Status' },
              options: [
                { label: { zh: '草稿', en: 'Draft' }, value: 'draft' },
                { label: { zh: '已排队', en: 'Queued' }, value: 'queued' },
                { label: { zh: '运行中', en: 'Running' }, value: 'running' },
                { label: { zh: '已完成', en: 'Completed' }, value: 'completed' },
                { label: { zh: '内容不匹配', en: 'Content Mismatch' }, value: 'content-mismatch' },
                { label: { zh: '失败', en: 'Failed' }, value: 'failed' },
              ],
              required: true,
            },
            {
              name: 'baseUrl',
              type: 'text',
              defaultValue: 'http://localhost:3000',
              admin: { description: { zh: '要验收的当前站点地址。本地测试用 http://localhost:3000，线上验收改为正式域名。', en: 'Current site base URL to audit.' } },
              label: { zh: '当前站点地址', en: 'Current Site Base URL' },
              required: true,
            },
            {
              name: 'sourceCsvPath',
              type: 'text',
              defaultValue: 'audit/url-preservation-map.csv',
              admin: { description: { zh: '旧站 URL 清单文件，默认使用仓库 audit/url-preservation-map.csv。', en: 'Legacy URL inventory CSV.' } },
              label: { zh: '旧 URL 清单', en: 'Legacy URL CSV' },
            },
            { name: 'reportPath', type: 'text', admin: { readOnly: true }, label: { zh: '报告文件', en: 'Report Path' } },
            { name: 'isCurrent', type: 'checkbox', defaultValue: true, admin: { readOnly: true, position: 'sidebar' }, label: { zh: '当前有效', en: 'Current Result' } },
            { name: 'supersededAt', type: 'date', admin: { readOnly: true, position: 'sidebar' }, label: { zh: '被新结果替代时间', en: 'Superseded At' } },
            { name: 'lastRunAt', type: 'date', admin: { readOnly: true, position: 'sidebar' }, label: { zh: '最近开始时间', en: 'Last Run At' } },
            { name: 'completedAt', type: 'date', admin: { readOnly: true, position: 'sidebar' }, label: { zh: '完成时间', en: 'Completed At' } },
          ],
        },
        {
          label: { zh: '验收结果', en: 'Audit Result' },
          fields: [
            {
              type: 'row',
              fields: [
                { name: 'totalUrls', type: 'number', defaultValue: 0, admin: { readOnly: true }, label: { zh: 'URL 总数', en: 'Total URLs' }, min: 0 },
                { name: 'directRenderCount', type: 'number', defaultValue: 0, admin: { readOnly: true }, label: { zh: '直出通过数', en: 'Direct Render Count' }, min: 0 },
                { name: 'contentMatchCount', type: 'number', defaultValue: 0, admin: { readOnly: true }, label: { zh: '内容匹配数', en: 'Content Match Count' }, min: 0 },
                { name: 'contentMismatchCount', type: 'number', defaultValue: 0, admin: { readOnly: true }, label: { zh: '内容不匹配数', en: 'Content Mismatch Count' }, min: 0 },
                { name: 'compatibleFallbackCount', type: 'number', defaultValue: 0, admin: { readOnly: true }, label: { zh: '兼容兜底数', en: 'Compatible Fallback Count' }, min: 0 },
                { name: 'redirectCount', type: 'number', defaultValue: 0, admin: { readOnly: true }, label: { zh: '跳转数', en: 'Redirect Count' }, min: 0 },
                { name: 'failedCount', type: 'number', defaultValue: 0, admin: { readOnly: true }, label: { zh: '失败数', en: 'Failed Count' }, min: 0 },
              ],
            },
            { name: 'summary', type: 'textarea', admin: { readOnly: true }, label: { zh: '验收摘要', en: 'Audit Summary' } },
            { name: 'errorMessage', type: 'textarea', admin: { readOnly: true }, label: { zh: '错误信息', en: 'Error Message' } },
            {
              name: 'results',
              type: 'array',
              admin: {
                description: { zh: '每条旧站 URL 的当前响应。ok=false 或 directRender=false 的记录需要优先处理。', en: 'Per-URL result. Prioritize rows where ok=false or directRender=false.' },
                initCollapsed: true,
                readOnly: true,
              },
              fields: [
                { name: 'path', type: 'text', label: { zh: '旧站路径', en: 'Legacy Path' } },
                { name: 'type', type: 'text', label: { zh: '类型', en: 'Type' } },
                { name: 'expectedAction', type: 'text', label: { zh: '预期动作', en: 'Expected Action' } },
                { name: 'expectedTitle', type: 'text', label: { zh: '旧站标题', en: 'Expected Title' } },
                { name: 'localStatus', type: 'text', label: { zh: '当前状态码', en: 'Current Status' } },
                { name: 'localLocation', type: 'text', label: { zh: '跳转目标', en: 'Redirect Location' } },
                { name: 'localTitle', type: 'text', label: { zh: '页面标题', en: 'Page Title' } },
                { name: 'directRender', type: 'checkbox', label: { zh: '是否直出', en: 'Direct Render' } },
                { name: 'contentMatch', type: 'checkbox', label: { zh: '内容是否匹配', en: 'Content Match' } },
                { name: 'compatibleFallback', type: 'checkbox', label: { zh: '是否兼容兜底', en: 'Compatible Fallback' } },
                { name: 'contentMatchNote', type: 'textarea', label: { zh: '内容匹配说明', en: 'Content Match Note' } },
                { name: 'ok', type: 'checkbox', label: { zh: '是否通过', en: 'Passed' } },
              ],
              label: { zh: 'URL 明细', en: 'URL Results' },
            },
          ],
        },
      ],
    },
  ],
  timestamps: true,
};
