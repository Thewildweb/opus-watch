<?php
/**
 * Front page — NOCTURNE design.
 * Dark "Aurum Noir" boutique homepage ported from 03-nocturne.html, wired to
 * real WooCommerce data. Hero → feature tiles → collection → interlude
 * (manifest + specsheet) → shop-by-brand → atelier → closing line.
 * All copy in Dutch. Does NOT call get_header/get_footer.
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
<section class="nocturne-hero">
	<img class="hero-bg" src="<?php echo esc_url( $theme_uri . '/assets/images/hero-repair.jpg' ); ?>"
		alt="Een horlogemaker werkt bij kaarslicht aan de werkbank" fetchpriority="high" decoding="async" />
	<div class="container hero-inner">
		<div class="eyebrow-row">
			<span class="bar"></span>
			<span>Onafhankelijk horlogehuis</span>
			<span class="star">&#9670;</span>
			<span>Antwerpen &amp; Genève</span>
			<span class="bar"></span>
		</div>

		<h1 class="hero-title">
			De uren,
			<span class="it">in detail.</span>
			<span class="small">— Gekocht, verkocht en met de hand hersteld.</span>
		</h1>

		<div class="hero-cta">
			<a class="btn solid" href="<?php echo esc_url( $shop_url ); ?>">Bekijk de collectie <span>&rarr;</span></a>
			<a class="btn light" href="<?php echo esc_url( $service_url ); ?>">Onderhoud aanvragen</a>
		</div>

		<div class="hero-meta">
			<div>Selectie <b>Zorgvuldig samengesteld</b></div>
			<div>Atelier <b>In eigen huis</b></div>
			<div>Inkoop <b>Eerlijk &amp; discreet</b></div>
			<div>Verzending <b>Verzekerd, wereldwijd</b></div>
		</div>
	</div>
</section>

<!-- ── FEATURE TILES ─────────────────────────────────────── -->
<section class="band">
	<div class="container">
		<div class="tiles cols-3">
			<a class="tile" href="<?php echo esc_url( $shop_url ); ?>">
				<img src="<?php echo esc_url( $theme_uri . '/assets/images/tile-movement.jpg' ); ?>" alt="" loading="lazy" />
				<div class="tile-scrim"></div>
				<div class="tile-body">
					<p class="tile-eyebrow">De collectie</p>
					<h3 class="tile-title">Bijzondere horloges,<br/>op voorraad.</h3>
					<span class="tile-link">Bekijk alles &rarr;</span>
				</div>
			</a>

			<div class="tile editorial">
				<div class="tile-body">
					<p class="tile-eyebrow">Inkoop &amp; taxatie</p>
					<h3 class="tile-title">Wij kopen ook<br/>uw horloge.</h3>
					<p>Een mooi horloge te verkopen? Wij taxeren vrijblijvend en kopen
						rechtstreeks in — eerlijk, snel en discreet.</p>
					<a class="tile-link gilt" href="<?php echo esc_url( $service_url ); ?>">Vraag een taxatie aan &rarr;</a>
				</div>
			</div>

			<a class="tile" href="<?php echo esc_url( $service_url ); ?>">
				<img src="<?php echo esc_url( $theme_uri . '/assets/images/tile-buy.jpg' ); ?>" alt="" loading="lazy" />
				<div class="tile-scrim"></div>
				<div class="tile-body">
					<p class="tile-eyebrow">Atelier</p>
					<h3 class="tile-title">Onderhoud &amp;<br/>restauratie.</h3>
					<span class="tile-link">Onze diensten &rarr;</span>
				</div>
			</a>
		</div>
	</div>
</section>

<!-- ── COLLECTION ────────────────────────────────────────── -->
<section class="section" id="collection">
	<div class="container">
		<div class="section-head">
			<div>
				<p class="sect-eyebrow">De collectie · MMXXVI</p>
				<h2>Nu op voorraad,<br/><em>stuk voor stuk gekeurd.</em></h2>
			</div>
			<div class="lede">
				Een kleine, zorgvuldig samengestelde selectie. Elk horloge is in eigen huis
				gecontroleerd, gefotografeerd en — waar nodig — nagezien aan de werkbank
				voordat het bij u terechtkomt.
			</div>
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
		<div style="margin-top:56px;text-align:center;">
			<a class="btn" href="<?php echo esc_url( $shop_url ); ?>">Bekijk alle horloges <span>&rarr;</span></a>
		</div>
	</div>
</section>

<!-- ── INTERLUDE: manifest + specsheet ───────────────────── -->
<section class="interlude">
	<div class="container">
		<div class="interlude-grid">
			<div>
				<div class="label">— Over terughoudendheid</div>
				<h2>Weinig horloges,<br/><em>met aandacht</em><br/>behandeld.</h2>
				<div class="pullquote">
					"Er schuilt een zekere waardigheid in het zorgvuldig behandelen van enkele
					horloges per maand. Je kent ze stuk voor stuk. Je weet wie eraan gewerkt
					heeft. En je neemt de telefoon op als er iets is."
				</div>
				<div class="signature">Junior &nbsp;·&nbsp; Horlogemaker, Maison &amp; Opus</div>
			</div>
			<div class="specsheet">
				<div class="specsheet-title">Het atelier</div>
				<div class="specsheet-ref">Inname · revisie · restauratie</div>
				<table>
					<tr><td>Onderzoek</td><td>vóór elke offerte</td></tr>
					<tr><td>Volledige revisie</td><td>op offerte</td></tr>
					<tr><td>Polijsten &amp; afwerken</td><td>op offerte</td></tr>
					<tr><td>Glas &amp; afdichting</td><td>10 dagen</td></tr>
					<tr><td>Bandje plaatsen</td><td>dezelfde dag</td></tr>
					<tr><td>Batterij &amp; afdichting</td><td>dezelfde dag</td></tr>
					<tr><td>Innamepagina</td><td>op uw telefoon</td></tr>
					<tr><td>Verzending</td><td>verzekerd, wereldwijd</td></tr>
				</table>
			</div>
		</div>
	</div>
</section>

<!-- ── SHOP BY BRAND ─────────────────────────────────────── -->
<section class="band band-tint">
	<div class="container">
		<div class="section-head">
			<div>
				<p class="sect-eyebrow">Merken</p>
				<h2>Shop op merk</h2>
			</div>
			<div class="lede">
				Rolex, Patek Philippe en Audemars Piguet — onze meest gevraagde huizen,
				rechtstreeks uit de actuele voorraad.
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
						<span class="tile-link">Ontdek &rarr;</span>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- ── ATELIER / SERVICE ─────────────────────────────────── -->
<section class="service" id="service">
	<div class="container service-grid">
		<div class="service-copy">
			<p class="sect-eyebrow">Onderhoud &amp; restauratie</p>
			<h2>Breng uw horloge<br/><em>naar de werkbank.</em></h2>
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
			<a class="btn solid" href="<?php echo esc_url( $service_url ); ?>">Bekijk onze diensten <span>&rarr;</span></a>
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
