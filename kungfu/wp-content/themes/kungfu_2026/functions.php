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
