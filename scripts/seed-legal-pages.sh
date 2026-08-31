#!/usr/bin/env bash
#
# Create or update the legal pages from content/legal/*.html.
#
#   local:  cd ~/Code/opus-watch && scripts/seed-legal-pages.sh
#   prod:   scp the repo's content/legal + this script, or run it from a
#           checkout on the server with:
#           WP="docker compose --profile tools run --rm -T wpcli" \
#             scripts/seed-legal-pages.sh
#
# New pages are created as DRAFTS on purpose: both documents carry
# [AANVULLEN] markers that the shop owner has to resolve — btw-id, garantie-
# termijn, retourwerkwijze, levertijd — and publishing an unfinished privacy
# statement or set of terms is worse than having none.
#
# Re-running updates the body of an existing page and leaves its status,
# title and slug alone, so edits made in wp-admin to the status are kept but
# body edits made there WILL be overwritten. Edit the HTML in the repo.
set -euo pipefail

WP="${WP:-docker compose run --rm -T wpcli}"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"

upsert() {
	local slug="$1" title="$2" file="$3" b64
	if [ ! -f "$file" ]; then
		echo "  missing: $file" >&2
		return 1
	fi
	b64="$(base64 -w0 < "$file")"

	# base64 keeps the HTML clear of every quoting layer between here and PHP.
	$WP eval "
	\$content = base64_decode( '${b64}' );
	\$page    = get_page_by_path( '${slug}' );
	if ( \$page ) {
		wp_update_post( array( 'ID' => \$page->ID, 'post_content' => \$content ) );
		WP_CLI::log( sprintf( '    %-24s updated  (#%d, %s)', '${slug}', \$page->ID, \$page->post_status ) );
		\$id = \$page->ID;
	} else {
		\$id = wp_insert_post( array(
			'post_type'    => 'page',
			'post_status'  => 'draft',
			'post_title'   => '${title}',
			'post_name'    => '${slug}',
			'post_content' => \$content,
		) );
		WP_CLI::log( sprintf( '    %-24s created  (#%d, draft)', '${slug}', \$id ) );
	}
	"
}

printf '\n\033[1m==> Legal pages\033[0m\n'
upsert privacyverklaring   "Privacyverklaring"    "$ROOT/content/legal/privacyverklaring.html"
upsert algemene-voorwaarden "Algemene voorwaarden" "$ROOT/content/legal/algemene-voorwaarden.html"

printf '\n\033[1m==> Wiring\033[0m\n'
# Only point WordPress and WooCommerce at these once they are actually
# published — a terms checkbox linking to a draft is a 404 at checkout.
$WP eval '
$map = array(
	"wp_page_for_privacy_policy"  => "privacyverklaring",
	"woocommerce_terms_page_id"   => "algemene-voorwaarden",
);
foreach ( $map as $option => $slug ) {
	$page = get_page_by_path( $slug );
	if ( $page && "publish" === $page->post_status ) {
		update_option( $option, $page->ID );
		WP_CLI::log( sprintf( "    %-28s -> #%d", $option, $page->ID ) );
	} else {
		WP_CLI::log( sprintf( "    %-28s skipped (%s is not published yet)", $option, $slug ) );
	}
}
'

printf '\n\033[1m==> Done\033[0m\n'
echo "Both pages are drafts. Before publishing, resolve every [AANVULLEN] marker:"
echo "  · btw-identificatienummer (required on a webshop, currently missing)"
echo "  · garantietermijn, retourwerkwijze, levertijd, betaalmethoden"
echo "  · article 14 — the Webshop Keurmerk dispute scheme only applies to members"
echo "Then re-run this script to wire up the privacy and terms page settings."
