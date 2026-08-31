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

/** Selected filters from the query string, sanitized. */
function opus_active_filters() {
	$types = isset( $_GET['type'] ) ? array_map( 'sanitize_key', (array) $_GET['type'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification
	$model = isset( $_GET['model'] ) ? sanitize_key( $_GET['model'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$prijs = isset( $_GET['prijs'] ) ? array_map( 'sanitize_key', (array) $_GET['prijs'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification
	$prijs = array_values( array_intersect( $prijs, array_keys( opus_price_ranges() ) ) );
	$van   = isset( $_GET['jaar_van'] ) ? absint( $_GET['jaar_van'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
	$tot   = isset( $_GET['jaar_tot'] ) ? absint( $_GET['jaar_tot'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification

	return array(
		'type'     => $types,
		'model'    => $model,
		'prijs'    => $prijs,
		'jaar_van' => $van,
		'jaar_tot' => $tot,
	);
}

/** Do the current query args carry any filter? */
function opus_filters_are_active() {
	$f = opus_active_filters();
	return $f['type'] || $f['model'] || $f['prijs'] || $f['jaar_van'] || $f['jaar_tot'];
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
		if ( $f['type'] || $f['model'] ) {
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
	$carry = array_filter(
		array(
			'type'     => $f['type'],
			'model'    => $f['model'],
			'prijs'    => $f['prijs'],
			'jaar_van' => $f['jaar_van'],
			'jaar_tot' => $f['jaar_tot'],
		)
	);
	?>
	<aside class="shop-filters">
		<form class="shop-filters__form" method="get" action="<?php echo esc_url( $action ); ?>">

			<?php if ( $types ) : ?>
				<fieldset class="filter-group">
					<legend class="filter-group__title">Type horloge</legend>
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
					<label class="filter-group__title" for="opus-filter-model">Model</label>
					<select id="opus-filter-model" class="filter-model" name="model">
						<option value="">Alle modellen</option>
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
					<p class="filter-group__title">Merk</p>
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
					<legend class="filter-group__title">Bouwjaar</legend>
					<div class="filter-years">
						<select name="jaar_van" aria-label="Bouwjaar vanaf">
							<option value="">Van</option>
							<?php foreach ( array_reverse( $years ) as $y ) : ?>
								<option value="<?php echo esc_attr( $y ); ?>" <?php selected( (int) $y, $f['jaar_van'] ); ?>><?php echo esc_html( $y ); ?></option>
							<?php endforeach; ?>
						</select>
						<span aria-hidden="true">—</span>
						<select name="jaar_tot" aria-label="Bouwjaar tot en met">
							<option value="">Tot</option>
							<?php foreach ( $years as $y ) : ?>
								<option value="<?php echo esc_attr( $y ); ?>" <?php selected( (int) $y, $f['jaar_tot'] ); ?>><?php echo esc_html( $y ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</fieldset>
			<?php endif; ?>

			<fieldset class="filter-group">
				<legend class="filter-group__title">Prijs</legend>
				<?php foreach ( opus_price_ranges() as $key => $label ) : ?>
					<label class="filter-check">
						<input type="checkbox" name="prijs[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $f['prijs'], true ) ); ?> />
						<span><?php echo esc_html( $label ); ?></span>
					</label>
				<?php endforeach; ?>
			</fieldset>

			<div class="filter-actions">
				<button class="btn solid" type="submit">Toepassen</button>
				<?php if ( opus_filters_are_active() ) : ?>
					<a class="filter-reset" href="<?php echo esc_url( $action ); ?>">Wis filters</a>
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
