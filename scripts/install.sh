#!/usr/bin/env bash
# Idempotent first-run setup for the Maison & Opus local stack.
# Safe to re-run: it skips steps that are already done.
set -euo pipefail
cd "$(dirname "$0")/.."

# Load env (WP_URL, admin creds, …)
set -a; source .env; set +a

wp() { docker compose run --rm wpcli "$@"; }

echo "▸ Waiting for the database & WordPress files to settle…"
docker compose up -d db wordpress >/dev/null

# wp-cli needs the core files in the wp_data volume; the wordpress
# container populates them on first boot. Poll until wp-config is reachable.
for i in $(seq 1 30); do
  if wp core is-installed >/dev/null 2>&1; then break; fi
  if wp config path >/dev/null 2>&1 || [ "$i" -ge 3 ]; then break; fi
  sleep 2
done

if wp core is-installed >/dev/null 2>&1; then
  echo "▸ WordPress already installed — skipping core install."
else
  echo "▸ Installing WordPress core…"
  wp core install \
    --url="$WP_URL" \
    --title="$WP_TITLE" \
    --admin_user="$WP_ADMIN_USER" \
    --admin_password="$WP_ADMIN_PASSWORD" \
    --admin_email="$WP_ADMIN_EMAIL" \
    --skip-email
fi

echo "▸ Ensuring WooCommerce is installed & active…"
wp plugin is-installed woocommerce >/dev/null 2>&1 || wp plugin install woocommerce
wp plugin is-active woocommerce >/dev/null 2>&1 || wp plugin activate woocommerce

echo "▸ Activating the Maison & Opus theme…"
wp theme activate maison-opus

echo "▸ Installing Dutch (nl_NL)…"
wp language core install nl_NL --activate >/dev/null 2>&1 || true
wp language plugin install woocommerce nl_NL >/dev/null 2>&1 || true

echo "▸ Configuring store basics (Dutch, EUR)…"
wp option update woocommerce_currency EUR
wp option update woocommerce_default_country NL
wp option update woocommerce_price_num_decimals 0
wp option update woocommerce_price_thousand_sep "."
wp option update woocommerce_price_decimal_sep ","
wp option update woocommerce_currency_pos left_space
wp option update blogdescription "Onafhankelijk horlogehuis — gekocht, verkocht & onderhouden"
wp rewrite structure '/%postname%/' --hard >/dev/null 2>&1 || true

echo
echo "✓ Done."
echo "  Storefront : $WP_URL"
echo "  Admin      : $WP_URL/wp-admin  ($WP_ADMIN_USER / $WP_ADMIN_PASSWORD)"
echo "  phpMyAdmin : http://localhost:${PMA_PORT}"
