#!/usr/bin/env node

import { execFileSync, spawnSync } from 'node:child_process';
import { existsSync, mkdirSync, readFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const scriptDir = dirname(fileURLToPath(import.meta.url));
const appDir = resolve(scriptDir, '..');
const repoDir = resolve(appDir, '../..');

loadDotEnv(resolve(repoDir, '.env'));
loadDotEnv(resolve(appDir, '.env.local'));

const args = parseArgs(process.argv.slice(2));
if (!args.jobId) exitWithError('缺少 --job-id');
if (!process.env.DATABASE_URL) exitWithError('缺少 DATABASE_URL');

const batch = readBatch(args.jobId);
if (!batch) exitWithError(`找不到产品导入批次：${args.jobId}`);

const batchId = batch.batch_id || `legacy-${new Date().toISOString().slice(0, 10).replaceAll('-', '')}-${args.jobId}`;
const runAction = batch.run_action || 'source-audit';
const reportPath = resolve(repoDir, 'output/product-source-audits', `backend-batch-${batchId}.csv`);

try {
  updateBatch(args.jobId, {
    status: 'reviewing',
    started: true,
    summary: `后台已开始执行：${runAction}。`,
    error: '',
    reportPath,
  });

  if (runAction === 'import-and-source-audit' || runAction === 'dry-run-import') {
    const importArgs = [resolve(appDir, 'scripts/import-legacy-products.py'), '--batch-id', batchId];
    if (runAction === 'dry-run-import') importArgs.push('--dry-run');
    if (batch.overwrite_policy === 'rewrite-unverified') importArgs.push('--overwrite-unverified');
    runCommand('python3', importArgs);
  }

  if (runAction !== 'dry-run-import') {
    runCommand('python3', [
      resolve(appDir, 'scripts/audit-product-source.py'),
      '--mark-exact',
      '--output',
      reportPath,
    ]);
  }

  const stats = readProductStats(batchId);
  const failures = readAuditFailures(reportPath);
  writeRelations(args.jobId, batchId);
  writeFailureLogs(args.jobId, failures);

  updateBatch(args.jobId, {
    status: failures.length === 0 ? 'completed' : 'failed',
    completed: true,
    summary: `后台执行完成：action=${runAction}; products=${stats.productCount}; verified=${stats.verifiedCount}; needsReview=${stats.needsReviewCount}; drawingRecords=${stats.drawingReviewedCount}; failures=${failures.length}.`,
    error: failures.length === 0 ? '' : '存在需要人工复核的产品，请查看失败记录和报告文件。',
    reportPath,
    stats,
  });
} catch (error) {
  updateBatch(args.jobId, {
    status: 'failed',
    completed: true,
    summary: `后台执行失败：action=${runAction}.`,
    error: error instanceof Error ? error.message : String(error),
    reportPath,
  });
  throw error;
}

function readBatch(id) {
  return rows(`
    SELECT id, batch_id, status::text, run_action::text, overwrite_policy::text
    FROM product_import_batches
    WHERE id=${ql(id)}
    LIMIT 1
  `)[0];
}

function readProductStats(batchId) {
  const row = rows(`
    SELECT
      count(*)::int AS product_count,
      count(*) FILTER (WHERE parameter_review_status::text = 'verified-from-source')::int AS verified_count,
      count(*) FILTER (WHERE parameter_review_status::text = 'in-review')::int AS in_review_count,
      count(*) FILTER (WHERE parameter_review_status::text = 'needs-review')::int AS needs_review_count,
      0 AS drawing_review_needed_count,
      COALESCE((
        SELECT count(*)::int
        FROM products_drawing_images pdi
        JOIN products p2 ON p2.id = pdi._parent_id
        WHERE p2.import_batch_id=${ql(batchId)}
      ), 0) AS drawing_reviewed_count
    FROM products
    WHERE import_batch_id=${ql(batchId)}
  `)[0] || {};

  return {
    productCount: Number(row.product_count || 0),
    importedCount: Number(row.product_count || 0),
    verifiedCount: Number(row.verified_count || 0),
    inReviewCount: Number(row.in_review_count || 0),
    needsReviewCount: Number(row.needs_review_count || 0),
    drawingReviewNeededCount: Number(row.drawing_review_needed_count || 0),
    drawingReviewedCount: Number(row.drawing_reviewed_count || 0),
    failedCount: 0,
    skippedCount: 0,
  };
}

function readAuditFailures(path) {
  if (!existsSync(path)) return [];
  const rows = parseCsv(readFileSync(path, 'utf8'));
  return rows
    .filter((row) => row.recommendation !== 'mark-verified' || row.sourceParsed !== 'True' || row.titleMatch !== 'True' || row.specsMatch !== 'True')
    .slice(0, 100)
    .map((row) => ({
      sourceUrl: row.sourceUrl || '',
      productTitle: row.title || row.slug || '',
      error: row.error || [row.missingSourceLabelsInDb, row.mismatchedLabels].filter(Boolean).join('; ') || '需要人工复核',
    }));
}

function updateBatch(id, options) {
  const sets = [
    `"status"=${ql(options.status)}::enum_product_import_batches_status`,
    `"updated_at"=now()`,
  ];
  if (options.started) sets.push(`"started_at"=now()`);
  if (options.completed) sets.push(`"completed_at"=now()`);
  if (typeof options.reportPath === 'string') sets.push(`"report_path"=${ql(options.reportPath)}`);
  if (typeof options.summary === 'string') sets.push(`"summary"=${ql(options.summary.slice(0, 900))}`);
  if (typeof options.error === 'string') sets.push(`"error_message"=${ql(options.error.slice(0, 900))}`);
  if (options.stats) {
    sets.push(`"product_count"=${options.stats.productCount}`);
    sets.push(`"imported_count"=${options.stats.importedCount}`);
    sets.push(`"verified_count"=${options.stats.verifiedCount}`);
    sets.push(`"in_review_count"=${options.stats.inReviewCount}`);
    sets.push(`"needs_review_count"=${options.stats.needsReviewCount}`);
    sets.push(`"drawing_review_needed_count"=${options.stats.drawingReviewNeededCount}`);
    sets.push(`"drawing_reviewed_count"=${options.stats.drawingReviewedCount}`);
    sets.push(`"failed_count"=${options.stats.failedCount}`);
    sets.push(`"skipped_count"=${options.stats.skippedCount}`);
  }
  execSql(`UPDATE product_import_batches SET ${sets.join(', ')} WHERE id=${ql(id)};`);
}

function writeRelations(id, batchId) {
  execSql(`
    DELETE FROM product_import_batches_rels WHERE parent_id=${ql(id)} AND path='products';
    INSERT INTO product_import_batches_rels ("order", parent_id, path, products_id)
    SELECT row_number() OVER (ORDER BY p.id) - 1, ${ql(id)}, 'products', p.id
    FROM products p
    WHERE p.import_batch_id=${ql(batchId)}
    ORDER BY p.id;
  `);
}

function writeFailureLogs(id, failures) {
  const values = failures.map((item, index) => `
    (${index}, ${ql(id)}, ${ql(`${id}-${index + 1}`)}, ${ql(item.sourceUrl)}, ${ql(item.productTitle)}, ${ql(item.error)})
  `).join(',\n');
  execSql(`
    DELETE FROM product_import_batches_failure_logs WHERE _parent_id=${ql(id)};
    ${values ? `INSERT INTO product_import_batches_failure_logs (_order, _parent_id, id, source_url, product_title, error) VALUES ${values};` : ''}
  `);
}

function runCommand(command, commandArgs) {
  mkdirSync(resolve(repoDir, 'output/product-source-audits'), { recursive: true });
  const result = spawnSync(command, commandArgs, {
    cwd: repoDir,
    env: process.env,
    encoding: 'utf8',
    maxBuffer: 64 * 1024 * 1024,
  });
  if (result.status !== 0) {
    throw new Error(`${command} ${commandArgs.join(' ')} failed: ${(result.stderr || result.stdout || '').slice(-1200)}`);
  }
}

function rows(sqlText) {
  const json = execPsql(`SELECT COALESCE(json_agg(row_to_json(q)), '[]'::json) FROM (${sqlText}) q;`, ['-t', '-A']);
  return JSON.parse(json || '[]');
}

function execSql(sqlText) {
  execPsql(sqlText, []);
}

function execPsql(sqlText, extraArgs) {
  const connection = new URL(process.env.DATABASE_URL);
  const env = { ...process.env, PGPASSWORD: decodeURIComponent(connection.password) };
  const commandArgs = [
    '-h', connection.hostname,
    '-p', connection.port || '5432',
    '-U', decodeURIComponent(connection.username),
    '-d', connection.pathname.slice(1),
    '-v', 'ON_ERROR_STOP=1',
    ...extraArgs,
    '-c', sqlText,
  ];
  return execFileSync('psql', commandArgs, { env, encoding: 'utf8', maxBuffer: 64 * 1024 * 1024 }).trim();
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

function parseArgs(argv) {
  return Object.fromEntries(argv.map((arg) => {
    const match = arg.match(/^--([^=]+)=(.*)$/);
    return match ? [toCamel(match[1]), match[2]] : [toCamel(arg.replace(/^--/, '')), true];
  }));
}

function toCamel(value) {
  return value.replace(/-([a-z])/g, (_, letter) => letter.toUpperCase());
}

function ql(value) {
  if (value === null || value === undefined) return 'NULL';
  return `'${String(value).replaceAll("'", "''")}'`;
}

function loadDotEnv(path) {
  if (!existsSync(path)) return;
  for (const line of readFileSync(path, 'utf8').split(/\r?\n/)) {
    const match = line.match(/^\s*([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)\s*$/);
    if (!match || process.env[match[1]]) continue;
    process.env[match[1]] = match[2].replace(/^['"]|['"]$/g, '');
  }
}

function exitWithError(message) {
  console.error(message);
  process.exit(1);
}
