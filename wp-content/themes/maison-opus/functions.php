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

/** Watch database — custom-table reference catalogue (table, importer, REST, detail route). */
require_once get_template_directory() . '/inc/watchdb.php';

/** Hardening — REST/author enumeration, version disclosure. */
require_once get_template_directory() . '/inc/hardening.php';

/** Watch database — SEO: readable URLs, per-page metadata, schema, sitemap. */
require_once get_template_directory() . '/inc/watchdb-seo.php';

/** Service / onderhoud — inquiry form, submissions, per-product video field. */
require_once get_template_directory() . '/inc/service-inquiry.php';

/** Contact — general contact form + submissions. */
require_once get_template_directory() . '/inc/contact.php';

/** Shop filters — watch type taxonomy + type/merk/bouwjaar/prijs filtering. */
require_once get_template_directory() . '/inc/shop-filters.php';

/** Taxatie — appraisal request form + submissions. */
require_once get_template_directory() . '/inc/taxatie.php';

/** Site teksten — backend editor for all frontend theme strings (NL + EN). */
require_once get_template_directory() . '/inc/site-texts.php';

/**
 * Theme supports.
 */
function maison_opus_setup() {
	load_theme_textdomain( 'maison-opus', get_template_directory() . '/languages' );

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
 * Fonts & styles — self-hosted faces, shared base, then the heritage skin.
 */
function maison_opus_assets() {
	wp_enqueue_style(
		'maison-opus-fonts',
		get_template_directory_uri() . '/assets/css/fonts.css',
		array(),
		maison_opus_asset_ver( '/assets/css/fonts.css' )
	);

	wp_enqueue_style(
		'maison-opus-base',
		get_template_directory_uri() . '/assets/css/base.css',
		array(),
		maison_opus_asset_ver( '/assets/css/base.css' )
	);

	wp_enqueue_style(
		'maison-opus-skin',
		get_template_directory_uri() . '/assets/css/styles/heritage.css',
		array( 'maison-opus-fonts', 'maison-opus-base' ),
		maison_opus_asset_ver( '/assets/css/styles/heritage.css' )
	);
}

/**
 * Asset version string — the file's mtime, in every environment.
 *
 * This used to fall back to MAISON_OPUS_VERSION outside local, which meant
 * production served `?ver=0.1.0` forever: a deploy changed the file but not
 * the URL, so browsers and Cloudflare (max-age 14400) kept handing out the
 * previous stylesheet. The theme version only changes when someone remembers
 * to bump it; the mtime changes whenever the file does.
 */
function maison_opus_asset_ver( $rel ) {
	$path = get_template_directory() . '/' . ltrim( $rel, '/' );
	if ( file_exists( $path ) ) {
		return (string) filemtime( $path );
	}
	return MAISON_OPUS_VERSION;
}

/**
 * URL for a bundled theme file, carrying the same mtime cache buster.
 *
 * Stylesheets and scripts get a version from wp_enqueue_*; images are
 * referenced straight from the templates, so without this they keep their
 * URL forever and a replaced logo or hero sits in browser and Cloudflare
 * caches until the max-age runs out.
 *
 * Not used for the preloaded woff2 files: fonts.css requests those without
 * a query string, and a preload only counts if the two URLs match exactly.
 */
function maison_opus_asset_uri( $rel ) {
	$rel = '/' . ltrim( $rel, '/' );
	return add_query_arg( 'ver', maison_opus_asset_ver( $rel ), get_theme_file_uri( $rel ) );
}
add_action( 'wp_enqueue_scripts', 'maison_opus_assets' );

/**
 * Mini-cart drawer — script + markup.
 *
 * Depends on wc-cart-fragments so WooCommerce owns the fragment refresh and
 * the session storage that goes with it; the drawer only reacts to it.
 */
function maison_opus_cart_assets() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}

	wp_enqueue_script(
		'maison-opus-notices',
		get_template_directory_uri() . '/assets/js/notices.js',
		array(),
		maison_opus_asset_ver( '/assets/js/notices.js' ),
		true
	);

	wp_enqueue_script(
		'maison-opus-cart-drawer',
		get_template_directory_uri() . '/assets/js/cart-drawer.js',
		array( 'jquery', 'wc-cart-fragments', 'maison-opus-notices' ),
		maison_opus_asset_ver( '/assets/js/cart-drawer.js' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'maison_opus_cart_assets', 20 );

/**
 * Drop WooCommerce assets that the page in front of the visitor cannot use.
 *
 * wc-blocks.css (14 KB) styles the cart and checkout *blocks*, and was loading
 * on the homepage, the database and every page besides. The classic templates
 * this theme uses never touch it.
 *
 * Filtering the tag rather than dequeuing: WooCommerce enqueues these after
 * both wp_enqueue_scripts and the wp_print_styles action have run, so at no
 * point that a theme can hook is the handle actually in the queue.
 *
 * Note what is deliberately left alone: wc-cart-fragments, because the header
 * count and the mini-cart drawer are on every page, and the woocommerce-*
 * stylesheets, because the homepage renders product tiles.
 */
function maison_opus_trim_assets( $tag, $handle ) {
	$block_styles = array( 'wc-blocks-style', 'wc-blocks-style-cart', 'wc-blocks-style-checkout' );

	if ( ! in_array( $handle, $block_styles, true ) ) {
		return $tag;
	}
	if ( ! function_exists( 'is_cart' ) ) {
		return $tag;
	}
	if ( is_cart() || is_checkout() || is_account_page() ) {
		return $tag;
	}
	return '';
}
add_filter( 'style_loader_tag', 'maison_opus_trim_assets', 10, 2 );

/**
 * jquery-migrate exists to keep pre-3.0 jQuery code working. Nothing here
 * needs it, and it is a request on every page.
 */
add_action(
	'wp_default_scripts',
	function ( $scripts ) {
		if ( is_admin() || empty( $scripts->registered['jquery'] ) ) {
			return;
		}
		$scripts->registered['jquery']->deps = array_diff(
			$scripts->registered['jquery']->deps,
			array( 'jquery-migrate' )
		);
	}
);

/** The drawer lives at the top of the body, outside the footer element. */
add_action(
	'wp_body_open',
	function () {
		get_template_part( 'template-parts/cart-drawer' );
	},
	20
);

/**
 * Cart fragments — the header count and the drawer contents, so both update
 * without a page reload. The header count is custom markup, so WooCommerce
 * does not know about it unless we say so here.
 */
function maison_opus_cart_fragments( $fragments ) {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return $fragments;
	}

	$count = WC()->cart->get_cart_contents_count();

	ob_start();
	?>
	<span class="cart-count<?php echo $count ? ' has-items' : ''; ?>"><?php echo (int) $count; ?></span>
	<?php
	$fragments['span.cart-count'] = trim( ob_get_clean() );

	ob_start();
	echo '<div class="opus-mini-cart">';
	woocommerce_mini_cart();
	echo '</div>';
	$fragments['div.opus-mini-cart'] = ob_get_clean();

	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'maison_opus_cart_fragments' );

