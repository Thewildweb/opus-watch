<?php
/**
 * Service pages (SEO brief, section B): /diensten/ plus one page per repair
 * intent (/horloge-reparatie-amsterdam/, /rolex-service/, …).
 *
 * The pages are ordinary WordPress pages, so the copy (title = H1, excerpt =
 * intro, editor content incl. the FAQ as "Details" blocks) is edited in
 * wp-admin. This file adds what the copy shouldn't have to carry: the page
 * layout (templates/service-page.php), the bookable services and their
 * prices (from the service products, one source of truth), links between
 * the pages, Service + FAQPage structured data, and the plumbing around them:
 *
 * - /diensten/ replaces the Woo "service" category as the hub (Dutch; the
 *   English category stays until the pages are translated);
 * - the Dutch service products become booking pages: noindex and out of the
 *   sitemap, so /product/polijsten/ doesn't compete with /horloge-polijsten/;
 * - brand, product and database pages link to the matching service page.
 *
 * @package maison-opus
 */

defined( 'ABSPATH' ) || exit;

/**
 * The service pages, keyed by page slug, in hub order.
 *
 * label/summary: the card on /diensten/; products: service product slugs
 * listed (with prices) on the page; related: sibling pages linked at the
 * bottom; type/from: Service schema (serviceType, lowest from-price).
 */
