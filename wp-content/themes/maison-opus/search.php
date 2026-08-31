<?php
/**
 * Search results.
 *
 * @package maison-opus
 */

get_header();
?>

<main class="page-main">
	<div class="container">
		<div class="section">
		<article class="page-article">
			<header class="page-header">
				<p class="page-eyebrow"><?php esc_html_e( 'Zoeken', 'maison-opus' ); ?></p>
				<h1 class="page-title display">
					<?php
					printf(
						/* translators: %s: search term. */
						esc_html__( 'Resultaten voor “%s”', 'maison-opus' ),
						esc_html( get_search_query() )
					);
					?>
				</h1>
			</header>

			<div class="page-content entry">
				<?php get_search_form(); ?>

				<?php if ( have_posts() ) : ?>
					<ul class="search-results">
						<?php
						while ( have_posts() ) :
							the_post();
							?>
							<li class="search-result">
								<a class="search-result__title" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
								<p class="search-result__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 32 ) ); ?></p>
							</li>
							<?php
						endwhile;
						?>
					</ul>

					<?php
					the_posts_pagination(
						array(
							'prev_text' => esc_html__( 'Vorige', 'maison-opus' ),
							'next_text' => esc_html__( 'Volgende', 'maison-opus' ),
						)
					);
					?>
				<?php else : ?>
					<p class="italic"><?php esc_html_e( 'Niets gevonden. Probeer een merk of referentienummer.', 'maison-opus' ); ?></p>
				<?php endif; ?>
			</div>
		</article>
		</div>
	</div>
</main>

<?php
get_footer();
