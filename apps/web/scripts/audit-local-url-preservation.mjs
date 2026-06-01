#!/usr/bin/env node

import { execFileSync } from 'node:child_process';
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const scriptDir = dirname(fileURLToPath(import.meta.url));
const repoDir = resolve(scriptDir, '../../..');
const appDir = resolve(scriptDir, '..');

loadDotEnv(resolve(repoDir, '.env'));
loadDotEnv(resolve(appDir, '.env.local'));

const args = parseArgs(process.argv.slice(2));
const input = resolve(repoDir, args.input || 'audit/url-preservation-map.csv');
const outputDir = resolve(repoDir, 'output/url-preservation-audits');
const baseUrl = process.env.AUDIT_BASE_URL || 'http://localhost:3000';
const concurrency = Number(process.env.AUDIT_CONCURRENCY || 4);
const timeoutMs = Number(process.env.AUDIT_TIMEOUT_MS || 30000);
const requestMethod = String(process.env.AUDIT_METHOD || 'GET').toUpperCase();
const output = args.output || resolve(outputDir, `local-url-preservation-${timestamp()}.csv`);
const contentStopWords = new Set([
  'and', 'are', 'for', 'from', 'the', 'with', 'our', 'you', 'your', 'this', 'that', 'all',
  'crane', 'cranes', 'products', 'product', 'equipment', 'machine', 'machinery', 'mounted',
]);

if (!existsSync(input)) {
  console.error(`找不到旧站 URL 清单：${input}`);
  process.exit(1);
}

if (args.syncReport && args.jobId) {
  const reportPath = resolve(repoDir, String(args.syncReport));
  if (!existsSync(reportPath)) {
    console.error(`找不到 URL 验收报告：${reportPath}`);
    process.exit(1);
  }
  const reportRows = parseCsv(readFileSync(reportPath, 'utf8'));
  const okCount = reportRows.filter((item) => item.direct_render === 'true').length;
  const contentMatchCount = reportRows.filter((item) => item.content_match === 'true').length;
  const contentMismatchCount = reportRows.filter((item) => item.content_match === 'false').length;
  const compatibleFallbackCount = reportRows.filter((item) => item.compatible_fallback === 'true').length;
  const redirectCount = reportRows.filter((item) => item.local_location).length;
  const failed = reportRows.filter((item) => item.direct_render !== 'true');
  syncUrlAuditJob(args.jobId, {
    total: reportRows.length,
    directRender: okCount,
    contentMatch: contentMatchCount,
    contentMismatch: contentMismatchCount,
    compatibleFallback: compatibleFallbackCount,
    redirects: redirectCount,
    failed: failed.length,
    reportPath,
    results: reportRows,
  });
  process.exit(0);
}

mkdirSync(dirname(output), { recursive: true });
const rows = parseCsv(readFileSync(input, 'utf8'));
const results = Array.from({ length: rows.length });
let nextIndex = 0;
await Promise.all(Array.from({ length: Math.min(concurrency, rows.length) }, async () => {
  while (nextIndex < rows.length) {
    const index = nextIndex;
    nextIndex += 1;
    const row = rows[index];
    const path = normalizePath(row.path || new URL(row.old_url).pathname || '/');
    const url = new URL(path, baseUrl).toString();
    const result = await checkUrl(url);
    const content = classifyContentMatch(row, result);
    const directRender = result.status >= 200 && result.status < 300 && !result.location;
    results[index] = {
      old_url: row.old_url,
      path,
      old_site_status: row.status,
      type: row.type,
      expected_action: row.action,
      expected_title: row.title,
      local_status: String(result.status),
      local_location: result.location,
      local_title: result.title,
      local_h1: result.h1,
      direct_render: String(directRender),
      content_match: String(content.match),
      compatible_fallback: String(content.compatibleFallback),
      content_match_note: content.note,
      ok: String(directRender && content.match),
    };
    console.log(`[${index + 1}/${rows.length}] ${path} -> ${result.status}${result.location ? ` location=${result.location}` : ''}`);
  }
}));