function opus_service_pages() {
	return array(
		'diensten'                   => array(
			'label'    => __( 'Diensten', 'maison-opus' ),
			'summary'  => '',
			'products' => array(),
			'related'  => array(),
			'type'     => __( 'Horlogeonderhoud en reparatie', 'maison-opus' ),
			'from'     => 450,
		),
		'horloge-reparatie-amsterdam' => array(
			'label'    => __( 'Horloge reparatie', 'maison-opus' ),
			'summary'  => __( 'Loopt uw horloge achter, staat het stil of is er iets kapot? Onderzoek en offerte vooraf.', 'maison-opus' ),
			'products' => array( 'revisie', 'horlogeglas-vervangen', 'wijzerplaat-wisselen', 'polijsten' ),
			'related'  => array( 'horloge-revisie', 'rolex-service', 'horlogeglas-vervangen', 'horloge-polijsten', 'horlogemaker-amsterdam' ),
			'type'     => __( 'Horlogereparatie', 'maison-opus' ),
			'from'     => 60,
		),
		'rolex-service'              => array(
			'label'    => __( 'Rolex service', 'maison-opus' ),
			'summary'  => __( 'Onafhankelijke revisie en onderhoud van uw Rolex, modern en vintage.', 'maison-opus' ),
			'products' => array( 'revisie', 'revisie-chronograaf', 'revisie-vintage', 'revisie-vintage-chronograaf', 'rolex-band-restauratie', 'polijsten' ),
			'related'  => array( 'horloge-revisie', 'horlogeband', 'horloge-polijsten', 'vintage-horloge-reparatie', 'omega-service' ),
			'type'     => __( 'Rolex service en revisie', 'maison-opus' ),
			'from'     => 450,
		),
		'omega-service'              => array(
			'label'    => __( 'Omega service', 'maison-opus' ),
			'summary'  => __( 'Revisie van Speedmaster, Seamaster en vintage Omega.', 'maison-opus' ),
			'products' => array( 'revisie', 'revisie-chronograaf', 'revisie-vintage', 'revisie-vintage-chronograaf', 'polijsten' ),
			'related'  => array( 'horloge-revisie', 'vintage-horloge-reparatie', 'rolex-service', 'horloge-polijsten' ),
			'type'     => __( 'Omega service en revisie', 'maison-opus' ),
			'from'     => 450,
		),
		'horloge-revisie'            => array(
			'label'    => __( 'Revisie', 'maison-opus' ),
			'summary'  => __( 'Volledige revisie van het uurwerk, met twee jaar garantie.', 'maison-opus' ),
			'products' => array( 'revisie', 'revisie-chronograaf', 'revisie-vintage', 'revisie-vintage-chronograaf', 'revisie-jaren-50-en-ouder' ),
			'related'  => array( 'rolex-service', 'omega-service', 'vintage-horloge-reparatie', 'horloge-polijsten', 'horloge-reparatie-amsterdam' ),
			'type'     => __( 'Horloge revisie', 'maison-opus' ),
			'from'     => 450,
		),
		'horloge-polijsten'          => array(
			'label'    => __( 'Polijsten', 'maison-opus' ),
			'summary'  => __( 'Kast en band volgens de richtlijnen van de fabrikant, en eerlijk advies.', 'maison-opus' ),
			'products' => array( 'polijsten', 'kastpoten-opmaken' ),
			'related'  => array( 'horlogeglas-vervangen', 'horlogeband', 'rolex-service', 'vintage-horloge-reparatie' ),
			'type'     => __( 'Horloge polijsten', 'maison-opus' ),
			'from'     => 150,
		),
		'horlogeglas-vervangen'      => array(
			'label'    => __( 'Horlogeglas', 'maison-opus' ),
			'summary'  => __( 'Saffier of plexiglas, met nieuwe afdichting en waterdichtheidstest.', 'maison-opus' ),
			'products' => array( 'horlogeglas-vervangen' ),
			'related'  => array( 'horloge-polijsten', 'horloge-reparatie-amsterdam', 'rolex-service' ),
			'type'     => __( 'Horlogeglas vervangen', 'maison-opus' ),
			'from'     => 60,
		),
		'horlogeband'                => array(
			'label'    => __( 'Band en kast', 'maison-opus' ),
			'summary'  => __( 'Restauratie van Oyster- en Jubileebanden, en kastpoten opmaken.', 'maison-opus' ),
			'products' => array( 'rolex-band-restauratie', 'kastpoten-opmaken' ),
			'related'  => array( 'rolex-service', 'horloge-polijsten' ),
			'type'     => __( 'Horlogeband restauratie', 'maison-opus' ),
			'from'     => 100,
		),
		'vintage-horloge-reparatie'  => array(
			'label'    => __( 'Vintage en restauratie', 'maison-opus' ),
			'summary'  => __( 'Restauratie met respect voor originaliteit en patina.', 'maison-opus' ),
			'products' => array( 'revisie-vintage', 'revisie-vintage-chronograaf', 'revisie-jaren-50-en-ouder' ),
			'related'  => array( 'rolex-service', 'omega-service', 'horloge-revisie', 'horloge-polijsten' ),
			'type'     => __( 'Vintage horloge restauratie', 'maison-opus' ),
			'from'     => 550,
		),
		'horlogemaker-amsterdam'     => array(
			'label'    => __( 'Het atelier', 'maison-opus' ),
			'summary'  => __( 'Onze horlogemakers en het atelier in Amsterdam.', 'maison-opus' ),
			'products' => array(),
			'related'  => array( 'horloge-reparatie-amsterdam', 'rolex-service', 'vintage-horloge-reparatie', 'horloge-revisie' ),
			'type'     => __( 'Horlogemaker', 'maison-opus' ),
			'from'     => 0,
		),
	);
}

/** Published service page for a slug (Dutch only for now), or null. */
function opus_service_page( $slug ) {
	static $cache = array();
	if ( ! array_key_exists( $slug, $cache ) ) {
		$page           = get_page_by_path( $slug );
		$cache[ $slug ] = ( $page && 'publish' === $page->post_status ) ? $page : null;
	}
	return $cache[ $slug ];
}

/** True on the Dutch site, where the service pages live. */
function opus_service_pages_lang_ok() {
	$lang = apply_filters( 'wpml_current_language', null );
	return ! $lang || 'nl' === $lang;
}

/** URL of a service page, or '' when it doesn't exist (or on /en/). */
function opus_service_page_url( $slug ) {
	if ( ! opus_service_pages_lang_ok() ) {
		return '';
	}
	$page = opus_service_page( $slug );
	return $page ? get_permalink( $page ) : '';
}

