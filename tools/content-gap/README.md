# content-gap importers

Brings the four pages the old site had and the new one lacked into WordPress: two Industry Insight articles
(`marine-crane-buying-guide`, `forklift-jib-crane-guide`) and two English products (7 Ton Forklift Fly Jib Crane CC165,
800 kg Pickup Crane Without Outriggers SQ08ZA3). English only; no ja/zh versions are created.

Source folder (default `tools/content-gap/source/`, or pass a path / set `CONTENT_GAP_SOURCE`):

- tracked: the two article `.html` files and two `.jsonld` files;
- not tracked (binary): `marine-crane-buying-guide-images.zip`, `forklift-jib-crane-images.zip`, and `forklift-charts/` (made by `python3 charts.py`, needs matplotlib), `products/` (made by `fetch-old-images.sh`).

Backups: set `BOBLIFT_BACKUP_DIR` to a private folder outside the web root (the importers refuse to run `overwrite` without it).

Run order on the target site (back up the database first):

```bash
tools/content-gap/fetch-old-images.sh
python3 tools/content-gap/charts.py
wp eval-file tools/content-gap/import-articles.php
wp eval-file tools/content-gap/import-products.php
```

Both importers check every source file before writing anything. Pages that already exist are left unchanged; pass `overwrite` as an extra argument to replace them, in which case each page's current fields and meta are saved to `tools/content-gap/backup-content-gap-<slug>-<time>.json` first.
