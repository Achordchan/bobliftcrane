import type { CollectionConfig } from 'payload';

import { isAdmin } from '../access/isAdmin';
import { legacyAliasesField } from '../fields/legacyAliases';
import { publishedOrAdmin } from '../access/publishedOrAdmin';
import { seoFields } from '../fields/seo';
import { slugField } from '../fields/slug';
import { statusField } from '../fields/status';
import { validateUniqueLegacyPaths } from '../hooks/validateUniqueLegacyPaths';

type CategoryDraftData = Record<string, unknown>;

function hasText(value: unknown): boolean {
  if (typeof value === 'string') return value.trim().length > 0;
  if (value && typeof value === 'object') return Object.values(value).some(hasText);
  return false;
}

function categoryFrontendChecklist(data: CategoryDraftData) {
  const blocking: string[] = [];
  const notes: string[] = [];

  if (data.status !== 'published') blocking.push('分类未发布');
  if (data.pageType === 'redirect-only') blocking.push('栏目类型为不在网站展示');
  if (!hasText(data.title)) blocking.push('分类名称');
  if (!hasText(data.slug)) blocking.push('短地址');
  if (!hasText(data.legacyPath)) notes.push('未填写旧网站页面地址，将使用 /products/category/{分类值}');
  if (data.showInMegaMenu === false) notes.push('不进入顶部产品菜单');
  if (data.showOnHome === false) notes.push('不进入首页产品分类');
  if (data.showInProductFilters === false) notes.push('不进入产品列表筛选项，但直接访问分类页仍生效');

  if (blocking.length > 0) {
    return { status: 'hidden', note: blocking.concat(notes).join('、') };
  }

  return {
    status: notes.some((note) => note.includes('页面地址')) ? 'needs-url-review' : 'ready',
    note: notes.length > 0 ? notes.join('、') : '已发布，可在网站对应位置显示',
  };
}

