<?php
/**
 * Local SEO + URL hygiene (from the SEO brief, ~/Code/opus-watch-seo).
 *
 * - The company's address/contact facts in one place (opus_nap()), used by
 *   the footer and the LocalBusiness JSON-LD.
 * - The business as JewelryStore (a LocalBusiness subtype) structured data,
 *   merged into Yoast's Organization so it's one entity on every page
 *   (LocalBusiness itself needs Yoast's paid Local add-on).
 * - /brand/… → /merk/… (both languages): the brand base was /brand/ for a
 *   while, and those URLs are still around.
 * - No "guess" redirects for unknown URLs: WordPress sent /brand/rolex/ to
 *   an arbitrary product (rolex-band-restauratie) — a wrong page served with
 *   a 301 is worse than a plain 404 for search engines and visitors.
 *
 * @package maison-opus
 */

/** Week order: key => [short label, admin label, schema.org day]. */
function opus_weekdays() {
	return array(
		'mon' => array( __( 'ma', 'maison-opus' ), 'maandag', 'Monday' ),
		'tue' => array( __( 'di', 'maison-opus' ), 'dinsdag', 'Tuesday' ),
		'wed' => array( __( 'wo', 'maison-opus' ), 'woensdag', 'Wednesday' ),
		'thu' => array( __( 'do', 'maison-opus' ), 'donderdag', 'Thursday' ),
		'fri' => array( __( 'vr', 'maison-opus' ), 'vrijdag', 'Friday' ),
		'sat' => array( __( 'za', 'maison-opus' ), 'zaterdag', 'Saturday' ),
		'sun' => array( __( 'zo', 'maison-opus' ), 'zondag', 'Sunday' ),
	);
}

/**
 * Name, address, phone, hours — edited under Site teksten → Bedrijfsprofiel.
 * The coordinates stay in code: the contact map is a baked image
 * (scripts/build-map-image.py) that has to be rebuilt if the address moves.
 */
function opus_nap() {
	$days = array_values( array_intersect( array_keys( opus_weekdays() ), explode( ',', opus_business_get( 'open_dagen' ) ) ) );
	return array(
		'name'     => 'Opus Watches',
		'street'   => opus_business_get( 'straat' ),
		'postcode' => opus_business_get( 'postcode' ),
		'city'     => opus_business_get( 'plaats' ),
		'country'  => 'NL',
		'phone'    => opus_business_get( 'telefoon' ),
		'email'    => opus_business_get( 'email' ),
		'days'     => $days,
		'opens'    => opus_business_get( 'opent' ),
		'closes'   => opus_business_get( 'sluit' ),
		'lat'      => 52.3120932,
		'lon'      => 4.9407954,
	);
}

/** "di – za · 10:00 – 18:00": consecutive days as a range, otherwise listed. */
function opus_opening_hours_text() {
	$nap = opus_nap();
	if ( ! $nap['days'] ) {
		return '';
	}
	$all   = array_keys( opus_weekdays() );
	$short = array_map( fn( $d ) => opus_weekdays()[ $d ][0], $nap['days'] );
	$first = array_search( $nap['days'][0], $all, true );
	$run   = array_slice( $all, $first, count( $nap['days'] ) ) === $nap['days'];
	$days  = ( $run && count( $short ) > 2 ) ? reset( $short ) . ' – ' . end( $short ) : implode( ', ', $short );
	return $days . ' · ' . $nap['opens'] . ' – ' . $nap['closes'];
}

/**
 * The business as schema.org properties (JewelryStore, a LocalBusiness
 * subtype), from the Bedrijfsprofiel. Without @context/@id/name/url: those
 * come from whoever outputs it (Yoast's Organization, or the fallback below).
 */
function opus_local_business_data() {
	$nap  = opus_nap();
	$data = array(
		'description' => __( 'Onafhankelijk horlogehuis in Amsterdam: in- en verkoop van luxe horloges, onderhoud en restauratie in eigen atelier.', 'maison-opus' ),
		'telephone'   => $nap['phone'],
		'email'       => $nap['email'],
		'priceRange'  => '€€€€',
		'address'     => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => $nap['street'],
			'postalCode'      => $nap['postcode'],
			'addressLocality' => $nap['city'],
			'addressCountry'  => $nap['country'],
		),
		'geo'         => array(
			'@type'     => 'GeoCoordinates',
			'latitude'  => $nap['lat'],
			'longitude' => $nap['lon'],
		),
		'openingHoursSpecification' => $nap['days'] ? array(
			array(
				'@type'     => 'OpeningHoursSpecification',
				'dayOfWeek' => array_map( fn( $d ) => opus_weekdays()[ $d ][2], $nap['days'] ),
				'opens'     => $nap['opens'],
				'closes'    => $nap['closes'],
			),
		) : null,
		'vatID'       => opus_business_get( 'btw_id' ) ? opus_business_get( 'btw_id' ) : null,
		'identifier'  => array(
			'@type'      => 'PropertyValue',
			'propertyID' => 'KvK',
			'value'      => opus_business_get( 'kvk' ),
		),
	);
	return array_filter( $data, fn( $v ) => null !== $v && '' !== $v );
}

/**
 * Yoast's Organization (Yoast → Instellingen → Sitevertegenwoordiging: name +
 * logo) becomes the JewelryStore too — one business entity on every page,
 * which products reference as their seller.
 */
add_filter(
	'wpseo_schema_organization',
	function ( $data ) {
		$data['@type'] = array( 'Organization', 'JewelryStore' );
		return array_merge( $data, opus_local_business_data() );
	}
);

