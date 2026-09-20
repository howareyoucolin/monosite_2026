<?php
/**
 * The conversation under a chapter.
 *
 * Comments were never disabled on this site — posts have carried
 * comment_status 'open' the whole time. What was missing was a theme willing
 * to draw them: with no comments.php, comments_template() falls through to
 * core's theme-compat file, which is deprecated and prints a notice rather
 * than a conversation. So nothing appeared, and nobody could reply.
 *
 * Everything visible lives here rather than in comments.php so the template
 * stays the shape of the page and this stays the shape of the markup — the
 * same split the chapter tools use.
 *
 * @package kungfu_2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core does not enqueue comment-reply for classic themes; the theme has to.
 *
 * Without it "Reply" is still a working link — it reloads the page with
 * ?replytocom= and the form moves server-side — so this is an improvement on
 * the fallback, not a requirement for it.
 */
function kungfu_2026_comment_assets() {
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'kungfu_2026_comment_assets' );

/**
 * One comment.
 *
 * Passed to wp_list_comments() as its callback, which means it only ever sees
 * ordinary comments: Walker_Comment routes pingbacks and trackbacks to its own
 * ping() before it looks for a callback. That is why there is no ping branch
 * here, and why the count in the heading still matches what is listed.
 *
 * The <li> is opened and not closed — Walker_Comment::end_el() writes the
 * closing tag itself, and closing it here would nest every reply one level too
 * shallow.
 *
 * No avatar. Gravatar is an image request to a third party keyed on a hash of
 * the commenter's email, which is a lot of identity to spend on a decoration
 * beside a name that now reads "Anonymous" anyway.
 *
 * @param WP_Comment $comment Comment.
 * @param array      $args    wp_list_comments() arguments.
 * @param int        $depth   Depth of this comment in the thread.
 */
function akw_the_comment( $comment, $args, $depth ) {
	?>
	<li id="comment-<?php comment_ID(); ?>" <?php comment_class( '', $comment ); ?>>
		<article class="comment__body">
			<header class="comment__head">
				<div class="comment__byline">
					<?php
					/*
					 * Plain text, not comment_author_link(): a link to the
					 * commenter's own site would hand back the identity the
					 * byline just withheld.
					 */
					?>
					<span class="comment__author"><?php echo esc_html( get_comment_author( $comment ) ); ?></span>

					<?php
					/*
					 * The permalink carries $args so it lands on the right page
					 * of a paged thread. The visible date is formatted for
					 * reading; the machine-readable one is in the datetime
					 * attribute, which is what makes the two agree.
					 */
					?>
					<a class="comment__date" href="<?php echo esc_url( get_comment_link( $comment, $args ) ); ?>">
						<time datetime="<?php comment_time( 'c' ); ?>"><?php echo esc_html( get_comment_date( '', $comment ) ); ?></time>
					</a>
				</div>
			</header>

			<?php if ( '1' !== $comment->comment_approved ) : ?>
				<p class="comment__pending"><?php esc_html_e( 'Your comment is waiting to be approved.', 'kungfu_2026' ); ?></p>
			<?php endif; ?>

			<div class="comment__text">
				<?php comment_text( $comment ); ?>
			</div>

			<footer class="comment__foot">
				<?php
				comment_reply_link(
					array_merge(
						$args,
						array(
							'add_below'    => 'comment',
							'depth'        => $depth,
							'max_depth'    => $args['max_depth'],
							'reply_text'   => __( 'Reply', 'kungfu_2026' ),
							// The link's accessible name. Core builds it from
							// its own text domain, which would leave "Reply to"
							// in English beside a Chinese byline.
							/* translators: %s: comment author being replied to. */
							'reply_to_text' => __( 'Reply to %s', 'kungfu_2026' ),
							'before'       => '<span class="comment__reply">',
							'after'        => '</span>',
						)
					),
					$comment
				);

				edit_comment_link( __( 'Edit', 'kungfu_2026' ), '<span class="comment__edit">', '</span>', $comment );
				?>
			</footer>
		</article>
	<?php
	// Deliberately no </li>. See the note above.
}

/**
 * Every commenter reads as "Anonymous".
 *
 * Filtered rather than written into the markup so the whole front end agrees:
 * the byline, the reply link's aria-label, and the "Reply to %s" heading all
 * ask this same question, and a byline anonymized on its own would still be
 * undone by a reply link naming the person.
 *
 * The admin is deliberately left alone. The name is still collected and still
 * stored — moderation needs to tell one commenter from another, and a Comments
 * screen listing twenty identical Anonymouses cannot.
 *
 * @param string $author Comment author name as stored.
 * @return string
 */
