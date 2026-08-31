#!/usr/bin/env bash
#
# Idempotent WooCommerce configuration for Opus Watches.
#
# Run from the directory holding the compose stack:
#
#   local:  cd ~/Code/opus-watch && scripts/configure-shop.sh
#   prod:   ssh erik@85.17.236.215
#           cd /opt/opus-watch
#           WP="docker compose --profile tools run --rm -T wpcli" \
#             bash -s < scripts/configure-shop.sh
#
# Safe to re-run: every step checks the current value first.
#
# NOT set here, on purpose:
#   - payment gateways     — the customer's Mollie account (B3)
#   - final shipping rates — placeholders below, customer decides (B4)
#   - the returns policy   — customer decides (B7)
set -euo pipefail

WP="${WP:-docker compose run --rm -T wpcli}"

say() { printf '\n\033[1m==> %s\033[0m\n' "$1"; }
set_option() {
	local key="$1" want="$2" have
	have="$($WP option get "$key" 2>/dev/null || true)"
	if [ "$have" = "$want" ]; then
		printf '    %-42s = %s (unchanged)\n' "$key" "$want"
	else
		$WP option update "$key" "$want" >/dev/null
		printf '    %-42s : %s -> %s\n' "$key" "${have:-<unset>}" "$want"
	fi
}

# ─────────────────────────────────────────────────────────────────
say "Site identity"
set_option blogname "Opus Watches"

# ─────────────────────────────────────────────────────────────────
say "BTW — 21% Dutch standard rate, prices entered including tax"
# Prices already in the catalogue are gross, so entering them as
# tax-inclusive keeps every displayed price exactly as it is today and
# breaks the 21% out in the cart, on the invoice and in the order.
set_option woocommerce_calc_taxes            yes
set_option woocommerce_prices_include_tax    yes
set_option woocommerce_tax_based_on          billing
set_option woocommerce_default_customer_address base
set_option woocommerce_tax_display_shop      incl
set_option woocommerce_tax_display_cart      incl
set_option woocommerce_tax_total_display     itemized
set_option woocommerce_price_display_suffix  "incl. btw"

if [ "$($WP wc tax list --user=1 --format=count 2>/dev/null || echo 0)" -gt 0 ]; then
	echo "    tax rate                                   already present (unchanged)"
else
	$WP wc tax create --user=1 --country=NL --rate=21 --name="BTW 21%" \
		--shipping=true --class=standard >/dev/null
	echo "    tax rate                                   : created NL 21%"
fi

# ─────────────────────────────────────────────────────────────────
say "E-mail — afzender op het geauthenticeerde domein"
# DKIM only aligns when the From address sits on the domain Brevo authenticated.
# Order mail was going out as erik@datadepartment.io, which fails DMARC.
set_option woocommerce_email_from_name    "Opus Watches"
set_option woocommerce_email_from_address "info@opus.watches"
set_option woocommerce_stock_email_recipient "info@opus.watches"

read -r -d '' MAIL_PHP <<'PHP' || true
// Store-facing notifications go to the shop, not to whoever built it.
foreach ( array( 'new_order', 'cancelled_order', 'failed_order' ) as $email ) {
	$key      = 'woocommerce_' . $email . '_settings';
	$settings = get_option( $key, array() );
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}
	if ( isset( $settings['recipient'] ) && 'info@opus.watches' === $settings['recipient'] ) {
		WP_CLI::log( '    ' . str_pad( $email, 28 ) . ' = info@opus.watches (unchanged)' );
		continue;
	}
	$settings['recipient'] = 'info@opus.watches';
	update_option( $key, $settings );
	WP_CLI::log( '    ' . str_pad( $email, 28 ) . ' : -> info@opus.watches' );
}
PHP
$WP eval "$MAIL_PHP"

# ─────────────────────────────────────────────────────────────────
say "Order attribution — uit, want het zet trackingcookies"
# WooCommerce's order attribution loads sourcebuster.js, which drops seven
# first-party cookies (sbjs_first, sbjs_current, sbjs_udata, …) recording
# referrer, campaign and UTM data on the very first page view, before anyone
# consents to anything. That is non-essential tracking: keep it and the site
# legally needs a cookie banner.
#
# The trade-off is losing the "how did this customer find us" field on orders.
# Umami already answers that at an aggregate level. If the customer would
# rather have per-order attribution, set this back to yes AND add a consent
# banner — the two go together.
set_option woocommerce_feature_order_attribution_enabled no

# ─────────────────────────────────────────────────────────────────
say "Verzending — placeholder rates, to be confirmed by the customer"
# The wc CLI can only *list* zone locations, so the zone is built through
# WooCommerce's own API instead.
#
# TODO(customer): confirm the real rate. Insured, signed-for shipping of
# five-figure goods is not free — the 25 below is a placeholder, not a quote.
# Note that WooCommerce takes shipping costs EXCLUDING btw, so 25 shows to
# the customer as 30,25 (rounded to 30, the shop displays whole euros).
read -r -d '' ZONE_PHP <<'PHP' || true
$zone = null;
foreach ( WC_Shipping_Zones::get_zones() as $z ) {
	if ( 'Nederland' === $z['zone_name'] ) {
		$zone = new WC_Shipping_Zone( $z['zone_id'] );
	}
}
if ( ! $zone ) {
	$zone = new WC_Shipping_Zone();
	$zone->set_zone_name( 'Nederland' );
	$zone->set_zone_order( 1 );
}
$zone->set_locations( array( array( 'code' => 'NL', 'type' => 'country' ) ) );
$zone->save();

if ( $zone->get_shipping_methods() ) {
	WP_CLI::log( sprintf( '    zone Nederland #%d = %d method(s) (unchanged)', $zone->get_id(), count( $zone->get_shipping_methods() ) ) );
} else {
	$flat = $zone->add_shipping_method( 'flat_rate' );
	update_option(
		'woocommerce_flat_rate_' . $flat . '_settings',
		array( 'title' => 'Verzekerde verzending', 'tax_status' => 'taxable', 'cost' => '25' )
	);
	$pickup = $zone->add_shipping_method( 'local_pickup' );
	update_option(
		'woocommerce_local_pickup_' . $pickup . '_settings',
		array( 'title' => 'Afhalen op afspraak', 'tax_status' => 'taxable', 'cost' => '0' )
	);
	WP_CLI::log( sprintf( '    zone Nederland #%d : created flat_rate 25 + local_pickup', $zone->get_id() ) );
}
PHP
$WP eval "$ZONE_PHP"

say "Done"
echo "Still outstanding and owned by the customer:"
echo "  · Mollie account -> enable the gateway"
echo "  · confirm the shipping rate above (currently a placeholder)"
echo "  · returns policy + terms, then set woocommerce_terms_page_id"
