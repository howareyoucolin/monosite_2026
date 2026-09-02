<?php
/**
 * The Chinese version of a chapter: its own title and its own block content.
 *
 * Two pieces of post meta. The content is stored as serialized block markup —
 * the same thing post_content holds — so the field in the editor can be a real
 * block editor rather than a textarea pretending to be one.
 *
 * The editing UI lives in a meta box below the main editor, because that is the
 * only full-width region the block editor gives a plugin: every other slot
 * (PluginDocumentSettingPanel, PluginSidebar) is a ~280px sidebar, too narrow
 * to write in. The box itself posts nothing; it is a mount point, and both
 * values save with the post through their REST meta fields.
 *
 * @package kungfu_2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Meta key: the Chinese title. */
define( 'AKW_TITLE_ZH', 'akw_title_zh' );

/** Meta key: the Chinese content, as serialized blocks. */
define( 'AKW_CONTENT_ZH', 'akw_content_zh' );

/**
 * Register both fields, exposed to REST so the editor can read and write them.
 */
function kungfu_2026_register_chinese_meta() {
	register_post_meta(
		AKW_CHAPTER,
		AKW_TITLE_ZH,
		array(
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => 'kungfu_2026_can_edit_chinese_version',
		)
	);

	register_post_meta(
		AKW_CHAPTER,
		AKW_CONTENT_ZH,
		array(
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			'sanitize_callback' => 'kungfu_2026_sanitize_chinese_content',
			'auth_callback'     => 'kungfu_2026_can_edit_chinese_version',
		)
	);
}
add_action( 'init', 'kungfu_2026_register_chinese_meta' );

/**
 * Who may write the Chinese version: whoever may edit the chapter.
 *
 * @param bool   $allowed  Unused; the default answer.
 * @param string $meta_key Unused.
 * @param int    $post_id  Chapter ID.
 * @return bool
 */
function kungfu_2026_can_edit_chinese_version( $allowed, $meta_key, $post_id ) {
	return current_user_can( 'edit_post', $post_id );
}

/**
 * Sanitize the Chinese content the same way core sanitizes post_content.
 *
 * Block delimiters are HTML comments, which kses passes through untouched, so
 * this strips disallowed markup without breaking the block structure.
 *
 * @param string $value Serialized blocks.
 * @return string
 */
function kungfu_2026_sanitize_chinese_content( $value ) {
	$value = (string) $value;

	return current_user_can( 'unfiltered_html' ) ? $value : wp_kses_post( $value );
}

/**
 * Add the meta box.
 *
 * Only on a block-editor screen: the UI is React mounted into an empty div, so
 * on a classic-editor screen the box would render as a blank panel.
 */
function kungfu_2026_add_chinese_meta_box() {
	$screen = get_current_screen();

	if ( ! $screen || ! $screen->is_block_editor() ) {
		return;
	}

	add_meta_box(
		'akw-chinese-version',
		__( 'Chinese version', 'kungfu_2026' ),
		'kungfu_2026_render_chinese_meta_box',
		AKW_CHAPTER,
		'normal',
		'high',
		array( '__block_editor_compatible_meta_box' => true )
	);
}
add_action( 'add_meta_boxes', 'kungfu_2026_add_chinese_meta_box' );

/**
 * The mount point. Everything inside it is built by js/chinese-version.js.
 */
function kungfu_2026_render_chinese_meta_box() {
	// Not "akw-chinese-version": add_meta_box() already gave the surrounding
	// postbox that id, and a duplicate would send getElementById() to the
	// postbox instead — React would then render over the box's own header.
	echo '<div id="akw-chinese-version-root" class="akw-zh-root"></div>';
}

/**
 * Load the editor UI.
 */
function kungfu_2026_chinese_version_assets() {
	$screen = get_current_screen();

	if ( ! $screen || AKW_CHAPTER !== $screen->post_type ) {
		return;
	}

	$version = wp_get_theme()->get( 'Version' );

	wp_enqueue_script(
		'kungfu-2026-chinese-version',
		get_theme_file_uri( 'js/chinese-version.js' ),
		array(
			'wp-block-editor',
			'wp-block-library',
			'wp-blocks',
			'wp-components',
			'wp-core-data',
			'wp-data',
			'wp-editor',
			'wp-element',
			'wp-i18n',
		),
		$version,
		true
	);

	wp_set_script_translations( 'kungfu-2026-chinese-version', 'kungfu_2026' );

	wp_enqueue_style(
		'kungfu-2026-chinese-version',
		get_theme_file_uri( 'css/chinese-version.css' ),
		array( 'wp-edit-blocks' ),
		$version
	);
}
add_action( 'enqueue_block_editor_assets', 'kungfu_2026_chinese_version_assets' );

/**
 * The Chinese title of a chapter.
 *
 * @param int|WP_Post|null $post Chapter.
 * @return string Empty when none has been written.
 */
function akw_get_zh_title( $post = null ) {
	$post = get_post( $post );

	return $post ? (string) get_post_meta( $post->ID, AKW_TITLE_ZH, true ) : '';
}

/**
 * The raw Chinese content of a chapter, as stored: serialized blocks.
 *
 * @param int|WP_Post|null $post Chapter.
 * @return string
 */
function akw_get_raw_zh_content( $post = null ) {
	$post = get_post( $post );

	return $post ? (string) get_post_meta( $post->ID, AKW_CONTENT_ZH, true ) : '';
}

/**
 * Whether a chapter has a Chinese version worth linking to.
 *
 * @param int|WP_Post|null $post Chapter.
 * @return bool
 */
function akw_has_zh_version( $post = null ) {
	return '' !== akw_get_zh_title( $post ) || '' !== trim( akw_get_raw_zh_content( $post ) );
}

/**
 * The Chinese content, rendered.
 *
 * Runs the block markup through do_blocks() and the two texturizing filters, so
 * it comes out the way the_content() would. Deliberately not the_content
 * itself: that filter chain is full of plugins that assume they are looking at
 * the post's own body.
 *
 * @param int|WP_Post|null $post Chapter.
 * @return string HTML, ready to echo.
 */
function akw_get_zh_content( $post = null ) {
	$raw = akw_get_raw_zh_content( $post );

	if ( '' === trim( $raw ) ) {
		return '';
	}

	$html = do_blocks( $raw );
	$html = wptexturize( $html );
	$html = convert_smilies( $html );

	return apply_filters( 'akw_zh_content', $html, get_post( $post ) );
}

/**
 * Echo the Chinese content.
 *
 * @param int|WP_Post|null $post Chapter.
 */
function akw_the_zh_content( $post = null ) {
	echo akw_get_zh_content( $post ); // phpcs:ignore WordPress.Security.EscapeOutput -- Block markup, sanitized on save and rendered by do_blocks().
}
