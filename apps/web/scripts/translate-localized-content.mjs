#!/usr/bin/env node

import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import { existsSync, readFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const scriptDir = dirname(fileURLToPath(import.meta.url));
const appDir = resolve(scriptDir, '..');
const repoDir = resolve(appDir, '../..');

loadDotEnv(resolve(repoDir, '.env'));
loadDotEnv(resolve(appDir, '.env.local'));

const args = parseArgs(process.argv.slice(2));
const translationSettings = readTranslationSettings();
const sourceLocale = args.source || process.env.TRANSLATION_SOURCE_LOCALE || translationSettings.sourceLocale || 'en';
const defaultTargetLocales = 'zh,es,ar,fr,de,pt,ru,tr,id,vi,th,hi,ko';
const targetLocales = csv(args.targets || process.env.TRANSLATION_TARGET_LOCALES || translationSettings.targetLocales.join(',') || defaultTargetLocales).filter((item) => item !== sourceLocale);
const apply = Boolean(args.apply);
const coverage = Boolean(args.coverage);
const overwrite = Boolean(args.overwrite);
const copySafeOnly = Boolean(args.copySafeOnly);
const includeStale = coverage || overwrite || Boolean(args.includeStale);
const scopes = csv(args.scopes || 'site_settings,home_settings,product_listing_ui,product_detail_ui,page_text_settings,product_categories,products,articles,cases,media');
const provider = String(args.provider || process.env.TRANSLATION_PROVIDER || translationSettings.provider || 'deepl').toLowerCase();
const authKeyCandidates = [...new Set([
  args.authKeyEnv,
  provider === 'deeplx' ? translationSettings.deeplxAuthKeyEnvName : translationSettings.authKeyEnvName,
  provider === 'deeplx' ? 'DEEPLX_API_KEY' : 'DEEPL_AUTH_KEY',
  provider === 'deeplx' ? 'DEEPLX_AUTH_KEY' : 'DEEPL_API_KEY',
].filter(Boolean))];
const authKeyName = authKeyCandidates.find((name) => process.env[name]) || authKeyCandidates[0] || (provider === 'deeplx' ? 'DEEPLX_API_KEY' : 'DEEPL_AUTH_KEY');
const authKey = process.env[authKeyName];
const deeplxEndpointCandidates = [...new Set([
  args.deeplxApiUrlEnv,
  translationSettings.deeplxApiUrlEnvName,
  'DEEPLX_API_URL',
  'DEEPLX_ENDPOINT',
  'DEEPLX_URL',
].filter(Boolean))];
const deeplxEndpointName = deeplxEndpointCandidates.find((name) => process.env[name]) || deeplxEndpointCandidates[0] || 'DEEPLX_API_URL';
const configuredDeepLXEndpoint = process.env[deeplxEndpointName] || args.deeplxEndpoint || '';
const deeplxEndpoint = configuredDeepLXEndpoint || (provider === 'deeplx' && authKey ? 'https://api.deeplx.org' : '');
const configuredFormality = args.formality || process.env.DEEPL_FORMALITY || translationSettings.formality || 'default';
const protectedTerms = translationSettings.protectedTerms.length > 0 ? translationSettings.protectedTerms : ['BOB-LIFT'];
const deeplxConcurrency = Math.max(1, Number(args.deeplxConcurrency || process.env.DEEPLX_CONCURRENCY || 1) || 1);
const deeplxRetries = Math.max(0, Number(args.deeplxRetries || process.env.DEEPLX_RETRIES || 3) || 3);
const deeplxRetryBaseMs = Math.max(250, Number(args.deeplxRetryBaseMs || process.env.DEEPLX_RETRY_BASE_MS || 1200) || 1200);
const deeplxSourceLang = String(args.deeplxSource || process.env.DEEPLX_SOURCE_LANG || 'AUTO').toUpperCase();
const translationCache = new Map();

if (!process.env.DATABASE_URL) exitWithHelp('缺少 DATABASE_URL，请先配置 apps/web/.env.local。');
if (!translationSettings.enabled && !args.listTables && !coverage) exitWithHelp('后台“翻译插件设置”未启用翻译流程。');
if (!['deepl', 'deeplx'].includes(provider)) exitWithHelp(`当前脚本只支持 DeepL 或 DeepLX，后台配置为：${provider}`);
if (targetLocales.length === 0) exitWithHelp('缺少目标语言。示例：--targets=zh,es,ar');
if (provider === 'deepl' && !authKey && !args.listTables && !coverage && !copySafeOnly) exitWithHelp(`缺少 DeepL API Key。请设置 ${authKeyCandidates.join(' 或 ')}，或先用 --coverage 只检查缺失译文。`);
if (provider === 'deeplx' && !deeplxEndpoint && !args.listTables && !coverage && !copySafeOnly) exitWithHelp(`缺少 DeepLX 接口地址。请设置 ${deeplxEndpointName}=https://...，或用 --deeplx-endpoint=https://...。`);

const tables = discoverLocaleTables().filter((table) => inScopes(table.name, scopes));
if (args.listTables) {
  console.log(tables.map((table) => `${table.name}: ${table.columns.join(', ')}`).join('\n'));
  process.exit(0);
}

let translator = null;
let targetCodeByLocale = Object.fromEntries(targetLocales.map((locale) => [locale, locale.toUpperCase()]));
if (!coverage && !copySafeOnly) {
  if (provider === 'deepl') {
    const { Translator } = await import('deepl-node');
    translator = new Translator(authKey);
    const supportedTargets = await translator.getTargetLanguages();
    targetCodeByLocale = resolveDeepLTargetCodes(targetLocales, supportedTargets);
  } else {
    translator = createDeepLXClient({ endpoint: deeplxEndpoint, authKey });
    targetCodeByLocale = resolveDeepLXTargetCodes(targetLocales);
  }
  const unsupported = targetLocales.filter((locale) => !targetCodeByLocale[locale]);
  if (unsupported.length) console.warn(`跳过 ${providerLabel(provider)} 当前不可用语言：${unsupported.join(', ')}`);
}

const plan = [];
const copyPlan = [];
for (const table of tables) {
  const sourceRows = rows(`SELECT ${selectList(['id', '_parent_id', ...table.columns])} FROM ${qi(table.name)} WHERE "_locale"=${ql(sourceLocale)}`);
  for (const targetLocale of targetLocales) {
    const targetCode = targetCodeByLocale[targetLocale];
    if (!targetCode) continue;
    const targetRows = new Map(rows(`SELECT ${selectList(['_parent_id', ...table.columns])} FROM ${qi(table.name)} WHERE "_locale"=${ql(targetLocale)}`).map((row) => [String(row._parent_id), row]));
    const sourceHashes = readSourceHashMap(table.name, targetLocale);
    for (const sourceRow of sourceRows) {
      const targetRow = targetRows.get(String(sourceRow._parent_id));
      for (const column of table.columns) {
        if (shouldSkipTranslationField(table.name, column)) continue;
        const value = cleanText(sourceRow[column]);
        if (!shouldTranslate(value)) continue;
        const targetValue = cleanText(targetRow?.[column]);
        const sourceHash = hashText(value);
        const trackedHash = sourceHashes.get(hashMapKey(sourceRow._parent_id, column));
        const staleByHash = includeStale && Boolean(targetValue) && Boolean(trackedHash) && trackedHash !== sourceHash;
        const untranslated = includeStale && targetValue === value && trackedHash !== sourceHash;
        const untrackedExisting = includeStale && Boolean(targetValue) && !trackedHash;
        if (!overwrite && targetValue && !staleByHash && !untranslated && !untrackedExisting) continue;
        const item = { table: table.name, parentId: sourceRow._parent_id, column, sourceText: value, sourceHash, targetLocale, targetCode };
        if (shouldPreserveSourceText(value, table.name, column)) copyPlan.push(item);
        else plan.push(item);
      }
    }
  }
}

console.log(`翻译计划：tables=${tables.length}, fields=${plan.length}, copySafeFields=${copyPlan.length}, targets=${Object.keys(targetCodeByLocale).join(',')}, apply=${apply}`);
if (coverage) {
  printCoverage([...plan, ...copyPlan], tables);
  const hasCredentials = provider === 'deeplx' ? Boolean(deeplxEndpoint) : Boolean(authKey);
  recordTranslationJob({
    status: plan.length + copyPlan.length === 0 ? 'completed' : hasCredentials ? 'coverage-needed' : 'needs-credentials',
    summary: coverageSummary([...plan, ...copyPlan], tables),
    errorMessage: plan.length + copyPlan.length === 0
      ? ''
      : hasCredentials
        ? `Professional translation is not complete. Run npm run payload:translate -- --provider=${provider} --include-stale --apply to write ${providerLabel(provider)} translations.`
        : `Professional translation is not complete. Configure ${provider === 'deeplx' ? deeplxEndpointName : authKeyName} and run npm run payload:translate -- --provider=${provider} --include-stale --apply.`,
  });
  if (args.syncReadiness) syncLanguageReadiness([...plan, ...copyPlan]);
  process.exit(0);
}
if (plan.length + copyPlan.length === 0) process.exit(0);
if (apply) {
  ensureTargetLocaleRows(tables);
  writeCopySafeFields(copyPlan);
  if (copySafeOnly) {
    recordTranslationJob({
      status: 'machine-translated',
      summary: `Copied ${copyPlan.length} source-safe technical fields without calling ${providerLabel(provider)}. source=${sourceLocale}; targets=${targetLocales.join(',')}; scopes=${scopes.join(',')}.`,
      errorMessage: '',
    });
    console.log(`已复制无需翻译字段：${copyPlan.length} 个。`);
    process.exit(0);
  }
}

const statements = [];
let translatedCount = 0;
for (const batch of chunks(plan, 40)) {
  const masked = batch.map((item) => maskProtected(item.sourceText));
  const translated = await translateGroupedBatch(masked, batch, translator);

  batch.forEach((item, index) => {
    const translatedText = masked[index].restore(translated[index] || item.sourceText);
    statements.push(upsertFieldSql(item.table, item.parentId, item.targetLocale, item.column, translatedText));
    statements.push(upsertSourceHashSql(item));
    translatedCount += 1;
  });

  if (statements.length >= 200 && apply) flush(statements.splice(0));
}

if (apply) {
  if (statements.length) flush(statements);
  markLanguageProgress(Object.keys(targetCodeByLocale), args.markReady ? 'ready' : 'in-progress');
  recordTranslationJob({
    status: 'machine-translated',
    summary: `${providerLabel(provider)} wrote ${translatedCount} localized fields and copied ${copyPlan.length} source-safe fields. source=${sourceLocale}; targets=${Object.keys(targetCodeByLocale).join(',')}; scopes=${scopes.join(',')}.`,
    errorMessage: '',
  });
  console.log(`已写入专业翻译：${translatedCount} 个字段。`);
} else {
  recordTranslationJob({
    status: 'queued',
    summary: `Preview found ${plan.length} fields ready for ${providerLabel(provider)}. source=${sourceLocale}; targets=${Object.keys(targetCodeByLocale).join(',')}; apply=false.`,
    errorMessage: `Dry run only. Add --apply after confirming ${providerLabel(provider)} configuration and scope.`,
  });
  console.log('仅预览，未写入数据库。加 --apply 后才会保存译文。');
  for (const item of plan.slice(0, 12)) console.log(`[preview] ${item.table}.${item.column} -> ${item.targetLocale}: ${item.sourceText.slice(0, 90)}`);
}

async function translateGroupedBatch(maskedItems, planItems, translatorInstance) {
  if (!translatorInstance) throw new Error(`缺少 ${providerLabel(provider)} 翻译客户端。`);
  if (provider === 'deeplx') return translateDeepLXBatch(maskedItems, planItems, translatorInstance);

  const output = new Array(maskedItems.length);
  const groups = new Map();
  planItems.forEach((item, index) => {
    const key = `${item.targetCode}`;
    if (!groups.has(key)) groups.set(key, []);
    groups.get(key).push({ item, index });
  });

  for (const [targetCode, group] of groups) {
    const texts = group.map(({ index }) => maskedItems[index].text);
    const result = await translatorInstance.translateText(texts, sourceLocale.toUpperCase(), targetCode, translateOptions(targetCode));
    const resultList = Array.isArray(result) ? result : [result];
    resultList.forEach((entry, groupIndex) => {
      output[group[groupIndex].index] = entry.text;
    });
  }
  return output;
}

async function translateDeepLXBatch(maskedItems, planItems, client) {
  const output = new Array(maskedItems.length);
  await mapWithConcurrency(maskedItems, deeplxConcurrency, async (maskedItem, index) => {
    output[index] = await translateDeepLXText(maskedItem.text, planItems[index], client);
  });
  return output;
}

async function translateDeepLXText(text, item, client) {
  const cacheKey = `${item.targetCode}\t${text}`;
  if (translationCache.has(cacheKey)) return translationCache.get(cacheKey);
  let lastError = null;
  for (let attempt = 0; attempt <= deeplxRetries; attempt += 1) {
    const response = await fetch(client.endpoint, {
      method: 'POST',
      headers: client.headers,
      body: JSON.stringify({
        text,
        source_lang: deeplxSourceLang,
        target_lang: item.targetCode,
      }),
    });
    const payload = await response.json().catch(() => ({}));
    if (response.ok) {
      const translatedText = extractDeepLXText(payload);
      translationCache.set(cacheKey, translatedText);
      return translatedText;
    }
    lastError = new Error(`DeepLX 请求失败：status=${response.status}; target=${item.targetLocale}; field=${item.table}.${item.column}; text=${text.slice(0, 160)}; body=${JSON.stringify(payload).slice(0, 500)}`);
    if (!isTransientDeepLXStatus(response.status) || attempt >= deeplxRetries) break;
    await sleep(deepLXRetryDelayMs(response.status, attempt));
  }
  throw lastError;
}

function isTransientDeepLXStatus(status) {
  return status === 408 || status === 409 || status === 418 || status === 425 || status === 429 || status >= 500;
}

function deepLXRetryDelayMs(status, attempt) {
  const multiplier = status === 418 || status === 429 ? 6 : 1;
  return deeplxRetryBaseMs * multiplier * (attempt + 1);
}

function extractDeepLXText(payload) {
  if (typeof payload?.data === 'string') return payload.data;
  if (typeof payload?.translation === 'string') return payload.translation;
  if (typeof payload?.result === 'string') return payload.result;
  if (typeof payload?.text === 'string') return payload.text;
  if (typeof payload?.data?.text === 'string') return payload.data.text;
  if (typeof payload?.data?.translation === 'string') return payload.data.translation;
  if (Array.isArray(payload?.translations) && typeof payload.translations[0]?.text === 'string') return payload.translations[0].text;
  if (Array.isArray(payload?.data?.translations) && typeof payload.data.translations[0]?.text === 'string') return payload.data.translations[0].text;
  throw new Error(`DeepLX 返回格式无法识别：${JSON.stringify(payload).slice(0, 500)}`);
}

function printCoverage(planItems, selectedTables) {
  const byLocale = new Map();
  const byTable = new Map();
  for (const item of planItems) {
    byLocale.set(item.targetLocale, (byLocale.get(item.targetLocale) || 0) + 1);
    const key = `${item.targetLocale}\t${item.table}`;
    byTable.set(key, (byTable.get(key) || 0) + 1);
  }
  const metricName = includeStale ? 'missingOrStaleFields' : 'missingFields';
  console.log(`翻译覆盖检查：tables=${selectedTables.length}, ${metricName}=${planItems.length}, source=${sourceLocale}, targets=${targetLocales.join(',')}`);
  for (const locale of targetLocales) console.log(`[locale] ${locale}: ${includeStale ? 'missingOrStale' : 'missing'}=${byLocale.get(locale) || 0}`);
  [...byTable.entries()]
    .sort((a, b) => b[1] - a[1])
    .slice(0, 20)
    .forEach(([key, count]) => {
      const [locale, table] = key.split('\t');
      console.log(`[table] ${locale}.${table}: ${includeStale ? 'missingOrStale' : 'missing'}=${count}`);
    });
}

function coverageSummary(planItems, selectedTables) {
  const byLocale = new Map();
  for (const item of planItems) byLocale.set(item.targetLocale, (byLocale.get(item.targetLocale) || 0) + 1);
  const localeSummary = targetLocales.map((locale) => `${locale}=${byLocale.get(locale) || 0}`).join(', ');
  const metricName = includeStale ? 'missing/stale' : 'missing';
  return `Coverage ${metricName}: fields=${planItems.length}; tables=${selectedTables.length}; source=${sourceLocale}; ${localeSummary}.`;
}

function recordTranslationJob({ status, summary, errorMessage }) {
  if (args.noJob) return;
  const title = args.jobTitle || `${providerLabel(provider)} Translation Coverage`;
  const safeSummary = String(summary || '').slice(0, 900);
  const safeError = String(errorMessage || '').slice(0, 900);

  try {
    execSql(`
WITH existing AS (
  SELECT tj.id
  FROM "translation_jobs" tj
  JOIN "translation_jobs_locales" tjl ON tjl."_parent_id"=tj.id AND tjl."_locale"='en'
  WHERE tjl."title"=${ql(title)}
  ORDER BY tj."updated_at" DESC
  LIMIT 1
),
created AS (
  INSERT INTO "translation_jobs" (
    "provider", "status", "source_locale", "overwrite_existing", "dry_run",
    "preserve_brand_terms", "last_run_at", "summary", "error_message", "created_at", "updated_at"
  )
  SELECT
    ${ql(provider)}::enum_translation_jobs_provider, ${ql(status)}::enum_translation_jobs_status, ${ql(sourceLocale)},
    ${overwrite ? 'TRUE' : 'FALSE'}, ${apply ? 'FALSE' : 'TRUE'},
    TRUE, now(), ${ql(safeSummary)}, ${ql(safeError)}, now(), now()
  WHERE NOT EXISTS (SELECT 1 FROM existing)
  RETURNING id
),
target AS (
  SELECT id FROM existing
  UNION ALL
  SELECT id FROM created
),
updated AS (
  UPDATE "translation_jobs"
  SET "provider"=${ql(provider)}::enum_translation_jobs_provider,
      "status"=${ql(status)}::enum_translation_jobs_status,
      "source_locale"=${ql(sourceLocale)},
      "overwrite_existing"=${overwrite ? 'TRUE' : 'FALSE'},
      "dry_run"=${apply ? 'FALSE' : 'TRUE'},
      "preserve_brand_terms"=TRUE,
      "last_run_at"=now(),
      "summary"=${ql(safeSummary)},
      "error_message"=${ql(safeError)},
      "updated_at"=now()
  WHERE "id"=(SELECT id FROM target LIMIT 1)
  RETURNING id
)
INSERT INTO "translation_jobs_locales" ("_parent_id", "_locale", "title")
SELECT id, 'en', ${ql(title)} FROM target
ON CONFLICT ("_locale", "_parent_id") DO UPDATE SET "title"=EXCLUDED."title";

DELETE FROM "translation_jobs_target_locales"
WHERE "parent_id"=(SELECT tj.id FROM "translation_jobs" tj JOIN "translation_jobs_locales" tjl ON tjl."_parent_id"=tj.id AND tjl."_locale"='en' WHERE tjl."title"=${ql(title)} ORDER BY tj."updated_at" DESC LIMIT 1);
${targetLocales.map((locale, index) => `INSERT INTO "translation_jobs_target_locales" ("order", "parent_id", "value") SELECT ${index}, tj.id, ${ql(locale)} FROM "translation_jobs" tj JOIN "translation_jobs_locales" tjl ON tjl."_parent_id"=tj.id AND tjl."_locale"='en' WHERE tjl."title"=${ql(title)} ORDER BY tj."updated_at" DESC LIMIT 1;`).join('\n')}

DELETE FROM "translation_jobs_scopes"
WHERE "parent_id"=(SELECT tj.id FROM "translation_jobs" tj JOIN "translation_jobs_locales" tjl ON tjl."_parent_id"=tj.id AND tjl."_locale"='en' WHERE tjl."title"=${ql(title)} ORDER BY tj."updated_at" DESC LIMIT 1);
${scopes.map((scope, index) => `INSERT INTO "translation_jobs_scopes" ("order", "parent_id", "value") SELECT ${index}, tj.id, ${ql(scope)} FROM "translation_jobs" tj JOIN "translation_jobs_locales" tjl ON tjl."_parent_id"=tj.id AND tjl."_locale"='en' WHERE tjl."title"=${ql(title)} ORDER BY tj."updated_at" DESC LIMIT 1;`).join('\n')}

DELETE FROM "translation_jobs_texts"
WHERE "parent_id"=(SELECT tj.id FROM "translation_jobs" tj JOIN "translation_jobs_locales" tjl ON tjl."_parent_id"=tj.id AND tjl."_locale"='en' WHERE tjl."title"=${ql(title)} ORDER BY tj."updated_at" DESC LIMIT 1)
  AND "path" IN ('targetLocales', 'scopes');
${targetLocales.map((locale, index) => `INSERT INTO "translation_jobs_texts" ("order", "parent_id", "path", "text") SELECT ${index}, tj.id, 'targetLocales', ${ql(locale)} FROM "translation_jobs" tj JOIN "translation_jobs_locales" tjl ON tjl."_parent_id"=tj.id AND tjl."_locale"='en' WHERE tjl."title"=${ql(title)} ORDER BY tj."updated_at" DESC LIMIT 1;`).join('\n')}
${scopes.map((scope, index) => `INSERT INTO "translation_jobs_texts" ("order", "parent_id", "path", "text") SELECT ${index}, tj.id, 'scopes', ${ql(scope)} FROM "translation_jobs" tj JOIN "translation_jobs_locales" tjl ON tjl."_parent_id"=tj.id AND tjl."_locale"='en' WHERE tjl."title"=${ql(title)} ORDER BY tj."updated_at" DESC LIMIT 1;`).join('\n')}
`);
    console.log(`[translation-job] ${title}: ${status}`);
  } catch (error) {
    console.warn(`翻译任务记录更新失败：${error.message}`);
  }
}

function syncLanguageReadiness(planItems) {
  const missingByLocale = new Map(targetLocales.map((locale) => [locale, 0]));
  for (const item of planItems) {
    missingByLocale.set(item.targetLocale, (missingByLocale.get(item.targetLocale) || 0) + 1);
  }

  for (const locale of targetLocales) {
    const missing = missingByLocale.get(locale) || 0;
    if (missing === 0) {
      execSql(`UPDATE "site_settings_language_options" SET "translation_status"='ready', "completion_percent"=100, "enabled"=TRUE WHERE "locale"=${ql(locale)};`);
      console.log(`[readiness] ${locale}: ready, enabled=true`);
    } else {
      execSql(`UPDATE "site_settings_language_options" SET "translation_status"='in-progress', "completion_percent"=LEAST(COALESCE("completion_percent", 85), 85), "enabled"=FALSE WHERE "locale"=${ql(locale)};`);
      console.log(`[readiness] ${locale}: missingOrStale=${missing}, enabled=false`);
    }
  }
}

function ensureTargetLocaleRows(selectedTables) {
  const statements = [];
  for (const table of selectedTables) {
    const columns = table.columns.filter(Boolean);
    for (const targetLocale of targetLocales) {
      if (!targetCodeByLocale[targetLocale]) continue;
      statements.push(`
INSERT INTO ${qi(table.name)} ("_parent_id", "_locale"${columns.length ? `, ${columns.map(qi).join(', ')}` : ''})
SELECT "_parent_id", ${ql(targetLocale)}${columns.length ? `, ${columns.map(qi).join(', ')}` : ''}
FROM ${qi(table.name)}
WHERE "_locale"=${ql(sourceLocale)}
ON CONFLICT ("_locale", "_parent_id") DO NOTHING;`);
    }
  }
  for (const batch of chunks(statements, 40)) flush(batch);
}

function writeCopySafeFields(items) {
  if (!items.length) return;
  const statements = [];
  for (const item of items) {
    statements.push(upsertFieldSql(item.table, item.parentId, item.targetLocale, item.column, item.sourceText));
    statements.push(upsertSourceHashSql(item));
    if (statements.length >= 200) flush(statements.splice(0));
  }
  if (statements.length) flush(statements);
}

function translateOptions(targetCode) {
  const options = { tagHandling: 'html', ignoreTags: ['x'], preserveFormatting: true };
  if (['DE', 'ES', 'FR', 'IT', 'NL', 'PL', 'PT-BR', 'PT-PT', 'RU'].includes(targetCode)) {
    options.formality = configuredFormality;
  }
  if (args.glossaryId) options.glossary = args.glossaryId;
  return options;
}

function readTranslationSettings() {
  try {
    const settings = rows(`
      SELECT
        ts.enabled,
        ts.provider,
        ts.auth_key_env_name,
        ts.deeplx_api_url_env_name,
        ts.deeplx_auth_key_env_name,
        ts.source_locale,
        ts.formality,
        COALESCE((
          SELECT array_agg(tst.value ORDER BY tst."order")
          FROM translation_settings_target_locales tst
          WHERE tst.parent_id = ts.id AND NULLIF(tst.value, '') IS NOT NULL
        ), ARRAY[]::varchar[]) AS target_locales,
        COALESCE((
          SELECT array_agg(tsp.term ORDER BY tsp._order)
          FROM translation_settings_protected_terms tsp
          WHERE tsp._parent_id = ts.id AND NULLIF(tsp.term, '') IS NOT NULL
        ), ARRAY[]::varchar[]) AS protected_terms
      FROM translation_settings ts
      ORDER BY ts.created_at DESC NULLS LAST, ts.id DESC
      LIMIT 1
    `)[0];

    return {
      enabled: settings?.enabled !== false,
      provider: settings?.provider || 'deepl',
      authKeyEnvName: settings?.auth_key_env_name || '',
      deeplxApiUrlEnvName: settings?.deeplx_api_url_env_name || '',
      deeplxAuthKeyEnvName: settings?.deeplx_auth_key_env_name || '',
      sourceLocale: settings?.source_locale || 'en',
      targetLocales: Array.isArray(settings?.target_locales) ? settings.target_locales : [],
      formality: settings?.formality || 'default',
      protectedTerms: Array.isArray(settings?.protected_terms) ? settings.protected_terms : [],
    };
  } catch {
    return {
      enabled: true,
      provider: 'deepl',
      authKeyEnvName: '',
      deeplxApiUrlEnvName: '',
      deeplxAuthKeyEnvName: '',
      sourceLocale: 'en',
      targetLocales: [],
      formality: 'default',
      protectedTerms: [],
    };
  }
}

function discoverLocaleTables() {
  const sqlText = `
    SELECT table_name, json_agg(column_name ORDER BY ordinal_position) AS columns
    FROM information_schema.columns
    WHERE table_schema='public'
      AND table_name LIKE '%\\_locales'
      AND column_name NOT IN ('id', '_locale', '_parent_id')
      AND data_type IN ('character varying', 'text')
      AND table_name IN (
        SELECT table_name FROM information_schema.columns WHERE table_schema='public' AND column_name='_locale'
      )
      AND table_name IN (
        SELECT table_name FROM information_schema.columns WHERE table_schema='public' AND column_name='_parent_id'
      )
    GROUP BY table_name
    ORDER BY table_name`;
  return rows(sqlText).map((row) => ({ name: row.table_name, columns: row.columns }));
}

function inScopes(tableName, selectedScopes) {
  const prefixes = selectedScopes.flatMap((scope) => scopePrefix(scope));
  return prefixes.some((prefix) => tableName === `${prefix}_locales` || tableName.startsWith(`${prefix}_`));
}

function scopePrefix(scope) {
  const known = {
    site_settings: ['site_settings'],
    home_settings: ['home_settings'],
    product_listing_ui: ['product_listing_ui', 'cap_ranges', 'boom_dict', 'app_dict', 'status_dict'],
    product_detail_ui: ['product_detail_ui'],
    page_text_settings: ['page_text_settings'],
    product_categories: ['product_categories'],
    products: ['products'],
    articles: ['articles'],
    cases: ['cases'],
    media: ['media'],
  };
  return known[scope] || [scope];
}

function resolveDeepLTargetCodes(locales, supported) {
  const aliases = { zh: ['ZH-HANS', 'ZH'], pt: ['PT-BR', 'PT-PT'], en: ['EN-US', 'EN-GB'], ko: ['KO'], id: ['ID'], vi: ['VI'], th: ['TH'], hi: ['HI'], ar: ['AR'], es: ['ES'], fr: ['FR'], de: ['DE'], tr: ['TR'], ru: ['RU'] };
  const supportedCodes = new Set(supported.map((lang) => lang.code.toUpperCase()));
  return Object.fromEntries(locales.flatMap((locale) => {
    const candidates = aliases[locale] || [locale.toUpperCase()];
    const code = candidates.find((candidate) => supportedCodes.has(candidate));
    return code ? [[locale, code]] : [];
  }));
}

function resolveDeepLXTargetCodes(locales) {
  const aliases = { zh: 'ZH', pt: 'PT', en: 'EN', ko: 'KO', id: 'ID', vi: 'VI', th: 'TH', hi: 'HI', ar: 'AR', es: 'ES', fr: 'FR', de: 'DE', tr: 'TR', ru: 'RU' };
  return Object.fromEntries(locales.flatMap((locale) => aliases[locale] ? [[locale, aliases[locale]]] : []));
}

function createDeepLXClient({ endpoint, authKey }) {
  const normalizedEndpoint = normalizeDeepLXEndpoint(endpoint, authKey);
  const headers = {
    'Content-Type': 'application/json',
  };
  if (authKey) {
    headers.Authorization = `Bearer ${authKey}`;
    headers['X-API-Key'] = authKey;
  }

  return { endpoint: normalizedEndpoint, headers };
}

function normalizeDeepLXEndpoint(value, authKey = '') {
  const endpoint = String(value || '').trim().replace(/\/+$/, '');
  if (!endpoint) throw new Error('缺少 DeepLX 接口地址。');
  try {
    const url = new URL(endpoint);
    if (url.hostname.toLowerCase() === 'api.deeplx.org') {
      const parts = url.pathname.split('/').filter(Boolean);
      const key = String(authKey || '').trim();
      if (parts.length === 0 || (parts.length === 1 && parts[0].toLowerCase() === 'translate')) {
        if (!key) throw new Error('DeepLX 官方接口需要配置 DEEPLX_API_KEY。');
        url.pathname = `/${encodeURIComponent(key)}/translate`;
        return url.toString().replace(/\/$/, '');
      }
      if (parts.at(-1)?.toLowerCase() !== 'translate') {
        url.pathname = `/${parts.map(encodeURIComponent).join('/')}/translate`;
        return url.toString().replace(/\/$/, '');
      }
    }
  } catch (error) {
    if (error instanceof TypeError) {
      throw new Error(`DeepLX 接口地址格式无效：${endpoint}`);
    }
    throw error;
  }
  return endpoint.endsWith('/translate') ? endpoint : `${endpoint}/translate`;
}

function providerLabel(value) {
  return value === 'deeplx' ? 'DeepLX' : 'DeepL';
}

function upsertFieldSql(table, parentId, locale, column, value) {
  return `UPDATE ${qi(table)} SET ${qi(column)}=${ql(value)} WHERE "_parent_id"=${ql(parentId)} AND "_locale"=${ql(locale)};`;
}

function upsertSourceHashSql(item) {
  return `INSERT INTO "translation_source_hashes" ("table_name", "parent_id", "column_name", "source_locale", "target_locale", "source_hash", "translated_at", "updated_at")
VALUES (${ql(item.table)}, ${ql(item.parentId)}, ${ql(item.column)}, ${ql(sourceLocale)}, ${ql(item.targetLocale)}, ${ql(item.sourceHash)}, now(), now())
ON CONFLICT ("table_name", "parent_id", "column_name", "source_locale", "target_locale")
DO UPDATE SET "source_hash"=EXCLUDED."source_hash", "translated_at"=now(), "updated_at"=now();`;
}

function readSourceHashMap(tableName, targetLocale) {
  try {
    return new Map(rows(`
      SELECT "parent_id", "column_name", "source_hash"
      FROM "translation_source_hashes"
      WHERE "table_name"=${ql(tableName)}
        AND "source_locale"=${ql(sourceLocale)}
        AND "target_locale"=${ql(targetLocale)}
    `).map((row) => [hashMapKey(row.parent_id, row.column_name), row.source_hash]));
  } catch {
    return new Map();
  }
}

function hashMapKey(parentId, column) {
  return `${parentId}\t${column}`;
}

function hashText(value) {
  return createHash('sha256').update(String(value)).digest('hex');
}

function markLanguageProgress(locales, status) {
  const percent = status === 'ready' ? 100 : 90;
  const enabled = status === 'ready' ? 'TRUE' : 'FALSE';
  const sqlText = `UPDATE "site_settings_language_options" SET "translation_status"=${ql(status)}, "completion_percent"=GREATEST(COALESCE("completion_percent", 0), ${percent}), "enabled"=${enabled} WHERE "locale" IN (${locales.map(ql).join(',')});`;
  try { execSql(sqlText); } catch (error) { console.warn(`语言状态更新失败：${error.message}`); }
}

function rows(sqlText) {
  const json = execPsql(`SELECT COALESCE(json_agg(row_to_json(q)), '[]'::json) FROM (${sqlText}) q;`, ['-t', '-A']);
  return JSON.parse(json || '[]');
}

function flush(sqlItems) {
  execSql(sqlItems.join('\n'));
}

function execSql(sqlText) {
  execPsql(sqlText, []);
}

function execPsql(sqlText, extraArgs) {
  const connection = new URL(process.env.DATABASE_URL);
  const env = { ...process.env, PGPASSWORD: decodeURIComponent(connection.password) };
  const args = [
    '-h', connection.hostname,
    '-p', connection.port || '5432',
    '-U', decodeURIComponent(connection.username),
    '-d', connection.pathname.slice(1),
    '-v', 'ON_ERROR_STOP=1',
    ...extraArgs,
    '-c', sqlText,
  ];
  return execFileSync('psql', args, { env, encoding: 'utf8', maxBuffer: 64 * 1024 * 1024 }).trim();
}

function maskProtected(text) {
  const replacements = [];
  let output = String(text);
  const termPatterns = protectedTerms
    .filter(Boolean)
    .map((term) => new RegExp(escapeRegExp(term), 'g'));
  const patterns = [/\{[^}]+\}/g, ...termPatterns, /\bSQ\d+(?:\.\d+)?[A-Z0-9]*\b/g, /\b[A-Z]{1,4}\d{2,}[A-Z0-9-]*\b/g];
  patterns.forEach((pattern) => {
    output = output.replace(pattern, (match) => {
      const token = `[[BOBLIFT_KEEP_${replacements.length}]]`;
      replacements.push(match);
      return token;
    });
  });
  return {
    text: output,
    restore(value) {
      return String(value)
        .replace(/\[\[\s*BOBLIFT_KEEP_(\d+)\s*\]\]/g, (_, index) => replacements[Number(index)] ?? '')
        .replace(/<x>\s*(\d+)\s*<\/x>/g, (_, index) => replacements[Number(index)] ?? '');
    },
  };
}

