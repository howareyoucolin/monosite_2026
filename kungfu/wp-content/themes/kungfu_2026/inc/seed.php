<?php
/** Repeatable setup for the character directory and sidebar navigation. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Use a native archive menu item so its URL follows the current site domain.
 * Existing menu items and character content are preserved.
 *
 * @return array|WP_Error Seeded menu and item IDs, or the WordPress error.
 */
function kungfu_2026_seed_site() {
	if ( ! post_type_exists( AKW_CHARACTER ) ) {
		return new WP_Error( 'kungfu_seed_missing_characters', 'The Characters post type must be registered before seeding.' );
	}

	$locations = get_nav_menu_locations();
	$menu = ! empty( $locations['primary'] ) ? wp_get_nav_menu_object( $locations['primary'] ) : false;
	if ( ! $menu ) {
		$menu = wp_get_nav_menu_object( 'Primary Menu' );
		if ( ! $menu ) {
			$menu_id = wp_create_nav_menu( 'Primary Menu' );
			if ( is_wp_error( $menu_id ) ) {
				return $menu_id;
			}
			$menu = wp_get_nav_menu_object( $menu_id );
		}
		$locations['primary'] = (int) $menu->term_id;
		set_theme_mod( 'nav_menu_locations', $locations );
	}

	$items = wp_get_nav_menu_items( $menu->term_id );
	if ( ! $items ) {
		$home_id = wp_update_nav_menu_item( $menu->term_id, 0, array(
			'menu-item-title'  => __( 'Home', 'kungfu_2026' ),
			'menu-item-type'   => 'custom',
			'menu-item-url'    => home_url( '/' ),
			'menu-item-status' => 'publish',
		) );
		if ( is_wp_error( $home_id ) ) {
			return $home_id;
		}
	}

	$item_id = 0;
	$archive_url = get_post_type_archive_link( AKW_CHARACTER );
	$archive_path = wp_parse_url( $archive_url, PHP_URL_PATH );
	$site_hosts = array( wp_parse_url( home_url(), PHP_URL_HOST ), wp_parse_url( get_option( 'home' ), PHP_URL_HOST ) );
	foreach ( (array) $items as $item ) {
		$is_archive = 'post_type_archive' === $item->type && AKW_CHARACTER === $item->object;
		$is_old_link = 'custom' === $item->type
			&& in_array( wp_parse_url( $item->url, PHP_URL_HOST ), $site_hosts, true )
			&& untrailingslashit( (string) wp_parse_url( $item->url, PHP_URL_PATH ) ) === untrailingslashit( (string) $archive_path )
			&& wp_parse_url( $item->url, PHP_URL_QUERY ) === wp_parse_url( $archive_url, PHP_URL_QUERY );
		if ( $is_archive || $is_old_link ) {
			$item_id = (int) $item->ID;
			// Leave an existing, visible native archive link untouched.
			if ( $is_archive && ! $item->menu_item_parent && 'publish' === $item->post_status ) {
				flush_rewrite_rules( false );
				return array( 'menu_id' => (int) $menu->term_id, 'characters_item_id' => $item_id, 'created' => false );
			}
			break;
		}
	}

	$result = wp_update_nav_menu_item( $menu->term_id, $item_id, array(
		'menu-item-title'     => __( 'Characters', 'kungfu_2026' ),
		'menu-item-type'      => 'post_type_archive',
		'menu-item-object'    => AKW_CHARACTER,
		'menu-item-parent-id' => 0,
		'menu-item-status'    => 'publish',
	) );
	if ( is_wp_error( $result ) ) {
		return $result;
	}
	flush_rewrite_rules( false );
	return array( 'menu_id' => (int) $menu->term_id, 'characters_item_id' => (int) $result, 'created' => ! $item_id );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'kungfu seed', function () {
		$result = kungfu_2026_seed_site();
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}
		WP_CLI::log( 'Characters directory: ' . get_post_type_archive_link( AKW_CHARACTER ) );
		WP_CLI::success( $result['created'] ? 'Characters added to the sidebar menu.' : 'Characters sidebar menu link is ready.' );
	} );
}
