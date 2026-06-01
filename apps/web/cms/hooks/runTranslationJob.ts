import { spawn } from 'node:child_process';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

import type { CollectionConfig } from 'payload';

type TranslationJobDoc = {
  id: number | string;
  title?: string | null;
  provider?: string | null;
  status?: string | null;
  sourceLocale?: string | null;
  targetLocales?: string[] | null;
  scopes?: string[] | null;
  overwriteExisting?: boolean | null;
  dryRun?: boolean | null;
  glossaryId?: string | null;
};

const filename = fileURLToPath(import.meta.url);
const cmsDir = dirname(dirname(filename));
const appDir = dirname(cmsDir);
const repoDir = resolve(appDir, '../..');
const translatorScript = resolve(appDir, 'scripts/translate-localized-content.mjs');
const runningJobIds = new Set<string>();

type AfterChangeHook = NonNullable<NonNullable<CollectionConfig['hooks']>['afterChange']>[number];

export const runTranslationJobAfterChange: AfterChangeHook = async ({
  doc,
  req,
}) => {
  const translationJob = doc as TranslationJobDoc;
  if (translationJob.provider && !['deepl', 'deeplx'].includes(translationJob.provider)) return doc;
  if (translationJob.status !== 'queued') return doc;

  const jobId = String(translationJob.id);
  if (runningJobIds.has(jobId)) return doc;
  runningJobIds.add(jobId);

  void startTranslationJob(req.payload, translationJob);

  return doc;
};

async function startTranslationJob(payload: any, doc: TranslationJobDoc) {
  try {
    await payload.update({
      collection: 'translation-jobs',
      id: doc.id,
      data: {
        status: 'running',
        lastRunAt: new Date().toISOString(),
        summary: `后台已启动 ${providerLabel(doc.provider)} 翻译任务，完成后会自动更新本记录。`,
        errorMessage: '',
      },
    });

    const child = spawn(process.execPath, buildArgs(doc), {
      cwd: repoDir,
      env: process.env,
      stdio: ['ignore', 'pipe', 'pipe'],
    });

    let stdout = '';
    let stderr = '';
    child.stdout.on('data', (chunk) => {
      stdout = tail(`${stdout}${chunk.toString()}`, 12000);
    });
    child.stderr.on('data', (chunk) => {
      stderr = tail(`${stderr}${chunk.toString()}`, 6000);
    });
    child.on('close', async (code) => {
      runningJobIds.delete(String(doc.id));
      const result = resolveTranslationResult({
        code,
        stdout,
        stderr,
        isCoverageRun: doc.dryRun !== false,
      });

      try {
        await payload.update({
          collection: 'translation-jobs',
          id: doc.id,
          data: {
            status: result.status,
            lastRunAt: new Date().toISOString(),
            summary: result.summary,
            errorMessage: result.errorMessage,
          },
        });
      } catch (error) {
        console.error('Translation job status update failed.', error);
      }
    });
  } catch (error) {
    runningJobIds.delete(String(doc.id));
    console.error('Translation job runner failed to start.', error);
  }
}

function buildArgs(doc: TranslationJobDoc) {
  const args = [translatorScript];
  if (doc.provider) args.push(`--provider=${doc.provider}`);
  if (doc.sourceLocale) args.push(`--source=${doc.sourceLocale}`);
  if (doc.targetLocales?.length) args.push(`--targets=${doc.targetLocales.join(',')}`);
  if (doc.scopes?.length) args.push(`--scopes=${doc.scopes.join(',')}`);
  if (doc.title) args.push(`--job-title=${doc.title}`);
  if (doc.glossaryId) args.push(`--glossary-id=${doc.glossaryId}`);

  args.push('--include-stale', '--no-job');
  if (doc.overwriteExisting) args.push('--overwrite');
  if (doc.dryRun === false) {
    args.push('--apply');
  } else {
    args.push('--coverage', '--sync-readiness');
  }

  return args;
}

