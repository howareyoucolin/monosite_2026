<?php
/**
 * Fallback template. Every theme needs one.
 *
 * @package kungfu_2026
 */

get_header();

if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		?>
		<article <?php post_class(); ?>>
			<h2><?php the_title(); ?></h2>
			<?php the_content(); ?>
		</article>
		<?php
	endwhile;
else :
	?>
	<p><?php esc_html_e( 'Nothing here yet.', 'kungfu_2026' ); ?></p>
	<?php
endif;

get_footer();
