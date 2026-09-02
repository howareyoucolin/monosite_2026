<?php
/**
 * One chapter.
 *
 * index.php used to catch this, which meant a bare h2 and body text at the
 * site's default size — the .chapter-body rules in the stylesheet had nothing
 * to apply to. Reading happens here, so this is what gets the reading measure
 * and the larger type.
 *
 * The content is rendered once into a variable rather than echoed by
 * the_content(), because the letter count has to be counted from the same
 * string that goes on the page. Rendering it twice would mean the count could
 * disagree with what the reader can see — and would run every block through
 * do_blocks() a second time for nothing.
 *
 * @package kungfu_2026
 */

get_header();

while ( have_posts() ) :
	the_post();

	$akw_label   = akw_get_chapter_label();
	$akw_arc     = akw_get_arc();
	$akw_content = apply_filters( 'the_content', get_the_content() );
	$akw_letters = akw_count_letters( $akw_content );
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

		<?php akw_the_chapter_tools( $akw_letters ); ?>

		<div class="chapter-body">
			<?php echo $akw_content; // phpcs:ignore WordPress.Security.EscapeOutput -- Already through the_content filters. ?>
		</div>

		<?php akw_the_next_chapter_link(); ?>
	</article>
	<?php
endwhile;

get_footer();