function escapeRegExp(value) {
  return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function shouldTranslate(value) {
  if (!value || value.length > 1600) return false;
  if (/^https?:\/\//i.test(value) || /^\/[-/\w.?#=&%]+$/.test(value)) return false;
  if (/^[\d\s.,:+\-°%/()]+$/.test(value)) return false;
  return /[A-Za-z\u4e00-\u9fff\u0600-\u06FF]/.test(value);
}

function shouldPreserveSourceText(value, tableName, columnName) {
  const text = cleanText(value);
  if (!text) return false;
  if (/^\{[^}]+\}\s*\|\s*BOB-LIFT$/i.test(text)) return true;
  if (/^(?:BOB-LIFT|WhatsApp)$/i.test(text)) return true;
  if (tableName === 'products_specs_locales' && columnName === 'value') return isTechnicalSpecValue(text);
  return false;
}

function isTechnicalSpecValue(value) {
  const text = value.trim();
  if (!/\d/.test(text)) return false;
  if (/^[A-Z]{1,8}\d[A-Z0-9._-]*$/.test(text)) return true;
  const words = text.match(/[A-Za-z]+/g) || [];
  if (!words.length) return true;
  const allowed = new Set(['c', 'kg', 'kgs', 'kw', 'kn', 'l', 'm', 'min', 'mm', 'mpa', 'r', 'rpm', 't', 'ton', 'tons']);
  return words.every((word) => allowed.has(word.toLowerCase()));
}

function shouldSkipTranslationField(tableName, columnName) {
  return tableName === 'site_settings_language_options_locales' && columnName === 'label';
}

function cleanText(value) {
  return typeof value === 'string' ? value.trim() : '';
}

function chunks(items, size) {
  const output = [];
  for (let index = 0; index < items.length; index += size) output.push(items.slice(index, index + size));
  return output;
}

async function mapWithConcurrency(items, limit, worker) {
  let nextIndex = 0;
  const workers = Array.from({ length: Math.min(limit, items.length) }, async () => {
    while (nextIndex < items.length) {
      const index = nextIndex;
      nextIndex += 1;
      await worker(items[index], index);
    }
  });
  await Promise.all(workers);
}

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function csv(value) {
  return String(value || '').split(',').map((item) => item.trim()).filter(Boolean);
}

function parseArgs(argv) {
  return Object.fromEntries(argv.map((arg) => {
    if (arg === '--apply') return ['apply', true];
    if (arg === '--overwrite') return ['overwrite', true];
    if (arg === '--list-tables') return ['listTables', true];
    if (arg === '--mark-ready') return ['markReady', true];
    const match = arg.match(/^--([^=]+)=(.*)$/);
    return match ? [toCamel(match[1]), match[2]] : [toCamel(arg.replace(/^--/, '')), true];
  }));
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

function selectList(columns) {
  return columns.map(qi).join(', ');
}

function qi(value) {
  return `"${String(value).replaceAll('"', '""')}"`;
}

function ql(value) {
  if (value === null || value === undefined) return 'NULL';
  return `'${String(value).replaceAll("'", "''")}'`;
}

function exitWithHelp(message) {
  console.error(message);
  console.error('DeepL 示例：DEEPL_AUTH_KEY=xxx npm run payload:translate -- --provider=deepl --include-stale --apply');
  console.error('DeepLX 示例：DEEPLX_API_URL=https://... npm run payload:translate -- --provider=deeplx --include-stale --apply');
  console.error('DeepL 兼容变量名：DEEPL_AUTH_KEY 或 DEEPL_API_KEY。DeepLX 兼容变量名：DEEPLX_API_URL / DEEPLX_ENDPOINT，Key 可用 DEEPLX_API_KEY。');
  console.error('覆盖检查：npm run payload:translate -- --coverage --targets=zh,es,ar');
  console.error('同步语言上线状态：npm run payload:translate -- --coverage --include-stale --targets=zh,es,ar --sync-readiness');
  process.exit(1);
}