function kungfu_2026_anonymous_comment_author( $author ) {
	if ( is_admin() ) {
		return $author;
	}

	return __( 'Anonymous', 'kungfu_2026' );
}
add_filter( 'get_comment_author', 'kungfu_2026_anonymous_comment_author' );

/**
 * The reply form, dressed for this theme.
 *
 * Name, email and the comment itself. No website field and no cookie consent
 * checkbox: a URL box on a fiction site collects little but spam links, and
 * with nothing stored there is nothing to ask consent for.
 *
 * Every visible label is overridden with a kungfu_2026 string rather than left
 * to core's defaults, because the Chinese version is a lookup on this theme's
 * text domain (see inc/language.php) — a label left in the 'default' domain
 * would sit in English in the middle of a Chinese page.
 *
 * @return array Arguments for comment_form().
 */
function akw_comment_form_args() {
	$commenter = wp_get_current_commenter();
	$required  = (bool) get_option( 'require_name_email' );

	// Both halves of the same thing: the attribute the browser and screen
	// reader act on, and the asterisk the eye reads. The asterisk is hidden
	// from assistive tech so a required field is announced once, not twice.
	$req_attr = $required ? ' required aria-required="true"' : '';
	$req_mark = '<span class="comment-form__required" aria-hidden="true">*</span>';

	$fields = array(
		'author' => sprintf(
			'<p class="comment-form__field comment-form__field--author"><label for="author">%1$s%2$s</label><input id="author" name="author" type="text" value="%3$s" maxlength="245" autocomplete="name"%4$s /></p>',
			esc_html__( 'Name', 'kungfu_2026' ),
			$required ? ' ' . $req_mark : '',
			esc_attr( $commenter['comment_author'] ),
			$req_attr
		),
		'email'  => sprintf(
			'<p class="comment-form__field comment-form__field--email"><label for="email">%1$s%2$s</label><input id="email" name="email" type="email" value="%3$s" maxlength="100" autocomplete="email"%4$s /><span class="comment-form__hint">%5$s</span></p>',
			esc_html__( 'Email', 'kungfu_2026' ),
			$required ? ' ' . $req_mark : '',
			esc_attr( $commenter['comment_author_email'] ),
			$req_attr,
			esc_html__( 'Your email address will not be published.', 'kungfu_2026' )
		),
	);

	/*
	 * Present and empty, rather than absent. comment_form() re-adds its own
	 * consent checkbox to any fields array that does not mention one, so
	 * leaving the key out is how you get the checkbox back — in core's markup
	 * and core's text domain, which would sit in English on a Chinese page.
	 *
	 * The consequence of having no checkbox is core's, not ours:
	 * wp-comments-post.php reads consent as "was the box ticked", so an absent
	 * box reads as a refusal and wp_set_comment_cookies() expires the author
	 * cookies. A commenter therefore types their name and email each time. That
	 * is the honest reading — the alternative, a hidden field that always says
	 * yes, is consent nobody gave.
	 */
	$fields['cookies'] = '';

	return array(
		'fields'               => $fields,
		'comment_field'        => sprintf(
			'<p class="comment-form__field comment-form__field--comment"><label for="comment">%1$s %2$s</label><textarea id="comment" name="comment" cols="45" rows="8" maxlength="65525" required aria-required="true"></textarea></p>',
			esc_html__( 'Comment', 'kungfu_2026' ),
			$req_mark
		),
		'class_form'           => 'comment-form',
		'class_submit'         => 'comment-form__submit',
		'title_reply'          => __( 'Leave a comment', 'kungfu_2026' ),
		/* translators: %s: comment author being replied to. */
		'title_reply_to'       => __( 'Reply to %s', 'kungfu_2026' ),
		// The id is core's: comment-reply.js finds the heading by it.
		'title_reply_before'   => '<h2 id="reply-title" class="comments__title comments__title--reply">',
		'title_reply_after'    => '</h2>',
		'cancel_reply_link'    => __( 'Cancel reply', 'kungfu_2026' ),
		'label_submit'         => __( 'Post comment', 'kungfu_2026' ),
		// The email hint sits on the email field itself, where it answers the
		// question at the moment it is asked, so the block above is empty.
		'comment_notes_before' => '',
		'comment_notes_after'  => '',
	);
}
