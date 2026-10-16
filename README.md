# BOB-LIFT WordPress Theme

这是从当前 Next.js 前台改造出的 WordPress 主题项目。运行时使用 PHP + WordPress + MySQL，前台样式以原 Next.js 前台为复刻基准。

## 安装

1. 把本目录放到 WordPress 的 `wp-content/themes/bobliftcrane`。
2. 在 WordPress 后台启用 `BOB-LIFT Crane` 主题。
3. 刷新固定链接：后台 `设置 → 固定链接 → 保存更改`。
4. 导入当前内容：

```bash
wp boblift import-current
```

导入命令会创建产品、产品分类、新闻、案例、基础页面、主题 Options，并把主题 assets 中的图片导入媒体库。导入按 slug 更新，不会默认删除已有内容。

## 开发样式

```bash
npm install
npm run build:css
```

WordPress 运行时只加载 `assets/css/theme.css` 和 `assets/js/theme.js`，不依赖 Node 或 React 运行服务。

## 本地启动

先确保 Homebrew MySQL 已运行，再从任意目录执行启动脚本：

```bash
brew services start mysql
./start.sh
```

默认访问 `http://localhost:8080`。可以传入端口，或使用 `PORT` 和 `HOST` 环境变量：

```bash
./start.sh 8081
PORT=8082 HOST=127.0.0.1 ./start.sh
```

日志直接显示在当前终端；按 `Ctrl+C` 或关闭终端即可停止。`npm run start:wp` 使用同一启动脚本。

## 内容结构

- Products：自定义文章类型 `product`，归档路径 `/products`，详情路径 `/products/{slug}`。
- Product Categories：分类法 `product_category`，用于产品筛选和后台维护。
- Cases：自定义文章类型 `case`，归档路径 `/cases`，详情路径 `/cases/{slug}`。
- News：使用 WordPress 默认文章，`page-news.php` 提供新闻列表页。
- Inquiries：自定义文章类型 `inquiry`，用于保存询盘表单提交。
- BOB-LIFT Settings：主题设置页，使用 WordPress Options API 保存基础品牌、联系方式和 CTA 文案。

## 已移除的旧链路

- 旧版前台运行服务。
- 旧版内容后台集合、全局配置、迁移和后台路由。
- 旧版容器化数据库链路。
- 历史路径保护、历史重定向集合和地址审计导入逻辑。
