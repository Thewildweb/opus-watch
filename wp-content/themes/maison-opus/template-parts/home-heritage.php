<?php
/**
 * Front page — modern, image-forward boutique homepage.
 * Hero → feature tiles (image + text overlay) → in-stock collection →
 * shop-by-brand tiles → service → closing line. Heritage palette, modern shape.
 *
 * Imagery: hero-repair.jpg (Pexels, Rubas Maqbool), tile-movement.jpg &
 * tile-buy.jpg (Pexels, free licence). Brand tiles use real product images.
 *
 * @package maison-opus
 */


$shop_url    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$service_url = opus_services_url();
$service_url = is_wp_error( $service_url ) ? $shop_url : $service_url;
?>

<!-- ── HERO ──────────────────────────────────────────────── -->
<section class="lede">
	<?php $opus_hero = opus_theme_image( 'home_hero', 'assets/images/hero-heritage.webp' ); ?>
	<img class="lede-img" src="<?php echo esc_url( $opus_hero['url'] ); ?>" <?php echo opus_image_dims( $opus_hero ); // phpcs:ignore WordPress.Security.EscapeOutput -- integers only. ?>
		alt="<?php esc_attr_e( 'Een chique gouden horloge met groene wijzerplaat', 'maison-opus' ); ?>" fetchpriority="high" decoding="async" />
	<div class="lede-scrim"></div>
	<div class="container lede-inner">
		<h1 class="lede-title"><?php esc_html_e( 'Horloges —', 'maison-opus' ); ?><br/><?php esc_html_e( 'gekocht, verkocht en', 'maison-opus' ); ?><br/><em><?php esc_html_e( 'met de hand hersteld.', 'maison-opus' ); ?></em></h1>
		<p class="lede-sub">
			<?php esc_html_e( 'Opus biedt een zorgvuldig samengestelde selectie bijzondere horloges, en volledig onderhoud en restauratie in eigen atelier.', 'maison-opus' ); ?>
		</p>
		<div class="lede-cta">
			<a class="btn solid" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Bekijk de collectie', 'maison-opus' ); ?></a>
			<a class="btn light" href="<?php echo esc_url( $service_url ); ?>"><?php esc_html_e( 'Onderhoud aanvragen', 'maison-opus' ); ?></a>
		</div>
	</div>
</section>

<!-- ── FEATURE TILES (image + text overlay) ──────────────── -->
<section class="band">
	<div class="container">
		<div class="tiles cols-3">
			<a class="tile" href="<?php echo esc_url( $shop_url ); ?>">
				<?php $opus_tile = opus_theme_image( 'home_tile_service', 'assets/images/tile-movement.webp' ); ?>
				<img src="<?php echo esc_url( $opus_tile['url'] ); ?>" <?php echo opus_image_dims( $opus_tile ); // phpcs:ignore WordPress.Security.EscapeOutput -- integers only. ?> alt="" loading="lazy" decoding="async" />
				<div class="tile-scrim"></div>
				<div class="tile-body">
					<h3 class="tile-title"><?php esc_html_e( 'Bijzondere horloges,', 'maison-opus' ); ?><br/><?php esc_html_e( 'op voorraad.', 'maison-opus' ); ?></h3>
					<span class="tile-link"><?php esc_html_e( 'Bekijk alles →', 'maison-opus' ); ?></span>
				</div>
			</a>

			<a class="tile editorial" href="<?php echo esc_url( $service_url ); ?>">
				<div class="tile-body">
					<h3 class="tile-title"><?php esc_html_e( 'Wij kopen ook', 'maison-opus' ); ?><br/><?php esc_html_e( 'uw horloge.', 'maison-opus' ); ?></h3>
					<span class="tile-link gilt"><?php esc_html_e( 'Vraag een taxatie aan →', 'maison-opus' ); ?></span>
				</div>
			</a>

			<a class="tile" href="<?php echo esc_url( $service_url ); ?>">
				<?php $opus_tile = opus_theme_image( 'home_tile_buy', 'assets/images/tile-buy.webp' ); ?>
				<img src="<?php echo esc_url( $opus_tile['url'] ); ?>" <?php echo opus_image_dims( $opus_tile ); // phpcs:ignore WordPress.Security.EscapeOutput -- integers only. ?> alt="" loading="lazy" decoding="async" />
				<div class="tile-scrim"></div>
				<div class="tile-body">
					<h3 class="tile-title"><?php esc_html_e( 'Onderhoud &', 'maison-opus' ); ?><br/><?php esc_html_e( 'restauratie.', 'maison-opus' ); ?></h3>
					<span class="tile-link"><?php esc_html_e( 'Onze diensten →', 'maison-opus' ); ?></span>
				</div>
			</a>
		</div>
	</div>
