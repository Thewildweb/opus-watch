<?php
/**
 * Shop filters — type / merk / bouwjaar / prijs on brand archives (and the shop).
 *
 * Watch type lives in the `watch_type` taxonomy (seeded + backfilled by
 * scripts/backfill-watch-meta.php, editable per product in wp-admin).
 * The build year lives in `_opus_year` meta, extracted from the imported
 * spec table by the same script.
 *
 * @package maison-opus
 */

defined( 'ABSPATH' ) || exit;

/** Watch type taxonomy — Sport & tool / Dress / Dames. */
add_action(
	'init',
	function () {
		register_taxonomy(
			'watch_type',
			'product',
			array(
				'labels'            => array(
					'name'          => 'Type horloge',
					'singular_name' => 'Type horloge',
					'menu_name'     => 'Type horloge',
				),
				'public'            => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'hierarchical'      => true,
				'rewrite'           => false,
			)
		);

		/* Model family (Datejust, Daytona, Submariner, …) — drives the Model dropdown. */
		register_taxonomy(
			'watch_model',
			'product',
			array(
				'labels'            => array(
					'name'          => 'Model',
					'singular_name' => 'Model',
					'menu_name'     => 'Model',
				),
				'public'            => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'hierarchical'      => true,
				'rewrite'           => false,
			)
		);
	}
);

/**
 * Keep the products list readable: Yoast's five columns squeezed the table
 * until headers rendered vertically. SEO editing stays available on the
 * product itself; the list keeps the columns a shop owner actually scans.
 */
add_filter(
	'manage_edit-product_columns',
	function ( $columns ) {
		foreach ( array( 'wpseo-score', 'wpseo-score-readability', 'wpseo-title', 'wpseo-metadesc', 'wpseo-focuskw', 'wpseo-links', 'wpseo-linked' ) as $key ) {
			unset( $columns[ $key ] );
		}
		return $columns;
	},
	20
);

/** The price buckets (key => label). Keys are "min-max" in euros. */
function opus_price_ranges() {
	return array(
		'0-5000'       => __( '< € 5.000', 'maison-opus' ),
		'5000-10000'   => __( '€ 5.000 – € 10.000', 'maison-opus' ),
		'10000-25000'  => __( '€ 10.000 – € 25.000', 'maison-opus' ),
		'25000-100000' => __( '€ 25.000 – € 100.000', 'maison-opus' ),
	);
}

/**
 * Attribute filters shown in the sidebar: query param => [taxonomy, label].
 * (pa_bouwjaar exists too but the year-range filter below covers it; doos/
 * papieren are near-uniform "Ja" and not worth a filter.)
 */
function opus_attribute_filters() {
	return array(
		'staat'         => array( 'pa_staat', __( 'Staat', 'maison-opus' ) ),
		'kastmaat'      => array( 'pa_kastmaat', __( 'Kastmaat', 'maison-opus' ) ),
		'kastmateriaal' => array( 'pa_kastmateriaal', __( 'Kastmateriaal', 'maison-opus' ) ),
		'wijzerplaat'   => array( 'pa_wijzerplaat', __( 'Wijzerplaat', 'maison-opus' ) ),
	);
}

/*
 * ── Facets: only offer options that return watches ─────────────────────
 *
 * Every filter group shows an option only when at least one in-stock watch
 * would match it — within the current archive (brand / category / shop) and
 * combined with the OTHER active filter groups (a group's own selection must
 * not narrow its own options, or picking "Staal" would hide "Goud"). Counts
 * come from the same sets. Options the visitor already ticked always stay,
 * so they can be unticked.
 *
 * Products are few (dozens), so this works on id sets in PHP: one query for
 * the archive's in-stock watches, one per active filter group, intersected.
 */

