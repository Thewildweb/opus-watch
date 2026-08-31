#!/usr/bin/env python3
"""
Export watches from the WatchBase SQLite dump into normalized JSON for import
into the Opus `wp_opus_watches` table.

Reads:  ../watchbase_com/watchbase.db  (override with $WATCHBASE_DB)
Writes: wp-content/themes/maison-opus/data/watches.json  (mounted into the
        container so `wp opus import-watches` can read it)

Re-runnable: just produces a fresh snapshot. The importer upserts on `url`,
so running this again as the crawl grows and re-importing is safe.
"""
import json
import os
import re
import sqlite3
import sys

HERE = os.path.dirname(os.path.abspath(__file__))
DB = os.environ.get("WATCHBASE_DB", os.path.join(HERE, "..", "..", "watchbase_com", "watchbase.db"))
OUT = os.path.join(HERE, "..", "wp-content", "themes", "maison-opus", "data", "watches.json")


def first_float(s):
    if not s:
        return None
    m = re.search(r"(\d+(?:\.\d+)?)", s)
    return float(m.group(1)) if m else None


def first_year(s):
    if not s:
        return None
    m = re.search(r"(1[89]\d{2}|20\d{2})", s)
    return int(m.group(1)) if m else None


def dial_color(specs_raw):
    try:
        specs = json.loads(specs_raw) if specs_raw else {}
        return (specs.get("Dial") or {}).get("Color", "") or ""
    except (ValueError, AttributeError):
        return ""


def main():
    if not os.path.exists(DB):
        sys.exit(f"watchbase.db not found at {DB} (set $WATCHBASE_DB)")

    con = sqlite3.connect(f"file:{DB}?mode=ro", uri=True)
    con.row_factory = sqlite3.Row
    rows = con.execute(
        "SELECT url, brand, family, family_url, reference, name, movement, "
        "caliber_url, produced, limited, diameter, material, specs_json "
        "FROM watches"
    ).fetchall()

    out = []
    for r in rows:
        out.append({
            "url": r["url"],
            "brand": r["brand"] or "",
            "family": r["family"] or "",
            "family_url": r["family_url"] or "",
            "reference": r["reference"] or "",
            "name": r["name"] or "",
            "movement": r["movement"] or "",
            "caliber_url": r["caliber_url"],
            "produced": r["produced"] or "",
            "produced_year": first_year(r["produced"]),
            "limited": 1 if (r["limited"] or "").strip().lower() == "yes" else 0,
            "diameter_mm": first_float(r["diameter"]),
            "material": r["material"] or "",
            "dial_color": dial_color(r["specs_json"]),
            "specs_json": r["specs_json"] or "{}",
        })

    os.makedirs(os.path.dirname(OUT), exist_ok=True)
    with open(OUT, "w", encoding="utf-8") as f:
        json.dump(out, f, ensure_ascii=False)

    print(f"Exported {len(out)} watches -> {os.path.relpath(OUT, HERE)}")


if __name__ == "__main__":
    main()
