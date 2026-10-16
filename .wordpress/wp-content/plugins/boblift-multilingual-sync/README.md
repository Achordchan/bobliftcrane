# BOB-LIFT 翻译传输

在两端安装并启用本插件、Polylang 及相同内容类型；先配置相同的主维护语言和目标语言，并准备好以主维护语言 slug 对应的源内容与源分类。默认主维护语言是 `en`。传输只调用 WordPress 和 Polylang API，不直接操作数据库，不连接另一站点。

## WP-CLI

```sh
wp boblift-mls export ja --file=/tmp/ja.json
wp boblift-mls export ja > /tmp/ja.json
# 将文件复制到目标站点后，在目标站点执行：
wp boblift-mls import /tmp/ja.json --dry-run
wp boblift-mls import /tmp/ja.json
wp boblift-mls import /tmp/ja.json --no-overwrite
wp boblift-mls import /tmp/ja.json --publish
wp boblift-mls import /tmp/ja.json --only=posts,terms
```

默认覆盖已关联译文、分类及收到的选项分区，内容保存为草稿；`--publish` 保留包内 `publish` / `draft` 状态，并在包含 options 且没有错误或缺失源对象时将语言状态设置为 `published`。它不会发布包外内容。语言状态记录本身也属于导入内容；未加 `--publish` 时仍会合并包内状态。`--no-overwrite` 保护已有对象、选项分区和语言记录；显式 `--publish` 仍会在上述条件满足时设置语言发布状态。

`--only` 控制写入范围。仅选 posts 时可以关联已经存在的目标语言分类，不创建分类或写入选项；仅选 terms 时不会修改内容；只有包含 options 才会修改语言状态。`--dry-run` 校验并报告计划计数，完全不写入；它无法预知真实 WordPress 写入钩子、slug 冲突或写入失败。重复导入通过源对象的 Polylang 关联更新，不创建重复译文。计数覆盖内容、分类、选项分区和语言状态记录，并非只有文章数量。

## REST API

REST API = 通过 HTTP 请求调用 WordPress 功能。端点要求已认证用户具有 `manage_options` 权限；使用目标站点允许的 WordPress 认证方式。以下环境变量分别提供站点 URL、用户名及应用程序密码，文档不包含真实凭据。

```sh
curl --fail-with-body --user "$WP_USER:$WP_APP_PASSWORD" \
  "$WP_SITE/wp-json/boblift-mls/v1/export/ja" -o /tmp/ja.json

curl --fail-with-body --user "$WP_USER:$WP_APP_PASSWORD" \
  -H 'Content-Type: application/json' --data-binary @/tmp/ja.json \
  "$WP_SITE/wp-json/boblift-mls/v1/import?dry_run=true"

curl --fail-with-body --user "$WP_USER:$WP_APP_PASSWORD" \
  -H 'Content-Type: application/json' --data-binary @/tmp/ja.json \
  "$WP_SITE/wp-json/boblift-mls/v1/import?publish=true&overwrite=true&only=posts,terms,options"
```

GET 返回数据包本身；POST 返回 `created`、`updated`、`skipped`、`terms_meta_written`、`errors`、`missing_sources`、`warnings`。`terms_meta_written` 按成功应用的分类元数据 key 计数，包含值未变化和显式空字符串；写入失败不计数，`dry_run` 返回计划应用的 key 数量。参数也可放在包的顶层；查询参数优先。`only` 接受数组或逗号分隔字符串；布尔参数接受 true/false。无效数据包返回 400，未知语言返回 404，超过 32 MiB（33,554,432 字节）的导入正文返回 413。导入在单次请求中执行；目标服务器的请求体、内存和执行时间限制也必须允许该请求。

## 包含范围与限制

- 导出目标语言的 `post`、`page`、`product`、`case` 中已发布或草稿且具有主维护语言关联的内容，包括标题、正文、摘要、日期、排序、页面父级、允许的 meta 和分类。主维护语言内容按类型和 slug 精确查找，歧义或缺失会跳过并记录。
- 导出关联的 `category`、`post_tag`、`product_category`，保留名称、slug、描述及源分类父级映射。父子对象在全部创建后关联，包内顺序不影响结果。
- 顶层 `terms[]` 中的 `product_category` 还通过 `meta` 对象传输已设置的 `_boblift_intro_title` 和 `_boblift_intro_text`，包括已存储的空字符串；其他分类元数据从不导出。字符串原样保留空行分段、HTML 实体、非 ASCII 字符、引号和反斜杠，不裁剪、不解码。导入按源 slug 查找并创建或更新目标语言分类后，使用 `update_term_meta` 和 `wp_slash` 写入。省略 key、省略 `meta` 或空 `meta` 均保留现有值；只有显式 `""` 才清空该 key 的值，不删除元数据行。两个 key 必须是字符串（`null` 也拒绝），每个最多 20000 个 Unicode 字符；未知 key 警告并忽略。`--no-overwrite`、`--only` 和 `--dry-run` 同样约束元数据写入。
- meta 的字符串保持字符串，序列化值仅执行 `maybe_unserialize`。`meta` 保存各 key 的首值，可选 `meta_rows` 保存同 key 的全部多值行，保证数组值与多行值没有歧义。该扩展导入时会替换此 key 的元数据行，不删除内容或选项。
- 主题选项通过现有语言选项助手保存，保留分区结构与列表顺序。只传输插件已有分区和 `languages[目标语言]`，合并状态时保留其他语言及其他本地状态。
- 不导出密码、API key、token 等敏感命名字段、`polytranslate_ai` 配置、`form_security`、编辑锁、历史 slug 或 Polylang 内部 meta；嵌套敏感选项字段会被过滤。任意正文或普通字符串里的人工粘贴凭据无法按字段名识别，导出前需自行移除。
- 不传输图片二进制、上传文件、用户、菜单、翻译任务、源语言内容、上述两个字段以外的分类 meta 或其他插件选项。特色图片仅按目标站点已有附件的 `_wp_attached_file` 精确匹配；找不到或不唯一时警告并跳过。匹配后会更新该附件的 alt，该附件若被多篇内容共享，也会受到影响。空 thumbnail 会清除译文的特色图片关联。
- 内容 slug 使用主维护语言 slug，经现有 WordPress / 主题 slug 过滤器处理；case 等路由仍遵循现有唯一 slug 规则，包内译文 slug 不强制覆盖这些规则。正文 URL、meta 和选项中的媒体 ID 不自动跨站替换；两端媒体库不同的情况下需要单独整理。
- 未知字段或选项警告并忽略，不删除内容或选项。写入错误会逐项报告，导入不提供事务回滚；修复原因后可重复导入。实际站点能否运行自定义插件、认证方式及路由，需要在目标环境确认。

## 离线验证

```sh
php .wordpress/wp-content/plugins/boblift-multilingual-sync/tests/transport.php
php .wordpress/wp-content/plugins/boblift-multilingual-sync/tests/transport-cli.php
php .wordpress/wp-content/plugins/boblift-multilingual-sync/tests/transport-term-meta.php
```

测试使用隔离的 WordPress API 替身，验证数据流及零写入预演；不会启动 WordPress、访问数据库或网络。不能代替真实 WordPress、Polylang 和目标站点上的集成验证。
