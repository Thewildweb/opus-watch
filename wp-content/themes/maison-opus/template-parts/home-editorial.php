<?php
/**
 * Front page — EDITORIAL design. Ported from 02-editorial.html.
 * Ticker → split hero (issue mark + featured watch) → manifesto →
 * archive/collection (real watches) → workshop band → closing line.
 * Newspaper/print language, Dutch copy. Real WooCommerce data.
 *
 * The router supplies get_header/get_footer — this part must NOT call them.
 *
 * @package maison-opus
 */

$theme_uri   = get_template_directory_uri();
$shop_url    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$service_cat = get_term_by( 'slug', 'service', 'product_cat' );
$service_url = $service_cat ? get_term_link( $service_cat ) : $shop_url;
$service_url = is_wp_error( $service_url ) ? $shop_url : $service_url;

$featured = opus_featured_products( 6 );

// Pull the hero piece from the first featured product where possible.
$hero_product = ( $featured && isset( $featured[0] ) ) ? $featured[0] : null;
$hero_img     = '';
$hero_name    = 'De selectie';
$hero_brand   = 'Maison & Opus';
$hero_price   = '';
$hero_link    = $shop_url;
if ( $hero_product ) {
	$hero_link  = get_permalink( $hero_product->get_id() );
	$hero_name  = $hero_product->get_name();
	$hero_price = $hero_product->get_price_html();
	$hb         = wp_get_post_terms( $hero_product->get_id(), 'product_brand', array( 'fields' => 'names' ) );
	$hero_brand = ( ! is_wp_error( $hb ) && $hb ) ? $hb[0] : $hero_brand;
	$hero_img   = $hero_product->get_image( 'woocommerce_single' );
}
?>

<!-- ── TICKER ─────────────────────────────────────────────── -->
<div class="ed-ribbon">
	<div class="ed-ribbon-inner">
		<span>Vol. 01</span><span>—</span>
		<span>Lente / MMXXVI</span><span>—</span>
		<span>Nieuw in de etalage</span><span>—</span>
		<span>Atelier neemt werk aan</span><span>—</span>
		<span>Verzekerde verzending wereldwijd</span><span>—</span>
		<span>Vol. 01</span><span>—</span>
		<span>Lente / MMXXVI</span><span>—</span>
		<span>Nieuw in de etalage</span><span>—</span>
		<span>Atelier neemt werk aan</span><span>—</span>
		<span>Verzekerde verzending wereldwijd</span><span>—</span>
	</div>
</div>

<!-- ── HERO ───────────────────────────────────────────────── -->
<section class="ed-hero">
	<div class="container">
		<div class="ed-hero-grid">
			<div class="ed-hero-left">
				<div>
					<div class="ed-issue">
						<span class="num">01</span>
						<div>
							Vol. Nº1<br/>
							Lente / MMXXVI<br/>
							Onafhankelijk sinds 1987
						</div>
					</div>
					<h1 class="ed-hero-title">
						Horloges,<br/>
						<em>met zorg.</em>
					</h1>
				</div>
				<div class="ed-hero-foot">
					<div class="ed-lede">
						<span class="ed-lede-label">Een woord van de werkbank</span>
						Wij verzamelen horloges zoals we ze zelf zouden willen dragen: in kleine
						aantallen, zorgvuldig uitgekozen, en met de hand verzorgd. Wat hier hangt
						is gekocht, gekeurd en — waar nodig — in eigen atelier hersteld.
					</div>
					<a class="btn solid" href="<?php echo esc_url( $shop_url ); ?>">
						Bekijk de collectie <span aria-hidden="true">&rarr;</span>
					</a>
				</div>
			</div>

			<a class="ed-hero-right" href="<?php echo esc_url( $hero_link ); ?>" aria-label="<?php echo esc_attr( $hero_name ); ?>">
				<div class="ed-caption ed-cc-tl">
					<div>Fig. 01</div>
					<div class="line"></div>
					<div><?php echo esc_html( $hero_brand ); ?></div>
				</div>
				<div class="ed-caption ed-cc-tr">
					<div>Gekeurd &amp; verzorgd</div>
					<div class="line"></div>
					<div>In eigen atelier</div>
				</div>
				<div class="ed-caption ed-cc-br">
					<div>Nu op voorraad</div>
					<div class="line"></div>
					<?php if ( $hero_price ) : ?>
						<span class="price"><?php echo wp_kses_post( $hero_price ); ?></span>
					<?php endif; ?>
				</div>
				<div class="ed-runningnum">01</div>
				<?php
				if ( $hero_img ) {
					// Re-tag the WC image with our hero class.
					echo str_replace( '<img ', '<img class="ed-hero-photo" ', wp_kses_post( $hero_img ) );
				} else {
					echo '<img class="ed-hero-photo" src="' . esc_url( $theme_uri . '/assets/images/tile-movement.jpg' ) . '" alt="" />';
				}
				?>
			</a>
		</div>
	</div>
