import type { CollectionConfig } from 'payload';

import { isAdmin } from '../access/isAdmin';
import { runTranslationJobAfterChange } from '../hooks/runTranslationJob';

const defaultTargetLocales = ['zh', 'es', 'ar', 'fr', 'de', 'pt', 'ru', 'tr', 'id', 'vi', 'th', 'hi', 'ko'];
const defaultScopes = ['site_settings', 'home_settings', 'product_listing_ui', 'product_detail_ui', 'page_text_settings', 'product_categories', 'products', 'articles', 'cases', 'media'];

export const TranslationJobs: CollectionConfig = {
  slug: 'translation-jobs',
  labels: {
    singular: '翻译任务',
    plural: '翻译任务',
  },
  access: {
    create: isAdmin,
    delete: isAdmin,
    read: isAdmin,
    update: isAdmin,
  },
  admin: {
    defaultColumns: ['title', 'provider', 'sourceLocale', 'status', 'lastRunAt', 'summary'],
    description: {
      zh: '用来记录和触发专业翻译插件任务。英语是源语言；把任务状态改为“已排队”并保存即可在后台启动 DeepL / DeepLX。dryRun=仅覆盖检查，关闭 dryRun 后才写入译文。译文完成并复核后，再到“网站设置 → 语言菜单”把对应语言改为 ready。',
      en: 'Tracks and runs professional translation jobs. English is the source locale. Set status to Queued and save to start DeepL / DeepLX in the background. dryRun checks coverage only; disable dryRun to write translations.',
    },
    group: { zh: '06 翻译与多语言', en: '06 Translation & i18n' },
    useAsTitle: 'title',
  },
  hooks: {
    afterChange: [runTranslationJobAfterChange],
  },
  fields: [
    { name: 'title', type: 'text', localized: true, required: true, defaultValue: 'Professional Translation Job', label: { zh: '任务名称', en: 'Job Title' } },
    {
      type: 'row',
      fields: [
        { name: 'provider', type: 'select', defaultValue: 'deepl', label: { zh: '翻译服务', en: 'Provider' }, options: [
          { label: 'DeepL', value: 'deepl' },
          { label: 'DeepLX', value: 'deeplx' },
        ], required: true },
        { name: 'status', type: 'select', defaultValue: 'draft', label: { zh: '任务状态', en: 'Status' }, options: [
          { label: { zh: '草稿', en: 'Draft' }, value: 'draft' },
          { label: { zh: '待配置 Key', en: 'Needs Credentials' }, value: 'needs-credentials' },
          { label: { zh: '覆盖率未通过', en: 'Coverage Needed' }, value: 'coverage-needed' },
          { label: { zh: '已排队', en: 'Queued' }, value: 'queued' },
          { label: { zh: '运行中', en: 'Running' }, value: 'running' },
          { label: { zh: '机器翻译完成', en: 'Machine Translated' }, value: 'machine-translated' },
          { label: { zh: '人工复核中', en: 'Human Review' }, value: 'human-review' },
          { label: { zh: '已批准上线', en: 'Approved' }, value: 'approved' },
          { label: { zh: '已完成', en: 'Completed' }, value: 'completed' },
          { label: { zh: '失败', en: 'Failed' }, value: 'failed' },
        ], required: true },
      ],
    },
    {
      type: 'row',
      fields: [
        { name: 'sourceLocale', type: 'text', defaultValue: 'en', label: { zh: '源语言代码', en: 'Source Locale Code' }, required: true },
        { name: 'targetLocales', type: 'text', hasMany: true, defaultValue: defaultTargetLocales, label: { zh: '目标语言代码', en: 'Target Locale Codes' }, required: true },
      ],
    },
    { name: 'scopes', type: 'text', hasMany: true, defaultValue: defaultScopes, label: { zh: '翻译范围代码', en: 'Translation Scope Codes' }, required: true },
    {
      type: 'collapsible',
      label: { zh: '执行选项', en: 'Run Options' },
      fields: [
        { name: 'overwriteExisting', type: 'checkbox', defaultValue: false, label: { zh: '覆盖已有译文', en: 'Overwrite Existing Translations' } },
        { name: 'dryRun', type: 'checkbox', defaultValue: true, label: { zh: '仅预览，不写入数据库', en: 'Dry Run Only' } },
        { name: 'preserveBrandTerms', type: 'checkbox', defaultValue: true, label: { zh: '保护品牌、型号与 {变量}', en: 'Preserve Brand Terms, Models and {variables}' } },
        { name: 'glossaryId', type: 'text', label: { zh: 'DeepL 术语表 ID（仅 DeepL）', en: 'DeepL Glossary ID (DeepL Only)' } },
        { name: 'lastRunAt', type: 'date', admin: { readOnly: true }, label: { zh: '最后执行时间', en: 'Last Run At' } },
      ],
    },
    { name: 'summary', type: 'textarea', admin: { readOnly: true }, label: { zh: '执行摘要', en: 'Run Summary' } },
    { name: 'errorMessage', type: 'textarea', admin: { readOnly: true }, label: { zh: '错误信息', en: 'Error Message' } },
  ],
  timestamps: true,
};
