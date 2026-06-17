# Staged (not loaded)

Files here are **parked** — WordPress only auto-loads `.php` files at the top
level of `mu-plugins/`, not in subdirectories, so nothing here runs.

## `opus-catalogue.php` — the "Database" sprint

The watch catalogue (the `watch_model` CPT, `brand`/`watch_type` taxonomies,
spec meta + metabox, and the `wp_opus_parts` custom table). Built, lints clean,
and verified to register — but **deferred out of sprint 1**. Opus is launching
with Products + Services first; the Database ships in a later sprint.

To re-activate when that sprint starts: move the file back up to
`wp-content/mu-plugins/opus-catalogue.php`. On first admin load it will create
the parts table, seed the eight brands, and flush rewrites.

NB: in sprint 1, product brands use WooCommerce's **native `product_brand`**
taxonomy. When the catalogue returns, decide whether to unify on
`product_brand` (attach it to `watch_model`) rather than the separate `brand`
taxonomy this plugin registers.
