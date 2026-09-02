<?php
/**
 * Admin affordances for the chapter structure.
 *
 * There is nothing to edit here any more — arcs are tags and numbering is
 * derived from publication order — so this is read-only: a column that shows
 * where each post lands in the run.
 *
 * @package kungfu_2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Show the resolved numbering on the chapter list table.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function kungfu_2026_chapter_columns( $columns ) {
	$reordered = array();

	foreach ( $columns as $key => $label ) {
		$reordered[ $key ] = $label;

		if ( 'title' === $key ) {
			$reordered['akw_number'] = __( 'Numbering', 'kungfu_2026' );
		}
	}

	return $reordered;
}
add_filter( 'manage_' . AKW_CHAPTER . '_posts_columns', 'kungfu_2026_chapter_columns' );

/**
 * Render that column.
 *
 * @param string $column  Column key.
 * @param int    $post_id Chapter ID.
 */
function kungfu_2026_chapter_column_content( $column, $post_id ) {
	if ( 'akw_number' !== $column ) {
		return;
	}

	if ( ! akw_get_chapter_number( $post_id ) ) {
		echo '<em>' . esc_html__( 'Not in the sequence yet', 'kungfu_2026' ) . '</em>';
		return;
	}

	echo esc_html( akw_get_chapter_label( $post_id ) );

	$arc_index = akw_get_arc_index( $post_id );

	if ( ! $arc_index ) {
		echo ' <span class="description">' . esc_html__( '(no arc tag)', 'kungfu_2026' ) . '</span>';
		return;
	}

	printf(
		' <span class="description">(%s)</span>',
		esc_html(
			sprintf(
				/* translators: %d: position within the arc. */
				__( '#%d in arc', 'kungfu_2026' ),
				$arc_index
			)
		)
	);
}
add_action( 'manage_' . AKW_CHAPTER . '_posts_custom_column', 'kungfu_2026_chapter_column_content', 10, 2 );

/**
 * Sort that column.
 *
 * @param array $columns Sortable columns.
 * @return array
 */
function kungfu_2026_chapter_sortable_columns( $columns ) {
	$columns['akw_number'] = 'akw_number';

	return $columns;
}
add_filter( 'manage_edit-' . AKW_CHAPTER . '_sortable_columns', 'kungfu_2026_chapter_sortable_columns' );

/**
 * Sort the post list by chapter number when that column is clicked.
 *
 * Chapter number *is* publication order now, so this only has to hand the
 * query the date — no meta join, and nothing dropped from the list.
 *
 * Only when asked: the list keeps WordPress' own newest-first default.
 *
 * @param WP_Query $query Query.
 */
function kungfu_2026_chapter_admin_order( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( 'akw_number' !== $query->get( 'orderby' ) || AKW_CHAPTER !== $query->get( 'post_type' ) ) {
		return;
	}

	$order = 'desc' === strtolower( (string) $query->get( 'order' ) ) ? 'DESC' : 'ASC';

	$query->set(
		'orderby',
		array(
			'date' => $order,
			'ID'   => $order,
		)
	);
}
add_action( 'pre_get_posts', 'kungfu_2026_chapter_admin_order' );
