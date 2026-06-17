#!/usr/bin/env bash
# Import real watches from the partner site Amstel Watches into WooCommerce,
# replacing the placeholder demo watches. Idempotent & resumable (keyed by SKU).
#
#   ./scripts/import-amstel.sh [LIMIT]      # default: all
set -euo pipefail
cd "$(dirname "$0")/.."

LIMIT="${1:-999}"

echo "▸ Removing placeholder demo watches (OPUS-W-*)…"
docker compose run --rm wpcli eval '
foreach (array("OPUS-W-001","OPUS-W-002","OPUS-W-003","OPUS-W-004","OPUS-W-005","OPUS-W-006") as $sku) {
	$id = wc_get_product_id_by_sku($sku);
	if ($id) { wp_delete_post($id, true); }
}
echo "placeholders removed\n";
' 2>/dev/null | grep -v Container || true

echo "▸ Importing up to ${LIMIT} watches from Amstel Watches (with images)…"
docker compose run --rm -T wpcli eval-file - "$LIMIT" < scripts/import-amstel.php 2>&1 \
  | grep -vE "Container|Creating|Created|Running|Waiting|Healthy|Starting|Started"

echo "✓ Import finished."
