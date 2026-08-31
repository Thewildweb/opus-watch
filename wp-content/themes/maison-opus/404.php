<?php
/**
 * 404 — page not found.
 *
 * @package maison-opus
 */

get_header();

$opus_shop     = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$opus_database = get_page_by_path( 'database' );
$opus_database = $opus_database ? get_permalink( $opus_database ) : home_url( '/database/' );
$opus_contact  = get_page_by_path( 'contact' );
$opus_contact  = $opus_contact ? get_permalink( $opus_contact ) : home_url( '/contact/' );
?>

<main class="page-main">
	<div class="container">
		<div class="section">
		<article class="page-article page-article--404">
			<header class="page-header">
				<p class="page-eyebrow">404</p>
				<h1 class="page-title display"><?php esc_html_e( 'Deze pagina bestaat niet', 'maison-opus' ); ?></h1>
			</header>

			<div class="page-content entry">
				<p>
					<?php esc_html_e( 'Mogelijk is het horloge verkocht of is de link verouderd. Onderstaande wegen leiden wel ergens heen.', 'maison-opus' ); ?>
				</p>

				<nav class="page-404-links">
					<a href="<?php echo esc_url( $opus_shop ); ?>"><?php esc_html_e( 'Alle horloges', 'maison-opus' ); ?></a>
					<a href="<?php echo esc_url( $opus_database ); ?>"><?php esc_html_e( 'De database', 'maison-opus' ); ?></a>
					<a href="<?php echo esc_url( $opus_contact ); ?>"><?php esc_html_e( 'Neem contact op', 'maison-opus' ); ?></a>
				</nav>

				<?php get_search_form(); ?>
			</div>
		</article>
		</div>
	</div>
</main>

<?php
get_footer();
