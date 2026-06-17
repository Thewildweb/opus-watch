#!/usr/bin/env bash
# Seed demo content for sprint 1 — sample watches + services (Dutch).
# Idempotent: skips any product whose SKU already exists. Safe to re-run.
#
# Categories keep stable slugs (watches/service) with Dutch display names,
# so theme links and the shop filter (which target the slug) keep working.
set -euo pipefail
cd "$(dirname "$0")/.."

wp() { docker compose run --rm wpcli "$@"; }

echo "▸ Ensuring product categories…"
existing_slugs=$(wp term list product_cat --field=slug 2>/dev/null | tr -d '\r' || true)
ensure_cat() { # NAME SLUG
  if echo "$existing_slugs" | grep -qx "$2"; then
    echo "  = $1 (exists)"
  else
    wp term create product_cat "$1" --slug="$2" >/dev/null && echo "  + $1"
  fi
}
ensure_cat "Horloges" "watches"
ensure_cat "Onderhoud" "service"

# create_product SKU NAME PRICE BRAND CAT_SLUG FEATURED SHORT_DESC
create_product() {
  local sku="$1" name="$2" price="$3" brand="$4" cat="$5" featured="$6" desc="$7"
  if wp wc product list --sku="$sku" --field=id --user=1 2>/dev/null | grep -q '[0-9]'; then
    echo "  = $name (exists)"; return
  fi
  local args=(--name="$name" --type=simple --sku="$sku" --featured="$featured"
              --short_description="$desc" --catalog_visibility=visible --user=1 --porcelain)
  if [ -n "$price" ]; then
    args+=(--regular_price="$price" --manage_stock=true --stock_quantity=1)
  fi
  local id
  id=$(wp wc product create "${args[@]}" 2>/dev/null | tr -d '\r' | grep -E '^[0-9]+$' | tail -1)
  # Category by SLUG (term_exists sanitises the arg to a slug, so a Dutch
  # display name like "Horloges" would NOT match slug "watches").
  wp post term set "$id" product_cat "$cat" >/dev/null 2>&1
  [ -n "$brand" ] && wp post term set "$id" product_brand "$brand" >/dev/null 2>&1
  echo "  + $name ($id)"
}

echo "▸ Horloges…"
create_product OPUS-W-001 "Rolex Submariner Date"          14800  "Rolex"           watches true  "Oystersteel · 41mm · Cal. 3235 · 2022, met doos en papieren."
create_product OPUS-W-002 "Patek Philippe Nautilus 5711"   118000 "Patek Philippe"  watches true  "Roestvrij staal · 40mm · Cal. 26-330 · complete set."
create_product OPUS-W-003 "Omega Speedmaster Professional" 6400   "Omega"           watches true  "Moonwatch · 42mm · handopwind Cal. 3861 · 2021."
create_product OPUS-W-004 "Cartier Tank Louis Cartier"     11200  "Cartier"         watches false "18k geelgoud · handopwind · alligatorband."
create_product OPUS-W-005 "Audemars Piguet Royal Oak"      38000  "Audemars Piguet" watches false "Staal · 41mm · 'Jumbo' blauwe wijzerplaat · Cal. 4302."
create_product OPUS-W-006 "Tudor Black Bay Fifty-Eight"    3650   "Tudor"           watches false "Staal · 39mm · Cal. MT5402 · marineblauwe ring."

echo "▸ Onderhoud…"
# The one genuinely payable item — a token €5 that records terms acceptance.
create_product OPUS-AGREEMENT "Serviceovereenkomst & inname" 5 "" service false \
  "Door deze symbolische €5 te accepteren en te betalen, gaat u akkoord met de servicevoorwaarden van Opus voor het werk aan uw horloge. Dit is uw vastgelegde bewijs van akkoord — geen betaling voor de reparatie zelf."
# Enquiry-only services (no price → not directly purchasable in sprint 1).
create_product OPUS-S-REVISION "Volledige revisie"     "" "" service false "Volledige revisie van het uurwerk — reinigen, smeren, afstellen en opnieuw afdichten. Op offerte."
create_product OPUS-S-POLISH   "Polijsten & afwerken"  "" "" service false "Kast en band met de hand opnieuw afwerken. Op offerte."
create_product OPUS-S-CRYSTAL  "Glas & afdichting"     "" "" service false "Glas vervangen en de volledige afdichting vernieuwen. Op offerte."

echo
echo "✓ Demo content seeded."
