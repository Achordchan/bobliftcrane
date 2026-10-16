#!/usr/bin/env bash
# Downloads the old site's photos used by import-products.php into <source-dir>/products/.
#   tools/content-gap/fetch-old-images.sh [source-dir]
set -Eeuo pipefail
SOURCE="${1:-${CONTENT_GAP_SOURCE:-$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/source}}"
DEST="${SOURCE}/products"
BASE="https://iprorwxhqnjjli5q.ldycdn.com/cloud"
mkdir -p "${DEST}"
get() { curl -fsSL --max-time 60 -o "${DEST}/$1" "${BASE}/$2"; }
get fork-7ton-main-1.jpg loBpiKollnSRpjnlojknjq/7-ton-forklift-fly-jib-crane-800-800.jpg
get fork-7ton-main-2.jpg lqBpiKollnSRpjnlojlnjq/7-ton-forklift-fly-jib-crane-800-800.jpg
i=1; for f in liBpiKollnSRnlopmkomjm/feibeidiao_01 llBpiKollnSRnloprlnijq/feibeidiao_02 ljBpiKollnSRnloprljjjq/feibeidiao_03 loBpiKollnSRnloprlojjq/feibeidiao_04 lkBpiKollnSRnloprljkjq/feibeidiao_05 lrBpiKollnSRnloprlmkjp/feibeidiao_07 llBpiKollnSRnloprlinjq/feibeidiao_08 lqBpiKollnSRnloprllnjq/feibeidiao_09; do get "fork-7ton-detail-${i}.jpg" "${f}.jpg"; i=$((i+1)); done
i=1; for f in lpBpiKollnSRnlmqljmqjp/0 lkBpiKollnSRnlmqljkpjp/1 lnBpiKollnSRnlmqljnpjp/2 loBpiKollnSRnlmqljopjp/7 llBpiKollnSRnlmqljlpjp/8; do get "pickup-main-${i}.jpg" "${f}-800-800.jpg"; i=$((i+1)); done
i=1; for f in jiBpiKollnSRkkkipklqjq jjBpiKollnSRkkkipknqjq jkBpiKollnSRkkkipkoqjq jlBpiKollnSRkkkipkqqjq jnBpiKollnSRkkkipkpqjq jpBpiKollnSRkkkipkiqjo jrBpiKollnSRkkkipkkqjq; do get "pickup-detail-${i}.jpg" "${f}/xiangqing.jpg"; i=$((i+1)); done
get pickup-SQ08Z-drawing.jpg jmBpiKollnSRkkkipkjqjo/SQ08ZA3T.jpg
echo "Downloaded to ${DEST}"
