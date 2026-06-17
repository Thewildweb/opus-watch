<?php
/**
 * Site footer.
 *
 * @package maison-opus
 */
?>
<footer class="site-foot">
	<div class="container">
		<div class="foot-grid">
			<div>
				<div class="mark">MAISON <em>&amp;</em> OPUS</div>
				<p style="font-style: italic;">
					Een klein onafhankelijk horlogehuis.<br/>
					Horloges gekocht, verkocht en met de hand verzorgd.
				</p>
			</div>
			<div>
				<h4>Collectie</h4>
				<a href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ); ?>">Alle horloges</a>
				<a href="<?php echo esc_url( home_url( '/database/' ) ); ?>">De database</a>
				<a href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/' ) ); ?>">Inloggen</a>
			</div>
			<div>
				<h4>Onderhoud</h4>
				<a href="<?php echo esc_url( home_url( '/product-category/service/' ) ); ?>">Onze diensten</a>
				<a href="<?php echo esc_url( home_url( '/product-category/service/' ) ); ?>">Onderhoud aanvragen</a>
				<a href="<?php echo esc_url( home_url( '/product-category/service/' ) ); ?>">Restauratie</a>
			</div>
			<div>
				<h4>Contact</h4>
				<a href="#">Online &amp; op afspraak</a>
				<a href="#">Verzekerde verzending wereldwijd</a>
				<a href="#">Vragen</a>
			</div>
		</div>
		<div class="foot-bot">
			<div>© <?php echo esc_html( gmdate( 'Y' ) ); ?> Maison &amp; Opus · Onafhankelijk horlogehuis</div>
			<div>Colofon &nbsp;·&nbsp; Privacy &nbsp;·&nbsp; Voorwaarden</div>
		</div>
	</div>
	<?php wp_footer(); ?>
</footer>
</body>
</html>
