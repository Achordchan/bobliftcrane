# BOB-LIFT 现站内容框架与 URL 保护审计

数据来源：`https://www.bobliftcrane.com/robots.txt`、`https://www.bobliftcrane.com/sitemap.xml`、sitemap 内页面逐页抓取。抓取日期：2026-05-21。

## 01 当前 URL 规模

- sitemap URL 总数：219
- 当前 200：215
- 当前 404/异常：4
- 产物：`audit/sitemap_urls.txt`、`audit/pages.csv`、`audit/url-preservation-map.csv`

## 02 页面类型分布

- article: 72
- product_detail: 68
- seo_category: 30
- product_category_legacy: 25
- support_media_legal: 7
- news_index: 5
- inquiry_contact: 4
- about: 3
- product_index: 2
- news_tag: 2
- home: 1

## 03 现站内容框架

现站核心不是单纯企业官网，而是“产品 SEO 分类 + 产品详情参数 + 新闻/案例内容 + 询盘表单”的 B2B 外贸站。首页结构是：导航、产品入口、FEATURED PRODUCTS、ABOUT US、NEWS、NEW PRODUCTS、底部联系与产品快捷链接。主导航里真正需要保留的是 HOME、ABOUT US、PRODUCTS、NEWS、CONTACT/Send Inquiry；产品导航重点是 Truck Mounted Cranes、Knuckle Boom、Telescopic Boom、Marine Cranes、Pickup Cranes、Forklift Fly Jib Cranes、Crane by Ton、Mini Dumpers、Aerial Work Platform、Truck Tail Lift。

## 04 新站 Payload 内容模型方向

1. ProductCategory：名称、slug、旧URL、父级、SEO标题、SEO描述、排序、语言、封面图。
2. Product：名称、slug、旧URL、分类、多语言内容、图库、视频、参数表、推荐产品、询盘CTA。
3. ProductSpec：字段名、单位、数值、排序；现站常见字段包括 Max Lifting Capacity、Max Lifting Moment、Recommend Power、Hydraulic System Flow、Hydraulic System Pressure、Oil Tank Capacity、Installation Space、Self Weight、Rotation Angle。
4. Article：标题、slug、旧URL、栏目、发布时间、封面、正文、关联产品、SEO。
5. Inquiry：来源URL、产品、姓名、邮箱、电话、国家、消息、语言、UTM、提交时间。

## 05 URL 改版原则

- 新站必须把 sitemap 中 219 个 URL 全部纳入路由保护，不能只做新URL。
- 产品分类存在两套路径：旧 `.html` 路径和短 SEO 路径，例如 `/Products-Truck-Mounted-Cranes.html` 与 `/truck-mounted-crane`；新站至少都要可访问，并为 canonical 指向主路径。
- 产品详情大量是根路径 `.html`，例如 `/10-ton-knuckle-boom-truck-mounted-crane.html`；Next.js 需要做根级动态兼容，不能强行变成 `/products/...` 后不管旧链接。
- 新闻文章既有 `/news-show-2.html`，也有 `*-id数字.html`，也有问题型 SEO 文章路径；全部需要旧URL直达。
- `/contact-us` 当前是 404，历史广告已经踩过；新站必须补 301 到 `/message.html` 或直接 200 渲染询盘页。

## 06 当前发现的风险点

- /Products-Excavator.html: 当前 HTTPError: HTTP Error 404: Not Found
- /Products-Excavator-by-Ton.html: 当前 HTTPError: HTTP Error 404: Not Found
- /Medium-Excavator-1.html: 当前 HTTPError: HTTP Error 404: Not Found
- /Mini-Excavator-1.html: 当前 HTTPError: HTTP Error 404: Not Found
- sitemap 里还有一批重复标题/重复内容，需要新站用 canonical 和内部模型统一处理。
  - 7 个重复标题：Cranes | Lifting Equipment & Machines - BOB-LIFT => /products.html, /productcategory.html, /gallery.html, /videos.html, /video-detail.html, /products/Lightweight-construction-equipment.html
  - 3 个重复标题：Send Inquiry - Xuzhou BOB-LIFT Construction Machinery Co., Ltd. => /message.html, /faqlist.html, /article-inquire.html
  - 2 个重复标题：徐州巴布工程机械有限公司 => /keywords.html, /keyword-index.html
  - 2 个重复标题：Timber Cranes - BOB-LIFT => /Timber-Crane-1.html, /timber-crane.html
  - 2 个重复标题：Telescopic Boom Truck Mounted Cranes - BOB-LIFT => /Telescopic-Boom-Truck-Mounted-Cranes.html, /telescopic-boom-truck-mounted-crane
  - 2 个重复标题：Telescopic Boom Marine Cranes - BOB-LIFT => /Telescopic-Boom-Marine-Cranes.html, /telescopic-boom-marine-crane
  - 2 个重复标题：Pickup Truck Cranes - BOB-LIFT => /Products-Pickup-Cranes.html, /pickup-crane
  - 2 个重复标题：Mini Dumpers - BOB-LIFT => /Products-Mini-Dumpers.html, /mini-dumper

## 07 第一版页面建设优先级

P0：保留全部旧URL、首页、产品总览、核心分类页、产品详情页、询盘页、新闻详情页。
P1：多语言英语/西语/俄语/阿语、sitemap、canonical、hreflang、结构化数据、图片 CDN。
P2：下载、图库、视频、FAQ、案例专题、产品对比和参数筛选。
