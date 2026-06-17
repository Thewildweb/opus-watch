<?php
/**
 * Front page — MODERN design.
 * Ported from 05-modern.html (modern brand / corporate-luxe direction)
 * and wired to real WooCommerce data. All copy in Dutch.
 *
 * Sections: announce → split hero → in-stock collection grid →
 * feature tiles → shop-by-brand → movement (dark) → service →
 * newsletter → closing line.
 *
 * @package maison-opus
 */

$theme_uri   = get_template_directory_uri();
$shop_url    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$service_cat = get_term_by( 'slug', 'service', 'product_cat' );
$service_url = $service_cat ? get_term_link( $service_cat ) : $shop_url;
$service_url = is_wp_error( $service_url ) ? $shop_url : $service_url;
$db_page     = get_page_by_path( 'database' );
$db_url      = $db_page ? get_permalink( $db_page ) : home_url( '/' );

$featured = opus_featured_products( 4 );
?>

<!-- ── ANNOUNCE ──────────────────────────────────────────── -->
<div class="m-announce">
	Wereldwijde verzekerde verzending en onderhoud in eigen atelier.
	<a href="<?php echo esc_url( $service_url ); ?>">Ontdek</a>
</div>

<!-- ── HERO (split) ──────────────────────────────────────── -->
<section class="m-hero">
	<div class="m-hero-text">
		<div>
			<p class="m-eyebrow">Onafhankelijk horlogehuis · sinds 2018</p>
			<h1 class="m-hero-title">
				Een horloge<br/>
				<span class="light">gemaakt voor de</span><br/>
				<span class="accent">lange termijn.</span>
			</h1>
			<p class="m-hero-desc">
				Opus koopt, verkoopt en herstelt bijzondere horloges. Een zorgvuldig
				samengestelde collectie, en volledig onderhoud met de hand in ons eigen
				atelier — eerlijk, vakkundig en discreet.
			</p>
			<div class="m-btn-row">
				<a class="btn solid" href="<?php echo esc_url( $shop_url ); ?>">
					Bekijk de collectie
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12 H19 M14 6 L19 12 L14 18"/></svg>
				</a>
				<a class="btn" href="<?php echo esc_url( $service_url ); ?>">
					Onderhoud aanvragen
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12 H19 M14 6 L19 12 L14 18"/></svg>
				</a>
			</div>
		</div>

		<div class="m-hero-meta">
			<div><span>Atelier</span><b>In eigen huis</b></div>
			<div><span>Taxatie</span><b>Vrijblijvend</b></div>
			<div><span>Verzending</span><b>Wereldwijd verzekerd</b></div>
		</div>
	</div>

	<div class="m-hero-img">
		<img src="<?php echo esc_url( $theme_uri . '/assets/images/hero-repair.jpg' ); ?>"
			alt="Een horlogemaker herstelt met de hand een horloge aan de werkbank"
			fetchpriority="high" decoding="async" />
		<div class="m-img-scrim"></div>
		<div class="m-img-meta">
			<div class="name">Onderhoud &amp; restauratie<small>In eigen atelier · met de hand</small></div>
			<div class="ref">Gekocht<br/>Verkocht<br/>Verzorgd</div>
		</div>
	</div>
</section>

<!-- ── COLLECTION GRID ───────────────────────────────────── -->
<section class="section">
	<div class="container">
		<div class="m-section-head">
			<div><div class="label">— Ontdek</div></div>
			<h2>Onze horloges<span class="light"> nu op voorraad.</span></h2>
			<a class="link-arrow" href="<?php echo esc_url( $shop_url ); ?>">Bekijk alles →</a>
		</div>

		<?php if ( $featured ) : ?>
			<div class="m-collection pieces">
				<?php
				$i = 0;
				foreach ( $featured as $product ) {
					opus_product_piece( $product, $i++ );
				}
				?>
			</div>
		<?php else : ?>
			<p>De collectie wordt voorbereid.</p>
		<?php endif; ?>
	</div>
</section>

<!-- ── FEATURE TILES ─────────────────────────────────────── -->
<section class="section m-families">
	<div class="container">
		<div class="m-section-head">
			<div><div class="label">— Het huis</div></div>
			<h2>Kopen, verkopen<span class="light"> en herstellen.</span></h2>
			<a class="link-arrow" href="<?php echo esc_url( $shop_url ); ?>">Naar de shop →</a>
		</div>

		<div class="m-tiles">
			<a class="m-tile" href="<?php echo esc_url( $shop_url ); ?>">
				<img src="<?php echo esc_url( $theme_uri . '/assets/images/tile-movement.jpg' ); ?>" alt="" loading="lazy" />
				<div class="m-tile-scrim"></div>
				<div class="m-tile-body">
					<p class="m-tile-eyebrow">De collectie</p>
					<h3 class="m-tile-title">Bijzondere horloges,<br/>op voorraad.</h3>
					<span class="m-tile-link">Bekijk alles →</span>
				</div>
			</a>

			<div class="m-tile editorial">
				<div class="m-tile-body">
					<p class="m-tile-eyebrow">Inkoop &amp; taxatie</p>
					<h3 class="m-tile-title">Wij kopen ook<br/>uw horloge.</h3>
					<p>Een mooi horloge te verkopen? Wij taxeren vrijblijvend en kopen
						rechtstreeks in — eerlijk, snel en discreet.</p>
					<a class="m-tile-link" href="<?php echo esc_url( $service_url ); ?>" style="margin-top:22px;">Vraag een taxatie aan →</a>
				</div>
			</div>

			<a class="m-tile" href="<?php echo esc_url( $service_url ); ?>">
				<img src="<?php echo esc_url( $theme_uri . '/assets/images/tile-buy.jpg' ); ?>" alt="" loading="lazy" />
				<div class="m-tile-scrim"></div>
				<div class="m-tile-body">
					<p class="m-tile-eyebrow">Atelier</p>
					<h3 class="m-tile-title">Onderhoud &amp;<br/>restauratie.</h3>
					<span class="m-tile-link">Onze diensten →</span>
				</div>
			</a>
		</div>
	</div>
