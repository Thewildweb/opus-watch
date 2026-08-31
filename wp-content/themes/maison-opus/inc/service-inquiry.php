<?php
/**
 * Service / onderhoud — inquiry form, submission storage, and a per-product
 * video field. Shared by the services overview and each service product page.
 *
 * @package maison-opus
 */

defined( 'ABSPATH' ) || exit;

/**
 * Public service products (excludes the internal €5 agreement product).
 *
 * @param string $group Optional _opus_service_group filter ('onderhoud'|'deelreparatie').
 */
function opus_service_products( $group = '' ) {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return array();
	}
	$products = wc_get_products(
		array(
			'status'    => 'publish',
			'limit'     => -1,
			'orderby'   => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'tax_query' => array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					'terms'    => array( 'service', 'servicing' ),
				),
			),
		)
	);
	return array_filter(
		$products,
		function ( $p ) use ( $group ) {
			if ( 'OPUS-AGREEMENT' === $p->get_sku() ) {
				return false;
			}
			return ! $group || get_post_meta( $p->get_id(), '_opus_service_group', true ) === $group;
		}
	);
}

/**
 * Display price for a service: the free-text _opus_price_display meta
 * ("Vanaf €450", "Op aanvraag"), falling back to the Woo price / "Op offerte".
 * Returns safe HTML.
 */
function opus_service_price( $product ) {
	$display = get_post_meta( $product->get_id(), '_opus_price_display', true );
	if ( $display ) {
		return esc_html( $display );
	}
	$html = $product->get_price_html();
	return $html ? wp_kses_post( $html ) : esc_html__( 'Op offerte', 'maison-opus' );
}

/** Brands offered, plus an escape hatch. */
function opus_service_brands() {
	return array( 'Rolex', 'Patek Philippe', 'Audemars Piguet', 'Cartier', 'Omega', 'Tudor', 'IWC', 'Vacheron Constantin', __( 'Ander merk', 'maison-opus' ) );
}

/**
 * Render the onderhoud inquiry form.
 *
 * @param array $args preselect (service product id to pre-tick), eyebrow, title, sub.
 */
