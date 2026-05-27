# 2026-05-21 开发记录

## 当前架构

- 前台：`apps/web`，基于 Next.js。
- 后台：Payload CMS 已集成到同一个 Next.js 应用，后台入口为 `/admin`。
- 数据库：Docker Compose 启动 PostgreSQL 16，数据库名为 `bobliftcrane_content`。
- 内容模型：产品、文章、案例、媒体库、站点设置。

## 启动命令

```bash
npm run db:up
npm run web:dev
```

- 前台地址：`http://localhost:3000`
- 后台地址：`http://localhost:3000/admin`
- 内容 API：`http://localhost:3000/api/payload`

## 维护命令

```bash
npm run payload:generate:types
npm run payload:generate:importmap
npm run payload:migrate:status
```
