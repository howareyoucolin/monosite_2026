<?php
/** Feature-gated appointment requests for Lumi. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function lexington116_booking_preview_enabled() {
    return isset( $_GET['ff'] ) && is_string( $_GET['ff'] ) && '1' === $_GET['ff'];
}

function lexington116_preview_url( $url ) {
    return lexington116_booking_preview_enabled() ? add_query_arg( 'ff', '1', $url ) : $url;
}

function lexington116_booking_window() {
    $today = new DateTimeImmutable( 'today', new DateTimeZone( 'America/New_York' ) );
    return array( $today->modify( '+1 day' )->format( 'Y-m-d' ), $today->modify( '+28 days' )->format( 'Y-m-d' ) );
}

function lexington116_external_notifications_enabled() {
    $host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
    $local_hosts = array( 'localhost', '127.0.0.1', '::1' );
    $enabled = 'production' === wp_get_environment_type() && ! in_array( $host, $local_hosts, true );
    return (bool) apply_filters( 'lexington116_external_notifications_enabled', $enabled );
}

function lexington116_booking_notification_message( $appointment_id, $data ) {
    return sprintf(
        "Lumi appointment request #%d\n\nDate: %s\nTime: %s\nSession: %s minutes\nName: %s\nPhone: %s%s",
        (int) $appointment_id,
        $data['date'],
        $data['time'],
        $data['duration'],
        $data['name'],
        $data['phone'],
        $data['notes'] ? "\nNotes: " . $data['notes'] : ''
    );
}

function lexington116_send_booking_email( $appointment_id, $data ) {
    if ( ! lexington116_external_notifications_enabled() ) {
        return new WP_Error( 'lumi_notifications_disabled', 'External notifications are disabled outside production.' );
    }
    $to = defined( 'LUMI_BOOKING_EMAIL' ) ? sanitize_email( LUMI_BOOKING_EMAIL ) : 'howareyoucolin@gmail.com';
    if ( ! is_email( $to ) ) {
        return new WP_Error( 'lumi_email_not_configured', 'The booking email address is invalid.' );
    }
    $subject = sprintf( '(#%d) Lumi appointment request from %s', (int) $appointment_id, $data['name'] );
    $sent = wp_mail( $to, $subject, lexington116_booking_notification_message( $appointment_id, $data ), array( 'Content-Type: text/plain; charset=UTF-8' ) );
    return $sent ? true : new WP_Error( 'lumi_email_failed', 'WordPress could not send the booking email.' );
}

/**
 * Send a staff notification through Textbelt.
 *
 * Define LUMI_TEXTBELT_API_KEY and LUMI_TEXTBELT_NOTIFY_PHONE in wp-config.php.
 * Keeping credentials outside the theme prevents them from being committed or
 * exposed when the theme source is shared.
 */
function lexington116_send_booking_sms( $appointment_id, $data ) {
    if ( ! lexington116_external_notifications_enabled() ) {
        return new WP_Error( 'lumi_notifications_disabled', 'External notifications are disabled outside production.' );
    }
    $config = apply_filters( 'lexington116_booking_sms_config', array(
        'api_key' => defined( 'LUMI_TEXTBELT_API_KEY' ) ? trim( (string) LUMI_TEXTBELT_API_KEY ) : '',
        'phone'   => defined( 'LUMI_TEXTBELT_NOTIFY_PHONE' ) ? trim( (string) LUMI_TEXTBELT_NOTIFY_PHONE ) : '',
    ) );
    $api_key = isset( $config['api_key'] ) && is_string( $config['api_key'] ) ? trim( $config['api_key'] ) : '';
    $phone = isset( $config['phone'] ) && is_string( $config['phone'] ) ? preg_replace( '/[^0-9+]/', '', $config['phone'] ) : '';

    if ( ! $api_key || ! $phone ) {
        return new WP_Error( 'lumi_sms_not_configured', 'Textbelt is not configured.' );
    }

    $message = lexington116_booking_notification_message( $appointment_id, $data );
    $response = wp_remote_post( 'https://textbelt.com/text', array(
        'timeout' => 20,
        'body'    => array(
            'phone'   => $phone,
            'message' => $message,
            'key'     => $api_key,
        ),
    ) );
    if ( is_wp_error( $response ) ) {
        return new WP_Error( 'lumi_sms_request_failed', $response->get_error_message() );
    }

    $status_code = wp_remote_retrieve_response_code( $response );
    $result = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( $status_code < 200 || $status_code >= 300 || ! is_array( $result ) || empty( $result['success'] ) ) {
        $error = is_array( $result ) && ! empty( $result['error'] ) ? sanitize_text_field( $result['error'] ) : 'Textbelt rejected the message.';
        return new WP_Error( 'lumi_sms_rejected', $error );
    }

    return $result;
}

