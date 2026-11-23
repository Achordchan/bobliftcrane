#!/usr/bin/env bash
# After a deploy, checks from outside the host that the IndexNow key file is really served over HTTPS (a web server or
# CDN rule for .txt paths would stop it before WordPress): ./scripts/check-indexnow-key.sh <key> [https://bobliftcrane.com]
# The key is the boblift_indexnow_key option (wp-admin/options.php). Exits 0 only when the exact URL answers HTTP 200 with the key as its body, byte for byte.
set -euo pipefail
key="${1:?usage: $0 <key> [home URL]}"
home="${2:-https://bobliftcrane.com}"
url="${home%/}/${key}.txt"
body_file="$(mktemp)"
expected_file="$(mktemp)"
trap 'rm -f "$body_file" "$expected_file"' EXIT
printf '%s' "$key" > "$expected_file"
# The exact advertised URL, as IndexNow fetches it (no cache-busting query: a cached 404 there must fail the check).
code="$(curl -sS -o "$body_file" -w '%{http_code}' "$url")"
if [ "$code" = 200 ] && cmp -s "$body_file" "$expected_file"; then
    echo "ok   $url answers 200 with exactly the key"
    exit 0
fi
echo "FAIL $url answered HTTP $code with $(wc -c < "$body_file") bytes (expected 200 and exactly the ${#key}-byte key)" >&2
# Diagnosis only: does the origin serve it when the cache is bypassed?
fresh="$(curl -sS -o /dev/null -w '%{http_code}' "$url?nocache=$(date +%s)" || true)"
echo "     with a cache-busting query the origin answers HTTP $fresh" >&2
exit 1
