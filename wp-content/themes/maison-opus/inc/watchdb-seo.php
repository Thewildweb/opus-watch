<?php
/**
 * Watch database — SEO for the reference catalogue.
 *
 * The detail route is a custom rewrite with no post behind it, so WordPress
 * resolved it as the front page and Yoast described all 2314 pages as the
 * homepage: one shared title, one shared description, and a canonical
 * pointing at https://opus.watches/ — an explicit instruction to Google not
 * to index any of them. This supplies the per-page metadata, readable URLs,
 * structured data and a sitemap.
 *
 * @package maison-opus
 */

defined( 'ABSPATH' ) || exit;

/* ─────────────────────────────────────────────────────────────
 * URLs — /database/{brand}/{name}-{reference}-{id}/
 * ───────────────────────────────────────────────────────────── */

/**
 * The bare reference: the scrape carries variants as "12510-0001 (aka: …)"
 * and only the leading token is the reference people search for.
 */
function opus_watch_ref_clean( $reference ) {
	return trim( preg_split( '/\s*\(/', (string) $reference )[0] );
}

/**
 * Slug for a watch row, without the /database/ prefix.
 *
 * The id stays on the end: name and reference are not guaranteed unique in
 * the source data, and keeping the id means a URL resolves with a primary-key
 * lookup instead of a scan over a computed column.
 */
function opus_watch_slug( $row ) {
	$name = sanitize_title( $row['name'] );
	if ( strlen( $name ) > 60 ) {
		$name = trim( substr( $name, 0, 60 ), '-' );
	}
	$parts = array_filter( array( $name, sanitize_title( opus_watch_ref_clean( $row['reference'] ) ) ) );

	return sanitize_title( $row['brand'] ) . '/' . implode( '-', $parts ) . '-' . (int) $row['id'];
}

/* Readable detail URL, and the brand-scoped database, share the same prefix;
   the brand rule matches one segment, this one matches two. */
add_action(
	'init',
	function () {
		add_rewrite_rule(
			'^database/(?!watch/)([a-z0-9-]+)/([a-z0-9-]+)/?$',
			'index.php?opus_watch_slug=$matches[2]',
			'top'
		);
	},
	11
);

add_filter(
	'query_vars',
	function ( $vars ) {
		$vars[] = 'opus_watch_slug';
		return $vars;
	}
);

/** The watch this request is for, from either the slug or the legacy id route. */
function opus_watch_current() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache ? $cache : null;
	}
	$cache = false;

	$id = (int) get_query_var( 'opus_watch_id' );
	if ( ! $id ) {
		$slug = (string) get_query_var( 'opus_watch_slug' );
		if ( $slug && preg_match( '/-(\d+)$/', $slug, $m ) ) {
			$id = (int) $m[1];
		}
	}
	if ( $id ) {
		$row = opus_watch_get( $id );
		if ( $row ) {
			$cache = $row;
		}
	}
	return $cache ? $cache : null;
}

/**
 * Send the legacy /database/watch/{id}/ URLs, and any near-miss slug, to the
 * canonical one. Both were live and linked, so these are permanent.
 */
add_action(
	'template_redirect',
	function () {
		$watch = opus_watch_current();
		if ( ! $watch ) {
			return;
		}
		$canonical = opus_watch_permalink( $watch );
		$current   = home_url( add_query_arg( array() ) );
		if ( untrailingslashit( strtok( $current, '?' ) ) !== untrailingslashit( $canonical ) ) {
			wp_safe_redirect( $canonical, 301 );
			exit;
		}
	},
	1
);

/* ─────────────────────────────────────────────────────────────
 * Metadata
 * ───────────────────────────────────────────────────────────── */

/**
 * "Tudor 79540-0001 — Heritage Black Bay 41 Black / Bracelet | Opus Watches"
 *
 * Brand and reference lead because the reference is what people search for,
 * and these names are long enough that anything after ~60 characters is
 * truncated in the results.
 */
function opus_watch_seo_title( $watch ) {
	$ref   = opus_watch_ref_clean( $watch['reference'] );
	$parts = array_filter( array( $watch['brand'], $ref ) );

	return trim( implode( ' ', $parts ) . ' — ' . $watch['name'] ) . ' | ' . get_bloginfo( 'name' );
}

