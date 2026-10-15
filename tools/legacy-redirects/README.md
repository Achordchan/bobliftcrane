# 旧站 → 新站 301 跳转

旧站是 SaaS 托管的 www.bobliftcrane.com，另有 fr / es / ru / sa 四个语言子域。新站上线后，旧链接由主题 `inc/legacy-redirects.php` 在 WordPress 最早的 `init` 阶段 301 到新地址，保留 utm、gclid 等查询参数。

## 文件

| 文件 | 作用 |
| --- | --- |
| `inc/legacy-redirects.json` | 出厂规则（149 条）和语言子域兜底规则，数据库里没有自定义规则时用它 |
| `inc/legacy-redirects.php` | 解析与跳转逻辑，REST 接口 |
| `tools/legacy-redirects/redirect-map.csv` | 人看的映射表（旧路径、新目标、类型、说明） |
| `tools/legacy-redirects/test.php` | 不依赖 WordPress 的规则自测：`php tools/legacy-redirects/test.php` |
| `tools/legacy-redirects/check.py` | 对着跑起来的新站逐条验证：`python3 tools/legacy-redirects/check.py --base http://localhost:8080` |

## 规则怎么维护（不改代码）

需要管理员账号的应用程序密码：

- 查看：`GET /wp-json/boblift/v1/legacy-redirects`
- 新增或修改：`POST /wp-json/boblift/v1/legacy-redirects`，body `{"rules":[{"from":"/old.html","to":"/new.html","note":"说明"}]}`；`"enabled":false` 可暂停一条；`"replace":true` 整体替换
- 删除一条：`DELETE …/legacy-redirects?from=/old.html`；恢复出厂：`DELETE …?reset=1`

`to` 可以是站内路径（`/marine-crane`）或 https 绝对地址（跳 bobliftloader.com）。路径匹配不分大小写、忽略结尾斜杠。

## 映射原则

- 新站沿用了旧站 `/slug.html` 的结构，产品、新闻 140 条路径一致或只差大小写，无需也无法再「映射」，大小写差异的 50 条已列入规则。
- 旧分类页（`Products-*.html`、单数命名的分类、按吨位页）统一到新站分类页或 `/products.html`。
- 滑移装载机、迷你翻斗车、搅拌车：跳 bobliftloader.com 对应产品页（型号后缀 T/S、-H/-C 为按名称推断，待业务确认）。
- SaaS 自动生成的关键词页（`/products/*.html`、`/news/*.html`、keywords）归到最相关的分类或首页。
- 语言子域（fr/es/ru/sa）：译文 slug 无法逐条对应英文，按后缀归并：`-idNNN` 新闻 → `/news.html`，`-pd`/`-pl` 产品 → `/products.html`，其余回首页。
- `/forklift-jib-crane-guide`、`/marine-crane-buying-guide` 两篇文章目前只在旧站，规则已写好但 `enabled:false`，文章迁入新站后再启用。

## 上线切换清单

标 ⚠ 的步骤会影响线上，必须先得到用户确认。

**一、上线前**

1. 补齐内容：旧站独有的文章（上面两篇，以及 10 月新发的）在新站建好同名页面，启用对应规则。
2. 新站放到正式主机的临时地址，用 `check.py --base <临时地址> --host www.bobliftcrane.com` 跑全部规则，再抽查语言子域（`curl -I -H 'Host: fr.bobliftcrane.com' …`）。
3. 确认新站 `home` / `siteurl` 为 `https://www.bobliftcrane.com`，固定链接刷新，`/sitemap.xml`、`robots.txt`、canonical、hreflang 正常。
4. 提前 24 小时把相关 DNS 记录 TTL 降到 300 秒。
5. 如果新站托管在 WordPress.com：附加域名只会整体跳主域，语言子域做不到逐路径跳转，需改用 Cloudflare 重定向规则；自建主机（Lightsail 等）可让子域直接解析到新站，由主题处理。托管方式定了再选。

**二、切换日** ⚠

1. 旧站停止改内容，导出最终 sitemap，与 `redirect-map.csv` 做最后一次差异比对。
2. 部署主题和内容，启用待迁入文章的规则。
3. DNS：`www` 指向新站；根域 `bobliftcrane.com` 301 到 www；`fr/es/ru/sa` 子域也指向新站（或 Cloudflare 规则），保留到跳转稳定。
4. 签发或确认 HTTPS 证书，覆盖 www 与四个子域。
5. 跑 `check.py --base https://www.bobliftcrane.com`，再手测首页、一个产品页、一个旧分类页、旧子域首页，确认 301 且带 `X-Redirect-By: boblift-legacy-redirects`。

**三、上线后** （网页点击由用户本人操作）

1. GSC：同一个域名没有换，不需要「更改地址」。在 Search Console 提交新站 `https://www.bobliftcrane.com/sitemap.xml`，删除旧 sitemap；对首页和几个重点页做网址检查。
2. 若 GSC 里 fr/es/ru/sa 是单独资源，保留到抓取量降下来，不要删除。
3. GA4、Google Ads、社媒主页链接里写死旧路径的，改成新地址。
4. 一周内每天看 GSC「网页索引编码」里的 404 和「已找到未编入」，把漏掉的旧路径用 REST 补进规则。
5. 跳转至少保留 12 个月。

**回滚**：把 `www` 的 DNS 改回旧 SaaS 即可，因 TTL 已降到 300 秒，几分钟内生效。
