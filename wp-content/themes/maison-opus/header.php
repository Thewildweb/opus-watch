<?php
/**
 * Site header — wordmark flanked by primary/utility nav.
 *
 * @package maison-opus
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width,initial-scale=1" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-head">
	<div class="container">
		<div class="top-row">
			<?php
			$opus_shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
			// Label = the shop page's title (Horloges / Watches), so it follows the page and its translation.
			$opus_shop_label = function_exists( 'wc_get_page_id' ) && wc_get_page_id( 'shop' ) > 0 ? get_the_title( wc_get_page_id( 'shop' ) ) : __( 'Horloges', 'maison-opus' );
			$opus_svc  = opus_services_url(); // /diensten/, or the service category on /en/
			$opus_zoek = get_page_by_path( 'zoekopdracht' );
			$opus_zoek = $opus_zoek ? get_permalink( $opus_zoek ) : home_url( '/zoekopdracht/' );
			?>
			<button class="nav-burger" type="button" aria-label="<?php esc_attr_e( 'Menu', 'maison-opus' ); ?>" aria-expanded="false" aria-controls="opus-mobile-menu" data-opus-menu-open>
				<span></span><span></span><span></span>
			</button>

			<nav class="left" aria-label="<?php esc_attr_e( 'Primary', 'maison-opus' ); ?>">
				<ul class="primary-nav">
					<li class="has-mega">
						<a href="<?php echo esc_url( $opus_shop ); ?>"><?php echo esc_html( $opus_shop_label ); ?></a>
						<div class="mega"><?php opus_render_shop_mega(); ?></div>
					</li>
					<li><a href="<?php echo esc_url( $opus_svc ); ?>"><?php esc_html_e( 'Diensten', 'maison-opus' ); ?></a></li>
					<li><a href="<?php echo esc_url( $opus_zoek ); ?>"><?php esc_html_e( 'Zoekopdracht', 'maison-opus' ); ?></a></li>
				</ul>
			</nav>

			<a class="wordmark" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<img src="<?php echo esc_url( maison_opus_asset_uri( 'assets/images/logo-lockup.svg' ) ); ?>" alt="Opus Watches" width="1520" height="1515" />
			</a>

			<nav class="right" aria-label="<?php esc_attr_e( 'Utility', 'maison-opus' ); ?>">
				<?php
				if ( has_nav_menu( 'utility' ) ) {
					wp_nav_menu(
						array(
							'theme_location' => 'utility',
							'container'      => false,
							'depth'          => 1,
						)
					);
				} else {
					?>
					<a href="#"><?php esc_html_e( 'Over ons', 'maison-opus' ); ?></a>
					<a href="#"><?php esc_html_e( 'Contact', 'maison-opus' ); ?></a>
					<?php
				}

				if ( function_exists( 'WC' ) && WC()->cart ) :
					$opus_count = WC()->cart->get_cart_contents_count();
					?>
					<a class="cart-link" href="<?php echo esc_url( wc_get_cart_url() ); ?>" aria-label="<?php esc_attr_e( 'Winkelwagen', 'maison-opus' ); ?>">
						<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true" focusable="false">
							<path d="M6 8h12l-1 12H7L6 8z" />
							<path d="M9 8V6a3 3 0 0 1 6 0v2" />
						</svg>
						<span class="cart-count<?php echo $opus_count ? ' has-items' : ''; ?>"><?php echo (int) $opus_count; ?></span>
					</a>
				<?php endif; ?>

				<?php get_template_part( 'template-parts/language-switcher' ); ?>
			</nav>
		</div>
		<div class="meta-row">
			<div><?php esc_html_e( 'Online & op afspraak', 'maison-opus' ); ?></div>
			<div class="italic" style="font-variant: normal; letter-spacing: 0.18em;"><?php esc_html_e( 'Horloges gekocht · verkocht · onderhouden', 'maison-opus' ); ?></div>
			<div class="right"><?php esc_html_e( 'Verzekerde verzending wereldwijd', 'maison-opus' ); ?></div>
		</div>
	</div>
</header>

<?php
// Mobile menu — the drawer carries all navigation on small screens.
$opus_mm_page = function ( $path ) {
	$page = get_page_by_path( $path );
	if ( ! $page ) {
		return home_url( '/' . $path . '/' );
	}
	return get_permalink( apply_filters( 'wpml_object_id', $page->ID, 'page', true ) );
};
$opus_mm_brands = get_terms( array( 'taxonomy' => 'product_brand', 'hide_empty' => false, 'orderby' => 'count', 'order' => 'DESC' ) );
$opus_mm_brands = is_wp_error( $opus_mm_brands ) ? array() : $opus_mm_brands;
?>
<div class="opus-mobile-menu" id="opus-mobile-menu" hidden>
	<div class="opus-mobile-menu__scrim" data-opus-menu-close></div>
	<nav class="opus-mobile-menu__panel" aria-label="<?php esc_attr_e( 'Menu', 'maison-opus' ); ?>">
		<div class="opus-mobile-menu__head">
			<span class="opus-mobile-menu__title">Opus Watches</span>
			<button class="opus-mobile-menu__close" type="button" aria-label="<?php esc_attr_e( 'Sluiten', 'maison-opus' ); ?>" data-opus-menu-close>&times;</button>
		</div>
		<div class="opus-mobile-menu__links">
			<a href="<?php echo esc_url( $opus_shop ); ?>"><?php esc_html_e( 'Alle horloges', 'maison-opus' ); ?></a>
			<?php if ( $opus_mm_brands ) : ?>
				<div class="opus-mobile-menu__brands">
					<?php foreach ( $opus_mm_brands as $opus_mm_b ) : ?>
						<?php $opus_mm_bl = get_term_link( $opus_mm_b ); ?>
						<?php if ( ! is_wp_error( $opus_mm_bl ) ) : ?>
							<a href="<?php echo esc_url( $opus_mm_bl ); ?>"><?php echo esc_html( $opus_mm_b->name ); ?></a>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<a href="<?php echo esc_url( $opus_svc ); ?>"><?php esc_html_e( 'Diensten', 'maison-opus' ); ?></a>
			<a href="<?php echo esc_url( $opus_zoek ); ?>"><?php esc_html_e( 'Zoekopdracht', 'maison-opus' ); ?></a>
			<a href="<?php echo esc_url( $opus_mm_page( 'database' ) ); ?>"><?php esc_html_e( 'De database', 'maison-opus' ); ?></a>
			<a href="<?php echo esc_url( $opus_mm_page( 'over-ons' ) ); ?>"><?php esc_html_e( 'Over ons', 'maison-opus' ); ?></a>
			<a href="<?php echo esc_url( $opus_mm_page( 'contact' ) ); ?>"><?php esc_html_e( 'Contact', 'maison-opus' ); ?></a>
		</div>
		<div class="opus-mobile-menu__foot">
			<?php get_template_part( 'template-parts/language-switcher' ); ?>
		</div>
	</nav>
</div>
<script>
( function () {
	var menu   = document.getElementById( 'opus-mobile-menu' );
	var burger = document.querySelector( '[data-opus-menu-open]' );
	if ( ! menu || ! burger ) { return; }
	function set( open ) {
		menu.hidden = ! open;
		document.body.classList.toggle( 'opus-menu-open', open );
		burger.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
	}
	burger.addEventListener( 'click', function () { set( menu.hidden ); } );
	menu.querySelectorAll( '[data-opus-menu-close]' ).forEach( function ( el ) {
		el.addEventListener( 'click', function () { set( false ); } );
	} );
	document.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' === e.key && ! menu.hidden ) { set( false ); }
	} );
}() );
</script>