/** A description built from whatever specs the record actually has. */
function opus_watch_seo_description( $watch ) {
	$facts = array();
	if ( ! empty( $watch['diameter_mm'] ) ) {
		$facts[] = rtrim( rtrim( number_format( (float) $watch['diameter_mm'], 2 ), '0' ), '.' ) . ' mm';
	}
	if ( ! empty( $watch['material'] ) ) {
		$facts[] = strtolower( $watch['material'] );
	}
	if ( ! empty( $watch['dial_color'] ) ) {
		$facts[] = strtolower( $watch['dial_color'] ) . ' wijzerplaat';
	}
	if ( ! empty( $watch['movement'] ) ) {
		$facts[] = 'uurwerk ' . $watch['movement'];
	}
	if ( ! empty( $watch['produced'] ) ) {
		$facts[] = 'productie ' . $watch['produced'];
	}

	$ref  = opus_watch_ref_clean( $watch['reference'] );
	$text = sprintf(
		/* translators: 1: brand and model, 2: reference, 3: comma-separated specifications. */
		__( 'Technische gegevens van de %1$s%2$s: %3$s. Referentie uit de horlogedatabase van Opus Watches.', 'maison-opus' ),
		trim( $watch['brand'] . ' ' . $watch['name'] ),
		$ref ? ', referentie ' . $ref : '',
		implode( ', ', $facts )
	);

	if ( mb_strlen( $text ) > 158 ) {
		$text = trim( mb_substr( $text, 0, 157 ) ) . '…';
	}
	return $text;
}

/**
 * Yoast describes these pages as the homepage because there is no post in the
 * query; these filters replace what it would emit. Each bails out unless the
 * request really is a watch page, so the rest of the site is untouched.
 */
function opus_watch_seo_filters() {
	$with_watch = function ( callable $build ) {
		return function ( $value ) use ( $build ) {
			$watch = opus_watch_current();
			return $watch ? $build( $watch ) : $value;
		};
	};

	$title = $with_watch( 'opus_watch_seo_title' );
	$desc  = $with_watch( 'opus_watch_seo_description' );
	$url   = $with_watch(
		function ( $watch ) {
			return opus_watch_permalink( $watch );
		}
	);

	foreach ( array( 'wpseo_title', 'wpseo_opengraph_title', 'wpseo_twitter_title', 'pre_get_document_title' ) as $hook ) {
		add_filter( $hook, $title, 20 );
	}
	foreach ( array( 'wpseo_metadesc', 'wpseo_opengraph_desc', 'wpseo_twitter_description' ) as $hook ) {
		add_filter( $hook, $desc, 20 );
	}
	foreach ( array( 'wpseo_canonical', 'wpseo_opengraph_url' ) as $hook ) {
		add_filter( $hook, $url, 20 );
	}

	add_filter(
		'wpseo_opengraph_type',
		function ( $type ) {
			return opus_watch_current() ? 'article' : $type;
		},
		20
	);

	// Yoast's own schema graph still describes the homepage here; ours below
	// replaces it rather than contradicting it.
	add_filter(
		'wpseo_json_ld_output',
		function ( $data ) {
			return opus_watch_current() ? false : $data;
		},
		20
	);
}
add_action( 'wp', 'opus_watch_seo_filters' );

/* ─────────────────────────────────────────────────────────────
 * Brand hubs — /database/{brand}/
 * ───────────────────────────────────────────────────────────── */

/** The brand this request is scoped to, or '' when it is not a brand hub. */
function opus_db_current_brand() {
	static $cache = null;
	if ( null === $cache ) {
		$cache = opus_db_brand_from_slug( (string) get_query_var( 'opus_db_brand' ) );
	}
	return $cache;
}

/** How many references the catalogue holds for one brand. */
function opus_db_brand_count( $brand ) {
	foreach ( opus_watch_facets()['brands'] as $b ) {
		if ( $b['value'] === $brand ) {
			return (int) $b['n'];
		}
	}
	return 0;
}

/**
 * These share the /database/ page, so Yoast gave all eight of them that
 * page's title and its canonical — which made them duplicates of each other.
 * They are the pages with real search intent ("Rolex referenties"), so they
 * get their own.
 */
