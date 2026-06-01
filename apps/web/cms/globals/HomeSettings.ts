import type { GlobalConfig } from 'payload';

import { isAdmin } from '../access/isAdmin';

const iconOptions = [
  { label: 'Award', value: 'award' },
  { label: 'Building', value: 'building' },
  { label: 'Factory', value: 'factory' },
  { label: 'Globe', value: 'globe' },
  { label: 'Network', value: 'network' },
  { label: 'Package Check', value: 'package-check' },
  { label: 'Ship', value: 'ship' },
  { label: 'Truck', value: 'truck' },
  { label: 'Wrench', value: 'wrench' },
];

const sectionSwitch = (name: string, zh: string, en: string) => ({
  name,
  type: 'checkbox' as const,
  defaultValue: true,
  label: { zh, en },
});

export const HomeSettings: GlobalConfig = {
  slug: 'home-settings',
  label: { zh: '首页内容', en: 'Home Settings' },
  access: {
    read: () => true,
    update: isAdmin,
  },
  admin: {
    hideAPIURL: true,
    group: { zh: '02 页面内容', en: '02 Page Content' },
  },
  fields: [
    {
      type: 'tabs',
      tabs: [
        {
          label: { zh: '首页顶部', en: 'Home 顶部区域' },
          fields: [
            {
              name: 'homeSeo',
              type: 'group',
              label: { zh: '搜索结果设置', en: 'Search Result Settings' },
              fields: [
                { name: 'metaTitle', type: 'text', localized: true, defaultValue: 'BOB-LIFT | Truck Mounted Cranes and Lifting Solutions', label: { zh: '搜索结果标题', en: 'Search Result Title' } },
                { name: 'metaDescription', type: 'textarea', localized: true, defaultValue: 'BOB-LIFT supplies truck mounted cranes, marine cranes, aerial work platforms and lifting attachments for global projects.', label: { zh: '搜索结果描述', en: 'Search Result Description' } },
                { name: 'canonicalPath', type: 'text', defaultValue: '/', label: { zh: '标准页面地址', en: 'Canonical Path' } },
              ],
            },
            {
              name: 'homeHeroSlides',
              type: 'array',
              admin: {
                description: { zh: '首页顶部轮播图；不填写时使用系统默认图。', en: 'Home hero carousel slides. Defaults are used when empty.' },
                initCollapsed: true,
              },
              fields: [
                { name: 'image', type: 'upload', label: { zh: '桌面端图片', en: 'Desktop Image' }, relationTo: 'media' },
                { name: 'imagePath', type: 'text', label: { zh: '桌面图片路径（一般不用）', en: 'Desktop Backup Image Path' } },
                { name: 'mobileImage', type: 'upload', label: { zh: '移动端图片', en: 'Mobile Image' }, relationTo: 'media' },
                { name: 'mobileImagePath', type: 'text', label: { zh: '手机图片路径（一般不用）', en: 'Mobile Backup Image Path' } },
              ],
              label: { zh: '首页轮播图', en: 'Hero Slides' },
              maxRows: 6,
              minRows: 0,
            },
            {
              name: 'homeHeroUi',
              type: 'group',
              label: { zh: '轮播交互与辅助文案', en: 'Hero Behavior & Accessibility Text' },
              fields: [
                { name: 'ariaLabel', type: 'text', localized: true, defaultValue: 'Featured lifting solutions', label: { zh: '轮播区域说明', en: 'Hero Section Aria Label' } },
                { name: 'previousSlideLabel', type: 'text', localized: true, defaultValue: 'Previous banner', label: { zh: '上一张按钮说明', en: 'Previous Button Label' } },
                { name: 'nextSlideLabel', type: 'text', localized: true, defaultValue: 'Next banner', label: { zh: '下一张按钮说明', en: 'Next Button Label' } },
                { name: 'slideIndicatorLabelTemplate', type: 'text', localized: true, defaultValue: 'Show banner {index}', label: { zh: '圆点按钮说明模板', en: 'Slide Dot Label Template' } },
                { name: 'autoplay', type: 'checkbox', defaultValue: true, label: { zh: '自动轮播', en: 'Autoplay Slides' } },
                { name: 'autoplayIntervalMs', type: 'number', defaultValue: 5600, min: 2000, max: 20000, label: { zh: '自动轮播间隔（毫秒）', en: 'Autoplay Interval (ms)' } },
                { name: 'showControls', type: 'checkbox', defaultValue: true, label: { zh: '显示左右切换按钮', en: 'Show Arrow Controls' } },
                { name: 'showIndicators', type: 'checkbox', defaultValue: true, label: { zh: '显示轮播圆点', en: 'Show Slide Indicators' } },
              ],
            },
            {
              name: 'homeSectionVisibility',
              type: 'group',
              label: { zh: '首页区块显示', en: 'Home Section Visibility' },
              fields: [
                sectionSwitch('hero', '显示首页轮播', 'Show Hero'),
                sectionSwitch('trustBar', '显示认证条', 'Show Trust Bar'),
                sectionSwitch('productSection', '显示首页产品区', 'Show Products Section'),
                sectionSwitch('aboutSection', '显示关于我们区', 'Show About Section'),
                sectionSwitch('newsSection', '显示新闻区', 'Show News Section'),
                sectionSwitch('finalCta', '显示底部询盘横幅', 'Show Final 询盘按钮'),
              ],
            },
            {
              name: 'homeFeaturedArticles',
              type: 'relationship',
              admin: {
                description: { zh: '可选：指定首页新闻区展示的文章；网站会按这里的拖动顺序展示。为空时展示最新发布文章。', en: 'Optional articles for the home news section. The front end follows this order.' },
              },
              filterOptions: { status: { equals: 'published' } },
              hasMany: true,
              label: { zh: '首页优先新闻', en: 'Home Featured Articles' },
              relationTo: 'articles',
            },
          ],
        },
        {
          label: { zh: '首页产品区', en: 'Home Products' },
          fields: [
            {
              name: 'homeProductSection',
              type: 'group',
              label: { zh: '首页产品区', en: 'Home Products Section' },
              fields: [
                { name: 'label', type: 'text', localized: true, defaultValue: 'Main Products', label: { zh: '小标题', en: 'Label' } },
                { name: 'title', type: 'text', localized: true, defaultValue: 'BOB-LIFT Crane Solutions', label: { zh: '标题', en: 'Title' } },
                { name: 'maxCategoryProducts', type: 'number', defaultValue: 8, min: 1, max: 16, label: { zh: '每个分类最多展示产品数', en: 'Max Products Per Category' } },
                { name: 'showCategoryScrollButtons', type: 'checkbox', defaultValue: true, label: { zh: '显示分类左右滚动按钮', en: 'Show Category Scroll Buttons' } },
                { name: 'showInlineProductDetail', type: 'checkbox', defaultValue: true, label: { zh: '点击产品先显示首页内详情', en: 'Open Inline Product Detail First' } },
                { name: 'showProductSpecs', type: 'checkbox', defaultValue: true, label: { zh: '显示首页产品参数', en: 'Show Home Product Specs' } },
                { name: 'showProductVisualWatermark', type: 'checkbox', defaultValue: true, label: { zh: '显示产品视觉背景字', en: 'Show Product Visual Watermark' } },
                { name: 'findModelTitle', type: 'text', localized: true, defaultValue: 'Find Your Model', label: { zh: '型号区标题', en: 'Model Area Title' } },
                { name: 'detailsShortLabel', type: 'text', localized: true, defaultValue: 'Details', label: { zh: '移动端详情短文案', en: 'Mobile Detail Short Label' } },
                { name: 'viewDetailsLabel', type: 'text', localized: true, defaultValue: 'View Details', label: { zh: '查看详情按钮', en: 'View Details Label' } },
                { name: 'backToModelsLabel', type: 'text', localized: true, defaultValue: 'Back to Models', label: { zh: '返回型号按钮', en: 'Back to Models Label' } },
                { name: 'previousCategoriesLabel', type: 'text', localized: true, defaultValue: 'Previous product categories', label: { zh: '上一组分类按钮说明', en: 'Previous Categories Label' } },
                { name: 'nextCategoriesLabel', type: 'text', localized: true, defaultValue: 'Next product categories', label: { zh: '下一组分类按钮说明', en: 'Next Categories Label' } },
                { name: 'productNameLabel', type: 'text', localized: true, defaultValue: 'Product Name', label: { zh: '产品名称标签', en: 'Product Name Label' } },
                { name: 'modelLabel', type: 'text', localized: true, defaultValue: 'Model', label: { zh: '型号标签', en: 'Model Label' } },
                { name: 'modelSpecLabel', type: 'text', localized: true, defaultValue: 'Model', label: { zh: '参数：型号', en: 'Spec: Model' } },
                { name: 'capacitySpecLabel', type: 'text', localized: true, defaultValue: 'Capacity', label: { zh: '参数：起重能力', en: 'Spec: Capacity' } },
                { name: 'typeSpecLabel', type: 'text', localized: true, defaultValue: 'Type', label: { zh: '参数：类型', en: 'Spec: Type' } },
                { name: 'systemSpecLabel', type: 'text', localized: true, defaultValue: 'System', label: { zh: '参数：系统', en: 'Spec: System' } },
                { name: 'systemValue', type: 'text', localized: true, defaultValue: 'Hydraulic', label: { zh: '系统默认值', en: 'System Default Value' } },
                { name: 'visualWatermarkLabel', type: 'text', localized: true, defaultValue: 'BOB-LIFT', label: { zh: '产品视觉背景字', en: 'Product Visual Watermark' } },
              ],
            },
          ],
        },
        {
          label: { zh: '首页内容区', en: 'Home Sections' },
          fields: [
            {
              name: 'trustBar',
              type: 'group',
              label: { zh: '认证条', en: 'Trust Bar' },
              fields: [
                { name: 'label', type: 'text', localized: true, defaultValue: 'Certified', label: { zh: '标签', en: 'Label' } },
                { name: 'title', type: 'text', localized: true, defaultValue: 'Quality Assurance', label: { zh: '标题', en: 'Title' } },
                { name: 'description', type: 'text', localized: true, defaultValue: 'Export-可显示 documentation.', label: { zh: '说明', en: 'Description' } },
                { name: 'showIntroCard', type: 'checkbox', defaultValue: true, label: { zh: '显示认证条说明卡', en: 'Show Trust Intro Card' } },
                { name: 'showMarkNames', type: 'checkbox', defaultValue: true, label: { zh: '显示认证名称', en: 'Show Trust Mark Names' } },
                {
                  name: 'marks',
                  type: 'array',
                  admin: { initCollapsed: true },
                  fields: [
                    { name: 'name', type: 'text', localized: true, required: true, label: { zh: '名称', en: 'Name' } },
                    { name: 'logoPath', type: 'text', label: { zh: '图片路径（一般不用）', en: 'Backup Logo Path' } },
                    { name: 'logoImage', type: 'upload', label: { zh: '上传 Logo', en: 'Logo Upload' }, relationTo: 'media' },
                  ],
                  label: { zh: '认证项', en: 'Trust Marks' },
                },
              ],
            },
            {
              name: 'aboutSection',
              type: 'group',
              label: { zh: '关于我们区', en: 'About Section' },
              fields: [
                { name: 'label', type: 'text', localized: true, defaultValue: 'About BOB-LIFT', label: { zh: '小标题', en: 'Label' } },
                { name: 'title', type: 'text', localized: true, defaultValue: 'Engineering Excellence. Powered by People.', label: { zh: '标题', en: 'Title' } },
                { name: 'text', type: 'textarea', localized: true, label: { zh: '正文', en: 'Text' } },
                { name: 'ctaLabel', type: 'text', localized: true, defaultValue: 'More About Us', label: { zh: '按钮文案', en: '按钮 Label' } },
                { name: 'ctaHref', type: 'text', defaultValue: '/about', label: { zh: '按钮链接', en: 'Button URL' } },
                { name: 'showGallery', type: 'checkbox', defaultValue: true, label: { zh: '显示关于我们图片组', en: 'Show About Gallery' } },
                { name: 'showStats', type: 'checkbox', defaultValue: true, label: { zh: '显示关于我们数据项', en: 'Show About Stats' } },
                { name: 'showTimeline', type: 'checkbox', defaultValue: true, label: { zh: '显示关于我们时间线', en: 'Show About Timeline' } },
                { name: 'showCta', type: 'checkbox', defaultValue: true, label: { zh: '显示关于我们按钮', en: 'Show About 按钮' } },
                {
                  name: 'galleryThumbnailLabelTemplate',
                  type: 'text',
                  localized: true,
                  defaultValue: 'Show {title}',
                  label: { zh: '缩略图按钮说明模板', en: 'Thumbnail Button Label Template' },
                },
                { name: 'videoLabel', type: 'text', localized: true, defaultValue: 'Video', label: { zh: '视频标记文案', en: 'Video Badge Label' } },
                {
                  name: 'gallery',
                  type: 'array',
                  admin: { initCollapsed: true },
                  fields: [
                    { name: 'image', type: 'upload', label: { zh: '图片', en: 'Image' }, relationTo: 'media' },
                    { name: 'imagePath', type: 'text', label: { zh: '图片路径（一般不用）', en: 'Backup Image Path' } },
                    { name: 'alt', type: 'text', localized: true, required: true, label: { zh: '图片说明', en: 'Alt Text' } },
                    { name: 'video', type: 'checkbox', defaultValue: false, label: { zh: '显示视频标记', en: 'Show Video Marker' } },
                    { name: 'videoUrl', type: 'text', label: { zh: '视频链接', en: 'Video URL' } },
                  ],
                  label: { zh: '图片组', en: 'Gallery' },
                },
                {
                  name: 'stats',
                  type: 'array',
                  admin: { initCollapsed: true },
                  fields: [
                    { name: 'value', type: 'text', localized: true, required: true, label: { zh: '数值', en: 'Value' } },
                    { name: 'label', type: 'text', localized: true, required: true, label: { zh: '说明', en: 'Label' } },
                    { name: 'iconName', type: 'select', defaultValue: 'award', label: { zh: '图标', en: 'Icon' }, options: iconOptions },
                  ],
                  label: { zh: '数据项', en: 'Stats' },
                },
                {
                  name: 'timeline',
                  type: 'array',
                  admin: { initCollapsed: true },
                  fields: [{ name: 'text', type: 'text', localized: true, required: true, label: { zh: '时间线文案', en: 'Timeline Text' } }],
                  label: { zh: '时间线', en: 'Timeline' },
                },
              ],
            },
            {
              name: 'newsSection',
              type: 'group',
              label: { zh: '新闻区', en: 'News Section' },
              fields: [
                { name: 'label', type: 'text', localized: true, defaultValue: 'News Center', label: { zh: '小标题', en: 'Label' } },
                { name: 'title', type: 'text', localized: true, defaultValue: 'Latest Insights from BOB-LIFT.', label: { zh: '标题', en: 'Title' } },
                { name: 'ctaLabel', type: 'text', localized: true, defaultValue: 'View All News', label: { zh: '按钮文案', en: '按钮 Label' } },
                { name: 'ctaHref', type: 'text', defaultValue: '/news', label: { zh: '按钮链接', en: 'Button URL' } },
                { name: 'showCta', type: 'checkbox', defaultValue: true, label: { zh: '显示新闻区按钮', en: 'Show News 按钮' } },
                { name: 'showExcerpt', type: 'checkbox', defaultValue: true, label: { zh: '显示新闻摘要', en: 'Show News Excerpts' } },
                { name: 'showDate', type: 'checkbox', defaultValue: true, label: { zh: '显示新闻日期', en: 'Show News Dates' } },
                { name: 'showCategory', type: 'checkbox', defaultValue: true, label: { zh: '显示新闻分类', en: 'Show News Categories' } },
                { name: 'maxArticles', type: 'number', defaultValue: 4, min: 1, max: 4, label: { zh: '首页新闻最多展示数', en: 'Max Home News Articles' } },
                { name: 'readMoreLabel', type: 'text', localized: true, defaultValue: 'Read More', label: { zh: '卡片更多按钮', en: 'Read More Label' } },
                { name: 'pinnedLabel', type: 'text', localized: true, defaultValue: 'Pinned News', label: { zh: '置顶标签', en: 'Pinned Label' } },
                { name: 'backgroundWord', type: 'text', localized: true, defaultValue: 'NEWS', label: { zh: '背景大字', en: 'Background Word' } },
              ],
            },
            {
              name: 'finalCta',
              type: 'group',
              label: { zh: '底部询盘横幅', en: 'Final 按钮' },
              fields: [
                { name: 'label', type: 'text', localized: true, defaultValue: 'Project Consultation', label: { zh: '小标题', en: 'Label' } },
                { name: 'title', type: 'text', localized: true, defaultValue: 'Need a Lifting Solution?', label: { zh: '标题', en: 'Title' } },
                { name: 'buttonLabel', type: 'text', localized: true, defaultValue: 'Request a Quote', label: { zh: '按钮文案', en: 'Button Label' } },
                { name: 'buttonHref', type: 'text', defaultValue: '/quote', label: { zh: '按钮链接', en: 'Button URL' } },
                { name: 'image', type: 'upload', label: { zh: '背景图', en: 'Background Image' }, relationTo: 'media' },
                { name: 'imagePath', type: 'text', defaultValue: '/images/cta-lift-photo.png', label: { zh: '图片路径（一般不用）', en: 'Backup Image Path' } },
              ],
            },
          ],
        },
      ],
    },
  ],
};
