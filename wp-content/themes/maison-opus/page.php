<?php
/**
 * Generic page template — title + editor content.
 *
 * Without this file WordPress falls back to index.php, which is an archive
 * template printing the_excerpt(); that renders the WooCommerce cart,
 * checkout and account pages (and any page the shop creates in wp-admin)
 * as an empty shell. Pages with their own template — contact, taxatie,
 * zoekopdracht, database — are unaffected and keep their own layout.
 *
 * @package maison-opus
 */

get_header();

// Cart, checkout and account are block/shortcode driven and need the full
// column; editorial pages read better on a narrow measure.
$opus_wide = function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() );
?>

<main class="page-main<?php echo $opus_wide ? ' page-main--wide' : ''; ?>">
	<div class="container">
		<div class="section">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article <?php post_class( 'page-article' ); ?>>
				<header class="page-header">
					<h1 class="page-title display"><?php the_title(); ?></h1>
				</header>

				<div class="page-content entry">
					<?php
					the_content();

					wp_link_pages(
						array(
							'before' => '<nav class="page-links">',
							'after'  => '</nav>',
						)
					);
					?>
				</div>
			</article>
			<?php
		endwhile;
		?>
		</div>
	</div>
</main>

<?php
get_footer();
