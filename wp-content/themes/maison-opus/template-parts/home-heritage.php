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


$theme_uri   = get_template_directory_uri();
$shop_url    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$service_cat = get_term_by( 'slug', 'service', 'product_cat' );
$service_url = $service_cat ? get_term_link( $service_cat ) : $shop_url;
$service_url = is_wp_error( $service_url ) ? $shop_url : $service_url;
?>

<!-- ── HERO ──────────────────────────────────────────────── -->
<section class="lede">
	<img class="lede-img" src="<?php echo esc_url( $theme_uri . '/assets/images/hero-repair.jpg' ); ?>"
		alt="Een horlogemaker repareert met de hand een horloge aan de werkbank" fetchpriority="high" decoding="async" />
	<div class="lede-scrim"></div>
	<div class="container lede-inner">
		<p class="lede-eyebrow">Onafhankelijk horlogehuis</p>
		<h1 class="lede-title">Horloges —<br/>gekocht, verkocht en<br/><em>met de hand hersteld.</em></h1>
		<p class="lede-sub">
			Opus biedt een zorgvuldig samengestelde selectie bijzondere horloges, en
			volledig onderhoud en restauratie in eigen atelier.
		</p>
		<div class="lede-cta">
			<a class="btn solid" href="<?php echo esc_url( $shop_url ); ?>">Bekijk de collectie</a>
			<a class="btn light" href="<?php echo esc_url( $service_url ); ?>">Onderhoud aanvragen</a>
		</div>
	</div>
</section>

<!-- ── FEATURE TILES (image + text overlay) ──────────────── -->
<section class="band">
	<div class="container">
		<div class="tiles cols-3">
			<a class="tile" href="<?php echo esc_url( $shop_url ); ?>">
				<img src="<?php echo esc_url( $theme_uri . '/assets/images/tile-movement.jpg' ); ?>" alt="" loading="lazy" />
				<div class="tile-scrim"></div>
				<div class="tile-body">
					<p class="tile-eyebrow">De collectie</p>
					<h3 class="tile-title">Bijzondere horloges,<br/>op voorraad.</h3>
					<span class="tile-link">Bekijk alles →</span>
				</div>
			</a>

			<div class="tile editorial">
				<div class="tile-body">
					<p class="tile-eyebrow">Inkoop &amp; taxatie</p>
					<h3 class="tile-title">Wij kopen ook<br/>uw horloge.</h3>
					<p>Een mooi horloge te verkopen? Wij taxeren vrijblijvend en kopen
						rechtstreeks in — eerlijk, snel en discreet.</p>
					<a class="tile-link gilt" href="<?php echo esc_url( $service_url ); ?>">Vraag een taxatie aan →</a>
				</div>
			</div>

			<a class="tile" href="<?php echo esc_url( $service_url ); ?>">
				<img src="<?php echo esc_url( $theme_uri . '/assets/images/tile-buy.jpg' ); ?>" alt="" loading="lazy" />
				<div class="tile-scrim"></div>
				<div class="tile-body">
					<p class="tile-eyebrow">Atelier</p>
					<h3 class="tile-title">Onderhoud &amp;<br/>restauratie.</h3>
					<span class="tile-link">Onze diensten →</span>
				</div>
			</a>
		</div>
	</div>
</section>

<!-- ── COLLECTION ────────────────────────────────────────── -->
<section class="section" id="collection">
	<div class="container">
		<div class="sect-head">
			<div>
				<p class="sect-eyebrow">De collectie</p>
				<h2>Nu op voorraad</h2>
			</div>
			<a class="sect-link" href="<?php echo esc_url( $shop_url ); ?>">Bekijk alle horloges →</a>
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
				echo '<p class="italic">De collectie wordt voorbereid.</p>';
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
				<p class="sect-eyebrow">Merken</p>
				<h2>Shop op merk</h2>
			</div>
		</div>
		<div class="tiles cols-3">
			<?php
			foreach ( array( 'Rolex', 'Patek Philippe', 'Audemars Piguet' ) as $brand ) :
				$img  = opus_brand_image_url( $brand );
				$link = opus_brand_term_link( $brand );
				?>
				<a class="tile tile-brand" href="<?php echo esc_url( $link ); ?>">
					<?php if ( $img ) : ?>
						<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $brand ); ?>" loading="lazy" />
					<?php endif; ?>
					<div class="tile-scrim"></div>
					<div class="tile-body">
						<h3 class="tile-title"><?php echo esc_html( $brand ); ?></h3>
						<span class="tile-link">Ontdek →</span>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- ── SERVICE ───────────────────────────────────────────── -->
<section class="service" id="service">
	<div class="container service-grid">
		<div class="service-copy">
			<p class="sect-eyebrow gilt">Onderhoud &amp; restauratie</p>
			<h2>Breng uw horloge<br/>naar de werkbank.</h2>
			<p class="service-lead">
				Een groot deel van ons werk is de zorg voor horloges — die van ons en van
				andere merken, vintage en modern. Junior, onze horlogemaker, onderzoekt uw
				horloge en brengt een offerte uit voordat er iets gebeurt.
			</p>
			<p class="service-lead">
				Wanneer wij een horloge innemen, ontvangt u op uw telefoon een innamepagina:
				foto's, een omschrijving van het werk en onze voorwaarden. U leest het en
				gaat akkoord — en uw akkoord wordt vastgelegd voordat het werk begint.
			</p>
			<a class="btn solid" href="<?php echo esc_url( $service_url ); ?>">Bekijk onze diensten</a>
		</div>
		<ul class="service-list">
			<li><span>Volledige revisie</span><em>op offerte</em></li>
			<li><span>Polijsten &amp; afwerken</span><em>op offerte</em></li>
			<li><span>Glas &amp; afdichting</span><em>10 dagen</em></li>
			<li><span>Bandje plaatsen</span><em>dezelfde dag</em></li>
			<li><span>Batterij &amp; afdichting</span><em>dezelfde dag</em></li>
		</ul>
	</div>
</section>

<!-- ── CLOSING ───────────────────────────────────────────── -->
<section class="closing">
	<div class="container">
		<p>De beste dag om een mooi horloge te kopen is vandaag.</p>
	</div>
</section>

<?php
