<?php
/**
 * Contact — general contact form: stores messages as a private CPT and
 * e-mails the shop. Posts to admin-post.php.
 *
 * @package maison-opus
 */

defined( 'ABSPATH' ) || exit;

/** Private post type for contact messages. */
add_action(
	'init',
	function () {
		register_post_type(
			'opus_contact_message',
			array(
				'labels'          => array(
					'name'          => 'Berichten',
					'singular_name' => 'Bericht',
					'menu_name'     => 'Berichten',
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'menu_icon'       => 'dashicons-email',
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
			)
		);
	}
);

function opus_handle_contact() {
	$back = wp_get_referer() ? wp_get_referer() : home_url( '/contact/' );

	if ( ! isset( $_POST['opus_contact_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['opus_contact_nonce'] ), 'opus_contact' ) ) {
		wp_safe_redirect( add_query_arg( 'contact', 'error', $back ) );
		exit;
	}

	$naam      = sanitize_text_field( wp_unslash( $_POST['naam'] ?? '' ) );
	$email     = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$onderwerp = sanitize_text_field( wp_unslash( $_POST['onderwerp'] ?? '' ) );
	$bericht   = sanitize_textarea_field( wp_unslash( $_POST['bericht'] ?? '' ) );

	if ( '' === $naam || ! is_email( $email ) || '' === $bericht ) {
		wp_safe_redirect( add_query_arg( 'contact', 'error', $back ) );
		exit;
	}

	$post_id = wp_insert_post(
		array(
			'post_type'   => 'opus_contact_message',
			'post_status' => 'private',
			'post_title'  => $naam . ( $onderwerp ? ' — ' . $onderwerp : '' ),
		)
	);

	if ( $post_id && ! is_wp_error( $post_id ) ) {
		foreach ( compact( 'naam', 'email', 'onderwerp', 'bericht' ) as $k => $v ) {
			update_post_meta( $post_id, '_opus_' . $k, $v );
		}
		wp_mail(
			get_option( 'admin_email' ),
			'Nieuw contactbericht — ' . $naam,
			"Naam: {$naam}\nE-mail: {$email}\nOnderwerp: {$onderwerp}\n\n{$bericht}",
			array( 'Reply-To: ' . $naam . ' <' . $email . '>' )
		);
	}

	wp_safe_redirect( add_query_arg( 'contact', 'ok', remove_query_arg( 'contact', $back ) ) . '#contactform' );
	exit;
}
add_action( 'admin_post_opus_contact', 'opus_handle_contact' );
add_action( 'admin_post_nopriv_opus_contact', 'opus_handle_contact' );