/** In-stock watches of the current archive, in the current language. */
function opus_facet_base_ids() {
	static $ids = null;
	if ( null !== $ids ) {
		return $ids;
	}
	$tax_query = array(
		array(
			'taxonomy' => 'product_cat',
			'field'    => 'slug',
			'terms'    => array( 'service', 'servicing' ),
			'operator' => 'NOT IN',
		),
	);
	if ( is_tax( 'product_brand' ) || ( function_exists( 'is_product_category' ) && is_product_category() ) ) {
		$term        = get_queried_object();
		$tax_query[] = array(
			'taxonomy' => $term->taxonomy,
			'field'    => 'term_id',
			'terms'    => array( (int) $term->term_id ),
		);
	}
	$q   = new WP_Query(
		array(
			'post_type'        => 'product',
			'post_status'      => 'publish',
			'posts_per_page'   => -1,
			'fields'           => 'ids',
			'no_found_rows'    => true,
			'ep_integrate'     => false, // plain SQL; ElasticPress serves the listing itself
			'suppress_filters' => false, // keep WPML's current-language filter
			'tax_query'        => $tax_query,
			'meta_query'       => array(
				array(
					'key'   => '_stock_status',
					'value' => 'instock',
				),
			),
		)
	);
	return $ids = array_map( 'intval', $q->posts );
}

/** Of the base ids, those matching one active filter group (null = group inactive). */
function opus_facet_group_ids( $group ) {
	static $cache = array();
	if ( array_key_exists( $group, $cache ) ) {
		return $cache[ $group ];
	}
	$f    = opus_active_filters();
	$base = opus_facet_base_ids();
	$args = null;

	if ( 'type' === $group && $f['type'] ) {
		$args = array( 'tax_query' => array( array( 'taxonomy' => 'watch_type', 'field' => 'slug', 'terms' => $f['type'] ) ) );
	} elseif ( 'model' === $group && $f['model'] ) {
		$args = array( 'tax_query' => array( array( 'taxonomy' => 'watch_model', 'field' => 'slug', 'terms' => array( $f['model'] ) ) ) );
	} elseif ( 'prijs' === $group && $f['prijs'] ) {
		$price_q = array( 'relation' => 'OR' );
		foreach ( $f['prijs'] as $key ) {
			list( $min, $max ) = array_map( 'intval', explode( '-', $key ) );
			$price_q[]         = array( 'key' => '_price', 'value' => array( $min, $max ), 'compare' => 'BETWEEN', 'type' => 'NUMERIC' );
		}
		$args = array( 'meta_query' => array( $price_q ) );
	} elseif ( 'jaar' === $group && ( $f['jaar_van'] || $f['jaar_tot'] ) ) {
		$args = array(
			'meta_query' => array(
				array(
					'key'     => '_opus_year',
					'value'   => array( $f['jaar_van'] ? $f['jaar_van'] : 1900, $f['jaar_tot'] ? $f['jaar_tot'] : 2100 ),
					'compare' => 'BETWEEN',
					'type'    => 'NUMERIC',
				),
			),
		);
	} elseif ( isset( opus_attribute_filters()[ $group ] ) && $f[ $group ] ) {
		$args = array( 'tax_query' => array( array( 'taxonomy' => opus_attribute_filters()[ $group ][0], 'field' => 'slug', 'terms' => $f[ $group ] ) ) );
	}

	if ( null === $args || ! $base ) {
		return $cache[ $group ] = ( null === $args ? null : array() );
	}
	$q = new WP_Query(
		array_merge(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'ep_integrate'   => false,
				'post__in'       => $base,
			),
			$args
		)
	);
	return $cache[ $group ] = array_map( 'intval', $q->posts );
}

/** Watches the options of $group are counted against: base ∩ every OTHER active group. */
function opus_facet_ids( $group ) {
	$ids = opus_facet_base_ids();
	foreach ( array_merge( array( 'type', 'model', 'prijs', 'jaar' ), array_keys( opus_attribute_filters() ) ) as $other ) {
		if ( $other === $group ) {
			continue;
		}
		$match = opus_facet_group_ids( $other );
		if ( null !== $match ) {
			$ids = array_values( array_intersect( $ids, $match ) );
		}
	}
	return $ids;
}

/**
 * Terms of $taxonomy that occur among $ids, with their count among them,
 * plus any $selected slugs (count 0) so a ticked option never disappears.
 *
 * @return array of [ 'term' => WP_Term, 'count' => int ]
 */
