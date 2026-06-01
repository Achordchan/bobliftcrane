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
  if (data.pageType === 'redirect-only') blocking.push('栏目类型为不在前台展示');
  if (!hasText(data.title)) blocking.push('分类名称');
  if (!hasText(data.slug)) blocking.push('slug');
  if (!hasText(data.legacyPath)) notes.push('未填写旧站公开 URL，将使用 /products/category/{slug}');
  if (data.showInMegaMenu === false) notes.push('不进入顶部 Products 菜单');
  if (data.showOnHome === false) notes.push('不进入首页产品分类');
  if (data.showInProductFilters === false) notes.push('不进入产品列表筛选项，但直接访问分类页仍生效');

  if (blocking.length > 0) {
    return { status: 'hidden', note: blocking.concat(notes).join('、') };
  }

  return {
    status: notes.some((note) => note.includes('公开 URL')) ? 'needs-url-review' : 'ready',
    note: notes.length > 0 ? notes.join('、') : '已具备产品列表筛选、分类页、顶部菜单和首页分类测试条件',
  };
}

export const ProductCategories: CollectionConfig = {
  slug: 'product-categories',
  labels: {
    singular: '产品分类管理（先建分类）',
    plural: '产品分类管理（先建分类）',
  },
  access: {
    create: isAdmin,
    delete: isAdmin,
    read: publishedOrAdmin,
    update: isAdmin,
  },
  admin: {
    defaultColumns: ['title', 'frontendSurfaceStatus', 'frontendSurfaceNote', 'parentCategory', 'legacyPath', 'pageType', 'menuGroup', 'showInMegaMenu', 'showOnHome', 'showInProductFilters', 'sortOrder', 'status'],
    description: {
      zh: '这里就是编辑产品分类的地方：先建并发布分类，再到“产品上传 / 产品管理（看前台可见状态）”里选择该分类。分类会同步控制顶部 Products 大菜单、产品列表筛选、分类页 Banner/SEO、公开 URL 和首页产品分类入口。',
      en: 'Edit product categories here. Create and publish categories first, then select them from Product Upload / Product Management. Categories drive the Products mega menu, listing filters, category page banner/SEO, public URLs, and home category entry.',
    },
    group: { zh: '01 产品中心', en: '01 Product Center' },
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
              admin: { description: { zh: '可选。用于把 SEO 子栏目归到主分类下面；主分类会继续作为产品列表筛选入口。', en: 'Optional parent category for SEO or sub-category hierarchy.' } },
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
                  zh: '公开分类 URL。这里填写当前前台真实访问路径，要求和旧站路径一致；前台会直接在这个路径渲染分类页，不依赖跳转。',
                  en: 'Public category URL. Use the current front-end path that matches the old website path. The front end renders this category directly at this path without relying on redirects.',
                },
                position: 'sidebar',
              },
              label: { zh: '公开分类 URL（保持旧站一致）', en: 'Public Category URL' },
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
                  zh: '自由填写图标标识；前台已有图标会自动匹配，未匹配时使用默认图标。',
                  en: 'Free icon key. Known front-end icons are matched automatically; unknown keys use a fallback icon.',
                },
              },
              label: { zh: '导航图标', en: 'Navigation Icon' },
            },
          ],
        },
        {
          label: { zh: '前台展示', en: 'Front-end Display' },
          fields: [
            {
              name: 'pageType',
              type: 'select',
              defaultValue: 'product-listing',
              label: { zh: '栏目类型', en: 'Page Type' },
              options: [
                { label: { zh: '产品列表栏目', en: 'Product Listing' }, value: 'product-listing' },
                { label: { zh: 'SEO 落地页', en: 'SEO Landing Page' }, value: 'seo-landing' },
                { label: { zh: '不在前台展示', en: 'Hidden From Front-end' }, value: 'redirect-only' },
              ],
              required: true,
            },
            {
              name: 'menuGroup',
              type: 'text',
              defaultValue: 'truck-mounted-crane',
              admin: {
                description: {
                  zh: '自由填写分组标识；会与“网站设置 → 产品大菜单分组”的分组标识对应，决定该分类在顶部 Products 大菜单的哪一列显示。',
                  en: 'Free mega-menu group key. Match it with Site Settings → Product Mega Menu Groups.',
                },
              },
              label: { zh: '顶部 Products 菜单分组', en: 'Products Mega Menu Group' },
              required: true,
            },
            {
              name: 'listingGroup',
              type: 'text',
              defaultValue: 'Crane Accessories',
              admin: {
                description: {
                  zh: '自由填写后台归类名称，只用于编辑人员理解；前台分类筛选由“分类名称、是否发布、是否前台展示”控制。',
                  en: 'Free listing group name for editorial organization.',
                },
              },
              label: { zh: '后台归类标签（不控制前台筛选）', en: 'Editorial Group, Not Filter Logic' },
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
                  zh: '关闭后，这个分类页仍可直接访问，但不会出现在产品列表页左侧分类筛选里。适合旧站 SEO 落地页、按吨位页等后台可维护页面。',
                  en: 'When disabled, the category page remains directly accessible but is hidden from listing filter options. Useful for legacy SEO landing pages and capacity pages.',
                },
              },
              label: { zh: '显示在产品列表筛选', en: 'Show in product filters' },
            },
            { name: 'sortOrder', type: 'number', defaultValue: 100, label: { zh: '排序值', en: 'Sort Order' }, admin: { position: 'sidebar' } },
            legacyAliasesField({
              zh: '同一分类曾经用过的其他历史路径。主公开 URL 请填上方“公开分类 URL”。',
              en: 'Other historical paths for this category. Put the main public URL in Public Category URL above.',
            }),
            {
              name: 'defaultCategoryFilters',
              type: 'text',
              hasMany: true,
              admin: {
                description: {
                  zh: '可选。访问这个分类页时默认筛选的产品分类 slug。为空时按当前分类和子分类筛选；适合 /truck-mounted-crane 这类聚合旧 URL。',
                  en: 'Optional category slugs applied when this category page opens. Empty means filter by this category and child categories.',
                },
              },
              label: { zh: '默认筛选：产品分类 slug', en: 'Default Filter: Category Slugs' },
            },
            {
              name: 'defaultCapacityFilters',
              type: 'text',
              hasMany: true,
              admin: {
                description: {
                  zh: '可选。填写“产品列表页设置 → 起重能力筛选项”的值，例如 0 - 5 tons。用于旧站按吨位落地页。',
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
                  zh: '可选。填写“产品列表页设置 → 应用场景字典”的值，例如 construction。',
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
          label: { zh: '列表页 SEO 区块', en: 'Listing SEO Block' },
          fields: [
            { name: 'bannerLabel', type: 'text', localized: true, label: { zh: '列表页标签', en: 'Banner Label' } },
            { name: 'bannerTitle', type: 'text', localized: true, label: { zh: '列表页标题', en: 'Banner Title' } },
            { name: 'bannerImage', type: 'upload', label: { zh: '列表页图片', en: 'Banner Image' }, relationTo: 'media' },
            {
              name: 'bannerParagraphs',
              type: 'array',
              admin: {
                description: {
                  zh: '支持普通段落，也支持 [可点击文字](/products?category=分类slug&boom=臂架值) 这种写法；前台点击后会直接应用筛选。',
                  en: 'Supports plain paragraphs and [link text](/products?category=category-slug&boom=boom-key). Clicking applies listing filters on the front end.',
                },
                initCollapsed: true,
              },
              fields: [{ name: 'text', type: 'textarea', localized: true, label: { zh: '段落', en: 'Paragraph' }, required: true }],
              label: { zh: '列表页说明段落', en: 'Listing Paragraphs' },
            },
          ],
        },
        {
          label: { zh: 'SEO 与发布', en: 'SEO & Publish' },
          fields: [seoFields, statusField, { name: 'publishedAt', type: 'date', admin: { position: 'sidebar' }, label: { zh: '发布时间', en: 'Published At' } }],
        },
      ],
    },
    {
      name: 'frontendSurfaceStatus',
      type: 'text',
      admin: {
        description: {
          zh: '系统自动计算：ready=分类页、列表筛选、菜单和首页入口可测试；needs-url-review=可显示但还缺旧站公开 URL；hidden=不会进入前台。',
          en: 'Auto calculated: ready is testable on category/listing/menu/home; needs-url-review is visible but missing legacy URL; hidden is not public.',
        },
        position: 'sidebar',
        readOnly: true,
      },
      defaultValue: 'hidden',
      label: { zh: '分类前台状态', en: 'Category Front-end Status' },
    },
    {
      name: 'frontendSurfaceNote',
      type: 'textarea',
      admin: {
        description: {
          zh: '系统自动列出这个分类为什么不会出现在产品菜单、首页或旧站 URL 测试中。',
          en: 'Auto lists why this category does not appear in product menus, home, or legacy URL tests.',
        },
        position: 'sidebar',
        readOnly: true,
      },
      label: { zh: '分类显示说明', en: 'Category Visibility Note' },
    },
  ],
  timestamps: true,
};
