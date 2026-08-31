<?php
/**
 * Watch database — single reference detail view.
 * Loaded via template_include for /database/{brand}/{name}-{ref}-{id}/
 * (see watchdb.php for the route, watchdb-seo.php for the URL and metadata).
 *
 * @package maison-opus
 */

defined( 'ABSPATH' ) || exit;

$watch = opus_watch_current();

if ( ! $watch ) {
	status_header( 404 );
	get_header();
	echo '<main class="container section"><p class="italic">' . esc_html__( 'Dit horloge is niet gevonden.', 'maison-opus' ) . '</p></main>';
	get_footer();
	return;
}

status_header( 200 );

$specs   = json_decode( (string) $watch['specs_json'], true );
$specs   = is_array( $specs ) ? $specs : array();
$db_page = get_page_by_path( 'database' );
$db_url  = $db_page ? get_permalink( $db_page ) : home_url( '/database/' );
$stock   = opus_watch_instock_link( $watch['reference'] );

// Add a Dutch gloss to the most common spec labels; fall back to source label.
$label_nl = array(
	'Brand' => __( 'Merk', 'maison-opus' ), 'Family' => __( 'Collectie', 'maison-opus' ),
	'Reference' => __( 'Referentie', 'maison-opus' ), 'Name' => __( 'Naam', 'maison-opus' ),
	'Movement' => __( 'Uurwerk', 'maison-opus' ), 'Produced' => __( 'Productie', 'maison-opus' ),
	'Limited' => __( 'Gelimiteerd', 'maison-opus' ),
	'Material' => __( 'Materiaal', 'maison-opus' ), 'Materials' => __( 'Materiaal', 'maison-opus' ),
	'Coating' => __( 'Coating', 'maison-opus' ), 'Glass' => __( 'Glas', 'maison-opus' ),
	'Back' => __( 'Kastbodem', 'maison-opus' ), 'Bezel' => __( 'Bezel', 'maison-opus' ),
	'Shape' => __( 'Vorm', 'maison-opus' ), 'Diameter' => __( 'Diameter', 'maison-opus' ),
	'Height' => __( 'Hoogte', 'maison-opus' ), 'Lug Width' => __( 'Bandbreedte', 'maison-opus' ),
	'W/R' => __( 'Waterdichtheid', 'maison-opus' ),
	'Color' => __( 'Kleur', 'maison-opus' ), 'Finish' => __( 'Afwerking', 'maison-opus' ),
	'Hands' => __( 'Wijzers', 'maison-opus' ), 'Indexes' => __( 'Indexen', 'maison-opus' ),
	'Nickname' => __( 'Bijnaam', 'maison-opus' ),
);
$section_nl = array(
	'General' => __( 'Algemeen', 'maison-opus' ),
	'Case'    => __( 'Kast', 'maison-opus' ),
	'Dial'    => __( 'Wijzerplaat', 'maison-opus' ),
);

get_header();
?>

<main class="container wdetail">

	<nav class="wdetail-crumb">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> /
		<a href="<?php echo esc_url( $db_url ); ?>">Database</a> /
		<a href="<?php echo esc_url( home_url( '/database/' . sanitize_title( $watch['brand'] ) . '/' ) ); ?>"><?php echo esc_html( $watch['brand'] ); ?></a> /
		<span><?php echo esc_html( $watch['name'] ); ?></span>
	</nav>

	<header class="wdetail-head">
		<p class="wdetail-brand"><?php echo esc_html( $watch['brand'] ); ?></p>
		<h1 class="wdetail-name"><?php echo esc_html( $watch['name'] ); ?></h1>
		<?php if ( ! empty( $watch['reference'] ) ) : ?>
			<p class="wdetail-ref"><?php esc_html_e( 'Referentie', 'maison-opus' ); ?> <?php echo esc_html( $watch['reference'] ); ?></p>
		<?php endif; ?>

		<ul class="wdetail-keyfacts">
			<?php if ( ! empty( $watch['family'] ) ) : ?><li><span><?php esc_html_e( 'Collectie', 'maison-opus' ); ?></span><em><?php echo esc_html( $watch['family'] ); ?></em></li><?php endif; ?>
			<?php if ( ! empty( $watch['diameter_mm'] ) ) : ?><li><span><?php esc_html_e( 'Kastmaat', 'maison-opus' ); ?></span><em><?php echo esc_html( rtrim( rtrim( number_format( (float) $watch['diameter_mm'], 2 ), '0' ), '.' ) ); ?> mm</em></li><?php endif; ?>
			<?php if ( ! empty( $watch['material'] ) ) : ?><li><span><?php esc_html_e( 'Materiaal', 'maison-opus' ); ?></span><em><?php echo esc_html( $watch['material'] ); ?></em></li><?php endif; ?>
			<?php if ( ! empty( $watch['produced'] ) ) : ?><li><span><?php esc_html_e( 'Productie', 'maison-opus' ); ?></span><em><?php echo esc_html( $watch['produced'] ); ?></em></li><?php endif; ?>
		</ul>

		<?php if ( $stock ) : ?>
			<a class="btn solid wdetail-stock" href="<?php echo esc_url( $stock ); ?>"><?php esc_html_e( 'Nu op voorraad — bekijk in de shop', 'maison-opus' ); ?></a>
		<?php endif; ?>
	</header>

	<?php if ( $specs ) : ?>
		<div class="wdetail-specs">
			<?php foreach ( $specs as $section => $rows ) : ?>
				<?php if ( ! is_array( $rows ) || ! $rows ) { continue; } ?>
				<section class="wdetail-specblock">
					<h2 class="product-block-title"><?php echo esc_html( isset( $section_nl[ $section ] ) ? $section_nl[ $section ] : $section ); ?></h2>
					<dl class="spec-sheet">
						<?php foreach ( $rows as $label => $value ) : ?>
							<?php if ( '' === (string) $value ) { continue; } ?>
							<div class="spec-row">
								<dt><?php echo esc_html( isset( $label_nl[ $label ] ) ? $label_nl[ $label ] : $label ); ?></dt>
								<dd><?php echo esc_html( $value ); ?></dd>
							</div>
						<?php endforeach; ?>
					</dl>
				</section>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<p class="wdetail-source">
		<?php esc_html_e( 'Gegevens ter referentie, samengesteld uit openbare bronnen.', 'maison-opus' ); ?>
		<a href="<?php echo esc_url( $db_url ); ?>"><?php esc_html_e( '← Terug naar de database', 'maison-opus' ); ?></a>
	</p>

</main>

<?php
get_footer();
