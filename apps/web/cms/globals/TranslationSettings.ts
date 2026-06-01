import type { GlobalConfig } from 'payload';

import { isAdmin } from '../access/isAdmin';

const defaultTargetLocales = ['zh', 'es', 'ar', 'fr', 'de', 'pt', 'ru', 'tr', 'id', 'vi', 'th', 'hi', 'ko'];

export const TranslationSettings: GlobalConfig = {
  slug: 'translation-settings',
  label: { zh: '自动翻译（暂停）', en: 'Translation Settings' },
  access: {
    read: isAdmin,
    update: isAdmin,
  },
  admin: {
    hideAPIURL: true,
    description: {
      zh: '自动翻译已暂停，运营人员不用设置这里。以后换好可用密钥后，再由管理员开启。',
      en: 'English is the source locale. Other languages are generated from English by a professional translation provider; the front end only reads Payload localized content.',
    },
    group: { zh: '90 管理员工具（一般不用）', en: '90 Admin Tools' },
  },
  fields: [
    {
      type: 'tabs',
      tabs: [
        {
          label: { zh: '当前状态', en: 'Status' },
          fields: [
            {
              name: 'enabled',
              type: 'checkbox',
              defaultValue: false,
              admin: { description: { zh: '现在保持关闭。不要在未确认密钥可用前开启。', en: 'Keep disabled until translation credentials are ready.' } },
              label: { zh: '开启自动翻译（现在不要开启）', en: 'Enable Translation Workflow' },
            },
            {
              type: 'collapsible',
              admin: { initCollapsed: true },
              label: { zh: '管理员配置（运营不用改）', en: 'Admin Configuration' },
              fields: [
                { name: 'provider', type: 'select', defaultValue: 'deepl', label: { zh: '翻译通道', en: 'Provider' }, options: [
                  { label: 'DeepL', value: 'deepl' },
                  { label: 'DeepLX', value: 'deeplx' },
                ], required: true },
                { name: 'authKeyEnvName', type: 'text', defaultValue: 'DEEPL_AUTH_KEY', label: { zh: 'DeepL 密钥名称', en: 'DeepL API Key Env Name' } },
                { name: 'deeplxApiUrlEnvName', type: 'text', defaultValue: 'DEEPLX_API_URL', label: { zh: 'DeepLX 服务地址名称', en: 'DeepLX API URL Env Name' } },
                { name: 'deeplxAuthKeyEnvName', type: 'text', defaultValue: 'DEEPLX_API_KEY', label: { zh: 'DeepLX 密钥名称', en: 'DeepLX Key Env Name (Optional)' } },
                { name: 'sourceLocale', type: 'text', defaultValue: 'en', label: { zh: '原始语言', en: 'Source Language' }, required: true },
                {
                  name: 'targetLocales',
                  type: 'text',
                  hasMany: true,
                  defaultValue: defaultTargetLocales,
                  admin: {
                    description: {
                      zh: '自动翻译暂停期间不用维护。',
                      en: 'Defaults to every front-end locale except English. After translation and review, mark the locale as 可显示 in Site Settings → Language Menu.',
                    },
                  },
                  label: { zh: '要翻译成哪些语言', en: 'Target Languages' },
                  required: true,
                },
                { name: 'formality', type: 'select', defaultValue: 'default', label: { zh: '语气', en: 'Formality' }, options: [
                  { label: { zh: '默认', en: 'Default' }, value: 'default' },
                  { label: { zh: '正式', en: 'Formal' }, value: 'more' },
                  { label: { zh: '简洁', en: 'Less Formal' }, value: 'less' },
                ] },
              ],
            },
          ],
        },
        {
          label: { zh: '不翻译的词', en: 'Term Protection' },
          fields: [
            { name: 'preserveVariables', type: 'checkbox', defaultValue: true, label: { zh: '保留 {name} 这类占位文字', en: 'Preserve Variables' } },
            { name: 'preserveBrandTerms', type: 'checkbox', defaultValue: true, label: { zh: '保护品牌和型号', en: 'Preserve Brand and Model Terms' } },
            {
              name: 'protectedTerms',
              type: 'array',
              defaultValue: [{ term: 'BOB-LIFT' }],
              fields: [{ name: 'term', type: 'text', required: true, label: { zh: '固定词', en: 'Protected Term' } }],
              label: { zh: '固定不翻译的品牌词', en: 'Protected Terms' },
              labels: { singular: { zh: '固定词', en: 'Protected Term' }, plural: { zh: '固定不翻译的品牌词', en: 'Protected Terms' } },
            },
            {
              name: 'notes',
              type: 'textarea',
              defaultValue: '自动翻译当前暂停。后续更换可用密钥后，由管理员重新启用并检查各语言内容；运营人员暂时不用维护这里。',
              label: { zh: '说明', en: 'Notes' },
            },
          ],
        },
      ],
    },
  ],
};
