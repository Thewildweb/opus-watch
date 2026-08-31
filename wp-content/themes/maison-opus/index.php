<?php
/**
 * Generic fallback template.
 *
 * @package maison-opus
 */

get_header();
?>

<main class="container">
	<div class="section">
		<?php
		if ( have_posts() ) :
			while ( have_posts() ) :
				the_post();
				?>
				<article <?php post_class(); ?>>
					<h2 class="display" style="font-size: clamp(32px,4vw,56px);">
						<a href="<?php the_permalink(); ?>" style="text-decoration:none;"><?php the_title(); ?></a>
					</h2>
					<div class="entry"><?php the_excerpt(); ?></div>
				</article>
				<div class="ornament">✦ · ✦ · ✦</div>
				<?php
			endwhile;
			the_posts_pagination();
		else :
			?>
			<p class="italic"><?php esc_html_e( 'Hier staat nog niets.', 'maison-opus' ); ?></p>
		<?php endif; ?>
	</div>
</main>

<?php
get_footer();
