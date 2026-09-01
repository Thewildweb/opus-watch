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

/**
 * Product ids matching every active filter EXCEPT the given attribute group
 * (a group's own selection must not narrow its own options). Returns null
 * when no other filter is active — i.e. no extra restriction.
 */
function opus_filtered_product_ids( $exclude_param ) {
	static $cache = array();
	if ( array_key_exists( $exclude_param, $cache ) ) {
		return $cache[ $exclude_param ];
	}
	$f         = opus_active_filters();
	$tax_query = array();
	$meta_query = array();
	$any       = false;

	if ( $f['type'] ) {
		$tax_query[] = array( 'taxonomy' => 'watch_type', 'field' => 'slug', 'terms' => $f['type'] );
		$any         = true;
	}
	if ( $f['model'] ) {
		$tax_query[] = array( 'taxonomy' => 'watch_model', 'field' => 'slug', 'terms' => array( $f['model'] ) );
		$any         = true;
	}
	foreach ( opus_attribute_filters() as $param => $conf ) {
		if ( $param === $exclude_param || ! $f[ $param ] ) {
			continue;
		}
		$tax_query[] = array( 'taxonomy' => $conf[0], 'field' => 'slug', 'terms' => $f[ $param ] );
		$any         = true;
	}
	if ( $f['prijs'] ) {
		$price_q = array( 'relation' => 'OR' );
		foreach ( $f['prijs'] as $key ) {
			list( $min, $max ) = array_map( 'intval', explode( '-', $key ) );
			$price_q[]         = array( 'key' => '_price', 'value' => array( $min, $max ), 'compare' => 'BETWEEN', 'type' => 'NUMERIC' );
		}
		$meta_query[] = $price_q;
		$any          = true;
	}
	if ( $f['jaar_van'] || $f['jaar_tot'] ) {
		$meta_query[] = array(
			'key'     => '_opus_year',
			'value'   => array( $f['jaar_van'] ? $f['jaar_van'] : 1900, $f['jaar_tot'] ? $f['jaar_tot'] : 2100 ),
			'compare' => 'BETWEEN',
			'type'    => 'NUMERIC',
		);
		$any = true;
	}

	if ( ! $any ) {
		return $cache[ $exclude_param ] = null;
	}
	$q = new WP_Query(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'tax_query'      => $tax_query,
			'meta_query'     => $meta_query,
		)
	);
	return $cache[ $exclude_param ] = array_map( 'intval', $q->posts );
}

/**
 * Terms of an attribute taxonomy with at least one product IN STOCK — via
 * WooCommerce's attribute lookup table, so a sold watch drops out of the
 * filter options immediately. On a brand archive the options are scoped to
 * that brand's stock, so only choices that can return results are offered.
 */
function opus_attribute_terms_in_stock( $taxonomy, $param = '' ) {
	global $wpdb;
	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
		)
	);
	if ( is_wp_error( $terms ) || ! $terms ) {
		return array();
	}

	$brand_join = '';
	if ( is_tax( 'product_brand' ) ) {
		$brand      = get_queried_object();
		$brand_join = $wpdb->prepare(
			" INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = l.product_id AND tr.term_taxonomy_id = %d",
			$brand->term_taxonomy_id
		);
	}

	// Facet: restrict to products matching the OTHER active filters.
	$product_ctx = '';
	$ctx_ids     = $param ? opus_filtered_product_ids( $param ) : null;
	if ( is_array( $ctx_ids ) ) {
		$product_ctx = $ctx_ids
			? ' AND l.product_id IN (' . implode( ',', $ctx_ids ) . ')'
			: ' AND 1 = 0';
	}

	$ids = implode( ',', array_map( 'intval', wp_list_pluck( $terms, 'term_id' ) ) );
	$in_stock = $wpdb->get_col( $wpdb->prepare(
		"SELECT DISTINCT l.term_id
		 FROM {$wpdb->prefix}wc_product_attributes_lookup l{$brand_join}
		 WHERE l.taxonomy = %s AND l.in_stock = 1 AND l.term_id IN ({$ids}){$product_ctx}",
		$taxonomy
	) );
	$in_stock = array_map( 'intval', $in_stock );

	// Keep the user's own current selections visible even when other groups
	// have narrowed them away — otherwise they cannot be unticked.
	$f        = opus_active_filters();
	$selected = ( $param && ! empty( $f[ $param ] ) ) ? $f[ $param ] : array();

	return array_values(
		array_filter(
			$terms,
			function ( $t ) use ( $in_stock, $selected ) {
				return in_array( (int) $t->term_id, $in_stock, true )
					|| in_array( $t->slug, $selected, true );
			}
		)
	);
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
		if ( is_admin() || ( ! is_tax( 'product_brand' ) && ! ( function_exists( 'is_shop' ) && is_shop() ) ) ) {
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
		return $crumbs;
	}
);