add_action( 'init', function () {
    register_post_type( 'lumi_appointment', array(
        'labels' => array( 'name' => 'Appointment requests', 'singular_name' => 'Appointment request' ),
        'public' => false,
        'show_ui' => true,
        'show_in_rest' => false,
        'menu_icon' => 'dashicons-calendar-alt',
        'supports' => array( 'title' ),
        'map_meta_cap' => false,
        'capabilities' => array_fill_keys( array( 'edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts', 'publish_posts', 'read_private_posts', 'delete_posts', 'delete_private_posts', 'delete_published_posts', 'delete_others_posts', 'edit_private_posts', 'edit_published_posts', 'create_posts' ), 'manage_options' ),
    ) );
} );

add_action( 'add_meta_boxes_lumi_appointment', function () {
    add_meta_box( 'lumi_request_details', 'Request details', function ( $post ) {
        foreach ( array( 'date' => 'Preferred date', 'time' => 'Preferred time', 'duration' => 'Minutes', 'name' => 'Name', 'phone' => 'Phone', 'notes' => 'Notes' ) as $key => $label ) {
            echo '<p><strong>' . esc_html( $label ) . ':</strong> ' . nl2br( esc_html( get_post_meta( $post->ID, '_lumi_' . $key, true ) ) ) . '</p>';
        }
        $sms_status = get_post_meta( $post->ID, '_lumi_sms_status', true );
        $email_status = get_post_meta( $post->ID, '_lumi_email_status', true );
        echo '<p><strong>Staff email:</strong> ' . esc_html( $email_status ? $email_status : 'Not attempted' ) . '</p>';
        echo '<p><strong>Staff SMS:</strong> ' . esc_html( $sms_status ? $sms_status : 'Not attempted' ) . '</p>';
        echo '<p>This is an appointment request. Contact the guest to confirm availability.</p>';
    }, 'lumi_appointment', 'normal', 'high' );
} );

