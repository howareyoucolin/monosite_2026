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
 * The three faces the design is built on.
 *
 * UnifrakturMaguntia is the masthead, Coustard the menu, Poppins everything
 * else. Version is null because Google serves its own versioned URLs, and a
 * ?ver= query on top of that only breaks their caching.
 */
function kungfu_2026_fonts_url() {
	return 'https://fonts.googleapis.com/css2?family=Coustard&family=Poppins:wght@400;500;600&family=UnifrakturMaguntia&display=swap';
}

/**
 * Load the fonts and the stylesheet.
 */
function kungfu_2026_scripts() {
	wp_enqueue_style( 'kungfu-2026-fonts', kungfu_2026_fonts_url(), array(), null );

	wp_enqueue_style(
		'kungfu-2026-style',
		get_stylesheet_uri(),
		array( 'kungfu-2026-fonts' ),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'kungfu_2026_scripts' );

/**
 * Open the connection to the font host while the page is still parsing.
 *
 * @param string[] $urls          URLs for the relation.
 * @param string   $relation_type Hint type.
 * @return array
 */
function kungfu_2026_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' === $relation_type && wp_style_is( 'kungfu-2026-fonts', 'queue' ) ) {
		$urls[] = array(
			'href'        => 'https://fonts.gstatic.com',
			'crossorigin' => '',
		);
	}

	return $urls;
}
add_filter( 'wp_resource_hints', 'kungfu_2026_resource_hints', 10, 2 );

/**
 * Chapter/arc content model.
 */
require_once get_theme_file_path( 'inc/content-model.php' );
require_once get_theme_file_path( 'inc/template-tags.php' );

if ( is_admin() ) {
	require_once get_theme_file_path( 'inc/admin.php' );
}
