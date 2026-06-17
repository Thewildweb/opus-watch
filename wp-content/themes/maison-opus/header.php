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
			<nav class="left" aria-label="<?php esc_attr_e( 'Primary', 'maison-opus' ); ?>">
				<?php
				if ( has_nav_menu( 'primary' ) ) {
					wp_nav_menu(
						array(
							'theme_location' => 'primary',
							'container'      => false,
							'depth'          => 1,
						)
					);
				} else {
					echo '<a href="#">Collectie</a><a href="#">Atelier</a><a href="#">Database</a>';
				}
				?>
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
					echo '<a href="#">Magazine</a><a href="#">Reserveren</a><a href="#">Inloggen</a>';
				}
				?>
			</nav>
		</div>
		<div class="meta-row">
			<div>Online &amp; op afspraak</div>
			<div class="italic" style="font-variant: normal; letter-spacing: 0.18em;">Horloges gekocht · verkocht · onderhouden</div>
			<div class="right">Verzekerde verzending wereldwijd</div>
		</div>
	</div>
</header>