/** Schema @id of the business: Yoast's Organization, or the fallback below. */
function opus_business_schema_id() {
	return home_url( '/' ) . ( defined( 'WPSEO_VERSION' ) ? '#organization' : '#localbusiness' );
}

/** Fallback without Yoast: the JewelryStore on its own, homepage + contact page. */
function opus_local_business_jsonld() {
	if ( defined( 'WPSEO_VERSION' ) ) {
		return; // Yoast outputs it, see wpseo_schema_organization above.
	}
	$contact    = get_page_by_path( 'contact' );
	$is_contact = $contact && is_page( apply_filters( 'wpml_object_id', $contact->ID, 'page', true ) );
	if ( ! is_front_page() && ! $is_contact ) {
		return;
	}
	$data = array_merge(
		array(
			'@context' => 'https://schema.org',
			'@type'    => 'JewelryStore',
			'@id'      => opus_business_schema_id(),
			'name'     => opus_nap()['name'],
			'url'      => home_url( '/' ),
			'logo'     => get_theme_file_uri( 'assets/images/favicon-192.png' ),
			'image'    => get_theme_file_uri( 'assets/images/hero-heritage.webp' ),
		),
		opus_local_business_data()
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
}
add_action( 'wp_head', 'opus_local_business_jsonld', 30 );

/** /brand/x/ and /en/brand/x/ → the /merk/ equivalent. */
function opus_redirect_brand_base() {
	$path = wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- only matched, then rebuilt.
	if ( $path && preg_match( '#^(/en)?/brand/(.*)$#', $path, $m ) ) {
		wp_safe_redirect( home_url( $m[1] . '/merk/' . $m[2] ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'opus_redirect_brand_base', 1 );

/**
 * One URL for "all watches": the shop page (/horloges/, /en/watches/).
 * The Horloges category showed the same products under the same title, so
 * it — and the shop's old slugs (/winkel/, /en/shop/) — 301 there, keeping
 * the page number and any filters.
 */
function opus_redirect_to_shop() {
	if ( ! function_exists( 'wc_get_page_permalink' ) ) {
		return;
	}
	$path  = wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- only matched, then rebuilt.
	$paged = 0;
	if ( is_product_category( array( 'watches', 'watches-en' ) ) ) {
		$paged = (int) get_query_var( 'paged' );
	} elseif ( $path && preg_match( '#^(?:/en)?/(?:winkel|shop)(?:/page/(\d+))?/?$#', $path, $m ) ) {
		$paged = isset( $m[1] ) ? (int) $m[1] : 0;
	} else {
		return;
	}
	$url = wc_get_page_permalink( 'shop' );
	if ( $paged > 1 ) {
		$url = trailingslashit( $url ) . 'page/' . $paged . '/';
	}
	if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
		$url .= '?' . wp_unslash( $_SERVER['QUERY_STRING'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- passed through to the same site.
	}
	wp_safe_redirect( $url, 301 );
	exit;
}
add_action( 'template_redirect', 'opus_redirect_to_shop', 1 );

/** The Horloges category redirects (see above), so it stays out of the sitemap. */
add_filter(
	'wpseo_exclude_from_sitemap_by_term_ids',
	function ( $ids ) {
		$term = get_term_by( 'slug', 'watches', 'product_cat' );
		$term = $term ? $term : get_term_by( 'slug', 'watches-en', 'product_cat' );
		if ( ! $term ) {
			return $ids;
		}
		$ids[] = $term->term_id;
		// WPML only finds the current language's term by slug; add its translations.
		foreach ( array_keys( (array) apply_filters( 'wpml_active_languages', null ) ) as $lang ) {
			$ids[] = (int) apply_filters( 'wpml_object_id', $term->term_id, 'product_cat', false, $lang );
		}
		return array_values( array_filter( array_unique( $ids ) ) );
	}
);

/**
 * /cart/ was a leftover Dutch cart page from the first WooCommerce install
 * (the real one is /winkelwagen/); it's unpublished, the URL redirects.
 */
function opus_redirect_old_cart() {
	$path = wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- only matched.
	if ( '/cart' === untrailingslashit( (string) $path ) && function_exists( 'wc_get_page_permalink' ) ) {
		wp_safe_redirect( wc_get_page_permalink( 'cart' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'opus_redirect_old_cart', 1 );

/** Cart, checkout and account pages (all languages): personal, nothing to index. */
function opus_private_shop_page_ids() {
	if ( ! function_exists( 'wc_get_page_id' ) ) {
		return array();
	}
	$ids = array();
	foreach ( array( 'cart', 'checkout', 'myaccount' ) as $page ) {
		$id = wc_get_page_id( $page );
		if ( $id <= 0 ) {
			continue;
		}
		$ids[] = $id;
		foreach ( array_keys( (array) apply_filters( 'wpml_active_languages', null ) ) as $lang ) {
			$ids[] = (int) apply_filters( 'wpml_object_id', $id, 'page', false, $lang );
		}
	}
	return array_values( array_filter( array_unique( $ids ) ) );
}

add_filter(
	'wpseo_exclude_from_sitemap_by_post_ids',
	fn( $ids ) => array_merge( (array) $ids, opus_private_shop_page_ids() )
);

add_filter(
	'wpseo_robots',
	function ( $robots ) {
		if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) {
			return 'noindex, follow';
		}
		return $robots;
	}
);

/** Unknown URL → 404, never a guessed "closest" post. */
add_filter( 'do_redirect_guess_404_permalink', '__return_false' );
