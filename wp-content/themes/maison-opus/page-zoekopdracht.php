<?php
/**
 * Page template — Zoekopdracht (find-me-this-watch request).
 * Auto-selected by WordPress for the page with slug "zoekopdracht".
 *
 * @package maison-opus
 */

get_header();

$status    = isset( $_GET['zoek'] ) ? sanitize_key( wp_unslash( $_GET['zoek'] ) ) : '';
$brands    = array( 'Rolex', 'Patek Philippe', 'Audemars Piguet', 'Cartier', 'Omega', 'Tudor', 'IWC', 'Vacheron Constantin', 'Ander merk' );
$states    = array( __( 'Maakt niet uit', 'maison-opus' ), __( 'Nieuw', 'maison-opus' ), __( 'Ongedragen', 'maison-opus' ), __( 'Zo goed als nieuw', 'maison-opus' ), __( 'Gedragen', 'maison-opus' ) );
?>

<main class="zoek">

	<!-- ── Intro ─────────────────────────────────────────────── -->
	<section class="zoek-hero">
		<div class="container zoek-hero-grid">
			<div class="zoek-hero-copy">
				<p class="zoek-eyebrow"><?php esc_html_e( 'Zoekopdracht', 'maison-opus' ); ?></p>
				<h1 class="zoek-title"><?php esc_html_e( 'Niet op voorraad?', 'maison-opus' ); ?><br/><em><?php esc_html_e( 'Dan vinden wij het.', 'maison-opus' ); ?></em></h1>
				<p class="zoek-lead">
					<?php esc_html_e( 'Zoekt u een specifiek horloge dat niet in onze actuele collectie staat? Via ons netwerk gaan we voor u op zoek. Eén vast aanspreekpunt, korte lijnen en heldere afspraken — en elk horloge wordt vóór levering door Junior, onze horlogemaker, gecontroleerd op echtheid en staat.', 'maison-opus' ); ?>
				</p>
				<a class="btn solid" href="#zoekform"><?php esc_html_e( 'Plaats een zoekopdracht', 'maison-opus' ); ?></a>
			</div>
			<div class="zoek-hero-media">
				<?php $opus_img = opus_theme_image( 'zoekopdracht_image', 'assets/images/hero-rolex.webp' ); ?>
				<img src="<?php echo esc_url( $opus_img['url'] ); ?>" <?php echo opus_image_dims( $opus_img ); // phpcs:ignore WordPress.Security.EscapeOutput -- integers only. ?>
					alt="<?php echo esc_attr__( 'Bijzonder horloge', 'maison-opus' ); ?>" loading="eager" decoding="async" />
			</div>
		</div>
	</section>

	<!-- ── Werkwijze ─────────────────────────────────────────── -->
	<section class="zoek-process">
		<div class="container">
			<div class="zoek-process-head">
				<p class="zoek-eyebrow"><?php esc_html_e( 'Uw wens, onze missie', 'maison-opus' ); ?></p>
				<h2><?php esc_html_e( 'Zo gaan we te werk', 'maison-opus' ); ?></h2>
			</div>
			<ol class="zoek-steps">
				<li>
					<span class="zoek-step-no">01</span>
					<h3><?php esc_html_e( 'U plaatst de zoekopdracht', 'maison-opus' ); ?></h3>
					<p><?php esc_html_e( 'Vul hieronder in welk horloge u zoekt — merk, referentie en eventuele wensen. Hoe specifieker, hoe gerichter we zoeken.', 'maison-opus' ); ?></p>
				</li>
				<li>
					<span class="zoek-step-no">02</span>
					<h3><?php esc_html_e( 'We bespreken uw wensen', 'maison-opus' ); ?></h3>
					<p><?php esc_html_e( 'U krijgt persoonlijk antwoord met een heldere prijsindicatie en een verwachte levertijd, zonder verplichtingen.', 'maison-opus' ); ?></p>
				</li>
				<li>
					<span class="zoek-step-no">03</span>
					<h3><?php esc_html_e( 'We starten de zoektocht', 'maison-opus' ); ?></h3>
					<p><?php esc_html_e( 'Binnen ons netwerk gaan we op zoek naar het juiste exemplaar en houden we u op de hoogte van de voortgang.', 'maison-opus' ); ?></p>
				</li>
				<li>
					<span class="zoek-step-no">04</span>
					<h3><?php esc_html_e( 'Controle en levering', 'maison-opus' ); ?></h3>
					<p><?php esc_html_e( 'Eenmaal binnen controleren we het horloge in eigen atelier. Daarna volledig verzekerd geleverd, of op te halen op afspraak.', 'maison-opus' ); ?></p>
				</li>
			</ol>
		</div>
	</section>

	<!-- ── Formulier ─────────────────────────────────────────── -->
	<section class="zoek-form-section" id="zoekform">
		<div class="container zoek-form-wrap">
			<div class="zoek-form-head">
				<p class="zoek-eyebrow"><?php esc_html_e( 'Het formulier', 'maison-opus' ); ?></p>
				<h2><?php esc_html_e( 'Vertel ons wat u zoekt', 'maison-opus' ); ?></h2>
				<p class="zoek-form-sub"><?php esc_html_e( 'Wij reageren doorgaans binnen één werkdag.', 'maison-opus' ); ?></p>
			</div>

			<?php if ( 'ok' === $status ) : ?>
				<p class="zoek-notice zoek-notice--ok">
					<?php esc_html_e( 'Dank u — uw zoekopdracht is ontvangen. We nemen zo snel mogelijk persoonlijk contact met u op.', 'maison-opus' ); ?>
				</p>
			<?php elseif ( 'error' === $status ) : ?>
				<p class="zoek-notice zoek-notice--error">
					<?php esc_html_e( 'Er ontbrak iets. Controleer uw naam, een geldig e-mailadres en het merk, en probeer het opnieuw.', 'maison-opus' ); ?>
				</p>
			<?php endif; ?>

			<form class="zoek-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="opus_zoekopdracht" />
				<?php wp_nonce_field( 'opus_zoekopdracht', 'opus_zoek_nonce' ); ?>

				<div class="zoek-fieldset">
					<h3 class="zoek-legend"><?php esc_html_e( 'Het horloge', 'maison-opus' ); ?></h3>
					<div class="zoek-grid">
						<label class="zoek-field">
							<span><?php esc_html_e( 'Merk', 'maison-opus' ); ?> <em>*</em></span>
							<div class="zoek-combo" data-combo="brand">
								<input type="text" name="merk" required autocomplete="off" placeholder="<?php esc_attr_e( 'Kies of typ een merk…', 'maison-opus' ); ?>"
									value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET['merk'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification ?>" />
								<ul class="zoek-suggest" role="listbox" hidden></ul>
							</div>
						</label>
						<label class="zoek-field">
							<span><?php esc_html_e( 'Model', 'maison-opus' ); ?></span>
							<div class="zoek-combo" data-combo="model">
								<input type="text" name="model" autocomplete="off" placeholder="<?php esc_attr_e( 'Begin te typen — bijv. Daytona, Royal Oak', 'maison-opus' ); ?>"
									value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET['model'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification ?>" />
								<ul class="zoek-suggest" role="listbox" hidden></ul>
							</div>
						</label>
						<label class="zoek-field">
							<span><?php esc_html_e( 'Referentienummer', 'maison-opus' ); ?></span>
							<input type="text" name="referentie" placeholder="bijv. 126500LN" />
						</label>
						<label class="zoek-field">
							<span><?php esc_html_e( 'Gewenste staat', 'maison-opus' ); ?></span>
							<select name="staat">
								<?php foreach ( $states as $s ) : ?>
									<option value="<?php echo esc_attr( $s ); ?>"><?php echo esc_html( $s ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<label class="zoek-field zoek-field--full">
							<span><?php esc_html_e( 'Budget (indicatie)', 'maison-opus' ); ?></span>
							<input type="text" name="budget" placeholder="bijv. € 15.000 – € 20.000" />
						</label>
						<label class="zoek-field zoek-field--full">
							<span><?php esc_html_e( 'Toelichting', 'maison-opus' ); ?></span>
							<textarea name="bericht" rows="4" placeholder="<?php esc_attr_e( 'Specifieke wijzerplaat, bouwjaar, papieren, of andere wensen.', 'maison-opus' ); ?>"></textarea>
						</label>
					</div>
				</div>

				<div class="zoek-fieldset">
					<h3 class="zoek-legend"><?php esc_html_e( 'Uw gegevens', 'maison-opus' ); ?></h3>
					<div class="zoek-grid">
						<label class="zoek-field">
							<span><?php esc_html_e( 'Naam', 'maison-opus' ); ?> <em>*</em></span>
							<input type="text" name="naam" required />
						</label>
						<label class="zoek-field">
							<span><?php esc_html_e( 'E-mailadres', 'maison-opus' ); ?> <em>*</em></span>
							<input type="email" name="email" required />
						</label>
						<label class="zoek-field zoek-field--full">
							<span><?php esc_html_e( 'Telefoon', 'maison-opus' ); ?></span>
							<input type="tel" name="telefoon" placeholder="<?php esc_attr_e( 'Optioneel — handig voor snel overleg', 'maison-opus' ); ?>" />
						</label>
					</div>
				</div>

				<div class="zoek-submit">
					<button type="submit" class="btn solid"><?php esc_html_e( 'Verstuur zoekopdracht', 'maison-opus' ); ?></button>
					<p class="zoek-fineprint"><?php esc_html_e( 'Uw gegevens gebruiken we uitsluitend om uw zoekopdracht te behandelen.', 'maison-opus' ); ?></p>
				</div>
			</form>
		</div>
	</section>

</main>

<script>
( function () {
	var REST   = <?php echo wp_json_encode( esc_url_raw( rest_url( 'opus/v1/suggest' ) ) ); ?>;
	var BRANDS = <?php echo wp_json_encode( array_values( $brands ) ); ?>;
	var form   = document.querySelector( '.zoek-form' );
	if ( ! form ) { return; }
	var brandIn = form.querySelector( '[data-combo=brand] input' );
	var modelIn = form.querySelector( '[data-combo=model] input' );
	var refIn   = form.querySelector( '[name=referentie]' );

	function esc( s ) { var d = document.createElement( 'div' ); d.textContent = s == null ? '' : s; return d.innerHTML; }
	function isBrand( v ) { v = ( v || '' ).toLowerCase(); return BRANDS.some( function ( b ) { return b.toLowerCase() === v; } ); }

	/* Generic combobox: input + suggestion list. `source(q, cb)` yields an
	   array of { html, pick } items; `pick()` runs when one is chosen. */
	function combo( wrap, source ) {
		var input = wrap.querySelector( 'input' );
		var list  = wrap.querySelector( '.zoek-suggest' );
		var items = [], active = -1, t;

		function close() { list.hidden = true; list.innerHTML = ''; active = -1; }
		function paint() {
			Array.prototype.forEach.call( list.children, function ( li, i ) {
				li.classList.toggle( 'is-active', i === active );
			} );
		}
		function render( sugs ) {
			items = sugs || [];
			if ( ! items.length ) { close(); return; }
			list.innerHTML = items.map( function ( s, i ) {
				return '<li role="option" data-i="' + i + '">' + s.html + '</li>';
			} ).join( '' );
			list.hidden = false; active = -1;
		}
		function choose( i ) { if ( items[ i ] ) { items[ i ].pick(); close(); } }

		input.addEventListener( 'input', function () {
			clearTimeout( t );
			t = setTimeout( function () { source( input.value.trim(), render ); }, 160 );
		} );
		input.addEventListener( 'focus', function () { source( input.value.trim(), render ); } );
		input.addEventListener( 'keydown', function ( e ) {
			if ( list.hidden ) { return; }
			if ( 'ArrowDown' === e.key ) { e.preventDefault(); active = Math.min( active + 1, items.length - 1 ); paint(); }
			else if ( 'ArrowUp' === e.key ) { e.preventDefault(); active = Math.max( active - 1, 0 ); paint(); }
			else if ( 'Enter' === e.key && active >= 0 ) { e.preventDefault(); choose( active ); }
			else if ( 'Escape' === e.key ) { close(); }
		} );
		// mousedown (not click) so it fires before the input blurs.
		list.addEventListener( 'mousedown', function ( e ) {
			var li = e.target.closest( 'li' );
			if ( li ) { e.preventDefault(); choose( parseInt( li.dataset.i, 10 ) ); }
		} );
		document.addEventListener( 'click', function ( e ) { if ( ! wrap.contains( e.target ) ) { close(); } } );
	}

	// Brand — filter the fixed list of houses as you type.
	combo( form.querySelector( '[data-combo=brand]' ), function ( q, cb ) {
		var ql = q.toLowerCase();
		cb( BRANDS.filter( function ( b ) { return ! ql || b.toLowerCase().indexOf( ql ) !== -1; } )
			.map( function ( b ) { return { html: esc( b ), pick: function () { brandIn.value = b; } }; } ) );
	} );

	// Model — live watch lookup from the database, scoped to the chosen brand.
	combo( form.querySelector( '[data-combo=model]' ), function ( q, cb ) {
		if ( q.length < 2 ) { cb( [] ); return; }
		var url = REST + '?q=' + encodeURIComponent( q );
		if ( isBrand( brandIn.value ) ) { url += '&brand=' + encodeURIComponent( brandIn.value ); }
		fetch( url ).then( function ( r ) { return r.json(); } ).then( function ( rows ) {
			cb( ( rows || [] ).map( function ( w ) {
				var html = '<span class="zs-name">' + esc( w.family || w.name ) + '</span>'
					+ ( w.reference ? '<span class="zs-ref">' + esc( w.reference ) + '</span>' : '' );
				return { html: html, pick: function () {
					modelIn.value = w.family || w.name;
					if ( refIn && w.reference ) { refIn.value = w.reference; }
					if ( ! brandIn.value && w.brand ) { brandIn.value = w.brand; }
				} };
			} ) );
		} ).catch( function () { cb( [] ); } );
	} );
} )();
</script>

<?php
get_footer();
