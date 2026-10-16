#!/usr/bin/env python3
"""Check every legacy redirect against a running copy of the new site.

  python3 tools/legacy-redirects/check.py --base http://localhost:8080
  python3 tools/legacy-redirects/check.py --base https://203.0.113.10 --host www.bobliftcrane.com   # before DNS is switched

Expects each enabled rule to answer 301 with a Location whose path matches the rule's target, and follows the target on the new site once to make sure it returns 200.
Rules marked "expect_live" are fallbacks for pages imported by tools/content-gap: once imported, the page itself
is served, so the source path must answer 200 directly (a 301 means the import is missing).
"""
import argparse, http.client, json, ssl, sys
from pathlib import Path
from urllib.parse import urlsplit

ap = argparse.ArgumentParser()
ap.add_argument('--base', required=True)
ap.add_argument('--host', help='Host header to send (the production hostname)')
ap.add_argument('--rules', default=str(Path(__file__).resolve().parents[2] / 'inc' / 'legacy-redirects.json'))
args = ap.parse_args()
base = urlsplit(args.base)
ctx = ssl._create_unverified_context()

def get(path, host=None):
    cls = http.client.HTTPSConnection if base.scheme == 'https' else http.client.HTTPConnection
    conn = cls(base.hostname, base.port, timeout=30, **({'context': ctx} if base.scheme == 'https' else {}))
    conn.request('GET', path, headers={'Host': host or args.host or base.netloc, 'User-Agent': 'legacy-redirect-check'})
    r = conn.getresponse(); r.read()
    return r.status, r.getheader('Location') or ''

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
