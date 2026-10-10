<?php
/**
 * Import in-stock watches from a prepared folder into WooCommerce, NL + EN.
 *
 * The folder holds products.json (one object per watch: sku, slug, brand,
 * model, type, ref, size/case/band/dial/staat attribute values in Dutch,
 * move_/clasp_/title_/short_/desc_ in nl+en, featured, images[]) and the
 * processed images next to it, in <slug>/<file>.
 *
 * Per watch it creates the Dutch product, then its English translation
 * (same SKU, slug and images; English twin terms for the pa_* attributes).
 * No price is set: the theme shows "Prijs op aanvraag" for those.
 *
 * Idempotent & resumable, keyed by SKU: finished watches are skipped; a watch
 * whose import was interrupted (no _opus_import_done flag) is removed with its
 * images and redone.
 *
 * Run from the WP root (inside the wpcli container):
 *   wp eval-file import-stock.php <dir>                  # import
 *   wp eval-file import-stock.php <dir> trash-amstel     # import, then trash the AMSTEL-* placeholders
 *
 * @package opus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$dir          = rtrim( $args[0] ?? '', '/' );
$trash_amstel = in_array( 'trash-amstel', $args, true );
$watches      = json_decode( (string) @file_get_contents( "$dir/products.json" ), true );
if ( ! $watches ) {
	WP_CLI::error( "No products.json in '$dir'." );
}

$lang = function ( $code ) {
	do_action( 'wpml_switch_language', $code );
};

/** term_taxonomy_id of a term (WPML keys taxonomy translations on it). */
$ttid = function ( $term_id, $tax ) {
	$t = get_term( $term_id, $tax );
	return $t && ! is_wp_error( $t ) ? (int) $t->term_taxonomy_id : 0;
};

/**
 * English name for a new Dutch attribute term: reuse the English twin of a
 * same-named term in another pa_* taxonomy (e.g. "Staal/Roségoud"), else keep
 * the Dutch name (sizes like "39 mm").
 */
$en_name = function ( $nl_name ) {
	$known = array(
		'Staal'          => 'Steel',
		'Staal/Goud'     => 'Steel/Gold',
		'Staal/Roségoud' => 'Steel/Rose gold',
		'Roségoud'       => 'Rose gold',
		'Geelgoud'       => 'Yellow gold',
		'Witgoud'        => 'White gold',
		'Platina'        => 'Platinum',
		'Leer'           => 'Leather',
	);
	if ( isset( $known[ $nl_name ] ) ) {
		return $known[ $nl_name ];
	}
	foreach ( wc_get_attribute_taxonomy_names() as $tax ) {
		$t = get_term_by( 'name', $nl_name, $tax );
		if ( ! $t ) {
			continue;
		}
		$en = apply_filters( 'wpml_object_id', $t->term_id, $tax, false, 'en' );
		if ( $en && (int) $en !== (int) $t->term_id ) {
			return get_term( $en, $tax )->name;
		}
	}
	return $nl_name;
};

/** [nl term id, en term id] for a Dutch attribute value, creating both if needed. */
$attr_terms = function ( $tax, $nl_name ) use ( $lang, $ttid, $en_name ) {
	$lang( 'nl' );
	$t = get_term_by( 'name', $nl_name, $tax );
	if ( ! $t ) {
		$r = wp_insert_term( $nl_name, $tax );
		if ( is_wp_error( $r ) ) {
			WP_CLI::error( "$tax/$nl_name: " . $r->get_error_message() );
		}
		$t = get_term( $r['term_id'], $tax );
		WP_CLI::log( "  new term $tax: $nl_name" );
	}
	$en = apply_filters( 'wpml_object_id', $t->term_id, $tax, false, 'en' );
	if ( ! $en || (int) $en === (int) $t->term_id ) {
		$name = $en_name( $nl_name );
		$lang( 'en' );
		$r = wp_insert_term( $name, $tax, array( 'slug' => sanitize_title( $name ) . '-en' ) );
		if ( is_wp_error( $r ) ) {
			WP_CLI::error( "$tax/$name (en): " . $r->get_error_message() );
		}
		$en  = $r['term_id'];
		$trid = apply_filters( 'wpml_element_trid', null, $ttid( $t->term_id, $tax ), 'tax_' . $tax );
		do_action(
			'wpml_set_element_language_details',
			array(
				'element_id'           => $ttid( $en, $tax ),
				'element_type'         => 'tax_' . $tax,
				'trid'                 => $trid,
				'language_code'        => 'en',
				'source_language_code' => 'nl',
			)
		);
		$lang( 'nl' );
		WP_CLI::log( "  new term $tax (en): $name" );
	}
	return array( (int) $t->term_id, (int) $en );
};

