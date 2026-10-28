<?php
/** Theme setup, assets, and optional booking/contact settings. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'after_setup_theme', function () {
    add_theme_support( 'title-tag' );
    add_theme_support( 'html5', array( 'style', 'script' ) );
    add_theme_support( 'responsive-embeds' );
} );

add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style( 'lexington116-fonts', get_theme_file_uri( '/assets/fonts.css' ), array(), (string) filemtime( get_stylesheet_directory() . '/assets/fonts.css' ) );
    wp_enqueue_style( 'lexington116', get_stylesheet_uri(), array( 'lexington116-fonts' ), (string) filemtime( get_stylesheet_directory() . '/style.css' ) );
    wp_enqueue_script( 'lexington116', get_theme_file_uri( '/assets/site.js' ), array(), (string) filemtime( get_stylesheet_directory() . '/assets/site.js' ), true );
} );

// Prioritize the hero poster so the frame appears while the video buffers.
add_action( 'wp_head', function () {
    if ( is_front_page() ) {
        printf( '<link rel="preload" as="image" href="%s" fetchpriority="high">' . "\n", esc_url( get_theme_file_uri( '/assets/massage-poster.jpg' ) ) );
    }
}, 2 );

/** Shared contact details, overridable through the WordPress Customizer. */
function lexington116_contact_detail( $key ) {
    $defaults = array(
        'phone' => '646-298-5898',
        'address' => '116 Lexington Ave, 2nd Floor, New York, NY 10016',
        'hours' => '9:30 am – 9:30 pm',
    );
    if ( ! isset( $defaults[ $key ] ) ) {
        return '';
    }
    return get_theme_mod( 'lexington116_' . $key, '' ) ?: $defaults[ $key ];
}

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
        'hours'       => array( 'Business hours', 'text', 'sanitize_text_field' ),
    ) as $key => $field ) {
        $setting = 'lexington116_' . $key;
        $customizer->add_setting( $setting, array( 'default' => 'booking_url' === $key ? '' : lexington116_contact_detail( $key ), 'sanitize_callback' => $field[2] ) );
        $customizer->add_control( $setting, array( 'label' => $field[0], 'type' => $field[1], 'section' => 'lexington116_contact' ) );
    }
} );

/** Create missing navigation pages once, keeping existing page content intact. */
function lexington116_ensure_site_pages() {
    if ( '2' === get_option( 'lexington116_pages_version' ) ) {
        return;
    }
    foreach ( array( 'about-lumi' => 'About Lumi', 'menu' => 'Massage Menu', 'visit-us' => 'Visit Us', 'contact-us' => 'Contact Us' ) as $slug => $title ) {
        if ( ! get_page_by_path( $slug, OBJECT, 'page' ) ) {
            $result = wp_insert_post( array(
                'post_type' => 'page',
                'post_status' => 'publish',
                'post_name' => $slug,
                'post_title' => $title,
                'post_content' => '',
            ), true );
            if ( is_wp_error( $result ) || ! $result ) {
                return;
            }
        }
    }
    update_option( 'lexington116_pages_version', '2' );
}
add_action( 'after_switch_theme', 'lexington116_ensure_site_pages' );
// Existing installations pick up the pages after the next administrator visit.
add_action( 'admin_init', function () {
    if ( current_user_can( 'manage_options' ) ) {
        lexington116_ensure_site_pages();
    }
} );

function lexington116_page_url( $slug ) {
    $page = get_page_by_path( $slug, OBJECT, 'page' );
    return $page ? get_permalink( $page ) : home_url( '/' . $slug . '/' );
}
