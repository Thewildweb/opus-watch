<?php
/**
 * Backfill watch filter data. Idempotent.
 *
 *  - Seeds the `watch_type` taxonomy terms.
 *  - Classifies every watch by model name (client can adjust per product
 *    in wp-admin afterwards — the "Type horloge" box on the product).
 *  - Extracts the build year from the imported spec table into _opus_year.
 *
 * Run: docker compose run --rm -T wpcli eval-file - < scripts/backfill-watch-meta.php
 */

$types = array(
	'sport-tool' => 'Sport & tool',
	'dress'      => 'Dress',
	'dames'      => 'Dames',
);
foreach ( $types as $slug => $name ) {
	if ( ! term_exists( $slug, 'watch_type' ) ) {
		wp_insert_term( $name, 'watch_type', array( 'slug' => $slug ) );
		WP_CLI::log( "term created: {$name}" );
	}
}

// Retired bucket ("Casual Oyster" was a competitor quirk, not a real type).
$retired = term_exists( 'casual-oyster', 'watch_type' );
if ( $retired ) {
	wp_delete_term( (int) $retired['term_id'], 'watch_type' );
	WP_CLI::log( 'term removed: Casual Oyster' );
}

/** Type rules, first match wins. Oyster Perpetual 36/41 counts as dress. */
function opus_classify_watch( $name ) {
	$rules = array(
		'dames'      => '/31\s?MM|Datejust 31|28\s?MM|26\s?MM|\bLady|\bLadies|4947/i',
		'sport-tool' => '/Submariner|Sea-Dweller|Deepsea|GMT-Master|Explorer|Daytona|Yacht-Master|Milgauss|Air-King|Royal Oak|Nautilus|Aquanaut|Overseas|Black Bay|Pelagos|Speedmaster|Seamaster|Pilot/i',
		'dress'      => '/Datejust|Day-Date|Sky-Dweller|Oyster Perpetual|Cellini|Calatrava|Annual Calendar|Tank|Santos|Portugieser|Patrimony/i',
	);
	foreach ( $rules as $slug => $re ) {
		if ( preg_match( $re, $name ) ) {
			return $slug;
		}
	}
	return '';
}

/**
 * Reference => family lookup from the watchbase catalogue that ships with the
 * theme (data/watches.json). Sizes are stripped from the family name so
 * "Datejust 41" and "Datejust 36" both file under "Datejust".
 */
function opus_watchbase_family_map() {
	static $map = null;
	if ( null !== $map ) {
		return $map;
	}
	$map  = array();
	$file = get_template_directory() . '/data/watches.json';
	$rows = file_exists( $file ) ? json_decode( file_get_contents( $file ), true ) : array();
	foreach ( (array) $rows as $row ) {
		if ( empty( $row['reference'] ) || empty( $row['family'] ) ) {
			continue;
		}
		// "126334-0034 (aka: M126334-0034)" -> "126334-0034"
		$ref = strtolower( strtok( trim( $row['reference'] ), ' ' ) );
		$ref = ltrim( $ref, 'm' );
		// "Datejust 41" -> "Datejust", "Oyster Perpetual 31" -> "Oyster Perpetual".
		$map[ $ref ] = preg_replace( '/\s+\d+$/', '', $row['family'] );
	}
	return $map;
}

/** Family via the reference in the product name (the last token). */
function opus_model_from_reference( $name ) {
	$tokens = preg_split( '/\s+/', trim( $name ) );
	$token  = strtolower( end( $tokens ) );
	if ( '' === $token || ! preg_match( '/\d/', $token ) ) {
		return '';
	}
	foreach ( opus_watchbase_family_map() as $ref => $family ) {
		if ( $ref === $token || 0 === strpos( $ref, $token . '-' ) ) {
			return $family;
		}
	}
	return '';
}

/**
 * Fallback for references missing from the watchbase data: match known family
 * names in the product title. Names align with watchbase family naming
 * (e.g. GMT-Master II files under "GMT-Master", like watchbase does).
 */
function opus_model_from_name( $name ) {
	$families = array(
		'Explorer II', 'Explorer', 'GMT-Master', 'Sea-Dweller', 'Sky-Dweller',
		'Datejust', 'Day-Date', 'Daytona', 'Oyster Perpetual', 'Submariner',
		'Yacht-Master', 'Milgauss', 'Air-King', 'Royal Oak', 'Nautilus',
		'Aquanaut', 'Annual Calendar', 'Calatrava', 'Overseas', 'Black Bay',
		'Pelagos', 'Speedmaster', 'Seamaster', 'Tank', 'Santos',
	);
	foreach ( $families as $family ) {
		if ( false !== stripos( $name, $family ) ) {
			return $family;
		}
	}
	return '';
}

$products = wc_get_products( array( 'limit' => -1, 'category' => array( 'watches' ), 'status' => 'any' ) );
$unclassified = 0;

foreach ( $products as $p ) {
	$id   = $p->get_id();
	$name = $p->get_name();

	$slug = opus_classify_watch( $name );
	if ( $slug ) {
		wp_set_object_terms( $id, $slug, 'watch_type' );
	} else {
		$unclassified++;
		WP_CLI::warning( "unclassified: {$name}" );
	}

	$model  = opus_model_from_reference( $name );
	$origin = 'ref';
	if ( ! $model ) {
		$model  = opus_model_from_name( $name );
		$origin = $model ? 'name' : '—';
	}
	wp_set_object_terms( $id, $model ? $model : array(), 'watch_model' );

	// "Year</td>...<td>2020" in the imported spec table.
	$flat = preg_replace( '/\s+/', ' ', wp_strip_all_tags( $p->get_description() ) );
	if ( preg_match( '/\bYear\s+(\d{4})\b/i', $flat, $m ) ) {
		update_post_meta( $id, '_opus_year', $m[1] );
	}

	WP_CLI::log( sprintf( '%d  %-11s %-16s (%-4s) %s  %s', $id, $slug ? $slug : '—', $model ? $model : '—', $origin, get_post_meta( $id, '_opus_year', true ) ?: '----', $name ) );
}

// Drop model terms nothing points at anymore (e.g. after re-grouping).
foreach ( get_terms( array( 'taxonomy' => 'watch_model', 'hide_empty' => false ) ) as $t ) {
	if ( 0 === $t->count ) {
		wp_delete_term( $t->term_id, 'watch_model' );
		WP_CLI::log( "empty model term removed: {$t->name}" );
	}
}

WP_CLI::success( count( $products ) . ' watches processed, ' . $unclassified . ' unclassified.' );
