<?php
/**
 * Front page — v2 (work in progress): clean, white, one green accent.
 * Banner slider → statement → in stock → sell / service split → brands → service.
 *
 * Only logged-in users who pick "v2" in the admin-bar Design menu see it;
 * styles live in assets/css/styles/v2.css (.v2-* classes).
 *
 * @package maison-opus
 */

$shop_url    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$service_url = opus_services_url();
$service_url = is_wp_error( $service_url ) ? $shop_url : $service_url;
$taxatie     = get_page_by_path( 'taxatie' );
$taxatie_url = $taxatie ? get_permalink( apply_filters( 'wpml_object_id', $taxatie->ID, 'page', true ) ) : $service_url;

// Four in-stock watches: featured first, topped up with the newest. Services
// are products too, so they are filtered out of both.
$v2_is_watch = fn( $p ) => ! has_term( array( 'service', 'servicing' ), 'product_cat', $p->get_id() );
$v2_pieces   = array_values( array_filter( opus_featured_products( 4 ), $v2_is_watch ) );
if ( count( $v2_pieces ) < 4 && function_exists( 'wc_get_products' ) ) {
	$v2_more   = wc_get_products(
		array(
			'status'       => 'publish',
			'stock_status' => 'instock',
			'limit'        => 24,
			'exclude'      => array_map( fn( $p ) => $p->get_id(), $v2_pieces ),
			'orderby'      => 'date',
			'order'        => 'DESC',
		)
	);
	$v2_pieces = array_slice( array_merge( $v2_pieces, array_filter( $v2_more, $v2_is_watch ) ), 0, 4 );
}
?>

<!-- ── HERO: the client's banner set as a slider ──────────── -->
<?php
// Banners from the client (scripts/build-banners.py). Wide ones on desktop,
// squares on phones. 'sku' links a slide to that watch while it is on the
// site; otherwise the slide links to the shop.
$v2_wide   = array(
	array( 2, '', 'Rolex Daytona' ),
	array( 4, 'OPUS-116506', 'Rolex Daytona 116506' ),
	array( 6, 'OPUS-126710BLRO', 'Rolex GMT-Master II “Pepsi”' ),
	array( 12, 'OPUS-7900V-110A-B334', 'Vacheron Constantin Overseas Dual Time' ),
	array( 13, 'OPUS-126621', 'Rolex Yacht-Master 126621' ),
	array( 3, 'OPUS-116528', 'Rolex Daytona 116528' ),
	array( 9, 'OPUS-116520', 'Rolex Daytona 116520' ),
	array( 8, 'OPUS-218206', 'Rolex Day-Date II 218206' ),
	array( 10, 'OPUS-126710GRNR', 'Rolex GMT-Master II 126710GRNR' ),
	array( 15, 'OPUS-16570', 'Rolex Explorer II 16570' ),
	array( 17, 'OPUS-214270', 'Rolex Explorer 214270' ),
	array( 19, '', 'Rolex Day-Date' ),
	array( 22, '', 'Rolex Datejust' ),
	array( 23, '', 'Rolex GMT-Master “Pepsi”' ),
	array( 25, '', 'Audemars Piguet Royal Oak' ),
);
$v2_square = array(
	array( 14, 'OPUS-126621', 'Rolex Yacht-Master 126621' ),
	array( 16, 'OPUS-16570', 'Rolex Explorer II 16570' ),
	array( 18, 'OPUS-214270', 'Rolex Explorer 214270' ),
	array( 20, '', 'Rolex Day-Date' ),
	array( 21, '', 'Rolex Datejust' ),
	array( 24, '', 'Rolex GMT-Master “Pepsi”' ),
	array( 26, '', 'Audemars Piguet Royal Oak' ),
);

/** Slide link: the watch in the current language if it is for sale, else the shop. */
$v2_slide_link = function ( $sku ) use ( $shop_url ) {
	$id = ( $sku && function_exists( 'wc_get_product_id_by_sku' ) ) ? wc_get_product_id_by_sku( $sku ) : 0;
	$id = $id ? apply_filters( 'wpml_object_id', $id, 'product', true ) : 0;
	$p  = $id ? wc_get_product( $id ) : null;
	return ( $p && 'publish' === $p->get_status() ) ? array( get_permalink( $id ), $p->get_name() ) : array( $shop_url, '' );
};

