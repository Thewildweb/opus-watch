<?php
/**
 * Page template — Taxatie (watch appraisal request).
 * Auto-selected by WordPress for the page with slug "taxatie".
 * Modeled on the zoekopdracht page: hero → werkwijze → form.
 *
 * @package maison-opus
 */

get_header();

$theme_uri = get_template_directory_uri();
$status    = isset( $_GET['tax'] ) ? sanitize_key( wp_unslash( $_GET['tax'] ) ) : '';
$brands    = array( 'Rolex', 'Patek Philippe', 'Audemars Piguet', 'Cartier', 'Omega', 'Tudor', 'IWC', 'Vacheron Constantin', 'Ander merk' );
$states    = array( __( 'Nieuw', 'maison-opus' ), __( 'Ongedragen', 'maison-opus' ), __( 'Zo goed als nieuw', 'maison-opus' ), __( 'Gedragen', 'maison-opus' ), __( 'Met gebreken / defect', 'maison-opus' ) );
$papers    = array( __( 'Doos en papieren', 'maison-opus' ), __( 'Alleen doos', 'maison-opus' ), __( 'Alleen papieren', 'maison-opus' ), __( 'Geen van beide', 'maison-opus' ) );
$goals     = array( __( 'Verkoop', 'maison-opus' ), __( 'Verzekering', 'maison-opus' ), __( 'Nalatenschap / verdeling', 'maison-opus' ), __( 'Alleen nieuwsgierig', 'maison-opus' ) );
?>

