<?php
/** Preview appointment page, adapted from deyutcm's two-step booking layout. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
if ( ! lexington116_booking_preview_enabled() ) : ?>
<main id="main" class="inner-page section-inner" data-booking-unavailable>
    <header class="page-intro"><p class="eyebrow">Lumi Massage</p><h1>Online booking<br><em>is coming soon.</em></h1><p class="page-lead">Online booking is under construction. Please call <?php echo esc_html( lexington116_contact_detail( 'phone' ) ); ?> for appointments.</p></header>
</main>
<?php else :
    list( $min_date, $max_date ) = lexington116_booking_window();
?>
<main id="main" class="inner-page booking-page section-inner">
    <header class="page-intro"><p class="eyebrow">Lumi Massage · Appointment request</p><h1>Book your<br><em>massage.</em></h1><p class="page-lead">Choose a preferred date and time, then leave your contact details. Our team will confirm availability; submitting a request does not confirm an appointment.</p></header>
    <?php if ( isset( $_GET['request'] ) && 'received' === $_GET['request'] ) : ?>
        <section class="booking-success" role="status"><h2>Request received.</h2><p>Thank you. Our team will contact you to confirm your appointment.</p></section>
    <?php else : ?>
    <form class="booking-form" method="post" action="<?php echo esc_url( add_query_arg( 'ff', '1', admin_url( 'admin-post.php' ) ) ); ?>">
        <input type="hidden" name="action" value="lumi_booking">
        <?php wp_nonce_field( 'lumi_booking_request', 'lumi_booking_nonce' ); ?>
        <div class="booking-honeypot" aria-hidden="true"><label for="booking-website">Website</label><input id="booking-website" name="website" tabindex="-1" autocomplete="off"></div>
        <section class="booking-schedule" aria-labelledby="schedule-title">
            <h2 id="schedule-title"><span>01</span> Date &amp; time</h2>
            <p class="booking-hint">Request a time within the next four weeks.</p>
            <div id="booking-calendar" data-min="<?php echo esc_attr( $min_date ); ?>" data-max="<?php echo esc_attr( $max_date ); ?>" aria-label="Choose an appointment date"></div>
            <input id="booking-date" name="date" type="hidden">
            <p class="booking-selection"><span>Preferred date</span><output id="booking-date-display" aria-live="polite">Choose a date from the calendar</output></p>
            <fieldset class="booking-time-fieldset"><legend>Choose a time</legend><p class="booking-hint">Business hours: 9:30 am–9:30 pm. Times shown are New York time.</p><div class="booking-times" role="group" aria-label="Available time choices"></div></fieldset>
            <input id="booking-time" name="time" type="hidden">
            <p class="booking-selection"><span>Selected time</span><output id="booking-time-display" aria-live="polite">Choose a time above</output></p>
            <p class="booking-error booking-schedule-error" role="alert" tabindex="-1" hidden></p>
        </section>
        <section class="booking-details" aria-labelledby="details-title">
            <h2 id="details-title"><span>02</span> Your details</h2><p class="booking-hint">Name and phone number are required.</p>
            <div class="booking-field"><label for="booking-duration">Massage session</label><select id="booking-duration" name="duration" required><option value="20">20 minutes · $39</option><option value="30">30 minutes · $49</option><option value="60" selected>60 minutes · $79</option></select></div>
            <div class="booking-field"><label for="booking-name">Your name</label><input id="booking-name" name="name" autocomplete="name" maxlength="150" required></div>
            <div class="booking-field"><label for="booking-phone">Phone number</label><input id="booking-phone" name="phone" type="tel" autocomplete="tel" maxlength="40" pattern="[0-9\s\+\(\)\.\-]{10,40}" title="Enter your phone number, including the area code." placeholder="646-555-0123" required></div>
            <div class="booking-field"><label for="booking-notes">Anything we should know? <span>(optional)</span></label><textarea id="booking-notes" name="notes" rows="5" maxlength="2000" placeholder="Preferences or questions about your visit"></textarea></div>
            <p class="booking-error" role="alert" hidden></p>
            <button class="button booking-submit" type="submit">Request appointment</button>
            <p class="booking-hint booking-help">Prefer to call? <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', lexington116_contact_detail( 'phone' ) ) ); ?>"><?php echo esc_html( lexington116_contact_detail( 'phone' ) ); ?></a></p>
        </section>
    </form>
    <?php endif; ?>
</main>
<?php endif; get_footer(); ?>
