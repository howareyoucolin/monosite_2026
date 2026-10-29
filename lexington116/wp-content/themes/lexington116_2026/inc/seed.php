<?php
/** Idempotent site content and permalink seeder. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function lexington116_seed_site() {
    $definitions = array(
        'home'       => 'Home',
        'about-lumi' => 'About Lumi',
        'menu'       => 'Massage Menu',
        'contact-us' => 'Contact Us',
        'booking'    => 'Book an Appointment',
        'visit-us'   => 'Visit Us',
    );
    $result = array( 'created' => array(), 'updated' => array(), 'unchanged' => array() );
    $page_ids = array();

    foreach ( $definitions as $slug => $title ) {
        $page = get_page_by_path( $slug, OBJECT, 'page' );
        if ( ! $page ) {
            $id = wp_insert_post( array(
                'post_type'    => 'page',
                'post_status'  => 'publish',
                'post_name'    => $slug,
                'post_title'   => $title,
                'post_content' => '',
            ), true );
            if ( is_wp_error( $id ) || ! $id ) {
                return is_wp_error( $id ) ? $id : new WP_Error( 'lumi_seed_page_failed', 'Could not create ' . $title . '.' );
            }
            $result['created'][] = $slug;
            $page_ids[ $slug ] = (int) $id;
            continue;
        }

        $changes = array( 'ID' => $page->ID );
        if ( 'publish' !== $page->post_status ) $changes['post_status'] = 'publish';
        if ( $title !== $page->post_title ) $changes['post_title'] = $title;
        if ( $slug !== $page->post_name ) $changes['post_name'] = $slug;
        if ( count( $changes ) > 1 ) {
            $updated = wp_update_post( $changes, true );
            if ( is_wp_error( $updated ) || ! $updated ) {
                return is_wp_error( $updated ) ? $updated : new WP_Error( 'lumi_seed_page_failed', 'Could not update ' . $title . '.' );
            }
            $result['updated'][] = $slug;
        } else {
            $result['unchanged'][] = $slug;
        }
        $page_ids[ $slug ] = (int) $page->ID;
    }

    update_option( 'show_on_front', 'page' );
    update_option( 'page_on_front', $page_ids['home'] );
    update_option( 'permalink_structure', '/%postname%/' );
    update_option( 'lexington116_pages_version', '4' );
    flush_rewrite_rules( false );

    $result['front_page'] = $page_ids['home'];
    $result['permalink_structure'] = '/%postname%/';
    return $result;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
    WP_CLI::add_command( 'lumi seed', function () {
        $result = lexington116_seed_site();
        if ( is_wp_error( $result ) ) {
            WP_CLI::error( $result->get_error_message() );
        }
        foreach ( array( 'created', 'updated', 'unchanged' ) as $status ) {
            WP_CLI::log( ucfirst( $status ) . ': ' . ( $result[ $status ] ? implode( ', ', $result[ $status ] ) : 'none' ) );
        }
        WP_CLI::log( 'Front page: Home (ID ' . $result['front_page'] . ')' );
        WP_CLI::log( 'Permalinks: ' . $result['permalink_structure'] );
        WP_CLI::success( 'Lumi site seed complete.' );
    } );
}