export const ProductCategories: CollectionConfig = {
  slug: 'product-categories',
  labels: {
    singular: '产品分类',
    plural: '产品分类',
  },
  access: {
    create: isAdmin,
    delete: isAdmin,
    read: publishedOrAdmin,
    update: isAdmin,
  },
  admin: {
    hideAPIURL: true,
    defaultColumns: ['title', 'frontendSurfaceStatus', 'frontendSurfaceNote', 'parentCategory', 'legacyPath', 'pageType', 'showInMegaMenu', 'showOnHome', 'showInProductFilters', 'sortOrder', 'status'],
    description: {
      zh: '这里管理网站前台的产品分类。一级分类作为产品类型，二级分类挂到父级分类下面；顶部产品菜单和产品列表会按这个层级显示。',
      en: 'Edit product categories here. Create and publish categories first, then select them from Product Upload / Product Management. Categories drive the Products mega menu, listing filters, category page banner/search display, public URLs, and home category entry.',
    },
    group: { zh: '01 产品管理', en: '01 Product Management' },
    listSearchableFields: ['title', 'slug', 'shortTitle', 'legacyPath'],
    preview: ({ legacyPath, slug }) => {
      if (typeof legacyPath === 'string' && legacyPath.startsWith('/')) return legacyPath;
      return typeof slug === 'string' && slug ? `/products/category/${slug}` : '/products';
    },
    useAsTitle: 'title',
  },
  defaultSort: 'sortOrder',
  hooks: {
    beforeValidate: [
      validateUniqueLegacyPaths('product-categories'),
      ({ data, originalDoc }) => {
        if (!data) return data;
        const checklist = categoryFrontendChecklist({ ...(originalDoc as CategoryDraftData | undefined), ...data });
        return {
          ...data,
          frontendSurfaceStatus: checklist.status,
          frontendSurfaceNote: checklist.note,
        };
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
            { name: 'title', type: 'text', localized: true, label: { zh: '分类名称', en: 'Category Name' }, required: true },
            slugField('title'),

            {
              name: 'parentCategory',
              type: 'relationship',
              admin: { description: { zh: '选择上级产品类型。例如 Knuckle Boom Truck Mounted Cranes 的父级应选 Truck Mounted Cranes；顶部产品菜单会按这个层级显示。', en: 'Optional parent category for search display or sub-category hierarchy.' } },
              label: { zh: '父级分类', en: 'Parent Category' },
              relationTo: 'product-categories',
            },
            {
              name: 'shortTitle',
              type: 'text',
              localized: true,
              admin: { description: { zh: '导航或窄屏展示用短名称；为空则使用分类名称。', en: 'Short label for navigation.' } },
              label: { zh: '短名称', en: 'Short Title' },
            },
            {
              name: 'legacyPath',
              type: 'text',
              admin: {
                description: {
                  zh: '分类页面地址。填写这个分类在网站上的访问路径，尽量和旧网站保持一致。',
                  en: 'Public category URL. Use the current front-end path that matches the old website path. The front end renders this category directly at this path without relying on redirects.',
                },
                position: 'sidebar',
              },
              label: { zh: '分类页面地址', en: 'Public Category URL' },
            },
            {
              name: 'description',
              type: 'textarea',
              localized: true,
              label: { zh: '分类说明', en: 'Description' },
              maxLength: 260,
            },
            {
              name: 'image',
              type: 'upload',
              label: { zh: '分类图片', en: 'Category Image' },
              relationTo: 'media',
            },
            {
              name: 'iconName',
              type: 'text',
              defaultValue: 'truck-crane',
              admin: {
                description: {
                  zh: '自由填写图标名称（一般不用）；网站已有图标会自动匹配，未匹配时使用默认图标。',
                  en: 'Free icon key. Known front-end icons are matched automatically; unknown keys use a fallback icon.',
                },
              },
              label: { zh: '导航图标', en: 'Navigation Icon' },
            },
          ],
        },
        {
          label: { zh: '网站显示', en: 'Website Display' },
          fields: [
            {
              name: 'pageType',
              type: 'select',
              defaultValue: 'product-listing',
              label: { zh: '栏目类型', en: 'Page Type' },
              options: [
                { label: { zh: '产品列表栏目', en: 'Product Listing' }, value: 'product-listing' },
                { label: { zh: '专题页', en: 'Search Landing Page' }, value: 'seo-landing' },
                { label: { zh: '不在网站展示', en: 'Hidden From Front-end' }, value: 'redirect-only' },
              ],
              required: true,
            },
            {
              name: 'menuGroup',
              type: 'text',
              defaultValue: 'truck-mounted-crane',
              admin: {
                description: {
                  zh: '一般不用改。只有这个分类没有父级分类时，系统才用这里决定它放到顶部产品菜单哪一列。',
                  en: 'Advanced fallback group key. Parent category hierarchy is preferred for the mega menu.',
                },
              },
              label: { zh: '备用菜单分组', en: 'Fallback Menu Group' },
              required: true,
            },
            {
              name: 'listingGroup',
              type: 'text',
              defaultValue: 'Crane Accessories',
              admin: {
                description: {
                  zh: '只给后台编辑人员看的内部分类，不影响网站筛选。',
                  en: 'Free listing group name for editorial organization.',
                },
              },
              label: { zh: '内部备注分类', en: 'Editorial Group, Not Filter Logic' },
              required: true,
            },
            { name: 'showInMegaMenu', type: 'checkbox', defaultValue: true, label: { zh: '显示在顶部产品菜单', en: 'Show in mega menu' } },
            { name: 'showOnHome', type: 'checkbox', defaultValue: true, label: { zh: '显示在首页产品分类', en: 'Show on home page' } },
            {
              name: 'showInProductFilters',
              type: 'checkbox',
              defaultValue: true,
              admin: {
                description: {
                  zh: '关闭后，这个分类页仍可通过页面地址打开，但不会出现在产品列表页左侧筛选里。适合旧网站专题页、按吨位页等页面。',
                  en: 'When disabled, the category page remains directly accessible but is hidden from listing filter options. Useful for legacy search landing pages and capacity pages.',
                },
              },
              label: { zh: '显示在产品列表筛选', en: 'Show in product filters' },
            },
            { name: 'sortOrder', type: 'number', defaultValue: 100, label: { zh: '排序值', en: 'Sort Order' }, admin: { position: 'sidebar' } },
            legacyAliasesField({
              zh: '同一分类曾经用过的其他旧地址，一般不用改。主页面地址填上方“分类页面地址”。',
              en: 'Other historical paths for this category. Put the main public URL in Public Category URL above.',
            }),
            {
              name: 'defaultCategoryFilters',
              type: 'text',
              hasMany: true,
              admin: {
                description: {
                  zh: '可选。打开这个分类页时默认选中的产品分类值。为空时按当前分类和子分类筛选；适合 /truck-mounted-crane 这类旧网站聚合页。',
                  en: 'Optional category 短地址 applied when this category page opens. Empty means filter by this category and child categories.',
                },
              },
              label: { zh: '默认筛选：产品分类', en: 'Default Filter: Category Slug' },
            },
            {
              name: 'defaultCapacityFilters',
              type: 'text',
              hasMany: true,
              admin: {
                description: {
                  zh: '可选。填写“产品列表页设置 → 起重能力筛选项”的值，例如 0 - 5 tons。用于旧网站按吨位页面。',
                  en: 'Optional values from Product Listing Settings → Capacity Filter Options, e.g. 0 - 5 tons.',
                },
              },
              label: { zh: '默认筛选：起重能力', en: 'Default Filter: Capacity' },
            },
            {
              name: 'defaultBoomTypeFilters',
              type: 'text',
              hasMany: true,
              admin: {
                description: {
                  zh: '可选。填写“产品列表页设置 → 臂架类型筛选项”的值，例如 knuckle-boom。',
                  en: 'Optional values from Product Listing Settings → Boom Type Filter Options, e.g. knuckle-boom.',
                },
              },
              label: { zh: '默认筛选：臂架类型', en: 'Default Filter: Boom Type' },
            },
            {
              name: 'defaultApplicationFilters',
              type: 'text',
              hasMany: true,
              admin: {
                description: {
                  zh: '可选。填写“产品列表页设置 → 应用场景选项”的值，例如 construction。',
                  en: 'Optional values from Product Listing Settings → Application Dictionary, e.g. construction.',
                },
              },
              label: { zh: '默认筛选：应用场景', en: 'Default Filter: Application' },
            },
            {
              name: 'defaultAvailabilityFilters',
              type: 'text',
              hasMany: true,
              admin: {
                description: {
                  zh: '可选。填写“产品列表页设置 → 可售状态筛选项”的值，例如 customizable。',
                  en: 'Optional values from Product Listing Settings → Availability Filter Options, e.g. customizable.',
                },
              },
              label: { zh: '默认筛选：可售状态', en: 'Default Filter: Availability' },
            },
          ],
        },
        {
          label: { zh: '分类页顶部内容', en: 'Listing Banner Block' },
          fields: [
            { name: 'bannerLabel', type: 'text', localized: true, label: { zh: '列表页标签', en: '顶部横幅 Label' } },
            { name: 'bannerTitle', type: 'text', localized: true, label: { zh: '列表页标题', en: '顶部横幅 Title' } },
            { name: 'bannerImage', type: 'upload', label: { zh: '列表页图片', en: 'Banner Image' }, relationTo: 'media' },
            {
              name: 'bannerParagraphs',
              type: 'array',
              admin: {
                description: {
                  zh: '填写普通说明文字即可。需要可点击筛选链接时，再让管理员配置。',
                  en: 'Supports plain paragraphs and [link text](/products?category=category-短地址&boom=boom-key). Clicking applies listing filters on the front end.',
                },
                initCollapsed: true,
              },
              fields: [{ name: 'text', type: 'textarea', localized: true, label: { zh: '段落', en: 'Paragraph' }, required: true }],
              label: { zh: '列表页说明段落', en: 'Listing Paragraphs' },
            },
          ],
        },
        {
          label: { zh: '搜索设置与发布', en: 'Search Settings & Publish' },
          fields: [seoFields, statusField, { name: 'publishedAt', type: 'date', admin: { position: 'sidebar' }, label: { zh: '发布时间', en: 'Published At' } }],
        },
      ],
    },
    {
      name: 'frontendSurfaceStatus',
      type: 'text',
      admin: {
        description: {
          zh: '系统自动判断这个分类是否能在网站菜单、首页和列表筛选中显示。',
          en: 'Auto calculated: 可显示 is testable on category/listing/menu/home; needs-url-review is visible but missing legacy URL; hidden is not public.',
        },
        position: 'sidebar',
        readOnly: true,
      },
      defaultValue: 'hidden',
      label: { zh: '分类显示状态', en: 'Category Website Status' },
    },
    {
      name: 'frontendSurfaceNote',
      type: 'textarea',
      admin: {
        description: {
          zh: '系统自动说明这个分类为什么不会出现在产品菜单、首页或旧网站链接检查结果中。',
          en: 'Auto lists why this category does not appear in product menus, home, or legacy URL tests.',
        },
        position: 'sidebar',
        readOnly: true,
      },
      label: { zh: '显示说明', en: 'Category Visibility Note' },
    },
  ],
  timestamps: true,
};
