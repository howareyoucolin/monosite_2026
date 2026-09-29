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
 * The two faces the design is built on.
 *
 * Coustard is the menu, Poppins everything else — including the masthead, which
 * used to be set in UnifrakturMaguntia. That blackletter is gone: a wordmark
 * only has to be read, and a long site name in gothic capitals is the one place
 * it is hardest. Dropping it also drops a webfont request.
 *
 * Version is null because Google serves its own versioned URLs, and a ?ver=
 * query on top of that only breaks their caching.
 */
function kungfu_2026_fonts_url() {
	return 'https://fonts.googleapis.com/css2?family=Coustard&family=Poppins:wght@400;500;600&display=swap';
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

	// Every page has the back-to-top button, so unlike the copy script this one
	// is not conditional.
	wp_enqueue_script(
		'kungfu-2026-back-to-top',
		get_theme_file_uri( 'js/back-to-top.js' ),
		array(),
		wp_get_theme()->get( 'Version' ),
		true
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
 * Make browsers re-check a page before reusing it.
 *
 * The host adds "Cache-Control: max-age=600" to every page it serves to a
 * logged-out visitor. A phone that opened the site before signing in then kept
 * showing that logged-out copy — no admin bar — for ten minutes after, because
 * mobile Safari reuses it without asking and does not honour "Vary: Cookie".
 *
 * "no-cache" still lets the browser store the page (the back button stays
 * instant); it only has to confirm with the server first. The Expires date in
 * the past matters as much as the header: mod_expires leaves a response alone
 * once it already carries an Expires, which is what keeps the host from adding
 * its ten minutes back. Signed-in requests already get WordPress's own
 * no-cache headers, so those are left as they are.
 *
 * @param array $headers Response headers.
 * @return array
 */
function kungfu_2026_revalidate_pages( $headers ) {
	if ( is_user_logged_in() ) {
		return $headers;
	}

	$headers['Cache-Control'] = 'no-cache';
	$headers['Expires']       = 'Wed, 11 Jan 1984 05:00:00 GMT';

	return $headers;
}
add_filter( 'wp_headers', 'kungfu_2026_revalidate_pages' );

/**
 * Chapter/arc content model.
 */
require_once get_theme_file_path( 'inc/content-model.php' );
require_once get_theme_file_path( 'inc/template-tags.php' );

/**
 * The Chinese title and Chinese body a chapter carries alongside its own.
 */
require_once get_theme_file_path( 'inc/chinese-version.php' );

/**
 * Which of the two a visitor is reading.
 */
require_once get_theme_file_path( 'inc/language.php' );

/**
 * The letter count and copy button a chapter page carries.
 */
require_once get_theme_file_path( 'inc/chapter-tools.php' );

/**
 * The comment thread under a chapter.
 */
require_once get_theme_file_path( 'inc/comments.php' );

if ( is_admin() ) {
	require_once get_theme_file_path( 'inc/admin.php' );

	// The "Format sections" button in the chapter editor.
	require_once get_theme_file_path( 'inc/format-sections.php' );
}