add_action(
	'wp',
	function () {
		$brand = opus_db_current_brand();
		if ( ! $brand ) {
			return;
		}
		$count = opus_db_brand_count( $brand );
		$url   = home_url( '/database/' . sanitize_title( $brand ) . '/' );

		$title = sprintf(
			/* translators: 1: brand name, 2: number of references. */
			__( '%1$s referenties — %2$d horloges in de database', 'maison-opus' ),
			$brand,
			$count
		) . ' | ' . get_bloginfo( 'name' );

		$desc = sprintf(
			/* translators: 1: brand name, 2: number of references. */
			__( 'Alle %1$s-referenties in de horlogedatabase van Opus Watches: %2$d modellen met kastmaat, materiaal, uurwerk en productiejaren. Zoek op referentienummer.', 'maison-opus' ),
			$brand,
			$count
		);

		foreach ( array( 'wpseo_title', 'wpseo_opengraph_title', 'wpseo_twitter_title', 'pre_get_document_title' ) as $hook ) {
			add_filter( $hook, function () use ( $title ) { return $title; }, 20 );
		}
		foreach ( array( 'wpseo_metadesc', 'wpseo_opengraph_desc', 'wpseo_twitter_description' ) as $hook ) {
			add_filter( $hook, function () use ( $desc ) { return $desc; }, 20 );
		}
		foreach ( array( 'wpseo_canonical', 'wpseo_opengraph_url' ) as $hook ) {
			add_filter( $hook, function () use ( $url ) { return $url; }, 20 );
		}
	},
	9
);

/* ─────────────────────────────────────────────────────────────
 * Structured data
 * ───────────────────────────────────────────────────────────── */

/**
 * Product + BreadcrumbList for a reference page.
 *
 * Deliberately no `offers`: these are catalogue references, not stock. The
 * in-stock link on the page points at the WooCommerce product, which carries
 * its own Product schema with a price.
 */
function opus_watch_schema() {
	$watch = opus_watch_current();
	if ( ! $watch ) {
		return;
	}

	$db_page = get_page_by_path( 'database' );
	$db_url  = $db_page ? get_permalink( $db_page ) : home_url( '/database/' );
	$url     = opus_watch_permalink( $watch );

	$product = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Product',
		'name'        => trim( $watch['brand'] . ' ' . $watch['name'] ),
		'description' => opus_watch_seo_description( $watch ),
		'url'         => $url,
		'category'    => 'Horloges',
		'brand'       => array(
			'@type' => 'Brand',
			'name'  => $watch['brand'],
		),
	);

	$ref = opus_watch_ref_clean( $watch['reference'] );
	if ( $ref ) {
		$product['mpn'] = $ref;
	}
	if ( ! empty( $watch['family'] ) ) {
		$product['model'] = $watch['family'];
	}

	$properties = array();
	foreach ( array(
		'Kastmaat'  => empty( $watch['diameter_mm'] ) ? '' : rtrim( rtrim( number_format( (float) $watch['diameter_mm'], 2 ), '0' ), '.' ) . ' mm',
		'Materiaal' => $watch['material'],
		'Wijzerplaat' => $watch['dial_color'],
		'Uurwerk'   => $watch['movement'],
		'Productie' => $watch['produced'],
	) as $label => $value ) {
		if ( '' !== (string) $value ) {
			$properties[] = array(
				'@type' => 'PropertyValue',
				'name'  => $label,
				'value' => (string) $value,
			);
		}
	}
	if ( $properties ) {
		$product['additionalProperty'] = $properties;
	}

	$crumbs = array(
		array( 'name' => get_bloginfo( 'name' ), 'item' => home_url( '/' ) ),
		array( 'name' => __( 'Database', 'maison-opus' ), 'item' => $db_url ),
		array( 'name' => $watch['brand'], 'item' => home_url( '/database/' . sanitize_title( $watch['brand'] ) . '/' ) ),
		array( 'name' => $watch['name'], 'item' => $url ),
	);
	$items = array();
	foreach ( $crumbs as $i => $crumb ) {
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => $crumb['name'],
			'item'     => $crumb['item'],
		);
	}

	$breadcrumbs = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $items,
	);

	foreach ( array( $product, $breadcrumbs ) as $block ) {
		echo '<script type="application/ld+json">'
			. wp_json_encode( $block, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
			. '</script>' . "\n";
	}
}
add_action( 'wp_head', 'opus_watch_schema', 20 );

/**
 * Product schema for the shop itself.
 *
 * Yoast only emits Product markup with its paid WooCommerce add-on, so the
 * search results carried no price or availability. Offers use the gross price
 * because the shop is configured tax-inclusive.
 *
 * "Prijs op aanvraag" watches have no price, and get NO offer: an Offer
 * needs a price, and WooCommerce turns the empty price into 0 — which told
 * Google every watch cost € 0. They keep the descriptive Product data
 * (brand, reference, model, condition, photos); the offer appears by itself
 * once a price is entered.
 */
