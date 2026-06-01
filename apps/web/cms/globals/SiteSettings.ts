import type { GlobalConfig } from 'payload';

import { defaultChromeUiContent, defaultLanguageOptions } from '../../lib/site-ui';
import { isAdmin } from '../access/isAdmin';

const localeOptions = defaultLanguageOptions.map(({ locale, label }) => ({ label, value: locale }));
const directionOptions = [
  { label: { zh: '从左到右', en: 'Left to Right' }, value: 'ltr' },
  { label: { zh: '从右到左', en: 'Right to Left' }, value: 'rtl' },
];
const translationStatusOptions = [
  { label: { zh: '未开始', en: 'Not Started' }, value: 'not-started' },
  { label: { zh: '翻译中', en: 'In Progress' }, value: 'in-progress' },
  { label: { zh: '可上线', en: 'Ready' }, value: 'ready' },
];

type LanguageOptionDraft = {
  locale?: string | null;
  translationStatus?: string | null;
  completionPercent?: number | null;
  enabled?: boolean | null;
  [key: string]: unknown;
};

function normalizeLanguageOptions(data: unknown) {
  if (!data || typeof data !== 'object') return data;
  const draft = data as { languageOptions?: LanguageOptionDraft[] };
  if (!Array.isArray(draft.languageOptions)) return data;

  return {
    ...draft,
    languageOptions: draft.languageOptions.map((item) => {
      const locale = item.locale;
      const isDefaultEnglish = locale === 'en';
      const completionPercent = typeof item.completionPercent === 'number' ? item.completionPercent : 0;
      const canGoLive = isDefaultEnglish || (item.translationStatus === 'ready' && completionPercent >= 95);

      return {
        ...item,
        translationStatus: isDefaultEnglish ? 'ready' : item.translationStatus,
        completionPercent: isDefaultEnglish ? 100 : completionPercent,
        enabled: isDefaultEnglish ? true : canGoLive ? item.enabled : false,
      };
    }),
  };
}

