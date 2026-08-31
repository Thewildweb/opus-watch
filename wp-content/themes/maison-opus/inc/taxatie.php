<?php
/**
 * Taxatie — "what is my watch worth" appraisal requests.
 * Mirrors the zoekopdracht flow: submissions are stored as a private CPT
 * (visible in wp-admin) and a notification is e-mailed to the shop.
 * Posts to admin-post.php; the form lives in page-taxatie.php.
 *
 * @package maison-opus
 */

defined( 'ABSPATH' ) || exit;

/** Private post type so the shop can review incoming appraisal requests. */
add_action(
	'init',
	function () {
		register_post_type(
			'opus_taxatie',
			array(
				'labels'          => array(
					'name'          => 'Taxaties',
					'singular_name' => 'Taxatie',
					'menu_name'     => 'Taxaties',
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'menu_icon'       => 'dashicons-money-alt',
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
			)
		);
	}
);

/** Fields we accept, with their human labels (used for storage + e-mail). */
function opus_taxatie_fields() {
	return array(
		'naam'          => 'Naam',
		'email'         => 'E-mailadres',
		'telefoon'      => 'Telefoon',
		'merk'          => 'Merk',
		'model'         => 'Model',
		'referentie'    => 'Referentienummer',
		'bouwjaar'      => 'Bouwjaar',
		'staat'         => 'Staat',
		'doos_papieren' => 'Doos & papieren',
		'doel'          => 'Doel van de taxatie',
		'bericht'       => 'Toelichting',
	);
}

/** Handle the taxatie form submission. */
function opus_handle_taxatie() {
	$back = wp_get_referer() ? wp_get_referer() : home_url( '/taxatie/' );

	if ( ! isset( $_POST['opus_taxatie_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['opus_taxatie_nonce'] ), 'opus_taxatie' ) ) {
		wp_safe_redirect( add_query_arg( 'tax', 'error', $back ) );
		exit;
	}

	$fields = opus_taxatie_fields();
	$data   = array();
	foreach ( $fields as $key => $label ) {
		$raw          = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
		$data[ $key ] = ( 'bericht' === $key ) ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
	}

	// Minimal validation: name, a valid e-mail, and a brand.
	if ( '' === $data['naam'] || ! is_email( $data['email'] ) || '' === $data['merk'] ) {
		wp_safe_redirect( add_query_arg( 'tax', 'error', $back ) );
		exit;
	}

	$title   = sprintf( '%s — %s %s', $data['naam'], $data['merk'], $data['model'] );
	$post_id = wp_insert_post(
		array(
			'post_type'   => 'opus_taxatie',
			'post_status' => 'private',
			'post_title'  => wp_strip_all_tags( $title ),
		)
	);

	if ( $post_id && ! is_wp_error( $post_id ) ) {
		foreach ( $data as $key => $value ) {
			update_post_meta( $post_id, '_opus_' . $key, $value );
		}

		// Best-effort notification to the shop.
		$lines = array( 'Nieuwe taxatieaanvraag via de website:', '' );
		foreach ( $fields as $key => $label ) {
			if ( '' !== $data[ $key ] ) {
				$lines[] = $label . ': ' . $data[ $key ];
			}
		}
		wp_mail(
			get_option( 'admin_email' ),
			'Nieuwe taxatieaanvraag — ' . $data['merk'] . ' ' . $data['model'],
			implode( "\n", $lines ),
			array( 'Reply-To: ' . $data['naam'] . ' <' . $data['email'] . '>' )
		);
	}

	wp_safe_redirect( add_query_arg( 'tax', 'ok', remove_query_arg( 'tax', $back ) ) . '#taxform' );
	exit;
}
add_action( 'admin_post_opus_taxatie', 'opus_handle_taxatie' );
add_action( 'admin_post_nopriv_opus_taxatie', 'opus_handle_taxatie' );