writeCsv(output, results);
const directRenderCount = results.filter((item) => item.direct_render === 'true').length;
const contentMatchCount = results.filter((item) => item.content_match === 'true').length;
const contentMismatchCount = results.filter((item) => item.content_match === 'false').length;
const compatibleFallbackCount = results.filter((item) => item.compatible_fallback === 'true').length;
const redirectCount = results.filter((item) => item.local_location).length;
const failed = results.filter((item) => item.direct_render !== 'true');
const mismatched = results.filter((item) => item.content_match === 'false');
console.log(`URL audit done. total=${results.length} directRender=${directRenderCount} contentMatch=${contentMatchCount} contentMismatch=${contentMismatchCount} compatibleFallback=${compatibleFallbackCount} redirects=${redirectCount} failed=${failed.length} report=${output}`);
if (failed.length) {
  for (const item of failed.slice(0, 20)) console.log(`[needs-fix] ${item.path} status=${item.local_status} location=${item.local_location}`);
}
if (mismatched.length) {
  for (const item of mismatched.slice(0, 20)) console.log(`[content-mismatch] ${item.path} title=${item.local_title} note=${item.content_match_note}`);
}

if (args.jobId) {
  syncUrlAuditJob(args.jobId, {
    total: results.length,
    directRender: directRenderCount,
    contentMatch: contentMatchCount,
    contentMismatch: contentMismatchCount,
    compatibleFallback: compatibleFallbackCount,
    redirects: redirectCount,
    failed: failed.length,
    reportPath: output,
    results,
  });
}

async function checkUrl(url) {
  let last = { status: 0, location: '', title: '', h1: '', contentText: '' };
  for (let attempt = 1; attempt <= 3; attempt += 1) {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), timeoutMs);
    try {
      last = await Promise.race([
        readUrl(url, controller.signal),
        new Promise((_, reject) => setTimeout(() => reject(new Error(`timeout after ${timeoutMs}ms`)), timeoutMs)),
      ]);
      if (last.status < 500) return last;
    } catch (error) {
      const message = error instanceof Error && error.name === 'AbortError'
        ? `timeout after ${timeoutMs}ms`
        : error instanceof Error ? error.message : String(error);
      last = { status: 0, location: '', title: message, h1: '', contentText: '' };
    } finally {
      clearTimeout(timeout);
    }
    await new Promise((resolve) => setTimeout(resolve, 600 * attempt));
  }
  return last;
}

async function readUrl(url, signal) {
  let response = await fetch(url, { method: requestMethod, redirect: 'manual', signal });
  if (requestMethod === 'HEAD' && response.status === 405) {
    response = await fetch(url, { method: 'GET', redirect: 'manual', signal });
  }
  const location = response.headers.get('location') || '';
  let title = '';
  let h1 = '';
  let contentText = '';
  const contentType = response.headers.get('content-type') || '';
  if (requestMethod !== 'HEAD' && contentType.includes('text/html')) {
    const html = await response.text();
    title = html.match(/<title[^>]*>(.*?)<\/title>/is)?.[1]?.replace(/\s+/g, ' ').trim() || '';
    h1 = html.match(/<h1[^>]*>(.*?)<\/h1>/is)?.[1]?.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim() || '';
    contentText = html
      .replace(/<script[^>]*>[\s\S]*?<\/script>/gi, ' ')
      .replace(/<style[^>]*>[\s\S]*?<\/style>/gi, ' ')
      .replace(/<[^>]+>/g, ' ')
      .replace(/\s+/g, ' ')
      .trim()
      .slice(0, 4000);
  }
  return { status: response.status, location, title, h1, contentText };
}

function classifyContentMatch(row, result) {
  if (!(result.status >= 200 && result.status < 300) || result.location) {
    return { match: false, compatibleFallback: false, note: 'HTTP 未直出，内容未验收。' };
  }

  const type = row.type || '';
  const path = normalizePath(row.path || new URL(row.old_url).pathname || '/');
  const expectedTitle = (row.title || '').trim();
  const localTitle = (result.title || '').trim();
  const localText = `${localTitle} ${result.h1 || ''} ${result.contentText || ''}`;
  const genericProducts = isGenericProductListing(localTitle);
  const productLike = /product|seo_category/i.test(type);

  if (productLike && genericProducts && !isProductIndex(type, path)) {
    return {
      match: false,
      compatibleFallback: true,
      note: '当前直出为通用产品列表，只能算兼容兜底，不能证明旧 URL 内容对版。',
    };
  }

  if (isDirectRenderOnlyType(type)) {
    return {
      match: true,
      compatibleFallback: false,
      note: '该类型只要求旧 URL 直出可访问，不按标题做强匹配。',
    };
  }

  if (!expectedTitle) {
    return {
      match: true,
      compatibleFallback: false,
      note: '旧站清单没有标题，只按 200 无跳转和页面非空验收。',
    };
  }

  const expectedTokens = contentTokens(expectedTitle);
  if (expectedTokens.length === 0) {
    return { match: true, compatibleFallback: false, note: '旧站标题没有可比对关键词，只按直出验收。' };
  }

  const localTokens = new Set(contentTokens(localText));
  const matched = expectedTokens.filter((token) => localTokens.has(token));
  const requiredMatches = expectedTokens.length <= 2 ? 1 : expectedTokens.length <= 4 ? 2 : Math.max(3, Math.ceil(expectedTokens.length * 0.45));

  if (matched.length >= requiredMatches) {
    return {
      match: true,
      compatibleFallback: false,
      note: `标题关键词匹配 ${matched.length}/${expectedTokens.length}。`,
    };
  }

  return {
    match: false,
    compatibleFallback: false,
    note: `标题关键词只匹配 ${matched.length}/${expectedTokens.length}；旧站标题：${expectedTitle}；当前标题：${localTitle || '无标题'}。`,
  };
}

