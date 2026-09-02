<?php
/**
 * One chapter.
 *
 * index.php used to catch this, which meant a bare h2 and body text at the
 * site's default size — the .chapter-body rules in the stylesheet had nothing
 * to apply to. Reading happens here, so this is what gets the reading measure
 * and the larger type.
 *
 * @package kungfu_2026
 */

get_header();

while ( have_posts() ) :
	the_post();

	$akw_label = akw_get_chapter_label();
	$akw_arc   = akw_get_arc();
	?>
	<article <?php post_class( 'chapter' ); ?>>
		<header class="chapter__head">
			<?php if ( $akw_label ) : ?>
				<p class="chapter__label">
					<?php if ( $akw_arc ) : ?>
						<a href="<?php echo esc_url( get_term_link( $akw_arc ) ); ?>"><?php echo esc_html( $akw_label ); ?></a>
					<?php else : ?>
						<?php echo esc_html( $akw_label ); ?>
					<?php endif; ?>
				</p>
			<?php endif; ?>

			<h1 class="chapter__title"><?php the_title(); ?></h1>
		</header>

		<div class="chapter-body">
			<?php the_content(); ?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
