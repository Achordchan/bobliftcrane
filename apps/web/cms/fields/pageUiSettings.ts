import type { Field } from 'payload';

import { defaultPageUiContent } from '../../lib/site-ui';

const text = (name: string, labelZh: string, labelEn: string, defaultValue: string): Field => ({
  name,
  type: 'text',
  localized: true,
  defaultValue,
  label: { zh: labelZh, en: labelEn },
});

const textarea = (name: string, labelZh: string, labelEn: string, defaultValue: string): Field => ({
  name,
  type: 'textarea',
  localized: true,
  defaultValue,
  label: { zh: labelZh, en: labelEn },
});

const textPlain = (name: string, labelZh: string, labelEn: string, defaultValue: string): Field => ({
  name,
  type: 'text',
  defaultValue,
  label: { zh: labelZh, en: labelEn },
});

const textareaPlain = (name: string, labelZh: string, labelEn: string, defaultValue: string): Field => ({
  name,
  type: 'textarea',
  defaultValue,
  label: { zh: labelZh, en: labelEn },
});

const quoteFieldControl = (
  name: keyof typeof defaultPageUiContent.quoteForm.fieldControls,
  labelZh: string,
  labelEn: string,
): Field => ({
  name,
  type: 'group',
  label: { zh: labelZh, en: labelEn },
  fields: [
    { name: 'visible', type: 'checkbox', defaultValue: defaultPageUiContent.quoteForm.fieldControls[name].visible, label: { zh: '显示字段', en: 'Show Field' } },
    { name: 'required', type: 'checkbox', defaultValue: defaultPageUiContent.quoteForm.fieldControls[name].required, label: { zh: '设为必填', en: 'Required' } },
  ],
});