/**
 * Fleuron — the logo's rosette between two hairlines, as a section ornament.
 * Inlined so it inherits currentColor (gilt on paper, gilt-bright on dark).
 */
function opus_fleuron() {
	static $svg = null;
	if ( null === $svg ) {
		$file = get_template_directory() . '/assets/images/logo-icon.svg';
		$svg  = file_exists( $file ) ? (string) file_get_contents( $file ) : '';
		$svg  = preg_replace( '/<style>.*?<\/style>/s', '', $svg );
		$svg  = str_replace( '<g ', '<g fill="currentColor" ', $svg );
	}
	if ( $svg ) {
		echo '<div class="fleuron" aria-hidden="true">' . $svg . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- static theme asset.
	}
}

/**
 * Preload the two faces that set the first paint — body text and headings.
 * The rest resolve from fonts.css as they are needed.
 */
function maison_opus_preload_fonts() {
	$faces = array(
		'eb-garamond-400-normal-latin.woff2',
		'cormorant-garamond-400-normal-latin.woff2',
	);
	foreach ( $faces as $face ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin />' . "\n",
			esc_url( get_theme_file_uri( 'assets/fonts/' . $face ) )
		);
	}
}
add_action( 'wp_head', 'maison_opus_preload_fonts', 4 );

/**
 * Favicons — rosette from the logo; the SVG flips to paper on dark browser UIs.
 */