/** WC attribute objects (hidden: the theme renders its own spec sheet). */
$attributes = function ( $map ) {
	$out = array();
	foreach ( $map as $tax => $term_id ) {
		$a = new WC_Product_Attribute();
		$a->set_id( wc_attribute_taxonomy_id_by_name( $tax ) );
		$a->set_name( $tax );
		$a->set_options( array( $term_id ) );
		$a->set_visible( false );
		$a->set_variation( false );
		$out[ $tax ] = $a;
	}
	return $out;
};

/** NL product id for a SKU (EN translations share the SKU). */
$nl_by_sku = function ( $sku ) {
	global $wpdb;
	$ids = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = '_sku' AND pm.meta_value = %s AND p.post_type = 'product' AND p.post_status <> 'trash'", $sku ) );
	foreach ( $ids as $id ) {
		if ( 'nl' === apply_filters( 'wpml_element_language_code', null, array( 'element_id' => $id, 'element_type' => 'post_product' ) ) ) {
			return (int) $id;
		}
	}
	return 0;
};

$lang( 'nl' );
$cat_nl = get_term_by( 'slug', 'watches', 'product_cat' );
if ( ! $cat_nl ) {
	WP_CLI::error( 'No product_cat "watches".' );
}
$cat_en = (int) apply_filters( 'wpml_object_id', $cat_nl->term_id, 'product_cat', true, 'en' );

