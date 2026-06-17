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
			$opus_svc  = get_term_by( 'slug', 'service', 'product_cat' );
			$opus_svc  = $opus_svc ? get_term_link( $opus_svc ) : $opus_shop;
			$opus_svc  = is_wp_error( $opus_svc ) ? $opus_shop : $opus_svc;
			$opus_db   = get_page_by_path( 'database' );
			$opus_db   = $opus_db ? get_permalink( $opus_db ) : home_url( '/' );
			?>
			<nav class="left" aria-label="<?php esc_attr_e( 'Primary', 'maison-opus' ); ?>">
				<ul class="primary-nav">
					<li class="has-mega">
						<a href="<?php echo esc_url( $opus_shop ); ?>">Shop</a>
						<div class="mega"><?php opus_render_shop_mega(); ?></div>
					</li>
					<li><a href="<?php echo esc_url( $opus_svc ); ?>">Diensten</a></li>
					<li><a href="<?php echo esc_url( $opus_db ); ?>">Horlogedatabase</a></li>
				</ul>
			</nav>

			<a class="wordmark" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				MAISON <span class="amp">&amp;</span> OPUS
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
					echo '<a href="#">Over ons</a><a href="#">Contact</a>';
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
			</nav>
		</div>
		<div class="meta-row">
			<div>Online &amp; op afspraak</div>
			<div class="italic" style="font-variant: normal; letter-spacing: 0.18em;">Horloges gekocht · verkocht · onderhouden</div>
			<div class="right">Verzekerde verzending wereldwijd</div>
		</div>
	</div>
</header>
