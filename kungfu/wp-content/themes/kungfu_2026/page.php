<?php
/**
 * A page, with the same title-and-content copy control as chapters.
 *
 * @package kungfu_2026
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article <?php post_class( 'chapter' ); ?>>
		<header class="chapter__head">
			<h1 class="chapter__title"><?php the_title(); ?></h1>
			<?php akw_the_admin_post_id(); ?>
		</header>

		<?php if ( ! post_password_required() ) : ?>
			<?php akw_the_chapter_tools(); ?>
		<?php endif; ?>

		<div class="chapter-body">
			<?php the_content(); ?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
