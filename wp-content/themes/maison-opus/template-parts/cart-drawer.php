<?php
/**
 * Mini-cart drawer — slides in after a watch is added to the cart.
 *
 * The inner .opus-mini-cart is refreshed through the WooCommerce cart
 * fragments API (see maison_opus_cart_fragments), so its contents stay
 * correct without a page reload.
 *
 * @package maison-opus
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
	return;
}
?>
<div class="opus-drawer" id="opus-cart-drawer" hidden>
	<div class="opus-drawer__scrim" data-opus-drawer-close></div>

	<aside class="opus-drawer__panel" role="dialog" aria-modal="true"
		aria-labelledby="opus-drawer-title" tabindex="-1">
		<header class="opus-drawer__head">
			<h2 class="opus-drawer__title display" id="opus-drawer-title">
				<?php esc_html_e( 'In uw winkelwagen', 'maison-opus' ); ?>
			</h2>
			<button type="button" class="opus-drawer__close" data-opus-drawer-close
				aria-label="<?php esc_attr_e( 'Sluiten', 'maison-opus' ); ?>">
				<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor"
					stroke-width="1.3" aria-hidden="true" focusable="false">
					<path d="M6 6l12 12M18 6L6 18" />
				</svg>
			</button>
		</header>

		<div class="opus-mini-cart"><?php woocommerce_mini_cart(); ?></div>

		<footer class="opus-drawer__foot">
			<button type="button" class="btn" data-opus-drawer-close>
				<?php esc_html_e( 'Verder kijken', 'maison-opus' ); ?>
			</button>
		</footer>
	</aside>
</div>
