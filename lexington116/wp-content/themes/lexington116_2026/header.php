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
            <a href="<?php echo esc_url( home_url( '/#home' ) ); ?>">Home</a>
            <a href="<?php echo esc_url( home_url( '/#approach' ) ); ?>">Our approach</a>
            <a href="<?php echo esc_url( home_url( '/#visit' ) ); ?>">Visit us</a>
            <?php lexington116_booking_link( 'Book a visit', 'button button-small' ); ?>
        </nav>
    </div>
</header>
