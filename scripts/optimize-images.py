#!/usr/bin/env python3
"""Convert the theme's photographic assets to sized WebP.

The originals are full-resolution JPEGs — a 2200px hero and two 2000px tiles
delivered to a phone that shows them at a few hundred pixels. This writes a
WebP next to each one at a width the layout actually uses.

    python3 scripts/optimize-images.py

The JPEGs stay in the repo as the source; templates reference the .webp.
Re-runnable: it always regenerates from the JPEG, so re-run after replacing
a source image.
"""

from __future__ import annotations

import pathlib
import sys

from PIL import Image

ROOT = pathlib.Path(__file__).resolve().parent.parent
IMAGES = ROOT / "wp-content/themes/maison-opus/assets/images"

# Widest the layout ever renders each image, allowing for a 2x display on the
# smaller ones. Anything above this is bytes nobody sees.
MAX_WIDTH = {
    "hero-heritage.jpg": 1920,   # full-bleed homepage hero, and the LCP
    "tile-movement.jpg": 1200,   # homepage tile, portrait
    "tile-buy.jpg": 1200,        # homepage tile
    "watch-speedmaster.jpg": 1400,
    "hero-rolex.jpg": 800,       # already small; re-encode only
    "heroes/omega.jpg": 1600,    # brand archive header band
    "heroes/rolex.jpg": 800,
}
DEFAULT_MAX = 1600
QUALITY = 82


def main() -> int:
    sources = sorted(IMAGES.glob("*.jpg")) + sorted(IMAGES.glob("heroes/*.jpg"))
    if not sources:
        print("No JPEG sources found.", file=sys.stderr)
        return 1

    before = after = 0
    for src in sources:
        rel = str(src.relative_to(IMAGES))
        img = Image.open(src).convert("RGB")
        limit = MAX_WIDTH.get(rel, DEFAULT_MAX)

        if img.width > limit:
            height = round(img.height * limit / img.width)
            img = img.resize((limit, height), Image.LANCZOS)

        dst = src.with_suffix(".webp")
        img.save(dst, "WEBP", quality=QUALITY, method=6)

        src_kb = src.stat().st_size // 1024
        dst_kb = dst.stat().st_size // 1024
        before += src_kb
        after += dst_kb
        print(f"  {rel:26s} {src_kb:5d} KB -> {dst_kb:4d} KB  ({img.width}px)")

    saved = before - after
    print(f"\n  {'total':26s} {before:5d} KB -> {after:4d} KB  ({saved} KB saved)")
    return 0


if __name__ == "__main__":
    sys.exit(main())
