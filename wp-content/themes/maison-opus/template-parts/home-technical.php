<?php
/**
 * Front page — TECHNICAL skin. Ported from 04-technical.html.
 *
 * Blueprint hero (watch elevation + spec readouts) → headline strip →
 * calibre notes + spec grid → live catalogue (.piece cards) →
 * workshop service flow → brand index → closing line.
 * Spec-sheet aesthetic; all copy in Dutch. Wired to real WooCommerce data.
 *
 * @package maison-opus
 */

$theme_uri   = get_template_directory_uri();
$shop_url    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$service_cat = get_term_by( 'slug', 'service', 'product_cat' );
$service_url = $service_cat ? get_term_link( $service_cat ) : $shop_url;
$service_url = is_wp_error( $service_url ) ? $shop_url : $service_url;
$db_page     = get_page_by_path( 'database' );
$db_url      = $db_page ? get_permalink( $db_page ) : $shop_url;

$featured = opus_featured_products( 6 );
$count    = is_array( $featured ) ? count( $featured ) : 0;
?>

<!-- ── BLUEPRINT HERO ─────────────────────────────────────── -->
<section class="tech-hero">
	<div class="container">
		<div class="bp-grid">

			<!-- left readout -->
			<aside class="bp-side">
				<div class="ref-large"><em>Cal.</em><br/>OP</div>
				<div class="ref-line">
					<span class="l">In productie</span><span class="v">Sinds 2018</span>
				</div>

				<div class="block">
					<h4>Uurwerk</h4>
					<div class="data-row"><span class="l">Type</span><span class="v">Automaat</span></div>
					<div class="data-row"><span class="l">Diameter</span><span class="v">31,0 mm</span></div>
					<div class="data-row"><span class="l">Hoogte</span><span class="v">4,8 mm</span></div>
					<div class="data-row"><span class="l">Frequentie</span><span class="v">28.800 t/u</span></div>
					<div class="data-row"><span class="l">Gangreserve</span><span class="v">72 u</span></div>
					<div class="data-row"><span class="l">Stenen</span><span class="v">32</span></div>
					<div class="data-row"><span class="l">Afwerking</span><span class="v"><em>Met de hand</em></span></div>
				</div>

				<div class="block">
					<h4>Toleranties</h4>
					<div class="data-row"><span class="l">Gang (gem.)</span><span class="v">-2 / +4 s/d</span></div>
					<div class="data-row"><span class="l">Zes posities</span><span class="v">&plusmn;6 s/d</span></div>
					<div class="data-row"><span class="l">Amplitude</span><span class="v">&ge; 280&deg;</span></div>
				</div>
			</aside>

			<!-- center diagram -->
			<div class="bp-center">
				<span class="c0"></span><span class="c1"></span><span class="c2"></span><span class="c3"></span>
				<div class="bp-meta">TEK. &#8470; OP-A &#183; ELEVATIE &#183; 1:1</div>
				<div class="bp-meta-bot">MAISON &amp; OPUS &#183; ANTWERPEN &#183; SCHAAL 1:1</div>

				<svg class="bp-diagram" viewBox="0 0 700 660" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
					<defs>
						<pattern id="tgrid" width="40" height="40" patternUnits="userSpaceOnUse">
							<path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(19,87,168,0.10)" stroke-width="0.5" />
						</pattern>
						<pattern id="thatch" width="6" height="6" patternUnits="userSpaceOnUse" patternTransform="rotate(45)">
							<line x1="0" y1="0" x2="0" y2="6" stroke="rgba(20,20,15,0.35)" stroke-width="0.6" />
						</pattern>
					</defs>

					<rect width="700" height="660" fill="url(#tgrid)" />

					<!-- crosshair -->
					<line x1="0" y1="330" x2="700" y2="330" stroke="rgba(19,87,168,0.30)" stroke-width="0.5" stroke-dasharray="4 4" />
					<line x1="350" y1="0" x2="350" y2="660" stroke="rgba(19,87,168,0.30)" stroke-width="0.5" stroke-dasharray="4 4" />

					<g transform="translate(350 330)">
						<circle r="240" fill="none" stroke="#14140f" stroke-width="1.4" />
						<circle r="232" fill="none" stroke="#14140f" stroke-width="0.5" />
						<circle r="225" fill="#faf7ee" stroke="#14140f" stroke-width="0.8" />
						<circle r="208" fill="none" stroke="#14140f" stroke-width="0.5" />
						<circle r="200" fill="none" stroke="#14140f" stroke-width="0.3" stroke-dasharray="2 3" />
						<circle r="195" fill="#f1ede4" stroke="#14140f" stroke-width="0.5" />
						<circle r="178" fill="none" stroke="#14140f" stroke-width="0.4" />
						<circle r="160" fill="none" stroke="#14140f" stroke-width="0.4" />

						<!-- hour ticks -->
						<?php
						$ticks = '';
						for ( $i = 0; $i < 60; $i++ ) {
							if ( 0 === $i % 15 ) {
								continue;
							}
							$a   = ( $i / 60 ) * M_PI * 2 - M_PI / 2;
							$r1  = ( 0 === $i % 5 ) ? 160 : 168;
							$r2  = 175;
							$x1  = cos( $a ) * $r1;
							$y1  = sin( $a ) * $r1;
							$x2  = cos( $a ) * $r2;
							$y2  = sin( $a ) * $r2;
							$sw  = ( 0 === $i % 5 ) ? '1.5' : '0.5';
							$ticks .= sprintf(
								'<line x1="%.2f" y1="%.2f" x2="%.2f" y2="%.2f" stroke="#14140f" stroke-width="%s" stroke-linecap="round" />',
								$x1, $y1, $x2, $y2, $sw
							);
						}
						echo $ticks; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- numeric SVG, no user input.
						?>

						<g font-family="Newsreader, serif" font-size="22" font-weight="300" fill="#14140f" text-anchor="middle">
							<text x="0" y="-150">12</text>
							<text x="160" y="8">3</text>
							<text x="0" y="166">6</text>
							<text x="-160" y="8">9</text>
						</g>

						<text x="0" y="-80" font-family="Newsreader, serif" font-style="italic" font-size="16" fill="#1357a8" text-anchor="middle">Opus</text>
						<text x="0" y="-62" font-family="IBM Plex Mono, monospace" font-size="7" letter-spacing="3" fill="#5a5851" text-anchor="middle">CAL. OP-04</text>

						<!-- sub seconds -->
						<circle cx="0" cy="100" r="42" fill="#faf7ee" stroke="#14140f" stroke-width="0.5" />
						<?php
						$sub = '';
						for ( $i = 0; $i < 60; $i++ ) {
							$a   = ( $i / 60 ) * M_PI * 2 - M_PI / 2;
							$r1  = ( 0 === $i % 15 ) ? 33 : ( ( 0 === $i % 5 ) ? 35 : 37 );
							$r2  = 40;
							$x1  = cos( $a ) * $r1;
							$y1  = 100 + sin( $a ) * $r1;
							$x2  = cos( $a ) * $r2;
							$y2  = 100 + sin( $a ) * $r2;
							$sw  = ( 0 === $i % 15 ) ? '0.9' : '0.3';
							$sub .= sprintf(
								'<line x1="%.2f" y1="%.2f" x2="%.2f" y2="%.2f" stroke="#14140f" stroke-width="%s" />',
								$x1, $y1, $x2, $y2, $sw
							);
						}
						echo $sub; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- numeric SVG, no user input.
						?>
						<text x="0" y="76" font-family="IBM Plex Mono, monospace" font-size="6" letter-spacing="2" fill="#5a5851" text-anchor="middle">SECONDEN</text>

						<!-- hands -->
						<g transform="rotate(50)"><polygon points="-3,-145 3,-145 5,-15 -5,-15" fill="#14140f" /></g>
						<g transform="rotate(140)"><polygon points="-3,-110 3,-110 5,-15 -5,-15" fill="#14140f" /></g>
						<g transform="translate(0 100) rotate(60)">
							<rect x="-0.6" y="-30" width="1.2" height="38" fill="#c93418" />
							<circle r="2" fill="#14140f" />
						</g>
						<circle r="5" fill="#14140f" />
						<circle r="1.5" fill="#c93418" />

						<!-- crown -->
						<g transform="translate(240 0)">
							<rect x="0" y="-14" width="22" height="28" fill="url(#thatch)" stroke="#14140f" stroke-width="0.8" />
							<rect x="2" y="-10" width="18" height="20" fill="none" stroke="#14140f" stroke-width="0.4" />
						</g>
					</g>

					<!-- callouts -->
					<g font-family="IBM Plex Mono, monospace" font-size="9" letter-spacing="1.5" fill="#1357a8">
						<line x1="350" y1="90" x2="350" y2="40" stroke="#1357a8" stroke-width="0.5" />
						<line x1="350" y1="40" x2="600" y2="40" stroke="#1357a8" stroke-width="0.5" />
						<circle cx="350" cy="90" r="3" fill="none" stroke="#1357a8" stroke-width="1" />
						<text x="612" y="34">01 &#8212; KAST &#216; 39,00 mm</text>
						<text x="612" y="46" fill="#5a5851">    GEPOLIJST STAAL</text>
					</g>
					<g font-family="IBM Plex Mono, monospace" font-size="9" letter-spacing="1.5" fill="#1357a8">
						<line x1="550" y1="330" x2="650" y2="220" stroke="#1357a8" stroke-width="0.5" />
						<circle cx="555" cy="320" r="3" fill="none" stroke="#1357a8" stroke-width="1" />
						<text x="612" y="212">02 &#8212; LUNETTE</text>
						<text x="612" y="224" fill="#5a5851">    GESLEPEN</text>
					</g>
					<g font-family="IBM Plex Mono, monospace" font-size="9" letter-spacing="1.5" fill="#1357a8">
						<line x1="150" y1="330" x2="60" y2="220" stroke="#1357a8" stroke-width="0.5" />
						<line x1="60" y1="220" x2="240" y2="220" stroke="#1357a8" stroke-width="0.5" />
						<circle cx="155" cy="320" r="3" fill="none" stroke="#1357a8" stroke-width="1" />
						<text x="22" y="214">03 &#8212; WIJZERPLAAT</text>
						<text x="22" y="226" fill="#5a5851">    SECTOR &#183; LAK</text>
					</g>
					<g font-family="IBM Plex Mono, monospace" font-size="9" letter-spacing="1.5" fill="#1357a8">
						<line x1="608" y1="330" x2="660" y2="380" stroke="#1357a8" stroke-width="0.5" />
						<circle cx="600" cy="330" r="3" fill="none" stroke="#1357a8" stroke-width="1" />
						<text x="540" y="402">04 &#8212; KROON &#183; SCHROEFKROON</text>
						<text x="540" y="414" fill="#5a5851">          STAAL &#183; GEKARTELD</text>
					</g>
					<g font-family="IBM Plex Mono, monospace" font-size="9" letter-spacing="1.5" fill="#1357a8">
						<line x1="350" y1="475" x2="350" y2="600" stroke="#1357a8" stroke-width="0.5" />
						<line x1="350" y1="600" x2="600" y2="600" stroke="#1357a8" stroke-width="0.5" />
						<circle cx="350" cy="475" r="3" fill="none" stroke="#1357a8" stroke-width="1" />
						<text x="612" y="595">05 &#8212; KLEINE SECONDE @ 6U</text>
						<text x="612" y="607" fill="#5a5851">    RODE WIJZER</text>
					</g>
					<g font-family="IBM Plex Mono, monospace" font-size="9" letter-spacing="1.5" fill="#1357a8">
						<line x1="150" y1="540" x2="60" y2="600" stroke="#1357a8" stroke-width="0.5" />
						<line x1="60" y1="600" x2="240" y2="600" stroke="#1357a8" stroke-width="0.5" />
						<circle cx="160" cy="540" r="3" fill="none" stroke="#1357a8" stroke-width="1" />
						<text x="22" y="595">06 &#8212; HOORN TOT HOORN</text>
						<text x="22" y="607" fill="#5a5851">    46,20 mm</text>
					</g>

					<g stroke="#14140f" stroke-width="0.6">
						<line x1="110" y1="640" x2="110" y2="650" />
						<line x1="590" y1="640" x2="590" y2="650" />
						<line x1="110" y1="645" x2="240" y2="645" />
						<line x1="460" y1="645" x2="590" y2="645" />
					</g>
					<text x="350" y="649" font-family="IBM Plex Mono, monospace" font-size="9" letter-spacing="2" fill="#14140f" text-anchor="middle">39,00 mm</text>
				</svg>
			</div>

			<!-- right readout -->
			<aside class="bp-side">
				<div class="block">
					<h4>Kast</h4>
					<div class="data-row"><span class="l">Diameter</span><span class="v">39,00 mm</span></div>
					<div class="data-row"><span class="l">Hoorn tot hoorn</span><span class="v">46,20 mm</span></div>
					<div class="data-row"><span class="l">Dikte</span><span class="v">9,65 mm</span></div>
					<div class="data-row"><span class="l">Bandbreedte</span><span class="v">20,00 mm</span></div>
					<div class="data-row"><span class="l">Materiaal</span><span class="v">316L staal</span></div>
					<div class="data-row"><span class="l">Glas</span><span class="v">Saffier &#183; AR&#215;2</span></div>
					<div class="data-row"><span class="l">Waterdicht</span><span class="v">100 m</span></div>
				</div>

				<div class="block">
					<h4>Service</h4>
					<div class="data-row"><span class="l">Revisie</span><span class="v">Eigen atelier</span></div>
					<div class="data-row"><span class="l">Garantie</span><span class="v">2 jaar</span></div>
					<div class="data-row"><span class="l">Dossier</span><span class="v"><em>Ondertekend</em></span></div>
				</div>

				<div class="block">
					<h4>Voorraad</h4>
					<div class="data-row"><span class="l">Referenties</span><span class="v"><em><?php echo (int) $count; ?> stuks</em></span></div>
					<div class="data-row"><span class="l">Levertijd</span><span class="v">Direct</span></div>
					<div class="data-row"><span class="l">Inkoop</span><span class="v">Op afspraak</span></div>
				</div>
			</aside>

		</div>
	</div>