</section>

<!-- ── SHOP BY BRAND ─────────────────────────────────────── -->
<section class="section">
	<div class="container">
		<div class="m-section-head">
			<div><div class="label">— Merken</div></div>
			<h2>Shop op merk.</h2>
			<a class="link-arrow" href="<?php echo esc_url( $shop_url ); ?>">Alle merken →</a>
		</div>

		<div class="m-tiles">
			<?php
			foreach ( array( 'Rolex', 'Patek Philippe', 'Audemars Piguet' ) as $brand ) :
				$img  = opus_brand_image_url( $brand );
				$link = opus_brand_term_link( $brand );
				?>
				<a class="m-tile m-tile-brand" href="<?php echo esc_url( $link ); ?>">
					<?php if ( $img ) : ?>
						<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $brand ); ?>" loading="lazy" />
					<?php endif; ?>
					<div class="m-tile-scrim"></div>
					<div class="m-tile-body">
						<p class="m-tile-eyebrow">Collectie</p>
						<h3 class="m-tile-title"><?php echo esc_html( $brand ); ?></h3>
						<span class="m-tile-link">Ontdek →</span>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- ── MOVEMENT (dark inverted) ──────────────────────────── -->
<section class="m-tech">
	<div class="container">
		<div class="m-tech-grid">
			<div>
				<div class="label">— Het vakmanschap</div>
				<h2>Met de hand verzorgd,<br/><span class="light">aan de</span> <span class="accent">werkbank.</span></h2>
				<p>
					Een groot deel van ons werk is de zorg voor horloges — die van ons en van
					andere merken, vintage en modern. Elk uurwerk wordt onderzocht, gereinigd,
					gesmeerd en geregeld voordat het uw pols weer siert.
				</p>
				<p>
					Junior, onze horlogemaker, brengt eerst een offerte uit. Pas na uw akkoord
					begint het werk — en dat akkoord leggen wij vast.
				</p>
				<div class="m-btn-row" style="margin-top:34px;">
					<a class="btn light" href="<?php echo esc_url( $service_url ); ?>">
						Bekijk onze diensten
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12 H19 M14 6 L19 12 L14 18"/></svg>
					</a>
				</div>
				<div class="m-specs">
					<div><span>Revisie</span><b>Op offerte</b></div>
					<div><span>Glas &amp; afdichting</span><b>10 dagen</b></div>
					<div><span>Batterij</span><b>Dezelfde dag</b></div>
					<div><span>Garantie</span><b>Op het werk</b></div>
				</div>
			</div>
			<div class="m-tech-vis">
				<img src="<?php echo esc_url( $theme_uri . '/assets/images/tile-movement.jpg' ); ?>"
					alt="Uurwerk van een horloge in detail" loading="lazy" />
			</div>
		</div>
	</div>
</section>

<!-- ── SERVICE ───────────────────────────────────────────── -->
<section class="m-service">
	<div class="container">
		<div class="m-service-grid">
			<div class="m-service-vis">
				<img src="<?php echo esc_url( $theme_uri . '/assets/images/tile-buy.jpg' ); ?>"
					alt="Horloge in onderhoud in het atelier" loading="lazy" />
			</div>
			<div>
				<div class="label">— Onderhoud &amp; restauratie</div>
				<h2>Zorg voor uw horloge,<br/><span class="light">voor</span> <span class="accent">het leven.</span></h2>
				<p>
					Wanneer wij een horloge innemen, ontvangt u op uw telefoon een innamepagina:
					foto's, een omschrijving van het werk en onze voorwaarden. U leest het, gaat
					akkoord — en uw akkoord wordt vastgelegd voordat het werk begint.
				</p>
				<div class="m-service-list">
					<div class="row"><span class="n">01</span><span class="name">Volledige revisie</span><span class="time">Op offerte</span></div>
					<div class="row"><span class="n">02</span><span class="name">Polijsten &amp; afwerken</span><span class="time">Op offerte</span></div>
					<div class="row"><span class="n">03</span><span class="name">Glas &amp; afdichting</span><span class="time">10 dagen</span></div>
					<div class="row"><span class="n">04</span><span class="name">Bandje &amp; batterij</span><span class="time">Dezelfde dag</span></div>
				</div>
				<div class="m-btn-row" style="margin-top:34px;">
					<a class="btn solid" href="<?php echo esc_url( $service_url ); ?>">
						Onderhoud aanvragen
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12 H19 M14 6 L19 12 L14 18"/></svg>
					</a>
				</div>
			</div>
		</div>
	</div>
</section>

<!-- ── NEWSLETTER ────────────────────────────────────────── -->
<section class="m-news">
	<div class="container">
		<div class="m-news-grid">
			<div>
				<h3>De Opus nieuwsbrief.</h3>
				<p>Als eerste op de hoogte van nieuw binnengekomen horloges, taxatiedagen en
					verhalen van de werkbank.</p>
			</div>
			<div>
				<form class="m-news-form" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" onsubmit="return false;">
					<input type="email" name="email" placeholder="E-mailadres" aria-label="E-mailadres" />
					<button type="submit">Inschrijven →</button>
				</form>
			</div>
		</div>
	</div>
</section>

<!-- ── CLOSING ───────────────────────────────────────────── -->
<section class="m-closing">
	<div class="container">
		<p>De beste dag om een mooi horloge te kopen is <b>vandaag.</b></p>
	</div>
</section>
