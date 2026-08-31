<?php
/**
 * Page template — Horlogedatabase (the deep reference catalogue).
 * Auto-selected by WordPress for the page with slug "database".
 *
 * Server-renders the first page of results (for SEO / no-JS), then a small
 * inline script drives faceted filtering against /wp-json/opus/v1/watches.
 *
 * @package maison-opus
 */

get_header();

/* /database/{brand-slug}/ pre-scopes the whole page to one brand. */
$scoped_brand = opus_db_brand_from_slug( sanitize_key( get_query_var( 'opus_db_brand' ) ) );

$facets  = opus_watch_facets();
$initial = opus_watch_query(
	array(
		'per_page' => 24,
		'brands'   => $scoped_brand ? array( $scoped_brand ) : array(),
	)
);
$nf      = number_format_i18n( $facets['total'] );
?>

<main class="wdb container">

	<header class="wdb-head">
		<p class="wdb-eyebrow"><?php esc_html_e( 'De database', 'maison-opus' ); ?></p>
		<h1 class="wdb-title"><?php esc_html_e( 'Horlogedatabase', 'maison-opus' ); ?><?php echo $scoped_brand ? ' — ' . esc_html( $scoped_brand ) : ''; ?></h1>
		<p class="wdb-intro">
			<?php esc_html_e( 'Een doorzoekbare index van', 'maison-opus' ); ?> <?php echo esc_html( $nf ); ?> <?php esc_html_e( 'referenties van de acht huizen die wij voeren — techniek, maten en jaren op een rij. Een naslagwerk, los van wat er nu op voorraad ligt.', 'maison-opus' ); ?>
		</p>
	</header>

	<div class="wdb-layout">

		<!-- ── Filters ──────────────────────────────────────── -->
		<aside class="wdb-filters">
			<form id="wdb-form">
				<div class="wdb-fgroup">
					<label class="wdb-flabel" for="wdb-search"><?php esc_html_e( 'Zoeken', 'maison-opus' ); ?></label>
					<input type="search" id="wdb-search" name="search" placeholder="<?php esc_attr_e( 'Model, referentie of collectie…', 'maison-opus' ); ?>" />
				</div>

				<div class="wdb-fgroup">
					<span class="wdb-flabel"><?php esc_html_e( 'Merk', 'maison-opus' ); ?></span>
					<?php foreach ( $facets['brands'] as $b ) : ?>
						<label class="wdb-check">
							<input type="checkbox" name="brands" value="<?php echo esc_attr( $b['value'] ); ?>" <?php checked( $b['value'], $scoped_brand ); ?> />
							<span><?php echo esc_html( $b['value'] ); ?></span>
							<em><?php echo esc_html( $b['n'] ); ?></em>
						</label>
					<?php endforeach; ?>
				</div>

				<div class="wdb-fgroup">
					<span class="wdb-flabel"><?php esc_html_e( 'Materiaal', 'maison-opus' ); ?></span>
					<?php foreach ( $facets['materials'] as $m ) : ?>
						<label class="wdb-check">
							<input type="checkbox" name="materials" value="<?php echo esc_attr( $m['value'] ); ?>" />
							<span><?php echo esc_html( $m['value'] ); ?></span>
							<em><?php echo esc_html( $m['n'] ); ?></em>
						</label>
					<?php endforeach; ?>
				</div>

				<div class="wdb-fgroup">
					<label class="wdb-flabel" for="wdb-size"><?php esc_html_e( 'Kastmaat', 'maison-opus' ); ?></label>
					<select id="wdb-size" name="size">
						<option value=""><?php esc_html_e( 'Alle maten', 'maison-opus' ); ?></option>
						<option value="0-36"><?php esc_html_e( 'Tot 36 mm', 'maison-opus' ); ?></option>
						<option value="36-40">36 – 40 mm</option>
						<option value="40-44">40 – 44 mm</option>
						<option value="44-99"><?php esc_html_e( '44 mm en groter', 'maison-opus' ); ?></option>
					</select>
				</div>

				<div class="wdb-fgroup">
					<span class="wdb-flabel"><?php esc_html_e( 'Bouwjaar', 'maison-opus' ); ?></span>
					<div class="wdb-range">
						<input type="number" name="year_min" inputmode="numeric"
							placeholder="<?php echo esc_attr( $facets['year_min'] ); ?>" min="<?php echo esc_attr( $facets['year_min'] ); ?>" max="<?php echo esc_attr( $facets['year_max'] ); ?>" />
						<span>—</span>
						<input type="number" name="year_max" inputmode="numeric"
							placeholder="<?php echo esc_attr( $facets['year_max'] ); ?>" min="<?php echo esc_attr( $facets['year_min'] ); ?>" max="<?php echo esc_attr( $facets['year_max'] ); ?>" />
					</div>
				</div>

				<div class="wdb-fgroup">
					<label class="wdb-check">
						<input type="checkbox" name="limited" value="1" />
						<span><?php esc_html_e( 'Alleen gelimiteerd', 'maison-opus' ); ?></span>
					</label>
				</div>

				<button type="button" id="wdb-reset" class="wdb-reset"><?php esc_html_e( 'Filters wissen', 'maison-opus' ); ?></button>
			</form>
		</aside>

		<!-- ── Results ──────────────────────────────────────── -->
		<section class="wdb-results">
			<div class="wdb-resbar">
				<p class="wdb-count" id="wdb-count"><?php echo esc_html( number_format_i18n( $initial['total'] ) ); ?> <?php esc_html_e( 'resultaten', 'maison-opus' ); ?></p>
				<label class="wdb-sortwrap">
					<span><?php esc_html_e( 'Sorteer', 'maison-opus' ); ?></span>
					<select id="wdb-sort" name="sort">
						<option value="relevance"><?php esc_html_e( 'Aanbevolen', 'maison-opus' ); ?></option>
						<option value="name"><?php esc_html_e( 'Naam (A–Z)', 'maison-opus' ); ?></option>
						<option value="year_desc"><?php esc_html_e( 'Nieuwste eerst', 'maison-opus' ); ?></option>
						<option value="year_asc"><?php esc_html_e( 'Oudste eerst', 'maison-opus' ); ?></option>
						<option value="size_desc"><?php esc_html_e( 'Kastmaat (groot → klein)', 'maison-opus' ); ?></option>
						<option value="size_asc"><?php esc_html_e( 'Kastmaat (klein → groot)', 'maison-opus' ); ?></option>
					</select>
				</label>
			</div>

			<div class="wdb-grid" id="wdb-grid">
				<?php
				foreach ( $initial['items'] as $it ) {
					echo opus_watch_card_html( $it ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- assembled from esc_* helpers.
				}
				?>
			</div>

			<p class="wdb-empty" id="wdb-empty" hidden><?php esc_html_e( 'Geen horloges gevonden. Pas uw filters aan.', 'maison-opus' ); ?></p>

			<div class="wdb-more">
				<button type="button" id="wdb-more" class="btn"<?php echo $initial['pages'] > 1 ? '' : ' hidden'; ?>><?php esc_html_e( 'Meer laden', 'maison-opus' ); ?></button>
			</div>
		</section>

	</div>
</main>

<script>
( function () {
	var REST  = <?php echo wp_json_encode( esc_url_raw( rest_url( 'opus/v1/watches' ) ) ); ?>;
	var form  = document.getElementById( 'wdb-form' );
	var grid  = document.getElementById( 'wdb-grid' );
	var count = document.getElementById( 'wdb-count' );
	var empty = document.getElementById( 'wdb-empty' );
	var more  = document.getElementById( 'wdb-more' );
	var sort  = document.getElementById( 'wdb-sort' );
	var page  = 1, pages = 1, busy = false;

	function nf( n ) { return ( '' + n ).replace( /\B(?=(\d{3})+(?!\d))/g, '.' ); }
	function esc( s ) { var d = document.createElement( 'div' ); d.textContent = s == null ? '' : s; return d.innerHTML; }

	function params( p ) {
		var fd = new FormData( form );
		var q  = new URLSearchParams();
		q.set( 'page', p );
		q.set( 'per_page', 24 );
		q.set( 'sort', sort.value );
		var search = fd.get( 'search' ); if ( search ) { q.set( 'search', search ); }
		var brands = fd.getAll( 'brands' ); if ( brands.length ) { q.set( 'brands', brands.join( ',' ) ); }
		var mats = fd.getAll( 'materials' ); if ( mats.length ) { q.set( 'materials', mats.join( ',' ) ); }
		var size = fd.get( 'size' );
		if ( size ) { var b = size.split( '-' ); q.set( 'size_min', b[0] ); q.set( 'size_max', b[1] ); }
		if ( fd.get( 'year_min' ) ) { q.set( 'year_min', fd.get( 'year_min' ) ); }
		if ( fd.get( 'year_max' ) ) { q.set( 'year_max', fd.get( 'year_max' ) ); }
		if ( fd.get( 'limited' ) ) { q.set( 'limited', 1 ); }
		return q;
	}

	function card( it ) {
		var meta = [];
		if ( it.family ) { meta.push( it.family ); }
		if ( it.diameter_mm ) { meta.push( ( parseFloat( it.diameter_mm ) + '' ).replace( /\.00$/, '' ) + ' mm' ); }
		if ( it.material ) { meta.push( it.material ); }
		if ( it.produced_year ) { meta.push( it.produced_year ); }
		return '<a class="wdb-card" href="' + it.permalink + '">' +
			( Number( it.limited ) ? '<span class="wdb-badge">Gelimiteerd</span>' : '' ) +
			'<span class="wdb-card-brand">' + esc( it.brand ) + '</span>' +
			'<span class="wdb-card-name">' + esc( it.name ) + '</span>' +
			( it.reference ? '<span class="wdb-card-ref">' + esc( it.reference ) + '</span>' : '' ) +
			'<span class="wdb-card-meta">' + esc( meta.join( ' · ' ) ) + '</span></a>';
	}

	function fetchPage( p, append ) {
		if ( busy ) { return; }
		busy = true;
		grid.classList.add( 'is-loading' );
		fetch( REST + '?' + params( p ).toString() )
			.then( function ( r ) { return r.json(); } )
			.then( function ( d ) {
				page = d.page; pages = d.pages;
				count.textContent = nf( d.total ) + ' resultaten';
				var html = d.items.map( card ).join( '' );
				if ( append ) { grid.insertAdjacentHTML( 'beforeend', html ); }
				else { grid.innerHTML = html; }
				empty.hidden = d.total !== 0;
				more.hidden = page >= pages;
				busy = false;
				grid.classList.remove( 'is-loading' );
			} )
			.catch( function () { busy = false; grid.classList.remove( 'is-loading' ); } );
	}

	var t;
	function refresh() { clearTimeout( t ); t = setTimeout( function () { fetchPage( 1, false ); }, 220 ); }

	form.addEventListener( 'input', refresh );
	form.addEventListener( 'change', refresh );
	sort.addEventListener( 'change', function () { fetchPage( 1, false ); } );
	more.addEventListener( 'click', function () { fetchPage( page + 1, true ); } );
	document.getElementById( 'wdb-reset' ).addEventListener( 'click', function () {
		form.reset(); sort.value = 'relevance'; fetchPage( 1, false );
	} );
} )();
</script>

<?php
get_footer();
