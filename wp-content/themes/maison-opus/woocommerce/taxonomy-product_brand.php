<?php
/**
 * Brand archive — filterable overview of one brand's in-stock watches.
 * Override of WooCommerce's archive for the `product_brand` taxonomy.
 *
 * @package maison-opus
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

/* Opens our <main class="container wc-main"><div class="section"> wrapper. */
do_action( 'woocommerce_before_main_content' );

$opus_term = get_queried_object();
?>

<?php $opus_hero = opus_brand_hero_url( $opus_term ); ?>
<header class="woocommerce-products-header brand-hero<?php echo $opus_hero ? '' : ' brand-hero--bare'; ?>">
	<div class="brand-hero__text">
		<p class="zoek-eyebrow">Merk</p>
		<h1 class="woocommerce-products-header__title page-title"><?php echo esc_html( $opus_term->name ); ?></h1>
		<?php
		$opus_term_desc = term_description();
		if ( $opus_term_desc ) {
			echo '<div class="term-description">' . wp_kses_post( wpautop( $opus_term_desc ) ) . '</div>';
		} else {
			printf(
				'<p class="term-description">Onze actuele collectie %s — elk horloge door onze horlogemaker gecontroleerd op echtheid en staat.</p>',
				esc_html( $opus_term->name )
			);
		}

		$opus_db_url = function_exists( 'opus_db_brand_url' ) ? opus_db_brand_url( $opus_term ) : '';
		if ( $opus_db_url ) {
			printf(
				'<p class="brand-hero__dblink"><a href="%s">Verken alle %s-referenties in onze database<span aria-hidden="true"> &rarr;</span></a></p>',
				esc_url( $opus_db_url ),
				esc_html( $opus_term->name )
			);
		}

		// Rolex and Omega have their own service page (inc/service-pages.php).
		$opus_brand_svc = in_array( $opus_term->slug, array( 'rolex', 'omega' ), true ) && function_exists( 'opus_service_url_for_brand' )
			? opus_service_url_for_brand( $opus_term->name ) : '';
		if ( $opus_brand_svc ) {
			printf(
				'<p class="brand-hero__dblink"><a href="%s">Onderhoud en revisie van uw %s<span aria-hidden="true"> &rarr;</span></a></p>',
				esc_url( $opus_brand_svc ),
				esc_html( $opus_term->name )
			);
		}
		?>
	</div>
	<?php if ( $opus_hero ) : ?>
		<figure class="brand-hero__media">
			<img src="<?php echo esc_url( $opus_hero ); ?>" alt="<?php echo esc_attr( $opus_term->name ); ?>" loading="eager" fetchpriority="high" decoding="async" />
		</figure>
	<?php endif; ?>
</header>

<div class="brand-archive">
	<?php opus_shop_filters(); ?>

	<div class="brand-results">
		<?php opus_filters_toggle( true ); /* phones: opens the sidebar as a modal */ ?>
		<?php if ( woocommerce_product_loop() ) : ?>
			<p class="brand-results__count">
				<?php
				global $wp_query;
				$opus_found = (int) $wp_query->found_posts;
				/* translators: %d: number of watches */
				echo esc_html( sprintf( _n( '%d horloge', '%d horloges', $opus_found, 'maison-opus' ), $opus_found ) );
				?>
			</p>
			<?php
			woocommerce_product_loop_start();
			while ( have_posts() ) {
				the_post();
				do_action( 'woocommerce_shop_loop' );
				wc_get_template_part( 'content', 'product' );
			}
			woocommerce_product_loop_end();
			do_action( 'woocommerce_after_shop_loop' );
			?>
		<?php else : ?>
			<?php
			/* Not in stock — point to the zoekopdracht flow, pre-filled with brand + model. */
			$opus_filters    = opus_active_filters();
			$opus_model_term = $opus_filters['model'] ? get_term_by( 'slug', $opus_filters['model'], 'watch_model' ) : null;
			$opus_wanted     = trim( $opus_term->name . ( $opus_model_term ? ' ' . $opus_model_term->name : '' ) );
			$opus_zoek_url   = add_query_arg(
				array_filter(
					array(
						'merk'  => rawurlencode( $opus_term->name ),
						'model' => $opus_model_term ? rawurlencode( $opus_model_term->name ) : '',
					)
				),
				home_url( '/zoekopdracht/' )
			) . '#zoekform';
			?>
			<div class="brand-empty">
				<p class="zoek-eyebrow">Niet op voorraad</p>
				<h2 class="brand-empty__title">Binnenkort verwacht.</h2>
				<p class="brand-empty__text">
					Op dit moment hebben wij geen <?php echo esc_html( $opus_wanted ); ?> die aan uw
					selectie voldoet. Onze collectie wisselt voortdurend — de kans is groot dat er
					binnenkort weer een binnenkomt. Nog sneller: plaats een zoekopdracht, dan gaan
					wij via ons netwerk gericht voor u op zoek.
				</p>
				<div class="brand-empty__actions">
					<a class="btn solid" href="<?php echo esc_url( $opus_zoek_url ); ?>">Plaats een zoekopdracht</a>
					<?php if ( opus_filters_are_active() ) : ?>
						<a class="filter-reset" href="<?php echo esc_url( opus_filters_base_url() ); ?>">Wis de filters</a>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
</div>

<?php
/* Closes the wrapper. */
do_action( 'woocommerce_after_main_content' );

get_footer( 'shop' );
