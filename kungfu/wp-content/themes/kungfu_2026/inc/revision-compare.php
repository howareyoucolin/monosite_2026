<?php
/**
 * Front-end comparison between a chapter and its previous saved revision.
 *
 * The comparison template lives in template-parts/chapter-compare.php. This
 * file only decides who may see it, resolves the revision, and renders the
 * small administrator link on the ordinary chapter page.
 *
 * @package kungfu_2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Query parameter used by the front-end comparison. */
const AKW_COMPARE_PARAM = 'compare';

/**
 * Find the latest non-autosave revision that differs from the current title
 * or content. WordPress may store a newest revision identical to the current
 * save, so that copy is skipped rather than showing an empty comparison.
 *
 * @param int|WP_Post|null $post Chapter.
 * @return WP_Post|null
 */
function akw_get_previous_revision( $post = null ) {
	$post = get_post( $post );

	if ( ! $post || AKW_CHAPTER !== $post->post_type ) {
		return null;
	}

	static $found = array();

	if ( array_key_exists( $post->ID, $found ) ) {
		return $found[ $post->ID ];
	}

	$found[ $post->ID ] = null;
	$revisions          = wp_get_post_revisions(
		$post->ID,
		array(
			'posts_per_page' => -1,
			'order'          => 'DESC',
			'orderby'        => 'date ID',
		)
	);

	foreach ( $revisions as $revision ) {
		if ( wp_is_post_autosave( $revision ) ) {
			continue;
		}

		if ( $revision->post_title === $post->post_title && $revision->post_content === $post->post_content ) {
			continue;
		}

		$found[ $post->ID ] = $revision;
		break;
	}

	return $found[ $post->ID ];
}

/**
 * The previous revision's chronological number among saved, non-autosave
 * revisions for this chapter.
 *
 * @param int|WP_Post|null $post Chapter.
 * @return int
 */
function akw_get_previous_revision_number( $post = null ) {
	$post     = get_post( $post );
	$previous = akw_get_previous_revision( $post );

	if ( ! $post || ! $previous ) {
		return 0;
	}

	$number    = 0;
	$revisions = wp_get_post_revisions(
		$post->ID,
		array(
			'posts_per_page' => -1,
			'order'          => 'ASC',
			'orderby'        => 'date ID',
		)
	);

	foreach ( $revisions as $revision ) {
		if ( wp_is_post_autosave( $revision ) ) {
			continue;
		}

		++$number;
		if ( $revision->ID === $previous->ID ) {
			return $number;
		}
	}

	return 0;
}

/**
 * Whether this request should use the comparison template.
 *
 * A guessed ?compare=1 URL does not reveal revisions to a logged-out reader;
 * it simply falls back to the normal chapter template.
 *
 * @return bool
 */
function akw_is_revision_compare_view() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display choice.
	$requested = isset( $_GET[ AKW_COMPARE_PARAM ] ) && '1' === wp_unslash( $_GET[ AKW_COMPARE_PARAM ] );

	return $requested
		&& is_singular( AKW_CHAPTER )
		&& current_user_can( 'manage_options' )
		&& (bool) akw_get_previous_revision( get_queried_object_id() );
}

/**
 * Whether the current visitor gets the comparison control.
 *
 * @param int|WP_Post|null $post Chapter.
 * @return bool
 */
function akw_can_compare_revision( $post = null ) {
	return current_user_can( 'manage_options' ) && (bool) akw_get_previous_revision( $post );
}

/**
 * Print the top-right administrator revision control.
 *
 * @param int|WP_Post|null $post Chapter.
 */
function akw_the_revision_compare_link( $post = null ) {
	$post = get_post( $post );

	if ( ! $post || ! akw_can_compare_revision( $post ) ) {
		return;
	}

	$revision_number = akw_get_previous_revision_number( $post );
	if ( ! $revision_number ) {
		return;
	}

	$url = add_query_arg( AKW_COMPARE_PARAM, '1', get_permalink( $post ) );
	/* translators: %d: chronological revision number. */
	$label = sprintf( __( 'Compare with revision (%d)', 'kungfu_2026' ), $revision_number );
	?>
	<div class="chapter__revision-meta">
		<a class="chapter__compare" href="<?php echo esc_url( $url ); ?>">
			<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
				<path d="M8 7h11l-3-3"/>
				<path d="m19 7-3 3"/>
				<path d="M16 17H5l3 3"/>
				<path d="m5 17 3-3"/>
			</svg>
			<span><?php echo esc_html( $label ); ?></span>
		</a>
	</div>
	<?php
}

/**
 * Turn stored block content into readable lines for wp_text_diff().
 *
 * @param string $content Raw post content.
 * @return string
 */
function akw_revision_compare_text( $content ) {
	$html = do_blocks( $content );
	$html = preg_replace( '#<(?:br|/p|/h[1-6]|/li|/div|/blockquote|/figcaption)[^>]*>#i', "\n", $html );
	$text = wp_strip_all_tags( $html );
	$text = html_entity_decode( $text, ENT_QUOTES, get_bloginfo( 'charset' ) );
	$text = preg_replace( '/[\t ]+/u', ' ', $text );
	$text = preg_replace( '/\n{3,}/u', "\n\n", $text );

	return trim( (string) $text );
}

/**
 * Which kind of change the comparison page should display.
 *
 * @return string One of both, deleted, added, or plain.
 */
function akw_get_revision_change_view() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display preference stored in the URL.
	$view = isset( $_GET['changes'] ) ? sanitize_key( wp_unslash( $_GET['changes'] ) ) : 'both';

	return in_array( $view, array( 'both', 'deleted', 'added', 'plain' ), true ) ? $view : 'both';
}

/**
 * Build a persistent comparison filter URL.
 *
 * @param string           $view View name.
 * @param int|WP_Post|null $post Chapter.
 * @return string
 */
function akw_get_revision_change_url( $view, $post = null ) {
	return add_query_arg(
		array(
			AKW_COMPARE_PARAM => '1',
			'changes'         => $view,
		),
		get_permalink( $post )
	);
}

/**
 * Render a split WordPress text diff.
 *
 * @param string $previous Previous value.
 * @param string $current  Current value.
 * @return string HTML.
 */
function akw_revision_text_diff( $previous, $current ) {
	if ( ! class_exists( 'WP_Text_Diff_Renderer_Table', false ) ) {
		require_once ABSPATH . WPINC . '/wp-diff.php';
	}

	require_once get_theme_file_path( 'inc/class-akw-revision-inline-diff-renderer.php' );

	$previous_lines = explode( "\n", normalize_whitespace( $previous ) );
	$current_lines  = explode( "\n", normalize_whitespace( $current ) );
	$text_diff      = new Text_Diff( $previous_lines, $current_lines );
	$renderer       = new AKW_Revision_Inline_Diff_Renderer();
	$rows           = $renderer->render( $text_diff );

	if ( '' === trim( $rows ) ) {
		return '';
	}

	return '<table class="diff diff-inline"><tbody>' . $rows . '</tbody></table>';
}

/**
 * Keep administrator revision URLs out of search indexes.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function kungfu_2026_revision_compare_robots( $robots ) {
	if ( akw_is_revision_compare_view() ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
	}

	return $robots;
}
add_filter( 'wp_robots', 'kungfu_2026_revision_compare_robots' );
