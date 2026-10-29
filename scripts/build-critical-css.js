#!/usr/bin/env node
// Writes assets/css/critical.css: the rules of assets/css/theme.css that match anything shown on the pages listed in
// scripts/critical-css-pages.txt, at phone, tablet, desktop and wide-screen sizes. inc/setup.php prints these
// in <head> and loads theme.css after the first paint; the first line records the SHA-1 of the theme.css they were cut
// from, and a stale file is never used (the stylesheet then loads render-blocking, as it always did).
//
//   npm run start:wp                                  # local site at http://localhost:8080 (the default)
//   node scripts/build-critical-css.js [site-url]
//
// Rebuild after `npm run build:css` whenever theme.css changes. Pages load one at a time; do not point this at the live
// site with a short delay (WordPress.com rate-limits browser traffic from one address).
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const postcss = require('postcss');

const ROOT = path.join(__dirname, '..');
const THEME_CSS = path.join(ROOT, 'assets/css/theme.css');
const OUT = path.join(ROOT, 'assets/css/critical.css');
const PAGES = path.join(__dirname, 'critical-css-pages.txt');
const VIEWPORTS = [
  { width: 393, height: 852 },
  { width: 768, height: 1024 },
  { width: 1440, height: 900 },
  { width: 2000, height: 1043 },
];
/* Every style rule of theme.css in source order (rules inside @keyframes are not selectors). */
function styleRules(root) {
  const rules = [];
  root.walkRules((rule) => {
    if (rule.parent && rule.parent.type === 'atrule' && /keyframes$/i.test(rule.parent.name)) return;
    rules.push(rule);
  });
  return rules;
}

/*
 * Runs in the page: the indexes of the rules whose selector matches an element the page shows, anywhere on it (a
 * picture far down the page is placed by its section's rules, and a visitor may arrive scrolled or at an #anchor), or a
 * hidden one (display: none) inside a shown parent, as the rule that hides it must be there too. What is inside a
 * hidden element is left out: menus and dialogs open only through theme.js, which waits for the full stylesheet. Pseudo-elements and state pseudo-classes (:hover, ::before …)
 * are dropped before matching, so a rule for any state of such an element is kept; one the browser cannot parse is kept.
 */
function collectInPage({ selectors }) {
  const structural = /^(not|is|where|has|first-child|last-child|only-child|first-of-type|last-of-type|only-of-type|nth-child|nth-last-child|nth-of-type|nth-last-of-type|empty|root|lang|dir)$/;
  const simplify = (selector) => {
    // A colon after a backslash belongs to a class name (Tailwind's .lg\:flex); it is not a pseudo-class.
    let out = selector.replace(/(?<!\\)::?[a-zA-Z-]+(\((?:[^()]|\([^()]*\))*\))?/g, (match) => {
      if (match.startsWith('::')) return '';
      const name = match.slice(1).replace(/\(.*$/, '');
      return structural.test(name) ? match : '';
    });
    out = out.replace(/(^|[\s>+~,(])(?=$|[\s>+~,)])/g, '$1*').trim();
    return out || '*';
  };
  const rendered = (element) => element.getClientRects().length > 0 || getComputedStyle(element).display === 'contents';
  const shown = new Set();
  const walk = (element) => {
    for (const child of element.children) {
      if (rendered(child)) {
        shown.add(child);
        walk(child);
      } else if (rendered(element)) {
        shown.add(child);
      }
    }
  };
  shown.add(document.documentElement);
  if (document.body) shown.add(document.body);
  walk(document.documentElement);
  const kept = [];
  selectors.forEach((list, index) => {
    for (const selector of list) {
      let elements;
      try {
        elements = document.querySelectorAll(simplify(selector));
      } catch (error) {
        kept.push(index);
        return;
      }
      for (const element of elements) {
        if (shown.has(element)) {
          kept.push(index);
          return;
        }
      }
    }
  });
  return kept;
}

/* theme.css with only the kept rules (and every @font-face, @keyframes and @property), plus the SHA-1 header line. */
function buildCritical(css, kept) {
  const root = postcss.parse(css);
  styleRules(root).forEach((rule, index) => {
    if (!kept.has(index)) rule.remove();
  });
  // Drop at-rules left empty (an @media whose rules all went); keep the ones that carry no rules of their own.
  let removed = true;
  while (removed) {
    removed = false;
    root.walkAtRules((atRule) => {
      if (atRule.nodes && atRule.nodes.length === 0) {
        atRule.remove();
        removed = true;
      }
    });
  }
  const body = root.toString().replace(/\n/g, '');
  if (/<\/style/i.test(body)) throw new Error('theme.css contains "</style"; it cannot be printed in the page.');
  const hash = crypto.createHash('sha1').update(fs.readFileSync(THEME_CSS)).digest('hex');
  return `/*! theme.css sha1:${hash} */\n${body}\n`;
}

async function main() {
  const site = (process.argv[2] || 'http://localhost:8080').replace(/\/$/, '');
  const css = fs.readFileSync(THEME_CSS, 'utf8');
  const selectors = styleRules(postcss.parse(css)).map((rule) => rule.selectors);
  const pages = fs.readFileSync(PAGES, 'utf8').split('\n').map((line) => line.trim()).filter((line) => line && !line.startsWith('#'));
  const { chromium } = require('playwright');
  const browser = await chromium.launch();
  const kept = new Set();
  for (const page of pages) {
    for (const viewport of VIEWPORTS) {
      const context = await browser.newContext({ viewport, reducedMotion: 'reduce' });
      const tab = await context.newPage();
      try {
        await tab.goto(site + page, { waitUntil: 'load', timeout: 120000 });
        await tab.waitForTimeout(1500);
        (await tab.evaluate(collectInPage, { selectors })).forEach((index) => kept.add(index));
        process.stdout.write('.');
      } catch (error) {
        console.error(`\n${page} at ${viewport.width}px: ${error.message}`);
        process.exitCode = 1;
      }
      await context.close();
      await new Promise((resolve) => setTimeout(resolve, 1600));
    }
  }
  await browser.close();
  if (process.exitCode) {
    console.error('\nNot written: every page must load.');
    return;
  }
  const output = buildCritical(css, kept);
  fs.writeFileSync(OUT, output);
  console.log(`\n${path.relative(ROOT, OUT)}: ${kept.size} of ${selectors.length} rules, ${Buffer.byteLength(output)} bytes`);
}

module.exports = { buildCritical, collectInPage, styleRules };
if (require.main === module) main();
