<?php
/**
 * "Format sections" and "Clear content" buttons in the block editor's top toolbar.
 *
 * Chapters arrive pasted, with their section titles as ordinary paragraphs —
 * "Section 1: The Gate", "**Section 2 - Rain**", sometimes run into the first
 * line of the body. The button turns each of those into the one shape the
 * theme styles: <h3 class="wp-block-heading">Section 1: The Gate</h3>.
 * The same click strips bold from everything that is not a heading.
 *
 * A "Clear content" button beside it empties the body for the next paste. It
 * never saves, and it pauses autosave until the post has content again.
 *
 * All of it happens in the editor (js/format-sections.js), as ordinary block
 * edits: nothing is saved until the post is, and one undo reverts it.
 *
 * @package kungfu_2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load the buttons on the chapter editor.
 */
function kungfu_2026_format_sections_assets() {
	$screen = get_current_screen();

	if ( ! $screen || AKW_CHAPTER !== $screen->post_type ) {
		return;
	}

	wp_enqueue_script(
		'kungfu-2026-format-sections',
		get_theme_file_uri( 'js/format-sections.js' ),
		array( 'wp-blocks', 'wp-components', 'wp-data', 'wp-dom-ready', 'wp-element', 'wp-i18n' ),
		wp_get_theme()->get( 'Version' ),
		true
	);

	wp_set_script_translations( 'kungfu-2026-format-sections', 'kungfu_2026' );

	wp_enqueue_style(
		'kungfu-2026-format-sections',
		get_theme_file_uri( 'css/format-sections.css' ),
		array(),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'enqueue_block_editor_assets', 'kungfu_2026_format_sections_assets' );