function maison_opus_favicons() {
	echo '<link rel="icon" href="' . esc_url( maison_opus_asset_uri( 'assets/images/logo-icon.svg' ) ) . '" type="image/svg+xml" />' . "\n";
	echo '<link rel="icon" href="' . esc_url( maison_opus_asset_uri( 'assets/images/favicon-32.png' ) ) . '" type="image/png" sizes="32x32" />' . "\n";
	echo '<link rel="icon" href="' . esc_url( maison_opus_asset_uri( 'assets/images/favicon-192.png' ) ) . '" type="image/png" sizes="192x192" />' . "\n";
	echo '<link rel="apple-touch-icon" href="' . esc_url( maison_opus_asset_uri( 'assets/images/apple-touch-icon.png' ) ) . '" />' . "\n";
}
add_action( 'wp_head', 'maison_opus_favicons', 5 );

/**
 * Swap WooCommerce's default wrappers for ours.
 */
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

add_action(
	'woocommerce_before_main_content',
	function () {
		echo '<main class="container wc-main"><div class="section">';

		// WooCommerce normally prints notices from woocommerce_before_single_product
		// and woocommerce_before_shop_loop, but this theme replaces those content
		// templates and never fires either hook — so add-to-cart messages and
		// errors were queueing in the session invisibly and then surfacing on
		// whatever page happened to print notices next (cart, account). Printing
		// here covers every WooCommerce page and clears the queue.
		woocommerce_output_all_notices();
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
		<text x="100" y="92" font-family="Cormorant Garamond, serif" font-style="italic" font-size="7" fill="<?php echo esc_attr( $p['mark'] ); ?>" text-anchor="middle">Opus Watches</text>
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
			<?php if ( $brand ) : ?><div class="piece-ref"><?php echo esc_html( $brand ); ?></div><?php endif; ?>
			<div class="piece-name"><?php echo esc_html( $product->get_name() ); ?></div>
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
			'terms'    => array( 'service', 'servicing' ),
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

/**
 * Logo file URL for a brand, if one has been added to the theme.
 * Drop a file at assets/images/brands/<term-slug>.svg (or .png/.webp) and it
 * is used automatically; otherwise the homepage falls back to a wordmark.
 */
function opus_brand_logo_url( $slug ) {
	$dir = get_template_directory() . '/assets/images/brands/';
	foreach ( array( 'svg', 'webp', 'png' ) as $ext ) {
		if ( file_exists( $dir . $slug . '.' . $ext ) ) {
			return maison_opus_asset_uri( 'assets/images/brands/' . $slug . '.' . $ext );
		}
	}
	return '';
}

/**
 * Hero image URL for a brand archive. Priority:
 *  1. curated file at assets/images/heroes/<term-slug>.<jpg|webp|png>
 *  2. the brand's newest product photo
 *  3. '' — the template then renders a text-only header.
 */
function opus_brand_hero_url( $term ) {
	$dir = get_template_directory() . '/assets/images/heroes/';
	foreach ( array( 'webp', 'jpg', 'png' ) as $ext ) {
		if ( file_exists( $dir . $term->slug . '.' . $ext ) ) {
			return maison_opus_asset_uri( 'assets/images/heroes/' . $term->slug . '.' . $ext );
		}
	}
	return opus_brand_image_url( $term->name );
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
 * Single product — spec sheet parsed from the imported description.
 * The importer bakes a <table> of specs into the product description;
 * we lift it out into a clean label=>value map (Dutch labels) and keep
 * the remaining prose separately so the template can style each.
 * ───────────────────────────────────────────────────────────── */
function opus_product_spec_data( $product ) {
	$html  = (string) $product->get_description();
	$specs = array();
	$prose = $html;

	if ( $html && preg_match( '/<table[^>]*>.*?<\/table>/is', $html, $m ) ) {
		$table = $m[0];
		$prose = str_replace( $table, '', $html );

		$labels = array(
			'brand'             => __( 'Merk', 'maison-opus' ),
			'model'             => __( 'Model', 'maison-opus' ),
			'reference number'  => __( 'Referentie', 'maison-opus' ),
			'reference'         => __( 'Referentie', 'maison-opus' ),
			'condition'         => __( 'Staat', 'maison-opus' ),
			'year'              => __( 'Bouwjaar', 'maison-opus' ),
			'box'               => __( 'Doos', 'maison-opus' ),
			'papers'            => __( 'Papieren', 'maison-opus' ),
			'case size'         => __( 'Kastmaat', 'maison-opus' ),
			'movement'          => __( 'Uurwerk', 'maison-opus' ),
			'case material'     => __( 'Kastmateriaal', 'maison-opus' ),
			'bracelet material' => __( 'Bandmateriaal', 'maison-opus' ),
			'dial color'        => __( 'Wijzerplaat', 'maison-opus' ),
			'dial'              => __( 'Wijzerplaat', 'maison-opus' ),
			'clasp'             => __( 'Sluiting', 'maison-opus' ),
			'gender'            => __( 'Voor', 'maison-opus' ),
			'water resistance'  => __( 'Waterdichtheid', 'maison-opus' ),
		);
		$values = array(
			'yes'    => __( 'Ja', 'maison-opus' ),
			'no'     => __( 'Nee', 'maison-opus' ),
			'as new' => __( 'Zo goed als nieuw', 'maison-opus' ),
			'new'    => __( 'Nieuw', 'maison-opus' ),
			'unworn' => __( 'Ongedragen', 'maison-opus' ),
		);

		$dom = new DOMDocument();
		libxml_use_internal_errors( true );
		$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $table );
		libxml_clear_errors();

		foreach ( $dom->getElementsByTagName( 'tr' ) as $tr ) {
			$cells = $tr->getElementsByTagName( 'td' );
			if ( $cells->length < 2 ) {
				continue;
			}
			$label = trim( $cells->item( 0 )->textContent );
			$value = trim( $cells->item( 1 )->textContent );
			if ( '' === $label || '' === $value ) {
				continue;
			}
			$lkey  = strtolower( $label );
			$vkey  = strtolower( $value );
			$label = isset( $labels[ $lkey ] ) ? $labels[ $lkey ] : $label;
			$value = isset( $values[ $vkey ] ) ? $values[ $vkey ] : $value;
			$specs[ $label ] = $value;
		}
	}

	// Tidy the leftover prose: collapse <br> runs into paragraph breaks.
	$prose = preg_replace( '/(?:<br\s*\/?>\s*)+/i', "\n\n", (string) $prose );
	$prose = trim( $prose );
	if ( '' === trim( wp_strip_all_tags( $prose ) ) ) {
		$prose = '';
	}

	return array(
		'specs' => $specs,
		'prose' => $prose,
	);
}

/** Keep related products to a clean single row of three. */
add_filter(
	'woocommerce_output_related_products_args',
	function ( $args ) {
		$args['posts_per_page'] = 3;
		$args['columns']       = 3;
		return $args;
	}
);

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
					'terms'    => array( 'watches', 'watches-en' ),
				),
			),
		)
	);
	?>
	<div class="mega-inner">
		<div class="mega-brands">
			<p class="mega-label"><?php esc_html_e( 'Merken', 'maison-opus' ); ?></p>
			<a href="<?php echo esc_url( $shop ); ?>"><?php esc_html_e( 'Alle horloges', 'maison-opus' ); ?></a>
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
			<p class="mega-label"><?php esc_html_e( 'Nieuw binnen', 'maison-opus' ); ?></p>
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

