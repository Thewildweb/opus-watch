#!/usr/bin/env python3
"""Bake the contact-page map into a static image.

The contact page used to embed a Google Maps iframe, which set third-party
cookies before a visitor could consent to anything and added a third-party
request to the critical path. This renders the same location once, from
OpenStreetMap tiles, and ships the result as a theme asset — so the live page
makes no external request at all.

Tiles are toned to the heritage palette (paper/ink) instead of OSM's default
colours, so the map reads as part of the page rather than a pasted-in widget.

    python3 scripts/build-map-image.py

Re-run only when the address changes. Tiles are cached in .tile-cache/ so a
re-run does not hit the OSM servers again.

OpenStreetMap data is ODbL: the "© OpenStreetMap-bijdragers" credit rendered
next to the map on the contact page is required, not decorative.
"""

from __future__ import annotations

import math
import pathlib
import sys
import time

import requests
from PIL import Image, ImageDraw, ImageEnhance

# De Entree 201, 1101 HG Amsterdam — via Nominatim.
LAT, LON = 52.3120932, 4.9407954
ZOOM = 17

# Two crops: the contact page is 21/9 on desktop and 4/3 below 880px.
CROPS = {
    "map-contact-wide.webp": (1680, 720),
    "map-contact-narrow.webp": (1000, 750),
}

# Heritage tokens (assets/css/styles/heritage.css).
PAPER = (244, 236, 224)
INK_SOFT = (74, 53, 34)
GILT = (156, 117, 54)

TILE = 256
UA = "opus-watches-site-build/1.0 (+https://opus.watches; erik@datadepartment.io)"

ROOT = pathlib.Path(__file__).resolve().parent.parent
OUT = ROOT / "wp-content/themes/maison-opus/assets/images"
CACHE = pathlib.Path(__file__).resolve().parent / ".tile-cache"


def deg2num(lat: float, lon: float, zoom: int) -> tuple[float, float]:
    """Lat/lon to fractional slippy-map tile coordinates."""
    rad = math.radians(lat)
    n = 2.0**zoom
    x = (lon + 180.0) / 360.0 * n
    y = (1.0 - math.asinh(math.tan(rad)) / math.pi) / 2.0 * n
    return x, y


def fetch_tile(z: int, x: int, y: int) -> Image.Image:
    CACHE.mkdir(exist_ok=True)
    path = CACHE / f"{z}_{x}_{y}.png"
    if not path.exists():
        url = f"https://tile.openstreetmap.org/{z}/{x}/{y}.png"
        res = requests.get(url, headers={"User-Agent": UA}, timeout=30)
        res.raise_for_status()
        path.write_bytes(res.content)
        time.sleep(0.12)  # be a good citizen on a shared tile server
    return Image.open(path).convert("RGB")


def duotone(img: Image.Image, dark: tuple, light: tuple) -> Image.Image:
    """Map luminance onto a dark→light ramp, so the map takes the page's palette."""
    grey = ImageEnhance.Contrast(img.convert("L")).enhance(1.12)
    ramp = []
    for channel in range(3):
        ramp += [
            round(dark[channel] + (light[channel] - dark[channel]) * (i / 255))
            for i in range(256)
        ]
    return grey.convert("RGB").point(ramp)


def draw_marker(img: Image.Image, cx: int, cy: int) -> None:
    """A gilt pin: soft halo, filled drop, paper-coloured centre."""
    d = ImageDraw.Draw(img, "RGBA")
    d.ellipse((cx - 30, cy - 30, cx + 30, cy + 30), fill=GILT + (26,))
    # Teardrop: circle plus a triangle pointing at the exact coordinate.
    top = cy - 30
    d.ellipse((cx - 13, top - 13, cx + 13, top + 13), fill=GILT, outline=PAPER, width=2)
    d.polygon([(cx - 8, top + 9), (cx + 8, top + 9), (cx, cy)], fill=GILT)
    d.ellipse((cx - 5, top - 5, cx + 5, top + 5), fill=PAPER)


def main() -> int:
    width = max(w for w, _ in CROPS.values())
    height = max(h for _, h in CROPS.values())
    # Enough tiles to cover the largest crop in both directions, plus a margin.
    canvas_w, canvas_h = width + 2 * TILE, height + 2 * TILE

    fx, fy = deg2num(LAT, LON, ZOOM)
    # Pixel position of the centre in the global pixel plane at this zoom.
    px, py = fx * TILE, fy * TILE
    left, top = px - canvas_w / 2, py - canvas_h / 2

    x0, y0 = math.floor(left / TILE), math.floor(top / TILE)
    x1 = math.floor((left + canvas_w) / TILE)
    y1 = math.floor((top + canvas_h) / TILE)

    canvas = Image.new("RGB", (canvas_w, canvas_h), PAPER)
    total = (x1 - x0 + 1) * (y1 - y0 + 1)
    print(f"Fetching {total} tiles at zoom {ZOOM} …")
    for tx in range(x0, x1 + 1):
        for ty in range(y0, y1 + 1):
            tile = fetch_tile(ZOOM, tx, ty)
            canvas.paste(tile, (int(tx * TILE - left), int(ty * TILE - top)))

    canvas = duotone(canvas, INK_SOFT, PAPER)
    draw_marker(canvas, canvas_w // 2, canvas_h // 2)

    OUT.mkdir(parents=True, exist_ok=True)
    for name, (w, h) in CROPS.items():
        box = (
            (canvas_w - w) // 2,
            (canvas_h - h) // 2,
            (canvas_w - w) // 2 + w,
            (canvas_h - h) // 2 + h,
        )
        path = OUT / name
        canvas.crop(box).save(path, "WEBP", quality=80, method=6)
        print(f"  {name}  {w}×{h}  {path.stat().st_size // 1024} KB")

    return 0


if __name__ == "__main__":
    sys.exit(main())