</section>

<!-- ── MANIFESTO ──────────────────────────────────────────── -->
<section class="ed-manifesto">
	<div class="container">
		<p class="ed-manifesto-text">
			Wij verkopen <em>minder horloges</em>, keuren ze stuk voor stuk, en
			<span class="strike">jagen op snelle omzet</span> &mdash; we nemen liever
			<em>de tijd</em> om het goed te doen.
		</p>
		<div class="ed-manifesto-meta">
			<div><span>Sinds</span><strong>1987</strong></div>
			<div><span>Gekocht &amp; verkocht</span><strong>Met de hand</strong></div>
			<div><span>Onderhoud</span><strong>Eigen atelier</strong></div>
			<div><span>Verzending</span><strong>Verzekerd</strong></div>
		</div>
	</div>
</section>

<!-- ── ARCHIVE / COLLECTION ───────────────────────────────── -->
<section class="ed-archive" id="collection">
	<div class="container">
		<div class="ed-archive-head">
			<h2>De <em>collectie</em></h2>
			<a class="ed-archive-link" href="<?php echo esc_url( $shop_url ); ?>">Alle horloges &rarr;</a>
		</div>
		<div class="pieces">
			<?php
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

<!-- ── WORKSHOP / SERVICE BAND ────────────────────────────── -->
<section class="ed-workshop" id="service">
	<div class="container">
		<div class="ed-workshop-grid">
			<div>
				<div class="eyebrow">Het atelier / onderhoud</div>
				<h2>Breng uw horloge<br/><em>naar de werkbank.</em></h2>
				<p class="body">
					Een groot deel van ons werk is de zorg voor horloges — die van ons en van
					andere merken, vintage en modern. We onderzoeken uw horloge, fotograferen
					het, en brengen een offerte uit voordat er iets gebeurt.
				</p>
				<a class="btn gilt" href="<?php echo esc_url( $service_url ); ?>">
					Onderhoud aanvragen <span aria-hidden="true">&rarr;</span>
				</a>
			</div>
			<div class="ed-workshop-steps">
				<div class="ed-step">
					<div class="ed-step-num">01</div>
					<h3>Onder verzegeling</h3>
					<p>Verzekerd en getraceerd opgehaald. Bij aankomst gefotografeerd en geregistreerd.</p>
					<div class="step-mono">2–4 dagen</div>
				</div>
				<div class="ed-step">
					<div class="ed-step-num">02</div>
					<h3>Openen &amp; offerte</h3>
					<p>U ontvangt op uw telefoon een innamepagina: foto's, bevindingen en een vaste prijs.</p>
					<div class="step-mono">+4 dagen</div>
				</div>
				<div class="ed-step">
					<div class="ed-step-num">03</div>
					<h3>Met de hand hersteld</h3>
					<p>Revisie, afwerken, afdichting en regelen — één horloge tegelijk, zoals het hoort.</p>
					<div class="step-mono">3–7 weken</div>
				</div>
				<div class="ed-step">
					<div class="ed-step-num">04</div>
					<h3>Weer thuis</h3>
					<p>Met certificaat en garantie keert uw horloge verzekerd naar u terug.</p>
					<div class="step-mono">Bij retour</div>
				</div>
			</div>
		</div>
	</div>
</section>

<!-- ── CLOSING ────────────────────────────────────────────── -->
<section class="ed-closing">
	<div class="container">
		<p>De beste dag om een mooi horloge te kopen is <em>vandaag.</em></p>
	</div>
</section>
