#!/usr/bin/env python3
"""Size the client's banner set (~/inbox/banners-*.zip) for the v2 homepage.

The originals are ~2.5 MB PNGs: 1983x793 wide banners (logo baked in on the
left) and 1254x1254 squares. This writes sRGB WebP, metadata stripped, into
assets/images/banners/:

    banner-NN-1920.webp / banner-NN-1200.webp   wide — desktop hero slider
    banner-NN-800.webp                           square — mobile hero slider
    tile-sell.webp / tile-atelier.webp           4:3 crops of the two
                                                 logo-free banners (5, 11)

    python3 scripts/build-banners.py [path/to/banners.zip]

The PNG sources are not committed (66 MB); re-run against the zip to rebuild.
"""

from __future__ import annotations

import glob
import io
import os
import pathlib
import re
import sys
import zipfile

from PIL import Image, ImageCms

ROOT = pathlib.Path(__file__).resolve().parent.parent
OUT = ROOT / "wp-content/themes/maison-opus/assets/images/banners"
QUALITY = 80

WIDE_WIDTHS = (1920, 1200)
SQUARE_WIDTH = 800
# Logo-free banners reused as homepage feature tiles: (banner, out, left edge
# of a full-height 4:3 crop as a fraction of the width — keeps the watch in).
TILES = ((5, "tile-sell", 0.40), (11, "tile-atelier", 0.30))
# Not in the slider: 1 = 2 plus a caption, 7 = near-copy of 6, 5 and 11 have
# no logo (used as tiles only).
SKIP_SLIDE = {1, 5, 7, 11}


def srgb(im: Image.Image) -> Image.Image:
    icc = im.info.get("icc_profile")
    if icc:
        src = ImageCms.ImageCmsProfile(io.BytesIO(icc))
        im = ImageCms.profileToProfile(im, src, ImageCms.createProfile("sRGB"))
    return im.convert("RGB")


def save(im: Image.Image, name: str, width: int) -> None:
    if im.width > width:
        im = im.resize((width, round(im.height * width / im.width)), Image.LANCZOS)
    path = OUT / f"{name}.webp"
    im.save(path, "WEBP", quality=QUALITY, method=6)  # no exif/icc passed = stripped
    print(f"  {path.name:28} {im.width}x{im.height}  {path.stat().st_size // 1024} KB")


def main() -> int:
    src = sys.argv[1] if len(sys.argv) > 1 else max(
        glob.glob(os.path.expanduser("~/inbox/banners-*.zip")), default=None, key=os.path.getmtime
    )
    if not src:
        print("No banners zip found in ~/inbox", file=sys.stderr)
        return 1

    OUT.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(src) as z:
        for entry in sorted(z.namelist()):
            m = re.search(r"opus(\d+)\.png$", entry)
            if not m:
                continue
            n = int(m.group(1))
            im = srgb(Image.open(io.BytesIO(z.read(entry))))
            if n in SKIP_SLIDE:
                pass
            elif im.width > im.height * 1.5:
                for w in WIDE_WIDTHS:
                    save(im, f"banner-{n:02d}-{w}", w)
            else:
                save(im, f"banner-{n:02d}-{SQUARE_WIDTH}", SQUARE_WIDTH)
            for tile_n, tile_name, left in TILES:
                if n == tile_n:
                    cw = round(im.height * 4 / 3)
                    x = min(round(im.width * left), im.width - cw)
                    save(im.crop((x, 0, x + cw, im.height)), tile_name, 1200)

    total = sum(f.stat().st_size for f in OUT.glob("*.webp"))
    print(f"{OUT.relative_to(ROOT)}  {total // 1024} KB total")
    return 0


if __name__ == "__main__":
    sys.exit(main())
