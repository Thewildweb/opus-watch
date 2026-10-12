<?php
/**
 * Service category — overview of all services + an onderhoud inquiry form.
 * Override of WooCommerce's archive for the "service" product category.
 *
 * @package maison-opus
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

/* Opens our <main class="container wc-main"><div class="section"> wrapper. */
do_action( 'woocommerce_before_main_content' );
?>

<div class="svc-intro">
	<div class="svc-intro__text">
		<?php if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>
			<h1 class="woocommerce-products-header__title page-title"><?php woocommerce_page_title(); ?></h1>
		<?php endif; ?>
		<?php
		$opus_term_desc = term_description();
		if ( $opus_term_desc ) {
			echo '<div class="term-description">' . wp_kses_post( wpautop( $opus_term_desc ) ) . '</div>';
		} else {
			echo '<p class="term-description">' . esc_html__( 'Onderhoud, revisie en restauratie in eigen atelier — voor uw horloge en dat van andere merken, vintage en modern. Onze horlogemaker onderzoekt uw horloge en brengt een offerte uit voordat er iets gebeurt.', 'maison-opus' ) . '</p>';
		}
		?>
		<a class="btn solid svc-intro__cta" href="#svcform"><?php esc_html_e( 'Onderhoud aanvragen', 'maison-opus' ); ?></a>
	</div>
	<figure class="svc-intro__media">
		<?php $opus_img = opus_theme_image( 'diensten_image', 'assets/images/watch-speedmaster.webp' ); ?>
		<img src="<?php echo esc_url( $opus_img['url'] ); ?>" <?php echo opus_image_dims( $opus_img ); // phpcs:ignore WordPress.Security.EscapeOutput -- integers only. ?>
			alt="<?php echo esc_attr__( 'Omega Speedmaster Professional chronograaf', 'maison-opus' ); ?>" loading="lazy" decoding="async" />
	</figure>
</div>

<?php
$opus_groups   = array(
	'onderhoud'     => __( 'Volledig onderhoud', 'maison-opus' ),
	'deelreparatie' => __( 'Deelreparaties', 'maison-opus' ),
);
$opus_rendered = false;
foreach ( $opus_groups as $opus_group_key => $opus_group_title ) :
	$opus_services = opus_service_products( $opus_group_key );
	if ( ! $opus_services ) {
		continue;
	}
	$opus_rendered = true;
	?>
	<section class="svc-group">
		<h2 class="svc-group__title"><?php echo esc_html( $opus_group_title ); ?></h2>
		<div class="svc-list">
			<?php
			foreach ( $opus_services as $opus_service ) :
				$opus_pid  = $opus_service->get_id();
				$opus_desc = $opus_service->get_short_description();
				$opus_desc = $opus_desc ? $opus_desc : $opus_service->get_description();
				$opus_desc = wp_strip_all_tags( $opus_desc );
				?>
				<a class="svc-item" href="<?php echo esc_url( get_permalink( $opus_pid ) ); ?>">
					<div class="svc-item__text">
						<h3 class="svc-item__title"><?php echo esc_html( $opus_service->get_name() ); ?></h3>
						<?php if ( $opus_desc ) : ?>
							<p class="svc-item__desc"><?php echo esc_html( $opus_desc ); ?></p>
						<?php endif; ?>
					</div>
					<div class="svc-item__meta">
						<span class="svc-item__price"><?php echo opus_service_price( $opus_service ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?></span>
						<span class="svc-item__link"><?php esc_html_e( 'Meer informatie', 'maison-opus' ); ?><span aria-hidden="true"> &rarr;</span></span>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
	</section>
<?php endforeach; ?>
<?php if ( ! $opus_rendered ) : ?>
	<p><?php esc_html_e( 'Er zijn nog geen diensten beschikbaar.', 'maison-opus' ); ?></p>
<?php endif; ?>

<?php
/* Taxatie — separate block under the services. */
$opus_tax_page = get_page_by_path( 'taxatie' );
$opus_tax_url  = $opus_tax_page ? get_permalink( $opus_tax_page ) : home_url( '/taxatie/' );
?>
<section class="svc-taxatie">
	<div class="svc-taxatie__text">
		<p class="zoek-eyebrow"><?php esc_html_e( 'Taxatie', 'maison-opus' ); ?></p>
		<h2 class="svc-taxatie__title"><?php esc_html_e( 'Wat is uw horloge waard?', 'maison-opus' ); ?></h2>
		<p class="svc-taxatie__sub"><?php esc_html_e( 'Voor verkoop, verzekering of gewoon uit nieuwsgierigheid: onze horlogemaker taxeert uw horloge met kennis van de actuele markt — vrijblijvend en kosteloos.', 'maison-opus' ); ?></p>
	</div>
	<a class="btn solid" href="<?php echo esc_url( $opus_tax_url ); ?>"><?php esc_html_e( 'Vraag een taxatie aan', 'maison-opus' ); ?></a>
</section>

<?php
opus_service_inquiry_form(
	array(
		'title' => __( 'Onderhoud aanvragen', 'maison-opus' ),
		'sub'   => __( 'Beschrijf uw horloge en kruis aan wat er moet gebeuren — wij reageren doorgaans binnen één werkdag.', 'maison-opus' ),
	)
);

/* Closes the wrapper. */
do_action( 'woocommerce_after_main_content' );

get_footer( 'shop' );