</section>

<!-- ── HEADLINE STRIP ─────────────────────────────────────── -->
<section class="tech-strip">
	<div class="container">
		<div class="strip-row">
			<div class="strip-ident">
				MAISON &amp; OPUS<br/>
				<span>Catalogus N&ordm; 2026</span>
			</div>
			<h1>Horloges &#8212; gekocht, verkocht<br/>en <em>met de hand hersteld.</em></h1>
			<div class="strip-tag">
				Onafhankelijk horlogehuis<br/>
				<b>Antwerpen</b>
			</div>
		</div>
	</div>
</section>

<!-- ── CALIBRE NOTES + SPEC GRID ──────────────────────────── -->
<section class="tech-cal">
	<div class="container">
		<div class="cal-grid">
			<div class="cal-copy">
				<p class="tech-label">Het atelier / notities van de werkbank</p>
				<h2>Een huis voor horloges,<br/><em>en voor hun onderhoud.</em></h2>
				<p>
					<strong>Maison &amp; Opus</strong> koopt, verkoopt en herstelt bijzondere
					horloges. Elke referentie wordt nagezien op de werkbank voordat zij in
					de collectie verschijnt &#8212; van afdichting tot regulatie in zes posities.
				</p>
				<p>
					Bij elk horloge hoort een <strong>ondertekend dossier</strong>: gang,
					amplitude en gangafwijking, gedateerd op de dag van oplevering. Wat wij
					verkopen, onderhouden wij ook &#8212; in eigen huis.
				</p>
				<a class="link-arrow" href="<?php echo esc_url( $shop_url ); ?>">Bekijk de collectie &#8594;</a>
			</div>

			<div class="specs-grid">
				<div class="spec-cell"><span class="num">01</span><div class="l">Frequentie</div><div class="v">28.800 <span class="unit">t/u</span></div></div>
				<div class="spec-cell"><span class="num">02</span><div class="l">Gangreserve</div><div class="v">72 <span class="unit">u</span></div></div>
				<div class="spec-cell"><span class="num">03</span><div class="l">Stenen</div><div class="v">32</div></div>
				<div class="spec-cell"><span class="num">04</span><div class="l">Onderdelen</div><div class="v">219</div></div>
				<div class="spec-cell"><span class="num">05</span><div class="l">Gemiddelde gang</div><div class="v">-2 / +4 <span class="unit">s/d</span></div></div>
				<div class="spec-cell"><span class="num">06</span><div class="l">Zes posities</div><div class="v"><em>&plusmn;6</em> <span class="unit">s/d</span></div></div>
				<div class="spec-cell"><span class="num">07</span><div class="l">Amplitude</div><div class="v">285&deg; <span class="unit">min.</span></div></div>
				<div class="spec-cell"><span class="num">08</span><div class="l">Garantie</div><div class="v" style="font-size:22px;padding-top:12px;"><em>2 jaar</em><br/>Op elk horloge</div></div>
			</div>
		</div>
	</div>