/** Distinct build years present in the catalogue (desc order). */
function opus_available_years() {
	static $years = null;
	if ( null === $years ) {
		global $wpdb;
		$years = $wpdb->get_col(
			"SELECT DISTINCT meta_value FROM {$wpdb->postmeta}
			 WHERE meta_key = '_opus_year' AND meta_value REGEXP '^[0-9]{4}$'
			 ORDER BY meta_value DESC"
		);
	}
	return $years;
}

/**
 * Render the filter sidebar. Auto-submits on change; the "Merk" block is
 * navigation between brand archives and carries the active filters along.
 */
function opus_shop_filters() {
	$f       = opus_active_filters();
	$current = is_tax( 'product_brand' ) ? get_queried_object() : null;
	$action  = $current ? get_term_link( $current ) : ( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) );
	$action  = is_wp_error( $action ) ? home_url( '/' ) : $action;

	$types  = get_terms(
		array(
			'taxonomy'   => 'watch_type',
			'hide_empty' => true,
		)
	);
	$types  = is_wp_error( $types ) ? array() : $types;
	$models = get_terms(
		array(
			'taxonomy'   => 'watch_model',
			'hide_empty' => true,
		)
	);
	$models = is_wp_error( $models ) ? array() : $models;
	$brands = get_terms(
		array(
			'taxonomy'   => 'product_brand',
			'hide_empty' => true,
		)
	);
	$brands = is_wp_error( $brands ) ? array() : $brands;
	$years  = opus_available_years();

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
		<form class="shop-filters__form" method="get" action="<?php echo esc_url( $action ); ?>">

			<?php if ( $types ) : ?>
				<fieldset class="filter-group">
					<legend class="filter-group__title"><?php esc_html_e( 'Type horloge', 'maison-opus' ); ?></legend>
					<?php foreach ( $types as $t ) : ?>
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
						<?php foreach ( $models as $m ) : ?>
							<option value="<?php echo esc_attr( $m->slug ); ?>" <?php selected( $m->slug, $f['model'] ); ?>>
								<?php echo esc_html( $m->name ); ?> (<?php echo (int) $m->count; ?>)
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			<?php endif; ?>

			<?php if ( $brands && ! $current ) : /* brand pages are already brand-scoped — no Merk block there */ ?>
				<div class="filter-group">
					<p class="filter-group__title"><?php esc_html_e( 'Merk', 'maison-opus' ); ?></p>
					<?php
					foreach ( $brands as $b ) :
						$link = get_term_link( $b );
						if ( is_wp_error( $link ) ) {
							continue;
						}
						$link = $carry ? add_query_arg( $carry, $link ) : $link;
						?>
						<a class="filter-brand" href="<?php echo esc_url( $link ); ?>">
							<?php echo esc_html( $b->name ); ?> <span><?php echo (int) $b->count; ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

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
				$opus_attr_terms = opus_attribute_terms_in_stock( $opus_conf[0], $opus_param );
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

			<fieldset class="filter-group">
				<legend class="filter-group__title"><?php esc_html_e( 'Prijs', 'maison-opus' ); ?></legend>
				<?php foreach ( opus_price_ranges() as $key => $label ) : ?>
					<label class="filter-check">
						<input type="checkbox" name="prijs[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $f['prijs'], true ) ); ?> />
						<span><?php echo esc_html( $label ); ?></span>
					</label>
				<?php endforeach; ?>
			</fieldset>

			<div class="filter-actions">
				<button class="btn solid" type="submit"><?php esc_html_e( 'Toepassen', 'maison-opus' ); ?></button>
				<?php if ( opus_filters_are_active() ) : ?>
					<a class="filter-reset" href="<?php echo esc_url( $action ); ?>"><?php esc_html_e( 'Wis filters', 'maison-opus' ); ?></a>
				<?php endif; ?>
			</div>
		</form>
	</aside>
	<script>
		( function () {
			var form = document.querySelector( '.shop-filters__form' );
			if ( form ) {
				form.addEventListener( 'change', function () { form.submit(); } );
			}
		}() );
	</script>
	<?php
}
