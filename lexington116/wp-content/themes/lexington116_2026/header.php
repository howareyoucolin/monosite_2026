<?php
/** Shared site header. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header">
    <div class="header-inner">
        <a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Lumi Massage home">
            <span class="brand-name">Lumi Massage</span>
            <span class="brand-description">Massage &amp; relaxation</span>
        </a>
        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-navigation">
            <span>Menu</span><span class="menu-lines" aria-hidden="true"></span>
        </button>
        <nav id="primary-navigation" class="primary-navigation" aria-label="Main navigation">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>"<?php if ( is_front_page() ) { echo ' aria-current="page"'; } ?>>Home</a>
            <a href="<?php echo esc_url( lexington116_page_url( 'about-lumi' ) ); ?>"<?php if ( is_page( 'about-lumi' ) ) { echo ' aria-current="page"'; } ?>>About Lumi</a>
            <a href="<?php echo esc_url( lexington116_page_url( 'menu' ) ); ?>"<?php if ( is_page( 'menu' ) ) { echo ' aria-current="page"'; } ?>>Menu</a>
            <a href="<?php echo esc_url( lexington116_page_url( 'contact-us' ) ); ?>"<?php if ( is_page( 'contact-us' ) ) { echo ' aria-current="page"'; } ?>>Contact us</a>
            <?php lexington116_booking_link( 'Book an appointment', 'button button-small' ); ?>
        </nav>
    </div>
</header>
