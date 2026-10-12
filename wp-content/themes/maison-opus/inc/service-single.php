<?php
/**
 * Service product — single view. Leads with the video (or a 16:9 image when no
 * video is set), followed by the description, gallery, and an onderhoud inquiry
 * form pre-selecting this service.
 * Included from woocommerce/content-single-product.php for "service" products.
 *
 * @package maison-opus
 */

defined( 'ABSPATH' ) || exit;

global $product;

$pid        = $product->get_id();
$short      = $product->get_short_description();
$full       = $product->get_description();
$video      = get_post_meta( $pid, '_opus_video_url', true );

// Fallback image for the lead slot when there is no video.
$lead_img = has_post_thumbnail( $pid )
	? get_the_post_thumbnail_url( $pid, 'large' )
	: opus_theme_image_url( 'service_fallback', 'assets/images/hero-rolex.webp' );

$gallery_ids = $product->get_gallery_image_ids();
?>
<div id="product-<?php echo esc_attr( $pid ); ?>" <?php wc_product_class( 'opus-product svc-single', $product ); ?>>

	<header class="svc-head">
		<p class="zoek-eyebrow"><?php esc_html_e( 'Onderhoud & restauratie', 'maison-opus' ); ?></p>
		<h1 class="svc-title"><?php echo esc_html( $product->get_name() ); ?></h1>
		<?php if ( $short ) : ?>
			<div class="svc-lead"><?php echo wp_kses_post( apply_filters( 'woocommerce_short_description', $short ) ); ?></div>
		<?php endif; ?>
		<div class="svc-head-cta">
			<a class="btn solid" href="#svcform"><?php esc_html_e( 'Aanvraag doen', 'maison-opus' ); ?></a>
			<span class="svc-price"><?php echo opus_service_price( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?></span>
		</div>
	</header>

	<!-- Lead media: the video, or a 16:9 image fallback -->
	<figure class="svc-lead-media">
		<?php if ( $video ) : ?>
			<?php echo wp_oembed_get( esc_url( $video ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php else : ?>
			<img src="<?php echo esc_url( $lead_img ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" loading="eager" decoding="async" />
		<?php endif; ?>
	</figure>

	<?php if ( $full ) : ?>
		<div class="svc-content">
			<div class="svc-body">
				<h2 class="product-block-title"><?php esc_html_e( 'Over deze service', 'maison-opus' ); ?></h2>
				<div class="svc-body-text"><?php echo apply_filters( 'the_content', $full ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				<?php $opus_guide = function_exists( 'opus_service_guide_for_product' ) ? opus_service_guide_for_product( $product->get_slug() ) : null; ?>
				<?php if ( $opus_guide ) : ?>
					<p class="svc-guide"><a href="<?php echo esc_url( $opus_guide[0] ); ?>"><?php echo esc_html( $opus_guide[1] ); ?><span aria-hidden="true"> &rarr;</span></a></p>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( $gallery_ids ) : ?>
		<section class="svc-gallery-wrap">
			<h2 class="product-block-title svc-gallery-title"><?php esc_html_e( 'Uit het atelier', 'maison-opus' ); ?></h2>
			<div class="svc-gallery">
				<?php foreach ( $gallery_ids as $gid ) : ?>
					<figure><?php echo wp_get_attachment_image( $gid, 'large' ); ?></figure>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php opus_service_inquiry_form( array( 'preselect' => $pid, 'title' => __( 'Vraag deze service aan', 'maison-opus' ) ) ); ?>

</div>
<?php