function resolveTranslationResult({
  code,
  stdout,
  stderr,
  isCoverageRun,
}: {
  code: number | null;
  stdout: string;
  stderr: string;
  isCoverageRun: boolean;
}) {
  const rawCombined = `${stdout || ''}\n${stderr || ''}`;
  const output = tail(stdout || '');
  const errorOutput = tail(stderr || '');

  if (mentionsMissingCredentials(rawCombined)) {
    return {
      status: 'needs-credentials',
      summary: output || `${providerLabelFromOutput(rawCombined)} 翻译任务未执行：缺少服务配置。`,
      errorMessage: errorOutput || '请先在运行环境配置 DEEPL_AUTH_KEY / DEEPL_API_KEY，或 DEEPLX_API_URL / DEEPLX_ENDPOINT / DEEPLX_API_KEY，再重新排队任务。',
    };
  }

  if (code !== 0) {
    return {
      status: 'failed',
      summary: output || `翻译任务退出码：${code}`,
      errorMessage: errorOutput || '翻译任务失败，请检查环境变量和后台翻译设置。',
    };
  }

  if (isCoverageRun) {
    const missingCount = missingTranslationCount(rawCombined);
    if (missingCount > 0) {
      const hasCredentials = hasTranslationCredentials(rawCombined);
      return {
        status: hasCredentials ? 'coverage-needed' : 'needs-credentials',
        summary: coverageSummaryFromOutput(rawCombined, missingCount),
        errorMessage: hasCredentials
          ? '覆盖率未通过。请关闭 dryRun 后重新排队，写入机器翻译译文。'
          : '覆盖率未通过且当前运行环境缺少 DeepL Key 或 DeepLX 接口地址。',
      };
    }
  }

  return {
    status: 'machine-translated',
    summary: output || '机器翻译已写入，下一步请人工复核，确认后把任务状态改为“已批准上线”。',
    errorMessage: '',
  };
}

function mentionsMissingCredentials(value: string) {
  return /缺少\s*(?:DeepL|DeepLX)|DEEPL_AUTH_KEY|DEEPL_API_KEY|DEEPLX_API_URL|DEEPLX_ENDPOINT|DEEPLX_API_KEY|auth key/i.test(value);
}

function hasTranslationCredentials(value: string) {
  if (/deeplx/i.test(value)) return Boolean(process.env.DEEPLX_API_URL || process.env.DEEPLX_ENDPOINT || process.env.DEEPLX_URL || process.env.DEEPLX_API_KEY || process.env.DEEPLX_AUTH_KEY);
  return Boolean(process.env.DEEPL_AUTH_KEY || process.env.DEEPL_API_KEY);
}

function providerLabel(value?: string | null) {
  return value === 'deeplx' ? 'DeepLX' : 'DeepL';
}

function providerLabelFromOutput(value: string) {
  return /deeplx/i.test(value) ? 'DeepLX' : 'DeepL';
}

function missingTranslationCount(value: string) {
  const patterns = [
    /missingOrStaleFields=(\d+)/i,
    /missingFields=(\d+)/i,
    /Coverage\s+(?:missing\/stale|missing):\s+fields=(\d+)/i,
    /\[locale\]\s+[a-z-]+:\s+missingOrStale=(\d+)/i,
    /\[locale\]\s+[a-z-]+:\s+missing=(\d+)/i,
  ];

  let highestCount = 0;
  for (const pattern of patterns) {
    const matches = value.matchAll(new RegExp(pattern.source, pattern.flags.includes('g') ? pattern.flags : `${pattern.flags}g`));
    for (const match of matches) {
      if (match?.[1]) highestCount = Math.max(highestCount, Number(match[1]));
    }
  }

  return highestCount;
}

function coverageSummaryFromOutput(value: string, missingCount: number) {
  const line = value
    .split(/\r?\n/)
    .map((item) => item.trim())
    .find((item) => /翻译覆盖检查|Coverage\s+(?:missing\/stale|missing)/i.test(item));

  return tail(line
    ? `翻译覆盖检查未通过：缺失或过期字段 ${missingCount} 个。${line}`
    : `翻译覆盖检查未通过：缺失或过期字段 ${missingCount} 个。`);
}

function tail(value: string, maxLength = 1800) {
  const text = value.trim();
  return text.length > maxLength ? text.slice(-maxLength) : text;
}
