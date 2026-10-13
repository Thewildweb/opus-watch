<?php
/**
 * Service page — /diensten/ and the repair pages (inc/service-pages.php).
 *
 * Title = H1, excerpt = intro, editor content = the copy and its FAQ. Around
 * it: the services hub cards (on /diensten/), the bookable services with
 * prices, links to sibling pages, the atelier's address and hours, and the
 * onderhoud form. Same look as a service product (inc/service-single.php).
 *
 * @package maison-opus
 */

defined( 'ABSPATH' ) || exit;

get_header();

$opus_slug   = opus_current_service_page();
$opus_pages  = opus_service_pages();
$opus_def    = $opus_pages[ $opus_slug ];
$opus_is_hub = 'diensten' === $opus_slug;
$opus_nap    = opus_nap();
$opus_tel    = str_replace( ' ', '', $opus_nap['phone'] );
?>

<main class="page-main svc-page">
	<div class="container">
		<div class="section">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article <?php post_class( 'svc-single' ); ?>>

				<header class="svc-head">
					<p class="zoek-eyebrow"><?php esc_html_e( 'Onderhoud & reparatie', 'maison-opus' ); ?></p>
					<h1 class="svc-title"><?php the_title(); ?></h1>
					<?php if ( has_excerpt() ) : ?>
						<div class="svc-lead"><p><?php echo esc_html( get_the_excerpt() ); ?></p></div>
					<?php endif; ?>
					<div class="svc-head-cta">
						<a class="btn solid" href="#svcform"><?php esc_html_e( 'Onderhoud aanvragen', 'maison-opus' ); ?></a>
						<a class="svc-phone" href="tel:<?php echo esc_attr( $opus_tel ); ?>"><?php echo esc_html( $opus_nap['phone'] ); ?></a>
						<?php if ( $opus_def['from'] ) : ?>
							<span class="svc-price">
								<?php
								/* translators: %s: lowest price, e.g. €450 */
								printf( esc_html__( 'Vanaf %s', 'maison-opus' ), esc_html( '€' . number_format_i18n( $opus_def['from'] ) ) );
								?>
							</span>
						<?php endif; ?>
					</div>
					<ul class="svc-facts">
						<li><?php esc_html_e( 'Eigen atelier in Amsterdam', 'maison-opus' ); ?></li>
						<li><?php esc_html_e( 'Offerte vooraf, geen werk zonder akkoord', 'maison-opus' ); ?></li>
						<li><?php esc_html_e( 'Twee jaar garantie op een revisie', 'maison-opus' ); ?></li>
						<li><?php esc_html_e( 'Uitsluitend op afspraak', 'maison-opus' ); ?></li>
					</ul>
				</header>

				<?php if ( $opus_is_hub ) : ?>
					<nav class="svc-cards" aria-label="<?php esc_attr_e( 'Onze diensten', 'maison-opus' ); ?>">
						<?php
						foreach ( $opus_pages as $opus_card_slug => $opus_card ) :
							// The overview shows the general services only; the brand pages and the atelier page are linked elsewhere.
							$opus_card_url = in_array( $opus_card_slug, array( 'diensten', 'rolex-service', 'omega-service', 'horlogemaker-amsterdam' ), true ) ? '' : opus_service_page_url( $opus_card_slug );
							if ( ! $opus_card_url ) {
								continue;
							}
							?>
							<a class="svc-card" href="<?php echo esc_url( $opus_card_url ); ?>">
								<span class="svc-card__title"><?php echo esc_html( $opus_card['label'] ); ?></span>
								<span class="svc-card__text"><?php echo esc_html( $opus_card['summary'] ); ?></span>
								<span class="svc-card__meta">
									<?php
									if ( $opus_card['from'] ) {
										/* translators: %s: lowest price, e.g. €450 */
										printf( esc_html__( 'Vanaf %s', 'maison-opus' ), esc_html( '€' . number_format_i18n( $opus_card['from'] ) ) );
									} else {
										esc_html_e( 'Lees meer', 'maison-opus' );
									}
									?>
									<span aria-hidden="true"> &rarr;</span>
								</span>
							</a>
						<?php endforeach; ?>
					</nav>
				<?php endif; ?>

				<div class="svc-content">
					<div class="svc-body">
						<div class="svc-body-text svc-page-text"><?php the_content(); ?></div>
					</div>
				</div>

				<?php
				// Bookable services with prices: grouped on the hub, the page's own selection elsewhere.
				$opus_groups = $opus_is_hub
					? array(
						__( 'Volledig onderhoud', 'maison-opus' ) => opus_service_products( 'onderhoud' ),
						__( 'Deelreparaties', 'maison-opus' )     => opus_service_products( 'deelreparatie' ),
					)
					: array( __( 'Tarieven', 'maison-opus' ) => opus_service_products_by_slug( $opus_def['products'] ) );
				foreach ( $opus_groups as $opus_group_title => $opus_services ) :
					if ( ! $opus_services ) {
						continue;
					}
					?>
					<section class="svc-group svc-content">
						<h2 class="svc-group__title"><?php echo esc_html( $opus_group_title ); ?></h2>
						<div class="svc-list">
							<?php
							foreach ( $opus_services as $opus_service ) :
								$opus_desc = wp_strip_all_tags( $opus_service->get_short_description() );
								?>
								<a class="svc-item" href="<?php echo esc_url( get_permalink( $opus_service->get_id() ) ); ?>">
									<div class="svc-item__text">
										<h3 class="svc-item__title"><?php echo esc_html( $opus_service->get_name() ); ?></h3>
										<?php if ( $opus_desc ) : ?>
											<p class="svc-item__desc"><?php echo esc_html( $opus_desc ); ?></p>
										<?php endif; ?>
									</div>
									<div class="svc-item__meta">
										<span class="svc-item__price"><?php echo opus_service_price( $opus_service ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?></span>
										<span class="svc-item__link"><?php esc_html_e( 'Aanvragen', 'maison-opus' ); ?><span aria-hidden="true"> &rarr;</span></span>
									</div>
								</a>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endforeach; ?>

				<?php if ( $opus_is_hub ) : ?>
					<?php
					$opus_tax_page = get_page_by_path( 'taxatie' );
					$opus_tax_url  = $opus_tax_page ? get_permalink( $opus_tax_page ) : home_url( '/taxatie/' );
					?>
					<section class="svc-taxatie svc-content">
						<div class="svc-taxatie__text">
							<p class="zoek-eyebrow"><?php esc_html_e( 'Taxatie', 'maison-opus' ); ?></p>
							<h2 class="svc-taxatie__title"><?php esc_html_e( 'Wat is uw horloge waard?', 'maison-opus' ); ?></h2>
							<p class="svc-taxatie__sub"><?php esc_html_e( 'Voor verkoop, verzekering of gewoon uit nieuwsgierigheid: onze horlogemaker taxeert uw horloge met kennis van de actuele markt — vrijblijvend en kosteloos.', 'maison-opus' ); ?></p>
						</div>
						<a class="btn solid" href="<?php echo esc_url( $opus_tax_url ); ?>"><?php esc_html_e( 'Vraag een taxatie aan', 'maison-opus' ); ?></a>
					</section>
				<?php endif; ?>

				<?php
				$opus_related = array();
				foreach ( $opus_def['related'] as $opus_rel ) {
					$opus_rel_url = opus_service_page_url( $opus_rel );
					if ( $opus_rel_url && isset( $opus_pages[ $opus_rel ] ) ) {
						$opus_related[] = array( $opus_rel_url, $opus_pages[ $opus_rel ]['label'], $opus_pages[ $opus_rel ]['summary'] );
					}
				}
				?>
				<?php if ( $opus_related ) : ?>
					<nav class="svc-related svc-content" aria-label="<?php esc_attr_e( 'Meer over onderhoud', 'maison-opus' ); ?>">
						<h2 class="svc-group__title"><?php esc_html_e( 'Meer over onderhoud', 'maison-opus' ); ?></h2>
						<ul>
							<?php foreach ( $opus_related as $opus_rel ) : ?>
								<li><a href="<?php echo esc_url( $opus_rel[0] ); ?>"><?php echo esc_html( $opus_rel[1] ); ?></a> <span><?php echo esc_html( $opus_rel[2] ); ?></span></li>
							<?php endforeach; ?>
							<li><a href="<?php echo esc_url( opus_services_url() ); ?>"><?php esc_html_e( 'Alle diensten', 'maison-opus' ); ?></a> <span><?php esc_html_e( 'Overzicht van ons onderhoud, met prijzen.', 'maison-opus' ); ?></span></li>
						</ul>
					</nav>
				<?php endif; ?>

				<aside class="svc-nap svc-content">
					<div>
						<h2 class="svc-group__title"><?php esc_html_e( 'Het atelier', 'maison-opus' ); ?></h2>
						<p><strong><?php echo esc_html( $opus_nap['name'] ); ?></strong><br/>
							<?php echo esc_html( $opus_nap['street'] ); ?><br/>
							<?php echo esc_html( $opus_nap['postcode'] . ' ' . $opus_nap['city'] ); ?></p>
					</div>
					<div>
						<h2 class="svc-group__title"><?php esc_html_e( 'Openingstijden', 'maison-opus' ); ?></h2>
						<p><?php echo esc_html( opus_opening_hours_text() ); ?><br/><em><?php esc_html_e( 'Uitsluitend op afspraak', 'maison-opus' ); ?></em></p>
					</div>
					<div>
						<h2 class="svc-group__title"><?php esc_html_e( 'Contact', 'maison-opus' ); ?></h2>
						<p><a href="tel:<?php echo esc_attr( $opus_tel ); ?>"><?php echo esc_html( $opus_nap['phone'] ); ?></a><br/>
							<a href="mailto:<?php echo esc_attr( $opus_nap['email'] ); ?>"><?php echo esc_html( $opus_nap['email'] ); ?></a></p>
					</div>
				</aside>

				<?php
				opus_service_inquiry_form(
					array(
						'title' => __( 'Onderhoud aanvragen', 'maison-opus' ),
						'sub'   => __( 'Beschrijf uw horloge en kruis aan wat er moet gebeuren — wij reageren doorgaans binnen één werkdag.', 'maison-opus' ),
					)
				);
				?>
			</article>
			<?php
		endwhile;
		?>
		</div>
	</div>
</main>

<?php
get_footer();
