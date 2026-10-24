#!/usr/bin/env python3
"""Request every sitemap URL once after a deploy, so the WordPress.com edge cache stops serving pre-deploy pages.

The edge cache keeps a page for 5 minutes and then serves the old copy while it fetches a new one (x-ac: STALE).
A page nobody visits keeps its old copy indefinitely, so Google can be served a page from before the last deploy;
one request after the deploy is enough to replace it. Paced to stay under the host's rate limit (429).
"""
import re
import sys
import time
import urllib.error
import urllib.request

SITE = sys.argv[1] if len(sys.argv) > 1 else "https://bobliftcrane.com"
HEADERS = {"User-Agent": "BOB-LIFT deploy cache warmer"}


def fetch(url: str) -> tuple[int, bytes]:
    for attempt in range(4):
        try:
            with urllib.request.urlopen(urllib.request.Request(url, headers=HEADERS), timeout=60) as response:
                return response.status, response.read()
        except urllib.error.HTTPError as error:
            if error.code != 429:
                return error.code, b""
            time.sleep(20 * (attempt + 1))
        except OSError:
            time.sleep(5)
    return 0, b""


def locs(xml: bytes) -> list[str]:
    return re.findall(r"<loc>([^<]+)</loc>", xml.decode("utf-8", "replace"))


def main() -> int:
    status, index = fetch(f"{SITE}/sitemap.xml")
    if status != 200:
        print(f"sitemap index returned {status}")
        return 1
    urls: list[str] = []
    failed_sitemaps = 0
    for sitemap in locs(index):
        status, body = fetch(sitemap)
        if status == 200:
            urls.extend(locs(body))
        else:
            failed_sitemaps += 1
            print(f"{sitemap} returned {status}")
    failed = 0
    for url in urls:
        status, _ = fetch(url)
        if status != 200:
            failed += 1
            print(f"{url} returned {status}")
        time.sleep(0.6)
    print(f"warmed {len(urls) - failed} of {len(urls)} URLs, {failed_sitemaps} sitemaps unreadable")
    # Any skipped sitemap or page means some pages may still be stale, so the run fails and shows up in Actions.
    return 1 if failed or failed_sitemaps else 0


if __name__ == "__main__":
    sys.exit(main())
