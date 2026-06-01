import type { CollectionConfig } from 'payload';

import { isAdmin } from '../access/isAdmin';
import { legacyAliasesField } from '../fields/legacyAliases';
import { productDrawingImagesField } from '../fields/productDrawingImages';
import { publishedOrAdmin } from '../access/publishedOrAdmin';
import { seoFields } from '../fields/seo';
import { slugField } from '../fields/slug';
import { statusField } from '../fields/status';
import { syncProductImportBatchStats } from '../hooks/syncProductImportBatchStats';
import { validateProductFrontendDictionaries } from '../hooks/validateProductFrontendDictionaries';
import { validateUniqueLegacyPaths } from '../hooks/validateUniqueLegacyPaths';
import { productDrawingReviewStats, productFrontendChecklist, type ProductDraftData } from '../lib/productReviewReadiness';

const productCategoryOptions = [
  { label: { zh: '伸缩臂随车起重机', en: 'Telescopic Boom Truck Mounted Cranes' }, value: 'telescopic-boom-truck-mounted-cranes' },
  { label: { zh: '折臂随车起重机', en: 'Knuckle Boom Truck Mounted Cranes' }, value: 'knuckle-boom-truck-mounted-cranes' },
  { label: { zh: '折臂船用起重机', en: 'Knuckle Boom Marine Cranes' }, value: 'knuckle-boom-marine-cranes' },
  { label: { zh: '伸缩臂船用起重机', en: 'Telescopic Boom Marine Cranes' }, value: 'telescopic-boom-marine-cranes' },
  { label: { zh: '高空作业平台', en: 'Aerial Work Platform' }, value: 'aerial-work-platform' },
  { label: { zh: '皮卡起重机', en: 'Pickup Cranes' }, value: 'pickup-cranes' },
  { label: { zh: '叉车飞臂吊', en: 'Forklift Fly Jib Cranes' }, value: 'forklift-fly-jib-cranes' },
  { label: { zh: '迷你自卸车', en: 'Mini Dumpers' }, value: 'mini-dumpers' },
  { label: { zh: '起重机配件', en: 'Crane Accessories' }, value: 'crane-accessories' },
  { label: { zh: '其他起重机', en: 'Other Cranes' }, value: 'other-cranes' },
];

