<?php
/**
 * Plugin Name: Opus — maintenance headers
 * Description: Makes the LightStart maintenance page a proper 503 + noindex.
 *
 * LightStart (wp-maintenance-mode) has two ways to render maintenance mode. Its
 * classic template sends "503 Service Unavailable" + Retry-After, but the
 * "page" mode (a regular WP page set as the maintenance page) swaps the front
 * page and renders it as a normal 200 with Yoast's "index, follow". To search
 * engines that looks like the homepage was replaced by a maintenance notice.
 *
 * This restores the correct signal whenever a visitor who does not bypass
 * maintenance mode gets that page: 503, Retry-After, no caching, noindex.
 * Logged-in admins (who see the real site) are untouched.
 *
 * @package maison-opus
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the current request is being served the maintenance page.
 */
function opus_serving_maintenance_page() {
	if ( ! class_exists( 'WP_Maintenance_Mode' ) ) {
		return false;
	}
	$wpmm     = WP_Maintenance_Mode::get_instance();
	$settings = $wpmm->get_plugin_settings();
	if ( empty( $settings['general']['status'] ) || empty( $settings['design']['page_id'] ) ) {
		return false;
	}
	if ( $wpmm->check_user_role() || $wpmm->check_exclude() ) {
		return false;
	}
	$page_id = (int) $settings['design']['page_id'];
	return is_front_page() || in_array( get_queried_object_id(), array( $page_id, (int) apply_filters( 'wpml_object_id', $page_id, 'page', true ) ), true );
}

add_action(
	'template_redirect',
	function () {
		if ( ! opus_serving_maintenance_page() ) {
			return;
		}
		status_header( 503 );
		header( 'Retry-After: 3600' );
		nocache_headers();
		add_filter( 'wpseo_robots', fn() => 'noindex, nofollow' );
	},
	0
);

/*
 * LightStart's page mode forces show_on_front=page on every request so it can
 * swap in its maintenance page for visitors. Our homepage is the theme's
 * front-page.php (show_on_front=posts), so a "static page" setting without a
 * page means "use the theme's homepage" — for admins previewing during
 * maintenance, and after maintenance mode is switched off again.
 */
add_filter(
	'option_show_on_front',
	function ( $value ) {
		return ( 'page' === $value && ! (int) get_option( 'page_on_front' ) ) ? 'posts' : $value;
	}
);
