<?php
/** Character wiki entry. @package kungfu_2026 */
get_header();

while ( have_posts() ) :
	the_post();
	?>
	<main id="main-content" class="character-wiki">
		<article <?php post_class( 'character-entry' ); ?>>
			<header class="character-entry__head">
				<p><a href="<?php echo esc_url( get_post_type_archive_link( AKW_CHARACTER ) ); ?>"><?php esc_html_e( 'All characters', 'kungfu_2026' ); ?></a></p>
				<h1><?php the_title(); ?></h1>
				<?php akw_the_admin_post_id(); ?>
				<?php if ( has_excerpt() ) : ?>
					<div class="character-entry__summary"><?php the_excerpt(); ?></div>
				<?php endif; ?>
			</header>
			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="character-entry__portrait"><?php the_post_thumbnail( 'large' ); ?></figure>
			<?php endif; ?>
			<div class="chapter-body character-entry__body"><?php the_content(); ?></div>
			<?php wp_link_pages(); ?>
			<?php edit_post_link( __( 'Edit character', 'kungfu_2026' ), '<p class="character-entry__edit">', '</p>' ); ?>
		</article>
	</main>
	<?php
endwhile;

get_footer();
