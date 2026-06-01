import type { GlobalConfig } from 'payload';

import { isAdmin } from '../access/isAdmin';

const defaultTargetLocales = ['zh', 'es', 'ar', 'fr', 'de', 'pt', 'ru', 'tr', 'id', 'vi', 'th', 'hi', 'ko'];

export const TranslationSettings: GlobalConfig = {
  slug: 'translation-settings',
  label: { zh: '翻译插件设置', en: 'Translation Plugin Settings' },
  access: {
    read: isAdmin,
    update: isAdmin,
  },
  admin: {
    description: {
      zh: '英语是默认源语言；其他语言由专业翻译服务从英语生成，前端只读取 Payload 本地化内容，不硬编码译文。',
      en: 'English is the source locale. Other languages are generated from English by a professional translation provider; the front end only reads Payload localized content.',
    },
    group: { zh: '06 翻译与多语言', en: '06 Translation & i18n' },
  },
  fields: [
    {
      type: 'tabs',
      tabs: [
        {
          label: { zh: '服务配置', en: 'Provider' },
          fields: [
            { name: 'enabled', type: 'checkbox', defaultValue: false, label: { zh: '启用翻译流程', en: 'Enable Translation Workflow' } },
            { name: 'provider', type: 'select', defaultValue: 'deepl', label: { zh: '翻译服务', en: 'Provider' }, options: [
              { label: 'DeepL', value: 'deepl' },
              { label: 'DeepLX', value: 'deeplx' },
            ], required: true },
            { name: 'authKeyEnvName', type: 'text', defaultValue: 'DEEPL_AUTH_KEY', label: { zh: 'DeepL API Key 环境变量名', en: 'DeepL API Key Env Name' } },
            { name: 'deeplxApiUrlEnvName', type: 'text', defaultValue: 'DEEPLX_API_URL', label: { zh: 'DeepLX 接口地址环境变量名', en: 'DeepLX API URL Env Name' } },
            { name: 'deeplxAuthKeyEnvName', type: 'text', defaultValue: 'DEEPLX_API_KEY', label: { zh: 'DeepLX Key 环境变量名（可选）', en: 'DeepLX Key Env Name (Optional)' } },
            { name: 'sourceLocale', type: 'text', defaultValue: 'en', label: { zh: '源语言代码', en: 'Source Locale Code' }, required: true },
            {
              name: 'targetLocales',
              type: 'text',
              hasMany: true,
              defaultValue: defaultTargetLocales,
              admin: {
                description: {
                  zh: '默认覆盖除英语外的全部前台语言。翻译完成并复核后，再到“网站设置 → 语言菜单”把对应语言状态改为可上线。',
                  en: 'Defaults to every front-end locale except English. After translation and review, mark the locale as ready in Site Settings → Language Menu.',
                },
              },
              label: { zh: '默认目标语言代码', en: 'Default Target Locale Codes' },
              required: true,
            },
            { name: 'formality', type: 'select', defaultValue: 'default', label: { zh: '语气', en: 'Formality' }, options: [
              { label: { zh: '默认', en: 'Default' }, value: 'default' },
              { label: { zh: '正式', en: 'Formal' }, value: 'more' },
              { label: { zh: '简洁', en: 'Less Formal' }, value: 'less' },
            ] },
          ],
        },
        {
          label: { zh: '术语保护', en: 'Term Protection' },
          fields: [
            { name: 'preserveVariables', type: 'checkbox', defaultValue: true, label: { zh: '保护 {变量}', en: 'Preserve {variables}' } },
            { name: 'preserveBrandTerms', type: 'checkbox', defaultValue: true, label: { zh: '保护品牌和型号', en: 'Preserve Brand and Model Terms' } },
            {
              name: 'protectedTerms',
              type: 'array',
              defaultValue: [{ term: 'BOB-LIFT' }],
              fields: [{ name: 'term', type: 'text', required: true, label: { zh: '不翻译术语', en: 'Protected Term' } }],
              label: { zh: '不翻译术语表', en: 'Protected Terms' },
            },
            {
              name: 'notes',
              type: 'textarea',
              defaultValue: '1) Coverage: npm run payload:translate -- --coverage --include-stale --sync-readiness. This writes the latest coverage summary to Translation Jobs and disables unfinished languages in the language menu. 2) DeepL Apply: DEEPL_AUTH_KEY=xxx npm run payload:translate -- --provider=deepl --include-stale --apply. 3) DeepLX Apply: DEEPLX_API_KEY=xxx npm run payload:translate -- --provider=deeplx --include-stale --apply. Optional custom endpoint: DEEPLX_API_URL=https://... 4) Scope includes pages, products, categories, articles, cases, and media Alt/Caption. 5) Go live only after the target language has no missing/stale fields and is marked ready.',
              label: { zh: '操作说明', en: 'Run Notes' },
            },
          ],
        },
      ],
    },
  ],
};
