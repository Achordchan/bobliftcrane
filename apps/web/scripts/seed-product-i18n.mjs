#!/usr/bin/env node

import { spawnSync } from 'node:child_process';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const scriptDir = dirname(fileURLToPath(import.meta.url));
const translatorScript = resolve(scriptDir, 'translate-localized-content.mjs');

const passThroughArgs = process.argv.slice(2);
const hasScopes = passThroughArgs.some((arg) => arg === '--scopes' || arg.startsWith('--scopes='));
const hasTargets = passThroughArgs.some((arg) => arg === '--targets' || arg.startsWith('--targets='));
const hasCoverage = passThroughArgs.includes('--coverage');
const hasApply = passThroughArgs.includes('--apply');

const args = [translatorScript];
if (!hasScopes) args.push('--scopes=products,product_categories,product_listing_ui,product_detail_ui');
if (!hasTargets) args.push('--targets=zh,es,ar,fr,de,pt,ru,tr,id,vi,th,hi,ko');
args.push(...passThroughArgs);

if (!hasCoverage && !hasApply) {
  console.log('产品多语言不再使用硬编码译文；当前默认只预览 DeepL 翻译计划。写入数据库请追加 --apply。');
}

const result = spawnSync(process.execPath, args, {
  cwd: resolve(scriptDir, '..'),
  env: process.env,
  stdio: 'inherit',
});

process.exit(result.status ?? 1);