function lexington116_submit_booking() {
    if ( ! lexington116_booking_preview_enabled() ) {
        wp_die( 'Online booking is under construction. Please call Lumi Massage.', 'Booking unavailable', array( 'response' => 403 ) );
    }
    if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
        wp_die( 'Please use the appointment form.', 'Invalid request', array( 'response' => 405 ) );
    }
    $nonce = isset( $_POST['lumi_booking_nonce'] ) && is_string( $_POST['lumi_booking_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['lumi_booking_nonce'] ) ) : '';
    if ( ! wp_verify_nonce( $nonce, 'lumi_booking_request' ) ) {
        wp_die( 'Your form has expired. Please reload the booking page.', 'Form expired', array( 'response' => 403 ) );
    }
    $data = array();
    foreach ( array( 'date', 'time', 'duration', 'name', 'phone', 'notes', 'website' ) as $key ) {
        $raw = isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
        $data[ $key ] = 'notes' === $key ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
    }
    if ( $data['website'] ) {
        wp_die( 'Unable to accept this request.', 'Invalid request', array( 'response' => 400 ) );
    }
    list( $min_date, $max_date ) = lexington116_booking_window();
    $date = DateTimeImmutable::createFromFormat( '!Y-m-d', $data['date'], new DateTimeZone( 'America/New_York' ) );
    $valid_date = $date && $date->format( 'Y-m-d' ) === $data['date'] && $data['date'] >= $min_date && $data['date'] <= $max_date;
    $valid_time = preg_match( '/^([01][0-9]|2[0-3]):([0-5][0-9])$/', $data['time'] );
    $minutes = $valid_time ? (int) substr( $data['time'], 0, 2 ) * 60 + (int) substr( $data['time'], 3, 2 ) : 0;
    $valid_duration = in_array( $data['duration'], array( '20', '30', '60' ), true );
    $digits = preg_replace( '/\D/', '', $data['phone'] );
    if ( ! $valid_date || ! $valid_time || ! $valid_duration || $minutes < 570 || $minutes + (int) $data['duration'] > 1290 || 0 !== ( $minutes - 570 ) % 30 || ! $data['name'] || strlen( $data['name'] ) > 150 || strlen( $digits ) < 10 || strlen( $digits ) > 15 || ! preg_match( '/^[0-9+().\s-]+$/', $data['phone'] ) || strlen( $data['phone'] ) > 40 || strlen( $data['notes'] ) > 2000 ) {
        wp_die( 'Please enter a valid name and phone number, and choose a date within the next four weeks and a time within business hours.', 'Check your appointment details', array( 'response' => 400, 'back_link' => true ) );
    }
    $id = wp_insert_post( array(
        'post_type' => 'lumi_appointment',
        'post_status' => 'private',
        'post_title' => $data['date'] . ' ' . $data['time'] . ' — ' . $data['name'],
    ), true );
    if ( is_wp_error( $id ) || ! $id ) {
        wp_die( 'We could not save your request. Please call us.', 'Request not saved', array( 'response' => 500 ) );
    }
    foreach ( array( 'date', 'time', 'duration', 'name', 'phone', 'notes' ) as $key ) {
        update_post_meta( $id, '_lumi_' . $key, $data[ $key ] );
    }
    $email = lexington116_send_booking_email( $id, $data );
    if ( is_wp_error( $email ) ) {
        $email_status = 'lumi_notifications_disabled' === $email->get_error_code()
            ? 'Not sent — disabled outside production'
            : 'Failed — ' . $email->get_error_message();
    } else {
        $email_status = 'Sent';
        update_post_meta( $id, '_lumi_email_sent_at', current_time( 'mysql' ) );
    }
    update_post_meta( $id, '_lumi_email_status', sanitize_text_field( $email_status ) );

    $sms = lexington116_send_booking_sms( $id, $data );
    if ( is_wp_error( $sms ) ) {
        if ( 'lumi_notifications_disabled' === $sms->get_error_code() ) {
            $sms_status = 'Not sent — disabled outside production';
        } elseif ( 'lumi_sms_not_configured' === $sms->get_error_code() ) {
            $sms_status = 'Not sent — Textbelt is not configured';
        } else {
            $sms_status = 'Failed — ' . $sms->get_error_message();
        }
    } else {
        $sms_status = 'Sent';
        update_post_meta( $id, '_lumi_sms_sent_at', current_time( 'mysql' ) );
        if ( isset( $sms['quotaRemaining'] ) ) {
            update_post_meta( $id, '_lumi_sms_quota_remaining', (int) $sms['quotaRemaining'] );
        }
    }
    update_post_meta( $id, '_lumi_sms_status', sanitize_text_field( $sms_status ) );
    wp_safe_redirect( add_query_arg( array( 'ff' => '1', 'request' => 'received' ), lexington116_page_url( 'booking' ) ), 303 );
    exit;
}
add_action( 'admin_post_nopriv_lumi_booking', 'lexington116_submit_booking' );
add_action( 'admin_post_lumi_booking', 'lexington116_submit_booking' );

// Prevent shared page caches from mixing the preview with the public modal.
add_action( 'template_redirect', function () {
    if ( lexington116_booking_preview_enabled() || is_page( 'booking' ) ) {
        if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
        nocache_headers();
    }
} );
