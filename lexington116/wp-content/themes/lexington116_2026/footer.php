<?php
/** Shared footer and booking information. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$phone   = is_page( 'menu' ) ? '' : lexington116_contact_detail( 'phone' );
$address = is_page( 'menu' ) ? '' : lexington116_contact_detail( 'address' );
?>
<footer class="site-footer" id="visit">
    <div class="footer-inner">
        <div><span class="footer-name">Lumi Massage</span><p>&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?>, designed and built by <a href="https://369usa.com/">369usa</a>.</p></div>
        <div class="visit-details">
            <?php if ( $address ) : ?><p><?php echo esc_html( $address ); ?></p><?php endif; ?>
            <?php if ( $phone ) : ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a><?php endif; ?>
            <?php lexington116_booking_link( 'Book an appointment ↗', 'footer-booking' ); ?>
        </div>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