$v2_slider = function ( $slides, $variant ) use ( $v2_slide_link ) {
	$count = count( $slides );
	?>
	<div class="v2-slider v2-slider--<?php echo esc_attr( $variant ); ?>" data-v2-slider aria-roledescription="carousel" aria-label="<?php esc_attr_e( 'Uitgelichte horloges', 'maison-opus' ); ?>">
		<?php
		foreach ( $slides as $i => $slide ) :
			list( $n, $sku, $label ) = $slide;
			list( $href, $name )     = $v2_slide_link( $sku );
			$base                    = sprintf( 'assets/images/banners/banner-%02d-', $n );
			?>
			<a class="v2-slide<?php echo 0 === $i ? ' is-active' : ''; ?>" href="<?php echo esc_url( $href ); ?>" role="group" aria-roledescription="slide" aria-label="<?php echo esc_attr( ( $i + 1 ) . ' / ' . $count ); ?>"<?php echo 0 === $i ? '' : ' aria-hidden="true" tabindex="-1"'; ?>>
				<?php if ( 'wide' === $variant ) : ?>
					<img src="<?php echo esc_url( maison_opus_asset_uri( $base . '1920.webp' ) ); ?>"
						srcset="<?php echo esc_url( maison_opus_asset_uri( $base . '1200.webp' ) ); ?> 1200w, <?php echo esc_url( maison_opus_asset_uri( $base . '1920.webp' ) ); ?> 1920w"
						sizes="100vw" width="1920" height="768"
				<?php else : ?>
					<img src="<?php echo esc_url( maison_opus_asset_uri( $base . '800.webp' ) ); ?>" width="800" height="800"
				<?php endif; ?>
					alt="<?php echo esc_attr( $name ? $name : $label ); ?>" decoding="async"<?php echo 0 === $i ? ' fetchpriority="high"' : ' loading="lazy"'; ?> />
				<?php if ( $name ) : ?>
					<span class="v2-slide__cap"><?php echo esc_html( $name ); ?> <span aria-hidden="true">›</span></span>
				<?php endif; ?>
			</a>
		<?php endforeach; ?>
		<div class="v2-slider__nav">
			<button type="button" class="v2-slider__btn" data-v2-prev aria-label="<?php esc_attr_e( 'Vorige', 'maison-opus' ); ?>">‹</button>
			<span class="v2-slider__count" data-v2-count aria-live="polite">1 / <?php echo (int) $count; ?></span>
			<button type="button" class="v2-slider__btn" data-v2-next aria-label="<?php esc_attr_e( 'Volgende', 'maison-opus' ); ?>">›</button>
		</div>
	</div>
	<?php
};
?>
<section class="v2-hero">
	<?php
	$v2_slider( $v2_wide, 'wide' );
	$v2_slider( $v2_square, 'square' );
	?>
</section>

<!-- ── STATEMENT ─────────────────────────────────────────── -->
<section class="v2-intro">
	<div class="v2-wrap v2-wrap--narrow">
		<p class="v2-eyebrow"><?php esc_html_e( 'Online & op afspraak', 'maison-opus' ); ?></p>
		<h1 class="v2-intro__title"><?php esc_html_e( 'Bijzondere horloges, gekocht, verkocht en onderhouden.', 'maison-opus' ); ?></h1>
		<p class="v2-intro__text"><?php esc_html_e( 'Opus biedt een zorgvuldig samengestelde selectie bijzondere horloges, en volledig onderhoud en restauratie in eigen atelier.', 'maison-opus' ); ?></p>
		<div class="v2-actions v2-actions--center">
			<a class="v2-btn" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Bekijk de collectie', 'maison-opus' ); ?></a>
			<a class="v2-btn v2-btn--outline" href="<?php echo esc_url( $service_url ); ?>"><?php esc_html_e( 'Onderhoud aanvragen', 'maison-opus' ); ?></a>
		</div>
	</div>
</section>

<!-- ── IN STOCK ──────────────────────────────────────────── -->
<section class="v2-section" id="collection">
	<div class="v2-wrap">
		<div class="v2-head">
			<h2 class="v2-h2"><?php esc_html_e( 'Nu op voorraad', 'maison-opus' ); ?></h2>
			<a class="v2-link" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Bekijk alle horloges', 'maison-opus' ); ?></a>
		</div>
		<?php if ( $v2_pieces ) : ?>
			<div class="pieces v2-pieces">
				<?php
				foreach ( $v2_pieces as $i => $product ) {
					opus_product_piece( $product, $i );
				}
				?>
			</div>
		<?php else : ?>
			<p><?php esc_html_e( 'De collectie wordt voorbereid.', 'maison-opus' ); ?></p>
		<?php endif; ?>
	</div>
