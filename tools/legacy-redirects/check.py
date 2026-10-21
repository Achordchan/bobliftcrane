#!/usr/bin/env python3
"""Check every legacy redirect against a running copy of the new site.

  python3 tools/legacy-redirects/check.py --base http://localhost:8080
  python3 tools/legacy-redirects/check.py --base https://203.0.113.10 --host www.bobliftcrane.com   # before DNS is switched

Expects each enabled rule to answer 301 with a Location whose path matches the rule's target, and follows the target on the new site once to make sure it returns 200.
Rules marked "expect_live" are fallbacks for pages imported by tools/content-gap: once imported, the page itself
is served, so the source path must answer 200 directly (a 301 means the import is missing).

  python3 tools/legacy-redirects/check.py --base https://www.bobliftcrane.com --urls old-urls.txt --csv result.csv

With --urls, every old URL in the file (one per line, e.g. a GSC page export or the old sitemaps) is requested on the
new site instead, language subdomains as ?legacy_lang=xx the way the Cloudflare rule sends them, and followed to its
final page; anything that does not end on a 200 (or on another site, such as bobliftloader.com) fails.
"""
import argparse, csv, http.client, json, ssl, sys, time
from urllib.parse import urljoin
from pathlib import Path
from urllib.parse import urlsplit

ap = argparse.ArgumentParser()
ap.add_argument('--base', required=True)
ap.add_argument('--host', help='Host header to send (the production hostname)')
ap.add_argument('--rules', default=str(Path(__file__).resolve().parents[2] / 'inc' / 'legacy-redirects.json'))
ap.add_argument('--urls', help='File of old URLs to follow to their final page')
ap.add_argument('--csv', help='With --urls: write old URL, hops, final status and final URL here')
ap.add_argument('--sleep', type=float, default=0.3, help='Pause between requests (WordPress.com answers 429 when hit too fast)')
args = ap.parse_args()
base = urlsplit(args.base)
ctx = ssl._create_unverified_context()

def get(path, host=None):
    cls = http.client.HTTPSConnection if base.scheme == 'https' else http.client.HTTPConnection
    conn = cls(base.hostname, base.port, timeout=30, **({'context': ctx} if base.scheme == 'https' else {}))
    conn.request('GET', path, headers={'Host': host or args.host or base.netloc, 'User-Agent': 'legacy-redirect-check'})
    r = conn.getresponse(); r.read()
    return r.status, r.getheader('Location') or ''

def get_retry(path, host=None):
    for attempt in range(5):
        status, loc = get(path, host)
        if status not in (428, 429):
            return status, loc
        time.sleep(5 * (attempt + 1))
    return status, loc

if args.urls:
    cfg = json.load(open(args.rules))
    languages = set(cfg.get('language_hosts', []))
    site_host = args.host or base.netloc
    rows, bad = [], 0
    for line in open(args.urls, encoding='utf-8'):
        old = line.strip()
        if not old:
            continue
        parts = urlsplit(old)
        label = parts.hostname.split('.')[0] if parts.hostname else ''
        path = (parts.path or '/') + ('?' + parts.query if parts.query else '')
        if label in languages:
            path += ('&' if parts.query else '?') + 'legacy_lang=' + label
        hops, status, final = 0, None, path
        while True:
            time.sleep(args.sleep)
            status, loc = get_retry(final)
            if status in (301, 302, 307, 308) and loc and hops < 6:
                hops += 1
                target = urlsplit(urljoin('https://' + site_host + final, loc))
                if target.hostname not in (site_host, base.hostname, cfg.get('canonical_host')):
                    status, final = 'external', target.geturl()
                    break
                final = (target.path or '/') + ('?' + target.query if target.query else '')
                continue
            break
        ok = status in (200, 'external')
        bad += 0 if ok else 1
        rows.append([old, hops, status, final])
        if not ok:
            print(f'FAIL {old} -> {status} {final} ({hops} hops)')
    if args.csv:
        with open(args.csv, 'w', newline='', encoding='utf-8') as f:
            csv.writer(f).writerows([['old_url', 'hops', 'final_status', 'final_url']] + rows)
    print(f'{len(rows)} URLs checked, {bad} failed')
    sys.exit(1 if bad else 0)

cfg = json.load(open(args.rules))
bad = 0
for rule in cfg['rules']:
    if rule.get('enabled', True) is False:
        continue
    status, loc = get(rule['from'].replace(' ', '%20'))
    want = rule['to']
    if rule.get('expect_live'):
        if status != 200:
            bad += 1
            print(f"FAIL {rule['from']} -> {status} {loc} (want 200: imported page)")
        continue
    ok = status == 301 and (loc.endswith(want) if want.startswith('/') else loc.rstrip('/') == want.rstrip('/'))
    if ok and want.startswith('/'):
        final, _ = get(want)
        ok = final == 200
        status = f'301→{final}'
    if not ok:
        bad += 1
        print(f"FAIL {rule['from']} -> {status} {loc} (want {want})")
print(f"{len(cfg['rules'])} rules checked, {bad} failed")
sys.exit(1 if bad else 0)
