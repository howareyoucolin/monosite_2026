<?php
/**
 * Chapters, series and arcs.
 *
 * A series is a top-level akw_series term; its arcs are that term's children.
 * A chapter is filed under its arc, and the series term is kept on the post as
 * well, so series-wide queries never have to walk the hierarchy.
 *
 * @package kungfu_2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Post type holding one chapter. */
define( 'AKW_CHAPTER', 'akw_chapter' );

/** Hierarchical taxonomy: top level is a series, its children are that series' arcs. */
define( 'AKW_SERIES', 'akw_series' );

/**
 * Register the chapter post type.
 *
 * page-attributes is what gives each chapter a menu_order, which is how
 * chapters are sequenced inside their arc.
 */
function kungfu_2026_register_chapter_type() {
	register_post_type(
		AKW_CHAPTER,
		array(
			'labels'        => array(
				'name'               => __( 'Chapters', 'kungfu_2026' ),
				'singular_name'      => __( 'Chapter', 'kungfu_2026' ),
				'menu_name'          => __( 'Chapters', 'kungfu_2026' ),
				'add_new_item'       => __( 'Add New Chapter', 'kungfu_2026' ),
				'edit_item'          => __( 'Edit Chapter', 'kungfu_2026' ),
				'new_item'           => __( 'New Chapter', 'kungfu_2026' ),
				'view_item'          => __( 'View Chapter', 'kungfu_2026' ),
				'search_items'       => __( 'Search Chapters', 'kungfu_2026' ),
				'not_found'          => __( 'No chapters found.', 'kungfu_2026' ),
				'all_items'          => __( 'All Chapters', 'kungfu_2026' ),
			),
			'public'        => true,
			'has_archive'   => true,
			'menu_icon'     => 'dashicons-book-alt',
			'menu_position' => 5,
			'supports'      => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'revisions', 'page-attributes' ),
			'taxonomies'    => array( AKW_SERIES ),
			'rewrite'       => array(
				'slug'       => 'chapter',
				'with_front' => false,
			),
			'show_in_rest'  => true,
		)
	);
}
add_action( 'init', 'kungfu_2026_register_chapter_type' );

/**
 * Register the series/arc taxonomy.
 */
function kungfu_2026_register_series_taxonomy() {
	register_taxonomy(
		AKW_SERIES,
		array( AKW_CHAPTER ),
		array(
			'labels'            => array(
				'name'              => __( 'Series & Arcs', 'kungfu_2026' ),
				'singular_name'     => __( 'Series or Arc', 'kungfu_2026' ),
				'menu_name'         => __( 'Series & Arcs', 'kungfu_2026' ),
				'all_items'         => __( 'All Series & Arcs', 'kungfu_2026' ),
				'edit_item'         => __( 'Edit Series or Arc', 'kungfu_2026' ),
				'add_new_item'      => __( 'Add New Series or Arc', 'kungfu_2026' ),
				'parent_item'       => __( 'Parent Series', 'kungfu_2026' ),
				'parent_item_colon' => __( 'Parent Series:', 'kungfu_2026' ),
				'search_items'      => __( 'Search Series & Arcs', 'kungfu_2026' ),
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array(
				'slug'         => 'series',
				'with_front'   => false,
				'hierarchical' => true,
			),
		)
	);
}
add_action( 'init', 'kungfu_2026_register_series_taxonomy' );

/**
 * Register the stored fields.
 *
 * The computed ones are exposed to REST read-only — they are derived by
 * akw_recalculate_series(), so letting a client write them would only ever
 * introduce drift.
 */
function kungfu_2026_register_chapter_meta() {
	$computed_post_meta = array( 'akw_chapter_number', 'akw_arc_index' );
	foreach ( $computed_post_meta as $key ) {
		register_post_meta(
			AKW_CHAPTER,
			$key,
			array(
				'type'          => 'integer',
				'single'        => true,
				'default'       => 0,
				'show_in_rest'  => true,
				'auth_callback' => '__return_false',
			)
		);
	}

	// Author-set: where an arc sits in its series.
	register_term_meta(
		AKW_SERIES,
		'akw_order',
		array(
			'type'              => 'integer',
			'single'            => true,
			'default'           => 0,
			'show_in_rest'      => true,
			'sanitize_callback' => 'absint',
			'auth_callback'     => function () {
				return current_user_can( 'manage_categories' );
			},
		)
	);

	// Author-set on a series: 'en' or 'zh'. Drives how labels are formatted.
	register_term_meta(
		AKW_SERIES,
		'akw_lang',
		array(
			'type'              => 'string',
			'single'            => true,
			'default'           => 'en',
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_key',
			'auth_callback'     => function () {
				return current_user_can( 'manage_categories' );
			},
		)
	);

	$computed_term_meta = array( 'akw_chapter_offset', 'akw_chapter_count', 'akw_arc_position', 'akw_chapter_total' );
	foreach ( $computed_term_meta as $key ) {
		register_term_meta(
			AKW_SERIES,
			$key,
			array(
				'type'          => 'integer',
				'single'        => true,
				'default'       => 0,
				'show_in_rest'  => true,
				'auth_callback' => '__return_false',
			)
		);
	}
}
add_action( 'init', 'kungfu_2026_register_chapter_meta' );
