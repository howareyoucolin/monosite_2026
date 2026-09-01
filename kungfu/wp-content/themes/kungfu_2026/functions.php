<?php
/**
 * Kungfu 2026 theme setup.
 *
 * @package kungfu_2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare the handful of features the starter needs.
 */
function kungfu_2026_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ) );

	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'kungfu_2026' ),
		)
	);
}
add_action( 'after_setup_theme', 'kungfu_2026_setup' );

/**
 * Load the stylesheet.
 */
function kungfu_2026_scripts() {
	wp_enqueue_style(
		'kungfu-2026-style',
		get_stylesheet_uri(),
		array(),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'kungfu_2026_scripts' );

/**
 * Chapter/series/arc content model.
 */
require_once get_theme_file_path( 'inc/content-model.php' );
require_once get_theme_file_path( 'inc/numbering.php' );
require_once get_theme_file_path( 'inc/template-tags.php' );

if ( is_admin() ) {
	require_once get_theme_file_path( 'inc/admin.php' );
}

/**
 * The chapter and series permalinks only exist once their rules are rebuilt.
 */
function kungfu_2026_flush_rewrites() {
	kungfu_2026_register_chapter_type();
	kungfu_2026_register_series_taxonomy();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'kungfu_2026_flush_rewrites' );
