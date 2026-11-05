<?php
/**
 * Character wiki pages, kept outside the chapter sequence.
 *
 * @package kungfu_2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AKW_CHARACTER', 'character' );

/** Register the wiki and an editable starting outline for new entries. */
function kungfu_2026_register_characters() {
	$sections = array(
		'overview'     => array( __( 'Overview', 'kungfu_2026' ), __( 'Aliases, role, affiliations, and a short introduction…', 'kungfu_2026' ) ),
		'appearance'   => array( __( 'Appearance', 'kungfu_2026' ), __( 'Face, build, hair, clothing, and distinguishing features. Add reference images or a gallery here…', 'kungfu_2026' ) ),
		'skills'       => array( __( 'Skills & abilities', 'kungfu_2026' ), __( 'Fighting styles, techniques, strengths, limits, and weaknesses…', 'kungfu_2026' ) ),
		'equipment'    => array( __( 'Weapons & equipment', 'kungfu_2026' ), __( 'Weapons, signature items, and their properties…', 'kungfu_2026' ) ),
		'background'   => array( __( 'Personality & background', 'kungfu_2026' ), __( 'Motivations, history, and important relationships…', 'kungfu_2026' ) ),
		'references'   => array( __( 'References', 'kungfu_2026' ), __( 'Link to source chapters and visual references; include image credits and notes…', 'kungfu_2026' ) ),
	);
	$template = array();
	foreach ( $sections as $anchor => $section ) {
		$template[] = array( 'core/heading', array( 'level' => 3, 'content' => $section[0], 'anchor' => $anchor ) );
		$template[] = array( 'core/paragraph', array( 'placeholder' => $section[1] ) );
	}

	register_post_type(
		AKW_CHARACTER,
		array(
			'labels' => array(
				'name'                  => __( 'Characters', 'kungfu_2026' ),
				'singular_name'         => __( 'Character', 'kungfu_2026' ),
				'add_new_item'          => __( 'Add New Character', 'kungfu_2026' ),
				'edit_item'             => __( 'Edit Character', 'kungfu_2026' ),
				'new_item'              => __( 'New Character', 'kungfu_2026' ),
				'view_item'             => __( 'View Character', 'kungfu_2026' ),
				'view_items'            => __( 'View Characters', 'kungfu_2026' ),
				'search_items'          => __( 'Search Characters', 'kungfu_2026' ),
				'not_found'             => __( 'No characters found.', 'kungfu_2026' ),
				'not_found_in_trash'    => __( 'No characters found in Trash.', 'kungfu_2026' ),
				'all_items'             => __( 'All Characters', 'kungfu_2026' ),
				'archives'              => __( 'Characters', 'kungfu_2026' ),
				'featured_image'        => __( 'Character portrait', 'kungfu_2026' ),
				'set_featured_image'    => __( 'Set character portrait', 'kungfu_2026' ),
				'remove_featured_image' => __( 'Remove character portrait', 'kungfu_2026' ),
				'use_featured_image'    => __( 'Use as character portrait', 'kungfu_2026' ),
			),
			'public'        => true,
			'show_in_rest'  => true,
			'rest_base'     => 'characters',
			'has_archive'   => 'characters',
			'rewrite'       => array( 'slug' => 'characters', 'with_front' => false ),
			'menu_icon'     => 'dashicons-id',
			'menu_position' => 21,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'author' ),
			'template'      => $template,
			'template_lock' => false,
		)
	);
}
add_action( 'init', 'kungfu_2026_register_characters' );

/** Refresh routes once when the feature arrives on an existing installation. */
function kungfu_2026_character_rewrite_rules() {
	if ( ! current_user_can( 'manage_options' ) || '1' === get_option( 'akw_character_rewrite_version' ) ) {
		return;
	}
	flush_rewrite_rules( false );
	update_option( 'akw_character_rewrite_version', '1', false );
}
add_action( 'admin_init', 'kungfu_2026_character_rewrite_rules' );
add_action( 'after_switch_theme', 'kungfu_2026_character_rewrite_rules' );

/** Keep the public directory alphabetical, including subsequent pages. */
function kungfu_2026_character_archive_order( $query ) {
	if ( ! is_admin() && $query->is_main_query() && $query->is_post_type_archive( AKW_CHARACTER ) ) {
		$query->set( 'orderby', array( 'title' => 'ASC', 'ID' => 'ASC' ) );
	}
}
add_action( 'pre_get_posts', 'kungfu_2026_character_archive_order' );
