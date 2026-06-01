import { spawn } from 'node:child_process';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

import type { CollectionConfig } from 'payload';

type ProductImportBatchDoc = {
  id: number | string;
  status?: string | null;
};

const filename = fileURLToPath(import.meta.url);
const cmsDir = dirname(dirname(filename));
const appDir = dirname(cmsDir);
const repoDir = resolve(appDir, '../..');
const runnerScript = resolve(appDir, 'scripts/run-product-import-batch.mjs');
const runningJobIds = new Set<string>();

type AfterChangeHook = NonNullable<NonNullable<CollectionConfig['hooks']>['afterChange']>[number];

export const runProductImportBatchAfterChange: AfterChangeHook = async ({ doc, req }) => {
  const batch = doc as ProductImportBatchDoc;
  if (batch.status !== 'queued') return doc;

  const jobId = String(batch.id);
  if (runningJobIds.has(jobId)) return doc;
  runningJobIds.add(jobId);

  let stderr = '';
  const child = spawn(process.execPath, [runnerScript, `--job-id=${jobId}`], {
    cwd: repoDir,
    env: process.env,
    stdio: ['ignore', 'ignore', 'pipe'],
  });

  child.stderr.on('data', (chunk) => {
    stderr = tail(`${stderr}${chunk.toString()}`, 4000);
    console.error(chunk.toString());
  });
  child.on('error', async (error) => {
    runningJobIds.delete(jobId);
    await markBatchFailed(req.payload, batch.id, `产品导入任务启动失败：${error.message}`);
  });
  child.on('close', async (code, signal) => {
    runningJobIds.delete(jobId);
    if (signal || (code && code !== 0)) {
      await markBatchFailed(req.payload, batch.id, stderr || `产品导入任务异常退出，退出码：${code ?? '无'}，信号：${signal ?? '无'}`);
    }
  });

  return doc;
};

async function markBatchFailed(payload: Parameters<AfterChangeHook>[0]['req']['payload'], id: ProductImportBatchDoc['id'], message: string) {
  try {
    await payload.update({
      collection: 'product-import-batches',
      id,
      overrideAccess: true,
      data: {
        status: 'failed',
        completedAt: new Date().toISOString(),
        summary: '后台产品导入任务未正常完成。',
        errorMessage: tail(message, 900),
      },
    });
  } catch (error) {
    console.error('Product import batch failure status update failed.', error);
  }
}

function tail(value: string, maxLength = 1800) {
  const text = value.trim();
  return text.length > maxLength ? text.slice(-maxLength) : text;
}