function opus_facet_terms( $taxonomy, $ids, $selected = array() ) {
	global $wpdb;
	$counts = array();
	if ( $ids ) {
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT tt.term_id, COUNT(DISTINCT tr.object_id) AS n
			 FROM {$wpdb->term_relationships} tr
			 JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
			 WHERE tt.taxonomy = %s AND tr.object_id IN (" . implode( ',', array_map( 'intval', $ids ) ) . ')
			 GROUP BY tt.term_id',
			$taxonomy
		), ARRAY_A );
		foreach ( $rows as $r ) {
			$counts[ (int) $r['term_id'] ] = (int) $r['n'];
		}
	}
	$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
	$out   = array();
	foreach ( is_wp_error( $terms ) ? array() : $terms as $t ) {
		if ( isset( $counts[ $t->term_id ] ) || in_array( $t->slug, (array) $selected, true ) ) {
			$out[] = array( 'term' => $t, 'count' => $counts[ $t->term_id ] ?? 0 );
		}
	}
	return $out;
}

/** Build years present among the watches the year filter is counted against (desc). */
function opus_facet_years() {
	global $wpdb;
	$ids = opus_facet_ids( 'jaar' );
	if ( ! $ids ) {
		return array();
	}
	return $wpdb->get_col(
		"SELECT DISTINCT meta_value FROM {$wpdb->postmeta}
		 WHERE meta_key = '_opus_year' AND meta_value REGEXP '^[0-9]{4}$'
		   AND post_id IN (" . implode( ',', array_map( 'intval', $ids ) ) . ')
		 ORDER BY meta_value DESC'
	);
}

/** Price buckets with at least one watch (plus ticked ones). */
function opus_facet_prices() {
	global $wpdb;
	$ids      = opus_facet_ids( 'prijs' );
	$prices   = $ids ? array_map( 'floatval', $wpdb->get_col(
		"SELECT meta_value FROM {$wpdb->postmeta}
		 WHERE meta_key = '_price' AND meta_value <> '' AND post_id IN (" . implode( ',', array_map( 'intval', $ids ) ) . ')'
	) ) : array();
	$selected = opus_active_filters()['prijs'];
	$out      = array();
	foreach ( opus_price_ranges() as $key => $label ) {
		list( $min, $max ) = array_map( 'intval', explode( '-', $key ) );
		$hit = array_filter( $prices, fn( $p ) => $p >= $min && $p <= $max );
		if ( $hit || in_array( $key, $selected, true ) ) {
			$out[ $key ] = $label;
		}
	}
	return $out;
}

/** Selected filters from the query string, sanitized. */
function opus_active_filters() {
	$types = isset( $_GET['type'] ) ? array_map( 'sanitize_key', (array) $_GET['type'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification
	$model = isset( $_GET['model'] ) ? sanitize_key( $_GET['model'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$prijs = isset( $_GET['prijs'] ) ? array_map( 'sanitize_key', (array) $_GET['prijs'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification
	$prijs = array_values( array_intersect( $prijs, array_keys( opus_price_ranges() ) ) );
	$van   = isset( $_GET['jaar_van'] ) ? absint( $_GET['jaar_van'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
	$tot   = isset( $_GET['jaar_tot'] ) ? absint( $_GET['jaar_tot'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification

	$filters = array(
		'type'     => $types,
		'model'    => $model,
		'prijs'    => $prijs,
		'jaar_van' => $van,
		'jaar_tot' => $tot,
	);

	foreach ( array_keys( opus_attribute_filters() ) as $param ) {
		$filters[ $param ] = isset( $_GET[ $param ] ) ? array_map( 'sanitize_key', (array) $_GET[ $param ] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification
	}

	return $filters;
}

/** Do the current query args carry any filter? */
function opus_filters_are_active() {
	$f = opus_active_filters();
	if ( $f['type'] || $f['model'] || $f['prijs'] || $f['jaar_van'] || $f['jaar_tot'] ) {
		return true;
	}
	foreach ( array_keys( opus_attribute_filters() ) as $param ) {
		if ( $f[ $param ] ) {
			return true;
		}
	}
	return false;
}

/** Apply the filters to the main product query on brand archives + shop. */
add_action(
	'woocommerce_product_query',
	function ( $query ) {
		if ( is_admin() || ! opus_filters_apply_here() ) {
			return;
		}
		$f = opus_active_filters();

		$tax_query = (array) $query->get( 'tax_query' );
		if ( $f['type'] ) {
			$tax_query[] = array(
				'taxonomy' => 'watch_type',
				'field'    => 'slug',
				'terms'    => $f['type'],
			);
		}
		if ( $f['model'] ) {
			$tax_query[] = array(
				'taxonomy' => 'watch_model',
				'field'    => 'slug',
				'terms'    => array( $f['model'] ),
			);
		}
		$attr_active = false;
		foreach ( opus_attribute_filters() as $param => $conf ) {
			if ( $f[ $param ] ) {
				$tax_query[] = array(
					'taxonomy' => $conf[0],
					'field'    => 'slug',
					'terms'    => $f[ $param ],
				);
				$attr_active = true;
			}
		}

		if ( $f['type'] || $f['model'] || $attr_active ) {
			$query->set( 'tax_query', $tax_query );
		}

		$meta_query = (array) $query->get( 'meta_query' );

		if ( $f['prijs'] ) {
			$price_q = array( 'relation' => 'OR' );
			foreach ( $f['prijs'] as $key ) {
				list( $min, $max ) = array_map( 'intval', explode( '-', $key ) );
				$price_q[]         = array(
					'key'     => '_price',
					'value'   => array( $min, $max ),
					'compare' => 'BETWEEN',
					'type'    => 'NUMERIC',
				);
			}
			$meta_query[] = $price_q;
		}

		if ( $f['jaar_van'] || $f['jaar_tot'] ) {
			$van          = $f['jaar_van'] ? $f['jaar_van'] : 1900;
			$tot          = $f['jaar_tot'] ? $f['jaar_tot'] : 2100;
			$meta_query[] = array(
				'key'     => '_opus_year',
				'value'   => array( $van, $tot ),
				'compare' => 'BETWEEN',
				'type'    => 'NUMERIC',
			);
		}

		if ( $f['prijs'] || $f['jaar_van'] || $f['jaar_tot'] ) {
			$query->set( 'meta_query', $meta_query );
		}
	},
	20
);

/** Breadcrumb on brand archives: Home / Shop / <brand> instead of Home / <brand>. */
add_filter(
	'woocommerce_get_breadcrumb',
	function ( $crumbs ) {
		if ( is_tax( 'product_brand' ) && function_exists( 'wc_get_page_permalink' ) && count( $crumbs ) >= 2 ) {
			array_splice( $crumbs, 1, 0, array( array( get_the_title( wc_get_page_id( 'shop' ) ), wc_get_page_permalink( 'shop' ) ) ) );
		}
		// Product: Home / Horloges / <brand> / <product>. Brands are a
		// taxonomy of their own, not a subcategory, so Woo leaves them out.
		if ( is_product() && count( $crumbs ) >= 2 ) {
			$brands = wp_get_post_terms( get_queried_object_id(), 'product_brand' );
			$link   = ( ! is_wp_error( $brands ) && $brands ) ? get_term_link( $brands[0] ) : '';
			if ( $link && ! is_wp_error( $link ) ) {
				// Brands aren't translated, so get_term_link() is the Dutch URL; move it to the page's language.
				$link = apply_filters( 'wpml_permalink', $link, apply_filters( 'wpml_current_language', null ) );
				array_splice( $crumbs, count( $crumbs ) - 1, 0, array( array( $brands[0]->name, $link ) ) );
			}
		}
		return $crumbs;
	}
);

/**
 * Yoast's breadcrumb (it feeds the BreadcrumbList schema Google reads) =
 * WooCommerce's visible one on shop pages. Yoast built its own trail, which
 * left out the brand and linked "Winkel" to the homepage (a stale indexable
 * from when the shop was briefly set as the front page).
 */
add_filter(
	'wpseo_breadcrumb_links',
	function ( $links ) {
		if ( ! function_exists( 'is_woocommerce' ) || ! is_woocommerce() || ! class_exists( 'WC_Breadcrumb' ) ) {
			return $links;
		}
		$wc = new WC_Breadcrumb();
		$wc->add_crumb( _x( 'Home', 'breadcrumb', 'woocommerce' ), apply_filters( 'woocommerce_breadcrumb_home_url', home_url( '/' ) ) );
		$crumbs = $wc->generate(); // runs woocommerce_get_breadcrumb, so it includes the changes above
		if ( count( $crumbs ) < 2 ) {
			return $links;
		}
		$current = home_url( add_query_arg( array() ) );
		return array_map(
			fn( $c ) => array(
				'text' => wp_strip_all_tags( $c[0] ),
				'url'  => $c[1] ? $c[1] : $current,
			),
			$crumbs
		);
	}
);

/**
 * Render the filter sidebar. assets/js/shop-filters.js applies changes in
 * place (fetch + pushState); without JS the "Toepassen" button submits. The
 * "Merk" block is navigation between brand archives and carries the active
 * filters along.
 */
/**
 * Per-term product counts for the CURRENT language. Raw term counts include
 * every language's product twin (Rolex showed 136 instead of 68).
 *
 * @param int[] $term_ids
 * @return array term_id => count
 */
function opus_term_counts_current_lang( $term_ids ) {
	global $wpdb;
	$term_ids = array_filter( array_map( 'intval', (array) $term_ids ) );
	if ( ! $term_ids ) {
		return array();
	}
	$lang = apply_filters( 'wpml_current_language', null );
	if ( ! $lang ) {
		return array();
	}
	$in   = implode( ',', $term_ids );
	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT tt.term_id, COUNT(*) AS n
		 FROM {$wpdb->term_relationships} tr
		 JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
		 JOIN {$wpdb->prefix}icl_translations t ON t.element_id = tr.object_id AND t.element_type = 'post_product' AND t.language_code = %s
		 JOIN {$wpdb->posts} p ON p.ID = tr.object_id AND p.post_status = 'publish'
		 WHERE tt.term_id IN ({$in})
		 GROUP BY tt.term_id",
		$lang
	), ARRAY_A );
	$out = array();
	foreach ( $rows as $r ) {
		$out[ (int) $r['term_id'] ] = (int) $r['n'];
	}
	return $out;
}

/** Archives that carry the filter UI: shop, watch categories, brand pages. */
function opus_filters_apply_here() {
	if ( is_tax( 'product_brand' ) || ( function_exists( 'is_shop' ) && is_shop() ) ) {
		return true;
	}
	// Watch categories only — the service category has its own template.
	return function_exists( 'is_product_category' )
		&& is_product_category()
		&& ! is_product_category( array( 'service', 'servicing' ) );
}

/**
 * "Filters" toggle + (on non-brand archives) the filter panel itself.
 * Brand pages already render the sidebar inline; there the toggle only
 * appears on small screens, where CSS turns that same sidebar into the
 * modal. On shop/category archives the panel is modal-only at any width.
 */
add_action(
	'woocommerce_before_shop_loop',
	function () {
		if ( ! opus_filters_apply_here() ) {
			return;
		}
		$brand_page = is_tax( 'product_brand' );
		opus_filters_toggle( $brand_page );
		if ( ! $brand_page ) {
			echo '<div class="opus-filters-host">';
			opus_shop_filters();
			echo '</div>';
		}
	},
	5
);

/**
 * The "Filters" button that opens the panel. On brand pages the panel is an
 * inline sidebar on desktop, so the button only shows on small screens; the
 * brand template calls this itself (it doesn't run woocommerce_before_shop_loop).
 */
function opus_filters_toggle( $mobile_only = false ) {
	?>
	<button type="button"
		class="opus-filters-toggle<?php echo $mobile_only ? ' opus-filters-toggle--mobile-only' : ''; ?>"
		data-opus-filters-open aria-expanded="false">
		<?php esc_html_e( 'Filters', 'maison-opus' ); ?><?php if ( opus_filters_are_active() ) : ?><span class="opus-filters-toggle__dot" aria-hidden="true"></span><?php endif; ?>
	</button>
	<?php
}

function opus_shop_filters() {
	$f       = opus_active_filters();
	$current = is_tax( 'product_brand' ) ? get_queried_object() : null;
	$action  = opus_filters_base_url();

	// Only options that return watches here (see "Facets" above).
	$types  = opus_facet_terms( 'watch_type', opus_facet_ids( 'type' ), $f['type'] );
	$models = opus_facet_terms( 'watch_model', opus_facet_ids( 'model' ), $f['model'] ? array( $f['model'] ) : array() );
	$brands = $current ? array() : opus_facet_terms( 'product_brand', opus_facet_ids( 'brand' ) );
	$years  = opus_facet_years();
	$prices = opus_facet_prices();

	// Filter args to carry along when switching brand.
	$carry = array(
		'type'     => $f['type'],
		'model'    => $f['model'],
		'prijs'    => $f['prijs'],
		'jaar_van' => $f['jaar_van'],
		'jaar_tot' => $f['jaar_tot'],
	);
	foreach ( array_keys( opus_attribute_filters() ) as $param ) {
		$carry[ $param ] = $f[ $param ];
	}
	$carry = array_filter( $carry );
	?>
	<aside class="shop-filters">
		<div class="shop-filters__head">
			<span class="shop-filters__title"><?php esc_html_e( 'Filters', 'maison-opus' ); ?></span>
			<button type="button" class="shop-filters__close" aria-label="<?php esc_attr_e( 'Sluiten', 'maison-opus' ); ?>" data-opus-filters-close>&times;</button>
		</div>
		<form class="shop-filters__form" method="get" action="<?php echo esc_url( $action ); ?>">

			<?php if ( $types ) : ?>
				<fieldset class="filter-group">
					<legend class="filter-group__title"><?php esc_html_e( 'Type horloge', 'maison-opus' ); ?></legend>
					<?php foreach ( wp_list_pluck( $types, 'term' ) as $t ) : ?>
						<label class="filter-check">
							<input type="checkbox" name="type[]" value="<?php echo esc_attr( $t->slug ); ?>" <?php checked( in_array( $t->slug, $f['type'], true ) ); ?> />
							<span><?php echo esc_html( $t->name ); ?></span>
						</label>
					<?php endforeach; ?>
				</fieldset>
			<?php endif; ?>

			<?php if ( $models ) : ?>
				<div class="filter-group">
					<label class="filter-group__title" for="opus-filter-model"><?php esc_html_e( 'Model', 'maison-opus' ); ?></label>
					<select id="opus-filter-model" class="filter-model" name="model">
						<option value=""><?php esc_html_e( 'Alle modellen', 'maison-opus' ); ?></option>
						<?php foreach ( $models as $opus_m ) : ?>
							<option value="<?php echo esc_attr( $opus_m['term']->slug ); ?>" <?php selected( $opus_m['term']->slug, $f['model'] ); ?>>
								<?php echo esc_html( $opus_m['term']->name ); ?> (<?php echo (int) $opus_m['count']; ?>)
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			<?php endif; ?>

	<?php if ( $brands && ! $current ) : /* brand pages are already brand-scoped — no Merk block there */ ?>
				<div class="filter-group">
					<p class="filter-group__title"><?php esc_html_e( 'Merk', 'maison-opus' ); ?></p>
					<?php
					foreach ( $brands as $opus_b ) :
						$b    = $opus_b['term'];
						$link = get_term_link( $b );
						if ( is_wp_error( $link ) ) {
							continue;
						}
						$link = $carry ? add_query_arg( $carry, $link ) : $link;
						?>
						<a class="filter-brand" href="<?php echo esc_url( $link ); ?>">
							<?php echo esc_html( $b->name ); ?> <span><?php echo (int) $opus_b['count']; ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php
			foreach ( array( $f['jaar_van'], $f['jaar_tot'] ) as $opus_y ) {
				if ( $opus_y && ! in_array( (string) $opus_y, $years, true ) ) {
					$years[] = (string) $opus_y;
				}
			}
			rsort( $years );
			?>
			<?php if ( $years ) : ?>
				<fieldset class="filter-group">
					<legend class="filter-group__title"><?php esc_html_e( 'Bouwjaar', 'maison-opus' ); ?></legend>
					<div class="filter-years">
						<select name="jaar_van" aria-label="<?php esc_attr_e( 'Bouwjaar vanaf', 'maison-opus' ); ?>">
							<option value=""><?php esc_html_e( 'Van', 'maison-opus' ); ?></option>
							<?php foreach ( array_reverse( $years ) as $y ) : ?>
								<option value="<?php echo esc_attr( $y ); ?>" <?php selected( (int) $y, $f['jaar_van'] ); ?>><?php echo esc_html( $y ); ?></option>
							<?php endforeach; ?>
						</select>
						<span aria-hidden="true">—</span>
						<select name="jaar_tot" aria-label="<?php esc_attr_e( 'Bouwjaar tot en met', 'maison-opus' ); ?>">
							<option value=""><?php esc_html_e( 'Tot', 'maison-opus' ); ?></option>
							<?php foreach ( $years as $y ) : ?>
								<option value="<?php echo esc_attr( $y ); ?>" <?php selected( (int) $y, $f['jaar_tot'] ); ?>><?php echo esc_html( $y ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</fieldset>
			<?php endif; ?>

			<?php foreach ( opus_attribute_filters() as $opus_param => $opus_conf ) : ?>
				<?php
				$opus_attr_terms = wp_list_pluck( opus_facet_terms( $opus_conf[0], opus_facet_ids( $opus_param ), $f[ $opus_param ] ), 'term' );
				if ( ! $opus_attr_terms ) {
					continue;
				}
				?>
				<fieldset class="filter-group">
					<legend class="filter-group__title"><?php echo esc_html( $opus_conf[1] ); ?></legend>
					<?php foreach ( $opus_attr_terms as $opus_term ) : ?>
						<label class="filter-check">
							<input type="checkbox" name="<?php echo esc_attr( $opus_param ); ?>[]" value="<?php echo esc_attr( $opus_term->slug ); ?>" <?php checked( in_array( $opus_term->slug, $f[ $opus_param ], true ) ); ?> />
							<span><?php echo esc_html( $opus_term->name ); ?></span>
						</label>
					<?php endforeach; ?>
				</fieldset>
			<?php endforeach; ?>

			<?php if ( $prices ) : ?>
			<fieldset class="filter-group">
				<legend class="filter-group__title"><?php esc_html_e( 'Prijs', 'maison-opus' ); ?></legend>
				<?php foreach ( $prices as $key => $label ) : ?>
					<label class="filter-check">
						<input type="checkbox" name="prijs[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $f['prijs'], true ) ); ?> />
						<span><?php echo esc_html( $label ); ?></span>
					</label>
				<?php endforeach; ?>
			</fieldset>
			<?php endif; ?>

			<div class="filter-actions">
				<button class="btn solid" type="submit"><?php esc_html_e( 'Toepassen', 'maison-opus' ); ?></button>
				<?php if ( opus_filters_are_active() ) : ?>
					<a class="filter-reset" href="<?php echo esc_url( $action ); ?>"><?php esc_html_e( 'Wis filters', 'maison-opus' ); ?></a>
				<?php endif; ?>
			</div>
		</form>
	</aside>
	<?php
}

/**
 * The archive being viewed, without filters or page number — what the filter
 * form submits to and "Wis filters" links to. Built from the request path so
 * the language prefix (/en/) survives: get_term_link() on a brand returns the
 * Dutch URL even on the English page (brands are not translated).
 */
function opus_filters_base_url() {
	$path = wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/', PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- path only, rebuilt below.
	$path = preg_replace( '#/page/\d+/?$#', '/', (string) $path );
	// Scheme + host from the raw option: WPML filters home_url() and would
	// prefix /en/ a second time.
	$home = wp_parse_url( get_option( 'home' ) );
	$base = $home['scheme'] . '://' . $home['host'] . ( isset( $home['port'] ) ? ':' . $home['port'] : '' );
	return $base . user_trailingslashit( '/' . trim( $path, '/' ) );
}

/** In-place filtering (and the filters panel toggle) on filter archives. */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! opus_filters_apply_here() ) {
			return;
		}
		$rel = '/assets/js/shop-filters.js';
		wp_enqueue_script( 'opus-shop-filters', get_template_directory_uri() . $rel, array(), maison_opus_asset_ver( $rel ), array( 'strategy' => 'defer', 'in_footer' => true ) );
	}
);