$done = 0;
foreach ( $watches as $w ) {
	$sku = $w['sku'];
	$lang( 'nl' );

	$existing = $nl_by_sku( $sku );
	if ( $existing && get_post_meta( $existing, '_opus_import_done', true ) ) {
		WP_CLI::log( "= {$w['title_nl']} (exists)" );
		continue;
	}
	if ( $existing ) { // Interrupted earlier: clear it out and redo.
		$trid = apply_filters( 'wpml_element_trid', null, $existing, 'post_product' );
		$tr   = (array) apply_filters( 'wpml_get_element_translations', null, $trid, 'post_product' );
		foreach ( get_attached_media( 'image', $existing ) as $att ) {
			wp_delete_attachment( $att->ID, true );
		}
		foreach ( $tr as $t ) {
			wp_delete_post( $t->element_id, true );
		}
		wp_delete_post( $existing, true );
		WP_CLI::log( "~ {$w['title_nl']} (incomplete earlier run, redoing)" );
	}

	// Attribute terms, NL + EN twins.
	$pa = array(
		'pa_staat'         => $w['staat'],
		'pa_kastmaat'      => $w['size'],
		'pa_kastmateriaal' => $w['case'],
		'pa_bandmateriaal' => $w['band'],
		'pa_wijzerplaat'   => $w['dial'],
	);
	$pa_nl = array();
	$pa_en = array();
	foreach ( array_filter( $pa ) as $tax => $value ) {
		list( $pa_nl[ $tax ], $pa_en[ $tax ] ) = $attr_terms( $tax, $value );
	}

	// Dutch product.
	$lang( 'nl' );
	$p = new WC_Product_Simple();
	$p->set_name( $w['title_nl'] );
	$p->set_slug( $w['slug'] );
	$p->set_sku( $sku );
	$p->set_status( 'publish' );
	$p->set_description( $w['desc_nl'] );
	$p->set_short_description( $w['short_nl'] );
	$p->set_catalog_visibility( 'visible' );
	$p->set_stock_status( 'instock' );
	$p->set_sold_individually( true );
	$p->set_featured( ! empty( $w['featured'] ) );
	$p->set_category_ids( array( $cat_nl->term_id ) );
	$p->set_attributes( $attributes( $pa_nl ) );
	$nl_id = $p->save();

	$images = array();
	foreach ( $w['images'] as $i => $file ) {
		$src = "$dir/{$w['slug']}/$file";
		$tmp = wp_tempnam( $file );
		copy( $src, $tmp );
		$att = media_handle_sideload(
			array( 'name' => $file, 'tmp_name' => $tmp ),
			$nl_id,
			$w['title_nl'] . ( $i ? ' — foto ' . ( $i + 1 ) : '' )
		);
		if ( is_wp_error( $att ) ) {
			WP_CLI::warning( "$file: " . $att->get_error_message() );
			continue;
		}
		update_post_meta( $att, '_wp_attachment_image_alt', $w['title_nl'] );
		$images[] = $att;
	}
	$p = wc_get_product( $nl_id );
	$p->set_image_id( $images[0] ?? 0 );
	$p->set_gallery_image_ids( array_slice( $images, 1 ) );
	$p->save();

	// English translation.
	$lang( 'en' );
	$e = new WC_Product_Simple();
	$e->set_name( $w['title_en'] );
	$e->set_slug( sanitize_title( $w['title_en'] ) );
	$e->set_status( 'publish' );
	$e->set_description( $w['desc_en'] );
	$e->set_short_description( $w['short_en'] );
	$e->set_catalog_visibility( 'visible' );
	$e->set_stock_status( 'instock' );
	$e->set_sold_individually( true );
	$e->set_featured( ! empty( $w['featured'] ) );
	$e->set_category_ids( array( $cat_en ) );
	$e->set_attributes( $attributes( $pa_en ) );
	$e->set_image_id( $images[0] ?? 0 );
	$e->set_gallery_image_ids( array_slice( $images, 1 ) );
	$en_id = $e->save();
	do_action(
		'wpml_set_element_language_details',
		array(
			'element_id'           => $en_id,
			'element_type'         => 'post_product',
			'trid'                 => apply_filters( 'wpml_element_trid', null, $nl_id, 'post_product' ),
			'language_code'        => 'en',
			'source_language_code' => 'nl',
		)
	);
	// Same SKU as the Dutch original — set directly: Woo's uniqueness check
	// would refuse it before the translation link exists.
	update_post_meta( $en_id, '_sku', $sku );

	// Shared taxonomies + meta, both languages.
	foreach ( array( $nl_id => 'nl', $en_id => 'en' ) as $id => $code ) {
		$lang( $code );
		wp_set_object_terms( $id, $w['brand'], 'product_brand' );
		wp_set_object_terms( $id, $w['model'], 'watch_model' );
		wp_set_object_terms( $id, $w['type'], 'watch_type' );
		update_post_meta( $id, '_opus_reference', $w['ref'] );
		update_post_meta( $id, '_opus_movement', $w[ 'move_' . $code ] );
		update_post_meta( $id, '_opus_clasp', $w[ 'clasp_' . $code ] );
	}
	// Woo's attribute lookup table (sidebar filters): the update it queues for
	// the English product runs in the default language and would store Dutch
	// term ids — cancel it and build the English rows here, in English.
	foreach ( array( 1, 2, 3 ) as $op ) {
		as_unschedule_all_actions( 'woocommerce_run_product_attribute_lookup_update_callback', array( $en_id, $op ) );
	}
	$lang( 'en' );
	wc_get_container()->get( \Automattic\WooCommerce\Internal\ProductAttributesLookup\LookupDataStore::class )->create_data_for_product( wc_get_product( $en_id ) );
	$lang( 'nl' );

	update_post_meta( $nl_id, '_opus_import_done', 1 );
	update_post_meta( $en_id, '_opus_import_done', 1 );
	wc_delete_product_transients( $nl_id );
	wc_delete_product_transients( $en_id );

	++$done;
	WP_CLI::log( "+ {$w['title_nl']} (nl $nl_id / en $en_id, " . count( $images ) . ' images)' );
}

if ( $trash_amstel ) {
	global $wpdb;
	$old = $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = '_sku' AND pm.meta_value LIKE 'AMSTEL-%' AND p.post_type = 'product' AND p.post_status <> 'trash'" );
	foreach ( $old as $id ) {
		wp_trash_post( (int) $id );
	}
	WP_CLI::log( '- trashed ' . count( $old ) . ' AMSTEL-* placeholder products (both languages)' );
}

// ElasticPress serves the shop listings, one index per language; its on-save
// sync missed most products in a bulk run, so re-sync both languages.
foreach ( array( 'nl', 'en' ) as $code ) {
	WP_CLI::runcommand( "wpml_elasticpress sync --indexables=post --post-lang=$code", array( 'exit_error' => false ) );
}

WP_CLI::success( "Imported $done watch(es). Flush the object cache (redis-cli FLUSHALL) — wp-cli runs with Redis disabled." );
