<?php
/**
 * Plugin Name: Opus — mail configuration
 * Description: Supplies the Brevo API key to FluentSMTP from the environment.
 *
 * FluentSMTP can read its credentials from a constant instead of the database
 * (key_store => wp_config), which keeps the key out of every database dump and
 * out of the admin UI. The constant cannot come from WORDPRESS_CONFIG_EXTRA,
 * because the WordPress image only writes wp-config.php when it does not
 * already exist — so it is defined here instead, from a container env var.
 *
 * @package maison-opus
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'FLUENTMAIL_SENDINBLUE_API_KEY' ) ) {
	$opus_brevo_key = getenv( 'BREVO_API_KEY' );
	if ( is_string( $opus_brevo_key ) && '' !== $opus_brevo_key ) {
		define( 'FLUENTMAIL_SENDINBLUE_API_KEY', $opus_brevo_key );
	}
	unset( $opus_brevo_key );
}
