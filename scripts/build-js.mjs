// Minifies assets/js/theme.js into assets/js/theme.min.js. The first line records the SHA-1 of the source it was built
// from; inc/setup.php serves the minified file only while that matches theme.js, so a stale build is never loaded.
import { createHash } from 'node:crypto';
import { readFileSync, writeFileSync } from 'node:fs';
import { transform } from 'esbuild';

const source = 'assets/js/theme.js';
const target = 'assets/js/theme.min.js';
const code = readFileSync(source);
const hash = createHash('sha1').update(code).digest('hex');
const result = await transform(code.toString('utf8'), { minify: true, legalComments: 'none', charset: 'utf8' });

writeFileSync(target, `/*! theme.js sha1:${hash} */\n${result.code}`);
console.log(`${target}: ${code.length} -> ${Buffer.byteLength(result.code)} bytes (source sha1 ${hash.slice(0, 12)})`);