</section>

<!-- ── CATALOGUE (live pieces) ────────────────────────────── -->
<section class="tech-catalogue">
	<div class="container">
		<div class="tech-head">
			<div>
				<p class="tech-label">De collectie</p>
				<h2>Nu op <em>voorraad</em></h2>
			</div>
			<div class="count">Op voorraad &#8212; <b><?php echo (int) $count; ?> referenties</b><br/>
				<a class="tech-link" href="<?php echo esc_url( $shop_url ); ?>">Bekijk alle horloges &#8594;</a>
			</div>
		</div>

		<div class="pieces">
			<?php
			if ( $featured ) {
				$i = 0;
				foreach ( $featured as $product ) {
					opus_product_piece( $product, $i++ );
				}
			} else {
				echo '<p>De collectie wordt voorbereid.</p>';
			}
			?>
		</div>
	</div>
</section>

<!-- ── WORKSHOP FLOW ──────────────────────────────────────── -->
<section class="tech-workshop">
	<div class="container">
		<div class="ws-head">
			<h2>Het <em>atelier.</em><br/>Onderhoud &amp; restauratie.</h2>
			<p>
				Wij onderhouden elk horloge dat wij verkopen, en nemen per seizoen een
				beperkt aantal horloges van andere merken aan. Hieronder het serviceproces,
				van begin tot eind. Levertijden zijn ruim gerekend &#8212; meestal gaat het sneller.
			</p>
		</div>

		<div class="flow">
			<div class="flow-step">
				<div class="num">&#8212; STAP 01</div>
				<h3>Verzegeld <em>opgehaald</em></h3>
				<p>Verzekerde ophaling, bij aankomst gefotografeerd en getekend door de horlogemaker.</p>
				<div class="time">T+ 2&#8211;4 DAGEN</div>
			</div>
			<div class="flow-step">
				<div class="num">&#8212; STAP 02</div>
				<h3>Open &amp; offerte</h3>
				<p>Demontage, volledig fotodossier, geschreven offerte naar u gemaild.</p>
				<div class="time">T+ 4 DAGEN</div>
			</div>
			<div class="flow-step">
				<div class="num">&#8212; STAP 03</div>
				<h3>Werk aan de bank</h3>
				<p>Revisie van het uurwerk, afwerking waar nodig, afdichtingen, regulatie in 6 posities.</p>
				<div class="time">T+ 3&#8211;7 WEKEN</div>
			</div>
			<div class="flow-step">
				<div class="num">&#8212; STAP 04</div>
				<h3>QC &amp; <em>dossier</em></h3>
				<p>72-uurs testperiode. Gang, amplitude en gangafwijking geprint en ondertekend.</p>
				<div class="time">T+ 1 WEEK</div>
			</div>
			<div class="flow-step">
				<div class="num">&#8212; STAP 05</div>
				<h3>Geretourneerd</h3>
				<p>Verzegelde retourkoerier met certificaat, dossier en twee jaar garantie.</p>
				<div class="time">T+ 2&#8211;4 DAGEN</div>
			</div>
		</div>

		<div class="ws-cta">
			<p class="lead">
				<span class="mono-tag">&#8212; INNAME GEOPEND &#183; OP AFSPRAAK</span>
				Stuur ons uw horloge. Wij openen het,<br/>fotograferen het en <em>schrijven u terug.</em>
			</p>
			<a class="btn" href="<?php echo esc_url( $service_url ); ?>">Boek het atelier <span class="arr">&#8594;</span></a>
		</div>
	</div>
