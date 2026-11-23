#!/usr/bin/env bash
# After a deploy, checks from outside the host that the IndexNow key file is really served over HTTPS (a web server or
# CDN rule for .txt paths would stop it before WordPress): ./scripts/check-indexnow-key.sh <key> [https://bobliftcrane.com]
# The key is the boblift_indexnow_key option (wp-admin/options.php). Exits 0 only on HTTP 200 with the exact key as body.
set -euo pipefail
key="${1:?usage: $0 <key> [home URL]}"
home="${2:-https://bobliftcrane.com}"
url="${home%/}/${key}.txt"
body_file="$(mktemp)"
trap 'rm -f "$body_file"' EXIT
code="$(curl -sS -o "$body_file" -w '%{http_code}' -H 'Cache-Control: no-cache' "$url?nocache=$(date +%s)")"
if [ "$code" = 200 ] && [ "$(cat "$body_file")" = "$key" ]; then
    echo "ok   $url answers 200 with the exact key"
    exit 0
fi
echo "FAIL $url answered HTTP $code with $(wc -c < "$body_file") bytes (expected 200 and the key)" >&2
exit 1