export const SiteSettings: GlobalConfig = {
  slug: 'site-settings',
  label: { zh: '网站设置（导航 / 联系 / 页脚）', en: 'Site Settings (Navigation / Contact / Footer)' },
  access: {
    read: () => true,
    update: isAdmin,
  },
  admin: {
    group: { zh: '02 前台页面设置', en: '02 Front-end Page Settings' },
  },
  hooks: {
    beforeValidate: [({ data }) => normalizeLanguageOptions(data)],
  },
  fields: [
    {
      type: 'tabs',
      tabs: [
        {
          label: { zh: '联系信息', en: 'Contact' },
          fields: [
            { name: 'siteName', type: 'text', localized: true, defaultValue: 'BOB-LIFT' },
            { name: 'logoImage', type: 'upload', label: { zh: '网站 Logo 图片', en: 'Site Logo Image' }, relationTo: 'media' },
            { name: 'logoPath', type: 'text', defaultValue: '/images/bob-lift-logo.png', label: { zh: 'Logo 备用路径', en: 'Logo Fallback Path' } },
            { name: 'logoWidth', type: 'number', defaultValue: 188, min: 1, label: { zh: 'Logo 宽度', en: 'Logo Width' } },
            { name: 'logoHeight', type: 'number', defaultValue: 91, min: 1, label: { zh: 'Logo 高度', en: 'Logo Height' } },
            { name: 'email', type: 'email', defaultValue: 'info@bobliftcrane.com' },
            { name: 'phone', type: 'text', defaultValue: '+86 15852499415' },
            { name: 'whatsapp', type: 'text', defaultValue: '+86 15852499415' },
            { name: 'wechatId', type: 'text', defaultValue: '15852499415', label: { zh: '微信号', en: 'WeChat ID' } },
            { name: 'wechatQr', type: 'upload', label: { zh: '微信二维码', en: 'WeChat QR Code' }, relationTo: 'media' },
            { name: 'address', type: 'text', localized: true, defaultValue: 'Xuzhou, China' },
            { name: 'quotePath', type: 'text', defaultValue: '/quote', label: { zh: '询盘页路径', en: 'Quote Page Path' } },
            { name: 'inquirySubject', type: 'text', localized: true, defaultValue: 'BOB-LIFT quotation request', label: { zh: '询盘邮件标题', en: 'Inquiry Email Subject' } },
            { name: 'defaultInquiryText', type: 'textarea', localized: true, defaultValue: 'Hello BOB-LIFT, I would like to request a quotation.', label: { zh: '默认询盘开头', en: 'Default Inquiry Intro' } },
            {
              name: 'inquiryNotificationEnabled',
              type: 'checkbox',
              defaultValue: true,
              label: { zh: '启用询盘通知', en: 'Enable Inquiry Notification' },
            },
            {
              name: 'inquiryNotificationRecipients',
              type: 'textarea',
              admin: {
                description: {
                  zh: '多个收件人用英文逗号或换行分隔。为空时默认使用上方联系邮箱。',
                  en: 'Separate multiple recipients with commas or line breaks. If empty, the contact email above is used.',
                },
              },
              defaultValue: 'info@bobliftcrane.com',
              label: { zh: '询盘通知收件人', en: 'Inquiry Notification Recipients' },
            },
            {
              name: 'inquiryNotificationSubject',
              type: 'text',
              localized: true,
              defaultValue: 'New BOB-LIFT inquiry: {product}',
              label: { zh: '询盘通知标题模板', en: 'Inquiry Notification Subject Template' },
            },
            {
              name: 'inquiryNotificationIntro',
              type: 'textarea',
              localized: true,
              defaultValue: 'A new inquiry has been saved in the BOB-LIFT admin. Please review and follow up.',
              label: { zh: '询盘通知开头', en: 'Inquiry Notification Intro' },
            },
            {
              name: 'inquiryNotificationWebhookEnvName',
              type: 'text',
              admin: {
                description: {
                  zh: '可选。填写环境变量名，例如 INQUIRY_WEBHOOK_URL；系统会把询盘同步 POST 到该地址。',
                  en: 'Optional. Put an env var name such as INQUIRY_WEBHOOK_URL; the system POSTs inquiry payloads to that URL.',
                },
              },
              defaultValue: 'INQUIRY_WEBHOOK_URL',
              label: { zh: '询盘通知 Webhook 环境变量名', en: 'Inquiry Webhook Env Name' },
            },
          ],
        },
        {
          label: { zh: '导航与语言', en: 'Navigation & Languages' },
          fields: [
            {
              name: 'headerTopBar',
              type: 'group',
              label: { zh: '顶部提示条', en: 'Top Bar' },
              fields: [
                { name: 'highlight', type: 'text', localized: true, defaultValue: 'Global Lifting Solutions.', label: { zh: '强调文案', en: 'Highlight' } },
                { name: 'text', type: 'text', localized: true, defaultValue: 'Exported to 80+ Countries Worldwide.', label: { zh: '说明文案', en: 'Text' } },
                { name: 'linkLabel', type: 'text', localized: true, defaultValue: 'Learn More +', label: { zh: '链接文案', en: 'Link Label' } },
                { name: 'linkHref', type: 'text', defaultValue: '/about', label: { zh: '链接地址', en: 'Link URL' } },
              ],
            },
            {
              name: 'navigationItems',
              type: 'array',
              admin: { initCollapsed: true },
              fields: [
                { name: 'label', type: 'text', localized: true, required: true, label: { zh: '名称', en: 'Label' } },
                { name: 'href', type: 'text', required: true, label: { zh: '链接', en: 'URL' } },
                { name: 'hasMenu', type: 'checkbox', defaultValue: false, label: { zh: '显示下拉箭头', en: 'Has Dropdown' } },
                { name: 'showInHeader', type: 'checkbox', defaultValue: true, label: { zh: '显示在顶部导航', en: 'Show in Header' } },
                { name: 'sortOrder', type: 'number', defaultValue: 100, label: { zh: '排序值', en: 'Sort Order' } },
              ],
              label: { zh: '顶部导航项', en: 'Header Navigation Items' },
            },
            {
              name: 'productMenuBackgroundImage',
              type: 'upload',
              admin: { description: { zh: '顶部 Products 大菜单的浅背景图。为空时使用备用路径。', en: 'Subtle background image for the Products mega menu.' } },
              label: { zh: '产品大菜单背景图', en: 'Product Menu Background Image' },
              relationTo: 'media',
            },
            {
              name: 'productMenuBackgroundImagePath',
              type: 'text',
              defaultValue: '/images/telescopic-banner.jpg',
              label: { zh: '产品菜单背景备用路径', en: 'Product Menu Background Fallback Path' },
            },
            {
              name: 'productMenuGroups',
              type: 'array',
              admin: { description: { zh: '顶部 Products 大菜单的三组标题、说明和链接。', en: 'Top Products mega-menu group titles and descriptions.' }, initCollapsed: true },
              fields: [
                {
                  name: 'groupKey',
                  type: 'text',
                  admin: {
                    description: {
                      zh: '自由填写分组标识；产品分类里的“产品导航分组”填写同一个值即可归入该组。',
                      en: 'Free group key. Product categories with the same Mega Menu Group value are placed here.',
                    },
                  },
                  label: { zh: '分组标识', en: 'Group Key' },
                  required: true,
                },
                { name: 'title', type: 'text', localized: true, label: { zh: '分组标题', en: 'Group Title' }, required: true },
                { name: 'description', type: 'text', localized: true, label: { zh: '分组说明', en: 'Group Description' } },
                { name: 'href', type: 'text', defaultValue: '/products', label: { zh: '分组链接', en: 'Group URL' } },
                { name: 'iconName', type: 'text', label: { zh: '图标名', en: 'Icon Name' } },
                { name: 'sortOrder', type: 'number', defaultValue: 100, label: { zh: '排序值', en: 'Sort Order' } },
              ],
              label: { zh: '产品大菜单分组', en: 'Product Mega Menu Groups' },
            },
            {
              name: 'languageOptions',
              type: 'array',
              admin: {
                description: { zh: '前台语言下拉菜单。英语永远可用；其他语言必须“翻译状态=可上线”且完成度至少 95%，即使误开“启用”也会自动保持前台不可见。', en: 'Front-end language menu options. English is always available. Other locales are only public when ready and at least 95% complete.' },
                initCollapsed: true,
              },
              defaultValue: defaultLanguageOptions,
              fields: [
                { name: 'locale', type: 'select', label: { zh: '语言代码', en: 'Locale' }, options: localeOptions, required: true },
                { name: 'label', type: 'text', localized: true, label: { zh: '显示名称', en: 'Display Name' }, required: true },
                { name: 'market', type: 'text', localized: true, label: { zh: '市场说明', en: 'Market Text' } },
                { name: 'flag', type: 'text', label: { zh: '旗帜图片路径', en: 'Flag Image Path' }, required: true },
                { name: 'direction', type: 'select', defaultValue: 'ltr', label: { zh: '文字方向', en: 'Text Direction' }, options: directionOptions, required: true },
                {
                  name: 'translationStatus',
                  type: 'select',
                  admin: { description: { zh: '用于判断该语言内容是否已经可上线。非英语语言未达到 ready + 95% 时，系统会自动禁止前台显示。', en: 'Editorial translation readiness. Non-English locales are hidden until ready and at least 95% complete.' } },
                  defaultValue: 'in-progress',
                  label: { zh: '翻译状态', en: 'Translation Status' },
                  options: translationStatusOptions,
                  required: true,
                },
                { name: 'completionPercent', type: 'number', defaultValue: 0, min: 0, max: 100, label: { zh: '翻译完成度 %', en: 'Translation Completion %' } },
                { name: 'translationOwner', type: 'text', label: { zh: '翻译负责人', en: 'Translation Owner' } },
                { name: 'lastReviewedAt', type: 'date', label: { zh: '最后复核时间', en: 'Last Reviewed At' } },
                { name: 'enabled', type: 'checkbox', defaultValue: true, label: { zh: '启用', en: 'Enabled' } },
                { name: 'sortOrder', type: 'number', defaultValue: 100, label: { zh: '排序值', en: 'Sort Order' } },
              ],
              label: { zh: '语言菜单', en: 'Language Menu' },
            },
            {
              name: 'chromeUi',
              type: 'group',
              label: { zh: '全站导航与浮窗文案', en: 'Chrome UI Text' },
              fields: [
                { name: 'languageMenuAriaLabel', type: 'text', localized: true, defaultValue: defaultChromeUiContent.languageMenuAriaLabel, label: { zh: '语言按钮辅助文案', en: 'Language Button Aria Label' } },
                { name: 'mainNavigationLabel', type: 'text', localized: true, defaultValue: defaultChromeUiContent.mainNavigationLabel, label: { zh: '主导航辅助文案', en: 'Main Navigation Aria Label' } },
                { name: 'logoHomeLabel', type: 'text', localized: true, defaultValue: defaultChromeUiContent.logoHomeLabel, label: { zh: 'Logo 首页链接说明', en: 'Logo Home Label' } },
                { name: 'logoImageAlt', type: 'text', localized: true, defaultValue: defaultChromeUiContent.logoImageAlt, label: { zh: 'Logo 图片说明', en: 'Logo Image Alt' } },
                { name: 'mobileMenuAriaLabel', type: 'text', localized: true, defaultValue: defaultChromeUiContent.mobileMenuAriaLabel, label: { zh: '移动端菜单按钮辅助文案', en: 'Mobile Menu Aria Label' } },
                { name: 'mobileMenuEyebrow', type: 'text', localized: true, defaultValue: defaultChromeUiContent.mobileMenuEyebrow, label: { zh: '移动端菜单小标题', en: 'Mobile Menu Eyebrow' } },
                { name: 'mobileMenuTitle', type: 'text', localized: true, defaultValue: defaultChromeUiContent.mobileMenuTitle, label: { zh: '移动端菜单标题', en: 'Mobile Menu Title' } },
                { name: 'mobileProductCategoriesLabel', type: 'text', localized: true, defaultValue: defaultChromeUiContent.mobileProductCategoriesLabel, label: { zh: '移动端产品分类标题', en: 'Mobile Product Categories Label' } },
                { name: 'requestQuoteLabel', type: 'text', localized: true, defaultValue: defaultChromeUiContent.requestQuoteLabel, label: { zh: '询盘按钮文案', en: 'Request Quote Label' } },
                { name: 'productCatalogTitle', type: 'text', localized: true, defaultValue: defaultChromeUiContent.productCatalogTitle, label: { zh: '产品菜单底部标题', en: 'Product Catalog Title' } },
                { name: 'productCatalogDescription', type: 'text', localized: true, defaultValue: defaultChromeUiContent.productCatalogDescription, label: { zh: '产品菜单底部说明', en: 'Product Catalog Description' } },
                { name: 'viewProductCatalogLabel', type: 'text', localized: true, defaultValue: defaultChromeUiContent.viewProductCatalogLabel, label: { zh: '查看产品目录按钮', en: 'View Product Catalog Label' } },
                { name: 'floatingQuoteLabel', type: 'text', localized: true, defaultValue: defaultChromeUiContent.floatingQuoteLabel, label: { zh: '浮窗询盘短文案', en: 'Floating Quote Short Label' } },
                { name: 'floatingQuoteTooltip', type: 'text', localized: true, defaultValue: defaultChromeUiContent.floatingQuoteTooltip, label: { zh: '浮窗询盘提示', en: 'Floating Quote Tooltip' } },
                { name: 'floatingEmailLabel', type: 'text', localized: true, defaultValue: defaultChromeUiContent.floatingEmailLabel, label: { zh: '浮窗邮箱文案', en: 'Floating Email Label' } },
                { name: 'floatingWhatsAppLabel', type: 'text', localized: true, defaultValue: defaultChromeUiContent.floatingWhatsAppLabel, label: { zh: '浮窗 WhatsApp 文案', en: 'Floating WhatsApp Label' } },
                { name: 'floatingCallLabel', type: 'text', localized: true, defaultValue: defaultChromeUiContent.floatingCallLabel, label: { zh: '浮窗电话文案', en: 'Floating Call Label' } },
                { name: 'floatingWeChatLabel', type: 'text', localized: true, defaultValue: defaultChromeUiContent.floatingWeChatLabel, label: { zh: '浮窗微信提示', en: 'Floating WeChat Label' } },
                { name: 'weChatTitle', type: 'text', localized: true, defaultValue: defaultChromeUiContent.weChatTitle, label: { zh: '微信弹窗标题', en: 'WeChat Popup Title' } },
                { name: 'weChatDescriptionTemplate', type: 'text', localized: true, defaultValue: defaultChromeUiContent.weChatDescriptionTemplate, label: { zh: '微信弹窗说明模板', en: 'WeChat Description Template' } },
                { name: 'weChatQrAlt', type: 'text', localized: true, defaultValue: defaultChromeUiContent.weChatQrAlt, label: { zh: '微信二维码图片说明', en: 'WeChat QR Image Alt' } },
                { name: 'closeWeChatLabel', type: 'text', localized: true, defaultValue: defaultChromeUiContent.closeWeChatLabel, label: { zh: '关闭微信弹窗辅助文案', en: 'Close WeChat Aria Label' } },
                { name: 'backToTopLabel', type: 'text', localized: true, defaultValue: defaultChromeUiContent.backToTopLabel, label: { zh: '返回顶部辅助文案', en: 'Back To Top Aria Label' } },
              ],
            },
          ],
        },
        {
          label: { zh: '页脚', en: 'Footer' },
          fields: [
            { name: 'footerIntro', type: 'textarea', localized: true, defaultValue: 'Engineered lifting solutions for global projects. Built to lift. Built to last.', label: { zh: '品牌简介', en: 'Brand Intro' } },
            {
              name: 'socialLinks',
              type: 'array',
              admin: { initCollapsed: true },
              fields: [
                { name: 'label', type: 'text', localized: true, required: true, label: { zh: '名称', en: 'Label' } },
                { name: 'href', type: 'text', required: true, label: { zh: '链接', en: 'URL' } },
              ],
              label: { zh: '社交链接', en: 'Social Links' },
            },
            {
              name: 'footerGroups',
              type: 'array',
              admin: { initCollapsed: true },
              fields: [
                { name: 'title', type: 'text', localized: true, required: true, label: { zh: '分组标题', en: 'Group Title' } },
                {
                  name: 'links',
                  type: 'array',
                  admin: { initCollapsed: true },
                  fields: [
                    { name: 'label', type: 'text', localized: true, required: true, label: { zh: '链接名称', en: 'Link Label' } },
                    { name: 'href', type: 'text', required: true, label: { zh: '链接地址', en: 'Link URL' } },
                  ],
                  label: { zh: '链接', en: 'Links' },
                },
              ],
              label: { zh: '页脚分组', en: 'Footer Groups' },
            },
            { name: 'footerContactTitle', type: 'text', localized: true, defaultValue: 'Contact Us', label: { zh: '联系列标题', en: 'Contact Column Title' } },
            { name: 'copyright', type: 'text', localized: true, defaultValue: '© 2026 BOB-LIFT. All Rights Reserved.', label: { zh: '版权', en: 'Copyright' } },
            {
              name: 'legalLinks',
              type: 'array',
              admin: { initCollapsed: true },
              fields: [
                { name: 'label', type: 'text', localized: true, required: true, label: { zh: '链接名称', en: 'Link Label' } },
                { name: 'href', type: 'text', required: true, label: { zh: '链接地址', en: 'Link URL' } },
              ],
              label: { zh: '底部政策链接', en: 'Legal Links' },
            },
          ],
        },
      ],
    },
  ],
};