</section>

<!-- ── BRAND INDEX ────────────────────────────────────────── -->
<section class="tech-brands">
	<div class="container">
		<div class="tech-head">
			<div>
				<p class="tech-label">Merkenregister</p>
				<h2>Shop op <em>merk</em></h2>
			</div>
			<div class="count">
				<a class="tech-link" href="<?php echo esc_url( $db_url ); ?>">Volledige database &#8594;</a>
			</div>
		</div>

		<div class="brand-grid">
			<?php
			$bn = 1;
			foreach ( array( 'Rolex', 'Patek Philippe', 'Audemars Piguet' ) as $brand ) :
				$img  = opus_brand_image_url( $brand );
				$link = opus_brand_term_link( $brand );
				?>
				<a class="brand-cell" href="<?php echo esc_url( $link ); ?>">
					<span class="brand-img">
						<?php if ( $img ) : ?>
							<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $brand ); ?>" loading="lazy" />
						<?php endif; ?>
					</span>
					<span class="brand-foot">
						<span class="brand-name"><?php echo esc_html( $brand ); ?></span>
						<span class="brand-cta">0<?php echo (int) $bn; ?> &#8212; Ontdek &#8594;</span>
					</span>
				</a>
				<?php
				$bn++;
			endforeach;
			?>
		</div>
	</div>
</section>

<!-- ── CLOSING ────────────────────────────────────────────── -->
<section class="tech-closing">
	<div class="container">
		<p>De beste dag om een mooi horloge te kopen is <em>vandaag.</em></p>
	</div>
</section>
