#!/usr/bin/env python3
"""Smaller WebP copies of theme pictures, for srcset on phones (see boblift_theme_image_variants() in inc/template-functions.php).

Each picture listed below gets <name>-<width>w.webp beside it for every width smaller than its own. Run it again after
replacing one of these pictures:

    python3 scripts/build-image-variants.py

Needs Pillow (pip install Pillow).
"""
from pathlib import Path

from PIL import Image

ROOT = Path(__file__).resolve().parent.parent / 'assets' / 'images'

# Picture globs (relative to assets/images) and the widths to make. Large photos get phone and tablet widths; the
# product-family icons, shown at 26 to 40 CSS pixels, get small ones.
SETS = [
    (['page-heroes/*.webp', 'product-banners/*.jpg', 'about/home-*.webp'], [160, 480, 768, 1080]),
    (['mega-menu-icons/*.png'], [64, 128]),
]
QUALITY = 78


def variants_of(path: Path) -> bool:
    return path.stem.rsplit('-', 1)[-1].rstrip('w').isdigit() and path.stem.endswith('w')


def main() -> None:
    made = 0
    for patterns, widths in SETS:
        for pattern in patterns:
            for source in sorted(ROOT.glob(pattern)):
                if variants_of(source):
                    continue
                with Image.open(source) as image:
                    image.load()
                    for width in widths:
                        if width >= image.width:
                            continue
                        target = source.with_name(f'{source.stem}-{width}w.webp')
                        height = round(image.height * width / image.width)
                        copy = image.convert('RGBA' if image.mode in ('RGBA', 'LA', 'P') else 'RGB').resize((width, height), Image.LANCZOS)
                        copy.save(target, 'WEBP', quality=QUALITY, method=6)
                        made += 1
                        print(f'{target.relative_to(ROOT)}  {target.stat().st_size // 1024} KB')
    print(f'{made} copies')


if __name__ == '__main__':
    main()
