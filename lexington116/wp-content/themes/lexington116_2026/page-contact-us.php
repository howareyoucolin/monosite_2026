<?php
/** Contact Lumi: location, phone, and business hours. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$phone = lexington116_contact_detail( 'phone' );
$address = lexington116_contact_detail( 'address' );
get_header();
?>
<main id="main" class="inner-page section-inner">
    <header class="page-intro"><p class="eyebrow">Lumi Massage</p><h1>Contact <em>us.</em></h1><p class="page-lead">Visit us on Lexington Avenue, near East 28th Street.</p></header>
    <section class="visit-grid contact-details-grid" aria-label="Location and contact details">
        <article><p class="eyebrow">Get in touch</p><h2>Call Lumi</h2><a class="contact-phone" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a><p>Call to ask about availability and appointments.</p></article>
        <article><p class="eyebrow">Business hours</p><h2><?php echo esc_html( lexington116_contact_detail( 'hours' ) ); ?></h2><p>New customer special<br>9:30 am – 2:30 pm</p></article>
        <article class="contact-location">
            <p class="eyebrow">Find us</p><h2>Our location</h2><p><?php echo esc_html( $address ); ?></p>
            <div class="contact-map" aria-busy="true">
                <div class="map-placeholder" role="status"><span class="map-placeholder-icon" aria-hidden="true">⌖</span><span>Loading map…</span></div>
                <iframe class="contact-map-frame" data-src="<?php echo esc_url( 'https://www.google.com/maps?q=' . rawurlencode( $address ) . '&output=embed' ); ?>" title="Google map showing Lumi Massage at <?php echo esc_attr( $address ); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
            </div>
            <a class="map-larger-link" href="<?php echo esc_url( 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $address ) ); ?>" target="_blank" rel="noopener noreferrer">View larger map ↗</a>
        </article>
    </section>
    <?php while ( have_posts() ) : the_post(); if ( get_the_content() ) : ?><div class="entry-content page-extra"><?php the_content(); ?></div><?php endif; endwhile; ?>
</main>
<?php get_footer(); ?>