function opus_watch_product_schema() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	$product = wc_get_product( get_the_ID() );
	if ( ! $product ) {
		return;
	}
	$id = $product->get_id();

	$data = array(
		'@context'      => 'https://schema.org',
		'@type'         => 'Product',
		'@id'           => get_permalink( $id ) . '#product',
		'name'          => $product->get_name(),
		'url'           => get_permalink( $id ),
		'description'   => wp_strip_all_tags( $product->get_short_description() ? $product->get_short_description() : $product->get_description() ),
		'sku'           => $product->get_sku(),
		// Every watch here is pre-owned; "ongedragen" is still second hand.
		'itemCondition' => 'https://schema.org/UsedCondition',
	);

	$reference = trim( (string) get_post_meta( $id, '_opus_reference', true ) );
	if ( '' !== $reference ) {
		$data['mpn'] = $reference;
	}

	$images = array_filter(
		array_map(
			fn( $img ) => wp_get_attachment_image_url( $img, 'full' ),
			array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() )
		)
	);
	if ( $images ) {
		$data['image'] = array_values( array_unique( $images ) );
	}

	$brands = wp_get_post_terms( $id, 'product_brand', array( 'fields' => 'names' ) );
	if ( ! is_wp_error( $brands ) && $brands ) {
		$data['brand'] = array(
			'@type' => 'Brand',
			'name'  => $brands[0],
		);
	}
	$models = wp_get_post_terms( $id, 'watch_model', array( 'fields' => 'names' ) );
	if ( ! is_wp_error( $models ) && $models ) {
		$data['model'] = $models[0];
	}
	$material = wc_get_product_terms( $id, 'pa_kastmateriaal', array( 'fields' => 'names' ) );
	if ( $material ) {
		$data['material'] = $material[0];
	}

	if ( '' !== (string) $product->get_price() ) {
		$data['offers'] = array(
			'@type'         => 'Offer',
			'url'           => get_permalink( $id ),
			'price'         => wc_format_decimal( wc_get_price_including_tax( $product ), 2 ),
			'priceCurrency' => get_woocommerce_currency(),
			// One of a kind: once sold it is gone, not temporarily out of stock.
			'availability'  => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut',
			'itemCondition' => 'https://schema.org/UsedCondition',
			'seller'        => array(
				'@type' => 'Organization',
				'@id'   => opus_business_schema_id(), // the business entity (inc/seo-local.php)
				'name'  => opus_nap()['name'],
				'url'   => home_url( '/' ),
			),
		);
	}

	echo '<script type="application/ld+json">'
		. wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
		. '</script>' . "\n";
}
add_action( 'wp_head', 'opus_watch_product_schema', 20 );

/* ─────────────────────────────────────────────────────────────
 * Sitemap — /sitemap-watches.xml, linked from the Yoast index
 *
 * Named this way round on purpose: Yoast claims every `*-sitemap.xml` URL for
 * its own providers, so `watches-sitemap.xml` would 404 as an unknown Yoast
 * sitemap type rather than reach us.
 * ───────────────────────────────────────────────────────────── */

add_action(
	'init',
	function () {
		add_rewrite_rule( '^sitemap-watches\.xml$', 'index.php?opus_watch_sitemap=1', 'top' );
	},
	11
);

add_filter(
	'query_vars',
	function ( $vars ) {
		$vars[] = 'opus_watch_sitemap';
		return $vars;
	}
);

add_action(
	'template_redirect',
	function () {
		if ( ! get_query_var( 'opus_watch_sitemap' ) ) {
			return;
		}
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT id, brand, name, reference FROM ' . opus_watch_table(), ARRAY_A ); // phpcs:ignore WordPress.DB

		header( 'Content-Type: application/xml; charset=UTF-8' );
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		foreach ( opus_watch_facets()['brands'] as $b ) {
			echo "\t<url><loc>" . esc_url( home_url( '/database/' . sanitize_title( $b['value'] ) . '/' ) ) . "</loc><changefreq>weekly</changefreq><priority>0.7</priority></url>\n";
		}
		foreach ( $rows as $row ) {
			echo "\t<url><loc>" . esc_url( opus_watch_permalink( $row ) ) . "</loc><changefreq>monthly</changefreq><priority>0.5</priority></url>\n";
		}
		echo '</urlset>';
		exit;
	},
	0
);

/** Announce it inside Yoast's sitemap index so one submission covers everything. */
add_filter(
	'wpseo_sitemap_index',
	function ( $index ) {
		return $index . sprintf(
			"<sitemap><loc>%s</loc><lastmod>%s</lastmod></sitemap>\n",
			esc_url( home_url( '/sitemap-watches.xml' ) ),
			esc_html( gmdate( 'c' ) )
		);
	}
);
