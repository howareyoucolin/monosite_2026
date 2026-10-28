<?php
/** Shared footer and booking information. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$phone   = get_theme_mod( 'lexington116_phone', '' );
$address = get_theme_mod( 'lexington116_address', '' );
?>
<footer class="site-footer" id="visit">
    <div class="footer-inner">
        <div><span class="footer-name">Lumi Massage</span><p>Call or book your next massage appointment.</p></div>
        <div class="visit-details">
            <?php if ( $address ) : ?><p><?php echo esc_html( $address ); ?></p><?php endif; ?>
            <?php if ( $phone ) : ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a><?php endif; ?>
            <?php lexington116_booking_link( 'Book an appointment ↗', 'footer-booking' ); ?>
        </div>
    </div>
</footer>
<dialog class="booking-dialog" id="booking-dialog" aria-labelledby="booking-title">
    <button type="button" class="dialog-close" aria-label="Close booking information">×</button>
    <p class="eyebrow">Lumi Massage</p>
    <h2 id="booking-title">Book your<br><em>massage.</em></h2>
    <?php if ( $phone ) : ?>
        <p>Call us to check availability and book your massage.</p><a class="button" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
    <?php else : ?>
        <p>Online booking is coming soon.<br>Please check back for massage appointments and contact details.</p>
    <?php endif; ?>
    <?php if ( $address ) : ?><p class="dialog-address"><?php echo esc_html( $address ); ?></p><?php endif; ?>
</dialog>
<?php wp_footer(); ?>
</body>
</html>
