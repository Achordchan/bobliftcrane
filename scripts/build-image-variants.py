#!/usr/bin/env python3
"""Smaller WebP copies of theme pictures, for srcset on phones (see boblift_theme_image_variants() in inc/template-functions.php).

Each picture listed below gets <name>-<width>w.webp beside it for every width smaller than its own. Run it again after
replacing one of these pictures:

    python3 scripts/build-image-variants.py

Needs Pillow (pip install Pillow).
"""
from pathlib import Path

from PIL import Image, ImageOps

ROOT = Path(__file__).resolve().parent.parent / 'assets' / 'images'

# Picture globs (relative to assets/images) and the widths to make. Large photos get phone and tablet widths; the
# product-family icons, shown at 26 to 40 CSS pixels, get small ones.
SETS = [
    (['page-heroes/*.webp', 'product-banners/*.jpg', 'about/home-*.webp', 'about/about-aerial-*.webp', 'about/about-logo-1.png', 'about/about-logo-prototype-crane-ai.png'], [160, 480, 768, 1080, 1440]),
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
                # Copies from an earlier run go first: a picture replaced by a narrower one must not keep wider
                # copies of the old picture, which the theme would still offer in srcset.
                for stale in source.parent.glob(f'{source.stem}-*w.webp'):
                    if stale.stem[len(source.stem) + 1:-1].isdigit():
                        stale.unlink()
                with Image.open(source) as opened:
                    # Upright pixels: a browser shows the original turned by its EXIF orientation, the copies carry none.
                    image = ImageOps.exif_transpose(opened)
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
