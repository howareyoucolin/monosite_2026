<?php
/** Shared footer and booking information. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$phone   = get_theme_mod( 'lexington116_phone', '' );
$address = get_theme_mod( 'lexington116_address', '' );
?>
<footer class="site-footer" id="visit">
    <div class="footer-inner">
        <div><span class="footer-name">116 Lexington Massage</span><p>A little time. A little calm. All yours.</p></div>
        <div class="visit-details">
            <?php if ( $address ) : ?><p><?php echo esc_html( $address ); ?></p><?php endif; ?>
            <?php if ( $phone ) : ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a><?php endif; ?>
            <?php lexington116_booking_link( 'Plan your visit ↗', 'footer-booking' ); ?>
        </div>
    </div>
</footer>
<dialog class="booking-dialog" id="booking-dialog" aria-labelledby="booking-title">
    <button type="button" class="dialog-close" aria-label="Close booking information">×</button>
    <p class="eyebrow">116 Lexington Massage</p>
    <h2 id="booking-title">Your moment<br>of <em>calm awaits.</em></h2>
    <?php if ( $phone ) : ?>
        <p>Call us to plan your visit.</p><a class="button" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
    <?php else : ?>
        <p>Online booking is coming soon.<br>We look forward to welcoming you. Please check back for appointment details.</p>
    <?php endif; ?>
    <?php if ( $address ) : ?><p class="dialog-address"><?php echo esc_html( $address ); ?></p><?php endif; ?>
</dialog>
<?php wp_footer(); ?>
</body>
</html>