function opus_service_inquiry_form( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'preselect' => 0,
			'eyebrow'   => __( 'Onderhoud aanvragen', 'maison-opus' ),
			'title'     => __( 'Vraag uw onderhoud aan', 'maison-opus' ),
			'sub'       => __( 'Beschrijf uw horloge en wat er moet gebeuren — wij reageren doorgaans binnen één werkdag.', 'maison-opus' ),
		)
	);
	$status   = isset( $_GET['svc'] ) ? sanitize_key( wp_unslash( $_GET['svc'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$services = opus_service_products();
	$brands   = opus_service_brands();
	?>
	<section class="zoek-form-section" id="svcform">
		<div class="container zoek-form-wrap">
			<div class="zoek-form-head">
				<p class="zoek-eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p>
				<h2><?php echo esc_html( $args['title'] ); ?></h2>
				<p class="zoek-form-sub"><?php echo esc_html( $args['sub'] ); ?></p>
			</div>

			<?php if ( 'ok' === $status ) : ?>
				<p class="zoek-notice zoek-notice--ok"><?php esc_html_e( 'Dank u — uw aanvraag is ontvangen. We nemen zo snel mogelijk persoonlijk contact met u op.', 'maison-opus' ); ?></p>
			<?php elseif ( 'error' === $status ) : ?>
				<p class="zoek-notice zoek-notice--error"><?php esc_html_e( 'Er ontbrak iets. Controleer uw naam, een geldig e-mailadres en ten minste één service.', 'maison-opus' ); ?></p>
			<?php endif; ?>

			<form class="zoek-form svc-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="opus_service_inquiry" />
				<?php wp_nonce_field( 'opus_service_inquiry', 'opus_svc_nonce' ); ?>

				<div class="zoek-fieldset">
					<h3 class="zoek-legend"><?php esc_html_e( 'Welke service(s)?', 'maison-opus' ); ?></h3>
					<?php if ( $services ) : ?>
						<div class="svc-services">
							<?php foreach ( $services as $s ) : ?>
								<label class="svc-check">
									<input type="checkbox" name="services[]" value="<?php echo esc_attr( $s->get_name() ); ?>" <?php checked( (int) $args['preselect'], $s->get_id() ); ?> />
									<span><?php echo esc_html( $s->get_name() ); ?></span>
								</label>
							<?php endforeach; ?>
							<label class="svc-check">
								<input type="checkbox" name="services[]" value="<?php echo esc_attr__( 'Anders / weet ik nog niet', 'maison-opus' ); ?>" />
								<span><?php esc_html_e( 'Anders / weet ik nog niet', 'maison-opus' ); ?></span>
							</label>
						</div>
					<?php endif; ?>
				</div>

				<div class="zoek-fieldset">
					<h3 class="zoek-legend"><?php esc_html_e( 'Uw horloge', 'maison-opus' ); ?></h3>
					<div class="zoek-grid">
						<label class="zoek-field">
							<span><?php esc_html_e( 'Merk', 'maison-opus' ); ?></span>
							<select name="merk">
								<option value=""><?php esc_html_e( 'Kies een merk…', 'maison-opus' ); ?></option>
								<?php foreach ( $brands as $b ) : ?>
									<option value="<?php echo esc_attr( $b ); ?>"><?php echo esc_html( $b ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<label class="zoek-field">
							<span><?php esc_html_e( 'Model', 'maison-opus' ); ?></span>
							<input type="text" name="model" data-svc-model placeholder="<?php echo esc_attr__( 'bijv. Submariner, Speedmaster', 'maison-opus' ); ?>" />
						</label>
						<label class="zoek-field zoek-field--full">
							<label class="svc-inline-check">
								<input type="checkbox" name="model_unknown" value="1" data-svc-unknown />
								<span><?php esc_html_e( 'Ik weet het model niet (zeker)', 'maison-opus' ); ?></span>
							</label>
						</label>
						<label class="zoek-field zoek-field--full">
							<span><?php esc_html_e( 'Omschrijving', 'maison-opus' ); ?></span>
							<textarea name="watch" rows="4" placeholder="<?php echo esc_attr__( 'Wat moet er gebeuren? Beschrijf uw horloge en de klacht of wens.', 'maison-opus' ); ?>"></textarea>
						</label>
					</div>
				</div>

				<div class="zoek-fieldset">
					<h3 class="zoek-legend"><?php esc_html_e( 'Uw gegevens', 'maison-opus' ); ?></h3>
					<div class="zoek-grid">
						<label class="zoek-field">
							<span><?php esc_html_e( 'Naam', 'maison-opus' ); ?> <em>*</em></span>
							<input type="text" name="naam" required />
						</label>
						<label class="zoek-field">
							<span><?php esc_html_e( 'E-mailadres', 'maison-opus' ); ?> <em>*</em></span>
							<input type="email" name="email" required />
						</label>
						<label class="zoek-field zoek-field--full">
							<span><?php esc_html_e( 'Telefoon', 'maison-opus' ); ?></span>
							<input type="tel" name="telefoon" placeholder="<?php echo esc_attr__( 'Optioneel — handig voor snel overleg', 'maison-opus' ); ?>" />
						</label>
					</div>
				</div>

				<div class="zoek-submit">
					<button type="submit" class="btn solid"><?php esc_html_e( 'Verstuur aanvraag', 'maison-opus' ); ?></button>
					<p class="zoek-fineprint"><?php esc_html_e( 'Uw gegevens gebruiken we uitsluitend om uw aanvraag te behandelen.', 'maison-opus' ); ?></p>
				</div>
			</form>
		</div>
	</section>
	<script>
	( function () {
		var u = document.querySelector( '[data-svc-unknown]' );
		var m = document.querySelector( '[data-svc-model]' );
		if ( ! u || ! m ) { return; }
		function sync() { m.disabled = u.checked; if ( u.checked ) { m.value = ''; } }
		u.addEventListener( 'change', sync ); sync();
	} )();
	</script>
	<?php
}

/* ─────────────────────────────────────────────────────────────
 * Submission storage (private CPT) + handler.
 * ───────────────────────────────────────────────────────────── */
add_action(
	'init',
	function () {
		register_post_type(
			'opus_service_request',
			array(
				'labels'          => array(
					'name'          => 'Service-aanvragen',
					'singular_name' => 'Service-aanvraag',
					'menu_name'     => 'Service-aanvragen',
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'menu_icon'       => 'dashicons-admin-tools',
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
			)
		);
	}
);

function opus_handle_service_inquiry() {
	$back = wp_get_referer() ? wp_get_referer() : home_url( '/product-category/service/' );

	if ( ! isset( $_POST['opus_svc_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['opus_svc_nonce'] ), 'opus_service_inquiry' ) ) {
		wp_safe_redirect( add_query_arg( 'svc', 'error', $back ) );
		exit;
	}

	$naam     = sanitize_text_field( wp_unslash( $_POST['naam'] ?? '' ) );
	$email    = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$telefoon = sanitize_text_field( wp_unslash( $_POST['telefoon'] ?? '' ) );
	$merk     = sanitize_text_field( wp_unslash( $_POST['merk'] ?? '' ) );
	$model    = isset( $_POST['model_unknown'] ) ? 'Onbekend' : sanitize_text_field( wp_unslash( $_POST['model'] ?? '' ) );
	$watch    = sanitize_textarea_field( wp_unslash( $_POST['watch'] ?? '' ) );
	$services = isset( $_POST['services'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['services'] ) ) : array();

	if ( '' === $naam || ! is_email( $email ) || ! $services ) {
		wp_safe_redirect( add_query_arg( 'svc', 'error', $back ) );
		exit;
	}

	$post_id = wp_insert_post(
		array(
			'post_type'   => 'opus_service_request',
			'post_status' => 'private',
			'post_title'  => $naam . ' — ' . implode( ', ', $services ),
		)
	);

	if ( $post_id && ! is_wp_error( $post_id ) ) {
		$meta = compact( 'naam', 'email', 'telefoon', 'merk', 'model', 'watch' );
		foreach ( $meta as $k => $v ) {
			update_post_meta( $post_id, '_opus_' . $k, $v );
		}
		update_post_meta( $post_id, '_opus_services', implode( ', ', $services ) );

		$lines = array(
			'Nieuwe service-aanvraag via de website:',
			'',
			'Service(s): ' . implode( ', ', $services ),
			'Merk: ' . $merk,
			'Model: ' . $model,
			'Omschrijving: ' . $watch,
			'',
			'Naam: ' . $naam,
			'E-mail: ' . $email,
			'Telefoon: ' . $telefoon,
		);
		wp_mail(
			get_option( 'admin_email' ),
			'Nieuwe service-aanvraag — ' . $naam,
			implode( "\n", $lines ),
			array( 'Reply-To: ' . $naam . ' <' . $email . '>' )
		);
	}

	wp_safe_redirect( add_query_arg( 'svc', 'ok', remove_query_arg( 'svc', $back ) ) . '#svcform' );
	exit;
}
add_action( 'admin_post_opus_service_inquiry', 'opus_handle_service_inquiry' );
add_action( 'admin_post_nopriv_opus_service_inquiry', 'opus_handle_service_inquiry' );

/* ─────────────────────────────────────────────────────────────
 * Per-product video field (rendered on service pages).
 * ───────────────────────────────────────────────────────────── */
add_action(
	'add_meta_boxes',
	function () {
		add_meta_box( 'opus_video', 'Opus — Video', 'opus_video_metabox', 'product', 'side' );
	}
);
function opus_video_metabox( $post ) {
	wp_nonce_field( 'opus_video_save', 'opus_video_nonce' );
	$v = get_post_meta( $post->ID, '_opus_video_url', true );
	echo '<p style="margin-top:0">YouTube- of Vimeo-URL. Wordt op de servicepagina getoond.</p>';
	echo '<input type="url" name="opus_video_url" value="' . esc_attr( $v ) . '" style="width:100%" placeholder="https://…" />';
}
add_action(
	'save_post_product',
	function ( $post_id ) {
		if ( ! isset( $_POST['opus_video_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['opus_video_nonce'] ), 'opus_video_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		update_post_meta( $post_id, '_opus_video_url', esc_url_raw( wp_unslash( $_POST['opus_video_url'] ?? '' ) ) );
	}
);
