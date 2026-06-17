<?php
/**
 * Import watches from the partner site Amstel Watches (Shopify) into WooCommerce.
 *
 * Pulls the public products.json (title, Dutch description, brand=vendor, price,
 * images on Shopify's CDN), creates a WooCommerce product per watch, and
 * sideloads its images (first = featured, rest = gallery).
 *
 * Idempotent & resumable: products are keyed by SKU "AMSTEL-<shopify id>", so
 * re-running skips what already imported — a timeout mid-run is safe to resume.
 *
 * Run (LIMIT optional):
 *   docker compose run --rm -T wpcli eval-file - <LIMIT> < scripts/import-amstel.php
 *
 * @package opus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$limit       = isset( $args[0] ) ? max( 1, intval( $args[0] ) ) : 999;
$max_images  = 4;
$source      = 'https://amstelwatches.nl/products.json?limit=250&page=1';

$resp = wp_remote_get( $source, array( 'timeout' => 30 ) );
if ( is_wp_error( $resp ) ) {
	WP_CLI::error( 'Fetch failed: ' . $resp->get_error_message() );
}
$data     = json_decode( wp_remote_retrieve_body( $resp ), true );
$products = isset( $data['products'] ) ? $data['products'] : array();
if ( empty( $products ) ) {
	WP_CLI::error( 'No products in feed.' );
}

// Category: Horloges (stable slug "watches").
$cat     = get_term_by( 'slug', 'watches', 'product_cat' );
$cat_ids = $cat ? array( $cat->term_id ) : array();

// Lead with the non-Rolex pieces (variety on the homepage), then price desc.
usort(
	$products,
	function ( $a, $b ) {
		$ar = strtolower( $a['vendor'] ) === 'rolex' ? 1 : 0;
		$br = strtolower( $b['vendor'] ) === 'rolex' ? 1 : 0;
		if ( $ar !== $br ) {
			return $ar - $br;
		}
		return floatval( $b['variants'][0]['price'] ) <=> floatval( $a['variants'][0]['price'] );
	}
);

$count = 0;
$created = 0;
$skipped = 0;
$featured = 0;

foreach ( $products as $p ) {
	if ( $count >= $limit ) {
		break;
	}
	++$count;

	$sku = 'AMSTEL-' . $p['id'];
	if ( wc_get_product_id_by_sku( $sku ) ) {
		++$skipped;
		WP_CLI::log( "= {$p['title']} (exists)" );
		continue;
	}

	$price = isset( $p['variants'][0]['price'] ) ? $p['variants'][0]['price'] : '';
	$plain = trim( wp_strip_all_tags( $p['body_html'] ) );
	$short = mb_substr( $plain, 0, 120 );
	if ( mb_strlen( $plain ) > 120 ) {
		$short = preg_replace( '/\s+\S*$/u', '…', $short );
	}

	$prod = new WC_Product_Simple();
	$prod->set_name( $p['title'] );
	$prod->set_sku( $sku );
	if ( '' !== $price ) {
		$prod->set_regular_price( $price );
	}
	$prod->set_description( wp_kses_post( $p['body_html'] ) );
	$prod->set_short_description( $short );
	$prod->set_catalog_visibility( 'visible' );
	$prod->set_stock_status( 'instock' );
	if ( $cat_ids ) {
		$prod->set_category_ids( $cat_ids );
	}
	$is_featured = ( $created < 3 ); // first three (non-Rolex lead) feature on the homepage.
	$prod->set_featured( $is_featured );
	$pid = $prod->save();

	if ( ! empty( $p['vendor'] ) ) {
		wp_set_object_terms( $pid, $p['vendor'], 'product_brand' );
	}

	$gallery = array();
	$imgs    = isset( $p['images'] ) ? $p['images'] : array();
	foreach ( array_slice( $imgs, 0, $max_images ) as $i => $img ) {
		$att = media_sideload_image( $img['src'], $pid, $p['title'], 'id' );
		if ( is_wp_error( $att ) ) {
			continue;
		}
		if ( 0 === $i ) {
			set_post_thumbnail( $pid, $att );
		} else {
			$gallery[] = $att;
		}
	}
	if ( $gallery ) {
		$prod = wc_get_product( $pid );
		$prod->set_gallery_image_ids( $gallery );
		$prod->save();
	}

	++$created;
	if ( $is_featured ) {
		++$featured;
	}
	WP_CLI::log( "+ {$p['title']} (€{$price}) [" . count( $imgs ) . ' imgs]' . ( $is_featured ? ' *featured' : '' ) );
}

WP_CLI::log( "── Done. created={$created} skipped={$skipped} featured={$featured} ──" );