export const Products: CollectionConfig = {
  slug: 'products',
  labels: {
    singular: '产品管理',
    plural: '产品管理',
  },
  access: {
    create: isAdmin,
    delete: isAdmin,
    read: publishedOrAdmin,
    update: isAdmin,
  },
  admin: {
    hideAPIURL: true,
    defaultColumns: ['title', 'frontendContentStatus', 'model', 'categoryRef', 'capacity', 'parameterReviewStatus', 'showInProductList', 'showOnHome', 'status'],
    description: {
      zh: '日常上传和编辑产品。先建产品分类，再填写产品名称、型号、参数、图片、卖点和是否在网站显示。右侧会自动提示这个产品能不能出现在首页和产品列表。',
      en: 'Upload and edit products here. This controls product listing, detail page, home recommendations, specifications, drawings, gallery, features, and FAQ. Manage categories from Product Categories in this same group first, then select Product Category here. Boom type, applications, and availability are validated against Product Listing Settings dictionaries.',
    },
    group: { zh: '01 产品管理', en: '01 Product Management' },
    listSearchableFields: ['title', 'slug', 'model', 'sourceUrl'],
    preview: ({ legacyPath, slug }) => {
      if (typeof legacyPath === 'string' && legacyPath.startsWith('/')) return legacyPath;
      return typeof slug === 'string' && slug ? `/products/${slug}` : '/products';
    },
    useAsTitle: 'title',
  },
  defaultSort: 'sortOrder',
  hooks: {
    beforeValidate: [
      validateUniqueLegacyPaths('products'),
      validateProductFrontendDictionaries,
      ({ data, originalDoc }) => {
        if (!data) return data;
        const nextData = { ...(originalDoc as ProductDraftData | undefined), ...data };
        const checklist = productFrontendChecklist(nextData);
        const drawingReview = productDrawingReviewStats(nextData.drawingImages);
        return {
          ...data,
          frontendContentStatus: checklist.status,
          frontendMissingFieldsNote: checklist.note,
          drawingReviewStatus: drawingReview.status,
          drawingReviewSummary: drawingReview.summary,
          drawingReviewNeededCount: drawingReview.neededCount,
          drawingReviewedCount: drawingReview.reviewedCount,
        };
      },
    ],
    afterChange: [
      async ({ doc, req }) => {
        const batchId = typeof doc.importBatchId === 'string' ? doc.importBatchId.trim() : '';
        if (!batchId) return doc;
        await syncProductImportBatchStats(req.payload, batchId);
        return doc;
      },
    ],
  },
  fields: [
    {
      type: 'tabs',
      tabs: [
        {
          label: { zh: '基础信息', en: 'Basic Info' },
          fields: [
            { name: 'title', type: 'text', localized: true, label: { zh: '产品名称', en: 'Product Name' }, required: true },
            slugField('title'),
            {
              name: 'legacyPath',
              type: 'text',
              admin: {
                description: {
                  zh: '产品页面地址。为了让改版后链接与旧网站一致，优先填写旧网站真实 .html 路径。',
                  en: 'Current public product URL. Use the original old-site .html path whenever possible. The front end renders the product directly at this path without relying on redirects.',
                },
                position: 'sidebar',
              },
              label: { zh: '产品页面地址', en: 'Public Product URL' },
            },
            legacyAliasesField({
              zh: '同一产品曾经用过的其他旧地址，一般不用改。主页面地址填上方“产品页面地址”。',
              en: 'Other historical paths for this product. Put the main public URL in Public Product URL above. These aliases also render the product detail page directly.',
            }),
            {
              name: 'categoryRef',
              type: 'relationship',
              admin: {
                description: {
                  zh: '选择产品属于哪个分类。这里会影响产品菜单、产品列表筛选和首页分类展示。',
                  en: 'The real editable category used by front-end navigation, filters, and home category sections. Only published front-end categories can be selected here.',
                },
              },
              filterOptions: {
                and: [
                  { status: { equals: 'published' } },
                  { pageType: { not_equals: 'redirect-only' } },
                ],
              },
              label: { zh: '产品分类', en: 'Product Category' },
              relationTo: 'product-categories',
              required: true,
            },
            { name: 'model', type: 'text', label: { zh: '型号', en: 'Model' } },
            {
              name: 'category',
              type: 'select',
              admin: {
                description: { zh: '旧数据保留字段，平时不用填写。', en: 'Legacy field kept for old data.' },
                hidden: true,
              },
              defaultValue: 'other-cranes',
              label: { zh: '旧分类（不用填）', en: 'Legacy Category' },
              options: productCategoryOptions,
              required: true,
            },
            { name: 'capacity', type: 'text', label: { zh: '起重能力显示值', en: 'Capacity Label' } },
            {
              name: 'capacityValue',
              type: 'number',
              admin: { description: { zh: '用于列表筛选和排序，只填数字，例如 10、3.2。', en: 'Used by filters and sorting. Number only, e.g. 10 or 3.2.' } },
              label: { zh: '起重能力数值', en: 'Capacity Value' },
            },
            {
              name: 'boomType',
              type: 'text',
              defaultValue: 'custom-solution',
              admin: {
                description: {
                  zh: '用于产品列表筛选、详情页参数和首页卡片。这里填写“产品列表页设置 → 臂架类型筛选项”里的选项值；不确定时先不要改。',
                  en: 'Used by listing filters, detail specs, and home cards. Enter a value from Product Listing Settings → Boom Type Filter Options, e.g. telescopic-boom. New dictionary values can be used here directly.',
                },
              },
              label: { zh: '臂架类型', en: 'Boom Type' },
              required: true,
            },
            { name: 'productType', type: 'text', localized: true, label: { zh: '产品类型补充', en: 'Type Label' } },
            { name: 'intro', type: 'textarea', localized: true, label: { zh: '简短介绍', en: 'Short Introduction' }, maxLength: 320 },
            {
              name: 'sourceUrl',
              type: 'text',
              admin: {
                description: { zh: '必填。填写旧网站或供应商资料地址，用来核对参数。', en: 'Required source URL used to verify product parameters.' },
                position: 'sidebar',
              },
              label: { zh: '资料来源地址', en: 'Source URL' },
              required: true,
            },
            {
              name: 'importSource',
              type: 'select',
              admin: { position: 'sidebar' },
              defaultValue: 'manual',
              label: { zh: '内容来源', en: 'Content Source' },
              options: [
                { label: { zh: '手动录入', en: 'Manual' }, value: 'manual' },
                { label: { zh: '旧网站导入', en: 'Legacy Site Import' }, value: 'legacy-site' },
                { label: { zh: '智能辅助生成', en: 'AI Assisted' }, value: 'ai-assisted' },
              ],
            },
            {
              name: 'parameterReviewStatus',
              type: 'select',
              admin: {
                description: { zh: '导入后先保持“待核对”；人工对照旧网站原文或厂家资料后，再选“已按来源核对”。图纸只作为资料记录，不影响产品显示。', en: 'Keep imported products as needs review until a human checks the old source text or factory data. Drawing images are supporting records only and no longer block readiness.' },
                position: 'sidebar',
              },
              defaultValue: 'needs-review',
              label: { zh: '参数核对状态', en: 'Parameter Review Status' },
              options: [
                { label: { zh: '待核对', en: 'Needs Review' }, value: 'needs-review' },
                { label: { zh: '核对中', en: 'In Review' }, value: 'in-review' },
                { label: { zh: '已按来源核对', en: 'Verified From Source' }, value: 'verified-from-source' },
                { label: { zh: '退回修改', en: 'Changes Required' }, value: 'changes-required' },
              ],
            },
            { name: 'parameterReviewer', type: 'text', admin: { position: 'sidebar' }, label: { zh: '核对人', en: 'Reviewer' } },
            { name: 'parameterReviewedAt', type: 'date', admin: { position: 'sidebar' }, label: { zh: '核对时间', en: 'Reviewed At' } },
            { name: 'importBatchId', type: 'text', admin: { position: 'sidebar' }, label: { zh: '导入批次', en: 'Import Batch' } },
            { name: 'parameterReviewNotes', type: 'textarea', localized: true, label: { zh: '参数核对备注', en: 'Parameter Review Notes' } },
          ],
        },
        {
          label: { zh: '列表与首页', en: 'Listing & Home' },
          fields: [
            {
              name: 'availability',
              type: 'text',
              defaultValue: 'customizable',
              admin: {
                description: {
                  zh: '控制产品卡片状态标签和列表筛选。这里填写“产品列表页设置 → 可售状态筛选项”里的选项值；不确定时先不要改。',
                  en: 'Controls product-card status badges and listing filters. Enter a value from Product Listing Settings → Availability Filter Options, e.g. customizable. New dictionary values can be used here directly.',
                },
              },
              label: { zh: '列表状态', en: 'Listing Status' },
              required: true,
            },
            {
              name: 'applications',
              type: 'text',
              admin: {
                description: {
                  zh: '用于产品列表筛选和详情页应用场景卡片。每一行填写“产品列表页设置 → 应用场景选项”里的选项值；不确定时先不要改。',
                  en: 'Used by listing filters and detail application cards. Enter values from Product Listing Settings → Application Dictionary, e.g. construction. New dictionary values can be used here directly.',
                },
              },
              hasMany: true,
              label: { zh: '应用场景', en: 'Applications' },
            },
            {
              name: 'tags',
              type: 'array',
              admin: { initCollapsed: true },
              fields: [{ name: 'label', type: 'text', localized: true, label: { zh: '标签', en: 'Tag' }, required: true }],
              label: { zh: '列表标签', en: 'Listing Tags' },
            },
            {
              name: 'showInProductList',
              type: 'checkbox',
              defaultValue: true,
              admin: {
                description: {
                  zh: '关闭后，这个已发布产品仍可通过产品页面地址打开，但不会出现在产品列表页、分类页和筛选结果中。即使开启，也只有“网站显示状态=可显示”的产品会进入列表。',
                  en: 'When disabled, the published product can still be tested by its detail URL, but it will not appear in listing, category, or filter results. Even when enabled, only products with Front-end Content Status = 可显示 enter listing pages.',
                },
              },
              label: { zh: '显示在产品列表页', en: 'Show in Product Listing' },
            },
            {
              name: 'showOnHome',
              type: 'checkbox',
              defaultValue: true,
              admin: {
                description: {
                  zh: '关闭后，这个产品不会进入首页产品区；不影响产品详情页。即使开启，也只有“网站显示状态=可显示”的产品会进入首页。',
                  en: 'When disabled, the product is excluded from the home product section. Detail pages are unaffected. Even when enabled, only products with Front-end Content Status = 可显示 enter the home page.',
                },
              },
              label: { zh: '显示在首页', en: 'Show on Home Page' },
            },
            { name: 'isFeatured', type: 'checkbox', defaultValue: false, label: { zh: '重点产品优先展示', en: 'Featured' } },
            { name: 'popularity', type: 'number', defaultValue: 50, label: { zh: '排序热度', en: 'Popularity' }, min: 0, max: 100 },
            { name: 'sortOrder', type: 'number', defaultValue: 100, label: { zh: '排序值', en: 'Sort Order' }, admin: { position: 'sidebar' } },
          ],
        },
        {
          label: { zh: '图片与详情', en: 'Media & Detail' },
          fields: [
            { name: 'coverImage', type: 'upload', label: { zh: '封面图', en: 'Cover Image' }, relationTo: 'media', required: true },
            {
              name: 'gallery',
              type: 'array',
              fields: [
                { name: 'image', type: 'upload', label: { zh: '图片', en: 'Image' }, relationTo: 'media', required: true },
                { name: 'label', type: 'text', localized: true, label: { zh: '图片说明', en: 'Image Label' } },
              ],
              label: { zh: '产品图库', en: 'Gallery' },
            },
            {
              name: 'detailHotspots',
              type: 'array',
              admin: {
                description: {
                  zh: '产品详情页主图上的标注文字；不填写时使用“产品详情页设置”的默认标注。',
                  en: 'Hotspot labels shown on the product detail hero image. Defaults from Product Detail Settings are used when empty.',
                },
                initCollapsed: true,
              },
              fields: [{ name: 'label', type: 'text', localized: true, label: { zh: '标注文字', en: 'Hotspot Label' }, required: true }],
              label: { zh: '详情页主图标注', en: 'Detail 顶部区域 Hotspots' },
              maxRows: 6,
            },
            {
              name: 'applicationCards',
              type: 'array',
              admin: {
                description: {
                  zh: '可选。为当前产品单独覆盖详情页“应用场景”卡片；不填写时使用产品列表页设置里的应用场景选项。',
                  en: 'Optional per-product application cards for the detail page. If empty, global application dictionary cards are used.',
                },
                initCollapsed: true,
              },
              fields: [
                { name: 'title', type: 'text', localized: true, label: { zh: '卡片标题', en: 'Card Title' }, required: true },
                { name: 'text', type: 'textarea', localized: true, label: { zh: '卡片说明', en: 'Card Text' }, required: true, maxLength: 220 },
                { name: 'image', type: 'upload', label: { zh: '卡片图片', en: 'Card Image' }, relationTo: 'media' },
                { name: 'imagePath', type: 'text', label: { zh: '图片路径（一般不用）', en: 'Backup Image Path' } },
                { name: 'iconName', type: 'text', defaultValue: 'construction', label: { zh: '图标名称（一般不用）', en: 'Icon Key' } },
                { name: 'sortOrder', type: 'number', defaultValue: 100, label: { zh: '排序值', en: 'Sort Order' } },
              ],
              label: { zh: '详情页应用场景卡片', en: 'Detail Application Cards' },
              maxRows: 6,
            },
            productDrawingImagesField,
            {
              name: 'documents',
              type: 'array',
              admin: { initCollapsed: true },
              fields: [
                { name: 'title', type: 'text', localized: true, label: { zh: '文件标题', en: 'Document Title' }, required: true },
                { name: 'file', type: 'upload', label: { zh: '上传文件', en: 'Uploaded File' }, relationTo: 'media' },
                { name: 'url', type: 'text', label: { zh: '外部文件链接', en: 'External Document URL' } },
              ],
              label: { zh: '产品资料链接', en: 'Product Documents' },
            },
            {
              name: 'specs',
              type: 'array',
              admin: { initCollapsed: true },
              fields: [
                { name: 'label', type: 'text', localized: true, label: { zh: '参数名', en: 'Label' }, required: true },
                { name: 'value', type: 'text', localized: true, label: { zh: '参数值', en: 'Value' }, required: true },
                { name: 'sourceNote', type: 'textarea', localized: true, label: { zh: '参数来源说明', en: 'Source Note' } },
                { name: 'sourceImage', type: 'upload', label: { zh: '参数来源图', en: 'Source Image' }, relationTo: 'media' },
              ],
              label: { zh: '规格参数', en: 'Specifications' },
            },
            {
              name: 'keyFeatures',
              type: 'array',
              admin: { initCollapsed: true },
              fields: [
                { name: 'title', type: 'text', localized: true, label: { zh: '卖点标题', en: 'Feature Title' }, required: true },
                { name: 'text', type: 'textarea', localized: true, label: { zh: '卖点说明', en: 'Feature Text' }, required: true, maxLength: 260 },
              ],
              label: { zh: '详情页核心卖点', en: 'Detail Key Features' },
            },
            {
              name: 'faqs',
              type: 'array',
              admin: { initCollapsed: true },
              fields: [
                { name: 'question', type: 'text', localized: true, label: { zh: '问题', en: 'Question' }, required: true },
                { name: 'answer', type: 'textarea', localized: true, label: { zh: '回答', en: 'Answer' }, required: true },
              ],
              label: { zh: '详情页 FAQ', en: 'Detail FAQ' },
            },
          ],
        },
        {
          label: { zh: '搜索设置与发布', en: 'Search Settings & Publish' },
          fields: [
            seoFields,
            statusField,
            { name: 'publishedAt', type: 'date', admin: { position: 'sidebar' }, label: { zh: '发布时间', en: 'Published At' } },
          ],
        },
      ],
    },
    {
      name: 'frontendContentStatus',
      type: 'text',
      admin: {
        description: {
          zh: '系统自动判断产品资料是否完整。显示“可显示”才会进入首页、产品列表和分类页。',
          en: 'Auto calculated: 可显示 can enter home and listing tests; needs-content is missing front-end content; needs-parameter-review still needs parameter verification. Drawing images are evidence records and no longer block public listings. Non-可显示 products are excluded from public listings.',
        },
        position: 'sidebar',
        readOnly: true,
      },
      defaultValue: 'needs-content',
      label: { zh: '网站显示状态', en: 'Front-end Visibility Status' },
    },
    {
      name: 'frontendMissingFieldsNote',
      type: 'textarea',
      admin: {
        description: {
          zh: '系统自动列出会导致产品无法进入首页、产品列表、分类页测试的原因。',
          en: 'Auto lists missing content that affects product listing, detail page, and home page tests.',
        },
        position: 'sidebar',
        readOnly: true,
      },
      label: { zh: '不能显示原因', en: 'Why It Is Not Visible' },
    },
    {
      name: 'drawingReviewStatus',
      type: 'text',
      admin: {
        description: {
          zh: '系统自动计算：当前图纸/参数图只作为资料记录，不影响网站可见性。',
          en: 'Auto calculated drawing evidence status. Drawing images no longer block front-end visibility.',
        },
        position: 'sidebar',
        readOnly: true,
      },
      defaultValue: 'not-required',
      label: { zh: '图纸记录状态', en: 'Drawing Evidence Status' },
    },
    {
      name: 'drawingReviewSummary',
      type: 'textarea',
      admin: {
        description: {
          zh: '系统自动显示本产品图纸/参数图记录数量。',
          en: 'Auto summary for drawing and parameter-image evidence records.',
        },
        position: 'sidebar',
        readOnly: true,
      },
      label: { zh: '图纸记录说明', en: 'Drawing Evidence Summary' },
    },
    {
      name: 'drawingReviewNeededCount',
      type: 'number',
      admin: { position: 'sidebar', readOnly: true },
      defaultValue: 0,
      label: { zh: '图纸待处理数（已停用）', en: 'Drawing Pending Count (Disabled)' },
      min: 0,
    },
    {
      name: 'drawingReviewedCount',
      type: 'number',
      admin: { position: 'sidebar', readOnly: true },
      defaultValue: 0,
      label: { zh: '图纸记录数量', en: 'Drawing Evidence Count' },
      min: 0,
    },
  ],
  timestamps: true,
};
