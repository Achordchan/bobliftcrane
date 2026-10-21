# 老站排名页的 TDK

上线前对比了老站 239 个 sitemap 页面的标题、描述和新站对应页（`tdk.json` 每条的 `why` 写了理由）：

- 产品页和新闻页的标题、描述新站已照搬老站，只是分隔符 `-` 变成 `–`，不改。
- 分类页是主要退步：新站描述都是「X from the BOB-LIFT product catalog.」占位句，几个标题还丢了老站的关键词（Ship Cranes、Cranes for Truck、Pickup Truck Cranes）。`tdk.json` 补回老站关键词并重写描述。
- 首页恢复老站标题（带来 202 次点击的那个），描述写入公司全称。
- 5 篇有点击的文章，老站和新站描述都是「标题, BOB-LIFT」，按正文重写描述，标题不动。
- 不输出 meta keywords：Google 不使用它。

只改英文。运行（WordPress 根目录，`BOBLIFT_BACKUP_DIR` 指向网站目录外的私有文件夹，旧值先备份到那里）：

    wp eval-file <theme>/tools/seo-tdk/apply.php dry-run
    wp eval-file <theme>/tools/seo-tdk/apply.php