/**
 * Every watch is one of a kind — sell one at a time. This removes the
 * quantity selector on product pages and caps each line item at 1.
 */
add_filter( 'woocommerce_is_sold_individually', '__return_true' );

/**
 * Show the product description as plain content — drop the tabbed UI
 * (the lone "Beschrijving" tab) and just render the description below
 * the product summary.
 */
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
add_action(
	'woocommerce_after_single_product_summary',
	function () {
		global $product;
		if ( ! $product ) {
			return;
		}
		$content = $product->get_description();
		if ( ! $content ) {
			return;
		}
		echo '<div class="opus-product-description">' . apply_filters( 'the_content', wp_kses_post( $content ) ) . '</div>';
	},
	10
);

/* ─────────────────────────────────────────────────────────────
 * Zoekopdracht — "find me this watch" search requests.
 * Submissions are stored as a private CPT (visible in wp-admin) and
 * a notification is e-mailed to the shop. Posts to admin-post.php.
 * ───────────────────────────────────────────────────────────── */

/** Private post type so the shop can review incoming search requests. */
add_action(
	'init',
	function () {
		register_post_type(
			'opus_search_request',
			array(
				'labels'       => array(
					'name'          => 'Zoekopdrachten',
					'singular_name' => 'Zoekopdracht',
					'menu_name'     => 'Zoekopdrachten',
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => true,
				'menu_icon'    => 'dashicons-search',
				'supports'     => array( 'title' ),
				'capability_type' => 'post',
			)
		);
	}
);

/** Fields we accept, with their human labels (used for storage + e-mail). */
function opus_search_request_fields() {
	return array(
		'naam'       => 'Naam',
		'email'      => 'E-mailadres',
		'telefoon'   => 'Telefoon',
		'merk'       => 'Merk',
		'model'      => 'Model',
		'referentie' => 'Referentienummer',
		'staat'      => 'Gewenste staat',
		'budget'     => 'Budget',
		'bericht'    => 'Toelichting',
	);
}

/** Handle the zoekopdracht form submission. */
function opus_handle_search_request() {
	$back = wp_get_referer() ? wp_get_referer() : home_url( '/zoekopdracht/' );

	if ( ! isset( $_POST['opus_zoek_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['opus_zoek_nonce'] ), 'opus_zoekopdracht' ) ) {
		wp_safe_redirect( add_query_arg( 'zoek', 'error', $back ) );
		exit;
	}

	$fields = opus_search_request_fields();
	$data   = array();
	foreach ( $fields as $key => $label ) {
		$raw = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
		$data[ $key ] = ( 'bericht' === $key ) ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
	}

	// Minimal validation: name, a valid e-mail, and a brand.
	if ( '' === $data['naam'] || ! is_email( $data['email'] ) || '' === $data['merk'] ) {
		wp_safe_redirect( add_query_arg( 'zoek', 'error', $back ) );
		exit;
	}

	$title = sprintf( '%s — %s %s', $data['naam'], $data['merk'], $data['model'] );
	$post_id = wp_insert_post(
		array(
			'post_type'   => 'opus_search_request',
			'post_status' => 'private',
			'post_title'  => wp_strip_all_tags( $title ),
		)
	);

	if ( $post_id && ! is_wp_error( $post_id ) ) {
		foreach ( $data as $key => $value ) {
			update_post_meta( $post_id, '_opus_' . $key, $value );
		}

		// Best-effort notification to the shop.
		$lines = array( 'Nieuwe zoekopdracht via de website:', '' );
		foreach ( $fields as $key => $label ) {
			if ( '' !== $data[ $key ] ) {
				$lines[] = $label . ': ' . $data[ $key ];
			}
		}
		wp_mail(
			get_option( 'admin_email' ),
			'Nieuwe zoekopdracht — ' . $data['merk'] . ' ' . $data['model'],
			implode( "\n", $lines ),
			array( 'Reply-To: ' . $data['naam'] . ' <' . $data['email'] . '>' )
		);
	}

	wp_safe_redirect( add_query_arg( 'zoek', 'ok', remove_query_arg( 'zoek', $back ) ) . '#zoekform' );
	exit;
}
add_action( 'admin_post_opus_zoekopdracht', 'opus_handle_search_request' );
add_action( 'admin_post_nopriv_opus_zoekopdracht', 'opus_handle_search_request' );