</section>

<!-- ── COLLECTION ────────────────────────────────────────── -->
<section class="section" id="collection">
	<div class="container">
		<?php opus_fleuron(); ?>
		<div class="sect-head">
			<div>
				<h2><?php esc_html_e( 'Nu op voorraad', 'maison-opus' ); ?></h2>
			</div>
			<a class="sect-link" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Bekijk alle horloges →', 'maison-opus' ); ?></a>
		</div>
		<div class="pieces">
			<?php
			$featured = opus_featured_products( 3 );
			if ( $featured ) {
				$i = 0;
				foreach ( $featured as $product ) {
					opus_product_piece( $product, $i++ );
				}
			} else {
				echo '<p class="italic">' . esc_html__( 'De collectie wordt voorbereid.', 'maison-opus' ) . '</p>';
			}
			?>
		</div>
	</div>
</section>

<!-- ── SHOP BY BRAND (image + name overlay) ──────────────── -->
<section class="band band-tint">
	<div class="container">
		<div class="sect-head">
			<div>
				<h2><?php esc_html_e( 'Shop op merk', 'maison-opus' ); ?></h2>
			</div>
		</div>
		<div class="brand-wall">
			<?php
			$brand_terms = get_terms(
				array(
					'taxonomy'   => 'product_brand',
					'hide_empty' => false,
					'orderby'    => 'count',
					'order'      => 'DESC',
				)
			);
			if ( ! is_wp_error( $brand_terms ) ) :
				foreach ( $brand_terms as $bt ) :
					$link = get_term_link( $bt );
					if ( is_wp_error( $link ) ) {
						continue;
					}
					?>
					<a class="brand-logo" href="<?php echo esc_url( $link ); ?>" aria-label="<?php echo esc_attr( $bt->name ); ?>">
						<span class="brand-wordmark"><?php echo esc_html( $bt->name ); ?></span>
					</a>
					<?php
				endforeach;
			endif;
			?>
		</div>
	</div>
</section>

<!-- ── SERVICE ───────────────────────────────────────────── -->
<section class="service" id="service">
	<div class="container service-grid">
		<div class="service-copy">
			<h2><?php esc_html_e( 'Breng uw horloge', 'maison-opus' ); ?><br/><?php esc_html_e( 'naar de werkbank.', 'maison-opus' ); ?></h2>
			<p class="service-lead">
				<?php esc_html_e( 'Een groot deel van ons werk is de zorg voor horloges — die van ons en van andere merken, vintage en modern. Onze horlogemaker onderzoekt uw horloge en brengt een offerte uit voordat er iets gebeurt.', 'maison-opus' ); ?>
			</p>
			<p class="service-lead">
				<?php esc_html_e( 'Wanneer wij een horloge innemen, ontvangt u op uw telefoon een innamepagina: foto\'s, een omschrijving van het werk en onze voorwaarden. U leest het en gaat akkoord — en uw akkoord wordt vastgelegd voordat het werk begint.', 'maison-opus' ); ?>
			</p>
			<a class="btn solid" href="<?php echo esc_url( $service_url ); ?>"><?php esc_html_e( 'Bekijk onze diensten', 'maison-opus' ); ?></a>
		</div>
		<ul class="service-list">
			<li><span><?php esc_html_e( 'Volledige revisie', 'maison-opus' ); ?></span><em><?php esc_html_e( 'op offerte', 'maison-opus' ); ?></em></li>
			<li><span><?php esc_html_e( 'Polijsten & afwerken', 'maison-opus' ); ?></span><em><?php esc_html_e( 'op offerte', 'maison-opus' ); ?></em></li>
			<li><span><?php esc_html_e( 'Glas & afdichting', 'maison-opus' ); ?></span><em><?php esc_html_e( '10 dagen', 'maison-opus' ); ?></em></li>
			<li><span><?php esc_html_e( 'Bandje plaatsen', 'maison-opus' ); ?></span><em><?php esc_html_e( 'dezelfde dag', 'maison-opus' ); ?></em></li>
			<li><span><?php esc_html_e( 'Batterij & afdichting', 'maison-opus' ); ?></span><em><?php esc_html_e( 'dezelfde dag', 'maison-opus' ); ?></em></li>
		</ul>
	</div>
</section>

<?php
