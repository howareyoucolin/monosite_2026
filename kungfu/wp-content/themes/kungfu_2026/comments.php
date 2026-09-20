<?php
/**
 * The comment thread and the reply form, under a chapter.
 *
 * Loaded by comments_template() from single.php. The markup for a single
 * comment and the arguments for the form are in inc/comments.php; this file is
 * only the shape of the section.
 *
 * Nothing here needs to know about the language switch. The form posts to
 * core's wp-comments-post.php, which redirects back through get_comment_link()
 * → get_permalink(), and that runs the post_link filter — so a reader who
 * chose the Chinese version comes back to the Chinese page with their comment
 * on it.
 *
 * @package kungfu_2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * A password-protected chapter must not leak its discussion to someone who
 * cannot read the chapter itself.
 */
if ( post_password_required() ) {
	return;
}
?>

<section id="comments" class="comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="comments__title">
			<?php
			$akw_comments = get_comments_number();

			printf(
				/* translators: %s: number of comments on this chapter. */
				esc_html( _n( '%s comment', '%s comments', $akw_comments, 'kungfu_2026' ) ),
				esc_html( number_format_i18n( $akw_comments ) )
			);
			?>
		</h2>

		<ol class="comments__list">
			<?php
			wp_list_comments(
				array(
					'style'      => 'ol',
					// A pingback is a line, not a post: the whole excerpt core
					// would otherwise print is someone else's text.
					'short_ping' => true,
					'callback'   => 'akw_the_comment',
				)
			);
			?>
		</ol>

		<?php
		/*
		 * Prints nothing while the thread fits on one page, which — with
		 * Settings → Discussion not breaking comments into pages — is always.
		 * It is here so that turning that setting on does not strand anyone on
		 * page one.
		 */
		the_comments_navigation(
			array(
				'prev_text' => esc_html__( 'Older comments', 'kungfu_2026' ),
				'next_text' => esc_html__( 'Newer comments', 'kungfu_2026' ),
				'screen_reader_text' => esc_html__( 'Comment navigation', 'kungfu_2026' ),
			)
		);
		?>
	<?php endif; ?>

	<?php
	/*
	 * Only worth saying where there is a thread to explain. On a chapter with
	 * no comments and comments off there is nothing to announce, and the line
	 * would read as an apology for an absence nobody noticed.
	 */
	if ( ! comments_open() && get_comments_number() ) :
		?>
		<p class="comments__closed"><?php esc_html_e( 'Comments are closed.', 'kungfu_2026' ); ?></p>
	<?php endif; ?>

	<?php comment_form( akw_comment_form_args() ); ?>
</section>