</section>

<!-- ── SELL / SERVICE ────────────────────────────────────── -->
<section class="v2-section v2-section--flush">
	<div class="v2-wrap v2-split">
		<a class="v2-feature" href="<?php echo esc_url( $taxatie_url ); ?>">
			<?php $opus_tile = opus_theme_image( 'home_tile_service', 'assets/images/banners/tile-sell.webp' ); ?>
			<div class="v2-feature__media"><img src="<?php echo esc_url( $opus_tile['url'] ); ?>" <?php echo opus_image_dims( $opus_tile ); // phpcs:ignore WordPress.Security.EscapeOutput -- integers only. ?> alt="" loading="lazy" decoding="async" /></div>
			<p class="v2-eyebrow"><?php esc_html_e( 'Verkopen', 'maison-opus' ); ?></p>
			<h3 class="v2-h3"><?php esc_html_e( 'Wij kopen ook uw horloge.', 'maison-opus' ); ?></h3>
			<span class="v2-link"><?php esc_html_e( 'Vraag een taxatie aan', 'maison-opus' ); ?></span>
		</a>
		<a class="v2-feature" href="<?php echo esc_url( $service_url ); ?>">
			<?php $opus_tile = opus_theme_image( 'home_tile_buy', 'assets/images/banners/tile-atelier.webp' ); ?>
			<div class="v2-feature__media"><img src="<?php echo esc_url( $opus_tile['url'] ); ?>" <?php echo opus_image_dims( $opus_tile ); // phpcs:ignore WordPress.Security.EscapeOutput -- integers only. ?> alt="" loading="lazy" decoding="async" /></div>
			<p class="v2-eyebrow"><?php esc_html_e( 'Atelier', 'maison-opus' ); ?></p>
			<h3 class="v2-h3"><?php esc_html_e( 'Onderhoud & restauratie.', 'maison-opus' ); ?></h3>
			<span class="v2-link"><?php esc_html_e( 'Onze diensten', 'maison-opus' ); ?></span>
		</a>
	</div>
</section>

<!-- ── BRANDS ────────────────────────────────────────────── -->
<section class="v2-section">
	<div class="v2-wrap">
		<h2 class="v2-h2 v2-center"><?php esc_html_e( 'Shop op merk', 'maison-opus' ); ?></h2>
		<div class="v2-brands">
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
					<a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $bt->name ); ?></a>
					<?php
				endforeach;
			endif;
			?>
		</div>
	</div>
</section>

<!-- ── SERVICE ───────────────────────────────────────────── -->
<section class="v2-section v2-section--tint" id="service">
	<div class="v2-wrap v2-service">
		<div>
			<p class="v2-eyebrow"><?php esc_html_e( 'Onderhoud', 'maison-opus' ); ?></p>
			<h2 class="v2-h2"><?php esc_html_e( 'Breng uw horloge naar de werkbank.', 'maison-opus' ); ?></h2>
			<p class="v2-lead">
				<?php esc_html_e( 'Een groot deel van ons werk is de zorg voor horloges — die van ons en van andere merken, vintage en modern. Onze horlogemaker onderzoekt uw horloge en brengt een offerte uit voordat er iets gebeurt.', 'maison-opus' ); ?>
			</p>
			<a class="v2-btn" href="<?php echo esc_url( $service_url ); ?>"><?php esc_html_e( 'Bekijk onze diensten', 'maison-opus' ); ?></a>
		</div>
		<ul class="v2-list">
			<li><span><?php esc_html_e( 'Volledige revisie', 'maison-opus' ); ?></span><em><?php esc_html_e( 'op offerte', 'maison-opus' ); ?></em></li>
			<li><span><?php esc_html_e( 'Polijsten & afwerken', 'maison-opus' ); ?></span><em><?php esc_html_e( 'op offerte', 'maison-opus' ); ?></em></li>
			<li><span><?php esc_html_e( 'Glas & afdichting', 'maison-opus' ); ?></span><em><?php esc_html_e( '10 dagen', 'maison-opus' ); ?></em></li>
			<li><span><?php esc_html_e( 'Bandje plaatsen', 'maison-opus' ); ?></span><em><?php esc_html_e( 'dezelfde dag', 'maison-opus' ); ?></em></li>
			<li><span><?php esc_html_e( 'Batterij & afdichting', 'maison-opus' ); ?></span><em><?php esc_html_e( 'dezelfde dag', 'maison-opus' ); ?></em></li>
		</ul>
	</div>
</section>
