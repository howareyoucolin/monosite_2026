<?php
/** Theme setup, assets, and optional booking/contact settings. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'after_setup_theme', function () {
    add_theme_support( 'title-tag' );
    add_theme_support( 'html5', array( 'style', 'script' ) );
    add_theme_support( 'responsive-embeds' );
} );

add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style( 'lexington116-fonts', get_theme_file_uri( '/assets/fonts.css' ), array(), '1.1.0' );
    wp_enqueue_style( 'lexington116', get_stylesheet_uri(), array( 'lexington116-fonts' ), (string) filemtime( get_stylesheet_directory() . '/style.css' ) );
    wp_enqueue_script( 'lexington116', get_theme_file_uri( '/assets/site.js' ), array(), (string) filemtime( get_stylesheet_directory() . '/assets/site.js' ), true );
} );

/** Use a configured booking URL, or show the honest coming-soon/contact dialog. */
function lexington116_booking_link( $label, $class ) {
    $url = get_theme_mod( 'lexington116_booking_url', '' );
    if ( $url ) {
        printf( '<a class="%s" href="%s">%s</a>', esc_attr( $class ), esc_url( $url ), esc_html( $label ) );
    } else {
        printf( '<button type="button" class="%s" data-open-booking>%s</button>', esc_attr( $class ), esc_html( $label ) );
    }
}

add_action( 'customize_register', function ( $customizer ) {
    $customizer->add_section( 'lexington116_design', array( 'title' => 'Lexington116 · Hero design' ) );
    $customizer->add_setting( 'lexington116_hero_shape', array(
        'default' => 'b',
        'sanitize_callback' => function ( $value ) {
            return in_array( $value, array( 'a', 'b' ), true ) ? $value : 'b';
        },
    ) );
    $customizer->add_control( 'lexington116_hero_shape', array(
        'label' => 'Hero image shape',
        'type' => 'select',
        'section' => 'lexington116_design',
        'choices' => array( 'a' => 'Shape A — Arch', 'b' => 'Shape B — Rounded rectangle' ),
    ) );
    $customizer->add_section( 'lexington116_contact', array( 'title' => 'Lexington116 · Booking & contact' ) );
    foreach ( array(
        'booking_url' => array( 'Booking website URL', 'url', 'esc_url_raw' ),
        'phone'       => array( 'Contact phone', 'text', 'sanitize_text_field' ),
        'address'     => array( 'Visit address', 'text', 'sanitize_text_field' ),
    ) as $key => $field ) {
        $setting = 'lexington116_' . $key;
        $customizer->add_setting( $setting, array( 'default' => '', 'sanitize_callback' => $field[2] ) );
        $customizer->add_control( $setting, array( 'label' => $field[0], 'type' => $field[1], 'section' => 'lexington116_contact' ) );
    }
} );
