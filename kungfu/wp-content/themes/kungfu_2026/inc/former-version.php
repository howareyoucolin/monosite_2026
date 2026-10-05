<?php
/**
 * The former version of a chapter: the body it had before it was remastered.
 *
 * One piece of post meta, holding serialized block markup — the same thing
 * post_content holds. The "Set as former version" button in the editor sidebar
 * (js/former-version.js) copies the current body into it and empties the
 * editor for the rewrite. Like every other editor tool in the theme it only
 * edits: the copy and the empty body both land when Update is pressed, together.
 *
 * On the front end a chapter that has one gets a small "Draft" link
 * at the top, which reloads the same chapter with ?version=previous and the old
 * body in place of the new. A chapter emptied for a remaster that has not
 * been written yet shows the former body as its own, with no link.
 *
 * @package kungfu_2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Meta key: the former content, as serialized blocks. */
define( 'AKW_CONTENT_FORMER', 'akw_content_former' );

/** Query parameter that asks for the former version. */
define( 'AKW_VERSION_PARAM', 'version' );

/** Its one meaningful value. */
define( 'AKW_VERSION_PREVIOUS', 'previous' );

/**
 * Register the field, exposed to REST so the editor can read and write it.
 */
function kungfu_2026_register_former_meta() {
	register_post_meta(
		AKW_CHAPTER,
		AKW_CONTENT_FORMER,
		array(
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			// Same rule as the Chinese body: it is post_content by another name.
			'sanitize_callback' => 'kungfu_2026_sanitize_chinese_content',
			'auth_callback'     => 'kungfu_2026_can_edit_chinese_version',
		)
	);
}
add_action( 'init', 'kungfu_2026_register_former_meta' );

/**
 * Load the sidebar button on the chapter editor.
 */
function kungfu_2026_former_version_assets() {
	$screen = get_current_screen();

	if ( ! $screen || AKW_CHAPTER !== $screen->post_type ) {
		return;
	}

	wp_enqueue_script(
		'kungfu-2026-former-version',
		get_theme_file_uri( 'js/former-version.js' ),
		array( 'wp-blocks', 'wp-components', 'wp-data', 'wp-edit-post', 'wp-editor', 'wp-element', 'wp-i18n', 'wp-plugins' ),
		wp_get_theme()->get( 'Version' ),
		true
	);

	wp_set_script_translations( 'kungfu-2026-former-version', 'kungfu_2026' );
}
add_action( 'enqueue_block_editor_assets', 'kungfu_2026_former_version_assets' );

/**
 * The raw former content of a chapter, as stored: serialized blocks.
 *
 * @param int|WP_Post|null $post Chapter.
 * @return string Empty when the chapter has never been remastered.
 */
function akw_get_raw_former_content( $post = null ) {
	$post = get_post( $post );

	return $post ? (string) get_post_meta( $post->ID, AKW_CONTENT_FORMER, true ) : '';
}

/**
 * Whether a chapter has a former version worth linking to.
 *
 * @param int|WP_Post|null $post Chapter.
 * @return bool
 */
function akw_has_former_version( $post = null ) {
	return '' !== trim( akw_get_raw_former_content( $post ) );
}

/**
 * Whether this request asked for the former version of a chapter that has one.
 *
 * A ?version=previous on a chapter without one is ignored rather than served
 * as an empty page.
 *
 * @return bool
 */
function akw_is_former_view() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Choosing what to display, not acting on it.
	$requested = isset( $_GET[ AKW_VERSION_PARAM ] ) && AKW_VERSION_PREVIOUS === wp_unslash( $_GET[ AKW_VERSION_PARAM ] );

	return $requested && is_singular( AKW_CHAPTER ) && akw_has_former_version( get_queried_object_id() );
}

/**
 * Whether a chapter has been emptied for a remaster that is not written yet.
 *
 * True when the body is blank and a former version exists — the state a post
 * is left in by "Set as former version" followed by Update. The page then shows
 * the former version as the chapter itself rather than an empty page, until
 * the rewrite lands.
 *
 * Blank means no text once tags are gone, so an empty paragraph block left by
 * clicking into the editor still counts as empty. A Chinese reader is not
 * waiting if the chapter has a Chinese body: that is what they are shown, and
 * the button never touches it.
 *
 * @param int|WP_Post|null $post Chapter.
 * @return bool
 */
function akw_is_awaiting_remaster( $post = null ) {
	$post = get_post( $post );

	if ( ! $post || '' !== trim( wp_strip_all_tags( $post->post_content ) ) || ! akw_has_former_version( $post ) ) {
		return false;
	}

	return ! ( akw_should_use_zh_text() && '' !== trim( akw_get_raw_zh_content( $post ) ) );
}

/**
 * The former content, rendered.
 *
 * Same route as akw_get_zh_content(), and not the_content for the same reason —
 * plus one of its own: the_content is where the Chinese body is swapped in,
 * which would put today's translation over the old text.
 *
 * @param int|WP_Post|null $post Chapter.
 * @return string HTML, ready to echo.
 */
function akw_get_former_content( $post = null ) {
	$raw = akw_get_raw_former_content( $post );

	if ( '' === trim( $raw ) ) {
		return '';
	}

	$html = do_blocks( $raw );
	$html = wptexturize( $html );
	$html = convert_smilies( $html );

	return apply_filters( 'akw_former_content', $html, get_post( $post ) );
}

/**
 * The small link at the top of a chapter: to the previous version, or back.
 *
 * Prints nothing on a chapter that was never remastered.
 *
 * @param int|WP_Post|null $post Chapter.
 */
function akw_the_version_link( $post = null ) {
	// Nothing to switch between while the former version is the only one.
	if ( ! akw_has_former_version( $post ) || akw_is_awaiting_remaster( $post ) ) {
		return;
	}

	// get_permalink() already carries ?lang=cn when the reader is in Chinese.
	$permalink = get_permalink( $post );

	if ( akw_is_former_view() ) {
		$url   = $permalink;
		$label = __( 'Remaster', 'kungfu_2026' );
	} else {
		$url   = add_query_arg( AKW_VERSION_PARAM, AKW_VERSION_PREVIOUS, $permalink );
		$label = __( 'Draft', 'kungfu_2026' );
	}
	?>
	<a class="chapter__version" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
	<?php
}

/**
 * Keep the former version out of search results.
 *
 * It is the same chapter at the same canonical URL, with older words.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function kungfu_2026_former_version_robots( $robots ) {
	if ( akw_is_former_view() ) {
		$robots['noindex'] = true;
	}

	return $robots;
}
add_filter( 'wp_robots', 'kungfu_2026_former_version_robots' );
