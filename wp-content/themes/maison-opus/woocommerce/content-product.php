<?php
/**
 * Product loop item — overridden to render the heritage "piece" card.
 * Override of woocommerce/content-product.php.
 *
 * @package maison-opus
 * @version 9.0.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}

$opus_index = (int) wc_get_loop_prop( 'loop' );
?>
<li <?php wc_product_class( 'piece-li', $product ); ?>>
	<?php opus_product_piece( $product, $opus_index ); ?>
</li>
