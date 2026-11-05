<?php
/** Alphabetical character directory. @package kungfu_2026 */
get_header();
?>
<main id="main-content" class="character-directory">
	<header class="character-entry__head">
		<h1><?php esc_html_e( 'Characters', 'kungfu_2026' ); ?></h1>
		<p><?php esc_html_e( 'Appearance, abilities, and references for the people in the story.', 'kungfu_2026' ); ?></p>
	</header>
	<?php if ( have_posts() ) : ?>
		<div class="character-directory__grid">
			<?php while ( have_posts() ) : the_post(); ?>
				<article <?php post_class( 'character-card' ); ?>>
					<a class="character-card__link" href="<?php the_permalink(); ?>">
						<?php if ( has_post_thumbnail() ) : ?>
							<?php the_post_thumbnail( 'medium', array( 'class' => 'character-card__portrait', 'alt' => '' ) ); ?>
						<?php endif; ?>
						<h2><?php the_title(); ?></h2>
					</a>
					<?php if ( has_excerpt() ) : the_excerpt(); endif; ?>
				</article>
			<?php endwhile; ?>
		</div>
		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<p><?php esc_html_e( 'No character pages have been published yet.', 'kungfu_2026' ); ?></p>
	<?php endif; ?>
</main>
<?php get_footer(); ?>