function isProductIndex(type, path) {
  return type === 'product_index' || path === '/products' || path === '/products.html' || path === '/productcategory.html';
}

function isDirectRenderOnlyType(type) {
  return ['about', 'inquiry_contact', 'news_index', 'news_tag', 'product_index', 'support_media_legal'].includes(type);
}

function isGenericProductListing(title) {
  const text = `${title || ''}`.toLowerCase();
  return text.includes('construction equipment') || text.includes('bob-lift product range');
}

function contentTokens(text) {
  return normalizeContentText(text)
    .split(/\s+/)
    .filter((token) => token.length >= 3 && !contentStopWords.has(token));
}

function normalizeContentText(text) {
  return String(text || '')
    .toLowerCase()
    .replace(/xuzhou bob-lift construction machinery co\.?,?\s*ltd\.?/g, ' ')
    .replace(/bob[-\s]?lift/g, ' ')
    .replace(/\b(china|manufacturer|supplier|company|limited|time|discount|hot|selling|currently|promotion)\b/g, ' ')
    .replace(/[^a-z0-9]+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function normalizePath(path) {
  const clean = path.trim() || '/';
  return clean.startsWith('/') ? clean : `/${clean}`;
}

function timestamp() {
  const date = new Date();
  const pad = (value) => String(value).padStart(2, '0');
  return `${date.getFullYear()}${pad(date.getMonth() + 1)}${pad(date.getDate())}-${pad(date.getHours())}${pad(date.getMinutes())}${pad(date.getSeconds())}`;
}

function parseCsv(text) {
  const lines = text.trim().split(/\r?\n/);
  const headers = parseCsvLine(lines.shift() || '');
  return lines.filter(Boolean).map((line) => Object.fromEntries(parseCsvLine(line).map((value, index) => [headers[index], value])));
}

function parseCsvLine(line) {
  const values = [];
  let current = '';
  let quoted = false;
  for (let index = 0; index < line.length; index += 1) {
    const char = line[index];
    if (char === '"') {
      if (quoted && line[index + 1] === '"') {
        current += '"';
        index += 1;
      } else {
        quoted = !quoted;
      }
    } else if (char === ',' && !quoted) {
      values.push(current);
      current = '';
    } else {
      current += char;
    }
  }
  values.push(current);
  return values;
}

function writeCsv(path, data) {
  const headers = Object.keys(data[0] || {});
  const lines = [headers.join(',')];
  for (const row of data) lines.push(headers.map((header) => csvCell(row[header] ?? '')).join(','));
  writeFileSync(path, `${lines.join('\n')}\n`);
}

function csvCell(value) {
  const text = String(value);
  return /[",\n]/.test(text) ? `"${text.replaceAll('"', '""')}"` : text;
}

function syncUrlAuditJob(jobId, report) {
  if (!process.env.DATABASE_URL) {
    console.warn('缺少 DATABASE_URL，无法把 URL 验收结果写回后台。');
    return;
  }

  const status = report.failed > 0 ? 'failed' : report.contentMismatch > 0 ? 'content-mismatch' : 'completed';
  const summary = `URL audit done. total=${report.total}; directRender=${report.directRender}; contentMatch=${report.contentMatch}; contentMismatch=${report.contentMismatch}; compatibleFallback=${report.compatibleFallback}; redirects=${report.redirects}; failed=${report.failed}.`;
  const rowsSql = report.results.map((item, index) => `
    INSERT INTO "product_url_audits_results" (
      "_order", "_parent_id", "id", "path", "type", "expected_action",
      "expected_title", "local_status", "local_location", "local_title", "direct_render",
      "content_match", "compatible_fallback", "content_match_note", "ok"
    ) VALUES (
      ${index}, ${ql(jobId)}, ${ql(`${jobId}-${index + 1}`)}, ${ql(item.path)}, ${ql(item.type)}, ${ql(item.expected_action)},
      ${ql(item.expected_title)}, ${ql(item.local_status)}, ${ql(item.local_location)}, ${ql(item.local_title)},
      ${item.direct_render === 'true' ? 'TRUE' : 'FALSE'},
      ${item.content_match === 'true' ? 'TRUE' : 'FALSE'}, ${item.compatible_fallback === 'true' ? 'TRUE' : 'FALSE'}, ${ql(item.content_match_note)}, ${item.ok === 'true' ? 'TRUE' : 'FALSE'}
    );
  `).join('\n');

  execSql(`
    UPDATE "product_url_audits"
    SET
      "is_current"=FALSE,
      "superseded_at"=now(),
      "updated_at"=now()
    WHERE "id"<>${ql(jobId)}
      AND "is_current" IS DISTINCT FROM FALSE;

    UPDATE "product_url_audits"
    SET
      "status"=${ql(status)}::enum_product_url_audits_status,
      "is_current"=TRUE,
      "superseded_at"=NULL,
      "report_path"=${ql(report.reportPath)},
      "total_urls"=${Number(report.total) || 0},
      "direct_render_count"=${Number(report.directRender) || 0},
      "content_match_count"=${Number(report.contentMatch) || 0},
      "content_mismatch_count"=${Number(report.contentMismatch) || 0},
      "compatible_fallback_count"=${Number(report.compatibleFallback) || 0},
      "redirect_count"=${Number(report.redirects) || 0},
      "failed_count"=${Number(report.failed) || 0},
      "completed_at"=now(),
      "summary"=${ql(summary)},
      "error_message"=${report.failed === 0 && report.contentMismatch === 0 ? "''" : ql(report.failed > 0 ? '存在未直出的旧站 URL，请查看 URL 明细。' : '存在直出但内容不匹配的旧站 URL，请查看“内容是否匹配”和匹配说明。')},
      "updated_at"=now()
    WHERE "id"=${ql(jobId)};

    DELETE FROM "product_url_audits_results" WHERE "_parent_id"=${ql(jobId)};
    ${rowsSql}
  `);
  console.log(`[product-url-audit] synced job=${jobId} status=${status}`);
}

function execSql(sqlText) {
  const connection = new URL(process.env.DATABASE_URL);
  const env = { ...process.env, PGPASSWORD: decodeURIComponent(connection.password) };
  const args = [
    '-h', connection.hostname,
    '-p', connection.port || '5432',
    '-U', decodeURIComponent(connection.username),
    '-d', connection.pathname.slice(1),
    '-v', 'ON_ERROR_STOP=1',
    '-c', sqlText,
  ];
  return execFileSync('psql', args, { env, encoding: 'utf8', maxBuffer: 64 * 1024 * 1024 }).trim();
}

function ql(value) {
  if (value === null || value === undefined) return 'NULL';
  return `'${String(value).replaceAll("'", "''")}'`;
}

function parseArgs(argv) {
  const output = {};
  for (let index = 0; index < argv.length; index += 1) {
    const arg = argv[index];
    if (!arg.startsWith('--')) continue;
    const equalsIndex = arg.indexOf('=');
    if (equalsIndex > -1) {
      output[toCamel(arg.slice(2, equalsIndex))] = arg.slice(equalsIndex + 1);
    } else {
      const key = toCamel(arg.slice(2));
      const next = argv[index + 1];
      if (next && !next.startsWith('--')) {
        output[key] = next;
        index += 1;
      } else {
        output[key] = true;
      }
    }
  }
  return output;
}

function toCamel(value) {
  return value.replace(/-([a-z])/g, (_, letter) => letter.toUpperCase());
}

function loadDotEnv(path) {
  if (!existsSync(path)) return;
  for (const line of readFileSync(path, 'utf8').split(/\r?\n/)) {
    const match = line.match(/^\s*([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)\s*$/);
    if (!match || process.env[match[1]]) continue;
    process.env[match[1]] = match[2].replace(/^['"]|['"]$/g, '');
  }
}
