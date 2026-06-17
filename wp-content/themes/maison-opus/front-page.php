<?php
/**
 * Front page — a real boutique homepage: photographic hero, trust strip,
 * the in-stock collection, and the service offer. Heritage feel, business shape.
 *
 * Hero image: "Man Repairing Wristwatch" by Rubas Maqbool (Pexels, free licence,
 * no attribution required). Stored at assets/images/hero-repair.jpg.
 *
 * @package maison-opus
 */

get_header();

$theme_uri   = get_template_directory_uri();
$shop_url    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$service_cat = get_term_by( 'slug', 'service', 'product_cat' );
$service_url = $service_cat ? get_term_link( $service_cat ) : $shop_url;
$service_url = is_wp_error( $service_url ) ? $shop_url : $service_url;
?>

<!-- ── HERO ──────────────────────────────────────────────── -->
<section class="lede">
	<img
		class="lede-img"
		src="<?php echo esc_url( $theme_uri . '/assets/images/hero-repair.jpg' ); ?>"
		alt="A watchmaker repairing a wristwatch by hand at the bench"
		fetchpriority="high"
		decoding="async"
	/>
	<div class="lede-scrim"></div>
	<div class="container lede-inner">
		<p class="lede-eyebrow">Onafhankelijk horlogehuis</p>
		<h1 class="lede-title">Horloges —<br/>gekocht, verkocht en<br/><em>met de hand hersteld.</em></h1>
		<p class="lede-sub">
			Opus biedt een zorgvuldig samengestelde selectie bijzondere horloges, en
			volledig onderhoud en restauratie in eigen atelier. Elk horloge wordt
			onderzocht, gedocumenteerd en vastgelegd.
		</p>
		<div class="lede-cta">
			<a class="btn solid" href="<?php echo esc_url( $shop_url ); ?>">Bekijk de collectie</a>
			<a class="btn light" href="<?php echo esc_url( $service_url ); ?>">Onderhoud aanvragen</a>
		</div>
	</div>
</section>

<!-- ── TRUST STRIP ───────────────────────────────────────── -->
<section class="marks">
	<div class="container marks-grid">
		<div class="mark-item">
			<div class="mark-label">Samengesteld</div>
			<p>Een kleine, zorgvuldig gekozen reeks bijzondere horloges — op voorraad en verzekerd verzonden.</p>
		</div>
		<div class="mark-item">
			<div class="mark-label">Eigen atelier</div>
			<p>Onderhoud, reparatie en restauratie door onze eigen horlogemaker, op offertebasis.</p>
		</div>
		<div class="mark-item">
			<div class="mark-label">Gedocumenteerd</div>
			<p>Elk horloge wordt onderzocht, gefotografeerd en vastgelegd — zodat u precies weet wat u bezit.</p>
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

<?php
get_footer();
