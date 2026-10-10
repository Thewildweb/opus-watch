<?php
/**
 * Page template — Contact. Form + business details (KvK/BTW) + map.
 * Auto-selected by WordPress for the page with slug "contact".
 *
 * Business details below are placeholders — edit them here (or ask to wire
 * them to editable settings).
 *
 * @package maison-opus
 */

get_header();

$status   = isset( $_GET['contact'] ) ? sanitize_key( wp_unslash( $_GET['contact'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
// "Informeer naar dit horloge" on a price-on-request product links here with
// the watch name, so the subject arrives pre-filled.
$subject  = isset( $_GET['horloge'] ) ? sanitize_text_field( wp_unslash( $_GET['horloge'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
// Coordinates of De Entree 201 — also baked into the map image by
// scripts/build-map-image.py; change them in both places.
$map_lat = '52.3120932';
$map_lon = '4.9407954';
$intro    = trim( wp_strip_all_tags( get_the_content() ) );
?>

<main class="container contact">

	<header class="contact-head">
		<p class="zoek-eyebrow"><?php esc_html_e( 'Contact', 'maison-opus' ); ?></p>
		<h1><?php esc_html_e( 'Neem contact op', 'maison-opus' ); ?></h1>
		<p class="contact-intro">
			<?php
			echo esc_html(
				$intro
					? $intro
					: __( 'Een vraag over een horloge, onderhoud of een afspraak in onze boutique? Stuur ons een bericht — we reageren doorgaans binnen één werkdag.', 'maison-opus' )
			);
			?>
		</p>
	</header>

	<div class="contact-grid">

		<!-- ── Form ─────────────────────────────────────────── -->
		<div class="contact-form-col" id="contactform">
			<?php if ( 'ok' === $status ) : ?>
				<p class="zoek-notice zoek-notice--ok"><?php esc_html_e( 'Dank u — uw bericht is ontvangen. We nemen zo snel mogelijk contact met u op.', 'maison-opus' ); ?></p>
			<?php elseif ( 'error' === $status ) : ?>
				<p class="zoek-notice zoek-notice--error"><?php esc_html_e( 'Er ontbrak iets. Controleer uw naam, een geldig e-mailadres en uw bericht.', 'maison-opus' ); ?></p>
			<?php endif; ?>

			<form class="zoek-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="opus_contact" />
				<?php wp_nonce_field( 'opus_contact', 'opus_contact_nonce' ); ?>

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
						<span><?php esc_html_e( 'Onderwerp', 'maison-opus' ); ?></span>
						<input type="text" name="onderwerp" value="<?php echo esc_attr( $subject ); ?>" placeholder="<?php esc_attr_e( 'bijv. afspraak, onderhoud, een specifiek horloge', 'maison-opus' ); ?>" />
					</label>
					<label class="zoek-field zoek-field--full">
						<span><?php esc_html_e( 'Bericht', 'maison-opus' ); ?> <em>*</em></span>
						<textarea name="bericht" rows="6" required placeholder="<?php esc_attr_e( 'Waarmee kunnen we u helpen?', 'maison-opus' ); ?>"></textarea>
					</label>
				</div>

				<div class="zoek-submit">
					<button type="submit" class="btn solid"><?php esc_html_e( 'Verstuur bericht', 'maison-opus' ); ?></button>
					<p class="zoek-fineprint"><?php esc_html_e( 'Uw gegevens gebruiken we uitsluitend om uw vraag te behandelen.', 'maison-opus' ); ?></p>
				</div>
			</form>
		</div>

		<!-- ── Business details ─────────────────────────────── -->
		<aside class="contact-details">
			<h2 class="contact-name">Opus Watches</h2>

			<div class="contact-block">
				<h3><?php esc_html_e( 'Bezoek', 'maison-opus' ); ?></h3>
				<p><?php esc_html_e( 'De Entree 201', 'maison-opus' ); ?><br/>1101 HG <?php esc_html_e( 'Amsterdam', 'maison-opus' ); ?><br/><em><?php esc_html_e( 'Uitsluitend op afspraak', 'maison-opus' ); ?></em></p>
			</div>

			<div class="contact-block">
				<h3><?php esc_html_e( 'Bereikbaar', 'maison-opus' ); ?></h3>
				<p>
					<a href="mailto:info@opus.watches">info@opus.watches</a><br/>
					<a href="tel:+31202614827">+31 20 261 4827</a>
				</p>
			</div>

			<div class="contact-block">
				<h3><?php esc_html_e( 'Openingstijden', 'maison-opus' ); ?></h3>
				<p><?php esc_html_e( 'Online & op afspraak', 'maison-opus' ); ?><br/>di – za · 10:00 – 18:00</p>
			</div>

			<div class="contact-block contact-legal">
				<h3><?php esc_html_e( 'Bedrijfsgegevens', 'maison-opus' ); ?></h3>
				<dl>
					<div><dt><?php esc_html_e( 'KvK', 'maison-opus' ); ?></dt><dd>93934866</dd></div>
					<div><dt><?php esc_html_e( 'Vestigingsnr.', 'maison-opus' ); ?></dt><dd>000065652193</dd></div>
				</dl>
				<p class="contact-activity"><?php esc_html_e( 'Eenmanszaak · In- en verkoop en reparatie van uurwerken', 'maison-opus' ); ?></p>
			</div>
		</aside>
	</div>

	<!-- ── Map ──────────────────────────────────────────────────
	     A baked OpenStreetMap image (scripts/build-map-image.py), not an
	     embed: an iframe would set third-party cookies before the visitor
	     consented to anything. Nothing here leaves the site. -->
	<figure class="contact-map-figure">
		<div class="contact-map">
			<picture>
				<source media="(max-width: 880px)" width="1000" height="750"
					srcset="<?php echo esc_url( maison_opus_asset_uri( 'assets/images/map-contact-narrow.webp' ) ); ?>" />
				<img src="<?php echo esc_url( maison_opus_asset_uri( 'assets/images/map-contact-wide.webp' ) ); ?>"
					width="1680" height="720" loading="lazy" decoding="async"
					alt="<?php esc_attr_e( 'Kaart met de locatie van Opus Watches aan De Entree 201 in Amsterdam', 'maison-opus' ); ?>" />
			</picture>
		</div>
		<figcaption class="contact-map__credit">
			<span>De Entree 201, Amsterdam</span>
			<a href="<?php echo esc_url( 'https://www.openstreetmap.org/directions?to=' . $map_lat . '%2C' . $map_lon ); ?>"
				target="_blank" rel="noopener"><?php esc_html_e( 'Routebeschrijving', 'maison-opus' ); ?></a>
			<a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">&copy; OpenStreetMap-bijdragers</a>
		</figcaption>
	</figure>

</main>

<?php
get_footer();
