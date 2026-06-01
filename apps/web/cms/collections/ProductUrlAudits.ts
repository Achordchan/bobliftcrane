import type { CollectionConfig } from 'payload';

import { isAdmin } from '../access/isAdmin';
import { runProductUrlAuditAfterChange } from '../hooks/runProductUrlAudit';

export const ProductUrlAudits: CollectionConfig = {
  slug: 'product-url-audits',
  labels: {
    singular: '旧链接检查（管理员）',
    plural: '旧链接检查（管理员）',
  },
  access: {
    create: isAdmin,
    delete: isAdmin,
    read: isAdmin,
    update: isAdmin,
  },
  admin: {
    hideAPIURL: true,
    defaultColumns: ['title', 'status', 'isCurrent', 'totalUrls', 'directRenderCount', 'contentMatchCount', 'contentMismatchCount', 'failedCount', 'lastRunAt', 'updatedAt'],
    description: {
      zh: '管理员用来批量检查旧网站地址是否还能正常打开。日常运营一般不用；需要检查时，把处理状态改为“开始检查”并保存。',
      en: 'Run legacy URL direct-render audits from the admin. Set Status to Queued and save to check audit/url-preservation-map.csv. Passing URLs must return 200 without redirect. Treat the Current row as the latest conclusion.',
    },
    group: { zh: '90 管理员工具（一般不用）', en: '90 Admin Tools' },
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
          label: { zh: '检查设置', en: 'Check Settings' },
          fields: [
            { name: 'title', type: 'text', defaultValue: '旧链接检查', label: { zh: '检查名称', en: 'Audit Title' }, required: true },
            {
              name: 'status',
              type: 'select',
              admin: { position: 'sidebar' },
              defaultValue: 'draft',
              label: { zh: '处理状态', en: 'Status' },
              options: [
                { label: { zh: '草稿', en: 'Draft' }, value: 'draft' },
                { label: { zh: '开始检查', en: 'Queued' }, value: 'queued' },
                { label: { zh: '处理中', en: 'Running' }, value: 'running' },
                { label: { zh: '已完成', en: 'Completed' }, value: 'completed' },
                { label: { zh: '页面内容不对', en: 'Content Mismatch' }, value: 'content-mismatch' },
                { label: { zh: '失败', en: 'Failed' }, value: 'failed' },
              ],
              required: true,
            },
            {
              name: 'baseUrl',
              type: 'text',
              defaultValue: 'http://localhost:3000',
              admin: { description: { zh: '要检查的网站地址。测试环境和正式网站都可以填；不确定时不要改。', en: 'Current site base URL to audit.' } },
              label: { zh: '当前网站地址', en: 'Current Site Base URL' },
              required: true,
            },
            {
              name: 'sourceCsvPath',
              type: 'text',
              defaultValue: 'audit/url-preservation-map.csv',
              admin: { description: { zh: '管理员维护的旧网站地址清单文件，日常不用改。', en: 'Legacy URL inventory CSV.' } },
              label: { zh: '旧网站地址清单', en: 'Legacy URL CSV' },
            },
            { name: 'reportPath', type: 'text', admin: { readOnly: true }, label: { zh: '报告文件', en: 'Report Path' } },
            { name: 'isCurrent', type: 'checkbox', defaultValue: true, admin: { readOnly: true, position: 'sidebar' }, label: { zh: '当前有效', en: 'Current Result' } },
            { name: 'supersededAt', type: 'date', admin: { readOnly: true, position: 'sidebar' }, label: { zh: '被新结果替代时间', en: 'Superseded At' } },
            { name: 'lastRunAt', type: 'date', admin: { readOnly: true, position: 'sidebar' }, label: { zh: '最近开始时间', en: 'Last Run At' } },
            { name: 'completedAt', type: 'date', admin: { readOnly: true, position: 'sidebar' }, label: { zh: '完成时间', en: 'Completed At' } },
          ],
        },
        {
          label: { zh: '检查结果', en: 'Audit Result' },
          fields: [
            {
              type: 'row',
              fields: [
                { name: 'totalUrls', type: 'number', defaultValue: 0, admin: { readOnly: true }, label: { zh: '网址总数', en: 'Total URLs' }, min: 0 },
                { name: 'directRenderCount', type: 'number', defaultValue: 0, admin: { readOnly: true }, label: { zh: '正常打开数', en: 'Direct Open Count' }, min: 0 },
                { name: 'contentMatchCount', type: 'number', defaultValue: 0, admin: { readOnly: true }, label: { zh: '内容匹配数', en: 'Content Match Count' }, min: 0 },
                { name: 'contentMismatchCount', type: 'number', defaultValue: 0, admin: { readOnly: true }, label: { zh: '内容不匹配数', en: 'Content Mismatch Count' }, min: 0 },
                { name: 'compatibleFallbackCount', type: 'number', defaultValue: 0, admin: { readOnly: true }, label: { zh: '历史规则数', en: 'Compatibility Rule Count' }, min: 0 },
                { name: 'redirectCount', type: 'number', defaultValue: 0, admin: { readOnly: true }, label: { zh: '打开到其他页面数', en: 'Redirect Count' }, min: 0 },
                { name: 'failedCount', type: 'number', defaultValue: 0, admin: { readOnly: true }, label: { zh: '失败数', en: 'Failed Count' }, min: 0 },
              ],
            },
            { name: 'summary', type: 'textarea', admin: { readOnly: true }, label: { zh: '检查摘要', en: 'Audit Summary' } },
            { name: 'errorMessage', type: 'textarea', admin: { readOnly: true }, label: { zh: '错误信息', en: 'Error Message' } },
            {
              name: 'results',
              type: 'array',
              admin: {
                description: { zh: '每条旧网站地址的检查结果。未通过的记录需要优先处理。', en: 'Per-网址 result. Prioritize rows where ok=false or directRender=false.' },
                initCollapsed: true,
                readOnly: true,
              },
              fields: [
                { name: 'path', type: 'text', label: { zh: '旧网站路径', en: 'Legacy Path' } },
                { name: 'type', type: 'text', label: { zh: '类型', en: 'Type' } },
                { name: 'expectedAction', type: 'text', label: { zh: '预期动作', en: 'Expected Action' } },
                { name: 'expectedTitle', type: 'text', label: { zh: '旧网站标题', en: 'Expected Title' } },
                { name: 'localStatus', type: 'text', label: { zh: '打开结果编号（管理员）', en: 'Current Status' } },
                { name: 'localLocation', type: 'text', label: { zh: '打开到哪里', en: 'Redirect Location' } },
                { name: 'localTitle', type: 'text', label: { zh: '页面标题', en: 'Page Title' } },
                { name: 'directRender', type: 'checkbox', label: { zh: '是否正常打开', en: 'Direct Open' } },
                { name: 'contentMatch', type: 'checkbox', label: { zh: '内容是否匹配', en: 'Content Match' } },
                { name: 'compatibleFallback', type: 'checkbox', label: { zh: '是否使用历史规则', en: 'Compatibility Rule Used' } },
                { name: 'contentMatchNote', type: 'textarea', label: { zh: '内容匹配说明', en: 'Content Match Note' } },
                { name: 'ok', type: 'checkbox', label: { zh: '是否通过', en: 'Passed' } },
              ],
              label: { zh: '网址明细', en: 'URL Results' },
            },
          ],
        },
      ],
    },
  ],
  timestamps: true,
};