/** The services hub: /diensten/, else the service category. */
function opus_services_url() {
	$url = opus_service_page_url( 'diensten' );
	if ( $url ) {
		return $url;
	}
	$cat  = get_term_by( 'slug', 'service', 'product_cat' );
	$link = $cat ? get_term_link( $cat ) : '';
	return ( $link && ! is_wp_error( $link ) ) ? $link : home_url( '/' );
}

/** Service page for a watch brand: Rolex/Omega get their own, others the revisie page. */
function opus_service_url_for_brand( $brand ) {
	$slug = sanitize_title( (string) $brand );
	$url  = in_array( $slug, array( 'rolex', 'omega' ), true ) ? opus_service_page_url( $slug . '-service' ) : '';
	return $url ? $url : opus_service_page_url( 'horloge-revisie' );
}

/** Slug of the current page if it is a registered service page. */
function opus_current_service_page() {
	if ( ! is_page() || ! opus_service_pages_lang_ok() ) {
		return '';
	}
	$slug = get_post_field( 'post_name', get_queried_object_id() );
	return isset( opus_service_pages()[ $slug ] ) ? $slug : '';
}

/** Service products for a list of slugs, in that order (missing ones skipped). */
function opus_service_products_by_slug( $slugs ) {
	$out = array();
	foreach ( (array) $slugs as $slug ) {
		$post    = get_page_by_path( $slug, OBJECT, 'product' );
		// The Dutch product: the English twin can share the slug.
		$product = $post ? wc_get_product( apply_filters( 'wpml_object_id', $post->ID, 'product', true, 'nl' ) ) : null;
		if ( $product && 'publish' === $product->get_status() ) {
			$out[] = $product;
		}
	}
	return $out;
}

/** The service pages render through templates/service-page.php. */
add_filter(
	'template_include',
	function ( $template ) {
		if ( opus_current_service_page() ) {
			$custom = locate_template( 'templates/service-page.php' );
			return $custom ? $custom : $template;
		}
		return $template;
	}
);

/** Excerpts on pages: the hero intro of a service page. */
add_action( 'init', fn() => add_post_type_support( 'page', 'excerpt' ) );

/** FAQ from the page's "Details" blocks: [ [question, answer text], … ]. */
function opus_service_page_faq( $post ) {
	$faq = array();
	foreach ( parse_blocks( $post->post_content ) as $block ) {
		if ( 'core/details' !== $block['blockName'] ) {
			continue;
		}
		if ( ! preg_match( '#<summary[^>]*>(.*?)</summary>(.*)</details>#s', render_block( $block ), $m ) ) {
			continue;
		}
		$q = trim( wp_strip_all_tags( $m[1] ) );
		$a = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $m[2] ) ) );
		if ( $q && $a ) {
			$faq[] = array( $q, $a );
		}
	}
	return $faq;
}

/** Service + FAQPage structured data on the service pages. */
function opus_service_page_schema() {
	$slug = opus_current_service_page();
	if ( ! $slug ) {
		return;
	}
	$def  = opus_service_pages()[ $slug ];
	$post = get_queried_object();
	$url  = get_permalink( $post );

	$service = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Service',
		'@id'         => $url . '#service',
		'name'        => wp_strip_all_tags( get_the_title( $post ) ),
		'serviceType' => $def['type'],
		'description' => wp_strip_all_tags( get_the_excerpt( $post ) ),
		'url'         => $url,
		'provider'    => array( '@id' => opus_business_schema_id() ),
		'areaServed'  => array(
			'@type' => 'City',
			'name'  => 'Amsterdam',
		),
	);
	if ( $def['from'] ) {
		$service['offers'] = array(
			'@type'              => 'Offer',
			'priceCurrency'      => 'EUR',
			'priceSpecification' => array(
				'@type'         => 'PriceSpecification',
				'minPrice'      => $def['from'],
				'priceCurrency' => 'EUR',
			),
		);
	}
	$graph = array( $service );

	$faq = opus_service_page_faq( $post );
	if ( $faq ) {
		$graph[] = array(
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'@id'        => $url . '#faq',
			'mainEntity' => array_map(
				fn( $qa ) => array(
					'@type'          => 'Question',
					'name'           => $qa[0],
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text'  => $qa[1],
					),
				),
				$faq
			),
		);
	}
	foreach ( $graph as $node ) {
		echo '<script type="application/ld+json">' . wp_json_encode( $node, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
	}
}
add_action( 'wp_head', 'opus_service_page_schema', 31 );

