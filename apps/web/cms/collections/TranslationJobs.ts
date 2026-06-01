import type { CollectionConfig } from 'payload';

import { isAdmin } from '../access/isAdmin';
import { runTranslationJobAfterChange } from '../hooks/runTranslationJob';

const defaultTargetLocales = ['zh', 'es', 'ar', 'fr', 'de', 'pt', 'ru', 'tr', 'id', 'vi', 'th', 'hi', 'ko'];
const defaultScopes = ['site_settings', 'home_settings', 'product_listing_ui', 'product_detail_ui', 'page_text_settings', 'product_categories', 'products', 'articles', 'cases', 'media'];

export const TranslationJobs: CollectionConfig = {
  slug: 'translation-jobs',
  labels: {
    singular: '自动翻译任务（暂停）',
    plural: '自动翻译任务（暂停）',
  },
  access: {
    create: isAdmin,
    delete: isAdmin,
    read: isAdmin,
    update: isAdmin,
  },
  admin: {
    hideAPIURL: true,
    defaultColumns: ['title', 'provider', 'sourceLocale', 'status', 'lastRunAt', 'summary'],
    description: {
      zh: '自动翻译已经暂停，运营不要新建任务，也不要把任务改成等待处理。后续更换可用密钥后，再由管理员处理。',
      en: 'Tracks and runs professional translation jobs. English is the source locale. Set status to Queued and save to start DeepL / DeepLX in the background. 只检查 checks coverage only; disable 只检查 to write translations.',
    },
    group: { zh: '90 管理员工具（一般不用）', en: '90 Admin Tools' },
    useAsTitle: 'title',
  },
  hooks: {
    afterChange: [runTranslationJobAfterChange],
  },
  fields: [
    { name: 'title', type: 'text', localized: true, required: true, defaultValue: 'Translation Job', label: { zh: '任务名称', en: 'Job Title' } },
    {
      type: 'row',
      fields: [
        { name: 'provider', type: 'select', defaultValue: 'deepl', label: { zh: '翻译通道（暂停）', en: 'Provider' }, options: [
          { label: 'DeepL', value: 'deepl' },
          { label: 'DeepLX', value: 'deeplx' },
        ], required: true },
        { name: 'status', type: 'select', defaultValue: 'draft', label: { zh: '任务状态（不要排队）', en: 'Status' }, options: [
          { label: { zh: '草稿', en: 'Draft' }, value: 'draft' },
          { label: { zh: '缺少管理员配置', en: 'Needs Credentials' }, value: 'needs-credentials' },
          { label: { zh: '内容不完整', en: 'Coverage Needed' }, value: 'coverage-needed' },
          { label: { zh: '等待管理员处理（暂停勿选）', en: 'Queued' }, value: 'queued' },
          { label: { zh: '处理中', en: 'Running' }, value: 'running' },
          { label: { zh: '自动翻译已完成', en: 'Machine Translated' }, value: 'machine-translated' },
          { label: { zh: '人工检查中', en: 'Human Review' }, value: 'human-review' },
          { label: { zh: '已确认可用', en: 'Approved' }, value: 'approved' },
          { label: { zh: '已完成', en: 'Completed' }, value: 'completed' },
          { label: { zh: '失败', en: 'Failed' }, value: 'failed' },
        ], required: true },
      ],
    },
    {
      type: 'row',
      fields: [
        { name: 'sourceLocale', type: 'text', defaultValue: 'en', label: { zh: '原始语言', en: 'Source Language' }, required: true },
        { name: 'targetLocales', type: 'text', hasMany: true, defaultValue: defaultTargetLocales, label: { zh: '目标语言', en: 'Target Locale Codes' }, required: true },
      ],
    },
    { name: 'scopes', type: 'text', hasMany: true, defaultValue: defaultScopes, label: { zh: '翻译内容范围（管理员）', en: 'Translation Scope' }, required: true },
    {
      type: 'collapsible',
      label: { zh: '处理选项', en: 'Options' },
      fields: [
        { name: 'overwriteExisting', type: 'checkbox', defaultValue: false, label: { zh: '覆盖已有译文', en: 'Overwrite Existing Translations' } },
        { name: 'dryRun', type: 'checkbox', defaultValue: true, label: { zh: '只检查，不保存（暂停）', en: 'Preview Only' } },
        { name: 'preserveBrandTerms', type: 'checkbox', defaultValue: true, label: { zh: '品牌和型号不翻译', en: 'Preserve Brand Terms, Models and {variables}' } },
        { name: 'glossaryId', type: 'text', label: { zh: '固定术语表（管理员）', en: 'DeepL Glossary ID (DeepL Only)' } },
        { name: 'lastRunAt', type: 'date', admin: { readOnly: true }, label: { zh: '最后执行时间', en: 'Last Run At' } },
      ],
    },
    { name: 'summary', type: 'textarea', admin: { readOnly: true }, label: { zh: '处理结果', en: 'Run Summary' } },
    { name: 'errorMessage', type: 'textarea', admin: { readOnly: true }, label: { zh: '失败原因', en: 'Error Message' } },
  ],
  timestamps: true,
};
