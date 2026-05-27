# BOB-LIFT 独立站改版

本项目当前为 Next.js 前台 + Payload CMS 集成后台 + PostgreSQL 本地数据库，用于承接旧站 `www.bobliftcrane.com` 的产品、文章、案例、询盘和旧 URL 保护。

## 本地启动

数据库：

```bash
npm run db:up
```

前台与后台共用同一个 Next.js 服务：

```bash
npm run web:dev
```

- 前台地址：`http://localhost:3000`
- 后台地址：`http://localhost:3000/admin`
- 内容 API：`http://localhost:3000/api/payload`
- GraphQL：`http://localhost:3000/api/graphql`

## 构建与后台维护

```bash
npm run web:build
npm run payload:generate:types
npm run payload:generate:importmap
npm run payload:migrate:status
```

## 已配置内容模型

- 产品：承接产品详情、型号、分类、吨位、参数表、图库和旧产品 URL。
- 文章：承接新闻、指南和旧文章 URL。
- 案例：承接项目案例、国家地区、行业和旧案例 URL。
- 媒体库：承接图片上传、缩略图、卡片图和首页大图尺寸。
- 站点设置：品牌、邮箱、电话、WhatsApp、地址等全局信息。

## URL 改版资料

- URL 审计报告：`audit/current-site-framework-and-url-audit.md`
- URL 保护表：`audit/url-preservation-map.csv`
- sitemap 原始清单：`audit/sitemap_urls.txt`
