<?php
/**
 * Single product — bespoke Opus boutique layout.
 *
 * Replaces WooCommerce's default summary hooks with an explicit two-column
 * hero (framed gallery + summary), a styled spec sheet parsed from the
 * imported description table, the remaining prose, and related products.
 *
 * Override of woocommerce/content-single-product.php.
 *
 * @package maison-opus
 * @version 9.0.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}

// Service products get their own bespoke layout (gallery, video, inquiry form).
if ( has_term( array( 'service', 'servicing' ), 'product_cat', $product->get_id() ) ) {
	include get_template_directory() . '/inc/service-single.php';
	return;
}

$pid     = $product->get_id();
$brands  = wp_get_post_terms( $pid, 'product_brand', array( 'fields' => 'names' ) );
$brand   = ( ! is_wp_error( $brands ) && $brands ) ? $brands[0] : '';
$data    = opus_product_spec_data( $product );
$specs   = $data['specs'];
$prose   = $data['prose'];
$contact = get_page_by_path( 'contact' );
$contact = $contact ? get_permalink( $contact ) : '';
$short   = $product->get_short_description();
?>
<div id="product-<?php echo esc_attr( $pid ); ?>" <?php wc_product_class( 'opus-product', $product ); ?>>

	<div class="product-hero">

		<div class="product-gallery-col">
			<?php
			/**
			 * Standard gallery + sale flash
			 * (woocommerce_show_product_sale_flash @10, woocommerce_show_product_images @20).
			 */
			do_action( 'woocommerce_before_single_product_summary' );
			?>
		</div>

		<div class="product-summary">
			<?php if ( $brand ) : ?>
				<p class="product-brand"><?php echo esc_html( $brand ); ?></p>
			<?php endif; ?>

			<h1 class="product-title"><?php echo esc_html( $product->get_name() ); ?></h1>

			<?php if ( $product->is_in_stock() ) : ?>
				<div class="product-price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
			<?php endif; ?>

			<?php if ( $short ) : ?>
				<div class="product-intro">
					<?php echo wp_kses_post( apply_filters( 'woocommerce_short_description', $short ) ); ?>
				</div>
			<?php endif; ?>

			<?php if ( $product->is_in_stock() && $product->is_purchasable() ) : ?>
				<div class="product-buy">
					<?php woocommerce_template_single_add_to_cart(); ?>
				</div>
			<?php elseif ( $product->is_in_stock() ) : ?>
				<?php
				// Price on request: no cart, ask instead (subject pre-filled).
				$opus_contact_id = $contact ? apply_filters( 'wpml_object_id', get_page_by_path( 'contact' )->ID, 'page', true ) : 0;
				?>
				<?php if ( $opus_contact_id ) : ?>
					<div class="product-buy product-buy--inquire">
						<a class="btn solid" href="<?php echo esc_url( add_query_arg( 'horloge', rawurlencode( $product->get_name() ), get_permalink( $opus_contact_id ) ) . '#contactform' ); ?>"><?php esc_html_e( 'Informeer naar dit horloge', 'maison-opus' ); ?></a>
					</div>
				<?php endif; ?>
			<?php else : ?>
				<?php
				$opus_zoek_pg  = get_page_by_path( 'zoekopdracht' );
				$opus_zoek_url = $opus_zoek_pg ? get_permalink( apply_filters( 'wpml_object_id', $opus_zoek_pg->ID, 'page', true ) ) : home_url( '/zoekopdracht/' );
				?>
				<div class="product-sold">
					<span class="product-sold__flag"><?php esc_html_e( 'Verkocht', 'maison-opus' ); ?></span>
					<p><?php esc_html_e( 'Dit horloge is inmiddels verkocht.', 'maison-opus' ); ?>
						<?php esc_html_e( 'Dan vinden wij het.', 'maison-opus' ); ?>
						<a href="<?php echo esc_url( $opus_zoek_url ); ?>"><?php esc_html_e( 'Plaats een zoekopdracht', 'maison-opus' ); ?></a></p>
				</div>
			<?php endif; ?>

			<ul class="product-assurances">
				<li><?php esc_html_e( 'Verzekerde verzending wereldwijd', 'maison-opus' ); ?></li>
				<li><?php esc_html_e( 'Authenticiteit gegarandeerd', 'maison-opus' ); ?></li>
				<?php $opus_svc_url = function_exists( 'opus_service_url_for_brand' ) ? opus_service_url_for_brand( $brand ) : ''; ?>
				<li>
					<?php if ( $opus_svc_url ) : ?>
						<a href="<?php echo esc_url( $opus_svc_url ); ?>"><?php esc_html_e( 'Onderhoud & revisie in eigen atelier', 'maison-opus' ); ?></a>
					<?php else : ?>
						<?php esc_html_e( 'Onderhoud & revisie in eigen atelier', 'maison-opus' ); ?>
					<?php endif; ?>
				</li>
			</ul>

			<?php if ( $contact ) : ?>
				<p class="product-help"><?php esc_html_e( 'Vragen over dit horloge, of liever eerst in het echt bekijken? Neem contact op.', 'maison-opus' ); ?>
					<a href="<?php echo esc_url( $contact ); ?>"><?php esc_html_e( 'Neem contact op', 'maison-opus' ); ?></a>.</p>
			<?php endif; ?>
		</div>

	</div>

	<?php if ( $specs ) : ?>
		<section class="product-specs">
			<h2 class="product-block-title"><?php esc_html_e( 'Specificaties', 'maison-opus' ); ?></h2>
			<dl class="spec-sheet">
				<?php foreach ( $specs as $label => $value ) : ?>
					<div class="spec-row">
						<dt><?php echo esc_html( $label ); ?></dt>
						<dd><?php echo esc_html( $value ); ?></dd>
					</div>
				<?php endforeach; ?>
			</dl>
		</section>
	<?php endif; ?>

	<?php if ( $prose ) : ?>
		<section class="product-story">
			<h2 class="product-block-title"><?php esc_html_e( 'Over dit horloge', 'maison-opus' ); ?></h2>
			<div class="product-story-body"><?php echo wp_kses_post( wpautop( $prose ) ); ?></div>
		</section>
	<?php endif; ?>

	<?php woocommerce_output_related_products(); ?>

</div>