<main class="zoek">

	<!-- ── Intro ─────────────────────────────────────────────── -->
	<section class="zoek-hero">
		<div class="container zoek-hero-grid">
			<div class="zoek-hero-copy">
				<p class="zoek-eyebrow"><?php esc_html_e( 'Taxatie', 'maison-opus' ); ?></p>
				<h1 class="zoek-title"><?php esc_html_e( 'Wat is uw horloge', 'maison-opus' ); ?><br/><em><?php esc_html_e( 'werkelijk waard?', 'maison-opus' ); ?></em></h1>
				<p class="zoek-lead">
					<?php esc_html_e( 'Of u nu overweegt te verkopen, een verzekering afsluit of gewoon nieuwsgierig bent: onze horlogemaker taxeert uw horloge met kennis van de actuele markt. Deel de gegevens hieronder en u ontvangt binnen één werkdag een eerste indicatie — vrijblijvend en kosteloos.', 'maison-opus' ); ?>
				</p>
				<a class="btn solid" href="#taxform"><?php esc_html_e( 'Vraag een taxatie aan', 'maison-opus' ); ?></a>
			</div>
			<div class="zoek-hero-media">
				<img src="<?php echo esc_url( opus_theme_image_url( 'taxatie_image', 'assets/images/hero-rolex.jpg' ) ); ?>"
					alt="<?php echo esc_attr__( 'Horloge onder loep in het atelier', 'maison-opus' ); ?>" loading="eager" decoding="async" />
			</div>
		</div>
	</section>

	<!-- ── Werkwijze ─────────────────────────────────────────── -->
	<section class="zoek-process">
		<div class="container">
			<div class="zoek-process-head">
				<p class="zoek-eyebrow"><?php esc_html_e( 'Kennis van zaken', 'maison-opus' ); ?></p>
				<h2><?php esc_html_e( 'Zo gaan we te werk', 'maison-opus' ); ?></h2>
			</div>
			<ol class="zoek-steps">
				<li>
					<span class="zoek-step-no">01</span>
					<h3><?php esc_html_e( 'U deelt de gegevens', 'maison-opus' ); ?></h3>
					<p><?php esc_html_e( 'Vul hieronder merk, model, referentie, bouwjaar en staat in. Hoe vollediger de gegevens, hoe scherper de taxatie.', 'maison-opus' ); ?></p>
				</li>
				<li>
					<span class="zoek-step-no">02</span>
					<h3><?php esc_html_e( 'Eerste indicatie', 'maison-opus' ); ?></h3>
					<p><?php esc_html_e( 'U ontvangt binnen één werkdag een eerste waarde-indicatie op basis van de actuele markt — geheel vrijblijvend.', 'maison-opus' ); ?></p>
				</li>
				<li>
					<span class="zoek-step-no">03</span>
					<h3><?php esc_html_e( 'Beoordeling in het atelier', 'maison-opus' ); ?></h3>
					<p><?php esc_html_e( 'Voor een definitieve taxatie bekijken we het horloge op afspraak in ons atelier en controleren we echtheid en staat.', 'maison-opus' ); ?></p>
				</li>
				<li>
					<span class="zoek-step-no">04</span>
					<h3><?php esc_html_e( 'Taxatie of bod', 'maison-opus' ); ?></h3>
					<p><?php esc_html_e( 'U ontvangt een onderbouwde taxatie — en overweegt u te verkopen, dan doen wij u desgewenst direct een bod.', 'maison-opus' ); ?></p>
				</li>
			</ol>
		</div>
	</section>

	<!-- ── Formulier ─────────────────────────────────────────── -->
	<section class="zoek-form-section" id="taxform">
		<div class="container zoek-form-wrap">
			<div class="zoek-form-head">
				<p class="zoek-eyebrow"><?php esc_html_e( 'Het formulier', 'maison-opus' ); ?></p>
				<h2><?php esc_html_e( 'Vertel ons over uw horloge', 'maison-opus' ); ?></h2>
				<p class="zoek-form-sub"><?php esc_html_e( 'Wij reageren doorgaans binnen één werkdag.', 'maison-opus' ); ?></p>
			</div>

			<?php if ( 'ok' === $status ) : ?>
				<p class="zoek-notice zoek-notice--ok">
					<?php esc_html_e( 'Dank u — uw taxatieaanvraag is ontvangen. We nemen zo snel mogelijk persoonlijk contact met u op.', 'maison-opus' ); ?>
				</p>
			<?php elseif ( 'error' === $status ) : ?>
				<p class="zoek-notice zoek-notice--error">
					<?php esc_html_e( 'Er ontbrak iets. Controleer uw naam, een geldig e-mailadres en het merk, en probeer het opnieuw.', 'maison-opus' ); ?>
				</p>
			<?php endif; ?>

			<form class="zoek-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="opus_taxatie" />
				<?php wp_nonce_field( 'opus_taxatie', 'opus_taxatie_nonce' ); ?>

				<div class="zoek-fieldset">
					<h3 class="zoek-legend"><?php esc_html_e( 'Het horloge', 'maison-opus' ); ?></h3>
					<div class="zoek-grid">
						<label class="zoek-field">
							<span><?php esc_html_e( 'Merk', 'maison-opus' ); ?> <em>*</em></span>
							<div class="zoek-combo" data-combo="brand">
								<input type="text" name="merk" required autocomplete="off" placeholder="<?php esc_attr_e( 'Kies of typ een merk…', 'maison-opus' ); ?>" />
								<ul class="zoek-suggest" role="listbox" hidden></ul>
							</div>
						</label>
						<label class="zoek-field">
							<span><?php esc_html_e( 'Model', 'maison-opus' ); ?></span>
							<div class="zoek-combo" data-combo="model">
								<input type="text" name="model" autocomplete="off" placeholder="<?php esc_attr_e( 'Begin te typen — bijv. Daytona, Royal Oak', 'maison-opus' ); ?>" />
								<ul class="zoek-suggest" role="listbox" hidden></ul>
							</div>
						</label>
						<label class="zoek-field">
							<span><?php esc_html_e( 'Referentienummer', 'maison-opus' ); ?></span>
							<input type="text" name="referentie" placeholder="bijv. 126500LN" />
						</label>
						<label class="zoek-field">
							<span><?php esc_html_e( 'Bouwjaar (circa)', 'maison-opus' ); ?></span>
							<input type="text" name="bouwjaar" inputmode="numeric" placeholder="bijv. 1998" />
						</label>
						<label class="zoek-field">
							<span><?php esc_html_e( 'Staat', 'maison-opus' ); ?></span>
							<select name="staat">
								<?php foreach ( $states as $s ) : ?>
									<option value="<?php echo esc_attr( $s ); ?>"><?php echo esc_html( $s ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<label class="zoek-field">
							<span><?php esc_html_e( 'Doos & papieren', 'maison-opus' ); ?></span>
							<select name="doos_papieren">
								<?php foreach ( $papers as $p ) : ?>
									<option value="<?php echo esc_attr( $p ); ?>"><?php echo esc_html( $p ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<label class="zoek-field">
							<span><?php esc_html_e( 'Doel van de taxatie', 'maison-opus' ); ?></span>
							<select name="doel">
								<?php foreach ( $goals as $g ) : ?>
									<option value="<?php echo esc_attr( $g ); ?>"><?php echo esc_html( $g ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<label class="zoek-field zoek-field--full">
							<span><?php esc_html_e( 'Toelichting', 'maison-opus' ); ?></span>
							<textarea name="bericht" rows="4" placeholder="<?php esc_attr_e( 'Bijzonderheden — servicehistorie, aankoopjaar, schade, of andere details.', 'maison-opus' ); ?>"></textarea>
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
					<button type="submit" class="btn solid"><?php esc_html_e( 'Vraag taxatie aan', 'maison-opus' ); ?></button>
					<p class="zoek-fineprint"><?php esc_html_e( 'Uw gegevens gebruiken we uitsluitend om uw taxatieaanvraag te behandelen.', 'maison-opus' ); ?></p>
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
