import { spawn } from 'node:child_process';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

import type { CollectionConfig } from 'payload';

type ProductUrlAuditDoc = {
  id: number | string;
  baseUrl?: string | null;
  status?: string | null;
};

const filename = fileURLToPath(import.meta.url);
const cmsDir = dirname(dirname(filename));
const appDir = dirname(cmsDir);
const repoDir = resolve(appDir, '../..');
const auditScript = resolve(appDir, 'scripts/audit-local-url-preservation.mjs');
const runningJobIds = new Set<string>();

type AfterChangeHook = NonNullable<NonNullable<CollectionConfig['hooks']>['afterChange']>[number];

export const runProductUrlAuditAfterChange: AfterChangeHook = async ({ doc, req }) => {
  const audit = doc as ProductUrlAuditDoc;
  if (audit.status !== 'queued') return doc;

  const jobId = String(audit.id);
  if (runningJobIds.has(jobId)) return doc;
  runningJobIds.add(jobId);

  void startProductUrlAudit(req.payload, audit);

  return doc;
};

async function startProductUrlAudit(payload: Parameters<AfterChangeHook>[0]['req']['payload'], audit: ProductUrlAuditDoc) {
  const jobId = String(audit.id);
  try {
    await new Promise((resolve) => setTimeout(resolve, 250));
    await payload.update({
      collection: 'product-url-audits',
      id: audit.id,
      overrideAccess: true,
      data: {
        status: 'running',
        isCurrent: true,
        supersededAt: null,
        lastRunAt: new Date().toISOString(),
        summary: '后台已开始检查旧网站地址，完成后会自动写入明细。',
        errorMessage: '',
      },
    });
  } catch (error) {
    runningJobIds.delete(jobId);
    console.error('Product URL audit status update failed.', error);
    return;
  }

  let stderr = '';
  const child = spawn(process.execPath, [
    auditScript,
    `--job-id=${jobId}`,
  ], {
    cwd: repoDir,
    env: {
      ...process.env,
      AUDIT_BASE_URL: audit.baseUrl || process.env.NEXT_PUBLIC_SITE_URL || 'http://localhost:3000',
      AUDIT_CONCURRENCY: process.env.AUDIT_CONCURRENCY || '8',
      AUDIT_TIMEOUT_MS: process.env.AUDIT_TIMEOUT_MS || '60000',
    },
    stdio: ['ignore', 'ignore', 'pipe'],
  });

  child.stderr.on('data', (chunk) => {
    stderr = tail(`${stderr}${chunk.toString()}`, 4000);
    console.error(chunk.toString());
  });
  child.on('error', async (error) => {
    runningJobIds.delete(jobId);
    await markProductUrlAuditFailed(payload, audit.id, `旧网站地址检查启动失败：${error.message}`);
  });
  child.on('close', async (code, signal) => {
    runningJobIds.delete(jobId);
    if (signal || (code && code !== 0)) {
      await markProductUrlAuditFailed(payload, audit.id, stderr || `旧网站地址检查异常退出，退出码：${code ?? '无'}，信号：${signal ?? '无'}`);
    }
  });
}

async function markProductUrlAuditFailed(
  payload: Parameters<AfterChangeHook>[0]['req']['payload'],
  id: ProductUrlAuditDoc['id'],
  message: string,
) {
  try {
    await payload.update({
      collection: 'product-url-audits',
      id,
      overrideAccess: true,
      data: {
        status: 'failed',
        completedAt: new Date().toISOString(),
        summary: '旧网站地址检查未正常完成。',
        errorMessage: tail(message, 900),
      },
    });
  } catch (error) {
    console.error('Product URL audit failure status update failed.', error);
  }
}

function tail(value: string, maxLength = 1800) {
  const text = value.trim();
  return text.length > maxLength ? text.slice(-maxLength) : text;
}