export const pageUiSettingsFields: Field[] = [
  {
    name: 'seoFallbacksUi',
    type: 'group',
    label: { zh: '搜索结果默认文案', en: 'Search and Default Text' },
    fields: [
      textPlain('siteTitle', '站点默认标题', 'Site Default Title', defaultPageUiContent.seoFallbacks.siteTitle),
      textareaPlain('siteDescription', '站点默认描述', 'Site Default Description', defaultPageUiContent.seoFallbacks.siteDescription),
      textPlain('genericTitle', '通用默认标题', 'Generic Backup Title', defaultPageUiContent.seoFallbacks.genericTitle),
      textareaPlain('genericDescription', '通用默认描述', 'Generic Backup Description', defaultPageUiContent.seoFallbacks.genericDescription),
      textPlain('productTitle', '产品详情默认标题', 'Product Backup Title', defaultPageUiContent.seoFallbacks.productTitle),
      textPlain('productTitleTemplate', '产品详情标题模板', 'Product Title Template', defaultPageUiContent.seoFallbacks.productTitleTemplate),
      textareaPlain('productDescription', '产品详情默认描述', 'Product Backup Description', defaultPageUiContent.seoFallbacks.productDescription),
      textareaPlain('productDescriptionTemplate', '产品详情描述模板', 'Product Description Template', defaultPageUiContent.seoFallbacks.productDescriptionTemplate),
      textPlain('productsTitle', '产品列表默认标题', 'Products Backup Title', defaultPageUiContent.seoFallbacks.productsTitle),
      textPlain('productsTitleTemplate', '产品列表标题模板', 'Products Title Template', defaultPageUiContent.seoFallbacks.productsTitleTemplate),
      textareaPlain('productsDescription', '产品列表默认描述', 'Products Backup Description', defaultPageUiContent.seoFallbacks.productsDescription),
      textareaPlain('productCategoryDescriptionTemplate', '产品分类描述模板', 'Product Category Description Template', defaultPageUiContent.seoFallbacks.productCategoryDescriptionTemplate),
      textareaPlain('productNavGroupDescription', '产品导航分组默认描述', 'Product Navigation Group Backup Description', defaultPageUiContent.seoFallbacks.productNavGroupDescription),
      textPlain('articleTitle', '新闻详情默认标题', 'Article Backup Title', defaultPageUiContent.seoFallbacks.articleTitle),
      textPlain('articleTitleTemplate', '新闻详情标题模板', 'Article Title Template', defaultPageUiContent.seoFallbacks.articleTitleTemplate),
      textareaPlain('articleDescription', '新闻详情默认描述', 'Article Backup Description', defaultPageUiContent.seoFallbacks.articleDescription),
      textareaPlain('articleExcerpt', '新闻摘要默认文案', 'Article Excerpt Backup', defaultPageUiContent.seoFallbacks.articleExcerpt),
      textPlain('caseTitle', '案例详情默认标题', 'Case Backup Title', defaultPageUiContent.seoFallbacks.caseTitle),
      textPlain('caseTitleTemplate', '案例详情标题模板', 'Case Title Template', defaultPageUiContent.seoFallbacks.caseTitleTemplate),
      textareaPlain('caseDescription', '案例详情默认描述', 'Case Backup Description', defaultPageUiContent.seoFallbacks.caseDescription),
      textareaPlain('caseSummary', '案例摘要默认文案', 'Case Summary Backup', defaultPageUiContent.seoFallbacks.caseSummary),
    ],
  },
  {
    name: 'contactPageUi',
    type: 'group',
    label: { zh: '联系页文案', en: 'Contact Page Text' },
    fields: [
      text('metaTitle', '搜索结果标题', 'Search Result Title', defaultPageUiContent.contactPage.metaTitle),
      textarea('metaDescription', '搜索结果描述', 'Search Result Description', defaultPageUiContent.contactPage.metaDescription),
      { name: 'canonicalPath', type: 'text', defaultValue: defaultPageUiContent.contactPage.canonicalPath, label: { zh: '标准页面地址', en: 'Canonical Path' } },
      text('heroLabel', '顶部区域标签', 'Hero Label', defaultPageUiContent.contactPage.heroLabel),
      text('heroTitle', '顶部区域标题', 'Hero Title', defaultPageUiContent.contactPage.heroTitle),
      textarea('heroDescription', '顶部区域说明', 'Hero Description', defaultPageUiContent.contactPage.heroDescription),
      { name: 'heroImage', type: 'text', defaultValue: defaultPageUiContent.contactPage.heroImage, label: { zh: '顶部区域图片路径', en: 'Hero Image Path' } },
      {
        name: 'cardLabels',
        type: 'group',
        label: { zh: '联系卡片标签', en: 'Contact Card Labels' },
        fields: [
          text('email', '邮箱标签', 'Email Label', defaultPageUiContent.contactPage.cardLabels.email),
          text('phone', '电话标签', 'Phone Label', defaultPageUiContent.contactPage.cardLabels.phone),
          text('whatsapp', 'WhatsApp 标签', 'WhatsApp Label', defaultPageUiContent.contactPage.cardLabels.whatsapp),
          text('address', '地址标签', 'Address Label', defaultPageUiContent.contactPage.cardLabels.address),
        ],
      },
      text('whatsappValue', 'WhatsApp 显示值', 'WhatsApp Display Value', defaultPageUiContent.contactPage.whatsappValue),
      text('weChatTitle', '微信区标题', 'WeChat Section Title', defaultPageUiContent.contactPage.weChatTitle),
      textarea('weChatDescriptionTemplate', '微信说明模板', 'WeChat Description Template', defaultPageUiContent.contactPage.weChatDescriptionTemplate),
      text('weChatQrAlt', '微信二维码 图片说明', 'WeChat QR Alt', defaultPageUiContent.contactPage.weChatQrAlt),
    ],
  },
  {
    name: 'quotePageUi',
    type: 'group',
    label: { zh: '询盘页文案', en: 'Quote Page Text' },
    fields: [
      text('metaTitle', '搜索结果标题', 'Search Result Title', defaultPageUiContent.quotePage.metaTitle),
      textarea('metaDescription', '搜索结果描述', 'Search Result Description', defaultPageUiContent.quotePage.metaDescription),
      { name: 'canonicalPath', type: 'text', defaultValue: defaultPageUiContent.quotePage.canonicalPath, label: { zh: '标准页面地址', en: 'Canonical Path' } },
      text('heroLabel', '顶部区域标签', 'Hero Label', defaultPageUiContent.quotePage.heroLabel),
      text('heroTitle', '顶部区域标题', 'Hero Title', defaultPageUiContent.quotePage.heroTitle),
      textarea('heroDescription', '顶部区域说明', 'Hero Description', defaultPageUiContent.quotePage.heroDescription),
      { name: 'heroImage', type: 'text', defaultValue: defaultPageUiContent.quotePage.heroImage, label: { zh: '顶部区域图片路径', en: 'Hero Image Path' } },
      text('workflowLabel', '流程小标题', 'Workflow Label', defaultPageUiContent.quotePage.workflowLabel),
      text('workflowTitle', '流程标题', 'Workflow Title', defaultPageUiContent.quotePage.workflowTitle),
      {
        name: 'workflowItems',
        type: 'array',
        defaultValue: defaultPageUiContent.quotePage.workflowItems,
        fields: [
          { name: 'iconName', type: 'select', defaultValue: 'file-text', label: { zh: '图标', en: 'Icon' }, options: ['file-text', 'mail-check', 'message-circle'] },
          text('title', '标题', 'Title', ''),
          textarea('text', '说明', 'Text', ''),
        ],
        label: { zh: '流程步骤', en: 'Workflow Items' },
      },
      text('productLinkLabel', '产品页链接文案', 'Product Link Label', defaultPageUiContent.quotePage.productLinkLabel),
      { name: 'productLinkHref', type: 'text', defaultValue: defaultPageUiContent.quotePage.productLinkHref, label: { zh: '产品页链接', en: 'Product Link URL' } },
    ],
  },
  {
    name: 'quoteFormUi',
    type: 'group',
    label: { zh: '询盘表单文案', en: 'Quote Form Text' },
    fields: [
      text('label', '表单小标题', 'Form Label', defaultPageUiContent.quoteForm.label),
      text('title', '表单标题', 'Form Title', defaultPageUiContent.quoteForm.title),
      text('badge', '表单角标', 'Form Badge', defaultPageUiContent.quoteForm.badge),
      {
        name: 'productOptionLimit',
        type: 'number',
        defaultValue: defaultPageUiContent.quoteForm.productOptionLimit,
        min: 0,
        max: 500,
        admin: {
          description: { zh: '询盘表单产品下拉最多展示多少个产品；填 0 表示展示全部已公开产品。', en: 'Maximum products in the inquiry dropdown. Use 0 to show all published products.' },
        },
        label: { zh: '产品下拉最大数量', en: 'Product Dropdown Limit' },
      },
      {
        name: 'fields',
        type: 'group',
        label: { zh: '字段标签', en: 'Field Labels' },
        fields: Object.entries(defaultPageUiContent.quoteForm.fields).map(([name, defaultValue]) => text(name, name, name, defaultValue)),
      },
      {
        name: 'buttons',
        type: 'group',
        label: { zh: '按钮文案', en: 'Button Labels' },
        fields: Object.entries(defaultPageUiContent.quoteForm.buttons).map(([name, defaultValue]) => text(name, name, name, defaultValue)),
      },
      {
        name: 'fieldControls',
        type: 'group',
        admin: {
          description: {
            zh: '控制询盘表单每个字段是否显示、是否必填。注意：如果启用“保存到后台”，姓名和邮箱会被网站强制显示并必填，避免询盘无法入库。',
            en: 'Control visibility and required state for each inquiry field. Name and email remain visible and required when Save to Admin is enabled.',
          },
        },
        label: { zh: '字段显示与必填控制', en: 'Field Visibility and Required Controls' },
        fields: [
          quoteFieldControl('name', '姓名字段', 'Name Field'),
          quoteFieldControl('email', '邮箱字段', 'Email Field'),
          quoteFieldControl('phone', '电话 / WhatsApp 字段', 'Phone / WhatsApp Field'),
          quoteFieldControl('country', '国家/地区字段', 'Country Field'),
          quoteFieldControl('product', '产品兴趣字段', 'Product Interest Field'),
          quoteFieldControl('quantity', '预计数量字段', 'Quantity Field'),
          quoteFieldControl('message', '项目需求字段', 'Message Field'),
        ],
      },
      {
        name: 'buttonChannels',
        type: 'group',
        admin: {
          description: { zh: '控制表单底部显示哪些提交渠道。至少保留一个渠道；如果全部关闭，网站会保留“保存询盘”。', en: 'Controls which inquiry channels are visible.' },
        },
        label: { zh: '提交渠道开关', en: 'Submit Channel Visibility' },
        fields: [
          { name: 'save', type: 'checkbox', defaultValue: defaultPageUiContent.quoteForm.buttonChannels.save, label: { zh: '保存到后台', en: 'Save to Admin' } },
          { name: 'email', type: 'checkbox', defaultValue: defaultPageUiContent.quoteForm.buttonChannels.email, label: { zh: '显示邮件按钮', en: 'Show Email Button' } },
          { name: 'whatsapp', type: 'checkbox', defaultValue: defaultPageUiContent.quoteForm.buttonChannels.whatsapp, label: { zh: '显示 WhatsApp 按钮', en: 'Show WhatsApp Button' } },
        ],
      },
      {
        name: 'messages',
        type: 'group',
        label: { zh: '提示文案', en: 'Messages' },
        fields: Object.entries(defaultPageUiContent.quoteForm.messages).map(([name, defaultValue]) => textarea(name, name, name, defaultValue)),
      },
      {
        name: 'inquiryLineLabels',
        type: 'group',
        label: { zh: '询盘草稿字段名', en: 'Inquiry Draft Labels' },
        fields: Object.entries(defaultPageUiContent.quoteForm.inquiryLineLabels).map(([name, defaultValue]) => text(name, name, name, defaultValue)),
      },
    ],
  },
  {
    name: 'newsPageUi',
    type: 'group',
    label: { zh: '新闻列表页文案', en: 'News Page Text' },
    fields: [
      text('metaTitle', '搜索结果标题', 'Search Result Title', defaultPageUiContent.newsPage.metaTitle),
      textarea('metaDescription', '搜索结果描述', 'Search Result Description', defaultPageUiContent.newsPage.metaDescription),
      { name: 'canonicalPath', type: 'text', defaultValue: defaultPageUiContent.newsPage.canonicalPath, label: { zh: '标准页面地址', en: 'Canonical Path' } },
      text('heroLabel', '顶部区域标签', 'Hero Label', defaultPageUiContent.newsPage.heroLabel),
      text('heroTitle', '顶部区域标题', 'Hero Title', defaultPageUiContent.newsPage.heroTitle),
      textarea('heroDescription', '顶部区域说明', 'Hero Description', defaultPageUiContent.newsPage.heroDescription),
      { name: 'heroImage', type: 'text', defaultValue: defaultPageUiContent.newsPage.heroImage, label: { zh: '顶部区域图片路径', en: 'Hero Image Path' } },
      text('readFeaturedLabel', '头条按钮', 'Read Featured Label', defaultPageUiContent.newsPage.readFeaturedLabel),
      text('requestQuoteLabel', '询盘按钮', 'Request Quote Label', defaultPageUiContent.newsPage.requestQuoteLabel),
      text('featuredLabel', '头条标签', 'Featured Label', defaultPageUiContent.newsPage.featuredLabel),
      text('readArticleLabel', '阅读全文按钮', 'Read Article Label', defaultPageUiContent.newsPage.readArticleLabel),
      text('readMoreLabel', '卡片更多按钮', 'Read More Label', defaultPageUiContent.newsPage.readMoreLabel),
    ],
  },
  {
    name: 'newsDetailUi',
    type: 'group',
    label: { zh: '新闻详情页文案', en: 'News Detail Text' },
    fields: [
      text('backLabel', '返回按钮', 'Back Label', defaultPageUiContent.newsDetail.backLabel),
      text('relatedLabel', '相关推荐标题', 'Related Label', defaultPageUiContent.newsDetail.relatedLabel),
      text('quoteCtaLabel', '询盘 按钮', '询盘按钮 Label', defaultPageUiContent.newsDetail.quoteCtaLabel),
      { name: 'relatedArticleCount', type: 'number', defaultValue: defaultPageUiContent.newsDetail.relatedArticleCount, min: 0, max: 12, label: { zh: '相关推荐数量', en: 'Related Article Count' } },
    ],
  },
  {
    name: 'casesPageUi',
    type: 'group',
    label: { zh: '案例列表页文案', en: 'Cases Page Text' },
    fields: [
      text('metaTitle', '搜索结果标题', 'Search Result Title', defaultPageUiContent.casesPage.metaTitle),
      textarea('metaDescription', '搜索结果描述', 'Search Result Description', defaultPageUiContent.casesPage.metaDescription),
      { name: 'canonicalPath', type: 'text', defaultValue: defaultPageUiContent.casesPage.canonicalPath, label: { zh: '标准页面地址', en: 'Canonical Path' } },
      text('heroLabel', '顶部区域标签', 'Hero Label', defaultPageUiContent.casesPage.heroLabel),
      text('heroTitle', '顶部区域标题', 'Hero Title', defaultPageUiContent.casesPage.heroTitle),
      textarea('heroDescription', '顶部区域说明', 'Hero Description', defaultPageUiContent.casesPage.heroDescription),
      { name: 'heroImage', type: 'text', defaultValue: defaultPageUiContent.casesPage.heroImage, label: { zh: '顶部区域图片路径', en: 'Hero Image Path' } },
      text('primaryCtaLabel', '主按钮', 'Primary 按钮 Label', defaultPageUiContent.casesPage.primaryCtaLabel),
      text('secondaryCtaLabel', '次按钮', 'Secondary 按钮 Label', defaultPageUiContent.casesPage.secondaryCtaLabel),
      text('listLabel', '列表小标题', 'List Label', defaultPageUiContent.casesPage.listLabel),
      text('listTitle', '列表标题', 'List Title', defaultPageUiContent.casesPage.listTitle),
      text('cardTypeLabel', '卡片类型标签', 'Card Type Label', defaultPageUiContent.casesPage.cardTypeLabel),
      text('viewCaseLabel', '查看案例按钮', 'View Case Label', defaultPageUiContent.casesPage.viewCaseLabel),
    ],
  },
  {
    name: 'caseDetailUi',
    type: 'group',
    label: { zh: '案例详情页文案', en: 'Case Detail Text' },
    fields: [
      text('backLabel', '返回按钮', 'Back Label', defaultPageUiContent.caseDetail.backLabel),
      text('factsTitle', '事实卡标题', 'Facts Title', defaultPageUiContent.caseDetail.factsTitle),
      text('discussSimilarLabel', '类似项目按钮', 'Discuss Similar Label', defaultPageUiContent.caseDetail.discussSimilarLabel),
    ],
  },
  {
    name: 'aboutPageUi',
    type: 'group',
    label: { zh: 'About 页面文案', en: 'About Page Text' },
    fields: [
      text('metaTitle', '搜索结果标题', 'Search Result Title', defaultPageUiContent.aboutPage.metaTitle),
      textarea('metaDescription', '搜索结果描述', 'Search Result Description', defaultPageUiContent.aboutPage.metaDescription),
      { name: 'canonicalPath', type: 'text', defaultValue: defaultPageUiContent.aboutPage.canonicalPath, label: { zh: '标准页面地址', en: 'Canonical Path' } },
      text('primaryCtaLabel', '主按钮', 'Primary 按钮 Label', defaultPageUiContent.aboutPage.primaryCtaLabel),
      text('secondaryCtaLabel', '次按钮', 'Secondary 按钮 Label', defaultPageUiContent.aboutPage.secondaryCtaLabel),
      text('strengthLabel', '实力区小标题', 'Strength Label', defaultPageUiContent.aboutPage.strengthLabel),
      text('strengthTitle', '实力区标题', 'Strength Title', defaultPageUiContent.aboutPage.strengthTitle),
      text('timelineLabel', '时间线小标题', 'Timeline Label', defaultPageUiContent.aboutPage.timelineLabel),
      { name: 'galleryImageCount', type: 'number', defaultValue: defaultPageUiContent.aboutPage.galleryImageCount, min: 1, max: 12, label: { zh: '图库展示数量', en: 'Gallery Image Count' } },
    ],
  },
  {
    name: 'contentLabelsUi',
    type: 'group',
    label: { zh: '内容默认标签', en: 'Content Default Labels' },
    fields: [
      {
        name: 'articleCategories',
        type: 'group',
        label: { zh: '新闻分类显示名', en: 'Article Category Labels' },
        fields: Object.entries(defaultPageUiContent.contentLabels.articleCategories).map(([name, defaultValue]) => text(name, name, name, defaultValue)),
      },
      {
        name: 'caseFacts',
        type: 'group',
        label: { zh: '案例默认事实标签', en: 'Case Fact Labels' },
        fields: Object.entries(defaultPageUiContent.contentLabels.caseFacts).map(([name, defaultValue]) => text(name, name, name, defaultValue)),
      },
    ],
  },
];
