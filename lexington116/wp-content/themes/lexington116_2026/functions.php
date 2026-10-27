<?php
/** Theme setup and assets. */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'after_setup_theme', function () {
    add_theme_support( 'title-tag' );
    add_theme_support( 'html5', array( 'style', 'script' ) );
} );

add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style( 'lexington116', get_stylesheet_uri(), array(), wp_get_theme()->get( 'Version' ) );
} );