/** Dutch service category → /diensten/ (keeps ?svc=ok and the like). */
function opus_redirect_service_category() {
	if ( ! function_exists( 'is_product_category' ) || ! is_product_category( 'service' ) || ! opus_service_pages_lang_ok() ) {
		return;
	}
	$url = opus_service_page_url( 'diensten' );
	if ( ! $url ) {
		return;
	}
	if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
		$url .= '?' . wp_unslash( $_SERVER['QUERY_STRING'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- passed through to the same site.
	}
	wp_safe_redirect( $url, 301 );
	exit;
}
add_action( 'template_redirect', 'opus_redirect_service_category', 1 );

/** Is this a Dutch service product (booking page once /diensten/ exists)? */
function opus_is_booking_product( $product_id ) {
	if ( ! opus_service_page( 'diensten' ) || ! has_term( 'service', 'product_cat', $product_id ) ) {
		return false;
	}
	$lang = apply_filters( 'wpml_element_language_code', null, array( 'element_id' => $product_id, 'element_type' => 'post_product' ) );
	return ! $lang || 'nl' === $lang;
}

add_filter(
	'wpseo_robots',
	function ( $robots ) {
		if ( function_exists( 'is_product' ) && is_product() && opus_is_booking_product( get_queried_object_id() ) ) {
			return 'noindex, follow';
		}
		return $robots;
	}
);

add_filter(
	'wpseo_exclude_from_sitemap_by_post_ids',
	function ( $ids ) {
		if ( ! function_exists( 'opus_service_products' ) ) {
			return $ids;
		}
		foreach ( opus_service_products() as $p ) {
			if ( opus_is_booking_product( $p->get_id() ) ) {
				$ids[] = $p->get_id();
			}
		}
		return $ids;
	}
);

/** The redirected Dutch service category stays out of the sitemap too. */
add_filter(
	'wpseo_exclude_from_sitemap_by_term_ids',
	function ( $ids ) {
		$cat = get_term_by( 'slug', 'service', 'product_cat' );
		if ( $cat && opus_service_page( 'diensten' ) ) {
			$ids[] = $cat->term_id;
		}
		return $ids;
	}
);

/** Service product → the page that explains it ("Alles over …" link). */
function opus_service_guide_for_product( $product_slug ) {
	$map  = array(
		'revisie'                     => 'horloge-revisie',
		'revisie-chronograaf'         => 'horloge-revisie',
		'revisie-vintage'             => 'vintage-horloge-reparatie',
		'revisie-vintage-chronograaf' => 'vintage-horloge-reparatie',
		'revisie-jaren-50-en-ouder'   => 'vintage-horloge-reparatie',
		'polijsten'                   => 'horloge-polijsten',
		'kastpoten-opmaken'           => 'horlogeband',
		'horlogeglas-vervangen'       => 'horlogeglas-vervangen',
		'rolex-band-restauratie'      => 'horlogeband',
		'wijzerplaat-wisselen'        => 'horloge-reparatie-amsterdam',
	);
	$slug = isset( $map[ $product_slug ] ) ? $map[ $product_slug ] : '';
	$url  = $slug ? opus_service_page_url( $slug ) : '';
	return $url ? array( $url, get_the_title( opus_service_page( $slug ) ) ) : null;
}
