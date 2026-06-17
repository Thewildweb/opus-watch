<?php
/**
 * Maison & Opus — theme bootstrap.
 *
 * @package maison-opus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MAISON_OPUS_VERSION', '0.1.0' );

/**
 * Theme supports.
 */
function maison_opus_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );

	// WooCommerce.
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	register_nav_menus(
		array(
			'primary' => __( 'Primary — left of wordmark', 'maison-opus' ),
			'utility' => __( 'Utility — right of wordmark', 'maison-opus' ),
		)
	);
}
add_action( 'after_setup_theme', 'maison_opus_setup' );

/**
 * Fonts & styles.
 */
function maison_opus_assets() {
	wp_enqueue_style(
		'maison-opus-fonts',
		'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400&family=EB+Garamond:ital,wght@0,400;0,500;1,400&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'maison-opus',
		get_template_directory_uri() . '/assets/css/theme.css',
		array( 'maison-opus-fonts' ),
		MAISON_OPUS_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'maison_opus_assets' );

/**
 * Swap WooCommerce's default wrappers for ours.
 */
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

add_action(
	'woocommerce_before_main_content',
	function () {
		echo '<main class="container wc-main"><div class="section">';
	},
	10
);
add_action(
	'woocommerce_after_main_content',
	function () {
		echo '</div></main>';
	},
	10
);

/* ─────────────────────────────────────────────────────────────
 * Storefront helpers (homepage Product collection)
 * ───────────────────────────────────────────────────────────── */

/**
 * Featured products for the homepage collection.
 *
 * @param int $limit How many to return.
 * @return WC_Product[]
 */
function opus_featured_products( $limit = 3 ) {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return array();
	}
	$products = wc_get_products(
		array(
			'status'   => 'publish',
			'featured' => true,
			'limit'    => $limit,
			'orderby'  => 'date',
			'order'    => 'ASC',
		)
	);
	// Fall back to most recent in-stock watches if nothing is flagged featured.
	if ( empty( $products ) ) {
		$products = wc_get_products(
			array(
				'status'   => 'publish',
				'limit'    => $limit,
				'orderby'  => 'date',
				'order'    => 'DESC',
			)
		);
	}
	return $products;
}

/**
 * On-brand SVG dial, used as a placeholder when a product has no image.
 * Palette + hand angles vary by $seed so a grid of placeholders reads as
 * distinct pieces rather than clones.
 *
 * @param int $seed Index of the piece in its grid.
 * @return string Escaped SVG markup.
 */
function opus_dial_svg( $seed = 0 ) {
	$palettes = array(
		array( 'bezel' => '#3a2a16', 'dial' => '#fbf3e0', 'ink' => '#281b10', 'mark' => '#9c7536' ),
		array( 'bezel' => '#1a1410', 'dial' => '#0e0a06', 'ink' => '#c69955', 'mark' => '#c69955' ),
		array( 'bezel' => '#5a4a35', 'dial' => '#f0e2c0', 'ink' => '#281b10', 'mark' => '#9c7536' ),
	);
	$p        = $palettes[ $seed % count( $palettes ) ];
	$numerals = array(
		array( 100, 42, 'XII' ),
		array( 158, 105, 'III' ),
		array( 100, 170, 'VI' ),
		array( 42, 105, 'IX' ),
	);

	ob_start();
	?>
	<svg class="dial" viewBox="0 0 200 200" aria-hidden="true" focusable="false">
		<circle cx="100" cy="100" r="95" fill="<?php echo esc_attr( $p['bezel'] ); ?>" />
		<circle cx="100" cy="100" r="85" fill="<?php echo esc_attr( $p['dial'] ); ?>" />
		<?php foreach ( $numerals as $n ) : ?>
			<text x="<?php echo (int) $n[0]; ?>" y="<?php echo (int) $n[1]; ?>" font-family="Cormorant Garamond, serif" font-size="13" text-anchor="middle" fill="<?php echo esc_attr( $p['ink'] ); ?>"><?php echo esc_html( $n[2] ); ?></text>
		<?php endforeach; ?>
		<text x="100" y="92" font-family="Cormorant Garamond, serif" font-style="italic" font-size="7" fill="<?php echo esc_attr( $p['mark'] ); ?>" text-anchor="middle">Maison &amp; Opus</text>
		<g transform="rotate(<?php echo (int) ( -40 + $seed * 47 ); ?> 100 100)"><rect x="99" y="50" width="2" height="55" fill="<?php echo esc_attr( $p['ink'] ); ?>" /></g>
		<g transform="rotate(<?php echo (int) ( 110 + $seed * 31 ); ?> 100 100)"><rect x="99" y="60" width="2" height="45" fill="<?php echo esc_attr( $p['ink'] ); ?>" /></g>
		<circle cx="100" cy="100" r="3" fill="<?php echo esc_attr( $p['mark'] ); ?>" />
	</svg>
	<?php
	return ob_get_clean();
}

/**
 * Render one product as a heritage "piece" card (matches 01-heritage.html).
 *
 * @param WC_Product $product Product to render.
 * @param int        $seed    Grid index, drives the placeholder dial.
 */
