import { spawn } from 'node:child_process';
import { existsSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { setTimeout as wait } from 'node:timers/promises';
import { chromium } from 'playwright';

const port = Number(process.env.RESPONSIVE_TEST_PORT || 3100);
let baseUrl = process.env.RESPONSIVE_TARGET_URL || `http://localhost:${port}`;
const chromePath = process.env.PLAYWRIGHT_CHROME_PATH || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const nodePath = process.env.RESPONSIVE_NODE_PATH || (existsSync('/opt/homebrew/bin/node') ? '/opt/homebrew/bin/node' : process.execPath);
const appDir = fileURLToPath(new URL('..', import.meta.url));

const viewports = [
  { name: 'phone', width: 390, height: 1100 },
  { name: 'tablet', width: 820, height: 1180 },
  { name: 'desktop', width: 1440, height: 1200 },
];

const staticPaths = [
  '/',
  '/products',
  '/news',
  '/news/bob-lift-sq10za5-limited-time-discount-hot-selling',
  '/cases',
  '/cases/bob-lift-provides-reliable-support-for-water-operations',
  '/about',
  '/contact',
  '/quote',
];

let server;

async function isReady(url) {
  try {
    const res = await fetch(url, { cache: 'no-store' });
    return res.ok;
  } catch {
    return false;
  }
}

async function waitForServer(url) {
  for (let i = 0; i < 60; i += 1) {
    if (await isReady(url)) return;
    await wait(500);
  }
  throw new Error(`Server not ready: ${url}`);
}

async function startServer() {
  if (process.env.RESPONSIVE_TARGET_URL) return;
  const runningDevUrl = 'http://localhost:3000';
  if (await isReady(runningDevUrl)) {
    baseUrl = runningDevUrl;
    return;
  }
  server = spawn(nodePath, ['node_modules/next/dist/bin/next', 'dev', '-p', String(port)], {
    cwd: appDir,
    stdio: ['ignore', 'pipe', 'pipe'],
    env: { ...process.env, NEXT_TELEMETRY_DISABLED: '1' },
  });
  server.stdout.on('data', (chunk) => process.stdout.write(chunk));
  server.stderr.on('data', (chunk) => process.stderr.write(chunk));
  server.on('error', (error) => { throw error; });
  await waitForServer(baseUrl);
}

function joinUrl(base, path) {
  return `${base.replace(/\/$/, '')}${path}`;
}

async function inspect(page, viewport, path) {
  await page.setViewportSize({ width: viewport.width, height: viewport.height });
  await gotoStable(page, joinUrl(baseUrl, path));

  const result = await page.evaluate(() => {
    const box = (selector) => {
      const node = document.querySelector(selector);
      if (!node) return null;
      const rect = node.getBoundingClientRect();
      return { left: rect.left, right: rect.right, width: rect.width, top: rect.top, height: rect.height };
    };
    const stage = document.querySelector('.product-stage');
    const stageStyle = stage ? getComputedStyle(stage) : null;
    const visibleImages = [...document.images].filter((img) => img.getBoundingClientRect().height > 20).length;
    return {
      innerWidth: window.innerWidth,
      scrollWidth: document.documentElement.scrollWidth,
      header: box('header'),
      main: box('main'),
      footer: box('footer'),
      firstHeading: document.querySelector('h1, h2')?.textContent?.trim() || '',
      stageColumns: stageStyle?.gridTemplateColumns || '',
      visibleImages,
    };
  });

  const overflow = result.scrollWidth - result.innerWidth;
  if (overflow > 2) {
    throw new Error(`${viewport.name} ${path}: horizontal overflow ${overflow}px (${result.scrollWidth} > ${result.innerWidth})`);
  }
  for (const [name, rect] of Object.entries({ header: result.header, main: result.main, footer: result.footer })) {
    if (!rect) throw new Error(`${viewport.name} ${path}: missing ${name}`);
    if (rect.left < -2 || rect.right > result.innerWidth + 2) {
      throw new Error(`${viewport.name} ${path}: ${name} overflows viewport (${rect.left}, ${rect.right}, vw ${result.innerWidth})`);
    }
  }
  if (!result.firstHeading) throw new Error(`${viewport.name} ${path}: missing visible heading`);
  if (result.visibleImages < 1) throw new Error(`${viewport.name} ${path}: no visible image found`);
  if (viewport.width <= 820 && result.stageColumns.trim().split(/\s+/).length > 1) {
    throw new Error(`${viewport.name} ${path}: product stage must stack on small screens, got ${result.stageColumns}`);
  }
}

async function discoverProductDetailPath(page) {
  await page.setViewportSize({ width: 1440, height: 1200 });
  await gotoStable(page, joinUrl(baseUrl, '/products'));

  const href = await page
    .locator('a[href*=".html"], a[href^="/products/"]')
    .filter({ hasText: /View Details|Details|详情/i })
    .first()
    .getAttribute('href')
    .catch(() => null);

  if (!href) return null;
  const url = new URL(href, baseUrl);
  return `${url.pathname}${url.search}`;
}

async function gotoStable(page, url) {
  for (let attempt = 0; attempt < 3; attempt += 1) {
    try {
      await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 45000 });
      await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
      return;
    } catch (error) {
      const message = error instanceof Error ? error.message : String(error);
      if (!message.includes('net::ERR_ABORTED') || attempt === 2) throw error;
      await wait(600);
    }
  }
}

try {
  await startServer();
  const browser = await chromium.launch({ headless: true, executablePath: chromePath });
  const page = await browser.newPage();
  const productDetailPath = await discoverProductDetailPath(page);
  const paths = [
    ...staticPaths.slice(0, 2),
    ...(productDetailPath ? [productDetailPath] : []),
    ...staticPaths.slice(2),
  ];

  for (const viewport of viewports) {
    for (const path of paths) {
      await inspect(page, viewport, path);
      console.log(`responsive:${viewport.name}:${path}: pass`);
    }
  }
  await browser.close();
} finally {
  if (server) server.kill('SIGTERM');
}
