# 旧站 → 新站 301 跳转

旧站是 SaaS 托管的 www.bobliftcrane.com，另有 fr / es / ru / sa 四个语言子域。新站上线后，旧链接由主题 `inc/legacy-redirects.php` 在 `template_redirect` 最前面（内容类型都注册好之后、正常路由之前）301 到新地址，保留 utm、gclid 等查询参数。

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

`to` 可以是站内路径（`/marine-crane`）或 https 绝对地址（跳 bobliftloader.com）。路径匹配不分大小写、忽略结尾斜杠。新站当前正在使用的地址（含后台改过的公开地址、产品、页面、分类）不会被规则抢走：来源命中的是现役内容时直接放行，不跳转。

## 映射原则

- 新站沿用了旧站 `/slug.html` 的结构，产品、新闻 140 条路径一致或只差大小写，无需也无法再「映射」，大小写差异的 50 条已列入规则。
- 旧分类页（`Products-*.html`、单数命名的分类、按吨位页）统一到新站分类页或 `/products.html`。
- 滑移装载机、迷你翻斗车、搅拌车：跳 bobliftloader.com 对应产品页（型号后缀 T/S、-H/-C 为按名称推断，待业务确认）。
- SaaS 自动生成的关键词页（`/products/*.html`、`/news/*.html`、keywords）归到最相关的分类或首页。
- 语言子域（fr/es/ru/sa）：译文 slug 无法逐条对应英文，按后缀归并：`-idNNN` 新闻 → `/news.html`，`-pd`/`-pl` 产品 → `/products.html`；英文 slug 的页面落到同路径；其余回首页。以上全部落到该语言自己的子目录（fr→/fr/、es→/es/、ru→/ru/、sa→/ar/，由 `language_host_prefix` 配置），在这些语言上线前是占位，会先 404，上线后自动生效；不上某个语言就删掉它的 prefix，改落英文。子域请求经 Cloudflare 带 `?legacy_lang=xx` 进入主题处理。
- `/forklift-jib-crane-guide`、`/marine-crane-buying-guide` 两篇文章目前只在旧站，规则已写好但 `enabled:false`，文章迁入新站后再启用。

## 上线切换清单

标 ⚠ 的步骤会影响线上，必须先得到用户确认。

**一、上线前**（定案：新站托管在 WordPress.com，多语言用子目录 `/ja/`、`/zh/`，英文在根目录）

1. 补齐内容：旧站独有的文章（上面两篇，以及 10 月新发的）在新站建好同名页面，启用对应规则。
2. Polylang 从「按域名」改成「按目录」：设置里 URL 修改选「从固定链接中的目录名设置语言」，清空各语言域名，英文隐藏语言前缀，保存后刷新固定链接。改完在本地确认 `/foo.html` 与 `/ja/foo.html`、分类页、`/feedback/…` 都能打开。
3. 新站放 WordPress.com（Premium 以上才能上传自定义主题和插件）；主题和插件 zip 上传，先在 WP.com 默认地址上测。用 `check.py --base <WP.com 临时地址> --host www.bobliftcrane.com` 跑全部规则。
4. 语言子域 fr/es/ru/sa：WP.com 的附加域名只会整体跳主域，不能逐路径跳，所以由 Cloudflare 接管这四个子域（域名 DNS 要在 Cloudflare，免费版即可）。每个子域建一条重定向规则（共 4 条，把 `fr` 换成对应前缀）：条件是主机名等于 `fr.bobliftcrane.com`，类型选动态，状态码 301，目标表达式：

   `concat("https://www.bobliftcrane.com", http.request.uri.path, "?legacy_lang=fr&", http.request.uri.query)`

   （Cloudflare 重定向表达式不支持 `if()`，所以无条件拼接；原链接没有查询参数时结尾会多一个 `&`，主题会忽略它。）这样原路径和原查询参数（gclid、utm 等）都会带过去，不会在第一跳丢掉。保存前用规则预览确认表达式可用。主题认到 `legacy_lang` 后按语言子域的归并规则再 301 一次到最终地址，并去掉这个参数。这样是两跳；想一跳到位，可改用 Cloudflare 批量重定向列表，把 `redirect-map.csv` 对应的语言子域地址逐条导入（条数受套餐限制，先确认）。
5. 新站没有 fr/es/ru/sa 版本，这些子域的页面统一落到英文对应页；以后要加某种语言，在 `legacy-redirects.json` 里把该语言的目标改成 `/xx/…` 即可。
6. 确认新站 `home` / `siteurl` 为 `https://www.bobliftcrane.com`，`/sitemap.xml`、`robots.txt`、canonical、hreflang 正常。
7. 提前 24 小时把相关 DNS 记录 TTL 降到 300 秒。

**二、切换日** ⚠

1. 旧站停止改内容，导出最终 sitemap，与 `redirect-map.csv` 做最后一次差异比对。
2. 在 WP.com 把 `www.bobliftcrane.com` 设为主域名，按 WP.com 给出的记录改 DNS（Cloudflare 里用 A 记录、灰云，不要改名称服务器）；根域 301 到 www。
3. 同时在 Cloudflare 启用第 4 步的子域重定向规则，子域 DNS 记录保持代理（橙云）。
4. 等 WP.com 自动签发 HTTPS 证书，覆盖 www。
5. 跑 `check.py --base https://www.bobliftcrane.com`，再手测首页、一个产品页、一个旧分类页、`/ja/` 页面，以及 `fr.bobliftcrane.com` 首页和一个深层页面，确认 301 且带 `X-Redirect-By: boblift-legacy-redirects`；再测一个带参数的地址，如 `curl -I 'https://fr.bobliftcrane.com/message.html?gclid=abc'`，两跳之后的最终地址里必须还有 `gclid=abc`。

**三、上线后** （网页点击由用户本人操作）

1. GSC：同一个域名没有换，不需要「更改地址」。在 Search Console 提交新站 `https://www.bobliftcrane.com/sitemap.xml`，删除旧 sitemap；对首页和几个重点页做网址检查。
2. 若 GSC 里 fr/es/ru/sa 是单独资源，保留到抓取量降下来，不要删除。
3. GA4、Google Ads、社媒主页链接里写死旧路径的，改成新地址。
4. 一周内每天看 GSC「网页索引编码」里的 404 和「已找到未编入」，把漏掉的旧路径用 REST 补进规则。
5. 跳转至少保留 12 个月。

**回滚**：把 `www` 的 DNS 改回旧 SaaS 即可，因 TTL 已降到 300 秒，几分钟内生效。