function opus_product_piece( $product, $seed = 0 ) {
	$id     = $product->get_id();
	$brands = wp_get_post_terms( $id, 'product_brand', array( 'fields' => 'names' ) );
	$brand  = ( ! is_wp_error( $brands ) && $brands ) ? $brands[0] : '';
	$desc   = wp_strip_all_tags( $product->get_short_description() );
	?>
	<a class="piece" href="<?php echo esc_url( get_permalink( $id ) ); ?>">
		<div class="piece-frame">
			<?php
			if ( has_post_thumbnail( $id ) ) {
				echo get_the_post_thumbnail( $id, 'large', array( 'class' => 'piece-photo' ) );
			} else {
				echo opus_dial_svg( $seed ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- assembled from esc_* helpers.
			}
			?>
		</div>
		<div class="piece-meta">
			<div class="piece-name"><?php echo esc_html( $product->get_name() ); ?></div>
			<?php if ( $brand ) : ?><div class="piece-ref"><?php echo esc_html( $brand ); ?></div><?php endif; ?>
		</div>
		<?php if ( $desc ) : ?>
			<div class="piece-line"><?php echo esc_html( $desc ); ?></div>
		<?php endif; ?>
		<div class="piece-price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
	</a>
	<?php
}

/**
 * Use our on-brand dial instead of WooCommerce's grey placeholder image
 * (sample products have no photos yet). Affects single product + cart.
 */
add_filter(
	'woocommerce_placeholder_img',
	function ( $html ) {
		return '<div class="opus-dial-placeholder">' . opus_dial_svg( 0 ) . '</div>';
	}
);

/** Three across in the shop loop (our CSS grid honours this semantically). */
add_filter( 'loop_shop_columns', function () { return 3; } );

/**
 * The main Shop is the Product section — watches only. Services have their
 * own category page, so exclude the "service" category from the shop loop.
 */
add_action(
	'woocommerce_product_query',
	function ( $query ) {
		if ( is_admin() || ! function_exists( 'is_shop' ) || ! is_shop() ) {
			return;
		}
		$tax_query   = (array) $query->get( 'tax_query' );
		$tax_query[] = array(
			'taxonomy' => 'product_cat',
			'field'    => 'slug',
			'terms'    => array( 'service' ),
			'operator' => 'NOT IN',
		);
		$query->set( 'tax_query', $tax_query );
	}
);

/* ─────────────────────────────────────────────────────────────
 * Homepage "shop by brand" tiles — image + link per brand.
 * ───────────────────────────────────────────────────────────── */

/** A representative product image URL for a brand (newest in stock). */
function opus_brand_image_url( $brand, $size = 'large' ) {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return '';
	}
	$q = wc_get_products(
		array(
			'limit'     => 1,
			'orderby'   => 'date',
			'order'     => 'DESC',
			'return'    => 'objects',
			'tax_query' => array(
				array(
					'taxonomy' => 'product_brand',
					'field'    => 'name',
					'terms'    => $brand,
				),
			),
		)
	);
	if ( ! $q ) {
		return '';
	}
	$url = wp_get_attachment_image_url( get_post_thumbnail_id( $q[0]->get_id() ), $size );
	return $url ? $url : '';
}

/** Archive link for a brand term (falls back to the shop). */
function opus_brand_term_link( $brand ) {
	$t = get_term_by( 'name', $brand, 'product_brand' );
	if ( ! $t ) {
		return function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
	}
	$l = get_term_link( $t );
	return is_wp_error( $l ) ? home_url( '/' ) : $l;
}

/* ─────────────────────────────────────────────────────────────
 * Shop mega-menu — brand column (1/6) + recent watches (5/6).
 * ───────────────────────────────────────────────────────────── */
function opus_render_shop_mega() {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return;
	}
	$shop    = wc_get_page_permalink( 'shop' );
	$brands  = get_terms(
		array(
			'taxonomy'   => 'product_brand',
			'hide_empty' => true,
			'orderby'    => 'count',
			'order'      => 'DESC',
		)
	);
	$watches = wc_get_products(
		array(
			'status'    => 'publish',
			'limit'     => 4,
			'orderby'   => 'date',
			'order'     => 'DESC',
			'tax_query' => array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					'terms'    => 'watches',
				),
			),
		)
	);
	?>
	<div class="mega-inner">
		<div class="mega-brands">
			<p class="mega-label">Merken</p>
			<a href="<?php echo esc_url( $shop ); ?>">Alle horloges</a>
			<?php
			if ( ! is_wp_error( $brands ) ) :
				foreach ( $brands as $b ) :
					$bl = get_term_link( $b );
					?>
					<a href="<?php echo esc_url( is_wp_error( $bl ) ? $shop : $bl ); ?>">
						<?php echo esc_html( $b->name ); ?> <span><?php echo (int) $b->count; ?></span>
					</a>
				<?php endforeach; endif; ?>
		</div>
		<div class="mega-watches">
			<p class="mega-label">Nieuw binnen</p>
			<div class="mega-grid">
				<?php foreach ( $watches as $w ) : ?>
					<a class="mega-card" href="<?php echo esc_url( get_permalink( $w->get_id() ) ); ?>">
						<span class="mega-thumb"><?php echo $w->get_image( 'woocommerce_thumbnail' ); ?></span>
						<span class="mega-cname"><?php echo esc_html( $w->get_name() ); ?></span>
						<span class="mega-cprice"><?php echo wp_kses_post( $w->get_price_html() ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
	<?php
}

/* ─────────────────────────────────────────────────────────────
 * Remove product reviews from the storefront (no reviews for now).
 * Drops the "Beoordelingen" tab and the empty rating/review prompt.
 * ───────────────────────────────────────────────────────────── */
add_filter(
	'woocommerce_product_tabs',
	function ( $tabs ) {
		unset( $tabs['reviews'] );
		return $tabs;
	},
	98
);
add_action(
	'init',
	function () {
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
	}
);
