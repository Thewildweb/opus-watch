<?php
/**
 * Site footer.
 *
 * @package maison-opus
 */
?>
<footer class="site-foot">
	<div class="container">
		<?php opus_fleuron(); ?>
		<div class="foot-grid">
			<div>
				<div class="mark">Opus Watches</div>
				<p style="font-style: italic;">
					<?php esc_html_e( 'Een klein onafhankelijk horlogehuis.', 'maison-opus' ); ?><br/>
					<?php esc_html_e( 'Horloges gekocht, verkocht en met de hand verzorgd.', 'maison-opus' ); ?>
				</p>
			</div>
			<?php
			// Language-aware links: term/page lookups go through WPML filters,
			// so these resolve to the translated URLs on /en/.
			$opus_ft_page = function ( $path ) {
				$page = get_page_by_path( $path );
				if ( ! $page ) {
					return home_url( '/' . $path . '/' );
				}
				$id = apply_filters( 'wpml_object_id', $page->ID, 'page', true );
				return get_permalink( $id );
			};
			$opus_ft_svc = get_term_by( 'slug', 'service', 'product_cat' );
			$opus_ft_svc = $opus_ft_svc ? get_term_link( $opus_ft_svc ) : home_url( '/' );
			$opus_ft_svc = is_wp_error( $opus_ft_svc ) ? home_url( '/' ) : $opus_ft_svc;
			?>
			<div>
				<h4><?php esc_html_e( 'Collectie', 'maison-opus' ); ?></h4>
				<a href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ); ?>"><?php esc_html_e( 'Alle horloges', 'maison-opus' ); ?></a>
				<a href="<?php echo esc_url( $opus_ft_page( 'database' ) ); ?>"><?php esc_html_e( 'De database', 'maison-opus' ); ?></a>
				<a href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/' ) ); ?>"><?php esc_html_e( 'Inloggen', 'maison-opus' ); ?></a>
			</div>
			<div>
				<h4><?php esc_html_e( 'Onderhoud', 'maison-opus' ); ?></h4>
				<a href="<?php echo esc_url( $opus_ft_svc ); ?>"><?php esc_html_e( 'Onze diensten', 'maison-opus' ); ?></a>
				<a href="<?php echo esc_url( $opus_ft_svc ); ?>"><?php esc_html_e( 'Onderhoud aanvragen', 'maison-opus' ); ?></a>
				<a href="<?php echo esc_url( $opus_ft_svc ); ?>"><?php esc_html_e( 'Restauratie', 'maison-opus' ); ?></a>
			</div>
			<div>
				<h4><?php esc_html_e( 'Contact', 'maison-opus' ); ?></h4>
				<a href="<?php echo esc_url( $opus_ft_page( 'contact' ) ); ?>"><?php esc_html_e( 'Neem contact op', 'maison-opus' ); ?></a>
				<a href="<?php echo esc_url( $opus_ft_page( 'contact' ) ); ?>"><?php esc_html_e( 'De Entree 201, Amsterdam', 'maison-opus' ); ?></a>
				<a href="<?php echo esc_url( $opus_ft_page( 'over-ons' ) ); ?>"><?php esc_html_e( 'Over ons', 'maison-opus' ); ?></a>
			</div>
		</div>
		<div class="foot-bot">
			<div>© <?php echo esc_html( gmdate( 'Y' ) ); ?> Opus Watches · <?php esc_html_e( 'Onafhankelijk horlogehuis', 'maison-opus' ); ?> · KvK 93934866</div>
			<div><?php esc_html_e( 'Colofon', 'maison-opus' ); ?> &nbsp;·&nbsp; <?php esc_html_e( 'Privacy', 'maison-opus' ); ?> &nbsp;·&nbsp; <?php esc_html_e( 'Voorwaarden', 'maison-opus' ); ?></div>
		</div>
	</div>
	<?php wp_footer(); ?>
</footer>
<script>
/* Form confirmations are rendered from a query flag (?contact/zoek/svc=ok).
   Strip it after load so a refresh or shared link doesn't re-show the notice. */
( function () {
	try {
		var u = new URL( window.location.href );
		var changed = false;
		[ 'contact', 'zoek', 'svc' ].forEach( function ( k ) {
			if ( u.searchParams.has( k ) ) { u.searchParams.delete( k ); changed = true; }
		} );
		if ( changed ) {
			history.replaceState( null, '', u.pathname + ( u.search || '' ) + u.hash );
		}
	} catch ( e ) {}
} )();
</script>
</body>
</html>
